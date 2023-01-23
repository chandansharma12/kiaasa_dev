<?php
namespace App\Http\Middleware;
use Auth;
use Closure;
use App\Models\User;

class PosApiAuthenticated
{
    public function handle($request, Closure $next)
    {  
        $content_type_header = $request->header('Content-Type');
        if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
        }
            
        $access_token_header = $request->header('Access-Token');
        if(empty($access_token_header)){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Access-Token Header is Required', 'errors' => 'Access-Token Header is Required'),200);
        }

        $user_data = User::where('api_token',$access_token_header)->select('id','name','api_token','api_token_created_at')->first();

        if(empty($user_data)){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Access-Token', 'errors' => 'Invalid Access-Token'),200);
        }

        if((time()-strtotime($user_data->api_token_created_at))/3600 > 240){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Access-Token Expired', 'errors' => 'Access-Token Expired'),200);
        }
        
        return $next($request);
    }
}