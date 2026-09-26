import { Link, usePage } from '@inertiajs/react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;

    return (
        <div className="app-shell">
            <aside className="app-sidebar">
                <Link href="/" className="app-logo"><img src="/logo%20watermark%202%20(1).png" alt="ForestWatch" /></Link>
                <nav className="app-nav"><Link className="active" href={route('dashboard')}>⌂ <span>Beranda</span></Link><Link href="/incidents">⌁ <span>Live Monitoring</span></Link><Link href="/reports/create">▣ <span>Riwayat Tiket</span></Link></nav>
                <div className="app-user"><span>{user.name?.charAt(0) ?? 'U'}</span><div><b>{user.name}</b><small>{user.email}</small></div><Link href={route('logout')} method="post" as="button" className="app-logout" aria-label="Keluar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5h5v14h-5M10 12h9M10 12l3-3M10 12l3 3M5 5h4M5 19h4" /></svg></Link></div>
            </aside>
            <div className="app-main"><header className="app-topbar">{header ?? <span>ForestWatch Operations</span>}<div className="app-top-actions"><span className="app-bell">♧</span><span className="app-avatar">{user.name?.charAt(0) ?? 'U'}</span><b>{user.name}</b></div></header><main>{children}</main></div>
        </div>
    );
}
