import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Index({ officerCount, incidentCount }) {
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Admin Preview</h2>}><Head title="Admin Preview" /><div className="grid gap-4 p-6 sm:grid-cols-2"><div className="rounded bg-white p-5 shadow"><div className="text-sm text-gray-500">Officers</div><div className="text-3xl font-bold">{officerCount}</div></div><div className="rounded bg-white p-5 shadow"><div className="text-sm text-gray-500">Incidents</div><div className="text-3xl font-bold">{incidentCount}</div></div></div></AuthenticatedLayout>;
}
