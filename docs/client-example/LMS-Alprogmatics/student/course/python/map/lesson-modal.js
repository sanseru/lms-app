// ===== TASKS POPUP (opened from the course map) =====
// Popup with a row of task circles for one lesson; each circle opens course/python/<level>/lesson-N/index.html?task=K
// Opened by: course/python/map/journey.js   Styles: course/python/map/python.css -> section "TASKS POPUP"
// Task names come from task-K.js in the lesson folder (via registry.js)

(function () {
  const P = window.PythonProgress;
  let overlay;
  let onChange = () => {};

  const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);

  // Creates the dark overlay + dialog once. Styles: python.css -> section "TASKS POPUP"
  function ensureOverlay() {
    if (overlay) return;
    overlay = document.createElement("div");
    overlay.className = "pm-overlay";
    overlay.hidden = true;
    overlay.innerHTML = '<div class="pm-dialog" role="dialog" aria-modal="true" aria-labelledby="pmTitle"></div>';
    document.body.appendChild(overlay);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !overlay.hidden) close();
    });
  }

  function close() {
    overlay.hidden = true;
    document.body.classList.remove("pm-open");
    onChange();
  }

  // Task names live in task-K.js, so load them before drawing the popup.
  // Called by journey.js when a lesson circle is clicked
  function open(level, lessonIndex, callback) {
    window.PythonCourse.loadLesson(level.id, lessonIndex + 1).then(() => {
      draw(P.levels().find((l) => l.id === level.id), lessonIndex, callback);
    });
  }

  // Draws the popup: title, one circle per task (links to lesson-N/index.html?task=K) and the Theory circle
  function draw(level, lessonIndex, callback) {
    ensureOverlay();
    onChange = callback || (() => {});
    const tasks = P.tasksOf(level, lessonIndex);
    const done = P.doneTasks(level.id, lessonIndex);
    const theory = P.theoryOf(level.id, lessonIndex);
    const theoryRead = P.theoryRead(level.id, lessonIndex);
    const DOC = '<svg viewBox="0 0 24 24" width="56" height="56" fill="currentColor"><path d="M6 2h8l6 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm7 1.5V9h5.5zM8 13h8v1.6H8zm0 3.4h8V18H8zM8 9.6h3v1.6H8z"/></svg>';
    const page = `${level.id}/lesson-${lessonIndex + 1}/index.html`;
    const dialog = overlay.firstElementChild;
    dialog.innerHTML = `
      <div class="pm-head">
        <h2 id="pmTitle">${esc(level.lessons[lessonIndex].heading)}</h2>
        <button class="pm-close" type="button" aria-label="Close">✕</button>
      </div>
      <div class="pm-tasks">
        ${tasks
          .map(
            (t, i) => `<a class="pm-task ${done.includes(i) ? "is-done" : ""}" href="${page}?task=${i + 1}">
              <span class="pm-circle">${i + 1}${done.includes(i) ? '<i class="pm-tick">✓</i>' : ""}</span>
              <span class="pm-task-title">${esc(t.title)}</span>
            </a>`
          )
          .join("")}
        ${
          theory
            ? `<a class="pm-task ${theoryRead ? "is-done" : ""}" href="${page}?theory=1">
              <span class="pm-circle">${DOC}${theoryRead ? '<i class="pm-tick">✓</i>' : ""}</span>
              <span class="pm-task-title">Theory. ${esc(theory.title)}</span>
            </a>`
            : ""
        }
      </div>`;
    dialog.querySelector(".pm-close").onclick = close;
    overlay.hidden = false;
    document.body.classList.add("pm-open");
    dialog.querySelector(".pm-task").focus();
  }

  window.PythonModal = { open };
})();
