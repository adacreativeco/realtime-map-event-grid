<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canlı Olay Haritası - Realtime Map Event Grid</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/i18n.js"></script>
</head>
<body class="bg-[#090d16] text-gray-100 overflow-hidden flex flex-col h-screen select-none">

    <!-- Top Navigation Bar -->
    <header class="glass-nav h-16 flex items-center justify-between px-6 z-30 relative shrink-0">
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-cyan-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-cyan-500/30">
                    <span class="text-lg">🌐</span>
                </div>
                <div>
                    <h1 data-i18n="brand_title" class="text-base font-bold tracking-tight brand-font bg-gradient-to-r from-white via-gray-200 to-cyan-300 bg-clip-text text-transparent">Event Grid</h1>
                    <span data-i18n="brand_subtitle" class="text-[10px] text-gray-400 font-mono block -mt-1">Genel Harita & Canlı Akış</span>
                </div>
            </div>

            <nav class="hidden sm:flex items-center space-x-1 pl-4 border-l border-gray-800">
                <a href="/public_map.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-cyan-600/20 text-cyan-400 border border-cyan-500/30 transition-all flex items-center space-x-1.5">
                    <span>🗺️</span>
                    <span data-i18n="nav_map">Harita</span>
                </a>
                <a href="/api_docs.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-gray-200 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>📖</span>
                    <span data-i18n="nav_api_docs">API Dokümantasyonu</span>
                </a>
            </nav>
        </div>

        <div class="flex items-center space-x-3">
            <button onclick="i18n.toggleLanguage()" id="lang-indicator" class="px-2.5 py-1 rounded-lg bg-gray-800/90 hover:bg-gray-700 text-gray-300 text-xs font-semibold border border-gray-700/50 transition-colors shadow">
                🇹🇷 TR
            </button>

            <!-- Replay Scrubber Toggle -->
            <button id="btn-replay-toggle" onclick="toggleReplayMode()" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-800/90 hover:bg-gray-700 text-gray-300 transition-all flex items-center space-x-1 border border-gray-700/50 shadow" title="Geçmiş Oynatıcı / Time Replay">
                <span data-i18n="replay_btn_toggle">⏱️ Geçmiş Oynatıcı</span>
            </button>

            <div class="flex items-center space-x-2 px-3 py-1 rounded-full bg-gray-900/80 border border-gray-800">
                <div id="conn-dot" class="live-dot"></div>
                <span id="conn-label" data-i18n="conn_sse" class="text-xs font-semibold text-emerald-400">SSE Canlı</span>
            </div>

            <a href="/login.php" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-1.5">
                <span>🔐</span>
                <span data-i18n="nav_login">Yönetici Girişi</span>
            </a>
        </div>
    </header>

    <!-- Main Workspace -->
    <div class="flex flex-1 overflow-hidden relative">

        <!-- Sidebar -->
        <aside class="w-72 md:w-80 glass-panel border-r border-gray-800 flex flex-col z-20 shrink-0">
            <div class="p-4 border-b border-gray-800 space-y-3 bg-gray-950/40">
                <div class="flex items-center justify-between">
                    <h2 data-i18n="sidebar_title" class="font-bold text-sm text-gray-200">Son Olaylar</h2>
                    <span id="event-count-badge" class="px-2 py-0.5 rounded-full text-[11px] font-mono bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">0</span>
                </div>

                <div class="relative">
                    <input type="text" id="filter-search" data-i18n-placeholder="search_placeholder" placeholder="Olay ara..." class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-1.5 pl-8 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-cyan-500">
                    <span class="absolute left-2.5 top-2 text-gray-500 text-xs">🔍</span>
                </div>

                <div>
                    <select id="filter-type" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-2.5 py-1.5 text-xs text-gray-300 focus:outline-none focus:border-cyan-500">
                        <option value="" data-i18n="filter_all_types">Tüm Olay Türleri</option>
                    </select>
                </div>
            </div>

            <div id="event-list" class="flex-1 overflow-y-auto divide-y divide-gray-800/40">
                <div class="p-6 text-center text-gray-500 text-xs" data-i18n="loading_events">Yükleniyor...</div>
            </div>

            <div class="p-3 border-t border-gray-800 bg-gray-950/60 flex items-center justify-between text-[11px] text-gray-500 font-mono">
                <span><span data-i18n="last_update">Güncelleme</span>: <span id="last-update-time" class="text-gray-300">Şimdi</span></span>
            </div>
        </aside>

        <!-- Map -->
        <main class="flex-1 relative h-full">
            <div id="map" class="w-full h-full z-0"></div>

            <!-- Layer Switcher -->
            <div class="absolute top-4 left-4 z-[1000] glass-panel rounded-2xl p-1.5 shadow-2xl flex items-center space-x-1 border border-gray-700/60">
                <button id="mode-both" onclick="setViewMode('both')" class="mode-btn px-3 py-1.5 rounded-xl text-xs font-semibold bg-indigo-600 text-white shadow transition-all">
                    <span data-i18n="mode_both">✨ Karma Mod</span>
                </button>
                <button id="mode-pins" onclick="setViewMode('pins')" class="mode-btn px-3 py-1.5 rounded-xl text-xs font-medium text-gray-400 hover:text-gray-200 transition-all">
                    <span data-i18n="mode_pins">📍 Pinler</span>
                </button>
                <button id="mode-heatmap" onclick="setViewMode('heatmap')" class="mode-btn px-3 py-1.5 rounded-xl text-xs font-medium text-gray-400 hover:text-gray-200 transition-all">
                    <span data-i18n="mode_heatmap">🔥 Isı Haritası</span>
                </button>
            </div>

            <!-- Replay Scrubber Panel -->
            <div id="replay-panel" class="hidden absolute bottom-6 left-1/2 -translate-x-1/2 z-[1000] w-11/12 max-w-xl glass-panel rounded-2xl p-4 shadow-2xl border border-cyan-500/40 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm">⏱️</span>
                        <span data-i18n="replay_title" class="text-xs font-bold text-gray-100">Zaman Çizelgesi & Oynatıcı</span>
                    </div>
                    <div class="flex items-center space-x-3 text-xs font-mono">
                        <span id="replay-current-time" class="text-cyan-300 font-semibold">-</span>
                        <button onclick="exitReplayMode()" data-i18n="replay_return_live" class="px-2 py-0.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-[11px] font-semibold transition-colors">
                            🔴 Canlıya Dön
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <button id="btn-replay-play" onclick="togglePlayReplay()" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-cyan-600/30 transition-all shrink-0">
                        <span data-i18n="replay_play">▶️ Oynat</span>
                    </button>
                    <input type="range" id="replay-slider" oninput="onReplaySliderChange(this.value)" class="flex-1 accent-cyan-500 cursor-pointer h-2 bg-gray-800 rounded-lg">
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/public_app.js"></script>
</body>
</html>
