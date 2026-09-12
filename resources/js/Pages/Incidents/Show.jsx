import { Head, Link } from '@inertiajs/react';

export default function Show({ incident }) {
    return <div className="min-h-screen bg-slate-100 p-6"><Head title={`Incident #${incident.id}`} /><main className="mx-auto max-w-3xl rounded bg-white p-6 shadow"><Link href="/incidents" className="text-blue-600">Back</Link><h1 className="mt-4 text-2xl font-bold">Incident #{incident.id}</h1><dl className="mt-4 grid grid-cols-2 gap-3"><div><dt>Status</dt><dd>{incident.status}</dd></div><div><dt>Warning</dt><dd>{incident.warning_level ?? 'None'}</dd></div><div><dt>Confidence</dt><dd>{incident.confidence ?? 'None'}</dd></div><div><dt>Reports</dt><dd>{incident.reports?.length ?? 0}</dd></div><div><dt>Hotspots</dt><dd>{incident.hotspots?.length ?? 0}</dd></div></dl></main></div>;
}
