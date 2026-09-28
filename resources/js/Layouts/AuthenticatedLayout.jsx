import { Link, usePage } from '@inertiajs/react';

export default function AuthenticatedLayout({ header, children }) {
    const { props, url } = usePage();
    const user = props.auth?.user ?? { name: 'Petugas', email: '' };
    const isActive = (path) => url === path || url.startsWith(`${path}/`);

    return (
        <div className="app-shell">
            <aside className="app-sidebar">
                <Link href="/" className="app-logo"><img src="/logo%20watermark%202%20(1).png" alt="ForestWatch" /></Link>
                <nav className="app-nav">
                    <Link className={isActive('/dashboard') ? 'active' : ''} href={route('dashboard')}><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 10 8-6 8 6v9a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1z" /></svg><span>Beranda</span></Link>
                    <Link className={isActive('/incidents') ? 'active' : ''} href="/incidents"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5a2 2 0 0 1 2-2h5v16H6a2 2 0 0 0-2 2zM20 5.5a2 2 0 0 0-2-2h-5v16h5a2 2 0 0 1 2 2z" /><circle cx="17.5" cy="8" r="2.5" /><path d="M17.5 6.8v1.5l.9.6" /></svg><span>Live Monitoring</span></Link>
                    <Link className={isActive('/reports') ? 'active' : ''} href="/reports/create"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3.5" width="14" height="17" rx="2" /><path d="M9 3.5v3h6v-3M9 11h6M9 15h4" /></svg><span>Laporan</span></Link>
                </nav>
                <div className="app-user"><span>{user.name?.charAt(0) ?? 'U'}</span><div><b>{user.name}</b><small>Unit A-301</small></div><Link href={route('logout')} method="post" as="button" className="app-logout" aria-label="Keluar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5h5v14h-5M10 12h9M10 12l3-3M10 12l3 3M5 5h4M5 19h4" /></svg></Link></div>
            </aside>
            <div className="app-main"><header className="app-topbar">{header ?? <span>ForestWatch Operations</span>}<div className="app-top-actions"><span className="app-bell">♧</span><span className="app-avatar">{user.name?.charAt(0) ?? 'U'}</span><b>{user.name}</b></div></header><main>{children}</main></div>
        </div>
    );
}
