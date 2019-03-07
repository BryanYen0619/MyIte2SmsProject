<?php
/**
 * Created by PhpStorm.
 * User: bryan.yen
 * Date: 2018/12/4
 * Time: 10:26 AM
 */


namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Ite2SmsStatusRepository {

    public function create(array $data)
    {
        return DB::table('cloud_message_status')->insertGetId($data);
    }

    public function update($id, $checkModeId)
    {
        return DB::table('cloud_message_status')
            ->where('id', '=', $id)
            ->update(['check_mode_id' => $checkModeId]);
    }

    public static function getErrorCodeMessageById($id)
    {
        return DB::table('ite2_sms_error_code')
            ->select('message')
            ->where('id', '=', $id)
            ->where('type', '=', 'Add')
            ->get();
    }

    public static function getErrorCodeMessage($code)
    {
        return DB::table('ite2_sms_error_code')
            ->select('message')
            ->where('code', '=', $code)
            ->where('type', '=', 'Add')
            ->get();
    }

    public static function getErrorCodeId($code)
    {
        return DB::table('ite2_sms_error_code')
            ->select('id')
            ->where('code', '=', $code)
            ->where('type', '=', 'Add')
            ->get();
    }

    public static function getCheckModeMessage($id)
    {
        return DB::table('ite2_sms_check_mode')
            ->select('messages')
            ->where('id', '=', $id)
            ->get();
    }

    public static function getCheckMode($checkMode)
    {
        return DB::table('ite2_sms_check_mode')
            ->select('id','message')
            ->where('code', '=', $checkMode)
            ->get();
    }

    public static function getCheckModeErrorCodeMessage($code)
    {
        return DB::table('ite2_sms_error_code')
            ->select('message')
            ->where('code', '=', $code)
            ->where('type', '=', 'Status')
            ->get();
    }

    public static function updatePublishedDate($messageId)
    {
        // 日期格式轉換
        $date = Carbon::now()->toDateTimeString();

        return DB::table('messages')
            ->where('id', '=', $messageId)
            ->update(['published_at' => $date]);
    }
}