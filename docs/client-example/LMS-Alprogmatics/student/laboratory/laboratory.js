const P = window.Projects;
let pythonRuntime = null;
let codeEditor = null;
let errorLine = null;
let current = null; // project being edited
let filter = 'all';
let detailId = null;

const STARTER = {
  python: 'name = "friend"\nprint("Hello,", name)\n\nfor i in range(1, 4):\n    print("Step", i)\n',
  scratch: '',
  presentation: '# Welcome!\nMy first presentation\n---\n# About me\nI love coding\nI am learning Python\n---\n# Thank you!\nSee you next time\n',
  website: '<h1>My first page</h1>\n<p>Edit me and press Run!</p>\n<style>\n  h1 { color: rebeccapurple; }\n</style>\n'
};
const LABEL = { python: 'Python', scratch: 'Scratch', presentation: 'Presentation', website: 'Website' };
const THUMB = { python: 'Py', scratch: '&#128049;', presentation: '&#128250;', website: '&lt;/&gt;' };
const SCRATCH_URL = '../course/scratch/scratch-gui/build/index.html';

function slidesHtml(src) {
  const slides = src.split(/^---\s*$/m).map((s) => s.trim()).filter(Boolean).map((s) => {
    const lines = s.split('\n');
    const title = /^#\s*/.test(lines[0]) ? lines.shift().replace(/^#\s*/, '') : '';
    const esc = (x) => x.replace(/&/g, '&amp;').replace(/</g, '&lt;');
    return '<section><h1>' + esc(title) + '</h1>' + lines.filter((l) => l.trim()).map((l) => '<p>' + esc(l) + '</p>').join('') + '</section>';
  });
  if (!slides.length) return '<p style="font:20px sans-serif;padding:20px">Write some slides first.</p>';
  return '<!doctype html><meta charset="utf-8"><style>*{box-sizing:border-box}body{margin:0;font-family:Segoe UI,sans-serif;background:#1d2145;color:#fff;height:100vh;overflow:hidden}' +
    'section{display:none;height:100vh;padding:8vh 10vw;flex-direction:column;justify-content:center;gap:2vh;background:linear-gradient(135deg,#5b4bff,#ff7a59)}section.on{display:flex}' +
    'h1{font-size:7vh;margin:0 0 2vh}p{font-size:4vh;margin:0}nav{position:fixed;bottom:2vh;left:0;right:0;text-align:center}' +
    'button{border:0;border-radius:20px;padding:1vh 3vh;font-size:2.4vh;font-weight:700;margin:0 6px;cursor:pointer}</style>' + slides.join('') +
    '<nav><button onclick="go(-1)">&lsaquo; Back</button><span id="n"></span><button onclick="go(1)">Next &rsaquo;</button></nav>' +
    '<script>var s=document.querySelectorAll("section"),i=0;function go(d){i=Math.max(0,Math.min(s.length-1,i+d));s.forEach(function(e,k){e.className=k==i?"on":""});document.getElementById("n").textContent=" "+(i+1)+" / "+s.length+" "}' +
    'document.onkeydown=function(e){if(e.key=="ArrowRight")go(1);if(e.key=="ArrowLeft")go(-1)};go(0)<\/script>';
}
const $ = (id) => document.getElementById(id);

/* ---------- Python runtime ---------- */
const outTarget = () => (detailId ? $('dtConsole') : $('labOutput'));
async function loadPythonRuntime() {
  const status = $('labStatus');
  status.textContent = 'Loading Python...';
  status.className = 'loading';
  pythonRuntime = await loadPyodide();
  pythonRuntime.setStdout({ batched: (m) => (outTarget().textContent += m + '\n') });
  status.textContent = 'Python is ready';
  status.className = 'ready';
}

