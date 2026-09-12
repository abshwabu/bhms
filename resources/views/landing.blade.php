<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SEO Primary Meta Tags -->
    <title>Metro HMS | Modern Hospital Management System & Real-Time Telegram Reporting</title>
    <meta name="title" content="Metro HMS | Modern Hospital Management System & Real-Time Telegram Reporting">
    <meta name="description" content="The unified, HIPAA-compliant hospital operating system powering patient intake, clinical EHR, inpatient bed tracking, pharmacy, billing, and real-time Telegram alerts.">
    <meta name="keywords" content="hospital management system, HMS software, inpatient bed management, clinical EHR, hospital billing software, hospital telegram reporting, HIPAA compliant HMS, healthcare ERP">
    <meta name="author" content="Metro Health Technologies">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="Metro HMS | Next-Generation Hospital Management System">
    <meta property="og:description" content="One modern platform to run your entire hospital — from OPD to inpatient bed allocation, pharmacy, billing, and real-time Telegram emergency alerts.">
    <meta property="og:image" content="{{ asset('build/assets/og-preview.png') }}">
    <meta property="og:site_name" content="Metro HMS">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url('/') }}">
    <meta property="twitter:title" content="Metro HMS | Modern Hospital Operating System">
    <meta property="twitter:description" content="Streamline hospital operations, eliminate billing leakages, and empower doctors with real-time Telegram critical alerts.">
    <meta property="twitter:image" content="{{ asset('build/assets/og-preview.png') }}">

    <!-- Structured Data JSON-LD for Search Engines -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "SoftwareApplication",
      "name": "Metro HMS",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Cloud / Web-based",
      "offers": {
        "@@type": "Offer",
        "price": "65000.00",
        "priceCurrency": "ETB"
      },
      "description": "Comprehensive, multi-branch Hospital Management System featuring patient registry, clinical EHR, IPD bed management, pharmacy dispensing, billing, and real-time Telegram operational digests.",
      "featureList": [
        "Master Patient Index with Biometric Walk-In Registration",
        "Specialist OPD Scheduling & Dynamic Queue Tokens",
        "Interactive Graphical Ward & ICU Bed Map",
        "Longitudinal EHR with CDS Clinical Decision Warnings",
        "Pharmacy FEFO Expiry Tracking & Dispensing",
        "Automated Multi-Payer Billing & Tariff Invoicing",
        "Emergency CAD Dispatch & ESI Acuity Triage",
        "Real-Time Telegram Reporting Bot with RBAC Permissions",
        "HIPAA-Compliant Column Encryption & Audit Logging"
      ]
    }
    </script>

    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-900 text-slate-100 font-sans antialiased selection:bg-sky-500 selection:text-white">

    <!-- Top Announcement Bar -->
    <div class="bg-gradient-to-r from-sky-600 via-blue-600 to-indigo-600 text-white text-xs font-semibold py-2 px-4 text-center">
        <span>🚀 Version 2.4 Released: Integrated Real-Time Telegram Reporting & Critical Clinical Alert Push Engine.</span>
        <a href="#telegram-spotlight" class="underline ml-2 hover:text-sky-100">See Live Demo &rarr;</a>
    </div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 bg-slate-900/90 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center font-black text-2xl text-white shadow-lg shadow-sky-500/30 group-hover:scale-105 transition">
                    +
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight text-white flex items-center gap-1.5">
                        Metro<span class="text-sky-400">HMS</span>
                    </span>
                    <span class="block text-[10px] uppercase font-mono tracking-widest text-slate-400">Hospital OS</span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="#features" class="hover:text-sky-400 transition">Modules</a>
                <a href="#telegram-spotlight" class="hover:text-sky-400 transition flex items-center gap-1.5 text-sky-300">
                    <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                    Telegram Alerts
                </a>
                <a href="#how-it-works" class="hover:text-sky-400 transition">How It Works</a>
                <a href="#product-preview" class="hover:text-sky-400 transition">Preview</a>
                <a href="#pricing" class="hover:text-sky-400 transition">Pricing</a>
                <a href="#faq" class="hover:text-sky-400 transition">FAQ</a>
            </nav>

            <!-- Action CTAs -->
            <div class="hidden sm:flex items-center gap-3">
                <a
                    href="{{ url('/app') }}"
                    class="px-4 py-2 rounded-xl text-xs font-bold border border-slate-700 hover:border-slate-500 hover:bg-slate-800 text-slate-200 transition"
                >
                    Sign In to Portal
                </a>
                <a
                    href="#demo-request"
                    class="px-5 py-2.5 rounded-xl text-xs font-bold bg-sky-500 hover:bg-sky-400 text-slate-950 shadow-lg shadow-sky-500/25 transition"
                >
                    Request Demo
                </a>
            </div>

            <!-- Mobile Menu Toggle Button -->
            <button
                id="mobile-menu-btn"
                class="md:hidden p-2 text-slate-400 hover:text-white focus:outline-none"
                aria-label="Toggle navigation menu"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <!-- Mobile Dropdown Menu -->
        <div id="mobile-menu" class="hidden md:hidden px-4 pt-2 pb-6 border-b border-slate-800 bg-slate-900 space-y-3">
            <a href="#features" class="block py-2 text-sm text-slate-300 hover:text-white">Modules & Capabilities</a>
            <a href="#telegram-spotlight" class="block py-2 text-sm text-sky-400 font-semibold">Telegram Real-Time Alerts</a>
            <a href="#how-it-works" class="block py-2 text-sm text-slate-300 hover:text-white">How It Works</a>
            <a href="#product-preview" class="block py-2 text-sm text-slate-300 hover:text-white">Product Preview</a>
            <a href="#pricing" class="block py-2 text-sm text-slate-300 hover:text-white">Pricing & Plans</a>
            <a href="#faq" class="block py-2 text-sm text-slate-300 hover:text-white">FAQ</a>
            <div class="pt-4 flex flex-col gap-2">
                <a href="{{ url('/app') }}" class="w-full text-center py-2.5 rounded-xl text-xs font-bold border border-slate-700 text-slate-200">
                    Sign In to Portal
                </a>
                <a href="#demo-request" class="w-full text-center py-2.5 rounded-xl text-xs font-bold bg-sky-500 text-slate-950">
                    Request Live Demo
                </a>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="relative pt-16 pb-24 lg:pt-24 lg:pb-32 overflow-hidden">
        <!-- Ambient Glow Elements -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[400px] bg-sky-500/10 blur-[130px] pointer-events-none rounded-full"></div>
        <div class="absolute top-1/3 right-10 w-[400px] h-[300px] bg-indigo-500/10 blur-[120px] pointer-events-none rounded-full"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-6">
                    <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                    Modern Healthcare Operating Platform
                </div>

                <!-- Main Value Proposition -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.15]">
                    One Platform to Run Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-blue-400 to-indigo-400">Entire Hospital</span>
                </h1>

                <!-- Subheadline -->
                <p class="mt-6 text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl mx-auto">
                    Replace disjointed legacy systems with a unified, HIPAA-compliant hospital management suite. From OPD scheduling and clinical EHR to graphical bed allocation, multi-payer billing, and instant <b>Telegram emergency alerts</b>.
                </p>

                <!-- Primary & Secondary CTAs -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a
                        href="#demo-request"
                        class="w-full sm:w-auto px-8 py-4 rounded-xl text-sm font-bold bg-sky-500 hover:bg-sky-400 text-slate-950 shadow-xl shadow-sky-500/30 transition flex items-center justify-center gap-2"
                    >
                        Schedule a Demo &rarr;
                    </a>
                    <a
                        href="{{ url('/app') }}"
                        class="w-full sm:w-auto px-8 py-4 rounded-xl text-sm font-bold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4 text-sky-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                        </svg>
                        Explore Interactive Portal
                    </a>
                </div>

                <!-- Proof Badges -->
                <div class="mt-12 pt-8 border-t border-slate-800/80 grid grid-cols-2 md:grid-cols-4 gap-6 text-left">
                    <div class="p-3 bg-slate-800/30 rounded-xl border border-slate-800">
                        <div class="text-2xl font-extrabold text-white font-mono">100k+</div>
                        <div class="text-xs text-slate-400 mt-0.5">Patient records under 2s response</div>
                    </div>
                    <div class="p-3 bg-slate-800/30 rounded-xl border border-slate-800">
                        <div class="text-2xl font-extrabold text-sky-400 font-mono">99.99%</div>
                        <div class="text-xs text-slate-400 mt-0.5">High availability cloud uptime</div>
                    </div>
                    <div class="p-3 bg-slate-800/30 rounded-xl border border-slate-800">
                        <div class="text-2xl font-extrabold text-emerald-400 font-mono">15</div>
                        <div class="text-xs text-slate-400 mt-0.5">Fully integrated modules</div>
                    </div>
                    <div class="p-3 bg-slate-800/30 rounded-xl border border-slate-800">
                        <div class="text-2xl font-extrabold text-rose-400 font-mono">&lt; 3 sec</div>
                        <div class="text-xs text-slate-400 mt-0.5">Panic lab & code blue alerts</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: TELEGRAM REPORTING SPOTLIGHT (Differentiator) -->
    <section id="telegram-spotlight" class="py-20 bg-slate-950 border-y border-slate-800 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-12">
                <!-- Text Content -->
                <div class="lg:w-1/2 space-y-6">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                        <svg class="w-4 h-4 text-sky-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.52 2.77-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .27z"/>
                        </svg>
                        Exclusive HMS Differentiator
                    </div>

                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                        Real-Time Telegram Reports & Critical Alerts On Every Phone
                    </h2>

                    <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                        No more lost pagers, buried emails, or slow manual handoffs. Metro HMS connects directly to your hospital leadership and clinical channels via the <b>Telegram Bot API</b> with bank-grade role isolation.
                    </p>

                    <div class="space-y-4 pt-2">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-sky-950 text-sky-400 border border-sky-800 shrink-0">
                                📊
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Daily 8:00 AM Operational Digest</h3>
                                <p class="text-xs text-slate-400">Scheduled automated summaries of admissions, discharges, active bed occupancy, daily billing revenue, and pending lab tests sent to hospital executives.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-rose-950 text-rose-400 border border-rose-800 shrink-0">
                                🚨
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Unbatched Critical Clinical Push</h3>
                                <p class="text-xs text-slate-400">Immediate unbatched alerts for ESI-1 trauma resuscitations, lab panic values (e.g. Potassium > 6.5 mmol/L), and ICU bed shortage thresholds.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-indigo-950 text-indigo-400 border border-indigo-800 shrink-0">
                                💬
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Two-Way Bot Commands with RBAC Security</h3>
                                <p class="text-xs text-slate-400">Authorized staff can query the bot interactively: <code>/beds available</code>, <code>/revenue today</code>, <code>/stock low</code>, with strict role-based permission enforcement.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Switcher Buttons -->
                    <div class="pt-4 border-t border-slate-800">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-2">Test Message Simulator:</span>
                        <div class="flex flex-wrap gap-2" id="telegram-tab-buttons">
                            <button onclick="switchTelegramPreview('digest')" class="tg-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-600 text-white cursor-pointer" data-tab="digest">
                                📊 Daily Digest
                            </button>
                            <button onclick="switchTelegramPreview('esi1')" class="tg-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer" data-tab="esi1">
                                🚨 ESI-1 Resuscitation
                            </button>
                            <button onclick="switchTelegramPreview('lab')" class="tg-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer" data-tab="lab">
                                ⚠️ Critical Lab Panic
                            </button>
                            <button onclick="switchTelegramPreview('command')" class="tg-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer" data-tab="command">
                                💬 Bot Command: /beds
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Phone Mockup Container -->
                <div class="lg:w-1/2 flex justify-center">
                    <div class="w-[320px] sm:w-[350px] bg-slate-900 rounded-[42px] p-3.5 border-4 border-slate-700 shadow-2xl shadow-sky-500/10 relative">
                        <!-- Phone Notch / Island -->
                        <div class="w-32 h-4 bg-slate-800 rounded-full mx-auto mb-3"></div>

                        <!-- Telegram Header -->
                        <div class="bg-slate-800 rounded-2xl p-3 flex items-center justify-between border border-slate-700/60 mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-sky-500 flex items-center justify-center font-bold text-white text-xs">
                                    🤖
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-white">Metro HMS Bot</div>
                                    <div class="text-[10px] text-emerald-400">bot &bull; online</div>
                                </div>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400">Role: Doctor</span>
                        </div>

                        <!-- Telegram Chat Screen -->
                        <div class="bg-slate-950 rounded-2xl p-3.5 min-h-[380px] border border-slate-800 flex flex-col justify-between font-sans text-xs">
                            <div id="telegram-preview-content">
                                <!-- Message Bubble: Default Daily Digest -->
                                <div class="bg-slate-800/90 text-slate-100 p-3.5 rounded-2xl rounded-tl-none border border-slate-700 shadow space-y-2">
                                    <div class="text-sky-400 font-bold text-[11px] flex items-center justify-between">
                                        <span>📊 DAILY HOSPITAL OPERATIONAL DIGEST</span>
                                        <span class="text-[9px] text-slate-400">08:00 AM</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400"><i>Date: September 12, 2026</i></div>
                                    <div class="border-t border-slate-700/60 pt-1.5 space-y-1 text-[11px]">
                                        <p>🏥 <b>Bed Occupancy:</b> 85 / 100 (85%)</p>
                                        <p>&bull; Available ICU: <b>3 / 10 beds</b></p>
                                        <p>&bull; General Wards Available: <b>12 beds</b></p>
                                    </div>
                                    <div class="border-t border-slate-700/60 pt-1.5 space-y-1 text-[11px]">
                                        <p>👥 <b>Patient Flow:</b></p>
                                        <p>&bull; Admissions Today: <b>14</b> | Discharges: <b>9</b></p>
                                        <p>&bull; Active Emergency ER: <b>5</b></p>
                                    </div>
                                    <div class="border-t border-slate-700/60 pt-1.5 space-y-1 text-[11px]">
                                        <p>💰 <b>Revenue Invoiced:</b> ETB 1,650,000.00</p>
                                        <p>💊 <b>Low Stock Items:</b> 2 (Atropine, Normal Saline)</p>
                                    </div>
                                    <div class="text-[9px] text-slate-400 pt-1 text-right">Delivered via Metro HMS Bot ✓✓</div>
                                </div>
                            </div>

                            <!-- Input Bar Simulator -->
                            <div class="mt-3 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 flex items-center justify-between text-slate-400 text-[11px]">
                                <span>Type /help or /beds...</span>
                                <span class="text-sky-400 font-bold">➤</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: FEATURE HIGHLIGHTS (15 CORE MODULES) -->
    <section id="features" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-3">
                Comprehensive Hospital Ecosystem
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                Designed for Medical Excellence & Financial Control
            </h2>
            <p class="text-slate-400 text-sm sm:text-base mt-3">
                Every department, clinical workflow, and administrative function united in a frictionless, modern architecture.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- 1. Patient Management -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-bold text-lg">
                    🗂️
                </div>
                <h3 class="text-base font-bold text-white">Patient Registry & MPI</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Master Patient Index with biometric duplicate detection, unique national MRN generation, insurance policy eligibility checks, and complete patient demographics.
                </p>
            </div>

            <!-- 2. OPD & Appointments -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-400 flex items-center justify-center font-bold text-lg">
                    📅
                </div>
                <h3 class="text-base font-bold text-white">OPD & Queue Tokens</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Multi-specialist appointment booking calendars, dynamic department queuing display screens, doctor session limits, and patient SMS notifications.
                </p>
            </div>

            <!-- 3. IPD Bed Map -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-lg">
                    🛏️
                </div>
                <h3 class="text-base font-bold text-white">Inpatient & Bed Allocation Map</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Visual graphical ward map, real-time ICU and isolation unit availability, nurse station shift handovers, medication admin charts, and ALOS analytics.
                </p>
            </div>

            <!-- 4. Clinical EHR -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center font-bold text-lg">
                    🩺
                </div>
                <h3 class="text-base font-bold text-white">Doctor EHR & Clinical Notes</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Structured SOAP clinical notes, ICD-10 diagnostic indexing, longitudinal patient timeline, and e-prescriptions with automatic drug-drug interaction warnings.
                </p>
            </div>

            <!-- 5. Pharmacy & Dispensing -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold text-lg">
                    💊
                </div>
                <h3 class="text-base font-bold text-white">Pharmacy & FEFO Inventory</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    First-Expired-First-Out (FEFO) dispensing batch safety, low-stock threshold alerts, controlled substance registers, and seamless billing synchronization.
                </p>
            </div>

            <!-- 6. Diagnostic Laboratory (LIS) -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center font-bold text-lg">
                    🔬
                </div>
                <h3 class="text-base font-bold text-white">Laboratory (LIS) & Diagnostics</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Specimen accessioning with barcode tracking, automated analyzer integration, abnormal/panic value detection, and digital signature verification.
                </p>
            </div>

            <!-- 7. Radiology (RIS) & PACS -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center font-bold text-lg">
                    🩻
                </div>
                <h3 class="text-base font-bold text-white">Radiology (RIS) & PACS</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    DICOM modality worklist scheduling (X-Ray, CT, MRI, Ultrasound), structured radiologist reporting templates, and integrated PACS study links.
                </p>
            </div>

            <!-- 8. Billing & Insurance Claims -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-lg">
                    💵
                </div>
                <h3 class="text-base font-bold text-white">Billing, Insurance & Claims</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Automated service tariff pricing, split copay invoicing, multi-payer health insurance claims generation, shift cashier reconciliation, and zero revenue leakage.
                </p>
            </div>

            <!-- 9. Emergency & Ambulance CAD -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-400 flex items-center justify-center font-bold text-lg">
                    🚨
                </div>
                <h3 class="text-base font-bold text-white">Emergency Trauma & CAD</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    ESI-1 to ESI-5 clinical acuity triage scoring, live GPS ambulance dispatch CAD, emergency bed priority allocation, and trauma bay resuscitation workflows.
                </p>
            </div>

            <!-- 10. Materials & Asset Maintenance -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center font-bold text-lg">
                    📦
                </div>
                <h3 class="text-base font-bold text-white">Inventory & Equipment Maintenance</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Biomedical equipment preventive maintenance calendars, purchase order approval hierarchies, vendor catalogues, and consumable stock audits.
                </p>
            </div>

            <!-- 11. HR & Duty Rostering -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center font-bold text-lg">
                    👥
                </div>
                <h3 class="text-base font-bold text-white">HR & Staff Duty Rostering</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Conflict-free shift scheduling, automated double-booking detection, staff medical credential/license renewal expiry alerts, and biometric attendance.
                </p>
            </div>

            <!-- 12. Multi-Branch Federation & HIPAA -->
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold text-lg">
                    🛡️
                </div>
                <h3 class="text-base font-bold text-white">Enterprise Multi-Branch & Security</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Tenant-isolated branch federations, granular RBAC permissions, encrypted column-level PII, and immutable HIPAA audit logging for all records.
                </p>
            </div>
        </div>
    </section>

    <!-- SECTION 4: HOW IT WORKS (4-STEP VISUAL WORKFLOW) -->
    <section id="how-it-works" class="py-24 bg-slate-950/80 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-3">
                    Streamlined Hospital Journey
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    How Metro HMS Transforms Your Operations
                </h2>
                <p class="text-slate-400 text-sm sm:text-base mt-2">
                    A cohesive, continuous patient care flow with zero manual re-entry of data between departments.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Step 1 -->
                <div class="relative p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-full bg-sky-500 text-slate-950 font-black flex items-center justify-center text-sm">
                        01
                    </div>
                    <h3 class="text-base font-bold text-white">Intake & Triage</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Walk-in patients, ambulance CAD dispatches, and online bookings are verified in seconds with national MRN and immediate ESI acuity classification.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="relative p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-full bg-sky-500 text-slate-950 font-black flex items-center justify-center text-sm">
                        02
                    </div>
                    <h3 class="text-base font-bold text-white">Unified Clinical Care</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Physicians document SOAP notes, request diagnostic lab & radiology orders, prescribe medications, or allocate inpatient ICU beds from a single screen.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="relative p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-full bg-sky-500 text-slate-950 font-black flex items-center justify-center text-sm">
                        03
                    </div>
                    <h3 class="text-base font-bold text-white">Reconciled Billing</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Dispensed drugs, surgical procedures, and daily bed rates automatically post to invoices. Copays and insurance claims are calculated instantly.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="relative p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-full bg-sky-500 text-slate-950 font-black flex items-center justify-center text-sm">
                        04
                    </div>
                    <h3 class="text-base font-bold text-white">Executive Alerts</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Leadership tracks revenue and bed occupancy on real-time dashboards, while Telegram bots dispatch panic lab alerts and shift handovers to staff.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: PRODUCT PREVIEWS / INTERACTIVE TABS -->
    <section id="product-preview" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-3">
                Interface Walkthrough
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                Designed for Clinicians. Built for Administrators.
            </h2>
            <p class="text-slate-400 text-sm sm:text-base mt-2">
                Fast, responsive, keyboard-accessible interfaces that eliminate repetitive clicks and reduce physician burnout.
            </p>
        </div>

        <!-- Tab Controls -->
        <div class="flex justify-center mb-8">
            <div class="inline-flex p-1 bg-slate-800 rounded-xl border border-slate-700 max-w-full overflow-x-auto">
                <button onclick="switchProductPreview('ehr')" class="preview-tab px-4 py-2 text-xs font-bold rounded-lg bg-sky-500 text-slate-950 cursor-pointer" data-target="ehr">
                    Clinical EHR & Timeline
                </button>
                <button onclick="switchProductPreview('bedmap')" class="preview-tab px-4 py-2 text-xs font-bold rounded-lg text-slate-300 hover:text-white cursor-pointer" data-target="bedmap">
                    Graphical Ward & ICU Map
                </button>
                <button onclick="switchProductPreview('pharmacy')" class="preview-tab px-4 py-2 text-xs font-bold rounded-lg text-slate-300 hover:text-white cursor-pointer" data-target="pharmacy">
                    Pharmacy Dispensing & FEFO
                </button>
                <button onclick="switchProductPreview('bi')" class="preview-tab px-4 py-2 text-xs font-bold rounded-lg text-slate-300 hover:text-white cursor-pointer" data-target="bi">
                    Executive Analytics & BI
                </button>
            </div>
        </div>

        <!-- Preview Showcase Canvas -->
        <div id="product-preview-canvas" class="bg-slate-950 rounded-3xl p-6 md:p-8 border border-slate-800 shadow-2xl relative overflow-hidden min-h-[460px]">
            <!-- Content will be swapped via JS -->
            <div id="preview-content">
                <div class="space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-800 pb-4 gap-4">
                        <div>
                            <span class="text-xs font-mono text-sky-400 uppercase tracking-wider">Clinical Workspace &bull; Doctor EHR</span>
                            <h3 class="text-lg font-bold text-white mt-0.5">Patient Longitudinal Medical Record (MRN #MCH-10023)</h3>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">Allergy Checked</span>
                            <span class="px-2.5 py-1 rounded-md bg-sky-500/10 text-sky-400 border border-sky-500/20 font-medium">Insurance Active</span>
                        </div>
                    </div>

                    <!-- Realistic UI Wireframe / Mockup -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
                            <div class="font-bold text-slate-200 border-b border-slate-800 pb-2">Patient Vitals & History</div>
                            <div class="space-y-1.5 text-slate-400">
                                <div><b>BP:</b> 128/82 mmHg &bull; <b>HR:</b> 74 bpm</div>
                                <div><b>SpO2:</b> 98% &bull; <b>Temp:</b> 36.8 °C</div>
                                <div><b>Known Allergies:</b> Penicillin (Rash)</div>
                                <div><b>Primary Condition:</b> Type 2 Diabetes Mellitus</div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
                            <div class="font-bold text-slate-200 border-b border-slate-800 pb-2">Active E-Prescriptions</div>
                            <div class="space-y-2 text-slate-400">
                                <div class="p-2 rounded bg-slate-800/60 flex justify-between">
                                    <span>Metformin 500mg (BID)</span>
                                    <span class="text-emerald-400 font-medium">Dispensed</span>
                                </div>
                                <div class="p-2 rounded bg-slate-800/60 flex justify-between">
                                    <span>Lisinopril 10mg (OD)</span>
                                    <span class="text-sky-400 font-medium">Active</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
                            <div class="font-bold text-slate-200 border-b border-slate-800 pb-2">Diagnostic Labs & Imaging</div>
                            <div class="space-y-2 text-slate-400">
                                <div class="p-2 rounded bg-slate-800/60 flex justify-between">
                                    <span>HbA1c Glycated Hemoglobin</span>
                                    <span class="text-slate-200 font-mono">6.4% (Normal)</span>
                                </div>
                                <div class="p-2 rounded bg-slate-800/60 flex justify-between">
                                    <span>Chest X-Ray (AP View)</span>
                                    <span class="text-sky-400 font-semibold">Report Signed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 6: PRICING & DEPLOYMENT TIERS -->
    <section id="pricing" class="py-24 bg-slate-950 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-3">
                    Transparent Deployment Plans
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Predictable Pricing For Hospitals of Any Scale
                </h2>
                <p class="text-slate-400 text-sm sm:text-base mt-2">
                    Transparent annual or monthly licensing in Ethiopian Birr (ETB) with zero hidden fees. Dedicated on-site migration and 24/7 technical support included.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                <!-- Plan 1: Community Hospital -->
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <div class="text-sm font-bold text-slate-400 uppercase tracking-wider">Community Clinic</div>
                        <div class="mt-4 flex items-baseline gap-1.5">
                            <span class="text-3xl sm:text-4xl font-black text-white">ETB 65,000</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Perfect for regional medical clinics and community facilities up to 50 beds.</p>

                        <ul class="mt-6 space-y-3 text-xs text-slate-300">
                            <li class="flex items-center gap-2">✓ Up to 50 active inpatient beds</li>
                            <li class="flex items-center gap-2">✓ Full OPD booking & queue management</li>
                            <li class="flex items-center gap-2">✓ Patient registry & longitudinal EHR</li>
                            <li class="flex items-center gap-2">✓ Pharmacy dispensing & stock control</li>
                            <li class="flex items-center gap-2">✓ Automated billing & cash receipts</li>
                            <li class="flex items-center gap-2 text-slate-500">✗ Telegram bot alert engine</li>
                            <li class="flex items-center gap-2 text-slate-500">✗ Multi-branch federation</li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="#demo-request" class="block w-full text-center py-3 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition">
                            Select Community Plan
                        </a>
                    </div>
                </div>

                <!-- Plan 2: Regional Medical Center (FEATURED) -->
                <div class="p-8 rounded-3xl bg-gradient-to-b from-slate-900 via-slate-900 to-sky-950/40 border-2 border-sky-500 shadow-2xl shadow-sky-500/10 flex flex-col justify-between relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full text-[11px] font-bold bg-sky-500 text-slate-950">
                        MOST POPULAR
                    </div>
                    <div>
                        <div class="text-sm font-bold text-sky-400 uppercase tracking-wider">Regional Medical Center</div>
                        <div class="mt-4 flex items-baseline gap-1.5">
                            <span class="text-3xl sm:text-4xl font-black text-white">ETB 169,000</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Comprehensive operational suite for acute care hospitals up to 250 beds.</p>

                        <ul class="mt-6 space-y-3 text-xs text-slate-200">
                            <li class="flex items-center gap-2">✓ Up to 250 active inpatient & ICU beds</li>
                            <li class="flex items-center gap-2">✓ All 15 integrated hospital modules</li>
                            <li class="flex items-center gap-2 font-bold text-sky-300">✓ Telegram Reporting & Alert Engine</li>
                            <li class="flex items-center gap-2">✓ Emergency CAD & ESI trauma triage</li>
                            <li class="flex items-center gap-2">✓ Laboratory (LIS) & Radiology (RIS)</li>
                            <li class="flex items-center gap-2">✓ Multi-payer insurance claims engine</li>
                            <li class="flex items-center gap-2">✓ 99.99% Cloud SLA & automated backup</li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="#demo-request" class="block w-full text-center py-3.5 rounded-xl text-xs font-bold bg-sky-500 hover:bg-sky-400 text-slate-950 shadow-lg shadow-sky-500/25 transition">
                            Request Regional Demo &rarr;
                        </a>
                    </div>
                </div>

                <!-- Plan 3: Health System Enterprise -->
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <div class="text-sm font-bold text-slate-400 uppercase tracking-wider">Multi-Branch Enterprise</div>
                        <div class="mt-4 flex items-baseline gap-1.5">
                            <span class="text-3xl sm:text-4xl font-black text-white">Custom</span>
                            <span class="text-xs text-slate-400">ETB quote</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Federated multi-tenant architecture for hospital chains and university medical groups.</p>

                        <ul class="mt-6 space-y-3 text-xs text-slate-300">
                            <li class="flex items-center gap-2">✓ Unlimited beds, branches & facilities</li>
                            <li class="flex items-center gap-2">✓ Isolated multi-tenant branch federations</li>
                            <li class="flex items-center gap-2">✓ HL7 & FHIR lab/imaging integrations</li>
                            <li class="flex items-center gap-2">✓ Dedicated Telegram bot instances per facility</li>
                            <li class="flex items-center gap-2">✓ 24/7 Dedicated solutions engineering</li>
                            <li class="flex items-center gap-2">✓ Custom on-premise or sovereign cloud</li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="#demo-request" class="block w-full text-center py-3 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition">
                            Contact Enterprise Sales
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 7: TESTIMONIALS & SOCIAL PROOF -->
    <section class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-3">
                Proven Hospital Outcomes
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                Trusted By Hospital Leadership
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 space-y-4">
                <div class="text-amber-400 text-sm">★★★★★</div>
                <p class="text-xs text-slate-300 leading-relaxed italic">
                    "The Telegram reporting engine alone saved our cardiology team. When a critical troponin panic value triggers, the on-call doctor has it in Telegram within 2 seconds. It cuts out 15 minutes of phone tag."
                </p>
                <div class="pt-2 border-t border-slate-800 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center font-bold text-xs text-white">
                        DM
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">Dr. Marcus Vance, MD</div>
                        <div class="text-[10px] text-slate-400">Chief Medical Officer &bull; St. Jude General</div>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 space-y-4">
                <div class="text-amber-400 text-sm">★★★★★</div>
                <p class="text-xs text-slate-300 leading-relaxed italic">
                    "Our revenue cycle reconciliation went from 3 weeks at month-end to real-time daily closing. Invoicing errors dropped by 94% across our four hospital branches."
                </p>
                <div class="pt-2 border-t border-slate-800 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center font-bold text-xs text-white">
                        EK
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">Elena Rostova, CPA</div>
                        <div class="text-[10px] text-slate-400">VP of Finance &bull; Metro Health Alliance</div>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-2xl bg-slate-800/40 border border-slate-800 space-y-4">
                <div class="text-amber-400 text-sm">★★★★★</div>
                <p class="text-xs text-slate-300 leading-relaxed italic">
                    "Implementation took just 18 days. The staff grasped the UI on day one without extensive training seminars. The graphical bed allocation is intuitive and infallible."
                </p>
                <div class="pt-2 border-t border-slate-800 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center font-bold text-xs text-white">
                        TA
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">Tariq Al-Mansoor</div>
                        <div class="text-[10px] text-slate-400">Hospital Director &bull; Crescent Medical Center</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Badges Banner -->
        <div class="mt-16 pt-8 border-t border-slate-800/80 flex flex-wrap items-center justify-center gap-8 text-slate-400 text-xs font-mono uppercase tracking-widest">
            <span class="flex items-center gap-1.5"><span class="text-emerald-400">✔</span> HIPAA Safeguards</span>
            <span class="flex items-center gap-1.5"><span class="text-emerald-400">✔</span> HL7 & FHIR Standard</span>
            <span class="flex items-center gap-1.5"><span class="text-emerald-400">✔</span> ISO 27001 Security</span>
            <span class="flex items-center gap-1.5"><span class="text-emerald-400">✔</span> AES-256 Column Encryption</span>
            <span class="flex items-center gap-1.5"><span class="text-emerald-400">✔</span> TLS 1.3 Strict HTTPS</span>
        </div>
    </section>

    <!-- SECTION 8: FAQ ACCORDION -->
    <section id="faq" class="py-24 bg-slate-950 border-t border-slate-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-3">
                    Answers For Administrators
                </div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">
                    Frequently Asked Questions
                </h2>
            </div>

            <div class="space-y-4" id="faq-accordion">
                <!-- FAQ 1 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
                    <button onclick="toggleFaq(1)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between cursor-pointer">
                        <span>How quickly can our hospital transition from our existing legacy system?</span>
                        <span id="faq-icon-1" class="text-sky-400 font-mono text-base">+</span>
                    </button>
                    <div id="faq-answer-1" class="hidden px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3">
                        Most hospitals achieve full go-live in <b>2 to 4 weeks</b>. Our automated migration scripts import existing patient master records, active inventory, and billing price lists with zero downtime.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
                    <button onclick="toggleFaq(2)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between cursor-pointer">
                        <span>How is patient data secured to satisfy HIPAA and local privacy mandates?</span>
                        <span id="faq-icon-2" class="text-sky-400 font-mono text-base">+</span>
                    </button>
                    <div id="faq-answer-2" class="hidden px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3">
                        All Personally Identifiable Information (PII) is encrypted at the database column level using <b>AES-256</b>. All transit is enforced over TLS 1.3. Granular Role-Based Access Control (RBAC) ensures staff only view patient records relevant to their clinical role, and every access is logged in an immutable audit ledger.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
                    <button onclick="toggleFaq(3)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between cursor-pointer">
                        <span>Is Telegram reporting safe for hospital communications?</span>
                        <span id="faq-icon-3" class="text-sky-400 font-mono text-base">+</span>
                    </button>
                    <div id="faq-answer-3" class="hidden px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3">
                        Yes. Outbound Telegram channels are isolated per department (Executive, Doctors, Nursing, Pharmacy). Telegram messages sanitize sensitive identification details, utilizing internal clinical report IDs and reference numbers. Furthermore, inbound commands enforce strict RBAC (e.g., pharmacy chat is blocked from querying billing data).
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
                    <button onclick="toggleFaq(4)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between cursor-pointer">
                        <span>Can we manage multiple satellite clinics and hospital branches?</span>
                        <span id="faq-icon-4" class="text-sky-400 font-mono text-base">+</span>
                    </button>
                    <div id="faq-answer-4" class="hidden px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3">
                        Yes! Metro HMS includes built-in multi-tenancy and multi-branch federation. Hospital chains can isolate pharmacy stock, billing tariffs, and staff rosters per branch while executive leadership views consolidated health system analytics from one screen.
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
                    <button onclick="toggleFaq(5)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between cursor-pointer">
                        <span>Does Metro HMS require expensive local servers or hardware?</span>
                        <span id="faq-icon-5" class="text-sky-400 font-mono text-base">+</span>
                    </button>
                    <div id="faq-answer-5" class="hidden px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3">
                        No. The platform runs seamlessly in the cloud or in your private hospital data center. Staff access the system through any standard modern web browser (Chrome, Edge, Safari, Firefox) on workstations, tablets, or smartphones.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 9: DEMO REQUEST / LEAD CAPTURE FORM -->
    <section id="demo-request" class="py-24 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-tr from-slate-900 via-slate-900 to-sky-950/40 p-8 sm:p-12 rounded-3xl border border-sky-500/30 shadow-2xl relative">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-2">
                    Personalized Hospital Consultation
                </span>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">
                    Experience Metro HMS With Your Hospital's Workflow
                </h2>
                <p class="text-slate-400 text-xs sm:text-sm mt-2">
                    Fill out the form below. Our healthcare solutions specialists will prepare a customized walkthrough tailored to your hospital size and clinical departments.
                </p>
            </div>

            <!-- Confirmation Card (Hidden Initially) -->
            <div id="form-success-message" class="hidden p-8 rounded-2xl bg-emerald-950/80 border border-emerald-500/50 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center mx-auto text-2xl">
                    ✓
                </div>
                <h3 class="text-xl font-extrabold text-white">Demo Request Confirmed!</h3>
                <p class="text-xs sm:text-sm text-emerald-200 max-w-lg mx-auto">
                    Thank you! Your inquiry has been registered in our system. A senior hospital solution consultant will reach out within 24 hours to coordinate your interactive walkthrough.
                </p>
                <div class="text-xs font-mono text-emerald-300">Reference Lead ID: <span id="lead-ref-id" class="font-bold"></span></div>
                <div class="pt-2">
                    <a href="{{ url('/app') }}" class="inline-block px-5 py-2 rounded-xl text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 transition">
                        Explore Live Portal Preview &rarr;
                    </a>
                </div>
            </div>

            <!-- Lead Capture Form -->
            <form id="lead-capture-form" onsubmit="handleDemoSubmit(event)" class="space-y-4 text-xs">
                <div id="form-error-alert" class="hidden p-3 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-200 text-xs"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Your Full Name *</label>
                        <input
                            type="text"
                            name="name"
                            required
                            placeholder="e.g. Dr. Sarah Jenkins"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-sky-500"
                        >
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Work Email Address *</label>
                        <input
                            type="email"
                            name="email"
                            required
                            placeholder="sarah.jenkins@stmaryshospital.org"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-sky-500"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Hospital / Organization Name *</label>
                        <input
                            type="text"
                            name="hospital_name"
                            required
                            placeholder="e.g. St. Mary's Medical Center"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-sky-500"
                        >
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Phone Number (Direct / Mobile)</label>
                        <input
                            type="tel"
                            name="phone"
                            placeholder="+1 (555) 234-5678"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-sky-500"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Facility Type</label>
                        <select name="hospital_type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-slate-200 focus:outline-none focus:border-sky-500">
                            <option value="general_hospital">General Hospital</option>
                            <option value="specialty_hospital">Specialty / Surgical Center</option>
                            <option value="clinic_network">Clinic Network / Poly-clinic</option>
                            <option value="academic_center">Academic / Teaching Hospital</option>
                            <option value="emergency_trauma">Trauma & Emergency Center</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Bed Capacity</label>
                        <select name="hospital_size" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-slate-200 focus:outline-none focus:border-sky-500">
                            <option value="under_50">&lt; 50 Beds</option>
                            <option value="50_150" selected>50 &ndash; 150 Beds</option>
                            <option value="150_500">150 &ndash; 500 Beds</option>
                            <option value="500_plus">500+ Beds (Multi-Campus)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Number of Branches</label>
                        <input
                            type="number"
                            name="branches_count"
                            min="1"
                            max="100"
                            value="1"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:border-sky-500"
                        >
                    </div>
                </div>

                <!-- Modules Checkboxes -->
                <div>
                    <label class="block font-semibold text-slate-300 mb-2">Priority Modules of Interest</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                            <input type="checkbox" name="modules[]" value="telegram_alerts" checked class="rounded bg-slate-950 border-slate-800 text-sky-500">
                            <span>Telegram Bot Alerts</span>
                        </label>
                        <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                            <input type="checkbox" name="modules[]" value="inpatient_bed_map" checked class="rounded bg-slate-950 border-slate-800 text-sky-500">
                            <span>Inpatient Bed Map</span>
                        </label>
                        <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                            <input type="checkbox" name="modules[]" value="billing_insurance" checked class="rounded bg-slate-950 border-slate-800 text-sky-500">
                            <span>Billing & Insurance Claims</span>
                        </label>
                        <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                            <input type="checkbox" name="modules[]" value="clinical_ehr" class="rounded bg-slate-950 border-slate-800 text-sky-500">
                            <span>Doctor EHR & Notes</span>
                        </label>
                        <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                            <input type="checkbox" name="modules[]" value="emergency_cad" class="rounded bg-slate-950 border-slate-800 text-sky-500">
                            <span>Emergency Triage & CAD</span>
                        </label>
                        <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                            <input type="checkbox" name="modules[]" value="pharmacy_lis" class="rounded bg-slate-950 border-slate-800 text-sky-500">
                            <span>Pharmacy & Lab (LIS)</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Target Demo Date</label>
                        <input
                            type="date"
                            name="preferred_demo_date"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:border-sky-500"
                        >
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Additional Requirements / Notes</label>
                        <input
                            type="text"
                            name="notes"
                            placeholder="e.g. Currently migrating from legacy Cerner / paper charts"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-sky-500"
                        >
                    </div>
                </div>

                <div class="pt-4">
                    <button
                        type="submit"
                        id="submit-btn"
                        class="w-full py-4 rounded-xl text-sm font-bold bg-sky-500 hover:bg-sky-400 text-slate-950 shadow-xl shadow-sky-500/25 transition cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span>Schedule VIP Walkthrough</span>
                        <span>&rarr;</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-slate-950 border-t border-slate-800 pt-16 pb-12 text-slate-400 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-8 pb-12 border-b border-slate-800">
                <!-- Col 1: Brand Info -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-sky-500 flex items-center justify-center font-bold text-slate-950">
                            +
                        </div>
                        <span class="text-base font-extrabold text-white">Metro<span class="text-sky-400">HMS</span></span>
                    </div>
                    <p class="text-slate-400 max-w-sm leading-relaxed">
                        The next-generation Hospital Management System engineered to unite clinical workflows, reduce medical administrative friction, and deliver real-time operational alerts.
                    </p>
                    <div class="text-[11px] text-slate-500">
                        Inquiries: <a href="mailto:solutions@metrohms.com" class="text-sky-400 hover:underline">solutions@metrohms.com</a> &bull; +1 (800) 555-METRO
                    </div>
                </div>

                <!-- Col 2: Clinical Modules -->
                <div class="space-y-2">
                    <div class="font-bold text-white uppercase text-[10px] tracking-wider font-mono">Clinical Modules</div>
                    <ul class="space-y-1.5">
                        <li><a href="#features" class="hover:text-white">Patient Registry & MPI</a></li>
                        <li><a href="#features" class="hover:text-white">Doctor EHR & SOAP Notes</a></li>
                        <li><a href="#features" class="hover:text-white">Ward Map & ICU Census</a></li>
                        <li><a href="#features" class="hover:text-white">Laboratory (LIS) System</a></li>
                        <li><a href="#features" class="hover:text-white">Radiology (RIS) Viewer</a></li>
                    </ul>
                </div>

                <!-- Col 3: Operational Modules -->
                <div class="space-y-2">
                    <div class="font-bold text-white uppercase text-[10px] tracking-wider font-mono">Operations</div>
                    <ul class="space-y-1.5">
                        <li><a href="#telegram-spotlight" class="hover:text-white text-sky-300 font-semibold">Telegram Bot Engine</a></li>
                        <li><a href="#features" class="hover:text-white">Emergency Trauma & CAD</a></li>
                        <li><a href="#features" class="hover:text-white">Billing & Split Copays</a></li>
                        <li><a href="#features" class="hover:text-white">Pharmacy & FEFO Batches</a></li>
                        <li><a href="#features" class="hover:text-white">Staff Roster Scheduling</a></li>
                    </ul>
                </div>

                <!-- Col 4: Trust & Portal -->
                <div class="space-y-2">
                    <div class="font-bold text-white uppercase text-[10px] tracking-wider font-mono">Access</div>
                    <ul class="space-y-1.5">
                        <li><a href="{{ url('/app') }}" class="text-sky-400 font-semibold hover:underline">Launch Hospital Portal &rarr;</a></li>
                        <li><a href="#demo-request" class="hover:text-white">Schedule Custom Demo</a></li>
                        <li><a href="{{ url('/sitemap.xml') }}" class="hover:text-white">XML Sitemap</a></li>
                        <li><a href="{{ url('/robots.txt') }}" class="hover:text-white">Robots Index</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-slate-500">
                <div>
                    &copy; 2026 Metro Health Technologies Holding. All rights reserved. HIPAA, HL7 & ISO-27001 Compliant.
                </div>
                <div class="flex gap-6">
                    <a href="#demo-request" class="hover:text-slate-300">Privacy Policy</a>
                    <a href="#demo-request" class="hover:text-slate-300">Terms of Service</a>
                    <a href="#demo-request" class="hover:text-slate-300">Security Safeguards</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Client-Side Vanilla JS for Interactivity & SEO Performance -->
    <script>
        // Mobile Menu Toggle
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // FAQ Accordion Toggle
        function toggleFaq(index) {
            const answer = document.getElementById(`faq-answer-${index}`);
            const icon = document.getElementById(`faq-icon-${index}`);
            if (!answer) return;

            const isHidden = answer.classList.contains('hidden');
            if (isHidden) {
                answer.classList.remove('hidden');
                icon.textContent = '−';
            } else {
                answer.classList.add('hidden');
                icon.textContent = '+';
            }
        }

        // Telegram Preview Switcher
        const telegramPreviews = {
            digest: `
                <div class="bg-slate-800/90 text-slate-100 p-3.5 rounded-2xl rounded-tl-none border border-slate-700 shadow space-y-2">
                    <div class="text-sky-400 font-bold text-[11px] flex items-center justify-between">
                        <span>📊 DAILY HOSPITAL OPERATIONAL DIGEST</span>
                        <span class="text-[9px] text-slate-400">08:00 AM</span>
                    </div>
                    <div class="text-[10px] text-slate-400"><i>Date: September 12, 2026</i></div>
                    <div class="border-t border-slate-700/60 pt-1.5 space-y-1 text-[11px]">
                        <p>🏥 <b>Bed Occupancy:</b> 85 / 100 (85%)</p>
                        <p>&bull; Available ICU: <b>3 / 10 beds</b></p>
                        <p>&bull; General Wards Available: <b>12 beds</b></p>
                    </div>
                    <div class="border-t border-slate-700/60 pt-1.5 space-y-1 text-[11px]">
                        <p>👥 <b>Patient Flow:</b></p>
                        <p>&bull; Admissions Today: <b>14</b> | Discharges: <b>9</b></p>
                        <p>&bull; Active Emergency ER: <b>5</b></p>
                    </div>
                    <div class="border-t border-slate-700/60 pt-1.5 space-y-1 text-[11px]">
                        <p>💰 <b>Revenue Invoiced:</b> ETB 1,650,000.00</p>
                        <p>💊 <b>Low Stock Items:</b> 2 (Atropine, Normal Saline)</p>
                    </div>
                    <div class="text-[9px] text-slate-400 pt-1 text-right">Delivered via Metro HMS Bot ✓✓</div>
                </div>
            `,
            esi1: `
                <div class="bg-rose-950/80 text-rose-100 p-3.5 rounded-2xl rounded-tl-none border border-rose-700 shadow space-y-2">
                    <div class="text-rose-400 font-bold text-[11px] flex items-center justify-between">
                        <span>🚨 CODE BLUE / ESI-1 RESUSCITATION</span>
                        <span class="text-[9px] text-rose-300">14:22:04</span>
                    </div>
                    <div class="text-[10px] text-rose-300"><i>Immediate Trauma Call</i></div>
                    <div class="border-t border-rose-800 pt-1.5 space-y-1 text-[11px]">
                        <p>• <b>Case #:</b> <code>#EM-9041</code></p>
                        <p>• <b>Patient:</b> Unidentified Adult Male (Est. 35)</p>
                        <p>• <b>Location:</b> <b>Trauma Bay 1 / Resuscitation Room</b></p>
                        <p>• <b>Condition:</b> Cardiac arrest, CPR in progress</p>
                    </div>
                    <div class="p-1.5 bg-rose-900/60 rounded text-[10px] text-rose-200">
                        ⚠️ Trauma team, anesthesiology, and ICU registrar report immediately.
                    </div>
                    <div class="text-[9px] text-rose-300 text-right">Instant Alert Push ✓✓</div>
                </div>
            `,
            lab: `
                <div class="bg-amber-950/80 text-amber-100 p-3.5 rounded-2xl rounded-tl-none border border-amber-700 shadow space-y-2">
                    <div class="text-amber-400 font-bold text-[11px] flex items-center justify-between">
                        <span>⚠️ CRITICAL PANIC LAB VALUE DETECTED</span>
                        <span class="text-[9px] text-amber-300">11:15 AM</span>
                    </div>
                    <div class="text-[10px] text-amber-300"><i>Hospital Central Pathology</i></div>
                    <div class="border-t border-amber-800 pt-1.5 space-y-1 text-[11px]">
                        <p>• <b>Patient:</b> John Smith (MRN: #MCH-10023)</p>
                        <p>• <b>Test:</b> Serum Electrolytes Panel</p>
                        <p>• <b>Critical Result:</b> 🔴 <b>Potassium: 6.9 mmol/L</b> (Ref: 3.5 - 5.0)</p>
                        <p>• <b>Attending:</b> Dr. Robert Martinez (Cardiology)</p>
                    </div>
                    <div class="p-1.5 bg-amber-900/60 rounded text-[10px] text-amber-200">
                        Immediate physician acknowledgment and IV intervention required.
                    </div>
                    <div class="text-[9px] text-amber-300 text-right">Direct Alert Delivered ✓✓</div>
                </div>
            `,
            command: `
                <div class="space-y-2">
                    <div class="flex justify-end">
                        <div class="bg-sky-600 text-white px-3 py-1.5 rounded-xl rounded-tr-none text-[11px]">
                            /beds available
                        </div>
                    </div>
                    <div class="bg-slate-800 text-slate-100 p-3.5 rounded-2xl rounded-tl-none border border-slate-700 shadow space-y-1.5 text-[11px]">
                        <div class="text-sky-400 font-bold text-[11px]">🛏️ HOSPITAL BED OCCUPANCY STATUS</div>
                        <p>• <b>Total Beds:</b> 100</p>
                        <p>• <b>Occupied:</b> 85 | <b>Available:</b> 15</p>
                        <div class="border-t border-slate-700 pt-1 text-[10px] space-y-0.5">
                            <p>🏥 <b>ICU Units:</b> 3 available / 10 total</p>
                            <p>🏥 <b>General Wards:</b> 12 available / 90 total</p>
                        </div>
                        <div class="text-[9px] text-slate-400 pt-1 text-right">RBAC Authorized &bull; Role: Doctor ✓✓</div>
                    </div>
                </div>
            `
        };

        function switchTelegramPreview(tabKey) {
            const container = document.getElementById('telegram-preview-content');
            if (container && telegramPreviews[tabKey]) {
                container.innerHTML = telegramPreviews[tabKey];
            }

            // Update button styles
            const buttons = document.querySelectorAll('.tg-btn');
            buttons.forEach(btn => {
                if (btn.getAttribute('data-tab') === tabKey) {
                    btn.className = 'tg-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-600 text-white cursor-pointer';
                } else {
                    btn.className = 'tg-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer';
                }
            });
        }

        // Product Preview Tab Switcher
        const productPreviews = {
            ehr: `
                <div class="space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-800 pb-4 gap-4">
                        <div>
                            <span class="text-xs font-mono text-sky-400 uppercase tracking-wider">Clinical Workspace &bull; Doctor EHR</span>
                            <h3 class="text-lg font-bold text-white mt-0.5">Patient Longitudinal Medical Record (MRN #MCH-10023)</h3>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">Allergy Checked</span>
                            <span class="px-2.5 py-1 rounded-md bg-sky-500/10 text-sky-400 border border-sky-500/20 font-medium">Insurance Active</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
                            <div class="font-bold text-slate-200 border-b border-slate-800 pb-2">Patient Vitals & History</div>
                            <div class="space-y-1.5 text-slate-400">
                                <div><b>BP:</b> 128/82 mmHg &bull; <b>HR:</b> 74 bpm</div>
                                <div><b>SpO2:</b> 98% &bull; <b>Temp:</b> 36.8 °C</div>
                                <div><b>Known Allergies:</b> Penicillin (Rash)</div>
                                <div><b>Primary Condition:</b> Type 2 Diabetes Mellitus</div>
                            </div>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
                            <div class="font-bold text-slate-200 border-b border-slate-800 pb-2">Active E-Prescriptions</div>
                            <div class="space-y-2 text-slate-400">
                                <div class="p-2 rounded bg-slate-800/60 flex justify-between">
                                    <span>Metformin 500mg (BID)</span>
                                    <span class="text-emerald-400 font-medium">Dispensed</span>
                                </div>
                                <div class="p-2 rounded bg-slate-800/60 flex justify-between">
                                    <span>Lisinopril 10mg (OD)</span>
                                    <span class="text-sky-400 font-medium">Active</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
                            <div class="font-bold text-slate-200 border-b border-slate-800 pb-2">Diagnostic Labs & Imaging</div>
                            <div class="space-y-2 text-slate-400">
                                <div class="p-2 rounded bg-slate-800/60 flex justify-between">
                                    <span>HbA1c Glycated Hemoglobin</span>
                                    <span class="text-slate-200 font-mono">6.4% (Normal)</span>
                                </div>
                                <div class="p-2 rounded bg-slate-800/60 flex justify-between">
                                    <span>Chest X-Ray (AP View)</span>
                                    <span class="text-sky-400 font-semibold">Report Signed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            bedmap: `
                <div class="space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-800 pb-4 gap-4">
                        <div>
                            <span class="text-xs font-mono text-emerald-400 uppercase tracking-wider">Inpatient (IPD) & ICU Ward Management</span>
                            <h3 class="text-lg font-bold text-white mt-0.5">Live Graphical Bed Allocation & Occupancy Map</h3>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Available (15)</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Occupied (85)</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Cleaning (3)</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-rose-950/40 border border-rose-800 text-center">
                            <div class="font-mono font-bold text-rose-300">ICU-01</div>
                            <div class="text-[10px] text-slate-400 mt-1">Occupied</div>
                            <div class="text-[9px] text-rose-400 mt-0.5">High Acuity</div>
                        </div>
                        <div class="p-3 rounded-xl bg-rose-950/40 border border-rose-800 text-center">
                            <div class="font-mono font-bold text-rose-300">ICU-02</div>
                            <div class="text-[10px] text-slate-400 mt-1">Occupied</div>
                            <div class="text-[9px] text-rose-400 mt-0.5">Ventilator</div>
                        </div>
                        <div class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-800 text-center">
                            <div class="font-mono font-bold text-emerald-300">ICU-03</div>
                            <div class="text-[10px] text-emerald-400 mt-1">Available</div>
                            <div class="text-[9px] text-slate-400 mt-0.5">Ready</div>
                        </div>
                        <div class="p-3 rounded-xl bg-rose-950/40 border border-rose-800 text-center">
                            <div class="font-mono font-bold text-rose-300">GEN-101</div>
                            <div class="text-[10px] text-slate-400 mt-1">Occupied</div>
                            <div class="text-[9px] text-slate-400 mt-0.5">Admitted</div>
                        </div>
                        <div class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-800 text-center">
                            <div class="font-mono font-bold text-emerald-300">GEN-102</div>
                            <div class="text-[10px] text-emerald-400 mt-1">Available</div>
                            <div class="text-[9px] text-slate-400 mt-0.5">Ready</div>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-950/40 border border-amber-800 text-center">
                            <div class="font-mono font-bold text-amber-300">GEN-103</div>
                            <div class="text-[10px] text-amber-400 mt-1">Cleaning</div>
                            <div class="text-[9px] text-slate-400 mt-0.5">Turnaround</div>
                        </div>
                    </div>
                </div>
            `,
            pharmacy: `
                <div class="space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-800 pb-4 gap-4">
                        <div>
                            <span class="text-xs font-mono text-amber-400 uppercase tracking-wider">Hospital Pharmacy & Formulary</span>
                            <h3 class="text-lg font-bold text-white mt-0.5">FEFO Batch Dispensing & Real-Time Stock Tracking</h3>
                        </div>
                        <span class="text-xs text-amber-300 bg-amber-500/10 px-3 py-1 rounded-full border border-amber-500/20">Automated Reorder Enabled</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                            <div class="flex justify-between font-bold text-slate-200">
                                <span>Ceftriaxone 1g Injection</span>
                                <span class="text-emerald-400">In Stock: 140 vials</span>
                            </div>
                            <div class="text-[11px] text-slate-400">Batch #B-9021 &bull; Expiry: Nov 2027 (FEFO Rank #1)</div>
                            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full" style="width: 75%"></div>
                            </div>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                            <div class="flex justify-between font-bold text-slate-200">
                                <span>Atropine Sulfate 1mg/mL</span>
                                <span class="text-rose-400 font-bold">Low Stock: 4 ampoules</span>
                            </div>
                            <div class="text-[11px] text-slate-400">Reorder Level: 20 ampoules &bull; Auto PO Drafted</div>
                            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-rose-500 h-full" style="width: 20%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            bi: `
                <div class="space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-800 pb-4 gap-4">
                        <div>
                            <span class="text-xs font-mono text-indigo-400 uppercase tracking-wider">Executive BI Intelligence</span>
                            <h3 class="text-lg font-bold text-white mt-0.5">Hospital Group Performance & Revenue Cycle</h3>
                        </div>
                        <span class="text-xs text-indigo-300 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">Pre-Aggregated &bull; Sub-2s Load</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
                            <div class="text-slate-400 text-[11px]">Monthly Gross Billings</div>
                            <div class="text-2xl font-bold text-white mt-1">ETB 48,290,000</div>
                            <div class="text-emerald-400 text-[10px] mt-1">↑ 12.4% vs previous month</div>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
                            <div class="text-slate-400 text-[11px]">Average Length of Stay (ALOS)</div>
                            <div class="text-2xl font-bold text-white mt-1">3.4 Days</div>
                            <div class="text-sky-400 text-[10px] mt-1">Optimal target threshold met</div>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
                            <div class="text-slate-400 text-[11px]">Claims Settlement Rate</div>
                            <div class="text-2xl font-bold text-white mt-1">98.2%</div>
                            <div class="text-emerald-400 text-[10px] mt-1">0.4% denial appeal rate</div>
                        </div>
                    </div>
                </div>
            `
        };

        function switchProductPreview(targetKey) {
            const container = document.getElementById('preview-content');
            if (container && productPreviews[targetKey]) {
                container.innerHTML = productPreviews[targetKey];
            }

            const tabs = document.querySelectorAll('.preview-tab');
            tabs.forEach(tab => {
                if (tab.getAttribute('data-target') === targetKey) {
                    tab.className = 'preview-tab px-4 py-2 text-xs font-bold rounded-lg bg-sky-500 text-slate-950 cursor-pointer';
                } else {
                    tab.className = 'preview-tab px-4 py-2 text-xs font-bold rounded-lg text-slate-300 hover:text-white cursor-pointer';
                }
            });
        }

        // Demo Request Form Submission Handler
        async function handleDemoSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const submitBtn = document.getElementById('submit-btn');
            const errorAlert = document.getElementById('form-error-alert');
            const successMsg = document.getElementById('form-success-message');
            const leadRefId = document.getElementById('lead-ref-id');

            errorAlert.classList.add('hidden');
            errorAlert.textContent = '';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Processing Submission...</span>';

            const formData = new FormData(form);
            const modules = formData.getAll('modules[]');

            const payload = {
                name: formData.get('name'),
                email: formData.get('email'),
                phone: formData.get('phone') || null,
                hospital_name: formData.get('hospital_name'),
                hospital_type: formData.get('hospital_type'),
                hospital_size: formData.get('hospital_size'),
                branches_count: parseInt(formData.get('branches_count') || '1', 10),
                modules_of_interest: modules,
                preferred_demo_date: formData.get('preferred_demo_date') || null,
                notes: formData.get('notes') || null,
                source: 'website_landing_page'
            };

            try {
                const response = await fetch('/api/v1/leads', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    form.classList.add('hidden');
                    successMsg.classList.remove('hidden');
                    if (leadRefId) {
                        leadRefId.textContent = data.data.lead_id;
                    }
                } else {
                    const message = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to submit request.');
                    errorAlert.textContent = message;
                    errorAlert.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Schedule VIP Walkthrough</span> <span>&rarr;</span>';
                }
            } catch (err) {
                errorAlert.textContent = 'Network error occurred. Please try again or email solutions@metrohms.com directly.';
                errorAlert.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Schedule VIP Walkthrough</span> <span>&rarr;</span>';
            }
        }
    </script>
</body>
</html>
