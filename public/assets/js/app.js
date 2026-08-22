// ==========================================
// REALTIME MAP EVENT GRID - CORE APPLICATION
// ==========================================

let map, heatLayer, markerLayer;
let events = [];
let markers = {};
let lastEventId = null;
let viewMode = 'both'; // 'pins', 'heatmap', 'both'
let connectionMode = 'connecting'; // 'sse', 'polling', 'offline'
let eventSource = null;
let pollingInterval = null;
let autoSimInterval = null;
let soundEnabled = false;

// Audio context for sound alert
let audioCtx = null;
function playNotificationSound() {
    if (!soundEnabled) return;
    try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.2);
    } catch(e) {}
}

// Event type color & icon mapping
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
            gradient: {
                0.2: '#06b6d4',
                0.4: '#3b82f6',
                0.6: '#10b981',
                0.8: '#f59e0b',
                1.0: '#ef4444'
            }
        }).addTo(map);
    }

    map.on('moveend', () => {
        const boundsCheckbox = document.getElementById('filter-bounds');
        if (boundsCheckbox && boundsCheckbox.checked) {
            applyClientFilters();
        }
    });
}

function setConnectionStatus(status) {
    connectionMode = status;
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
        label.textContent = window.i18n ? window.i18n.t('conn_polling') : 'Polling (3sn)';
        label.className = 'text-xs font-semibold text-amber-400';
    } else {
        dot.className = 'offline-dot';
        label.textContent = window.i18n ? window.i18n.t('conn_offline') : 'Bağlantı Kesildi';
        label.className = 'text-xs font-semibold text-rose-400';
    }
}

function startSSE() {
    if (eventSource) eventSource.close();
    const url = `/api/v1/events/stream.php${lastEventId ? `?last_event_id=${lastEventId}` : ''}`;
    try {
        eventSource = new EventSource(url);
        eventSource.addEventListener('connected', () => {
            setConnectionStatus('sse');
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        });

        eventSource.addEventListener('event', (e) => {
            try {
                const eventData = JSON.parse(e.data);
                handleIncomingEvent(eventData, true);
            } catch (err) {}
        });

        eventSource.addEventListener('reconnect', () => {
            eventSource.close();
            setTimeout(startSSE, 500);
        });

        eventSource.addEventListener('ping', () => {
            const timeEl = document.getElementById('last-update-time');
            if (timeEl) timeEl.textContent = new Date().toLocaleTimeString();
        });

        eventSource.onerror = () => {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            startPollingFallback();
        };
    } catch (e) {
        startPollingFallback();
    }
}

function startPollingFallback() {
    setConnectionStatus('polling');
    if (!pollingInterval) {
        pollingInterval = setInterval(() => {
            fetchLatestEvents(true);
        }, 3000);
    }
    setTimeout(() => {
        if (connectionMode === 'polling' && !eventSource) {
            startSSE();
        }
    }, 15000);
}

async function fetchInitialEvents() {
    try {
        const res = await fetch('/api/v1/events.php?limit=100');
        const json = await res.json();
        if (json.status === 'success' && json.data) {
            events = json.data;
            if (events.length > 0) {
                lastEventId = events[0].event_id;
                events.forEach(evt => addEventToMap(evt, false));
                rebuildHeatmap();
                renderEventList();
                updateFilterCounters();

                if (Object.keys(markers).length > 0) {
                    const group = new L.featureGroup(Object.values(markers));
                    map.fitBounds(group.getBounds().pad(0.1));
                }
            }
        }
    } catch (e) {}
}

async function fetchLatestEvents(isPolling = false) {
    try {
        let url = '/api/v1/events.php?limit=50';
        if (lastEventId) url += `&after_id=${lastEventId}`;

        const res = await fetch(url);
        const json = await res.json();
        if (json.status === 'success' && json.data && json.data.length > 0) {
            json.data.reverse().forEach(evt => handleIncomingEvent(evt, isPolling));
        }
        const timeEl = document.getElementById('last-update-time');
        if (timeEl) timeEl.textContent = new Date().toLocaleTimeString();
    } catch (e) {}
}

