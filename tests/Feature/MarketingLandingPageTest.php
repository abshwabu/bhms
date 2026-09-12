<?php

namespace Tests\Feature;

use App\Domain\Marketing\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingLandingPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test public marketing landing page renders successfully with all required sections.
     */
    public function test_landing_page_renders_successfully_with_all_sections(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // SEO and Meta Tags
        $response->assertSee('Metro HMS | Modern Hospital Management System', false);
        $response->assertSee('meta name="description"', false);
        $response->assertSee('SoftwareApplication', false);

        // Hero Section
        $response->assertSee('One Platform to Run Your', false);
        $response->assertSee('Entire Hospital', false);
        $response->assertSee('Schedule a Demo', false);
        $response->assertSee('Explore Interactive Portal', false);

        // Telegram Reporting Spotlight
        $response->assertSee('Real-Time Telegram Reports & Critical Alerts', false);
        $response->assertSee('Daily 8:00 AM Operational Digest', false);
        $response->assertSee('Unbatched Critical Clinical Push', false);

        // Core Features
        $response->assertSee('Patient Registry & MPI', false);
        $response->assertSee('Inpatient & Bed Allocation Map', false);
        $response->assertSee('Doctor EHR & Clinical Notes', false);
        $response->assertSee('Pharmacy & FEFO Inventory', false);
        $response->assertSee('Emergency Trauma & CAD', false);
        $response->assertSee('Billing, Insurance & Claims', false);

        // How it Works
        $response->assertSee('How Metro HMS Transforms Your Operations', false);
        $response->assertSee('Intake & Triage', false);
        $response->assertSee('Unified Clinical Care', false);

        // Pricing & FAQ
        $response->assertSee('Predictable Pricing For Hospitals', false);
        $response->assertSee('Regional Medical Center', false);
        $response->assertSee('Frequently Asked Questions', false);

        // Demo Request Form
        $response->assertSee('lead-capture-form', false);
        $response->assertSee('Schedule VIP Walkthrough', false);
    }

    /**
     * Test authenticated application portal route /app renders.
     */
    public function test_authenticated_hospital_app_portal_renders(): void
    {
        $response = $this->get('/app');

        $response->assertStatus(200);
        $response->assertSee('id="patient-app"', false);
    }

    /**
     * Test sitemap.xml endpoint.
     */
    public function test_sitemap_xml_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<urlset', false);
        $response->assertSee('#telegram-spotlight', false);
        $response->assertSee('#demo-request', false);
    }

    /**
     * Test robots.txt endpoint.
     */
    public function test_robots_txt_returns_valid_content(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Disallow: /api/', false);
        $response->assertSee('Sitemap:', false);
    }

    /**
     * Test demo request form submission stores lead in database.
     */
    public function test_demo_request_submission_captures_lead(): void
    {
        $payload = [
            'name' => 'Dr. Elizabeth Chen',
            'email' => 'e.chen@memorialhealth.org',
            'phone' => '+1 (555) 789-0123',
            'hospital_name' => 'Memorial General Health System',
            'hospital_type' => 'academic_center',
            'hospital_size' => '150_500',
            'branches_count' => 3,
            'modules_of_interest' => ['telegram_alerts', 'inpatient_bed_map', 'billing_insurance'],
            'preferred_demo_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Evaluating replacement for legacy Cerner EHR with focus on real-time Telegram panic lab alerts.',
        ];

        $response = $this->postJson('/api/v1/leads', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hospital_name', 'Memorial General Health System')
            ->assertJsonPath('data.status', 'new');

        $leadId = $response->json('data.lead_id');

        // Confirm database record created
        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'name' => 'Dr. Elizabeth Chen',
            'email' => 'e.chen@memorialhealth.org',
            'hospital_name' => 'Memorial General Health System',
            'status' => 'new',
            'hospital_size' => '150_500',
            'branches_count' => 3,
        ]);

        $savedLead = Lead::find($leadId);
        $this->assertNotNull($savedLead);
        $this->assertContains('telegram_alerts', $savedLead->modules_of_interest);
    }

    /**
     * Test demo request validation rules.
     */
    public function test_demo_request_validation_fails_for_invalid_input(): void
    {
        $invalidPayload = [
            'name' => '',
            'email' => 'not-an-email',
            'hospital_name' => '',
        ];

        $response = $this->postJson('/api/v1/leads', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'hospital_name']);
    }

    /**
     * Test lead retrieval API endpoint.
     */
    public function test_leads_can_be_retrieved(): void
    {
        Lead::create([
            'name' => 'Dr. Gregory House',
            'email' => 'house@princeton-plainsboro.org',
            'hospital_name' => 'Princeton-Plainsboro Teaching Hospital',
            'hospital_type' => 'academic_center',
            'hospital_size' => '500_plus',
            'status' => 'new',
        ]);

        $response = $this->getJson('/api/v1/leads');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.hospital_name', 'Princeton-Plainsboro Teaching Hospital');
    }
}
