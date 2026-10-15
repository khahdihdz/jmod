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
$mission = ['progress' => 0, 'claimed_at' => null];
$pet = null;
$crops = [
    'carrot' => ['name' => 'Cà rốt', 'cost' => 10, 'reward' => 18, 'xp' => 5, 'seconds' => 60, 'level' => 1, 'icon' => '🥕'],
    'wheat' => ['name' => 'Lúa mì', 'cost' => 20, 'reward' => 35, 'xp' => 10, 'seconds' => 120, 'level' => 1, 'icon' => '🌾'],
    'corn' => ['name' => 'Ngô', 'cost' => 35, 'reward' => 60, 'xp' => 15, 'seconds' => 240, 'level' => 1, 'icon' => '🌽'],
    'strawberry' => ['name' => 'Dâu tây', 'cost' => 60, 'reward' => 105, 'xp' => 25, 'seconds' => 360, 'level' => 2, 'icon' => '🍓'],
    'pumpkin' => ['name' => 'Bí đỏ', 'cost' => 100, 'reward' => 175, 'xp' => 40, 'seconds' => 600, 'level' => 4, 'icon' => '🎃'],
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
        } elseif (isset($_POST['adopt_pet'])) {
            $petTypes = [
                'farm_cat' => ['name' => 'Mèo Nông Trại', 'cost' => 150, 'gift' => 10, 'icon' => '🐱'],
                'farm_rabbit' => ['name' => 'Thỏ Đồng', 'cost' => 220, 'gift' => 15, 'icon' => '🐰'],
                'farm_fox' => ['name' => 'Cáo Đỏ', 'cost' => 350, 'gift' => 20, 'icon' => '🦊'],
            ];
            $requestedPet = isset($_POST['pet_key']) ? (string) $_POST['pet_key'] : 'farm_cat';
            $db->beginTransaction();
            try {
                // Lock the player row first to serialize simultaneous adoption requests.
                $lockPetPlayer = $db->prepare('SELECT coins FROM qqfarm_players WHERE user_id = :uid FOR UPDATE');
                $lockPetPlayer->execute([':uid' => $userId]);
                $petBalance = (int) $lockPetPlayer->fetchColumn();
                $petExists = $db->prepare('SELECT user_id FROM qqfarm_pets WHERE user_id = :uid');
                $petExists->execute([':uid' => $userId]);
                if (!isset($petTypes[$requestedPet])) {
                    $notice = 'Loại thú nuôi không hợp lệ.';
                } elseif ($petExists->fetchColumn() !== false) {
                    $notice = 'Bạn đã có thú nuôi đồng hành rồi.';
                } elseif ($petBalance < $petTypes[$requestedPet]['cost']) {
                    $notice = 'Bạn không đủ xu để nhận nuôi ' . $petTypes[$requestedPet]['name'] . '.';
                } else {
                    $petType = $petTypes[$requestedPet];
                    $spendPet = $db->prepare('UPDATE qqfarm_players SET coins = coins - :cost WHERE user_id = :uid AND coins >= :minimum');
                    $spendPet->execute([':cost' => $petType['cost'], ':uid' => $userId, ':minimum' => $petType['cost']]);
                    if ($spendPet->rowCount() === 1) {
                        $adopt = $db->prepare('INSERT INTO qqfarm_pets (user_id, pet_key, last_gift, created_at) VALUES (:uid, :pet_key, NULL, CURRENT_TIMESTAMP)');
                        $adopt->execute([':uid' => $userId, ':pet_key' => $requestedPet]);
                        $notice = 'Bạn đã nhận nuôi ' . $petType['name'] . '! Thú nuôi tặng ' . $petType['gift'] . ' xu mỗi ngày.';
                    } else {
                        $notice = 'Số dư đã thay đổi; hãy tải lại trang và thử lại.';
                    }
                }
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                throw $e;
            }
        } elseif (isset($_POST['claim_pet_gift'])) {
            $petTypes = [
                'farm_cat' => ['name' => 'Mèo Nông Trại', 'gift' => 10, 'icon' => '🐱'],
                'farm_rabbit' => ['name' => 'Thỏ Đồng', 'gift' => 15, 'icon' => '🐰'],
                'farm_fox' => ['name' => 'Cáo Đỏ', 'gift' => 20, 'icon' => '🦊'],
            ];
            $db->beginTransaction();
            try {
                $lockPet = $db->prepare('SELECT pet_key, last_gift FROM qqfarm_pets WHERE user_id = :uid FOR UPDATE');
                $lockPet->execute([':uid' => $userId]);
                $petRow = $lockPet->fetch(PDO::FETCH_ASSOC);
                if (!$petRow || !isset($petTypes[$petRow['pet_key']])) {
                    $notice = 'Bạn chưa có thú nuôi hợp lệ.';
                } elseif (!empty($petRow['last_gift']) && $petRow['last_gift'] >= date('Y-m-d')) {
                    $notice = 'Thú nuôi đã tặng quà hôm nay. Hãy quay lại ngày mai.';
                } else {
                    $petGift = $db->prepare('UPDATE qqfarm_pets SET last_gift = CURRENT_DATE WHERE user_id = :uid AND (last_gift IS NULL OR last_gift < CURRENT_DATE)');
                    $petGift->execute([':uid' => $userId]);
                    if ($petGift->rowCount() === 1) {
                        $giftAmount = $petTypes[$petRow['pet_key']]['gift'];
                        $petReward = $db->prepare('UPDATE qqfarm_players SET coins = coins + :gift WHERE user_id = :uid');
                        $petReward->execute([':gift' => $giftAmount, ':uid' => $userId]);
                        $notice = $petTypes[$petRow['pet_key']]['name'] . ' tặng bạn ' . $giftAmount . ' xu hôm nay!';
                    } else {
                        $notice = 'Thú nuôi đã tặng quà hôm nay. Hãy quay lại ngày mai.';
                    }
                }
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                throw $e;
            }
        } elseif (isset($_POST['claim_mission'])) {
            $db->beginTransaction();
            try {
                $claimMission = $db->prepare("UPDATE qqfarm_daily_missions SET claimed_at = CURRENT_TIMESTAMP WHERE user_id = :uid AND mission_date = CURRENT_DATE AND mission_key = 'harvest3' AND progress >= 3 AND claimed_at IS NULL");
                $claimMission->execute([':uid' => $userId]);
                if ($claimMission->rowCount() === 1) {
                    $rewardMission = $db->prepare('UPDATE qqfarm_players SET coins = coins + 50, experience = experience + 20 WHERE user_id = :uid');
                    $rewardMission->execute([':uid' => $userId]);
                    $notice = 'Hoàn thành nhiệm vụ ngày! +50 xu, +20 XP.';
                } else {
                    $notice = 'Bạn cần thu hoạch đủ 3 lần hôm nay và chưa nhận thưởng nhiệm vụ.';
                }
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                throw $e;
            }
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
                    $lockPlayer = $db->prepare('SELECT coins, experience FROM qqfarm_players WHERE user_id = :uid FOR UPDATE');
                    $lockPlayer->execute([':uid' => $userId]);
                    $playerRow = $lockPlayer->fetch(PDO::FETCH_ASSOC);
                    $balance = $playerRow ? (int) $playerRow['coins'] : 0;
                    $playerLevel = $playerRow ? (int) floor((int) $playerRow['experience'] / 100) + 1 : 1;
                    $lockPlot = $db->prepare('SELECT crop_key FROM qqfarm_plots WHERE user_id = :uid AND plot_no = :plot FOR UPDATE');
                    $lockPlot->execute([':uid' => $userId, ':plot' => $plotNo]);
                    $currentCrop = $lockPlot->fetchColumn();
                    if ($currentCrop !== false && $currentCrop !== null && $currentCrop !== '') {
                        $notice = 'Ô đất này đang có cây trồng.';
                    } elseif ($playerLevel < $crop['level']) {
                        $notice = 'Bạn cần đạt cấp ' . $crop['level'] . ' để mở khóa ' . $crop['name'] . '.';
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
                            $xp = $crops[$row['crop_key']]['xp'];
                            $pay = $db->prepare('UPDATE qqfarm_players SET coins = coins + :reward, experience = experience + :xp WHERE user_id = :uid');
                            $pay->execute([':reward' => $reward, ':xp' => $xp, ':uid' => $userId]);
                            $missionProgress = $db->prepare("INSERT INTO qqfarm_daily_missions (user_id, mission_date, mission_key, progress, claimed_at) VALUES (:uid, CURRENT_DATE, 'harvest3', 1, NULL) ON DUPLICATE KEY UPDATE progress = LEAST(progress + 1, 3)");
                            $missionProgress->execute([':uid' => $userId]);
                            $notice = 'Thu hoạch thành công! +' . $reward . ' xu, +' . $xp . ' XP.';
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

    $stmt = $db->prepare('SELECT user_id, coins, experience, last_daily FROM qqfarm_players WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $userId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    $plotStmt = $db->prepare('SELECT plot_no, crop_key, planted_at, ready_at, (ready_at IS NOT NULL AND ready_at <= CURRENT_TIMESTAMP) AS is_ready FROM qqfarm_plots WHERE user_id = :uid ORDER BY plot_no');
    $plotStmt->execute([':uid' => $userId]);
    $plots = $plotStmt->fetchAll(PDO::FETCH_ASSOC);
    $makeMission = $db->prepare("INSERT IGNORE INTO qqfarm_daily_missions (user_id, mission_date, mission_key, progress, claimed_at) VALUES (:uid, CURRENT_DATE, 'harvest3', 0, NULL)");
    $makeMission->execute([':uid' => $userId]);
    $missionStmt = $db->prepare("SELECT progress, claimed_at FROM qqfarm_daily_missions WHERE user_id = :uid AND mission_date = CURRENT_DATE AND mission_key = 'harvest3' LIMIT 1");
    $missionStmt->execute([':uid' => $userId]);
    $missionRow = $missionStmt->fetch(PDO::FETCH_ASSOC);
    if ($missionRow) { $mission = $missionRow; }
    $petStmt = $db->prepare('SELECT pet_key, last_gift FROM qqfarm_pets WHERE user_id = :uid LIMIT 1');
    $petStmt->execute([':uid' => $userId]);
    $pet = $petStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('[QQFarm] ' . $e->getMessage());
    $player = null;
    $plots = [];
    $notice = 'Chưa cài đặt hoặc chưa cập nhật cơ sở dữ liệu QQFarm. Xem qqfarm/README.md.';
}

require '../system/head.php';
echo '<main class="qqfarm">';
$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
echo '<section class="farm-hero"><h2>🌱 QQFarm Việt hóa</h2><div>Chăm sóc nông trại, gieo hạt và thu hoạch để tích lũy xu.</div></section>';
echo '<div class="menu">Xin chào, <b>' . $esc($systemUser->name) . '</b>! Trồng cây, chờ cây lớn và thu hoạch để kiếm xu.</div>';
if ($notice !== '') {
    echo '<div class="rmenu">' . $esc($notice) . '</div>';
}
if ($player) {
    $experience = (int) $player['experience'];
    $level = (int) floor($experience / 100) + 1;
    $levelProgress = $experience % 100;
    echo '<div class="farm-stats"><div class="farm-stat"><span>💰 Ví xu</span><strong>' . number_format((int) $player['coins']) . '</strong><span class="farm-muted">xu hiện có</span></div><div class="farm-stat"><span>⭐ Cấp độ ' . $level . '</span><strong>' . number_format($experience) . ' XP</strong><div class="farm-xp-track"><span style="width:' . $levelProgress . '%"></span></div><span class="farm-muted">' . $levelProgress . '/100 XP đến cấp tiếp theo</span></div><div class="farm-stat"><span>🟫 Nông trại</span><strong>' . count($plots) . ' ô đất</strong><span class="farm-muted">mỗi tài khoản một ruộng</span></div></div>';
    echo '<div class="menu farm-daily"><div><b>🎁 Thưởng hằng ngày</b><br><small class="farm-muted">Nhận 20 xu, tối đa một lần mỗi ngày.</small></div><form method="post" action=""><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><button type="submit" name="claim_daily" value="1">Nhận thưởng</button></form>';
    echo !empty($player['last_daily']) && $player['last_daily'] >= gmdate('Y-m-d') ? '<small>Đã nhận thưởng hôm nay.</small>' : '<small>Thưởng đăng nhập mỗi ngày: 20 xu.</small>';
    echo '</div>';
    $missionProgressValue = min(3, (int) $mission['progress']);
    echo '<section class="menu farm-mission"><div><b>📜 Nhiệm vụ hôm nay: Thu hoạch 3 lần</b><br><small class="farm-muted">Tiến độ: ' . $missionProgressValue . '/3 · Phần thưởng: 50 xu và 20 XP</small><div class="farm-xp-track"><span style="width:' . (int) round($missionProgressValue / 3 * 100) . '%"></span></div></div>';
    if (!empty($mission['claimed_at'])) {
        echo '<span class="farm-state">Đã nhận thưởng</span>';
    } elseif ($missionProgressValue >= 3) {
        echo '<form method="post" action=""><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><button type="submit" name="claim_mission" value="1">Nhận thưởng nhiệm vụ</button></form>';
    } else {
        echo '<span class="farm-muted">Thu hoạch cây để tăng tiến độ.</span>';
    }
    echo '</section>';
    echo '<section class="menu farm-pet"><b>🐾 Thú nuôi đồng hành</b><br>';
    $petTypes = [
        'farm_cat' => ['name' => 'Mèo Nông Trại', 'cost' => 150, 'gift' => 10, 'icon' => '🐱', 'desc' => 'Thân thiện, quà 10 xu/ngày'],
        'farm_rabbit' => ['name' => 'Thỏ Đồng', 'cost' => 220, 'gift' => 15, 'icon' => '🐰', 'desc' => 'Nhanh nhẹn, quà 15 xu/ngày'],
        'farm_fox' => ['name' => 'Cáo Đỏ', 'cost' => 350, 'gift' => 20, 'icon' => '🦊', 'desc' => 'Hiếm, quà 20 xu/ngày'],
    ];
    if (!$pet) {
        echo '<p>Chọn một người bạn đồng hành. Mỗi tài khoản chỉ nhận nuôi được một thú.</p><div class="farm-pet-options">';
        foreach ($petTypes as $petKey => $petType) {
            echo '<form method="post" action="" class="farm-pet-option"><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><input type="hidden" name="pet_key" value="' . $esc($petKey) . '"><div class="farm-pet-icon">' . $esc($petType['icon']) . '</div><b>' . $esc($petType['name']) . '</b><small>' . $esc($petType['desc']) . '</small><button type="submit" name="adopt_pet" value="1">Nhận nuôi · ' . $petType['cost'] . ' xu</button></form>';
        }
        echo '</div>';
    } else {
        $activePet = isset($petTypes[$pet['pet_key']]) ? $petTypes[$pet['pet_key']] : $petTypes['farm_cat'];
        echo '<p><span class="farm-pet-icon">' . $esc($activePet['icon']) . '</span> <b>' . $esc($activePet['name']) . '</b> đang đồng hành cùng bạn.</p>';
        if (!empty($pet['last_gift']) && $pet['last_gift'] >= date('Y-m-d')) {
            echo '<small class="farm-muted">Thú nuôi đã tặng quà hôm nay.</small>';
        } else {
            echo '<form method="post" action=""><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><button type="submit" name="claim_pet_gift" value="1">Nhận quà từ thú nuôi (+' . $activePet['gift'] . ' xu)</button></form>';
        }
    }
    echo '</section>';
    echo '<h3>🌿 Ruộng của bạn</h3><div class="farm-grid">';
    foreach ($plots as $plot) {
        $no = (int) $plot['plot_no'];
        $key = $plot['crop_key'];
        echo '<section class="farm-plot"><div class="farm-plot-title"><b>Ô đất ' . $no . '</b>';
        if (empty($key)) {
            echo '<span class="farm-state">Đất trống</span></div><div class="farm-crop">🟫<small>Chưa gieo trồng</small></div><form method="post" action=""><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><input type="hidden" name="plot" value="' . $no . '"><select name="crop">';
            foreach ($crops as $cropId => $crop) {
                if ($level >= $crop['level']) {
                    echo '<option value="' . $esc($cropId) . '">' . $esc($crop['icon'] . ' ' . $crop['name'] . ' — ' . $crop['cost'] . ' xu, thu ' . $crop['reward'] . ' xu, +' . $crop['xp'] . ' XP, ' . $crop['seconds'] . ' giây') . '</option>';
                }
            }
            echo '</select> <button type="submit" name="plant" value="1">Gieo hạt</button></form>';
        } elseif (!isset($crops[$key])) {
            echo '</div><div class="farm-crop">Cây không xác định.</div>';
        } else {
            $crop = $crops[$key];
            echo '<span class="farm-state">' . ((int) $plot['is_ready'] === 1 ? 'Đã chín' : 'Đang lớn') . '</span></div><div class="farm-crop"><span>' . $esc($crop['icon']) . '</span><b>' . $esc($crop['name']) . '</b>';
            if ((int) $plot['is_ready'] === 1) {
                echo '<small>Sẵn sàng thu hoạch</small></div><form method="post" action=""><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><input type="hidden" name="plot" value="' . $no . '"><button type="submit" name="harvest" value="1">Thu hoạch +' . $crop['reward'] . ' xu</button></form>';
            } else {
                echo '<small class="farm-muted">Dự kiến chín: ' . $esc($plot['ready_at']) . ' UTC</small></div>';
            }
        }
        echo '</section>';
    }
    echo '</div><div class="farm-market"><b>🛒 Chợ hạt giống</b><br>';
    foreach ($crops as $crop) {
        $locked = $level < $crop['level'];
        echo $esc($crop['icon'] . ' ' . $crop['name'] . ': ' . $crop['cost'] . ' xu → ' . $crop['reward'] . ' xu, +' . $crop['xp'] . ' XP; ' . $crop['seconds'] . ' giây' . ($locked ? ' — mở khóa ở cấp ' . $crop['level'] : ' — đã mở khóa')) . '<br>';
    }
    echo '</div>';
    echo '<p class="farm-muted">Bản thử nghiệm PHP/HTML; chưa phải bản chuyển đổi đầy đủ QQFarm 6.2 gốc.</p>';
}
echo '<p><a href="../">← Quay lại trang chủ</a></p></main>';
require '../system/end.php';
