#!/usr/bin/env php
<?php

/*
 * This file is part of the Alameda Smoke Test package.
 *
 * (c) Sebastian Kuhlmann <zebba@hotmail.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function includeIfExists($file)
{
    if (file_exists($file)) {
        return include $file;
    }
}

if ((!$loader = includeIfExists(__DIR__.'/vendor/autoload.php')) &&
    (!$loader = includeIfExists(__DIR__.'/../../autoload.php'))
) {
    die('You must set up the project dependencies, run the following commands:' . PHP_EOL.
        'curl -sS https://getcomposer.org/installer | php' . PHP_EOL.
        'php composer.phar install' . PHP_EOL);
}

use Symfony\Component\Console\Application;
use Alameda\Quality\Command\SmokeTestCommand;

$console = new Application('Alameda Smoke Test', '1.0.0-alpha');
$console->add(new SmokeTestCommand());
$console->run();
