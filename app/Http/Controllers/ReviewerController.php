<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Design;
use Validator;
use Illuminate\Validation\Rule;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Auth;

class ReviewerController extends Controller
{
    
    public function __construct(){
    }
    
    function dashboard(Request $request){
        try{ 
            return view('reviewer/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'REVIEWER',__FUNCTION__,__FILE__);
            return view('reviewer/dashboard',array('error_message'=>$e->getMessage()));
        }
    }
    
    
}
