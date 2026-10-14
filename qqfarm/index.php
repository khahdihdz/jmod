<?php
/**
 * QQFarm integration entry point for JMod / JohnCMS 7.1.
 * This is a safe integration scaffold, not the original Flash game client.
 */
define('_IN_JOHNCMS', 1);
require_once '../system/bootstrap.php';

$container = App::getContainer();
/** @var PDO $db */
$db = $container->get(PDO::class);
/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

$headmod = 'qqfarm';
$textl = 'QQFarm Việt hóa';

if (!$systemUser->isValid()) {
    $config = $container->get(Johncms\Api\ConfigInterface::class);
    header('Location: ' . $config->homeurl . '/login.php');
    exit;
}

$userId = (int) $systemUser->id;
$notice = '';
$player = null;

if (empty($_SESSION['qqfarm_csrf'])) {
    $_SESSION['qqfarm_csrf'] = bin2hex(random_bytes(32));
}

try {
    $stmt = $db->prepare('SELECT user_id, coins, last_daily FROM qqfarm_players WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $userId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        $insert = $db->prepare('INSERT INTO qqfarm_players (user_id, coins, last_daily, created_at) VALUES (:uid, 100, NULL, CURRENT_TIMESTAMP)');
        $insert->execute([':uid' => $userId]);
        $stmt->execute([':uid' => $userId]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_daily'])) {
        $token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
        if (!hash_equals($_SESSION['qqfarm_csrf'], $token)) {
            http_response_code(403);
            $notice = 'Phiên thao tác không hợp lệ. Hãy tải lại trang.';
        } else {
            $claim = $db->prepare(
                'UPDATE qqfarm_players SET coins = coins + 20, last_daily = CURRENT_DATE
                 WHERE user_id = :uid AND (last_daily IS NULL OR last_daily < CURRENT_DATE)'
            );
            $claim->execute([':uid' => $userId]);
            if ($claim->rowCount() === 1) {
                $notice = 'Bạn nhận được 20 xu thưởng hằng ngày.';
            } else {
                $notice = 'Bạn đã nhận thưởng hôm nay. Hãy quay lại vào ngày mai.';
            }
            $stmt->execute([':uid' => $userId]);
            $player = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log('[QQFarm] ' . $e->getMessage());
    $player = null;
    $notice = 'Chưa cài đặt bảng dữ liệu QQFarm. Xem qqfarm/README.md để cài đặt.';
}

require '../system/head.php';
echo '<div class="phdr"><b>QQFarm Việt hóa</b></div>';
echo '<div class="menu">Xin chào, <b>' . htmlspecialchars((string) $systemUser->name, ENT_QUOTES, 'UTF-8') . '</b>.</div>';

if ($notice !== '') {
    echo '<div class="rmenu">' . htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') . '</div>';
}

if ($player) {
    echo '<div class="menu"><b>Nông trại của bạn</b></div>';
    echo '<div class="menu">Số xu: <b>' . (int) $player['coins'] . '</b></div>';
    echo '<div class="menu">Đây là khung tích hợp ban đầu; client Flash và các API game gốc chưa được chuyển đổi.</div>';
    echo '<div class="menu"><form method="post" action="">';
    echo '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['qqfarm_csrf'], ENT_QUOTES, 'UTF-8') . '">';
    echo '<button type="submit" name="claim_daily" value="1">Nhận 20 xu hằng ngày</button>';
    echo '</form></div>';
}
echo '<div class="menu"><a href="../">Quay lại trang chủ</a></div>';
require '../system/end.php';
