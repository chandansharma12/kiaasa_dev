<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Store_asset;
use App\Models\Store_asset_detail;
use App\Models\Store_asset_order;
use App\Models\Store_asset_order_detail;
use App\Models\Store_asset_bills;
use App\Models\Store_products_demand;
use App\Models\Store_products_demand_detail;
use App\Models\Store_staff;
use App\Models\Store_user;
use App\Models\Design_lookup_items_master;
use App\Models\Product_category_master; 
use App\Models\Pos_product_master;
use App\Models\Pos_product_master_inventory;
use App\Models\Production_size_counts;
use App\Models\Store_products_demand_inventory;
use App\Models\Pos_customer_orders_detail;
use App\Models\Store_inventory_review;
use App\Models\Store_inventory_balance;
use App\Models\Store_products_demand_courier;
use App\Models\Store_products_demand_sku;
use App\Models\Debit_notes;
use App\Models\Debit_note_items;
use App\Models\Store_bags_inventory;
use App\Helpers\CommonHelper;
use PDF;
use Validator;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    
    public function __construct(){
        
    }
    
    function dashboard(Request $request){
        try{ 
            $user = Auth::user();
            
            $user_store = \DB::table('store_users as su')->where('user_id',$user->id)->where('is_deleted',0)->first();
            return view('store/dashboard',array('error_message'=>'','user'=>$user,'user_store'=>$user_store));
        }catch (\Exception $e){
            return view('store/dashboard',array('error_message'=>$e->getMessage()));
        }
    }
    
    public function addStore(Request $request){
        try{
            
            $data = $request->all();
            
            $validateionRules = array('store_name_add'=>'required','store_region_add'=>'required','store_phone_no_add'=>'required','store_gst_no_add'=>'required','store_gst_applicable_add'=>'required','store_type_add'=>'required',
            'store_address_line1_add'=>'required','store_address_line2_add'=>'required','store_city_name_add'=>'required','store_postal_code_add'=>'required','store_gst_name_add'=>'required','store_state_add'=>'required','store_code_add'=>'required',
            'store_zone_add'=>'required','store_info_type_add'=>'required','store_google_name_add'=>'required','store_display_name_add'=>'required','store_front_picture_add'=>'required|image|mimes:jpeg,png,jpg,gif|max:3072',
            'store_back_picture_add'=>'required|image|mimes:jpeg,png,jpg,gif|max:3072');
            
            $attributes = array('store_name_add'=>'Name','store_region_add'=>'Region','store_phone_no_add'=>'Phone No','store_gst_no_add'=>'GST No','store_address_line1_add'=>'Address Line 1','store_state_add'=>'State',
            'store_address_line2_add'=>'Address Line 2','store_city_name_add'=>'City Name','store_postal_code_add'=>'Postal Code','store_gst_name_add'=>'GST Name','store_gst_applicable_add'=>'GST Applicable','store_type_add'=>'Store Type',
            'store_code_add'=>'Store Code','store_zone_add'=>'Store Zone','store_info_type_add'=>'Store Info Type','store_google_name_add'=>'Store Google Address','store_display_name_add'=>'Store Display Name','store_front_picture_add'=>'Front Picture',
            'store_back_picture_add'=>'Back Picture');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $storeExists = Store::where('store_name',$data['store_name_add'])->where('region_id',$data['store_region_add'])->where('is_deleted',0)->first();
            if(!empty($storeExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store already exists in region', 'errors' => 'Store already exists in region'));
            }
            
            $storeCodeExists = Store::where('store_code',$data['store_code_add'])->where('is_deleted',0)->first();
            if(!empty($storeCodeExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store Code assigned to other Store', 'errors' => 'Store Code assigned to other Store'));
            }
            
            $front_picture_name = CommonHelper::uploadImage($request,$request->file('store_front_picture_add'),'images/store_images');
            $back_picture_name = CommonHelper::uploadImage($request,$request->file('store_back_picture_add'),'images/store_images');
            
            $store_id_code = str_ireplace(['ks','kf'],['',''], trim($data['store_code_add']));
            $prefix = ($data['store_type_add'] == 1)?'KS':'KF';
            $store_id_code = $prefix.$store_id_code;
            
            $slug = CommonHelper::getSlug($data['store_name_add']);
            
            $insertArray = array('store_name'=>$data['store_name_add'],'region_id'=>$data['store_region_add'],'address_line1'=>$data['store_address_line1_add'],'phone_no'=>$data['store_phone_no_add'],'state_id'=>$data['store_state_add'],'store_type'=>$data['store_type_add'],
            'gst_no'=>$data['store_gst_no_add'],'address_line2'=>$data['store_address_line2_add'],'city_name'=>$data['store_city_name_add'],'postal_code'=>$data['store_postal_code_add'],'gst_name'=>$data['store_gst_name_add'],'gst_applicable'=>$data['store_gst_applicable_add'],
            'store_code'=>trim($data['store_code_add']),'zone_id'=>trim($data['store_zone_add']),'store_id_code'=>$store_id_code,'store_info_type'=>trim($data['store_info_type_add']),'google_name'=>trim($data['store_google_name_add']),'display_name'=>trim($data['store_display_name_add']),
            'front_picture'=>$front_picture_name,'back_picture'=>$back_picture_name,'latitude'=>trim($data['store_latitude_add']),'longitude'=>trim($data['store_longitude_add']),'slug'=>$slug);
            
            $store = Store::create($insertArray);
            CommonHelper::createLog('New Store Created, ID: '.$store->id,'STORE_CREATED','STORE');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateStore(Request $request){
        try{
            
            $data = $request->all();
            $front_picture_name = $back_picture_name = '';
            $store_id = $data['store_edit_id'];
            
            $validateionRules = array('store_name_edit'=>'required','store_region_edit'=>'required','store_phone_no_edit'=>'required','store_gst_no_edit'=>'required','store_gst_applicable_edit'=>'required','store_type_edit'=>'required',
            'store_address_line1_edit'=>'required','store_address_line2_edit'=>'required','store_city_name_edit'=>'required','store_postal_code_edit'=>'required','store_gst_name_edit'=>'required','store_state_edit'=>'required','store_code_edit'=>'required',
            'store_zone_edit'=>'required','store_info_type_edit'=>'required','store_google_name_edit'=>'required','store_display_name_edit'=>'required','store_front_picture_edit'=>'image|mimes:jpeg,png,jpg,gif|max:3072',
            'store_back_picture_edit'=>'image|mimes:jpeg,png,jpg,gif|max:3072','store_ecommerce_status_edit'=>'required');
            
            $attributes = array('store_name_edit'=>'Name','store_region_edit'=>'Region','store_phone_no_edit'=>'Phone No','store_gst_no_edit'=>'GST No','store_gst_applicable_edit'=>'GST Applicable','store_state_edit'=>'State',
            'store_address_line1_edit'=>'Address Line 1','store_address_line2_edit'=>'Address Line 2','store_city_name_edit'=>'City Name','store_postal_code_edit'=>'Postal Code','store_gst_name_edit'=>'GST Name','store_type_edit'=>'Store Type',
            'store_code_edit'=>'Store Code','store_zone_edit'=>'Store Zone','store_info_type_edit'=>'Store Info Type','store_google_name_edit'=>'Store Google Address','store_display_name_edit'=>'Store Display Name','store_front_picture_edit'=>'Front Picture',
            'store_back_picture_edit'=>'Back Picture','store_ecommerce_status_edit'=>'Ecommerce Status');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $storeExists = Store::where('store_name',$data['store_name_edit'])->where('region_id',$data['store_region_edit'])->where('id','!=',$store_id)->where('is_deleted',0)->first();
            if(!empty($storeExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store already exists in region', 'errors' => 'Store already exists in region'));
            }
            
            $storeCodeExists = Store::where('store_code',$data['store_code_edit'])->where('id','!=',$store_id)->where('is_deleted',0)->first();
            if(!empty($storeCodeExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store Code assigned to other store', 'errors' => 'Store Code assigned to other store'));
            }
            
            if(!empty($request->file('store_front_picture_edit'))){
                $front_picture_name = CommonHelper::uploadImage($request,$request->file('store_front_picture_edit'),'images/store_images');
            }
            
            if(!empty($request->file('store_back_picture_edit'))){
                $back_picture_name = CommonHelper::uploadImage($request,$request->file('store_back_picture_edit'),'images/store_images');
            }
            
            $slug = CommonHelper::getSlug($data['store_name_edit']);
            
            $updateArray = array('store_name'=>$data['store_name_edit'],'region_id'=>$data['store_region_edit'],'address_line1'=>$data['store_address_line1_edit'],'phone_no'=>$data['store_phone_no_edit'],'state_id'=>$data['store_state_edit'],'store_type'=>$data['store_type_edit'],
            'gst_no'=>$data['store_gst_no_edit'],'address_line2'=>$data['store_address_line2_edit'],'city_name'=>$data['store_city_name_edit'],'postal_code'=>$data['store_postal_code_edit'],'gst_name'=>$data['store_gst_name_edit'],'gst_applicable'=>$data['store_gst_applicable_edit'],
            'store_code'=>trim($data['store_code_edit']),'gst_type'=>trim($data['store_gst_type_edit']),'zone_id'=>trim($data['store_zone_edit']),'store_info_type'=>trim($data['store_info_type_edit']),'google_name'=>trim($data['store_google_name_edit']),
            'display_name'=>trim($data['store_display_name_edit']),'latitude'=>trim($data['store_latitude_edit']),'longitude'=>trim($data['store_longitude_edit']),'ecommerce_status'=>trim($data['store_ecommerce_status_edit']),'slug'=>$slug);
            
            if(!empty($front_picture_name)){
                $updateArray['front_picture'] = $front_picture_name;
            }
            
            if(!empty($back_picture_name)){
                $updateArray['back_picture'] = $back_picture_name;
            }
           
            unset($updateArray['store_code']);
            
            Store::where('id', '=', $store_id)->update($updateArray);
            CommonHelper::createLog('Store Updated, ID: '.$store_id,'STORE_UPDATED','STORE');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function storeList(Request $request){
        try{
            $data = $request->all();
            
            $store_list = \DB::table('store as s')
            ->join('design_lookup_items_master as dlim','s.region_id', '=', 'dlim.id')
            ->join('state_list as sl','s.state_id', '=', 'sl.id')        
            ->leftJoin('design_lookup_items_master as zone','s.zone_id', '=', 'zone.id')                
            ->where('s.is_deleted',0)
            ->select('s.*','dlim.name as region_name','sl.state_name','zone.name as zone_name');
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'s.id','name'=>'s.store_name','region'=>'region_name','status'=>'s.status','created'=>'s.created_at','updated'=>'s.updated_at','state'=>'sl.state_name','google_name'=>'s.google_name','display_name'=>'s.display_name',
                'address1'=>'s.address_line1','address2'=>'s.address_line2','bags_inv'=>'s.bags_inventory','gst_no'=>'s.gst_no','gst_name'=>'s.gst_name','type'=>'s.store_type','zone'=>'zone.name','code'=>'s.store_id_code');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'s.id';
                $store_list = $store_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }
            
            if(isset($data['action']) &&  $data['action'] == 'update_access_key'){
                $store_id = trim($data['id']);
                $access_key = md5(time());
                
                $updateArray = array('api_access_key'=>$access_key);
                Store::where('id', '=', $store_id)->update($updateArray);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Access Key updated successfully'),200);
            }
            
            if(isset($data['store_type']) && !empty($data['store_type'])){
                $store_list = $store_list->where('s.store_type',$data['store_type']);
            }
            
            if(isset($data['state_id']) && !empty($data['state_id'])){
                $store_list = $store_list->where('s.state_id',$data['state_id']);
            }
            
            if(isset($data['zone_id']) && !empty($data['zone_id'])){
                $store_list = $store_list->where('s.zone_id',$data['zone_id']);
            }
            
            if(isset($data['s_name']) && !empty($data['s_name'])){
                $name = trim($data['s_name']);
                $store_list = $store_list->whereRaw("(s.store_name like '%{$name}%')");
            }
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $store_list = $store_list->where('s.id',trim($data['s_id']));
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $store_list = $store_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $store_list = $store_list->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=stores_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Store ID','Name','Code ','State','Address Line 1','Phone','Google Address','Display Name','GST No','GST Name','Type','Zone','Bags Inventory','Ecommerce Status','Created On');

                $callback = function() use ($store_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($store_list);$i++){
                        $store_type_text = ($store_list[$i]->store_type != null)?($store_list[$i]->store_type == 1)?'Kiaasa':'Franchise':'';
                        $ecomm_status = ($store_list[$i]->ecommerce_status == 1)?'Enabled':'Disabled';
                        
                        $array = array($store_list[$i]->id,$store_list[$i]->store_name,$store_list[$i]->store_id_code,$store_list[$i]->state_name,CommonHelper::filterCsvData($store_list[$i]->address_line1),$store_list[$i]->phone_no,CommonHelper::filterCsvData($store_list[$i]->google_name),$store_list[$i]->display_name,$store_list[$i]->gst_no,$store_list[$i]->gst_name,
                        $store_type_text,$store_list[$i]->zone_name,$store_list[$i]->bags_inventory,$ecomm_status,date('d-m-Y',strtotime($store_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $region_list = Design_lookup_items_master::where('type','STORE_ITEM_REGION')->where('is_deleted',0)->get()->toArray();
            
            $states_list = \DB::table('state_list')->where('is_deleted',0)->where('status',1)->orderBy('state_name')->get()->toArray();
            $store_zones = Design_lookup_items_master::where('type','STORE_ZONE')->get()->toArray();
            
           
            return view('admin/store_list',array('store_list'=>$store_list,'region_list'=>$region_list,'states_list'=>$states_list,'error_message'=>'','store_zones'=>$store_zones));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('admin/store_list',array('error_message'=>$e->getMessage(),'store_list'=>array()));
        }
    }
    
    function storeData(Request $request,$id){
        try{
            $data = $request->all();
            $store_data = Store::where('id',$id)->select('*')->first();
            
            if(!empty($store_data->api_access_key)){
                $from_date = date('d-m-Y',strtotime('-1 week'));
                $to_date = date('d-m-Y');
                $api_url = url('api/pos/store/orders/list?access_key='.$store_data->api_access_key.'&from='.$from_date.'&to='.$to_date);
            }else{
                $api_url = '';
            }
            
            $store_data->api_url = $api_url;
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store data','store_data' => $store_data),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function storeUpdateStatus(Request $request){
        try{
            
            $data = $request->all();
            $items_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Store');
            
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
                
            Store::whereIn('id',$items_ids)->update($updateArray);
            CommonHelper::createLog('Store Status Updated, IDs: '.$data['ids'],'STORE_STATUS_UPDATED','STORE');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Stores updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function userStoresList(Request $request){
        try{
            $data = $request->all();
            $user_stores_list = $user_store_ids = array();
            
            if(isset($data['action']) &&  $data['action'] == 'update_user_stores'){
                \DB::beginTransaction();
                
                $validateionRules = array('user_id'=>'required','stores_ids'=>'required');
                $attributes = array('user_id'=>'User','stores_ids'=>'Stores');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $user_id = trim($data['user_id']);
                $store_ids = explode(',',trim($data['stores_ids']));
                
                $updateArray = array('is_deleted'=>1);
                Store_user::where('user_id',$user_id)->update($updateArray);
                
                $user_stores = Store_user::where('user_id',$user_id)->get()->toArray();
                for($i=0;$i<count($user_stores);$i++){
                    $user_store_ids[] = $user_stores[$i]['id'];
                }
                
                for($i=0;$i<count($store_ids);$i++){
                    $store_id = $store_ids[$i];
                    if(in_array($store_id, $user_store_ids)){
                        $updateArray = array('is_deleted'=>0);
                        Store_user::where('user_id',$user_id)->where('store_id',$store_id)->update($updateArray);
                    }else{
                        $insertArray = array('store_id'=>$store_id,'user_id'=>$user_id);
                        Store_user::create($insertArray);
                    }
                }
                
                \DB::commit();
                
                CommonHelper::createLog('User Stores Updated. User ID: '.$user_id,'USER_STORE_UPDATED','USER_STORE');
                 
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'User Stores updated successfully','status' => 'success'),200);
            }
            
            $store_list = \DB::table('store as s')
            ->join('design_lookup_items_master as dlim','s.region_id', '=', 'dlim.id')
            ->join('state_list as sl','s.state_id', '=', 'sl.id')        
            ->leftJoin('design_lookup_items_master as zone','s.zone_id', '=', 'zone.id')                
            ->where('s.is_deleted',0)
            ->select('s.*','dlim.name as region_name','sl.state_name','zone.name as zone_name');
            
            if(isset($data['store_type']) && !empty($data['store_type'])){
                $store_list = $store_list->where('s.store_type',$data['store_type']);
            }
            
            if(isset($data['state_id']) && !empty($data['state_id'])){
                $store_list = $store_list->where('s.state_id',$data['state_id']);
            }
            
            if(isset($data['zone_id']) && !empty($data['zone_id'])){
                $store_list = $store_list->where('s.zone_id',$data['zone_id']);
            }
            
            $store_list = $store_list->orderBy('s.store_name')->get()->toArray();
            
            $states_list = \DB::table('state_list')->where('is_deleted',0)->where('status',1)->orderBy('state_name')->get()->toArray();
            $store_zones = Design_lookup_items_master::where('type','STORE_ZONE')->get()->toArray();
            
            $users_list = \DB::table('users')->where('user_type',9)->where('is_deleted',0)->where('status',1)->orderBy('name')->get()->toArray();
            
            if(isset($data['user_id']) && !empty($data['user_id'])){
                $user_stores_list = \DB::table('store_users as su')
                ->join('store as s','s.id', '=', 'su.store_id')   
                ->where('su.user_id',$data['user_id'])        
                ->where('su.is_deleted',0)
                ->where('s.is_deleted',0)        
                ->select('s.*')     
                ->orderBy('s.store_name')        
                ->get()->toArray();
                
                for($i=0;$i<count($user_stores_list);$i++){
                    $user_store_ids[] = $user_stores_list[$i]->id;
                }
            }
            
            return view('admin/user_stores_list',array('store_list'=>$store_list,'users_list'=>$users_list,'states_list'=>$states_list,'error_message'=>'','store_zones'=>$store_zones,'user_stores_list'=>$user_stores_list,'user_store_ids'=>$user_store_ids));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message'=>$e->getMessage()),500);
            }else{
                return view('admin/user_stores_list',array('error_message'=>$e->getMessage(),'store_list'=>array()));
            }
        }
    }
    
    public function listPosInventory(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            
            $store_user = \DB::table('store as s')->join('store_users as su','s.id', '=', 'su.store_id')->where('su.user_id',$user->id)->select('s.*')->first();
            
            if(!empty($store_user)){
                $products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('store as s','s.id', '=', 'ppmi.store_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')
                ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')   
                ->where('ppmi.store_id',$store_user->id)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->where('ppm.is_deleted',0);
                
                if(isset($data['status']) && !empty($data['status'])){
                    $products_list = $products_list->where('ppmi.product_status',trim($data['status']));
                }

                $products_list = $products_list->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','dlim_1.name as category_name','dlim_1.name as subcategory_name','s.store_name')
                ->orderBy('id','DESC')->paginate(30);

                $status_list = CommonHelper::getposProductStatusList();
            }else{
                $error_message = 'Store not assigned to user';
            }
            
            return view('store/pos_inventory_list',array('error_message'=>$error_message,'products_list'=>$products_list,'status_list'=>$status_list));
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS',__FUNCTION__,__FILE__);
            return view('store/pos_inventory_list',array('error_message'=>$e->getMessage()));
        }  
    }
    
    function posProductList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $pos_product_list = \DB::table('pos_product_master as ppm')
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
            ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)
            ->select('ppm.*','dlim_1.name as category_name','dlim_1.name as subcategory_name');
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'ppm.id','name'=>'ppm.product_name','barcode'=>'ppm.product_barcode','sku'=>'ppm.product_sku','category'=>'category_name',
                'subcategory'=>'subcategory_name','description'=>'ppm.product_description','base_price'=>'ppm.base_price','sale_price'=>'ppm.sale_price','status'=>'ppm.status','created'=>'ppm.created_at','updated'=>'ppm.updated_at');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'ppm.id';
                $pos_product_list = $pos_product_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }
            
            $pos_product_list = $pos_product_list->paginate(30);
            
            return view('store/pos_product_list',array('error_message'=>$error_message,'pos_product_list'=>$pos_product_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('store/pos_product_list',array('error_message'=>$e->getMessage(),'demands_list'=>array()));
        }
    }
    
    public function posInventoryList(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            $store_id = (isset($data['store_id']))?$data['store_id']:'';
            $store_data = CommonHelper::getUserStoreData($user->id);
                    
            $products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->where('ppmi.is_deleted',0)        
            ->where('ppm.is_deleted',0);
            
            $inventory_count = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('ppm.is_deleted',0);        
               
            if($is_fake_inventory_user){
                $products_list = $products_list->groupBy('ppm.product_sku')          
                ->where('ppmi.store_id',$store_id)         
                ->where('ppmi.status',1)
                ->where('ppmi.fake_inventory',1)
                ->where('ppm.fake_inventory',1);        
                
                $inventory_count = $inventory_count
                ->where('ppmi.store_id',$store_id)               
                ->where('ppmi.fake_inventory',1)
                ->where('ppm.fake_inventory',1);            
            }else{
                $products_list = $products_list->groupBy('ppm.id')          
                ->where('ppmi.store_id',$store_data->id)         
                ->where('ppmi.status',1)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0);        
                
                $inventory_count = $inventory_count
                ->where('ppmi.store_id',$store_data->id)               
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0);            
            }        
            
            if(isset($data['inv_type']) && !empty($data['inv_type'])){
                if($data['inv_type'] == 1){
                    $products_list = $products_list->where('ppmi.arnon_inventory',0);
                    $inventory_count = $inventory_count->where('ppmi.arnon_inventory',0);
                }
                if($data['inv_type'] == 2){
                    $products_list = $products_list->where('ppmi.arnon_inventory',1);
                    $inventory_count = $inventory_count->where('ppmi.arnon_inventory',1);
                }
            }
            
            if(isset($data['barcode']) && !empty($data['barcode'])){
                $barcode = $sku = trim($data['barcode']);
                $sku = substr($sku,0, strrpos($sku, '-'));
                $products_list = $products_list->whereRaw("(ppm.product_name LIKE '%$barcode%' OR ppm.product_barcode = '$barcode' OR ppm.product_sku = '$barcode' OR ppm.product_sku = '$sku')");
                $inventory_count = $inventory_count->whereRaw("(ppm.product_name LIKE '%$barcode%' OR ppm.product_barcode = '$barcode' OR ppm.product_sku = '$barcode' OR ppm.product_sku = '$sku')");
            }
            
            if(isset($data['status']) &&  $data['status'] != '' ){
                $products_list = $products_list->where('ppmi.product_status',trim($data['status']));
                $inventory_count = $inventory_count->where('ppmi.product_status',trim($data['status']));
            }
            
            if(isset($data['category_search']) && !empty($data['category_search'])){
                $products_list = $products_list->where('ppm.category_id',$data['category_search']);
                $inventory_count = $inventory_count->where('ppm.category_id',$data['category_search']);
            }
            
            if(isset($data['product_subcategory_search']) && !empty($data['product_subcategory_search'])){
                $products_list = $products_list->where('ppm.subcategory_id',$data['product_subcategory_search']);
                $inventory_count = $inventory_count->where('ppm.subcategory_id',$data['product_subcategory_search']);
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('product_id'=>'ppm.id','inventory'=>'inventory_count','product_name'=>'ppm.product_name',
                'product_barcode'=>'ppm.product_barcode','sku'=>'ppm.product_sku','category'=>'category_name','subcategory'=>'subcategory_name','base_price'=>'ppm.base_price',
                'sale_price'=>'ppm.sale_price','status'=>'ppm.product_status');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'ppmi.id';
                $products_list = $products_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }else{
                $products_list = $products_list->orderBy('ppm.id','ASC');
            }

            $products_list = $products_list->select('ppmi.product_master_id','ppm.*','dlim_1.name as category_name','ppmi.store_base_rate',
            'dlim_2.name as subcategory_name','dlim_3.name as color_name','psc.size as size_name',\DB::Raw('count(ppmi.id) as inventory_count'));
            
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $products_list = $products_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $products_list = $products_list->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=inventory_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Product ID','Product Name','SKU','Inventory','Product Barcode','Category','SubCategory','Cost Price','Sale Price');

                $callback = function() use ($products_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($products_list);$i++){
                        $product_name = $products_list[$i]->product_name.' '.$products_list[$i]->size_name.' '.$products_list[$i]->color_name;
                        $array = array($products_list[$i]->product_master_id,$product_name,$products_list[$i]->product_sku,$products_list[$i]->inventory_count,$products_list[$i]->product_barcode,$products_list[$i]->category_name,$products_list[$i]->subcategory_name,$products_list[$i]->store_base_rate,$products_list[$i]->sale_price);
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $inventory_count = $inventory_count->count();
            
            $status_list = CommonHelper::getposProductStatusList();
            
            $category_list = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY'))->where('status',1)->get()->toArray();
            
            $store_list = CommonHelper::getStoresList();
            
            return view('store/pos_inventory_list',array('error_message'=>$error_message,'products_list'=>$products_list,'user'=>$user,'status_list'=>$status_list,'inventory_count'=>$inventory_count,'category_list'=>$category_list,'store_list'=>$store_list,'is_fake_inventory_user'=>$is_fake_inventory_user));
        }catch (\Exception $e){		
            
            CommonHelper::saveException($e,'POS',__FUNCTION__,__FILE__);
            return view('store/pos_inventory_list',array('error_message'=>$e->getMessage().', Line: '.$e->getLine()));
        }  
    }
    
    function inventoryPushDemandList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $store_user = CommonHelper::getUserStoreData($user->id); 
            
            $demands_list = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.store_id',$store_user->id)        
            ->where('spd.demand_type','inventory_push')
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))
            ->where('spd.status',1)
            ->where('spd.is_deleted',0)
            ->where('spd.fake_inventory',0)        
            ->select('spd.*','s.store_name');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $demands_list = $demands_list->offset($start)->limit($limit)->orderBy('spd.id','ASC')->get()->toArray();
            }else{
                $demands_list = $demands_list->orderBy('spd.id','DESC')->paginate(50);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_demands_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Demand ID','Invoice No','Store','Demand Status','Type','Created On');

                $callback = function() use ($demands_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($demands_list);$i++){
                        $demand_type = !empty($demands_list[$i]->push_demand_id)?'Complete Inventory Return':'Inventory Push';
                        $array = array($demands_list[$i]->id,$demands_list[$i]->invoice_no,$demands_list[$i]->store_name,str_replace('_',' ',$demands_list[$i]->demand_status),$demand_type,date('d-m-Y',strtotime($demands_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('store/inventory_push_demand_list',array('demands_list'=>$demands_list,'error_message'=>$error_message));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_push_demand_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function editInventoryPushDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')            
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])        
                ->where('ppmi.demand_id',$data['demand_id'])                
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')
                ->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists' ));
                }
                
                if($product_data->product_status > 3){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> already added', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> already added' ));
                }
                
                if($product_data->product_status < 3){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> not available', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> not available' ));
                }
                
                \DB::beginTransaction();
                
                $updateArray = array('product_status'=>4,'store_intake_date'=>date('Y/m/d H:i:s'));
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 
                
                //$demand_product = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->increment('store_intake_qty');
                
                Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->update(array('receive_status'=>1,'receive_date'=>date('Y/m/d H:i:s'))); 
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data with code: <b>'.$product_data->peice_barcode.'</b> added','product_data'=>$product_data,),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_demand_inventory'){
                $rec_per_page = 100;
                $product_list = \DB::table('store_products_demand_inventory as spdi')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')            
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('spdi.is_deleted',0)        
                ->where('spdi.demand_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0);
                
                if(isset($data['page_type']) && strtolower($data['page_type']) == 'edit'){
                    $product_list = $product_list->where('spdi.receive_status',1);
                }
                
                if(isset($data['barcode']) && !empty($data['barcode'])){
                    $product_list = $product_list->where('ppmi.peice_barcode',trim($data['barcode']));
                }  
                
                if(isset($data['product_id']) && !empty($data['product_id'])){
                    $product_list = $product_list->where('ppmi.product_master_id',trim($data['product_id']));
                }  
                
                $product_list = $product_list->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')
                ->orderBy('ppmi.store_intake_date','ASC')
                ->paginate($rec_per_page);
                        
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')             
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('spdi.receive_status',1)        
                //->where('ppmi.product_status','>=',4)         
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('spdi.is_deleted',0)        
                ->where('spdi.demand_status',1)        
                ->groupByRaw('ppmi.product_master_id')
                ->selectRaw('ppm.id,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                ->orderByRaw('poi.vendor_sku,psc.id')        
                ->get()->toArray();
                
                $inventory_total = \DB::table('store_products_demand_inventory as spdi')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('spdi.demand_id',$data['demand_id'])        
                ->where('spdi.is_deleted',0)      
                ->where('spdi.demand_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0);
                
                $inventory_received = clone $inventory_total;
                
                $inventory_total = $inventory_total->count();
                $inventory_received = $inventory_received->where('spdi.receive_status',1)->count();
                
                $inv_count_data = array('inventory_total'=>$inventory_total,'inventory_received'=>$inventory_received);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'inv_count_data' => $inv_count_data,'sku_list'=>$sku_list),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_count_data'){
                $inv_data = array('rec_count'=>0,'rec_base_price_sum'=>0,'rec_sale_price_sum'=>0);
                
                $inv_received = \DB::table('store_products_demand_inventory as spdi')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')   
                ->where('spdi.demand_id',$data['demand_id'])                
                ->where('spdi.receive_status',1)        
                ->where('spdi.is_deleted',0)->where('ppmi.is_deleted',0)->where('spdi.demand_status',1)     
                ->selectRaw('COUNT(spdi.id) as cnt,SUM(spdi.store_base_rate) as base_price_sum,SUM(ppmi.sale_price) as sale_price_sum,SUM(spdi.store_base_price) as sale_store_base_price')->first();
                
                if(!empty($inv_received)){
                    $inv_data['rec_count'] = $inv_received->cnt;
                    $inv_data['rec_base_price_sum'] = $inv_received->base_price_sum;
                    $inv_data['rec_sale_price_sum'] = $inv_received->sale_price_sum;
                    $inv_data['rec_sale_store_base_price'] = $inv_received->sale_store_base_price;
                }
                
                $inv_total = \DB::table('store_products_demand_inventory as spdi')->where('spdi.demand_id',$data['demand_id'])->where('spdi.is_deleted',0)->where('spdi.demand_status',1)->count();
                $inv_data['inv_total'] = $inv_total;
                
                if($inv_data['rec_count'] == $inv_data['inv_total']){
                    $demand_data = \DB::table('store_products_demand as spd')->where('spd.id',$demand_id)->first();
                    $demand_total_data = json_decode($demand_data->total_data);
                
                    $inv_data['rec_base_price_sum'] = round($demand_total_data->total_taxable_val,2);
                    $inv_data['rec_sale_price_sum'] = round($demand_total_data->total_sale_price,2);
                    $inv_data['rec_sale_store_base_price'] = round($demand_total_data->total_value,2);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','inv_data'=>$inv_data,'status' => 'success'),200);
            }
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name')->first();
            
            if(strtolower($demand_data->demand_status) == 'warehouse_dispatched'){
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'store_loading'));
            }
            
            return view('store/inventory_push_demand_edit',array('error_message'=>$error_message,'demand_data'=>$demand_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_push_demand_edit',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function updateInventoryPushDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name','u.name as user_name')->first();
            
            if(isset($data['action']) && $data['action']  == 'close_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('comments_close_demand'=>'required');
                $attributes = array('comments_close_demand'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                // For Tikki global demand, update inventory is received without loading
                if(!empty($demand_data->transfer_field) && !empty($demand_data->transfer_percent) && $demand_data->store_id == 50){
                    
                    $date = date('Y/m/d H:i:s');
                    
                    $updateArray = array('product_status'=>4,'store_intake_date'=>$date);
                    Pos_product_master_inventory::where('demand_id',$demand_id)->update($updateArray); 
                    
                    $updateArray = array('receive_status'=>1,'receive_date'=>$date);
                    Store_products_demand_inventory::where('demand_id',$demand_id)->update($updateArray);
                    
                    //\DB::update('UPDATE store_products_demand_detail set store_intake_qty = product_quantity where demand_id = '.$demand_id);
                }
                
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'store_loaded','comments'=>$data['comments_close_demand']));
                
                CommonHelper::createLog('Warehouse to Store Demand Closed by Store. Demand ID: '.$demand_id,'WAREHOUSE_TO_STORE_DEMAND_CLOSED','WAREHOUSE_TO_STORE_DEMAND');
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_docket_no'){
                $validateionRules = array('docket_no'=>'required','receive_date'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $data_arr = explode('-', str_replace("/", '-', $data['receive_date']));
                $receive_date = $data_arr[2].'-'.$data_arr[1].'-'.$data_arr[0];
                
                $updateArray = array('receive_docket_no'=>trim($data['docket_no']),'receive_date'=>$receive_date);
                Store_products_demand::where('id',$data['demand_id'])->update($updateArray);
                
                CommonHelper::createLog('Warehouse to Store Demand Docked Number Updated. Demand ID: '.$demand_id,'WAREHOUSE_TO_STORE_DEMAND_DOCKET_UPDATED','WAREHOUSE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'import_demand_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $inv_id_list = $barcodes_list = $barcodes_updated = array();
                $demand_id = trim($data['demand_id']);
                
                $barcode_data = CommonHelper::importInventoryBarcodes($request,$data);
                $dest_folder = $barcode_data['dest_folder'];
                $file_name =  $barcode_data['file_name'];
                $barcodes = $barcode_data['barcodes'];

                if(!empty($barcode_data['error_msg'])){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }

                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$barcode_data['error_msg'], 'errors' =>$barcode_data['error_msg']));
                }
                
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->wherein('ppmi.peice_barcode',$barcodes)        
                ->where('ppmi.demand_id',$demand_id)                
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->select('ppmi.id','ppmi.product_status','ppmi.peice_barcode','ppmi.product_master_id')
                ->get()->toArray();
                
                // Check if product status is 3
                for($i=0;$i<count($product_list);$i++){
                    if($product_list[$i]->product_status != 3){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product is not transit to store <br/>';
                    }
                    
                    $barcodes_list[] = $product_list[$i]->peice_barcode;
                    $inv_id_list[] = $product_list[$i]->id;
                }
                
                // Iterate user provided barcodes to check with database barcodes
                if(count($barcodes) != count($product_list)){
                    for($i=0;$i<count($barcodes);$i++){
                        if(!in_array($barcodes[$i],$barcodes_list)){
                            $error_msg.=$barcodes[$i].': Inventory Product does not exist <br/>';
                        }
                    }
                }
                
                if(!empty($error_msg)){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }
                    
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                \DB::beginTransaction();
                
                $updateArray = array('product_status'=>4,'store_intake_date'=>date('Y/m/d H:i:s'));
                Pos_product_master_inventory::wherein('id',$inv_id_list)->update($updateArray); 
                
                Store_products_demand_inventory::where('demand_id',$demand_id)->wherein('inventory_id',$inv_id_list)->where('is_deleted',0)->update(array('receive_status'=>1,'receive_date'=>date('Y/m/d H:i:s'))); 
                
                /*for($i=0;$i<count($product_list);$i++){
                    Store_products_demand_detail::where('demand_id',$demand_id)->where('product_id',$product_list[$i]->product_master_id)->where('is_deleted',0)->increment('store_intake_qty');
                }*/
                
                \DB::commit();
                
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully'),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function inventoryPushDemandDetail(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            $product_inventory = $products = $size_list = $products_sku = array();
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name','u.name as user_name')->first();
            
            /*$product_list = \DB::table('store_products_demand_detail as spdd')
            ->join('pos_product_master as ppm','ppm.id', '=', 'spdd.product_id')
            ->leftJoin('purchase_order_items as poi','poi.id','=','spdd.po_item_id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->where('spdd.demand_id',$demand_id)         
            ->where('spdd.is_deleted',0)
            ->where('spdd.status',1)
            ->where('ppm.is_deleted',0)
            ->select('spdd.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.id as size_id','psc.size as size_name','poi.vendor_sku','ppm.hsn_code')
            ->get()->toArray();*/        
            
            $product_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')       
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')    
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')           
            ->where('spdi.demand_id',$demand_id)        
            ->where('ppmi.is_deleted',0)
            ->where('spdi.is_deleted',0)  
            ->where('ppm.is_deleted',0)
            ->where('spdi.transfer_status',1)        
            ->select('ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku',
            'ppm.hsn_code','poi.vendor_sku','ppm.size_id','dlim_1.name as color_name','psc.size as size_name','ppm.product_barcode')        
            ->get()->toArray();
            
            for($i=0;$i<count($product_list);$i++){
                $sku = strtolower($product_list[$i]->product_sku);
                $key = $sku.'_'.$product_list[$i]->size_id;
                if(isset($products[$key])){
                    $products[$key]+=1;
                }else{
                    $products[$key] = 1;
                }
                
                $products_sku[$sku] = $product_list[$i];
                $size_list[] = $product_list[$i]->size_id;
            }
            
            $size_list = array_values(array_unique($size_list));
            $size_list = Production_size_counts::wherein('id',$size_list)->where('is_deleted',0)->get()->toArray();
            
            $product_list = json_decode(json_encode($product_list),true);
             
            // Convert multiple rows of sku sizes into single sku row with different sizes as columns
            /*for($i=0;$i<count($product_list);$i++){
                $size_id = $product_list[$i]['size_id'];
                $index = array_search($product_list[$i]['product_sku'], array_column($products, 'product_sku'));
                if($index !== false){
                    $products[$index]['size_'.$size_id] = array('rec_qty'=>$product_list[$i]['product_quantity'],'intake_qty'=>$product_list[$i]['store_intake_qty']);
                }else{
                    $product_list[$i]['size_'.$size_id] = array('rec_qty'=>$product_list[$i]['product_quantity'],'intake_qty'=>$product_list[$i]['store_intake_qty']);
                    $products[] = $product_list[$i];
                }
                
                $size_list[] = $size_id;
            }
            
            $size_list = array_unique($size_list);
            $size_list = Production_size_counts::wherein('id',$size_list)->where('is_deleted',0)->get()->toArray();*/
            
            $gate_pass_data = \DB::table('store_products_demand_courier')
            ->where('demand_id',$demand_id)->where('type','inventory_push')->where('status',1)->where('is_deleted',0)
            ->select('*')->first();
            
            $inventory_total_count = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->count();
            
            $inventory_received_count = Store_products_demand_inventory::where('demand_id',$demand_id)->where('receive_status',1)->where('is_deleted',0)->count();
            
            $debit_note = Debit_notes::where('invoice_id',$demand_id)->where('debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand')->where('debit_note_status','completed')->where('is_deleted',0)->first();
            
            return view('store/inventory_push_demand_detail',array('product_list'=>$product_list,'product_inventory'=>$product_inventory,'demand_data'=>$demand_data,'error_message'=>$error_message,'products_sku'=>$products_sku,
            'size_list'=>$size_list,'products'=>$products,'gate_pass_data'=>$gate_pass_data,'inventory_total_count'=>$inventory_total_count,'inventory_received_count'=>$inventory_received_count,'debit_note'=>$debit_note));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_push_demand_detail',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryPushDemandDebitNote(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            $product_inventory = array();
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)
            ->where('spd.status',1)
            ->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name','u.name as user_name')->first();
            
            if(isset($data['action']) && $data['action']  == 'update_debit_note'){
                $validateionRules = array('inv_ids'=>'required','demand_id'=>'required');
                $attributes = array('inv_ids'=>'Products','demand_id'=>'Demand');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $inv_ids = explode(',',trim($data['inv_ids']));
                
                $debit_note = Debit_notes::where('invoice_id',$demand_id)->where('debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand')->where('debit_note_status','completed')->where('is_deleted',0)->first();
                
                \DB::beginTransaction();
                
                // create debit note
                if(empty($debit_note)){
                    $store_user = CommonHelper::getUserStoreData($user->id); 
                    $debit_note_no = $this->getReturnDemandDebitNoteNo($store_user);
                    $credit_note_no = CommonHelper::getReturnDemandCreditNoteNo();
                    
                    $invoice_no_exists = Store_products_demand::where('debit_note_no',$debit_note_no)->where('invoice_series_type',2)->count();
                    if($invoice_no_exists > 0){
                        return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Debit Note No', 'errors' =>'Error in creating Debit Note No' ));
                    }

                    $invoice_no_exists = Store_products_demand::where('credit_note_no',$credit_note_no)->where('invoice_series_type',2)->count();
                    if($invoice_no_exists > 0){
                        return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Credit Note No', 'errors' =>'Error in creating Credit Note No' ));
                    }
                    
                    $insertArray = array('invoice_id'=>$demand_id,'debit_note_type'=>'less_inventory_from_warehouse_to_store_in_push_demand','debit_note_no'=>$debit_note_no,'credit_note_no'=>$credit_note_no,'items_count'=>count($inv_ids));
                    $debit_note = Debit_notes::create($insertArray);
                }else{
                    // update debit note
                    $updateArray = array('items_count'=>count($inv_ids));
                    Debit_notes::where('id',$debit_note->id)->update($updateArray);
                }
                
                // update debit note items
                $updateArray = array('is_deleted'=>1);
                Debit_note_items::where('debit_note_id',$debit_note->id)->update($updateArray);
                
                $inv_list = Pos_product_master_inventory::wherein('id',$inv_ids)->get()->toArray();
                for($i=0;$i<count($inv_list);$i++){
                    $insertArray = array('debit_note_id'=>$debit_note->id,'item_id'=>$inv_list[$i]['id'],'item_qty'=>1,'base_rate'=>$inv_list[$i]['store_base_rate'],
                    'gst_percent'=>$inv_list[$i]['store_gst_percent'],'gst_amount'=>$inv_list[$i]['store_gst_amount'],'base_price'=>$inv_list[$i]['store_base_price']);
                    Debit_note_items::create($insertArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Warehouse to Store Demand Debit Note Created. Debit Note ID: '.$debit_note->id,'WAREHOUSE_TO_STORE_DEMAND_DEBIT_NOTE_ADDED','WAREHOUSE_TO_STORE_DEMAND_DEBIT_NOTE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Debit Note updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'cancel_debit_note'){
                $validateionRules = array('comments'=>'required');
                $attributes = array('comments'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $debit_note_id = trim($data['debit_note_id']);
                
                \DB::beginTransaction();
                
                $updateArray = array('debit_note_status'=>'cancelled','cancel_user_id'=>$user->id,'cancel_date'=>date('Y/m/d H:i:s'),'cancel_comments'=>trim($data['comments']));
                Debit_notes::where('id',$debit_note_id)->update($updateArray);
                
                $updateArray = array('debit_note_status'=>3);
                Debit_note_items::where('debit_note_id',$debit_note_id)->update($updateArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Warehouse to Store Demand Debit Note Cancelled. ID: '.$debit_note_id,'WAREHOUSE_TO_STORE_DEMAND_DEBIT_NOTE_CANCELLED','WAREHOUSE_TO_STORE_DEMAND_DEBIT_NOTE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Debit Note cancelled successfully'),200);
            }
            
            $product_inventory = \DB::table('pos_product_master_inventory as ppmi')
            ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->where('spdi.demand_id',$demand_id)      
            ->whereRAW("(spdi.receive_status = 0 OR spdi.receive_status IS NULL)")        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('spdi.is_deleted',0)
            ->where('spdi.demand_status',1)        
            ->where('ppm.is_deleted',0)
            ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.store_base_rate')
            ->orderByRaw('ppm.product_name,ppm.product_sku,ppm.size_id')        
            ->get()->toArray();        
            
            $inventory_total_count = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->count();
            
            $inventory_received_count = Store_products_demand_inventory::where('demand_id',$demand_id)->where('receive_status',1)->where('is_deleted',0)->count();
            
            return view('store/inventory_push_demand_debit_note',array('product_inventory'=>$product_inventory,'demand_data'=>$demand_data,'error_message'=>$error_message,'inventory_total_count'=>$inventory_total_count,'inventory_received_count'=>$inventory_received_count));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_push_demand_debit_note',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryPushDemandDebitNoteInvoice(Request $request,$id,$invoice_type_id = 1){
        try{
            $data = $request->all();
            $user = Auth::user();
            $debit_note_id = $id;
            $products_list = $demand_data = $company_data = $products_sku = $demand_sku_list = array();
            
            $invoice_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            
            $debit_note_data = Debit_notes::where('id',$id)->first();
            
            $demand_id = $debit_note_data['invoice_id'];
            $demand_data = Store_products_demand::where('id',$demand_id)->first();
            
            $store_data = (!empty($demand_data->store_data))?json_decode($demand_data->store_data,false):Store::where('id',$demand_data->store_id)->first();
            $company_data = CommonHelper::getCompanyData();
            $company_data['company_name'] = $demand_data->company_gst_name;
            $company_data['company_gst_no'] = $demand_data->company_gst_no;        
            
            if($store_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($store_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            }else{
                $gst_name = '';
            }
            
            $demand_products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('debit_note_items as dni','dni.item_id', '=', 'ppmi.id')              
            ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')            
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')                
            ->where('dni.debit_note_id',$debit_note_id)                
            ->where('spdi.demand_id',$demand_id)        
            ->where('spdi.transfer_status',1)
            ->where('spdi.is_deleted',0)        
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)
            ->where('dni.is_deleted',0)        
            ->groupBy('ppm.id')        
            ->select('ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','poi.vendor_sku','ppm.hsn_code',\DB::raw('COUNT(ppmi.id) as product_quantity'))        
            ->get()->toArray();
            
            for($i=0;$i<count($demand_products_list);$i++){
                $sku = $demand_products_list[$i]->product_sku;
                if(!isset($demand_sku_list[$sku])){
                    $demand_sku_list[$sku] = array('prod'=>$demand_products_list[$i],'qty'=>$demand_products_list[$i]->product_quantity);
                }else{
                    $demand_sku_list[$sku]['qty']+= $demand_products_list[$i]->product_quantity;
                }
            }
            
            $data = array('message' => 'products list','demand_sku_list' => $demand_sku_list,'company_data'=>$company_data,'gst_name'=>$gst_name,'store_data'=>$store_data,'demand_data'=>$demand_data,'invoice_type'=>$invoice_type,'store_data'=>$store_data,'debit_note_data'=>$debit_note_data);
            
            //return view('store/inventory_push_demand_debit_note_invoice',$data);
            
            $pdf = PDF::loadView('store/inventory_push_demand_debit_note_invoice', $data);

            return $pdf->download('inventory_push_demand_debit_note_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_RETURN_DEMAND',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function inventoryReturnCompleteDemandList(Request $request){
        try{
            set_time_limit(300);
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $store_push_demands = array();
            
            if($user->user_type == 9){
                $store_user = CommonHelper::getUserStoreData($user->id); 
            }
            
            if(isset($data['action']) && $data['action'] == 'create_demand'){
                
                //valid demand status: warehouse_dispatched, store_loaded
                
                \DB::beginTransaction();
                
                $store_inventory = \DB::table('pos_product_master_inventory as ppmi')
                ->where('ppmi.store_id',$store_user->id)        
                ->where('ppmi.product_status',4)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->select('ppmi.*')
                ->get()->toArray();
                
                if(empty($store_inventory)){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Store Inventory is empty', 'errors' => 'Store Inventory is empty' ));
                }	
                
                $validateionRules = array('comments_return_inv'=>'required|max:200');
                $attributes = array('comments_return_inv'=>'Comments');
                
                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $invoice_no = $this->getReturnDemandDebitNoteNo($store_user);
                $credit_note_no =  CommonHelper::getReturnDemandCreditNoteNo(); 
                
                $invoice_no_exists = Store_products_demand::where('invoice_no',$invoice_no)->where('invoice_series_type',2)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                }
                
                $invoice_no_exists = Store_products_demand::where('invoice_no',$credit_note_no)->where('invoice_series_type',2)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                }
                
                $company_data = CommonHelper::getCompanyData();
                
                $insertArray = array('invoice_no'=>$invoice_no,'credit_invoice_no'=>$credit_note_no,'user_id'=>$user->id,'store_id'=>$store_user->id,'demand_type'=>'inventory_return_complete','demand_status'=>'warehouse_dispatched','comments'=>$data['comments_return_inv']);
                
                $insertArray['store_data'] = json_encode($store_user);
                $insertArray['store_state_id'] = $store_user->state_id;
                
                $insertArray['company_gst_no'] = $company_data['company_gst_no'];
                $insertArray['company_gst_name'] = $company_data['company_name'];
                
                $demand = Store_products_demand::create($insertArray);
                
                
                for($i=0;$i<count($store_inventory);$i++){
                    $insertArray = array('demand_id'=>$demand->id,'inventory_id'=>$store_inventory[$i]->id,'transfer_status'=>1,'receive_status'=>1,
                    'store_base_rate'=>$store_inventory[$i]->store_base_rate,'store_gst_percent'=>$store_inventory[$i]->store_gst_percent,
                    'store_gst_amount'=>$store_inventory[$i]->store_gst_amount,'store_base_price'=>$store_inventory[$i]->store_base_price,
                    'transfer_date'=>date('Y/m/d H:i:s'),'receive_date'=>date('Y/m/d H:i:s'),'store_id'=>$demand->store_id,
                    'product_id'=>$store_inventory[$i]->product_master_id,'product_sku_id'=>$store_inventory[$i]->product_sku_id,'po_item_id'=>$store_inventory[$i]->po_item_id,'vendor_id'=>$store_inventory[$i]->vendor_id);
                    
                    Store_products_demand_inventory::create($insertArray); 
                    
                    /*$demand_product = Store_products_demand_detail::where('demand_id',$demand->id)->where('product_id',$store_inventory[$i]->product_master_id)->where('is_deleted',0)->first();
                    if(empty($demand_product)){
                        $insertArray = array('demand_id'=>$demand->id,'product_id'=>$store_inventory[$i]->product_master_id,'product_quantity'=>1,'store_intake_qty'=>1,'po_item_id'=>$store_inventory[$i]->po_item_id);
                        Store_products_demand_detail::create($insertArray);
                    }else{
                        $demand_product->increment('product_quantity');
                    }*/
                    
                    /*$updateArray = array('product_status'=>1,'store_id'=>null,'demand_id'=>null,'store_base_rate'=>null,'store_gst_percent'=>null,'store_gst_amount'=>null,'store_base_price'=>null,
                    'store_assign_date'=>null,'store_intake_date'=>null);*/
                    
                    $updateArray = array('product_status'=>1,'store_id'=>null);

                    Pos_product_master_inventory::where('id',$store_inventory[$i]->id)->update($updateArray);
                }
                
                CommonHelper::updateDemandTotalData($demand->id);
                
                \DB::commit();
                
                CommonHelper::createLog('Inventory Return Complete Demand Created. Demand ID: '.$demand->id,'INVENTORY_RETURN_COMPLETE_DEMAND_CREATED','INVENTORY_RETURN_COMPLETE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Complete Return demand created successfully','demand_detail'=>$demand,'status' => 'success'),200);
            }
            
            $demands_list = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->leftJoin('store_products_demand as spd_1','spd_1.push_demand_id', '=', 'spd.id');
            
            if($user->user_type == 9){
                $demands_list = $demands_list->where('spd.store_id',$store_user->id);        
            }
            
            if($user->user_type == 6){
                //$demands_list = $demands_list->wherein('spd.demand_status',array('warehouse_loading','warehouse_dispatched','warehouse_loaded'));        
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $invoice_no = trim($data['invoice_no']);
                $demands_list = $demands_list->where('spd.invoice_no','LIKE','%'.$invoice_no.'%');
            }
            
            if(isset($data['invoice_id']) && !empty($data['invoice_id'])){
                $demands_list = $demands_list->where('spd.id',trim($data['invoice_id']));
            }
                    
            $demands_list = $demands_list->where('spd.demand_type','inventory_return_complete')
            ->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name','s.store_id_code','spd_1.invoice_no as tax_invoice_no');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $demands_list = $demands_list->offset($start)->limit($limit)->orderBy('spd.id','ASC')->get()->toArray();
            }else{
                $demands_list = $demands_list->orderBy('spd.id','DESC')->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=inventory_return_complete_demand_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Demand ID','Debit Note No','Credit Note No','Store Name','Code ','Demand Status','Tax Invoice','Created On');

                $callback = function() use ($demands_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($demands_list);$i++){
                        $array = array($demands_list[$i]->id,$demands_list[$i]->invoice_no,$demands_list[$i]->credit_invoice_no,$demands_list[$i]->store_name,$demands_list[$i]->store_id_code,str_replace('_',' ',$demands_list[$i]->demand_status),$demands_list[$i]->tax_invoice_no,date('d-m-Y',strtotime($demands_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('store/inventory_return_complete_demand_list',array('demands_list'=>$demands_list,'error_message'=>$error_message,'user'=>$user));
 
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_RETURN_COMPLETE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_return_complete_demand_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryReturnCompleteDemandDetail(Request $request,$id){
        try{
            set_time_limit(300);
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            $push_demand =  $size_list = $products = $products_sku = array();
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name','s.store_id_code','u.name as user_name')->first();
            
            if(isset($data['action']) && $data['action']  == 'create_tax_invoice'){
                
                $validateionRules = array('comments_tax_invoice'=>'required|max:200');
                $attributes = array('comments_tax_invoice'=>'Comments');
                
                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                \DB::beginTransaction();
                
                /* Create push inventory demand start */
                $store_data = Store::where('id',$demand_data->store_id)->first();
                $invoice_no = CommonHelper::inventoryPushDemandInvoiceNo($store_data);
                
                $invoice_no_exists = Store_products_demand::where('invoice_no',$invoice_no)->count();
                
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                }
                
                $company_data = CommonHelper::getCompanyData();
                
                $insertArray = array('invoice_no'=>$invoice_no,'user_id'=>$user->id,'store_id'=>$demand_data->store_id,'demand_type'=>'inventory_push','demand_status'=>'store_loaded','push_demand_id'=>$demand_data->id,'comments'=>$data['comments_tax_invoice'],'store_data'=>json_encode($store_data));
                $insertArray['store_state_id'] = $store_data->state_id;
                
                $insertArray['company_gst_no'] = $company_data['company_gst_no'];
                $insertArray['company_gst_name'] = $company_data['company_name'];
                
                $inv_push_demand = Store_products_demand::create($insertArray);
                /* Create push inventory demand end */
                
                $company_data = CommonHelper::getCompanyData();
                //$store_data = Store::where('id',$demand_data->store_id)->first();
                
                $demand_inventory = Store_products_demand_inventory::where('demand_id',$demand_data->id)->where('is_deleted',0)->get();
                
                for($i=0;$i<count($demand_inventory);$i++){
                    $inventory_product = Pos_product_master_inventory::where('id',$demand_inventory[$i]->inventory_id)->first();
                    $product = Pos_product_master::where('id',$inventory_product->product_master_id)->first();

                    if($inventory_product->arnon_inventory == 0){
                        // Add 10% to vendor base price if store is franchise
                        $store_base_rate = ($store_data->store_type == 1)?$inventory_product->vendor_base_price:round($inventory_product->vendor_base_price+($inventory_product->vendor_base_price*.10),2);
                    }else{
                        $store_base_rate = $inventory_product->vendor_base_price;
                    }
                    
                    if($store_data->gst_no != $company_data['company_gst_no']){
                        if(empty($store_base_rate)){
                            $store_base_rate = $inventory_product->base_price;
                        }
                        
                        $gst_data = CommonHelper::getGSTData($product->hsn_code,$store_base_rate);
                        if(!empty($gst_data)){
                            $gst_percent = $gst_data->rate_percent;
                        }else{
                            $gst_percent = ($store_base_rate >= 1000)?12:5;
                        }
                        
                        $gst_amount = round($store_base_rate*($gst_percent/100),2);
                    }else{
                        $gst_percent = $gst_amount = 0;
                    }

                    $store_base_price = $store_base_rate+$gst_amount;

                    $updateArray = array('product_status'=>4,'store_id'=>$demand_data->store_id,'demand_id'=>$inv_push_demand->id,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,
                    'store_base_price'=>$store_base_price,'store_assign_date'=>date('Y/m/d H:i:s'));
                    Pos_product_master_inventory::where('id',$demand_inventory[$i]->inventory_id)->update($updateArray); 

                    /*$demand_product = Store_products_demand_detail::where('demand_id',$inv_push_demand->id)->where('product_id',$product->id)->where('is_deleted',0)->first();
                    if(empty($demand_product)){
                        $insertArray = array('demand_id'=>$inv_push_demand->id,'product_id'=>$product->id,'product_quantity'=>1,'po_item_id'=>$inventory_product->po_item_id);
                        Store_products_demand_detail::create($insertArray);
                    }else{
                        $demand_product->increment('product_quantity');
                    }*/

                    $insertArray = array('demand_id'=>$inv_push_demand->id,'inventory_id'=>$demand_inventory[$i]->inventory_id,'transfer_status'=>1,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'transfer_date'=>date('Y/m/d H:i:s'),
                    'store_id'=>$demand_data->store_id,'product_id'=>$inventory_product->product_master_id,'product_sku_id'=>$inventory_product->product_sku_id,'po_item_id'=>$inventory_product->po_item_id,'vendor_id'=>$inventory_product->vendor_id);
                    Store_products_demand_inventory::create($insertArray); 
                }
                
                $updateArray = array('demand_status'=>'store_loaded');
                Store_products_demand::where('id',$demand_data->id)->update($updateArray);
                
                CommonHelper::updateDemandTotalData($inv_push_demand->id);
                
                \DB::commit();
                
                CommonHelper::createLog('Inventory Return Complete Demand Tax Invoice Created. Demand ID: '.$demand_id,'INVENTORY_RETURN_COMPLETE_DEMAND_TAX_INVOICE_CREATED','INVENTORY_RETURN_COMPLETE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Tax Invoice created created successfully','demand_detail'=>$inv_push_demand,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'cancel_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('comments'=>'required');
                $attributes = array('comments'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $push_demand_id = $demand_data->push_demand_id;
                $store_id = $demand_data->store_id;
                Store_products_demand::where('id',$data['demand_id'])->update(array('demand_status'=>'cancelled','cancel_comments'=>$data['comments'],'cancel_user_id'=>$user->id,'cancel_date'=>date('Y/m/d H:i:s')));
                
                $inv_returned = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')
                ->where('spdi.demand_id',$demand_data->id)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)        
                ->select('spdi.*')
                ->get()->toArray();
                
                for($i=0;$i<count($inv_returned);$i++){
                    $updateArray = array('product_status'=>4,'store_id'=>$demand_data->store_id);
                    Pos_product_master_inventory::where('id',$inv_returned[$i]->inventory_id)->update($updateArray);
                }
                
                $updateArray = array('demand_status'=>3);
                Store_products_demand_inventory::where('demand_id',$demand_data->id)->update($updateArray);
                //Store_products_demand_detail::where('demand_id',$demand_data->id)->update($updateArray);
                Store_products_demand_courier::where('demand_id',$demand_data->id)->update($updateArray);
                Store_products_demand_sku::where('demand_id',$demand_data->id)->update($updateArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand Cancelled successfully','status' => 'success'),200);
            }
            
            /*
            $product_list = \DB::table('store_products_demand_detail as spdd')
            ->join('pos_product_master as ppm','ppm.id', '=', 'spdd.product_id')
            ->leftJoin('purchase_order_items as poi','poi.id','=','spdd.po_item_id')                 
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->where('spdd.demand_id',$demand_id)         
            ->where('spdd.is_deleted',0)
            ->where('spdd.status',1)
            ->where('ppm.is_deleted',0)
            ->select('spdd.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')->paginate(100, ['*'], 'prod_page');
            */
            
            $product_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')       
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')           
            ->where('spdi.demand_id',$demand_id)        
            ->where('ppmi.is_deleted',0)
            ->where('spdi.is_deleted',0)  
            ->where('ppm.is_deleted',0)
            ->where('spdi.transfer_status',1)        
            ->select('ppmi.base_price','ppm.product_barcode','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','ppm.size_id','dlim_1.name as color_name')        
            ->get()->toArray();
            
            for($i=0;$i<count($product_list);$i++){
                $sku = strtolower($product_list[$i]->product_sku);
                $key = $sku.'_'.$product_list[$i]->size_id;
                if(isset($products[$key])){
                    $products[$key]+=1;
                }else{
                    $products[$key] = 1;
                }
                
                $products_sku[$sku] = $product_list[$i];
                $size_list[] = $product_list[$i]->size_id;
            }
            
            $size_list = array_values(array_unique($size_list));
            $size_list = Production_size_counts::wherein('id',$size_list)->where('is_deleted',0)->get()->toArray();
            
            $product_inventory = \DB::table('pos_product_master_inventory as ppmi')
            ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')            
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->where('spdi.demand_id',$demand_id)         
            ->where('spdi.is_deleted',0)   
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('ppm.is_deleted',0)
            ->where('spdi.transfer_status',1)        
            ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount')
            ->orderBy('spdi.created_at','ASC')->paginate(100, ['*'], 'inv_page');
            
            if($user->user_type == 6){
                $push_demand = Store_products_demand::where('demand_type','inventory_push')->where('push_demand_id',$demand_id)->where('is_deleted',0)->first();
            }
                
            return view('store/inventory_return_complete_demand_detail',array('product_list'=>$product_list,'product_inventory'=>$product_inventory,'demand_data'=>$demand_data,'error_message'=>$error_message,'user'=>$user,'push_demand'=>$push_demand,'products'=>$products,'products_sku'=>$products_sku,'size_list'=>$size_list));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_RETURN_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine().', '.$e->getFile()),500);
            }else{
                return view('store/inventory_return_complete_demand_detail',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryReturnCompleteDemandInvoice(Request $request,$id,$invoice_type_id = 1){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(180);
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $invoice_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            $products_list = $demand_data = $company_data = $products_sku = $demand_sku_list = $demand_sku_list_arnon = array();
            
            $demand_data = Store_products_demand::where('id',$demand_id)->first();
            
            $store_data = json_decode($demand_data->store_data,false); //Store::where('id',$demand_data->store_id)->first();//print_r($store_data);exit;
            
            $company_data = CommonHelper::getCompanyData();
            
            //if($store_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($store_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            /*}else{
                $gst_name = '';
            }*/
            
            $demand_products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')            
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')                
            ->where('spdi.demand_id',$demand_id)        
            ->where('spdi.transfer_status',1)
            ->where('spdi.is_deleted',0)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.arnon_inventory',0)
            ->where('ppm.is_deleted',0)
            ->where('spdi.transfer_status',1)
            //->where('spdi.receive_status',1)        
            ->select('ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','poi.vendor_sku','ppm.hsn_code','ppm.id as product_id','ppm.size_id','ppm.color_id')        
            ->get()->toArray();
            
            for($i=0;$i<count($demand_products_list);$i++){
                if($demand_data->invoice_type  == 'product_id'){
                    $key = $demand_products_list[$i]->product_id;
                }else{
                    $key = $demand_products_list[$i]->product_sku;
                }
                
                if(!isset($demand_sku_list[$key])){
                    $demand_sku_list[$key] = array('prod'=>$demand_products_list[$i],'qty'=>1);
                }else{
                    $demand_sku_list[$key]['qty']+=1;
                }
            }
            
            $demand_products_list_arnon = \DB::table('pos_product_master_inventory as ppmi')
            ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')            
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            //->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')                
            ->where('spdi.demand_id',$demand_id)        
            ->where('spdi.transfer_status',1)
            ->where('spdi.is_deleted',0)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.arnon_inventory',1)
            ->where('ppm.is_deleted',0)
            ->where('spdi.transfer_status',1)
            //->where('spdi.receive_status',1)              
            ->select('ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','ppm.hsn_code','ppm.id as product_id','ppm.size_id','ppm.color_id')        
            ->get()->toArray();
            
            for($i=0;$i<count($demand_products_list_arnon);$i++){
                if($demand_data->invoice_type  == 'product_id'){
                    $key = $demand_products_list_arnon[$i]->product_id;
                }else{
                    $key = $demand_products_list_arnon[$i]->product_sku;
                }
                
                if(!isset($demand_sku_list_arnon[$key])){
                    $demand_sku_list_arnon[$key] = array('prod'=>$demand_products_list_arnon[$i],'qty'=>1);
                }else{
                    $demand_sku_list_arnon[$key]['qty']+=1;
                }
            }
            
            $size_list = Production_size_counts::where('is_deleted',0)->orderBy('id')->get()->toArray();
            $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->orderBy('name')->get()->toArray();
            
            for($i=0;$i<count($size_list);$i++){
                $sizes[$size_list[$i]['id']] = $size_list[$i]['size'];
            }
            
            for($i=0;$i<count($color_list);$i++){
                $colors[$color_list[$i]['id']] = $color_list[$i]['name'];
            }
            
            $data = array('message' => 'products list','demand_sku_list' => $demand_sku_list,'company_data'=>$company_data,'gst_name'=>$gst_name,'store_data'=>$store_data,'invoice_type'=>$invoice_type,'demand_data'=>$demand_data,'demand_sku_list_arnon'=>$demand_sku_list_arnon,'sizes'=>$sizes,'colors'=>$colors);
            
            //return view('store/inventory_return_complete_invoice',$data);
            
            $pdf = PDF::loadView('store/inventory_return_complete_invoice', $data);

            return $pdf->download('inventory_return_complete_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function inventoryReturnDemandList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $store_push_demands = array();
            
            if($user->user_type == 9){
                $store_user = CommonHelper::getUserStoreData($user->id); 
            }
            
            if(isset($data['action']) && $data['action'] == 'create_demand'){
                
                // valid demand status: store_loading, warehouse_dispatched, warehouse_loading, warehouse_loaded
                
                $validateionRules = array('inv_type_add'=>'required');
                $attributes = array('push_demand_add'=>'Push Demand','inv_type_add'=>'Inventory Type');
                
                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                $invoice_no = $credit_note_no = null;
                $company_data = CommonHelper::getCompanyData();
                
                $insertArray = array('invoice_no'=>$invoice_no,'credit_invoice_no'=>$credit_note_no,'user_id'=>$user->id,'store_id'=>$store_user->id,'push_demand_id'=>$data['push_demand_add'],'demand_type'=>'inventory_return_to_warehouse','demand_status'=>'store_loading','inv_type'=>$data['inv_type_add']);
                $insertArray['store_data'] = json_encode($store_user);
                $insertArray['store_state_id'] = $store_user->state_id;
                $insertArray['company_gst_no'] = $company_data['company_gst_no'];
                $insertArray['company_gst_name'] = $company_data['company_name'];
                
                $demand = Store_products_demand::create($insertArray);
                
                CommonHelper::createLog('Store to Warehouse Demand Created. Demand ID: '.$demand->id,'STORE_TO_WAREHOUSE_DEMAND_CREATED','STORE_TO_WAREHOUSE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Return demand created successfully','demand_detail'=>$demand,'status' => 'success'),200);
            }
            
            $demands_list = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->leftJoin('store_products_demand_inventory as spdi',function($join){$join->on('spdi.demand_id','=','spd.id')->where('spdi.is_deleted','=','0');})                        
            ->leftJoin('store_products_demand as spd_1','spd_1.id', '=', 'spd.push_demand_id')
            ->leftJoin('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id');
            
            if($user->user_type == 9){
                $demands_list = $demands_list->where('spd.store_id',$store_user->id);        
            }
            
            if($user->user_type != 9){
                $demands_list = $demands_list->wherein('spd.demand_status',array('warehouse_loading','warehouse_dispatched','warehouse_loaded','cancelled'));        
            }
            
            if(isset($data['store_id']) && !empty($data['store_id'])){
                $demands_list = $demands_list->where('spd.store_id',trim($data['store_id']));
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $invoice_no = trim($data['invoice_no']);
                $demands_list = $demands_list->whereRaw("(spd.invoice_no LIKE '%$invoice_no%' OR spd.credit_invoice_no LIKE '%$invoice_no%')");
            }
            
            if(isset($data['invoice_id']) && !empty($data['invoice_id'])){
                $demands_list = $demands_list->where('spd.id',trim($data['invoice_id']));
            }
                    
            $demands_list = $demands_list->where('spd.demand_type','inventory_return_to_warehouse')
            ->where('spd.status',1)
            ->where('spd.is_deleted',0)
            ->groupBy('spd.id')        
            ->selectRaw('spd.*,s.store_name,s.store_id_code,spd_1.invoice_no as push_demand_invoice_no,COUNT(spdi.id) as inv_count,SUM(spdi.store_base_price) as store_base_price_sum,SUM(ppmi.sale_price) as sale_price_sum');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $demands_list = $demands_list->offset($start)->limit($limit)->orderBy('spd.id','ASC')->get()->toArray();
            }else{
                $demands_list = $demands_list->orderBy('spd.id','DESC')->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_to_warehouse_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Demand ID','Credit Note No','Debit Note No','Store Name','Code','Demand Status','Inventory Type','Inventory Count','Created On');

                $callback = function() use ($demands_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($demands_list);$i++){
                        $status = CommonHelper::getDemandStatusText('inventory_return_to_warehouse',$demands_list[$i]->demand_status);
                        $type = CommonHelper::getInventoryType($demands_list[$i]->inv_type);
                        $array = array($demands_list[$i]->id,$demands_list[$i]->credit_invoice_no,$demands_list[$i]->invoice_no,$demands_list[$i]->store_name,$demands_list[$i]->store_id_code,$status,$type,$demands_list[$i]->inv_count,date('d-m-Y',strtotime($demands_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            if($user->user_type == 9){
                $store_push_demands = Store_products_demand::where('store_id',$store_user->id)->where('demand_type','inventory_push')->get()->toArray();
            }
            
            $store_list = CommonHelper::getStoresList();
            
            return view('store/inventory_return_demand_list',array('demands_list'=>$demands_list,'store_push_demands'=>$store_push_demands,'error_message'=>$error_message,'user'=>$user,'store_list'=>$store_list));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_RETURN_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_return_demand_list',array('error_message'=>$e->getMessage().', Line'.$e->getLine()));
            }
        }
    }
    
    function editInventoryReturnDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')            
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->leftJoin('store_products_demand as spd','spd.id','=','ppmi.demand_id')        
                ->where('ppmi.peice_barcode',trim($data['barcode']))        
                //->where('ppmi.product_status',4)                
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0)        
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spd.invoice_no as push_demand_no')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product does not exists', 'errors' =>'Inventory Product does not exists' ));
                }
                
                if($product_data->product_status != 4){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product Status is not Ready for sale in store', 'errors' =>'Inventory Product Status is not Ready for sale in store' ));
                }
                
                $demand_data = Store_products_demand::where('id',$data['demand_id'])->first();
                
                if($demand_data->store_id != $product_data->store_id){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product does not exists in this store', 'errors' =>'Inventory Product does not exists in this store' ));
                }
                
                // validation only for northcorp inventory
                if($demand_data->inv_type == 1 && !empty($demand_data->push_demand_id) && $demand_data->push_demand_id != $product_data->demand_id){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product does not exists in this push demand', 'errors' =>'Inventory Product does not exists in this push demand' ));
                }
                
                // validation only for northcorp inventory
                if($demand_data->inv_type == 1 && $product_data->arnon_inventory == 1){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product Type is not '.CommonHelper::getInventoryType(1), 'errors' =>'Inventory Product Type is not '.CommonHelper::getInventoryType(1) ));
                }
                
                // validation only for arnon inventory
                if($demand_data->inv_type == 2 && $product_data->arnon_inventory == 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product Type is not '.CommonHelper::getInventoryType(2), 'errors' =>'Inventory Product Type is not '.CommonHelper::getInventoryType(2) ));
                }
                
                $demand_inventory_prod = Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->first();
                
                if(!empty($demand_inventory_prod)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product already added in this demand', 'errors' =>'Inventory Product already added in this demand' ));
                }
                
                \DB::beginTransaction();
                
                $insertArray = array('demand_id'=>$data['demand_id'],'inventory_id'=>$product_data->id,'transfer_status'=>0,'store_base_rate'=>$product_data->store_base_rate,'store_gst_percent'=>$product_data->store_gst_percent,
                'store_gst_amount'=>$product_data->store_gst_amount,'store_base_price'=>$product_data->store_base_price,'transfer_date'=>date('Y/m/d H:i:s'),'push_demand_id'=>$product_data->demand_id,
                'store_id'=>$demand_data->store_id,'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                Store_products_demand_inventory::create($insertArray); 
                
                /*$demand_product = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->first();
                if(empty($demand_product)){
                    $insertArray = array('demand_id'=>$data['demand_id'],'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                    Store_products_demand_detail::create($insertArray);
                }else{
                    $demand_product->increment('product_quantity');
                }*/
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Product with QR Code: <b>'.$product_data->peice_barcode.'</b> added successfully','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_inv_return_items'){
                $inv_items = Pos_product_master_inventory::wherein('id',$data['deleteChk'])->where('is_deleted',0)->get()->toArray();
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($inv_items);$i++){
                    $updateArray = array('is_deleted'=>1);
                    Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$inv_items[$i]['id'])->where('is_deleted',0)->update($updateArray);
                    
                    //Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$inv_items[$i]['product_master_id'])->where('is_deleted',0)->decrement('product_quantity',1);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Warehouse Demand Inventory Deleted. Demand ID: '.$demand_id,'STORE_TO_WAREHOUSE_DEMAND_INVENTORY_DELETED','STORE_TO_WAREHOUSE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Return Inventory items deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_demand_inventory'){//print_r($data);exit;
                $rec_per_page = 100;
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')            
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->leftJoin('store_products_demand as spd','spd.id','=','spdi.push_demand_id')                    
                ->where('spdi.demand_id',$data['demand_id'])         
                ->where('spdi.is_deleted',0)   
                ->where('spdi.demand_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0);
                
                if(isset($data['barcode']) && !empty($data['barcode'])){
                    $product_list = $product_list->where('ppmi.peice_barcode',trim($data['barcode']));
                }  
                
                if(isset($data['product_id']) && !empty($data['product_id'])){
                    $product_list = $product_list->where('ppmi.product_master_id',trim($data['product_id']));
                }  
                
                $product_list = $product_list->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','ppmi.arnon_inventory','spd.invoice_no as push_demand_no')
                ->orderBy('ppmi.store_intake_date','ASC')->paginate($rec_per_page);
                    
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('ppmi.is_deleted',0)
                ->where('spdi.is_deleted',0)   
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('spdi.is_deleted',0)         
                ->where('spdi.demand_status',1)        
                ->groupByRaw('ppmi.product_master_id')
                ->selectRaw('ppm.id,ppm.product_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                ->orderByRaw('ppm.product_sku,psc.id')        
                ->get()
                ->toArray();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'sku_list' => $sku_list),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_count_data'){
                $inv_data = array('rec_count'=>0,'rec_base_price_sum'=>0,'rec_sale_price_sum'=>0);
                
                $demand_total_data = CommonHelper::updateDemandTotalData($data['demand_id'],1,1);
                $total_data = $demand_total_data['total_data'];
                
                $demand_data = Store_products_demand::where('id',$data['demand_id'])->first();
                $store_data = json_decode($demand_data->store_data,true);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Return Inventory data','total_data'=>$total_data,'demand_data' => $demand_data,'store_data'=>$store_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'close_demand'){
                \DB::beginTransaction();
                $demand_id = trim($data['demand_id']);
                
                $validateionRules = array('comments_close_demand'=>'required');
                $attributes = array('comments_close_demand'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                // Check if Demand have Duplicate Inventory
                $duplicate_inv_qrcodes = CommonHelper::getDemandDuplicateInventory($demand_id);
                if(!empty($duplicate_inv_qrcodes)){
                    $duplicate_inv_qrcodes_str = implode(', ',$duplicate_inv_qrcodes);
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Demand have Duplicate Inventory with QR Codes: '.$duplicate_inv_qrcodes_str, 'errors' =>'Demand have Duplicate Inventory with QR Codes: '.$duplicate_inv_qrcodes_str));
                }
                
                $demand_data = Store_products_demand::where('id',$demand_id)->first();
                
                // Demands created before new rule already have invoice no
                if(empty($demand_data->invoice_no)){
                    $store_user = CommonHelper::getUserStoreData($user->id); 
                    $invoice_no = $this->getReturnDemandDebitNoteNo($store_user);
                    
                    $invoice_no_exists = Store_products_demand::where('invoice_no',$invoice_no)->where('invoice_series_type',2)->count();
                    if($invoice_no_exists > 0){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                    }

                    $updateArray = array('invoice_no'=>$invoice_no,'credit_invoice_no'=>null,'demand_status'=>'warehouse_dispatched','comments'=>trim($data['comments_close_demand']),'debit_note_date'=>date('Y/m/d H:i:s'));
                    Store_products_demand::where('id',$data['demand_id'])->update($updateArray);
                }else{
                    $updateArray = array('demand_status'=>'warehouse_dispatched','comments'=>trim($data['comments_close_demand']));
                    Store_products_demand::where('id',$data['demand_id'])->update($updateArray);
                }
                
                $inv_loaded = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')
                ->where('spdi.demand_id',$demand_id)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)        
                ->where('spdi.demand_status',1)        
                ->select('spdi.*','ppmi.product_status')
                ->get()->toArray();
                
                for($i=0;$i<count($inv_loaded);$i++){
                    if($inv_loaded[$i]->product_status == 4){
                        $updateArray = array('product_status'=>6,'store_id'=>null,'demand_id'=>$demand_id);
                        
                        Pos_product_master_inventory::where('id',$inv_loaded[$i]->inventory_id)->update($updateArray);
                        
                        Store_products_demand_inventory::where('id',$inv_loaded[$i]->id)->update(array('transfer_status'=>1)); 
                    }
                }
                
                CommonHelper::updateDemandTotalData($demand_id);
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Warehouse Demand closed. Demand ID: '.$demand_id,'STORE_TO_WAREHOUSE_DEMAND_CLOSED','STORE_TO_WAREHOUSE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand Return Inventory updated successfully','status' => 'success'),200);
            }
            
            
            if(isset($data['action']) && $data['action']  == 'import_demand_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $inv_id_array = $demand_detail_listing = $barcodes_list = $barcodes_updated = array();
                $demand_id = trim($data['demand_id']);
                $store_id = trim($data['store_id']);
                
                $barcode_data = CommonHelper::importInventoryBarcodes($request,$data);
                $dest_folder = $barcode_data['dest_folder'];
                $file_name =  $barcode_data['file_name'];
                $barcodes = $barcode_data['barcodes'];

                if(!empty($barcode_data['error_msg'])){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }

                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$barcode_data['error_msg'], 'errors' =>$barcode_data['error_msg']));
                }
                
                // Existing demand ids
                $demand_data = Store_products_demand::where('id',$data['demand_id'])->first();
                $demand_inventory_ids = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->select('inventory_id')->get()->toArray();
                $demand_inventory_ids = array_column($demand_inventory_ids, 'inventory_id');
                
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->wherein('ppmi.peice_barcode',$barcodes)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppmi.fake_inventory',0)                
                ->select('ppmi.*')
                ->get()->toArray();
                
                // Check if product status is 4
                for($i=0;$i<count($product_list);$i++){
                    if($product_list[$i]->product_status != 4){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product not ready for sale <br/>';
                    }elseif($product_list[$i]->store_id != $store_id){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product does not exists in this store <br/>';
                    }elseif($demand_data->inv_type == 1 && $product_list[$i]->arnon_inventory == 1){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product Type is not '.CommonHelper::getInventoryType(1).' <br/>';
                    }elseif($demand_data->inv_type == 2 && $product_list[$i]->arnon_inventory == 0){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product Type is not '.CommonHelper::getInventoryType(2).' <br/>';
                    }elseif($demand_data->inv_type == 1 && !empty($demand_data->push_demand_id) && $demand_data->push_demand_id != $product_list[$i]->demand_id){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product does not exists in this push demand <br/>';
                    }elseif(in_array($product_list[$i]->id,$demand_inventory_ids)){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product already added in this demand <br/>';
                    }
                    
                    $barcodes_list[] = $product_list[$i]->peice_barcode;
                    $inv_id_array[] =  $product_list[$i]->id;
                }
                
                // Iterate user provided barcodes to check with database barcodes
                if(count($barcodes) != count($product_list)){
                    for($i=0;$i<count($barcodes);$i++){
                        if(!in_array($barcodes[$i],$barcodes_list)){
                            $error_msg.=$barcodes[$i].': Inventory Product does not exist <br/>';
                        }
                    }
                }
                
                if(!empty($error_msg)){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }
                    
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                /*$demand_detail_list = Store_products_demand_detail::where('demand_id',$demand_id)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($demand_detail_list);$i++){
                    $demand_detail_listing[$demand_detail_list[$i]['product_id']] = $demand_detail_list[$i];
                }*/
                
                $date = date('Y/m/d H:i:s');
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($product_list);$i++){
                    $product_data = $product_list[$i];
                    
                    $insertArray = array('demand_id'=>$demand_id,'inventory_id'=>$product_data->id,'transfer_status'=>0,'store_base_rate'=>$product_data->store_base_rate,'store_gst_percent'=>$product_data->store_gst_percent,
                    'store_gst_amount'=>$product_data->store_gst_amount,'store_base_price'=>$product_data->store_base_price,'transfer_date'=>$date,'push_demand_id'=>$product_data->demand_id,
                    'store_id'=>$demand_data->store_id,'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                    Store_products_demand_inventory::create($insertArray); 
                    
                    // Add/update store demand detail row
                    /*if(!isset($demand_detail_listing[$product_data->product_master_id])){
                       $insertArray = array('demand_id'=>$demand_id,'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                       Store_products_demand_detail::create($insertArray);
                       $demand_detail_listing[$product_data->product_master_id] = 1;
                    }else{
                        Store_products_demand_detail::where('demand_id',$demand_id)->where('product_id',$product_data->product_master_id)->increment('product_quantity');
                    }*/
                }
                
                \DB::commit();
                
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully'),200);
            }
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name')->first();
            
            /*if(strtolower($demand_data->demand_status) == 'warehouse_dispatched'){
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'store_loading'));
            }*/
            
            return view('store/inventory_return_demand_edit',array('error_message'=>$error_message,'demand_data'=>$demand_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_RETURN_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('store/inventory_return_demand_edit',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryReturnDemandDetail(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            $size_list = $products = $products_sku = array();
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)
            ->where('spd.status',1)
            ->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name','u.name as user_name','s.store_id_code')
            ->first();
            
            if(isset($data['action']) && $data['action']  == 'cancel_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('comments_cancel_demand'=>'required');
                $attributes = array('comments_cancel_demand'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $push_demand_id = $demand_data->push_demand_id;
                $store_id = $demand_data->store_id;
                Store_products_demand::where('id',$data['demand_id'])->update(array('demand_status'=>'cancelled','cancel_comments'=>$data['comments_cancel_demand'],'cancel_user_id'=>$user->id,'cancel_date'=>date('Y/m/d H:i:s')));
                
                $inv_returned = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')
                ->where('spdi.demand_id',$demand_data->id)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)        
                ->select('spdi.*')
                ->get()->toArray();
                
                for($i=0;$i<count($inv_returned);$i++){
                    $updateArray = array('product_status'=>4,'store_id'=>$store_id,'demand_id'=>$inv_returned[$i]->push_demand_id);
                    Pos_product_master_inventory::where('id',$inv_returned[$i]->inventory_id)->update($updateArray);
                }
                
                $updateArray = array('demand_status'=>3);
                Store_products_demand_inventory::where('demand_id',$demand_data->id)->update($updateArray);
                //Store_products_demand_detail::where('demand_id',$demand_data->id)->update($updateArray);
                Store_products_demand_courier::where('demand_id',$demand_data->id)->update($updateArray);
                Store_products_demand_sku::where('demand_id',$demand_data->id)->update($updateArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand Cancelled successfully','status' => 'success'),200);
            }
            
            
            $product_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')       
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')           
            ->where('spdi.demand_id',$demand_id)        
            ->where('ppmi.is_deleted',0)
            ->where('spdi.is_deleted',0)  
            ->where('ppm.is_deleted',0)
            ->where('spdi.transfer_status',1)        
            ->select('ppmi.base_price','ppm.product_barcode','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','ppm.size_id','dlim_1.name as color_name')        
            ->get()->toArray();
            
            for($i=0;$i<count($product_list);$i++){
                $sku = strtolower($product_list[$i]->product_sku);
                $key = $sku.'_'.$product_list[$i]->size_id;
                if(isset($products[$key])){
                    $products[$key]+=1;
                }else{
                    $products[$key] = 1;
                }
                
                $products_sku[$sku] = $product_list[$i];
                $size_list[] = $product_list[$i]->size_id;
            }
            
            $size_list = array_values(array_unique($size_list));
            $size_list = Production_size_counts::wherein('id',$size_list)->where('is_deleted',0)->get()->toArray();
            
            $product_inventory = \DB::table('pos_product_master_inventory as ppmi')
            ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->leftJoin('store_products_demand as spd','spd.id','=','spdi.push_demand_id')           
            ->where('spdi.demand_id',$demand_id)         
            ->where('spdi.is_deleted',0)   
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('spdi.transfer_status',1)        
            //->where('spdi.receive_status',1)           
            ->where('ppm.is_deleted',0);
            
            $product_inventory_count = clone ($product_inventory);
            
            $product_inventory = $product_inventory->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','spd.invoice_no as push_demand_no')
            ->orderBy('spdi.id','ASC')->paginate(100, ['*'], 'inv_page');
            
            if($product_inventory->hasPages()){
                $product_inventory_count = $product_inventory_count->selectRaw('COUNT(spdi.id) as inv_count,SUM(spdi.store_base_price) as store_base_price_total,SUM(ppmi.sale_price) as sale_price_total')->first();
            }
                
            return view('store/inventory_return_demand_detail',array('product_list'=>$product_list,'product_inventory'=>$product_inventory,'demand_data'=>$demand_data,'error_message'=>$error_message,'user'=>$user,'products'=>$products,'products_sku'=>$products_sku,'size_list'=>$size_list,'product_inventory_count'=>$product_inventory_count));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_RETURN_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_return_demand_detail',array('error_message'=>$e->getMessage().', Line'.$e->getLine()));
            }
        }
    }
    
    function inventoryReturnDemandInvoice(Request $request,$id,$invoice_type_id = 1){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $products_list = $demand_data = $company_data = $products_sku = $demand_sku_list = $push_demand_list = array();
            
            $invoice_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            $demand_data = Store_products_demand::where('id',$demand_id)->first();
            
            $store_data = (!empty($demand_data->store_data))?json_decode($demand_data->store_data,false):Store::where('id',$demand_data->store_id)->first();
            $company_data = CommonHelper::getCompanyData();
            
            if($store_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($store_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            }else{
                $gst_name = '';
            }
            
            if($demand_data->invoice_type  == 'product_sku'){
                $demand_products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')            
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')    
                ->leftJoin('store_products_demand as spd','spd.id','=','spdi.push_demand_id')  
                ->where('spdi.demand_id',$demand_id)        
                ->where('spdi.transfer_status',1)        
                //->where('spdi.receive_status',1)   
                ->where('spdi.is_deleted',0)        
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)
                ->groupBy('ppm.id')        
                ->select('ppmi.base_price','ppmi.arnon_inventory','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount',
                'ppm.product_name','ppm.product_sku','poi.vendor_sku','ppm.hsn_code','spd.invoice_no as push_demand_no',\DB::raw('COUNT(ppmi.id) as product_quantity'))        
                ->get()->toArray();
                
                for($i=0;$i<count($demand_products_list);$i++){
                    $sku = $demand_products_list[$i]->product_sku;
                    if(!isset($demand_sku_list[$sku])){
                        $demand_sku_list[$sku] = array('prod'=>$demand_products_list[$i],'qty'=>$demand_products_list[$i]->product_quantity);
                    }else{
                        $demand_sku_list[$sku]['qty']+= $demand_products_list[$i]->product_quantity;
                    }

                    if(!empty($demand_products_list[$i]->push_demand_no)){
                        $push_demand_list[] = $demand_products_list[$i]->push_demand_no;
                    }
                }
            }else{
                $demand_products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')            
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')    
                ->leftJoin('store_products_demand as spd','spd.id','=','spdi.push_demand_id')  
                ->where('spdi.demand_id',$demand_id)        
                ->where('spdi.transfer_status',1)        
                //->where('spdi.receive_status',1)   
                ->where('spdi.is_deleted',0)        
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)
                ->select('ppmi.base_price','ppmi.arnon_inventory','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount',
                'ppm.product_name','ppm.product_sku','poi.vendor_sku','ppm.hsn_code','spd.invoice_no as push_demand_no','ppm.id as product_id','ppm.size_id','ppm.color_id')        
                ->get()->toArray();
                
                for($i=0;$i<count($demand_products_list);$i++){
                    $key = $demand_products_list[$i]->product_id;
                    if(!isset($demand_sku_list[$key])){
                        $demand_sku_list[$key] = array('prod'=>$demand_products_list[$i],'qty'=>1);
                    }else{
                        $demand_sku_list[$key]['qty']+= 1;
                    }

                    if(!empty($demand_products_list[$i]->push_demand_no)){
                        $push_demand_list[] = $demand_products_list[$i]->push_demand_no;
                    }
                }
            }
            
            $push_demand_list = array_values(array_unique($push_demand_list));
            
            $size_list = Production_size_counts::where('is_deleted',0)->orderBy('id')->get()->toArray();
            $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->orderBy('name')->get()->toArray();
            
            for($i=0;$i<count($size_list);$i++){
                $sizes[$size_list[$i]['id']] = $size_list[$i]['size'];
            }
            
            for($i=0;$i<count($color_list);$i++){
                $colors[$color_list[$i]['id']] = $color_list[$i]['name'];
            }
            
            $data = array('message' => 'products list','demand_sku_list' => $demand_sku_list,'company_data'=>$company_data,'gst_name'=>$gst_name,'store_data'=>$store_data,'demand_data'=>$demand_data,'invoice_type'=>$invoice_type,'push_demand_list'=>$push_demand_list,'sizes'=>$sizes,'colors'=>$colors);
            
            //return view('store/inventory_return_demand_invoice',$data);
            
            $pdf = PDF::loadView('store/inventory_return_demand_invoice', $data);

            return $pdf->download('inventory_return_demand_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_RETURN_DEMAND',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    private function getReturnDemandDebitNoteNo($store_user){
        $invoice_no = '';  
        
        $company_data = CommonHelper::getCompanyData();
        $financial_year = substr(CommonHelper::getFinancialYear(date('Y/m/d')),0,2);

        if($store_user->gst_no != $company_data['company_gst_no']){ // Different GST
            $invoice_no = 'K'.'DN'.$store_user->store_code.$financial_year;
            
            $store_demand = Store_products_demand::wherein('demand_type',array('inventory_return_complete','inventory_return_to_warehouse'))->where('invoice_no','LIKE',"{$invoice_no}%")->where('invoice_series_type',2)->select('invoice_no')->orderBy('invoice_no','DESC')->first();
            
            $store_demand_debit_note = Debit_notes::where('debit_note_no','LIKE',"{$invoice_no}%")->where('debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand')->where('invoice_series_type',2)->orderBy('debit_note_no','DESC')->first();
            
            $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,-5):0;
            $invoice_no_debit_note = (!empty($store_demand_debit_note) && !empty($store_demand_debit_note->debit_note_no))?substr($store_demand_debit_note->debit_note_no,-5):0;
            
            $invoice_no = max(array($invoice_no,$invoice_no_debit_note));
            
            $invoice_no = 'K'.'DN'.$store_user->store_code.$financial_year.str_pad($invoice_no+1, 5, "0", STR_PAD_LEFT);
        }elseif($store_user->gst_no == $company_data['company_gst_no'] && $store_user->state_id != $company_data['company_state_id']){ // Same GST and diff state
            $invoice_no = 'K'.'DN'.$store_user->store_code.$financial_year;
            
            $store_demand = Store_products_demand::wherein('demand_type',array('inventory_return_complete','inventory_return_to_warehouse'))->where('invoice_no','LIKE',"{$invoice_no}%")->where('invoice_series_type',2)->select('invoice_no')->orderBy('invoice_no','DESC')->first();
            
            $store_demand_debit_note = Debit_notes::where('debit_note_no','LIKE',"{$invoice_no}%")->where('debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand')->where('invoice_series_type',2)->orderBy('debit_note_no','DESC')->first();
            
            $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,-5):0;
            $invoice_no_debit_note = (!empty($store_demand_debit_note) && !empty($store_demand_debit_note->debit_note_no))?substr($store_demand_debit_note->debit_note_no,-5):0;
            
            $invoice_no = max(array($invoice_no,$invoice_no_debit_note));
            
            $invoice_no = 'K'.'DN'.$store_user->store_code.$financial_year.str_pad($invoice_no+1, 5, "0", STR_PAD_LEFT);
        }else{
            $invoice_no = 'K'.'UN'.$store_user->store_code.$financial_year;
            
            $store_demand = Store_products_demand::wherein('demand_type',array('inventory_return_complete','inventory_return_to_warehouse'))->where('invoice_no','LIKE',"{$invoice_no}%")->where('invoice_series_type',2)->select('invoice_no')->orderBy('invoice_no','DESC')->first();
            
            $store_demand_debit_note = Debit_notes::where('debit_note_no','LIKE',"{$invoice_no}%")->where('debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand')->where('invoice_series_type',2)->orderBy('debit_note_no','DESC')->first();
            
            $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,-5):0;
            $invoice_no_debit_note = (!empty($store_demand_debit_note) && !empty($store_demand_debit_note->debit_note_no))?substr($store_demand_debit_note->debit_note_no,-5):0;
            
            $invoice_no = max(array($invoice_no,$invoice_no_debit_note));
            $invoice_no = 'K'.'UN'.$store_user->store_code.$financial_year.str_pad($invoice_no+1, 5, "0", STR_PAD_LEFT);
        }

        return $invoice_no;
    }
    
    private function getReturnDemandCreditNoteNo(){
        $invoice_no = '';  
        $financial_year = CommonHelper::getFinancialYear(date('Y/m/d'));

        $store_demand = Store_products_demand::wherein('demand_type',array('inventory_return_complete','inventory_return_to_warehouse'))->whereRaw("(credit_invoice_no != '' && credit_invoice_no IS NOT NULL)")->where('invoice_series_type',2)->select('credit_invoice_no')->orderBy('credit_invoice_no','DESC')->first();
        
        if(!empty($store_demand) && !empty($store_demand->credit_invoice_no)){
            $invoice_financial_year = substr($store_demand->credit_invoice_no,3,4);
            $invoice_no = ($invoice_financial_year == $financial_year)?substr($store_demand->credit_invoice_no,7):0;
        }else{
            $invoice_no = 0;
        }
        
        
        $store_demand_debit_note = Debit_notes::whereRaw("(credit_note_no != '' && credit_note_no IS NOT NULL)")->where('debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand')->where('invoice_series_type',2)->orderBy('credit_note_no','DESC')->first();
        if(!empty($store_demand_debit_note) && !empty($store_demand_debit_note->credit_note_no)){
            $invoice_financial_year = substr($store_demand_debit_note->credit_note_no,3,4);
            $invoice_no_credit_note = ($invoice_financial_year == $financial_year)?substr($store_demand_debit_note->credit_note_no,7):0;
        }else{
            $invoice_no_credit_note = 0;
        }
        
        $invoice_no = max(array($invoice_no,$invoice_no_credit_note));
        
        $invoice_no = 'K'.'CN'.$financial_year.str_pad($invoice_no+1, 4, "0", STR_PAD_LEFT);  
        
        return $invoice_no;
    }
    
    private function getTikiReturnDemandDebitNoteNo(){
        $financial_year = CommonHelper::getFinancialYear(date('Y/m/d')); 
        $invoice_no = 'TKDN/'.substr($financial_year,0,2).'-'.substr($financial_year,2).'/';
        $store_demand = Store_products_demand::where('demand_type','inventory_transfer_to_store')->where('transfer_return_demand',1)->where('invoice_no','LIKE',"{$invoice_no}%")->select('invoice_no')->orderBy('invoice_no','DESC')->first();
        $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,-4):0;
        
        $invoice_no = 'TKDN/'.substr($financial_year,0,2).'-'.substr($financial_year,2).'/'.str_pad($invoice_no+1, 4, '0', STR_PAD_LEFT);
        
        return $invoice_no;
    }
    
    private function getTikiReturnDemandCreditNoteNo(){
        $financial_year = CommonHelper::getFinancialYear(date('Y/m/d')); 
        $invoice_no = 'TKCN/'.substr($financial_year,0,2).'-'.substr($financial_year,2).'/';
        $store_demand = Store_products_demand::where('demand_type','inventory_transfer_to_store')->where('transfer_return_demand',1)->where('credit_invoice_no','LIKE',"{$invoice_no}%")->select('credit_invoice_no')->orderBy('credit_invoice_no','DESC')->first();
        $invoice_no = (!empty($store_demand) && !empty($store_demand->credit_invoice_no))?substr($store_demand->credit_invoice_no,-4):0;
        
        $invoice_no = 'TKCN/'.substr($financial_year,0,2).'-'.substr($financial_year,2).'/'.str_pad($invoice_no+1, 4, '0', STR_PAD_LEFT);
        
        return $invoice_no;
    }
    
    function reviewInventory(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $reason_str =  '';
            $store_data = CommonHelper::getUserStoreData($user->id);
            
            if(isset($data['action']) && $data['action']  == 'check_product'){
                
                $barcode = trim($data['barcode']);
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
                ->leftJoin('store as s1','s1.id','=','ppmi.store_id')                
                ->leftJoin('store_products_demand as spd','spd.id', '=', 'ppmi.demand_id')        
                ->leftJoin('store as s2','s2.id','=','spd.store_id')                        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$barcode)       
                ->where('ppmi.fake_inventory',0)        
                ->select('ppmi.*','s1.store_name','s2.store_name as demand_store_name','spd.store_id as demand_store_id','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','ppm.is_deleted as ppm_is_deleted')
                ->first();
                
                if(empty($product_data)){
                    $reason_str = 'Inventory Product with QR Code: '.$barcode.' does not exists';
                    $insertArray = array('base_store_id'=>$store_data->id,'inv_barcode'=>$barcode,'reason_str'=>$reason_str);
                    Store_inventory_review::create($insertArray);
                    $product_data = array('reason_str'=>$reason_str);
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'success', 'message'=>'POS Inventory Review data','product_data'=>$product_data ));
                }
                
                $product_data = json_decode(json_encode($product_data),true);
                
                if($product_data['product_status'] == 0){
                    $reason_str.='Product is Warehouse In Pending <br/>';
                }
                
                if(empty($reason_str) && $product_data['store_id'] == 0){
                    $reason_str.='Product is not assigned to Store <br/>';
                }
                
                if(empty($reason_str)){
                    $empty_fields = array('product_master_id','store_id','demand_id','po_id','po_item_id','product_status');
                    for($i=0;$i<count($empty_fields);$i++){
                        if(empty($product_data[$empty_fields[$i]])){
                            $reason_str.=ucwords(str_replace('_',' ',$empty_fields[$i])).' is empty in database. <br/>';
                        }
                    }
                }
                
                if(empty($reason_str) && $product_data['store_id'] != $store_data->id){
                    $reason_str.='Product is assigned to '.$product_data['store_name'].' Store. <br/>';
                }
                
                if(empty($reason_str) && $product_data['demand_store_id'] != $store_data->id){
                    $reason_str.='Product Demand is created for '.$product_data['demand_store_name'].' Store. <br/>';
                }
                
                if(empty($reason_str) && $product_data['product_status'] != 4){
                    $reason_str.='Product Status is '.CommonHelper::getposProductStatusName($product_data['product_status']).'. <br/>';
                }
                
                if(empty($reason_str) && $product_data['status'] == 0){
                    $reason_str.='Inventory Product is disabled in database'.'. <br/>';
                }
                
                if(empty($reason_str) && $product_data['is_deleted'] == 1){
                    $reason_str.='Inventory Product is deleted in database'.'. <br/>';
                }
                
                if(empty($reason_str) && $product_data['ppm_is_deleted'] == 1){
                    $reason_str.='Master Product is deleted in database'.'. <br/>';
                }
                
                $insertArray = array('base_store_id'=>$store_data->id,'inv_barcode'=>$barcode,'reason_str'=>$reason_str,'inv_id'=>$product_data['id'],'product_id'=>$product_data['product_master_id'],'store_id'=>$product_data['store_id'],'demand_id'=>$product_data['demand_id']);
                $insertArray['po_id'] = $product_data['po_id'];$insertArray['po_item_id'] = $product_data['po_item_id'];$insertArray['product_status'] = $product_data['product_status'];
                $insertArray['inv_status'] = $product_data['status'];$insertArray['inv_is_deleted'] = $product_data['is_deleted'];
                
                Store_inventory_review::create($insertArray);
                
                $reason_str = (!empty($reason_str))?$reason_str:'No Reason Found';
                $product_data['reason_str'] = $reason_str;
                $product_data['product_status_text'] = CommonHelper::getposProductStatusName($product_data['product_status']);
                    
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory Review data','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_inventory'){
                $rec_per_page = 50;
                $product_list = \DB::table('store_inventory_review as str')
                ->leftJoin('pos_product_master_inventory as ppmi','ppmi.id', '=', 'str.inv_id')        
                ->leftJoin('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
                ->leftJoin('store as s1','s1.id','=','ppmi.store_id')                
               ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('str.base_store_id',$store_data->id)         
                ->where('str.is_deleted',0)            
                ->select('ppmi.*','s1.store_name','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','str.reason_str','str.created_at as str_created_at')
                ->orderBy('str.created_at','DESC')
                ->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'status' => 'success'),200);
            }
            
            return view('store/inventory_review',array('error_message'=>$error_message,'store_data'=>$store_data));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_INVENTORY_REVIEW',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_review',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryTransferStoreDemandList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $store_user = $page_types = $page_type = '';
            
            if($user->user_type == 9){
                $store_user = CommonHelper::getUserStoreData($user->id); 
                $page_types = array('1'=>$store_user->store_name.' to Other Stores','2'=>'Other Stores to '.$store_user->store_name);
                $page_type = (isset($data['page_type']))?$data['page_type']:1;
            }
            
            // valid demand status: loading, loaded, store_loading, store_loaded
            
            if(isset($data['action']) && $data['action'] == 'create_demand'){
                $validateionRules = array('store_id'=>'required');
                $attributes = array('store_id'=>'Store');
                
                // Tikki global store to other stores
                if(!empty($store_user) && $store_user->store_info_type == 3){
                    $validateionRules['transfer_field'] = 'required';
                    $validateionRules['transfer_percent'] = 'required|numeric|max:100';
                    $attributes['transfer_field'] = 'Transfer Type';
                    $attributes['transfer_percent'] = 'Transfer Margin Percent';
                }

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $to_store_id = trim($data['store_id']);
                $to_store_data = Store::where('id',$to_store_id)->first();
                $company_data = CommonHelper::getCompanyData();
                
                $insertArray = array('invoice_no'=>null,'credit_invoice_no'=>null,'user_id'=>$user->id,'demand_type'=>'inventory_transfer_to_store','demand_status'=>'loading','push_demand_id'=>null,'store_id'=>$to_store_id,'store_data'=>json_encode($to_store_data),'store_state_id'=>$to_store_data->state_id,'from_store_id'=>$store_user->id,'from_store_data'=>json_encode($store_user));
                
                // Tikki global to other stores
                if(!empty($store_user) && $store_user->store_info_type == 3){
                    $insertArray['transfer_field'] = trim($data['transfer_field']);
                    $insertArray['transfer_percent'] = trim($data['transfer_percent']);
                }
                
                if(!empty($store_user) && $store_user->store_info_type == 2 && $to_store_data->store_info_type == 3){
                    $insertArray['transfer_return_demand'] = 1;
                }
                
                $insertArray['company_gst_no'] = $company_data['company_gst_no'];
                $insertArray['company_gst_name'] = $company_data['company_name'];
                
                $demand = Store_products_demand::create($insertArray);
                
                CommonHelper::createLog('Store to Store Transfer Demand Created. Demand ID: '.$demand->id,'STORE_TO_STORE_DEMAND_CREATED','STORE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Transfer to Store demand created successfully','demand_details'=>$demand,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_gate_pass_pdf'){
                $demand_id = trim($data['id']);
                $demand_data = \DB::table('store_products_demand as spd')
                ->join('store as s','s.id', '=', 'spd.store_id')
                ->where('spd.id',$demand_id)
                ->select('spd.*','s.store_name','s.address_line1','s.address_line2','s.phone_no','s.gst_no','s.city_name','s.postal_code')->first();

                //$total_qty = Store_products_demand_detail::where('demand_id',$demand_id)->where('is_deleted',0)->where('status',1)->count();
                $total_qty = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->count();
                
                $gate_pass_data = Store_products_demand_courier::where('demand_id',$demand_id)->where('type','inventory_transfer_to_store')->where('is_deleted',0)->where('status',1)->first();
                $company_data = CommonHelper::getCompanyData();

                $data = array('message' => 'products list','gate_pass_data' => $gate_pass_data,'demand_data' => $demand_data,'company_data'=>$company_data,'total_qty'=>$total_qty);

                $pdf = PDF::loadView('store/inventory_transfer_to_store_gate_pass', $data);
                return $pdf->download('inventory_transfer_to_store_gate_pass_pdf');
            }
            
            $demands_list = \DB::table('store_products_demand as spd')
            ->join('store as s1','s1.id', '=', 'spd.store_id')
            ->join('store as s2','s2.id', '=', 'spd.from_store_id')  
            ->where('spd.demand_type','inventory_transfer_to_store');    
            
            if($user->user_type == 9){
                if($page_type == 1){
                    $demands_list = $demands_list->where('spd.from_store_id',$store_user->id);       
                }

                if($page_type == 2){
                    $demands_list = $demands_list->where('spd.store_id',$store_user->id)
                    ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'));       
                }
            }
            
            if($user->user_type != 9){
                $demands_list = $demands_list->where('spd.demand_status','!=','loading');
            }
            
            if(isset($data['store_id']) && !empty($data['store_id'])){
                $store_id = trim($data['store_id']);
                $demands_list = $demands_list->whereRaw("(spd.store_id = $store_id OR spd.from_store_id = $store_id)");
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $invoice_no = trim($data['invoice_no']);
                $demands_list = $demands_list->where('spd.invoice_no','LIKE','%'.$invoice_no.'%');
            }
            
            if(isset($data['invoice_id']) && !empty($data['invoice_id'])){
                $demands_list = $demands_list->where('spd.id',trim($data['invoice_id']));
            }
            
            $demands_list = $demands_list->where('spd.status',1)
            ->where('spd.is_deleted',0)
            ->where('s1.is_deleted',0)        
            ->where('s2.is_deleted',0)                
            ->select('spd.*','s1.store_name as to_store_name','s1.store_id_code as to_store_id_code','s2.store_name as from_store_name','s2.store_id_code as from_store_id_code');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $demands_list = $demands_list->offset($start)->limit($limit)->orderBy('spd.id','ASC')->get()->toArray();
            }else{
                $demands_list = $demands_list->orderBy('spd.created_at','DESC')->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_to_store_inv_transfer_demands_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Demand ID','Invoice No','From Store Name','Code','To Store Name','Code','Demand Status','Inventory','Created On');

                $callback = function() use ($demands_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($demands_list);$i++){
                        $total_data = json_decode($demands_list[$i]->total_data);
                        $total_qty = (!empty($total_data) && isset($total_data->total_qty))?$total_data->total_qty:'';
                        $status = CommonHelper::getDemandStatusText('inventory_transfer_to_store',$demands_list[$i]->demand_status);
                        $array = array($demands_list[$i]->id,$demands_list[$i]->invoice_no,$demands_list[$i]->from_store_name,$demands_list[$i]->from_store_id_code,$demands_list[$i]->to_store_name,$demands_list[$i]->to_store_id_code,$status,$total_qty,date('d-m-Y',strtotime($demands_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            if($user->user_type == 9){
                $store_list = Store::where('id','!=',$store_user->id)->where('is_deleted',0)->orderBy('store_name')->get()->toArray();
            }else{
                $store_list = Store::where('is_deleted',0)->orderBy('store_name')->get()->toArray();
            }
            
            return view('store/inventory_transfer_store_demand_list',array('demands_list'=>$demands_list,'error_message'=>$error_message,'user'=>$user,'page_types'=>$page_types,'page_type'=>$page_type,'store_user'=>$store_user,'store_list'=>$store_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_transfer_store_demand_list',array('error_message'=>$e->getMessage().', Line: '.$e->getLine()));
            }
        }
    }
    
    function inventoryTransferStoreDemandDetail(Request $request,$id,$invoice_type_id = 1){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = $pdf_type = $debit_credit_note_type = '';
            $product_inventory = $products = $size_list = $products_sku = $size_list = array();
            
            if(isset($data['action']) && $data['action']  == 'open_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('comments'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'store_loading','comments'=>$data['comments']));
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Store Transfer Demand Opened by Administrator. Demand ID: '.$demand_id,'STORE_TO_STORE_DEMAND_REOPENED','STORE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully','status' => 'success'),200);
            }
            
            $product_inventory = \DB::table('pos_product_master_inventory as ppmi')
            ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')                
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')                
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->where('spdi.demand_id',$demand_id)         
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('spdi.is_deleted',0)  
            ->where('ppm.is_deleted',0)
            ->where('spdi.transfer_status',1)        
            ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.store_base_price');
            
            if(isset($data['action']) && $data['action']  == 'search_inv_barcode'){
                $product_inventory = $product_inventory->where('ppmi.peice_barcode',trim($data['barcode']))->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory data','product' => $product_inventory),200);
            }
            
            $product_inventory = $product_inventory->paginate(100, ['*'], 'inv_page');
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s1','s1.id', '=', 'spd.store_id')
            ->join('store as s2','s2.id', '=', 'spd.from_store_id')        
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s1.store_name as to_store_name','s2.store_name as from_store_name','u.name as user_name','s1.store_id_code as to_store_id_code','s2.store_id_code as from_store_id_code')
            ->first();
            
            $from_store_data = json_decode($demand_data->from_store_data,false);
            $to_store_data = json_decode($demand_data->store_data,false);
            
            if($from_store_data->gst_no == $to_store_data->gst_no){
                $gst_name = '';
            }elseif($from_store_data->state_id != $to_store_data->state_id){
                $gst_name = 'i_gst';
            }else{
                $gst_name = 's_gst';
            }
            
            if($demand_data->transfer_return_demand == 1){
                $pdf_type = 'debit_credit_note';
                $debit_credit_note_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            }else{
                $pdf_type = 'invoice_challan';
            }
            
            $store_user = CommonHelper::getUserStoreData($user->id);
            
            $product_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')       
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')           
            ->where('spdi.demand_id',$demand_id)        
            ->where('ppmi.is_deleted',0)
            ->where('spdi.is_deleted',0)  
            ->where('ppm.is_deleted',0)
            ->where('spdi.transfer_status',1)        
            //->where('spdi.receive_status',1)           
            ->select('ppmi.base_price','ppm.product_barcode','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','ppm.size_id','dlim_1.name as color_name')        
            ->get()->toArray();
            
            for($i=0;$i<count($product_list);$i++){
                $sku = strtolower($product_list[$i]->product_sku);
                $key = $sku.'_'.$product_list[$i]->size_id;
                if(isset($products[$key])){
                    $products[$key]+=1;
                }else{
                    $products[$key] = 1;
                }
                
                $products_sku[$sku] = $product_list[$i];
                $size_list[] = $product_list[$i]->size_id;
            }
            
            $size_list = array_values(array_unique($size_list));
            $size_list = Production_size_counts::wherein('id',$size_list)->where('is_deleted',0)->get()->toArray();
            
            $gate_pass_data = \DB::table('store_products_demand_courier')
            ->where('demand_id',$demand_id)->where('type','inventory_transfer_to_store')->where('status',1)->where('is_deleted',0)
            ->select('*')->first();
            
            $store_user = CommonHelper::getUserStoreData($user->id); 
            
            return view('store/inventory_transfer_store_demand_detail',array('product_inventory'=>$product_inventory,'products'=>$products,'demand_data'=>$demand_data,'error_message'=>$error_message,'gate_pass_data'=>$gate_pass_data,'user'=>$user,'size_list'=>$size_list,'store_user'=>$store_user,'products_sku'=>$products_sku,'pdf_type'=>$pdf_type,'debit_credit_note_type'=>$debit_credit_note_type));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_transfer_store_demand_detail',array('error_message'=>$e->getMessage().' '.$e->getLine()));
            }
        }
    }
    
    function editInventoryTransferStoreDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.hsn_code','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists' ));
                }
                
                $demand_inventory_prod = Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->first();
                
                if(!empty($demand_inventory_prod)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product already added in this demand', 'errors' =>'Inventory Product already added in this demand' ));
                }
                
                if($product_data->product_status != 4){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> status is not Ready for Sale', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> status is not Ready for Sale' ));
                }
                
                if($product_data->store_id != $data['store_id']){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists in this store', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists in this store' ));
                }
                
                \DB::beginTransaction();
                
                $demand_data = Store_products_demand::where('id',$data['demand_id'])->first();
                
                $company_data = CommonHelper::getCompanyData();
                $from_store_data = Store::where('id',$demand_data->from_store_id)->first();
                $to_store_data = Store::where('id',$demand_data->store_id)->first();
                
                if(!empty($demand_data->transfer_field) && !empty($demand_data->transfer_percent)){
                    // It is store base price but not store base rate
                    if($demand_data->transfer_field == 'store_cost_price'){
                        $store_base_rate = $product_data->store_base_price;
                        $store_base_rate = $store_base_rate+round($store_base_rate*($demand_data->transfer_percent/100),6);
                    }else{
                        $store_base_rate = round($product_data->sale_price*($demand_data->transfer_percent/100),2);
                    }
                    
                    //$store_base_rate = ($demand_data->transfer_field == 'store_cost_price')?$product_data->store_base_price:$product_data->sale_price;
                    //$store_base_rate = $store_base_rate+round($store_base_rate*($demand_data->transfer_percent/100),6);
                    $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                    if(!empty($gst_data)){
                        $gst_percent = $gst_data->rate_percent;
                    }else{
                        $gst_percent = ($store_base_rate >= 1000)?12:5;
                    }
                    $gst_amount = round($store_base_rate*($gst_percent/100),6);
                    $store_base_price = $store_base_rate+$gst_amount;
                }else{
                
                    if($from_store_data->store_type == 1 && $to_store_data->store_type == 2){
                        // Kiaasa to Franchisee.  Increase by 10%  // Add 10% to vendor base price if store is franchise
                        $store_base_rate = round($product_data->vendor_base_price+($product_data->vendor_base_price*.10),6);

                        if($to_store_data->gst_no != $company_data['company_gst_no']){
                            $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                            if(!empty($gst_data)){
                                $gst_percent = $gst_data->rate_percent;
                            }else{
                                $gst_percent = ($store_base_rate >= 1000)?12:5;
                            }
                            $gst_amount = round($store_base_rate*($gst_percent/100),6);
                        }else{
                             //It is not working as if $to_store_data->store_type == 2, then its gst no is never equal to company gst as store is franchisee
                            $gst_percent = $gst_amount = 0;
                        }

                        $store_base_price = $store_base_rate+$gst_amount;
                    }else{
                        // Do not increase/decrease rates in other cases
                        $store_base_rate = $product_data->store_base_rate;
                        $store_base_price = $product_data->store_base_price;
                        $gst_percent = $product_data->store_gst_percent;
                        $gst_amount = $product_data->store_gst_amount;
                    }
                }
                
                $product_data->spdi_store_base_price = round($store_base_price,2);
                    
                $insertArray = array('demand_id'=>$data['demand_id'],'inventory_id'=>$product_data->id,'transfer_status'=>1,'transfer_date'=>date('Y/m/d H:i:s'),'push_demand_id'=>$product_data->demand_id,
                'base_price'=>$product_data->base_price,'sale_price'=>$product_data->sale_price,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,
                'store_id'=>$demand_data->store_id,'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'from_store_id'=>$demand_data->from_store_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                Store_products_demand_inventory::create($insertArray);

                // Add/update store demand detail row
                /*$demand_product = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->first();
                if(empty($demand_product)){
                   $insertArray = array('demand_id'=>$data['demand_id'],'store_id'=>null,'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id); 
                   Store_products_demand_detail::create($insertArray);
                }else{
                    $demand_product->increment('product_quantity');
                }*/
                
                //$updateArray = array('product_status'=>3);
                $updateArray = array('store_id'=>$demand_data->store_id,'demand_id'=>$demand_id,'product_status'=>3);
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> added','product_data'=>$product_data,),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_demand_inventory'){
                $rec_per_page = 100;
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')             
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('ppmi.product_status','>=',0)         
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)        
                ->where('ppm.is_deleted',0);
                
                if(isset($data['barcode']) && !empty($data['barcode'])){
                    $product_list = $product_list->where('ppmi.peice_barcode',trim($data['barcode']));
                }  
                
                if(isset($data['product_id']) && !empty($data['product_id'])){
                    $product_list = $product_list->where('ppmi.product_master_id',trim($data['product_id']));
                }  
                        
                $product_list = $product_list->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.store_base_price as spdi_store_base_price')
                ->orderBy('ppmi.store_assign_date','ASC')->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')             
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('ppmi.product_status','>=',0)         
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('spdi.is_deleted',0)         
                ->groupByRaw('ppmi.product_master_id')
                ->selectRaw('ppm.id,ppm.product_sku,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                ->orderByRaw('poi.vendor_sku,psc.id')        
                ->get()->toArray();
                
                $inventory_count = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('ppmi.product_status','>=',0)         
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('spdi.is_deleted',0)
                ->count();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'sku_list' => $sku_list,'inventory_count'=>$inventory_count),200);
            }
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name')->first();
            
            if(isset($data['action']) && $data['action']  == 'get_demand_preview_data'){
                $demand_total_data = CommonHelper::updateDemandTotalData($demand_id,3,1);
                $total_data = $demand_total_data['total_data'];
                $store_data = json_decode($demand_data->store_data,true);
                $from_store_data = json_decode($demand_data->from_store_data,true);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand data','demand_data'=>$demand_data,'total_data'=>$total_data,'store_data'=>$store_data,'from_store_data'=>$from_store_data),200);
            }
            
            return view('store/inventory_transfer_store_demand_edit',array('error_message'=>$error_message,'demand_data'=>$demand_data));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_TRANSFER_STORE_DEMAND',__FUNCTION__,__FILE__);
            \DB::rollBack();
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine().$e->getFile()),500);
            }else{
                return view('store/inventory_transfer_store_demand_edit',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function updateInventoryTransferStoreDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name','u.name as user_name')->first();
            
            if(isset($data['action']) && $data['action']  == 'close_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('boxes_count'=>'required|numeric','transporter_name'=>'required','transporter_gst'=>'required','docket_no'=>'required','eway_bill_no'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                // Check if Demand have Duplicate Inventory
                $duplicate_inv_qrcodes = CommonHelper::getDemandDuplicateInventory($demand_id);
                if(!empty($duplicate_inv_qrcodes)){
                    $duplicate_inv_qrcodes_str = implode(', ',$duplicate_inv_qrcodes);
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Demand have Duplicate Inventory with QR Codes: '.$duplicate_inv_qrcodes_str, 'errors' =>'Demand have Duplicate Inventory with QR Codes: '.$duplicate_inv_qrcodes_str));
                }
                
                $insertArray = array('type'=>'inventory_transfer_to_store','demand_id'=>$demand_id,'boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no']);
                $demand_courier = Store_products_demand_courier::create($insertArray);
                
                $demand_inventory =  Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->get()->toArray();
                
                for($i=0;$i<count($demand_inventory);$i++){
                    $updateArray = array('store_id'=>$demand_data->store_id,'demand_id'=>$demand_id,'product_status'=>3);
                    Pos_product_master_inventory::where('id',$demand_inventory[$i]['inventory_id'])->update($updateArray);
                }
                
                // previous demands have invoice no
                if(empty($demand_data->invoice_no)){
                    if($demand_data->transfer_return_demand == 1){
                        //$store_user = CommonHelper::getUserStoreData($user->id); 
                        $invoice_no = $debit_note_no = $this->getTikiReturnDemandDebitNoteNo();
                    }else{
                        $invoice_no = CommonHelper::getStoreToStoreTransferInvoiceNo($demand_data->from_store_id,$demand_data->store_id);
                    }

                    $invoice_no_exists = Store_products_demand::where('invoice_no',$invoice_no)->where('invoice_series_type',2)->count();
                    if($invoice_no_exists > 0 || empty($invoice_no)){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                    }

                    $updateArray = array('invoice_no'=>$invoice_no,'demand_status'=>'loaded','courier_id'=>$demand_courier->id,'debit_note_date'=>date('Y/m/d H:i:s'));
                    Store_products_demand::where('id',$demand_id)->update($updateArray);
                }else{
                    $updateArray = array('demand_status'=>'loaded','courier_id'=>$demand_courier->id);
                    Store_products_demand::where('id',$demand_id)->update($updateArray);
                }
                
                CommonHelper::updateDemandTotalData($demand_id,3);
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Store Transfer Demand Closed. Demand ID: '.$demand_id,'STORE_TO_STORE_DEMAND_CLOSED','STORE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_gate_pass_data'){
                $validateionRules = array('boxes_count'=>'required|numeric','transporter_name'=>'required','transporter_gst'=>'required','docket_no'=>'required','eway_bill_no'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $updateArray = array('boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no']);
                Store_products_demand_courier::where('demand_id',$data['demand_id'])->where('type','inventory_transfer_to_store')->update($updateArray);
                
                CommonHelper::createLog('Store to Store Transfer Demand Gate Pass Data Updated. Demand ID: '.$data['demand_id'],'STORE_TO_STORE_DEMAND_GATE_PASS_UPDATED','STORE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Transportation Details updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_push_demand_items'){
                
                \DB::beginTransaction();
                
                $inv_items = Pos_product_master_inventory::wherein('id',$data['deleteChk'])->get()->toArray();
                for($i=0;$i<count($inv_items);$i++){
                    $updateArray = array('is_deleted'=>1);
                    Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$inv_items[$i]['id'])->where('is_deleted',0)->update($updateArray);
                    
                    //Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$inv_items[$i]['product_master_id'])->where('is_deleted',0)->decrement('product_quantity',1);
                    
                    $prev_demand_inv = Store_products_demand_inventory::where('demand_id','!=',$data['demand_id'])->where('inventory_id',$inv_items[$i]['id'])->where('is_deleted',0)->orderBy('id','DESC')->first();
                    
                    $prev_demand = Store_products_demand::where('id',$prev_demand_inv->demand_id)->first();
                    $updateArray = array('store_id'=>$prev_demand->store_id,'demand_id'=>$prev_demand->id,'product_status'=>4);
                    
                    Pos_product_master_inventory::where('id',$inv_items[$i]['id'])->update($updateArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Store Transfer Demand Inventory Deleted. Demand ID: '.$data['demand_id'],'STORE_TO_STORE_DEMAND_INVENTORY_DELETED','STORE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand Items deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'cancel_demand'){
                $demand_id = trim($data['demand_id']);
                
                $demand_data = \DB::table('store_products_demand as spd')
                ->where('spd.id',$demand_id)
                ->where('spd.status',1)
                ->where('spd.is_deleted',0)
                ->select('spd.*')
                ->first();
                
                $inv_transferred = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')
                ->where('spdi.demand_id',$demand_id)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)        
                ->select('spdi.*')
                ->get()->toArray();
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($inv_transferred);$i++){
                    
                    $demand_inv = $inv_transferred[$i];
                    
                    $updateArray = array('store_id'=>$demand_data->from_store_id,'demand_id'=>$demand_inv->push_demand_id,'product_status'=>4);
                    Pos_product_master_inventory::where('id',$demand_inv->inventory_id)->update($updateArray);
                }
                
                $updateArray = array('demand_status'=>'cancelled','cancel_user_id'=>$user->id,'cancel_comments'=>trim($data['comments']),'cancel_date'=>date('Y/m/d H:i:s'));
                
                Store_products_demand::where('id',$demand_id)->update($updateArray);
                
                $updateArray = array('demand_status'=>3);
                
                //Store_products_demand_detail::where('demand_id',$demand_id)->update($updateArray);
                Store_products_demand_inventory::where('demand_id',$demand_id)->update($updateArray);
                Store_products_demand_courier::where('demand_id',$demand_id)->update($updateArray);
                Store_products_demand_sku::where('demand_id',$demand_id)->update($updateArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Store Transfer Demand Cancelled. Demand ID: '.$demand_id,'STORE_TO_STORE_DEMAND_CANCELLED','STORE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand cancelled successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'import_demand_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $inv_id_array = $demand_detail_listing = $barcodes_list = $barcodes_updated = array();
                $demand_id = trim($data['demand_id']);
                $store_id = trim($data['store_id']);
                
                $barcode_data = CommonHelper::importInventoryBarcodes($request,$data);
                $dest_folder = $barcode_data['dest_folder'];
                $file_name =  $barcode_data['file_name'];
                $barcodes = $barcode_data['barcodes'];

                if(!empty($barcode_data['error_msg'])){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }

                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$barcode_data['error_msg'], 'errors' =>$barcode_data['error_msg']));
                }
                
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->wherein('ppmi.peice_barcode',$barcodes)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->select('ppmi.*','ppm.product_sku','ppm.hsn_code')
                ->get()->toArray();
                
                // Check if product status is 4
                for($i=0;$i<count($product_list);$i++){
                    if($product_list[$i]->product_status != 4){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product not ready for sale <br/>';
                    }elseif($product_list[$i]->store_id != $store_id){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product does not exists in this store <br/>';
                    }
                    
                    $barcodes_list[] = $product_list[$i]->peice_barcode;
                    $inv_id_array[] =  $product_list[$i]->id;
                }
                
                // Iterate user provided barcodes to check with database barcodes
                if(count($barcodes) != count($product_list)){
                    for($i=0;$i<count($barcodes);$i++){
                        if(!in_array($barcodes[$i],$barcodes_list)){
                            $error_msg.=$barcodes[$i].': Inventory Product does not exist <br/>';
                        }
                    }
                }
                
                if(!empty($error_msg)){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }
                    
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                $demand_data = Store_products_demand::where('id',$demand_id)->first();
                $company_data = CommonHelper::getCompanyData();
                $from_store_data = Store::where('id',$demand_data->from_store_id)->first();
                $to_store_data = Store::where('id',$demand_data->store_id)->first();
                $date = date('Y/m/d H:i:s');
                
                /*$demand_detail_list = Store_products_demand_detail::where('demand_id',$demand_id)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($demand_detail_list);$i++){
                    $demand_detail_listing[$demand_detail_list[$i]['product_id']] = $demand_detail_list[$i];
                }*/
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($product_list);$i++){
                    $product_data = $product_list[$i];
                    if(!empty($demand_data->transfer_field) && !empty($demand_data->transfer_percent)){
                        // It is store base price but not store base rate
                        $store_base_rate = ($demand_data->transfer_field == 'store_cost_price')?$product_data->store_base_price:$product_data->sale_price;
                        $store_base_rate = $store_base_rate+round($store_base_rate*($demand_data->transfer_percent/100),6);
                        $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                        if(!empty($gst_data)){
                            $gst_percent = $gst_data->rate_percent;
                        }else{
                            $gst_percent = ($store_base_rate >= 1000)?12:5;
                        }
                        $gst_amount = round($store_base_rate*($gst_percent/100),6);
                        $store_base_price = $store_base_rate+$gst_amount;
                    }else{

                        if($from_store_data->store_type == 1 && $to_store_data->store_type == 2){
                            // Kiaasa to Franchisee.  Increase by 10%  // Add 10% to vendor base price if store is franchise
                            $store_base_rate = round($product_data->vendor_base_price+($product_data->vendor_base_price*.10),6);

                            if($to_store_data->gst_no != $company_data['company_gst_no']){
                                $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                                if(!empty($gst_data)){
                                    $gst_percent = $gst_data->rate_percent;
                                }else{
                                    $gst_percent = ($store_base_rate >= 1000)?12:5;
                                }
                                $gst_amount = round($store_base_rate*($gst_percent/100),6);
                            }else{
                                 //It is not working as if $to_store_data->store_type == 2, then its gst no is never equal to company gst as store is franchisee
                                $gst_percent = $gst_amount = 0;
                            }

                            $store_base_price = $store_base_rate+$gst_amount;
                        }else{
                            // Do not increase/decrease rates in other cases
                            $store_base_rate = $product_data->store_base_rate;
                            $store_base_price = $product_data->store_base_price;
                            $gst_percent = $product_data->store_gst_percent;
                            $gst_amount = $product_data->store_gst_amount;
                        }
                    }

                    $insertArray = array('demand_id'=>$demand_id,'inventory_id'=>$product_data->id,'transfer_status'=>1,'transfer_date'=>$date,'push_demand_id'=>$product_data->demand_id,'base_price'=>$product_data->base_price,'sale_price'=>$product_data->sale_price,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,
                    'store_id'=>$demand_data->store_id,'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'from_store_id'=>$demand_data->from_store_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                    Store_products_demand_inventory::create($insertArray);

                    // Add/update store demand detail row
                    /*if(!isset($demand_detail_listing[$product_data->product_master_id])){
                       $insertArray = array('demand_id'=>$data['demand_id'],'store_id'=>null,'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id); 
                       Store_products_demand_detail::create($insertArray);
                       $demand_detail_listing[$product_data->product_master_id] = 1;
                    }else{
                        Store_products_demand_detail::where('demand_id',$demand_id)->where('product_id',$product_data->product_master_id)->increment('product_quantity');
                    }*/
                }
                
                $updateArray = array('store_id'=>$demand_data->store_id,'demand_id'=>$demand_id,'product_status'=>3);
                Pos_product_master_inventory::wherein('id',$inv_id_array)->update($updateArray);
                
                \DB::commit();
                
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Demand updated successfully'),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_TRANSFER_STORE_DEMAND',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function loadInventoryTransferStoreDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')         
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')            
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])        
                ->where('ppmi.demand_id',$data['demand_id'])                
                ->where('spdi.demand_id',$data['demand_id'])                       
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('spdi.is_deleted',0)  
                ->where('ppmi.fake_inventory',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.store_base_rate as spdi_store_base_rate','spdi.store_gst_percent as spdi_store_gst_percent','spdi.store_gst_amount as spdi_store_gst_amount','spdi.store_base_price as spdi_store_base_price')
                ->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists' ));
                }
                
                if($product_data->product_status > 3){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> already added', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> already added' ));
                }
                
                if($product_data->product_status < 3){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> not available', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> not available' ));
                }
                
                \DB::beginTransaction();
               
                $updateArray = array('product_status'=>4,'store_intake_date'=>date('Y/m/d H:i:s'),'store_base_rate'=>$product_data->spdi_store_base_rate,'store_gst_percent'=>$product_data->spdi_store_gst_percent,'store_gst_amount'=>$product_data->spdi_store_gst_amount,'store_base_price'=>$product_data->spdi_store_base_price);
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 
                
                //$demand_product = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->increment('store_intake_qty');
                
                Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->update(array('receive_status'=>1,'receive_date'=>date('Y/m/d H:i:s'))); 
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data with code: <b>'.$product_data->peice_barcode.'</b> added','product_data'=>$product_data,),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_demand_inventory'){
                $rec_per_page = 100;
                $product_list = \DB::table('store_products_demand_inventory as spdi')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')            
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('spdi.receive_status',1)        
                ->where('spdi.is_deleted',0)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0);
                
                if(isset($data['barcode']) && !empty($data['barcode'])){
                    $product_list = $product_list->where('ppmi.peice_barcode',trim($data['barcode']));
                }  
                
                if(isset($data['product_id']) && !empty($data['product_id'])){
                    $product_list = $product_list->where('ppmi.product_master_id',trim($data['product_id']));
                }  
                
                $product_list = $product_list->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.store_base_price as spdi_store_base_price')
                ->orderBy('ppmi.store_intake_date','ASC')
                ->paginate($rec_per_page);
                        
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')             
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('spdi.receive_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('spdi.is_deleted',0)         
                ->groupByRaw('ppmi.product_master_id')
                ->selectRaw('ppm.id,ppm.product_sku,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                ->orderByRaw('poi.vendor_sku,psc.id')        
                ->get()->toArray();
                
                $inventory_total = \DB::table('store_products_demand_inventory as spdi')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('spdi.demand_id',$data['demand_id'])        
                ->where('spdi.is_deleted',0)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0);
                
                $inventory_received = clone $inventory_total;
                
                $inventory_total = $inventory_total->count();
                $inventory_received = $inventory_received->where('spdi.receive_status',1)->count();
                
                $inv_count_data = array('inventory_total'=>$inventory_total,'inventory_received'=>$inventory_received);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'inv_count_data' => $inv_count_data,'sku_list'=>$sku_list),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_count_data'){
                $inv_data = array('rec_count'=>0,'rec_base_price_sum'=>0,'rec_sale_price_sum'=>0);
                $inv_received = Pos_product_master_inventory::where('demand_id',$data['demand_id'])        
                ->whereIn('product_status',array(4,5))        
                ->where('is_deleted',0)->where('status',1)
                ->selectRaw('COUNT(id) as cnt,SUM(store_base_rate) as base_price_sum,SUM(sale_price) as sale_price_sum')->first();
                
                if(!empty($inv_received)){
                    $inv_data['rec_count'] = $inv_received->cnt;
                    $inv_data['rec_base_price_sum'] = $inv_received->base_price_sum;
                    $inv_data['rec_sale_price_sum'] = $inv_received->sale_price_sum;
                }
                
                $inv_total = Pos_product_master_inventory::where('demand_id',$data['demand_id'])        
                ->where('is_deleted',0)->where('status',1)
                ->count();
                
                $inv_data['inv_total'] = $inv_total;
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','inv_data'=>$inv_data,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'close_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('comments_close_demand'=>'required');
                $attributes = array('comments_close_demand'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $demand_data = Store_products_demand::where('id',$demand_id)->first();
                $updateArray = array('demand_status'=>'store_loaded','comments'=>$data['comments_close_demand']);
                
                if($demand_data->transfer_return_demand == 1){
                    $credit_note_no =  $this->getTikiReturnDemandCreditNoteNo();
                    
                    $invoice_no_exists = Store_products_demand::where('credit_invoice_no',$credit_note_no)->count();
                    if($invoice_no_exists > 0 || empty($credit_note_no)){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                    }
                    
                    $updateArray['credit_invoice_no'] = $credit_note_no;
                    $updateArray['credit_note_date'] = date('Y/m/d H:i:s');
                }
                
                Store_products_demand::where('id',$demand_id)->update($updateArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Store Transfer Demand Closed. Demand ID: '.$demand_id,'STORE_TO_STORE_DEMAND_CLOSED','STORE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'import_demand_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $inv_id_array = $demand_detail_listing = $barcodes_list = $barcodes_updated = array();
                $demand_id = trim($data['demand_id']);
                $store_id = trim($data['store_id']);
                
                $barcode_data = CommonHelper::importInventoryBarcodes($request,$data);
                $dest_folder = $barcode_data['dest_folder'];
                $file_name =  $barcode_data['file_name'];
                $barcodes = $barcode_data['barcodes'];

                if(!empty($barcode_data['error_msg'])){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }

                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$barcode_data['error_msg'], 'errors' =>$barcode_data['error_msg']));
                }
                
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')         
                ->wherein('ppmi.peice_barcode',$barcodes)        
                ->where('ppmi.demand_id',$demand_id)                
                ->where('spdi.demand_id',$demand_id)                       
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)  
                ->where('ppmi.fake_inventory',0)        
                ->select('ppmi.*','spdi.store_base_rate as spdi_store_base_rate','spdi.store_gst_percent as spdi_store_gst_percent','spdi.store_gst_amount as spdi_store_gst_amount','spdi.store_base_price as spdi_store_base_price')
                ->get()->toArray();
                
                // Check if product status is 4
                for($i=0;$i<count($product_list);$i++){
                    if($product_list[$i]->product_status != 3){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product not transit to store <br/>';
                    }
                    
                    $barcodes_list[] = $product_list[$i]->peice_barcode;
                    $inv_id_array[] =  $product_list[$i]->id;
                }
                
                // Iterate user provided barcodes to check with database barcodes
                if(count($barcodes) != count($product_list)){
                    for($i=0;$i<count($barcodes);$i++){
                        if(!in_array($barcodes[$i],$barcodes_list)){
                            $error_msg.=$barcodes[$i].': Inventory Product does not exist <br/>';
                        }
                    }
                }
                
                if(!empty($error_msg)){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }
                    
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                $date = date('Y/m/d H:i:s');
                
                \DB::beginTransaction();
               
                for($i=0;$i<count($product_list);$i++){
                    $product_data = $product_list[$i];
                    $updateArray = array('product_status'=>4,'store_intake_date'=>$date,'store_base_rate'=>$product_data->spdi_store_base_rate,'store_gst_percent'=>$product_data->spdi_store_gst_percent,'store_gst_amount'=>$product_data->spdi_store_gst_amount,'store_base_price'=>$product_data->spdi_store_base_price);
                    Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 
                
                    //$demand_product = Store_products_demand_detail::where('demand_id',$demand_id)->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->increment('store_intake_qty');
                }
                
                Store_products_demand_inventory::where('demand_id',$demand_id)->wherein('inventory_id',$inv_id_array)->where('is_deleted',0)->update(array('receive_status'=>1,'receive_date'=>$date)); 
                
                \DB::commit();
                
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }
                    
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully'),200);
            }
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name')->first();
            
            if(strtolower($demand_data->demand_status) == 'warehouse_dispatched'){
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'store_loading'));
            }
            
            return view('store/inventory_transfer_store_demand_load',array('error_message'=>$error_message,'demand_data'=>$demand_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_TRANSFER_STORE_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/inventory_transfer_store_demand_load',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function InventoryTransferStoreDemandGatePass(Request $request,$id){
        try{
            $data = $request->all();
            $demand_id = $id;
            $user = Auth::user();
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)
            ->select('spd.*','s.store_name','s.address_line1','s.address_line2','s.phone_no','s.gst_no','s.city_name','s.postal_code')->first();
            
            /*$total_qty = Store_products_demand_detail::where('demand_id',$demand_id)->where('is_deleted',0)->where('status',1)->selectRaw('SUM(product_quantity) as total_qty')->first();
            $total_qty = $total_qty->total_qty;*/
            
            $total_qty = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->count();
            
            $gate_pass_data = Store_products_demand_courier::where('demand_id',$demand_id)->where('type','inventory_push')->where('is_deleted',0)->where('status',1)->first();
            $company_data = CommonHelper::getCompanyData();
            
            $data = array('message' => 'products list','gate_pass_data' => $gate_pass_data,'demand_data' => $demand_data,'company_data'=>$company_data,'total_qty'=>$total_qty);
            
            //return view('warehouse/inventory_push_demand_gate_pass',$data);
            
            $pdf = PDF::loadView('store/inventory_transfer_store_demand_gate_pass', $data);

            return $pdf->download('inventory_transfer_store_demand_gate_pass_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }
    }
    
    function inventoryTransferStoreDemandInvoice(Request $request,$id,$invoice_type_id = 1){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $demand_id = $id;
            $user = Auth::user();
            $products_list = $demand_data = $company_data = $products_sku = $demand_sku_list = $sizes = $colors = array();
            $pdf_type = $debit_credit_note_type = '';
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->join('store as s2','s2.id', '=', 'spd.from_store_id')        
            ->where('spd.id',$demand_id)
            ->select('spd.*','u.name as user_name','s.store_name','s.address_line1','s.address_line2','s.phone_no','s.gst_no','s.city_name','s.postal_code','s.gst_name','s2.state_id as from_state_id','s.state_id as to_state_id')
            ->first();
            
            $store_user = CommonHelper::getUserStoreData($user->id);
            
            $from_store_data = json_decode($demand_data->from_store_data,false);
            $to_store_data = json_decode($demand_data->store_data,false);
            
            if($from_store_data->gst_no == $to_store_data->gst_no){
                $gst_name = '';
            }elseif($from_store_data->state_id != $to_store_data->state_id){
                $gst_name = 'i_gst';
            }else{
                $gst_name = 's_gst';
            }
            
            if($demand_data->transfer_return_demand == 1){
                $pdf_type = 'debit_credit_note';
                $debit_credit_note_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            }else{
                $pdf_type = 'invoice_challan';
            }
            
            if($demand_data->invoice_type  == 'product_sku'){
                $demand_products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')            
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')    
                ->leftJoin('store_products_demand as spd','spd.id','=','spdi.push_demand_id')  
                ->where('spdi.demand_id',$demand_id)        
                ->where('spdi.transfer_status',1)
                ->where('spdi.is_deleted',0)        
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)
                ->where('spdi.transfer_status',1)        
                //->where('spdi.receive_status',1)           
                ->groupBy('ppm.id')        
                ->select('ppmi.base_price','ppmi.arnon_inventory','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount',
                'ppm.product_name','ppm.product_sku','poi.vendor_sku','ppm.hsn_code','spd.invoice_no as push_demand_no',\DB::raw('COUNT(ppmi.id) as product_quantity'))        
                ->get()->toArray();

                for($i=0;$i<count($demand_products_list);$i++){
                    $sku = $demand_products_list[$i]->product_sku;
                    if(!isset($demand_sku_list[$sku])){
                        $demand_sku_list[$sku] = array('prod'=>$demand_products_list[$i],'qty'=>$demand_products_list[$i]->product_quantity);
                    }else{
                        $demand_sku_list[$sku]['qty']+= $demand_products_list[$i]->product_quantity;
                    }
                }
            }else{
                $demand_products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')            
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')    
                ->leftJoin('store_products_demand as spd','spd.id','=','spdi.push_demand_id')  
                ->where('spdi.demand_id',$demand_id)        
                ->where('spdi.transfer_status',1)
                ->where('spdi.is_deleted',0)        
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)
                ->where('spdi.transfer_status',1)        
                //->where('spdi.receive_status',1)           
                ->select('ppmi.base_price','ppmi.arnon_inventory','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount',
                'ppm.id as product_id','ppm.product_name','ppm.product_sku','ppm.size_id','ppm.color_id','poi.vendor_sku','ppm.hsn_code','spd.invoice_no as push_demand_no')        
                ->get()->toArray();

                for($i=0;$i<count($demand_products_list);$i++){
                    $key = $demand_products_list[$i]->product_id; //str_replace(' ','_',strtolower($demand_products_list[$i]->product_name)).'_'.str_replace(' ','_',strtolower($demand_products_list[$i]->product_sku)).'_'.$demand_products_list[$i]->color_id.'_'.$demand_products_list[$i]->size_id;
                    if(!isset($demand_sku_list[$key])){
                        $demand_sku_list[$key] = array('prod'=>$demand_products_list[$i],'qty'=>1);
                    }else{
                        $demand_sku_list[$key]['qty']+=1;
                    }
                }
            }
            
            $size_list = Production_size_counts::where('is_deleted',0)->orderBy('id')->get()->toArray();
            $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->orderBy('name')->get()->toArray();
            
            for($i=0;$i<count($size_list);$i++){
                $sizes[$size_list[$i]['id']] = $size_list[$i]['size'];
            }
            
            for($i=0;$i<count($color_list);$i++){
                $colors[$color_list[$i]['id']] = $color_list[$i]['name'];
            }
            
            $company_data = CommonHelper::getCompanyData();
            
            $data = array('message' => 'products list','demand_sku_list' => $demand_sku_list,'demand_data' => $demand_data,'company_data'=>$company_data,'gst_name'=>$gst_name,'sizes'=>$sizes,'colors'=>$colors,'pdf_type'=>$pdf_type,'debit_credit_note_type'=>$debit_credit_note_type);
            
            //return view('store/inventory_transfer_store_demand_invoice',$data);
            
            $pdf = PDF::loadView('store/inventory_transfer_store_demand_invoice', $data);

            return $pdf->download('inventory_transfer_store_demand_invoice_pdf');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'products list','demand_products_list' => $demand_products_list,'demand_data' => $demand_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function storeCategorySalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $rec_per_page = 100;
            $inv_bal = $store_list = $category_list = array();
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            
            // Check days difference validation
            $days_diff =  CommonHelper::dateDiff($start_date, $end_date);
            
            if($days_diff > 180){
                throw new \Exception('Date difference should not be more than 180 days');
            }
           
            // Orders list by store and category start
            $orders_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pcod.order_id', '=', 'pco.id')        
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')          
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')       
            ->where('pcod.is_deleted',0)
            ->where('pco.is_deleted',0)        
            ->where('ppmi.is_deleted',0)            
            ->where('ppm.is_deleted',0)     
            ->where('pcod.fake_inventory',0)
            ->where('pco.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)       
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)         
            ->whereRaw("DATE(pco.created_at) >= '$start_date' AND DATE(pco.created_at) <= '$end_date'");     
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $orders_list = $orders_list->where('pco.store_id',$data['s_id']);
            }
            
            $orders_list = $orders_list->groupByRaw('DATE(pco.created_at),pco.store_id,ppm.category_id')        
            ->selectRaw('DATE(pco.created_at) as order_date,pco.store_id,ppm.category_id,SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price')     
            ->orderByRaw('DATE(pco.created_at) DESC');
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $orders_list = $orders_list->get()->toArray();
            }else{
                $orders_list = $orders_list->paginate($rec_per_page);
            }
            
            // Orders list by store and category end
            
            // Inventory balance start
            $inv_balance = Store_inventory_balance::where('inv_date','>=',$start_date)
            ->where('inv_date','<=',$end_date)
            ->where('record_type',2)
            ->where('store_id','>',0)        
            ->where('is_deleted',0);
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $inv_balance = $inv_balance->where('store_id',$data['s_id']);
            }
            
            $inv_balance = $inv_balance->select('inv_date','store_id','category_id','bal_qty','bal_value')        
            ->get()->toArray();
            
            for($i=0;$i<count($inv_balance);$i++){
                $key = strtotime($inv_balance[$i]['inv_date']).'_'.$inv_balance[$i]['store_id'].'_'.$inv_balance[$i]['category_id'];
                $inv_bal[$key] = $inv_balance[$i];
            }
            
            // Inventory balance end
            
            // Orders total data start
            $orders_list_total = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pcod.order_id', '=', 'pco.id')        
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')          
            ->where('pcod.is_deleted',0)
            ->where('pco.is_deleted',0)        
            ->where('ppmi.is_deleted',0)   
            ->where('pcod.fake_inventory',0)
            ->where('pco.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)     
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)         
            ->whereRaw("DATE(pco.created_at) >= '$start_date' AND DATE(pco.created_at) <= '$end_date'");     
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $orders_list_total = $orders_list_total->where('pco.store_id',$data['s_id']);
            }
            
            $orders_list_total = $orders_list_total        
            ->selectRaw('SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price')     
            ->first();
            
            // Orders total data end
            
            // Category and stores list
            $stores = CommonHelper::getStoresList();
            $category = Design_lookup_items_master::where('type','POS_PRODUCT_CATEGORY')->get()->toArray();
            
            for($i=0;$i<count($stores);$i++){
                $store_list[$stores[$i]['id']] = $stores[$i];
            }
            
            for($i=0;$i<count($category);$i++){
                $category_list[$category[$i]['id']] = $category[$i];
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_category_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Date','Store Name','Store Code','Category','Sale Qty','Sale NET Price','Bal Qty','Bal MRP Value');

                $callback = function() use ($orders_list,$store_list,$category_list,$orders_list_total,$inv_bal,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total = array('qty'=>0,'net_price'=>0);
                    for($i=0;$i<count($orders_list);$i++){
                        $key = strtotime($orders_list[$i]->order_date).'_'.$orders_list[$i]->store_id.'_'.$orders_list[$i]->category_id;
                        $bal_qty = isset($inv_bal[$key]['bal_qty'])?$inv_bal[$key]['bal_qty']:'';
                        $bal_value = isset($inv_bal[$key]['bal_value'])?$inv_bal[$key]['bal_value']:'';
                        
                        $array = array(date('d-m-Y',strtotime($orders_list[$i]->order_date)),$store_list[$orders_list[$i]->store_id]['store_name'],$store_list[$orders_list[$i]->store_id]['store_id_code'],$category_list[$orders_list[$i]->category_id]['name'],$orders_list[$i]->prod_count,
                        round($orders_list[$i]->prod_net_price,2),$bal_qty,$bal_value);
                        fputcsv($file, $array);
                        
                        $total['qty']+=$orders_list[$i]->prod_count;
                        $total['net_price']+=$orders_list[$i]->prod_net_price;
                    }
                    
                    $array = array('Total','','','',$total['qty'],round($total['net_price'],2));
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('store/report_store_category_sales',array('orders_list'=>$orders_list,'store_list'=>$store_list,'error_message'=>$error_message,'user'=>$user,'category_list'=>$category_list,'orders_list_total'=>$orders_list_total,'inv_bal'=>$inv_bal));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_CATEGORY_SALES_REPORT',__FUNCTION__,__FILE__);
            return view('store/report_store_category_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function storeCategoryStaffSalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $rec_per_page = 100;
            $inv_bal = $store_list = $category_list = $staff_list = array();
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            
            // Check days difference validation
            $days_diff =  CommonHelper::dateDiff($start_date, $end_date);
            
            if($days_diff > 180){
                throw new \Exception('Date difference should not be more than 180 days');
            }
           
            // Orders list by store, staff and category start
            $orders_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pcod.order_id', '=', 'pco.id')        
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')          
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')       
            ->where('pcod.is_deleted',0)
            ->where('pco.is_deleted',0)        
            ->where('ppmi.is_deleted',0)            
            ->where('ppm.is_deleted',0)    
            ->where('pcod.fake_inventory',0)
            ->where('pco.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)       
            ->where('ppm.fake_inventory',0)         
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)         
            ->whereRaw("DATE(pco.created_at) >= '$start_date' AND DATE(pco.created_at) <= '$end_date'");     
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $orders_list = $orders_list->where('pco.store_id',$data['s_id']);
            }
            
            $orders_list = $orders_list->groupByRaw('DATE(pco.created_at),pco.store_id,ppm.category_id,pcod.staff_id')        
            ->selectRaw('DATE(pco.created_at) as order_date,pco.store_id,ppm.category_id,SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price,pcod.staff_id')     
            ->orderByRaw('DATE(pco.created_at) DESC');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $orders_list = $orders_list->get()->toArray();
            }else{
                $orders_list = $orders_list->paginate($rec_per_page);
            }
            
            // Orders list by store, staff and category end
            
            // Inventory balance start
            
            $inv_balance = Store_inventory_balance::where('inv_date','>=',$start_date)
            ->where('inv_date','<=',$end_date)
            ->where('record_type',2)
            ->where('store_id','>',0)        
            ->where('is_deleted',0);
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $inv_balance = $inv_balance->where('store_id',$data['s_id']);
            }
            
            $inv_balance = $inv_balance->select('inv_date','store_id','category_id','bal_qty','bal_value')        
            ->get()->toArray();
            
            for($i=0;$i<count($inv_balance);$i++){
                $key = strtotime($inv_balance[$i]['inv_date']).'_'.$inv_balance[$i]['store_id'].'_'.$inv_balance[$i]['category_id'];
                $inv_bal[$key] = $inv_balance[$i];
            }
            
            // Inventory balance end
            
            // Orders total data start
            $orders_list_total = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pcod.order_id', '=', 'pco.id')        
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')          
            ->where('pcod.is_deleted',0)
            ->where('pco.is_deleted',0)        
            ->where('ppmi.is_deleted',0)   
            ->where('pcod.fake_inventory',0)
            ->where('pco.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)        
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)         
            ->whereRaw("DATE(pco.created_at) >= '$start_date' AND DATE(pco.created_at) <= '$end_date'");     
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $orders_list_total = $orders_list_total->where('pco.store_id',$data['s_id']);
            }
            
            $orders_list_total = $orders_list_total        
            ->selectRaw('SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price')     
            ->first();
            
            // Orders total data end
            
            // Category, Stores, Staff list
            $stores = CommonHelper::getStoresList();
            $category = Design_lookup_items_master::where('type','POS_PRODUCT_CATEGORY')->get()->toArray();
            $staff = Store_staff::where('is_deleted',0)->get()->toArray();
            
            for($i=0;$i<count($stores);$i++){
                $store_list[$stores[$i]['id']] = $stores[$i];
            }
            
            for($i=0;$i<count($category);$i++){
                $category_list[$category[$i]['id']] = $category[$i];
            }
            
            for($i=0;$i<count($staff);$i++){
                $staff_list[$staff[$i]['id']] = $staff[$i];
            }
            
            // CSV download
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_category_staff_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Date','Store Name','Store Code','Category','Staff','Sale Qty','Sale NET Price','Bal Qty','Bal MRP Value');

                $callback = function() use ($orders_list,$store_list,$category_list,$orders_list_total,$inv_bal,$staff_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total = array('qty'=>0,'net_price'=>0);
                    for($i=0;$i<count($orders_list);$i++){
                        $key = strtotime($orders_list[$i]->order_date).'_'.$orders_list[$i]->store_id.'_'.$orders_list[$i]->category_id;
                        $bal_qty = isset($inv_bal[$key]['bal_qty'])?$inv_bal[$key]['bal_qty']:'';
                        $bal_value = isset($inv_bal[$key]['bal_value'])?$inv_bal[$key]['bal_value']:'';
                        $staff_name = isset($staff_list[$orders_list[$i]->staff_id]['name'])?$staff_list[$orders_list[$i]->staff_id]['name']:'';
                        
                        $array = array(date('d-m-Y',strtotime($orders_list[$i]->order_date)),$store_list[$orders_list[$i]->store_id]['store_name'],$store_list[$orders_list[$i]->store_id]['store_id_code'],$category_list[$orders_list[$i]->category_id]['name'],$staff_name,$orders_list[$i]->prod_count,
                        round($orders_list[$i]->prod_net_price,2),$bal_qty,$bal_value);
                        fputcsv($file, $array);
                        
                        $total['qty']+=$orders_list[$i]->prod_count;
                        $total['net_price']+=$orders_list[$i]->prod_net_price;
                    }
                    
                    $array = array('Total','','','','',$total['qty'],round($total['net_price'],2));
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('store/report_store_category_staff_sales',array('orders_list'=>$orders_list,'store_list'=>$store_list,'error_message'=>$error_message,'user'=>$user,'category_list'=>$category_list,'orders_list_total'=>$orders_list_total,'inv_bal'=>$inv_bal,'staff_list'=>$staff_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_CATEGORY_SALES_STAFF_REPORT',__FUNCTION__,__FILE__);
            return view('store/report_store_category_staff_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function storeBagsInventoryList(Request $request){
        try{
            $data = $request->all();
            
            if(isset($data['action']) &&  $data['action'] == 'get_store_bags_data'){
                $id = trim($data['id']);
                $store_bags_data = Store_bags_inventory::where('id', '=', $id)->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store bags data','store_bags_data'=>$store_bags_data),200);
            }
            
            $inv_list = \DB::table('store_bags_inventory as sb')
            ->join('store as s','s.id', '=', 'sb.store_id')
            ->where('sb.is_deleted',0)
            ->select('sb.*','s.store_name');
            
            if(isset($data['store_id']) && !empty($data['store_id'])){
                $inv_list = $inv_list->where('s.id',$data['store_id']);
            }
            
            if(isset($data['id']) && !empty($data['id'])){
                $inv_list = $inv_list->where('sb.id',$data['id']);
            }
            
            $inv_list = $inv_list->paginate(100);
            
            $store_list = CommonHelper::getStoresList();
            return view('store/store_bags_inventory_list',array('store_list'=>$store_list,'inv_list'=>$inv_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('store/store_bags_inventory_list',array('error_message'=>$e->getMessage(),'store_list'=>array()));
        }
    }
    
    public function addStoreBags(Request $request){
        try{
            $data = $request->all();
            
            $validationRules = array('store_id_add'=>'required','bags_count_add'=>'required','date_assigned_add'=>'required');
            $attributes = array('store_id_add'=>'Store Name','bags_count_add'=>'Bags Count','date_assigned_add'=>'Date Assigned');
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            \DB::beginTransaction();
             
            $date = CommonHelper::convertUserDateToDBDate($data['date_assigned_add']);
            
            $insertArray = array('store_id'=>$data['store_id_add'],'bags_assigned'=>$data['bags_count_add'],'date_assigned'=>$date);
            
            $store_bag = Store_bags_inventory::create($insertArray);
            
            Store::where('id',$data['store_id_add'])->increment('bags_inventory',$data['bags_count_add']);
            
            \DB::commit();
            
            CommonHelper::createLog('New Bags assigned to Store, ID: '.$store_bag->id,'STORE_BAGS','STORE');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store bags added successfully'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateStoreBags(Request $request){
        try{
            
            $data = $request->all();
            
            $validationRules = array('store_id_edit'=>'required','bags_count_edit'=>'required','date_assigned_edit'=>'required');
            $attributes = array('store_id_edit'=>'Store Name','bags_count_edit'=>'Bags Count','date_assigned_edit'=>'Date Assigned');
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            \DB::beginTransaction();
            
            $store_bags_data = Store_bags_inventory::where('id', '=', $data['store_bags_edit_id'])->first();
            
            $date = CommonHelper::convertUserDateToDBDate($data['date_assigned_edit']);
            
            $updateArray = array('store_id'=>$data['store_id_edit'],'bags_assigned'=>$data['bags_count_edit'],'date_assigned'=>$date);
            
            $store_bag = Store_bags_inventory::where('id',$data['store_bags_edit_id'])->update($updateArray);
            
            Store::where('id',$data['store_id_edit'])->decrement('bags_inventory',$store_bags_data->bags_assigned);
            
            Store::where('id',$data['store_id_edit'])->increment('bags_inventory',$data['bags_count_edit']);
            
            \DB::commit();
            
            CommonHelper::createLog('Bags assigned to Store Updated, ID: '.$data['store_bags_edit_id'],'STORE_BAGS','STORE');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store bags updated successfully'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
}