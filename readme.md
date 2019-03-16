# 使用说明

## 命令

### composer install

composer install -vvv

### 生成加密密钥

php artisan key:generate

### 生成 JWT 加密密钥

php artisan jwt:secret

### 数据库迁移

php artisan migrate

### 后台：新增超级管理员角色

php artisan permission:create-role super-admin admin_user

### 后台：新增权限

php artisan permission:create-permission get_admin_user admin_user

php artisan permission:create-permission post_admin_user admin_user

php artisan permission:create-permission delete_admin_user admin_user

php artisan permission:create-permission get_admin_group admin_user

php artisan permission:create-permission post_permission_of_admin_group admin_user

php artisan permission:create-permission delete_permission_of_admin_group admin_user

### 后台：新建管理员

php artisan admin_user new

### 后台：管理员分配角色

php artisan admin_user role