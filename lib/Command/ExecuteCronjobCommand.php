<?php
declare(strict_types=1);
/*
 * @package    agitation/cron-bundle
 * @link       http://github.com/agitation/cron-bundle
 * @author     Alexander Günsche
 * @license    http://opensource.org/licenses/MIT
 */

namespace Agit\CronBundle\Command;

use Exception;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCronjobCommand extends ContainerAwareCommand
{
    private $cronjobs = [];

    public function addCronjob($serviceId, $method)
    {
        $this->cronjobs[] = ['service' => $serviceId, 'method' => $method];
    }

    protected function configure()
    {
        $this
            ->setName('agit:cronjob')
            ->setDescription('Executes a service method, identified by a service ID and a method name.')
            ->addArgument('service', InputArgument::REQUIRED, 'service ID')
            ->addArgument('method', InputArgument::REQUIRED, 'service method name')
            ->addOption('log', 'l', InputOption::VALUE_NONE, 'Adds a log entry when the cronjob is executed. Note: Errors are always logged.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->has('logger') ? $this->getContainer()->get('logger') : null;

        try
        {
            $serviceId = $input->getArgument('service');
            $method = $input->getArgument('method');

            $jobRegistered = false;

            foreach ($this->cronjobs as $cronjob)
            {
                if ($cronjob['service'] === $serviceId && $cronjob['method'] === $method)
                {
                    $jobRegistered = true;
                }
            }

            if (! $jobRegistered)
            {
                throw new Exception('Such a cronjob has not been registered.');
            }

            $service = $this->getContainer()->get($serviceId);
            call_user_func([$service, $method]);

            if ($logger && $input->getOption('log'))
            {
                $logger->log(LogLevel::INFO, sprintf('Cronjob @%s->%s() completed successfully.', $serviceId, $method));
            }
        }
        catch (Exception $e)
        {
            if ($logger)
            {
                $logger->log(LogLevel::ERROR, sprintf(
                    'Error while executing cronjob @%s->%s(): %s',
                    $serviceId,
                    $method,
                    $e->getMessage()
                ));
            }
        }
    }
}
