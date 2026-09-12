<?php

namespace App\Domain\Marketing\Http\Controllers;

use App\Domain\Marketing\Models\Lead;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MarketingLandingController extends Controller
{
    /**
     * Render the public marketing landing page.
     */
    public function index()
    {
        return view('landing');
    }

    /**
     * Capture and store a demo request / contact lead.
     */
    public function storeLead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:50',
            'hospital_name' => 'required|string|max:180',
            'hospital_type' => 'nullable|string|max:60',
            'hospital_size' => 'nullable|string|max:50',
            'branches_count' => 'nullable|integer|min:1|max:500',
            'modules_of_interest' => 'nullable|array',
            'preferred_demo_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:2000',
        ]);

        $lead = Lead::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'hospital_name' => $validated['hospital_name'],
            'hospital_type' => $validated['hospital_type'] ?? 'general_hospital',
            'hospital_size' => $validated['hospital_size'] ?? '50_150',
            'branches_count' => $validated['branches_count'] ?? 1,
            'modules_of_interest' => $validated['modules_of_interest'] ?? [],
            'preferred_demo_date' => $validated['preferred_demo_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'new',
            'source' => $request->input('source', 'website_landing_page'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your interest in Metro HMS! Our hospital solutions team will contact you within 24 hours to schedule your personalized interactive walkthrough.',
            'data' => [
                'lead_id' => $lead->id,
                'hospital_name' => $lead->hospital_name,
                'status' => $lead->status,
                'created_at' => $lead->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Retrieve leads for evaluation or administration.
     */
    public function listLeads(Request $request): JsonResponse
    {
        $query = Lead::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                    ->orWhere('email', 'ilike', $search)
                    ->orWhere('hospital_name', 'ilike', $search);
            });
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($leads);
    }

    /**
     * Generate sitemap.xml for SEO indexing.
     */
    public function sitemap(): Response
    {
        $baseUrl = url('/');
        $lastMod = now()->toDateString();

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{$baseUrl}/</loc>
        <lastmod>{$lastMod}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{$baseUrl}/#features</loc>
        <lastmod>{$lastMod}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{$baseUrl}/#telegram-spotlight</loc>
        <lastmod>{$lastMod}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{$baseUrl}/#how-it-works</loc>
        <lastmod>{$lastMod}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{$baseUrl}/#pricing</loc>
        <lastmod>{$lastMod}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{$baseUrl}/#faq</loc>
        <lastmod>{$lastMod}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{$baseUrl}/#demo-request</loc>
        <lastmod>{$lastMod}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
</urlset>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate robots.txt for search engine crawlers.
     */
    public function robots(): Response
    {
        $sitemapUrl = url('/sitemap.xml');
        $content = "User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /app\n\nSitemap: {$sitemapUrl}\n";

        return response($content, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
