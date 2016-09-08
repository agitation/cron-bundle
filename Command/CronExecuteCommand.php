<?php

/*
 * @package    agitation/base-bundle
 * @link       http://github.com/agitation/base-bundle
 * @author     Alexander Günsche
 * @license    http://opensource.org/licenses/MIT
 */

namespace Agit\CronBundle\Command;

use Agit\BaseBundle\Command\SingletonCommandTrait;
use Agit\BaseBundle\Exception\InternalErrorException;
use Agit\CronBundle\Event\CronjobRegistrationEvent;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronExecuteCommand extends ContainerAwareCommand
{
    const EVENT_REGISTRATION_KEY = "agit.cron.register";

    use SingletonCommandTrait;

    private $eventDispatcher;

    private $serviceList = [];

    // min/max values for minute, hour, day, month, weekday
    private $ranges = [[0, 59], [0, 23], [1, 31], [1, 12], [0, 6]];

    private $now;

    protected function configure()
    {
        $this
            ->setName("agit:cronjobs:execute")
            ->setDescription("Executes all registered cronjobs that are registered for the current cycle.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! $this->flock(__FILE__)) {
            return;
        }

        $dateTime = new DateTime();

        $this->now = [
            (int) $dateTime->format("i"),
            (int) $dateTime->format("H"),
            (int) $dateTime->format("d"),
            (int) $dateTime->format("m"),
            (int) $dateTime->format("w")
        ];

        $this->getContainer()->get("event_dispatcher")->dispatch(
            self::EVENT_REGISTRATION_KEY,
            new CronjobRegistrationEvent($this)
        );
    }

    public function cronApplies($cronTime)
    {
        $cronParts = $this->parseCronTime($cronTime);
        $applies = true;

        foreach ($cronParts as $pos => $value) {
            if ($value !== null && ! in_array($this->now[$pos], $value)) {
                $applies = false;
                break;
            }
        }

        return $applies;
    }

    public function parseCronTime($cronTime)
    {
        $cronParts = preg_split("|\s+|", $cronTime, null, PREG_SPLIT_NO_EMPTY);

        if (count($cronParts) !== 5) {
            throw new InternalErrorException("Invalid cron time.");
        }

        $parsedParts = [];

        foreach ($cronParts as $pos => $value) {
            if ($value === "*") {
                $parsedParts[$pos] = null;
            } else {
                $elements = [];

                if (preg_match("|^\*/\d+$|", $value)) {
                    $step = (int) substr($value, 2);

                    for ($i = $this->ranges[$pos][0]; $i < $this->ranges[$pos][1]; $i += $step) {
                        $elements[] = $i;
                    }
                } elseif (preg_match("|^\d+(,\d+)*$|", $value)) {
                    $elements = array_map("intval", explode(",", $value));
                } else {
                    throw new InternalErrorException("Invalid cron time parameter at position $pos.");
                }

                foreach ($elements as $element) {
                    if ($element < $this->ranges[$pos][0] || $element > $this->ranges[$pos][1]) {
                        throw new InternalErrorException("Invalid cron time parameter at position $pos.");
                    }
                }

                $parsedParts[$pos] = $elements;
            }
        }

        return $parsedParts;
    }
}
