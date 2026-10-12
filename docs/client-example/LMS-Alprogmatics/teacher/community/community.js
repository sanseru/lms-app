(function () {
  const P = window.Projects;
  const SCRATCH_URL = '../../student/course/scratch/scratch-gui/build/index.html';
  const $ = (id) => document.getElementById(id);
  const DAY = 86400000;
  const KIND = { python: 'Python', scratch: 'Scratch', presentation: 'Presentation', website: 'Website' };
  const ICON = { python: 'Py', scratch: '&#128049;', presentation: '&#128250;', website: '&lt;/&gt;' };
  const initials = (n) => n.split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase();
  const now = Date.now();

  const state = { sort: 'new', type: 'all', days: 1, q: '', current: null };
  let py = null;

  const all = () => P.all().map((p) => Object.assign({}, p, { views0: 0, likes0: 0 }));
  const likes = (p) => p.likes0 + (P.likes()[p.id] ? 1 : 0);
  const views = (p) => p.views0 + P.views(p.id);
  const comments = (p) => P.comments(p.id).length;
  const score = (p) => likes(p) * 3 + views(p);

  function card(p) {
    return '<article class="cm-card" data-id="' + p.id + '"><div class="cm-thumb ' + p.type + '"><span class="cm-badge">' + KIND[p.type] + '</span>' + ICON[p.type] + '</div>' +
      '<div class="cm-info"><h3>' + P.esc(p.title) + '</h3><div class="cm-by"><span class="cm-av">' + P.esc(initials(p.author)) + '</span>' + P.esc(p.author) + '</div>' +
      '<div class="cm-nums"><span>\u2661 ' + likes(p) + '</span><span>\uD83D\uDC41 ' + views(p) + '</span><span>\uD83D\uDCAC ' + comments(p) + '</span></div></div></article>';
  }

  function render() {
    const list = all();
    const since = now - state.days * DAY;
    let trend = list.filter((p) => p.createdAt >= since).sort((a, b) => score(b) - score(a)).slice(0, 10);
    if (!trend.length) trend = list.slice().sort((a, b) => score(b) - score(a)).slice(0, 6);
    $('trendRow').innerHTML = trend.map(card).join('');

    let items = list.filter((p) => (state.type === 'all' || p.type === state.type) &&
      (!state.q || (p.title + ' ' + p.author).toLowerCase().includes(state.q)));
    const sorters = { new: (a, b) => b.createdAt - a.createdAt, popular: (a, b) => score(b) - score(a), discussed: (a, b) => comments(b) - comments(a) || score(b) - score(a) };
    items.sort(sorters[state.sort]);
    $('cmGrid').innerHTML = items.length ? items.map(card).join('') : '<div class="cm-empty">' + (list.length ? 'No projects found.' : 'No projects yet. Create one in the Laboratory and it will show up here!') + '</div>';
  }

  function segment(boxId, attr, fn) {
    $(boxId).addEventListener('click', (e) => {
      const b = e.target.closest('button'); if (!b) return;
      $(boxId).querySelectorAll('button').forEach((x) => x.classList.toggle('on', x === b));
      fn(b.dataset[attr]); render();
    });
  }
  segment('trendTabs', 'days', (v) => (state.days = Number(v)));
  segment('sortTabs', 'sort', (v) => (state.sort = v));
  segment('typeChips', 'type', (v) => (state.type = v));
  $('cmSearch').addEventListener('input', (e) => { state.q = e.target.value.trim().toLowerCase(); render(); });
  [$('trendRow'), $('cmGrid')].forEach((box) => box.addEventListener('click', (e) => {
    const c = e.target.closest('.cm-card'); if (c) openModal(c.dataset.id);
  }));

  /* ---------- Modal ---------- */
  const current = () => all().find((p) => p.id === state.current);

  function openModal(id) {
    state.current = id;
    P.addView(id);
    const p = current();
    $('cmModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    $('mAv').textContent = initials(p.author);
    $('mTitle').textContent = p.title;
    $('mMeta').textContent = 'by ' + p.author + ' \u2022 ' + P.ago(p.createdAt) + ' \u2022 ' + KIND[p.type] + ' project';
    $('mDesc').textContent = p.description || '';
    $('mDesc').classList.toggle('hidden', !p.description);
    $('mOut').classList.toggle('hidden', p.type !== 'python');
    $('mOut').textContent = 'Press Run to start.';
    $('mFrame').classList.toggle('hidden', p.type === 'python');
    $('mFrame').removeAttribute('src'); $('mFrame').removeAttribute('srcdoc');
    $('mRun').classList.toggle('hidden', p.type === 'scratch');
    $('mStatus').textContent = '';
    if (p.type === 'scratch') $('mFrame').src = SCRATCH_URL;
    else if (p.type !== 'python') showFrame(p);
    modalStats();
    render();
  }

  function showFrame(p) {
    $('mFrame').srcdoc = p.type === 'website' ? p.code : P.slidesHtml(p.code);
  }

  function closeModal() {
    state.current = null;
    $('cmModal').classList.add('hidden');
    $('mFrame').removeAttribute('src'); $('mFrame').removeAttribute('srcdoc');
    document.body.style.overflow = '';
  }

  function modalStats() {
    const p = current();
    const liked = !!P.likes()[p.id];
    const cs = P.comments(p.id);
    $('mLike').className = liked ? 'on' : '';
    $('mLike').textContent = (liked ? '\u2665 ' : '\u2661 ') + likes(p) + ' likes';
    $('mViews').textContent = '\uD83D\uDC41 ' + views(p) + ' views';
    $('mCount').textContent = '\uD83D\uDCAC ' + cs.length + ' comments';
    $('mList').innerHTML = cs.length ? cs.slice().reverse().map((c) =>
      '<div class="cm-comment"><div class="cm-av">' + P.esc(c.author[0].toUpperCase()) + '</div><div><b>' + P.esc(c.author) + '</b><small>' + P.ago(c.at) + '</small><p>' + P.esc(c.text) + '</p></div></div>'
    ).join('') : '<p style="color:#7b8794">No comments yet. Be the first!</p>';
  }

  async function runModal() {
    const p = current();
    if (p.type !== 'python') { showFrame(p); return; }
    const out = $('mOut'), st = $('mStatus');
    let text = '';
    out.textContent = '';
    st.textContent = 'Running...';
    try {
      if (!py) { st.textContent = 'Loading Python...'; py = await loadPyodide(); }
      py.setStdout({ batched: (m) => { text += m + '\n'; out.textContent = text; } });
      await py.runPythonAsync(p.code);
      st.textContent = 'Program finished';
      if (!text.trim()) out.textContent = '(This program did not produce any output)';
    } catch (err) {
      out.textContent = err.message || String(err);
      st.textContent = 'An error happened';
    }
  }

  $('cmClose').addEventListener('click', () => { closeModal(); render(); });
  $('cmModal').addEventListener('click', (e) => { if (e.target === $('cmModal')) { closeModal(); render(); } });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && state.current) { closeModal(); render(); } });
  $('mRun').addEventListener('click', runModal);
  $('mLike').addEventListener('click', () => { P.toggleLike(state.current); modalStats(); render(); });
  $('mForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const text = $('mInput').value.trim();
    if (!text) return;
    const user = P.user();
    if (!user) { $('mInput').value = ''; $('mInput').placeholder = 'Please log in to comment.'; return; }
    P.addComment(state.current, user.name, text);
    $('mInput').value = '';
    modalStats(); render();
  });
  render();
})();