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
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com/)
[![License](https://img.shields.io/badge/License-Apache_2.0-blue?style=for-the-badge)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-19%20Passed-success?style=for-the-badge&logo=php&logoColor=white)](tests/test_rteg.php)
[![GitHub Stars](https://img.shields.io/github/stars/adacreativeco/realtime-map-event-grid?style=for-the-badge&color=ffd700)](https://github.com/adacreativeco/realtime-map-event-grid/stargazers)

</div>

**Realtime Map Event Grid (RTEG)** is a high-throughput, geospatial event streaming and visualization engine. It provides high-frequency HTTP ingestion, sub-second Server-Sent Events (SSE) broadcasting, spatial boundary filtering, interactive Leaflet/Heatmap layers, and source key governance.

<p align="center">
  <img src="docs/assets/rteg_dashboard.png" alt="Realtime Map Event Grid - Live Operations Dashboard" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

---

## 🌟 Visual Showcase & Key Modules

```mermaid
graph LR
    A[IoT / Fleet / Edge Ingest] -->|HTTP POST| B(RTEG Ingest Engine)
    B -->|Persist| C[(SQLite / PostgreSQL / MySQL)]
    B -->|Broadcast| D[SSE Stream Broker]
    D -->|Real-Time SSE| E[Interactive Map Dashboard]
    D -->|Public Feed| F[Public Embed Map]
    C -->|Aggregate| G[Telemetry & Analytics Engine]
```

### 1. 🗺️ Live Event Stream & Spatial Dashboard
- Real-time SSE live feed with clustering, custom category icons, and auto-centering.
- Multi-layer visualization supporting **Combined Markers, Isolated Pins, and Density Heatmaps**.
- Instant payload inspection and temporal replay controls.

<p align="center">
  <img src="docs/assets/rteg_public_map.png" alt="RTEG Public Map View" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 2. 📊 Stream Telemetry & Spatial Analytics
- Real-time 24-hour event distribution chart (Chart.js time series).
- Dynamic event category breakdown (Vehicle Movement, Sensor Alert, Delivery, Emergency, Drone Survey).
- Top transmitting API sources and throughput ratios.

<p align="center">
  <img src="docs/assets/rteg_analytics.png" alt="RTEG Stream Analytics & Telemetry" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 3. 🔑 Source & API Key Governance
- Multi-client API key generation with granular active/paused state controls.
- Automatic rate-limiting and source event accounting.

<p align="center">
  <img src="docs/assets/rteg_sources.png" alt="RTEG Source & API Key Management" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 4. 📖 Interactive API Documentation
- Full REST API specification with sample `curl`, JavaScript, and Python snippets.

<p align="center">
  <img src="docs/assets/rteg_api_docs.png" alt="RTEG Interactive API Documentation" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

---

## 🚀 Quick Start

### 1. Requirements
- PHP 8.0+ (with `pdo`, `pdo_sqlite`, `pdo_pgsql`, or `pdo_mysql`)
- Web server (Nginx, Apache, or PHP Built-in Server)

### 2. Installation & Run
```bash
# Clone the repository
git clone https://github.com/adacreativeco/realtime-map-event-grid.git
cd realtime-map-event-grid

# Initialize the database
php database/init.php

# Start development server
php -S 127.0.0.1:8080 -t public
```

### 3. Run Automated Tests
```bash
php tests/test_rteg.php
```

---

## 📡 Ingesting Events via API

```bash
curl -X POST http://localhost:8080/api/v1/event/ingest.php \
  -H "Content-Type: application/json" \
  -H "X-Source-Key: test_key" \
  -d '{
    "type": "vehicle_movement",
    "lat": 41.0082,
    "lon": 28.9784,
    "payload": {
      "vehicle_id": "FLEET-104",
      "speed_kmh": 62,
      "status": "in_transit"
    }
  }'
```

---

## 📄 License
Licensed under the Apache License 2.0. Developed with 🧠 by [ADA Creative Co.](https://github.com/adacreativeco).
