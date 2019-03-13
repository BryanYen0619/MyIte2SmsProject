<?php

namespace App\Http\Controllers\api;

use App\Repositories\Ite2SmsDataRepository;
use App\Repositories\Ite2SmsStatusRepository;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Self_;

class Ite2SmsController extends Controller {

    protected $dataRepo;

    public function __construct(Ite2SmsDataRepository $dataRepo) {
        $this->dataRepo = $dataRepo;
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function index($mesageId) {
        $sqlData = $this->dataRepo->index($mesageId);
        if ($sqlData) {
            // 檢查SMS發送狀態
            $smsResponseData = self::getSmsSendResponseStatus($sqlData);

            return response()->json(['data' => $smsResponseData]);
        } else {
            return response()->json(['data' => array()]);
        }
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function show($mesageId, $id) {
        $sqlData = $this->dataRepo->find($mesageId, $id);
        if ($sqlData) {
            // 檢查SMS發送狀態
            $smsResponseData = self::getSmsSendResponseStatus($sqlData);

            return response()->json(['data' => $smsResponseData]);
        } else {
            return response()->json(['data' => array()]);
        }
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function store($messageId, Request $request) {
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
        $count = 0;
        $responseList = array();
        for ($i = 0; $i < count($jsonOriginData); $i++) {
            $userId = $jsonOriginData[$i]['user_id'];
            $phone = $jsonOriginData[$i]['phone'];

            // SMS
            if ($tempUserId != $userId) {
                // 判斷imageUrl是否有資料，沒資料才發SMS
                if (empty($imageUrl)) {
                    $smsResponse = self::sendSMS($messageId, $userId, $phone, $message);
                    if ($smsResponse == null) {
                        $smsResponse = array_add($smsResponse, 'send_status_code', 601);
                        $smsResponse = array_add($smsResponse, 'send_status', "SMS發送失敗");
                    }
                } else {
                    $smsResponse = Array();
                    $smsResponse = array_add($smsResponse, 'send_status_code', 602);
                    $smsResponse = array_add($smsResponse, 'send_status', "SMS不發送圖片");
                }
                $insertId = self::insertDataDB($messageId, $userId, $smsResponse);
                  
                $response = array();
                $response = array_add($response, 'errorCode', $smsResponse['send_status_code']);
                $response = array_add($response, 'message', $smsResponse['send_status']);
                $response = array_add($response, 'smsId', $insertId);
                $responseList[$count] = $response;
                $count++;
                
                $tempUserId = $userId;
                
            }
        }

        return response()->json(['data'=>$responseList], 200);
    }

    private function insertDataDB($messageId, $userId, $responseData) {
        $responseData = array_add($responseData, 'message_id', $messageId);
        $responseData = array_add($responseData, 'user_id', $userId);

        // create data
        $dataId = $this->dataRepo->create($responseData);
        if ($dataId) {
            return $dataId;
        } else {
            return -1;
        }
    }
    
    private function updateDB($id, $smsResponseStatusCode, $smsResponseStatusMessage) {
        // update data
        $this->dataRepo->update($id, $smsResponseStatusCode, $smsResponseStatusMessage);
        return null;
    }

    private function sendSMS($messageId, $userId, $phone, $message) {
        // 測試發送成功
//        $postSmsRequest = "{
//\"RowId\":\"201812270\", \"Cnt\":\"1\", \"ErrorCode\":\"0\"
//}";
        // 測試空資料
//        $postSmsRequest = "{}";
        // 測試Error Code
//        $postSmsRequest = "{\"ErrorCode\":\"19\"}";
        // post ite2 Sms Api
        $postSmsRequest = Ite2SmsApiController::postSmsRequest($phone, $message);
        // response get JSON
        $jsonData = json_decode($postSmsRequest, true);
        if ($jsonData != null) {
            $errorCode = (int) $jsonData['ErrorCode'];
            $responseData = Array();

            if ($errorCode == 0) {
                $responseData = array_add($responseData, 'row_id', (int) $jsonData['RowId']);
                $responseData = array_add($responseData, 'count', $jsonData['Cnt']);
            }

            $statusMessage = '';
            switch ($errorCode) {
                case 0 :
                    $statusMessage = '簡訊已發至 SMS server';
                    break;
                case 1 :
                    $statusMessage = '傳入參數有誤';
                    break;
                case 2 :
                    $statusMessage = '帳號/密碼錯誤';
                    break;
                case 3 :
                    $statusMessage = '電話號碼格式錯誤';
                    break;
                case 4 :
                    $statusMessage = '帳號已遭暫停使用';
                    break;
                case 6 :
                    $statusMessage = '不允許的 IP';
                    break;
                case 7 :
                    $statusMessage = '預約時間錯誤';
                    break;
                case 9 :
                    $statusMessage = '簡訊內容為空白';
                    break;
                case 10 :
                    $statusMessage = '資料庫存取或系統錯誤';
                    break;
                case 11 :
                    $statusMessage = '餘額已為 0';
                    break;
                case 12 :
                    $statusMessage = '超過長簡訊發送字數';
                    break;
                case 13 :
                    $statusMessage = '電話號碼為黑名單';
                    break;
                case 14 :
                    $statusMessage = '僅接受 POST method';
                    break;
                case 15 :
                    $statusMessage = '指定發送代碼無效';
                    break;
                case 16 :
                    $statusMessage = '失效時間錯誤';
                    break;
                case 17 :
                    $statusMessage = '沒有權限使用 API';
                    break;
                case 19 :
                    $statusMessage = '查無資料';
                    break;
                default :
                    break;
            }

            $responseData = array_add($responseData, 'send_status_code', $errorCode);
            $responseData = array_add($responseData, 'send_status', $statusMessage);

//            if ($errorCode == 0) {
//                $dataId = self::insertDataDB($messageId, $userId, $responseData);
//                if ($dataId) {
//                    return $dataId;
//                }
//            } else {
//                return $responseData;
//            }
            return $responseData;
        } else {
            return null;
        }
    }

    private function getSmsSendResponseStatus($dataReq) {
        foreach ($dataReq as $data) {
            $rowId = $data->row_id;

            // check mode from ite2 sms
            if (!empty($rowId)) {
                $smsStatusData = Ite2SmsApiController::postSmsStatusRequest($rowId);
                if ($smsStatusData) {
                    // response get JSON
                    $jsonData = json_decode($smsStatusData, true);
                    if ($jsonData != null) {
                        $errorCode = (int) $jsonData['ErrorCode'];
                        if ($errorCode == 0) {
                            $smsResponseStatusCode = $jsonData['CheckMode'];

                            $smsResponseStatusMessage = '';
                            switch ($smsResponseStatusCode) {
                                case 'CE':
                                    $smsResponseStatusMessage = '指定特碼無效';
                                    break;
                                case 'CK':
                                    $smsResponseStatusMessage = '已處理';
                                    break;
                                case 'CM':
                                    $smsResponseStatusMessage = '客戶拒收商務簡訊';
                                    break;
                                case 'CS':
                                    $smsResponseStatusMessage = '無法送達';
                                    break;
                                case 'E1':
                                    $smsResponseStatusMessage = '電話號碼錯誤';
                                    break;
                                case 'E2':
                                    $smsResponseStatusMessage = '黑名單';
                                    break;
                                case 'E3':
                                    $smsResponseStatusMessage = '特碼錯誤';
                                    break;
                                case 'E4':
                                    $smsResponseStatusMessage = '電話號碼異常';
                                    break;
                                case 'EM':
                                    $smsResponseStatusMessage = '空號';
                                    break;
                                case 'EP':
                                    $smsResponseStatusMessage = '空白簡訊';
                                    break;
                                case 'ER':
                                    $smsResponseStatusMessage = '通訊錯誤/發送失敗';
                                    break;
                                case 'EV':
                                    $smsResponseStatusMessage = '簡訊內容含無效變數';
                                    break;
                                case 'EX':
                                    $smsResponseStatusMessage = '逾時不發';
                                    break;
                                case 'FD':
                                    $smsResponseStatusMessage = '已送達電信業者';
                                    break;
                                case 'LT':
                                    $smsResponseStatusMessage = '宵禁';
                                    break;
                                case 'NA':
                                    $smsResponseStatusMessage = '不被放行';
                                    break;
                                case 'ND':
                                    $smsResponseStatusMessage = '資料過期/無此資料';
                                    break;
                                case 'NI':
                                    $smsResponseStatusMessage = '指定 ISP 未啟用';
                                    break;
                                case 'NP':
                                    $smsResponseStatusMessage = '點數不足';
                                    break;
                                case 'OF':
                                    $smsResponseStatusMessage = '未開機/收不到訊號';
                                    break;
                                case 'OK':
                                    $smsResponseStatusMessage = '發送成功';
                                    break;
                                case 'SD':
                                    $smsResponseStatusMessage = '已發送';
                                    break;
                                case 'SE':
                                    $smsResponseStatusMessage = '發送錯誤/發送失敗';
                                    break;
                                case 'ST':
                                    $smsResponseStatusMessage = '已預約';
                                    break;
                                case 'TE':
                                    $smsResponseStatusMessage = '簡訊內容無法送達';
                                    break;
                                case 'WT':
                                    $smsResponseStatusMessage = '等待放行';
                                    break;
                                default :
                                    break;
                            }

                            $data->response_status_code = $smsResponseStatusCode;
                            $data->response_status = $smsResponseStatusMessage;
                            
                            self::updateDB($data->id, $smsResponseStatusCode, $smsResponseStatusMessage);
                        }
                    }
                }
            }
        }

        return $dataReq;
    }

}
