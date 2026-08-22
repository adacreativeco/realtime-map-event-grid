<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API & Entegrasyon Kılavuzu - Realtime Map Event Grid</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
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
                    <h1 class="text-base font-bold tracking-tight brand-font bg-gradient-to-r from-white via-gray-200 to-indigo-300 bg-clip-text text-transparent">Event Grid</h1>
                    <span class="text-[10px] text-gray-400 font-mono block -mt-1">Developer & Integration Docs</span>
                </div>
            </div>

            <nav class="hidden md:flex items-center space-x-1 pl-4 border-l border-gray-800">
                <a href="/index.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-gray-200 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>🗺️</span>
                    <span>Yönetici Haritası</span>
                </a>
                <a href="/public_map.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-cyan-400 hover:bg-gray-800/60 transition-all flex items-center space-x-1.5">
                    <span>🌐</span>
                    <span>Genel Harita</span>
                </a>
                <a href="/api_docs.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 transition-all flex items-center space-x-1.5">
                    <span>📖</span>
                    <span>API Kılavuzu</span>
                </a>
            </nav>
        </div>

        <div class="flex items-center space-x-4">
            <a href="/login.php" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition-all">
                Giriş Yap
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-10 max-w-5xl flex-1 space-y-10">

        <!-- Intro Hero -->
        <div class="glass-panel p-8 rounded-3xl border border-gray-800 shadow-2xl relative overflow-hidden">
            <div class="absolute right-0 top-0 w-80 h-80 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <h2 class="text-3xl font-extrabold tracking-tight brand-font bg-gradient-to-r from-white via-indigo-200 to-cyan-300 bg-clip-text text-transparent">
                Realtime Map Event Grid API
            </h2>
            <p class="text-sm text-gray-400 mt-2 max-w-2xl">
                Dış servisler, IoT sensörleri, mobil uygulamalar veya araç takip sistemleri için yüksek performanslı, coğrafi olay (Spatial Event) toplama ve canlı yayınlama API arayüzü.
            </p>
            <div class="mt-6 flex flex-wrap items-center gap-3 text-xs font-mono">
                <div class="px-3 py-1.5 rounded-xl bg-gray-900 border border-gray-800 text-gray-300">
                    Base URL: <span class="text-indigo-400 font-bold">http://localhost:8081/api/v1</span>
                </div>
                <div class="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                    SSE Canlı Akış: <span class="font-bold">Aktif</span>
                </div>
            </div>
        </div>

        <!-- Section 1: Ingest API (POST) -->
        <section class="glass-panel p-8 rounded-3xl border border-gray-800 shadow-xl space-y-6">
            <div class="flex items-center justify-between border-b border-gray-800 pb-4">
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold font-mono uppercase">POST</span>
                    <h3 class="text-lg font-bold text-gray-100 font-mono">/api/v1/event/ingest.php</h3>
                </div>
                <span class="text-xs text-gray-400 font-medium">Olay Kaydı (Veri Girişi)</span>
            </div>

            <p class="text-xs text-gray-400">
                Sisteme yeni bir coğrafi olay göndermek için kullanılır. İstek başlığında <code class="text-indigo-400 bg-gray-900 px-1.5 py-0.5 rounded">X-Source-Key</code> zorunludur.
            </p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Request Spec -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-300">İstek Parametreleri (JSON Body)</h4>
                    <ul class="text-xs space-y-2 text-gray-400">
                        <li><code class="text-indigo-400 font-mono">type</code> (String, Zorunlu): Olay türü (örn: <span class="text-gray-200">vehicle_movement</span>, <span class="text-gray-200">sensor_alert</span>)</li>
                        <li><code class="text-indigo-400 font-mono">lat</code> (Float, Zorunlu): Enlem (-90..90)</li>
                        <li><code class="text-indigo-400 font-mono">lon</code> (Float, Zorunlu): Boylam (-180..180)</li>
                        <li><code class="text-indigo-400 font-mono">timestamp</code> (Integer, Zorunlu): Unix zaman damgası</li>
                        <li><code class="text-indigo-400 font-mono">payload</code> (Object, Zorunlu): Esnek JSON veri nesnesi</li>
                        <li><code class="text-indigo-400 font-mono">event_id</code> (String, Opsiyonel): Benzersiz olay kimliği</li>
                    </ul>
                </div>

                <!-- Code Example -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span class="font-bold">Örnek cURL</span>
                    </div>
                    <pre class="bg-gray-950 p-4 rounded-2xl border border-gray-800 text-xs font-mono text-gray-300 overflow-x-auto">curl -X POST http://localhost:8081/api/v1/event/ingest.php \
  -H "Content-Type: application/json" \
  -H "X-Source-Key: test_key" \
  -d '{
    "type": "vehicle_movement",
    "lat": 41.0151,
    "lon": 28.9795,
    "timestamp": 1733872741,
    "payload": {
      "speed_kmh": 65,
      "vehicle_id": "34-ABC-789"
    }
  }'</pre>
                </div>
            </div>

            <!-- Live Interactive Tester -->
            <div class="mt-6 pt-6 border-t border-gray-800">
                <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-3 flex items-center space-x-1.5">
                    <span>⚡</span>
                    <span>Canlı API Test Konsolu (Try It Out)</span>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] text-gray-400 mb-1">API Key (X-Source-Key)</label>
                        <input type="text" id="test-source-key" value="test_key" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-200">
                    </div>
                    <div>
                        <label class="block text-[11px] text-gray-400 mb-1">Olay Türü (Type)</label>
                        <input type="text" id="test-type" value="vehicle_movement" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-200">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-[11px] text-gray-400 mb-1">JSON Payload</label>
                    <textarea id="test-payload" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded-xl p-3 text-xs font-mono text-gray-200">{ "vehicle_id": "34-TEST-01", "speed": 82, "status": "active" }</textarea>
                </div>
                <div class="mt-3 flex items-center space-x-3">
                    <button onclick="executeLiveIngestTest()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow transition-all">
                        🚀 İsteği Gönder (Test Et)
                    </button>
                    <span id="test-ingest-status" class="text-xs font-mono text-gray-400"></span>
                </div>
                <div id="test-ingest-response" class="hidden mt-3 p-3 rounded-xl bg-gray-950 border border-gray-800 text-xs font-mono text-gray-300"></div>
            </div>
        </section>

        <!-- Section 2: Server-Sent Events (SSE) Stream -->
        <section class="glass-panel p-8 rounded-3xl border border-gray-800 shadow-xl space-y-6">
            <div class="flex items-center justify-between border-b border-gray-800 pb-4">
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 text-xs font-bold font-mono uppercase">STREAM</span>
                    <h3 class="text-lg font-bold text-gray-100 font-mono">/api/v1/events/stream.php</h3>
                </div>
                <span class="text-xs text-emerald-400 font-medium flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Gerçek Zamanlı Push Akışı</span>
                </span>
            </div>

            <p class="text-xs text-gray-400">
                Tarayıcı veya istemci uygulamaların HTTP Server-Sent Events (SSE) protokolü üzerinden yeni olayları sıfır gecikmeyle dinlemesini sağlar.
            </p>

            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-300">JavaScript İstemci Entegrasyon Örneği</h4>
                <pre class="bg-gray-950 p-4 rounded-2xl border border-gray-800 text-xs font-mono text-gray-300 overflow-x-auto">const eventSource = new EventSource('/api/v1/events/stream.php');

