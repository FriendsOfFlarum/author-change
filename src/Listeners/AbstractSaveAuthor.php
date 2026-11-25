<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange\Listeners;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;
use FoF\AuthorChange\Event;
use FoF\AuthorChange\Validators\TimeValidator;

abstract class AbstractSaveAuthor
{
    public function __construct(protected TimeValidator $timeValidator)
    {
    }

    /**
     * @param Discussion|Post $model
     * @param User            $actor
     * @param array           $data
     *
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function saveAuthor(AbstractModel $model, User $actor, array $data): void
    {
        $userData = $data['relationships']['user'] ?? [];

        // A null `data` clears an existing author; on creation the API assigns the creator.
        if (isset($userData['data']) || ($model->exists && array_key_exists('data', $userData))) {
            $actor->assertCan('fof-author-change.edit-user');

            // The API has already set the new values on the model by now.
            $oldUserId = $model->getOriginal('user_id');
            $oldUser = $oldUserId ? User::query()->find($oldUserId) : null;
            $newUser = null;

            if ($model instanceof Post) {
                $model->raise(new Event\PostUserChanged($model, $oldUser));
            } else {
                $model->raise(new Event\DiscussionUserChanged($model, $oldUser));
            }

            if (isset($data['relationships']['user']['data']['id'])) {
                $userId = $data['relationships']['user']['data']['id'];
                $newUser = User::query()->findOrFail($userId);

                $model->user()->associate($newUser);
            } elseif (empty($data['relationships']['user']['data'])) {
                $model->user()->dissociate();
            }

            // Update discussion metadata
            if ($model instanceof Post) {
                $model->afterSave(function () use ($model) {
                    $model->discussion
                        ->refreshParticipantCount()
                        ->refreshLastPost()
                        ->save();
                });
            }

            // Update user metadata
            $model->afterSave(function () use ($model, $oldUser, $newUser) {
                if ($oldUser) {
                    if ($model instanceof Post) {
                        $oldUser->refreshCommentCount();
                    } else {
                        $oldUser->refreshDiscussionCount();
                    }

                    $oldUser->save();
                }

                if ($newUser) {
                    if ($model instanceof Post) {
                        $newUser->refreshCommentCount();
                    } else {
                        $newUser->refreshDiscussionCount();
                    }

                    $newUser->save();
                }
            });
        }

        if (isset($data['attributes']['createdAt'])) {
            $actor->assertCan('fof-author-change.edit-date');

            $this->timeValidator->assertValid([
                'time' => $data['attributes']['createdAt'],
            ]);

            if ($model instanceof Post) {
                if ($model->exists) {
                    $model->raise(new Event\PostCreateDateChanged($model, $model->getOriginal('created_at')));
                }

                // Update discussion metadata
                $model->afterSave(function (Post $post) {
                    $post->discussion->refreshLastPost()->save();
                });
            } elseif ($model->exists) {
                $model->raise(new Event\DiscussionCreateDateChanged($model, $model->getOriginal('created_at')));
            }

            $model->created_at = Carbon::parse($data['attributes']['createdAt'])->utc();
        }

        if (isset($data['attributes']['editedAt']) && $model instanceof Post) {
            $actor->assertCan('fof-author-change.edit-date');

            $model->raise(new Event\PostEditDateChanged($model, $model->getOriginal('edited_at')));

            if (empty($data['attributes']['editedAt'])) {
                $model->edited_at = null;
            } else {
                $this->timeValidator->assertValid([
                    'time' => $data['attributes']['editedAt'],
                ]);

                $model->edited_at = Carbon::parse($data['attributes']['editedAt'])->utc();
            }
        }
    }
}
