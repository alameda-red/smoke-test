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

use Alameda\Quality\ClockResultList;
use Alameda\Quality\Exception\ResultException;
use Alameda\Quality\Exception\ServiceAdapterException;
use Alameda\Quality\Formatter\FormatterInterface;
use Alameda\Quality\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @author Sebastian Kuhlmann <zebba@hotmail.de>
 */
class SmokeTestCommand extends Command
{
    const STATUS_OK = 0;
    const STATUS_CONFIGURATION_ERROR = 1;
    const STATUS_KERNEL_ADAPTER_ERROR = 2;
    const STATUS_KERNEL_ADAPTER_RESULT_ERROR = 3;

    /** @var FormatterInterface */
    private $formatter;

    /** @inheritdoc */
    protected function configure()
    {
        $this
            ->setName('quality:smoke-test')
            ->setDefinition([
                new InputArgument('app', InputArgument::REQUIRED, 'The path to the directory with your kernel'),
                new InputOption('autoload', null, InputOption::VALUE_OPTIONAL, 'The name of your autoload file', 'autoload.php'),
                new InputOption('kernel', null, InputOption::VALUE_OPTIONAL, 'The name of your kernel file', 'AppKernel.php'),
                new InputOption('env', null, InputOption::VALUE_OPTIONAL, 'The environment to boot the kernel in', 'dev'),
                new InputOption('format', null, InputOption::VALUE_OPTIONAL, 'The format of the output', 'text'),
            ])
            ->setHelp(<<<EOF
The <info>%command.name%</info> command looks  for issues in your
dependency injection container of your application kernel:

<info>php %command.full_name% /path/to/app/folder</info>

You can also pass the name of an <info>autoload.php</info> file as an option:

<info>php %command.full_name% /path/to/app/folder --autoload=autoload.php</info>

You can further more pass the path to a <info>kernel</info> file name as an option: 

<info>php %command.full_name% /path/to/app/folder --kernel=AppKernel.php</info>

You can also pass the target <info>environment</info> you wish to check as an option:
 
<info>php %command.full_name% /path/to/app/folder --env=dev</info> 

You can choose the <info>format</info> of the result output:

<info>php %command.full_name% /path/to/app/folder --format=text</info>
<info>php %command.full_name% /path/to/app/folder --format=json</info>

For further information on the output format of the JSON, see the \Alameda\Quality\Formatter\JsonFormatter class.
EOF
            )
        ;
    }

    /** @inheritdoc */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            $adapter = new ServiceAdapter();
            $options = $adapter->normalize([
                'app' => $input->getArgument('app'),
                'autoload' => $input->getOption('autoload'),
                'kernel' => $input->getOption('kernel'),
                'env' => $input->getOption('env'),
                'format' => $input->getOption('format'),
            ]);

            $input->setOption('autoload', $options['autoload']);
            $input->setOption('kernel', $options['kernel']);
            $input->setOption('env', $options['env']);

            $this->formatter = $options['format'];
        } catch (ServiceAdapterException $e) {
            $output->writeln($this->getHelperSet()->get('formatter')->formatBlock(
                sprintf('Misconfiguration: %s', $e->getMessage()),
                'error',
                true
            ));

            $this->setCode(function () {
                return self::STATUS_CONFIGURATION_ERROR;
            });
        }
    }

    /** @inheritdoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $services = $this->getServicesFromKernelAdapter(
                $input->getOption('kernel'),
                $input->getOption('autoload'),
                $input->getOption('env')
            );

            $result = new ClockResultList();

            /** @var string $serviceId */
            foreach ($services as $serviceId) {
                $data = $this->getResultFromKernelAdapter(
                    $input->getOption('kernel'),
                    $input->getOption('autoload'),
                    $input->getOption('env'),
                    $serviceId
                );

                $result->add($data);
            }
        } catch (ResultException $e) {
            $output->writeln($this->getHelperSet()->get('formatter')->formatBlock(
                'Unable to process result: ' . $e->getMessage(),
                'error',
                true
            ));

            return self::STATUS_KERNEL_ADAPTER_RESULT_ERROR;
        } catch (ProcessFailedException $e) {
            $output->writeln($this->getHelperSet()->get('formatter')->formatBlock(
                $e->getMessage(),
                'error',
                true
            ));

            return self::STATUS_KERNEL_ADAPTER_ERROR;
        }

        $this->formatter->displayResults($output, $result);

        return self::STATUS_OK;
    }

    /**
     * @param \SplFileInfo $kernel
     * @param \SplFileInfo $autoload
     * @param string $environment
     * @throws ServiceAdapterException
     * @return string[]
     */
    private function getServicesFromKernelAdapter(
        \SplFileInfo $kernel,
        \SplFileInfo $autoload,
        string $environment): array
    {
        $process = sprintf(
            'php foreign_kernel_adapter.php %s %s %s',
            $autoload->getRealPath(),
            $kernel->getRealPath(),
            $environment
        );

        return $this->getJsonFromAdapter($process);
    }

    /**
     * @param \SplFileInfo $kernel
     * @param \SplFileInfo $autoload
     * @param string $environment
     * @param string $serviceId
     * @return array
     */
    private function getResultFromKernelAdapter(
        \SplFileInfo $kernel,
        \SplFileInfo $autoload,
        string $environment,
        string $serviceId): array
    {
        $process = sprintf(
            'php clock_service_adapter.php %s %s %s %s',
            $autoload->getRealPath(),
            $kernel->getRealPath(),
            $environment,
            $serviceId
        );

        return $this->getJsonFromAdapter($process);
    }

    /**
     * @param string $process
     * @throwsResultAdapterException
     * @return array
     */
    private function getJsonFromAdapter(string $process): array
    {
        $adapter = new Process($process);
        $adapter->run();

        if (!$adapter->isSuccessful()) {
            throw new ProcessFailedException($adapter);
        }

        $result = json_decode($adapter->getOutput(), true);

        if (json_last_error()) {
            throw ResultException::jsonError(json_last_error_msg());
        }

        return $result;
    }
}
