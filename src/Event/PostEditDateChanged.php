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
 * The edit date of a post was modified.
 * @property Post $post
 * @property Carbon|null $oldDate Previous edit date
 * @property User|null $actor Actor who performed the change
 */
class PostEditDateChanged
{
    /** @var Post */
    public $post;
    /** @var Carbon|null */
    public $oldDate;
    /** @var User|null */
    public $actor;

    public function __construct(Post $post, Carbon $oldDate = null, User $actor = null)
    {
        $this->post = $post;
        $this->oldDate = $oldDate;
        $this->actor = $actor;
    }
}
