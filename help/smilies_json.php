<?php
/**
 * JohnCMS/JMod smiley feed for the WYSIWYG editor.
 * Returns the same smiley assets and codes used by JohnCMS.
 */

define('_IN_JOHNCMS', 1);

require('../system/bootstrap.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$homeurl = rtrim((string)$config['homeurl'], '/');
$smileys = [];
$seen = [];

/**
 * Add a JohnCMS smiley to the feed.
 */
$add = static function ($code, $url) use (&$smileys, &$seen) {
    $code = trim((string)$code);
    $url = trim((string)$url);

    if ($code === '' || $url === '' || isset($seen[$code])) {
        return;
    }

    $seen[$code] = true;
    $smileys[] = [
        'code' => $code,
        'url' => $url,
    ];
};

$ext = ['gif', 'jpg', 'jpeg', 'png'];

// Built-in JohnCMS smileys.
foreach (glob(ROOT_PATH . 'images/smileys/simply/*') ?: [] as $filePath) {
    $file = basename($filePath);
    $parts = pathinfo($file);
    $extension = strtolower($parts['extension'] ?? '');

    if (!in_array($extension, $ext, true)) {
        continue;
    }

    $name = $parts['filename'];
    $url = $homeurl . '/images/smileys/simply/' . rawurlencode($file);

    // This follows JohnCMS' own smiley code convention.
    $add(':' . $name . ':', $url);
}

// User smiley catalog used by JohnCMS.
foreach (glob(ROOT_PATH . 'images/smileys/user/*/*') ?: [] as $filePath) {
    $file = basename($filePath);
    $category = basename(dirname($filePath));
    $parts = pathinfo($file);
    $extension = strtolower($parts['extension'] ?? '');

    if (!in_array($extension, $ext, true)) {
        continue;
    }

    $name = $parts['filename'];
    $url = $homeurl . '/images/smileys/user/' . rawurlencode($category) . '/' . rawurlencode($file);

    // JohnCMS accepts both the original code and its transliterated form.
    $add(':' . $name . ':', $url);
    $translated = trim((string)$tools->trans($name));
    if ($translated !== $name) {
        $add(':' . $translated . ':', $url);
    }
}

echo json_encode(
    ['smileys' => $smileys],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
