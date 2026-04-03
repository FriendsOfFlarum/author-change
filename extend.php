<?php

namespace ClarkWinkelmann\AuthorChange;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
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
            fn ($_model, Context $context) => $context->creating() || $context->getActor()->can('clarkwinkelmann-author-change.edit-user')
        ))
        ->field('createdAt', fn (Schema\DateTime $field) => $field->writable(
            fn ($_model, Context $context) => $context->getActor()->can('clarkwinkelmann-author-change.edit-date')
        )),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->field('user', fn (Schema\Relationship\ToOne $field) => $field->writable(
            fn ($_model, Context $context) => $context->getActor()->can('clarkwinkelmann-author-change.edit-user')
        ))
        ->field('createdAt', fn (Schema\DateTime $field) => $field->writable(
            fn ($_model, Context $context) => ($context->creating() && $context->getActor()->isAdmin())
                || $context->getActor()->can('clarkwinkelmann-author-change.edit-date')
        ))
        ->field('editedAt', fn (Schema\DateTime $field) => $field->writable(
            fn ($_model, Context $context) => $context->getActor()->can('clarkwinkelmann-author-change.edit-date')
        )),

    (new Extend\Event())
        ->listen(DiscussionSaving::class, Listeners\SaveDiscussion::class)
        ->listen(PostSaving::class, Listeners\SavePost::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('clarkwinkelmannAuthorChangeCanEditUser')
                ->get(fn ($_model, Context $context) => $context->getActor()->can('clarkwinkelmann-author-change.edit-user')),
            Schema\Boolean::make('clarkwinkelmannAuthorChangeCanEditDate')
                ->get(fn ($_model, Context $context) => $context->getActor()->can('clarkwinkelmann-author-change.edit-date')),
        ]),
];
