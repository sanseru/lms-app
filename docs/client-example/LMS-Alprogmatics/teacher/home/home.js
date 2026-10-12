(function () {
  const T = window.TeacherLMS;
  const total = T.levels.reduce((n, l) => n + l.lessonCount, 0);
  const opened = T.levels.reduce((n, l) => n + T.openLessons(l.id).length, 0);
  document.getElementById("tStats").innerHTML =
    `<div><b>${T.levels.length}</b><span>Courses</span></div><div><b>${total}</b><span>Total lessons</span></div><div><b>${opened}</b><span>Opened by you</span></div><div><b>${total - opened}</b><span>Not opened</span></div>`;
  document.getElementById("tSummary").innerHTML = T.levels
    .map((l) => {
      const n = T.openLessons(l.id).length;
      return `<div class="t-row"><span>${l.name}</span><div class="t-bar"><i style="width:${(n / l.lessonCount) * 100}%"></i></div><small>${n}/${l.lessonCount}</small></div>`;
    })
    .join("");
})();
