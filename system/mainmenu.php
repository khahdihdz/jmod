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

function johncms_post_thumbnail($text)
{
    // HTML: <img src="...">
    if (preg_match('/<img[^>]+src=["\\\']([^"\\\']+)["\\\']/i', $text, $m)) {
        return trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    }

    // JohnCMS/BBCode: [img]URL[/img] or [img=WIDTHxHEIGHT]URL[/img]
    if (preg_match('/\\[img(?:=[^\\]]+)?\\]([^\\[]+)\\[\\\/img\\]/i', $text, $m)) {
        return trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
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

// ============================================================
// Bài viết mới từ diễn đàn trên trang chủ.
// ============================================================
if ($config->mod_forum || $systemUser->rights >= 7) {
    $forumLimit = 6;
    $forumPosts = $db->query(
        "SELECT `id`, `refid`, `time`, `user_id`, `from`, `text`
         FROM `forum`
         WHERE `type` = 'm' AND `close` != '1'
         ORDER BY time DESC, id DESC
         LIMIT " . $forumLimit
    );

    echo '<section class="forum-home forum-blog-feed">';
    echo '<div class="forum-blog-cover">';
    echo '<span class="forum-blog-badge">COMMUNITY</span>';
    echo '<h2>Khám phá thảo luận mới nhất</h2>';
    echo '<p>Cập nhật những câu chuyện, câu hỏi và chia sẻ mới từ diễn đàn.</p>';
    echo '<a class="forum-blog-cover-link" href="forum/">Tham gia cộng đồng →</a>';
    echo '</div>';
    echo '<div class="forum-home-heading">';
    echo '<div><span class="forum-home-kicker">Diễn đàn</span><h2>Bài viết mới</h2><p>Các thảo luận mới nhất từ cộng đồng</p></div>';
    echo '<a class="forum-home-all" href="forum/">Xem diễn đàn <span>→</span></a>';
    echo '</div>';

    $hasForumPosts = false;
    if ($forumPosts) {
        $forumPosts = $forumPosts->fetchAll(PDO::FETCH_ASSOC);
        $hasForumPosts = !empty($forumPosts);
    }

    if ($hasForumPosts) {
        echo '<div class="forum-home-list">';

        foreach ($forumPosts as $post) {
            $topic = $db->query(
                "SELECT id, refid, text
                 FROM forum
                 WHERE id = '" . (int) $post['refid'] . "' AND type = 't'
                 LIMIT 1"
            )->fetch();

            if (!$topic) {
                continue;
            }

            $section = $db->query(
                "SELECT id, refid, text
                 FROM forum
                 WHERE id = '" . (int) $topic['refid'] . "' AND type = 'r'
                 LIMIT 1"
            )->fetch();

            $category = $section ? $db->query(
                "SELECT id, text
                 FROM forum
                 WHERE id = '" . (int) $section['refid'] . "' AND type = 'f'
                 LIMIT 1"
            )->fetch() : false;

            $topicTitle = htmlspecialchars($topic['text'], ENT_QUOTES, 'UTF-8');
            $author = htmlspecialchars($post['from'], ENT_QUOTES, 'UTF-8');
            $postThumb = johncms_post_thumbnail($post['text']);
            $postThumbHtml = $postThumb !== ''
                ? '<img src="' . htmlspecialchars($postThumb, ENT_QUOTES, 'UTF-8') . '" alt="' . $topicTitle . '" loading="lazy">'
                : '<span class="forum-home-thumb-placeholder" aria-hidden="true"><span>💬</span></span>';
            $excerpt = mb_substr($post['text'], 0, 180, 'UTF-8');
            $excerpt = $tools->checkout($excerpt, 2, 1);
            $excerpt = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $excerpt);

            echo '<article class="forum-home-item">';
            echo '<a class="forum-home-thumb" href="forum/index.php?id=' . (int) $topic['id'] . '" aria-label="' . $topicTitle . '">' . $postThumbHtml . '</a>';
            echo '<div class="forum-home-content">';

            echo '<div class="forum-home-meta"><span>' . $author . '</span><span>•</span><span>' . $tools->displayDate($post['time']) . '</span></div>';
            echo '<h3><a href="forum/index.php?id=' . (int) $topic['id'] . '">' . $topicTitle . '</a></h3>';
            echo '<div class="forum-home-excerpt">' . $excerpt . '</div>';

            if ($category || $section) {
                echo '<div class="forum-home-category">';
                if ($category) {
                    echo '<a href="forum/index.php?id=' . (int) $category['id'] . '">' . htmlspecialchars($category['text'], ENT_QUOTES, 'UTF-8') . '</a>';
                }
                if ($category && $section) {
                    echo ' / ';
                }
                if ($section) {
                    echo '<a href="forum/index.php?id=' . (int) $section['id'] . '">' . htmlspecialchars($section['text'], ENT_QUOTES, 'UTF-8') . '</a>';
                }
                echo '</div>';
            }

            echo '<a class="forum-home-read" href="forum/index.php?act=post&amp;id=' . (int) $post['id'] . '">Đọc bài viết →</a>';
            echo '</div></article>';
        }

        echo '</div>';
    } else {
        echo '<div class="forum-home-empty">Chưa có bài viết mới trong diễn đàn.</div>';
    }

    echo '</section>';
}

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

