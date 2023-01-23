<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Design;
use App\Models\Store;
use App\Models\Design_lookup_items_master;
use App\Models\Pos_customer_orders;
use App\Models\Pos_customer_orders_detail;
use App\Models\Pos_product_master;
use App\Models\Pos_product_master_inventory;
use App\Models\Gst_rates;
use App\Models\Category_hsn_code;
use App\Models\Vendor_detail;
use App\Models\Production_size_counts;
use App\Models\Story_master;
use App\Models\Store_report_types;
use Validator;
use PDF;
use Excel;
use App\Helpers\CommonHelper;
use App\Exports\InvoicesExport;
use App\Exports\ViewExport;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    
    public function __construct(){
    }
    
    function dashboard(Request $request){
        try{ 
            
            $pos_orders = Pos_customer_orders::where('is_deleted',0)->where('order_status',1)
            ->groupByRaw('Date(created_at)')->selectRaw('Date(created_at) as date_created,COUNT(id) as orders_count')
            ->orderBy('id','DESC')->limit(30)->get()->toArray();
            
            return view('admin/dashboard',array('error_message'=>'','pos_orders'=>$pos_orders));
        }catch (\Exception $e){
            return view('admin/dashboard',array('error_message'=>$e->getMessage(). ''.$e->getLine()));
        }
    }
    
    function storeSalesReportByDate(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $zone_id = $store_id = '';
            $sales_data = $payment_data = $store_data = $inv_type = $user_stores_list = $other_stores_ids = $store_ids = $store_report_data = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            $fake_inventory = $is_fake_inventory_user?[0,1]:[0];
            
            if($user->user_type == 9){
                // List of stores of store user
                $user_stores_list = \DB::table('store_users as su')
                ->join('store as s','s.id', '=', 'su.store_id')   
                ->where('su.user_id',$user->id)        
                ->where('su.is_deleted',0)
                ->where('s.is_deleted',0)        
                ->select('s.*')     
                ->orderBy('s.store_name')        
                ->get()->toArray();
                
                // Store ids of store user
                for($i=0;$i<count($user_stores_list);$i++){
                    $store_ids[] = $user_stores_list[$i]->id;
                }
            }
            
           $zone_id = (isset($_GET['s_zone_id']))?trim($_GET['s_zone_id']):'';
           
           if(isset($_GET['order_source']) && !empty($_GET['order_source'])){
               $order_source = (trim($_GET['order_source']) == 'pos_orders')?['web','api']:['website_api'];
           }else{
               $order_source = '';
           } 
             
            // If user have searched by store dropdown filter, then one store is used as array
            if((isset($data['s_id']) && !empty($data['s_id']))){
                $store_ids = array($data['s_id']);
                $store_data = Store::where('id',$data['s_id'])->where('status',1)->where('is_deleted',0)->select('*')->first();
            }elseif(count($store_ids) == 1){
                $store_data = Store::where('id',$store_ids[0])->where('status',1)->where('is_deleted',0)->select('*')->first();
            }else{
                $store_data = array();
            }
            
            if(!empty($store_data)){
                $store_report_data = Store_report_types::where('store_id',$store_data->id)->where('report','daily_sales_report')->where('is_deleted',0)->first();
                if(!empty($store_report_data->report_type)){
                    $store_inv_types = ['real'=>[0],'fake'=>[1],'both'=>[0,1]];
                    $fake_inventory = $store_inv_types[$store_report_data->report_type];
                }
            }
           
            
            // Sales data
            $sales_data = \DB::table('pos_customer_orders as pco')
            ->join('store as s','s.id', '=', 'pco.store_id')                
            ->join('pos_customer_orders_detail as pcod','pco.id', '=', 'pcod.order_id');         

            if(!empty($store_ids)){
                $sales_data = $sales_data->wherein('pco.store_id',$store_ids);
            }
            if(!empty($zone_id)){
                $sales_data = $sales_data->where('s.zone_id',$zone_id);
            }
            if(!empty($order_source)){
                $sales_data = $sales_data->wherein('pco.order_source',$order_source);
            }

            $sales_data = $sales_data
            ->where('pco.is_deleted',0)
            ->where('pcod.is_deleted',0)
            ->wherein('pco.fake_inventory',$fake_inventory) 
            ->wherein('pcod.fake_inventory',$fake_inventory)      
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)         
            ->groupByRaw("date(pco.created_at)")        
            ->selectRaw('date(pco.created_at) as sales_date,count(DISTINCT pco.id) as orders_count, SUM(pcod.base_price) as total_base_price,SUM(pcod.sale_price) as total_sale_price,SUM(pcod.net_price) as total_net_price,
            SUM(pcod.discount_amount_actual) as total_discount_amout,SUM(pcod.gst_amount) as total_gst_amout,SUM(pcod.product_quantity) as total_quantity');

            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('od'=>'sales_date','oc'=>'orders_count','tbp'=>'total_base_price','tsp'=>'total_sale_price','tnp'=>'total_net_price',
                'tda'=>'total_discount_amout');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'sales_date';
                $sales_data = $sales_data->orderBy($sort_by,CommonHelper::getSortingOrder());
            }else{
                $sales_data = $sales_data->orderBy('sales_date','DESC');
            }
            
            // Sales count data
            $sales_count = \DB::table('pos_customer_orders as pco')
            ->join('store as s','s.id', '=', 'pco.store_id')                
            ->join('pos_customer_orders_detail as pcod','pco.id', '=', 'pcod.order_id');   
            
            if(!empty($store_ids)){        
                $sales_count = $sales_count->wherein('pcod.store_id',$store_ids);
            }
            if(!empty($zone_id)){
                $sales_count = $sales_count->where('s.zone_id',$zone_id);
            }
            if(!empty($order_source)){
                $sales_count = $sales_count->wherein('pco.order_source',$order_source);
            }
            
            $sales_count = $sales_count->where('pcod.product_quantity','>',0)
            ->where('pcod.is_deleted',0)
            ->wherein('pcod.fake_inventory',$fake_inventory)        
            ->where('pcod.order_status',1)        
            ->groupByRaw("date(pcod.created_at)")
            ->selectRaw('date(pcod.created_at) as sales_date,COUNT(pcod.id) as sales_count');
            
            // Sales return data
            $return_count = \DB::table('pos_customer_orders as pco')
            ->join('store as s','s.id', '=', 'pco.store_id')                
            ->join('pos_customer_orders_detail as pcod','pco.id', '=', 'pcod.order_id');   
            
            if(!empty($store_ids)){        
                $return_count = $return_count->wherein('pcod.store_id',$store_ids);
            }        
            if(!empty($zone_id)){
                $return_count = $return_count->where('s.zone_id',$zone_id);
            }
            if(!empty($order_source)){
                $return_count = $return_count->wherein('pco.order_source',$order_source);
            }
            
            $return_count = $return_count->where('pcod.product_quantity','<',0)
            ->where('pcod.is_deleted',0)
            ->wherein('pcod.fake_inventory',$fake_inventory)        
            ->where('pcod.order_status',1)        
            ->groupByRaw("date(pcod.created_at)")
            ->selectRaw('date(pcod.created_at) as return_date,COUNT(pcod.id) as return_count');
            
            // Inventory type data
            $inv_type_data = \DB::table('pos_customer_orders as pco')
            ->join('store as s','s.id', '=', 'pco.store_id')                
            ->join('pos_customer_orders_detail as pcod','pco.id', '=', 'pcod.order_id');   
            
            if(!empty($store_ids)){        
                $inv_type_data = $inv_type_data->wherein('pcod.store_id',$store_ids);
            }     
            if(!empty($zone_id)){
                $inv_type_data = $inv_type_data->where('s.zone_id',$zone_id);
            }
            if(!empty($order_source)){
                $inv_type_data = $inv_type_data->wherein('pco.order_source',$order_source);
            }
            
            $inv_type_data = $inv_type_data->where('pcod.is_deleted',0)
            ->wherein('pcod.fake_inventory',$fake_inventory)     
            ->where('pcod.order_status',1)        
            ->groupByRaw("date(pcod.created_at),pcod.arnon_prod_inv")
            ->selectRaw('date(pcod.created_at) as order_date,pcod.arnon_prod_inv,SUM(pcod.product_quantity) as inv_count');

            // Payment method data
            $sales_data_payment_method = \DB::table('pos_customer_orders as pco')
            ->join('store as s','s.id', '=', 'pco.store_id')        
            ->join('pos_customer_orders_payments as pcop','pco.id', '=', 'pcop.order_id');

            if(!empty($store_ids)){
                $sales_data_payment_method = $sales_data_payment_method->wherein('pco.store_id',$store_ids);
            }
            if(!empty($zone_id)){
                $sales_data_payment_method = $sales_data_payment_method->where('s.zone_id',$zone_id);
            }
            if(!empty($order_source)){
                $sales_data_payment_method = $sales_data_payment_method->wherein('pco.order_source',$order_source);
            }

            $sales_data_payment_method = $sales_data_payment_method
            ->where('pco.is_deleted',0)
            ->where('pcop.is_deleted',0)
            ->wherein('pco.fake_inventory',$fake_inventory)    
            ->wherein('pcop.fake_inventory',$fake_inventory)    
            ->where('pco.order_status',1)         
            ->where('pcop.order_status',1)        
            ->groupByRaw("date(pco.created_at),pcop.payment_method")        
            ->selectRaw('date(pco.created_at) as sales_date,pcop.payment_method,SUM(pcop.payment_received) as total_net_price');

            // Date filter
            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $data_arr = explode('-',$data['startDate']);
                $data['startDate'] = $data_arr[2].'-'.$data_arr[1].'-'.$data_arr[0];
                $data_arr = explode('-',$data['endDate']);
                $data['endDate'] = $data_arr[2].'-'.$data_arr[1].'-'.$data_arr[0];
                $start_date = date('Y/m/d',strtotime(trim($data['startDate'])));//.' 00:00';
                $end_date = date('Y/m/d',strtotime(trim($data['endDate'])));//.' 23:59';
            }else{
                $start_date = date('Y/m/d',strtotime(CommonHelper::getDefaultDaysInterval()));//.' 00:00';
                $end_date = date('Y/m/d');//.' 23:59';
            }

            // Add date filter to data
            /*$sales_data = $sales_data->whereRaw("pco.created_at BETWEEN '$start_date' AND '$end_date'")->get();;//echo "pco.created_at BETWEEN '$start_date' AND '$end_date'";
            $sales_data_payment_method = $sales_data_payment_method->whereRaw("pco.created_at BETWEEN '$start_date' AND '$end_date'")->get();

            $sales_count = $sales_count->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'")->get()->toArray();
            $return_count = $return_count->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'")->get()->toArray();
            $inv_type_data = $inv_type_data->whereRaw("pcod.created_at BETWEEN '$start_date' AND '$end_date'")->get()->toArray();*/
            
            $sales_data = $sales_data->whereRaw("DATE(pco.created_at) >= '$start_date' AND DATE(pco.created_at) <= '$end_date'")->get();
            $sales_data_payment_method = $sales_data_payment_method->whereRaw("DATE(pco.created_at) >= '$start_date' AND DATE(pco.created_at) <= '$end_date'")->get();

            $sales_count = $sales_count->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")->get()->toArray();
            $return_count = $return_count->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")->get()->toArray();
            $inv_type_data = $inv_type_data->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")->get()->toArray();
            
            $inv_type_data = json_decode(json_encode($inv_type_data),true);
            
            for($i=0;$i<count($inv_type_data);$i++){
                $key = str_replace('-','_',$inv_type_data[$i]['order_date']).'__'.$inv_type_data[$i]['arnon_prod_inv'];
                $inv_type[$key] = $inv_type_data[$i]['inv_count'];
            }

            for($i=0;$i<count($sales_data_payment_method);$i++){
                $payment_data[date('Y-n-d',strtotime($sales_data_payment_method[$i]->sales_date))][strtolower($sales_data_payment_method[$i]->payment_method)] = $sales_data_payment_method[$i]->total_net_price;
            }
           
            // Insert sales and return units in payment record.
            for($i=0;$i<count($sales_data);$i++){
                $row = CommonHelper::getArrayRecord($sales_count,'sales_date',$sales_data[$i]->sales_date);
                $sales_data[$i]->items_count_1 = (isset($row['sales_count']))?$row['sales_count']:0;

                $row = CommonHelper::getArrayRecord($return_count,'return_date',$sales_data[$i]->sales_date);
                $sales_data[$i]->items_count_2 = (isset($row['return_count']))?$row['return_count']:0;
                
                $key = str_replace('-','_',$sales_data[$i]->sales_date).'__0';
                $sales_data[$i]->north_inv = (isset($inv_type[$key]))?$inv_type[$key]:0;
                
                $key = str_replace('-','_',$sales_data[$i]->sales_date).'__1';
                $sales_data[$i]->arnon_inv = (isset($inv_type[$key]))?$inv_type[$key]:0;
            }
                
            $response_data = array('error_message'=>$error_message,'sales_data'=>$sales_data,'store_data'=>$store_data,'data'=>$data,'user'=>$user,'payment_data'=>$payment_data);
            
            if(isset($data['action']) && $data['action'] == 'download'){
                $pdf = PDF::loadView('admin/report_store_date_sales_pdf', $response_data);
                return $pdf->download('report_store_date_sales_pdf');
            }
            
            // Download CSV Start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=daily_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                
                $store_name = (!empty($store_data))?$store_data->store_name.' ('.$store_data->store_id_code.')':'All Stores';
                $date_range = date('d-m-Y',strtotime($start_date)).' - '.date('d-m-Y',strtotime($end_date));
                $columns_heading = array('DAILY SALES REPORT',$store_name,$date_range);
                
                $columns_1 = array('Date','Total Orders','Sold Units','Return','Total Units','Arnon');
                if($user->user_type == 1){
                    $columns_1[] = 'Base Price';
                }
                $columns_2 = array('Sale Price','Discount','GST','NET Price','Cash','Card','E-Wallet');
                $columns = array_merge($columns_1,$columns_2);

                $callback = function() use ($response_data, $columns,$columns_heading){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns_heading);
                    fputcsv($file, $columns);
                    $sales_data = $response_data['sales_data'];
                    $user = $response_data['user'];
                    $payment_data = $response_data['payment_data'];
                    $total_data = array('orders'=>0,'units'=>0,'return_units'=>0,'total_units'=>0,'arnon_units'=>0,'base_price'=>0,'sale_price'=>0,'discount'=>0,'gst'=>0,'net_price'=>0,'cash'=>0,'card'=>0,'wallet'=>0);
                    
                    for($i=0;$i<count($sales_data);$i++){
                        $date = date('Y-n-d',strtotime($sales_data[$i]->sales_date));
                        $order_date = date('d M Y',strtotime($sales_data[$i]->sales_date));
                        $array = array($order_date,$sales_data[$i]->orders_count,$sales_data[$i]->items_count_1,$sales_data[$i]->items_count_2,$sales_data[$i]->items_count_1-$sales_data[$i]->items_count_2,$sales_data[$i]->arnon_inv);
                        if($user->user_type == 1){
                            $array[] = $sales_data[$i]->total_base_price;
                        }
                        
                        $array[] = $sales_data[$i]->total_sale_price;
                        $array[] = round($sales_data[$i]->total_discount_amout,2);
                        $array[] = round($sales_data[$i]->total_gst_amout,2);
                        $array[] = round($sales_data[$i]->total_net_price,2);
                        $array[] = $cash = isset($payment_data[$date]['cash'])?$payment_data[$date]['cash']:0.00;
                        $array[] = $card = isset($payment_data[$date]['card'])?$payment_data[$date]['card']:0.00;
                        $array[] = $wallet = isset($payment_data[$date]['e-wallet'])?$payment_data[$date]['e-wallet']:0.00;
                        fputcsv($file, $array);
                        
                        $total_data['orders']+=$sales_data[$i]->orders_count;
                        $total_data['units']+=$sales_data[$i]->items_count_1;
                        $total_data['return_units']+=$sales_data[$i]->items_count_2;
                        $total_data['total_units']+=$sales_data[$i]->items_count_1-$sales_data[$i]->items_count_2;
                        $total_data['arnon_units']+=$sales_data[$i]->arnon_inv;
                        
                        $total_data['base_price']+=$sales_data[$i]->total_base_price;
                        $total_data['sale_price']+=$sales_data[$i]->total_sale_price;
                        $total_data['discount']+=$sales_data[$i]->total_discount_amout;
                        $total_data['gst']+=$sales_data[$i]->total_gst_amout;
                        $total_data['net_price']+=$sales_data[$i]->total_net_price;
                        $total_data['cash']+=$cash;
                        $total_data['card']+=$card;
                        $total_data['wallet']+=$wallet;
                    }

                    $array_1 = array('Total',$total_data['orders'],$total_data['units'],$total_data['return_units'],$total_data['total_units'],$total_data['arnon_units']);
                    if($user->user_type == 1){
                        $array_1[] = round($total_data['base_price'],2);
                    }
                    
                    $array_2 = array(round($total_data['sale_price'],2),round($total_data['discount'],2),round($total_data['gst'],2),round($total_data['net_price'],2),round($total_data['cash'],2),round($total_data['card'],2),round($total_data['wallet'],2));
                    $array = array_merge($array_1,$array_2);
                    
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV End
            
            $store_list = Store::where('is_deleted',0);
            if(!empty($zone_id)){
                $store_list = $store_list->where('zone_id',$zone_id);
            }
            $store_list = $store_list->orderBy('store_name')->get()->toArray();
            
            $store_zones = Design_lookup_items_master::where('type','STORE_ZONE')->get()->toArray();
            
            $response_data['store_list'] = $store_list;
            $response_data['store_zones'] = $store_zones;
            $response_data['user_stores_list'] = $user_stores_list;
            
            return view('admin/report_store_date_sales',$response_data);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_ORDER',__FUNCTION__,__FILE__);
            return view('admin/report_store_date_sales',array('error_message'=>$e->getMessage(). ''.$e->getLine() ,'sales_report'=>array()));
        }
    }
    
    function storeDiscountTypesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
           
            $products_list = \DB::table('store as s')
            ->join('pos_customer_orders_detail as pcod','pcod.store_id', '=', 's.id')         
            ->where('pcod.is_deleted',0)
            ->where('pcod.fake_inventory',0)     
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")        
            ->groupByRaw('s.id,abs(pcod.discount_percent)')        
            ->selectRaw('s.id as store_id,s.store_name,s.store_id_code,abs(pcod.discount_percent) as discount_percent,SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price,SUM(pcod.sale_price) as prod_sale_price,SUM(pcod.discount_amount_actual) as prod_discount_amount,SUM(pcod.gst_amount) as prod_gst_amount')     
            ->orderByRaw('s.store_name ASC,prod_count DESC')        
            ->get()->toArray();
            
            $products_list = json_decode(json_encode($products_list),true);
            $products = $products_list;
            
            $products_list_all = \DB::table('pos_customer_orders_detail as pcod')
            ->where('pcod.is_deleted',0)
            ->where('pcod.fake_inventory',0)            
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")      
            ->groupByRaw('abs(pcod.discount_percent)')        
            ->selectRaw('abs(pcod.discount_percent) as discount_percent,SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price,SUM(pcod.sale_price) as prod_sale_price,SUM(pcod.discount_amount_actual) as prod_discount_amount,SUM(pcod.gst_amount) as prod_gst_amount')     
            ->orderByRaw('prod_count DESC')        
            ->get()->toArray();
            
            $products_list_all = json_decode(json_encode($products_list_all),true);
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=report_store_discount_type.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Store Name','Store Code','Discount Percent','Inventory Count','Sale Price','Discount Amount','GST Amount','Net Price');
                
                $callback = function() use ($products,$products_list_all, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total = array('units'=>0,'sale_price'=>0,'discount_amount'=>0,'gst_amount'=>0,'net_price'=>0); 
                    $total_store = array('units'=>0,'sale_price'=>0,'discount_amount'=>0,'gst_amount'=>0,'net_price'=>0);
                    
                    for($i=0;$i<count($products_list_all);$i++){
                        $array = array('All Stores','',$products_list_all[$i]['discount_percent'],$products_list_all[$i]['prod_count'],round($products_list_all[$i]['prod_sale_price'],2),
                        round($products_list_all[$i]['prod_discount_amount'],2),round($products_list_all[$i]['prod_gst_amount'],2),round($products_list_all[$i]['prod_net_price'],2));
                        fputcsv($file, $array);
                        
                        $total['units']+=$products_list_all[$i]['prod_count'];
                        $total['sale_price']+=$products_list_all[$i]['prod_sale_price'];
                        $total['discount_amount']+=$products_list_all[$i]['prod_discount_amount'];
                        $total['gst_amount']+=$products_list_all[$i]['prod_gst_amount'];
                        $total['net_price']+=$products_list_all[$i]['prod_net_price'];
                    }
                    
                    $array = array('All Stores Total','','',$total['units'],round($total['sale_price'],2),round($total['discount_amount'],2),round($total['gst_amount'],2),round($total['net_price'],2));
                    fputcsv($file, $array);
                    
                    $total = array('units'=>0,'sale_price'=>0,'discount_amount'=>0,'gst_amount'=>0,'net_price'=>0);
                    
                    for($i=0;$i<count($products);$i++){
                        $array = array($products[$i]['store_name'],$products[$i]['store_id_code'],$products[$i]['discount_percent'],$products[$i]['prod_count'],round($products[$i]['prod_sale_price'],2),
                        round($products[$i]['prod_discount_amount'],2),round($products[$i]['prod_gst_amount'],2),round($products[$i]['prod_net_price'],2));
                        fputcsv($file, $array);
                        
                        $total['units']+=$products[$i]['prod_count'];
                        $total['sale_price']+=$products[$i]['prod_sale_price'];
                        $total['discount_amount']+=$products[$i]['prod_discount_amount'];
                        $total['gst_amount']+=$products[$i]['prod_gst_amount'];
                        $total['net_price']+=$products[$i]['prod_net_price'];

                        $total_store['units']+=$products[$i]['prod_count'];
                        $total_store['sale_price']+=$products[$i]['prod_sale_price'];
                        $total_store['discount_amount']+=$products[$i]['prod_discount_amount'];
                        $total_store['gst_amount']+=$products[$i]['prod_gst_amount'];
                        $total_store['net_price']+=$products[$i]['prod_net_price'];
                        
                        if((isset($products[$i+1]['store_id']) && $products[$i]['store_id'] != $products[$i+1]['store_id']) || ($i+1 == count($products)) ){
                            $array = array($products[$i]['store_name'].' Total','',$total_store['units'],round($total_store['sale_price'],2),round($total_store['discount_amount'],2),
                            round($total_store['gst_amount'],2),round($total_store['net_price'],2));
                            
                            fputcsv($file, $array);
                            $total_store = array('units'=>0,'sale_price'=>0,'discount_amount'=>0,'gst_amount'=>0,'net_price'=>0);
                        }
                    }
                    
                    $array = array('Total','','',$total['units'],round($total['sale_price'],2),round($total['discount_amount'],2),round($total['gst_amount'],2),round($total['net_price'],2));
                    fputcsv($file, $array);
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('admin/report_store_discount_type',array('products_list'=>$products,'error_message'=>$error_message,'products_list_all'=>$products_list_all));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('admin/report_store_discount_type',array('error_message'=>$e->getMessage()));
        }
    }
    
    function categorySalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $category_balance_stores = $category_sale_list = $category_balance_stores = $category_list = $category_listing = $store_data = array();
            
            if($user->user_type == 9){
                $store_data = CommonHelper::getUserStoreData($user->id);
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            
            $inv_type = (isset($data['inv_type']) && !empty($data['inv_type']))?trim($data['inv_type']):'both';
            if($inv_type == 'both') $inv_ids = [0,1];elseif($inv_type == 'arnon') $inv_ids = [1];elseif($inv_type == 'north') $inv_ids = [0];
           
            if($user->user_type != 9){
                // Category List for stores sales
                $category_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')         
                ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')        
                ->join('store as s','pcod.store_id', '=', 's.id')          
                ->where('ppm.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->where('pcod.fake_inventory',0)    
                ->where('ppm.fake_inventory',0)            
                ->where('pcod.order_status',1)        
                //->where('dlim_1.is_deleted',0)        
                ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")        
                ->groupByRaw('pcod.store_id,ppm.category_id')        
                ->selectRaw('dlim_1.id as category_id,s.id as store_id,s.store_name,dlim_1.name as category_name,SUM(pcod.product_quantity) as cat_count,SUM(pcod.net_price) as cat_net_price')     
                ->orderByRaw('store_name ASC, cat_net_price DESC')        
                ->get()->toArray();

                for($i=0;$i<count($category_list);$i++){
                    $key = $category_list[$i]->category_id.'_'.$category_list[$i]->store_id;
                    $category_sale_list[$key]['qty'] = $category_list[$i]->cat_count;
                    $category_sale_list[$key]['price'] = $category_list[$i]->cat_net_price;
                }

                //Total Units available in stores without dates. It is calculated without dates
                $category_balance_in_stores = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
                ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')
                ->join('store as s','ppmi.store_id', '=', 's.id')                  
                ->where('ppmi.product_status',4)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppmi.fake_inventory',0)    
                ->where('ppm.fake_inventory',0)            
                //->where('dlim_1.is_deleted',0)
                ->groupByRaw('dlim_1.id,ppmi.store_id')        
                ->select('dlim_1.id as category_id','dlim_1.name as category_name','s.id as store_id','s.store_name','ppmi.store_id',\DB::raw('COUNT(ppmi.id) as inv_count'))
                ->orderBy('inv_count','DESC')->get()->toArray();

                for($i=0;$i<count($category_balance_in_stores);$i++){
                    $key = $category_balance_in_stores[$i]->category_id.'_'.$category_balance_in_stores[$i]->store_id;
                    $category_balance_stores[$key] = $category_balance_in_stores[$i]->inv_count;
                }

                $categories = Design_lookup_items_master::where('type','POS_PRODUCT_CATEGORY')->get()->toArray();
                $store_list = CommonHelper::getStoresList();
                /*for($i=0;$i<count($categories);$i++){
                    $key = $categories[$i]['category_id'].'_'.$categories[$i]['store_id'];
                    $category_list[$i]->balance_inv = (isset($category_balance_stores[$key]))?$category_balance_stores[$key]:0;
                }*/

                // Merge balance data into category list array
                for($i=0;$i<count($store_list);$i++){
                    for($q=0;$q<count($categories);$q++){
                        $key = $categories[$q]['id'].'_'.$store_list[$i]['id'];
                        if(isset($category_sale_list[$key])){
                            $category_listing[$key] = array('store_name'=>$store_list[$i]['store_name'],'store_id_code'=>$store_list[$i]['store_id_code'],'category_name'=>$categories[$q]['name'],'sale_qty'=>$category_sale_list[$key]['qty'],'sale_price'=>$category_sale_list[$key]['price']);
                        }

                        if(isset($category_balance_stores[$key])){
                            if(isset($category_listing[$key])){
                                $category_listing[$key]['bal_qty'] = $category_balance_stores[$key];
                            }else{
                                $category_listing[$key] = array('store_name'=>$store_list[$i]['store_name'],'store_id_code'=>$store_list[$i]['store_id_code'],'category_name'=>$categories[$q]['name'],'bal_qty'=>$category_balance_stores[$key]);
                            }
                        }
                    }
                }

                if(isset($data['action']) && $data['action'] == 'download_csv_cat_store'){
                    $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=category_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                    $columns = array('Store Name','Store Code','Category','Sold Units','Balance Units','Total NET Price');

                    $callback = function() use ($category_listing, $columns){
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);
                        $total_sold_units = $total_balance_units = $total_price = 0;
                        foreach($category_listing as $key=>$category_data){
                            $sale_qty = isset($category_data['sale_qty'])?$category_data['sale_qty']:0;
                            $bal_qty = isset($category_data['bal_qty'])?$category_data['bal_qty']:0;
                            $sale_price = isset($category_data['sale_price'])?round($category_data['sale_price'],2):0;

                            $array = array($category_data['store_name'],$category_data['store_id_code'],$category_data['category_name'],$sale_qty,$bal_qty,$sale_price);
                            $total_sold_units+=$sale_qty;
                            $total_balance_units+=$bal_qty;
                            $total_price+=$sale_price;
                            fputcsv($file, $array);
                        }

                        $array = array('Total','','',$total_sold_units,$total_balance_units,$total_price);
                        fputcsv($file, $array);

                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }
            }
            
            $demand_statuses = CommonHelper::getDispatchedPushDemandStatusList();
            
            //$inv_id_1 = 0;
            //$inv_id_2 = 43000000;
            //Units pushed to store in dates
            //\DB::enableQueryLog();
            $category_list_demand_pushed = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
            ->where('spd.demand_type','inventory_push')        
            ->wherein('spd.demand_status',['store_loading','store_loaded'])        
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")         
            ->wherein('ppmi.arnon_inventory',$inv_ids)        
            ->where('spdi.receive_status',1)                        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('spd.is_deleted',0)
            ->where('spdi.fake_inventory',0)    
            ->where('ppmi.fake_inventory',0)    
            ->where('ppm.fake_inventory',0)    
            ->where('spd.fake_inventory',0)            
            //->whereRaw('ppmi.id NOT IN(2858,3929,3718,3853,3880,4061)')        
            //->whereRaw('ppmi.id >= '.$inv_id_1.' and ppmi.id <= '.$inv_id_2)     
            ->where('spdi.demand_status',1)        
            ->where('spdi.is_deleted',0);        
            
            if($user->user_type == 9){
                $category_list_demand_pushed = $category_list_demand_pushed->where('spd.store_id',$store_data->id);
            }        
            
            $category_list_demand_pushed = $category_list_demand_pushed->groupBy('dlim_1.id')        
            ->select('dlim_1.id as category_id','dlim_1.name as category_name',\DB::raw('COUNT(ppmi.id) as inv_count_pushed'))
            ->orderBy('inv_count_pushed','DESC');        
            
            $category_list_demand_pushed_obj = clone ($category_list_demand_pushed);
            
            $category_list_demand_pushed = $category_list_demand_pushed->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'")
            ->get()->toArray();
            
            //Total Units pushed to store without dates
            $category_list_demand_pushed_total = $category_list_demand_pushed_obj->get()->toArray();
            
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            
            //Total Units available in stores without dates.  It is calculated without dates
            $category_list_in_stores = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')
            ->wherein('ppmi.product_status',[4])        
            ->wherein('ppmi.arnon_inventory',$inv_ids)        
            ->where('ppmi.is_deleted',0)
            //->whereRaw('ppmi.id NOT IN(2858,3929,3718,3853,3880,4061)')            
            //->whereRaw('ppmi.id >= '.$inv_id_1.' and ppmi.id <= '.$inv_id_2)
            ->where('ppmi.fake_inventory',0)    
            ->where('ppm.fake_inventory',0)            
            ->where('ppmi.status',1);
            
            if($user->user_type == 9){
                $category_list_in_stores = $category_list_in_stores->where('ppmi.store_id',$store_data->id);
            }        
            
            $category_list_in_stores = $category_list_in_stores->groupBy('dlim_1.id')        
            ->select('dlim_1.id as category_id','dlim_1.name as category_name',\DB::raw('COUNT(ppmi.id) as inv_count'),\DB::raw('SUM(ppmi.sale_price) as bal_inv_mrp'))
            ->orderBy('inv_count','DESC')->get()->toArray();
            
            //Units Returned from store to warehouse
            $category_list_demand_returned_to_wh = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
            ->where('spd.demand_type','inventory_return_to_warehouse')        
            ->wherein('spd.demand_status',array('warehouse_dispatched','warehouse_loading','warehouse_loaded'))        
            ->wherein('ppmi.arnon_inventory',$inv_ids)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('spd.is_deleted',0)
            //->whereRaw('ppmi.id NOT IN(2858,3929,3718,3853,3880,4061)')            
            //->whereRaw('ppmi.id >= '.$inv_id_1.' and ppmi.id <= '.$inv_id_2)
            ->where('spdi.fake_inventory',0)    
            ->where('ppmi.fake_inventory',0)    
            ->where('ppm.fake_inventory',0)    
            ->where('spd.fake_inventory',0)        
            ->where('spdi.demand_status',1)        
            ->where('spdi.is_deleted',0);
            if($user->user_type == 9){
                $category_list_demand_returned_to_wh = $category_list_demand_returned_to_wh->where('spd.store_id',$store_data->id);
            }          
            $category_list_demand_returned_to_wh = $category_list_demand_returned_to_wh->groupBy('dlim_1.id')        
            ->select('dlim_1.id as category_id','dlim_1.name as category_name',\DB::raw('COUNT(spdi.id) as inv_count'))        
            ->get()->toArray();  
            
            //inventory return complete from store but not returned to store
            $category_list_demand_return_complete = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
            ->where('spd.demand_type','inventory_return_complete')        
            //->wherein('spd.demand_status',array('store_loaded'))        
            ->wherein('ppmi.arnon_inventory',$inv_ids)        
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")         
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('spd.is_deleted',0)
            //->whereRaw('ppmi.id NOT IN(2858,3929,3718,3853,3880,4061)')            
            //->whereRaw('ppmi.id >= '.$inv_id_1.' and ppmi.id <= '.$inv_id_2)
            ->where('spdi.fake_inventory',0)    
            ->where('ppmi.fake_inventory',0)    
            ->where('ppm.fake_inventory',0)    
            ->where('spd.fake_inventory',0)          
            ->where('spdi.demand_status',1)        
            ->where('spdi.is_deleted',0);
            
            if($user->user_type == 9){
                $category_list_demand_return_complete = $category_list_demand_return_complete->where('spd.store_id',$store_data->id);
            }          
            
            $category_list_demand_return_complete = $category_list_demand_return_complete->groupBy('dlim_1.id')        
            ->select('dlim_1.id as category_id','dlim_1.name as category_name',\DB::raw('COUNT(spdi.id) as inv_count'))        
            ->get()->toArray();  
            
            
            //print_r($category_list_demand_return_complete);exit;
            //Units Transferred from store to other stores
            $category_list_demand_transfer_from_store = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
            ->where('spd.demand_type','inventory_transfer_to_store')        
            ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))        
            ->wherein('ppmi.arnon_inventory',$inv_ids)        
            ->where('ppmi.product_status',3)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('spd.is_deleted',0)
            //->whereRaw('ppmi.id NOT IN(2858,3929,3718,3853,3880,4061)')            
            //->whereRaw('ppmi.id >= '.$inv_id_1.' and ppmi.id <= '.$inv_id_2)  
            ->where('spdi.fake_inventory',0)    
            ->where('ppmi.fake_inventory',0)    
            ->where('ppm.fake_inventory',0)    
            ->where('spd.fake_inventory',0)        
            ->where('spdi.demand_status',1)        
            ->where('spdi.is_deleted',0);
            
            if($user->user_type == 9){
                $category_list_demand_transfer_from_store = $category_list_demand_transfer_from_store->where('spd.from_store_id',$store_data->id);
            }          
            
            $category_list_demand_transfer_from_store = $category_list_demand_transfer_from_store->groupBy('dlim_1.id')        
            ->select('dlim_1.id as category_id','dlim_1.name as category_name',\DB::raw('COUNT(spdi.id) as inv_count'))        
            ->get()->toArray();
            
            //print_r($category_list_demand_transfer_from_store);
            
            //Inventory sold from store in dates
            $category_sold_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')        
            ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')   
            ->wherein('ppmi.arnon_inventory',$inv_ids)    
            //->whereRaw('ppmi.id NOT IN(2858,3929,3718,3853,3880,4061)')            
            //->whereRaw('ppmi.id >= '.$inv_id_1.' and ppmi.id <= '.$inv_id_2)
            ->where('ppm.is_deleted',0)
            ->where('pcod.fake_inventory',0)    
            ->where('ppmi.fake_inventory',0)    
            ->where('ppm.fake_inventory',0)    
            ->where('pcod.order_status',1)        
            ->where('pcod.is_deleted',0);
            
            if($user->user_type == 9){
                $category_sold_list = $category_sold_list->where('pcod.store_id',$store_data->id);
            }            
            
            $category_sold_list = $category_sold_list->groupBy('ppm.category_id')        
            ->selectRaw('dlim_1.id as category_id,dlim_1.name as category_name,SUM(pcod.product_quantity) as inv_count,SUM(pcod.net_price) as inv_net_price')     
            ->orderByRaw('inv_count DESC');        
            
            $category_sold_list_obj = clone ($category_sold_list);
            
            $category_sold_list = $category_sold_list->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")->get()->toArray();     
            
            $category_sold_list_total = $category_sold_list_obj->get()->toArray();
            
            $category_list_demand_pushed = json_decode(json_encode($category_list_demand_pushed),true);
            $category_list_demand_pushed_total = json_decode(json_encode($category_list_demand_pushed_total),true);
            $category_list_in_stores = json_decode(json_encode($category_list_in_stores),true);
            $category_sold_list = json_decode(json_encode($category_sold_list),true);
            $category_sold_list_total = json_decode(json_encode($category_sold_list_total),true);
            $category_list_demand_returned_to_wh = json_decode(json_encode($category_list_demand_returned_to_wh),true);
            $category_list_demand_transfer_from_store = json_decode(json_encode($category_list_demand_transfer_from_store),true);
            $category_list_demand_return_complete = json_decode(json_encode($category_list_demand_return_complete),true);
            
            $category_type_list = Design_lookup_items_master::where('type','POS_PRODUCT_CATEGORY')->get()->toArray();
            
            for($i=0;$i<count($category_type_list);$i++){
                $category_id = $category_type_list[$i]['id'];
                $index = array_search($category_id,array_column($category_list_demand_pushed,'category_id'));
                $category_type_list[$i]['inv_pushed'] = ($index !== false)?$category_list_demand_pushed[$index]['inv_count_pushed']:0;
                
                $index = array_search($category_id,array_column($category_list_demand_pushed_total,'category_id'));
                $category_type_list[$i]['inv_pushed_total'] = ($index !== false)?$category_list_demand_pushed_total[$index]['inv_count_pushed']:0;
                
                $index = array_search($category_id,array_column($category_list_in_stores,'category_id'));
                $category_type_list[$i]['inv_balance'] = ($index !== false)?$category_list_in_stores[$index]['inv_count']:0;
                $category_type_list[$i]['bal_inv_mrp'] = ($index !== false)?$category_list_in_stores[$index]['bal_inv_mrp']:0;
                
                $index = array_search($category_id,array_column($category_sold_list,'category_id'));
                $category_type_list[$i]['inv_sold'] = ($index !== false)?$category_sold_list[$index]['inv_count']:0;
                $category_type_list[$i]['inv_sold_price'] = ($index !== false)?$category_sold_list[$index]['inv_net_price']:0;
                
                $index = array_search($category_id,array_column($category_sold_list_total,'category_id'));
                $category_type_list[$i]['inv_sold_total'] = ($index !== false)?$category_sold_list_total[$index]['inv_count']:0;
                $category_type_list[$i]['inv_sold_price_total'] = ($index !== false)?$category_sold_list_total[$index]['inv_net_price']:0;
                
                $index = array_search($category_id,array_column($category_list_demand_returned_to_wh,'category_id'));
                $category_type_list[$i]['inv_returned'] = ($index !== false)?$category_list_demand_returned_to_wh[$index]['inv_count']:0;
                
                $index = array_search($category_id,array_column($category_list_demand_transfer_from_store,'category_id'));
                $category_type_list[$i]['inv_transfer_from_store'] = ($index !== false)?$category_list_demand_transfer_from_store[$index]['inv_count']:0;
                
                $index = array_search($category_id,array_column($category_list_demand_return_complete,'category_id'));
                $category_type_list[$i]['inv_returned_complete'] = ($index !== false)?$category_list_demand_return_complete[$index]['inv_count']:0;
            }
            
            $totals = array_column($category_type_list,'inv_pushed_total');
            array_multisort($totals, SORT_DESC, $category_type_list);
            
            if(isset($data['action']) && $data['action'] == 'download_csv_cat'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=category_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns_0 = array('','Total','Date: '.date('d M Y', strtotime($start_date)).' - '.date('d M Y', strtotime($end_date)));
                $columns = array('Category','Total Pushed to Store Units','Pushed to Store Units','Sold Units','Balance Units','Balance Value','Sell Through','Total Net Price');

                $callback = function() use ($category_type_list, $columns_0,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns_0);
                    fputcsv($file, $columns);
                    $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_balance'=>0,'inv_sold_price'=>0,'bal_inv_mrp'=>0);

                    for($i=0;$i<count($category_type_list);$i++){
                        $sell_through = ($category_type_list[$i]['inv_balance']+$category_type_list[$i]['inv_sold'] > 0)?round(($category_type_list[$i]['inv_sold']/($category_type_list[$i]['inv_balance']+$category_type_list[$i]['inv_sold']))*100,3).'%':0;
                        $array = array($category_type_list[$i]['name'],$category_type_list[$i]['inv_pushed_total'],$category_type_list[$i]['inv_pushed'],$category_type_list[$i]['inv_sold'],$category_type_list[$i]['inv_balance'],$category_type_list[$i]['bal_inv_mrp'],$sell_through,$category_type_list[$i]['inv_sold_price']);

                        fputcsv($file, $array);

                        $total_data['inv_pushed_total']+=$category_type_list[$i]['inv_pushed_total']; 
                        $total_data['inv_pushed']+=$category_type_list[$i]['inv_pushed']; 
                        $total_data['inv_sold']+=$category_type_list[$i]['inv_sold']; 
                        $total_data['inv_balance']+=$category_type_list[$i]['inv_balance']; 
                        $total_data['inv_sold_price']+=$category_type_list[$i]['inv_sold_price'];
                        $total_data['bal_inv_mrp']+=$category_type_list[$i]['bal_inv_mrp'];
                    }

                    $array = array('Total',$total_data['inv_pushed_total'],$total_data['inv_pushed'],$total_data['inv_sold'],$total_data['inv_balance'],$total_data['bal_inv_mrp'],'',$total_data['inv_sold_price']);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('admin/report_category_sales',array('category_list'=>$category_list,'error_message'=>$error_message,'category_type_list'=>$category_type_list,'category_listing'=>$category_listing,'user'=>$user,'store_data'=>$store_data,'start_date'=>$start_date,'end_date'=>$end_date));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY_SALES_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_category_sales',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function subcategorySalesReport(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $category_id = $id;
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
           
            $subcategory_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.subcategory_id', '=', 'dlim_1.id')        
            ->join('store as s','pcod.store_id', '=', 's.id')        
            ->where('ppm.category_id',$category_id)        
            ->where('ppm.is_deleted',0)
            ->where('pcod.is_deleted',0)
            ->where('dlim_1.is_deleted',0)   
            ->where('s.is_deleted',0)        
            ->where('pcod.fake_inventory',0) 
            ->where('ppm.fake_inventory',0)         
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")       
            ->groupByRaw('pcod.store_id,ppm.subcategory_id')        
            ->selectRaw('s.store_name,s.store_id_code,dlim_1.name as subcategory_name,SUM(pcod.product_quantity) as subcat_count,SUM(pcod.net_price) as subcat_net_price')     
            ->orderByRaw('store_name ASC, subcat_net_price DESC')        
            ->get()->toArray();
            
            if(isset($data['action']) && $data['action'] == 'download_csv_subcat_store'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=subcategory_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Store Name','Store Code','Subcategory','Sold Units','Total NET Price');

                $callback = function() use ($subcategory_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_units = $total_price = 0;
                    for($i=0;$i<count($subcategory_list);$i++){
                        $array = array($subcategory_list[$i]->store_name,$subcategory_list[$i]->store_id_code,$subcategory_list[$i]->subcategory_name,$subcategory_list[$i]->subcat_count,$subcategory_list[$i]->subcat_net_price);
                        $total_units+=$subcategory_list[$i]->subcat_count;
                        $total_price+=$subcategory_list[$i]->subcat_net_price;
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','',$total_units,$total_price);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            //Units pushed to store in dates
            $demand_statuses = CommonHelper::getDispatchedPushDemandStatusList();
            //\DB::enableQueryLog();
            
            $subcategory_list_demand_pushed = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.subcategory_id', '=', 'dlim_1.id')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
            ->where('ppm.category_id',$category_id)        
            ->where('spd.demand_type','inventory_push')        
            ->wherein('spd.demand_status',$demand_statuses)        
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")            
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('spd.is_deleted',0)
            ->where('spdi.is_deleted',0)      
            ->where('spdi.demand_status',1)        
            ->where('spdi.fake_inventory',0) 
            ->where('ppmi.fake_inventory',0) 
            ->where('ppm.fake_inventory',0) 
            ->where('spd.fake_inventory',0)         
            ->groupByRaw('dlim_1.id')        
            ->select('dlim_1.id as subcategory_id','dlim_1.name as subcategory_name',\DB::raw('COUNT(ppmi.id) as inv_count_pushed'))
            ->orderBy('inv_count_pushed','DESC');        
            
            $subcategory_list_demand_pushed_obj = clone ($subcategory_list_demand_pushed);
            $subcategory_list_demand_pushed = $subcategory_list_demand_pushed->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'")->get()->toArray();
            
            //Total Units pushed to store without dates
            $subcategory_list_demand_pushed_total = $subcategory_list_demand_pushed_obj->get()->toArray();
            
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            
            //Total Units available in stores without dates
            $subcategory_list_in_stores = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.subcategory_id', '=', 'dlim_1.id')
            ->where('ppm.category_id',$category_id)        
            ->where('ppmi.product_status',4)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('ppmi.fake_inventory',0) 
            ->where('ppm.fake_inventory',0)         
            ->groupByRaw('dlim_1.id')        
            ->select('dlim_1.id as subcategory_id','dlim_1.name as subcategory_name',\DB::raw('COUNT(ppmi.id) as inv_count'))
            ->orderBy('inv_count','DESC')->get()->toArray();
            
            //Inventory sold from store in dates
            $subcategory_sold_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.subcategory_id', '=', 'dlim_1.id')      
            ->where('ppm.category_id',$category_id)        
            ->where('ppm.is_deleted',0)
            ->where('pcod.is_deleted',0)
            ->where('pcod.fake_inventory',0) 
            ->where('ppm.fake_inventory',0)         
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")        
            ->groupBy('ppm.category_id')        
            ->selectRaw('dlim_1.id as subcategory_id,dlim_1.name as subcategory_name,SUM(pcod.product_quantity) as inv_count,SUM(pcod.net_price) as inv_net_price')     
            ->orderByRaw('inv_count DESC')        
            ->get()->toArray();
            
            $subcategory_list_demand_pushed = json_decode(json_encode($subcategory_list_demand_pushed),true);
            $subcategory_list_demand_pushed_total = json_decode(json_encode($subcategory_list_demand_pushed_total),true);
            $subcategory_list_in_stores = json_decode(json_encode($subcategory_list_in_stores),true);
            $subcategory_sold_list = json_decode(json_encode($subcategory_sold_list),true);
            
            $subcategory_type_list = Design_lookup_items_master::where('type','POS_PRODUCT_SUBCATEGORY')->where('pid',$category_id)->get()->toArray();
            
            for($i=0;$i<count($subcategory_type_list);$i++){
                $subcategory_id = $subcategory_type_list[$i]['id'];
                $index = array_search($subcategory_id,array_column($subcategory_list_demand_pushed,'subcategory_id'));
                $subcategory_type_list[$i]['inv_pushed'] = ($index !== false)?$subcategory_list_demand_pushed[$index]['inv_count_pushed']:0;
                
                $index = array_search($subcategory_id,array_column($subcategory_list_demand_pushed_total,'subcategory_id'));
                $subcategory_type_list[$i]['inv_pushed_total'] = ($index !== false)?$subcategory_list_demand_pushed_total[$index]['inv_count_pushed']:0;
                
                $index = array_search($subcategory_id,array_column($subcategory_list_in_stores,'subcategory_id'));
                $subcategory_type_list[$i]['inv_balance'] = ($index !== false)?$subcategory_list_in_stores[$index]['inv_count']:0;
                
                $index = array_search($subcategory_id,array_column($subcategory_sold_list,'subcategory_id'));
                $subcategory_type_list[$i]['inv_sold'] = ($index !== false)?$subcategory_sold_list[$index]['inv_count']:0;
                $subcategory_type_list[$i]['inv_sold_price'] = ($index !== false)?$subcategory_sold_list[$index]['inv_net_price']:0;
            }
            
            $totals = array_column($subcategory_type_list,'inv_pushed_total');
            array_multisort($totals, SORT_DESC, $subcategory_type_list);
            
            if(isset($data['action']) && $data['action'] == 'download_csv_subcat'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=subcategory_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Subcategory','Total Pushed to Store Units','Pushed to Store Units','Sold Units','Balance Units','Sell Through','Total Net Price');

                $callback = function() use ($subcategory_type_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_balance'=>0,'inv_sold_price'=>0);

                    for($i=0;$i<count($subcategory_type_list);$i++){
                        $sell_through = ($subcategory_type_list[$i]['inv_balance']+$subcategory_type_list[$i]['inv_sold'] > 0)?round(($subcategory_type_list[$i]['inv_sold']/($subcategory_type_list[$i]['inv_balance']+$subcategory_type_list[$i]['inv_sold']))*100,3).'%':0;
                        $array = array($subcategory_type_list[$i]['name'],$subcategory_type_list[$i]['inv_pushed_total'],$subcategory_type_list[$i]['inv_pushed'],$subcategory_type_list[$i]['inv_sold'],$subcategory_type_list[$i]['inv_balance'],$sell_through,$subcategory_type_list[$i]['inv_sold_price']);

                        fputcsv($file, $array);

                        $total_data['inv_pushed_total']+=$subcategory_type_list[$i]['inv_pushed_total']; 
                        $total_data['inv_pushed']+=$subcategory_type_list[$i]['inv_pushed']; 
                        $total_data['inv_sold']+=$subcategory_type_list[$i]['inv_sold']; 
                        $total_data['inv_balance']+=$subcategory_type_list[$i]['inv_balance']; 
                        $total_data['inv_sold_price']+=$subcategory_type_list[$i]['inv_sold_price'];
                    }

                    $array = array('Total',$total_data['inv_pushed_total'],$total_data['inv_pushed'],$total_data['inv_sold'],$total_data['inv_balance'],'',$total_data['inv_sold_price']);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $category_data = Design_lookup_items_master::where('id',$category_id)->first();
            
            return view('admin/report_subcategory_sales',array('subcategory_list'=>$subcategory_list,'error_message'=>$error_message,'subcategory_type_list'=>$subcategory_type_list,'category_data'=>$category_data));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY_SALES_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_subcategory_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function sizeSalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
           
            // Size sales data of individual stores
            $size_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')
            ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')
            ->join('store as s','pcod.store_id', '=', 's.id')
            ->where('pcod.is_deleted',0)
            ->where('ppm.is_deleted',0)
            ->where('pcod.fake_inventory',0) 
            ->where('ppm.fake_inventory',0)         
            ->where('psc.is_deleted',0)        
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")        
            ->groupByRaw('pcod.store_id,ppm.size_id')        
            ->selectRaw('s.store_name,s.store_id_code,pcod.store_id,psc.id as size_id,psc.size as size_name,SUM(pcod.product_quantity) as size_count,SUM(pcod.net_price) as size_net_price')     
            ->orderByRaw('store_name ASC,size_net_price DESC')        
            ->get()->toArray();
            
            //Total Units available in stores without dates. It is calculated without dates
            $size_balance_in_stores = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')
            ->where('ppmi.product_status',4)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0) 
            ->where('ppm.fake_inventory',0)         
            ->where('ppmi.status',1)
            //->where('dlim_1.is_deleted',0)
            ->groupByRaw('ppm.size_id,ppmi.store_id')        
            ->select('psc.id as size_id','psc.size as size_name','ppmi.store_id',\DB::raw('COUNT(ppmi.id) as inv_count'))
            ->orderBy('inv_count','DESC')->get()->toArray();
            
            for($i=0;$i<count($size_balance_in_stores);$i++){
                $key = $size_balance_in_stores[$i]->size_id.'_'.$size_balance_in_stores[$i]->store_id;
                $size_balance_stores[$key] = $size_balance_in_stores[$i]->inv_count;
            }
            
            // Merge balance data into category list array
            for($i=0;$i<count($size_list);$i++){
                $key = $size_list[$i]->size_id.'_'.$size_list[$i]->store_id;
                $size_list[$i]->balance_inv = (isset($size_balance_stores[$key]))?$size_balance_stores[$key]:0;
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv_size_store'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=size_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Store Name','Store Code','Size','Sold Units','Balance Units','Total NET Price');

                $callback = function() use ($size_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_units = $total_price = $bal_units = 0;
                    for($i=0;$i<count($size_list);$i++){
                        $array = array($size_list[$i]->store_name,$size_list[$i]->store_id_code,$size_list[$i]->size_name,$size_list[$i]->size_count,$size_list[$i]->balance_inv,$size_list[$i]->size_net_price);
                        $total_units+=$size_list[$i]->size_count;
                        $bal_units+=$size_list[$i]->balance_inv;
                        $total_price+=$size_list[$i]->size_net_price;
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','',$total_units,$bal_units,$total_price);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $demand_statuses = CommonHelper::getDispatchedPushDemandStatusList();
            
            //Units pushed to store in dates
            $size_list_demand_pushed = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')       
            //$size_list_demand_pushed = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
            ->where('spd.demand_type','inventory_push')        
            ->wherein('spd.demand_status',$demand_statuses)        
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")        
            ->where('ppmi.is_deleted',0)
            ->where('spdi.fake_inventory',0) 
            ->where('ppmi.fake_inventory',0) 
            ->where('ppm.fake_inventory',0) 
            ->where('spd.fake_inventory',0)         
            ->where('ppmi.status',1)
            //->where('dlim_1.is_deleted',0)
            ->where('spd.is_deleted',0)
            ->where('spdi.is_deleted',0)      
            ->where('spdi.demand_status',1)        
            ->groupByRaw('ppm.size_id')        
            ->select('psc.id as size_id','psc.size as size_name',\DB::raw('COUNT(ppmi.id) as inv_count_pushed'))
            ->orderBy('inv_count_pushed','DESC');        
            
            $size_list_demand_pushed_obj = clone ($size_list_demand_pushed);
            
            $size_list_demand_pushed = $size_list_demand_pushed->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'")
            ->get()->toArray();
            
            //Total Units pushed to store without dates
            $size_list_demand_pushed_total = $size_list_demand_pushed_obj->get()->toArray();
            
            //Total Units available in stores without dates.  It is calculated without dates
            $size_list_in_stores = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')
            ->where('ppmi.product_status',4)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0) 
            ->where('ppm.fake_inventory',0) 
            ->where('ppmi.status',1)
            //->where('dlim_1.is_deleted',0)
            ->groupByRaw('ppm.size_id')        
            ->select('psc.id as size_id','psc.size as size_name',\DB::raw('COUNT(ppmi.id) as inv_count'))
            ->orderBy('inv_count','DESC')->get()->toArray();;  
            
            //Inventory sold from store in dates
            $size_sold_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')         
            ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
            ->where('ppm.is_deleted',0)
            ->where('pcod.fake_inventory',0) 
            ->where('ppm.fake_inventory',0) 
            ->where('pcod.is_deleted',0)
            ->where('pcod.order_status',1)        
            //->where('dlim_1.is_deleted',0)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")      
            ->groupBy('ppm.size_id')        
            ->selectRaw('psc.id as size_id,psc.size as size_name,SUM(pcod.product_quantity) as inv_count,SUM(pcod.net_price) as inv_net_price')     
            ->orderByRaw('inv_count DESC')        
            ->get()->toArray();
            
            $size_list_demand_pushed = json_decode(json_encode($size_list_demand_pushed),true);
            $size_list_demand_pushed_total = json_decode(json_encode($size_list_demand_pushed_total),true);
            $size_list_in_stores = json_decode(json_encode($size_list_in_stores),true);
            $size_sold_list = json_decode(json_encode($size_sold_list),true);
            
            $size_type_list = Production_size_counts::where('is_deleted','0')->get()->toArray();
            
            for($i=0;$i<count($size_type_list);$i++){
                $size_id = $size_type_list[$i]['id'];
                $index = array_search($size_id,array_column($size_list_demand_pushed,'size_id'));
                $size_type_list[$i]['inv_pushed'] = ($index !== false)?$size_list_demand_pushed[$index]['inv_count_pushed']:0;
                
                $index = array_search($size_id,array_column($size_list_demand_pushed_total,'size_id'));
                $size_type_list[$i]['inv_pushed_total'] = ($index !== false)?$size_list_demand_pushed_total[$index]['inv_count_pushed']:0;
                
                $index = array_search($size_id,array_column($size_list_in_stores,'size_id'));
                $size_type_list[$i]['inv_balance'] = ($index !== false)?$size_list_in_stores[$index]['inv_count']:0;
                
                $index = array_search($size_id,array_column($size_sold_list,'size_id'));
                $size_type_list[$i]['inv_sold'] = ($index !== false)?$size_sold_list[$index]['inv_count']:0;
                $size_type_list[$i]['inv_sold_price'] = ($index !== false)?$size_sold_list[$index]['inv_net_price']:0;
            }
            
            $totals = array_column($size_type_list,'inv_pushed_total');
            array_multisort($totals, SORT_DESC, $size_type_list);
            
            if(isset($data['action']) && $data['action'] == 'download_csv_size'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=size_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Size','Total Pushed to Store Units','Pushed to Store Units','Sold Units','Balance Units','Sell Through','Total Net Price');

                $callback = function() use ($size_type_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_balance'=>0,'inv_sold_price'=>0);

                    for($i=0;$i<count($size_type_list);$i++){
                        $sell_through = ($size_type_list[$i]['inv_balance']+$size_type_list[$i]['inv_sold'] > 0)?round(($size_type_list[$i]['inv_sold']/($size_type_list[$i]['inv_balance']+$size_type_list[$i]['inv_sold']))*100,3).'%':0;
                        $array = array($size_type_list[$i]['size'],$size_type_list[$i]['inv_pushed_total'],$size_type_list[$i]['inv_pushed'],$size_type_list[$i]['inv_sold'],$size_type_list[$i]['inv_balance'],$sell_through,$size_type_list[$i]['inv_sold_price']);

                        fputcsv($file, $array);

                        $total_data['inv_pushed_total']+=$size_type_list[$i]['inv_pushed_total']; 
                        $total_data['inv_pushed']+=$size_type_list[$i]['inv_pushed']; 
                        $total_data['inv_sold']+=$size_type_list[$i]['inv_sold']; 
                        $total_data['inv_balance']+=$size_type_list[$i]['inv_balance']; 
                        $total_data['inv_sold_price']+=$size_type_list[$i]['inv_sold_price'];
                    }

                    $array = array('Total',$total_data['inv_pushed_total'],$total_data['inv_pushed'],$total_data['inv_sold'],$total_data['inv_balance'],'',$total_data['inv_sold_price']);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('admin/report_size_sales',array('size_list'=>$size_list,'error_message'=>$error_message,'size_type_list'=>$size_type_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'SIZE_SALES_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_size_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function storeSalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $po_category_sale_data = array();
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            $inv_type = (isset($data['inv_type']) && !empty($data['inv_type']))?trim($data['inv_type']):'both';
            if($inv_type == 'both') $inv_ids = [0,1];elseif($inv_type == 'arnon') $inv_ids = [1];elseif($inv_type == 'north') $inv_ids = [0];
           
            $store_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('store as s','pcod.store_id', '=', 's.id')         
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')        
            ->wherein('ppmi.arnon_inventory',$inv_ids)                
            ->where('pcod.is_deleted',0)
            ->where('s.is_deleted',0)        
            ->where('ppmi.is_deleted',0)        
            ->where('ppmi.fake_inventory',0) 
            ->where('pcod.fake_inventory',0)         
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")        
            ->groupByRaw('s.id')        
            ->selectRaw('s.id as store_id,s.store_name,s.store_id_code,SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price')     
            ->orderBy('prod_net_price','DESC')        
            ->get()->toArray();
            
            $po_category_sale_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')         
            ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')
            ->wherein('ppmi.arnon_inventory',$inv_ids)                        
            ->wherein('po.category_id',array(322,324))    
            ->where('pcod.is_deleted',0)
            ->where('ppmi.is_deleted',0)       
            ->where('ppmi.fake_inventory',0) 
            ->where('pcod.fake_inventory',0) 
            ->where('po.fake_inventory',0)         
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")    
            ->groupByRaw('pcod.store_id,po.category_id')        
            ->selectRaw('pcod.store_id,po.category_id,SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price')     
            ->get()->toArray();
            
            for($i=0;$i<count($po_category_sale_list);$i++){
                $key = $po_category_sale_list[$i]->store_id.'_'.$po_category_sale_list[$i]->category_id;
                $po_category_sale_data[$key] = $po_category_sale_list[$i];
            }
            
            //Units pushed to store in dates
            $demand_statuses = CommonHelper::getDispatchedPushDemandStatusList();
            
            $store_list_demand_pushed = \DB::table('store_products_demand_inventory as spdi')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')        
            ->join('store as s','spd.store_id', '=', 's.id')       
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')                
            ->where('spd.demand_type','inventory_push')              
            ->wherein('spd.demand_status',$demand_statuses)      
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")        
            ->where('spdi.is_deleted',0)
            ->where('spdi.demand_status',1)        
            ->where('ppmi.is_deleted',0)                
            ->where('spd.is_deleted',0)
            ->where('s.is_deleted',0)        
            ->wherein('ppmi.arnon_inventory',$inv_ids)                        
            ->where('spdi.fake_inventory',0)         
            ->where('spd.fake_inventory',0)         
            ->where('ppmi.fake_inventory',0)         
            ->groupByRaw('s.id')        
            ->select('s.id as store_id','s.store_name',\DB::raw('COUNT(spdi.id) as inv_count_pushed'));
            
            $store_list_demand_pushed_obj = clone ($store_list_demand_pushed);
            $store_list_demand_pushed = $store_list_demand_pushed->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'")->get()->toArray();
            
            //Total Units pushed to store without dates
            $store_list_demand_pushed_total = $store_list_demand_pushed_obj->get()->toArray();
            
            // Inventory return to warehouse start
            
            $store_list_demand_return = \DB::table('store_products_demand_inventory as spdi')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')        
            ->join('store as s','spd.store_id', '=', 's.id')      
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')                   
            ->where('spd.demand_type','inventory_return_to_warehouse')                      
            ->wherein('spd.demand_status',array('warehouse_dispatched','warehouse_loading','warehouse_loaded'))      
            ->where('spdi.is_deleted',0)
            ->where('spdi.demand_status',1)        
            ->where('ppmi.is_deleted',0)                
            ->wherein('ppmi.arnon_inventory',$inv_ids)                
            ->where('spd.is_deleted',0)
            ->where('s.is_deleted',0)    
            ->where('spdi.fake_inventory',0) 
            ->where('spd.fake_inventory',0) 
            ->where('ppmi.fake_inventory',0)         
            ->groupByRaw('s.id')        
            ->select('s.id as store_id','s.store_name',\DB::raw('COUNT(spdi.id) as inv_count_return'));
            
            $store_list_demand_return_obj = clone ($store_list_demand_return);
            $store_list_demand_return = $store_list_demand_return->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'")->get()->toArray();
            
            //Total Units pushed to store without dates
            $store_list_demand_return_total = $store_list_demand_return_obj->get()->toArray();
            
            // Inventory return to warehouse end
            
            $store_list_demand_transfer = \DB::table('store_products_demand_inventory as spdi')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')        
            ->join('store as s','spd.store_id', '=', 's.id')      
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')                   
            ->where('spd.demand_type','inventory_transfer_to_store')                      
            ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))      
            ->where('spdi.is_deleted',0)
            ->where('spdi.demand_status',1)        
            ->where('ppmi.is_deleted',0)                
            ->wherein('ppmi.arnon_inventory',$inv_ids)                
            ->where('spd.is_deleted',0)
            ->where('s.is_deleted',0)      
            ->where('spdi.fake_inventory',0) 
            ->where('spd.fake_inventory',0) 
            ->where('ppmi.fake_inventory',0)         
            ->groupByRaw('s.id')        
            ->select('s.id as store_id','s.store_name',\DB::raw('COUNT(spdi.id) as inv_count_transfer'));
                
            $store_list_demand_transfer = $store_list_demand_transfer->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'")->get()->toArray();
            
            $inv_list_in_stores = \DB::table('pos_product_master_inventory as ppmi')
            ->join('store as s','ppmi.store_id', '=', 's.id')        
            ->wherein('ppmi.product_status',array(3,4))        
            ->where('ppmi.is_deleted',0)
            ->wherein('ppmi.arnon_inventory',$inv_ids)                
            ->where('ppmi.status',1)
            ->where('ppmi.fake_inventory',0)         
            ->groupByRaw('s.id')        
            ->select('s.id as store_id','s.store_name',\DB::raw('COUNT(ppmi.id) as inv_count'))
            ->orderBy('inv_count','DESC')
            ->get()->toArray();
            
            $store_list_demand_pushed = json_decode(json_encode($store_list_demand_pushed),true);
            $store_list_demand_pushed_total = json_decode(json_encode($store_list_demand_pushed_total),true);
            $store_list_demand_return = json_decode(json_encode($store_list_demand_return),true);
            $store_list_demand_return_total = json_decode(json_encode($store_list_demand_return_total),true);
            $store_list_demand_transfer = json_decode(json_encode($store_list_demand_transfer),true);
            $inv_list_in_stores = json_decode(json_encode($inv_list_in_stores),true);
            $store_list = json_decode(json_encode($store_list),true);
            
            $stores = Store::where('is_deleted',0)->get()->toArray();
            
            for($i=0;$i<count($stores);$i++){
                $store_id = $stores[$i]['id'];
                $index = array_search($store_id,array_column($store_list_demand_pushed,'store_id'));
                $stores[$i]['inv_pushed'] = ($index !== false)?$store_list_demand_pushed[$index]['inv_count_pushed']:0;
                
                $index = array_search($store_id,array_column($store_list_demand_pushed_total,'store_id'));
                $stores[$i]['inv_pushed_total'] = ($index !== false)?$store_list_demand_pushed_total[$index]['inv_count_pushed']:0;
                
                $index = array_search($store_id,array_column($store_list_demand_return,'store_id'));
                $stores[$i]['inv_return'] = ($index !== false)?$store_list_demand_return[$index]['inv_count_return']:0;
                
                $index = array_search($store_id,array_column($store_list_demand_return_total,'store_id'));
                $stores[$i]['inv_return_total'] = ($index !== false)?$store_list_demand_return_total[$index]['inv_count_return']:0;
                
                $index = array_search($store_id,array_column($store_list_demand_transfer,'store_id'));
                $stores[$i]['inv_transfer'] = ($index !== false)?$store_list_demand_transfer[$index]['inv_count_transfer']:0;
                
                $index = array_search($store_id,array_column($inv_list_in_stores,'store_id'));
                $stores[$i]['inv_balance'] = ($index !== false)?$inv_list_in_stores[$index]['inv_count']:0;
                
                $index = array_search($store_id,array_column($store_list,'store_id'));
                $stores[$i]['inv_sold'] = ($index !== false)?$store_list[$index]['prod_count']:0;
                $stores[$i]['inv_sold_price'] = ($index !== false)?$store_list[$index]['prod_net_price']:0;
                
            }
            
            $totals = array_column($stores,'inv_pushed_total');
            array_multisort($totals, SORT_DESC, $stores);
            
            $store_list = $stores;
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Store Name','Store Code','Total Pushed to Store Units','Pushed to Store Units','Sold Units','Balance Units','Sell Through','Total Net Price');

                $callback = function() use ($store_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_balance'=>0,'inv_sold_price'=>0);
                    
                    for($i=0;$i<count($store_list);$i++){
                        $sell_through = ($store_list[$i]['inv_balance']+$store_list[$i]['inv_sold'] > 0)?round(($store_list[$i]['inv_sold']/($store_list[$i]['inv_balance']+$store_list[$i]['inv_sold']))*100,3).'%':0;
                        $array = array($store_list[$i]['store_name'],$store_list[$i]['store_id_code'],$store_list[$i]['inv_pushed_total'],$store_list[$i]['inv_pushed'],$store_list[$i]['inv_sold'],$store_list[$i]['inv_balance'],$sell_through,$store_list[$i]['inv_sold_price']);
                        
                        fputcsv($file, $array);

                        $total_data['inv_pushed_total']+=$store_list[$i]['inv_pushed_total']; 
                        $total_data['inv_pushed']+=$store_list[$i]['inv_pushed']; 
                        $total_data['inv_sold']+=$store_list[$i]['inv_sold']; 
                        $total_data['inv_balance']+=$store_list[$i]['inv_balance']; 
                        $total_data['inv_sold_price']+=$store_list[$i]['inv_sold_price'];
                    }

                    $array = array('Total','',$total_data['inv_pushed_total'],$total_data['inv_pushed'],$total_data['inv_sold'],$total_data['inv_balance'],'',$total_data['inv_sold_price']);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            return view('admin/report_store_sales',array('store_list'=>$store_list,'error_message'=>$error_message,'po_category_sale_data'=>$po_category_sale_data));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_SALES_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_store_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function storeStaffSalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
           
            $staff_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('store_staff as ss','pcod.staff_id', '=', 'ss.id')         
            ->join('store as s','pcod.store_id', '=', 's.id')         
            ->where('pcod.is_deleted',0)
            ->where('ss.is_deleted',0)        
            ->where('s.is_deleted',0)        
            ->where('pcod.fake_inventory',0)        
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'");     
            
            $return_products_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('store as s','pcod.store_id', '=', 's.id')  
            ->where('pcod.product_quantity',-1)        
            ->where('pcod.is_deleted',0)
            ->where('pcod.fake_inventory',0)         
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'");
            
            if($user->user_type == 9){
                $store_data = CommonHelper::getUserStoreData($user->id);
                $staff_list = $staff_list->where('s.id',$store_data->id);
                
                $return_products_list = $return_products_list->where('s.id',$store_data->id);
            }else{
                if(isset($data['s_id']) && !empty($data['s_id'])){
                    $staff_list = $staff_list->where('s.id',$data['s_id']);
                    $return_products_list = $return_products_list->where('s.id',$data['s_id']);
                }
            }
            
            $staff_list = $staff_list->groupByRaw('ss.id')        
            ->selectRaw('ss.name as staff_name,s.store_name,s.store_id_code, SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price')     
            ->orderBy('prod_net_price','DESC')        
            ->get()->toArray();
            
            $return_products_list = $return_products_list->selectRaw("SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price")
            ->first();        
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_staff_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Staff Member','Store Name','Store Code','Units Count','Total NET Price');

                $callback = function() use ($staff_list,$return_products_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_units = $total_price = 0;
                    for($i=0;$i<count($staff_list);$i++){
                        $array = array($staff_list[$i]->staff_name,$staff_list[$i]->store_name,$staff_list[$i]->store_id_code,$staff_list[$i]->prod_count,$staff_list[$i]->prod_net_price);
                        $total_units+=$staff_list[$i]->prod_count;
                        $total_price+=$staff_list[$i]->prod_net_price;
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total Sales','','',$total_units,$total_price);
                    fputcsv($file, $array);
                    
                    $array = array('Total Returns','','',abs($return_products_list->prod_count),abs($return_products_list->prod_net_price));
                    fputcsv($file, $array);
                    
                    $array = array('Total','','',$total_units+$return_products_list->prod_count,$total_price+$return_products_list->prod_net_price);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $store_list = CommonHelper::getStoresList();
            
            return view('admin/report_store_staff_sales',array('staff_list'=>$staff_list,'store_list'=>$store_list,'error_message'=>$error_message,'user'=>$user,'return_products_list'=>$return_products_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_STAFF_SALES_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_store_staff_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function priceSalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $store_id = '';
            $price_slots = $price_slots_1 = array();
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            
            if($user->user_type == 9){
                $store_data = CommonHelper::getUserStoreData($user->id);
                $store_id = $store_data->id;
            }else{
                $store_id = (isset($data['s_id']))?$data['s_id']:'';
            }
            
            if(isset($data['action']) && $data['action'] == 'get_category_report'){
                $category_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')         
                ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')        
                ->where('ppm.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->where('pcod.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)       
                ->where('pcod.order_status',1)        
                ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")        
                ->whereRaw('ABS(pcod.sale_price) >='.trim($data['min']))           
                ->whereRaw('ABS(pcod.sale_price) <='.trim($data['max']));
                
                if(!empty($store_id)){
                    $category_list = $category_list->where('pcod.store_id',$store_id);   
                }
                
                $category_list = $category_list->groupByRaw('ppm.category_id')        
                ->selectRaw('dlim_1.id as category_id,dlim_1.name as category_name,SUM(pcod.product_quantity) as cat_count,SUM(pcod.net_price) as cat_net_price,SUM(pcod.sale_price) as cat_sale_price')     
                ->orderByRaw('cat_count DESC')        
                ->get()->toArray();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Category data','category_list'=>$category_list,'min'=>$data['min'],'max'=>$data['max']),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'get_subcategory_report'){
                $subcategory_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')         
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.subcategory_id', '=', 'dlim_1.id')        
                ->where('ppm.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->where('pcod.fake_inventory',0) 
                ->where('ppm.fake_inventory',0)        
                ->where('pcod.order_status',1)        
                ->where('ppm.category_id',trim($data['category_id']))
                ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'")        
                ->whereRaw('ABS(pcod.sale_price) >='.trim($data['min']))           
                ->whereRaw('ABS(pcod.sale_price) <='.trim($data['max']));
                
                if(!empty($store_id)){
                    $subcategory_list = $subcategory_list->where('pcod.store_id',$store_id);   
                }
                
                $subcategory_list = $subcategory_list->groupByRaw('ppm.subcategory_id')        
                ->selectRaw('dlim_1.id as subcategory_id,dlim_1.name as subcategory_name,SUM(pcod.product_quantity) as subcat_count,SUM(pcod.net_price) as subcat_net_price,SUM(pcod.sale_price) as subcat_sale_price')     
                ->orderByRaw('subcat_count DESC')        
                ->get()->toArray();
                
                $category_data = Design_lookup_items_master::where('id',trim($data['category_id']))->first();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Subcategory data','subcategory_list'=>$subcategory_list,'category_data'=>$category_data),200);
            }
           
            $price_list = \DB::table('pos_customer_orders_detail as pcod')
            ->where('pcod.is_deleted',0)
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'");
            
            if(!empty($store_id)){
                $price_list = $price_list->where('pcod.store_id',$store_id);   
            }
            
            // ABS is used as there are refunds orders with negative sale price
            $price_list = $price_list->groupByRaw('ABS(pcod.sale_price)')        
            ->selectRaw('pcod.sale_price,SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price')     
            ->orderBy('pcod.sale_price','ASC')        
            ->get()->toArray();
            
            // Create mrp slots from 0 to 20000
            $i = $total = 0;
            while($i<20000){
                $q=$i+500;
                $price_slots[] = array('min'=>$i+1,'max'=>$q,'prod_count'=>0,'prod_net_price'=>0);
                $i=$q;
            }
            
            // Iterate price and slot loops to add prices and count in time slots
            for($i=0;$i<count($price_list);$i++){
                // Abs is used as there are slots with total as negative or only as negative slot.
                $sale_price = abs($price_list[$i]->sale_price);
                for($q=0;$q<count($price_slots);$q++){
                    if($sale_price >= $price_slots[$q]['min'] && $sale_price <= $price_slots[$q]['max']){
                        $price_slots[$q]['prod_count']+=$price_list[$i]->prod_count;
                        $price_slots[$q]['prod_net_price']+=$price_list[$i]->prod_net_price;
                        break;
                    }
                }
            }
            
            // Remove price slots from start which have product count 0
            for($i=0;$i<count($price_slots);$i++){
                if($price_slots[$i]['prod_count'] == 0){
                    unset($price_slots[$i]);
                }else{
                    break;
                }
            }
            
            $price_slots = array_values($price_slots);
            
            // Remove price slots from end which have product count 0
            for($i=count($price_slots)-1;$i>=0;$i--){
                if($price_slots[$i]['prod_count'] == 0){
                    unset($price_slots[$i]);
                }else{
                    break;
                }
            }
            
            $price_slots = array_values($price_slots);
            
           if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=price_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('MRP Slot','Units Count','Avg Unit Net Price','Total NET Price');

                $callback = function() use ($price_slots, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_units = $total_price = 0;
                    for($i=0;$i<count($price_slots);$i++){
                        $avg_net_price = ($price_slots[$i]['prod_count']>0)?round($price_slots[$i]['prod_net_price']/$price_slots[$i]['prod_count'],2):'0.0';
                        $array = array($price_slots[$i]['min'].'-'.$price_slots[$i]['max'],$price_slots[$i]['prod_count'],$avg_net_price,$price_slots[$i]['prod_net_price']);
                        $total_units+=$price_slots[$i]['prod_count'];
                        $total_price+=$price_slots[$i]['prod_net_price'];
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total',$total_units,'',$total_price);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $store_list = CommonHelper::getStoresList();
            
            return view('admin/report_price_sales',array('price_slots'=>$price_slots,'error_message'=>$error_message,'store_list'=>$store_list,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRICE_SALES_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_price_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function timeSlotSalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
           
            $orders_list = \DB::table('pos_customer_orders as pco')
            ->join('pos_customer_orders_detail as pcod','pcod.order_id', '=', 'pco.id')        
            ->join('store as s','pco.store_id', '=', 's.id')         
            ->where('pco.is_deleted',0)
            ->where('s.is_deleted',0)        
            ->where('pco.fake_inventory',0) 
            ->where('pcod.fake_inventory',0)    
            ->where('pco.order_status',1)        
            ->where('pcod.order_status',1)        
            ->whereRaw("DATE(pcod.created_at) >= '$start_date' AND DATE(pcod.created_at) <= '$end_date'");     
            
            if($user->user_type == 9){
                $store_data = CommonHelper::getUserStoreData($user->id);
                $orders_list = $orders_list->where('s.id',$store_data->id);
            }else{
                if(isset($data['s_id']) && !empty($data['s_id'])){
                    $orders_list = $orders_list->where('s.id',$data['s_id']);
                }
            }
            
            $orders_list = $orders_list->groupByRaw('HOUR(pco.created_at)')        
            ->selectRaw('HOUR(pco.created_at) as order_hour, SUM(pcod.product_quantity) as prod_count,SUM(pcod.net_price) as prod_net_price')     
            ->orderBy('order_hour','ASC')        
            ->get()->toArray();
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=time_slot_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Time Slot','Total Units','Total NET Price');

                $callback = function() use ($orders_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_units = $total_price = 0;
                    for($i=0;$i<count($orders_list);$i++){
                        $start_hour = $orders_list[$i]->order_hour;$end_hour = $orders_list[$i]->order_hour+1; 
                        if($start_hour >= 12 ) {if($start_hour != 12) $start_hour = $start_hour-12; $start_hour.=' PM'; } else{ $start_hour.=' AM';} 
                        if($end_hour >= 12 ) {if($end_hour != 12) $end_hour = $end_hour-12; $end_hour.=' PM'; } else{ $end_hour.=' AM';} 
      
                        $array = array($start_hour.' - '.$end_hour,$orders_list[$i]->prod_count,$orders_list[$i]->prod_net_price);
                        $total_units+=$orders_list[$i]->prod_count;
                        $total_price+=$orders_list[$i]->prod_net_price;
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total',$total_units,$total_price);
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            $store_list = CommonHelper::getStoresList();
            
            return view('admin/report_time_slot_sales',array('orders_list'=>$orders_list,'store_list'=>$store_list,'error_message'=>$error_message,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_STAFF_SALES_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_time_slot_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function hsnCodeGstList(Request $request){
        try{ 
            $data = $request->all();
            $max_amount_name = 'Maximum Amount';
            $maximum_amount = 1000000000000;
            
            if(isset($data['action']) &&  $data['action'] == 'get_hsn_gst_data'){
                $gst_data = Gst_rates::where('id',$data['id'])->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'hsn gst data','gst_data'=>$gst_data,'maximum_amount'=>$maximum_amount),200);
            }
            
            if(isset($data['action']) &&  $data['action'] == 'add_hsn_gst_data'){
                $validateionRules = array('hsn_code_add'=>'required','min_amount_add'=>'required|numeric','rate_percent_add'=>'required|numeric','max_amount_add'=>'required_without:max_amount_chk_add');
                $attributes = array('hsn_code_add'=>'HSN Code','min_amount_add'=>'Min Amount','max_amount_add'=>'Max Amount','rate_percent_add'=>'GST %','max_amount_chk_add'=>'Maximum Amount');
                
                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                $max_amount = (isset($data['max_amount_chk_add']) && !empty($data['max_amount_chk_add']))?$maximum_amount:$data['max_amount_add'];
                
                if($data['min_amount_add'] >= $max_amount){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Min Amount should be less than Max Amount', 'errors' => 'Min Amount should be less than Max Amount'));
                }
                
                $hsn_exists = Gst_rates::where('hsn_code',$data['hsn_code_add'])->where('min_amount',$data['min_amount_add'])->where('max_amount',$data['max_amount_add'])->where('is_deleted',0)->first();
                if(!empty($hsn_exists)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'HSN Code with this Min and Max Amount already exists', 'errors' => 'HSN Code with this Min and Max Amount already exists'));
                }
                
                $hsn_min_range_exists = Gst_rates::where('hsn_code',$data['hsn_code_add'])->where('min_amount','<',$data['min_amount_add'])->where('max_amount','>',$data['min_amount_add'])->where('is_deleted',0)->first();
                if(!empty($hsn_min_range_exists)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'HSN Code with this Min and Max Amount Range already exists', 'errors' => 'HSN Code with this Min and Max Amount Range already exists'));
                }
                
                $hsn_max_range_exists = Gst_rates::where('hsn_code',$data['hsn_code_add'])->where('min_amount','<',$max_amount)->where('max_amount','>',$max_amount)->where('is_deleted',0)->first();
                if(!empty($hsn_max_range_exists)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'HSN Code with this Min and Max Amount Range already exists', 'errors' => 'HSN Code with this Min and Max Amount Range already exists'));
                }
                
                $insertArray = array('hsn_code'=>$data['hsn_code_add'],'min_amount'=>$data['min_amount_add'],'rate_percent'=>$data['rate_percent_add']);
                $insertArray['max_amount'] = $max_amount; //(isset($data['max_amount_chk_add']) && !empty($data['max_amount_chk_add']))?$maximum_amount:$data['max_amount_add'];
                
                $res = Gst_rates::create($insertArray);
                
                CommonHelper::createLog('HSN Codes GST Added. ID: '.$res->id,'HSN_CODE_GST_ADDED','HSN_CODE_GST');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'HSN GST Data Added'),200);
            }
            
            if(isset($data['action']) &&  $data['action'] == 'update_hsn_gst_data'){
                $validateionRules = array('hsn_code_edit'=>'required','min_amount_edit'=>'required|numeric','rate_percent_edit'=>'required|numeric','max_amount_edit'=>'required_without:max_amount_chk_edit');
                $attributes = array('hsn_code_edit'=>'HSN Code','min_amount_edit'=>'Min Amount','max_amount_edit'=>'Max Amount','rate_percent_edit'=>'GST %','max_amount_chk_edit'=>'Maximum Amount');
                
                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                $max_amount = (isset($data['max_amount_chk_edit']) && !empty($data['max_amount_chk_edit']))?$maximum_amount:$data['max_amount_edit'];
                
                if($data['min_amount_edit'] >= $max_amount){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Min Amount should be less than Max Amount', 'errors' => 'Min Amount should be less than Max Amount'));
                }
                
                $hsn_exists = Gst_rates::where('id','!=',$data['hsn_gst_id'])->where('hsn_code',$data['hsn_code_edit'])->where('min_amount',$data['min_amount_edit'])->where('max_amount',$data['max_amount_edit'])->where('is_deleted',0)->first();
                if(!empty($hsn_exists)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'HSN Code with this Min and Max Amount already exists', 'errors' => 'HSN Code with this Min and Max Amount already exists'));
                }
                
                $hsn_min_range_exists = Gst_rates::where('id','!=',$data['hsn_gst_id'])->where('hsn_code',$data['hsn_code_edit'])->where('min_amount','<',$data['min_amount_edit'])->where('max_amount','>',$data['min_amount_edit'])->where('is_deleted',0)->first();
                if(!empty($hsn_min_range_exists)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'HSN Code with this Min and Max Amount Range already exists', 'errors' => 'HSN Code with this Min and Max Amount Range already exists'));
                }
                
                $hsn_max_range_exists = Gst_rates::where('id','!=',$data['hsn_gst_id'])->where('hsn_code',$data['hsn_code_edit'])->where('min_amount','<',$max_amount)->where('max_amount','>',$max_amount)->where('is_deleted',0)->first();
                if(!empty($hsn_max_range_exists)){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'HSN Code with this Min and Max Amount Range already exists', 'errors' => 'HSN Code with this Min and Max Amount Range already exists'));
                }
                
                $updateArray = array('hsn_code'=>$data['hsn_code_edit'],'min_amount'=>$data['min_amount_edit'],'rate_percent'=>$data['rate_percent_edit']);
                $updateArray['max_amount'] = $max_amount; //(isset($data['max_amount_chk_edit']) && !empty($data['max_amount_chk_edit']))?$maximum_amount:$data['max_amount_edit'];
                
                Gst_rates::where('id',$data['hsn_gst_id'])->update($updateArray);
                
                CommonHelper::createLog('HSN Codes GST Updated. ID: '.$data['hsn_gst_id'],'HSN_CODE_GST_UPDATED','HSN_CODE_GST');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'HSN GST Data Updated'),200);
            }
            
            if(isset($data['action']) &&  $data['action'] == 'delete_hsn_gst_data'){
                $updateArray = array('is_deleted'=>1);
                Gst_rates::where('id',$data['id'])->update($updateArray);
                
                CommonHelper::createLog('HSN Codes GST Deleted. ID: '.$data['id'],'HSN_CODE_GST_DELETED','HSN_CODE_GST');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'HSN GST data deleted successfully'),200);
            }
            
            $hsn_gst_rates = Gst_rates::where('is_deleted',0);
            
            if(isset($data['id']) && !empty($data['id'])){
                $hsn_gst_rates = $hsn_gst_rates->where('id',$data['id']);
            }
            
            if(isset($data['hsn_code']) && !empty($data['hsn_code'])){
                $hsn_gst_rates = $hsn_gst_rates->where('hsn_code',$data['hsn_code']);
            }
            
            $hsn_gst_rates = $hsn_gst_rates->orderBy('id')->get()->toArray();
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=hsn_codes_gst_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Record ID','HSN Code','Min Amount','Max Amount','GST%');

                $callback = function() use ($hsn_gst_rates,$max_amount_name,$maximum_amount,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($hsn_gst_rates);$i++){
                        if($maximum_amount == $hsn_gst_rates[$i]['max_amount']) $max_amount =  $max_amount_name;  else $max_amount =  $hsn_gst_rates[$i]['max_amount'];
                        $array = array($hsn_gst_rates[$i]['id'],$hsn_gst_rates[$i]['hsn_code'],$hsn_gst_rates[$i]['min_amount'],$max_amount,$hsn_gst_rates[$i]['rate_percent']);
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }

            return view('admin/hsn_code_gst_list',array('hsn_gst_rates'=>$hsn_gst_rates,'error_message'=>'','max_amount'=>$maximum_amount,'max_amount_name'=>$max_amount_name));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'HSN_GST',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('admin/hsn_code_gst_list',array('error_message'=>$e->getMessage(),'hsn_gst_rates'=>array()));
            }
        }
    }
    
    function categoryHsnCodeList(Request $request){
        try{ 
            $data = $request->all();
            if(isset($data['action']) &&  $data['action'] == 'get_category_hsn_data'){
                $hsn_data = \DB::table('design_lookup_items_master as dlim')
                ->leftJoin('category_hsn_code as chc','dlim.id', '=', 'chc.category_id')     
                ->where('dlim.id',$data['id'])
                ->select('dlim.*','chc.hsn_code')        
                ->first();        
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'category hsn data','hsn_data'=>$hsn_data),200);
            }
            
            if(isset($data['action']) &&  $data['action'] == 'update_category_hsn_data'){
                $validationRules = array('hsn_code_edit'=>'required','category_id'=>'required|numeric');
                $attributes = array('hsn_code_edit'=>'HSN Code','category_id'=>'Category');
                
                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                $hsn_code = trim($data['hsn_code_edit']);
                $category_id = trim($data['category_id']);
                
                $category_hsn_data = Category_hsn_code::where('category_id',$category_id)->where('is_deleted',0)->first();
                
                if(empty($category_hsn_data)){
                    $insertArray = array('category_id'=>$category_id,'hsn_code'=>$hsn_code);
                    $res = Category_hsn_code::create($insertArray);
                    $cat_id = $category_id;
                }else{
                    $updateArray = array('hsn_code'=>$hsn_code);
                    Category_hsn_code::where('id',$category_hsn_data['id'])->update($updateArray);
                    $cat_id  = $category_hsn_data['category_id'];
                }
                
                CommonHelper::createLog('Category HSN Codes Updated. Category ID: '.$cat_id,'CATEGORY_HSN_CODE_UPDATED','CATEGORY_HSN_CODE');
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' =>'Category HSN data updated successfully'),200);
            }
            
            $category_list = \DB::table('design_lookup_items_master as dlim')
            ->leftJoin('category_hsn_code as chc','dlim.id', '=', 'chc.category_id')     
            ->where('dlim.type','POS_PRODUCT_CATEGORY')
            ->where('dlim.is_deleted',0)
            ->select('dlim.*','chc.hsn_code')     
            ->orderBy('dlim.id');        
            
            if(isset($data['id']) && !empty($data['id'])){
                $category_list = $category_list->where('dlim.id',$data['id']);
            }       
            
            $category_list = $category_list->get()->toArray();

            return view('admin/category_hsn_code_list',array('category_list'=>$category_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY_GSN',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message'=>$e->getMessage()),500);
            }else{
                return view('admin/category_hsn_code_list',array('error_message'=>$e->getMessage(),'category_list'=>array()));
            }
        }
    }
    
    function debitNotesList(Request $request){
        try{
            $data = $request->all();
            $start_date = $end_date = '';
            $debit_notes_list = $debit_note_data = array();
            $type_id = (isset($data['type_id']) && !empty($data['type_id']))?$data['type_id']:1;
            $debit_note_no = (isset($data['debit_note_no']) && !empty($data['debit_note_no']))?$data['debit_note_no']:'';
            $debit_note_id = (isset($data['debit_note_id']) && !empty($data['debit_note_id']))?$data['debit_note_id']:'';
            $action = (isset($data['action']) && !empty($data['action']))?trim($data['action']):'';
            
            $debit_note_types = array('1'=>'Defective Inventory Returned from Warehouse to Vendor in PO Invoice QC',
            '4'=>'SOR Stock Return from Warehouse to Vendor',
            '6'=>'Less Inventory from Warehouse to Store in Push Demand',
            '5'=>'Less Inventory from Vendor to Warehouse in PO Invoice',
            '7'=>'Excess Amount from Vendor to Warehouse in PO Invoice',    
            '2'=>'SOR Stock Return from Store to Warehouse',
            '3'=>'Complete Stock Return from Store to Warehouse');
            
            $user = Auth::user();
            // If user is logged as vendor role
            if($user->user_type == 15){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->first();
                if(!in_array($type_id, array(1,4,5))){
                    throw new \Exception('Access Denied'); 
                }
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            if($type_id == 1){
                $debit_notes_list = \DB::table('purchase_order_grn_qc as po_grn_qc')
                ->join('purchase_order as po','po.id', '=', 'po_grn_qc.po_id')        
                ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')
                ->where('po_grn_qc.type','qc_return');

                // Fetch only vendor records
                if($user->user_type == 15){
                    $debit_notes_list = $debit_notes_list->where('po.vendor_id',$vendor_data->id);
                }

                if(!empty($start_date) && !empty($end_date)){
                    $debit_notes_list = $debit_notes_list->whereRaw("DATE(po_grn_qc.created_at) >= '$start_date' AND DATE(po_grn_qc.created_at) <= '$end_date'");        
                }
                
                if(isset($data['id']) && !empty($data['id'])){
                    $debit_notes_list = $debit_notes_list->where('po_grn_qc.id',trim($data['id']));
                }
                
                $debit_notes_list = $debit_notes_list->where('po_grn_qc.is_deleted',0)        
                ->where('po_grn_qc.fake_inventory',0)     
                ->where('po.fake_inventory',0)         
                ->where('pod.fake_inventory',0)        
                ->select('po_grn_qc.*','po.order_no','pod.invoice_no')
                ->orderBy('po_grn_qc.id','DESC');
                
                if($action == 'search_debit_note_no' && !empty($debit_note_no)){
                    $debit_note = clone ($debit_notes_list);
                    $debit_note = $debit_note->where('po_grn_qc.grn_no',$debit_note_no)->first();
                    if(!empty($debit_note)){
                        $debit_note_data = ['debit_note_no'=>$debit_note->grn_no,'debit_note_id'=>$debit_note->id,'type_id'=>1];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','debit_note_data'=>$debit_note_data),200);
                    }
                }else{
                
                    if(!empty($debit_note_id)){
                        $debit_notes_list = $debit_notes_list->where('po_grn_qc.grn_no',$debit_note_no);
                    }

                    $debit_notes_list = $debit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 2 || $action == 'search_debit_note_no'){
                $debit_notes_list = \DB::table('store_products_demand as spd1')
                ->leftJoin('store_products_demand as spd2',function($join){$join->on('spd1.push_demand_id','=','spd2.id')->where('spd2.is_deleted','=',0);})        
                ->join('store as s','spd1.store_id', '=', 's.id')        
                ->where('spd1.demand_type','inventory_return_to_warehouse')        
                ->wherein('spd1.demand_status',['warehouse_loaded','cancelled'])        
                ->where('spd1.is_deleted','0')
                ->where('spd1.fake_inventory',0)         
                ->where('s.is_deleted','0');      
                
                if(!empty($start_date) && !empty($end_date)){
                    $debit_notes_list = $debit_notes_list->whereRaw("DATE(spd1.created_at) >= '$start_date' AND DATE(spd1.created_at) <= '$end_date'");        
                }        
                
                if(isset($data['id']) && !empty($data['id'])){
                    $debit_notes_list = $debit_notes_list->where('spd1.id',trim($data['id']));
                }
                
                $debit_notes_list = $debit_notes_list->select('spd1.*','spd2.invoice_no as base_demand_invoice_no','s.store_name','s.store_id_code')
                ->orderBy('spd1.id','DESC');
                
                if($action == 'search_debit_note_no' && !empty($debit_note_no)){
                    $debit_note = clone ($debit_notes_list);
                    $debit_note = $debit_note->where('spd1.invoice_no',$debit_note_no)->first();
                    
                    if(!empty($debit_note)){
                        $debit_note_data = ['debit_note_no'=>$debit_note->invoice_no,'debit_note_id'=>$debit_note->id,'type_id'=>2];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','debit_note_data'=>$debit_note_data),200);
                    }
                }else{
                    if(!empty($debit_note_no)){
                        $debit_notes_list = $debit_notes_list->where('spd1.invoice_no',$debit_note_no);
                    }

                    $debit_notes_list = $debit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 3 || $action == 'search_debit_note_no'){
                $debit_notes_list = \DB::table('store_products_demand as spd1')
                ->join('store as s','spd1.store_id', '=', 's.id')        
                ->where('spd1.demand_type','inventory_return_complete')        
                ->wherein('spd1.demand_status',['store_loaded','cancelled'])        
                ->where('spd1.is_deleted','0')
                ->where('spd1.fake_inventory',0)                 
                ->where('s.is_deleted','0');
                
                if(!empty($start_date) && !empty($end_date)){
                    $debit_notes_list = $debit_notes_list->whereRaw("DATE(spd1.created_at) >= '$start_date' AND DATE(spd1.created_at) <= '$end_date'");        
                } 
                
                if(isset($data['id']) && !empty($data['id'])){
                    $debit_notes_list = $debit_notes_list->where('spd1.id',trim($data['id']));
                }
                
                $debit_notes_list = $debit_notes_list->select('spd1.*','s.store_name','s.store_id_code')
                ->orderBy('spd1.id','DESC'); 
                
                if($action == 'search_debit_note_no' && !empty($debit_note_no)){
                    $debit_note = clone ($debit_notes_list);
                    $debit_note = $debit_note->where('spd1.invoice_no',$debit_note_no)->first();
                    
                    if(!empty($debit_note)){
                        $debit_note_data = ['debit_note_no'=>$debit_note->invoice_no,'debit_note_id'=>$debit_note->id,'type_id'=>3];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','debit_note_data'=>$debit_note_data),200);
                    }
                }else{
                    if(!empty($debit_note_no)){
                        $debit_notes_list = $debit_notes_list->where('spd1.invoice_no',$debit_note_no);
                    }

                    $debit_notes_list = $debit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 4 || $action == 'search_debit_note_no'){
                $debit_notes_list = \DB::table('store_products_demand as spd')
                //->join('purchase_order_details as pod','pod.id', '=', 'spd.push_demand_id')                
                //->join('purchase_order as po','po.id', '=', 'pod.po_id')         
                ->join('vendor_detail as vd','vd.id', '=', 'spd.store_id')          
                ->join('users as u','u.id', '=', 'spd.user_id')            
                ->where('spd.demand_type','inventory_return_to_vendor')
                ->wherein('spd.demand_status',['warehouse_dispatched','cancelled'])        
                ->where('spd.status',1)
                ->where('spd.is_deleted',0)
                //->where('pod.is_deleted',0)        
                ->where('spd.fake_inventory',0)         
                ->where('vd.is_deleted',0);
                
                if($user->user_type == 15){
                    $debit_notes_list = $debit_notes_list->where('demand_status','warehouse_dispatched')
                    ->where('spd.store_id',$vendor_data->id);   // vendor id is stored in store_id column
                }

                if(!empty($start_date) && !empty($end_date)){
                    $debit_notes_list = $debit_notes_list->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
                }                
                
                if(isset($data['id']) && !empty($data['id'])){
                    $debit_notes_list = $debit_notes_list->where('spd.id',trim($data['id']));
                }
                
                $debit_notes_list = $debit_notes_list->select('spd.*','u.name as user_name','vd.name as vendor_name')
                ->orderBy('spd.id','DESC');
                
                if($action == 'search_debit_note_no' && !empty($debit_note_no)){
                    $debit_note = clone ($debit_notes_list);
                    $debit_note = $debit_note->where('spd.invoice_no',$debit_note_no)->first();
                    
                    if(!empty($debit_note)){
                        $debit_note_data = ['debit_note_no'=>$debit_note->invoice_no,'debit_note_id'=>$debit_note->id,'type_id'=>4];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','debit_note_data'=>$debit_note_data),200);
                    }
                }else{
                    if(!empty($debit_note_no)){
                        $debit_notes_list = $debit_notes_list->where('spd.invoice_no',$debit_note_no);
                    }

                    $debit_notes_list = $debit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 5 || $action == 'search_debit_note_no'){
                $debit_notes_list = \DB::table('debit_notes as dn')
                ->join('purchase_order_details as pod','dn.invoice_id', '=', 'pod.id')        
                ->join('purchase_order as po','pod.po_id', '=', 'po.id')  
                ->join('vendor_detail as vd','po.vendor_id', '=', 'vd.id')           
                ->where('dn.debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice') 
                ->wherein('dn.debit_note_status',['completed','cancelled'])        
                ->where('dn.is_deleted','0')
                ->where('pod.fake_inventory',0)         
                ->where('po.fake_inventory',0)                 
                ->select('dn.*','po.order_no','vd.name as vendor_name','pod.invoice_no','pod.invoice_date');
                
                if($user->user_type == 15){
                    $debit_notes_list = $debit_notes_list->where('po.vendor_id',$vendor_data->id);
                }
                
                if(!empty($start_date) && !empty($end_date)){
                    $debit_notes_list = $debit_notes_list->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");        
                }
                
                if(isset($data['id']) && !empty($data['id'])){
                    $debit_notes_list = $debit_notes_list->where('dn.id',trim($data['id']));
                }
                
                $debit_notes_list = $debit_notes_list->orderBy('dn.debit_note_no','DESC');
                
                if($action == 'search_debit_note_no' && !empty($debit_note_no)){
                    $debit_note = clone ($debit_notes_list);
                    $debit_note = $debit_note->where('dn.debit_note_no',$debit_note_no)->first();
                    
                    if(!empty($debit_note)){
                        $debit_note_data = ['debit_note_no'=>$debit_note->debit_note_no,'debit_note_id'=>$debit_note->id,'type_id'=>5];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','debit_note_data'=>$debit_note_data),200);
                    }
                }else{
                    if(!empty($debit_note_no)){
                        $debit_notes_list = $debit_notes_list->where('dn.debit_note_no',$debit_note_no);
                    }

                    $debit_notes_list = $debit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 6 || $action == 'search_debit_note_no'){
                $debit_notes_list = \DB::table('debit_notes as dn')
                ->join('store_products_demand as spd','spd.id', '=', 'dn.invoice_id')                        
                ->join('store as s','spd.store_id', '=', 's.id')                
                ->where('dn.debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand') 
                ->wherein('dn.debit_note_status',['completed','cancelled'])        
                ->where('spd.fake_inventory',0)                
                ->where('dn.is_deleted','0');
                
                if(!empty($start_date) && !empty($end_date)){
                    $debit_notes_list = $debit_notes_list->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");           
                }
                
                if(isset($data['id']) && !empty($data['id'])){
                    $debit_notes_list = $debit_notes_list->where('dn.id',trim($data['id']));
                }
                
                $debit_notes_list = $debit_notes_list->select('dn.*','s.store_name','spd.invoice_no as demand_invoice_no','s.store_id_code')
                ->orderBy('dn.id','DESC');
                
                if($action == 'search_debit_note_no' && !empty($debit_note_no)){
                    $debit_note = clone ($debit_notes_list);
                    $debit_note = $debit_note->where('dn.debit_note_no',$debit_note_no)->first();
                    
                    if(!empty($debit_note)){
                        $debit_note_data = ['debit_note_no'=>$debit_note->debit_note_no,'debit_note_id'=>$debit_note->id,'type_id'=>6];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','debit_note_data'=>$debit_note_data),200);
                    }
                }else{
                    if(!empty($debit_note_no)){
                        $debit_notes_list = $debit_notes_list->where('dn.debit_note_no',$debit_note_no);
                    }

                    $debit_notes_list = $debit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 7 || $action == 'search_debit_note_no'){
                $debit_notes_list = \DB::table('debit_notes as dn')
                ->join('purchase_order as po','po.id', '=', 'dn.po_id')        
                ->join('purchase_order_details as pod','pod.id', '=', 'dn.invoice_id')
                ->where('dn.debit_note_type','excess_amount')
                ->wherein('dn.debit_note_status',['completed','cancelled'])        
                ->where('dn.is_deleted',0)        
                ->where('po.fake_inventory',0)   
                ->where('pod.fake_inventory',0);
                
                if(!empty($start_date) && !empty($end_date)){
                    $debit_notes_list = $debit_notes_list->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");           
                }
                
                if(isset($data['id']) && !empty($data['id'])){
                    $debit_notes_list = $debit_notes_list->where('dn.id',trim($data['id']));
                }
                
                $debit_notes_list = $debit_notes_list->select('dn.*','po.order_no','pod.invoice_no')
                ->orderBy('dn.id','DESC');
                
                if($action == 'search_debit_note_no' && !empty($debit_note_no)){
                    $debit_note = clone ($debit_notes_list);
                    $debit_note = $debit_note->where('dn.debit_note_no',$debit_note_no)->first();
                    
                    if(!empty($debit_note)){
                        $debit_note_data = ['debit_note_no'=>$debit_note->debit_note_no,'debit_note_id'=>$debit_note->id,'type_id'=>7];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','debit_note_data'=>$debit_note_data),200);
                    }
                }else{
                    if(!empty($debit_note_no)){
                        $debit_notes_list = $debit_notes_list->where('dn.debit_note_no',$debit_note_no);
                    }

                    $debit_notes_list = $debit_notes_list->paginate(100);
                }
            }
            
            if($action == 'search_debit_note_no' && empty($debit_note_data)){
                return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'fail','message'=>$debit_note_no.' - Debit Note does not exists','errors'=>$debit_note_no.' - Debit Note does not exists'),200);
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $file_name = str_replace(' ','_',strtolower($debit_note_types[$type_id]));
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename='.$file_name.'.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                
                $callback = function() use ($debit_notes_list,$type_id,$start_date,$end_date){
                    $file = fopen('php://output', 'w');
                    
                    if($type_id == 1){
                        $debit_notes_gst = $debit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $qc_return_inv_list = \DB::table('purchase_order_grn_qc_items as po_grn_qc_items')
                        ->join('pos_product_master_inventory as ppmi','po_grn_qc_items.inventory_id', '=', 'ppmi.id')    
                        ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')
                        ->join('purchase_order as po','po.id', '=', 'po_grn_qc.po_id')        
                        ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')                
                        ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')        
                        ->join('purchase_order_grn_qc as po_grn_qc_1',function($join){$join->on('po_grn_qc.po_detail_id','=','po_grn_qc_1.po_detail_id')->where('po_grn_qc_1.type','=','grn')->where('po_grn_qc_1.is_deleted','=','0');})        
                        ->where('po_grn_qc.type','qc_return')        
                        ->where('po_grn_qc.is_deleted',0)        
                        ->where('po_grn_qc_items.is_deleted',0) 
                        ->where('ppmi.is_deleted',0)
                        ->where('pod.is_deleted',0)       
                        ->where('po_grn_qc_items.fake_inventory',0)           
                        ->where('ppmi.fake_inventory',0)           
                        ->where('po_grn_qc.fake_inventory',0)           
                        ->where('po.fake_inventory',0)                   
                        ->where('pod.fake_inventory',0)             
                        ->groupBy('po_grn_qc.id');
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $qc_return_inv_list = $qc_return_inv_list->whereRaw("DATE(po_grn_qc.created_at) >= '$start_date' AND DATE(po_grn_qc.created_at) <= '$end_date'");        
                        }
                
                        $qc_return_inv_list = $qc_return_inv_list->selectRaw('vd.name as vendor_name,vd.gst_no as vendor_gst_no,pod.invoice_no,pod.invoice_date,po.id as po_id,po_grn_qc.grn_no as debit_note_no,po_grn_qc.po_detail_id,po_grn_qc.created_at as qc_return_date,
                        po_grn_qc.comments,po.order_no as po_no,po_grn_qc.id as po_grn_qc_id,po.company_data,COUNT(po_grn_qc_items.id) as total_qty,SUM(ppmi.vendor_base_price) as total_taxable_value,SUM(ppmi.vendor_gst_amount) as total_gst_amount')        
                        ->orderBy('po_grn_qc.id')
                        ->get()->toArray();
                        
                        $qc_return_inv_list_gst = \DB::table('purchase_order_grn_qc_items as po_grn_qc_items')
                        ->join('pos_product_master_inventory as ppmi','po_grn_qc_items.inventory_id', '=', 'ppmi.id')    
                        ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')
                        ->where('po_grn_qc.type','qc_return')        
                        ->where('po_grn_qc.is_deleted',0)        
                        ->where('po_grn_qc_items.is_deleted',0) 
                        ->where('ppmi.is_deleted',0)        
                        ->where('po_grn_qc_items.fake_inventory',0)   
                        ->where('ppmi.fake_inventory',0)   
                        ->where('po_grn_qc.fake_inventory',0)           
                        ->groupByRaw('po_grn_qc.id,ppmi.vendor_gst_percent'); 
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $qc_return_inv_list_gst = $qc_return_inv_list_gst->whereRaw("DATE(po_grn_qc.created_at) >= '$start_date' AND DATE(po_grn_qc.created_at) <= '$end_date'");        
                        }
                
                        $qc_return_inv_list_gst = $qc_return_inv_list_gst->selectRaw('po_grn_qc.id,ppmi.vendor_gst_percent,COUNT(po_grn_qc_items.id) as total_qty,SUM(ppmi.vendor_base_price) as total_taxable_value,SUM(ppmi.vendor_gst_amount) as total_gst_amount')        
                        ->get()->toArray();
                        
                        for($i=0;$i<count($qc_return_inv_list_gst);$i++){
                            $gst_percent = str_replace('.00', '', $qc_return_inv_list_gst[$i]->vendor_gst_percent);
                            $key = $qc_return_inv_list_gst[$i]->id.'_'.$gst_percent;
                            $debit_notes_gst[$key] = $qc_return_inv_list_gst[$i];
                            $debit_notes_gst_types[] = $gst_percent;
                        }
                        
                        $debit_notes_gst_types = array_values(array_unique($debit_notes_gst_types));
                        
                        $columns = array('Debit Note No','Invoice No','PO No','GRN No','Debit Note Date','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Debit Note From','Debit Note To','Debit Note From GST No','Debit Note To GST No');
                        
                        for($i=0;$i<count($debit_notes_gst_types);$i++){
                            $gst_percent = $debit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        //$company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($qc_return_inv_list);$i++){
                            
                            $company_data = json_decode($qc_return_inv_list[$i]->company_data,true);
                            
                            if($qc_return_inv_list[$i]->vendor_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($qc_return_inv_list[$i]->vendor_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $qc_return_date = date('d-m-Y', strtotime($qc_return_inv_list[$i]->qc_return_date));
                            $array = array($qc_return_inv_list[$i]->debit_note_no,$qc_return_inv_list[$i]->invoice_no,$qc_return_inv_list[$i]->po_no,'',$qc_return_date,
                            $qc_return_inv_list[$i]->total_qty,$qc_return_inv_list[$i]->total_taxable_value);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($qc_return_inv_list[$i]->total_gst_amount/2,2);
                                $array[] = round($qc_return_inv_list[$i]->total_gst_amount/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($qc_return_inv_list[$i]->total_gst_amount)/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($qc_return_inv_list[$i]->total_gst_amount,2);
                                $total_data['i_gst']+=$qc_return_inv_list[$i]->total_gst_amount;
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($qc_return_inv_list[$i]->total_taxable_value+$qc_return_inv_list[$i]->total_gst_amount,2);
                            $array[] = $qc_return_inv_list[$i]->comments;
                            $array[] = $company_data['company_name'];
                            $array[] = $qc_return_inv_list[$i]->vendor_name;
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $qc_return_inv_list[$i]->vendor_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($debit_notes_gst_types);$q++){
                                $gst_percent = $debit_notes_gst_types[$q];
                                $key = $qc_return_inv_list[$i]->po_grn_qc_id.'_'.$gst_percent;
                                $array[] = $qty = isset($debit_notes_gst[$key]->total_qty)?$debit_notes_gst[$key]->total_qty:0;
                                $array[] = $total_taxable_value = isset($debit_notes_gst[$key]->total_taxable_value)?$debit_notes_gst[$key]->total_taxable_value:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount/2:0;
                                    $c_s_gst = isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount,2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                    $i_gst = isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount,2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$qc_return_inv_list[$i]->total_qty;
                            $total_data['taxable_val']+=$qc_return_inv_list[$i]->total_taxable_value;
                            $total_data['total_val']+=($qc_return_inv_list[$i]->total_taxable_value+$qc_return_inv_list[$i]->total_gst_amount);
                        }
                        
                        $array = array('Total','','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($debit_notes_gst_types);$q++){
                            $gst_percent = $debit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    if($type_id == 6){
                        $debit_notes_gst = $debit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $debit_notes_list_csv = \DB::table('debit_notes as dn')
                        ->join('store_products_demand as spd','spd.id', '=', 'dn.invoice_id')          
                        ->join('debit_note_items as dni','dni.debit_note_id', '=', 'dn.id')                
                        ->join('store_products_demand_inventory as spdi',function($join){$join->on('spdi.inventory_id','=','dni.item_id')->on('spdi.demand_id','=','dn.invoice_id')->where('spdi.is_deleted','=','0');})        
                        ->join('store as s','spd.store_id', '=', 's.id')                
                        ->where('dn.debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand') 
                        ->wherein('dn.debit_note_status',['completed','cancelled'])            
                        ->where('dn.is_deleted','0')
                        ->where('dni.is_deleted','0')        
                        ->where('spd.fake_inventory',0)         
                        ->groupBy('dn.id');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $debit_notes_list_csv = $debit_notes_list_csv->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");        
                        }
                        
                        $debit_notes_list_csv = $debit_notes_list_csv->selectRaw('dn.*,s.store_name,s.gst_no as store_gst_no,spd.invoice_no as demand_invoice_no,
                        spdi.store_base_rate,spdi.store_gst_percent,spdi.store_gst_amount,spdi.store_base_price,spd.company_gst_no,spd.company_gst_name,
                        spd.store_data,COUNT(dni.id) as total_qty,SUM(spdi.store_base_rate) as total_taxable_value,SUM(spdi.store_gst_amount) as total_gst_amount')
                        ->orderBy('dn.id','ASC')
                        ->get()->toArray();
                        
                        $debit_notes_list_gst_csv = \DB::table('debit_notes as dn')
                        ->join('debit_note_items as dni','dni.debit_note_id', '=', 'dn.id')                
                        ->join('store_products_demand_inventory as spdi',function($join){$join->on('spdi.inventory_id','=','dni.item_id')->on('spdi.demand_id','=','dn.invoice_id')->where('spdi.is_deleted','=','0');})        
                        ->where('dn.debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand') 
                        ->wherein('dn.debit_note_status',['completed','cancelled'])                    
                        ->where('dn.is_deleted','0')
                        ->where('dni.is_deleted','0')        
                        ->groupByRaw('dn.id,spdi.store_gst_percent');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $debit_notes_list_gst_csv = $debit_notes_list_gst_csv->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");         
                        }
                        
                        $debit_notes_list_gst_csv = $debit_notes_list_gst_csv->selectRaw('dn.id,spdi.store_gst_percent,
                        COUNT(dni.id) as total_qty,SUM(spdi.store_base_rate) as total_taxable_value,SUM(spdi.store_gst_amount) as total_gst_amount')
                        ->get()->toArray();
                        
                        for($i=0;$i<count($debit_notes_list_gst_csv);$i++){
                            $gst_percent = str_replace('.00', '', $debit_notes_list_gst_csv[$i]->store_gst_percent);
                            $key = $debit_notes_list_gst_csv[$i]->id.'_'.$gst_percent;
                            $debit_notes_gst[$key] = $debit_notes_list_gst_csv[$i];
                            $debit_notes_gst_types[] = $gst_percent;
                        }
                       
                        $debit_notes_gst_types = array_values(array_unique($debit_notes_gst_types));
                        
                        $columns = array('Debit Note No','Invoice No','Debit Note Date','Status','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Debit Note From','Debit Note To','Debit Note From GST No','Debit Note To GST No');
                        
                        for($i=0;$i<count($debit_notes_gst_types);$i++){
                            $gst_percent = $debit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        $company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($debit_notes_list_csv);$i++){
                            
                            $company_data['company_gst_no'] = $debit_notes_list_csv[$i]->company_gst_no;
                            $company_data['company_name'] = $debit_notes_list_csv[$i]->company_gst_name;
                                    
                            $store_info = json_decode($debit_notes_list_csv[$i]->store_data,true);
                            $store_gst_no = $store_info['gst_no'];
                            
                            if($store_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($store_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $debit_notes_date = date('d-m-Y', strtotime($debit_notes_list_csv[$i]->created_at));
                            $array = array($debit_notes_list_csv[$i]->debit_note_no,$debit_notes_list_csv[$i]->demand_invoice_no,$debit_notes_date,
                            $debit_notes_list_csv[$i]->debit_note_status,$debit_notes_list_csv[$i]->total_qty,$debit_notes_list_csv[$i]->total_taxable_value);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($debit_notes_list_csv[$i]->total_gst_amount/2,2);
                                $array[] = round($debit_notes_list_csv[$i]->total_gst_amount/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($debit_notes_list_csv[$i]->total_gst_amount)/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($debit_notes_list_csv[$i]->total_gst_amount,2);
                                $total_data['i_gst']+=$debit_notes_list_csv[$i]->total_gst_amount;
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($debit_notes_list_csv[$i]->total_taxable_value+$debit_notes_list_csv[$i]->total_gst_amount,2);
                            $array[] = '';
                            $array[] = $company_data['company_name'];
                            $array[] = $store_info['gst_name'];
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $store_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($debit_notes_gst_types);$q++){
                                $gst_percent = $debit_notes_gst_types[$q];
                                $key = $debit_notes_list_csv[$i]->id.'_'.$gst_percent;
                                $array[] = $qty = isset($debit_notes_gst[$key]->total_qty)?$debit_notes_gst[$key]->total_qty:0;
                                $array[] = $total_taxable_value = isset($debit_notes_gst[$key]->total_taxable_value)?$debit_notes_gst[$key]->total_taxable_value:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount/2:0;
                                    $c_s_gst = isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount,2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                    $i_gst = isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount,2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$debit_notes_list_csv[$i]->total_qty;
                            $total_data['taxable_val']+=$debit_notes_list_csv[$i]->total_taxable_value;
                            $total_data['total_val']+=($debit_notes_list_csv[$i]->total_taxable_value+$debit_notes_list_csv[$i]->total_gst_amount);
                        }
                        
                        $array = array('Total','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($debit_notes_gst_types);$q++){
                            $gst_percent = $debit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    if($type_id == 5){
                        $debit_notes_gst = $debit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $debit_notes_list_csv = \DB::table('debit_notes as dn')
                        ->join('purchase_order_details as pod','dn.invoice_id', '=', 'pod.id')        
                        ->join('purchase_order as po','pod.po_id', '=', 'po.id')  
                        ->join('vendor_detail as vd','po.vendor_id', '=', 'vd.id')         
                        ->join('debit_note_items as dni','dni.debit_note_id', '=', 'dn.id')                        
                        ->where('dn.debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice') 
                        ->wherein('dn.debit_note_status',['completed','cancelled'])            
                        ->where('pod.is_deleted','0')        
                        ->where('dn.is_deleted','0')
                        ->where('dni.is_deleted','0')    
                        ->where('pod.fake_inventory',0)        
                        ->where('po.fake_inventory',0)                
                        ->groupBy('dn.id');           
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $debit_notes_list_csv = $debit_notes_list_csv->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");   
                        }
                        
                        $debit_notes_list_csv = $debit_notes_list_csv->selectRaw('dn.*,vd.name as vendor_name,vd.gst_no as vendor_gst_no,pod.invoice_no as pod_invoice_no,pod.invoice_date,
                        po.order_no as po_no,po.company_data,SUM(dni.item_qty) as total_qty,SUM(dni.item_qty*dni.base_rate) as total_taxable_value,SUM(dni.item_qty*dni.gst_amount) as total_gst_amount')
                        ->orderBy('dn.debit_note_no','ASC')
                        ->get()->toArray();
                        
                        $debit_notes_list_gst_csv = \DB::table('debit_notes as dn')
                        ->join('debit_note_items as dni','dni.debit_note_id', '=', 'dn.id')                
                        ->where('dn.debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice') 
                        ->wherein('dn.debit_note_status',['completed','cancelled'])                    
                        ->where('dn.is_deleted','0')
                        ->where('dni.is_deleted','0')        
                        ->groupByRaw('dn.id,dni.gst_percent');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $debit_notes_list_gst_csv = $debit_notes_list_gst_csv->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");      
                        }
                        
                        $debit_notes_list_gst_csv = $debit_notes_list_gst_csv->selectRaw('dn.id,dni.gst_percent,
                        SUM(dni.item_qty) as total_qty,SUM(dni.item_qty*dni.base_rate) as total_taxable_value,SUM(dni.item_qty*dni.gst_amount) as total_gst_amount')
                        ->get()->toArray();
                        
                        for($i=0;$i<count($debit_notes_list_gst_csv);$i++){
                            $gst_percent = str_replace('.00', '', $debit_notes_list_gst_csv[$i]->gst_percent);
                            $key = $debit_notes_list_gst_csv[$i]->id.'_'.$gst_percent;
                            $debit_notes_gst[$key] = $debit_notes_list_gst_csv[$i];
                            $debit_notes_gst_types[] = $gst_percent;
                        }
                       
                        $debit_notes_gst_types = array_values(array_unique($debit_notes_gst_types));
                        sort($debit_notes_gst_types);
                        
                        $columns = array('Debit Note No','Invoice No','PO No','Debit Note Date','Status','Invoice Date','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Debit Note From','Debit Note To','Debit Note From GST No','Debit Note To GST No');
                        
                        for($i=0;$i<count($debit_notes_gst_types);$i++){
                            $gst_percent = $debit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        //$company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($debit_notes_list_csv);$i++){
                            
                            $company_data = json_decode($debit_notes_list_csv[$i]->company_data,true);
                            
                            if($debit_notes_list_csv[$i]->vendor_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($debit_notes_list_csv[$i]->vendor_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $debit_notes_date = date('d-m-Y', strtotime($debit_notes_list_csv[$i]->created_at));
                            $invoice_date = date('d-m-Y', strtotime($debit_notes_list_csv[$i]->invoice_date));
                            $array = array($debit_notes_list_csv[$i]->debit_note_no,$debit_notes_list_csv[$i]->pod_invoice_no,$debit_notes_list_csv[$i]->po_no,
                            $debit_notes_date,$debit_notes_list_csv[$i]->debit_note_status,$invoice_date,$debit_notes_list_csv[$i]->total_qty,$debit_notes_list_csv[$i]->total_taxable_value);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($debit_notes_list_csv[$i]->total_gst_amount/2,2);
                                $array[] = round($debit_notes_list_csv[$i]->total_gst_amount/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($debit_notes_list_csv[$i]->total_gst_amount)/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($debit_notes_list_csv[$i]->total_gst_amount,2);
                                $total_data['i_gst']+=$debit_notes_list_csv[$i]->total_gst_amount;
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($debit_notes_list_csv[$i]->total_taxable_value+$debit_notes_list_csv[$i]->total_gst_amount,2);
                            $array[] = '';
                            $array[] = $company_data['company_name'];
                            $array[] = $debit_notes_list_csv[$i]->vendor_name;
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $debit_notes_list_csv[$i]->vendor_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($debit_notes_gst_types);$q++){
                                $gst_percent = $debit_notes_gst_types[$q];
                                $key = $debit_notes_list_csv[$i]->id.'_'.$gst_percent;
                                $array[] = $qty = isset($debit_notes_gst[$key]->total_qty)?$debit_notes_gst[$key]->total_qty:0;
                                $array[] = $total_taxable_value = isset($debit_notes_gst[$key]->total_taxable_value)?$debit_notes_gst[$key]->total_taxable_value:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount/2:0;
                                    $c_s_gst = isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount,2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                    $i_gst = isset($debit_notes_gst[$key]->total_gst_amount)?$debit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($debit_notes_gst[$key]->total_gst_amount)?round($debit_notes_gst[$key]->total_gst_amount,2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$debit_notes_list_csv[$i]->total_qty;
                            $total_data['taxable_val']+=$debit_notes_list_csv[$i]->total_taxable_value;
                            $total_data['total_val']+=($debit_notes_list_csv[$i]->total_taxable_value+$debit_notes_list_csv[$i]->total_gst_amount);
                        }
                        
                        $array = array('Total','','','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($debit_notes_gst_types);$q++){
                            $gst_percent = $debit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    if($type_id == 2){
                        $debit_notes_gst = $debit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $debit_notes_list_csv = \DB::table('store_products_demand as spd')
                        //->join('store_products_demand_inventory as spdi','spdi.demand_id', '=', 'spd.id')                
                        ->join('store as s','spd.store_id', '=', 's.id')                
                        ->where('spd.demand_type','inventory_return_to_warehouse')        
                        ->wherein('spd.demand_status',['warehouse_loaded','cancelled'])     
                        ->where('spd.fake_inventory',0)        
                        ->where('spd.is_deleted','0');             
                        //->where('spdi.is_deleted','0')        
                 
                        if(!empty($start_date) && !empty($end_date)){
                            $debit_notes_list_csv = $debit_notes_list_csv->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
                        }
                        
                        $debit_notes_list_csv = $debit_notes_list_csv->selectRaw('spd.*,s.store_name,s.gst_no as store_gst_no')
                        ->orderBy('spd.id','ASC')
                        ->get()->toArray();
                        
                        $debit_notes_gst_types = array(3,5,12,18,0);
                        $columns = array('Debit Note No','Invoice No','Debit Note Date','Status','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Debit Note From','Debit Note To','Debit Note From GST No','Debit Note To GST No');
                        
                        for($i=0;$i<count($debit_notes_gst_types);$i++){
                            $gst_percent = $debit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        $company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($debit_notes_list_csv);$i++){
                            
                            $company_data['company_gst_no'] = $debit_notes_list_csv[$i]->company_gst_no;
                            $company_data['company_name'] = $debit_notes_list_csv[$i]->company_gst_name;
                            
                            $total_info = json_decode($debit_notes_list_csv[$i]->total_data,true);
                            $store_info = json_decode($debit_notes_list_csv[$i]->store_data,true);
                            $store_gst_no = $store_info['gst_no'];
                            
                            if($store_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($store_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $debit_notes_date = date('d-m-Y', strtotime($debit_notes_list_csv[$i]->created_at));
                            $debit_note_status  = CommonHelper::getDemandStatusText('inventory_return_to_warehouse',$debit_notes_list_csv[$i]->demand_status);
                            
                            $array = array($debit_notes_list_csv[$i]->invoice_no,$debit_notes_list_csv[$i]->invoice_no,$debit_notes_date,
                            $debit_note_status,$total_info['total_qty'],$total_info['total_taxable_val']);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($total_info['total_gst_amt'])/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($total_info['total_gst_amt'],2);
                                $total_data['i_gst']+=$total_info['total_gst_amt'];
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($total_info['total_taxable_val']+$total_info['total_gst_amt'],2);
                            $array[] = '';
                            $array[] = $store_info['gst_name'];
                            $array[] = $company_data['company_name'];
                            $array[] = $store_gst_no;
                            $array[] = $company_data['company_gst_no'];
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($debit_notes_gst_types);$q++){
                                $gst_percent = $debit_notes_gst_types[$q];
                                
                                $array[] = $qty = isset($total_info['qty_'.$gst_percent])?$total_info['qty_'.$gst_percent]:0;
                                $array[] = $total_taxable_value = isset($total_info['taxable_value_'.$gst_percent])?$total_info['taxable_value_'.$gst_percent]:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]/2:0;
                                    $c_s_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                    $i_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$total_info['total_qty'];
                            $total_data['taxable_val']+=$total_info['total_taxable_val'];
                            $total_data['total_val']+=($total_info['total_taxable_val']+$total_info['total_gst_amt']);
                        }
                        
                        $array = array('Total','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($debit_notes_gst_types);$q++){
                            $gst_percent = $debit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                        
                    }
                    
                    if($type_id == 3){
                        $debit_notes_gst = $debit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $debit_notes_list_csv = \DB::table('store_products_demand as spd')
                        ->join('store as s','spd.store_id', '=', 's.id')     
                        ->leftJoin('store_products_demand as spd_1','spd_1.push_demand_id', '=', 'spd.id')        
                        ->where('spd.demand_type','inventory_return_complete')        
                        ->wherein('spd.demand_status',['store_loaded','cancelled'])       
                        ->where('spd.is_deleted','0')           
                        ->where('spd.fake_inventory',0)        
                        ->groupBy('spd.id');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $debit_notes_list_csv = $debit_notes_list_csv->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
                        }
                        
                        $debit_notes_list_csv = $debit_notes_list_csv->selectRaw('spd.*,s.store_name,s.gst_no as store_gst_no,spd_1.invoice_no as tax_invoice_no')
                        ->orderBy('spd.id','ASC')
                        ->get()->toArray();
                        
                        $debit_notes_gst_types = array(3,5,12,18,0);
                        $columns = array('Debit Note No','Invoice No','Debit Note Date','Status','Tax Invoice No','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Debit Note From','Debit Note To','Debit Note From GST No','Debit Note To GST No');
                        
                        for($i=0;$i<count($debit_notes_gst_types);$i++){
                            $gst_percent = $debit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        $company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($debit_notes_list_csv);$i++){
                            
                            $company_data['company_gst_no'] = $debit_notes_list_csv[$i]->company_gst_no;
                            $company_data['company_name'] = $debit_notes_list_csv[$i]->company_gst_name;
                            
                            $total_info = json_decode($debit_notes_list_csv[$i]->total_data,true);
                            $store_info = json_decode($debit_notes_list_csv[$i]->store_data,true);
                            $store_gst_no = $store_info['gst_no'];
                            
                            if($store_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($store_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $debit_notes_date = date('d-m-Y', strtotime($debit_notes_list_csv[$i]->created_at));
                            $debit_note_status = CommonHelper::getDemandStatusText('inventory_return_complete',$debit_notes_list[$i]->demand_status);
                            
                            $array = array($debit_notes_list_csv[$i]->invoice_no,$debit_notes_list_csv[$i]->invoice_no,$debit_notes_date,
                            $debit_note_status,$debit_notes_list_csv[$i]->tax_invoice_no,$total_info['total_qty'],$total_info['total_taxable_val']);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($total_info['total_gst_amt'])/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($total_info['total_gst_amt'],2);
                                $total_data['i_gst']+=$total_info['total_gst_amt'];
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($total_info['total_taxable_val']+$total_info['total_gst_amt'],2);
                            $array[] = '';
                            $array[] = $store_info['gst_name'];
                            $array[] = $company_data['company_name'];
                            $array[] = $store_gst_no;
                            $array[] = $company_data['company_gst_no'];
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($debit_notes_gst_types);$q++){
                                $gst_percent = $debit_notes_gst_types[$q];
                                //$key = $debit_notes_list_csv[$i]->id.'_'.$gst_percent;
                                $array[] = $qty = isset($total_info['qty_'.$gst_percent])?$total_info['qty_'.$gst_percent]:0;
                                $array[] = $total_taxable_value = isset($total_info['taxable_value_'.$gst_percent])?$total_info['taxable_value_'.$gst_percent]:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]/2:0;
                                    $c_s_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                    $i_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$total_info['total_qty'];
                            $total_data['taxable_val']+=$total_info['total_taxable_val'];
                            $total_data['total_val']+=($total_info['total_taxable_val']+$total_info['total_gst_amt']);
                        }
                        
                        $array = array('Total','','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($debit_notes_gst_types);$q++){
                            $gst_percent = $debit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    if($type_id == 4){
                        $debit_notes_gst = $debit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $debit_notes_list_csv = \DB::table('store_products_demand as spd')
                        ->join('vendor_detail as vd','vd.id', '=', 'spd.store_id')          
                        ->where('spd.demand_type','inventory_return_to_vendor')
                        ->wherein('spd.demand_status',['warehouse_dispatched','cancelled'])       
                        ->where('spd.fake_inventory',0)        
                        ->where('spd.is_deleted','0');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $debit_notes_list_csv = $debit_notes_list_csv->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
                        }
                        
                        $debit_notes_list_csv = $debit_notes_list_csv->selectRaw('spd.*,vd.name as vendor_name,vd.gst_no as vendor_gst_no')
                        ->orderBy('spd.id','ASC')
                        ->get()->toArray();
                        
                        $debit_notes_gst_types = array(3,5,12,18,0);
                        $columns = array('Debit Note No','Debit Note Date','Status','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Debit Note From','Debit Note To','Debit Note From GST No','Debit Note To GST No');
                        
                        for($i=0;$i<count($debit_notes_gst_types);$i++){
                            $gst_percent = $debit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        $company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($debit_notes_list_csv);$i++){
                            
                            $company_data['company_gst_no'] = $debit_notes_list_csv[$i]->company_gst_no;
                            $company_data['company_name'] = $debit_notes_list_csv[$i]->company_gst_name;
                            
                            $total_info = json_decode($debit_notes_list_csv[$i]->total_data,true);
                            
                            if($debit_notes_list_csv[$i]->vendor_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($debit_notes_list_csv[$i]->vendor_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $debit_notes_date = date('d-m-Y', strtotime($debit_notes_list_csv[$i]->created_at));
                            $debit_note_status = CommonHelper::getDemandStatusText('inventory_return_to_vendor',$debit_notes_list[$i]->demand_status);
                            
                            $array = array($debit_notes_list_csv[$i]->invoice_no,$debit_notes_date,$debit_note_status,
                            $total_info['total_qty'],$total_info['total_taxable_val']);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($total_info['total_gst_amt'])/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($total_info['total_gst_amt'],2);
                                $total_data['i_gst']+=$total_info['total_gst_amt'];
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($total_info['total_taxable_val']+$total_info['total_gst_amt'],2);
                            $array[] = '';
                            $array[] = $company_data['company_name'];
                            $array[] = $debit_notes_list_csv[$i]->vendor_name;
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $debit_notes_list_csv[$i]->vendor_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($debit_notes_gst_types);$q++){
                                $gst_percent = $debit_notes_gst_types[$q];
                                //$key = $debit_notes_list_csv[$i]->id.'_'.$gst_percent;
                                $array[] = $qty = isset($total_info['qty_'.$gst_percent])?$total_info['qty_'.$gst_percent]:0;
                                $array[] = $total_taxable_value = isset($total_info['taxable_value_'.$gst_percent])?$total_info['taxable_value_'.$gst_percent]:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]/2:0;
                                    $c_s_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                    $i_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$total_info['total_qty'];
                            $total_data['taxable_val']+=$total_info['total_taxable_val'];
                            $total_data['total_val']+=($total_info['total_taxable_val']+$total_info['total_gst_amt']);
                        }
                        
                        $array = array('Total','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($debit_notes_gst_types);$q++){
                            $gst_percent = $debit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            return view('admin/debit_notes_list',array('debit_notes_list'=>$debit_notes_list,'debit_note_types'=>$debit_note_types,'user'=>$user,'type_id'=>$type_id,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return view('admin/debit_notes_list',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function creditNotesList(Request $request){
        try{
            $data = $request->all();
            $start_date = $end_date = '';
            $credit_notes_list = $credit_note_data = array();
            $type_id = (isset($data['type_id']) && !empty($data['type_id']))?$data['type_id']:1;
            
            $credit_note_no = (isset($data['credit_note_no']) && !empty($data['credit_note_no']))?$data['credit_note_no']:'';
            $credit_note_id = (isset($data['credit_note_id']) && !empty($data['credit_note_id']))?$data['credit_note_id']:'';
            $action = (isset($data['action']) && !empty($data['action']))?trim($data['action']):'';
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            $credit_note_types = array('1'=>'Defective Inventory Returned from Warehouse to Vendor in PO Invoice QC',
            '4'=>'SOR Stock Return from Warehouse to Vendor',
            '6'=>'Less Inventory from Warehouse to Store in Push Demand',
            '5'=>'Less Inventory from Vendor to Warehouse in PO Invoice',
            '7'=>'Excess Amount from Vendor to Warehouse in PO Invoice',        
            '2'=>'SOR Stock Return from Store to Warehouse',
            '3'=>'Complete Stock Return from Store to Warehouse');
            
            $user = Auth::user();
            // If user is logged as vendor role
            if($user->user_type == 15){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->first();
            }
            
            if($type_id == 1){
                $credit_notes_list = \DB::table('purchase_order_grn_qc as po_grn_qc')
                ->join('purchase_order as po','po.id', '=', 'po_grn_qc.po_id')        
                ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')
                ->where('po_grn_qc.type','qc_return');

                // Fetch only vendor records
                if($user->user_type == 15){
                    $credit_notes_list = $credit_notes_list->where('po.vendor_id',$vendor_data->id);
                }
                
                if(!empty($start_date) && !empty($end_date)){
                    $credit_notes_list = $credit_notes_list->whereRaw("DATE(po_grn_qc.created_at) >= '$start_date' AND DATE(po_grn_qc.created_at) <= '$end_date'");        
                }
                
                if(isset($data['id']) && !empty($data['id'])){
                    $credit_notes_list = $credit_notes_list->where('po_grn_qc.id',trim($data['id']));
                }

                $credit_notes_list = $credit_notes_list->where('po_grn_qc.is_deleted',0)        
                ->where('po_grn_qc.fake_inventory',0)     
                ->where('po.fake_inventory',0)         
                ->where('pod.fake_inventory',0)                
                ->select('po_grn_qc.*','po.order_no','pod.invoice_no')
                ->orderBy('po_grn_qc.id','DESC');
                
                if($action == 'search_credit_note_no' && !empty($credit_note_no)){
                    $credit_note = clone ($credit_notes_list);
                    $credit_note = $credit_note->where('po_grn_qc.credit_note_no',$credit_note_no)->first();
                    if(!empty($credit_note)){
                        $credit_note_data = ['credit_note_no'=>$credit_note->grn_no,'credit_note_id'=>$credit_note->id,'type_id'=>1];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','credit_note_data'=>$credit_note_data),200);
                    }
                }else{
                    if(!empty($credit_note_id)){
                        $credit_notes_list = $credit_notes_list->where('po_grn_qc.credit_note_no',$credit_note_no);
                    }

                    $credit_notes_list = $credit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 2 || $action == 'search_credit_note_no'){
                $credit_notes_list = \DB::table('store_products_demand as spd1')
                //->join('store_products_demand as spd2','spd1.push_demand_id', '=', 'spd2.id')        
                ->leftJoin('store_products_demand as spd2',function($join){$join->on('spd1.push_demand_id','=','spd2.id')->where('spd2.is_deleted','=',0);})                     
                ->join('store as s','spd1.store_id', '=', 's.id')        
                ->where('spd1.demand_type','inventory_return_to_warehouse')        
                ->wherein('spd1.demand_status',['warehouse_loaded','cancelled'])        
                ->where('spd1.is_deleted','0')
                //->where('spd2.is_deleted','0')
                ->where('s.is_deleted','0')        
                ->where('spd1.fake_inventory',0);      
                
                if(!empty($start_date) && !empty($end_date)){
                    $credit_notes_list = $credit_notes_list->whereRaw("DATE(spd1.created_at) >= '$start_date' AND DATE(spd1.created_at) <= '$end_date'");        
                }    
                
                if(isset($data['id']) && !empty($data['id'])){
                    $credit_notes_list = $credit_notes_list->where('spd1.id',trim($data['id']));
                }
                
                $credit_notes_list = $credit_notes_list->select('spd1.*','spd2.invoice_no as base_demand_invoice_no','s.store_name','s.store_id_code')
                ->orderBy('spd1.id','DESC');
                
                if($action == 'search_credit_note_no' && !empty($credit_note_no)){
                    $credit_note = clone ($credit_notes_list);
                    $credit_note = $credit_note->where('spd1.credit_invoice_no',$credit_note_no)->first();
                    
                    if(!empty($credit_note)){
                        $credit_note_data = ['credit_note_no'=>$credit_note->invoice_no,'credit_note_id'=>$credit_note->id,'type_id'=>2];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','credit_note_data'=>$credit_note_data),200);
                    }
                }else{
                    if(!empty($credit_note_no)){
                        $credit_notes_list = $credit_notes_list->where('spd1.credit_invoice_no',$credit_note_no);
                    }

                    $credit_notes_list = $credit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 3 || $action == 'search_credit_note_no' ){
                $credit_notes_list = \DB::table('store_products_demand as spd1')
                ->join('store as s','spd1.store_id', '=', 's.id')        
                ->where('spd1.demand_type','inventory_return_complete')        
                ->wherein('spd1.demand_status',['store_loaded','cancelled'])        
                ->where('spd1.is_deleted','0')
                ->where('s.is_deleted','0')         
                ->where('spd1.fake_inventory',0);          
                
                if(!empty($start_date) && !empty($end_date)){
                    $credit_notes_list = $credit_notes_list->whereRaw("DATE(spd1.created_at) >= '$start_date' AND DATE(spd1.created_at) <= '$end_date'");        
                } 
                
                if(isset($data['id']) && !empty($data['id'])){
                    $credit_notes_list = $credit_notes_list->where('spd1.id',trim($data['id']));
                }
                
                $credit_notes_list = $credit_notes_list->select('spd1.*','s.store_name','s.store_id_code')
                ->orderBy('spd1.id','DESC');
                
                if($action == 'search_credit_note_no' && !empty($credit_note_no)){
                    $credit_note = clone ($credit_notes_list);
                    $credit_note = $credit_note->where('spd1.credit_invoice_no',$credit_note_no)->first();
                    
                    if(!empty($credit_note)){
                        $credit_note_data = ['credit_note_no'=>$credit_note->invoice_no,'credit_note_id'=>$credit_note->id,'type_id'=>3];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','credit_note_data'=>$credit_note_data),200);
                    }
                }else{
                    if(!empty($credit_note_no)){
                        $credit_notes_list = $credit_notes_list->where('spd1.credit_invoice_no',$credit_note_no);
                    }

                    $credit_notes_list = $credit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 4 || $action == 'search_credit_note_no'){
                $credit_notes_list = \DB::table('store_products_demand as spd')
                //->join('purchase_order_details as pod','pod.id', '=', 'spd.push_demand_id')                
                //->join('purchase_order as po','po.id', '=', 'pod.po_id')         
                ->join('vendor_detail as vd','vd.id', '=', 'spd.store_id')          
                ->join('users as u','u.id', '=', 'spd.user_id')            
                ->where('spd.demand_type','inventory_return_to_vendor')
                ->wherein('spd.demand_status',['warehouse_dispatched','cancelled'])        
                ->where('spd.status',1)
                ->where('spd.is_deleted',0)
                //->where('pod.is_deleted',0)   
                ->where('spd.fake_inventory',0)            
                //->where('pod.fake_inventory',0) 
                //->where('po.fake_inventory',0)         
                ->where('vd.is_deleted',0);
                
                if($user->user_type == 15){
                    $credit_notes_list = $credit_notes_list->where('demand_status','warehouse_dispatched')
                    ->where('spd.store_id',$vendor_data->id);   // vendor id is stored in store_id column
                }                
                
                if(!empty($start_date) && !empty($end_date)){
                    $credit_notes_list = $credit_notes_list->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
                }     
                
                if(isset($data['id']) && !empty($data['id'])){
                    $credit_notes_list = $credit_notes_list->where('spd.id',trim($data['id']));
                }
                
                $credit_notes_list = $credit_notes_list->select('spd.*','u.name as user_name','vd.name as vendor_name')
                ->orderBy('spd.id','DESC');
                
                if($action == 'search_credit_note_no' && !empty($credit_note_no)){
                    $credit_note = clone ($credit_notes_list);
                    $credit_note = $credit_note->where('spd.credit_invoice_no',$credit_note_no)->first();
                    
                    if(!empty($credit_note)){
                        $credit_note_data = ['credit_note_no'=>$credit_note->invoice_no,'credit_note_id'=>$credit_note->id,'type_id'=>4];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','credit_note_data'=>$credit_note_data),200);
                    }
                }else{
                    if(!empty($credit_note_no)){
                        $credit_notes_list = $credit_notes_list->where('spd.credit_invoice_no',$credit_note_no);
                    }

                    $credit_notes_list = $credit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 5 || $action == 'search_credit_note_no'){
                $credit_notes_list = \DB::table('debit_notes as dn')
                ->join('purchase_order_details as pod','dn.invoice_id', '=', 'pod.id')        
                ->join('purchase_order as po','pod.po_id', '=', 'po.id')  
                ->join('vendor_detail as vd','po.vendor_id', '=', 'vd.id')           
                ->where('dn.debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice') 
                ->wherein('dn.debit_note_status',['completed','cancelled'])          
                ->where('dn.is_deleted','0')
                ->where('pod.fake_inventory',0)        
                ->where('po.fake_inventory',0)                
                ->select('dn.*','po.order_no','vd.name as vendor_name','pod.invoice_no','pod.invoice_date');
                 
                if($user->user_type == 15){
                    $credit_notes_list = $credit_notes_list->where('po.vendor_id',$vendor_data->id);
                }
                
                if(!empty($start_date) && !empty($end_date)){
                    $credit_notes_list = $credit_notes_list->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");      
                }
                
                if(isset($data['id']) && !empty($data['id'])){
                    $credit_notes_list = $credit_notes_list->where('dn.id',trim($data['id']));
                }
                
                $credit_notes_list = $credit_notes_list->orderBy('dn.credit_note_no','DESC');
                
                if($action == 'search_credit_note_no' && !empty($credit_note_no)){
                    $credit_note = clone ($credit_notes_list);
                    $credit_note = $credit_note->where('dn.credit_note_no',$credit_note_no)->first();
                    
                    if(!empty($credit_note)){
                        $credit_note_data = ['credit_note_no'=>$credit_note->credit_note_no,'credit_note_id'=>$credit_note->id,'type_id'=>5];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','credit_note_data'=>$credit_note_data),200);
                    }
                }else{
                    if(!empty($credit_note_no)){
                        $credit_notes_list = $credit_notes_list->where('dn.credit_note_no',$credit_note_no);
                    }

                    $credit_notes_list = $credit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 6 || $action == 'search_credit_note_no'){
                $credit_notes_list = \DB::table('debit_notes as dn')
                ->join('store_products_demand as spd','spd.id', '=', 'dn.invoice_id')                        
                ->join('store as s','spd.store_id', '=', 's.id')                
                ->where('dn.debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand') 
                ->wherein('dn.debit_note_status',['completed','cancelled'])          
                ->where('dn.is_deleted','0')
                ->where('spd.fake_inventory',0);      
                
                if(!empty($start_date) && !empty($end_date)){
                    $credit_notes_list = $credit_notes_list->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");       
                }
                
                if(isset($data['id']) && !empty($data['id'])){
                    $credit_notes_list = $credit_notes_list->where('dn.id',trim($data['id']));
                }
                
                $credit_notes_list = $credit_notes_list->select('dn.*','s.store_name','spd.invoice_no as demand_invoice_no','s.store_id_code')
                ->orderBy('dn.id','DESC');
                
                if($action == 'search_credit_note_no' && !empty($credit_note_no)){
                    $credit_note = clone ($credit_notes_list);
                    $credit_note = $credit_note->where('dn.credit_note_no',$credit_note_no)->first();
                    
                    if(!empty($credit_note)){
                        $credit_note_data = ['credit_note_no'=>$credit_note->credit_note_no,'credit_note_id'=>$credit_note->id,'type_id'=>6];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','credit_note_data'=>$credit_note_data),200);
                    }
                }else{
                    if(!empty($credit_note_no)){
                        $credit_notes_list = $credit_notes_list->where('dn.credit_note_no',$credit_note_no);
                    }

                    $credit_notes_list = $credit_notes_list->paginate(100);
                }
            }
            
            if($type_id == 7 || $action == 'search_credit_note_no'){
                $credit_notes_list = \DB::table('debit_notes as dn')
                ->join('purchase_order as po','po.id', '=', 'dn.po_id')        
                ->join('purchase_order_details as pod','pod.id', '=', 'dn.invoice_id')
                ->where('dn.debit_note_type','excess_amount')
                ->wherein('dn.debit_note_status',['completed','cancelled'])          
                ->where('dn.is_deleted',0)        
                ->where('po.fake_inventory',0) 
                ->where('pod.fake_inventory',0);       
                
                if(!empty($start_date) && !empty($end_date)){
                    $credit_notes_list = $credit_notes_list->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");       
                }
                
                if(isset($data['id']) && !empty($data['id'])){
                    $credit_notes_list = $credit_notes_list->where('dn.id',trim($data['id']));
                }
                
                $credit_notes_list = $credit_notes_list->select('dn.*','po.order_no','pod.invoice_no')
                ->orderBy('dn.id','DESC');
                
                if($action == 'search_credit_note_no' && !empty($credit_note_no)){
                    $credit_note = clone ($credit_notes_list);
                    $credit_note = $credit_note->where('dn.credit_note_no',$credit_note_no)->first();
                    
                    if(!empty($credit_note)){
                        $credit_note_data = ['credit_note_no'=>$credit_note->credit_note_no,'credit_note_id'=>$credit_note->id,'type_id'=>7];
                        return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','credit_note_data'=>$credit_note_data),200);
                    }
                }else{
                    if(!empty($credit_note_no)){
                        $credit_notes_list = $credit_notes_list->where('dn.credit_note_no',$credit_note_no);
                    }

                    $credit_notes_list = $credit_notes_list->paginate(100);
                }
            }
            
            if($action == 'search_credit_note_no' && empty($credit_note_data)){
                return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'fail','message'=>$credit_note_no.' - Credit Note does not exists','errors'=>$credit_note_no.' - Credit Note does not exists'),200);
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $file_name = str_replace(' ','_',strtolower($credit_note_types[$type_id]));
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename='.$file_name.'.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                
                $callback = function() use ($credit_notes_list,$type_id,$start_date,$end_date){
                    $file = fopen('php://output', 'w');
                    
                    if($type_id == 1){
                        $credit_notes_gst = $credit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $qc_return_inv_list = \DB::table('purchase_order_grn_qc_items as po_grn_qc_items')
                        ->join('pos_product_master_inventory as ppmi','po_grn_qc_items.inventory_id', '=', 'ppmi.id')    
                        ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')
                        ->join('purchase_order as po','po.id', '=', 'po_grn_qc.po_id')        
                        ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')                
                        ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')        
                        ->join('purchase_order_grn_qc as po_grn_qc_1',function($join){$join->on('po_grn_qc.po_detail_id','=','po_grn_qc_1.po_detail_id')->where('po_grn_qc_1.type','=','grn')->where('po_grn_qc_1.is_deleted','=','0');})        
                        ->where('po_grn_qc.type','qc_return')        
                        ->where('po_grn_qc.is_deleted',0)        
                        ->where('po_grn_qc_items.is_deleted',0) 
                        ->where('ppmi.is_deleted',0)
                        ->where('pod.is_deleted',0)       
                        ->where('po_grn_qc_items.fake_inventory',0)           
                        ->where('ppmi.fake_inventory',0)           
                        ->where('po_grn_qc.fake_inventory',0)           
                        ->where('po.fake_inventory',0)                   
                        ->where('pod.fake_inventory',0)             
                        ->groupBy('po_grn_qc.id');
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $qc_return_inv_list = $qc_return_inv_list->whereRaw("DATE(po_grn_qc.created_at) >= '$start_date' AND DATE(po_grn_qc.created_at) <= '$end_date'");        
                        }
                
                        $qc_return_inv_list = $qc_return_inv_list->selectRaw('vd.name as vendor_name,vd.gst_no as vendor_gst_no,pod.invoice_no,pod.invoice_date,po.id as po_id,po_grn_qc.credit_note_no,po_grn_qc.po_detail_id,po_grn_qc.created_at as qc_return_date,
                        po_grn_qc.comments,po.order_no as po_no,po_grn_qc.id as po_grn_qc_id,po.company_data,COUNT(po_grn_qc_items.id) as total_qty,SUM(ppmi.vendor_base_price) as total_taxable_value,SUM(ppmi.vendor_gst_amount) as total_gst_amount')        
                        ->orderBy('po_grn_qc.id')
                        ->get()->toArray();
                        
                        $qc_return_inv_list_gst = \DB::table('purchase_order_grn_qc_items as po_grn_qc_items')
                        ->join('pos_product_master_inventory as ppmi','po_grn_qc_items.inventory_id', '=', 'ppmi.id')    
                        ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')
                        ->where('po_grn_qc.type','qc_return')        
                        ->where('po_grn_qc.is_deleted',0)        
                        ->where('po_grn_qc_items.is_deleted',0) 
                        ->where('ppmi.is_deleted',0)        
                        ->where('po_grn_qc_items.fake_inventory',0)   
                        ->where('ppmi.fake_inventory',0)   
                        ->where('po_grn_qc.fake_inventory',0)           
                        ->groupByRaw('po_grn_qc.id,ppmi.vendor_gst_percent'); 
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $qc_return_inv_list_gst = $qc_return_inv_list_gst->whereRaw("DATE(po_grn_qc.created_at) >= '$start_date' AND DATE(po_grn_qc.created_at) <= '$end_date'");        
                        }
                
                        $qc_return_inv_list_gst = $qc_return_inv_list_gst->selectRaw('po_grn_qc.id,ppmi.vendor_gst_percent,COUNT(po_grn_qc_items.id) as total_qty,SUM(ppmi.vendor_base_price) as total_taxable_value,SUM(ppmi.vendor_gst_amount) as total_gst_amount')        
                        ->get()->toArray();
                        
                        for($i=0;$i<count($qc_return_inv_list_gst);$i++){
                            $gst_percent = str_replace('.00', '', $qc_return_inv_list_gst[$i]->vendor_gst_percent);
                            $key = $qc_return_inv_list_gst[$i]->id.'_'.$gst_percent;
                            $credit_notes_gst[$key] = $qc_return_inv_list_gst[$i];
                            $credit_notes_gst_types[] = $gst_percent;
                        }
                        
                        $credit_notes_gst_types = array_values(array_unique($credit_notes_gst_types));
                        
                        $columns = array('Credit Note No','Invoice No','PO No','GRN No','Credit Note Date','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Credit Note From','Credit Note To','Credit Note From GST No','Credit Note To GST No');
                        
                        for($i=0;$i<count($credit_notes_gst_types);$i++){
                            $gst_percent = $credit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        //$company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($qc_return_inv_list);$i++){
                            
                            $company_data = json_decode($qc_return_inv_list[$i]->company_data,true);
                            
                            if($qc_return_inv_list[$i]->vendor_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($qc_return_inv_list[$i]->vendor_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $qc_return_date = date('d-m-Y', strtotime($qc_return_inv_list[$i]->qc_return_date));
                            $array = array($qc_return_inv_list[$i]->credit_note_no,$qc_return_inv_list[$i]->invoice_no,$qc_return_inv_list[$i]->po_no,'',$qc_return_date,
                            $qc_return_inv_list[$i]->total_qty,$qc_return_inv_list[$i]->total_taxable_value);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($qc_return_inv_list[$i]->total_gst_amount/2,2);
                                $array[] = round($qc_return_inv_list[$i]->total_gst_amount/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($qc_return_inv_list[$i]->total_gst_amount)/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($qc_return_inv_list[$i]->total_gst_amount,2);
                                $total_data['i_gst']+=$qc_return_inv_list[$i]->total_gst_amount;
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($qc_return_inv_list[$i]->total_taxable_value+$qc_return_inv_list[$i]->total_gst_amount,2);
                            $array[] = $qc_return_inv_list[$i]->comments;
                            $array[] = $company_data['company_name'];
                            $array[] = $qc_return_inv_list[$i]->vendor_name;
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $qc_return_inv_list[$i]->vendor_gst_no;
                            
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($credit_notes_gst_types);$q++){
                                $gst_percent = $credit_notes_gst_types[$q];
                                $key = $qc_return_inv_list[$i]->po_grn_qc_id.'_'.$gst_percent;
                                $array[] = $qty = isset($credit_notes_gst[$key]->total_qty)?$credit_notes_gst[$key]->total_qty:0;
                                $array[] = $total_taxable_value = isset($credit_notes_gst[$key]->total_taxable_value)?$credit_notes_gst[$key]->total_taxable_value:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount/2:0;
                                    $c_s_gst = isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount,2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                    $i_gst = isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount,2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$qc_return_inv_list[$i]->total_qty;
                            $total_data['taxable_val']+=$qc_return_inv_list[$i]->total_taxable_value;
                            $total_data['total_val']+=($qc_return_inv_list[$i]->total_taxable_value+$qc_return_inv_list[$i]->total_gst_amount);
                        }
                        
                        $array = array('Total','','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($credit_notes_gst_types);$q++){
                            $gst_percent = $credit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    if($type_id == 6){
                        $credit_notes_gst = $credit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $credit_notes_list_csv = \DB::table('debit_notes as dn')
                        ->join('store_products_demand as spd','spd.id', '=', 'dn.invoice_id')          
                        ->join('debit_note_items as dni','dni.debit_note_id', '=', 'dn.id')                
                        ->join('store_products_demand_inventory as spdi',function($join){$join->on('spdi.inventory_id','=','dni.item_id')->on('spdi.demand_id','=','dn.invoice_id')->where('spdi.is_deleted','=','0');})        
                        ->join('store as s','spd.store_id', '=', 's.id')                
                        ->where('dn.debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand') 
                        ->wherein('dn.debit_note_status',['completed','cancelled'])                  
                        ->where('dn.is_deleted','0')
                        ->where('dni.is_deleted','0')        
                        ->where('spd.fake_inventory',0)         
                        ->groupBy('dn.id');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $credit_notes_list_csv = $credit_notes_list_csv->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");      
                        }
                        
                        $credit_notes_list_csv = $credit_notes_list_csv->selectRaw('dn.*,s.store_name,s.store_id_code,s.gst_no as store_gst_no,spd.invoice_no as demand_invoice_no,
                        spdi.store_base_rate,spdi.store_gst_percent,spdi.store_gst_amount,spdi.store_base_price,spd.company_gst_no,spd.company_gst_name,
                        spd.store_data,COUNT(dni.id) as total_qty,SUM(spdi.store_base_rate) as total_taxable_value,SUM(spdi.store_gst_amount) as total_gst_amount')
                        ->orderBy('dn.id','ASC')
                        ->get()->toArray();
                        
                        $credit_notes_list_gst_csv = \DB::table('debit_notes as dn')
                        ->join('debit_note_items as dni','dni.debit_note_id', '=', 'dn.id')                
                        ->join('store_products_demand_inventory as spdi',function($join){$join->on('spdi.inventory_id','=','dni.item_id')->on('spdi.demand_id','=','dn.invoice_id')->where('spdi.is_deleted','=','0');})        
                        ->where('dn.debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand') 
                        ->wherein('dn.debit_note_status',['completed','cancelled'])            
                        ->where('dn.is_deleted','0')
                        ->where('dni.is_deleted','0')        
                        ->groupByRaw('dn.id,spdi.store_gst_percent');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $credit_notes_list_gst_csv = $credit_notes_list_gst_csv->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");      
                        }
                        
                        $credit_notes_list_gst_csv = $credit_notes_list_gst_csv->selectRaw('dn.id,spdi.store_gst_percent,
                        COUNT(dni.id) as total_qty,SUM(spdi.store_base_rate) as total_taxable_value,SUM(spdi.store_gst_amount) as total_gst_amount')
                        ->get()->toArray();
                        
                        for($i=0;$i<count($credit_notes_list_gst_csv);$i++){
                            $gst_percent = str_replace('.00', '', $credit_notes_list_gst_csv[$i]->store_gst_percent);
                            $key = $credit_notes_list_gst_csv[$i]->id.'_'.$gst_percent;
                            $credit_notes_gst[$key] = $credit_notes_list_gst_csv[$i];
                            $credit_notes_gst_types[] = $gst_percent;
                        }
                       
                        $credit_notes_gst_types = array_values(array_unique($credit_notes_gst_types));
                        
                        $columns = array('Credit Note No','Invoice No','Credit Note Date','Status','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Credit Note From','Credit Note To','Credit Note From GST No','Credit Note To GST No');
                        
                        for($i=0;$i<count($credit_notes_gst_types);$i++){
                            $gst_percent = $credit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        $company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($credit_notes_list_csv);$i++){
                            
                            $company_data['company_gst_no'] = $credit_notes_list_csv[$i]->company_gst_no;
                            $company_data['company_name'] = $credit_notes_list_csv[$i]->company_gst_name;
                            
                            $store_info = json_decode($credit_notes_list_csv[$i]->store_data,true);
                            $store_gst_no = $store_info['gst_no'];
                            
                            if($store_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($store_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $credit_notes_date = date('d-m-Y', strtotime($credit_notes_list_csv[$i]->created_at));
                            $array = array($credit_notes_list_csv[$i]->credit_note_no,$credit_notes_list_csv[$i]->demand_invoice_no,$credit_notes_date,
                            $credit_notes_list_csv[$i]->debit_note_status,$credit_notes_list_csv[$i]->total_qty,$credit_notes_list_csv[$i]->total_taxable_value);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($credit_notes_list_csv[$i]->total_gst_amount/2,2);
                                $array[] = round($credit_notes_list_csv[$i]->total_gst_amount/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($credit_notes_list_csv[$i]->total_gst_amount)/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($credit_notes_list_csv[$i]->total_gst_amount,2);
                                $total_data['i_gst']+=$credit_notes_list_csv[$i]->total_gst_amount;
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($credit_notes_list_csv[$i]->total_taxable_value+$credit_notes_list_csv[$i]->total_gst_amount,2);
                            $array[] = '';
                            $array[] = $company_data['company_name'];
                            $array[] = $store_info['gst_name'];
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $store_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($credit_notes_gst_types);$q++){
                                $gst_percent = $credit_notes_gst_types[$q];
                                $key = $credit_notes_list_csv[$i]->id.'_'.$gst_percent;
                                $array[] = $qty = isset($credit_notes_gst[$key]->total_qty)?$credit_notes_gst[$key]->total_qty:0;
                                $array[] = $total_taxable_value = isset($credit_notes_gst[$key]->total_taxable_value)?$credit_notes_gst[$key]->total_taxable_value:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount/2:0;
                                    $c_s_gst = isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount,2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                    $i_gst = isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount,2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$credit_notes_list_csv[$i]->total_qty;
                            $total_data['taxable_val']+=$credit_notes_list_csv[$i]->total_taxable_value;
                            $total_data['total_val']+=($credit_notes_list_csv[$i]->total_taxable_value+$credit_notes_list_csv[$i]->total_gst_amount);
                        }
                        
                        $array = array('Total','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($credit_notes_gst_types);$q++){
                            $gst_percent = $credit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    if($type_id == 5){
                        $credit_notes_gst = $credit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $credit_notes_list_csv = \DB::table('debit_notes as dn')
                        ->join('purchase_order_details as pod','dn.invoice_id', '=', 'pod.id')        
                        ->join('purchase_order as po','pod.po_id', '=', 'po.id')  
                        ->join('vendor_detail as vd','po.vendor_id', '=', 'vd.id')         
                        ->join('debit_note_items as dni','dni.debit_note_id', '=', 'dn.id')                        
                        ->where('dn.debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice') 
                        ->wherein('dn.debit_note_status',['completed','cancelled'])                  
                        ->where('pod.is_deleted','0')        
                        ->where('dn.is_deleted','0')
                        ->where('dni.is_deleted','0')    
                        ->where('pod.fake_inventory',0)        
                        ->where('po.fake_inventory',0)                
                        ->groupBy('dn.id');           
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $credit_notes_list_csv = $credit_notes_list_csv->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");        
                        }
                        
                        $credit_notes_list_csv = $credit_notes_list_csv->selectRaw('dn.*,vd.name as vendor_name,vd.gst_no as vendor_gst_no,pod.invoice_no as pod_invoice_no,pod.invoice_date,
                        po.order_no as po_no,po.company_data,SUM(dni.item_qty) as total_qty,SUM(dni.item_qty*dni.base_rate) as total_taxable_value,SUM(dni.item_qty*dni.gst_amount) as total_gst_amount')
                        ->orderBy('dn.debit_note_no','ASC')
                        ->get()->toArray();
                        
                        $credit_notes_list_gst_csv = \DB::table('debit_notes as dn')
                        ->join('debit_note_items as dni','dni.debit_note_id', '=', 'dn.id')                
                        ->where('dn.debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice') 
                        ->wherein('dn.debit_note_status',['completed','cancelled'])                    
                        ->where('dn.is_deleted','0')
                        ->where('dni.is_deleted','0')        
                        ->groupByRaw('dn.id,dni.gst_percent');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $credit_notes_list_gst_csv = $credit_notes_list_gst_csv->whereRaw("DATE(dn.created_at) >= '$start_date' AND DATE(dn.created_at) <= '$end_date'");       
                        }
                        
                        $credit_notes_list_gst_csv = $credit_notes_list_gst_csv->selectRaw('dn.id,dni.gst_percent,
                        SUM(dni.item_qty) as total_qty,SUM(dni.item_qty*dni.base_rate) as total_taxable_value,SUM(dni.item_qty*dni.gst_amount) as total_gst_amount')
                        ->get()->toArray();
                        
                        for($i=0;$i<count($credit_notes_list_gst_csv);$i++){
                            $gst_percent = str_replace('.00', '', $credit_notes_list_gst_csv[$i]->gst_percent);
                            $key = $credit_notes_list_gst_csv[$i]->id.'_'.$gst_percent;
                            $credit_notes_gst[$key] = $credit_notes_list_gst_csv[$i];
                            $credit_notes_gst_types[] = $gst_percent;
                        }
                       
                        $credit_notes_gst_types = array_values(array_unique($credit_notes_gst_types));
                        sort($credit_notes_gst_types);
                        
                        $columns = array('Credit Note No','Invoice No','PO No','Credit Note Date','Invoice Date','Status','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Credit Note From','Credit Note To','Credit Note From GST No','Credit Note To GST No');
                        
                        for($i=0;$i<count($credit_notes_gst_types);$i++){
                            $gst_percent = $credit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        //$company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($credit_notes_list_csv);$i++){
                            
                            $company_data = json_decode($credit_notes_list_csv[$i]->company_data,true);
                            
                            if($credit_notes_list_csv[$i]->vendor_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($credit_notes_list_csv[$i]->vendor_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $credit_notes_date = date('d-m-Y', strtotime($credit_notes_list_csv[$i]->created_at));
                            $invoice_date = date('d-m-Y', strtotime($credit_notes_list_csv[$i]->invoice_date));
                            $array = array($credit_notes_list_csv[$i]->credit_note_no,$credit_notes_list_csv[$i]->pod_invoice_no,$credit_notes_list_csv[$i]->po_no,
                            $credit_notes_date,$invoice_date,$credit_notes_list_csv[$i]->debit_note_status,$credit_notes_list_csv[$i]->total_qty,$credit_notes_list_csv[$i]->total_taxable_value);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($credit_notes_list_csv[$i]->total_gst_amount/2,2);
                                $array[] = round($credit_notes_list_csv[$i]->total_gst_amount/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($credit_notes_list_csv[$i]->total_gst_amount)/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($credit_notes_list_csv[$i]->total_gst_amount,2);
                                $total_data['i_gst']+=$credit_notes_list_csv[$i]->total_gst_amount;
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($credit_notes_list_csv[$i]->total_taxable_value+$credit_notes_list_csv[$i]->total_gst_amount,2);
                            $array[] = '';
                            $array[] = $company_data['company_name'];
                            $array[] = $credit_notes_list_csv[$i]->vendor_name;
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $credit_notes_list_csv[$i]->vendor_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($credit_notes_gst_types);$q++){
                                $gst_percent = $credit_notes_gst_types[$q];
                                $key = $credit_notes_list_csv[$i]->id.'_'.$gst_percent;
                                $array[] = $qty = isset($credit_notes_gst[$key]->total_qty)?$credit_notes_gst[$key]->total_qty:0;
                                $array[] = $total_taxable_value = isset($credit_notes_gst[$key]->total_taxable_value)?$credit_notes_gst[$key]->total_taxable_value:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount/2:0;
                                    $c_s_gst = isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount,2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                    $i_gst = isset($credit_notes_gst[$key]->total_gst_amount)?$credit_notes_gst[$key]->total_gst_amount:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($credit_notes_gst[$key]->total_gst_amount)?round($credit_notes_gst[$key]->total_gst_amount,2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$credit_notes_list_csv[$i]->total_qty;
                            $total_data['taxable_val']+=$credit_notes_list_csv[$i]->total_taxable_value;
                            $total_data['total_val']+=($credit_notes_list_csv[$i]->total_taxable_value+$credit_notes_list_csv[$i]->total_gst_amount);
                        }
                        
                        $array = array('Total','','','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($credit_notes_gst_types);$q++){
                            $gst_percent = $credit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    if($type_id == 2){
                        $credit_notes_gst = $credit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $credit_notes_list_csv = \DB::table('store_products_demand as spd')
                        //->join('store_products_demand_inventory as spdi','spdi.demand_id', '=', 'spd.id')                
                        ->join('store as s','spd.store_id', '=', 's.id')                
                        ->where('spd.demand_type','inventory_return_to_warehouse')        
                        ->wherein('spd.demand_status',['warehouse_loaded','cancelled'])     
                        ->where('spd.fake_inventory',0)        
                        ->where('spd.is_deleted','0');             
                        //->where('spdi.is_deleted','0')        
                 
                        if(!empty($start_date) && !empty($end_date)){
                            $credit_notes_list_csv = $credit_notes_list_csv->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
                        }
                        
                        $credit_notes_list_csv = $credit_notes_list_csv->selectRaw('spd.*,s.store_name,s.gst_no as store_gst_no')
                        ->orderBy('spd.id','ASC')
                        ->get()->toArray();
                        
                        $credit_notes_gst_types = array(3,5,12,18,0);
                        $columns = array('Credit Note No','Invoice No','Credit Note Date','Status','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Credit Note From','Credit Note To','Credit Note From GST No','Credit Note To GST No');
                        
                        for($i=0;$i<count($credit_notes_gst_types);$i++){
                            $gst_percent = $credit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        $company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($credit_notes_list_csv);$i++){
                            
                            $company_data['company_gst_no'] = $credit_notes_list_csv[$i]->company_gst_no;
                            $company_data['company_name'] = $credit_notes_list_csv[$i]->company_gst_name;
                            
                            $total_info = json_decode($credit_notes_list_csv[$i]->total_data,true);
                            $store_info = json_decode($credit_notes_list_csv[$i]->store_data,true);
                            $store_gst_no = $store_info['gst_no'];
                            
                            if($store_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($store_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $credit_notes_date = date('d-m-Y', strtotime($credit_notes_list_csv[$i]->created_at));
                            $credit_note_status  = CommonHelper::getDemandStatusText('inventory_return_to_warehouse',$credit_notes_list_csv[$i]->demand_status);
                            
                            $array = array($credit_notes_list_csv[$i]->credit_invoice_no,$credit_notes_list_csv[$i]->invoice_no,$credit_notes_date,
                            $credit_note_status,$total_info['total_qty'],$total_info['total_taxable_val']);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($total_info['total_gst_amt'])/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($total_info['total_gst_amt'],2);
                                $total_data['i_gst']+=$total_info['total_gst_amt'];
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($total_info['total_taxable_val']+$total_info['total_gst_amt'],2);
                            $array[] = '';
                            $array[] = $company_data['company_name'];
                            $array[] = $store_info['gst_name'];
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $store_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($credit_notes_gst_types);$q++){
                                $gst_percent = $credit_notes_gst_types[$q];
                                
                                $array[] = $qty = isset($total_info['qty_'.$gst_percent])?$total_info['qty_'.$gst_percent]:0;
                                $array[] = $total_taxable_value = isset($total_info['taxable_value_'.$gst_percent])?$total_info['taxable_value_'.$gst_percent]:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]/2:0;
                                    $c_s_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                    $i_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$total_info['total_qty'];
                            $total_data['taxable_val']+=$total_info['total_taxable_val'];
                            $total_data['total_val']+=($total_info['total_taxable_val']+$total_info['total_gst_amt']);
                        }
                        
                        $array = array('Total','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($credit_notes_gst_types);$q++){
                            $gst_percent = $credit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                        
                    }
                    
                    if($type_id == 3){
                        $credit_notes_gst = $credit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $credit_notes_list_csv = \DB::table('store_products_demand as spd')
                        ->join('store as s','spd.store_id', '=', 's.id')     
                        ->leftJoin('store_products_demand as spd_1','spd_1.push_demand_id', '=', 'spd.id')        
                        ->where('spd.demand_type','inventory_return_complete')        
                        ->wherein('spd.demand_status',['store_loaded','cancelled'])       
                        ->where('spd.is_deleted','0')           
                        ->where('spd.fake_inventory',0)        
                        ->groupBy('spd.id');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $credit_notes_list_csv = $credit_notes_list_csv->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
                        }
                        
                        $credit_notes_list_csv = $credit_notes_list_csv->selectRaw('spd.*,s.store_name,s.gst_no as store_gst_no,spd_1.invoice_no as tax_invoice_no')
                        ->orderBy('spd.id','ASC')
                        ->get()->toArray();
                        
                        $credit_notes_gst_types = array(3,5,12,18,0);
                        $columns = array('Credit Note No','Invoice No','Credit Note Date','Status','Tax Invoice No','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Credit Note From','Credit Note To','Credit Note From GST No','Credit Note To GST No');
                        
                        for($i=0;$i<count($credit_notes_gst_types);$i++){
                            $gst_percent = $credit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        $company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($credit_notes_list_csv);$i++){
                            
                            $company_data['company_gst_no'] = $credit_notes_list_csv[$i]->company_gst_no;
                            $company_data['company_name'] = $credit_notes_list_csv[$i]->company_gst_name;
                            
                            $total_info = json_decode($credit_notes_list_csv[$i]->total_data,true);
                            $store_info = json_decode($credit_notes_list_csv[$i]->store_data,true);
                            $store_gst_no = $store_info['gst_no'];
                            
                            if($store_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($store_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $credit_notes_date = date('d-m-Y', strtotime($credit_notes_list_csv[$i]->created_at));
                            $credit_note_status = CommonHelper::getDemandStatusText('inventory_return_complete',$credit_notes_list_csv[$i]->demand_status);
                            
                            $array = array($credit_notes_list_csv[$i]->credit_invoice_no,$credit_notes_list_csv[$i]->invoice_no,$credit_notes_date,
                            $credit_note_status,$credit_notes_list_csv[$i]->tax_invoice_no,$total_info['total_qty'],$total_info['total_taxable_val']);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($total_info['total_gst_amt'])/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($total_info['total_gst_amt'],2);
                                $total_data['i_gst']+=$total_info['total_gst_amt'];
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($total_info['total_taxable_val']+$total_info['total_gst_amt'],2);
                            $array[] = '';
                            $array[] = $company_data['company_name'];
                            $array[] = $store_info['gst_name'];
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $store_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($credit_notes_gst_types);$q++){
                                $gst_percent = $credit_notes_gst_types[$q];
                                //$key = $credit_notes_list_csv[$i]->id.'_'.$gst_percent;
                                $array[] = $qty = isset($total_info['qty_'.$gst_percent])?$total_info['qty_'.$gst_percent]:0;
                                $array[] = $total_taxable_value = isset($total_info['taxable_value_'.$gst_percent])?$total_info['taxable_value_'.$gst_percent]:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]/2:0;
                                    $c_s_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                    $i_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$total_info['total_qty'];
                            $total_data['taxable_val']+=$total_info['total_taxable_val'];
                            $total_data['total_val']+=($total_info['total_taxable_val']+$total_info['total_gst_amt']);
                        }
                        
                        $array = array('Total','','','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($credit_notes_gst_types);$q++){
                            $gst_percent = $credit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    if($type_id == 4){
                        $credit_notes_gst = $credit_notes_gst_types = array();
                        $total_data = array('qty'=>0,'taxable_val'=>0,'c_s_gst'=>0,'i_gst'=>0,'total_val'=>0);
                        
                        $credit_notes_list_csv = \DB::table('store_products_demand as spd')
                        ->join('vendor_detail as vd','vd.id', '=', 'spd.store_id')          
                        ->where('spd.demand_type','inventory_return_to_vendor')
                        ->wherein('spd.demand_status',['warehouse_dispatched','cancelled'])       
                        ->where('spd.fake_inventory',0)        
                        ->where('spd.is_deleted','0');        
                        
                        if(!empty($start_date) && !empty($end_date)){
                            $credit_notes_list_csv = $credit_notes_list_csv->whereRaw("DATE(spd.created_at) >= '$start_date' AND DATE(spd.created_at) <= '$end_date'");        
                        }
                        
                        $credit_notes_list_csv = $credit_notes_list_csv->selectRaw('spd.*,vd.name as vendor_name,vd.gst_no as vendor_gst_no')
                        ->orderBy('spd.id','ASC')
                        ->get()->toArray();
                        
                        $credit_notes_gst_types = array(3,5,12,18,0);
                        $columns = array('Credit Note No','Credit Note Date','Status','Qty','Taxable Value','CGST','SGST','IGST','Total Value',
                        'Comments','Credit Note From','Credit Note To','Credit Note From GST No','Credit Note To GST No');
                        
                        for($i=0;$i<count($credit_notes_gst_types);$i++){
                            $gst_percent = $credit_notes_gst_types[$i];
                            $columns[] = 'Qty '.$gst_percent.'%';
                            $columns[] = 'Taxable Value '.$gst_percent.'%';
                            $columns[] = 'CGST '.$gst_percent.'%';
                            $columns[] = 'SGST '.$gst_percent.'%';
                            $columns[] = 'IGST '.$gst_percent.'%';
                            $columns[] = 'Total Tax '.$gst_percent.'%';
                            $columns[] = 'Total Value '.$gst_percent.'%';
                            
                            $total_data['qty_'.$gst_percent] = 0;
                            $total_data['taxable_val_'.$gst_percent] = 0;
                            $total_data['c_s_gst_'.$gst_percent] = 0;
                            $total_data['i_gst_'.$gst_percent] = 0;
                            $total_data['total_tax_'.$gst_percent] = 0;
                            $total_data['total_val_'.$gst_percent] = 0;
                        }
                        
                        fputcsv($file, $columns);
                        
                        $company_data = CommonHelper::getCompanyData();
                        
                        for($i=0;$i<count($credit_notes_list_csv);$i++){
                            
                            $company_data['company_gst_no'] = $credit_notes_list_csv[$i]->company_gst_no;
                            $company_data['company_name'] = $credit_notes_list_csv[$i]->company_gst_name;
                            
                            $total_info = json_decode($credit_notes_list_csv[$i]->total_data,true);
                            
                            if($credit_notes_list_csv[$i]->vendor_gst_no != $company_data['company_gst_no']){
                                $gst_type = CommonHelper::getGSTType($credit_notes_list_csv[$i]->vendor_gst_no);
                                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
                            }else{
                                $gst_name = '';
                            }
                            
                            $credit_notes_date = date('d-m-Y', strtotime($credit_notes_list_csv[$i]->created_at));
                            $credit_note_status = CommonHelper::getDemandStatusText('inventory_return_to_vendor',$credit_notes_list_csv[$i]->demand_status);
                            
                            $array = array($credit_notes_list_csv[$i]->credit_invoice_no,$credit_notes_date,$credit_note_status,
                            $total_info['total_qty'],$total_info['total_taxable_val']);
                            
                            if($gst_name == 's_gst'){
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = round($total_info['total_gst_amt']/2,2);
                                $array[] = '';
                                $total_data['c_s_gst']+=($total_info['total_gst_amt'])/2;
                            }elseif($gst_name == 'i_gst'){
                                $array[] = '';
                                $array[] = '';
                                $array[] = round($total_info['total_gst_amt'],2);
                                $total_data['i_gst']+=$total_info['total_gst_amt'];
                            }elseif($gst_name == ''){
                                $array[] = '';
                                $array[] = '';
                                $array[] = '';
                            }
                            
                            $array[] = round($total_info['total_taxable_val']+$total_info['total_gst_amt'],2);
                            $array[] = '';
                            $array[] = $company_data['company_name'];
                            $array[] = $credit_notes_list_csv[$i]->vendor_name;
                            $array[] = $company_data['company_gst_no'];
                            $array[] = $credit_notes_list_csv[$i]->vendor_gst_no;
                            
                            $c_s_gst = $i_gst = 0;
                            for($q=0;$q<count($credit_notes_gst_types);$q++){
                                $gst_percent = $credit_notes_gst_types[$q];
                                
                                $array[] = $qty = isset($total_info['qty_'.$gst_percent])?$total_info['qty_'.$gst_percent]:0;
                                $array[] = $total_taxable_value = isset($total_info['taxable_value_'.$gst_percent])?$total_info['taxable_value_'.$gst_percent]:0;
                                
                                if($gst_name == 's_gst'){
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent]/2,2):0;
                                    $array[] = '';
                                    $total_data['c_s_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]/2:0;
                                    $c_s_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == 'i_gst'){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                    $total_data['i_gst_'.$gst_percent]+=isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                    $i_gst = isset($total_info['gst_amount_'.$gst_percent])?$total_info['gst_amount_'.$gst_percent]:0;
                                }elseif($gst_name == ''){
                                    $array[] = '';
                                    $array[] = '';
                                    $array[] = '';
                                }   
                                
                                $array[] = $total_gst_amount = isset($total_info['gst_amount_'.$gst_percent])?round($total_info['gst_amount_'.$gst_percent],2):0;
                                $array[] = $total_taxable_value+$total_gst_amount;
                                
                                $total_data['qty_'.$gst_percent]+=$qty;
                                $total_data['taxable_val_'.$gst_percent]+=$total_taxable_value;
                                $total_data['total_tax_'.$gst_percent]+=($c_s_gst+$i_gst);
                                $total_data['total_val_'.$gst_percent]+=($total_taxable_value+$c_s_gst+$i_gst);
                            }
                            
                            fputcsv($file, $array);
                            
                            $total_data['qty']+=$total_info['total_qty'];
                            $total_data['taxable_val']+=$total_info['total_taxable_val'];
                            $total_data['total_val']+=($total_info['total_taxable_val']+$total_info['total_gst_amt']);
                        }
                        
                        $array = array('Total','','',$total_data['qty'],$total_data['taxable_val'],$total_data['c_s_gst'],$total_data['c_s_gst'],$total_data['i_gst'],$total_data['total_val'],'','','','','');
                        
                        for($q=0;$q<count($credit_notes_gst_types);$q++){
                            $gst_percent = $credit_notes_gst_types[$q];
                            $array[] = $total_data['qty_'.$gst_percent];
                            $array[] = $total_data['taxable_val_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['c_s_gst_'.$gst_percent];
                            $array[] = $total_data['i_gst_'.$gst_percent];
                            $array[] = $total_data['total_tax_'.$gst_percent];
                            $array[] = $total_data['total_val_'.$gst_percent];
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
            
            return view('admin/credit_notes_list',array('credit_notes_list'=>$credit_notes_list,'credit_note_types'=>$credit_note_types,'user'=>$user,'type_id'=>$type_id,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'VENDOR',__FUNCTION__,__FILE__);
            return view('admin/credit_notes_list',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function storeInventoryStatusReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $store_inventory = $store_demand_return_data = $store_demand_return = array();
            
            $store_inventory_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('store as s','ppmi.store_id', '=', 's.id')         
            ->leftJoin('purchase_order as po','po.id', '=', 'ppmi.po_id')                 
            ->where('ppmi.product_status','>','1')        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0)         
            ->where('ppmi.status',1)        
            ->where('s.is_deleted',0);     
            
            if($user->user_type == 9){
                $store_data = CommonHelper::getUserStoreData($user->id);
                $store_inventory_list = $store_inventory_list->where('s.id',$store_data->id);
            }else{
                if(isset($data['s_id']) && !empty($data['s_id'])){
                    $store_inventory_list = $store_inventory_list->where('s.id',$data['s_id']);
                }
                
                if(isset($data['v_id']) && !empty($data['v_id'])){
                    $store_inventory_list = $store_inventory_list->where('po.vendor_id',$data['v_id']);
                    
                    if(isset($data['inv_type']) && strtolower($data['inv_type']) == 'arnon'){
                        $store_inventory_list = $store_inventory_list->where('ppmi.arnon_inventory',1);
                    }
                    if(isset($data['inv_type']) && strtolower($data['inv_type']) == 'north'){
                        $store_inventory_list = $store_inventory_list->where('ppmi.arnon_inventory',0);
                    }
                }
            }
            
            $store_inventory_list = $store_inventory_list->groupByRaw('s.id,ppmi.product_status')        
            ->selectRaw('s.id as store_id,s.store_name,s.store_id_code,ppmi.product_status, COUNT(ppmi.id) as inv_count')     
            ->orderBy('s.store_name','ASC')        
            ->get()->toArray();
            
            for($i=0;$i<count($store_inventory_list);$i++){
                $index = array_search($store_inventory_list[$i]->store_id,array_column($store_inventory,'store_id'));
                $status_name = 'status_'.$store_inventory_list[$i]->product_status;
                if($index === false){
                    $store_inventory[] = array('store_id'=>$store_inventory_list[$i]->store_id,'store_name'=>$store_inventory_list[$i]->store_name,'store_id_code'=>$store_inventory_list[$i]->store_id_code,$status_name=>$store_inventory_list[$i]->inv_count);
                }else{
                    if(isset($store_inventory[$index][$status_name])){
                        $store_inventory[$index][$status_name] = $store_inventory[$index][$status_name]+$store_inventory_list[$i]->inv_count;
                    }else{
                        $store_inventory[$index][$status_name] = $store_inventory_list[$i]->inv_count;
                    }
                }
            }
            
            /*$store_demand_return = \DB::table('store_products_demand_inventory as spdi')
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')        
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')                   
            ->where('spd.demand_type','inventory_return_to_warehouse')                      
            ->wherein('spd.demand_status',array('warehouse_dispatched','warehouse_loading','warehouse_loaded'))      
            ->where('spdi.is_deleted',0)
            ->where('ppmi.is_deleted',0)                
            //->wherein('ppmi.arnon_inventory',0)                
            ->where('spd.is_deleted',0)
            ->groupByRaw('spd.store_id')        
            ->select('spd.store_id',\DB::raw('COUNT(spdi.id) as inv_count_return'))
            ->get()->toArray();
            
            for($i=0;$i<count($store_demand_return);$i++){
                $store_id = $store_demand_return[$i]->store_id;
                $store_demand_return_data[$store_id] = $store_demand_return[$i];
            }*/
            
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=stores_inventory_status_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Store Name','Store Code');
                
                $status_list = CommonHelper::getposProductStatusList();
                unset($status_list[0]);unset($status_list[1]);unset($status_list[6]);unset($status_list[7]); 
                $statuses_total = array();
                foreach($status_list as $status_id=>$status_name){
                    $columns[] = $status_name;
                    $statuses_total[$status_id] = 0;
                }
                //$columns[] = 'Returned from Warehouse';
                $columns[] = 'Total';
                
                $callback = function() use ($store_inventory, $columns,$status_list,$statuses_total,$store_demand_return_data){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $grand_total = $store_total = $return_total_stores = 0;
                    for($i=0;$i<count($store_inventory);$i++){
                        $store_id = $store_inventory[$i]['store_id'];
                        $array = array($store_inventory[$i]['store_name'],$store_inventory[$i]['store_id_code']);
                        
                        foreach($status_list as $status_id=>$status_name){
                            $status_total = (isset($store_inventory[$i]['status_'.$status_id]))?$store_inventory[$i]['status_'.$status_id]:0;
                            $array[] = $status_total;
                            $store_total+=$status_total; 
                            $statuses_total[$status_id]+=$status_total;
                        }
                        
                        $return_total = isset($store_demand_return_data[$store_id])?$store_demand_return_data[$store_id]->inv_count_return:0;
                        $store_total+=$return_total;
                        
                        //$array[] = $return_total;
                        $array[] = $store_total;
                        $store_total = 0; 
                        //$return_total_stores+=$return_total;
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','');
                    foreach($status_list as $status_id=>$status_name){
                        $array[] = $statuses_total[$status_id];
                        $grand_total+=$statuses_total[$status_id];
                    }
                    
                    //$array[] = $return_total_stores;
                    $array[] = $grand_total;
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            $store_list = CommonHelper::getStoresList();
            $vendors_list = CommonHelper::getVendorsList();
            
            return view('admin/report_store_inventory_status',array('store_inventory'=>$store_inventory,'store_list'=>$store_list,'error_message'=>$error_message,'user'=>$user,'store_demand_return_data'=>$store_demand_return_data,'vendors_list'=>$vendors_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_INVENTORY_STATUS_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_store_inventory_status',array('error_message'=>$e->getMessage()));
        }
    }
    
    function warehouseInventoryStatusReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $warehouse_inventory = array();
            
            $warehouse_inventory_list = \DB::table('pos_product_master_inventory as ppmi')
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.fake_inventory',0)         
            ->where('ppmi.status',1);        
                 
            $warehouse_inventory_list = $warehouse_inventory_list->groupByRaw('ppmi.product_status')        
            ->selectRaw('ppmi.product_status, COUNT(ppmi.id) as inv_count')     
            ->get()->toArray();
            
            for($i=0;$i<count($warehouse_inventory_list);$i++){
                $warehouse_inventory['status_'.$warehouse_inventory_list[$i]->product_status] = $warehouse_inventory_list[$i]->inv_count;
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=warehouse_inventory_status_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array();
                
                $status_list = CommonHelper::getposProductStatusList();
                unset($status_list[6]); 
                foreach($status_list as $status_id=>$status_name){
                    $columns[] = $status_name;
                    
                }
                $columns[] = 'Total';
                
                $callback = function() use ($warehouse_inventory, $columns,$status_list){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $grand_total = 0;
                    
                    $array = array();

                    foreach($status_list as $status_id=>$status_name){
                        $status_total = (isset($warehouse_inventory['status_'.$status_id]))?$warehouse_inventory['status_'.$status_id]:0;
                        $array[] = $status_total;
                        $grand_total+=$status_total; 
                    }

                    $array[] = $grand_total;
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            return view('admin/report_warehouse_inventory_status',array('warehouse_inventory'=>$warehouse_inventory,'error_message'=>$error_message,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE_INVENTORY_STATUS_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_warehouse_inventory_status',array('error_message'=>$e->getMessage()));
        }
    }
    
    function warehouseInventoryDailyUpdateReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $dates_list = $inventory_in_from_vendor_list = $inventory_out_to_store_list = $inventory_in_from_store_list = $inventory_out_to_vendor_list = array();
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            $start_date = $search_date['start_date'];
            $end_date = $search_date['end_date'];
            
            $days_diff =  CommonHelper::dateDiff($start_date, $end_date);
            
            if($days_diff > 365){
                throw new \Exception('Date difference should not be more than 365 days');
            }
            
            // PO data from vendor to warehouse
            $inventory_in_from_vendor = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc_items.grn_qc_id', '=', 'po_grn_qc.id')        
            ->where('po_grn_qc.type','grn')         
            ->where('po_grn_qc.is_deleted',0)
            ->where('po_grn_qc_items.is_deleted',0)
            ->where('po_grn_qc.fake_inventory',0)             
            ->where('po_grn_qc_items.fake_inventory',0)                     
            ->whereRaw("po_grn_qc.created_at BETWEEN '$start_date' AND '$end_date'")        
            ->groupByRaw('DATE(po_grn_qc.created_at)')        
            ->selectRaw('DATE(po_grn_qc.created_at) as intake_date, COUNT(po_grn_qc_items.id) as inv_in_count')     
            ->get()->toArray();
            
            // Inventory Return from Store to warehouse
            $inventory_in_from_store = \DB::table('store_products_demand as spd')
            ->join('store_products_demand_inventory as spdi','spdi.demand_id', '=', 'spd.id')        
            ->wherein('spd.demand_status',array('warehouse_loading','warehouse_dispatched','warehouse_loaded'))         
            ->where('spd.demand_type','inventory_return_to_warehouse')     
            ->where('spd.is_deleted',0)
            ->where('spdi.is_deleted',0)        
            ->where('spdi.demand_status',1)        
            ->where('spd.fake_inventory',0)
            ->where('spdi.fake_inventory',0)        
            ->whereRaw("spd.created_at BETWEEN '$start_date' AND '$end_date'")        
            ->groupByRaw('DATE(spd.created_at)')        
            ->selectRaw('DATE(spd.created_at) as intake_date, COUNT(spdi.id) as inv_in_count')     
            ->get()->toArray();
            
            // Store push demand from warehouse to store
            $inventory_out_to_store = \DB::table('store_products_demand as spd')
            ->join('store_products_demand_inventory as spdi','spdi.demand_id', '=', 'spd.id')        
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))         
            ->where('spd.demand_type','inventory_push')     
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")                
            ->where('spd.is_deleted',0)
            ->where('spdi.is_deleted',0)      
            ->where('spdi.demand_status',1)        
            ->where('spd.fake_inventory',0)
            ->where('spdi.fake_inventory',0)        
            ->whereRaw("spd.created_at BETWEEN '$start_date' AND '$end_date'")        
            ->groupByRaw('DATE(spd.created_at)')        
            ->selectRaw('DATE(spd.created_at) as store_assign_date, COUNT(spdi.id) as inv_out_count')     
            ->get()->toArray();
            
            // Inventory Return from warehouse to vendor
            $inventory_out_to_vendor = \DB::table('store_products_demand as spd')
            ->join('store_products_demand_inventory as spdi','spdi.demand_id', '=', 'spd.id')        
            ->wherein('spd.demand_status',array('warehouse_dispatched'))         
            ->where('spd.demand_type','inventory_return_to_vendor')     
            ->where('spd.is_deleted',0)
            ->where('spdi.is_deleted',0)     
            ->where('spdi.demand_status',1)        
            ->where('spd.fake_inventory',0)
            ->where('spdi.fake_inventory',0)        
            ->whereRaw("spd.created_at BETWEEN '$start_date' AND '$end_date'")        
            ->groupByRaw('DATE(spd.created_at)')        
            ->selectRaw('DATE(spd.created_at) as inv_out_date, COUNT(spdi.id) as inv_out_count')     
            ->get()->toArray();
            
            //Defective PO Invoice inventory return from warehouse to vendor
            $inventory_out_to_vendor_defective = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc_items.grn_qc_id', '=', 'po_grn_qc.id')        
            ->where('po_grn_qc.type','qc_return')         
            ->where('po_grn_qc.is_deleted',0)
            ->where('po_grn_qc_items.is_deleted',0)
            ->where('po_grn_qc.fake_inventory',0)
            ->where('po_grn_qc_items.fake_inventory',0)        
            ->whereRaw("po_grn_qc.created_at BETWEEN '$start_date' AND '$end_date'")        
            ->groupByRaw('DATE(po_grn_qc.created_at)')        
            ->selectRaw('DATE(po_grn_qc.created_at) as inv_out_date, COUNT(po_grn_qc_items.id) as inv_out_count')     
            ->get()->toArray();
            
            for($i=0;$i<count($inventory_in_from_vendor);$i++){
                $key = date('Y-n-d',strtotime($inventory_in_from_vendor[$i]->intake_date));
                $inventory_in_from_vendor_list[$key] = $inventory_in_from_vendor[$i]->inv_in_count;
            }
            
            for($i=0;$i<count($inventory_in_from_store);$i++){
                $key = date('Y-n-d',strtotime($inventory_in_from_store[$i]->intake_date));
                $inventory_in_from_store_list[$key] = $inventory_in_from_store[$i]->inv_in_count;
            }
            
            for($i=0;$i<count($inventory_out_to_store);$i++){
                $key = date('Y-n-d',strtotime($inventory_out_to_store[$i]->store_assign_date));
                $inventory_out_to_store_list[$key] = $inventory_out_to_store[$i]->inv_out_count;
            }
            
            for($i=0;$i<count($inventory_out_to_vendor);$i++){
                $key = date('Y-n-d',strtotime($inventory_out_to_vendor[$i]->inv_out_date));
                $inventory_out_to_vendor_list[$key] = $inventory_out_to_vendor[$i]->inv_out_count;
            }
            
            for($i=0;$i<count($inventory_out_to_vendor_defective);$i++){
                $key = date('Y-n-d',strtotime($inventory_out_to_vendor_defective[$i]->inv_out_date));
                $inventory_out_to_vendor_defective_list[$key] = $inventory_out_to_vendor_defective[$i]->inv_out_count;
            }
            
            for($i=0;$i<=$days_diff;$i++){
                $date = date('Y-n-d',strtotime("+ $i days",strtotime($start_date)));
                $dates_list[$i]['date'] = $date;
                
                $dates_list[$i]['inventory_in_from_vendor'] = (isset($inventory_in_from_vendor_list[$date]))?$inventory_in_from_vendor_list[$date]:0;
                $dates_list[$i]['inventory_in_from_store'] = (isset($inventory_in_from_store_list[$date]))?$inventory_in_from_store_list[$date]:0;
                
                $dates_list[$i]['inventory_out_to_store'] = (isset($inventory_out_to_store_list[$date]))?$inventory_out_to_store_list[$date]:0;
                $dates_list[$i]['inventory_out_to_vendor'] = (isset($inventory_out_to_vendor_list[$date]))?$inventory_out_to_vendor_list[$date]:0;
                $dates_list[$i]['inventory_out_to_vendor_defective'] = (isset($inventory_out_to_vendor_defective_list[$date]))?$inventory_out_to_vendor_defective_list[$date]:0;
            }
            
            $dates_list = array_reverse($dates_list);
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=warehouse_inventory_in_out_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns1 = array('Date','Warehouse In','','','Warehouse Out','','','','');
                $columns2 = array('','PO Invoices from Vendor','SOR Return from Store','Total In','Inventory Push to Store','SOR Return to Vendor','QC Defective Return to Vendor','Total Out');
                
                $callback = function() use ($dates_list, $columns1,$columns2){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns1);
                    fputcsv($file, $columns2);
                    $total_data = array('inv_in_vendor'=>0,'inv_in_store'=>0,'total_in'=>0,'inv_out_store'=>0,'inv_out_vendor'=>0,'inv_out_vendor_defective'=>0,'total_out'=>0);
                    
                    for($i=0;$i<count($dates_list);$i++){
                        $date = date('d-m-Y',strtotime($dates_list[$i]['date']));
                        $inv_in_vendor = (isset($dates_list[$i]['inventory_in_from_vendor']))?$dates_list[$i]['inventory_in_from_vendor']:0;
                        $inv_in_store = (isset($dates_list[$i]['inventory_in_from_store']))?$dates_list[$i]['inventory_in_from_store']:0;
                        $total_in = $inv_in_vendor+$inv_in_store;
                        $inv_out_store = (isset($dates_list[$i]['inventory_out_to_store']))?$dates_list[$i]['inventory_out_to_store']:0;
                        $inv_out_vendor = (isset($dates_list[$i]['inventory_out_to_vendor']))?$dates_list[$i]['inventory_out_to_vendor']:0;
                        $inv_out_vendor_defective = (isset($dates_list[$i]['inventory_out_to_vendor_defective']))?$dates_list[$i]['inventory_out_to_vendor_defective']:0;
                        $total_out = $inv_out_store+$inv_out_vendor+$inv_out_vendor_defective;
                        
                        $array = array($date,$inv_in_vendor,$inv_in_store,$total_in,$inv_out_store,$inv_out_vendor,$inv_out_vendor_defective,$total_out);
                        fputcsv($file, $array);
                        
                        $total_data['inv_in_vendor']+=$inv_in_vendor;    
                        $total_data['inv_in_store']+=$inv_in_store;      
                        $total_data['total_in']+=$total_in;      
                        $total_data['inv_out_store']+=$inv_out_store;      
                        $total_data['inv_out_vendor']+=$inv_out_vendor;      
                        $total_data['inv_out_vendor_defective']+=$inv_out_vendor_defective;
                        $total_data['total_out']+=$total_out;  
                    }

                    $array = array('Total',$total_data['inv_in_vendor'],$total_data['inv_in_store'],$total_data['total_in'],$total_data['inv_out_store'],$total_data['inv_out_vendor'],$total_data['inv_out_vendor_defective'],$total_data['total_out']);
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            return view('admin/report_warehouse_inventory_daily_update',array('dates_list'=>$dates_list,'error_message'=>$error_message,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE_INVENTORY_DAILY_UPDATE_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_warehouse_inventory_daily_update',array('error_message'=>$e->getMessage().', Line: '.$e->getLine()));
        }
    }
    
    function warehouseInventoryBalanceReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $category_list = $category_data = $subcategory_list = $vendor_list = $po_list = $po_category_list = $po_cat_list = array();
            $po_list_count = 0;
            
            $report_types = array('1'=>'Category','2'=>'Subcategory','3'=>'Vendor','4'=>'Purchase Order');
            $report_type = (isset($data['type_id']) && !empty($data['type_id']))?$data['type_id']:1;
            
            // Category wise
            if($report_type == 1){
                $category_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                ->join('design_lookup_items_master as dlim','dlim.id', '=', 'ppm.category_id')
                ->wherein('ppmi.product_status',[1,2])
                ->where('ppmi.qc_status',1)        
                ->where('ppmi.is_deleted',0)      
                ->where('ppm.is_deleted',0)                    
                ->where('ppm.fake_inventory',0)         
                ->where('ppmi.fake_inventory',0)   
                ->groupBy('dlim.id')        
                ->selectRaw('dlim.id as category_id,dlim.name as category_name,COUNT(ppmi.id) as inv_count')
                ->orderBy('inv_count','DESC')        
                ->get()->toArray(); 
            }
            
            // SubCategory wise
            if($report_type == 2){
                $category_id = trim($data['cat_id']);
                $category_data = Design_lookup_items_master::where('id',$category_id)->first();
                
                $subcategory_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                ->leftJoin('design_lookup_items_master as dlim_1','dlim_1.id', '=', 'ppm.subcategory_id')
                ->where('ppm.category_id',$category_id)        
                ->wherein('ppmi.product_status',[1,2])
                ->where('ppmi.qc_status',1)        
                ->where('ppmi.is_deleted',0)      
                ->where('ppm.is_deleted',0)                    
                ->where('ppm.fake_inventory',0)         
                ->where('ppmi.fake_inventory',0)   
                ->groupBy('dlim_1.id')        
                ->selectRaw('dlim_1.id as subcategory_id,dlim_1.name as subcategory_name,COUNT(ppmi.id) as inv_count')
                ->orderBy('inv_count','DESC')        
                ->get()->toArray(); 
            }
            
            //Vendor wise
            if($report_type == 3){
                $vendor_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
                ->wherein('ppmi.product_status',[1,2])
                ->where('ppmi.qc_status',1)        
                ->where('ppmi.is_deleted',0)                
                ->where('po.fake_inventory',0)         
                ->where('ppmi.fake_inventory',0)   
                ->groupBy('vd.id')        
                ->selectRaw('vd.id,vd.name as vendor_name,COUNT(ppmi.id) as inv_count')
                ->orderBy('inv_count','DESC')        
                ->get()->toArray(); 
            }
            
            //Purchase Order wise
            if($report_type == 4){
                $po_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
                ->wherein('ppmi.product_status',[1,2])
                ->where('ppmi.qc_status',1)        
                ->where('ppmi.is_deleted',0)                
                ->where('po.fake_inventory',0)         
                ->where('ppmi.fake_inventory',0)   
                ->groupBy('po.id')        
                ->selectRaw('po.id,po.order_no,po.category_id,vd.name as vendor_name,COUNT(ppmi.id) as inv_count')
                ->orderBy('inv_count','DESC') 
                ->paginate(100);
                
                $po_list_count = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order as po','po.id', '=', 'ppmi.po_id')        
                ->join('vendor_detail as vd','vd.id', '=', 'po.vendor_id')
                ->wherein('ppmi.product_status',[1,2])
                ->where('ppmi.qc_status',1)        
                ->where('ppmi.is_deleted',0)                
                ->where('po.fake_inventory',0)         
                ->where('ppmi.fake_inventory',0)   
                ->count();
                
                $po_category_list = Design_lookup_items_master::where('type','PURCHASE_ORDER_CATEGORY')->where(array('status'=>1,'is_deleted'=>0))->orderBy('name')->get()->toArray();
                
                for($i=0;$i<count($po_category_list);$i++){
                    $po_cat_list[$po_category_list[$i]['id']] = $po_category_list[$i]['name'];
                }
                
                $po_category_list = $po_cat_list;
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=warehouse_inventory_balance_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                if($report_type == 1){
                    $columns = array('Category','Balance Inventory');
                }elseif($report_type == 2){
                    $columns = array('Subcategory','Balance Inventory');
                }elseif($report_type == 3){
                    $columns = array('Vendor','Balance Inventory');
                }elseif($report_type == 4){
                    $columns = array('Purchase Order','Vendor','Balance Inventory');
                }
                
                $callback = function() use ($report_type,$category_list,$subcategory_list,$vendor_list,$po_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total_records = 0;
                    // Category
                    if($report_type == 1){
                        for($i=0;$i<count($category_list);$i++){
                            $array = array($category_list[$i]->category_name,$category_list[$i]->inv_count);
                            fputcsv($file, $array);
                            $total_records+=$category_list[$i]->inv_count; 
                        }
                        
                        $array = array('Total',$total_records);
                        fputcsv($file, $array);
                    }elseif($report_type == 2){ // Subcategory
                        for($i=0;$i<count($subcategory_list);$i++){
                            $array = array($subcategory_list[$i]->subcategory_name,$subcategory_list[$i]->inv_count);
                            fputcsv($file, $array);
                            $total_records+=$subcategory_list[$i]->inv_count; 
                        }
                        
                        $array = array('Total',$total_records);
                        fputcsv($file, $array);
                    }elseif($report_type == 3){ // Vendor
                        for($i=0;$i<count($vendor_list);$i++){
                            $array = array($vendor_list[$i]->vendor_name,$vendor_list[$i]->inv_count);
                            fputcsv($file, $array);
                            $total_records+=$vendor_list[$i]->inv_count; 
                        }
                        
                        $array = array('Total',$total_records);
                        fputcsv($file, $array);
                    }elseif($report_type == 4){ // PO
                        for($i=0;$i<count($po_list);$i++){
                            $array = array($po_list[$i]->order_no,$po_list[$i]->vendor_name,$po_list[$i]->inv_count);
                            fputcsv($file, $array);
                            $total_records+=$po_list[$i]->inv_count; 
                        }
                        
                        $array = array('Total','',$total_records);
                        fputcsv($file, $array);
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            return view('admin/report_warehouse_inventory_balance',array('report_types'=>$report_types,'report_type'=>$report_type,'error_message'=>$error_message,'po_category_list'=>$po_category_list,
            'category_list'=>$category_list,'subcategory_list'=>$subcategory_list,'category_data'=>$category_data,'vendor_list'=>$vendor_list,'po_list'=>$po_list,'po_list_count'=>$po_list_count));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE_INVENTORY_BALANCE_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_warehouse_inventory_balance',array('error_message'=>$e->getMessage()));
        }
    }
    
    function inventoryRawReport(Request $request){
        try{ 
            //\DB::enableQueryLog();
            ini_set('memory_limit', '-1');
            $data = $request->all();
            $stores = $vendors = $users = $po_list = $design_items = $size_list = array();
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            $start_date = $end_date = '';
            
            if(!empty($search_date['start_date']) && !empty($search_date['start_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            $inventory_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
            ->leftJoin('pos_customer_orders as pco','pco.id', '=', 'ppmi.customer_order_id')             
            ->leftJoin('pos_customer_orders_detail as pcod',function($join){$join->on('pcod.inventory_id','=','ppmi.id')->on('pcod.order_id','=','ppmi.customer_order_id')->where('pcod.is_deleted',0);}) 
            ->leftJoin('pos_customer as pc','pc.id', '=', 'pco.customer_id')
            ->leftJoin('store_staff as ss','ss.id', '=', 'pcod.staff_id')                
            ->leftJoin('store_products_demand as spd','spd.id', '=', 'ppmi.demand_id')     
            ->leftJoin('store_products_demand_inventory as spdi',function($join){$join->on('spdi.inventory_id','=','ppmi.id')->on('spdi.demand_id','=','ppmi.demand_id')->where('spdi.is_deleted',0);})    
            ->leftJoin('store_products_demand_courier as spdc',function($join){$join->on('spd.id','=','spdc.demand_id')->where('spdc.is_deleted',0);})    
            ->leftJoin('purchase_order_items as poi','poi.id', '=', 'ppmi.po_item_id')       
            ->leftJoin('purchase_order_details as pod','pod.id', '=', 'ppmi.po_detail_id') 
            ->leftJoin('purchase_order_grn_qc as po_grn','po_grn.id', '=', 'ppmi.grn_id')        
            ->selectRaw('ppmi.*,pc.salutation,pc.customer_name,pc.phone as customer_phone,spd.invoice_no as demand_invoice_no,spd.demand_status,spd.demand_type,spd.user_id as demand_user_id,spd.from_store_id,spd.store_id as to_store_id,
            spd.created_at as demand_created_at,spd.user_id as demand_created_by,pod.invoice_no as pod_invoice_no,po_grn.grn_no,poi.vendor_sku,ppm.product_name,ppm.product_barcode,ppm.product_sku,
            ppm.vendor_product_sku,ppm.category_id,ppm.subcategory_id,ppm.color_id,ppm.size_id,ppm.hsn_code,ppm.product_description,ppm.created_at as product_created_at,ppm.updated_at as product_updated_at,
            ppm.arnon_product,pco.order_no as pos_order_no,pco.total_price as pos_total_price,pco.total_items as pos_total_items,pco.store_id as pco_store_id,pco.store_user_id,pco.created_at as pco_created_at,
            pco.bags_count,pcod.sale_price as pcod_sale_price,pcod.net_price as pcod_net_price,pcod.discounted_price as pcod_discounted_price,pcod.discount_percent as pcod_discount_percent,pcod.discount_amount as pcod_discount_amount,
            pcod.gst_percent as pcod_gst_percent,pcod.gst_amount as pcod_gst_amount,pcod.gst_inclusive as pcod_gst_inclusive,ss.name as store_staff_name,spdi.store_base_rate as spdi_store_base_rate,spdi.store_gst_percent as spdi_store_gst_percent,
            spdi.store_gst_amount as spdi_store_gst_amount,spdi.store_base_price as spdi_store_base_price,spd.comments as demand_comments,spdc.boxes_count,spdc.transporter_name,spdc.transporter_gst,spdc.docket_no,spdc.eway_bill_no,spdc.lr_no')
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)
            ->where('ppmi.fake_inventory',0)        
            ->where('ppm.fake_inventory',0)
            ->where('ppmi.status',1)        
            ->orderBy('ppmi.id','ASC');
            
            if(!empty($start_date) && !empty($end_date)){
                $inventory_list = $inventory_list->whereRaw("DATE(ppmi.created_at) >= '$start_date' AND DATE(ppmi.created_at) <= '$end_date'");        
            }
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $inventory_list = $inventory_list->where('ppmi.vendor_id',trim($data['v_id']));
            }
            
            if(isset($data['status']) && ($data['status']) != ''){
                $inventory_list = $inventory_list->where('ppmi.product_status',trim($data['status']));
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $inventory_list = $inventory_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $inventory_list = $inventory_list->paginate(100);
            }
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            $store_list = CommonHelper::getStoresList();
            $vendor_list = CommonHelper::getVendorsList();
            $user_list = \DB::table('users as u')->select('id','name','email')->get();
            $po_listing = \DB::table('purchase_order')->where('is_deleted',0)->where('fake_inventory',0)->select('id','order_no','vendor_id','user_id','created_at')->get();
            $items_list = Design_lookup_items_master::wherein('type',['color','pos_product_category','pos_product_subcategory'])->select('id','name','type')->get()->toArray();
            $size_listing = Production_size_counts::select('id','size')->get()->toArray();
            $status_list = CommonHelper::getposProductStatusList();
            
            for($i=0;$i<count($store_list);$i++){
                $stores[$store_list[$i]['id']] = $store_list[$i];
            }

            for($i=0;$i<count($vendor_list);$i++){
                $vendors[$vendor_list[$i]['id']] = $vendor_list[$i];
            }
            
            for($i=0;$i<count($user_list);$i++){
                $users[$user_list[$i]->id] = $user_list[$i];
            }
            
            for($i=0;$i<count($po_listing);$i++){
                $po_list[$po_listing[$i]->id] = $po_listing[$i];
            }
            
            for($i=0;$i<count($items_list);$i++){
                $design_items[$items_list[$i]['id']] = $items_list[$i];
            }
            
            for($i=0;$i<count($size_listing);$i++){
                $size_list[$size_listing[$i]['id']] = $size_listing[$i];
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=raw_inventory_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Inventory ID','QR Code','Status','Inventory Type','Date Added','Current Store','Current Demand','Demand Type','From Store','To Store','Demand Status','Demand Created On','Demand Created by','Demand Store Base Rate','Demand Store GST%','Demand Store GST Amt','Demand Store Cost Price','Demand Comments','Demand Boxes Count','Demand Transporter Name','Demand Transporter GST','Demand Docket No','Demand Eway Bill No','Demand LR No','Vendor','Purchase Order','Created On',
                'Created by','PO Invoice No','GRN No','QC Status','Product SKU','Vendor SKU','Product Name','Category','Subcategory','Color','Size','HSN Code','Product Type','Product Barcode','Description','Created On','Updated On','Vendor Rate',
                'Vendor GST %','Vendor GST Amount','Cost Price','Sale Price (MRP)','Store Base Rate','Store GST %','Store GST Amount','Store Cost Price','Warehouse Intake Date','Store Assign Date','Store Intake Date','Store Sale Date','Record Last Updated',
                'Store POS Order No','Order Total Price','Order Total Items','Order Store','Order Customer Name','Order Customer Phone','Order Created By','Order Bags','Order Product MRP','Order Product Discount %','Order Product Discount Amount','Order Product GST %',
                'Order Product GST Amount','Order Product Net Price','Order Product Staff Name','Order Date');

                $callback = function() use ($inventory_list,$stores,$vendors,$users,$po_list,$design_items,$size_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($inventory_list);$i++){
                        $array = [$inventory_list[$i]->id,
                        CommonHelper::filterCsvInteger($inventory_list[$i]->peice_barcode),
                        CommonHelper::getposProductStatusName($inventory_list[$i]->product_status),
                        ($inventory_list[$i]->arnon_inventory == 0)?CommonHelper::getInventoryType(1):CommonHelper::getInventoryType(2),
                        date('d-m-Y',strtotime($inventory_list[$i]->created_at)),
                        isset($stores[$inventory_list[$i]->store_id])?$stores[$inventory_list[$i]->store_id]['store_name']:'',
                        $inventory_list[$i]->demand_invoice_no,
                        CommonHelper::getDemandTypeText($inventory_list[$i]->demand_type),
                        isset($stores[$inventory_list[$i]->from_store_id])?$stores[$inventory_list[$i]->from_store_id]['store_name']:'',
                        isset($stores[$inventory_list[$i]->to_store_id])?$stores[$inventory_list[$i]->to_store_id]['store_name']:'',
                        CommonHelper::getDemandStatusText($inventory_list[$i]->demand_type,$inventory_list[$i]->demand_status),
                        (!empty($inventory_list[$i]->demand_created_at))?date('d-m-Y',strtotime($inventory_list[$i]->demand_created_at)):'',
                        (!empty($inventory_list[$i]->demand_created_by))?$users[$inventory_list[$i]->demand_created_by]->name:'',
                        $inventory_list[$i]->spdi_store_base_rate,
                        $inventory_list[$i]->spdi_store_gst_percent,
                        $inventory_list[$i]->spdi_store_gst_amount,
                        $inventory_list[$i]->spdi_store_base_price,
                        $inventory_list[$i]->demand_comments,
                        $inventory_list[$i]->boxes_count,
                        $inventory_list[$i]->transporter_name,
                        $inventory_list[$i]->transporter_gst,
                        $inventory_list[$i]->docket_no,
                        $inventory_list[$i]->eway_bill_no,
                        $inventory_list[$i]->lr_no,
                        isset($vendors[$inventory_list[$i]->vendor_id])?$vendors[$inventory_list[$i]->vendor_id]['name']:'',
                        isset($po_list[$inventory_list[$i]->po_id])?$po_list[$inventory_list[$i]->po_id]->order_no:'',
                        isset($po_list[$inventory_list[$i]->po_id])?date('d-m-Y',strtotime($po_list[$inventory_list[$i]->po_id]->created_at)):'',
                        (isset($po_list[$inventory_list[$i]->po_id]) && isset($users[$po_list[$inventory_list[$i]->po_id]->user_id]))?$users[$po_list[$inventory_list[$i]->po_id]->user_id]->name:'',
                        $inventory_list[$i]->pod_invoice_no,
                        $inventory_list[$i]->grn_no,
                        CommonHelper::getProductInventoryQCStatusName($inventory_list[$i]->qc_status),
                        !empty($inventory_list[$i]->vendor_sku)?$inventory_list[$i]->vendor_sku:$inventory_list[$i]->product_sku,
                        $inventory_list[$i]->vendor_product_sku,
                        $inventory_list[$i]->product_name,
                        isset($design_items[$inventory_list[$i]->category_id])?$design_items[$inventory_list[$i]->category_id]['name']:'',
                        isset($design_items[$inventory_list[$i]->subcategory_id])?$design_items[$inventory_list[$i]->subcategory_id]['name']:'',
                        isset($design_items[$inventory_list[$i]->color_id])?$design_items[$inventory_list[$i]->color_id]['name']:'',
                        isset($size_list[$inventory_list[$i]->size_id])?$size_list[$inventory_list[$i]->size_id]['size']:'',
                        $inventory_list[$i]->hsn_code,
                        ($inventory_list[$i]->arnon_product == 0)?CommonHelper::getInventoryType(1):CommonHelper::getInventoryType(2),
                        CommonHelper::filterCsvInteger($inventory_list[$i]->product_barcode),
                        $inventory_list[$i]->product_description,
                        (!empty($inventory_list[$i]->product_created_at))?date('d-m-Y',strtotime($inventory_list[$i]->product_created_at)):'',
                        (!empty($inventory_list[$i]->product_updated_at))?date('d-m-Y',strtotime($inventory_list[$i]->product_updated_at)):'',
                        $inventory_list[$i]->vendor_base_price,
                        $inventory_list[$i]->vendor_gst_percent,
                        $inventory_list[$i]->vendor_gst_amount,
                        $inventory_list[$i]->base_price,
                        $inventory_list[$i]->sale_price,
                        $inventory_list[$i]->store_base_rate,
                        $inventory_list[$i]->store_gst_percent,
                        $inventory_list[$i]->store_gst_amount,
                        $inventory_list[$i]->store_base_price,
                        (!empty($inventory_list[$i]->intake_date))?date('d-m-Y',strtotime($inventory_list[$i]->intake_date)):'',
                        (!empty($inventory_list[$i]->store_assign_date))?date('d-m-Y',strtotime($inventory_list[$i]->store_assign_date)):'',
                        (!empty($inventory_list[$i]->store_intake_date))?date('d-m-Y',strtotime($inventory_list[$i]->store_intake_date)):'',
                        (!empty($inventory_list[$i]->store_sale_date))?date('d-m-Y',strtotime($inventory_list[$i]->store_sale_date)):'',
                        (!empty($inventory_list[$i]->updated_at))?date('d-m-Y',strtotime($inventory_list[$i]->updated_at)):'',
                        $inventory_list[$i]->pos_order_no,
                        !empty($inventory_list[$i]->pos_total_price)?round($inventory_list[$i]->pos_total_price,2):'',
                        $inventory_list[$i]->pos_total_items,
                        isset($stores[$inventory_list[$i]->pco_store_id])?$stores[$inventory_list[$i]->pco_store_id]['store_name']:'',
                        $inventory_list[$i]->salutation.' '.$inventory_list[$i]->customer_name,
                        $inventory_list[$i]->customer_phone,
                        !empty($inventory_list[$i]->store_user_id)?$users[$inventory_list[$i]->store_user_id]->name:'',
                        $inventory_list[$i]->bags_count,
                        !empty($inventory_list[$i]->pcod_sale_price)?round($inventory_list[$i]->pcod_sale_price,2):'',
                        !empty($inventory_list[$i]->pcod_discount_percent)?round($inventory_list[$i]->pcod_discount_percent,2).'%':'',
                        !empty($inventory_list[$i]->pcod_discount_amount)?round($inventory_list[$i]->pcod_discount_amount,2):'',
                        !empty($inventory_list[$i]->pcod_gst_percent)?round($inventory_list[$i]->pcod_gst_percent,2).'%':'',
                        !empty($inventory_list[$i]->pcod_gst_amount)?round($inventory_list[$i]->pcod_gst_amount,2):'',
                        !empty($inventory_list[$i]->pcod_net_price)?round($inventory_list[$i]->pcod_net_price,2):'',
                        $inventory_list[$i]->store_staff_name,
                        (!empty($inventory_list[$i]->pco_created_at))?date('d-m-Y',strtotime($inventory_list[$i]->pco_created_at)):''];
                        
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('admin/report_inventory_raw',array('inventory_list'=>$inventory_list,'error_message'=>'','stores'=>$stores,'vendors'=>$vendors,'users'=>$users,
            'po_list'=>$po_list,'design_items'=>$design_items,'size_list'=>$size_list,'status_list'=>$status_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('admin/report_inventory_raw',array('error_message'=>$e->getMessage(),'inventory_list'=>array()));
        }
    }
    
    function vendorSkuInventoryReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $vendor_id = '';
            $poi_ids =  $inventory_data = $inv_qc_returned = $inv_grn = $inv_qc_defective = $inventory_total_data = $vendor_total_data = array();
            $images_list  = $vendor_id_list = array();
            $min_po_item_id = $max_po_item_id = 0;
            $rec_per_page = 100;
            
            // This query fetch po items which are used in following queries
            //->where('poi.qty_received','>',0) is commented as some records are defective and returned. Their received count is 0
            // SKU source is used as purchase_order_items table as it has SKUs according to vendor. One sku may be supplied by multiple vendors 
            //pos_product_master_inventory is not used as it is slowing query
            
            $sku_list = \DB::table('purchase_order_items as poi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'poi.item_master_id')        
            //->join('pos_product_master_inventory as ppmi','ppmi.po_item_id', '=', 'poi.id')       
            ->join('purchase_order as po','po.id', '=', 'poi.order_id')                     
            ->wherein('po.type_id',[3,5])        
            //->where('ppmi.product_status','>',0)                
            //->where('poi.qty_received','>',0)        
            ->where('ppm.is_deleted',0)
            //->where('ppmi.is_deleted',0)        
            ->where('poi.fake_inventory',0)
            ->where('ppm.fake_inventory',0);        
            //->where('ppmi.fake_inventory',0);                
           
            
            // Vendor filter
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $vendor_id = trim($data['v_id']);
            }
            
            // if logged in user is vendor
            if($user->user_type == 15){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->first();
                $vendor_id = $vendor_data->id;
            }
            
            if(!empty($vendor_id)){
                $sku_list = $sku_list->where('poi.vendor_id',$vendor_id);
            }
            
            $sku_list = $sku_list
            ->groupBy('poi.id')
            ->orderBy('poi.id')        
            ->selectRaw('poi.id as po_item_id,poi.rate,poi.gst_percent,poi.vendor_id,poi.vendor_sku,ppm.vendor_product_sku,ppm.id as product_id,MAX(ppm.id) as max_product_id');        
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $sku_list = $sku_list->offset($start)->limit($limit)->get()->toArray();
                
                if(!empty($sku_list)){
                    $min_po_item_id = $sku_list[0]->po_item_id;
                    $max_po_item_id = $sku_list[count($sku_list)-1]->po_item_id;
                }
            }else{
                $sku_list = $sku_list->paginate($rec_per_page);
                
                if($sku_list->total() > 0){
                    $min_po_item_id = $sku_list[0]->po_item_id;
                    $max_po_item_id = $sku_list[count($sku_list)-1]->po_item_id;

                    $min_prod_id = $sku_list[0]->product_id;
                    $max_prod_id = $sku_list[count($sku_list)-1]->max_product_id;

                    $images = \DB::table('pos_product_images')->where('product_id','>=',$min_prod_id)->where('product_id','<=',$max_prod_id)
                    ->where('image_type','front')->where('is_deleted',0)->select('id','product_id','image_name','image_type')
                    ->get()->toArray();

                    for($i=0;$i<count($images);$i++){
                        $images_list[$images[$i]->product_id] = $images[$i];
                    }
                }
            }
            
            $poi_id_where_1 = 'ppmi.po_item_id >= '.$min_po_item_id.' AND ppmi.po_item_id <= '.$max_po_item_id;
            
            
            /** Inventory Status data start **/
            // Fetch product status for inventory data with po item ids
            $inventory_list = \DB::table('pos_product_master_inventory as ppmi')
            ->whereRaw($poi_id_where_1)   
            ->where('ppmi.product_status','>',0)        
            ->where('ppmi.is_deleted',0)        
            ->where('ppmi.fake_inventory',0)
            ->where('ppmi.qc_status',1)        
            ->groupByRaw('ppmi.po_item_id,ppmi.product_status')        
            ->selectRaw("ppmi.po_item_id,ppmi.product_status,COUNT(ppmi.id) as inv_count")        
            ->get()->toArray();
            
            // Create indexed array with item_id and status
            for($i=0;$i<count($inventory_list);$i++){
                $po_item_id = $inventory_list[$i]->po_item_id;
                $product_status = $inventory_list[$i]->product_status;
                $inventory_data[$po_item_id.'_'.$product_status] = $inventory_list[$i]->inv_count;
            }
            
            /** Inventory Status data end **/
            
            /** Inventory GRN data start **/
            // GRN data for po item ids
            $inv_list_grn = \DB::table('pos_product_master_inventory as ppmi')
            ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_qc_items.grn_qc_id')
            ->where('po_grn_qc.type','grn')   
            ->whereRaw($poi_id_where_1)   
            ->where('po_qc_items.is_deleted',0)                    
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('po_grn_qc.is_deleted',0)
            ->groupByRaw('ppmi.po_item_id')
            ->selectRaw("ppmi.po_item_id,COUNT(ppmi.id) as inv_count")        
            ->get()->toArray();
            
            for($i=0;$i<count($inv_list_grn);$i++){
                $po_item = $inv_list_grn[$i];
                $inv_grn[$po_item->po_item_id] = $po_item->inv_count;
            }
            
            /** Inventory GRN data end **/
            
            /** Inventory Defective data start **/
            // QC Returned data for po item ids
            $inv_list_defective = \DB::table('pos_product_master_inventory as ppmi')
            ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_qc_items.grn_qc_id')
            ->where('po_grn_qc.type','qc')   
            ->where('po_qc_items.qc_status',2)              
            ->whereRaw($poi_id_where_1)   
            ->where('po_qc_items.is_deleted',0)                    
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('po_grn_qc.is_deleted',0)
            ->groupByRaw('ppmi.po_item_id')
            ->selectRaw("ppmi.po_item_id,COUNT(ppmi.id) as inv_count")        
            ->get()->toArray();
            
            for($i=0;$i<count($inv_list_defective);$i++){
                $po_item = $inv_list_defective[$i];
                $inv_qc_defective[$po_item->po_item_id] = $po_item->inv_count;
            }
            
            /** Inventory Defective data end **/
            
            /** Inventory Return data start **/
            // QC Returned data for po item ids
            $inv_list_returned = \DB::table('pos_product_master_inventory as ppmi')
            ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_qc_items.grn_qc_id')
            ->where('po_grn_qc.type','qc_return')   
            ->whereRaw($poi_id_where_1)   
            ->where('po_qc_items.is_deleted',0)                    
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('po_grn_qc.is_deleted',0)
            ->groupByRaw('ppmi.po_item_id')
            ->selectRaw("ppmi.po_item_id,COUNT(ppmi.id) as inv_count")        
            ->get()->toArray();
            
            for($i=0;$i<count($inv_list_returned);$i++){
                $po_item = $inv_list_returned[$i];
                $inv_qc_returned[$po_item->po_item_id] = $po_item->inv_count;
            }
            
            /** Inventory Return data end **/
            
            $vendor_list = CommonHelper::getVendorsList();
            for($i=0;$i<count($vendor_list);$i++){
                $vendor_id_list[$vendor_list[$i]['id']] = $vendor_list[$i];
            }
            
            // Merge arrays
            for($i=0;$i<count($sku_list);$i++){
                $po_item_id = $sku_list[$i]->po_item_id;
                $sku_list[$i]->inv_status_1 = (isset($inventory_data[$po_item_id.'_1']))?$inventory_data[$po_item_id.'_1']:0;
                $sku_list[$i]->inv_status_2 = (isset($inventory_data[$po_item_id.'_2']))?$inventory_data[$po_item_id.'_2']:0;
                $sku_list[$i]->inv_status_3 = (isset($inventory_data[$po_item_id.'_3']))?$inventory_data[$po_item_id.'_3']:0;
                $sku_list[$i]->inv_status_4 = (isset($inventory_data[$po_item_id.'_4']))?$inventory_data[$po_item_id.'_4']:0;
                $sku_list[$i]->inv_status_5 = (isset($inventory_data[$po_item_id.'_5']))?$inventory_data[$po_item_id.'_5']:0;
                $sku_list[$i]->inv_status_6 = (isset($inventory_data[$po_item_id.'_6']))?$inventory_data[$po_item_id.'_6']:0;
                $sku_list[$i]->inv_status_7 = (isset($inventory_data[$po_item_id.'_7']))?$inventory_data[$po_item_id.'_7']:0;
                $sku_list[$i]->qc_defective = (isset($inv_qc_defective[$po_item_id]))?$inv_qc_defective[$po_item_id]:0;
                $sku_list[$i]->qc_returned = (isset($inv_qc_returned[$po_item_id]))?$inv_qc_returned[$po_item_id]:0;
                $sku_list[$i]->inv_grn = (isset($inv_grn[$po_item_id]))?$inv_grn[$po_item_id]:0;
                $sku_list[$i]->vendor_name = isset($vendor_id_list[$sku_list[$i]->vendor_id])?$vendor_id_list[$sku_list[$i]->vendor_id]['name']:'';
            }
            
            // Total data is fetched if vendor filter is added or vendor is logged in
            if(!empty($vendor_id)){
                // Subquery is added as po_items table join is making query slow
                $vendor_id_where = 'ppmi.vendor_id ='.$vendor_id;
                
                /** Inventory Status data start **/
                // Total Inventory status count for vendor id
                $inventory_list = \DB::table('pos_product_master_inventory as ppmi')
                ->whereRaw($vendor_id_where)        
                ->where('ppmi.product_status','>',0)        
                ->where('ppmi.is_deleted',0)        
                ->where('ppmi.fake_inventory',0)
                ->where('ppmi.qc_status',1)        
                ->groupByRaw('ppmi.product_status')        
                ->selectRaw("ppmi.product_status,COUNT(ppmi.id) as inv_count")        
                ->get()->toArray();

                // Create indexed array with item_id and status
                for($i=0;$i<count($inventory_list);$i++){
                    $product_status = $inventory_list[$i]->product_status;
                    $inventory_total_data['inv_status_'.$product_status] = $inventory_list[$i]->inv_count;
                }
                
                for($i=1;$i<=7;$i++){
                    if(!isset($inventory_total_data['inv_status_'.$i])){
                        $inventory_total_data['inv_status_'.$i] = 0;
                    }
                }

                /** Inventory Status data end **/
                
                /** Inventory GRN data start **/
                // Total Inventory GRN count for vendor id
                $inv_list_grn = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_qc_items.grn_qc_id')
                ->where('po_grn_qc.type','grn')   
                ->whereRaw($vendor_id_where)         
                ->where('po_qc_items.is_deleted',0)                    
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('po_grn_qc.is_deleted',0)
                ->selectRaw('COUNT(ppmi.id) as inv_count')        
                ->first();
                
                $vendor_total_data['grn_qty'] = $inv_list_grn->inv_count;
                
                /** Inventory GRN data end **/
                
                /** Inventory Defective data start **/
                //\DB::enableQueryLog();
                // Total Inventory qc defective count for vendor id
                $inv_list_defective = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_qc_items.grn_qc_id')
                ->where('po_grn_qc.type','qc')   
                ->where('po_qc_items.qc_status',2)              
                ->whereRaw($vendor_id_where)         
                ->where('po_qc_items.is_deleted',0)                    
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('po_grn_qc.is_deleted',0)
                ->selectRaw('COUNT(ppmi.id) as inv_count')        
                ->first();
                //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
                $vendor_total_data['qc_defective'] = $inv_list_defective->inv_count;

                /** Inventory Defective data end **/
                
                /** Inventory Return data start **/
                // Total Inventory qc return count for vendor id
                $inv_list_returned = \DB::table('pos_product_master_inventory as ppmi')
                ->join('purchase_order_grn_qc_items as po_qc_items','ppmi.id', '=', 'po_qc_items.inventory_id')        
                ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id','=','po_qc_items.grn_qc_id')
                ->where('po_grn_qc.type','qc_return')   
                ->whereRaw($vendor_id_where)   
                ->where('po_qc_items.is_deleted',0)                    
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('po_grn_qc.is_deleted',0)
                ->selectRaw('COUNT(ppmi.id) as inv_count')        
                ->first();

                $vendor_total_data['qc_returned'] = $inv_list_returned->inv_count;

                /** Inventory Return data end **/
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=vendor_sku_inventory_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('SNo','Vendor','SKU','Vendor SKU','Cost Rate','GST Amount','Cost Price','GRN QTY','QC Defective','QC Returned','Warehouse Qty','Store Qty','Sold Qty','Transit Transfer','Transit Return','Returned to Vendor','Total','Balance Qty');
                
                $callback = function() use ($sku_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total_array = array('grn_qty'=>0,'w_qty'=>0,'store_qty'=>0,'sold_qty'=>0,'total_qty'=>0,'bal_qty'=>0,'transit_transfer_qty'=>0,'transit_return_qty'=>0,'return_vendor_qty'=>0,'qc_defective_qty'=>0,'qc_returned_qty'=>0);
                    
                    for($i=0;$i<count($sku_list);$i++){
                        $gst_amt = round($sku_list[$i]->rate*($sku_list[$i]->gst_percent/100),2);
                        $total_qty = $sku_list[$i]->inv_status_1+$sku_list[$i]->inv_status_2+$sku_list[$i]->inv_status_3+$sku_list[$i]->inv_status_4+$sku_list[$i]->inv_status_5+$sku_list[$i]->inv_status_6+$sku_list[$i]->inv_status_7;
                        $bal_qty = ($sku_list[$i]->inv_grn-$sku_list[$i]->qc_defective)-$total_qty;
                        
                        $array = array(($i+1),$sku_list[$i]->vendor_name,$sku_list[$i]->vendor_sku,$sku_list[$i]->vendor_product_sku,$sku_list[$i]->rate,$gst_amt,$sku_list[$i]->rate+$gst_amt,
                        $sku_list[$i]->inv_grn,$sku_list[$i]->qc_defective,$sku_list[$i]->qc_returned,$sku_list[$i]->inv_status_1+$sku_list[$i]->inv_status_2,$sku_list[$i]->inv_status_4,$sku_list[$i]->inv_status_5,
                        $sku_list[$i]->inv_status_3,$sku_list[$i]->inv_status_6,$sku_list[$i]->inv_status_7,$total_qty,$bal_qty);
                        
                        fputcsv($file, $array);
                        
                        $total_array['grn_qty']+= $sku_list[$i]->inv_grn; 
                        $total_array['qc_defective_qty']+= $sku_list[$i]->qc_defective;
                        $total_array['qc_returned_qty']+= $sku_list[$i]->qc_returned;
                        $total_array['w_qty']+= $sku_list[$i]->inv_status_1+$sku_list[$i]->inv_status_2; 
                        $total_array['store_qty']+= $sku_list[$i]->inv_status_4; 
                        $total_array['sold_qty']+= $sku_list[$i]->inv_status_5; 
                        $total_array['transit_transfer_qty']+= $sku_list[$i]->inv_status_3;
                        $total_array['transit_return_qty']+= $sku_list[$i]->inv_status_6;
                        $total_array['return_vendor_qty']+= $sku_list[$i]->inv_status_7;
                        $total_array['total_qty']+= $total_qty; 
                        $total_array['bal_qty']+= $bal_qty; 
                    }
                    
                    $array = array('Total','','','','','','',$total_array['grn_qty'],$total_array['qc_defective_qty'],$total_array['qc_returned_qty'],$total_array['w_qty'],$total_array['store_qty'],$total_array['sold_qty'],$total_array['transit_transfer_qty'],$total_array['transit_return_qty'],$total_array['return_vendor_qty'],$total_array['total_qty'],$total_array['bal_qty']);
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            
            $page = (isset($data['page']))?$data['page']:1;
            $sno = (($page-1)*$rec_per_page)+1;
            
            return view('admin/vendor_sku_inventory_report',array('sku_list'=>$sku_list,'vendor_list'=>$vendor_list,'error_message'=>$error_message,'sno'=>$sno,'user'=>$user,
             'inventory_total_data'=>$inventory_total_data,'vendor_total_data'=>$vendor_total_data,'vendor_id'=>$vendor_id,'vendor_id_list'=>$vendor_id_list,'images_list'=>$images_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE_INVENTORY_BALANCE_REPORT',__FUNCTION__,__FILE__);
            return view('admin/vendor_sku_inventory_report',array('error_message'=>$e->getMessage().', Line: '.$e->getLine()));
        }
    }
    
    function storeSkuInventoryReport(Request $request){
        try{
            
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $user = Auth::user();
            $error_message = $store_id = $vendor_id = '';
            $inv_list = $skus = $store_to_store_rec_sku_list = $store_id_list = $vendor_id_list = $vendor_list = $store_list = $images_list = $inventory_status_sku_list = array();
            $store_id_list = $wh_to_store_sku_list = $store_to_wh_sku_list = $pos_orders_sku_list = $store_to_store_sku_list = $store_to_wh_comp_sku_list = $search_array = array();
            $total_data = ['wh_to_store'=>0,'store_to_wh'=>0,'store_to_wh_comp'=>0,'store_to_store'=>0,'store_to_store_rec'=>0,'inv_sold_count'=>0];
            $max_po_item_id = $min_po_item_id = 0;
            $page_action = '';
            $sku_per_page = 20;
            
            $store_list = CommonHelper::getStoresList();
            $vendor_list = CommonHelper::getVendorsList();
            
            if($user->user_type == 15){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->first();
                $vendor_id = $vendor_data->id;
            }elseif(isset($data['v_id']) && !empty($data['v_id'])){
                $vendor_id = trim($data['v_id']);
            }
                
            /** SKU List Start **/
            //$sku_list = CommonHelper::getSKUList();
            
            $sku_list = \DB::table('purchase_order_items as poi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'poi.item_master_id')        
            ->join('purchase_order as po','po.id', '=', 'poi.order_id')            
            ->wherein('po.type_id',[3,5])        
            ->where('ppm.is_deleted',0)
            ->where('poi.fake_inventory',0)
            ->where('ppm.fake_inventory',0);        
                            
            if(isset($data['sku']) && !empty($data['sku'])){
                $search_array['sku'] = $sku = trim($data['sku']);
                $sku_list = $sku_list->whereRaw("(ppm.vendor_product_sku = '$sku' OR ppm.product_sku = '$sku' )");
            }
            
            if(!empty($vendor_id)){
                $search_array['vendor_id'] = $vendor_id;
                $sku_list = $sku_list->where('poi.vendor_id',$vendor_id);
            }
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $search_array['store_id'] =  $store_id = trim($data['s_id']);
                
                // For store filter, only inventory which is received by store is displayed
                $sku_list = $sku_list->join('store_products_demand_inventory as spdi','poi.id', '=', 'spdi.po_item_id')   
                ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')       
                ->wherein('spd.demand_type',['inventory_push','inventory_transfer_to_store'])
                ->wherein('spd.demand_status',['store_loading','store_loaded'])        
                ->where('spd.store_id',$store_id);        
            }
            
            if(isset($data['inv_type']) && !empty($data['inv_type'])){
                if($data['inv_type'] == 1)
                    $sku_list = $sku_list->where('ppmi.arnon_inventory',0);
                if($data['inv_type'] == 2)
                    $sku_list = $sku_list->where('ppmi.arnon_inventory',1);
            }
            
            $sku_list = $sku_list
            ->groupBy('poi.id')
            ->orderBy('poi.id')        
            ->selectRaw('poi.id as po_item_id,ppm.product_sku,ppm.vendor_product_sku,ppm.id as product_id,poi.vendor_id,MAX(ppm.id) as max_product_id');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $page_action = 'download_csv';
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $sku_list = $sku_list->offset($start)->limit($limit)->get();
                
                if(!empty($sku_list)){
                    $min_po_item_id = $sku_list[0]->po_item_id;
                    $max_po_item_id = $sku_list[count($sku_list)-1]->po_item_id;
                }
            }else{
                $sku_list = $sku_list->paginate($sku_per_page); 
                
                // Fetch images
                if($sku_list->total() > 0){
                    $min_po_item_id = $sku_list[0]->po_item_id;
                    $max_po_item_id = $sku_list[count($sku_list)-1]->po_item_id;
                    
                    $min_prod_id = $sku_list[0]->product_id;
                    $max_prod_id = $sku_list[count($sku_list)-1]->max_product_id;

                    $images = \DB::table('pos_product_images')->where('product_id','>=',$min_prod_id)->where('product_id','<=',$max_prod_id)
                    ->where('image_type','front')->where('is_deleted',0)->select('id','product_id','image_name','image_type')
                    ->get();
                    
                    for($i=0;$i<count($images);$i++){
                        $images_list[$images[$i]->product_id] = $images[$i];
                    }
                }
            }
            
            /** SKU List End **/
            
            // Data is fetched from demands if skus are found in page or in csv download
            if($max_po_item_id > 0){
            
                /** Warehouse to Store Receive Start **/
                
                $wh_to_store_list = CommonHelper::getWHToStoreList();
                $wh_to_store_list = $wh_to_store_list->where('spdi.receive_status',1);

                // Demands data linked to store are fetched
                if(!empty($store_id)){
                    $wh_to_store_list->where('spd.store_id',$store_id);
                }
                
                if(!empty($vendor_id)){
                    $wh_to_store_list->where('spdi.vendor_id',$vendor_id);
                }
                
                if(empty($page_action)){
                    $wh_to_store_list_total = clone $wh_to_store_list;
                    $wh_to_store_list_total = $wh_to_store_list_total->selectRaw('COUNT(spdi.id) as inv_cnt')->first();
                    $total_data['wh_to_store'] = $wh_to_store_list_total->inv_cnt;
                }
                
                $wh_to_store_list = $wh_to_store_list->where('spdi.po_item_id','>=',$min_po_item_id)->where('spdi.po_item_id','<=',$max_po_item_id);
                $wh_to_store_list = $wh_to_store_list->groupByRaw('spdi.po_item_id,spdi.store_id')->selectRaw('spdi.po_item_id,spdi.store_id,COUNT(spdi.id) as inv_count')->get();

                for($i=0;$i<count($wh_to_store_list);$i++){
                    $key = $wh_to_store_list[$i]->store_id.'__'.$wh_to_store_list[$i]->po_item_id;
                    $wh_to_store_sku_list[$key] = $wh_to_store_list[$i]->inv_count;
                }
                
                /** Warehouse to Store Receive End **/

                /** Store to Warehouse Return Start **/
                // It includes both inventory types, which is received by warehouse and which is not received by warehouse

                $store_to_wh_list = CommonHelper::getStoreToWHList();
                $store_to_wh_list = $store_to_wh_list->where('spdi.transfer_status',1);

                if(!empty($store_id)){
                    $store_to_wh_list->where('spd.store_id',$store_id);
                }
                
                if(!empty($vendor_id)){
                    $store_to_wh_list->where('spdi.vendor_id',$vendor_id);
                }
                
                if(empty($page_action)){
                    $store_to_wh_list_total = clone $store_to_wh_list;
                    $store_to_wh_list_total = $store_to_wh_list_total->selectRaw('COUNT(spdi.id) as inv_cnt')->first();
                    $total_data['store_to_wh'] = $store_to_wh_list_total->inv_cnt;
                }
                
                $store_to_wh_list = $store_to_wh_list->where('spdi.po_item_id','>=',$min_po_item_id)->where('spdi.po_item_id','<=',$max_po_item_id);
                $store_to_wh_list = $store_to_wh_list->groupByRaw('spdi.po_item_id,spdi.store_id')->selectRaw('spdi.po_item_id,spdi.store_id,COUNT(spdi.id) as inv_count')->get();

                for($i=0;$i<count($store_to_wh_list);$i++){
                    $key = $store_to_wh_list[$i]->store_id.'__'.$store_to_wh_list[$i]->po_item_id;
                    $store_to_wh_sku_list[$key] = $store_to_wh_list[$i]->inv_count;
                }

                /** Store to Warehouse Return End **/

                /** Store to Warehouse by complete inventory return Start **/
                // This demand is not returned back to store by warehouse
                // It includes both inventory types,  which is received by warehouse and which is not received by warehouse

                $store_to_wh_comp_list = CommonHelper::getStoreToWhCompleteList(['warehouse_dispatched']);
                $store_to_wh_comp_list = $store_to_wh_comp_list->where('spdi.transfer_status',1);

                if(!empty($store_id)){
                    $store_to_wh_comp_list->where('spd.store_id',$store_id);
                }
                
                if(!empty($vendor_id)){
                    $store_to_wh_comp_list->where('spdi.vendor_id',$vendor_id);
                }
                
                if(empty($page_action)){
                    $store_to_wh_comp_list_total = clone $store_to_wh_comp_list;
                    $store_to_wh_comp_list_total = $store_to_wh_comp_list_total->selectRaw('COUNT(spdi.id) as inv_cnt')->first();
                    $total_data['store_to_wh_comp'] = $store_to_wh_comp_list_total->inv_cnt;
                }
                
                $store_to_wh_comp_list = $store_to_wh_comp_list->where('spdi.po_item_id','>=',$min_po_item_id)->where('spdi.po_item_id','<=',$max_po_item_id);

                $store_to_wh_comp_list = $store_to_wh_comp_list->groupByRaw('spdi.po_item_id,spdi.store_id')->selectRaw('spdi.po_item_id,spdi.store_id,COUNT(spdi.id) as inv_count')->get();

                for($i=0;$i<count($store_to_wh_comp_list);$i++){
                    $key = $store_to_wh_comp_list[$i]->store_id.'__'.$store_to_wh_comp_list[$i]->po_item_id;
                    $store_to_wh_comp_sku_list[$key] = $store_to_wh_comp_list[$i]->inv_count;
                }

                /** Store to Warehouse by complete inventory return End **/

                /** Other Store to this Store Receive Start **/
                // It includes both inventory type which is received by store from other store

                $store_to_store_rec_list = CommonHelper::getStoreToStoreList();
                $store_to_store_rec_list = $store_to_store_rec_list->where('spdi.transfer_status',1)
                ->where('spdi.receive_status',1);

                if(!empty($store_id)){
                    $store_to_store_rec_list->where('spd.store_id',$store_id);
                }
                
                if(!empty($vendor_id)){
                    $store_to_store_rec_list->where('spdi.vendor_id',$vendor_id);
                }
                
                if(empty($page_action)){
                    $store_to_store_rec_list_total = clone $store_to_store_rec_list;
                    $store_to_store_rec_list_total = $store_to_store_rec_list_total->selectRaw('COUNT(spdi.id) as inv_cnt')->first();
                    $total_data['store_to_store_rec'] = $store_to_store_rec_list_total->inv_cnt;
                }
                
                $store_to_store_rec_list = $store_to_store_rec_list->where('spdi.po_item_id','>=',$min_po_item_id)->where('spdi.po_item_id','<=',$max_po_item_id);

                $store_to_store_rec_list = $store_to_store_rec_list->groupByRaw('spdi.po_item_id,spdi.store_id')->selectRaw('spdi.po_item_id,spdi.store_id,COUNT(spdi.id) as inv_count')->get();

                for($i=0;$i<count($store_to_store_rec_list);$i++){
                    $key = $store_to_store_rec_list[$i]->store_id.'__'.$store_to_store_rec_list[$i]->po_item_id;
                    $store_to_store_rec_sku_list[$key] = $store_to_store_rec_list[$i]->inv_count;
                }

                /** Other Store to this Store Receive End **/

                /** This Store to other Store Transfer Start **/
                // It includes both inventory types, which is received by other store and which is not received by other store

                $store_to_store_list = CommonHelper::getStoreToStoreList();
                $store_to_store_list = $store_to_store_list->where('spdi.transfer_status',1);

                if(!empty($store_id)){
                    $store_to_store_list->where('spd.from_store_id',$store_id);
                }
                
                if(!empty($vendor_id)){
                    $store_to_store_list->where('spdi.vendor_id',$vendor_id);
                }
                
                if(empty($page_action)){
                    $store_to_store_list_total = clone $store_to_store_list;
                    $store_to_store_list_total = $store_to_store_list_total->selectRaw('COUNT(spdi.id) as inv_cnt')->first();
                    $total_data['store_to_store'] = $store_to_store_list_total->inv_cnt;
                }
                
                $store_to_store_list = $store_to_store_list->where('spdi.po_item_id','>=',$min_po_item_id)->where('spdi.po_item_id','<=',$max_po_item_id);

                $store_to_store_list = $store_to_store_list->groupByRaw('spdi.po_item_id,spd.from_store_id')->selectRaw('spdi.po_item_id,spd.from_store_id as store_id,spd.id,COUNT(spdi.id) as inv_count')->get();

                for($i=0;$i<count($store_to_store_list);$i++){
                    $key = $store_to_store_list[$i]->store_id.'__'.$store_to_store_list[$i]->po_item_id;
                    $store_to_store_sku_list[$key] = $store_to_store_list[$i]->inv_count;
                }

                /** This Store to other Store Transfer End **/

                /** SKU Sold Start **/

                $pos_orders_list = CommonHelper::getPosOrdersList();
                
                if(!empty($store_id)){
                    $pos_orders_list->where('pcod.store_id',$store_id);
                }
                
                if(!empty($vendor_id)){
                    $pos_orders_list->where('pcod.vendor_id',$vendor_id);
                }
                
                if(empty($page_action)){
                    $pos_orders_list_total = clone $pos_orders_list;
                    $pos_orders_list_total = $pos_orders_list_total->selectRaw('SUM(pcod.product_quantity) as inv_sold_count')->first();
                    $total_data['inv_sold_count'] = $pos_orders_list_total->inv_sold_count;
                }
               
                $pos_orders_list = $pos_orders_list->where('pcod.po_item_id','>=',$min_po_item_id)->where('pcod.po_item_id','<=',$max_po_item_id);

                $pos_orders_list = $pos_orders_list->groupByRaw('pcod.po_item_id,pcod.store_id')->selectRaw('pcod.po_item_id,pcod.store_id,SUM(pcod.product_quantity) as inv_sold_count,SUM(pcod.net_price) as inv_sold_net_price')->get();

                for($i=0;$i<count($pos_orders_list);$i++){
                    $key = $pos_orders_list[$i]->store_id.'__'.$pos_orders_list[$i]->po_item_id;
                    $pos_orders_sku_list[$key] = $pos_orders_list[$i];
                }

                /** SKU Sold End **/
                
                
                $inventory_status_list = \DB::table('pos_product_master_inventory as ppmi')
                ->where('ppmi.product_status','=',4)        
                ->where('ppmi.is_deleted',0)        
                ->where('ppmi.fake_inventory',0)
                ->where('ppmi.qc_status',1);        
                
                if(!empty($store_id)){
                    $inventory_status_list->where('ppmi.store_id',$store_id);
                }
                
                if(!empty($vendor_id)){
                    $inventory_status_list->where('ppmi.vendor_id',$vendor_id);
                }
                
                if(empty($page_action)){
                    $inventory_status_list_total = clone $inventory_status_list;
                    $inventory_status_list_total = $inventory_status_list_total->selectRaw('COUNT(ppmi.id) as inv_count')->first();
                    $total_data['inv_in_store_count'] = $inventory_status_list_total->inv_count;
                }
                
                $inventory_status_list = $inventory_status_list->where('ppmi.po_item_id','>=',$min_po_item_id)->where('ppmi.po_item_id','<=',$max_po_item_id);
                
                $inventory_status_list = $inventory_status_list->groupByRaw('ppmi.po_item_id,ppmi.store_id')        
                ->selectRaw('ppmi.po_item_id,ppmi.store_id,COUNT(ppmi.id) as inv_count')        
                ->get();

                // Create indexed array with item_id and status
                for($i=0;$i<count($inventory_status_list);$i++){
                    $key = $inventory_status_list[$i]->store_id.'__'.$inventory_status_list[$i]->po_item_id;
                    $inventory_status_sku_list[$key] = $inventory_status_list[$i];
                }
                

                for($i=0;$i<count($store_list);$i++){
                    $store_id_list[$store_list[$i]['id']] = $store_list[$i];
                }

                for($i=0;$i<count($vendor_list);$i++){
                    $vendor_id_list[$vendor_list[$i]['id']] = $vendor_list[$i];
                }

                for($i=0;$i<count($sku_list);$i++){
                    $count = 0;

                    for($q=0;$q<count($store_list);$q++){
                        $store_id1 = $store_list[$q]['id'];
                        $sku_id = $sku_list[$i]->po_item_id;
                        $key = $store_id1.'__'.$sku_id;

                        if(isset($wh_to_store_sku_list[$key]) || isset($store_to_store_rec_sku_list[$key])){
                            $inv_array = array('product_sku'=>$sku_list[$i]->product_sku,'store_id'=>$store_id1,'vendor_product_sku'=>$sku_list[$i]->vendor_product_sku,
                            'vendor_id'=>$sku_list[$i]->vendor_id,'product_id'=>$sku_list[$i]->product_id);

                            $inv_array['wh_to_store_qty'] = isset($wh_to_store_sku_list[$key])?$wh_to_store_sku_list[$key]:0;
                            $inv_array['store_to_wh_qty'] = isset($store_to_wh_sku_list[$key])?$store_to_wh_sku_list[$key]:0;
                            $inv_array['store_to_wh_qty']+= isset($store_to_wh_comp_sku_list[$key])?$store_to_wh_comp_sku_list[$key]:0;        

                            $inv_array['store_to_store_qty'] = isset($store_to_store_sku_list[$key])?$store_to_store_sku_list[$key]:0;
                            $inv_array['store_to_store_rec_qty'] = isset($store_to_store_rec_sku_list[$key])?$store_to_store_rec_sku_list[$key]:0;

                            $inv_array['inv_sold_count'] = isset($pos_orders_sku_list[$key])?$pos_orders_sku_list[$key]->inv_sold_count:0;
                            $inv_array['inv_sold_net_price'] = isset($pos_orders_sku_list[$key])?$pos_orders_sku_list[$key]->inv_sold_net_price:0;
                            $inv_array['inv_in_store_qty'] = isset($inventory_status_sku_list[$key])?$inventory_status_sku_list[$key]->inv_count:0;

                            $inv_list[] = $inv_array;
                            $count++;
                        }
                    }

                    // It is to display sku whose status > 0, but not assigned to any store
                    if($count == 0){
                       $inv_list[] = array('product_sku'=>$sku_list[$i]->product_sku,'store_id'=>0,'vendor_product_sku'=>$sku_list[$i]->vendor_product_sku,
                       'vendor_id'=>$sku_list[$i]->vendor_id,'wh_to_store_qty'=>0,'product_id'=>$sku_list[$i]->product_id,'store_to_store_rec_qty'=>0);
                    }
                }
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=store_sku_inventory_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('SNo','Vendor','SKU','Vendor SKU','Store Name','Store Code','Store Rec WH','Store Rec Store','Sold Qty','Store to WH','Store to Store','Balance','In Stores','Sold Net Price');
                
                $callback = function() use ($inv_list,$vendor_id_list,$store_id_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total_array = array('wh_to_store_qty'=>0,'store_to_store_rec_qty'=>0,'inv_sold_count'=>0,'store_to_wh_qty'=>0,'inv_sold_net_price'=>0,'bal_qty'=>0,'store_to_store_qty'=>0,'inv_in_store_qty'=>0);
                    
                    for($i=0;$i<count($inv_list);$i++){
                       $vendor_name = isset($vendor_id_list[$inv_list[$i]['vendor_id']])?$vendor_id_list[$inv_list[$i]['vendor_id']]['name']:'';
                        $array = array(($i+1),$vendor_name,$inv_list[$i]['product_sku'],$inv_list[$i]['vendor_product_sku']);
                        
                        if($inv_list[$i]['wh_to_store_qty'] > 0 || $inv_list[$i]['store_to_store_rec_qty'] > 0){
                            $bal_qty = ($inv_list[$i]['wh_to_store_qty']+$inv_list[$i]['store_to_store_rec_qty'])-($inv_list[$i]['inv_sold_count']+$inv_list[$i]['store_to_wh_qty']+$inv_list[$i]['store_to_store_qty']);
                            
                            $array[] = $store_id_list[$inv_list[$i]['store_id']]['store_name'];
                            $array[] = $store_id_list[$inv_list[$i]['store_id']]['store_id_code'];
                            $array[] = $inv_list[$i]['wh_to_store_qty'];
                            $array[] = $inv_list[$i]['store_to_store_rec_qty'];
                            $array[] = $inv_list[$i]['inv_sold_count'];
                            $array[] = $inv_list[$i]['store_to_wh_qty'];
                            $array[] = $inv_list[$i]['store_to_store_qty'];
                            $array[] = $bal_qty;
                            $array[] = $inv_list[$i]['inv_in_store_qty'];
                            $array[] = round($inv_list[$i]['inv_sold_net_price'],2);
                            
                            $total_array['wh_to_store_qty']+= $inv_list[$i]['wh_to_store_qty'];
                            $total_array['store_to_store_rec_qty']+= $inv_list[$i]['store_to_store_rec_qty']; 
                            $total_array['inv_sold_count']+= $inv_list[$i]['inv_sold_count']; 
                            $total_array['store_to_wh_qty']+= $inv_list[$i]['store_to_wh_qty'];
                            $total_array['store_to_store_qty']+= $inv_list[$i]['store_to_store_qty'];
                            $total_array['inv_sold_net_price']+= $inv_list[$i]['inv_sold_net_price'];
                            $total_array['inv_in_store_qty']+= $inv_list[$i]['inv_in_store_qty'];
                            $total_array['bal_qty']+= $bal_qty;
                            
                        }else{
                            $array[] = '';$array[] = '';$array[] = '';$array[] = '';$array[] = '';$array[] = '';$array[] = '';
                        }
                        
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','','','','',$total_array['wh_to_store_qty'],$total_array['store_to_store_rec_qty'],$total_array['inv_sold_count'],
                    $total_array['store_to_wh_qty'],$total_array['store_to_store_qty'],$total_array['bal_qty'],$total_array['inv_in_store_qty'],round($total_array['inv_sold_net_price'],2));
                    
                    fputcsv($file, $array);
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            $page = (isset($data['page']))?$data['page']:1;
            $sno = (($page-1)*$sku_per_page)+1;
            
            return view('admin/store_sku_inventory_report',array('sku_list'=>$sku_list,'inv_list'=>$inv_list,
            'vendor_list'=>$vendor_list,'error_message'=>$error_message,'sno'=>$sno,'store_list'=>$store_list,
            'user'=>$user,'store_id_list'=>$store_id_list,'vendor_id_list'=>$vendor_id_list,'images_list'=>$images_list,
            'total_data'=>$total_data,'search_array'=>$search_array));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE_INVENTORY_BALANCE_REPORT',__FUNCTION__,__FILE__);
            return view('admin/store_sku_inventory_report',array('error_message'=>$e->getMessage().', Line: '.$e->getLine().', '.$e->getFile()));
        }
    }
    
    function grnSkuReport(Request $request){
        try{ 
            $data = $request->all();
            $user = Auth::user();
            $rec_per_page  = 100;
            $inv_data = $po_qc_data = $sku_list_wh_data = $sku_list_store_data = $vendor_id_list = $category_id_list = $invoice_ids = $search_array = array();
            $vendor_data = $sub_vendor_list = [];
            $total_array = ['grn'=>0,'wh'=>0,'store'=>0];
            $page_action = $start_date = $end_date = '';
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            if(!empty($search_date['start_date']) && !empty($search_date['start_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            $sku_list = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')        
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')         
            ->join('purchase_order as po','po.id', '=', 'po_grn_qc.po_id')  
            ->join('pos_product_master_inventory as ppmi','po_grn_qc_items.inventory_id', '=', 'ppmi.id')        
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
            ->join('purchase_order_items as poi','poi.id', '=', 'ppmi.po_item_id')         
            ->where('po_grn_qc.type','grn')      
            ->where('ppmi.product_status','>',0)        
            ->where('ppmi.qc_status',1)          
            ->where('po_grn_qc.is_deleted',0)
            ->where('po_grn_qc_items.is_deleted',0)
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)        
            ->where('po.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)       
            ->where('ppm.fake_inventory',0);     
            
            // Vendor filter
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $search_array['vendor_id'] = trim($data['v_id']);
                $sku_list = $sku_list->where('po.vendor_id',trim($data['v_id']));
            }
            
            // PO filter
            if(isset($data['po_no']) && !empty($data['po_no'])){
                $search_array['po_no'] = trim($data['po_no']);
                $sku_list = $sku_list->where('po.order_no',trim($data['po_no']));
            }
            
            // Invoice filter
            if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                $search_array['invoice_no'] = trim($data['invoice_no']);
                $sku_list = $sku_list->where('pod.invoice_no',trim($data['invoice_no']));
            }
            
            // SKU filter
            if(isset($data['sku']) && !empty($data['sku'])){
                $search_array['sku'] = trim($data['sku']);
                $sku_list = $sku_list->where('ppm.product_sku',trim($data['sku']));
            }
            
            // Date filter
            if(!empty($start_date) && !empty($end_date)){
                $sku_list = $sku_list->whereRaw("DATE(po_grn_qc.created_at) >= '$start_date' AND DATE(po_grn_qc.created_at) <= '$end_date'");   
            }
            
            // Total data
            if(!empty($search_array)){
                $sku_list_total = clone $sku_list;
                $sku_list_total = $sku_list_total->selectRaw('COUNT(po_grn_qc_items.id) as inv_count')->first();
                $total_array['grn'] = $sku_list_total->inv_count;
            }
            
            // If vendor is logged in
            if($user->user_type == 15 ){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->where('is_deleted',0)->first();
                $sub_vendor_list = Vendor_detail::where('pid',$vendor_data->id)->get()->toArray();
                $sub_vendor_ids = array_column($sub_vendor_list,'id');
                
                // Sub vendor list also have vendor data to display all vendors in dropdown
                if(isset($data['sub_v_id']) && !empty($data['sub_v_id']) && (in_array($data['sub_v_id'],$sub_vendor_ids) || $data['sub_v_id'] == $vendor_data->id) ){
                    $sku_list = $sku_list->where('po.vendor_id',$data['sub_v_id']);
                }else{
                    $vendor_ids = array_merge([$vendor_data->id],$sub_vendor_ids);
                    $sku_list = $sku_list->wherein('po.vendor_id',$vendor_ids);
                }
            }
            
            $sku_list = $sku_list->groupByRaw('po_grn_qc.id,ppm.product_sku_id')                
            ->selectRaw('po_grn_qc.id as grn_id,po_grn_qc.grn_no,pod.invoice_no,pod.id as invoice_id,
            po.order_no,ppm.id as product_id,ppm.product_sku,po_grn_qc.created_at as grn_date,po.vendor_id,
            po.category_id as po_category_id,ppm.product_sku_id,poi.rate as poi_rate,poi.gst_percent as poi_gst_percent,COUNT(po_grn_qc_items.id) as inv_count')        
            ->orderBy('pod.id');  
            
            // Download paging or page paging
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $page_action = 'download_csv';
                $rec_count = trim($data['report_rec_count']);
                $rec_count_arr = explode('_',$rec_count);
                $start = $rec_count_arr[0];
                $start = $start-1;
                $end = $rec_count_arr[1];
                $limit = $end-$start;
                $sku_list = $sku_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $sku_list = $sku_list->paginate($rec_per_page);
            }
           
            for($i=0;$i<count($sku_list);$i++){
                $invoice_ids[] = $sku_list[$i]->invoice_id;
            }
            
            if(!empty($invoice_ids)){
                $sku_list_wh_store = \DB::table('purchase_order_grn_qc as po_grn_qc')
                ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'po_grn_qc_items.inventory_id') 
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->join('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')   
                ->join('purchase_order as po','po.id', '=', 'pod.po_id')         
                ->where('po_grn_qc.type','grn')  
                ->wherein('ppmi.product_status',[1,2,4])        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->where('ppmi.qc_status',1)
                ->where('ppmi.fake_inventory',0)        
                ->where('po_grn_qc.is_deleted',0)        
                ->where('po_grn_qc_items.is_deleted',0) 
                ->where('ppm.fake_inventory',0)
                ->where('po.fake_inventory',0)        
                ->where('ppm.is_deleted',0)
                ->where('pod.is_deleted',0);    

                // Vendor filter
                if(isset($data['v_id']) && !empty($data['v_id'])){
                    $sku_list_wh_store = $sku_list_wh_store->where('po.vendor_id',trim($data['v_id']));
                }

                // PO filter
                if(isset($data['po_no']) && !empty($data['po_no'])){
                    $sku_list_wh_store = $sku_list_wh_store->where('po.order_no',trim($data['po_no']));
                }

                // Invoice filter
                if(isset($data['invoice_no']) && !empty($data['invoice_no'])){
                    $sku_list_wh_store = $sku_list_wh_store->where('pod.invoice_no',trim($data['invoice_no']));
                }

                // SKU filter
                if(isset($data['sku']) && !empty($data['sku'])){
                    $sku_list_wh_store = $sku_list_wh_store->where('ppm.product_sku',trim($data['sku']));
                }

                // Date filter
                if(!empty($start_date) && !empty($end_date)){
                    $sku_list_wh_store = $sku_list_wh_store->whereRaw("DATE(po_grn_qc.created_at) >= '$start_date' AND DATE(po_grn_qc.created_at) <= '$end_date'");   
                }
                
                // Total data
                if(!empty($search_array)){
                    $sku_list_wh_store_total = clone $sku_list_wh_store;
                    $sku_list_wh_store_total = $sku_list_wh_store_total->groupBy('ppmi.product_status')->selectRaw('ppmi.product_status,COUNT(po_grn_qc_items.id) as inv_count')->get();
                    for($i=0;$i<count($sku_list_wh_store_total);$i++){
                        if($sku_list_wh_store_total[$i]->product_status == 1 || $sku_list_wh_store_total[$i]->product_status == 2){
                            $total_array['wh']+=$sku_list_wh_store_total[$i]->inv_count;
                        }else{
                            $total_array['store']+=$sku_list_wh_store_total[$i]->inv_count;
                        }
                    }
                }
                
                $sku_list_wh_store = $sku_list_wh_store->where('pod.id','>=',min($invoice_ids))->where('pod.id','<=',max($invoice_ids))
                ->groupByRaw('po_grn_qc.id,ppm.product_sku_id,ppmi.product_status')        
                ->selectRaw('pod.id as invoice_id,ppm.product_sku,ppm.product_sku_id,ppmi.product_status,COUNT(po_grn_qc_items.id) as inv_count')        
                ->get();
                
                for($i=0;$i<count($sku_list_wh_store);$i++){
                    $key = $sku_list_wh_store[$i]->invoice_id.'_'.$sku_list_wh_store[$i]->product_sku_id;
                    if($sku_list_wh_store[$i]->product_status == 1 || $sku_list_wh_store[$i]->product_status == 2){
                        $sku_list_wh_data[$key] = $sku_list_wh_store[$i]->inv_count;
                    }else{
                        $sku_list_store_data[$key] = $sku_list_wh_store[$i]->inv_count;
                    }
                }

                for($i=0;$i<count($sku_list);$i++){
                    $key = $sku_list[$i]->invoice_id.'_'.$sku_list[$i]->product_sku_id;
                    $sku_list[$i]->inv_wh = (isset($sku_list_wh_data[$key]))?$sku_list_wh_data[$key]:0;
                    $sku_list[$i]->inv_store = (isset($sku_list_store_data[$key]))?$sku_list_store_data[$key]:0;
                }
            }
            
            $vendor_list = CommonHelper::getVendorsList();
            for($i=0;$i<count($vendor_list);$i++){
                $vendor_id_list[$vendor_list[$i]['id']] = $vendor_list[$i]['name'];
            }
            
            $item_list = Design_lookup_items_master::wherein('type',['PURCHASE_ORDER_CATEGORY'])->select('id','name','type')->get()->toArray();
            for($i=0;$i<count($item_list);$i++){
                $category_id_list[$item_list[$i]['id']] = $item_list[$i]['name'];
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=grn_sku_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('GRN No','Invoice No','PO No','Vendor','Style','Total Qty','WH Qty','Store Qty','Category','Rate','GST%','GST Amt','Cost','Total Cost','Created On');
                
                $callback = function() use ($sku_list,$vendor_id_list,$category_id_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total_inv = $wh_inv = $store_inv = 0; 
                    
                    for($i=0;$i<count($sku_list);$i++){
                      
                        $gst_amount = round($sku_list[$i]->poi_rate*($sku_list[$i]->poi_gst_percent)/100,2);
                        $cost = $sku_list[$i]->poi_rate+$gst_amount;
                        
                        $array = array($sku_list[$i]->grn_no,$sku_list[$i]->invoice_no,$sku_list[$i]->order_no,$vendor_id_list[$sku_list[$i]->vendor_id],
                        $sku_list[$i]->product_sku,$sku_list[$i]->inv_count,$sku_list[$i]->inv_wh,$sku_list[$i]->inv_store,$category_id_list[$sku_list[$i]->po_category_id],
                        $sku_list[$i]->poi_rate,$sku_list[$i]->poi_gst_percent,$gst_amount,$cost,round($sku_list[$i]->inv_count*$cost,2),date('d-m-Y',strtotime($sku_list[$i]->grn_date)));
                         
                        fputcsv($file, $array);
                        
                        $total_inv+=$sku_list[$i]->inv_count;
                        $wh_inv+=$sku_list[$i]->inv_wh;
                        $store_inv+=$sku_list[$i]->inv_store;
                    }
                    
                    $array = array('Total','','','','',$total_inv,$wh_inv,$store_inv);
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            return view('admin/report_grn_sku',array('sku_list'=>$sku_list,'error_message'=>'',
            'vendor_list'=>$vendor_list,'vendor_id_list'=>$vendor_id_list,'category_id_list'=>$category_id_list,
            'total_array'=>$total_array,'search_array'=>$search_array,'vendor_data'=>$vendor_data,'sub_vendor_list'=>$sub_vendor_list,'user'=>$user));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('admin/report_grn_sku',array('error_message'=>$e->getMessage().', Line: '.$e->getLine()));
        }
    }
    
    function shelfLifeReport(Request $request){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            
            $data = $request->all();
            $user = Auth::user();
            $error_message = $sku = $vendor_id =  '';
            $categories =  $vendors = $store_sku_list = $stores = $store_sku_received_list = $sku_sale_list = $store_sku_return_list = $grn_id_list = array();
            $rec_per_page = 20;
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            $sku_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
            ->join('purchase_order_items as poi','poi.id', '=', 'ppmi.po_item_id')        
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'ppmi.grn_id'); 
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $sku_list = $sku_list->leftJoin('pos_product_images as ppi',function($join){$join->on('ppi.product_id','=','ppm.id')->where('ppi.image_type','=','front')->where('ppi.is_deleted','=',0);});        
                $fields = 'ppi.image_name,ppm.id as product_id';
            }else{
                $fields = 'ppm.id as product_id';
            }
            
            $sku_list = $sku_list->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)        
            ->where('po_grn_qc.is_deleted',0)                
            ->where('ppmi.arnon_inventory',0)             
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)
            ->where('poi.fake_inventory',0)        
            ->where('po_grn_qc.fake_inventory',0)                
            ->whereRaw("po_grn_qc.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'");        
            
            if(isset($data['sku']) && !empty($data['sku'])){
                $sku = trim($data['sku']);
                $sku_list = $sku_list->whereRaw("(poi.product_sku = '$sku' OR poi.vendor_sku = '$sku')");
            }
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $vendor_id = trim($data['v_id']);
                $sku_list = $sku_list->where('poi.vendor_id',$vendor_id);
            }
            
            $sku_list = $sku_list->groupByRaw('ppm.product_sku,ppmi.grn_id')           
            ->orderBy('grn_id')        
            ->selectRaw("po_grn_qc.id as grn_id,$fields,ppm.product_sku,ppm.category_id,ppm.vendor_product_sku,poi.vendor_sku,poi.vendor_id,po_grn_qc.created_at as grn_date,ppmi.sale_price,COUNT(ppmi.id) as inv_count,SUM(ppmi.base_price) as inv_value");  
                  
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $sku_list = $sku_list->get()->toArray(); 
            }else{
                $sku_list = $sku_list->paginate($rec_per_page); 
            }
            
            if(!empty($sku) || !empty($vendor_id)){
                for($i=0;$i<count($sku_list);$i++){
                    $grn_id_list[] = $sku_list[$i]->grn_id;
                }
                
                $grn_id_list = implode(',',array_values(array_unique($grn_id_list)));
                $grn_where_str = "po_grn_qc.id IN($grn_id_list)";
            }else{
                $grn_where_str = 1;
            }
            
            // Push demand list
            $demand_sku_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')           
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'ppmi.grn_id')        
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')       
            ->where('spd.demand_type','inventory_push')    
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))        
            ->where('spd.is_deleted',0)               
            ->where('spdi.is_deleted',0)     
            ->where('spdi.demand_status',1)        
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)        
            ->where('po_grn_qc.is_deleted',0)                
            ->where('ppmi.arnon_inventory',0)            
            ->where('spdi.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)        
            ->where('po_grn_qc.fake_inventory',0)      
            ->where('spd.fake_inventory',0)           
            ->whereRaw("po_grn_qc.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")     
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")        
            ->groupByRaw('ppm.product_sku,spd.store_id,po_grn_qc.id')                
            ->selectRaw("po_grn_qc.id as grn_id,spd.created_at as wh_out_date,ppm.product_sku,spd.store_id,COUNT(ppmi.id) as inv_count,SUM(ppmi.store_base_price) as inv_value")  
            ->get()->toArray();      
            
            // This type of array is created instead of array with 3 indexes(grn_id__sku__store_id) because it will reduce loop iterations. 
            // Array with 3 indexes will require iterate through all stores and check if inventory is out from warehouse for that index key.
            for($i=0;$i<count($demand_sku_list);$i++){
                $key = $demand_sku_list[$i]->grn_id.'__'.$demand_sku_list[$i]->product_sku;
                $store_sku_list[$key][] = $demand_sku_list[$i];
            }
            
            // Push demand which are received by store receive_status = 1
            //\DB::enableQueryLog();
            $demand_sku_received_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')           
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'ppmi.grn_id')        
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')       
            ->where('spd.demand_type','inventory_push')        
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
            ->where('spd.is_deleted',0)                
            ->where('spdi.receive_status',1)                
            ->where('spdi.is_deleted',0)     
            ->where('spdi.demand_status',1)        
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)        
            ->where('po_grn_qc.is_deleted',0)                
            ->where('ppmi.arnon_inventory',0)           
            ->where('spdi.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)        
            ->where('po_grn_qc.fake_inventory',0)      
            ->where('spd.fake_inventory',0)               
            ->whereRaw("po_grn_qc.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")     
            ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")        
            ->groupByRaw('ppm.product_sku,spd.store_id,po_grn_qc.id')                
            ->selectRaw("po_grn_qc.id as grn_id,ppm.product_sku,spd.store_id,spdi.receive_date as store_in_date,COUNT(ppmi.id) as inv_count,SUM(ppmi.store_base_price) as inv_value")  
            ->get()->toArray();      
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            for($i=0;$i<count($demand_sku_received_list);$i++){
                $key = $demand_sku_received_list[$i]->grn_id.'__'.$demand_sku_received_list[$i]->product_sku.'__'.$demand_sku_received_list[$i]->store_id;
                $store_sku_received_list[$key] = $demand_sku_received_list[$i];
            }
            
            // Inventory Return Demand
            $demand_sku_return_list = \DB::table('store_products_demand_inventory as spdi')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')           
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'ppmi.grn_id')        
            ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')       
            ->where('spd.demand_type','inventory_return_to_warehouse')    
            ->wherein('spd.demand_status',array('warehouse_dispatched','warehouse_loading','warehouse_loaded'))        
            ->where('spdi.is_deleted',0)    
            ->where('spdi.demand_status',1)        
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)        
            ->where('po_grn_qc.is_deleted',0)                
            ->where('ppmi.arnon_inventory',0)       
            ->where('spdi.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)        
            ->where('po_grn_qc.fake_inventory',0)      
            ->where('spd.fake_inventory',0)                   
            ->whereRaw("po_grn_qc.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")     
            ->groupByRaw('ppm.product_sku,spd.store_id,po_grn_qc.id')                
            ->selectRaw("po_grn_qc.id as grn_id,ppm.product_sku,spd.store_id,COUNT(ppmi.id) as inv_count,SUM(spdi.store_base_price) as inv_value")  
            ->get()->toArray();      
            
            for($i=0;$i<count($demand_sku_return_list);$i++){
                $key = $demand_sku_return_list[$i]->grn_id.'__'.$demand_sku_return_list[$i]->product_sku.'__'.$demand_sku_return_list[$i]->store_id;
                $store_sku_return_list[$key] = $demand_sku_return_list[$i];
            }
            
            // Sku sold list
            $sku_sold_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')           
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
            ->join('purchase_order_grn_qc as po_grn_qc','po_grn_qc.id', '=', 'ppmi.grn_id')        
            //->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')       
            ->where('pcod.is_deleted',0)        
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)        
            ->where('po_grn_qc.is_deleted',0)                
            ->where('ppmi.arnon_inventory',0)      
            ->where('pcod.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)     
            ->where('po_grn_qc.fake_inventory',0)   
            ->where('pcod.order_status',1)         
            ->whereRaw("po_grn_qc.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")     
            ->groupByRaw('ppm.product_sku,po_grn_qc.id,pcod.store_id,pcod.net_price')                
            ->selectRaw("po_grn_qc.id as grn_id,ppm.product_sku,pcod.store_id,pcod.net_price,SUM(pcod.product_quantity) as prod_count,SUM(pcod.sale_price) as base_price_total,SUM(pcod.net_price) as net_price_total")  
            ->get()->toArray();      
            
            for($i=0;$i<count($sku_sold_list);$i++){
                $key = $sku_sold_list[$i]->grn_id.'__'.$sku_sold_list[$i]->product_sku.'__'.$sku_sold_list[$i]->store_id;
                if(!isset($sku_sale_list[$key])){
                    $sku_sale_list[$key] = array('sale_qty'=>0,'sale_value'=>0,'sale_net_amount'=>0,'1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0,'6'=>0);
                }
                
                $sku_sale_list[$key]['sale_qty']+=$sku_sold_list[$i]->prod_count;
                $sku_sale_list[$key]['sale_value']+=$sku_sold_list[$i]->base_price_total;
                $sku_sale_list[$key]['sale_net_amount']+=$sku_sold_list[$i]->net_price_total;
                
                $net_price = abs($sku_sold_list[$i]->net_price);
                
                if($net_price < 1000){
                    $sku_sale_list[$key]['1']+=$sku_sold_list[$i]->prod_count;
                }elseif($net_price >= 1000 && $net_price < 1500){
                    $sku_sale_list[$key]['2']+=$sku_sold_list[$i]->prod_count;
                }elseif($net_price >= 1500 && $net_price < 2000){
                    $sku_sale_list[$key]['3']+=$sku_sold_list[$i]->prod_count;
                }elseif($net_price >= 2000 && $net_price < 2500){
                    $sku_sale_list[$key]['4']+=$sku_sold_list[$i]->prod_count;
                }elseif($net_price >= 2500 && $net_price < 3000){
                    $sku_sale_list[$key]['5']+=$sku_sold_list[$i]->prod_count;
                }elseif($net_price >= 3000 ){
                    $sku_sale_list[$key]['6']+=$sku_sold_list[$i]->prod_count;
                }
            }
            
            $category_list = Design_lookup_items_master::where('type','POS_PRODUCT_CATEGORY')->get()->toArray();
            $vendor_list = CommonHelper::getVendorsList();       
            $store_list = CommonHelper::getStoresList();    
            
            for($i=0;$i<count($category_list);$i++){
                $categories[$category_list[$i]['id']] = $category_list[$i]['name'];
            }
            
            for($i=0;$i<count($vendor_list);$i++){
                $vendors[$vendor_list[$i]['id']] = $vendor_list[$i]['name'];
            }
            
            for($i=0;$i<count($store_list);$i++){
                $stores[$store_list[$i]['id']] = $store_list[$i];
            }
            
            $data_html = array('sku_list'=>$sku_list,'error_message'=>'','vendors'=>$vendors,'stores'=>$stores,'categories'=>$categories,
            'store_sku_list'=>$store_sku_list,'store_sku_received_list'=>$store_sku_received_list,'sku_sale_list'=>$sku_sale_list,'store_sku_return_list'=>$store_sku_return_list);
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                //$export = new ViewExport('admin.file1',$data_html);
                $export = new ViewExport('admin.report_shelf_life_excel',$data_html);
            
                return Excel::download($export, 'shelf_life_report.xlsx');    
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=shelf_life_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Vendor','SKU','Vendor SKU','Category','WH In Date','WH In Qty','WH In Value','WH Ret Qty','WH Ret Value','WH Out Date','WH Out Qty','WH Out Value','Store Name','Store Code','Store In Date','Store In Qty','Store In Value','Sale Qty',
                'Bal Qty','Sale Value','Sale Net Amt','MRP','< 1000','1000 - 1499','1500 - 1999','2000 - 2499','2500 - 2999','> 3000','Sale %','Sale Value %','Shelf Life');
                
                $callback = function() use ($data_html,$columns){
                    extract($data_html);
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $cur_date = date('Y/m/d');
                    $total = array('wh_in_qty'=>0,'wh_in_val'=>0,'wh_ret_qty'=>0,'wh_ret_val'=>0,'wh_out_qty'=>0,'wh_out_val'=>0,'sku_rec_count'=>0,'sku_rec_val'=>0,'sale_qty'=>0,'bal_qty'=>0,
                    'sale_val'=>0,'sale_net_amt'=>0,'sale_1'=>0,'sale_2'=>0,'sale_3'=>0,'sale_4'=>0,'sale_5'=>0,'sale_6'=>0,'shelf_life'=>0);
                    
                    $blank = array('No Records','','','','','','','','','','','','','','','','','','','','','','','','');
                    
                    for($i=0;$i<count($sku_list);$i++){
                      
                        $vendor = isset($vendors[$sku_list[$i]->vendor_id])?$vendors[$sku_list[$i]->vendor_id]:'';
                        
                        $array = array($vendor,$sku_list[$i]->vendor_sku,$sku_list[$i]->vendor_product_sku,$categories[$sku_list[$i]->category_id],date('d-m-Y',strtotime($sku_list[$i]->grn_date)),$sku_list[$i]->inv_count,$sku_list[$i]->inv_value);
                        
                        // Added only once, warehouse in 
                        $total['wh_in_qty']+=$sku_list[$i]->inv_count;
                        $total['wh_in_val']+=$sku_list[$i]->inv_value;
                        
                        $key = $sku_list[$i]->grn_id.'__'.$sku_list[$i]->product_sku; 
                        $store_sku = isset($store_sku_list[$key])?$store_sku_list[$key]:array();
                        
                        for($q=0;$q<count($store_sku);$q++){
                            
                            if($q > 0){
                                $vendor = isset($vendors[$sku_list[$i]->vendor_id])?$vendors[$sku_list[$i]->vendor_id]:'';
                                //$array = array($vendor,$sku_list[$i]->vendor_sku,$categories[$sku_list[$i]->category_id],date('d-m-Y',strtotime($sku_list[$i]->grn_date)),$sku_list[$i]->inv_count,$sku_list[$i]->inv_value);
                                $array = array('','','','','','','');
                            }
                            
                            $key = $store_sku[$q]->grn_id.'__'.$store_sku[$q]->product_sku.'__'.$store_sku[$q]->store_id;
                            $array[] = $ret_qty = (isset($store_sku_return_list[$key]))?$store_sku_return_list[$key]->inv_count:0;
                            $array[] = $ret_val = (isset($store_sku_return_list[$key]))?$store_sku_return_list[$key]->inv_value:0;
                            $array[] = date('d-m-Y',strtotime($store_sku[$q]->wh_out_date));
                            $array[] = $store_in_qty = $store_sku[$q]->inv_count;
                            $array[] = $store_sku[$q]->inv_value;        
                            $array[] = $stores[$store_sku[$q]->store_id]['store_name'];
                            $array[] = $stores[$store_sku[$q]->store_id]['store_id_code'];
                            $array[] = $store_in_date = isset($store_sku_received_list[$key])?date('d-m-Y',strtotime($store_sku_received_list[$key]->store_in_date)):'';
                            $array[] = $sku_received_count = (isset($store_sku_received_list[$key]))?$store_sku_received_list[$key]->inv_count:0;
                            $array[] = $sku_received_val = (isset($store_sku_received_list[$key]))?$store_sku_received_list[$key]->inv_value:0;
                            $array[] = $sale_qty = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['sale_qty']:0;        
                            $array[] = $bal_qty = $store_in_qty-($sale_qty+$ret_qty);
                            $array[] = $sale_val = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['sale_value']:0;
                            $array[] = $sale_net_amt = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['sale_net_amount']:0;
                            $array[] = $sku_list[$i]->sale_price;
                            $array[] = $sale_1 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['1']:0;        
                            $array[] = $sale_2 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['2']:0;
                            $array[] = $sale_3 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['3']:0;
                            $array[] = $sale_4 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['4']:0;
                            $array[] = $sale_5 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['5']:0;
                            $array[] = $sale_6 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['6']:0;
                            $array[] = round(($sale_qty/$store_in_qty)*100,2).' %';
                            $array[] = round(($sale_qty/$store_in_qty)*100,2).' %';
                            $array[] = $shelf_life = ($bal_qty > 0)?CommonHelper::dateDiff($cur_date,$store_in_date):'';
                            
                            fputcsv($file, $array);
                            
                            $total['wh_ret_qty']+=$ret_qty;
                            $total['wh_ret_val']+=$ret_val;
                            $total['wh_out_qty']+=$store_in_qty;
                            $total['wh_out_val']+=$store_sku[$q]->inv_value;
                            $total['sku_rec_count']+=$sku_received_count;
                            $total['sku_rec_val']+=$sku_received_val;
                            $total['sale_qty']+=$sale_qty;
                            $total['bal_qty']+=$bal_qty;
                            $total['sale_val']+=$sale_val;
                            $total['sale_net_amt']+=$sale_net_amt;
                            $total['sale_1']+=$sale_1;
                            $total['sale_2']+=$sale_2;
                            $total['sale_3']+=$sale_3;
                            $total['sale_4']+=$sale_4;
                            $total['sale_5']+=$sale_5;
                            $total['sale_6']+=$sale_6;
                            $total['shelf_life']+=($shelf_life != '')?$shelf_life:0;
                        }
                        
                        if(empty($store_sku)){
                            $array = array_merge($array,$blank);
                            fputcsv($file, $array);
                        }
                        
                    }
                    
                    $array = array('Total','','','','',$total['wh_in_qty'],$total['wh_in_val'],$total['wh_ret_qty'],$total['wh_ret_val'],'',
                    $total['wh_out_qty'],$total['wh_out_val'],'','','',$total['sku_rec_count'],$total['sku_rec_val'],$total['sale_qty'],
                    $total['bal_qty'],$total['sale_val'],$total['sale_net_amt'],'',$total['sale_1'],$total['sale_2'],$total['sale_3'],
                    $total['sale_4'],$total['sale_5'],$total['sale_6'],'','',$total['shelf_life']);
                    
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            return view('admin/report_shelf_life',$data_html);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('admin/report_shelf_life',array('error_message'=>$e->getMessage()));
        }
    }
    
    function categoryDetailSalesReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message =  '';
            $category_sold = $category_balance = array();
            $categories = array('18'=>'MNM','20'=>'SKD','139'=>'KURTA SET','646'=>'KURTA');
            $category_ids = array_keys($categories);
            
            $price_ranges = array([0,999],[1000,1999],[2000,2899],[2900,3999],[4000,4999],[5000,100000],
            [0,1999],[2000,2999],[3000,3999],[0,3999],[4000,5999],[6000,7999],[8000,100000]);
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            // Category sold list
            $category_sold_list = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')           
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')        
            ->where('pcod.is_deleted',0)        
            ->where('ppmi.is_deleted',0)
            ->where('ppm.is_deleted',0)        
            //->wherein('ppm.category_id',$category_ids)       
            ->where('pcod.fake_inventory',0)
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)
            ->where('pco.fake_inventory',0)        
            ->where('pco.order_status',1) 
            ->where('pcod.order_status',1)         
            ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")     
            ->groupByRaw('ppm.category_id,pcod.store_id,ABS(pcod.sale_price)')                
            ->selectRaw("ppm.category_id,pcod.store_id,ABS(pcod.sale_price) as sale_price,SUM(pcod.product_quantity) as inv_count")  
            ->get()->toArray();      
            
            //Total Units available in stores without dates. It is calculated without dates
            $category_balance_in_stores = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->where('ppmi.product_status',4)        
            ->where('ppmi.is_deleted',0)
            //->where('ppmi.arnon_inventory',0)
            ->where('ppmi.status',1)
            //->wherein('ppm.category_id',$category_ids)         
            ->where('ppmi.fake_inventory',0)   
            ->where('ppm.fake_inventory',0)        
            ->groupByRaw('ppm.category_id,ppmi.store_id,ppmi.sale_price')        
            ->selectRaw('ppm.category_id,ppmi.store_id,ppmi.sale_price,COUNT(ppmi.id) as inv_count')
            ->orderBy('inv_count','DESC')
            ->get()->toArray();
            
            // Sale Array
            for($i=0;$i<count($category_sold_list);$i++){
                $sale_price = ($category_sold_list[$i]->sale_price);
                $store_id = $category_sold_list[$i]->store_id;
                $key1 = $store_id.'_'.$category_sold_list[$i]->category_id;
                
                if(isset($category_sold[$key1])){
                    $category_sold[$key1]+= $category_sold_list[$i]->inv_count;
                }else{
                    $category_sold[$key1] = $category_sold_list[$i]->inv_count;
                }
                
                /*if(isset($category_sold[$store_id])){
                    $category_sold[$store_id]+= $category_sold_list[$i]->inv_count;
                }else{
                    $category_sold[$store_id] = $category_sold_list[$i]->inv_count;
                }*/
                
                for($q=0;$q<count($price_ranges);$q++){
                    $range = $price_ranges[$q];
                    $min = $range[0];
                    $max = $range[1];
                    $key = $store_id.'_'.$category_sold_list[$i]->category_id.'_'.$min.'_'.$max;
                    if($sale_price >= $min && $sale_price <= $max){
                        if(isset($category_sold[$key])){
                            $category_sold[$key]+= $category_sold_list[$i]->inv_count;
                        }else{
                            $category_sold[$key] = $category_sold_list[$i]->inv_count;
                        }
                    }
                }
            }
            
            // Balance array
            for($i=0;$i<count($category_balance_in_stores);$i++){
                $sale_price = ($category_balance_in_stores[$i]->sale_price);
                $key1 = $category_balance_in_stores[$i]->store_id.'_'.$category_balance_in_stores[$i]->category_id;
                
                if(isset($category_balance[$key1])){
                    $category_balance[$key1]+= $category_balance_in_stores[$i]->inv_count;
                }else{
                    $category_balance[$key1] = $category_balance_in_stores[$i]->inv_count;
                }
                
                for($q=0;$q<count($price_ranges);$q++){
                    $range = $price_ranges[$q];
                    $min = $range[0];
                    $max = $range[1];
                    $key = $category_balance_in_stores[$i]->store_id.'_'.$category_balance_in_stores[$i]->category_id.'_'.$min.'_'.$max;
                    if($sale_price >= $min && $sale_price <= $max){
                        if(isset($category_balance[$key])){
                            $category_balance[$key]+= $category_balance_in_stores[$i]->inv_count;
                        }else{
                            $category_balance[$key] = $category_balance_in_stores[$i]->inv_count;
                        }
                    }
                }
                
            }
            
            $store_list = CommonHelper::getStoresList();
            $category_list = Design_lookup_items_master::where('type','POS_PRODUCT_CATEGORY')->orderBy('name')->get()->toArray();
            
            $data_html = array('category_balance'=>$category_balance,'category_sold'=>$category_sold,'store_list'=>$store_list,'error_message'=>'','category_list'=>$category_list,'category_ids'=>$category_ids);
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=category_detail_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns1_1 = array('','');
                for($i=0;$i<count($category_list);$i++){
                    if(!in_array($category_list[$i]['id'],$category_ids)){
                          $columns1_1[] = $category_list[$i]['name'];
                          $columns1_1[] = '';
                    }
                }    
                $columns1_2 = array('MNM','','','','','','','','','','','','','',
                'KURTA','','','','','','','','','','','','','',    
                'KURTA SET','','','','','','','','','','','',       
                'SKD','','','','','','','', '','',   
                'TOTAL CATEGORY','');
                
                $columns1 = array_merge($columns1_1,$columns1_2);
                        
                $columns2_1 = array('','');
                for($i=0;$i<count($category_list);$i++){
                    if(!in_array($category_list[$i]['id'],$category_ids)){
                          $columns2_1[] = 'Sale Qty';
                          $columns2_1[] = 'Bal Qty';
                    }
                }    
                
                $columns2_2 = array('0 - 999 Sale','Bal','1000 - 1999 Sale','Bal','2000 - 2899 Sale','Bal','2900 - 3999 Sale','Bal','4000 - 4999 Sale','Bal',
                '> 4999 Sale','Bal','Total Sale','Total Bal','0 - 999 Sale','Bal','1000 - 1999 Sale','Bal','2000 - 2899 Sale','Bal','2900 - 3999 Sale','Bal','4000 - 4999 Sale','Bal','> 4999 Sale','Bal',
                'Total Sale','Total Bal','0 - 1999 Sale','Bal','2000 - 2999 Sale','Bal','3000 - 3999 Sale','Bal','4000 - 4999 Sale','Bal','> 4999 Sale','Bal','Total Sale','Total Bal','0 - 3999 Sale','Bal','4000 - 5999 Sale','Bal',
                '6000 - 7999 Sale','Bal','> 7999 Sale','Bal','Total Sale','Total Bal','Sale','Bal');
                
                $columns2 = array_merge($columns2_1,$columns2_2);
                
                $callback = function() use ($category_balance,$category_sold,$store_list,$category_ids,$category_list,$columns1,$columns2){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns1);
                    fputcsv($file, $columns2);
                    
                    $total_data = array('inv_sold'=>0,'inv_bal'=>0,'cat_sold'=>0,'cat_bal'=>0);
                    for($i=0;$i<count($store_list);$i++){
                        
                        $store_id = $store_list[$i]['id']; 
                        $ranges1 = array([0,999],[1000,1999],[2000,2899],[2900,3999],[4000,4999],[5000,100000]); 
                        $ranges2 = array([0,1999],[2000,2999],[3000,3999],[4000,4999],[5000,100000]); 
                        $ranges3 = array([0,3999],[4000,5999],[6000,7999],[8000,100000]); 
                        
                        $array = array($i+1,$store_list[$i]['store_name'].' ('.$store_list[$i]['store_id_code'].')'); 
                        
                        for($q=0;$q<count($category_list);$q++){
                            if(!in_array($category_list[$q]['id'],$category_ids)){
                                $key = $store_list[$i]['id'].'_'.$category_list[$q]['id'];

                                $inv_sold = isset($category_sold[$key])?$category_sold[$key]:0;
                                $inv_bal = isset($category_balance[$key])?$category_balance[$key]:0;
                                $total_data['inv_sold']+=$inv_sold;
                                $total_data['inv_bal']+=$inv_bal;
                                $array[] = $inv_sold;
                                $array[] = $inv_bal;
                            }
                        }  
                        
                        for($q=0;$q<count($ranges1);$q++){
                            $range = $ranges1[$q];$min = $range[0];$max = $range[1]; 
                            $key = $store_list[$i]['id'].'_18_'.$min.'_'.$max; 
                            $inv_sold = isset($category_sold[$key])?$category_sold[$key]:0;
                            $inv_bal = isset($category_balance[$key])?$category_balance[$key]:0;
                            $array[] = $inv_sold;
                            $array[] = $inv_bal;
                            $total_data['inv_sold']+=$inv_sold; 
                            $total_data['inv_bal']+=$inv_bal; 
                            $total_data['cat_sold']+=$inv_sold;
                            $total_data['cat_bal']+=$inv_bal;
                        }  
                        
                        $array[] = $total_data['cat_sold'];
                        $array[] = $total_data['cat_bal'];
                        $total_data['cat_sold'] = $total_data['cat_bal'] = 0;
                        
                        for($q=0;$q<count($ranges1);$q++){
                            $range = $ranges1[$q];$min = $range[0];$max = $range[1]; 
                            $key = $store_list[$i]['id'].'_646_'.$min.'_'.$max; 
                            $inv_sold = isset($category_sold[$key])?$category_sold[$key]:0;
                            $inv_bal = isset($category_balance[$key])?$category_balance[$key]:0;
                            $array[] = $inv_sold;
                            $array[] = $inv_bal;
                            $total_data['inv_sold']+=$inv_sold; 
                            $total_data['inv_bal']+=$inv_bal; 
                            $total_data['cat_sold']+=$inv_sold;
                            $total_data['cat_bal']+=$inv_bal;
                        }  
                        
                        $array[] = $total_data['cat_sold'];
                        $array[] = $total_data['cat_bal'];
                        $total_data['cat_sold'] = $total_data['cat_bal'] = 0;
                        
                        for($q=0;$q<count($ranges2);$q++){
                            $range = $ranges2[$q];$min = $range[0];$max = $range[1]; 
                            $key = $store_list[$i]['id'].'_139_'.$min.'_'.$max; 
                            $inv_sold = isset($category_sold[$key])?$category_sold[$key]:0;
                            $inv_bal = isset($category_balance[$key])?$category_balance[$key]:0;
                            $array[] = $inv_sold;
                            $array[] = $inv_bal;
                            $total_data['inv_sold']+=$inv_sold; 
                            $total_data['inv_bal']+=$inv_bal; 
                            $total_data['cat_sold']+=$inv_sold;
                            $total_data['cat_bal']+=$inv_bal;
                        }  
                        
                        $array[] = $total_data['cat_sold'];
                        $array[] = $total_data['cat_bal'];
                        $total_data['cat_sold'] = $total_data['cat_bal'] = 0;
                                        
                        for($q=0;$q<count($ranges3);$q++){
                            $range = $ranges3[$q];$min = $range[0];$max = $range[1]; 
                            $key = $store_list[$i]['id'].'_20_'.$min.'_'.$max; 
                            $inv_sold = isset($category_sold[$key])?$category_sold[$key]:0;
                            $inv_bal = isset($category_balance[$key])?$category_balance[$key]:0;
                            $array[] = $inv_sold;
                            $array[] = $inv_bal;
                            $total_data['inv_sold']+=$inv_sold; 
                            $total_data['inv_bal']+=$inv_bal; 
                            $total_data['cat_sold']+=$inv_sold;
                            $total_data['cat_bal']+=$inv_bal;
                        }                 
                        
                        $array[] = $total_data['cat_sold'];
                        $array[] = $total_data['cat_bal'];
                        $total_data['cat_sold'] = $total_data['cat_bal'] = 0;
                                        
                        $array[] = $total_data['inv_sold'];
                        $array[] = $total_data['inv_bal'];                
                        $total_data['inv_sold'] = 0; 
                        $total_data['inv_bal'] = 0;                 
                                         
                        fputcsv($file, $array);                
                                       
                    }
                            
                    //$array = array('Total','','','','',);
                    //fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            return view('admin/report_category_detail_sales',$data_html);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return view('admin/report_category_detail_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    function warehouseProductWiseInventoryBalanceReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $start_date = $end_date = '';
            $design_items = $sizes = $vendors = array();
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            if(!empty($search_date['start_date']) && !empty($search_date['start_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            $inv_type = isset($data['inv_type'])?trim($data['inv_type']):'arnon';
           
            $warehouse_inv = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->where('ppmi.product_status',1)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)        
            ->where('ppmi.arnon_inventory',1)
            ->where('ppmi.fake_inventory',0)  
            ->where('ppm.fake_inventory',0)          
            ->where('ppm.is_deleted',0);        
            
            if(!empty($start_date) && !empty($end_date)){
                $warehouse_inv = $warehouse_inv->whereRaw("DATE(ppmi.created_at) >= '$start_date' AND DATE(ppmi.created_at) <= '$end_date'");         
            }
            
            if(isset($data['sku']) && !empty($data['sku'])){
                $sku = trim($data['sku']);
                $warehouse_inv = $warehouse_inv->whereRaw("(ppm.product_sku = '$sku' OR ppm.product_name LIKE '%$sku%')");
            }
            
            $warehouse_inv_total = clone ($warehouse_inv);
            
            $warehouse_inv = $warehouse_inv->groupByRaw('ppm.product_name,ppm.product_sku,ppm.size_id,ppm.color_id')        
            ->selectRaw('ppm.*,ppmi.base_price as cost_price,ppmi.sale_price as mrp,COUNT(ppmi.id) as inv_count')
            ->orderBy('inv_count','DESC');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $warehouse_inv = $warehouse_inv->get()->toArray(); 
            }else{
                $warehouse_inv = $warehouse_inv->paginate(300); 
                $warehouse_inv_total = $warehouse_inv_total->selectRaw('COUNT(ppmi.id) as inv_count')->first();
            }
            
            $design_items_list = Design_lookup_items_master::wherein('type',array('COLOR','POS_PRODUCT_CATEGORY','POS_PRODUCT_SUBCATEGORY','SEASON'))->orderBy('name')->get()->toArray();
            
            for($i=0;$i<count($design_items_list);$i++){
                $type = strtoupper($design_items_list[$i]['type']);
                $id = $design_items_list[$i]['id'];
                $name = $design_items_list[$i]['name'];
                $design_items[$type][$id] = $name;
            }
            
            $size_list = Production_size_counts::get()->toArray();
            for($i=0;$i<count($size_list);$i++){
                $sizes[$size_list[$i]['id']] = $size_list[$i]['size'];
            }
            
            $story_list = Story_master::where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($story_list);$i++){
                $design_story[$story_list[$i]['id']] = $story_list[$i]['name'];
            }
            
            /*$vendor_list = Vendor_detail::where('is_deleted',0)->get()->toArray();
            
            for($i=0;$i<count($vendor_list);$i++){
                
                $vendors[$vendor_list[$i]['id']] = $vendor_list[$i]['name'];
            }*/
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=warehouse_inventory_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Style','Category','Subcategory','Product Name','Bal Qty','Size','Color','Season','Story','Supplier','Cost Price','Sale Price');

                $sku_list = array();
                /*$file = 'documents/qr_code_1.csv';
                if(($handle = fopen($file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                        $bal_qty = trim($data[9]);
                        if($bal_qty > 0){
                            $sku = trim($data[10]);
                            $season = trim($data[18]);
                            $story = trim($data[20]);
                            $vendor = trim($data[22]);
                            $category = trim($data[17]);
                            $subcategory = trim($data[21]);
                            $sku_list[$sku] = ['category'=>$category,'subcategory'=>$subcategory,'season'=>$season,'story'=>$story,'vendor'=>$vendor];
                        }
                    }
                }*/
                
                $callback = function() use ($warehouse_inv,$design_items,$sizes,$sku_list,$design_story, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total = array('inv_count'=>0);
                    
                    for($i=0;$i<count($warehouse_inv);$i++){
                        $sku = $warehouse_inv[$i]->product_sku;
                        $category = isset($design_items['POS_PRODUCT_CATEGORY'][$warehouse_inv[$i]->category_id])?$design_items['POS_PRODUCT_CATEGORY'][$warehouse_inv[$i]->category_id]:'';//isset($sku_list[$sku]['category'])?$sku_list[$sku]['category']:'';  
                        $subcategory = isset($design_items['POS_PRODUCT_SUBCATEGORY'][$warehouse_inv[$i]->subcategory_id])?$design_items['POS_PRODUCT_SUBCATEGORY'][$warehouse_inv[$i]->subcategory_id]:'';//isset($sku_list[$sku]['subcategory'])?$sku_list[$sku]['subcategory']:''; 
                        $size = isset($sizes[$warehouse_inv[$i]->size_id])?$sizes[$warehouse_inv[$i]->size_id]:'';
                        $color = isset($design_items['COLOR'][$warehouse_inv[$i]->color_id])?$design_items['COLOR'][$warehouse_inv[$i]->color_id]:'';
                        $season = isset($design_items['SEASON'][$warehouse_inv[$i]->season_id])?$design_items['SEASON'][$warehouse_inv[$i]->season_id]:'';//isset($sku_list[$sku]['season'])?$sku_list[$sku]['season']:'';  
                        $story = isset($design_story[$warehouse_inv[$i]->story_id])?$design_story[$warehouse_inv[$i]->story_id]:'';;//isset($sku_list[$sku]['story'])?$sku_list[$sku]['story']:'';
                        $vendor = $warehouse_inv[$i]->supplier_name; //isset($sku_list[$sku]['vendor'])?$sku_list[$sku]['vendor']:'';
                        
                        $array = array($warehouse_inv[$i]->product_sku,$category,$subcategory,$warehouse_inv[$i]->product_name,$warehouse_inv[$i]->inv_count,$size,$color,$season,$story,$vendor,$warehouse_inv[$i]->base_price,$warehouse_inv[$i]->sale_price);
                        $total['inv_count']+=$warehouse_inv[$i]->inv_count;
                       
                        fputcsv($file, $array);
                    }
                    
                    $array = array('Total','','','',$total['inv_count']);
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            //$store_list = CommonHelper::getStoresList();
            
            return view('admin/report_warehouse_product_wise_balance',array('warehouse_inv'=>$warehouse_inv,'error_message'=>$error_message,'user'=>$user,'inv_total'=>$warehouse_inv_total,'design_items'=>$design_items,'sizes'=>$sizes,'vendors'=>$vendors,'design_story'=>$design_story));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE_BALANCE_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_warehouse_product_wise_balance',array('error_message'=>$e->getMessage()));
        }
    }
    
    function warehouseSizeWiseInventoryBalanceReport(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $start_date = $end_date = '';
            $design_items = $sizes = $vendors = $prod_skus = $inv_size = $size_ids = $size_total = $warehouse_inv_total = $warehouse_inv_1 = $size_not_sel_basic = $size_array = array();
            $size_sel_basic = [1,2,3,4,5,6];
            $size_data = array('1'=>'S','2'=>'M','3'=>'L','4'=>'XL','5'=>'XXL','6'=>'3XL');
            $rec_per_page = 200;
            
            $size_combinations =  $this->arrayUniqueCombinations($size_sel_basic);
            for($i=0;$i<count($size_combinations);$i++){
                $size_arr = $size_combinations[$i];
                sort($size_arr);
                $size_names = array();
                if(!empty($size_arr)){
                    for($q=0;$q<count($size_arr);$q++){
                        $size_id = $size_arr[$q];
                        $size_names[] = $size_data[$size_id];
                    }
                    $size_str = implode(',',$size_arr);
                    $size_array[$size_str] = implode(' | ',$size_names);
                }
            }
            
            ksort($size_array);
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            if(!empty($search_date['start_date']) && !empty($search_date['start_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            $inv_type = isset($data['inv_type'])?trim($data['inv_type']):'arnon';
            $arnon_inventory = ($inv_type == 'arnon')?1:0;
            
            $size_list = Production_size_counts::get()->toArray();
            
            $warehouse_inv = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')         
            ->leftJoin('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
            ->where('ppmi.product_status',1)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)        
            ->where('ppmi.arnon_inventory',$arnon_inventory)
            ->where('ppmi.fake_inventory',0)
            ->where('ppm.fake_inventory',0)        
            ->where('ppm.is_deleted',0);        
            
            if(!empty($start_date) && !empty($end_date)){
                if($inv_type == 'arnon'){
                    $warehouse_inv = $warehouse_inv->whereRaw("DATE(ppmi.created_at) >= '$start_date' AND DATE(ppmi.created_at) <= '$end_date'");         
                }else{
                    $warehouse_inv = $warehouse_inv->whereRaw("DATE(ppmi.intake_date) >= '$start_date' AND DATE(ppmi.intake_date) <= '$end_date'");         
                }
            }
            
            if(isset($data['sku']) && !empty($data['sku'])){
                $sku = trim($data['sku']);
                $warehouse_inv = $warehouse_inv->whereRaw("(ppm.product_sku = '$sku' OR ppm.product_name LIKE '%$sku%')");
            }
            
            if(isset($data['cat_id']) && !empty($data['cat_id'])){
                $category_id = trim($data['cat_id']);
                $warehouse_inv = $warehouse_inv->where('ppm.category_id',$category_id);
            }
            
            //$warehouse_inv_total = clone ($warehouse_inv);
            //$warehouse_inv_size_total = clone ($warehouse_inv);
            $warehouse_inv_size = clone ($warehouse_inv);
            
            $warehouse_inv = $warehouse_inv->groupByRaw('ppm.product_sku,ppm.product_name,ppm.color_id')
            ->selectRaw('ppm.*,ppmi.base_price as cost_price,ppmi.sale_price as mrp,poi.vendor_id,COUNT(ppmi.id) as inv_count')
            ->orderBy('inv_count','DESC');
            
            //if(isset($data['action']) && $data['action'] == 'download_csv'){
                $warehouse_inv = $warehouse_inv->get()->toArray(); 
                $warehouse_inv_size = $warehouse_inv_size->groupByRaw('ppm.product_sku,ppm.product_name,ppm.color_id,ppm.size_id')
                ->selectRaw('ppm.*,COUNT(ppmi.id) as inv_count')
                ->get()->toArray();
                
                for($i=0;$i<count($size_list);$i++){
                    $size_total[$size_list[$i]['id']] = 0;
                }
            /*}else{
                $warehouse_inv = $warehouse_inv->paginate($rec_per_page); 
                $warehouse_inv_total = $warehouse_inv_total->selectRaw('COUNT(ppmi.id) as inv_count')->first();
                $warehouse_inv_size_total = $warehouse_inv_size_total->groupByRaw('ppm.size_id')->selectRaw('ppm.size_id,COUNT(ppmi.id) as inv_count')->get()->toArray();
                
                for($i=0;$i<count($warehouse_inv);$i++){
                    $prod_skus[] = $warehouse_inv[$i]->product_sku;
                }

                $warehouse_inv_size = $warehouse_inv_size->groupByRaw('ppm.product_sku,ppm.product_name,ppm.color_id,ppm.size_id')
                ->selectRaw('ppm.*,COUNT(ppmi.id) as inv_count')
                ->wherein('ppm.product_sku',$prod_skus)
                ->get()->toArray();
                
                for($i=0;$i<count($size_list);$i++){
                    $size_total[$size_list[$i]['id']] = 0;
                }

                for($i=0;$i<count($warehouse_inv_size_total);$i++){
                    $size_id = $warehouse_inv_size_total[$i]->size_id;
                    $size_total[$size_id]+=$warehouse_inv_size_total[$i]->inv_count;
                }
            }*/
                
            for($i=0;$i<count($warehouse_inv_size);$i++){
                $key = strtolower($warehouse_inv_size[$i]->product_sku).'_'.str_replace(' ','_',strtolower($warehouse_inv_size[$i]->product_name)).'_'.$warehouse_inv_size[$i]->color_id;
                $size_id = $warehouse_inv_size[$i]->size_id;
                $size_ids[] = $size_id;
                if(isset($inv_size[$key][$size_id])) $inv_size[$key][$size_id]+=$warehouse_inv_size[$i]->inv_count;else $inv_size[$key][$size_id] = $warehouse_inv_size[$i]->inv_count;
            }    
            
            //print_r($inv_size);
            
            // Check if size search is executed
            if(isset($data['size_sel']) && !empty($data['size_sel'])){    
                $size_sel = explode(',',trim($data['size_sel']));
                $size_sel_count = count($size_sel);
                for($i=0;$i<count($size_list);$i++){
                    // List of size id which are not selected, it includes other like XS, size F etc.
                    if(!in_array($size_list[$i]['id'],$size_sel)){
                        $size_not_sel[] = $size_list[$i]['id'];
                    }
                    
                    // List of size id which are not selected, it does not includes other like XS, size F etc.
                    if(!in_array($size_list[$i]['id'],$size_sel) && in_array($size_list[$i]['id'],$size_sel_basic) ){
                        $size_not_sel_basic[] = $size_list[$i]['id'];
                    }
                }

                for($i=0;$i<count($warehouse_inv);$i++){
                    $count = $count_basic = 0;
                    $key = strtolower($warehouse_inv[$i]->product_sku).'_'.str_replace(' ','_',strtolower($warehouse_inv[$i]->product_name)).'_'.$warehouse_inv[$i]->color_id; 
                    
                    // count of products in inventory which are in selected size of search 
                    for($q=0;$q<count($size_sel);$q++){
                        $size_id = $size_sel[$q];
                        if(isset($inv_size[$key][$size_id]) && $inv_size[$key][$size_id] > 0){
                            $count++;
                        }
                    }
                    
                    // count of products in inventory which are in not selected size of search and are basic
                    for($q=0;$q<count($size_not_sel_basic);$q++){
                        $size_id = $size_not_sel_basic[$q];
                        if(isset($inv_size[$key][$size_id]) && $inv_size[$key][$size_id] > 0){
                            $count_basic++;
                        }
                    }
                    
                    // update other sizes which are not in search as 0
                    for($q=0;$q<count($size_not_sel);$q++){
                        $size_id = $size_not_sel[$q];
                        if(isset($inv_size[$key][$size_id]) && $inv_size[$key][$size_id] > 0){
                            $inv_size[$key][$size_id] = 0;
                        }
                    }
                    
                    // Include product only if it is in all sizes of search and is not in other size combination
                    if($count == $size_sel_count && $count_basic == 0){
                        $warehouse_inv_1[] = $warehouse_inv[$i];
                    }
                }

                $warehouse_inv = $warehouse_inv_1;
            }
            
            $design_items_list = Design_lookup_items_master::wherein('type',array('COLOR','POS_PRODUCT_CATEGORY','POS_PRODUCT_SUBCATEGORY','SEASON'))->orderBy('name')->get()->toArray();
            
            for($i=0;$i<count($design_items_list);$i++){
                $type = strtoupper($design_items_list[$i]['type']);
                $id = $design_items_list[$i]['id'];
                $name = $design_items_list[$i]['name'];
                $design_items[$type][$id] = $name;
            }
            
            for($i=0;$i<count($size_list);$i++){
                if(in_array($size_list[$i]['id'], $size_ids)){
                    $sizes[$size_list[$i]['id']] = $size_list[$i]['size'];
                }
            }
            
            $story_list = Story_master::where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($story_list);$i++){
                $design_story[$story_list[$i]['id']] = $story_list[$i]['name'];
            }
            
            $vendor_list = Vendor_detail::where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($vendor_list);$i++){
                $vendors[$vendor_list[$i]['id']] = $vendor_list[$i]['name'];
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=warehouse_inventory_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Style','Category','Subcategory','Product Name','Color','Season','Story','Supplier','Cost Price','Sale Price');
                foreach($sizes as $id=>$size){
                    $columns[] = $size;
                }
                
                $columns[] = 'Total';
                
                $sku_list = array();
               
                $callback = function() use ($warehouse_inv,$design_items,$sizes,$sku_list,$design_story,$inv_size,$vendors,$inv_type,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $total = array('inv_count'=>0,'inv_count1'=>0);
                    foreach($sizes as $id=>$size){
                        $total[$id] = 0;
                    }
                    
                    for($i=0;$i<count($warehouse_inv);$i++){
                        $product_total = 0;
                        $sku = $warehouse_inv[$i]->product_sku;
                        $category = isset($design_items['POS_PRODUCT_CATEGORY'][$warehouse_inv[$i]->category_id])?$design_items['POS_PRODUCT_CATEGORY'][$warehouse_inv[$i]->category_id]:'';
                        $subcategory = isset($design_items['POS_PRODUCT_SUBCATEGORY'][$warehouse_inv[$i]->subcategory_id])?$design_items['POS_PRODUCT_SUBCATEGORY'][$warehouse_inv[$i]->subcategory_id]:'';
                        $size = isset($sizes[$warehouse_inv[$i]->size_id])?$sizes[$warehouse_inv[$i]->size_id]:'';
                        $color = isset($design_items['COLOR'][$warehouse_inv[$i]->color_id])?$design_items['COLOR'][$warehouse_inv[$i]->color_id]:'';
                        $season = isset($design_items['SEASON'][$warehouse_inv[$i]->season_id])?$design_items['SEASON'][$warehouse_inv[$i]->season_id]:'';
                        $story = isset($design_story[$warehouse_inv[$i]->story_id])?$design_story[$warehouse_inv[$i]->story_id]:'';
                        if($inv_type == 'arnon'){
                            $vendor = $warehouse_inv[$i]->supplier_name;
                        }else{
                            $vendor = isset($vendors[$warehouse_inv[$i]->vendor_id])?$vendors[$warehouse_inv[$i]->vendor_id]:'';
                        }
                        
                        $key = strtolower($warehouse_inv[$i]->product_sku).'_'.str_replace(' ','_',strtolower($warehouse_inv[$i]->product_name)).'_'.$warehouse_inv[$i]->color_id;
                        
                        $array = array($warehouse_inv[$i]->product_sku,$category,$subcategory,$warehouse_inv[$i]->product_name,$color,$season,$story,$vendor,$warehouse_inv[$i]->base_price,$warehouse_inv[$i]->sale_price);
                        
                        foreach($sizes as $id=>$size){
                            $size_qty = isset($inv_size[$key][$id])?$inv_size[$key][$id]:0;
                            $product_total+=$size_qty;
                            $total[$id]+=$size_qty; 
                            $array[] = $size_qty;
                        }
                       
                        $array[] = $product_total;
                        fputcsv($file, $array);
                        
                        $total['inv_count1']+=$product_total; 
                    }
                    
                    $array = array('Total','','','','','','','','','');
                    foreach($sizes as $id=>$size){
                        $array[] = $total[$id];
                    }
                    
                    $array[] = $total['inv_count1'];
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('admin/report_warehouse_size_wise_balance',array('warehouse_inv'=>$warehouse_inv,'error_message'=>$error_message,'user'=>$user,'inv_total'=>$warehouse_inv_total,
            'design_items'=>$design_items,'sizes'=>$sizes,'vendors'=>$vendors,'design_story'=>$design_story,'inv_size'=>$inv_size,'inv_type'=>$inv_type,'size_total'=>$size_total,'size_array'=>$size_array));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'WAREHOUSE_BALANCE_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_warehouse_size_wise_balance',array('error_message'=>$e->getMessage().', Line: '.$e->getLine()));
        }
    }
    
    function arrayUniqueCombinations($array) {
        // initialize by adding the empty set
        $results = array(array( ));

        foreach ($array as $element){
            foreach ($results as $combination){
                array_push($results, array_merge(array($element), $combination));
            }
        }

        return $results;
    }
    
    function updateStoreInventory(Request $request){
        try{
            $data = $request->all();
            \DB::beginTransaction();
            
            /*$start_date = '2021/09/14';
            
            for($i=0;$i<=100;$i++){
                $date = date('Y/m/d',strtotime("+$i day",strtotime($start_date)));
                CommonHelper::updateStoreInventory($date);
            }*/
            
            CommonHelper::updateStoreInventoryBalance();
            
            \DB::commit();
        }catch (\Exception $e){
            \DB::rollBack();
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine()),500);

            CommonHelper::saveException($e,'UPDATE_STORE_INVENTORY',__FUNCTION__,__FILE__);
        }
    }
    
    function categorySalesReportGraph(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = $store_data = '';
            $category_sales = array();
            $store_id = (isset($data['store_id']) && !empty($data['store_id']))?trim($data['store_id']):'';
            
            if($user->user_type == 9){
                $store_data = CommonHelper::getUserStoreData($user->id);
            }
            
            if(!empty($store_id)){
                $store_data = Store::where('id',$store_id)->first();
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            $start_date = date('Y/m/d',strtotime($search_date['start_date']));
            $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            
            // Category List for stores sales
            $category_sales = \DB::table('pos_customer_orders_detail as pcod')
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')               
            ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')         
            ->join('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')        
            ->where('ppm.is_deleted',0)
            ->where('pcod.is_deleted',0)
            ->where('pcod.fake_inventory',0)    
            ->where('ppm.fake_inventory',0)            
            ->where('pco.order_status',1)  
            ->where('pco.is_deleted',0)
            ->where('pco.fake_inventory',0)                   
            //->where('dlim_1.is_deleted',0)        
            ->whereRaw("DATE(pco.created_at) >= '$start_date' AND DATE(pco.created_at) <= '$end_date'");        
            
            if(!empty($store_id)){
                $category_sales = $category_sales->where('pco.store_id',$store_id);
            }
            
            $category_sales = $category_sales->groupBy('ppm.category_id')->havingRaw("cat_qty > 0")        
            ->selectRaw('dlim_1.id as category_id,dlim_1.name as category_name,SUM(pcod.product_quantity) as cat_qty,SUM(pcod.net_price) as cat_net_price'); 
            
            // clone for sorting by net price
            $category_sales_price = clone ($category_sales);
                    
            // sort by quantity
            $category_sales = $category_sales->orderBy('cat_qty','DESC')->get()->toArray();
            
            // sort by net price
            $category_sales_price = $category_sales_price->orderBy('cat_net_price','DESC')->get()->toArray();
            
            // Payment method type 
            $payment_sales = \DB::table('pos_customer_orders_payments as pcop')
            ->join('pos_customer_orders as pco','pco.id', '=', 'pcop.order_id')                 
            ->where('pcop.is_deleted',0)
            ->where('pcop.fake_inventory',0)    
            ->where('pco.is_deleted',0)
            ->where('pco.fake_inventory',0)     
            ->where('pco.order_status',1)          
            ->whereRaw("DATE(pco.created_at) >= '$start_date' AND DATE(pco.created_at) <= '$end_date'");
            
            if(!empty($store_id)){
                $payment_sales = $payment_sales->where('pco.store_id',$store_id);
            }
            
            $payment_sales = $payment_sales->groupBy('pcop.payment_method')      
            ->selectRaw('pcop.payment_method,SUM(pcop.payment_amount) as total_payment_amount')      
            ->orderBy('total_payment_amount','DESC')
            ->get()->toArray();
            
            $store_list = CommonHelper::getStoresList();

            return view('admin/report_category_sales_graph',array('category_sales'=>$category_sales,'category_sales_price'=>$category_sales_price,'error_message'=>$error_message,
            'store_list'=>$store_list,'user'=>$user,'start_date'=>$start_date,'end_date'=>$end_date,'store_data'=>$store_data,'payment_sales'=>$payment_sales));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY_SALES_REPORT_GRAPH',__FUNCTION__,__FILE__);
            return view('admin/report_category_sales_graph',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
}
