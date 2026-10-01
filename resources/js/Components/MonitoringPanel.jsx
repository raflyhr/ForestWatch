import { useEffect, useRef, useState } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { createHotspotPopup, createRoutePopup, isValidHotspot } from '@/hotspots';

function MonitoringMap({ adminMode = false }) {
    const mapRef = useRef(null);
    const mapShellRef = useRef(null);
    const hotspotLayerRef = useRef(null);
    const [hotspotCount, setHotspotCount] = useState(0);
    const [lastUpdated, setLastUpdated] = useState(null);

    useEffect(() => {
        const map = L.map(mapRef.current, { zoomControl: true, attributionControl: true }).setView([-2.25, 113.92], 6);
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' }).addTo(map);
        hotspotLayerRef.current = L.layerGroup().addTo(map);
        const refreshHotspots = async () => {
            try {
                const response = await fetch('/api/hotspots?per_page=100', { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const payload = await response.json();
                const hotspots = payload.data ?? payload;
                hotspotLayerRef.current.clearLayers();
                hotspots.filter(isValidHotspot).forEach((hotspot) => {
                    const marker = L.circleMarker([hotspot.latitude, hotspot.longitude], { radius: 7, color: '#ff5b2f', fillColor: '#ffd894', fillOpacity: 1, weight: 3 });
                    const loadRoute = async (button) => {
                        button.disabled = true;
                        button.textContent = 'Mencari rute...';
                        try {
                            const response = await fetch(`/admin/hotspots/${encodeURIComponent(hotspot.id)}/water-route`, { headers: { Accept: 'application/json' } });
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.message);
                            marker.setPopupContent(createRoutePopup(data));
                        } catch (error) {
                            button.disabled = false;
                            button.textContent = error.message || 'Rute gagal ditemukan';
                        }
                    };
                    marker.bindPopup(createHotspotPopup(hotspot, adminMode ? loadRoute : null)).addTo(hotspotLayerRef.current);
                });
                setHotspotCount(hotspots.length);
                setLastUpdated(new Date());
            } catch {
                // Keep last known hotspots when API temporarily fails.
            }
        };
        refreshHotspots();
        const hotspotTimer = window.setInterval(refreshHotspots, 30000);
        const resizeObserver = new ResizeObserver(() => map.invalidateSize());
        resizeObserver.observe(mapRef.current);
        return () => { window.clearInterval(hotspotTimer); resizeObserver.disconnect(); map.remove(); };
    }, []);

    const toggleFullscreen = () => document.fullscreenElement ? document.exitFullscreen() : mapShellRef.current?.requestFullscreen();

    return <div ref={mapShellRef} className="monitoring-map-wrap"><button className="map-fullscreen-button" type="button" onClick={toggleFullscreen} aria-label="Buka peta layar penuh">⛶ Layar penuh</button><div ref={mapRef} className="dashboard-leaflet-map" aria-label="Peta monitoring satelit ForestWatch" /><div className="hotspot-live-status"><span /> {hotspotCount} hotspot · {lastUpdated ? `update ${lastUpdated.toLocaleTimeString('id-ID')}` : 'memuat data...'}</div></div>;
}

export default function MonitoringPanel({ stats, adminMode = false }) {
    return <div className="dashboard-grid"><section className="dashboard-map-card"><div className="panel-heading"><div><p>LIVE MONITORING</p><h2>Area pemantauan aktif</h2></div><a href="/incidents">Buka map →</a></div><MonitoringMap adminMode={adminMode} /></section><section className="dashboard-alert-card"><div className="panel-heading"><div><p>RESPONSE QUEUE</p><h2>Status respons</h2></div><span className="online-dot">● Online</span></div><div className="alert-row"><span className="alert-icon">!</span><div><b>{stats.pendingReports} laporan menunggu verifikasi</b><small>Perlu ditinjau petugas lapangan</small></div></div><div className="alert-row"><span className="alert-icon green">✓</span><div><b>{stats.activeIncidents} insiden aktif</b><small>Monitoring dan respons berjalan</small></div></div></section></div>;
}
