# 使用说明

## 命令

### 生成加密密钥

php artisan key:generate

### 生成 JWT 加密密钥

php artisan jwt:secret

### 数据库迁移

php artisan migrate

### 新建管理员

php artisan admin_user new

### 新增超级管理员角色

php artisan permission:create-role super-admin admin_user