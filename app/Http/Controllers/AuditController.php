<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit;
use App\Models\Audit_inventory;
use App\Models\Store;
use App\Models\Design_lookup_items_master;
use App\Models\Pos_product_master_inventory;
use App\Models\Pos_customer;
use App\Models\Pos_customer_orders;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;
use PDF;
use Session;

class AuditController extends Controller
{
    
    public function __construct(){
    }
    
    function dashboard(Request $request){
        try{ 
            return view('audit/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return view('audit/dashboard',array('error_message' =>$e->getMessage()));
        }
    }
    
    function auditsList(Request $request){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            
            if(isset($data['action']) && $data['action'] == 'audit_add'){ 
                //print_r($data);exit;
                $validationRules = array('audit_type_add'=>'required','store_id_add'=>'required','members_present_add'=>'required','counter_cash_add'=>'required|numeric','manual_bills_add'=>'required');

                $attributes = array('audit_type_add'=>'Audit Type','store_id_add'=>'Store','members_present_add'=>'Present Members','counter_cash_add'=>'Cash in Counter','manual_bills_add'=>'Manual Bills');
                
                if($data['audit_type_add'] == 'warehouse'){
                    unset($validationRules['store_id_add']);
                }

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                \DB::beginTransaction();
                
                if($data['audit_type_add'] == 'store'){
                
                    $insertArray = array('audit_type'=>'store','store_id'=>$data['store_id_add'],'auditor_id'=>$user->id,'members_present'=>$data['members_present_add'],'counter_cash'=>$data['counter_cash_add'],'manual_bills'=>$data['manual_bills_add']);

                    $store_data = Store::where('id',$data['store_id_add'])->first();
                    $last_audit =  Audit::where('store_id',$data['store_id_add'])->where('audit_type','store')->whereRaw('DATE(created_at) = CURDATE()')->orderBy('id','DESC')->first();
                    $audit_no = (!empty($last_audit) && strlen($last_audit->audit_no) == 20)?(substr($last_audit->audit_no,-3)):0;
                    $audit_no = 'KNCAU_'.$store_data->store_code.'_'.date('ymd').'_'.str_pad(($audit_no+1),3,'0',STR_PAD_LEFT);

                    $insertArray['audit_no'] = $audit_no;
                    $audit = Audit::create($insertArray);

                    $inventory = Pos_product_master_inventory::where('store_id',$data['store_id_add'])
                    ->where('product_status',4)
                    ->where('is_deleted',0)
                    ->where('fake_inventory',0)
                    ->get()->toArray();

                    for($i=0;$i<count($inventory);$i++){
                        $insertArray = array('audit_id'=>$audit->id,'inventory_id'=>$inventory[$i]['id'],'store_id'=>$data['store_id_add'],'product_status'=>$inventory[$i]['product_status'],'present_system'=>1);
                        Audit_inventory::create($insertArray);
                    }
                }
                
                if($data['audit_type_add'] == 'warehouse'){
                    $insertArray = array('audit_type'=>'warehouse','store_id'=>0,'auditor_id'=>$user->id,'members_present'=>$data['members_present_add'],'counter_cash'=>$data['counter_cash_add'],'manual_bills'=>$data['manual_bills_add']);

                    $store_data = Store::where('id',$data['store_id_add'])->first();
                    $last_audit =  Audit::where('audit_type','warehouse')->whereRaw('DATE(created_at) = CURDATE()')->orderBy('id','DESC')->first();
                    $audit_no = (!empty($last_audit))?(substr($last_audit->audit_no,-3)):0;
                    $audit_no = 'KNCAU_'.'WH'.'_'.date('ymd').'_'.str_pad(($audit_no+1),3,'0',STR_PAD_LEFT);

                    $insertArray['audit_no'] = $audit_no;
                    $audit = Audit::create($insertArray);
                    
                    $sql = 'INSERT INTO `audit_inventory` (audit_id, inventory_id, store_id,product_status,present_system) SELECT '.$audit->id.',id,0,product_status,1 FROM pos_product_master_inventory WHERE product_status = 1 AND fake_inventory = 0 AND is_deleted = 0';
                    \DB::statement($sql);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Audit Created, ID: '.$audit->id,'AUDIT_CREATED','AUDIT');

                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit added successfully','audit_id' =>$audit->id,'audit_type' =>$data['audit_type_add']),200);

            }
            
            if(isset($data['action']) && $data['action'] == 'get_audit_data'){
                $audit_data = Audit::where('id',$data['id'])->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit data','audit_data' => $audit_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'audit_update'){
                $audit_data = Audit::where('id',$data['audit_edit_id'])->first();
                
                $validationRules = array('store_id_edit'=>'required','members_present_edit'=>'required','counter_cash_edit'=>'required|numeric','manual_bills_edit'=>'required');
                $attributes = array('store_id_edit'=>'Store','members_present_edit'=>'Present Members','counter_cash_edit'=>'Cash in Counter','manual_bills_edit'=>'Manual Bills');

                if($audit_data->audit_type == 'warehouse'){
                    unset($validationRules['store_id_edit']);
                }
                
                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                $updateArray = array('members_present'=>$data['members_present_edit'],'counter_cash'=>$data['counter_cash_edit'],'manual_bills'=>$data['manual_bills_edit']);
                $updateArray['wbc_sku_list'] = (isset($data['wbc_sku_list_edit']))?$data['wbc_sku_list_edit']:null;
                $updateArray['cash_verified'] = (isset($data['cash_verified_edit']))?$data['cash_verified_edit']:null;
                $updateArray['cash_verified_comment'] = (isset($data['cash_verified_comment_edit']))?$data['cash_verified_comment_edit']:null;
                if(isset($data['audit_complete_edit']) && $data['audit_complete_edit'] == 1) $updateArray['audit_status'] = 'completed';
                
                if($audit_data->audit_type == 'store'){
                    $updateArray['store_id'] = $data['store_id_edit'];
                }
                
                $audit = Audit::where('id',$data['audit_edit_id'])->update($updateArray);
                CommonHelper::createLog('Audit Updated, ID: '.$data['audit_edit_id'],'AUDIT_UPDATED','AUDIT');

                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'get_audit_final_report_pdf'){
                $request = new Request(['action'   => 'get_report_data']);
                $res = $this->auditInventoryVarianceReport($request,$data['id'],1);
                $view_data = $res;
                $pdf = PDF::loadView('audit/audit_final_report_pdf', $view_data);
                return $pdf->download('audit_final_report_pdf');
            }
            
            if(isset($data['action']) && $data['action'] == 'get_audit_wh_final_report_pdf'){
                $request = new Request(['action'   => 'get_report_data']);
                $res = $this->auditWarehouseInventoryVarianceReport($request,$data['id'],1);
                $view_data = $res;
                //return view('audit/audit_wh_final_report_pdf',$view_data);
                $pdf = PDF::loadView('audit/audit_wh_final_report_pdf', $view_data);
                return $pdf->download('audit_wh_final_report_pdf');
            }
            
            $audits_list = \DB::table('audit as a')
            ->leftJoin('store as s','s.id', '=', 'a.store_id')
            ->join('users as u','u.id', '=', 'a.auditor_id')        
            ->where('a.is_deleted',0);
            
            // Admin can view all audits, other users can view only own audits
            if($user->user_type != 1){
                $audits_list = $audits_list->where('a.auditor_id',$user->id);
            }
            
            if(isset($data['store_id']) && !empty($data['store_id'])){
                $audits_list = $audits_list->where('store_id',$data['store_id']);
            }
            
            if(isset($data['audit_id']) && !empty($data['audit_id'])){
                $audits_list = $audits_list->where('a.id',$data['audit_id']);
            }    
            
            if(isset($data['audit_no']) && !empty($data['audit_no'])){
                $audits_list = $audits_list->where('a.audit_no',$data['audit_no']);
            }   
            
            if(isset($data['audit_type']) && !empty($data['audit_type'])){
                $audits_list = $audits_list->where('a.audit_type',$data['audit_type']);
            }    
            
            $audits_list = $audits_list->select('a.*','s.store_name','s.store_id_code','u.name as auditor_name')->orderBy('a.id','DESC');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $audits_list = $audits_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $audits_list = $audits_list->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=audit_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Audit ID','Audit No','Audit Type','Store Name','Code ','Auditor','Status','Created On');

                $callback = function() use ($audits_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($audits_list);$i++){
                        $array = array($audits_list[$i]->id,$audits_list[$i]->audit_no,$audits_list[$i]->audit_type,$audits_list[$i]->store_name,$audits_list[$i]->store_id_code,$audits_list[$i]->auditor_name,str_replace('_',' ',$audits_list[$i]->audit_status),date('d-m-Y',strtotime($audits_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $store_list = Store::where('is_deleted',0)->orderBy('store_name')->get()->toArray();
            
            return view('audit/audits_list',array('error_message'=>'','audits_list'=>$audits_list,'store_list'=>$store_list,'user'=>$user));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('audit/audits_list',array('error_message' =>$e->getMessage()));
            }
        }
    }
    
    function auditDetail(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            
            $audit_data = $this->getAuditData($audit_id);
            
            $inv_count = \DB::table('pos_product_master_inventory as ppmi')
            ->join('audit_inventory as ai','ppmi.id', '=', 'ai.inventory_id')        
            ->where('ai.audit_id',$audit_id)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0)        
            ->where('ai.is_deleted',0)
            ->count();        
            
            return view('audit/audit_detail',array('error_message'=>'','audit_data'=>$audit_data,'user'=>$user,'inv_count'=>$inv_count));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return view('audit/audit_detail',array('error_message' =>$e->getMessage()));
        }
    }
    
    function getAuditData($audit_id){
        $audit_data = \DB::table('audit as a')
        ->leftJoin('store as s','s.id', '=', 'a.store_id')
        ->join('users as u','u.id', '=', 'a.auditor_id')   
        ->where('a.id',$audit_id)        
        ->where('a.is_deleted',0)
        ->select('a.*','s.store_name','s.store_id_code','u.name as auditor_name')->first();
        
        return $audit_data;
    }
    
    function auditScanInventory(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                
                // Check product start
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('store as s','s.id', '=', 'ppmi.store_id')             
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])
                ->where('ppmi.is_deleted',0)
                ->where('ppm.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)        
                ->where('ppmi.status',1)
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name',
                'psc.size as size_name','s.store_name')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists' ));
                }
                
