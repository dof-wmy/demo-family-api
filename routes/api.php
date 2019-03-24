<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * @var $api \Dingo\Api\Routing\Router
 */
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', [
    'middleware' => [
        'api.throttle',
    ],
    'limit'     => config('api.throttle.limit'),
    'expires'   => config('api.throttle.expires'),
    'namespace' => 'App\\Http\\Controllers\\Api',
], function ($api) {
    $api->group([
        'prefix' => 'auth',
    ], function ($api) {
        $api->post('login', 'AuthController@login');
        $api->post('wechat_login', 'AuthController@loginByWechat');
        $api->post('logout', 'AuthController@logout');
        $api->post('refresh', 'AuthController@refresh');
        $api->post('me', 'AuthController@me');
        $api->post('me/update', 'AuthController@updateMe');
    });
    $api->post('feedback', 'FeedbackController@store');
    $api->post('announcements/read', 'Announcementcontroller@read');
    $api->post('notices/read', 'Noticecontroller@read');

    // 后台
    $api->group([
        'prefix' => 'admin',
        'namespace' => 'Admin',
    ], function ($api) {
        $api->group([
            'prefix' => 'auth',
        ], function ($api) {
            $api->post('login', 'AuthController@login');
            $api->post('logout', 'AuthController@logout');
            $api->post('refresh', 'AuthController@refresh');
            $api->post('me', 'AuthController@me');
            $api->post('me/update', 'AuthController@updateMe');
        });

        $api->get('admin_users', 'AdminUserController@index');
        $api->post('admin_users/{id?}', 'AdminUserController@saveAdminUser');
        $api->delete('admin_users', 'AdminUserController@deleteAdminUser');
        $api->get('admin_groups', 'AdminGroupController@index');
        $api->post('admin_groups/{groupId}/permissions/{permissionId}', 'AdminGroupController@givePermission');
        $api->delete('admin_groups/{groupId}/permissions/{permissionId}', 'AdminGroupController@revokePermission');
        $api->get('users', 'UserController@index');
        $api->post('users/{ids}/blacklist', 'UserController@blacklist');
        $api->delete('users/{ids}/blacklist', 'UserController@unblacklist');
        $api->get('feedback', 'FeedbackController@index');
        $api->get('announcements', 'AnnouncementController@index');
        $api->post('announcements', 'AnnouncementController@store');
    });
});