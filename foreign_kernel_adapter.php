<?php

/*
 * This file is part of the Alameda Smoke Test package.
 *
 * (c) Sebastian Kuhlmann <zebba@hotmail.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/** @see \Alameda\Quality\Process\Process::$exitCodes */
const STATUS_OK = 0;
const STATUS_ERROR_AUTOLOAD_FILE = 64;
const STATUS_ERROR_KERNEL_FILE = 65;
const STATUS_ERROR_ENVIRONMENT = 66;
const STATUS_ERROR_SERVICE = 67;
const STATUS_ERROR_KERNEL_NAME = 68;
const STATUS_ERROR_KERNEL_INSTANCE = 69;
const STATUS_ERROR_KERNEL_BOOT = 70;
const STATUS_ERROR_FATAL_ON_SERVICE_INSTANCE = 71;

// validate parameters
$autoloadFile = new \SplFileInfo($argv[1]);
if (!$autoloadFile->isFile() || !$autoloadFile->isReadable()) {
    return STATUS_ERROR_AUTOLOAD_FILE;
}

$kernelFile = new \SplFileInfo($argv[2]);
if (!$kernelFile->isFile() || !$kernelFile->isReadable()) {
    return STATUS_ERROR_KERNEL_FILE;
}

$environment = $argv[3];
if (!is_string($environment)) {
    return STATUS_ERROR_ENVIRONMENT;
}

$kernelName = strstr($kernelFile->getBasename(), '.', true);
if (!is_string($kernelName)) {
    return STATUS_ERROR_KERNEL_NAME;
}

// load foreign autoloader
require_once $autoloadFile->getRealPath();
require_once $kernelFile->getRealPath();

use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Symfony\Component\HttpFoundation\Kernel;

// boot the kernel
try {
    /** @var Kernel $kernel */
    $kernel = new $kernelName($environment, false);
    $kernel->boot();
} catch (\Exception $e) {
    error_log($e->getTraceAsString());

    exit(!is_object($kernel) ? STATUS_ERROR_KERNEL_INSTANCE : STATUS_ERROR_KERNEL_BOOT);
}

echo json_encode($kernel->getContainer()->getServiceIds()) . PHP_EOL;

exit(STATUS_OK);
