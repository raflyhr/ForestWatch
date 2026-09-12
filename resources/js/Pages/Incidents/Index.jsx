import { Head, Link } from '@inertiajs/react';

export default function Index({ incidents }) {
    return <div className="min-h-screen bg-slate-100 p-6"><Head title="Incidents" /><main className="mx-auto max-w-5xl"><h1 className="text-2xl font-bold">Incidents</h1><div className="mt-4 space-y-3">{incidents.length ? incidents.map((incident) => <Link key={incident.id} href={`/incidents/${incident.id}`} className="block rounded bg-white p-4 shadow"><b>Incident #{incident.id}</b><span className="ml-4">{incident.status}</span><span className="ml-4">{incident.warning_level ?? 'No warning'}</span></Link>) : <p className="rounded bg-white p-4">No incidents.</p>}</div></main></div>;
}
