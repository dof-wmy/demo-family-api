<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\AdminUser as AdminUserModel;
use Spatie\Permission\Models\Role as RoleModel;

class AdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin_user {do}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '管理员';

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
        $do = $this->argument('do');
        if(in_array($do, [
            'new',
            'reset',
            'role',
        ])){
            $this->$do();
        }else{
            $this->error("参数错误");
        }
    }

    protected function new(){
        $this->comment("新建管理员...");

        $bar = $this->output->createProgressBar(2);
        $username = '';
        while(empty($username)){
            $username = $this->ask('请输入用户名');
            if(AdminUserModel::fieldExist($username, 'username')){
                $username = '';
                $this->error("用户名已存在！");
            }
        }
        $bar->advance();

        $password = '';
        while(empty($password)){
            $password = trim($this->secret('请输入密码'));
            $passwordConfirm = trim($this->secret('请再次输入密码'));
            if($password != $passwordConfirm){
                $password = '';
                $this->error("两次输入密码不一致");
            }
        }
        $bar->advance();

        try{
            $adminUser = AdminUserModel::create([
                'username'  => $username,
                'password'  => $password,
            ]);
            $bar->finish();
            $this->info("管理员 {$adminUser->username} 新建成功");
        }catch(\Exception $e){
            $this->error("管理员 {$adminUser->username} 生成失败");
        }
    }

    public function reset(){
        $this->comment("管理员重置密码...");

        while(empty($adminUser)){
            $username = $this->ask('请输入用户名');
            $adminUser = AdminUserModel::where('username', $username)->first();
            if(empty($adminUser)){
                $this->error("管理员 {$username} 不存在！");
            }
        }

        while(empty($password)){
            $password = trim($this->secret('请输入新密码'));
            $passwordConfirm = trim($this->secret('请再次输入密码'));
            if($password != $passwordConfirm){
                $password = '';
                $this->error("两次输入密码不一致");
            }
        }

        $adminUser->password = $password;
        $adminUser->save();
        $this->info("管理员 {$adminUser->username} 重置密码成功");
    }

    public function role(){
        $this->comment("管理员角色分配...");

        while(empty($adminUser)){
            $username = $this->ask('请输入管理员用户名');
            $adminUser = AdminUserModel::where('username', $username)->first();
            if(empty($adminUser)){
                $this->error("管理员 {$username} 不存在！");
            }
        }
        $this->roleManage($adminUser);
    }

    public function roleManage($user){
        while(empty($finish)){
            $userRoleNames = $user->getRoleNames();
            if(!blank($userRoleNames)){
                $this->table([
                    '已分配角色',
                ], $userRoleNames->map(function($roleName){
                    return [
                        '已分配角色' => $roleName,
                    ];
                }));
            }

            $roleMethod = $this->choice("请选择要进行的操作类型", [
                'assignRole',
                'removeRole'
            ], 0);
            if($roleMethod == 'assignRole'){
                $otherRoleNames = RoleModel::where('guard_name', $user->guard_name)
                    ->whereNotIn('name', $userRoleNames->toArray())
                    ->pluck('name');
                if(blank($otherRoleNames)){
                    $this->error('无任何可分配角色');
                }else{
                    $roleName = $this->choice('请选择要分配的角色', $otherRoleNames->toArray());
                    $user->assignRole($roleName);
                }
            }elseif($roleMethod == 'removeRole'){
                if(blank($userRoleNames)){
                    $this->error('无任何可移除角色');
                }else{
                    $roleName = $this->choice('请选择要移除的角色', $userRoleNames->toArray());
                    $user->removeRole($roleName);
                }
            }else{
                $this->error('错误的操作类型');
            }

            $finish = !$this->confirm('继续操作吗？');
        }

        // $user->syncRoles($roleNames);
        $this->info('操作完成✅');
    }

}
