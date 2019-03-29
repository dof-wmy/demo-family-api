<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Artisan;

class PusherTest extends TestCase
{
    public function testPusher()
    {
        $this->artisan('pusher')
         ->expectsQuestion('请选择频道', 'admin')
         ->expectsQuestion('请选择事件', 'notice')
         ->expectsQuestion('请输入内容', 'hello world')
         ->assertExitCode(0);
    }
}
