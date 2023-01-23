<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Discount;
use App\Models\Store;
use App\Models\Product_category_master; 
use App\Http\Controllers\DateTime;
use Carbon\Carbon;
use App\Models\Pos_product_master;
use App\Models\Store_asset_detail;
use App\Models\Store_asset_order;
use App\Models\Store_asset_order_detail;
use App\Models\Store_asset_bills;
use App\Models\Store_products_demand;
use App\Models\Store_products_demand_detail;
use App\Models\Design_lookup_items_master;
use App\Models\Pos_product_master_inventory;
use App\Models\Production_size_counts;
use App\Models\Discount_list;
use App\Helpers\CommonHelper;
use PDF;
use Validator;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
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
    
    
    public function adddiscount(Request $request){
        try{   
            $data = $request->all();
            
            $gst_including  = (isset($data['gst_including']) && $data['gst_including'] == 1)?1:0;

            $validateionRules = array('discount_type'=>'required','from_date'=>'required','to_date'=>'required','inv_type'=>'required');
            $attributes = array('discount_type'=>'Name','inv_type'=>'Inventory Type');
            
            if(isset($data['discount_type']) && $data['discount_type'] == 1){
                $validateionRules['discount_percent'] = 'required|numeric';
            }
            
            if(isset($data['discount_type']) && $data['discount_type'] == 2){
                $validateionRules['flat_price'] = 'required|numeric';
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if(!empty($data['product_sku'])){
                $product_data = Pos_product_master::where('product_sku',trim($data['product_sku']))->where('is_deleted',0)->first();
                if(empty($product_data)){
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Product SKU does not exists', 'errors' => 'Product SKU does not exists' ));
                }
            }
                
            $insertArray = array('sku'=>trim($data['product_sku']),'discount_type'=>$data['discount_type'],'flat_price'=>$data['flat_price'],'discount_percent'=>$data['discount_percent'],
            'category_id'=>$data['category_id'],'store_id'=>$data['store_id'],'from_date'=> $data['from_date'] ,'to_date'=> $data['to_date'],'gst_including'=>$gst_including ,'season'=>$data['season_id'],
            'from_price'=>$data['from_price'],'to_price'=>$data['to_price'],'inv_type'=>$data['inv_type']);
            
            $discount_exists = Discount::where($insertArray)->where('is_deleted',0)->first();
            if(!empty($discount_exists)){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Discount already exists. ID: '.$discount_exists->id, 'errors' => 'Discount already exists. ID: '.$discount_exists->id ));
            }
          
            $discount = Discount::create($insertArray);
            
            CommonHelper::createLog('Discount Added. ID: '.$discount->id,'DISCOUNT_ADDED','DISCOUNT');
           
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Discount created successfully'),200);
                        
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updatediscount(Request $request){
        try{
            $data = $request->all();
            $discounts_ids = explode(',',$data['ids']);       
               $action = strtolower($data['action']);
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Discount');
            
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
                
            Discount::whereIn('id',$discounts_ids)->update($updateArray);               
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Discounts updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }   
    
    public function getdiscount(Request $request,$barcode){
        try{ 
            $data = $request->all();
            if($barcode ==''){
                   return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => 'Please check product barcode'));
            }
            $productData = Pos_product_master_inventory::where('peice_barcode',$barcode)->where('product_status',4)->select('product_master_id','store_id','store_base_price')->get()->first();
            if($productData){
                $productData = $productData->toArray();
                $productMasterData = Pos_product_master::where('id',$productData['product_master_id'])->select('product_sku as sku','category_id','season_id as season')->get()->first();
               if($productMasterData){
                   $productMasterData = $productMasterData->toArray();
                   foreach($productMasterData as $productMasterKey=>$productMasterValue){
                       $productData[$productMasterKey]=$productMasterValue;
                   }
               }                
            }else{
               return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => 'Please check product barcode'));
            }
            $matchAttributeWeightArr = array("store_id" => 100,"sku" => 1000,"category_id" => 100,"season" => 100); 
            $currentDate = Carbon::now()->format('Y-m-d');
            $store_id =$productData['store_id'];
            $sku =$productData['sku'];
            $category_id =$productData['category_id'];
            $season =$productData['season'];
            
            $discount_list = Discount::where('is_deleted',0)
            ->where('from_date','<=',$currentDate)
            ->where('to_date','>=',$currentDate)
            ->where(function ($discount_list) use ($store_id){ $discount_list->where('store_id', '=',$store_id)
                ->orWhereNull('store_id');
            })
            ->where(function ($discount_list) use ($sku){ $discount_list->where('sku', '=',$sku)
                ->orWhereNull('sku');
            })                                
            ->where(function ($discount_list) use ($category_id){ $discount_list->where('category_id', '=',$category_id)
                ->orWhereNull('category_id');
            })                               
            ->where(function ($discount_list) use ($season){ $discount_list->where('season', '=',$season)
                ->orWhereNull('season');
            });
              
            $discount_list= $discount_list->select('id','category_id','store_id','sku','discount_type','flat_price','discount_percent','gst_including','season')->get()->toArray();  
            $discountRowWeightArr= array();
            $discountRowsData =array();        
         
            if(empty($discount_list)){
               return response(array('httpStatus'=>200, 'dateTime'=>time(), 'data'=>array(), 'status'=>'success','message' => 'Discounts match not exist','status' => 'success'),200);
            }
            foreach($discount_list as $discount_list_arr){
                 $matchWeight =0;
                 $commonResult=array_intersect_assoc($productData,$discount_list_arr);
                 foreach($commonResult as $commonResultKey=>$value){
                     $matchWeight = $matchWeight+ $matchAttributeWeightArr[$commonResultKey];
                 }
                $discountRowWeightArr[$discount_list_arr['id']] =  $matchWeight;                        
                $discountRowsData[$discount_list_arr['id']] = $discount_list_arr;                 
            }
            $maxIndex='';
            $maxValue=0;
            foreach($discountRowWeightArr as $key=>$value){
                if($value>=$maxValue){
                    $maxValue = $value;                    
                    $maxIndex = $key;
                }
            } 
            if( $discountRowsData[$maxIndex]['discount_type'] ==1){ unset($discountRowsData[$maxIndex]['flat_price']); }else{ unset($discountRowsData[$maxIndex]['discount_percent']); }
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'data'=>$discountRowsData[$maxIndex], 'status'=>'success','message' => 'Discounts matched successfully','status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function discountList(Request $request){
        try{
            ini_set('memory_limit', '-1');
            $data = $request->all();          
            $product_sku_arr = $store_list_arr = $category_list_arr = $season_arr = array();
            $discount_list_count = 0;
            
            if(isset($data['action']) && $data['action'] == 'filter_sku_list'){
                $product_sku = Pos_product_master::where('is_deleted',0);
                
                if(isset($data['category_id']) && !empty($data['category_id'])){
                    $product_sku = $product_sku->where('category_id',$data['category_id']);
                }
                
                if(isset($data['season_id']) && !empty($data['season_id'])){
                    $product_sku = $product_sku->where('season_id',$data['season_id']);
                }
                
                if(isset($data['store_id']) && !empty($data['store_id'])){
                    $sql = 'SELECT DISTINCT ppm.product_sku from pos_product_master as ppm INNER JOIN pos_product_master_inventory as ppmi ON ppm.id = ppmi.product_master_id WHERE ppmi.store_id = '.$data['store_id'];
                    $product_sku = $product_sku->whereRaw("product_sku IN($sql)");
                }
                
                $product_sku = $product_sku->distinct()->get(['product_sku'])->toArray(); 
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'SKU List','product_sku'=>$product_sku),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'check_discount'){
                $qr_code = trim($data['qr_code']);
                
                $inventory_data = \DB::table('pos_product_master_inventory as ppmi')
                ->where('ppmi.peice_barcode',$qr_code)        
                ->wherein('ppmi.product_status',[4,5])
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->first();
                
                if(empty($inventory_data)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product does not exists', 'errors' => 'Product does not exists' ));
                }
                
                $store_user_data = \DB::table('store_users as su')->where('su.store_id',$inventory_data->store_id)->where('is_deleted',0)->first(); 
                
                if(empty($store_user_data)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store user does not exists', 'errors' => 'Store user does not exists' ));
                }        
                
                $product_data = CommonHelper::getPosProductData($qr_code,'',$store_user_data->user_id);
                
                $discount_percent = $product_data->discount_percent;
                
                $product_data->discount_amount = $discount_amount = (empty($discount_percent))?0:round(($product_data->sale_price*($discount_percent/100)),6);
                $product_data->discounted_price = $discounted_price = $product_data->sale_price-$discount_amount;
                
                $store_user = CommonHelper::getUserStoreData($store_user_data->user_id);
                
                if($store_user->gst_applicable == 1){
                    $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$discounted_price);
                    $gst_percent = (!empty($gst_data))?$gst_data->rate_percent:0;
                }else{
                    $gst_percent = 0;
                }
                
                if($discount_percent == 0 || ($discount_percent > 0 && $product_data->gst_inclusive == 1)){
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
                
                $product_data->gst_amount = round($gst_amount,2);
                $product_data->net_price = round($net_price,2);
                $product_data->discounted_price = round($product_data->discounted_price,2);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'SKU List','product_data'=>$product_data),200);
            }
            
            $discount_list = Discount::where('is_deleted',0);
            
            if(isset($data['sku']) && !empty($data['sku'])){
                $sku_1 = trim($data['sku']);
                $sku_2 = substr($sku_1,0, strrpos($sku_1, '-'));
                $discount_list = $discount_list->whereRaw("(sku = '$sku_1' OR sku = '$sku_2')");
            }
            
            if(isset($data['discount_percent']) && $data['discount_percent'] != ''){
                $discount_list = $discount_list->where('discount_type',1)->where('discount_percent',trim($data['discount_percent']));
            }
            
            if(isset($data['discount_rate']) && $data['discount_rate'] != ''){
                $discount_list = $discount_list->where('discount_type',2)->where('flat_price',trim($data['discount_rate']));
            }
            
            if(isset($data['store_id']) && trim($data['store_id']) != ''){
                $discount_list = $discount_list->where('store_id',trim($data['store_id']));
            }
            
            if(isset($data['id']) && !empty($data['id'])){
                $discount_list = $discount_list->where('id',$data['id']);
            }
            
            if(isset($data['startDate']) && trim($data['startDate']) != '' && isset($data['endDate']) && trim($data['endDate']) != ''){
                $dates = CommonHelper::getSearchStartEndDate($data,false);
                $start_date = $dates['start_date'];
                $end_date = $dates['end_date'];
                $discount_list = $discount_list->whereRaw("(DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date')");
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                
                $discount_count = trim($data['discount_count']);
                $discount_count_arr = explode('_',$discount_count);
                $start = $discount_count_arr[0];
                $start = $start-1;
                $end = $discount_count_arr[1];
                $limit = $end-$start;
                $discount_list = $discount_list->offset($start)->limit($limit)->orderBy('id')->get();
                
            }else{
                $discount_list_count = clone ($discount_list);
                $discount_list_count = $discount_list_count->count();
                $discount_list = $discount_list->orderBy('id','DESC')->paginate(1000);
            }
           
            $store_list = CommonHelper::getStoresList();
            foreach($store_list as $store_list_obj){
                $store_list_arr[$store_list_obj['id']] = $store_list_obj;
            }            
           
            $category_list = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY'))->where('status',1)->orderBy('name','ASC')->get()->toArray();
            foreach($category_list as $category_list_obj){
                $category_list_arr[$category_list_obj['id']] = $category_list_obj['name'];
            }            
            
            /*$product_sku = Pos_product_master::where('is_deleted',0)->distinct()->orderBy('product_sku')->get(['product_sku'])->toArray();  //product_sku   
            $product_sku_arr= array();
            foreach($product_sku as $product_sku_obj){
                $product_sku_arr[$product_sku_obj['product_sku']] = $product_sku_obj['product_sku'];
            } */ 
            
            $season= design_lookup_items_master::whereraw("upper(type) = 'SEASON'")->get()->toArray(); //product_sku 
            foreach($season as $season_obj){
                $season_arr[$season_obj['id']] = $season_obj['name'];
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=discount_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('ID No','Discount Type','Percent','Flat Price','Category','Season','Store Name','Store Code','SKU','From Price','To Price','From Date','To Date','GST Included','Inventory');

                $callback = function() use ($discount_list, $columns,$store_list_arr,$season_arr,$product_sku_arr,$category_list_arr){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $discountType=array('1'=>'Percent','2'=>'FlatPrice','3'=>'FreeItems','4'=>'Bill');
                    $currency = CommonHelper::getCurrency();
                    
                    foreach($discount_list as $discount_list_arr){
                        $flat_price = (!empty($discount_list_arr['flat_price']))?$currency.' '.$discount_list_arr['flat_price']:'';
                        $category = isset( $category_list_arr[$discount_list_arr['category_id']])?$category_list_arr[$discount_list_arr['category_id']] : '';
                        $season = isset( $season_arr[$discount_list_arr['season']])?$season_arr[$discount_list_arr['season']] : '';
                        $store = isset( $store_list_arr[$discount_list_arr['store_id']])?$store_list_arr[$discount_list_arr['store_id']]['store_name'] : '';
                        $store_code = isset( $store_list_arr[$discount_list_arr['store_id']])?$store_list_arr[$discount_list_arr['store_id']]['store_id_code'] : '';
                        $sku = $discount_list_arr['sku']; //isset( $product_sku_arr[$discount_list_arr['sku']])?$product_sku_arr[$discount_list_arr['sku']] : '';
                        $gst_including = ($discount_list_arr['gst_including']==1)?'Yes':'No';
                        $inv_type = ($discount_list_arr['inv_type']==1)?CommonHelper::getInventoryType(1):CommonHelper::getInventoryType(2);
                        
                        $array = array($discount_list_arr['id'],$discountType[$discount_list_arr['discount_type']],$discount_list_arr['discount_percent'],$flat_price,$category,$season,$store,$store_code,$sku,$discount_list_arr['from_price'],$discount_list_arr['to_price'],date('d-m-Y', strtotime($discount_list_arr['from_date'])),date('d-m-Y', strtotime($discount_list_arr['to_date'])),$gst_including,$inv_type );

                        fputcsv($file, $array);
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $discount_percents = Discount::where('discount_type',1)->where('is_deleted',0)->selectRaw("DISTINCT discount_percent")->orderBy('discount_percent')->get()->toArray();
            $discount_flat_rates = Discount::where('discount_type',2)->where('is_deleted',0)->selectRaw("DISTINCT flat_price")->orderBy('flat_price')->get()->toArray();
            
            return view('discount/list',array('discount_list'=>$discount_list,'store_list'=>$store_list_arr,'season'=>$season_arr,'product_sku'=>$product_sku_arr,
            'category_list_arr'=>$category_list_arr,'discount_percents'=>$discount_percents,'discount_flat_rates'=>$discount_flat_rates,'discount_list_count'=>$discount_list_count,'error_message'=>'')); 
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('discount/list',array('error_message'=>$e->getMessage().', '.$e->getLine()));
            }
        }
    }    
    
    function inventoryPushDemandDiscountList(Request $request,$id){
        try{
           
            $data = $request->all();          
            $demand_id = $id;
            $store_list = $category_list = $season_list = $demand_skus = $sku_with_discounts = $sku_with_no_discounts = $sku_data_list = array();
            
            $demand_data = store_products_demand::where('id',$demand_id)->first();
            
            // List of demand skus
            $demand_sku_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->where('spdi.demand_id',$demand_id)
            ->where('spdi.is_deleted',0)
            ->where('ppmi.is_deleted',0)        
            ->where('ppm.is_deleted',0);                
            
            if(isset($data['sku']) && !empty($data['sku'])){
                $sku_1 = trim($data['sku']);
                $sku_2 = substr($sku_1,0, strrpos($sku_1, '-'));
                $demand_sku_list = $demand_sku_list->whereRaw("(ppm.product_sku = '$sku_1' OR ppm.product_sku = '$sku_2')");
            }
            
            $demand_sku_list = $demand_sku_list->groupBy('ppm.product_sku')        
            ->select('ppm.product_sku')
            ->orderBy('spdi.id')        
            ->paginate(100);
            
            // create sku array
            for($i=0;$i<count($demand_sku_list);$i++){
                $demand_skus[] = $demand_sku_list[$i]->product_sku;
            }
            
            $demand_skus = array_values(array_unique($demand_skus));
            
            // Fetch discounts list for demand skus
            $discount_list = \DB::table('discount_master as dm')
            ->wherein('dm.sku',$demand_skus)   
            ->where('dm.is_deleted',0)    
            ->orderBy('sku')
            ->get()->toArray();
            
            for($i=0;$i<count($discount_list);$i++){
                $sku_with_discounts[] = $discount_list[$i]->sku;
            }
            
            // List of skus with no discounts
            for($i=0;$i<count($demand_skus);$i++){
                if(!in_array($demand_skus[$i], $sku_with_discounts)){
                    $sku_with_no_discounts[] = $demand_skus[$i];
                }
            }
            
            $sku_data = Pos_product_master::wherein('product_sku',$demand_skus)->get()->toArray();
            for($i=0;$i<count($sku_data);$i++){
                $sku_data_list[strtolower($sku_data[$i]['product_sku'])] = $sku_data[$i];
            }
            
            $stores = Store::where('status',1)->where('is_deleted',0)->select('*')->get()->toArray();
            
            for($i=0;$i<count($stores);$i++){
                $store_list[$stores[$i]['id']] = $stores[$i]['store_name'];
            }            
           
            $categories = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY'))->where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();

            for($i=0;$i<count($categories);$i++){
                $category_list[$categories[$i]['id']] = $categories[$i]['name'];
            }             
            
            $seasons = design_lookup_items_master::whereraw("upper(type) = 'SEASON'")->where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            
            for($i=0;$i<count($seasons);$i++){
                $season_list[$seasons[$i]['id']] = $seasons[$i]['name'];
            }  
           
           return view('discount/inventory_push_demand_discount_list',array('demand_sku_list'=>$demand_sku_list,'error_message'=>'','store_list'=>$store_list,'category_list'=>$category_list,'season_list'=>$season_list,'demand_data'=>$demand_data,'discount_list'=>$discount_list,'sku_with_no_discounts'=>$sku_with_no_discounts,'sku_data_list'=>$sku_data_list)); 
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('discount/inventory_push_demand_discount_list',array('error_message'=>$e->getMessage().', '.$e->getLine(),'store_list'=>array()));
        }
    }
    
    // Function for buy 1 get 3 discounts listing
    function discountsList(Request $request){
        try{
            $data = $request->all();          
            
            if(isset($data['action']) && $data['action'] == 'add_discount'){
                $validateionRules = array('gst_type_add'=>'required','buy_items_add'=>'required','get_items_add'=>'required');

                $attributes = array('gst_type_add'=>'GST Type','buy_items_add'=>'Buy Items','get_items_add'=>'Get Items');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                if($data['buy_items_add'] > $data['get_items_add']){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Buy items should not be less than Get items', 'errors' => 'Buy items should not be less than Get items' ));
                }
                
                $discount = Discount_list::where('buy_items',$data['buy_items_add'])->first();
                
                if(!empty($discount) && $discount->is_deleted == 0){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Discount is already added', 'errors' => 'Discount is already added' ));
                }
                
                if(!empty($discount)){
                    $updateArray = array('buy_items'=>$data['buy_items_add'],'get_items'=>$data['get_items_add'],'gst_type'=>$data['gst_type_add'],'is_deleted'=>0);
                    Discount_list::where('id',$discount->id)->update($updateArray);
                }else{
                    $insertArray = array('buy_items'=>$data['buy_items_add'],'get_items'=>$data['get_items_add'],'gst_type'=>$data['gst_type_add'],'item_type'=>'multiple');
                    Discount_list::insert($insertArray);
                }
                
               return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Discount added successfully'),200);
            }
            
            
            if(isset($data['action']) && $data['action'] == 'get_discount_data'){
                $discount_data = Discount_list::where('id',$data['id'])->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Discount data','discount_data'=>$discount_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'edit_discount'){
                
                $discount = Discount_list::where('id',$data['discount_id_edit'])->first();
                
                if($discount->item_type == 'single'){
                    $validateionRules = array('gst_type_edit'=>'required','discount_edit'=>'required|numeric');
                    $attributes = array('gst_type_edit'=>'GST Type','discount_edit'=>'Discount');
                }else{
                    $validateionRules = array('gst_type_edit'=>'required','buy_items_edit'=>'required','get_items_edit'=>'required');
                    $attributes = array('gst_type_edit'=>'GST Type','buy_items_edit'=>'Buy Items','get_items_edit'=>'Get Items');
                }

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                if($discount->item_type == 'multiple'){
                    if($data['buy_items_edit'] > $data['get_items_edit']){
                        return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Buy items should not be less than Get items', 'errors' => 'Buy items should not be less than Get items' ));
                    }
                    
                    $discount_exists = Discount_list::where('buy_items',$data['buy_items_edit'])->where('id','!=',$data['discount_id_edit'])->count();
                    
                    if($discount_exists > 0){
                        return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Discount is already added', 'errors' => 'Discount is already added' ));
                    }
                    
                    $updateArray = array('buy_items'=>$data['buy_items_edit'],'get_items'=>$data['get_items_edit'],'gst_type'=>$data['gst_type_edit'],'is_deleted'=>0);
                }else{
                    $updateArray = array('discount'=>$data['discount_edit'],'gst_type'=>$data['gst_type_edit'],'is_deleted'=>0);
                }
                
                Discount_list::where('id',$discount->id)->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Discount updated successfully'),200);
            }
            
            $discount_list = Discount_list::where('is_deleted',0)->paginate(100);
            
            return view('discount/discounts_list',array('discount_list'=>$discount_list,'error_message'=>'')); 
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                 \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
            }else{
                return view('discount/discounts_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    public function addMultipleDiscounts(Request $request){
        try{
            $data = $request->all();          
            
            if(isset($data['action']) && $data['action'] == 'get_sku_data'){
                $sku = trim($data['sku']);
                
                if(empty($sku)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'SKU is Required Field', 'errors' => 'SKU is Required Field' ));
                }
                
                $product_data = Pos_product_master::where('product_sku',$sku)->where('is_deleted',0)->first();
                if(empty($product_data)){
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'SKU does not exists', 'errors' => 'SKU does not exists' ));
                }
                
               return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'SKU Data','product_data'=>$product_data),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'import_sku'){
                // CSV file validations
                $sku_list = $invalid_sku_list = array();
                $validateionRules = array('uploadSKUCsvFile'=>'required|mimes:csv,txt|max:5120');
                $attributes = array('uploadSKUCsvFile'=>'CSV File');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'fail','message'=>'Validation error','errors' => $validator->errors()));
                }	

                // Upload CSV File Start
                $file = $request->file('uploadSKUCsvFile');
                $file_name_text = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(),'.'));
                $file_ext = $file->getClientOriginalExtension();
                $dest_folder = 'documents/import_discount_sku_csv';

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

                // Create SKU array from csv data
                $file = public_path($dest_folder.'/'.$file_name);
                if(($handle = fopen($file, "r")) !== FALSE) {
                    while (($csv_data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                        $sku = trim($csv_data[0]);
                        if(!empty($sku)){
                            $sku_list[] = trim($csv_data[0]);
                        }
                    }
                }
                
                for($i=0;$i<count($sku_list);$i++){
                    $product_data = Pos_product_master::where('product_sku',$sku_list[$i])->where('is_deleted',0)->select('product_sku')->first();
                    if(empty($product_data)){
                        $invalid_sku_list[] = $sku_list[$i];
                    }
                }
                
                if(!empty($invalid_sku_list)){ 
                    $error_msg = 'Following SKU does not exists:<br> '.implode('<br>',$invalid_sku_list);
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' => $error_msg));
                }
                
                fclose($handle);
                unlink($file);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','sku_list' => $sku_list,'message'=>'SKU imported successfully'),200);
            }
            
            
            if(isset($data['action']) && $data['action'] == 'get_zone_stores'){
                $zone_id = trim($data['zone_id']);
                if(!empty($zone_id)){
                    $stores_list = Store::where('zone_id',$zone_id)->where('is_deleted',0)->orderBy('store_name','ASC')->get()->toArray();
                }else{
                    $stores_list = Store::where('is_deleted',0)->orderBy('store_name','ASC')->get()->toArray();
                }
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','stores_list' => $stores_list,'message'=>'Zone Stores'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'add_multiple_discount'){
            
                \DB::beginTransaction();
                 
                $gst_including  = (isset($data['gst_type']) && $data['gst_type'] == 'inclusive')?1:0;

                $validateionRules = array('discount_type'=>'required','from_date'=>'required','to_date'=>'required','inv_type'=>'required','sku_list'=>'required');
                $attributes = array('discount_type'=>'Name','inv_type'=>'Inventory Type','sku_list'=>'SKU','store_list'=>'Stores');

                if(isset($data['discount_type']) && $data['discount_type'] == 1){
                    $validateionRules['discount_percent'] = 'required|numeric';
                }

                if(isset($data['discount_type']) && $data['discount_type'] == 2){
                    $validateionRules['flat_price'] = 'required|numeric';
                }

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $sku_list = explode(',',trim($data['sku_list']));
                $store_list = (!empty($data['store_list']))?explode(',',trim($data['store_list'])):[];
                
                // If discount are for stores else discount are without stores
                if(!empty($store_list)){
                    for($i=0;$i<count($store_list);$i++){
                        for($q=0;$q<count($sku_list);$q++){
                            $store_id = trim($store_list[$i]);
                            $sku = trim($sku_list[$q]);

                            $discountArray = array('sku'=>$sku,'discount_type'=>$data['discount_type'],'flat_price'=>$data['flat_price'],'discount_percent'=>$data['discount_percent'],
                            'category_id'=>$data['category_id'],'store_id'=>$store_id,'from_date'=> $data['from_date'] ,'to_date'=> $data['to_date'],'gst_including'=>$gst_including ,'season'=>$data['season_id'],
                            'from_price'=>$data['from_price'],'to_price'=>$data['to_price'],'inv_type'=>$data['inv_type']);

                            $discount_exists = Discount::where($discountArray)->where('is_deleted',0)->first();
                            if(!empty($discount_exists)){
                                $store_data = Store::where('id',$store_id)->select('store_name')->first();
                                $error_msg = 'Discount already exists. Discount ID: '.$discount_exists->id.', SKU:  '.$sku.', Store: '.$store_data->store_name;
                                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' => $error_msg ));
                            }
                        }
                    }

                    for($i=0;$i<count($store_list);$i++){
                        for($q=0;$q<count($sku_list);$q++){
                            $store_id = trim($store_list[$i]);
                            $sku = trim($sku_list[$q]);

                            $insertArray = array('sku'=>$sku,'discount_type'=>$data['discount_type'],'flat_price'=>$data['flat_price'],'discount_percent'=>$data['discount_percent'],
                            'category_id'=>$data['category_id'],'store_id'=>$store_id,'from_date'=> $data['from_date'] ,'to_date'=> $data['to_date'],'gst_including'=>$gst_including ,'season'=>$data['season_id'],
                            'from_price'=>$data['from_price'],'to_price'=>$data['to_price'],'inv_type'=>$data['inv_type']);

                            $discount = Discount::create($insertArray);
                        }
                    }
                }else{
                    for($q=0;$q<count($sku_list);$q++){
                        $sku = trim($sku_list[$q]);

                        $discountArray = array('sku'=>$sku,'discount_type'=>$data['discount_type'],'flat_price'=>$data['flat_price'],'discount_percent'=>$data['discount_percent'],
                        'category_id'=>$data['category_id'],'from_date'=> $data['from_date'] ,'to_date'=> $data['to_date'],'gst_including'=>$gst_including ,'season'=>$data['season_id'],
                        'from_price'=>$data['from_price'],'to_price'=>$data['to_price'],'inv_type'=>$data['inv_type']);

                        $discount_exists = Discount::where($discountArray)->where('is_deleted',0)->first();
                        if(!empty($discount_exists)){
                            $error_msg = 'Discount already exists. Discount ID: '.$discount_exists->id.', SKU:  '.$sku;
                            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' => $error_msg ));
                        }
                    }
                    
                    for($q=0;$q<count($sku_list);$q++){
                        $sku = trim($sku_list[$q]);

                        $insertArray = array('sku'=>$sku,'discount_type'=>$data['discount_type'],'flat_price'=>$data['flat_price'],'discount_percent'=>$data['discount_percent'],
                        'category_id'=>$data['category_id'],'from_date'=> $data['from_date'] ,'to_date'=> $data['to_date'],'gst_including'=>$gst_including ,'season'=>$data['season_id'],
                        'from_price'=>$data['from_price'],'to_price'=>$data['to_price'],'inv_type'=>$data['inv_type']);

                        $discount = Discount::create($insertArray);
                    }
                }
                
                \DB::commit();
                
                CommonHelper::createLog('Multiple Discounts Added.','MULTIPLE_DISCOUNTS_ADDED','DISCOUNT');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Discounts added successfully'),200);
            }
            
            $store_list = CommonHelper::getStoresList();
            $category_list = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY'))->where('status',1)->orderBy('name','ASC')->get()->toArray();
            $season_list = design_lookup_items_master::whereraw("upper(type) = 'SEASON'")->get()->toArray(); 
            $zone_list = design_lookup_items_master::whereraw("upper(type) = 'STORE_ZONE'")->get()->toArray(); 
            
            return view('discount/discount_add_multiple',array('error_message'=>'','store_list'=>$store_list,'category_list'=>$category_list,'season_list'=>$season_list,'zone_list'=>$zone_list)); 
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('discount/discount_add_multiple',array('error_message'=>$e->getMessage().', '.$e->getLine(),'store_list'=>array()));
            }
        }
    }
    
    public function editMultipleDiscounts(Request $request){
        try{
            $data = $request->all();          
            
            $store_list = CommonHelper::getStoresList();
            $category_list = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY'))->where('status',1)->orderBy('name','ASC')->get()->toArray();
            $season_list = design_lookup_items_master::whereraw("upper(type) = 'SEASON'")->get()->toArray(); 
            $zone_list = design_lookup_items_master::whereraw("upper(type) = 'STORE_ZONE'")->get()->toArray(); 
            
            return view('discount/discount_edit_multiple',array('error_message'=>'','store_list'=>$store_list,'category_list'=>$category_list,'season_list'=>$season_list,'zone_list'=>$zone_list)); 
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('discount/discount_edit_multiple',array('error_message'=>$e->getMessage().', '.$e->getLine(),'store_list'=>array()));
            }
        }
    }
    
    public function deleteMultipleDiscounts(Request $request){
        try{
            set_time_limit(300);
            $data = $request->all();
            $error_msg = '';
            
            $sku_data = $this->importDiscountSKU($request,$data);
            $dest_folder = $sku_data['dest_folder'];
            $file_name =  $sku_data['file_name'];
            $skus = $sku_data['skus'];

            if(!empty($sku_data['error_msg'])){
                if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                    unlink(public_path($dest_folder).'/'.$file_name);
                }

                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$sku_data['error_msg'], 'errors' =>$sku_data['error_msg']));
            }
            
            $updateArray = ['is_deleted'=>1];
            $updated_records = Discount::whereIn('sku',$skus)->update($updateArray);
            
            if(!empty($dest_folder) && !empty($file_name) && file_exists(public_path($dest_folder).'/'.$file_name)){
                unlink(public_path($dest_folder).'/'.$file_name);
            }
                
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Discounts deleted successfully ','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }   
    
    function importDiscountSKU($request,$data){
        $error_msg = $dest_folder = $file_name =  '';
        $skus = $skus_updated = array();
        
        // validations
        $validationRules = array('skuTxtFile'=>'required|mimes:txt|max:3072');
        $attributes = array('skuTxtFile'=>'SKU File');

        $validator = Validator::make($data,$validationRules,array(),$attributes);
        if ($validator->fails()){ 
            $error_msg = $validator->errors();
        }	

        if(empty($error_msg)){
            // Get file data
            $file = $request->file('skuTxtFile');
            $file_name_text = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(),'.'));
            $file_ext = $file->getClientOriginalExtension();
            $dest_folder = 'documents/product_sku_txt';

            $file_name = $file_name_text.'_'.rand(1000,1000000).'.'.$file_ext;
            $file->move(public_path($dest_folder), $file_name);
            $skus = file(public_path($dest_folder).'/'.$file_name);

            // maximum allowed sku is 500 validation
            if(count($skus) > 500){
                $error_msg = 'Maximum 500 SKU can be imported';
            }
            
            if(empty($error_msg)){
                for($i=0;$i<count($skus);$i++){
                    if(!empty(trim($skus[$i]))){
                        $skus_updated[$i] = substr(trim($skus[$i]),0,50);
                    }
                }

                $skus = array_values(array_unique($skus_updated));
            }
        }
        
        return ['skus'=>$skus,'error_msg'=>$error_msg,'dest_folder'=>$dest_folder,'file_name'=>$file_name];
    }
}


