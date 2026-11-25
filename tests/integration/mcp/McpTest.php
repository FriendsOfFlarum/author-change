<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange\Tests\integration\mcp;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Mcp\Testing\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use FoF\AuthorChange\Tests\fixtures\AuthorChangeTrait;
use PHPUnit\Framework\Attributes\Test;

class McpTest extends McpTestCase
{
    use AuthorChangeTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mcp', 'fof-author-change');

        $this->prepareDatabase([
            User::class => [
                ...$this->authorChangeUsers(),
                ['id' => 6, 'username' => 'authorOnly', 'email' => 'author-only@machine.local', 'is_email_confirmed' => 1],
                ['id' => 7, 'username' => 'dateOnly', 'email' => 'date-only@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ...$this->authorChangeGroups(),
                ['id' => 11, 'name_singular' => 'Authors', 'name_plural' => 'Authors'],
                ['id' => 12, 'name_singular' => 'Dates', 'name_plural' => 'Dates'],
            ],
            'group_user' => [
                ...$this->authorChangeGroupUsers(),
                ['group_id' => 11, 'user_id' => 6],
                ['group_id' => 12, 'user_id' => 7],
            ],
            'group_permission' => [
                ...$this->authorChangePermissions(),
                ['group_id' => 11, 'permission' => 'fof-author-change.edit-user'],
                ['group_id' => 12, 'permission' => 'fof-author-change.edit-date'],
            ],
            Discussion::class => $this->authorChangeDiscussions(),
            Post::class       => $this->authorChangePosts(),
        ]);
    }

    /**
     * @return array{instructions: string, abilities: list<string>}
     */
    protected function toldTo(int $userId): array
    {
        $result = $this->rpcResult($this->modern('resources/read', ['uri' => 'flarum://forum'], $this->bearer($this->developerToken($userId)), 'flarum://forum'));
        $overview = json_decode($result['contents'][0]['text'], true);

        return [
            'instructions' => array_column($overview['extensions'], 'instructions', 'id')['fof-author-change'] ?? '',
            'abilities'    => array_column($overview['you']['can'], 'ability'),
        ];
    }

    #[Test]
    public function a_user_with_both_permissions_is_told_about_authors_and_dates(): void
    {
        $told = $this->toldTo(3);

        $this->assertStringContainsString('relationships {"user": "<user ID>"}', $told['instructions']);
        $this->assertStringContainsString('{"createdAt": "<ISO 8601 date>"}', $told['instructions']);
        $this->assertStringContainsString('{"editedAt": "<ISO 8601 date>"}', $told['instructions']);
        $this->assertContains('fof-author-change.edit-user', $told['abilities']);
        $this->assertContains('fof-author-change.edit-date', $told['abilities']);
    }

    #[Test]
    public function the_author_permission_alone_is_told_about_authors_only(): void
    {
        $told = $this->toldTo(6);

        $this->assertStringContainsString('relationships {"user"', $told['instructions']);
        $this->assertStringNotContainsString('createdAt', $told['instructions']);
        $this->assertContains('fof-author-change.edit-user', $told['abilities']);
        $this->assertNotContains('fof-author-change.edit-date', $told['abilities']);
    }

    #[Test]
    public function the_date_permission_alone_is_told_about_dates_only(): void
    {
        $told = $this->toldTo(7);

        $this->assertStringContainsString('{"createdAt": "<ISO 8601 date>"}', $told['instructions']);
        $this->assertStringNotContainsString('author', $told['instructions']);
        $this->assertContains('fof-author-change.edit-date', $told['abilities']);
        $this->assertNotContains('fof-author-change.edit-user', $told['abilities']);
    }

    #[Test]
    public function others_are_not_told(): void
    {
        $told = $this->toldTo(2);

        $this->assertSame('', $told['instructions']);
        $this->assertNotContains('fof-author-change.edit-user', $told['abilities']);
        $this->assertNotContains('fof-author-change.edit-date', $told['abilities']);
    }