                // Check product end
                
                // Insert product start
                $audit_inventory_prod = Audit_inventory::where('audit_id',$data['audit_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->first();
                if(!empty($audit_inventory_prod) && $audit_inventory_prod->scan_status == 1){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> already added ', 'errors' =>'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> already added ' ));
                }        
                
                if($product_data->product_status == 4){
                    if(!empty($audit_inventory_prod)){
                        // present in system and in store
                        $updateArray = array('scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_store'=>1);
                        Audit_inventory::where('id',$audit_inventory_prod->id)->update($updateArray);
                        $product_data->present_system = 1;
                    }else{
                        // not present in system, but present in store
                        $insertArray = array('audit_id'=>$data['audit_id'],'inventory_id'=>$product_data->id,'store_id'=>$product_data->store_id,'product_status'=>$product_data->product_status,'scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_system'=>0,'present_store'=>1);
                        Audit_inventory::create($insertArray);
                        $product_data->present_system = 0;
                    }
                }else{
                    // not present in system, but present in store
                    $insertArray = array('audit_id'=>$data['audit_id'],'inventory_id'=>$product_data->id,'store_id'=>$product_data->store_id,'product_status'=>$product_data->product_status,'scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_system'=>0,'present_store'=>1);
                    Audit_inventory::create($insertArray);
                    $product_data->present_system = 0;
                }
                
                $product_data->present_store = 1;
                
                // Insert product end
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> added ','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_audit_inventory'){
                $rec_per_page = 500;
                $barcode_list = array();
                $scan_status = explode(',',$data['scan_status']);
                
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('audit_inventory as ai','ppmi.id', '=', 'ai.inventory_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->leftJoin('store as s','s.id', '=', 'ai.store_id')        
                ->where('ai.audit_id',$data['audit_id'])        
                ->wherein('ai.scan_status',$scan_status)       
                ->where('ppmi.is_deleted',0)
                ->where('ppm.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)        
                ->where('ai.is_deleted',0);
                
                if(isset($data['store_id_search']) && !empty($data['store_id_search'])){
                    $product_list = $product_list->where('ai.store_id',$data['store_id_search']);
                }
                
                if(isset($data['product_status_search']) && !empty($data['product_status_search'])){
                    $product_list = $product_list->where('ai.product_status',$data['product_status_search']);
                }
                
                if(isset($data['inv_status_search']) && !empty($data['inv_status_search'])){
                    if($data['inv_status_search'] == 2){
                        $product_list = $product_list->where('ai.present_system',1);
                    }
                    if($data['inv_status_search'] == 3){
                        $product_list = $product_list->where('ai.present_store',1);
                    }
                    if($data['inv_status_search'] == 4){
                        $product_list = $product_list->where('ai.present_system',1)->where('ai.present_store',1);
                    }
                    if($data['inv_status_search'] == 5){
                        $product_list = $product_list->where('ai.present_system',1)->where('ai.present_store',0);
                    }   
                    if($data['inv_status_search'] == 6){
                        $product_list = $product_list->where('ai.present_system',0)->where('ai.present_store',1);
                    }
                }
                
                $product_list = $product_list->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name',
                'psc.size as size_name','ai.scan_date','s.store_name','ai.present_system','ai.present_store')->orderBy('scan_date','ASC')->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $inv_imported = Audit_inventory::where('audit_id',$data['audit_id'])->where('scan_status','=','1')->where('is_deleted',0)->count();
                $inv_total = Audit_inventory::where('audit_id',$data['audit_id'])->where('is_deleted',0)->count();
                
                $inventory_count_data = array('inv_total'=>$inv_total,'inv_imported'=>$inv_imported);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'inventory_count_data'=>$inventory_count_data,'barcode_list'=>$barcode_list),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'complete_audit_scan'){
                \DB::beginTransaction();
                
                // Update product sold during audit as scanned
                $inventory_sold_list = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->where('ai.audit_id',$data['audit_id'])        
                ->where('ppmi.product_status',5)                
                ->where('ai.scan_status',0)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->select('ai.*')->get()->toArray();
                
                for($i=0;$i<count($inventory_sold_list);$i++){
                    //$inventory_id_arr[] = $inventory_sold_list[$i]->inventory_id;
                    $updateArray = array('scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_store'=>1,'product_status'=>4);
                    Audit_inventory::where('id',$inventory_sold_list[$i]->id)->update($updateArray);
                }
                
                $audit_inventory = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ai.audit_id',$data['audit_id'])        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->selectRaw("ai.*,ppmi.sale_price,ppmi.base_price")
                ->orderBy('ai.id','ASC')->get()->toArray();
                
                $inv_total = array('inv_count_system'=>0,'inv_count_store'=>0,'inv_price_system'=>0,'inv_price_store'=>0,'inv_cost_price_system'=>0,'inv_cost_price_store'=>0);
                for($i=0;$i<count($audit_inventory);$i++){
                    if($audit_inventory[$i]->present_system == 1){
                        $inv_total['inv_count_system']+=1;
                        $inv_total['inv_price_system']+=$audit_inventory[$i]->sale_price;
                        $inv_total['inv_cost_price_system']+=$audit_inventory[$i]->base_price;
                    }
                    
                    if($audit_inventory[$i]->present_store == 1){
                        $inv_total['inv_count_store']+=1;
                        $inv_total['inv_price_store']+=$audit_inventory[$i]->sale_price;
                        $inv_total['inv_cost_price_store']+=$audit_inventory[$i]->base_price;
                    }
                }
                
                $updateArray = array('audit_status'=>'scan_completed','scan_complete_date'=>date('Y/m/d H'),'scan_complete_comment'=>$data['comments'],'system_inv_quantity'=>$inv_total['inv_count_system'],
                'system_inv_cost_price'=>$inv_total['inv_cost_price_system'],'system_inv_sale_price'=>$inv_total['inv_price_system'],'store_inv_quantity'=>$inv_total['inv_count_store'],
                'store_inv_cost_price'=>$inv_total['inv_cost_price_store'],'store_inv_sale_price'=>$inv_total['inv_price_store']);

                Audit::where('id',$data['audit_id'])->update($updateArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit Scan completed'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_audit_inv_items'){
                $audit_items = Audit_inventory::where('audit_id',$data['audit_id'])->wherein('inventory_id',$data['deleteChk'])->where('is_deleted',0)->get()->toArray();
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($audit_items);$i++){
                    if($audit_items[$i]['present_system'] == 1){
                        $updateArray = array('scan_status'=>0,'scan_date'=>null,'present_store'=>0);
                    }else{
                        $updateArray = array('is_deleted'=>1);
                    }
                    Audit_inventory::where('id',$audit_items[$i]['id'])->where('is_deleted',0)->update($updateArray);
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit items deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                
                $audit_data = $this->getAuditData($audit_id);
                
                $inv_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('audit_inventory as ai','ppmi.id', '=', 'ai.inventory_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->leftJoin('store as s','s.id', '=', 'ai.store_id')        
                ->where('ai.audit_id',$data['audit_id'])        
                ->where('ppmi.is_deleted',0)
                ->where('ppm.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)        
                ->where('ai.is_deleted',0);
                
                $inv_list = $inv_list->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name',
                'psc.size as size_name','ai.scan_date','s.store_name','ai.present_system','ai.present_store','ai.present_warehouse')
                ->orderBy('scan_date','ASC');
                //->get();
                
                $audit_inventory_count = trim($data['audit_inventory_count']);
                $audit_inventory_count_arr = explode('_',$audit_inventory_count);
                $start = $audit_inventory_count_arr[0];
                $start = $start-1;
                $end = $audit_inventory_count_arr[1];
                $limit = $end-$start;
                $inv_list = $inv_list->offset($start)->limit($limit)->get();
                
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=audit_inventory_'.$data['audit_id'].'.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                if($audit_data->audit_type == 'store'){
                    $columns = array('SNo','Peice Barcode','Product','Size','Color','SKU','Cost','Scan Date','Intake Date','Status','Store','Present System','Present Store','Type');
                }else{
                    $columns = array('SNo','Peice Barcode','Product','Size','Color','SKU','Cost','Scan Date','Intake Date','Status','Store','Present System','Present Warehouse','Type');
                }

                $callback = function() use ($inv_list,$columns,$audit_data){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($inv_list);$i++){
                        $inv_data = $inv_list[$i];
                        $intake_date = $inv_data->intake_date != null?$inv_data->intake_date:'';
                        $scan_date = ($inv_data->scan_date != null)?$inv_data->scan_date:'';
                        $present_system = ($inv_data->present_system == 1)?'Yes':'No';
                        $present_store = ($inv_data->present_store == 1)?'Yes':'No';
                        $present_warehouse = ($inv_data->present_warehouse == 1)?'Yes':'No';
                        
                        if($audit_data->audit_type == 'store'){
                            $cost_price = $inv_data->store_base_price != null?$inv_data->store_base_price:'';
                        }else{
                            $cost_price = $inv_data->base_price != null?$inv_data->base_price:'';
                        }
                        
                        if($present_system == 'Yes'){
                            $inv_type = ($inv_data->arnon_inventory == 1)?'Arnon':'Northcorp';
                        }else{
                            $inv_type = '';
                        }
                        
                        $array = array($i+1, CommonHelper::filterCsvInteger($inv_data->peice_barcode),$inv_data->product_name,$inv_data->size_name,$inv_data->color_name,$inv_data->product_sku,$cost_price,$scan_date,
                        $intake_date, CommonHelper::getposProductStatusName($inv_data->product_status),$inv_data->store_name,$present_system);
                        
                        if($audit_data->audit_type == 'store'){
                            $array[] = $present_store;
                        }else{
                            $array[] = $present_warehouse;
                        }
                        
                        $array[] = $inv_type;
                        
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $audit_data = $this->getAuditData($audit_id);
            
            return view('audit/audit_scan_inventory',array('error_message'=>'','audit_data'=>$audit_data));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine()),500);
            }else{
                return view('audit/audit_scan_inventory',array('error_message' =>$e->getMessage()));
            }
        }
    }
    
