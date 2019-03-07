<?php
/**
 * Created by PhpStorm.
 * User: bryan.yen
 * Date: 2018/12/4
 * Time: 10:24 AM
 */

namespace App\Repositories;

use App\Http\Controllers\api\Ite2SmsApiController;
use Illuminate\Support\Facades\DB;
use stdClass;

class Ite2SmsDataRepository {
    public function index($messageId)
    {
        $dataReq = DB::table('cloud_message_data')
            ->select('*')
            ->join('cloud_message_status', 'cloud_message_data.status_id', '=', 'cloud_message_status.id')
            ->where('message_id','=',$messageId)
            ->get();

        if (!$dataReq) {
            return null;
        }

        return self::mappingData($dataReq);
    }

    public function create(array $data)
    {
        return DB::table('cloud_message_data')->insertGetId($data);
    }

    public function find($messageId, $id)
    {
        $dataReq = DB::table('cloud_message_data')
            ->select('*')
            ->where('cloud_message_data.message_id', '=', $messageId)
            ->where('cloud_message_data.id', '=', $id)
            ->join('cloud_message_status', 'cloud_message_data.status_id', '=', 'cloud_message_status.id')
            ->get();

        if (!$dataReq) {
            return null;
        }

        return self::mappingData($dataReq);
    }

    private function mappingData($dataReq) {
        $newArray = Array();

        foreach ($dataReq as $data) {
            $errorCodeId = $data->error_code_id;
            $checkModeId = $data->check_mode_id;
            $rowId = $data->row_id;

            // mapping error code table
            $errorCodeMessageReq = Ite2SmsStatusRepository::getErrorCodeMessageById($errorCodeId);
            foreach ($errorCodeMessageReq as $errorCodeMessage) {
                $data->error_code_message = $errorCodeMessage->message;
            }

            // check mode from ite2 sms
            if (!empty($rowId) && empty($checkModeId)) {
                $smsStatusData = Ite2SmsApiController::postSmsStatusRequest($data->row_id);
                if ($smsStatusData) {
                    // response get JSON
                    $jsonData = json_decode($smsStatusData, true);
                    if ($jsonData != null) {
                        $errorCode = (int)$jsonData['ErrorCode'];
                        if ($errorCode == 0) {
                            $checkMode = $jsonData['CheckMode'];

                            $checkModeReq = Ite2SmsStatusRepository::getCheckMode($checkMode);
                            if($checkModeReq) {
                                foreach ($checkModeReq as $checkModeReqItem) {
                                    // update status db
                                    (new Ite2SmsStatusRepository)->update($data->status_id, $checkModeReqItem->id);

                                    $data->check_mode_message = $checkModeReqItem->message;
                                }
                            }
                        } else {
                            // get error message from db table
                            $errorCodeMessageReq = Ite2SmsStatusRepository::getCheckModeErrorCodeMessage($errorCode);
                            if ($errorCodeMessageReq) {
                                foreach ($errorCodeMessageReq as $errorCodeMessage) {
                                    $data->check_mode_message = $errorCodeMessage->message;
                                }
                            }
                        }
                    }
                }
            } else {
                // mapping check mode table
                $checkModeMessageReq = Ite2SmsStatusRepository::getCheckModeMessage($checkModeId);
                foreach ($checkModeMessageReq as $checkModeMessage) {
                    $data->check_mode_message = $checkModeMessage->message;
                }
            }

            // create output data object
            $newData = new stdClass();
            $newData->id = $data->id;
            $newData->message_id = $data->message_id;
            $newData->user_id = $data->user_id;
            $newData->is_push = $data->is_push;
            $newData->status_id = $data->status_id;
            $newData->row_id = $data->row_id;
            $newData->error_code_message = $data->error_code_message;
            if (!empty($data->check_mode_message)) {
                $newData->check_mode_message = $data->check_mode_message;
            } else {
                $newData->check_mode_message = '';
            }

            $newArray[] = $newData;
        }

        return $newArray;
    }
}