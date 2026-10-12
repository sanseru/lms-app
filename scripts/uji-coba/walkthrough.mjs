// Walkthrough panduan uji coba klien: menjalankan alur aplikasi dari awal sampai akhir
// di browser sungguhan (Playwright) di atas data demo yang baru, lalu menyimpan
// screenshot bernomor ke docs/panduan-uji-coba/img. Dipakai oleh skill /panduan-uji-coba.
//
// Syarat: `php artisan migrate:fresh --seed`, `npm run build`, server jalan di APP_URL
// (bawaan http://127.0.0.1:8000). Skrip ini MENGUBAH data demo (mengisi form, menyimpan),
// jadi jalankan ulang seeder sebelum mengulang.
//
// Pakai: node scripts/uji-coba/walkthrough.mjs            (semua bab)
//        ONLY=a,b node scripts/uji-coba/walkthrough.mjs    (bab tertentu, untuk perbaikan)
import { as, finish, go, OUT, shot, start, step } from './lib.mjs';
import { join } from 'node:path';

const only = (process.env.ONLY ?? '').split(',').filter(Boolean);
const chapter = async (key, fn) => (!only.length || only.includes(key)) && fn();
const btn = (page, name) => page.getByRole('button', { name, exact: false }).first();
const link = (page, name) => page.getByRole('link', { name, exact: false }).first();
// Buka tautan pertama di <main> yang alamatnya cocok dengan pola (href dari route() berupa URL penuh).
const openFirst = async (page, pattern) => {
    const href = await page.locator('main a[href]').evaluateAll((as, src) => as.map((a) => a.href).find((h) => new RegExp(src).test(h)), pattern.source);
    if (!href) throw new Error(`tidak ada tautan yang cocok dengan ${pattern}`);
    await go(page, new URL(href).pathname);
};
const flash = (page) => page.locator('[role=status], [role=alert]').first().waitFor({ timeout: 8000 }).catch(() => {});

// Data yang diisi selama walkthrough (dipakai lagi di bab berikutnya dan di panduan).
const TRIAL = { parent: 'Rina Wijaya', phone: '081234567890', child: 'Kayla', age: '9' };

await start();

// ───────────────────────── A. Masuk ke aplikasi ─────────────────────────
await chapter('a', async () => {
    const guest = await as(null);
    await step('Halaman login', async () => {
        await go(guest, '/login');
        await shot(guest, '01-login');
    });
});

// ───────────────────── B. Calon siswa daftar trial (tamu) ─────────────────────
await chapter('b', async () => {
    const guest = await as(null);
    await step('Form free trial', async () => {
        await go(guest, '/free-trial');
        await guest.fill('#parent_name', TRIAL.parent);
        await guest.fill('#phone', TRIAL.phone);
        await guest.fill('#child_name', TRIAL.child);
        await guest.fill('#child_age', TRIAL.age);
        await guest.selectOption('#interest', { label: 'Scratch Junior & Scratch' });
        await guest.fill('#preferred_time', 'Saturday morning');
        await shot(guest, '02-free-trial-form', { full: true });
        await btn(guest, 'Send sign-up').click();
        await guest.getByText(`Thank you, ${TRIAL.parent}!`).waitFor();
        await shot(guest, '03-free-trial-terkirim');
    });
});

