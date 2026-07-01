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
use Flarum\Discussion\Discussion;
use Flarum\User\User;

/**
 * The creation date of a discussion was modified.
 *
 * @property Discussion  $discussion
 * @property Carbon|null $oldDate    Previous creation date
 * @property User|null   $actor      Actor who performed the change
 */
class DiscussionCreateDateChanged
{
    /** @var Discussion */
    public $discussion;
    /** @var Carbon|null */
    public $oldDate;
    /** @var User|null */
    public $actor;

    public function __construct(Discussion $discussion, Carbon $oldDate, ?User $actor = null)
    {
        $this->discussion = $discussion;
        $this->oldDate = $oldDate;
        $this->actor = $actor;
    }
}
