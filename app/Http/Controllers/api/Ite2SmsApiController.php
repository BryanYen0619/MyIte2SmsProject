<?php
/**
 * Created by PhpStorm.
 * User: bryan.yen
 * Date: 2018/12/13
 * Time: 11:45 AM
 */

namespace App\Http\Controllers\api;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Request;

class Ite2SmsApiController extends Controller
{

    // 銓力SMS 帳戶
    const ITE2_SMS_ACCOUNT = '';

    // 銓力SMS 密碼
    const ITE2_SMS_PASSWORD = '';

    public static function postSmsRequest($phone, $message)
    {

        // mapping POST data
        $postData = array(
            'UID' => self::ITE2_SMS_ACCOUNT,
            'Pwd' => base64_encode(self::ITE2_SMS_PASSWORD),
            'DA' => $phone,
            'SM' => $message
        );

        $client = new Client();

        // set Header
        $headers = array(
            'Content-type' => 'application/json; charset=utf-8',
            'Accept'=> 'application/json'
        );

        $url = 'http://smsc.ite2.com.tw/ApiSMSC/Sms/SendSms';

        $request = new Request("POST", $url, $headers, json_encode($postData));
        try {
            $response = $client->send($request, ['timeout' => 30]);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                // Error
                $response = response()->json(["Request To Server Error."]);
            } else {
                $response = $response->getBody()->getContents();
            }
        } catch (GuzzleException $e) {
            $response = response()->json(["Request To Server Error"=> $e->getMessage()]);
        }

        return $response;
    }

    public static function postSmsStatusRequest($rowId)
    {

        // mapping POST data
        $postData = array(
            'UID' => self::ITE2_SMS_ACCOUNT,
            'Pwd' => base64_encode(self::ITE2_SMS_PASSWORD),
            'RowId' => $rowId
        );

        $client = new Client();

        // set Header
        $headers = array(
            'Content-type' => 'application/json; charset=utf-8',
            'Accept'=> 'application/json'
        );

        $url = 'http://smsc.ite2.com.tw/ApiSMSC/Sms/QuerySmsDr';

        $request = new Request("POST", $url, $headers, json_encode($postData));
        try {
            $response = $client->send($request, ['timeout' => 30]);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                // Error
                $response = response()->json(["Request To Server Error."]);
            } else {
                $response = $response->getBody()->getContents();
            }
        } catch (GuzzleException $e) {
            $response = response()->json(["Request To Server Error"=> $e->getMessage()]);
        }

        return $response;
    }
}