<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

namespace Johncms;

use Psr\Container\ContainerInterface;

class Bbcode implements Api\BbcodeInterface
{
    /**
     * @var Api\ConfigInterface
     */
    protected $config;

    /**
     * @var Api\UserInterface::class
     */
    protected $user;

    /**
     * @var UserConfig
     */
    protected $userConfig;

    /**
     * @var \GeSHi
     */
    protected $geshi;

    protected $homeUrl;

    public function __invoke(ContainerInterface $container)
    {
        $this->config = $container->get(Api\ConfigInterface::class);
        $this->user = $container->get(Api\UserInterface::class);
        $this->userConfig = $this->user->getConfig();
        $this->homeUrl = $this->config['homeurl'];

        return $this;
    }

    // Обработка тэгов и ссылок
    public function tags($var)
    {
        // Bảo vệ [img]...[/img] trong lúc xử lý URL.
        // Nếu để URL parser chạy trước, URL ảnh sẽ bị biến thành <a ...>,
        // khiến media() không còn nhận diện được cú pháp [img].
        $images = [];
        $var = preg_replace_callback(
            '~\\\\[img(?:=\\\\d{1,4}x\\\\d{1,4})?\\\\].*?\\\\[/img\\\\]~isu',
            function ($match) use (&$images) {
                $key = '__JMOD_IMG_' . count($images) . '__';
                $images[$key] = $match[0];
                return $key;
            },
            $var
        );

        $var = $this->parseTime($var);               // Обработка тэга времени
        $var = $this->highlightCode($var);           // Подсветка кода
        $var = $this->highlightBb($var);             // Основные BBCode
        $var = $this->youtube($var);                 // YouTube
        $var = $this->highlightBbcodeUrl($var);      // Ссылки в BBCode
        $var = $this->highlightUrl($var);            // Обычные ссылки

        // Khôi phục BBCode ảnh rồi render ở bước cuối.
        if ($images) {
            $var = strtr($var, $images);
        }
        $var = $this->media($var);                   // Изображения

        return $var;
    }

    public function notags($var = '')
    {
        $var = preg_replace('#\[color=(.+?)\](.+?)\[/color]#si', '$2', $var);
        $var = preg_replace('#\[timestamp\](.+?)\[/timestamp]#si', '$2', $var);
        $var = preg_replace('#\[code=(.+?)\](.+?)\[/code]#si', '$2', $var);
        $var = preg_replace('!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is', '$2', $var);
        $var = preg_replace('#\[spoiler(?:=(.+?))?\](.+?)\[/spoiler\]#si', '$2', $var);
        $replace = [
            '[small]' => '', '[/small]' => '', '[big]' => '', '[/big]' => '',
            '[green]' => '', '[/green]' => '', '[red]' => '', '[/red]' => '',
            '[blue]' => '', '[/blue]' => '', '[b]' => '', '[/b]' => '',
            '[i]' => '', '[/i]' => '', '[u]' => '', '[/u]' => '',
            '[s]' => '', '[/s]' => '', '[quote]' => '', '[/quote]' => '',
            '[youtube]' => '', '[/youtube]' => '', '[php]' => '', '[/php]' => '',
            '[c]' => '', '[/c]' => '', '[center]' => '', '[/center]' => '',
            '[left]' => '', '[/left]' => '', '[right]' => '', '[/right]' => '',
            '[justify]' => '', '[/justify]' => '', '[sup]' => '', '[/sup]' => '',
            '[sub]' => '', '[/sub]' => '', '[br]' => '', '[hr]' => '',
            '[list]' => '', '[/list]' => '', '[*]' => '', '[/*]' => '',
        ];

        return strtr($var, $replace);
    }

    /**
     * BbCode Toolbar
     *
     * @param string $form
     * @param string $field
     * @return string
     */
    public function buttons($form, $field)
    {
        $field = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $field);
        $scriptUrl = htmlspecialchars($this->homeUrl . '/system/js/jmod-editor.js', ENT_QUOTES, 'UTF-8');

