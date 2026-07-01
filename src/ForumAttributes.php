<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange;

use Flarum\Api\Serializer\ForumSerializer;

class ForumAttributes
{
    public function __invoke(ForumSerializer $serializer): array
    {
        return [
            'fofAuthorChangeCanEditUser' => $serializer->getActor()->can('fof-author-change.edit-user'),
            'fofAuthorChangeCanEditDate' => $serializer->getActor()->can('fof-author-change.edit-date'),
        ];
    }
}
