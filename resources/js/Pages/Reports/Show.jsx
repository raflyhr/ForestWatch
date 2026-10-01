import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const statusLabels = { submitted: 'Menunggu ditinjau', under_review: 'Sedang ditinjau', valid: 'Terverifikasi', invalid: 'Tidak valid' };
const reportLabels = { fire: 'Api', smoke: 'Asap', smoke_fire: 'Api dan asap' };

export default function Show({ report }) {
    return <AuthenticatedLayout header={<span>Detail Laporan Warga</span>}><Head title={`Laporan #${report.id}`} /><div className="report-detail-page"><Link href="/reports" className="report-back-link">← Kembali ke laporan warga</Link><header className="report-detail-header"><div><p className="dashboard-kicker">COMMUNITY REPORT #{report.id}</p><h1>{reportLabels[report.report_type] ?? 'Laporan insiden'}</h1><p>Dikirim oleh pelapor pada {new Date(report.created_at).toLocaleString('id-ID')}</p></div><span className={`report-status status-${report.status}`}>{statusLabels[report.status] ?? report.status}</span></header><div className="report-detail-grid"><section className="report-detail-card"><h2>Isi laporan</h2><div className="report-detail-description">{report.description || 'Pelapor tidak menambahkan deskripsi.'}</div>{report.photo_url && <img className="report-detail-photo" src={report.photo_url} alt="Foto bukti laporan warga" />}</section><section className="report-detail-card"><h2>Lokasi kejadian</h2><div className="report-coordinate"><span>LATITUDE</span><strong>{report.latitude}</strong></div><div className="report-coordinate"><span>LONGITUDE</span><strong>{report.longitude}</strong></div>{report.incident && <Link href={`/incidents/${report.incident.id}`} className="report-detail-button">Buka insiden terkait</Link>}</section></div></div></AuthenticatedLayout>;
}
