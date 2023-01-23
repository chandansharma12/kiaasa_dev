<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Store_staff;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class StoreStaffController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
        
    }
    
    function dashboard(Request $request){
        
    }
    
    function listing(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $store_data = CommonHelper::getUserStoreData($user->id);
            
            if(isset($data['action']) && $data['action'] == 'add_store_staff'){
                $data['store_id'] = $store_data->id;
                return $this->add($data);
            }
            
            if(isset($data['action']) && $data['action'] == 'get_store_staff_data'){
                return $this->data($data);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_store_staff_data'){
                return $this->update($data);
            }
            
            $staff_list = \DB::table('store_staff as ss')->where('ss.is_deleted',0);
            $staff_list = $staff_list->where('store_id',$store_data->id);
            
            if(isset($data['u_name']) && !empty($data['u_name'])){
                $name = trim($data['u_name']);
                $staff_list = $staff_list->whereRaw("(name like '%{$name}%')");
            }
            
            $staff_list = $staff_list->select('ss.*')->paginate(30);
            
            return view('store/staff_list',array('staff_list'=>$staff_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_STAFF',__FUNCTION__,__FILE__);
            return view('store/staff_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function data($data){
        try{
            $store_staff_data = \DB::table('store_staff as ss')->where('ss.id',$data['id'])->select('ss.*')->first();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'data','store_staff_data' => $store_staff_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_STAFF',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function update($data){
        try{
            $store_staff_id = $data['store_staff_id'];
            
            $validateionRules = array('name_edit'=>'required','phone_no_edit'=>'required','status_edit'=>'required');
            $attributes = array('name_edit'=>'Name','phone_no_edit'=>'Phone No','status_edit'=>'Status');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $updateArray = array('name'=>$data['name_edit'],'phone_no'=>$data['phone_no_edit'],'address'=>$data['address_edit'],'status'=>trim($data['status_edit']));
            
            Store_staff::where('id', '=', $store_staff_id)->update($updateArray);
            
            CommonHelper::createLog('Store staff Updated. ID: '.$store_staff_id,'STORE_STAFF_UPDATED','VENDOR');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store staff updated successfully'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_STAFF',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function add($data){
        try{
            
            $validateionRules = array('name_add'=>'required','phone_no_add'=>'required');
            
            $attributes = array('name_add'=>'Name','phone_no_add'=>'Phone No');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $insertArray = array('store_id'=>$data['store_id'],'name'=>$data['name_add'],'phone_no'=>$data['phone_no_add'],'address'=>$data['address_add']);
           
            $store_staff = Store_staff::create($insertArray);
            
            CommonHelper::createLog('Store staff Created. ID: '.$store_staff->id,'STORE_STAFF_ADDED','STORE_STAFF');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store Staff added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_STAFF',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateStatus(Request $request){
        try{
            
            $data = $request->all();
            $user_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Vendors');
            
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
                
            Vendor_detail::whereIn('id',$user_ids)->update($updateArray);
            
            CommonHelper::createLog('Vendor Updated. IDs: '.$data['ids'],'VENDOR_UPDATED','VENDOR');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Vendors updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    
    
    
    
}