eventSource.addEventListener('connected', (e) => {
    console.log('SSE Canlı Yayın Bağlandı:', JSON.parse(e.data));
});

eventSource.addEventListener('event', (e) => {
    const newEvent = JSON.parse(e.data);
    console.log('Yeni Coğrafi Olay Alındı:', newEvent);
    // Haritaya ekleyin veya UI güncelleyin
});

eventSource.addEventListener('ping', () => {
    // Heartbeat sinyali
});

eventSource.onerror = (err) => {
    console.warn('Bağlantı koptu, yeniden bağlanılıyor...', err);
};</pre>
            </div>
        </section>

        <!-- Section 3: Public Read API -->
        <section class="glass-panel p-8 rounded-3xl border border-gray-800 shadow-xl space-y-6">
            <div class="flex items-center justify-between border-b border-gray-800 pb-4">
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 rounded-xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 text-xs font-bold font-mono uppercase">GET</span>
                    <h3 class="text-lg font-bold text-gray-100 font-mono">/api/v1/public/events.php</h3>
                </div>
                <span class="text-xs text-gray-400 font-medium">Genel Olay Listesi</span>
            </div>

            <p class="text-xs text-gray-400">
                3. parti entegrasyonların veya harita arayüzlerinin son olayları güvenle sorgulaması için genel API endpoint'i.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">Query Parametreleri</h4>
                    <ul class="text-xs space-y-1.5 text-gray-400">
                        <li><code class="text-cyan-400 font-mono">type</code>: Olay tipine göre filtreleme</li>
                        <li><code class="text-cyan-400 font-mono">source_id</code>: Kaynak ID filtreleme</li>
                        <li><code class="text-cyan-400 font-mono">search</code>: Olay ID veya metin arama</li>
                        <li><code class="text-cyan-400 font-mono">start_time</code> / <code class="text-cyan-400 font-mono">end_time</code>: Zaman aralığı</li>
                        <li><code class="text-cyan-400 font-mono">limit</code> (max 100), <code class="text-cyan-400 font-mono">offset</code>: Sayfalama</li>
                        <li><code class="text-cyan-400 font-mono">after_id</code>: Belirli olaydan sonrakileri getirme</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">Örnek Yanıt</h4>
                    <pre class="bg-gray-950 p-3 rounded-xl border border-gray-800 text-[11px] font-mono text-gray-300">{
  "status": "success",
  "data": [
    {
      "event_id": "evt_20260822_a4f1",
      "source_id": "test_source",
      "type": "vehicle_movement",
      "lat": 41.0151,
      "lon": 28.9795,
      "timestamp": 1733872741,
      "created_at": "2026-08-22 04:10:00"
    }
  ],
  "count": 1
}</pre>
                </div>
            </div>
        </section>

    </main>

    <script>
        async function executeLiveIngestTest() {
            const statusEl = document.getElementById('test-ingest-status');
            const respEl = document.getElementById('test-ingest-response');
            statusEl.textContent = 'Gönderiliyor...';

            const key = document.getElementById('test-source-key').value;
            const type = document.getElementById('test-type').value;
            let payload = {};
            try {
                payload = JSON.parse(document.getElementById('test-payload').value);
            } catch(e) {
                alert('Geçersiz JSON Payload formatı!');
                return;
            }

            const body = {
                type: type,
                lat: 41.0082 + (Math.random() - 0.5) * 0.05,
                lon: 28.9784 + (Math.random() - 0.5) * 0.05,
                timestamp: Math.floor(Date.now() / 1000),
                payload: payload
            };

            try {
                const res = await fetch('/api/v1/event/ingest.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Source-Key': key
                    },
                    body: JSON.stringify(body)
                });
                const json = await res.json();
                statusEl.textContent = `HTTP ${res.status} (${json.status})`;
                respEl.classList.remove('hidden');
                respEl.textContent = JSON.stringify(json, null, 2);
            } catch(err) {
                statusEl.textContent = 'Hata: ' + err.message;
            }
        }
    </script>
</body>
</html>
