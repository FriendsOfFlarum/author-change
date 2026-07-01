<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

/**
 * Migrate permission grants stored under the original clarkwinkelmann/flarum-ext-author-change
 * permission keys to the new fof/author-change keys so existing installs keep their grants.
 */
return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        $renames = [
            'clarkwinkelmann-author-change.edit-user' => 'fof-author-change.edit-user',
            'clarkwinkelmann-author-change.edit-date' => 'fof-author-change.edit-date',
        ];

        foreach ($renames as $old => $new) {
            $db->table('group_permission')
                ->where('permission', $old)
                ->update(['permission' => $new]);
        }
    },
    'down' => function (Builder $schema) {
        $db = $schema->getConnection();

        $renames = [
            'fof-author-change.edit-user' => 'clarkwinkelmann-author-change.edit-user',
            'fof-author-change.edit-date' => 'clarkwinkelmann-author-change.edit-date',
        ];

        foreach ($renames as $old => $new) {
            $db->table('group_permission')
                ->where('permission', $old)
                ->update(['permission' => $new]);
        }
    },
];
