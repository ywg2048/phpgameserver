<?php
$host = FALSE;
if (count($argv) > 1) {
    $host = $argv[1];
}

if (!$argv[2]) {
    echo "please set OpenID";
}

$openid = $argv[2];

if ($host) {

    require_once("../app/config/autoload.php");
    setEnvironment($host);

    $pdo_share = Env::getDbConnectionForShare();
    $sql = "select id from user_devices where oid = '" . $openid . "'";
    $stmt = $pdo_share->prepare($sql);
    $stmt->execute();
    $user_id = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$user_id) {
        echo "this openid is not found";
		exit(1);
    }
    $user_id = $user_id[0];
    if (!$user_id) {
        echo "user not fund";
		exit(1);
    }

    $pdo_user = Env::getDbConnectionForUserWrite($user_id);
    try {

        $user = User::find($user_id);
        if (!$user) {
            echo "user not fund in db";
			exit(1);
        }
        $user->lv = 200;
        $user->stamina = 999;
        $user->stamina_max = 999;
        $user->coin = 9999999;
        $user->round = 999;
        $user->vip_lv = 10;
        $user->tp_gold = 100000;
        $user->update($pdo_user);

        $cards = Card::getAll();
        foreach ($cards as $card) {
            if ($card->padc_id < 10000) {
                if ($card->id == 0) {
                    continue;
                }
                $user_card = UserCard::findBy(array(
                    'user_id' => $user_id,
                    'card_id' => $card->id
                ));
                if (!$user_card) {
                    UserCard::addCardToUser($user_id, $card->id, UserCard::DEFAULT_LEVEL, UserCard::DEFAULT_SKILL_LEVEL, $pdo_user);
                }
            }
        }
        $sql = "DELETE FROM user_dungeon_floors WHERE user_id = " . $user_id . " AND dungeon_id < 10000;";
        $stmt = $pdo_user->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute();

        $floors = DungeonFloor::getAll();
        foreach ($floors as $floor) {
            if ($floor->dungeon_id < 10000) {
                $user_dungeon_floor = new UserDungeonFloor();
                $user_dungeon_floor->user_id = $user_id;
                $user_dungeon_floor->dungeon_id = $floor->dungeon_id;
                $user_dungeon_floor->dungeon_floor_id = $floor->id;
                $user_dungeon_floor->first_played_at = null;
                $user_dungeon_floor->cm1_first_played_at = null;
                $user_dungeon_floor->cm1_cleared_at = null;
                $user_dungeon_floor->cm2_first_played_at = null;
                $user_dungeon_floor->cm2_cleared_at = null;
                $user_dungeon_floor->cleared_at = BaseModel::timeToStr(time());
                $user_dungeon_floor->daily_cleared_at = null;
                $user_dungeon_floor->max_star = 3; // #PADC_DY# 三星数初始化
                $user_dungeon_floor->daily_first_played_at = null; // #PADC_DY# 单日第一次潜入时间
                $user_dungeon_floor->daily_played_times = 0; // #PADC_DY# 当日潜入次数
                $user_dungeon_floor->daily_recovered_times = 0; // #PADC_DY# 当日恢复次数
                $user_dungeon_floor->create($pdo_user);
            }
        }

        $sql = "delete from user_pieces where user_id = " . $user_id . ";";
        $stmt = $pdo_user->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute();

        $pieces = Piece::getAll();
        foreach ($pieces as $piece) {
            if ($piece->id != 0 && $piece->id != 10007) {
                $user_piece = new UserPiece();
                $user_piece->user_id = $user_id;
                $user_piece->piece_id = $piece->id;
                $user_piece->num = 999;
                $user_piece->create_card = 1;
                $user_piece->last_get_time = User::timeToStr(time());
                $user_piece->create($pdo_user);
            }
        }

    } catch (Exception $e) {
        echo 'ERROR Message: ' . $e->getMessage() . "\n";
        echo "ERROR Dump: \n";
        // var_dump($e);

    }
    echo strftime("%y/%m/%d %H:%M:%S", time()) . ' : success' . "\n";
} else {
    echo "no_host_name\n";
}
return;