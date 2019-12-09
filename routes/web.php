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

Route::get('/', 'AiController@index');
Route::post('/recommend', 'IndexController@recommend');
Route::get('/index', 'IndexController@index');
Route::post('/loginFB', 'IndexController@loginFB');
Route::post('/login', 'IndexController@login');
Route::post('/recommend-item', 'IndexController@recommendItem');