function handleIncomingEvent(evt, isLive = true) {
    if (markers[evt.event_id]) return;

    events.unshift(evt);
    if (events.length > 500) events.pop();

    lastEventId = evt.event_id;
    addEventToMap(evt, isLive);
    rebuildHeatmap();
    renderEventList();
    updateFilterCounters();

    if (isLive) {
        playNotificationSound();
        showToastNotification(evt);
    }

    const timeEl = document.getElementById('last-update-time');
    if (timeEl) timeEl.textContent = new Date().toLocaleTimeString();
}

function addEventToMap(evt, isNew = false) {
    if (markers[evt.event_id]) return;

    const cfg = getEventConfig(evt.type);
    const marker = L.marker([evt.lat, evt.lon], {
        icon: createCustomMarkerIcon(evt.type)
    });

    const popupHtml = `
        <div class="p-2 min-w-[220px]">
            <div class="flex items-center justify-between border-b border-gray-700/60 pb-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${cfg.badge}">
                    ${cfg.icon} ${cfg.label}
                </span>
                <span class="text-xs text-gray-400">${new Date(evt.timestamp * 1000).toLocaleTimeString()}</span>
            </div>
            <div class="text-xs text-gray-300 space-y-1">
                <div><span class="text-gray-500">ID:</span> <span class="font-mono text-gray-200">${evt.event_id}</span></div>
                <div><span class="text-gray-500">Source:</span> <span class="text-indigo-400 font-medium">${evt.source_id}</span></div>
                <div><span class="text-gray-500">Lat/Lon:</span> ${evt.lat.toFixed(4)}, ${evt.lon.toFixed(4)}</div>
            </div>
            <button onclick="showEventDetail('${evt.event_id}')" class="mt-3 w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold py-1.5 px-3 rounded-lg transition-colors shadow">
                ${window.i18n ? window.i18n.t('modal_view_details') : 'Detayları Görüntüle'}
            </button>
        </div>
    `;

    marker.bindPopup(popupHtml);
    marker.on('click', () => marker.openPopup());

    if (viewMode === 'pins' || viewMode === 'both') {
        marker.addTo(markerLayer);
    }

    markers[evt.event_id] = marker;
}

function rebuildHeatmap() {
    if (!heatLayer) return;
    const heatData = events.map(e => [e.lat, e.lon, 0.8]);
    heatLayer.setLatLngs(heatData);
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
    document.getElementById('event-count-badge').textContent = `${filtered.length} / ${events.length}`;

    if (filtered.length === 0) {
        listEl.innerHTML = `
            <div class="p-8 text-center text-gray-500 flex flex-col items-center justify-center space-y-2">
                <span class="text-3xl">🔍</span>
                <p class="text-sm font-medium">${window.i18n ? window.i18n.t('no_events_found') : 'Eşleşen olay bulunamadı'}</p>
                <p class="text-xs text-gray-600">${window.i18n ? window.i18n.t('no_events_desc') : 'Filtre kriterlerini temizleyin veya yeni olay simüle edin.'}</p>
            </div>
        `;
        return;
    }

    listEl.innerHTML = filtered.map((evt, idx) => {
        const cfg = getEventConfig(evt.type);
        const isNewFlash = idx === 0 && (Date.now() - evt.timestamp * 1000 < 5000) ? 'event-flash' : '';
        return `
            <div onclick="focusEvent('${evt.event_id}')" class="p-3.5 border-b border-gray-800/80 hover:bg-gray-800/40 cursor-pointer transition-all ${isNewFlash} group">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">${cfg.icon}</span>
                        <span class="font-semibold text-gray-200 text-xs group-hover:text-indigo-400 transition-colors">${cfg.label}</span>
                    </div>
                    <span class="text-[10px] text-gray-400 font-mono">${new Date(evt.timestamp * 1000).toLocaleTimeString()}</span>
                </div>
                <div class="flex items-center justify-between mt-2 text-[11px] text-gray-400">
                    <span class="font-mono px-1.5 py-0.5 rounded bg-gray-800/80 text-gray-300 text-[10px] border border-gray-700/50">${evt.source_id}</span>
                    <span class="text-gray-500 font-mono">${evt.lat.toFixed(3)}, ${evt.lon.toFixed(3)}</span>
                </div>
            </div>
        `;
    }).join('');
}

