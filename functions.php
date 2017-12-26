<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2017 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Application;

/**
 * @param bool $throw
 *
 * @return App
 */
function app($throw = true)
{
    return App::getInstance($throw);
}
