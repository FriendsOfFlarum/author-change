<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange\Tests\fixtures;

use Carbon\Carbon;
use Flarum\Group\Group;

/**
 * Shared database seed for author-change integration tests.
 *
 * User layout:
 *  - id=1 admin (open gate, all permissions)
 *  - id=2 normal member (RetrievesAuthorizedUsers::normalUser, no permissions)
 *  - id=3 moderator WITH both author-change permissions
 *  - id=4 moderator WITHOUT author-change permissions (member of a bare group)
 *  - id=5 the "new author" a change can point discussions/posts to
 *
 * Content layout:
 *  - discussion 1, authored by user 2, first post 1 (also user 2)
 *  - post 2 in discussion 1, authored by user 2, number 2 (a reply)
 */
trait AuthorChangeTrait
{
    protected function authorChangeUsers(): array
    {
        // BCrypt hash for "too-obscure"
        $password = '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim';

        return [
            $this->normalUser(),
            ['id' => 3, 'username' => 'modWithPermission', 'password' => $password, 'email' => 'mod-perm@machine.local', 'is_email_confirmed' => 1, 'last_seen_at' => Carbon::now()->subSecond()],
            ['id' => 4, 'username' => 'modNoPermission', 'password' => $password, 'email' => 'mod-noperm@machine.local', 'is_email_confirmed' => 1, 'last_seen_at' => Carbon::now()->subSecond()],
            ['id' => 5, 'username' => 'newAuthor', 'password' => $password, 'email' => 'new-author@machine.local', 'is_email_confirmed' => 1, 'last_seen_at' => Carbon::now()->subSecond()],
        ];
    }

    protected function authorChangeGroups(): array
    {
        return [
            // A bare group with no author-change permission for the unprivileged moderator
            ['id' => 10, 'name_singular' => 'Bare', 'name_plural' => 'Bare', 'color' => null, 'icon' => null],
        ];
    }

    protected function authorChangeGroupUsers(): array
    {
        return [
            ['group_id' => Group::MODERATOR_ID, 'user_id' => 3],
            ['group_id' => 10, 'user_id' => 4],
        ];
    }

    protected function authorChangePermissions(): array
    {
        return [
            ['group_id' => Group::MODERATOR_ID, 'permission' => 'fof-author-change.edit-user'],
            ['group_id' => Group::MODERATOR_ID, 'permission' => 'fof-author-change.edit-date'],
            // Moderators must be able to see the user list to search for a new author
            ['group_id' => Group::MODERATOR_ID, 'permission' => 'viewUserList'],
        ];
    }

    protected function authorChangeDiscussions(): array
    {
        return [
            ['id' => 1, 'title' => 'Author change discussion', 'created_at' => Carbon::createFromDate(2020, 1, 1)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2020, 1, 1)->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 2, 'is_private' => 0],
        ];
    }

    protected function authorChangePosts(): array
    {
        return [
            ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::createFromDate(2020, 1, 1)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first post</p></t>'],
            ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'created_at' => Carbon::createFromDate(2020, 1, 2)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a reply</p></t>'],
        ];
    }
}
