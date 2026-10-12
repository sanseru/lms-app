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

  // Lessons opened by the teacher (set in teacher/index.html). Same browser/origin only: localStorage.
  const UKEY = "alprogmatics-teacher-unlocks-v1";
  const readU = () => {
    try {
      return JSON.parse(localStorage.getItem(UKEY)) || {};
    } catch {
      return {};
    }
  };
  const teacherUnlocked = (levelId, lessonIndex) => (readU()[levelId] || []).includes(lessonIndex);
  function setTeacherUnlock(levelId, lessonIndex, open) {
    const all = readU();
    const set = new Set(all[levelId] || []);
    open ? set.add(lessonIndex) : set.delete(lessonIndex);
    all[levelId] = [...set].sort((a, b) => a - b);
    localStorage.setItem(UKEY, JSON.stringify(all));
  }

  window.PythonProgress = { teacherUnlocked, setTeacherUnlock, theoryRead, markTheoryRead, theoryOf, levels, tasksOf, doneTasks, markTaskDone, isLessonDone, completedCount, questionState, saveQuestionState };
})();
