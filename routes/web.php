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

//Route::get('/', function () {
//    return view('welcome');
//});


Route::get('/balance', 'UserController@balance');
Route::post('/deposit', 'UserController@deposit');
Route::post('/withdraw', 'UserController@withdraw');
Route::post('/transfer', 'UserController@transfer');