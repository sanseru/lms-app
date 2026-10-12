(function () {
  const T = window.TeacherLMS;
  const root = document.getElementById("tLevels");
  const expanded = new Set([T.levels[0].id]);

  function render() {
    root.innerHTML = T.levels
      .map((l) => {
        const open = T.openLessons(l.id);
        const grid = expanded.has(l.id)
          ? `<div class="t-grid">${Array.from({ length: l.lessonCount }, (_, i) => {
              const on = open.includes(i);
              return `<button type="button" class="t-lesson ${on ? "on" : ""}" data-level="${l.id}" data-lesson="${i}" aria-pressed="${on}"><b>Lesson ${i + 1}</b><span>${on ? "Open" : "Locked"}</span></button>`;
            }).join("")}</div>`
          : "";
        return `<section class="t-card">
          <div class="t-head">
            <div><h2>${l.name}</h2><small>${l.desc} &middot; ${open.length}/${l.lessonCount} lessons opened</small></div>
            <div class="t-actions">
              <button type="button" data-all="${l.id}" data-open="1">Open all</button>
              <button type="button" data-all="${l.id}" data-open="0">Lock all</button>
              <button type="button" data-toggle="${l.id}">${expanded.has(l.id) ? "Hide" : "Show"} lessons</button>
            </div>
          </div>${grid}</section>`;
      })
      .join("");
  }

  root.addEventListener("click", (e) => {
    const lesson = e.target.closest(".t-lesson");
    if (lesson) {
      const on = !lesson.classList.contains("on");
      T.setOpen(lesson.dataset.level, Number(lesson.dataset.lesson), on);
      return render();
    }
    const all = e.target.closest("[data-all]");
    if (all) {
      const l = T.levels.find((x) => x.id === all.dataset.all);
      for (let i = 0; i < l.lessonCount; i++) T.setOpen(l.id, i, all.dataset.open === "1");
      return render();
    }
    const tg = e.target.closest("[data-toggle]");
    if (tg) {
      expanded.has(tg.dataset.toggle) ? expanded.delete(tg.dataset.toggle) : expanded.add(tg.dataset.toggle);
      render();
    }
  });

  render();
})();
