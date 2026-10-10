export const rupiah = (value) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value ?? 0);

export const compactRupiah = (value) =>
    'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value ?? 0);

export const greeting = () => {
    const hour = new Date().getHours();
    if (hour < 11) return 'Selamat pagi';
    if (hour < 15) return 'Selamat siang';
    if (hour < 18) return 'Selamat sore';
    return 'Selamat malam';
};

/** Warna kartu kelas: kelas Tailwind lengkap agar tidak terhapus saat build. */
export const classColors = {
    violet: { bg: 'bg-violet-50', ring: 'ring-violet-200', dot: 'bg-violet-500', text: 'text-violet-700', solid: 'bg-violet-500', grad: 'from-violet-500 to-fuchsia-500' },
    sky: { bg: 'bg-sky-50', ring: 'ring-sky-200', dot: 'bg-sky-500', text: 'text-sky-700', solid: 'bg-sky-500', grad: 'from-sky-500 to-cyan-400' },
    emerald: { bg: 'bg-emerald-50', ring: 'ring-emerald-200', dot: 'bg-emerald-500', text: 'text-emerald-700', solid: 'bg-emerald-500', grad: 'from-emerald-500 to-teal-400' },
    amber: { bg: 'bg-amber-50', ring: 'ring-amber-200', dot: 'bg-amber-500', text: 'text-amber-700', solid: 'bg-amber-500', grad: 'from-amber-400 to-orange-500' },
    rose: { bg: 'bg-rose-50', ring: 'ring-rose-200', dot: 'bg-rose-500', text: 'text-rose-700', solid: 'bg-rose-500', grad: 'from-rose-500 to-pink-500' },
    indigo: { bg: 'bg-indigo-50', ring: 'ring-indigo-200', dot: 'bg-indigo-500', text: 'text-indigo-700', solid: 'bg-indigo-500', grad: 'from-indigo-500 to-blue-500' },
};
