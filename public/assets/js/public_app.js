// ==========================================
// REALTIME MAP EVENT GRID - PUBLIC MAP APPLICATION
// ==========================================

let map, heatLayer, markerLayer;
let events = [];
let markers = {};
let lastEventId = null;
let viewMode = 'both';
let eventSource = null;
let pollingInterval = null;

// Replay state
let isReplayMode = false;
let replayIsPlaying = false;
let replaySpeed = 1;
let replayCurrentTime = null;
let replayMinTime = null;
let replayMaxTime = null;
let replayInterval = null;

const eventConfig = {
    'vehicle_movement': { color: '#38bdf8', icon: '🚗', labelKey: 'type_vehicle_movement', badge: 'bg-sky-500/20 text-sky-400 border-sky-500/30' },
    'sensor_alert': { color: '#f59e0b', icon: '⚠️', labelKey: 'type_sensor_alert', badge: 'bg-amber-500/20 text-amber-400 border-amber-500/30' },
    'delivery_completed': { color: '#10b981', icon: '📦', labelKey: 'type_delivery_completed', badge: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
    'temperature_spike': { color: '#ef4444', icon: '🔥', labelKey: 'type_temperature_spike', badge: 'bg-rose-500/20 text-rose-400 border-rose-500/30' },
    'security_incident': { color: '#a855f7', icon: '🚨', labelKey: 'type_security_incident', badge: 'bg-purple-500/20 text-purple-400 border-purple-500/30' },
    'location_update': { color: '#06b6d4', icon: '📍', labelKey: 'type_location_update', badge: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30' },
    'default': { color: '#6366f1', icon: '⚡', labelKey: 'type_default', badge: 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30' }
};

function getEventConfig(type) {
    const raw = eventConfig[type] || eventConfig['default'];
    return {
        ...raw,
        label: window.i18n ? window.i18n.t(raw.labelKey) : (raw.labelKey || 'Olay')
    };
}

function createCustomMarkerIcon(type) {
    const cfg = getEventConfig(type);
    return L.divIcon({
        className: 'custom-leaflet-marker',
        html: `
            <div class="relative flex items-center justify-center w-8 h-8 -translate-x-1/2 -translate-y-1/2">
                <div class="absolute w-8 h-8 rounded-full opacity-40 animate-ping" style="background-color: ${cfg.color};"></div>
                <div class="relative w-7 h-7 rounded-full flex items-center justify-center text-xs shadow-lg border border-white/30 backdrop-blur-sm" style="background-color: ${cfg.color};">
                    <span>${cfg.icon}</span>
                </div>
            </div>
        `,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -16]
    });
}

function initMap() {
    map = L.map('map', {
        zoomControl: false,
        attributionControl: false
    }).setView([41.015, 28.985], 11);

    L.control.zoom({ position: 'topright' }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 20,
        subdomains: 'abcd'
    }).addTo(map);

    markerLayer = L.layerGroup().addTo(map);

    if (typeof L.heatLayer === 'function') {
        heatLayer = L.heatLayer([], {
            radius: 28,
            blur: 20,
            maxZoom: 15,
            gradient: { 0.2: '#06b6d4', 0.4: '#3b82f6', 0.6: '#10b981', 0.8: '#f59e0b', 1.0: '#ef4444' }
        }).addTo(map);
    }
}

function setConnectionStatus(status) {
    const dot = document.getElementById('conn-dot');
    const label = document.getElementById('conn-label');
    if (!dot || !label) return;

    dot.className = '';
    if (status === 'sse') {
        dot.className = 'live-dot';
        label.textContent = window.i18n ? window.i18n.t('conn_sse') : 'SSE Canlı';
        label.className = 'text-xs font-semibold text-emerald-400';
    } else if (status === 'polling') {
        dot.className = 'polling-dot';
        label.textContent = window.i18n ? window.i18n.t('conn_polling') : 'Polling';
        label.className = 'text-xs font-semibold text-amber-400';
    } else {
        dot.className = 'offline-dot';
        label.textContent = window.i18n ? window.i18n.t('conn_offline') : 'Bağlantı Kesildi';
        label.className = 'text-xs font-semibold text-rose-400';
    }
}

function startSSE() {
    if (eventSource) eventSource.close();
    try {
        eventSource = new EventSource('/api/v1/events/stream.php');
        eventSource.addEventListener('connected', () => {
            setConnectionStatus('sse');
            if (pollingInterval) { clearInterval(pollingInterval); pollingInterval = null; }
        });
        eventSource.addEventListener('event', (e) => {
            try {
                const eventData = JSON.parse(e.data);
                handleIncomingEvent(eventData);
            } catch(err) {}
        });
        eventSource.addEventListener('ping', () => {
            const timeEl = document.getElementById('last-update-time');
            if (timeEl) timeEl.textContent = new Date().toLocaleTimeString();
        });
        eventSource.onerror = () => {
            if (eventSource) { eventSource.close(); eventSource = null; }
            startPollingFallback();
        };
    } catch(e) {
        startPollingFallback();
    }
}

function startPollingFallback() {
    setConnectionStatus('polling');
    if (!pollingInterval) {
        pollingInterval = setInterval(() => {
            fetchEvents(true);
        }, 3000);
    }
}

async function fetchEvents(isPolling = false) {
    try {
        let url = '/api/v1/public/events.php?limit=100';
        if (isPolling && lastEventId) url += `&after_id=${lastEventId}`;

        const res = await fetch(url);
        const json = await res.json();
        if (json.status === 'success' && json.data && json.data.length > 0) {
            json.data.reverse().forEach(evt => handleIncomingEvent(evt));
            if (!isPolling && Object.keys(markers).length > 0) {
                const group = new L.featureGroup(Object.values(markers));
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }
        const timeEl = document.getElementById('last-update-time');
        if (timeEl) timeEl.textContent = new Date().toLocaleTimeString();
    } catch(e) {}
}

function handleIncomingEvent(evt) {
    if (markers[evt.event_id]) return;

    events.unshift(evt);
    if (events.length > 300) events.pop();
    lastEventId = evt.event_id;

    addEventToMap(evt);
    if (!isReplayMode) {
        rebuildHeatmap();
        renderEventList();
        updateFilterCounters();
    }
}

function addEventToMap(evt) {
    if (markers[evt.event_id]) return;
    const cfg = getEventConfig(evt.type);
    const marker = L.marker([evt.lat, evt.lon], {
        icon: createCustomMarkerIcon(evt.type)
    });

    const popupHtml = `
        <div class="p-2 min-w-[200px]">
            <div class="flex items-center justify-between border-b border-gray-700 pb-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${cfg.badge}">
                    ${cfg.icon} ${cfg.label}
                </span>
                <span class="text-xs text-gray-400">${new Date(evt.timestamp * 1000).toLocaleTimeString()}</span>
            </div>
            <div class="text-xs text-gray-300 space-y-1">
                <div><span class="text-gray-500">ID:</span> <span class="font-mono text-gray-200">${evt.event_id}</span></div>
                <div><span class="text-gray-500">Source:</span> <span class="text-indigo-400">${evt.source_id}</span></div>
                <div><span class="text-gray-500">Lat/Lon:</span> ${evt.lat.toFixed(4)}, ${evt.lon.toFixed(4)}</div>
            </div>
        </div>
    `;

    marker.bindPopup(popupHtml);
    if (viewMode === 'pins' || viewMode === 'both') {
        marker.addTo(markerLayer);
    }
    markers[evt.event_id] = marker;
}

function rebuildHeatmap() {
    if (!heatLayer) return;
    const active = getFilteredEvents();
    heatLayer.setLatLngs(active.map(e => [e.lat, e.lon, 0.8]));
}

function setViewMode(mode) {
    viewMode = mode;
    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.classList.remove('bg-indigo-600', 'text-white', 'shadow');
        btn.classList.add('text-gray-400', 'hover:text-gray-200');
    });
    const activeBtn = document.getElementById(`mode-${mode}`);
    if (activeBtn) {
        activeBtn.classList.remove('text-gray-400', 'hover:text-gray-200');
        activeBtn.classList.add('bg-indigo-600', 'text-white', 'shadow');
    }

    if (mode === 'heatmap') {
        map.removeLayer(markerLayer);
        if (heatLayer && !map.hasLayer(heatLayer)) map.addLayer(heatLayer);
    } else if (mode === 'pins') {
        if (!map.hasLayer(markerLayer)) map.addLayer(markerLayer);
        if (heatLayer && map.hasLayer(heatLayer)) map.removeLayer(heatLayer);
    } else {
        if (!map.hasLayer(markerLayer)) map.addLayer(markerLayer);
        if (heatLayer && !map.hasLayer(heatLayer)) map.addLayer(heatLayer);
    }
}

