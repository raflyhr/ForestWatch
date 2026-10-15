import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { createHotspotPopup, isValidHotspot } from '@/hotspots';
import { 
    Satellite, 
    CloudSun, 
    ClipboardList, 
    MapPin, 
    Route, 
    Droplets, 
    ShieldCheck, 
    Users,
    Flame,
    AlertTriangle,
    Maximize2,
    Menu,
    ArrowRight
} from 'lucide-react';

const features = [
    ['01', 'Akses Wilayah', 'Membantu memahami akses jalan menuju area kejadian berdasarkan data peta yang tersedia.', Route],
    ['02', 'Sumber Air', 'Menemukan dan meninjau sumber air yang terpetakan seperti sungai, danau, atau rawa di sekitar lokasi.', Droplets],
    ['03', 'Persiapan Respons', 'Memberikan gambaran awal bagi petugas untuk mempertimbangkan logistik sebelum bergerak ke lapangan.', ShieldCheck],
    ['04', 'Laporan Masyarakat', 'Integrasi laporan warga untuk mempercepat verifikasi indikasi titik api secara kolektif.', Users],
];

const intelligence = [
    [Satellite, 'Integrasi NASA FIRMS', 'Akses data hotspot satelit MODIS dan VIIRS untuk mendeteksi anomali panas di wilayah hutan.'],
    [CloudSun, 'Informasi Cuaca', 'Pantau parameter cuaca BMKG seperti suhu dan kelembaban yang memengaruhi risiko kebakaran.'],
    [ClipboardList, 'Laporan Indikasi', 'Verifikasi berbasis masyarakat dengan fitur unggah temuan indikasi kebakaran langsung dari lokasi.'],
    [MapPin, 'Konteks Lokasi', 'Menyediakan data pendukung akses dan sumber air terdekat untuk membantu persiapan strategi respons.'],
];

function MapPanel({ large = false }) {
    const mapShellRef = useRef(null);
    const toggleFullscreen = () => document.fullscreenElement ? document.exitFullscreen() : mapShellRef.current?.requestFullscreen();

    return (
        <div ref={mapShellRef} className={`leaflet-map-wrap ${large ? 'leaflet-map-large' : 'leaflet-map-hero'}`}>
            <button className="map-fullscreen-button" type="button" onClick={toggleFullscreen} aria-label="Buka peta layar penuh">
                <Maximize2 size={14} style={{marginRight: '6px'}} /> Layar penuh
            </button>
            <LeafletMap />
        </div>
    );
}

function LeafletMap() {
    const mapRef = useRef(null);
    const hotspotLayerRef = useRef(null);

    useEffect(() => {
        const map = L.map(mapRef.current, { zoomControl: false, attributionControl: true }).setView([-2.25, 113.92], 6);
        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri'
        }).addTo(map);
        const standard = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' });
        const terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenTopoMap contributors' });
        hotspotLayerRef.current = L.layerGroup().addTo(map);
        fetch('/api/hotspots?per_page=100', { headers: { Accept: 'application/json' } })
            .then((response) => response.ok ? response.json() : Promise.reject())
            .then((payload) => (payload.data ?? payload).filter(isValidHotspot).forEach((hotspot) => L.circleMarker([hotspot.latitude, hotspot.longitude], { radius: 8, color: '#ff5b2f', fillColor: '#ffd894', fillOpacity: 1, weight: 3 }).bindPopup(createHotspotPopup(hotspot)).addTo(hotspotLayerRef.current)))
            .catch(() => {});
        const resizeObserver = new ResizeObserver(() => map.invalidateSize());
        resizeObserver.observe(mapRef.current);
        window.setTimeout(() => map.invalidateSize(), 100);
        return () => {
            resizeObserver.disconnect();
            map.remove();
        };
    }, []);

    return <div ref={mapRef} className="leaflet-map" aria-label="ForestWatch live monitoring map" />;
}

