import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const priority = (incident) => incident.warning_level ?? (incident.status === 'response' ? 'high' : 'medium');

export default function Dashboard({ stats, incidents = [] }) {
    const [filter, setFilter] = useState('all');
    const filteredIncidents = useMemo(() => incidents.filter((incident) => filter === 'all' || priority(incident) === filter), [filter, incidents]);
    const cards = [
        ['critical', '⚠', stats.criticalIncidents, 'Insiden Kritis', 'critical'],
        ['high', '⚠', stats.highIncidents, 'Prioritas Tinggi', 'high'],
        ['medium', '⌁', stats.mediumIncidents, 'Sedang Dipantau', 'medium'],
        ['resolved', '✓', stats.resolvedToday, 'Selesai Hari Ini', 'safe'],
    ];

    return <AuthenticatedLayout header={<span>Pusat Kendali Insiden</span>}>
        <Head title="Pusat Kendali Insiden" />
        <div className="home-dashboard">
            <header className="home-header"><div><b>Pusat Kendali Insiden</b><small>Senin, 26 Mei 2025 · Data diperbarui langsung</small></div><div className="home-search">⌕ <span>Cari insiden atau lokasi...</span></div><button className="home-notification">♧</button><span className="home-mini-avatar">AR</span></header>
            <section className="home-welcome"><div><p>● SISTEM PEMANTAUAN AKTIF</p><h1>Selamat pagi, {stats.userName ?? 'Andi'}</h1><span>Berikut kondisi kebakaran hutan dan lahan hari ini.</span></div><a href="/incidents">▧ &nbsp; Buka Peta Monitoring</a></section>
            <section className="home-stats">{cards.map(([key, icon, value, label, tone]) => <div className={`home-stat ${tone}`} key={key}><span className="home-stat-icon">{icon}</span><small>↗ {key === 'critical' ? '2 baru' : key === 'resolved' ? '24%' : '12%'}</small><strong>{value}</strong><p>{label}</p><i>▂▃▅▆▇</i></div>)}</section>
            <section className="home-alert"><span>⚠</span><div><b>PERLU TINDAKAN SEGERA <small>4 MENIT LALU</small></b><p>{stats.pendingReports} laporan perlu diverifikasi oleh petugas lapangan.</p></div><a href="/incidents">Verifikasi Sekarang</a><button>›</button></section>
            <section className="home-content"><div className="incident-panel"><div className="home-panel-title"><div><h2>Insiden Aktif</h2><p>Diurutkan berdasarkan tingkat prioritas</p></div><div className="home-filters">{[['all', 'Semua'], ['critical', 'Kritis'], ['high', 'Tinggi'], ['medium', 'Sedang']].map(([value, label]) => <button className={filter === value ? 'active' : ''} onClick={() => setFilter(value)} key={value}>{label}</button>)}</div></div><div className="incident-table-head"><span>INSIDEN & LOKASI</span><span>PRIORITAS</span><span>SUMBER</span><span>STATUS</span><span>AKSI</span></div>{filteredIncidents.slice(0, 6).map((incident) => <div className="incident-row" key={incident.id}><span><b>⌖ &nbsp; {incident.location_name ?? `Insiden #${incident.id}`}</b><small>{incident.latitude}, {incident.longitude} · {incident.created_at ? 'baru' : 'terpantau'}</small></span><em className={`priority-${priority(incident)}`}>● {priority(incident).toUpperCase()}</em><span>NASA Hotspot</span><span className="incident-status">● {incident.status ?? 'Belum diverifikasi'}</span><a href={`/incidents/${incident.id}`}>›</a></div>)}{!filteredIncidents.length && <div className="incident-empty">Belum ada insiden pada filter ini.</div>}<footer>Menampilkan {Math.min(filteredIncidents.length, 6)} insiden aktif <a href="/incidents">Lihat semua insiden ›</a></footer></div></section>
        </div>
    </AuthenticatedLayout>;
}
