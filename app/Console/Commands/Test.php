<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Str;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test {module?} {--params=* : 附加参数}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试命令';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $module = $this->argument('module');
        $moduleMethodName = "{$module}Module";
        if(!method_exists($this, $moduleMethodName)){
            $reflecObject = new \ReflectionClass($this);
            $methods = $reflecObject->getMethods();
            $moduleMethodName = $this->choice('请选择要执行的模块', collect($methods)->filter(function($methodItem){
                return ends_with($methodItem->name, 'Module');
            })->pluck('name')->toArray());
        }
        $this->$moduleMethodName();
    }

    private function pluralModule(){
        $params = $this->arguments('params');
        $str = array_get($params, 0);
        while(empty($str)){
            $str = $this->ask('请输入单词（仅支持英文）');
        }
        foreach([
            '1' => '单',
            '2' => '复',
        ] as $k => $v){
            $this->info("{$str} 的{$v}数形式：" . Str::plural($str, $k));
        }
    }

    private function dingtalkModule(){
        app('dingtalk_client')->post(config('dingtalk.path.robot'), [
            'query' => [
                'access_token' => config('dingtalk.access_token.robot'),
            ],
            'json' => [
                "msgtype" => "text",
                "text" => [
                    "content" => "我就是我, 是颜色不一样的烟火",
                ],
                "at" => [
                    'atMobiles' => [
                        // "130xxxx6752", 
                    ],
                    // 'isAtAll' => false,
                ],
            ],
        ]);
    }

}
