<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

/**
 * The author of a discussion was modified.
 *
 * @property Discussion $discussion
 * @property User|null  $oldUser    Previous discussion author if any
 * @property User|null  $actor      Actor who performed the change
 */
class DiscussionUserChanged
{
    public function __construct(public Discussion $discussion, public ?User $oldUser = null, public ?User $actor = null)
    {
    }
}
