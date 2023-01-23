<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Design;
use App\Models\Design_sizes;
use App\Models\Store;
use App\Models\Design_item_master;
use App\Models\Design_items_instance;
use App\Models\Unit;
use App\Models\Category;
use App\Models\Product; 
use App\Models\Quotation; 
use App\Models\Quotation_details; 
use App\Models\Quotation_vendors; 
use App\Models\Design_lookup_items_master;
use App\Models\Vendor_detail;
use App\Models\Vendor_accessories;
use App\Models\Accessories;
use App\Models\Purchase_order;
use App\Models\Purchase_order_details;
use App\Models\Purchase_order_items;
use App\Models\Purchase_order_grn_qc;
use App\Models\Purchase_order_grn_qc_items;
use App\Models\Store_products_demand_courier;
use App\Models\Production_size_counts;
use App\Models\Pos_product_master;
use App\Models\Pos_product_master_inventory;
use App\Helpers\CommonHelper;
use Validator;
use PDF;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    
    public function __construct(){
    }
    
    function dashboard(Request $request){
        try{ 
            return view('purchaser/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/dashboard',array('error_message'=>$e->getMessage()));
        }
    }
    
    function designList(Request $request){
        try{ 
            $data = $request->all();
            $rec_per_page = 100;
            $whereArray = array('designer_submitted'=>1,'d.status'=>1,'d.is_deleted'=>0);
            
            $designs = \DB::table('designs as d')
            ->leftJoin('design_lookup_items_master as s','d.season_id','=','s.id')
            ->leftJoin('design_lookup_items_master as c','c.id','=','d.color_id')
            ->leftJoin('design_lookup_items_master as d1','d1.id','=','d.category_id')        
            ->leftJoin('design_lookup_items_master as d2','d2.id','=','d.sub_cat_id')                
            ->leftJoin('product_category_master as dt','d.design_type_id','=','dt.id')
            ->leftJoin('users as u1','u1.id','=','d.user_id')        
            ->leftJoin('story_master as sm','sm.id','=','d.story_id');
            
            $designs = $designs->where($whereArray);
            
            $designs = $designs->select('d.*','s.name as season_name','sm.name as story_name','dt.name as design_type_name','d1.name as category_name','d2.name as subcategory_name',
            'c.name as color_name','u1.name as designer_name','sm.name as story_name')
            ->orderBy('d.id','DESC')        
            ->paginate($rec_per_page);
                           
            return view('purchaser/design_list',array('approved_designs'=>$designs,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/design_list',array('error_message'=>$e->getMessage(),'approved_designs'=>array()));
        }
    }
    
    function designPOList(Request $request,$id){
        try{ 
            $data = $request->all();
            $design_id = $id;
            $rec_per_page = 100;
            $whereArray = array('poi.design_id'=>$design_id);
            
            $po_list = \DB::table('purchase_order as po')
            ->Join('purchase_order_items as poi','po.id','=','poi.order_id')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','po.category_id')
            ->Join('users as u','u.id','=','po.user_id')        
            ->where($whereArray)
            ->select('po.*','vd.name as vendor_name','dlim_1.name as category_name','u.name as user_name')
            ->groupBy('poi.order_id')        
            ->orderBy('po.id','DESC')        
            ->paginate($rec_per_page);
            
            $design_data = Design::where('id',$design_id)->first();    
                           
            return view('purchaser/design_po_list',array('po_list'=>$po_list,'error_message'=>'','design_data'=>$design_data));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/design_po_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function skuQuotation(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $design_ids = explode(',',$data['design_ids']);
            $design_items_fabric  = $design_items_accessories = $design_items_process = $design_items_packaging_sheet = $production_size_counts = $quotation_data_fabric =  array();
            $quotation_data_accessories = $quotation_data_process = array();
            
            /**   Fetch production size and their ratio start      **/
            $production_size_counts_list = \DB::table('production_size_counts')->where('status',1)->where('is_deleted',0)->get()->toArray();
            
            $production_size_count_sum = 0;
            for($i=0;$i<count($production_size_counts_list);$i++){
                $production_size_counts[$production_size_counts_list[$i]->size] = $production_size_counts_list[$i]->item_count;
                $production_size_count_sum+=$production_size_counts_list[$i]->item_count;
            }
            
            /**   Fetch production size and their ratio end      **/
            
            // Fetch size variations for instance ids.
            $sql = "Select id from design_items_instance where design_id IN(".$data['design_ids'].")";
            $size_variations_list = \DB::table('design_size_variations as dsv')->Join('production_size_counts as psc','dsv.size_id','=','psc.id')->whereRaw("dsv.design_items_instance_id IN($sql)")->select('dsv.*','psc.size as size_name')->get()->toArray();
            
            //print_r($size_variations_list);exit;
            
            // Fetch instance table fabric data for selected designs
            $design_items_fabric = \DB::table('design_items_instance as dii')->Join('design_item_master as dim','dii.design_item_id','=','dim.id')
            ->Join('designs as d','d.id','=','dii.design_id')        
            ->leftJoin('design_lookup_items_master as dlim_1',function($join){$join->on('dim.name_id','=','dlim_1.id')->where('dlim_1.status','=','1')->where('dlim_1.is_deleted','=','0');})        
            ->leftJoin('design_lookup_items_master as dlim_2',function($join){$join->on('dim.quality_id','=','dlim_2.id')->where('dlim_2.status','=','1')->where('dlim_2.is_deleted','=','0');})   
            ->leftJoin('design_lookup_items_master as dlim_3',function($join){$join->on('dim.color_id','=','dlim_3.id')->where('dlim_3.status','=','1')->where('dlim_3.is_deleted','=','0');})           
            ->leftJoin('design_lookup_items_master as dlim_4',function($join){$join->on('dim.width_id','=','dlim_4.id')->where('dlim_4.status','=','1')->where('dlim_4.is_deleted','=','0');}) 
            ->leftJoin('design_lookup_items_master as dlim_5',function($join){$join->on('dim.content_id','=','dlim_5.id')->where('dlim_5.status','=','1')->where('dlim_5.is_deleted','=','0');})
            ->leftJoin('units as u',function($join){$join->on('dim.unit_id','=','u.id')->where('u.status','=','1')->where('u.is_deleted','=','0');}) 
            ->whereIn('dii.design_id',$design_ids)->whereIn('dii.design_type_id',array(1,2,3))->where('dii.role_id',2)->where('dii.status',1)->where('dii.is_deleted',0)->where('dim.status',1)->where('dim.is_deleted',0)
            ->selectraw('d.production_count,dii.*,dlim_1.name as name_id_name,dlim_2.name as quality_id_name,dlim_3.name as color_id_name,dlim_4.name as width_id_name,dlim_5.name as size_id_name,u.code as unit_code')
            ->get()->toArray();
            
            for($i=0;$i<count($design_items_fabric);$i++){
                $production_size_counts_data =  $size_variations =  array();
                
                $instance_id = $design_items_fabric[$i]->id;
                $design_item_id =  $design_items_fabric[$i]->design_item_id;
                $average = $design_items_fabric[$i]->avg;
                $production_count = $design_items_fabric[$i]->production_count;
                
                if($production_count == 0){
                    echo 'Production count not defined. Design ID: '.$design_items_fabric[$i]->design_id;exit;
                }
               
                // $common_ratio is common multiple according to design production count
                $common_ratio = floor($production_count/$production_size_count_sum);
                $remainder = $production_count%$production_size_count_sum;
                
                // Calculate production count peices according to size ratio
                foreach($production_size_counts as $size=>$ratio){
                    $production_size_counts_data[$size] = $ratio*$common_ratio;
                }
                
                // Add remainder peices to medium size
                if($remainder > 0){
                    $production_size_counts_data['M'] = $production_size_counts_data['M']+$remainder;
                }
                
                // Filter size variations for this instance
                for($q=0;$q<count($size_variations_list);$q++){
                    if($size_variations_list[$q]->design_items_instance_id == $instance_id){
                        $size_variations[strtoupper($size_variations_list[$q]->size_name)] = $size_variations_list[$q]->variation_value;
                    }
                }
                
                // Calculate production peice data
                foreach($production_size_counts_data as $size=>$peice_count){
                    if(!isset($size_variations[$size]) || empty($size_variations[$size])){
                        $size_variations[$size] = 0;
                    }
                        
                    if(strtolower($size) == 'm'){ 
                        $production_data['M'] = $average*$peice_count;
                    }elseif(strtolower($size) == 's'){
                        $production_data[$size] = ($average-($average*($size_variations[$size]/100)))*$peice_count;
                    }else{
                        $production_data[$size] = ($average+($average*($size_variations[$size]/100)))*$peice_count;
                    }
                }
                
                // Calculate sum of all sizes
                $data_sum = 0;
                foreach($production_data as $size=>$data){
                    $data_sum+=$data;
                }
                
                $design_items_fabric[$i]->peice_data = $data_sum;
                
                // Create unique array according to design_item_id
                if($design_items_fabric[$i]->design_type_id == 1){
                    if(isset($quotation_data_fabric[$design_item_id])){
                        $quotation_data_fabric[$design_item_id]['peice_data']+=$data_sum;
                    }else{
                        $quotation_data_fabric[$design_item_id]['peice_data'] = $data_sum;
                        $quotation_data_fabric[$design_item_id]['name'] = $design_items_fabric[$i]->name_id_name;
                        $quotation_data_fabric[$design_item_id]['width'] = $design_items_fabric[$i]->width_id_name;
                        $quotation_data_fabric[$design_item_id]['unit'] = $design_items_fabric[$i]->unit_code;
                        $quotation_data_fabric[$design_item_id]['color'] = $design_items_fabric[$i]->color_id_name;
                    }
                }elseif($design_items_fabric[$i]->design_type_id == 2){
                    if(isset($quotation_data_accessories[$design_item_id])){
                        $quotation_data_accessories[$design_item_id]['peice_data']+=$design_items_fabric[$i]->avg;
                    }else{
                        $quotation_data_accessories[$design_item_id]['peice_data'] = $design_items_fabric[$i]->avg;                        
                        $quotation_data_accessories[$design_item_id]['category'] = $design_items_fabric[$i]->name_id_name;
                        $quotation_data_accessories[$design_item_id]['subcategory'] = $design_items_fabric[$i]->quality_id_name;
                        $quotation_data_accessories[$design_item_id]['color'] = $design_items_fabric[$i]->color_id_name;
                        $quotation_data_accessories[$design_item_id]['size'] = $design_items_fabric[$i]->size_id_name;
                    }
                }elseif($design_items_fabric[$i]->design_type_id == 3){
                    if(isset($quotation_data_process[$design_item_id])){
                        $quotation_data_process[$design_item_id]['peice_data']+=$design_items_fabric[$i]->avg;
                    }else{
                        $quotation_data_process[$design_item_id]['peice_data'] = $design_items_fabric[$i]->avg;                        
                        $quotation_data_process[$design_item_id]['category'] = $design_items_fabric[$i]->name_id_name;
                        $quotation_data_process[$design_item_id]['type'] = $design_items_fabric[$i]->quality_id_name;
                    }
                }
                
            }
            
            //print_r($design_items_fabric);
            
            $vendors_list = Vendor_detail::where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return view('purchaser/sku_quotation',array('quotation_fabric'=>$quotation_data_fabric,'quotation_accessories'=>$quotation_data_accessories,'quotation_process'=>$quotation_data_process,'vendors_list'=>$vendors_list,'error_message'=>''));
        }catch (Exception $e) {
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/sku_quotation',array('error_message'=>$e->getMessage(),'quotation_data_fabric'=>array(),'quotation_data_accessories'=>array(),'process_items_process'=>array(),'vendors_list'=>array()));
        }
    }
    
    /*function addQuotation(Request $request){
        try{
            $data = $request->all();
            $user_id = Auth::id(); 
            $master_ids = explode(',',$data['master_ids']);
            $qty_list = explode(',',$data['qty_list']);
            $vendor_ids = explode(',',$data['vendor_ids']);
            
            \DB::beginTransaction();
            
            $insertArray = array('mail_body'=>null,'created_by'=>$user_id,'type_id'=>1);
            $quotation = Quotation::create($insertArray);
           
            $vendors_list = Vendor_detail::whereIn('id',$vendor_ids)->get()->toArray();
            $company_data = CommonHelper::getCompanyData();
            $email_data = CommonHelper::getQuotationEmailData($quotation);
            $email_subject = $email_data['email_subject']; 
            $email_body = $email_data['email_body'];
            
            for($i=0;$i<count($vendor_ids);$i++){
                $vendor_id = $vendor_ids[$i];
                $insertArray = array('quotation_id'=>$quotation->id,'vendor_id'=>$vendor_id);
                Quotation_vendors::create($insertArray);
                
                for($q=0;$q<count($master_ids);$q++){
                    $master_id = $master_ids[$q];
                    $qty = $qty_list[$q];
                    $insertArray = array('quotation_id'=>$quotation->id,'item_master_id'=>$master_id,'quantity'=>$qty,'vendor_id'=>$vendor_id);
                    Quotation_details::create($insertArray);
                }
                
                $vendor_data = CommonHelper::getArrayRecord($vendors_list,'id',$vendor_id);
                $quotation_url = url('quotation/submit/'.$quotation->id.'/'.$vendor_id);
                $search_data = array('{{VENDOR_ID}}','{{VENDOR_NAME}}','{{QUOTATION_URL}}','{{COMPANY_TITLE}}','{{COMPANY_NAME}}','{{COMPANY_ADDRESS}}','{{WEBSITE_URL}}');
                $replace_data = array($vendor_data['id'],$vendor_data['name'],$quotation_url,$company_data['company_title'],$company_data['company_name'],$company_data['company_address'],url('/'));
                //CommonHelper::sendEmail($email_subject,$email_body,$search_data,$replace_data,$vendor_data['email'],$vendor_data['name']);
            }
            
            \DB::commit();
            
            CommonHelper::createLog('Quotation added','QUOTATION_ADDED','PURCHASE_ORDER');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Quotation added successfully','status' => 'success'),200);
            
        }catch (Exception $e) {
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function createPurchaseOrder(Request $request,$id){
        try{
            $data = $request->all();
            $quotation_id = $id;
            $user_id = Auth::id(); 
            $vendor_quote_data = $quotation_items_vendors = array();  
            $quotation_data = Quotation::where('id',$quotation_id)->first();
            
            \DB::beginTransaction();
            
            if($quotation_data->type_id == 1){
            
                $fabric_ids = explode(',',$data['fabric_ids']);
                $acc_ids = explode(',',$data['acc_ids']);
                $process_ids = explode(',',$data['process_ids']);
                

                for($i=0;$i<count($fabric_ids);$i++){
                    $id = $fabric_ids[$i];
                    $validateionRules['vendor_'.$id] = 'required|numeric';
                    $attributes['vendor_'.$id] = 'Fabric '.($i+1).' Quote Price';
                }

                for($i=0;$i<count($acc_ids);$i++){
                    $id = $acc_ids[$i];
                    $validateionRules['vendor_'.$id] = 'required|numeric';
                    $attributes['vendor_'.$id] = 'Accessories '.($i+1).' Quote Price';
                }

                for($i=0;$i<count($process_ids);$i++){
                    $id = $process_ids[$i];
                    $validateionRules['vendor_'.$id] = 'required|numeric';
                    $attributes['vendor_'.$id] = 'Process '.($i+1).' Quote Price';
                }

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                $item_master_ids = array_merge($fabric_ids,$acc_ids,$process_ids);

                $insertArray = array('quotation_id'=>$quotation_id,'user_id'=>$user_id,'type_id'=>1);
                $purchase_order = Purchase_order::create($insertArray);
                $purchase_order_id = $purchase_order->id;

                $quotation_items = Quotation_details::where('quotation_id',$quotation_id)->where('status',1)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($quotation_items);$i++){
                    $id = $quotation_items[$i]['item_master_id'].'_'.$quotation_items[$i]['vendor_id'];
                    $quotation_items_vendors[$id] = $quotation_items[$i];
                }

                for($i=0;$i<count($item_master_ids);$i++){
                    $id = $item_master_ids[$i];
                    $vendor_id = $data['vendor_'.$id];
                    $quantity = $quotation_items_vendors[$id.'_'.$vendor_id]['quantity'];
                    $cost = $quotation_items_vendors[$id.'_'.$vendor_id]['price'];
                    $quotation_detail_id = $quotation_items_vendors[$id.'_'.$vendor_id]['id'];
                    $insertArray = array('order_id'=>$purchase_order_id,'item_master_id'=>$id,'vendor_id'=>$vendor_id,'qty_ordered'=>$quantity,'cost'=>$cost,'quotation_detail_id'=>$quotation_detail_id);
                    Purchase_order_items::create($insertArray);
                }
                
                $updateArray = array('po_id'=>$purchase_order_id);
                Quotation::where('id',$quotation_id)->update($updateArray);
            }else{
                $item_ids = explode(',',$data['item_ids']);
                
                for($i=0;$i<count($item_ids);$i++){
                    $id = $item_ids[$i];
                    $validateionRules['vendor_'.$id] = 'required|numeric';
                    $attributes['vendor_'.$id] = 'Item '.($i+1).' Quote Price';
                }

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $insertArray = array('quotation_id'=>$quotation_id,'user_id'=>$user_id,'type_id'=>2);
                $purchase_order = Purchase_order::create($insertArray);
                $purchase_order_id = $purchase_order->id;

                $quotation_items = Quotation_details::where('quotation_id',$quotation_id)->where('status',1)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($quotation_items);$i++){
                    $id = $quotation_items[$i]['item_master_id'].'_'.$quotation_items[$i]['design_id'].'_'.$quotation_items[$i]['vendor_id'];
                    $quotation_items_vendors[$id] = $quotation_items[$i];
                }
                
                for($i=0;$i<count($item_ids);$i++){
                    $id = $item_ids[$i];
                    $vendor_id = $data['vendor_'.$id];
                    $quantity = $quotation_items_vendors[$id.'_'.$vendor_id]['quantity'];
                    $cost = $quotation_items_vendors[$id.'_'.$vendor_id]['price'];
                    $quotation_detail_id = $quotation_items_vendors[$id.'_'.$vendor_id]['id'];
                    $item_master_id_arr = explode('_',$id);
                    $item_master_id = $item_master_id_arr[0];
                    $design_id = $item_master_id_arr[1];
                    $insertArray = array('order_id'=>$purchase_order_id,'item_master_id'=>$item_master_id,'vendor_id'=>$vendor_id,'qty_ordered'=>$quantity,'cost'=>$cost,
                    'quotation_detail_id'=>$quotation_detail_id,'design_id'=>$design_id);
                    Purchase_order_items::create($insertArray);
                }
                
                $updateArray = array('po_id'=>$purchase_order_id);
                Quotation::where('id',$quotation_id)->update($updateArray);
            }
            
            \DB::commit();
            
            CommonHelper::createLog('Purchase Order Created. Order ID: '.$purchase_order_id,'PURCHASE_ORDER_CREATED','PURCHASE_ORDER');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Purchase order placed successfully','status' => 'success'),200);
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function purchaseOrdersList(Request $request){
        try{
            $data = $request->all();
            $user_id = Auth::id(); 
            
            $purchase_orders = \DB::table('purchase_order as po')
            ->Join('quotation as q','q.id','=','po.quotation_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->Join('purchase_order_items as poi','po.id','=','poi.order_id')        
            ->Join('design_item_master as dim','poi.item_master_id','=','dim.id')  
            ->Join('vendor_detail as vd','vd.id','=','poi.vendor_id')
            ->leftJoin('designs as d','d.id','=','poi.design_id')
            ->leftJoin('design_lookup_items_master as dlim_1',function($join){$join->on('dim.name_id','=','dlim_1.id')->where('dlim_1.status','=','1')->where('dlim_1.is_deleted','=','0');});        
            
            if(isset($data['po_id']) && !empty($data['po_id'])){
                $purchase_orders =  $purchase_orders->where('po.id',trim($data['po_id']));
            }
            
            $purchase_orders =  $purchase_orders->select('poi.*','u.name as user_name','q.type_id as quotation_type_id','dim.type_id as item_type_id','dlim_1.name as name_id_name','vd.name as vendor_name','d.sku')
            ->orderBy('po.id','DESC')->paginate(50);
            
            $data_array['purchase_orders'] = $purchase_orders;
            $data_array['error_message'] = '';
            return view('purchaser/purchase_orders_list',$data_array);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/purchase_orders_list',array('error_message'=>$e->getMessage(),'purchase_orders'=>array()));
        }
    }
    
    function purchaseOrderDetail(Request $request,$id){
        try{
            $data = $request->all();
            $order_id = $id;
            $user_id = Auth::id(); 
            
            $purchase_order_detail = \DB::table('purchase_order as po')
            ->Join('quotation as q','q.id','=','po.quotation_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->Join('purchase_order_items as poi','po.id','=','poi.order_id')        
            ->select('poi.*','u.name as user_name','q.type_id')
            ->where('poi.id',$order_id)        
            ->first();
            
            $purchase_order_items = \DB::table('purchase_order_items as poi')
            ->leftJoin('designs as d','d.id','=','poi.design_id')
            ->Join('design_item_master as dim','poi.item_master_id','=','dim.id')
            ->Join('vendor_detail as vd','vd.id','=','poi.vendor_id')
            ->leftJoin('design_lookup_items_master as dlim_1',function($join){$join->on('dim.name_id','=','dlim_1.id')->where('dlim_1.status','=','1')->where('dlim_1.is_deleted','=','0');})        
            ->leftJoin('design_lookup_items_master as dlim_2',function($join){$join->on('dim.quality_id','=','dlim_2.id')->where('dlim_2.status','=','1')->where('dlim_2.is_deleted','=','0');})   
            ->leftJoin('design_lookup_items_master as dlim_3',function($join){$join->on('dim.color_id','=','dlim_3.id')->where('dlim_3.status','=','1')->where('dlim_3.is_deleted','=','0');})         
            ->where('poi.id',$order_id)->where('dim.status',1)->where('dim.is_deleted',0)
            ->select('poi.*','dim.type_id as item_type_id','dlim_1.name as name_id_name','dlim_2.name as quality_id_name','dlim_3.name as color_id_name','vd.name as vendor_name','vd.email as vendor_email','d.sku','d.id as design_id')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            if($purchase_order_detail->type_id == 2){
                $purchase_order_data = array();
                for($i=0;$i<count($purchase_order_items);$i++){
                    $purchase_order_data[$purchase_order_items[$i]->design_id]['order_items'][] = $purchase_order_items[$i];
                    $purchase_order_data[$purchase_order_items[$i]->design_id]['design_data'] = array('sku'=>$purchase_order_items[$i]->sku);
                }
                
                $purchase_order_items = $purchase_order_data;//print_r($purchase_order_items);
            }
            
            $data_array['purchase_order_detail'] = $purchase_order_detail;
            $data_array['purchase_order_items'] = $purchase_order_items;
            $data_array['error_message'] = '';
            return view('purchaser/purchase_order_detail',$data_array);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/purchase_order_detail',array('error_message'=>$e->getMessage(),'purchase_order_detail'=>array(),'purchase_order_items'=>array()));
        }
    }*/
    
    function createProductPurchaseOrder(Request $request){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $vendor_list = CommonHelper::getVendorsList();
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return view('purchaser/product_purchase_order_create',array('size_list'=>$size_list,'color_list'=>$color_list,'vendor_list'=>$vendor_list,'po_category_list'=>$po_category_list,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'QUOTATION',__FUNCTION__,__FILE__);
            return view('purchaser/product_purchase_order_create',array('error_message'=>$e->getMessage()));
        }
    }
    
    function saveProductPurchaseOrder(Request $request){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $data = $request->all();
            $user_id = Auth::id(); 
            $error_msg = '';
            $size_data = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if(isset($data['style_data']) && $data['style_data'] == 1){
                $size_id_list = array();
                $error_msg = '';
                $style_list = \DB::table('pos_product_master as ppm')
                ->Join('design_lookup_items_master as dlim','dlim.id','=','ppm.color_id')        
                ->where('ppm.product_sku',$data['style'])->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)->where('ppm.status',1)       
                ->select('ppm.*','dlim.name as color_name')
                ->get()->toArray();
                
                if(empty($style_list)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Style does not exists', 'errors' => 'Style does not exists'));
                }
                
                for($i=0;$i<count($style_list);$i++){
                    $size_id_list[] = $style_list[$i]->size_id;
                }

                $style_data = $style_list[0];
                $style_data->size_id_list = $size_id_list;

                $req_fields = array('category_id','subcategory_id','base_price','sale_price','size_id','color_id','hsn_code');
                
                for($i=0;$i<count($req_fields);$i++){
                    if(empty($style_data->{$req_fields[$i]})){
                        $error_msg.='Invalid '.strtoupper(str_replace('_',' ',$req_fields[$i])).' of Product. <br/>';
                    }
                }

                if(!empty($error_msg)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }

                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Style Data','style_data' => $style_data),200);
            }
            
            if(isset($data['create_order']) && $data['create_order'] == 1){
                
                $validationRules = array('vendor_id'=>'Required','delivery_date'=>'Required','category_id'=>'Required');
                $attributes = array('vendor_id'=>'Vendor','category_id'=>'Category','delivery_date'=>'Delivery Date');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    $error_msg = 'Vendor is Required Field <br> Delivery Date is Required Field <br> Category is Required Field';
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' => $validator->errors()));
                } 
                
                \DB::beginTransaction();
                
                $vendor_data = Vendor_detail::where('id',$data['vendor_id'])->first();
                                
                if(!isset($vendor_data->gst_no) || empty($vendor_data->gst_no)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor GST numnber is invalid', 'errors' =>'Vendor GST numnber is invalid' ));
                }
                
                if(!isset($vendor_data->vendor_code) || empty($vendor_data->vendor_code)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor Code is invalid', 'errors' =>'Vendor Code is invalid' ));
                }
                
                $fake_inventory = ($is_fake_inventory_user == true)?1:0;
                $gst_type = CommonHelper::getGSTType($vendor_data->gst_no);
                $del_date = explode('/',$data['delivery_date']);
                $delivery_date = date('Y/m/d',strtotime($del_date[2].'/'.$del_date[1].'/'.$del_date[0]));    
                $today_latest_po = Purchase_order::whereRaw("DATE(created_at) = CURDATE()")->select('order_no')->orderBy('order_no','DESC')->first();
                $po_number = (!empty($today_latest_po) && strlen($today_latest_po->order_no) == 13)?substr($today_latest_po->order_no,10):0;
                $po_number = 'KSPO'.Date('ymd').str_pad($po_number+1,3,'0',STR_PAD_LEFT);
                
                $po_exists = Purchase_order::where('order_no',$po_number)->select('order_no')->first();
                if(!empty($po_exists)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Error in creating PO Number', 'errors' =>'Error in creating PO Number' ));
                }
                
                $company_data = CommonHelper::getCompanyData();
                
                $insertArray = array('quotation_id'=>null,'order_no'=>$po_number,'user_id'=>$user_id,'type_id'=>3,'vendor_id'=>$data['vendor_id'],'other_cost'=>$data['other_cost'],'other_comments'=>$data['other_comments'],'gst_type'=>$gst_type,'delivery_date'=>$delivery_date,'category_id'=>$data['category_id']);
                $insertArray['fake_inventory'] = $fake_inventory;
                $insertArray['company_data'] = json_encode($company_data);
                $purchase_order = Purchase_order::create($insertArray);
                
                $purchase_order_id = $purchase_order->id;
                
                $style_list = $data['rows'];
                
                for($i=0;$i<count($style_list);$i++){
                    if(! (isset($style_list[$i]['gst_percent']) && !empty($style_list[$i]['gst_percent']))){
                        $error_msg.='GST Percent not defined for '.$style_list[$i]['style'].' HSN Code <br>';
                    }
                }
                
                if(!empty($error_msg)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                for($i=0;$i<count($style_list);$i++){
                    
                    for($q=0;$q<count($size_list);$q++){
                        $size_id = $size_list[$q]['id'];//var_dump($style_list[$i]['size_'.$size_id]);exit;
                        if(isset($style_list[$i]['size_'.$size_id]) && !empty($style_list[$i]['size_'.$size_id])){
                            $size_data[$size_id] = $style_list[$i]['size_'.$size_id];
                        }else{
                            $size_data[$size_id] = 0;
                        }
                    }
                    //print_r($size_data);exit;
                    $style_data =  Pos_product_master::where('product_sku',$style_list[$i]['style'])->where('is_deleted',0)->where('status',1)->first();
                    $cost = round($style_list[$i]['style_rate']*$style_list[$i]['size_total'],2);
                    $gst_amount = round(($style_list[$i]['gst_percent']/100)*$cost,2);
                    $total_cost = $cost+$gst_amount;
                    
                    $insertArray = array('order_id'=>$purchase_order_id,'product_sku'=>$style_data->product_sku,'vendor_sku'=>$style_data->product_sku.'-'.$vendor_data->vendor_code,'item_master_id'=>$style_data->id,'quotation_detail_id'=>$style_list[$i]['color'],'vendor_id'=>$data['vendor_id'],
                    'qty_ordered'=>$style_list[$i]['size_total'],'rate'=>$style_list[$i]['style_rate'],'size_data'=>json_encode($size_data),'cost'=>$cost,'gst_percent'=>$style_list[$i]['gst_percent'],
                    'gst_amount'=>$gst_amount,'total_cost'=>$total_cost,'fake_inventory'=>$fake_inventory);
                    
                    $po_item = Purchase_order_items::create($insertArray);
                    
                    /*   Add inventory start   */
                    foreach($size_data as $size_id=>$size_count){
                        if($size_count == 0) continue;
                        
                        $product_data = Pos_product_master::where('product_sku',$style_data->product_sku)->where('size_id',$size_id)->where('is_deleted',0)->where('status',1)->first();
                        //echo $size_id;exit;
                        //$product_data = $style_data;
                        $vendor_rate = $product_data->base_price;
                        $vendor_gst_percent = $style_list[$i]['gst_percent'];
                        $vendor_gst_amount = round(($vendor_gst_percent/100)*$vendor_rate,2);
                        $base_price = $product_data->base_price+$vendor_gst_amount;
                        
                        $inventory_product = Pos_product_master_inventory::where('product_master_id',$product_data->id)->where('is_deleted',0)->orderBy('id','DESC')->first();
                        $quantity = $size_count;
                        $inventory_product_barcode = (!empty($inventory_product))?ltrim(str_replace($product_data->product_barcode,'',$inventory_product['peice_barcode']),'0'):0;

                        for($q=1;$q<=$quantity;$q++){
                            /*$barcode = $inventory_product_barcode+$q;
                            $barcode = str_pad($barcode,6,'0',STR_PAD_LEFT);
                            $barcode = $product_data->product_barcode.$barcode;*/
                            $barcode = null;
                            $insertArray = array('product_master_id'=>$product_data->id,'peice_barcode'=>$barcode,'po_item_id'=>$po_item->id,
                            'product_status'=>0,'base_price'=>$base_price,'sale_price'=>$product_data->sale_price,
                            'po_id'=>$purchase_order_id,'vendor_base_price'=>$vendor_rate,'vendor_gst_percent'=>$vendor_gst_percent,
                            'vendor_gst_amount'=>$vendor_gst_amount,'fake_inventory'=>$fake_inventory,'vendor_id'=>$data['vendor_id'],'product_sku_id'=>$product_data->product_sku_id);
                            Pos_product_master_inventory::create($insertArray);   
                        }
                    }
                    /*   Add inventory end  */
                }
                
                \DB::commit();
                
                CommonHelper::createLog('SOR Purchase Order Created. ID: '.$purchase_order_id,'SOR_PURCHASE_ORDER_CREATED','SOR_PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Order created successfully','status' => 'success'),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function createStaticProductPurchaseOrder(Request $request){
        try{
            $vendor_list = CommonHelper::getVendorsList();
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return view('purchaser/product_purchase_order_static_create',array('vendor_list'=>$vendor_list,'po_category_list'=>$po_category_list,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/product_purchase_order_static_create',array('error_message'=>$e->getMessage()));
        }
    }
    
    function saveStaticProductPurchaseOrder(Request $request){
        try{
            
            $data = $request->all();
            $user_id = Auth::id(); 
            $error_msg = '';
            
            if(isset($data['create_order']) && $data['create_order'] == 1){
                
                $validationRules = array('vendor_id'=>'Required','category_id'=>'Required','delivery_date'=>'Required');
                $attributes = array('vendor_id'=>'Vendor','category_id'=>'Category','delivery_date'=>'Delivery Date');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                } 
            
                \DB::beginTransaction();
                
                $vendor_data = Vendor_detail::where('id',$data['vendor_id'])->first();
                                
                if(!isset($vendor_data->gst_no) || empty($vendor_data->gst_no)){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor GST numnber is invalid', 'errors' =>'Vendor GST numnber is invalid' ));
                }
                
                if(!isset($vendor_data->vendor_code) || empty($vendor_data->vendor_code)){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor Code is invalid', 'errors' =>'Vendor Code is invalid' ));
                }
                
                $gst_type = CommonHelper::getGSTType($vendor_data->gst_no);
                $del_date = explode('/',$data['delivery_date']);
                $delivery_date = date('Y/m/d',strtotime($del_date[2].'/'.$del_date[1].'/'.$del_date[0]));    
                
                $today_latest_po = Purchase_order::whereRaw("DATE(created_at) = CURDATE()")->select('order_no')->orderBy('order_no','DESC')->first();
                $po_number = (!empty($today_latest_po) && strlen($today_latest_po->order_no) == 13)?substr($today_latest_po->order_no,10):0;
                $po_number = 'KSPO'.Date('ymd').str_pad($po_number+1,3,'0',STR_PAD_LEFT);
                
                $po_exists = Purchase_order::where('order_no',$po_number)->select('order_no')->first();
                if(!empty($po_exists)){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Error in creating PO Number', 'errors' =>'Error in creating PO Number' ));
                }
                
                $insertArray = array('quotation_id'=>null,'order_no'=>$po_number,'user_id'=>$user_id,'type_id'=>3,'vendor_id'=>$data['vendor_id'],'other_cost'=>$data['other_cost'],
                'other_comments'=>$data['other_comments'],'gst_type'=>$gst_type,'delivery_date'=>$delivery_date,'category_id'=>$data['category_id'],'static_po'=>1);
                
                $company_data = CommonHelper::getCompanyData();
                $insertArray['company_data'] = json_encode($company_data);
                
                $purchase_order = Purchase_order::create($insertArray);
                
                \DB::commit();
                
                CommonHelper::createLog('Static Purchase Order Created. ID: '.$purchase_order->id,'STATIC_PURCHASE_ORDER_CREATED','STATIC_PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Purchase Order created successfully','po_data'=>$purchase_order),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function editStaticProductPurchaseOrder(Request $request,$id){
        try{
            $data = $request->all();
            $user_id = Auth::id(); 
            $po_id = $id;
            $error_msg = '';
            $po_data = $sizes = $colors = $product_list = array();
            
            $po_data = Purchase_order::where('id',$po_id)->first();
            $vendor_data = Vendor_detail::where('id',$po_data->vendor_id)->first();
            
            if(isset($data['action']) && $data['action'] == 'upload_po_csv'){
                
                $validationRules = array('purchaseOrderCsvFile'=>'required|mimes:csv,txt|max:3072','po_id'=>'required');
                $attributes = array('purchaseOrderCsvFile'=>'CSV File','po_id'=>'PO');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                $po_id = $data['po_id'];

                $file = $request->file('purchaseOrderCsvFile');
                $file_name_text = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(),'.'));
                $file_ext = $file->getClientOriginalExtension();
                $dest_folder = 'documents/product_barcode_csv';

                for($i=0;$i<1000;$i++){
                    $file_name = ($i == 0)?$file_name_text.'.'.$file_ext:$file_name_text.'_'.$i.'.'.$file_ext;
                    if(!file_exists(public_path($dest_folder.'/'.$file_name))){
                        break;
                    }
                }

                if(!isset($file_name)){
                    $file_name = $file_name_text.'_'.rand(1000,100000).'.'.$file_ext;
                }

                $file->move(public_path($dest_folder), $file_name);
                
                $file = public_path($dest_folder.'/'.$file_name);
                
                $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
                for($i=0;$i<count($size_list);$i++){
                    $sizes[strtolower($size_list[$i]['size'])] = $size_list[$i]['id'];
                }
                
                $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->where('status',1)->get()->toArray();
                
                for($i=0;$i<count($color_list);$i++){
                    $color_name = strtolower($color_list[$i]['name']);
                    $colors[$color_name][] = $color_list[$i];
                }
                
                $fp = file($file, FILE_SKIP_EMPTY_LINES);
                if(count($fp) > 1000){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'CSV file can not have more than 1000 rows', 'errors' => 'CSV file can not have more than 1000 rows'));
                }
                
                if(($handle = fopen($file, "r")) !== FALSE) {
                    while (($csv_data = fgetcsv($handle, 10000, ",")) !== FALSE){
                        $sku = trim($csv_data[0]);
                        $color_name = trim(strtolower($csv_data[1]));
                        $size_name = trim(strtolower($csv_data[2]));
                        $quantity = trim($csv_data[3]);
                        
                        $product_data = null;
                        
                        if(!isset($sizes[$size_name]) || !isset($colors[$color_name])){
                            $error_msg.=$sku.' '.$size_name.' '.$color_name.': does not exists <br>';
                            continue;
                        }
                        
                        $size_id = $sizes[$size_name];
                                
                        $color_data = $colors[$color_name];
                        for($i=0;$i<count($color_data);$i++){
                            $color_id = $color_data[$i]['id'];
                            $product_data = Pos_product_master::where('product_sku',$sku)->where('size_id',$size_id)->where('color_id',$color_id)->where('is_deleted',0)->where('fake_inventory',0)->first();
                            if(!empty($product_data)){
                                break;
                            }
                        }
                        
                        if(empty($product_data)){
                            $error_msg.=$sku.' '.$size_name.' '.$color_name.': does not exists <br>';
                            continue;
                        }
                        
                        $product_data->quantity = $quantity;
                        
                        $product_list[] = $product_data;
                        
                    }
                }
                
                if(!empty($error_msg)){
                    return response(array('httpStatus'=>200,'dateTime'=>time(), 'status'=>'fail','message'=>$error_msg,'errors' =>$error_msg));
                }
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($product_list);$i++){
                   $product_data = $product_list[$i];
                   $quantity = $product_data->quantity;
                   
                   $size_data = [];
                   
                    /*for($q=0;$q<count($size_list);$q++){
                        $size_id = $size_list[$q]['id'];
                        $size_data[$size_id] = $quantity;
                    }*/
                    
                    $vendor_rate = $product_data->base_price;
                    $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$vendor_rate);
                    
                    if(!empty($gst_data)){
                        $vendor_gst_percent = $gst_data->rate_percent;
                    }else{
                        $vendor_gst_percent = ($vendor_rate >1000)?12:5;
                    }
                    
                    $po_item = Purchase_order_items::where('order_id',$po_id)->where('product_sku',$product_data->product_sku)->where('is_deleted',0)->where('fake_inventory',0)->first();
                    
                    if(empty($po_item)){
                        $cost = round($vendor_rate*$quantity,2);
                        $gst_amount = round(($vendor_gst_percent/100)*$cost,2);
                        $total_cost = $cost+$gst_amount;
                        $size_id = $product_data->size_id;
                        $size_data[$size_id] = $quantity;

                        $insertArray = array('order_id'=>$po_id,'product_sku'=>$product_data->product_sku,'vendor_sku'=>$product_data->product_sku.'-'.$vendor_data->vendor_code,
                        'item_master_id'=>$product_data->id,'quotation_detail_id'=>$product_data->color_id,'vendor_id'=>$po_data->vendor_id,
                        'qty_ordered'=>$quantity,'rate'=>$vendor_rate,'size_data'=>json_encode($size_data),'cost'=>$cost,'gst_percent'=>$vendor_gst_percent,
                        'gst_amount'=>$gst_amount,'total_cost'=>$total_cost);

                        $po_item = Purchase_order_items::create($insertArray);
                    }else{
                        $qty_ordered = $po_item->qty_ordered+$quantity;
                        $cost = round($po_item->rate*$qty_ordered,2);
                        $gst_amount = round(($vendor_gst_percent/100)*$cost,2);
                        $total_cost = $cost+$gst_amount;
                        $size_data = json_decode($po_item->size_data,true);
                        $size_id = $product_data->size_id;
                        
                        if(isset($size_data[$size_id])){
                            $size_data[$size_id]+=$quantity;
                        }else{
                            $size_data[$size_id] = $quantity;
                        }
                        
                        $updateArray = array('qty_ordered'=>$qty_ordered,'cost'=>$cost,'gst_amount'=>$gst_amount,'total_cost'=>$total_cost,'size_data'=>json_encode($size_data));
                        Purchase_order_items::where('id',$po_item->id)->update($updateArray);
                    }
                    
                    $vendor_rate = $product_data->base_price;
                    $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$vendor_rate);
                    
                    if(!empty($gst_data)){
                        $vendor_gst_percent = $gst_data->rate_percent;
                    }else{
                        $vendor_gst_percent = ($vendor_rate >1000)?12:5;
                    }
                    
                    $vendor_gst_amount = round(($vendor_gst_percent/100)*$vendor_rate,2);
                    $base_price = $product_data->base_price+$vendor_gst_amount;

                    $product_barcode = str_ireplace('u', '', $product_data->product_barcode);
                    $inventory_product = Pos_product_master_inventory::where('product_master_id',$product_data->id)->where('peice_barcode','>',0)->orderBy('id','DESC')->first();
                    $inventory_product_barcode = (!empty($inventory_product))?ltrim(str_replace($product_barcode,'',$inventory_product->peice_barcode),'0'):0;
                    
                    for($q=1;$q<=$quantity;$q++){
                        $barcode = $inventory_product_barcode+$q;
                        $barcode = str_pad($barcode,3,'0',STR_PAD_LEFT);
                        $barcode = $product_barcode.$barcode;
                        
                        $insertArray = array('product_master_id'=>$product_data->id,'peice_barcode'=>$barcode,'po_item_id'=>$po_item->id,
                        'product_status'=>0,'base_price'=>$base_price,'sale_price'=>$product_data->sale_price,'vendor_id'=>$po_data->vendor_id,
                        'po_id'=>$po_id,'vendor_base_price'=>$vendor_rate,'vendor_gst_percent'=>$vendor_gst_percent,'vendor_gst_amount'=>$vendor_gst_amount,
                        'product_sku_id'=>$product_data->product_sku_id);
                        
                        Pos_product_master_inventory::create($insertArray);   
                    }
                }
                
                fclose($handle);
                unlink($file);
                
                \DB::commit();
                
                CommonHelper::createLog('Static Purchase Order CSV Added. PO ID: '.$po_id,'STATIC_PURCHASE_ORDER_CSV_ADDED','STATIC_PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Purchase Order updated successfully'),200);
            }
            
            return view('purchaser/product_purchase_order_static_edit',array('po_data'=>$po_data,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().' '.$e->getLine()),500);
            }else{
                return view('purchaser/product_purchase_order_static_edit',array('error_message'=>$e->getMessage().' '.$e->getLine()));
            }
        }
    }
    
    function listProductPurchaseOrder(Request $request){
        try{
            $user = Auth::user();
            $data = $request->all();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            $purchase_orders = \DB::table('purchase_order as po')
            ->leftJoin('purchase_order_details as pod',function($join){$join->on('po.id','=','pod.po_id')->where('pod.is_deleted','=','0');})      
            ->Join('users as u','u.id','=','po.user_id')
            ->Join('vendor_detail as v','v.id','=','po.vendor_id')   
            ->join('design_lookup_items_master as dlim_po_cat','po.category_id', '=', 'dlim_po_cat.id')        
            ->where('po.is_deleted',0)        
            ->wherein('type_id',array(3,5));
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $purchase_orders = $purchase_orders->where('po.vendor_id',$data['v_id']);
            }
            
            if(isset($data['po_cat_id']) && !empty($data['po_cat_id'])){
                $purchase_orders = $purchase_orders->where('po.category_id',$data['po_cat_id']);
            }
            
            if(isset($data['po_no']) && !empty($data['po_no'])){
                $purchase_orders = $purchase_orders->where('po.order_no','LIKE','%'.$data['po_no'].'%');
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $purchase_orders = $purchase_orders->where('pod.invoice_no','LIKE','%'.$data['invoice_no'].'%');
            }
            
            if(isset($data['po_id']) && !empty($data['po_id'])){
                $purchase_orders = $purchase_orders->where('po.id',trim($data['po_id']));
            }
            
            if($is_fake_inventory_user){
                $purchase_orders = $purchase_orders->where('po.fake_inventory',1);
            }else{
                $purchase_orders = $purchase_orders->where('po.fake_inventory',0);
            }
                
            $purchase_orders = $purchase_orders->select('po.*','u.name as user_name','v.name as vendor_name','v.email as vendor_email','dlim_po_cat.name as po_category_name',\DB::raw('count(pod.id) as invoice_count'))
            ->groupBy('po.id');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $purchase_orders = $purchase_orders->offset($start)->limit($limit)->orderBy('po.id','ASC')->get()->toArray();
            }else{
                $purchase_orders = $purchase_orders->orderBy('po.id','DESC')->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=purchase_orders_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Order ID','Order No','Vendor','Category','Delivery Date','Invoice Count','Created By','Created On');

                $callback = function() use ($purchase_orders,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($purchase_orders);$i++){
                        $del_date = (!empty($purchase_orders[$i]->delivery_date))?date('d-M-Y',strtotime($purchase_orders[$i]->delivery_date)):'';
                        $array = array($purchase_orders[$i]->id,$purchase_orders[$i]->order_no,$purchase_orders[$i]->vendor_name,$purchase_orders[$i]->po_category_name,$del_date,$purchase_orders[$i]->invoice_count,$purchase_orders[$i]->user_name,date('d-m-Y',strtotime($purchase_orders[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where(array('status'=>1,'is_deleted'=>0))->orderBy('name')->get()->toArray();
            $vendors_list = Vendor_detail::where('is_deleted',0)->where('status',1)->orderBy('name')->get()->toArray();
            
            return view('purchaser/product_purchase_order_list',array('purchase_orders'=>$purchase_orders,'user'=>$user,'error_message'=>'','po_category_list'=>$po_category_list,'vendors_list'=>$vendors_list,'is_fake_inventory_user'=>$is_fake_inventory_user));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/product_purchase_order_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function productPurchaseOrderDetail(Request $request,$id){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->get()->toArray();
            $data = $request->all();
            $user_id = Auth::id(); 
            $user = Auth::user();
            $order_id = $id;
            $error_msg = '';$size_arr = $size_list_updated = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            $purchase_order_data = \DB::table('purchase_order as po')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->where('po.id',$order_id)
            ->select('po.*','vd.name as vendor_name','vd.email as vendor_email','u.name as user_name')->first();        
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($purchase_order_data->gst_type);
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('pos_product_master as ppm','ppm.id','=','poi.item_master_id')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.quotation_detail_id')        
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.category_id', '=', 'dlim_2.id')         
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.subcategory_id', '=', 'dlim_3.id')        
            ->where('poi.order_id',$order_id)       
            ->where('poi.is_deleted',0)        
            ->select('poi.*','ppm.product_sku','ppm.hsn_code','ppm.sale_price','dlim_1.name as color_name','dlim_2.name as category_name','dlim_3.name as subcategory_name')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            $purchase_order_inventory = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->where('ppmi.po_id',$order_id)         
            ->where('poi.order_id',$order_id)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('ppm.is_deleted',0)
            ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')->paginate(100);
            
            if(isset($data['action']) && $data['action'] == 'ean_csv'){
                $headers = array(
                    'Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=file_po_'.$purchase_order_data->id.'.csv',
                    'Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0'
                );

                $columns = array('Product Name','Product Description (Should be less than 40 characters)','Sub Category','HS Code','Date of Activation','MRP:Pan India','MRP Activation Date:Pan India');

                $callback = function() use ($po_items, $columns,$size_list){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    for($i=0;$i<count($po_items);$i++){
                        $size_data = json_decode($po_items[$i]->size_data,true);
                        foreach($size_data as $size_id=>$qty){
                            if(empty($qty)) continue;
                            $size_name_data = CommonHelper::getArrayRecord($size_list,'id',$size_id);
                            
                            $product_name = $po_items[$i]->subcategory_name.'/'.$po_items[$i]->category_name.' '.$po_items[$i]->vendor_sku.' '.$size_name_data['size'].' '.$po_items[$i]->color_name;
                            $array = array($product_name,substr($product_name,0,40),'',$po_items[$i]->hsn_code,'',$po_items[$i]->sale_price,'');
                            fputcsv($file, $array);
                        }
                        
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            if(isset($data['action']) && $data['action'] == 'sku_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=po_sku_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('SKU','Size','Barcode');
                
                $products_list = \DB::table('pos_product_master as ppm')
                ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')        
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('poi.order_id',$order_id)        
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
                ->select('ppm.product_name','ppm.product_sku','ppm.product_barcode','psc.size as size_name','poi.vendor_sku')
                ->get()->toArray();
                
                $callback = function() use ($products_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    for($i=0;$i<count($products_list);$i++){
                        $array = array($products_list[$i]->vendor_sku,$products_list[$i]->size_name, CommonHelper::filterCsvInteger($products_list[$i]->product_barcode));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
            
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
            
            return view('purchaser/product_purchase_order_detail',array('purchase_orders_items'=>$purchase_orders_items,'size_list'=>$size_list,'purchase_order_data'=>$purchase_order_data,
            'gst_type_percent'=>$gst_type_percent,'purchase_order_inventory'=>$purchase_order_inventory,'user'=>$user,'error_message'=>'','is_fake_inventory_user'=>$is_fake_inventory_user));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/product_purchase_order_detail',array('error_message'=>$e->getMessage()));
        }
    }
    
    function productPurchaseOrderEdit(Request $request,$id){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $data = $request->all();
            $user_id = Auth::id(); 
            $user = Auth::user();
            $order_id = $id;
            $error_msg = '';$size_arr = $size_list_updated = array();
            
            if(isset($data['action']) && $data['action'] == 'get_po_item_data'){
                $po_item_data = Purchase_order_items::where('id',$data['id'])->first();
                $size_data = (!empty($po_item_data->size_data))?json_decode($po_item_data->size_data,true):array();
                $size_data_rec = (!empty($po_item_data->size_data_received))?json_decode($po_item_data->size_data_received,true):array();
                
                $po_items = \DB::table('pos_product_master as ppm')
                ->Join('production_size_counts as psc','psc.id','=','ppm.size_id')
                ->where('ppm.product_sku',$po_item_data->product_sku)
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)     
                ->select('ppm.*','psc.size')->orderBy('ppm.size_id','ASC')->get()->toArray();        
                
                for($i=0;$i<count($po_items);$i++){
                    $po_items[$i]->size_count = (isset($size_data[$po_items[$i]->size_id]))?$size_data[$po_items[$i]->size_id]:0;
                    $po_items[$i]->size_count_rec = (isset($size_data_rec[$po_items[$i]->size_id]))?$size_data_rec[$po_items[$i]->size_id]:0;
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'po item data','po_items' => $po_items,'po_item_data'=>$po_item_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_po_item_data'){
                $total_qty = $inv_barcode_length = 0;
                $size_arr = array();
                $po_item_data = Purchase_order_items::where('id',$data['po_item_id'])->first();
                $po_data = Purchase_order::where('id',$po_item_data->order_id)->first();
                $size_data = (!empty($po_item_data->size_data))?json_decode($po_item_data->size_data,true):array();
                
                $po_items = \DB::table('pos_product_master as ppm')
                ->Join('production_size_counts as psc','psc.id','=','ppm.size_id')
                ->where('ppm.product_sku',$po_item_data->product_sku)
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)     
                ->select('ppm.*','psc.size')->get()->toArray();  
                
                \DB::beginTransaction();
                
                // Check if new size is added in products, but not added in po items data
                $size_data_updated = $size_data;
                for($i=0;$i<count($po_items);$i++){
                    $size_id = $po_items[$i]->size_id;
                    if(isset($data['size_'.$size_id]) && !isset($size_data[$size_id])){
                        $size_data_updated[$size_id] = 0;
                    }
                }
                
                // if new size is added in products, then add it in po items data
                if(count($size_data_updated) != count($size_data)){
                    $updateArray = array('size_data'=>json_encode($size_data_updated));
                    Purchase_order_items::where('id',$data['po_item_id'])->update($updateArray);
                    $size_data = $size_data_updated;
                }
                
                for($i=0;$i<count($po_items);$i++){
                    $size_id = $po_items[$i]->size_id;
                    if($data['size_'.$size_id] < $data['size_rec_'.$size_id]){
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Size ordered should not be less than Size Received for Size: '.$po_items[$i]->size, 'errors' =>'Size ordered should not be less than Size Received for Size: '.$po_items[$i]->size ));
                    }
                    
                    // Increase quantity
                    if($data['size_'.$size_id] > $size_data[$size_id]){
                        $diff = $data['size_'.$size_id]-$size_data[$size_id];
                        $inv_item = Pos_product_master_inventory::where('product_master_id',$po_items[$i]->id)->where('is_deleted',0)->orderBy('id','DESC')->first();
                        $inv_barcode = (!empty($inv_item->peice_barcode))?($inv_item->peice_barcode):null;
                        $product_barcode = null;
                        if(!empty($inv_barcode)){
                            $inv_barcode_number = (strlen($inv_barcode) == 19)?substr($inv_barcode,-6,6):substr($inv_barcode,-3,3);
                            $product_barcode = str_replace($inv_barcode_number,'',$inv_barcode);
                            $inv_barcode_length = strlen($inv_barcode);
                        }
                        
                        // If inventory item is not added for product, then pick data from product sku
                        if(empty($inv_item)){
                            $products = Pos_product_master::where('product_sku',$po_items[$i]->product_sku)->where('is_deleted',0)->get()->toArray();
                            for($q=0;$q<count($products);$q++){
                                $inv_item_1 = Pos_product_master_inventory::where('product_master_id',$products[$q]['id'])->where('is_deleted',0)->orderBy('id','DESC')->first();
                                if(!empty($inv_item_1)){
                                    if(!empty($inv_item_1->peice_barcode)){
                                        $inv_barcode_length = strlen($inv_item_1->peice_barcode);
                                    }
                                    break;
                                }
                            }
                            
                            if(empty($inv_item_1)){
                                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'SKU does not have inventory items', 'errors' =>'SKU does not have inventory items' ));
                            }
                            
                            if(!empty($po_items[$i]->product_barcode)){
                                $product_barcode = $po_items[$i]->product_barcode;
                                $inv_barcode_number = 0;
                            }
                        }
                        
                        $inv_barcode_length = ($inv_barcode_length > 0)?$inv_barcode_length:16;
                        
                        for($q=1;$q<=$diff;$q++){
                            if(!empty($product_barcode)){
                                $barcode = $inv_barcode_number+$q;
                                $barcode = ($inv_barcode_length == 19)?str_pad($barcode,6,'0',STR_PAD_LEFT):str_pad($barcode,3,'0',STR_PAD_LEFT);
                                $barcode = $product_barcode.$barcode;
                            }else{
                                $barcode = null;
                            }
                            
                            if(!empty($inv_item)){
                                $insertArray = array('product_master_id'=>$po_items[$i]->id,'po_id'=>$data['po_id'],'po_item_id'=>$inv_item->po_item_id,'peice_barcode'=>$barcode,'product_status'=>0,'vendor_id'=>$po_data->vendor_id,'product_sku_id'=>$po_items[$i]->product_sku_id,
                                'vendor_base_price'=>$inv_item->vendor_base_price,'vendor_gst_percent'=>$inv_item->vendor_gst_percent,'vendor_gst_amount'=>$inv_item->vendor_gst_amount,'base_price'=>$inv_item->base_price,'sale_price'=>$inv_item->sale_price);
                            }else{
                                $insertArray = array('product_master_id'=>$po_items[$i]->id,'po_id'=>$data['po_id'],'po_item_id'=>$inv_item_1->po_item_id,'peice_barcode'=>$barcode,'product_status'=>0,'vendor_id'=>$po_data->vendor_id,'product_sku_id'=>$po_items[$i]->product_sku_id,
                                'vendor_base_price'=>$inv_item_1->vendor_base_price,'vendor_gst_percent'=>$inv_item_1->vendor_gst_percent,'vendor_gst_amount'=>$inv_item_1->vendor_gst_amount,'base_price'=>$inv_item_1->base_price,'sale_price'=>$inv_item_1->sale_price);
                            }
                            
                            Pos_product_master_inventory::create($insertArray);
                        }
                    }
                    
                    // Decrease quantity
                    if($data['size_'.$size_id] < $size_data[$size_id]){
                        $diff = $size_data[$size_id]-$data['size_'.$size_id];
                        $id_array = array();
                        $inv_items = Pos_product_master_inventory::where('product_master_id',$po_items[$i]->id)->where('is_deleted',0)->orderBy('id','DESC')->limit($diff)->get()->toArray();
                        for($q=0;$q<count($inv_items);$q++){
                            $id_array[] = $inv_items[$q]['id'];
                        }
                        
                        $updateArray = array('is_deleted'=>1);
                        Pos_product_master_inventory::wherein('id',$id_array)->update($updateArray);
                    }
                    
                    $total_qty+=$data['size_'.$size_id];
                    $size_arr[$size_id] = $data['size_'.$size_id];
                }
                
                // Update only if total quantity is changed
                if(trim($total_qty) != $po_item_data->qty_ordered){
                    $cost = round($total_qty*$po_item_data->rate,2);
                    $gst_amount = round($cost*($po_item_data->gst_percent/100),2);
                    $total_cost = $cost+$gst_amount;
                    $updateArray = array('qty_ordered'=>$total_qty,'cost'=>$cost,'gst_amount'=>$gst_amount,'total_cost'=>$total_cost,'size_data'=>json_encode($size_arr));
                    Purchase_order_items::where('id',$data['po_item_id'])->update($updateArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Purchase Order Updated. PO ID: '.$data['po_id'],'PURCHASE_ORDER_UPDATED','PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'PO updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_po_item_rate_data'){
                $validateionRules = array('item_rate_edit'=>'required|numeric');
                $attributes = array('item_rate_edit'=>'Rate');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                $po_id = trim($data['po_id']);
                
                $inv_count = Pos_product_master_inventory::where('po_id',$po_id)->where('demand_id','>',0)->where('is_deleted',0)->count();
                if($inv_count > 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store push demand created for purchase order', 'errors' =>'Store push demand created for purchase order' ));
                }
                
                $po_item_id = trim($data['po_item_id_1']);
                $item_rate = trim($data['item_rate_edit']);
                
                $po_item_data = \DB::table('purchase_order_items as poi')
                ->Join('pos_product_master as ppm','ppm.id','=','poi.item_master_id')
                ->where('poi.id',$po_item_id)
                ->select('poi.*','ppm.hsn_code')->first();     
                
                $gst_data = CommonHelper::getGSTData($po_item_data->hsn_code,$item_rate);
                
                if($po_item_data->rate != $item_rate){
                    \DB::beginTransaction();
                    
                    $cost = round($item_rate*$po_item_data->qty_ordered,2);
                    $gst_amount = round($cost*($gst_data->rate_percent/100),2);
                    $updateArray = array('rate'=>$item_rate,'cost'=>$cost,'gst_percent'=>$gst_data->rate_percent,'gst_amount'=>$gst_amount,'total_cost'=>$cost+$gst_amount);
                    Purchase_order_items::where('id',$po_item_id)->update($updateArray);
                    
                    $gst_percent = $gst_data->rate_percent;
                    $gst_amount = ($gst_data->rate_percent/100)*$item_rate;
                    $base_price = round($item_rate+$gst_amount,2);
                    $updateArray = array('vendor_base_price'=>$item_rate,'vendor_gst_percent'=>$gst_data->rate_percent,'vendor_gst_amount'=>round($gst_amount,2),'base_price'=>$base_price);

                    Pos_product_master_inventory::where('po_item_id',$po_item_data->id)->where('po_id',$po_id)->update($updateArray);
                    
                    \DB::commit();
                }
                
                CommonHelper::createLog('Purchase Order Updated. PO ID: '.$po_id,'PURCHASE_ORDER_UPDATED','PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'PO Item rate updated successfully'),200);
            }
            
            $purchase_order_data = \DB::table('purchase_order as po')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->where('po.id',$order_id)
            ->select('po.*','vd.name as vendor_name','vd.email as vendor_email','u.name as user_name')->first();        
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($purchase_order_data->gst_type);
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('pos_product_master as ppm','ppm.id','=','poi.item_master_id')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.quotation_detail_id')        
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.category_id', '=', 'dlim_2.id')         
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.subcategory_id', '=', 'dlim_3.id')        
            ->where('poi.order_id',$order_id)       
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
            
            return view('purchaser/product_purchase_order_edit',array('purchase_orders_items'=>$purchase_orders_items,'size_list'=>$size_list,'purchase_order_data'=>$purchase_order_data,
            'gst_type_percent'=>$gst_type_percent,'user'=>$user,'error_message'=>''));
        }catch (\Exception $e){
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
                return view('purchaser/product_purchase_order_edit',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function productPurchaseOrderInvoice(Request $request,$id){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $size_arr = $size_list_updated = [];
            $data = $request->all();
            $order_id = $id;
            $user_id = Auth::id(); 
            
            //$company_data = CommonHelper::getCompanyData();
            
            $purchase_order_data = \DB::table('purchase_order as po')
            ->leftJoin('design_lookup_items_master as dlim','po.category_id','=','dlim.id')
            ->where('po.id',$order_id)        
            ->select('po.*','dlim.name as category_name')->first();
            
            $company_data = json_decode($purchase_order_data->company_data,true);
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($purchase_order_data->gst_type);
            
            $purchase_orders_items = \DB::table('purchase_order_items as poi')
            ->Join('pos_product_master as ppm','ppm.id','=','poi.item_master_id')
            ->Join('design_lookup_items_master as dlim','dlim.id','=','poi.quotation_detail_id')        
            ->where('poi.order_id',$order_id)  
            ->where('poi.is_deleted',0)              
            ->select('poi.*','ppm.product_sku','dlim.name as color_name')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            $vendor_data = \DB::table('vendor_detail as vd') 
            ->Join('state_list as sl','vd.state','=','sl.id')  
            ->where('vd.id',$purchase_order_data->vendor_id)        
            ->select('vd.*','sl.state_name')->first();        
            
            /*  Code to update size list start   */
            for($i=0;$i<count($purchase_orders_items);$i++){
                $size_data = (!empty($purchase_orders_items[$i]->size_data))?json_decode($purchase_orders_items[$i]->size_data,true):array();
                foreach($size_data as $size_id=>$qty){
                    if($qty > 0) $size_arr[] = $size_id;
                }
            }
            
            $size_arr = array_values(array_unique($size_arr));
            
            for($i=0;$i<count($size_list);$i++){
                if(in_array($size_list[$i]['id'],$size_arr)){
                    $size_list_updated[] = $size_list[$i];
                }
            }
            
            $size_list = $size_list_updated;
            /*  Code to update size list end   */
            
            $data = array('products_list' => array(),'company_data'=>$company_data,'po_items'=>$purchase_orders_items,
            'size_list'=>$size_list,'po_data'=>$purchase_order_data,'gst_types'=>$gst_type_percent,'vendor_data'=>$vendor_data);
            
            //return view('purchaser/product_purchase_order_invoice_pdf',$data);
            
            $pdf = PDF::loadView('purchaser/product_purchase_order_invoice_pdf', $data);

            return $pdf->download('purchase_order_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function productPurchaseOrderInvoiceList(Request $request,$id){
        try{
            $data = $request->all();
            $user_id = Auth::id(); 
            $user = Auth::user();
            $po_id = $id;
            $error_msg = '';
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if(isset($data['action']) && $data['action'] == 'get_pending_import_inv_count'){
                $inventory_count = Pos_product_master_inventory::where('po_id',$data['po_id'])->where('product_status',0)->where('is_deleted',0)->count();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Count','inventory_count' => $inventory_count),200);
            }
            
            $po_data = \DB::table('purchase_order as po')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->where('po.id',$po_id)
            ->select('po.*','vd.name as vendor_name','vd.email as vendor_email','u.name as user_name')->first();        
            
            $po_invoices = \DB::table('purchase_order_details as pod')
            ->join('purchase_order as po','po.id', '=', 'pod.po_id')
            ->Join('users as u','u.id','=','pod.user_id')
            ->leftJoin('purchase_order_grn_qc as po_grn_qc_1',function($join){$join->on('po_grn_qc_1.po_detail_id','=','pod.id')->where('po_grn_qc_1.type','=','grn')->where('po_grn_qc_1.is_deleted','=','0');})                
            ->leftJoin('purchase_order_grn_qc as po_grn_qc_2',function($join){$join->on('po_grn_qc_2.po_detail_id','=','pod.id')->where('po_grn_qc_2.type','=','qc')->where('po_grn_qc_2.is_deleted','=','0');})                
            ->where('pod.po_id',$po_id)         
            ->where('pod.is_deleted',0)
            ->where('pod.status',1)
            ->select('pod.*','u.name as user_name','po_grn_qc_1.id as grn_id','po_grn_qc_2.id as qc_id')
            ->paginate(30);//print_r($po_invoices);
            
            return view('purchaser/product_purchase_order_invoice_list',array('po_data'=>$po_data,'po_invoices'=>$po_invoices,'user'=>$user,'error_message'=>'','is_fake_inventory_user'=>$is_fake_inventory_user));
        }catch (\Exception $e){
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
                return view('purchaser/product_purchase_order_invoice_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function editPurchaseOrderData(Request $request,$po_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            
            $po_data = \DB::table('purchase_order as po')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->where('po.id',$po_id)
            ->select('po.*','vd.name as vendor_name','vd.email as vendor_email','u.name as user_name')->first();  
            
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->wherein('id',[322,324])->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return view('purchaser/purchase_order_data_edit',array('po_data'=>$po_data,'po_category_list'=>$po_category_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/purchase_order_data_edit',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function updatePurchaseOrderData(Request $request,$po_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            
            $validationRules = array('category_id'=>'required','po_id'=>'required','delivery_date'=>'required');
            $attributes = array('category_id'=>'Category','po_id'=>'PO');

            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $del_date = explode('/',trim($data['delivery_date']));
            $delivery_date = date('Y/m/d',strtotime($del_date[2].'/'.$del_date[1].'/'.$del_date[0]));    
            
            $updateArray = ['category_id'=>trim($data['category_id']),'delivery_date'=>$delivery_date,'other_comments'=>trim($data['other_comments'])];
            Purchase_order::where('id',trim($data['po_id']))->update($updateArray); 
            
            CommonHelper::createLog('Purchase Order Data Updated. ID: '.$po_id,'PURCHASE_ORDER_DATA_UPDATED','PURCHASE_ORDER');
                
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Purchase Order data updated successfully'),200);

        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function productPurchaseOrderPurchasedProducts(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = $vendor_id = '';
            $product_ids = $products_list_qc_return = $total_array = $invoice_ids = $search_array = array();
            $rec_per_page = 100;
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            // Case 1: Page paging: true, Vendor search: false, download csv: false. product ids used in qc return page data, total data not displayed
            // Case 2: Page paging: true, Vendor search: true, download csv: false. product ids used in qc return page data, total data displayed without product ids .
            // Case 3: Page paging: true, Vendor search: true/false, download csv: true. product ids used in qc return page data, total data not displayed, but calculated in csv 
            
            $products_list = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order_grn_qc_items as po_qc_items','po_grn_qc.id', '=', 'po_qc_items.grn_qc_id')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'po_qc_items.inventory_id') 
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')  
            ->join('purchase_order as po','po.id', '=', 'pod.po_id')       
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
            ->where('po_grn_qc.type','grn')              
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('po_grn_qc.is_deleted',0)        
            ->where('po_qc_items.is_deleted',0) 
            ->where('pod.is_deleted',0)     
            ->where('po.fake_inventory',0)        
            ->where('ppmi.fake_inventory',0)                
            ->where('ppm.fake_inventory',0)                
            ->where('ppm.is_deleted',0);      
            
            if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                $products_list = $products_list->whereRaw("po_grn_qc.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'");
            }
            
            if(isset($data['po_no']) && !empty($data['po_no'])){
                $search_array['po_no'] = trim($data['po_no']);
                $products_list = $products_list->where('po.order_no','LIKE',trim($data['po_no']).'%');
            }
            
            if(isset($data['vendor_id']) && !empty($data['vendor_id'])){
                $search_array['vendor_id'] = $vendor_id = trim($data['vendor_id']);
                $products_list = $products_list->where('po.vendor_id',$vendor_id);
            }
            
            if(isset($data['sku']) && !empty($data['sku'])){
                $search_array['sku'] = $sku = trim($data['sku']);
                $products_list = $products_list->whereRaw("(ppm.product_sku = '$sku' OR poi.vendor_sku = '$sku')");
            }
            
            // Total data
            $products_list_total = clone $products_list;
            $products_list_total = $products_list_total->selectRaw('COUNT(po_qc_items.id) as inv_count')->first();
            $total_array['grn'] = $products_list_total->inv_count;
                    
            $products_list = $products_list->groupByRaw('po_grn_qc.id,ppm.id')        
            ->selectRaw('ppm.id as product_id,ppm.product_name,ppm.product_sku,ppm.hsn_code,ppm.sale_price,ppm.color_id,ppm.size_id,ppm.season_id,
            poi.vendor_sku,poi.rate as poi_rate,poi.gst_percent as poi_gst_percent,po.vendor_id,
            po.order_no,pod.id as invoice_id,pod.invoice_no,pod.invoice_date,pod.products_count,po_grn_qc.grn_no,po_grn_qc.created_at as grn_date,poi.rate,
            poi.gst_percent,COUNT(po_qc_items.id) as inv_count,po_grn_qc.type as po_grn_qc_type')
            ->orderByRaw('po_grn_qc.id,poi.id,ppm.size_id');
                   
            // Download paging or page paging
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $products_list = $products_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $products_list = $products_list->paginate($rec_per_page);
            }
            
            for($i=0;$i<count($products_list);$i++){
                $invoice_ids[] = $products_list[$i]->invoice_id;
            }
            
            if(!empty($invoice_ids)){
                $products_list_return = \DB::table('purchase_order_grn_qc as po_grn_qc')
                ->join('purchase_order_grn_qc_items as po_qc_items','po_grn_qc.id', '=', 'po_qc_items.grn_qc_id')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'po_qc_items.inventory_id') 
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')   
                ->join('purchase_order as po','po.id', '=', 'pod.po_id')        
                ->where('po_grn_qc.type','qc_return')  
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('po_grn_qc.is_deleted',0)        
                ->where('po_qc_items.is_deleted',0) 
                ->where('pod.fake_inventory',0)        
                ->where('ppmi.fake_inventory',0)                
                ->where('ppm.fake_inventory',0)                
                ->where('ppm.is_deleted',0)
                ->where('pod.is_deleted',0);     
                
                if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                    $products_list_return = $products_list_return->whereRaw("po_grn_qc.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'");
                }

                if(isset($data['po_no']) && !empty($data['po_no'])){
                    $products_list_return = $products_list_return->where('po.order_no','LIKE',trim($data['po_no']).'%');
                }

                if(isset($data['vendor_id']) && !empty($data['vendor_id'])){
                    $vendor_id = trim($data['vendor_id']);
                    $products_list_return = $products_list_return->where('po.vendor_id',$vendor_id);
                }

                if(isset($data['sku']) && !empty($data['sku'])){
                    $sku = trim($data['sku']);
                    $products_list_return = $products_list_return->whereRaw("(ppm.product_sku = '$sku' OR poi.vendor_sku = '$sku')");
                }
                
                // In total data, product id not used. When vendor id search is executed, then product min and max are less than actual as they are from paging records
                $products_list_return_total = clone $products_list_return;
                $products_list_return_total = $products_list_return_total
                ->selectRaw('COUNT(po_qc_items.id) as inv_count')->first();
                $total_array['return'] = $products_list_return_total->inv_count;
                
                // product id are used for page paging as it reduces the records to be fetched for qc return
                $products_list_return = $products_list_return
                ->where('pod.id','>=',min($invoice_ids))        
                ->where('pod.id','<=',max($invoice_ids))        
                ->groupByRaw('po_grn_qc.id,ppm.id')        
                ->selectRaw('pod.id as invoice_id,ppm.id as product_id,COUNT(po_qc_items.id) as inv_count')        
                ->get();

                for($i=0;$i<count($products_list_return);$i++){
                    $key = $products_list_return[$i]->invoice_id.'_'.$products_list_return[$i]->product_id;
                    $products_list_qc_return[$key] = $products_list_return[$i];
                }
            }
            
            for($i=0;$i<count($products_list);$i++){
                $key = $products_list[$i]->invoice_id.'_'.$products_list[$i]->product_id;
                $products_list[$i]->inv_return = (isset($products_list_qc_return[$key]))?$products_list_qc_return[$key]->inv_count:0;
            }
            
            $vendor_list = CommonHelper::getVendorsList();
            for($i=0;$i<count($vendor_list);$i++){
                $vendor_id_list[$vendor_list[$i]['id']] = $vendor_list[$i]['name'];
            }
            
            $size_list = Production_size_counts::select('id','size')->get()->toArray();
            for($i=0;$i<count($size_list);$i++){
                $size_id_list[$size_list[$i]['id']] = $size_list[$i]['size'];
            }
            
            $item_list = Design_lookup_items_master::wherein('type',['color','season'])->where('is_deleted',0)->where('status',1)->select('id','name','type')->get()->toArray();
            for($i=0;$i<count($item_list);$i++){
                if(strtolower($item_list[$i]['type']) == 'color'){
                    $color_id_list[$item_list[$i]['id']] = $item_list[$i]['name'];
                }elseif(strtolower($item_list[$i]['type']) == 'season'){
                    $season_id_list[$item_list[$i]['id']] = $item_list[$i]['name'];
                }
            }
            
            $sno = (isset($data['page']))?(($data['page']-1)*$rec_per_page)+1:1;
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=purchase_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('S.No','Supplier','Style','Item Name','Size','Color','HSN Code','Season','PO No','Bill No','Bill Date','GRN No','GRN Date','Qty','Return','Rate','Cost','GST %','GST Amt','Total Cost','Sale Price');
                
                $callback = function() use ($products_list,$vendor_id_list,$size_id_list,$color_id_list,$season_id_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total_data = array('qty'=>0,'gst_amt'=>0,'cost'=>0,'total_cost'=>0,'return'=>0);
                    for($i=0;$i<count($products_list);$i++){
                        $cost = $products_list[$i]->rate*$products_list[$i]->inv_count;
                        $cost_rounded = round($cost,2);
                        $gst_amt = ($products_list[$i]->rate*$products_list[$i]->inv_count)*($products_list[$i]->gst_percent/100);
                        $gst_amt_rounded = round($gst_amt,2);
                        
                        $vendor_name = $vendor_id_list[$products_list[$i]->vendor_id];
                        $size_name = $size_id_list[$products_list[$i]->size_id];
                        $color_name = $color_id_list[$products_list[$i]->color_id];
                        $season_name = isset($season_id_list[$products_list[$i]->season_id])?$season_id_list[$products_list[$i]->season_id]:'';
                        
                        $array = array($i+1,$vendor_name,$products_list[$i]->vendor_sku,$products_list[$i]->product_name,$size_name,$color_name,$products_list[$i]->hsn_code,
                        $season_name,$products_list[$i]->order_no,$products_list[$i]->invoice_no,date('d-m-Y',strtotime($products_list[$i]->invoice_date)),$products_list[$i]->grn_no,date('d-m-Y',strtotime($products_list[$i]->grn_date)),
                        $products_list[$i]->inv_count,$products_list[$i]->inv_return,$products_list[$i]->rate,$cost_rounded,$products_list[$i]->gst_percent.'%',$gst_amt_rounded,$cost_rounded+$gst_amt_rounded,$products_list[$i]->sale_price);
                        
                        fputcsv($file, $array);
                        
                        $total_data['qty']+=$products_list[$i]->inv_count;
                        $total_data['gst_amt']+=$gst_amt;
                        $total_data['cost']+=$cost;
                        $total_data['total_cost']+=($cost+$gst_amt);
                        $total_data['return']+=$products_list[$i]->inv_return;
                    }
                    
                    $array = array('Total','','','','','','','','','','','','',$total_data['qty'],$total_data['return'],'',round($total_data['cost'],2),'',round($total_data['gst_amt'],2),round($total_data['total_cost'],2),'');
                    fputcsv($file, $array);
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            // Download CSV end
            
            return view('purchaser/product_purchase_order_purchased_products',array('products_list'=>$products_list,
            'sno'=>$sno,'error_message'=>'','vendor_list'=>$vendor_list,'vendor_id_list'=>$vendor_id_list,'size_id_list'=>$size_id_list,
            'color_id_list'=>$color_id_list,'season_id_list'=>$season_id_list,'total_array'=>$total_array,'search_array'=>$search_array));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/product_purchase_order_purchased_products',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function productPurchaseOrderStockDetails(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = $vendor_id = '';
            $invoice_ids = $products_list_wh_out_data = $total_array = $category_id_list = $vendor_id_list = $size_id_list = $story_id_list = $search_array = array();
            $rec_per_page = 100;
            
            // Case 1: Page paging: true, Vendor search: false, download csv: false. product ids used in stock out page data, total data not displayed
            // Case 2: Page paging: true, Vendor search: true, download csv: false. product ids used in stock out page data, total data displayed without product ids .
            // Case 3: Page paging: true, Vendor search: true/false, download csv: true. product ids used in stock out page data, total data not displayed, but calculated in csv 
            
            // Inventory which is imported in warehouse and qc status = 1
            $products_list = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order_grn_qc_items as po_qc_items','po_grn_qc.id', '=', 'po_qc_items.grn_qc_id')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'po_qc_items.inventory_id') 
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')  
            ->join('purchase_order as po','po.id', '=', 'pod.po_id')       
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
            ->where('po_grn_qc.type','grn')              
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('ppmi.product_status','>',0)        
            ->where('ppmi.qc_status',1)        
            ->where('po_grn_qc.is_deleted',0)        
            ->where('po_qc_items.is_deleted',0) 
            ->where('pod.is_deleted',0)     
            ->where('po.fake_inventory',0)        
            ->where('ppmi.fake_inventory',0)                
            ->where('ppm.fake_inventory',0)                
            ->where('ppm.is_deleted',0);      
            
            if(isset($data['vendor_id']) && !empty($data['vendor_id'])){
                $search_array['vendor_id'] = $vendor_id = trim($data['vendor_id']);
                $products_list = $products_list->where('po.vendor_id',$vendor_id);
            }
            
            if(isset($data['sku']) && !empty($data['sku'])){
                $search_array['sku'] = $sku = trim($data['sku']);
                $products_list = $products_list->whereRaw("(ppm.product_sku = '$sku' OR poi.vendor_sku = '$sku')");
            }
            
            // Total data
            $products_list_total = clone $products_list;
            $products_list_total = $products_list_total->selectRaw('COUNT(po_qc_items.id) as inv_count')->first();
            $total_array['grn'] = $products_list_total->inv_count;
                    
            $products_list = $products_list->groupByRaw('po_grn_qc.id,ppm.id')        
            ->selectRaw('ppm.id as product_id,ppm.product_name,ppm.product_sku,ppm.sale_price,ppm.color_id,ppm.size_id,ppm.season_id,pod.id as invoice_id,
            poi.vendor_sku,poi.rate as poi_rate,po.vendor_id,ppm.story_id,ppm.category_id,poi.rate,po.order_no,COUNT(po_qc_items.id) as inv_count')
            ->orderByRaw('po_grn_qc.id,poi.id,ppm.size_id');
                   
            // Download paging or page paging
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $products_list = $products_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $products_list = $products_list->paginate($rec_per_page);
            }
            
            for($i=0;$i<count($products_list);$i++){
                $invoice_ids[] = $products_list[$i]->invoice_id;
            }
            
            // Inventory which is out from warehouse. Status 1 is in wh and status 2 in demands which are not closed
            if(!empty($invoice_ids)){
                $products_list_wh_out = \DB::table('purchase_order_grn_qc as po_grn_qc')
                ->join('purchase_order_grn_qc_items as po_qc_items','po_grn_qc.id', '=', 'po_qc_items.grn_qc_id')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'po_qc_items.inventory_id') 
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')   
                ->join('purchase_order as po','po.id', '=', 'pod.po_id')         
                ->where('po_grn_qc.type','grn')  
                ->where('ppmi.product_status','>',2)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppmi.qc_status',1)
                ->where('ppmi.fake_inventory',0)        
                ->where('po_grn_qc.is_deleted',0)        
                ->where('po_qc_items.is_deleted',0) 
                ->where('ppm.fake_inventory',0)
                ->where('po.fake_inventory',0)        
                ->where('ppm.is_deleted',0)
                ->where('pod.is_deleted',0);     
                
                if(isset($data['vendor_id']) && !empty($data['vendor_id'])){
                    $vendor_id = trim($data['vendor_id']);
                    $products_list_wh_out = $products_list_wh_out->where('po.vendor_id',$vendor_id);
                }

                if(isset($data['sku']) && !empty($data['sku'])){
                    $sku = trim($data['sku']);
                    $products_list_wh_out = $products_list_wh_out->whereRaw("(ppm.product_sku = '$sku' OR poi.vendor_sku = '$sku')");
                }
                
                // In total data, product id not used. When vendor id search is executed, then product min and max are less than actual as they are from paging records
                $products_list_wh_out_total = clone $products_list_wh_out;
                $products_list_wh_out_total = $products_list_wh_out_total
                ->selectRaw('COUNT(po_qc_items.id) as inv_count')->first();
                $total_array['inv_out'] = $products_list_wh_out_total->inv_count;
                
                // product id are used for page paging and download csv as it reduces the records to be fetched for stock out
                $products_list_wh_out = $products_list_wh_out
                ->where('pod.id','>=',min($invoice_ids))        
                ->where('pod.id','<=',max($invoice_ids))        
                ->groupByRaw('po_grn_qc.id,ppm.id')        
                ->selectRaw('pod.id as invoice_id,ppm.id as product_id,COUNT(po_qc_items.id) as inv_count')        
                ->get();
                
                for($i=0;$i<count($products_list_wh_out);$i++){
                    $key = $products_list_wh_out[$i]->invoice_id.'_'.$products_list_wh_out[$i]->product_id;
                    $products_list_wh_out_data[$key] = $products_list_wh_out[$i];
                }
            }
            
            for($i=0;$i<count($products_list);$i++){
                $key = $products_list[$i]->invoice_id.'_'.$products_list[$i]->product_id;
                $products_list[$i]->inv_out = (isset($products_list_wh_out_data[$key]))?$products_list_wh_out_data[$key]->inv_count:0;
            }
            
            $vendor_list = CommonHelper::getVendorsList();
            for($i=0;$i<count($vendor_list);$i++){
                $vendor_id_list[$vendor_list[$i]['id']] = $vendor_list[$i]['name'];
            }
            
            $size_list = Production_size_counts::select('id','size')->get();
            for($i=0;$i<count($size_list);$i++){
                $size_id_list[$size_list[$i]->id] = $size_list[$i]->size;
            }
            
            $item_list = Design_lookup_items_master::wherein('type',['POS_PRODUCT_CATEGORY','SEASON'])->select('id','name','type')->get()->toArray();
            for($i=0;$i<count($item_list);$i++){
                if(strtolower($item_list[$i]['type']) == 'pos_product_category'){
                    $category_id_list[$item_list[$i]['id']] = $item_list[$i]['name'];
                }elseif(strtolower($item_list[$i]['type']) == 'season'){
                    $season_id_list[$item_list[$i]['id']] = $item_list[$i]['name'];
                }
            }
            
            $story_list = \DB::table('story_master')->where('is_deleted')->select('id','name')->get();
            for($i=0;$i<count($story_list);$i++){
                $story_id_list[$story_list[$i]->id] = $story_list[$i]->name;
            }
            
            $sno = (isset($data['page']))?(($data['page']-1)*$rec_per_page)+1:1;
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=purchase_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('S.No','Style','Inventory Group','Item Name','Size','Vendor','Po No','Op. Stock','Tot. Out','Bal Qty','Cost Price','Sale Price','Story','Season');
                
                $callback = function() use ($products_list,$vendor_id_list,$size_id_list,$category_id_list,$season_id_list,$story_id_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total_data = array('qty'=>0,'inv_out'=>0,'bal'=>0);
                    for($i=0;$i<count($products_list);$i++){
                        $vendor_name = $vendor_id_list[$products_list[$i]->vendor_id];
                        $size_name = $size_id_list[$products_list[$i]->size_id];
                        $category_name = isset($category_id_list[$products_list[$i]->category_id])?$category_id_list[$products_list[$i]->category_id]:'';
                        $season_name = isset($season_id_list[$products_list[$i]->season_id])?$season_id_list[$products_list[$i]->season_id]:'';
                        $story_name = isset($story_id_list[$products_list[$i]->story_id])?$story_id_list[$products_list[$i]->story_id]:'';
                        
                        $array = array($i+1,$products_list[$i]->vendor_sku,$category_name,$products_list[$i]->product_name,$size_name,$vendor_name,$products_list[$i]->order_no,
                        $products_list[$i]->inv_count,$products_list[$i]->inv_out,($products_list[$i]->inv_count-$products_list[$i]->inv_out),$products_list[$i]->rate,$products_list[$i]->sale_price,    
                        $story_name,$season_name);
                        
                        fputcsv($file, $array);
                        
                        $total_data['qty']+=$products_list[$i]->inv_count;
                        $total_data['inv_out']+=$products_list[$i]->inv_out;
                        $total_data['bal']+=($products_list[$i]->inv_count-$products_list[$i]->inv_out);
                    }
                    
                    $array = array('Total','','','','','','',$total_data['qty'],$total_data['inv_out'],$total_data['bal']);
                    fputcsv($file, $array);
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            // Download CSV end
            
            return view('purchaser/product_purchase_order_stock_details',array('products_list'=>$products_list,'story_id_list'=>$story_id_list,
            'sno'=>$sno,'error_message'=>'','vendor_list'=>$vendor_list,'vendor_id_list'=>$vendor_id_list,'size_id_list'=>$size_id_list,
            'category_id_list'=>$category_id_list,'season_id_list'=>$season_id_list,'total_array'=>$total_array,'search_array'=>$search_array));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/product_purchase_order_stock_details',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function storeStockDetails(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = '';
            $rec_per_page = 100;
            $color_id_list = $category_id_list = $store_id_list = $story_id_list = $size_id_list = $product_ids = $images_list = $search_array = array();
            $inv_transfer_total = $inv_receive_total = 0;
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            $report_types = array('1'=>'Warehouse to Store','2'=>'Store to Warehouse','3'=>'Store to Store');
            $report_type = (isset($data['type']))?$data['type']:1;
            
            $store_products = \DB::table('store_products_demand_inventory as spdi')
            ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
            ->join('pos_product_master as ppm','ppm.id', '=', 'spdi.product_id')   
            ->leftJoin('purchase_order_items as poi',function($join){$join->on('spdi.po_item_id','=','poi.id')->where('poi.fake_inventory','=','0');})                                
            ->where('spdi.transfer_status',1)        
            ->where('spdi.is_deleted',0)
            ->where('spdi.demand_status',1)         
            ->where('spdi.fake_inventory',0)        
            ->where('spd.fake_inventory',0)        
            ->where('ppm.fake_inventory',0)            
            ->where('ppm.is_deleted',0);  
            
            if($report_type == 1){
                $store_products = $store_products->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                ->where('spd.demand_type','inventory_push');
            }
            
            if($report_type == 2){
                $store_products = $store_products->wherein('spd.demand_status',array('warehouse_dispatched','warehouse_loading','warehouse_loaded'))    
                ->wherein('spd.demand_type',['inventory_return_to_warehouse','inventory_return_complete']);
            }
            
            if($report_type == 3){
                $store_products = $store_products->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
                ->where('spd.demand_type','inventory_transfer_to_store');
            }
            
            if(isset($data['sku']) && !empty($data['sku'])){
                $search_array['sku'] = $sku = trim($data['sku']);
                $store_products = $store_products->whereRaw("(ppm.product_sku = '$sku' OR poi.vendor_sku = '$sku')");
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $search_array['invoice_no'] = $search_invoice_no = trim($data['invoice_no']);
                $store_products = $store_products->where('spd.invoice_no',$search_invoice_no);
            }
            
            if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                $store_products = $store_products->whereRaw("spd.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'");
            }        
            
            if(isset($data['store_id']) && !empty($data['store_id'])){
                $search_array['store_id'] = $search_store_id = trim($data['store_id']);
                $store_products = $store_products->where('spd.store_id',$search_store_id);
            }
            
            $store_products_total = clone $store_products;
            
            $store_products_total = $store_products_total->selectRaw('SUM(spdi.transfer_status) as transfer_count,SUM(spdi.receive_status) as receive_count')->first();
            $inv_transfer_total =  $store_products_total->transfer_count;
            $inv_receive_total =  $store_products_total->receive_count;
            
            $store_products = $store_products->groupByRaw('spd.id,ppm.id')        
            ->selectRaw('ppm.id as product_id,ppm.product_name,ppm.product_sku,ppm.hsn_code,ppm.size_id,ppm.color_id,ppm.category_id,
            ppm.sale_price,poi.vendor_sku,spd.invoice_no,spd.created_at as invoice_date,spd.receive_docket_no,spd.receive_date,
            spd.credit_invoice_no,spdi.store_base_price,spd.store_id,ppm.id as image_name,SUM(spdi.transfer_status) as transfer_count,SUM(spdi.receive_status) as receive_count')
            ->orderByRaw('spd.id,ppm.id'); 
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $store_products = $store_products->offset($start)->limit($limit)->get();
            }else{
                $store_products = $store_products->paginate($rec_per_page); 
                
                for($i=0;$i<count($store_products);$i++){
                    $product_ids[] = $store_products[$i]->product_id;
                }

                if(!empty($product_ids)){
                    $images = \DB::table('pos_product_images')->where('product_id','>=',min($product_ids))->where('product_id','<=',max($product_ids))
                    ->where('image_type','front')->where('is_deleted',0)->select('id','product_id','image_name','image_type')
                    ->get()->toArray();

                    for($i=0;$i<count($images);$i++){
                        $images_list[$images[$i]->product_id] = $images[$i];
                    }
                }
            }
            
            $sno = (isset($data['page']))?(($data['page']-1)*$rec_per_page)+1:1;
            
            $item_list = Design_lookup_items_master::wherein('type',['color','POS_PRODUCT_CATEGORY'])->select('id','name','type')->get()->toArray();
            for($i=0;$i<count($item_list);$i++){
                if(strtolower($item_list[$i]['type']) == 'color'){
                    $color_id_list[$item_list[$i]['id']] = $item_list[$i]['name'];
                }elseif(strtolower($item_list[$i]['type']) == 'pos_product_category'){
                    $category_id_list[$item_list[$i]['id']] = $item_list[$i]['name'];
                }
            }
            
            $store_list = CommonHelper::getStoresList();    
            for($i=0;$i<count($store_list);$i++){
                $store_id_list[$store_list[$i]['id']] = $store_list[$i];
            }
            
            $story_list = \DB::table('story_master')->select('id','name')->get()->toArray();
            for($i=0;$i<count($story_list);$i++){
                $story_id_list[$story_list[$i]->id] = $story_list[$i]->name;
            }
            
            $size_list = Production_size_counts::select('id','size')->get()->toArray();
            for($i=0;$i<count($size_list);$i++){
                $size_id_list[$size_list[$i]['id']] = $size_list[$i]['size'];
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_stock_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('S.No','Doc No','Store Name','Store Code','Item Name','Doc Date','Rec. DocNo','Rec. Date','Qty','Rec Qty','Value','Type','Style','Sale Price','Inventory Group','Size','Color');
                
                $callback = function() use ($store_products,$color_id_list,$story_id_list,$size_id_list,$category_id_list,$store_id_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total_data = array('qty'=>0,'rec_qty'=>0);
                    for($i=0;$i<count($store_products);$i++){
                        $receive_count = (!empty($store_products[$i]->receive_count))?$store_products[$i]->receive_count:0;
                        $doc_receive_date = (!empty($store_products[$i]->receive_date))?date('d-m-Y',strtotime($store_products[$i]->receive_date)):'';
                        $sku = (!empty($store_products[$i]->vendor_sku))?$store_products[$i]->vendor_sku:$store_products[$i]->product_sku;
                        
                        $array = array($i+1,$store_products[$i]->invoice_no,$store_id_list[$store_products[$i]->store_id]['store_name'],$store_id_list[$store_products[$i]->store_id]['store_id_code'],$store_products[$i]->product_name,
                        date('d-m-Y',strtotime($store_products[$i]->invoice_date)),$store_products[$i]->receive_docket_no,
                        $doc_receive_date,$store_products[$i]->transfer_count,$receive_count,$store_products[$i]->store_base_price,'',
                        $sku,$store_products[$i]->sale_price,$category_id_list[$store_products[$i]->category_id],$size_id_list[$store_products[$i]->size_id],$color_id_list[$store_products[$i]->color_id]);
                        
                        fputcsv($file, $array);
                        
                        $total_data['qty']+=$store_products[$i]->transfer_count;
                        $total_data['rec_qty']+=$receive_count;
                    }

                    $array = array('Total','','','','','','','',$total_data['qty'],$total_data['rec_qty'],'','','','','','');
                    fputcsv($file, $array);
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            // Download CSV end
            
            
            return view('purchaser/store_stock_details',array('store_products'=>$store_products,'sno'=>$sno,
            'error_message'=>'','report_types'=>$report_types,'report_type'=>$report_type,'store_list'=>$store_list,
            'color_id_list'=>$color_id_list,'category_id_list'=>$category_id_list,'store_id_list'=>$store_id_list,
            'story_id_list'=>$story_id_list,'size_id_list'=>$size_id_list,'images_list'=>$images_list,'inv_transfer_total'=>$inv_transfer_total,
            'inv_receive_total'=>$inv_receive_total,'search_array'=>$search_array));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/store_stock_details',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function productSkuDetails(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_msg = $vendor_id = '';
            $sku_details_status = $sku_details_store1 = $sku_details_store = $store_list = $store_ids = $sku_list = $size_ids = $size_list = array();
            $po_item_ids = $sku_data = array();
            $sku_per_page = 50;
            
            $skus = \DB::table('purchase_order_items as poi')
            ->join('pos_product_master_inventory AS ppmi','poi.id', '=', 'ppmi.po_item_id')        
            ->where('ppmi.product_status',4)        
            ->where('poi.is_deleted',0)
            ->where('poi.fake_inventory',0);
                    
            // Vendor filter
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $vendor_id = trim($data['v_id']);
                $skus = $skus->where('poi.vendor_id',$vendor_id);
            }
            
            $skus = $skus
            ->orderBy('poi.id')        
            ->groupBy('poi.id')        
            ->selectRaw('poi.id as po_item_id,poi.vendor_id,poi.product_sku,poi.vendor_sku');
                    
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $page_action = 'download_csv';
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $skus = $skus->offset($start)->limit($limit)->get();
            }else{
                $skus = $skus->paginate($sku_per_page);
            }        
                   
            for($i=0;$i<count($skus);$i++){
                $po_item_ids[] = $skus[$i]->po_item_id;
                $sku_data[$skus[$i]->po_item_id] = $skus[$i];
            }
            
            $poi_item_id_where = 'ppmi.po_item_id >= '.min($po_item_ids).' AND ppmi.po_item_id <= '.max($po_item_ids);
            
            /** Inventory in Store data start **/
            
            $inventory_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master AS ppm','ppm.id', '=', 'ppmi.product_master_id')        
            ->where('ppmi.product_status',4)        
            ->where('ppmi.is_deleted',0)        
            ->where('ppmi.fake_inventory',0)
            ->where('ppmi.qc_status',1);
            
            if(!empty($vendor_id)){
                $inventory_list = $inventory_list->where('ppmi.vendor_id',$vendor_id);
            }
            
            $inventory_list_total = clone $inventory_list;
            $inventory_list_total = $inventory_list_total->selectRaw('COUNT(ppmi.id) as inv_count')->first();
            $inventory_list_total = $inventory_list_total->inv_count;
            
            $inventory_list = $inventory_list->whereRaw($poi_item_id_where)
            ->groupByRaw('ppmi.po_item_id,ppmi.store_id,ppm.size_id')        
            ->selectRaw('ppmi.po_item_id,ppmi.store_id,ppm.size_id,COUNT(ppmi.id) as inv_count')        
            ->get();
            
            $sku_details_store = $inventory_list;

            for($i=0;$i<count($sku_details_store);$i++){
                //$key = $sku_details_store[$i]->store_id.'_'.$sku_details_store[$i]->product_sku;
                $key = $sku_details_store[$i]->store_id.'_'.$sku_details_store[$i]->po_item_id;
                $sku_details_store1[$key][$sku_details_store[$i]->size_id] = $sku_details_store[$i]->inv_count;
                //$sku_list[] = $sku_details_store[$i]->product_sku;
                $sku_list[] = $sku_details_store[$i]->po_item_id;
                $store_ids[] = $sku_details_store[$i]->store_id;
                $size_ids[] = $sku_details_store[$i]->size_id;
            }

            $sku_details_store = $sku_details_store1;

            $sku_list = array_values(array_unique($sku_list));
            $store_ids = array_values(array_unique($store_ids));
            $size_ids = array_values(array_unique($size_ids));

            $store_list = Store::wherein('id',$store_ids)->where('is_deleted',0)->get()->toArray(); 
            $size_list = Production_size_counts::wherein('id',$size_ids)->where('is_deleted',0)->where('status',1)->get()->toArray();

            $page = (isset($data['page']))?$data['page']:1;
            $sno = (($page-1)*$sku_per_page)+1;

            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){

                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=product_sku_details.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('S.No','Product SKU','Store Name','Store Code');
                for($i=0;$i<count($size_list);$i++){
                    $columns[] = $size_list[$i]['size'];
                }
                $columns[] = 'Total Inventory';

                $callback = function() use ($sku_details_store,$store_list,$sku_list,$size_list,$sku_data,$sno, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    $total_data = array('total'=>0,'store'=>0);$count = 1;$store_sku = 0;$sku_sizes = array(); 
                    for($i=0;$i<count($sku_list);$i++){
                        $sku_sizes = array(); 
                        for($q=0;$q<count($store_list);$q++){
                            $key = $store_list[$q]['id'].'_'.$sku_list[$i];
                            if(isset($sku_details_store[$key])){
                                $inv = $sku_details_store[$key];
                                $array = array($sno,$sku_data[$sku_list[$i]]->product_sku,$store_list[$q]['store_name'],$store_list[$q]['store_id_code']);
                                $inv_sku = 0;
                                for($z=0;$z<count($size_list);$z++){
                                    $size_id = $size_list[$z]['id'];
                                    $inv = isset($sku_details_store[$key][$size_id])?$sku_details_store[$key][$size_id]:0;
                                    $store_sku+=$inv;
                                    $inv_sku+=$inv;
                                    $total_data['total']+=$inv;
                                    if(isset($sku_sizes[$size_id])) $sku_sizes[$size_id]+=$inv; else $sku_sizes[$size_id] = $inv; 

                                    $array[] = $inv;
                                }

                                $array[] = $inv_sku;
                                fputcsv($file, $array);
                            }
                        }

                        $sno++;
                        $array = array($sku_data[$sku_list[$i]]->product_sku.' Total','','','');
                        for($z=0;$z<count($size_list);$z++){
                            $size_id = $size_list[$z]['id'];
                            $array[] = isset($sku_sizes[$size_id])?$sku_sizes[$size_id]:0;
                        }
                        $array[] = $store_sku;
                        fputcsv($file, $array);
                        $store_sku = 0;
                    }

                    $array = array('Total','','','');
                    for($z=0;$z<count($size_list);$z++){
                        $array[] = '';
                    }

                    $array[] = $total_data['total'];
                    fputcsv($file, $array);
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            // Download CSV end
                
            $vendor_list = CommonHelper::getVendorsList(); 
            
            return view('purchaser/product_sku_details',array('sku_details_status'=>$sku_details_status,'sno'=>$sno,
            'sku_details_store'=>$sku_details_store,'store_list'=>$store_list,'vendor_list'=>$vendor_list,
            'sku_list'=>$sku_list,'size_list'=>$size_list,'error_message'=>'','sku_data'=>$sku_data,'skus'=>$skus,
            'inventory_list_total'=>$inventory_list_total,'vendor_id'=>$vendor_id));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCT_SKU_DETAILS',__FUNCTION__,__FILE__);
            return view('purchaser/product_sku_details',array('error_message'=>$e->getMessage().' '.$e->getLine()));
        }
    }
    
    function listProductPurchaseOrderGrn(Request $request){
        try{
            $user = Auth::user();
            $data = $request->all();
            $grn_inv_list = array();
            
            $grn_list = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->Join('purchase_order_details as pod','po_grn_qc.po_detail_id','=','pod.id')    
            ->Join('purchase_order as po','po_grn_qc.po_id','=','po.id')                
            ->Join('vendor_detail as v','v.id','=','po.vendor_id')   
            ->join('design_lookup_items_master as dlim_po_cat','po.category_id', '=', 'dlim_po_cat.id')      
            ->leftJoin('purchase_order_grn_qc as po_grn_qc_1',function($join){$join->on('po_grn_qc_1.po_detail_id','=','pod.id')->where('po_grn_qc_1.type','=','qc')->where('po_grn_qc_1.is_deleted','=','0');})                        
            ->where('po_grn_qc.type','grn')
            ->where('po_grn_qc.is_deleted',0)
            ->where('pod.is_deleted',0)
            ->where('po_grn_qc.fake_inventory',0)        
            ->where('pod.fake_inventory',0)        
            ->where('po.fake_inventory',0)                
            ->orderBy('po_grn_qc.id');        
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $grn_list = $grn_list->where('po.vendor_id',$data['v_id']);
            }
            
            if(isset($data['po_cat_id']) && !empty($data['po_cat_id'])){
                $grn_list = $grn_list->where('po.category_id',$data['po_cat_id']);
            }
            
            if(isset($data['po_no']) && !empty($data['po_no'])){
                $grn_list = $grn_list->where('po.order_no','LIKE','%'.$data['po_no'].'%');
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $grn_list = $grn_list->where('pod.invoice_no','LIKE','%'.$data['invoice_no'].'%');
            }
			
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            $start_date = $search_date['start_date'];
            $end_date = $search_date['end_date'];
            
            if(!empty($start_date) && !empty($end_date)){
                $grn_list = $grn_list->whereRaw("po_grn_qc.created_at BETWEEN '$start_date' AND '$end_date'");   
            }
                
            $grn_list = $grn_list->select('po_grn_qc.*','v.name as vendor_name','v.email as vendor_email','dlim_po_cat.name as po_category_name','pod.invoice_no','po.order_no as po_no','po_grn_qc_1.id as qc_id')
            ->orderByRaw('po.id,po_grn_qc.id');
			
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $grn_list = $grn_list->get()->toArray();
            }else{
                $grn_list = $grn_list->paginate(100);
            }
			
            $grn_inv = \DB::table('pos_product_master_inventory as ppmi')
            ->where('ppmi.grn_id','>',0)
            ->wherein('ppmi.product_status',array(1,4))
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0)        
            ->groupBy('ppmi.grn_id')
            ->selectRaw('ppmi.grn_id,COUNT(ppmi.id) as balance_cnt,SUM(ppmi.vendor_base_price) as vendor_base_price_total,SUM(ppmi.vendor_gst_amount) as vendor_gst_amount_total,SUM(ppmi.base_price) as base_price_total')
            ->get()->toArray();

            for($i=0;$i<count($grn_inv);$i++){
                $key = $grn_inv[$i]->grn_id;
                $grn_inv_list[$key]['balance_cnt'] = $grn_inv[$i]->balance_cnt;
                $grn_inv_list[$key]['balance_vendor_base_price_total'] = $grn_inv[$i]->vendor_base_price_total;
                $grn_inv_list[$key]['balance_vendor_gst_amount_total'] = $grn_inv[$i]->vendor_gst_amount_total;
                $grn_inv_list[$key]['balance_base_price_total'] = $grn_inv[$i]->base_price_total;
            }

            for($i=0;$i<count($grn_list);$i++){
                $grn_id = $grn_list[$i]->id;
                $grn_list[$i]->balance_cnt = (isset($grn_inv_list[$grn_id]))?$grn_inv_list[$grn_id]['balance_cnt']:0;
                $grn_list[$i]->balance_vendor_base_price_total = (isset($grn_inv_list[$grn_id]))?$grn_inv_list[$grn_id]['balance_vendor_base_price_total']:0;
                $grn_list[$i]->balance_vendor_gst_amount_total = (isset($grn_inv_list[$grn_id]))?$grn_inv_list[$grn_id]['balance_vendor_gst_amount_total']:0;
                $grn_list[$i]->balance_base_price_total = (isset($grn_inv_list[$grn_id]))?$grn_inv_list[$grn_id]['balance_base_price_total']:0;
            }
            
            $grn_inv = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->Join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc_items.grn_qc_id','=','po_grn_qc.id')                   
            ->Join('pos_product_master_inventory as ppmi','po_grn_qc_items.inventory_id','=','ppmi.id')      
            ->where('po_grn_qc.type','grn')        
            //->where('ppmi.grn_id','>',0)
            ->where('ppmi.is_deleted',0)
            ->where('po_grn_qc.is_deleted',0)        
            ->where('po_grn_qc_items.is_deleted',0)    
            ->where('po_grn_qc.fake_inventory',0)
            ->where('po_grn_qc_items.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)        
            ->groupBy('po_grn_qc.id')
            ->selectRaw('po_grn_qc.id as grn_id,COUNT(ppmi.id) as grn_cnt,SUM(ppmi.vendor_base_price) as vendor_base_price_total,SUM(ppmi.vendor_gst_amount) as vendor_gst_amount_total,SUM(ppmi.base_price) as base_price_total')
            ->get()->toArray();
            
            $grn_inv_list = array();
            
            for($i=0;$i<count($grn_inv);$i++){
                $key = $grn_inv[$i]->grn_id;
                $grn_inv_list[$key]['grn_cnt'] = $grn_inv[$i]->grn_cnt;
                $grn_inv_list[$key]['grn_vendor_base_price_total'] = $grn_inv[$i]->vendor_base_price_total;
                $grn_inv_list[$key]['grn_vendor_gst_amount_total'] = $grn_inv[$i]->vendor_gst_amount_total;
                $grn_inv_list[$key]['grn_base_price_total'] = $grn_inv[$i]->base_price_total;
            }

            for($i=0;$i<count($grn_list);$i++){
                $grn_id = $grn_list[$i]->id;
                $grn_list[$i]->grn_cnt = (isset($grn_inv_list[$grn_id]))?$grn_inv_list[$grn_id]['grn_cnt']:0;
                $grn_list[$i]->grn_vendor_base_price_total = (isset($grn_inv_list[$grn_id]))?$grn_inv_list[$grn_id]['grn_vendor_base_price_total']:0;
                $grn_list[$i]->grn_vendor_gst_amount_total = (isset($grn_inv_list[$grn_id]))?$grn_inv_list[$grn_id]['grn_vendor_gst_amount_total']:0;
                $grn_list[$i]->grn_base_price_total = (isset($grn_inv_list[$grn_id]))?$grn_inv_list[$grn_id]['grn_base_price_total']:0;
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                //$grn_list = $grn_list->get()->toArray();
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=grn_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('GRN No','Invoice No','PO No','Vendor','Inventory Count','Vendor Price','GST Amt','Base Price','Balance Qty','Vendor Price','GST Amt','Base Price','Category','QC Completed','Created On');

                $callback = function() use ($grn_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_data = array('inv'=>0,'vendor_price'=>0,'gst_amt'=>0,'base_price'=>0,'bal_inv'=>0,'bal_vendor_price'=>0,'bal_gst_amt'=>0,'bal_base_price'=>0);
                    
                    for($i=0;$i<count($grn_list);$i++){
                        //$other_data = json_decode($grn_list[$i]->other_data,true);
                        $qc_completed = (!empty($grn_list[$i]->qc_id))?'Yes':'No';
                       
                        $array = array($grn_list[$i]->grn_no,$grn_list[$i]->invoice_no,$grn_list[$i]->po_no,$grn_list[$i]->vendor_name,$grn_list[$i]->grn_cnt,
                        $grn_list[$i]->grn_vendor_base_price_total,$grn_list[$i]->grn_vendor_gst_amount_total,$grn_list[$i]->grn_base_price_total,    
                        $grn_list[$i]->balance_cnt,$grn_list[$i]->balance_vendor_base_price_total,$grn_list[$i]->balance_vendor_gst_amount_total,$grn_list[$i]->balance_base_price_total,
                        $grn_list[$i]->po_category_name,$qc_completed,date('d-m-Y',strtotime($grn_list[$i]->created_at)));
                        
                        $total_data['inv']+=$grn_list[$i]->grn_cnt;
			$total_data['vendor_price']+=$grn_list[$i]->grn_vendor_base_price_total;
                        $total_data['gst_amt']+=$grn_list[$i]->grn_vendor_gst_amount_total;
                        $total_data['base_price']+=$grn_list[$i]->grn_base_price_total;
                        
                        $total_data['bal_inv']+=$grn_list[$i]->balance_cnt;
			$total_data['bal_vendor_price']+=$grn_list[$i]->balance_vendor_base_price_total;
                        $total_data['bal_gst_amt']+=$grn_list[$i]->balance_vendor_gst_amount_total;
                        $total_data['bal_base_price']+=$grn_list[$i]->balance_base_price_total;
                        
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','','',$total_data['inv'],$total_data['vendor_price'],$total_data['gst_amt'],$total_data['base_price'],
                    $total_data['bal_inv'],$total_data['bal_vendor_price'],$total_data['bal_gst_amt'],$total_data['bal_base_price'],'','');
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            //$grn_list = $grn_list->paginate(100);
           
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where(array('status'=>1,'is_deleted'=>0))->orderBy('name')->get()->toArray();
            $vendors_list = Vendor_detail::where('is_deleted',0)->where('status',1)->orderBy('name')->get()->toArray();
            
            return view('purchaser/product_purchase_order_grn_list',array('grn_list'=>$grn_list,'user'=>$user,'error_message'=>'','po_category_list'=>$po_category_list,'vendors_list'=>$vendors_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/product_purchase_order_grn_list',array('error_message'=>$e->getMessage().', Line:'.$e->getLine()));
        }
    }
    
    function createBulkPurchaseOrder(Request $request){
        try{
            $design_list = Design::where('design_review','approved')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $fabric_list = Design_lookup_items_master::where('type','FABRIC_NAME')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $vendor_list = CommonHelper::getVendorsList();
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $unit_list = Unit::where('is_deleted',0)->get()->toArray();
            
            return view('purchaser/bulk_purchase_order_create',array('design_list'=>$design_list,'fabric_list'=>$fabric_list,'color_list'=>$color_list,'vendor_list'=>$vendor_list,'error_message'=>'','po_category_list'=>$po_category_list,'unit_list'=>$unit_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'QUOTATION',__FUNCTION__,__FILE__);
            return view('purchaser/bulk_purchase_order_create',array('error_message'=>$e->getMessage()));
        }
    }
    
    function saveBulkPurchaseOrder(Request $request){
        try{
            //$size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $data = $request->all();
            $user_id = Auth::id(); 
            $error_msg = '';
            $size_data = array();
            
            if(isset($data['style_data']) && $data['style_data'] == 1){
                
                $style_data = \DB::table('designs as d')->where('id',trim($data['id']))->where('is_deleted',0)->first();
                $req_fields = array('category_id','sub_cat_id','net_cost','mrp','color_id','hsn_code');
                
                for($i=0;$i<count($req_fields);$i++){
                    if(empty($style_data->{$req_fields[$i]})){
                        $error_msg.='Invalid '.strtoupper(str_replace('_',' ',$req_fields[$i])).' of Product. <br/>';
                    }
                }

                if(!empty($error_msg)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Style Data','style_data' => $style_data),200);
            }
            
            if(isset($data['create_order']) && $data['create_order'] == 1){
                \DB::beginTransaction();
                
                $vendor_data = Vendor_detail::where('id',$data['vendor_id'])->first();
                                
                if(!isset($vendor_data->gst_no) || empty($vendor_data->gst_no)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor GST numnber is invalid', 'errors' =>'Vendor GST numnber is invalid' ));
                }
                
                if(!isset($vendor_data->vendor_code) || empty($vendor_data->vendor_code)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor Code is invalid', 'errors' =>'Vendor Code is invalid' ));
                }
                
                $gst_type = CommonHelper::getGSTType($vendor_data->gst_no);
                $del_date = explode('/',$data['delivery_date']);
                $delivery_date = date('Y/m/d',strtotime($del_date[2].'/'.$del_date[1].'/'.$del_date[0]));    
                $today_latest_po = Purchase_order::whereRaw("DATE(created_at) = CURDATE()")->select('order_no')->orderBy('order_no','DESC')->first();
                $po_number = (!empty($today_latest_po) && strlen($today_latest_po->order_no) == 13)?substr($today_latest_po->order_no,10):0;
                $po_number = 'KSPO'.Date('ymd').str_pad($po_number+1,3,'0',STR_PAD_LEFT);
                
                $po_exists = Purchase_order::where('order_no',$po_number)->select('order_no')->first();
                if(!empty($po_exists)){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Error in creating PO Number', 'errors' =>'Error in creating PO Number' ));
                }
                
                $insertArray = array('quotation_id'=>null,'order_no'=>$po_number,'user_id'=>$user_id,'type_id'=>4,'vendor_id'=>$data['vendor_id'],'other_cost'=>$data['other_cost'],'other_comments'=>$data['other_comments'],'gst_type'=>$gst_type,'delivery_date'=>$delivery_date,'category_id'=>$data['category_id']);
                
                $company_data = CommonHelper::getCompanyData();
                $insertArray['company_data'] = json_encode($company_data);
                
                $purchase_order = Purchase_order::create($insertArray);
                
                $purchase_order_id = $purchase_order->id;
                
                $style_list = $data['rows'];//print_r($style_list);exit;
                
                for($i=0;$i<count($style_list);$i++){
                    if(! (isset($style_list[$i]['gst_percent']) && !empty($style_list[$i]['gst_percent']))){
                        $error_msg.='GST Percent not defined for '.$style_list[$i]['style'].' HSN Code <br>';
                    }
                }
                
                if(!empty($error_msg)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                for($i=0;$i<count($style_list);$i++){
                    //print_r($size_data);exit;
                    
                    $cost = round($style_list[$i]['style_rate']*$style_list[$i]['style_qty'],2);
                    $gst_amount = round(($style_list[$i]['gst_percent']/100)*$cost,2);
                    $total_cost = $cost+$gst_amount;
                    
                    $insertArray = array('order_id'=>$purchase_order_id,'product_sku'=>$style_list[$i]['design_name'],'vendor_sku'=>$style_list[$i]['design_name'].'-'.$vendor_data->vendor_code,'item_master_id'=>$style_list[$i]['fabric_id'],
                    'quotation_detail_id'=>$style_list[$i]['color_id'],'design_id'=>$style_list[$i]['design_id'],'vendor_id'=>$data['vendor_id'],'qty_ordered'=>$style_list[$i]['style_qty'],'rate'=>$style_list[$i]['style_rate'],'size_data'=>null,
                    'cost'=>$cost,'gst_percent'=>$style_list[$i]['gst_percent'],'gst_amount'=>$gst_amount,'total_cost'=>$total_cost,'width_id'=>$style_list[$i]['width_id'],'content_id'=>$style_list[$i]['content_id'],'gsm_id'=>$style_list[$i]['gsm_id'],
                    'unit_id'=>$style_list[$i]['unit_id']);
                    
                    $po_item = Purchase_order_items::create($insertArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Bulk Purchase Order Created. PO ID: '.$purchase_order_id,'BULK_PURCHASE_ORDER_CREATED','BULK_PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Purchase Order created successfully','po_details' => $purchase_order),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function listBulkPurchaseOrder(Request $request){
        try{
            $user = Auth::user();
            $data = $request->all();
            
            $purchase_orders = \DB::table('purchase_order as po')
            ->leftJoin('purchase_order_details as pod','po.id','=','pod.po_id')        
            ->Join('users as u','u.id','=','po.user_id')
            ->Join('vendor_detail as v','v.id','=','po.vendor_id')   
            ->join('design_lookup_items_master as dlim_po_cat','po.category_id', '=', 'dlim_po_cat.id')        
            ->where('type_id',4);
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $purchase_orders = $purchase_orders->where('po.vendor_id',$data['v_id']);
            }
            
            if(isset($data['po_cat_id']) && !empty($data['po_cat_id'])){
                $purchase_orders = $purchase_orders->where('po.category_id',$data['po_cat_id']);
            }
            
            if(isset($data['po_no']) && !empty($data['po_no'])){
                $purchase_orders = $purchase_orders->where('po.order_no','LIKE','%'.$data['po_no'].'%');
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $purchase_orders = $purchase_orders->where('pod.invoice_no','LIKE','%'.$data['invoice_no'].'%');
            }
            
            if(isset($data['po_id']) && !empty($data['po_id'])){
                $purchase_orders = $purchase_orders->where('po.id',$data['po_id']);
            }
                
            $purchase_orders = $purchase_orders->select('po.*','u.name as user_name','v.name as vendor_name','v.email as vendor_email','dlim_po_cat.name as po_category_name',\DB::raw('count(pod.id) as invoice_count'))
            ->groupBy('po.id')->orderBy('po.id','DESC')->paginate(100);
            
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where(array('status'=>1,'is_deleted'=>0))->orderBy('name')->get()->toArray();
            $vendors_list = Vendor_detail::where('is_deleted',0)->where('status',1)->orderBy('name')->get()->toArray();
            
            return view('purchaser/bulk_purchase_order_list',array('purchase_orders'=>$purchase_orders,'user'=>$user,'error_message'=>'','po_category_list'=>$po_category_list,'vendors_list'=>$vendors_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/bulk_purchase_order_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function bulkPurchaseOrderDetail(Request $request,$id){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $data = $request->all();
            $user_id = Auth::id(); 
            $user = Auth::user();
            $order_id = $id;
            $error_msg = '';$size_arr = $size_list_updated = array();
            
            $purchase_order_data = \DB::table('purchase_order as po')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->where('po.id',$order_id)
            ->select('po.*','vd.name as vendor_name','vd.email as vendor_email','u.name as user_name')->first();        
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($purchase_order_data->gst_type);
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.item_master_id')        
            ->Join('design_lookup_items_master as dlim_2','dlim_2.id','=','poi.quotation_detail_id')                
            ->Join('design_lookup_items_master as dlim_3','dlim_3.id','=','poi.width_id') 
            ->Join('design_lookup_items_master as dlim_4','dlim_4.id','=','poi.content_id') 
            ->Join('design_lookup_items_master as dlim_5','dlim_5.id','=','poi.gsm_id')         
            ->Join('designs as d','d.id','=','poi.design_id')         
            ->leftJoin('units as u','u.id','=','poi.unit_id')         
            ->where('poi.order_id',$order_id)       
            ->select('poi.*','dlim_1.name as fabric_name','dlim_2.name as color_name','dlim_3.name as width_name','dlim_4.name as content_name','dlim_5.name as gsm_name','d.sku','u.code as unit_code')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            if(isset($data['action']) && $data['action'] == 'ean_csv'){
                $headers = array(
                    'Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=file_po_'.$purchase_order_data->id.'.csv',
                    'Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0'
                );

                $columns = array('Product Name','Product Description (Should be less than 40 characters)','Sub Category','HS Code','Date of Activation','MRP:Pan India','MRP Activation Date:Pan India');

                $callback = function() use ($po_items, $columns,$size_list){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    for($i=0;$i<count($po_items);$i++){
                        $size_data = json_decode($po_items[$i]->size_data,true);
                        foreach($size_data as $size_id=>$qty){
                            if(empty($qty)) continue;
                            $size_name_data = CommonHelper::getArrayRecord($size_list,'id',$size_id);
                            
                            $product_name = $po_items[$i]->subcategory_name.'/'.$po_items[$i]->category_name.' '.$po_items[$i]->vendor_sku.' '.$size_name_data['size'].' '.$po_items[$i]->color_name;
                            $array = array($product_name,substr($product_name,0,40),'',$po_items[$i]->hsn_code,'',$po_items[$i]->sale_price,'');
                            fputcsv($file, $array);
                        }
                        
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            if(isset($data['action']) && $data['action'] == 'sku_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=po_sku_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('SKU','Size','Barcode');
                
                $products_list = \DB::table('pos_product_master as ppm')
                ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')        
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                ->where('poi.order_id',$order_id)        
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
                ->select('ppm.product_name','ppm.product_sku','ppm.product_barcode','psc.size as size_name','poi.vendor_sku')
                ->get()->toArray();
                
                $callback = function() use ($products_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    for($i=0;$i<count($products_list);$i++){
                        $array = array($products_list[$i]->vendor_sku,$products_list[$i]->size_name,$products_list[$i]->product_barcode);
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
            
            return view('purchaser/bulk_purchase_order_detail',array('purchase_orders_items'=>$purchase_orders_items,'purchase_order_data'=>$purchase_order_data,
            'gst_type_percent'=>$gst_type_percent,'user'=>$user,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/bulk_purchase_order_detail',array('error_message'=>$e->getMessage()));
        }
    }
    
    function bulkPurchaseOrderInvoice(Request $request,$id){
        try{
            $data = $request->all();
            $order_id = $id;
            $user_id = Auth::id(); 
            
            //$company_data = CommonHelper::getCompanyData();
            
            $purchase_order_data = \DB::table('purchase_order as po')
            ->leftJoin('design_lookup_items_master as dlim','po.category_id','=','dlim.id')
            ->where('po.id',$order_id)        
            ->select('po.*','dlim.name as category_name')->first();
            
            $company_data = json_decode($purchase_order_data->company_data,true);
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($purchase_order_data->gst_type);
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.item_master_id')        
            ->Join('design_lookup_items_master as dlim_2','dlim_2.id','=','poi.quotation_detail_id')                
            ->Join('design_lookup_items_master as dlim_3','dlim_3.id','=','poi.width_id') 
            ->Join('design_lookup_items_master as dlim_4','dlim_4.id','=','poi.content_id') 
            ->Join('design_lookup_items_master as dlim_5','dlim_5.id','=','poi.gsm_id')         
            ->Join('designs as d','d.id','=','poi.design_id')         
            ->leftJoin('units as u','u.id','=','poi.unit_id')         
            ->where('poi.order_id',$order_id)       
            ->select('poi.*','dlim_1.name as fabric_name','dlim_2.name as color_name','dlim_3.name as width_name','dlim_4.name as content_name','d.sku','dlim_5.name as gsm_name','u.code as unit_code')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            $vendor_data = \DB::table('vendor_detail as vd') 
            ->Join('state_list as sl','vd.state','=','sl.id')  
            ->where('vd.id',$purchase_order_data->vendor_id)        
            ->select('vd.*','sl.state_name')->first();        
            
            $data = array('products_list' => array(),'company_data'=>$company_data,'po_items'=>$purchase_orders_items,
            'po_data'=>$purchase_order_data,'gst_types'=>$gst_type_percent,'vendor_data'=>$vendor_data);
            
            //return view('purchaser/bulk_purchase_order_invoice_pdf',$data);
            
            $pdf = PDF::loadView('purchaser/bulk_purchase_order_invoice_pdf', $data);

            return $pdf->download('purchase_order_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function bulkPurchaseOrderInvoiceList(Request $request,$id){
        try{
            $data = $request->all();
            $user_id = Auth::id(); 
            $user = Auth::user();
            $po_id = $id;
            $error_msg = '';
            
            if(isset($data['action']) && $data['action'] == 'get_pending_import_inv_count'){
                $inventory_count = Pos_product_master_inventory::where('po_id',$data['po_id'])->where('product_status',0)->where('is_deleted',0)->count();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Count','inventory_count' => $inventory_count),200);
            }
            
            $po_data = \DB::table('purchase_order as po')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->where('po.id',$po_id)
            ->select('po.*','vd.name as vendor_name','vd.email as vendor_email','u.name as user_name')->first();        
            
            $po_invoices = \DB::table('purchase_order_details as pod')
            ->join('purchase_order as po','po.id', '=', 'pod.po_id')
            ->Join('users as u','u.id','=','pod.user_id')
            ->leftJoin('purchase_order_grn_qc as po_grn_qc_1',function($join){$join->on('po_grn_qc_1.po_detail_id','=','pod.id')->where('po_grn_qc_1.type','=','grn')->where('po_grn_qc_1.is_deleted','=','0');})                
            ->leftJoin('purchase_order_grn_qc as po_grn_qc_2',function($join){$join->on('po_grn_qc_2.po_detail_id','=','pod.id')->where('po_grn_qc_2.type','=','qc')->where('po_grn_qc_2.is_deleted','=','0');})                
            ->where('pod.po_id',$po_id)         
            ->where('pod.is_deleted',0)
            ->where('pod.status',1)
            ->select('pod.*','u.name as user_name','po_grn_qc_1.id as grn_id','po_grn_qc_2.id as qc_id')->paginate(50);//print_r($po_invoices);
            
            return view('purchaser/bulk_purchase_order_invoice_list',array('po_data'=>$po_data,'po_invoices'=>$po_invoices,'user'=>$user,'error_message'=>''));
        }catch (\Exception $e){
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
                return view('purchaser/bulk_purchase_order_invoice_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function importBulkPurchaseOrderItems(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $invoice_id = $id;
            $error_message = '';
            
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
                    ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)        
                    ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0);
                    
                    $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                    ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('ppmi.po_id',$data['po_id'])
                    ->where('ppmi.po_detail_id',$data['po_detail_id'])                
                    ->where('ppmi.product_status','>=',1)
                    ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)        
                    ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)
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
                    ->join('purchase_order_items as poi','ppm.product_sku', '=', 'poi.product_sku')    
                    ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                    ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                    ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->leftJoin('vendor_detail as vd','vd.id', '=', 'po.vendor_id')        
                    ->where('po_qc_items.grn_qc_id',$grn_data->id)        
                    ->where('poi.order_id',$grn_data->po_id)        
                    ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)
                    ->where('po_qc_items.is_deleted',0) 
                    ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0);        
                    
                    $sku_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                    ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                    ->where('poi.order_id',$grn_data->po_id) 
                    ->where('po_qc_items.grn_qc_id',$grn_data->id)                      
                    //->where('ppmi.product_status','>=',1)
                    ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)        
                    ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)
                    ->groupByRaw('ppmi.product_master_id')
                    ->selectRaw('ppm.id,poi.vendor_sku,psc.size as size_name,COUNT(ppmi.id) as inv_count')
                    ->orderByRaw('poi.vendor_sku,psc.id')        
                    ->get()->toArray();        
                    
                    $inv_imported = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                    ->where('po_qc_items.grn_qc_id',$grn_data->id)        
                    ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)
                    ->where('po_qc_items.is_deleted',0) 
                    ->count();        
                }
                
                if(isset($data['barcode']) && !empty($data['barcode'])){
                    $product_list = $product_list->where('ppmi.peice_barcode',trim($data['barcode']));
                }
                
                if(isset($data['product_id']) && !empty($data['product_id'])){
                    $product_list = $product_list->where('ppmi.product_master_id',trim($data['product_id']));
                }
                
                $product_list = $product_list->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)
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
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
                ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.size_id','dlim_1.name as color_name',
                'psc.size as size_name','po.order_no as po_order_no','vd.name as vendor_name','ppm.size_id','ppm.product_barcode','poi.vendor_sku')->first();
                
                if(empty($product_data)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with Code: <b>'.$data['barcode'].'</b> does not exists', 'errors' =>'Inventory Product with Code: <b>'.$data['barcode'].'</b> does not exists' ));
                }
                
                if($product_data->product_status != 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Inventory Product with Code: <b>'.$data['barcode'].'</b> already added', 'errors' =>'Inventory Product with Code: <b>'.$data['barcode'].'</b> already added' ));
                }
                
                \DB::beginTransaction();
                
                $updateArray = array('product_status'=>1,'intake_date'=>date('Y/m/d H:i:s'),'po_detail_id'=>$data['po_detail_id']);
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 

                $po_detail_data = Purchase_order_items::where('order_id',$data['po_id'])->where('product_sku',trim($product_data->product_sku))->first();//echo $product->product_sku;exit;

                $po_detail_data->increment('qty_received');
                
                $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                if(isset($po_size_data_received[$product_data->size_id])) $po_size_data_received[$product_data->size_id] = $po_size_data_received[$product_data->size_id]+1;else $po_size_data_received[$product_data->size_id] = 1;

                $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Product with Code: <b>'.$product_data->peice_barcode.'</b> added ','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_po_invoice_details'){
                $vehicle_details = Purchase_order_details::where('id',$data['id'])->first();
                if(!empty($vehicle_details)){
                    $vehicle_details->images_list = json_decode($vehicle_details->images_list,true);
                }
                
                $grn_data = $products_list = array();
                /*if(!empty($vehicle_details->grn_id)){
                    
                    $grn_data = Purchase_order_grn_qc::where('id',$vehicle_details->grn_id)->where('is_deleted',0)->where('status',1)->first();
                    
                    $products_list = \DB::table('pos_product_master_inventory as ppmi')
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                    ->join('purchase_order_items as poi','poi.product_sku', '=', 'ppm.product_sku')        
                    ->where('ppmi.grn_id',$vehicle_details->grn_id)->where('ppmi.product_status',1)        
                    ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)
                    ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
                    ->groupBy('ppm.product_sku')        
                    ->selectRaw('ppm.product_name,ppm.product_sku,ppmi.vendor_base_price,vendor_gst_percent,vendor_gst_amount,count(ppmi.id) as products_count,poi.rate,poi.gst_percent')
                    ->get()->toArray();
                }*/
                
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
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1)
                ->where('po_qc_items.is_deleted',0) 
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)        
                ->groupByRaw('ppm.size_id')        
                ->select('psc.size as size_name','ppm.size_id',\DB::raw('count(po_qc_items.id) as inv_count'))
                ->get()->toArray();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Size data','size_data'=>$size_data),200);
            }
            
            $product_list = $sku_products_list = $po_invoice_list = array();
            
            $po_detail_data = Purchase_order_details::where('id',$invoice_id)->first();
            
            $grn_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','grn')->where('is_deleted',0)->where('status',1)->first();
            
            $qc_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','qc')->where('is_deleted',0)->where('status',1)->first();
            
            $po_data = Purchase_order::where('id',$po_detail_data->po_id)->first();
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($po_data->gst_type);
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.item_master_id')        
            ->Join('design_lookup_items_master as dlim_2','dlim_2.id','=','poi.quotation_detail_id')                
            ->Join('design_lookup_items_master as dlim_3','dlim_3.id','=','poi.width_id') 
            ->Join('design_lookup_items_master as dlim_4','dlim_4.id','=','poi.content_id') 
            ->Join('design_lookup_items_master as dlim_5','dlim_5.id','=','poi.gsm_id')                  
            ->Join('designs as d','d.id','=','poi.design_id')         
            ->leftJoin('units as u','u.id','=','poi.unit_id')                 
            ->where('poi.order_id',$po_detail_data->po_id)       
            ->select('poi.*','dlim_1.name as fabric_name','dlim_2.name as color_name','dlim_3.name as width_name','dlim_4.name as content_name','dlim_5.name as gsm_name','u.code as unit_code','d.sku')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            if(!empty($grn_data)){
                $sku_list = $sku_products_list = $sku_data = $product_list = array();
            }
            
            return view('purchaser/bulk_purchase_order_items_import',array('error_message'=>$error_message,'po_detail_data'=>$po_detail_data,'grn_data'=>$grn_data,'purchase_orders_items'=>$purchase_orders_items,'po_data'=>$po_data,'gst_type_percent'=>$gst_type_percent,'po_invoice_list'=>$po_invoice_list,'qc_data'=>$qc_data,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('purchaser/bulk_purchase_order_items_import',array('error_message'=>$e->getMessage(),'demands_list'=>array()));
            }
        }
    }
    
    function submitImportBulkPurchaseOrderItems(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $images_list = $invoice_items = array();
            
            if(isset($data['action']) && $data['action']  == 'add_vehicle_details'){
                $validateionRules = array('vehicle_no'=>'required','containers_count'=>'required|numeric','invoice_no'=>'required','invoice_date'=>'required');
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
                
                $insertArray = array('po_id'=>$data['po_id'],'vehicle_no'=>$data['vehicle_no'],'containers_count'=>$data['containers_count'],'images_list'=>json_encode($images_list),'comments'=>$data['comments'],
                'invoice_no'=>$data['invoice_no'],'invoice_date'=>$invoice_date,'products_count'=>null,'user_id'=>$user->id);
                
                $po_details = Purchase_order_details::create($insertArray);

                \DB::commit();
                
                CommonHelper::createLog('Bulk Purchase Order Invoice Created. PO ID: '.$po_details->id,'BULK_PURCHASE_ORDER_INVOICE_CREATED','BULK_PURCHASE_ORDER');

                return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','po_details'=>$po_details,'message' => 'PO Invoice added successfully'),201);
            }
            
            if(isset($data['action']) && $data['action']  == 'add_bulk_po_grn'){
                \DB::beginTransaction();
                
                $grn_exists = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('type','grn')->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->count();
                if($grn_exists > 0){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'GRN already added for Invoice', 'errors' =>'GRN already added for Invoice' ));
                }
                
                $po_items = Purchase_order_items::where('order_id',$data['po_id'])->get()->toArray();
                for($i=0;$i<count($po_items);$i++){
                    $id = $po_items[$i]['id'];
                    if(isset($data['qty_'.$id]) && !empty($data['qty_'.$id]) && filter_var(trim($data['qty_'.$id]), FILTER_VALIDATE_INT) === false ){
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Invoice Items should have numeric value', 'errors' =>'Invoice Items should have numeric value' ));
                    }
                }
                
                for($i=0;$i<count($po_items);$i++){
                    $id = $po_items[$i]['id'];
                    if(isset($data['qty_'.$id]) && !empty($data['qty_'.$id])){
                        $invoice_items[$id] = trim($data['qty_'.$id]);
                    }
                }
                
                if(empty($invoice_items)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Invoice Items is Required Field', 'errors' =>'Invoice Items is Required Field' ));
                }
                
                for($i=0;$i<count($po_items);$i++){
                    $id = $po_items[$i]['id'];
                    if(isset($invoice_items[$id])){
                        $total_received = $invoice_items[$id]+$po_items[$i]['qty_received'];
                        if($total_received > $po_items[$i]['qty_ordered']){
                            return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Invoice Items Quantity Received should not be more then Quantity Ordered', 'errors' =>'Invoice Items Quantity Received should not be more then Quantity Ordered' ));
                        }
                    }
                }
                
                for($i=0;$i<count($po_items);$i++){
                    $id = $po_items[$i]['id'];
                    if(isset($invoice_items[$id])){
                        $total_received = $invoice_items[$id]+$po_items[$i]['qty_received'];
                        $updateArray = array('qty_received'=>$total_received);
                        Purchase_order_items::where('id',$id)->update($updateArray);
                    }
                }
                
                $updateArray = array('invoice_items'=>json_encode($invoice_items));
                Purchase_order_details::where('id',$data['po_detail_id'])->update($updateArray);
                
                $po_data = Purchase_order::where('id',$data['po_id'])->first();
                
                $today_latest_grn = Purchase_order_grn_qc::whereRaw("DATE(created_at) = CURDATE()")->where('type','grn')->select('grn_no')->orderBy('id','DESC')->first();
                $grn_number = (!empty($today_latest_grn))?substr($today_latest_grn->grn_no,10,3):0;
                $grn_number = 'KSGN'.Date('ymd').str_pad($grn_number+1,3,'0',STR_PAD_LEFT).'-'.str_ireplace('KSPO','',$po_data->order_no);
                
                $insertArray = array('grn_no'=>$grn_number,'po_id'=>$data['po_id'],'type'=>'GRN','comments'=>$data['add_inventory_grn_comments'],'po_detail_id'=>$data['po_detail_id']);
                $insertArray['other_data'] = json_encode($invoice_items);
                $grn = Purchase_order_grn_qc::create($insertArray);
                
                for($i=0;$i<count($po_items);$i++){
                    $id = $po_items[$i]['id'];
                    if(isset($invoice_items[$id])){
                        $quantity = $invoice_items[$id];
                        $insertArray = array('grn_qc_id'=>$grn->id,'inventory_id'=>$id,'quantity'=>$quantity,'grn_qc_date'=>date('Y/m/d H:i:s'));
                        Purchase_order_grn_qc_items::create($insertArray);
                    }
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Bulk Purchase Order GRN Created. PO ID: '.$grn->id,'BULK_PURCHASE_ORDER_GRN_CREATED','BULK_PURCHASE_ORDER');
                
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
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'PO Invoice updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'delete_inv_import_items'){
                $inv_items = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->wherein('ppmi.id',$data['deleteChk'])        
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)  
                ->where('ppm.is_deleted',0)->where('ppm.arnon_product',0)   
                ->select('ppmi.*','ppm.product_sku','ppm.size_id')        
                ->get()->toArray();
                
                \DB::beginTransaction();
                
                for($i=0;$i<count($inv_items);$i++){
                    
                    $updateArray = array('product_status'=>0,'intake_date'=>null,'po_detail_id'=>null);
                    Pos_product_master_inventory::where('id',$inv_items[$i]->id)->update($updateArray); 

                    $po_detail_data = Purchase_order_items::where('order_id',$data['po_id'])->where('product_sku',trim($inv_items[$i]->product_sku))->first();

                    $po_detail_data->decrement('qty_received');

                    $size_id = $inv_items[$i]->size_id;
                    $po_size_data_received = (!empty($po_detail_data->size_data_received))?json_decode($po_detail_data->size_data_received,true):array();
                    if(isset($po_size_data_received[$size_id])) $po_size_data_received[$size_id] = $po_size_data_received[$size_id]-1;else $po_size_data_received[$size_id] = 0;

                    $updateArray = array('size_data_received'=>json_encode($po_size_data_received));
                    Purchase_order_items::where('id',$po_detail_data->id)->update($updateArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Bulk Purchase Order Inventory Deleted. PO ID: '.$data['po_id'],'BULK_PURCHASE_ORDER_INVENTORY_DELETED','BULK_PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory import items deleted successfully'),200);
            }
            
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function bulkPurchaseOrderItemsImportInvoice(Request $request,$id){
        try{
            $data = $request->all();
            $grn_id = $id;
            
            $vendor_details = \DB::table('purchase_order_grn_qc as po_grn')
            ->join('purchase_order as po','po.id', '=', 'po_grn.po_id')
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn.po_detail_id')             
            ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
            ->where('po_grn.id',$grn_id)
            ->select('vd.*','pod.invoice_no','pod.invoice_date','po_grn.id as po_grn_id','po.id as po_id','po_grn.grn_no','po_grn.created_at as grn_created_date','pod.invoice_items','po.company_data')->first(); 
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.item_master_id')        
            ->Join('design_lookup_items_master as dlim_2','dlim_2.id','=','poi.quotation_detail_id')                
            ->Join('design_lookup_items_master as dlim_3','dlim_3.id','=','poi.width_id') 
            ->Join('design_lookup_items_master as dlim_4','dlim_4.id','=','poi.content_id') 
            ->Join('design_lookup_items_master as dlim_5','dlim_5.id','=','poi.gsm_id')                       
            ->Join('designs as d','d.id','=','poi.design_id')         
            ->leftJoin('units as u','u.id','=','poi.unit_id')             
            ->where('poi.order_id',$vendor_details->po_id)       
            ->select('poi.*','dlim_1.name as fabric_name','dlim_2.name as color_name','dlim_3.name as width_name','dlim_4.name as content_name','dlim_5.name as gsm_name','d.sku','u.code as unit_code')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            //$company_data = CommonHelper::getCompanyData();
            $company_data = json_decode($vendor_details->company_data,true);
            
            $data = array('message' => 'Inventory import invoice','vendor_details'=>$vendor_details,'purchase_orders_items'=>$purchase_orders_items,'company_data'=>$company_data);
            
            //return view('purchaser/bulk_purchase_order_items_import_invoice',$data);
            
            $pdf = PDF::loadView('purchaser/bulk_purchase_order_items_import_invoice', $data);

            return $pdf->download('bulk_purchase_order_items_import_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().''.$e->getLine()),500);
        }
    }
    
    function qcBulkPurchaseOrderItems(Request $request,$id){
        try{
            $data = $request->all();
            $invoice_id = $id;
            $user = Auth::user();
            $error_message = '';
            
            $product_list = $sku_products_list = $po_invoice_list = $qc_return_data = $qc_return_gate_pass_data = array();
            
            $po_detail_data = Purchase_order_details::where('id',$invoice_id)->first();
            
            $grn_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','grn')->where('is_deleted',0)->where('status',1)->first();
            
            $qc_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','qc')->where('is_deleted',0)->where('status',1)->first();
            
            $po_data = Purchase_order::where('id',$po_detail_data->po_id)->first();
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.item_master_id')        
            ->Join('design_lookup_items_master as dlim_2','dlim_2.id','=','poi.quotation_detail_id')                
            ->Join('design_lookup_items_master as dlim_3','dlim_3.id','=','poi.width_id') 
            ->Join('design_lookup_items_master as dlim_4','dlim_4.id','=','poi.content_id') 
            ->Join('design_lookup_items_master as dlim_5','dlim_5.id','=','poi.gsm_id')          
            ->Join('designs as d','d.id','=','poi.design_id')         
            ->leftJoin('units as u','u.id','=','poi.unit_id')                 
            ->where('poi.order_id',$po_detail_data->po_id)       
            ->select('poi.*','dlim_1.name as fabric_name','dlim_2.name as color_name','dlim_3.name as width_name','dlim_4.name as content_name','dlim_5.name as gsm_name','d.sku','u.code as unit_code')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            if(!empty($grn_data)){
                $sku_list = $sku_products_list = $sku_data = $product_list = array();
            }
            
            if(!empty($qc_data)){
                $qc_return_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','qc_return')->where('is_deleted',0)->where('status',1)->first();
                $grn_items = json_decode($grn_data->other_data,true);
                $qc_items = json_decode($qc_data->other_data,true);
                
                $qc_data->return_to_vendor = (empty($qc_return_data) && array_sum($grn_items) != array_sum($qc_items))?1:0;
                
                if(array_sum($grn_items) == array_sum($qc_items))    
                    $qc_data->defective_returned = 'N/A';
                elseif(empty($qc_return_data) && array_sum($grn_items) != array_sum($qc_items))
                    $qc_data->defective_returned = 'No';
                elseif(!empty($qc_return_data))
                    $qc_data->defective_returned = 'Yes';
                
                if(!empty($qc_return_data)){
                    $qc_return_items = $po_items = \DB::table('purchase_order_grn_qc_items as po_grn_qc_items')
                    ->Join('purchase_order_items as poi','poi.id','=','po_grn_qc_items.inventory_id')        
                    ->Join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_grn_qc_items.grn_qc_id')                
                    ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.item_master_id')         
                    ->Join('design_lookup_items_master as dlim_2','dlim_2.id','=','poi.quotation_detail_id')                
                    ->Join('designs as d','d.id','=','poi.design_id')         
                    ->leftJoin('units as u','u.id','=','poi.unit_id')                 
                    ->where('po_grn_qc.id',$qc_return_data->id)       
                    ->select('poi.*','dlim_1.name as fabric_name','dlim_2.name as color_name','d.sku','u.code as unit_code','po_grn_qc_items.quantity')
                    ->orderBy('poi.id','ASC')->get()->toArray();
                    
                    $qc_return_gate_pass_data = Store_products_demand_courier::where('invoice_id',$qc_return_data->id)->where('type','inventory_return')->where('is_deleted',0)->where('status',1)->first();
                    
                }
            }
            
            return view('purchaser/bulk_purchase_order_items_qc',array('error_message'=>$error_message,'po_detail_data'=>$po_detail_data,'grn_data'=>$grn_data,'purchase_orders_items'=>$purchase_orders_items,
            'po_data'=>$po_data,'po_invoice_list'=>$po_invoice_list,'qc_data'=>$qc_data,'user'=>$user,'qc_return_data'=>$qc_return_data,'qc_return_gate_pass_data'=>$qc_return_gate_pass_data));

        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/bulk_purchase_order_items_qc',array('error_message'=>$e->getMessage().'&nbsp;, Line: '.$e->getLine()));
        }
    }
    
    function submitQcBulkPurchaseOrderItems(Request $request,$id){
        try{
            $data = $request->all();
            $po_detail_id = $id;
            $qc_items = array();
            $error_message = '';
            
            if(isset($data['action']) && $data['action']  == 'add_bulk_po_qc'){
                \DB::beginTransaction();
                
                
                $qc_exists = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('type','qc')->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->count();
                if($qc_exists > 0){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'QC already added for Invoice', 'errors' =>'QC already added for Invoice' ));
                }
                
                $po_grn = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('type','grn')->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->first();
                $po_grn_items = json_decode($po_grn['other_data'],true);
                
                foreach($po_grn_items as $po_item=>$quantity){
                    if(isset($data['qc_acc_'.$po_item]) && !empty($data['qc_acc_'.$po_item]) && filter_var(trim($data['qc_acc_'.$po_item]), FILTER_VALIDATE_INT) === false ){
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'QC Items should have numeric value', 'errors' =>'QC Items should have numeric value' ));
                    }
                }
                
                foreach($po_grn_items as $po_item=>$quantity){
                    if(isset($data['qc_acc_'.$po_item]) && !empty($data['qc_acc_'.$po_item])){
                        $qc_items[$po_item] = trim($data['qc_acc_'.$po_item]);
                    }
                }
                
                if(count($qc_items) != count($po_grn_items)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'QC Items is Required Field', 'errors' =>'QC Items is Required Field' ));
                }
                
                foreach($po_grn_items as $po_item=>$quantity){
                    if($po_grn_items[$po_item] < $qc_items[$po_item]){
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'QC Items Quantity Received should not be more than Received Quantity', 'errors' =>'QC Items Quantity Received should not be more than Received Quantity' ));
                    }
                }
                
                $insertArray = array('grn_no'=>null,'po_id'=>$data['po_id'],'type'=>'QC','comments'=>$data['add_inventory_qc_comments'],'po_detail_id'=>$data['po_detail_id']);
                $insertArray['other_data'] = json_encode($qc_items);
                $grn_qc = Purchase_order_grn_qc::create($insertArray);
                $date = date('Y/m/d H:i:s');
                
                foreach($qc_items as $po_item=>$quantity){
                    $qc_status = ($qc_items[$po_item] == $po_grn_items[$po_item])?1:2;
                    $insertArray = array('grn_qc_id'=>$grn_qc->id,'inventory_id'=>$po_item,'quantity'=>$quantity,'grn_qc_date'=>$date,'qc_status'=>$qc_status);
                    Purchase_order_grn_qc_items::create($insertArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Bulk Purchase Order QC Completed. PO ID: '.$data['po_id'],'BULK_PURCHASE_ORDER_QC_COMPLETED','BULK_PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Products QC completed','status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'confirm_return_bulk_po_items'){
                \DB::beginTransaction();
                $qc_return_data = array();
                
                $validateionRules = array('boxes_count'=>'required|numeric','transporter_name'=>'required','transporter_gst'=>'required','docket_no'=>'required','eway_bill_no'=>'required');
                $attributes = array();

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                /** Update po data start **/
                
                $po_grn = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('type','grn')->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->first();
                $po_grn_items = json_decode($po_grn->other_data,true);
                
                $qc_data = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('type','qc')->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->first();
                $qc_items = json_decode($qc_data->other_data,true);
                
                foreach($po_grn_items as $po_item=>$quantity){
                    if($po_grn_items[$po_item] != $qc_items[$po_item]){
                        $qc_return_data[$po_item] = $po_grn_items[$po_item]-$qc_items[$po_item];
                    }
                }
                
                foreach($qc_return_data as $po_item=>$quantity){
                    Purchase_order_items::where('id',$po_item)->decrement('qty_received',$quantity);
                }
                
                /** Update po data end **/
                
                $invoice_no = CommonHelper::getPOInvoiceDebitNoteNo();
                $credit_note_no = CommonHelper::getPOInvoiceCreditNoteNo();
                $insertArray = array('po_id'=>$data['po_id'],'type'=>'qc_return','comments'=>null,'po_detail_id'=>$data['po_detail_id'],'grn_no'=>$invoice_no,'credit_note_no'=>$credit_note_no);
                $insertArray['other_data'] = json_encode($qc_return_data);
                $qc_return = Purchase_order_grn_qc::create($insertArray);
                $date = date('Y/m/d H:i:s');
                
                foreach($qc_return_data as $po_item=>$quantity){
                    $insertArray = array('grn_qc_id'=>$qc_return->id,'inventory_id'=>$po_item,'quantity'=>$quantity,'grn_qc_date'=>$date,'qc_status'=>2);
                    Purchase_order_grn_qc_items::create($insertArray);
                }
                
                $insertArray = array('type'=>'inventory_return','invoice_id'=>$qc_return->id,'boxes_count'=>$data['boxes_count'],'transporter_name'=>$data['transporter_name'],'transporter_gst'=>$data['transporter_gst'],'docket_no'=>$data['docket_no'],'eway_bill_no'=>$data['eway_bill_no']);
                $demand_courier = Store_products_demand_courier::create($insertArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Products returned to vendor','status' => 'success'),200);
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
                $updateArray = array('qc_status'=>0,'qc_date'=>null);
                Pos_product_master_inventory::wherein('id',$data['deleteChk'])->update($updateArray); 
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'QC Inventory deleted successfully'),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function bulkPurchaseOrderItemsReturnedInvoice(Request $request,$id,$invoice_type_id = 1){
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
            
            $qc_return_items = $po_items = \DB::table('purchase_order_grn_qc_items as po_grn_qc_items')
            ->Join('purchase_order_items as poi','poi.id','=','po_grn_qc_items.inventory_id')        
            ->Join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_grn_qc_items.grn_qc_id')                
            ->Join('design_lookup_items_master as dlim_1','dlim_1.id','=','poi.item_master_id')         
            ->Join('design_lookup_items_master as dlim_2','dlim_2.id','=','poi.quotation_detail_id')                
            ->Join('designs as d','d.id','=','poi.design_id')         
            ->leftJoin('units as u','u.id','=','poi.unit_id')                 
            ->where('po_grn_qc.id',$qc_return_id)       
            ->select('poi.*','dlim_1.name as fabric_name','dlim_2.name as color_name','d.sku','u.code as unit_code','po_grn_qc_items.quantity')
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            $data = array('message' => 'products list','qc_return_items' => $qc_return_items,'po_data' => $po_data,'company_data'=>$company_data,'gst_name'=>$gst_name,'invoice_type'=>$invoice_type);
            
            //return view('purchaser/bulk_purchase_order_items_qc_returned_invoice',$data);
            
            $pdf = PDF::loadView('purchaser/bulk_purchase_order_items_qc_returned_invoice', $data);

            return $pdf->download('bulk_purchase_order_items_qc_returned_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().$e->getFile().$e->getLine()),500);
        }
    }
    
    function bulkPurchaseOrderItemsReturnGatePass(Request $request,$id){
        try{
            $data = $request->all();
            $qc_return_id = $id;
            $user = Auth::user();
            
            $po_data = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order as po','po.id', '=', 'po_grn_qc.po_id')        
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')                
            ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
            ->where('po_grn_qc.id',$qc_return_id)
            ->select('vd.*','pod.invoice_no','pod.invoice_date','po_grn_qc.other_data','po.company_data')->first();
            
           
            //$other_data = json_decode($po_data->other_data,true);
            $total_qty = '';//$other_data['total'];
            
            $gate_pass_data = Store_products_demand_courier::where('invoice_id',$qc_return_id)->where('type','inventory_return')->where('is_deleted',0)->where('status',1)->first();
            
            //$company_data = CommonHelper::getCompanyData();
            $company_data = json_decode($po_data->company_data,true);
            
            $data = array('message' => 'products list','gate_pass_data' => $gate_pass_data,'po_data' => $po_data,'company_data'=>$company_data,'total_qty'=>$total_qty);
            
            //return view('purchaser/bulk_purchase_order_items_qc_return_gate_pass',$data);
            
            $pdf = PDF::loadView('purchaser/bulk_purchase_order_items_qc_return_gate_pass', $data);

            return $pdf->download('bulk_purchase_order_items_qc_return_gate_pass_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }
    }
    
    function createBulkFinishedPurchaseOrder(Request $request){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $vendor_list = CommonHelper::getVendorsList();
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $unit_list = Unit::where('is_deleted',0)->get()->toArray();
            $design_list = Design::where('design_review','approved')->where('is_deleted',0)->get()->toArray();
            
            return view('purchaser/bulk_finished_purchase_order_create',array('size_list'=>$size_list,'color_list'=>$color_list,'vendor_list'=>$vendor_list,'po_category_list'=>$po_category_list,'error_message'=>'','unit_list'=>$unit_list,'design_list'=>$design_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'QUOTATION',__FUNCTION__,__FILE__);
            return view('purchaser/bulk_finished_purchase_order_create',array('error_message'=>$e->getMessage()));
        }
    }
    
    function saveBulkFinishedPurchaseOrder(Request $request){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $data = $request->all();
            $user_id = Auth::id(); 
            $error_msg = '';
            $size_data =  $size_id_list = array();
            
            if(isset($data['style_data']) && $data['style_data'] == 1){
                $size_id_list = array();
                $error_msg = '';
                $style_list = \DB::table('pos_product_master as ppm')
                ->Join('design_lookup_items_master as dlim','dlim.id','=','ppm.color_id')        
                ->where('ppm.product_sku',$data['style'])
                ->where('ppm.is_deleted',0)
                ->where('ppm.arnon_product',0)
                ->where('ppm.status',1)       
                ->select('ppm.*','dlim.name as color_name')
                ->get()->toArray();
                
                if(empty($style_list)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Style does not exists', 'errors' => 'Style does not exists'));
                }
                
                for($i=0;$i<count($style_list);$i++){
                    $size_id_list[] = $style_list[$i]->size_id;
                }

                $style_data = $style_list[0];
                $style_data->size_id_list = $size_id_list;

                $req_fields = array('category_id','subcategory_id','base_price','sale_price','size_id','color_id','hsn_code');
                
                for($i=0;$i<count($req_fields);$i++){
                    if(empty($style_data->{$req_fields[$i]})){
                        $error_msg.='Invalid '.strtoupper(str_replace('_',' ',$req_fields[$i])).' of Product. <br/>';
                    }
                }

                if(!empty($error_msg)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                /*
                $item_data = \DB::table('design_item_master as dim') 
                ->Join('design_lookup_items_master as dlim','dim.name_id','=','dlim.id')            
                ->where('dlim.name','STITCHING')                
                ->where('dlim.type','GARMENT_CMT')                        
                ->where('dim.type_id',7)        
                ->where('dim.is_deleted',0) 
                ->where('dlim.is_deleted',0)         
                ->select('dim.id')                
                ->first();        
                
                if(empty($item_data)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Stitching Data not added', 'errors' => 'Stitching Data not added'));
                }
                         
                $design_data = \DB::table('designs as d')
                ->Join('design_items_instance as dii','d.id','=','dii.design_id')    
                ->where('dii.design_item_id',$item_data->id)        
                ->where('d.sku',trim($data['style']))
                ->select('dii.rate','dii.cost')        
                ->first();                
                
                if(empty($design_data)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Stitching Cost not added', 'errors' => 'Stitching Cost not added'));
                }*/
                
                $design_data = Design::where('sku',trim($data['style']))->where('is_deleted',0)->first();         
                
                $style_data->base_price = $design_data->net_cost-$design_data->fabric_cost;
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Style Data','style_data' => $style_data),200);
            }
            
            if(isset($data['create_order']) && $data['create_order'] == 1){
                \DB::beginTransaction();
                
                $vendor_data = Vendor_detail::where('id',$data['vendor_id'])->first();
                                
                if(!isset($vendor_data->gst_no) || empty($vendor_data->gst_no)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor GST numnber is invalid', 'errors' =>'Vendor GST numnber is invalid' ));
                }
                
                if(!isset($vendor_data->vendor_code) || empty($vendor_data->vendor_code)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor Code is invalid', 'errors' =>'Vendor Code is invalid' ));
                }
                
                $gst_type = CommonHelper::getGSTType($vendor_data->gst_no);
                $del_date = explode('/',$data['delivery_date']);
                $delivery_date = date('Y/m/d',strtotime($del_date[2].'/'.$del_date[1].'/'.$del_date[0]));    
                $today_latest_po = Purchase_order::whereRaw("DATE(created_at) = CURDATE()")->select('order_no')->orderBy('order_no','DESC')->first();
                $po_number = (!empty($today_latest_po) && strlen($today_latest_po->order_no) == 13)?substr($today_latest_po->order_no,10):0;
                $po_number = 'KSPO'.Date('ymd').str_pad($po_number+1,3,'0',STR_PAD_LEFT);
                
                $po_exists = Purchase_order::where('order_no',$po_number)->select('order_no')->first();
                if(!empty($po_exists)){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Error in creating PO Number', 'errors' =>'Error in creating PO Number' ));
                }
                
                $insertArray = array('quotation_id'=>null,'order_no'=>$po_number,'user_id'=>$user_id,'type_id'=>5,'vendor_id'=>$data['vendor_id'],'other_cost'=>$data['other_cost'],'other_comments'=>$data['other_comments'],'gst_type'=>$gst_type,'delivery_date'=>$delivery_date,'category_id'=>$data['category_id']);
                
                $company_data = CommonHelper::getCompanyData();
                $insertArray['company_data'] = json_encode($company_data);
                
                $purchase_order = Purchase_order::create($insertArray);
                
                $purchase_order_id = $purchase_order->id;
                $style_list = $data['rows'];
                
                for($i=0;$i<count($style_list);$i++){
                    if(! (isset($style_list[$i]['gst_percent']) && !empty($style_list[$i]['gst_percent']))){
                        $error_msg.='GST Percent not defined for '.$style_list[$i]['style'].' HSN Code <br>';
                    }
                }
                
                if(!empty($error_msg)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                for($i=0;$i<count($style_list);$i++){
                    
                    for($q=0;$q<count($size_list);$q++){
                        $size_id = $size_list[$q]['id'];
                        if(isset($style_list[$i]['size_'.$size_id]) && !empty($style_list[$i]['size_'.$size_id])){
                            $size_data[$size_id] = $style_list[$i]['size_'.$size_id];
                        }else{
                            $size_data[$size_id] = 0;
                        }
                    }
                    
                    $style_data =  Pos_product_master::where('product_sku',$style_list[$i]['style'])->where('is_deleted',0)->where('status',1)->first();
                    $design_data =  Design::where('sku',$style_list[$i]['style'])->where('is_deleted',0)->where('status',1)->first();
                    $cost = round($style_list[$i]['style_rate']*$style_list[$i]['size_total'],2);
                    $gst_amount = round(($style_list[$i]['gst_percent']/100)*$cost,2);
                    $total_cost = $cost+$gst_amount;
                    
                    $insertArray = array('order_id'=>$purchase_order_id,'product_sku'=>$style_data->product_sku,'vendor_sku'=>$style_data->product_sku.'-'.$vendor_data->vendor_code,'item_master_id'=>$style_data->id,
                    'quotation_detail_id'=>$style_list[$i]['color'],'vendor_id'=>$data['vendor_id'],'qty_ordered'=>$style_list[$i]['size_total'],'rate'=>$style_list[$i]['style_rate'],'size_data'=>json_encode($size_data),
                    'cost'=>$cost,'gst_percent'=>$style_list[$i]['gst_percent'],'gst_amount'=>$gst_amount,'total_cost'=>$total_cost,'design_id'=>$design_data->id);
                    
                    $po_item = Purchase_order_items::create($insertArray);
                    
                    /*   Add inventory start   */
                    foreach($size_data as $size_id=>$size_count){
                        if($size_count == 0) continue;
                        
                        $product_data = Pos_product_master::where('product_sku',$style_data->product_sku)->where('size_id',$size_id)->where('is_deleted',0)->where('status',1)->first();
                        //$product_data =  Design_sizes::where('design_id',$style_data->id)->where('size_id',$size_id)->where('is_deleted',0)->first();
                        
                        $vendor_rate = $style_data->base_price;
                        $vendor_gst_percent = $style_list[$i]['gst_percent'];
                        $vendor_gst_amount = round(($vendor_gst_percent/100)*$vendor_rate,2);
                        $base_price = $style_data->base_price+$vendor_gst_amount;
                        
                        //$inventory_product = Pos_product_master_inventory::where('product_master_id',$product_data->id)->where('is_deleted',0)->orderBy('id','DESC')->first();
                        $quantity = $size_count;
                        //$inventory_product_barcode = (!empty($inventory_product))?ltrim(str_replace($product_data->product_barcode,'',$inventory_product['peice_barcode']),'0'):0;

                        for($q=1;$q<=$quantity;$q++){
                            /*$barcode = $inventory_product_barcode+$q;
                            $barcode = str_pad($barcode,6,'0',STR_PAD_LEFT);
                            $barcode = $product_data->product_barcode.$barcode;*/
                            $barcode = null;
                            $insertArray = array('product_master_id'=>$product_data->id,'peice_barcode'=>$barcode,'po_item_id'=>$po_item->id,
                            'product_status'=>0,'base_price'=>$base_price,'sale_price'=>$style_data->sale_price,
                            'po_id'=>$purchase_order_id,'vendor_base_price'=>$vendor_rate,'vendor_gst_percent'=>$vendor_gst_percent,
                            'vendor_gst_amount'=>$vendor_gst_amount,'vendor_id'=>$data['vendor_id'],'product_sku_id'=>$product_data->product_sku_id);
                            Pos_product_master_inventory::create($insertArray);   
                        }
                    }
                    /*   Add inventory end  */
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Order created successfully','po_data' => $purchase_order),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine()),500);
        }
    }
    

    function createAccessoriesPurchaseOrder(Request $request){
        try{
            $accessories_list = \DB::table('accessories')->where('is_deleted',0)->orderBy('accessory_name')->get()->toArray();
            $vendor_list = CommonHelper::getVendorsList();
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return view('purchaser/accessories_purchase_order_create',array('accessories_list'=>$accessories_list,'vendor_list'=>$vendor_list,'size_list'=>$size_list,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'ACCESSORIES_PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/accessories_purchase_order_create',array('error_message'=>$e->getMessage()));
        }
    }
    
    function saveAccessoriesPurchaseOrder(Request $request){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $data = $request->all();
            $user_id = Auth::id(); 
            $error_msg = '';
            $size_data = array();
            
            if(isset($data['item_data']) && $data['item_data'] == 1){
                
                $item_data = \DB::table('accessories')->where('id',trim($data['id']))->where('is_deleted',0)->first();
                $req_fields = array('accessory_name','rate','gst_percent');
                
                for($i=0;$i<count($req_fields);$i++){
                    if(empty($item_data->{$req_fields[$i]})){
                        $error_msg.='Invalid '.strtoupper(str_replace('_',' ',$req_fields[$i])).' of Item. <br/>';
                    }
                }

                if(!empty($error_msg)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Style Data','item_data' => $item_data),200);
            }
            
            if(isset($data['create_order']) && $data['create_order'] == 1){
                \DB::beginTransaction();
                
                $vendor_data = Vendor_detail::where('id',$data['vendor_id'])->first();
                                
                if(!isset($vendor_data->gst_no) || empty($vendor_data->gst_no)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor GST numnber is invalid', 'errors' =>'Vendor GST numnber is invalid' ));
                }
                
                if(!isset($vendor_data->vendor_code) || empty($vendor_data->vendor_code)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Vendor Code is invalid', 'errors' =>'Vendor Code is invalid' ));
                }
                
                $gst_type = CommonHelper::getGSTType($vendor_data->gst_no);
                $del_date = explode('/',$data['delivery_date']);
                $delivery_date = date('Y/m/d',strtotime($del_date[2].'/'.$del_date[1].'/'.$del_date[0]));    
                $today_latest_po = Purchase_order::whereRaw("DATE(created_at) = CURDATE()")->select('order_no')->orderBy('order_no','DESC')->first();
                $po_number = (!empty($today_latest_po) && strlen($today_latest_po->order_no) == 13)?substr($today_latest_po->order_no,10):0;
                $po_number = 'KSPO'.Date('ymd').str_pad($po_number+1,3,'0',STR_PAD_LEFT);
                
                $po_exists = Purchase_order::where('order_no',$po_number)->select('order_no')->first();
                if(!empty($po_exists)){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Error in creating PO Number', 'errors' =>'Error in creating PO Number' ));
                }
                
                $insertArray = array('quotation_id'=>null,'order_no'=>$po_number,'user_id'=>$user_id,'type_id'=>6,'vendor_id'=>$data['vendor_id'],'other_cost'=>$data['other_cost'],'other_comments'=>$data['other_comments'],'gst_type'=>$gst_type,'delivery_date'=>$delivery_date,'category_id'=>null);
                
                $company_data = CommonHelper::getCompanyData();
                $insertArray['company_data'] = json_encode($company_data);
                
                $purchase_order = Purchase_order::create($insertArray);
                
                $purchase_order_id = $purchase_order->id;
                
                $item_list = $data['rows'];//print_r($item_list);exit;
                
                for($i=0;$i<count($item_list);$i++){
                    if(! (isset($item_list[$i]['gst_percent']) && !empty($item_list[$i]['gst_percent']))){
                        $error_msg.='GST Percent not defined for '.$item_list[$i]['item_name'].' HSN Code <br>';
                    }
                }
                
                if(!empty($error_msg)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
                
                for($i=0;$i<count($item_list);$i++){
                    
                    for($q=0;$q<count($size_list);$q++){
                        $size_id = $size_list[$q]['id'];
                        if(isset($item_list[$i]['size_'.$size_id]) && !empty($item_list[$i]['size_'.$size_id])){
                            $size_data[$size_id] = $item_list[$i]['size_'.$size_id];
                        }else{
                            $size_data[$size_id] = 0;
                        }
                    }
                    
                    $cost = round($item_list[$i]['item_rate']*$item_list[$i]['size_total'],2);
                    $gst_amount = round(($item_list[$i]['gst_percent']/100)*$cost,2);
                    $total_cost = $cost+$gst_amount;
                    
                    $insertArray = array('order_id'=>$purchase_order_id,'product_sku'=>null,'vendor_sku'=>null,'item_master_id'=>$item_list[$i]['item_id'],'size_data'=>json_encode($size_data),
                    'quotation_detail_id'=>null,'design_id'=>null,'vendor_id'=>$data['vendor_id'],'qty_ordered'=>$item_list[$i]['size_total'],'rate'=>$item_list[$i]['item_rate'],
                    'cost'=>$cost,'gst_percent'=>$item_list[$i]['gst_percent'],'gst_amount'=>$gst_amount,'total_cost'=>$total_cost,'width_id'=>null,'content_id'=>null,'gsm_id'=>null);
                    
                    $po_item = Purchase_order_items::create($insertArray);
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Accessories Purchase Order Created. PO ID: '.$purchase_order_id,'ACCESSORIES_PURCHASE_ORDER_CREATED','ACCESSORIES_PURCHASE_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Purchase Order created successfully','po_data' => $purchase_order),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function listAccessoriesPurchaseOrder(Request $request){
        try{
            $user = Auth::user();
            $data = $request->all();
            
            $purchase_orders = \DB::table('purchase_order as po')
            ->leftJoin('purchase_order_details as pod','po.id','=','pod.po_id')        
            ->Join('users as u','u.id','=','po.user_id')
            ->Join('vendor_detail as v','v.id','=','po.vendor_id')   
            ->where('type_id',6);
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $purchase_orders = $purchase_orders->where('po.vendor_id',$data['v_id']);
            }
            
            if(isset($data['po_no']) && !empty($data['po_no'])){
                $purchase_orders = $purchase_orders->where('po.order_no','LIKE','%'.$data['po_no'].'%');
            }
            
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $purchase_orders = $purchase_orders->where('pod.invoice_no','LIKE','%'.$data['invoice_no'].'%');
            }
            
            if(isset($data['po_id']) && !empty($data['po_id'])){
                $purchase_orders = $purchase_orders->where('po.id',trim($data['po_id']));
            }
                
            $purchase_orders = $purchase_orders->select('po.*','u.name as user_name','v.name as vendor_name','v.email as vendor_email',\DB::raw('count(pod.id) as invoice_count'))
            ->groupBy('po.id')->orderBy('po.id','DESC')->paginate(100);
            
            $vendors_list = Vendor_detail::where('is_deleted',0)->where('status',1)->orderBy('name')->get()->toArray();
            
            return view('purchaser/accessories_purchase_order_list',array('purchase_orders'=>$purchase_orders,'user'=>$user,'error_message'=>'','vendors_list'=>$vendors_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/accessories_purchase_order_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function accessoriesPurchaseOrderDetail(Request $request,$id){
        try{
            $size_list = Production_size_counts::where('is_deleted',0)->get()->toArray();
            $data = $request->all();
            $user = Auth::user();
            $order_id = $id;
            $error_msg = '';
            $size_arr = $size_list_updated = array();
            
            $purchase_order_data = \DB::table('purchase_order as po')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->where('po.id',$order_id)
            ->select('po.*','vd.name as vendor_name','vd.email as vendor_email','u.name as user_name')->first();        
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($purchase_order_data->gst_type);
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('accessories as acc','acc.id','=','poi.item_master_id')
            ->where('poi.order_id',$order_id)       
            //->select('poi.*','acc.accessory_name','acc.rate','acc.gst_percent')
            ->select('poi.*','acc.accessory_name')        
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
            
            return view('purchaser/accessories_purchase_order_detail',array('purchase_orders_items'=>$purchase_orders_items,'size_list'=>$size_list,'purchase_order_data'=>$purchase_order_data,
            'gst_type_percent'=>$gst_type_percent,'user'=>$user,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return view('purchaser/accessories_purchase_order_detail',array('error_message'=>$e->getMessage()));
        }
    }
    
    function accessoriesPurchaseOrderInvoice(Request $request,$id){
        try{
            $data = $request->all();
            $order_id = $id;
            $size_arr = $size_list_updated = [];
            $user_id = Auth::id(); 
            
            //$company_data = CommonHelper::getCompanyData();
            
            $purchase_order_data = \DB::table('purchase_order as po')
            ->leftJoin('design_lookup_items_master as dlim','po.category_id','=','dlim.id')
            ->where('po.id',$order_id)        
            ->select('po.*','dlim.name as category_name')->first();
            
            $company_data = json_decode($purchase_order_data->company_data,true);
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($purchase_order_data->gst_type);
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('accessories as acc','acc.id','=','poi.item_master_id')
            ->where('poi.order_id',$order_id)       
            //->select('poi.*','acc.accessory_name','acc.rate','acc.gst_percent')
            ->select('poi.*','acc.accessory_name')        
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            $vendor_data = \DB::table('vendor_detail as vd') 
            ->Join('state_list as sl','vd.state','=','sl.id')  
            ->where('vd.id',$purchase_order_data->vendor_id)        
            ->select('vd.*','sl.state_name')->first();        
            
            /*  Code to update size list start   */
            for($i=0;$i<count($purchase_orders_items);$i++){
                $size_data = (!empty($purchase_orders_items[$i]->size_data))?json_decode($purchase_orders_items[$i]->size_data,true):array();
                foreach($size_data as $size_id=>$qty){
                    if($qty > 0) $size_arr[] = $size_id;
                }
            }
            
            $size_arr = array_values(array_unique($size_arr));
            
            for($i=0;$i<count($size_list);$i++){
                if(in_array($size_list[$i]['id'],$size_arr)){
                    $size_list_updated[] = $size_list[$i];
                }
            }
            
            $size_list = $size_list_updated;
            /*  Code to update size list end   */
            
            $data = array('products_list' => array(),'company_data'=>$company_data,'po_items'=>$purchase_orders_items,
            'size_list'=>$size_list,'po_data'=>$purchase_order_data,'gst_types'=>$gst_type_percent,'vendor_data'=>$vendor_data);
            
            //return view('purchaser/accessories_purchase_order_invoice_pdf',$data);
            
            $pdf = PDF::loadView('purchaser/accessories_purchase_order_invoice_pdf', $data);

            return $pdf->download('accessories_purchase_order_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function accessoriesPurchaseOrderInvoiceList(Request $request,$id){
        try{
            $data = $request->all();
            $user_id = Auth::id(); 
            $user = Auth::user();
            $po_id = $id;
            $error_msg = '';
            
            if(isset($data['action']) && $data['action'] == 'get_pending_import_inv_count'){
                $inventory_count = Pos_product_master_inventory::where('po_id',$data['po_id'])->where('product_status',0)->where('is_deleted',0)->count();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Inventory Count','inventory_count' => $inventory_count),200);
            }
            
            $po_data = \DB::table('purchase_order as po')
            ->Join('vendor_detail as vd','vd.id','=','po.vendor_id')
            ->Join('users as u','u.id','=','po.user_id')
            ->where('po.id',$po_id)
            ->select('po.*','vd.name as vendor_name','vd.email as vendor_email','u.name as user_name')->first();        
            
            $po_invoices = \DB::table('purchase_order_details as pod')
            ->join('purchase_order as po','po.id', '=', 'pod.po_id')
            ->Join('users as u','u.id','=','pod.user_id')
            ->leftJoin('purchase_order_grn_qc as po_grn_qc_1',function($join){$join->on('po_grn_qc_1.po_detail_id','=','pod.id')->where('po_grn_qc_1.type','=','grn')->where('po_grn_qc_1.is_deleted','=','0');})                
            ->leftJoin('purchase_order_grn_qc as po_grn_qc_2',function($join){$join->on('po_grn_qc_2.po_detail_id','=','pod.id')->where('po_grn_qc_2.type','=','qc')->where('po_grn_qc_2.is_deleted','=','0');})                
            ->where('pod.po_id',$po_id)         
            ->where('pod.is_deleted',0)
            ->where('pod.status',1)
            ->select('pod.*','u.name as user_name','po_grn_qc_1.id as grn_id','po_grn_qc_2.id as qc_id')
            ->paginate(50);
            
            return view('purchaser/accessories_purchase_order_invoice_list',array('po_data'=>$po_data,'po_invoices'=>$po_invoices,'user'=>$user,'error_message'=>''));
        }catch (\Exception $e){
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                CommonHelper::saveException($e,'PURCHASE_ORDER',__FUNCTION__,__FILE__);
                return view('purchaser/accessories_purchase_order_invoice_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function importAccessoriesPurchaseOrderItems(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $invoice_id = $id;
            $error_message = '';
            
            $product_list = $sku_products_list = $po_invoice_list = array();
            
            if(isset($data['action']) && $data['action']  == 'get_po_invoice_details'){
                $vehicle_details = Purchase_order_details::where('id',$data['id'])->first();
                if(!empty($vehicle_details)){
                    $vehicle_details->images_list = json_decode($vehicle_details->images_list,true);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'vehicle details','vehicle_details'=>$vehicle_details),200);
            }
            
            $po_detail_data = Purchase_order_details::where('id',$invoice_id)->first();
            
            $grn_data = Purchase_order_grn_qc::where('po_id',$po_detail_data->po_id)->where('po_detail_id',$po_detail_data->id)->where('type','grn')->where('is_deleted',0)->where('status',1)->first();
            
            $po_data = Purchase_order::where('id',$po_detail_data->po_id)->first();
            
            $gst_type_percent = CommonHelper::getGSTTypePercent($po_data->gst_type);
            
            $purchase_orders_items = $po_items = \DB::table('purchase_order_items as poi')
            ->Join('accessories as acc','acc.id','=','poi.item_master_id')
            ->where('poi.order_id',$po_detail_data->po_id)       
            //->select('poi.*','acc.accessory_name','acc.rate','acc.gst_percent')
            ->select('poi.*','acc.accessory_name')        
            ->orderBy('poi.id','ASC')->get()->toArray();
            
            if(!empty($grn_data)){
                $sku_list = $sku_products_list = $sku_data = $product_list = array();
            }
            
            $size_list = Production_size_counts::where('is_deleted',0)->get()->toArray();
            
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
            
            return view('purchaser/accessories_purchase_order_items_import',array('error_message'=>$error_message,'po_detail_data'=>$po_detail_data,'grn_data'=>$grn_data,'purchase_orders_items'=>$purchase_orders_items,'po_data'=>$po_data,'gst_type_percent'=>$gst_type_percent,'po_invoice_list'=>$po_invoice_list,'user'=>$user,'size_list'=>$size_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('purchaser/accessories_purchase_order_items_import',array('error_message'=>$e->getMessage().', Line:'.$e->getLine(),'demands_list'=>array()));
            }
        }
    }
    
    function submitImportAccessoriesPurchaseOrderItems(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $images_list = $invoice_items = $invoice_items_total = array();
            $total_received_count = 0;
            
            if(isset($data['action']) && $data['action']  == 'add_vehicle_details'){
                $validateionRules = array('vehicle_no'=>'required','containers_count'=>'required|numeric','invoice_no'=>'required','invoice_date'=>'required');
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
                
                $insertArray = array('po_id'=>$data['po_id'],'vehicle_no'=>$data['vehicle_no'],'containers_count'=>$data['containers_count'],'images_list'=>json_encode($images_list),'comments'=>$data['comments'],
                'invoice_no'=>$data['invoice_no'],'invoice_date'=>$invoice_date,'products_count'=>null,'user_id'=>$user->id);
                
                $po_details = Purchase_order_details::create($insertArray);

                \DB::commit();
                
                CommonHelper::createLog('Accessories Purchase Order Invoice Created. PO ID: '.$data['po_id'],'ACCESSORIES_PURCHASE_ORDER_INVOICE_CREATED','ACCESSORIES_PURCHASE_ORDER');

                return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','po_details'=>$po_details,'message' => 'PO Invoice added successfully'),201);
            }
            
            if(isset($data['action']) && $data['action']  == 'add_bulk_po_grn'){
                $size_array = $po_items_array = $items_received = array();
                \DB::beginTransaction();
                
                $grn_exists = Purchase_order_grn_qc::where('po_id',$data['po_id'])->where('type','grn')->where('po_detail_id',$data['po_detail_id'])->where('is_deleted',0)->count();
                if($grn_exists > 0){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'GRN already added for Invoice', 'errors' =>'GRN already added for Invoice' ));
                }
                
                $size_list = Production_size_counts::where('is_deleted',0)->get()->toArray();
                
                //$po_items = Purchase_order_items::where('order_id',$data['po_id'])->get()->toArray();
                
                $po_items = \DB::table('purchase_order_items as poi')
                ->Join('accessories as acc','acc.id','=','poi.item_master_id')
                ->where('poi.order_id',$data['po_id'])       
                ->select('poi.*','acc.accessory_name','acc.rate','acc.gst_percent')
                ->orderBy('poi.id','ASC')->get()->toArray();
                
                $po_items = json_decode(json_encode($po_items),true);
                
                for($i=0;$i<count($size_list);$i++){
                    $size_array[$size_list[$i]['id']] = $size_list[$i];
                }
                
                for($i=0;$i<count($po_items);$i++){
                    $po_items_array[$po_items[$i]['id']] = $po_items[$i];
                }
                
                for($i=0;$i<count($po_items);$i++){
                    for($q=0;$q<count($size_list);$q++){
                        $item_id = $po_items[$i]['id'];
                        $size_id = $size_list[$q]['id'];
                        $key = $item_id.'_'.$size_id;
                        if(isset($data['qty_'.$key]) && !empty($data['qty_'.$key]) && filter_var(trim($data['qty_'.$key]), FILTER_VALIDATE_INT) === false ){
                            $item_name = $po_items_array[$item_id]['accessory_name'].' - '.$size_array[$size_id]['size'];
                            return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Invoice Items should have numeric value: '.$item_name, 'errors' =>'Invoice Items should have numeric value: '.$item_name ));
                        }
                    }
                }
                
                for($i=0;$i<count($po_items);$i++){
                    $item_total = 0;
                    $item_id = $po_items[$i]['id'];
                    for($q=0;$q<count($size_list);$q++){
                        $size_id = $size_list[$q]['id'];
                        $key = $item_id.'_'.$size_id;
                        if(isset($data['qty_'.$key]) && !empty($data['qty_'.$key])){
                            $invoice_items[$key] = trim($data['qty_'.$key]);
                            $item_total+=trim($data['qty_'.$key]);
                            
                        }
                    }
                    
                    $invoice_items_total[$item_id] = $item_total;
                    $total_received_count+=$item_total;
                }
                
                if(empty($invoice_items)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Invoice Items is Required Field', 'errors' =>'Invoice Items is Required Field' ));
                }
                
                for($i=0;$i<count($po_items);$i++){
                    $size_rec = 0;
                    $size_ordered = json_decode($po_items[$i]['size_data'],true);
                    $size_received = json_decode($po_items[$i]['size_data_received'],true);
                    for($q=0;$q<count($size_list);$q++){
                        $item_id = $po_items[$i]['id'];
                        $size_id = $size_list[$q]['id'];
                        $key = $item_id.'_'.$size_id;
                        if(isset($data['qty_'.$key]) && !empty($data['qty_'.$key])){
                            $size_rec = trim($data['qty_'.$key]);
                            $size_rec_db = isset($size_received[$size_id])?$size_received[$size_id]:0;
                            $size_ord_db = $size_ordered[$size_id];
                            $total_received = $size_rec+$size_rec_db;
                           
                            if($total_received > $size_ord_db){
                                $item_name = $po_items_array[$item_id]['accessory_name'].' - '.$size_array[$size_id]['size'];
                                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Invoice Items Quantity Received should not be more then Quantity Ordered: '.$item_name, 'errors' =>'Invoice Items Quantity Received should not be more then Quantity Ordered: '.$item_name ));
                            }
                        }
                    }
                }
                
                for($i=0;$i<count($po_items);$i++){
                    $size_rec_array = array();
                    $rec_item_total = 0;
                    $item_id = $po_items[$i]['id'];
                    $size_ordered = json_decode($po_items[$i]['size_data'],true);
                    $size_received = json_decode($po_items[$i]['size_data_received'],true);
                    for($q=0;$q<count($size_list);$q++){
                        $size_id = $size_list[$q]['id'];
                        $key = $item_id.'_'.$size_id;
                        if(isset($data['qty_'.$key]) && !empty($data['qty_'.$key])){
                            $size_rec = trim($data['qty_'.$key]);
                            $size_rec_db = isset($size_received[$size_id])?$size_received[$size_id]:0;
                            
                            $size_rec_array[$size_id] = $size_rec+$size_rec_db;
                            $items_received[$item_id][$size_id] = $size_rec;
                            $rec_item_total+=$size_rec;
                        }
                    }
                    
                    $updateArray = array('qty_received'=>$rec_item_total,'size_data_received'=>json_encode($size_rec_array));
                    Purchase_order_items::where('id',$po_items[$i]['id'])->update($updateArray);
                }
                
                
                $po_data = Purchase_order::where('id',$data['po_id'])->first();
                
                $today_latest_grn = Purchase_order_grn_qc::whereRaw("DATE(created_at) = CURDATE()")->where('type','grn')->select('grn_no')->orderBy('id','DESC')->first();
                $grn_number = (!empty($today_latest_grn))?substr($today_latest_grn->grn_no,10,3):0;
                $grn_number = 'KSGN'.Date('ymd').str_pad($grn_number+1,3,'0',STR_PAD_LEFT).'-'.str_ireplace('KSPO','',$po_data->order_no);
                
                $insertArray = array('grn_no'=>$grn_number,'po_id'=>$data['po_id'],'type'=>'GRN','comments'=>$data['add_inventory_grn_comments'],'po_detail_id'=>$data['po_detail_id']);
                $insertArray['other_data'] = json_encode(array('total'=>$total_received_count));
                $insertArray['grn_items'] = json_encode($items_received);
                $grn = Purchase_order_grn_qc::create($insertArray);
                
                for($i=0;$i<count($po_items);$i++){
                    $id = $po_items[$i]['id'];
                    if(isset($invoice_items_total[$id]) && $invoice_items_total[$id] > 0){
                        $quantity = $invoice_items_total[$id];
                        $insertArray = array('grn_qc_id'=>$grn->id,'inventory_id'=>$id,'quantity'=>$quantity,'grn_qc_date'=>date('Y/m/d H:i:s'));
                        Purchase_order_grn_qc_items::create($insertArray);
                    }
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Accessories Purchase Order GRN Created. GRN ID: '.$grn->id,'ACCESSORIES_PURCHASE_ORDER_GRN_CREATED','ACCESSORIES_PURCHASE_ORDER');
                
                return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'GRN data added successfully'),201);
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
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'PO Invoice updated successfully'),200);
            }
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function accessoriesPurchaseOrderItemsImportInvoice(Request $request,$id){
        try{
            $data = $request->all();
            $grn_id = $id;
            
            $vendor_details = \DB::table('purchase_order_grn_qc as po_grn')
            ->join('purchase_order as po','po.id', '=', 'po_grn.po_id')
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn.po_detail_id')             
            ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
            ->where('po_grn.id',$grn_id)
            ->select('vd.*','pod.invoice_no','pod.invoice_date','po_grn.id as po_grn_id','po.id as po_id','po_grn.grn_no','po_grn.created_at as grn_created_date','pod.invoice_items','company_data')->first(); 
            
            $products_list = \DB::table('purchase_order_grn_qc_items as po_qc_items')
            ->join('purchase_order_items as poi','poi.id', '=', 'po_qc_items.inventory_id')        
            ->join('accessories as acc','acc.id', '=', 'poi.item_master_id')   
            ->where('po_qc_items.grn_qc_id',$grn_id)  
            ->where('poi.order_id',$vendor_details->po_id)        
            ->where('po_qc_items.is_deleted',0)                    
            ->where('acc.is_deleted',0)
            ->selectRaw('acc.accessory_name,poi.rate,poi.gst_percent,po_qc_items.quantity')
            ->get()->toArray();//print_r($products_list);exit;
            
            //$company_data = CommonHelper::getCompanyData();
            $company_data = json_decode($vendor_details->company_data,true);
            
            $data = array('message' => 'Inventory import invoice','vendor_details'=>$vendor_details,'products_list'=>$products_list,'company_data'=>$company_data);
            
            //return view('purchaser/accessories_purchase_order_items_import_invoice',$data);
            
            $pdf = PDF::loadView('purchaser/accessories_purchase_order_items_import_invoice', $data);

            return $pdf->download('accessories_purchase_order_items_import_invoice_pdf');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage().''.$e->getLine()),500);
        }
    }
    
    function accessoriesList(Request $request){
        try{
            $data = $request->all();
            
            if(isset($data['action']) && $data['action']  == 'get_acc_data'){
                $acc_data = Accessories::where('id',$data['id'])->select('*')->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'accessory data','acc_data' => $acc_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_acc'){
                $acc_id = $data['accessory_edit_id'];
            
                $validationRules = array('acc_name_edit'=>'required','acc_rate_edit'=>'required|numeric','acc_gst_edit'=>'required|numeric');
                $attributes = array('acc_name_edit'=>'Accessory Name','acc_rate_edit'=>'Rate','acc_gst_edit'=>'GST %');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                $accessoryExists = Accessories::where('accessory_name',trim($data['acc_name_edit']))->where('id','!=',$acc_id)->where('is_deleted',0)->count();
                if(!empty($accessoryExists)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Accessory already exists with this name', 'errors' => 'Accessory already exists with this name'));
                }

                $updateArray = array('accessory_name'=>trim($data['acc_name_edit']),'rate'=>trim($data['acc_rate_edit']),'gst_percent'=>trim($data['acc_gst_edit']),'description'=>trim($data['acc_desc_edit']));

                Accessories::where('id',$acc_id)->update($updateArray);
                
                CommonHelper::createLog('Accessories Updated. ID: '.$acc_id,'ACCESSORIES_UPDATED','ACCESSORIES');
            
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Accessory updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'add_acc'){
                
                $validationRules = array('acc_name_add'=>'required','acc_rate_add'=>'required|numeric','acc_gst_add'=>'required|numeric');
                $attributes = array('acc_name_add'=>'Accessory Name','acc_rate_add'=>'Rate','acc_gst_add'=>'GST %');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                $accessoryExists = Accessories::where('accessory_name',trim($data['acc_name_add']))->where('is_deleted',0)->first();
                if(!empty($accessoryExists)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Accessory already exists with this name', 'errors' => 'Accessory already exists with this name'));
                }

                $insertArray = array('accessory_name'=>trim($data['acc_name_add']),'rate'=>trim($data['acc_rate_add']),'gst_percent'=>trim($data['acc_gst_add']),'description'=>trim($data['acc_desc_add']));

                $acc = Accessories::create($insertArray);
                
                CommonHelper::createLog('Accessories Created. ID: '.$acc->id,'ACCESSORIES_CREATED','ACCESSORIES');
            
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Accessory added successfully'),200);
            }
            
            $accessories_list = \DB::table('accessories')->where('is_deleted',0);
            
            if(isset($data['acc_name']) && !empty($data['acc_name'])){
                $accessories_list = $accessories_list->where('accessory_name','LIKE','%'.$data['acc_name'].'%');
            }
            
            if(isset($data['acc_id']) && !empty($data['acc_id'])){
                $accessories_list = $accessories_list->where('id',$data['acc_id']);
            }
            
            $accessories_list = $accessories_list->orderBy('id')->paginate(100);
            
            return view('purchaser/accessories_list',array('accessories_list'=>$accessories_list,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'ACCESSORIES',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('purchaser/accessories_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    function vendorAccessoriesList(Request $request){
        try{
            $data = $request->all();
            
            if(isset($data['action']) && $data['action']  == 'get_vendor_po_list'){
                $po_list = Purchase_order::where('vendor_id',$data['v_id'])->where('is_deleted',0)->get()->toArray();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'accessory data','po_list' => $po_list),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_vend_acc_data'){
                $vend_acc_data = Vendor_accessories::where('id',$data['id'])->select('*')->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'accessory data','vend_acc_data' => $vend_acc_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_vend_acc'){
                $vend_acc_id = $data['vendor_acc_edit_id'];
            
                $validationRules = array('vendor_id_edit'=>'required','po_id_edit'=>'required','acc_id_edit'=>'required','date_provided_edit'=>'required','quantity_edit'=>'required|numeric');
                $attributes = array('vendor_id_edit'=>'Vendor Name','po_id_edit'=>'Purchase Order','acc_id_edit'=>'Accessory','date_provided_edit'=>'Date Provided','quantity_edit'=>'Quantity');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                $vendorAccessoryExists = Vendor_accessories::where('id','!=',$vend_acc_id)->where('vendor_id',trim($data['vendor_id_edit']))->where('po_id',trim($data['po_id_edit']))->where('accessory_id',trim($data['acc_id_edit']))->where('is_deleted',0)->first();
                if(!empty($vendorAccessoryExists)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Vendor Accessory already added for this Purchase Order', 'errors' => 'Vendor Accessory already added for this Purchase Order'));
                }
                
                $date_provided = str_replace('-','/',$data['date_provided_edit']);
                $date_provided_arr = explode('/',$date_provided);
                $date_provided = $date_provided_arr[2].'/'.$date_provided_arr[1].'/'.$date_provided_arr[0];

                $updateArray = array('vendor_id'=>trim($data['vendor_id_edit']),'accessory_id'=>trim($data['acc_id_edit']),'po_id'=>trim($data['po_id_edit']),'date_provided'=>$date_provided,'quantity'=>trim($data['quantity_edit']));

                Vendor_accessories::where('id',$vend_acc_id)->update($updateArray);
                
                CommonHelper::createLog('Vendor Accessories Updated. Vendor ID: '.$data['vendor_id_edit'],'VENDOR_ACCESSORIES_UPDATED','ACCESSORIES');
            
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Vendor Accessory updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'add_vend_acc'){
                $validationRules = array('vendor_id_add'=>'required','po_id_add'=>'required','acc_id_add'=>'required','date_provided_add'=>'required','quantity_add'=>'required|numeric');
                $attributes = array('vendor_id_add'=>'Vendor Name','po_id_add'=>'Purchase Order','acc_id_add'=>'Accessory','date_provided_add'=>'Date Provided','quantity_add'=>'Quantity');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	

                $vendorAccessoryExists = Vendor_accessories::where('vendor_id',trim($data['vendor_id_add']))->where('po_id',trim($data['po_id_add']))->where('accessory_id',trim($data['acc_id_add']))->where('is_deleted',0)->first();
                if(!empty($accessoryExists)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Vendor Accessory already added for this Purchase Order', 'errors' => 'Vendor Accessory already added for this Purchase Order'));
                }
                
                $date_provided = str_replace('-','/',$data['date_provided_add']);
                $date_provided_arr = explode('/',$date_provided);
                $date_provided = $date_provided_arr[2].'/'.$date_provided_arr[1].'/'.$date_provided_arr[0];

                $insertArray = array('vendor_id'=>trim($data['vendor_id_add']),'accessory_id'=>trim($data['acc_id_add']),'po_id'=>trim($data['po_id_add']),'date_provided'=>$date_provided,'quantity'=>trim($data['quantity_add']));

                Vendor_accessories::create($insertArray);
                
                CommonHelper::createLog('Vendor Accessories Created. Vendor ID: '.$data['vendor_id_add'],'VENDOR_ACCESSORIES_CREATED','ACCESSORIES');
            
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Vendor Accessory added successfully'),200);
            }
            
            $vendor_acc_list = \DB::table('vendor_accessories as va')
            ->join('accessories as acc','va.accessory_id', '=', 'acc.id')        
            ->join('vendor_detail as vd','va.vendor_id', '=', 'vd.id')   
            ->join('purchase_order as po','va.po_id', '=', 'po.id')           
            ->where('va.is_deleted',0)                    
            ->where('acc.is_deleted',0)
            ->select('va.*','acc.accessory_name','vd.name as vendor_name','po.order_no as po_no');
            
            if(isset($data['acc_name']) && !empty($data['acc_name'])){
                $vendor_acc_list = $vendor_acc_list->where('acc.accessory_name','LIKE','%'.$data['acc_name'].'%');
            }
            
            if(isset($data['acc_id']) && !empty($data['acc_id'])){
                $vendor_acc_list = $vendor_acc_list->where('va.id',$data['acc_id']);
            }
            
            $vendor_acc_list = $vendor_acc_list->paginate(100);
            
            $vendor_list = CommonHelper::getVendorsList();
            $accessory_list = Accessories::where('is_deleted',0)->orderBy('accessory_name')->get()->toArray();
            
            return view('purchaser/vendor_accessories_list',array('vendor_acc_list'=>$vendor_acc_list,'error_message'=>'','vendor_list'=>$vendor_list,'accessory_list'=>$accessory_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'ACCESSORIES',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('purchaser/vendor_accessories_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    
}