        return '<div class="jmod-editor-mount" data-jmod-editor="' . $field . '"></div>'
            . '<script src="' . $scriptUrl . '"></script>'
            . '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    if (window.JmodEditor) {
                        window.JmodEditor.init("' . $field . '");
                    }
                });
            </script>';
    }

    /**
     * Обработка тэга [time]
     *
     * @param string $var
     * @return string
     */
    protected function parseTime($var)
    {
        $var = preg_replace_callback(
            '#\[time\](.+?)\[\/time\]#s',
            function ($matches) {
                $shift = ($this->config['timeshift'] + $this->userConfig->timeshift) * 3600;

                if (($out = strtotime($matches[1])) !== false) {
                    return date("d.m.Y / H:i", $out + $shift);
                } else {
                    return $matches[1];
                }
            },
            $var
        );

        $var = preg_replace_callback(
            '#\[timestamp\](.+?)\[\/timestamp\]#s',
            function ($matches) {
                $shift = ($this->config['timeshift'] + $this->userConfig->timeshift) * 3600;

                if (($out = strtotime($matches[1])) !== false) {
                    return '<small class="gray">' . _t('Added', 'system') . ': ' . date("d.m.Y / H:i", $out + $shift) . '</small>';
                } else {
                    return $matches[1];
                }
            },
            $var
        );

        return $var;
    }

    /**
     * Парсинг ссылок
     * За основу взята доработанная функция от форума phpBB 3.x.x
     *
     * @param $text
     * @return mixed
     */
    protected function highlightUrl($text)
    {
        $homeurl = $this->homeUrl;

        // Обработка внутренних ссылок
        $text = preg_replace_callback(
            '#(^|[\n\t (>.])(' . preg_quote($homeurl,
                '#') . ')/((?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*(?:/(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?)#iu',
            function ($matches) {
                return $this->urlCallback(1, $matches[1], $matches[2], $matches[3]);
            },
            $text
        );

        // Обработка обычных ссылок типа xxxx://aaaaa.bbb.cccc. ...
        $text = preg_replace_callback(
            '#(^|[\n\t (>.])([a-z][a-z\d+]*:/{2}(?:(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-zа-яё0-9.]+:[a-zа-яё0-9.]+:[a-zа-яё0-9.:]+\])(?::\d*)?(?:/(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?)#iu',
            function ($matches) {
                return $this->urlCallback(2, $matches[1], $matches[2], '');
            },
            $text
        );

        return $text;
    }

    private function urlCallback($type, $whitespace, $url, $relative_url)
    {
        $orig_url = $url;
        $orig_relative = $relative_url;
        $url = htmlspecialchars_decode($url);
        $relative_url = htmlspecialchars_decode($relative_url);
        $text = '';
        $chars = ['<', '>', '"'];
        $split = false;

        foreach ($chars as $char) {
            $next_split = strpos($url, $char);
            if ($next_split !== false) {
                $split = ($split !== false) ? min($split, $next_split) : $next_split;
            }
        }

        if ($split !== false) {
            $url = substr($url, 0, $split);
            $relative_url = '';
        } else {
            if ($relative_url) {
                $split = false;
                foreach ($chars as $char) {
                    $next_split = strpos($relative_url, $char);
                    if ($next_split !== false) {
                        $split = ($split !== false) ? min($split, $next_split) : $next_split;
                    }
                }
                if ($split !== false) {
                    $relative_url = substr($relative_url, 0, $split);
                }
            }
        }

        $last_char = ($relative_url) ? $relative_url[strlen($relative_url) - 1] : $url[strlen($url) - 1];

        switch ($last_char) {
            case '.':
            case '?':
            case '!':
            case ':':
            case ',':
                $append = $last_char;
                if ($relative_url) {
                    $relative_url = substr($relative_url, 0, -1);
                } else {
                    $url = substr($url, 0, -1);
                }
                break;

            default:
                $append = '';
                break;
        }

        $short_url = (mb_strlen($url) > 40) ? mb_substr($url, 0, 30) . ' ... ' . mb_substr($url, -5) : $url;

        switch ($type) {
            case 1:
                $relative_url = preg_replace('/[&?]sid=[0-9a-f]{32}$/', '', preg_replace('/([&?])sid=[0-9a-f]{32}&/', '$1', $relative_url));
                $url = $url . '/' . $relative_url;
                $text = $relative_url;
                if (!$relative_url) {
                    return $whitespace . $orig_url . '/' . $orig_relative;
                }
                break;

            case 2:
                $text = $short_url;
                if (!$this->userConfig->directUrl) {
                    $url = $this->homeUrl . '/go.php?url=' . rawurlencode($url);
                }
                break;

            case 4:
                $text = $short_url;
                $url = 'mailto:' . $url;
                break;
        }
        $url = htmlspecialchars($url);
        $text = htmlspecialchars($text);
        $append = htmlspecialchars($append);

        return $whitespace . '<a href="' . $url . '">' . $text . '</a>' . $append;
    }

    /**
     * Подсветка кода
     *
     * @param string $var
     * @return mixed
     */
    protected function highlightCode($var)
    {
        $var = preg_replace_callback('#\[php\](.+?)\[\/php\]#s', [$this, 'phpCodeCallback'], $var);
        $var = preg_replace_callback('#\[code=(.+?)\](.+?)\[\/code]#is', [$this, 'codeCallback'], $var);

        return $var;
    }

    private function phpCodeCallback($code)
    {
        return $this->codeCallback([1 => 'php', 2 => $code[1]]);
    }

    private function codeCallback($code)
    {
        $parsers = [
            'php'  => 'php',
            'css'  => 'css',
            'html' => 'html5',
            'js'   => 'javascript',
            'sql'  => 'sql',
            'xml'  => 'xml',
        ];

        $parser = isset($code[1]) && isset($parsers[$code[1]]) ? $parsers[$code[1]] : 'php';

        if (null === $this->geshi) {
            $this->geshi = new \GeSHi;
            $this->geshi->set_link_styles(GESHI_LINK, 'text-decoration: none');
            $this->geshi->set_link_target('_blank');
            $this->geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
            $this->geshi->set_line_style('background: rgba(255, 255, 255, 0.5)', 'background: rgba(255, 255, 255, 0.35)', false);
            $this->geshi->set_code_style('padding-left: 6px; white-space: pre-wrap');
        }

        $this->geshi->set_language($parser);
        $php = strtr($code[2], ['<br />' => '']);
        $php = html_entity_decode(trim($php), ENT_QUOTES, 'UTF-8');
        $this->geshi->set_source($php);

        return '<div class="phpcode" style="overflow-x: auto">' . $this->geshi->parse_code() . '</div>';
    }

    /**
     * Обработка URL в тэгах BBcode
     *
     * @param $var
     * @return mixed
     */
    protected function highlightBbcodeUrl($var)
    {
        $callback = function ($url) {
            $target = html_entity_decode(trim($url[1]), ENT_QUOTES, 'UTF-8');
            $label = isset($url[2]) ? $url[2] : $target;
            $parsed = parse_url($target);

            if (!$parsed || empty($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
                return $label;
            }

            // Luôn giữ nguyên URL đích tuyệt đối; không chuyển qua go.php
            // và không thay đổi domain thành URL nội bộ.
            $targetEsc = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
            return '<a href="' . $targetEsc . '" rel="noopener noreferrer">' . $label . '</a>';
        };

        // Repair common malformed/nested URL BBCode produced by copy-paste.
        // Examples: [url][url=https://example.test]...[/url][/url]
        // and [url]url=https://example.test[/url].
        for ($i = 0; $i < 3; $i++) {
            $var = preg_replace(
                '~\[url\]\s*\[url=(https?://[^\s\]]+)\](.*?)\[/url\]\s*\[/url\]~isu',
                '[url=$1]$2[/url]',
                $var
            );
            $var = preg_replace(
                '~\[url\]\s*url=(https?://[^\s\]]+)\[/url\]~isu',
                '[url=$1]$1[/url]',
                $var
            );
            $var = preg_replace(
                '~\[url=(https?://[^\s\]]+)\]\s*\[url=(https?://[^\s\]]+)\](.*?)\[/url\]\s*\[/url\]~isu',
                '[url=$1]$3[/url]',
                $var
            );
        }

        $var = preg_replace_callback('~\[url=(https?://[^\s\]]+)](.+?)\[/url]~isu', $callback, $var);
        return preg_replace_callback('~\[url\](https?://[^\s\[]+)\[/url\]~isu',
            function ($m) use ($callback) { return $callback([1 => $m[1], 2 => $m[1]]); },
            $var
        );
    }

    /**
     * Список замен для основных тегов BB-кода.
     *
     * @return array
     */
    /**
     * Image BBCode. Only HTTP(S) is allowed.
     */
    protected function media($var)
    {
        return preg_replace_callback(
            '~\\[img(?:=(\\d{1,4})x(\\d{1,4}))?\\](.*?)\\[/img\\]~isu',
            function ($m) {
                // checkout() HTML-escapes stored text before BBCode parsing.
                $url = trim(html_entity_decode($m[3], ENT_QUOTES, 'UTF-8'));
                $parsed = parse_url($url);

                // Only allow absolute HTTP(S) URLs.
                if (!$parsed || empty($parsed['host']) || empty($parsed['scheme'])
                    || !in_array(strtolower($parsed['scheme']), ['http', 'https'], true)
                    || preg_match('/[\\r\\n"<>]/', $url)
                ) {
                    return $m[0];
                }

                $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                $size = '';

                if (!empty($m[1]) && !empty($m[2])) {
                    $w = (int) $m[1];
                    $h = (int) $m[2];
                    if ($w > 0 && $h > 0) {
                        $size = ' width="' . min($w, 1600) . '" height="' . min($h, 1200) . '"';
                    }
                }

                return '<a class="bb-image-link" href="' . $safe . '" target="_blank" rel="noopener noreferrer">'
                    . '<img class="bb-image"' . $size
                    . ' src="' . $safe . '" loading="lazy" decoding="async" alt="BBCode image">'
                    . '</a>';
            },
            $var
        );
    }

    protected function replacements()
    {
        return [
            // Жирный
            'b'       => [
                'from' => '#\[b](.+?)\[/b]#is',
                'to'   => '<span style="font-weight: bold">$1</span>',
            ],
            // Курсив
            'i'       => [
                'from' => '#\[i](.+?)\[/i]#is',
                'to'   => '<span style="font-style:italic">$1</span>',
            ],
            // Подчёркнутый
            'u'       => [
                'from' => '#\[u](.+?)\[/u]#is',
                'to'   => '<span style="text-decoration:underline">$1</span>',
            ],
            // Зачёркнутый
            's'       => [
                'from' => '#\[s](.+?)\[/s]#is',
                'to'   => '<span style="text-decoration:line-through">$1</span>',
            ],
            // Маленький шрифт
            'small'   => [
                'from' => '#\[small](.+?)\[/small]#is',
                'to'   => '<span style="font-size:x-small">$1</span>',
            ],
            // Большой шрифт
            'big'     => [
                'from' => '#\[big](.+?)\[/big]#is',
                'to'   => '<span style="font-size:large">$1</span>',
            ],
            // Красный
            'red'     => [
                'from' => '#\[red](.+?)\[/red]#is',
                'to'   => '<span style="color:red">$1</span>',
            ],
            // Зеленый
            'green'   => [
                'from' => '#\[green](.+?)\[/green]#is',
                'to'   => '<span style="color:green">$1</span>',
            ],
            // Синий
            'blue'    => [
                'from' => '#\[blue](.+?)\[/blue]#is',
                'to'   => '<span style="color:blue">$1</span>',
            ],
            // Цвет шрифта
            'color'   => [
                'from' => '!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/color]!is',
                'to'   => '<span style="color:$1">$2</span>',
            ],
            // Цвет фона
            'bg'      => [
                'from' => '!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is',
                'to'   => '<span style="background-color:$1">$2</span>',
            ],
            // Цитата
            'quote'   => [
                'from' => '#\[(quote|c)](.+?)\[/(quote|c)]#is',
                'to'   => '<span class="quote" style="display:block">$2</span>',
            ],
            // Список
            'list'    => [
                'from' => '#\[\*](.+?)\[/\*]#is',
                'to'   => '<span class="bblist">$1</span>',
            ],
            // Спойлер
            'spoiler' => [
                // Hỗ trợ cả [spoiler]...[/spoiler] và [spoiler=Tiêu đề]...[/spoiler].
                'from' => '#\[spoiler(?:=(.*?))?\](.+?)\[/spoiler]#is',
                'to'   => '<div class="bb-spoiler"><button type="button" class="spoilerhead" onclick="var b=this.nextElementSibling;if(b){b.hidden=!b.hidden;this.setAttribute(\'aria-expanded\',b.hidden?\'false\':\'true\');}">$1<span class="spoiler-toggle">(+/-)</span></button><div class="spoilerbody" hidden>$2</div></div>',
            ],
            // Căn chỉnh
            'center' => ['from' => '#\[center\](.+?)\[/center]#is', 'to' => '<div class="bb-center">$1</div>'],
            'left' => ['from' => '#\[left\](.+?)\[/left]#is', 'to' => '<div class="bb-left">$1</div>'],
            'right' => ['from' => '#\[right\](.+?)\[/right]#is', 'to' => '<div class="bb-right">$1</div>'],
            'justify' => ['from' => '#\[justify\](.+?)\[/justify]#is', 'to' => '<div class="bb-justify">$1</div>'],
            // Chỉ số
            'sup' => ['from' => '#\[sup\](.+?)\[/sup]#is', 'to' => '<sup>$1</sup>'],
            'sub' => ['from' => '#\[sub\](.+?)\[/sub]#is', 'to' => '<sub>$1</sub>'],
            'br' => ['from' => '#\[br\]#i', 'to' => '<br>'],
            'hr' => ['from' => '#\[hr\]#i', 'to' => '<hr class="bb-hr">'],
            'list' => ['from' => '#\[list\](.+?)\[/list]#is', 'to' => '<ul class="bb-list">$1</ul>'],
        ];
    }

    /**
     * Обработка bbCode
     *
     * @param string $var
     * @return string
     */
    protected function highlightBb($var)
    {
        $replacements = array_values($this->replacements());
        $search = array_column($replacements, 'from');
        $replace = array_column($replacements, 'to');

        return preg_replace($search, $replace, $var);
    }

    /**
     * Youtube bbcode
     *
     * @param string $var
     * @return string
     */
    protected function youtube($var)
    {
        return preg_replace_callback(
            '#\[youtube\](.+?)\[\/youtube\]#s',
            function ($matches) {
                if (preg_match('/youtube.com/', $matches[1])) {
                    $values = explode('=', $matches[1]);
                    $valuesto = explode('&', $values[1]);

                    return $this->youtubePlayer($valuesto[0]);
                } elseif (preg_match('/youtu.be/', $matches[1])) {
                    return $this->youtubePlayer(trim(parse_url($matches[1])['path'], '//'));
                } else {
                    $valuesto = explode('&', $matches[1]);

                    return $this->youtubePlayer($valuesto[0]);
                }
            },
            $var, 3
        );
    }

    protected function youtubePlayer($result)
    {
        if ($this->userConfig->youtube) {
            return '
<style>.video-container {
	position:relative;
	padding-bottom:56.25%;
	padding-top:30px;
	height:0;
	overflow:hidden;
}
.video-container iframe, .video-container object, .video-container embed {
	position:absolute;
	top:0;
	left:0;
	width:100%;
	height:100%;
}
</style>
<div style="max-width: 500px">
<div class="video-container">
<iframe allowfullscreen="allowfullscreen" src="//www.youtube.com/embed/' . $result . '" frameborder="0"></iframe>
</div></div>';
        } else {
            return '<div><a target="_blank" href="//m.youtube.com/watch?v=' . $result . '"><img src="//img.youtube.com/vi/' . $result . '/1.jpg" border="0" alt="youtube.com/embed/' . $result . '"></a></div>';
        }
    }
}
