<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    abort(404);
    // return view('welcome');
});

// Auth::routes();

Route::get('auth/login/socialite/{driver}', 'Auth\LoginController@socialiteRedirectToProvider');
Route::get('auth/login/socialite/{driver}/callback', 'Auth\LoginController@socialiteHandleProviderCallback');

// Route::get('/home', 'HomeController@index')->name('home');
