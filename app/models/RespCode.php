<?php

/**
 * クライアントへのレスポンスコード
 */
class RespCode {

    // エラーなし
    const SUCCESS = 0;
    // 予期せぬエラー
    const UNKNOWN_ERROR = 1;
    // セッション切れ（セッションID見つからない）
    const SESSION_ERROR = 2;
    // ユーザ未登録
    const USER_NOT_FOUND = 3;
    // 指定カードが見つからない
    const CARD_NOT_FOUND = 4;
    // スタミナ不足
    const LACK_OF_STAMINA = 5;
    // カードの上限オーバー
    const EXCEEDED_MAX_NUM_CARD = 6;
    // すでに登録済み（ユーザー登録時）
    const USER_ALREADY_EXISTS = 7;
    // ダンジョン潜入に失敗
    const FAILED_SNEAK = 8;
    // ダンジョンクリアパラメタ不正
    const INVALID_CLEAR_HASH = 9;
    // 既にフレンド
    const ALREADY_FRIEND = 10;
    // フレンド数上限オーバー(被申請者)
    const TARGET_REACHED_MAX_NUM_FRIEND = 11;
    // フレンド数上限オーバー(申請者)
    const INVITER_REACHED_MAX_NUM_FRIEND = 12;
    // 合成失敗.
    const FAILED_COMPOSITION = 13;
    // カード売却失敗.
    const FAILED_SELLING_USER_CARD = 14;
    // ガチャ失敗.
    const FAILED_GACHA = 15;
    // 既にお礼返し済み
    const ALREADY_SENT_PRESENT = 16;
    // デッキ設定 コストオーバー
    const EXCEEDED_MAX_COST = 17;
    // 魔法石(gold)が足りない
    const NOT_ENOUGH_MONEY = 18;
    // フレンドではない
    const NOT_FRIEND = 19;
    // 名前が不正 (名前変更API)
    const INVALID_NAME = 20;
    // 進化失敗.
    const FAILED_EVOLUTION = 21;
    // 既に購入済み(レシート使用済み)
    const ALREADY_PURCHASED = 22;
    // リクエストのチェックサムが不正
    const INVALID_REQUEST_CHECKSUM = 23;
    // フレンド申請送信者　フレンド数上限オーバー
    const SENDER_TOO_MANY_FRIENDS = 24;
    // フレンド申請受信者　フレンド数(または許可待ち状態フレンドリクエスト)上限オーバー
    const RECEIVER_TOO_MANY_FRIENDS = 25;
    // 指定されたHASH値が見つからなかった
    const CONTINUE_HASH_NOT_FOUND = 26;
    // 既にコンティニュー済み
    const CONTINUE_ALREADY_ACKED = 27;
    // ダンジョン潜入ACKでのスタミナ消費に失敗.
    const FAILED_SPEND_STAMINA = 28;
    // ダンジョンクリア処理に失敗.
    const FAILED_CLEAR_DUNGEON = 29;
    // メールが見つからなかった
    const MAIL_NOT_FOUND = 30;
    // アカバン、クライアントバージョンエラー
    const APP_VERSION_ERR = 31;
    // 存在しないシリアル番号
    const SERIAL_NOT_FOUND = 32;
    // カード付与失敗
    const FAILED_ADD_CARD = 33;
    // ユーザーまたはコード未登録
    const FAILED_USER_OR_CODE = 34;
    // ユーザー間メール受信拒否
    const REJECT_USER_MAIL = 35;
    // フレンド申請受信拒否
    const REJECT_FRIEND_REQUEST = 36;
    // ログインモードで未プレイ
    const NOT_PLAY_LOGIN_MODE = 37;
    // ダンジョン購入失敗
    const FAILED_BUY_DUNGEON = 38;
    // 技能觉醒失败
    const FAILED_AWAKE_SKILL = 39;
    // #PADC# ----------begin----------
    // テンセントサーバーと通信エラー
    const TENCENT_NETWORK_ERROR = 1001;
    //　テンセントAPIエラー
    const TENCENT_API_ERROR = 1002;
    //　テンセントトークンエラー
    const TENCENT_TOKEN_ERROR = 1003;
    // #PADC_DY# ----------begin----------
    // 预留错误码1
    const TENCENT_NETWORK_COMMON_ERROR = 1004;
    // 预留错误码2
    const TENCENT_API_COMMON_ERROR = 1005;
    // #PADC_DY# -----------end-----------
    // ユーザ登録上限エラー
    const SIGNUP_LIMIT_ERROR = 2001;
    const NGWORD_ERROR = 2002;
    // ユーザ無料コンティニュー回数が足りない
    const NOT_ENOUGH_USER_CONTINUE = 2003;
    // 最終ログイン時間が当日ではない（再ログインを促す）
    const LOGIN_DATE_DIFFERENT = 2004;
    // 周回チケットが足りない
    const NOT_ENOUGH_USER_ROUND = 2005;
    const FAILED_SELL_PIECE = 2006;
    //テンセント安全IDIPより機能禁止
    const PLAY_BAN = 2007;
    // セッション切れ（セッションIDが異なる）
    const SESSION_ERROR_DIFFERENT = 2008;
    // IDIPより強制オフライン
    const KICK_OFF = 2009;
    // ミッション報酬受け取り時間外
    const MISSION_REWARD_TIME_OUT = 2010;
    // ランキングポイント交換アイテムが存在しないまたはラインアップ期間切れ
    const INVALID_EXCHANGE_ITEM = 2011;
    //上限に達している
    const ITEM_REACH_MAX = 2012;
    // #PADC# ----------end----------
        
    // #PADC_DY# ----------begin----------
    const INVALID_PARAMS = 2013;
    const ALREADY_RECEIVED = 2014;
    const STAR_NOT_ENOUGH = 2015;
    const TIMES_USED_OUT = 2016;

    const ACTIVITY_ALREADY_FINISHED = 2017; // 活动已结束
    const CONDITION_NOT_REACH = 2018; // 活动条件未达成

    const ACTIVITY_NOT_IN_TIME = 2019;//限时魔法商店不在活动期

    const PLAY_BAN_SEND_MAIL = 2020; // 禁言

    const CARNIVAL_MISSION_CHECK = 2021; //嘉年华任务完成状态
    const GACHA_DISABLE = 2022;//无扭蛋次数
	// #PADC_DY# ----------end----------
}
