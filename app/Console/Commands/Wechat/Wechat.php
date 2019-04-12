<?php

namespace App\Console\Commands\Wechat;

use Illuminate\Console\Command;

use App\Events\Wechat\UserList;
use App\Events\Wechat\UserInfoList;

class Wechat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wechat {module?} {do?} {--app_type=official_account} {--app_account=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '微信';

    protected $wechatApp;

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
        $this->wechatApp($this->option('app_type'), $this->option('app_account'));
        $module = $this->argument('module');
        $modules = [
            'user',
        ];
        do{
            while(!in_array($module, $modules)){
                $module = $this->choice("请选择要操作的模块", $modules, 0);
            }
            $moduleMethodName = "{$module}Module";
            $this->$moduleMethodName();
        }while($this->confirm("继续操作此账户的其他模块吗？") && !($module = ''));
    }

    protected function userModule(){
        $this->info("微信用户管理 ...");
        $do = $this->argument('do');
        $doList = [
            'list',
            'select',
        ];
        while(!in_array($do, $doList)){
            $do = $this->choice('请选择要执行的操作', $doList, 0);
        }

        if($do == 'list'){
            $this->userList($do);
        }elseif($do == 'select'){
            $this->userInfoList($do);
        }else{
            // 
        };
    }

    protected function userList($do){
        $this->info("获取用户列表 ...");
        $nextOpenId = trim($this->ask("请输入 nextOpenId，为空则获取全部"));
        $nextOpenId = $nextOpenId ?: null;
        $count = 0;
        do{
            $usersList = $this->wechatApp->user->$do($nextOpenId);
            if(!empty($usersList['data'])){
                event(new UserList($this->wechatApp, $usersList['data']['openid']));
                $count += $usersList['count'];
                $remainCount = $usersList['total'] - $count;
                $nextOpenId = $usersList['count'] < 10000 ? null : $usersList['next_openid'];
            }else{
                $nextOpenId = null;
                $this->error(json_encode($usersList));
            }
            $this->info("关注该公众账号的总用户数 {$usersList['total']}，本次拉取数量 {$usersList['count']}，剩余数量 {$remainCount}");
        }while(
            $nextOpenId
            && ($remainCount > 0)
            // && $this->confirm("继续拉取吗？")
        );
        $this->info("获取用户列表结束 ...");
    }

    protected function userInfoList($do){
        $this->info("获取用户信息列表 ...");
        $openids = \App\Models\WechatUser::where([
            'app_type' => $this->wechatApp->app_type,
            'app_id'   => $this->wechatApp->app_id,
        ])->pluck('openid');
        $openidsCount = $openids->count();
        $chunkSize = 100;
        $chunkPage = ceil($openidsCount/$chunkSize);
        $this->info("得到 {$openidsCount} 条记录，即将向微信请求信息，分 {$chunkPage} 批进行处理 ...");
        foreach($openids->chunk($chunkSize) as $chunkPageIndex=>$openidsChunked){
            $chunkPageCurrent = $chunkPageIndex + 1;
            $this->info("第 {$chunkPageCurrent} 批处理开始（共 {$chunkPage} 批）...");
            $wechatUsers = $this->wechatApp->user->$do($openidsChunked->values()->toArray());
            if(!empty($wechatUsers['user_info_list'])){
                event(new UserInfoList($this->wechatApp, $wechatUsers['user_info_list']));
            }else{
                $this->error(json_encode($wechatUsers));
            }
        }
        $this->info("获取用户信息列表结束 ...");
    }

    protected function wechatApp($appType = '', $appAccount = ''){
        do{
            $appTypes = [
                'official_account',
                // 'mini_program',
                // 'open_platform',
            ];
            while(!in_array($appType, $appTypes)){
                $appType = $this->choice('请选择微信账户类型', $appTypes, 0);
            }
            $appAccounts = array_keys(config("wechat.{$appType}"));
            while(!in_array($appAccount, $appAccounts)){
                $appAccount = $this->choice("类型为 {$appType} 的微信账户如下，请选择", $appAccounts, 0);
            }
        }while(!$this->confirm("确定要操作的微信账户是：{$appType}.{$appAccount}？") && (($appType = '') || !($appAccount = '')));
        $this->wechatApp = app("wechat.{$appType}.{$appAccount}");
    }

}
