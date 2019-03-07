<?php
namespace App\Http\Controllers\api;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Request;

class FcmController extends Controller
{

    const FCM_PUSH_URL = 'https://fcm.googleapis.com/fcm/send';

    const FCM_KEY = '';

    public static function goPushNotification($push_token, $send_message, $image_url, $badge, $target_page) {

        if (!empty($image_url)) {
            if (empty($send_message)) {
                $send_message = "已傳送圖片";
            }
        }

        $fields = array (
            "notification"  => array(
                //"title"   => "iSearch Message",
                "text"  => "$send_message",
                "sound" => "default",
                "badge" => $badge,
                "click_action" => "$target_page" // should match to your intent filter
            ),
            "data"=> array(
                "page" => "$target_page",
//                "id" => "$target_id", //you can get this data as extras in your activity and this data is optional
                "image" => "$image_url"
            ),
            "to"=> "$push_token", // also can replace to key
            "mutable_content" => true  // IOS 10+ 專用參數
        );

        $client = new Client();

        $headers = array (
            'Content-type' => 'application/json; charset=utf-8',
            'Accept'=> 'application/json',
            'Authorization' => 'key='.self::FCM_KEY
        );

        $request = new Request("POST", self::FCM_PUSH_URL, $headers, json_encode($fields));
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



//    public function insertResponseLog($toBind, $rowsSQL) {
//        require_once("conn.php");
//        $db_connection = getDBConnection();
//        $sql = "INSERT INTO `log` (message_id, multicast_id, success, failure, canonical_ids, results) VALUES " . implode(", ", $rowsSQL);
//        $stmt = $db_connection->prepare($sql);
//        foreach($toBind as $param => $val) {
//            $stmt->bindValue($param, $val);
//        }
//        $stmt->execute();
//        $db_connection = null;
//    }

}

