// ===== LESSON ENGINE (shared by all 32 lessons of this level) =====
// Everything a lesson page needs, in one file. Sections in order:
//   1) CONTENT REGISTRY  2) PROGRESS  3) LESSON PAGE (questions, code practice, theory)
// Styles: style.css (same folder as this file).  Content: lesson-N/lesson.js, theory.js, task-K.js.
// Search for '=====' to jump between sections.

// ===== CONTENT REGISTRY =====
// Content files in each lesson folder register themselves here:
//   course/python/<level>/lesson-N/lesson.js  -> PythonCourse.lesson(...)   (title, taskCount)
//   course/python/<level>/lesson-N/task-K.js  -> PythonCourse.task(...)     (questions of task K)
//   course/python/<level>/lesson-N/theory.js  -> PythonCourse.theory(...)   (reading text)
// Other files read the content from here: progress.js, lesson-modal.js, quiz.js

(function () {
  const lessons = {};
  const questions = {};
  const theories = {};
  // .../course/python (a lesson page sits two folders deeper: <level>/lesson-N/)
  const base = (document.body && document.body.dataset.level ? new URL("../../", location.href) : new URL("./", location.href)).href.replace(/\/$/, "");

  function loadScripts(urls) {
    return Promise.all(
      urls.map(
        (src) =>
          new Promise((resolve) => {
            const el = document.createElement("script");
            el.src = src;
            el.onload = el.onerror = resolve; // a missing file must not block the page
            document.head.appendChild(el);
          })
      )
    );
  }

  function loadScript(src) {
    return new Promise((resolve) => {
      const el = document.createElement("script");
      el.src = src;
      el.onload = () => resolve(true);
      el.onerror = () => resolve(false);
      document.head.appendChild(el);
    });
  }

  const lessonUrl = (levelId, n, file) => `${base}/${levelId}/lesson-${n}/${file}`;

  // Loads task-1.js, task-2.js, ... until one is missing, and sets taskCount to how many exist.
  // Add or delete a task file (keep the numbers consecutive) and the lesson updates by itself.
  async function loadTasks(levelId, n) {
    const def = (lessons[levelId] || {})[n];
    if (!def) return;
    let k = 0;
    while (await loadScript(lessonUrl(levelId, n, `task-${k + 1}.js`))) k++;
    def.taskCount = k;
  }

  // Public API used by lesson folders: PythonCourse.lesson(...) in lesson.js, PythonCourse.task(...) in task-K.js, PythonCourse.theory(...) in theory.js
  window.PythonCourse = {
    lessons,
    questions,
    theories,
    lesson(levelId, n, def) {
      (lessons[levelId] = lessons[levelId] || {})[n] = def;
    },
    theory(levelId, n, def) {
      (theories[levelId] = theories[levelId] || {})[n] = def;
    },
    // def = { title, questions } (a plain array of questions is also accepted)
    task(levelId, n, k, def) {
      ((questions[levelId] = questions[levelId] || {})[n] = questions[levelId][n] || {})[k] = Array.isArray(def) ? { title: "Task " + k, questions: def } : def;
    },
    // Map page: load every lesson.js + theory.js of every level, then count the task-K.js files
    async loadAllLessons(levelList) {
      const urls = [];
      levelList.forEach((l) => {
        for (let n = 1; n <= l.lessonCount; n++) urls.push(lessonUrl(l.id, n, "lesson.js"), lessonUrl(l.id, n, "theory.js"));
      });
      await loadScripts(urls);
      const jobs = [];
      levelList.forEach((l) => {
        for (let n = 1; n <= l.lessonCount; n++) jobs.push(loadTasks(l.id, n));
      });
      await Promise.all(jobs);
    },
    // Lesson page: load lesson.js + theory.js first, then every task-K.js of that lesson
    async loadLesson(levelId, n) {
      await loadScripts([lessonUrl(levelId, n, "lesson.js"), lessonUrl(levelId, n, "theory.js")]);
      await loadTasks(levelId, n);
    },
  };
})();

// ===== PROGRESS (used by the map, the popup and the question pages) =====
// Saves which tasks/questions/theory the student finished in the browser (localStorage) and gives access to level/lesson data.
// Used by: journey.js, lesson-modal.js, quiz.js
// To reset your own progress: clear the keys "algonova-python-*" in the browser localStorage.