function getFilteredEvents() {
    const typeFilter = document.getElementById('filter-type')?.value || '';
    const sourceFilter = document.getElementById('filter-source')?.value || '';
    const searchFilter = (document.getElementById('filter-search')?.value || '').toLowerCase().trim();
    const boundsFilter = document.getElementById('filter-bounds')?.checked || false;

    const bounds = map ? map.getBounds() : null;

    return events.filter(evt => {
        if (typeFilter && evt.type !== typeFilter) return false;
        if (sourceFilter && evt.source_id !== sourceFilter) return false;
        if (searchFilter) {
            const payloadStr = JSON.stringify(evt.payload || '').toLowerCase();
            const idStr = (evt.event_id || '').toLowerCase();
            const typeStr = (evt.type || '').toLowerCase();
            if (!payloadStr.includes(searchFilter) && !idStr.includes(searchFilter) && !typeStr.includes(searchFilter)) {
                return false;
            }
        }
        if (boundsFilter && bounds) {
            if (!bounds.contains([evt.lat, evt.lon])) return false;
        }
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

    if (heatLayer) {
        heatLayer.setLatLngs(filtered.map(e => [e.lat, e.lon, 0.8]));
    }

    renderEventList();
}

function updateFilterCounters() {
    const typeSelect = document.getElementById('filter-type');
    const sourceSelect = document.getElementById('filter-source');
    if (!typeSelect || !sourceSelect) return;

    const curType = typeSelect.value;
    const curSource = sourceSelect.value;

    const types = {};
    const sources = {};

    events.forEach(e => {
        types[e.type] = (types[e.type] || 0) + 1;
        sources[e.source_id] = (sources[e.source_id] || 0) + 1;
    });

    typeSelect.innerHTML = `<option value="">${window.i18n ? window.i18n.t('filter_all_types') : 'Tüm Olay Türleri'}</option>`;
    Object.keys(types).forEach(t => {
        const cfg = getEventConfig(t);
        const opt = document.createElement('option');
        opt.value = t;
        opt.textContent = `${cfg.icon} ${cfg.label} (${types[t]})`;
        typeSelect.appendChild(opt);
    });
    typeSelect.value = curType;

    sourceSelect.innerHTML = `<option value="">${window.i18n ? window.i18n.t('filter_all_sources') : 'Tüm Kaynaklar'}</option>`;
    Object.keys(sources).forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        opt.textContent = `${s} (${sources[s]})`;
        sourceSelect.appendChild(opt);
    });
    sourceSelect.value = curSource;
}

function focusEvent(eventId) {
    const evt = events.find(e => e.event_id === eventId);
    if (!evt) return;

    map.flyTo([evt.lat, evt.lon], 14, { duration: 1 });
    if (markers[eventId]) {
        setTimeout(() => {
            markers[eventId].openPopup();
        }, 1000);
    }
}

