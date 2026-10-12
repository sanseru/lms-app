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