(function () {
  const KEY = "algonova-python-progress-v2";

  function read() {
    try {
      return JSON.parse(localStorage.getItem(KEY)) || {};
    } catch {
      return {};
    }
  }

  // Levels with their lessons assembled from the content registry (lesson.js / task-K.js files).
  // (continues) levels(): merges course/python/<level>/data.js with each lesson.js / task-K.js
  function levels() {
    const C = window.PythonCourse;
    return (window.PYTHON_LEVELS || [])
      .slice()
      .sort((a, b) => a.order - b.order)
      .map((level) => ({
        ...level,
        lessons: Array.from({ length: level.lessonCount }, (_, i) => {
          const n = i + 1;
          const def = (C.lessons[level.id] || {})[n] || { title: "Lesson " + n, taskCount: 0 };
          return {
            title: def.title,
            heading: def.heading || `Tasks for lesson No. ${n}. ${def.title}`,
            tasks: Array.from({ length: def.taskCount || 0 }, (_, k) => {
              const t = ((C.questions[level.id] || {})[n] || {})[k + 1];
              return { title: t ? t.title : "Task " + (k + 1), questions: t ? t.questions : [] };
            }),
          };
        }),
      }));
  }

  function tasksOf(level, lessonIndex) {
    return level.lessons[lessonIndex].tasks;
  }

  function doneTasks(levelId, lessonIndex) {
    return (read()[levelId] || {})[lessonIndex] || [];
  }

  function markTaskDone(levelId, lessonIndex, taskIndex) {
    const data = read();
    const lessons = (data[levelId] = data[levelId] || {});
    const set = new Set(lessons[lessonIndex] || []);
    set.add(taskIndex);
    lessons[lessonIndex] = [...set];
    localStorage.setItem(KEY, JSON.stringify(data));
  }

  function isLessonDone(level, lessonIndex) {
    return tasksOf(level, lessonIndex).every((_, t) => doneTasks(level.id, lessonIndex).includes(t));
  }

  // Number of lessons completed in order from the start
  function completedCount(level) {
    let n = 0;
    while (n < level.lessons.length && isLessonDone(level, n)) n++;
    return n;
  }

  // Per-question state inside a task: { solved: [questionIndexes], attempts: {questionIndex: n} }
  const QKEY = "algonova-python-questions-v1";
  const readQ = () => {
    try {
      return JSON.parse(localStorage.getItem(QKEY)) || {};
    } catch {
      return {};
    }
  };
  const qId = (levelId, lessonIndex, taskIndex) => `${levelId}:${lessonIndex}:${taskIndex}`;
  const questionState = (levelId, lessonIndex, taskIndex) => readQ()[qId(levelId, lessonIndex, taskIndex)] || { solved: [], attempts: {} };
  function saveQuestionState(levelId, lessonIndex, taskIndex, state) {
    const all = readQ();
    all[qId(levelId, lessonIndex, taskIndex)] = state;
    localStorage.setItem(QKEY, JSON.stringify(all));
  }

  const TKEY = "algonova-python-theory-v1";
  const readT = () => {
    try {
      return JSON.parse(localStorage.getItem(TKEY)) || {};
    } catch {
      return {};
    }
  };
  const theoryRead = (levelId, lessonIndex) => !!readT()[`${levelId}:${lessonIndex}`];
  function markTheoryRead(levelId, lessonIndex) {
    const all = readT();
    all[`${levelId}:${lessonIndex}`] = true;
    localStorage.setItem(TKEY, JSON.stringify(all));
  }
  const theoryOf = (levelId, lessonIndex) => (window.PythonCourse.theories[levelId] || {})[lessonIndex + 1] || null;

  window.PythonProgress = { theoryRead, markTheoryRead, theoryOf, levels, tasksOf, doneTasks, markTaskDone, isLessonDone, completedCount, questionState, saveQuestionState };
})();


// ===== LESSON PAGE ENGINE (page: course/python/<level>/lesson-N/index.html) =====
// One file draws every question type: multiple choice, theory and code practice (Python editor).
// Styles: style.css   Content: task-K.js and theory.js   Progress: section 2 of this file
// Quick search: press Ctrl+F and look for "=====" to jump between sections.

