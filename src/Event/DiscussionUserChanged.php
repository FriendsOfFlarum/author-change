<?php

namespace ClarkWinkelmann\AuthorChange\Event;

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
    public function __construct(public Discussion $discussion, public ?User $oldUser = null, public ?User $actor = null)
    {
    }
}
