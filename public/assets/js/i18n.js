// ==========================================
// REALTIME MAP EVENT GRID - i18n (TR / EN)
// ==========================================

const translations = {
    tr: {
        // Brand & Nav
        'brand_title': 'Event Grid',
        'brand_subtitle': 'Realtime Spatial Engine',
        'nav_map': 'Canlı Harita',
        'nav_analytics': 'Analitik',
        'nav_sources': 'Kaynaklar',
        'nav_api_docs': 'API Docs',
        'nav_public_map': 'Genel Harita',
        'nav_login': 'Yönetici Girişi',
        'nav_logout': 'Çıkış',
        'btn_refresh': 'Yenile',

        // Connection
        'conn_sse': 'SSE Canlı',
        'conn_polling': 'Polling (3sn)',
        'conn_offline': 'Bağlantı Kesildi',

        // Simulator
        'sim_1': '1 Olay Üret',
        'sim_5': '+5 Simüle',
        'sim_auto_start': '▶️ Otomatik Akış',
        'sim_auto_stop': '⏹️ Akışı Durdur',

        // Sidebar & Filters
        'sidebar_title': 'Canlı Olay Akışı',
        'search_placeholder': 'Olay ID, tür veya payload ara...',
        'filter_all_types': 'Tüm Olay Türleri (Hepsi)',
        'filter_all_sources': 'Tüm Kaynaklar (Hepsi)',
        'filter_bounds': 'Harita alanındakiler',
        'filter_reset': 'Temizle',
        'no_events_found': 'Eşleşen olay bulunamadı',
        'no_events_desc': 'Filtre kriterlerini temizleyin veya yeni olay simüle edin.',
        'loading_events': 'Olaylar yükleniyor...',
        'last_update': 'Son Güncelleme',
        'status_active': 'Aktif',

        // Map Modes
        'mode_both': '✨ Karma Mod',
        'mode_pins': '📍 Pinler',
        'mode_heatmap': '🔥 Isı Haritası (Heatmap)',

        // Event Types
        'type_vehicle_movement': 'Araç Hareketi',
        'type_sensor_alert': 'Sensör Uyarısı',
        'type_delivery_completed': 'Teslimat Tamamlandı',
        'type_temperature_spike': 'Sıcaklık Artışı',
        'type_security_incident': 'Güvenlik Olayı',
        'type_location_update': 'Konum Güncelleme',
        'type_default': 'Olay',

        // Event Modal
        'modal_title': 'Olay Detayı',
        'modal_source_id': 'Kaynak ID',
        'modal_time': 'Kayıt Zamanı',
        'modal_lat': 'Enlem (Latitude)',
        'modal_lon': 'Boylam (Longitude)',
        'modal_payload': 'JSON Payload Verisi',
        'modal_copy': '📋 Kopyala',
        'modal_focus': '🎯 Haritada Odaklan',
        'modal_close': 'Kapat',
        'modal_view_details': 'Detayları Görüntüle',

        // Toasts
        'toast_payload_copied': 'Payload panoya kopyalandı!',
        'toast_filters_cleared': 'Filtreler temizlendi',
        'toast_sim_success': 'yeni olay üretildi ve işlendi!',
        'toast_auto_sim_start': 'Canlı olay akışı simülasyonu başlatıldı!',
        'toast_auto_sim_stop': 'Otomatik simülasyon durduruldu',
        'toast_sound_on': 'Olay ses bildirimleri aktif edildi 🔔',
        'toast_sound_off': 'Olay ses bildirimleri kapatıldı 🔕',

        // Stats Page
        'stats_title': 'Sistem Analitiği & Raporlar',
        'stats_subtitle': 'Gerçek zamanlı olay akış hacimleri ve kaynak performans metrikleri',
        'stats_auto_refresh': 'Otomatik Canlı Güncelleme (10sn)',
        'stat_total_events': 'Toplam Kayıtlı Olay',
        'stat_active_sources_label': 'Aktif Kaynak',
        'stat_5min': 'Son 5 Dakika',
        'stat_5min_desc': 'Anlık olay frekansı',
        'stat_1hour': 'Son 1 Saat',
        'stat_1hour_desc': 'Saatlik akış yoğunluğu',
        'stat_24hours': 'Son 24 Saat',
        'stat_24hours_desc': 'Günlük toplam hacim',
        'chart_trend_title': '24 Saatlik Olay Dağılım Trendi',
        'chart_trend_subtitle': 'Saatlik periyotlarda işlenen toplam konumlu olay miktarı',
        'chart_trend_badge': 'Canlı Zaman Serisi',
        'chart_type_title': 'Olay Türleri Dağılımı',
        'chart_type_subtitle': 'Kategorilere göre gelen olayların yüzdesel oranı',
        'chart_sources_title': 'En Aktif Kaynaklar (Top Sources)',
        'chart_sources_subtitle': 'Sisteme en çok veri gönderen API istemcileri',
        'table_col_source': 'Kaynak Adı / ID',
        'table_col_count': 'Olay Sayısı',
        'table_col_ratio': 'Oran',

        // Sources Page
        'sources_title': 'Kaynak & API Anahtarı Yönetimi',
        'sources_subtitle': 'Dış sistemlerin Ingest API\'ye veri gönderebilmesi için API Secret Key üretin ve yönetin.',
        'btn_add_source': '➕ Yeni Kaynak Ekle',
        'th_source_info': 'Kaynak Bilgisi',
        'th_secret_key': 'API Secret Key (X-Source-Key)',
        'th_status': 'Durum',
        'th_total_events': 'Toplam Olay',
        'th_actions': 'İşlemler',
        'status_enabled': 'Aktif',
        'status_disabled': 'Pasif',
        'btn_pause': 'Durdur',
        'btn_activate': 'Aktifleştir',
        'btn_delete': 'Sil',
        'modal_add_title': 'Yeni API Kaynağı Tanımla',
        'form_name_label': 'Kaynak Adı / Açıklama *',
        'form_name_placeholder': 'Örn: Araç Takip Filosu, IoT Sensör Ağı',
        'form_key_label': 'Özel API Secret Key (Opsiyonel)',
        'form_key_placeholder': 'Boş bırakılırsa güvenli rastgele anahtar üretilir',
        'form_cancel': 'İptal',
        'form_submit': 'Oluştur',

        // Login Page
        'login_subtitle': 'Realtime Spatial Event Engine Yönetici Girişi',
        'login_username': 'Kullanıcı Adı',
        'login_password': 'Şifre',
        'login_submit': 'Giriş Yap',
        'login_hint_title': 'Varsayılan Giriş Bilgileri:',
        'login_hint_user': 'Kullanıcı:',
        'login_hint_pass': 'Şifre:'
    },
    en: {
        // Brand & Nav
        'brand_title': 'Event Grid',
        'brand_subtitle': 'Realtime Spatial Engine',
        'nav_map': 'Live Map',
        'nav_analytics': 'Analytics',
        'nav_sources': 'Sources',
        'nav_api_docs': 'API Docs',
        'nav_public_map': 'Public Map',
        'nav_login': 'Admin Login',
        'nav_logout': 'Logout',
        'btn_refresh': 'Refresh',

        // Connection
        'conn_sse': 'SSE Live',
        'conn_polling': 'Polling (3s)',
        'conn_offline': 'Disconnected',

        // Simulator
        'sim_1': 'Simulate 1',
        'sim_5': '+5 Simulate',
        'sim_auto_start': '▶️ Auto Stream',
        'sim_auto_stop': '⏹️ Stop Stream',

        // Sidebar & Filters
        'sidebar_title': 'Live Event Feed',
        'search_placeholder': 'Search event ID, type or payload...',
        'filter_all_types': 'All Event Types',
        'filter_all_sources': 'All Sources',
        'filter_bounds': 'In Visible Area',
        'filter_reset': 'Reset',
        'no_events_found': 'No matching events found',
        'no_events_desc': 'Clear filter criteria or simulate new events.',
        'loading_events': 'Loading events...',
        'last_update': 'Last Update',
        'status_active': 'Active',

        // Map Modes
        'mode_both': '✨ Combined',
        'mode_pins': '📍 Pins',
        'mode_heatmap': '🔥 Heatmap',

        // Event Types
        'type_vehicle_movement': 'Vehicle Movement',
        'type_sensor_alert': 'Sensor Alert',
        'type_delivery_completed': 'Delivery Completed',
        'type_temperature_spike': 'Temperature Spike',
        'type_security_incident': 'Security Incident',
        'type_location_update': 'Location Update',
        'type_default': 'Event',

        // Event Modal
        'modal_title': 'Event Details',
        'modal_source_id': 'Source ID',
        'modal_time': 'Timestamp',
        'modal_lat': 'Latitude',
        'modal_lon': 'Longitude',
        'modal_payload': 'JSON Payload',
        'modal_copy': '📋 Copy',
        'modal_focus': '🎯 Focus on Map',
        'modal_close': 'Close',
        'modal_view_details': 'View Details',

        // Toasts
        'toast_payload_copied': 'Payload copied to clipboard!',
        'toast_filters_cleared': 'Filters cleared',
        'toast_sim_success': 'new events generated and ingested!',
        'toast_auto_sim_start': 'Live stream simulation started!',
        'toast_auto_sim_stop': 'Auto simulation stopped',
        'toast_sound_on': 'Audio notifications enabled 🔔',
        'toast_sound_off': 'Audio notifications disabled 🔕',

        // Stats Page
        'stats_title': 'System Analytics & Reports',
        'stats_subtitle': 'Real-time event stream volume and source performance metrics',
        'stats_auto_refresh': 'Auto Live Update (10s)',
        'stat_total_events': 'Total Ingested Events',
        'stat_active_sources_label': 'Active Sources',
        'stat_5min': 'Last 5 Minutes',
        'stat_5min_desc': 'Instant event frequency',
        'stat_1hour': 'Last 1 Hour',
        'stat_1hour_desc': 'Hourly stream density',
        'stat_24hours': 'Last 24 Hours',
        'stat_24hours_desc': 'Daily total volume',
        'chart_trend_title': '24-Hour Event Distribution Trend',
        'chart_trend_subtitle': 'Total spatial events processed in hourly intervals',
        'chart_trend_badge': 'Live Time Series',
        'chart_type_title': 'Event Type Distribution',
        'chart_type_subtitle': 'Percentage breakdown by category',
        'chart_sources_title': 'Top Active Sources',
        'chart_sources_subtitle': 'Top API clients transmitting events',
        'table_col_source': 'Source Name / ID',
        'table_col_count': 'Event Count',
        'table_col_ratio': 'Ratio',

        // Sources Page
        'sources_title': 'Source & API Key Management',
        'sources_subtitle': 'Generate and manage API Secret Keys for external ingest clients.',
        'btn_add_source': '➕ Add New Source',
        'th_source_info': 'Source Information',
        'th_secret_key': 'API Secret Key (X-Source-Key)',
        'th_status': 'Status',
        'th_total_events': 'Total Events',
        'th_actions': 'Actions',
        'status_enabled': 'Active',
        'status_disabled': 'Inactive',
        'btn_pause': 'Pause',
        'btn_activate': 'Activate',
        'btn_delete': 'Delete',
        'modal_add_title': 'Create API Source',
        'form_name_label': 'Source Name / Label *',
        'form_name_placeholder': 'e.g. Fleet Tracker, IoT Sensor Network',
        'form_key_label': 'Custom API Secret Key (Optional)',
        'form_key_placeholder': 'Leave empty for secure random key',
        'form_cancel': 'Cancel',
        'form_submit': 'Create',

        // Login Page
        'login_subtitle': 'Realtime Spatial Event Engine Admin Login',
        'login_username': 'Username',
        'login_password': 'Password',
        'login_submit': 'Sign In',
        'login_hint_title': 'Default Credentials:',
        'login_hint_user': 'User:',
        'login_hint_pass': 'Password:'
    }
};

let currentLang = localStorage.getItem('rteg_lang') || 'tr';

function getLang() {
    return currentLang;
}

function t(key) {
    return translations[currentLang]?.[key] || translations['tr']?.[key] || key;
}

function setLanguage(lang) {
    if (!translations[lang]) return;
    currentLang = lang;
    localStorage.setItem('rteg_lang', lang);
    applyTranslations();
    
    // Dispatch custom event for dynamic components (charts, lists, etc.)
    window.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang } }));
}

function toggleLanguage() {
    setLanguage(currentLang === 'tr' ? 'en' : 'tr');
}

function applyTranslations() {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[currentLang]?.[key]) {
            el.textContent = translations[currentLang][key];
        }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (translations[currentLang]?.[key]) {
            el.setAttribute('placeholder', translations[currentLang][key]);
        }
    });

    // Update active lang indicator button text
    const langIndicator = document.getElementById('lang-indicator');
    if (langIndicator) {
        langIndicator.textContent = currentLang === 'tr' ? '🇹🇷 TR' : '🇬🇧 EN';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    applyTranslations();
});

window.i18n = {
    t,
    getLang,
    setLanguage,
    toggleLanguage,
    applyTranslations
};
