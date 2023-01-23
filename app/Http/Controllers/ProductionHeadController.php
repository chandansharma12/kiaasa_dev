<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Design;
use App\Models\Design_image;
use App\Models\Design_items_instance;
use App\Models\Design_specification_sheet;
use App\Helpers\CommonHelper;
use App\Models\Vendor_detail;
use App\Models\Design_support_files;
use App\Models\Reviewer_comment; 
use App\Models\Notification;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ProductionHeadController extends Controller
{
    
    public function __construct(){
    }
    
    function dashboard(Request $request){
        try{ 
            return view('production_head/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCTION_HEAD',__FUNCTION__,__FILE__);
            return view('production_head/dashboard',array('error_message'=>$e->getMessage()));
        }
    }
    
    function reviewProductionDesign(Request $request,$id){
        try{
            
            $data = $request->all();
            $user_id = Auth::id();    
            
            $design_data = Design::where('id',$id)->where('is_deleted',0)->first();
            $attributes = $validateionRules = array();
            
            if(strtolower($design_data->production_status) == 'waiting'){
                $validateionRules['production_status_sel'] = 'required';
                $validateionRules['production_comment'] = 'required|min:20';
                $attributes = array('production_status_sel'=>'Review Status','production_comment'=>'Comments');
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            \DB::beginTransaction();
            
            if(strtolower($design_data->production_status) == 'waiting'){
                $reviewDBArray = array('design_id'=>$id,'reviewer_id'=>$user_id,'comment'=>$data['production_comment'],'design_status'=>$data['production_status_sel'],'version'=>$design_data['production_version'],'review_type'=>'production');
                $review = Reviewer_comment::create($reviewDBArray);
                
                $designDBArray = array('production_status'=>$data['production_status_sel'],'production_head_id'=>$user_id);
                $design = Design::where('id', '=', $id)->update($designDBArray);
                
                // Save Production history
                if(strtolower($data['production_status_sel']) == 'rejected'){
                    $this->saveProductionHistory($id);
                }
                
                $msg = 'Review added successfully';
            }else{
                $reviewDBArray = array('comment'=>$data['production_comment']);
                $review_data = Reviewer_comment::where('design_id',$id)->where('review_type','production')->where('version',$design_data['production_version'])->first();
                $review = Reviewer_comment::where('id', '=', $review_data->id)->update($reviewDBArray);
                $msg = 'Review updated successfully';
            }
            
            Notification::where('reference_id',$design_data->id)->where('to_user_id',$user_id)->update(array('is_deleted'=>1));
            
            $design_data = Design::where('id',$id)->where('is_deleted',0)->first()->toArray();
            
            \DB::commit();
            
            CommonHelper::createLog('Production Head Review Added. Design ID: '.$design_data['id'],'PRODUCTION_HEAD_REVIEW_ADDED','PRODUCTION_HEAD');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => $msg,'status' => 'success','design_data'=>$design_data,'review_data'=>array()),201);
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'PRODUCTION_HEAD',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function saveProductionHistory($design_id){
        
        $design_data = Design::where('id',$design_id)->first()->toArray();
        $version = $design_data['production_version'];
        unset($design_data['id']);
        $design_data['design_id'] = $design_id;
        $design_data['history_type'] = 'production';
        $design_data['created_at'] = date('Y/m/d H:i:s',strtotime($design_data['created_at']));
        $design_data['updated_at'] = date('Y/m/d H:i:s',strtotime($design_data['updated_at']));

        \DB::table('designs_history')->insert($design_data);

        $design_images = Design_image::where('design_id',$design_id)->get()->toArray();
        for($i=0;$i<count($design_images);$i++){
            unset($design_images[$i]['id']);
            $design_images[$i]['version'] = $version;
            $design_images[$i]['history_type'] = 'production';
            $design_images[$i]['created_at'] = date('Y/m/d H:i:s',strtotime($design_images[$i]['created_at']));
            $design_images[$i]['updated_at'] = date('Y/m/d H:i:s',strtotime($design_images[$i]['updated_at']));
            \DB::table('design_images_history')->insert($design_images[$i]);
        }

        $design_items_instance = Design_items_instance::where('design_id',$design_id)->where('role_id',2)->get()->toArray();
        for($i=0;$i<count($design_items_instance);$i++){
            unset($design_items_instance[$i]['id']);
            $design_items_instance[$i]['version'] = $version;
            $design_items_instance[$i]['history_type'] = 'production';
            $design_items_instance[$i]['created_at'] = date('Y/m/d H:i:s',strtotime($design_items_instance[$i]['created_at']));
            $design_items_instance[$i]['updated_at'] = date('Y/m/d H:i:s',strtotime($design_items_instance[$i]['updated_at']));
            \DB::table('design_items_instance_history')->insert($design_items_instance[$i]);
        }

        $design_specification_sheet = Design_specification_sheet::where('design_id',$design_id)->where('role_id',2)->get()->toArray();
        for($i=0;$i<count($design_specification_sheet);$i++){
            unset($design_specification_sheet[$i]['id']);
            $design_specification_sheet[$i]['version'] = $version;
            $design_specification_sheet[$i]['history_type'] = 'production';
            $design_specification_sheet[$i]['created_at'] = date('Y/m/d H:i:s',strtotime($design_specification_sheet[$i]['created_at']));
            $design_specification_sheet[$i]['updated_at'] = date('Y/m/d H:i:s',strtotime($design_specification_sheet[$i]['updated_at']));
            \DB::table('design_specification_sheet_history')->insert($design_specification_sheet[$i]);
        }
        
        CommonHelper::createLog('Production History Added. Design ID: '.$design_id,'PRODUCTION_HISTORY_ADDED','PRODUCTION_HEAD');
    }
    
    function getProductionReviewsList(Request $request, $id){
        try{
            $design_reviews = Reviewer_comment::where('design_id',$id)->where('review_type','design')->orderBy('id','DESC')->get();
            $design_reviews = (!empty($design_reviews))?$design_reviews->toArray():array();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Reviews','status' => 'success','design_reviews'=>$design_reviews),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCTION_HEAD',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
   
}
