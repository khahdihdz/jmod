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

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\EnvironmentInterface $env */
$env = $container->get(Johncms\Api\EnvironmentInterface::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : '';
$headmod = isset($headmod) ? $headmod : '';
$textl = isset($textl) ? $textl : $config['copyright'];
$keywords = isset($keywords) ? htmlspecialchars($keywords) : $config->meta_key;
$descriptions = isset($descriptions) ? htmlspecialchars($descriptions) : $config->meta_desc;

echo '<!DOCTYPE html>' .
    "\n" . '<html lang="' . $config->lng . '">' .
    "\n" . '<head>' .
    "\n" . '<meta charset="utf-8">' .
    "\n" . '<meta http-equiv="X-UA-Compatible" content="IE=edge">' .
    "\n" . '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes">' .
    "\n" . '<meta name="HandheldFriendly" content="true">' .
    "\n" . '<meta name="MobileOptimized" content="width">' .
    "\n" . '<meta content="yes" name="apple-mobile-web-app-capable">' .
    "\n" . '<meta name="Generator" content="JohnCMS, http://johncms.com">' .
    "\n" . '<meta name="keywords" content="' . $keywords . '">' .
    "\n" . '<meta name="description" content="' . $descriptions . '">' .
    "\n" . '<link rel="stylesheet" href="' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/styles.css?v=' . @filemtime(ROOT_PATH . 'styles.css') . '">' .
    "\n" . '<link rel="shortcut icon" href="' . $config->homeurl . '/favicon.ico">' .
    "\n" . '<link rel="alternate" type="application/rss+xml" title="RSS | ' . _t('Site News', 'system') . '" href="' . $config->homeurl . '/rss/rss.php">' .
    "\n" . '<title>' . $textl . '</title>' .
    "\n" . '</head><body>';

// Рекламный модуль
$cms_ads = [];

if (!isset($_GET['err']) && $act != '404' && $headmod != 'admin') {
    $view = $systemUser->id ? 2 : 1;
    $layout = ($headmod == 'mainpage' && !$act) ? 1 : 2;
    $req = $db->query("SELECT * FROM `cms_ads` WHERE `to` = '0' AND (`layout` = '$layout' or `layout` = '0') AND (`view` = '$view' or `view` = '0') ORDER BY `mesto` ASC");

    if ($req->rowCount()) {
        while ($res = $req->fetch()) {
            $name = explode("|", $res['name']);
            $name = htmlentities($name[mt_rand(0, (count($name) - 1))], ENT_QUOTES, 'UTF-8');

            if (!empty($res['color'])) {
                $name = '<span style="color:#' . $res['color'] . '">' . $name . '</span>';
            }

            $font = $res['bold'] ? 'font-weight: bold;' : false;
            $font .= $res['italic'] ? ' font-style:italic;' : false;
            $font .= $res['underline'] ? ' text-decoration:underline;' : false;

            if ($font) {
                $name = '<span style="' . $font . '">' . $name . '</span>';
            }

            @$cms_ads[$res['type']] .= '<a href="' . ($res['show'] ? $tools->checkout($res['link']) : $config['homeurl'] . '/go.php?id=' . $res['id']) . '">' . $name . '</a><br>';

            if (($res['day'] != 0 && time() >= ($res['time'] + $res['day'] * 3600 * 24))
                || ($res['count_link'] != 0 && $res['count'] >= $res['count_link'])
            ) {
                $db->exec('UPDATE `cms_ads` SET `to` = 1 WHERE `id` = ' . $res['id']);
            }
        }
    }
}

if (isset($cms_ads[0])) {
    echo $cms_ads[0];
}

// Header thương hiệu + menu điều hướng
$homeUrl = $config['homeurl'];
$currentSection = $headmod;

echo '<header class="site-header">';
echo '<div class="header-inner">';
echo '<a class="header-brand-link" href="' . $homeUrl . '" aria-label="JMod">';
echo '<span class="header-brand-mark">J</span>';
echo '<span class="header-brand-text"><strong>JMod</strong><small>JohnCMS Community</small></span>';
echo '</a>';

echo '<div class="header-user">';
if ($systemUser->id) {
    echo '<span>Xin chào, <b>' . htmlspecialchars($systemUser->name, ENT_QUOTES, 'UTF-8') . '</b></span>';
} else {
    echo '<span>Chào mừng bạn</span>';
}
echo '</div>';
echo '</div>';

echo '<nav class="site-nav" aria-label="Điều hướng chính">';
echo '<input class="nav-menu-check" type="checkbox" id="jmod-nav-toggle">';
echo '<label class="nav-menu-toggle" for="jmod-nav-toggle"><span class="nav-menu-icon">☰</span><span>Menu</span><span class="nav-menu-chevron">⌄</span></label>';
echo '<div class="site-nav-inner">';

$navItems = [
    ['key' => 'mainpage', 'url' => $homeUrl, 'icon' => '⌂', 'label' => _t('Home', 'system')],
    ['key' => 'news', 'url' => $homeUrl . '/news/', 'icon' => '▤', 'label' => 'Tin tức'],
];

if ($config->mod_forum || $systemUser->rights >= 7) {
    $navItems[] = ['key' => 'forum', 'url' => $homeUrl . '/forum/', 'icon' => '☷', 'label' => 'Diễn đàn'];
}

$navItems[] = [
    'key' => 'community', 'icon' => '◆', 'label' => 'Cộng đồng', 'dropdown' => [
        ['url' => $homeUrl . '/forum/', 'icon' => '☷', 'label' => 'Diễn đàn'],
        ['url' => $homeUrl . '/guestbook/', 'icon' => '✎', 'label' => 'Guestbook'],
        ['url' => $homeUrl . '/users/', 'icon' => '◎', 'label' => 'Thành viên'],
        ['url' => $homeUrl . '/album/', 'icon' => '▧', 'label' => 'Album ảnh'],
    ]
];

$navItems[] = [
    'key' => 'resources', 'icon' => '▦', 'label' => 'Tiện ích', 'dropdown' => [
        ['url' => $homeUrl . '/download/', 'icon' => '↓', 'label' => 'Tải xuống'],
        ['url' => $homeUrl . '/library/', 'icon' => '▤', 'label' => 'Thư viện'],
        ['url' => $homeUrl . '/rss/rss.php', 'icon' => '◔', 'label' => 'RSS'],
    ]
];

if ($systemUser->id) {
    $navItems[] = ['key' => 'profile', 'url' => $homeUrl . '/profile/?act=office', 'icon' => '◎', 'label' => _t('Personal', 'system')];
    $navItems[] = ['key' => 'account', 'url' => $homeUrl . '/profile/', 'icon' => '◉', 'label' => 'Tài khoản'];
} else {
    $navItems[] = ['key' => 'login', 'url' => $homeUrl . '/login.php', 'icon' => '→', 'label' => _t('Login', 'system')];
}

foreach ($navItems as $item) {
    $isActive = ($currentSection === $item['key']) ||
        ($item['key'] === 'profile' && $currentSection === 'profile') ||
        ($item['key'] === 'account' && $currentSection === 'profile');
    if (!empty($item['dropdown'])) {
        echo '<div class="nav-dropdown' . ($isActive ? ' is-active' : '') . '">';
        echo '<button class="nav-item nav-dropdown-toggle" type="button" aria-haspopup="true">';
        echo '<span class="nav-icon" aria-hidden="true">' . $item['icon'] . '</span><span>' . $item['label'] . '</span><span class="nav-chevron" aria-hidden="true">⌄</span>';
        echo '</button><div class="nav-dropdown-menu">';
        foreach ($item['dropdown'] as $drop) {
            echo '<a class="nav-dropdown-item" href="' . $drop['url'] . '"><span class="nav-icon" aria-hidden="true">' . $drop['icon'] . '</span><span>' . $drop['label'] . '</span></a>';
        }
        echo '</div></div>';
    } else {
        $activeClass = $isActive ? ' is-active' : '';
        echo '<a class="nav-item' . $activeClass . '" href="' . $item['url'] . '">';
        echo '<span class="nav-icon" aria-hidden="true">' . $item['icon'] . '</span><span>' . $item['label'] . '</span></a>';
    }
}

echo '</div></nav>';

echo '<div class="maintxt">';

// Рекламный блок сайта
if (!empty($cms_ads[1])) {
    echo '<div class="gmenu">' . $cms_ads[1] . '</div>';
}

// Фиксация местоположений посетителей
$sql = '';
$set_karma = $config['karma'];

if ($systemUser->id) {
    if (!$systemUser->karma_off && $set_karma['on'] && $systemUser->karma_time <= (time() - 86400)) {
        $sql .= " `karma_time` = " . time() . ", ";
    }

    $movings = $systemUser->movings;

    if ($systemUser->lastdate < (time() - 300)) {
        $movings = 0;
        $sql .= " `sestime` = " . time() . ", ";
    }

    if ($systemUser->place != $headmod) {
        ++$movings;
        $sql .= " `place` = " . $db->quote($headmod) . ", ";
    }

    if ($systemUser->browser != $env->getUserAgent()) {
        $sql .= " `browser` = " . $db->quote($env->getUserAgent()) . ", ";
    }

    $totalonsite = $systemUser->total_on_site;

    if ($systemUser->lastdate > (time() - 300)) {
        $totalonsite = $totalonsite + time() - $systemUser->lastdate;
    }

    $db->query("UPDATE `users` SET $sql
        `movings` = '$movings',
        `total_on_site` = '$totalonsite',
        `lastdate` = '" . time() . "'
        WHERE `id` = " . $systemUser->id);
} else {
    $movings = 0;
    $session = md5($env->getIp() . $env->getIpViaProxy() . $env->getUserAgent());
    $req = $db->query("SELECT * FROM `cms_sessions` WHERE `session_id` = " . $db->quote($session) . " LIMIT 1");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $movings = ++$res['movings'];

        if ($res['sestime'] < (time() - 300)) {
            $movings = 1;
            $sql .= " `sestime` = '" . time() . "', ";
        }

        if ($res['place'] != $headmod) {
            $sql .= " `place` = " . $db->quote($headmod) . ", ";
        }

        $db->exec("UPDATE `cms_sessions` SET $sql
            `movings` = '$movings',
            `lastdate` = '" . time() . "'
            WHERE `session_id` = " . $db->quote($session) . "
        ");
    } else {
        $db->exec("INSERT INTO `cms_sessions` SET
            `session_id` = '" . $session . "',
            `ip` = '" . $env->getIp() . "',
            `ip_via_proxy` = '" . $env->getIpViaProxy() . "',
            `browser` = " . $db->quote($env->getUserAgent()) . ",
            `lastdate` = '" . time() . "',
            `sestime` = '" . time() . "',
            `place` = " . $db->quote($headmod) . "
        ");
    }
}

// Выводим сообщение о Бане
if (!empty($systemUser->ban)) {
    echo '<div class="alarm">' . _t('Ban', 'system') . '&#160;<a href="' . $config['homeurl'] . '/profile/?act=ban">' . _t('Details', 'system') . '</a></div>';
}

// Ссылки на непрочитанное
if ($systemUser->id) {
    $list = [];
    $new_sys_mail = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `read`='0' AND `sys`='1' AND `delete`!='" . $systemUser->id . "'")->fetchColumn();

    if ($new_sys_mail) {
        $list[] = '<a href="' . $config['homeurl'] . '/mail/index.php?act=systems">' . _t('System', 'system') . '</a> (+' . $new_sys_mail . ')';
    }

    $new_mail = $db->query("SELECT COUNT(*) FROM `cms_mail`
                            LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $systemUser->id . "'
                            WHERE `cms_mail`.`from_id`='" . $systemUser->id . "'
                            AND `cms_mail`.`sys`='0'
                            AND `cms_mail`.`read`='0'
                            AND `cms_mail`.`delete`!='" . $systemUser->id . "'
                            AND `cms_contact`.`ban`!='1'")->fetchColumn();

    if ($new_mail) {
        $list[] = '<a href="' . $config['homeurl'] . '/mail/index.php?act=new">' . _t('Mail', 'system') . '</a> (+' . $new_mail . ')';
    }

    if ($systemUser->comm_count > $systemUser->comm_old) {
        $list[] = '<a href="' . $config['homeurl'] . '/profile/?act=guestbook&amp;user=' . $systemUser->id . '">' . _t('Guestbook', 'system') . '</a> (' . ($systemUser->comm_count - $systemUser->comm_old) . ')';
    }

    $new_album_comm = $db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = ' . $systemUser->id . ' AND `unread_comments` = 1')->fetchColumn();

    if ($new_album_comm) {
        $list[] = '<a href="' . $config['homeurl'] . '/album/index.php?act=top&amp;mod=my_new_comm">' . _t('Comments', 'system') . '</a>';
    }

    if (!empty($list)) {
        echo '<div class="rmenu">' . _t('Unread', 'system') . ': ' . implode(', ', $list) . '</div>';
    }
}
