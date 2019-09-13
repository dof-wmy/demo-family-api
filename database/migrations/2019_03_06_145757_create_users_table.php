<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 64)->unique()->comment('用户名');
            $table->bigInteger('register_source_id')->nullable();
            $table->string('register_source_type', 64)->nullable();
            $table->string('email', 32)->unique()->nullable();
            $table->string('mobile', 16)->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('name', 64)->nullable()->comment('姓名');
            $table->json('avatar')->nullable();
            $table->unsignedBigInteger('inviter_id')->nullable();
            $table->double('longitude')->nullable();
            $table->double('latitude')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->unsignedTinyInteger('blacklist')->nullable();
            $table->timestamps();
            $table->unique(['register_source_id', 'register_source_type'], 'unique_register_source');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
