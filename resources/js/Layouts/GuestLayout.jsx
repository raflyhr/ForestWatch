import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="auth-shell">
            <aside className="auth-visual">
                <Link href="/" className="auth-visual-logo"><img src="/logo%20watermark%202%20(1).png" alt="ForestWatch" /></Link>
                <div className="auth-visual-copy">Deteksi Dini, Selamatkan Bumi<br /><span>Lindungi Hutan Sebelum Terlambat.</span></div>
            </aside>
            <main className="auth-content">{children}</main>
        </div>
    );
}
