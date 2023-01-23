<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
    public function add(Request $request){
        try{
            
        }catch (\Exception $e){
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }

    public function update(Request $request){
        try{
            
            $data = $request->all();
            $setting_id = $data['setting_edit_id'];
            
            $validateionRules = array('setting_value_edit'=>'required');
            $attributes = array('setting_value_edit'=>'Setting Value');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $updateArray = array('setting_value'=>$data['setting_value_edit']);
           
            Setting::where('id', '=', $setting_id)->update($updateArray);
            
            CommonHelper::createLog('Setting Updated. ID: '.$setting_id,'SETTING_UPDATED','SETTINGS');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Setting updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SETTINGS',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function settingList(Request $request){
        try{
            $data = $request->all();
            $setting_list = Setting::paginate(30);
            
            return view('admin/setting_list',array('setting_list'=>$setting_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SETTINGS',__FUNCTION__,__FILE__);
            return view('admin/setting_list',array('error_message'=>$e->getMessage(),'setting_list'=>array()));
        }
    }
    
    function settingData(Request $request,$id){
        try{
            $data = $request->all();
            $setting_data = Setting::where('id',$id)->select('*')->first();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Setting data','setting_data' =>$setting_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SETTINGS',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function settingUpdateStatus(Request $request){
        try{
            
            $data = $request->all();
            $setting_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Settings');
            
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
                
            Setting::whereIn('id',$setting_ids)->update($updateArray);
            
            CommonHelper::createLog('Setting Updated. IDs: '.$data['ids'],'SETTING_UPDATED','SETTINGS');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Settings updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SETTINGS',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function getGSTRules(Request $request){
        try{
            $data = $request->all();
            $gst_rules = \DB::table('gst_rates')->where('is_deleted',0)->select('*')->orderBy('id','ASC')->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'GST Data','gst_rules'=>$gst_rules,'status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SETTINGS',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
}
