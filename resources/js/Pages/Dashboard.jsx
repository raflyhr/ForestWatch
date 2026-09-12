import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ stats }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            ['Total Incidents', stats.incidents],
                            ['Active Incidents', stats.activeIncidents],
                            ['Total Reports', stats.reports],
                            ['Pending Reports', stats.pendingReports],
                        ].map(([label, value]) => (
                            <div key={label} className="rounded-lg bg-white p-6 shadow-sm">
                                <p className="text-sm text-gray-500">{label}</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">{value}</p>
                            </div>
                        ))}
                    </div>

                    <div className="mt-6 rounded-lg bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-gray-900">ForestWatch Backend</h3>
                        <p className="mt-2 text-gray-600">
                            Dashboard ringkas untuk memantau incident dan laporan masuk.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-3">
                            <a href="/reports/create" className="rounded bg-emerald-700 px-4 py-2 text-sm font-medium text-white">Submit Report</a>
                            <a href="/incidents" className="rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">View Incidents</a>
                            {stats.isAdmin && <a href="/admin" className="rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Admin Panel</a>}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
