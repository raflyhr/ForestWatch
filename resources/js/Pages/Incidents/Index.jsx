import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import MonitoringPanel from '@/Components/MonitoringPanel';

export default function Index({ incidents, stats }) {
    return <AuthenticatedLayout header={<span>Live Monitoring</span>}><Head title="Live Monitoring" /><div className="dashboard-page"><div className="dashboard-intro"><div><p className="dashboard-kicker">ENVIRONMENTAL COMMAND CENTER</p><h1>Live Monitoring</h1><p>Pantau area aktif dan status respons insiden secara real-time.</p></div></div><MonitoringPanel stats={stats} /><section className="dashboard-reports"><div className="panel-heading"><div><p>INCIDENT ACTIVITY</p><h2>Daftar insiden</h2></div></div><div className="empty-report">{incidents.length ? incidents.slice(0, 3).map((incident) => <Link key={incident.id} href={`/incidents/${incident.id}`}><b>Incident #{incident.id}</b><small>{incident.status} · {incident.warning_level ?? 'No warning'}</small></Link>) : <div><b>Belum ada insiden</b><small>Data insiden akan muncul di sini.</small></div>}</div></section></div></AuthenticatedLayout>;
}
