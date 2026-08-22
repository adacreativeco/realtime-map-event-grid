<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/EventManager.php';
Auth::check();

$eventManager = new EventManager();
$currentUser = $_SESSION['admin_user'] ?? 'admin';
$msg = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $token = $_POST['csrf_token'] ?? '';
    if (Auth::verifyCsrfToken($token)) {
        $name = trim($_POST['name'] ?? '');
        $customKey = trim($_POST['custom_key'] ?? '');
        if (!empty($name)) {
            $secret = !empty($customKey) ? $customKey : 'key_' . bin2hex(random_bytes(16));
            $eventManager->addSource($name, $secret);
            header('Location: /sources.php?msg=added');
            exit;
        } else {
            $error = "Kaynak adı boş olamaz.";
        }
    } else {
        $error = "Geçersiz oturum (CSRF Token hatası).";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $token = $_POST['csrf_token'] ?? '';
    if (Auth::verifyCsrfToken($token)) {
        $sourceId = $_POST['source_id'] ?? '';
        $eventManager->toggleSourceStatus($sourceId);
        header('Location: /sources.php?msg=status_updated');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $token = $_POST['csrf_token'] ?? '';
    if (Auth::verifyCsrfToken($token)) {
        $sourceId = $_POST['source_id'] ?? '';
        $eventManager->deleteSource($sourceId);
        header('Location: /sources.php?msg=deleted');
        exit;
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $msg = "Yeni kaynak başarıyla oluşturuldu.";
    if ($_GET['msg'] === 'deleted') $msg = "Kaynak sistemden silindi.";
    if ($_GET['msg'] === 'status_updated') $msg = "Kaynak durumu güncellendi.";
}

$sources = $eventManager->getSources();
?>
<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaynak Yönetimi - Realtime Map Event Grid</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <a href="/stats.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-gray-200 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>📊</span>
                    <span data-i18n="nav_analytics">Analitik</span>
                </a>
                <a href="/sources.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 transition-all flex items-center space-x-1.5">
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
            <button onclick="i18n.toggleLanguage()" id="lang-indicator" class="px-2.5 py-1 rounded-lg bg-gray-800/90 hover:bg-gray-700 text-gray-300 text-xs font-semibold border border-gray-700/50 transition-colors shadow">
                🇹🇷 TR
            </button>
            <a href="/logout.php" data-i18n="nav_logout" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-medium">
                Çıkış
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="container mx-auto px-6 py-8 flex-1 max-w-7xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h2 data-i18n="sources_title" class="text-2xl font-bold tracking-tight text-gray-100 brand-font">Kaynak & API Anahtarı Yönetimi</h2>
                <p data-i18n="sources_subtitle" class="text-xs text-gray-400 mt-1">Dış sistemlerin Ingest API'ye veri gönderebilmesi için API Secret Key üretin ve yönetin.</p>
            </div>
            <div>
                <button onclick="document.getElementById('add-modal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2">
                    <span data-i18n="btn_add_source">➕ Yeni Kaynak Ekle</span>
                </button>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center justify-between">
                <span>✅ <?= htmlspecialchars($msg) ?></span>
                <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-white">&times;</button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold flex items-center justify-between">
                <span>⚠️ <?= htmlspecialchars($error) ?></span>
                <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-white">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Sources Table Card -->
        <div class="glass-panel rounded-2xl border border-gray-800/80 shadow-2xl overflow-hidden">
            <table class="min-w-full text-left text-xs">
                <thead class="bg-gray-900/80 text-gray-400 font-semibold border-b border-gray-800">
                    <tr>
                        <th data-i18n="th_source_info" class="px-6 py-4">Kaynak Bilgisi</th>
                        <th data-i18n="th_secret_key" class="px-6 py-4">API Secret Key (X-Source-Key)</th>
                        <th data-i18n="th_status" class="px-6 py-4 text-center">Durum</th>
                        <th data-i18n="th_total_events" class="px-6 py-4 text-center">Toplam Olay</th>
                        <th data-i18n="th_actions" class="px-6 py-4 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    <?php if (empty($sources)): ?>
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">Kayıtlı API kaynağı bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sources as $source): ?>
                        <tr class="hover:bg-gray-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-100 text-sm"><?= htmlspecialchars($source['source_name']) ?></div>
                                <div class="font-mono text-gray-500 text-[11px] mt-0.5"><?= htmlspecialchars($source['source_id']) ?></div>
                                <div class="text-[10px] text-gray-500 mt-1">ID: <?= htmlspecialchars($source['created_at']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <code class="font-mono text-gray-300 bg-gray-950 px-2.5 py-1.5 rounded-lg border border-gray-800 text-[11px] select-all">
                                        <?= htmlspecialchars($source['source_secret']) ?>
                                    </code>
                                    <button onclick="copyToClipboard('<?= htmlspecialchars($source['source_secret']) ?>')" class="p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-indigo-400 transition-colors" title="Kopyala">
                                        📋
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($source['status'] === 'active'): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5"></span>
                                        <span data-i18n="status_enabled">Aktif</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-gray-500/10 text-gray-400 border border-gray-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500 mr-1.5"></span>
                                        <span data-i18n="status_disabled">Pasif</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-mono font-bold text-gray-200 text-sm"><?= number_format($source['total_events']) ?></span>
                                <?php if ($source['last_event_at']): ?>
                                    <div class="text-[10px] text-gray-500 mt-0.5"><?= htmlspecialchars($source['last_event_at']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::generateCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="source_id" value="<?= htmlspecialchars($source['source_id']) ?>">
                                    <button type="submit" class="px-2.5 py-1 bg-gray-800 hover:bg-gray-700 text-gray-300 text-[11px] rounded-lg transition-colors border border-gray-700/50">
                                        <span data-i18n="<?= $source['status'] === 'active' ? 'btn_pause' : 'btn_activate' ?>"><?= $source['status'] === 'active' ? 'Durdur' : 'Aktifleştir' ?></span>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Bu kaynağı silmek istediğinizden emin misiniz?');" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::generateCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="source_id" value="<?= htmlspecialchars($source['source_id']) ?>">
                                    <button type="submit" data-i18n="btn_delete" class="px-2.5 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-[11px] rounded-lg transition-colors border border-rose-500/20">
                                        Sil
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Source Modal -->
    <div id="add-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="glass-panel p-6 rounded-2xl border border-gray-800 w-full max-w-md shadow-2xl">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-800">
                <h3 data-i18n="modal_add_title" class="font-bold text-base text-gray-100">Yeni API Kaynağı Tanımla</h3>
                <button onclick="document.getElementById('add-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">&times;</button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::generateCsrfToken()) ?>">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label data-i18n="form_name_label" class="block text-xs font-semibold text-gray-300 mb-1.5">Kaynak Adı / Açıklama *</label>
                    <input type="text" name="name" data-i18n-placeholder="form_name_placeholder" placeholder="Örn: Araç Takip Filosu, IoT Sensör Ağı" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3.5 py-2 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-indigo-500" required>
                </div>

                <div>
                    <label data-i18n="form_key_label" class="block text-xs font-semibold text-gray-300 mb-1.5">Özel API Secret Key (Opsiyonel)</label>
                    <input type="text" name="custom_key" data-i18n-placeholder="form_key_placeholder" placeholder="Boş bırakılırsa güvenli rastgele anahtar üretilir" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3.5 py-2 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden')" data-i18n="form_cancel" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs rounded-xl transition-colors">
                        İptal
                    </button>
                    <button type="submit" data-i18n="form_submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl shadow-lg shadow-indigo-600/30 transition-colors">
                        Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert(window.i18n ? window.i18n.t('toast_payload_copied') : 'Panoya kopyalandı!');
            });
        }
    </script>
</body>
</html>
