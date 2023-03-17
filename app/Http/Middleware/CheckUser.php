<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;

class CheckUser{
    public function handle($request, Closure $next){
        // Get the token from the header
        $Authorization = $request->header('Authorization');
        // Check for empty token
        if (empty($Authorization)) {
            // Return a response with a 401 status code and a message
            return response(
                ['code'=>401, 'message'=>'Authorization failed'],
                401
            );
        }
        // Remove the Bearer part from the token
        $access_token = trim(ltrim($Authorization, 'Bearer'));
        // Get the user's information from the database
        $res_user = DB::table('users')
        ->where('access_token', $access_token)
        ->select('id','avatar','name','token','type','access_token','expired_date')
        ->first();
        // Check for empty result
        if(empty($res_user)){
            // Return a response with a 401 status code and a message
            return response(
                ['code'=>401,'message'=>'User not found'],
                401
            );
        }
        // Get the expired date of the token
        $expired_date = $res_user->expired_date; 
        // Check for empty expired date
        if(empty($expired_date)){
            // Return a response with a 401 status code and a message
            return response(
                ['code'=>401,'message'=>'You must login again'],
                401
            );
        }
        // Check if the token has expired
        if($expired_date<Carbon::now()){
            // Return a response with a 401 status code and a message
            return response(
                ['code'=>401,'message'=>'Expired token. You must login again'],
                401
            );
        }
        // Check if the token is about to expire
        $add_time = Carbon::now()->addDays(5);
        if($expired_date<$add_time){
            // Add a new date to the token
            $add_expired_date = Carbon::now()->addDays(30);
            DB::table("users")
            ->where("access_token", $access_token)
            ->update(["expired_date"=>$add_expired_date]);
        }
        $request->user_id = $res_user->id;
        $request->user_type = $res_user->type;
        $request->user_name = $res_user->name;
        $request->user_avatar = $res_user->avatar; 
        $request->user_token = $res_user->token;
        return $next($request);
    }
}
