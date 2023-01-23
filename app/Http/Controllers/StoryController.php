<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Story_master;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class StoryController extends Controller
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
    
    public function add(Request $request){
        try{
            
            $data = $request->all();
            
            $validateionRules = array('name_add'=>'required','designCount_add'=>'required|numeric','productionDesignCount_add'=>'required|numeric');
            $attributes = array('name_add'=>'Name','designCount_add'=>'Design Count','productionDesignCount_add'=>'Production Design Count');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $storyExists = Story_master::where('name',$data['name_add'])->where('is_deleted',0)->first();
            if(!empty($storyExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Story already exists', 'errors' => 'Story already exists'));
            }
            
            $insertArray = array('name'=>$data['name_add'],'design_count'=>$data['designCount_add'],'production_design_count'=>$data['productionDesignCount_add'],'story_year'=>date('Y'));
            
            $story = Story_master::create($insertArray);
            
            CommonHelper::createLog('Story Created. ID: '.$story->id,'STORY_CREATED','STORY');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Story added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORY',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }

    public function update(Request $request){
        try{
            
            $data = $request->all();
            $story_id = $data['story_edit_id'];
            
            $validateionRules = array('name_edit'=>'required','designCount_edit'=>'required|numeric','productionDesignCount_edit'=>'required|numeric');
            $attributes = array('name_edit'=>'Name','designCount_edit'=>'Design Count','productionDesignCount_edit'=>'Production Design Count');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $storyExists = Story_master::where('name',$data['name_edit'])->where('id','!=',$story_id)->where('is_deleted',0)->first();
            if(!empty($storyExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Story already exists', 'errors' => 'Story already exists'));
            }
            
            $updateArray = array('name'=>$data['name_edit'],'design_count'=>$data['designCount_edit'],'production_design_count'=>$data['productionDesignCount_edit']);
           
            Story_master::where('id', '=', $story_id)->update($updateArray);
            
            CommonHelper::createLog('Story Updated. ID: '.$story_id,'STORY_UPDATED','STORY');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Story updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORY',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function storyList(Request $request){
        try{
            $data = $request->all();
            $story_list = \DB::table('story_master as s')->where('s.is_deleted',0);
            
            if(isset($data['s_name']) && !empty($data['s_name'])){
                $name = trim($data['s_name']);
                $story_list = $story_list->whereRaw("(s.name like '%{$name}%')");
            }
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $story_list = $story_list->where('s.id',trim($data['s_id']));
            }
            
            $story_list = $story_list->select('s.*');
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'s.id','name'=>'s.name','year'=>'s.year','design_count'=>'s.design_count','production_count'=>'s.production_design_count','status'=>'s.status','created'=>'s.created_at','updated'=>'s.updated_at');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'s.id';
                $story_list = $story_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }
            
            $story_list = $story_list->paginate(50);
            return view('admin/story_list',array('story_list'=>$story_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORY',__FUNCTION__,__FILE__);
            return view('admin/story_list',array('error_msg'=>$e->getMessage(),'story_list'=>array()));
        }
    }
    
    function storyData(Request $request,$id){
        try{
            $data = $request->all();
            $story_data = \DB::table('story_master as s')->where('s.id',$id)->select('s.*')->first();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Story data','story_data' => $story_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORY',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function storyUpdateStatus(Request $request){
        try{
            
            $data = $request->all();
            $story_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Stories');
            
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
                
            Story_master::whereIn('id',$story_ids)->update($updateArray);
            
            CommonHelper::createLog('Story Updated. IDs: '.$data['ids'],'STORY_UPDATED','STORY');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Stories updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORY',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
}
