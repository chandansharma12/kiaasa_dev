<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
    }

    function listing(Request $request){
        try{
            $data = $request->all();
            $category_list = \DB::table('category_master as c1')->leftJoin('category_master as c2','c1.pid','=','c2.id');
            
            /*if(isset($data['u_name']) && !empty($data['u_name'])){
                $name_email = trim($data['u_name']);
                $users_list = $users_list->whereRaw("(u.name like '%{$name_email}%' OR u.email = '{$name_email}')");
            }*/
            if(isset($data['pid']) && !empty($data['pid'])){
                $pid = trim($data['pid']);
                $category_list = $category_list->where('pid','=',$pid);
            }
            
            $category_list = $category_list->where('c1.is_deleted',0)->select('c1.*','c2.name as parent_category_name')->paginate(30);
            $parent_categories = Category::where('pid',0)->select('*')->get()->toArray();
            return view('admin/category_list',array('category_list'=>$category_list,'parent_categories'=>$parent_categories,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY',__FUNCTION__,__FILE__);
            return view('admin/category_list',array('error_message'=>$e->getMessage(),'category_list'=>array(),'parent_categories'=>array()));
        }
    }
    
    function data(Request $request,$id){
        try{
            $data = $request->all();
            $category_data = Category::where('id',$id)->select('*')->first();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'category data','category_data' => $category_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function update(Request $request){
        try{
            
            $data = $request->all();
            $category_id = $data['category_edit_id'];
            
            $validateionRules = array('categoryName_edit'=>'required');
            $attributes = array('categoryName_edit'=>'Category Name');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $pid = (isset($data['categoryParent_edit']) && is_numeric($data['categoryParent_edit']))?$data['categoryParent_edit']:0;
            
            $categoryExists = Category::where('name',$data['categoryName_edit'])->where('pid',$pid)->where('id','!=',$category_id)->where('is_deleted',0)->first();
            if(!empty($categoryExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Category already exists', 'errors' => 'Category already exists'));
            }
            
            $updateArray = array('name'=>$data['categoryName_edit'],'pid'=>$pid);
            
            Category::where('id', '=', $category_id)->update($updateArray);
            
            CommonHelper::createLog('Category Updated. ID: '.$category_id,'CATEGORY_UPDATED','CATEGORY');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Category updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateStatus(Request $request){
        try{
            
            $data = $request->all();
            $category_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Category');
            
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
                
            Category::whereIn('id',$category_ids)->update($updateArray);
            CommonHelper::createLog('Category Updated. IDs: '.$data['ids'],'CATEGORY_UPDATED','CATEGORY');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Category updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function add(Request $request){
        try{
            
            $data = $request->all();
            
            $validateionRules = array('categoryName_add'=>'required');
            $attributes = array('categoryName_add'=>'Category');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $pid = (isset($data['categoryParent_add']) && is_numeric($data['categoryParent_add']))?$data['categoryParent_add']:0;
            
            $categoryExists = Category::where('name',$data['categoryName_add'])->where('pid',$pid)->where('is_deleted',0)->first();
            if(!empty($categoryExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Category already exists', 'errors' => 'Category already exists'));
            }
            
            $insertArray = array('name'=>$data['categoryName_add'],'pid'=>$pid);
           
            $category = Category::create($insertArray);
            CommonHelper::createLog('Category Added. ID: '.$category->id,'CATEGORY_ADDED','CATEGORY');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Category added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'CATEGORY',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
}
