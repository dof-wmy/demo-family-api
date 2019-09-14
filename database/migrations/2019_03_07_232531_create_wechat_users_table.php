<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('openid', 64)->index();
            $table->string('unionid', 64)->index()->nullable();
            $table->string('app_id', 64)->index();
            $table->string('app_type', 64);
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->json('detail')->nullable();
            $table->string('nickname', 64)->nullable();
            $table->string('headimgurl')->nullable();
            $table->timestamps();
            $table->unique(['openid', 'app_id', 'app_type'], 'unique_openid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wechat_users');
    }
}
