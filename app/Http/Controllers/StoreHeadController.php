<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store_item;
use App\Models\Store_item_detail;
use App\Models\Store_order;
use App\Models\Store_order_detail;
use App\Models\Design_lookup_items_master;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class StoreHeadController extends Controller
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
            return view('store_head/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_HEAD',__FUNCTION__,__FILE__);
            return view('store_head/dashboard',array('error_message' =>$e->getMessage()));
        }
    }
    
    function assetOrdersList(Request $request){
        try{
            $data = $request->all();
            
            $whereArray = array('so.is_deleted'=>0);
            $other_params = array('where_raw'=>array("so.order_status IN('waiting','rejected','approved')"));
            $orders_list_array = CommonHelper::getStoresAssetsOrdersList($whereArray,array(),$other_params);
            $orders_list_array['error_message'] = '';
            
            return view('store_head/asset_order_list',$orders_list_array);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE_HEAD',__FUNCTION__,__FILE__);
            return view('store_head/asset_order_list',array('error_message'=>$e->getMessage(),'orders_list'=>array()));
        }
    }
}