    #[Test]
    public function the_audit_log_is_not_mentioned_without_flarum_audit(): void
    {
        $this->assertStringNotContainsString('audit log', $this->toldTo(3)['instructions']);
    }

    #[Test]
    public function the_audit_log_is_mentioned_with_flarum_audit(): void
    {
        $this->extension('flarum-mcp', 'flarum-audit', 'fof-author-change');

        $instructions = $this->toldTo(7)['instructions'];

        $this->assertStringContainsString('{"createdAt": "<ISO 8601 date>"}', $instructions);
        $this->assertStringContainsString('recorded in the audit log', $instructions);
    }

    #[Test]
    public function others_are_not_told_about_the_audit_log(): void
    {
        $this->extension('flarum-mcp', 'flarum-audit', 'fof-author-change');

        $this->assertSame('', $this->toldTo(2)['instructions']);
    }

    #[Test]
    public function a_permitted_user_changes_a_post_author_and_dates(): void
    {
        $this->toolOutput($this->callTool('update_resource', [
            'type'          => 'posts',
            'id'            => '2',
            'attributes'    => ['createdAt' => '2021-03-04T05:06:00+00:00', 'editedAt' => '2021-03-05T05:06:00+00:00'],
            'relationships' => ['user' => '5'],
        ], $this->bearer($this->developerToken(3))));

        $post = Post::query()->findOrFail(2);
        $this->assertSame(5, $post->user_id);
        $this->assertSame('2021-03-04 05:06:00', Carbon::parse($post->created_at)->toDateTimeString());
        $this->assertSame('2021-03-05 05:06:00', Carbon::parse($post->edited_at)->toDateTimeString());
    }

    #[Test]
    public function a_permitted_user_changes_a_discussion_author_and_date(): void
    {
        $this->toolOutput($this->callTool('update_resource', [
            'type'          => 'discussions',
            'id'            => '1',
            'attributes'    => ['createdAt' => '2021-03-04T05:06:00+00:00'],
            'relationships' => ['user' => '5'],
        ], $this->bearer($this->developerToken(3))));

        $discussion = Discussion::query()->findOrFail(1);
        $this->assertSame(5, $discussion->user_id);
        $this->assertSame('2021-03-04 05:06:00', Carbon::parse($discussion->created_at)->toDateTimeString());
    }

    #[Test]
    public function a_date_without_an_offset_is_utc(): void
    {
        $this->toolOutput($this->callTool('update_resource', [
            'type'       => 'posts',
            'id'         => '2',
            'attributes' => ['createdAt' => '2021-03-04T05:06:00'],
        ], $this->bearer($this->developerToken(3))));

        $this->assertSame('2021-03-04 05:06:00', Carbon::parse(Post::query()->findOrFail(2)->created_at)->toDateTimeString());
    }

    #[Test]
    public function the_author_can_still_edit_the_post_content(): void
    {
        $this->toolOutput($this->callTool('update_resource', [
            'type'       => 'posts',
            'id'         => '2',
            'attributes' => ['content' => 'edited'],
        ], $this->bearer($this->developerToken(2))));

        $this->assertStringContainsString('edited', Post::query()->findOrFail(2)->content);
    }

    #[Test]
    public function a_user_without_permission_cannot_change_the_author(): void
    {
        $output = $this->toolOutput($this->callTool('update_resource', [
            'type'          => 'posts',
            'id'            => '2',
            'relationships' => ['user' => '5'],
        ], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertSame('You do not have permission to do that.', $output['error']);
        $this->assertSame(2, Post::query()->findOrFail(2)->user_id);
    }

    #[Test]
    public function a_user_without_permission_cannot_change_the_date(): void
    {
        $output = $this->toolOutput($this->callTool('update_resource', [
            'type'       => 'posts',
            'id'         => '2',
            'attributes' => ['createdAt' => '2021-03-04T05:06:00+00:00'],
        ], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertSame('You do not have permission to do that.', $output['error']);
        $this->assertSame('2020-01-02', Carbon::parse(Post::query()->findOrFail(2)->created_at)->toDateString());
    }
}