function renderEventList() {
    const listEl = document.getElementById('event-list');
    if (!listEl) return;

    const filtered = getFilteredEvents();
    document.getElementById('event-count-badge').textContent = `${filtered.length}`;

    listEl.innerHTML = filtered.map(evt => {
        const cfg = getEventConfig(evt.type);
        return `
            <div onclick="focusEvent('${evt.event_id}')" class="p-3 border-b border-gray-800/80 hover:bg-gray-800/40 cursor-pointer transition-all">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-200 text-xs">${cfg.icon} ${cfg.label}</span>
                    <span class="text-[10px] text-gray-400 font-mono">${new Date(evt.timestamp * 1000).toLocaleTimeString()}</span>
                </div>
                <div class="flex items-center justify-between mt-1 text-[10px] text-gray-500 font-mono">
                    <span>${evt.source_id}</span>
                    <span>${evt.lat.toFixed(3)}, ${evt.lon.toFixed(3)}</span>
                </div>
            </div>
        `;
    }).join('');
}

function getFilteredEvents() {
    const type = document.getElementById('filter-type')?.value || '';
    const search = (document.getElementById('filter-search')?.value || '').toLowerCase().trim();

    return events.filter(e => {
        if (isReplayMode && replayCurrentTime && e.timestamp > replayCurrentTime) return false;
        if (type && e.type !== type) return false;
        if (search && !(e.event_id.toLowerCase().includes(search) || e.type.toLowerCase().includes(search))) return false;
        return true;
    });
}

