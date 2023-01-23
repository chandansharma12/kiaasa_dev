<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Store_asset;
use App\Models\Store_asset_detail;
use App\Models\Store_asset_order;
use App\Models\Store_asset_order_detail;
use App\Models\Store_asset_bills;
use App\Models\Design_lookup_items_master;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class ASMController extends Controller
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
            return view('asm/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'ASM',__FUNCTION__,__FILE__);
            return view('asm/dashboard',array('error_message' =>$e->getMessage()));
        }
    }
}
