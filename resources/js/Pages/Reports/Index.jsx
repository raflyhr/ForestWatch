import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const statusLabels = {
    submitted: 'Menunggu ditinjau',
    under_review: 'Sedang ditinjau',
    valid: 'Terverifikasi',
    invalid: 'Tidak valid',
};

const reportLabels = { fire: 'Api', smoke: 'Asap', smoke_fire: 'Api dan asap' };

export default function Index({ reports }) {
    const moderate = async (id, status) => { await fetch(`/reports/${id}/moderate`, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, body: JSON.stringify({ status }) }); window.location.reload(); };
    return <AuthenticatedLayout header={<span>Laporan Warga</span>}><Head title="Laporan Warga" /><div className="reports-inbox">
        <header className="reports-inbox-header"><div><p className="dashboard-kicker">COMMUNITY REPORTS</p><h1>Hasil Laporan Warga</h1><p>Periksa laporan lapangan yang masuk dan tindak lanjuti insiden terkait.</p></div><div className="reports-count"><strong>{reports.total}</strong><span>Total laporan</span></div></header>
        <section className="reports-list-card"><div className="reports-list-title"><div><h2>Laporan terbaru</h2><p>Data dikirim oleh pelapor di lapangan.</p></div><span>{reports.from ?? 0}-{reports.to ?? 0} dari {reports.total}</span></div>
            <div className="reports-table-head"><span>LAPORAN</span><span>LOKASI</span><span>STATUS</span><span>DITERIMA</span><span>AKSI</span></div>
            {reports.data.map((report) => <article className="report-result-row" key={report.id}><div className="report-result-main"><span className="report-result-icon">{report.report_type === 'smoke' ? '◌' : '🔥'}</span><div><strong>{reportLabels[report.report_type] ?? 'Laporan insiden'}</strong><small>{report.description || 'Tidak ada deskripsi dari pelapor.'}</small></div></div><div className="report-result-location"><strong>{Number(report.latitude).toFixed(4)}, {Number(report.longitude).toFixed(4)}</strong><small>{report.incident ? `Insiden #${report.incident.id}` : 'Belum terhubung insiden'}</small></div><span className={`report-status status-${report.status}`}>{statusLabels[report.status] ?? report.status}</span><time dateTime={report.created_at}>{new Date(report.created_at).toLocaleString('id-ID')}</time><span className="report-result-actions"><Link href={`/reports/${report.id}`} className="report-result-action">Detail</Link>{report.status === 'submitted' && <button onClick={() => moderate(report.id, 'under_review')}>Tinjau</button>}{report.status === 'under_review' && <><button onClick={() => moderate(report.id, 'valid')}>Valid</button><button onClick={() => moderate(report.id, 'invalid')}>Tidak valid</button></>}</span></article>)}
            {!reports.data.length && <div className="reports-empty"><strong>Belum ada laporan warga</strong><span>Laporan yang masuk akan tampil di sini.</span></div>}
        </section>
    </div></AuthenticatedLayout>;
}
