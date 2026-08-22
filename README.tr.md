# 📡 Realtime Map Event Grid (RTEG)

<p align="center">
  <a href="README.md">🇬🇧 English</a> •
  <a href="README.tr.md">🇹🇷 Türkçe</a>
</p>

---

<div align="center">

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![SQLite](https://img.shields.io/badge/SQLite-3.0+-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://sqlite.org/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-PostGIS-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Hazır-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com/)
[![Lisans](https://img.shields.io/badge/Lisans-Apache_2.0-blue?style=for-the-badge)](LICENSE)
[![Testler](https://img.shields.io/badge/Testler-19%20Geçti-success?style=for-the-badge&logo=php&logoColor=white)](tests/test_rteg.php)
[![GitHub Stars](https://img.shields.io/github/stars/adacreativeco/realtime-map-event-grid?style=for-the-badge&color=ffd700)](https://github.com/adacreativeco/realtime-map-event-grid/stargazers)

</div>

**Realtime Map Event Grid (RTEG)**, yüksek hacimli mekânsal (geospatial) olay akışlarını toplayan, Server-Sent Events (SSE) ile anlık olarak harita üzerinde görselleştiren ve yöneten yüksek performanslı bir gerçek zamanlı olay motorudur.

<p align="center">
  <img src="docs/assets/rteg_dashboard.png" alt="Realtime Map Event Grid - Canlı Operasyon Haritası" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

---

## 🌟 Öne Çıkan Modüller & Görseller

```mermaid
graph LR
    A[IoT / Araç Takip / Sensör Akışı] -->|HTTP POST| B(RTEG Ingest Motoru)
    B -->|Kalıcı Depolama| C[(SQLite / PostgreSQL / MySQL)]
    B -->|Yayın| D[SSE Akış Dağıtıcısı]
    D -->|Gerçek Zamanlı SSE| E[İnteraktif Yönetici Haritası]
    D -->|Genel Akış| F[Genel Harita Görünümü]
    C -->|Analiz| G[Telemetri & İstatistik Motoru]
```

### 1. 🗺️ Canlı Olay Haritası & Kontrol Paneli
* Kümeleme (Clustering), kategori bazlı dinamik ikonlar ve anlık olay listesi.
* **Kombine İşaretçiler, Nokta Pinler ve Isı Haritası (Heatmap)** katman desteği.
* Olay detay penceresi, zaman tüneli (Time Replay) ve harita içi arama.

<p align="center">
  <img src="docs/assets/rteg_public_map.png" alt="RTEG Genel Harita Ekranı" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 2. 📊 Akış Telemetrisi & Mekânsal Analitik
* 24 saatlik olay dağılım trendi grafiği (Chart.js zaman serisi).
* Olay kategorisi dağılım oranları (Araç Hareketi, Sensör Uyarısı, Teslimat, Acil Durum vb.).
* En aktif kaynak istemcileri ve iletim oranları.

<p align="center">
  <img src="docs/assets/rteg_analytics.png" alt="RTEG Telemetri ve Analitik Panosu" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 3. 🔑 Kaynak & API Anahtarı Yönetimi
* Dış istemciler için anlık API Secret Key üretimi, duraklatma ve aktif etme kontrolleri.
* Hız sınırı (Rate Limiting) ve kaynak bazlı olay sayaçları.

<p align="center">
  <img src="docs/assets/rteg_sources.png" alt="RTEG Kaynak ve API Anahtarı Yönetimi" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 4. 📖 İnteraktif API Dokümantasyonu
* Örnek `curl`, JavaScript ve Python kod parçacıklarıyla eksiksiz REST API kılavuzu.

<p align="center">
  <img src="docs/assets/rteg_api_docs.png" alt="RTEG İnteraktif API Kılavuzu" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

---

## 🚀 Hızlı Başlangıç

### 1. Gereksinimler
* PHP 8.0+ (`pdo`, `pdo_sqlite`, `pdo_pgsql` veya `pdo_mysql` eklentileri)
* Web sunucusu (Nginx, Apache veya PHP Yerel Sunucusu)

### 2. Kurulum ve Çalıştırma
```bash
# Depoyu klonlayın
git clone https://github.com/adacreativeco/realtime-map-event-grid.git
cd realtime-map-event-grid

# Veritabanını başlatın
php database/init.php

# Geliştirme sunucusunu başlatın
php -S 127.0.0.1:8080 -t public
```

### 3. Otomatik Testleri Çalıştırma
```bash
php tests/test_rteg.php
```

---

## 📄 Lisans
Apache License 2.0 ile lisanslanmıştır. [ADA Creative Co.](https://github.com/adacreativeco) tarafından geliştirilmiştir.
