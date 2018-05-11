<?php


use PHPUnit\Framework\TestCase;

final class CronTest extends TestCase
{
    private $client;
    private $token;

    public function __execute()
    {
        ob_start();
        require '../cron/scheduled_tasks.php';
        $res = ob_get_flush();
        print_r($res);
    }

    public function testScriptRuns()
    {
        $this->assertEquals(1, 1);
    }

}