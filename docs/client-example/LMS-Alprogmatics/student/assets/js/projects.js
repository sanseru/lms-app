// Shared project store (browser localStorage). Used by Laboratory and Community.
window.Projects = (function () {
  const KEY = 'alprogmatics-projects-v1';
  const LIKES = 'alprogmatics-project-likes-v1';
  const VIEWS = 'alprogmatics-project-views-v1';
  const COMM = 'alprogmatics-project-comments-v1';
  const read = (k, d) => { try { return JSON.parse(localStorage.getItem(k)) || d; } catch (e) { return d; } };
  const write = (k, v) => localStorage.setItem(k, JSON.stringify(v));
  return {
    user() { return read('alprogmatics-user', null); },
    all() { return read(KEY, []); },
    get(id) { return this.all().find((p) => p.id === id); },
    save(p) {
      const list = this.all();
      const i = list.findIndex((x) => x.id === p.id);
      if (i >= 0) list[i] = p; else list.unshift(p);
      write(KEY, list);
    },
    remove(id) { write(KEY, this.all().filter((p) => p.id !== id)); },
    likeCount(id) { return this.likes()[id] ? 1 : 0; },
    likes() { return read(LIKES, {}); },
    toggleLike(id) {
      const l = this.likes(); l[id] = !l[id]; write(LIKES, l); return l[id];
    },
    // Views are counted once per account (guests share one 'guest' entry). Old numeric counts are kept as anonymous viewers.
    viewers(id) { const x = read(VIEWS, {})[id]; return Array.isArray(x) ? x : Array.from({ length: x || 0 }, (_, i) => '#old' + i); },
    views(id) { return this.viewers(id).length; },
    addView(id) {
      const who = (this.user() || {}).name || 'guest';
      const list = this.viewers(id);
      if (list.includes(who)) return;
      const v = read(VIEWS, {}); v[id] = list.concat(who); write(VIEWS, v);
    },
    comments(id) { return read(COMM, {})[id] || []; },
    addComment(id, author, text) { const c = read(COMM, {}); (c[id] = c[id] || []).push({ author, text, at: Date.now() }); write(COMM, c); },
    newId() { return 'p' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6); },
    ago(ts) {
      const s = Math.max(1, Math.floor((Date.now() - ts) / 1000));
      if (s < 60) return 'just now';
      if (s < 3600) return Math.floor(s / 60) + ' min ago';
      if (s < 86400) return Math.floor(s / 3600) + ' hours ago';
      return Math.floor(s / 86400) + ' days ago';
    },
    esc(s) { return String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
  };
})();

Projects.slidesHtml = function(src) {
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
};
