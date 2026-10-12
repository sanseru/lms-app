/**
 * Presentation projects: markdown text, one slide per block separated by a line with `---`
 * (the client prototype's format). A small, safe markdown subset: everything is escaped
 * first, then headings, lists, bold, italic, inline code and http(s) images are added back.
 */
const escape = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

function inline(text) {
    return escape(text)
        .replace(/!\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)/g, '<img src="$2" alt="$1" loading="lazy">')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/(^|[^*])\*([^*\s][^*]*)\*/g, '$1<em>$2</em>');
}

function block(src) {
    const out = [];
    let list = null; // 'ul' | 'ol'
    const close = () => list && (out.push(`</${list}>`), (list = null));

    for (const raw of src.split('\n')) {
        const line = raw.trimEnd();
        let m;
        if ((m = line.match(/^(#{1,3})\s+(.*)$/))) {
            close();
            out.push(`<h${m[1].length}>${inline(m[2])}</h${m[1].length}>`);
        } else if ((m = line.match(/^\s*[-*]\s+(.*)$/)) || (m = line.match(/^\s*\d+[.)]\s+(.*)$/))) {
            const type = /^\s*\d/.test(line) ? 'ol' : 'ul';
            if (list !== type) (close(), out.push(`<${type}>`), (list = type));
            out.push(`<li>${inline(m[1])}</li>`);
        } else if (line.trim() === '') {
            close();
        } else {
            close();
            out.push(`<p>${inline(line)}</p>`);
        }
    }
    close();
    return out.join('');
}

/** @returns {string[]} one HTML string per slide (empty slides are skipped) */
export function parseSlides(src) {
    return (src ?? '')
        .split(/^\s*---\s*$/m)
        .map((s) => s.trim())
        .filter(Boolean)
        .map(block);
}
