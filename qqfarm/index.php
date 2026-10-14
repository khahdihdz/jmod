<?php
/**
 * QQFarm HTML5/PHP starter gameplay for JMod / JohnCMS 7.1.
 * This is a native mini-game implementation, not the original Flash client.
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
$plots = [];
$crops = [
    'carrot' => ['name' => 'Cà rốt', 'cost' => 10, 'reward' => 18, 'seconds' => 60, 'icon' => '🥕'],
    'wheat' => ['name' => 'Lúa mì', 'cost' => 20, 'reward' => 35, 'seconds' => 120, 'icon' => '🌾'],
    'corn' => ['name' => 'Ngô', 'cost' => 35, 'reward' => 60, 'seconds' => 240, 'icon' => '🌽'],
];

if (empty($_SESSION['qqfarm_csrf'])) {
    $_SESSION['qqfarm_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['qqfarm_csrf'];

try {
    // INSERT IGNORE makes first-visit initialization safe under concurrent requests.
    $init = $db->prepare('INSERT IGNORE INTO qqfarm_players (user_id, coins, last_daily, created_at) VALUES (:uid, 100, NULL, CURRENT_TIMESTAMP)');
    $init->execute([':uid' => $userId]);
    $makePlots = $db->prepare('INSERT IGNORE INTO qqfarm_plots (user_id, plot_no, crop_key, planted_at, ready_at) VALUES (:uid, :plot, NULL, NULL, NULL)');
    for ($plotNo = 1; $plotNo <= 6; $plotNo++) {
        $makePlots->execute([':uid' => $userId, ':plot' => $plotNo]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
        if (!hash_equals($csrf, $token)) {
            http_response_code(403);
            $notice = 'Phiên thao tác không hợp lệ. Hãy tải lại trang.';
        } elseif (isset($_POST['claim_daily'])) {
            $claim = $db->prepare('UPDATE qqfarm_players SET coins = coins + 20, last_daily = CURRENT_DATE WHERE user_id = :uid AND (last_daily IS NULL OR last_daily < CURRENT_DATE)');
            $claim->execute([':uid' => $userId]);
            $notice = $claim->rowCount() === 1 ? 'Bạn nhận được 20 xu thưởng hằng ngày.' : 'Bạn đã nhận thưởng hôm nay. Hãy quay lại ngày mai.';
        } elseif (isset($_POST['plant'], $_POST['plot'], $_POST['crop'])) {
            $plotNo = (int) $_POST['plot'];
            $cropKey = (string) $_POST['crop'];
            if ($plotNo < 1 || $plotNo > 6 || !isset($crops[$cropKey])) {
                $notice = 'Lựa chọn ô đất hoặc hạt giống không hợp lệ.';
            } else {
                $crop = $crops[$cropKey];
                $db->beginTransaction();
                try {
                    $lockPlayer = $db->prepare('SELECT coins FROM qqfarm_players WHERE user_id = :uid FOR UPDATE');
                    $lockPlayer->execute([':uid' => $userId]);
                    $balance = $lockPlayer->fetchColumn();
                    $lockPlot = $db->prepare('SELECT crop_key FROM qqfarm_plots WHERE user_id = :uid AND plot_no = :plot FOR UPDATE');
                    $lockPlot->execute([':uid' => $userId, ':plot' => $plotNo]);
                    $currentCrop = $lockPlot->fetchColumn();
                    if ($currentCrop !== false && $currentCrop !== null && $currentCrop !== '') {
                        $notice = 'Ô đất này đang có cây trồng.';
                    } elseif ((int) $balance < $crop['cost']) {
                        $notice = 'Bạn không đủ xu để mua hạt giống.';
                    } else {
                        $spend = $db->prepare('UPDATE qqfarm_players SET coins = coins - :cost WHERE user_id = :uid AND coins >= :check_cost');
                        $spend->execute([':cost' => $crop['cost'], ':uid' => $userId, ':check_cost' => $crop['cost']]);
                        if ($spend->rowCount() === 1) {
                            $plant = $db->prepare('UPDATE qqfarm_plots SET crop_key = :crop, planted_at = CURRENT_TIMESTAMP, ready_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ' . (int) $crop['seconds'] . ' SECOND) WHERE user_id = :uid AND plot_no = :plot AND crop_key IS NULL');
                            $plant->execute([':crop' => $cropKey, ':uid' => $userId, ':plot' => $plotNo]);
                            if ($plant->rowCount() === 1) {
                                $notice = 'Đã gieo ' . $crop['name'] . ' ở ô đất số ' . $plotNo . '.';
                            } else {
                                throw new RuntimeException('Không thể cập nhật ô đất.');
                            }
                        } else {
                            $notice = 'Bạn không đủ xu để mua hạt giống.';
                        }
                    }
                    $db->commit();
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    throw $e;
                }
            }
        } elseif (isset($_POST['harvest'], $_POST['plot'])) {
            $plotNo = (int) $_POST['plot'];
            if ($plotNo < 1 || $plotNo > 6) {
                $notice = 'Ô đất không hợp lệ.';
            } else {
                $db->beginTransaction();
                try {
                    $lockPlot = $db->prepare('SELECT crop_key, ready_at FROM qqfarm_plots WHERE user_id = :uid AND plot_no = :plot FOR UPDATE');
                    $lockPlot->execute([':uid' => $userId, ':plot' => $plotNo]);
                    $row = $lockPlot->fetch(PDO::FETCH_ASSOC);
                    if (!$row || empty($row['crop_key']) || empty($row['ready_at'])) {
                        $notice = 'Ô đất này chưa có cây để thu hoạch.';
                    } elseif (!isset($crops[$row['crop_key']])) {
                        $notice = 'Loại cây trong ô đất không hợp lệ; hãy liên hệ quản trị viên.';
                    } else {
                        $ready = $db->prepare('UPDATE qqfarm_plots SET crop_key = NULL, planted_at = NULL, ready_at = NULL WHERE user_id = :uid AND plot_no = :plot AND crop_key = :crop AND ready_at <= CURRENT_TIMESTAMP');
                        $ready->execute([':uid' => $userId, ':plot' => $plotNo, ':crop' => $row['crop_key']]);
                        if ($ready->rowCount() === 1) {
                            $reward = $crops[$row['crop_key']]['reward'];
                            $pay = $db->prepare('UPDATE qqfarm_players SET coins = coins + :reward WHERE user_id = :uid');
                            $pay->execute([':reward' => $reward, ':uid' => $userId]);
                            $notice = 'Thu hoạch thành công! Bạn nhận được ' . $reward . ' xu.';
                        } else {
                            $notice = 'Cây chưa chín. Hãy quay lại sau.';
                        }
                    }
                    $db->commit();
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    throw $e;
                }
            }
        }
    }

    $stmt = $db->prepare('SELECT user_id, coins, last_daily FROM qqfarm_players WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $userId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    $plotStmt = $db->prepare('SELECT plot_no, crop_key, planted_at, ready_at, (ready_at IS NOT NULL AND ready_at <= CURRENT_TIMESTAMP) AS is_ready FROM qqfarm_plots WHERE user_id = :uid ORDER BY plot_no');
    $plotStmt->execute([':uid' => $userId]);
    $plots = $plotStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('[QQFarm] ' . $e->getMessage());
    $player = null;
    $plots = [];
    $notice = 'Chưa cài đặt hoặc chưa cập nhật cơ sở dữ liệu QQFarm. Xem qqfarm/README.md.';
}

require '../system/head.php';
$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
echo '<div class="phdr"><b>🌱 QQFarm Việt hóa</b></div>';
echo '<div class="menu">Xin chào, <b>' . $esc($systemUser->name) . '</b>! Trồng cây, chờ cây lớn và thu hoạch để kiếm xu.</div>';
if ($notice !== '') {
    echo '<div class="rmenu">' . $esc($notice) . '</div>';
}
if ($player) {
    echo '<div class="gmenu"><b>Ví xu:</b> ' . number_format((int) $player['coins']) . ' xu &nbsp;|&nbsp; <b>Đất:</b> ' . count($plots) . ' ô</div>';
    echo '<div class="menu"><form method="post" action=""><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><button type="submit" name="claim_daily" value="1">🎁 Nhận 20 xu hằng ngày</button></form>';
    echo !empty($player['last_daily']) && $player['last_daily'] >= gmdate('Y-m-d') ? '<small>Đã nhận thưởng hôm nay.</small>' : '<small>Thưởng đăng nhập mỗi ngày: 20 xu.</small>';
    echo '</div>';
    echo '<div class="phdr"><b>Ruộng của bạn</b></div>';
    foreach ($plots as $plot) {
        $no = (int) $plot['plot_no'];
        $key = $plot['crop_key'];
        echo '<div class="menu"><b>Ô đất ' . $no . '</b><br>';
        if (empty($key)) {
            echo '🟫 Đất trống<br><form method="post" action=""><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><input type="hidden" name="plot" value="' . $no . '"><select name="crop">';
            foreach ($crops as $cropId => $crop) {
                echo '<option value="' . $esc($cropId) . '">' . $esc($crop['icon'] . ' ' . $crop['name'] . ' — ' . $crop['cost'] . ' xu, thu ' . $crop['reward'] . ' xu, ' . $crop['seconds'] . ' giây') . '</option>';
            }
            echo '</select> <button type="submit" name="plant" value="1">Gieo hạt</button></form>';
        } elseif (!isset($crops[$key])) {
            echo 'Cây không xác định.';
        } else {
            $crop = $crops[$key];
            echo $esc($crop['icon'] . ' ' . $crop['name']) . '<br>';
            if ((int) $plot['is_ready'] === 1) {
                echo '<b>Đã chín — sẵn sàng thu hoạch!</b><br><form method="post" action=""><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><input type="hidden" name="plot" value="' . $no . '"><button type="submit" name="harvest" value="1">Thu hoạch +' . $crop['reward'] . ' xu</button></form>';
            } else {
                echo 'Đang phát triển. Dự kiến xong: ' . $esc($plot['ready_at']) . ' UTC.';
            }
        }
        echo '</div>';
    }
    echo '<div class="menu"><b>Giá hạt giống / tiền thu hoạch</b><br>';
    foreach ($crops as $crop) {
        echo $esc($crop['icon'] . ' ' . $crop['name'] . ': ' . $crop['cost'] . ' xu → ' . $crop['reward'] . ' xu; ' . $crop['seconds'] . ' giây') . '<br>';
    }
    echo '</div>';
    echo '<div class="menu"><small>Đây là bản chơi thử PHP/HTML, chưa phải bản chuyển đổi đầy đủ của QQFarm 6.2 gốc.</small></div>';
}
echo '<div class="menu"><a href="../">Quay lại trang chủ</a></div>';
require '../system/end.php';
