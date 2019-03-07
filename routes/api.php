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

// 瀏覽網站根目錄時提示訊息
//Route::get('/', 'WebController@wellcome');

// *******************************
// 前台 API
// http://{donaim}/community/{community_id}/user/{user_id}
// *******************************
// 網址前綴需帶入 community_id 及 user_id
Route::group([
    'prefix' => 'message/{message_id}',
    'as' => 'api.'
], function () {
    // *** 簡訊列表 ***
    Route::get('cloud_message', 'api\Ite2SmsController@index');
    // *** 簡訊列表By id ***
    Route::get('cloud_message/{id}', 'api\Ite2SmsController@show');
    // *** 新增簡訊 ***
    Route::post('cloud_message', 'api\Ite2SmsController@store');
////    // *** 查詢包裹狀態 ***
////    Route::get('packageStatus/{order_from_id}', 'api\PackageInfoController@getPackageInfoByTrackingId')->where('order_from_id','[0-9]+');
});
