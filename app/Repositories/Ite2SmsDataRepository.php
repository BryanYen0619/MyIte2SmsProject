<?php

/**
 * Created by PhpStorm.
 * User: bryan.yen
 * Date: 2018/12/4
 * Time: 10:24 AM
 */

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class Ite2SmsDataRepository {

    public function index($messageId) {
        $dataReq = DB::table('ite2_sms_log')
                ->select('*')
                ->where('message_id', '=', $messageId)
                ->get();

        if (!$dataReq) {
            return null;
        }

        return $dataReq;
    }

    public function create(array $data) {
        return DB::table('ite2_sms_log')->insertGetId($data);
    }

    public function find($messageId, $id) {
        $dataReq = DB::table('ite2_sms_log')
                ->select('*')
                ->where('message_id', '=', $messageId)
                ->where('id', '=', $id)
                ->get();

        if (!$dataReq) {
            return null;
        }

        return $dataReq;
    }
    
    public function update($id, $smsResponseStatusCode, $smsResponseStatusMessage)
    {
        $dataReq = DB::table('ite2_sms_log')
            ->where('id', '=', $id)
            ->update(['response_status_code' => $smsResponseStatusCode, 'response_status' => $smsResponseStatusMessage]);
        
        if (!$dataReq) {
            return null;
        }

        return $dataReq;
    }
}
