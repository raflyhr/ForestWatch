import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ incidents }) {
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Officer Queue</h2>}><Head title="Officer Queue" /><div className="p-6">{incidents.map((incident) => <Link key={incident.id} href={`/incidents/${incident.id}`} className="mb-3 block rounded bg-white p-4 shadow">#{incident.id} · {incident.status} · {incident.warning_level ?? 'low'}</Link>)}</div></AuthenticatedLayout>;
}
