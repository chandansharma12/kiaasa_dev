<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Permission;
use App\Models\Roles_permissions;
use Validator;
use App\Helpers\CommonHelper;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
        //$this->middleware('auth');
    }
    
    public function add(Request $request){
        try{
            
            $data = $request->all();
            
            $validateionRules = array('routePath_add'=>'required|unique:permissions,route_path','routeKey_add'=>'required');
            $attributes = array('routePath_add'=>'Route Path','routeKey_add'=>'Route Key');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $insertArray = array('route_path'=>$data['routePath_add'],'route_key'=>$data['routeKey_add'],'description'=>$data['description_add']);
            $permission = Permission::create($insertArray);
            
            CommonHelper::createLog('Permission Created. ID: '.$permission->id,'PERMISSION_CREATED','PERMISSION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Permission added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }

    public function update(Request $request){
        try{
            
            $data = $request->all();
            $permission_id = $data['permission_edit_id'];
            
            $validateionRules = array('routePath_edit'=>'required|unique:permissions,route_path,'.$permission_id,'routeKey_edit'=>'required','permission_type_edit'=>'required');
            $attributes = array('routePath_edit'=>'Route Path','routeKey_edit'=>'Route Key','permission_type_edit'=>'Permission Type');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $updateArray = array('route_path'=>$data['routePath_edit'],'route_key'=>$data['routeKey_edit'],'description'=>$data['description_edit'],'permission_type'=>$data['permission_type_edit']);
            Permission::where('id', '=', $permission_id)->update($updateArray);
            
            CommonHelper::createLog('Permission Updated. ID: '.$permission_id,'PERMISSION_UPDATED','PERMISSION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Permission updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
             CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function listing(Request $request){
        try{
            $data = $request->all();
            
            $permission_list = Permission::where('is_deleted',0);
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'id','name'=>'u.name','route_path'=>'route_path','route_key'=>'route_key','desc'=>'description','status'=>'permission_status','created'=>'created_at','updated'=>'updated_at');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'route_path';
                $permission_list = $permission_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }
            
            $permission_list = $permission_list->paginate(150);
            
            return view('admin/permission_list',array('permission_list'=>$permission_list,'error_message'=>''));
            
        }catch (\Exception $e){
             CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return view('admin/permission_list',array('error_message'=>$e->getMessage(),'permission_list'=>array()));
        }
    }
    
    function data(Request $request,$id){
        try{
            $data = $request->all();
            $permission_data = Permission::where('id',$id)->select('*')->first();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Permission data','permission_data' => $permission_data,'status' => 'success'),200);
        }catch (\Exception $e){
             CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function updateStatus(Request $request){
        try{
            
            $data = $request->all();
            $permission_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Permissions');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if($action == 'enable')
                $updateArray = array('permission_status'=>1);
            elseif($action == 'disable')
                $updateArray = array('permission_status'=>0);
            elseif($action == 'delete')
                $updateArray = array('is_deleted'=>1);
                
            Permission::whereIn('id',$permission_ids)->update($updateArray);
            
            CommonHelper::createLog('Permission Updated. IDs: '.$data['ids'],'PERMISSION_UPDATED','PERMISSION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Permissions updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function rolePermissionsList(Request $request,$id=''){
        try{
            $data = $request->all();
            $role_id = $id;
            $role_permissions_list = $role_data = array();
            $error_message = '';
            
            if(!empty($role_id)){
                $role_permissions_list = \DB::table('permissions as p')->leftJoin('roles_permissions as rp',function($join) use($role_id){$join->on('p.id','=','rp.permission_id') ->where('rp.role_id','=',$role_id)->where('rp.is_deleted','=','0')->where('rp.status','=','1');})
                ->where('p.is_deleted','=','0');
                
                if(isset($data['p_name']) && !empty($data['p_name'])){
                    $role_permissions_list = $role_permissions_list->where('p.route_path','LIKE','%'.trim($data['p_name']).'%');
                }
                
                if(isset($data['sort_by']) && !empty($data['sort_by'])){
                    $sort_array = array('id'=>'p.id','route_path'=>'p.route_path','route_key'=>'p.route_key','desc'=>'p.description','status'=>'rp.status','created_on'=>'rp.created_at','updated_on'=>'rp.updated_at');
                    $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'p.id';
                    $sort_order = (isset($data['sort_order']) && in_array(strtolower($data['sort_order']),array('asc','desc')) )?strtoupper($data['sort_order']):'ASC';
                    $role_permissions_list = $role_permissions_list->orderBy($sort_by,$sort_order);
                }
                
                $role_permissions_list = $role_permissions_list->select('p.*','rp.role_id','rp.status as role_permission_status','rp.created_at as rp_created_at','rp.updated_at as rp_updated_at');
                
                if(isset($data['action']) && $data['action'] == 'download_csv'){
                    $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                    $start = $paging_data['start'];
                    $limit = $paging_data['limit'];
                    $role_permissions_list = $role_permissions_list->offset($start)->limit($limit)->get()->toArray();
                }else{
                    $role_permissions_list = $role_permissions_list->paginate(100);
                }
                
                $role_data = \DB::table('user_roles')->where('id',$role_id)->first();

                if(isset($data['action']) && $data['action'] == 'download_csv'){
                    $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename='.$role_data->role_name.'_role_permission_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                    $columns = array('Permission ID','Route Path','Route Key','Permission','Created On');

                    $callback = function() use ($role_permissions_list,$columns){
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);

                        for($i=0;$i<count($role_permissions_list);$i++){
                            $permission = (!empty($role_permissions_list[$i]->role_permission_status))?'Yes':'No';
                            $created_at = (!empty($role_permissions_list[$i]->rp_created_at))?date('d-m-Y',strtotime($role_permissions_list[$i]->rp_created_at)):'';
                            
                            $array = array($role_permissions_list[$i]->id,$role_permissions_list[$i]->route_path,$role_permissions_list[$i]->route_key,$permission,$created_at);
                            fputcsv($file, $array);
                        }

                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }
            }else{
                $error_message = 'Please select role';
            }
            
            $roles_list = \DB::table('user_roles')->orderBy('role_name')->get()->toArray();
            
            return view('admin/role_permissions_list',array('role_permissions_list'=>$role_permissions_list,'roles_list'=>$roles_list,'role_id'=>$role_id,'error_message'=>$error_message,'role_data'=>$role_data));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return view('admin/role_permissions_list',array('error_message'=>$e->getMessage(),'role_permissions_list'=>array(),'roles_list'=>array(),'role_data'=>array()));
        }
    }
    
    function updateRolePermissions(Request $request,$id){
        try{
            $data = $request->all();
            $role_id = $id;
            $selected_permissions = explode(',',trim($data['permission_ids']));
            $permission_page_ids = explode(',',trim($data['permission_page_ids']));
            
            $permissions_list = \DB::table('permissions')->whereIn('id',$permission_page_ids)->where('is_deleted',0)->select('*')->get()->toArray();
            $role_permissions_list = \DB::table('roles_permissions')->where('role_id',$role_id)->select('*')->get()->toArray();
            
            \DB::beginTransaction();
            
            for($i=0;$i<count($permissions_list);$i++){
                $permission_id = $permissions_list[$i]->id;
                if(in_array($permission_id,$selected_permissions)){
                    // Assign
                    if(CommonHelper::DBArrayExists($role_permissions_list,'permission_id',$permission_id)){
                        $updateArray = array('status'=>1,'is_deleted'=>0);
                        \DB::table('roles_permissions')->where('role_id',$role_id)->where('permission_id',$permission_id)->update($updateArray);
                    }else{
                        $insertArray = array('role_id'=>$role_id,'permission_id'=>$permission_id);
                        \DB::table('roles_permissions')->insert($insertArray);
                    }
                }else{
                    //Revoke
                    if(CommonHelper::DBArrayExists($role_permissions_list,'permission_id',$permission_id)){
                        $updateArray = array('is_deleted'=>1);
                        \DB::table('roles_permissions')->where('role_id',$role_id)->where('permission_id',$permission_id)->update($updateArray);
                    }
                }
            }
            
            \DB::commit();
            
            CommonHelper::createLog('Role Permissions Updated. Role ID: '.$role_id,'ROLE_PERMISSION_UPDATED','PERMISSION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Permissions updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
             CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function userPermissionsList(Request $request,$id=''){
        try{
            $data = $request->all();
            $user_id = $id;
            $user_permissions_list = $user_data = array();
            $error_message = '';
            
            if(!empty($user_id)){
                $user_permissions_list = \DB::table('permissions as p')->leftJoin('user_permissions as up',function($join) use($user_id){$join->on('p.id','=','up.permission_id') ->where('up.user_id','=',$user_id)->where('up.is_deleted','=','0')->where('up.status','=','1');})
                ->where('p.is_deleted','=','0');
                
                if(isset($data['sort_by']) && !empty($data['sort_by'])){
                    $sort_array = array('id'=>'p.id','route_path'=>'p.route_path','route_key'=>'p.route_key','desc'=>'p.description','status'=>'up.status','created_on'=>'up.created_at','updated_on'=>'up.updated_at');
                    $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'p.id';
                    $sort_order = (isset($data['sort_order']) && in_array(strtolower($data['sort_order']),array('asc','desc')) )?strtoupper($data['sort_order']):'ASC';
                    $user_permissions_list = $user_permissions_list->orderBy($sort_by,$sort_order);
                }
                
                $user_permissions_list = $user_permissions_list->select('p.*','up.user_id','up.status as user_permission_status','up.created_at as up_created_at','up.updated_at as up_updated_at');
                
                if(isset($data['action']) && $data['action'] == 'download_csv'){
                    $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                    $start = $paging_data['start'];
                    $limit = $paging_data['limit'];
                    $user_permissions_list = $user_permissions_list->offset($start)->limit($limit)->get()->toArray();
                }else{
                    $user_permissions_list = $user_permissions_list->paginate(100);
                }
                
                $user_data = \DB::table('users')->where('id',$user_id)->first();

                if(isset($data['action']) && $data['action'] == 'download_csv'){
                    $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename='.$user_data->name.'_user_permissions_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                    $columns = array('Permission ID','Route Path','Route Key','Permission','Created On');

                    $callback = function() use ($user_permissions_list,$columns){
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);

                        for($i=0;$i<count($user_permissions_list);$i++){
                            $permission = (!empty($user_permissions_list[$i]->user_permission_status))?'Yes':'No';
                            $created_at = (!empty($user_permissions_list[$i]->up_created_at))?date('d-m-Y',strtotime($user_permissions_list[$i]->up_created_at)):'';
                            $array = array($user_permissions_list[$i]->id,$user_permissions_list[$i]->route_path,$user_permissions_list[$i]->route_key,$permission,$created_at);
                            fputcsv($file, $array);
                        }

                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }
                
            }else{
                $error_message = 'Please select user';
            }
            
            $user_list = \DB::table('users')->orderBy('name')->get()->toArray();
            
            return view('admin/user_permissions_list',array('user_permissions_list'=>$user_permissions_list,'user_list'=>$user_list,'user_id'=>$user_id,'error_message'=>$error_message,'user_data'=>$user_data));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return view('admin/user_permissions_list',array('error_message'=>$e->getMessage(),'user_permissions_list'=>array(),'user_list'=>array(),'user_data'=>array()));
        }
    }
    
    function updateUserPermissions(Request $request,$id){
        try{
            $data = $request->all();
            $user_id = $id;
            $selected_permissions = explode(',',trim($data['permission_ids']));
            $permission_page_ids = explode(',',trim($data['permission_page_ids']));
            
            $permissions_list = \DB::table('permissions')->whereIn('id',$permission_page_ids)->where('is_deleted',0)->select('*')->get()->toArray();
            $user_permissions_list = \DB::table('user_permissions')->where('user_id',$user_id)->select('*')->get()->toArray();
            
            \DB::beginTransaction();
            
            for($i=0;$i<count($permissions_list);$i++){
                $permission_id = $permissions_list[$i]->id;
                if(in_array($permission_id,$selected_permissions)){
                    // Assign
                    if(CommonHelper::DBArrayExists($user_permissions_list,'permission_id',$permission_id)){
                        $updateArray = array('status'=>1,'is_deleted'=>0);
                        \DB::table('user_permissions')->where('user_id',$user_id)->where('permission_id',$permission_id)->update($updateArray);
                    }else{
                        $insertArray = array('user_id'=>$user_id,'permission_id'=>$permission_id);
                        \DB::table('user_permissions')->insert($insertArray);
                    }
                }else{
                    //Revoke
                    if(CommonHelper::DBArrayExists($user_permissions_list,'permission_id',$permission_id)){
                        $updateArray = array('is_deleted'=>1);
                        \DB::table('user_permissions')->where('user_id',$user_id)->where('permission_id',$permission_id)->update($updateArray);
                    }
                }
            }
            
            \DB::commit();
            
            CommonHelper::createLog('User Permissions Updated. User ID: '.$user_id,'USER_PERMISSION_UPDATED','PERMISSION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Permissions updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
             CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function userPermissions(Request $request,$id){
        try{
            $data = $request->all();
            $user_id = $id;
            
            $user_data = \DB::table('users as u')
            ->join('user_roles as ur','ur.id', '=', 'u.user_type')        
            ->where('u.id',$user_id)
            ->select('u.*','ur.role_name')        
            ->first();                
            
            $role_permissions = \DB::table('roles_permissions as rp')
            ->join('permissions as p','p.id', '=', 'rp.permission_id')
            ->where('rp.role_id',$user_data->user_type)        
            ->where('rp.is_deleted',0)        
            ->where('p.is_deleted',0)
            ->select('p.*')
            ->get()->toArray();
            
            $user_permissions = \DB::table('user_permissions as up')
            ->join('permissions as p','p.id', '=', 'up.permission_id')
            ->where('up.user_id',$user_data->id)        
            ->where('up.is_deleted',0)        
            ->where('p.is_deleted',0)
            ->select('p.*')
            ->get()->toArray();
            
            return view('admin/user_permissions',array('user_data'=>$user_data,'role_permissions'=>$role_permissions,'user_permissions'=>$user_permissions,'error_message'=>''));
            
        }catch (\Exception $e){
             CommonHelper::saveException($e,'PERMISSION',__FUNCTION__,__FILE__);
            return view('admin/user_permissions',array('error_message'=>$e->getMessage()));
        }
    }
}
