<?php

namespace App\Console\Commands\Wechat;

use Illuminate\Console\Command;

use App\Events\Wechat\UserList;
use App\Events\Wechat\UserInfoList;

use App\Models\WechatUser;
use App\Models\WechatMenu;

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
            'menu',
        ];
        do{
            while(!in_array($module, $modules)){
                $module = $this->choice("请选择要操作的模块", $modules, 0);
            }
            $moduleMethodName = "{$module}Module";
            $this->$moduleMethodName();
            // TODO 继续操作此账户的其他模块（do 参数被复用）
        }while( false && $this->confirm("继续操作此账户的其他模块吗？") && !($module = ''));
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
        $openids = WechatUser::where([
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

    protected function menuModule(){
        $this->info("微信自定义菜单管理 ...");
        $do = $this->argument('do');
        $doList = [
            'get',
            'create',
            'delete',
        ];
        while(!in_array($do, $doList)){
            $do = $this->choice('请选择要执行的操作', $doList, 0);
        }
        $doMethod = 'menu' . ucfirst($do);
        $this->$doMethod($do);
    }

    protected function menuGet(){
        foreach([
            'list',// 读取（查询）已设置菜单
            'current',// 获取当前菜单
        ] as $type){
            $data = $this->wechatApp->menu->$type();
            $wechatMenu = new WechatMenu();
            $wechatMenu->app_id = $this->wechatApp->config->app_id;
            $wechatMenu->app_type = $this->wechatApp->config->app_type;
            $wechatMenu->type = $type;
            $wechatMenu->data = $data;
            $wechatMenu->save();
            $this->info(json_encode($wechatMenu->toArray()));
        }
    }

    protected function menuCreate(){
        // TODO 个性化菜单条件
        $matchRule = [
            // "tag_id" => "2",
            // "sex" => "1",
            // "country" => "中国",
            // "province" => "广东",
            // "city" => "广州",
            // "client_platform_type" => "2",
            // "language" => "zh_CN"
        ];

        $wechatMenu = WechatMenu::where([
                'type' => 'list',
            ])
            ->whereJsonLength('data->menu', '>', 0)
            ->orderBy('id', 'desc')->first();
        $buttons = array_get($wechatMenu->data, 'menu.button', []);
        $menuCreateResult = $this->wechatApp->menu->create($buttons, $matchRule);
        $this->info(json_encode($menuCreateResult));
    }

    public function menuDelete(){
        $menuId = null; // TODO 删除个性化菜单时用，ID 从查询接口获取，默认删除全部
        if($this->confirm("确定要删除自定义菜单吗？")){
            $this->wechatApp->menu->delete($menuId); 
        }
    }

    public function menuTest($userId){
        // $userId 可以是粉丝的 OpenID，也可以是粉丝的微信号。
        // 测试个性化菜单
        $this->wechatApp->menu->match($userId);
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
