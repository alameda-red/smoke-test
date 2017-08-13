<?php

/*
 * This file is part of the Alameda Smoke Test package.
 *
 * (c) Sebastian Kuhlmann <zebba@hotmail.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alameda\Quality\Command;

use Alameda\Quality\Exception\ServiceAdapterException;
use Alameda\Quality\Formatter\JsonFormatter;
use Alameda\Quality\Formatter\TextFormatter;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This classed is used to normalize the options passed to the command
 *
 * @author Sebastian Kuhlmann <zebba@hotmail.de>
 */
class ServiceAdapter
{
    /** @var string */
    const AUTOLOAD_FILE = 'autoload.php';

    /**
     * @param array $options
     * @return array
     * @throws InvalidArgumentException
     */
    public function normalize(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            'app',
            'autoload',
            'kernel',
            'env',
            'format',
        ]);
        $resolver->addAllowedValues('format', ['text', 'json']);
        $resolver->addAllowedTypes('env', 'string');
        $resolver->setNormalizer('app', self::getDirectoryNormalizer());
        $resolver->setNormalizer('autoload', self::getAutoloadNormalizer());
        $resolver->setNormalizer('kernel', self::getKernelNormalizer());
        $resolver->setNormalizer('format', self::getFormatter());

        return $resolver->resolve($options);
    }

    /**
     * Checks if the value is a readable directory
     *
     * @return \Closure
     */
    private static function getDirectoryNormalizer(): \Closure
    {
        return function (Options $options, $value) {
            $path = new \SplFileInfo($value);

            if (!$path->isDir()) {
                throw ServiceAdapterException::notDirectory($value);
            }

            if (!$path->isReadable()) {
                throw ServiceAdapterException::notReadable($path);
            }

            return $path;
        };
    }

    /**
     * Checks if the value points to a file and is readable
     *
     * @return \Closure
     */
    private static function getKernelNormalizer(): \Closure
    {
        return function (Options $options, $value) {
            $kernel = $options['app'];
            $callback = self::getDirectoryNormalizer();

            /** @var \SplFileInfo $directory */
            $directory = $callback($options, $kernel);

            $file = new \SplFileInfo($directory->getRealPath() . DIRECTORY_SEPARATOR . $value);

            if (!$file->isFile()) {
                throw ServiceAdapterException::notFile($value);
            }

            if (!$file->isReadable()) {
                throw ServiceAdapterException::notReadable($file);
            }

            return $file;
        };
    }

    /**
     * Checks if the value points towards a directory and normalizes the path by adding the autoloader
     *
     * @return \Closure
     */
    private static function getAutoloadNormalizer(): \Closure
    {
        return function (Options $options, $value) {
            $callback = self::getDirectoryNormalizer();

            /** @var \SplFileInfo $directory */
            $directory = $callback($options, $value);

            $file = new \SplFileInfo($directory . DIRECTORY_SEPARATOR . self::AUTOLOAD_FILE);

            if (!$file->isFile()) {
                throw ServiceAdapterException::notFile($value);
            }

            if (!$file->isReadable()) {
                throw ServiceAdapterException::notReadable($file);
            }

            return $file;
        };
    }

    /**
     * @return \Closure
     */
    private static function getFormatter(): \Closure
    {
        return function (Options $options, $value) {
            switch ($value) {
                case 'json':
                    return new JsonFormatter();
                default:
                    return new TextFormatter();
            }
        };
    }
}
