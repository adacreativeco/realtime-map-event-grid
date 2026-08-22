<?php
require_once __DIR__ . '/../src/Auth.php';
Auth::check();
$currentUser = $_SESSION['admin_user'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli - Realtime Map Event Grid</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            900: '#090d16',
                            800: '#111827',
                            700: '#1f2937',
                            600: '#374151'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <!-- Custom Styles -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- i18n Translation Engine -->
    <script src="/assets/js/i18n.js"></script>
</head>
<body class="bg-[#090d16] text-gray-100 overflow-hidden flex flex-col h-screen select-none">

    <!-- Top Navigation Bar -->
    <header class="glass-nav h-16 flex items-center justify-between px-6 z-30 relative shrink-0">
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <span class="text-lg">📡</span>
                </div>
                <div>
                    <h1 data-i18n="brand_title" class="text-base font-bold tracking-tight brand-font bg-gradient-to-r from-white via-gray-200 to-indigo-300 bg-clip-text text-transparent">Event Grid</h1>
                    <span data-i18n="brand_subtitle" class="text-[10px] text-gray-400 font-mono block -mt-1">Realtime Spatial Engine</span>
                </div>
            </div>

            <nav class="hidden md:flex items-center space-x-1 pl-4 border-l border-gray-800">
                <a href="/index.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 transition-all flex items-center space-x-1.5">
                    <span>🗺️</span>
                    <span data-i18n="nav_map">Canlı Harita</span>
                </a>
                <a href="/stats.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-gray-200 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>📊</span>
                    <span data-i18n="nav_analytics">Analitik</span>
                </a>
                <a href="/sources.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-gray-200 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>🔑</span>
                    <span data-i18n="nav_sources">Kaynaklar</span>
                </a>
                <a href="/api_docs.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-gray-200 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>📖</span>
                    <span data-i18n="nav_api_docs">API Docs</span>
                </a>
                <a href="/public_map.php" target="_blank" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-cyan-400 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>🌐</span>
                    <span data-i18n="nav_public_map">Genel Harita</span>
                </a>
            </nav>
        </div>

        <div class="flex items-center space-x-3">
            <!-- Language Switcher -->
            <button onclick="i18n.toggleLanguage()" id="lang-indicator" class="px-2.5 py-1 rounded-lg bg-gray-800/90 hover:bg-gray-700 text-gray-300 text-xs font-semibold border border-gray-700/50 transition-colors shadow" title="Dili Değiştir / Switch Language">
                🇹🇷 TR
            </button>

            <!-- Replay Scrubber Toggle -->
            <button id="btn-replay-toggle" onclick="toggleReplayMode()" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-800/90 hover:bg-gray-700 text-gray-300 transition-all flex items-center space-x-1 border border-gray-700/50 shadow" title="Geçmiş Oynatıcı / Time Replay">
                <span data-i18n="replay_btn_toggle">⏱️ Geçmiş Oynatıcı</span>
            </button>

            <!-- Connection Status Indicator -->
            <div class="flex items-center space-x-2 px-3 py-1 rounded-full bg-gray-900/80 border border-gray-800">
                <div id="conn-dot" class="live-dot"></div>
                <span id="conn-label" class="text-xs font-semibold text-emerald-400">SSE Canlı</span>
            </div>

            <!-- Sound Alert Toggle -->
            <button id="btn-sound" onclick="toggleSound()" class="p-2 rounded-lg bg-gray-800/80 hover:bg-gray-700/80 text-gray-500 hover:text-gray-300 transition-colors border border-gray-700/50" title="Ses Bildirimi Aç/Kapat">
                <span class="text-sm">🔔</span>
            </button>

            <!-- Simulator Quick Buttons -->
            <div class="hidden lg:flex items-center space-x-1.5 bg-gray-900/90 p-1 rounded-xl border border-gray-800">
                <button id="btn-simulate" onclick="triggerSimulator(1)" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-600 hover:bg-indigo-500 text-white transition-all shadow shadow-indigo-600/30 flex items-center space-x-1">
                    <span>⚡</span>
                    <span data-i18n="sim_1">1 Olay Üret</span>
                </button>
                <button onclick="triggerSimulator(5)" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-200 transition-all">
                    <span data-i18n="sim_5">+5 Simüle</span>
                </button>
                <button id="btn-auto-sim" onclick="toggleAutoSimulator()" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 transition-all flex items-center space-x-1">
                    <span data-i18n="sim_auto_start">▶️ Otomatik Akış</span>
                </button>
            </div>

            <!-- User and Logout -->
            <div class="flex items-center space-x-3 pl-2 border-l border-gray-800 text-xs">
                <span class="text-gray-400 hidden sm:inline font-mono"><?= htmlspecialchars($currentUser) ?></span>
                <a href="/logout.php" data-i18n="nav_logout" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition-all font-medium">
                    Çıkış
                </a>
            </div>
        </div>
    </header>

    <!-- Main Workspace (Sidebar + Map) -->
    <div class="flex flex-1 overflow-hidden relative">

        <!-- Left Sidebar: Filter Panel & Live Feed -->
        <aside class="w-80 md:w-96 glass-panel border-r border-gray-800/80 flex flex-col z-20 shrink-0">
            <!-- Sidebar Header & Filters -->
            <div class="p-4 border-b border-gray-800 space-y-3 bg-gray-950/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-ping"></span>
                        <h2 data-i18n="sidebar_title" class="font-bold text-sm text-gray-200">Canlı Olay Akışı</h2>
                    </div>
                    <span id="event-count-badge" class="px-2 py-0.5 rounded-full text-[11px] font-mono bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">0 / 0</span>
                </div>

                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="filter-search" data-i18n-placeholder="search_placeholder" placeholder="Olay ID, tür veya payload ara..." class="w-full bg-gray-900/90 border border-gray-700/70 rounded-xl px-3 py-2 pl-8 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition-colors">
                    <span class="absolute left-2.5 top-2.5 text-gray-500 text-xs">🔍</span>
                </div>

                <!-- Filters Dropdowns -->
                <div class="grid grid-cols-2 gap-2">
                    <select id="filter-type" class="bg-gray-900/90 border border-gray-700/70 rounded-xl px-2.5 py-1.5 text-xs text-gray-300 focus:outline-none focus:border-indigo-500">
                        <option value="" data-i18n="filter_all_types">Tüm Olay Türleri</option>
                    </select>

                    <select id="filter-source" class="bg-gray-900/90 border border-gray-700/70 rounded-xl px-2.5 py-1.5 text-xs text-gray-300 focus:outline-none focus:border-indigo-500">
                        <option value="" data-i18n="filter_all_sources">Tüm Kaynaklar</option>
                    </select>
                </div>

                <!-- Bounding Box & Reset Checkbox -->
                <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1">
                    <label class="flex items-center space-x-1.5 cursor-pointer">
                        <input type="checkbox" id="filter-bounds" class="rounded bg-gray-900 border-gray-700 text-indigo-600 focus:ring-0">
                        <span data-i18n="filter_bounds">Harita alanındakiler</span>
                    </label>
                    <button id="btn-reset-filters" data-i18n="filter_reset" class="text-indigo-400 hover:text-indigo-300 font-medium">
                        Temizle
                    </button>
                </div>
            </div>

            <!-- Scrollable Event Stream List -->
            <div id="event-list" class="flex-1 overflow-y-auto divide-y divide-gray-800/40">
                <div class="p-8 text-center text-gray-500">
                    <div class="inline-block animate-spin text-xl mb-2">⏳</div>
                    <p class="text-xs" data-i18n="loading_events">Olaylar yükleniyor...</p>
                </div>
            </div>

            <!-- Sidebar Footer -->
            <div class="p-3 border-t border-gray-800/80 bg-gray-950/60 flex items-center justify-between text-[11px] text-gray-500 font-mono">
                <span><span data-i18n="last_update">Son Güncelleme</span>: <span id="last-update-time" class="text-gray-300">Şimdi</span></span>
                <span class="text-emerald-400 flex items-center space-x-1">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span data-i18n="status_active">Aktif</span>
                </span>
            </div>
        </aside>

        <!-- Map Container -->
        <main class="flex-1 relative h-full">
            <div id="map" class="w-full h-full z-0"></div>

            <!-- Map Floating Layer Mode Switcher -->
            <div class="absolute top-4 left-4 z-[1000] glass-panel rounded-2xl p-1.5 shadow-2xl flex items-center space-x-1 border border-gray-700/60">
                <button id="mode-both" onclick="setViewMode('both')" class="mode-btn px-3 py-1.5 rounded-xl text-xs font-semibold bg-indigo-600 text-white shadow transition-all flex items-center space-x-1.5">
                    <span data-i18n="mode_both">✨ Karma Mod</span>
                </button>
                <button id="mode-pins" onclick="setViewMode('pins')" class="mode-btn px-3 py-1.5 rounded-xl text-xs font-medium text-gray-400 hover:text-gray-200 transition-all flex items-center space-x-1.5">
                    <span data-i18n="mode_pins">📍 Pinler</span>
                </button>
                <button id="mode-heatmap" onclick="setViewMode('heatmap')" class="mode-btn px-3 py-1.5 rounded-xl text-xs font-medium text-gray-400 hover:text-gray-200 transition-all flex items-center space-x-1.5">
                    <span data-i18n="mode_heatmap">🔥 Isı Haritası (Heatmap)</span>
                </button>
            </div>

            <!-- Floating Time Scrubber Replay Bar -->
            <div id="replay-panel" class="hidden absolute bottom-6 left-1/2 -translate-x-1/2 z-[1000] w-11/12 max-w-2xl glass-panel rounded-2xl p-4 shadow-2xl border border-indigo-500/40 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm">⏱️</span>
                        <span data-i18n="replay_title" class="text-xs font-bold text-gray-100">Zaman Çizelgesi & Oynatıcı</span>
                    </div>
                    <div class="flex items-center space-x-3 text-xs font-mono">
                        <span id="replay-current-time" class="text-indigo-300 font-semibold">-</span>
                        <span id="replay-active-count" class="text-gray-400 bg-gray-900/80 px-2 py-0.5 rounded border border-gray-800 text-[11px]">-</span>
                        <button onclick="exitReplayMode()" data-i18n="replay_return_live" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-[11px] font-semibold transition-colors">
                            🔴 Canlıya Dön
                        </button>
                    </div>
                </div>

                <!-- Range Slider -->
                <div class="flex items-center space-x-3">
                    <button id="btn-replay-play" onclick="togglePlayReplay()" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/30 transition-all shrink-0">
                        <span data-i18n="replay_play">▶️ Oynat</span>
                    </button>
                    <input type="range" id="replay-slider" oninput="onReplaySliderChange(this.value)" class="flex-1 accent-indigo-500 cursor-pointer h-2 bg-gray-800 rounded-lg">
                </div>

                <!-- Playback Speed -->
                <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1">
                    <div class="flex items-center space-x-1.5">
                        <span data-i18n="replay_speed">Hız:</span>
                        <button id="speed-1x" onclick="setReplaySpeed(1)" class="speed-btn px-2 py-0.5 rounded bg-indigo-600 text-white font-mono font-semibold">1x</button>
                        <button id="speed-2x" onclick="setReplaySpeed(2)" class="speed-btn px-2 py-0.5 rounded bg-gray-800 text-gray-400 hover:text-white font-mono">2x</button>
                        <button id="speed-5x" onclick="setReplaySpeed(5)" class="speed-btn px-2 py-0.5 rounded bg-gray-800 text-gray-400 hover:text-white font-mono">5x</button>
                        <button id="speed-10x" onclick="setReplaySpeed(10)" class="speed-btn px-2 py-0.5 rounded bg-gray-800 text-gray-400 hover:text-white font-mono">10x</button>
                    </div>
                    <span class="text-[10px] text-gray-500">Zaman çizelgesini sürükleyerek veya oynatarak olayları canlandırın</span>
                </div>
            </div>

            <!-- Event Detail Modal (Glassmorphism overlay) -->
            <div id="event-modal" class="hidden absolute top-4 right-4 z-[1001] w-96 max-h-[85vh] glass-panel rounded-2xl shadow-2xl p-5 border border-indigo-500/30 overflow-y-auto">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-800">
                    <h3 class="font-bold text-base text-gray-100" id="modal-title" data-i18n="modal_title">Olay Detayı</h3>
                    <button onclick="closeModal()" class="w-7 h-7 rounded-lg bg-gray-800/80 hover:bg-gray-700 text-gray-400 hover:text-white flex items-center justify-center transition-colors">
                        &times;
                    </button>
                </div>
                <div id="modal-content"></div>
            </div>

            <!-- Floating Live Toast Notifications -->
            <div id="toast-container" class="absolute bottom-6 right-6 z-[1000] space-y-2 pointer-events-auto"></div>
        </main>
    </div>

    <!-- Application Core Script -->
    <script src="/assets/js/app.js"></script>
</body>
</html>
