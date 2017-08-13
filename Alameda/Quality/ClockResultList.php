<?php

/*
 * This file is part of the Alameda Smoke Test package.
 *
 * (c) Sebastian Kuhlmann <zebba@hotmail.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Alameda\Quality;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Container for the results providing data access and statistics calculation
 *
 * @author Sebastian Kuhlmann <zebba@hotmail.de>
 */
class ClockResultList implements \JsonSerializable
{
    /** @var array */
    private $successful = [];

    /** @var array */
    private $failed = [];

    /** @var array */
    private $inactive = [];

    /** @var OptionsResolver */
    private $resolver;

    /** @var float local caching for the total elapsed time in ms */
    private $totalElapsedTime;

    /** @var float local caching for the average elapsed time in ms */
    private $averageElapsedTime;

    /** @var float local caching for the standard deviation in ms */
    private $standardDeviation;

    /** @var float local caching for the variance in ms**2 */
    private $variance;

    public function __construct()
    {
        $nullableString = function ($value) {
            return is_string($value) || is_null($value);
        };
        $nullableFloat = function ($value) {
            return is_float($value) || is_null($value);
        };

        $resolver = new OptionsResolver();
        $resolver->setRequired([
            'id',
            'time',
            'status',
            'scope',
            'error',
        ]);
        $resolver->setDefaults([
            'file' => null,
            'line' => null,
            'trace' => [],
        ]);
        $resolver->addAllowedTypes('file', ['string', 'null']);
        $resolver->addAllowedTypes('line', ['int', 'null']);
        $resolver->addAllowedValues('status', [
            'successful',
            'failed',
            'inactive',
            'fatal',
        ]);
        $resolver->addAllowedValues('id', function ($value) {
            return is_string($value);
        });
        $resolver->addAllowedValues('time', $nullableFloat);
        $resolver->addAllowedValues('scope', $nullableString);
        $resolver->addAllowedValues('error', $nullableString);

        $this->resolver = $resolver;
    }

    /**
     * Adds the parsed JSON result to the result list
     *
     * @param array $result
     */
    public function add(array $result)
    {
        $result = $this->resolver->resolve($result);

        switch ($result['status']) {
            case 'successful':
                $this->addSuccessful($result['id'], $result['time']);
                break;
            case 'fatal': // fall-through intended
            case 'failed':
                $this->addFailed(
                    $result['id'],
                    $result['error'],
                    $result['file'],
                    $result['line'],
                    $result['trace']
                );
                break;
            case 'inactive':
                $this->addInactive($result['id'], $result['scope']);
                break;
        }
    }

    /**
     * Adds a successful service result
     *
     * @param string $id
     * @param float $elapsed in ms
     */
    private function addSuccessful(string $id, float $elapsed)
    {
        $this->successful[] = [
            'id' => $id,
            'elapsed' => $elapsed
        ];
    }

    /**
     * Adds a failed service result
     *
     * @param string $id
     * @param string $message
     * @param string $file
     * @param integer $line
     * @param array $trace
     */
    private function addFailed(string $id, string $message, string $file = null, int $line = null, array $trace = [])
    {
        $this->failed[] = [
            'id' => $id,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => $trace,
        ];
    }

    /**
     * Adds an inactive service result
     *
     * @param string $id
     * @param string $scope
     */
    private function addInactive(string $id, string $scope)
    {
        $this->inactive[] = [
            'id' => $id,
            'scope' => $scope
        ];
    }

    /**
     * Checks if the result contains successful services
     *
     * @return bool
     */
    public function hasSuccessfulServices(): bool
    {
        return $this->countSuccessfulServices() > 0;
    }

    /**
     * Counts the successful services in the result
     *
     * @return int
     */
    public function countSuccessfulServices(): int
    {
        return count($this->getSuccessfulServices());
    }

