<?php

namespace App\Http\Controllers\Api;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\Controller;

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
        $result = DB::table('users')->select('email', 'name', 'avatar', 'phone', 'description','type', 'token', 'access_token','online', 'expired_date',  )->where($map)->first();
        if(empty ($result)){
            $validated['token'] = md5(uniqid().rand(10000, 99999));
            $validated['created_at'] = Carbon::now('Asia/Ho_Chi_Minh');
            $validated['access_token'] = md5(uniqid().rand(1000000, 9999999));
            $validated['expired_date'] = Carbon::now('Asia/Ho_Chi_Minh')->addDays(30);
            $user_id = DB::table('users')->insertGetId($validated);
            $user_result = DB::table('users')->select('avatar', 'name', 'description','type', 'token', 'access_token','online', )->where('id' , '=', $user_id)->first();
            return ['code' => 0, 'data' => $user_result, 'message' =>'User has been created'];
        }else{
            $access_token = md5(uniqid().rand(1000000, 9999999));
            $expired_date = Carbon::now('Asia/Ho_Chi_Minh')->addDays(30);
            DB::table('users')->where($map)->update([
                "access_token"=>$access_token,
                "expired_date"=>$expired_date,
            ]);
            $result->access_token = $access_token;
            return ['code' => 1, 'data' => $result, 'message' =>'User already exists'];
        }
        } catch (Exception $e) {
            //throw $th;.
            return ['code' => -1, 'data' => "no  data avalible", 'message' =>(String)$e];
        }
    }
}