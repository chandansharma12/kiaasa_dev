<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Pos_customer;
use App\Models\Pos_customer_orders;
use App\Models\Pos_customer_orders_detail;
use App\Models\Pos_customer_address;
use App\Models\Pos_product_master;
use App\Models\Pos_product_master_inventory;
use App\Models\Pos_customer_orders_payments;
use App\Models\Design_lookup_items_master;
use App\Models\Production_size_counts;
use App\Models\User;
use App\Models\PosCustomerTempOtp;
use App\Models\PosCustomerResetPassword;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\PosProductWishlist;

use Seshac\Shiprocket\Shiprocket;

class PosApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
        $this->razorpay_key_id = 'rzp_live_aKvJnvIAorZ8r3';
        $this->razorpay_key_secret = 'fiDvRCtR9R2HM6oUx699uTBU';
    }
    
    function login(Request $request){
        try{ 
            $data = $request->all();

            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }

            $validateionRules = array('email'=>'required|email','password'=>'required');
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Email and Password are Required Fields', 'errors' => $validator->errors()),200);
            }	
            
            $credentials = $request->only('email', 'password');

            if(Auth::attempt(['email' =>$data['email'], 'password' =>$data['password'],'user_type'=>[9,19], 'status'=>1,'is_deleted'=>0])) {
                // Fetch user details from email
                $user_data = User::where('email',trim($data['email']))->wherein('user_type',[9,19])->where('is_deleted',0)->where('status',1)->select('id','name','api_token','api_token_created_at')->first();
                
                if(!empty($user_data->api_token) && (time()-strtotime($user_data->api_token_created_at))/3600 <= 240){
                    $api_token = $user_data->api_token;
                }else{
                    $api_token = md5(uniqid($user_data->id, true));
                    $updateArray = array('api_token'=>$api_token,'api_token_created_at'=>date('Y/m/d H:i:s'));
                    User::where('id',$user_data->id)->update($updateArray);
                    $user_data = User::where('id',$user_data->id)->select('id','name','api_token','api_token_created_at')->first();
                }
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Authenticated','user_data'=>$user_data),200);
            }else{
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Incorrect login credentials'),200);
            }
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage().', '.$e->getLine(),'message'=>'Error in Processing Request'),200);
        }
    }
    
    function getProductData(Request $request,$barcode){
        try{ 
            
            $data = $request->all();
            
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $access_token_header = $request->header('Access-Token');
            $user_data = User::where('api_token',$access_token_header)->select('id','name','user_type','api_token','api_token_created_at')->first();
            
            $product_data = CommonHelper::getPosProductData($barcode,'',$user_data->id);//print_r($product_data);exit;
            
            if(empty($product_data)){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Products does not exists'),200);
            }
            
            $product = array('id'=>$product_data->id,'store_id'=>$product_data->store_id,'barcode'=>$product_data->peice_barcode,'product_status'=>$product_data->product_status);
            $product['product_name'] = $product_data->product_name; 
            $product['product_sku'] = $product_data->product_sku;
            $product['category_name'] = $product_data->category_name;
            $product['subcategory_name'] = $product_data->subcategory_name;
            $product['color_name'] = $product_data->color_name;
            $product['size_name'] = $product_data->size_name;
            $product['return_product'] = $product_data->return_product;
            if($product_data->return_product == 1){
                $product['sale_price_return'] = $product_data->sale_price_return;
                $product['net_price_return'] = $product_data->net_price_return;
                $product['discount_percent_return'] = $product_data->discount_percent_return;
                $product['discount_amount_return'] = $product_data->discount_amount_return;
                $product['gst_percent_return'] = $product_data->gst_percent_return;
                $product['gst_amount_return'] = $product_data->gst_amount_return;
                $product['gst_inclusive_return'] = $product_data->gst_inclusive_return;
            }else{
                $product['sale_price'] = $product_data->sale_price;
                $product['discount_percent'] = $product_data->discount_percent;
                $product['discount_id'] = $product_data->discount_id;
                $product['gst_percent'] = $product_data->gst_percent;
                $product['gst_inclusive'] = $product_data->gst_inclusive;
            }
                
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Products data','product_data'=>$product),200);
            
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getCustomerData(Request $request){
        try{ 
            $data = $request->all();
            return CommonHelper::getPosCustomerData($data);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function addCustomerData(Request $request){
        try{ 
            $data = $request->all();
            
            $validateionRules = array('phone_no'=>'Required','email'=>'nullable|email','salutation'=>'Required','name'=>'Required');
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Phone no, Salutation and Name are Required Fields. Email should be valid email address', 'errors' => $validator->errors()));
            } 
            
            $customer_params = array('customer_phone_new'=>$data['phone_no'],'customer_salutation'=>$data['salutation'],'customer_name'=>$data['name']);
            $customer_params['customer_email'] = (!empty($data['email']))?$data['email']:null;
            $customer_params['customer_postal_code'] = (!empty($data['postal_code']))?$data['postal_code']:null;
            $customer_params['customer_wedding_date'] = (!empty($data['wedding_date']))?$data['wedding_date']:null;
            $customer_params['customer_dob'] = (!empty($data['dob']))?$data['dob']:null;
            
            $response_data = CommonHelper::addPosCustomer($customer_params);
            return response($response_data,$response_data['httpStatus']);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function addPosOrder(Request $request){
        try{ 
            $data = $request->all();
            
            \DB::beginTransaction();
            
            $validateionRules = array('ids'=>'required','customer_id'=>'required|integer','cash_amount'=>'numeric','card_amount'=>'numeric','wallet_amount'=>'numeric','voucher_amount'=>'numeric',
            'voucher_approver_id'=>'integer','voucher_comment'=>'max:200','customer_postal_code'=>'max:50','customer_wedding_date'=>'nullable|date','customer_dob'=>'nullable|date','customer_salutation'=>'sometimes|required','customer_name'=>'sometimes|required');
            
            if(isset($data['customer_id']) && !empty($data['customer_id'])){
                $validateionRules['customer_email'] = 'nullable|email|unique:pos_customer,email,'.$data['customer_id'];
            }
            
            if(!empty($data['wallet_amount'])) $validateionRules['wallet_ref_no'] = 'required';
            
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                $errors_str = CommonHelper::parseValidationErrors($validator->errors());
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $validator->errors()));
            } 
            
            $data['cashAmtValue'] = (!empty($data['cash_amount']))?$data['cash_amount']:null;
            $data['cardAmtValue'] = (!empty($data['card_amount']))?$data['card_amount']:null;
            $data['WalletAmtValue'] = (!empty($data['wallet_amount']))?$data['wallet_amount']:null;
            $data['ref_no'] = (!empty($data['wallet_ref_no']))?$data['wallet_ref_no']:null;
            $data['voucherAmount'] = (!empty($data['voucher_amount']))?$data['voucher_amount']:null;
            $data['voucherComment'] = (!empty($data['voucher_comment']))?$data['voucher_comment']:null;
            $data['voucherApprover'] = (!empty($data['voucher_approver_id']))?$data['voucher_approver_id']:null;
            $data['order_source'] = 'api';
            
            $access_token_header = $request->header('Access-Token');
            $user_data = User::where('api_token',$access_token_header)->select('id','name','api_token','api_token_created_at')->first();
            $data['user_id'] = $user_data->id;
            
            $id_array = explode(',',$data['ids']);
            
            $inv = Pos_product_master_inventory::wherein('id',$id_array)->where('product_status',4)->where('is_deleted',0)->first();
            if(isset($inv->store_id) && !empty($inv->store_id)){
                $store_id = $inv->store_id;
                $data['store_id'] = $store_id;
            }else{
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Store ID', 'errors' => 'Invalid Store ID'));
            }
            
            $response_data = CommonHelper::createPosOrder($data);
            
            CommonHelper::updatePosCustomer($data);
            
            \DB::commit();
            
            return $response_data;
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function logout(Request $request){
        try{ 
            $data = $request->all();
            
            $access_token_header = $request->header('Access-Token');
            $user_data = User::where('api_token',$access_token_header)->select('id','name','api_token','api_token_created_at')->first();
            
            $updateArray = array('api_token'=>null,'api_token_created_at'=>null);
            User::where('id',$user_data->id)->update($updateArray);
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'User Logged out'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function storeOrdersList(Request $request){
        try{ 
           $data = $request->all();
            $orders_products_list = $orders_payments_list = array();
            
            $validateionRules = array('access_key'=>'required');
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                $str = '<?xml version="1.0" encoding="UTF-8"?><Error>Access Key is Required Field</Error>';
                echo $str;exit();
            }	
            
            $access_key = trim($data['access_key']);
            $store_data = Store::where('api_access_key',$access_key)->where('is_deleted',0)->first();
            
            if(empty($store_data)){
                $str = '<?xml version="1.0" encoding="UTF-8"?><Error>Incorrect Access Key</Error>';
                echo $str;exit();
            }
            
            if(isset($data['from']) && !empty($data['from']) && isset($data['to']) && !empty($data['to'])){
                $date_from_array = explode('-',str_replace('/','-',trim($data['from'])));
                $date_to_array = explode('-',str_replace('/','-',trim($data['to'])));
                
                if(count($date_from_array) != 3){
                    $str = '<?xml version="1.0" encoding="UTF-8"?><Error>Invalid From Date</Error>';
                    echo $str;exit();
                }
                
                if(!checkdate($date_from_array[1],$date_from_array[0],$date_from_array[2])){
                    $str = '<?xml version="1.0" encoding="UTF-8"?><Error>Invalid From Date</Error>';
                    echo $str;exit();
                }
                
                if(count($date_to_array) != 3){
                    $str = '<?xml version="1.0" encoding="UTF-8"?><Error>Invalid To Date</Error>';
                    echo $str;exit();
                }
                
                if(!checkdate($date_to_array[1],$date_to_array[0],$date_to_array[2])){
                    $str = '<?xml version="1.0" encoding="UTF-8"?><Error>Invalid To Date</Error>';
                    echo $str;exit();
                }
                
                $from_date = $date_from_array[2].'-'.$date_from_array[1].'-'.$date_from_array[0];
                $to_date = $date_to_array[2].'-'.$date_to_array[1].'-'.$date_to_array[0];
                
                $days_diff =  CommonHelper::dateDiff($from_date, $to_date);
                
                if($days_diff > 9){
                    $str = '<?xml version="1.0" encoding="UTF-8"?><Error>Order Dates should not be More than 10 Days</Error>';
                    echo $str;exit();
                }
            }else{
                $from_date = date('Y-m-d');
                $to_date = date('Y-m-d');
            }
            
            $cache_file_name = 'documents/store_api_cache/'.$store_data->id.'/'.strtotime($from_date).'_'.strtotime($to_date);
            
            if(file_exists(public_path($cache_file_name))){
                $minutes_file_created = ceil((time() - filemtime($cache_file_name))/60);
                if($minutes_file_created <= 10 ){	
                    $str = file_get_contents($cache_file_name);
                    echo $str;
                    exit();
                }
            }
            
            // Orders list
            $orders = Pos_customer_orders::where('store_id',$store_data->id)
            ->whereRaw("(DATE(created_at) BETWEEN '$from_date' AND '$to_date' )")
            ->where('is_deleted',0)
            ->where('order_status',1)        
            ->get()->toArray();
            
            // Orders products
            $orders_products  = \DB::table('pos_customer_orders as pco')
            ->join('pos_customer_orders_detail as pcod','pco.id', '=', 'pcod.order_id')
            ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')      
            ->where('pco.store_id',$store_data->id)
            ->whereRaw("(DATE(pco.created_at) BETWEEN '$from_date' AND '$to_date' )")        
            ->where('pco.is_deleted',0)
            ->where('pcod.is_deleted',0)      
            ->where('pco.fake_inventory',0)
            ->where('pcod.fake_inventory',0)
            ->where('ppm.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)     
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)        
            ->select('pcod.*','ppm.product_name','dlim_1.name as category_name','ppmi.peice_barcode')
            ->get()->toArray();
            
            // Orders payments
            $orders_payments  = \DB::table('pos_customer_orders as pco')
            ->join('pos_customer_orders_payments as pcop','pco.id', '=', 'pcop.order_id')
            ->where('pco.store_id',$store_data->id)
            ->whereRaw("(DATE(pco.created_at) BETWEEN '$from_date' AND '$to_date' )")        
            ->where('pco.is_deleted',0)
            ->where('pcop.is_deleted',0)        
            ->where('pco.fake_inventory',0)
            ->where('pcop.fake_inventory',0)        
            ->where('pco.order_status',1)
            ->where('pcop.order_status',1)        
            ->select('pcop.*')
            ->get()->toArray();
            
            for($i=0;$i<count($orders_products);$i++){
                $product = $orders_products[$i];
                $orders_products_list[$product->order_id][] = $product;
            }
            
            for($i=0;$i<count($orders_payments);$i++){
                $payment = $orders_payments[$i];
                $orders_payments_list[$payment->order_id][] = $payment;
            }
            
            $currency = 'INR';
            $exchange_rate = 1;
            $str = '<?xml version="1.0" encoding="UTF-8"?><Records>';
            
            for($i=0;$i<count($orders);$i++){
                $order_data = $orders[$i];
                $orders_products = (isset($orders_products_list[$order_data['id']]))?$orders_products_list[$order_data['id']]:array();
                $orders_payments = (isset($orders_payments_list[$order_data['id']]))?$orders_payments_list[$order_data['id']]:array();
                
                $total_net_price = $total_gst_amount = $total_discount_amount = $return_amount = 0;
                
                for($q=0;$q<count($orders_products);$q++){
                    $total_net_price+=$orders_products[$q]->net_price;
                    $total_gst_amount+=$orders_products[$q]->gst_amount;
                    $total_discount_amount+=$orders_products[$q]->discount_amount;
                    
                    /*if($orders_products[$q]->net_price < 0){
                        $return_amount+=$orders_products[$q]->net_price;
                    }*/
                }
                
                $total_net_price = round($total_net_price,2);
                $total_gst_amount = round($total_gst_amount,2);
                $total_discount_amount = round($total_discount_amount,2);
                
                $str.='<Record>
                <TransactionDetails>
                <LOCATION_CODE>'.$order_data['store_id'].'</LOCATION_CODE>
                <TERMINAL_ID>1</TERMINAL_ID>
                <SHIFT_NO>1</SHIFT_NO>
                <RCPT_NUM>'.$order_data['order_no'].'</RCPT_NUM>
                <RCPT_DT>'.date('Ymd',strtotime($order_data['created_at'])).'</RCPT_DT>
                <BUSINESS_DT>'.date('Ymd',strtotime($order_data['created_at'])).'</BUSINESS_DT>
                <RCPT_TM>'.date('Hms',strtotime($order_data['created_at'])).'</RCPT_TM>
                <INV_AMT>'.$total_net_price.'</INV_AMT>
                <TAX_AMT>'.$total_gst_amount.'</TAX_AMT>
                <RET_AMT>'.abs($return_amount).'</RET_AMT>
                <TRAN_STATUS>SALES</TRAN_STATUS>
                <OP_CUR>'.$currency.'</OP_CUR>
                <BC_EXCH>'.$exchange_rate.'</BC_EXCH>
                <DISCOUNT>'.$total_discount_amount.'</DISCOUNT>
                </TransactionDetails>';
                
                for($q=0;$q<count($orders_products);$q++){
                    $product_data = $orders_products[$q];
                    $gst_type = ($product_data->gst_inclusive == 0)?'E':'I';
                    $sale_type = ($product_data->net_price > 0)?'SALES':'RETURN';
                    
                    $str.='<ItemDetails>
                    <LOCATION_CODE>'.$order_data['store_id'].'</LOCATION_CODE>
                    <TERMINAL_ID>1</TERMINAL_ID>
                    <SHIFT_NO>1</SHIFT_NO>
                    <RCPT_NUM>'.$order_data['order_no'].'</RCPT_NUM>
                    <ITEM_CODE>'.$product_data->peice_barcode.'</ITEM_CODE>
                    <ITEM_NAME>'.$product_data->product_name.'</ITEM_NAME>
                    <ITEM_QTY>'.$product_data->product_quantity.'</ITEM_QTY>
                    <ITEM_PRICE>'.abs($product_data->sale_price).'</ITEM_PRICE>
                    <ITEM_CAT>'.$product_data->category_name.'</ITEM_CAT>
                    <ITEM_TAX>'.abs($product_data->gst_amount).'</ITEM_TAX>
                    <ITEM_TAX_TYPE>'.$gst_type.'</ITEM_TAX_TYPE>
                    <ITEM_NET_AMT>'.abs($product_data->net_price).'</ITEM_NET_AMT>
                    <OP_CUR>'.$currency.'</OP_CUR>
                    <BC_EXCH>'.$exchange_rate.'</BC_EXCH>
                    <ITEM_STATUS>'.$sale_type.'</ITEM_STATUS>
                    <ITEM_DISCOUNT>'.abs($product_data->discount_amount).'</ITEM_DISCOUNT>
                    </ItemDetails>';
                }
                
                for($q=0;$q<count($orders_payments);$q++){
                    $payment_data = $orders_payments[$q];
                    
                    $str.='<PaymentDetails>
                    <LOCATION_CODE>'.$order_data['store_id'].'</LOCATION_CODE>
                    <TERMINAL_ID>1</TERMINAL_ID>
                    <SHIFT_NO>1</SHIFT_NO>
                    <RCPT_NUM>'.$order_data['order_no'].'</RCPT_NUM>
                    <PAYMENT_NAME>'.$payment_data->payment_method.'</PAYMENT_NAME>
                    <CURRENCY_CODE>'.$currency.'</CURRENCY_CODE>
                    <EXCHANGE_RATE>'.$exchange_rate.'</EXCHANGE_RATE>
                    <TENDER_AMOUNT>'.$payment_data->payment_received.'</TENDER_AMOUNT>
                    <OP_CUR>'.$currency.'</OP_CUR>
                    <BC_EXCH>'.$exchange_rate.'</BC_EXCH>
                    <PAYMENT_STATUS>SALES</PAYMENT_STATUS>
                    </PaymentDetails>';
                }
                
                $str.='</Record>';
            }
            
            $str.='</Records>';
            
            echo $str;
            
            CommonHelper::createDirectory('documents/store_api_cache/'.$store_data->id);
            CommonHelper::saveCacheFile($cache_file_name,$str);
            
            exit();
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            
            $str = '<?xml version="1.0" encoding="UTF-8"?><Error>Error in Processing Request</Error>';
            echo $str;exit();
        }    
    }
    
    function getProductList(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $category_id_list = $color_id_list = $subcategory_id_list = $store_id_list = $size_id_list = $images_id_list = $images_id_list_back = $size_data = $size_color_qty = array();
            $qs_str = $store_id = '';
            
            // Get Ecommerce enabled vendors list start
            $vendor_list = \DB::table('vendor_detail as v')
            ->where('v.ecommerce_status',1)        
            ->where('v.is_deleted',0)
            ->select('v.id')        
            ->get()->toArray();   
            
            $vendor_ids = array_column($vendor_list,'id');
            $vendor_ids[] = 0;
            $vendor_ids_str = implode(',',$vendor_ids);
            
            // Get Ecommerce enabled vendors list end
            
            $products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('store as s','s.id', '=', 'ppmi.store_id')        
            ->join('pos_product_images as ppi','ppm.id', '=', 'ppi.product_id')       
            ->join('design_lookup_items_master as dlim1','dlim1.id', '=', 'ppm.category_id')       
            ->join('production_size_counts as psc','psc.id', '=', 'ppm.size_id')               
            ->where('ppmi.product_status',4)        
            ->whereRaw("(ppmi.vendor_id IN (".$vendor_ids_str.") OR ppmi.vendor_id IS NULL)")        
            ->where('ppmi.is_deleted',0)   
            ->where('ppm.is_deleted',0)        
            ->where('ppi.is_deleted',0)     
            ->where('dlim1.is_deleted',0)       
            ->where('psc.is_deleted',0)             
            ->where('ppmi.status',1)             
            ->where('s.ecommerce_status',1)           
            ->where('dlim1.api_data',1)        
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0); 
            
            if(isset($data['store_id']) && !empty($data['store_id'])){
                $store_id = trim($data['store_id']);
                if(!is_numeric($store_id)){
                    $store_data = Store::where('slug',$store_id)->first();
                    $store_id = !empty($store_data)?$store_data->id:0;
                }
                
                $products_list = $products_list->whereRaw("(ppmi.store_id = '$store_id')");
                $qs_str.='&store_id='.$data['store_id'];
            }
            
            if(isset($data['cat_id']) && !empty($data['cat_id'])){
                $cat_ids = "'" . implode ("','", explode(',',trim($data['cat_id'])) ) . "'";
                $products_list = $products_list->whereRaw("(ppm.category_id IN($cat_ids) OR dlim1.slug IN($cat_ids))");
                $qs_str.='&cat_id='.$data['cat_id'];
            }
            
            if(isset($data['subcat_id']) && !empty($data['subcat_id'])){
                $subcat_arr = explode(',',$data['subcat_id']);
                $products_list = $products_list->wherein('ppm.subcategory_id',$subcat_arr);
                $qs_str.='&subcategory_id='.$data['subcat_id'];
            }
            
            if(isset($data['size_id']) && !empty($data['size_id'])){
                $size_ids = "'" . implode ("','", explode(',',strtolower(trim($data['size_id']))) ) . "'";
                $products_list = $products_list->whereRaw("(ppm.size_id IN(".str_replace(['2x','3x','4x','5x','6x'],['','','',''],$size_ids).") OR psc.slug IN($size_ids))");
                //echo "(ppm.size_id IN(".str_replace(['2x','3x','4x','5x','6x'],['','','',''],$size_ids).") OR psc.slug IN($size_ids))";exit;
                $qs_str.='&size_id='.$data['size_id'];
            }
            
            if(isset($data['color_id']) && !empty($data['color_id'])){
                $color_id_list1 = [];
                $color_ids = "'" . implode ("','", explode(',',trim($data['color_id'])) ) . "'";
                $color_names = Design_lookup_items_master::whereRaw("(id IN($color_ids) OR slug IN($color_ids))")->select('name')->get()->toArray();
                for($i=0;$i<count($color_names);$i++){
                    $color_name = trim(str_replace(['light','dark'],['',''],strtolower($color_names[$i]['name'])));
                    $color_list = Design_lookup_items_master::where('name','LIKE','%'.$color_name.'%')->where('type','color')->select('id')->get()->toArray();
                    $color_id_list = array_column($color_list,'id');
                    $color_id_list1 = array_merge($color_id_list1,$color_id_list);
                }
                
                $color_id_list1 = array_values(array_unique($color_id_list1));
                $products_list = $products_list->wherein('ppm.color_id',$color_id_list1);
                
                $qs_str.='&color_id='.$data['color_id'];
            }
            
            if(isset($data['min_price']) && !empty($data['min_price'])){
                $products_list = $products_list->where('ppmi.sale_price','>=',trim($data['min_price']));
                $qs_str.='&min_price='.$data['min_price'];
            }
            
            if(isset($data['max_price']) && !empty($data['max_price'])){
                $products_list = $products_list->where('ppmi.sale_price','<=',trim($data['max_price']));
                $qs_str.='&max_price='.$data['max_price'];
            }
            
            // get object for inventory count
            $products_inv_list = clone $products_list;
            
            // group by sku for sku as one product
            $products_list = $products_list->groupBy('ppm.product_sku')
            ->select('ppm.id as product_id','ppmi.store_id','ppmi.sale_price','ppmi.peice_barcode','ppm.product_name','ppm.product_sku','ppm.category_id','ppm.subcategory_id','ppm.product_barcode',
            'ppm.product_description','ppm.season_id','ppm.hsn_code','ppm.gst_inclusive','ppmi.arnon_inventory',\DB::raw('count(ppmi.id) as inventory_count'))        
            ->orderBy('ppm.id')        
            ->paginate(20);        
            
            return $products_list = json_decode(json_encode($products_list),true);
            
            if(!empty($products_list['data'])){
                     
                $product_ids = array_column($products_list['data'],'product_id');
                $store_list = CommonHelper::getStoresList();
                $size_list = Production_size_counts::get()->toArray();
                $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','COLOR','POS_PRODUCT_SUBCATEGORY'))->orderBy('id')->get()->toArray();
                
                if(!empty($store_id)){
                    $store_user_data = \DB::table('store_users')->where('store_id',$store_id)->where('is_deleted',0)->select('user_id')->first();
                    $store_data = \DB::table('store')->where('id',$store_id)->where('is_deleted',0)->first();
                }
                
                // get images list (front)
                $images_list = \DB::table('pos_product_images')->where('product_id','>=',min($product_ids))->where('product_id','<=',max($product_ids))
                ->where('image_type','front')->where('is_deleted',0)->select('id','product_id','image_name','image_type')->get()->toArray();
                $images_list = json_decode(json_encode($images_list),true);

                // get images list (back)  
                $images_list_back = \DB::table('pos_product_images')->where('product_id','>=',min($product_ids))->where('product_id','<=',max($product_ids))
                ->where('image_type','back')->where('is_deleted',0)->select('id','product_id','image_name','image_type')->get()->toArray();
                $images_list_back = json_decode(json_encode($images_list_back),true);

                for($i=0;$i<count($design_lookup_items);$i++){
                    if(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_CATEGORY'){
                        $category_id_list[$design_lookup_items[$i]['id']] = $design_lookup_items[$i]['name'];
                    }elseif(strtoupper($design_lookup_items[$i]['type']) == 'COLOR'){
                        $color_id_list[$design_lookup_items[$i]['id']] = $design_lookup_items[$i]['name'];
                    }elseif(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_SUBCATEGORY'){
                        $subcategory_id_list[$design_lookup_items[$i]['id']] = $design_lookup_items[$i]['name'];
                    }
                }

                for($i=0;$i<count($store_list);$i++){
                    $store_id_list[$store_list[$i]['id']] = $store_list[$i]['store_name'];
                }

                for($i=0;$i<count($size_list);$i++){
                    $size_id_list[$size_list[$i]['id']] = $size_list[$i]['size'];
                }
                
                for($i=0;$i<count($images_list);$i++){
                    $images_id_list[$images_list[$i]['product_id']] = $images_list[$i];
                }

                for($i=0; $i<count($images_list_back);$i++)
                {
                    $images_id_list_back[$images_list_back[$i]['product_id']] = $images_list_back[$i];
                }

                $product_skus = array_column($products_list['data'],'product_sku');
                
                // get product list and inventory count
                $products_inv_list = $products_inv_list->wherein('ppm.product_sku',$product_skus)
                ->groupBy('ppm.id')
                ->select('ppm.id as product_id','ppm.product_sku','ppm.size_id','ppm.color_id',\DB::raw('count(ppmi.id) as inventory_count'))
                ->orderBy('ppm.id')        
                ->get()->toArray();
                
                //Create color size quantity array
                for($i=0;$i<count($products_inv_list);$i++){
                    $product_id = $products_inv_list[$i]->product_id;
                    $size_id = $products_inv_list[$i]->size_id;
                    $color_id = $products_inv_list[$i]->color_id;
                    $key = $product_id.'_'.$size_id.'_'.$color_id;
                    if(isset($size_color_qty[$key])){
                        $size_color_qty[$key]+=$products_inv_list[$i]->inventory_count;
                    }else{
                        $size_color_qty[$key] = $products_inv_list[$i]->inventory_count;
                    }
                }
                
                // Create size color array
                for($i=0;$i<count($products_inv_list);$i++){
                    $product_sku = strtolower($products_inv_list[$i]->product_sku);
                    $product_id = $products_inv_list[$i]->product_id;
                    $size_id = $products_inv_list[$i]->size_id;
                    $color_id = $products_inv_list[$i]->color_id;
                    $qty = $size_color_qty[$product_id.'_'.$size_id.'_'.$color_id];
                    
                    $size_data[$product_sku][$size_id]['size_id'] = $size_id;
                    $size_data[$product_sku][$size_id]['size_name'] = $size_id_list[$size_id];
                    $size_data[$product_sku][$size_id]['colors'][] = ['color_id'=>$color_id,'color_name'=>$color_id_list[$color_id],'inventory_count'=>$qty];
                }
               
                for($i=0;$i<count($products_list['data']);$i++){
                    $product = $products_list['data'][$i];
                    $products_list['data'][$i]['category_name'] = isset($category_id_list[$product['category_id']])?$category_id_list[$product['category_id']]:'';
                    $products_list['data'][$i]['subcategory_name'] = isset($subcategory_id_list[$product['subcategory_id']])?$subcategory_id_list[$product['subcategory_id']]:'';
                    $products_list['data'][$i]['product_description'] = nl2br($products_list['data'][$i]['product_description']);
                    
                    // Assign images to product
                    if(isset($images_id_list[$product['product_id']])){
                        $products_list['data'][$i]['image_type'] = $images_id_list[$product['product_id']]['image_type'];
                        $products_list['data'][$i]['image_url'] = str_replace('index.php','',url('/')).'/images/pos_product_images/'.$product['product_id'].'/'.trim($images_id_list[$product['product_id']]['image_name']);
                        
                        $products_list['data'][$i]['image_type_back'] = $images_id_list_back[$product['product_id']]['image_type'];
                        $products_list['data'][$i]['image_url_back'] = str_replace('index.php','',url('/')).'/images/pos_product_images/'.$product['product_id'].'/'.trim($images_id_list_back[$product['product_id']]['image_name']);
                    }else{
                        $products_list['data'][$i]['image_type'] = $products_list['data'][$i]['image_url'] = '';
                        $products_list['data'][$i]['image_type_back'] = $products_list['data'][$i]['image_url'] = '';
                    }
                    
                    // Assign size array
                    $product_sku = strtolower($products_list['data'][$i]['product_sku']);
                    $size_list = array();
                    $size_info = isset($size_data[$product_sku])?$size_data[$product_sku]:array();
                    ksort($size_info);
                    foreach($size_info as $size_array){
                        $size_list[] = $size_array;
                    }
                    
                    $products_list['data'][$i]['size_data'] = $size_list;
                    
                    if(!empty($store_id)){
                        
                        $product_data = ['store_id'=>$store_id,'sku'=>$products_list['data'][$i]['product_sku'],'category_id'=>$products_list['data'][$i]['category_id'],
                        'season'=>$products_list['data'][$i]['season_id'],'hsn_code'=>$products_list['data'][$i]['hsn_code'],'gst_inclusive'=>$products_list['data'][$i]['gst_inclusive'],
                        'peice_barcode'=>$products_list['data'][$i]['peice_barcode'],'arnon_inventory'=>$products_list['data'][$i]['arnon_inventory'],'sale_price'=>$products_list['data'][$i]['sale_price']];
                        
                        $net_price_data = CommonHelper::getProductNetPriceData($product_data,$store_data);
                        $products_list['data'][$i]['net_price'] = round($net_price_data['net_price'],3);
                        $products_list['data'][$i]['discount_percent'] = round($net_price_data['discount_percent'],3);
                        $products_list['data'][$i]['gst_inclusive'] = $net_price_data['gst_inclusive'];
                    }
                    unset($products_list['data'][$i]['peice_barcode']);
                    unset($products_list['data'][$i]['arnon_inventory']);
                }
                
                $products_list['next_page_url'] = (!empty($products_list['next_page_url']))?$products_list['next_page_url'].$qs_str:$products_list['next_page_url'];
                $products_list['prev_page_url'] = (!empty($products_list['prev_page_url']))?$products_list['prev_page_url'].$qs_str:$products_list['prev_page_url'];
                $products_list['first_page_url'] = (!empty($products_list['first_page_url']))?$products_list['first_page_url'].$qs_str:$products_list['first_page_url'];
                $products_list['last_page_url'] = (!empty($products_list['last_page_url']))?$products_list['last_page_url'].$qs_str:$products_list['last_page_url'];
            }
            //var_dump($products_list);exit;
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Products list','products_list'=>$products_list),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage().', '.$e->getLine(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getCategoryList(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $category_list = \DB::table('design_lookup_items_master as dlim')
            ->where('dlim.type','POS_PRODUCT_CATEGORY')      
            ->where('dlim.api_data',1)              
            //->where('dlim.is_deleted',0)          
            ->select('dlim.id','dlim.name','dlim.slug')        
            ->orderBy('dlim.id')        
            ->get()->toArray();        
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'category list','category_list'=>$category_list),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getSubCategoryList(Request $request,$category_id = ''){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $category_where = ($category_id > 0)?'dlim.pid = '.$category_id:'dlim.pid > 0';
            
            $subcategory_list = \DB::table('design_lookup_items_master as dlim')
            ->whereRaw($category_where)                   
            ->where('dlim.type','POS_PRODUCT_SUBCATEGORY')       
            ->where('dlim.is_deleted',0)           
            ->select('dlim.id','dlim.name','dlim.slug')        
            ->orderBy('dlim.id')        
            ->get()->toArray();        
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'subcategory list','subcategory_list'=>$subcategory_list),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getSizeList(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->select('id','size','slug')->orderBy('id')->get()->toArray();        
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'size list','size_list'=>$size_list),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getColorList(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
        
            $color_list = Design_lookup_items_master::where('type','COLOR')->where('api_data',1)->where('is_deleted',0)->where('status',1)->select('id','name','slug')->orderBy('id')->get()->toArray();        
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'color list','color_list'=>$color_list),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getStateList(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $state_list = \DB::table('state_list')->where('is_deleted',0)->where('status',1)->select('id','state_name')->orderBy('id')->get()->toArray();        
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'state list','state_list'=>$state_list),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getStoreList(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $store_list = \DB::table('store as s')
            ->join('state_list as sl','s.state_id', '=', 'sl.id')        
            ->leftJoin('design_lookup_items_master as zone','s.zone_id', '=', 'zone.id')                
            ->where('s.is_deleted',0)
            ->where('s.ecommerce_status',1);         
            
            if(isset($data['search']) && !empty($data['search'])){
                $search  = '%'.trim($data['search']).'%';
                $store_list = $store_list->whereRaw("(s.store_name LIKE '$search' OR s.city_name LIKE '$search' OR sl.state_name LIKE '$search')");
            }
            
            $store_list = $store_list->select('s.id','s.store_name','s.address_line1','s.address_line2','s.city_name','s.postal_code','s.state_id','s.google_name','s.display_name',
            's.store_id_code as store_code','s.zone_id','s.phone_no','s.front_picture','s.back_picture','s.latitude','s.longitude','sl.state_name','zone.name as zone_name','s.slug')
            ->get()->toArray();        
            
            for($i=0;$i<count($store_list);$i++){
                $store_list[$i]->front_picture = !empty($store_list[$i]->front_picture)?str_replace('index.php','',url('/')).'/images/store_images/'.$store_list[$i]->front_picture:'';
                $store_list[$i]->back_picture = !empty($store_list[$i]->back_picture)?str_replace('index.php','',url('/')).'/images/store_images/'.$store_list[$i]->back_picture:'';
            }
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'store list','store_list'=>$store_list),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getProductDetails(Request $request,$product_id,$store_id){
        try{ 
            $inventoryCount = 0;
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $products = $size_data = $product_detail = array();
            $product_info = Pos_product_master::where('id',$product_id)->where('is_deleted',0)->select('product_sku')->first();
            
            // Fetch inventory of sku in store
            $product_inv_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->join('design_lookup_items_master as dlim_1','dlim_1.id', '=', 'ppm.color_id')
            ->join('production_size_counts as psc','psc.id', '=', 'ppm.size_id')        
            ->where('ppm.product_sku',$product_info->product_sku)    
            ->where('ppmi.store_id',$store_id)                   
            ->where('ppmi.product_status',4)             
            ->where('ppmi.is_deleted',0)   
            ->where('ppm.is_deleted',0)        
            ->where('ppmi.status',1)             
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)
            ->groupBy('ppmi.product_master_id')        
            ->select('ppmi.peice_barcode','ppm.size_id','ppm.color_id','dlim_1.name as color_name','psc.size as size_name','ppm.product_description',\DB::raw('count(ppmi.id) as inventory_count'))
            ->orderBy('ppm.id')        
            ->get()->toArray(); 
            
            $products = $product_inv_list;

            foreach ($products as $key => $value) 
            {
                $inventoryCount += $value->inventory_count;
            }

            if(!empty($products)){
                $user_data = \DB::table('store_users')->where('store_id',$store_id)->where('is_deleted',0)->select('user_id')->first();
                $store_user = CommonHelper::getUserStoreData($user_data->user_id);
                
                for($i=0;$i<count($products);$i++){
                    $product_data = CommonHelper::getPosProductData($products[$i]->peice_barcode,'',$user_data->user_id);
                    
                    if(!empty($product_data)){
                        break;
                    }
                }
                
                $product_data  = json_decode(json_encode($product_data),true);
                $products  = json_decode(json_encode($products),true);
                
                //create color size quantity array
                for($i=0;$i<count($products);$i++){
                    $size_id = $products[$i]['size_id'];
                    $color_id = $products[$i]['color_id'];
                    $key = $size_id.'_'.$color_id;
                    if(isset($size_color_qty[$key])){
                        $size_color_qty[$key]+=$products[$i]['inventory_count'];
                    }else{
                        $size_color_qty[$key] = $products[$i]['inventory_count'];
                    }
                }
                
                // Create size color array
                for($i=0;$i<count($products);$i++){
                    $size_id = $products[$i]['size_id'];
                    $color_id = $products[$i]['color_id'];
                    $qty = $size_color_qty[$size_id.'_'.$color_id];
                    
                    $size_data[$size_id]['size_id'] = $size_id;
                    $size_data[$size_id]['size_name'] = $products[$i]['size_name'];
                    $size_data[$size_id]['colors'][] = ['color_id'=>$color_id,'color_name'=>$products[$i]['color_name'],'inventory_count'=>$qty];
                }
                
                ksort($size_data);
                
                $size_list = array();
                foreach($size_data as $size_array){
                    $size_list[] = $size_array;
                }

                $product_data['size_data'] = $size_list;
                
                $discount_amount = (empty($product_data['discount_percent']))?0:round(($product_data['sale_price']*($product_data['discount_percent']/100)),6);
                $discounted_price = $discounted_price_1 = $product_data['sale_price']-$discount_amount;
                
                if(isset($store_user->gst_applicable) && $store_user->gst_applicable == 1){
                    $gst_data = CommonHelper::getGSTData($product_data['hsn_code'],$discounted_price);
                    $gst_percent = (!empty($gst_data))?$gst_data->rate_percent:0;
                }else{
                    $gst_percent = 0;
                }
                
                if($product_data['gst_inclusive'] == 1){
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
                
                $product_data['discount_amount'] = round($discount_amount,3); //($product_data['gst_inclusive'] == 1)?round($discount_amount+$gst_amount,3):round($discount_amount,3);
                $product_data['discounted_price'] = ($product_data['gst_inclusive'] == 1)?round($discounted_price_1-$gst_amount,3):round($discounted_price_1,3);
                $product_data['discount_percent'] = $product_data['discount_percent'];//($product_data['gst_inclusive'] == 1)?round(($product_data['discount_amount']/$product_data['sale_price'])*100,3):$product_data['discount_percent'];
                $product_data['gst_percent'] = $gst_percent;
                $product_data['gst_amount'] = round($gst_amount,3);
                $product_data['net_price'] = round($net_price,3);
                
                $product_detail = array('product_id'=>$product_data['product_master_id'],'inventory_count'=>$inventoryCount,'product_name'=>$product_data['product_name'],'product_sku'=>$product_data['product_sku'],
                'category_id'=>$product_data['category_id'],'subcategory_id'=>$product_data['subcategory_id'],'category_name'=>$product_data['category_name'],'subcategory_name'=>$product_data['subcategory_name'],
                'product_barcode'=>$product_data['product_barcode'],'size_data'=>$product_data['size_data'],'sale_price'=>$product_data['sale_price'],'discount_percent'=>$product_data['discount_percent'],
                'discount_amount'=>$product_data['discount_amount'],'discounted_price'=>$product_data['discounted_price'],'gst_percent'=>$product_data['gst_percent'],'gst_amount'=>$product_data['gst_amount'],
                'net_price'=>$product_data['net_price'],'gst_inclusive'=>$product_data['gst_inclusive'],'product_description'=>nl2br($product_data['product_description']));
                
                // Images list of product
                $images_list = \DB::table('pos_product_images')->where('product_id',$product_detail['product_id'])->where('is_deleted',0)->select('product_id','image_name','image_type')->get()->toArray();
                $images_list  = json_decode(json_encode($images_list),true);
                
                for($i=0;$i<count($images_list);$i++){
                    $images_list[$i]['image_url'] = str_replace('index.php','',url('/')).'/images/pos_product_images/'.$images_list[$i]['product_id'].'/'.trim($images_list[$i]['image_name']);
                    unset($images_list[$i]['product_id']);
                    unset($images_list[$i]['image_name']);
                }
                
                $product_detail['images_list'] = $images_list;
            }
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'product detail','product_detail'=>$product_detail),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage().', '.$e->getLine(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    //This create pos customer function have email and password as required field. It is for website
    function createPosCustomer(Request $request){
        try{ 
            $data = $request->all();

            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
        
            // $validateionRules = array('phone_no'=>'Required|digits_between:10,12','email'=>'Required|email|max:200','salutation'=>'Required','name'=>'Required|max:200','password'=>'Required|min:8|max:50|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$');
            $validateionRules = array('phone_no'=>'Required|numeric|digits_between:10,12','email'=>'Required|email|max:200','name'=>'Required|max:200','password'=>'Required|min:8|max:50');
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            
            $customer_params = array('customer_phone_new'=>trim($data['phone_no']),'customer_salutation'=>trim($data['salutation']),'customer_name'=>trim($data['name']));
            $customer_params['customer_email'] = trim($data['email']);
            $customer_params['password'] = md5(trim($data['password']));
            $customer_params['customer_postal_code'] = $customer_params['customer_wedding_date'] =  $customer_params['customer_dob'] =  null;
              
            $response_data = CommonHelper::addPosCustomer($customer_params);
            return response($response_data,$response_data['httpStatus']);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getPosCustomerDetails(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $validationRules = array('email'=>'Required|email|max:200','password'=>'Required|min:8|max:50');
            $attributes = array();
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            
            $email = trim($data['email']);
            $password = trim($data['password']);
            
            $customer_detail = Pos_customer::where('email',$email)->where('is_deleted',0)->select('id','salutation','customer_name','email','password','phone','created_at')->first();
            
            if(empty($customer_detail)){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Customer does not exists', 'errors' => 'Customer does not exists'));
            } 
            
            if(md5($password) != $customer_detail->password){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Incorrect Password', 'errors' => 'Incorrect Password'));
            }
            
            unset($customer_detail->password);
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'customer detail','customer_detail'=>$customer_detail),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function updatePosCustomerProfile(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $validateionRules = array('customer_id'=>'Required|integer','phone_no'=>'Required|digits_between:10,12','salutation'=>'Required','name'=>'Required|max:200');
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            
            $customer_params = array('phone'=>trim($data['phone_no']),'salutation'=>trim($data['salutation']),'customer_name'=>trim($data['name']));
            Pos_customer::where('id',$data['customer_id'])->update($customer_params);
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'customer profile updated successfully'),200);
           
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function updatePosCustomerPassword(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $validateionRules = array('email'=>'Required|email|max:200','password'=>'Required|min:8|max:50|regex:/^[A-Za-z0-9_]+$/','password_new'=>'Required|min:8|max:50|regex:/^[A-Za-z0-9_]+$/');
            $attributes = array('password_new'=>'New Password');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            
            $email = trim($data['email']);
            $password = trim($data['password']);
            $password_new = trim($data['password_new']);
            
            $customer_detail = Pos_customer::where('email',$email)->where('is_deleted',0)->select('id','salutation','customer_name','email','password','phone','created_at')->first();
            
            if(empty($customer_detail)){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Customer does not exists', 'errors' => 'Customer does not exists'));
            } 
            
            if(md5($password) != $customer_detail->password){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Incorrect Current Password', 'errors' => 'Incorrect Current Password'));
            }
            
            $customer_params = array('password'=>md5($password_new));
            Pos_customer::where('id',$customer_detail->id)->update($customer_params);
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'customer password updated successfully'),200);
           
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function createPosCustomerAddress(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $validationRules = array('full_name'=>'Required|min:3|max:200','customer_id'=>'Required|integer','address'=>'Required|min:10|max:200','locality'=>'Required|min:2|max:200',
            'city_name'=>'Required|min:3|max:200','postal_code'=>'Required|min:5|max:15','state_id'=>'Required|integer');
            $attributes = array('state_id'=>'State');
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            
            $customer_data = Pos_customer::where('id',trim($data['customer_id']))->where('is_deleted',0)->first();
            
            if(empty($customer_data)){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Customer with ID: '.$data['customer_id'].' does not exists', 'errors' => 'Customer with ID: '.$data['customer_id'].' does not exists'));
            }
            
            $address_params = array('customer_id'=>trim($data['customer_id']),'address'=>trim($data['address']),'city_name'=>trim($data['city_name']),
            'postal_code'=>trim($data['postal_code']),'state_id'=>trim($data['state_id']),'full_name'=>trim($data['full_name']),'locality'=>trim($data['locality']));
            
            $address_params['is_deleted'] = 0;
            $address_exists = Pos_customer_address::where($address_params)->first();
            
            if(!empty($address_exists)){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Address already exists', 'errors' => 'Address already exists'));
            }
           
            $address = Pos_customer_address::create($address_params);
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'customer address added successfully','address'=>$address),200);
           
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getPosCustomerAddressList(Request $request,$customer_id){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $address_list = \DB::table('pos_customer_address as pca')
            ->join('state_list as sl','sl.id', '=', 'pca.state_id')
            ->where('pca.customer_id',$customer_id)        
            ->where('pca.is_deleted',0)        
            ->select('pca.id as address_id','pca.customer_id','pca.address','pca.city_name','pca.postal_code','pca.state_id','sl.state_name')
            ->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'customer address list','address_list'=>$address_list),200);
           
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function createPosOrder(Request $request){
        try{ 
            $data = $request->all();
            $errors_str = '';
            \DB::beginTransaction();
            
            $validationRules = array('product'=>'required','quantity'=>'required','color'=>'required','size'=>'required','store_id'=>'required','customer_id'=>'required|integer',
            'net_price'=>'required|numeric','address_id'=>'required|integer','rp_order_id'=>'required','rp_payment_id'=>'required','rp_signature'=>'required','billing_same'=>'required:integer');
            $attributes = $inv_ids = array();
            
            if(isset($data['billing_same']) && $data['billing_same'] == 0){
                $validationRules['billing_customer_name'] = 'Required|min:3|max:200';
                $validationRules['billing_address'] = 'Required|min:3|max:200';
                $validationRules['billing_locality'] = 'Required|min:3|max:200';
                $validationRules['billing_city_name'] = 'Required|min:3|max:200';
                $validationRules['billing_postal_code'] = 'Required|min:5|max:15';
                $validationRules['billing_state_id'] = 'Required|integer';
            }
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                $errors_str = CommonHelper::parseValidationErrors($validator->errors());
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $validator->errors()));
            } 
            
            // Check if customer with id exists in database
            $customer_data = Pos_customer::where('id',trim($data['customer_id']))->where('is_deleted',0)->first();
            
            if(empty($customer_data)){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Customer with ID: '.$data['customer_id'].' does not exists', 'errors' => 'Customer with ID: '.$data['customer_id'].' does not exists'));
            }
            
            // Check if address with id exists in database
            $address_data = Pos_customer_address::where('id',trim($data['address_id']))->where('is_deleted',0)->first();
            
            if(empty($address_data)){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Address with ID: '.$data['address_id'].' does not exists', 'errors' => 'Address with ID: '.$data['address_id'].' does not exists'));
            }
            
            // Check if address customer id is same as customer id in parameter
            if($address_data->customer_id != trim($data['customer_id']) ){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Address with ID: '.$data['address_id'].' does not exists for this customer', 'errors' => 'Address with ID: '.$data['address_id'].' does not exists for this customer'));
            }
            
            $store_id = trim($data['store_id']);
            $product_list = $data['product'];
            $quantity_list = $data['quantity'];
            $color_list = $data['color'];
            $size_list = $data['size'];
            
            if(!is_array($product_list) || !is_array($quantity_list) || !is_array($color_list) || !is_array($size_list)){
                $errors_str = 'Product, Quantity, Color and Size should be of type Array';
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $errors_str));
            }
            
            if(count($product_list) != count($quantity_list)){
                $errors_str = 'Invalid Quantity Data';
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $errors_str));
            }elseif(count($product_list) != count($color_list)){
                $errors_str = 'Invalid Color Data';
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $errors_str));
            }elseif(count($product_list) != count($size_list)){
                $errors_str = 'Invalid Size Data';
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $errors_str));
            }
            
            for($i=0;$i<count($product_list);$i++){
                $product_id = $product_list[$i];
                $size_id = $size_list[$i];
                $color_id = $color_list[$i];
                $quantity = $quantity_list[$i];
                
                $product_info = Pos_product_master::where('id',$product_id)->where('is_deleted',0)->select('product_sku')->first();
                if(empty($product_info)){
                    $errors_str = 'Product '.($i+1).' does not exists';
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $errors_str));
                }
                
                $product_inv = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->where('ppm.product_sku',$product_info->product_sku)    
                ->where('ppm.size_id',$size_id)        
                ->where('ppm.color_id',$color_id)                        
                ->where('ppmi.store_id',$store_id)                   
                ->where('ppmi.product_status',4)     
                ->where('ppmi.is_deleted',0)   
                ->where('ppm.is_deleted',0)        
                ->where('ppmi.status',1)             
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)
                ->select('ppmi.id')
                ->orderBy('id','DESC')       
                ->limit($quantity)        
                ->get()->toArray(); 
                
                if(count($product_inv) < $quantity){
                    $errors_str = 'Inventory count of '.$quantity.' not available for Product: '.($i+1);
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $errors_str));
                } 
                
                if(!empty($product_inv)){
                    for($q=0;$q<count($product_inv);$q++){
                        $inv_ids[] = $product_inv[$q]->id;
                    }
                }
            }
            
            $inv_ids_str = implode(',',$inv_ids);
            
            $order_data = array('ids'=>$inv_ids_str,'customer_id'=>$data['customer_id'],'address_id'=>trim($data['address_id']),'store_id'=>$store_id);
            $order_data['cashAmtValue'] = null;
            $order_data['cardAmtValue'] = trim($data['net_price']);
            $order_data['WalletAmtValue'] = null;
            $order_data['ref_no'] = null;
            $order_data['voucherAmount'] = $order_data['voucherComment'] = $order_data['voucherApprover'] = null;
            $order_data['order_source'] = 'website_api';
            $order_data['rp_order_id'] = trim($data['rp_order_id']);
            $order_data['rp_payment_id'] = trim($data['rp_payment_id']);
            $order_data['rp_signature'] = trim($data['rp_signature']);       
            $order_data['bill_data_same'] = trim($data['billing_same']);       
            
            if(isset($data['billing_same']) && $data['billing_same'] == 0){
                $order_data['bill_cust_name'] = trim($data['billing_customer_name']);       
                $order_data['bill_address']  = trim($data['billing_address']);       
                $order_data['bill_locality'] = trim($data['billing_locality']);       
                $order_data['bill_city_name'] = trim($data['billing_city_name']);       
                $order_data['bill_postal_code'] = trim($data['billing_postal_code']);       
                $order_data['bill_state_id'] = trim($data['billing_state_id']);       
            }
            
            $store_user_data = \DB::table('store_users')->where('store_id',$store_id)->where('is_deleted',0)->select('user_id')->first();
            
            if(empty($store_user_data)){
                $errors_str = 'User does not exists for store';
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $errors_str));
            } 
            
            $order_data['user_id'] = $store_user_data->user_id;
           
            $response_data = CommonHelper::createPosOrder($order_data);
            
            CommonHelper::updatePosCustomer($order_data);
            
            \DB::commit();
            
            $order_response = json_decode(json_encode($response_data),true);
            if(isset($order_response['original']['order_data'])){
                $order_info = $order_response['original']['order_data'];

                if(!empty($customer_data->email)){
                    $email_data = ['type'=>'create_order','customer_data'=>$customer_data,'order_data'=>$order_info];
                    $this->sendEmail($email_data);
                }
            }
            
            return $response_data;
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage().', '.$e->getLine(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    private function sendEmail($email_data){
        if($email_data['type'] == 'create_order'){
            $shipping_address_str = $billing_address_str = '';
            
            $order_data = $email_data['order_data'];
            $customer_data = $email_data['customer_data'];
            
            $email_html = file_get_contents(url('/email-templates/create_order.html'));
            
            $shipping_address = Pos_customer_address::join('state_list','pos_customer_address.state_id','=','state_list.id')
            ->where('pos_customer_address.id',$order_data['address_id'])
            ->select('pos_customer_address.*','state_list.state_name')        
            ->first();
            
            if(!empty($shipping_address)){
                $shipping_address_str = $shipping_address->full_name.'<br>'.$shipping_address->address.'<br>'.$shipping_address->locality.'<br>'.$shipping_address->city_name.'<br>'.$shipping_address->postal_code.'<br>'.$shipping_address->state_name;
            }
            
            if($order_data['bill_data_same'] == 0){
                $state_data = \DB::table('state_list')->where('id',$order_data['bill_state_id'])->first();
                $billing_address_str = $order_data['bill_cust_name'].'<br>'.$order_data['bill_address'].'<br>'.$order_data['bill_locality'].'<br>'.$order_data['bill_city_name'].'<br>'.$order_data['bill_postal_code'].'<br>'.$state_data->state_name;
            }else{
                $billing_address_str = $shipping_address_str;
            }
            
            $search_array = ['{{CUSTOMER_NAME}}','{{ORDER_NO}}','{{ORDER_DATE}}','{{BILLING_ADDRESS}}','{{SHIPPING_ADDRESS}}'];
            $replace_array = [$customer_data->salutation.' '.$customer_data->customer_name,$order_data['order_no'],date('d-m-Y',strtotime($order_data['created_at'])),$billing_address_str,$shipping_address_str];
            
            $email_html = str_replace($search_array, $replace_array, $email_html);
            $subject = 'Kiaasa Order Confirmation #'.$order_data['order_no'];
            $to = $customer_data->email;
            //echo $email_html;exit;
            
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=iso-8859-1';

            // Additional headers
            $headers[] = 'To: '.$customer_data->salutation.' '.$customer_data->customer_name.' <'.$to.'>';
            $headers[] = 'From: Kiaasa <customercare@kiaasaretail.com>';
            
            // Mail it
            mail($to, $subject, $email_html, implode("\r\n", $headers));
        }
    }
    
    function getPosOrderList(Request $request,$customer_id){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $orders_list = \DB::table('pos_customer_orders as pco')
            ->join('store as s','s.id', '=', 'pco.store_id')
            ->join('pos_customer as pc','pc.id', '=', 'pco.customer_id')        
            ->leftJoin('pos_customer_address as pca','pca.id', '=', 'pco.address_id')       
            ->leftJoin('state_list as sl','sl.id', '=', 'pca.state_id')               
            ->leftJoin('state_list as s2','s2.id', '=', 'pco.bill_state_id')                       
            ->where('pco.customer_id',$customer_id)
            ->where('pco.order_status',1)        
            ->where('pco.is_deleted',0)
            ->select('pco.id as order_id','pco.order_no','pco.customer_id','pco.store_id','pco.total_price','pco.total_items','pco.created_at','s.store_name','pc.customer_name',
            'pc.email as customer_email','pc.phone as customer_phone','pco.address_id','pca.full_name','pca.address','pca.locality','pca.city_name','pca.postal_code','pca.state_id','sl.state_name',
            'pco.bill_data_same','pco.bill_cust_name','pco.bill_address','pco.bill_locality','pco.bill_city_name','pco.bill_postal_code','pco.bill_state_id','s2.state_name as bill_state_name')        
            ->orderBy('pco.id')
            ->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'customer detail','orders_list'=>$orders_list),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function getPosOrderDetails(Request $request,$order_id){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $product_ids = array();
            
            $order_data = \DB::table('pos_customer_orders as pco')
            ->join('store as s','s.id', '=', 'pco.store_id')
            ->join('pos_customer as pc','pc.id', '=', 'pco.customer_id')      
            ->leftJoin('pos_customer_address as pca','pca.id', '=', 'pco.address_id')       
            ->leftJoin('state_list as sl','sl.id', '=', 'pca.state_id')        
            ->leftJoin('state_list as s2','s2.id', '=', 'pco.bill_state_id')             
            ->where('pco.id',$order_id)
            ->where('pco.order_status',1)        
            ->where('pco.is_deleted',0)
            ->select('pco.id as order_id','pco.order_no','pco.customer_id','pco.store_id','pco.total_price','pco.total_items','pco.created_at','s.store_name','pc.customer_name',
            'pc.email as customer_email','pc.phone as customer_phone','pco.address_id','pca.address','pca.city_name','pca.postal_code','pca.state_id','sl.state_name',
            'pco.bill_data_same','pco.bill_cust_name','pco.bill_address','pco.bill_locality','pco.bill_city_name','pco.bill_postal_code','pco.bill_state_id','s2.state_name as bill_state_name')        
            ->first();
            
            $order_products = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')   
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
            ->where('pcod.order_id',$order_id)
            ->where('pcod.order_status',1)           
            ->where('pcod.is_deleted',0)
            ->select('pcod.product_id','ppm.product_name','ppm.product_barcode','ppm.product_sku','dlim_1.name as category_name','dlim_2.name as subcategory_name',
            'dlim_3.name as color_name','psc.size as size_name','ppmi.peice_barcode','ppm.category_id','ppm.subcategory_id','ppm.size_id','ppm.color_id',
            'ppm.product_description','pcod.product_id','pcod.sale_price','pcod.net_price','pcod.discount_percent','pcod.discounted_price_actual as discounted_price','pcod.gst_percent','pcod.gst_amount',
            'pcod.gst_inclusive')
            ->orderBy('pcod.id')        
            ->get()->toArray();
            
            for($i=0;$i<count($order_products);$i++){
                $product_ids[] = $order_products[$i]->product_id;
            }
            
            $images_list = \DB::table('pos_product_images')->wherein('product_id',$product_ids)->where('is_deleted',0)->select('product_id','image_name','image_type')->get()->toArray();
            $images_list  = json_decode(json_encode($images_list),true);

            for($i=0;$i<count($images_list);$i++){
                $product_id = $images_list[$i]['product_id'];
                $images_list[$i]['image_url'] = url('/').'/images/pos_product_images/'.$product_id.'/'.trim($images_list[$i]['image_name']);
                
                unset($images_list[$i]['product_id']);
                unset($images_list[$i]['image_name']);
                
                $product_images[$product_id][] = $images_list[$i];
            }
            
            for($i=0;$i<count($order_products);$i++){
                $product_id = $order_products[$i]->product_id;
                $order_products[$i]->images = isset($product_images[$product_id])?$product_images[$product_id]:array();
            }
            
            $pdf_file_name = $order_data->order_id.'_'.md5($order_data->order_no).'.pdf'; 
            $order_data->pdf_link = url('documents/pos_order_pdf/'.$pdf_file_name); 
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'customer detail','order_data'=>$order_data,'order_products'=>$order_products),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage().', '.$e->getLine(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function createRazorPayOrder(Request $request){
        try{ 
            $data = $request->all();
            $order_data = array();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $validationRules = array('amount'=>'Required|numeric','currency'=>'Required|min:3|max:3');
            $attributes = array('amount'=>'Amount');
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            
            $url = 'https://api.razorpay.com/v1/orders';
            $user_name = $this->razorpay_key_id;
            $password = $this->razorpay_key_secret;
            $headers = ['content-type:application/json'];
            $amount = round(trim($data['amount']),2)*100;
            $currency = trim($data['currency']);
            $post_data = ['amount'=>$amount,'currency'=>$currency];
            $post_data = json_encode($post_data);
            
            $response = CommonHelper::processCURLRequest($url,$post_data,$user_name,$password,$headers);
            if(!empty($response)){
                $order_data = json_decode($response,true);//print_r($order_data);exit;
                if(!(isset($order_data['id']) && !empty($order_data['id']))){
                    $error_msg = isset($order_data['error'])?$order_data['error']['description']:'Error in Creating Order';
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' =>$error_msg));
                }
            }else{
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in Creating Order', 'errors' => 'Error in Creating Order'));
            }
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'order data','order_data'=>$order_data),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }
    
    function verifyRazorPaySignature(Request $request){
        try{ 
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            
            $validationRules = array('order_id'=>'Required|min:3|max:250','payment_id'=>'Required|min:3|max:250','signature'=>'Required|min:3|max:250');
            $attributes = array('order_id'=>'order_id','payment_id'=>'payment_id','signature'=>'signature');
            
            $validator = Validator::make($data,$validationRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            
            $razorpay_order_id = trim($data['order_id']);
            $razorpay_payment_id = trim($data['payment_id']);
            $razorpay_signature = trim($data['signature']);
            
            $string = $razorpay_order_id."|".$razorpay_payment_id;
            $generated_signature = hash_hmac('sha256', $string, $this->razorpay_key_secret);
            if($generated_signature == $razorpay_signature){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'signature verified'),200);
            }else{
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'signature not verified'),200);
            }
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>200,"dateTime"=>time(),'status' => 'fail','error_message'=>$e->getMessage(),'message'=>'Error in Processing Request'),200);
        }    
    }

    // pos customer forget password link send to customer
    public function posCustomerForgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:pos_customer'
        ]);

        if($validator->fails())
        {
            return response()->json(['status' => 'fail', 'error_message' => $validator->errors()], 400);
        } else {
            try {
                $pos_customer_info = Pos_customer::where('email',$request->email)->where('is_deleted',0)->first();
                if(empty($pos_customer_info))
                {
                    return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Customer with email does not exists', 'errors' => 'Customer with email does not exists');
                }
                $token = Str::random(64);
                
                // token save and update in database 
                $checkEmailId = PosCustomerResetPassword :: Where('email',$request->email)->first();
                $insertArray = [
                    'email' => $request->email,
                    'token' => $token,
                    'created_at' => date('d-m-Y H:i:s'),
                    'updated_at' => date('d-m-Y H:i:s')
                ];
                if($checkEmailId)
                {
                    PosCustomerResetPassword :: Where('email',$request->email)->update([
                        'email' => $request->email,
                        'token' => $token,
                        'updated_at' => date('d-m-Y H:i:s')
                    ]);
                } else {
                    PosCustomerResetPassword::create($insertArray);
                }

                $otp = random_int(1000, 9999);
                $text = 'Dear Customer, Forgot your password We have received a request to reset the password for your KIAASA account. OTP to reset password is '.$otp.''; 
                $checkPhone = PosCustomerTempOtp ::where('phone',$pos_customer_info->phone)->first();
                if($checkPhone)
                {
                    PosCustomerTempOtp::where('email',$request->email)->update([
                        'phone' => $pos_customer_info->phone,
                        'otp' => $otp
                    ]);
                } else {
                    PosCustomerTempOtp::create([
                        'phone' => $pos_customer_info->phone,
                        'otp' => $otp
                    ]);
                }
                $response_data = CommonHelper::dynamic_otp_send($pos_customer_info->phone,$text);
                // \Mail::to($request->email)->send(new \App\Mail\ForgetPasswordMail($token)); 
                return response()->json(['httpStatus'=>200, 'dateTime'=>time(),'status' => 'success', 'message' => 'We have e-mailed your password reset link!']);

            }catch (\Exception $th) { 
                return response()->json(['status' => 'fail', 'error_message' => $th->getMessage()], 400);
            }
        }
    }

    // pos customer reset pasword function 
    public function posCustomeResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:pos_customer',
            'token' => 'required|token',
            'password' => 'required|password'
        ]);

        if($validator->fails())
        {
            return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>$validator->errors());
        } else {
            try {
                // check token exists
                if(!empty($request->token))
                {
                    $tokenCheck = PosCustomerResetPassword::where('token',$request->token)->first();
                    if(empty($tokenCheck))
                    {
                        return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Token is incorrect', 'errors' => 'Token is incorrect');
                    }
                }

                // check token expire time 
                $diffTime = strtotime("now") - strtotime($tokenCheck->updated_at); 
                // 300 secends 
                if($diffTime <= 300)
                {
                    $checkEmailId = Pos_customer :: Where('email',$request->email)->first();
                    if($checkEmailId)
                    {
                        Pos_customer :: Where('email',$request->email)->update([
                            'email' => $request->email,
                            'password' => Hash::make($request->password),
                            'updated_at' => date('d-m-Y H:i:s')
                        ]);
                        return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'success', 'message'=>'Password created successfully!');
                    } else {
                        return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Something is wrong, Please try again!', 'errors' => 'Email id does not exists!');
                    }
                } else {
                    return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Something is wrong, Please try again!', 'errors' => 'Token is expire!');
                }
            } catch (\Exception $th) {
                return response()->json(['status' => 'fail', 'error_message' => $th->getMessage()], 400);
            }
        }
    }

    // loggedin user product wishlist add 
    public function pos_product_wishlist(Request $request)
    {
        try {
            $data = $request->all();  

            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            $validateionRules = ['product_id'=> 'required|numeric|min:1|not_in:0','customer_id'=>'required|numeric|min:1|not_in:0'];
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails())
            { 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            
            $reqData = ['product_id'=>trim($data['product_id']),'customer_id' => trim($data['customer_id'])];
              
            $response_data = CommonHelper::add_pos_product_wish_list($reqData);
            return response($response_data,$response_data['httpStatus']);

        } catch (\Exception $th) {
            return response()->json(['status' => 'fail', 'error_message' => $th->getMessage()], 400);
        }
    }


    // loggedin user get wishlist product 
    public function get_customer_product_wishlist(Request $request,$customerId)
    {
        try {
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' )
            {
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            if (!is_numeric($customerId))
            { 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => 'The customer id must be an integer.'));
            } 

            if($customerId <= 0)
            {
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => 'The customer id must be a positive and greater then 0'));
            }

            $posWishlist = PosProductWishlist::where('pos_product_wishlist.user_id',$customerId)
                                    ->leftJoin('pos_product_images', 'pos_product_images.product_id','=','pos_product_wishlist.product_id')
                                    ->leftJoin('pos_product_master', 'pos_product_master.id','=','pos_product_wishlist.product_id')
                                    ->select(
                                        'pos_product_wishlist.id', 
                                        'pos_product_master.id as product_id', 
                                        'pos_product_master.product_name', 
                                        'pos_product_master.product_description',
                                        'pos_product_master.base_price',
                                        'pos_product_master.sale_price',
                                        'pos_product_images.image_name',
                                        'pos_product_images.image_type',
                                        'pos_product_wishlist.store_id'
                                        )
                                    ->get(); 
            $result = [];
            if(count($posWishlist) == 0)
            {
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => 'The customer id does not exist!'));
            } else {
                $data = [];
                foreach($posWishlist as $key => $value)
                {
                    if(!array_key_exists($value->id, $data)){
                        $data[$value->id] = $value;
                    }elseif($value->image_type!='close'){
                        if($data[$value->id]->image_type=='front'){
                            $data[$value->id]->image_url_front = str_replace('index.php','',url('/')).'/images/pos_product_images/'.$value->product_id.'/'.trim($data[$value->id]->image_name);
                            $data[$value->id]->image_url_back = str_replace('index.php','',url('/')).'/images/pos_product_images/'.$value->product_id.'/'.trim($value->image_name);
                        }
                        
                        elseif($data[$value->id]->image_type=='back'){
                            $data[$value->id]->image_url_front = str_replace('index.php','',url('/')).'/images/pos_product_images/'.$value->product_id.'/'.trim($value->image_name);
                            $data[$value->id]->image_url_back = str_replace('index.php','',url('/')).'/images/pos_product_images/'.$value->product_id.'/'.trim($data[$value->id]->image_name);
                        }
                    }
                }

                foreach ($data as $value) 
                {
                    $result[] = $value;
                }
                return array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Get Wishlist Product','customer_data'=>(array)$result);
            }
        
        } catch (\Throwable $th) {
            return response()->json(['status' => 'fail', 'error_message' => $th->getMessage()], 400);
        }
    }

    public function destroy_customer_product_wishlist(Request $request)
    {
        try {
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' )
            {
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            $validateionRules = ['product_id'=> 'required|numeric|min:1|not_in:0','customer_id'=>'required|numeric|min:1|not_in:0'];
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails())
            { 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            } 
            $id = $request->product_id;
            $customerId = $request->customer_id;
            $checkData = PosProductWishlist::where('product_id',$id)->where('user_id',$customerId)->first();
            if(empty($checkData))
            {
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error', 'errors' =>'This ID does not exist!'));
            }
            PosProductWishlist::where('product_id',$id)->where('user_id',$customerId)->delete();
            return array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'This Product remove from your wishlist successfully!');
        } catch (\Throwable $th) {
            return response()->json(['status' => 'fail', 'error_message' => $th->getMessage()], 400);
        }
    }

    // guest check out send otp to mobile
    public function guest_customer_send_otp(Request $request)
    {
        try {
            $data = $request->all();
            $number = $data['mobile_no'];
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            $validateionRules = ['mobile_no'=> 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/|digits:10'];
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails())
            { 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            }
            // save phone no and opt for temp 
            $otp = random_int(1000, 9999);
            $text = 'Dear, OTP for joining KIAASA is '.$otp.'. You can start creating your first order soon after your account is created. Happy Shopping!';
            // check if no exists or not 
            $checkExistNo = PosCustomerTempOtp:: where('phone',$number)->first();
            if($checkExistNo)
            {
                PosCustomerTempOtp::where('phone',$number)->update(['phone' =>$number,'otp' => $otp]);
            } else {
                PosCustomerTempOtp::create(['phone' =>$number,'otp' => $otp]);
            }
            $response_data = CommonHelper::dynamic_otp_send($number,$text);
            return array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'We have sent OTP to your mobile number, please check','customer_data' => $number);

        } catch (\Throwable $th) {
            return response()->json(['status' => 'fail', 'error_message' => $th->getMessage()], 400);
        }
    }

    // verify otp then create user 
    public function guest_customer_verify_otp(Request $request)
    {
        try {
            $data = $request->all();
            $content_type_header = $request->header('Content-Type');
            if(empty($content_type_header) || strtolower($content_type_header) != 'application/json' ){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Invalid Content-Type Header', 'errors' => 'Invalid Content-Type Header'),200);
            }
            $validateionRules = ['otp'=> 'required|numeric|digits:4', 'mobile_no'=> 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/|digits:10'];
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails())
            { 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation Error', 'errors' => $validator->errors()));
            }
            $checkMobileNo = PosCustomerTempOtp ::where('phone',$data['mobile_no'])->first();
            if(empty($checkMobileNo))
            {
                return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Something is wrong, Please try again!', 'errors' => 'Your phone number does not match our records!');
            }

            $checkOtp = PosCustomerTempOtp ::where('otp',$data['otp'])->first();
            if(empty($checkOtp))
            {
                return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'You have enter wrong OTP, Please check', 'errors' => 'You have enter wrong OTP, Please check');
            }
            // otp expire validation (valid only 2 minutes)
            $diffTime = strtotime("now") - strtotime($checkOtp->updated_at); 
            // 120 secends 
            if($diffTime <= 120)
            {
                // if otp is valid then create new user....
                $pos_customer = Pos_customer::where('phone',$checkOtp->phone)->where('is_deleted',0)->first();            
                if($pos_customer) 
                {
                    return array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Pos customer','customer_data'=>$pos_customer);
                } else {
                    $pos_customer_new = Pos_customer::create([
                        'phone' => $checkOtp->phone
                    ]);
                    return array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Pos customer Created','customer_data'=>$pos_customer_new);
                }
            } else {
                return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Something is wrong, Please try again!', 'errors' => 'Otp is expire!');
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'fail', 'error_message' => $th->getMessage()], 400);
        }
    }


    // testing shiprocket 
    public function customer_ship_rocket(Request $request)
    {
        // return $loginDetails =  Shiprocket::login([
        //     'email' => 'chandan.sharma@kindlebit.com', 
        //     'password' => 'Welcome2kb'
        // ]);

        $orderDetails = [
            "order_id"=> "224-44491",
            "order_date"=> "2022-11-26 11:11",
            "pickup_location"=> "hq",
            "channel_id"=> "",
            "comment"=> "sdsd",
            "billing_customer_name"=> "CUSTOMER 1",
            "billing_last_name"=> "CUSTOMER LASTNAME",
            "billing_address"=> "House 221B, Leaf Village",
            "billing_address_2"=> "Near Hokage House",
            "billing_city"=> "New Delhi",
            "billing_pincode"=> "201002",
            "billing_state"=> "Delhi",
            "billing_country"=> "India",
            "billing_email"=> "kiaasa.pr@gmail.com",
            "billing_phone"=> "9897753786",
            "shipping_is_billing"=> true,
            "shipping_customer_name"=> "",
            "shipping_last_name"=> "",
            "shipping_address"=> "",
            "shipping_address_2"=> "",
            "shipping_city"=> "",
            "shipping_pincode"=> "",
            "shipping_country"=> "",
            "shipping_state"=> "",
            "shipping_email"=> "",
            "shipping_phone"=> "",
            "order_items"=> "2",
                "name"=> "COD",
                "sku"=> "Tshirt-Blue-32",
                "units"=> 1,
                "selling_price"=> "1139.80",
                "discount"=> "",
                "tax"=> "",
                "hsn"=> 4435,
            "payment_method"=> "Prepaid",
            "shipping_charges"=> 0,
            "giftwrap_charges"=>0,
            "transaction_charges"=> 0,
            "total_discount"=> 0,
            "sub_total"=> 1139.80,
            "length"=> 10,
            "breadth"=> 15,
            "height"=> 20,
            "weight"=> 2.5,
        ];

        // $token = Shiprocket::getToken();
        
        // $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjE0NTQwODUsImlzcyI6Imh0dHBzOi8vYXBpdjIuc2hpcHJvY2tldC5pbi92MS9leHRlcm5hbC9hdXRoL2xvZ2luIiwiaWF0IjoxNjc0NjMwMTgwLCJleHAiOjE2NzU0OTQxODAsIm5iZiI6MTY3NDYzMDE4MCwianRpIjoibTRESVFYOVdQdEVDVE5TWiJ9.1ZXa3Bau0FXwMlrNe-i7MUy70VyWdQ0s3cbq3AlR8_0';
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjMyMjc5NTgsImlzcyI6Imh0dHBzOi8vYXBpdjIuc2hpcHJvY2tldC5pbi92MS9leHRlcm5hbC9hdXRoL2xvZ2luIiwiaWF0IjoxNjc0NjMxMTk5LCJleHAiOjE2NzU0OTUxOTksIm5iZiI6MTY3NDYzMTE5OSwianRpIjoiM2NnajBTaUltN2RxZVRJcyJ9.aWBmhpB3CE7dPYt4ySH4EyksKuK0iz92WkOyQkoGp_k';
        $response =  Shiprocket::order($token)->create($orderDetails);

        return $response;
    }
}
