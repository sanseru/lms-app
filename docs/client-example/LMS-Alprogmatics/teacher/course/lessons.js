// Shared data for the Teacher LMS.
// Storage key + format must match student/course/python/map/progress.js (teacherUnlocked).
// Keep TeacherLMS.levels in sync with student/course/python/<level>/data.js
window.TeacherLMS = (function () {
  const levels = [
    { id: "python-start-1", name: "Python Start 1", desc: "Logic & syntax basics", lessonCount: 32 },
    { id: "python-start-2", name: "Python Start 2", desc: "Python Start, part 2", lessonCount: 32 },
    { id: "python-pro-1", name: "Python Pro 1", desc: "Python Pro, part 1", lessonCount: 32 },
    { id: "python-pro-2", name: "Python Pro 2", desc: "Python Pro, part 2", lessonCount: 32 },
  ];
  const KEY = "alprogmatics-teacher-unlocks-v1";
  const read = () => {
    try {
      return JSON.parse(localStorage.getItem(KEY)) || {};
    } catch {
      return {};
    }
  };
  const openLessons = (id) => read()[id] || [];
  function setOpen(id, i, open) {
    const all = read();
    const set = new Set(all[id] || []);
    open ? set.add(i) : set.delete(i);
    all[id] = [...set].sort((a, b) => a - b);
    localStorage.setItem(KEY, JSON.stringify(all));
  }
  return { levels, openLessons, setOpen };
})();
