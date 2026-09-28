import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Create({ stats }) {
    const [data, setData] = useState({ report_type: 'fire', latitude: '', longitude: '', phone_number: '', description: '', photo: null });
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});
    const [result, setResult] = useState(null);
    const [locationStatus, setLocationStatus] = useState('Mencari lokasi...');
    const [locationError, setLocationError] = useState(false);
    const [previewUrl, setPreviewUrl] = useState(null);

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

    return <AuthenticatedLayout header={<span>Laporan Warga</span>}><Head title="Laporan Warga" /><div className="report-page">
        <section className="report-hero"><div className="report-hero-content"><Link href="/" className="report-brand">🌲 <span>ForestWatch<small>WILDFIRE MONITORING</small></span></Link><p className="report-eyebrow">COMMUNITY RESPONSE</p><h1>Laporkan Karhutla</h1><p>Bantu kami mendeteksi kebakaran lebih cepat dengan melaporkan kejadian yang Anda temukan.</p><a className="report-incident-button" href="#report-form">Report Incident <b>→</b></a></div></section>
        <main id="report-form" className="report-form-wrap">
            {result && <div className="report-success">Laporan #{result.report_id} berhasil dikirim. Tim ForestWatch akan segera menindaklanjuti.</div>}
            {errors.form && <div className="report-error">{errors.form}</div>}
            <form onSubmit={submit}>
                <section className="report-card"><div className="report-card-title"><span>📍</span><div><h2>Lokasi Kejadian</h2><p>Tandai titik koordinat kejadian</p></div></div><button type="button" onClick={locate} className="report-primary" disabled={locationStatus === 'Meminta izin lokasi...'}>{locationStatus === 'Meminta izin lokasi...' ? 'Mendeteksi Lokasi...' : data.latitude ? '✓ Lokasi Terdeteksi' : '📡 Gunakan Lokasi Saya'}</button>{mapUrl ? <iframe title="Lokasi laporan" className="report-map" src={mapUrl} /> : <div className="report-map-placeholder">📍<span>Tekan tombol di atas untuk mendeteksi lokasi Anda</span></div>}<div className="report-fields"><label>Latitude<input readOnly={!locationError} value={data.latitude} onChange={(e) => update('latitude', e.target.value)} /></label><label>Longitude<input readOnly={!locationError} value={data.longitude} onChange={(e) => update('longitude', e.target.value)} /></label></div>{locationError && <small className="report-hint">GPS gagal. Koordinat dapat diisi manual.</small>}</section>
                <section className="report-card"><div className="report-card-title"><span>🔥</span><div><h2>Jenis Laporan</h2><p>Pilih jenis insiden yang Anda temukan</p></div></div><div className="report-types">{[['fire', '🔥', 'Api', 'Kebakaran aktif'], ['smoke', '🌫️', 'Asap', 'Kepulan asap'], ['smoke_fire', '🔥‍🌫️', 'Api & Asap', 'Keduanya ada']].map(([value, icon, title, sub]) => <button type="button" key={value} className={data.report_type === value ? 'selected' : ''} onClick={() => update('report_type', value)}><b>{icon}</b><strong>{title}</strong><small>{sub}</small>{data.report_type === value && <i>✓</i>}</button>)}</div></section>
                <section className="report-card"><div className="report-card-title"><span>📝</span><div><h2>Deskripsi</h2><p>Jelaskan apa yang Anda lihat</p></div><small>{data.description.length}/500</small></div><textarea maxLength="500" value={data.description} onChange={(e) => update('description', e.target.value)} placeholder="Jelaskan kondisi yang Anda lihat... misalnya lokasi, perkiraan luas, arah angin, dll." />{errors.description && <em>{errors.description[0]}</em>}</section>
                <section className="report-card"><div className="report-card-title"><span>📷</span><div><h2>Foto Bukti <small>(Opsional)</small></h2><p>Foto lapangan membantu verifikasi lebih cepat</p></div></div><label className="report-upload">🖼️<strong>{data.photo?.name ?? 'Unggah Foto Kondisi Lapangan'}</strong><small>JPG · PNG — maks. 10MB</small><input type="file" accept="image/jpeg,image/png" onChange={(e) => { const file = e.target.files?.[0]; update('photo', file); setPreviewUrl(file ? URL.createObjectURL(file) : null); }} /></label>{previewUrl && <img className="report-photo-preview" src={previewUrl} alt="Preview foto laporan" />}</section>
                <section className="report-card"><div className="report-card-title"><span>📱</span><div><h2>Nomor Telepon</h2><p>Untuk verifikasi dan tindak lanjut</p></div></div><input className="report-input" type="tel" placeholder="+62 812 3456 7890" value={data.phone_number} onChange={(e) => update('phone_number', e.target.value)} />{errors.phone_number && <em>{errors.phone_number[0]}</em>}<div className="report-privacy">🔒 Nomor telepon dienkripsi dan hanya digunakan untuk verifikasi laporan.</div></section>
                <div className="report-checklist"><b>Kelengkapan Laporan</b><span className={data.latitude ? 'done' : ''}>✓ Lokasi terdeteksi</span><span className={data.report_type ? 'done' : ''}>✓ Jenis insiden dipilih</span><span className={data.description.length > 5 ? 'done' : ''}>✓ Deskripsi diisi</span><span className={data.phone_number.length >= 9 ? 'done' : ''}>✓ Nomor telepon diisi</span></div>
                <button disabled={processing} className="report-submit">{processing ? 'Mengirim Laporan...' : '🚨 Kirim Laporan'}</button>
            </form>
        </main>
    </div></AuthenticatedLayout>;
}
