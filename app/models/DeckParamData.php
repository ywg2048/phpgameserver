<?php

/**
 * #PADC_DY#计算编队战斗力by David
 */
class DeckParamData {
    public $total_hp = 0;
    public $total_atk = 0;
    public $total_rec = 0;
    public $total_pow = 0;
    public $attr_atk = array(0, 0, 0, 0, 0);

    public function getTotalPower($user, $team_id, $pdo) {
        $user_deck = UserDeck::findBy(array('user_id'=>$user->id), $pdo);
        $decks = $user_deck->getUserDecks((int)$user->lv);
        $deck_list = array();
        foreach($decks as $deck){
            foreach($deck as $key => $value){
                $deck_list[$key] = $value;
            }
        }
        $setname = sprintf("set_%02s", $team_id);
        
        // deck内容を更新
        $cuids = array();
        foreach($deck_list[$setname] as $cuid){
            $cuids[] = $cuid;
        }
        $user_cards = UserCard::findByCuids($user->id, $cuids, $pdo);
        $cuid_cards = array();
        foreach($user_cards as $user_card){
            $cuid_cards[$user_card->cuid] = $user_card;
        }

        $deck = array();
        foreach($deck_list[$setname] as $cuid){
            if ($cuid > 0) {
                $deck[] = array(
                    (int)$cuid_cards[$cuid]->cuid,
                    (int)$cuid_cards[$cuid]->card_id,
                    (int)$cuid_cards[$cuid]->lv,
                    (int)$cuid_cards[$cuid]->slv,
                    (int)$cuid_cards[$cuid]->equip1,
                    (int)$cuid_cards[$cuid]->equip2,
                    (int)$cuid_cards[$cuid]->equip3,
                    (int)$cuid_cards[$cuid]->equip4,
                );
            }
            else {
                // 空
                $deck[] = array(0,0,0,0,0,0,0,0);
            }
        }

        foreach ($deck as $dcard) {
            $card = Card::get($dcard[1]);
            if ($card) {
                $card_hp = Card::getCardParam($dcard[2], $card->mlv, $card->pmhpa, $card->pmhpb, $card->pmhpc) + $dcard[4] * 10;
                $card_atk = Card::getCardParam($dcard[2], $card->mlv, $card->patka, $card->patkb, $card->patkc) + $dcard[5] * 5;
                $card_rec = Card::getCardParam($dcard[2], $card->mlv, $card->preca, $card->precb, $card->precc) + $dcard[6] * 3;

                $this->total_hp += $card_hp;
                $this->total_rec += $card_rec;
                $this->attr_atk[$card->attr] += $card_atk;
                if ($card->sattr >= 0) {
                    if ($card->attr == $card->sattr) {
                        $this->attr_atk[$card->attr] += ceil($card_atk / 10);
                    } else {
                        $this->attr_atk[$card->sattr] += ceil($card_atk / 3);
                    }
                }
            }
        }
        if ($this->total_hp < 0) {
            $this->total_hp = 1;
        }
        if ($this->total_rec < 0) {
            $this->total_rec = 0;
        }
        foreach ($this->attr_atk as $k => $v) {
            if ($v < 0) {
                $v = 0;
            }
            $this->total_atk += $v;
        }
        $this->total_pow = floor($this->total_hp / 10) + floor($this->total_atk / 5) + floor($this->total_rec / 3);

        return $this->total_pow;
    }

    public function getDecksInfo($user, $team_id, $pdo) {
        $user_deck = UserDeck::findBy(array('user_id'=>$user->id), $pdo);
        $decks = $user_deck->getUserDecks((int)$user->lv);
        $deck_list = array();
        foreach($decks as $deck){
            foreach($deck as $key => $value){
                $deck_list[$key] = $value;
            }
        }
        $setname = sprintf("set_%02s", $team_id);
        
        // deck内容を更新
        $cuids = array();
        foreach($deck_list[$setname] as $cuid){
            $cuids[] = $cuid;
        }
        $user_cards = UserCard::findByCuids($user->id, $cuids, $pdo);
        $cuid_cards = array();
        foreach($user_cards as $user_card){
            $cuid_cards[$user_card->cuid] = $user_card;
        }

        $deck = array();
        foreach($deck_list[$setname] as $cuid){
            if ($cuid > 0) {
                $deck[] = array(
                    (int)$cuid_cards[$cuid]->cuid,
                    (int)$cuid_cards[$cuid]->card_id,
                    (int)$cuid_cards[$cuid]->lv,
                    (int)$cuid_cards[$cuid]->slv,
                    (int)$cuid_cards[$cuid]->equip1,
                    (int)$cuid_cards[$cuid]->equip2,
                    (int)$cuid_cards[$cuid]->equip3,
                    (int)$cuid_cards[$cuid]->equip4,
                );
            }
            else {
                // 空
                $deck[] = array(0,0,0,0,0,0,0,0);
            }
        }

        foreach ($deck as $dcard) {
            $card = Card::get($dcard[1]);
            if ($card) {
                $card_hp = Card::getCardParam($dcard[2], $card->mlv, $card->pmhpa, $card->pmhpb, $card->pmhpc) + $dcard[4] * 10;
                $card_atk = Card::getCardParam($dcard[2], $card->mlv, $card->patka, $card->patkb, $card->patkc) + $dcard[5] * 5;
                $card_rec = Card::getCardParam($dcard[2], $card->mlv, $card->preca, $card->precb, $card->precc) + $dcard[6] * 3;

                $this->total_hp += $card_hp;
                $this->total_rec += $card_rec;
                $this->attr_atk[$card->attr] += $card_atk;
                if ($card->sattr >= 0) {
                    if ($card->attr == $card->sattr) {
                        $this->attr_atk[$card->attr] += ceil($card_atk / 10);
                    } else {
                        $this->attr_atk[$card->sattr] += ceil($card_atk / 3);
                    }
                }
            }
        }
        if ($this->total_hp < 0) {
            $this->total_hp = 1;
        }
        if ($this->total_rec < 0) {
            $this->total_rec = 0;
        }
        foreach ($this->attr_atk as $k => $v) {
            if ($v < 0) {
                $v = 0;
            }
            $this->total_atk += $v;
        }
        $this->total_pow = floor($this->total_hp / 10) + floor($this->total_atk / 5) + floor($this->total_rec / 3);

        return array($this->total_pow,$this->total_hp,$this->total_atk,$this->total_rec);
    }
}
