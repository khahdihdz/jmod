/* JMod WYSIWYG Editor
 * Lightweight, dependency-free BBCode editor for JohnCMS/JMod.
 * Keeps the original textarea as the form source of truth.
 */
(function () {
    'use strict';

    if (window.JmodEditor) return;

    var CSS = [
        '.jmod-editor{border:1px solid #cbd5e1;border-radius:10px;background:#fff;overflow:hidden;max-width:100%;box-shadow:0 1px 2px rgba(15,23,42,.05)}',
        '.jmod-editor-toolbar{display:flex;flex-wrap:wrap;gap:4px;padding:7px;border-bottom:1px solid #e2e8f0;background:#f8fafc;align-items:center;max-width:100%}',
        '.jmod-editor-toolbar button,.jmod-editor-toolbar select{border:1px solid #cbd5e1;background:#fff;color:#1e293b;border-radius:6px;min-height:32px;padding:4px 9px;font:inherit;cursor:pointer}',
        '.jmod-editor-toolbar button:hover,.jmod-editor-toolbar select:hover{background:#f1f5f9;border-color:#94a3b8}',
        '.jmod-editor-toolbar button:focus,.jmod-editor-toolbar select:focus{outline:2px solid rgba(37,99,235,.25);outline-offset:1px}',
        '.jmod-editor-toolbar .sep{width:1px;height:24px;background:#cbd5e1;margin:0 2px;flex:0 0 auto}',
        '.jmod-editor-toolbar .jmod-align-group{display:inline-flex;gap:2px}',
        '.jmod-editor-body{min-height:190px;padding:12px;outline:0;line-height:1.65;overflow-wrap:anywhere;word-break:break-word}',
        '.jmod-editor-body:empty:before{content:attr(data-placeholder);color:#94a3b8;pointer-events:none}',
        '.jmod-editor-body img{max-width:100%;height:auto;vertical-align:middle}',
        '.jmod-editor-body blockquote{margin:8px 0;padding:8px 12px;border-left:4px solid #94a3b8;background:#f8fafc}',
        '.jmod-editor-body pre{white-space:pre-wrap;overflow:auto;padding:10px;background:#f1f5f9;border-radius:6px}',
        '.jmod-editor-body pre code{font:13px/1.55 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;white-space:pre-wrap}',
        '.jmod-editor-body .bb-h{margin:.4rem 0 .25rem;font-weight:700;line-height:1.3}',
        '.jmod-editor-body .bb-h1{font-size:1.5em}.jmod-editor-body .bb-h2{font-size:1.3em}',
        '.jmod-editor-body .bb-h3{font-size:1.15em}.jmod-editor-body .bb-h4{font-size:1em}',
        '.jmod-editor-body ul,.jmod-editor-body ol{margin:.4rem 0;padding-left:1.5rem}',
        '.jmod-editor-spoiler{border:1px solid #cbd5e1;border-radius:6px;margin:8px 0;overflow:hidden}',
        '.jmod-editor-spoiler>button{width:100%;text-align:left;border:0;background:#f1f5f9;padding:8px 10px;cursor:pointer}',
        '.jmod-editor-spoiler>div{padding:8px 10px}',
        '.jmod-editor-status{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;padding:5px 9px;color:#64748b;font-size:12px;border-top:1px solid #eef2f7}',
        '.jmod-editor-source{display:none;width:100%;min-height:190px;border:0;resize:vertical;padding:12px;font:14px/1.6 ui-monospace,SFMono-Regular,Consolas,monospace;outline:0;box-sizing:border-box}',
        '.jmod-editor.source-mode .jmod-editor-body{display:none}.jmod-editor.source-mode .jmod-editor-source{display:block}',
        '.jmod-editor .jmod-color{width:36px;padding:4px}',
        '.jmod-editor .jmod-heading{min-width:108px}',
        '.jmod-editor .jmod-code-lang{min-width:78px}',
        '.jmod-smiley{position:relative;display:inline-flex}',
        '.jmod-smiley-toggle{font-size:19px;line-height:1;min-width:36px!important;min-height:32px!important;font-family:Arial,\'Segoe UI Symbol\',sans-serif;font-weight:600;display:inline-flex;align-items:center;justify-content:center}',
        '.jmod-smiley-panel{display:none;position:absolute;z-index:1000;top:calc(100% + 5px);left:0;width:min(360px,calc(100vw - 24px));max-height:300px;overflow:auto;padding:8px;grid-template-columns:repeat(7,1fr);gap:4px;background:#fff;border:1px solid #cbd5e1;border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.16)}',
        '.jmod-smiley.open .jmod-smiley-panel{display:grid}',
        '.jmod-smiley-hint{grid-column:1/-1;font-size:12px;line-height:1.45;color:#64748b;padding:2px 4px 6px}',
        '.jmod-smiley-item{border:1px solid transparent!important;background:transparent!important;border-radius:7px!important;min-height:42px!important;padding:4px!important;cursor:pointer}',
        '.jmod-smiley-item img{display:block;max-width:32px;max-height:32px;margin:auto;object-fit:contain}',
        '.jmod-smiley-item:hover{background:#f1f5f9!important;border-color:#cbd5e1!important}',
        '.jmod-smiley-item:focus{outline:2px solid rgba(37,99,235,.25)!important;outline-offset:-1px}',
        '@media(max-width:600px){.jmod-smiley-panel{position:fixed;left:10px;right:10px;bottom:10px;top:auto;width:auto;max-height:45vh}.jmod-editor-toolbar{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch}.jmod-editor-toolbar button,.jmod-editor-toolbar select{min-height:36px;flex:0 0 auto}.jmod-editor-body{min-height:220px;padding:10px}}'
    ].join('');

    function injectCss() {
        if (document.getElementById('jmod-editor-css')) return;
        var style = document.createElement('style');
        style.id = 'jmod-editor-css';
        style.textContent = CSS;
        document.head.appendChild(style);
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toHex(value) {
        var c = String(value || '').trim();
        var m = /^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i.exec(c);
        if (!m) return c;
        function h(n) { n = Math.max(0, Math.min(255, parseInt(n, 10))); return ('0' + n.toString(16)).slice(-2); }
        return '#' + h(m[1]) + h(m[2]) + h(m[3]);
    }

    function safeUrl(value, allowMail) {
        var url = String(value || '').trim();
        try {
            var parsed = new URL(url, window.location.href);
            var scheme = parsed.protocol.toLowerCase();
            if (scheme === 'http:' || scheme === 'https:' || (allowMail && scheme === 'mailto:')) {
                return url;
            }
        } catch (e) {}
        return '';
    }

    function bbToHtml(value) {
        var s = String(value || '');
        var stash = [];
        s = s.replace(/\[img(?:=(\d{1,4})x(\d{1,4}))?\]([\s\S]*?)\[\/img\]/gi, function(_, w, h, u) {
            var url = safeUrl(u, false);
            if (!url) return esc(_);
            var size = '';
            if (w && h) size = ' width="' + Math.min(parseInt(w, 10), 1600) + '" height="' + Math.min(parseInt(h, 10), 1200) + '"';
            var k = '__JMOD_IMAGE_' + stash.length + '__';
            stash.push('<a class="bb-image-link" href="' + esc(url) + '" target="_blank" rel="noopener noreferrer"><img src="' + esc(url) + '"' + size + ' alt="" loading="lazy" decoding="async"></a>');
            return k;
        });
        s = esc(s);
        s = s.replace(/\[b\]([\s\S]*?)\[\/b\]/gi, '<strong>$1</strong>');
        s = s.replace(/\[i\]([\s\S]*?)\[\/i\]/gi, '<em>$1</em>');
        s = s.replace(/\[u\]([\s\S]*?)\[\/u\]/gi, '<u>$1</u>');
        s = s.replace(/\[s\]([\s\S]*?)\[\/s\]/gi, '<s>$1</s>');
        s = s.replace(/\[small\]([\s\S]*?)\[\/small\]/gi, '<small>$1</small>');
        s = s.replace(/\[big\]([\s\S]*?)\[\/big\]/gi, '<big>$1</big>');
        s = s.replace(/\[red\]([\s\S]*?)\[\/red\]/gi, '<span style="color:red">$1</span>');
        s = s.replace(/\[green\]([\s\S]*?)\[\/green\]/gi, '<span style="color:green">$1</span>');
        s = s.replace(/\[blue\]([\s\S]*?)\[\/blue\]/gi, '<span style="color:blue">$1</span>');
        s = s.replace(/\[color=(#[0-9a-f]{3,8}|rgba?\([^)]*\)|[a-z-]+)\]([\s\S]*?)\[\/color\]/gi, '<span style="color:$1">$2</span>');
        s = s.replace(/\[bg=(#[0-9a-f]{3,8}|rgba?\([^)]*\)|[a-z-]+)\]([\s\S]*?)\[\/bg\]/gi, '<span style="background-color:$1">$2</span>');
        s = s.replace(/\[(quote|c)\]([\s\S]*?)\[\/(quote|c)\]/gi, '<blockquote>$2</blockquote>');
        s = s.replace(/\[h([1-4])\]([\s\S]*?)\[\/h\1\]/gi, '<div class="bb-h bb-h$1" data-bb="h$1">$2</div>');
        s = s.replace(/\[center\]([\s\S]*?)\[\/center\]/gi, '<div style="text-align:center">$1</div>');
        s = s.replace(/\[left\]([\s\S]*?)\[\/left\]/gi, '<div style="text-align:left">$1</div>');
        s = s.replace(/\[right\]([\s\S]*?)\[\/right\]/gi, '<div style="text-align:right">$1</div>');
        s = s.replace(/\[justify\]([\s\S]*?)\[\/justify\]/gi, '<div style="text-align:justify">$1</div>');
        s = s.replace(/\[sup\]([\s\S]*?)\[\/sup\]/gi, '<sup>$1</sup>');
        s = s.replace(/\[sub\]([\s\S]*?)\[\/sub\]/gi, '<sub>$1</sub>');
        s = s.replace(/\[code(?:=(php|css|html|js|sql|xml))?\]([\s\S]*?)\[\/code\]/gi, function(_, lang, body) {
            return '<pre><code data-lang="' + (lang || 'php') + '">' + body + '</code></pre>';
        });
        s = s.replace(/\[list=1\]([\s\S]*?)\[\/list\]/gi, '<ol>$1</ol>');
        s = s.replace(/\[list\]([\s\S]*?)\[\/list\]/gi, '<ul>$1</ul>');
        s = s.replace(/\[\*\]([\s\S]*?)\[\/\*\]/gi, '<li>$1</li>');
        s = s.replace(/\[url=(https?:\/\/[^\]]+)\]([\s\S]*?)\[\/url\]/gi, '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>');
        s = s.replace(/\[url\](https?:\/\/[^\[]+)\[\/url\]/gi, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        s = s.replace(/\[hr\]/gi, '<hr>');
        s = s.replace(/\[br\]/gi, '<br>');
        s = s.replace(/\[spoiler(?:=([^\]]*))?\]([\s\S]*?)\[\/spoiler\]/gi, function(_, title, body) {
            return '<div class="jmod-editor-spoiler"><button type="button" contenteditable="false">' + esc(title || 'Spoiler') + '</button><div>' + body + '</div></div>';
        });
        s = s.replace(/\r?\n/g, '<br>');
        s = s.replace(/__JMOD_IMAGE_(\d+)__/g, function(_, i) { return stash[Number(i)] || ''; });
        return s;
    }

    function preText(node) {
        var out = '';
        for (var i = 0; i < node.childNodes.length; i++) {
            var c = node.childNodes[i];
            if (c.nodeType === 3) {
                out += c.nodeValue.replace(/\u00a0/g, ' ');
            } else if (c.nodeType === 1) {
                var t = c.tagName.toLowerCase();
                if (t === 'br') out += '\n';
                else if (t === 'div' || t === 'p') out += '\n' + preText(c);
                else out += preText(c);
            }
        }
        return out;
    }

    function htmlToBb(root) {
        function walk(node) {
            if (node.nodeType === Node.TEXT_NODE) return node.nodeValue.replace(/\u00a0/g, ' ');
            if (node.nodeType !== Node.ELEMENT_NODE) return '';

            var tag = node.tagName.toLowerCase();

            if (tag === 'pre') {
                var lang = 'php';
                var codeEl = node.querySelector('code');
                if (codeEl && codeEl.getAttribute('data-lang')) lang = codeEl.getAttribute('data-lang');
                else if (node.getAttribute('data-lang')) lang = node.getAttribute('data-lang');
                var body = preText(node).replace(/^\n+/, '').replace(/\n+$/, '');
                return '[code=' + lang + ']' + body + '[/code]\n';
            }

            var inner = '';
            for (var i = 0; i < node.childNodes.length; i++) inner += walk(node.childNodes[i]);

            if (tag === 'strong' || tag === 'b') return '[b]' + inner + '[/b]';
            if (tag === 'em' || tag === 'i') return '[i]' + inner + '[/i]';
            if (tag === 'u') return '[u]' + inner + '[/u]';
            if (tag === 's' || tag === 'strike' || tag === 'del') return '[s]' + inner + '[/s]';
            if (tag === 'a') {
                var href = safeUrl(node.getAttribute('href') || '', true);
                return href ? '[url=' + href + ']' + inner + '[/url]' : inner;
            }
            if (tag === 'img') {
                var src = safeUrl(node.getAttribute('src') || '', false);
                return src ? '[img]' + src + '[/img]' : '';
            }
            if (tag === 'blockquote') return '[quote]' + inner + '[/quote]';
            if (node.classList && node.classList.contains('jmod-editor-spoiler')) {
                var content = node.querySelector('div');
                var titleNode = node.querySelector(':scope > button');
                var title = titleNode ? titleNode.textContent.trim() : '';
                return '[spoiler' + (title && title !== 'Spoiler' ? '=' + title : '') + ']' + (content ? walk(content) : '') + '[/spoiler]';
            }
            if (tag === 'hr') return '[hr]';
            if (tag === 'br') return '\n';
            if (tag === 'li') return '[*]' + inner + '[/*]';
            if (tag === 'ul') return '[list]' + inner + '[/list]\n';
            if (tag === 'ol') return '[list=1]' + inner + '[/list]\n';
            if (tag === 'font') {
                var fcolor = node.getAttribute('color');
                var fsize = node.getAttribute('size');
                var fnum = parseInt(fsize, 10);
                var fout = inner;
                if (fsize) fout = (fnum >= 4 ? '[big]' : '[small]') + fout + (fnum >= 4 ? '[/big]' : '[/small]');
                if (fcolor) fout = '[color=' + toHex(fcolor) + ']' + fout + '[/color]';
                return fout;
            }
            if (tag === 'div' || tag === 'p') {
                if (node.classList) {
                    for (var lv = 1; lv <= 4; lv++) {
                        if (node.classList.contains('bb-h' + lv)) return '[h' + lv + ']' + inner + '[/h' + lv + ']\n';
                    }
                }
                var bb = node.getAttribute && node.getAttribute('data-bb');
                if (bb && /^h[1-4]$/.test(bb)) return '[' + bb + ']' + inner + '[/' + bb + ']\n';
                var align = node.style.textAlign || node.getAttribute('align') || '';
                var prefix = align === 'justify' ? '[justify]' : align === 'center' ? '[center]' : align === 'right' ? '[right]' : align === 'left' ? '[left]' : '';
                var suffix = align === 'justify' ? '[/justify]' : align === 'center' ? '[/center]' : align === 'right' ? '[/right]' : align === 'left' ? '[/left]' : '';
                return prefix + inner + suffix + '\n';
            }
            if (tag === 'span') {
                var out = inner;
                var st = node.style || {};
                if (st.color) out = '[color=' + toHex(st.color) + ']' + out + '[/color]';
                if (st.backgroundColor && st.backgroundColor !== 'transparent') out = '[bg=' + toHex(st.backgroundColor) + ']' + out + '[/bg]';
                var fw = st.fontWeight;
                if (fw && (fw === 'bold' || fw === 'bolder' || parseInt(fw, 10) >= 600)) out = '[b]' + out + '[/b]';
                if (st.fontStyle === 'italic') out = '[i]' + out + '[/i]';
                var td = st.textDecorationLine || st.textDecoration || '';
                if (/line-through/.test(td)) out = '[s]' + out + '[/s]';
                if (/underline/.test(td)) out = '[u]' + out + '[/u]';
                var fs = st.fontSize;
                if (fs) {
                    var px = parseFloat(fs);
                    if (!isNaN(px)) {
                        if (px >= 20) out = '[big]' + out + '[/big]';
                        else if (px <= 12) out = '[small]' + out + '[/small]';
                    }
                }
                return out;
            }
            if (tag === 'small') return '[small]' + inner + '[/small]';
            if (tag === 'big') return '[big]' + inner + '[/big]';
            if (tag === 'sup') return '[sup]' + inner + '[/sup]';
            if (tag === 'sub') return '[sub]' + inner + '[/sub]';
            return inner;
        }
        return walk(root).replace(/\n{3,}/g, '\n\n').replace(/^\n+|\n+$/g, '');
    }

    function saveSelection(editor) {
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) return;
        var range = selection.getRangeAt(0);
        if (editor.body.contains(range.commonAncestorContainer)) editor.savedRange = range.cloneRange();
    }

    function restoreSelection(editor) {
        if (!editor.savedRange) return;
        editor.body.focus();
        var selection = window.getSelection();
        if (!selection) return;
        selection.removeAllRanges();
        selection.addRange(editor.savedRange);
    }

    function exec(editor, command, value) {
        restoreSelection(editor);
        try { document.execCommand(command, false, value == null ? null : value); } catch (e) {}
        saveSelection(editor);
        sync(editor);
    }

    function execColor(editor, command, value) {
        restoreSelection(editor);
        try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
        try { document.execCommand(command, false, value); } catch (e) {}
        try { document.execCommand('styleWithCSS', false, false); } catch (e) {}
        saveSelection(editor);
        sync(editor);
    }

    function sync(editor) {
        editor.source.value = htmlToBb(editor.body);
        editor.textarea.value = editor.source.value;
        var text = editor.body.innerText || editor.body.textContent || '';
        editor.count.textContent = text.trim().length + ' ký tự';
        editor.dirty = false;
    }

    function insertHtml(editor, html) {
        restoreSelection(editor);
        editor.body.focus();
        try { document.execCommand('insertHTML', false, html); } catch (e) {
            var sel = window.getSelection();
            if (sel && sel.rangeCount) {
                sel.getRangeAt(0).deleteContents();
                sel.getRangeAt(0).insertNode(document.createTextNode(html));
            }
        }
        sync(editor);
    }

    function insertText(editor, text) {
        restoreSelection(editor);
        editor.body.focus();
        try { document.execCommand('insertText', false, text); } catch (e) {
            var sel = window.getSelection();
            if (sel && sel.rangeCount) {
                var range = sel.getRangeAt(0);
                range.deleteContents();
                range.insertNode(document.createTextNode(text));
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }
        saveSelection(editor);
        sync(editor);
    }

    function button(label, title, fn, cls) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = label;
        b.title = title;
        if (cls) b.className = cls;
        b.addEventListener('click', function(e) { e.preventDefault(); fn(e); });
        return b;
    }

    function separator() {
        var s = document.createElement('span');
        s.className = 'sep';
        s.setAttribute('aria-hidden', 'true');
        return s;
    }

    function currentBlock(editor, range) {
        var node = range.startContainer;
        var block = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
        while (block && block !== editor.body && !/^(DIV|P|LI|BLOCKQUOTE)$/.test(block.tagName)) block = block.parentElement;
        return (block && block !== editor.body && editor.body.contains(block)) ? block : null;
    }

    function applyAlign(editor, value) {
        restoreSelection(editor);
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) return;
        var range = selection.getRangeAt(0);
        if (!editor.body.contains(range.commonAncestorContainer)) return;

        var block = currentBlock(editor, range);
        if (block) {
            block.style.textAlign = value;
        } else {
            var wrapper = document.createElement('div');
            wrapper.style.textAlign = value;
            if (range.collapsed) wrapper.appendChild(document.createElement('br'));
            else wrapper.appendChild(range.extractContents());
            range.insertNode(wrapper);
            range.selectNodeContents(wrapper);
            selection.removeAllRanges();
            selection.addRange(range);
        }
        savePosition(editor, selection);
    }

    function applyHeading(editor, level) {
        restoreSelection(editor);
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) return;
        var range = selection.getRangeAt(0);
        if (!editor.body.contains(range.commonAncestorContainer)) return;

        var tag = 'h' + level;
        var block = currentBlock(editor, range);
        if (block) {
            var isSame = false;
            for (var i = 1; i <= 4; i++) {
                if (block.classList.contains('bb-h' + i)) isSame = isSame || (i === level);
                block.classList.remove('bb-h' + i);
            }
            if (isSame) {
                block.classList.remove('bb-h');
                block.removeAttribute('data-bb');
                if (!block.getAttribute('class')) block.removeAttribute('class');
            } else {
                block.classList.add('bb-h', 'bb-h' + level);
                block.setAttribute('data-bb', tag);
            }
        } else {
            var wrapper = document.createElement('div');
            wrapper.className = 'bb-h bb-h' + level;
            wrapper.setAttribute('data-bb', tag);
            if (range.collapsed) wrapper.appendChild(document.createElement('br'));
            else wrapper.appendChild(range.extractContents());
            range.insertNode(wrapper);
            range.selectNodeContents(wrapper);
            selection.removeAllRanges();
            selection.addRange(range);
        }
        savePosition(editor, selection);
    }

    function savePosition(editor, selection) {
        if (selection && selection.rangeCount) {
            var r = selection.getRangeAt(0);
            if (editor.body.contains(r.commonAncestorContainer)) editor.savedRange = r.cloneRange();
        }
        sync(editor);
    }

    function insertCode(editor, lang) {
        restoreSelection(editor);
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) return;
        var range = selection.getRangeAt(0);
        if (!editor.body.contains(range.commonAncestorContainer)) return;

        var pre = document.createElement('pre');
        var code = document.createElement('code');
        code.setAttribute('data-lang', lang || 'php');
        if (range.collapsed) {
            code.appendChild(document.createElement('br'));
        } else {
            code.textContent = range.toString();
            range.deleteContents();
        }
        pre.appendChild(code);
        range.insertNode(pre);

        var r = document.createRange();
        r.setStart(code, 0);
        r.collapse(true);
        selection.removeAllRanges();
        selection.addRange(r);
        savePosition(editor, selection);
    }

    function findLink(node, body) {
        var el = node && (node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement);
        while (el && el !== body && el.tagName !== 'A') el = el.parentElement;
        return (el && el !== body) ? el : null;
    }

    function linkAction(editor) {
        restoreSelection(editor);
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) return;
        var range = selection.getRangeAt(0);
        if (!editor.body.contains(range.commonAncestorContainer)) return;

        var existing = findLink(range.startContainer, editor.body);
        if (existing) {
            var current = existing.getAttribute('href') || '';
            var value = window.prompt('Sửa liên kết (để trống để bỏ liên kết):', current);
            if (value === null) return;
            var url = safeUrl(value, true);
            if (!url) {
                var parent = existing.parentNode;
                while (existing.firstChild) parent.insertBefore(existing.firstChild, existing);
                parent.removeChild(existing);
            } else {
                existing.setAttribute('href', url);
                existing.setAttribute('rel', 'noopener noreferrer');
                existing.setAttribute('target', '_blank');
            }
            savePosition(editor, selection);
            return;
        }

        var insert = promptUrl('URL liên kết:', 'https://', true);
        if (insert) exec(editor, 'createLink', insert);
    }

    function promptUrl(title, initial, allowMail) {
        var value = window.prompt(title, initial || 'https://');
        if (value == null) return '';
        return safeUrl(value, !!allowMail);
    }

    function createHeadingControl(editor) {
        var select = document.createElement('select');
        select.className = 'jmod-heading';
        select.title = 'Tiêu đề (heading)';
        [['','Định dạng'],['1','Tiêu đề 1'],['2','Tiêu đề 2'],['3','Tiêu đề 3'],['4','Tiêu đề 4'],['0','Văn bản thường']].forEach(function(x) {
            var o = document.createElement('option');
            o.value = x[0];
            o.textContent = x[1];
            select.appendChild(o);
        });
        select.addEventListener('change', function() {
            if (select.value === '0') {
                restoreSelection(editor);
                exec(editor, 'formatBlock', '<div>');
            } else if (select.value) {
                applyHeading(editor, parseInt(select.value, 10));
            }
            select.value = '';
        });
        return select;
    }

    function createCodeControl(editor) {
        var wrap = document.createElement('span');
        wrap.className = 'jmod-code-control';
        var lang = document.createElement('select');
        lang.className = 'jmod-code-lang';
        lang.title = 'Ngôn ngữ đoạn mã';
        [['php','PHP'],['js','JS'],['css','CSS'],['html','HTML'],['sql','SQL'],['xml','XML']].forEach(function(x) {
            var o = document.createElement('option');
            o.value = x[0];
            o.textContent = x[1];
            lang.appendChild(o);
        });
        var insert = button('Code', 'Chèn đoạn mã [code=…]', function() { insertCode(editor, lang.value); });
        wrap.appendChild(lang);
        wrap.appendChild(insert);
        return wrap;
    }

    function createSmileyPicker(editor) {
        var root = document.createElement('span');
        root.className = 'jmod-smiley';
        var toggle = button('☺', 'Chèn mặt cười JohnCMS — gõ mã như :) hoặc :ten_smiley:', function(e) {
            e.stopPropagation();
            saveSelection(editor);
            root.classList.toggle('open');
        }, 'jmod-smiley-toggle');
        var panel = document.createElement('span');
        panel.className = 'jmod-smiley-panel';
        panel.setAttribute('role', 'menu');
        root.appendChild(toggle);
        root.appendChild(panel);

        function addHint() {
            var hint = document.createElement('span');
            hint.className = 'jmod-smiley-hint';
            hint.textContent = 'Hướng dẫn: bấm một biểu tượng để chèn mã tại vị trí con trỏ. Bạn cũng có thể gõ trực tiếp mã như :) :( :D hoặc :ten_smiley:';
            panel.appendChild(hint);
        }

        function addSmiley(item) {
            if (!item || !item.code || !item.url) return;
            var itemButton = button('', 'Chèn ' + item.code, function(e) {
                e.stopPropagation();
                insertText(editor, item.code);
                root.classList.remove('open');
            }, 'jmod-smiley-item');
            itemButton.setAttribute('role', 'menuitem');
            var img = document.createElement('img');
            img.src = item.url;
            img.alt = item.code;
            img.loading = 'lazy';
            img.decoding = 'async';
            itemButton.appendChild(img);
            panel.appendChild(itemButton);
        }

        function fallback() {
            panel.innerHTML = '';
            addHint();
            [
                {code: ':)', url: (window.JMOD_HOME_URL || '') + '/images/smileys/simply/).gif'},
                {code: ':(', url: (window.JMOD_HOME_URL || '') + '/images/smileys/simply/(.gif'},
                {code: ':D', url: (window.JMOD_HOME_URL || '') + '/images/smileys/simply/D.gif'},
                {code: ':P', url: (window.JMOD_HOME_URL || '') + '/images/smileys/simply/P.gif'}
            ].forEach(addSmiley);
        }

        function loadSmileys() {
            if (root.dataset.loaded || root.dataset.loading) return;
            root.dataset.loading = '1';
            fetch((window.JMOD_HOME_URL || '') + '/help/smilies_json.php', {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'Accept': 'application/json'}
            }).then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            }).then(function(data) {
                panel.innerHTML = '';
                addHint();
                if (data && Array.isArray(data.smileys)) {
                    data.smileys.forEach(addSmiley);
                }
                if (panel.children.length <= 1) fallback();
                root.dataset.loaded = '1';
            }).catch(function() {
                fallback();
            }).finally(function() {
                root.dataset.loading = '';
            });
        }

        toggle.addEventListener('click', loadSmileys);
        document.addEventListener('click', function(e) {
            if (!root.contains(e.target)) root.classList.remove('open');
        });
        panel.addEventListener('mousedown', function(e) { e.preventDefault(); });
        panel.addEventListener('touchstart', function(e) { e.stopPropagation(); }, {passive:true});
        return root;
    }

    function build(textarea) {
        if (!textarea || textarea.dataset.jmodEditorReady) return;
        textarea.dataset.jmodEditorReady = '1';
        injectCss();

        var wrap = document.createElement('div');
        wrap.className = 'jmod-editor';

        var toolbar = document.createElement('div');
        toolbar.className = 'jmod-editor-toolbar';
        toolbar.setAttribute('role', 'toolbar');
        toolbar.setAttribute('aria-label', 'Thanh công cụ định dạng');

        var body = document.createElement('div');
        body.className = 'jmod-editor-body';
        body.contentEditable = 'true';
        body.setAttribute('role', 'textbox');
        body.setAttribute('aria-multiline', 'true');
        body.setAttribute('data-placeholder', textarea.getAttribute('placeholder') || 'Nhập nội dung...');

        var source = document.createElement('textarea');
        source.className = 'jmod-editor-source';
        source.setAttribute('aria-label', 'BBCode');
        source.value = textarea.value || '';

        var status = document.createElement('div');
        status.className = 'jmod-editor-status';
        var count = document.createElement('span');
        var help = document.createElement('span');
        help.className = 'jmod-editor-help';
        help.textContent = 'Chèn mã: gõ :) :( :D hoặc :ten_smiley:';
        var mode = document.createElement('span');
        mode.textContent = 'Trình soạn thảo trực quan';
        status.appendChild(count);
        status.appendChild(help);
        status.appendChild(mode);

        var editor = {wrap:wrap, toolbar:toolbar, body:body, source:source, textarea:textarea, count:count, mode:mode, dirty:false, savedRange:null};

        body.innerHTML = bbToHtml(textarea.value || '');

        // Nhóm 1: hoàn tác / làm lại
        toolbar.appendChild(button('↶', 'Hoàn tác', function() { exec(editor, 'undo'); }));
        toolbar.appendChild(button('↷', 'Làm lại', function() { exec(editor, 'redo'); }));
        toolbar.appendChild(separator());

        // Nhóm 2: định dạng chữ
        toolbar.appendChild(button('B', 'In đậm [b]', function() { exec(editor, 'bold'); }));
        toolbar.appendChild(button('I', 'In nghiêng [i]', function() { exec(editor, 'italic'); }));
        toolbar.appendChild(button('U', 'Gạch chân [u]', function() { exec(editor, 'underline'); }));
        toolbar.appendChild(button('S', 'Gạch ngang [s]', function() { exec(editor, 'strikeThrough'); }));
        toolbar.appendChild(button('A-', 'Chữ nhỏ [small]', function() { exec(editor, 'fontSize', '2'); }));
        toolbar.appendChild(button('A+', 'Chữ lớn [big]', function() { exec(editor, 'fontSize', '6'); }));
        toolbar.appendChild(separator());

        // Nhóm 3: màu sắc
        var color = document.createElement('input');
        color.type = 'color';
        color.className = 'jmod-color';
        color.title = 'Màu chữ [color]';
        color.value = '#2563eb';
        color.addEventListener('input', function() { execColor(editor, 'foreColor', color.value); });
        toolbar.appendChild(color);

        var bg = document.createElement('input');
        bg.type = 'color';
        bg.className = 'jmod-color';
        bg.title = 'Màu nền [bg]';
        bg.value = '#fff59d';
        bg.addEventListener('input', function() { execColor(editor, 'hiliteColor', bg.value); });
        toolbar.appendChild(bg);
        toolbar.appendChild(separator());

        // Nhóm 4: tiêu đề + căn lề
        toolbar.appendChild(createHeadingControl(editor));

        var alignGroup = document.createElement('span');
        alignGroup.className = 'jmod-align-group';
        alignGroup.setAttribute('aria-label', 'Căn lề');
        [
            ['⇤', 'Căn trái [left]', 'left'],
            ['↔', 'Căn giữa [center]', 'center'],
            ['⇥', 'Căn phải [right]', 'right'],
            ['☷', 'Căn đều [justify]', 'justify']
        ].forEach(function(item) {
            var alignButton = button(item[0], item[1], function() { applyAlign(editor, item[2]); });
            alignButton.addEventListener('mousedown', function(e) { e.preventDefault(); });
            alignButton.addEventListener('touchstart', function() { saveSelection(editor); }, {passive:true});
            alignGroup.appendChild(alignButton);
        });
        toolbar.appendChild(alignGroup);
        toolbar.appendChild(separator());

        // Nhóm 5: danh sách
        toolbar.appendChild(button('• Danh sách', 'Danh sách dấu đầu dòng [list]', function() { exec(editor, 'insertUnorderedList'); }));
        toolbar.appendChild(button('1. Danh sách', 'Danh sách có thứ tự [list=1]', function() { exec(editor, 'insertOrderedList'); }));
        toolbar.appendChild(separator());

        // Nhóm 6: chèn nội dung
        toolbar.appendChild(button('Liên kết', 'Chèn/sửa liên kết [url]', function() { linkAction(editor); }));
        toolbar.appendChild(button('Ảnh', 'Chèn ảnh bằng URL [img]', function() {
            var url = promptUrl('URL ảnh:', 'https://', false);
            if (url) insertHtml(editor, '<img src="' + esc(url) + '" alt="">');
        }));
        toolbar.appendChild(button('Quote', 'Trích dẫn [quote]', function() { insertHtml(editor, '<blockquote><br></blockquote>'); }));
        toolbar.appendChild(button('Spoiler', 'Spoiler [spoiler]', function() {
            insertHtml(editor, '<div class="jmod-editor-spoiler"><button type="button" contenteditable="false">Spoiler</button><div><br></div></div>');
        }));
        toolbar.appendChild(createCodeControl(editor));
        toolbar.appendChild(button('HR', 'Đường kẻ [hr]', function() { insertHtml(editor, '<hr>'); }));
        toolbar.appendChild(createSmileyPicker(editor));
        toolbar.appendChild(separator());

        // Nhóm 7: tiện ích
        toolbar.appendChild(button('Xóa định dạng', 'Xóa định dạng', function() { exec(editor, 'removeFormat'); }));

        var sourceBtn = button('BBCode', 'Chuyển đổi chế độ xem trực quan / BBCode', function() {
            if (wrap.classList.contains('source-mode')) {
                body.innerHTML = bbToHtml(source.value);
                wrap.classList.remove('source-mode');
                mode.textContent = 'Trình soạn thảo trực quan';
                sync(editor);
            } else {
                sync(editor);
                wrap.classList.add('source-mode');
                mode.textContent = 'Chế độ BBCode';
                source.focus();
            }
        });
        toolbar.appendChild(sourceBtn);

        body.addEventListener('mouseup', function() { saveSelection(editor); });
        body.addEventListener('keyup', function() { saveSelection(editor); });
        body.addEventListener('input', function() { saveSelection(editor); editor.dirty = true; sync(editor); });
        body.addEventListener('blur', function() { saveSelection(editor); sync(editor); });
        toolbar.addEventListener('mousedown', function() { saveSelection(editor); });
        toolbar.addEventListener('touchstart', function() { saveSelection(editor); }, {passive:true});
        source.addEventListener('input', function() {
            textarea.value = source.value;
            var text = source.value.replace(/\[\/?.+?\]/g, '');
            count.textContent = text.trim().length + ' ký tự';
            editor.dirty = true;
        });
        body.addEventListener('paste', function(e) {
            if (!e.clipboardData) return;
            var html = e.clipboardData.getData('text/html');
            var text = e.clipboardData.getData('text/plain');
            if (html) {
                e.preventDefault();
                var holder = document.createElement('div');
                holder.innerHTML = html;
                Array.prototype.forEach.call(holder.querySelectorAll('script,style,iframe,object,embed,form'), function(n) { n.remove(); });
                Array.prototype.forEach.call(holder.querySelectorAll('*'), function(n) {
                    Array.prototype.forEach.call(n.attributes, function(a) {
                        if (/^on/i.test(a.name)) n.removeAttribute(a.name);
                    });
                });
                insertHtml(editor, holder.innerHTML);
            } else if (text) {
                e.preventDefault();
                insertHtml(editor, esc(text).replace(/\r?\n/g, '<br>'));
            }
        });

        wrap.appendChild(toolbar);
        wrap.appendChild(body);
        wrap.appendChild(source);
        wrap.appendChild(status);
        textarea.parentNode.insertBefore(wrap, textarea);
        textarea.style.display = 'none';

        sync(editor);

        if (textarea.form) {
            textarea.form.addEventListener('submit', function() {
                if (wrap.classList.contains('source-mode')) textarea.value = source.value;
                else sync(editor);
            });
        }
    }

    function init(field) {
        var safeField = String(field || '').replace(/"/g, '\\"');
        var nodes = document.querySelectorAll('textarea[name="' + safeField + '"]');
        Array.prototype.forEach.call(nodes, build);
    }

    window.JmodEditor = {
        init: init,
        build: build,
        htmlToBb: htmlToBb,
        bbToHtml: bbToHtml
    };
})();
