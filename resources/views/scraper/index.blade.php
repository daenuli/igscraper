<!DOCTYPE html>
<html lang="en" class="h-full bg-white text-slate-900 antialiased">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>InstaScrape — Instagram Data, Made Simple</title>
  <meta name="description" content="Extract and explore Instagram profile and post data with a modern, free developer tool powered by Apify.">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
            mono: ['JetBrains Mono', 'monospace'],
          },
          colors: {
            brand: {
              DEFAULT: '#E1306C',
              hover: '#C13584',
              secondary: '#833AB4',
              surface: '#F8FAFC',
              text: '#0F172A',
              muted: '#64748B',
              border: '#E2E8F0',
            }
          },
          animation: {
            'indeterminate': 'indeterminate 1.5s infinite linear',
            'pulse-subtle': 'pulse 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
          },
          keyframes: {
            indeterminate: {
              '0%': { transform: 'translateX(-100%)' },
              '100%': { transform: 'translateX(250%)' }
            }
          }
        }
      }
    }
  </script>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    /* Custom scrollbar and subtle focus rings */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #F1F5F9;
    }
    ::-webkit-scrollbar-thumb {
      background: #CBD5E1;
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #94A3B8;
    }
    .custom-gradient-text {
      background: linear-gradient(135deg, #0F172A 60%, #E1306C 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .ig-pill-gradient {
      background: linear-gradient(135deg, rgba(225, 48, 108, 0.1) 0%, rgba(131, 58, 180, 0.08) 100%);
    }
  </style>
</head>
<body class="min-h-full flex flex-col bg-white selection:bg-[#E1306C]/10 selection:text-[#E1306C]">

  <!-- Top Announcement / State Switcher Toolbar (For previewing all states easily) -->
  <aside class="bg-slate-900 text-slate-300 text-xs py-2 px-4 border-b border-slate-800">
    <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center space-x-2">
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-pink-500/20 text-pink-300 border border-pink-500/30">
          PROTOTYPE
        </span>
        <span class="text-slate-400 hidden sm:inline">UI State Switcher:</span>
      </div>
      
      <!-- Quick State Selector dropdown for evaluating all 11 brief requirements -->
      <div class="flex items-center gap-1.5 overflow-x-auto py-0.5 text-xs">
        <span class="text-slate-400 text-[11px] font-mono hidden md:inline">Quick Jump:</span>
        <button onclick="setPrototypeState('idle')" class="state-btn px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 transition">1. Idle</button>
        <button onclick="setPrototypeState('loading')" class="state-btn px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 transition">5. Scraping In Progress</button>
        <button onclick="setPrototypeState('profile_success')" class="state-btn px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 transition">6. Profile Result</button>
        <button onclick="setPrototypeState('posts_success')" class="state-btn px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 transition">7. Posts Result</button>
        <button onclick="setPrototypeState('hashtag_success')" class="state-btn px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 transition">8. Hashtag Result</button>
        <button onclick="setPrototypeState('empty')" class="state-btn px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 transition">9. Empty</button>
        <button onclick="setPrototypeState('api_error')" class="state-btn px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 transition">10. API Error</button>
        <button onclick="setPrototypeState('val_error')" class="state-btn px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 transition">11. Validation Error</button>
      </div>
    </div>
  </aside>

  <!-- Section A: Navigation Bar -->
  <header class="sticky top-0 z-40 w-full bg-white/90 backdrop-blur-md border-b border-slate-200/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
      <!-- Left: Logo & Brand -->
      <a href="#" class="flex items-center gap-2.5 group transition">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#f09433] via-[#E1306C] to-[#833AB4] flex items-center justify-center shadow-sm shadow-pink-500/20 group-hover:scale-105 transition-transform duration-200">
          <i data-lucide="instagram" class="w-4 h-4 text-white stroke-[2.2]"></i>
        </div>
        <div class="flex items-baseline gap-1.5">
          <span class="font-bold text-lg tracking-tight text-slate-900 group-hover:text-slate-700 transition">InstaScrape</span>
          <span class="text-[10px] font-mono uppercase tracking-wider text-slate-400 font-medium">Demo</span>
        </div>
      </a>

      <!-- Right: Links & Powered by Apify -->
      <nav class="flex items-center gap-3 sm:gap-6 text-sm">
        <a href="#docs-modal" onclick="openModal('docs'); return false;" class="text-slate-600 hover:text-slate-900 font-medium transition flex items-center gap-1.5">
          <i data-lucide="book-open" class="w-3.5 h-3.5 text-slate-400"></i>
          <span class="hidden sm:inline">Docs</span>
        </a>
        <a href="#about-modal" onclick="openModal('about'); return false;" class="text-slate-600 hover:text-slate-900 font-medium transition flex items-center gap-1.5">
          <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400"></i>
          <span class="hidden sm:inline">About</span>
        </a>
        
        <div class="h-4 w-px bg-slate-200"></div>

        <a href="https://apify.com/apify/instagram-scraper" target="_blank" rel="noopener noreferrer" 
           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 hover:bg-slate-200/80 hover:text-slate-900 border border-slate-200 transition">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>Powered by <strong>Apify</strong></span>
          <i data-lucide="external-link" class="w-3 h-3 text-slate-400 ml-0.5"></i>
        </a>
      </nav>
    </div>
  </header>

  <main class="flex-grow">
    <!-- Section B: Hero Section -->
    <section class="pt-12 pb-8 sm:pt-16 sm:pb-12 text-center px-4">
      <div class="max-w-3xl mx-auto">
        <!-- Small Badge -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-pink-50 text-[#E1306C] border border-pink-100 mb-5 shadow-xs">
          <i data-lucide="sparkles" class="w-3.5 h-3.5 text-[#E1306C]"></i>
          <span>FREE DEVELOPER TOOL</span>
        </div>

        <!-- Main Heading -->
        <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 mb-4 sm:mb-5">
          Instagram Data, <span class="custom-gradient-text">Made Simple.</span>
        </h1>

        <!-- Subtitle -->
        <p class="text-base sm:text-lg text-slate-600 max-w-xl mx-auto leading-relaxed">
          Extract and explore Instagram profile and post data with a simple, free developer tool.
        </p>
      </div>
    </section>

    <!-- Section C: Scraper Form -->
    <section class="px-4 pb-10">
      <div class="max-w-[680px] mx-auto bg-white rounded-2xl border border-slate-200 shadow-xl shadow-slate-900/5 p-4 sm:p-7 relative transition-all">
        
        <!-- Scraper Type Selector (3 Tabs) -->
        <div class="flex p-1 bg-slate-100 rounded-xl mb-6 text-sm font-medium border border-slate-200/70">
          <button type="button" id="tab-profile" onclick="switchTab('profile')" 
                  class="flex-1 py-2 px-3 rounded-lg flex items-center justify-center gap-2 transition-all duration-150 bg-white text-slate-900 shadow-xs font-semibold">
            <i data-lucide="user" class="w-4 h-4 text-[#E1306C]"></i>
            <span>Profile</span>
          </button>
          <button type="button" id="tab-posts" onclick="switchTab('posts')" 
                  class="flex-1 py-2 px-3 rounded-lg flex items-center justify-center gap-2 transition-all duration-150 text-slate-600 hover:text-slate-900">
            <i data-lucide="grid" class="w-4 h-4 text-slate-400"></i>
            <span>Posts</span>
          </button>
          <button type="button" id="tab-hashtags" onclick="switchTab('hashtags')" 
                  class="flex-1 py-2 px-3 rounded-lg flex items-center justify-center gap-2 transition-all duration-150 text-slate-600 hover:text-slate-900">
            <i data-lucide="hash" class="w-4 h-4 text-slate-400"></i>
            <span>Hashtags</span>
          </button>
        </div>

        <!-- Scraping Input Form -->
        <form id="scraper-form" onsubmit="handleScrapeSubmit(event)" class="space-y-4">
          <!-- Input + Limit Controls -->
          <div class="space-y-2">
            <label for="scraper-input" class="block text-xs font-semibold text-slate-700 uppercase tracking-wide flex justify-between items-center">
              <span id="input-label-text">Target Profile or Username</span>
              <span class="text-slate-400 font-normal lowercase text-[11px]" id="input-hint">e.g. @laravelphp</span>
            </label>
            
            <div class="flex flex-col sm:flex-row gap-2.5">
              <!-- Text Input -->
              <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400" id="input-icon-container">
                  <i data-lucide="at-sign" class="w-4 h-4" id="input-icon"></i>
                </div>
                <input 
                  type="text" 
                  id="scraper-input"
                  name="target"
                  value="laravelphp"
                  placeholder="Enter Instagram username or profile URL"
                  class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#E1306C]/20 focus:border-[#E1306C] focus:bg-white transition"
                  autocomplete="off"
                  spellcheck="false"
                >
              </div>

              <!-- Results Limit Dropdown -->
              <div class="sm:w-32 flex-shrink-0">
                <div class="relative">
                  <select 
                    id="scraper-limit"
                    class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 pr-8 text-sm text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-[#E1306C]/20 focus:border-[#E1306C] transition">
                    <option value="5">Limit: 5</option>
                    <option value="10" selected>Limit: 10</option>
                    <option value="20">Limit: 20</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Validation Error Message Area (State 11) -->
            <div id="validation-error-msg" class="hidden text-xs text-red-600 font-medium flex items-center gap-1.5 pt-1">
              <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-red-500 flex-shrink-0"></i>
              <span id="validation-error-text">Please provide a valid username or URL to continue.</span>
            </div>
          </div>

          <!-- Quick Presets -->
          <div class="flex items-center flex-wrap gap-1.5 text-xs text-slate-500 pt-1">
            <span class="text-slate-400 text-[11px]">Quick presets:</span>
            <button type="button" onclick="fillPreset('laravelphp')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono text-[11px] transition">@laravelphp</button>
            <button type="button" onclick="fillPreset('taylorotwell')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono text-[11px] transition">@taylorotwell</button>
            <button type="button" onclick="fillPreset('#laravel')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono text-[11px] transition">#laravel</button>
          </div>

          <!-- Submit Button -->
          <div class="pt-2">
            <button 
              type="submit" 
              id="submit-button"
              class="w-full py-3 px-5 rounded-xl font-semibold text-white bg-[#E1306C] hover:bg-[#C13584] active:scale-[0.99] transition shadow-md shadow-pink-500/20 flex items-center justify-center gap-2 group cursor-pointer">
              <i data-lucide="search" class="w-4 h-4 transition-transform group-hover:scale-110" id="btn-icon"></i>
              <span id="btn-text">Scrape Instagram</span>
            </button>
          </div>

          <!-- Helper Text -->
          <div class="text-center pt-1">
            <p class="text-xs text-slate-400 flex items-center justify-center gap-1.5">
              <i data-lucide="shield-check" class="w-3.5 h-3.5 text-slate-400"></i>
              <span>Only publicly accessible Instagram information is supported.</span>
            </p>
          </div>
        </form>

        <!-- Section D: Scraping Status Component (All 6 States) -->
        <div id="status-card-container" class="mt-6 pt-5 border-t border-slate-100">
          
          <!-- State 1: Idle -->
          <div id="status-state-idle" class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600">
            <div class="flex items-center gap-2.5">
              <span class="relative flex h-2.5 w-2.5">
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-slate-400"></span>
              </span>
              <span class="font-medium text-slate-700">Ready to scrape</span>
            </div>
            <span class="font-mono text-[11px] text-slate-400">Status: IDLE</span>
          </div>

          <!-- State 2: Queued -->
          <div id="status-state-queued" class="hidden flex items-center justify-between p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/80 text-xs text-amber-900">
            <div class="flex items-center gap-2.5">
              <i data-lucide="clock" class="w-4 h-4 text-amber-600 animate-spin"></i>
              <span class="font-medium">Your request has been queued on Apify worker pool...</span>
            </div>
            <span class="font-mono text-[11px] font-semibold text-amber-700">QUEUED</span>
          </div>

          <!-- State 3: Running -->
          <div id="status-state-running" class="hidden flex flex-col gap-2 p-3.5 rounded-xl bg-sky-50/70 border border-sky-200/80 text-xs text-sky-900">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <i data-lucide="loader-2" class="w-4 h-4 text-sky-600 animate-spin"></i>
                <span class="font-medium">Fetching Instagram data from public endpoints...</span>
              </div>
              <span class="font-mono text-[11px] font-semibold text-sky-700">RUNNING</span>
            </div>
            <!-- Indeterminate Progress bar -->
            <div class="w-full bg-sky-200/60 h-1.5 rounded-full overflow-hidden relative">
              <div class="h-full bg-sky-600 w-1/3 rounded-full animate-indeterminate"></div>
            </div>
          </div>

          <!-- State 4: Processing -->
          <div id="status-state-processing" class="hidden flex flex-col gap-2 p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200/80 text-xs text-indigo-900">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <i data-lucide="cpu" class="w-4 h-4 text-indigo-600 animate-pulse"></i>
                <span class="font-medium">Processing results and transforming JSON schema...</span>
              </div>
              <span class="font-mono text-[11px] font-semibold text-indigo-700">PROCESSING</span>
            </div>
            <div class="w-full bg-indigo-200/60 h-1.5 rounded-full overflow-hidden relative">
              <div class="h-full bg-indigo-600 w-2/5 rounded-full animate-indeterminate"></div>
            </div>
          </div>

          <!-- State 5: Completed -->
          <div id="status-state-completed" class="hidden flex items-center justify-between p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80 text-xs text-emerald-900">
            <div class="flex items-center gap-2.5">
              <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                <i data-lucide="check" class="w-3.5 h-3.5 stroke-[3]"></i>
              </div>
              <span class="font-medium text-emerald-950">Scraping completed successfully. Results ready below.</span>
            </div>
            <span class="font-mono text-[11px] font-semibold text-emerald-700">COMPLETED</span>
          </div>

          <!-- State 6: Failed (State 10: API error state) -->
          <div id="status-state-failed" class="hidden p-3.5 rounded-xl bg-red-50/80 border border-red-200 text-xs text-red-900">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-start gap-2.5">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-red-600 flex-shrink-0 mt-0.5"></i>
                <div>
                  <p class="font-semibold text-red-950">Scraping Failed</p>
                  <p class="text-red-700 text-[11px] mt-0.5" id="failed-error-details">
                    The requested Instagram account is either private, rate-limited by Instagram challenge wall, or does not exist.
                  </p>
                </div>
              </div>
              <button onclick="setPrototypeState('idle')" class="text-[11px] text-red-700 underline font-medium hover:text-red-900 flex-shrink-0">Dismiss</button>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- SECTION E & F: Results Section with Export Actions -->
    <section id="results-wrapper" class="max-w-6xl mx-auto px-4 sm:px-6 pb-20 transition-all duration-300">
      
      <!-- Section Header with Badges & Export Actions -->
      <div id="results-header" class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-200 mb-8">
        <div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
              Live Preview
            </span>
            <span class="text-xs text-slate-400 font-mono" id="results-timestamp">Scraped 2 minutes ago</span>
          </div>
          <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1" id="results-title">Scraped Results: @laravelphp</h2>
        </div>

        <!-- Section F: Export Actions -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Download JSON -->
          <button type="button" onclick="exportData('json')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-xs transition hover:border-slate-300 active:bg-slate-100">
            <i data-lucide="file-json" class="w-3.5 h-3.5 text-slate-500"></i>
            <span>Download JSON</span>
          </button>

          <!-- Download CSV -->
          <button type="button" onclick="exportData('csv')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-xs transition hover:border-slate-300 active:bg-slate-100">
            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-slate-500"></i>
            <span>Download CSV</span>
          </button>

          <!-- Copy JSON -->
          <button type="button" onclick="copyRawJson()" id="copy-json-btn" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-xs transition hover:border-slate-300 active:bg-slate-100">
            <i data-lucide="copy" class="w-3.5 h-3.5 text-slate-500" id="copy-icon"></i>
            <span id="copy-btn-text">Copy JSON</span>
          </button>

          <!-- Collapsible Raw JSON Toggle -->
          <button type="button" onclick="toggleRawJson()" id="toggle-json-btn" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-slate-900 hover:bg-slate-800 text-white shadow-xs transition">
            <i data-lucide="code-2" class="w-3.5 h-3.5 text-slate-300"></i>
            <span id="raw-json-label">View Raw JSON</span>
          </button>
        </div>
      </div>

      <!-- Collapsible Raw JSON Section -->
      <div id="raw-json-drawer" class="hidden mb-8 transition-all">
        <div class="bg-slate-950 rounded-xl border border-slate-800 overflow-hidden shadow-lg">
          <div class="flex items-center justify-between px-4 py-2.5 bg-slate-900/90 border-b border-slate-800 text-xs text-slate-400 font-mono">
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-red-500/80 inline-block"></span>
              <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
              <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
              <span class="ml-2 text-slate-300 font-medium">response.json</span>
            </div>
            <span class="text-[11px] text-slate-500">Apify Instagram Actor Schema v1.4</span>
          </div>
          <pre class="p-4 text-xs font-mono text-emerald-400 overflow-x-auto max-h-96 leading-relaxed bg-slate-950" id="raw-json-content">
          </pre>
        </div>
      </div>

      <!-- Result View 1: Profile Results Layout -->
      <div id="results-profile-view" class="space-y-6">
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
            <!-- Profile Avatar -->
            <div class="relative flex-shrink-0">
              <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full p-1 bg-gradient-to-tr from-[#f09433] via-[#E1306C] to-[#833AB4]">
                <img 
                  id="profile-avatar"
                  src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=250&auto=format&fit=crop&q=80" 
                  alt="Avatar" 
                  class="w-full h-full object-cover rounded-full border-2 border-white bg-slate-100"
                >
              </div>
              <span class="absolute bottom-1 right-1 bg-emerald-500 border-2 border-white w-4 h-4 rounded-full" title="Active Account"></span>
            </div>

            <!-- Profile Info -->
            <div class="flex-1 space-y-2">
              <div class="flex flex-wrap items-center gap-2.5">
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900" id="profile-username">laravelphp</h3>
                
                <!-- Verified indicator -->
                <span id="profile-verified-badge" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200" title="Verified Badge">
                  <i data-lucide="badge-check" class="w-3.5 h-3.5 text-sky-500 fill-sky-100"></i>
                  <span>Verified</span>
                </span>
                
                <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-mono" id="profile-id">ID: 839210491</span>
              </div>

              <p class="font-medium text-slate-700 text-sm" id="profile-fullname">Laravel — The PHP Framework for Web Artisans</p>
              
              <p class="text-sm text-slate-600 max-w-2xl leading-normal" id="profile-bio">
                Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable, truly fulfilling experience.
              </p>

              <!-- External URL -->
              <div class="pt-1">
                <a 
                  id="profile-external-url"
                  href="https://laravel.com" 
                  target="_blank" 
                  rel="noopener" 
                  class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#E1306C] hover:underline">
                  <i data-lucide="link-2" class="w-3.5 h-3.5"></i>
                  <span id="profile-external-text">https://laravel.com</span>
                </a>
              </div>
            </div>

            <!-- Profile Link CTA -->
            <div class="sm:self-start">
              <a 
                id="profile-instagram-link"
                href="https://instagram.com/laravelphp" 
                target="_blank" 
                rel="noopener"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                <span>View on Instagram</span>
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
              </a>
            </div>
          </div>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <span class="text-xs text-slate-500 font-medium">Followers</span>
            <div class="text-2xl font-bold text-slate-900 mt-1" id="profile-followers">124.5K</div>
            <div class="text-[11px] text-emerald-600 font-medium flex items-center gap-1 mt-1">
              <i data-lucide="trending-up" class="w-3 h-3"></i>
              <span>High Authority</span>
            </div>
          </div>

          <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <span class="text-xs text-slate-500 font-medium">Following</span>
            <div class="text-2xl font-bold text-slate-900 mt-1" id="profile-following">18</div>
            <div class="text-[11px] text-slate-400 mt-1">Curated follows</div>
          </div>

          <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <span class="text-xs text-slate-500 font-medium">Total Posts</span>
            <div class="text-2xl font-bold text-slate-900 mt-1" id="profile-total-posts">348</div>
            <div class="text-[11px] text-slate-400 mt-1">Publicly indexed</div>
          </div>

          <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <span class="text-xs text-slate-500 font-medium">Account Type</span>
            <div class="text-2xl font-bold text-slate-900 mt-1" id="profile-category">Business / Tech</div>
            <div class="text-[11px] text-slate-400 mt-1">Verified Public Profile</div>
          </div>
        </div>
      </div>

      <!-- Result View 2: Posts Results Layout -->
      <div id="results-posts-view" class="hidden space-y-6">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-500 font-medium">
            Showing <span class="font-bold text-slate-800" id="posts-count-label">6</span> latest extracted posts
          </p>
          <span class="text-xs font-mono text-slate-400">Order: Most Recent</span>
        </div>

        <!-- 3-column desktop, 2-column tablet, 1-column mobile -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="posts-grid-container">
          <!-- Dynamically populated via JS -->
        </div>
      </div>

      <!-- Result View 3: Hashtag Results Layout -->
      <div id="results-hashtag-view" class="hidden space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-lg font-bold text-slate-900" id="hashtag-tag-title">#laravel</span>
              <span class="text-xs bg-pink-100 text-[#E1306C] px-2 py-0.5 rounded-full font-semibold">Hashtag Feed</span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Aggregated public posts tagged with this hashtag.</p>
          </div>
          
          <div class="flex items-center gap-2 text-xs">
            <button onclick="setHashtagDisplay('grid')" id="ht-grid-btn" class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 font-semibold text-slate-900 shadow-xs flex items-center gap-1.5">
              <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
              <span>Grid</span>
            </button>
            <button onclick="setHashtagDisplay('table')" id="ht-table-btn" class="px-3 py-1.5 rounded-lg bg-transparent hover:bg-white text-slate-600 border border-transparent hover:border-slate-200 font-medium flex items-center gap-1.5 transition">
              <i data-lucide="table" class="w-3.5 h-3.5"></i>
              <span>Table</span>
            </button>
          </div>
        </div>

        <!-- Hashtag Posts Cards Grid -->
        <div id="hashtag-grid-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Dynamically populated via JS -->
        </div>

        <!-- Hashtag Posts Table View -->
        <div id="hashtag-table-container" class="hidden bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold tracking-wider">
                  <th class="py-3 px-4">Post</th>
                  <th class="py-3 px-4">Caption</th>
                  <th class="py-3 px-4">Likes</th>
                  <th class="py-3 px-4">Comments</th>
                  <th class="py-3 px-4">Date</th>
                  <th class="py-3 px-4 text-right">Action</th>
                </tr>
              </thead>
              <tbody id="hashtag-table-body" class="divide-y divide-slate-100">
                <!-- Dynamically populated via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Result View 4: Empty Results State (State 9) -->
      <div id="results-empty-view" class="hidden">
        <div class="text-center py-16 px-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
          <div class="w-12 h-12 rounded-full bg-slate-100 mx-auto flex items-center justify-center text-slate-400 mb-3">
            <i data-lucide="inbox" class="w-6 h-6 stroke-[1.5]"></i>
          </div>
          <h3 class="text-base font-semibold text-slate-900">No public results found</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
            We couldn't find any public posts or data matching this query. Please verify the handle or hashtag name.
          </p>
          <button onclick="setPrototypeState('idle')" class="mt-4 px-4 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition shadow-xs">
            Try Another Query
          </button>
        </div>
      </div>

    </section>
  </main>

  <!-- Section G: Minimal Footer -->
  <footer class="mt-auto border-t border-slate-200 bg-slate-50/70 text-slate-500 text-xs">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
      <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Brand & Badges -->
        <div class="flex flex-col sm:flex-row items-center gap-3 text-center sm:text-left">
          <div class="flex items-center gap-2">
            <div class="w-5 h-5 rounded bg-gradient-to-tr from-[#f09433] via-[#E1306C] to-[#833AB4] flex items-center justify-center text-white">
              <i data-lucide="instagram" class="w-3 h-3"></i>
            </div>
            <span class="font-bold text-slate-800">InstaScrape</span>
          </div>
          <span class="hidden sm:inline text-slate-300">•</span>
          <span class="text-slate-600">Built with <strong>Laravel</strong> architecture</span>
          <span class="hidden sm:inline text-slate-300">•</span>
          <a href="https://apify.com/apify/instagram-scraper" target="_blank" rel="noopener" class="text-slate-600 hover:text-slate-900 underline font-medium">
            Powered by Apify
          </a>
        </div>

        <!-- Links & Github / Docs -->
        <div class="flex items-center gap-4 text-slate-500">
          <a href="#docs-modal" onclick="openModal('docs'); return false;" class="hover:text-slate-800 transition">Documentation</a>
          <a href="#about-modal" onclick="openModal('about'); return false;" class="hover:text-slate-800 transition">About Demo</a>
          <a href="https://github.com/apify/instagram-scraper" target="_blank" rel="noopener" class="hover:text-slate-800 transition flex items-center gap-1">
            <span>Apify Actor</span>
            <i data-lucide="external-link" class="w-3 h-3"></i>
          </a>
        </div>
      </div>

      <!-- Legal & Disclaimer -->
      <div class="mt-6 pt-6 border-t border-slate-200/60 text-[11px] text-slate-400 text-center sm:text-left flex flex-col sm:flex-row justify-between items-center gap-2">
        <p>
          Disclaimer: This is an independent developer demonstration tool. Not affiliated with, endorsed by, or associated with Instagram, Meta, or their subsidiaries.
        </p>
        <p class="font-mono text-slate-400">v1.0.0-demo</p>
      </div>
    </div>
  </footer>

  <!-- Modal Component: Docs & About -->
  <div id="modal-container" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 border border-slate-200 shadow-2xl relative animate-in fade-in duration-200">
      <button onclick="closeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 p-1 rounded-lg">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>

      <div id="modal-content">
        <!-- Injected via JS -->
      </div>
    </div>
  </div>

  <!-- Toast Notification Component -->
  <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none">
    <div class="bg-slate-900 text-white text-xs font-medium px-4 py-2.5 rounded-xl shadow-lg border border-slate-800 flex items-center gap-2">
      <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
      <span id="toast-message">Copied to clipboard</span>
    </div>
  </div>

  <!-- Mock Datasets & Application Logic -->
  <script>
    // -------------------------------------------------------------
    // Mock Datasets (Realistic data for Laravel ecosystem & Instagram)
    // -------------------------------------------------------------
    const mockProfileData = {
      id: "839210491",
      username: "laravelphp",
      fullName: "Laravel — The PHP Framework",
      biography: "Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable, truly fulfilling experience.",
      profilePicUrl: "https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&auto=format&fit=crop&q=80",
      externalUrl: "https://laravel.com",
      followersCount: 124500,
      followingCount: 18,
      postsCount: 348,
      isVerified: true,
      category: "Software Company",
      scrapedAt: new Date().toISOString()
    };

    const mockPostsData = [
      {
        id: "post_1",
        shortcode: "C9x0182KaL",
        caption: "Laravel 11 is now released! Featuring a streamlined application structure, per-second rate limiting, health routing, and much more. Read the release notes at laravel.com/docs.",
        likesCount: 3420,
        commentsCount: 284,
        timestamp: "2024-03-12T14:32:00Z",
        displayUrl: "https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/C9x0182KaL"
      },
      {
        id: "post_2",
        shortcode: "C8k1937LpP",
        caption: "Announcing Laracon US 2024 speakers lineup! Join over 2,000 Laravel developers in Dallas, Texas. Tickets are going fast.",
        likesCount: 2890,
        commentsCount: 142,
        timestamp: "2024-04-05T18:10:00Z",
        displayUrl: "https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/C8k1937LpP"
      },
      {
        id: "post_3",
        shortcode: "C7m4012WqQ",
        caption: "Did you know? Laravel Herd now supports Windows! Ultra-fast local PHP development environment without Docker or VM overhead.",
        likesCount: 4510,
        commentsCount: 310,
        timestamp: "2024-05-20T11:05:00Z",
        displayUrl: "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/C7m4012WqQ"
      },
      {
        id: "post_4",
        shortcode: "C6n9832ZkL",
        caption: "Laravel Reverb: Blazingly fast WebSocket server for your Laravel applications. Zero configuration, scalable to millions of connections.",
        likesCount: 3980,
        commentsCount: 198,
        timestamp: "2024-06-01T09:40:00Z",
        displayUrl: "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/C6n9832ZkL"
      },
      {
        id: "post_5",
        shortcode: "C5p1284MmN",
        caption: "Tailwind CSS v4 compatibility with Laravel Blade templates. Clean, maintainable styling meets delightful developer ergonomics.",
        likesCount: 2430,
        commentsCount: 96,
        timestamp: "2024-06-18T16:20:00Z",
        displayUrl: "https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/C5p1284MmN"
      },
      {
        id: "post_6",
        shortcode: "C4q9821PpO",
        caption: "Tips & Tricks: Use Pest PHP for fluent testing in your modern Laravel apps. Clean assertions and effortless mock injection.",
        likesCount: 3120,
        commentsCount: 114,
        timestamp: "2024-07-02T13:00:00Z",
        displayUrl: "https://images.unsplash.com/photo-1515879218367-8466d910aaa4?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/C4q9821PpO"
      }
    ];

    const mockHashtagData = [
      {
        id: "ht_1",
        caption: "Crafting a clean clean API with Laravel and Vue 3! Exploring modern Inertia v2 features. #laravel #vuejs #php #webdev",
        likesCount: 1840,
        commentsCount: 75,
        timestamp: "2024-07-10T10:14:00Z",
        displayUrl: "https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/H0192kda"
      },
      {
        id: "ht_2",
        caption: "My desk setup for building fullstack web applications. Double monitor with VS Code and Laravel docs open. #laravel #developer #setup",
        likesCount: 2950,
        commentsCount: 180,
        timestamp: "2024-07-11T12:30:00Z",
        displayUrl: "https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/H0291abc"
      },
      {
        id: "ht_3",
        caption: "Deploying Laravel applications to production with zero downtime using Docker and Forge. #laravel #devops #docker #backend",
        likesCount: 1420,
        commentsCount: 62,
        timestamp: "2024-07-12T08:45:00Z",
        displayUrl: "https://images.unsplash.com/photo-1607799279861-4dd421887fb3?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/H0382def"
      },
      {
        id: "ht_4",
        caption: "Mastering Eloquent ORM relationships: HasManyThrough and MorphTo tips for high performance SQL queries. #laravel #phpc #database",
        likesCount: 2210,
        commentsCount: 133,
        timestamp: "2024-07-13T15:20:00Z",
        displayUrl: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=600&auto=format&fit=crop&q=80",
        url: "https://instagram.com/p/H0471ghi"
      }
    ];

    // Current State Container
    let currentTab = 'profile'; // 'profile' | 'posts' | 'hashtags'
    let currentStatus = 'completed'; // 'idle' | 'queued' | 'running' | 'processing' | 'completed' | 'failed'
    let activeDataset = mockProfileData;
    let hashtagViewMode = 'grid'; // 'grid' | 'table'

    // DOM References
    const scraperInput = document.getElementById('scraper-input');
    const inputHint = document.getElementById('input-hint');
    const inputLabelText = document.getElementById('input-label-text');
    const inputIcon = document.getElementById('input-icon');
    const validationErrorMsg = document.getElementById('validation-error-msg');
    const validationErrorText = document.getElementById('validation-error-text');

    const statusStates = {
      idle: document.getElementById('status-state-idle'),
      queued: document.getElementById('status-state-queued'),
      running: document.getElementById('status-state-running'),
      processing: document.getElementById('status-state-processing'),
      completed: document.getElementById('status-state-completed'),
      failed: document.getElementById('status-state-failed'),
    };

    const resultsWrapper = document.getElementById('results-wrapper');
    const resultsProfileView = document.getElementById('results-profile-view');
    const resultsPostsView = document.getElementById('results-posts-view');
    const resultsHashtagView = document.getElementById('results-hashtag-view');
    const resultsEmptyView = document.getElementById('results-empty-view');
    const rawJsonDrawer = document.getElementById('raw-json-drawer');
    const rawJsonContent = document.getElementById('raw-json-content');

    // -------------------------------------------------------------
    // Tab Switching Logic
    // -------------------------------------------------------------
    function switchTab(tab) {
      currentTab = tab;
      
      const tabProfile = document.getElementById('tab-profile');
      const tabPosts = document.getElementById('tab-posts');
      const tabHashtags = document.getElementById('tab-hashtags');

      // Reset tab styling
      [tabProfile, tabPosts, tabHashtags].forEach(btn => {
        btn.className = "flex-1 py-2 px-3 rounded-lg flex items-center justify-center gap-2 transition-all duration-150 text-slate-600 hover:text-slate-900";
        const icon = btn.querySelector('svg, i');
        if (icon) icon.classList.remove('text-[#E1306C]');
        if (icon) icon.classList.add('text-slate-400');
      });

      // Highlight active tab
      const activeBtn = document.getElementById(`tab-${tab}`);
      activeBtn.className = "flex-1 py-2 px-3 rounded-lg flex items-center justify-center gap-2 transition-all duration-150 bg-white text-slate-900 shadow-xs font-semibold";
      const activeIcon = activeBtn.querySelector('svg, i');
      if (activeIcon) {
        activeIcon.classList.remove('text-slate-400');
        activeIcon.classList.add('text-[#E1306C]');
      }

      // Update input placeholders & hints
      hideValidationError();

      if (tab === 'profile') {
        scraperInput.placeholder = "Enter Instagram username or profile URL";
        inputLabelText.textContent = "Target Profile or Username";
        inputHint.textContent = "e.g. @laravelphp or instagram.com/laravelphp";
        if (!scraperInput.value || scraperInput.value.startsWith('#')) {
          scraperInput.value = "laravelphp";
        }
      } else if (tab === 'posts') {
        scraperInput.placeholder = "Enter Instagram profile URL";
        inputLabelText.textContent = "Instagram Profile URL";
        inputHint.textContent = "e.g. https://instagram.com/laravelphp";
        if (!scraperInput.value || scraperInput.value.startsWith('#')) {
          scraperInput.value = "https://instagram.com/laravelphp";
        }
      } else if (tab === 'hashtags') {
        scraperInput.placeholder = "Enter a hashtag, e.g. #laravel";
        inputLabelText.textContent = "Hashtag Keyword";
        inputHint.textContent = "e.g. #laravel or php";
        if (!scraperInput.value || !scraperInput.value.startsWith('#')) {
          scraperInput.value = "#laravel";
        }
      }

      // Update current displayed view if we already have completed results
      if (currentStatus === 'completed') {
        renderResultsForCurrentTab();
      }

      lucide.createIcons();
    }

    function fillPreset(val) {
      if (val.startsWith('#')) {
        switchTab('hashtags');
      } else if (currentTab === 'hashtags') {
        switchTab('profile');
      }
      scraperInput.value = val;
      hideValidationError();
      scraperInput.focus();
    }

    // -------------------------------------------------------------
    // Status & Prototype State Switcher
    // -------------------------------------------------------------
    function updateStatus(status) {
      currentStatus = status;
      Object.keys(statusStates).forEach(key => {
        if (key === status) {
          statusStates[key].classList.remove('hidden');
        } else {
          statusStates[key].classList.add('hidden');
        }
      });
      lucide.createIcons();
    }

    function setPrototypeState(state) {
      hideValidationError();
      rawJsonDrawer.classList.add('hidden');
      document.getElementById('raw-json-label').textContent = "View Raw JSON";

      switch (state) {
        case 'idle':
          updateStatus('idle');
          resultsWrapper.classList.add('opacity-40', 'pointer-events-none');
          break;

        case 'loading':
          // Simulate the step progression (Queued -> Running -> Processing)
          resultsWrapper.classList.add('opacity-40', 'pointer-events-none');
          updateStatus('queued');
          setTimeout(() => {
            if (currentStatus === 'queued') updateStatus('running');
          }, 800);
          setTimeout(() => {
            if (currentStatus === 'running') updateStatus('processing');
          }, 1800);
          break;

        case 'profile_success':
          switchTab('profile');
          scraperInput.value = "laravelphp";
          activeDataset = mockProfileData;
          updateStatus('completed');
          resultsWrapper.classList.remove('opacity-40', 'pointer-events-none');
          renderResultsForCurrentTab();
          break;

        case 'posts_success':
          switchTab('posts');
          scraperInput.value = "https://instagram.com/laravelphp";
          activeDataset = mockPostsData;
          updateStatus('completed');
          resultsWrapper.classList.remove('opacity-40', 'pointer-events-none');
          renderResultsForCurrentTab();
          break;

        case 'hashtag_success':
          switchTab('hashtags');
          scraperInput.value = "#laravel";
          activeDataset = mockHashtagData;
          updateStatus('completed');
          resultsWrapper.classList.remove('opacity-40', 'pointer-events-none');
          renderResultsForCurrentTab();
          break;

        case 'empty':
          updateStatus('completed');
          resultsWrapper.classList.remove('opacity-40', 'pointer-events-none');
          showEmptyResult();
          break;

        case 'api_error':
          updateStatus('failed');
          resultsWrapper.classList.add('opacity-40', 'pointer-events-none');
          break;

        case 'val_error':
          scraperInput.value = "";
          showValidationError("Please enter an Instagram username, profile URL, or hashtag.");
          break;
      }
    }

    function showValidationError(msg) {
      validationErrorText.textContent = msg;
      validationErrorMsg.classList.remove('hidden');
      scraperInput.classList.add('border-red-400', 'ring-2', 'ring-red-100');
      scraperInput.focus();
    }

    function hideValidationError() {
      validationErrorMsg.classList.add('hidden');
      scraperInput.classList.remove('border-red-400', 'ring-2', 'ring-red-100');
    }

    // -------------------------------------------------------------
    // Form Submission & Simulated Scraping Pipeline
    // -------------------------------------------------------------
    // -------------------------------------------------------------
    // Helper Formatting & Rendering Functions
    // -------------------------------------------------------------
    function formatMetric(num) {
      if (typeof num !== 'number') num = parseInt(num) || 0;
      if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
      if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
      return num.toLocaleString();
    }

    function populateProfileView(profile) {
      if (!profile) return;
      const avatar = document.getElementById('profile-avatar');
      if (avatar && profile.profilePicUrl) {
        avatar.src = profile.profilePicUrl;
        avatar.onerror = function() {
          this.onerror = null;
          this.src = "https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=250&auto=format&fit=crop&q=80";
        };
      }

      const username = profile.username || 'unknown';
      document.getElementById('profile-username').textContent = username;
      document.getElementById('profile-id').textContent = 'ID: ' + (profile.id || 'N/A');
      document.getElementById('profile-fullname').textContent = profile.fullName || username;
      document.getElementById('profile-bio').textContent = profile.biography || 'No biography available.';

      const verifiedBadge = document.getElementById('profile-verified-badge');
      if (verifiedBadge) {
        if (profile.isVerified) {
          verifiedBadge.classList.remove('hidden');
        } else {
          verifiedBadge.classList.add('hidden');
        }
      }

      const extUrlEl = document.getElementById('profile-external-url');
      const extTextEl = document.getElementById('profile-external-text');
      if (extUrlEl && extTextEl) {
        if (profile.externalUrl) {
          extUrlEl.href = profile.externalUrl;
          extTextEl.textContent = profile.externalUrl;
          extUrlEl.parentElement.classList.remove('hidden');
        } else {
          extUrlEl.parentElement.classList.add('hidden');
        }
      }

      const igLink = document.getElementById('profile-instagram-link');
      if (igLink) {
        igLink.href = 'https://instagram.com/' + username;
      }

      document.getElementById('profile-followers').textContent = formatMetric(profile.followersCount);
      document.getElementById('profile-following').textContent = (profile.followingCount || 0).toLocaleString();
      document.getElementById('profile-total-posts').textContent = (profile.postsCount || 0).toLocaleString();
      document.getElementById('profile-category').textContent = profile.category || 'Public Profile';
    }

    // -------------------------------------------------------------
    // Form Submission & Scraper API Execution
    // -------------------------------------------------------------
    async function handleScrapeSubmit(e) {
      e.preventDefault();
      const val = scraperInput.value.trim();

      if (!val) {
        showValidationError("Please enter a target username or hashtag to scrape.");
        return;
      }

      hideValidationError();

      // UI Loading indicator on button
      const submitBtn = document.getElementById('submit-button');
      const btnText = document.getElementById('btn-text');
      const limit = parseInt(document.getElementById('scraper-limit').value) || 10;

      submitBtn.disabled = true;
      submitBtn.classList.add('opacity-80', 'cursor-wait');
      btnText.textContent = "Scraping...";

      resultsWrapper.classList.add('opacity-40', 'pointer-events-none');

      // Progressive status transitions
      updateStatus('queued');

      const runningTimer = setTimeout(() => {
        if (currentStatus === 'queued') updateStatus('running');
      }, 1000);

      const processingTimer = setTimeout(() => {
        if (currentStatus === 'running') updateStatus('processing');
      }, 3000);

      try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch('/scrape', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || ''
          },
          body: JSON.stringify({
            mode: currentTab,
            target: val,
            limit: limit
          })
        });

        clearTimeout(runningTimer);
        clearTimeout(processingTimer);

        const result = await response.json();

        if (!response.ok) {
          const msg = result.message || (result.errors ? Object.values(result.errors).flat().join(' ') : 'Scraping request failed.');
          throw new Error(msg);
        }

        if (result.status === 'empty' || !result.data || (Array.isArray(result.data) && result.data.length === 0)) {
          activeDataset = [];
          updateStatus('completed');
          resultsWrapper.classList.remove('opacity-40', 'pointer-events-none');
          showEmptyResult();
          showToast("No public results found for this query");
          return;
        }

        activeDataset = result.data;
        updateStatus('completed');
        resultsWrapper.classList.remove('opacity-40', 'pointer-events-none');

        // Populate raw json drawer with raw API items or transformed data
        rawJsonContent.textContent = JSON.stringify(result.raw && result.raw.length ? result.raw : activeDataset, null, 2);

        // Render results for active tab
        renderResultsForCurrentTab();
        showToast("Instagram data extracted successfully");

        resultsWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } catch (err) {
        clearTimeout(runningTimer);
        clearTimeout(processingTimer);
        updateStatus('failed');
        resultsWrapper.classList.add('opacity-40', 'pointer-events-none');

        const errorDetails = document.getElementById('failed-error-details');
        if (errorDetails) {
          errorDetails.textContent = err.message || 'Scraping failed. The target may be private, rate-limited, or not found.';
        }
        showToast("Scraping failed: " + err.message);
      } finally {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-80', 'cursor-wait');
        btnText.textContent = "Scrape Instagram";
      }
    }

    // -------------------------------------------------------------
    // Render Results Components
    // -------------------------------------------------------------
    function renderResultsForCurrentTab() {
      // Hide all result sub-views
      resultsProfileView.classList.add('hidden');
      resultsPostsView.classList.add('hidden');
      resultsHashtagView.classList.add('hidden');
      resultsEmptyView.classList.add('hidden');

      const resultsTitle = document.getElementById('results-title');
      const resultsTimestamp = document.getElementById('results-timestamp');
      resultsTimestamp.textContent = `Scraped ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;

      if (currentTab === 'profile') {
        resultsProfileView.classList.remove('hidden');
        const profile = (activeDataset && !Array.isArray(activeDataset)) ? activeDataset : mockProfileData;
        activeDataset = profile;
        resultsTitle.textContent = "Profile Results: @" + (profile.username || scraperInput.value.replace(/^@/, '') || "laravelphp");
        populateProfileView(profile);
      } else if (currentTab === 'posts') {
        resultsPostsView.classList.remove('hidden');
        const posts = (Array.isArray(activeDataset) && activeDataset.length > 0) ? activeDataset : mockPostsData;
        activeDataset = posts;
        resultsTitle.textContent = "Posts Results: @" + (scraperInput.value.replace(/^https?:\/\/(www\.)?instagram\.com\/?/, '').replace(/\/.*$/, '').replace(/^@/, '') || "laravelphp");
        populatePostsGrid(posts);
      } else if (currentTab === 'hashtags') {
        resultsHashtagView.classList.remove('hidden');
        const tag = scraperInput.value.startsWith('#') ? scraperInput.value : '#' + scraperInput.value.replace(/^#/, '');
        resultsTitle.textContent = `Hashtag Results: ${tag}`;
        document.getElementById('hashtag-tag-title').textContent = tag;
        const posts = (Array.isArray(activeDataset) && activeDataset.length > 0) ? activeDataset : mockHashtagData;
        activeDataset = posts;
        populateHashtagGrid(posts);
        populateHashtagTable(posts);
      }

      // Populate raw json drawer
      if (!rawJsonContent.textContent.trim() || rawJsonContent.textContent.trim() === '[]') {
        rawJsonContent.textContent = JSON.stringify(activeDataset, null, 2);
      }
      lucide.createIcons();
    }

    function showEmptyResult() {
      resultsProfileView.classList.add('hidden');
      resultsPostsView.classList.add('hidden');
      resultsHashtagView.classList.add('hidden');
      resultsEmptyView.classList.remove('hidden');
      document.getElementById('results-title').textContent = "No Results Found";
      activeDataset = [];
      rawJsonContent.textContent = "[]";
    }

    function populatePostsGrid(posts) {
      const container = document.getElementById('posts-grid-container');
      const limit = parseInt(document.getElementById('scraper-limit').value) || 10;
      const list = (Array.isArray(posts) && posts.length > 0) ? posts : (Array.isArray(activeDataset) && activeDataset.length > 0 ? activeDataset : mockPostsData);
      const postsToShow = list.slice(0, limit);
      document.getElementById('posts-count-label').textContent = postsToShow.length;

      container.innerHTML = postsToShow.map(post => `
        <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md transition-shadow flex flex-col group">
          <!-- Thumbnail -->
          <div class="aspect-4/3 sm:aspect-square w-full bg-slate-100 overflow-hidden relative">
            <img 
              src="${post.displayUrl || 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=80'}" 
              alt="Post image" 
              class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300"
              loading="lazy"
              onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=80';"
            >
            <div class="absolute top-2.5 right-2.5 bg-slate-900/70 backdrop-blur-xs text-white text-[11px] font-mono px-2 py-0.5 rounded-md flex items-center gap-1">
              <i data-lucide="image" class="w-3 h-3"></i>
              <span>Photo</span>
            </div>
          </div>

          <!-- Body -->
          <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed line-clamp-3" title="${post.caption || ''}">
              ${post.caption || 'No caption provided.'}
            </p>

            <div class="pt-2 border-t border-slate-100 space-y-2.5">
              <!-- Stats Row: Likes & Comments -->
              <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <div class="flex items-center gap-3">
                  <span class="flex items-center gap-1 text-slate-700 font-semibold" title="Likes">
                    <i data-lucide="heart" class="w-3.5 h-3.5 text-pink-500 fill-pink-50"></i>
                    <span>${(post.likesCount || 0).toLocaleString()}</span>
                  </span>
                  <span class="flex items-center gap-1 text-slate-700 font-semibold" title="Comments">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>${(post.commentsCount || 0).toLocaleString()}</span>
                  </span>
                </div>
                <time class="text-[11px] text-slate-400 font-mono">${post.timestamp ? new Date(post.timestamp).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Recent'}</time>
              </div>

              <!-- View Link -->
              <div class="pt-1">
                <a 
                  href="${post.url || '#'}" 
                  target="_blank" 
                  rel="noopener" 
                  class="w-full py-1.5 px-3 rounded-lg text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 flex items-center justify-center gap-1.5 transition">
                  <span>View on Instagram</span>
                  <i data-lucide="external-link" class="w-3 h-3 text-slate-400"></i>
                </a>
              </div>
            </div>
          </div>
        </article>
      `).join('');
    }

    function populateHashtagGrid(posts) {
      const container = document.getElementById('hashtag-grid-container');
      const list = (Array.isArray(posts) && posts.length > 0) ? posts : (Array.isArray(activeDataset) && activeDataset.length > 0 ? activeDataset : mockHashtagData);

      container.innerHTML = list.map(post => `
        <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md transition flex flex-col group">
          <div class="aspect-video w-full bg-slate-100 overflow-hidden relative">
            <img 
              src="${post.displayUrl || 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=600&auto=format&fit=crop&q=80'}" 
              alt="Hashtag post image" 
              class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300"
              loading="lazy"
              onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=600&auto=format&fit=crop&q=80';"
            >
          </div>
          <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
            <p class="text-xs text-slate-700 leading-relaxed line-clamp-2">
              ${post.caption || 'No caption provided.'}
            </p>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
              <div class="flex items-center gap-3 font-semibold text-slate-700">
                <span class="flex items-center gap-1">
                  <i data-lucide="heart" class="w-3.5 h-3.5 text-pink-500"></i>
                  <span>${(post.likesCount || 0).toLocaleString()}</span>
                </span>
                <span class="flex items-center gap-1">
                  <i data-lucide="message-circle" class="w-3.5 h-3.5 text-slate-400"></i>
                  <span>${post.commentsCount || 0}</span>
                </span>
              </div>
              <a href="${post.url || '#'}" target="_blank" rel="noopener" class="text-xs text-[#E1306C] hover:underline font-semibold flex items-center gap-1">
                <span>View</span>
                <i data-lucide="external-link" class="w-3 h-3"></i>
              </a>
            </div>
          </div>
        </article>
      `).join('');
    }

    function populateHashtagTable(posts) {
      const tbody = document.getElementById('hashtag-table-body');
      const list = (Array.isArray(posts) && posts.length > 0) ? posts : (Array.isArray(activeDataset) && activeDataset.length > 0 ? activeDataset : mockHashtagData);

      tbody.innerHTML = list.map(post => `
        <tr class="hover:bg-slate-50/80 transition">
          <td class="py-3 px-4">
            <img 
              src="${post.displayUrl || 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=600&auto=format&fit=crop&q=80'}" 
              class="w-10 h-10 rounded-lg object-cover border border-slate-200"
              onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=600&auto=format&fit=crop&q=80';"
            >
          </td>
          <td class="py-3 px-4 max-w-xs truncate text-slate-700 font-medium">
            ${post.caption || 'No caption'}
          </td>
          <td class="py-3 px-4 font-semibold text-slate-800">
            ${(post.likesCount || 0).toLocaleString()}
          </td>
          <td class="py-3 px-4 text-slate-600">
            ${post.commentsCount || 0}
          </td>
          <td class="py-3 px-4 text-slate-400 font-mono text-[11px]">
            ${post.timestamp ? new Date(post.timestamp).toLocaleDateString() : 'Recent'}
          </td>
          <td class="py-3 px-4 text-right">
            <a href="${post.url || '#'}" target="_blank" rel="noopener" class="text-xs text-[#E1306C] hover:underline font-semibold inline-flex items-center gap-1">
              <span>Inspect</span>
              <i data-lucide="external-link" class="w-3 h-3"></i>
            </a>
          </td>
        </tr>
      `).join('');
    }

    function setHashtagDisplay(mode) {
      hashtagViewMode = mode;
      const gridContainer = document.getElementById('hashtag-grid-container');
      const tableContainer = document.getElementById('hashtag-table-container');
      const gridBtn = document.getElementById('ht-grid-btn');
      const tableBtn = document.getElementById('ht-table-btn');

      if (mode === 'grid') {
        gridContainer.classList.remove('hidden');
        tableContainer.classList.add('hidden');
        gridBtn.className = "px-3 py-1.5 rounded-lg bg-white border border-slate-300 font-semibold text-slate-900 shadow-xs flex items-center gap-1.5";
        tableBtn.className = "px-3 py-1.5 rounded-lg bg-transparent hover:bg-white text-slate-600 border border-transparent hover:border-slate-200 font-medium flex items-center gap-1.5 transition";
      } else {
        gridContainer.classList.add('hidden');
        tableContainer.classList.remove('hidden');
        tableBtn.className = "px-3 py-1.5 rounded-lg bg-white border border-slate-300 font-semibold text-slate-900 shadow-xs flex items-center gap-1.5";
        gridBtn.className = "px-3 py-1.5 rounded-lg bg-transparent hover:bg-white text-slate-600 border border-transparent hover:border-slate-200 font-medium flex items-center gap-1.5 transition";
      }
      lucide.createIcons();
    }

    // -------------------------------------------------------------
    // Raw JSON Viewer & Export Actions
    // -------------------------------------------------------------
    function toggleRawJson() {
      const isHidden = rawJsonDrawer.classList.contains('hidden');
      const label = document.getElementById('raw-json-label');
      if (isHidden) {
        rawJsonDrawer.classList.remove('hidden');
        label.textContent = "Hide Raw JSON";
      } else {
        rawJsonDrawer.classList.add('hidden');
        label.textContent = "View Raw JSON";
      }
    }

    function copyRawJson() {
      const jsonText = JSON.stringify(activeDataset, null, 2);
      navigator.clipboard.writeText(jsonText).then(() => {
        showToast("JSON payload copied to clipboard");
        const btnText = document.getElementById('copy-btn-text');
        const originalText = btnText.textContent;
        btnText.textContent = "Copied!";
        setTimeout(() => {
          btnText.textContent = originalText;
        }, 2000);
      });
    }

    function exportData(format) {
      if (format === 'json') {
        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(activeDataset, null, 2));
        const downloadAnchor = document.createElement('a');
        downloadAnchor.setAttribute("href", dataStr);
        downloadAnchor.setAttribute("download", `instascrape_${currentTab}_${Date.now()}.json`);
        document.body.appendChild(downloadAnchor);
        downloadAnchor.click();
        downloadAnchor.remove();
        showToast("JSON file downloaded");
      } else if (format === 'csv') {
        let csvContent = "data:text/csv;charset=utf-8,";
        if (Array.isArray(activeDataset)) {
          if (activeDataset.length === 0) return;
          const headers = Object.keys(activeDataset[0]).join(",");
          const rows = activeDataset.map(row => {
            return Object.values(row).map(val => `"${String(val).replace(/"/g, '""')}"`).join(",");
          }).join("\n");
          csvContent += headers + "\n" + rows;
        } else {
          // Object (Profile)
          const headers = Object.keys(activeDataset).join(",");
          const values = Object.values(activeDataset).map(val => `"${String(val).replace(/"/g, '""')}"`).join(",");
          csvContent += headers + "\n" + values;
        }
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `instascrape_${currentTab}_${Date.now()}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        showToast("CSV file exported");
      }
    }

    // -------------------------------------------------------------
    // Toast Notification
    // -------------------------------------------------------------
    function showToast(msg) {
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toast-message');
      toastMessage.textContent = msg;
      toast.classList.remove('translate-y-24', 'opacity-0');
      toast.classList.add('translate-y-0', 'opacity-100');
      setTimeout(() => {
        toast.classList.add('translate-y-24', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
      }, 3000);
    }

    // -------------------------------------------------------------
    // Documentation & About Modals
    // -------------------------------------------------------------
    function openModal(type) {
      const modalContainer = document.getElementById('modal-container');
      const modalContent = document.getElementById('modal-content');

      if (type === 'docs') {
        modalContent.innerHTML = `
          <div class="flex items-center gap-2 mb-3">
            <div class="p-2 rounded-lg bg-pink-50 text-[#E1306C]">
              <i data-lucide="book-open" class="w-5 h-5"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Developer Documentation</h3>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed mb-4">
            InstaScrape is structured as a client-side interface ready to bind to Laravel Controllers via REST or Inertia.js.
          </p>
          <div class="space-y-3 text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono">
            <div>
              <span class="text-pink-600 font-bold">POST</span> /api/v1/scrape/profile
              <p class="text-slate-500 font-sans text-[11px] mt-0.5">Extracts verified badge, bio, counts, and external links.</p>
            </div>
            <div>
              <span class="text-pink-600 font-bold">POST</span> /api/v1/scrape/posts
              <p class="text-slate-500 font-sans text-[11px] mt-0.5">Retrieves latest posts with media previews and likes count.</p>
            </div>
            <div>
              <span class="text-pink-600 font-bold">POST</span> /api/v1/scrape/hashtag
              <p class="text-slate-500 font-sans text-[11px] mt-0.5">Aggregates public hashtag tagged posts.</p>
            </div>
          </div>
          <div class="mt-5 text-right">
            <button onclick="closeModal()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 transition">
              Got it
            </button>
          </div>
        `;
      } else if (type === 'about') {
        modalContent.innerHTML = `
          <div class="flex items-center gap-2 mb-3">
            <div class="p-2 rounded-lg bg-purple-50 text-[#833AB4]">
              <i data-lucide="info" class="w-5 h-5"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">About InstaScrape</h3>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed mb-3">
            InstaScrape is a modern, responsive single-page developer interface designed according to the specifications in PLAN.md.
          </p>
          <ul class="text-xs text-slate-600 space-y-1.5 list-disc pl-4 mb-4">
            <li>Powered conceptually by <strong class="text-slate-800">apify/instagram-scraper</strong>.</li>
            <li>Designed with modern SaaS aesthetics (Linear, Vercel, Raycast).</li>
            <li>Ready for modular Laravel Blade component extraction.</li>
            <li>No backend or API credentials required for this prototype.</li>
          </ul>
          <div class="mt-5 text-right">
            <button onclick="closeModal()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 transition">
              Close
            </button>
          </div>
        `;
      }

      modalContainer.classList.remove('hidden');
      lucide.createIcons();
    }

    function closeModal() {
      document.getElementById('modal-container').classList.add('hidden');
    }

    // Close modal on click outside
    document.getElementById('modal-container').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    // -------------------------------------------------------------
    // Initialization
    // -------------------------------------------------------------
    window.addEventListener('DOMContentLoaded', () => {
      lucide.createIcons();
      // Start in initial state with successful profile result loaded as demo
      renderResultsForCurrentTab();
    });
  </script>
</body>
</html>
