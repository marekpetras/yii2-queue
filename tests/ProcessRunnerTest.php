<?php

use UrbanIndo\Yii2\Queue\ProcessRunner;
use Symfony\Component\Process\Process;

class ProcessRunnerTest extends TestCase
{
    public function testRunnerRunSingle()
    {
        $runner = $this->getRunner();
        $this->assertEquals($runner->getIterator()->count(),0);

        $runner->runProcess('ls -l');

        $this->assertEquals($runner->getIterator()->count(),1);

        $runner->cleanUpAll();

        $this->assertEquals($runner->getIterator()->count(),0);
    }
    public function testRunnerRunMultiple()
    {
        $runner = $this->getRunner(2);

        $this->assertEquals($runner->getIterator()->count(),0);
        $runner->runProcess('ls -l');
        $runner->runProcess('ls -l');
        $this->assertEquals($runner->getIterator()->count(),2);

        $runner->cleanUpAll();
        $this->assertEquals($runner->getIterator()->count(),0);

    }
    public function testRunnerCleanUpProcess()
    {
        $pid = 123;
        $runner = $this->getRunner();

        $process = $this->createMock(Process::class);
        $process->method('getPid')
             ->willReturn($pid);

        $process->method('checkTimeout')
             ->willReturn(null);

        $process->method('isRunning')
             ->willReturn(false);

        $process->method('getOutput')
             ->willReturn('');

        $process->method('getErrorOutput')
             ->willReturn('');

        $process->method('isSuccessful')
             ->willReturn(true);

        $runner->addProcess($process);

        $this->assertEquals($runner->getIterator()->count(),1);

        $runner->cleanUpProc($process, $pid);

        $this->assertEquals($runner->getIterator()->count(),0);
    }
    public function testRunnerPropagateSignals()
    {
        $runner = $this->getRunner(2);
        $start = time();

        $runner->runProcess('setsid php -r "sleep(10);"');
        $this->assertEquals($runner->getIterator()->count(),1);
        $runner->runProcess('setsid php -r "sleep(10);"');
        $this->assertEquals($runner->getIterator()->count(),2);

        $runner->cleanUpAll(SIGKILL, true);
        $duration = time() - $start;
        $this->assertEquals($runner->getIterator()->count(),0);
        $this->assertLessThan(10, $duration);
    }
    protected function getRunner($proc = 1)
    {
        $queue = Yii::createObject([
            'class' => '\UrbanIndo\Yii2\Queue\Queues\MemoryQueue',
            'maxProcesses' => $proc,
        ]);
        $runner = new ProcessRunner();
        $runner->setQueue($queue);

        return $runner;
    }
}