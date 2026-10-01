import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import MonitoringPanel from '@/Components/MonitoringPanel';

export default function Index({ officerCount, incidentCount, stats }) {
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Admin Dashboard</h2>}><Head title="Admin Dashboard" /><div className="dashboard-page"><div className="dashboard-intro"><div><p className="dashboard-kicker">ADMIN OPERATIONS</p><h1>Admin Dashboard</h1><p>Kelola hotspot, sumber air, dan rute respons.</p></div></div><MonitoringPanel stats={stats} adminMode /><div className="grid gap-4 p-6 sm:grid-cols-2"><div className="rounded bg-white p-5 shadow"><div className="text-sm text-gray-500">Officers</div><div className="text-3xl font-bold">{officerCount}</div></div><div className="rounded bg-white p-5 shadow"><div className="text-sm text-gray-500">Incidents</div><div className="text-3xl font-bold">{incidentCount}</div></div></div></div></AuthenticatedLayout>;
}
