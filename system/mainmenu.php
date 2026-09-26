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

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var Johncms\Counters $counters */
$counters = $container->get('counters');

$db = $container->get(PDO::class);
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// ============================================================
// Trang chủ kiểu Blog: lấy bài viết từ bảng `news`.
// Thumbnail tự động = ảnh đầu tiên trong nội dung bài viết.
// Mô tả tự động = nội dung đã loại BBCode/HTML và rút gọn.
// ============================================================
$blogLimit = 8;
$blog = $db->query("SELECT `id`, `time`, `avt`, `name`, `text`, `kom` FROM `news` ORDER BY `time` DESC LIMIT " . $blogLimit);

function johncms_blog_thumbnail($text)
{
    if (preg_match('/<img[^>]+src=["\\\']([^"\\\']+)["\\\']/i', $text, $m)) {
        return trim($m[1]);
    }

    if (preg_match('/\\[img(?:=[^\\]]+)?\\]([^\\[]+)\\[\\\/img\\]/i', $text, $m)) {
        return trim($m[1]);
    }

    return '';
}

function johncms_blog_slug($title, $id = 0)
{
    $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES, 'UTF-8'));
    if (function_exists('transliterator_transliterate')) {
        $title = transliterator_transliterate('Any-Latin; Latin-ASCII', $title);
    } else {
        $title = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title;
    }
    $title = strtolower($title);
    $title = preg_replace('/[^a-z0-9]+/', '-', $title);
    $title = trim($title, '-');
    return ($id ? $id . '-' : '') . ($title ?: 'article');
}

function johncms_blog_excerpt($text, $tools, $length = 180)
{
    $text = preg_replace('/\\[img(?:=[^\\]]+)?\\].*?\\[\\\/img\\]/is', ' ', $text);
    $text = preg_replace('/\\[url(?:=[^\\]]+)?\\](.*?)\\[\\\/url\\]/is', '$1', $text);
    $text = preg_replace('/\\[[^\\]]+\\]/', ' ', $text);
    $text = strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
    $text = preg_replace('/\\s+/u', ' ', trim($text));
    if (mb_strlen($text, 'UTF-8') > $length) {
        $text = mb_substr($text, 0, $length, 'UTF-8');
        $text = preg_replace('/\\s+[^\\s]*$/u', '', $text) . '…';
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

$blogCount = (int) $db->query('SELECT COUNT(*) FROM `news`')->fetchColumn();

echo '<section class="blog-home">';
echo '<div class="blog-heading">';
echo '<div><span class="blog-kicker">' . _t('Latest articles', 'system') . '</span><h1>' . _t('News') . '</h1><p>' . _t('Latest news and articles') . '</p></div>';
echo '<a class="blog-all" href="news/">' . _t('News archive', 'system') . ' <span>→</span></a>';
echo '</div>';

if ($blogCount > 0) {
    echo '<div class="blog-grid">';
    while ($res = $blog->fetch()) {
        $title = htmlspecialchars($res['name'], ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars($res['avt'], ENT_QUOTES, 'UTF-8');
        $excerpt = johncms_blog_excerpt($res['text'], $tools);
        $thumb = johncms_blog_thumbnail($res['text']);
        $slug = johncms_blog_slug($res['name'], (int) $res['id']);
        $articleUrl = 'news/' . $slug;
        $thumbHtml = $thumb !== ''
            ? '<img src="' . htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') . '" alt="' . $title . '" loading="lazy">'
            : '<span class="blog-thumb-placeholder" aria-hidden="true"><span>✦</span></span>';

        echo '<article class="blog-card">';
        echo '<a class="blog-thumb" href="' . $articleUrl . '" aria-label="' . $title . '">' . $thumbHtml . '</a>';
        echo '<div class="blog-card-body">';
        echo '<div class="blog-meta"><span>' . $tools->displayDate($res['time']) . '</span><span>•</span><span>' . $author . '</span></div>';
        echo '<h2><a href="' . $articleUrl . '">' . $title . '</a></h2>';
        echo '<p>' . $excerpt . '</p>';
        echo '<a class="blog-read" href="' . $articleUrl . '">' . _t('Read more') . ' <span>→</span></a>';
        echo '</div></article>';
    }
    echo '</div>';
} else {
    echo '<div class="blog-empty">' . _t('No news') . '</div>';
}
echo '</section>';

// Các khu vực chức năng JohnCMS vẫn giữ nguyên bên dưới blog.
echo '<div class="phdr"><b>' . _t('Communication', 'system') . '</b></div>';

if ($config->mod_guest || $systemUser->rights >= 7) {
    echo '<div class="menu"><a href="guestbook/index.php">' . _t('Guestbook', 'system') . '</a> (' . $counters->guestbook() . ')</div>';
}
if ($config->mod_forum || $systemUser->rights >= 7) {
    echo '<div class="menu"><a href="forum/">' . _t('Forum', 'system') . '</a> (' . $counters->forum() . ')</div>';
}

echo '<div class="phdr"><b>' . _t('Useful', 'system') . '</b></div>';
if ($config->mod_down || $systemUser->rights >= 7) {
    echo '<div class="menu"><a href="downloads/">' . _t('Downloads', 'system') . '</a> (' . $counters->downloads() . ')</div>';
}
if ($config->mod_lib || $systemUser->rights >= 7) {
    echo '<div class="menu"><a href="library/">' . _t('Library', 'system') . '</a> (' . $counters->library() . ')</div>';
}

if ($systemUser->isValid() || $config->active) {
    echo '<div class="phdr"><b>' . _t('Community', 'system') . '</b></div>' .
        '<div class="menu"><a href="users/index.php">' . _t('Users', 'system') . '</a> (' . $counters->users() . ')</div>' .
        '<div class="menu"><a href="album/index.php">' . _t('Photo Albums', 'system') . '</a> (' . $counters->album() . ')</div>';
}

echo '<div class="phdr"><a href="http://gazenwagen.com">Gazenwagen</a></div>';
