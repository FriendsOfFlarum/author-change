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

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\AuthorChange\Tests\fixtures\AuthorChangeTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * Asserts that a moderator holding the author-change permissions actually
 * changes the author and/or dates of discussions and posts correctly.
 */
class ChangeAuthorAndDateTest extends TestCase
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
    }

    #[Test]
    public function changing_discussion_author_updates_the_record()
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

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('5', $body['data']['relationships']['user']['data']['id']);

        $this->assertEquals(5, (int) $this->database()->table('discussions')->where('id', 1)->value('user_id'));
    }

    #[Test]
    public function changing_post_author_updates_the_record()
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

        $this->assertEquals(5, (int) $this->database()->table('posts')->where('id', 2)->value('user_id'));
    }

    #[Test]
    public function removing_the_author_dissociates_the_user()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        // A null (but present) relationship dissociates the author.
                        // In Flarum 2.x's JSON:API layer a to-one relationship is cleared
                        // with `data: null`; the listener triggers dissociation on
                        // empty($data['relationships']['user']['data']).
                        'relationships' => [
                            'user' => ['data' => null],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $this->assertNull($this->database()->table('posts')->where('id', 2)->value('user_id'));
    }

    #[Test]
    public function changing_discussion_creation_date_updates_the_record()
    {
        $newDate = '2015-08-20T14:30:00+00:00';

        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'createdAt' => $newDate,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $stored = $this->database()->table('discussions')->where('id', 1)->value('created_at');
        $this->assertEquals(
            Carbon::parse($newDate)->toDateTimeString(),
            Carbon::parse($stored)->toDateTimeString()
        );
    }

    #[Test]
    public function changing_post_creation_date_updates_the_record()
    {
        $newDate = '2016-03-10T08:00:00+00:00';

        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'createdAt' => $newDate,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $stored = $this->database()->table('posts')->where('id', 1)->value('created_at');
        $this->assertEquals(
            Carbon::parse($newDate)->toDateTimeString(),
            Carbon::parse($stored)->toDateTimeString()
        );
    }

    #[Test]
    public function changing_post_edited_date_updates_the_record()
    {
        $newDate = '2017-11-05T19:45:00+00:00';

        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'editedAt' => $newDate,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $stored = $this->database()->table('posts')->where('id', 1)->value('edited_at');
        $this->assertNotNull($stored);
        $this->assertEquals(
            Carbon::parse($newDate)->toDateTimeString(),
            Carbon::parse($stored)->toDateTimeString()
        );
    }

    #[Test]
    public function can_change_author_and_date_together()
    {
        $newDate = '2018-01-01T00:00:00+00:00';

        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'createdAt' => $newDate,
                        ],
                        'relationships' => [
                            'user' => ['data' => ['type' => 'users', 'id' => '5']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $row = $this->database()->table('discussions')->where('id', 1)->first();
        $this->assertEquals(5, (int) $row->user_id);
        $this->assertEquals(
            Carbon::parse($newDate)->toDateTimeString(),
            Carbon::parse($row->created_at)->toDateTimeString()
        );
    }

    #[Test]
    public function invalid_date_is_rejected()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'createdAt' => 'not-a-real-date',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
    }
}
