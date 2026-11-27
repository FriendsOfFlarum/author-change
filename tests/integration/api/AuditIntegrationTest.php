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

use Flarum\Audit\AuditLogger;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\AuthorChange\Tests\fixtures\AuthorChangeTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * Verifies the optional flarum/audit integration declared in extend.php.
 *
 * The integration is gated behind (new Conditional())->whenExtensionEnabled('flarum-audit'),
 * so it only records audit log entries when the audit extension is enabled alongside this one.
 */
class AuditIntegrationTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use AuthorChangeTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Suppress the "audit self-enabled" log entry so the table only holds
        // entries produced by the changes made in each test.
        AuditLogger::$testMode = true;

        $this->prepareDatabase([
            User::class        => $this->authorChangeUsers(),
            Group::class       => $this->authorChangeGroups(),
            'group_user'       => $this->authorChangeGroupUsers(),
            'group_permission' => $this->authorChangePermissions(),
            Discussion::class  => $this->authorChangeDiscussions(),
            Post::class        => $this->authorChangePosts(),
        ]);

        $this->extension('flarum-audit', 'fof-author-change');
    }

    #[Test]
    public function changing_discussion_author_writes_an_audit_log_entry()
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

        $log = $this->database()->table('audit_log')
            ->where('action', 'discussion.user_changed')
            ->first();

        $this->assertNotNull($log, 'Expected an audit log entry for the author change.');

        $payload = json_decode($log->payload, true);
        $this->assertEquals(1, $payload['discussion_id']);
        $this->assertEquals(2, $payload['old_user_id']);
        $this->assertEquals(5, $payload['new_user_id']);
    }

    #[Test]
    public function removing_the_post_author_writes_an_audit_log_entry()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'relationships' => [
                            'user' => ['data' => null],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $log = $this->database()->table('audit_log')
            ->where('action', 'post.user_changed')
            ->first();

        $this->assertNotNull($log, 'Expected an audit log entry for the author removal.');

        $payload = json_decode($log->payload, true);
        $this->assertEquals(2, $payload['old_user_id']);
        $this->assertNull($payload['new_user_id']);
    }

    #[Test]
    public function changing_post_creation_date_writes_an_audit_log_entry()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'createdAt' => '2016-03-10T08:00:00+00:00',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $log = $this->database()->table('audit_log')
            ->where('action', 'post.create_date_changed')
            ->first();

        $this->assertNotNull($log, 'Expected an audit log entry for the post date change.');

        $payload = json_decode($log->payload, true);
        $this->assertEquals(1, $payload['post_id']);
    }

    #[Test]
    public function no_author_change_audit_entry_when_only_the_title_changes()
    {
        // A title-only edit must not raise any of the author-change events, so
        // this extension logs nothing (audit's own core integration still records
        // the unrelated `discussion.renamed` action, which we ignore here).
        $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'title' => 'A brand new title',
                        ],
                    ],
                ],
            ])
        );

        $authorChangeActions = [
            'discussion.create_date_changed',
            'discussion.user_changed',
            'post.create_date_changed',
            'post.edit_date_changed',
            'post.user_changed',
        ];

        $this->assertEquals(
            0,
            $this->database()->table('audit_log')->whereIn('action', $authorChangeActions)->count()
        );
    }

    protected function patch(string $path, array $attributes): void
    {
        $response = $this->send(
            $this->request('PATCH', "/api/$path", [
                'authenticatedAs' => 3,
                'json'            => ['data' => ['attributes' => $attributes]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function lastPayload(string $action): array
    {
        $log = $this->database()->table('audit_log')->where('action', $action)->orderByDesc('id')->first();
        $this->assertNotNull($log, "Expected an audit log entry for $action.");

        return json_decode($log->payload, true);
    }

    #[Test]
    public function date_changes_record_the_previous_date()
    {
        $this->patch('posts/1', ['createdAt' => '2016-03-10T08:00:00+00:00']);
        $payload = $this->lastPayload('post.create_date_changed');
        $this->assertStringStartsWith('2020-01-01', $payload['old_date']);
        $this->assertStringStartsWith('2016-03-10T08:00:00', $payload['new_date']);

        $this->patch('discussions/1', ['createdAt' => '2016-03-10T08:00:00+00:00']);
        $payload = $this->lastPayload('discussion.create_date_changed');
        $this->assertStringStartsWith('2020-01-01', $payload['old_date']);
        $this->assertStringStartsWith('2016-03-10T08:00:00', $payload['new_date']);

        $this->patch('posts/2', ['editedAt' => '2021-01-01T00:00:00+00:00']);
        $this->patch('posts/2', ['editedAt' => '2022-01-01T00:00:00+00:00']);
        $payload = $this->lastPayload('post.edit_date_changed');
        $this->assertStringStartsWith('2021-01-01T00:00:00', $payload['old_date']);
        $this->assertStringStartsWith('2022-01-01T00:00:00', $payload['new_date']);
    }
}
