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
use Symfony\Component\DependencyInjection\Reference;

class RegisterCronjobsCompilerPass implements CompilerPassInterface
{
    private $containerBuilder;

    public function process(ContainerBuilder $containerBuilder)
    {
        $processor = $containerBuilder->findDefinition("agit.cron");
        $services = $containerBuilder->findTaggedServiceIds("agit.cronjob");

        foreach ($services as $name => $tags) {
            foreach ($tags as $tag) {
                $processor->addMethodCall("addCronjob", [$tag["schedule"], new Reference($name), $tag["method"]]);
            }
        }
    }
}