// ───────────────────── C. Admin: lead sampai jadi siswa ─────────────────────
await chapter('c', async () => {
    const admin = await as('admin');
    await step('Beranda admin', async () => {
        await shot(admin, '04-admin-beranda', { full: true });
    });
    await step('Papan lead', async () => {
        await go(admin, '/leads');
        await shot(admin, '05-admin-leads', { full: true });
    });
    await step('Lead baru: dihubungi dan dijadwalkan trial', async () => {
        await link(admin, TRIAL.child).click();
        await admin.waitForURL('**/leads/*');
        await shot(admin, '06-admin-lead-baru');
        await btn(admin, 'I have contacted them').click();
        await flash(admin);
        await btn(admin, 'Schedule trial').click();
        const at = new Date(Date.now() + 2 * 864e5);
        at.setHours(10, 0, 0, 0);
        const pad = (n) => String(n).padStart(2, '0');
        await admin.fill('#t_at', `${at.getFullYear()}-${pad(at.getMonth() + 1)}-${pad(at.getDate())}T10:00`);
        await admin.selectOption('#t_teacher', { label: 'Sari Wulandari' });
        await admin.fill('#t_zoom', 'https://zoom.us/j/1234567890');
        await shot(admin, '07-admin-jadwal-trial');
        await btn(admin, 'Save schedule').click();
        await admin.getByText('Trial scheduled').waitFor();
    });
    await step('Hasil trial', async () => {
        await btn(admin, 'Record trial result').click();
        await admin.fill('#r_result', 'Kayla quickly made the cat move and asked to add a score. Ready for Scratch Creator 1.');
        await admin.getByRole('button', { name: '5', exact: true }).click();
        await admin.selectOption('#r_class', { label: 'Scratch Game Maker' });
        await shot(admin, '08-admin-hasil-trial');
        await btn(admin, 'Save result').click();
        await admin.getByText('Trial result saved').waitFor();
    });
    await step('Daftarkan jadi siswa', async () => {
        await btn(admin, 'Enrol as student').click();
        await admin.fill('#c_password', 'kayla12345');
        await admin.selectOption('#c_class', { label: 'Scratch Game Maker' });
        await shot(admin, '09-admin-buat-akun');
        await btn(admin, 'Create accounts').click();
        await admin.getByText('is now enrolled as a student').waitFor();
        await shot(admin, '10-admin-lead-jadi-siswa');
    });
});

// ───────────────────── D. Admin: data master dan kelas ─────────────────────
await chapter('d', async () => {
    const admin = await as('admin');
    await step('Daftar siswa dan form siswa', async () => {
        await go(admin, '/people/students');
        await shot(admin, '11-admin-siswa');
        await go(admin, '/people/students/create');
        await shot(admin, '12-admin-form-siswa', { full: true });
    });
    await step('Form orang tua dan guru', async () => {
        await go(admin, '/people/guardians/create');
        await shot(admin, '13-admin-form-orang-tua', { full: true });
        await go(admin, '/people/teachers/create');
        await shot(admin, '14-admin-form-guru', { full: true });
    });
    await step('Kelas dan detail kelas', async () => {
        await go(admin, '/classes');
        await shot(admin, '15-admin-kelas');
        await link(admin, 'Scratch Game Maker').click();
        await admin.waitForURL(/\/classes\/\d+$/);
        await shot(admin, '16-admin-detail-kelas', { full: true });
    });
    await step('Form kelas baru', async () => {
        await go(admin, '/classes/create');
        await shot(admin, '17-admin-form-kelas', { full: true });
    });
    await step('Program dan level', async () => {
        await go(admin, '/programs');
        await shot(admin, '18-admin-program', { full: true });
    });
});

