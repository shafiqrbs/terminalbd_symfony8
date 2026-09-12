<?php

namespace App\User\Permission\Provider;

/**
 * Security Provider
 *
 * @author Mohammad Emran Hasan <phpfour@gmail.com>
 */
class SecurityPermissionProvider implements ProviderInterface
{
    public function getPermissions()
    {
        return [
            'ROLE_VENDOR' => [],
            'ROLE_SYNDICATE' => [],
            'ROLE_ADMIN' => [],
            'ROLE_MANAGE_USERS'      => ['ROLE_MANAGE_USERS_VIEW', 'ROLE_MANAGE_ADD', 'ROLE_MANAGE_UPDATE', 'ROLE_MANAGE_DELETE', 'ROLE_MANAGE_SUSPEND'],
            'ROLE_MANAGE_GROUPS'     => ['ROLE_MANAGE_GROUPS_VIEW', 'ROLE_GROUP_ADD', 'ROLE_GROUP_UPDATE', 'ROLE_GROUP_DELETE'],
            'ROLE_MANAGE_PERMISSION' => ['ROLE_MANAGE_USERS', 'ROLE_MANAGE_GROUPS'],
            'ROLE_MANAGE_SECURITY'   => [
                'ROLE_MANAGE_USERS',
                'ROLE_MANAGE_GROUPS',
                'ROLE_MANAGE_PERMISSION',
                'ROLE_SYNDICATE',
                'ROLE_VENDOR'
            ]
        ];
    }
}