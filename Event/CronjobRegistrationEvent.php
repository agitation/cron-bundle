<?php

namespace Agit\CronBundle\Event;

use Agit\CronBundle\Command\CronExecuteCommand;
use Symfony\Component\EventDispatcher\Event;

class CronjobRegistrationEvent extends Event
{
    private $cronCommand;

    public function __construct(CronExecuteCommand $cronCommand)
    {
        $this->cronCommand = $cronCommand;
    }

    public function registerCronjob($cronTime, Callable $callback)
    {
        if ($this->cronCommand->cronApplies($cronTime))
            call_user_func($callback);
    }
}
