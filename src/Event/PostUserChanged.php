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

use Flarum\Post\Post;
use Flarum\User\User;

/**
 * The author of a post was modified.
 * @property Post $post
 * @property User|null $oldUser Previous post author if any
 * @property User|null $actor Actor who performed the change
 */
class PostUserChanged
{
    public function __construct(public Post $post, public ?User $oldUser = null, public ?User $actor = null)
    {
    }
}
