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
 * @property Discussion $discussion
 * @property User|null $oldUser Previous discussion author if any
 * @property User|null $actor Actor who performed the change
 */
class DiscussionUserChanged
{
    /** @var Discussion */
    public $discussion;
    /** @var User|null */
    public $oldUser;
    /** @var User|null */
    public $actor;

    public function __construct(Discussion $discussion, User $oldUser = null, User $actor = null)
    {
        $this->discussion = $discussion;
        $this->oldUser = $oldUser;
        $this->actor = $actor;
    }
}
