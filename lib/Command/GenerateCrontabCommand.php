<?php
declare(strict_types=1);
/*
 * @package    agitation/cron-bundle
 * @link       http://github.com/agitation/cron-bundle
 * @author     Alexander Günsche
 * @license    http://opensource.org/licenses/MIT
 */

namespace Agit\CronBundle\Command;

use Agit\BaseBundle\Exception\InternalErrorException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCrontabCommand extends ContainerAwareCommand
{
    private $cronjobs = [];

    // min/max values for minute, hour, day, month, weekday
    private $ranges = [[0, 59], [0, 23], [1, 31], [1, 12], [0, 6]];

    public function addCronjob($cronTime, $serviceId, $method)
    {
        $this->cronjobs[] = [$cronTime, $serviceId, $method];
    }

    protected function configure()
    {
        $this
            ->setName('agit:crontab')
            ->setDescription('Executes all registered cronjobs that are registered for the current cycle.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = fileowner(__FILE__);

        if (function_exists('posix_getpwuid'))
        {
            $user = posix_getpwuid($user)['name'];
        }

        $command = 'agit:cronjob';
        $consoleFile = isset($GLOBALS) && isset($GLOBALS['argv']) && isset($GLOBALS['argv'][0]) ? $GLOBALS['argv'][0] : 'app/console';
        $consoleFilePath = sprintf('%s/%s', getcwd(), $consoleFile);

        $entries = $this->getFileHeader();

        foreach ($this->cronjobs as $cronjob)
        {
            list($cronTime, $serviceId, $method) = $cronjob;

            try
            {
                $this->parseCronTime($cronTime);
            }
            catch (Exception $e)
            {
                throw new Exception(sprintf('Invalid cron time for service %s.%s: %s', $serviceId, $method, $e->getMessage()));
            }

            $entries .= sprintf("%s\t%s\t%s %s %s %s\n", $cronTime, $user, $consoleFilePath, $command, $serviceId, $method);
        }

        $output->write($entries);
    }

    private function parseCronTime($cronTime)
    {
        $cronParts = preg_split("|\s+|", $cronTime, null, PREG_SPLIT_NO_EMPTY);

        if (count($cronParts) !== 5)
        {
            throw new InternalErrorException('Invalid cron time.');
        }

        $parsedParts = [];

        foreach ($cronParts as $pos => $value)
        {
            if ($value === '*')
            {
                $parsedParts[$pos] = null;
            }
            else
            {
                $elements = [];

                if (preg_match("|^\*/\d+$|", $value))
                {
                    $step = (int) substr($value, 2);

                    for ($i = $this->ranges[$pos][0]; $i < $this->ranges[$pos][1]; $i += $step)
                    {
                        $elements[] = $i;
                    }
                }
                elseif (preg_match("|^\d+(,\d+)*$|", $value))
                {
                    $elements = array_map('intval', explode(',', $value));
                }
                else
                {
                    throw new InternalErrorException("Invalid cron time parameter at position $pos.");
                }

                foreach ($elements as $element)
                {
                    if ($element < $this->ranges[$pos][0] || $element > $this->ranges[$pos][1])
                    {
                        throw new InternalErrorException("Invalid cron time parameter at position $pos.");
                    }
                }

                $parsedParts[$pos] = $elements;
            }
        }

        return $parsedParts;
    }

    private function getFileHeader()
    {
        return  "# Put this file into the /etc/cron.d/ directory or wherever is appropriate on\n" .
                "# your system. Feel free to modify the timings if you know what you are doing.\n\n" .
                "# ATTENTION: If you are overwriting an existing file, remember to preserve your\n" .
                "# modifications, if necessary.\n\n";
    }
}
