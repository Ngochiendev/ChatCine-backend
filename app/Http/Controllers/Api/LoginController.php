<?php

namespace App\Http\Controllers\Api;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;

class LoginController extends Controller{
    public function login (Request $request){

        $validator = Validator::make($request->all(),[
            'avatar' =>'required',
            'name' =>'required',
            'type' =>   'required',
            'open_id' => 'required',
            'email' => 'max:50',
            'phone' =>  'max:30',
            
        ]);
        if($validator->fails()){
            return ['code' => -1, 'data' => 'no valid data', 'message' =>$validator->errors()->first()];
        }

        try {
            //code...
            $validated = $validator->validated();
        $map= [];
        $map['type']= $validated['type'];
        $map['open_id']= $validated['open_id'];
        $result = DB::table('users')->select('email', 'name', 'avatar', 'phone', 'description','type', 'token', 'access_token','online', 'expired_date',)
        ->where($map)->first();
        if(empty ($result)){
            $validated['token'] = md5(uniqid().rand(10000, 99999));
            $validated['created_at'] = Carbon::now('Asia/Ho_Chi_Minh');
            $validated['access_token'] = md5(uniqid().rand(1000000, 9999999));
            $validated['expired_date'] = Carbon::now('Asia/Ho_Chi_Minh')->addDays(30);
            $user_id = DB::table('users')->insertGetId($validated);
            $user_result = DB::table('users')->select('avatar', 'name', 'description','type', 'token', 'access_token','online', )
            ->where('id' , '=',$user_id)->first();
            return ['code' => 0, 'data' => $user_result, 'message' =>'User has been created'];
        }else{
            $access_token = md5(uniqid().rand(1000000, 9999999));
            $expired_date = Carbon::now('Asia/Ho_Chi_Minh')->addDays(30);
            DB::table('users')->where($map)->update([
                "access_token"=>$access_token,
                "expired_date"=>$expired_date,
            ]);
            $result->access_token = $access_token;
            return ['code' => 0, 'data' => $result, 'message' =>'User already exists'];
        }
        } catch (Exception $e) {
            //throw $th;.
            return ['code' => -1, 'data' => "no  data avalible", 'message' =>(String)$e];
        }
    }

    public function contact (Request $request){
        $token = $request->user_token;
        $res = DB::table('users')
        ->select('id','avatar', 'description', 'online', 'token', 'name', 'type')
        ->where('token', '!=',$token)
        ->get();
        return ['code' => 0, 'data' => $res,'message' =>'got all the user information'];
    }
    public function send_notice(Request $request){
        //call info
        $user_token = $request->user_token;
        $user_avatar = $request->user_avatar;
        $user_name = $request->user_name;

        //call info
        $to_token = $request->input("to_token");
        $to_avatar = $request->input("to_avatar");
        $to_name = $request->input("to_name");
        $call_type = $request->input("call_type");
        $doc_id = $request->input("doc_id");
        if(empty($doc_id)){
            $doc_id = "";
        }

        //get other token
        $res = DB::table('users')->select("avatar", "name","token","fcmtoken")
        ->where('token', '=',$to_token)->first();

        if(empty($res)){
            return ['code'=>-1, 'data' => 'no data', 'message' => 'user not found'];
        }

        $device_token = $res->fcmtoken;
        try {
            //code...

            if(!empty($device_token)){
                $messaging = app("firebase.messaging");
                if($call_type=="cancel"){
                    $message = CloudMessage::fromArray([
                        "token" => $device_token,
                        "data"=>[
                            "token"=>$user_token,
                            "user_avatar"=>$user_avatar,
                            "user_name"=>$user_name,
                            "doc_id"=>$doc_id,
                            "call_type"=>$call_type,
                        ],
                    ]);
                    $messaging ->send($$message);
                } elseif($call_type=="voice"){
                    $message = CloudMessage::fromArray([
                        "token" => $device_token,
                        "data"=>[
                            "token"=>$user_token,
                            "user_avatar"=>$user_avatar,
                            "user_name"=>$user_name,
                            "doc_id"=>$doc_id,
                            "call_type"=>$call_type,
                        ],
                        "android"=>[
                            "priority"=>"high",
                            "notification"=>[
                                "channel_id"=>"xxxxx",
                                "title"=>"Voice call make by".$user_name,
                                "body"=>"Please click to answer voice call from ".$user_name,
                            ]
                        ]
                    ]);

                }
            }else{
                return ['code'=>-1, 'data' => 'no data', 'message' => "device token is empty"];
            }

        } catch (\Exception $e) {
            return ['code'=>-1, 'data' => 'no data', 'message' => (string)$e];
        }

        //success
        return ['code'=>0, 'data' => "to_token: ".$to_token, 'message' => 'send notice successfully'];

    }
}