<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;
use App\Models\AdminUser;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化';

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
        $this->info("应用初始化 ===>>\n");

        $this->info('生成加密密钥...');
        $this->call('key:generate');

        $this->info('生成 JWT 加密密钥...');
        $this->call('jwt:secret');

        $this->info('运行数据库迁移...');
        $this->call('migrate');

        $this->info('后台：新增管理组...');
        $adminRoleGuardName = (new AdminUser())->guard_name;
        foreach([
            'super-admin', // 超级管理员
            'admin',
            'editor',
        ] as $adminRoleName){
            $this->call("permission:create-role", [
                'name'  => $adminRoleName,
                'guard' => $adminRoleGuardName,
            ]);
        }
        $this->info('后台：新增权限...');
        foreach([
            'get_admin_user',
            'post_admin_user',
            'delete_admin_user',
            'get_admin_group',
            'post_permission_of_admin_group',
            'delete_permission_of_admin_group',
        ] as $adminPermissionName){
            $this->call("permission:create-permission", [
                'name'  => $adminPermissionName,
                'guard' => $adminRoleGuardName,
            ]);
        }
        $this->info('后台：新建管理员...');
        $this->call('admin_user', [
            'do' => 'new',
        ]);
        $this->info('后台：管理员分配角色...');
        $this->call('admin_user', [
            'do' => 'role',
        ]);

        $this->info('运行数据填充...');
        $this->call('db:seed');
        $this->info("\n<<<=== 应用初始化完成");
    }
}
