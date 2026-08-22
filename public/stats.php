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
    <title>Analitik ve İstatistikler - Realtime Map Event Grid</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/i18n.js"></script>
</head>
<body class="bg-[#090d16] text-gray-100 min-h-screen flex flex-col">

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
                <a href="/index.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-gray-200 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>🗺️</span>
                    <span data-i18n="nav_map">Canlı Harita</span>
                </a>
                <a href="/stats.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 transition-all flex items-center space-x-1.5">
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
            </nav>
        </div>

        <div class="flex items-center space-x-3">
            <!-- Language Switcher -->
            <button onclick="i18n.toggleLanguage()" id="lang-indicator" class="px-2.5 py-1 rounded-lg bg-gray-800/90 hover:bg-gray-700 text-gray-300 text-xs font-semibold border border-gray-700/50 transition-colors shadow">
                🇹🇷 TR
            </button>

            <button onclick="loadStats()" class="px-3 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-xs font-semibold text-gray-200 border border-gray-700/60 flex items-center space-x-1.5 transition-colors">
                <span>🔄</span>
                <span data-i18n="btn_refresh">Yenile</span>
            </button>
            <a href="/logout.php" data-i18n="nav_logout" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-medium">
                Çıkış
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="container mx-auto px-6 py-8 flex-1 max-w-7xl">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 data-i18n="stats_title" class="text-2xl font-bold tracking-tight text-gray-100 brand-font">Sistem Analitiği & Raporlar</h2>
                <p data-i18n="stats_subtitle" class="text-xs text-gray-400 mt-1">Gerçek zamanlı olay akış hacimleri ve kaynak performans metrikleri</p>
            </div>
            <div class="flex items-center space-x-2 text-xs font-mono text-gray-400 bg-gray-900/90 px-3 py-1.5 rounded-xl border border-gray-800">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span data-i18n="stats_auto_refresh">Otomatik Canlı Güncelleme (10sn)</span>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <!-- Total Events -->
            <div class="glass-panel p-5 rounded-2xl border border-gray-800/80 hover:border-indigo-500/30 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span data-i18n="stat_total_events" class="text-xs font-bold uppercase tracking-wider">Toplam Kayıtlı Olay</span>
                    <span class="text-lg">🗄️</span>
                </div>
                <div class="text-3xl font-extrabold text-white font-mono" id="stat-total">-</div>
                <div class="mt-2 text-[11px] text-gray-400 flex items-center space-x-1">
                    <span class="text-indigo-400 font-semibold" id="stat-active-sources">-</span>
                    <span data-i18n="stat_active_sources_label">Aktif Kaynak</span>
                </div>
            </div>

            <!-- Last 5 Min -->
            <div class="glass-panel p-5 rounded-2xl border border-gray-800/80 hover:border-sky-500/30 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span data-i18n="stat_5min" class="text-xs font-bold uppercase tracking-wider">Son 5 Dakika</span>
                    <span class="text-lg">⚡</span>
                </div>
                <div class="text-3xl font-extrabold text-sky-400 font-mono" id="stat-5min">-</div>
                <div data-i18n="stat_5min_desc" class="mt-2 text-[11px] text-gray-400">Anlık olay frekansı</div>
            </div>

            <!-- Last 1 Hour -->
            <div class="glass-panel p-5 rounded-2xl border border-gray-800/80 hover:border-emerald-500/30 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span data-i18n="stat_1hour" class="text-xs font-bold uppercase tracking-wider">Son 1 Saat</span>
                    <span class="text-lg">⏱️</span>
                </div>
                <div class="text-3xl font-extrabold text-emerald-400 font-mono" id="stat-1hour">-</div>
                <div data-i18n="stat_1hour_desc" class="mt-2 text-[11px] text-gray-400">Saatlik akış yoğunluğu</div>
            </div>

            <!-- Last 24 Hours -->
            <div class="glass-panel p-5 rounded-2xl border border-gray-800/80 hover:border-purple-500/30 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span data-i18n="stat_24hours" class="text-xs font-bold uppercase tracking-wider">Son 24 Saat</span>
                    <span class="text-lg">📅</span>
                </div>
                <div class="text-3xl font-extrabold text-purple-400 font-mono" id="stat-24hour">-</div>
                <div data-i18n="stat_24hours_desc" class="mt-2 text-[11px] text-gray-400">Günlük toplam hacim</div>
            </div>
        </div>

        <!-- 24 Hour Hourly Trend Chart -->
        <div class="glass-panel p-6 rounded-2xl border border-gray-800/80 mb-8 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 data-i18n="chart_trend_title" class="font-bold text-base text-gray-200">24 Saatlik Olay Dağılım Trendi</h3>
                    <p data-i18n="chart_trend_subtitle" class="text-xs text-gray-500">Saatlik periyotlarda işlenen toplam konumlu olay miktarı</p>
                </div>
                <span data-i18n="chart_trend_badge" class="text-xs font-mono text-indigo-400 px-2.5 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/20">Canlı Zaman Serisi</span>
            </div>
            <div class="h-64">
                <canvas id="hourlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Bottom Row: Type Distribution & Sources -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Event Types Donut -->
            <div class="glass-panel p-6 rounded-2xl border border-gray-800/80 shadow-xl flex flex-col">
                <h3 data-i18n="chart_type_title" class="font-bold text-base text-gray-200 mb-1">Olay Türleri Dağılımı</h3>
                <p data-i18n="chart_type_subtitle" class="text-xs text-gray-500 mb-4">Kategorilere göre gelen olayların yüzdesel oranı</p>
                <div class="h-64 flex items-center justify-center relative">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            <!-- Top Sources Table -->
            <div class="glass-panel p-6 rounded-2xl border border-gray-800/80 shadow-xl flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 data-i18n="chart_sources_title" class="font-bold text-base text-gray-200">En Aktif Kaynaklar (Top Sources)</h3>
                        <p data-i18n="chart_sources_subtitle" class="text-xs text-gray-500">Sisteme en çok veri gönderen API istemcileri</p>
                    </div>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="min-w-full text-left text-xs">
                        <thead class="bg-gray-900/60 text-gray-400 font-semibold border-b border-gray-800">
                            <tr>
                                <th data-i18n="table_col_source" class="px-4 py-3">Kaynak Adı / ID</th>
                                <th data-i18n="table_col_count" class="px-4 py-3 text-right">Olay Sayısı</th>
                                <th data-i18n="table_col_ratio" class="px-4 py-3 text-right">Oran</th>
                            </tr>
                        </thead>
                        <tbody id="source-list" class="divide-y divide-gray-800/60">
                            <tr><td colspan="3" class="p-4 text-center text-gray-500">Yükleniyor...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        let hourlyChart = null;
        let typeChart = null;

        async function loadStats() {
            try {
                const res = await fetch('/api/v1/stats.php');
                const json = await res.json();
                if (json.status === 'success') {
                    const data = json.data;

                    document.getElementById('stat-total').textContent = data.total_events?.toLocaleString() || '0';
                    document.getElementById('stat-active-sources').textContent = data.active_sources || '0';
                    document.getElementById('stat-5min').textContent = data.last_5_min?.toLocaleString() || '0';
                    document.getElementById('stat-1hour').textContent = data.last_1_hour?.toLocaleString() || '0';
                    document.getElementById('stat-24hour').textContent = data.last_24_hours?.toLocaleString() || '0';

                    if (data.hourly_trend) {
                        const labels = data.hourly_trend.map(h => h.hour);
                        const counts = data.hourly_trend.map(h => h.count);

                        const ctxHourly = document.getElementById('hourlyTrendChart').getContext('2d');
                        if (hourlyChart) hourlyChart.destroy();

                        hourlyChart = new Chart(ctxHourly, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: window.i18n ? window.i18n.t('table_col_count') : 'Olay Sayısı',
                                    data: counts,
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                                    fill: true,
                                    tension: 0.35,
                                    borderWidth: 2.5,
                                    pointBackgroundColor: '#818cf8',
                                    pointRadius: 3,
                                    pointHoverRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { color: 'rgba(255, 255, 255, 0.04)' }, ticks: { color: '#6b7280', font: { size: 10 } } },
                                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.04)' }, ticks: { color: '#6b7280', font: { size: 10 }, precision: 0 } }
                                }
                            }
                        });
                    }

                    if (data.by_type) {
                        const typeLabels = data.by_type.map(x => window.i18n ? window.i18n.t('type_' + x.type) : x.type);
                        const typeCounts = data.by_type.map(x => x.count);
                        const colors = ['#38bdf8', '#f59e0b', '#10b981', '#ef4444', '#a855f7', '#6366f1', '#ec4899'];

                        const ctxType = document.getElementById('typeChart').getContext('2d');
                        if (typeChart) typeChart.destroy();

                        typeChart = new Chart(ctxType, {
                            type: 'doughnut',
                            data: {
                                labels: typeLabels,
                                datasets: [{
                                    data: typeCounts,
                                    backgroundColor: colors.slice(0, typeLabels.length),
                                    borderWidth: 2,
                                    borderColor: '#111827'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '65%',
                                plugins: {
                                    legend: { position: 'right', labels: { color: '#9ca3af', boxWidth: 12, font: { size: 11 } } }
                                }
                            }
                        });
                    }

                    const tbody = document.getElementById('source-list');
                    if (data.top_sources && data.top_sources.length > 0) {
                        const totalTop = data.top_sources.reduce((acc, s) => acc + s.count, 0);
                        tbody.innerHTML = data.top_sources.map(s => {
                            const pct = totalTop > 0 ? Math.round((s.count / totalTop) * 100) : 0;
                            return `
                                <tr class="hover:bg-gray-800/40 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-200">${s.source_name || s.source_id}</div>
                                        <div class="font-mono text-[10px] text-gray-500">${s.source_id}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-gray-200">${s.count.toLocaleString()}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">%${pct}</span>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-gray-500">Henüz kaynak verisi yok.</td></tr>';
                    }
                }
            } catch(e) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            setInterval(loadStats, 10000);
            window.addEventListener('languageChanged', () => {
                loadStats();
            });
        });
    </script>
</body>
</html>
