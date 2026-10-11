/**
 * Previews student HTML/CSS in a sandboxed iframe (no allow-same-origin) and checks rules inside it.
 * Results are sent back via postMessage. Structure rules are re-checked on the server too;
 * CSS style rules can only be measured in the browser.
 */
const checker = (rules) => `
<script>
(function () {
  var rules = ${JSON.stringify(rules).replace(/</g, '\\u003c')};
  function norm(prop, value) {
    var probe = document.createElement('div');
    probe.style.setProperty(prop, value);
    document.body.appendChild(probe);
    var v = getComputedStyle(probe).getPropertyValue(prop);
    probe.remove();
    return v.trim();
  }
  function check(r) {
    try {
      var el = document.querySelector(r.input);
      var exp = String(r.expected || '').trim().toLowerCase();
      switch (r.kind) {
        case 'exists': return !!el;
        case 'count': return document.querySelectorAll(r.input).length >= Math.max(1, parseInt(r.expected, 10) || 1);
        case 'text': return !!el && el.textContent.replace(/\\s+/g, ' ').toLowerCase().indexOf(exp) !== -1;
        case 'attr': return !!el && el.hasAttribute(r.property) && (exp === '' || el.getAttribute(r.property).toLowerCase().indexOf(exp) !== -1);
        case 'style': return !!el && getComputedStyle(el).getPropertyValue(r.property).trim() === norm(r.property, r.expected);
      }
    } catch (e) {}
    return false;
  }
  window.addEventListener('load', function () {
    var out = {};
    rules.forEach(function (r) { out[r.id] = check(r); });
    parent.postMessage({ type: 'alp-html-check', results: out }, '*');
  });
})();
<\/script>`;

export function buildSrcdoc(html, rules = []) {
    const doc = html || '';
    const script = rules.length ? checker(rules) : '';
    return /<\/body>/i.test(doc) ? doc.replace(/<\/body>/i, script + '</body>') : doc + script;
}

/** Run the checks in a hidden iframe; result is {ruleId: boolean}. */
export function checkHtml(html, rules) {
    return new Promise((resolve) => {
        const frame = document.createElement('iframe');
        frame.setAttribute('sandbox', 'allow-scripts');
        frame.style.cssText = 'position:absolute;width:800px;height:600px;left:-9999px;top:0;border:0';
        const done = (results) => {
            window.removeEventListener('message', onMessage);
            clearTimeout(timer);
            frame.remove();
            resolve(results);
        };
        const onMessage = (e) => {
            if (e.source === frame.contentWindow && e.data?.type === 'alp-html-check') done(e.data.results);
        };
        const timer = setTimeout(() => done(Object.fromEntries(rules.map((r) => [r.id, false]))), 4000);
        window.addEventListener('message', onMessage);
        frame.srcdoc = buildSrcdoc(html, rules);
        document.body.appendChild(frame);
    });
}
