import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Create() {
    const [data, setData] = useState({ report_type: 'fire', latitude: '', longitude: '', phone_number: '', description: '', photo: null });
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});
    const [result, setResult] = useState(null);
    const [locationStatus, setLocationStatus] = useState('Mencari lokasi...');
    const [locationError, setLocationError] = useState(false);

    const update = (field, value) => setData((current) => ({ ...current, [field]: value }));

    const locate = () => {
        if (!navigator.geolocation) {
            setLocationStatus('GPS browser tidak tersedia. Isi lokasi manual.');
            setLocationError(true);
            return;
        }

        setLocationStatus('Meminta izin lokasi...');
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                update('latitude', coords.latitude.toFixed(7));
                update('longitude', coords.longitude.toFixed(7));
                setLocationStatus('Lokasi berhasil ditemukan.');
                setLocationError(false);
            },
            () => {
                setLocationStatus('Lokasi tidak ditemukan. Isi latitude dan longitude manual.');
                setLocationError(true);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 },
        );
    };

    useEffect(() => locate(), []);

    const submit = async (event) => {
        event.preventDefault();
        setProcessing(true);
        setErrors({});
        setResult(null);
        const body = new FormData();
        Object.entries(data).forEach(([key, value]) => body.append(key, value ?? ''));

        try {
            const response = await fetch('/api/reports', { method: 'POST', headers: { Accept: 'application/json' }, body });
            const payload = await response.json();
            if (!response.ok) {
                setErrors(payload.errors ?? { form: payload.message ?? 'Report failed.' });
                return;
            }
            setResult(payload);
        } catch {
            setErrors({ form: 'Server tidak dapat dihubungi.' });
        } finally {
            setProcessing(false);
        }
    };

    const mapUrl = data.latitude && data.longitude
        ? `https://www.openstreetmap.org/export/embed.html?bbox=${Number(data.longitude) - 0.01}%2C${Number(data.latitude) - 0.01}%2C${Number(data.longitude) + 0.01}%2C${Number(data.latitude) + 0.01}&layer=mapnik&marker=${data.latitude}%2C${data.longitude}`
        : null;

    return <div className="min-h-screen bg-slate-100 p-6"><Head title="Submit Report" /><div className="mx-auto max-w-xl rounded bg-white p-6 shadow">
        <Link href="/" className="text-sm text-blue-600">ForestWatch</Link><h1 className="mt-4 text-2xl font-bold">Submit Report</h1>
        {result && <div className="mt-4 rounded bg-emerald-50 p-3 text-emerald-800">Report #{result.report_id} linked to incident #{result.incident_id}.<br />Submitted at: {new Date(result.submitted_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })}</div>}
        {errors.form && <div className="mt-4 rounded bg-red-50 p-3 text-red-700">{errors.form}</div>}
        <form onSubmit={submit} className="mt-6 space-y-4">
            <div className="rounded border bg-slate-50 p-3 text-sm"><div>{locationStatus}</div><button type="button" onClick={locate} className="mt-2 rounded border px-3 py-1">Ambil Lokasi Lagi</button></div>
            <div className="grid gap-4 sm:grid-cols-2">{['latitude', 'longitude'].map((field) => <label key={field} className="block text-sm">{field}<input readOnly={!locationError} className="mt-1 w-full rounded border p-2 read-only:bg-slate-100" value={data[field]} onChange={(e) => update(field, e.target.value)} />{errors[field] && <span className="text-red-600">{errors[field][0]}</span>}</label>)}</div>
            {mapUrl && <iframe title="Lokasi laporan" className="h-64 w-full rounded border" src={mapUrl} />}
            {locationError && <p className="text-xs text-slate-600">GPS gagal. Koordinat dapat diisi manual sebagai fallback.</p>}
            <label className="block text-sm">phone_number<input className="mt-1 w-full rounded border p-2" value={data.phone_number} onChange={(e) => update('phone_number', e.target.value)} />{errors.phone_number && <span className="text-red-600">{errors.phone_number[0]}</span>}</label>
            <label className="block text-sm">Type<select className="mt-1 w-full rounded border p-2" value={data.report_type} onChange={(e) => update('report_type', e.target.value)}><option value="smoke">Smoke</option><option value="fire">Fire</option><option value="smoke_fire">Smoke + Fire</option></select></label>
            <label className="block text-sm">Description<textarea className="mt-1 w-full rounded border p-2" value={data.description} onChange={(e) => update('description', e.target.value)} />{errors.description && <span className="text-red-600">{errors.description[0]}</span>}</label>
            <label className="block text-sm">Photo<input type="file" accept="image/jpeg,image/png" className="mt-1 w-full" onChange={(e) => update('photo', e.target.files[0])} />{errors.photo && <span className="text-red-600">{errors.photo[0]}</span>}</label>
            <button disabled={processing} className="rounded bg-emerald-700 px-4 py-2 text-white disabled:opacity-50">{processing ? 'Sending...' : 'Send Report'}</button>
        </form>
    </div></div>;
}
