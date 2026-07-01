<?php

/*
 * This file is part of fof/author-change.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AuthorChange\Validators;

use Flarum\Foundation\AbstractValidator;

class TimeValidator extends AbstractValidator
{
    protected function getRules(): array
    {
        return [
            'time' => 'required|date',
        ];
    }
}
