// Landing page data. Change the WhatsApp number, email, prices and testimonials here.
export const site = {
    brand: 'Alprogmatics',
    waNumber: '6281234567890', // international format without +
    waDisplay: '+62 812-3456-7890',
    email: 'halo@alprogmatics.id',
    hours: 'Monday–Saturday, 8 am–8 pm WIB',
};

export function waLink(text = 'Hi, I have a question about your coding classes.') {
    return `https://wa.me/${site.waNumber}?text=${encodeURIComponent(text)}`;
}

export const trialText = "Hi, I'd like to book a free trial class for my child.";

export const navLinks = [
    { href: '#programs', label: 'Programs' },
    { href: '#how-it-works', label: 'How it works' },
    { href: '#reports', label: 'Parent reports' },
    { href: '#pricing', label: 'Pricing' },
    { href: '#faq', label: 'FAQ' },
];

export const features = [
    { icon: 'users', tone: 'brand', title: 'Small classes, full attention', body: 'One-to-one private lessons, or groups of up to 5 children of the same age and level.' },
    { icon: 'project', tone: 'accent', title: 'Learning by making', body: 'Every module ends with something real to show off: a game, an animation, an app or a website.' },
    { icon: 'check', tone: 'success', title: 'A report after every session', body: 'Attendance, what was covered and the teacher’s notes go straight to you. Plus a progress report at the end of each level.' },
    { icon: 'calendar', tone: 'accent', title: 'Flexible schedule', body: 'Pick the days and times that suit you, including afternoons and weekends. Easy to reschedule if your child can’t make it.' },
];

export const ageGroups = [
    { key: 'all', label: 'All ages' },
    { key: '6-9', label: 'Ages 6–9' },
    { key: '10-12', label: 'Ages 10–12' },
    { key: '13-17', label: 'Ages 13–17' },
];

export const programs = [
    { mark: 'Sc', bg: 'bg-amber-100 text-amber-800', ages: ['6-9'], range: 'Ages 6–9', title: 'Scratch Junior & Scratch', body: 'Coding with colourful blocks. Children create animated stories and simple games while learning sequences, loops and logic.', meta: '3 levels · 16 sessions per level' },
    { mark: 'GM', bg: 'bg-emerald-100 text-emerald-800', ages: ['6-9', '10-12'], range: 'Ages 8–12', title: 'Game Maker', body: 'Designing levels, characters and game rules. Perfect for kids who love Minecraft and Roblox.', meta: '2 levels · 16 sessions per level' },
    { mark: 'Py', bg: 'bg-orange-100 text-orange-800', ages: ['10-12', '13-17'], range: 'Ages 10–17', title: 'Python Pro', body: 'A real programming language. From variables and loops to building text games, quizzes and small automations.', meta: '4 levels · 32 lessons per level' },
    { mark: '{ }', bg: 'bg-cyan-100 text-cyan-800', ages: ['10-12', '13-17'], range: 'Ages 11–17', title: 'Web Developer', body: 'HTML, CSS and JavaScript. Your child builds their own portfolio website and interactive pages.', meta: '3 levels · 16 sessions per level' },
    { mark: 'AI', bg: 'bg-violet-100 text-violet-800', ages: ['13-17'], range: 'Ages 13–17', title: 'AI & Data Explorer', body: 'A safe, hands-on introduction to artificial intelligence: working with data, making charts and training simple models.', meta: '2 levels · 16 sessions per level' },
    { mark: 'An', bg: 'bg-green-100 text-green-800', ages: ['13-17'], range: 'Ages 13–17', title: 'Android & IoT', body: 'Building phone apps and smart-device projects, from automatic lights to temperature sensors.', meta: '2 levels · 16 sessions per level' },
];

export const steps = [
    { title: 'Chat with us on WhatsApp', body: 'Tell us your child’s age and interests. Our admin will help you choose a program and schedule.' },
    { title: 'Free trial class', body: 'One 45-minute Zoom session with a teacher. Your child builds a mini project and the teacher finds the right starting level.' },
    { title: 'Choose a package & pay', body: 'Pay by bank transfer and send us the transfer proof. Our admin confirms it and the schedule starts right away.' },
    { title: 'Learn & get reports', body: 'Your child joins Zoom on schedule. After each session, you receive notes on their progress.' },
];

// Sample text: replace with real testimonials before going live.
export const testimonials = [
    { initial: 'A', quote: 'My son usually gets bored quickly. Here he actually asks for extra sessions because he wants to finish his game.', name: 'Parent name', role: 'Mother of a Scratch student, 8' },
    { initial: 'B', quote: 'The reports after each class are so helpful. Now I can chat with my daughter about what she built that day.', name: 'Parent name', role: 'Father of a Python student, 12' },
    { initial: 'C', quote: 'The groups are small, so my child still gets plenty of attention. The teacher patiently explains things as many times as needed.', name: 'Parent name', role: 'Mother of a Web student, 14' },
];

// Sample prices (Rupiah). Adjust before going live.
export const plans = [
    { name: 'Starter', sub: '4 sessions · a great way to try', sessions: 4, price: { privat: 600000, kelompok: 350000 }, perks: ['A report after every session', 'Access to lessons & practice', 'Valid for 6 weeks'] },
    { name: 'Regular', sub: '8 sessions · 2 sessions a week', sessions: 8, price: { privat: 1120000, kelompok: 640000 }, perks: ['Everything in Starter', 'Progress report', '1 free reschedule', 'Valid for 10 weeks'], featured: true },
    { name: 'Full Level', sub: '16 sessions · complete a whole level', sessions: 16, price: { privat: 2080000, kelompok: 1200000 }, perks: ['Everything in Regular', 'Level completion certificate', 'Project showcase for parents', 'Valid for 20 weeks'] },
];

export const faqs = [
    { q: 'My child has never coded before. Can they join?', a: 'Absolutely. Most of our students start from zero. The teacher finds the right starting level during the free trial class.' },
    { q: 'What equipment is needed?', a: 'A laptop or computer with a camera, microphone and a stable internet connection. For ages 6–7, a tablet works for the early levels. The Zoom app is free.' },
    { q: 'What is the difference between private and group classes?', a: 'Private means one child with one teacher, with a schedule and pace fully tailored to your child. Groups have 3–5 children of the same age and level: more affordable, and your child gets to learn alongside friends.' },
    { q: 'How do I pay?', a: 'Transfer to the official account our admin sends you, then share a photo of the transfer proof on WhatsApp. Once confirmed, your session package is active straight away.' },
    { q: 'What if my child is sick or has something on?', a: 'Let our admin know at least 6 hours before class and the session is rescheduled without using up your package.' },
    { q: 'Do parents need to sit in?', a: 'For children under 8, we suggest helping them join Zoom at the start. After that, your child learns independently with the teacher.' },
];
