<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\AuthorChange\Tests\fixtures\AuthorChangeTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * Permission enforcement for author/date changes on discussions and posts.
 *
 * The extension listens on Discussion\Event\Saving and Post\Event\Saving, so
 * changes flow through the core PATCH /api/discussions/{id} and
 * PATCH /api/posts/{id} endpoints. Author changes require the
 * fof-author-change.edit-user permission; date changes require
 * fof-author-change.edit-date.
 */
class PermissionTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use AuthorChangeTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class        => $this->authorChangeUsers(),
            Group::class       => $this->authorChangeGroups(),
            'group_user'       => $this->authorChangeGroupUsers(),
            'group_permission' => $this->authorChangePermissions(),
            Discussion::class  => $this->authorChangeDiscussions(),
            Post::class        => $this->authorChangePosts(),
        ]);

        $this->extension('fof-author-change');

        // Exempt the discussions route from CSRF so the guest request reaches the
        // actual authorization check (returning 401/403) rather than being stopped
        // at the CSRF layer with a 400.
        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('discussions.update')
        );
    }

    #[Test]
    public function moderator_with_permission_can_change_discussion_author()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'relationships' => [
                            'user' => ['data' => ['type' => 'users', 'id' => '5']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function moderator_with_permission_can_change_post_author()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'relationships' => [
                            'user' => ['data' => ['type' => 'users', 'id' => '5']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function moderator_without_permission_cannot_change_discussion_author()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 4,
                'json'            => [
                    'data' => [
                        'relationships' => [
                            'user' => ['data' => ['type' => 'users', 'id' => '5']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());

        // Author must be unchanged
        $this->assertEquals(2, $this->getDiscussionUserId(1));
    }

    #[Test]
    public function normal_user_cannot_change_post_author()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/2', [
                'authenticatedAs' => 2,
                'json'            => [
                    'data' => [
                        'relationships' => [
                            'user' => ['data' => ['type' => 'users', 'id' => '5']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(2, $this->getPostUserId(2));
    }

    #[Test]
    public function moderator_without_permission_cannot_change_post_date()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/2', [
                'authenticatedAs' => 4,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'createdAt' => '2021-06-15T10:00:00+00:00',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function guest_cannot_change_author()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'json' => [
                    'data' => [
                        'relationships' => [
                            'user' => ['data' => ['type' => 'users', 'id' => '5']],
                        ],
                    ],
                ],
            ])
        );

        // In Flarum 2.x the core Update endpoint runs assertRegistered() at the
        // visibility layer (before the extension's permission check), so a guest
        // is rejected with 401 NotAuthenticated. Either way the author is denied.
        $this->assertEquals(401, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(2, $this->getDiscussionUserId(1));
    }

    private function getDiscussionUserId(int $id): ?int
    {
        $value = $this->database()->table('discussions')->where('id', $id)->value('user_id');

        return $value === null ? null : (int) $value;
    }

    private function getPostUserId(int $id): ?int
    {
        $value = $this->database()->table('posts')->where('id', $id)->value('user_id');

        return $value === null ? null : (int) $value;
    }
}
