import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const priority = (incident) => incident.warning_level ?? (incident.status === 'response' ? 'high' : 'medium');

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

export default function Dashboard({ stats, incidents = [] }) {
    const [filter, setFilter] = useState('all');
    const [query, setQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [sort, setSort] = useState('priority');
    const [noticeOpen, setNoticeOpen] = useState(false);
    const [moderating, setModerating] = useState(null);
    const filteredIncidents = useMemo(() => incidents.filter((incident) => {
        const text = `${incident.id} ${incident.location_name ?? ''} ${incident.latitude} ${incident.longitude}`.toLowerCase();
        return (filter === 'all' || priority(incident) === filter) && (statusFilter === 'all' || incident.status === statusFilter) && text.includes(query.toLowerCase());
    }).sort((a, b) => sort === 'newest' ? new Date(b.created_at) - new Date(a.created_at) : ['critical', 'high', 'medium'].indexOf(priority(a)) - ['critical', 'high', 'medium'].indexOf(priority(b))), [filter, statusFilter, query, sort, incidents]);
    const cards = [
        ['critical', '⚠', stats.criticalIncidents, 'Insiden Kritis', 'critical'],
        ['high', '⚠', stats.highIncidents, 'Prioritas Tinggi', 'high'],
        ['medium', '⌁', stats.mediumIncidents, 'Sedang Dipantau', 'medium'],
        ['resolved', '✓', stats.resolvedToday, 'Selesai Hari Ini', 'safe'],
    ];

    return <AuthenticatedLayout header={<span>Pusat Kendali Insiden</span>}>
        <Head title="Pusat Kendali Insiden" />
        <div className="home-dashboard">
            <header className="home-header"><div><b>Pusat Kendali Insiden</b><small>{new Date().toLocaleDateString('id-ID', { dateStyle: 'full' })} · Data diperbarui langsung</small></div><label className="home-search">⌕ <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Cari insiden atau lokasi..." /></label><div className="home-notice-wrap"><button className="home-notification" onClick={() => setNoticeOpen(!noticeOpen)} aria-label="Buka notifikasi">♧{stats.pendingReports > 0 && <i>{stats.pendingReports}</i>}</button>{noticeOpen && <div className="home-notice-menu"><b>Notifikasi</b>{stats.pendingReportItems?.length ? stats.pendingReportItems.map((report) => <a href={`/reports/${report.id}`} key={report.id}>Laporan #{report.id}<small>Menunggu verifikasi</small></a>) : <span>Tidak ada laporan baru.</span>}</div>}</div><span className="home-mini-avatar">{stats.userName?.slice(0, 2).toUpperCase() ?? 'AR'}</span></header>
            <section className="home-welcome"><div><p>● SISTEM PEMANTAUAN AKTIF</p><h1>Selamat pagi, {stats.userName ?? 'Andi'}</h1><span>Berikut kondisi kebakaran hutan dan lahan hari ini.</span></div><a href="/incidents">▧ &nbsp; Buka Peta Monitoring</a></section>
            <section className="home-stats">{cards.map(([key, icon, value, label, tone]) => <div className={`home-stat ${tone}`} key={key}><span className="home-stat-icon">{icon}</span><small>↗ {key === 'critical' ? '2 baru' : key === 'resolved' ? '24%' : '12%'}</small><strong>{value}</strong><p>{label}</p><i>▂▃▅▆▇</i></div>)}</section>
            <section className="home-alert"><span>⚠</span><div><b>PERLU TINDAKAN SEGERA <small>SEKARANG</small></b><p>{stats.pendingReports} laporan perlu diverifikasi oleh petugas lapangan.</p></div><a href="/reports">Verifikasi Sekarang</a><button onClick={() => setNoticeOpen(true)}>›</button></section>
            <section className="home-content"><div className="incident-panel"><div className="home-panel-title"><div><h2>Insiden Aktif</h2><p>Gunakan pencarian, prioritas, dan status untuk menyaring insiden.</p></div><div className="home-filters">{[['all', 'Semua'], ['critical', 'Kritis'], ['high', 'Tinggi'], ['medium', 'Sedang']].map(([value, label]) => <button className={filter === value ? 'active' : ''} onClick={() => setFilter(value)} key={value}>{label}</button>)}</div></div><div className="home-table-tools"><select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}><option value="all">Semua status</option><option value="unverified">Belum diverifikasi</option><option value="under_verification">Dalam verifikasi</option><option value="verified_fire">Terverifikasi api</option><option value="false_alarm">Alarm palsu</option></select><select value={sort} onChange={(event) => setSort(event.target.value)}><option value="priority">Prioritas</option><option value="newest">Terbaru</option></select></div><div className="incident-table-head"><span>INSIDEN & LOKASI</span><span>PRIORITAS</span><span>SUMBER</span><span>STATUS</span><span>AKSI</span></div>{filteredIncidents.slice(0, 6).map((incident) => <div className="incident-row" key={incident.id}><span><b>⌖ &nbsp; {incident.location_name ?? `Insiden #${incident.id}`}</b><small>{incident.latitude}, {incident.longitude} · {incident.created_at ? 'baru' : 'terpantau'}</small></span><em className={`priority-${priority(incident)}`}>● {priority(incident).toUpperCase()}</em><span>NASA Hotspot</span><span className="incident-status">● {incident.status ?? 'Belum diverifikasi'}</span><span className="incident-actions"><a href={`/incidents/${incident.id}`}>Detail</a><button onClick={() => setModerating(incident)}>Verifikasi</button></span></div>)}{!filteredIncidents.length && <div className="incident-empty">Belum ada insiden pada filter ini.</div>}<footer>Menampilkan {Math.min(filteredIncidents.length, 6)} insiden aktif <a href="/incidents">Lihat semua insiden ›</a></footer></div></section>
            {moderating && <div className="dashboard-modal"><div><button onClick={() => setModerating(null)} className="dashboard-modal-close">×</button><h2>Verifikasi Insiden #{moderating.id}</h2><p>Pilih hasil pemeriksaan petugas.</p>{[['fire_confirmed', 'Api terkonfirmasi'], ['smoke_only', 'Asap saja'], ['false_alarm', 'Alarm palsu'], ['unable_to_verify', 'Belum dapat diverifikasi']].map(([result, label]) => <button className="verification-option" key={result} onClick={async () => { await fetch(`/incidents/${moderating.id}/verify`, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ result }) }); window.location.reload(); }}>{label}</button>)}</div></div>}
        </div>
    </AuthenticatedLayout>;
}
