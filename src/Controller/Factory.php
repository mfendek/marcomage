<?php
/**
 * Controller factory
 * Provides resource caching and sugar functions
 */

namespace Controller;

use ArcomageException as Exception;
use Util\Rename;

class Factory extends \FactoryAbstract
{
    /**
     * @return array
     */
    private static function controllers()
    {
        return [
            'web' => [
                'card' => [
                    'cards_apply_filters',
                    'cards_select_page',
                    'find_card_thread',
                    'buy_foil_card',
                ],
                'challenge' => [
                    'accept_challenge',
                    'reject_challenge',
                    'send_challenge',
                    'withdraw_challenge',
                ],
                'concept' => [
                    'concepts_order_asc',
                    'concepts_order_desc',
                    'concepts_apply_filters',
                    'show_my_concepts',
                    'concepts_select_page',
                    'new_concept',
                    'create_concept',
                    'edit_concept',
                    'save_concept',
                    'save_concept_special',
                    'upload_concept_image',
                    'clear_concept_image',
                    'delete_concept',
                    'delete_concept_confirm',
                    'find_concept_thread',
                ],
                'replay' => [
                    'replays_order_asc',
                    'replays_order_desc',
                    'replays_apply_filters',
                    'show_my_replays',
                    'replays_select_page',
                    'find_replay_thread',
                ],
                'deck' => [
                    'add_card',
                    'return_card',
                    'set_tokens',
                    'auto_tokens',
                    'save_deck_note',
                    'save_deck_note_return',
                    'clear_deck_note',
                    'clear_deck_note_return',
                    'deck_apply_filters',
                    'reset_deck_prepare',
                    'reset_deck_confirm',
                    'reset_stats_prepare',
                    'reset_stats_confirm',
                    'rename_deck',
                    'export_deck',
                    'import_deck',
                    'decks_shared_filter',
                    'decks_order_asc',
                    'decks_order_desc',
                    'decks_select_page',
                    'import_shared_deck',
                    'share_deck',
                    'unshare_deck',
                    'card_pool_switch',
                    'find_deck_thread',
                ],
                'forum' => [
                    'new_thread',
                    'create_thread',
                    'forum_search',
                    'thread_lock',
                    'thread_unlock',
                    'thread_delete',
                    'thread_delete_confirm',
                    'new_post',
                    'create_post',
                    'quote_post',
                    'edit_thread',
                    'modify_thread',
                    'move_thread',
                    'edit_post',
                    'modify_post',
                    'delete_post',
                    'delete_post_confirm',
                    'move_post',
                ],
                'game' => [
                    'next_game',
                    'save_game_note',
                    'save_game_note_return',
                    'clear_game_note',
                    'clear_game_note_return',
                    'send_message',
                    'play_card',
                    'discard_card',
                    'preview_card',
                    'ai_move',
                    'custom_ai_move',
                    'finish_move',
                    'initiate_surrender',
                    'cancel_surrender',
                    'reject_surrender',
                    'accept_surrender',
                    'abort_game',
                    'finish_game',
                    'leave_game',
                    'host_game',
                    'unhost_game',
                    'join_game',
                    'ai_game',
                    'ai_challenge',
                    'quick_game',
                    'filter_hosted_games',
                    'put_card',
                    'change_attribute',
                    'change_game_mode',
                ],
                'message' => [
                    'message_details',
                    'message_retrieve',
                    'message_delete',
                    'message_delete_confirm',
                    'message_cancel',
                    'message_send',
                    'message_create',
                    'system_notification',
                    'messages_order_asc',
                    'messages_order_desc',
                    'messages_apply_filters',
                    'messages_select_page',
                    'delete_mass_messages',
                ],
                'misc' => [
                    'back_to_top',
                    'reset_notification',
                    'card_statistics',
                    'other_statistics',
                    'test_cards',
                    'test_keywords',
                ],
                'player' => [
                    'change_access',
                    'rename_player',
                    'delete_player',
                    'reset_exp',
                    'reset_avatar_remote',
                    'export_deck_remote',
                    'add_gold',
                    'reset_password',
                    'players_apply_filters',
                    'players_select_page',
                ],
                'setting' => [
                    'save_settings',
                    'upload_avatar_image',
                    'reset_avatar',
                    'change_password',
                    'buy_item',
                    'skip_tutorial',
                ]
            ],
            'ajax' => [
                'ajax' => [
                    'take_card',
                    'remove_card',
                    'preview_card',
                    'save_game_note',
                    'clear_game_note',
                    'save_deck_note',
                    'clear_deck_note',
                    'send_chat_message',
                    'reset_chat_notification',
                    'card_lookup',
                    'active_games',
                ],
            ],
            'scripts' => [
                'scripts' => [
                    'init_game_auto_increment',
                    'players_cleanup',
                    'r2636',
                ],
            ],
        ];
    }

    /**
     * Create resource of specified name
     * @param string $resourceName
     */
    protected function createResource($resourceName)
    {
        // determine config key name
        $resourceKey = strtolower($resourceName);

        // add class name prefix
        $className = '\Controller\\'. Rename::underscoreToClassName($resourceName);

        $service = new $className($this->getDic());

        // store service to resource cache for future use
        $this->resources[$resourceKey] = $service;
    }

    /**
     * @param string $context
     * @throws Exception
     * @return Result
     */
    public function executeControllerAction($context)
    {
        $request = $this->getDic()->request();
        $controllers = self::controllers();

        // validate controller context
        if (empty($controllers[$context])) {
            throw new Exception('context not found '.$context);
        }

        // validate controller action
        $controller = $action = '';
        foreach ($controllers[$context] as $controllerName => $actionList) {
            foreach ($request as $key => $value) {
                if (in_array($key, $actionList)) {
                    $controller = $controllerName;
                    $action = $key;
                    break;
                }
            }
        }

        // controller action not found
        if (empty($controller) || empty($action)) {
            throw new Exception('controller action is not white-listed', Exception::WARNING);
        }

        /* @var ControllerAbstract $controller */
        $controller = $this->loadResource($controller);

        return $controller->executeAction(Rename::inputToActionName($action));
    }
}
