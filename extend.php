<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Audit\Extend\Audit;
use Flarum\Discussion\Event\Saving as DiscussionSaving;
use Flarum\Extend;
use Flarum\Post\Event\Saving as PostSaving;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/resources/less/forum.less')
        ->jsDirectory(__DIR__ . '/js/dist/forum'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    // Show and Create already include 'user' by default in core; Update does not.
    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->endpoint(Endpoint\Update::class, fn (Endpoint\Update $endpoint) => $endpoint->defaultInclude(['user']))
        ->field('user', fn (Schema\Relationship\ToOne $field) => $field->writable(
            fn ($_model, Context $context) => $context->creating() || $context->getActor()->can('fof-author-change.edit-user')
        ))
        ->field('createdAt', fn (Schema\DateTime $field) => $field->writable(
            fn ($_model, Context $context) => $context->getActor()->can('fof-author-change.edit-date')
        )),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->field('user', fn (Schema\Relationship\ToOne $field) => $field->writable(
            fn ($_model, Context $context) => $context->getActor()->can('fof-author-change.edit-user')
        ))
        ->field('createdAt', fn (Schema\DateTime $field) => $field->writable(
            fn ($_model, Context $context) => ($context->creating() && $context->getActor()->isAdmin())
                || $context->getActor()->can('fof-author-change.edit-date')
        ))
        ->field('editedAt', fn (Schema\DateTime $field) => $field->writable(
            fn ($_model, Context $context) => $context->getActor()->can('fof-author-change.edit-date')
        )),

    (new Extend\Event())
        ->listen(DiscussionSaving::class, Listeners\SaveDiscussion::class)
        ->listen(PostSaving::class, Listeners\SavePost::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('fofAuthorChangeCanEditUser')
                ->get(fn ($_model, Context $context) => $context->getActor()->can('fof-author-change.edit-user')),
            Schema\Boolean::make('fofAuthorChangeCanEditDate')
                ->get(fn ($_model, Context $context) => $context->getActor()->can('fof-author-change.edit-date')),
        ]),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-audit', fn () => [
            (new Audit())
                ->group('fof-author-change')
                ->listen(Event\DiscussionCreateDateChanged::class, 'discussion.create_date_changed', fn ($e) => [
                    'discussion_id' => $e->discussion->id,
                    'old_date' => $e->oldDate->toIso8601String(),
                    'new_date' => $e->discussion->created_at->toIso8601String(),
                ])
                ->listen(Event\DiscussionUserChanged::class, 'discussion.user_changed', fn ($e) => [
                    'discussion_id' => $e->discussion->id,
                    'old_user_id' => optional($e->oldUser)->id,
                    'new_user_id' => optional($e->discussion->user)->id,
                ])
                ->listen(Event\PostCreateDateChanged::class, 'post.create_date_changed', fn ($e) => [
                    'post_id' => $e->post->id,
                    'discussion_id' => $e->post->discussion->id,
                    'old_date' => $e->oldDate->toIso8601String(),
                    'new_date' => $e->post->created_at->toIso8601String(),
                ])
                ->listen(Event\PostEditDateChanged::class, 'post.edit_date_changed', fn ($e) => [
                    'post_id' => $e->post->id,
                    'discussion_id' => $e->post->discussion->id,
                    'old_date' => optional($e->oldDate)->toIso8601String(),
                    'new_date' => optional($e->post->edited_at)->toIso8601String(),
                ])
                ->listen(Event\PostUserChanged::class, 'post.user_changed', fn ($e) => [
                    'post_id' => $e->post->id,
                    'discussion_id' => $e->post->discussion->id,
                    'old_user_id' => optional($e->oldUser)->id,
                    'new_user_id' => optional($e->post->user)->id,
                ]),
        ]),
];