// ───────────────────── E. Keuangan: tagihan sampai lunas ─────────────────────
await chapter('e', async () => {
    const admin = await as('admin');
    await step('Paket sesi', async () => {
        await go(admin, '/finance/packages');
        await shot(admin, '19-admin-paket');
    });
    await step('Buat tagihan', async () => {
        await go(admin, '/finance/invoices/create');
        // Label opsi berisi nama dan kode siswa, mis. "Bima … (SIS-…)".
        const bima = await admin.locator('#student_id option', { hasText: 'Bima' }).first().getAttribute('value');
        await admin.selectOption('#student_id', bima);
        await admin.fill('#title', 'Scratch workshop (1 day)');
        await admin.fill('#amount', '150000');
        await shot(admin, '20-admin-form-tagihan', { full: true });
        await btn(admin, 'Create invoice').click();
        await admin.getByText(/invoice\(s\) created/).waitFor();
        await shot(admin, '21-admin-daftar-tagihan');
    });

    // Orang tua (ortu5, anak Bima dan Citra) mengirim bukti transfer.
    const parent = await as('ortu5');
    const proof = join(OUT, '..', 'bukti-transfer-contoh.png');
    await step('Orang tua membuka tagihan', async () => {
        await shot(parent, '22-ortu-beranda', { full: true });
        await go(parent, '/finance/invoices');
        await shot(parent, '23-ortu-tagihan');
        await parent.locator('tr', { hasText: 'Scratch workshop' }).first().click();
        await parent.waitForURL(/\/finance\/invoices\/\d+$/);
    });
    await step('Orang tua mengirim bukti transfer', async () => {
        // Contoh bukti transfer dibuat dari HTML, supaya tidak memakai data bank sungguhan.
        const tmp = await parent.context().browser().newPage();
        await tmp.setViewportSize({ width: 420, height: 220 });
        await tmp.setContent('<div style="font:16px sans-serif;width:360px;padding:24px;border:2px solid #0b5;border-radius:12px"><b style="color:#0b5">TRANSFER SUCCESSFUL</b><p>To: Alprogmatics<br>Amount: Rp150,000<br>From: Mrs. Sakti<br>Ref: DEMO-123456</p></div>');
        await tmp.screenshot({ path: proof });
        await tmp.close();

        await btn(parent, 'Send transfer proof').click();
        await parent.fill('#pay_bank', 'BCA');
        await parent.fill('#pay_holder', 'Mrs. Sakti');
        await parent.setInputFiles('#pay_proof', proof);
        await shot(parent, '24-ortu-kirim-bukti');
        await parent.getByRole('button', { name: 'Send', exact: true }).click();
        await parent.getByText('Transfer proof sent').waitFor();
        await shot(parent, '25-ortu-bukti-terkirim');
    });
    await step('Admin memverifikasi pembayaran', async () => {
        await go(admin, '/finance');
        await shot(admin, '26-admin-keuangan', { full: true });
        await go(admin, '/finance/invoices');
        await admin.locator('tr', { hasText: 'Scratch workshop' }).first().click();
        await admin.waitForURL(/\/finance\/invoices\/\d+$/);
        await shot(admin, '27-admin-cek-bukti', { full: true });
        await btn(admin, 'Verify').click();
        await admin.getByText('Payment verified').waitFor();
        await shot(admin, '28-admin-lunas');
        await link(admin, 'Receipt').click();
        await admin.waitForURL(/receipt/);
        await shot(admin, '29-kuitansi', { full: true });
    });
    await step('Honor guru', async () => {
        await go(admin, '/finance/payroll');
        await shot(admin, '30-admin-honor', { full: true });
    });
});

// ───────────────────── F. Guru: materi, sesi, penilaian ─────────────────────
await chapter('f', async () => {
    const teacher = await as('budi');
    await step('Beranda guru', async () => {
        await shot(teacher, '31-guru-beranda', { full: true });
    });
    await step('Course dan isi level', async () => {
        await go(teacher, '/courses');
        await shot(teacher, '32-guru-course');
        await link(teacher, 'Python Start 1').click();
        await teacher.waitForURL(/\/courses\/\d+$/);
        await shot(teacher, '33-guru-isi-course', { full: true });
    });
    await step('Tambah task Theory', async () => {
        await link(teacher, 'Theory').click();
        await teacher.waitForURL(/lessons\/create/);
        await teacher.fill('#title', 'Theory: Loops recap');
        await teacher.fill('#body', '## Loops\n\nA `for` loop repeats code for every item:\n\n```python\nfor i in range(3):\n    print("Hi", i)\n```');
        await shot(teacher, '34-guru-form-theory', { full: true });
        await btn(teacher, 'Save').click();
        await teacher.waitForURL(/\/lessons\/\d+$/);
        await shot(teacher, '35-guru-theory-tersimpan');
    });
    await step('Akses lesson per kelas', async () => {
        await go(teacher, '/classes');
        await link(teacher, 'Python Start 1').click();
        await teacher.waitForURL(/\/classes\/\d+$/);
        await link(teacher, 'Lesson access').click();
        await teacher.waitForURL(/\/lessons$/);
        await shot(teacher, '36-guru-akses-lesson', { full: true });
    });
    await step('Sesi dan absensi', async () => {
        await go(teacher, '/sessions');
        await shot(teacher, '37-guru-sesi');
        await go(teacher, '/sessions?tab=completed');
        await teacher.locator('main a[href*="/sessions/"]').first().click();
        await teacher.waitForURL(/\/sessions\/\d+$/);
        await shot(teacher, '38-guru-isi-sesi', { full: true });
    });
    await step('Laporan bulanan', async () => {
        await go(teacher, '/monthly-reports');
        await shot(teacher, '39-guru-laporan-bulanan');
    });

    const scratchTeacher = await as('sari');
    await step('Penilaian tugas', async () => {
        await go(scratchTeacher, '/grading');
        await shot(scratchTeacher, '40-guru-penilaian', { full: true });
        await scratchTeacher.locator('input[id^="score-"]').first().fill('90');
        await scratchTeacher.locator('textarea[aria-label^="Feedback for"]').first().fill('Great game! Try adding a timer next.');
        await btn(scratchTeacher, 'Save grade').click();
        await scratchTeacher.getByText('Grade saved').waitFor();
    });
    await step('Tugas Scratch: form', async () => {
        await go(scratchTeacher, '/courses');
        await link(scratchTeacher, 'Scratch Creator 1').click();
        await scratchTeacher.waitForURL(/\/courses\/\d+$/);
        await link(scratchTeacher, 'Assignment').click();
        await scratchTeacher.waitForURL(/lessons\/create/);
        await scratchTeacher.fill('#title', 'Project: My dancing cat');
        await scratchTeacher.fill('#body', 'Make the cat dance to music with at least 3 moves. Press Submit when you are done.');
        await scratchTeacher.selectOption('#workspace', 'scratch');
        await shot(scratchTeacher, '41-guru-tugas-scratch', { full: true });
        await btn(scratchTeacher, 'Save').click();
        await scratchTeacher.waitForURL(/\/lessons\/\d+$/);
    });
    await step('Guru membuka semua lesson untuk kelasnya', async () => {
        await go(scratchTeacher, '/classes');
        await link(scratchTeacher, 'Scratch Game Maker').click();
        await scratchTeacher.waitForURL(/\/classes\/\d+$/);
        await link(scratchTeacher, 'Lesson access').click();
        await scratchTeacher.waitForURL(/\/lessons$/);
        await btn(scratchTeacher, 'Open all').click();
        await scratchTeacher.getByText('Every lesson in this level is open').waitFor();
        await shot(scratchTeacher, '42-guru-buka-semua-lesson', { full: true });
    });
});

