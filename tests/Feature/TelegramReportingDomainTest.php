<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\Bed;
use App\Domain\Laboratory\Models\LabOrder;
use App\Domain\Laboratory\Models\LabResult;
use App\Domain\Pharmacy\Models\Drug;
use App\Domain\Pharmacy\Models\DrugBatch;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Domain\Telegram\Console\Commands\RetryFailedTelegramMessagesCommand;
use App\Domain\Telegram\Console\Commands\SendDailyTelegramDigestCommand;
use App\Domain\Telegram\Console\Commands\SendShiftHandoverReportCommand;
use App\Domain\Telegram\Models\TelegramChannel;
use App\Domain\Telegram\Models\TelegramMessageLog;
use App\Domain\Telegram\Services\TelegramAlertDispatcherService;
use App\Domain\Telegram\Services\TelegramApiService;
use App\Domain\Telegram\Services\TelegramCommandParserService;
use App\Domain\Telegram\Services\TelegramReportGeneratorService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TelegramReportingDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $user;
    protected \App\Domain\OPD\Models\Department $department;
    protected \App\Domain\IPD\Models\Ward $ward;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'name' => 'Metro General Hospital Group',
            'code' => 'MGH-GRP',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'organization_id' => $this->org->id,
            'name' => 'Metro Central Hospital',
            'code' => 'MCH-01',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
        ]);

        $this->department = \App\Domain\OPD\Models\Department::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'name' => 'General Medicine',
            'code' => 'GENMED',
            'is_active' => true,
        ]);

        $this->ward = \App\Domain\IPD\Models\Ward::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'name' => 'Main ICU Ward',
            'code' => 'ICU-W1',
            'ward_type' => 'icu',
            'capacity' => 10,
            'daily_rate' => 250.00,
            'is_active' => true,
        ]);
    }

    /**
     * 1. Test Daily Operational Digest generation and dispatch.
     */
    public function test_daily_operational_digest_generates_and_dispatches_correctly(): void
    {
        // Seed bed data
        Bed::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'ward_id' => $this->ward->id,
            'bed_number' => 'ICU-101',
            'bed_type' => 'icu',
            'status' => 'available',
            'is_active' => true,
        ]);
        Bed::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'ward_id' => $this->ward->id,
            'bed_number' => 'GEN-201',
            'bed_type' => 'general',
            'status' => 'occupied',
            'is_active' => true,
        ]);

        // Register Telegram Channel for daily digest
        $channel = TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '-1009988776655',
            'name' => 'Executive Leadership Channel',
            'role' => 'admin',
            'allowed_report_types' => ['daily_digest', 'critical_alerts'],
            'allowed_commands' => ['/digest', '/beds', '/revenue'],
            'is_active' => true,
        ]);

        // Mock Telegram Bot API sendMessage endpoint
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 90210,
                    'chat' => ['id' => -1009988776655],
                ],
            ], 200),
        ]);

        // Run artisan command
        $this->artisan('hms:telegram-daily-digest')
            ->assertSuccessful();

        // Verify message log was recorded
        $log = TelegramMessageLog::where('chat_id', '-1009988776655')->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);
        $this->assertEquals('daily_digest', $log->message_type);
        $this->assertStringContainsString('DAILY HOSPITAL OPERATIONAL DIGEST', $log->content);
        $this->assertStringContainsString('ICU Beds Available', $log->content);
        $this->assertStringContainsString('Total Beds: <b>2</b>', $log->content);
        $this->assertEquals('90210', $log->telegram_message_id);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] === '-1009988776655'
                && str_contains($request['text'], 'DAILY HOSPITAL OPERATIONAL DIGEST');
        });
    }

    /**
     * 2. Test Role-Based Telegram Channel Isolation.
     */
    public function test_channel_role_based_isolation_and_subscriptions(): void
    {
        // 1. Pharmacy channel: only low_stock and inventory
        $pharmacyChannel = TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '-1001111111111',
            'name' => 'Pharmacy Dispensary Chat',
            'role' => 'pharmacy',
            'allowed_report_types' => ['critical_alerts'],
            'allowed_commands' => ['/stock', '/help'],
            'is_active' => true,
        ]);

        // 2. Doctors channel: only emergency and lab alerts
        $doctorsChannel = TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '-1002222222222',
            'name' => 'On-Call Doctors Group',
            'role' => 'doctors',
            'allowed_report_types' => ['critical_alerts', 'shift_handover'],
            'allowed_commands' => ['/beds', '/patients', '/handover', '/help'],
            'is_active' => true,
        ]);

        // 3. Finance channel: only daily digest and revenue
        $financeChannel = TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '-1003333333333',
            'name' => 'Finance & Revenue Group',
            'role' => 'finance',
            'allowed_report_types' => ['daily_digest'],
            'allowed_commands' => ['/revenue', '/help'],
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 12345]], 200),
        ]);

        $alertDispatcher = app(TelegramAlertDispatcherService::class);

        // Dispatch an Emergency ESI-1 alert: Doctors channel should receive it; Pharmacy and Finance channels must NOT!
        $alertDispatcher->dispatchCriticalAlert('emergency_esi1', [
            'case_number' => 'EM-999',
            'patient_name' => 'John Doe',
            'chief_complaint' => 'Severe Chest Pain / Ventricular Fibrillation',
            'location' => 'Trauma Room A',
        ]);

        $doctorLogs = TelegramMessageLog::where('chat_id', '-1002222222222')->get();
        $pharmacyLogs = TelegramMessageLog::where('chat_id', '-1001111111111')->get();
        $financeLogs = TelegramMessageLog::where('chat_id', '-1003333333333')->get();

        $this->assertCount(1, $doctorLogs, 'Doctors channel must receive emergency alert');
        $this->assertCount(0, $pharmacyLogs, 'Pharmacy channel must NOT receive emergency alert');
        $this->assertCount(0, $financeLogs, 'Finance channel must NOT receive emergency alert');

        // Dispatch a Low Stock alert: Pharmacy channel should receive it; Doctors and Finance channels must NOT!
        $alertDispatcher->dispatchCriticalAlert('low_stock', [
            'drug_name' => 'Morphine 10mg/mL Ampoule',
            'current_stock' => 2,
            'reorder_threshold' => 25,
        ]);

        $pharmacyStockLogs = TelegramMessageLog::where('chat_id', '-1001111111111')->where('message_type', 'critical_alert')->get();
        $financeStockLogs = TelegramMessageLog::where('chat_id', '-1003333333333')->where('message_type', 'critical_alert')->get();

        $this->assertCount(1, $pharmacyStockLogs, 'Pharmacy channel must receive low stock alert');
        $this->assertCount(0, $financeStockLogs, 'Finance channel must NOT receive low stock alert');
    }

    /**
     * 3. Test Inbound Bot Commands with Strict RBAC Enforcement.
     */
    public function test_bot_commands_enforce_strict_rbac_and_execute_queries(): void
    {
        // Register Pharmacy channel
        $pharmacyChannel = TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '-1005555555555',
            'name' => 'Pharmacy Dispensary Room',
            'role' => 'pharmacy',
            'allowed_report_types' => ['critical_alerts'],
            'allowed_commands' => ['/stock', '/help'],
            'is_active' => true,
        ]);

        // Register Finance channel
        $financeChannel = TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '-1006666666666',
            'name' => 'Billing & Cash Office',
            'role' => 'finance',
            'allowed_report_types' => ['daily_digest'],
            'allowed_commands' => ['/revenue', '/help'],
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 777]], 200),
        ]);

        $commandParser = app(TelegramCommandParserService::class);

        // 1. Pharmacy attempts to run /revenue today -> MUST BE FORBIDDEN
        $forbiddenResult = $commandParser->processCommand('-1005555555555', '/revenue today');
        $this->assertFalse($forbiddenResult['authorized']);
        $this->assertEquals('forbidden', $forbiddenResult['status']);
        $this->assertStringContainsString('Access Denied', $forbiddenResult['response']);
        $this->assertStringContainsString('pharmacy', $forbiddenResult['response']);

        // 2. Finance runs /revenue today -> MUST BE ALLOWED
        $allowedResult = $commandParser->processCommand('-1006666666666', '/revenue today');
        $this->assertTrue($allowedResult['authorized']);
        $this->assertEquals('success', $allowedResult['status']);
        $this->assertStringContainsString('BILLING & REVENUE SUMMARY', $allowedResult['response']);

        // 3. Finance attempts to run /stock -> MUST BE FORBIDDEN
        $stockForbidden = $commandParser->processCommand('-1006666666666', '/stock');
        $this->assertFalse($stockForbidden['authorized']);

        // 4. Pharmacy runs /stock -> MUST BE ALLOWED
        $stockAllowed = $commandParser->processCommand('-1005555555555', '/stock');
        $this->assertTrue($stockAllowed['authorized']);
        $this->assertStringContainsString('PHARMACY INVENTORY', $stockAllowed['response']);

        // 5. Unknown unauthenticated chat ID attempts command -> MUST BE REJECTED AS UNREGISTERED
        $unregisteredResult = $commandParser->processCommand('9999999999', '/help');
        $this->assertFalse($unregisteredResult['authorized']);
        $this->assertEquals('unregistered_chat', $unregisteredResult['status']);
    }

    /**
     * 4. Test Shift Handover Report dispatch.
     */
    public function test_shift_handover_report_dispatches_to_clinical_teams(): void
    {
        // Nursing channel
        TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '-1007777777777',
            'name' => 'Ward Nursing Supervisors',
            'role' => 'nursing',
            'allowed_report_types' => ['shift_handover'],
            'allowed_commands' => ['/handover', '/beds'],
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 888]], 200),
        ]);

        $this->artisan('hms:telegram-shift-handover', ['shift' => 'evening'])
            ->assertSuccessful();

        $log = TelegramMessageLog::where('chat_id', '-1007777777777')->first();
        $this->assertNotNull($log);
        $this->assertEquals('shift_handover', $log->message_type);
        $this->assertStringContainsString('SHIFT HANDOVER REPORT', $log->content);
        $this->assertStringContainsString('Evening Shift', $log->content);
    }

    /**
     * 5. Test Delivery Failure, Logging, and Retry Engine with Backoff.
     */
    public function test_delivery_audit_and_retry_engine_with_backoff(): void
    {
        $channel = TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '-1008888888888',
            'name' => 'ICU Nurse Station',
            'role' => 'nursing',
            'allowed_report_types' => ['critical_alerts'],
            'allowed_commands' => ['/beds'],
            'is_active' => true,
        ]);

        // 1. Initial attempt fails with 500 Bad Gateway from Telegram
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::sequence()
                ->push(['ok' => false, 'description' => 'Internal Server Error: Bad Gateway'], 500)
                ->push(['ok' => true, 'result' => ['message_id' => 999111]], 200),
        ]);

        $apiService = app(TelegramApiService::class);
        $log = $apiService->sendMessage(
            chatId: $channel->chat_id,
            text: 'Test Notification',
            channel: $channel,
            messageType: 'critical_alert',
            maxRetries: 2
        );

        $this->assertEquals('retrying', $log->status);
        $this->assertEquals(0, $log->retry_count);
        $this->assertNotNull($log->error_message);
        $this->assertStringContainsString('Bad Gateway', $log->error_message);

        // 2. Execute retry command - next attempt should succeed
        $this->artisan('hms:telegram-retry-failed')
            ->assertSuccessful();

        $updatedLog = $log->fresh();
        $this->assertEquals('sent', $updatedLog->status);
        $this->assertEquals(1, $updatedLog->retry_count);
        $this->assertEquals('999111', $updatedLog->telegram_message_id);
    }

    /**
     * 6. Test Channel CRUD & API endpoints.
     */
    public function test_channel_crud_and_endpoints(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Create channel
        $response = $this->postJson('/api/v1/telegram/channels', [
            'chat_id' => '-1004443332221',
            'name' => 'Surgical Department Telegram Feed',
            'role' => 'doctors',
            'bot_token_ref' => 'TELEGRAM_BOT_TOKEN',
            'allowed_report_types' => ['daily_digest', 'critical_alerts'],
            'allowed_commands' => ['/beds', '/patients'],
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'organization_id' => $this->org->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Surgical Department Telegram Feed')
            ->assertJsonPath('data.role', 'doctors');

        $channelId = $response->json('data.id');

        // 2. List channels
        $listResponse = $this->getJson('/api/v1/telegram/channels');
        $listResponse->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);

        // 3. Test Message Ping
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 54321]], 200),
        ]);

        $pingResponse = $this->postJson("/api/v1/telegram/channels/{$channelId}/test");
        $pingResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // 4. Update channel
        $updateResponse = $this->putJson("/api/v1/telegram/channels/{$channelId}", [
            'name' => 'Updated Surgical Feed',
        ]);
        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Surgical Feed');

        // 5. Delete channel
        $deleteResponse = $this->deleteJson("/api/v1/telegram/channels/{$channelId}");
        $deleteResponse->assertStatus(200);

        $this->assertSoftDeleted('telegram_channels', ['id' => $channelId]);
    }

    /**
     * 7. Test Public Telegram Webhook Endpoint.
     */
    public function test_telegram_webhook_processes_inbound_chat_update(): void
    {
        TelegramChannel::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'chat_id' => '987654321',
            'name' => 'Chief Medical Officer Chat',
            'role' => 'doctors',
            'allowed_report_types' => ['daily_digest'],
            'allowed_commands' => ['/beds', '/patients', '/help'],
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 9999]], 200),
        ]);

        $webhookPayload = [
            'update_id' => 1234567,
            'message' => [
                'message_id' => 101,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Dr. Gregory',
                    'username' => 'dr_house',
                ],
                'chat' => [
                    'id' => 987654321,
                    'first_name' => 'Dr. Gregory',
                    'username' => 'dr_house',
                    'type' => 'private',
                ],
                'date' => 1726000000,
                'text' => '/beds',
            ],
        ];

        $response = $this->postJson('/api/v1/telegram/webhook', $webhookPayload);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('result.authorized', true)
            ->assertJsonPath('result.command', '/beds');
    }
}
