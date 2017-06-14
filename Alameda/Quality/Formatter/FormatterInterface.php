<?php

/*
 * This file is part of the Alameda Smoke Test package.
 *
 * (c) Sebastian Kuhlmann <zebba@hotmail.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alameda\Quality\Formatter;

use Alameda\Quality\ClockResultList;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This interface is implemented by all Formatter classes
 *
 * @author Sebastian Kuhlmann <zebba@hotmail.de>
 */
interface FormatterInterface
{
    /**
     * Writes the result formatted to the output
     *
     * @param OutputInterface $output
     * @param ClockResultList $result
     */
    public function displayResults(OutputInterface $output, ClockResultList $result);
}