export default function Welcome() {
    const [isScrolled, setIsScrolled] = useState(false);

    useEffect(() => {
        const handleScroll = () => setIsScrolled(window.scrollY > 24);
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return (
        <>
            <Head title="ForestWatch" />
            <div className="forestwatch">
                <nav className={`nav-shell ${isScrolled ? 'nav-scrolled' : ''}`}>
                    <a className="brand" href="#top"><img src="/logo%20watermark%202%20(1).png" alt="ForestWatch" /></a>
                    <div className="nav-links"><a href="#works">Alur</a><a href="#intelligence">Informasi</a><a href="#monitoring">Peta Pemantauan</a><a href="#contact">Filosofi</a></div>
                    <a className="login-button" href="/login">Masuk</a>
                    <button className="menu-button" type="button" aria-label="Buka menu"><Menu size={20} /></button>
                </nav>

                <header id="top" className="hero section-wrap">
                    <img className="hero-corner hero-corner-tl" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <img className="hero-corner hero-corner-tr" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <img className="hero-corner hero-corner-bl" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <img className="hero-corner hero-corner-br" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <div className="eyebrow">FORESTWATCH · PEMANTAUAN DINI & DUKUNGAN RESPONS KARHUTLA</div>
                    <h1>Deteksi Lebih Awal.<br />Respons Lebih Terarah.</h1>
                    <p className="hero-copy">ForestWatch mengintegrasikan data hotspot satelit, informasi cuaca, dan laporan masyarakat untuk membantu pemantauan kebakaran hutan dan lahan. Informasi akses wilayah dan sumber air turut memberikan konteks bagi petugas dalam mempersiapkan penanganan di lapangan.</p>
                    <div className="button-row">
                        <a className="button button-orange" href="#monitoring">
                            Lihat Peta Pemantauan <ArrowRight size={14} style={{display: 'inline', marginLeft: '6px'}} />
                        </a>
                        <a className="button button-ghost" href="/reports/create">Laporkan Indikasi Kebakaran</a>
                    </div>
                </header>

                <div className="hero-workflow-divider" aria-hidden="true" />

                <section id="works" className="works section-wrap">
                    <img className="workflow-bird workflow-bird-left" src="/hornbill-accent.png" alt="" aria-hidden="true" />
                    <img className="workflow-bird workflow-bird-right" src="/hornbill-accent.png" alt="" aria-hidden="true" />
                    <div className="eyebrow">TACTICAL WORKFLOW</div><h2>Dari Titik Hotspot ke Gambaran Respons Lapangan</h2><p className="section-copy">Mengetahui lokasi indikasi kebakaran saja belum cukup untuk mempersiapkan penanganan. ForestWatch membantu menampilkan konteks akses wilayah dan sumber air di sekitar lokasi.</p>
                    <div className="feature-grid">
                        {features.map(([num, title, copy, IconComponent]) => (
                            <article className="feature-card" key={title}>
                                <div className="feature-icon">
                                    <IconComponent size={24} />
                                </div>
                                <small>{num}</small>
                                <h3>{title}</h3>
                                <p>{copy}</p>
                            </article>
                        ))}
                    </div>
                </section>

<section id="intelligence" className="intelligence section-wrap">
                    <div className="eyebrow">MULTI-SOURCE DATA ENGINE</div>
                    <div className="split-heading">
                        <h2>Dukungan Informasi<br />Kesiapsiagaan</h2>
                        <p>Kami menyatukan pengamatan satelit, parameter cuaca terestrial, dan laporan warga ke dalam satu sistem informasi untuk mendukung persiapan respons di lapangan.</p>
                    </div>
                    <div className="intelligence-grid">
                        {intelligence.map(([Icon, title, copy], index) => (
                            <article className={`intel-card ${index % 3 === 1 || index === 2 ? 'dark-card' : ''}`} key={title}>
                                <div className="intel-icon">
                                    <Icon size={24} strokeWidth={2.5} />
                                </div>
                                <div>
                                    <h3>{title}</h3>
                                    <p>{copy}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>

                <section id="monitoring" className="monitoring section-wrap">
                    <img className="monitoring-leaf monitoring-leaf-left" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <img className="monitoring-leaf monitoring-leaf-right" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <div className="eyebrow">OPERATIONS CONTROL</div>
                    <h2>Peta Pemantauan Aktif</h2>
                    <p className="section-copy">Snapshot real-time untuk pemantauan hotspot aktif dan tracking operasional di wilayah kerja.</p>
                    <div className="monitoring-dashboard">
                        <aside className="outbreaks">
                            <h3>Indikasi Hotspot</h3>
                            <p>Pantau titik anomali panas dan status penanganannya berdasarkan informasi yang tersedia. Data hotspot merupakan indikasi awal dan tetap memerlukan verifikasi lapangan.</p>
                            <div className="outbreak-card">
                                <b>Simulasi: Kalimantan Tengah</b>
                                <span className="critical">
                                    <Flame size={12} style={{display: 'inline-block', marginRight: '6px', verticalAlign: 'middle'}} />
                                    Data Simulasi — Indikasi Panas
                                </span>
                                <small>Terdeteksi: 2024-08-15 14:32 WIB · Confidence: 85% · Satelit: VIIRS</small>
                            </div>
                            <div className="outbreak-card">
                                <b>Simulasi: Sumatera Selatan</b>
                                <span className="verified">
                                    <ShieldCheck size={12} style={{display: 'inline-block', marginRight: '6px', verticalAlign: 'middle'}} />
                                    Data Simulasi — Butuh Verifikasi
                                </span>
                                <small>Terdeteksi: 2024-08-15 11:15 WIB · Confidence: 72% · Satelit: MODIS</small>
                            </div>
                            <div className="outbreak-card">
                                <b>Simulasi: Riau</b>
                                <span className="extinguished">
                                    <AlertTriangle size={12} style={{display: 'inline-block', marginRight: '6px', verticalAlign: 'middle'}} />
                                    Data Simulasi — Monitoring
                                </span>
                                <small>Terdeteksi: 2024-08-14 09:45 WIB · Confidence: 65% · Satelit: VIIRS</small>
                            </div>
                        </aside>
                        <MapPanel large />
                    </div>
                </section>

                 <section id="contact" className="closing section-wrap">
                    <div className="partners">PEMANTAUAN MANDIRI　 DATA TERINTEGRASI　 KESIAPSIAGAAN DINI</div>
                    <div className="eyebrow" style={{textAlign: 'center', width: '100%', marginBottom: '1rem'}}>WHY FORESTWATCH EXISTS</div>
                    <blockquote style={{maxWidth: '800px', margin: '42px auto'}}>
                        “Jangan Menunggu Dampaknya Terlihat untuk Mulai Bertindak. Dampak bencana sering kali menjadi hal yang paling terlihat, sementara tanda-tanda awal dan informasi yang dibutuhkan untuk bersiap dapat luput dari perhatian. ForestWatch berangkat dari keyakinan bahwa kesiapsiagaan perlu dimulai sebelum keadaan memburuk.”
                        <cite>ForestWatch Philosophy<br /><small>Kesiapsiagaan dimulai dari informasi yang tepat.</small></cite>
                    </blockquote>

                    <div className="cta-card" style={{maxWidth: '800px', margin: '0 auto 60px', padding: '48px 40px', borderRadius: '24px', background: 'linear-gradient(110deg, #ff6b39 0%, #a66d45 33%, #09533c 100%)', boxShadow: '0 18px 34px #001b1355', textAlign: 'center'}}>
                        <h2 style={{margin: '0 0 16px', fontSize: 'clamp(28px, 3.5vw, 44px)', lineHeight: '1.1', color: '#fff'}}>Mulai dari Informasi yang Tepat</h2>
                        <p style={{margin: '0 auto 28px', maxWidth: '560px', color: '#e1e8df', fontSize: '16px', lineHeight: '1.65'}}>
                            Pantau indikasi karhutla, pahami konteks lokasi, dan sampaikan laporan untuk membantu proses verifikasi. ForestWatch menyatukan informasi awal yang dapat mendukung kesiapsiagaan.
                        </p>
                        <div className="cta-form" style={{justifyContent: 'center'}}>
                            <a className="button button-orange" href="#monitoring" style={{fontSize: '14px', padding: '15px 28px', borderRadius: '13px'}}>
                                Buka Peta Pemantauan <ArrowRight size={16} style={{display: 'inline', marginLeft: '8px', verticalAlign: 'middle'}} />
                            </a>
                        </div>
                    </div>
                </section>

                                   <footer style={{marginTop: '0', paddingTop: '40px'}}><div className="footer-brand"><a className="brand" href="#top"><img src="/logo%20watermark%202%20(1).png" alt="ForestWatch" /></a><p>ForestWatch membantu pemantauan indikasi karhutla melalui integrasi data satelit, informasi cuaca, laporan masyarakat, serta konteks akses dan sumber air untuk mendukung persiapan respons.</p></div><div className="footer-links"><div><b>Pemantauan</b><a href="#monitoring">Peta Pemantauan</a><a href="#works">Alur Kerja</a><a href="/reports/create">Pelaporan</a></div><div><b>Informasi</b><a href="#intelligence">Sumber Data</a><a href="#contact">Tentang ForestWatch</a></div><div><b>Akun</b><a href="/login">Masuk</a></div></div><div className="copyright">© 2026 ForestWatch Project. Data satelit bersumber dari NASA FIRMS.</div></footer>
            </div>
        </>
    );
}
