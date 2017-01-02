<?php

/*
 * @package    agitation/cron-bundle
 * @link       http://github.com/agitation/cron-bundle
 * @author     Alexander Günsche
 * @license    http://opensource.org/licenses/MIT
 */

namespace Agit\CronBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterCronjobsCompilerPass implements CompilerPassInterface
{
    private $containerBuilder;

    public function process(ContainerBuilder $containerBuilder)
    {
        $crontabProcessor = $containerBuilder->findDefinition("agit.crontab");
        $cronjobProcessor = $containerBuilder->findDefinition("agit.cronjob");
        $services = $containerBuilder->findTaggedServiceIds("agit.cronjob");

        foreach ($services as $name => $tags) {
            foreach ($tags as $tag) {
                $crontabProcessor->addMethodCall("addCronjob", [$tag["schedule"], $name, $tag["method"]]);
                $cronjobProcessor->addMethodCall("addCronjob", [$name, $tag["method"]]);
            }
        }
    }
}