    /**
     * Returns a list of successful services
     *
     * @example
     * [
     *  [
     *      'id' => 'foo.service', // service id
     *      'elapsed' => 123.45, // elapsed time in ms
     *  ],
     * ]
     * @return array
     */
    public function getSuccessfulServices(): array
    {
        return $this->successful;
    }

    /**
     * Checks if the result contains failed services
     *
     * @return bool
     */
    public function hasFailedServices(): bool
    {
        return $this->countFailedServices() > 0;
    }

    /**
     * Counts the failed services in the result
     *
     * @return int
     */
    public function countFailedServices(): int
    {
        return count($this->getFailedServices());
    }

    /**
     * Returns a list of failed services
     *
     * @example
     * [
     *  [
     *      'id' => 'foo.service', // service id
     *      'message' => 'An exception occured in driver: SQLSTATE[HY000] [2002] Connection refused', // error message
     *  ],
     * ]
     * @return array
     */
    public function getFailedServices(): array
    {
        return $this->failed;
    }

    /**
     * Checks if the result contains inactive services
     *
     * @return bool
     */
    public function hasInactiveServices(): bool
    {
        return $this->countInactiveServices() > 0;
    }

    /**
     * Counts the inactive services in the result
     *
     * @return int
     */
    public function countInactiveServices(): int
    {
        return count($this->getInactiveServices());
    }

    /**
     * Returns a list of inactive services
     *
     * @example
     * [
     *  [
     *      'id' => 'foo.service', // service id
     *      'scope' => 'request', // scope of the service
     *  ].
     * ]
     * @return array
     */
    public function getInactiveServices(): array
    {
        return $this->inactive;
    }

    /**
     * Counts the total number of services in the result regardless of their type
     *
     * @return int
     */
    public function getTotalNumberOfServices(): int
    {
        return $this->countSuccessfulServices() + $this->countFailedServices() + $this->countInactiveServices();
    }

    /**
     * Calculates and returns the total elapsed time for the successful services
     *
     * @return float in ms
     */
    public function getTotalElapsedTime(): float
    {
        if (!$this->totalElapsedTime) {
            $this->totalElapsedTime = array_sum(array_column($this->successful, 'elapsed'));
        }

        return $this->totalElapsedTime;
    }

    /**
     * Calculates and returns the average elapsed time in relation to the number of successful services
     *
     * @return float in ms
     */
    public function getAverageElapsedTime(): float
    {
        if (!$this->averageElapsedTime) {
            $this->averageElapsedTime = round($this->getTotalElapsedTime() / (float) $this->countSuccessfulServices());
        }

        return $this->averageElapsedTime;
    }

    /**
     * Calculates and returns the standard deviation for the elapsed time based upon the successful services
     *
     * @return float in ms
     */
    public function getStandardDeviation(): float
    {
        if (!$this->standardDeviation) {
            $variance = $this->getVariance();

            $this->standardDeviation = sqrt($variance);
        }

        return $this->standardDeviation;
    }

    /**
     * Calculates and returns the variance for the elapsed time based upon the successful services
     *
     * @return float in ms**2
     */
    public function getVariance(): float
    {
        if (!$this->variance) {
            $average = $this->getAverageElapsedTime();

            $this->variance = array_sum(array_map(function ($e) use ($average) {
                return pow($e['elapsed'] - $average, 2);
            }, $this->successful));;
        }

        return $this->variance;
    }

    /** @inheritdoc */
    public function jsonSerialize()
    {
        return [
            'statistics' => [
                'total_number' => $this->getTotalNumberOfServices(),
                'total_time' => $this->getTotalElapsedTime(),
                'average_time' => $this->getAverageElapsedTime(),
                'variance' => $this->getVariance(),
                'standard_Deviation' => $this->getStandardDeviation(),
            ],
            'services' => [
                'successful' => $this->getSuccessfulServices(),
                'failed' => $this->getFailedServices(),
                'inactive' => $this->getInactiveServices(),
            ],
        ];
    }
}
