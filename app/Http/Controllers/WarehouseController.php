<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Store_item;
use App\Models\Store_item_detail;
use App\Models\Store_order;
use App\Models\Store_order_detail;
use App\Models\Store_products_demand;
use App\Models\Store_products_demand_detail;
use App\Models\Store_products_demand_inventory;
use App\Models\Design_lookup_items_master;
use App\Models\Pos_product_master;
use App\Models\Pos_product_master_inventory;
use App\Models\Store_products_demand_courier;
use App\Models\Store_products_demand_sku;
use App\Models\Purchase_order;
use App\Models\Purchase_order_details;
use App\Models\Purchase_order_items;
use App\Models\Purchase_order_grn_qc;
use App\Models\Purchase_order_grn_qc_items;
use App\Models\Production_size_counts;
use App\Models\Vendor_detail;
use App\Models\Debit_notes;
use App\Models\Debit_note_items;
use App\Helpers\CommonHelper;
use Validator;
use PDF;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function __construct(){
    }
    
    function dashboard(Request $request){
        try{ 
            return view('warehouse/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE',__FUNCTION__,__FILE__);
            return view('warehouse/dashboard',array('error_message'=>$e->getMessage()));
        }
    }
    
    public function listPosProducts(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            
            $products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('store as s','s.id', '=', 'ppmi.store_id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')   
            ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)->where('ppm.is_deleted',0)->where('ppm.arnon_product',0);
           
            if(isset($data['store_id']) && !empty($data['store_id'])){
                $products_list = $products_list->where('ppmi.store_id',trim($data['store_id']));
            }
            
            if(isset($data['status']) && !empty($data['status'])){
                $products_list = $products_list->where('ppmi.product_status',trim($data['status']));
            }

            $products_list = $products_list->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','dlim_1.name as category_name','dlim_1.name as subcategory_name','s.store_name')
            ->orderBy('id','DESC')->paginate(30);
            
            $store_list = CommonHelper::getStoresList();
            $status_list = CommonHelper::getposProductStatusList();
            
            return view('warehouse/pos_products_list',array('error_message'=>$error_message,'products_list'=>$products_list,'store_list'=>$store_list,'status_list'=>$status_list));
        }catch (\Exception $e){		
            
            CommonHelper::saveException($e,'POS',__FUNCTION__,__FILE__);
            return view('warehouse/pos_products_list',array('error_message'=>$e->getMessage()));
        }  
    }
    
    function inventoryPushDemandList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $start_date = $end_date = '';
            $rec_per_page = 100;
            $transfer_demands = $demands_list_count = array();
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            if(!empty($search_date['start_date']) && !empty($search_date['start_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if(isset($data['action']) && $data['action'] == 'create_demand'){
                
                // valid demand status: warehouse_loading, warehouse_dispatched, store_loading, store_loaded
                $validateionRules = array('store_id'=>'required');
                $attributes = array('store_id'=>'Store');
                
                
                // Tikki global store to other stores
                if(!empty($data['store_id']) && trim($data['store_id']) == 50){
                    $validateionRules['transfer_field'] = 'required';
                    $validateionRules['transfer_percent'] = 'required|numeric|max:100';
                    $attributes['transfer_field'] = 'Transfer Type';
                    $attributes['transfer_percent'] = 'Transfer Percent';
                }

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $store_data = Store::where('id',$data['store_id'])->first();
                $company_data = CommonHelper::getCompanyData();
                
                /*$invoice_no = CommonHelper::inventoryPushDemandInvoiceNo($store_data);
                
                $invoice_no_exists = Store_products_demand::where('invoice_no',$invoice_no)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                }*/
                
                $invoice_no = null;
                
                $insertArray = array('invoice_no'=>$invoice_no,'user_id'=>$user->id,'store_id'=>$data['store_id'],'demand_type'=>'inventory_push','demand_status'=>'warehouse_loading','store_data'=>json_encode($store_data),'store_state_id'=>$store_data->state_id);
                if($is_fake_inventory_user){
                    $insertArray['fake_inventory'] = 1;
                }
                
                // Tikki global
                if(!empty($data['store_id']) && trim($data['store_id']) == 50){
                    $insertArray['transfer_field'] = trim($data['transfer_field']);
                    $insertArray['transfer_percent'] = trim($data['transfer_percent']);
                }
                
                $insertArray['company_gst_no'] = $company_data['company_gst_no'];
                $insertArray['company_gst_name'] = $company_data['company_name'];
                
                $demand = Store_products_demand::create($insertArray);
                
                CommonHelper::createLog('Warehouse to Store Demand Created. ID: '.$demand->id,'INVENTORY_PUSH_DEMAND_CREATED','INVENTORY_PUSH_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Push demand created successfully','demand_details'=>$demand,'status' => 'success'),200);
            }
            
            $demands_list = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->leftJoin('store_products_demand_courier as spdc',function($join){$join->on('spd.id','=','spdc.demand_id')->where('spdc.type','=','inventory_push');})        
            ->where('spd.demand_type','inventory_push')
            ->where('spd.status',1);
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $demands_list = $demands_list->where('s.id',$data['s_id']);
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $demands_list = $demands_list->where('spd.invoice_no','LIKE','%'.trim($data['invoice_no']).'%');
            }
            
            if(isset($data['status']) && !empty($data['status'])){
                $status_list = array('1'=>'warehouse_loading','2'=>'warehouse_dispatched','3'=>'store_loading','4'=>'store_loaded');
                $demands_list = $demands_list->where('spd.demand_status',$status_list[$data['status']]);
            }
            
            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $demands_list = $demands_list->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
            }
            
            if(isset($data['invoice_id']) && !empty($data['invoice_id'])){
                $demands_list = $demands_list->where('spd.id',$data['invoice_id']);
            }
            
            if(isset($data['type']) && !empty($data['type'])){
                if($data['type'] == 1){
                    $demands_list = $demands_list->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')");
                }
                if($data['type'] == 2){
                    $demands_list = $demands_list->whereRaw("(spd.push_demand_id IS NOT NULL)");
                }
            }
            
            if($is_fake_inventory_user){
                $demands_list = $demands_list->where('spd.fake_inventory',1);
            }else{
                $demands_list = $demands_list->where('spd.fake_inventory',0);
            }
            
            $demands_list_count = clone $demands_list;
            
            $demands_list = $demands_list->groupBy('spd.id')->select('spd.*','s.store_name','spdc.transporter_name','spdc.boxes_count','spdc.docket_no','spdc.eway_bill_no','spdc.lr_no','s.store_id_code');
            
            if(!($user->user_type == 6 || $user->user_type == 1 || $is_fake_inventory_user) ){
                $demands_list = $demands_list->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))
                ->orderBy('spd.id','ASC');
            }else{
                $demands_list = $demands_list->orderBy('spd.id','DESC');
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $demands_list = $demands_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $demands_list = $demands_list->paginate($rec_per_page);
            }
            
            $transfer_demands_list = \DB::table('store_products_demand as spd')
            ->join('store_products_demand_inventory as spdi','spd.id', '=', 'spdi.demand_id')    
            ->join('store_products_demand as spd_push_demand','spdi.push_demand_id', '=', 'spd_push_demand.id')          
            ->where('spd.demand_type','inventory_transfer_to_store')        
            ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
            ->where('spd.status',1)
            ->where('spd.is_deleted',0)
            ->where('spdi.is_deleted',0)
            ->where('spdi.demand_status',1)        
            ->whereRaw('spdi.push_demand_id IS NOT NULL');
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $transfer_demands_list = $transfer_demands_list->where('spd_push_demand.store_id',$data['s_id']);
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $transfer_demands_list = $transfer_demands_list->where('spd_push_demand.invoice_no','LIKE','%'.trim($data['invoice_no']).'%');
            }
            
            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $transfer_demands_list = $transfer_demands_list->whereRaw("spd_push_demand.created_at BETWEEN '$start_date' AND '$end_date'");        
            }
            
            $transfer_demands_count = clone $transfer_demands_list;
            
            $transfer_demands_list = $transfer_demands_list->groupBy('spdi.push_demand_id')     
            ->selectRaw("spdi.push_demand_id,COUNT(spdi.id) as inv_count")->get()->toArray();  
            
            for($i=0;$i<count($transfer_demands_list);$i++){
                $key = $transfer_demands_list[$i]->push_demand_id;
                if(isset($transfer_demands[$key]))
                    $transfer_demands[$key]+= $transfer_demands_list[$i]->inv_count;
                else
                    $transfer_demands[$key] = $transfer_demands_list[$i]->inv_count;
            }
            
            //$demands_list_count = $demands_list_count->selectRaw("COUNT(spdi.id) as inv_count,SUM(spdi.store_base_price) as store_cost_price")->first();
            
            $transfer_demands_count = $transfer_demands_count->selectRaw("COUNT(spdi.id) as inv_count")->first();
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=stock_out_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('SNo','Invoice No','Created On','Store Name','Store Code','Total Qty','Total Amt','Transporter','Docket No','LR No','E-way Bill No','Boxes Count','Status','Type');
                
                
                $callback = function() use ($demands_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total_data = array('qty'=>0,'amt'=>0,'boxes'=>0);
                    
                    for($i=0;$i<count($demands_list);$i++){
                        $type = !empty($demands_list[$i]->push_demand_id)?'Complete Inventory Return':'Inventory Push';
                        $total_inv_data = json_decode($demands_list[$i]->total_data,true);
                        $array = array($i+1,$demands_list[$i]->invoice_no,date('d-m-Y',strtotime($demands_list[$i]->created_at)),$demands_list[$i]->store_name,$demands_list[$i]->store_id_code,$total_inv_data['total_qty'],$total_inv_data['total_value'],$demands_list[$i]->transporter_name,$demands_list[$i]->docket_no,
                        $demands_list[$i]->lr_no,$demands_list[$i]->eway_bill_no,$demands_list[$i]->boxes_count,ucwords(str_replace('_',' ',$demands_list[$i]->demand_status)),$type);
                        
                        $total_data['qty']+=$total_inv_data['total_qty'];
                        $total_data['amt']+=$total_inv_data['total_value'];
                        $total_data['boxes']+=$demands_list[$i]->boxes_count;
                                
                        fputcsv($file, $array);
                    }

                    $array = array('Total','','','','',$total_data['qty'],$total_data['amt'],'','','','',$total_data['boxes'],'');
                    fputcsv($file, $array);
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            // Download CSV end
            
            $store_list = CommonHelper::getStoresList();
            $sno = (isset($data['page']))?(($data['page']-1)*$rec_per_page)+1:1;
            
            return view('warehouse/inventory_push_demand_list',array('demands_list'=>$demands_list,'error_message'=>$error_message,'store_list'=>$store_list,'user'=>$user,'sno'=>$sno,'demands_list_count'=>$demands_list_count,'transfer_demands'=>$transfer_demands,'transfer_demands_count'=>$transfer_demands_count,'is_fake_inventory_user'=>$is_fake_inventory_user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_push_demand_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function editInventoryPushDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            $category_list = $color_list = $season_list = $size_list = $inventory_list = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){

                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])        
                ->where('ppmi.qc_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.hsn_code','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> does not exists' ));
                }
                
                if($product_data->product_status != 1){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: <b>'.$data['barcode'].'</b> already added', 'errors' =>'Inventory Product with code: <b>'.$data['barcode'].'</b> already added' ));
                }
                
                \DB::beginTransaction();
                
                $demand_data = Store_products_demand::where('id',$demand_id)->first();
                
                // Warehouse to Tikki global else other store
                if(!empty($demand_data->transfer_field) && !empty($demand_data->transfer_percent)){
                    if($demand_data->transfer_field == 'base_price'){
                        $store_base_rate = $product_data->base_price;
                        $store_base_rate = $store_base_rate+round($store_base_rate*($demand_data->transfer_percent/100),6);
                    }else{
                        $store_base_rate = round($product_data->sale_price*($demand_data->transfer_percent/100),2);
                    }
                    //$store_base_rate = ($demand_data->transfer_field == 'base_price')?$product_data->base_price:$product_data->sale_price;
                    //$store_base_rate = round($store_base_rate*($demand_data->transfer_percent/100),2);
                    //$store_base_rate = $store_base_rate+round($store_base_rate*($demand_data->transfer_percent/100),6);
                    $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                    if(!empty($gst_data)){
                        $gst_percent = $gst_data->rate_percent;
                    }else{
                        $gst_percent = ($store_base_rate >= 1000)?12:5;
                    }
                    
                    $gst_amount = round($store_base_rate*($gst_percent/100),2);
                    
                }else{
                    $company_data = CommonHelper::getCompanyData();
                    $store_data = Store::where('id',$data['store_id'])->first();
                
                    // Add 10% to vendor base price if store is franchise
                    $store_base_rate = ($store_data->store_type == 1)?$product_data->vendor_base_price:round($product_data->vendor_base_price+($product_data->vendor_base_price*.10),2);

                    if($store_data->gst_no != $company_data['company_gst_no']){
                        $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                        $gst_percent = $gst_data->rate_percent;
                        $gst_amount = round($store_base_rate*($gst_percent/100),2);
                    }else{
                        $gst_percent = $gst_amount = 0;
                    }
                }
                
                $store_base_price = $store_base_rate+$gst_amount;
                $product_data->store_base_price = $store_base_price;
                
                $updateArray = array('product_status'=>2,'store_id'=>$data['store_id'],'demand_id'=>$data['demand_id'],'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'store_assign_date'=>date('Y/m/d H:i:s'));
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 
                
                /*$demand_product = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->first();
                if(empty($demand_product)){
                    $insertArray = array('demand_id'=>$data['demand_id'],'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                    Store_products_demand_detail::create($insertArray);
                }else{
                    $demand_product->increment('product_quantity');
                }*/
                
                $insertArray = array('demand_id'=>$data['demand_id'],'inventory_id'=>$product_data->id,'transfer_status'=>1,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'transfer_date'=>date('Y/m/d H:i:s'),
                'store_id'=>$data['store_id'],'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                Store_products_demand_inventory::create($insertArray);
                
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
                //->where('spdi.demand_status',1)        
                ->where('ppm.is_deleted',0);
                
                if(isset($data['barcode']) && !empty($data['barcode'])){
                    $product_list = $product_list->where('ppmi.peice_barcode',trim($data['barcode']));
                }  
                
                if(isset($data['product_id']) && !empty($data['product_id'])){
                    $product_list = $product_list->where('ppmi.product_master_id',trim($data['product_id']));
                }  
                        
                $product_list = $product_list->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')
                ->orderBy('ppmi.store_assign_date','ASC')
                ->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                // Sku list for dropdown search
                $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')             
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('ppmi.product_status','>=',0)         
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.arnon_inventory',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppm.arnon_product',0)
                ->where('spdi.is_deleted',0)         
                ->where('spdi.demand_status',1)        
                ->groupByRaw('ppmi.product_master_id')
                ->selectRaw('ppm.id,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                ->orderByRaw('poi.vendor_sku,psc.id')        
                ->get()
                ->toArray();
                
                $inventory_count = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('spdi.demand_id',$data['demand_id'])
                ->where('ppmi.product_status','>=',0)         
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('spdi.is_deleted',0)
                ->where('spdi.demand_status',1)          
                ->count();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'sku_list' => $sku_list,'inventory_count'=>$inventory_count),200);
            }
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name')
            ->first();
            
            if(isset($data['action']) && $data['action']  == 'get_demand_preview_data'){
                $demand_total_data = CommonHelper::updateDemandTotalData($demand_id,1,1);
                $total_data = $demand_total_data['total_data'];
                $store_data = json_decode($demand_data->store_data,true);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand data','demand_data'=>$demand_data,'total_data'=>$total_data,'store_data'=>$store_data),200);
            }
            
            if($is_fake_inventory_user){
                $inventory_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
                ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
                ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->leftJoin('purchase_order as po','ppmi.po_id', '=', 'po.id')         
                ->where('ppmi.peice_barcode','>',0)        
                ->where('ppmi.product_status',1)        
                ->where('ppmi.qc_status',1)             
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.fake_inventory',1)
                ->where('ppmi.status',1);

                if(isset($data['prod_name_search']) && !empty($data['prod_name_search'])){
                    $product_name = trim($data['prod_name_search']);
                    $vendor_sku = (strpos($product_name,'-') !== false)?substr($product_name,0, strrpos($product_name, '-')):$product_name;
                    $inventory_list = $inventory_list->whereRaw("(ppm.product_name LIKE '%{$product_name}%' OR ppm.product_barcode = '$product_name' OR ppmi.peice_barcode = '$product_name' OR ppm.product_sku = '$product_name' OR ppm.product_sku = '$vendor_sku')");
                }

                if(isset($data['size_search']) && !empty($data['size_search'])){
                    $inventory_list = $inventory_list->where('ppm.size_id',$data['size_search']);
                }

                if(isset($data['color_search']) && !empty($data['color_search'])){
                    $inventory_list = $inventory_list->where('ppm.color_id',$data['color_search']);
                }

                if(isset($data['category_search']) && !empty($data['category_search'])){
                    $inventory_list = $inventory_list->where('ppm.category_id',$data['category_search']);
                }

                if(isset($data['product_subcategory_search']) && !empty($data['product_subcategory_search'])){
                    $inventory_list = $inventory_list->where('ppm.subcategory_id',$data['product_subcategory_search']);
                }

                if(isset($data['po_search']) && !empty($data['po_search'])){
                    $po = trim($data['po_search']);
                    $inventory_list = $inventory_list->whereRaw("(ppmi.po_id = '$po' OR po.order_no = '$po')");
                }
                
                $inventory_list = $inventory_list->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','dlim_1.name as category_name',
                'dlim_2.name as subcategory_name','dlim_3.name as color_name','psc.size as size_name','po.order_no as po_order_no');
                
                $inventory_list = $inventory_list->paginate(100);
                
                $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','COLOR','SEASON'))->where('is_deleted',0)->where('status',1)->orderBy('name')->get()->toArray();
            
                for($i=0;$i<count($design_lookup_items);$i++){
                    if(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_CATEGORY'){
                        $category_list[] = $design_lookup_items[$i];
                    }elseif(strtoupper($design_lookup_items[$i]['type']) == 'COLOR'){
                        $color_list[] = $design_lookup_items[$i];
                    }elseif(strtoupper($design_lookup_items[$i]['type']) == 'SEASON'){
                        $season_list[] = $design_lookup_items[$i];
                    }
                }

                $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
                
            }
            
            return view('warehouse/inventory_push_demand_edit',array('error_message'=>$error_message,'demand_data'=>$demand_data,'is_fake_inventory_user'=>$is_fake_inventory_user,'inventory_list'=>$inventory_list,'size_list'=>$size_list,'color_list'=>$color_list,'season_list'=>$season_list,'category_list'=>$category_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_push_demand_edit',array('error_message'=>$e->getMessage()));
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
                
                $validateionRules = array('boxes_count'=>'required|numeric','transporter_name'=>'required','transporter_gst'=>'required','docket_no'=>'required','eway_bill_no'=>'required','discount_percent'=>'nullable|numeric','lr_no'=>'required');
                if($data['discount_applicable'] == 1){
                    $validateionRules['discount_percent'] = 'required|numeric';
                }
                $attributes = array('lr_no'=>'LR Number');

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
                
                $store_data = Store::where('id',$demand_data->store_id)->first();
                $invoice_no = CommonHelper::inventoryPushDemandInvoiceNo($store_data);
                
                $invoice_no_exists = Store_products_demand::where('invoice_no',$invoice_no)->where('invoice_series_type',2)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                }
                
                $insertArray = array('type'=>'inventory_push','demand_id'=>$demand_id,'boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no'],'lr_no'=>$data['lr_no']);
                $demand_courier = Store_products_demand_courier::create($insertArray);
                
                $updateArray = array('product_status'=>3);
                Pos_product_master_inventory::where('demand_id',$demand_id)->where('product_status',2)->update($updateArray);
                
                $updateArray = array('demand_status'=>'warehouse_dispatched','crn_user_id'=>$user->id,'invoice_no'=>$invoice_no,'created_at'=>date('Y/m/d H:i:s'),'debit_note_date'=>date('Y/m/d H:i:s'));
                
                /*if($data['discount_applicable'] != ''){
                    $updateArray['discount_applicable'] = $data['discount_applicable'];
                    $updateArray['discount_percent'] = ($data['discount_applicable'] == 1)?$data['discount_percent']:null;
                }else{
                   $updateArray['discount_applicable'] = null;
                   $updateArray['discount_percent'] = null;
                }
                
                $updateArray['gst_inclusive'] = ($data['gst_inclusive'] != '')?$data['gst_inclusive']:null;*/
                
                $updateArray['discount_applicable'] = null;
                $updateArray['discount_percent'] = null;
                $updateArray['gst_inclusive'] = null;
                
                Store_products_demand::where('id',$demand_id)->update($updateArray);
                
                CommonHelper::updateDemandTotalData($demand_id);
                
                \DB::commit();
                
                CommonHelper::createLog('Warehouse to Store Demand closed. ID: '.$demand_id,'INVENTORY_PUSH_DEMAND_CLOSED','INVENTORY_PUSH_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully','status' => 'success'),200);

            }
            
            if(isset($data['action']) && $data['action']  == 'update_gate_pass_data'){
                $validateionRules = array('boxes_count'=>'required|numeric','transporter_name'=>'required','transporter_gst'=>'required','docket_no'=>'required','eway_bill_no'=>'required','discount_percent'=>'nullable|numeric','lr_no'=>'required');
                if($data['discount_applicable'] == 1){
                    $validateionRules['discount_percent'] = 'required|numeric';
                }
                $attributes = array('lr_no'=>'LR Number');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $updateArray = array('boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],
                'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no'],'lr_no'=>$data['lr_no'],'ship_to'=>$data['ship_to'],'dispatch_by'=>$data['dispatch_by']);
                Store_products_demand_courier::where('demand_id',$data['demand_id'])->where('type','inventory_push')->update($updateArray);
                
                $updateArray = array();
                if($data['discount_applicable'] != ''){
                    $updateArray['discount_applicable'] = $data['discount_applicable'];
                    $updateArray['discount_percent'] = ($data['discount_applicable'] == 1)?$data['discount_percent']:null;
                    
                }else{
                   $updateArray['discount_applicable'] = null;
                   $updateArray['discount_percent'] = null;
                }
                
                $updateArray['gst_inclusive'] = ($data['gst_inclusive'] != '')?$data['gst_inclusive']:null;
                
                Store_products_demand::where('id',$data['demand_id'])->update($updateArray);
                
                CommonHelper::createLog('Gate Pass Data Updated. Demand ID: '.$data['demand_id'],'GATE_PASS_DATA_UPDATED','INVENTORY_PUSH_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Gate Pass updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_push_demand_items'){
                
                \DB::beginTransaction();
                
                $inv_items = Pos_product_master_inventory::wherein('id',$data['deleteChk'])->get()->toArray();
                for($i=0;$i<count($inv_items);$i++){
                    $updateArray = array('product_status'=>1,'store_id'=>null,'demand_id'=>null,'store_base_rate'=>null,'store_gst_percent'=>null,
                    'store_gst_amount'=>null,'store_base_price'=>null,'store_assign_date'=>null);
                    
                    Pos_product_master_inventory::where('id',$inv_items[$i]['id'])->update($updateArray);
                    
                    $updateArray = array('is_deleted'=>1);
                    Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$inv_items[$i]['id'])->where('is_deleted',0)->update($updateArray);
                    
                    //Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$inv_items[$i]['product_master_id'])->where('is_deleted',0)->decrement('product_quantity',1);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Warehouse Demand Inventory Deleted. Demand ID: '.$data['demand_id'],'INVENTORY_PUSH_DEMAND_INVENTORY_DELETED','INVENTORY_PUSH_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand Items deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'add_fake_push_demand_items'){
                \DB::beginTransaction();
                
                $inv_items = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->wherein('ppmi.id',$data['demandChkArray'])        
                ->where('ppmi.qc_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.hsn_code')
                ->get()->toArray();
                
                $demand_data = Store_products_demand::where('id',$demand_id)->first();
                $company_data = CommonHelper::getCompanyData();
                $store_data = Store::where('id',$demand_data->store_id)->first();
                
                for($i=0;$i<count($inv_items);$i++){
                    
                    $product_data = $inv_items[$i];
                    
                    $inv_exists = Store_products_demand_inventory::where('demand_id',$demand_id)->where('inventory_id',$product_data->id)->where('is_deleted',0)->select('id')->first();
                    if(!empty($inv_exists)){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Duplicate Inventory Barcode:'.$product_data->peice_barcode, 'errors' =>'Duplicate Inventory Barcode:'.$product_data->peice_barcode ));
                    }
                    
                    // Add 10% to vendor base price if store is franchise
                    $store_base_rate = ($store_data->store_type == 1)?$product_data->vendor_base_price:round($product_data->vendor_base_price+($product_data->vendor_base_price*.10),2);

                    if($store_data->gst_no != $company_data['company_gst_no']){
                        $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                        $gst_percent = $gst_data->rate_percent;
                        $gst_amount = round($store_base_rate*($gst_percent/100),2);
                    }else{
                        $gst_percent = $gst_amount = 0;
                    }


                    $store_base_price = $store_base_rate+$gst_amount;
                    $product_data->store_base_price = $store_base_price;

                    $updateArray = array('product_status'=>2,'store_id'=>$demand_data->store_id,'demand_id'=>$demand_id,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'store_assign_date'=>date('Y/m/d H:i:s'));
                    Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 

                    /*$demand_product = Store_products_demand_detail::where('demand_id',$demand_id)->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->first();
                    if(empty($demand_product)){
                        $insertArray = array('demand_id'=>$demand_id,'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                        Store_products_demand_detail::create($insertArray);
                    }else{
                        $demand_product->increment('product_quantity');
                    }*/

                    $insertArray = array('demand_id'=>$demand_id,'inventory_id'=>$product_data->id,'transfer_status'=>1,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'transfer_date'=>date('Y/m/d H:i:s'),
                    'store_id'=>$demand_data->store_id,'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                    Store_products_demand_inventory::create($insertArray);
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Inventory added successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'import_demand_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $demand_detail_listing = $barcodes_list = $barcodes_updated = array();
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
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->wherein('ppmi.peice_barcode',$barcodes)        
                ->where('ppmi.qc_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.hsn_code')
                ->get()->toArray();        
                
                // Check if product status is 1
                for($i=0;$i<count($product_list);$i++){
                    if($product_list[$i]->product_status != 1){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product is not in warehouse <br/>';
                    }
                    
                    $barcodes_list[] = $product_list[$i]->peice_barcode;
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
                
                /*$demand_detail_list = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($demand_detail_list);$i++){
                    $demand_detail_listing[$demand_detail_list[$i]['product_id']] = $demand_detail_list[$i];
                }*/
                
                $date = date('Y/m/d H:i:s');
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($product_list);$i++){
                    
                    $product_data = $product_list[$i];
                    // Warehouse to Tikki global else other store
                    if(!empty($demand_data->transfer_field) && !empty($demand_data->transfer_percent)){
                        //$store_base_rate = ($demand_data->transfer_field == 'base_price')?$product_data->base_price:$product_data->sale_price;
                        //$store_base_rate = round($store_base_rate*($demand_data->transfer_percent/100),2);
                        if($demand_data->transfer_field == 'base_price'){
                            $store_base_rate = $product_data->base_price;
                            $store_base_rate = $store_base_rate+round($store_base_rate*($demand_data->transfer_percent/100),6);
                        }else{
                            $store_base_rate = round($product_data->sale_price*($demand_data->transfer_percent/100),2);
                        }
                        
                        $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                        if(!empty($gst_data)){
                            $gst_percent = $gst_data->rate_percent;
                        }else{
                            $gst_percent = ($store_base_rate >= 1000)?12:5;
                        }

                        $gst_amount = round($store_base_rate*($gst_percent/100),2);
                    }else{
                        $store_data = Store::where('id',$data['store_id'])->first();

                        // Add 10% to vendor base price if store is franchise
                        $store_base_rate = ($store_data->store_type == 1)?$product_data->vendor_base_price:round($product_data->vendor_base_price+($product_data->vendor_base_price*.10),2);

                        if($store_data->gst_no != $company_data['company_gst_no']){
                            $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                            if(!empty($gst_data)){
                                $gst_percent = $gst_data->rate_percent;
                            }else{
                                $gst_percent = ($store_base_rate >= 1000)?12:5;
                            }
                            $gst_amount = round($store_base_rate*($gst_percent/100),2);
                        }else{
                            $gst_percent = $gst_amount = 0;
                        }
                    }

                    $store_base_price = $store_base_rate+$gst_amount;
                    
                    $updateArray = array('product_status'=>2,'store_id'=>$data['store_id'],'demand_id'=>$data['demand_id'],'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'store_assign_date'=>$date);
                    Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 

                    /*if(!isset($demand_detail_listing[$product_data->product_master_id])){
                        $insertArray = array('demand_id'=>$data['demand_id'],'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                        Store_products_demand_detail::create($insertArray);
                        $demand_detail_listing[$product_data->product_master_id] = 1;
                    }else{
                        Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->increment('product_quantity');
                    }*/

                    $insertArray = array('demand_id'=>$data['demand_id'],'inventory_id'=>$product_data->id,'transfer_status'=>1,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'transfer_date'=>$date,
                    'store_id'=>$data['store_id'],'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                    Store_products_demand_inventory::create($insertArray);
                }
                
                \DB::commit();
                
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Demand updated successfully'),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function inventoryPushDemandDetail(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            $product_inventory = $products = $size_list = $products_sku = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->leftJoin('users as u1','u1.id', '=', 'spd.crn_user_id')                
            ->leftJoin('users as u2','u2.id', '=', 'spd.cancel_user_id')          
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name','s.store_id_code','u.name as user_name','u1.name as crn_user_name','u2.name as cancel_user_name')
            ->first();
            
            //$inventory_received_count to check cancel demand
            $inventory_received_count = Store_products_demand_inventory::where('demand_id',$demand_id)->where('receive_status',1)->where('is_deleted',0)->count();
            
            // Cancel demand
            if(isset($data['action']) && $data['action']  == 'cancel_demand'){
                $validateionRules = array('comments'=>'required',);
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                if($inventory_received_count > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Products are loaded by Store', 'errors' =>'Inventory Products are loaded by Store' )); 
                }
                
                if(!in_array(strtolower($demand_data->demand_status),array('warehouse_loading','warehouse_dispatched','store_loading'))){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Demand Status is '.str_replace('_',' ',$demand_data->demand_status), 'errors' =>'Demand Status is '.str_replace('_',' ',$demand_data->demand_status) )); 
                }
                
                \DB::beginTransaction();
                
                $updateArray = array('demand_status'=>3);
                Store_products_demand_inventory::where('demand_id',$demand_id)->update($updateArray);
                //Store_products_demand_detail::where('demand_id',$demand_id)->update($updateArray);
                Store_products_demand_courier::where('demand_id',$demand_id)->update($updateArray);
                Store_products_demand_sku::where('demand_id',$demand_id)->update($updateArray);
                
                $updateArray = array('demand_status'=>'cancelled','cancel_user_id'=>$user->id,'cancel_comments'=>trim($data['comments']),'cancel_date'=>date('Y/m/d H:i:s'));
                Store_products_demand::where('id',$demand_id)->update($updateArray);
                
                $updateArray = array('product_status'=>1,'store_id'=>null,'demand_id'=>null,'store_base_rate'=>null,'store_gst_percent'=>null,'store_gst_amount'=>null,'store_base_price'=>null,'store_assign_date'=>null);
                Pos_product_master_inventory::where('demand_id',$demand_id)->update($updateArray); 
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Warehouse Demand Cancelled. Demand ID: '.$demand_id,'INVENTORY_PUSH_DEMAND_CANCELLED','INVENTORY_PUSH_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand cancelled successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'edit_push_demand'){
                $errors = $add_inv_list = $delete_inv_list =  $add_inv_qr_codes = $delete_inv_qr_codes = [];
                $total_add = trim($data['total_add']);
                $total_delete = trim($data['total_delete']);
                
                $demand_data = Store_products_demand::where('id',$data['demand_id'])->first();
                
                // check id demand is not already edited once
                if($demand_data->demand_edited == 1){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Demand is already edited', 'errors' => 'Demand is already edited'));
                }
                
                // check demand status
                if($demand_data->demand_status != 'warehouse_dispatched'){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Demand Status is not Warehouse Dispatched', 'errors' => 'Demand Status is not Warehouse Dispatched'));
                }
                
                // Create Add inventory list
                for($i=1;$i<=$total_add;$i++){
                    $elem_name = 'qr_code_add_'.$i;
                    if(isset($data[$elem_name]) && !empty($data[$elem_name])){
                        $qr_code = trim($data[$elem_name]);
                        
                        $inv_data = \DB::table('pos_product_master_inventory as ppmi')
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                        ->where('ppmi.peice_barcode',$qr_code)->where('ppmi.is_deleted',0)->where('ppmi.fake_inventory',0)
                        ->where('ppm.is_deleted',0)->where('ppm.fake_inventory',0)        
                        ->select('ppmi.*','ppm.hsn_code')        
                        ->first();
                        
                        if(empty($inv_data)){
                            $errors[$elem_name] = 'Product does not exists';
                        }elseif(!empty($inv_data) && $inv_data->product_status != 1){
                            $errors[$elem_name] = 'Not Available in Warehouse';
                        }elseif(in_array($qr_code,$add_inv_qr_codes)){
                            $errors[$elem_name] = 'Duplicate QR Code';
                        }
                        
                        if(!in_array($qr_code,$add_inv_qr_codes)){
                            $add_inv_list[] = $inv_data;
                            $add_inv_qr_codes[] = $qr_code;
                        }
                    }
                }
                
                // Create delete inventory list
                for($i=1;$i<=$total_delete;$i++){
                    $elem_name = 'qr_code_delete_'.$i;
                    if(isset($data[$elem_name]) && !empty($data[$elem_name])){
                        $qr_code = trim($data[$elem_name]);
                        
                        $inv_data = \DB::table('pos_product_master_inventory as ppmi')
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                        ->where('ppmi.peice_barcode',$qr_code)->where('ppmi.is_deleted',0)->where('ppmi.fake_inventory',0)
                        ->where('ppm.is_deleted',0)->where('ppm.fake_inventory',0)        
                        ->select('ppmi.*','ppm.hsn_code')                
                        ->first();
                        
                        if(empty($inv_data)){
                            $errors[$elem_name] = 'Product does not exists';
                        }elseif(!empty($inv_data) && $inv_data->demand_id != $data['demand_id']){
                            $errors[$elem_name] = 'Prod does not exists in this Demand';
                        }elseif(in_array($delete_inv_qr_codes,$add_inv_qr_codes)){
                            $errors[$elem_name] = 'Duplicate QR Code';
                        }
                        
                        if(!in_array($qr_code,$delete_inv_qr_codes)){
                            $delete_inv_list[] = $inv_data;
                            $delete_inv_qr_codes[] = $qr_code;
                        }
                    }
                }
                
                if(!empty($errors)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $errors));
                }
                
                if(empty($add_inv_list) && empty($delete_inv_list)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Add/Delete QR Code is empty', 'errors' => 'Add/Delete QR Code is empty'));
                }
                
                $company_data = CommonHelper::getCompanyData();
                $store_data = Store::where('id',$demand_data->store_id)->first();
                $date = date('Y/m/d H:i:s');
                
                \DB::beginTransaction();
                
                // Add inventory to demand
                for($i=0;$i<count($add_inv_list);$i++){
                    $product_data = $add_inv_list[$i];
                    // Add 10% to vendor base price if store is franchise
                    $store_base_rate = ($store_data->store_type == 1)?$product_data->vendor_base_price:round($product_data->vendor_base_price+($product_data->vendor_base_price*.10),2);

                    if($store_data->gst_no != $company_data['company_gst_no']){
                        $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                        $gst_percent = $gst_data->rate_percent;
                        $gst_amount = round($store_base_rate*($gst_percent/100),2);
                    }else{
                        $gst_percent = $gst_amount = 0;
                    }
                    
                    $store_base_price = $store_base_rate+$gst_amount;
                    $product_data->store_base_price = $store_base_price;

                    $updateArray = array('product_status'=>3,'store_id'=>$demand_data->store_id,'demand_id'=>$data['demand_id'],'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'store_assign_date'=>$date);
                    Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 

                    /*$demand_product = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->first();
                    if(empty($demand_product)){
                        $insertArray = array('demand_id'=>$data['demand_id'],'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                        Store_products_demand_detail::create($insertArray);
                    }else{
                        $demand_product->increment('product_quantity');
                    }*/

                    $insertArray = array('demand_id'=>$data['demand_id'],'inventory_id'=>$product_data->id,'transfer_status'=>1,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'transfer_date'=>$date,
                    'store_id'=>$demand_data->store_id,'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                    Store_products_demand_inventory::create($insertArray);
                }
                
                //Delete inventory from demand
                for($i=0;$i<count($delete_inv_list);$i++){
                    $product_data = $delete_inv_list[$i];
                    $updateArray = array('product_status'=>1,'store_id'=>null,'demand_id'=>null,'store_base_rate'=>null,'store_gst_percent'=>null,'store_gst_amount'=>null,'store_base_price'=>null,'store_assign_date'=>null);
                    
                    Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray);
                    
                    $updateArray = array('is_deleted'=>1);
                    Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->update($updateArray);
                    
                    //Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->decrement('product_quantity',1);
                }
                
                // Updated that demand is edited, it cannot be edited again.
                $updateArray = ['demand_edited'=>1];
                Store_products_demand::where('id',$demand_id)->update($updateArray);
                
                CommonHelper::updateDemandTotalData($demand_id);
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Warehouse Demand Updated. Demand ID: '.$demand_id,'INVENTORY_PUSH_DEMAND_UPDATED','INVENTORY_PUSH_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_fake_inventory_demand_in_store'){
                $date = date('Y/m/d H:i:s');
                
                \DB::beginTransaction();
                
                $updateArray = array('product_status'=>4,'store_intake_date'=>$date);
                Pos_product_master_inventory::where('demand_id',$demand_id)->update($updateArray); 

                $updateArray = array('receive_status'=>1,'receive_date'=>$date);
                Store_products_demand_inventory::where('demand_id',$demand_id)->update($updateArray);

                //\DB::update('UPDATE store_products_demand_detail set store_intake_qty = product_quantity where demand_id = '.$demand_id);
                
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'store_loaded','comments'=>$data['comments']));
                
                \DB::commit();
                    
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'open_demand'){
                $validationRules = array('comments'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'store_loading','comments'=>$data['comments']));
                
                CommonHelper::createLog('Warehouse to Store Demand Opened by Administrator. Demand ID: '.$demand_id,'WAREHOUSE_TO_STORE_DEMAND_REOPENED','WAREHOUSE_TO_STORE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand opened successfully'),200);
            }
            
            $product_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')       
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')           
            ->where('spdi.demand_id',$demand_id)        
            ->where('ppmi.is_deleted',0)
            ->where('spdi.is_deleted',0)  
            //->where('spdi.demand_status',1) 
            ->where('spdi.transfer_status',1)        
            ->where('ppm.is_deleted',0)
            ->select('ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','ppm.size_id','dlim_1.name as color_name')        
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
            ->where('demand_id',$demand_id)->where('type','inventory_push')->where('status',1)->where('is_deleted',0)
            ->select('*')->first();
            
            $inventory_total_count = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->count();
            //$inventory_received_count = Store_products_demand_inventory::where('demand_id',$demand_id)->where('receive_status',1)->where('is_deleted',0)->count();
            
            $debit_note = \DB::table('debit_notes')->where('invoice_id',$demand_id)->where('debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand')->where('is_deleted',0)->first();
                    
            return view('warehouse/inventory_push_demand_detail',array('product_list'=>$product_list,'products'=>$products,'demand_data'=>$demand_data,
            'error_message'=>$error_message,'gate_pass_data'=>$gate_pass_data,'user'=>$user,'size_list'=>$size_list,'inventory_received_count'=>$inventory_received_count,
            'products_sku'=>$products_sku,'inventory_total_count'=>$inventory_total_count,'debit_note'=>$debit_note,'is_fake_inventory_user'=>$is_fake_inventory_user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_PUSH_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().' '.$e->getLine()),500);
            }else{
                return view('warehouse/inventory_push_demand_detail',array('error_message'=>$e->getMessage().' '.$e->getLine()));
            }
        }
    }
    
    function inventoryPushDemandInvoice(Request $request,$id){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $demand_id = $id;
            $user = Auth::user();
            $products_list = $demand_data = $company_data = $products_sku = $demand_sku_list = $demand_sku_list_arnon = array();
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->leftJoin('state_list as sl','sl.id', '=', 's.state_id')        
            ->where('spd.id',$demand_id)
            ->select('spd.*','u.name as user_name','s.store_name','s.address_line1','s.address_line2','s.phone_no','s.gst_no','s.city_name','s.postal_code','s.gst_name','sl.state_name')->first();
            
            $company_data = CommonHelper::getCompanyData();
            $store_data = json_decode($demand_data->store_data,false);
            
            //if($demand_data->gst_no != $company_data['company_gst_no']){
            if($store_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($demand_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            }else{
                $gst_name = '';
            }
            
            //  Northcorp inventory
            $demand_products_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
            ->where('spdi.demand_id',$demand_id)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.arnon_inventory',0)
            ->where('spdi.is_deleted',0)  
            //->where('spdi.demand_status',1)    
            ->where('spdi.transfer_status',1)        
            //->where('spdi.receive_status',1)         
            ->where('ppm.is_deleted',0)
            ->select('ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','ppm.id as product_id','ppm.size_id','ppm.color_id')        
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
                    $demand_sku_list[$key]['qty']+= 1;
                }
            }
            
            // Arnon Inventory
            $demand_products_list_arnon = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->where('spdi.demand_id',$demand_id)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.arnon_inventory',1)
            ->where('spdi.is_deleted',0)  
            //->where('spdi.demand_status',1)        
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
                    $demand_sku_list_arnon[$key]['qty']+= 1;
                }
            }
            
            $gate_pass_data = \DB::table('store_products_demand_courier')
            ->where('demand_id',$demand_id)->where('type','inventory_push')->where('status',1)->where('is_deleted',0)
            ->select('*')->first();
            
            $size_list = Production_size_counts::where('is_deleted',0)->orderBy('id')->get()->toArray();
            $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->orderBy('name')->get()->toArray();
            
            for($i=0;$i<count($size_list);$i++){
                $sizes[$size_list[$i]['id']] = $size_list[$i]['size'];
            }
            
            for($i=0;$i<count($color_list);$i++){
                $colors[$color_list[$i]['id']] = $color_list[$i]['name'];
            }
            
            $data = array('message' => 'products list','demand_sku_list' => $demand_sku_list,'demand_data' => $demand_data,'company_data'=>$company_data,'gst_name'=>$gst_name,'demand_sku_list_arnon'=>$demand_sku_list_arnon,'gate_pass_data'=>$gate_pass_data,'sizes'=>$sizes,'colors'=>$colors);
            
            //return view('warehouse/inventory_push_demand_invoice',$data);
            
            $pdf = PDF::loadView('warehouse/inventory_push_demand_invoice', $data);

            return $pdf->download('demand_invoice_pdf');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'products list','demand_products_list' => $demand_products_list,'demand_data' => $demand_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function inventoryPushDemandGatePass(Request $request,$id){
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
            
            $pdf = PDF::loadView('warehouse/inventory_push_demand_gate_pass', $data);

            return $pdf->download('demand_gate_pass_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }
    }
    
    function importSorInventory(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $invoice_id = $id;
            $error_message = $excess_amount_debit_note = $less_inv_debit_note = '';
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if(isset($data['action']) && $data['action']  == 'load_po_inventory'){
                $rec_per_page = 100;
                $barcode_list = array();
                $grn_data = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->where('status',1)->first();
                
                if(empty($grn_data)){
                    $product_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                    ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                    ->leftJoin('vendor_detail as vd','vd.id', '=', 'po.vendor_id')                
                    ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                    ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('ppmi.po_id',$data['po_id'])
                    ->where('ppmi.po_detail_id',$data['po_detail_id'])                
                    ->where('ppmi.product_status','>=',1)
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)        
                    ->where('ppm.is_deleted',0);
                    
                    
                    $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                    ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('ppmi.po_id',$data['po_id'])
                    ->where('ppmi.po_detail_id',$data['po_detail_id'])                
                    ->where('ppmi.product_status','>=',1)
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)        
                    ->where('ppm.is_deleted',0)
                    ->groupByRaw('ppmi.product_master_id')
                    ->selectRaw('ppm.id,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                    ->orderByRaw('poi.vendor_sku,psc.id')        
                    ->get()->toArray();        
                    
                    $inv_imported = Pos_product_master_inventory::where('po_detail_id',$data['po_detail_id'])
                    ->where('po_id',$data['po_id'])->where('product_status','>',0)
                    ->where('is_deleted',0)->where('status',1)->count();
                    
                }else{
                    $product_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')    
                    ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                    ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                    ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->leftJoin('vendor_detail as vd','vd.id', '=', 'po.vendor_id')        
                    ->where('po_qc_items.grn_qc_id',$grn_data->id)        
                    ->where('poi.order_id',$grn_data->po_id)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)
                    ->where('po_qc_items.is_deleted',0) 
                    ->where('ppm.is_deleted',0);
                    
                    $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                    ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('poi.order_id',$grn_data->po_id) 
                    ->where('po_qc_items.grn_qc_id',$grn_data->id)                      
                    //->where('ppmi.product_status','>=',1)
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)        
                    ->where('ppm.is_deleted',0)
                    ->where('po_qc_items.is_deleted',0)         
                    ->groupByRaw('ppmi.product_master_id')
                    ->selectRaw('ppm.id,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                    ->orderByRaw('poi.vendor_sku,psc.id')        
                    ->get()->toArray();        
                    
                    $inv_imported = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                    ->where('po_qc_items.grn_qc_id',$grn_data->id)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)
                    ->where('po_qc_items.is_deleted',0) 
                    ->count();        
                }
                
                if(isset($data['barcode']) && !empty($data['barcode'])){
                    $product_list = $product_list->where('ppmi.peice_barcode',trim($data['barcode']));
                }
                
                if(isset($data['product_id']) && !empty($data['product_id'])){
                    $product_list = $product_list->where('ppmi.product_master_id',trim($data['product_id']));
                }
                
                $product_list = $product_list->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name',
                'po.order_no as po_order_no','vd.name as vendor_name','poi.vendor_sku')->orderBy('intake_date','ASC')->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $po_detail_data = Purchase_order_details::where('id',$data['po_detail_id'])->where('is_deleted',0)->where('status',1)->first();
                
                $inventory_count_data = array('inv_total'=>$po_detail_data->products_count,'inv_imported'=>$inv_imported);
                
                if(!empty($grn_data)){
                    $grn_data->inv_imported = $inv_imported;
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'grn_data'=>$grn_data,'inventory_count_data'=>$inventory_count_data,'sku_list'=>$sku_list),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')    
                ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')        
                ->leftJoin('vendor_detail as vd','vd.id', '=', 'po.vendor_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])
                ->where('ppmi.po_id',$data['po_id'])        
                ->where('poi.order_id',$data['po_id'])                
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.size_id','dlim_1.name as color_name',
                'psc.size as size_name','po.order_no as po_order_no','vd.name as vendor_name','ppm.size_id','ppm.product_barcode','poi.vendor_sku')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with Code: <b>'.$data['barcode'].'</b> does not exists', 'errors' =>'Inventory Product with Code: <b>'.$data['barcode'].'</b> does not exists' ));
                }
                
                if($product_data->product_status != 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with Code: <b>'.$data['barcode'].'</b> already added', 'errors' =>'Inventory Product with Code: <b>'.$data['barcode'].'</b> already added' ));
                }
                
                $po_detail_data = Purchase_order_details::where('id',$invoice_id)->first();
                $inv_in_wh_count = Pos_product_master_inventory::where('po_detail_id',$invoice_id)->where('product_status','>',0)->where('is_deleted',0)->count(); 
                
                if($inv_in_wh_count >= $po_detail_data->products_count){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Total Inventory of Invoice imported', 'errors' =>'Total Inventory of Invoice imported' ));
                }
                
                \DB::beginTransaction();
                
                $updateArray = array('product_status'=>1,'intake_date'=>date('Y/m/d H:i:s'),'po_detail_id'=>$data['po_detail_id']);
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 

                $po_detail_data = Purchase_order_items::where('order_id',$data['po_id'])->where('product_sku',trim($product_data->product_sku))->first();//echo $product->product_sku;exit;

                $po_detail_data->increment('qty_received');
                $po_detail_data->increment('qty_received_actual');
                
                $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                if(isset($po_size_data_received[$product_data->size_id])) $po_size_data_received[$product_data->size_id] = $po_size_data_received[$product_data->size_id]+1;else $po_size_data_received[$product_data->size_id] = 1;

                $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Product with Code: <b>'.$product_data->peice_barcode.'</b> added ','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_detail'){
                $vehicle_details = Purchase_order_details::where('id',$data['id'])->first();
                if(!empty($vehicle_details)){
                    $vehicle_details->images_list = json_decode($vehicle_details->images_list,true);
                }
                
                $grn_data = $products_list = array();
                if(!empty($vehicle_details->grn_id)){
                    
                    $grn_data = Purchase_order_grn_qc::where('id',$vehicle_details->grn_id)->where('is_deleted',0)->where('status',1)->first();
                    
                    $products_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','poi.product_sku', '=', 'ppm.product_sku')        
                    ->where('ppmi.grn_id',$vehicle_details->grn_id)->where('ppmi.product_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)
                    ->where('ppm.is_deleted',0)
                    ->groupBy('ppm.product_sku')        
                    ->selectRaw('ppm.product_name,ppm.product_sku,ppmi.vendor_base_price,vendor_gst_percent,vendor_gst_amount,count(ppmi.id) as products_count,poi.rate,poi.gst_percent')
                    ->get()->toArray();
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory vehicle details','vehicle_details'=>$vehicle_details,'products_list'=>$products_list,'grn_data'=>$grn_data,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_transfer_inventory_data'){
                $size_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('po_qc_items.grn_qc_id',$data['grn_id'])        
                ->where('ppm.product_sku',$data['sku'])       
                ->where('ppmi.product_status','>',0)        // exclude qc defective products
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('po_qc_items.is_deleted',0) 
                ->where('ppm.is_deleted',0)
                ->groupByRaw('ppm.size_id')        
                ->select('psc.size as size_name','ppm.size_id',\DB::raw('count(po_qc_items.id) as inv_count'))
                ->get()->toArray();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Size data','size_data'=>$size_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_grn_preview_data'){
                $grn_sku_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ppmi.po_id',$data['po_id'])        
                ->where('ppmi.po_detail_id',$data['po_detail_id'])                
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->groupBy('ppm.product_sku')                
                ->selectRaw('ppm.product_sku,ppmi.base_price,COUNT(ppmi.id) as inv_count')
                ->get()->toArray();        
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'GRN data','grn_sku_list'=>$grn_sku_list),200);
            }
            
            $product_list = $sku_products_list = $po_invoice_list = array();
            
            $po_detail_data = Purchase_order_details::where('id',$invoice_id)->first();
            
            $grn_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','grn')->where('is_deleted',0)->where('status',1)->first();
            
            $qc_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','qc')->where('is_deleted',0)->where('status',1)->first();
            
            $po_data = Purchase_order::where('id',$po_detail_data->po_id)->first();
            
            $size_list = Production_size_counts::where('is_deleted',0)->get()->toArray();
            
            if(!empty($grn_data)){
                $sku_list = $sku_products_list = $sku_data = $product_list = array();
                
                $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('po_qc_items.grn_qc_id',$grn_data->id)        
                ->where('poi.order_id',$po_detail_data->po_id)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('po_qc_items.is_deleted',0) 
                ->where('ppm.is_deleted',0)
                ->groupByRaw('ppm.product_sku,ppm.size_id')        
                ->select('ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','ppm.size_id','ppm.base_price',\DB::raw('count(ppmi.id) as inv_count'),'poi.vendor_sku','poi.rate as poi_rate','poi.gst_percent as poi_gst_percent')
                ->get()->toArray();
                
               for($i=0;$i<count($sku_list);$i++){
                    if(in_array($sku_list[$i]->product_sku,$sku_data)){
                        $key = array_search($sku_list[$i]->product_sku,$sku_data);
                        $product_data = $sku_products_list[$key];
                        $product_data->size_data[$sku_list[$i]->size_id] = $sku_list[$i]->inv_count;
                        $sku_products_list[$key] = $product_data;
                        
                    }else{
                        $sku_list[$i]->size_data[$sku_list[$i]->size_id] = $sku_list[$i]->inv_count;
                        $sku_products_list[] = $sku_list[$i];
                        $sku_data[] = $sku_list[$i]->product_sku;
                    }
                }
                
                $invoices_with_qc = array();
                $po_qc_grn_list = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('type','qc')->where('is_deleted',0)->where('status',1)->select('po_detail_id')->get()->toArray();
                for($i=0;$i<count($po_qc_grn_list);$i++){
                    $invoices_with_qc[] = $po_qc_grn_list[$i]['po_detail_id'];
                }
                
                $po_invoice_list = Purchase_order_details::where('po_id',$po_detail_data->po_id)->wherein('id',$invoices_with_qc)->where('id','!=',$invoice_id)->where('grn_id','>',0)->where('is_deleted',0)->where('status',1)->get()->toArray();

                $excess_amount_debit_note = Debit_notes::where('po_id',$po_detail_data->po_id)->where('invoice_id',$po_detail_data->id)->where('debit_note_type','excess_amount')->where('debit_note_status','completed')->where('is_deleted',0)->first();
                
                $less_inv_debit_note = Debit_notes::where('po_id',$po_detail_data->po_id)->where('invoice_id',$po_detail_data->id)->where('debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice')->where('debit_note_status','completed')->where('is_deleted',0)->first();
            }
            
            return view('warehouse/sor_inventory_import',array('error_message'=>$error_message,'po_detail_data'=>$po_detail_data,'grn_data'=>$grn_data,
            'product_list'=>$product_list,'po_data'=>$po_data,'sku_products_list'=>$sku_products_list,'size_list'=>$size_list,'po_invoice_list'=>$po_invoice_list,
            'qc_data'=>$qc_data,'user'=>$user,'excess_amount_debit_note'=>$excess_amount_debit_note,'less_inv_debit_note'=>$less_inv_debit_note,'is_fake_inventory_user'=>$is_fake_inventory_user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/pos_inventory_import',array('error_message'=>$e->getMessage(),'demands_list'=>array()));
            }
        }
    }
    
    function submitImportSorInventory(Request $request){
        try{
            set_time_limit(600);
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $images_list = array();
            
            if(isset($data['action']) && $data['action']  == 'add_vehicle_details'){
                $validateionRules = array('vehicle_no'=>'required','containers_count'=>'required|numeric','invoice_no'=>'required','invoice_date'=>'required','products_count'=>'required|numeric');
                $attributes = array('vehicle_no'=>'Vehicle Number','containers_count'=>'No of Containers');
                
                if(!empty($data['containers_count'])){
                    for($i=1;$i<=$data['containers_count'];$i++){
                        $validateionRules['container_image_'.$i] = 'required|image|mimes:jpeg,png,jpg,gif|max:5120';
                    }
                }

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                \DB::beginTransaction();

                for($i=1;$i<=$data['containers_count'];$i++){
                    if(!empty($request->file('container_image_'.$i))){
                        $images_list[] = $image_name = CommonHelper::uploadImage($request,'container_image_'.$i,'images/po_images/'.$data['po_id']);
                    }
                }
                
                if(isset($data['invoice_date']) && !empty($data['invoice_date'])){
                    $invoice_date_arr = explode('/',$data['invoice_date']);
                    $invoice_date = $invoice_date_arr[2].'/'.$invoice_date_arr[1].'/'.$invoice_date_arr[0];
                }else{
                    $data['invoice_no'] = $data['products_count'] = $invoice_date = null ;
                }
                
                $po_data = Purchase_order::where('id',$data['po_id'])->first();
                
                $insertArray = array('po_id'=>$data['po_id'],'vehicle_no'=>$data['vehicle_no'],'containers_count'=>$data['containers_count'],'images_list'=>json_encode($images_list),'comments'=>$data['comments'],
                'invoice_no'=>$data['invoice_no'],'invoice_date'=>$invoice_date,'products_count'=>$data['products_count'],'user_id'=>$user->id,'fake_inventory'=>$po_data->fake_inventory);
                
                $po_details = Purchase_order_details::create($insertArray);

                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice Created. ID: '.$po_details->id,'PO_INVOICE_CREATED','PO_INVOICE');

                return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','po_details'=>$po_details,'message' => 'PO Invoice added successfully'),201);
            }
            
            if(isset($data['action']) && $data['action']  == 'add_inventory_grn'){
                \DB::beginTransaction();
                
                $grn_exists = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('type','grn')->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->count();
                if($grn_exists > 0){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'GRN already added for Invoice', 'errors' =>'GRN already added for Invoice' ));
                }
                
                $po_data = Purchase_order::where('id',$data['po_id'])->first();
                
                $today_latest_grn = Purchase_order_grn_qc::whereRaw("DATE(created_at) = CURDATE()")->where('type','grn')->select('grn_no')->orderBy('id','DESC')->first();
                $grn_number = (!empty($today_latest_grn))?substr($today_latest_grn->grn_no,10,3):0;
                $grn_number = 'KSGN'.Date('ymd').str_pad($grn_number+1,3,'0',STR_PAD_LEFT).'-'.str_ireplace('KSPO','',$po_data->order_no);
                
                if($po_data->fake_inventory == 0){
                    $products_count = Pos_product_master_inventory::where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->selectRaw('count(id) as inv_count')->first();
                    
                    $insertArray = array('grn_no'=>$grn_number,'po_id'=>$data['po_id'],'type'=>'GRN','comments'=>$data['add_inventory_grn_comments'],'po_detail_id'=>$data['po_detail_id']);
                    $insertArray['other_data'] = json_encode(array('total'=>$products_count->inv_count));
                    $grn = Purchase_order_grn_qc::create($insertArray);
                
                    $updateArray = array('grn_id'=>$grn->id,'products_count'=>$products_count->inv_count);
                    Purchase_order_details::where('id',$data['po_detail_id'])->update($updateArray);

                    $updateArray = array('grn_id'=>$grn->id);
                    Pos_product_master_inventory::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->update($updateArray);

                    $inventory_list = Pos_product_master_inventory::where('grn_id',$grn->id)->where('is_deleted',0)->select('id')->get()->toArray();

                    for($i=0;$i<count($inventory_list);$i++){
                        $insertArray = array('grn_qc_id'=>$grn->id,'inventory_id'=>$inventory_list[$i]['id'],'grn_qc_date'=>date('Y/m/d H:i:s'));
                        Purchase_order_grn_qc_items::create($insertArray);
                    }
                }else{
                    $po_detail_data = Purchase_order_details::where('id',$data['po_detail_id'])->first();
                    $products_count = $po_detail_data->products_count;
                    
                    $insertArray = array('grn_no'=>$grn_number,'po_id'=>$data['po_id'],'type'=>'GRN','comments'=>$data['add_inventory_grn_comments'],'po_detail_id'=>$data['po_detail_id'],'fake_inventory'=>1);
                    $insertArray['other_data'] = json_encode(array('total'=>$products_count));
                    $grn = Purchase_order_grn_qc::create($insertArray);
                    
                    $updateArray = array('grn_id'=>$grn->id);
                    Purchase_order_details::where('id',$data['po_detail_id'])->update($updateArray);
                    
                    $inventory_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->where('po_id',$po_data->id)        
                    ->whereRaw("(ppmi.po_detail_id IS NULL OR ppmi.po_detail_id = 0 OR ppmi.po_detail_id = '')")        
                    ->where('ppmi.is_deleted',0)        
                    ->select('ppmi.id','ppm.product_sku','ppm.size_id')
                    ->orderBy('ppmi.id')
                    ->limit($products_count)->get()->toArray();        
                    
                    $date = date('Y/m/d H:i:s');
                    
                    for($i=0;$i<count($inventory_list);$i++){
                        $updateArray = array('product_status'=>1,'intake_date'=>$date,'po_detail_id'=>$data['po_detail_id'],'grn_id'=>$grn->id);
                        Pos_product_master_inventory::where('id',$inventory_list[$i]->id)->update($updateArray); 

                        $po_detail_data = Purchase_order_items::where('order_id',$data['po_id'])->where('product_sku',trim($inventory_list[$i]->product_sku))->first();

                        $po_detail_data->increment('qty_received');
                        $po_detail_data->increment('qty_received_actual');

                        $size_id  = $inventory_list[$i]->size_id;
                        $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                        if(isset($po_size_data_received[$size_id])) $po_size_data_received[$size_id] = $po_size_data_received[$size_id]+1;else $po_size_data_received[$size_id] = 1;

                        $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                        Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                        
                        $insertArray = array('grn_qc_id'=>$grn->id,'inventory_id'=>$inventory_list[$i]->id,'grn_qc_date'=>$date,'fake_inventory'=>1);
                        Purchase_order_grn_qc_items::create($insertArray);
                    }
                    
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice GRN Created. GRN ID: '.$grn->id,'PO_INVOICE_GRN_CREATED','PO_INVOICE_GRN');
                
                return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'GRN data added successfully'),201);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_transfer_inventory_data'){
                $size_ids_added = $size_qty = array();
                
                $size_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('po_qc_items.grn_qc_id',$data['grn_id'])        
                ->where('ppm.product_sku',$data['transfer_sku'])        
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)
                ->where('po_qc_items.is_deleted',0) 
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
                ->groupByRaw('ppm.size_id')        
                ->select('psc.size as size_name','ppm.size_id',\DB::raw('count(po_qc_items.id) as inv_count'))
                ->get()->toArray();
                
                for($i=0;$i<count($size_data);$i++){
                    $size_qty[$size_data[$i]->size_id] = array('qty'=>$size_data[$i]->inv_count,'name'=>$size_data[$i]->size_name);
                }
                
                $size_ids = explode(',',trim($data['transfer_id_str']));
                
                if(empty($data['invoice_id'])){
                    $error_message = 'Invoice is Required Field <br/>';
                }
                
                $inv_added = false;
                for($i=0;$i<count($size_ids);$i++){
                    if(!empty($data['transfer_'.$size_ids[$i]])){
                        $inv_added = true;
                        break;
                    }
                }
                
                if($inv_added == false){
                    $error_message.='Transfer inventory is Required Field <br/>';
                }
                
                if($inv_added == true){
                    for($i=0;$i<count($size_ids);$i++){
                        $size_id = $size_ids[$i];
                        if(!empty($data['transfer_'.$size_id]) &&  filter_var(trim($data['transfer_'.$size_id]), FILTER_VALIDATE_INT) === false ){
                            $error_message.='Size '.$size_qty[$size_id]['name'].': Transfer inventory should have numeric value <br/>';
                        }
                        
                        if(!empty($data['transfer_'.$size_id]) &&  filter_var(trim($data['transfer_'.$size_id]), FILTER_VALIDATE_INT) !== false && $data['transfer_'.$size_id] > $size_qty[$size_id]['qty'] ){
                            $error_message.='Size '.$size_qty[$size_id]['name'].': Transfer inventory should not be greater than available inventory <br/>';
                        }
                    }
                }
                
                if(!empty($error_message)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_message, 'errors' => $error_message));
                }
                
                for($i=0;$i<count($size_ids);$i++){
                    if(!empty($data['transfer_'.$size_ids[$i]])){
                        $size_ids_added[] = $size_ids[$i];
                    }
                }                
                
                $target_invoice_id = trim($data['invoice_id']);
                $target_grn_data = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('po_detail_id',$target_invoice_id)->where('type','grn')->where('is_deleted',0)->first();
                
                $target_grn_qc_data = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('po_detail_id',$target_invoice_id)->where('type','qc')->where('is_deleted',0)->first();
                
                $sku = trim($data['transfer_sku']);
                $product_list = Pos_product_master::where('product_sku',$sku)->wherein('size_id',$size_ids_added)->get()->toArray();
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($product_list);$i++){
                    $product_id = $product_list[$i]['id'];
                    $size_id = $product_list[$i]['size_id'];
                    $inv_count = trim($data['transfer_'.$size_id]);
                    $inventory_data = Pos_product_master_inventory::where('product_master_id',$product_id)->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->where('status',1)->orderBy('id','DESC')->limit($inv_count)->get()->toArray();
                    
                    for($q=0;$q<count($inventory_data);$q++){
                        $inventory_id = $inventory_data[$q]['id'];
                        
                        $updateArray = array('grn_qc_id'=>$target_grn_data->id);
                        Purchase_order_grn_qc_items::where('grn_qc_id',$inventory_data[$q]['grn_id'])->where('inventory_id',$inventory_id)->where('is_deleted',0)->update($updateArray);

                        if($inventory_data[$q]['qc_id'] > 0 && !empty($target_grn_qc_data)){
                            $updateArray = array('grn_qc_id'=>$target_grn_qc_data->id);
                            Purchase_order_grn_qc_items::where('grn_qc_id',$inventory_data[$q]['qc_id'])->where('inventory_id',$inventory_id)->where('is_deleted',0)->update($updateArray);
                        }

                        $updateArray = array('grn_id'=>$target_grn_data->id,'po_detail_id'=>$target_invoice_id);
                        if(!empty($target_grn_qc_data)){
                            $updateArray['qc_id'] = $target_grn_qc_data->id;
                        }
                        
                        Pos_product_master_inventory::where('id',$inventory_id)->update($updateArray);
                    }
                    
                    Purchase_order_details::where('id',$data['po_detail_id'])->where('is_deleted',0)->decrement('products_count',$inv_count);
                    
                    Purchase_order_details::where('id',$target_invoice_id)->where('is_deleted',0)->increment('products_count',$inv_count);
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory transferred successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_po_invoice_details'){
                $validateionRules = array('invoice_no_detail'=>'required','invoice_date_detail'=>'required','vehicle_no_detail'=>'required','comments_detail'=>'required');
                $attributes = array('invoice_no_detail'=>'Invoice No','invoice_date_detail'=>'Invoice Date','vehicle_no_detail'=>'Vehicle Number','comments_detail'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }

                if(isset($data['invoice_date_detail']) && !empty($data['invoice_date_detail'])){
                    $invoice_date_arr = explode('/', str_replace('-', '/',$data['invoice_date_detail']));
                    $invoice_date = $invoice_date_arr[2].'/'.$invoice_date_arr[1].'/'.$invoice_date_arr[0];
                }else{
                    $invoice_date = null ;
                }
                
                $updateArray = array('invoice_no'=>$data['invoice_no_detail'],'invoice_date'=>$invoice_date,'comments'=>$data['comments_detail'],'vehicle_no'=>$data['vehicle_no_detail']);
                $po_details = Purchase_order_details::where('id',$data['po_detail_id'])->update($updateArray);
                
                CommonHelper::createLog('Purchase Order Invoice Updated. Invoice ID: '.$data['po_detail_id'],'PO_INVOICE_UPDATED','PO_INVOICE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'PO Invoice updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_inv_import_items'){
                $inv_items = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->wherein('ppmi.id',$data['deleteChk'])        
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)  
                ->where('ppm.is_deleted',0)   
                ->select('ppmi.*','ppm.product_sku','ppm.size_id')        
                ->get()->toArray();
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($inv_items);$i++){
                    
                    $updateArray = array('product_status'=>0,'intake_date'=>null,'po_detail_id'=>null);
                    Pos_product_master_inventory::where('id',$inv_items[$i]->id)->update($updateArray); 

                    $po_detail_data = Purchase_order_items::where('order_id',$data['po_id'])->where('product_sku',trim($inv_items[$i]->product_sku))->first();

                    $po_detail_data->decrement('qty_received');
                    $po_detail_data->decrement('qty_received_actual');

                    $size_id = $inv_items[$i]->size_id;
                    $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                    if(isset($po_size_data_received[$size_id])) $po_size_data_received[$size_id] = $po_size_data_received[$size_id]-1;else $po_size_data_received[$size_id] = 0;

                    $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                    Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice Updated. Invoice ID: '.$po_detail_data->id,'PO_INVOICE_UPDATED','PO_INVOICE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory import items deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_invoice_grn'){
                $grn_id = trim($data['id']);
                $po_id = trim($data['po_id']);
                $po_detail_id = trim($data['po_detail_id']);
                
                $qc_data = Purchase_order_grn_qc::where('po_id',$po_id)->where('po_detail_id',$po_detail_id)->where('type','qc')->where('is_deleted',0)->first();
                
                if(!empty($qc_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invoice QC is completed', 'errors' => 'Invoice QC is completed'));
                }
                
                $inv_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ppmi.po_id',$po_id)        
                ->where('ppmi.po_detail_id',$po_detail_id)                
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.arnon_inventory',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.size_id')
                ->get()->toArray();
                
                \DB::beginTransaction();
                
                $updateArray = array('is_deleted'=>1);
                Purchase_order_grn_qc::where('id',$grn_id)->update($updateArray);
                
                Purchase_order_grn_qc_items::where('grn_qc_id',$grn_id)->update($updateArray);
                
                $updateArray = array('grn_id'=>0);
                Purchase_order_details::where('id',$po_detail_id)->update($updateArray);
                
                $updateArray = array('grn_id'=>0,'product_status'=>0,'intake_date'=>null,'po_detail_id'=>0);
                Pos_product_master_inventory::where('po_id',$po_id)->where('po_detail_id',$po_detail_id)->update($updateArray);
                
                for($i=0;$i<count($inv_list);$i++){
                    $po_detail_data = Purchase_order_items::where('order_id',$inv_list[$i]->po_id)->where('product_sku',trim($inv_list[$i]->product_sku))->first();//echo $product->product_sku;exit;

                    $po_detail_data->decrement('qty_received');
                    $po_detail_data->decrement('qty_received_actual');

                    $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                    if(isset($po_size_data_received[$inv_list[$i]->size_id])) $po_size_data_received[$inv_list[$i]->size_id] = $po_size_data_received[$inv_list[$i]->size_id]-1;else $po_size_data_received[$inv_list[$i]->size_id] = 0;

                    $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                    Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice GRN Deleted. Invoice ID: '.$po_detail_data->id,'PO_INVOICE_GRN_DELETED','PO_INVOICE_GRN');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Invoice GRN Data deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_po_invoice'){
                $po_id = trim($data['po_id']);
                $po_detail_id = trim($data['po_detail_id']);
                
                $grn_data = Purchase_order_grn_qc::where('po_id',$po_id)->where('po_detail_id',$po_detail_id)->where('type','grn')->where('is_deleted',0)->first();
                
                if(!empty($grn_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invoice GRN is added', 'errors' => 'Invoice GRN is added'));
                }
                
                $qc_data = Purchase_order_grn_qc::where('po_id',$po_id)->where('po_detail_id',$po_detail_id)->where('type','qc')->where('is_deleted',0)->first();
                
                if(!empty($qc_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invoice QC is completed', 'errors' => 'Invoice QC is completed'));
                }
                
                $inv_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ppmi.po_id',$po_id)        
                ->where('ppmi.po_detail_id',$po_detail_id)                
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.arnon_inventory',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.size_id')
                ->get()->toArray();
                
                \DB::beginTransaction();
                
                $updateArray = array('is_deleted'=>1);
                Purchase_order_details::where('id',$po_detail_id)->update($updateArray);
                
                $updateArray = array('grn_id'=>0,'product_status'=>0,'intake_date'=>null,'po_detail_id'=>0);
                Pos_product_master_inventory::where('po_id',$po_id)->where('po_detail_id',$po_detail_id)->update($updateArray);
                
                for($i=0;$i<count($inv_list);$i++){
                    $po_detail_data = Purchase_order_items::where('order_id',$inv_list[$i]->po_id)->where('product_sku',trim($inv_list[$i]->product_sku))->first();//echo $product->product_sku;exit;

                    $po_detail_data->decrement('qty_received');
                    $po_detail_data->decrement('qty_received_actual');

                    $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                    if(isset($po_size_data_received[$inv_list[$i]->size_id])) $po_size_data_received[$inv_list[$i]->size_id] = $po_size_data_received[$inv_list[$i]->size_id]-1;else $po_size_data_received[$inv_list[$i]->size_id] = 0;

                    $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                    Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice Deleted. Invoice ID: '.$po_detail_id,'PO_INVOICE_DELETED','PO_INVOICE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Invoice deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'edit_grn_inventory'){
                $errors = $add_inv_list = $delete_inv_list =  $add_inv_qr_codes = $delete_inv_qr_codes = [];
                $total_add = trim($data['total_add']);
                $total_delete = trim($data['total_delete']);
                
                $po_id = trim($data['po_id']);
                $po_detail_id = trim($data['po_detail_id']);
                
                $grn_data = Purchase_order_grn_qc::where('po_id',$po_id)->where('po_detail_id',$po_detail_id)->where('type','grn')->where('is_deleted',0)->first();
                $qc_data = Purchase_order_grn_qc::where('po_id',$po_id)->where('po_detail_id',$po_detail_id)->where('type','qc')->where('is_deleted',0)->first();
                
                // check id demand is not already edited once
                if($grn_data->grn_edited == 1){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'GRN is already edited', 'errors' => 'GRN is already edited'));
                }
                
                if(!empty($qc_data)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'PO Invoice QC is completed. Inventory cannot be added', 'errors' => 'PO Invoice QC is completed. Inventory cannot be added'));
                }
                
                // Create Add inventory list
                for($i=1;$i<=$total_add;$i++){
                    $elem_name = 'qr_code_add_'.$i;
                    if(isset($data[$elem_name]) && !empty($data[$elem_name])){
                        $qr_code = trim($data[$elem_name]);
                        
                        $inv_data = \DB::table('pos_product_master_inventory as ppmi')
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                        ->where('ppmi.peice_barcode',$qr_code)->where('ppmi.is_deleted',0)->where('ppmi.fake_inventory',0)
                        ->where('ppm.is_deleted',0)->where('ppm.fake_inventory',0)        
                        ->select('ppmi.*','ppm.hsn_code','ppm.product_sku','ppm.size_id')        
                        ->first();
                        
                        if(empty($inv_data)){
                            $errors[$elem_name] = 'Product does not exists';
                        }elseif(!empty($inv_data) && $inv_data->product_status != 0){
                            $errors[$elem_name] = 'Not Warehouse In Pending';
                        }elseif(!empty($inv_data) && $inv_data->po_id != $po_id){
                            $errors[$elem_name] = 'Product does not exists in this Purchase Order';
                        }elseif(in_array($qr_code,$add_inv_qr_codes)){
                            $errors[$elem_name] = 'Duplicate QR Code';
                        }
                        
                        if(!in_array($qr_code,$add_inv_qr_codes)){
                            $add_inv_list[] = $inv_data;
                            $add_inv_qr_codes[] = $qr_code;
                        }
                    }
                }
                
                // Create delete inventory list
                for($i=1;$i<=$total_delete;$i++){
                    $elem_name = 'qr_code_delete_'.$i;
                    if(isset($data[$elem_name]) && !empty($data[$elem_name])){
                        $qr_code = trim($data[$elem_name]);
                        
                        $inv_data = \DB::table('pos_product_master_inventory as ppmi')
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                        ->where('ppmi.peice_barcode',$qr_code)->where('ppmi.is_deleted',0)->where('ppmi.fake_inventory',0)
                        ->where('ppm.is_deleted',0)->where('ppm.fake_inventory',0)        
                        ->select('ppmi.*','ppm.hsn_code','ppm.product_sku','ppm.size_id')                
                        ->first();
                        
                        if(empty($inv_data)){
                            $errors[$elem_name] = 'Product does not exists';
                        }elseif(!empty($inv_data) && $inv_data->po_detail_id != $po_detail_id){
                            $errors[$elem_name] = 'Prod does not exists in this Demand';
                        }elseif(in_array($delete_inv_qr_codes,$add_inv_qr_codes)){
                            $errors[$elem_name] = 'Duplicate QR Code';
                        }
                        
                        if(!in_array($qr_code,$delete_inv_qr_codes)){
                            $delete_inv_list[] = $inv_data;
                            $delete_inv_qr_codes[] = $qr_code;
                        }
                    }
                }
                
                if(!empty($errors)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $errors));
                }
                
                if(empty($add_inv_list) && empty($delete_inv_list)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Add/Delete QR Code is empty', 'errors' => 'Add/Delete QR Code is empty'));
                }
                
                \DB::beginTransaction();
                
                $date = date('Y/m/d H:i:s');
                
                for($i=0;$i<count($add_inv_list);$i++){
                    $product_data = $add_inv_list[$i];
                    $updateArray = array('product_status'=>1,'intake_date'=>$date,'po_detail_id'=>$data['po_detail_id'],'grn_id'=>$grn_data->id);
                    Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 

                    $po_detail_data = Purchase_order_items::where('order_id',$po_id)->where('product_sku',trim($product_data->product_sku))->first();

                    $po_detail_data->increment('qty_received');
                    $po_detail_data->increment('qty_received_actual');

                    $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                    if(isset($po_size_data_received[$product_data->size_id])) $po_size_data_received[$product_data->size_id] = $po_size_data_received[$product_data->size_id]+1;else $po_size_data_received[$product_data->size_id] = 1;

                    $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                    Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                    
                    $insertArray = array('grn_qc_id'=>$grn_data->id,'inventory_id'=>$product_data->id,'grn_qc_date'=>$date);
                    Purchase_order_grn_qc_items::create($insertArray);
                }
                
                for($i=0;$i<count($delete_inv_list);$i++){
                    $product_data = $delete_inv_list[$i];

                    $updateArray = array('is_deleted'=>1);
                    Purchase_order_grn_qc_items::where('grn_qc_id',$grn_data->id)->where('inventory_id',$product_data->id)->update($updateArray);

                    $updateArray = array('grn_id'=>0,'product_status'=>0,'intake_date'=>null,'po_detail_id'=>0);
                    Pos_product_master_inventory::where('id',$product_data->id)->where('po_id',$po_id)->update($updateArray);

                    $po_detail_data = Purchase_order_items::where('order_id',$po_id)->where('product_sku',trim($product_data->product_sku))->first();
                    $po_detail_data->decrement('qty_received');
                    $po_detail_data->decrement('qty_received_actual');

                    $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                    if(isset($po_size_data_received[$product_data->size_id])) $po_size_data_received[$product_data->size_id] = $po_size_data_received[$product_data->size_id]-1;else $po_size_data_received[$product_data->size_id] = 0;

                    $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                    Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                }
                
                $inv_count = Pos_product_master_inventory::where('grn_id',$grn_data->id)->where('is_deleted',0)->count();
                
                $updateArray = array('products_count'=>$inv_count);
                Purchase_order_details::where('id',$data['po_detail_id'])->update($updateArray);
                
                $updateArray = array('other_data'=>json_encode(array('total'=>$inv_count)),'grn_edited'=>1);
                Purchase_order_grn_qc::where('id',$grn_data->id)->update($updateArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice GRN Inventory Updated. Invoice ID: '.$po_detail_data->id,'PO_INVOICE_GRN_INVENTORY_UPDATED','PO_INVOICE_GRN');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'GRN Inventory updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'import_po_invoice_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $inv_id_array = $po_items_list = $sku_list = $barcodes_list = $barcodes_updated = array();
                $po_id = trim($data['po_id']);
                $po_detail_id = trim($data['po_detail_id']);
                
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
                
                $po_detail_data = Purchase_order_details::where('id',$po_detail_id)->first();
                
                $inv_imported = Pos_product_master_inventory::where('po_detail_id',$po_detail_id)
                ->where('po_id',$po_id)->where('product_status','>',0)
                ->where('is_deleted',0)->where('status',1)->count();
                
                // validation if invoice maximum products imported
                if(count($barcodes)+$inv_imported > $po_detail_data->products_count){
                    if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                        unlink(public_path($dest_folder).'/'.$file_name);
                    }
                    
                    $error_msg = 'Maximum '.$po_detail_data->products_count.' products can be imported in invoice <br>';
                    $error_msg.=' Products already imported: '.$inv_imported.' <br>';
                    $error_msg.=' Products in File: '.count($barcodes).' <br>';
                    
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }

                // Fetch barcoces inventory
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->wherein('ppmi.peice_barcode',$barcodes)
                ->where('ppmi.po_id',$po_id)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->select('ppmi.id','ppmi.product_status','ppmi.peice_barcode','ppm.product_sku','ppm.size_id')
                ->get()->toArray();
                
                // check if product status is 0
                for($i=0;$i<count($product_list);$i++){
                    if($product_list[$i]->product_status != 0){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product already added <br/>';
                    }
                    
                    $barcodes_list[] = $product_list[$i]->peice_barcode;
                }
                
                // iterate user provided barcodes to check with database barcodes
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
                
                $po_items = Purchase_order_items::where('order_id',$po_id)->get()->toArray();
                for($i=0;$i<count($po_items);$i++){
                    $po_items_list[trim($po_items[$i]['product_sku'])] = $po_items[$i];
                }
               
                for($i=0;$i<count($product_list);$i++){
                    $product_data = $product_list[$i];
                    $sku = trim($product_data->product_sku);
                    $size_id = $product_data->size_id;
                    $po_items_list[$sku]['qty_received'] = $po_items_list[$sku]['qty_received']+1;
                    $po_items_list[$sku]['qty_received_actual'] = $po_items_list[$sku]['qty_received_actual']+1;
                    
                    $po_size_data_received = (!empty($po_items_list[$sku]['size_data_received']))?json_decode($po_items_list[$sku]['size_data_received'],true):array();
                    
                    if(isset($po_size_data_received[$size_id])) 
                        $po_size_data_received[$size_id] = $po_size_data_received[$size_id]+1;
                    else 
                        $po_size_data_received[$size_id] = 1;

                    $po_items_list[$sku]['size_data_received'] = json_encode($po_size_data_received);
                    
                    $inv_id_array[] = $product_data->id;
                    $sku_list[] = trim($product_data->product_sku);
                }
                
                \DB::beginTransaction();
                
                // Update inventory
                $updateArray = array('product_status'=>1,'intake_date'=>date('Y/m/d H:i:s'),'po_detail_id'=>$po_detail_id);
                Pos_product_master_inventory::wherein('id',$inv_id_array)->update($updateArray); 
                
                $sku_list = array_values(array_unique($sku_list));
                
                // Update po items data only if sku in uploaded inventory sku
                foreach($po_items_list as $item_sku=>$item_data){
                    if(in_array($item_sku,$sku_list)){
                        $updateArray = array('qty_received'=>$item_data['qty_received'],'qty_received_actual'=>$item_data['qty_received_actual'],'size_data_received'=>$item_data['size_data_received']);
                        Purchase_order_items::where('order_id',$po_id)->where('product_sku',$item_sku)->update($updateArray);
                    }
                }
                
                \DB::commit();
                
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Updated Successfully '),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine()),500);
        }
    }
    
    function sorInventoryImportInvoice(Request $request,$id){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);

            $data = $request->all();
            $grn_id = $id;
            
            $vendor_details = \DB::table('purchase_order_grn_qc as po_grn')
            ->join('purchase_order as po','po.id', '=', 'po_grn.po_id')
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn.po_detail_id')             
            ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
            ->where('po_grn.id',$grn_id)
            ->select('vd.*','pod.invoice_no','pod.invoice_date','po_grn.id as po_grn_id','po.id as po_id','po_grn.grn_no','po_grn.created_at as grn_created_date','po.company_data')->first();      
            
            $products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('purchase_order_items as poi','poi.product_sku', '=', 'ppm.product_sku')        
            ->where('po_qc_items.grn_qc_id',$grn_id)        
            ->where('po_qc_items.is_deleted',0)                    
            ->where('poi.order_id',$vendor_details->po_id)         
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('ppm.is_deleted',0)
            ->groupBy('ppm.product_sku')        
            ->selectRaw('ppm.product_name,ppm.product_sku,ppmi.vendor_base_price,vendor_gst_percent,vendor_gst_amount,count(ppmi.id) as products_count,poi.rate as poi_rate,poi.gst_percent as poi_gst_percent,poi.vendor_sku')
            ->get()->toArray();
            
            //$company_data = CommonHelper::getCompanyData();
            $company_data = json_decode($vendor_details->company_data,true);
            
            $data = array('message' => 'Inventory import invoice','vendor_details'=>$vendor_details,'products_list'=>$products_list,'company_data'=>$company_data);
            
            //return view('warehouse/sor_inventory_import_invoice',$data);
            
            $pdf = PDF::loadView('warehouse/sor_inventory_import_invoice', $data);

            return $pdf->download('sor_inventory_import_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }
    }
    
    function qcSorInventory(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $po_detail_id = $id;
            $error_message = '';
            $inventory_status_list = $inventory_statuses  = array();
            $total_count = 0;
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')   
                ->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')         
                ->leftJoin('vendor_detail as vd','vd.id', '=', 'po.vendor_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])
                ->where('ppmi.product_status',1)
                ->where('ppmi.po_id',$data['po_id'])        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name',
                'psc.size as size_name','po.order_no as po_order_no','vd.name as vendor_name','poi.vendor_sku')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with code: '.$data['barcode'].' does not exists', 'errors' =>'Inventory Product with code: '.$data['barcode'].' does not exists' ));
                }
                
                if($product_data->qc_status != 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'QC not Pending for Product with code: '.$data['barcode'], 'errors' =>'QC not Pending for Product with code: '.$data['barcode'] ));
                }
                
                $updateArray = array('qc_status'=>2,'qc_date'=>date('Y/m/d H:i:s'));
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 
                
                Purchase_order_items::where('id',$product_data->po_item_id)->increment('qty_defective');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'QC completed for Product with code: '.$data['barcode'],'product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_po_inventory'){
                $rec_per_page = 100;
                $qc_data = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('type','qc')->where('is_deleted',0)->where('status',1)->first();
                $qc_return_data = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('type','qc_return')->where('is_deleted',0)->where('status',1)->first();
                
                if(empty($qc_data)){
                    $product_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                    ->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')         
                    ->leftJoin('vendor_detail as vd','vd.id', '=', 'po.vendor_id')                
                    ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                    ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('ppmi.po_id',$data['po_id'])
                    ->where('ppmi.po_detail_id',$data['po_detail_id'])
                    ->where('ppmi.qc_status','>',0)         
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)
                    ->where('ppm.is_deleted',0)
                    ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name',
                    'po.order_no as po_order_no','vd.name as vendor_name','poi.vendor_sku');        
                    
                    $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                    ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('ppmi.po_id',$data['po_id'])
                    ->where('ppmi.po_detail_id',$data['po_detail_id'])                
                    ->where('ppmi.qc_status','=',2)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)        
                    ->where('ppm.is_deleted',0)
                    ->groupByRaw('ppmi.product_master_id')
                    ->selectRaw('ppm.id,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                    ->orderByRaw('poi.vendor_sku,psc.id')        
                    ->get()->toArray();        
                    
                    $inventory_qc = \DB::table('pos_product_master_inventory as ppmi')
                    ->where('ppmi.po_id',$data['po_id'])
                    ->where('po_detail_id',$data['po_detail_id'])
                    ->where('ppmi.product_status','>',0)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)
                    ->groupBy('ppmi.qc_status')        
                    ->selectRaw('ppmi.qc_status,count(ppmi.id) as qc_count')->get()->toArray();
                     
                }else{
                    $product_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')     
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')      
                    ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                    ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                    ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->leftJoin('vendor_detail as vd','vd.id', '=', 'po.vendor_id')            
                    ->where('po_qc_items.grn_qc_id',$qc_data->id)    
                    ->where('po_qc_items.is_deleted',0)                    
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)
                    ->where('ppm.is_deleted',0)
                    ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name',
                    'po.order_no as po_order_no','vd.name as vendor_name','poi.vendor_sku','po_qc_items.qc_status','po_qc_items.grn_qc_date as qc_date');
                    
                    $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                    ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('poi.order_id',$qc_data->po_id) 
                    ->where('po_qc_items.grn_qc_id',$qc_data->id)                      
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)        
                    ->where('ppm.is_deleted',0)
                   ->groupByRaw('ppmi.product_master_id')
                    ->selectRaw('ppm.id,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                    ->orderByRaw('poi.vendor_sku,psc.id')        
                    ->get()->toArray();        
                    
                    $inventory_qc = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                    ->where('po_qc_items.grn_qc_id',$qc_data->id)  
                    ->where('po_qc_items.is_deleted',0)          
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)
                    ->groupBy('ppmi.qc_status')        
                    ->selectRaw('po_qc_items.qc_status,count(po_qc_items.id) as qc_count')->get()->toArray();
                }
                
                if(isset($data['barcode']) && !empty($data['barcode'])){
                    $product_list = $product_list->where('ppmi.peice_barcode',trim($data['barcode']));
                }
                
                if(isset($data['product_id']) && !empty($data['product_id'])){
                    $product_list = $product_list->where('ppmi.product_master_id',trim($data['product_id']));
                }
                
                $product_list = $product_list->orderBy('ppmi.id','ASC')->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $po_details = Purchase_order_details::where('po_id',$data['po_id'])->where('status',1)->where('is_deleted',0)->get()->toArray();;
                
                $inventory_qc_data = array('inventory_accepted'=>0,'inventory_defective'=>0,'inventory_qc_pending'=>0,'inventory_total'=>0);
                
                for($i=0;$i<count($inventory_qc);$i++){
                    if($inventory_qc[$i]->qc_status == 1)
                        $inventory_qc_data['inventory_accepted'] = $inventory_qc[$i]->qc_count;
                    elseif($inventory_qc[$i]->qc_status == 2)
                        $inventory_qc_data['inventory_defective'] = $inventory_qc[$i]->qc_count;
                    elseif($inventory_qc[$i]->qc_status == 0)
                        $inventory_qc_data['inventory_qc_pending'] = $inventory_qc[$i]->qc_count;

                    $inventory_qc_data['inventory_total']+=$inventory_qc[$i]->qc_count;
                }
            
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'po_details'=>$po_details,'inventory_qc_data'=>$inventory_qc_data,'qc_data'=>$qc_data,'sku_list'=>$sku_list,'qc_return_data'=>$qc_return_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_qc_data'){
                $inventory_qc = \DB::table('pos_product_master_inventory as ppmi')
                ->where('ppmi.po_id',$data['po_id'])->where('ppmi.po_detail_id',$data['po_detail_id'])->where('ppmi.product_status',1)        
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)->where('ppmi.qc_id',0)
                ->groupBy('ppmi.qc_status')        
                ->selectRaw('ppmi.qc_status,count(ppmi.id) as qc_count')->get()->toArray();
                
                $inventory_qc_data = array('inventory_accepted'=>0,'inventory_defective'=>0,'inventory_total'=>0);
                for($i=0;$i<count($inventory_qc);$i++){
                    if($inventory_qc[$i]->qc_status == 2)
                        $inventory_qc_data['inventory_defective'] = $inventory_qc[$i]->qc_count;
                    
                    $inventory_qc_data['inventory_total']+=$inventory_qc[$i]->qc_count;
                }
                
                $inventory_qc_data['inventory_accepted'] = $inventory_qc_data['inventory_total']-$inventory_qc_data['inventory_defective'];
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory QC data','inventory_qc_data'=>$inventory_qc_data,'status' => 'success'),200);
            }
            
            
            $product_list = $product_list_returned = $qc_return_data = $qc_return_gate_pass_data = array();
            $po_detail_data = Purchase_order_details::where('id',$po_detail_id)->where('is_deleted',0)->where('status',1)->first();
            
            $po_data = Purchase_order::where('id',$po_detail_data->po_id)->first();
            
            $grn_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','grn')->where('is_deleted',0)->where('status',1)->first();
            
            $qc_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','qc')->where('is_deleted',0)->where('status',1)->first();
            
            $inventory_status_data = array('inventory_imported'=>0,'inventory_import_pending'=>0,'inventory_total'=>0);
            
            if(empty($qc_data)){
                $inventory_status = Pos_product_master_inventory::where('po_id',$po_detail_data->po_id)
                ->where('po_detail_id',$po_detail_data->id)->where('product_status','>',0)->where('is_deleted',0)->where('status',1)
                ->selectRaw('count(id) as inventory_imported')->first();

                $inventory_status_data['inventory_imported'] = $inventory_status->inventory_imported;
                $inventory_status_data['inventory_total'] = $po_detail_data->products_count;
                $inventory_status_data['inventory_import_pending'] = $po_detail_data->products_count-$inventory_status_data['inventory_imported'];
            }    
            
            if(!empty($qc_data)){
                $qc_return_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','qc_return')->where('is_deleted',0)->where('status',1)->first();
                
                $inventory_defective = Pos_product_master_inventory::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('qc_status',2)->where('is_deleted',0)->where('status',1)->count();
                
                $qc_other_data = json_decode($qc_data->other_data,true);
                
                $qc_data->return_to_vendor = ($inventory_defective > 0)?1:0;
                if($qc_other_data['defective'] == 0)    
                    $qc_data->defective_returned = 'N/A';
                elseif($qc_other_data['defective'] > 0 && $inventory_defective > 0)
                    $qc_data->defective_returned = 'No';
                elseif($qc_other_data['defective'] > 0 && $inventory_defective == 0)
                    $qc_data->defective_returned = 'Yes';
                
                
                if(!empty($qc_return_data)){
                    $product_list_returned = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
                    ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                    ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('po_qc_items.grn_qc_id',$qc_return_data->id)   
                    ->where('po_qc_items.is_deleted',0)                    
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.status',1)
                    ->where('ppm.is_deleted',0)
                    ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','po_qc_items.grn_qc_date as qc_date_1','po_qc_items.qc_status as qc_status_1','poi.vendor_sku')
                    ->orderBy('qc_date','ASC')->paginate(50, ['*'], 'def_page');
                    
                    $qc_return_gate_pass_data = Store_products_demand_courier::where('invoice_id',$qc_return_data->id)->where('type','inventory_return')->where('is_deleted',0)->where('status',1)->first();
                }
                
                $inventory_statuses = Pos_product_master_inventory::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('is_deleted',0)->where('status',1)->groupBy('product_status')->selectRaw("product_status,COUNT(product_status) as inv_count")->get()->toArray();
                for($i=0;$i<count($inventory_statuses);$i++){
                    $inventory_status_list[$inventory_statuses[$i]['product_status']] = $inventory_statuses[$i]['inv_count'];
                    $total_count+=$inventory_statuses[$i]['inv_count'];
                }
                
                $inventory_status_list['total_count'] = $total_count;
            }
            
            return view('warehouse/sor_inventory_qc',array('error_message'=>$error_message,'user'=>$user,'po_data'=>$po_data,'qc_data'=>$qc_data,
                'inventory_status_data'=>$inventory_status_data,'grn_data'=>$grn_data,'po_detail_data'=>$po_detail_data,'product_list'=>$product_list,
                'product_list_returned'=>$product_list_returned,'qc_return_data'=>$qc_return_data,'qc_return_gate_pass_data'=>$qc_return_gate_pass_data,'inventory_status_list'=>$inventory_status_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/sor_inventory_qc',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function submitQcSorInventory(Request $request,$id){
        try{
            $data = $request->all();
            $po_detail_id = $id;
            $error_message = '';
            
            if(isset($data['action']) && $data['action']  == 'confirm_complete_inventory_qc'){
                \DB::beginTransaction();
                
                $po_data = Purchase_order::where('id',$data['po_id'])->first();
                
                $updateArray = array('qc_status'=>1,'qc_date'=>date('Y/m/d H:i:s'));
                Pos_product_master_inventory::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('product_status','>',0)->where('qc_status',0)->update($updateArray);
                
                $qc_defective_inventory = Pos_product_master_inventory::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('product_status','>',0)->where('qc_status',2)->where('is_deleted',0)->selectRaw('count(id) as inv_count')->first();
                
                $accepted_inv_count = Pos_product_master_inventory::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('product_status','>',0)->where('qc_status',1)->where('is_deleted',0)->selectRaw('count(id) as inv_count')->first();
                
                $accepted_inv_count = $accepted_inv_count->inv_count;
                $defective_inv_count = $qc_defective_inventory->inv_count;
                $total_inv_count = $accepted_inv_count+$defective_inv_count;
                
                $data_arr = array('total'=>$total_inv_count,'accepted'=>$accepted_inv_count,'defective'=>$defective_inv_count);
                
                $insertArray = array('po_id'=>$data['po_id'],'type'=>'QC','comments'=>$data['comments'],'po_detail_id'=>$data['po_detail_id'],'other_data'=>json_encode($data_arr),'fake_inventory'=>$po_data->fake_inventory);
                $qc = Purchase_order_grn_qc::create($insertArray);
                
                $qc_inventory = Pos_product_master_inventory::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('product_status','>',0)->where('is_deleted',0)->select('id','qc_status','qc_date')->get()->toArray();
                
                for($i=0;$i<count($qc_inventory);$i++){
                    $insertArray = array('grn_qc_id'=>$qc->id,'inventory_id'=>$qc_inventory[$i]['id'],'grn_qc_date'=>$qc_inventory[$i]['qc_date'],'qc_status'=>$qc_inventory[$i]['qc_status'],'fake_inventory'=>$po_data->fake_inventory);
                    Purchase_order_grn_qc_items::create($insertArray);
                }
                
                $updateArray = array('qc_id'=>$qc->id);
                Pos_product_master_inventory::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('product_status','>',0)->where('qc_status','>',0)->update($updateArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice QC Completed. Invoice ID: '.$data['po_detail_id'],'PO_INVOICE_QC_COMPLETED','PO_INVOICE_QC');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory QC completed','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'confirm_return_inventory'){
                \DB::beginTransaction();
                
                $validateionRules = array('boxes_count'=>'required|numeric','transporter_name'=>'required','transporter_gst'=>'required','docket_no'=>'required','eway_bill_no'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                /** Update po data start **/
                
                $product_sku_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ppmi.po_id',$data['po_id'])->where('ppmi.po_detail_id',$data['po_detail_id'])
                ->where('ppmi.qc_status',2)->where('ppmi.is_deleted',0)->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)         
                ->selectRaw('ppm.product_sku,count(ppmi.id) as inv_count')
                ->groupBy('ppm.product_sku')
                ->get()->toArray();
                
                for($i=0;$i<count($product_sku_list);$i++){
                    Purchase_order_items::where('order_id',$data['po_id'])->where('product_sku',$product_sku_list[$i]->product_sku)->decrement('qty_received',$product_sku_list[$i]->inv_count);
                    Purchase_order_items::where('order_id',$data['po_id'])->where('product_sku',$product_sku_list[$i]->product_sku)->increment('qty_returned',$product_sku_list[$i]->inv_count);
                }
                
                $product_sku_size_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ppmi.po_id',$data['po_id'])->where('ppmi.po_detail_id',$data['po_detail_id'])
                ->where('ppmi.qc_status',2)->where('ppmi.is_deleted',0)->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)        
                ->selectRaw('ppm.product_sku,ppm.size_id,count(ppmi.id) as inv_count')
                ->groupByRaw('ppm.product_sku,ppm.size_id')
                ->get()->toArray();
                
                for($i=0;$i<count($product_sku_size_list);$i++){
                    $po_item_data = Purchase_order_items::where('product_sku',$product_sku_size_list[$i]->product_sku)->first();
                    $size_data_rec = json_decode($po_item_data->size_data_received,true);
                    $size_data_rec[$product_sku_size_list[$i]->size_id] = $size_data_rec[$product_sku_size_list[$i]->size_id] - $product_sku_size_list[$i]->inv_count;
                    $updateArray = array('size_data_received'=>json_encode($size_data_rec));
                    Purchase_order_items::where('product_sku',$product_sku_size_list[$i]->product_sku)->update($updateArray);
                }
                
                /** Update po data end **/
                $qc_inventory = Pos_product_master_inventory::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('product_status','>',0)->where('qc_status',2)->where('is_deleted',0)->select('id','qc_status','qc_date')->get()->toArray();
                
                $invoice_no = CommonHelper::getPOInvoiceDebitNoteNo();
                $credit_note_no = CommonHelper::getPOInvoiceCreditNoteNo();
                
                $invoice_no_exists = Debit_notes::where('debit_note_no',$invoice_no)->where('invoice_series_type',2)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating GRN No', 'errors' =>'Error in creating GRN No' ));
                }
                
                $invoice_no_exists = Purchase_order_grn_qc::where('grn_no',$invoice_no)->where('invoice_series_type',2)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating GRN No', 'errors' =>'Error in creating GRN No' ));
                }

                $invoice_no_exists = Debit_notes::where('credit_note_no',$credit_note_no)->where('invoice_series_type',2)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Credit Note No', 'errors' =>'Error in creating Credit Note No' ));
                }
                
                $invoice_no_exists = Purchase_order_grn_qc::where('credit_note_no',$credit_note_no)->where('invoice_series_type',2)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Credit Note No', 'errors' =>'Error in creating Credit Note No' ));
                }
                    
                $insertArray = array('po_id'=>$data['po_id'],'type'=>'qc_return','comments'=>null,'po_detail_id'=>$data['po_detail_id'],'grn_no'=>$invoice_no,'credit_note_no'=>$credit_note_no);
                $insertArray['other_data'] = json_encode(array('total'=>count($qc_inventory)));
                $qc_return = Purchase_order_grn_qc::create($insertArray);
                
                for($i=0;$i<count($qc_inventory);$i++){
                    $insertArray = array('grn_qc_id'=>$qc_return->id,'inventory_id'=>$qc_inventory[$i]['id'],'grn_qc_date'=>date('Y/m/d H:i:s'),'qc_status'=>$qc_inventory[$i]['qc_status']);
                    Purchase_order_grn_qc_items::create($insertArray);
                }
                
                $updateArray = array('qc_status'=>0,'qc_date'=>null,'grn_id'=>0,'product_status'=>0,'qc_id'=>0,'po_detail_id'=>0);
                Pos_product_master_inventory::where('po_id',$data['po_id'])->where('po_detail_id',$data['po_detail_id'])->where('product_status',1)->where('qc_status',2)->where('is_deleted',0)->update($updateArray);
                
                $insertArray = array('type'=>'inventory_return','invoice_id'=>$qc_return->id,'boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no']);
                $demand_courier = Store_products_demand_courier::create($insertArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice QC Inventory Returned. ID: '.$qc_return->id.', Invoice ID: '.$data['po_detail_id'],'PO_INVOICE_QC_INVENTORY_RETURNED','PO_INVOICE_QC');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory returned to vendor','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_return_inv_gate_pass_data'){
                $validateionRules = array('boxes_count'=>'required|numeric','transporter_name'=>'required','transporter_gst'=>'required','docket_no'=>'required','eway_bill_no'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $updateArray = array('boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no']);
                Store_products_demand_courier::where('id',$data['qc_return_gate_pass_id'])->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Gate Pass updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_inv_qc_items'){
                \DB::beginTransaction();
                
                $updateArray = array('qc_status'=>0,'qc_date'=>null);
                Pos_product_master_inventory::wherein('id',$data['deleteChk'])->update($updateArray); 
                
                $inv_list = Pos_product_master_inventory::wherein('id',$data['deleteChk'])->get()->toArray();
                for($i=0;$i<count($inv_list);$i++){
                    Purchase_order_items::where('id',$inv_list[$i]['po_item_id'])->decrement('qty_defective');
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice QC Inventory Deleted. Invoice ID: '.$po_detail_id,'PO_INVOICE_QC_INVENTORY_DELETED','PO_INVOICE_QC');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'QC Inventory deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'edit_inventory_qc'){
                $validateionRules = array('qr_codes'=>'required','comments'=>'required');
                $attributes = array('qr_codes'=>'QR Codes');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $qr_codes = explode(',',rtrim(trim($data['qr_codes']),','));
                $qc_id = trim($data['qc_id']);
                $errors = $inv_list = array();
                
                for($i=0;$i<count($qr_codes);$i++){
                    $inv_product = Pos_product_master_inventory::where('peice_barcode',trim($qr_codes[$i]))->where('is_deleted',0)->first();
                    if(empty($inv_product)){
                        $errors[] = 'Inventory Product with QR Code <b>'.$qr_codes[$i].'</b> does not exists';
                        continue;
                    }
                    
                    if($inv_product->product_status != 1){
                        $errors[] = 'Inventory Product with QR Code <b>'.$qr_codes[$i].'</b> is not available in warehouse';
                        continue;
                    }
                    
                    if($inv_product->qc_id != $qc_id){
                        $errors[] = 'Inventory Product with QR Code <b>'.$qr_codes[$i].'</b> is not in this invoice';
                        continue;
                    }
                    
                    if($inv_product->qc_status != 1){
                        $errors[] = 'Inventory Product with QR Code <b>'.$qr_codes[$i].'</b> is not accepted in QC';
                        continue;
                    }
                    
                    $inv_list[] = $inv_product;
                }
                
                if(!empty($errors)){
                    $error_msg = implode('<br>',$errors);
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' => $error_msg));
                }
                
                \DB::beginTransaction();
                
                $date = date('Y/m/d H:i:s');
                
                for($i=0;$i<count($inv_list);$i++){
                    $updateArray = array('qc_status'=>2,'qc_date'=>$date);
                    Pos_product_master_inventory::where('id',$inv_list[$i]->id)->update($updateArray);
                    
                    $updateArray = array('grn_qc_date'=>$date,'qc_status'=>2);
                    Purchase_order_grn_qc_items::where('grn_qc_id',$qc_id)->where('inventory_id',$inv_list[$i]->id)->where('is_deleted',0)->update($updateArray);
                }
                
                \DB::commit();
                
                $defective_inv_count = Pos_product_master_inventory::where('qc_id',$qc_id)->where('qc_status',2)->where('is_deleted',0)->count();
                $accepted_inv_count = Pos_product_master_inventory::where('qc_id',$qc_id)->where('qc_status',1)->where('is_deleted',0)->count();
                $total_inv_count = $accepted_inv_count+$defective_inv_count;
                
                $data_arr = array('total'=>$total_inv_count,'accepted'=>$accepted_inv_count,'defective'=>$defective_inv_count);
                
                $updateArray = array('other_data'=>json_encode($data_arr),'comments'=>trim($data['comments']));
                Purchase_order_grn_qc::where('id',$qc_id)->update($updateArray);
                
                CommonHelper::createLog('Purchase Order Invoice QC Updated. Invoice ID: '.$po_detail_id,'PO_INVOICE_QC_UPDATED','PO_INVOICE_QC');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'QC Data updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_inv_item_qc'){
                $inv = Pos_product_master_inventory::where('id',$data['id'])->first();
                
                \DB::beginTransaction();
                
                $updateStatus = ($inv->qc_status == 1)?2:1;
                $date = date('Y/m/d H:i:s');
                
                $updateArray = array('qc_status'=>$updateStatus,'qc_date'=>$date);
                Pos_product_master_inventory::where('id',$inv->id)->update($updateArray);
                    
                $updateArray = array('grn_qc_date'=>$date,'qc_status'=>$updateStatus);
                Purchase_order_grn_qc_items::where('grn_qc_id',$inv->qc_id)->where('inventory_id',$inv->id)->where('is_deleted',0)->update($updateArray);
                
                \DB::commit();
                
                $defective_inv_count = Pos_product_master_inventory::where('qc_id',$inv->qc_id)->where('qc_status',2)->where('is_deleted',0)->count();
                $accepted_inv_count = Pos_product_master_inventory::where('qc_id',$inv->qc_id)->where('qc_status',1)->where('is_deleted',0)->count();
                $total_inv_count = $accepted_inv_count+$defective_inv_count;
                
                $data_arr = array('total'=>$total_inv_count,'accepted'=>$accepted_inv_count,'defective'=>$defective_inv_count);
                
                $updateArray = array('other_data'=>json_encode($data_arr));
                Purchase_order_grn_qc::where('id',$inv->qc_id)->update($updateArray);
                
                if($updateStatus == 2){
                    Purchase_order_items::where('id',$inv->po_item_id)->increment('qty_defective');
                }else{
                    Purchase_order_items::where('id',$inv->po_item_id)->decrement('qty_defective');
                }
                
                CommonHelper::createLog('Purchase Order Invoice QC Updated. Invoice ID: '.$po_detail_id,'PO_INVOICE_QC_UPDATED','PO_INVOICE_QC');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'QC Data updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_invoice_qc'){
                
                $qc_id = trim($data['id']);
                $po_id = trim($data['po_id']);
                $po_detail_id = trim($data['po_detail_id']);
                $inventory_status_list = array(1=>0);
                $total_count = 0;
                
                $inventory_statuses = Pos_product_master_inventory::where('po_id',$po_id)->where('po_detail_id',$po_detail_id)->where('is_deleted',0)->where('status',1)->groupBy('product_status')->selectRaw("product_status,COUNT(product_status) as inv_count")->get()->toArray();
                for($i=0;$i<count($inventory_statuses);$i++){
                    $inventory_status_list[$inventory_statuses[$i]['product_status']] = $inventory_statuses[$i]['inv_count'];
                    $total_count+=$inventory_statuses[$i]['inv_count'];
                }
                
                $inventory_status_list['total_count'] = $total_count;
                
                if($inventory_status_list['total_count'] != $inventory_status_list[1]){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'All Products of invoice are not available in warehouse', 'errors' => 'All Products of invoice are not available in warehouse'));
                }
                
                \DB::beginTransaction();
                
                $updateArray = array('is_deleted'=>1);
                Purchase_order_grn_qc::where('id',$qc_id)->update($updateArray);
                
                Purchase_order_grn_qc_items::where('grn_qc_id',$qc_id)->update($updateArray);
                
                $product_inv_list = \DB::table('pos_product_master_inventory as ppmi')
                ->where('ppmi.po_id',$po_id)
                ->where('ppmi.po_detail_id',$po_detail_id)
                ->where('ppmi.qc_status',2)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->select('ppmi.po_item_id')
                ->get()->toArray();
                
                for($i=0;$i<count($product_inv_list);$i++){
                    Purchase_order_items::where('id',$product_inv_list[$i]->po_item_id)->decrement('qty_defective');
                }
                
                $updateArray = array('qc_id'=>0,'qc_status'=>0,'qc_date'=>null);
                Pos_product_master_inventory::where('po_detail_id',$po_detail_id)->update($updateArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Invoice QC Deleted. Invoice ID: '.$po_detail_id,'PO_INVOICE_QC_DELETED','PO_INVOICE_QC');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory QC Deleted Successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'import_po_qc_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $inv_id_array = $barcodes_list = $barcodes_updated = array();
                $po_id = trim($data['po_id']);
                $po_detail_id = trim($data['po_detail_id']);
                
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
                ->where('ppmi.product_status',1)
                ->where('ppmi.po_id',trim($data['po_id']))        
                ->where('ppmi.po_detail_id',trim($data['po_detail_id']))               
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppmi.fake_inventory',0)
                ->select('ppmi.id','ppmi.peice_barcode','ppmi.qc_status','ppmi.po_item_id')
                ->get()->toArray();
                
                // Check if product qc status is 0
                for($i=0;$i<count($product_list);$i++){
                    if($product_list[$i]->qc_status != 0){
                        $error_msg.=$product_list[$i]->peice_barcode.': QC not Pending <br/>';
                    }
                    
                    $barcodes_list[] = $product_list[$i]->peice_barcode;
                    $inv_id_array[] = $product_list[$i]->id;
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
                
                $updateArray = array('qc_status'=>2,'qc_date'=>date('Y/m/d H:i:s'));
                Pos_product_master_inventory::wherein('id',$inv_id_array)->update($updateArray); 
                
                for($i=0;$i<count($product_list);$i++){
                    Purchase_order_items::where('id',$product_list[$i]->po_item_id)->increment('qty_defective',1);
                }
                
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory QC Updated Successfully '),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function SorInventoryReturnedInvoice(Request $request,$id,$invoice_type_id = 1){
        try{
            $data = $request->all();
            $qc_return_id = $id;
            $user = Auth::user();
            $products_list = $po_data = $company_data = $products_sku = $qc_return_sku_list = array();
            
            $invoice_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            
            $po_data = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order as po','po.id', '=', 'po_grn_qc.po_id')        
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')                
            ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
            ->where('po_grn_qc.id',$qc_return_id)
            ->select('vd.*','pod.invoice_no','pod.invoice_date','po.id as po_id','po_grn_qc.grn_no','po_grn_qc.credit_note_no','po_grn_qc.grn_no as debit_note_no','po_grn_qc.po_detail_id','po_grn_qc.created_at as qc_return_date','po.company_data')->first();
            
            $grn_data = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->where('po_detail_id',$po_data->po_detail_id)
            ->where('po_grn_qc.type','grn')        
            ->first();
            
            $po_data->po_grn_no = $grn_data->grn_no;
                    
            //$company_data = CommonHelper::getCompanyData();
            $company_data = json_decode($po_data->company_data,true);
            
            if($po_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($po_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            }else{
                $gst_name = '';
            }
            
            $qc_return_inv_list = \DB::table('purchase_order_grn_qc_items as po_grn_qc_items')
            ->join('pos_product_master_inventory as ppmi','po_grn_qc_items.inventory_id', '=', 'ppmi.id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
            ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')        
            ->where('po_grn_qc_items.grn_qc_id',$qc_return_id)
            ->where('poi.order_id',$po_data->po_id)        
            ->where('po_grn_qc_items.is_deleted',0) 
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)
            ->select('po_grn_qc_items.id','ppmi.base_price','ppmi.vendor_base_price','ppmi.vendor_gst_percent','ppmi.vendor_gst_amount','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku')        
            ->distinct()->get()->toArray();
            
            for($i=0;$i<count($qc_return_inv_list);$i++){
                $sku = $qc_return_inv_list[$i]->product_sku;
                if(!isset($qc_return_sku_list[$sku])){
                    $qc_return_sku_list[$sku] = array('prod'=>$qc_return_inv_list[$i],'qty'=>1);
                }else{
                    $qc_return_sku_list[$sku]['qty']+= 1;
                }
            }
            
            $data = array('message' => 'products list','qc_return_sku_list' => $qc_return_sku_list,'po_data' => $po_data,'company_data'=>$company_data,'gst_name'=>$gst_name,'invoice_type'=>$invoice_type);
            
            //return view('warehouse/sor_inventory_returned_invoice',$data);
            
            //PDF::setOptions(['defaultFont' => 'sans-serif','defaultPaperSize'=>'a4']);
            $pdf = PDF::loadView('warehouse/sor_inventory_returned_invoice', $data);

            return $pdf->download('inventory_returned_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function SorInventoryReturnGatePass(Request $request,$id){
        try{
            $data = $request->all();
            $qc_return_id = $id;
            $user = Auth::user();
            
            $po_data = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order as po','po.id', '=', 'po_grn_qc.po_id')        
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')                
            ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
            ->where('po_grn_qc.id',$qc_return_id)
            ->select('vd.*','pod.invoice_no','pod.invoice_date','po_grn_qc.other_data')->first();
            
            //$total_qty = Store_products_demand_detail::where('demand_id',$demand_id)->where('is_deleted',0)->where('status',1)->selectRaw('SUM(product_quantity) as total_qty')->first();
            $other_data = json_decode($po_data->other_data,true);
            $total_qty = $other_data['total'];
            
            $gate_pass_data = Store_products_demand_courier::where('invoice_id',$qc_return_id)->where('type','inventory_return')->where('is_deleted',0)->where('status',1)->first();
            $company_data = CommonHelper::getCompanyData();
            
            $data = array('message' => 'products list','gate_pass_data' => $gate_pass_data,'po_data' => $po_data,'company_data'=>$company_data,'total_qty'=>$total_qty);
            
            //return view('warehouse/sor_inventory_return_gate_pass',$data);
            
            $pdf = PDF::loadView('warehouse/sor_inventory_return_gate_pass', $data);

            return $pdf->download('sor_inventory_return_gate_pass_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }
    }
    
    function inventoryReturnDemandLoad(Request $request,$id){
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
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product does not exists', 'errors' =>'Inventory Product does not exists' ));
                }
                
                if($product_data->product_status == 1){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> is available in warehouse', 'errors' =>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> is available in warehouse' ));
                }
                
                if($product_data->product_status != 6){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> is not returned from store', 'errors' =>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> is not returned from store' ));
                }
                
                $demand_data = Store_products_demand::where('id',$data['demand_id'])->first();
                
                $demand_inventory_prod = Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->first();
                
                if(empty($demand_inventory_prod)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> does not exist in this demand', 'errors' =>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> does not exist in this demand' ));
                }
                
                \DB::beginTransaction();
                
                Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->update(array('receive_status'=>1,'receive_date'=>date('Y/m/d H:i:s'))); 
                
                Pos_product_master_inventory::where('id',$product_data->id)->update(array('product_status'=>1,'store_assign_date'=>null,'store_intake_date'=>null,'demand_id'=>null,'store_base_rate'=>null,'store_gst_percent'=>null,'store_gst_amount'=>null,'store_base_price'=>null)); 
                
                //$demand_product = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->increment('store_intake_qty');
                
                if(strtolower($demand_data->demand_status) == 'warehouse_dispatched'){
                    Store_products_demand::where('id',$data['demand_id'])->update(array('demand_status'=>'warehouse_loading'));
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> updated successfully','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_demand_inventory'){
                $rec_per_page = 100;
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')            
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])         
                ->where('spdi.receive_status',1)           
                ->where('spdi.is_deleted',0)   
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.created_at as store_out_date')
                ->orderBy('spdi.id','ASC')->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                $total_inventory = Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('is_deleted',0)->count();
                $received_inventory = Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('receive_status',1)->where('is_deleted',0)->count();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'total_inventory' => $total_inventory,'received_inventory'=>$received_inventory),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_count_data'){
                $inv_data = array('rec_count'=>0,'rec_base_price_sum'=>0,'rec_sale_price_sum'=>0);
                
                $inv_loaded = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')
                ->where('spdi.demand_id',$data['demand_id'])        
                ->where('spdi.receive_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)        
                ->selectRaw('COUNT(ppmi.id) as cnt,SUM(ppmi.base_price) as base_price_sum,SUM(ppmi.sale_price) as sale_price_sum,SUM(spdi.store_base_price) as store_base_price_sum')->first();
                
                if(!empty($inv_loaded)){
                    $inv_data['rec_count'] = $inv_loaded->cnt;
                    $inv_data['rec_base_price_sum'] = $inv_loaded->base_price_sum;
                    $inv_data['rec_sale_price_sum'] = $inv_loaded->sale_price_sum;
                    $inv_data['rec_store_base_price_sum'] = $inv_loaded->store_base_price_sum;
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Return Inventory data','inv_data'=>$inv_data,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'close_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('comments_close_demand'=>'required');
                $attributes = array('comments_close_demand'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $credit_note_no =  CommonHelper::getReturnDemandCreditNoteNo();
                $invoice_no_exists = Store_products_demand::where('credit_invoice_no',$credit_note_no)->where('invoice_series_type',2)->count();
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                }
                
                $updateArray = array('demand_status'=>'warehouse_loaded','comments'=>trim($data['comments_close_demand']),'credit_invoice_no'=>$credit_note_no,'credit_note_date'=>date('Y/m/d H:i:s'));
                
                Store_products_demand::where('id',$data['demand_id'])->update($updateArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Store to Warehouse Demand closed by Warehouse. Demand ID: '.$data['demand_id'],'STORE_TO_WAREHOUSE_DEMAND_CLOSED','STORE_TO_WAREHOUSE_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand Return Inventory updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'import_demand_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $inv_id_array = $barcodes_list = $barcodes_updated = array();
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
                ->wherein('ppmi.peice_barcode',$barcodes)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppmi.fake_inventory',0)
                ->select('ppmi.id','ppmi.peice_barcode','ppmi.product_master_id','ppmi.product_status')
                ->get()->toArray(); 
                
                $demand_inventory = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->select('inventory_id')->get()->toArray(); 
                $demand_inv_ids = array_column($demand_inventory, 'inventory_id');
                
                // Check if product status is 6
                for($i=0;$i<count($product_list);$i++){
                    if(!in_array($product_list[$i]->id, $demand_inv_ids)){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product does not exists in this demand <br/>';
                    }elseif($product_list[$i]->product_status != 6){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product not transit to warehouse from store <br/>';
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
                
                \DB::beginTransaction();
                
                Store_products_demand_inventory::where('demand_id',$demand_id)->wherein('inventory_id',$inv_id_array)->where('is_deleted',0)->update(array('receive_status'=>1,'receive_date'=>date('Y/m/d H:i:s'))); 
                
                $updateArray = array('product_status'=>1,'store_assign_date'=>null,'store_intake_date'=>null,'demand_id'=>null,'store_base_rate'=>null,'store_gst_percent'=>null,'store_gst_amount'=>null,'store_base_price'=>null);
                Pos_product_master_inventory::wherein('id',$inv_id_array)->update($updateArray); 
                
                if(strtolower($demand_data->demand_status) == 'warehouse_dispatched'){
                    Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'warehouse_loading'));
                }
                
                /*for($i=0;$i<count($product_list);$i++){
                    Store_products_demand_detail::where('demand_id',$demand_id)->where('product_id',$product_list[$i]->product_master_id)->where('is_deleted',0)->increment('store_intake_qty');
                }*/
                
                \DB::commit();
                
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully'),200);
            }
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)
            ->where('spd.status',1)
            ->where('spd.is_deleted',0)
            ->select('spd.*','s.store_name')
            ->first();
            
            /*if(strtolower($demand_data->demand_status) == 'warehouse_dispatched'){
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>'warehouse_loading'));
            }*/
            
            return view('warehouse/inventory_return_demand_load',array('error_message'=>$error_message,'demand_data'=>$demand_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_RETURN_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('warehouse/inventory_return_demand_load',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryAssignDemandList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            if(isset($data['action']) && $data['action'] == 'create_demand'){
                $invoice_no = '';
                
                $insertArray = array('invoice_no'=>$invoice_no,'user_id'=>$user->id,'store_id'=>null,'demand_type'=>'inventory_assign','demand_status'=>'approval_pending','store_data'=>null);
                $demand = Store_products_demand::create($insertArray);
                
                CommonHelper::createLog('Inventory Assign Demand Created. Demand ID: '.$demand->id,'INVENTORY_ASSIGN_DEMAND_CREATED','INVENTORY_ASSIGN_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Assign demand created successfully','demand_details'=>$demand,'status' => 'success'),200);
            }
            
            $demands_list = \DB::table('store_products_demand as spd')
            ->join('users as u','u.id', '=', 'spd.user_id')                
            ->where('spd.demand_type','inventory_assign')
            ->where('spd.status',1)
            ->where('spd.is_deleted',0);
            
            $demands_list = $demands_list->select('spd.*','u.name as user_name')->orderBy('spd.id','DESC')->paginate(100);
            
            return view('warehouse/inventory_assign_demand_list',array('demands_list'=>$demands_list,'error_message'=>$error_message,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_ASSIGN_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_assign_demand_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function editInventoryAssignDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)
            ->select('spd.*','u.name as user_name')->first();
            
            $store_list = CommonHelper::getStoresList();
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->select('*')->get()->toArray();
            
            if(isset($data['action']) && $data['action'] == 'get_sku_size_list'){
                $demand_id = trim($data['demand_id']);
                $product_sku = trim($data['product_sku']);
                $product_ids = array();
                
                // product list of sku in products table
                $product_list = Pos_product_master::where('product_sku',$product_sku)->where('is_deleted',0)->select('id','size_id')->get()->toArray();
                
                for($i=0;$i<count($product_list);$i++){
                    $product_ids[] = $product_list[$i]['id'];
                }
                
                $demand_detail = \DB::table('store_products_demand_detail as spdd')
                ->join('pos_product_master as ppm','ppm.id', '=', 'spdd.product_id')        
                ->where('spdd.demand_id',$demand_id)
                ->wherein('spdd.product_id',$product_ids)->where('spdd.is_deleted',0)
                ->where('spdd.store_id','>',0) 
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
                ->where('spdd.is_deleted',0)        
                ->select('spdd.*','ppm.size_id')        
                ->get()->toArray();
                
                $product_available_qty = \DB::table('pos_product_master as ppm')
                ->join('pos_product_master_inventory as ppmi','ppm.id', '=', 'ppmi.product_master_id')        
                ->wherein('ppm.id',$product_ids)
                ->where('ppmi.product_status',1)        
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)
                ->groupBy('ppm.size_id')
                ->selectRaw('ppm.size_id,COUNT(ppmi.id) as qty')        
                ->get()->toArray();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product SKU List','product_list'=>$product_list,'demand_detail'=>$demand_detail,'product_qty'=>$product_available_qty),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_qty'){
                $demand_id = trim($data['demand_id']);
                $product_sku = trim($data['product_sku']);
                $product_sku_size_list = Pos_product_master::where('product_sku',$product_sku)->where('is_deleted',0)->get()->toArray();
                
                // Validation
                for($i=0;$i<count($store_list);$i++){
                    for($q=0;$q<count($product_sku_size_list);$q++){
                        $store_id = trim($store_list[$i]['id']);
                        $product_id = trim($product_sku_size_list[$q]['id']);
                        $size_id = trim($product_sku_size_list[$q]['size_id']);
                        
                        if(isset($data['qty_'.$store_id.'_'.$size_id]) &&  filter_var(trim($data['qty_'.$store_id.'_'.$size_id]), FILTER_VALIDATE_INT) === false ){
                            return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Size Quantity should have numeric value', 'errors' => 'Size Quantity should have numeric value'));
                        }
                    }
                }
                
                // Update demand
                for($i=0;$i<count($store_list);$i++){
                    $store_id = trim($store_list[$i]['id']);
                    for($q=0;$q<count($product_sku_size_list);$q++){
                        $product_id = trim($product_sku_size_list[$q]['id']);
                        $size_id = trim($product_sku_size_list[$q]['size_id']);
                        $qty = (isset($data['qty_'.$store_id.'_'.$size_id]) && is_numeric($data['qty_'.$store_id.'_'.$size_id]))?trim($data['qty_'.$store_id.'_'.$size_id]):0;
                        $demand_detail = Store_products_demand_detail::where('demand_id',$demand_id)->where('store_id',$store_list[$i]['id'])->where('product_id',$product_id)->where('is_deleted',0)->first();
                        if(empty($demand_detail)){
                           $insertArray = array('demand_id'=>$demand_id,'store_id'=>$store_id,'product_id'=>$product_id,'product_quantity'=>$qty); 
                           Store_products_demand_detail::create($insertArray);
                        }else{
                            $updateArray = array('product_quantity'=>$qty);
                            Store_products_demand_detail::where('id',$demand_detail->id)->update($updateArray);
                        }
                    }
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand updated successfully'),200);
            }
            
            $product_sku = \DB::table('pos_product_master as ppm')
            ->join('pos_product_master_inventory as ppmi','ppm.id', '=', 'ppmi.product_master_id')    
            ->where('ppmi.product_status',1)        
            ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)
            ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)        
            ->select('ppm.product_sku')
            ->groupBy('ppm.id')        
            ->orderBy('product_sku')->distinct()->get()->toArray();
            
            return view('warehouse/inventory_assign_demand_edit',array('demand_data'=>$demand_data,'error_message'=>$error_message,'product_sku'=>$product_sku,'size_list'=>$size_list,'store_list'=>$store_list,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_ASSIGN_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_assign_demand_edit',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryAssignDemandDetail(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            $product_sku_list = array();
            
            if(isset($data['action']) && $data['action'] == 'update_demand'){
                $demand_id = trim($data['demand_id']);
                $status = trim($data['status']);
                Store_products_demand::where('id',$demand_id)->update(array('demand_status'=>$status));
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Demand Updated Successfully'),200);
            }
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)
            ->select('spd.*','u.name as user_name')->first();
            
            $demand_detail_list = \DB::table('store_products_demand_detail as spdd')
            ->join('pos_product_master as ppm','ppm.id', '=', 'spdd.product_id')        
            ->where('spdd.demand_id',$demand_id)
            ->where('spdd.store_id','>',0)        
            ->where('spdd.is_deleted',0)
            ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
            ->select('spdd.*','ppm.product_sku')        
            ->get()->toArray();
            
            for($i=0;$i<count($demand_detail_list);$i++){
                $product_sku_list[] = $demand_detail_list[$i]->product_sku;
            }
            
            $product_sku_list = array_values(array_unique($product_sku_list));
            sort($product_sku_list);
            $store_list = CommonHelper::getStoresList();
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->select('*')->get()->toArray();
            
            return view('warehouse/inventory_assign_demand_detail',array('demand_data'=>$demand_data,'error_message'=>$error_message,'product_sku_list'=>$product_sku_list,'store_list'=>$store_list,'size_list'=>$size_list,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_ASSIGN_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_assign_demand_detail',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryReturnVendorDemandList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $po_list = array();
            
            if(isset($data['action']) && $data['action'] == 'get_po_invoice_list'){
                $invoice_list = Purchase_order_details::where('po_id',trim($data['po_id']))->where('is_deleted',0)->get()->toArray();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Invoice List','invoice_list'=>$invoice_list,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'create_demand'){
                
                //valid demand status: warehouse_loading, warehouse_dispatched 
                
                $validateionRules = array('vendor_id'=>'required');
                $attributes = array('vendor_id'=>'Vendor','po_invoice_id'=>'Purchase Order Invoice');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $vendor_data = Vendor_detail::where('id',trim($data['vendor_id']))->first();
                $vendor_data->state_id = $vendor_data->state;
                $company_data = CommonHelper::getCompanyData();
                
                /*$invoice_no = CommonHelper::inventoryPushDemandInvoiceNo($vendor_data);
                $credit_note_no = null;
                
                $invoice_no_exists = Store_products_demand::where('invoice_no',$invoice_no)->count();
                
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                }*/
                
                $invoice_no = $credit_note_no = null;
                
                $insertArray = array('invoice_no'=>$invoice_no,'credit_invoice_no'=>$credit_note_no,'user_id'=>$user->id,'demand_type'=>'inventory_return_to_vendor','demand_status'=>'warehouse_loading','push_demand_id'=>null,'store_id'=>$vendor_data->id,'store_data'=>json_encode($vendor_data),'store_state_id'=>$vendor_data->state);
                
                $insertArray['company_gst_no'] = $company_data['company_gst_no'];
                $insertArray['company_gst_name'] = $company_data['company_name'];
                
                $demand = Store_products_demand::create($insertArray);
                
                CommonHelper::createLog('Warehouse to Vendor Demand Created. Demand ID: '.$demand->id,'WAREHOUSE_TO_VENDOR_DEMAND_CREATED','WAREHOUSE_TO_VENDOR_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory return demand created successfully','demand_details'=>$demand,'status' => 'success'),200);
            }
            
            $demands_list = \DB::table('store_products_demand as spd')
            //->join('purchase_order_details as pod','pod.id', '=', 'spd.push_demand_id')                
            //->join('purchase_order as po','po.id', '=', 'pod.po_id')         
            ->join('vendor_detail as vd','vd.id', '=', 'spd.store_id')          
            ->join('users as u','u.id', '=', 'spd.user_id')            
            ->where('spd.demand_type','inventory_return_to_vendor')
            ->where('spd.status',1)
            ->where('spd.is_deleted',0);
            
            if($user->user_type == 15){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->first();
                $demands_list = $demands_list->where('demand_status','warehouse_dispatched')
                ->where('spd.store_id',$vendor_data->id);   // vendor id is stored in store_id column
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $invoice_no = trim($data['invoice_no']);
                $demands_list = $demands_list->where('spd.invoice_no','LIKE','%'.$invoice_no.'%');
            }
            
            if(isset($data['invoice_id']) && !empty($data['invoice_id'])){
                $demands_list = $demands_list->where('spd.id',trim($data['invoice_id']));
            }
            
            $demands_list = $demands_list->select('spd.*','u.name as user_name','vd.name as vendor_name');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $demands_list = $demands_list->offset($start)->limit($limit)->orderBy('spd.id','ASC')->get()->toArray();
            }else{
                $demands_list = $demands_list->orderBy('spd.id','DESC')->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=inventory_return_to_vendor_demands_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Demand ID','Invoice No','Vendor','Demand Status','Created By','Created On');

                $callback = function() use ($demands_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($demands_list);$i++){
                        $array = array($demands_list[$i]->id,$demands_list[$i]->invoice_no,$demands_list[$i]->vendor_name,ucwords(str_replace('_',' ',$demands_list[$i]->demand_status)),$demands_list[$i]->user_name,date('d-m-Y',strtotime($demands_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $vendors_list = CommonHelper::getVendorsList();
            
            return view('warehouse/inventory_return_vendor_demand_list',array('demands_list'=>$demands_list,'error_message'=>$error_message,'user'=>$user,'po_list'=>$po_list,'vendors_list'=>$vendors_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_RETURN_VENDOR_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_return_vendor_demand_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function editInventoryReturnVendorDemand(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_product'){
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','ppmi.po_item_id','=','poi.id')            
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$data['barcode'])        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)    
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','poi.vendor_id')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product does not exists', 'errors' =>'Inventory Product does not exists' ));
                }
                
                if($product_data->product_status != 1){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> Status is not available in warehouse', 'errors' =>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> Status is not available in warehouse' ));
                }
                
                $demand_data = Store_products_demand::where('id',$data['demand_id'])->first();
                /*
                if($demand_data->push_demand_id != $product_data->po_detail_id){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> does not exists in PO Invoice', 'errors' =>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> does not exists in PO Invoice' ));
                }*/
                
                //$demand_data->store_id is vendor id in demand
                if($product_data->arnon_inventory == 0 && $demand_data->store_id != $product_data->vendor_id){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> have different vendor from demand', 'errors' =>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> have different vendor from demand' ));
                }
                
                $demand_inventory_prod = Store_products_demand_inventory::where('demand_id',$data['demand_id'])->where('inventory_id',$product_data->id)->where('is_deleted',0)->first();
                
                if(!empty($demand_inventory_prod)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> already added in this demand', 'errors' =>'Inventory Product with QR code: <b>'.$product_data->peice_barcode.'</b> already added in this demand' ));
                }
                
                \DB::beginTransaction();
                
                $insertArray = array('demand_id'=>$data['demand_id'],'inventory_id'=>$product_data->id,'transfer_status'=>0,'vendor_base_price'=>$product_data->vendor_base_price,'vendor_gst_percent'=>$product_data->vendor_gst_percent,'vendor_gst_amount'=>$product_data->vendor_gst_amount,'base_price'=>$product_data->base_price,'sale_price'=>$product_data->sale_price,
                'transfer_date'=>date('Y/m/d H:i:s'),'store_id'=>$demand_data->store_id,'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                Store_products_demand_inventory::create($insertArray); 
                
                /*$demand_product = Store_products_demand_detail::where('demand_id',$data['demand_id'])->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->first();
                if(empty($demand_product)){
                    $insertArray = array('demand_id'=>$data['demand_id'],'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                    Store_products_demand_detail::create($insertArray);
                }else{
                    $demand_product->increment('product_quantity');
                }*/
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory product with QR code: <b>'.$product_data->peice_barcode.'</b> added successfully','product_data'=>$product_data),200);
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
                
                CommonHelper::createLog('Warehouse to Vendor Demand Inventory Deleted. Demand ID: '.$demand_id,'WAREHOUSE_TO_VENDOR_DEMAND_INVENTORY_DELETED','WAREHOUSE_TO_VENDOR_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Return Inventory items deleted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'load_demand_inventory'){
                $rec_per_page = 200;
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('purchase_order_items as poi','ppmi.po_item_id','=','poi.id')                  
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('spdi.demand_id',$data['demand_id'])         
                ->where('spdi.is_deleted',0)   
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')
                ->orderBy('ppmi.store_intake_date','ASC')
                ->paginate($rec_per_page);
                
                $paging_links =  (string) $product_list->links();
                $paging_links = str_replace('pagination','pagination pagination-ajax',$paging_links);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Inventory data','product_list'=>$product_list,'paging_links'=>$paging_links,'rec_per_page'=>$rec_per_page,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_inventory_count_data'){
                $demand_data = Store_products_demand::where('id',$data['demand_id'])->first();
                $vendor_data = json_decode($demand_data->store_data,true);
                $demand_total_data = CommonHelper::updateDemandTotalData($data['demand_id'],2,1);
                $total_data = $demand_total_data['total_data'];
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Return Inventory data','demand_data'=>$demand_data,'vendor_data'=>$vendor_data,'total_data'=>$total_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'close_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('comments_close_demand'=>'required');
                $attributes = array('comments_close_demand'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                // Check if Demand have Duplicate Inventory
                $duplicate_inv_qrcodes = CommonHelper::getDemandDuplicateInventory($demand_id);
                if(!empty($duplicate_inv_qrcodes)){
                    $duplicate_inv_qrcodes_str = implode(', ',$duplicate_inv_qrcodes);
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Demand have Duplicate Inventory with QR Codes: '.$duplicate_inv_qrcodes_str, 'errors' =>'Demand have Duplicate Inventory with QR Codes: '.$duplicate_inv_qrcodes_str));
                }
                
                $demand_data = Store_products_demand::where('id',trim($data['demand_id']))->first();
                $vendor_data = json_decode($demand_data->store_data,false);
                $invoice_no = CommonHelper::inventoryPushDemandInvoiceNo($vendor_data);
                
                $invoice_no_exists = Store_products_demand::where('invoice_no',$invoice_no)->where('invoice_series_type',2)->count();
                
                if($invoice_no_exists > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Invoice No', 'errors' =>'Error in creating Invoice No' ));
                }
                
                $updateArray = array('invoice_no'=>$invoice_no,'demand_status'=>'warehouse_dispatched','comments'=>($data['comments_close_demand']),'debit_note_date'=>date('Y/m/d H:i:s'));
                Store_products_demand::where('id',$data['demand_id'])->update($updateArray);
                
                $inv_loaded = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')
                ->where('spdi.demand_id',$data['demand_id'])        
                ->where('ppmi.product_status',1)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)       
                ->where('spdi.demand_status',1)        
                ->select('spdi.*','ppmi.product_status')
                ->get()->toArray();
                
                for($i=0;$i<count($inv_loaded);$i++){
                    $updateArray = array('product_status'=>7,'demand_id'=>$data['demand_id']);
                    Pos_product_master_inventory::where('id',$inv_loaded[$i]->inventory_id)->update($updateArray);

                    Store_products_demand_inventory::where('id',$inv_loaded[$i]->id)->update(array('transfer_status'=>1,'transfer_date'=>date('Y/m/d H:i:s'))); 
                }
                
                CommonHelper::updateDemandTotalData($data['demand_id'],2);
                
                \DB::commit();
                
                CommonHelper::createLog('Warehouse to Vendor Demand Closed. Demand ID: '.$data['demand_id'],'WAREHOUSE_TO_VENDOR_DEMAND_CLOSED','WAREHOUSE_TO_VENDOR_DEMAND');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Vendor Return Inventory Demand updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'import_demand_inventory'){
                set_time_limit(300);
                $error_msg = '';
                $product_list = $demand_detail_listing = $barcodes_list = array();
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
                
                $demand_data = Store_products_demand::where('id',$demand_id)->first();
                $demand_inventory = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->select('inventory_id')->get()->toArray(); 
                $demand_inv_ids = array_column($demand_inventory, 'inventory_id');
                
                $product_list = \DB::table('pos_product_master_inventory as ppmi')
                ->leftJoin('purchase_order_items as poi','ppmi.po_item_id','=','poi.id')              
                ->wherein('ppmi.peice_barcode',$barcodes)          
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppmi.fake_inventory',0)
                ->select('ppmi.*','poi.vendor_id')
                ->get()->toArray();        
                
                // Check if product status is 1
                for($i=0;$i<count($product_list);$i++){
                    if($product_list[$i]->product_status != 1){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product is not available in warehouse <br/>';
                    }elseif($product_list[$i]->arnon_inventory == 0 && $demand_data->store_id != $product_list[$i]->vendor_id){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product have different vendor from demand <br/>';
                    }elseif(in_array($product_list[$i]->id, $demand_inv_ids)){
                        $error_msg.=$product_list[$i]->peice_barcode.': Inventory Product already added in this demand <br/>';
                    }
                    
                    $barcodes_list[] = $product_list[$i]->peice_barcode;
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
                    $insertArray = array('demand_id'=>$demand_id,'inventory_id'=>$product_data->id,'transfer_status'=>0,'vendor_base_price'=>$product_data->vendor_base_price,'vendor_gst_percent'=>$product_data->vendor_gst_percent,'vendor_gst_amount'=>$product_data->vendor_gst_amount,'base_price'=>$product_data->base_price,'sale_price'=>$product_data->sale_price,'transfer_date'=>$date,
                    'store_id'=>$demand_data->store_id,'product_id'=>$product_data->product_master_id,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);
                    
                    Store_products_demand_inventory::create($insertArray); 

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
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*')->first();
            
            return view('warehouse/inventory_return_vendor_demand_edit',array('error_message'=>$error_message,'demand_data'=>$demand_data));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'INVENTORY_RETURN_VENDOR_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_return_vendor_demand_edit',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryReturnVendorDemandDetail(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $error_message = '';
            $size_list = [];
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','u.name as user_name')->first();
            
            if(isset($data['action']) && $data['action']  == 'update_gate_pass_data'){
                $validateionRules = array('boxes_count'=>'required|numeric','transporter_name'=>'required','transporter_gst'=>'required','docket_no'=>'required','eway_bill_no'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                $courier_data = Store_products_demand_courier::where('demand_id',$data['demand_id'])->where('type','inventory_return_vendor')->where('is_deleted',0)->first();
                
                if(empty($courier_data)){
                    $insertArray = array('boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no'],'type'=>'inventory_return_vendor','demand_id'=>$data['demand_id']);
                    Store_products_demand_courier::create($insertArray);
                }else{
                    $updateArray = array('boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no']);
                    Store_products_demand_courier::where('id',$courier_data->id)->update($updateArray);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Gate Pass updated successfully','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'cancel_demand'){
                \DB::beginTransaction();
                
                $validateionRules = array('comments'=>'required');
                $attributes = array('comments'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                Store_products_demand::where('id',$data['demand_id'])->update(array('demand_status'=>'cancelled','cancel_comments'=>trim($data['comments']),'cancel_user_id'=>$user->id,'cancel_date'=>date('Y/m/d H:i:s')));
                
                $inv_returned = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')
                ->where('spdi.demand_id',$demand_data->id)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)        
                ->select('spdi.*')
                ->get()->toArray();
                
                for($i=0;$i<count($inv_returned);$i++){
                    $updateArray = array('product_status'=>1,'demand_id'=>null);
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
            ->select('ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','ppm.size_id','dlim_1.name as color_name')        
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
            ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','spdi.vendor_base_price','spdi.vendor_gst_percent','spdi.vendor_gst_amount','spdi.base_price','spdi.sale_price')
            ->orderBy('spdi.created_at','ASC')->paginate(200, ['*'], 'inv_page');
            
            $gate_pass_data = \DB::table('store_products_demand_courier')
            ->where('demand_id',$demand_id)->where('status',1)->where('is_deleted',0)
            ->select('*')->first();
                
            return view('warehouse/inventory_return_vendor_demand_detail',array('product_list'=>$product_list,'product_inventory'=>$product_inventory,'demand_data'=>$demand_data,'error_message'=>$error_message,'user'=>$user,'gate_pass_data'=>$gate_pass_data,'products'=>$products,'products_sku'=>$products_sku,'size_list'=>$size_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_RETURN_VENDOR_DEMAND',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_return_vendor_demand_detail',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function inventoryReturnVendorDemandInvoice(Request $request,$id,$invoice_type_id = 1){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(180);
            $data = $request->all();
            $user = Auth::user();
            $demand_id = $id;
            $products_list = $demand_data = $company_data = $products_sku = $demand_sku_list = array();
            
            $invoice_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            $demand_data = Store_products_demand::where('id',$demand_id)->first();
            
            $vendor_data = (!empty($demand_data->store_data))?json_decode($demand_data->store_data,false):Vendor_detail::where('id',$demand_data->store_id)->first();
            $company_data = CommonHelper::getCompanyData();
            
            if($vendor_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($vendor_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            }else{
                $gst_name = '';
            }
            
            if($demand_data->invoice_type  == 'product_sku'){
                $demand_products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','spdi.inventory_id', '=', 'ppmi.id')            
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')                
                ->where('spdi.demand_id',$demand_id)        
                ->where('spdi.transfer_status',1)
                ->where('spdi.is_deleted',0)        
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)
                ->groupBy('ppm.id')        
                ->select('spdi.base_price','spdi.vendor_base_price','spdi.vendor_gst_percent','spdi.vendor_gst_amount','ppm.product_name','ppm.product_sku','poi.vendor_sku','ppm.hsn_code',\DB::raw('COUNT(ppmi.id) as product_quantity'))        
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
                ->where('spdi.demand_id',$demand_id)        
                ->where('spdi.transfer_status',1)
                ->where('spdi.is_deleted',0)        
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)
                ->select('spdi.base_price','spdi.vendor_base_price','spdi.vendor_gst_percent','spdi.vendor_gst_amount','ppm.product_name','ppm.product_sku','poi.vendor_sku','ppm.hsn_code','ppm.id as product_id','ppm.size_id','ppm.color_id')        
                ->get()->toArray();

                for($i=0;$i<count($demand_products_list);$i++){
                    $key = $demand_products_list[$i]->product_id;
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
            
            $data = array('message' => 'products list','demand_sku_list' => $demand_sku_list,'company_data'=>$company_data,'gst_name'=>$gst_name,'vendor_data'=>$vendor_data,'demand_data'=>$demand_data,'invoice_type'=>$invoice_type,'sizes'=>$sizes,'colors'=>$colors);
            
            //return view('warehouse/inventory_return_vendor_demand_invoice',$data);
            
            $pdf = PDF::loadView('warehouse/inventory_return_vendor_demand_invoice', $data);

            return $pdf->download('inventory_return_vendor_demand_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_RETURN_VENDOR_DEMAND',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function inventoryReturnVendorDemandGatePass(Request $request,$id){
        try{
            $data = $request->all();
            $demand_id = $id;
            $user = Auth::user();
            
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('users as u','u.id', '=', 'spd.user_id')        
            ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
            ->select('spd.*','u.name as user_name')->first();
            
            $gate_pass_data = Store_products_demand_courier::where('demand_id',$demand_id)->where('type','inventory_return_vendor')->where('is_deleted',0)->first();
            $company_data = CommonHelper::getCompanyData();
            
            $total_qty = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->count();
            
            $data = array('message' => 'gate pass','gate_pass_data' => $gate_pass_data,'demand_data' => $demand_data,'company_data'=>$company_data,'total_qty'=>$total_qty);
            
            //return view('warehouse/inventory_return_vendor_gate_pass',$data);
            
            $pdf = PDF::loadView('warehouse/inventory_return_vendor_gate_pass', $data);

            return $pdf->download('inventory_return_vendor_gate_pass_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_RETURN_VENDOR_DEMAND',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }
    }
    
    function trackInventory(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            if(isset($data['action']) && $data['action'] == 'check_product'){
                $barcode = trim($data['barcode']);
                
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->where('ppmi.peice_barcode',$barcode)  
                ->where('ppmi.fake_inventory',0)        
                ->where('ppmi.is_deleted',0)
                ->first();        
                
                if(empty($product_data)){
                   return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product does not exists', 'errors' =>'Inventory Product does not exists' ));
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory product data','product_data'=>$product_data),200);
                        
                //  Inventory Product data from database
                $product_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
                ->join('purchase_order as po','po.id','=','ppmi.po_id')                
                ->leftJoin('store as s1','s1.id','=','ppmi.store_id')                
                ->leftJoin('store_products_demand as spd','spd.id', '=', 'ppmi.demand_id')        
                ->leftJoin('store as s2','s2.id','=','spd.store_id')                        
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('ppmi.peice_barcode',$barcode)  
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)               
                ->select('ppmi.*','s1.store_name','s2.store_name as demand_store_name','spd.store_id as demand_store_id','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku','po.order_no as po_no','ppm.hsn_code')
                ->first();
                
                if(empty($product_data)){
                   return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product does not exists', 'errors' =>'Inventory Product does not exists' ));
                }
                
                $product_stages = array();
                $product_stages[] = array('product_status'=>0,'date'=>$product_data->created_at,'timestamp'=>strtotime($product_data->created_at),'desc'=>'Inventory Product Created with QR Code: '.$product_data->peice_barcode,'ref_data'=>array(array('name'=>'PO No','ref_no'=>$product_data->po_no)));
                
                // GRN data
                $grn_data = \DB::table('purchase_order_grn_qc as po_grn_qc')
                ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')
                ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')        
                ->where('po_grn_qc.po_id',$product_data->po_id)        
                ->where('po_grn_qc_items.inventory_id',$product_data->id)                
                ->where('po_grn_qc.type','grn')                
                ->where('po_grn_qc.is_deleted',0)
                ->where('po_grn_qc_items.is_deleted',0)        
                ->orderByRaw('po_grn_qc.id ASC, po_grn_qc_items.id ASC')
                ->select('po_grn_qc.*','pod.invoice_no','pod.id as pod_id')        
                ->first();        
                
                if(!empty($grn_data)){
                    $product_stages[] = array('product_status'=>1,'date'=>$grn_data->created_at,'timestamp'=>strtotime($grn_data->created_at),'desc'=>'Inventory Product GRN Created. Product QC Pending','ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$grn_data->invoice_no),array('name'=>'Invoice ID','ref_no'=>$grn_data->pod_id),array('name'=>'GRN No','ref_no'=>$grn_data->grn_no)));
                }
                
                // QC data
                $qc_data = \DB::table('purchase_order_grn_qc as po_grn_qc')
                ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')
                ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')        
                ->where('po_grn_qc.po_id',$product_data->po_id)        
                ->where('po_grn_qc_items.inventory_id',$product_data->id)                
                ->where('po_grn_qc.type','qc')                
                ->where('po_grn_qc.is_deleted',0)
                ->where('po_grn_qc_items.is_deleted',0)        
                ->orderByRaw('po_grn_qc.id ASC, po_grn_qc_items.id ASC')
                ->select('po_grn_qc.*','pod.invoice_no','po_grn_qc_items.qc_status')        
                ->first();        
                
                if(!empty($qc_data)){
                    $qc_text = ($qc_data->qc_status == 1)?'QC Accepted':'QC Rejected';
                    $product_stages[] = array('product_status'=>1,'date'=>$qc_data->created_at,'timestamp'=>strtotime($qc_data->created_at),'desc'=>'Inventory Product QC Completed. '.$qc_text,'ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$qc_data->invoice_no)));
                }
                
                // Inventory Push Demand data. Product is pushed to store
                $demand_data = \DB::table('store_products_demand as spd')
                ->join('store_products_demand_inventory as spdi','spd.id', '=', 'spdi.demand_id')
                ->join('store as s','s.id', '=', 'spd.store_id')        
                ->where('spd.demand_type','inventory_push')        
                ->where('spdi.inventory_id',$product_data->id)                
                ->where('spd.is_deleted',0)
                ->where('spdi.is_deleted',0)        
                ->where('spdi.demand_status',1)        
                ->orderByRaw('spd.id ASC, spdi.id ASC')
                ->select('spd.*','spdi.transfer_status','spdi.transfer_date','spdi.receive_status','spdi.receive_date','s.store_name',
                 'spd.discount_applicable as demand_discount_applicable','spd.discount_percent as demand_discount','spd.gst_inclusive as demand_gst_inclusive')        
                ->first();        
                
                if(!empty($demand_data)){
                    $net_price_str = '';
                    if($product_data->product_status == 2 || $product_data->product_status == 3 || $product_data->product_status == 4){
                        $net_price = 0;
                        $discount_data = CommonHelper::getdiscount($product_data->peice_barcode);
                       
                        if($discount_data['status'] == 'success' && isset($discount_data['data']['discount_percent'])){
                            $discount_percent = $discount_data['data']['discount_percent'];
                            $gst_inclusive = $discount_data['data']['gst_including'];
                            $discount_id = $discount_data['data']['id'];
                        }else{
                            $discount_percent = ($demand_data->demand_discount_applicable == 1)?$demand_data->demand_discount:CommonHelper::getPosDiscountPercent();
                            $discount_id = 0;
                            $gst_inclusive = ($demand_data->demand_gst_inclusive !== null)?$demand_data->demand_gst_inclusive:$demand_data->gst_inclusive;
                        }
                        
                        $discount_price = ($discount_percent > 0)?($product_data->sale_price*($discount_percent/100)):0;
                        $discounted_price = round($product_data->sale_price-$discount_price,2);

                        if($gst_inclusive == 0){
                            $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$discounted_price);
                            $gst_percent = (!empty($gst_data))?$gst_data->rate_percent:0; 
                            $gst_amount = (!empty($gst_percent))?round($discounted_price*($gst_percent/100),2):0;
                            $net_price = $discounted_price+$gst_amount;
                        }else{
                            $net_price = $discounted_price;
                        }
                        
                        $net_price_str = ' (NET Price: '.$net_price.' )';
                    }
                    
                    $product_stages[] = array('product_status'=>3,'date'=>$demand_data->transfer_date,'timestamp'=>strtotime($demand_data->transfer_date),'desc'=>'Inventory Product pushed to '.$demand_data->store_name.$net_price_str,'ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$demand_data->invoice_no)));
                    if($demand_data->receive_status == 1){
                        $product_stages[] = array('product_status'=>4,'date'=>$demand_data->receive_date,'timestamp'=>strtotime($demand_data->receive_date),'desc'=>'Inventory Product received at '.$demand_data->store_name.' Store','ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$demand_data->invoice_no)));
                    }
                }
                
                // Order data. Product is sold from store
                $order_list = \DB::table('pos_customer_orders as pco')
                ->join('pos_customer_orders_detail as pcod','pco.id', '=', 'pcod.order_id')
                ->join('store as s','s.id', '=', 'pco.store_id')                        
                ->where('pcod.inventory_id',$product_data->id)    
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)        
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)           
                ->orderByRaw('pco.id ASC')
                ->select('pco.*','pcod.product_quantity','pcod.net_price','s.store_name')        
                ->get()->toArray();
                
                for($i=0;$i<count($order_list);$i++){
                    if($order_list[$i]->product_quantity > 0){
                        $product_stages[] = array('product_status'=>5,'date'=>$order_list[$i]->created_at,'timestamp'=>strtotime($order_list[$i]->created_at),'desc'=>'Inventory Product sold from '.$order_list[$i]->store_name.' Store (NET Price: '.$order_list[$i]->net_price.' )','ref_data'=>array(array('name'=>'Order No','ref_no'=>$order_list[$i]->order_no)));
                    }else{
                        $product_stages[] = array('product_status'=>4,'date'=>$order_list[$i]->created_at,'timestamp'=>strtotime($order_list[$i]->created_at),'desc'=>'Inventory Product returned to '.$order_list[$i]->store_name.' Store','ref_data'=>array(array('name'=>'Order No','ref_no'=>$order_list[$i]->order_no)));
                    }
                }
                
                // Inventory Return Demand data. Product is returned to warehouse by store
                $demand_data = \DB::table('store_products_demand as spd')
                ->join('store_products_demand_inventory as spdi','spd.id', '=', 'spdi.demand_id')
                ->join('store as s','s.id', '=', 'spd.store_id')        
                ->where('spd.demand_type','inventory_return_to_warehouse')        
                ->where('spdi.inventory_id',$product_data->id)                
                ->where('spd.is_deleted',0)
                ->where('spdi.is_deleted',0)     
                ->where('spdi.demand_status',1)        
                ->orderByRaw('spd.id ASC, spdi.id ASC')
                ->select('spd.*','spdi.transfer_status','spdi.transfer_date','spdi.receive_status','spdi.receive_date','s.store_name')        
                ->first();        
                
                if(!empty($demand_data)){
                    $product_stages[] = array('product_status'=>6,'date'=>$demand_data->transfer_date,'timestamp'=>strtotime($demand_data->transfer_date),'desc'=>'Inventory Product returned to Warehouse from '.$demand_data->store_name.' Store','ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$demand_data->invoice_no)));
                    if($demand_data->receive_status == 1){
                        $product_stages[] = array('product_status'=>7,'date'=>$demand_data->receive_date,'timestamp'=>strtotime($demand_data->receive_date),'desc'=>'Inventory Product received at Warehouse from '.$demand_data->store_name.' Store','ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$demand_data->invoice_no)));
                    }
                }
                
                // Inventory Return Demand data. Product is returned to vendor by warehouse
                $demand_data = \DB::table('store_products_demand as spd')
                ->join('store_products_demand_inventory as spdi','spd.id', '=', 'spdi.demand_id')
                ->join('store as s','s.id', '=', 'spd.store_id')        
                ->where('spd.demand_type','inventory_return_to_vendor')        
                ->where('spdi.inventory_id',$product_data->id)                
                ->where('spd.is_deleted',0)
                ->where('spdi.is_deleted',0)        
                ->where('spdi.demand_status',1)        
                ->orderByRaw('spd.id ASC, spdi.id ASC')
                ->select('spd.*','spdi.transfer_status','spdi.transfer_date','spdi.receive_status','spdi.receive_date','s.store_name')        
                ->first();        
                
                if(!empty($demand_data)){
                    $product_stages[] = array('product_status'=>8,'date'=>$demand_data->transfer_date,'timestamp'=>strtotime($demand_data->transfer_date),'desc'=>'Inventory Product returned to vendor from Warehouse','ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$demand_data->invoice_no)));
                }
                
                // Inventory Transfer Demand data. Product is transferred from one store to other
                $demand_data = \DB::table('store_products_demand as spd')
                ->join('store_products_demand_inventory as spdi','spd.id', '=', 'spdi.demand_id')
                ->join('store as s1','s1.id', '=', 'spd.from_store_id')        
                ->join('store as s2','s2.id', '=', 'spd.store_id')          
                ->where('spd.demand_type','inventory_transfer_to_store')        
                ->where('spdi.inventory_id',$product_data->id)                
                ->where('spd.is_deleted',0)
                ->where('spdi.is_deleted',0)        
                ->where('spdi.demand_status',1)        
                ->orderByRaw('spd.id ASC, spdi.id ASC')
                ->select('spd.*','spdi.transfer_status','spdi.transfer_date','spdi.receive_status','spdi.receive_date','s1.store_name as from_store_name','s2.store_name as to_store_name')        
                ->first();        
                
                if(!empty($demand_data)){
                    $product_stages[] = array('product_status'=>4,'date'=>$demand_data->transfer_date,'timestamp'=>strtotime($demand_data->transfer_date),'desc'=>'Inventory Product transferred from '.$demand_data->from_store_name.' Store to '.$demand_data->to_store_name.' Store','ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$demand_data->invoice_no)));
                    if($demand_data->receive_status == 1){
                        $product_stages[] = array('product_status'=>4,'date'=>$demand_data->receive_date,'timestamp'=>strtotime($demand_data->receive_date),'desc'=>'Inventory Product received at '.$demand_data->to_store_name.' Store from '.$demand_data->from_store_name.' Store','ref_data'=>array(array('name'=>'Invoice No','ref_no'=>$demand_data->invoice_no)));
                    }
                }
                
                $totals = array_column($product_stages,'timestamp');
                array_multisort($totals, SORT_ASC, $product_stages);
                
                $product_data->product_status_text = CommonHelper::getposProductStatusName($product_data->product_status);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory product data','product_data'=>$product_data,'product_stages'=>$product_stages),200);
            }
            
            return view('warehouse/inventory_track',array('error_message'=>$error_message));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'INVENTORY_TRACK',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('warehouse/inventory_track',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function addSorInvoiceDebitNote(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $invoice_id = $id;
            
            $po_data = \DB::table('purchase_order as po')
            ->Join('purchase_order_details as pod','po.id','=','pod.po_id')
            ->where('pod.id',$invoice_id)
            ->select('po.*','pod.id as po_detail_id','pod.debit_note_added','pod.debit_note_data','pod.debit_note_no')
            ->first();
            
            $debit_note = Debit_notes::where('po_id',$po_data->id)->where('invoice_id',$po_data->po_detail_id)->where('debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice')->where('is_deleted',0)->where('debit_note_status','completed')->first();
            
            if(isset($data['action']) && $data['action'] == 'add_debit_note'){
                
                $debit_note_items = array();
                
                $po_items = \DB::table('purchase_order_items as poi')
                ->Join('pos_product_master as ppm','ppm.product_sku','=','poi.product_sku')
                ->where('poi.order_id',$data['po_id'])
                ->where('ppm.is_deleted',0)
                ->select('ppm.*')->get()->toArray(); 
                
                for($i=0;$i<count($po_items);$i++){
                    $name = str_replace(' ','_',$po_items[$i]->product_sku).'__'.$po_items[$i]->size_id;
                    if(isset($data[$name]) && !empty($data[$name])){
                        $debit_note_items[$po_items[$i]->id] = trim($data[$name]);
                    }
                }
                
                if(empty($debit_note_items)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Please add debit note items', 'errors' =>'Please add debit note items' ));
                }
                
                \DB::beginTransaction();
                
                // create debit note 
                if(empty($debit_note)){
                    $debit_note_no = CommonHelper::getPOInvoiceDebitNoteNo();
                    $credit_note_no = CommonHelper::getPOInvoiceCreditNoteNo();
                    
                    $invoice_no_exists = Debit_notes::where('debit_note_no',$debit_note_no)->where('invoice_series_type',2)->count();
                    if($invoice_no_exists > 0){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Debit Note No', 'errors' =>'Error in creating Debit Note No' ));
                    }

                    $invoice_no_exists = Purchase_order_grn_qc::where('grn_no',$debit_note_no)->where('invoice_series_type',2)->count();
                    if($invoice_no_exists > 0){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Debit Note No', 'errors' =>'Error in creating Debit Note No' ));
                    }

                    $invoice_no_exists = Debit_notes::where('credit_note_no',$credit_note_no)->where('invoice_series_type',2)->count();
                    if($invoice_no_exists > 0){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Credit Note No', 'errors' =>'Error in creating Credit Note No' ));
                    }

                    $invoice_no_exists = Purchase_order_grn_qc::where('credit_note_no',$credit_note_no)->where('invoice_series_type',2)->count();
                    if($invoice_no_exists > 0){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Credit Note No', 'errors' =>'Error in creating Credit Note No' ));
                    }
                    
                    $insertArray = array('po_id'=>$po_data->id,'invoice_id'=>$po_data->po_detail_id,'debit_note_type'=>'less_inventory_from_vendor_to_warehouse_in_po_invoice','debit_note_no'=>$debit_note_no,'credit_note_no'=>$credit_note_no,'items_count'=>count($debit_note_items),'comments'=>trim($data['comments']));
                    $debit_note = Debit_notes::create($insertArray);
                }else{
                    // update debit note
                    $updateArray = array('items_count'=>count($debit_note_items),'comments'=>trim($data['comments']));
                    Debit_notes::where('id',$debit_note->id)->update($updateArray);
                }
                
                // update debit note items
                $updateArray = array('is_deleted'=>1);
                Debit_note_items::where('debit_note_id',$debit_note->id)->update($updateArray);
                
                foreach($debit_note_items as $product_id=>$qty){
                    $inv_data = Pos_product_master_inventory::where('product_master_id',$product_id)
                    ->where('po_id',$po_data->id)
                    //->where('po_detail_id',$po_data->po_detail_id)
                    ->first();
                    
                    $insertArray = array('debit_note_id'=>$debit_note->id,'item_id'=>$product_id,'item_qty'=>$qty,'base_rate'=>$inv_data->vendor_base_price,
                    'gst_percent'=>$inv_data->vendor_gst_percent,'gst_amount'=>$inv_data->vendor_gst_amount,'base_price'=>$inv_data->base_price);
                    
                    Debit_note_items::create($insertArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('PO Invoice Debit Note Added. Debit Note ID: '.$debit_note->id,'PO_INVOICE_DEBIT_NOTE_ADDED','PO_INVOICE_DEBIT_NOTE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Debit Note created successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'cancel_debit_note'){
                $validateionRules = array('comments'=>'required');
                $attributes = array('comments'=>'Comments');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $debit_note_id = trim($data['debit_note_id']);
                
                \DB::beginTransaction();
                
                $updateArray = array('debit_note_status'=>'cancelled','cancel_user_id'=>$user->id,'cancel_date'=>date('Y/m/d H:i:s'),'cancel_comments'=>trim($data['comments']));
                Debit_notes::where('id',$debit_note_id)->update($updateArray);
                
                $updateArray = array('debit_note_status'=>3);
                Debit_note_items::where('debit_note_id',$debit_note_id)->update($updateArray);
                
                \DB::commit();
                
                CommonHelper::createLog('PO Invoice Pending Inventory Debit Note Cancelled. ID: '.$debit_note_id,'PO_INVOICE_DEBIT_NOTE_CANCELLED','PO_INVOICE_DEBIT_NOTE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Debit Note cancelled successfully'),200);
            }
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($po_data->gst_type);
            
            
            $invoice_items = \DB::table('purchase_order_grn_qc_items as po_grn_items')
            ->Join('pos_product_master_inventory as ppmi','ppmi.id','=','po_grn_items.inventory_id')        
            ->Join('pos_product_master as ppm','ppm.id','=','ppmi.product_master_id')
            ->Join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_grn_items.grn_qc_id')        
            ->where('po_grn_qc.po_detail_id',$invoice_id)                
            ->where('po_grn_qc.is_deleted',0)        
            ->where('po_grn_items.is_deleted',0)                         
            ->where('ppmi.is_deleted',0)                                 
            ->selectRaw('DISTINCT ppm.product_sku')        
            ->get()->toArray();        

            for($i=0;$i<count($invoice_items);$i++){
                $sku_list[] = $invoice_items[$i]->product_sku;
            }
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('pos_product_master as ppm','ppm.id','=','poi.item_master_id')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.quotation_detail_id')        
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.category_id', '=', 'dlim_2.id')         
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.subcategory_id', '=', 'dlim_3.id')        
            ->where('poi.order_id',$po_data->id)     
            ->wherein('poi.product_sku',$sku_list)             
            ->select('poi.*','ppm.product_sku','ppm.hsn_code','ppm.sale_price','dlim_1.name as color_name','dlim_2.name as category_name','dlim_3.name as subcategory_name')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            /*  Code to update size list start   */
            for($i=0;$i<count($po_items);$i++){
                $size_data = (!empty($po_items[$i]->size_data))?json_decode($po_items[$i]->size_data,true):array();
                foreach($size_data as $size_id=>$qty){
                    if($qty > 0) $size_arr[] = $size_id;
                }
            }
            
            $size_arr = array_unique($size_arr);
            
            for($i=0;$i<count($size_list);$i++){
                if(in_array($size_list[$i]['id'],$size_arr)){
                    $size_list_updated[] = $size_list[$i];
                }
            }
            
            $size_list = $size_list_updated;
            /*  Code to update size list end   */
            
            return view('warehouse/sor_invoice_debit_note_add',array('purchase_orders_items'=>$purchase_orders_items,'size_list'=>$size_list,'purchase_order_data'=>$po_data,'user'=>$user,'error_message'=>'','gst_type_percent'=>$gst_type_percent));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SOR_INVOICE_DEBIT_NOTE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('warehouse/sor_invoice_debit_note_add',array('error_message'=>$e->getMessage().', '.$e->getLine()));
            }
        }
    }
    
    function sorInventoryPendingInvoice(Request $request,$id,$invoice_type_id = 1){
        try{
            $data = $request->all();
            $debit_note_id = $id;
            //$po_detail_id = $id;
            $user = Auth::user();
            $products_list = $po_data = $company_data = $products_sku = $product_ids = $sku_list = array();
            
            $invoice_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            
            $debit_note_data = Debit_notes::where('id',$debit_note_id)->first();
            $po_detail_id = $debit_note_data['invoice_id'];
            
            $po_data = \DB::table('purchase_order_details as pod')
            ->join('purchase_order as po','po.id', '=', 'pod.po_id')        
            ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.po_detail_id', '=', 'pod.id')         
            ->where('pod.id',$po_detail_id)
            ->where('po_grn_qc.type','grn')        
            ->select('vd.*','pod.invoice_no','pod.invoice_date','po.id as po_id','pod.debit_note_data','pod.debit_note_no','po_grn_qc.grn_no','pod.credit_note_no','po.company_data')->first();
            
            //$company_data = CommonHelper::getCompanyData();
            $company_data = json_decode($po_data->company_data,true);
            
            if($po_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($po_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            }else{
                $gst_name = '';
            }
            
            /*$debit_note_data = json_decode($po_data->debit_note_data,true);
            
            foreach($debit_note_data as $id=>$qty){
                $product_ids[] = $id;
            }*/
            
            $inv_list = \DB::table('pos_product_master as ppm')
            //->join('pos_product_master_inventory as ppmi','ppmi.product_master_id', '=', 'ppm.id')   
            ->join('debit_note_items as dni','dni.item_id', '=', 'ppm.id')        
            ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')        
            ->where('dni.debit_note_id',$debit_note_id)        
            //->wherein('ppm.id',$product_ids)
            ->where('poi.order_id',$po_data->po_id)        
            //->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)
            ->where('dni.is_deleted',0)        
            ->select('ppm.id','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','dni.item_qty','dni.base_rate','dni.gst_percent','dni.gst_amount','dni.base_price')        
            ->distinct()->get()->toArray();
            
            for($i=0;$i<count($inv_list);$i++){
                /*$inv = Pos_product_master_inventory::where('product_master_id',$inv_list[$i]->id)->first();
                $inv_list[$i]->vendor_base_price = $inv->vendor_base_price;
                $inv_list[$i]->vendor_gst_percent = $inv->vendor_gst_percent;
                $inv_list[$i]->vendor_gst_amount = $inv->vendor_gst_amount;
                $inv_list[$i]->base_price = $inv->base_price;*/
                
                $inv_list[$i]->vendor_base_price = $inv_list[$i]->base_rate;
                $inv_list[$i]->vendor_gst_percent = $inv_list[$i]->gst_percent;
                $inv_list[$i]->vendor_gst_amount = $inv_list[$i]->gst_amount;
                $inv_list[$i]->base_price = $inv_list[$i]->base_price;
                
                $sku = $inv_list[$i]->product_sku;
                if(!isset($sku_list[$sku])){
                    $sku_list[$sku] = array('prod'=>$inv_list[$i],'qty'=>$inv_list[$i]->item_qty);
                }else{
                    $sku_list[$sku]['qty']+=$inv_list[$i]->item_qty;
                }
            }
            //print_r($sku_list);exit;
            $data = array('message' => 'products list','sku_list' => $sku_list,'po_data' => $po_data,'company_data'=>$company_data,'gst_name'=>$gst_name,'invoice_type'=>$invoice_type,'debit_note_data'=>$debit_note_data);
            
            //return view('warehouse/sor_inventory_pending_invoice',$data);
            
            $pdf = PDF::loadView('warehouse/sor_inventory_pending_invoice', $data);

            return $pdf->download('inventory_pending_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function addSorInvoiceExcessAmountDebitNote(Request $request,$id){
        try{
            exit;
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $invoice_id = $id;
            $debit_note_sku_list = array();
            
            $po_data = \DB::table('purchase_order as po')
            ->Join('purchase_order_details as pod','po.id','=','pod.po_id')
            ->where('pod.id',$invoice_id)
            ->select('po.*','pod.id as po_detail_id','pod.invoice_no')->first();  
            
            $debit_note = Debit_notes::where('po_id',$po_data->id)->where('invoice_id',$po_data->po_detail_id)->where('debit_note_type','excess_amount')->where('debit_note_status','completed')->where('is_deleted',0)->first();
            
            if(isset($data['action']) && $data['action'] == 'get_item_data'){
                $id = trim($data['id']);
                
                $item_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')     
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                  
                ->where('poi.order_id',$po_data->id)        
                ->where('poi.id',$id)               
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->select('poi.*','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','dlim_1.name as color_name')
                ->first();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Item data','item_data' => $item_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'add_debit_note'){
                \DB::beginTransaction();
                $debit_note_items = $data['items'];
                
                //print_r($data);exit;
                /*$debit_note_items = array();
                $ids = explode(',',$data['ids']);
                
                $invoice_items = \DB::table('purchase_order_items as poi')
                ->wherein('poi.id',$ids)
                ->select('poi.*')->get()->toArray(); 
                
                for($i=0;$i<count($invoice_items);$i++){
                    $name = 'invoice_cost_'.$invoice_items[$i]->id;
                    if(isset($data[$name]) && !empty($data[$name])){
                        $invoice_items[$i]->item_invoice_cost = trim($data[$name]);
                        $debit_note_items[$invoice_items[$i]->id] = $invoice_items[$i];
                    }
                }
                
                if(empty($debit_note_items)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Please add debit note items', 'errors' =>'Please add debit note items' ));
                }
                
                foreach($debit_note_items as $item=>$item_data){
                    $cost = round($item_data->rate+($item_data->rate*($item_data->gst_percent/100)),2);
                    if($cost > $item_data->item_invoice_cost){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$item_data->vendor_sku.': Item Invoice cost should be more than Item PO cost', 'errors' =>$item_data->vendor_sku.': Item Invoice cost should be more than Item PO cost' ));
                    }
                }*/
                
                if(empty($debit_note_items)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Please add debit note items', 'errors' =>'Please add debit note items' ));
                }
                
                for($i=0;$i<count($debit_note_items);$i++){
                    if($debit_note_items[$i]['item_cost'] >= $debit_note_items[$i]['item_invoice_cost']){
                        $error_msg = $debit_note_items[$i]['vendor_sku'].': Item invoice cost should be greater than item cost';
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg ));
                    }
                }
                
                $debit_note_no = CommonHelper::getPOInvoiceDebitNoteNo();
                $credit_note_no = CommonHelper::getPOInvoiceCreditNoteNo();
                $insertArray = array('po_id'=>$po_data->id,'invoice_id'=>$po_data->po_detail_id,'debit_note_no'=>$debit_note_no,'credit_note_no'=>$credit_note_no,'debit_note_type'=>'excess_amount');

                $debit_note = Debit_notes::create($insertArray);
                
                $updateArray = array('is_deleted'=>1);
                Debit_note_items::where('debit_note_id',$debit_note->id)->update($updateArray);
                
                for($i=0;$i<count($debit_note_items);$i++){
                    $item = $debit_note_items[$i];
                    $invoice_gst_percent = round(($item['item_invoice_gst']/$item['item_invoice_rate'])*100,2);
                    
                    $insertArray = array('debit_note_id'=>$debit_note->id,'item_id'=>$item['id'],'item_qty'=>$item['qty'],
                    'base_rate'=>$item['rate'],'gst_percent'=>$item['gst_percent'],'gst_amount'=>$item['item_gst_amount'],'base_price'=>$item['item_cost'],
                    'invoice_base_rate'=>$item['item_invoice_rate'],'invoice_gst_percent'=>$invoice_gst_percent,'invoice_gst_amount'=>$item['item_invoice_gst'],'invoice_base_price'=>$item['item_invoice_cost']);
                    
                    Debit_note_items::create($insertArray);
                }
                
                /*foreach($debit_note_items as $item=>$item_data){
                    $qty = trim($data['item_qty_'.$item_data->id]);
                    $cost = round($item_data->rate+($item_data->rate*($item_data->gst_percent/100)),2);
                    $insertArray = array('debit_note_id'=>$debit_note->id,'item_id'=>$item_data->id,'item_cost'=>$cost,'item_qty'=>$qty,'item_invoice_cost'=>$item_data->item_invoice_cost);
                    Debit_note_items::create($insertArray);
                }*/
                
                \DB::commit();
                
                CommonHelper::createLog('PO Invoice Excess Amount Debit Note Added. Debit Note ID: '.$debit_note->id,'PO_INVOICE_EXCESS_AMOUNT_DEBIT_NOTE_ADDED','PO_INVOICE_EXCESS_AMOUNT_DEBIT_NOTE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Debit Note created successfully','status' => 'success'),200);
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
                
                CommonHelper::createLog('PO Invoice Excess Amount Debit Note Cancelled. ID: '.$debit_note_id,'PO_INVOICE_EXCESS_AMOUNT_DEBIT_NOTE_CANCELLED','PO_INVOICE_EXCESS_AMOUNT_DEBIT_NOTE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Debit Note cancelled successfully'),200);
            }
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($po_data->gst_type);
            
            $grn_data = Purchase_order_grn_qc::where('po_id',$po_data->id)->where('po_detail_id',$po_data->po_detail_id)->where('type','grn')->where('is_deleted',0)->where('status',1)->first();

            $sku_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->where('po_qc_items.grn_qc_id',$grn_data->id)        
            ->where('poi.order_id',$po_data->id)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('po_qc_items.is_deleted',0) 
            ->where('ppm.is_deleted',0)
            ->groupByRaw('ppm.product_sku')        
            ->select('poi.*','ppm.product_name','ppm.product_sku','ppm.hsn_code','poi.vendor_sku','dlim_1.name as color_name',\DB::raw('count(ppmi.id) as inv_count'))
            ->get()->toArray();
            
            if(!empty($debit_note)){
                $debit_note_skus = Debit_note_items::where('debit_note_id',$debit_note->id)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($debit_note_skus);$i++){
                    $id = $debit_note_skus[$i]['item_id'];
                    $debit_note_sku_list[$id] = $debit_note_skus[$i];
                }
            }
            
            return view('warehouse/sor_invoice_excess_amount_debit_note_add_updated',array('sku_list'=>$sku_list,'po_data'=>$po_data,'user'=>$user,'error_message'=>'','gst_type_percent'=>$gst_type_percent,'grn_data'=>$grn_data,'debit_note'=>$debit_note,'debit_note_sku_list'=>$debit_note_sku_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SOR_INVOICE_EXCESS_AMOUNT_DEBIT_NOTE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                 \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('warehouse/sor_invoice_excess_amount_debit_note_add',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function downloadSorInvoiceExcessAmountDebitNote(Request $request,$id,$invoice_type_id = 1){
        try{
            $data = $request->all();
            $debit_note_id = $id;
            $user = Auth::user();
            $products_list = $po_data = $company_data = $products_sku = $product_ids = $sku_list = array();
            
            $invoice_type = ($invoice_type_id == 1)?'debit_note':'credit_note';
            
            $debit_note_data = Debit_notes::where('id',$debit_note_id)->first();
            
            $po_data = \DB::table('purchase_order_details as pod')
            ->join('purchase_order as po','po.id', '=', 'pod.po_id')        
            ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.po_detail_id', '=', 'pod.id')         
            ->where('pod.id',$debit_note_data->invoice_id)
            ->where('po_grn_qc.type','grn')        
            ->select('vd.*','pod.invoice_no','pod.invoice_date','po.id as po_id','pod.debit_note_data','pod.debit_note_no','po_grn_qc.grn_no','pod.credit_note_no','po.company_data')->first();
            
            //$company_data = CommonHelper::getCompanyData();
            $company_data = json_decode($po_data->company_data,true);
            
            if($po_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($po_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            }else{
                $gst_name = '';
            }
            
            $sku_list = \DB::table('debit_note_items as dni')
            ->join('purchase_order_items as poi','poi.id', '=', 'dni.item_id')      
            ->join('pos_product_master as ppm','ppm.product_sku', '=', 'poi.product_sku')        
            ->where('dni.debit_note_id',$debit_note_data->id)
            ->where('poi.order_id',$po_data->po_id)        
            ->where('dni.is_deleted',0)
            ->groupBy('poi.product_sku')        
            ->select('dni.*','poi.vendor_sku','ppm.product_name','ppm.hsn_code')        
            ->get()->toArray();
            
            $data = array('message' => 'products list','sku_list' => $sku_list,'po_data'=>$po_data,'company_data'=>$company_data,'gst_name'=>$gst_name,'invoice_type'=>$invoice_type,'debit_note_data'=>$debit_note_data);
            
            //return view('warehouse/sor_invoice_excess_amount_debit_note_download',$data);
            
            $pdf = PDF::loadView('warehouse/sor_invoice_excess_amount_debit_note_download', $data);

            return $pdf->download('sor_invoice_excess_amount_debit_note_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function addSorInvoiceLessAmountDebitNote(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $invoice_id = $id;
            $debit_note_sku_list = array();
            
            $po_data = \DB::table('purchase_order as po')
            ->Join('purchase_order_details as pod','po.id','=','pod.po_id')
            ->where('pod.id',$invoice_id)
            ->select('po.*','pod.id as po_detail_id','pod.invoice_no')->first();  
            
            $debit_note = Debit_notes::where('po_id',$po_data->id)->where('invoice_id',$po_data->po_detail_id)->where('debit_note_type','less_amount')->where('is_deleted',0)->first();
            
            if(isset($data['action']) && $data['action'] == 'add_debit_note'){
                \DB::beginTransaction();
                
                $debit_note_items = array();
                $ids = explode(',',$data['ids']);
                
                $invoice_items = \DB::table('purchase_order_items as poi')
                ->wherein('poi.id',$ids)
                ->select('poi.*')->get()->toArray(); 
                
                for($i=0;$i<count($invoice_items);$i++){
                    $name = 'invoice_cost_'.$invoice_items[$i]->id;
                    if(isset($data[$name]) && !empty($data[$name])){
                        $invoice_items[$i]->item_invoice_cost = trim($data[$name]);
                        $debit_note_items[$invoice_items[$i]->id] = $invoice_items[$i];
                    }
                }
                
                if(empty($debit_note_items)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Please add debit note items', 'errors' =>'Please add debit note items' ));
                }
                
                foreach($debit_note_items as $item=>$item_data){
                    $cost = round($item_data->rate+($item_data->rate*($item_data->gst_percent/100)),2);
                    if($cost < $item_data->item_invoice_cost){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$item_data->vendor_sku.': Item Invoice cost should be less than Item PO cost', 'errors' =>$item_data->vendor_sku.': Item Invoice cost should be less than Item PO cost' ));
                    }
                }
                
                if(empty($debit_note)){
                    $debit_note_no = CommonHelper::getPOInvoiceDebitNoteNo();
                    $credit_note_no = CommonHelper::getPOInvoiceCreditNoteNo();
                    $insertArray = array('po_id'=>$po_data->id,'invoice_id'=>$po_data->po_detail_id,'debit_note_no'=>$debit_note_no,'credit_note_no'=>$credit_note_no,'debit_note_type'=>'less_amount');

                    $debit_note = Debit_notes::create($insertArray);
                }
                
                $updateArray = array('is_deleted'=>1);
                Debit_note_items::where('debit_note_id',$debit_note->id)->update($updateArray);
                
                foreach($debit_note_items as $item=>$item_data){
                    $qty = trim($data['item_qty_'.$item_data->id]);
                    $cost = round($item_data->rate+($item_data->rate*($item_data->gst_percent/100)),2);
                    $insertArray = array('debit_note_id'=>$debit_note->id,'item_id'=>$item_data->id,'item_cost'=>$cost,'item_qty'=>$qty,'item_invoice_cost'=>$item_data->item_invoice_cost);
                    Debit_note_items::create($insertArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('PO Invoice Less Amount Debit Note Added. Debit Note ID: '.$debit_note->id,'PO_INVOICE_LESS_AMOUNT_DEBIT_NOTE_ADDED','PO_INVOICE_LESS_AMOUNT_DEBIT_NOTE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Debit Note created successfully','status' => 'success'),200);
            }
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($po_data->gst_type);
            
            $grn_data = Purchase_order_grn_qc::where('po_id',$po_data->id)->where('po_detail_id',$po_data->po_detail_id)->where('type','grn')->where('is_deleted',0)->where('status',1)->first();

            $sku_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->where('po_qc_items.grn_qc_id',$grn_data->id)        
            ->where('poi.order_id',$po_data->id)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('po_qc_items.is_deleted',0) 
            ->where('ppm.is_deleted',0)
            ->groupByRaw('ppm.product_sku')        
            ->select('poi.*','ppm.product_name','ppm.product_sku','poi.vendor_sku','dlim_1.name as color_name',\DB::raw('count(ppmi.id) as inv_count'))
            ->get()->toArray();
            
            if(!empty($debit_note)){
                $debit_note_skus = Debit_note_items::where('debit_note_id',$debit_note->id)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($debit_note_skus);$i++){
                    $id = $debit_note_skus[$i]['item_id'];
                    $debit_note_sku_list[$id] = $debit_note_skus[$i];
                }
            }
            
            return view('warehouse/sor_invoice_less_amount_debit_note_add',array('sku_list'=>$sku_list,'po_data'=>$po_data,'user'=>$user,'error_message'=>'','gst_type_percent'=>$gst_type_percent,'grn_data'=>$grn_data,'debit_note'=>$debit_note,'debit_note_sku_list'=>$debit_note_sku_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SOR_INVOICE_LESS_AMOUNT_DEBIT_NOTE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                 \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('warehouse/sor_invoice_less_amount_debit_note_add',array('error_message'=>$e->getMessage()));
            }
        }
    }

    public function demandData(Request $request,$demand_id){
       try{ 
            $data = $request->all();
            $error_msg = '';
            $demand_data = [];
            $user = Auth::user();
            
            if(!empty($demand_id)){
                $demand_data = Store_products_demand::where('id',$demand_id)->first();

                $inv_list = \DB::table('store_products_demand_inventory as spdi')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->leftJoin('store_products_demand as spd','spd.id', '=', 'ppmi.demand_id')                                 
                ->leftJoin('store as s','s.id', '=', 'ppmi.store_id')                                         
                ->where('spdi.demand_id',$demand_id)   
                ->where('spdi.is_deleted',0)                    
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','s.store_name','spd.invoice_no')
                ->orderBy('spdi.id','ASC')
                ->get()->toArray();//print_r($inv_list);
            }else{
                $error_msg = 'Demand ID is required Field';
            }
            
            return view('warehouse/demand_data',array('error_message'=>$error_msg,'inv_list'=>$inv_list,'demand_data'=>$demand_data));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE',__FUNCTION__,__FILE__);
            return view('warehouse/demand_data',array('error_message'=>$e->getMessage()));
        }
    }
    
}
