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

use Carbon\Carbon;
use Flarum\Post\Post;
use Flarum\User\User;

/**
 * The creation date of a post was modified.
 * @property Post $post
 * @property Carbon|null $oldDate Previous creation date
 * @property User|null $actor Actor who performed the change
 */
class PostCreateDateChanged
{
    public function __construct(public Post $post, public Carbon $oldDate, public ?User $actor = null)
    {
    }
}
