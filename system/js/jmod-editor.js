/* JMod WYSIWYG Editor
 * Lightweight, dependency-free BBCode editor for JohnCMS/JMod.
 * Keeps the original textarea as the form source of truth.
 */
(function () {
    'use strict';

    if (window.JmodEditor) return;

    var CSS = [
        '.jmod-editor{border:1px solid #cbd5e1;border-radius:10px;background:#fff;overflow:hidden;max-width:100%;box-shadow:0 1px 2px rgba(15,23,42,.05)}',
        '.jmod-editor-toolbar{display:flex;flex-wrap:wrap;gap:4px;padding:7px;border-bottom:1px solid #e2e8f0;background:#f8fafc;align-items:center}',
        '.jmod-editor-toolbar button,.jmod-editor-toolbar select{border:1px solid #cbd5e1;background:#fff;color:#1e293b;border-radius:6px;min-height:32px;padding:4px 9px;font:inherit;cursor:pointer}',
        '.jmod-editor-toolbar button:hover,.jmod-editor-toolbar select:hover{background:#f1f5f9;border-color:#94a3b8}',
        '.jmod-editor-toolbar button:focus,.jmod-editor-toolbar select:focus{outline:2px solid rgba(37,99,235,.25);outline-offset:1px}',
        '.jmod-editor-toolbar .sep{width:1px;height:24px;background:#cbd5e1;margin:0 2px}',
        '.jmod-editor-body{min-height:190px;padding:12px;outline:0;line-height:1.65;overflow-wrap:anywhere;word-break:break-word}',
        '.jmod-editor-body:empty:before{content:attr(data-placeholder);color:#94a3b8;pointer-events:none}',
        '.jmod-editor-body img{max-width:100%;height:auto;vertical-align:middle}',
        '.jmod-editor-body blockquote{margin:8px 0;padding:8px 12px;border-left:4px solid #94a3b8;background:#f8fafc}',
        '.jmod-editor-body pre{white-space:pre-wrap;overflow:auto;padding:10px;background:#f1f5f9;border-radius:6px}',
        '.jmod-editor-spoiler{border:1px solid #cbd5e1;border-radius:6px;margin:8px 0;overflow:hidden}',
        '.jmod-editor-spoiler>button{width:100%;text-align:left;border:0;background:#f1f5f9;padding:8px 10px;cursor:pointer}',
        '.jmod-editor-spoiler>div{padding:8px 10px}',
        '.jmod-editor-status{display:flex;justify-content:space-between;gap:8px;padding:5px 9px;color:#64748b;font-size:12px;border-top:1px solid #eef2f7}',
        '.jmod-editor-source{display:none;width:100%;min-height:190px;border:0;resize:vertical;padding:12px;font:14px/1.6 ui-monospace,SFMono-Regular,Consolas,monospace;outline:0;box-sizing:border-box}',
        '.jmod-editor.source-mode .jmod-editor-body{display:none}.jmod-editor.source-mode .jmod-editor-source{display:block}',
        '.jmod-editor .jmod-color{width:36px;padding:4px}.jmod-editor .jmod-size{min-width:90px}',
        '.jmod-emoji{position:relative;display:inline-flex}.jmod-emoji-toggle{font-size:19px;line-height:1}.jmod-emoji-panel{display:none;position:absolute;z-index:1000;top:calc(100% + 5px);left:0;width:min(330px,calc(100vw - 24px));max-height:260px;overflow:auto;padding:8px;grid-template-columns:repeat(8,1fr);gap:3px;background:#fff;border:1px solid #cbd5e1;border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.16)}.jmod-emoji.open .jmod-emoji-panel{display:grid}.jmod-emoji-item{border:0!important;background:transparent!important;border-radius:7px!important;min-height:34px!important;padding:2px!important;font-size:21px!important;cursor:pointer}.jmod-emoji-item:hover{background:#f1f5f9!important}.jmod-emoji-item:focus{outline:2px solid rgba(37,99,235,.25)!important;outline-offset:-1px}@media(max-width:600px){.jmod-emoji-panel{position:fixed;left:10px;right:10px;bottom:10px;top:auto;width:auto;max-height:45vh}}',
        '@media(max-width:600px){.jmod-editor-toolbar button,.jmod-editor-toolbar select{min-height:36px}.jmod-editor-body{min-height:220px;padding:10px}}'
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
        s = s.replace(/\[red\]([\s\S]*?)\[\/red\]/gi, '<span style="color:red">$1</span>');
        s = s.replace(/\[green\]([\s\S]*?)\[\/green\]/gi, '<span style="color:green">$1</span>');
        s = s.replace(/\[blue\]([\s\S]*?)\[\/blue\]/gi, '<span style="color:blue">$1</span>');
        s = s.replace(/\[color=(#[0-9a-f]{3,8}|[a-z-]+)\]([\s\S]*?)\[\/color\]/gi, '<span style="color:$1">$2</span>');
        s = s.replace(/\[bg=(#[0-9a-f]{3,8}|[a-z-]+)\]([\s\S]*?)\[\/bg\]/gi, '<span style="background-color:$1">$2</span>');
        s = s.replace(/\[(quote|c)\]([\s\S]*?)\[\/(quote|c)\]/gi, '<blockquote>$2</blockquote>');
        s = s.replace(/\[center\]([\s\S]*?)\[\/center\]/gi, '<div style="text-align:center">$1</div>');
        s = s.replace(/\[left\]([\s\S]*?)\[\/left\]/gi, '<div style="text-align:left">$1</div>');
        s = s.replace(/\[right\]([\s\S]*?)\[\/right\]/gi, '<div style="text-align:right">$1</div>');
        s = s.replace(/\[justify\]([\s\S]*?)\[\/justify\]/gi, '<div style="text-align:justify">$1</div>');
        s = s.replace(/\[small\]([\s\S]*?)\[\/small\]/gi, '<small>$1</small>');
        s = s.replace(/\[big\]([\s\S]*?)\[\/big\]/gi, '<big>$1</big>');
        s = s.replace(/\[sup\]([\s\S]*?)\[\/sup\]/gi, '<sup>$1</sup>');
        s = s.replace(/\[sub\]([\s\S]*?)\[\/sub\]/gi, '<sub>$1</sub>');
        s = s.replace(/\[code(?:=(php|css|html|js|sql|xml))?\]([\s\S]*?)\[\/code\]/gi, '<pre><code>$2</code></pre>');
        s = s.replace(/\[url=(https?:\/\/[^\]]+)\]([\s\S]*?)\[\/url\]/gi, '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>');
        s = s.replace(/\[url\](https?:\/\/[^\[]+)\[\/url\]/gi, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        s = s.replace(/\[hr\]/gi, '<hr>');
        s = s.replace(/\[br\]/gi, '<br>');
        s = s.replace(/\[list\]([\s\S]*?)\[\/list\]/gi, '<ul>$1</ul>');
        s = s.replace(/\[\*\]([\s\S]*?)\[\/\*\]/gi, '<li>$1</li>');
        s = s.replace(/\[spoiler(?:=([^\]]*))?\]([\s\S]*?)\[\/spoiler\]/gi, function(_, title, body) {
            return '<div class="jmod-editor-spoiler"><button type="button" contenteditable="false">' + esc(title || 'Spoiler') + '</button><div>' + body + '</div></div>';
        });
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
            if (tag === 'ul' || tag === 'ol') return '[list]' + inner + '[/list]\n';
            if (tag === 'pre') return '[code=php]' + inner + '[/code]\n';
            if (tag === 'div' || tag === 'p') {
                var align = node.style.textAlign || node.getAttribute('align') || '';
                var prefix = align === 'justify' ? '[justify]' : align === 'center' ? '[center]' : align === 'right' ? '[right]' : align === 'left' ? '[left]' : '';
                var suffix = align === 'justify' ? '[/justify]' : align === 'center' ? '[/center]' : align === 'right' ? '[/right]' : align === 'left' ? '[/left]' : '';
                return prefix + inner + suffix + '\n';
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

    function sync(editor) {
        editor.source.value = htmlToBb(editor.body);
        editor.textarea.value = editor.source.value;
        var text = editor.body.innerText || editor.body.textContent || '';
        editor.count.textContent = text.trim().length + ' ký tự';
        editor.dirty = false;
    }

    function insertHtml(editor, html) {
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

    function createEmojiPicker(editor) {
        var faces = [
            '😀','😃','😄','😁','😆','😅','😂','🤣',
            '😊','😇','🙂','🙃','😉','😌','😍','🥰',
            '😘','😗','😙','😚','😋','😛','😝','😜',
            '🤪','🤨','🧐','🤓','😎','🤩','🥳','😏',
            '😒','😞','😔','😟','😕','🙁','☹️','😣',
            '😖','😫','😩','🥺','😢','😭','😤','😠',
            '😡','🤬','🤗','🤔','🤭','🤫','🤥','😶',
            '😐','😑','😬','🙄','😯','😦','😧','😮',
            '😲','🥱','😴','🤤','😪','😵','🤐','🥴',
            '🤢','🤮','🤧','😷','🤒','🤕','🤑','🤠',
            '😈','👿','👹','👺','🤡','💩','👻','💀',
            '☠️','👽','🤖','🎃','😺','😸','😹','😻',
            '😼','😽','🙀','😿','😾','🙈','🙉','🙊'
        ];
        var root = document.createElement('span');
        root.className = 'jmod-emoji';
        var toggle = button('😊', 'Chèn biểu tượng cảm xúc', function(e) {
            e.stopPropagation();
            saveSelection(editor);
            root.classList.toggle('open');
        }, 'jmod-emoji-toggle');
        var panel = document.createElement('span');
        panel.className = 'jmod-emoji-panel';
        panel.setAttribute('role', 'menu');
        faces.forEach(function(face) {
            var item = button(face, 'Chèn ' + face, function(e) {
                e.stopPropagation();
                insertText(editor, face);
                root.classList.remove('open');
            }, 'jmod-emoji-item');
            item.setAttribute('role', 'menuitem');
            panel.appendChild(item);
        });
        root.appendChild(toggle);
        root.appendChild(panel);
        document.addEventListener('click', function(e) {
            if (!root.contains(e.target)) root.classList.remove('open');
        });
        panel.addEventListener('mousedown', function(e) { e.preventDefault(); });
        panel.addEventListener('touchstart', function(e) { e.stopPropagation(); }, {passive:true});
        return root;
    }

    function promptUrl(title, initial, allowMail) {
        var value = window.prompt(title, initial || 'https://');
        if (value == null) return '';
        return safeUrl(value, !!allowMail);
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
        var mode = document.createElement('span');
        mode.textContent = 'Trình soạn thảo trực quan';
        status.appendChild(count);
        status.appendChild(mode);

        var editor = {wrap:wrap, toolbar:toolbar, body:body, source:source, textarea:textarea, count:count, mode:mode, dirty:false, savedRange:null};

        body.innerHTML = bbToHtml(textarea.value || '');

        toolbar.appendChild(button('↶', 'Hoàn tác', function(){exec(editor,'undo');}));
        toolbar.appendChild(button('↷', 'Làm lại', function(){exec(editor,'redo');}));
        toolbar.appendChild(separator());
        toolbar.appendChild(button('B', 'In đậm', function(){exec(editor,'bold');}));
        toolbar.appendChild(button('I', 'In nghiêng', function(){exec(editor,'italic');}));
        toolbar.appendChild(button('U', 'Gạch chân', function(){exec(editor,'underline');}));
        toolbar.appendChild(button('S', 'Gạch ngang', function(){exec(editor,'strikeThrough');}));
        toolbar.appendChild(separator());

        var size = document.createElement('select');
        size.className = 'jmod-size';
        size.title = 'Cỡ chữ';
        [['','Cỡ chữ'],['1','Nhỏ'],['3','Bình thường'],['5','Lớn'],['7','Rất lớn']].forEach(function(x){
            var o=document.createElement('option'); o.value=x[0]; o.textContent=x[1]; size.appendChild(o);
        });
        size.addEventListener('change', function(){ if (size.value) exec(editor,'fontSize',size.value); size.value=''; });
        toolbar.appendChild(size);

        var color = document.createElement('input');
        color.type = 'color';
        color.className = 'jmod-color';
        color.title = 'Màu chữ';
        color.value = '#2563eb';
        color.addEventListener('input', function(){exec(editor,'foreColor',color.value);});
        toolbar.appendChild(color);

        var bg = document.createElement('input');
        bg.type = 'color';
        bg.className = 'jmod-color';
        bg.title = 'Màu nền';
        bg.value = '#fff59d';
        bg.addEventListener('input', function(){exec(editor,'hiliteColor',bg.value);});
        toolbar.appendChild(bg);
        toolbar.appendChild(separator());
        toolbar.appendChild(createEmojiPicker(editor));
        toolbar.appendChild(separator());

        var alignGroup = document.createElement('span');
        alignGroup.className = 'jmod-align-group';
        alignGroup.setAttribute('aria-label', 'Căn lề');
        [
            ['⇤', 'Căn trái', 'left'],
            ['↔', 'Căn giữa', 'center'],
            ['⇥', 'Căn phải', 'right'],
            ['☷', 'Căn đều', 'justify']
        ].forEach(function(item) {
            var alignButton = button(item[0], item[1], function() {
                restoreSelection(editor);
                var selection = window.getSelection();
                if (!selection || !selection.rangeCount) return;
                var range = selection.getRangeAt(0);
                if (!editor.body.contains(range.commonAncestorContainer)) return;

                var node = range.startContainer;
                var block = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
                while (block && block !== editor.body && !/^(DIV|P|LI|BLOCKQUOTE)$/.test(block.tagName)) block = block.parentElement;

                if (block && block !== editor.body && editor.body.contains(block)) {
                    block.style.textAlign = item[2];
                } else if (!range.collapsed) {
                    var wrapper = document.createElement('div');
                    wrapper.style.textAlign = item[2];
                    wrapper.appendChild(range.extractContents());
                    range.insertNode(wrapper);
                    range.selectNodeContents(wrapper);
                    selection.removeAllRanges();
                    selection.addRange(range);
                } else {
                    var wrapper = document.createElement('div');
                    wrapper.style.textAlign = item[2];
                    wrapper.appendChild(document.createElement('br'));
                    range.insertNode(wrapper);
                    range.selectNodeContents(wrapper);
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
                saveSelection(editor);
                sync(editor);
            });
            alignButton.addEventListener('mousedown', function(e) { e.preventDefault(); });
            alignButton.addEventListener('touchstart', function() { saveSelection(editor); }, {passive:true});
            alignGroup.appendChild(alignButton);
        });
        toolbar.appendChild(alignGroup);
        toolbar.appendChild(button('• Danh sách', 'Danh sách', function(){exec(editor,'insertUnorderedList');}));
        toolbar.appendChild(button('1. Danh sách', 'Danh sách đánh số', function(){exec(editor,'insertOrderedList');}));
        toolbar.appendChild(separator());

        toolbar.appendChild(button('Liên kết', 'Chèn liên kết', function(){
            var url=promptUrl('URL liên kết:', 'https://', true);
            if(url) exec(editor,'createLink',url);
        }));
        toolbar.appendChild(button('Ảnh', 'Chèn ảnh bằng URL', function(){
            var url=promptUrl('URL ảnh:', 'https://', false);
            if(url) insertHtml(editor,'<img src="' + esc(url) + '" alt="">');
        }));
        toolbar.appendChild(button('Quote', 'Trích dẫn', function(){insertHtml(editor,'<blockquote><br></blockquote>');}));
        toolbar.appendChild(button('Spoiler', 'Spoiler', function(){
            insertHtml(editor,'<div class="jmod-editor-spoiler"><button type="button" contenteditable="false">Spoiler</button><div><br></div></div>');
        }));
        toolbar.appendChild(button('Code', 'Khối mã', function(){insertHtml(editor,'<pre><code><br></code></pre>');}));
        toolbar.appendChild(button('HR', 'Đường kẻ', function(){insertHtml(editor,'<hr>');}));
        toolbar.appendChild(button('Xóa định dạng', 'Xóa định dạng', function(){exec(editor,'removeFormat');}));
        toolbar.appendChild(separator());

        var sourceBtn=button('BBCode', 'Chuyển sang chế độ BBCode', function(){
            if(wrap.classList.contains('source-mode')){
                body.innerHTML=bbToHtml(source.value);
                wrap.classList.remove('source-mode');
                mode.textContent='Trình soạn thảo trực quan';
                sync(editor);
            }else{
                sync(editor);
                wrap.classList.add('source-mode');
                mode.textContent='Chế độ BBCode';
                source.focus();
            }
        });
        toolbar.appendChild(sourceBtn);

        body.addEventListener('mouseup', function(){saveSelection(editor);});
        body.addEventListener('keyup', function(){saveSelection(editor);});
        body.addEventListener('input', function(){saveSelection(editor); editor.dirty=true; sync(editor);});
        body.addEventListener('blur', function(){saveSelection(editor); sync(editor);});
        toolbar.addEventListener('mousedown', function(){saveSelection(editor);});
        toolbar.addEventListener('touchstart', function(){saveSelection(editor);}, {passive:true});
        source.addEventListener('input', function(){
            textarea.value=source.value;
            var text=source.value.replace(/\[\/?.+?\]/g,'');
            count.textContent=text.trim().length+' ký tự';
            editor.dirty=true;
        });
        body.addEventListener('paste', function(e){
            if (!e.clipboardData) return;
            var html=e.clipboardData.getData('text/html');
            var text=e.clipboardData.getData('text/plain');
            if (html) {
                e.preventDefault();
                var holder=document.createElement('div');
                holder.innerHTML=html;
                Array.prototype.forEach.call(holder.querySelectorAll('script,style,iframe,object,embed,form'),function(n){n.remove();});
                Array.prototype.forEach.call(holder.querySelectorAll('*'),function(n){
                    Array.prototype.forEach.call(n.attributes,function(a){
                        if (/^on/i.test(a.name)) n.removeAttribute(a.name);
                    });
                });
                insertHtml(editor, holder.innerHTML);
            } else if (text) {
                e.preventDefault();
                insertHtml(editor, esc(text).replace(/\r?\n/g,'<br>'));
            }
        });

        wrap.appendChild(toolbar);
        wrap.appendChild(body);
        wrap.appendChild(source);
        wrap.appendChild(status);
        textarea.parentNode.insertBefore(wrap, textarea);
        textarea.style.display='none';

        sync(editor);

        if (textarea.form) {
            textarea.form.addEventListener('submit', function(){
                if (wrap.classList.contains('source-mode')) textarea.value=source.value;
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
