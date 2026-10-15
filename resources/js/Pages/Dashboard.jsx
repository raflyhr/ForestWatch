import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { 
    AlertCircle, 
    Zap, 
    CheckCircle2, 
    Search, 
    Bell, 
    Target,
    Activity,
    ChevronRight,
    Map as MapIcon
} from 'lucide-react';

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
        const text = `${incident.id} ${incident.location_name ?? ''} ${incident.latitude} ${incident.longitude} ${incident.status ?? ''}`.toLowerCase();
        return (filter === 'all' || priority(incident) === filter) && (statusFilter === 'all' || incident.status === statusFilter) && text.includes(query.toLowerCase());
    }).sort((a, b) => sort === 'newest' ? new Date(b.created_at) - new Date(a.created_at) : ['critical', 'high', 'medium'].indexOf(priority(a)) - ['critical', 'high', 'medium'].indexOf(priority(b))), [filter, statusFilter, query, sort, incidents]);
    
    const cards = [
        ['critical', AlertCircle, stats.criticalIncidents, 'Insiden Kritis', 'critical'],
        ['high', Zap, stats.highIncidents, 'Prioritas Tinggi', 'high'],
        ['medium', Activity, stats.mediumIncidents, 'Sedang Dipantau', 'medium'],
        ['resolved', CheckCircle2, stats.resolvedToday, 'Selesai Hari Ini', 'safe'],
    ];

    return <AuthenticatedLayout header={<span>Pusat Kendali Insiden</span>}>
        <Head title="Pusat Kendali Insiden" />
        <div className="home-dashboard">
            <header className="home-header">
                <div>
                    <b>Pusat Kendali Insiden</b>
                    <small>{new Date().toLocaleDateString('id-ID', { dateStyle: 'full' })} · Data Real-time</small>
                </div>
                <label className="home-search">
                    <Search size={14} />
                    <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Cari insiden atau lokasi..." />
                </label>
                <div className="home-notice-wrap">
                    <button className="home-notification" onClick={() => setNoticeOpen(!noticeOpen)} aria-label="Buka notifikasi">
                        <Bell size={16} />
                        {stats.pendingReports > 0 && <i>{stats.pendingReports}</i>}
                    </button>
                    {noticeOpen && <div className="home-notice-menu">
                        <b>Notifikasi</b>
                        {stats.pendingReportItems?.length ? stats.pendingReportItems.map((report) => <a href={`/reports/${report.id}`} key={report.id}>Laporan #{report.id}<small>Menunggu verifikasi</small></a>) : <span>Tidak ada laporan baru.</span>}
                    </div>}
                </div>
                <span className="home-mini-avatar">{stats.userName?.slice(0, 2).toUpperCase() ?? 'AR'}</span>
            </header>

            <section className="home-welcome">
                <div>
                    <p><Activity size={10} style={{display: 'inline', marginRight: '6px'}} /> SISTEM PEMANTAUAN AKTIF</p>
                    <h1>Selamat pagi, {stats.userName ?? 'Andi'}</h1>
                    <span>Berikut ringkasan kondisi karhutla hari ini.</span>
                </div>
                <a href="/incidents">
                    <MapIcon size={14} style={{display: 'inline', marginRight: '8px'}} /> Buka Peta Monitoring
                </a>
            </section>

            <section className="home-stats">
                {cards.map(([key, Icon, value, label, tone]) => (
                    <div className={`home-stat ${tone}`} key={key}>
                        <span className="home-stat-icon"><Icon size={18} /></span>
                        <small>Terpantau</small>
                        <strong>{value}</strong>
                        <p>{label}</p>
                    </div>
                ))}
            </section>

            <section className="home-alert">
                <AlertCircle size={20} />
                <div>
                    <b>PERLU VERIFIKASI SEGERA</b>
                    <p>{stats.pendingReports} laporan warga masuk membutuhkan verifikasi lapangan.</p>
                </div>
                <a href="/reports">Daftar Laporan</a>
                <button onClick={() => setNoticeOpen(true)}><ChevronRight size={16} /></button>
            </section>

            <section className="home-content">
                <div className="incident-panel">
                    <div className="home-panel-title">
                        <div>
                            <h2>Insiden Aktif</h2>
                            <p>Manajemen status dan prioritas penanganan insiden.</p>
                        </div>
                        <div className="home-filters">
                            {[['all', 'Semua'], ['critical', 'Kritis'], ['high', 'Tinggi'], ['medium', 'Sedang']].map(([value, label]) => (
                                <button className={filter === value ? 'active' : ''} onClick={() => setFilter(value)} key={value}>{label}</button>
                            ))}
                        </div>
                    </div>
                    <div className="home-table-tools">
                        <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                            <option value="all">Semua status</option>
                            <option value="unverified">Belum diverifikasi</option>
                            <option value="under_verification">Dalam verifikasi</option>
                            <option value="verified_fire">Terverifikasi api</option>
                            <option value="false_alarm">Alarm palsu</option>
                        </select>
                        <select value={sort} onChange={(event) => setSort(event.target.value)}>
                            <option value="priority">Prioritas</option>
                            <option value="newest">Terbaru</option>
                        </select>
                    </div>
                    <div className="incident-table-head">
                        <span>INSIDEN & LOKASI</span>
                        <span>PRIORITAS</span>
                        <span>SUMBER</span>
                        <span>STATUS</span>
                        <span>AKSI</span>
                    </div>
                    {filteredIncidents.slice(0, 6).map((incident) => (
                        <div className="incident-row" key={incident.id}>
                            <span>
                                <b><Target size={12} style={{display: 'inline', marginRight: '8px'}} /> {incident.location_name ?? `Insiden #${incident.id}`}</b>
                                <small>{incident.latitude}, {incident.longitude} · {new Date(incident.created_at).toLocaleTimeString()}</small>
                            </span>
                            <em className={`priority-${priority(incident)}`}>
                                {priority(incident).toUpperCase()}
                            </em>
                            <span>NASA FIRMS</span>
                            <span className="incident-status">{incident.status ?? 'Pending'}</span>
                            <span className="incident-actions">
                                <a href={`/incidents/${incident.id}`}>Detail</a>
                                <button onClick={() => setModerating(incident)}>Moderasi</button>
                            </span>
                        </div>
                    ))}
                    {!filteredIncidents.length && <div className="incident-empty">Tidak ada insiden yang ditemukan.</div>}
                    <footer>
                        Menampilkan {Math.min(filteredIncidents.length, 6)} insiden terbaru
                    </footer>
                </div>
            </section>
            {moderating && (
                <div className="dashboard-modal">
                    <div>
                        <button onClick={() => setModerating(null)} className="dashboard-modal-close">×</button>
                        <h2>Moderasi Insiden #{moderating.id}</h2>
                        <p>Tentukan status validasi berdasarkan laporan lapangan.</p>
                        {[['fire_confirmed', 'Api Terkonfirmasi'], ['smoke_only', 'Hanya Asap'], ['false_alarm', 'Alarm Palsu'], ['unable_to_verify', 'Tidak Terverifikasi']].map(([result, label]) => (
                            <button className="verification-option" key={result} onClick={async () => { 
                                await fetch(`/incidents/${moderating.id}/verify`, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ result }) }); 
                                window.location.reload(); 
                            }}>{label}</button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    </AuthenticatedLayout>;
}