async function runLab() {
  const code = codeEditor.getValue();
  if (current.type === 'website' || current.type === 'presentation') {
    $('edPreview').srcdoc = current.type === 'website' ? code : slidesHtml(code);
    $('labStatus').textContent = 'Preview updated';
    $('labStatus').className = 'ready';
    return;
  }
  const output = $('labOutput');
  const status = $('labStatus');
  output.textContent = '';
  clearErrorLine();
  status.textContent = 'Running...';
  status.className = 'loading';
  try {
    if (!pythonRuntime) await loadPythonRuntime();
    await pythonRuntime.runPythonAsync(code);
    status.textContent = 'Program finished';
    status.className = 'ready';
    if (!output.textContent.trim()) output.textContent = '(This program did not produce any output)';
  } catch (error) {
    output.textContent = error.message || String(error);
    markErrorLine(error.message || String(error));
    status.textContent = 'An error happened';
    status.className = 'error';
  }
}

function clearLab() {
  codeEditor.setValue('');
  $('labOutput').textContent = 'Console cleared.';
  clearErrorLine();
}

function clearErrorLine() {
  if (!codeEditor || errorLine === null) return;
  codeEditor.removeLineClass(errorLine, 'wrap', 'cm-error-line');
  codeEditor.setGutterMarker(errorLine, 'python-error-gutter', null);
  errorLine = null;
}

function markErrorLine(message) {
  const match = message.match(/line (\d+)/i);
  if (!match) return;
  errorLine = Number(match[1]) - 1;
  codeEditor.addLineClass(errorLine, 'wrap', 'cm-error-line');
  const marker = document.createElement('span');
  marker.className = 'python-error-marker';
  marker.title = 'There is an error on this line';
  marker.textContent = '\u25CF';
  codeEditor.setGutterMarker(errorLine, 'python-error-gutter', marker);
}

function setupEditor() {
  codeEditor = CodeMirror.fromTextArea($('labCode'), {
    mode: { name: 'python', version: 3 },
    theme: 'material-darker',
    lineNumbers: true,
    indentUnit: 4,
    tabSize: 4,
    indentWithTabs: false,
    smartIndent: true,
    matchBrackets: true,
    lineWrapping: true,
    gutters: ['CodeMirror-linenumbers', 'python-error-gutter']
  });
  codeEditor.setSize(null, 280);
  codeEditor.on('change', clearErrorLine);
}

/* ---------- Views ---------- */
function show(view) {
  $('viewList').classList.toggle('hidden', view !== 'list');
  $('viewEdit').classList.toggle('hidden', view !== 'edit');
  $('viewDetail').classList.toggle('hidden', view !== 'detail');
  if (view !== 'detail') { detailId = null; $('dtFrame').removeAttribute('src'); $('dtFrame').removeAttribute('srcdoc'); }
}

function setType(type) {
  current.type = type;
  const scratch = type === 'scratch';
  const preview = type === 'website' || type === 'presentation';
  codeEditor.setOption('mode', type === 'website' ? 'htmlmixed' : type === 'presentation' ? 'markdown' : { name: 'python', version: 3 });
  $('labTitleText').textContent = LABEL[type] + (type === 'presentation' ? ' Slides' : ' Playground');
  $('labRunBtn').textContent = type === 'python' ? '\u25B6 Run Python' : '\u25B6 Run ' + (type === 'presentation' ? 'slideshow' : 'preview');
  $('codePane').classList.toggle('hidden', scratch);
  $('pyOut').classList.toggle('hidden', type !== 'python');
  $('edPreview').classList.toggle('hidden', !preview);
  $('edScratch').classList.toggle('hidden', !scratch);
  if (scratch && !$('edScratch').getAttribute('src')) $('edScratch').src = SCRATCH_URL;
  $('labStatus').textContent = type === 'python' ? (pythonRuntime ? 'Python is ready' : 'Runtime not loaded yet') : 'Ready';
  $('labStatus').className = '';
}

