// ===== COURSE MAP (page: course/python/index.html) =====
// Builds the winding map of all levels: lesson circles 1..32, locked/current/done states, castle at the end.
// Styles: course/python/map/python.css -> section "COURSE MAP"
// Clicking a circle opens the Tasks popup: course/python/map/lesson-modal.js
// Level/lesson list comes from: course/python/<level>/data.js  and progress.js

(function () {
  const P = window.PythonProgress;
  const journey = document.getElementById("journey");
  // Map layout sizes: STEP = vertical gap between lesson circles, PAD = space above first, TAIL = space below last
  const STEP = 175;
  const PAD = 150;
  const TAIL = 260;

  // Draws one level (banner, dotted path, numbered lesson circles, castle). Circle look: python.css -> section "COURSE MAP"
  function renderStage(level, index, all, unlocked) {
    const total = level.lessons.length;
    const done = P.completedCount(level);
    const height = PAD + STEP * (total - 1) + TAIL;
    const points = level.lessons.map((_, i) => ({ i, x: 50 + 34 * Math.sin(i * 0.95), y: PAD + STEP * i }));
    const last = points[points.length - 1];
    const castle = { x: 50, y: last.y + TAIL - 50 };
    const path = [...points, castle];

    let d = `M ${path[0].x} ${path[0].y}`;
    for (let i = 1; i < path.length; i++) {
      const my = (path[i - 1].y + path[i].y) / 2;
      d += ` C ${path[i - 1].x} ${my}, ${path[i].x} ${my}, ${path[i].x} ${path[i].y}`;
    }

    const nodes = points
      .map(({ i, x, y }) => {
        const byTeacher = P.teacherUnlocked(level.id, i);
        const state = byTeacher ? (P.isLessonDone(level, i) ? "done" : "current") : !unlocked ? "locked" : i < done ? "done" : i === done ? "current" : "locked";
        const doneTasks = P.doneTasks(level.id, i).length;
        const badge = state === "current" && doneTasks ? `<span class="node-badge">${doneTasks}/${P.tasksOf(level, i).length}</span>` : "";
        const inner = `${i + 1}${state === "done" ? '<i class="node-check">✓</i>' : ""}`;
        const btn = `<button type="button" class="node-btn" data-level="${level.id}" data-lesson="${i}" ${state === "locked" ? 'disabled aria-disabled="true"' : ""} aria-label="Lesson ${i + 1}">${inner}</button>`;
        return `<div class="node ${state}" style="left:${x}%;top:${y}px">${state === "current" ? '<span class="node-pin">▼</span>' : ""}${badge}${btn}<span class="node-label">${level.lessons[i].title}</span></div>`;
      })
      .join("");

    const castleLabel = "Finish";
    const title = index === 0 ? `🚩 START · ${level.name}` : level.name;
    const sub = `${done}/${total} lessons completed`;

    return `<section class="stage ${unlocked ? "" : "locked-stage"}">
      <div class="stage-banner"><span>${title}</span><small>${sub}</small></div>
      <div class="snake" style="height:${height}px">
        <svg viewBox="0 0 100 ${height}" preserveAspectRatio="none" aria-hidden="true"><path d="${d}" vector-effect="non-scaling-stroke" /></svg>
        ${nodes}
        <div class="castle" style="left:${castle.x}%;top:${castle.y}px"><div class="big">🏰</div><b>${castleLabel}</b></div>
      </div>
    </section>`;
  }

  // Draws all levels and wires the circle clicks to the Tasks popup (lesson-modal.js)
  // A level stays grey/locked until the previous level is finished or the teacher opens one of its lessons (teacher LMS > Course)
  const openedByTeacher = (level) => level.lessons.some((_, i) => P.teacherUnlocked(level.id, i));

  function render() {
    const all = P.levels();
    const shown = all;
    let unlocked = true;
    journey.innerHTML =
      shown
        .map((level) => {
          const html = renderStage(level, all.indexOf(level), all, unlocked || openedByTeacher(level));
          unlocked = unlocked && P.completedCount(level) >= level.lessons.length;
          return html;
        })
        .join("") + '<div class="journey-end">🎓 Awesome! You have finished Python</div>';
  }

  journey.addEventListener("click", (e) => {
    const btn = e.target.closest(".node-btn");
    if (!btn || btn.disabled) return;
    const level = P.levels().find((l) => l.id === btn.dataset.level);
    const y = window.scrollY;
    window.PythonModal.open(level, Number(btn.dataset.lesson), () => {
      render();
      window.scrollTo(0, y);
    });
  });

  // Load all lesson.js files first, then draw the map
  window.PythonCourse.loadAllLessons(window.PYTHON_LEVELS).then(() => {
    render();
    const cur = journey.querySelector(".node.current");
    if (cur && journey.querySelector(".node.done")) cur.scrollIntoView({ block: "center" });
  });
})();
