<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Jobs\XstatisticsLinkQueue;
use App\Jobs\XstatisticsResourcesQueue;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});


Route::get('/test/db', function () {
    dd(\App\Models\User::first()->get()->toArray());
});
Route::get('/test/ip', function (Request $request) {
    $ip = $request->header('x-forwarded-for')??$request->ip();
    dd($ip);
});

// 防失联2重备案域名跳转链接 go.url/s=share
// 127.0.0.1:8000/s?url=https://google.com/404?query=s&tag=test
// 127.0.0.1:8000/s?url=https://google.com/404?query=s%26tag=test
Route::get('/s', function (Request $request) {
    $url = $request->query('url');
    $status = 302;
    $headers = ['referer' => $url];

    // TODO: 统计数据 GA or influxdb！ or Redis counts
    // $ip = $request->header('x-forwarded-for')??$request->ip();
    // XstatisticsLinkQueue::dispatchAfterResponse($ip, $url, $data);
    
    // table:
    // IP: 127.0.0.1 url: https://go.url.xxx count=1
    return redirect()->away($url, $status, $headers);
});