// ───────────────────── G. Siswa: belajar, berkarya, berbagi ─────────────────────
await chapter('g', async () => {
    const student = await as('aldi');
    const ready = async (page) => {
        await page.getByText('Opening Scratch…').first().waitFor({ timeout: 15000 }).catch(() => {});
        await page.getByText('Opening Scratch…').first().waitFor({ state: 'detached', timeout: 90000 });
        await page.waitForTimeout(1200);
    };
    await step('Beranda siswa', async () => {
        await shot(student, '43-siswa-beranda', { full: true });
    });
    await step('Peta Course (layar penuh)', async () => {
        await student.locator('header nav').getByRole('link', { name: 'Course', exact: true }).click();
        await student.waitForURL('**/courses**');
        await student.waitForTimeout(800);
        await shot(student, '44-siswa-peta-course');
        await shot(student, '45-siswa-peta-course-penuh', { full: true });
    });
    await step('Popup Lesson dan task', async () => {
        // Lingkaran Lesson yang menyala (Up next) membuka daftar task-nya.
        await student.locator('button[aria-haspopup="dialog"]').nth(3).click({ force: true });
        await student.waitForTimeout(500);
        await shot(student, '46-siswa-popup-lesson');
        await student.keyboard.press('Escape');
    });
    await step('Theory dalam mode fokus', async () => {
        await go(student, '/search?q=Theory');
        await shot(student, '47-siswa-cari');
        await student.locator('main a[href*="/lessons/"]').first().click();
        await student.waitForURL(/\/lessons\/\d+$/);
        await shot(student, '48-siswa-theory');
    });
    await step('Kuis', async () => {
        await go(student, '/search?q=Quiz');
        await student.locator('main a[href*="/lessons/"]').first().click();
        await student.waitForURL(/\/lessons\/\d+$/);
        await shot(student, '49-siswa-kuis', { full: true });
    });
    await step('Soal coding', async () => {
        await go(student, '/search?q=Code Practice');
        await student.locator('main a[href*="/lessons/"]').first().click();
        await student.waitForURL(/\/lessons\/\d+$/);
        await student.waitForTimeout(1500);
        await shot(student, '50-siswa-coding');
    });
    await step('Tugas Scratch: buat dan kumpulkan', async () => {
        // Tugas yang dibuat guru Sari di bab F (dikerjakan di editor Scratch).
        await go(student, '/search?q=My dancing cat');
        await student.locator('main a[href*="/lessons/"]').first().click();
        await student.waitForURL(/\/lessons\/\d+$/);
        await ready(student);
        await shot(student, '51-siswa-tugas-scratch');
        await student.locator('#body').fill('My cat dances three moves!');
        // Layar penuh untuk membuat proyek; Submit ada di bar editor.
        const editor = student.locator('section[aria-labelledby="scratch-title"]');
        await editor.getByRole('button', { name: 'Fullscreen' }).click();
        await student.waitForTimeout(800);
        await shot(student, '51b-siswa-tugas-scratch-penuh');
        await editor.getByRole('button', { name: 'Submit' }).click();
        await student.getByText('Assignment submitted').waitFor({ timeout: 60000 });
        await shot(student, '52-siswa-tugas-terkirim');
    });
    await step('Laboratory: proyek Python', async () => {
        await go(student, '/lab');
        await shot(student, '53-siswa-lab');
        await btn(student, 'New project').click();
        await shot(student, '54-siswa-lab-pilih-jenis');
        await student.fill('#lab-title', 'My times table');
        await btn(student, 'Create and open').click();
        await student.waitForURL(/\/lab\/\d+$/);
        await student.getByRole('button', { name: 'Run', exact: true }).click();
        await student.getByText('Step 3').waitFor({ timeout: 90000 });
        await shot(student, '55-siswa-lab-python');
        await btn(student, 'Save & share').click();
        await student.getByText('Shared to the Community').waitFor();
    });
    await step('Laboratory: Scratch dan presentasi', async () => {
        await go(student, '/lab');
        await btn(student, 'New project').click();
        await student.locator('label', { hasText: 'Scratch' }).first().click();
        await student.fill('#lab-title', 'Cat dance');
        await btn(student, 'Create and open').click();
        await student.waitForURL(/\/lab\/\d+$/);
        await ready(student);
        await shot(student, '56-siswa-lab-scratch');
        await btn(student, 'Save').click();
        await student.getByText('Project saved').waitFor();
        await go(student, '/lab');
        await btn(student, 'New project').click();
        await student.locator('label', { hasText: 'Presentation' }).first().click();
        await student.fill('#lab-title', 'About me');
        await btn(student, 'Create and open').click();
        await student.waitForURL(/\/lab\/\d+$/);
        await btn(student, 'Next').click();
        await shot(student, '57-siswa-lab-presentasi');
    });
    await step('Community: like dan komentar', async () => {
        await go(student, '/community');
        await shot(student, '58-siswa-community');
        await link(student, 'Bouncing ball').click();
        await student.waitForURL(/\/community\/\d+$/);
        await ready(student);
        // Data demo bisa sudah berisi like dari Aldi; klik hanya kalau belum disukai.
        const like = student.getByRole('button', { name: 'Like', exact: true });
        if (await like.count()) await like.click();
        await student.fill('#c-body', 'Cool bounce! How did you make it say Hello?');
        await btn(student, 'Post comment').click();
        await student.getByText('Comment posted').waitFor();
        await shot(student, '59-siswa-community-proyek');
    });
    await step('Q&A: bertanya ke guru', async () => {
        await go(student, '/questions/new');
        await btn(student, 'Python Start 1').click();
        await student.getByPlaceholder('What are you stuck on?').fill('Why does range(3) stop at 2?');
        await student.getByPlaceholder('I tried… but what happened was…').fill('I wrote for i in range(3): print(i) and it printed 0, 1, 2. Where is 3?');
        await shot(student, '60-siswa-tanya', { full: true });
        await btn(student, 'Send question').click();
        await student.getByText('Question sent').waitFor();
    });
    await step('Proyek kanban', async () => {
        await go(student, '/projects');
        await student.locator('main a[href*="/projects/boards/"]').first().click();
        await student.waitForURL(/\/projects\/boards\/\d+$/);
        await student.locator('button[title="Move right"]').first().click();
        await student.waitForTimeout(600);
        await shot(student, '61-siswa-papan-proyek', { full: true });
    });
    await step('Online Lesson, Archive, prestasi, sertifikat', async () => {
        await go(student, '/schedule');
        await shot(student, '62-siswa-online-lesson');
        await go(student, '/archive');
        await shot(student, '63-siswa-archive');
        await go(student, '/achievements');
        await shot(student, '64-siswa-prestasi', { full: true });
        await go(student, '/my-certificates');
        await student.locator('main a[href*="/my-certificates/"]').first().click();
        await student.waitForURL(/\/my-certificates\/\d+$/);
        await shot(student, '65-siswa-sertifikat');
    });
    await step('Katalog course dan I\'m interested', async () => {
        await go(student, '/courses/explore');
        await shot(student, '66-siswa-katalog');
        await student.getByRole('button', { name: /Data/ }).first().click();
        await student.locator('main a[href*="/courses/"]').first().click();
        await student.waitForURL(/\/courses\/\d+$/);
        await btn(student, "I'm interested").click();
        await student.getByText('Thanks!').waitFor();
        await shot(student, '67-siswa-tertarik');
    });
});

