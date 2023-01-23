<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\User_roles;
use App\Models\Notification;
use App\Models\Store;
use App\Models\Store_user;
use App\Models\Vendor_detail;
use App\Models\User_profile;
use App\Models\User_profile_files;
use App\Models\User_attendance;
use App\Models\User_leaves;
use App\Models\User_overtime;
use App\Models\User_salary;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    
    public function __construct(){
    }

    public function editProfile(){
        try{
            $user = Auth::user();
            $user_role_data = \DB::table('user_roles')->where('id',$user->user_type)->first();
            $user->role_name = $user_role_data->role_name;
                    
            $other_roles = array();
            if(!empty($user->other_roles)){
                $user_roles = explode(',',$user->other_roles);
                $other_roles = \DB::table('user_roles as ur')->whereIn('id',$user_roles)->where('ur.role_status',1)->get()->toArray();
            }
            
            return view('profile_edit',array('user'=>$user,'other_roles'=>$other_roles));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return view('profile_edit',array('error_message'=>$e->getMessage(),'user'=>array(),'other_roles'=>array()));
            //return redirect('user/editprofile')->with('error_message', $e->getMessage());
        }  
    }
    
    public function updateProfile(Request $request){
        try{
            $user = Auth::user();
            $request_data = $request->all();

            $validations =  array(
                'name' => 'required',
                'email' => 'required|email|unique:users,email,'.$user->id,
            );

            if(isset($request_data['update_password']) && !empty($request_data['update_password'])){
                $validations['password'] = 'required|min:8|confirmed';
            }

            $this->validate(request(), $validations);

            $user->name = request('name');
            $user->email = request('email');
            if(isset($request_data['update_password']) && !empty($request_data['update_password'])){
                $user->password = bcrypt(request('password'));
            }
            
            if(isset($request_data['switch_role_id']) && !empty($request_data['switch_role_id'])){
                $current_role = $user->user_type;
                $user->user_type = $request_data['switch_role_id'];
                
                $other_roles_array = explode(',',$user->other_roles);
                $roles_array = array();
                for($i=0;$i<count($other_roles_array);$i++){
                    if($other_roles_array[$i] != $request_data['switch_role_id']){
                        $roles_array[] = $other_roles_array[$i];
                    }
                }
                
                $roles_array[] = $current_role;
                $user->other_roles = implode(',',$roles_array);
            }

            $user->save();
            
            // Update vendor data
            if($user->user_type == 15){
                $updateArray = array('name'=>trim(request('name')),'email'=>trim(request('email')));
                Vendor_detail::where('user_id',$user->id)->update($updateArray);
            }

            CommonHelper::createLog('Profile Updated. ID: '.$user->id,'PROFILE_UPDATED','PROFILE');
            return redirect('user/editprofile')->with('success_message', 'Profile updated successfully');
        
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return redirect('user/editprofile')->with('error_message', $e->getMessage());
        }  
    }
    
    public function updateRole(Request $request){
        try{
            $user = Auth::user();
            $request_data = $request->all();
            $current_role = $user->user_type;
            $user->user_type = $request_data['switch_role_id_header'];

            $other_roles_array = explode(',',$user->other_roles);
            $roles_array = array();
            for($i=0;$i<count($other_roles_array);$i++){
                if($other_roles_array[$i] != $request_data['switch_role_id_header']){
                    $roles_array[] = $other_roles_array[$i];
                }
            }

            $roles_array[] = $current_role;
            $user->other_roles = implode(',',$roles_array);
            
            $user->save();
            
            CommonHelper::createLog('Role Updated. ID: '.$user->id,'ROLE_UPDATED','PROFILE');
            return redirect('user/editprofile')->with('success_message', 'Profile updated successfully');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return redirect('user/editprofile')->with('error_message', $e->getMessage());
        }
    }
    
    function listing(Request $request){
        try{
            /*$server = 'MY-PC\SQLEXPRESS';
            $database = 'kiasa';
            $user = 'yogesh';
            $password = 'qwertyu0#';
            $connection = odbc_connect("Driver={SQL Server};Server=$server;Database=$database;", $user, $password);
            
            $rs = odbc_exec($connection, "SELECT TOP 10 * from CRM");
            while($arr = odbc_fetch_array($rs)){
                //print_r($arr);
            }*/
    
            $data = $request->all();
            $user = Auth::user();
            
            $users_list = \DB::table('users as u')
            ->join('user_roles as ur','ur.id', '=', 'u.user_type')
            //->where('u.user_type','!=','administrator')
            ->where('u.is_deleted',0);
            
            if(isset($data['u_name']) && !empty($data['u_name'])){
                $name_email = trim($data['u_name']);
                $users_list = $users_list->whereRaw("(u.name like '%{$name_email}%' OR u.email = '{$name_email}')");
            }
            
            if(isset($data['role_id']) && !empty($data['role_id'])){
                $users_list = $users_list->where('u.user_type',trim($data['role_id']));
            }
            
            if(isset($data['u_id']) && !empty($data['u_id'])){
                $users_list = $users_list->where('u.id',trim($data['u_id']));
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'u.id','name'=>'u.name','email'=>'u.email','type'=>'ur.role_name','status'=>'u.status','created'=>'u.created_at','updated'=>'u.updated_at');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'u.id';
                $users_list = $users_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }
            
            $users_list = $users_list->select('u.*','ur.role_name')->where('is_deleted',0);
            
            // Exclude store and vendor users from list for hrm user.
            if($user->user_type == 17){
                $users_list = $users_list->where('user_type','!=',9)->where('user_type','!=',15);
            }
            
            /*if($user->user_type != 17){
                 $users_list = $users_list->where('user_type','!=',1);
            }*/
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $users_list = $users_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $users_list = $users_list->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=user_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('User ID','Name','Email','Type ','Status','Created On','Updated On');

                $callback = function() use ($users_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($users_list);$i++){
                        $status = ($users_list[$i]->status == 1)?'Enabled':'Disabled';
                        $created_at = !empty($users_list[$i]->created_at)?date('d-m-Y',strtotime($users_list[$i]->created_at)):'';
                        $updated_at = !empty($users_list[$i]->updated_at)?date('d-m-Y',strtotime($users_list[$i]->updated_at)):'';
                        $array = array($users_list[$i]->id,$users_list[$i]->name,$users_list[$i]->email,$users_list[$i]->role_name,$status,$created_at,$updated_at);
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $roles_list = User_roles::where('role_status',1)->orderBy('role_name')->get()->toArray();
            $reviewers_list = User::whereIn('user_type',[1, 4])->where('is_deleted',0)->get()->toArray();
            $production_head_list = User::whereIn('user_type',[1, 7])->where('is_deleted',0)->get()->toArray();
            $warehouse_head_list = User::whereIn('user_type',[1, 8])->where('is_deleted',0)->get()->toArray();
            $stores_list = Store::where('status',1)->where('is_deleted',0)->get()->toArray();
            
            return view('admin/users_list',array('users_list'=>$users_list,'roles_list'=>$roles_list,'reviewers_list'=>$reviewers_list,'production_head_list'=>$production_head_list,
            'warehouse_head_list'=>$warehouse_head_list,'stores_list'=>$stores_list,'error_message'=>'','user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return view('admin/users_list',array('error_message'=>$e->getMessage(),'users_list'=>array(),'reviewers_list'=>array(),'production_head_list'=>array(),'warehouse_head_list'=>array()));
        }
    }
    
    function data(Request $request,$id){
        try{
            $data = $request->all();
            $user_data = \DB::table('users as u')->where('u.id',$id)->select('u.*')->first();
            if(!empty($user_data) && $user_data->user_type == 9){
                $store_user = Store_user::where('user_id',$user_data->id)->where('is_deleted',0)->where('status',1)->first();//print_r($store_user);exit;
                if(!empty($store_user))
                    $user_data->store_id = $store_user->store_id;
            }
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Users data','user_data' => $user_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function update(Request $request){
        try{
            
            $data = $request->all();
            $user_id = $data['user_edit_id'];
            
            $validateionRules = array('userName'=>'required','userEmail'=>'required|email|unique:users,email,'.$user_id,'userType'=>'required');
            $attributes = array('userName'=>'Name','userEmail'=>'Email','userType'=>'User Type');
            
            if(isset($data['updatePassword']) && !empty($data['updatePassword'])){
                $validateionRules['userPassword'] = 'required|min:8';
                $attributes['userPassword'] = 'Password';
            }
            
            if($data['userType'] == 5){
                $validateionRules['userParent'] = 'required';
                $attributes['userParent'] = 'Head Designer';
            }elseif($data['userType'] == 2){
                $validateionRules['userParentPH_edit'] = 'required';
                $attributes['userParentPH_edit'] = 'Production Head';
            }elseif($data['userType'] == 6){
                $validateionRules['userParentWH_edit'] = 'required';
                $attributes['userParentWH_edit'] = 'Warehouse Head';
            }elseif($data['userType'] == 9){
                $validateionRules['userStore_edit'] = 'required';
                $attributes['userStore_edit'] = 'Store';
                $validateionRules['userStoreUserType_edit'] = 'required';
                $attributes['userStoreUserType_edit'] = 'Store User Type';
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if(!empty($data['otheruserType_edit']) && in_array($data['userType'],$data['otheruserType_edit'])){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'User type should be different from other Roles' ));
            }
            
            $updateArray = array('name'=>$data['userName'],'email'=>$data['userEmail'],'user_type'=>$data['userType']);
           
            if(isset($data['updatePassword']) && !empty($data['updatePassword'])){
                $updateArray['password'] = bcrypt($data['userPassword']);
            }
            
            if($data['userType'] == 5){
                $updateArray['parent_user'] = $data['userParent'];
            }elseif($data['userType'] == 2){
                $updateArray['parent_user'] = $data['userParentPH_edit'];
            }elseif($data['userType'] == 6){
                $updateArray['parent_user'] = $data['userParentPH_edit'];
            }elseif($data['userType'] == 9){
                $updateArray['store_owner'] = ($data['userStoreUserType_edit'] == 2)?1:0;
            }
            
            if(!empty($data['otheruserType_edit'])){
                $otheruserTypeStr = implode(',', $data['otheruserType_edit']);
                $updateArray['other_roles']=$otheruserTypeStr;
            }
            
            $updateArray['is_view_modified_inv'] = (isset($data['viewModifiedReport']) && $data['viewModifiedReport'] == 1)?1:0;

            User::where('id',$user_id)->update($updateArray);
            
            // Assign store to user if user type is store
            if($data['userType'] == 9){
                $store_user = Store_user::where('user_id',$user_id)->first();
                if(!empty($store_user)){
                    $updateArray = array('store_id'=>$data['userStore_edit'],'status'=>1,'is_deleted'=>0);
                    Store_user::where('id',$store_user->id)->update($updateArray);
                }else{
                    $insertArray = array('store_id'=>$data['userStore_edit'],'user_id'=>$user_id);
                    Store_user::create($insertArray);
                }
            }
            
            // Update vendor type user
            if($data['userType'] == 15){
                $vendor_user = Vendor_detail::where('user_id',$user_id)->first();
                if(!empty($vendor_user)){
                    $updateArray = array('name'=>$data['userName'],'email'=>$data['userEmail']);
                    Vendor_detail::where('user_id',$user_id)->update($updateArray);
                }else{
                    $insertArray = array('user_id'=>$user_id,'name'=>$data['userName'],'email'=>$data['userEmail']);
                    Vendor_detail::create($insertArray);
                }
            }
            
            CommonHelper::createLog('User Updated. ID: '.$user_id,'USER_UPDATED','USER');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'User updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateStatus(Request $request){
        try{
            
            $data = $request->all();
            $user_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Users');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if($action == 'enable')
                $updateArray = array('status'=>1);
            elseif($action == 'disable')
                $updateArray = array('status'=>0);
            elseif($action == 'delete')
                $updateArray = array('is_deleted'=>1);
                
            User::whereIn('id',$user_ids)->update($updateArray);
            
            CommonHelper::createLog('User Status Updated. IDs: '.$data['ids'],'USER_STATUS_UPDATED','USER');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Users updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function add(Request $request){
        try{
            
            $data = $request->all();
            
            $validateionRules = array('userName_add'=>'required','userEmail_add'=>'required|email|unique:users,email','userPassword_add'=>'required|min:8|confirmed','userType_add'=>'required');
            $attributes = array('userName_add'=>'Name','userEmail_add'=>'Email','userType_add'=>'User Type','userPassword_add'=>'Password');
            
            if($data['userType_add'] == 5){
                $validateionRules['userParent_add'] = 'required';
                $attributes['userParent_add'] = 'Head Designer';
            }elseif($data['userType_add'] == 2){
                $validateionRules['userParentPH_add'] = 'required';
                $attributes['userParentPH_add'] = 'Production Head';
            }elseif($data['userType_add'] == 6){
                $validateionRules['userParentWH_add'] = 'required';
                $attributes['userParentWH_add'] = 'Warehouse Head';
            }elseif($data['userType_add'] == 9){
                $validateionRules['userStore_add'] = 'required';
                $attributes['userStore_add'] = 'Store';
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if(!empty($data['otheruserType_add']) && in_array($data['userType_add'],$data['otheruserType_add'])){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'User type should be different from other Roles' ));
            }
            
            $insertArray = array('name'=>$data['userName_add'],'email'=>$data['userEmail_add'],'user_type'=>$data['userType_add'],'password'=>bcrypt($data['userPassword_add']));
            if($data['userType_add'] == 5){
                $insertArray['parent_user'] = $data['userParent_add'];
            }elseif($data['userType_add'] == 2){
                $insertArray['parent_user'] = $data['userParentPH_add'];
            }elseif($data['userType_add'] == 6){
                $insertArray['parent_user'] = $data['userParentWH_add'];
            }
          
            if(!empty($data['otheruserType_add'])){
                $otheruserTypeStr = implode(',', $data['otheruserType_add']);
                $insertArray['other_roles']=$otheruserTypeStr;
            }
          
            $user = User::create($insertArray);
            
            // Assign store to user if user type is store
            if($data['userType_add'] == 9){
                $store_user = Store_user::where('user_id',$user->id)->first();
                if(!empty($store_user)){
                    $updateArray = array('store_id'=>$data['userStore_add'],'status'=>1,'is_deleted'=>0);
                    Store_user::where('id',$store_user->id)->update($updateArray);
                }else{
                    $insertArray = array('store_id'=>$data['userStore_add'],'user_id'=>$user->id);
                    Store_user::create($insertArray);
                }
            }
            
            // Create vendor user
            if($data['userType_add'] == 15){
                $insertArray = array('user_id'=>$user->id,'name'=>$data['userName_add'],'email'=>$data['userEmail_add']);
                Vendor_detail::create($insertArray);
            }
            
            CommonHelper::createLog('New User Created. ID: '.$user->id,'USER_ADDED','USER');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'User added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function sendNotification(Request $request){
        try{
            $data = $request->all();
            $type_id = $data['type_id'];
            $ref_id = $data['ref_id'];
            $params = array('ref_id'=>$ref_id);
            
            return CommonHelper::sendNotificationEmail($type_id,$params);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function getNotificationsList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $to_user_id = $user->id;
            $notifications = Notification::where('to_user_id',$to_user_id)->where('is_read',0)->orderBy('id','DESC')->limit(15)->get()->toArray();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Notifications list','notifications_list'=>$notifications,'status' => 'success'),201);
            
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function readNotification(Request $request,$id){
        try{
            $data = $request->all();
            $url = '';
            Notification::where('id',$id)->update(array('is_read'=>1));
            
            $notification_data = Notification::where('id',$id)->first();
            if($notification_data->template_id == 1){
                $url = url('design/detail/'.$notification_data->reference_id);
            }elseif($notification_data->template_id == 2){
                $url = url('design/detail/'.$notification_data->reference_id);
            }
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Notification Read','url'=>$url),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function viewUserProfile(Request $request,$id){
        try{
            $user_id = $id;
            $data = $request->all();
            
            $user_data = \DB::table('users as u')
            ->where('u.id',$user_id)
            ->where('u.is_deleted',0)        
            ->first();        
            
            $profile_data = \DB::table('user_profile as up')
            ->where('up.user_id',$user_id)
            ->where('up.is_deleted',0)        
            ->first();   
            
            if(empty($profile_data)){
                $insertArray = array('user_id'=>$user_id,'employee_id'=>'K'.$user_id);
                User_profile::create($insertArray);
            }
            
            if(isset($data['action']) && $data['action'] == 'get_user_profile_data'){
                $user_files = User_profile_files::where('user_id',$user_id)->where('is_deleted',0)->get()->toArray();
                $profile_data->qualification_details = (!empty($profile_data->qualification_details))?json_decode($profile_data->qualification_details,true):'';
                $profile_data->experience_details = (!empty($profile_data->experience_details))?json_decode($profile_data->experience_details,true):'';
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'User Profile','user_profile'=>$profile_data,'user_files'=>$user_files),200);
            }
            
            $state_list = \DB::table('state_list')->where('is_deleted',0)->orderBy('state_name')->get()->toArray();
            
            return view('user/profile_view',array('user_data'=>$user_data,'profile_data'=>$profile_data,'state_list'=>$state_list,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('user/profile_view',array('error_message'=>$e->getMessage().', Line: '.$e->getLine(),'user'=>array()));
            }
        }  
    }
    
    public function updateUserProfile(Request $request,$id){
        try{
            $data = $request->all();
            $user_id = $id;
            $profile_picture = '';
            
            $user_data = \DB::table('users as u')
            ->join('user_roles as ur','u.user_type', '=', 'ur.id')
            ->where('u.id',$user_id)
            ->where('u.is_deleted',0)        
            ->first();        
            
            $profile_data = \DB::table('user_profile as up')
            ->leftJoin('state_list as sl','sl.id', '=', 'up.state_id')
            ->leftJoin('users as ul','ul.id', '=', 'up.supervisor_id')        
            ->where('up.user_id',$user_id)
            ->where('up.is_deleted',0)        
            ->first();   
            
            if(isset($data['action']) && $data['action'] == 'update_profile'){
            
                if(isset($data['type']) && $data['type'] == 'personal'){
                    $validationRules = array('userGender_edit'=>'required','userMaritalStatus_edit'=>'required','userDOB_edit'=>'required','userBloodGroup_edit'=>'required');

                    $attributes = array('userGender_edit'=>'Gender','userMaritalStatus_edit'=>'Marital Status','userDOB_edit'=>'DOB','userBloodGroup_edit'=>'Blood Group');

                    for($i=1;$i<=5;$i++){
                        if(!empty($request->file('userPersonalFile_edit_'.$i))){
                           
                            $validationRules['userPersonalFile_edit_'.$i] = 'mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:2048';        
                            $validationRules['userPersonalFileTitle_edit_'.$i] = 'required';
                            $validationRules['userPersonalFileType_edit_'.$i] = 'required';
                            $attributes['userPersonalFileTitle_edit_'.$i] = 'Title';
                            $attributes['userPersonalFileType_edit_'.$i] = 'Type';
                            $attributes['userPersonalFile_edit_'.$i] = 'File';
                        }
                    }

                    $validator = Validator::make($data,$validationRules,array(),$attributes);
                    if ($validator->fails()){ 
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                    }	

                    \DB::beginTransaction();

                    for($i=1;$i<=5;$i++){
                        if(!empty($request->file('userPersonalFile_edit_'.$i))){
                            $doc_name = CommonHelper::uploadImage($request,'userPersonalFile_edit_'.$i,'documents/user_files/'.$user_id,false);
                            $file_name = trim($data['userPersonalFileTitle_edit_'.$i]);
                            $file_type = trim($data['userPersonalFileType_edit_'.$i]);

                            $insertArray = array('user_id'=>$user_id,'file_name'=>$doc_name,'file_title'=>$file_name,'file_type'=>$file_type,'file_category'=>$data['type']);
                            User_profile_files::create($insertArray);
                        }
                    }

                    if(!empty($request->file('userProfilePicture_edit'))){
                        $profile_picture = CommonHelper::uploadImage($request,'userProfilePicture_edit','documents/user_files/'.$user_id,true);
                    }

                    $dob = CommonHelper::formatDate(trim($data['userDOB_edit']));

                    $updateArray = array('gender'=>trim($data['userGender_edit']),'marital_status'=>trim($data['userMaritalStatus_edit']),'dob'=>$dob,'blood_group'=>trim($data['userBloodGroup_edit']));

                    if(!empty($profile_picture)){
                        $updateArray['profile_picture'] = $profile_picture;
                    }

                    User_profile::where('user_id',$user_id)->where('is_deleted',0)->update($updateArray);

                    \DB::commit();
                }
                
                if(isset($data['type']) && $data['type'] == 'contact'){
                    $validationRules = array('userAddress_edit'=>'required','userCity_edit'=>'required','userState_edit'=>'required','userEmailAddress_edit'=>'nullable|email');

                    $attributes = array('userAddress_edit'=>'Address','userCity_edit'=>'City','userState_edit'=>'State','userEmailAddress_edit'=>'Email Address');

                    for($i=1;$i<=5;$i++){
                        if(!empty($request->file('userContactFile_edit_'.$i))){
                           
                            $validationRules['userContactFile_edit_'.$i] = 'mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:2048';        
                            $validationRules['userContactFileTitle_edit_'.$i] = 'required';
                            $validationRules['userContactFileType_edit_'.$i] = 'required';
                            $attributes['userContactFileTitle_edit_'.$i] = 'Title';
                            $attributes['userContactFileType_edit_'.$i] = 'Type';
                            $attributes['userContactFile_edit_'.$i] = 'File';
                        }
                    }

                    $validator = Validator::make($data,$validationRules,array(),$attributes);
                    if ($validator->fails()){ 
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                    }	

                    \DB::beginTransaction();

                    for($i=1;$i<=5;$i++){
                        if(!empty($request->file('userContactFile_edit_'.$i))){
                            $doc_name = CommonHelper::uploadImage($request,'userContactFile_edit_'.$i,'documents/user_files/'.$user_id,false);
                            $file_name = trim($data['userContactFileTitle_edit_'.$i]);
                            $file_type = trim($data['userContactFileType_edit_'.$i]);

                            $insertArray = array('user_id'=>$user_id,'file_name'=>$doc_name,'file_title'=>$file_name,'file_type'=>$file_type,'file_category'=>$data['type']);
                            User_profile_files::create($insertArray);
                        }
                    }

                    $updateArray = array('address'=>trim($data['userAddress_edit']),'city'=>trim($data['userCity_edit']),'state_id'=>trim($data['userState_edit']),'postal_code'=>trim($data['userPostalCode_edit']),
                    'mobile_no'=>trim($data['userMobileNo_edit']),'home_phone_no'=>trim($data['userHomePhoneNo_edit']),'personal_email'=>trim($data['userEmailAddress_edit']),'emergency_contact_name'=>trim($data['userEmergencyContactName_edit']),
                    'emergency_contact_relation'=>trim($data['userEmergencyContactRelation_edit']),'emergency_contact_phone_no'=>trim($data['userEmergencyContactPhoneNo_edit']));

                    User_profile::where('user_id',$user_id)->where('is_deleted',0)->update($updateArray);

                    \DB::commit();
                }
                
                if(isset($data['type']) && $data['type'] == 'job'){
                    $validationRules = array('userJobTitle_edit'=>'required','userAnnualCtc_edit'=>'required|numeric');
                    $attributes = array('userJobTitle_edit'=>'Job Title','userAnnualCtc_edit'=>'Annual CTC');

                    for($i=1;$i<=5;$i++){
                        if(!empty($request->file('userJobFile_edit_'.$i))){
                           
                            $validationRules['userJobFile_edit_'.$i] = 'mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:2048';        
                            $validationRules['userJobFileTitle_edit_'.$i] = 'required';
                            $validationRules['userJobFileType_edit_'.$i] = 'required';
                            $attributes['userJobFileTitle_edit_'.$i] = 'Title';
                            $attributes['userJobFileType_edit_'.$i] = 'Type';
                            $attributes['userJobFile_edit_'.$i] = 'File';
                        }
                    }

                    $validator = Validator::make($data,$validationRules,array(),$attributes);
                    if ($validator->fails()){ 
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                    }	

                    \DB::beginTransaction();

                    for($i=1;$i<=5;$i++){
                        if(!empty($request->file('userJobFile_edit_'.$i))){
                            $doc_name = CommonHelper::uploadImage($request,'userJobFile_edit_'.$i,'documents/user_files/'.$user_id,false);
                            $file_name = trim($data['userJobFileTitle_edit_'.$i]);
                            $file_type = trim($data['userJobFileType_edit_'.$i]);

                            $insertArray = array('user_id'=>$user_id,'file_name'=>$doc_name,'file_title'=>$file_name,'file_type'=>$file_type,'file_category'=>$data['type']);
                            User_profile_files::create($insertArray);
                        }
                    }

                    $joining_date = CommonHelper::formatDate(trim($data['userJoiningDate_edit']));
                    $relieving_date = CommonHelper::formatDate(trim($data['userRelievingDate_edit']));
                    
                    $monthly_salary = round(trim($data['userAnnualCtc_edit'])/12,2);
                    
                    $updateArray = array('job_title'=>trim($data['userJobTitle_edit']),'employment_type'=>trim($data['userEmploymentType_edit']),
                    'employment_status'=>trim($data['userEmploymentStatus_edit']),'joining_date'=>$joining_date,'relieving_date'=>$relieving_date,
                    'overtime_hourly_rate'=>$data['userOvertimeHourlyRate_edit'],'annual_ctc'=>$data['userAnnualCtc_edit'],'monthly_salary'=>$monthly_salary);

                    User_profile::where('user_id',$user_id)->where('is_deleted',0)->update($updateArray);

                    \DB::commit();
                }
                
                if(isset($data['type']) && $data['type'] == 'qualification'){
                    $validationRules = array();
                    $attributes = array();

                    for($i=1;$i<=10;$i++){
                        if(!empty($data['userQualificationType_edit_'.$i]) || !empty($data['userQualificationName_edit_'.$i]) || !empty($data['userQualificationFrom_edit_'.$i]) || !empty($data['userQualificationTo_edit_'.$i]) || !empty($data['userQualificationCollege_edit_'.$i]) || !empty($data['userQualificationPercentage_edit_'.$i])){
                           
                            $validationRules['userQualificationType_edit_'.$i] = 'required';        
                            $validationRules['userQualificationName_edit_'.$i] = 'required';
                            $validationRules['userQualificationFrom_edit_'.$i] = 'required';
                            $validationRules['userQualificationTo_edit_'.$i] = 'required';
                            $validationRules['userQualificationCollege_edit_'.$i] = 'required';
                            $validationRules['userQualificationPercentage_edit_'.$i] = 'required';
                            $attributes['userQualificationType_edit_'.$i] = 'Type';
                            $attributes['userQualificationName_edit_'.$i] = 'Name';
                            $attributes['userQualificationFrom_edit_'.$i] = 'From Date';
                            $attributes['userQualificationTo_edit_'.$i] = 'To Date';
                            $attributes['userQualificationCollege_edit_'.$i] = 'College/University';
                            $attributes['userQualificationPercentage_edit_'.$i] = 'Percentage';
                        }
                        
                        if(!empty($data['userExpType_edit_'.$i]) || !empty($data['userExpDesignation_edit_'.$i]) || !empty($data['userExpCompany_edit_'.$i]) || !empty($data['userExpFrom_edit_'.$i]) || !empty($data['userExpTo_edit_'.$i]) ){
                           
                            $validationRules['userExpType_edit_'.$i] = 'required';        
                            $validationRules['userExpDesignation_edit_'.$i] = 'required';
                            $validationRules['userExpCompany_edit_'.$i] = 'required';
                            $validationRules['userExpFrom_edit_'.$i] = 'required';
                            $validationRules['userExpTo_edit_'.$i] = 'required';
                            $attributes['userExpType_edit_'.$i] = 'Type';
                            $attributes['userExpDesignation_edit_'.$i] = 'Designation';
                            $attributes['userExpCompany_edit_'.$i] = 'Company';
                            $attributes['userExpFrom_edit_'.$i] = 'From Date';
                            $attributes['userExpTo_edit_'.$i] = 'To Date';
                        }
                    }
                    
                    for($i=1;$i<=5;$i++){
                        if(!empty($request->file('userQualificationFile_edit_'.$i))){
                           
                            $validationRules['userQualificationFile_edit_'.$i] = 'mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:2048';        
                            $validationRules['userQualificationFileTitle_edit_'.$i] = 'required';
                            $validationRules['userQualificationFileType_edit_'.$i] = 'required';
                            $attributes['userQualificationFileTitle_edit_'.$i] = 'Title';
                            $attributes['userQualificationFileType_edit_'.$i] = 'Type';
                            $attributes['userQualificationFile_edit_'.$i] = 'File';
                        }
                    }

                    $validator = Validator::make($data,$validationRules,array(),$attributes);
                    if ($validator->fails()){ 
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                    }	

                    \DB::beginTransaction();

                    for($i=1;$i<=5;$i++){
                        if(!empty($request->file('userQualificationFile_edit_'.$i))){
                            $doc_name = CommonHelper::uploadImage($request,'userQualificationFile_edit_'.$i,'documents/user_files/'.$user_id,false);
                            $file_name = trim($data['userQualificationFileTitle_edit_'.$i]);
                            $file_type = trim($data['userQualificationFileType_edit_'.$i]);

                            $insertArray = array('user_id'=>$user_id,'file_name'=>$doc_name,'file_title'=>$file_name,'file_type'=>$file_type,'file_category'=>$data['type']);
                            User_profile_files::create($insertArray);
                        }
                    }
                    
                    $qualification_list = array();
                    for($i=1;$i<=10;$i++){
                        if(!empty($data['userQualificationType_edit_'.$i])){
                            $from_date = trim($data['userQualificationFrom_edit_'.$i]);//CommonHelper::formatDate(trim($data['userQualificationFrom_edit_'.$i]));
                            $to_date = trim($data['userQualificationTo_edit_'.$i]); //CommonHelper::formatDate(trim($data['userQualificationTo_edit_'.$i]));
                            
                            $qualification = array('type'=>trim($data['userQualificationType_edit_'.$i]),'name'=>$data['userQualificationName_edit_'.$i],'from'=>$from_date,'to'=>$to_date);
                            $qualification['college'] = trim($data['userQualificationCollege_edit_'.$i]);
                            $qualification['percentage'] = trim($data['userQualificationPercentage_edit_'.$i]);
                            $qualification_list[] = $qualification;
                        }
                    }
                    
                    $qualifications = (!empty($qualification_list))?json_encode($qualification_list):'';
                    
                    $exp_list = array();
                    for($i=1;$i<=10;$i++){
                        if(!empty($data['userExpType_edit_'.$i])){
                            $from_date = trim($data['userExpFrom_edit_'.$i]);//CommonHelper::formatDate(trim($data['userExpFrom_edit_'.$i]));
                            $to_date = trim($data['userExpTo_edit_'.$i]);//CommonHelper::formatDate(trim($data['userExpTo_edit_'.$i]));
                            
                            $exp = array('type'=>trim($data['userExpType_edit_'.$i]),'designation'=>$data['userExpDesignation_edit_'.$i],'company'=>$data['userExpCompany_edit_'.$i],'from'=>$from_date,'to'=>$to_date);
                            $exp_list[] = $exp;
                        }
                    }
                    
                    $exps = (!empty($exp_list))?json_encode($exp_list):'';
                    
                    $updateArray = array('qualification_details'=>$qualifications,'experience_details'=>$exps);

                    User_profile::where('user_id',$user_id)->where('is_deleted',0)->update($updateArray);

                    \DB::commit();
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Profile updated Successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'delete_file'){
                $file_data = User_profile_files::where('id',$data['id'])->where('is_deleted',0)->first();
                
                $updateArray = array('is_deleted'=>1);
                User_profile_files::where('id',$data['id'])->where('is_deleted',0)->update($updateArray);
                $file = public_path('documents/user_files/'.$file_data->user_id.'/'.$file_data->file_name);
                unlink($file);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'File deleted Successfully'),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function listAttendance(Request $request){
        try{
            
            $data = $request->all();
            $users_ids = $attendance_list = $users_leaves = array();
            $users_per_page = 50;
            // last 9 days + today = 10 days
            $search_date = CommonHelper::getSearchStartEndDate($data,true,'-9 days');
            $start_date = trim($search_date['start_date']);
            $end_date = trim($search_date['end_date']);
            
            $user_list = \DB::table('users as u')
            ->join('user_roles as ur','ur.id', '=', 'u.user_type')        
            ->where('u.is_deleted',0)        
            ->where('u.user_type','!=',9)->where('u.user_type','!=',15)  
            ->select('u.*','ur.role_name')        
            ->orderBy('u.name')        
            ->paginate($users_per_page);        
            
            for($i=0;$i<count($user_list);$i++){
                $users_ids[] = $user_list[$i]->id;
            }
            
            $user_attendance = User_attendance::wherein('user_id',$users_ids)
            ->whereRaw("attendance_date BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
            ->where('is_deleted',0)
            ->get()->toArray();
            
            for($i=0;$i<count($user_attendance);$i++){
                $key = str_replace(array('/','-'),'_',$user_attendance[$i]['attendance_date']);
                $attendance_list[$user_attendance[$i]['user_id']][$key] = $user_attendance[$i];
            }
            
            $users_approved_leaves = User_leaves::
            whereRaw("( (from_date <= '$start_date' AND to_date >= '$start_date') OR (from_date <= '$end_date' AND to_date >= '$end_date') OR (from_date <= '$start_date' AND to_date >= '$end_date') OR (from_date >= '$start_date' AND to_date <= '$end_date') )")        
            ->where('leave_status','approved')        
            ->where('is_deleted',0)
            ->get()->toArray();

            for($i=0;$i<count($users_approved_leaves);$i++){
                $days = CommonHelper::dateDiff($users_approved_leaves[$i]['from_date'],$users_approved_leaves[$i]['to_date']);
                for($q=0;$q<=$days;$q++){
                    $timestamp = strtotime("+$q days",strtotime($users_approved_leaves[$i]['from_date']));
                    $leave_date = date('Y_m_d',$timestamp);
                    $users_leaves[$users_approved_leaves[$i]['user_id']][$leave_date] = $users_approved_leaves[$i]['leave_type'];
                }
            }
            
            return view('user/attendance_list',array('attendance_list'=>$attendance_list,'error_message'=>'','start_date'=>$start_date,'end_date'=>$end_date,'user_list'=>$user_list,'users_leaves'=>$users_leaves));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER_PROFILE',__FUNCTION__,__FILE__);
            return view('user/attendance_list',array('error_message'=>$e->getMessage().', Line: '.$e->getLine()));
        }  
    }
    
    public function editDailyAttendance(Request $request){
        try{
            //0 = absent, 1 = present, 2 =  half day 
            $data = $request->all();
            $users_ids = $attendance_list = $users_leaves = array();
            $users_per_page = 50;
            $date = (isset($data['date']))? CommonHelper::formatDate($data['date']):date('Y/m/d');
            
            $user_list = \DB::table('users as u')
            ->join('user_roles as ur','ur.id', '=', 'u.user_type')        
            ->where('u.is_deleted',0)        
            ->where('u.user_type','!=',9)->where('u.user_type','!=',15)  
            ->select('u.*','ur.role_name')        
            ->orderBy('u.name')        
            ->paginate($users_per_page);        
            
            for($i=0;$i<count($user_list);$i++){
                $users_ids[] = $user_list[$i]->id;
            }
            
            $user_attendance = User_attendance::wherein('user_id',$users_ids)
            ->whereRaw("attendance_date = '$date'")        
            ->where('is_deleted',0)
            ->get()->toArray();
            
            for($i=0;$i<count($user_attendance);$i++){
                $key = str_replace(array('/','-'),'_',$user_attendance[$i]['attendance_date']);
                $attendance_list[$user_attendance[$i]['user_id']][$key] = $user_attendance[$i];
            }
            
            $users_approved_leaves = User_leaves::
            whereRaw("(from_date <= '$date' AND to_date >= '$date')")        
            ->where('leave_status','approved')        
            ->where('is_deleted',0)
            ->get()->toArray();
            
            for($i=0;$i<count($users_approved_leaves);$i++){
                $users_leaves[$users_approved_leaves[$i]['user_id']] = $users_approved_leaves[$i]['leave_type'];
            }
           
            return view('user/attendance_edit',array('attendance_list'=>$attendance_list,'error_message'=>'','user_list'=>$user_list,'date'=>$date,'users_ids'=>$users_ids,'users_leaves'=>$users_leaves));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER_PROFILE',__FUNCTION__,__FILE__);
            return view('user/attendance_edit',array('error_message'=>$e->getMessage().', Line: '.$e->getLine()));
        }  
    }
    
     public function updateDailyAttendance(Request $request){
        try{
            $data = $request->all();
            $attendance_list = array();
            $user_ids = explode(',',trim($data['user_ids']));
            $date = trim($data['date']);
            
            $user_attendance = User_attendance::wherein('user_id',$user_ids)->where('attendance_date',$date)->get()->toArray();
            for($i=0;$i<count($user_attendance);$i++){
                $attendance_list[$user_attendance[$i]['user_id']] = $user_attendance[$i]['attendance_status'];
            }
            
            \DB::beginTransaction();
             
            for($i=0;$i<count($user_ids);$i++){
                $user_id = $user_ids[$i];
                $attendance_status = trim($data['attendance_'.$user_id]);
                if($attendance_status >= 0){
                    // Update or insert if attendance status >= 0
                    if(isset($attendance_list[$user_id])){
                        $updateArray = array('attendance_status'=>$attendance_status,'is_deleted'=>0);
                        User_attendance::where('user_id',$user_id)->where('attendance_date',$date)->update($updateArray);
                    }else{
                        $insertArray = array('attendance_status'=>$attendance_status,'user_id'=>$user_id,'attendance_date'=>$date);
                        User_attendance::create($insertArray);
                    }
                }else{
                    // Delete attendance record if it is updated as not added
                    if(isset($attendance_list[$user_id])){
                        $updateArray = array('is_deleted'=>1);
                        User_attendance::where('user_id',$user_id)->where('attendance_date',$date)->update($updateArray);
                    }
                }
            }
            
            \DB::commit();
            
            return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','message' => 'Attendance data updated successfully'),200);
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'USER_PROFILE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function listLeaves(Request $request){
        try{
            $data = $request->all();
            $rec_per_page = 50;
            $leaves_timestamp = $leaves_applied_timestamp = array();
            
            if(isset($data['action']) && $data['action'] == 'add_leave'){
                $validateionRules = array('userId_add'=>'required','leaveFrom_add'=>'required','leaveTo_add'=>'required','leaveStatus_add'=>'required','leaveComments_add'=>'required','leaveType_add'=>'required');
                $attributes = array('userId_add'=>'Employee','leaveFrom_add'=>'Leave From','leaveTo_add'=>'leave To','leaveStatus_add'=>'Leave Status','leaveComments_add'=>'Reason/Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                // Leave dates validation start
                $leave_user_id = trim($data['userId_add']);
                $from_date = CommonHelper::formatDate(trim($data['leaveFrom_add']));
                $to_date = CommonHelper::formatDate(trim($data['leaveTo_add']));
                $days = CommonHelper::dateDiff($from_date,$to_date);
                for($i=0;$i<=$days;$i++){
                    $timestamp = strtotime("+$i days",strtotime($from_date));
                    $leaves_timestamp[] = $timestamp;
                }
                
                $last_month_date = date('Y/m/d',strtotime('-3 months'));
                $users_applied_leaves =  User_leaves::where('user_id',$leave_user_id)->where('from_date','>=',$last_month_date)->where('is_deleted',0)->get()->toArray();
                
                for($i=0;$i<count($users_applied_leaves);$i++){
                    $days = CommonHelper::dateDiff($users_applied_leaves[$i]['from_date'],$users_applied_leaves[$i]['to_date']);
                    for($q=0;$q<=$days;$q++){
                        $timestamp = strtotime("+$q days",strtotime($users_applied_leaves[$i]['from_date']));
                        $leaves_applied_timestamp[] = $timestamp;
                    }
                }
                
                for($i=0;$i<count($leaves_timestamp);$i++){
                    if(in_array($leaves_timestamp[$i], $leaves_applied_timestamp)){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Leave is already applied on '.date('d M Y',$leaves_timestamp[$i]), 'errors' =>'Leave is already applied on '.date('d M Y',$leaves_timestamp[$i]) ));
                    }
                }
                
                // Leave dates validation end
                /*$matching_date = \DB::table('user_leaves as ul')->where('ul.user_id',$data['userId_add'])->whereRaw(("ul.from_date BETWEEN '$start_date' AND '$end_date'") OR ("ul.to_date BETWEEN '$start_date' AND '$end_date'"))->count();
                
                if ($matching_date > 0){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid leave dates as one of the applying leave date were already taken in past.', 'errors' =>'Leave already exists' ));
                }*/
                
                $insertArray = array('user_id'=>trim($data['userId_add']),'from_date'=>$from_date,'to_date'=>$to_date,'comments'=>trim($data['leaveComments_add']),'leave_status'=>trim($data['leaveStatus_add']),'leave_type'=>trim($data['leaveType_add']));
                $leave = User_leaves::create($insertArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Leave added successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'get_leave_data'){
                $leave_data = User_leaves::where('id',trim($data['id']))->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Leave data','leave_data'=>$leave_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_leave'){
                $validateionRules = array('userId_edit'=>'required','leaveFrom_edit'=>'required','leaveTo_edit'=>'required','leaveStatus_edit'=>'required','leaveComments_edit'=>'required','leaveType_edit'=>'required');
                $attributes = array('userId_edit'=>'Employee','leaveFrom_edit'=>'Leave From','leaveTo_edit'=>'leave To','leaveStatus_edit'=>'Leave Status','leaveComments_edit'=>'Reason/Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                // Leave dates validation start
                $from_date = CommonHelper::formatDate(trim($data['leaveFrom_edit']));
                $to_date = CommonHelper::formatDate(trim($data['leaveTo_edit']));
                $leave_id = trim($data['leave_id_edit']);
                
                $leave_user_id = trim($data['userId_edit']);
                $days = CommonHelper::dateDiff($from_date,$to_date);
                for($i=0;$i<=$days;$i++){
                    $timestamp = strtotime("+$i days",strtotime($from_date));
                    $leaves_timestamp[] = $timestamp;
                }
                
                $last_month_date = date('Y/m/d',strtotime('-3 months'));
                $users_applied_leaves =  User_leaves::where('user_id',$leave_user_id)->where('from_date','>=',$last_month_date)->where('is_deleted',0)->where('id','!=',$leave_id)->get()->toArray();
                
                for($i=0;$i<count($users_applied_leaves);$i++){
                    $days = CommonHelper::dateDiff($users_applied_leaves[$i]['from_date'],$users_applied_leaves[$i]['to_date']);
                    for($q=0;$q<=$days;$q++){
                        $timestamp = strtotime("+$q days",strtotime($users_applied_leaves[$i]['from_date']));
                        $leaves_applied_timestamp[] = $timestamp;
                    }
                }
                
                for($i=0;$i<count($leaves_timestamp);$i++){
                    if(in_array($leaves_timestamp[$i], $leaves_applied_timestamp)){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Leave is already applied on '.date('d M Y',$leaves_timestamp[$i]), 'errors' =>'Leave is already applied on '.date('d M Y',$leaves_timestamp[$i]) ));
                    }
                }
                // Leave dates validation end
                
                $updateArray = array('user_id'=>trim($data['userId_edit']),'from_date'=>$from_date,'to_date'=>$to_date,'comments'=>trim($data['leaveComments_edit']),'leave_status'=>trim($data['leaveStatus_edit']),'leave_type'=>trim($data['leaveType_edit']));
                $leave = User_leaves::where('id',$leave_id)->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Leave updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'delete_leave'){
                $updateArray = array('is_deleted'=>1);
                User_leaves::where('id',trim($data['id']))->update($updateArray);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Leave deleted successfully'),200);
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            $leaves_list = \DB::table('user_leaves as ul')
            ->join('users as u','u.id', '=', 'ul.user_id')  
            ->join('user_roles as ur','ur.id', '=', 'u.user_type');
            
            if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                $start_date = trim($search_date['start_date']);
                $end_date = trim($search_date['end_date']);
                $leaves_list = $leaves_list->whereRaw("( (ul.from_date <= '$start_date' AND ul.to_date >= '$start_date') OR (ul.from_date <= '$end_date' AND ul.to_date >= '$end_date') OR (ul.from_date <= '$start_date' AND ul.to_date >= '$end_date') OR (ul.from_date >= '$start_date' AND ul.to_date <= '$end_date') )");        
            }
                    
            $leaves_list = $leaves_list->where('ul.is_deleted',0)        
            ->select('ul.*','ur.role_name','u.name as user_name')        
            ->orderBy('ul.id','DESC')        
            ->paginate($rec_per_page);        
            
            $user_list = \DB::table('users as u')
            ->join('user_roles as ur','ur.id', '=', 'u.user_type')        
            ->where('u.is_deleted',0)        
            ->where('u.user_type','!=',9)->where('u.user_type','!=',15)  
            ->select('u.*','ur.role_name')        
            ->orderBy('u.name')        
            ->get()->toArray();        
            
            return view('user/leaves_list',array('leaves_list'=>$leaves_list,'error_message'=>'','user_list'=>$user_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('user/leaves_list',array('error_message'=>$e->getMessage()));
            }
        }  
    }
    
    public function listOverTime(Request $request){
        try{
            $data = $request->all();
            $rec_per_page = 50;
            $leaves_timestamp = $leaves_applied_timestamp = array();
            
            if(isset($data['action']) && $data['action'] == 'add_overtime'){
                $validateionRules = array('userId_add'=>'required','overTimeDate_add'=>'required','overTimeHours_add'=>'required','overtimeStatus_add'=>'required','overtimeComments_add'=>'required');
                $attributes = array('userId_add'=>'Employee','overTimeDate_add'=>'Overtime Date','overTimeHours_add'=>'Overtime Hours','overtimeStatus_add'=>'Status','overtimeComments_add'=>'Reason/Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                $overtime_user_id = trim($data['userId_add']);
                $overtime_date = CommonHelper::formatDate(trim($data['overTimeDate_add']));
                
                $insertArray = array('user_id'=>$overtime_user_id,'overtime_date'=>$overtime_date,'overtime_hours'=>trim($data['overTimeHours_add']),'comments'=>trim($data['overtimeComments_add']),'overtime_status'=>trim($data['overtimeStatus_add']));
                $overtime = User_overtime::create($insertArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Overtime added successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'get_overtime_data'){
                $overtime_data = User_overtime::where('id',trim($data['id']))->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Overtime data','overtime_data'=>$overtime_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_overtime'){
                $validateionRules = array('userId_edit'=>'required','overTimeDate_edit'=>'required','overTimeHours_edit'=>'required','overtimeStatus_edit'=>'required','overtimeComments_edit'=>'required');
                $attributes = array('userId_edit'=>'Employee','overTimeDate_edit'=>'Overtime Date','overTimeHours_edit'=>'Overtime Hours','overtimeStatus_edit'=>'Overtime Status','overtimeComments_edit'=>'Reason/Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $overtime_user_id = $data['userId_edit'];
                $overtime_date = CommonHelper::formatDate(trim($data['overTimeDate_edit']));
                
                $updateArray = array('user_id'=>$overtime_user_id,'overtime_date'=>$overtime_date,'overtime_hours'=>trim($data['overTimeHours_edit']),'comments'=>trim($data['overtimeComments_edit']),'overtime_status'=>trim($data['overtimeStatus_edit']));
                $overtime = User_overtime::where('id',$data['overtime_id_edit'])->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Overtime updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'delete_overtime'){
                $updateArray = array('is_deleted'=>1);
                User_overtime::where('id',trim($data['id']))->update($updateArray);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Overtime deleted successfully'),200);
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            $overtime_list = \DB::table('user_overtime as ul')
            ->join('users as u','u.id', '=', 'ul.user_id')  
            ->join('user_roles as ur','ur.id', '=', 'u.user_type');
            
            if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                $start_date = trim($search_date['start_date']);
                $end_date = trim($search_date['end_date']);
                $overtime_list = $overtime_list->whereRaw("( (ul.overtime_date >= '$start_date' AND ul.overtime_date <= '$end_date')  )");        
            }
            
            $overtime_list = $overtime_list->where('ul.is_deleted',0)        
            ->select('ul.*','ur.role_name','u.name as user_name')        
            ->orderBy('ul.id','DESC')        
            ->paginate($rec_per_page);        
            
            $user_list = \DB::table('users as u')
            ->join('user_roles as ur','ur.id', '=', 'u.user_type')        
            ->where('u.is_deleted',0)        
            ->where('u.user_type','!=',9)->where('u.user_type','!=',15)  
            ->select('u.*','ur.role_name')        
            ->orderBy('u.name')        
            ->get()->toArray();        
            
            return view('user/overtime_list',array('overtime_list'=>$overtime_list,'error_message'=>'','user_list'=>$user_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER_OVERTIME',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('user/overtime_list',array('error_message'=>$e->getMessage()));
            }
        }  
    }    
    
    public function listSalary(Request $request,$id){
        try{
           
            $data = $request->all();
            $user_id = $id;
            $rec_per_page = 50;
            
            $profile_data = User_profile::where('user_id',$user_id)->first();
            
            if(isset($data['action']) && $data['action'] == 'add_salary'){
                $validateionRules = array('salaryMonth_add'=>'required');
                $attributes = array('salaryMonth_add'=>'Month');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                if(empty($profile_data) || empty($profile_data->annual_ctc)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Annual CTC not added in profile', 'errors' =>'Annual CTC not added in profile' ));
                }
                
                $month_year = explode('_',trim($data['salaryMonth_add']));
                
                $salary_added = User_salary::where('user_id',$user_id)->where('salary_month',$month_year[0])->where('salary_year',$month_year[1])->where('is_deleted',0)->first();
                if(!empty($salary_added)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Salary already added for this month/year', 'errors' =>'Salary already added for this month/year' ));
                }
                
                $insertArray = array('user_id'=>$user_id,'salary_month'=>$month_year[0],'salary_year'=>$month_year[1],'annual_ctc'=>$profile_data->annual_ctc,'monthly_salary'=>$profile_data->monthly_salary);
                $salary = User_salary::create($insertArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Salary added successfully'),200);
            }
            
            $salary_list = \DB::table('user_salary as us')
            ->join('users as u','u.id', '=', 'us.user_id')        
            ->where('us.is_deleted',0)         
            ->where('u.is_deleted',0)        
            ->where('u.id',$user_id)
            ->select('us.*','u.name as user_name')        
            ->orderByRaw('us.salary_month,us.salary_year')        
            ->paginate($rec_per_page);        
            
            $user_data = User::where('id',$user_id)->first();
            
            return view('user/salary_list',array('salary_list'=>$salary_list,'error_message'=>'','user_data'=>$user_data,'profile_data'=>$profile_data));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER_PROFILE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('user/salary_list',array('error_message'=>$e->getMessage()));
            }
        }  
    }
    
    public function editSalary(Request $request,$id,$page_action='edit'){
        try{
           
            $data = $request->all();
            $salary_id = $id;
            $full_day_leave_timestamps = $half_day_leave_timestamps = $full_day_absent_timestamps = $half_day_absent_timestamps = array();
           
            if(isset($data['action']) && $data['action'] == 'update_salary'){
                $validateionRules = array('net_salary'=>'required|numeric','basic'=>'nullable|numeric','da'=>'nullable|numeric','hra'=>'nullable|numeric','conveyance'=>'nullable|numeric','medical'=>'nullable|numeric','lta'=>'nullable|numeric','overtime_wages'=>'nullable|numeric','leaves_deduction'=>'nullable|numeric','pf_deduction'=>'nullable|numeric');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                if(!empty($data['overtime_hours']) && empty($data['overtime_hourly_rate'])){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Overtime Hourly Rate not added in profile', 'errors' =>'Overtime Hourly Rate not added in profile'));
                }
                
                $basic = (!empty($data['basic']))?trim($data['basic']):null;
                $da = (!empty($data['da']))?trim($data['da']):null;
                $hra = (!empty($data['hra']))?trim($data['hra']):null;
                $conveyance = (!empty($data['conveyance']))?trim($data['conveyance']):null;
                $medical = (!empty($data['medical']))?trim($data['medical']):null;
                $lta = (!empty($data['lta']))?trim($data['lta']):null;
                $overtime_wages = (!empty($data['overtime_wages']))?trim($data['overtime_wages']):null;
                $leaves_deduction = (!empty($data['leaves_deduction']))?trim($data['leaves_deduction']):null;
                $pf_deduction = (!empty($data['pf_deduction']))?trim($data['pf_deduction']):null;
                $net_salary = (!empty($data['net_salary']))?trim($data['net_salary']):null;
                $overtime_hours = (!empty($data['overtime_hours']))?trim($data['overtime_hours']):null;
                $overtime_hourly_rate = (!empty($data['overtime_hourly_rate']))?trim($data['overtime_hourly_rate']):null;
                $approved_leaves = (!empty($data['approved_leaves']))?trim($data['approved_leaves']):null;
                $unapproved_leaves = (!empty($data['unapproved_leaves']))?trim($data['unapproved_leaves']):null;
                $status = 'created';
                
                $updateArray = array('basic'=>$basic,'da'=>$da,'hra'=>$hra,'conveyance'=>$conveyance,'medical'=>$medical,'lta'=>$lta,'overtime_hours'=>$overtime_hours,
                'overtime_hourly_rate'=>$overtime_hourly_rate,'overtime_wages'=>$overtime_wages,'approved_leaves'=>$approved_leaves,'unapproved_leaves'=>$unapproved_leaves,
                'leaves_deduction'=>$leaves_deduction,'pf_deduction'=>$pf_deduction,'net_salary'=>$net_salary,'status'=>$status,'comments'=>trim($data['comments']));
                
                $salary = User_salary::where('id',$salary_id)->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Salary updated successfully'),200);
            }
            
            $salary_data = \DB::table('user_salary as us')
            ->join('users as u','u.id', '=', 'us.user_id')        
            ->join('user_roles as ur','ur.id', '=', 'u.user_type')           
            ->where('us.is_deleted',0)         
            ->where('u.is_deleted',0)        
            ->where('us.id',$salary_id)
            ->select('us.*','u.name as user_name','role_name')        
            ->first();        
            
            $user_id = $salary_data->user_id;
            $profile_data = User_profile::where('user_id',$user_id)->first();
            
            // Get user approved leaves
            $start_date = $salary_data->salary_year.'/'.$salary_data->salary_month.'/1';
            $end_day = date('t',strtotime($start_date));
            $end_date = $salary_data->salary_year.'/'.$salary_data->salary_month.'/'.$end_day;
            
            $user_leaves = User_leaves::
            where('user_id',$user_id)        
            ->whereRaw("( (from_date <= '$start_date' AND to_date >= '$start_date') OR (from_date <= '$end_date' AND to_date >= '$end_date') OR (from_date <= '$start_date' AND to_date >= '$end_date') OR (from_date >= '$start_date' AND to_date <= '$end_date') )")        
            ->where('leave_status','approved')        
            ->where('is_deleted',0)
            ->get()->toArray();
            
            // Get user approved overtime
            $user_overtime = User_overtime::where('user_id',$user_id)        
            ->whereRaw("MONTH(overtime_date) = ".$salary_data->salary_month) 
            ->whereRaw("YEAR(overtime_date) = ".$salary_data->salary_year)         
            ->where('overtime_status','approved')        
            ->where('is_deleted',0)
            ->get()->toArray();
            
            // Get approved leaves days timestamps
            for($i=0;$i<count($user_leaves);$i++){
                $days = CommonHelper::dateDiff($user_leaves[$i]['from_date'],$user_leaves[$i]['to_date']);
                for($q=0;$q<=$days;$q++){
                    $timestamp = strtotime("+$q days",strtotime($user_leaves[$i]['from_date']));
                    
                    if($user_leaves[$i]['leave_type'] == 'full_day'){
                        $full_day_leave_timestamps[] = $timestamp;
                    }else{
                        $half_day_leave_timestamps[] = $timestamp;
                    }
                }
                
                $salary_month_days = 0; 
                for($q=0;$q<=$days;$q++){
                    $timestamp = strtotime("+$q days",strtotime($user_leaves[$i]['from_date']));
                    $month = date('m',$timestamp);
                    $year = date('Y',$timestamp);
                    if($month == $salary_data->salary_month && $year == $salary_data->salary_year){
                        $salary_month_days++;
                    }
                }
                
                $user_leaves[$i]['salary_month_days'] = $salary_month_days;
            }
            
            // Get user absents from attendance
            $user_absents = User_attendance::where('user_id',$user_id)
            ->whereRaw("MONTH(attendance_date) = ".$salary_data->salary_month)       
            ->whereRaw("YEAR(attendance_date) = ".$salary_data->salary_year)  
            ->wherein('attendance_status',array(0,2))      
            ->where('is_deleted',0)        
            ->get()->toArray();        
            
            // Get absents days timestamps if they are not in leaves
            for($i=0;$i<count($user_absents);$i++){
                $timestamp = strtotime($user_absents[$i]['attendance_date']);
                if($user_absents[$i]['attendance_status'] == 0 && !in_array($timestamp,$full_day_leave_timestamps)){
                    $full_day_absent_timestamps[] = $timestamp;
                }
                
                if($user_absents[$i]['attendance_status'] == 2 && !in_array($timestamp,$half_day_leave_timestamps)){
                    $half_day_absent_timestamps[] = $timestamp;
                }
            }
            
            sort($full_day_absent_timestamps);
            sort($half_day_absent_timestamps);
            
            return view('user/salary_edit',array('salary_data'=>$salary_data,'error_message'=>'','profile_data'=>$profile_data,'user_leaves'=>$user_leaves,'user_overtime'=>$user_overtime,'start_date'=>$start_date,'full_day_absent_timestamps'=>$full_day_absent_timestamps,'half_day_absent_timestamps'=>$half_day_absent_timestamps,'page_action'=>$page_action));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER_PROFILE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('user/salary_edit',array('error_message'=>$e->getMessage()));
            }
        }  
    }
    
    public function viewSalary(Request $request,$id){
        return $this->editSalary($request,$id,'view');
    }
    
    public function editUserAttendance(Request $request,$id,$page_action='edit'){
        try{
            //0 = absent, 1 = present, 2 =  half day 
            $data = $request->all();
            $attendance_list = $users_leaves = array();
            $user_id = $id;
            
            if(isset($data['action']) && $data['action'] == 'update_attendance'){
                \DB::beginTransaction();
                
                $days = CommonHelper::dateDiff($data['start_date'],$data['end_date']);
                for($q=0;$q<=$days;$q++){
                    $timestamp = strtotime("+$q days",strtotime($data['start_date']));
                    $date = date('Y_m_d',$timestamp);
                    $key = 'attendance_'.$date;
                    $attendance_status = trim($data[$key]);
                    $attendance_date = date('Y/m/d',$timestamp);
                    
                    $attendance_exists = User_attendance::where('user_id',$user_id)->where('attendance_date',$attendance_date)->count();
                    
                    if($attendance_exists > 0){
                        $updateArray = array('attendance_status'=>$attendance_status,'is_deleted'=>0);
                        User_attendance::where('user_id',$user_id)->where('attendance_date',$attendance_date)->update($updateArray);
                    }else{
                        $insertArray = array('user_id'=>$user_id,'attendance_status'=>$attendance_status,'attendance_date'=>$attendance_date);
                        User_attendance::create($insertArray);
                    }
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Attendance updated successfully'),200);
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data,true,'-1 month');
            $start_date = trim($search_date['start_date']);
            $end_date = trim($search_date['end_date']);
            
            $user_attendance = User_attendance::where('user_id',$user_id)
            ->whereRaw("attendance_date BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
            ->where('is_deleted',0)
            ->get()->toArray();
            
            for($i=0;$i<count($user_attendance);$i++){
                $key = str_replace(array('/','-'),'_',$user_attendance[$i]['attendance_date']);
                $attendance_list[$key] = $user_attendance[$i];
            }
            
            $users_approved_leaves = User_leaves::
            where('user_id',$user_id)        
            ->whereRaw("( (from_date <= '$start_date' AND to_date >= '$start_date') OR (from_date <= '$end_date' AND to_date >= '$end_date') OR (from_date <= '$start_date' AND to_date >= '$end_date') OR (from_date >= '$start_date' AND to_date <= '$end_date') )")        
            ->where('leave_status','approved')        
            ->where('is_deleted',0)
            ->get()->toArray();

            for($i=0;$i<count($users_approved_leaves);$i++){
                $days = CommonHelper::dateDiff($users_approved_leaves[$i]['from_date'],$users_approved_leaves[$i]['to_date']);
                for($q=0;$q<=$days;$q++){
                    $timestamp = strtotime("+$q days",strtotime($users_approved_leaves[$i]['from_date']));
                    $leave_date = date('Y_m_d',$timestamp);
                    $users_leaves[$leave_date] = $users_approved_leaves[$i]['leave_type'];
                }
            }
            
            $user_data = \DB::table('users as u')
            ->join('user_roles as ur','ur.id', '=', 'u.user_type')    
            ->where('u.id',$user_id)        
            ->where('u.is_deleted',0)        
            ->select('u.*','ur.role_name')        
            ->first();  
           
            return view('user/user_attendance_edit',array('attendance_list'=>$attendance_list,'error_message'=>'','start_date'=>$start_date,'end_date'=>$end_date,'users_leaves'=>$users_leaves,'user_data'=>$user_data,'page_action'=>$page_action));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER_ATTENDANCE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('user/user_attendance_edit',array('error_message'=>$e->getMessage().', Line: '.$e->getLine(),'user'=>array()));
            }
            
        }  
    }
    
    public function viewUserAttendance(Request $request,$id){
        return $this->editUserAttendance($request,$id,'view');
    }
    
    function userActivityList(Request $request){
        try{
            $data = $request->all();
            
            if(isset($data['action']) && $data['action'] == 'get_role_users'){
                $role_id = trim($data['role_id']);
                $users_list = User::where('is_deleted',0);
                if(!empty($role_id)){
                    $users_list = $users_list->where('user_type',$role_id);
                } 
                $users_list = $users_list->orderBy('name')->get()->toArray();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'User List','users_list'=>$users_list),200);
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            $start_date = $search_date['start_date'];
            $end_date = $search_date['end_date'];
            
            $logs_list = \DB::table('app_logs as al')
            ->leftJoin('user_roles as ur','ur.id', '=', 'al.role_id')
            ->leftJoin('users as u','u.id', '=', 'al.user_id');        
            
            if(isset($data['role_id']) && !empty($data['role_id'])){
                $logs_list = $logs_list->where('al.role_id',trim($data['role_id']));
            }
            
            if(isset($data['user_id']) && !empty($data['user_id'])){
                $logs_list = $logs_list->where('al.user_id',trim($data['user_id']));
            }
            
            if(!empty($start_date) && !empty($end_date)){
                $logs_list = $logs_list->whereRaw("al.created_at BETWEEN '$start_date' AND '$end_date'");      
            }
            
            $logs_list = $logs_list->select('al.*','ur.role_name','u.name as user_name','u.email');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $logs_list = $logs_list->offset($start)->limit($limit)->orderBy('id','ASC')->get()->toArray();
            }else{
                $logs_list = $logs_list->orderBy('id','DESC')->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=user_activity_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Log ID','Title','Type','User ','User Type','Date');

                $callback = function() use ($logs_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($logs_list);$i++){
                        $array = array($logs_list[$i]->id,$logs_list[$i]->log_title,$logs_list[$i]->log_type,$logs_list[$i]->user_name,$logs_list[$i]->role_name,date('d-m-Y',strtotime($logs_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $roles_list = User_roles::where('role_status',1)->orderBy('role_name')->get()->toArray();
            
            $users_list = User::where('is_deleted',0)->orderBy('name')->get()->toArray();
            
            return view('admin/user_activity_list',array('logs_list'=>$logs_list,'roles_list'=>$roles_list,'users_list'=>$users_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'USER',__FUNCTION__,__FILE__);
            return view('admin/user_activity_list',array('error_message'=>$e->getMessage(),'users_list'=>array()));
        }
    }
}
