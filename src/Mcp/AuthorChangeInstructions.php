<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange\Mcp;

use Flarum\Mcp\Capability\McpContext;

/**
 * Changes go through the generic `update_resource` tool; the fields' own gates
 * decide.
 */
class AuthorChangeInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        $actor = $context->actor;
        $lines = [];

        if ($actor->can('fof-author-change.edit-user')) {
            $lines[] = 'Change author: update_resource on posts or discussions with relationships {"user": "<user ID>"}. A discussion and its first post each have their own author: change both to keep them in step.';
        }

        if ($actor->can('fof-author-change.edit-date')) {
            $lines[] = 'Change dates: update_resource with {"createdAt": "<ISO 8601 date>"} on posts or discussions, and {"editedAt": "<ISO 8601 date>"} on posts; a date without an offset is UTC. A discussion and its first post each have their own createdAt: change both to keep them in step.';
        }

        return $lines ? implode(' ', $lines) : null;
    }
}
