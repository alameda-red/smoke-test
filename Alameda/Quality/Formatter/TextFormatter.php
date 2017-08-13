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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Outputs the result to the console in human-readable format
 *
 * @author Sebastian Kuhlmann <zebba@hotmail.de>
 */
class TextFormatter implements FormatterInterface
{
    /** @inheritdoc */
    public function displayResults(OutputInterface $output, ClockResultList $result)
    {
        $io = new SymfonyStyle(new ArrayInput([]), $output);
        $io->title('Smoke Test');

        $this->outputStatistics($io, $result);
        $this->outputSuccessfulServices($io, $result);
        $this->outputFailedServices($io, $result);
        $this->outputInactiveServices($io, $result);
    }

    /**
     * @param SymfonyStyle $io
     * @param ClockResultList $result
     */
    private function outputSuccessfulServices(SymfonyStyle $io, ClockResultList $result)
    {
        $io->section('Successful Services');

        if ($result->hasSuccessfulServices()) {
            $io->success(sprintf('Found %d successful services', $result->countSuccessfulServices()));
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param ClockResultList $result
     */
    private function outputFailedServices(SymfonyStyle $io, ClockResultList $result)
    {
        $io->section('Failed Services');

        if (!$result->hasFailedServices()) {
            $io->success('No failed services found');

            return;
        }

        // hide additional fields from the output
        $failed = array_map(function ($e) {
            unset($e['file'], $e['line'], $e['trace']);

            return $e;
        }, $result->getFailedServices());

        if ($io->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $io->table(['id', 'error'], $failed);
        } elseif ($io->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $io->table(['id', 'error'], array_map(function (array $e) {
                if (80 < strlen($e['message'])) {
                    $e['message'] = substr($e['message'], 0, 77) . '...';
                }

                return $e;
            }, $failed));
        }

        $io->error(sprintf('Found %d failed services', $result->countFailedServices()));
    }

    /**
     * @param SymfonyStyle $io
     * @param ClockResultList $result
     */
    private function outputInactiveServices(SymfonyStyle $io, ClockResultList $result)
    {
        $io->section('Inactive Services');

        if (!$result->hasInactiveServices()) {
            $io->success('No inactive services found');

            return;
        }

        if ($io->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $io->note('Found inactive services');
            $io->table(['id', 'scope'], array_map(function ($id, $scope) {
                return [$id, $scope];
            },
                array_column($result->getInactiveServices(), 'id'),
                array_column($result->getInactiveServices(), 'scope')
            ));
        }

        $io->text(sprintf('Found %d inactive services', $result->countInactiveServices()));
    }

    /**
     * @param SymfonyStyle $io
     * @param ClockResultList $result
     */
    private function outputStatistics(SymfonyStyle $io, ClockResultList $result)
    {
        $io->section('Statistics');

        $io->listing([
            sprintf('number of services: %d', $result->getTotalNumberOfServices()),
            sprintf('total elapsed time: %f ms', $result->getTotalElapsedTime()),
            sprintf('average elapsed time: %f ms', $result->getAverageElapsedTime()),
            sprintf('variance: %f ms²', $result->getVariance()),
            sprintf('standard deviation: %f ms', $result->getStandardDeviation()),
        ]);
    }
}
