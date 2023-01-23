<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class FakeInventoryController extends Controller
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
            return view('fic/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'ASM',__FUNCTION__,__FILE__);
            return view('fic/dashboard',array('error_message' =>$e->getMessage()));
        }
    }
}
