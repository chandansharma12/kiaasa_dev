<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product_category_master;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public $productTypes;
    public function __construct(){
        $this->productTypes = array('1'=>'Product','2'=>'Specification sheet','3'=>'Category','4'=>'Subcategory');
    }

    function listing(Request $request){
        try{
            $data = $request->all();
            $product_list = \DB::table('product_category_master as p1')->leftJoin('product_category_master as p2','p1.parent_id','=','p2.id');
            
            /*if(isset($data['u_name']) && !empty($data['u_name'])){
                $name_email = trim($data['u_name']);
                $users_list = $users_list->whereRaw("(u.name like '%{$name_email}%' OR u.email = '{$name_email}')");
            }*/
            if(isset($data['pid']) && !empty($data['pid'])){
                $pid = trim($data['pid']);
                $product_list = $product_list->where('parent_id','=',$pid);
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'p1.id','name'=>'p1.name','type'=>'p1.type_id','parent'=>'p2.name','status'=>'p1.status','created'=>'p1.created_at','updated'=>'p1.updated_at');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'p1.id';
                $product_list = $product_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }
            
            $product_list = $product_list->where('p1.is_deleted',0)->select('p1.*','p2.name as parent_product_name')->paginate(30);
            $parent_products = Product_category_master::where('parent_id',0)->where('type_id',1)->select('*')->get()->toArray();
            $parent_category = Product_category_master::where('type_id',3)->select('*')->get()->toArray();
            return view('admin/product_list',array('product_list'=>$product_list,'parent_products'=>$parent_products,'product_types'=>$this->productTypes,'parent_category'=>$parent_category,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/product_list',array('error_message'=>$e->getMessage(),'product_list'=>array(),'parent_products'=>array(),'product_types'=>array(),'parent_category'=>array()));
        }
    }
    
    function data(Request $request,$id){
        try{
            $data = $request->all();
            $product_data = Product_category_master::where('id',$id)->select('*')->first();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'product data','product_data' => $product_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCT',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function update(Request $request){
        try{
            
            $data = $request->all();
            $product_id = $data['product_edit_id'];
            
            $validateionRules = array('productName_edit'=>'required','productType_edit'=>'required');
            $attributes = array('productName_edit'=>'Name','productType_edit'=>'Type');
            
            if($data['productType_edit'] == 2 || $data['productType_edit'] == 3){
                $validateionRules['productParent_edit'] = 'required';
                $attributes['productParent_edit']  = 'Parent Product';
            }elseif($data['productType_edit'] == 4){
                $validateionRules['productCategory_edit'] = 'required';
                $attributes['productCategory_edit']  = 'Parent Category';
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if($data['productType_edit'] == 2 || $data['productType_edit'] == 3){
                $pid = $data['productParent_edit'];
            }elseif($data['productType_edit'] == 4){
                $pid = $data['productCategory_edit'];
            }else{
                $pid = 0;
            }
            
            $productExists = Product_category_master::where('name',$data['productName_edit'])->where('type_id',$data['productType_edit'])->where('id','!=',$product_id)->where('is_deleted',0)->first();
            if(!empty($productExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product already exists', 'errors' => 'Product already exists'));
            }
            
            $updateArray = array('name'=>$data['productName_edit'],'parent_id'=>$pid,'product_style'=>$data['productStyle_edit'],'type_id'=>$data['productType_edit']);
            
            Product_category_master::where('id', '=', $product_id)->update($updateArray);
            
            CommonHelper::createLog('Product Updated. ID: '.$product_id,'PRODUCT_UPDATED','PRODUCT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCT',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateStatus(Request $request){
        try{
            
            $data = $request->all();
            $product_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Product');
            
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
                
            Product_category_master::whereIn('id',$product_ids)->update($updateArray);
            
            CommonHelper::createLog('Product Updated. IDs: '.$data['ids'],'PRODUCT_UPDATED','PRODUCT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCT',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function add(Request $request){
        try{
            
            $data = $request->all();
            
            $validateionRules = array('productName_add'=>'required','productType_add'=>'required');
            $attributes = array('productName_add'=>'Name','productType_add'=>'Type');
            
            if($data['productType_add'] == 2 || $data['productType_add'] == 3){
                $validateionRules['productParent_add'] = 'required';
                $attributes['productParent_add']  = 'Parent Product';
            }elseif($data['productType_add'] == 4){
                $validateionRules['productCategory_add'] = 'required';
                $attributes['productCategory_add']  = 'Parent Category';
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if($data['productType_add'] == 2 || $data['productType_add'] == 3){
                $pid = $data['productParent_add'];
            }elseif($data['productType_add'] == 4){
                $pid = $data['productCategory_add'];
            }else{
                $pid = 0;
            }
            
            $productExists = Product_category_master::where('name',$data['productName_add'])->where('type_id',$data['productType_add'])->where('is_deleted',0)->first();
            if(!empty($productExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product already exists', 'errors' => 'Product already exists'));
            }
            
            $insertArray = array('name'=>$data['productName_add'],'parent_id'=>$pid,'product_style'=>$data['productStyle_add'],'type_id'=>$data['productType_add']);
           
            $product = Product_category_master::create($insertArray);
            CommonHelper::createLog('Product Created. ID: '.$product->id,'PRODUCT_CREATED','PRODUCT');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCT',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
}
