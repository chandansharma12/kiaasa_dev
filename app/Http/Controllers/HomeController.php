<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('privacyPolicy');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
    
    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
    
    public function accessDenied(){
         return view('access_denied',array());
    }
    
    public function dashboard(){
        try{
            $categories = $design_types = $user = array();
            $user = Auth::user();
            if($user->status != 1){
                return redirect('/logout');
            }
            
            if(strtolower($user->user_type) == 1){
                return redirect('administrator/dashboard');
            }elseif(strtolower($user->user_type) == 2){
                return redirect('production/dashboard');
            }elseif(strtolower($user->user_type) == 3){
                return redirect('purchaser/dashboard');
            }elseif(strtolower($user->user_type) == 4){
                return redirect('reviewer/dashboard');
            }elseif(strtolower($user->user_type) == 5){
                return redirect('designer/dashboard');
            }elseif(strtolower($user->user_type) == 6){
                return redirect('warehouse/dashboard');
            }elseif(strtolower($user->user_type) == 7){
                return redirect('production-head/dashboard');
            }elseif(strtolower($user->user_type) == 8){
                return redirect('warehouse-head/dashboard');
            }elseif(strtolower($user->user_type) == 9){
                return redirect('store/dashboard');
            }elseif(strtolower($user->user_type) == 10){
                return redirect('store-head/dashboard');
            }elseif(strtolower($user->user_type) == 11){
                return redirect('accounts/dashboard');
            }elseif(strtolower($user->user_type) == 12){
                return redirect('operation/dashboard');
            }elseif(strtolower($user->user_type) == 13){
                return redirect('it/dashboard');
            }elseif(strtolower($user->user_type) == 14){
                return redirect('audit/dashboard');
            }elseif(strtolower($user->user_type) == 15){
                return redirect('vendor/dashboard');
            }elseif(strtolower($user->user_type) == 16){
                return redirect('asm/dashboard');
            }elseif(strtolower($user->user_type) == 17){
                return redirect('hrm/dashboard');
            }elseif(strtolower($user->user_type) == 18){
                return redirect('fic/dashboard');
            }
            $error_msg = '';
            
            $categories = array(); //Category::where('pid',0)->where('is_deleted',0)->where('status',1)->get()->toArray();
            $products = array(); //Product::where('is_deleted',0)->where('status',1)->get()->toArray();
            return view('dashboard',array('categories'=>$categories,'products'=>$products,'user'=>$user,'error_msg'=>$error_msg));
        
        }catch (\Exception $e){	
            echo $error_msg = $e->getMessage();
            return view('dashboard',array('categories'=>array(),'products'=>array(),'user'=>array(),'error_msg'=>$error_msg));
        }  
    }
    
    function privacyPolicy(Request $request){
        try{ 
            return view('privacy_policy',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'Privay_Policy',__FUNCTION__,__FILE__);
            return view('privacy_policy',array('error_message' =>$e->getMessage()));
        }
    }
             
}
