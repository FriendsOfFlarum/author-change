<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange\Listeners;

use Flarum\Post\Event\Saving;

class SavePost extends AbstractSaveAuthor
{
    public function handle(Saving $event): void
    {
        $this->saveAuthor($event->post, $event->actor, $event->data);
    }
}
