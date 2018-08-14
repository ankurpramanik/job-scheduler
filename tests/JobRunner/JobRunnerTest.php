<?php

namespace SchedulerTests\JobRunner;

use PHPUnit\Framework\TestCase;
use Scheduler\Job\Job;
use Scheduler\Action\Report;
use Scheduler\Scheduler;
use Scheduler\Job\CallableAction;
use Scheduler\Job\RRule;
use DateTime;
use Scheduler\JobRunner\JobRunner;
use Scheduler\ActionInspector\FileActionInspector;

/**
 * Class JobRunnerTest
 * @package SchedulerTests\TaskRunner
 * @author Aleh Hutnikau, <goodnickoff@gmail.com>
 */
class JobRunnerTest extends TestCase
{
    public function testRun()
    {
        $now = time();
        $errorCallbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();

        $errorCallbackMock->expects($this->atLeastOnce())
            ->method('myCallBack')
            ->will($this->returnCallback(function () {
                throw new \Exception('foo');
            }));

        $scheduler = new Scheduler([
            $this->getJob($now-5),
            $this->getJob($now-3,'FREQ=MONTHLY;COUNT=5', $errorCallbackMock),
            $this->getJob($now),
        ]);
        $jobRunner = new JobRunner();
        $reports = $jobRunner->run($scheduler, DateTime::createFromFormat('U', $now-6));
        $this->assertTrue(is_array($reports));
        $this->assertEquals(3, count($reports));
        $this->assertTrue($reports[0] instanceof Report);
        $this->assertTrue($reports[0]->getResult());
        $this->assertTrue($reports[1]->getResult() instanceof \Exception);
        $this->assertTrue($reports[2]->getResult());

        $reports = $jobRunner->run($scheduler, DateTime::createFromFormat('U', $now-6));
        $this->assertEquals(3, count($reports));

        $actionsLog = new FileActionInspector(__DIR__ . DIRECTORY_SEPARATOR  . 'actions.log');
        $jobRunner = new JobRunner($actionsLog);
        $reports = $jobRunner->run($scheduler, DateTime::createFromFormat('U', $now-6));
        $this->assertEquals(3, count($reports));
        $reports = $jobRunner->run($scheduler, DateTime::createFromFormat('U', $now-6));
        $this->assertEquals(0, count($reports));
    }

    /**
     * @param integer $start
     * @param string $rrule
     * @param null|callable $callbackMock
     * @return Job
     */
    private function getJob($start, $rrule = 'FREQ=MONTHLY;COUNT=5', $callbackMock = null)
    {
        if ($callbackMock === null) {
            $callbackMock = $this->getMockBuilder('\stdClass')
                ->setMethods(['myCallBack'])
                ->getMock();

            $callbackMock->expects($this->atLeastOnce())
                ->method('myCallBack')
                ->will($this->returnValue(true));
        }

        $timezone = 'UTC';
        $startDate = DateTime::createFromFormat('U', $start, new \DateTimeZone($timezone));
        $rule = new RRule($rrule, $startDate);
        return new Job($rule, [$callbackMock, 'myCallBack']);
    }
}