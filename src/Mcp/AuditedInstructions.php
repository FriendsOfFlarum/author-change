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

class AuditedInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        if (! $context->actor->can('fof-author-change.edit-user') && ! $context->actor->can('fof-author-change.edit-date')) {
            return null;
        }

        return 'Author and date changes are recorded in the audit log.';
    }
}