function openEditor(id, type) {
  const user = P.user();
  $('loginHint').classList.toggle('hidden', !!user);
  $('edSave').disabled = !user;
  current = id ? Object.assign({}, P.get(id)) : { id: P.newId(), type, title: '', description: '', code: STARTER[type] };
  $('edTitle').value = current.title;
  $('edDesc').value = current.description;
  $('edHeading').textContent = (id ? 'Edit ' : 'New ') + LABEL[current.type] + ' project';
  $('pickModal').classList.add('hidden');
  show('edit');
  setType(current.type);
  codeEditor.setValue(current.code || '');
  codeEditor.refresh();
  $('labOutput').textContent = 'Click Run to run your program.';
  const preview = current.type === 'website' ? current.code : current.type === 'presentation' ? slidesHtml(current.code) : '';
  $('edPreview').srcdoc = preview;
  window.scrollTo(0, 0);
}
function saveProject() {
  const title = $('edTitle').value.trim();
  if (!title) { $('edTitle').focus(); toast('Please give your project a title.'); return; }
  const user = P.user();
  if (!user) return;
  const existing = P.get(current.id);
  P.save({
    id: current.id,
    type: current.type,
    title,
    description: $('edDesc').value.trim(),
    code: codeEditor.getValue(),
    author: user.name,
    createdAt: existing ? existing.createdAt : Date.now(),
    updatedAt: Date.now()
  });
  renderList();
  show('list');
  toast('Project saved! It now appears in Community.', '../community/index.html', 'See it');
}

function deleteProject(id) {
  if (!confirm('Delete this project? It will also disappear from Community.')) return;
  P.remove(id);
  renderList();
}

function renderList() {
  const user = P.user();
  const mine = P.all().filter((p) => user && p.author === user.name);
  const list = mine.filter((p) => filter === 'all' || p.type === filter);
  const grid = $('projGrid');
  if (!list.length) {
    grid.innerHTML = '<div class="lab-empty" style="grid-column:1/-1"><h2>' +
      (mine.length ? 'No projects in this filter' : 'No projects yet') + '</h2><p>' +
      (user ? 'Press "Create project" to build something and share it with the community.' : 'Log in first, then create your first project.') + '</p></div>';
    return;
  }
  grid.innerHTML = list.map((p) =>
    '<article class="proj-card" data-open="' + p.id + '"><div class="proj-thumb ' + p.type + '">' + THUMB[p.type] + '</div>' +
    '<div class="proj-body"><h3>' + P.esc(p.title) + '</h3><small>' + LABEL[p.type] + ' \u2022 ' + P.ago(p.updatedAt) + '</small>' +
    (p.description ? '<p>' + P.esc(p.description) + '</p>' : '') +
    '<div class="proj-stats"><span>\u2661 ' + P.likeCount(p.id) + '</span><span>\uD83D\uDC41 ' + P.views(p.id) + '</span><span>\uD83D\uDCAC ' + P.comments(p.id).length + '</span></div>' +
    '<div class="proj-actions"><button data-edit="' + p.id + '">Edit</button><button class="danger" data-del="' + p.id + '" aria-label="Delete">Delete</button></div></div></article>'
  ).join('');
}

let toastTimer;
function toast(msg, href, linkText) {
  const t = $('labToast');
  t.textContent = msg;
  if (href) { const a = document.createElement('a'); a.href = href; a.textContent = linkText; t.append(a); }
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 4500);
}

