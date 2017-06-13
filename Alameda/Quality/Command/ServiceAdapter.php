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
        $resolver->setNormalizer('app', self::getKernelDirectoryNormalizer());
        $resolver->setNormalizer('autoload', self::getFileInKernelDirectoryNormalizer());
        $resolver->setNormalizer('kernel', self::getFileInKernelDirectoryNormalizer());
        $resolver->setNormalizer('format', self::getFormatter());

        return $resolver->resolve($options);
    }

    /**
     * @return \Closure
     */
    private static function getKernelDirectoryNormalizer(): \Closure
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
     * @return \Closure
     */
    private static function getFileInKernelDirectoryNormalizer(): \Closure
    {
        return function (Options $options, $value) {
            $kernel = $options['app'];

            $file = new \SplFileInfo($kernel->getRealPath() . DIRECTORY_SEPARATOR . $value);

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
