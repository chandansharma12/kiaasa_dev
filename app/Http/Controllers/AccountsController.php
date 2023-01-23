<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Design_lookup_items_master;
use App\Models\Store_expense_master_values;
use App\Models\Store_expense_monthly_values;
use App\Models\Pages_description;
use App\Models\Production_size_counts;
use App\Models\Story_master;
use App\Models\Store_sku_inventory_balance;
use App\Models\Pos_product_master_inventory;
use App\Models\Store_report_types;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class AccountsController extends Controller
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
    
    function dashboard(Request $request){
        try{ 
            return view('accounts/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'ACCOUNTS',__FUNCTION__,__FILE__);
            return view('accounts/dashboard',array('error_message' =>$e->getMessage()));
        }
    }
    
    function assetOrderList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            
            $whereArray = array('so.is_deleted'=>0);
            $orders_list_array =  CommonHelper::getStoresAssetsOrdersList($whereArray);
            
            return view('accounts/asset_order_list',$orders_list_array);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'ACCOUNTS',__FUNCTION__,__FILE__);
            return view('accounts/asset_order_list',array('error_message'=>$e->getMessage(),'orders_list'=>array()));
        }
    }
    
    function assetsOrderItemsList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            $asset_orders_items_list_array = CommonHelper::getAssetsOrderItemsList();
            
            $stores_list = Store::where('is_deleted',0)->get()->toArray();
            $category_list = Design_lookup_items_master::where('type','STORE_ASSET_CATEGORY')->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            $asset_orders_items_list_array['stores_list'] = $stores_list;
            $asset_orders_items_list_array['category_list'] = $category_list;
            
            return view('accounts/asset_order_items_list',$asset_orders_items_list_array);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'ACCOUNTS',__FUNCTION__,__FILE__);
            return view('accounts/asset_order_items_list',array('error_message'=>$e->getMessage(),'store_asset_orders_items_list'=>array()));
        }
    }
    
    function pageDescriptionList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            if(isset($data['action']) && $data['action']  == 'add_page_desc'){
                $validationRules = array('page_name_add'=>'required','desc_type_add'=>'required','desc_name_add'=>'required','desc_detail_add'=>'required');
                $attributes = array('page_name_add'=>'Page Name','desc_type_add'=>'Type','desc_name_add'=>'Name','desc_detail_add'=>'Description');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $insertArray = array('page_name'=>trim($data['page_name_add']),'desc_type'=>trim($data['desc_type_add']),'desc_name'=>trim($data['desc_name_add']),'desc_detail'=>trim($data['desc_detail_add']));
                Pages_description::create($insertArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Description added successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'edit_page_desc'){
                $id = trim($data['id']);
                $validationRules = array('page_name_edit'=>'required','desc_type_edit'=>'required','desc_name_edit'=>'required','desc_detail_edit'=>'required');
                $attributes = array('page_name_edit'=>'Page Name','desc_type_edit'=>'Type','desc_name_edit'=>'Name','desc_detail_edit'=>'Description');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $updateArray = array('page_name'=>trim($data['page_name_edit']),'desc_type'=>trim($data['desc_type_edit']),'desc_name'=>trim($data['desc_name_edit']),'desc_detail'=>trim($data['desc_detail_edit']));
                Pages_description::where('id',$id)->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Description updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_page_desc_list'){
                $page_name = trim($data['page_name']);
                
                $page_desc = \DB::table('pages_description as pd')
                ->where('pd.page_name',$page_name)->where('pd.is_deleted',0)
                ->select('pd.*')->orderByRaw('pd.sort_order,pd.id')->get()->toArray();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Page Description','page_desc'=>$page_desc),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_page_desc'){
                $id = trim($data['id']);
                $desc_data = Pages_description::where('id',$id)->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Page Description','desc_data'=>$desc_data),200);
            }
            
            $page_desc_list = Pages_description::where('is_deleted',0);
            if(isset($data['page_name']) && !empty($data['page_name'])){
                $page_desc_list = $page_desc_list->where('page_name',trim($data['page_name']));
            }
            
            $page_desc_list = $page_desc_list->paginate(100);
            
            $pages_name_list = Pages_description::where('is_deleted',0)->select('page_name')->distinct()->orderBy('page_name')->get()->toArray();
            
            return view('accounts/page_description_list',array('error_message'=>$error_msg,'page_desc_list'=>$page_desc_list,'pages_name_list'=>$pages_name_list));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'ACCOUNTS',__FUNCTION__,__FILE__);
            return view('accounts/page_description_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function storeExpenseList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            $store_expenses_list = \DB::table('store_expense_list as se')
            ->where('se.is_deleted',0)  
            ->select('se.*')        
            ->get()->toArray();
            
            //$store_list = Store::where('is_deleted',0)->select('id','store_name','store_code')->orderBy('store_name')->get()->toArray();
            
            return view('accounts/store_expense_list',array('error_message'=>$error_msg,'store_expenses_list'=>$store_expenses_list));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return view('accounts/store_expense_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function storeExpenseMasterList(Request $request,$store_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            $store_data = Store::where('id',$store_id)->select('id','store_name','store_id_code')->first();
            
            $store_expense_master_values = \DB::table('store_expense_list as se')
            ->leftJoin('store_expense_master_values as semv',function($join) use($store_id) {$join->on('se.id','=','semv.expense_id')->where('semv.store_id','=',$store_id)->where('semv.is_deleted','=',0);})               
            ->where('se.expense_type','master')        
            ->where('se.is_deleted',0)  
            ->select('se.id as expense_id','se.expense_name','se.expense_type','se.expense_category','semv.expense_value')        
            ->get()->toArray();
            
            return view('accounts/store_expense_master_list',array('error_message'=>$error_msg,'store_expense_master_values'=>$store_expense_master_values,'store_data'=>$store_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return view('accounts/store_expense_master_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function editStoreExpenseMaster(Request $request,$store_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            $store_data = Store::where('id',$store_id)->select('id','store_name','store_id_code')->first();
            
            $store_expense_master_values = \DB::table('store_expense_list as se')
            ->leftJoin('store_expense_master_values as semv',function($join) use($store_id) {$join->on('se.id','=','semv.expense_id')->where('semv.store_id','=',$store_id)->where('semv.is_deleted','=',0);})               
            ->where('se.expense_type','master')        
            ->where('se.is_deleted',0)  
            ->select('se.id as expense_id','se.expense_name','se.expense_type','se.expense_category','semv.expense_value')        
            ->get()->toArray();
            
            return view('accounts/store_expense_master_edit',array('error_message'=>$error_msg,'store_expense_master_values'=>$store_expense_master_values,'store_data'=>$store_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return view('accounts/store_expense_master_edit',array('error_message'=>$e->getMessage()));
        }
    }
    
    function updateStoreExpenseMaster(Request $request,$store_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            $store_data = Store::where('id',$store_id)->select('id','store_name','store_id_code')->first();
            
            $store_expense_master_values = \DB::table('store_expense_list as se')
            ->leftJoin('store_expense_master_values as semv',function($join) use($store_id) {$join->on('se.id','=','semv.expense_id')->where('semv.store_id','=',$store_id)->where('semv.is_deleted','=',0);})               
            ->where('se.expense_type','master')        
            ->where('se.is_deleted',0)  
            ->select('se.id as expense_id','se.expense_name','se.expense_type','se.expense_category','semv.expense_value')        
            ->get()->toArray();
            
            $validationRules = $attributes = array();
            for($i=0;$i<count($store_expense_master_values);$i++){
                $expense_id = $store_expense_master_values[$i]->expense_id;
                $validationRules['expense_'.$expense_id] = 'required|numeric';
                $attributes['expense_'.$expense_id] = $store_expense_master_values[$i]->expense_name;
                
            }
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            \DB::beginTransaction();
            
            for($i=0;$i<count($store_expense_master_values);$i++){
                $expense_id = $store_expense_master_values[$i]->expense_id;
                if(empty($store_expense_master_values[$i]->expense_value)){
                    $insertArray = ['store_id'=>$store_id,'expense_id'=>$expense_id,'expense_value'=>trim($data['expense_'.$expense_id])];
                    Store_expense_master_values::create($insertArray);
                }else{
                    Store_expense_master_values::where('store_id',$store_id)->where('expense_id',$expense_id)->update(['expense_value'=>trim($data['expense_'.$expense_id])]);
                }
            }
            
            $store_expense_master_values = Store_expense_master_values::where('store_id',$store_id)->where('is_deleted',0)->get();
            
            $dates_list = $this->getDatesList();
            for($q=0;$q<count($dates_list);$q++){
                $expense_date = $dates_list[$q];
                $month_data = Store_expense_monthly_values::where('store_id',$store_id)->where('expense_date',$expense_date)->where('is_deleted',0)->select('id')->first();
                if(empty($month_data)){
                    for($i=0;$i<count($store_expense_master_values);$i++){
                        $insertArray = ['store_id'=>$store_id,'expense_id'=>$store_expense_master_values[$i]->expense_id,'expense_date'=>$expense_date,'expense_value'=>$store_expense_master_values[$i]->expense_value];
                        Store_expense_monthly_values::create($insertArray);
                    }
                }
            }
            
            \DB::commit();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Store master data updated successfully'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function storeExpenseMonthlyList(Request $request,$store_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            $user_stores_ids = $user_perm_ids = $expense_dates = [];
            $date = date('Y/m/01');
            $expense_date = (isset($data['expense_date']) && !empty($data['expense_date']))?trim(str_replace('-','/',$data['expense_date'])):$date;
            
            $store_data = Store::where('id',$store_id)->select('id','store_name','store_id_code')->first();
            $store_list = Store::where('is_deleted',0)->select('id','store_name','store_id_code');
            
            // For store user, display only user stores in dropdown
            if($user->user_type == 9){
                $user_stores = \DB::table('store_users as su')->where('user_id',$user->id)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($user_stores);$i++){
                    $user_stores_ids[] = $user_stores[$i]->store_id;
                }
                
                $store_list = $store_list->wherein('id',$user_stores_ids);
            }
            
            $store_list = $store_list->orderBy('store_name')->get()->toArray();
            
            $store_expense_monthly_values = \DB::table('store_expense_list as se')
            ->join('store_expense_monthy_values as semv','se.id', '=', 'semv.expense_id')        
            ->join('store as s','s.id', '=', 'semv.store_id')    
            ->where('semv.store_id',$store_id)        
            ->where('semv.expense_date',$expense_date);         
            
            // For Store and HR user, display expenses for which it has permission
            if($user->user_type == 9 || $user->user_type == 17){
                $user_perm = \DB::table('store_expense_users')->where('role_id',$user->user_type)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($user_perm);$i++){
                    $user_perm_ids[] = $user_perm[$i]->expense_id;
                }
                
                $store_expense_monthly_values = $store_expense_monthly_values->wherein('se.id',$user_perm_ids)->where('se.expense_type','per_month');        
            }
            
            $store_expense_monthly_values = $store_expense_monthly_values->where('se.is_deleted',0)                 
            ->where('semv.is_deleted',0)          
            ->select('se.id as expense_id','se.expense_name','se.expense_type','se.expense_category','semv.store_id','semv.expense_date','semv.expense_value','semv.created_at','s.store_name','s.store_id_code')        
            ->get()->toArray();
            
            $expense_dates = array_reverse($this->getDatesList());
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_expense_monthly_values_'.$store_id.'.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Store','Month','Expense Name','Expense Value','Expense Category');

                $callback = function() use ($store_expense_monthly_values, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    for($i=0;$i<count($store_expense_monthly_values);$i++){
                        $array = array($store_expense_monthly_values[$i]->store_name.' ('.$store_expense_monthly_values[$i]->store_id_code.')',date('F Y',strtotime($store_expense_monthly_values[$i]->expense_date)),$store_expense_monthly_values[$i]->expense_name,
                        $store_expense_monthly_values[$i]->expense_value,$store_expense_monthly_values[$i]->expense_category);

                        fputcsv($file, $array);
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('accounts/store_expense_monthly_list',array('error_message'=>$error_msg,'store_expense_monthly_values'=>$store_expense_monthly_values,'store_list'=>$store_list,
            'user'=>$user,'expense_dates'=>$expense_dates,'expense_date'=>$expense_date,'date'=>$date,'store_id'=>$store_id,'store_data'=>$store_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return view('accounts/store_expense_monthly_list',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function editStoreExpenseMonthly(Request $request,$store_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $user_perm_ids = $user_stores_ids = $store_expense_monthly_values = array();
            $error_msg = '';
            $date = date('Y/m/01');
            $expense_date = (isset($data['expense_date']) && !empty($data['expense_date']))?trim(str_replace('-','/',$data['expense_date'])):$date;
            
            $store_data = Store::where('id',$store_id)->select('id','store_name','store_id_code')->first();
            
            // For Store userm=, check if store exists in its account
            if($user->user_type == 9){
                $user_stores = \DB::table('store_users as su')->where('user_id',$user->id)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($user_stores);$i++){
                    $user_stores_ids[] = $user_stores[$i]->store_id;
                }
                
                if(!in_array($store_id, $user_stores_ids)){
                    $error_msg = 'Store does not exists in your account';
                }
            }
            
            // Check permissions
            $user_perm = \DB::table('store_expense_users')->where('role_id',$user->user_type)->where('is_deleted','=',0)->get()->toArray();
            for($i=0;$i<count($user_perm);$i++){
                $user_perm_ids[] = $user_perm[$i]->expense_id;
            }
            
            if(empty($user_perm_ids)){
                $error_msg = 'You do not have permissions to edit monthly data';
            }
            
            if($user->user_type != 1 && strtotime($expense_date) != strtotime($date)){
                $error_msg = 'You have permissions to edit only current month data';
            }
            
            if(empty($error_msg)){
                $store_expense_monthly_values = \DB::table('store_expense_list as se')
                ->leftJoin('store_expense_monthy_values as semv',function($join) use($store_id,$expense_date) {$join->on('se.id','=','semv.expense_id')->where('semv.store_id','=',$store_id)->where('semv.expense_date','=',$expense_date)->where('semv.is_deleted','=',0);});               
                
                if($user->user_type != 1){
                    $store_expense_monthly_values = $store_expense_monthly_values->where('se.expense_type','per_month');
                }
                
                $store_expense_monthly_values = $store_expense_monthly_values->wherein('se.id',$user_perm_ids)        
                ->where('se.is_deleted',0)  
                ->select('se.id as expense_id','se.expense_name','se.expense_type','se.expense_category','semv.expense_value')        
                ->get()->toArray();
            }
            
            return view('accounts/store_expense_monthly_edit',array('error_message'=>$error_msg,'store_expense_monthly_values'=>$store_expense_monthly_values,'store_data'=>$store_data,'expense_date'=>$expense_date));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return view('accounts/store_expense_monthly_edit',array('error_message'=>$e->getMessage()));
        }
    }
    
    function updateStoreExpenseMonthly(Request $request,$store_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $user_perm_ids = $monthly_fields = [];
            $error_msg = '';
            $date = date('Y/m/01');
            $expense_date = (isset($data['expense_date']) && !empty($data['expense_date']))?trim(str_replace('-','/',$data['expense_date'])):$date;
            
            $store_data = Store::where('id',$store_id)->select('id','store_name','store_id_code')->first();
            
            \DB::beginTransaction();
            
            /* Store expense add master values code start */
            
            $store_expense_master_values = \DB::table('store_expense_list as se')
            ->leftJoin('store_expense_master_values as semv',function($join) use($store_id) {$join->on('se.id','=','semv.expense_id')->where('semv.store_id','=',$store_id)->where('semv.is_deleted','=',0);})               
            ->where('se.expense_type','master')        
            ->where('se.is_deleted',0)  
            ->select('se.id as expense_id','se.expense_name','se.expense_type','se.expense_category','semv.expense_value')        
            ->get()->toArray();
            
            for($i=0;$i<count($store_expense_master_values);$i++){
                $expense_name = $store_expense_master_values[$i]->expense_name;
                if(empty($store_expense_master_values[$i]->expense_value)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store Expenses Master values not added', 'errors' => 'Store Expenses Master values not added'));
                }
            }
            
            $store_expense =  Store_expense_monthly_values::where('store_id',$store_id)->where('expense_date',$expense_date)->where('is_deleted',0)->first();
            if(empty($store_expense)){
                for($i=0;$i<count($store_expense_master_values);$i++){
                    $insertArray = ['store_id'=>$store_id,'expense_id'=>$store_expense_master_values[$i]->expense_id,'expense_date'=>$expense_date,'expense_value'=>$store_expense_master_values[$i]->expense_value];
                    Store_expense_monthly_values::create($insertArray);
                }
            }
            
            /* Store expense add master values code end */
            
            $store_expense_monthly_fields = \DB::table('store_expense_list as se')->where('is_deleted',0)->where('expense_type','per_month')->select('id')->get()->toArray();
            for($i=0;$i<count($store_expense_monthly_fields);$i++){
                $monthly_fields[] = $store_expense_monthly_fields[$i]->id;
            }
            
            $user_perm = \DB::table('store_expense_users')->where('role_id',$user->user_type)->where('is_deleted','=',0)->get()->toArray();
            for($i=0;$i<count($user_perm);$i++){
                $user_perm_ids[] = $user_perm[$i]->expense_id;
            }
            
            if(empty($user_perm_ids)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'You do not have permissions to edit monthly data', 'errors' => 'You do not have permissions to edit monthly data'));
            }
            
            $store_expense_monthly_values = \DB::table('store_expense_list as se')
            ->leftJoin('store_expense_monthy_values as semv',function($join) use($store_id,$expense_date) {$join->on('se.id','=','semv.expense_id')->where('semv.store_id','=',$store_id)->where('semv.expense_date','=',$expense_date)->where('semv.is_deleted','=',0);})               
            ->wherein('se.id',$user_perm_ids);
            
            if($user->user_type != 1){
                $store_expense_monthly_values = $store_expense_monthly_values->where('se.expense_type','per_month');
            }        
            
            $store_expense_monthly_values = $store_expense_monthly_values->where('se.is_deleted',0)  
            ->select('se.id as expense_id','se.expense_name','se.expense_type','se.expense_category','semv.expense_value')        
            ->get()->toArray();
            
            // Validations 
            $validationRules = $attributes = array();
            for($i=0;$i<count($store_expense_monthly_values);$i++){
                $expense_id = $store_expense_monthly_values[$i]->expense_id;
                $validationRules['expense_'.$expense_id] = 'required|numeric';
                $attributes['expense_'.$expense_id] = $store_expense_monthly_values[$i]->expense_name;
            }
            
            if($user->user_type == 1){
                for($i=0;$i<count($monthly_fields);$i++){
                    $key = 'expense_'.$monthly_fields[$i];
                    unset($validationRules[$key]);
                }
            }
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Add/Edit monthly values
            for($i=0;$i<count($store_expense_monthly_values);$i++){
                $expense_id = $store_expense_monthly_values[$i]->expense_id;
                if(empty($store_expense_monthly_values[$i]->expense_value)){
                    $insertArray = ['store_id'=>$store_id,'expense_id'=>$expense_id,'expense_date'=>$expense_date,'expense_value'=>trim($data['expense_'.$expense_id])];
                    Store_expense_monthly_values::create($insertArray);
                }else{
                    Store_expense_monthly_values::where('store_id',$store_id)->where('expense_id',$expense_id)->where('expense_date',$expense_date)->update(['expense_value'=>trim($data['expense_'.$expense_id])]);
                }
            }
            
            \DB::commit();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Store monthly data updated successfully'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function profitLossReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $store_orders = $expense_values = $month_years = $expense_dates =  $invalid_stores = $store_topline = $store_oc = $month_search_days = array();
            $total_data = array('top_line_pcs'=>0,'top_line_turn_over'=>0,'top_line_cogs'=>0,'oc_rent'=>0,'oc_cam'=>0,'oc_gst_val'=>0,'oc_tds_val'=>0,'oc_total_val'=>0,
            'mc_comm_val'=>0,'mc_gst_val'=>0,'mc_tds_val'=>0,'mc_total_val'=>0,'salary'=>0,'sh_imprest'=>0,'sh_bank_charges_val'=>0,'sh_electricity'=>0,'sh_others'=>0,'total_expenditure'=>0,'ebitda'=>0);
            $error_msg = '';
            
            $search_date = CommonHelper::getSearchStartEndDate($data,true);
            $start_date = $search_date['start_date'];
            $end_date = $search_date['end_date'];
            
            $st_dt = date('Y/n/d',strtotime($start_date));
            $end_dt = date('Y/n/d',strtotime($end_date));
            
            while($st_dt <= $end_dt){
                $month = date('n',strtotime($st_dt));
                $year = date('Y',strtotime($st_dt));
                $month_year = $month.'_'.$year;
                if(!in_array($month_year, $month_years)){
                    $month_years[] = $month_year;
                    $expense_dates[] = $year.'/'.$month.'/1';
                }
                
                if(isset($month_search_days[$month_year])){
                    $month_search_days[$month_year]+=1;
                }else{
                    $month_search_days[$month_year] = 1;
                }
                
                $st_dt = date('Y/n/d',strtotime('+1 day',strtotime($st_dt)));
            }
            
            
            $store_list = Store::where('is_deleted',0)->select('id','store_name','store_id_code')->get()->toArray();
            
            // Stores monthly expense saved data is retreieved and saved in base array $expense_values
            $expense_monthly_values = Store_expense_monthly_values::wherein('expense_date',$expense_dates)->where('is_deleted',0)->select('store_id','expense_id','expense_date','expense_value')->get();
            for($i=0;$i<count($expense_monthly_values);$i++){
                $store_id = $expense_monthly_values[$i]->store_id;
                $expense_id = $expense_monthly_values[$i]->expense_id;
                $month_year = date('n_Y',strtotime($expense_monthly_values[$i]->expense_date));
                $key = $store_id.'_'.$expense_id.'_'.$month_year;
                $expense_values[$key] = $expense_monthly_values[$i]->expense_value;
            }
            
            for($i=0;$i<count($store_list);$i++){
                $store_id = $store_list[$i]['id'];
                $store_topline[$store_id] = ['pcs'=>0,'turn_over'=>0,'cogs'=>0];
                $store_oc[$store_id] = ['rent'=>0,'cam'=>0,'gst_oc'=>0,'tds_oc'=>0,'gst_val_oc'=>0,'tds_val_oc'=>0,'total_val_oc'=>0];
                $store_mc[$store_id] = ['comm'=>0,'gst'=>0,'tds'=>0,'gst_val'=>0,'tds_val'=>0,'total_val'=>0,'salary'=>0,'comm_val'=>0];
                $store_sh[$store_id] = ['imprest'=>0,'bank_charges'=>0,'electricity'=>0,'others'=>0,'bank_charges_val'=>0];
            }    
            
            /* Top Line Code Start.  Monthly data is retrieved as data is saved on monthly basis and is different for months. */
            /* Monthly basis data is retreived and added instead of total data.  */
            $store_orders_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id') 
            ->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'")    
            ->where('pcod.store_id','>',0)        
            ->where('pco.is_deleted',0)        
            ->where('pcod.is_deleted',0)
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)        
            ->where('pco.fake_inventory',0)     
            ->groupByRaw('pcod.store_id,MONTH(pcod.created_at),YEAR(pcod.created_at)')        
            ->selectRaw('pcod.store_id,MONTH(pcod.created_at) as order_month,YEAR(pcod.created_at) as order_year,SUM(pcod.product_quantity) as inv_count,SUM(pcod.net_price) as total_net_price')
            ->orderBy('pco.store_id')
            ->get();
            
            for($i=0;$i<count($store_orders_list);$i++){
                $store_id = $store_orders_list[$i]->store_id;
                $expense_id = 1;
                $month_year = ltrim($store_orders_list[$i]->order_month,0).'_'.$store_orders_list[$i]->order_year;
                $key = $store_id.'_'.$expense_id.'_'.$month_year;
                
                $store_topline[$store_id]['pcs']+=$store_orders_list[$i]->inv_count;
                $store_topline[$store_id]['turn_over']+=$store_orders_list[$i]->total_net_price;
                    
                //if(isset($expense_values[$key]) && !in_array($store_id, $invalid_stores)){
                if(isset($expense_values[$key])){    
                    $expense_value = $expense_values[$key];
                    $store_topline[$store_id]['cogs']+=$store_orders_list[$i]->total_net_price*($expense_value/100);
                }else{
                    //$invalid_stores[] = $store_id;
                    //$store_topline[$store_id]['pcs'] = $store_oc[$store_id]['turn_over'] = $store_oc[$store_id]['cogs'] = 0;
                }
            }
            
            /* Top Line Code End */
            
            /* Bank Charges Code Start */
            //\DB::enableQueryLog();
            $store_payment_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id') 
            ->join('pos_customer_orders_payments as pcop','pco.id', '=', 'pcop.order_id')        
            ->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'")        
            ->wherein('pcop.payment_method',['card','e-wallet'])           
            ->where('pco.is_deleted',0)        
            ->where('pcod.is_deleted',0)
            ->where('pcop.is_deleted',0)        
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)        
            ->where('pcop.order_status',1)               
            ->where('pco.fake_inventory',0)     
            ->groupByRaw('pcod.store_id,MONTH(pcod.created_at),YEAR(pcod.created_at)')        
            ->selectRaw('pcod.store_id,MONTH(pcod.created_at) as order_month,YEAR(pcod.created_at) as order_year,SUM(pcop.payment_received) as total_payment_received')
            ->get();
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            
            for($i=0;$i<count($store_payment_list);$i++){
                $store_id = $store_payment_list[$i]->store_id;
                $expense_id = 11;
                $month_year = ltrim($store_payment_list[$i]->order_month,0).'_'.$store_payment_list[$i]->order_year;
                $key = $store_id.'_'.$expense_id.'_'.$month_year;
                
                if(isset($expense_values[$key])){    
                    $bank_charges = $expense_values[$key];
                    $store_sh[$store_id]['bank_charges_val']+=$store_payment_list[$i]->total_payment_received*($bank_charges/100);
                }
            }
            
            /* Bank Charges Code End */
            
            $invalid_stores = [];
            
            for($i=0;$i<count($store_list);$i++){
                $store_id = $store_list[$i]['id'];
                for($q=0;$q<count($month_years);$q++){
                    $month_year = $month_years[$q];
                    $month_year_arr = explode('_',$month_year);
                    $month_days = cal_days_in_month(CAL_GREGORIAN,$month_year_arr[0],$month_year_arr[1]);
                    $search_days = $month_search_days[$month_year];
                    
                    /* Occupancy Cost Code Start */
                    $rent_key = $store_id.'_2_'.$month_year;
                    $cam_key = $store_id.'_3_'.$month_year;
                    $gst_key = $store_id.'_4_'.$month_year;
                    $tds_key = $store_id.'_5_'.$month_year;
                    
                    //if(isset($expense_values[$rent_key]) && isset($expense_values[$cam_key]) && isset($expense_values[$gst_key]) && isset($expense_values[$tds_key]) && !in_array($store_id, $invalid_stores)){
                    if(isset($expense_values[$rent_key]) && isset($expense_values[$cam_key]) && isset($expense_values[$gst_key]) && isset($expense_values[$tds_key])){
                        $rent = $expense_values[$rent_key];
                        $cam = $expense_values[$cam_key];
                        
                        if($month_days != $search_days){
                            $rent_per_day = $rent/$month_days;
                            $rent = $search_days*$rent_per_day;
                            $cam_per_day = $cam/$month_days;;
                            $cam = $search_days*$cam_per_day;
                        }
                        
                        $gst = $expense_values[$gst_key];
                        $tds = $expense_values[$tds_key];
                        $store_oc[$store_id]['rent']+=$rent;
                        $store_oc[$store_id]['cam']+=$cam;
                        $gst_val = ($rent+$cam)*($gst/100);
                        $tds_val = ($rent+$cam)*($tds/100);
                        $store_oc[$store_id]['gst_val_oc']+=$gst_val;
                        $store_oc[$store_id]['tds_val_oc']+=$tds_val;
                        $store_oc[$store_id]['total_val_oc']+=(($rent+$cam)+$gst_val-$tds_val);
                    }else{
                        //$invalid_stores[] = $store_id;
                        //$store_oc[$store_id]['rent'] = $store_oc[$store_id]['cam'] = $store_oc[$store_id]['gst_oc'] = $store_oc[$store_id]['tds_oc'] = 0;
                    }
                    
                    /* Occupancy Cost Code end */
                    
                    /* Manpower Cost Code Start */
                    
                    $comm_key = $store_id.'_6_'.$month_year;
                    $gst_key = $store_id.'_7_'.$month_year;
                    $tds_key = $store_id.'_8_'.$month_year;
                    $salary_key = $store_id.'_9_'.$month_year;
                    
                    if(isset($expense_values[$comm_key]) && $expense_values[$comm_key] > 0 && isset($expense_values[$gst_key]) && isset($expense_values[$tds_key])){
                        $comm = $expense_values[$comm_key];
                        $comm_val = $store_topline[$store_id]['turn_over']*($comm/100);
                        if($month_days != $search_days){
                            $comm_val_per_day = $comm_val/$month_days;
                            $comm_val = $search_days*$comm_val_per_day;
                        }
                        
                        $gst = $expense_values[$gst_key];
                        $tds = $expense_values[$tds_key];
                        $store_mc[$store_id]['comm_val']+=$comm_val;
                        $gst_val = ($comm_val)*($gst/100);
                        $tds_val = ($comm_val)*($tds/100);
                        $store_mc[$store_id]['gst_val']+=$gst_val;
                        $store_mc[$store_id]['tds_val']+=$tds_val;
                        $store_mc[$store_id]['total_val']+=($comm_val+$gst_val-$tds_val);
                    }
                    
                    if(isset($expense_values[$salary_key])){
                        $salary = $this->getExpenseValue($expense_values[$salary_key],$search_days,$month_days);
                        $store_mc[$store_id]['salary']+=$salary;
                    }
                            
                    /* Manpower Cost Code End */
                    
                    /* Selling Handling Code Start */
                    
                    $imprest_key = $store_id.'_10_'.$month_year;
                    $bank_charges_key = $store_id.'_11_'.$month_year;
                    $electricity_key = $store_id.'_12_'.$month_year;
                    $others_key = $store_id.'_13_'.$month_year;
                    
                    if(isset($expense_values[$imprest_key])){
                        $imprest = $this->getExpenseValue($expense_values[$imprest_key],$search_days,$month_days);
                        $store_sh[$store_id]['imprest']+=$imprest;
                    }
                    
                    if(isset($expense_values[$electricity_key])){
                        $electricity = $this->getExpenseValue($expense_values[$electricity_key],$search_days,$month_days);
                        $store_sh[$store_id]['electricity']+=$electricity;
                    }
                    
                    if(isset($expense_values[$others_key])){
                        $others = $this->getExpenseValue($expense_values[$others_key],$search_days,$month_days);
                        $store_sh[$store_id]['others']+=$others;
                    }
                    
                    /* Selling Handling Code End */
                }
            }
            
            for($i=0;$i<count($store_list);$i++){
                $store_id = $store_list[$i]['id'];
                
                $store_list[$i]['top_line_pcs'] = $store_topline[$store_id]['pcs'];
                $store_list[$i]['top_line_turn_over'] = $store_topline[$store_id]['turn_over'];
                $store_list[$i]['top_line_cogs'] = $store_topline[$store_id]['cogs'];
                $store_list[$i]['top_line_gm'] = $store_list[$i]['top_line_turn_over']-$store_list[$i]['top_line_cogs'];
                $store_list[$i]['top_line_percent'] = ($store_list[$i]['top_line_turn_over'] > 0)?round(($store_list[$i]['top_line_gm']/$store_list[$i]['top_line_turn_over'])*100,3):0;
                $store_list[$i]['top_line_asp'] =  ($store_list[$i]['top_line_pcs'] > 0)?round($store_list[$i]['top_line_turn_over']/$store_list[$i]['top_line_pcs'],3):0;
                
                $store_list[$i]['oc_rent'] = $store_oc[$store_id]['rent'];
                $store_list[$i]['oc_cam'] = $store_oc[$store_id]['cam'];
                $store_list[$i]['oc_gst_val'] = $store_oc[$store_id]['gst_val_oc'];
                $store_list[$i]['oc_tds_val'] = $store_oc[$store_id]['tds_val_oc'];
                $store_list[$i]['oc_total_val'] = $store_oc[$store_id]['total_val_oc'];
                $store_list[$i]['oc_percent'] = ($store_list[$i]['top_line_turn_over'] > 0)?round(($store_list[$i]['oc_total_val']/$store_list[$i]['top_line_turn_over'])*100,3):0;
                
                $store_list[$i]['mc_comm_val'] = $store_mc[$store_id]['comm_val'];
                $store_list[$i]['mc_gst_val'] = $store_mc[$store_id]['gst_val'];
                $store_list[$i]['mc_tds_val'] = $store_mc[$store_id]['tds_val'];
                $store_list[$i]['mc_total_val'] = $store_mc[$store_id]['total_val'];
                $store_list[$i]['salary'] = $store_mc[$store_id]['salary'];
                $store_list[$i]['mc_percent'] = ($store_list[$i]['top_line_turn_over'] > 0)?round(($store_list[$i]['salary']/$store_list[$i]['top_line_turn_over'])*100,3):0;
                
                $store_list[$i]['sh_imprest'] = $store_sh[$store_id]['imprest'];
                $store_list[$i]['sh_bank_charges_val'] = $store_sh[$store_id]['bank_charges_val'];
                $store_list[$i]['sh_electricity'] = $store_sh[$store_id]['electricity'];
                $store_list[$i]['sh_others'] = $store_sh[$store_id]['others'];
                
                $total_expenditure = $store_list[$i]['oc_total_val']+$store_list[$i]['mc_total_val']+$store_list[$i]['salary']+$store_list[$i]['sh_imprest']+$store_list[$i]['sh_bank_charges_val']+$store_list[$i]['sh_electricity']+$store_list[$i]['sh_others'];
                $store_list[$i]['total_expenditure'] = $total_expenditure;
                $store_list[$i]['ebitda'] = $store_list[$i]['top_line_turn_over']-$store_list[$i]['top_line_cogs']-$total_expenditure;
                
                $total_data['top_line_pcs']+=$store_list[$i]['top_line_pcs'];
                $total_data['top_line_turn_over']+=$store_list[$i]['top_line_turn_over'];
                $total_data['top_line_cogs']+=$store_list[$i]['top_line_cogs'];
                
                $total_data['oc_rent']+=$store_list[$i]['oc_rent'];
                $total_data['oc_cam']+=$store_list[$i]['oc_cam'];
                $total_data['oc_gst_val']+=$store_list[$i]['oc_gst_val'];
                $total_data['oc_tds_val']+=$store_list[$i]['oc_tds_val'];
                $total_data['oc_total_val']+=$store_list[$i]['oc_total_val'];
                
                $total_data['mc_comm_val']+=$store_list[$i]['mc_comm_val'];
                $total_data['mc_gst_val']+=$store_list[$i]['mc_gst_val'];
                $total_data['mc_tds_val']+=$store_list[$i]['mc_tds_val'];
                $total_data['mc_total_val']+=$store_list[$i]['mc_total_val'];
                $total_data['salary']+=$store_list[$i]['salary'];
                
                $total_data['sh_imprest']+=$store_list[$i]['sh_imprest'];
                $total_data['sh_bank_charges_val']+=$store_list[$i]['sh_bank_charges_val'];
                $total_data['sh_electricity']+=$store_list[$i]['sh_electricity'];
                $total_data['sh_others']+=$store_list[$i]['sh_others'];
            }
            
            $total_expenditure = $total_data['oc_total_val']+$total_data['mc_total_val']+$total_data['salary']+$total_data['sh_imprest']+$total_data['sh_bank_charges_val']+$total_data['sh_electricity']+$total_data['sh_others'];
            $total_data['total_expenditure'] = $total_expenditure;
            $total_data['ebitda'] = $total_data['top_line_turn_over']-$total_data['top_line_cogs']-$total_expenditure;
            
            // Download CSV Start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=profit_loss_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = ['SNo','Store Code','Store Name','Date','No of PCS','Turnover','COGS','GM','%','ASP','Rent','Cam','Total','GST','TDS','Total','%','Comm','GST','TDS','Total','Salary','%','Imprest','Bank Charges','Electricity','Others','Total Expenditure','Total Expenditure %','EBITDA','EBITDA %'];

                $callback = function() use ($columns,$store_list,$total_data,$start_date,$end_date){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $start_date = date('d-m-Y',strtotime($start_date));
                    $end_date = date('d-m-Y',strtotime($end_date));
                    
                    for($i=0;$i<count($store_list);$i++){
                        $array = [];
                        $array[] = $i+1;
                        $array[] = $store_list[$i]['store_id_code'];
                        $array[] = $store_list[$i]['store_name'];
                        $array[] = $start_date.' - '.$end_date;
                        $array[] = $store_list[$i]['top_line_pcs'];
                        $array[] = round($store_list[$i]['top_line_turn_over'],3);
                        $array[] = round($store_list[$i]['top_line_cogs'],3);
                        $array[] = round($store_list[$i]['top_line_gm'],3);
                        $array[] = $store_list[$i]['top_line_percent'].' %';
                        $array[] = $store_list[$i]['top_line_asp'];
                        $array[] = round($store_list[$i]['oc_rent'],3);
                        $array[] = round($store_list[$i]['oc_cam'],3);
                        $array[] = round($store_list[$i]['oc_rent']+$store_list[$i]['oc_cam'],3);
                        $array[] = round($store_list[$i]['oc_gst_val'],3);
                        $array[] = round($store_list[$i]['oc_tds_val'],3);
                        $array[] = round($store_list[$i]['oc_total_val'],3);
                        $array[] = $store_list[$i]['oc_percent'];
                        $array[] = round($store_list[$i]['mc_comm_val'],3);
                        $array[] = round($store_list[$i]['mc_gst_val'],3);
                        $array[] = round($store_list[$i]['mc_tds_val'],3);
                        $array[] = round($store_list[$i]['mc_total_val'],3);
                        $array[] = round($store_list[$i]['salary'],3);
                        $array[] = $store_list[$i]['mc_percent'];
                        $array[] = round($store_list[$i]['sh_imprest'],3);
                        $array[] = round($store_list[$i]['sh_bank_charges_val'],3);
                        $array[] = round($store_list[$i]['sh_electricity'],3);
                        $array[] = round($store_list[$i]['sh_others'],3);
                        $total_expenditure = $store_list[$i]['total_expenditure'];
                        $array[] = round($total_expenditure,3);
                        $array[] = ($store_list[$i]['top_line_turn_over'] > 0)?round(($total_expenditure/$store_list[$i]['top_line_turn_over'])*100,3).' %':0;
                        $ebitda = $store_list[$i]['ebitda'];
                        $array[] = round($ebitda,3);
                        $array[] = ($store_list[$i]['top_line_turn_over'] > 0)?round(($ebitda/$store_list[$i]['top_line_turn_over'])*100,3).' %':0;
                        
                        fputcsv($file, $array);
                    }

                    $array = ['','Total','',''];
                    $array[] = $total_data['top_line_pcs'];
                    $array[] = round($total_data['top_line_turn_over'],3);
                    $array[] = round($total_data['top_line_cogs'],3);
                    $array[] = round($total_data['top_line_turn_over']-$total_data['top_line_cogs'],3); 
                    $array[] = ($total_data['top_line_turn_over'] > 0)?round((($total_data['top_line_turn_over']-$total_data['top_line_cogs'])/$total_data['top_line_turn_over'])*100,3).' %':0;
                    $array[] = ($total_data['top_line_pcs'] > 0)?round($total_data['top_line_turn_over']/$total_data['top_line_pcs'],3):0;
                    $array[] = round($total_data['oc_rent'],3);
                    $array[] = round($total_data['oc_cam'],3);        
                    $array[] = round($total_data['oc_rent']+$total_data['oc_cam'],3);
                    $array[] = round($total_data['oc_gst_val'],3);
                    $array[] = round($total_data['oc_tds_val'],3);
                    $array[] = round($total_data['oc_total_val'],3);
                    $array[] = ($total_data['top_line_turn_over'] > 0)?round(($total_data['oc_total_val']/$total_data['top_line_turn_over'])*100,3).' %':0;
                    $array[] = round($total_data['mc_comm_val'],3);
                    $array[] = round($total_data['mc_gst_val'],3);
                    $array[] = round($total_data['mc_tds_val'],3);
                    $array[] = round($total_data['mc_total_val'],3);        
                    $array[] = round($total_data['salary'],3);
                    $array[] = ($total_data['top_line_turn_over'] > 0)?round(($total_data['salary']/$total_data['top_line_turn_over'])*100,3).' %':0;
                    $array[] = round($total_data['sh_imprest'],3);
                    $array[] = round($total_data['sh_bank_charges_val'],3);        
                    $array[] = round($total_data['sh_electricity'],3);        
                    $array[] = round($total_data['sh_others'],3);        
                    $total_expenditure = $total_data['total_expenditure'];
                    $array[] = round($total_expenditure,3);
                    $array[] = ($total_data['top_line_turn_over'] > 0)?round(($total_expenditure/$total_data['top_line_turn_over'])*100,3).' %':0;
                    $ebitda = $total_data['ebitda'];
                    $array[] = round($ebitda,3);
                    $array[] = ($total_data['top_line_turn_over'] > 0)?round(($ebitda/$total_data['top_line_turn_over'])*100,3).' %':0;        
                            
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV End
            
            return view('accounts/profit_loss_report',array('error_message'=>$error_msg,'store_list'=>$store_list,'start_date'=>$start_date,'end_date'=>$end_date,'total_data'=>$total_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return view('accounts/profit_loss_report',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function getExpenseValue($expense_value,$search_days,$month_days){
        if($month_days != $search_days){
            $expense_value_per_day = $expense_value/$month_days;
            $expense_value = round($search_days*$expense_value_per_day,3);
        }
        
        return $expense_value;
    }
    
    function getDatesList(){
        $dates_list = array();
        
        $start_date = '2021/03/01';
        $end_date = date('Y/m/d');
        while($start_date <= $end_date){
            $dates_list[] = $start_date;
            $start_date = date('Y/m/d',strtotime('+1 month',strtotime($start_date)));
        }
        
        return $dates_list;
    }
    
    function autoInsertMonthlyData(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $user_perm_ids = $monthly_fields = $dates_list = array();
            $error_msg = '';
            $date = date('Y/m/01');
            // Add 3 months data
            $dates_list = [$date,date('Y/m/d',strtotime('-1 month',strtotime($date))),date('Y/m/d',strtotime('-2 month',strtotime($date)))];
            
            $store_list = Store::where('is_deleted',0)->select('id','store_name')->get()->toArray();
            
            \DB::beginTransaction();
            
            for($i=0;$i<count($store_list);$i++){
                $store_id = $store_list[$i]['id'];
                
                $store_expense_master_values = Store_expense_master_values::where('store_id',$store_id)->where('is_deleted',0)->select('expense_id','expense_value')->get();
                if(!empty($store_expense_master_values)){
                    for($q=0;$q<count($dates_list);$q++){
                        $expense_date = $dates_list[$q];
                        $expense_monthly_values = Store_expense_monthly_values::where('store_id',$store_id)->where('expense_date',$expense_date)->where('is_deleted',0)->select('id')->first();
                        if(empty($expense_monthly_values)){
                            for($z=0;$z<count($store_expense_master_values);$z++){
                                $expense_id = $store_expense_master_values[$z]['expense_id'];
                                $expense_value = $store_expense_master_values[$z]['expense_value'];
                                $insertArray = ['store_id'=>$store_id,'expense_id'=>$expense_id,'expense_date'=>$expense_date,'expense_value'=>$expense_value];
                                Store_expense_monthly_values::create($insertArray);
                            }
                        }
                    }
                }
            }
            
            \DB::commit();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Store monthly data updated successfully'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_EXPENSE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function b2bGstReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $invoice_listing = array();
            $rec_per_page = 100;
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            $start_date = $search_date['start_date'];
            $end_date = $search_date['end_date'];
            
            // Demands list
            $invoice_list =  \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')                
            ->where('spd.demand_type','inventory_push')        
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
            ->where('spd.fake_inventory',0)         
            ->where('spd.is_deleted',0);
            
            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $invoice_list = $invoice_list->whereRaw("spd.created_at BETWEEN '$start_date' AND '$end_date'");        
            }
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $invoice_list = $invoice_list->where('spd.store_id',trim($data['s_id'])); 
            }
            
            $invoice_list = $invoice_list->selectRaw('spd.id,spd.invoice_no,spd.created_at,spd.store_data,spd.total_data');
            
            // Download csv or display page
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $invoice_list = $invoice_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $invoice_list = $invoice_list->paginate($rec_per_page);
            }
            
            $company_data = CommonHelper::getCompanyData();
            
            // Create separate rows according to gst rate
            $gst_types = [0,3,5,12,18];
            for($i=0;$i<count($invoice_list);$i++){
                $invoice_data  = [];
                foreach($invoice_list[$i] as $key=>$value){
                    $invoice_data[$key] = $value;
                }
                
                $total_data = json_decode($invoice_list[$i]->total_data,true);
                $store_data = json_decode($invoice_list[$i]->store_data,false);
                
                $invoice_data['invoice_value'] = $total_data['total_value'];
                $invoice_data['gst_no'] = $store_data->gst_no;
                
                if($store_data->gst_no != $company_data['company_gst_no']){
                    $gst_type = CommonHelper::getGSTType($invoice_data['gst_no']);
                    $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                }else{
                    $gst_name = '';
                }
                
                for($q=0;$q<count($gst_types);$q++){
                    $gst_type = $gst_types[$q];
                    if(isset($total_data['qty_'.$gst_type]) && $total_data['qty_'.$gst_type] > 0 ){
                        $qty = 'qty_'.$gst_type;
                        $taxable_value = 'taxable_value_'.$gst_type;
                        $gst_amount = 'gst_amount_'.$gst_type;
                        $invoice_data['rate'] = $gst_type;
                        $invoice_data['qty'] = $total_data[$qty];
                        $invoice_data['taxable_value'] = $total_data[$taxable_value];
                        $invoice_data['gst_amount'] = $total_data[$gst_amount];
                        
                        if($gst_name == 's_gst'){
                            $invoice_data['cgst'] = $invoice_data['sgst'] = round($invoice_data['gst_amount']/2,2);
                            $invoice_data['igst'] = '';
                        }elseif($gst_name == 'i_gst'){
                            $invoice_data['igst'] = $invoice_data['gst_amount'];
                            $invoice_data['cgst'] = $invoice_data['sgst'] = '';
                        }else{
                            $invoice_data['cgst'] = $invoice_data['sgst'] = $invoice_data['igst'] = '';
                        }
                        
                        $invoice_listing[] = $invoice_data;
                    }
                }
            }
            
            // Download csv
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=b2b_gst_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Recipient GST No','Invoice Number','Invoice Date','Invoice Value','Place of Supply','Reverse Charge','Invoice Type','Rate','Qty','Taxable Value','GST Amount','IGST','CGST','SGST','Total Value');

                $callback = function() use ($invoice_listing, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total = array('qty'=>0,'taxable_value'=>0,'gst_amount'=>0,'total_value'=>0,'igst'=>0,'cgst'=>0,'sgst'=>0);
                    
                    for($i=0;$i<count($invoice_listing);$i++){
                        $array = array($invoice_listing[$i]['gst_no'],$invoice_listing[$i]['invoice_no'],date('d-m-Y',strtotime($invoice_listing[$i]['created_at'])),$invoice_listing[$i]['invoice_value'],'09-Uttar Pradesh','N','Regular',$invoice_listing[$i]['rate'],$invoice_listing[$i]['qty'],$invoice_listing[$i]['taxable_value'],$invoice_listing[$i]['gst_amount'],$invoice_listing[$i]['igst'],$invoice_listing[$i]['cgst'],$invoice_listing[$i]['sgst'],($invoice_listing[$i]['taxable_value']+$invoice_listing[$i]['gst_amount']));
                        $total['qty']+=$invoice_listing[$i]['qty'];
                        $total['taxable_value']+=$invoice_listing[$i]['taxable_value'];
                        $total['gst_amount']+=$invoice_listing[$i]['gst_amount'];
                        $total['total_value']+=($invoice_listing[$i]['taxable_value']+$invoice_listing[$i]['gst_amount']);
                        $total['igst'] = $total['igst']+($invoice_listing[$i]['igst'] > 0?$invoice_listing[$i]['igst']:0);
                        $total['cgst'] = $total['cgst']+($invoice_listing[$i]['cgst'] > 0?$invoice_listing[$i]['cgst']:0);
                        $total['sgst'] = $total['sgst']+($invoice_listing[$i]['sgst'] > 0?$invoice_listing[$i]['sgst']:0);
                       
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','','','','','','',$total['qty'],round($total['taxable_value'],2),round($total['gst_amount'],2),round($total['igst'],2),round($total['cgst'],2),round($total['sgst'],2),round($total['total_value'],2));
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $store_list = Store::where('is_deleted',0)->select('id','store_name','store_id_code')->orderBy('store_name')->get()->toArray();
            
            return view('accounts/report_b2b_gst',array('error_message'=>$error_message,'user'=>$user,'invoice_list'=>$invoice_list,'invoice_listing'=>$invoice_listing,'store_list'=>$store_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'B2B_GST_REPORT',__FUNCTION__,__FILE__);
            return view('accounts/report_b2b_gst',array('error_message'=>$e->getLine().', '.$e->getMessage()));
        }
    }
    
    function b2cGstReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $rec_per_page = 100;
            
            if(isset($data['action']) && $data['action']  == 'get_state_stores'){
                $state_id = trim($data['state_id']);
                $state_where = !empty($state_id)?'state_id = '.$state_id:'state_id > 0';
                $stores_list = Store::where('is_deleted',0)->whereRaw($state_where)->select('id','store_name','store_id_code')->orderBy('store_name')->get()->toArray();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Stores list','stores_list'=>$stores_list),200);
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            $start_date = $search_date['start_date'];
            $end_date = $search_date['end_date'];
            
            $orders_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')           
            ->join('store as s','s.id', '=', 'pcod.store_id')
            ->where('pco.is_deleted',0)        
            ->where('pcod.is_deleted',0)
            ->where('pcod.fake_inventory',0)         
            ->where('pco.fake_inventory',0)                 
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1);        
            
            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $orders_list = $orders_list->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'");        
            }
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $orders_list = $orders_list->where('pcod.store_id',trim($data['s_id'])); 
            }
            
            if(isset($data['state_id']) && !empty($data['state_id'])){
                $orders_list = $orders_list->where('s.state_id',trim($data['state_id'])); 
            }
            
            $orders_list = $orders_list->groupByRaw('s.id,ABS(pcod.gst_percent)')        
            ->selectRaw('s.id as store_id,s.store_name,store_id_code,s.state_id,ABS(pcod.gst_percent) as gst_percent,SUM(pcod.product_quantity) as inv_count_sold,
            SUM(pcod.discounted_price_actual) as total_discounted_price,SUM(pcod.gst_amount) as total_gst_amount')
            ->orderByRaw('s.state_id,s.store_name,gst_percent');        
            
            // Download csv or display page
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $orders_list = $orders_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $orders_list = $orders_list->paginate($rec_per_page);
            }
            
            $states_list = \DB::table('state_list')->where('is_deleted',0)->where('status',1)->get();
            for($i=0;$i<count($states_list);$i++){
                $states[$states_list[$i]->id] = $states_list[$i]->state_name;
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=b2c_gst_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Type','Place Of Supply','Store','Rate','Qty','Taxable Value','GST Amount','Total Value');

                $callback = function() use ($orders_list,$states, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total = array('qty'=>0,'taxable_value'=>0,'gst_amount'=>0,'total_value'=>0);
                    
                    for($i=0;$i<count($orders_list);$i++){
                        $array = array('OE',$states[$orders_list[$i]->state_id],$orders_list[$i]->store_name,$orders_list[$i]->gst_percent,$orders_list[$i]->inv_count_sold,round($orders_list[$i]->total_discounted_price,2),round($orders_list[$i]->total_gst_amount,2),round($orders_list[$i]->total_discounted_price+$orders_list[$i]->total_gst_amount,2));
                        $total['qty']+=$orders_list[$i]->inv_count_sold;
                        $total['taxable_value']+=$orders_list[$i]->total_discounted_price;
                        $total['gst_amount']+=$orders_list[$i]->total_gst_amount;
                        $total['total_value']+=($orders_list[$i]->total_discounted_price+$orders_list[$i]->total_gst_amount);
                       
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','','',$total['qty'],round($total['taxable_value'],2),round($total['gst_amount'],2),round($total['total_value'],2));
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $store_list = Store::where('is_deleted',0)->select('id','store_name','store_id_code')->orderBy('store_name')->get()->toArray();
            
            return view('accounts/report_b2c_gst',array('error_message'=>$error_message,'user'=>$user,'orders_list'=>$orders_list,'store_list'=>$store_list,'states'=>$states));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'B2C_GST_REPORT',__FUNCTION__,__FILE__);
            return view('accounts/report_b2c_gst',array('error_message'=>$e->getMessage()));
        }
    }
    
    function hsnGstReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $store_id = '';
            $rec_per_page = 100;
            $kiasa_store_name = 'Kiasa HO'; $kiasa_store_code = 'HO';
            $stores_ids = $invoice_data = $order_data = $hsn_codes = $gst_percents = $total_list = $stores = array();
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            $start_date = $search_date['start_date'];
            $end_date = $search_date['end_date'];
            
            $company_data = CommonHelper::getCompanyData();
            
            /*$stores_list = Store::where('is_deleted',0)->select('id','store_name','store_id_code','state_id')->orderBy('id');
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $stores_list = $stores_list->where('id',trim($data['s_id'])); 
            }
            
            if(isset($data['state_id']) && !empty($data['state_id'])){
                $stores_list = $stores_list->where('state_id',trim($data['state_id'])); 
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $stores_list = $stores_list->offset($start)->limit($limit)->get();
                
            }else{
                $stores_list = $stores_list->paginate($rec_per_page);
            }
            
            for($i=0;$i<count($stores_list);$i++){
                $stores_ids[] = $stores_list[$i]->id;
                $stores[$stores_list[$i]->id] = $stores_list[$i];
            }
            */
            
            $states_list = \DB::table('state_list')->where('is_deleted',0)->where('status',1)->get();
            for($i=0;$i<count($states_list);$i++){
                $states[$states_list[$i]->id] = $states_list[$i]->state_name;
            }
            
            $store_list = Store::where('is_deleted',0)->select('id','store_name','store_id_code')->orderBy('store_name')->get()->toArray();
            //$store_list[] = ['id'=>'-1','store_name'=>$kiasa_store_name,'store_id_code'=>$kiasa_store_code];
            
            
            /*$invoice_list =  \DB::table('store_products_demand as spd')
            ->where('spd.demand_type','inventory_push')        
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
            ->wherein('spd.store_id',$stores_ids)        
            ->where('spd.fake_inventory',0)         
            ->where('spd.is_deleted',0);
            
            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $invoice_list = $invoice_list->whereRaw("spd.created_at BETWEEN '$start_date' AND '$end_date'");        
            }
            
            $invoice_list = $invoice_list->selectRaw('id,invoice_no,store_id,store_data,total_data_hsn')->get();
            
            for($i=0;$i<count($invoice_list);$i++){
                $store_id = $invoice_list[$i]->store_id;
                $hsn_data = json_decode($invoice_list[$i]->total_data_hsn,true);
                $store_data = json_decode($invoice_list[$i]->store_data,true);
                
                foreach($hsn_data as $key=>$value){
                    if(stripos($key,'taxable_value_') !== false){
                        $key1 = str_ireplace('taxable_value_', '', $key);
                        $key2 = $store_id.'_'.$key1;
                        if(isset($invoice_data[$key2]['taxable_value'])){
                            $invoice_data[$key2]['taxable_value']+=$value;
                        }else{
                            $invoice_data[$key2]['taxable_value'] = $value;
                        }
                        
                        $hsn_code = substr($key1,0,strpos($key1,'_'));
                        $hsn_codes[] = $hsn_code;
                        $gst_percent = substr($key1,strrpos($key1,'_')+1);
                        $gst_percents[] = $gst_percent;
                    }elseif(stripos($key,'gst_amount_') !== false){
                        $key1 = str_ireplace('gst_amount_', '', $key);
                        $key2 = $store_id.'_'.$key1;
                        if(isset($invoice_data[$key2]['gst_amount'])){
                            $invoice_data[$key2]['gst_amount']+=$value;
                        }else{
                            $invoice_data[$key2]['gst_amount'] = $value;
                        }
                        
                        if($store_data['gst_no'] != $company_data['company_gst_no']){
                            $gst_type = CommonHelper::getGSTType($store_data['gst_no']);
                            $gst_name = ($gst_type == 1)?'s_gst_amount':'i_gst_amount';
                            
                            if(isset($invoice_data[$key2][$gst_name])){
                                $invoice_data[$key2][$gst_name]+=$value;
                            }else{
                                $invoice_data[$key2][$gst_name] = $value;
                            }
                        }
                    }elseif(stripos($key,'qty_') !== false){
                        $key1 = str_ireplace('qty_', '', $key);
                        $key2 = $store_id.'_'.$key1;
                        if(isset($invoice_data[$key2]['qty'])){
                            $invoice_data[$key2]['qty']+=$value;
                        }else{
                            $invoice_data[$key2]['qty'] = $value;
                        }
                    }
                }
            }*/
            
            /*$orders_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')           
            ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')     
            ->join('store as s','s.id', '=', 'pcod.store_id')        
            ->where('pcod.store_id','>',0)        
            ->where('pco.is_deleted',0)        
            ->where('pcod.is_deleted',0)
            ->where('pcod.fake_inventory',0)         
            ->where('pco.fake_inventory',0)                 
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1);        
            
            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $orders_list = $orders_list->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'");        
            }
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $store_id = ($data['s_id']);
                $orders_list = $orders_list->wherein('pcod.store_id',$store_id); 
            }
            
            if(isset($data['state_id']) && !empty($data['state_id'])){
                $orders_list = $orders_list->where('s.state_id',trim($data['state_id'])); 
            }
            
            $orders_list = $orders_list->groupByRaw('pcod.store_id,ABS(pcod.gst_percent),ppm.hsn_code')        
            ->selectRaw('pcod.store_id,ppm.hsn_code,s.store_name,s.store_id_code,s.state_id,ABS(pcod.gst_percent) as gst_percent,SUM(pcod.product_quantity) as qty,
            SUM(pcod.discounted_price_actual) as taxable_value,SUM(pcod.gst_amount) as gst_amount')
            ->orderBy('pcod.store_id');        
                
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $orders_list = $orders_list->offset($start)->limit($limit)->get();
                $orders_data = json_decode(json_encode($orders_list),true);
            }else{
                $orders_list = $orders_list->paginate($rec_per_page);
                $orders_data = json_decode(json_encode($orders_list),true);
                $orders_data = $orders_data['data'];
            }
            
            for($i=0;$i<count($orders_data);$i++){
                $orders_data[$i]['i_gst_amount'] = 0;
                $orders_data[$i]['s_gst_amount'] = $orders_data[$i]['gst_amount'];
            }*/
    
            //Create arbitrary store kiasa ho. Display total b2b sales in it and b2c sales from all UP stores in it. Display igst and cgst in it.
            //if($store_id == -1){
                $invoice_list =  \DB::table('store_products_demand as spd')
                ->where('spd.demand_type','inventory_push')        
                ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                ->where('spd.fake_inventory',0)         
                ->where('spd.is_deleted',0);

                if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                    $invoice_list = $invoice_list->whereRaw("spd.created_at BETWEEN '$start_date' AND '$end_date'");        
                }

                $invoice_list = $invoice_list->selectRaw('id,invoice_no,store_id,store_data,total_data_hsn')->get();
                
                for($i=0;$i<count($invoice_list);$i++){
                    //$store_id = $invoice_list[$i]->store_id;
                    $hsn_data = json_decode($invoice_list[$i]->total_data_hsn,true);
                    $store_data = json_decode($invoice_list[$i]->store_data,true);

                    foreach($hsn_data as $key=>$value){
                        if(stripos($key,'taxable_value_') !== false){
                            $key1 = str_ireplace('taxable_value_', '', $key);
                            //$key2 = $store_id.'_'.$key1;
                            if(isset($invoice_data[$key1]['taxable_value'])){
                                $invoice_data[$key1]['taxable_value']+=$value;
                            }else{
                                $invoice_data[$key1]['taxable_value'] = $value;
                            }

                            $hsn_code = substr($key1,0,strpos($key1,'_'));
                            $hsn_codes[] = $hsn_code;
                            $gst_percent = substr($key1,strrpos($key1,'_')+1);
                            $gst_percents[] = $gst_percent;
                        }elseif(stripos($key,'gst_amount_') !== false){
                            $key1 = str_ireplace('gst_amount_', '', $key);
                            //$key2 = $store_id.'_'.$key1;
                            if(isset($invoice_data[$key1]['gst_amount'])){
                                $invoice_data[$key1]['gst_amount']+=$value;
                            }else{
                                $invoice_data[$key1]['gst_amount'] = $value;
                            }

                            if($store_data['gst_no'] != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($store_data['gst_no']);
                                $gst_name = ($gst_type == 1)?'s_gst_amount':'i_gst_amount';

                                if(isset($invoice_data[$key1][$gst_name])){
                                    $invoice_data[$key1][$gst_name]+=$value;
                                }else{
                                    $invoice_data[$key1][$gst_name] = $value;
                                }
                            }
                        }elseif(stripos($key,'qty_') !== false){
                            $key1 = str_ireplace('qty_', '', $key);
                            //$key2 = $store_id.'_'.$key1;
                            if(isset($invoice_data[$key1]['qty'])){
                                $invoice_data[$key1]['qty']+=$value;
                            }else{
                                $invoice_data[$key1]['qty'] = $value;
                            }
                        }
                    }
                }
                
                $orders_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')           
                ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')     
                ->join('store as s','s.id', '=', 'pcod.store_id')    
                //->where('s.state_id',34)           
                ->where('pcod.store_id','>',0)        
                ->where('pco.is_deleted',0)        
                ->where('pcod.is_deleted',0)
                ->where('pcod.fake_inventory',0)         
                ->where('pco.fake_inventory',0)                 
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1);        

                if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                    $orders_list = $orders_list->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'");        
                }
                
                if(isset($data['s_id']) && !empty($data['s_id'])){
                    $store_id = ($data['s_id']);
                    $orders_list = $orders_list->wherein('pcod.store_id',$store_id); 
                }

                if(isset($data['state_id']) && !empty($data['state_id'])){
                    $orders_list = $orders_list->where('s.state_id',trim($data['state_id'])); 
                }

                $orders_list = $orders_list->groupByRaw('ABS(pcod.gst_percent),ppm.hsn_code')        
                ->selectRaw('pcod.store_id,ppm.hsn_code,ABS(pcod.gst_percent) as gst_percent,SUM(pcod.product_quantity) as qty,
                SUM(pcod.discounted_price_actual) as taxable_value,SUM(pcod.gst_amount) as gst_amount')
                ->orderBy('pcod.store_id')        
                ->get(); 
                
                for($i=0;$i<count($orders_list);$i++){
                    $hsn_code = $orders_list[$i]->hsn_code;
                    $gst_percent = round($orders_list[$i]->gst_percent);
                    $key = $hsn_code.'_'.$gst_percent;
                    $order_data[$key] = ['qty'=>$orders_list[$i]->qty,'taxable_value'=>$orders_list[$i]->taxable_value,'gst_amount'=>$orders_list[$i]->gst_amount];
                    $hsn_codes[] = $hsn_code;
                    $gst_percents[] = $gst_percent;
                }

                $hsn_codes = array_values(array_unique($hsn_codes));
                $gst_percents = array_values(array_unique($gst_percents));
                
                for($q=0;$q<count($hsn_codes);$q++){
                    for($z=0;$z<count($gst_percents);$z++){
                        $hsn_code = $hsn_codes[$q];
                        $gst_percent = $gst_percents[$z];
                        $key = $hsn_code.'_'.$gst_percent;
                        if(isset($invoice_data[$key]) || isset($order_data[$key])){
                            $total_data = ['qty'=>0,'taxable_value'=>0,'gst_amount'=>0,'hsn_code'=>$hsn_code,'gst_percent'=>$gst_percent,'i_gst_amount'=>0,'s_gst_amount'=>0,'store_id'=>-1,'state_id'=>34,'store_name'=>$kiasa_store_name,'store_id_code'=>$kiasa_store_code];
                            if(isset($invoice_data[$key])){
                                $total_data['qty']+= $invoice_data[$key]['qty'];
                                $total_data['taxable_value']+= $invoice_data[$key]['taxable_value'];
                                $total_data['gst_amount']+= $invoice_data[$key]['gst_amount'];
                                $total_data['i_gst_amount']+= isset($invoice_data[$key]['i_gst_amount'])?$invoice_data[$key]['i_gst_amount']:0;
                                $total_data['s_gst_amount']+= isset($invoice_data[$key]['s_gst_amount'])?$invoice_data[$key]['s_gst_amount']:0;
                            }
                            
                            if(isset($order_data[$key])){
                                $total_data['qty']+= $order_data[$key]['qty'];
                                $total_data['taxable_value']+= $order_data[$key]['taxable_value'];
                                $total_data['gst_amount']+= $order_data[$key]['gst_amount'];
                                $total_data['s_gst_amount']+= $order_data[$key]['gst_amount'];
                            }
                            
                            $total_list[] = $total_data;
                        }
                    }
                }
                
                $orders_data = $total_list;
                $orders_list = '';
            //}
            
            /*for($i=0;$i<count($orders_list);$i++){
                $store_id = $orders_list[$i]->store_id;
                $hsn_code = $orders_list[$i]->hsn_code;
                $gst_percent = round($orders_list[$i]->gst_percent);
                $key = $store_id.'_'.$hsn_code.'_'.$gst_percent;
                $order_data[$key] = ['qty'=>$orders_list[$i]->inv_count_sold,'taxable_value'=>$orders_list[$i]->total_discounted_price,'gst_amount'=>$orders_list[$i]->total_gst_amount];
                $hsn_codes[] = $hsn_code;
                $gst_percents[] = $gst_percent;
            }
            
            $hsn_codes = array_values(array_unique($hsn_codes));
            
            
            $gst_percents = array_values(array_unique($gst_percents));
            
            for($i=0;$i<count($stores_list);$i++){
                for($q=0;$q<count($hsn_codes);$q++){
                    for($z=0;$z<count($gst_percents);$z++){
                        $store_id = $stores_list[$i]->id;
                        $hsn_code = $hsn_codes[$q];
                        $gst_percent = $gst_percents[$z];
                        $key = $store_id.'_'.$hsn_code.'_'.$gst_percent;
                        if(isset($invoice_data[$key]) || isset($order_data[$key])){
                            $total_data = ['qty'=>0,'taxable_value'=>0,'gst_amount'=>0,'hsn_code'=>$hsn_code,'gst_percent'=>$gst_percent,'store_id'=>$store_id,'i_gst_amount'=>0,'s_gst_amount'=>0,'state_id'=>$stores_list[$i]->state_id];
                            if(isset($invoice_data[$key])){
                                $total_data['qty']+= $invoice_data[$key]['qty'];
                                $total_data['taxable_value']+= $invoice_data[$key]['taxable_value'];
                                $total_data['gst_amount']+= $invoice_data[$key]['gst_amount'];
                                $total_data['i_gst_amount']+= isset($invoice_data[$key]['i_gst_amount'])?$invoice_data[$key]['i_gst_amount']:0;
                                $total_data['s_gst_amount']+= isset($invoice_data[$key]['s_gst_amount'])?$invoice_data[$key]['s_gst_amount']:0;
                            }
                            
                            if(isset($order_data[$key])){
                                $total_data['qty']+= $order_data[$key]['qty'];
                                $total_data['taxable_value']+= $order_data[$key]['taxable_value'];
                                $total_data['gst_amount']+= $order_data[$key]['gst_amount'];
                                $total_data['s_gst_amount']+= $order_data[$key]['gst_amount'];
                            }
                            
                            $total_list[] = $total_data;
                        }
                    }
                }
            }*/
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=hsn_gst_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Store','State','HSN','Description','Qty','Total Value','Taxable Value','Tax Rate','GST Amount','IGST','CGST','SGST');

                $callback = function() use ($orders_data,$states, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total = array('qty'=>0,'taxable_value'=>0,'gst_amount'=>0,'total_value'=>0,'i_gst_amount'=>0,'s_gst_amount'=>0);
                    
                    for($i=0;$i<count($orders_data);$i++){
                        $array = array($orders_data[$i]['store_name'],$states[$orders_data[$i]['state_id']],$orders_data[$i]['hsn_code'],'GARMENTS',$orders_data[$i]['qty'],round($orders_data[$i]['taxable_value']+$orders_data[$i]['gst_amount'],2),
                        round($orders_data[$i]['taxable_value'],2),$orders_data[$i]['gst_percent'],round($orders_data[$i]['gst_amount'],2),round($orders_data[$i]['i_gst_amount'],2),round($orders_data[$i]['s_gst_amount']/2,2),round($orders_data[$i]['s_gst_amount']/2,2));
                        
                        $total['qty']+=$orders_data[$i]['qty'];
                        $total['taxable_value']+=$orders_data[$i]['taxable_value'];
                        $total['gst_amount']+=$orders_data[$i]['gst_amount'];
                        $total['total_value']+=($orders_data[$i]['taxable_value']+$orders_data[$i]['gst_amount']);
                        $total['i_gst_amount']+=$orders_data[$i]['i_gst_amount'];
                        $total['s_gst_amount']+=$orders_data[$i]['s_gst_amount'];
                       
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','','',$total['qty'],round($total['total_value'],2),round($total['taxable_value'],2),'',round($total['gst_amount'],2),round($total['i_gst_amount'],2),round($total['s_gst_amount']/2,2),round($total['s_gst_amount']/2,2));
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('accounts/report_hsn_gst',array('error_message'=>$error_message,'orders_list'=>$orders_list,'store_list'=>$store_list,'stores'=>$stores,'states'=>$states,'orders_data'=>$orders_data));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'HSN_GST_REPORT',__FUNCTION__,__FILE__);
            return view('accounts/report_hsn_gst',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function itemsIDList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            $category_list = $color_list = $season_list = $subcategory_list = [];
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $story_list = Story_master::where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','POS_PRODUCT_SUBCATEGORY','COLOR','SEASON'))->where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            
            for($i=0;$i<count($design_lookup_items);$i++){
                if(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_CATEGORY'){
                    $category_list[] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_SUBCATEGORY'){
                    $pid = $design_lookup_items[$i]['pid'];
                    $subcategory_list[$pid][] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'COLOR'){
                    $color_list[] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'SEASON'){
                    $season_list[] = $design_lookup_items[$i];
                }
            }
            
            for($i=0;$i<count($category_list);$i++){
                $category_id = $category_list[$i]['id'];
                $category_list[$i]['subcategory_list'] = isset($subcategory_list[$category_id])?$subcategory_list[$category_id]:[];
            }
            
            return view('accounts/items_id_list',['category_list'=>$category_list,'size_list'=>$size_list,'story_list'=>$story_list,'color_list'=>$color_list,'season_list'=>$season_list,'error_message'=>$error_msg]);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'ITEMS_IDS_LIST',__FUNCTION__,__FILE__);
            return view('accounts/items_id_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function sizeList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get();
            
            return view('admin/size_list',['size_list'=>$size_list,'error_message'=>$error_msg]);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SIZE_LIST',__FUNCTION__,__FILE__);
            return view('admin/size_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function addSize(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            
            $validationRules = array('sizeName_add'=>'required');
            $attributes = array('sizeName_add'=>'Size Name');
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	

            $size_data = Production_size_counts::where('size',trim($data['sizeName_add']))->where('is_deleted',0)->first();
            if(!empty($size_data)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Size already exists in database', 'errors' =>'Size already exists in database' ));
            }
            
            $slug = CommonHelper::getSlug($data['sizeName_add']);
            
            Production_size_counts::create(['size'=>trim($data['sizeName_add']),'slug'=>$slug]);
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Size added successfully'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SIZE_ADD',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function updateStoreSKUInvBalance(Request $request,$type){
        try{
            set_time_limit(600);
            
            $date = date('Y/m/d');

            if(!($type == 1 || $type == 2)){
                exit;
            }

            if($type == 1 && !(date('H') >= 7 && date('H') <= 10)){
                echo 'Invalid date time';
                exit();
            }

            if($type == 2 && !(date('H') >= 21 && date('H') <= 23)){
                echo 'Invalid date time';
                exit();
            }

            $record_exists = Store_sku_inventory_balance::where('inv_date',$date)->where('record_type',$type)->first();
            if(!empty($record_exists)){
                echo 'Record exists';
                exit();
            }

            \DB::beginTransaction();

            $store_list = Store::where('is_deleted',0)->select('id','store_name')->orderBy('id')->get()->toArray();

            for($i=0;$i<count($store_list);$i++){

                $store_id = $store_list[$i]['id'];

                $store_sku_inv = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ppmi.store_id',$store_id)        
                ->where('ppmi.product_status',4)
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)        
                ->where('ppmi.fake_inventory',0)        
                ->where('ppm.fake_inventory',0)           
                ->groupBy('ppm.product_sku_id')                        
                ->selectRaw('ppm.product_sku_id,ppmi.base_price,ppmi.store_base_price,COUNT(ppmi.id) as inv_count')
                ->get()->toArray();

                for($q=0;$q<count($store_sku_inv);$q++){
                    $price = !empty($store_sku_inv[$q]->store_base_price)?$store_sku_inv[$q]->store_base_price:$store_sku_inv[$q]->base_price;
                    $insertArray = array('inv_date'=>$date,'record_type'=>$type,'store_id'=>$store_id,'sku'=>$store_sku_inv[$q]->product_sku_id,'bal_qty'=>$store_sku_inv[$q]->inv_count,'price'=>$price);
                    Store_sku_inventory_balance::create($insertArray);
                }
            }

            $wh_sku_inv = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->where('ppmi.product_status',1)
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)        
            ->where('ppmi.fake_inventory',0)        
            ->where('ppm.fake_inventory',0)           
            ->groupBy('ppm.product_sku_id')                        
            ->selectRaw('ppm.product_sku_id,COUNT(ppmi.id) as inv_count')
            ->get()->toArray();

            for($q=0;$q<count($wh_sku_inv);$q++){
                $insertArray = array('inv_date'=>$date,'record_type'=>$type,'store_id'=>0,'sku'=>$wh_sku_inv[$q]->product_sku_id,'bal_qty'=>$wh_sku_inv[$q]->inv_count);
                Store_sku_inventory_balance::create($insertArray);
            }

            \DB::commit();
        
        }catch (\Exception $e){
            \DB::rollBack();
            echo $e->getMessage();
        }
        
    }
    
    public function updateStoreSKUPrice(Request $request){
        try{
            set_time_limit(180);
            \DB::beginTransaction();
            
            $records = Store_sku_inventory_balance::where('store_id','>',0)->WhereRaw("price IS NULL")->orderBy('id')->select('store_id','sku')->limit(10)->get()->toArray();
            
            for($i=0;$i<count($records);$i++){
                $inv = Pos_product_master_inventory::where('store_id',$records[$i]['store_id'])->where('product_sku_id',$records[$i]['sku'])->where('is_deleted',0)->where('fake_inventory',0)->select('base_price','store_base_price')->first();
                
                if(!empty($inv)){
                    $price = !empty($inv->store_base_price)?$inv->store_base_price:$inv->base_price;
                }else{
                    $price = 0;
                }
                
                Store_sku_inventory_balance::where('store_id',$records[$i]['store_id'])->where('sku',$records[$i]['sku'])->update(['price'=>$price]);
            }
            
            \DB::commit(); 
             
        }catch (\Exception $e){
            \DB::rollBack();
            echo $e->getMessage().', '.$e->getLine();
        }
    }
    
    function closingStockDetailReport(Request $request){
        try{
            //ini_set('memory_limit', '-1');
            //set_time_limit(300);
            $data = $request->all();
            $error_message = $store_id = $start_date = $end_date = '';
            $user = Auth::user();
            
            $sku_list_store = $sku_list_ho = $inv_total = $inv_start = $inv_end = $store_sale = $store_purchase = [];
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            if(!empty($search_date['start_date']) && !empty($search_date['start_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            $store_id = trim($data['s_id']);
            
            $sku_inv_start = \DB::table('store_sku_inventory_balance as inv1')
            ->join('pos_product_master as ppm','ppm.product_sku_id', '=', 'inv1.sku')
            ->where('inv1.store_id',$store_id)        
            ->where('inv1.inv_date',$start_date)                
            ->where('inv1.record_type',1)        
            ->where('ppm.is_deleted',0)
            ->where('ppm.fake_inventory',0)
            ->select('inv1.*','ppm.product_sku','ppm.product_name','ppm.base_price')
            ->get();        
            
            $sku_inv_end = \DB::table('store_sku_inventory_balance as inv1')
            ->join('pos_product_master as ppm','ppm.product_sku_id', '=', 'inv1.sku')
            ->where('inv1.store_id',$store_id)        
            ->where('inv1.inv_date',$end_date)                
            ->where('inv1.record_type',2)        
            ->where('ppm.is_deleted',0)
            ->where('ppm.fake_inventory',0)
            ->select('inv1.*','ppm.product_sku','ppm.product_name','ppm.base_price')
            ->get();        
            
            for($i=0;$i<count($sku_inv_start);$i++){
                $record = $sku_inv_start[$i];
                $inv_total[$record->sku] = $record;
                $inv_start[$record->sku] = $record->bal_qty;
            }
            
            for($i=0;$i<count($sku_inv_end);$i++){
                $record = $sku_inv_end[$i];
                $inv_total[$record->sku] = $record;
                $inv_end[$record->sku] = $record->bal_qty;
            }
            
            $whereArray = ['ppm.is_deleted'=>0,'pco.is_deleted'=>0,'pcod.is_deleted'=>0,'pcod.fake_inventory'=>0,'pco.fake_inventory'=>0,'ppm.fake_inventory'=>0,'pco.order_status'=>1,'pcod.order_status'=>1];
            
            $store_out_products = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')         
            ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
            ->where('pco.store_id',$store_id)        
            ->whereRaw("DATE(pco.created_at) >= '".$start_date."' AND DATE(pco.created_at) <= '".$end_date."'")        
            ->where($whereArray)              
            ->groupBy('ppm.product_sku_id')   
            ->selectRaw('ppm.product_sku_id,pcod.net_price,SUM(pcod.product_quantity) as inv_sale_count')     
            ->get();        
            
            for($i=0;$i<count($store_out_products);$i++){
                $store_sale[$store_out_products[$i]->product_sku_id] =  $store_out_products[$i];
            }
            
            $whereArray = ['spdi.transfer_status'=>1,'ppmi.is_deleted'=>0,'ppmi.status'=>1,'spdi.demand_status'=>1,'spd.fake_inventory'=>0,'spdi.fake_inventory'=>0,'ppm.fake_inventory'=>0,'ppmi.fake_inventory'=>0];
            
            $store_in_products = \DB::table('store_products_demand_inventory as spdi')
            ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
            ->where('spd.store_id',$store_id)       
            ->where('spd.demand_type','inventory_push')        
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
            ->whereRaw("DATE(spd.created_at) >= '".$start_date."' AND DATE(spd.created_at) <= '".$end_date."'")     
            ->where($whereArray)              
            ->groupBy('ppm.product_sku_id')        
            ->selectRaw('spdi.store_base_price,ppm.product_sku_id,COUNT(spdi.id) as inv_in_count')
            ->get();
            
            for($i=0;$i<count($store_in_products);$i++){
                $store_purchase[$store_in_products[$i]->product_sku_id] =  $store_in_products[$i];
            }
            
            $store_list_total = Store::where('is_deleted',0)->get()->toArray();
            $store_data = Store::where('id',$store_id)->first();
            
            $data_array = ['inv_total'=>$inv_total,'inv_start'=>$inv_start,'inv_end'=>$inv_end,'error_message'=>$error_message,'user'=>$user,
            'store_list_total'=>$store_list_total,'store_data'=>$store_data,'store_sale'=>$store_sale,'store_purchase'=>$store_purchase];
            
            return view('accounts/report_closing_stock_data',$data_array);
        }catch (\Exception $e) {
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('accounts/report_closing_stock_data',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function storeReportTypes(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            $store_data = $store_reports = [];
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $store_id = trim($data['s_id']);
                $store_data = Store::where('id',$store_id)->first();
                $store_report_types = Store_report_types::where('store_id',$store_id)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($store_report_types);$i++){
                    $store_reports[$store_report_types[$i]['report']] = $store_report_types[$i]['report_type'];
                }
            }
            
            $reports = ['daily_sales_report'=>'Daily Sales Report','store_bill_wise_report'=>'Store wise Bill wise Report'];
            $report_types = ['real'=>'Real','fake'=>'Fake','both'=>'Both'];
            
            $store_list = Store::where('is_deleted',0)->orderBy('store_name')->get()->toArray();
            
            return view('accounts/store_report_types',array('error_message'=>$error_msg,'store_list'=>$store_list,'reports'=>$reports,'store_reports'=>$store_reports,'report_types'=>$report_types,'store_data'=>$store_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE_REPORT_TYPES',__FUNCTION__,__FILE__);
            return view('accounts/store_report_types',array('error_message'=>$e->getMessage()));
        }
    }
    
    function updateStoreReportTypes(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            $store_data = $store_reports = [];
            
            $validationRules = array('store_id'=>'required');
            $attributes = array('store_id'=>'Store');
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	

            $reports = ['daily_sales_report'=>'Daily Sales Report','store_bill_wise_report'=>'Store wise Bill wise Report'];
            
            $store_id = trim($data['store_id']);
            $store_data = Store::where('id',$store_id)->first();
            
            $store_report_types = Store_report_types::where('store_id',$store_id)->where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($store_report_types);$i++){
                $store_reports[$store_report_types[$i]['report']] = $store_report_types[$i]['report_type'];
            }
           
            foreach($reports as $report=>$report_name){
                $val = !empty($data[$report])?$data[$report]:null;
                if(array_key_exists($report, $store_reports)){
                    $updateArray = ['report_type'=>$val];
                    Store_report_types::where('store_id',$store_id)->where('report',$report)->update($updateArray);
                }else{
                    $insertArray = ['store_id'=>$store_id,'report'=>$report,'report_type'=>$val];
                    Store_report_types::create($insertArray);
                }
            }
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Store Reports updated successfully'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SIZE_ADD',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
}
