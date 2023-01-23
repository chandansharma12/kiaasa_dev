<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Design_quotation; 
use App\Models\Vendor_quotation; 
use App\Models\Design_lookup_items_master;
use App\Models\Vendor_detail;
use App\Models\Material_vendor;
use App\Models\Design_item_master;
use App\Models\Design_items_instance;
use App\Models\Unit;
use App\Models\Pos_product_master_inventory;
use App\Models\Pos_inventory_payments;
use App\Models\Pos_inventory_payments_detail;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class VendorController extends Controller
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
            return view('vendor/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return view('vendor/dashboard',array('error_message' =>$e->getMessage()));
        }
    }
    
    function listing(Request $request){
        try{
            $data = $request->all();
            $users_list = \DB::table('vendor_detail as v')
            ->leftJoin('state_list as s','s.id', '=', 'v.state')        
            ->leftJoin('vendor_detail as v1','v1.id', '=', 'v.pid')                
            ->where('v.is_deleted',0);
            
            if(isset($data['u_name']) && !empty($data['u_name'])){
                $name_email = trim($data['u_name']);
                $users_list = $users_list->whereRaw("(name like '%{$name_email}%' OR email = '{$name_email}')");
            }
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $users_list = $users_list->where('v.id',trim($data['v_id']));
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'v.id','name'=>'v.name','email'=>'v.email','phone'=>'v.phone','address'=>'v.address','status'=>'v.status','created'=>'v.created_at','updated'=>'v.updated_at');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'v.id';
                $sort_order = CommonHelper::getSortingOrder();
                $users_list = $users_list->orderBy($sort_by,$sort_order);
            }
           
            $users_list = $users_list->select('v.*','s.state_name','v1.name as main_vendor_name');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $users_list = $users_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $users_list = $users_list->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=vendor_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Vendor ID','Name','Email','Phone','Address','City','State','Postal Code','GST No','Vendor Code','Ecommerce Status','Created On');

                $callback = function() use ($users_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($users_list);$i++){
                        $ecomm_status = ($users_list[$i]->ecommerce_status)?'Enabled':'Disabled';
                        
                        $array = array($users_list[$i]->id,$users_list[$i]->name,$users_list[$i]->email,$users_list[$i]->phone,CommonHelper::filterCsvData($users_list[$i]->address),
                        $users_list[$i]->city,$users_list[$i]->state_name,$users_list[$i]->postal_code,$users_list[$i]->gst_no,$users_list[$i]->vendor_code,$ecomm_status,date('d-m-Y',strtotime($users_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $states_list = \DB::table('state_list')->where('is_deleted',0)->where('status',1)->get()->toArray();;
            
            return view('admin/vendors_list',array('users_list'=>$users_list,'states_list'=>$states_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return view('admin/vendors_list',array('error_message'=>$e->getMessage(),'users_list'=>array()));
        }
    }
    
    function data(Request $request,$id){
        try{
            $data = $request->all();
            $user_data = \DB::table('vendor_detail as v')->where('v.id',$id)->select('v.*')->first();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Users data','user_data' => $user_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function update(Request $request){
        try{
            
            $data = $request->all();
            $user_id = $data['user_edit_id'];
            
            $validateionRules = array('userName_edit'=>'required','userEmail_edit'=>'required|email|unique:users,email,'.$user_id,'ecommerceStatus_edit'=>'required','userCity_edit'=>'required',
            'userState_edit'=>'required','userPotalCode_edit'=>'required','userAddress_edit'=>'required','userGstNo_edit'=>'required','vendorCode_edit'=>'required','vendorCode_edit'=>'required|unique:vendor_detail,vendor_code,'.$user_id);
            
            unset($validateionRules['userName_edit']);
            unset($validateionRules['userEmail_edit']);
            
            $attributes = array('userName_edit'=>'Name','userEmail_edit'=>'Email','ecommerceStatus_edit'=>'Ecommerce Status','userCity_edit'=>'City',
            'userState_edit'=>'State','userPotalCode_edit'=>'Potal Code','userAddress_edit'=>'Address','userGstNo_edit'=>'GST No','vendorCode_edit'=>'Vendor Code');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $updateArray = array('name'=>$data['userName_edit'],'email'=>$data['userEmail_edit'],'phone'=>$data['userPhone_edit'],'address'=>$data['userAddress_edit'],
            'ecommerce_status'=>$data['ecommerceStatus_edit'],'city'=>$data['userCity_edit'],'state'=>$data['userState_edit'],'postal_code'=>$data['userPotalCode_edit'],
            'gst_no'=>$data['userGstNo_edit'],'vendor_code'=>$data['vendorCode_edit']);
            
            unset($updateArray['name']);
            unset($updateArray['email']);
           
            Vendor_detail::where('id', '=', $user_id)->update($updateArray);
            
            CommonHelper::createLog('Vendor Updated. ID: '.$user_id,'VENDOR_UPDATED','VENDOR');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Vendor updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function add(Request $request){
        try{
            
            $data = $request->all();
            
            $validateionRules = array('userName_add'=>'required','userEmail_add'=>'required|email|unique:users,email','userStatus_add'=>'required','userCity_add'=>'required',
            'userState_add'=>'required','userPotalCode_add'=>'required','userAddress_add'=>'required','userGstNo_add'=>'required');
            
            $attributes = array('userName_add'=>'Name','userEmail_add'=>'Email','userStatus_add'=>'Status','userCity_add'=>'City',
            'userState_add'=>'State','userPotalCode_add'=>'Potal Code','userAddress_add'=>'Address','userGstNo_add'=>'GST No');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $insertArray = array('name'=>$data['userName_add'],'email'=>$data['userEmail_add'],'phone'=>$data['userPhone_add'],'address'=>$data['userAddress_add'],
            'status'=>$data['userStatus_add'],'city'=>$data['userCity_add'],'state'=>$data['userState_add'],'postal_code'=>$data['userPotalCode_add'],'gst_no'=>$data['userGstNo_add']);
           
            $vendor = Vendor_detail::create($insertArray);
            
            CommonHelper::createLog('Vendor Created. ID: '.$vendor->id,'VENDOR_ADDED','VENDOR');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Vendor added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
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
    
    function vendorSalesReport(Request $request,$vendorId=''){
        try{
            $data = $request->all();
            $user = Auth::user();
            $vendors_list = $products_list = $vendor_data = $vendors = $vendors_list_inv_transit = $vendors_list_demand_balance = $vendors_list_inv_warehouse = $total_data = array();
            $vendor_info = [];
            $products_count = $report_type = '';
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            
            if(empty($vendorId)){
                $report_type = 'vendor_list';
            }else{
                $report_type = (isset($data['report_type']) && !empty($data['report_type']))?$data['report_type']:'sku_list';
                $vendor_data = Vendor_detail::where('id',$vendorId)->first();
            }
                    
            if($report_type == 'sku_list'){
                // SQL JOIN 'pcod.inventory_id','=','ppmi.id' and 'pcod.order_id','=','ppmi.customer_order_id' ensures 
                // that inventory current order id of customer_order_id record of inventory is same as order_id in order detail table  
                //and product status of 5 ensures that product is currently sold and is not returned to store
                // Total number of sold units keeps changing with above join and where condition as when product is returned, product status is 4 or order id is different
                // Without these join and condition, total units remains same as it picks data directly from order detail table and total remains same
                
                $products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                //->join('pos_customer_orders_detail as pcod',function($join){$join->on('pcod.inventory_id','=','ppmi.id')->on('pcod.order_id','=','ppmi.customer_order_id');})               
                ->join('pos_customer_orders_detail as pcod','ppmi.id','=','pcod.inventory_id')        
                ->join('store as s','s.id', '=', 'pcod.store_id')        
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')          
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')       
                ->leftJoin('pos_product_images as ppi',function($join){$join->on('ppi.product_id','=','ppm.id')->where('ppi.image_type','=','front')->where('ppi.is_deleted','=',0);})           
                ->where('po.vendor_id',$vendorId)
                //->where('ppmi.product_status',5)
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)
                ->where('pcod.is_deleted',0)        
                ->where('s.is_deleted',0)
                ->where('pco.is_deleted',0)       
                ->where('pco.order_status',1)       
                ->where('ppmi.fake_inventory',0)        
                ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'");
                
                if(isset($data['po_cat_id']) && !empty($data['po_cat_id'])){
                    $products_list = $products_list->where('po.category_id',$data['po_cat_id']);
                }
                
                $products_list = $products_list->groupBy('ppm.product_sku')        
                ->select('ppm.id as product_id','ppm.product_name','ppm.product_sku','ppm.vendor_product_sku','dlim_1.name as color_name','ppmi.vendor_base_price','ppmi.vendor_gst_percent',
                'ppmi.vendor_gst_amount','ppmi.base_price','ppmi.sale_price','ppi.image_name',\DB::raw('SUM(pcod.product_quantity) as inv_count'),\DB::raw('SUM(pcod.net_price) as total_net_price'),
                \DB::raw('SUM(pcod.net_price-pcod.gst_amount) as total_taxable_amount'),\DB::raw('SUM(pcod.gst_amount) as total_gst_amount'))
                ->orderBy('inv_count','DESC');
                
                if(isset($data['action']) && $data['action'] == 'download_csv_sku_list'){
                    $products_list = $products_list->get()->toArray();
                }else{
                    $products_list = $products_list->paginate(100);
                }
                
                // Empty variables if user logged is other vendor than in url and not is sub vendor
                if($user->user_type == 15 ){
                    $vendor_info = Vendor_detail::where('user_id',$user->id)->where('is_deleted',0)->first();
                    $sub_vendor_list = Vendor_detail::where('pid',$vendor_info->id)->get()->toArray();
                    $sub_vendor_ids = array_column($sub_vendor_list,'id');
                    if((!in_array($vendor_data->id,$sub_vendor_ids)) && $user->id != $vendor_data->user_id){
                        $products_list = array();
                    }
                }
                
                if(isset($data['action']) && $data['action'] == 'download_csv_sku_list'){
                    $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=vendor_sales_sku_list_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                    $columns = array('Product','SKU','Vendor SKU','Cost Rate','GST Amount','Cost Price','MRP','Sold Units','Total Taxable Amt','Total GST Amt','Total Net Price');

                    $callback = function() use ($products_list, $columns){
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);
                        
                        $total_data = array('inv_count'=>0,'total_taxable_amount'=>0,'total_gst_amount'=>0,'total_net_price'=>0); 
                        for($i=0;$i<count($products_list);$i++){
                            $array = array($products_list[$i]->product_name.' '.$products_list[$i]->color_name,$products_list[$i]->product_sku,$products_list[$i]->vendor_product_sku,
                            $products_list[$i]->vendor_base_price,$products_list[$i]->vendor_gst_amount,$products_list[$i]->base_price,$products_list[$i]->sale_price,$products_list[$i]->inv_count,
                            round($products_list[$i]->total_taxable_amount,2),round($products_list[$i]->total_gst_amount,2),round($products_list[$i]->total_net_price,2));

                            fputcsv($file, $array);
                            
                            $total_data['inv_count']+=$products_list[$i]->inv_count;
                            $total_data['total_taxable_amount']+=$products_list[$i]->total_taxable_amount;
                            $total_data['total_gst_amount']+=$products_list[$i]->total_gst_amount; 
                            $total_data['total_net_price']+=$products_list[$i]->total_net_price;
                        }
                        
                        $array = ['Total','','','','','','',$total_data['inv_count'],round($total_data['total_taxable_amount'],2),round($total_data['total_gst_amount'],2),round($total_data['total_net_price'],2),''];
                        fputcsv($file, $array);
                        
                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }
                
                $responseData = array('report_type'=>$report_type,'products_list'=>$products_list,'products_count'=>[],'vendor_data'=>$vendor_data);
            }

            // It is list of inventory which is sold in the order of order detail row but not in the other order. Sql pcod.order_id = ppmi.customer_order_id
            // It check product if is currently have status of sold in inventory by product_status = 5
            // SQL JOIN 'pcod.inventory_id','=','ppmi.id' and 'pcod.order_id','=','ppmi.customer_order_id' ensures 
            // that inventory current order id of customer_order_id record of inventory is same as order_id in order detail table  
            // and product status of 5 ensures that product is currently sold and is not returned to store
            // Total number of sold units keeps changing with above join and where condition as when product is returned, product status is 4 or order id is different

            if($report_type == 'sku_detail'){
                
                $products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                //->join('pos_customer_orders_detail as pcod',function($join){$join->on('pcod.inventory_id','=','ppmi.id')->on('pcod.order_id','=','ppmi.customer_order_id');})               
                ->join('pos_customer_orders_detail as pcod','ppmi.id','=','pcod.inventory_id')         
                ->join('store as s','s.id', '=', 'pcod.store_id')        
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')          
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id') 
                ->leftJoin('pos_product_images as ppi',function($join){$join->on('ppi.product_id','=','ppm.id')->where('ppi.image_type','=','front')->where('ppi.is_deleted','=',0);})                   
                ->where('po.vendor_id',$vendorId)
                //->where('ppmi.product_status',5)
                ->where('ppmi.is_deleted',0)
                ->where('ppm.is_deleted',0)
                ->where('pcod.is_deleted',0)   
                ->where('s.is_deleted',0)        
                ->where('pco.is_deleted',0)       
                ->where('pco.order_status',1)       
                ->where('pcod.order_status',1)           
                ->where('ppmi.fake_inventory',0)        
                ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'");
                
                if(isset($data['po_cat_id']) && !empty($data['po_cat_id'])){
                    $products_list = $products_list->where('po.category_id',$data['po_cat_id']);
                }
                
                $products_list_total = clone $products_list;
                
                $total_data = ['sale'=>0,'return'=>0];
                $products_list_total = $products_list_total->groupBy('pcod.product_quantity')
                ->selectRaw('pcod.product_quantity,COUNT(pcod.id) as cnt')->get();        
                
                for($i=0;$i<count($products_list_total);$i++){
                    if($products_list_total[$i]->product_quantity == 1){
                        $total_data['sale'] = $products_list_total[$i]->cnt;
                    }elseif($products_list_total[$i]->product_quantity == -1){
                        $total_data['return'] = $products_list_total[$i]->cnt;
                    }
                }
                
                // Apply sale or return filter after getting total records data
                if(isset($data['rec_type']) && !empty($data['rec_type'])){
                    $prod_quantity = ($data['rec_type'] == 'sale')?1:-1;
                    $products_list = $products_list->where('pcod.product_quantity',$prod_quantity);
                }
                
                $products_list = $products_list->selectRaw('ppmi.id as inventory_id,ppmi.peice_barcode,ppmi.payment_status,ppm.id as product_id,ppm.product_name,ppm.product_sku,ppm.vendor_product_sku,pcod.sale_price,
                pcod.net_price,pcod.discount_percent,pcod.discount_amount,pcod.gst_percent,pcod.gst_amount,pcod.product_quantity,
                pcod.gst_inclusive,s.store_name,pcod.order_id, pcod.created_at AS order_date,dlim_1.name as color_name,s.store_id_code,
                psc.size as size_name,pco.order_no,ppmi.vendor_base_price,ppmi.vendor_gst_percent,ppmi.vendor_gst_amount,ppmi.base_price,ppi.image_name')
                ->orderByRaw('ppm.product_sku, order_date');
                
                if(isset($data['action']) && $data['action'] == 'download_csv_sku_detail'){
                    $products_list = $products_list->get()->toArray();
                }else{
                    $products_list = $products_list->paginate(100);
                }
                
                // Empty variables if user logged is other vendor than in url and not is sub vendor
                if($user->user_type == 15 ){
                    $vendor_info = Vendor_detail::where('user_id',$user->id)->where('is_deleted',0)->first();
                    $sub_vendor_list = Vendor_detail::where('pid',$vendor_info->id)->get()->toArray();
                    $sub_vendor_ids = array_column($sub_vendor_list,'id');
                    
                    if((!in_array($vendor_data->id,$sub_vendor_ids)) && $user->id != $vendor_data->user_id){
                        $products_list = array();
                    }
                }
                
                if(isset($data['action']) && $data['action'] == 'download_csv_sku_detail'){
                    $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=vendor_sales_sku_detail_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');

                    $columns = array('Type','Product Name','SKU','Vendor SKU','MRP','Discount %','Discount Amount','GST %','GST Amount','GST Inclusive',
                    'Net Price','Cost Rate','Cost GST %','Cost GST Amount','Cost Price','Store Name','Store Code','Order Date','Order No','Size','Color');

                    $callback = function() use ($products_list, $columns){
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);

                        for($i=0;$i<count($products_list);$i++){
                            $type = $products_list[$i]->product_quantity == 1?'Sale':'Return';
                            $minus = $products_list[$i]->product_quantity == 1?'':'-';
                            
                            $array = array($type,$products_list[$i]->product_name,$products_list[$i]->product_sku,$products_list[$i]->vendor_product_sku,$products_list[$i]->sale_price,
                            $products_list[$i]->discount_percent,$products_list[$i]->discount_amount,$products_list[$i]->gst_percent,$products_list[$i]->gst_amount,
                            $products_list[$i]->gst_inclusive,$products_list[$i]->net_price,$minus.$products_list[$i]->vendor_base_price,$minus.$products_list[$i]->vendor_gst_percent,
                            $minus.$products_list[$i]->vendor_gst_amount,$minus.$products_list[$i]->base_price,$products_list[$i]->store_name,$products_list[$i]->store_id_code,
                            date('d/m/Y H:i',strtotime($products_list[$i]->order_date)),$products_list[$i]->order_no,$products_list[$i]->size_name,$products_list[$i]->color_name);

                            fputcsv($file, $array);
                        }

                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }
                
                $responseData = array('report_type'=>$report_type,'products_list'=>$products_list,'vendor_data'=>$vendor_data,'total_data'=>$total_data,'user'=>$user);
            }
            
            if($report_type == 'store_sku_list'){
                $sku = trim($data['sku']);
                $product_info = \DB::table('pos_product_master as ppm')
                ->leftJoin('pos_product_images as ppi',function($join){$join->on('ppi.product_id','=','ppm.id')->where('ppi.image_type','=','front')->where('ppi.is_deleted','=',0);})        
                ->where('product_sku',$sku)->select('ppm.*','ppi.image_name','ppm.id as product_id')->first();
                        
                $products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('store as s','s.id', '=', 'ppmi.store_id')        
                ->join('pos_customer_orders_detail as pcod',function($join){$join->on('pcod.inventory_id','=','ppmi.id')->on('pcod.order_id','=','ppmi.customer_order_id');})             
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
                ->where('ppm.product_sku',$sku)
                ->where('ppmi.product_status',5)
                ->where('ppmi.is_deleted',0)
                ->where('pcod.is_deleted',0)             
                ->where('pcod.order_status',1)     
                ->where('ppmi.fake_inventory',0)        
                ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")
                ->groupByRaw('ppm.id,s.id')        
                ->select('ppm.id as product_id','ppm.product_name','ppm.product_sku','ppm.size_id','dlim_1.name as color_name',
                'psc.size as size_name','s.id as store_id','s.store_name','s.store_id_code',\DB::raw('COUNT(ppmi.id) as inv_count'))
                ->orderBy('inv_count','DESC')
                ->get()->toArray();
                
                $products_list = (!empty($products_list))?json_decode(json_encode($products_list),true):array();
                
                $products = $size_list = array();
                for($i=0;$i<count($products_list);$i++){
                    $product_data = $products_list[$i];
                    $size_id = $product_data['size_id'];
                    
                    $index = array_search($product_data['store_id'],array_column($products,'store_id'));

                    if($index !== false){
                        $products[$index]['total']+=$product_data['inv_count'];
                        if(isset($products[$index][$size_id])){
                            $products[$index][$size_id]+=$product_data['inv_count'];
                        }else{
                            $products[$index][$size_id] = $product_data['inv_count'];
                        }
                    }else{
                        $product_data['total'] = $product_data[$size_id] = $product_data['inv_count'];
                        $products[] = $product_data;
                    }
                    
                    $size_list[] = $size_id;
                }
                
                $products_list = $products;
                
                $totals = array_column($products_list,'total');
                array_multisort($totals, SORT_DESC, $products_list);
                $size_list = \DB::table('production_size_counts')->wherein('id',$size_list)->get()->toArray();
                
                // Empty variables if user logged is other vendor than in url and is not sub vendor
                if($user->user_type == 15 ){
                    $vendor_info = Vendor_detail::where('user_id',$user->id)->where('is_deleted',0)->first();
                    $sub_vendor_list = Vendor_detail::where('pid',$vendor_info->id)->get()->toArray();
                    $sub_vendor_ids = array_column($sub_vendor_list,'id');
                    
                    if((!in_array($vendor_data->id,$sub_vendor_ids)) && $user->id != $vendor_data->user_id){
                        $products_list = $size_list = array();
                    }
                }
                
                $responseData = array('report_type'=>$report_type,'products_list'=>$products_list,'vendor_data'=>$vendor_data,'size_list'=>$size_list,'product_info'=>$product_info);
            }

            if($report_type == 'vendor_list'){
                //$demand_statuses = CommonHelper::getDispatchedPushDemandStatusList();
                $category_where = (isset($data['po_cat_id']) && !empty($data['po_cat_id']))?'po.category_id = '.trim($data['po_cat_id']):'po.category_id > 0 ';
                $demand_statuses = ['store_loading','store_loaded'];
                $demand_wh_to_store_total_1 = $demand_wh_to_store_total_2 = $sub_vendor_list = array();
                $demand_wh_to_store_1 = $demand_wh_to_store_2 = array();
                $vendors_list_demand_returned_total_1 = $vendors_list_demand_returned_total_2 = array();
                $vendors_list_demand_returned_1 = $vendors_list_demand_returned_2 = $vendors_list_inv_wh_in_demand_inv = array();
                $vendors_list_demand_returned_comp_total_1 = $vendors_list_demand_returned_comp_total_2 = array();
                $vendors_list_demand_returned_comp_1 = $vendors_list_demand_returned_comp_2 = array();
                $diff = $demand_wh_to_store_total_transit_1 = $vendor_to_wh_list = $demand_wh_to_vendor_list = array();
                
                /*** Inventory transferred from warehouse to store start ***/
                
                $demand_wh_to_store = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')             
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
                ->where('spd.demand_type','inventory_push')        
                ->wherein('spd.demand_status',['warehouse_dispatched','store_loading','store_loaded'])        
                ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")     
                ->whereRaw($category_where)        
                ->where('ppmi.is_deleted',0)
                ->where('spdi.is_deleted',0)   
                ->where('spdi.transfer_status',1)        
                ->where('spd.is_deleted',0)
                ->where('ppmi.fake_inventory',0)        
                ->groupByRaw('po.vendor_id,spdi.receive_status')        
                ->select('po.vendor_id','spdi.receive_status',\DB::raw('COUNT(ppmi.id) as inv_count_pushed'))
                ->orderBy('inv_count_pushed','DESC');
                
                $demand_wh_to_store_obj = clone ($demand_wh_to_store);
                
                //Units pushed to store with date filter
                $demand_wh_to_store = $demand_wh_to_store->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'")->get()->toArray();
               
                //Total Units pushed to store without date filter
                $demand_wh_to_store_total = $demand_wh_to_store_obj->get()->toArray();
                
                // Separate received and in transit wh to store without date filter. Total data 
                for($i=0;$i<count($demand_wh_to_store_total);$i++){
                    if($demand_wh_to_store_total[$i]->receive_status == 1){
                        $demand_wh_to_store_total_1[] = $demand_wh_to_store_total[$i];
                    }else{
                        $demand_wh_to_store_total_2[] = $demand_wh_to_store_total[$i];
                    }
                }
                
                $demand_wh_to_store_total = $demand_wh_to_store_total_1;
                $demand_wh_to_store_total_transit = $demand_wh_to_store_total_2;
                
                // Separate received and in transit wh to store with date filter 
                for($i=0;$i<count($demand_wh_to_store);$i++){
                    if($demand_wh_to_store[$i]->receive_status == 1){
                        $demand_wh_to_store_1[] = $demand_wh_to_store[$i];
                    }else{
                        $demand_wh_to_store_2[] = $demand_wh_to_store[$i];
                    }
                }
                
                $demand_wh_to_store = $demand_wh_to_store_1;
                $demand_wh_to_store_transit = $demand_wh_to_store_2;
                
                /*** Inventory transferred from warehouse to store end ***/
                
                // Units returned to warehouse from store
                $vendors_list_demand_returned = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')             
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
                ->where('spd.demand_type','inventory_return_to_warehouse')        
                ->wherein('spd.demand_status',['warehouse_dispatched','warehouse_loading','warehouse_loaded'])        
                ->whereRaw($category_where)        
                ->where('ppmi.is_deleted',0)
                ->where('spdi.is_deleted',0)    
                ->where('spdi.transfer_status',1)            
                ->where('spd.is_deleted',0)
                ->where('ppmi.fake_inventory',0)        
                ->groupByRaw('po.vendor_id,spdi.receive_status')        
                ->select('po.vendor_id','spdi.receive_status',\DB::raw('COUNT(ppmi.id) as inv_count_returned'));
                
                $vendors_list_demand_returned_obj = clone ($vendors_list_demand_returned);
                
                $vendors_list_demand_returned = $vendors_list_demand_returned->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");
                $vendors_list_demand_returned = $vendors_list_demand_returned->get()->toArray();
                
                //Total Units pushed to store without date filter
                $vendors_list_demand_returned_total = $vendors_list_demand_returned_obj->get()->toArray();//print_r($vendors_list_demand_returned_total);
                
                for($i=0;$i<count($vendors_list_demand_returned_total);$i++){
                    if($vendors_list_demand_returned_total[$i]->receive_status == 1){
                        $vendors_list_demand_returned_total_1[] = $vendors_list_demand_returned_total[$i];
                    }else{
                        $vendors_list_demand_returned_total_2[] = $vendors_list_demand_returned_total[$i];
                    }
                }
                
                $vendors_list_demand_returned_total = $vendors_list_demand_returned_total_1;
                $vendors_list_demand_returned_total_transit_store_to_wh = $vendors_list_demand_returned_total_2;
                
                for($i=0;$i<count($vendors_list_demand_returned);$i++){
                    if($vendors_list_demand_returned[$i]->receive_status == 1){
                        $vendors_list_demand_returned_1[] = $vendors_list_demand_returned[$i];
                    }else{
                        $vendors_list_demand_returned_2[] = $vendors_list_demand_returned[$i];
                    }
                }
                
                $vendors_list_demand_returned = $vendors_list_demand_returned_1;
                $vendors_list_demand_returned_transit_store_to_wh = $vendors_list_demand_returned_2;
                
                // Units returned to warehouse from store by complete inventory return, but not returned to store
                $vendors_list_demand_returned_comp = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')             
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
                ->where('spd.demand_type','inventory_return_complete')        
                ->wherein('spd.demand_status',['warehouse_dispatched'])    
                ->whereRaw($category_where)        
                ->where('ppmi.is_deleted',0)
                ->where('spdi.is_deleted',0)    
                ->where('spdi.transfer_status',1)        
                ->where('spd.is_deleted',0) 
                ->where('ppmi.fake_inventory',0)        
                ->groupByRaw('po.vendor_id,spdi.receive_status')        
                ->select('po.vendor_id','spdi.receive_status',\DB::raw('COUNT(ppmi.id) as inv_count_returned'));
                
                $vendors_list_demand_returned_comp_obj = clone ($vendors_list_demand_returned_comp);
                
                $vendors_list_demand_returned_comp = $vendors_list_demand_returned_comp->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");
                $vendors_list_demand_returned_comp = $vendors_list_demand_returned_comp->get()->toArray();
                
                //Total Units pushed to store without date filter
                $vendors_list_demand_returned_comp_total = $vendors_list_demand_returned_comp_obj->get()->toArray();
                
                for($i=0;$i<count($vendors_list_demand_returned_comp_total);$i++){
                    if($vendors_list_demand_returned_comp_total[$i]->receive_status == 1){
                        $vendors_list_demand_returned_comp_total_1[] = $vendors_list_demand_returned_comp_total[$i];
                    }else{
                        $vendors_list_demand_returned_comp_total_2[] = $vendors_list_demand_returned_comp_total[$i];
                    }
                }
                
                $vendors_list_demand_returned_comp_total = $vendors_list_demand_returned_comp_total_1;
                $vendors_list_demand_returned_comp_total_transit_store_to_wh = $vendors_list_demand_returned_comp_total_2;
                
                for($i=0;$i<count($vendors_list_demand_returned_comp);$i++){
                    if($vendors_list_demand_returned_comp[$i]->receive_status == 1){
                        $vendors_list_demand_returned_comp_1[] = $vendors_list_demand_returned_comp[$i];
                    }else{
                        $vendors_list_demand_returned_comp_2[] = $vendors_list_demand_returned_comp[$i];
                    }
                }
                
                $vendors_list_demand_returned_comp = $vendors_list_demand_returned_comp_1;
                $vendors_list_demand_returned_comp_transit_store_to_wh = $vendors_list_demand_returned_comp_2;
                
                // Inventory sold from store in dates
                $vendors_list_orders = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_product_master_inventory as ppmi','pcod.inventory_id', '=', 'ppmi.id')           
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')           
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')
                ->whereRaw($category_where)         
                ->where('ppmi.is_deleted',0)
                ->where('pco.is_deleted',0)        
                ->where('pcod.is_deleted',0)
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)        
                ->where('ppmi.fake_inventory',0)        
                ->groupByRaw('po.vendor_id')        
                ->select('po.vendor_id',\DB::raw('SUM(pcod.product_quantity) as inv_count_sold'),
                \DB::raw('SUM(pcod.net_price) as total_net_price'));
                
                $vendors_list_orders_obj = clone ($vendors_list_orders);
        
                $vendors_list_orders = $vendors_list_orders->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'");
                $vendors_list_orders = $vendors_list_orders->get()->toArray();
                
                $vendors_list_orders_total = $vendors_list_orders_obj->get()->toArray();
                
                // Inventory status data
                $vendors_list_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->wherein('ppmi.product_status',[1,2,3,4])     
                ->whereRaw($category_where)         
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.qc_status',1)     
                ->where('ppmi.fake_inventory',0)        
                ->groupByRaw('po.vendor_id,ppmi.product_status')        
                ->select('po.vendor_id','ppmi.product_status',\DB::raw('COUNT(ppmi.id) as inv_count'))
                ->get()->toArray();
                
                for($i=0;$i<count($vendors_list_data);$i++){
                    $vendor_id = $vendors_list_data[$i]->vendor_id;
                    if($vendors_list_data[$i]->product_status == 3){
                        //$vendors_list_inv_transit[] = $vendors_list_data[$i];
                    }elseif($vendors_list_data[$i]->product_status == 4){
                        $vendors_list_demand_balance[$vendor_id] = $vendors_list_data[$i]->inv_count;
                    }elseif($vendors_list_data[$i]->product_status == 1 ){
                        $vendors_list_inv_warehouse[$vendor_id] = $vendors_list_data[$i]->inv_count;
                    }elseif($vendors_list_data[$i]->product_status == 2){
                        $vendors_list_inv_wh_in_demand_inv[$vendor_id] = $vendors_list_data[$i]->inv_count;
                    }
                }
                
                $vendor_to_wh = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->where('ppmi.product_status','>',0)     
                ->whereRaw($category_where)         
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.qc_status',1)
                ->where('ppmi.fake_inventory',0)        
                ->groupByRaw('po.vendor_id')        
                ->select('po.vendor_id',\DB::raw('COUNT(ppmi.id) as inv_count'))
                ->get()->toArray();
                
                for($i=0;$i<count($vendor_to_wh);$i++){
                    $vendor_to_wh_list[$vendor_to_wh[$i]->vendor_id] = $vendor_to_wh[$i]->inv_count;
                }
                
                // Units transit from store to store
                $vendors_list_transit_store_to_store_data = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand as spd','spd.id', '=', 'ppmi.demand_id')   
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
                ->wherein('ppmi.product_status',[3])        
                ->where('spd.demand_type','inventory_transfer_to_store')      
                ->whereRaw($category_where)         
                ->where('ppmi.is_deleted',0)
                ->where('vd.is_deleted',0)
                ->where('ppmi.fake_inventory',0)        
                ->groupByRaw('vd.id')        
                ->select('vd.id as vendor_id',\DB::raw('COUNT(ppmi.id) as inv_count'))
                ->get()->toArray();
                
                // Units returned from warehouse to vendor
                $demand_wh_to_vendor = \DB::table('pos_product_master_inventory as ppmi')
                ->join('store_products_demand_inventory as spdi','ppmi.id', '=', 'spdi.inventory_id')             
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
                ->where('spd.demand_type','inventory_return_to_vendor')        
                ->wherein('spd.demand_status',['warehouse_dispatched'])        
                ->whereRaw($category_where)        
                ->where('ppmi.is_deleted',0)
                ->where('spdi.is_deleted',0)   
                ->where('spdi.transfer_status',1)        
                ->where('spd.is_deleted',0)
                ->where('ppmi.fake_inventory',0)        
                ->groupByRaw('po.vendor_id')        
                ->select('po.vendor_id',\DB::raw('COUNT(ppmi.id) as inv_count_returned'))
                ->get()->toArray();
                
                for($i=0;$i<count($demand_wh_to_vendor);$i++){
                    $demand_wh_to_vendor_list[$demand_wh_to_vendor[$i]->vendor_id] = $demand_wh_to_vendor[$i]->inv_count_returned;
                }
                
                // Sales data without po start
                if($user->user_type != 15){
                    $vendors_list_orders_without_po = \DB::table('pos_customer_orders_detail as pcod')
                    ->join('pos_product_master_inventory as ppmi','pcod.inventory_id', '=', 'ppmi.id')           
                    ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')        
                    ->whereRaw('ppmi.po_id IS NULL')        
                    ->where('ppmi.is_deleted',0)
                    ->where('pco.is_deleted',0)        
                    ->where('pcod.is_deleted',0)
                    ->where('pco.order_status',1)
                    ->where('pcod.order_status',1)
                    ->where('ppmi.fake_inventory',0)        
                    ->selectRaw('SUM(pcod.product_quantity) as inv_count_sold,SUM(pcod.net_price) as total_net_price');   

                    $vendors_list_orders_without_po_obj = clone ($vendors_list_orders_without_po);

                    $vendors_list_orders_without_po = $vendors_list_orders_without_po->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")->first();

                    $vendors_list_orders_without_po_obj_total = $vendors_list_orders_without_po_obj->first();

                    $vendors_list_orders_without_po->inv_count_sold_total = $vendors_list_orders_without_po_obj_total->inv_count_sold;
                    $vendors_list_orders_without_po->total_net_price_total = $vendors_list_orders_without_po_obj_total->total_net_price;
                }else{
                    $vendors_list_orders_without_po = new \stdClass;
                    $vendors_list_orders_without_po->inv_count_sold_total = $vendors_list_orders_without_po->total_net_price_total = 0;
                    $vendors_list_orders_without_po->inv_count_sold = $vendors_list_orders_without_po->total_net_price = 0;
                }
                // Sales data without po end
                
                if($user->user_type == 15){
                    $vendor_info = Vendor_detail::where('user_id',$user->id)->where('is_deleted',0)->first();
                    $sub_vendor_list = Vendor_detail::where('pid',$vendor_info->id)->get()->toArray();
                    $sub_vendor_ids = array_column($sub_vendor_list,'id');
                    
                    // Check if sub vendor is in sub vendors list. Sub vendor list also have vendor data to display all vendors in dropdown
                    if(isset($data['sub_v_id']) && !empty($data['sub_v_id']) && (in_array($data['sub_v_id'],$sub_vendor_ids) || $data['sub_v_id'] == $vendor_info->id) ){
                        $vendors_list = Vendor_detail::where('id',$data['sub_v_id'])->where('is_deleted',0)->get()->toArray();
                    }else{
                        $vendor_ids = array_merge([$vendor_info->id],$sub_vendor_ids);
                        $vendors_list = Vendor_detail::wherein('id',$vendor_ids)->where('is_deleted',0)->get()->toArray();
                    }
                }else{
                    $vendors_list = Vendor_detail::where('is_deleted',0)->get()->toArray();
                }
                
                $demand_wh_to_store = json_decode(json_encode($demand_wh_to_store),true);
                $demand_wh_to_store_total = json_decode(json_encode($demand_wh_to_store_total),true);
                $demand_wh_to_store_total_transit = json_decode(json_encode($demand_wh_to_store_total_transit),true);
                
                $vendors_list_orders = json_decode(json_encode($vendors_list_orders),true);
                $vendors_list_orders_total = json_decode(json_encode($vendors_list_orders_total),true);
                
                $vendors_list_demand_returned = json_decode(json_encode($vendors_list_demand_returned),true);
                $vendors_list_demand_returned_total = json_decode(json_encode($vendors_list_demand_returned_total),true);
                
                $vendors_list_transit_store_to_store_data = json_decode(json_encode($vendors_list_transit_store_to_store_data),true);
                $vendors_list_demand_returned_total_transit_store_to_wh = json_decode(json_encode($vendors_list_demand_returned_total_transit_store_to_wh),true);
                
                $vendors_list_demand_returned_comp = json_decode(json_encode($vendors_list_demand_returned_comp),true);
                $vendors_list_demand_returned_comp_total = json_decode(json_encode($vendors_list_demand_returned_comp_total),true);
                
                for($i=0;$i<count($demand_wh_to_store_total_transit);$i++){
                    $rec = $demand_wh_to_store_total_transit[$i]; 
                    if(!isset($demand_wh_to_store_total_transit_1[$rec['vendor_id']])){
                        $demand_wh_to_store_total_transit_1[$rec['vendor_id']] = $rec['inv_count_pushed'];
                    }else{
                        $demand_wh_to_store_total_transit_1[$rec['vendor_id']]+= $rec['inv_count_pushed'];
                    }
                }
                
                for($i=0;$i<count($vendors_list_demand_returned_total_transit_store_to_wh);$i++){
                    $rec = $vendors_list_demand_returned_total_transit_store_to_wh[$i]; 
                    if(!isset($demand_pushed_total_transit_store_to_wh[$rec['vendor_id']])){
                        $demand_pushed_total_transit_store_to_wh[$rec['vendor_id']] = $rec['inv_count_returned'];
                    }else{
                        $demand_pushed_total_transit_store_to_wh[$rec['vendor_id']]+= $rec['inv_count_returned'];
                    }
                }        
                
                for($i=0;$i<count($vendors_list);$i++){
                    $vendor_id = $vendors_list[$i]['id'];
                    
                    $inv_pushed = CommonHelper::getArrayRecord($demand_wh_to_store, 'vendor_id', $vendor_id);
                    $vendors_list[$i]['inv_pushed'] = (!empty($inv_pushed))?$inv_pushed['inv_count_pushed']:0;
                    
                    $inv_pushed_total = CommonHelper::getArrayRecord($demand_wh_to_store_total, 'vendor_id', $vendor_id);
                    $vendors_list[$i]['inv_pushed_total'] = (!empty($inv_pushed_total))?$inv_pushed_total['inv_count_pushed']:0;
                    
                    $inv_returned = CommonHelper::getArrayRecord($vendors_list_demand_returned, 'vendor_id', $vendor_id);
                    $vendors_list[$i]['inv_returned'] = (!empty($inv_returned))?$inv_returned['inv_count_returned']:0;
                    
                    $inv_returned_total = CommonHelper::getArrayRecord($vendors_list_demand_returned_total, 'vendor_id', $vendor_id);
                    $vendors_list[$i]['inv_returned_total'] = (!empty($inv_returned_total))?$inv_returned_total['inv_count_returned']:0;
                    
                    $inv_transit_store_to_wh_total = isset($demand_pushed_total_transit_store_to_wh[$vendor_id])?$demand_pushed_total_transit_store_to_wh[$vendor_id]:0;
                    $vendors_list[$i]['inv_returned_transit_total'] = $inv_transit_store_to_wh_total; 
                    
                    $inv_sold = CommonHelper::getArrayRecord($vendors_list_orders, 'vendor_id', $vendor_id);
                    $vendors_list[$i]['inv_sold'] = (!empty($inv_sold))?$inv_sold['inv_count_sold']:0;
                    $vendors_list[$i]['inv_sold_price'] = (!empty($inv_sold))?$inv_sold['total_net_price']:0;
                    
                    $inv_sold_total = CommonHelper::getArrayRecord($vendors_list_orders_total, 'vendor_id', $vendor_id);
                    $vendors_list[$i]['inv_sold_total'] = (!empty($inv_sold_total))?$inv_sold_total['inv_count_sold']:0;
                    $vendors_list[$i]['inv_sold_price_total'] = (!empty($inv_sold_total))?$inv_sold_total['total_net_price']:0;
                    
                    $vendors_list[$i]['inv_balance'] = isset($vendors_list_demand_balance[$vendor_id])?$vendors_list_demand_balance[$vendor_id]:0;
                    
                    $vendors_list[$i]['inv_transit_wh_to_store'] = isset($demand_wh_to_store_total_transit_1[$vendor_id])?$demand_wh_to_store_total_transit_1[$vendor_id]:0;
                    
                    $inv_transit_store_to_store = CommonHelper::getArrayRecord($vendors_list_transit_store_to_store_data, 'vendor_id', $vendor_id);
                    $vendors_list[$i]['inv_transit_store_to_store'] = (!empty($inv_transit_store_to_store))?$inv_transit_store_to_store['inv_count']:0;
                    
                    $vendors_list[$i]['inv_warehouse'] = isset($vendors_list_inv_warehouse[$vendor_id])?$vendors_list_inv_warehouse[$vendor_id]:0;
                    $vendors_list[$i]['inv_warehouse']+= isset($vendors_list_inv_wh_in_demand_inv[$vendor_id])?$vendors_list_inv_wh_in_demand_inv[$vendor_id]:0;
                    
                    $inv_return_comp = CommonHelper::getArrayRecord($vendors_list_demand_returned_comp_total, 'vendor_id', $vendor_id);
                    $vendors_list[$i]['inv_returned_total']+= (!empty($inv_return_comp))?$inv_return_comp['inv_count_returned']:0;     
                    
                    $vendors_list[$i]['inv_vendor_to_wh'] = isset($vendor_to_wh_list[$vendor_id])?$vendor_to_wh_list[$vendor_id]:0;
                    $vendors_list[$i]['inv_wh_to_vendor'] = isset($demand_wh_to_vendor_list[$vendor_id])?$demand_wh_to_vendor_list[$vendor_id]:0;        
                }
                
                $vendors_list = (!empty($vendors_list))?json_decode(json_encode($vendors_list),true):array();
                
                $totals = array_column($vendors_list,'inv_pushed_total');
                array_multisort($totals, SORT_DESC, $vendors_list);
                
                if(isset($data['action']) && $data['action'] == 'download_csv_vendor_list'){
                    $date_str = date('d-m-Y',strtotime($start_date)).' - '.date('d-m-Y',strtotime($end_date));
                    $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                    $columns = array('Vendor','Vendor to WH Rec (Total)','Transit WH to Store (Total)','WH to Store Rec (Total)','Transit Store to WH (Total)','Store to WH Rec (Total)','Transit Store to Store (Total)','Sold Units (Total)','Net Price','Sell Through (Total)','WH to Store Rec ('.$date_str.')','Store to WH Rec ('.$date_str.')','Sold Units ('.$date_str.')','Net Price ('.$date_str.')','Sell Through ('.$date_str.')','In Stores','In WH','WH to Vendor','In Stores Diff','In WH Diff');

                    $callback = function() use ($vendors_list, $columns,$vendors_list_orders_without_po,$user){
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);
                        $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_sold_total'=>0,'inv_balance'=>0,'inv_sold_price'=>0,'inv_sold_price_total'=>0,'inv_transit'=>0,'inv_warehouse'=>0);
                        
                        for($i=0;$i<count($vendors_list);$i++){
                            $sell_through = ($vendors_list[$i]['inv_balance']+$vendors_list[$i]['inv_sold'] > 0)?round(($vendors_list[$i]['inv_sold']/($vendors_list[$i]['inv_balance']+$vendors_list[$i]['inv_sold']))*100,3):0;
                            $sell_through_total = ($vendors_list[$i]['inv_balance']+$vendors_list[$i]['inv_sold_total'] > 0)?round(($vendors_list[$i]['inv_sold_total']/($vendors_list[$i]['inv_balance']+$vendors_list[$i]['inv_sold_total']))*100,3):0;
                            
                            $array = array($vendors_list[$i]['name'],$vendors_list[$i]['inv_vendor_to_wh'],$vendors_list[$i]['inv_transit_wh_to_store'],$vendors_list[$i]['inv_pushed_total'],$vendors_list[$i]['inv_returned_transit_total'],$vendors_list[$i]['inv_returned_total']);
                            $array[] = $vendors_list[$i]['inv_transit_store_to_store'];
                            $array[] = $vendors_list[$i]['inv_sold_total'];
                            $array[] = round($vendors_list[$i]['inv_sold_price_total'],3);
                            $array[] = $sell_through_total;
                            $array[] = $vendors_list[$i]['inv_pushed'];
                            $array[] = $vendors_list[$i]['inv_returned'];
                            $array[] = $vendors_list[$i]['inv_sold'];
                            $array[] = round($vendors_list[$i]['inv_sold_price'],3);
                            $array[] = $sell_through;
                            $array[] = $vendors_list[$i]['inv_balance'];
                            $array[] = $vendors_list[$i]['inv_warehouse'];
                            $array[] = $vendors_list[$i]['inv_wh_to_vendor'];
                            $array[] = $vendors_list[$i]['inv_pushed_total']-($vendors_list[$i]['inv_returned_transit_total']+$vendors_list[$i]['inv_returned_total']+$vendors_list[$i]['inv_transit_store_to_store']+$vendors_list[$i]['inv_sold_total']+$vendors_list[$i]['inv_balance']);
                            $array[] = (($vendors_list[$i]['inv_vendor_to_wh']+$vendors_list[$i]['inv_returned_total'])-($vendors_list[$i]['inv_transit_wh_to_store']+$vendors_list[$i]['inv_pushed_total']))-($vendors_list[$i]['inv_warehouse']+$vendors_list[$i]['inv_wh_to_vendor']);       
                            
                            fputcsv($file, $array);
                            
                            $total_data['inv_pushed_total']+=$vendors_list[$i]['inv_pushed_total']; 
                            $total_data['inv_sold_total']+=$vendors_list[$i]['inv_sold_total'];
                            $total_data['inv_sold_price_total']+=$vendors_list[$i]['inv_sold_price_total'];
                            $total_data['inv_pushed']+=$vendors_list[$i]['inv_pushed']; 
                            $total_data['inv_sold']+=$vendors_list[$i]['inv_sold']; 
                            $total_data['inv_balance']+=$vendors_list[$i]['inv_balance']; 
                            $total_data['inv_sold_price']+=$vendors_list[$i]['inv_sold_price'];
                            //$total_data['inv_transit']+=$vendors_list[$i]['inv_transit']; 
                            $total_data['inv_warehouse']+=$vendors_list[$i]['inv_warehouse']; 
                        }
                        
                        if($user->user_type != 15){
                            $array = array('Kiaasa Retail LLP (Arnon)','','','','','','',$vendors_list_orders_without_po->inv_count_sold_total,round($vendors_list_orders_without_po->total_net_price_total,3),'','','',$vendors_list_orders_without_po->inv_count_sold,round($vendors_list_orders_without_po->total_net_price,3));
                            fputcsv($file, $array);
                        }

                        $array = array('Total','','','','','','',$total_data['inv_sold_total']+$vendors_list_orders_without_po->inv_count_sold_total,round($total_data['inv_sold_price_total']+$vendors_list_orders_without_po->total_net_price_total,3),'','','',$total_data['inv_sold']+$vendors_list_orders_without_po->inv_count_sold,round($total_data['inv_sold_price']+$vendors_list_orders_without_po->total_net_price,3),'',$total_data['inv_balance'],$total_data['inv_warehouse']);
                        fputcsv($file, $array);

                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }

                $responseData = array('report_type'=>$report_type,'vendors_list'=>$vendors_list,'vendors_list_orders_without_po'=>$vendors_list_orders_without_po,'user'=>$user,'sub_vendor_list'=>$sub_vendor_list,'vendor_info'=>$vendor_info);
            }
            
            $responseData['error_message'] = '';
            
            $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where('is_deleted',0)->orderBy('name')->get()->toArray();
            $responseData['po_category_list'] = $po_category_list;
                    
            return view('admin/vendor_sales_report',$responseData); 
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return view('admin/vendor_sales_report',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function inventoryStatus(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $vendor_data = Vendor_detail::where('user_id',$user->id)->first();
            $status_data = array('inv_in_warehouse'=>0,'inv_in_transit'=>0,'inv_in_store'=>0,'inv_sold'=>0,'inv_total'=>0);
            
            $status_list = CommonHelper::getInventoryDBObject([1,2],[],[1,0]);  
            $status_list = $status_list->where('po.vendor_id',$vendor_data['id'])
            ->groupBy('ppmi.product_status')                
            ->selectRaw('ppmi.product_status,COUNT(ppmi.id) as inv_count')
            ->get()->toArray();
            
            for($i=0;$i<count($status_list);$i++){
                if(in_array($status_list[$i]->product_status, array(0,1,2))){
                    $status_data['inv_in_warehouse']+=$status_list[$i]->inv_count;
                }elseif($status_list[$i]->product_status == 3){
                    $status_data['inv_in_transit']+=$status_list[$i]->inv_count;
                }elseif($status_list[$i]->product_status == 4){
                    $status_data['inv_in_store']+=$status_list[$i]->inv_count;
                }elseif($status_list[$i]->product_status == 5){
                    $status_data['inv_sold']+=$status_list[$i]->inv_count;
                }
                
                $status_data['inv_total']+=$status_list[$i]->inv_count;
            }
            
            return view('vendor/inventory_status',array('status_data'=>$status_data,'error_message'=>$error_message,'user'=>$user));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR_INVENTORY_STATUS',__FUNCTION__,__FILE__);
            return view('vendor/inventory_status',array('error_message'=>$e->getMessage()));
        }
    }
    
    function addVendorInventoryPayment(Request $request){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            if(!(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate']) && isset($data['vendor_id']) && !empty($data['vendor_id']))){
                throw new \Exception('Start Date, End Date and Vendor ID are Required Fields');
            }
            
            $start_date = CommonHelper::convertUserDateToDBDate(trim($data['startDate'])).' 00:00';
            $end_date = CommonHelper::convertUserDateToDBDate(trim($data['endDate'])).' 23:59';
            $vendor_id = trim($data['vendor_id']);
            
            $vendor_data = Vendor_detail::where('id',$vendor_id)->first();
            
            $products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
            ->join('pos_customer_orders_detail as pcod',function($join){$join->on('pcod.inventory_id','=','ppmi.id')->on('pcod.order_id','=','ppmi.customer_order_id');})      
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')      
            ->leftJoin('store as s','s.id', '=', 'pcod.store_id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id') 
            ->leftJoin('pos_product_images as ppi',function($join){$join->on('ppi.product_id','=','ppm.id')->where('ppi.image_type','=','front')->where('ppi.is_deleted','=',0);})                   
            ->where('po.vendor_id',$vendor_id)
            ->where('ppmi.product_status',5)
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)
            ->where('pcod.is_deleted',0)   
            ->where('pco.is_deleted',0)       
            ->where('pco.order_status',1)       
            ->where('pcod.order_status',1)           
            ->where('ppmi.fake_inventory',0)   
            ->where('ppmi.payment_status',0)        
            ->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'");

            $products_list_total = clone $products_list;

            $products_list_total = $products_list_total
            ->selectRaw('SUM(pcod.product_quantity) as inv_count,SUM(pcod.net_price) as inv_net_price,SUM(ppmi.base_price) as inv_cost_price,SUM(pcod.sale_price) as inv_sale_price,
            SUM(pcod.discounted_price_actual) as inv_discounted_price,SUM(pcod.discount_amount_actual) as inv_discount_amount,SUM(pcod.gst_amount) as inv_gst_amount,
            SUM(ppmi.vendor_base_price) as inv_vendor_base_price,SUM(ppmi.vendor_gst_amount) as inv_vendor_gst_amount')->first();  
            
            if($products_list_total->inv_count == 0){
                $error_message =  ('There is no inventory with pending payment. Order Start Date: '.(trim($data['startDate'])).' | Order End Date: '.(trim($data['endDate'])).' | Vendor: '.$vendor_data->name);
            }

            $products_list = $products_list->selectRaw('ppmi.id as inventory_id,ppmi.peice_barcode,ppm.id as product_id,ppm.product_name,ppm.product_sku,ppm.vendor_product_sku,pcod.sale_price,
            pcod.net_price,pcod.discount_percent,pcod.discount_amount,pcod.gst_percent,pcod.gst_amount,pcod.product_quantity,
            pcod.gst_inclusive,s.store_name,pcod.order_id, pcod.created_at AS order_date,dlim_1.name as color_name,s.store_id_code,
            psc.size as size_name,pco.order_no,ppmi.vendor_base_price,ppmi.vendor_gst_percent,ppmi.vendor_gst_amount,ppmi.base_price,ppi.image_name')
            ->orderByRaw('pco.id DESC');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $products_list = $products_list->get()->toArray();
            }else{
                $products_list = $products_list->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=vendor_inventory_payment_make.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Inventory ID','QR Code','Product','SKU','Vendor SKU','MRP','Discount %','Discount Amount','GST %','GST Amount','GST Inclusive','Net Price','Cost Rate','Cost GST %','Cost GST Amount','Cost Price','Store Name','Code','Order Date','Order No');

                $callback = function() use ($products_list,$products_list_total,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($products_list);$i++){
                        $product_name = $products_list[$i]->product_name.' '.$products_list[$i]->size_name.' '.$products_list[$i]->color_name;
                        $gst_inc = ($products_list[$i]->gst_inclusive == 1)?'Yes':'No';
                        
                        $array = array($products_list[$i]->inventory_id, CommonHelper::filterCsvInteger($products_list[$i]->peice_barcode),$product_name,$products_list[$i]->product_sku,$products_list[$i]->vendor_product_sku,$products_list[$i]->sale_price,
                        round($products_list[$i]->discount_percent,2),round($products_list[$i]->discount_amount,2),$products_list[$i]->gst_percent,round($products_list[$i]->gst_amount,2),$gst_inc,round($products_list[$i]->net_price,2),
                        $products_list[$i]->vendor_base_price,$products_list[$i]->vendor_gst_percent,$products_list[$i]->vendor_gst_amount,$products_list[$i]->base_price,$products_list[$i]->store_name,$products_list[$i]->store_id_code,
                        date('d-m-Y H:i',strtotime($products_list[$i]->order_date)),$products_list[$i]->order_no);
                        
                        fputcsv($file, $array);
                    }
                    
                    $array = ['Total Units: '.$products_list_total->inv_count,'','','','',$products_list_total->inv_sale_price,'',round($products_list_total->inv_discount_amount,2),'',
                    round($products_list_total->inv_gst_amount,2),'',round($products_list_total->inv_net_price,2),round($products_list_total->inv_vendor_base_price,2),'',round($products_list_total->inv_vendor_gst_amount,2),
                    round($products_list_total->inv_cost_price,2),'','','',''];
                    
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('vendor/inventory_payment_add',array('products_list'=>$products_list,'products_list_total'=>$products_list_total,'error_message'=>$error_message,'startDate'=>$data['startDate'],
            'endDate'=>$data['endDate'],'vendor_data'=>$vendor_data,'start_date'=>$start_date,'end_date'=>$end_date,'user'=>$user));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR_PAYMENT',__FUNCTION__,__FILE__);
            return view('vendor/inventory_payment_add',array('error_message'=>$e->getMessage()));
        }
    }
    
    function submitAddVendorInventoryPayment(Request $request){
        try{
            set_time_limit(300);
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $validationRules = array('startDate'=>'required','endDate'=>'required','vendor_id'=>'required','comment'=>'required');
            $attributes = array('startDate'=>'Start Date','endDate'=>'End Date');
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $start_date = CommonHelper::convertUserDateToDBDate(trim($data['startDate'])).' 00:00';
            $end_date = CommonHelper::convertUserDateToDBDate(trim($data['endDate'])).' 23:59';
            $vendor_id = trim($data['vendor_id']);
            
            $vendor_data = Vendor_detail::where('id',$vendor_id)->first();
            
            $products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
            ->join('pos_customer_orders_detail as pcod',function($join){$join->on('pcod.inventory_id','=','ppmi.id')->on('pcod.order_id','=','ppmi.customer_order_id');})      
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')      
            ->where('po.vendor_id',$vendor_id)
            ->where('ppmi.product_status',5)
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)
            ->where('pcod.is_deleted',0)   
            ->where('pco.is_deleted',0)       
            ->where('pco.order_status',1)       
            ->where('pcod.order_status',1)           
            ->where('ppmi.fake_inventory',0)   
            ->where('ppmi.payment_status',0)        
            ->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'");

            $products_list_total = clone $products_list;

            $products_list_total = $products_list_total
            ->selectRaw('SUM(pcod.product_quantity) as inv_count,SUM(pcod.net_price) as inv_net_price,SUM(pcod.base_price) as inv_cost_price')->first();  
            
            if($products_list_total->inv_count == 0){
                $error_message =  ('There is no inventory with pending payment. Order Start Date: '.(trim($data['startDate'])).' | Order End Date: '.(trim($data['endDate'])).' | Vendor: '.$vendor_data->name);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_message ));
            }

            $products_list = $products_list->selectRaw('ppmi.id as inventory_id,pcod.net_price,ppmi.base_price,pcod.order_id')
            ->orderByRaw('pco.id ASC')
            ->get();
            
            \DB::beginTransaction();
            
            $insertArray = ['vendor_id'=>$vendor_id,'start_date'=>CommonHelper::convertUserDateToDBDate(trim($data['startDate'])),'end_date'=>CommonHelper::convertUserDateToDBDate(trim($data['endDate'])),
            'inventory_count'=>$products_list_total->inv_count,'inventory_net_price'=>$products_list_total->inv_net_price,'inventory_cost_price'=>$products_list_total->inv_cost_price,'comment'=>trim($data['comment']),'user_id'=>$user->id];
            
            $payment = Pos_inventory_payments::create($insertArray);
            
            for($i=0;$i<count($products_list);$i++){
                $insertArray = ['payment_id'=>$payment->id,'inventory_id'=>$products_list[$i]->inventory_id,'net_price'=>$products_list[$i]->net_price,'cost_price'=>$products_list[$i]->base_price,'order_id'=>$products_list[$i]->order_id];
                Pos_inventory_payments_detail::create($insertArray);
                
                Pos_product_master_inventory::where('id',$products_list[$i]->inventory_id)->update(['payment_status'=>1,'payment_id'=>$payment->id]);
            }
            
            \DB::commit();
            
            CommonHelper::createLog('Vendor Payment Added. ID: '.$payment->id,'VENDOR_PAYMENT_ADDED','VENDOR');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Payment added successfully'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'VENDOR_PAYMENT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function vendorInventoryPaymentList(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $payment_list = \DB::table('pos_inventory_payments as p')
            ->Join('vendor_detail as v','v.id','=','p.vendor_id')
            ->Join('users as u','u.id','=','p.user_id')        
            ->where('p.is_deleted',0)        
            ->select('p.*','v.name as vendor_name','u.name as user_name');
            
            if(isset($data['p_id']) && !empty($data['p_id'])){
                $payment_list = $payment_list->where('p.id',$data['p_id']);
            }
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $payment_list = $payment_list->where('p.vendor_id',$data['v_id']);
            }
            
            if($user->user_type == 15){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->first();
                $payment_list = $payment_list->where('p.vendor_id',$vendor_data->id);
            }
            
            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $start_date = CommonHelper::convertUserDateToDBDate(trim($data['startDate']));
                $end_date = CommonHelper::convertUserDateToDBDate(trim($data['endDate']));
                $payment_list = $payment_list->whereRaw("(p.start_date = '$start_date' AND p.end_date = '$end_date')");        
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $payment_list = $payment_list->offset($start)->limit($limit)->orderBy('p.id','ASC')->get()->toArray();
            }else{
                $payment_list = $payment_list->orderBy('p.id','DESC')->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=vendor_inventory_payment_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Payment ID','Vendor','Start Date','End Date','Inventory Count','Cost Price','Net Price','Comment','User','Created On');

                $callback = function() use ($payment_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($payment_list);$i++){
                        $array = array($payment_list[$i]->id,$payment_list[$i]->vendor_name,date('d M Y',strtotime($payment_list[$i]->start_date)),date('d M Y',strtotime($payment_list[$i]->end_date)),$payment_list[$i]->inventory_count,$payment_list[$i]->inventory_cost_price,
                        $payment_list[$i]->inventory_net_price,$payment_list[$i]->comment,$payment_list[$i]->user_name,date('d M Y',strtotime($payment_list[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $vendor_list = CommonHelper::getVendorsList();
            
            return view('vendor/inventory_payment_list',array('payment_list'=>$payment_list,'vendor_list'=>$vendor_list,'error_message'=>'','user'=>$user));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR_PAYMENT',__FUNCTION__,__FILE__);
            return view('vendor/inventory_payment_list',array('error_message'=>$e->getMessage(),'payment_list'=>array()));
        }
    }
    
    function vendorInventoryPaymentDetail(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $payment_id = $id;
            $error_message = '';
            
            
            $payment_detail = \DB::table('pos_inventory_payments as p')
            ->Join('vendor_detail as v','v.id','=','p.vendor_id')
            ->Join('users as u','u.id','=','p.user_id')        
            ->where('p.id',$payment_id)        
            ->select('p.*','v.name as vendor_name','u.name as user_name');
            
            if($user->user_type == 15){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->first();
                $payment_detail = $payment_detail->where('p.vendor_id',$vendor_data->id);
            }
            
            $payment_detail = $payment_detail->first();
            
            if(empty($payment_detail)){
                throw new \Exception('Payment data does not exists');
            }
            
            $products_list = \DB::table('pos_inventory_payments_detail as pipd')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pipd.inventory_id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('pos_customer_orders_detail as pcod',function($join){$join->on('pcod.inventory_id','=','pipd.inventory_id')->on('pcod.order_id','=','pipd.order_id');})          
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')      
            ->leftJoin('store as s','s.id', '=', 'pcod.store_id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id') 
            ->leftJoin('pos_product_images as ppi',function($join){$join->on('ppi.product_id','=','ppm.id')->where('ppi.image_type','=','front')->where('ppi.is_deleted','=',0);})                   
            ->where('pipd.payment_id',$payment_id)
            ->where('pipd.is_deleted',0)   
            ->selectRaw('ppmi.id as inventory_id,ppmi.peice_barcode,ppm.id as product_id,ppm.product_name,ppm.product_sku,ppm.vendor_product_sku,pcod.sale_price,
            pcod.net_price,pcod.discount_percent,pcod.discount_amount,pcod.gst_percent,pcod.gst_amount,pcod.product_quantity,
            pcod.gst_inclusive,s.store_name,pcod.order_id, pcod.created_at AS order_date,dlim_1.name as color_name,s.store_id_code,ppmi.payment_status,
            psc.size as size_name,pco.order_no,ppmi.vendor_base_price,ppmi.vendor_gst_percent,ppmi.vendor_gst_amount,ppmi.base_price,ppi.image_name')
            ->orderByRaw('pco.id DESC');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $products_list = $products_list->get()->toArray();
            }else{
                $products_list = $products_list->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=vendor_inventory_payment_detail_'.$payment_detail->id.'.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Inventory ID','QR Code','Product','SKU','Vendor SKU','MRP','Discount %','Discount Amount','GST %','GST Amount','GST Inclusive','Net Price','Cost Rate','Cost GST %','Cost GST Amount','Cost Price','Store Name','Store Code','Order Date','Order No');

                $callback = function() use ($products_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($products_list);$i++){
                        $array = array($products_list[$i]->inventory_id,$products_list[$i]->peice_barcode,$products_list[$i]->product_name.' '.$products_list[$i]->size_name.' '.$products_list[$i]->color_name,$products_list[$i]->product_sku,$products_list[$i]->vendor_product_sku,$products_list[$i]->sale_price,
                        $products_list[$i]->discount_percent,round($products_list[$i]->discount_amount,2),$products_list[$i]->gst_percent,round($products_list[$i]->gst_amount,2),($products_list[$i]->gst_inclusive == 1)?'Yes':'No',round($products_list[$i]->net_price,2),$products_list[$i]->vendor_base_price,
                        $products_list[$i]->vendor_gst_percent,$products_list[$i]->vendor_gst_amount,$products_list[$i]->base_price,$products_list[$i]->store_name,$products_list[$i]->store_id_code,date('d-m-Y H:i',strtotime($products_list[$i]->order_date)),$products_list[$i]->order_no);
                        
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('vendor/inventory_payment_detail',array('products_list'=>$products_list,'payment_detail'=>$payment_detail,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR_PAYMENT',__FUNCTION__,__FILE__);
            return view('vendor/inventory_payment_detail',array('error_message'=>$e->getMessage()));
        }
    }
    
    function vendorSubVendorsList(Request $request,$id){
        try{
            $data = $request->all();
            $vendor_id = $id;
            
            $vendors_list = \DB::table('vendor_detail as v')
            ->where('v.pid',$vendor_id)
            ->where('v.is_deleted',0);
            
            if(isset($data['v_name']) && !empty($data['v_name'])){
                $name_email = trim($data['v_name']);
                $vendors_list = $vendors_list->whereRaw("(name like '%{$name_email}%' OR email = '{$name_email}' OR phone = '{$name_email}')");
            }
            
            $vendors_list = $vendors_list->select('v.*')->orderBy('v.id')->paginate(100);
            
            $vendor_data = Vendor_detail::where('id',$vendor_id)->first();
            
            $vendors_add_list = Vendor_detail::where('pid',0)->where('subvendors_count',0)->orderBy('name')->get()->toArray();
            
            return view('vendor/vendors_subvendors_list',array('vendors_list'=>$vendors_list,'error_message'=>'','vendor_data'=>$vendor_data,'vendors_add_list'=>$vendors_add_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return view('vendor/vendors_subvendors_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    public function addVendorSubVendor(Request $request){
        try{
            
            $data = $request->all();
            $validateionRules = array('subvendor_add'=>'required');
            $attributes = array('subvendor_add'=>'Vendor');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $vendor_data = Vendor_detail::where('id',$data['subvendor_add'])->first();
            if(!empty($vendor_data['pid'])){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Vendor is already SubVendor', 'errors' =>'Vendor is already SubVendor' ));
            }
            
            if($vendor_data['subvendors_count'] > 0){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Vendor have SubVendors', 'errors' =>'Vendor have SubVendors' ));
            }
            
            \DB::beginTransaction();
            
            $updateArray = array('pid'=>trim($data['main_vendor_id']),'pid_added_date'=>date('Y/m/d H:i:s'));
           
            Vendor_detail::where('id',trim($data['subvendor_add']))->update($updateArray);
            
            Vendor_detail::where('id',trim($data['main_vendor_id']))->increment('subvendors_count');
            
            \DB::commit();
            
            CommonHelper::createLog('SubVendor Added. Main Vendor ID: '.$data['main_vendor_id'].'. Subvendor ID: '.$data['subvendor_add'],'SUBVENDOR_ADDED','VENDOR');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'SubVendor added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function deleteVendorSubVendor(Request $request){
        try{
            $data = $request->all();
            $validateionRules = array('subvendor_id'=>'required');
            $attributes = array('subvendor_id'=>'Vendor');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $vendor_data = Vendor_detail::where('id',$data['subvendor_id'])->first();
            if(empty($vendor_data['pid'])){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Vendor is not SubVendor', 'errors' =>'Vendor is not SubVendor' ));
            }
            
            \DB::beginTransaction();
            
            $updateArray = array('pid'=>0,'pid_added_date'=>null);
           
            Vendor_detail::where('id',trim($data['subvendor_id']))->update($updateArray);
            
            Vendor_detail::where('id',trim($vendor_data['pid']))->decrement('subvendors_count');
            
            \DB::commit();
            
            CommonHelper::createLog('SubVendor Deleted. Main Vendor ID: '.$vendor_data['pid'].'. Subvendor ID: '.$data['subvendor_id'],'SUBVENDOR_DELETED','VENDOR');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'SubVendor deleted successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
}
