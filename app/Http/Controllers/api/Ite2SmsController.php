<?php

namespace App\Http\Controllers\api;

use App\Repositories\Ite2SmsDataRepository;
use App\Repositories\Ite2SmsStatusRepository;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Self_;

class Ite2SmsController extends Controller
{

    protected $dataRepo;

    public function __construct(Ite2SmsDataRepository $dataRepo)
    {
        $this->dataRepo = $dataRepo;
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function index($mesageId)
    {
        $sqlData = $this->dataRepo->index($mesageId);
        return response()->json(['data' => $sqlData]);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function show($mesageId, $id)
    {
        $sqlData = $this->dataRepo->find($mesageId, $id);
        return response()->json(['data' => $sqlData]);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function store($messageId, Request $request)
    {
        if (empty($messageId)) {
            return response()->json(['errorCode' => 501, 'errorMessage' => '請指定message id'], 403);
        }

        if (empty($request)) {
            return response()->json(['errorCode' => 502, 'errorMessage' => '參數不足'], 403);
        }

        $dataList = json_decode($request->getContent(), true);

        if (empty($dataList)) {
            return response()->json(['errorCode' => 503, 'errorMessage' => 'Json解析失敗'], 403);
        }

        $jsonOriginData = $dataList['user'];

        if (isset($dataList['image_url'])) {
            $imageUrl = $dataList['image_url'];
        } else {
            $imageUrl = null;
        }

        if (isset($dataList['content'])) {
            $message = $dataList['content'];
        } else {
            $message = "";
        }

        $tempUserId = -1;
        for ($i = 0; $i < count($jsonOriginData); $i++) {
            $userId = $jsonOriginData[$i]['user_id'];
            $type = $jsonOriginData[$i]['is_push'];
            $phone = $jsonOriginData[$i]['phone'];

            // SMS
            if ($type == 0) {
                if ($tempUserId != $userId) {
                    // 判斷imageUrl是否有資料，沒資料才發SMS
                    if (empty($imageUrl)) {
                        $dataId = self::sendSMS($messageId, $userId, $phone, $message);
                        if ($dataId == -1) {
                            $responseData = Array();
                            self::insertLog($messageId, $userId, $responseData, $type, 0, "SMS發送失敗");
                        }
                    } else {
                        $responseData = Array();
                        self::insertLog($messageId, $userId, $responseData, $type, 0, "SMS不發送圖片");
                    }

                    $tempUserId = $userId;
                }
            } else if ($type == 1) {    // FCM
                $token = $jsonOriginData[$i]['token'];

                $targetPage = "";
                if (isset($jsonOriginData[$i]['target_page'])) {
                    $targetPage = $jsonOriginData[$i]['target_page'];
                }

                $badge = 0;
                if (isset($jsonOriginData[$i]['badge'])) {
                    $badge = $jsonOriginData[$i]['badge'];
                }

                $postFcmRequest = FcmController::goPushNotification($token, $message, $imageUrl, $badge, $targetPage);
                // response get JSON
                $jsonData = json_decode($postFcmRequest, true);
                if ($jsonData != null) {
                    $successCode = (int)$jsonData['success'];
                    $errorCode = (int)$jsonData['failure'];
                    $responseData = Array();
                    $responseData = array_add($responseData, 'multicast_id', $jsonData['multicast_id']);

                    if ($successCode == 1 && $errorCode == 0) {
                        $responseData = array_add($responseData, 'count', 1);
                        self::insertLog($messageId, $userId, $responseData, $type, 18);
                    } else {
                        // 推波發送失敗切換SMS
                        // 判斷imageUrl是否有資料，沒資料才發SMS
//                        if (empty($imageUrl)) {
//                            $dataId = self::sendSMS($messageId, $userId, $phone, $message);
//                            if ($dataId == -1) {
//                                self::insertLog($messageId,$userId,$responseData,$type, 0, "SMS發送失敗");
//                            }
//                        } else {
                        $resultJson = $jsonData['results'];
                        foreach ($resultJson as $item) {
                            $fcmErrorMessage = $item['error'];
                        }

                        if (!empty($fcmErrorMessage)) {
                            self::insertLog($messageId, $userId, $responseData, $type, 19, $fcmErrorMessage);
                        }
//                        }
                    }
                }
            }
        }

        return response()->json(['errorCode' => 0, 'message' => '執行完成'], 200);
    }

    private function insertStatusDB($responseData, $errorCode, $fcmErrorMessage = null)
    {
        // get error id from db table
        $errorCodeIdReq = Ite2SmsStatusRepository::getErrorCodeId($errorCode);
        if ($errorCodeIdReq) {

            $errorCodeId = -1;
            // get error id from error code
            foreach ($errorCodeIdReq as $item) {
                $errorCodeId = $item->id;
            }

            $responseData = array_add($responseData, 'error_code_id', $errorCodeId);
            if ($fcmErrorMessage != null) {
                $responseData = array_add($responseData, 'error_message', $fcmErrorMessage);
            }

            // create status data
            $statusId = (new \App\Repositories\Ite2SmsStatusRepository)->create($responseData);
            if ($statusId) {
                return $statusId;
            }
        }

        return null;
    }

    private function insertDataDB($type, $userId, $messageId, $statusId)
    {
        $insertData = Array();
        $insertData = array_add($insertData, 'message_id', $messageId);
        $insertData = array_add($insertData, 'user_id', $userId);
        $insertData = array_add($insertData, 'is_push', $type);
        $insertData = array_add($insertData, 'status_id', $statusId);

        // create data
        $dataId = $this->dataRepo->create($insertData);
        if ($dataId) {
            return $dataId;
        }
        return null;
    }

    private function sendSMS($messageId, $userId, $phone, $message)
    {
        // 測試發送成功
//        $postSmsRequest = "{
//\"RowId\":\"201812270\", \"Cnt\":\"1\", \"ErrorCode\":\"0\"
//}";
        // 測試空資料
//        $postSmsRequest = "{}";
        // 測試Error Code
//        $postSmsRequest = "{\"ErrorCode\":\"2\"}";

        // post ite2 Sms Api
        $postSmsRequest = Ite2SmsApiController::postSmsRequest($phone, $message);
        // response get JSON
        $jsonData = json_decode($postSmsRequest, true);
        if ($jsonData != null) {
            $errorCode = (int)$jsonData['ErrorCode'];
            $responseData = Array();

            if ($errorCode == 0) {
                $responseData = array_add($responseData, 'row_id', $jsonData['RowId']);
                $responseData = array_add($responseData, 'count', $jsonData['Cnt']);
            }

            self::insertLog($messageId, $userId, $responseData, 0, $errorCode);

            return 0;
        } else {
            return -1;
        }
    }

    private function insertLog($messageId, $userId, $responseData, $type, $errorCode, $errorMessage = null)
    {
        $statusId = self::insertStatusDB($responseData, $errorCode, $errorMessage);
        if ($statusId) {
            $dataId = self::insertDataDB($type, $userId, $messageId, $statusId);
            if ($dataId) {
                Ite2SmsStatusRepository::updatePublishedDate($messageId);
            }
        }
    }

}
