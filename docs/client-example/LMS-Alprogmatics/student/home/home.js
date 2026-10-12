(function () {
  const P = window.Projects;
  const $ = (id) => document.getElementById(id);
  const read = (k) => { try { return JSON.parse(localStorage.getItem(k)) || {}; } catch (e) { return {}; } };
  const LEVELS = [['python-start-1', 'Python Start 1'], ['python-start-2', 'Python Start 2'], ['python-pro-1', 'Python Pro 1'], ['python-pro-2', 'Python Pro 2']];
  const ICON = { python: 'Py', scratch: '&#128049;', presentation: '&#128250;', website: '&lt;/&gt;' };
  const KIND = { python: 'Python', scratch: 'Scratch', presentation: 'Presentation', website: 'Website' };
  const user = P.user();

  const progress = read('algonova-python-progress-v2');
  const questions = read('algonova-python-questions-v1');
  const lessonsDone = (id) => Object.values(progress[id] || {}).filter((t) => t.length).length;
  const totalLessons = LEVELS.reduce((s, l) => s + lessonsDone(l[0]), 0);
  const solved = Object.values(questions).reduce((s, q) => s + ((q && q.solved) || []).length, 0);

  const mine = user ? P.all().filter((p) => p.author === user.name) : [];
  const likesOf = (p) => (P.likes()[p.id] ? 1 : 0);
  const sum = (fn) => mine.reduce((s, p) => s + fn(p), 0);
  const likes = sum(likesOf), views = sum((p) => P.views(p.id)), comments = sum((p) => P.comments(p.id).length);

  if (user) {
    $('hmName').textContent = 'Hi, ' + user.name + '!';
    $('hmAvatar').textContent = user.name[0].toUpperCase();
    $('hmSub').textContent = 'Keep learning and keep creating.';
    $('hmLogin').textContent = 'My projects';
    $('hmLogin').href = '../laboratory/index.html';
  }
  const xp = totalLessons * 50 + solved * 10 + mine.length * 30;
  $('hmLevelText').textContent = 'Level ' + (Math.floor(xp / 200) + 1);
  $('hmXp').style.width = (xp % 200) / 2 + '%';
  $('hmXpText').textContent = xp % 200 + ' / 200 XP';

  [['sProjects', mine.length], ['sLikes', likes], ['sViews', views], ['sComments', comments], ['sLessons', totalLessons], ['sSolved', solved]]
    .forEach((r) => ($(r[0]).textContent = r[1]));

  $('hmCourses').innerHTML = LEVELS.map((l) => {
    const d = lessonsDone(l[0]);
    return '<a class="hm-course" href="../course/python/index.html"><div class="hm-ci">Py</div><div class="hm-cb"><b>' + l[1] + '</b>' +
      '<div class="hm-bar"><i style="width:' + (d / 32 * 100) + '%"></i></div></div><span>' + d + '/32</span></a>';
  }).join('');

  const badges = [
    ['&#127775;', 'First project', mine.length >= 1],
    ['&#128640;', '3 projects', mine.length >= 3],
    ['&#10084;&#65039;', 'First like', likes >= 1],
    ['&#128172;', 'First comment', comments >= 1],
    ['&#128013;', 'Python starter', totalLessons >= 1],
    ['&#129504;', '10 questions solved', solved >= 10]
  ];
  $('hmBadges').innerHTML = badges.map((b) => '<div class="hm-badge' + (b[2] ? ' on' : '') + '"><span>' + b[0] + '</span>' + b[1] + '</div>').join('');

  function card(p) {
    return '<a class="hm-pcard" href="../' + (p.mine ? 'laboratory' : 'community') + '/index.html"><div class="hm-pthumb ' + p.type + '">' + ICON[p.type] + '</div>' +
      '<b>' + P.esc(p.title) + '</b><small>' + KIND[p.type] + ' \u2022 ' + P.esc(p.author) + '</small>' +
      '<div class="hm-nums"><span>\u2661 ' + likesOf(p) + '</span><span>\uD83D\uDC41 ' + P.views(p.id) + '</span><span>\uD83D\uDCAC ' + P.comments(p.id).length + '</span></div></a>';
  }
  const empty = (t, href, b) => '<div class="hm-empty">' + t + ' <a href="' + href + '">' + b + '</a></div>';
  $('hmProjects').innerHTML = mine.length ? mine.slice(0, 6).map((p) => card(Object.assign({ mine: true }, p))).join('') : empty('No projects yet.', '../laboratory/index.html', 'Create your first project');
  const pop = P.all().sort((a, b) => (likesOf(b) * 3 + P.views(b.id)) - (likesOf(a) * 3 + P.views(a.id))).slice(0, 6);
  $('hmPopular').innerHTML = pop.length ? pop.map(card).join('') : empty('Nothing here yet.', '../laboratory/index.html', 'Be the first to share');
})();