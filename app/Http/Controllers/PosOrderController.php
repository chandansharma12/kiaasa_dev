<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Store_user;
use App\Models\Pos_inventory;
use App\Models\Pos_customer;
use App\Models\Pos_customer_orders;
use App\Models\Pos_customer_orders_detail;
use App\Models\Pos_customer_orders_update;
use App\Models\Pos_customer_orders_update_items;
use App\Models\Pos_customer_orders_drafts;
use App\Models\Pos_product_master;
use App\Models\Pos_product_master_inventory;
use App\Models\Pos_customer_orders_payments;
use App\Models\Store_staff;
use App\Models\Discount_list;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;

class PosOrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
    }
    
    function posBilling(Request $request){
        try{
            $user = Auth::user();
            $data = $request->all();
            $order_data = null;
            $error_message = $store_user_id = '';
            $store_staff = array();
            $foc = (isset($data['foc']) && $data['foc'] == 1)?1:0;
            
            //$order_type = (isset($data['order_type']) && !empty($data['order_type']))?trim($data['order_type']):'store';
            $store_id = (isset($data['store_id']) && !empty($data['store_id']))?trim($data['store_id']):'';
            
            if(!empty($store_id)){
                $store_user = Store_user::where('store_id',$store_id)->where('is_deleted',0)->first();
                $store_data = CommonHelper::getUserStoreData($store_user->user_id);
                $store_user_id = $store_user->user_id; // user_id of store user
            }else{
                $store_data = CommonHelper::getUserStoreData($user->id);
                $store_user_id = $user->id; // user_id of store user or warehouse
            }
            
            if(isset($data['action']) && $data['action'] == 'get_coupon_data'){
                $coupon_no = trim($data['coupon_no']);
                
                $coupon_data = \DB::table('coupon as c')
                ->join('coupon_items as ci','c.id', '=', 'ci.coupon_id')   
                ->where('ci.coupon_no',$coupon_no)        
                ->where('ci.coupon_used',0)        
                ->where('c.is_deleted',0)        
                ->where('ci.is_deleted',0)
                ->select('c.*','ci.coupon_used','ci.coupon_no','ci.id as coupon_item_id')        
                ->first();
                
                if(empty($coupon_data)){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon does not exists',),200);
                }
                
                if($coupon_data->status != 1){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon is not enabled',),200);
                }
                
                /*if($coupon_data->coupon_used == 1){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon is already used',),200);
                }*/
                
                if(!(strtotime(date('Y/m/d')) >= strtotime($coupon_data->valid_from) && strtotime(date('Y/m/d')) <= strtotime($coupon_data->valid_to))){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon date is expired '),200);
                }
                
                if($coupon_data->store_id != $store_data->id){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon is not applicable for your store',),200);
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Coupon added successfully','coupon_data' => $coupon_data),200);
            }
            
            if(!empty($store_data)){
                $store_staff = Store_staff::where('store_id',$store_data->id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            }else{
                $store_staff = Store_staff::where('store_id',0)->where('is_deleted',0)->where('status',1)->get()->toArray();
            }
            
            if(isset($data['order_id']) && !empty($data['order_id'])){
                $order_id = trim($data['order_id']);
                $order_data =  Pos_customer_orders::where('id',$order_id)->first();
                
                if($order_data->order_status != 2){
                    $error_message = 'Order Status is not Pending (OrderNo: '.$order_data->order_no.')';
                }elseif($store_data->id != $order_data->store_id){
                    $error_message = 'Order does not exists in '.$store_data->store_name;
                }
            }
            
            return view('store/pos_billing',array('error_message'=>$error_message,'store_staff'=>$store_staff,'order_data'=>$order_data,'foc'=>$foc,'store_data'=>$store_data,'store_user_id'=>$store_user_id));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('store/pos_billing',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    // This function is for pos billing.
    function posProductDetailByBarcode(Request $request){
        try{
            //\DB::enableQueryLog();
            $data = $request->all();
            $user = Auth::user();
            $barcode = trim($data['barcode']);
            $ids = trim($data['ids']);
            $foc = (isset($data['foc']) && $data['foc'] == 1)?1:0;
            $store_user_id = (isset($data['store_user_id']) && !empty($data['store_user_id']))?trim($data['store_user_id']):$user->id;
            
            $product_data = CommonHelper::getPosProductData($barcode,$ids,$store_user_id,$foc);
            
            if(isset($data['staff_id']) && !empty($product_data)){
                $product_data->staff_id = trim($data['staff_id']);
            }
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product data','product_data' => $product_data),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }

    public function createPosOrder(Request $request){
        try{
            $data = $request->all();   
            $user = Auth::user();
           
            if(isset($data['action']) && $data['action'] == 'check_exist_customer'){
                return CommonHelper::getPosCustomerData($data);
            }
            
            $customer_type = trim($data['billing_customer_type']);
           
            $validateionRules = array('customer_phone_new'=>'Required','customer_email'=>'nullable|email|unique:pos_customer,email,'.$data['customer_id'],
            'customer_salutation'=>'Required','customer_name'=>'Required','customer_postal_code'=>'max:50','customer_wedding_date'=>'nullable|date',
            'customer_dob'=>'nullable|date','bags_count'=>'Required');
            $attributes = array('customer_phone_new'=>'Customer Phone','customer_salutation'=>'salutation','customer_name'=>'Name');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            } 
            
            \DB::beginTransaction();
            
            if($customer_type == 'existing'){
                if(!empty($data['customer_wedding_date'])){ 
                    $date_wedding_arr = explode('-',$data['customer_wedding_date']);
                    $data['customer_wedding_date'] = $date_wedding_arr[2].'-'.$date_wedding_arr[1].'-'.$date_wedding_arr[0];
                }
                if(!empty($data['customer_dob'])){ 
                    $date_dob_arr = explode('-',$data['customer_dob']);
                    $data['customer_dob'] = $date_dob_arr[2].'-'.$date_dob_arr[1].'-'.$date_dob_arr[0];
                }
                
                CommonHelper::updatePosCustomer($data);
                $customer_id = $data['customer_id'];
            }else{
                
                $response_data = CommonHelper::addPosCustomer($data);
                if(strtolower($response_data['status']) == 'success'){
                    $pos_customer = $response_data['customer_data'];
                    $customer_id = $pos_customer->id;
                }else{
                    return response($response_data);
                }
            }
            
            $data['customer_id'] = $customer_id;
            $data['user_id'] = (isset($data['store_user_id']) && !empty($data['store_user_id']))?$data['store_user_id']:$user->id;
            $data['order_source'] = 'web';
            
            $id_array = explode(',',$data['ids']);
            
            $inv = Pos_product_master_inventory::wherein('id',$id_array)->where('product_status',4)->where('is_deleted',0)->first();
            if(isset($inv->store_id) && !empty($inv->store_id)){
                $store_id = $inv->store_id;
                $data['store_id'] = $store_id;
            }
            
            $response_data = $response_data_orig = CommonHelper::createPosOrder($data);
            
            \DB::commit();
            
            $response_data = json_decode(json_encode($response_data),true);
            
            $mobile_no = trim($data['customer_phone_new']);
            $valid_mobile_no = CommonHelper::validateMobileNumber($mobile_no);
            if(isset($response_data['original']['status']) && $response_data['original']['status'] == 'success' && $valid_mobile_no && isset($response_data['original']['order_data']['pdf_file_link_1']) ){
                $pdf_link = $response_data['original']['order_data']['pdf_file_link_1'];
                $pdf_link = str_replace(['localhost/kiaasa/public'],['web.kiaasa.com'],$pdf_link); 
                $message = urlencode('Dear Valued Customer Thank you for shopping with KIAASA. View your bill :- '.$pdf_link);
                
                $url = 'http://smsfortius.com/api/mt/SendSMS?user=kiaasa&password=kiaasa951&senderid=KIAASA&channel=Trans&DCS=0&flashsms=0&number='.$mobile_no.'&text='.$message.'&route=02';
                $res = CommonHelper::processCURLRequest($url,'','','','',1);
            }
            
            return $response_data_orig;
            
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage().', '.$e->getLine()),500);
        }  
    }
    
    public function listPosOrder(Request $request){
        try{
            ini_set('memory_limit', '-1');
            $data = $request->all();            
            $user = Auth::user();
            $error_message = $search_by = '';
            $store_user = $store_list = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if($user->user_type == 9){
                $store_user = \DB::table('store as s')->join('store_users as su','s.id', '=', 'su.store_id')->where('su.user_id',$user->id)->select('s.*')->first();
            }
            
            if(!empty($store_user) ||  in_array($user->user_type, array(1,14)) || $is_fake_inventory_user){
                $pos_orders = \DB::table('pos_customer_orders as pco')
                ->leftJoin('pos_customer as pc','pc.id', '=', 'pco.customer_id')
                ->join('users as u1','u1.id', '=', 'pco.store_user_id')    
                ->leftJoin('store as s','s.id', '=', 'pco.store_id')                
                //->join('pos_customer_orders_detail as pcod',function($join){$join->on('pco.id','=','pcod.order_id')->where('pcod.is_deleted','=','0');})              
                ->where('pco.is_deleted',0);

                if(!empty($store_user)){
                    $pos_orders = $pos_orders->where('pco.store_id',$store_user->id);
                }

                if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                    $start_date = date('Y/m/d',strtotime(trim($data['startDate']))).' 00:00';
                    $end_date = date('Y/m/d',strtotime(trim($data['endDate']))).' 23:59';
                    $pos_orders = $pos_orders->whereRaw("pco.created_at BETWEEN '$start_date' AND '$end_date'");
                }

                if(isset($data['search_value']) && !empty($data['search_value'])){
                    $search_by = (isset($data['search_by']) && !empty($data['search_by']))?trim($data['search_by']):'order_no';
                    $search_value = trim($data['search_value']);
                    if($search_by == 'order_no'){
                        //$pos_orders = $pos_orders->where('pco.order_no','LIKE','%'.$search_value);
                        $pos_orders = $pos_orders->whereRaw("(pco.order_no LIKE '$search_value' OR pco.orig_order_no LIKE '$search_value')");
                    }elseif($search_by == 'phone'){
                        $pos_orders = $pos_orders->where('pc.phone','LIKE','%'.$search_value.'%');
                    }elseif($search_by == 'cust_name'){
                        $pos_orders = $pos_orders->where('pc.customer_name','LIKE','%'.$search_value.'%');
                    }elseif($search_by == 'order_id'){
                        $pos_orders = $pos_orders->where('pco.id',$search_value);
                    }
                }
                
                if(isset($data['store_id']) && !empty($data['store_id'])){
                    $pos_orders = $pos_orders->where('pco.store_id',trim($data['store_id']));
                }
                
                if(isset($data['order_type']) && $data['order_type'] == '1'){
                    //$pos_orders = $pos_orders->where('pcod.gst_inclusive',1);
                }
                
                if(isset($data['order_source']) && in_array($data['order_source'],['pos','website']) ){
                    $order_source = ['pos'=>'web','website'=>'website_api'];
                    $pos_orders = $pos_orders->where('pco.order_source',$order_source[trim($data['order_source'])]);
                }
                
                if((!$is_fake_inventory_user) && isset($data['order_type']) && $data['order_type'] == '2'){
                    $pos_orders = $pos_orders->where('pco.foc',1);
                }
                
                if(isset($data['discount']) && $data['discount'] != ''){
                    //$pos_orders = $pos_orders->where('pcod.discount_percent',trim($data['discount']));
                }
                
                if($is_fake_inventory_user){
                    //Display both types of orders to fake inventory user if order_type filter is not used.
                    if(isset($data['order_type']) && $data['order_type'] != ''){
                        if($data['order_type'] == 1){
                            $pos_orders = $pos_orders->where('pco.fake_inventory',1);
                        }
                        if($data['order_type'] == 2){
                            $pos_orders = $pos_orders->where('pco.fake_inventory',0);
                        }
                    }
                }else{
                    // display only real orders to other types of user
                    $pos_orders = $pos_orders->where('pco.fake_inventory',0);
                }

                if(isset($data['sort_by']) && !empty($data['sort_by'])){
                    $sort_array = array('id'=>'pco.id','customer_name'=>'pc.customer_name','total_amount'=>'pco.total_price','payment_method'=>'pco.payment_method','foc'=>'pco.foc',
                    'created_by'=>'store_user_name','created_on'=>'pco.created_at','customer_phone'=>'pc.phone','total_items'=>'pco.total_items','order_no'=>'pco.order_no','order_status'=>'pco.order_status');
                    $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'pco.id';
                    $pos_orders = $pos_orders->orderBy($sort_by,CommonHelper::getSortingOrder());
                }else{
                    $pos_orders = $pos_orders->orderBy('pco.created_at','DESC');
                }

                $pos_orders = $pos_orders
                //->groupBy('pco.id')
                ->select('pco.*','pc.customer_name','pc.phone as customer_phone','u1.name as store_user_name','pc.salutation','s.store_name','s.store_id_code');
                
                if(isset($data['action']) && $data['action'] == 'download_csv'){
                    $pos_order_cust_count = trim($data['pos_orders_count']);
                    $pos_orders_count_arr = explode('_',$pos_order_cust_count);
                    $start = $pos_orders_count_arr[0];
                    $start = $start-1;
                    $end = $pos_orders_count_arr[1];
                    $limit = $end-$start;
                    $pos_orders = $pos_orders->offset($start)->limit($limit)->get();
                }else{
                    $pos_orders = $pos_orders->paginate(100);
                    $pos_orders_count = $pos_orders->total();
                }
            }
            
            // Download csv start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=pos_orders.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Order No','Customer Name','Customer Phone','Total Items','Total Amount','Store Name','Store Code','Created On','Order Status');

                $callback = function() use ($pos_orders, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_units = $total_price = 0;
                    for($i=0;$i<count($pos_orders);$i++){
                        $order_status = CommonHelper::getPosOrderStatusText($pos_orders[$i]->order_status);
                        $array = array($pos_orders[$i]->order_no,$pos_orders[$i]->salutation.' '.$pos_orders[$i]->customer_name,$pos_orders[$i]->customer_phone,$pos_orders[$i]->total_items,$pos_orders[$i]->total_price,$pos_orders[$i]->store_name,$pos_orders[$i]->store_id_code,date('d-M-Y',strtotime($pos_orders[$i]->created_at)),$order_status);
                        $total_units+=$pos_orders[$i]->total_items;
                        $total_price+=$pos_orders[$i]->total_price;
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','',$total_units,$total_price);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download csv end
            
            if($user->user_type == 9  && empty($store_user)){
                $error_message = 'Store not assigned for user';
            }
            
            if($user->user_type == 1 || $is_fake_inventory_user){
                $store_list = CommonHelper::getStoresList();
            }
            
            $discount_list = Pos_customer_orders_detail::where('discount_percent','>',0)->where('is_deleted',0)->groupBy('discount_percent')
            ->selectRaw('discount_percent, COUNT(DISTINCT order_id) AS cnt')
            ->orderBy('cnt','DESC')    
            ->get()->toArray();        
            
            return view('store/pos_order_list',array('error_message'=>$error_message,'pos_orders'=>$pos_orders,'user'=>$user,'search_by'=>$search_by,
            'store_list'=>$store_list,'discount_list'=>$discount_list,'is_fake_inventory_user'=>$is_fake_inventory_user,'pos_orders_count'=>$pos_orders_count));
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return view('store/pos_order_list',array('error_message'=>$e->getMessage().', '.$e->getLine(),'pos_orders'=>array()));
        }  
    }
    
    public function posOrderDetail(Request $request,$id){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            $order_id = $id;
            
            $store_data = CommonHelper::getUserStoreData($user->id);
            
            $pos_order_data = \DB::table('pos_customer_orders as pco')
            ->leftJoin('pos_customer as pc','pc.id', '=', 'pco.customer_id')
            ->join('users as u1','u1.id', '=', 'pco.store_user_id')        
            ->leftJoin('store as s','s.id', '=', 'pco.store_id')               
            ->leftJoin('coupon_items as ci','ci.id', '=', 'pco.coupon_item_id')         
            ->leftJoin('coupon as c','c.id', '=', 'ci.coupon_id')        
            ->leftJoin('users as u2','u2.id', '=', 'pco.cancel_user_id')                
            ->leftJoin('pos_customer_address as pca','pca.id', '=', 'pco.address_id')        
            ->leftJoin('state_list as s1','s1.id', '=', 'pca.state_id')                
            ->leftJoin('state_list as s2','s2.id', '=', 'pco.bill_state_id')        
            ->where('pco.id',$order_id)
            ->where('pco.is_deleted',0)
            ->select('pco.*','pc.customer_name','u1.name as store_user_name','s.store_name','s.store_id_code','pc.phone as customer_phone',
            'pc.salutation','ci.coupon_no','c.discount as coupon_discount','u2.name as cancel_user_name',
            'pca.full_name','pca.address','pca.locality','pca.city_name','pca.postal_code','pca.state_id','s1.state_name','s2.state_name as bill_state_name')->first();
            
            $pos_order_products = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')
            //->leftJoin('purchase_order_items as poi',function($join){$join->on('ppm.product_sku', '=', 'poi.product_sku')->on('poi.order_id','=','ppmi.po_id');})                
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')   
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
            ->leftJoin('store_staff as ss','pcod.staff_id', '=', 'ss.id')        
            ->where('pcod.order_id',$order_id)
            ->where('pcod.is_deleted',0)
            ->select('pcod.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','dlim_1.name as category_name','dlim_2.name as subcategory_name',
            'dlim_3.name as color_name','psc.size as size_name','ppmi.peice_barcode','ss.name as staff_name','ppmi.po_id')->get()->toArray();
            
            for($i=0;$i<count($pos_order_products);$i++){
                $vendor_sku = \DB::table('purchase_order_items')->where('product_sku',$pos_order_products[$i]->product_sku)->where('order_id',$pos_order_products[$i]->po_id)->select('vendor_sku')->first();
                $pos_order_products[$i]->vendor_sku = !empty($vendor_sku->vendor_sku)?$vendor_sku->vendor_sku:'';
            }
            
            $payment_types = Pos_customer_orders_payments::where('order_id',$order_id)->where('is_deleted',0)->get()->toArray();
            
            if($user->user_type == 9 && $store_data->id != $pos_order_data->store_id){
                $error_message = 'Order does not exists in '.$store_data->store_name;
            }
            
            return view('store/pos_order_detail',array('error_message'=>$error_message,'pos_order_data'=>$pos_order_data,'pos_order_products'=>$pos_order_products,'payment_types'=>$payment_types));
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return view('store/pos_order_detail',array('error_message'=>$e->getMessage().', Line:'.$e->getLine()));
        }  
    }
    
    public function holdPosOrder(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            
            $validationRules = array('hold_bills_count'=>'Required');
            $attributes = array('hold_bills_count'=>'Bills Count');

            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            } 
            
            $bills_count = trim($data['hold_bills_count']);
            $store_user = CommonHelper::getUserStoreData($user->id); 
            
            \DB::beginTransaction();
            
            for($i=1;$i<=$bills_count;$i++){
                $order_no = CommonHelper::getPosOrderNo($store_user);
                $order_exists =  Pos_customer_orders::where('order_no',$order_no)->select('order_no')->first();
                if(!empty($order_exists)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Order No', 'errors' => 'Error in creating Order No'));
                }

                $insertArray = array('customer_id'=>null,'store_id'=>$store_user->id,'payment_method'=>null,'store_user_id'=>$user->id,'total_price'=>null,
                'reference_no'=>null,'total_items'=>null,'order_no'=>$order_no,'order_source'=>'web','order_status'=>2);

                $pos_customer_order = Pos_customer_orders::create($insertArray); 
            }
            
            \DB::commit();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Bills saved successfully'),200);
            
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage().', Line:  '.$e->getLine()),500);
        }  
    }
    
    public function cancelPosOrder(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            
            $validationRules = array('comments_cancel_order'=>'Required','order_id'=>'Required');
            $attributes = array('comments_cancel_order'=>'Comments','order_id'=>'Order ID');

            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            } 

            $order_id = trim($data['order_id']);

            \DB::beginTransaction();

            $order_items = Pos_customer_orders_detail::join('pos_product_master_inventory as ppmi','pos_customer_orders_detail.inventory_id', '=', 'ppmi.id')
            ->where('pos_customer_orders_detail.order_id',$order_id)->where('pos_customer_orders_detail.is_deleted',0)
            ->select('pos_customer_orders_detail.*','ppmi.product_status')        
            ->get()->toArray();

            $updateArray = array('order_status'=>3,'cancel_comments'=>trim($data['comments_cancel_order']),'cancel_date'=>date('Y/m/d H:i:s'),'cancel_user_id'=>$user->id);
            Pos_customer_orders::where('id',$order_id)->update($updateArray);

            $updateArray = array('order_status'=>3);
            Pos_customer_orders_detail::where('order_id',$order_id)->update($updateArray);
            Pos_customer_orders_payments::where('order_id',$order_id)->update($updateArray);

            for($i=0;$i<count($order_items);$i++){
                if($order_items[$i]['product_status'] == 4){
                    // Return product which was returned to store in this order, now updated to be in previous order
                    $previous_order = Pos_customer_orders_detail::where('inventory_id',$order_items[$i]['inventory_id'])->where('order_id','!=',$order_id)->orderBy('id','DESC')->first();
                    if(!empty($previous_order)){
                        $updateArray = ['product_status'=>5,'customer_order_id'=>$previous_order->order_id,'store_sale_date'=>$previous_order->created_at];
                        Pos_product_master_inventory::where('id',$order_items[$i]['inventory_id'])->update($updateArray);
                    }else{
                        //throw new \Exception('Error in Order Cancellation. Previous Order of Return Product Not Found');
                        return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Error in Order Cancellation. Previous Order of Return Product Not Found'),200);
                    }
                }else{
                    // Sold product added to store inventory
                    $updateArray = array('product_status'=>4,'customer_order_id'=>null,'store_sale_date'=>null);
                    Pos_product_master_inventory::where('id',$order_items[$i]['inventory_id'])->update($updateArray);
                }
            }

            \DB::commit();
            
            CommonHelper::createLog('POS Order Cancelled. Order ID: '.$order_id,'POS_ORDER_CANCELLED','POS_ORDER');

            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Order cancelled successfully'),200);
            
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage().', Line:  '.$e->getLine()),500);
        }  
    }
    
    public function posOrderEdit(Request $request,$id){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            $order_id = $id;
            
            if(isset($data['action']) && $data['action'] == 'get_payment_method_data'){
                $payment_data = Pos_customer_orders_payments::where('order_id',$data['order_id'])->get()->toArray();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Payment method data','payment_data'=>$payment_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'get_order_product_data'){
                
                $product_data =  \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
                ->join('production_size_counts as psc','psc.id', '=', 'ppm.size_id')        
                ->join('design_lookup_items_master as dlim','dlim.id', '=', 'ppm.color_id')                
                ->where('pcod.id',$data['id'])
                ->where('ppm.is_deleted',0)        
                ->select('pcod.*','ppm.product_name','psc.size as size_name','dlim.name as color_name')        
                ->first();        
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product data','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_order_product_data'){
                
                $validationRules = array('discount_edit_type'=>'Required','gst_type'=>'Required');
                $attributes = array('discount_edit_type'=>'Discount Type','gst_type'=>'GST Type');
                
                if(isset($data['discount_edit_type']) && !empty($data['discount_edit_type'])){
                    if($data['discount_edit_type'] == 'percent'){
                        $validationRules['discount_percent'] = 'Required|numeric';
                    }else{
                        $validationRules['discount_amount'] = 'Required|numeric';
                    }
                }

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                } 
                
                $product_data =  \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
                ->where('pcod.id',$data['id'])
                ->where('ppm.is_deleted',0)        
                ->select('pcod.*','ppm.hsn_code')        
                ->first();        
                
                $order_id = trim($data['order_id']);
                
                $sale_price = $product_data->sale_price;
                         
                if($data['discount_edit_type'] == 'percent'){
                    $discount_percent = trim($data['discount_percent']);
                }else{
                    $discount_percent = (trim($data['discount_amount']) > 0)?round(($data['discount_amount']/$sale_price)*100,6):0;
                }
                
                if($discount_percent >= 100 ){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Discount should be less than 100%', 'errors' => 'Discount should be less than 100%' ));
                }
                
                $discount_amount = (empty($discount_percent))?0:round(($sale_price*($discount_percent/100)),6);
                $discounted_price = $sale_price-$discount_amount;
                $gst_type = trim(strtolower($data['gst_type']));
                
                $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$discounted_price);
                if(!empty($gst_data)){
                    $gst_percent = $gst_data->rate_percent;
                }else{
                    $gst_percent = ($discounted_price >= 1000)?12:5;
                }
                
                if($gst_type == 'inclusive'){
                    $discounted_price_orig = $discounted_price;
                    $gst_percent_1 = 100+$gst_percent;
                    $gst_percent_1 = $gst_percent_1/100;
                    $discounted_price = round($discounted_price/$gst_percent_1,6);

                    $gst_amount = $discounted_price_orig-$discounted_price;
                    $net_price = $discounted_price_orig; 
                }else{
                    $gst_amount = (!empty($gst_percent))?round($discounted_price*($gst_percent/100),6):0;
                    $net_price = $discounted_price+$gst_amount;
                }
                
                if(isset($data['calculate']) && $data['calculate'] == 1){
                    $calculate_data = array('discount_percent'=>$discount_percent,'discount_amount'=>$discount_amount,'discounted_price'=>$discounted_price,'gst_percent'=>$gst_percent,'gst_amount'=>$gst_amount,'gst_type'=>$gst_type,'net_price'=>$net_price);
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Calculate data','calculate_data'=>$calculate_data),200);
                }
                
                $order_total_prev = Pos_customer_orders_detail::where('order_id',$order_id)->where('is_deleted',0)->selectRaw('SUM(net_price) as net_price_total')->first();
                
                \DB::beginTransaction();
                
                $discounted_price = $sale_price-$discount_amount;
                $discounted_price_actual = ($gst_type == 'inclusive')?$discounted_price-$gst_amount:$discounted_price;        
                $discount_amount_actual =  ($gst_type == 'inclusive')?$discount_amount+$gst_amount:$discount_amount;  
                $gst_inclusive = ($gst_type == 'inclusive')?1:0;
                
                $updateArray = array('net_price'=>$net_price,'discount_percent'=>$discount_percent,
                'discount_amount'=>$discount_amount,'gst_percent'=>$gst_percent,'gst_amount'=>$gst_amount,'gst_inclusive'=>$gst_inclusive,
                'discounted_price'=>$discounted_price,'discounted_price_actual'=>$discounted_price_actual,'discount_amount_actual'=>$discount_amount_actual);
                
                Pos_customer_orders_detail::where('id',$data['id'])->update($updateArray);
                
                $order_total = Pos_customer_orders_detail::where('order_id',$order_id)->where('is_deleted',0)->selectRaw('SUM(net_price) as net_price_total,COUNT(id) as total_products')->first();
                $updateArray = array('total_price'=>$order_total->net_price_total,'total_items'=>$order_total->total_products);
                Pos_customer_orders::where('id',$order_id)->update($updateArray);
                
                $this->updateOrderPaymentData($order_id,$order_total_prev->net_price_total,$order_total->net_price_total);
                
                \DB::commit();
                
                CommonHelper::createLog('POS Order Updated. Order ID: '.$order_id,'POS_ORDER_UPDATED','POS_ORDER');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Order updated successfuly'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'delete_order_product'){
                \DB::beginTransaction();
                
                $order_id = trim($data['order_id']);
                
                $order_items = Pos_customer_orders_detail::where('order_id',$order_id)->where('is_deleted',0)->selectRaw('*')->get()->toArray();
                
                if(count($order_items) <= 1){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Order have only one product. You can cancel this order', 'errors' => 'Order have only one product. You can cancel this order' ));
                }
                
                $order_total_prev = Pos_customer_orders_detail::where('order_id',$order_id)->where('is_deleted',0)->selectRaw('SUM(net_price) as net_price_total')->first();
                
                for($i=0;$i<count($order_items);$i++){
                    if($order_items[$i]['id'] == $data['id']){
                        $order_item = $order_items[$i];
                    }
                }
                
                $updateArray = array('is_deleted'=>1);
                Pos_customer_orders_detail::where('id',$data['id'])->update($updateArray);
                
                $order_total = Pos_customer_orders_detail::where('order_id',$order_id)->where('is_deleted',0)->selectRaw('SUM(net_price) as net_price_total,COUNT(id) as total_products')->first();
                $updateArray = array('total_price'=>$order_total->net_price_total,'total_items'=>$order_total->total_products);
                Pos_customer_orders::where('id',$order_id)->update($updateArray);
                
                $updateArray = array('product_status'=>4,'customer_order_id'=>null,'store_sale_date'=>null);
                Pos_product_master_inventory::where('id',$order_item['inventory_id'])->update($updateArray);
                
                $this->updateOrderPaymentData($order_id,$order_total_prev->net_price_total,$order_total->net_price_total);
                
                CommonHelper::createLog('POS Order Updated. Order ID: '.$order_id,'POS_ORDER_UPDATED','POS_ORDER');
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Order updated successfuly'),200);
            }
            
            if(isset($data['action']) && in_array($data['action'],['get_order_product_data_qr_code','add_order_product'])){
                $validationRules = array('qr_code'=>'Required');
                $attributes = array('qr_code'=>'QR Code');
                
                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                } 
                
                $qr_code = trim($data['qr_code']);
                $order_id = trim($data['order_id']);
                
                $inv_data =  \DB::table('pos_product_master_inventory as ppmi')
                ->where('ppmi.peice_barcode',$qr_code)
                ->where('ppmi.is_deleted',0)        
                ->where('ppmi.fake_inventory',0)                
                ->select('ppmi.*')        
                ->first();
                
                if(empty($inv_data)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product does not exists', 'errors' => 'Product does not exists' ));
                }
                
                if($inv_data->product_status != 4){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product status is not ready for sale', 'errors' => 'Product status is not ready for sale' ));
                }
                
                $order_data =  \DB::table('pos_customer_orders as pco')
                ->join('store as s','s.id', '=', 'pco.store_id')    
                ->where('pco.id',$order_id)                 
                ->where('pco.is_deleted',0)          
                ->select('pco.*','s.store_name')        
                ->first();        
                
                if($order_data->store_id != $inv_data->store_id){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product does not exists in '.$order_data->store_name.' store', 'errors' => 'Product does not exists in '.$order_data->store_name.' store' ));
                }
               
                $store_user_data =  \DB::table('store_users')->where('store_id',$inv_data->store_id)->where('is_deleted',0)->first();
                
                $product_data = CommonHelper::getPosProductData($qr_code,'',$store_user_data->user_id);
                
                $discount_percent = $product_data->discount_percent;
                $discount_amount = (empty($discount_percent))?0:round(($product_data->sale_price*($discount_percent/100)),6);
                $discounted_price = $product_data->sale_price-$discount_amount;
                $gst_percent = $product_data->gst_percent;
                
                if($product_data->gst_inclusive == 1){
                    $discounted_price_orig = $discounted_price;
                    $gst_percent_1 = 100+$gst_percent;
                    $gst_percent_1 = $gst_percent_1/100;
                    $discounted_price = round($discounted_price/$gst_percent_1,6);

                    $gst_amount = $discounted_price_orig-$discounted_price;
                    $net_price = $discounted_price_orig; 
                }else{
                    $gst_amount = (!empty($gst_percent))?round($discounted_price*($gst_percent/100),6):0;
                    $net_price = $discounted_price+$gst_amount;
                }
                
                $product_data->discount_amount = $discount_amount;
                $product_data->discounted_price = $product_data->sale_price-$discount_amount;;
                $product_data->gst_amount = $gst_amount;
                $product_data->net_price = $net_price;
                
                if($data['action'] == 'get_order_product_data_qr_code'){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product data','product_data'=>$product_data),200);
                }
                
                $order_total_prev = Pos_customer_orders_detail::where('order_id',$order_id)->where('is_deleted',0)->selectRaw('SUM(net_price) as net_price_total')->first();
                
                \DB::beginTransaction();
                
                $discounted_price_actual = ($product_data->gst_inclusive == 1)?$product_data->discounted_price-$product_data->gst_amount:$product_data->discounted_price;        
                $discount_amount_actual =  ($product_data->gst_inclusive == 1)?$product_data->discount_amount+$product_data->gst_amount:$product_data->discount_amount;               
                
                $insertArray = array('order_id'=>$order_id,'store_id'=>$product_data->store_id,'product_id'=>$product_data->product_master_id,'inventory_id'=>$product_data->id,'base_price'=>$product_data->base_price,
                'sale_price'=>$product_data->sale_price,'net_price'=>$product_data->net_price,'discount_percent'=>$product_data->discount_percent,'discount_id'=>$product_data->discount_id,
                'discount_amount'=>$product_data->discount_amount,'gst_percent'=>$product_data->gst_percent,'gst_amount'=>$product_data->gst_amount,'gst_inclusive'=>$product_data->gst_inclusive,
                'staff_id'=>null,'discounted_price'=>$product_data->discounted_price,'discounted_price_actual'=>$discounted_price_actual,'discount_amount_actual'=>$discount_amount_actual,
                'arnon_prod_inv'=>$product_data->arnon_inventory,'coupon_discount_percent'=>null,'coupon_item_id'=>null,'product_sku_id'=>$product_data->product_sku_id,'po_item_id'=>$product_data->po_item_id,'vendor_id'=>$product_data->vendor_id);

                Pos_customer_orders_detail::create($insertArray);
                
                $updateArray = array('product_status'=>5,'customer_order_id'=>$order_id,'store_sale_date'=>Carbon::now());
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray);
                
                $order_total = Pos_customer_orders_detail::where('order_id',$order_id)->where('is_deleted',0)->selectRaw('SUM(net_price) as net_price_total,COUNT(id) as total_products')->first();
                $updateArray = array('total_price'=>$order_total->net_price_total,'total_items'=>$order_total->total_products);
                Pos_customer_orders::where('id',$order_id)->update($updateArray);
                
                $this->updateOrderPaymentData($order_id,$order_total_prev->net_price_total,$order_total->net_price_total);
                
                // Update date of added rows
                $updateArray = array('created_at'=>$order_data->created_at,'updated_at'=>$order_data->created_at);
                Pos_customer_orders_detail::where('order_id',$order_id)->update($updateArray);
                Pos_customer_orders_payments::where('order_id',$order_id)->update($updateArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Order updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_payment_method_data'){
                $payment_data = Pos_customer_orders_payments::where('order_id',$data['order_id'])->get()->toArray();
                $order_data = Pos_customer_orders::where('id',$data['order_id'])->first();
                
                /*if($data['payment_amount_cash_edit'] > 0 && $data['payment_received_cash_edit'] < $data['payment_amount_cash_edit']){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Cash Payment Received should not be less than Payment Amount', 'errors' => 'Cash Payment Received should not be less than Payment Amount' ));
                }
                
                if($data['payment_amount_card_edit'] > 0 && $data['payment_received_card_edit'] < $data['payment_amount_card_edit']){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Card Payment Received should not be less than Payment Amount', 'errors' => 'Card Payment Received should not be less than Payment Amount' ));
                }
                
                if($data['payment_amount_ewallet_edit'] > 0 && $data['payment_received_ewallet_edit'] < $data['payment_amount_ewallet_edit']){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'E-Wallet Payment Received should not be less than Payment Amount', 'errors' => 'E-Wallet Payment Received should not be less than Payment Amount' ));
                }*/
                
                $total_payment_amount = $data['payment_amount_cash_edit']+$data['payment_amount_card_edit']+$data['payment_amount_ewallet_edit']+$order_data->voucher_amount;
                if(ceil($total_payment_amount) != ceil($order_data->total_price) && floor($total_payment_amount) != floor($order_data->total_price)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Total payment amount: '.$total_payment_amount.' is not equal to Order Total: '.$order_data->total_price, 'errors' => 'Total payment amount: '.$total_payment_amount.' is not equal to Order Total: '.$order_data->total_price ));
                }
                
                $total_payment_received = $data['payment_received_cash_edit']+$data['payment_received_card_edit']+$data['payment_received_ewallet_edit']+$order_data->voucher_amount;
                if(ceil($total_payment_amount) != ceil($total_payment_received) && floor($total_payment_amount) != floor($total_payment_received)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Total payment amount is not equal to Total received amount', 'errors' => 'Total payment amount is not equal to Total received amount' ));
                }
                
                if($data['payment_amount_ewallet_edit'] > 0 && empty($data['reference_no_ewallet_edit'])){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'E-Wallet Reference number is required field', 'errors' => 'E-Wallet Reference number is required field' ));
                }
                
                $cash_payment = CommonHelper::getArrayRecord($payment_data, 'payment_method', 'cash');
                if(!empty($cash_payment)){
                    $updateArray = array('payment_amount'=>$data['payment_amount_cash_edit'],'payment_received'=>$data['payment_received_cash_edit']);
                    Pos_customer_orders_payments::where('id',$cash_payment['id'])->update($updateArray);
                }else{
                    if($data['payment_amount_cash_edit'] > 0 && $data['payment_received_cash_edit'] > 0){
                        $insertArray = array('order_id'=>$order_data->id,'store_id'=>$order_data->store_id,'payment_method'=>'Cash','payment_amount'=>$data['payment_amount_cash_edit'],'payment_received'=>$data['payment_received_cash_edit']);
                        Pos_customer_orders_payments::create($insertArray);
                    }
                }
                
                $card_payment = CommonHelper::getArrayRecord($payment_data, 'payment_method', 'card');
                if(!empty($card_payment)){
                    $updateArray = array('payment_amount'=>$data['payment_amount_card_edit'],'payment_received'=>$data['payment_received_card_edit']);
                    Pos_customer_orders_payments::where('id',$card_payment['id'])->update($updateArray);
                }else{
                    if($data['payment_amount_card_edit'] > 0 && $data['payment_received_card_edit'] > 0){
                        $insertArray = array('order_id'=>$order_data->id,'store_id'=>$order_data->store_id,'payment_method'=>'Card','payment_amount'=>$data['payment_amount_card_edit'],'payment_received'=>$data['payment_received_card_edit']);
                        Pos_customer_orders_payments::create($insertArray);
                    }
                }
                
                $ewallet_payment = CommonHelper::getArrayRecord($payment_data, 'payment_method', 'e-wallet');
                if(!empty($ewallet_payment)){
                    $updateArray = array('payment_amount'=>$data['payment_amount_ewallet_edit'],'payment_received'=>$data['payment_received_ewallet_edit'],'reference_number'=>$data['reference_no_ewallet_edit']);
                    Pos_customer_orders_payments::where('id',$ewallet_payment['id'])->update($updateArray);
                }else{
                    if($data['payment_amount_ewallet_edit'] > 0 && $data['payment_received_ewallet_edit'] > 0){
                        $insertArray = array('order_id'=>$order_data->id,'store_id'=>$order_data->store_id,'payment_method'=>'E-Wallet','payment_amount'=>$data['payment_amount_ewallet_edit'],'payment_received'=>$data['payment_received_ewallet_edit'],'reference_number'=>$data['reference_no_ewallet_edit']);
                        Pos_customer_orders_payments::create($insertArray);
                    }
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Payment method updated','payment_data'=>$payment_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'edit_customer_data'){
                $validationRules = array('customer_salutation'=>'Required','customer_name'=>'Required');
                $attributes = [];
                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                } 
                
                $order_data = Pos_customer_orders::where('id',$order_id)->first();
                $updateArray = ['salutation'=>trim($data['customer_salutation']),'customer_name'=>trim($data['customer_name'])];
                Pos_customer::where('id',$order_data['customer_id'])->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Customer updated successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_order_product_staff'){
                $validationRules = array('staff_id'=>'Required');
                $attributes = ['staff_id'=>'Staff'];
                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                } 
                
                $updateArray = ['staff_id'=>trim($data['staff_id'])];
                Pos_customer_orders_detail::where('id',$data['id'])->where('order_id',$data['order_id'])->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Staff updated successfully'),200);
            }
            
            $pos_order_data = \DB::table('pos_customer_orders as pco')
            ->leftJoin('pos_customer as pc','pc.id', '=', 'pco.customer_id')
            ->join('users as u1','u1.id', '=', 'pco.store_user_id')        
            ->leftJoin('store as s','s.id', '=', 'pco.store_id')               
            ->where('pco.id',$order_id)
            ->where('pco.is_deleted',0)
            ->wherein('pco.order_status',[1,2])        
            ->select('pco.*','pc.customer_name','u1.name as store_user_name','s.store_name','pc.phone as customer_phone','pc.salutation')->first();
            
            $pos_order_products = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')
            //->leftJoin('purchase_order_items as poi',function($join){$join->on('ppm.product_sku', '=', 'poi.product_sku')->on('poi.order_id','=','ppmi.po_id');})                
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')   
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
            ->leftJoin('store_staff as ss','pcod.staff_id', '=', 'ss.id')                
            ->where('pcod.order_id',$order_id)
            ->where('pcod.is_deleted',0)
            ->wherein('pcod.order_status',[1,2])        
            ->select('pcod.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','dlim_1.name as category_name','dlim_2.name as subcategory_name',
            'dlim_3.name as color_name','psc.size as size_name','ppmi.peice_barcode','ss.name as staff_name')->get()->toArray();
            
            $payment_types = Pos_customer_orders_payments::where('order_id',$order_id)->where('is_deleted',0)->get()->toArray();
            $store_staff = Store_staff::where('store_id',$pos_order_data->store_id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return view('store/pos_order_edit',array('error_message'=>$error_message,'pos_order_data'=>$pos_order_data,'pos_order_products'=>$pos_order_products,'payment_types'=>$payment_types,'user'=>$user,'store_staff'=>$store_staff));
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('store/pos_order_edit',array('error_message'=>$e->getMessage()));
            }
        }  
    }
    
    function updateOrderPaymentData($order_id,$net_price_prev,$net_price_current){
        if($net_price_current == $net_price_prev){
            return false;
        }
        
        $cash_payment_data = $card_payment_data = $ewallet_payment_data = array();
        $order_payments = Pos_customer_orders_payments::where('order_id',$order_id)->where('is_deleted',0)->get()->toArray();
        
        for($i=0;$i<count($order_payments);$i++){
            if(strtolower($order_payments[$i]['payment_method']) == 'cash'){
                $cash_payment_data = $order_payments[$i];
            }elseif(strtolower($order_payments[$i]['payment_method']) == 'card'){
                $card_payment_data = $order_payments[$i];
            }elseif(strtolower($order_payments[$i]['payment_method']) == 'e-wallet'){
                $ewallet_payment_data = $order_payments[$i];
            }
        }
        
        if($net_price_current > $net_price_prev){
            $payment_added = $net_price_current-$net_price_prev;
            
            if(!empty($cash_payment_data)){
                $updateArray = array('payment_amount'=>($cash_payment_data['payment_amount']+$payment_added),'payment_received'=>($cash_payment_data['payment_received']+$payment_added));
                Pos_customer_orders_payments::where('id',$cash_payment_data['id'])->where('is_deleted',0)->update($updateArray);
            }elseif(!empty($card_payment_data)){
                $updateArray = array('payment_amount'=>($card_payment_data['payment_amount']+$payment_added),'payment_received'=>($card_payment_data['payment_received']+$payment_added));
                Pos_customer_orders_payments::where('id',$card_payment_data['id'])->where('is_deleted',0)->update($updateArray);
            }elseif(!empty($ewallet_payment_data)){
                $updateArray = array('payment_amount'=>($ewallet_payment_data['payment_amount']+$payment_added),'payment_received'=>($ewallet_payment_data['payment_received']+$payment_added));
                Pos_customer_orders_payments::where('id',$ewallet_payment_data['id'])->where('is_deleted',0)->update($updateArray);
            }
        }else{
            $payment_subtracted = $net_price_prev-$net_price_current;
            $payment_decrease_left = $payment_subtracted;
            
            if(!empty($cash_payment_data)){
                $payment_decrease = ($cash_payment_data['payment_amount']>=$payment_decrease_left)?$payment_decrease_left:$cash_payment_data['payment_amount'];
                $payment_decrease_left = $payment_decrease_left-$payment_decrease;
                
                $updateArray = array('payment_amount'=>($cash_payment_data['payment_amount']-$payment_decrease),'payment_received'=>($cash_payment_data['payment_received']-$payment_decrease));
                Pos_customer_orders_payments::where('id',$cash_payment_data['id'])->where('is_deleted',0)->update($updateArray);
            }
            if(!empty($card_payment_data) && $payment_decrease_left > 0){
                $payment_decrease = ($card_payment_data['payment_amount']>=$payment_decrease_left)?$payment_decrease_left:$card_payment_data['payment_amount'];
                $payment_decrease_left = $payment_decrease_left-$payment_decrease;
                
                $updateArray = array('payment_amount'=>($card_payment_data['payment_amount']-$payment_decrease),'payment_received'=>($card_payment_data['payment_received']-$payment_decrease));
                Pos_customer_orders_payments::where('id',$card_payment_data['id'])->where('is_deleted',0)->update($updateArray);
            }
            if(!empty($ewallet_payment_data) && $payment_decrease_left > 0){
                $payment_decrease = ($ewallet_payment_data['payment_amount']>=$payment_decrease_left)?$payment_decrease_left:$ewallet_payment_data['payment_amount'];
                
                $updateArray = array('payment_amount'=>($ewallet_payment_data['payment_amount']-$payment_decrease),'payment_received'=>($ewallet_payment_data['payment_received']-$payment_decrease));
                Pos_customer_orders_payments::where('id',$ewallet_payment_data['id'])->where('is_deleted',0)->update($updateArray);
            }
        }
    }
    
    function posOrderInvoice(Request $request,$id){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(600);
            $data = $request_data = $request->all();            
            $error_message = '';
            $order_id = $id;
            
            return CommonHelper::posOrderInvoice($data,$order_id);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }
    }
    
    function posBillingUpdated(Request $request){
        try{
            $user = Auth::user();
            $data = $request->all();
            
            $store_data = CommonHelper::getUserStoreData($user->id);
            
            $store_staff = Store_staff::where('store_id',$store_data->id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            return view('store/pos_billing_updated',array('error_message'=>'','store_staff'=>$store_staff));
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('store/pos_billing_updated',array('error_message'=>$e->getMessage()));
        }
    }
    
    function posProductDetailByBarcodeUpdated(Request $request){
        try{
            //\DB::enableQueryLog();
            $data = $request->all();
            $user = Auth::user();
            $barcode = trim($data['barcode']);
            $ids = trim($data['ids']);
            
            $product_data = CommonHelper::getPosProductDataUpdated($barcode,$ids,$user->id);
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product data','product_data' => $product_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function posBillingProductsDetail(Request $request){
         try{
            $data = $request->all();
            $user = Auth::user();
            $ids = explode(',',trim($data['ids']));
            $store_data = CommonHelper::getUserStoreData($user->id);
            
            $products_data = CommonHelper::posBillingProductsData($ids,$store_data);
            if(isset($products_data['error_msg']) && !empty($products_data['error_msg'])){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' =>$products_data['error_msg']),200);
            }else{
                $response_data = array_merge($products_data,array('httpStatus'=>200, 'dateTime'=>time(),'status'=>'success','message'=>'Products data'));
            }
            
            return response($response_data,200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    public function createPosOrderUpdated(Request $request){
        try{
            $data = $request->all();   
            $user = Auth::user();
           
            if(isset($data['action']) && $data['action'] == 'check_exist_customer'){
                return CommonHelper::getPosCustomerData($data);
            }
            
            $customer_type = trim($data['billing_customer_type']);
           
            $validateionRules = array('customer_phone_new'=>'Required','customer_email'=>'nullable|email|unique:pos_customer,email,'.$data['customer_id'],'customer_salutation'=>'Required','customer_name'=>'Required','customer_postal_code'=>'max:50','customer_wedding_date'=>'nullable|date','customer_dob'=>'nullable|date');
            $attributes = array('customer_phone_new'=>'Customer Phone','customer_salutation'=>'salutation','customer_name'=>'Name');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            } 
            
            \DB::beginTransaction();
            
            if($customer_type == 'existing'){
                if(!empty($data['customer_wedding_date'])){ 
                    $date_wedding_arr = explode('-',$data['customer_wedding_date']);
                    $data['customer_wedding_date'] = $date_wedding_arr[2].'-'.$date_wedding_arr[1].'-'.$date_wedding_arr[0];
                }
                if(!empty($data['customer_dob'])){ 
                    $date_dob_arr = explode('-',$data['customer_dob']);
                    $data['customer_dob'] = $date_dob_arr[2].'-'.$date_dob_arr[1].'-'.$date_dob_arr[0];
                }
                
                CommonHelper::updatePosCustomer($data);
                $customer_id = $data['customer_id'];
            }else{
                
                $response_data = CommonHelper::addPosCustomer($data);
                if(strtolower($response_data['status']) == 'success'){
                    $pos_customer = $response_data['customer_data'];
                    $customer_id = $pos_customer->id;
                }else{
                    return response($response_data);
                }
            }
            
            $data['customer_id'] = $customer_id;
            $data['user_id'] = $user->id;
            $data['order_source'] = 'web';
            
            $response_data = CommonHelper::createPosOrderUpdated($data);
            
            \DB::commit();
            
            return $response_data;
            
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage().', Line: '.$e->getLine()),500);
        }  
    }
    
    public function listPosCustomer(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            
            $pos_customers = \DB::table('pos_customer_orders as pco')
            ->join('pos_customer as pc','pc.id', '=', 'pco.customer_id')
            ->join('store as s','s.id', '=', 'pco.store_id')              
            ->where('pc.is_deleted',0);
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $pos_customers = $pos_customers->where('s.id',trim($data['s_id']));
            }
            
            if(isset($data['cust_name']) && !empty($data['cust_name'])){
                $cust_name = trim($data['cust_name']);
                $pos_customers = $pos_customers->whereRaw("(pc.customer_name LIKE '%$cust_name%' OR pc.phone LIKE '%$cust_name%')");
            }
            
            if(isset($data['id']) && !empty($data['id'])){
                $pos_customers = $pos_customers->where('pc.id',trim($data['id']));
            }
            
            $pos_customers = $pos_customers 
            ->groupBy('pc.id')        
            ->select('pc.*','s.store_name','s.store_id_code');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $pos_order_cust_count = trim($data['pos_order_cust_count']);
                $pos_order_cust_count_arr = explode('_',$pos_order_cust_count);
                $start = $pos_order_cust_count_arr[0];
                $start = $start-1;
                $end = $pos_order_cust_count_arr[1];
                $limit = $end-$start;
                $pos_customers = $pos_customers->offset($start)->limit($limit)->get();
            }else{
                $pos_customers = $pos_customers->paginate(100);
                $pos_order_cust_count = $pos_customers->total();
            }
            
            // Download csv start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=pos_customers.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Customer ID','Customer Name','Phone','Email','DOB','Wedding Date','Postal Code','Store Name','Store Code');

                $callback = function() use ($pos_customers, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_units = $total_price = 0;
                    for($i=0;$i<count($pos_customers);$i++){
                        $wedding_date = !empty($pos_customers[$i]->wedding_date)?date('d/m/Y',strtotime($pos_customers[$i]->wedding_date)):'';
                        $dob = !empty($pos_customers[$i]->dob)?date('d/m/Y',strtotime($pos_customers[$i]->dob)):'';
                        
                        $array = array($pos_customers[$i]->id,$pos_customers[$i]->salutation.' '.$pos_customers[$i]->customer_name,
                        $pos_customers[$i]->phone,$pos_customers[$i]->email,$dob,$wedding_date,$pos_customers[$i]->postal_code,
                        $pos_customers[$i]->store_name,$pos_customers[$i]->store_id_code);
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            // Download csv end
            
            $store_list = CommonHelper::getStoresList();
            
            return view('store/pos_customer_list',array('error_message'=>$error_message,'pos_customers'=>$pos_customers,'user'=>$user,'store_list'=>$store_list,'pos_order_cust_count'=>$pos_order_cust_count));
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_CUSTOMER',__FUNCTION__,__FILE__);
            return view('store/pos_customer_list',array('error_message'=>$e->getMessage()));
        }  
    }
    
    public function createFakePosOrders(Request $request){
        try{
            $data = $request->all();   
            $user = Auth::user();
            
            $validateionRules = array('fake_order_store_id'=>'Required','fake_order_count'=>'Required','fake_order_date'=>'Required','fake_order_discount'=>'Required','fake_order_gst_type'=>'Required');
            $attributes = array('fake_order_store_id'=>'Store','fake_order_count'=>'Number of Orders','fake_order_date'=>'Order Date','fake_order_discount'=>'Discount','fake_order_gst_type'=>'GST Type');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            } 
            
            \DB::beginTransaction();
            
            $prev_order = $next_order = $next_order_no = null;
            $seconds_diff = $prev_order_id = 0;
            $order_data_list = $orders_created_list = $orders_updated_list = array();
            
            $orders_count = trim($data['fake_order_count']);
            $orders_date = trim($data['fake_order_date']);
            $store_id = trim($data['fake_order_store_id']);
            $orders_discount = trim($data['fake_order_discount']);
            $orders_gst_type = trim($data['fake_order_gst_type']);
            
            $store_user = Store_user::where('store_id',$store_id)->where('is_deleted',0)->first();
            $orders_date_arr = explode('-',str_replace('/','-',$orders_date));
            $orders_date = $orders_date_arr[2].'-'.$orders_date_arr[1].'-'.$orders_date_arr[0];
            $orders_date_year = date('Y',strtotime($orders_date));
            
            $date_updates  = Pos_customer_orders_update::where('store_id',$store_id)->where('order_date',$orders_date)->where('is_deleted',0)->count();
            if($date_updates > 0){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'POS Orders already added for this date', 'errors' => 'POS Orders already added for this date'));
            }
            
            if(strtotime($orders_date) == strtotime(date('Y/m/d')) ||  strtotime($orders_date) > strtotime(date('Y/m/d')) ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'POS Orders cannot be created on current/future date', 'errors' => 'POS Orders cannot be created on current/future date'));
            }
            
            // Fetch last order created before end of order date. Order by created_at as fake order created on previous day has greater id
            // Year condition to not get order data from previous year
            $orders_date_time = $orders_date.' 23:45:00';
            $last_order = Pos_customer_orders::where('store_id',$store_id)->whereRaw("created_at < '$orders_date_time' AND YEAR(created_at) = '".$orders_date_year."'")->where('is_deleted',0)->orderBy('created_at','DESC')->first();
            
            if(empty($last_order)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Orders. Try again later', 'errors' => 'Error in creating Orders. Try again later'));
            }
            
            // Fetch last order on order date, if order not found, then create at 10:00 am
            $order_date_last_order = Pos_customer_orders::where('store_id',$store_id)->whereRaw("DATE(created_at) = '$orders_date'")->where('is_deleted',0)->orderBy('id','DESC')->first();
            $order_date_time = (!empty($order_date_last_order))?date('H:i:s',strtotime($order_date_last_order->created_at)):'10:00:00';
            
            for($i=0;$i<$orders_count;$i++){
                $count = $i+1;
                $seconds_diff_order = ($count*30)+rand(5,10);
                $order_no = ltrim(substr($last_order->order_no,6),0);
                $order_no = substr($last_order->order_no,0,6).str_pad(($order_no+$count),6,'0',STR_PAD_LEFT);
                $order_date = date('Y/m/d H:i:s',strtotime($orders_date.$order_date_time)+($seconds_diff_order));
                $order_data_list[] = array('order_no'=>$order_no,'order_date'=>$order_date);
            }
            
            if(empty($order_data_list)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Orders. Try again later', 'errors' => 'Error in creating Orders. Try again later'));
            }
            
            for($i=0;$i<$orders_count;$i++){
                $store_inventory_ids = array();
                $customer_info = Pos_customer::whereRaw("LENGTH(customer_name) > 3 AND LENGTH(customer_name) < 10 AND POSITION(' ' IN customer_name) = 0 AND POSITION('.' IN customer_name) = 0")
                ->selectRaw('DISTINCT salutation,customer_name')
                ->orderByRaw('RAND()')
                ->first();        
                
                $mobile_no = $this->getOrderMobileNumber();
                $customer_name = ucfirst(strtolower(trim($customer_info['customer_name'])));
                $customer_data = array('customer_phone_new'=>$mobile_no,'customer_salutation'=>$customer_info['salutation'],'customer_name'=>$customer_name,'customer_email'=>null,'customer_postal_code'=>null,'fake_inventory'=>1);
                $response_data = CommonHelper::addPosCustomer($customer_data);

                if(strtolower($response_data['status']) == 'success'){
                    $pos_customer = $response_data['customer_data'];
                    $customer_id = $pos_customer->id;
                }else{
                    return response($response_data);
                }
                
                $inv_count = rand(1,3);
                $store_inventory = Pos_product_master_inventory::where('store_id',$store_id)->where('product_status',4)->where('fake_inventory',1)->where('is_deleted',0)->limit($inv_count)->get()->toArray();
                
                if(empty($store_inventory)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store Inventory is Empty', 'errors' => 'Store Inventory is Empty'));
                }
                
                for($q=0;$q<count($store_inventory);$q++){
                    $store_inventory_ids[] = $store_inventory[$q]['id'];
                }
                $store_inventory_ids = implode(',',$store_inventory_ids);
                
                $order_data = array('ids'=>$store_inventory_ids,'fake_inventory'=>1,'cashAmtValue'=>'','cardAmtValue'=>'','WalletAmtValue'=>'','voucherAmount'=>'');
                $order_data['customer_id'] = $customer_id;
                $order_data['order_date'] = $order_data_list[$i]['order_date'];
                $order_data['order_no'] = $order_data_list[$i]['order_no'];
                $order_data['user_id'] = $store_user->user_id;
                $order_data['discount_percent'] = $orders_discount;
                $order_data['gst_inclusive'] = (strtolower($orders_gst_type) == 'inclusive')?1:0;
                $order_data['store_id'] = $store_id;
                $order_data['order_source'] = 'web';

                $response_data = $response_data_orig = CommonHelper::createPosOrder($order_data);
                $response_data = json_decode(json_encode($response_data),true);
                
                if(isset($response_data['original']['status']) && $response_data['original']['status'] == 'success'){
                    $orders_created_list[] = $response_data['original']['order_data'];
                }else{
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$response_data['original']['message'], 'errors' => $response_data['original']['message']));
                }
            }
            
            // If orders created is not empty and equal to requested orders count
            if(!empty($orders_created_list) && count($orders_created_list) == $orders_count){
                
                $last_added_order = $orders_created_list[count($orders_created_list)-1];
                
                // Fetch orders created after order date.  Year condition to update only current year orders
                $orders_list = Pos_customer_orders::where('store_id',$store_id)->whereRaw("DATE(created_at) > '$orders_date' AND YEAR(created_at) = '".$orders_date_year."'")->where('is_deleted',0)->orderBy('order_no')->select('id','order_no','orig_order_no','fake_inventory')->get()->toArray();
                for($i=0;$i<count($orders_list);$i++){
                    
                    $count = $i+1;
                    $order_no = ltrim(substr($last_added_order['order_no'],6),0);
                    $order_no = substr($last_added_order['order_no'],0,6).str_pad(($order_no+$count),6,'0',STR_PAD_LEFT);
                    
                    $updateArray = array('order_no'=>$order_no);
                    if(empty($orders_list[$i]['orig_order_no']) && $orders_list[$i]['fake_inventory'] == 0){
                        $updateArray['orig_order_no'] = $orders_list[$i]['order_no'];
                    }
                    
                    Pos_customer_orders::where('id',$orders_list[$i]['id'])->update($updateArray);
                    $orders_updated_list[] = array('prev'=>$orders_list[$i]['order_no'],'new'=>$order_no,'id'=>$orders_list[$i]['id']);
                }
                
                $insertArray = array('store_id'=>$store_id,'orders_count'=>$orders_count,'order_date'=>$orders_date);
                $orders_update = Pos_customer_orders_update::create($insertArray);
                
                for($i=0;$i<count($orders_created_list);$i++){
                    $insertArray = array('update_id'=>$orders_update->id,'order_id'=>$orders_created_list[$i]['id'],'order_no_prev'=>null,'order_no_new'=>$orders_created_list[$i]['order_no'],'update_type'=>1);
                    Pos_customer_orders_update_items::create($insertArray);
                }
                
                for($i=0;$i<count($orders_updated_list);$i++){
                    $insertArray = array('update_id'=>$orders_update->id,'order_id'=>$orders_updated_list[$i]['id'],'order_no_prev'=>$orders_updated_list[$i]['prev'],'order_no_new'=>$orders_updated_list[$i]['new'],'update_type'=>2);
                    Pos_customer_orders_update_items::create($insertArray);
                }
                
            }else{
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Orders. Try again later', 'errors' => 'Error in creating Orders. Try again later'));
            }
            
            \DB::commit();
            
            return $response_data_orig;
            
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage().', Line: '.$e->getLine()),500);
        }  
    }
    
    function getOrderMobileNumber(){
	// Generate Unique No
	$mobile_no = rand(8,9);
	for($i=1;$i<=9;$i++){
            if($i<=3) $mobile_no.=rand(1,9); else $mobile_no.=rand(0,9);
	}
        
        while(Pos_customer::where('phone',$mobile_no)->count() > 0){	//check in database
            $mobile_no = rand(8,9);
            for($i=1;$i<=9;$i++){
                if($i<=3) $mobile_no.=rand(1,9); else $mobile_no.=rand(0,9);
            }
	}
	
	return $mobile_no;
    }
    
    public function posOrderSeriesUpdateList(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            $store_list = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if($is_fake_inventory_user){
                $pos_orders_updates = \DB::table('pos_customer_orders_update as pcou')
                ->join('store as s','s.id', '=', 'pcou.store_id')
                ->where('pcou.is_deleted',0);

                if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                    $start_date = date('Y/m/d',strtotime(trim($data['startDate'])));
                    $end_date = date('Y/m/d',strtotime(trim($data['endDate'])));
                    $pos_orders_updates = $pos_orders_updates->whereRaw("pcou.order_date BETWEEN '$start_date' AND '$end_date'");
                }

                if(isset($data['order_no']) && !empty($data['order_no']) ){
                    $pos_orders_updates = $pos_orders_updates->where('pcou.order_no','LIKE','%'.trim($data['order_no']));
                }
                
                if(isset($data['store_id']) && !empty($data['store_id'])){
                    $pos_orders_updates = $pos_orders_updates->where('pcou.store_id',trim($data['store_id']));
                }
               
                $pos_orders_updates = $pos_orders_updates->select('pcou.*','s.store_name')->orderBy('id','DESC')->paginate(100);
            }
            
            $store_list = CommonHelper::getStoresList();
            
            return view('store/pos_order_series_update_list',array('error_message'=>$error_message,'pos_orders_updates'=>$pos_orders_updates,'user'=>$user,'store_list'=>$store_list));
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return view('store/pos_order_series_update_list',array('error_message'=>$e->getMessage(),'pos_orders_updates'=>array()));
        }  
    }
    
    public function posOrderSeriesUpdateDetail(Request $request,$id){
        try{
            $data = $request->all();            
            $update_id = $id;
            $error_message = '';
            $store_list = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if($is_fake_inventory_user){
                $pos_orders_update_data = \DB::table('pos_customer_orders_update as pcou')->join('store as s','s.id', '=', 'pcou.store_id')
                ->where('pcou.id',$update_id)->select('pcou.*','s.store_name')->first();
                
                $pos_orders_updates_detail = \DB::table('pos_customer_orders_update_items as pcoui')
                ->where('pcoui.update_id',$update_id)
                ->where('pcoui.is_deleted',0);

                $pos_orders_updates_detail = $pos_orders_updates_detail->select('pcoui.*')->orderBy('id','ASC')->get()->toArray();
            }
            
            return view('store/pos_order_series_update_detail',array('error_message'=>$error_message,'pos_orders_updates_detail'=>$pos_orders_updates_detail,'pos_orders_update_data'=>$pos_orders_update_data));
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return view('store/pos_order_series_update_detail',array('error_message'=>$e->getMessage(),'pos_orders_updates_detail'=>array()));
        }  
    }
    
    public function savePosOrderDraft(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = $staff_data_str = '';
            $store_list = $staff_data = array();
            $draft_created = null;
            //$inv_barcodes_arr = $data['inv_barcodes_arr'];
            
            $billing_prod_list = $data['billing_prod_list'];
            for($i=0;$i<count($billing_prod_list);$i++){
                $barcode = $billing_prod_list[$i]['peice_barcode'];
                $inv_barcodes_arr[] = $barcode;
                $staff_data[$barcode] = $billing_prod_list[$i]['staff_id'];
            }
            
            sort($inv_barcodes_arr);
            ksort($staff_data);
            
            if(empty($inv_barcodes_arr)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Products list is empty', 'errors' => 'Products list is empty'));
            }
            
            $inv_barcodes_str = implode(',',$inv_barcodes_arr);
            $staff_data_str = implode(',',$staff_data);
            
            $store_user = \DB::table('store as s')->join('store_users as su','s.id', '=', 'su.store_id')->where('su.user_id',$user->id)->select('s.*')->first();
            
            $draft_exists = Pos_customer_orders_drafts::where('store_id',$store_user->id)->where('product_barcodes',$inv_barcodes_str)->where('is_deleted',0)->first();
            
            // Create draft if draft with same barcodes does not exists
            if(empty($draft_exists)){
                $insertArray = array('store_id'=>$store_user->id,'products_count'=>count($inv_barcodes_arr),'product_barcodes'=>implode(',',$inv_barcodes_arr),'staff_data'=>$staff_data_str);
                $draft_created = Pos_customer_orders_drafts::create($insertArray);
            }
            
            // Delete previous drafts if new draft created 
            if(!empty($draft_created)){
                $drafts_list = Pos_customer_orders_drafts::where('store_id',$store_user->id)->where('id','!=',$draft_created->id)->where('is_deleted',0)->get()->toArray();
                
                //Delete previous drafts if they have barcode which are in new draft
                for($i=0;$i<count($inv_barcodes_arr);$i++){
                    $barcode = $inv_barcodes_arr[$i];
                    for($q=0;$q<count($drafts_list);$q++){
                        $draft_barcodes_arr = explode(',',$drafts_list[$q]['product_barcodes']);
                        if(in_array($barcode, $draft_barcodes_arr)){
                            $updateArray = array('is_deleted'=>1);
                            Pos_customer_orders_drafts::where('id',$drafts_list[$q]['id'])->update($updateArray);
                        }
                    }
                }
            }
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Draft saved successfully','draft_data'=>$draft_created),200);
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }  
    }
    
    public function getPosOrderDraftItems(Request $request,$id){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            $draft_id = $id;
            
            $store_user = \DB::table('store as s')->join('store_users as su','s.id', '=', 'su.store_id')->where('su.user_id',$user->id)->select('s.*')->first();
            $draft_data = Pos_customer_orders_drafts::where('store_id',$store_user->id)->where('id',$draft_id)->where('is_deleted',0)->first();
            
            if(empty($draft_data)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Draft does not exists', 'errors' => 'Draft does not exists'));
            }
            
            $draft_products_barcodes = explode(',',$draft_data->product_barcodes);
            $draft_staff_ids = explode(',',$draft_data->staff_data);
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Draft data','products_barcodes'=>$draft_products_barcodes,'staff_ids'=>$draft_staff_ids),200);
            
            }catch (\Exception $e){		
                \DB::rollBack();
                CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
                return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }  
    }
    
    public function listPosOrderDrafts(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            
            $store_user = \DB::table('store as s')->join('store_users as su','s.id', '=', 'su.store_id')->where('su.user_id',$user->id)->select('s.*')->first();
            
            $drafts_list = Pos_customer_orders_drafts::where('store_id',$store_user->id)->where('is_deleted',0)->orderBy('id','DESC')->paginate(100);
            
            return view('store/pos_order_drafts_list',array('error_message'=>$error_message,'drafts_list'=>$drafts_list));
            
            }catch (\Exception $e){		
                CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
                return view('store/pos_order_drafts_list',array('error_message'=>$e->getMessage()));
        }  
    }
    
    public function deletePosOrderDraft(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            $deleteChkArray = $data['deleteChkArray'];
            
            $store_user = \DB::table('store as s')->join('store_users as su','s.id', '=', 'su.store_id')->where('su.user_id',$user->id)->select('s.*')->first();
            
            $drafts_list = Pos_customer_orders_drafts::where('store_id',$store_user->id)->wherein('id',$deleteChkArray)->update(array('is_deleted'=>1));
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Drafts deleted successfully'),200);
            
            }catch (\Exception $e){		
                CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
                return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }  
    }
    
    public function createFakePosOrdersFromCsv(Request $request){
        try{
            $data = $request->all();   
            $user = Auth::user();
            set_time_limit(600);
            $inv_error_msg = '';
            $count = 1;
            $bills_list = $orders_sku_inventory = $db_sku_inventory = array();
            
            // CSV file and store validations
            $validateionRules = array('fakePosOrderCsvFile'=>'required|mimes:csv,txt|max:5120','store_id'=>'required');
            $attributes = array('fakePosOrderCsvFile'=>'CSV File','store_id'=>'Store');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400,'dateTime'=>time(),'status'=>'fail','message'=>'Validation error','errors' => $validator->errors()));
            }	
            
            $store_id = trim($data['store_id']);
            $store_user = Store_user::where('store_id',$store_id)->where('is_deleted',0)->first();
            
            // Upload CSV File Start
            $file = $request->file('fakePosOrderCsvFile');
            $file_name_text = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(),'.'));
            $file_ext = $file->getClientOriginalExtension();
            $dest_folder = 'documents/fake_pos_orders_csv';

            for($i=0;$i<1000;$i++){
                $file_name = ($i == 0)?$file_name_text.'.'.$file_ext:$file_name_text.'_'.$i.'.'.$file_ext;
                if(!file_exists(public_path($dest_folder.'/'.$file_name))){
                    break;
                }
            }

            if(!isset($file_name)){
                $file_name = $file_name_text.'_'.rand(1000,1000000).'.'.$file_ext;
            }

            $file->move(public_path($dest_folder), $file_name);
            // Upload CSV File End
            
            \DB::beginTransaction();
            
            // Create bills list array from csv data
            $file = public_path($dest_folder.'/'.$file_name);
            if(($handle = fopen($file, "r")) !== FALSE) {
                while (($csv_data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $bill_no = trim($csv_data[0]);
                    $bill_date = trim($csv_data[1]);
                    $product_sku = trim($csv_data[2]);
                    $qty = trim($csv_data[3]);
                    $discount = trim($csv_data[4]);
                    $gst_included = trim($csv_data[5]);
                    
                    // CSV data empty validations
                    if(empty($bill_no) || empty($bill_date) || empty($product_sku) || empty($qty) || $discount == '' || $gst_included == ''){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Bill No / Bill Date / SKU /  Quantity / Discount / GST Type is empty in Row '.$count, 'errors' => 'Bill No / Bill Date / SKU /  Quantity / Discount / GST Type is empty in Row '.$count));
                    }
                    
                    if(!(is_numeric($bill_no) && is_numeric($qty) && is_numeric($discount) && is_numeric($gst_included))){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Bill No /  Quantity / Discount / GST Type is not numeric in Row '.$count, 'errors' => 'Bill No /  Quantity / Discount / GST Type is not numeric in Row '.$count));
                    }
                    
                    $bill_date_arr = explode('-',str_replace('/','-',$bill_date));
                    // Date validation
                    if(count($bill_date_arr) != 3){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Date in Row '.$count, 'errors' => 'Invalid Date in Row '.$count));
                    }
                    
                    $bill_date = $bill_date_arr[2].'-'.$bill_date_arr[1].'-'.$bill_date_arr[0];
                    $bill_date = date('Y-m-d',strtotime($bill_date));
                    
                    // Correct date validation
                    if(!checkdate($bill_date_arr[1],$bill_date_arr[0],$bill_date_arr[2])){
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Date in Row '.$count, 'errors' => 'Invalid Date in Row '.$count));
                    }
                    
                    $bills_list[$bill_date][$bill_no][] = array('sku'=>$product_sku,'qty'=>$qty,'discount'=>$discount,'gst_included'=>$gst_included);
                    $count++;
                }
            }
            
            // Validations to check bill date. 
            foreach($bills_list as $date=>$bills){
                
                $date_updates  = Pos_customer_orders_update::where('store_id',$store_id)->where('order_date',$date)->where('is_deleted',0)->count();
                if($date_updates > 0){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'POS Orders already added for this date: '.date('d/m/Y',strtotime($date)), 'errors' => 'POS Orders already added for this date: '.date('d/m/Y',strtotime($date))));
                }

                if(strtotime($date) == strtotime(date('Y/m/d')) ||  strtotime($date) > strtotime(date('Y/m/d')) ){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'POS Orders cannot be created on current/future date', 'errors' => 'POS Orders cannot be created on current/future date'));
                }
                
                $orders_date_time = $date.' 23:45:00';
                $orders_date_year = date('Y',strtotime($date));
                $last_order = Pos_customer_orders::where('store_id',$store_id)->whereRaw("created_at < '$orders_date_time' AND YEAR(created_at) = '".$orders_date_year."'")->where('is_deleted',0)->orderBy('created_at','DESC')->first();

                if(empty($last_order)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Order is not created on or before this date: '.date('d/m/Y',strtotime($date)), 'errors' => 'Order is not created on or before this date: '.date('d/m/Y',strtotime($date))));
                }
                
                // Create array of orders sku inventory
                foreach($bills as $bill_no=>$bill_data){
                    for($i=0;$i<count($bill_data);$i++){
                        $sku = trim($bill_data[$i]['sku']);
                        $qty = trim($bill_data[$i]['qty']);
                        if(isset($orders_sku_inventory[$sku])){
                            $orders_sku_inventory[$sku]+= $qty;
                        }else{
                            $orders_sku_inventory[$sku] = $qty;
                        }
                    }
                }
            }
            
            // SKU Liat
            $sku_list = array_keys($orders_sku_inventory);
            
            // Fetch List of SKU inventory from database
            $inv_sku_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
            ->wherein('ppm.product_sku',$sku_list)        
            ->where('ppmi.product_status',4)        
            ->where('ppmi.store_id',$store_id)                
            ->where('ppmi.is_deleted',0)        
            ->where('ppm.is_deleted',0)
            ->where('ppmi.fake_inventory',1)         
            ->selectRaw('ppm.product_sku,COUNT(ppmi.id) as cnt')        
            ->groupBy('ppm.product_sku')        
            ->get()->toArray();
            
            for($i=0;$i<count($inv_sku_list);$i++){
                $sku = trim($inv_sku_list[$i]->product_sku);
                $qty = trim($inv_sku_list[$i]->cnt);
                $db_sku_inventory[$sku] = $qty;
            }
            
            // Validation to Check if database inventory is less than orders inventory
            foreach($orders_sku_inventory as $order_sku=>$order_qty){
                if(isset($db_sku_inventory[$order_sku])){
                    if($db_sku_inventory[$order_sku] < $orders_sku_inventory[$order_sku]){
                        $inv_error_msg.=$order_sku.': Order Qty: '.$order_qty.', Store Qty: '.$db_sku_inventory[$order_sku].'<br>';
                    }
                }else{
                    $inv_error_msg.=$order_sku.': Order Qty: '.$order_qty.', Store Qty: 0'.'<br>';
                }
            }
            
            // Returns error if database inventory is less than orders inventory
            if(!empty($inv_error_msg)){
                $inv_error_msg = 'Inventory Error: <br>'.$inv_error_msg;
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$inv_error_msg, 'errors' => $inv_error_msg ) );
            }
            
            // Iterate bills list. Date is key and bills on that date are value
            foreach($bills_list as $date=>$bills){
                
                $orders_created_list = $orders_updated_list = array();
                $count = 1;
                
                // Fetch last order created before end of order date. Order by created_at as fake order created on previous day has greater id
                // Year condition to not get order data from previous year
                $orders_date_time = $date.' 23:45:00';
                $orders_date_year = date('Y',strtotime($date));
                $last_order = Pos_customer_orders::where('store_id',$store_id)->whereRaw("created_at < '$orders_date_time' AND YEAR(created_at) = '".$orders_date_year."'")->where('is_deleted',0)->orderBy('created_at','DESC')->first();

                // Fetch last order on order date, if order not found, then create at 10:00 am
                $order_date_last_order = Pos_customer_orders::where('store_id',$store_id)->whereRaw("DATE(created_at) = '$date'")->where('is_deleted',0)->orderBy('id','DESC')->first();
                $order_date_time = (!empty($order_date_last_order))?date('H:i:s',strtotime($order_date_last_order->created_at)):'10:00:00';
                $orders_count = count($bills);
                
                foreach($bills as $bill_no=>$bill_data){
                    $store_inventory_ids = array();
                    
                    // Create order no and order date
                    $seconds_diff_order = ($count*30)+rand(5,10);
                    $order_no = ltrim(substr($last_order->order_no,6),0);
                    $order_no = substr($last_order->order_no,0,6).str_pad(($order_no+$count),6,'0',STR_PAD_LEFT);
                    $order_date = date('Y/m/d H:i:s',strtotime($date.$order_date_time)+($seconds_diff_order));
                    
                    // Get customer info random from database
                    $customer_info = Pos_customer::whereRaw("LENGTH(customer_name) > 3 AND LENGTH(customer_name) < 10 AND POSITION(' ' IN customer_name) = 0 AND POSITION('.' IN customer_name) = 0")
                    ->selectRaw('DISTINCT salutation,customer_name')
                    ->orderByRaw('RAND()')
                    ->first();        
                    
                    // Create customer 
                    $mobile_no = $this->getOrderMobileNumber();
                    $customer_name = ucfirst(strtolower(trim($customer_info['customer_name'])));
                    $customer_data = array('customer_phone_new'=>$mobile_no,'customer_salutation'=>$customer_info['salutation'],'customer_name'=>$customer_name,'customer_email'=>null,'customer_postal_code'=>null,'fake_inventory'=>1);
                    $response_data = CommonHelper::addPosCustomer($customer_data);

                    if(strtolower($response_data['status']) == 'success'){
                        $pos_customer = $response_data['customer_data'];
                        $customer_id = $pos_customer->id;
                    }else{
                        return response($response_data);
                    }

                    // Fetch inventory ids from bill items 
                    for($i=0;$i<count($bill_data);$i++){
                        $inv_list = \DB::table('pos_product_master_inventory as ppmi')
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
                        ->where('ppm.product_sku',$bill_data[$i]['sku'])        
                        ->where('ppmi.product_status',4)        
                        ->where('ppmi.store_id',$store_id)                
                        ->where('ppmi.is_deleted',0)        
                        ->where('ppm.is_deleted',0)
                        ->where('ppmi.fake_inventory',1)         
                        ->select('ppmi.id')        
                        ->limit($bill_data[$i]['qty'])         
                        ->get()->toArray();
                        
                        $inventory_ids = array_column($inv_list,'id');
                        $store_inventory_ids = array_merge($store_inventory_ids,$inventory_ids);
                    }
                    
                    // Create Pos Order
                    $store_inventory_ids = implode(',',$store_inventory_ids);
                    $order_data = array('ids'=>$store_inventory_ids,'fake_inventory'=>1,'cashAmtValue'=>'','cardAmtValue'=>'','WalletAmtValue'=>'','voucherAmount'=>'');
                    $order_data['customer_id'] = $customer_id;
                    $order_data['order_date'] = $order_date;
                    $order_data['order_no'] = $order_no;
                    $order_data['user_id'] = $store_user->user_id;
                    $order_data['store_id'] = $store_id;
                    $order_data['discount_percent'] = $bill_data[0]['discount'];
                    $order_data['gst_inclusive'] = $bill_data[0]['gst_included'];
                    $order_data['order_source'] = 'web';
                   
                    $response_data = $response_data_orig = CommonHelper::createPosOrder($order_data);
                    $response_data = json_decode(json_encode($response_data),true);

                    if(isset($response_data['original']['status']) && $response_data['original']['status'] == 'success'){
                        $orders_created_list[] = $response_data['original']['order_data'];
                    }else{
                        return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$response_data['original']['message'], 'errors' => $response_data['original']['message']));
                    }
                    
                    $count++;
                }
                
                // Resequence bills numbers
                // If orders created is not empty and equal to requested orders count
                //if(!empty($orders_created_list) && count($orders_created_list) == $orders_count){
                if(1){
                    $last_added_order = $orders_created_list[count($orders_created_list)-1];

                    // Fetch orders created after order date.  Year condition to update only current year orders
                    $orders_list = Pos_customer_orders::where('store_id',$store_id)->whereRaw("DATE(created_at) > '$date' AND YEAR(created_at) = '".$orders_date_year."'")->where('is_deleted',0)->orderBy('order_no')->select('id','order_no','orig_order_no','fake_inventory')->get()->toArray();
                    for($i=0;$i<count($orders_list);$i++){

                        $count = $i+1;
                        //$order_no = ltrim(substr($last_added_order['order_no'],6),0);
                        $order_no = substr($last_added_order['order_no'],-6);
                        //$order_no = substr($last_added_order['order_no'],0,6).str_pad(($order_no+$count),6,'0',STR_PAD_LEFT);
                        $order_no = str_replace($order_no,'',$last_added_order['order_no']).str_pad(($order_no+$count),6,'0',STR_PAD_LEFT);

                        $updateArray = array('order_no'=>$order_no);
                        if(empty($orders_list[$i]['orig_order_no']) && $orders_list[$i]['fake_inventory'] == 0){
                            $updateArray['orig_order_no'] = $orders_list[$i]['order_no'];
                        }

                        Pos_customer_orders::where('id',$orders_list[$i]['id'])->update($updateArray);
                        $orders_updated_list[] = array('prev'=>$orders_list[$i]['order_no'],'new'=>$order_no,'id'=>$orders_list[$i]['id']);
                    }

                    $insertArray = array('store_id'=>$store_id,'orders_count'=>$orders_count,'order_date'=>$date);
                    $orders_update = Pos_customer_orders_update::create($insertArray);

                    for($i=0;$i<count($orders_created_list);$i++){
                        $insertArray = array('update_id'=>$orders_update->id,'order_id'=>$orders_created_list[$i]['id'],'order_no_prev'=>null,'order_no_new'=>$orders_created_list[$i]['order_no'],'update_type'=>1);
                        Pos_customer_orders_update_items::create($insertArray);
                    }

                    for($i=0;$i<count($orders_updated_list);$i++){
                        $insertArray = array('update_id'=>$orders_update->id,'order_id'=>$orders_updated_list[$i]['id'],'order_no_prev'=>$orders_updated_list[$i]['prev'],'order_no_new'=>$orders_updated_list[$i]['new'],'update_type'=>2);
                        Pos_customer_orders_update_items::create($insertArray);
                    }

                }else{
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Orders. Try again later ', 'errors' => 'Error in creating Orders. Try again later '));
                }
                
            }
            
            \DB::commit();
            
            fclose($handle);
            unlink($file);
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'POS Orders added successfully'),200);
            
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage().', Line:  '.$e->getLine()),500);
        }      
    }
    
    public function posOrderErrorList(Request $request){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            
            $errors_list = \DB::table('pos_customer_orders_errors as pcoe')
            ->join('store as s','s.id', '=', 'pcoe.store_id')
            ->where('pcoe.is_deleted',0)
            ->select('pcoe.*','s.store_name')
            ->orderBy('pcoe.id','DESC')                
            ->paginate(100);
            
            return view('store/pos_order_error_list',array('error_message'=>$error_message,'errors_list'=>$errors_list));
            
            }catch (\Exception $e){		
                CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
                return view('store/pos_order_error_list',array('error_message'=>$e->getMessage()));
        }  
    }
    
    public function posOrderErrorDetail(Request $request,$id){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $inv_list = $client_prices = $server_prices = [];
            $error_message = '';
            $error_id = $id;
            
            $error_data = \DB::table('pos_customer_orders_errors as pcoe')
            ->join('store as s','s.id', '=', 'pcoe.store_id')
            ->where('pcoe.id',$error_id)        
            ->select('pcoe.*','s.store_name')
            ->first();
            
            $id_array = explode(',',$error_data->inv_ids);
            
            if(!empty($id_array)){
                $inv_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id') 
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')           
                ->wherein('ppmi.id',$id_array)
                ->select('ppmi.*','ppm.product_name','ppm.hsn_code','dlim_1.name as color_name','psc.size as size_name')
                ->get()->toArray(); 
                
                $inv_list = json_decode(json_encode($inv_list),true);
                
                $client_price_list = explode(',',$error_data->client_price_list);
                $server_price_list = explode(',',$error_data->server_price_list);
                
                for($i=0;$i<count($client_price_list);$i++){
                    $client_price_data = explode(':',$client_price_list[$i]);
                    $client_prices[$client_price_data[0]] = $client_price_data[1];
                }
                
                for($i=0;$i<count($server_price_list);$i++){
                    $server_price_data = explode(':',$server_price_list[$i]);
                    $server_prices[$server_price_data[0]] = $server_price_data[1];
                }
                
                for($i=0;$i<count($inv_list);$i++){
                    $id = $inv_list[$i]['id'];
                    $inv_list[$i]['client_price'] = isset($client_prices[$id])?$client_prices[$id]:0;
                    $inv_list[$i]['server_price'] = isset($server_prices[$id])?$server_prices[$id]:0;
                }
            
            }
            
            return view('store/pos_order_error_detail',array('error_message'=>$error_message,'error_data'=>$error_data,'inv_list'=>$inv_list));
            
            }catch (\Exception $e){		
                CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
                return view('store/pos_order_error_detail',array('error_message'=>$e->getMessage()));
        }  
    }
}
