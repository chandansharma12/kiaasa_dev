<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Coupon;
use App\Models\Coupon_items;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    
    public function __construct(){
    }
    
    function listCoupons(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            
            if(isset($data['action']) && $data['action']  == 'add_coupon'){
                $validateionRules = array('couponName_add'=>'required','couponItemsCount_add'=>'required|numeric|min:1|max:10000','couponStore_add'=>'required','couponValidFrom_add'=>'required','couponValidTo_add'=>'required','couponDiscount_add'=>'required|numeric|min:1|max:100','couponType_add'=>'required');

                $attributes = array('couponName_add'=>'Coupon Name','couponItemsCount_add'=>'Coupon Count','couponStore_add'=>'Store','couponValidFrom_add'=>'Valid From','couponValidTo_add'=>'Valid To','couponDiscount_add'=>'Discount','couponType_add'=>'Coupon Type');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                \DB::beginTransaction();
                $valid_from = CommonHelper::formatDate($data['couponValidFrom_add']);
                $valid_to = CommonHelper::formatDate($data['couponValidTo_add']);
                $insertArray = array('coupon_name'=>$data['couponName_add'],'items_count'=>$data['couponItemsCount_add'],'store_id'=>$data['couponStore_add'],'valid_from'=>$valid_from,'valid_to'=>$valid_to,'user_id'=>$user->id,'discount'=>$data['couponDiscount_add'],'coupon_type'=>$data['couponType_add']);

                $coupon = Coupon::create($insertArray);
                $coupons_count = $data['couponItemsCount_add'];
                $coupon_type = $data['couponType_add'];
                
                if($coupon_type == 'unique'){
                    for($i=1;$i<=$coupons_count;$i++){
                        $coupon_exist = 1;
                        while($coupon_exist == 1){
                            $coupon_number = $this->getCouponNumber(8);
                            $coupon_exist = Coupon_items::where('coupon_no',$coupon_number)->where('is_deleted',0)->count();
                        }

                        $insertArray = array('coupon_id'=>$coupon->id,'coupon_no'=>$coupon_number);
                        Coupon_items::create($insertArray);
                    }
                }else{
                    $coupon_exist = 1;
                    while($coupon_exist == 1){
                        $coupon_number = $this->getCouponNumber(8);
                        $coupon_exist = Coupon_items::where('coupon_no',$coupon_number)->where('is_deleted',0)->count();
                    }
                    
                    for($i=1;$i<=$coupons_count;$i++){
                        $insertArray = array('coupon_id'=>$coupon->id,'coupon_no'=>$coupon_number);
                        Coupon_items::create($insertArray);
                    }
                }
                
                \DB::commit();

                CommonHelper::createLog('Coupon Created. ID: '.$coupon->id,'COUPON_ADDED','COUPON');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Coupon added successfully'),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'get_coupon_data'){
                $coupon_data = Coupon::where('id',$data['id'])->first();
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Coupon data','coupon_data'=>$coupon_data),200);
            }
            
            if(isset($data['action']) && $data['action']  == 'update_coupon'){
                $validateionRules = array('couponName_edit'=>'required','couponItemsCount_edit'=>'required|numeric|min:1|max:10000','couponStore_edit'=>'required','couponValidFrom_edit'=>'required','couponValidTo_edit'=>'required','couponStatus_edit'=>'required','couponDiscount_edit'=>'required|numeric|min:1|max:100');

                $attributes = array('couponName_edit'=>'Coupon Name','couponItemsCount_edit'=>'Coupon Count','couponStore_edit'=>'Store','couponValidFrom_edit'=>'Valid From','couponValidTo_edit'=>'Valid To','couponStatus_edit'=>'Status','couponDiscount_edit'=>'Discount','couponType_edit'=>'Coupon Type');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                \DB::beginTransaction();
                $valid_from = CommonHelper::formatDate($data['couponValidFrom_edit']);
                $valid_to = CommonHelper::formatDate($data['couponValidTo_edit']);
                $coupon_id = trim($data['coupon_edit_id']);
                $coupon_data = Coupon::where('id',$coupon_id)->first();
                 
                if($data['couponStore_edit'] != $coupon_data->store_id || $valid_from != $coupon_data->valid_from || $valid_to != $coupon_data->valid_to || $data['couponDiscount_edit'] != $coupon_data->discount){
                    $coupon_used = Coupon_items::where('coupon_id',$coupon_id)->where('coupon_used',1)->where('is_deleted',0)->count();
                    if($coupon_used > 0){
                        $error_msg = 'Coupon Item is used. Store, Date From, Date To cannot be changed';
                        return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>$error_msg, 'errors' => $error_msg));
                    }
                }
                
                $updateArray = array('coupon_name'=>$data['couponName_edit'],'store_id'=>$data['couponStore_edit'],'valid_from'=>$valid_from,'valid_to'=>$valid_to,'status'=>$data['couponStatus_edit'],'discount'=>$data['couponDiscount_edit']);

                $coupon = Coupon::where('id',$coupon_id)->update($updateArray);
                
                \DB::commit();

                CommonHelper::createLog('Coupon Updated. ID: '.$coupon_id,'COUPON_UPDATED','COUPON');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Coupon updated successfully'),200);
            }
            
            $store_list = CommonHelper::getStoresList();
            
            $coupon_list = \DB::table('coupon as c')
            ->join('store as s','s.id', '=', 'c.store_id')        
            ->join('users as u','u.id', '=', 'c.user_id')
            ->where('c.is_deleted',0)
            ->where('s.is_deleted',0)
            ->select('c.*','s.store_name','s.store_id_code','u.name as user_name');        
                    
            if(isset($data['c_name']) && !empty($data['c_name'])){
                $coupon_list = $coupon_list->where('c.coupon_name','LIKE','%'.trim($data['c_name']).'%');
            }
            
            if(isset($data['c_id']) && !empty($data['c_id'])){
                $coupon_list = $coupon_list->where('c.id',$data['c_id']);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $coupon_list = $coupon_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $coupon_list = $coupon_list->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=coupon_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Coupon ID','Name','Store Name','Code ','Valid From','Valid To','Coupons Count','Type','Created On','Status');

                $callback = function() use ($coupon_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($coupon_list);$i++){
                        $status = $coupon_list[$i]->status==1?'Enabled':'Disabled';
                        $array = array($coupon_list[$i]->id,$coupon_list[$i]->coupon_name,$coupon_list[$i]->store_name,$coupon_list[$i]->store_id_code,date('d M Y',strtotime($coupon_list[$i]->valid_from)),date('d M Y',strtotime($coupon_list[$i]->valid_to)),
                        $coupon_list[$i]->items_count,$coupon_list[$i]->coupon_type,date('d M Y, H:i',strtotime($coupon_list[$i]->created_at)),$status);
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            return view('coupon/coupon_list',array('coupon_list'=>$coupon_list,'store_list'=>$store_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'COUPON',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine()),500);
            }else{
                return view('coupon/coupon_list',array('error_message'=>$e->getMessage().' '.$e->getLine()));
            }
        }
    }
    
    function getCouponNumber($length=8){
        return substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    } 
    
    function couponDetail(Request $request,$id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $coupon_id = $id;
            
            $coupon_data = \DB::table('coupon as c')
            ->join('store as s','s.id', '=', 'c.store_id')        
            ->join('users as u','u.id', '=', 'c.user_id')
            ->where('c.id',$coupon_id)                
            ->where('c.is_deleted',0)
            ->where('s.is_deleted',0)
            ->select('c.*','s.store_name','u.name as user_name')        
            ->first();
            
            $coupon_items_used = \DB::table('coupon_items as ci')
            ->leftJoin('pos_customer_orders as pco','pco.id', '=', 'ci.order_id')        
            ->where('ci.coupon_id',$coupon_id)        
            ->where('ci.is_deleted',0)
            ->where('ci.coupon_used',1)
            ->select('ci.*','pco.order_no')        
            ->get()->toArray();
            
            $coupon_items_used = json_decode(json_encode($coupon_items_used),true);
            
            $coupon_items_not_used = Coupon_items::where('coupon_id',$coupon_id)->where('coupon_used',0)->where('is_deleted',0)->get()->toArray();
            
            return view('coupon/coupon_detail',array('coupon_items_used'=>$coupon_items_used,'coupon_data'=>$coupon_data,'error_message'=>'','coupon_items_not_used'=>$coupon_items_not_used));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'COUPON',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine()),500);
            }else{
                return view('coupon/coupon_detail',array('error_message'=>$e->getMessage().' '.$e->getLine()));
            }
        }    
    }
}
