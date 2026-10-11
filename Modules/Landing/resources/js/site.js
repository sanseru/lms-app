// Data landing page. Ganti nomor WhatsApp, email, harga, dan testimoni di sini.
export const site = {
    brand: 'Alprogmatics',
    waNumber: '6281234567890', // format internasional tanpa +
    waDisplay: '+62 812-3456-7890',
    email: 'halo@alprogmatics.id',
    hours: 'Senin–Sabtu, 08.00–20.00 WIB',
};

export function waLink(text = 'Halo, saya ingin bertanya tentang kelas coding.') {
    return `https://wa.me/${site.waNumber}?text=${encodeURIComponent(text)}`;
}

export const trialText = 'Halo, saya ingin daftar kelas coba gratis untuk anak saya.';

export const navLinks = [
    { href: '#program', label: 'Program' },
    { href: '#cara', label: 'Cara Belajar' },
    { href: '#laporan', label: 'Laporan Ortu' },
    { href: '#harga', label: 'Biaya' },
    { href: '#faq', label: 'FAQ' },
];

export const features = [
    { icon: 'users', tone: 'brand', title: 'Kelas kecil, perhatian penuh', body: 'Privat 1 anak 1 tutor, atau kelompok maksimal 5 anak dengan usia dan level yang sama.' },
    { icon: 'project', tone: 'accent', title: 'Belajar lewat proyek', body: 'Setiap modul berakhir dengan karya nyata: game, animasi, aplikasi, atau website yang bisa dipamerkan.' },
    { icon: 'check', tone: 'success', title: 'Laporan setiap sesi', body: 'Absensi, materi, dan catatan tutor dikirim ke orang tua. Ada rapor perkembangan tiap akhir level.' },
    { icon: 'calendar', tone: 'accent', title: 'Jadwal fleksibel', body: 'Pilih hari dan jam yang cocok, termasuk sore dan akhir pekan. Bisa dijadwal ulang bila anak berhalangan.' },
];

export const ageGroups = [
    { key: 'all', label: 'Semua usia' },
    { key: '6-9', label: '6–9 th' },
    { key: '10-12', label: '10–12 th' },
    { key: '13-17', label: '13–17 th' },
];

export const programs = [
    { mark: 'Sc', bg: 'bg-amber-100 text-amber-800', ages: ['6-9'], range: '6–9 tahun', title: 'Scratch Junior & Scratch', body: 'Coding dengan balok warna. Anak membuat cerita animasi dan game sederhana sambil belajar urutan, perulangan, dan logika.', meta: '3 level · 16 sesi per level' },
    { mark: 'GM', bg: 'bg-emerald-100 text-emerald-800', ages: ['6-9', '10-12'], range: '8–12 tahun', title: 'Game Maker', body: 'Merancang level, karakter, dan aturan permainan. Cocok untuk anak yang suka Minecraft dan Roblox.', meta: '2 level · 16 sesi per level' },
    { mark: 'Py', bg: 'bg-orange-100 text-orange-800', ages: ['10-12', '13-17'], range: '10–17 tahun', title: 'Python Pro', body: 'Bahasa pemrograman sungguhan. Dari variabel dan perulangan sampai membuat game teks, kuis, dan otomasi kecil.', meta: '4 level · 32 pelajaran per level' },
    { mark: '{ }', bg: 'bg-cyan-100 text-cyan-800', ages: ['10-12', '13-17'], range: '11–17 tahun', title: 'Web Developer', body: 'HTML, CSS, dan JavaScript. Anak membangun website portofolio dan halaman interaktif miliknya sendiri.', meta: '3 level · 16 sesi per level' },
    { mark: 'AI', bg: 'bg-violet-100 text-violet-800', ages: ['13-17'], range: '13–17 tahun', title: 'AI & Data Explorer', body: 'Mengenal kecerdasan buatan dengan cara yang aman: mengolah data, membuat grafik, dan melatih model sederhana.', meta: '2 level · 16 sesi per level' },
    { mark: 'An', bg: 'bg-green-100 text-green-800', ages: ['13-17'], range: '13–17 tahun', title: 'Android & IoT', body: 'Membuat aplikasi ponsel dan proyek perangkat pintar, dari lampu otomatis sampai sensor suhu.', meta: '2 level · 16 sesi per level' },
];