/* ---------- Init ---------- */
setupEditor();
$('projGrid').addEventListener('click', (e) => {
  const ed = e.target.closest('[data-edit]'); if (ed) { openEditor(ed.dataset.edit); return; }
  const del = e.target.closest('[data-del]'); if (del) { deleteProject(del.dataset.del); return; }
  const op = e.target.closest('[data-open]'); if (op) openDetail(op.dataset.open);
});
document.querySelectorAll('.lab-chip').forEach((c) => c.addEventListener('click', () => {
  filter = c.dataset.filter;
  document.querySelectorAll('.lab-chip').forEach((x) => x.classList.toggle('on', x === c));
  renderList();
}));
document.querySelectorAll('[data-pick]').forEach((b) => b.addEventListener('click', () => openEditor(null, b.dataset.pick)));
$('pickClose').addEventListener('click', () => $('pickModal').classList.add('hidden'));
$('createBtn').addEventListener('click', () => $('pickModal').classList.remove('hidden'));
$('edCancel').addEventListener('click', () => { show('list'); $('edScratch').removeAttribute('src'); });
$('edSave').addEventListener('click', saveProject);
renderList();
/* ---------- Project detail ---------- */
function openDetail(id, count) {
  const p = P.get(id);
  if (!p) return;
  detailId = id;
  if (count !== false) P.addView(id);
  show('detail');
  detailId = id;
  $('dtTitle').textContent = p.title;
  $('dtMeta').textContent = LABEL[p.type] + ' project \u2022 by ' + p.author + ' \u2022 ' + P.ago(p.updatedAt);
  $('dtDesc').textContent = p.description || '';
  $('dtDesc').classList.toggle('hidden', !p.description);
  $('dtPop').classList.add('hidden');
  $('dtConsole').classList.toggle('hidden', p.type !== 'python');
  $('dtConsole').textContent = 'Press Run to start.';
  $('dtFrame').classList.toggle('hidden', p.type === 'python');
  $('dtFrame').removeAttribute('src');
  $('dtStatus').textContent = '';
  if (p.type === 'scratch') $('dtFrame').src = SCRATCH_URL;
  else if (p.type === 'website') $('dtFrame').srcdoc = p.code;
  else if (p.type === 'presentation') $('dtFrame').srcdoc = slidesHtml(p.code);
  $('dtRun').classList.toggle('hidden', p.type === 'scratch');
  renderStats();
  window.scrollTo(0, 0);
}

function renderStats() {
  const p = P.get(detailId);
  const liked = P.likes()[detailId];
  const cs = P.comments(detailId);
  $('dtLike').className = liked ? 'on' : '';
  $('dtLike').textContent = (liked ? '\u2665 ' : '\u2661 ') + P.likeCount(detailId) + ' likes';
  $('dtViews').textContent = '\uD83D\uDC41 ' + P.views(detailId) + ' views';
  $('dtCommentCount').textContent = '\uD83D\uDCAC ' + cs.length + ' comments';
  $('dtList').innerHTML = cs.length ? cs.slice().reverse().map((c) =>
    '<div class="dt-comment"><div class="av">' + P.esc(c.author[0].toUpperCase()) + '</div><div><b>' + P.esc(c.author) + '</b><small>' + P.ago(c.at) + '</small><p>' + P.esc(c.text) + '</p></div></div>'
  ).join('') : '<p style="color:#7b8794">No comments yet. Be the first!</p>';
}

async function runDetail() {
  const p = P.get(detailId);
  if (p.type !== 'python') { $('dtFrame').srcdoc = p.type === 'website' ? p.code : slidesHtml(p.code); return; }
  const out = $('dtConsole'), st = $('dtStatus');
  out.textContent = '';
  st.textContent = 'Running...';
  try {
    if (!pythonRuntime) await loadPythonRuntime();
    await pythonRuntime.runPythonAsync(p.code);
    st.textContent = 'Program finished';
    if (!out.textContent.trim()) out.textContent = '(This program did not produce any output)';
  } catch (err) {
    out.textContent = err.message || String(err);
    st.textContent = 'An error happened';
  }
}

$('dtBack').addEventListener('click', () => { show('list'); renderList(); });
$('dtRun').addEventListener('click', runDetail);
$('dtDots').addEventListener('click', (e) => { e.stopPropagation(); $('dtPop').classList.toggle('hidden'); });
document.addEventListener('click', () => $('dtPop').classList.add('hidden'));
$('dtEdit').addEventListener('click', () => openEditor(detailId));
$('dtShare').addEventListener('click', () => { location.href = '../community/index.html'; });
$('dtDelete').addEventListener('click', () => {
  if (!confirm('Delete this project? It will also disappear from Community.')) return;
  P.remove(detailId); show('list'); renderList();
});
$('dtLike').addEventListener('click', () => { P.toggleLike(detailId); renderStats(); });
$('dtForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const text = $('dtInput').value.trim();
  const user = P.user();
  if (!text) return;
  if (!user) { toast('Please log in to comment.', '../login/index.html', 'Log in'); return; }
  P.addComment(detailId, user.name, text);
  $('dtInput').value = '';
  renderStats();
});