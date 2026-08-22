# Realtime Map Event Grid (RTEG) — Kullanım ve Entegrasyon Kılavuzu

Realtime Map Event Grid, harici kaynaklardan gelen coğrafi olayları (Spatial Events) anlık olarak toplayan, kaydeden, Leaflet.js ve Heatmap ile gerçek zamanlı görselleştiren modern bir olay takip platformudur.

---

## 🚀 Hızlı Başlangıç

### 1. Gereksinimler
* **PHP:** PHP 8.0 veya üzeri (`pdo_sqlite` ve `curl` eklentileri aktif olmalıdır).
* **Web Sunucusu:** PHP Dahili Sunucu, Apache veya Nginx.

### 2. Başlatma
Proje dizininde aşağıdaki komut ile sunucuyu başlatın:
```bash
php -S localhost:8081 -t public
```

### 3. Yönetici Paneli Girişi
* **URL:** `http://localhost:8081/login.php`
* **Kullanıcı Adı:** `admin`
* **Şifre:** `password123`
*(Alternatif Master Admin: `master_admin` / `Xk9#mP2$vL5@zQ1!`)*

---

## 🗺️ Sayfalar ve Modüller

| Sayfa | URL | Açıklama |
|---|---|---|
| **Canlı Harita (Dashboard)** | `/index.php` | Canlı SSE/Polling akışı, Heatmap/Pin katmanları, filtreler ve olay simülatörü. |
| **Sistem Analitiği** | `/stats.php` | 24 saatlik saatlik olay trendi, tür dağılım grafiği ve KPI sayaçları. |
| **Kaynak Yönetimi** | `/sources.php` | API Secret Key (`X-Source-Key`) oluşturma, durdurma ve yönetme. |
| **Genel Ziyaretçi Haritası** | `/public_map.php` | Ziyaretçiler için genel canlı harita görünümü. |
| **İnteraktif API Docs** | `/api_docs.php` | Canlı "Try It Out" API test konsolu ve kod örnekleri. |

---

## 📡 API Uç Noktaları

### 1. Event Ingest (Olay Girişi)
* **Endpoint:** `POST /api/v1/event/ingest.php`
* **Header:**
  * `Content-Type: application/json`
  * `X-Source-Key: <SOURCE_SECRET_KEY>` *(Örn: `test_key`)*

**Örnek İstek (cURL):**
```bash
curl -X POST http://localhost:8081/api/v1/event/ingest.php \
  -H "Content-Type: application/json" \
  -H "X-Source-Key: test_key" \
  -d '{
    "type": "vehicle_movement",
    "lat": 41.015137,
    "lon": 28.979530,
    "timestamp": 1733872741,
    "payload": {
      "vehicle_id": "34-ABC-789",
      "speed_kmh": 72,
      "status": "in_transit"
    }
  }'
```

**Yanıt (201 Created):**
```json
{
  "status": "success",
  "event_id": "evt_20260822_a4b9c1d2"
}
```

---

### 2. Canlı Akış (Server-Sent Events - SSE)
* **Endpoint:** `GET /api/v1/events/stream.php`
* **Parametreler (Opsiyonel):** `last_event_id`, `type`, `source_id`

**JavaScript İstemcisi:**
```javascript
const stream = new EventSource('/api/v1/events/stream.php');

stream.addEventListener('event', (e) => {
    const eventData = JSON.parse(e.data);
    console.log('Canlı Olay:', eventData);
});
```

---

### 3. Genel Olay Okuma API (Public Read)
* **Endpoint:** `GET /api/v1/public/events.php`
* **Filtreler:** `type`, `source_id`, `search`, `start_time`, `end_time`, `limit` (max 100), `offset`, `after_id`

---

### 4. Olay Simülatörü API
* **Endpoint:** `GET /api/v1/simulator.php?count=1` *(Oturum gerektirir)*
* **İşlev:** Haritada canlı hareket simüle etmek için otomatik gerçekçi konum ve payload üretir.

---

## 🧹 Bakım & Zamanlanmış Görevler (Cron)

### 30 Günden Eski Verileri Temizleme
```bash
0 3 * * * /usr/bin/php /path/to/project/scripts/cleanup.php >> /var/log/event_cleanup.log 2>&1
```

### Outbound Webhook Kuyruğunu Dağıtma
```bash
*/1 * * * * /usr/bin/php /path/to/project/scripts/dispatch_webhooks.php >> /var/log/webhook_dispatch.log 2>&1
```
