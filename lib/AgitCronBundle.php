<?php

/*
 * @package    agitation/cron-bundle
 * @link       http://github.com/agitation/cron-bundle
 * @author     Alexander Günsche
 * @license    http://opensource.org/licenses/MIT
 */

namespace Agit\CronBundle;

use Agit\CronBundle\DependencyInjection\RegisterCronjobsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AgitCronBundle extends Bundle
{
    public function build(ContainerBuilder $containerBuilder)
    {
        parent::build($containerBuilder);
        $containerBuilder->addCompilerPass(new RegisterCronjobsCompilerPass());
    }
}
