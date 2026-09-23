import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Login({ status, canResetPassword, canRegister }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Masuk" />
            <div className="auth-topbar"><Link href="/" className="auth-back"><span className="auth-back-icon">←</span><span>Kembali</span></Link>{canRegister && <span>Belum punya akun? <Link href={route('register')}>Daftar gratis</Link></span>}</div>
            <div className="auth-form-wrap">
                <div className="auth-heading"><h1>Selamat Datang Kembali</h1><p>Masuk untuk melanjutkan ke dashboard Anda</p><span>♢ Admin / Petugas Khusus</span></div>
                {status && <div className="auth-status">{status}</div>}
                <form onSubmit={submit} className="auth-form">
                    <div>
                        <InputLabel htmlFor="email" value="EMAIL ATAU ID KHUSUS" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="auth-input mt-1 block w-full"
                        placeholder="nama@instansi.go.id atau ID Khusus"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                    <div className="auth-password-field">
                        <div className="auth-password-row">
                            <InputLabel htmlFor="password" value="PASSWORD" />
                            {canResetPassword && <Link href={route('password.request')}>Lupa password?</Link>}
                        </div>
                        <div className="auth-password-input">
                            <TextInput
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                value={data.password}
                                className="auth-input mt-1 block w-full"
                                placeholder="••••••••"
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                            />
                            <button type="button" className="password-toggle" onClick={() => setShowPassword(!showPassword)} aria-label="Tampilkan password">◉</button>
                        </div>
                        <InputError message={errors.password} className="mt-2" />
                    </div>
                    <button className="auth-submit" disabled={processing}>Masuk ke Dashboard <span>→</span></button>
                </form>
                <div className="auth-security">♧　Dilindungi enkripsi end-to-end sistem pemantauan lingkungan.</div>
            </div>
            <div className="auth-copyright">© 2025 ForestWatch Network. Hak cipta dilindungi undang-undang.</div>
        </GuestLayout>
    );
}
