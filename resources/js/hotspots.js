export function createHotspotPopup(hotspot, onRoute) {
    const popup = document.createElement('div');
    popup.className = 'hotspot-popup';
    const date = hotspot.detected_at ? new Date(hotspot.detected_at).toLocaleString('id-ID') : 'Tidak tersedia';
    const weather = hotspot.incident?.latest_weather_snapshot;

    popup.innerHTML = `
        <div class="hotspot-popup-head">
            <span class="hotspot-popup-icon">🔥</span>
            <div><span class="hotspot-popup-kicker">LIVE DETECTION</span><strong>Hotspot terdeteksi</strong></div>
            <span class="hotspot-popup-badge">NASA FIRMS</span>
        </div>
        <div class="hotspot-popup-primary"><span>CONFIDENCE</span><b></b><small>tingkat keyakinan satelit</small></div>
        <div class="hotspot-popup-grid">
            <div><span>FRP</span><b></b><small>daya radiasi api</small></div>
            <div><span>SATELIT</span><b></b><small>instrumen deteksi</small></div>
            <div><span>TERDETEKSI</span><b></b><small>waktu pengamatan</small></div>
            <div><span>KOORDINAT</span><b></b><small>posisi hotspot</small></div>
        </div>
        ${weather ? '<div class="hotspot-weather"><div class="hotspot-weather-title"><span>☁</span><strong>Cuaca BMKG</strong><small>data terakhir</small></div><div class="hotspot-weather-grid"><div><b></b><span>Suhu</span></div><div><b></b><span>Kelembapan</span></div><div><b></b><span>Angin</span></div><div><b></b><span>Arah angin</span></div></div></div>' : '<div class="hotspot-weather-empty">Data cuaca BMKG belum tersedia untuk insiden ini.</div>'}
    `;

    const values = [
        [popup.querySelector('.hotspot-popup-primary b'), hotspot.confidence ?? 'Tidak tersedia'],
        [popup.querySelector('.hotspot-popup-grid div:nth-child(1) b'), hotspot.frp == null ? 'Tidak tersedia' : `${hotspot.frp} MW`],
        [popup.querySelector('.hotspot-popup-grid div:nth-child(2) b'), hotspot.satellite ?? 'Tidak tersedia'],
        [popup.querySelector('.hotspot-popup-grid div:nth-child(3) b'), date],
        [popup.querySelector('.hotspot-popup-grid div:nth-child(4) b'), `${hotspot.latitude}, ${hotspot.longitude}`],
    ];
    values.forEach(([element, value]) => { element.textContent = value; });

    if (weather) {
        const weatherValues = [
            [popup.querySelector('.hotspot-weather-grid div:nth-child(1) b'), weather.temperature == null ? '—' : `${weather.temperature}°C`],
            [popup.querySelector('.hotspot-weather-grid div:nth-child(2) b'), weather.humidity == null ? '—' : `${weather.humidity}%`],
            [popup.querySelector('.hotspot-weather-grid div:nth-child(3) b'), weather.wind_speed == null ? '—' : `${weather.wind_speed} km/j`],
            [popup.querySelector('.hotspot-weather-grid div:nth-child(4) b'), weather.wind_direction ?? '—'],
        ];
        weatherValues.forEach(([element, value]) => { element.textContent = value; });
    }

    if (hotspot.incident_id) {
        const link = document.createElement('a');
        link.href = `/incidents/${encodeURIComponent(hotspot.incident_id)}`;
        link.className = 'hotspot-popup-link';
        link.textContent = 'Buka detail insiden';
        popup.append(link);
    }

    if (onRoute) {
        const routeButton = document.createElement('button');
        routeButton.className = 'hotspot-popup-route';
        routeButton.type = 'button';
        routeButton.textContent = 'Cari rute air terdekat';
        routeButton.addEventListener('click', () => onRoute(routeButton));
        popup.append(routeButton);
    }

    return popup;
}

export function createRoutePopup(data) {
    const popup = document.createElement('div');
    popup.className = 'hotspot-popup hotspot-route-popup';
    const distance = data.route.distance_m == null ? 'Tidak tersedia' : `${(data.route.distance_m / 1000).toFixed(1)} km`;
    const duration = data.route.duration_s == null ? 'Tidak tersedia' : `${Math.round(data.route.duration_s / 60)} menit`;
    popup.innerHTML = '<div class="hotspot-popup-head"><span class="hotspot-popup-icon">💧</span><div><span class="hotspot-popup-kicker">ADMIN ROUTE</span><strong>Rute sumber air</strong></div><span class="hotspot-popup-badge">TERVERIFIKASI</span></div><div class="hotspot-popup-primary"><span>SUMBER AIR TERDEKAT</span><b></b><small></small></div><div class="hotspot-popup-grid"><div><span>JARAK</span><b></b><small>estimasi perjalanan</small></div><div><span>WAKTU</span><b></b><small>via jalan darat</small></div><div><span>TIPE</span><b></b><small>identifikasi sumber</small></div><div><span>DATA DARI</span><b></b><small>asal data sumber air</small></div><div class="hotspot-soil"><span>KLASIFIKASI TANAH</span><b></b><small>data insiden</small></div></div>';
    const values = [
        [popup.querySelector('.hotspot-popup-primary b'), data.source.name],
        [popup.querySelector('.hotspot-popup-primary small'), `Tujuan: ${data.source.latitude}, ${data.source.longitude}`],
        [popup.querySelector('.hotspot-popup-grid div:nth-child(1) b'), distance],
        [popup.querySelector('.hotspot-popup-grid div:nth-child(2) b'), duration],
        [popup.querySelector('.hotspot-popup-grid div:nth-child(3) b'), data.source.type],
        [popup.querySelector('.hotspot-popup-grid div:nth-child(4) b'), data.source.source ?? 'Tim kecamatan'],
        [popup.querySelector('.hotspot-soil b'), formatLandClassification(data.land_classification)],
    ];
    values.forEach(([element, value]) => { element.textContent = value ?? 'Tidak tersedia'; });
    return popup;
}

function formatLandClassification(classification) {
    if (!classification) return 'Tidak tersedia';
    if (typeof classification === 'string') return classification;
    return classification.terrain_type ?? classification.soil_type ?? classification.classification ?? (Object.values(classification).filter(Boolean).join(', ') || 'Tidak tersedia');
}

export function isValidHotspot(hotspot) {
    return Number.isFinite(Number(hotspot.latitude)) && Number.isFinite(Number(hotspot.longitude));
}
