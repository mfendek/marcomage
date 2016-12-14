<?php
/**
 * Access - access rights configuration
 */

class Access
{
    /**
     * Check access for specified user type and access right
     * @param string $userType user type
     * @param string $accessRight access right
     * @return bool
     */
    public static function checkAccess($userType, $accessRight)
    {
        $accessList = self::listAccessRights();

        if (!in_array($userType, array_keys($accessList))) {
            return false;
        }

        $accessRights = array_merge($accessList['guest'], $accessList[$userType]);

        if (!isset($accessRights[$accessRight])) {
            return false;
        }

        return $accessRights[$accessRight];
    }

    /**
     * @return array
     */
    private static function listAccessRights()
    {
        return [
            'guest' => [
                'create_post' => false,
                'create_thread' => false,
                'del_all_post' => false,
                'del_all_thread' => false,
                'lock_thread' => false,
                'edit_own_post' => false,
                'edit_all_post' => false,
                'edit_all_thread' => false,
                'edit_own_thread' => false,
                'move_post' => false,
                'move_thread' => false,
                'chng_priority' => false,
                'messages' => false,
                'chat' => false,
                'create_card' => false,
                'edit_own_card' => false,
                'edit_all_card' => false,
                'delete_own_card' => false,
                'delete_all_card' => false,
                'send_challenges' => false,
                'accept_challenges' => false,
                'change_own_avatar' => false,
                'change_all_avatar' => false,
                'login' => false,
                'see_all_messages' => false,
                'system_notification' => false,
                'reset_exp' => false,
                'export_deck' => false,
                'change_rights' => false
            ],
            'banned' => [],
            'limited' => [
                'messages' => true,
                'accept_challenges' => true,
                'login' => true
            ],
            'squashed' => [
                'create_post' => true,
                'messages' => true,
                'chat' => true,
                'accept_challenges' => true,
                'login' => true
            ],
            'user' => [
                'create_post' => true,
                'create_thread' => true,
                'edit_own_post' => true,
                'edit_own_thread' => true,
                'messages' => true,
                'chat' => true,
                'create_card' => true,
                'edit_own_card' => true,
                'delete_own_card' => true,
                'send_challenges' => true,
                'accept_challenges' => true,
                'change_own_avatar' => true,
                'login' => true
            ],
            'supervisor' => [
                'create_post' => true,
                'create_thread' => true,
                'edit_own_post' => true,
                'edit_own_thread' => true,
                'messages' => true,
                'chat' => true,
                'create_card' => true,
                'edit_own_card' => true,
                'edit_all_card' => true,
                'delete_own_card' => true,
                'delete_all_card' => true,
                'send_challenges' => true,
                'accept_challenges' => true,
                'change_own_avatar' => true,
                'login' => true
            ],
            'moderator' => [
                'create_post' => true,
                'create_thread' => true,
                'del_all_post' => true,
                'del_all_thread' => true,
                'lock_thread' => true,
                'edit_own_post' => true,
                'edit_all_post' => true,
                'edit_all_thread' => true,
                'edit_own_thread' => true,
                'move_post' => true,
                'move_thread' => true,
                'chng_priority' => true,
                'messages' => true,
                'chat' => true,
                'create_card' => true,
                'edit_own_card' => true,
                'delete_own_card' => true,
                'send_challenges' => true,
                'accept_challenges' => true,
                'change_own_avatar' => true,
                'login' => true
            ],
            'admin' => [
                'create_post' => true,
                'create_thread' => true,
                'del_all_post' => true,
                'del_all_thread' => true,
                'lock_thread' => true,
                'edit_own_post' => true,
                'edit_all_post' => true,
                'edit_all_thread' => true,
                'edit_own_thread' => true,
                'move_post' => true,
                'move_thread' => true,
                'chng_priority' => true,
                'messages' => true,
                'chat' => true,
                'create_card' => true,
                'edit_own_card' => true,
                'edit_all_card' => true,
                'delete_own_card' => true,
                'delete_all_card' => true,
                'send_challenges' => true,
                'accept_challenges' => true,
                'change_own_avatar' => true,
                'change_all_avatar' => true,
                'login' => true,
                'see_all_messages' => true,
                'system_notification' => true,
                'reset_exp' => true,
                'export_deck' => true,
                'change_rights' => true
            ]
        ];
    }
}