    function auditScanBulkInventory(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            $inventory_scanned = $inventory_not_in_system = $inventory_already_scanned = array();
            
            $audit_data = $this->getAuditData($audit_id);
            
            if(!empty($data['upload_submit'])){
                
                $validator = Validator::make($request->all(), ['piece_barcode_file' => 'required|mimes:txt|max:2048']);
                if($validator->fails()) {
                    return redirect('audit/inventory/scan/bulk/'.$audit_id)->withErrors($validator)->withInput();
                }
                
                $barcode_file = $request->file('piece_barcode_file');
                $file_name_text = substr($barcode_file->getClientOriginalName(),0,strpos($barcode_file->getClientOriginalName(),'.'));
                $file_ext = $barcode_file->getClientOriginalExtension();

                $file_name = $file_name_text.'_'.rand(1000,100000).'.'.$file_ext;

                $dest_folder = 'documents/scan_files';
                $barcode_file->move(public_path($dest_folder), $file_name);
                
                $barcodes = file(public_path($dest_folder).'/'.$file_name);
                
                for($i=0;$i<count($barcodes);$i++){
                    if(!empty(trim($barcodes[$i]))){
                        $barcodes[$i] = trim($barcodes[$i]);
                    }
                }
                
                $barcodes = array_values(array_unique($barcodes));
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($barcodes);$i++){
                    $barcode = $barcodes[$i];
                    
                    // Check product start
                    $product_data = \DB::table('pos_product_master_inventory as ppmi')
                    ->where('ppmi.peice_barcode',$barcode)
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.fake_inventory',0) 
                    ->where('ppmi.status',1)
                    ->select('ppmi.*')->first();

                    if(empty($product_data)){
                        $inventory_not_in_system[] = $barcode;
                        continue;
                    }

                    // Check product end

                    // Insert product start
                    $audit_inventory_prod = Audit_inventory::where('audit_id',$data['audit_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->first();
                    if(!empty($audit_inventory_prod) && $audit_inventory_prod->scan_status == 1){
                        $inventory_already_scanned[] = $product_data->id;
                        continue;
                    }        

                    if($product_data->product_status == 4){
                        if(!empty($audit_inventory_prod)){
                            // present in system and in store
                            $updateArray = array('scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_store'=>1);
                            Audit_inventory::where('id',$audit_inventory_prod->id)->update($updateArray);
                        }else{
                            // not present in system, but present in store
                            $insertArray = array('audit_id'=>$data['audit_id'],'inventory_id'=>$product_data->id,'store_id'=>$product_data->store_id,'product_status'=>$product_data->product_status,'scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_system'=>0,'present_store'=>1);
                            Audit_inventory::create($insertArray);
                        }
                        
                        $inventory_scanned[] = $product_data->id;
                    }else{
                        // not present in system, but present in store
                        $insertArray = array('audit_id'=>$data['audit_id'],'inventory_id'=>$product_data->id,'store_id'=>$product_data->store_id,'product_status'=>$product_data->product_status,'scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_system'=>0,'present_store'=>1);
                        Audit_inventory::create($insertArray);
                        
                        $inventory_scanned[] = $product_data->id;
                    }
                }
                
                \DB::commit();
                
                if(!empty($inventory_not_in_system)){
                    /*$inventory_not_in_system = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')->wherein('ppmi.id',$inventory_not_in_system)->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)->select('ppmi.*','ppm.product_sku')->orderByRaw('ppm.product_sku,ppmi.peice_barcode')->get()->toArray();*/
                }
                
                if(!empty($inventory_already_scanned)){
                    $inventory_already_scanned = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->wherein('ppmi.id',$inventory_already_scanned)->where('ppmi.is_deleted',0)
                    ->where('ppmi.fake_inventory',0) 
                    ->where('ppm.fake_inventory',0)         
                    ->where('ppm.is_deleted',0)
                    ->select('ppmi.*','ppm.product_sku')->orderByRaw('ppm.product_sku,ppmi.peice_barcode')
                    ->get()->toArray();
                }
                
                if(!empty($inventory_scanned)){
                    $inventory_scanned = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->wherein('ppmi.id',$inventory_scanned)
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.fake_inventory',0) 
                    ->where('ppm.fake_inventory',0)         
                    ->where('ppm.is_deleted',0)
                    ->select('ppmi.*','ppm.product_sku')
                    ->orderByRaw('ppm.product_sku,ppmi.peice_barcode')
                    ->get()->toArray();
                }
                
                unlink(public_path($dest_folder).'/'.$file_name);
            }
            
            return view('audit/audit_scan_bulk_inventory',array('error_message' =>'','audit_data'=>$audit_data,'inventory_not_in_system'=>$inventory_not_in_system,'inventory_already_scanned'=>$inventory_already_scanned,'inventory_scanned'=>$inventory_scanned));
        
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('audit/audit_scan_bulk_inventory',array('error_message' =>$e->getMessage().', Line: '.$e->getLine()));
            }
        }
    }
    
    function auditScanInventoryDetail(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            $audit_inventory = array();
            
            $audit_data = $this->getAuditData($audit_id);
            
            $inv_status = array('inv_total'=>0,'inv_in_system'=>0,'inv_in_store'=>0,'inv_in_both_system_store'=>0,'inv_only_in_system'=>0,'inv_only_in_store'=>0);
            
            $audit_inventory = Audit_inventory::where('audit_id',$audit_id)->where('is_deleted',0)->get()->toArray();
            $inv_status['inv_total'] = count($audit_inventory);
            for($i=0;$i<count($audit_inventory);$i++){
                if($audit_inventory[$i]['present_system'] == 1){
                    $inv_status['inv_in_system']+=1;
                }
                if($audit_inventory[$i]['present_store'] == 1){
                    $inv_status['inv_in_store']+=1;
                }
                if($audit_inventory[$i]['present_system'] == 1 && $audit_inventory[$i]['present_store'] == 1){
                    $inv_status['inv_in_both_system_store']+=1;
                }
                if($audit_inventory[$i]['present_system'] == 1 && $audit_inventory[$i]['present_store'] == 0){
                    $inv_status['inv_only_in_system']+=1;
                }
                if($audit_inventory[$i]['present_system'] == 0 && $audit_inventory[$i]['present_store'] == 1){
                    $inv_status['inv_only_in_store']+=1;
                }
            }
            
            $store_list = \DB::table('audit_inventory as ai')
            ->join('store as s','ai.store_id', '=', 's.id')
            ->where('ai.audit_id',$audit_id)        
            ->where('ai.is_deleted',0)
            ->where('s.is_deleted',0)       
            ->groupBy('s.id')        
            ->selectRaw('s.id,s.store_name,COUNT(ai.id) as inv_count')->get()->toArray();
            
            $status_list = \DB::table('audit_inventory as ai')
            ->where('ai.audit_id',$audit_id)        
            ->where('ai.is_deleted',0)
            ->groupBy('ai.product_status')        
            ->selectRaw('ai.product_status,COUNT(ai.id) as inv_count')->get()->toArray();
            
            for($i=0;$i<count($status_list);$i++){
                $status_list[$i]->status_name = CommonHelper::getposProductStatusName($status_list[$i]->product_status);
            }
            
            return view('audit/audit_scan_inventory_detail',array('error_message'=>'','audit_data'=>$audit_data,'audit_inventory'=>$audit_inventory,'inv_status'=>$inv_status,'store_list'=>$store_list,'status_list'=>$status_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return view('audit/audit_scan_inventory_detail',array('error_message' =>$e->getMessage()));
        }
    }
    
    function auditInventoryVarianceReport(Request $request,$id,$type_id = 1){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            $report_type_id = $type_id;
            $category_inventory = $sku_inventory = $qrcode_inventory = array();
            
            $audit_data = $this->getAuditData($audit_id);
            
            if($report_type_id == 1){
                
                $category_list = $audit_inventory_system = $audit_inventory_store =  array();
                
                $audit_inventory_present_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')               
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->groupByRaw('ppm.category_id')        
                ->selectRaw("dlim_1.id as category_id,dlim_1.name as category_name,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                $audit_inventory_present_store = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')               
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_store',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->groupByRaw('ppm.category_id')        
                ->selectRaw("dlim_1.id as category_id,dlim_1.name as category_name,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                for($i=0;$i<count($audit_inventory_present_system);$i++){
                    $category_list[] = array('id'=>$audit_inventory_present_system[$i]->category_id,'name'=>$audit_inventory_present_system[$i]->category_name);
                    $audit_inventory_system[$audit_inventory_present_system[$i]->category_id] = $audit_inventory_present_system[$i];
                }
                
                for($i=0;$i<count($audit_inventory_present_store);$i++){
                    if(!CommonHelper::DBArrayExists($category_list,'id',$audit_inventory_present_store[$i]->category_id)){
                        $category_list[] = array('id'=>$audit_inventory_present_store[$i]->category_id,'name'=>$audit_inventory_present_store[$i]->category_name);
                    }
                    $audit_inventory_store[$audit_inventory_present_store[$i]->category_id] = $audit_inventory_present_store[$i];
                }

                $view_data = array('error_message'=>'','category_list'=>$category_list,'audit_data'=>$audit_data,'report_type_id'=>$report_type_id,'audit_inventory_system'=>$audit_inventory_system,'audit_inventory_store'=>$audit_inventory_store);
                
                if(isset($data['action']) && $data['action'] == 'download_pdf'){
                    $pdf = PDF::loadView('audit/audit_variance_report_pdf', $view_data);
                    return $pdf->download('audit_variance_report_pdf');
                }
                
                if(isset($data['action']) && $data['action'] == 'get_report_data'){
                    return $view_data;
                }
                
                return view('audit/audit_variance_report',$view_data);
            }
            
            if($report_type_id == 2){
                
                $sku_list = $audit_inventory_system = $audit_inventory_store =  array();
                
                $audit_inventory_present_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                ->leftJoin('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->groupByRaw('ppm.id')        
                //->selectRaw("poi.id as sku_id,poi.vendor_sku,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")
                ->selectRaw("ppm.id as sku_id,poi.vendor_sku,ppm.product_sku,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")        
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                $audit_inventory_present_store = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                ->leftJoin('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_store',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->groupByRaw('ppm.id')              
                ->selectRaw("ppm.id as sku_id,poi.vendor_sku,ppm.product_sku,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")        
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                for($i=0;$i<count($audit_inventory_present_system);$i++){
                    //$sku_list[] = array('id'=>$audit_inventory_present_system[$i]->sku_id,'name'=>$audit_inventory_present_system[$i]->vendor_sku);
                    $sku = (!empty($audit_inventory_present_system[$i]->vendor_sku))?$audit_inventory_present_system[$i]->vendor_sku:$audit_inventory_present_system[$i]->product_sku;
                    
                    $sku_list[] = array('id'=>$audit_inventory_present_system[$i]->sku_id,'name'=>$sku);
                    $audit_inventory_system[$audit_inventory_present_system[$i]->sku_id] = $audit_inventory_present_system[$i];
                }
                
                for($i=0;$i<count($audit_inventory_present_store);$i++){
                    $sku = (!empty($audit_inventory_present_store[$i]->vendor_sku))?$audit_inventory_present_store[$i]->vendor_sku:$audit_inventory_present_store[$i]->product_sku;
                    
                    if(!CommonHelper::DBArrayExists($sku_list,'id',$audit_inventory_present_store[$i]->sku_id)){
                        //$sku_list[] = array('id'=>$audit_inventory_present_store[$i]->sku_id,'name'=>$audit_inventory_present_store[$i]->vendor_sku);
                        $sku_list[] = array('id'=>$audit_inventory_present_store[$i]->sku_id,'name'=>$sku);
                    }
                    $audit_inventory_store[$audit_inventory_present_store[$i]->sku_id] = $audit_inventory_present_store[$i];
                }

                $view_data = array('error_message'=>'','sku_list'=>$sku_list,'audit_data'=>$audit_data,'report_type_id'=>$report_type_id,'audit_inventory_system'=>$audit_inventory_system,'audit_inventory_store'=>$audit_inventory_store);

                if(isset($data['action']) && $data['action'] == 'download_pdf'){
                    $pdf = PDF::loadView('audit/audit_variance_report_pdf', $view_data);
                    return $pdf->download('audit_variance_report_pdf');
                }
                
                return view('audit/audit_variance_report',$view_data);
                
            }
            
            if($report_type_id == 3){
                $barcode_list = $audit_inventory_system = $audit_inventory_store =  array();
                
                $audit_inventory_present_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0)         
                ->selectRaw("ppmi.peice_barcode,1 as inv_count,ppmi.sale_price as inv_price,ppmi.base_price as inv_cost_price")
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                $audit_inventory_present_store = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_store',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->selectRaw("ppmi.peice_barcode,1 as inv_count,ppmi.sale_price as inv_price,ppmi.base_price as inv_cost_price")
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                for($i=0;$i<count($audit_inventory_present_system);$i++){
                    $barcode_list[] = array('id'=>$audit_inventory_present_system[$i]->peice_barcode);
                    $audit_inventory_system[$audit_inventory_present_system[$i]->peice_barcode] = $audit_inventory_present_system[$i];
                }
                
                for($i=0;$i<count($audit_inventory_present_store);$i++){
                    if(!CommonHelper::DBArrayExists($barcode_list,'id',$audit_inventory_present_store[$i]->peice_barcode)){
                        $barcode_list[] = array('id'=>$audit_inventory_present_store[$i]->peice_barcode);
                    }
                    $audit_inventory_store[$audit_inventory_present_store[$i]->peice_barcode] = $audit_inventory_present_store[$i];
                }

                $view_data = array('error_message'=>'','barcode_list'=>$barcode_list,'audit_data'=>$audit_data,'report_type_id'=>$report_type_id,'audit_inventory_system'=>$audit_inventory_system,'audit_inventory_store'=>$audit_inventory_store);

                if(isset($data['action']) && $data['action'] == 'download_pdf'){
                    $pdf = PDF::loadView('audit/audit_variance_report_pdf', $view_data);
                    return $pdf->download('audit_variance_report_pdf');
                }
                
                return view('audit/audit_variance_report',$view_data);
            }
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return view('audit/audit_variance_report',array('error_message' =>$e->getMessage().'. Line:'.$e->getLine()));
        }
    }
    
    function auditInventoryMismatchReport(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            
            $qrcode_inventory = array('inv_count_mismatch_store'=>0,'inv_price_mismatch_store'=>0,'inv_count_mismatch_system'=>0,'inv_price_mismatch_system'=>0);
            $inv_qrcodes_mismatch_store = $inv_qrcodes_mismatch_system = array();
            
            $audit_data = $this->getAuditData($audit_id);
            
            $audit_inventory = \DB::table('audit_inventory as ai')
            ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
            ->where('ai.audit_id',$audit_id)        
            ->where('ai.is_deleted',0)
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0) 
            ->selectRaw("ppmi.peice_barcode as qrcode,ai.*,ppmi.sale_price as inv_price")
            ->orderBy('inv_price','DESC')->get()->toArray();

            for($i=0;$i<count($audit_inventory);$i++){
                if($audit_inventory[$i]->present_system == 1 && $audit_inventory[$i]->present_store == 0){
                    $qrcode_inventory['inv_count_mismatch_store']+=1; 
                    $qrcode_inventory['inv_price_mismatch_store']+=$audit_inventory[$i]->inv_price;
                    $inv_qrcodes_mismatch_store[] = $audit_inventory[$i]->qrcode;
                }

                if($audit_inventory[$i]->present_system == 0 && $audit_inventory[$i]->present_store == 1){
                    $qrcode_inventory['inv_count_mismatch_system']+=1; 
                    $qrcode_inventory['inv_price_mismatch_system']+=$audit_inventory[$i]->inv_price;
                    $inv_qrcodes_mismatch_system[] = $audit_inventory[$i]->qrcode;
                }
            }
            
            $qrcode_inventory['inv_qrcodes_mismatch_store'] = $inv_qrcodes_mismatch_store;
            $qrcode_inventory['inv_qrcodes_mismatch_system'] = $inv_qrcodes_mismatch_system;

            $view_data = array('error_message'=>'','qrcode_inventory'=>$qrcode_inventory,'audit_data'=>$audit_data);

            if(isset($data['action']) && $data['action'] == 'download_pdf'){
                $pdf = PDF::loadView('audit/audit_mismatch_report_pdf', $view_data);
                return $pdf->download('audit_mismatch_report_pdf');
            }

            return view('audit/audit_mismatch_report',$view_data);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return view('audit/audit_mismatch_report',array('error_message' =>$e->getMessage().'. Line:'.$e->getLine()));
        }
    }
    
    function createBill(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            $create_bill = 0;
            $discount = $gst = '';
            
            // Inventory present only in system
            $audit_inv_system = \DB::table('audit_inventory as ai')
            ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')     
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->leftJoin('store as s','s.id', '=', 'ppmi.store_id')                     
            ->where('ai.audit_id',$audit_id)  
            ->where('ai.present_system',1)        
            ->where('ai.present_store',0)     
            ->where('ppmi.product_status',4)                        
            ->where('ai.is_deleted',0)
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0) 
            ->where('ppm.fake_inventory',0)         
            ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','s.store_name','ppm.hsn_code')
            ->orderBy('ai.id','DESC')
            ->get()->toArray();
            
            // Inventory present only in store and sold from system
            $audit_inv_store = \DB::table('audit_inventory as ai')
            ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('pos_customer_orders_detail as pcod',function($join){
            $join->on('ppmi.id','=','pcod.inventory_id')->on('ppmi.customer_order_id','=','pcod.order_id')->where('pcod.is_deleted','=','0')->where('pcod.order_status',1);})        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')     
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->leftJoin('store as s','s.id', '=', 'ppmi.store_id')                     
            ->where('ai.audit_id',$audit_id)  
            ->where('ai.present_system',0)        
            ->where('ai.present_store',1)     
            ->where('ppmi.product_status',5)                
            ->where('ai.is_deleted',0)
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0) 
            ->where('ppm.fake_inventory',0)         
            ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','s.store_name','pcod.net_price')
            ->orderBy('ai.id','DESC')
            ->get()->toArray();
            
            if(isset($data['calculate_bill']) && !empty($data['calculate_bill'])){
                $discount = (isset($data['discount']) && $data['discount'] > 0)?trim($data['discount']):0;
                $gst = (isset($data['gst']) && !empty($data['gst']))?trim($data['gst']):'exc';
                
                for($i=0;$i<count($audit_inv_system);$i++){
                    $sale_price = $audit_inv_system[$i]->sale_price;
                    $hsn_code = $audit_inv_system[$i]->hsn_code;
                    $discount_amount = round($sale_price*($discount/100),2);
                    $discounted_amount = $sale_price-$discount_amount;
                    if($gst == 'exc'){
                        $gst_data = CommonHelper::getGSTData($hsn_code,$discounted_amount);
                        $gst_percent = (!empty($gst_data))?$gst_data->rate_percent:0;
                        $gst_amount = $discounted_amount*($gst_percent/100);
                    }else{
                        $gst_percent = $gst_amount = 0;
                    }
                    
                    $net_price = $discounted_amount+$gst_amount;
                    $audit_inv_system[$i]->net_price = $net_price;
                }        
                
                $create_bill = 1;
            }
            
            $audit_data = $this->getAuditData($audit_id);
            
            return view('audit/audit_create_bill',array('error_message'=>'','audit_data'=>$audit_data,'audit_inv_system'=>$audit_inv_system,
            'audit_inv_store'=>$audit_inv_store,'discount'=>$discount,'gst'=>$gst,'create_bill'=>$create_bill));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return view('audit/audit_create_bill',array('error_message' =>$e->getMessage().'. Line:'.$e->getLine()));
        }
    }
    
    function submitCreateBill(Request $request,$id){
        try{ 
            ini_set('memory_limit', '-1');
            set_time_limit(600);
            
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            $id_array = array();
            
            if(isset($data['action']) && $data['action'] == 'create_bill'){
                
                \DB::beginTransaction();
                
                $system_net_price = $store_net_price = 0;
                $data['discount'] = trim(str_replace('%', '', $data['discount']));
                
                $audit_inv_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',1)        
                ->where('ai.present_store',0)    
                ->where('ppmi.product_status',4)                        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.hsn_code')
                ->orderBy('ai.id','DESC')
                ->get()->toArray();
            
                // Inventory present only in store and sold from store
                $audit_inv_store = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('pos_customer_orders_detail as pcod',function($join){
                $join->on('ppmi.id','=','pcod.inventory_id')->on('ppmi.customer_order_id','=','pcod.order_id')->where('pcod.is_deleted','=','0')->where('pcod.order_status',1);})        
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',0)        
                ->where('ai.present_store',1)     
                ->where('ppmi.product_status',5)                
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','pcod.net_price')
                ->orderBy('ai.id','DESC')
                ->get()->toArray();
                
                for($i=0;$i<count($audit_inv_system);$i++){
                    $id_array[] = $audit_inv_system[$i]->id;
                    
                    $sale_price = $audit_inv_system[$i]->sale_price;
                    $hsn_code = $audit_inv_system[$i]->hsn_code;
                    $discount_amount = round($sale_price*($data['discount']/100),2);
                    $discounted_amount = $sale_price-$discount_amount;
                    if($data['gst'] == 'exc'){
                        $gst_data = CommonHelper::getGSTData($hsn_code,$discounted_amount);
                        $gst_percent = (!empty($gst_data))?$gst_data->rate_percent:0;
                        $gst_amount = $discounted_amount*($gst_percent/100);
                    }else{
                        $gst_percent = $gst_amount = 0;
                    }
                    
                    $net_price = $discounted_amount+$gst_amount;
                    $audit_inv_system[$i]->net_price = $net_price;
                    $system_net_price+=$net_price;
                }
                
                for($i=0;$i<count($audit_inv_store);$i++){
                    $id_array[] = $audit_inv_store[$i]->id;
                    $store_net_price+=$audit_inv_store[$i]->net_price;
                }
                
                $id_list = implode(',',$id_array);
                
                $audit_data = $this->getAuditData($audit_id);
                $store_id = $audit_data->store_id;
                $store_user = \DB::table('store_users')->where('store_id',$store_id)->where('is_deleted',0)->first();
                $gst_inclusive = ($data['gst'] == 'inc')?1:0;
                $bill_discount = trim($data['discount']);
                $cashAmtValue = $system_net_price-$store_net_price;
                
                // Get customer details from phone no if exists else create new customer
                $pos_customer = Pos_customer::where('phone',$data['customer_phone_new'])->where('is_deleted',0)->first();
                if(!empty($pos_customer)){
                    $customer_id = $pos_customer->id;
                }else{
                    $customer_data = ['customer_phone_new'=>$data['customer_phone_new'],'customer_salutation'=>$data['customer_salutation'],'customer_name'=>$data['customer_name'],'customer_email'=>null,'customer_postal_code'=>null];
                    $response_data = CommonHelper::addPosCustomer($customer_data);
                    if(strtolower($response_data['status']) == 'success'){
                        $pos_customer = $response_data['customer_data'];
                        $customer_id = $pos_customer->id;
                    }else{
                        return response($response_data);
                    }
                }
                
                $bill_data = array('ids'=>$id_list,'user_id'=>$store_user->user_id,'discount_percent'=>$bill_discount,'gst_inclusive'=>$gst_inclusive,
                'cashAmtValue'=>$cashAmtValue,'cardAmtValue'=>0,'WalletAmtValue'=>0,'voucherAmount'=>0,'customer_id'=>$customer_id,'order_source'=>'web','store_id'=>$store_id);
                
                $response_data = CommonHelper::createPosOrder($bill_data);
                
                $response_array = json_decode(json_encode($response_data),true);
                
                if($response_array['original']['status'] == 'success'){
                    $order_data = $response_array['original']['order_data'];
                
                    $updateArray = array('order_type'=>'auditor');
                    Pos_customer_orders::where('id',$order_data['id'])->update($updateArray);
                
                    Audit::where('id',$audit_id)->update(['pos_order_id'=>$order_data['id']]);
                }
                
                \DB::commit();
                
                return $response_data;
                
            }
            
            if(isset($data['action']) && $data['action'] == 'calculate_discount'){
                
                $validateionRules = array('required_bill_amount'=>'required|numeric|min:0','discount_gst_type'=>'required');

                $attributes = array('required_bill_amount'=>'Bill Amount','discount_gst_type'=>'GST Type');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $inv_system_sale_price = $inv_store_net_price = 0;
                $gst_type = trim($data['discount_gst_type']);
                $required_bill_amount = trim($data['required_bill_amount']);
                
                $audit_inv_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',1)        
                ->where('ai.present_store',0)    
                ->where('ppmi.product_status',4)                        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.hsn_code')
                ->orderBy('ai.id','DESC')
                ->get()->toArray();
            
                // Inventory present only in store and sold from store
                $audit_inv_store = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('pos_customer_orders_detail as pcod',function($join){
                $join->on('ppmi.id','=','pcod.inventory_id')->on('ppmi.customer_order_id','=','pcod.order_id')->where('pcod.is_deleted','=','0')->where('pcod.order_status',1);})        
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',0)        
                ->where('ai.present_store',1)     
                ->where('ppmi.product_status',5)                
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','pcod.net_price')
                ->orderBy('ai.id','DESC')
                ->get()->toArray();
                
                for($i=0;$i<count($audit_inv_system);$i++){
                    $sale_price = $audit_inv_system[$i]->sale_price;
                    $inv_system_sale_price+=$sale_price; 
                }
                
                for($i=0;$i<count($audit_inv_store);$i++){
                    $inv_store_net_price+=$audit_inv_store[$i]->net_price;
                }
                
                if($gst_type == 'inc'){
                    //Store net price is added in required bill amount as it will be deducted later
                    $discount = ($required_bill_amount+$inv_store_net_price)/$inv_system_sale_price;
                    $discount = (abs(1-$discount))*100;
                    $discount = round($discount,5);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Discount Calculated: '.$discount.' %','discount'=>$discount),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line:'.$e->getLine()),500);
        }
    }
    
    function auditScanWarehouseInventory(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                
                // Check product start
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])
                ->where('ppmi.is_deleted',0)
                ->where('ppm.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)        
                ->where('ppmi.status',1)
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name',
                'psc.size as size_name')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists' ));
                }
                
                // Check product end
                
                // Insert product start
                $audit_inventory_prod = Audit_inventory::where('audit_id',$data['audit_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->first();
                if(!empty($audit_inventory_prod) && $audit_inventory_prod->scan_status == 1){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> already added ', 'errors' =>'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> already added ' ));
                }        
                
                
                
                if($product_data->product_status == 1){
                    if(!empty($audit_inventory_prod)){
                        // present in system and in warehouse
                        $updateArray = array('scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_warehouse'=>1);
                        Audit_inventory::where('id',$audit_inventory_prod->id)->update($updateArray);
                        $product_data->present_system = 1;
                    }else{
                        // not present in system, but present in warehouse
                        $insertArray = array('audit_id'=>$data['audit_id'],'inventory_id'=>$product_data->id,'store_id'=>0,'product_status'=>$product_data->product_status,'scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_system'=>0,'present_warehouse'=>1);
                        Audit_inventory::create($insertArray);
                        $product_data->present_system = 0;
                    }
                }else{
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> status is not Available in Warehouse ', 'errors' =>'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> status is not Available in Warehouse ' ));
                    
                    /*if(!empty($audit_inventory_prod)){
                        // present in system, present in warehouse, but inventory status modified by demand or other
                        $updateArray = array('scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_warehouse'=>1,'present_system'=>1,'product_status'=>$product_data->product_status);
                        Audit_inventory::where('id',$audit_inventory_prod->id)->update($updateArray);
                        $product_data->present_system = 1;
                    }else{
                        // not present in system, but present in warehouse
                        $insertArray = array('audit_id'=>$data['audit_id'],'inventory_id'=>$product_data->id,'store_id'=>0,'product_status'=>$product_data->product_status,'scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_system'=>0,'present_warehouse'=>1);
                        Audit_inventory::create($insertArray);
                        $product_data->present_system = 0;
                    }*/
                }
                
                $product_data->present_warehouse = 1;
                
                // Insert product end
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Product with code: <b>'.$product_data->peice_barcode.'</b> added ','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_audit_inventory'){
                $rec_per_page = 500;
                $barcode_list = array();
                $scan_status = explode(',',$data['scan_status']);
                
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('audit_inventory as ai','ppmi.id', '=', 'ai.inventory_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->leftJoin('store as s','s.id', '=', 'ai.store_id')        
                ->where('ai.audit_id',$data['audit_id'])        
                ->wherein('ai.scan_status',$scan_status)       
                ->where('ppmi.is_deleted',0)
                ->where('ppm.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)        
                ->where('ai.is_deleted',0);
                
                if(isset($data['product_status_search']) && !empty($data['product_status_search'])){
                    $product_list = $product_list->where('ai.product_status',$data['product_status_search']);
                }
                
                if(isset($data['inv_status_search']) && !empty($data['inv_status_search'])){
                    if($data['inv_status_search'] == 2){
                        $product_list = $product_list->where('ai.present_system',1);
                    }elseif($data['inv_status_search'] == 3){
                        $product_list = $product_list->where('ai.present_warehouse',1);
                    }elseif($data['inv_status_search'] == 4){
                        $product_list = $product_list->where('ai.present_system',1)->where('ai.present_warehouse',1);
                    }elseif($data['inv_status_search'] == 5){
                        $product_list = $product_list->where('ai.present_system',1)->where('ai.present_warehouse',0);
                    }elseif($data['inv_status_search'] == 6){
                        $product_list = $product_list->where('ai.present_system',0)->where('ai.present_warehouse',1);
                    }elseif($data['inv_status_search'] == 7){
                        $product_list = $product_list->where('ai.present_system',1)->where('ai.present_warehouse',1)->where('ai.product_status','!=',1);
                    }
                }
                
                $product_list = $product_list->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name',
                'psc.size as size_name','ai.scan_date','s.store_name','ai.present_system','ai.present_warehouse')->orderBy('scan_date','ASC')->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $inv_imported = Audit_inventory::where('audit_id',$data['audit_id'])->where('scan_status','=','1')->where('is_deleted',0)->count();
                $inv_total = Audit_inventory::where('audit_id',$data['audit_id'])->where('is_deleted',0)->count();
                
                $inventory_count_data = array('inv_total'=>$inv_total,'inv_imported'=>$inv_imported);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'inventory_count_data'=>$inventory_count_data,'barcode_list'=>$barcode_list),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'complete_audit_scan'){
                \DB::beginTransaction();
                
                $inv_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ai.audit_id',$data['audit_id'])        
                ->where('ai.present_system',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->selectRaw("COUNT(ai.id) as inv_count,SUM(ppmi.sale_price) as total_sale_price,SUM(ppmi.base_price) as total_base_price")
                ->first();
                
                $inv_warehouse = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ai.audit_id',$data['audit_id'])        
                ->where('ai.present_warehouse',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->selectRaw("COUNT(ai.id) as inv_count,SUM(ppmi.sale_price) as total_sale_price,SUM(ppmi.base_price) as total_base_price")
                ->first();
                
                
                
                $updateArray = array('audit_status'=>'scan_completed','scan_complete_date'=>date('Y/m/d H'),'scan_complete_comment'=>$data['comments'],'system_inv_quantity'=>$inv_system->inv_count,
                'system_inv_cost_price'=>$inv_system->total_base_price,'system_inv_sale_price'=>$inv_system->total_sale_price,'store_inv_quantity'=>$inv_warehouse->inv_count,
                'store_inv_cost_price'=>$inv_warehouse->total_base_price,'store_inv_sale_price'=>$inv_warehouse->total_sale_price);

                Audit::where('id',$data['audit_id'])->update($updateArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit Scan completed'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_audit_inv_items'){
                $audit_items = Audit_inventory::where('audit_id',$data['audit_id'])->wherein('inventory_id',$data['deleteChk'])->where('is_deleted',0)->get()->toArray();
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($audit_items);$i++){
                    if($audit_items[$i]['present_system'] == 1){
                        $updateArray = array('scan_status'=>0,'scan_date'=>null,'present_warehouse'=>0);
                    }else{
                        $updateArray = array('is_deleted'=>1);
                    }
                    Audit_inventory::where('id',$audit_items[$i]['id'])->where('is_deleted',0)->update($updateArray);
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Audit items deleted successfully'),200);
            }
            
            $audit_data = $this->getAuditData($audit_id);
            
            return view('audit/audit_scan_wh_inventory',array('error_message'=>'','audit_data'=>$audit_data));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine()),500);
            }else{
                return view('audit/audit_scan_wh_inventory',array('error_message' =>$e->getMessage()));
            }
        }
    }
    
    function auditScanBulkWarehouseInventory(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            $inventory_scanned = $inventory_not_in_system = $inventory_already_scanned = array();
            
            $audit_data = $this->getAuditData($audit_id);
            
            if(!empty($data['upload_submit'])){
                
                $validator = Validator::make($request->all(), ['piece_barcode_file' => 'required|mimes:txt|max:5120']);
                if($validator->fails()) {
                    return redirect('audit/inventory/scan/bulk/wh/'.$audit_id)->withErrors($validator)->withInput();
                }
                
                $barcode_file = $request->file('piece_barcode_file');
                $file_name_text = substr($barcode_file->getClientOriginalName(),0,strpos($barcode_file->getClientOriginalName(),'.'));
                $file_ext = $barcode_file->getClientOriginalExtension();

                $file_name = $file_name_text.'_'.rand(1000,100000).'.'.$file_ext;

                $dest_folder = 'documents/scan_files';
                $barcode_file->move(public_path($dest_folder), $file_name);
                
                $barcodes = file(public_path($dest_folder).'/'.$file_name);
                
                for($i=0;$i<count($barcodes);$i++){
                    if(!empty(trim($barcodes[$i]))){
                        $barcodes[$i] = trim($barcodes[$i]);
                    }
                }
                
                $barcodes = array_values(array_unique($barcodes));
                
                if(count($barcodes) > 10000){
                    unlink(public_path($dest_folder).'/'.$file_name);
                    Session::flash('alert-danger', 'File cannot have more than 10000 Barcodes');
                    return redirect('audit/inventory/scan/bulk/wh/'.$audit_id)->withInput();
                }
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($barcodes);$i++){
                    $barcode = $barcodes[$i];
                    
                    // Check product start
                    $product_data = \DB::table('pos_product_master_inventory as ppmi')
                    ->where('ppmi.peice_barcode',$barcode)
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.fake_inventory',0) 
                    ->where('ppmi.status',1)
                    ->select('ppmi.*')->first();

                    if(empty($product_data)){
                        $inventory_not_in_system[] = $barcode;
                        continue;
                    }

                    // Check product end

                    // Insert product start
                    $audit_inventory_prod = Audit_inventory::where('audit_id',$data['audit_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->first();
                    if(!empty($audit_inventory_prod) && $audit_inventory_prod->scan_status == 1){
                        $inventory_already_scanned[] = $product_data->id;
                        continue;
                    }        

                    if($product_data->product_status == 1){
                        if(!empty($audit_inventory_prod)){
                            // present in system and in warehouse
                            $updateArray = array('scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_warehouse'=>1);
                            Audit_inventory::where('id',$audit_inventory_prod->id)->update($updateArray);
                        }else{
                            // not present in system, but present in warehouse
                            $insertArray = array('audit_id'=>$data['audit_id'],'inventory_id'=>$product_data->id,'store_id'=>0,'product_status'=>$product_data->product_status,'scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_system'=>0,'present_warehouse'=>1);
                            Audit_inventory::create($insertArray);
                        }
                        $inventory_scanned[] = $product_data->id;
                    }else{
                        /*if(!empty($audit_inventory_prod)){
                            // present in system, present in warehouse, but inventory status modified by demand or other
                            $updateArray = array('scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_warehouse'=>1,'present_system'=>1,'product_status'=>$product_data->product_status);
                            Audit_inventory::where('id',$audit_inventory_prod->id)->update($updateArray);
                        }else{
                            // not present in system, but present in warehouse
                            $insertArray = array('audit_id'=>$data['audit_id'],'inventory_id'=>$product_data->id,'store_id'=>0,'product_status'=>$product_data->product_status,'scan_status'=>1,'scan_date'=>date('Y/m/d H:i:s'),'present_system'=>0,'present_warehouse'=>1);
                            Audit_inventory::create($insertArray);
                        }*/
                    }
                    
                    //$inventory_scanned[] = $product_data->id;
                }
                
                \DB::commit();
                
                if(!empty($inventory_not_in_system)){
                }
                
                if(!empty($inventory_already_scanned)){
                    $inventory_already_scanned = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->wherein('ppmi.id',$inventory_already_scanned)
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.fake_inventory',0) 
                    ->where('ppm.fake_inventory',0)         
                    ->where('ppm.is_deleted',0)
                    ->select('ppmi.*','ppm.product_sku')
                    ->orderByRaw('ppm.product_sku,ppmi.peice_barcode')
                    ->get()->toArray();
                }
                
                if(!empty($inventory_scanned)){
                    $inventory_scanned = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->wherein('ppmi.id',$inventory_scanned)
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.fake_inventory',0) 
                    ->where('ppm.fake_inventory',0)         
                    ->where('ppm.is_deleted',0)
                    ->select('ppmi.*','ppm.product_sku')
                    ->orderByRaw('ppm.product_sku,ppmi.peice_barcode')
                    ->get()->toArray();
                }
                
                unlink(public_path($dest_folder).'/'.$file_name);
            }
            
            return view('audit/audit_scan_bulk_wh_inventory',array('error_message' =>'','audit_data'=>$audit_data,'inventory_not_in_system'=>$inventory_not_in_system,'inventory_already_scanned'=>$inventory_already_scanned,'inventory_scanned'=>$inventory_scanned));
        
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('audit/audit_scan_bulk_wh_inventory',array('error_message' =>$e->getMessage().', Line: '.$e->getLine()));
            }
        }
    }
    
    
    function auditScanWarehouseInventoryDetail(Request $request,$id){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            $audit_inventory = array();
            
            $audit_data = $this->getAuditData($audit_id);
            
            
            $inv_data = \DB::table('audit_inventory as ai')
            ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->where('ai.audit_id',$audit_id)        
            ->where('ai.is_deleted',0)
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0) 
            ->where('ppm.fake_inventory',0)         
            ->selectRaw('COUNT(ai.id) as inv_count');
            
            $inv_total = clone ($inv_data);
            $inv_total = $inv_total->first();
            
            $inv_system = clone ($inv_data);
            $inv_system = $inv_system->where('ai.present_system',1)->first();
            
            $inv_wh = clone ($inv_data);
            $inv_wh = $inv_wh->where('ai.present_warehouse',1)->first();
            
            $inv_both_system_wh = clone ($inv_data);
            $inv_both_system_wh = $inv_both_system_wh->where('ai.present_system',1)->where('ai.present_warehouse',1)->first();
            
            $inv_only_system = clone ($inv_data);
            $inv_only_system = $inv_only_system->where('ai.present_system',1)->where('ai.present_warehouse',0)->first();
            
            $inv_only_wh = clone ($inv_data);
            $inv_only_wh = $inv_only_wh->where('ai.present_system',0)->where('ai.present_warehouse',1)->first();
            
            $inv_both_system_wh_diff_status = clone ($inv_data);
            $inv_both_system_wh_diff_status = $inv_both_system_wh_diff_status->where('ai.present_system',1)->where('ai.present_warehouse',1)->where('ai.product_status','!=',1)->first();
            
            $inv_status = array('inv_total'=>$inv_total->inv_count,'inv_in_system'=>$inv_system->inv_count,'inv_in_wh'=>$inv_wh->inv_count,'inv_in_both_system_wh'=>$inv_both_system_wh->inv_count,
            'inv_only_in_system'=>$inv_only_system->inv_count,'inv_only_in_wh'=>$inv_only_wh->inv_count,'inv_both_system_wh_diff_status'=>$inv_both_system_wh_diff_status->inv_count);
            
            
            $status_list = \DB::table('audit_inventory as ai')
            ->where('ai.audit_id',$audit_id)        
            ->where('ai.is_deleted',0)
            ->groupBy('ai.product_status')        
            ->selectRaw('ai.product_status,COUNT(ai.id) as inv_count')->get()->toArray();
            
            for($i=0;$i<count($status_list);$i++){
                $status_list[$i]->status_name = CommonHelper::getposProductStatusName($status_list[$i]->product_status);
            }
            
            return view('audit/audit_scan_wh_inventory_detail',array('error_message'=>'','audit_data'=>$audit_data,'audit_inventory'=>$audit_inventory,'inv_status'=>$inv_status,'status_list'=>$status_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return view('audit/audit_scan_wh_inventory_detail',array('error_message' =>$e->getMessage()));
        }
    }
    
    function auditWarehouseInventoryVarianceReport(Request $request,$id,$type_id = 1){
        try{ 
            $data = $request->all(); 
            $user = Auth::user();
            $audit_id = $id;
            $report_type_id = $type_id;
            $category_inventory = $sku_inventory = $qrcode_inventory = array();
            
            $audit_data = $this->getAuditData($audit_id);
            
            if($report_type_id == 1){
                
                $category_list = $audit_inventory_system = $audit_inventory_wh =  array();
                
                $audit_inventory_present_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')               
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->groupByRaw('ppm.category_id')        
                ->selectRaw("dlim_1.id as category_id,dlim_1.name as category_name,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                $audit_inventory_present_wh = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')               
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_warehouse',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->groupByRaw('ppm.category_id')        
                ->selectRaw("dlim_1.id as category_id,dlim_1.name as category_name,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                for($i=0;$i<count($audit_inventory_present_system);$i++){
                    $category_list[] = array('id'=>$audit_inventory_present_system[$i]->category_id,'name'=>$audit_inventory_present_system[$i]->category_name);
                    $audit_inventory_system[$audit_inventory_present_system[$i]->category_id] = $audit_inventory_present_system[$i];
                }
                
                for($i=0;$i<count($audit_inventory_present_wh);$i++){
                    if(!CommonHelper::DBArrayExists($category_list,'id',$audit_inventory_present_wh[$i]->category_id)){
                        $category_list[] = array('id'=>$audit_inventory_present_wh[$i]->category_id,'name'=>$audit_inventory_present_wh[$i]->category_name);
                    }
                    $audit_inventory_wh[$audit_inventory_present_wh[$i]->category_id] = $audit_inventory_present_wh[$i];
                }

                $view_data = array('error_message'=>'','category_list'=>$category_list,'audit_data'=>$audit_data,'report_type_id'=>$report_type_id,'audit_inventory_system'=>$audit_inventory_system,'audit_inventory_wh'=>$audit_inventory_wh);
                
                if(isset($data['action']) && $data['action'] == 'download_pdf'){
                    //return view('audit/audit_wh_variance_report_pdf',$view_data);
                    $pdf = PDF::loadView('audit/audit_wh_variance_report_pdf', $view_data);
                    return $pdf->download('audit_wh_variance_report_pdf');
                }
                
                if(isset($data['action']) && $data['action'] == 'get_report_data'){
                    return $view_data;
                }
                
                return view('audit/audit_wh_variance_report',$view_data);
            }
            
            if($report_type_id == 2){
                
                $sku_list = $audit_inventory_system = $audit_inventory_wh =  array();
                
                $audit_inventory_present_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                ->leftJoin('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->groupByRaw('ppm.id')        
                ->selectRaw("ppm.id as sku_id,poi.vendor_sku,ppm.product_sku,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")        
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                $audit_inventory_present_wh = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                ->leftJoin('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_warehouse',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)         
                ->groupByRaw('ppm.id')              
                ->selectRaw("ppm.id as sku_id,poi.vendor_sku,ppm.product_sku,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_price,SUM(ppmi.base_price) as inv_cost_price")        
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                for($i=0;$i<count($audit_inventory_present_system);$i++){
                    
                    $sku = (!empty($audit_inventory_present_system[$i]->vendor_sku))?$audit_inventory_present_system[$i]->vendor_sku:$audit_inventory_present_system[$i]->product_sku;
                    
                    $sku_list[] = array('id'=>$audit_inventory_present_system[$i]->sku_id,'name'=>$sku);
                    $audit_inventory_system[$audit_inventory_present_system[$i]->sku_id] = $audit_inventory_present_system[$i];
                }
                
                for($i=0;$i<count($audit_inventory_present_wh);$i++){
                    $sku = (!empty($audit_inventory_present_wh[$i]->vendor_sku))?$audit_inventory_present_wh[$i]->vendor_sku:$audit_inventory_present_wh[$i]->product_sku;
                    
                    if(!CommonHelper::DBArrayExists($sku_list,'id',$audit_inventory_present_wh[$i]->sku_id)){
                        
                        $sku_list[] = array('id'=>$audit_inventory_present_wh[$i]->sku_id,'name'=>$sku);
                    }
                    $audit_inventory_wh[$audit_inventory_present_wh[$i]->sku_id] = $audit_inventory_present_wh[$i];
                }

                $view_data = array('error_message'=>'','sku_list'=>$sku_list,'audit_data'=>$audit_data,'report_type_id'=>$report_type_id,'audit_inventory_system'=>$audit_inventory_system,'audit_inventory_wh'=>$audit_inventory_wh);

                if(isset($data['action']) && $data['action'] == 'download_pdf'){
                    $pdf = PDF::loadView('audit/audit_wh_variance_report_pdf', $view_data);
                    return $pdf->download('audit_wh_variance_report_pdf');
                }
                
                return view('audit/audit_wh_variance_report',$view_data);
                
            }
            
            /*if($report_type_id == 3){
                $barcode_list = $audit_inventory_system = $audit_inventory_store =  array();
                
                $audit_inventory_present_system = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_system',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0)         
                ->selectRaw("ppmi.peice_barcode,1 as inv_count,ppmi.sale_price as inv_price,ppmi.base_price as inv_cost_price")
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                $audit_inventory_present_store = \DB::table('audit_inventory as ai')
                ->join('pos_product_master_inventory as ppmi','ai.inventory_id', '=', 'ppmi.id')
                ->where('ai.audit_id',$audit_id)  
                ->where('ai.present_store',1)        
                ->where('ai.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',0) 
                ->selectRaw("ppmi.peice_barcode,1 as inv_count,ppmi.sale_price as inv_price,ppmi.base_price as inv_cost_price")
                ->orderBy('inv_count','DESC')->get()->toArray();
                
                for($i=0;$i<count($audit_inventory_present_system);$i++){
                    $barcode_list[] = array('id'=>$audit_inventory_present_system[$i]->peice_barcode);
                    $audit_inventory_system[$audit_inventory_present_system[$i]->peice_barcode] = $audit_inventory_present_system[$i];
                }
                
                for($i=0;$i<count($audit_inventory_present_store);$i++){
                    if(!CommonHelper::DBArrayExists($barcode_list,'id',$audit_inventory_present_store[$i]->peice_barcode)){
                        $barcode_list[] = array('id'=>$audit_inventory_present_store[$i]->peice_barcode);
                    }
                    $audit_inventory_store[$audit_inventory_present_store[$i]->peice_barcode] = $audit_inventory_present_store[$i];
                }

                $view_data = array('error_message'=>'','barcode_list'=>$barcode_list,'audit_data'=>$audit_data,'report_type_id'=>$report_type_id,'audit_inventory_system'=>$audit_inventory_system,'audit_inventory_store'=>$audit_inventory_store);

                if(isset($data['action']) && $data['action'] == 'download_pdf'){
                    $pdf = PDF::loadView('audit/audit_wh_variance_report_pdf', $view_data);
                    return $pdf->download('audit_wh_variance_report_pdf');
                }
                
                return view('audit/audit_wh_variance_report',$view_data);
            }*/
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'AUDIT',__FUNCTION__,__FILE__);
            return view('audit/audit_wh_variance_report',array('error_message' =>$e->getMessage().'. Line:'.$e->getLine()));
        }
    }
}
