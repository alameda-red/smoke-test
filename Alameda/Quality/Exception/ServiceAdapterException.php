<?php

/*
 * This file is part of the Alameda Smoke Test package.
 *
 * (c) Sebastian Kuhlmann <zebba@hotmail.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alameda\Quality\Exception;

use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException as OptionResolverInvalidArgumentException;

/**
 * Thrown when parameters normalized by the ServiceAdapter contain unexpected values
 *
 * @author Sebastian Kuhlmann <zebba@hotmail.de>
 */
class ServiceAdapterException extends OptionResolverInvalidArgumentException
{
    const ERR_NOT_DIRECTORY = 1;
    const ERR_NOT_FILE = 2;
    const ERR_NOT_READABLE = 4;

    /**
     * @param string $path
     * @param \Exception|null $previous
     * @return ServiceAdapterException
     */
    public static function notDirectory($path, \Exception $previous = null): ServiceAdapterException
    {
        return new self(
            sprintf('The path \'%s\' is not a directory', $path),
            self::ERR_NOT_DIRECTORY,
            $previous
        );
    }

    /**
     * @param string $path
     * @param \Exception|null $previous
     * @return ServiceAdapterException
     */
    public static function notFile($path, \Exception $previous = null): ServiceAdapterException
    {
        return new self(
            sprintf('The path \'%s\' is not a file', $path),
            self::ERR_NOT_FILE,
            $previous
        );
    }

    /**
     * @param \SplFileInfo $path
     * @param \Exception|null $previous
     * @return ServiceAdapterException
     */
    public static function notReadable(\SplFileInfo $path, \Exception $previous = null): ServiceAdapterException
    {
        return new self(
            sprintf('Unable to read \'%s\'', $path->getRealPath()),
            self::ERR_NOT_READABLE,
            $previous
        );
    }
}