export const steps = [
    { title: 'Konsultasi via WhatsApp', body: 'Ceritakan usia dan minat anak. Admin membantu memilih program dan jadwal.' },
    { title: 'Kelas coba gratis', body: 'Satu sesi Zoom 45 menit bersama tutor. Anak mencoba membuat proyek mini dan tutor menentukan level awal.' },
    { title: 'Pilih paket & transfer', body: 'Bayar melalui transfer bank, lalu kirim bukti transfer. Admin mengonfirmasi dan jadwal langsung aktif.' },
    { title: 'Belajar & terima laporan', body: 'Anak masuk Zoom sesuai jadwal. Setelah sesi, orang tua menerima catatan perkembangan.' },
];

// Contoh teks: ganti dengan testimoni asli sebelum dipublikasikan.
export const testimonials = [
    { initial: 'A', quote: 'Anak saya biasanya cepat bosan. Di sini ia malah minta jadwal tambahan karena ingin menyelesaikan game-nya.', name: 'Nama Orang Tua', role: 'Ibu dari siswa Scratch, 8 th' },
    { initial: 'B', quote: 'Laporan setelah kelas sangat membantu. Saya jadi bisa mengajak anak ngobrol soal apa yang ia buat hari itu.', name: 'Nama Orang Tua', role: 'Ayah dari siswa Python, 12 th' },
    { initial: 'C', quote: 'Kelas kelompoknya kecil jadi tetap diperhatikan. Tutornya sabar menjelaskan berulang kali.', name: 'Nama Orang Tua', role: 'Ibu dari siswa Web, 14 th' },
];

// Harga contoh (Rupiah). Sesuaikan sebelum dipublikasikan.
export const plans = [
    { name: 'Starter', sub: '4 sesi · cocok untuk mencoba', sessions: 4, price: { privat: 600000, kelompok: 350000 }, perks: ['Laporan setiap sesi', 'Akses materi & latihan', 'Berlaku 6 minggu'] },
    { name: 'Reguler', sub: '8 sesi · 2 sesi per minggu', sessions: 8, price: { privat: 1120000, kelompok: 640000 }, perks: ['Semua isi Starter', 'Rapor perkembangan', '1x jadwal ulang gratis', 'Berlaku 10 minggu'], featured: true },
    { name: 'Satu Level', sub: '16 sesi · tuntas satu level', sessions: 16, price: { privat: 2080000, kelompok: 1200000 }, perks: ['Semua isi Reguler', 'Sertifikat kelulusan level', 'Presentasi karya ke orang tua', 'Berlaku 20 minggu'] },
];

export const faqs = [
    { q: 'Anak saya belum pernah coding. Bisa ikut?', a: 'Bisa. Sebagian besar siswa mulai dari nol. Tutor menentukan level awal di kelas coba gratis.' },
    { q: 'Perangkat apa yang dibutuhkan?', a: 'Laptop atau komputer dengan kamera, mikrofon, dan internet stabil. Untuk usia 6–7 tahun, tablet masih bisa dipakai di level awal. Aplikasi Zoom gratis.' },
    { q: 'Apa beda kelas privat dan kelompok?', a: 'Privat berarti satu anak satu tutor dengan jadwal dan tempo yang sepenuhnya menyesuaikan anak. Kelompok berisi 3–5 anak seusia dan selevel, lebih hemat dan anak bisa belajar bersama teman.' },
    { q: 'Bagaimana cara membayar?', a: 'Transfer ke rekening resmi yang dikirim admin, lalu kirim foto bukti transfer lewat WhatsApp. Setelah dikonfirmasi, kuota sesi langsung aktif.' },
    { q: 'Kalau anak sakit atau ada acara?', a: 'Kabari admin paling lambat 6 jam sebelum kelas, dan sesi dijadwal ulang tanpa memotong kuota.' },
    { q: 'Apakah orang tua perlu mendampingi?', a: 'Untuk usia di bawah 8 tahun, kami sarankan orang tua membantu masuk Zoom di awal. Selebihnya anak belajar mandiri bersama tutor.' },
];
