# AI_CONTEXT: Alprogmatics LMS

Handoff file for any AI coding agent (Cursor, Codex, ChatGPT, Claude, Copilot, …) that continues this project without access to earlier conversations. Read this whole file first, then `CLAUDE.md`, `README.md` and `PRODUCT.md`.

Last updated: 2026-10-12, at `main` commit `49a04a2` (PR #6 merged). All earlier PRs (#1 to #6) are merged; nothing is in flight.

---

## 0. Paste-ready opening prompt

Copy everything inside the block into the other AI as its first message.

```text
You are continuing development of "Alprogmatics LMS", a Laravel 13 + Inertia 2 + Vue 3 + Tailwind 4
modular-monolith learning management system for a private online coding school for children
(classes on Zoom, payment by manual bank transfer). Repository: https://github.com/sanseru/lms-app (branch main).

Before writing any code:
1. Read AI_CONTEXT.md at the repo root completely, then CLAUDE.md, README.md and PRODUCT.md.
2. Run the app locally (section 3 of AI_CONTEXT.md) and run `php artisan test` to confirm the suite is green.
3. Pick the first unfinished task in section 9 ("Next tasks, in order") unless I tell you otherwise.

Hard rules:
- Every user-visible string (UI, validation, notifications, seed/demo data, URL segments) is ENGLISH.
  Docs in docs/ and code comments may be Indonesian. Talk to me (the owner) in Indonesian.
- New features go in Modules/<Name>, never in app/. Follow the module structure in AI_CONTEXT.md section 5.
- Permissions are declared in the module provider's permissions() and enforced with `can:` middleware or
  Policies. Admin bypasses all gates, so business-state rules must be checked explicitly.
- Thin controllers, validation in Form Requests, reusable write logic in Actions/.
- Vue 3 <script setup> + Tailwind 4, reuse resources/js/Components/ui and the btn-*/card/field/label/eyebrow
  utilities. Highlight panels use only `panel-brand` (the logo gradient blue → violet → coral, client request); no other gradients, no white text on coral, no emoji as UI icons.
- Every feature needs Pest tests in tests/Feature/Modules. Run `php artisan test` and `vendor/bin/pint`
  before every commit. Eager-load relations (lazy loading throws outside production).
- Never commit secrets or .env. Work on a branch and open a pull request; do not push straight to main.
- When a module changes, update README.md and the matching docs/ page in the same PR.

When you finish a task: tell me (in Indonesian) what changed, how you tested it, and what the next task is.
```

---

## 1. Product in one page

**Alprogmatics** is an LMS for a coding course for school children (about 6 to 17 years old), taught privately or in small groups over Zoom. The app is the course's "home" around Zoom, not a Zoom replacement.

Four roles (Spatie Permission role names in brackets):

| Role | Who | What they do in the app |
|---|---|---|
| Admin (`admin`) | Course operator | Students, teachers, guardians, classes, schedules, invoices, payment verification, quota packages, tutor fees, leads, audit log |
| Teacher (`teacher`) | Tutor | Attendance + session notes on one screen, grading, Q&A answers, project card reviews, writing coding exercises, trial classes |
| Student (`student`) | Child, main user | Next class + Zoom link, lessons, quizzes, coding exercises run in the browser, Q&A, Kanban project board, Laboratory, Community, XP/badges, certificates |
| Guardian (`guardian`) | Parent, pays | Child's attendance, session notes, progress, remaining quota, invoices, upload bank transfer proof, monthly report |

Business facts (decided by the owner, do not change without asking):

- Classes happen on Zoom; Zoom links are entered manually per class/session (no Zoom API yet).
- Payment is **manual bank transfer**: guardian uploads proof, admin verifies. **No payment gateway** (deliberate).
- Prepaid **quota packages** (e.g. 8/16/24 sessions). Present/absent deducts a session; excused/sick does not.
- Tutor fee per completed session (`TeacherProfile.rate_per_session`), bundled into monthly slips.
- Public self-registration is off. Admin creates accounts. Login by **username or email** (children may have no email). Public `/free-trial` form creates leads.
- Locale `en`, timezone `Asia/Jakarta`, currency Rupiah.

Success means: children come back on their own, parents trust the reports and pay on time, teachers need no other tool. Product principles, audience, accessibility targets (WCAG 2.2 AA, 44px touch targets, reduced motion) and brand are in `PRODUCT.md`.

---

## 2. Stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3+, Laravel 13, Inertia.js 2 (server adapter), Ziggy (`route()` in Vue), Sanctum, Breeze auth (customized) |
| Auth/permissions | spatie/laravel-permission, `Gate::before` gives admin everything |
| Frontend | Vue 3 `<script setup>`, Inertia Vue 3, Tailwind CSS 4 (`@tailwindcss/vite`, `@tailwindcss/forms`), Vite 8, lucide-vue-next icons, CodeMirror 6, qrcode |
| Coding runner | Pyodide (self-hosted, copied to `public/vendor/pyodide` by `scripts/copy-pyodide.mjs`), Web Workers for JS, sandboxed iframe for HTML/CSS. Optional Judge0 driver |
| Tests | Pest (205 tests, all passing on 2026-10-12), SQLite in memory |
| Lint | Laravel Pint |
| DB | SQLite by default; MySQL/PostgreSQL supported by Laravel but **not yet tested** |
| Design | Tailwind tokens in `resources/css/app.css`; Impeccable design skill vendored in `.claude/skills/impeccable` |

CI: `.github/workflows/ci.yml` runs Pint and `npm ci && npm run build && php artisan test` on every pull request and on pushes to `main`.

---

## 3. Run, test, build

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed        # roles, permissions and demo data (skipped when APP_ENV=production)
npm run dev                       # or: npm run build. Both copy Pyodide and install the Scratch editor into public/vendor first
php artisan serve                 # http://127.0.0.1:8000
php artisan schedule:work         # optional locally; production uses cron `schedule:run` every minute
```

- Tests: `php artisan test` (or `composer test`). Format: `vendor/bin/pint`.
- After adding permissions to a module: `php artisan db:seed --class="Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder"`.
- Fresh demo DB: `php artisan migrate:fresh --seed`.
- `.env` keys specific to this app (names only): `APP_TIMEZONE`, `FINANCE_BANK_NAME`, `FINANCE_BANK_ACCOUNT`, `FINANCE_BANK_HOLDER`, `CODING_DRIVER` (`browser` default, or `judge0`), `JUDGE0_URL`, `JUDGE0_TOKEN`, `GAMIFICATION_LEADERBOARD`.
- Production install, Nginx, cron, backup, deploy, security: `docs/production.md` (Indonesian).

Scheduled commands (`routes/console.php`):

| Command | When | Purpose |
|---|---|---|
| `sessions:generate` | daily 01:00 | Create class sessions ~4 weeks ahead from weekly schedules |
| `xp:sync` | daily 01:30 | Recompute XP and badges from source data |
| `sessions:remind-reports` | hourly | Notify teachers who have not filled in session reports |
| `reports:monthly` | 1st of month 08:00 | Tell guardians last month's report is ready |

### Demo accounts (password `password`)

| Role | Usernames |
|---|---|
| Admin | `admin` |
| Teacher | `budi`, `sari`, `rizky` |
| Student | `aldi`, `nadia`, `raka`, … |
| Guardian | `ortu1` … `ortu5` |

Production seeding creates **no** users; the first admin is created in `php artisan tinker` (see `docs/production.md`).

---

## 4. Modules and what is done

All modules live in `Modules/<Name>` and are enabled in `config/modules.php`. URL prefixes are English; **route names were kept** from the Indonesian era (e.g. `forum.*` lives at `/questions`), so always use `route('name')`, never hard-coded paths.

| Module | URL(s) | State | Notes |
|---|---|---|---|
| Identity | `/login` | Done | Roles, permissions, username-or-email login, inactive users blocked, login/failed-login audited |
| Dashboard | `/dashboard` | Done | One URL, different page per role |
| People | `/people/students`, `/teachers`, `/guardians` | Done | Auto codes `SIS-xxxxx`, `GRU-xxxx`; guardian ↔ child links |
| Classes | `/classes`, `/schedule` | Done | Zoom link, weekly schedule, capacity, teacher and student clash checks |
| Sessions | `/sessions`, `/calendar/{token}.ics` | Done | Generated sessions, one-screen attendance + notes + homework + parent note, cancel, **reschedule and make-up sessions** linked to the original (`replaces_session_id`), personal iCal feed. Event `SessionCompleted` drives Finance |
| Learning | `/courses`, `/lessons/{id}`, `/grading`, `/progress`, `/lesson-reports` | Done | Courses attached to classes (`class_rooms.course_id`), Markdown/video lessons, quizzes (single/multiple choice, true/false, short answer, timer via `QuizTimer`), assignments (text/link/file) with grade or revision, progress report, pre/post missions with normalized gain per concept, student **Course** page with winding learning map (`CourseMap.vue`, START → Finish), "Report a problem" on lessons. Students open lessons in **focus mode** (`resources/js/Layouts/FocusLayout.vue`, chosen per page by `lessonLayout.js` when `can.learn`), coding/Lab editors have a fullscreen toggle (`resources/js/lib/fullscreen.js`), and `/courses/explore` is a **course catalog** with read-only previews and an "I'm interested" button (`course_interests`, `RegisterCourseInterest`). Lesson types are pluggable via `Modules/Learning/Support/LessonTypes.php` |
| Coding | lesson type `code` | Done (stage 1) | Python, JavaScript, HTML/CSS, Parsons. Runs in the browser; the server compares outputs with answers kept only on the server (hidden tests never sent). Mandatory "Test solution" before publishing, tiered hints, solution after N failures, code rules, exercise versions. Grader: `Modules/Coding/Grading/Grader.php`; runners in `Modules/Coding/resources/js/lib/` |
| Forum (Q&A) | `/questions` | Done | Per class, optional lesson link, private ("teacher only") and anonymous-to-peers, teacher answers pinned first, accepted answer, votes, pin, guardian read-only, "Ask your teacher" card on each lesson |
| Kanban | `/projects` | Done | Teacher project per class with template steps; each student gets a synced board (todo/doing/review/done), teacher review queue, archived projects lock boards |
| Lab | `/lab`, `/community` | Done | Free projects: Python, **Scratch** (self-hosted Scratch editor in an iframe, `ScratchFrame.vue` + `resources/scratch/host.html`, `.sb3` on the private disk, served by `community.file`), **Presentation** (markdown slides split by `---`, `SlideShow.vue`), Website, JavaScript. Save & share to Community (trending Today/Week/Month, likes, unique views, comments by anyone signed in, teacher moderation, names shown as first name + initial). XP rules `lab_shared`, `lab_liked`, `lab_commented`. Permissions `lab.use`, `community.moderate` |
| Gamification | `/achievements` | Done | XP events recomputed from source data (`XpRules`), levels `50n(n-1)`, 19 badges (6 named after the client prototype; a badge may count several rules, and 0-XP "milestone" rules such as `python_lesson` only feed badges), class leaderboard (toggle `GAMIFICATION_LEADERBOARD`). No daily streak yet |
| Certificates | `/my-certificates`, public `/certificates/{code}` | Done | A4 landscape, QR, verify page, revoke, auto list of students who finished a course |
| Finance | `/finance/*` | Done | Invoices (single or per class), proof upload, verify/reject, partial/paid status, quota packages, tutor payroll slips, printable receipt with amount in words, low-quota list, 6-month summary |
| Notifications | `/notifications` | Done (in-app only) | Bell + page; `Notify::send()` (`Modules/Notifications/Support/Notify.php`) with dedupe key. No WhatsApp/email channel yet |
| Reports | `/monthly-reports/{student}/{YYYY-MM}` | Done | Monthly guardian report (attendance, lessons, grades, projects, quota), teacher note per class per month, browser print to A4 PDF |
| Leads | public `/free-trial`, `/leads`, `/leads/trials` | Done | Lead pipeline, trial class scheduling and result, `ConvertLead` creates accounts and reuses guardian by phone/email |
| Audit | `/audit` | Done | Append-only `activity_log` (model throws on update/delete), `AuditTrail::watch(...)` lets any module register models, passwords masked |
| Landing | `/` | Done, **placeholder content** | Promo page; contacts, prices and testimonials in `Modules/Landing/resources/js/site.js` are samples |

---

## 5. Code structure and conventions

```
app/                         shared infrastructure only: User, Support/Modules, Support/Navigation, middleware, Breeze auth
config/modules.php           list of enabled module providers
Modules/<Name>/
  Providers/<Name>ServiceProvider.php   extends App\Support\Modules\ModuleServiceProvider:
                                        name(), permissions() [perm => roles], navigation(NavigationRegistry), boot()
  Http/Controllers, Http/Requests
  Models, Policies, Actions, Support, Events, Listeners, Console
  Database/Migrations, Database/Seeders (<Name>DemoSeeder)
  routes/web.php                        auto-wrapped in web + auth middleware
  routes/public.php                     web middleware, no auth (landing, verify, free-trial, iCal)
  resources/js/Pages/…                  rendered as Inertia::render('<Name>::Folder/Page')
  resources/js/Components/…
resources/js/Components/ui/  shared Vue components (Badge, StatCard, EmptyState, ConfirmDialog, PageHeader, Field, …)
resources/js/Layouts/        AppLayout.vue (sidebar built from module navigation, filtered by permission), GuestLayout.vue
resources/css/app.css        Tailwind 4 theme tokens and utilities: btn-primary, btn-accent, card, field, label, eyebrow, panel-brand, print styles
tests/Feature/Modules/       one Pest file per module; helpers (userWithRole, learningSetup, codingSetup, makeQuiz, …) in tests/Pest.php
docs/                        Indonesian: user guides per role (docs/panduan-pengguna), production guide, completeness audit, design audit
```

Adding a module: copy the shape of `Modules/Forum` (small and complete), register the provider in `config/modules.php`, migrate, rerun the permission seeder, add a Pest file, add a row to README's feature table and the user guide.

Cross-module extension points (prefer these over editing another module's internals):

- `Notify::send()` for notifications (with a dedupe key).
- `AuditTrail::watch(Model::class, category, fields, label, events, values)` to audit a model.
- `XpRules` to add XP rules from another module.
- `LessonTypes` registry to add a lesson type (this is how Coding plugs into Learning; Blockly should do the same).
- Events such as `SessionCompleted` (Sessions → Finance/Notifications).
- Coding grader drivers (`browser`, `judge0`) behind one contract.

Design rules (from `CLAUDE.md`, `PRODUCT.md`, `docs/design/`):

- Brand purple `#5B4185`, coral `#F58464`; fonts Plus Jakarta Sans (headings), DM Sans (body), DM Mono (code). Muted text `#5f6b75`.
- Gradients only through `panel-brand` (logo colours blue → violet → coral, requested by the client; keep text on its blue/left side, white buttons on it), no white text on coral (`btn-accent` = coral background + dark ink text), emoji only as content (badges, project icons), never as UI icons.
- Eyebrow labels stay in app pages (owner's decision), removed only on landing/login.
- Student screens can be playful; guardian and finance screens calm and scannable. Each dashboard answers "what do I do now" before statistics.
- Never invent testimonials, statistics or "trusted by" claims.
- No dark mode and no `DESIGN.md` yet.
- The friend's static prototype (`LMS-Alprogmatics`, outside this repo) was a visual reference only.

---

## 6. Decisions already made (do not reopen without the owner)

1. Modular monolith in one Laravel app, not microservices or an SPA + API split.
2. Manual bank transfer + admin verification; no QRIS/VA/payment gateway.
3. Admin-created accounts; no public registration; username-or-email login.
4. Student code runs **in the browser**; the server never executes student code (Judge0 on a separate VPS is the only server-side option, for C++/Java later).
5. Hidden tests and answers stay on the server.
6. XP is derived from source data and recomputed, so other modules never "award" XP imperatively.
7. Quota usage is derived from attendance rows.
8. Audit log is append-only.
9. Entire UI in English; `docs/` in Indonesian; URL segments English; route names unchanged.
10. Monthly report and certificates are printed to PDF from the browser (no server PDF library).
11. Learning structure follows the client prototype: **Program → level (course) → Lesson (`course_units`) → task (lesson item)**. Students move up levels step by step: the next level unlocks when every published item of the previous one is done, without changing class (`Modules/Learning/Support/LevelAccess.php`). "Mixed" tasks (multiple choice + code in one task) are split into separate tasks. Inside a level, lessons also open one after another (`LessonGate`); the class teacher can open lessons early per class at Classes → Lesson access (`class_lesson_opens`), which also opens a later level with just those lessons.
12. Client naming wins for what users see: student menus are Home, Course, Online Lesson, Community, Laboratory, Archive (top header, `StudentLayout`/`StudentHeader`, rest under More); teachers see Course and Online Lesson. The comparison and decisions are in `docs/analisis-prototipe-klien.md`. Everyone logged in may like and comment on shared Community projects.
13. Scratch is self-hosted (not a TurboWarp embed) so projects save to our server. Laboratory types are Python, Scratch, Presentation, Website, JavaScript; an assignment can use the Scratch editor as its workspace (`lessons.workspace = 'scratch'`).

---

## 7. Known traps

- **Tests need a built frontend and an app key.** Run `npm run build` (creates `public/build/manifest.json`) and have `.env` with `APP_KEY` before `php artisan test`; otherwise most page tests fail with "Vite manifest not found" or "No application encryption key".
- **Lazy loading throws** outside production (`Model::preventLazyLoading`). Eager-load everything used in collections; a test with one record will not catch it.
- **Admin bypasses every gate** (`Gate::before`). Business-state rules (paid invoice cannot be paid, archived project is locked, etc.) must be checked explicitly in Actions/requests.
- **Route names vs URLs**: names are legacy (`forum.*`, `kanban.*`, `gamification.*`, `certificates.*`), URLs are English. Use `route()`.
- **New permissions do nothing** until the permission seeder runs (also in production after deploy).
- **Pyodide** must be present in `public/vendor/pyodide`; `npm run build`/`dev` copy it. Running plain `vite` skips it.
- **Scratch editor** lives in `public/vendor/scratch-gui` (~100 MB, git-ignored), installed by `scripts/fetch-scratch-gui.mjs` (pinned `@scratch/scratch-gui`, downloaded once with `npm pack`, publicPath patched to `/vendor/scratch-gui/`). CI sets `SCRATCH_GUI_SKIP=1`. Without it the Scratch pages show "The Scratch editor is not installed". Sprite/sound library thumbnails still load from Scratch's asset server. `resources/scratch/host.html` is the app's own page and is copied there on every build.
- **Demo seeding is skipped** in production; the first admin comes from tinker.
- **Audit logging pauses** during `db:seed`/`migrate:fresh`; `activity_log` rows cannot be updated or deleted (by design).
- **Uploads** use the `local` disk directly in code; switching to S3/object storage needs code changes.
- **Trusted proxies** are not configured; needed behind Cloudflare/a load balancer.
- **Three databases are tested.** CI runs the suite on SQLite, MySQL 8 and PostgreSQL 16. Use `whereLike()` for user searches (PostgreSQL `LIKE` is case-sensitive), avoid `max()`/`min()` on boolean columns, and don't query `notifications.data` with JSON paths (it is a text column).
- **Client trial guide** (`docs/panduan-uji-coba/`): regenerate with the `/panduan-uji-coba` skill (`.claude/skills/panduan-uji-coba/SKILL.md`): `scripts/uji-coba/reset.sh` (wipes the local DB!), `walkthrough.mjs` (Playwright, fills real forms), `check.mjs`, `build-pdf.mjs`. The PDF in `dist/` is not committed.
- **User guide screenshots** in `docs/panduan-pengguna/img/` come from the seeded demo data (`migrate:fresh --seed`) at 1366 px (desktop) and 390 px (phone), quantized to 256 colours. Retake them when a screen changes visibly.
- `lang/id` still exists but is unused (`APP_LOCALE=en`).
- Landing page data in `site.js` (WhatsApp number `6281234567890`, email, prices, testimonials marked as samples) is **fake** and must not go live.

---

## 8. What needs the owner (cannot be finished by an AI alone)

| Item | What is needed from the owner |
|---|---|
| Real landing content | WhatsApp number, email, opening hours, prices per program, real testimonials (or remove the section), program list confirmation |
| WhatsApp notifications | Provider choice and account (e.g. Fonnte, Wablas, WhatsApp Cloud API) and a sender number |
| Production deploy | Hosting/VPS, domain, database choice (MySQL or PostgreSQL), SMTP for password reset, bank account details for `FINANCE_BANK_*` |
| Judge0 (C++/Java) | A separate VPS for Judge0 CE (Docker, cgroup v1) |
| Zoom API, Google login, multi-branch | Business decision whether these are wanted |

---

## 9. Next tasks, in order

Each task is one PR on its own branch, with tests and docs updated. "Done when" is the acceptance criterion.

1. ~~**Sync stale docs.**~~ Done on branch `claude/elegant-mccarthy-yxr746`: `PRODUCT.md`, README, `docs/production.md`, `docs/audit-kelengkapan.md` and the user guides now describe the English UI with fresh screenshots.
2. ~~**Add CI.**~~ Done: `.github/workflows/ci.yml` (Pint job + build-and-test job, PHP 8.3, Node 22, SQLite).
3. ~~**Teacher link to student progress.**~~ Done: **Progress** links on the class roster (class teacher, admin) and the monthly reports list.
4. ~~**MySQL/PostgreSQL compatibility.**~~ Done: suite and demo seeding pass on SQLite, MySQL 8 and PostgreSQL 16; CI job `databases` runs both.
5. **Production hardening.** Configure trusted proxies via env, make the upload disk configurable (env-driven, works with S3-compatible storage), confirm cron/queue setup in `docs/production.md`, add a health check route.
   *Done when* the app runs behind a proxy with correct HTTPS URLs, uploads work on both `local` and `s3` disks (tested with `Storage::fake`), and docs describe it.
6. **Daily streak + PWA.** Streak derived from activity (same derive-and-recompute approach as XP) shown on the student dashboard and achievements page; web app manifest, icons and a minimal service worker for "add to home screen".
   *Done when* streak has tests (including timezone `Asia/Jakarta` day boundaries) and Lighthouse reports the app installable.
7. **Notification channels.** Introduce a channel abstraction that reads the existing `notifications` rows and sends via queued jobs: email first (Laravel Mail), then a WhatsApp driver (blocked on the owner's provider account, build it behind an interface with a `log` driver for development). User-level opt-in per channel. Queue worker becomes required; document it.
   *Done when* a notification created by `Notify::send()` is delivered by email in tests (`Mail::fake`) and by the WhatsApp driver to a faked HTTP client, with per-user preferences respected.
8. **Guardian reschedule request.** Guardians request to move a session (reason + preferred time); admin/teacher approve, which uses the existing `RescheduleSession` action and clash checks; guardian is notified.
   *Done when* request → approve/decline works end to end with tests and notifications.
9. **Blockly lesson type (ages 7 to 10).** New lesson type registered through `LessonTypes` (likely in a new `Modules/Blocks` or inside Coding), Blockly workspace in the browser, grading by comparing program output/state on the server like Coding, teacher authoring with mandatory "Test solution", appears on the Course map like other lessons.
   *Done when* a teacher can create, test and publish a Blockly exercise, a student can solve it from the Course map, the result counts toward progress and XP, with Pest tests.
10. **Replace landing placeholders** with the owner's real data (blocked on section 8).
    *Done when* `site.js` contains no sample values and the testimonial section shows only real, approved quotes or is hidden.
11. **Production deploy** following `docs/production.md` (blocked on section 8).
    *Done when* the app is live on the owner's domain with HTTPS, cron, backups and a first admin account.
12. **Later / optional:** Judge0 server for C++/Java; adaptive placement pre-test; live "teacher sees student code" mode during Zoom; assignment submission history; course sections and bookmarks; parent-teacher meeting reminder every N sessions; Google login and admin MFA; Zoom API; multi-branch with renamable terms; dark mode and `DESIGN.md`.

---

## 10. Where things are

| Need | Location |
|---|---|
| Working rules for agents | `CLAUDE.md` (Claude Code), `AGENTS.md` (Codex and others) |
| Feature list and module guide | `README.md` (Indonesian) |
| Product, users, brand, principles | `PRODUCT.md` |
| Production setup | `docs/production.md` |
| Feature completeness audit vs research | `docs/audit-kelengkapan.md` |
| Design audit | `docs/design/audit-impeccable-2026-10-11.md` |
| User guides per role | `docs/panduan-pengguna/` |
| Landing page content | `Modules/Landing/resources/js/site.js` |
| Theme tokens and utilities | `resources/css/app.css` |
| Test helpers | `tests/Pest.php` |
| Merged PR history | https://github.com/sanseru/lms-app/pulls?q=is%3Apr+is%3Amerged |
