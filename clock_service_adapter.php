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

$serviceId = $argv[4];
if (!is_string($serviceId)) {
    return STATUS_ERROR_SERVICE;
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

// override error handling
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }

    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// override shutdown handler
register_shutdown_function(function () {
    global $result;
    $error = error_get_last();

    if( $error !== null) {
        $result['error'] = $error['message'];
        $result['status'] = 'fatal';
        echo $result . PHP_EOL;

        exit(STATUS_OK);
    }
});

$result = [
    'id' => $serviceId,
    'time' => null,
    'status' => null,
    'scope' => null,
    'error' => null,
];

// retrieve the service instance
try {
    $start = microtime(true);

    $service = $kernel->getContainer()->get($serviceId);

    $end = microtime(true);

    $result['time'] = round($end - $start, 6);
    $result['status'] = 'successful';
} catch (InactiveScopeException $e) {
    $result['status'] = 'inactive';
    $result['scope']= $e->getScope();
} catch (\Exception $e) {
    $result['status'] = 'failed';
    $result['error'] = $e->getMessage();
}

echo json_encode($result) . PHP_EOL;

exit(STATUS_OK);
