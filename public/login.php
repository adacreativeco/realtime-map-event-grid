<?php
require_once __DIR__ . '/../src/Auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!Auth::verifyCsrfToken($token)) {
        $error = "Oturum süresi doldu veya geçersiz CSRF token.";
    } elseif (Auth::login($username, $password)) {
        header('Location: /index.php');
        exit;
    } else {
        $error = "Hatalı kullanıcı adı veya şifre (veya çok fazla deneme).";
    }
}
?>
<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi - Realtime Map Event Grid</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/i18n.js"></script>
</head>
<body class="bg-[#090d16] text-gray-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Background glowing ambient lights -->
    <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/3 w-96 h-96 bg-cyan-600/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md glass-panel p-8 rounded-3xl border border-gray-800 shadow-2xl relative z-10">
        <!-- Language Switcher in Login -->
        <div class="flex justify-end mb-2">
            <button onclick="i18n.toggleLanguage()" id="lang-indicator" class="px-2.5 py-1 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-semibold border border-gray-700/50 transition-colors shadow">
                🇹🇷 TR
            </button>
        </div>

        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center shadow-xl shadow-indigo-500/30 mx-auto mb-4">
                <span class="text-2xl">📡</span>
            </div>
            <h1 data-i18n="brand_title" class="text-2xl font-bold tracking-tight brand-font bg-gradient-to-r from-white via-gray-200 to-indigo-300 bg-clip-text text-transparent">Event Grid</h1>
            <p data-i18n="login_subtitle" class="text-xs text-gray-400 mt-1">Realtime Spatial Event Engine Yönetici Girişi</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold flex items-center justify-between">
                <span>⚠️ <?= htmlspecialchars($error) ?></span>
                <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-white">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::generateCsrfToken()) ?>">

            <div>
                <label data-i18n="login_username" class="block text-xs font-semibold text-gray-300 mb-1.5" for="username">Kullanıcı Adı</label>
                <input class="w-full bg-gray-900/90 border border-gray-700/80 rounded-xl px-4 py-2.5 text-xs text-gray-100 placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition-colors" id="username" name="username" type="text" placeholder="admin" required autofocus>
            </div>

            <div>
                <label data-i18n="login_password" class="block text-xs font-semibold text-gray-300 mb-1.5" for="password">Şifre</label>
                <input class="w-full bg-gray-900/90 border border-gray-700/80 rounded-xl px-4 py-2.5 text-xs text-gray-100 placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition-colors" id="password" name="password" type="password" placeholder="••••••••••••" required>
            </div>

            <button data-i18n="login_submit" class="w-full mt-2 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white font-semibold py-2.5 px-4 rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition-all" type="submit">
                Giriş Yap
            </button>
        </form>

        <!-- Credentials Helper Note -->
        <div class="mt-6 p-3.5 rounded-xl bg-gray-900/60 border border-gray-800/80 text-[11px] text-gray-400 space-y-1">
            <div class="font-semibold text-gray-300 flex items-center space-x-1">
                <span>💡</span>
                <span data-i18n="login_hint_title">Varsayılan Giriş Bilgileri:</span>
            </div>
            <div class="font-mono text-gray-400"><span data-i18n="login_hint_user">Kullanıcı:</span> <span class="text-indigo-400 font-bold">admin</span> &bull; <span data-i18n="login_hint_pass">Şifre:</span> <span class="text-indigo-400 font-bold">password123</span></div>
        </div>

        <!-- Public Links -->
        <div class="mt-6 pt-4 border-t border-gray-800/80 flex items-center justify-center space-x-4 text-xs text-gray-500">
            <a href="/public_map.php" class="hover:text-cyan-400 transition-colors flex items-center space-x-1">
                <span>🌐</span>
                <span data-i18n="nav_public_map">Genel Harita</span>
            </a>
            <span>&bull;</span>
            <a href="/api_docs.php" class="hover:text-indigo-400 transition-colors flex items-center space-x-1">
                <span>📖</span>
                <span data-i18n="nav_api_docs">API Dokümantasyonu</span>
            </a>
        </div>
    </div>

</body>
</html>