// ───────────────────── H. Guru menindaklanjuti siswa ─────────────────────
await chapter('h', async () => {
    const teacher = await as('budi');
    await step('Guru menjawab pertanyaan', async () => {
        await go(teacher, '/questions');
        await link(teacher, 'Why does range(3) stop at 2?').click();
        await teacher.waitForURL(/\/questions\/\d+$/);
        await teacher.locator('textarea').last().fill('range(3) counts 3 numbers starting from 0: 0, 1, 2. Use range(1, 4) to get 1, 2, 3.');
        await btn(teacher, 'Send answer').click();
        await teacher.getByText('Answer sent').waitFor();
        await shot(teacher, '68-guru-jawab', { full: true });
    });
    await step('Guru mengecek langkah proyek', async () => {
        await go(teacher, '/projects');
        await openFirst(teacher, /\/projects\/\d+$/);
        await shot(teacher, '69-guru-cek-proyek', { full: true });
        await btn(teacher, 'Approve').click();
        await teacher.getByText('Card approved').waitFor();
    });
    const student = await as('aldi');
    await step('Siswa memilih jawaban terbaik', async () => {
        await go(student, '/questions');
        await link(student, 'Why does range(3) stop at 2?').click();
        await btn(student, 'Pick as best answer').click();
        await student.getByText('Best answer picked').waitFor();
        await shot(student, '70-siswa-jawaban-terbaik');
    });
});

