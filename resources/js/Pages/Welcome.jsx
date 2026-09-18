import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const features = [
    ['01', 'Detect', 'Global FIRMS thermal bands flag infrared anomalies in real time.'],
    ['02', 'Warn', 'Automated routing instantly alerts ground response teams.'],
    ['03', 'Verify', 'Localer data needs & community reports confirm hotspots.'],
    ['04', 'Respond', 'Targeted coordinates dispatch directly to emergency response.'],
];

const intelligence = [
    ['◉', 'NASA FIRMS Integration', 'Direct orbital API download, harvests thermal anomaly pixel arrays from MODIS and VIIRS satellite instruments.'],
    ['◌', 'Weather Intelligence', 'Correlates predictive meteorological data: instant tracking of dry winds, lightning density, and humidity metrics.'],
    ['♧', 'Crowdsourced Reports', 'On-the-ground verification with citizen networks. Instantly gets high-accuracy reports with media attach features.'],
    ['◎', 'AI Evidence Analysis', 'Machine vision scanning models refine false positives by running multi-spectral image checks against fire databases.'],
];

function MapPanel({ large = false }) {
    return (
        <div className={`leaflet-map-wrap ${large ? 'leaflet-map-large' : 'leaflet-map-hero'}`}>
            <LeafletMap />
        </div>
    );
}

function LeafletMap() {
    const mapRef = useRef(null);

    useEffect(() => {
        const map = L.map(mapRef.current, { zoomControl: false, attributionControl: true }).setView([-2.25, 113.92], 6);
        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri'
        }).addTo(map);
        const standard = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' });
        const terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenTopoMap contributors' });
        L.polygon([[-1.2, 112.8], [-1.45, 115.4], [-3.2, 115.1], [-3.4, 112.7]], { color: '#46d5db', weight: 2, fillColor: '#0d6a60', fillOpacity: 0.16 }).addTo(map);
        [[-2.05, 113.95], [-1.8, 114.35], [-2.5, 113.55]].forEach((point) => L.circleMarker(point, { radius: 8, color: '#ff5b2f', fillColor: '#ffd894', fillOpacity: 1, weight: 3 }).addTo(map));
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
                    <div className="nav-links"><a href="#works">Alur</a><a href="#intelligence">Berita</a><a href="#monitoring">Tentang Kami</a><a href="#contact">Pelaporan</a></div>
                    <a className="login-button" href="/login">Masuk</a>
                    <button className="menu-button" type="button" aria-label="Buka menu">☰</button>
                </nav>

                <header id="top" className="hero section-wrap">
                    <img className="hero-corner hero-corner-tl" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <img className="hero-corner hero-corner-tr" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <img className="hero-corner hero-corner-bl" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <img className="hero-corner hero-corner-br" src="/deco-leaf-tr.png" alt="" aria-hidden="true" />
                    <div className="eyebrow">REAL-TIME ENVIRONMENTAL SECURITY</div>
                    <h1>Protect Forests<br />Before It's Too Late</h1>
                    <p className="hero-copy">Deploying autonomous, real-time wildfire intelligence. ForestWatch combines satellite thermal data, multispectral weather metrics, and crowdsourced reporting to stop outbreaks in minutes.</p>
                    <div className="button-row"><a className="button button-orange" href="#monitoring">View Live Map <b>→</b></a><a className="button button-ghost" href="/reports/create">Report Incident </a></div>
                </header>

                <div className="hero-workflow-divider" aria-hidden="true" />

                <section id="works" className="works section-wrap">
                    <img className="workflow-bird workflow-bird-left" src="/hornbill-accent.png" alt="" aria-hidden="true" />
                    <img className="workflow-bird workflow-bird-right" src="/hornbill-accent.png" alt="" aria-hidden="true" />
                    <div className="eyebrow">TACTICAL WORKFLOW</div><h2>How ForestWatch Works</h2><p className="section-copy">Closing the critical gap between initial outbreak and response agency deployment.</p>
                    <div className="feature-grid">{features.map(([num, title, copy]) => <article className="feature-card" key={title}><div className="feature-icon">{title === 'Detect' ? '◉' : title === 'Warn' ? '✹' : title === 'Verify' ? '✓' : '▣'}</div><small>{num}</small><h3>{title}</h3><p>{copy}</p></article>)}</div>
                </section>

                <section id="intelligence" className="intelligence section-wrap"><div className="eyebrow">MULTI-SOURCE DATA ENGINE</div><div className="split-heading"><h2>Continuous Ecological<br />Intelligence</h2><p>We integrate space observation, terrestrial weather systems, and boots-on-the-ground community alerts into one continuous intelligence stream.</p></div><div className="intelligence-grid">{intelligence.map(([icon, title, copy], index) => <article className={`intel-card ${index % 3 === 1 || index === 2 ? 'dark-card' : ''}`} key={title}><div className="intel-icon">{icon}</div><div><h3>{title}</h3><p>{copy}</p></div></article>)}</div></section>

                <section id="monitoring" className="monitoring section-wrap"><div className="eyebrow">OPERATIONAL CONTROL</div><h2>Live Monitoring Interface</h2><p className="section-copy">A high-fidelity snapshot of active global monitoring, operational tracking, and thermal hotspot detection indicators.</p><MapPanel large /></section>

                 <section id="impact" className="reach section-wrap"><div className="eyebrow">PLATFORM FOOTPRINT</div><h2>Empirical Protective Reach</h2><p className="section-copy">Deploying technology directly at the interface of preservation, early detection, and rapid action.</p><div className="stats">{[['12,847', 'Outbreaks Detected'], ['8,392', 'Verified Events'], ['156', 'Protected Reserve Hubs'], ['<3 min', 'Avg. Transmission']].map(([value, label], i) => <div className={`stat stat-${i}`} key={label}><strong>{value}</strong><span>{label}</span></div>)}</div></section>

                <section id="contact" className="closing section-wrap"><div className="partners">SATELLITE LABS　 METEOROLOGY ALLIANCE　 EARTH FIRST　 INDONESIA CONSERVATION　 ECOTRUST</div><blockquote>“Before implementing ForestWatch, tracking wildfires across our reserves relied entirely on visible ash and local report lag. The MODIS integration flags thermal events before columns break the forest canopy.”<cite>Dr. Rahma Santoso<br /><small>Lead Warden, Kalimantan Conservation Project</small></cite></blockquote><div className="cta"><h2>Join the Fight Against<br />Forest Loss</h2><a className="button button-orange" href="mailto:hello@forestwatch.id">Partner With Us <b>→</b></a></div></section>

                 <footer><div className="footer-brand"><a className="brand" href="#top"><img src="/logo%20watermark%202%20(1).png" alt="ForestWatch" /></a><p>Deploying global observation technology to secure<br />ecological reserves, protect natural infrastructure, and<br />secure early action against wildfire.</p></div><div className="footer-links"><div><b>Product</b><a href="#monitoring">Live Map</a><a href="#intelligence">FIRMS Sync</a><a href="#works">Demo Reports</a><a href="#contact">Pricing</a></div><div><b>Resources</b><a href="#contact">API Reference</a><a href="#contact">Incident Schema</a><a href="#contact">Case Studies</a><a href="#contact">Status</a></div><div><b>Company</b><a href="#contact">About</a><a href="#contact">Ecology Alliance</a><a href="#contact">Investor Room</a><a href="#contact">Contact</a></div></div><div className="copyright">© 2025 ForestWatch Inc. All species rights reserved under NASA information FIRMS protocols.</div></footer>
            </div>
        </>
    );
}