(function () {
  const P = window.PythonProgress;
  const body = document.body;
  const lesson = Number(body.dataset.lesson) - 1;
  let level;
  let tasks;
  const root = document.getElementById("quiz");
  const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);
  const mapUrl = "../../index.html";
  const REFRESH = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11a8 8 0 1 0-2.3 6"/><path d="M20 4v7h-7"/></svg>';
  const LOCK = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M7 10V8a5 5 0 0 1 10 0v2h1a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1zm2 0h6V8a3 3 0 0 0-6 0z"/></svg>';

  let taskIndex = 0;
  let current = 0;
  let selected = [];
  let verdict = null; // null | "good" | "bad"
  let wrongSet = new Set();

  const state = () => P.questionState(level.id, lesson, taskIndex);
  const isMulti = (q) => Array.isArray(q.answer);
  const answerOf = (q) => (isMulti(q) ? q.answer : [q.answer]).slice().sort();

  function firstOpen() {
    const solved = state().solved;
    const i = tasks[taskIndex].questions.findIndex((_, n) => !solved.includes(n));
    return i === -1 ? 0 : i;
  }

  function select(i) {
    current = i;
    selected = [];
    verdict = null;
    wrongSet = new Set();
    render();
  }

  // ===== TOP BAR: back button + question circles (all question types)
  // Styles: style.css -> section "TOP BAR" =====
  function topBar() {
    const st = state();
    const questions = tasks[taskIndex].questions;
    const unlockedUpTo = Math.min(...[...questions.keys()].filter((n) => !st.solved.includes(n)).concat(questions.length));
    const circles = questions
      .map((_, n) => {
        const solved = st.solved.includes(n);
        const locked = !solved && n > unlockedUpTo;
        const cls = ["qc", solved ? "is-solved" : "", n === current ? "is-current" : "", locked ? "is-locked" : "", n === current && verdict === "bad" ? "is-wrong" : ""].join(" ");
        return `<button type="button" class="${cls}" data-n="${n}" ${locked ? "disabled" : ""} aria-label="Question ${n + 1}">${locked ? LOCK : n + 1}</button>`;
      })
      .join("");
    return `<a class="lp-back" href="${mapUrl}" aria-label="Back to course map" title="Back to course map"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg></a>
      <nav class="lp-circles" aria-label="Questions">${circles}</nav>
      <span class="lp-spacer"></span>`;
  }

  // ===== RENDER: picks the multiple-choice view or the code-practice view for the current question =====
  function render() {
    const task = tasks[taskIndex];
    const q = task.questions[current];
    const st = state();
    const solved = st.solved.includes(current);
    const attempts = st.attempts[current] || 0;
    root.classList.toggle("is-code", !!q.code);
    document.body.classList.toggle("code-mode", !!q.code);
    if (q.code) return renderCode(task, q, solved, attempts);
    const multi = isMulti(q);
    const correct = answerOf(q);
    const showSolution = solved && !selected.length;
    const picked = showSolution ? correct : selected;

    document.getElementById("lpBar").innerHTML = topBar();
    root.innerHTML = `
      <div class="lc-head ${solved ? "is-solved" : ""}">
        <h1>${solved ? '<i class="lc-ok">✓</i>' : ""}Part ${taskIndex + 1}. ${esc(task.title)}</h1>
        <span class="lc-attempts">Attempts: ${attempts}</span>
      </div>
      <p class="lc-question">${esc(q.q)}</p>
      ${multi ? '<p class="lc-hint">Choose all that apply</p>' : ""}
      <div class="lc-options">
        ${q.options
          .map((o, j) => {
            const on = picked.includes(j);
            const mark = verdict && on ? (correct.includes(j) ? "is-right" : "is-bad") : "";
            return `<label class="lc-option ${on ? "is-on" : ""} ${mark}"><input type="${multi ? "checkbox" : "radio"}" name="opt" value="${j}" ${on ? "checked" : ""} ${solved ? "disabled" : ""} /><span class="lc-box">${on ? "✓" : ""}</span><span class="lc-text">${esc(o)}</span></label>`;
          })
          .join("")}
      </div>
      <p class="lc-result ${verdict || (solved ? "good" : "")}" role="status">${
        verdict === "bad" ? "Not quite right. Try again!" : verdict === "good" || solved ? "✅ Correct!" : ""
      }</p>
      <div class="lc-actions">
        ${solved ? `<button type="button" class="lc-btn" id="lcNext">${nextLabel()}</button>` : '<button type="button" class="lc-btn" id="lcCheck">Check</button>'}
        <button type="button" class="lc-refresh" id="lcRefresh" aria-label="Refresh question" title="Refresh question">${REFRESH}</button>
      </div>`;

    document.querySelectorAll(".qc").forEach((b) => (b.onclick = () => select(Number(b.dataset.n))));
    root.querySelectorAll(".lc-option input").forEach((inp) => {
      inp.onchange = () => {
        const j = Number(inp.value);
        selected = multi ? (inp.checked ? [...selected, j] : selected.filter((x) => x !== j)) : [j];
        verdict = null;
        render();
      };
    });
    document.getElementById("lcRefresh").onclick = () => select(current);
    const check = document.getElementById("lcCheck");
    if (check) check.onclick = () => submit(q, correct);
    const next = document.getElementById("lcNext");
    if (next) next.onclick = advance;
  }

  // ===== CODE PRACTICE: from here until "nextLabel" is the Python editor task
  // Styles: style.css -> section "CODE PRACTICE" (classes .cp-*)
  // Content: lesson-N/task-K.js (N = lesson number, K = task number) =====
  // ---- Code practice question: { q, code: { starter, expected, input? } } ----
  const SKULPT = ["https://cdn.jsdelivr.net/npm/skulpt@1.2.0/dist/skulpt.min.js", "https://cdn.jsdelivr.net/npm/skulpt@1.2.0/dist/skulpt-stdlib.js"];
  let skulptReady = null;
  function loadSkulpt() {
    if (window.Sk) return Promise.resolve();
    skulptReady =
      skulptReady ||
      SKULPT.reduce(
        (p, src) =>
          p.then(
            () =>
              new Promise((resolve, reject) => {
                const el = document.createElement("script");
                el.src = src;
                el.onload = resolve;
                el.onerror = () => reject(new Error("Could not load the Python runner. Check your internet connection."));
                document.head.appendChild(el);
              })
          ),
        Promise.resolve()
      );
    return skulptReady;
  }

  
  // Runs Python; resolves { output, error }
  // Runs the student code with Skulpt and returns { output, error }
  // dbgLine (optional): async function called before each line (step-by-step mode); it resolves when the next line may run
  async function runPython(source, inputs, onOutput, dbgLine) {
    await loadSkulpt();
    let output = "";
    const queue = (inputs || []).slice();
    Sk.configure({
      output: (t) => {
        output += t;
        onOutput(output);
      },
      read: (x) => {
        if (!Sk.builtinFiles || !Sk.builtinFiles.files[x]) throw "File not found: '" + x + "'";
        return Sk.builtinFiles.files[x];
      },
      inputfun: (prompt) => {
        const v = queue.length ? queue.shift() : "";
        output += prompt + v + "\n";
        onOutput(output);
        return v;
      },
      debugging: !!dbgLine,
      __future__: Sk.python3,
    });
    Sk.execLimit = Number.POSITIVE_INFINITY;
    Sk.breakpoints = () => true;
    const handlers = dbgLine ? { "Sk.debug": (susp) => new Promise((res, rej) => dbgLine(susp.child.$lineno).then(() => res(susp.resume()), rej)) } : undefined;
    try {
      await Sk.misceval.asyncToPromise(() => Sk.importMainWithBody("<stdin>", false, source, true), handlers);
      return { output, error: null };
    } catch (e) {
      return { output, error: String(e) };
    }
  }

  const normalize = (s) => String(s).replace(/\r/g, "").split("\n").map((l) => l.replace(/\s+$/, "")).join("\n").trim();
  const codeDrafts = {}; // keeps typed code while switching questions
  // SVG icons for the purple buttons
  const ICON = {
    play: '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>',
    stop: '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="1.5"/></svg>',
    check: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7"/></svg>',
    reset: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 1 0 2.5-5.8"/><path d="M4 4v5h5"/></svg>',
    newFile: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v10"/><path d="M14 3l5 5v11a2 2 0 0 1-2 2h-6"/><path d="M14 3v5h5"/><path d="M5 17v6M2 20h6"/></svg>',
    save: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h11l4 4v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M8 3v5h7V3M8 21v-7h8v7"/></svg>',
    theme: '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"/></svg>',
    expand: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M10 20H4v-6M20 4l-7 7M4 20l7-7"/></svg>',
  };

  // Tiny Python highlighter: returns HTML for the editor's backdrop layer
  const PY_TOKEN = /(#[^\n]*)|("(?:\\.|[^"\\\n])*"?|'(?:\\.|[^'\\\n])*'?)|(\b\d+(?:\.\d+)?\b)|\b(print|input|len|int|str|float|bool|range|type|abs|round|min|max|sum|list|dict|set)\b(?=\s*\()|\b(if|elif|else|for|while|in|def|return|import|from|as|and|or|not|True|False|None|break|continue|pass)\b|([()[\]{}])/g;
  // Syntax coloring for the editor (regex based)
  function highlight(src) {
    let html = "";
    let last = 0;
    let m;
    PY_TOKEN.lastIndex = 0;
    while ((m = PY_TOKEN.exec(src))) {
      html += esc(src.slice(last, m.index));
      const cls = m[1] ? "c" : m[2] ? "s" : m[3] ? "n" : m[4] ? "f" : m[5] ? "k" : "b";
      html += `<span class="tk-${cls}">${esc(m[0])}</span>`;
      last = m.index + m[0].length;
    }
    return html + esc(src.slice(last)) + "\n";
  }

  let panelKey = null; // question whose task panel is open (closed by default, like Algonova)
  let darkEditor = false;
  const STEP = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M5 5v14l9-7z"/><rect x="16" y="5" width="3" height="14" rx="1"/></svg>';
  const STEP_ALL = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M3 5v14l8-7z"/><path d="M12 5v14l8-7z"/></svg>';
  const INFO = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6M12 7.5v.5"/></svg>';
  const CHEV = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 15 6-6 6 6"/></svg>';

  // Builds the whole editor screen (editor + console + sliding task panel) and wires up all buttons
  function renderCode(task, q, solved, attempts) {
    const key = `${taskIndex}:${current}`;
    if (!(key in codeDrafts)) codeDrafts[key] = q.code.starter || "";
    const open = panelKey === key;
    let runId = 0;
    const heading = q.code.title || task.title;
    const goal = q.code.goal || "Your program prints the expected result";
    const tools = (extra = "") => `
      <button type="button" class="cp-btn" data-act="run" title="Run" aria-label="Run">${ICON.play}</button>
      <button type="button" class="cp-btn" data-act="step" title="Step" aria-label="Step">${STEP}</button>
      <button type="button" class="cp-btn" data-act="stepall" title="Run to end" aria-label="Run to end" disabled>${STEP_ALL}</button>
      <button type="button" class="cp-btn" data-act="stop" title="Stop" aria-label="Stop" disabled>${ICON.stop}</button>
      <button type="button" class="cp-btn" data-act="check" title="Check" aria-label="Check" ${solved ? "disabled" : ""}>${ICON.check}</button>${extra}`;
    const full = `<button type="button" class="cp-btn cp-push" data-act="full" title="Fullscreen" aria-label="Fullscreen">${ICON.expand}</button>`;
    document.getElementById("lpBar").innerHTML = topBar();
    root.classList.add("is-code");
    document.body.classList.add("code-mode");
    document.querySelectorAll(".qc").forEach((b) => (b.onclick = () => select(Number(b.dataset.n))));
    root.innerHTML = `
      <div class="cp  ${open ? "is-open" : ""} ${darkEditor ? "is-dark" : ""}" id="cp">
        <!-- LEFT: toolbar (new/reset/save/theme/fullscreen) + editor where the student types Python + console -->
        <section class="cp-left">
          <div class="cp-tools cp-tools-left">
            <button type="button" class="cp-btn" id="cpNew" title="Clear" aria-label="Clear editor">${ICON.newFile}</button>
            <button type="button" class="cp-btn" id="cpReset" title="Reset code" aria-label="Reset code">${ICON.reset}</button>
            <button type="button" class="cp-btn" id="cpDebug" title="Debug (run line by line)" aria-label="Debug"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="8" height="11" rx="4"/><path d="M12 8V5M5 12h3M16 12h3M6 18l2-2M18 18l-2-2M6 7l2 2M18 7l-2 2"/></svg></button>
            <button type="button" class="cp-btn" id="cpSave" title="Download .py" aria-label="Download code">${ICON.save}</button>
            <button type="button" class="cp-btn" id="cpTheme" title="Dark / light" aria-label="Toggle theme">${ICON.theme}</button>
            <button type="button" class="cp-btn" data-act="full" title="Fullscreen" aria-label="Fullscreen">${ICON.expand}</button>
          </div>
          <div class="cp-editor">
            <pre class="cp-gutter" id="cpGutter" aria-hidden="true"></pre>
            <div class="cp-code">
              <div class="cp-cur" id="cpCur" hidden></div>
              <pre class="cp-hl" id="cpHl" aria-hidden="true"></pre>
              <textarea id="cpCode" spellcheck="false" autocapitalize="off" autocomplete="off" aria-label="Python code"></textarea>
            </div>
          </div>
          <pre class="cp-console cp-term" aria-live="polite"><span class="cp-prompt">&gt;&gt;&gt;</span></pre>
        </section>
        <!-- RIGHT: Run / Stop / Check buttons + output console -->
        <section class="cp-right">
          <div class="cp-tools">${tools(full)}</div>
          <pre class="cp-console" aria-live="polite"><span class="cp-prompt">&gt;&gt;&gt;</span></pre>
        </section>
        <!-- HANDLE: purple strip that opens/closes the task panel -->
        <button type="button" class="cp-handle" id="cpHandle" aria-label="Show or hide the task" aria-expanded="${open}"></button>
        <!-- (the mini toolbar below is only a picture of the buttons: no data-act, so it does nothing) TASK PANEL: slides in from the right; title, goal, expected output, result -->
        <aside class="cp-panel" id="cpPanel">
          ${
            solved
              ? `<div class="cp-thanks"><h2>Well done!</h2><p>Your program is correct.</p><button type="button" class="cp-next" id="lcNext">${nextLabel()}</button></div>`
              : ""
          }
          <div class="cp-scroll">
            <div class="cp-card cp-title"><h2>${esc(heading)}</h2></div>
            <p class="cp-label"><span>Automatically checked task <i class="cp-info" title="Your program is run and its output is compared with the expected output">${INFO}</i></span><span class="cp-attempts">Attempts: ${attempts}</span></p>
            <div class="cp-card cp-goal" id="cpGoal">
              <button type="button" class="cp-goal-row" id="cpGoalToggle" aria-expanded="true">
                <i class="cp-tick ${solved ? "is-done" : ""}">${solved ? "✓" : ""}</i>
                <span class="cp-goal-text ${solved ? "is-done" : ""}">${esc(goal)}</span>
                <i class="cp-chev">${CHEV}</i>
              </button>
              <div class="cp-goal-body">
                <div class="cp-mini">
                  <div class="cp-mini-tools" aria-hidden="true">${(tools() + full).replace(/ data-act="[^"]*"/g, "").replace(/<button /g, "<button tabindex=\"-1\" ")}</div>
                  <pre class="cp-mini-out">${esc(q.code.expected)}</pre>
                </div>
                <p class="cp-desc">${esc(q.q)}</p>
                <p class="cp-feedback" id="cpResult" role="status"></p>
              </div>
            </div>
          </div>
        </aside>
      </div>`;

    const cp = document.getElementById("cp");
    const ta = document.getElementById("cpCode");
    const gutter = document.getElementById("cpGutter");
    const hl = document.getElementById("cpHl");
    const outs = [...cp.querySelectorAll(".cp-console")];
    const result = document.getElementById("cpResult");
    const act = (name) => [...cp.querySelectorAll(`[data-act="${name}"]`)];

    const cur = document.getElementById("cpCur");
    let curLine = 0;
    // yellow bar on the line being executed in Step mode (19px line height, 8px padding: see style.css .cp-editor)
    const placeCur = () => {
      cur.hidden = !curLine;
      if (curLine) cur.style.top = 8 + (curLine - 1) * 19 - ta.scrollTop + "px";
    };
    const sync = () => {
      hl.scrollTop = gutter.scrollTop = ta.scrollTop;
      hl.scrollLeft = ta.scrollLeft;
      placeCur();
    };
    const paint = () => {
      hl.innerHTML = highlight(ta.value);
      gutter.textContent = ta.value.split("\n").map((_, i) => i + 1).join("\n");
      sync();
    };
    ta.value = codeDrafts[key];
    paint();
    ta.oninput = () => {
      codeDrafts[key] = ta.value;
      paint();
    };
    ta.onscroll = sync;
    ta.onkeydown = (e) => {
      if (e.key !== "Tab") return;
      e.preventDefault();
      ta.setRangeText("    ", ta.selectionStart, ta.selectionEnd, "end");
      ta.oninput();
    };

    const show = (text, err) => {
      outs.forEach((out) => {
        out.textContent = "";
        out.append(document.createTextNode(text));
        if (err) {
          const e = document.createElement("span");
          e.className = "cp-error";
          e.textContent = (text && !text.endsWith("\n") ? "\n" : "") + err;
          out.append(e);
        }
        out.append(Object.assign(document.createElement("span"), { className: "cp-prompt", textContent: (text || err ? "\n" : "") + ">>>" }));
      });
    };
    const clearOutput = () => {
      show("", "");
      result.className = "cp-feedback";
      result.textContent = "";
    };
    const setCode = (text) => {
      ta.value = codeDrafts[key] = text;
      paint();
      clearOutput();
    };
    document.getElementById("cpNew").onclick = () => setCode("");
    document.getElementById("cpReset").onclick = () => setCode(q.code.starter || "");
    document.getElementById("cpSave").onclick = () => {
      const a = document.createElement("a");
      a.href = URL.createObjectURL(new Blob([ta.value], { type: "text/x-python" }));
      a.download = "main.py";
      a.click();
      URL.revokeObjectURL(a.href);
    };
    document.getElementById("cpTheme").onclick = () => {
      darkEditor = !darkEditor;
      cp.classList.toggle("is-dark", darkEditor);
    };
    act("full").forEach((b) => (b.onclick = () => (document.fullscreenElement ? document.exitFullscreen() : cp.requestFullscreen && cp.requestFullscreen())));
    const setPanel = (v) => {
      panelKey = v ? key : null;
      cp.classList.toggle("is-open", v);
      document.getElementById("cpHandle").setAttribute("aria-expanded", v);
    };
    document.getElementById("cpHandle").onclick = () => setPanel(panelKey !== key);
    document.getElementById("cpGoalToggle").onclick = (e) => {
      const goalEl = document.getElementById("cpGoal");
      goalEl.classList.toggle("is-collapsed");
      e.currentTarget.setAttribute("aria-expanded", !goalEl.classList.contains("is-collapsed"));
    };
    const next = document.getElementById("lcNext");
    if (next) next.onclick = advance;

    const dbgBtn = document.getElementById("cpDebug");
    let running = false;
    let stepMode = false; // true = pause before every line
    let nextLine = null; // resolves the pause
    const setPaused = (v) => {
      act("step").forEach((b) => (b.disabled = running && !v));
      act("stepall").forEach((b) => (b.disabled = !v));
    };
    const setRunning = (v) => {
      running = v;
      act("run").forEach((b) => (b.disabled = v));
      act("stop").forEach((b) => (b.disabled = !v));
      dbgBtn.disabled = v;
      if (!v) {
        stepMode = false;
        nextLine = null;
        curLine = 0;
        placeCur();
        setPaused(false);
      }
    };
    // Step mode: called before each line; waits until Step or Run to end is pressed
    const onLine = (line) => {
      if (!stepMode) return Promise.resolve();
      curLine = line;
      placeCur();
      setPaused(true);
      return new Promise((res, rej) => (nextLine = { res, rej }));
    };
    const resumeLine = () => {
      const f = nextLine;
      nextLine = null;
      setPaused(false);
      if (f) f.res();
    };
    // Run button: executes the code and prints the output in the console
    async function run(debug) {
      const id = ++runId;
      stepMode = !!debug;
      setRunning(true);
      result.className = "cp-feedback";
      result.textContent = "";
      try {
        const r = await runPython(ta.value, q.code.input, (t) => id === runId && show(t), debug ? onLine : undefined);
        if (id !== runId) return null;
        show(r.output, r.error);
        return r;
      } catch (e) {
        show("", e.message);
        return null;
      } finally {
        if (id === runId) setRunning(false);
      }
    }
    act("run").forEach((b) => (b.onclick = () => run()));
    // Step / Debug: run one line at a time (Step starts the run if nothing is running yet)
    act("step").forEach((b) => (b.onclick = () => (running ? resumeLine() : run(true))));
    dbgBtn.onclick = () => run(true);
    act("stepall").forEach(
      (b) =>
        (b.onclick = () => {
          stepMode = false;
          curLine = 0;
          placeCur();
          resumeLine();
        })
    );
    act("stop").forEach(
      (b) =>
        (b.onclick = () => {
          runId++;
          stepMode = false;
          if (nextLine) {
            nextLine = null; // paused in Step mode: simply never resume this run
          } else if (window.Sk && Sk.builtin) Sk.execLimit = 1; // interrupts a runaway loop
          setRunning(false);
        })
    );
    // Check button: runs the code, compares the output with `expected` from task-K.js, saves progress
    act("check").forEach(
      (b) =>
        (b.onclick = async () => {
          const r = await run();
          if (!r) return;
          const st = state();
          st.attempts[current] = (st.attempts[current] || 0) + 1;
          const ok = !r.error && normalize(r.output) === normalize(q.code.expected);
          if (ok && !st.solved.includes(current)) st.solved.push(current);
          P.saveQuestionState(level.id, lesson, taskIndex, st);
          if (ok) {
            if (st.solved.length === tasks[taskIndex].questions.length) P.markTaskDone(level.id, lesson, taskIndex);
            panelKey = key;
            return render();
          }
          document.querySelector(".cp-attempts").textContent = "Attempts: " + st.attempts[current];
          const msg = r.error ? "Your program has an error. Fix it and check again." : "The output doesn't match the expected result. Try again!";
          result.className = "cp-feedback is-bad";
          result.textContent = msg;
          show(r.output, (r.error ? r.error + "\n" : "") + msg);
        })
    );
  }

  // ===== NAVIGATION + MULTIPLE-CHOICE LOGIC
  // Styles: style.css -> section "MULTIPLE-CHOICE QUESTION" =====
  function nextLabel() {
    const questions = tasks[taskIndex].questions;
    if (current < questions.length - 1) return "Next question →";
    return taskIndex < tasks.length - 1 ? "Next task →" : "Back to course map";
  }

  function advance() {
    const questions = tasks[taskIndex].questions;
    if (current < questions.length - 1) return select(current + 1);
    if (taskIndex < tasks.length - 1) {
      taskIndex++;
      return select(firstOpen());
    }
    location.href = mapUrl;
  }

  function submit(q, correct) {
    if (!selected.length) {
      verdict = "bad";
      return render();
    }
    const st = state();
    st.attempts[current] = (st.attempts[current] || 0) + 1;
    const ok = selected.slice().sort().join() === correct.join();
    if (ok) {
      if (!st.solved.includes(current)) st.solved.push(current);
      verdict = "good";
    } else {
      verdict = "bad";
    }
    P.saveQuestionState(level.id, lesson, taskIndex, st);
    if (ok && st.solved.length === tasks[taskIndex].questions.length) P.markTaskDone(level.id, lesson, taskIndex);
    if (ok) selected = [];
    render();
  }

  // Theory view: ?theory=1
  const inline = (s) => esc(s).replace(/`([^`]+)`/g, "<code>$1</code>").replace(/\*\*([^*]+)\*\*/g, "<b>$1</b>");
  // ===== THEORY PAGES
  // Styles: style.css -> section "THEORY PAGE" =====
  function renderTheory(theory) {
    const blocks = theory.blocks
      .map((b) => {
        if (b.h) return `<h2 class="th-h">${inline(b.h)}</h2>`;
        if (b.p) return `<p class="th-p">${inline(b.p)}</p>`;
        if (b.list) return `<ul class="th-list">${b.list.map((i) => `<li>${inline(i)}</li>`).join("")}</ul>`;
        if (b.code) return `<pre class="th-code"><code>${esc(b.code)}</code></pre>`;
        if (b.tip) return `<p class="th-tip">💡 ${inline(b.tip)}</p>`;
        if (b.img) return `<img class="th-img" src="${esc(b.img)}" alt="${esc(b.alt || "")}" />`;
        return "";
      })
      .join("");
    document.getElementById("lpBar").innerHTML = `<a class="lp-back" href="${mapUrl}" aria-label="Back to course map" title="Back to course map"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg></a><nav class="lp-circles"><span class="qc is-theory is-current">≡</span></nav><span class="lp-spacer"></span>`;
    root.innerHTML = `
      <div class="lc-head"><h1>Theory. ${esc(theory.title)}</h1></div>
      <div class="th-body">${blocks}</div>
      <div class="lc-actions"><button type="button" class="lc-btn" id="thDone">Got it!</button></div>`;
    document.getElementById("thDone").onclick = () => {
      P.markTheoryRead(level.id, lesson);
      location.href = mapUrl;
    };
  }

  window.PythonCourse.loadLesson(body.dataset.level, lesson + 1).then(() => {
    level = P.levels().find((l) => l.id === body.dataset.level);
    tasks = P.tasksOf(level, lesson);
    const theory = P.theoryOf(level.id, lesson);
    if (new URLSearchParams(location.search).get("theory") && theory) return renderTheory(theory);
    taskIndex = Math.min(Math.max((Number(new URLSearchParams(location.search).get("task")) || 1) - 1, 0), tasks.length - 1);
    select(firstOpen());
  });
})();