// ───────────────────── I. Orang tua ─────────────────────
await chapter('i', async () => {
    const parent = await as('ortu1');
    await step('Beranda orang tua', async () => {
        await shot(parent, '71-ortu1-beranda', { full: true });
    });
    await step('Rapor progres anak', async () => {
        await go(parent, '/progress');
        await parent.locator('main a[href*="/progress/"]').first().click();
        await parent.waitForURL(/\/progress\/\d+$/);
        await shot(parent, '72-ortu1-rapor', { full: true });
    });
    await step('Laporan bulanan', async () => {
        await go(parent, '/monthly-reports');
        await parent.locator('main a[href*="/monthly-reports/"]').first().click();
        await parent.waitForURL(/\/monthly-reports\/\d+\/\d{4}-\d{2}$/);
        await shot(parent, '73-ortu1-laporan-bulanan', { full: true });
    });
});

// ───────────────────── J. Admin: pantauan dan tampilan HP ─────────────────────
await chapter('j', async () => {
    const admin = await as('admin');
    await step('Notifikasi dan audit', async () => {
        await go(admin, '/notifications');
        await shot(admin, '74-admin-notifikasi');
        await go(admin, '/audit');
        await shot(admin, '75-admin-audit');
    });
    const guest = await as(null);
    const student = await as('aldi');
    await step('Verifikasi sertifikat publik', async () => {
        await go(student, '/my-certificates');
        await student.locator('main a[href*="/my-certificates/"]').first().click();
        await student.waitForURL(/\/my-certificates\/\d+$/);
        const code = await student.evaluate(() => document.body.innerText.match(/\/certificates\/([A-Z0-9]{10})/)?.[1]);
        if (!code) throw new Error('kode sertifikat tidak ditemukan di halaman');
        await go(guest, `/certificates/${code}`);
        await shot(guest, '76-verifikasi-sertifikat');
    });
    const phone = await as('aldi', { mobile: true });
    await step('Tampilan HP', async () => {
        await shot(phone, '77-hp-beranda');
        await go(phone, '/courses');
        await phone.waitForTimeout(800);
        await shot(phone, '78-hp-peta');
    });
});

await finish();
