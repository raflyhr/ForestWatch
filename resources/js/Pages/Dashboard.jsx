import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

function DashboardMap() {
    const mapRef = useRef(null);

    useEffect(() => {
        const map = L.map(mapRef.current, { zoomControl: true, attributionControl: true }).setView([-2.25, 113.92], 6);
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' }).addTo(map);
        L.polygon([[-1.2, 112.8], [-1.45, 115.4], [-3.2, 115.1], [-3.4, 112.7]], { color: '#46d5db', weight: 2, fillColor: '#0d6a60', fillOpacity: 0.2 }).addTo(map);
        [[-2.05, 113.95], [-1.8, 114.35], [-2.5, 113.55]].forEach((point) => L.circleMarker(point, { radius: 8, color: '#ff5b2f', fillColor: '#ffd894', fillOpacity: 1, weight: 3 }).addTo(map));
        const resizeObserver = new ResizeObserver(() => map.invalidateSize());
        resizeObserver.observe(mapRef.current);
        return () => { resizeObserver.disconnect(); map.remove(); };
    }, []);

    return <div ref={mapRef} className="dashboard-leaflet-map" aria-label="Peta monitoring satelit ForestWatch" />;
}

export default function Dashboard({ stats }) {
    return (
        <AuthenticatedLayout
            header={<span>ForestWatch Operations</span>}
        >
            <Head title="Dashboard" />

            <div className="dashboard-page">
                <div className="dashboard-intro"><div><p className="dashboard-kicker">ENVIRONMENTAL COMMAND CENTER</p><h1>Selamat datang, {stats.userName ?? 'Petugas'}</h1><p>Pantau kondisi hutan, laporan warga, dan respons insiden dari satu ruang kendali.</p></div><a className="dashboard-primary" href="/reports/create">+ Laporan baru</a></div>
                    <div className="dashboard-stats">
                        {[
                            ['Total Incidents', stats.incidents],
                            ['Active Incidents', stats.activeIncidents],
                            ['Total Reports', stats.reports],
                            ['Pending Reports', stats.pendingReports],
                        ].map(([label, value]) => (     
                            <div key={label} className="dashboard-stat"><span className="dashboard-stat-icon">{label.includes('Incident') ? '◉' : '▣'}</span><p>{label}</p><strong>{value}</strong><small>Data terhubung real-time</small>
                            </div>
                        ))}
                    </div>

                    <div className="dashboard-grid"><section className="dashboard-map-card"><div className="panel-heading"><div><p>LIVE MONITORING</p><h2>Area pemantauan aktif</h2></div><a href="/incidents">Buka map →</a></div><DashboardMap /></section><section className="dashboard-alert-card"><div className="panel-heading"><div><p>RESPONSE QUEUE</p><h2>Status respons</h2></div><span className="online-dot">● Online</span></div><div className="alert-row"><span className="alert-icon">!</span><div><b>{stats.pendingReports} laporan menunggu verifikasi</b><small>Perlu ditinjau petugas lapangan</small></div></div><div className="alert-row"><span className="alert-icon green">✓</span><div><b>{stats.activeIncidents} insiden aktif</b><small>Monitoring dan respons berjalan</small></div></div></section></div><section className="dashboard-reports"><div className="panel-heading"><div><p>FIELD ACTIVITY</p><h2>Laporan terbaru</h2></div><a href="/incidents">Lihat semua →</a></div><div className="empty-report"><span>◌</span><div><b>Belum ada laporan terbaru</b><small>Laporan warga akan muncul di sini setelah dikirim.</small></div><a href="/reports/create">Kirim laporan</a></div></section>
            </div>
        </AuthenticatedLayout>
    );
}
