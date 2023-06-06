<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Jobs\InfluxQueue;

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

// http://127.0.0.1:8000/redirect?target=https://*.com/@fwdforward/7XFVL5o.m4a?metric=connect%26category=601%26bot=4
// metric:默认是connect 收听/看/点击链接
// by：author 可选 %26author=@fwdforward
Route::get('/redirect', function (Request $request) {
    $url = $request->query('target');
    $status = 302;
    $headers = ['referer' => $url];
    $ip = $request->header('x-forwarded-for')??$request->ip();
    $parts = parse_url($url); //$parts['host']
    // $paths = pathinfo($url); //mp3
    $url = strtok($url, '?'); //remove ?q=xxx
    $target = basename($url); //cc201221.mp3
    
    $data = [];
    if(isset($parts['query'])) parse_str($parts['query'], $data);
    
    // measurement/metric
    if(!isset($data['metric'])) $data['metric'] = 'connect';
    $metric = $data['metric'].",";unset($data['metric']); // ly-wechat
    $tags = http_build_query($data, '', ',');// category=603,bot=4

    $protocolLine = $metric.$tags.' count=1i,target="'.$target.'",ip="'.$ip.'"';
    // ly-listen,category=603,bot=%E5%8F%8B4count=1i,target="ee230909.mp3"
    // TODO Statistics BY IP / BY target.
    // dd($protocolLine,$parts,$url,$ip);
    InfluxQueue::dispatchAfterResponse($protocolLine);
    return redirect()->away($url, $status, $headers);
});