function showEventDetail(eventId) {
    const evt = events.find(e => e.event_id === eventId);
    if (!evt) return;

    const cfg = getEventConfig(evt.type);
    const modal = document.getElementById('event-modal');
    const title = document.getElementById('modal-title');
    const content = document.getElementById('modal-content');

    title.innerHTML = `
        <div class="flex items-center space-x-2">
            <span class="text-xl">${cfg.icon}</span>
            <span>${cfg.label}</span>
            <span class="text-xs font-mono text-gray-400 font-normal ml-2">#${evt.event_id}</span>
        </div>
    `;

    const jsonFormatted = syntaxHighlight(evt.payload || {});

    content.innerHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-lg bg-gray-800/80 border border-gray-700/50">
                    <span class="text-gray-400 block text-[10px] uppercase font-bold">${window.i18n ? window.i18n.t('modal_source_id') : 'Kaynak ID'}</span>
                    <span class="font-mono text-indigo-400 text-sm font-semibold">${evt.source_id}</span>
                </div>
                <div class="p-3 rounded-lg bg-gray-800/80 border border-gray-700/50">
                    <span class="text-gray-400 block text-[10px] uppercase font-bold">${window.i18n ? window.i18n.t('modal_time') : 'Kayıt Zamanı'}</span>
                    <span class="text-gray-200 text-sm">${new Date(evt.timestamp * 1000).toLocaleString()}</span>
                </div>
                <div class="p-3 rounded-lg bg-gray-800/80 border border-gray-700/50">
                    <span class="text-gray-400 block text-[10px] uppercase font-bold">${window.i18n ? window.i18n.t('modal_lat') : 'Enlem'}</span>
                    <span class="font-mono text-gray-200 text-sm">${evt.lat}</span>
                </div>
                <div class="p-3 rounded-lg bg-gray-800/80 border border-gray-700/50">
                    <span class="text-gray-400 block text-[10px] uppercase font-bold">${window.i18n ? window.i18n.t('modal_lon') : 'Boylam'}</span>
                    <span class="font-mono text-gray-200 text-sm">${evt.lon}</span>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-bold uppercase text-gray-400">${window.i18n ? window.i18n.t('modal_payload') : 'JSON Payload Verisi'}</span>
                    <button onclick="copyPayload('${evt.event_id}')" class="text-[11px] text-indigo-400 hover:text-indigo-300 font-medium flex items-center space-x-1">
                        <span>${window.i18n ? window.i18n.t('modal_copy') : '📋 Kopyala'}</span>
                    </button>
                </div>
                <pre class="bg-gray-950 p-3.5 rounded-xl border border-gray-800 text-xs overflow-x-auto font-mono text-gray-300">${jsonFormatted}</pre>
            </div>

            <div class="flex space-x-2 pt-2">
                <button onclick="focusEvent('${evt.event_id}'); closeModal();" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl text-xs transition-colors flex items-center justify-center space-x-1 shadow">
                    <span>${window.i18n ? window.i18n.t('modal_focus') : '🎯 Haritada Odaklan'}</span>
                </button>
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-xl text-xs transition-colors">
                    ${window.i18n ? window.i18n.t('modal_close') : 'Kapat'}
                </button>
            </div>
        </div>
    `;

    modal.classList.remove('hidden');
}

function closeModal() {
    const modal = document.getElementById('event-modal');
    if (modal) modal.classList.add('hidden');
}

function syntaxHighlight(json) {
    if (typeof json !== 'string') {
        json = JSON.stringify(json, undefined, 2);
    }
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        let cls = 'json-number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'json-key';
            } else {
                cls = 'json-string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'json-boolean';
        } else if (/null/.test(match)) {
            cls = 'json-null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

function copyPayload(eventId) {
    const evt = events.find(e => e.event_id === eventId);
    if (!evt) return;
    navigator.clipboard.writeText(JSON.stringify(evt.payload || {}, null, 2)).then(() => {
        showToast(window.i18n ? window.i18n.t('toast_payload_copied') : 'Payload panoya kopyalandı!', 'success');
    });
}

function showToastNotification(evt) {
    const cfg = getEventConfig(evt.type);
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'glass-panel p-3 rounded-xl border border-indigo-500/40 shadow-2xl flex items-center space-x-3 transform transition-all duration-300 translate-y-2 opacity-0 cursor-pointer max-w-sm';
    toast.innerHTML = `
        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm ${cfg.badge}">
            ${cfg.icon}
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-gray-100 truncate">${cfg.label}</p>
            <p class="text-[11px] text-gray-400 truncate">${evt.source_id} &bull; ${new Date(evt.timestamp * 1000).toLocaleTimeString()}</p>
        </div>
    `;

    toast.onclick = () => {
        focusEvent(evt.event_id);
        toast.remove();
    };

    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    }, 50);

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

function showToast(msg, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `glass-panel p-3 rounded-xl border ${type === 'success' ? 'border-emerald-500/50 text-emerald-400' : 'border-indigo-500/50 text-indigo-300'} shadow-xl text-xs font-semibold transition-all duration-300`;
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

async function triggerSimulator(count = 1) {
    try {
        const btn = document.getElementById('btn-simulate');
        if (btn) btn.disabled = true;
        
        const res = await fetch(`/api/v1/simulator.php?count=${count}`);
        const json = await res.json();
        if (json.status === 'success') {
            const suffix = window.i18n ? window.i18n.t('toast_sim_success') : 'yeni olay üretildi!';
            showToast(`${json.count} ${suffix}`, 'success');
            if (connectionMode === 'polling') {
                fetchLatestEvents(true);
            }
        }
    } catch (e) {
    } finally {
        const btn = document.getElementById('btn-simulate');
        if (btn) btn.disabled = false;
    }
}

function toggleAutoSimulator() {
    const btn = document.getElementById('btn-auto-sim');
    if (autoSimInterval) {
        clearInterval(autoSimInterval);
        autoSimInterval = null;
        if (btn) {
            btn.classList.remove('bg-rose-600', 'text-white');
            btn.classList.add('bg-gray-800', 'text-gray-300');
            btn.innerHTML = `<span>${window.i18n ? window.i18n.t('sim_auto_start') : '▶️ Otomatik Akış'}</span>`;
        }
        showToast(window.i18n ? window.i18n.t('toast_auto_sim_stop') : 'Otomatik simülasyon durduruldu', 'info');
    } else {
        autoSimInterval = setInterval(() => {
            triggerSimulator(1);
        }, 3500);
        if (btn) {
            btn.classList.remove('bg-gray-800', 'text-gray-300');
            btn.classList.add('bg-rose-600', 'text-white');
            btn.innerHTML = `<span>${window.i18n ? window.i18n.t('sim_auto_stop') : '⏹️ Akışı Durdur'}</span>`;
        }
        showToast(window.i18n ? window.i18n.t('toast_auto_sim_start') : 'Canlı olay akışı simülasyonu başlatıldı!', 'success');
    }
}

function toggleSound() {
    soundEnabled = !soundEnabled;
    const btn = document.getElementById('btn-sound');
    if (!btn) return;
    if (soundEnabled) {
        btn.classList.remove('text-gray-500');
        btn.classList.add('text-indigo-400');
        btn.title = 'Audio ON';
        playNotificationSound();
        showToast(window.i18n ? window.i18n.t('toast_sound_on') : 'Olay ses bildirimleri aktif edildi 🔔', 'info');
    } else {
        btn.classList.remove('text-indigo-400');
        btn.classList.add('text-gray-500');
        btn.title = 'Audio OFF';
        showToast(window.i18n ? window.i18n.t('toast_sound_off') : 'Olay ses bildirimleri kapatıldı 🔕', 'info');
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    initMap();
    await fetchInitialEvents();
    startSSE();

    document.getElementById('filter-type')?.addEventListener('change', applyClientFilters);
    document.getElementById('filter-source')?.addEventListener('change', applyClientFilters);
    document.getElementById('filter-search')?.addEventListener('input', applyClientFilters);
    document.getElementById('filter-bounds')?.addEventListener('change', applyClientFilters);

    document.getElementById('btn-reset-filters')?.addEventListener('click', () => {
        if (document.getElementById('filter-type')) document.getElementById('filter-type').value = '';
        if (document.getElementById('filter-source')) document.getElementById('filter-source').value = '';
        if (document.getElementById('filter-search')) document.getElementById('filter-search').value = '';
        if (document.getElementById('filter-bounds')) document.getElementById('filter-bounds').checked = false;
        applyClientFilters();
        showToast(window.i18n ? window.i18n.t('toast_filters_cleared') : 'Filtreler temizlendi', 'info');
    });

    window.addEventListener('languageChanged', () => {
        renderEventList();
        updateFilterCounters();
        setConnectionStatus(connectionMode);
    });
});

window.showEventDetail = showEventDetail;
window.closeModal = closeModal;
window.focusEvent = focusEvent;
window.copyPayload = copyPayload;
window.setViewMode = setViewMode;
window.triggerSimulator = triggerSimulator;
window.toggleAutoSimulator = toggleAutoSimulator;
window.toggleSound = toggleSound;
