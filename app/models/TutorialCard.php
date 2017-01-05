<?php
/**
 * #PADC# チュートリアル用カード.
 */

class TutorialCard extends Card {
	const TABLE_NAME = "padc_tutorial_cards";
	const VER_KEY_GROUP = "tutorial_card";
	const MEMCACHED_EXPIRE = 86400; // 24時間.
}
