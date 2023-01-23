<?php
namespace App\Http\Middleware;
use Auth;
use Closure;
use App\Models\Role_permissions;

class PermissionAuthenticated
{
    public function handle($request, Closure $next)
    {  
        $route_path = $request->route()->uri;
        
        $user = Auth::user();
        
        // Role permissions
        $permission_data = \DB::table('permissions as p')->join('roles_permissions as rp','p.id', '=', 'rp.permission_id')
        ->where('p.route_path', '=', $route_path)->where('rp.role_id', '=', $user->user_type)
        ->where('p.is_deleted',0)->where('rp.status',1)->where('rp.is_deleted',0)->select('p.id')->first();                
        
        /*if($user->user_type == 1){
            $req = $request->getRequestUri();
            $insertArray = array('log_title'=>$req,'user_id'=>$user->id,'role_id'=>$user->user_type);
            \DB::table('app_logs')->insert($insertArray);
        }*/
        //$file = fopen("test.txt","w");fwrite($file, json_encode($res));fclose($file);
        
        // Individual User permissions
        if(empty($permission_data)) {
            $permission_data = \DB::table('permissions as p')->join('user_permissions as up','p.id', '=', 'up.permission_id')
            ->where('p.route_path', '=', $route_path)->where('up.user_id', '=', $user->id)
            ->where('p.is_deleted',0)->where('up.status',1)->where('up.is_deleted',0)->select('p.id')->first();       
        }
        
        if(!empty($permission_data)) {
            return $next($request);
        }else{
            return redirect('access-denied');
        }

        abort(404);  // for other user throw 404 error
    }
}