function applyClientFilters() {
    const filtered = getFilteredEvents();
    markerLayer.clearLayers();
    filtered.forEach(evt => {
        if (markers[evt.event_id] && (viewMode === 'pins' || viewMode === 'both')) {
            markerLayer.addLayer(markers[evt.event_id]);
        }
    });
    if (heatLayer) heatLayer.setLatLngs(filtered.map(e => [e.lat, e.lon, 0.8]));
    renderEventList();
}

function updateFilterCounters() {
    const typeSelect = document.getElementById('filter-type');
    if (!typeSelect) return;
    const cur = typeSelect.value;
    const types = new Set(events.map(e => e.type));
    typeSelect.innerHTML = `<option value="">${window.i18n ? window.i18n.t('filter_all_types') : 'Tüm Olay Türleri'}</option>`;
    types.forEach(t => {
        const cfg = getEventConfig(t);
        const opt = document.createElement('option');
        opt.value = t;
        opt.textContent = `${cfg.icon} ${cfg.label}`;
        typeSelect.appendChild(opt);
    });
    typeSelect.value = cur;
}

// Replay functions
function toggleReplayMode() {
    isReplayMode = !isReplayMode;
    const panel = document.getElementById('replay-panel');
    const toggleBtn = document.getElementById('btn-replay-toggle');

    if (isReplayMode) {
        if (events.length === 0) {
            isReplayMode = false;
            return;
        }

        const timestamps = events.map(e => e.timestamp);
        replayMinTime = Math.min(...timestamps);
        replayMaxTime = Math.max(...timestamps, Math.floor(Date.now() / 1000));
        if (replayMinTime === replayMaxTime) replayMinTime -= 300;

        replayCurrentTime = replayMinTime;

        const slider = document.getElementById('replay-slider');
        if (slider) {
            slider.min = replayMinTime;
            slider.max = replayMaxTime;
            slider.value = replayCurrentTime;
        }

        if (panel) panel.classList.remove('hidden');
        if (toggleBtn) {
            toggleBtn.classList.remove('text-gray-400');
            toggleBtn.classList.add('bg-cyan-600', 'text-white');
        }

        updateReplayDisplay();
        applyClientFilters();
    } else {
        exitReplayMode();
    }
}

