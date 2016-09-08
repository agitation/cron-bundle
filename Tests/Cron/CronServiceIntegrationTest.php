<?php

/*
 * @package    agitation/base-bundle
 * @link       http://github.com/agitation/base-bundle
 * @author     Alexander Günsche
 * @license    http://opensource.org/licenses/MIT
 */

namespace Agit\CronBundle\Tests\Cron;

use Agit\CronBundle\Cron\CronService;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CronServiceIntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testEventRegistration()
    {
        $cronAwareService = $this->getMockBuilder('\Agit\CronBundle\Cron\CronAwareInterface')
            ->setMethods(['cronjobRegistration', 'cronjobExecute'])
            ->getMock();

        $cronAwareService->expects($this->any())
            ->method('cronjobExecute')
            ->will($this->throwException(new \Exception("Cronjob execution triggered.")));

        $cronService = new CronService(new EventDispatcher());
        $cronService->setDate(new \DateTime("2015-09-30 12:15"));

        // usually, cronjob registration is triggered by run(),
        // but here we must call it directly to have our service registered
        $cronService->registerCronjob($cronAwareService, '* * * * *');

        try {
            $cronService->run();
        } catch (\Exception $e) {
            $this->assertSame("Cronjob execution triggered.", $e->getMessage());
        }
    }
}
