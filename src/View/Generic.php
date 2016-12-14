<?php
/**
 * Generic - generic templates
 */

namespace View;

use ArcomageException as Exception;
use Util\Encode;

class Generic extends TemplateDataAbstract
{
    /**
     * @return Result
     * @throws Exception
     */
    protected function layout()
    {
        $dataMain = $dataNav = array();
        $player = $this->getCurrentPlayer();

        $config = $this->getDic()->config();
        $newUser = $this->getDic()->newUserFlag();
        $current = $this->getDic()->currentSection();
        $newLevelGained = $this->getDic()->levelGained();
        $error = $this->getDic()->error();
        $warning = $this->getDic()->warning();
        $info = $this->getDic()->info();

        // main template data
        $setting = $this->getCurrentSettings();

        $dataMain['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';
        $dataMain['skin'] = $setting->getSetting('Skin');
        $dataMain['new_user'] = ($newUser) ? 'yes' : 'no';
        $dataMain['include_captcha'] = ($current == 'Registration' && $config['catpcha']['enabled']) ? 'yes' : 'no';
        $dataMain['jquery_version'] = $config['jquery']['version'];
        $dataMain['jquery_ui_version'] = $config['jquery']['ui_version'];
        $dataMain['bootstrap_version'] = $config['bootstrap']['version'];
        $dataMain['cc_version'] = $config['client_cache_version'];
        $dataNav['google_plus'] = $config['external_links']['google_plus'];
        $dataNav['facebook'] = $config['external_links']['facebook'];

        // navigation bar params
        $dataNav['error_msg'] = Encode::htmlEncode($error);
        $dataNav['warning_msg'] = Encode::htmlEncode($warning);
        $dataNav['info_msg'] = Encode::htmlEncode($info);
        $dataNav['current'] = $current;

        // session information, if necessary
        if ($this->isSession() && !$player->hasCookies()) {
            $dataMain['username'] = $player->getUsername();
            $dataMain['session_id'] = $player->getSessionId();
        }

        // player is logged in
        if ($this->isSession()) {
            $dbEntityConcept = $this->dbEntity()->concept();
            $dbEntityGame = $this->dbEntity()->game();
            $dbEntityMessage = $this->dbEntity()->message();
            $dbEntityForumPost = $this->dbEntity()->forumPost();

            // inner navbar params
            $dataMain['player_name'] = $dataNav['player_name'] = $player->getUsername();
            $dataMain['new_level_gained'] = $newLevelGained;

            // list cards associated with newly gained level
            if ($newLevelGained > 0) {
                $defEntityCard = $this->defEntity()->card();

                // load card data
                $dataMain['new_cards'] = $defEntityCard->getData($defEntityCard->getList([
                    'level' => $newLevelGained,
                    'level_op' => '=',
                    'forbidden' => false,
                ]));
                $dataMain['card_old_look'] = $setting->getSetting('OldCardLook');
                $dataMain['card_insignias'] = $setting->getSetting('Insignias');
                $dataMain['card_foils'] = $setting->getSetting('FoilCards');
            }

            // fetch player's score data
            $dbEntityScore = $this->dbEntity()->score();
            $score = $dbEntityScore->getScoreAsserted($player->getUsername());

            $dataNav['level'] = $dataMain['level'] = $score->getLevel();
            $dataNav['exp'] = $score->getData('Exp');
            $dataNav['next_level'] = $score->nextLevel();
            $dataNav['exp_bar'] = $score->expBar();

            // menu bar notifications (depends on current user's game settings)
            $newPosts = $newConcepts = false;
            $forumNotification = ($setting->getSetting('Forum_notification') == 'yes');
            $conceptsNotification = ($setting->getSetting('Concepts_notification') == 'yes');

            // new forum posts notification
            if ($forumNotification) {
                $result = $dbEntityForumPost->newPosts($player->getNotification());
                if ($result->isError()) {
                    throw new Exception('Failed to load posts notification');
                }

                $newPosts = $result->isSuccess();
            }
            $dataNav['forum_notice'] = ($forumNotification && $newPosts) ? 'yes' : 'no';

            // new concepts notification
            if ($conceptsNotification) {
                $result = $dbEntityConcept->newConcepts($player->getNotification());
                if ($result->isError()) {
                    throw new Exception('Failed to load concepts notification');
                }

                $newConcepts = $result->isSuccess();
            }
            $dataNav['concept_notice'] = ($conceptsNotification && $newConcepts) ? 'yes' : 'no';

            // incoming challenges notification
            $result = $dbEntityGame->listChallengesTo($player->getUsername());
            if ($result->isError()) {
                throw new Exception('Failed to list challenges to');
            }
            $challengesTo = array();
            foreach ($result->data() as $resultData) {
                $challengesTo[] = $resultData['Player1'];
            }

            // unread messages notification
            $result = $dbEntityMessage->countUnreadMessages($player->getUsername());
            if ($result->isErrorOrNoEffect()) {
                throw new Exception('Failed to count unread messages');
            }
            $unreadMessages = $result[0]['CountUnread'];

            $dataNav['message_notice'] = (count($challengesTo) + $unreadMessages > 0) ? 'yes' : 'no';

            // active games notification
            $result = $dbEntityGame->countCurrentGames($player->getUsername());
            if ($result->isErrorOrNoEffect()) {
                throw new Exception('Failed to count current games');
            }
            $currentGames = $result[0]['count'];

            $dataMain['current_games'] = $currentGames;
            $dataNav['game_notice'] = ($currentGames > 0) ? 'yes' : 'no';
        }

        return new Result([
            'main' => $dataMain,
            'navbar' => $dataNav,
        ]);
    }
}
