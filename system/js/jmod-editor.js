(function () {
    'use strict';

    if (window.JmodEditor) return;

    var CSS = [
        '.jmod-editor{border:1px solid #cfd4da;border-radius:8px;background:#fff;overflow:hidden;max-width:100%;}',
        '.jmod-editor-toolbar{display:flex;flex-wrap:wrap;gap:4px;padding:6px;border-bottom:1px solid #e5e7eb;background:#f7f8fa;align-items:center;}',
        '.jmod-editor-toolbar button,.jmod-editor-toolbar select{border:1px solid #d5d9df;background:#fff;color:#20242a;border-radius:5px;min-height:32px;padding:4px 8px;font:inherit;cursor:pointer;}',
        '.jmod-editor-toolbar button:hover,.jmod-editor-toolbar select:hover{background:#eef1f4;}',
        '.jmod-editor-toolbar button.is-active{background:#dfe7ef;}',
        '.jmod-editor-toolbar .sep{width:1px;height:24px;background:#d9dde3;margin:0 2px;}',
        '.jmod-editor-body{min-height:180px;padding:12px;outline:0;line-height:1.6;overflow-wrap:anywhere;word-break:break-word;}',
        '.jmod-editor-body:empty:before{content:attr(data-placeholder);color:#8b949e;pointer-events:none;}',
        '.jmod-editor-body img{max-width:100%;height:auto;}',
        '.jmod-editor-body blockquote,.jmod-editor-quote{margin:8px 0;padding:8px 12px;border-left:4px solid #adb5bd;background:#f6f7f8;}',
        '.jmod-editor-spoiler{border:1px solid #d9dde3;border-radius:5px;margin:8px 0;overflow:hidden;}',
        '.jmod-editor-spoiler>button{width:100%;text-align:left;border:0;background:#f1f3f5;padding:7px 10px;cursor:pointer;}',
        '.jmod-editor-spoiler>div{padding:8px 10px;}',
        '.jmod-editor-status{display:flex;justify-content:space-between;gap:8px;padding:4px 8px;color:#6b7280;font-size:12px;border-top:1px solid #eef0f2;}',
        '.jmod-editor-source{display:none;width:100%;min-height:180px;border:0;resize:vertical;padding:12px;font:14px/1.6 monospace;outline:0;box-sizing:border-box;}',
        '.jmod-editor.source-mode .jmod-editor-body{display:none}.jmod-editor.source-mode .jmod-editor-source{display:block;}',
        '@media(max-width:600px){.jmod-editor-toolbar button,.jmod-editor-toolbar select{min-height:36px}.jmod-editor-body{min-height:220px;padding:10px;}}'
    ].join('');

    function injectCss() {
        if (document.getElementById('jmod-editor-css')) return;
        var style = document.createElement('style');
        style.id = 'jmod-editor-css';
        style.textContent = CSS;
        document.head.appendChild(style);
    }

    function esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function bbToHtml(value) {
        var s = String(value || '');
        var stash = [];
        s = s.replace(/\[img\](https?:\/\/[^\s\[]+)\[\/img\]/gi, function(_, u) {
            var k = '__JMOD_IMAGE_' + stash.length + '__';
            stash.push('<img src="' + esc(u) + '" alt="">');
            return k;
        });
        s = esc(s);
        s = s.replace(/\[b\]([\s\S]*?)\[\/b\]/gi, '<strong>$1</strong>');
        s = s.replace(/\[i\]([\s\S]*?)\[\/i\]/gi, '<em>$1</em>');
        s = s.replace(/\[u\]([\s\S]*?)\[\/u\]/gi, '<u>$1</u>');
        s = s.replace(/\[s\]([\s\S]*?)\[\/s\]/gi, '<s>$1</s>');
        s = s.replace(/\[red\]([\s\S]*?)\[\/red\]/gi, '<span style="color:red">$1</span>');
        s = s.replace(/\[green\]([\s\S]*?)\[\/green\]/gi, '<span style="color:green">$1</span>');
        s = s.replace(/\[blue\]([\s\S]*?)\[\/blue\]/gi, '<span style="color:blue">$1</span>');
        s = s.replace(/\[color=(#[0-9a-f]{3,6}|[a-z-]+)\]([\s\S]*?)\[\/color\]/gi, '<span style="color:$1">$2</span>');
        s = s.replace(/\[bg=(#[0-9a-f]{3,6}|[a-z-]+)\]([\s\S]*?)\[\/bg\]/gi, '<span style="background-color:$1">$2</span>');
        s = s.replace(/\[(quote|c)\]([\s\S]*?)\[\/(quote|c)\]/gi, '<blockquote>$2</blockquote>');
        s = s.replace(/\[center\]([\s\S]*?)\[\/center\]/gi, '<div style="text-align:center">$1</div>');
        s = s.replace(/\[left\]([\s\S]*?)\[\/left\]/gi, '<div style="text-align:left">$1</div>');
        s = s.replace(/\[right\]([\s\S]*?)\[\/right\]/gi, '<div style="text-align:right">$1</div>');
        s = s.replace(/\[justify\]([\s\S]*?)\[\/justify\]/gi, '<div style="text-align:justify">$1</div>');
        s = s.replace(/\[small\]([\s\S]*?)\[\/small\]/gi, '<small>$1</small>');
        s = s.replace(/\[big\]([\s\S]*?)\[\/big\]/gi, '<big>$1</big>');
        s = s.replace(/\[sup\]([\s\S]*?)\[\/sup\]/gi, '<sup>$1</sup>');
        s = s.replace(/\[sub\]([\s\S]*?)\[\/sub\]/gi, '<sub>$1</sub>');
        s = s.replace(/\[url=(https?:\/\/[^\]]+)\]([\s\S]*?)\[\/url\]/gi, '<a href="$1" rel="noopener noreferrer">$2</a>');
        s = s.replace(/\[url\](https?:\/\/[^\[]+)\[\/url\]/gi, '<a href="$1" rel="noopener noreferrer">$1</a>');
        s = s.replace(/\[hr\]/gi, '<hr>');
        s = s.replace(/\[br\]/gi, '<br>');
        s = s.replace(/\[list\]([\s\S]*?)\[\/list\]/gi, '<ul>$1</ul>');
        s = s.replace(/\[\*\]([\s\S]*?)\[\/\*\]/gi, '<li>$1</li>');
        s = s.replace(/\r?\n/g, '<br>');
        s = s.replace(/__JMOD_IMAGE_(\d+)__/g, function(_, i) { return stash[Number(i)] || ''; });
        return s;
    }

    function htmlToBb(root) {
        function walk(node) {
            if (node.nodeType === Node.TEXT_NODE) return node.nodeValue.replace(/\u00a0/g, ' ');
            if (node.nodeType !== Node.ELEMENT_NODE) return '';
            var tag = node.tagName.toLowerCase();
            var inner = '';
            for (var i = 0; i < node.childNodes.length; i++) inner += walk(node.childNodes[i]);

            if (tag === 'strong' || tag === 'b') return '[b]' + inner + '[/b]';
            if (tag === 'em' || tag === 'i') return '[i]' + inner + '[/i]';
            if (tag === 'u') return '[u]' + inner + '[/u]';
            if (tag === 's' || tag === 'strike') return '[s]' + inner + '[/s]';
            if (tag === 'a') {
                var href = node.getAttribute('href') || '';
                return href ? '[url=' + href + ']' + inner + '[/url]' : inner;
            }
            if (tag === 'img') {
                var src = node.getAttribute('src') || '';
                return src ? '[img]' + src + '[/img]' : '';
            }
            if (tag === 'blockquote') return '[quote]' + inner + '[/quote]';
            if (tag === 'hr') return '[hr]';
            if (tag === 'br') return '\n';
            if (tag === 'li') return '[*]' + inner + '[/ *]'.replace(' ', '');
            if (tag === 'ul' || tag === 'ol') return '[list]' + inner + '[/list]\n';
            if (tag === 'div' || tag === 'p') {
                var align = node.style.textAlign;
                if (align === 'justify') return '[justify]' + inner + '[/justify]\n';
                if (align === 'center') return '[center]' + inner + '[/center]\n';
                if (align === 'right') return '[right]' + inner + '[/right]\n';
                return inner + '\n';
            }
            if (tag === 'span') {
                var color = node.style.color;
                var bg = node.style.backgroundColor;
                if (color) return '[color=' + color + ']' + inner + '[/color]';
                if (bg) return '[bg=' + bg + ']' + inner + '[/bg]';
            }
            if (tag === 'small') return '[small]' + inner + '[/small]';
            if (tag === 'big') return '[big]' + inner + '[/big]';
            if (tag === 'sup') return '[sup]' + inner + '[/sup]';
            if (tag === 'sub') return '[sub]' + inner + '[/sub]';
            if (tag === 'pre') return '[code=php]' + inner + '[/code]';
            return inner;
        }
        return walk(root).replace(/\n{3,}/g, '\n\n').replace(/^\n+|\n+$/g, '');
    }

    function exec(editor, command, value) {
        editor.body.focus();
        try { document.execCommand(command, false, value || null); } catch (e) {}
        sync(editor);
    }

    function sync(editor) {
        editor.source.value = htmlToBb(editor.body);
        var text = editor.body.innerText || '';
        editor.count.textContent = text.trim().length + ' ký tự';
    }

    function insertHtml(editor, html) {
        editor.body.focus();
        document.execCommand('insertHTML', false, html);
        sync(editor);
    }

    function button(label, title, fn) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = label;
        b.title = title;
        b.addEventListener('click', fn);
        return b;
    }

    function build(textarea) {
        if (!textarea || textarea.dataset.jmodEditorReady) return;
        textarea.dataset.jmodEditorReady = '1';
        injectCss();

        var wrap = document.createElement('div');
        wrap.className = 'jmod-editor';

        var toolbar = document.createElement('div');
        toolbar.className = 'jmod-editor-toolbar';

        var body = document.createElement('div');
        body.className = 'jmod-editor-body';
        body.contentEditable = 'true';
        body.setAttribute('data-placeholder', 'Nhập nội dung...');
        body.innerHTML = bbToHtml(textarea.value);

        var source = document.createElement('textarea');
        source.className = 'jmod-editor-source';
        source.value = textarea.value;
        source.setAttribute('aria-label', 'BBCode');

        var status = document.createElement('div');
        status.className = 'jmod-editor-status';
        var count = document.createElement('span');
        status.appendChild(count);
        var mode = document.createElement('span');
        mode.textContent = 'Trình soạn thảo trực quan';
        status.appendChild(mode);

        toolbar.appendChild(button('B', 'In đậm', function(){exec(editor,'bold');}));
        toolbar.appendChild(button('I', 'In nghiêng', function(){exec(editor,'italic');}));
        toolbar.appendChild(button('U', 'Gạch chân', function(){exec(editor,'underline');}));
        toolbar.appendChild(button('S', 'Gạch ngang', function(){exec(editor,'strikeThrough');}));
        toolbar.appendChild(document.createTextNode(' '));

        var align = document.createElement('select');
        align.title = 'Căn lề';
        [['left','Căn trái'],['center','Căn giữa'],['right','Căn phải'],['justify','Căn đều 2 bên']].forEach(function(x){
            var o=document.createElement('option'); o.value=x[0]; o.textContent=x[1]; align.appendChild(o);
        });
        align.addEventListener('change', function(){exec(editor,'justify' + align.value.charAt(0).toUpperCase()+align.value.slice(1));});
        toolbar.appendChild(align);

        toolbar.appendChild(button('• Danh sách', 'Danh sách', function(){exec(editor,'insertUnorderedList');}));
        toolbar.appendChild(button('1. Danh sách', 'Danh sách đánh số', function(){exec(editor,'insertOrderedList');}));
        toolbar.appendChild(button('Liên kết', 'Chèn liên kết', function(){
            var url=window.prompt('URL:','https://');
            if(url) exec(editor,'createLink',url);
        }));
        toolbar.appendChild(button('Ảnh', 'Chèn ảnh', function(){
            var url=window.prompt('URL ảnh:','https://');
            if(url) insertHtml(editor,'<img src="'+esc(url)+'" alt="">');
        }));
        toolbar.appendChild(button('Quote', 'Trích dẫn', function(){insertHtml(editor,'<blockquote></blockquote>');}));
        toolbar.appendChild(button('Spoiler', 'Spoiler', function(){
            insertHtml(editor,'<div class="jmod-editor-spoiler" data-bbcode="spoiler"><button type="button" contenteditable="false">Spoiler</button><div>Nhập nội dung ẩn...</div></div>');
        }));
        toolbar.appendChild(button('HR', 'Đường kẻ', function(){insertHtml(editor,'<hr>');}));
        toolbar.appendChild(button('Xóa định dạng', 'Xóa định dạng', function(){exec(editor,'removeFormat');}));

        var sourceBtn=button('BBCode', 'Chuyển sang BBCode', function(){
            if(wrap.classList.contains('source-mode')){
                body.innerHTML=bbToHtml(source.value);
                wrap.classList.remove('source-mode');
                mode.textContent='Trình soạn thảo trực quan';
                sync(editor);
            }else{
                sync(editor);
                wrap.classList.add('source-mode');
                mode.textContent='Chế độ BBCode';
            }
        });
        toolbar.appendChild(sourceBtn);

        var editor = {wrap:wrap, toolbar:toolbar, body:body, source:source, count:count};
        body.addEventListener('input', function(){sync(editor);});
        body.addEventListener('blur', function(){sync(editor);});
        source.addEventListener('input', function(){textarea.value=source.value;});

        wrap.appendChild(toolbar);
        wrap.appendChild(body);
        wrap.appendChild(source);
        wrap.appendChild(status);
        textarea.parentNode.insertBefore(wrap, textarea);
        textarea.style.display='none';

        sync(editor);
        textarea.form && textarea.form.addEventListener('submit', function(){ sync(editor); textarea.value=source.value; });
    }

    function init(field) {
        var nodes = document.querySelectorAll('textarea[name="' + CSS.escape(field) + '"]');
        Array.prototype.forEach.call(nodes, build);
    }

    window.JmodEditor = {init:init, build:build, htmlToBb:htmlToBb, bbToHtml:bbToHtml};
})();