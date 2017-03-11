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

/**
 * Thrown when processing the json response from an adapter is not valid json
 *
 * @author Sebastian Kuhlmann <zebba@hotmail.de>
 */
class ResultException extends \DomainException
{
    const ERR_JSON_ERROR = 1;

    /**
     * @param string $jsonError
     * @param \Exception|null $previous
     * @return ResultException
     */
    public static function jsonError($jsonError, \Exception $previous = null): ResultException
    {
        return new self(
            $jsonError,
            self::ERR_JSON_ERROR,
            $previous
        );
    }
}