function exitReplayMode() {
    isReplayMode = false;
    pauseReplay();
    const panel = document.getElementById('replay-panel');
    const toggleBtn = document.getElementById('btn-replay-toggle');

    if (panel) panel.classList.add('hidden');
    if (toggleBtn) {
        toggleBtn.classList.remove('bg-cyan-600', 'text-white');
        toggleBtn.classList.add('text-gray-400');
    }

    applyClientFilters();
}

function togglePlayReplay() {
    if (replayIsPlaying) pauseReplay();
    else playReplay();
}

function playReplay() {
    if (!isReplayMode) return;
    replayIsPlaying = true;
    const playBtn = document.getElementById('btn-replay-play');
    if (playBtn) playBtn.innerHTML = `<span>${window.i18n ? window.i18n.t('replay_pause') : '⏸️ Duraklat'}</span>`;

    if (replayCurrentTime >= replayMaxTime) replayCurrentTime = replayMinTime;

    const stepSeconds = 15 * replaySpeed;
    if (replayInterval) clearInterval(replayInterval);

    replayInterval = setInterval(() => {
        replayCurrentTime += stepSeconds;
        if (replayCurrentTime >= replayMaxTime) {
            replayCurrentTime = replayMaxTime;
            pauseReplay();
        }

        const slider = document.getElementById('replay-slider');
        if (slider) slider.value = replayCurrentTime;

        updateReplayDisplay();
        applyClientFilters();
    }, 200);
}

function pauseReplay() {
    replayIsPlaying = false;
    if (replayInterval) {
        clearInterval(replayInterval);
        replayInterval = null;
    }
    const playBtn = document.getElementById('btn-replay-play');
    if (playBtn) playBtn.innerHTML = `<span>${window.i18n ? window.i18n.t('replay_play') : '▶️ Oynat'}</span>`;
}

function onReplaySliderChange(value) {
    replayCurrentTime = parseInt(value, 10);
    updateReplayDisplay();
    applyClientFilters();
}

function updateReplayDisplay() {
    const timeDisplay = document.getElementById('replay-current-time');
    const countDisplay = document.getElementById('replay-active-count');
    if (timeDisplay && replayCurrentTime) {
        const d = new Date(replayCurrentTime * 1000);
        timeDisplay.textContent = `${d.toLocaleDateString()} ${d.toLocaleTimeString()}`;
    }
    if (countDisplay) {
        const shown = getFilteredEvents().length;
        countDisplay.textContent = `${shown} / ${events.length} ${window.i18n ? window.i18n.t('replay_events_shown') : 'Olay'}`;
    }
}

function focusEvent(id) {
    const evt = events.find(e => e.event_id === id);
    if (!evt) return;
    map.flyTo([evt.lat, evt.lon], 14);
    if (markers[id]) markers[id].openPopup();
}

document.addEventListener('DOMContentLoaded', async () => {
    initMap();
    await fetchEvents();
    startSSE();
    document.getElementById('filter-type')?.addEventListener('change', applyClientFilters);
    document.getElementById('filter-search')?.addEventListener('input', applyClientFilters);
    window.addEventListener('languageChanged', () => {
        renderEventList();
        updateFilterCounters();
        setConnectionStatus(connectionMode);
        updateReplayDisplay();
    });
});

window.setViewMode = setViewMode;
window.focusEvent = focusEvent;
window.toggleReplayMode = toggleReplayMode;
window.exitReplayMode = exitReplayMode;
window.togglePlayReplay = togglePlayReplay;
window.onReplaySliderChange = onReplaySliderChange;
