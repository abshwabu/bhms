<?php

namespace App\Domain\Administration\Services\Notification;

use App\Domain\Administration\Models\NotificationLog;
use App\Domain\Administration\Models\NotificationTemplate;
use App\Domain\Administration\Services\Notification\Contracts\NotificationProviderInterface;
use App\Domain\Administration\Services\Notification\Providers\EmailNotificationProvider;
use App\Domain\Administration\Services\Notification\Providers\PushNotificationProvider;
use App\Domain\Administration\Services\Notification\Providers\SmsNotificationProvider;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationEngineService
{
    /** @var array<string, NotificationProviderInterface> */
    protected array $providers = [];

    public function __construct(
        SmsNotificationProvider $smsProvider,
        EmailNotificationProvider $emailProvider,
        PushNotificationProvider $pushProvider
    ) {
        $this->providers['sms'] = $smsProvider;
        $this->providers['email'] = $emailProvider;
        $this->providers['push'] = $pushProvider;
    }

    /**
     * Register or override a custom provider for a channel.
     */
    public function registerProvider(string $channel, NotificationProviderInterface $provider): void
    {
        $this->providers[$channel] = $provider;
    }

    /**
     * Dispatch notification with automated retry tracking and zero silent drops.
     */
    public function send(array $data): NotificationLog
    {
        $channel = strtolower($data['channel']);
        if (! isset($this->providers[$channel])) {
            throw new DomainException("Unsupported notification channel: [{$channel}]. Supported: sms, email, push.");
        }

        $subject = $data['subject'] ?? null;
        $body = $data['body'] ?? '';
        $templateId = null;

        // Render template if template_code or template_id is specified
        if (! empty($data['template_code'])) {
            $template = NotificationTemplate::where('code', $data['template_code'])
                ->where('channel', $channel)
                ->where('is_active', true)
                ->first();

            if ($template) {
                $templateId = $template->id;
                $rendered = $template->render($data['variables'] ?? []);
                $subject = $rendered['subject'] ?: $subject;
                $body = $rendered['body'];
            }
        } elseif (! empty($data['template_id'])) {
            $template = NotificationTemplate::find($data['template_id']);
            if ($template) {
                $templateId = $template->id;
                $rendered = $template->render($data['variables'] ?? []);
                $subject = $rendered['subject'] ?: $subject;
                $body = $rendered['body'];
            }
        }

        if (empty($body)) {
            throw new DomainException('Notification body cannot be empty.');
        }

        $branchId = $data['branch_id'] ?? (app()->bound('current_branch_id') ? app('current_branch_id') : null);
        $orgId = $data['organization_id'] ?? (app()->bound('current_organization_id') ? app('current_organization_id') : null);

        if (! $orgId && $branchId) {
            $branch = \App\Domain\Shared\Models\Branch::find($branchId);
            $orgId = $branch?->organization_id;
        }

        if (! $orgId) {
            $orgId = \App\Domain\Shared\Models\Organization::first()?->id;
        }

        // 1. Immutable Log Entry Created in Queued State
        $log = NotificationLog::create([
            'organization_id' => $orgId,
            'branch_id' => $branchId,
            'template_id' => $templateId,
            'recipient_user_id' => $data['recipient_user_id'] ?? null,
            'channel' => $channel,
            'recipient' => $data['recipient'],
            'event_type' => $data['event_type'] ?? 'general_notification',
            'subject' => $subject,
            'body' => $body,
            'payload' => $data['payload'] ?? [],
            'status' => 'queued',
            'max_retries' => $data['max_retries'] ?? 3,
        ]);

        // 2. Dispatch to Provider
        $provider = $this->providers[$channel];
        $result = $provider->send($log);

        if ($result['success']) {
            $log->markSent($result['provider']);
        } else {
            // Acceptance Criteria: Failures are retried and logged, not silently dropped
            $log->markFailed($result['error'] ?? 'Unknown dispatch error', $result['provider']);
        }

        return $log->fresh();
    }

    /**
     * Retry a previously failed/retrying notification.
     */
    public function retry(NotificationLog $log): NotificationLog
    {
        if ($log->status === 'sent') {
            return $log;
        }

        $channel = $log->channel;
        if (! isset($this->providers[$channel])) {
            $log->markFailed("Unsupported channel: {$channel}", 'system');
            return $log;
        }

        $provider = $this->providers[$channel];
        $result = $provider->send($log);

        if ($result['success']) {
            $log->markSent($result['provider']);
        } else {
            $log->markFailed($result['error'] ?? 'Retry transmission failed', $result['provider']);
        }

        return $log->fresh();
    }

    /**
     * Process batch retries for all pending notifications.
     */
    public function retryPendingNotifications(int $limit = 50): array
    {
        $pendingLogs = NotificationLog::where('status', 'retrying')
            ->whereColumn('retry_count', '<', 'max_retries')
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        $successCount = 0;
        $failedCount = 0;

        foreach ($pendingLogs as $log) {
            $updated = $this->retry($log);
            if ($updated->status === 'sent') {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        return [
            'processed' => $pendingLogs->count(),
            'recovered_sent' => $successCount,
            'still_failed' => $failedCount,
        ];
    }

    /**
     * Query notification logs with filters.
     */
    public function getLogs(array $filters = []): LengthAwarePaginator
    {
        $query = NotificationLog::with(['template', 'recipientUser', 'branch'])->latest('created_at');

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }
        if (! empty($filters['recipient'])) {
            $query->where('recipient', 'ilike', '%' . $filters['recipient'] . '%');
        }

        $perPage = min((int) ($filters['per_page'] ?? 25), 100);

        return $query->paginate($perPage);
    }

    /**
     * Pre-populate standard HMS notification templates.
     */
    public function seedDefaultTemplates(string $organizationId, ?string $branchId = null): void
    {
        $templates = [
            [
                'code' => 'appointment_confirmed',
                'name' => 'Appointment Booking Confirmation',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Hello {{patient_name}}, your appointment with {{doctor_name}} is confirmed for {{appointment_date}} at {{hospital_name}}. Token: {{token_number}}.',
                'available_variables' => ['patient_name', 'doctor_name', 'appointment_date', 'hospital_name', 'token_number'],
            ],
            [
                'code' => 'prescription_ready',
                'name' => 'Pharmacy Prescription Dispensed Alert',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {{patient_name}}, your prescription {{prescription_number}} has been prepared and is ready for pickup at {{pharmacy_name}}.',
                'available_variables' => ['patient_name', 'prescription_number', 'pharmacy_name'],
            ],
            [
                'code' => 'critical_lab_alert',
                'name' => 'Urgent Critical Lab Alert for Clinician',
                'channel' => 'push',
                'subject' => 'CRITICAL LAB ALERT: {{patient_mrn}}',
                'body' => 'STAT: Panic value detected for {{test_name}} on patient {{patient_name}} (MRN: {{patient_mrn}}). Value: {{result_value}} {{units}}.',
                'available_variables' => ['patient_name', 'patient_mrn', 'test_name', 'result_value', 'units'],
            ],
            [
                'code' => 'invoice_generated',
                'name' => 'Patient Invoice & Payment Receipt',
                'channel' => 'email',
                'subject' => 'Hospital Invoice #{{invoice_number}} - {{hospital_name}}',
                'body' => 'Dear {{patient_name}},\n\nYour invoice #{{invoice_number}} for amount {{currency}} {{total_amount}} has been generated. Balance due: {{balance_due}}.\n\nThank you,\n{{hospital_name}} Finance Desk',
                'available_variables' => ['patient_name', 'invoice_number', 'currency', 'total_amount', 'balance_due', 'hospital_name'],
            ],
        ];

        foreach ($templates as $tmpl) {
            NotificationTemplate::firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'code' => $tmpl['code'],
                    'channel' => $tmpl['channel'],
                ],
                [
                    'branch_id' => $branchId,
                    'name' => $tmpl['name'],
                    'subject' => $tmpl['subject'],
                    'body' => $tmpl['body'],
                    'available_variables' => $tmpl['available_variables'],
                    'is_active' => true,
                ]
            );
        }
    }
}
