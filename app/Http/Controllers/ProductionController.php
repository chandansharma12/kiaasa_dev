<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Design;
use App\Models\Vendor_detail;
use App\Models\Design_support_files;
use App\Models\Quotation; 
use App\Models\Quotation_details; 
use App\Models\Quotation_vendors; 
use App\Models\User;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ProductionController extends Controller
{
    
    public function __construct(){
    }
    
    function dashboard(Request $request){
        try{ 
            return view('production/dashboard',array('error_message'=>''));
        }catch (\Exception $e){
            return view('production/dashboard',array('error_message'=>''));
        }
    }
    
    function designList(Request $request){
        try{ 
            $data = $request->all();
        
            $whereArray = array('d.status'=>1,'d.is_deleted'=>0,'d.reviewer_status'=>'approved');
            $approved_designs = CommonHelper::getDesignsList($whereArray);

            return view('production/design_list',array('approved_designs'=>$approved_designs,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'PRODUCTION',__FUNCTION__,__FILE__);
            return view('production/design_list',array('error_message'=>$e->getMessage(),'approved_designs'=>array()));
        }
    }
    
    function updateDesignProductionCount(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            
            $validateionRules = array('production_count'=>'required|integer');
            $attributes = array();
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Update record in database
            Design::where('id', $design_id)->update(array('production_count' => $data['production_count']));
            
            CommonHelper::createLog('Production Count Updated. Design ID: '.$design_id,'PRODUCTION_COUNT_UPDATED','PRODUCTION_COUNT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Production count updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'PRODUCTION_COUNT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','error_message' =>$e->getMessage()),500);
        }  
    }
    
    function skuQuotation(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $design_ids = explode(',',$data['design_ids']);
            $quotation_data = array();
            $designs_list = Design::whereIn('id', $design_ids)->get()->toArray();
            
            $quotation_prod_process = \DB::table('design_items_instance as dii')->Join('design_item_master as dim','dii.design_item_id','=','dim.id')
            ->Join('designs as d','d.id','=','dii.design_id')        
            ->leftJoin('design_lookup_items_master as dlim_1',function($join){$join->on('dim.name_id','=','dlim_1.id')->where('dlim_1.status','=','1')->where('dlim_1.is_deleted','=','0');})        
            ->whereIn('dii.design_id',$design_ids)->where('dii.design_type_id',5)->where('dii.role_id',$user->user_type)->where('dii.status',1)->where('dii.is_deleted',0)->where('dim.status',1)->where('dim.is_deleted',0)
            ->selectraw('d.production_count,dii.*,dlim_1.name as name_id_name')
            ->get()->toArray();
            
            $quotation_pack_sheet = \DB::table('design_items_instance as dii')->Join('design_item_master as dim','dii.design_item_id','=','dim.id')
            ->Join('designs as d','d.id','=','dii.design_id')        
            ->leftJoin('design_lookup_items_master as dlim_1',function($join){$join->on('dim.name_id','=','dlim_1.id')->where('dlim_1.status','=','1')->where('dlim_1.is_deleted','=','0');})        
            ->whereIn('dii.design_id',$design_ids)->where('dii.design_type_id',4)->where('dii.role_id',$user->user_type)->where('dii.status',1)->where('dii.is_deleted',0)->where('dim.status',1)->where('dim.is_deleted',0)
            ->selectraw('d.production_count,dii.*,dlim_1.name as name_id_name')
            ->get()->toArray();
            
            for($i=0;$i<count($designs_list);$i++){
                $design_id = $designs_list[$i]['id'];
                $design_data = $designs_list[$i];
                $prod_process_data = CommonHelper::getDBArray($quotation_prod_process, 'design_id', $design_id);
                $pack_sheet_data = CommonHelper::getDBArray($quotation_pack_sheet, 'design_id', $design_id);
                $quotation_data[] = array('design_data'=>$design_data,'prod_process_data'=>$prod_process_data,'pack_sheet_data'=>$pack_sheet_data);
            }
            
           $vendors_list = Vendor_detail::where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return view('production/sku_quotation',array('quotation_data'=>$quotation_data,'vendors_list'=>$vendors_list,'error_message'=>''));
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'PRODUCTION',__FUNCTION__,__FILE__);
            return view('production/sku_quotation',array('error_message'=>$e->getMessage(),'quotation_data'=>array(),'vendors_list'=>array()));
        } 
    }
    
    function addQuotation(Request $request){
        try{
            $data = $request->all();
            $user_id = Auth::id(); 
            $prod_process_ids = (isset($data['prod_process_ids']) && !empty($data['prod_process_ids']))?explode(',',$data['prod_process_ids']):array();
            $prod_process_qty = (isset($data['prod_process_qty']) && !empty($data['prod_process_qty']))?explode(',',$data['prod_process_qty']):array();
            $pack_sheet_ids = (isset($data['pack_sheet_ids']) && !empty($data['pack_sheet_ids']))?explode(',',$data['pack_sheet_ids']):array();
            $pack_sheet_qty = (isset($data['pack_sheet_qty']) && !empty($data['pack_sheet_qty']))?explode(',',$data['pack_sheet_qty']):array();
            $vendor_ids = (isset($data['vendor_ids']) && !empty($data['vendor_ids']))?explode(',',$data['vendor_ids']):array();
            
            $validateionRules = array('vendor_ids'=>'required');
            if(empty($data['prod_process_ids']) && empty($data['pack_sheet_ids'])) { $validateionRules['pack_sheet_ids'] = 'required'; }
            $attributes = array('vendor_ids'=>'Vendors','pack_sheet_ids'=>'Packaging Sheet or Product Process');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            \DB::beginTransaction();
            
            $insertArray = array('mail_body'=>null,'created_by'=>$user_id,'type_id'=>2);
            $quotation = Quotation::create($insertArray);
           
            $vendors_list = Vendor_detail::whereIn('id',$vendor_ids)->get()->toArray();
            $company_data = CommonHelper::getCompanyData();
            $email_data = CommonHelper::getQuotationEmailData($quotation);
            $email_subject = $email_data['email_subject']; 
            $email_body = $email_data['email_body'];
            
            for($i=0;$i<count($vendor_ids);$i++){
                $vendor_id = $vendor_ids[$i];
                $insertArray = array('quotation_id'=>$quotation->id,'vendor_id'=>$vendor_id);
                Quotation_vendors::create($insertArray);
                for($q=0;$q<count($prod_process_ids);$q++){
                    $id_arr = explode('_',$prod_process_ids[$q]);
                    $id = $id_arr[0];
                    $design_id = $id_arr[1];
                    $qty = $prod_process_qty[$q];
                    $insertArray = array('quotation_id'=>$quotation->id,'item_master_id'=>$id,'quantity'=>$qty,'vendor_id'=>$vendor_id,'design_id'=>$design_id);
                    Quotation_details::create($insertArray);
                }
                
                for($q=0;$q<count($pack_sheet_ids);$q++){
                    $id_arr = explode('_',$pack_sheet_ids[$q]);
                    $id = $id_arr[0];
                    $design_id = $id_arr[1];
                    $qty = $pack_sheet_qty[$q];
                    $insertArray = array('quotation_id'=>$quotation->id,'item_master_id'=>$id,'quantity'=>$qty,'vendor_id'=>$vendor_id,'design_id'=>$design_id);
                    Quotation_details::create($insertArray);
                }
                
                $vendor_data = CommonHelper::getArrayRecord($vendors_list,'id',$vendor_id);
                $quotation_url = url('quotation/submit/'.$quotation->id.'/'.$vendor_id);
                $search_data = array('{{VENDOR_ID}}','{{VENDOR_NAME}}','{{QUOTATION_URL}}','{{COMPANY_TITLE}}','{{COMPANY_NAME}}','{{COMPANY_ADDRESS}}','{{WEBSITE_URL}}');
                $replace_data = array($vendor_data['id'],$vendor_data['name'],$quotation_url,$company_data['company_title'],$company_data['company_name'],$company_data['company_address'],url('/'));
                //CommonHelper::sendEmail($email_subject,$email_body,$search_data,$replace_data,$vendor_data['email'],$vendor_data['name']);
            }
            
            \DB::commit();
            
            CommonHelper::createLog('SKU Quotation Added','SKU_QUOTATION_ADDED','PRODUCTION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'SKU Quotation added successfully','status' => 'success'),200);
            
        }catch (Exception $e) {
            \DB::rollBack();
            CommonHelper::saveException($e,'PRODUCTION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function uploadDesignDocument(Request $request,$design_id){
        try{
            $request_data = $request->all();
            $user = Auth::user();
            $document_type = $request_data['document_type'];
            $attributes = array('design_document_'.$document_type=>'Document');
            $validation = Validator::make($request->all(), ['design_document_'.$document_type => 'required|mimes:pdf,jpeg,png,jpg,gif|max:5120'],array(),$attributes);

            if($validation->passes()){
                $document = $request->file('design_document_'.$document_type);
                $document_name = substr($document->getClientOriginalName(),0,strpos($document->getClientOriginalName(),'.'));
                $document_name = $document_name.'_'.rand(111,99999).'.'.$document->getClientOriginalExtension();

                CommonHelper::createDirectory('documents');
                CommonHelper::createDirectory('documents/production_documents');
                CommonHelper::createDirectory('documents/production_documents/'.$design_id);
                
                $document->move(public_path('documents/production_documents/'.$design_id), $document_name);
                
                $file = Design_support_files::where('design_id',$design_id)->where('file_number',$document_type)->where('is_deleted',0)->first();
                if(!empty($file)){
                    $update_data = array('file_name'=>$document_name);
                    Design_support_files::where('design_id',$design_id)->where('file_number',$document_type)->where('is_deleted',0)->update($update_data);
                }else{
                    $insert_data = array('user_id'=>$user->id,'design_id'=>$design_id,'file_name'=>$document_name,'display_name'=>$document->getClientOriginalName(),'file_number'=>$document_type);
                    Design_support_files::create($insert_data);
                }
                
                CommonHelper::createLog('Design Document Uploaded. Design ID: '.$design_id,'DESIGN_DOCUMENT_UPLOADED','PRODUCTION');
                
                return response()->json([
                    'status'=> 'success','message' => 'Document Upload Successfully','document_name' => $document_name,
                    'document_url'=> url('images/design_images/'.$design_id.'/thumbs/'.$document_name),
                    'document_type' => $document_type,
                ]);
            }else{
                return response()->json([
                    'status'=> 'fail','message'=>$validation->errors()->all(),'uploaded_document' => ''
                ]);
            }
        }catch(Exception $e) {
            CommonHelper::saveException($e,'PRODUCTION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function downloadDesignDocument(Request $request,$design_id,$document_id){
        try{
            $document = Design_support_files::where('design_id',$design_id)->where('file_number',$document_id)->where('is_deleted',0)->first();
            $file = public_path('documents/production_documents/'.$design_id).'/'.$document['file_name'];
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header("Content-Type: application/force-download");
            header('Content-Disposition: attachment; filename=' . urlencode(basename($file)));
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            exit;
        }catch (Exception $e) {
            CommonHelper::saveException($e,'PRODUCTION',__FUNCTION__,__FILE__);
        }
    }
    
    function deleteDesignDocument(Request $request){
        try{
            $request_data = $request->all();
            $user = Auth::user();
            $design_id = $request_data['design_id'];
            $type = $request_data['type'];
            
            $file_data = Design_support_files::where('design_id',$design_id)->where('file_number',$type)->where('is_deleted',0)->first();
            
            $update_data = array('is_deleted'=>1);
            Design_support_files::where('design_id',$design_id)->where('file_number',$type)->update($update_data);
            
            $file_path = 'documents/production_documents/'.$design_id.'/'.$file_data['file_name'];
            if(file_exists(public_path($file_path))){
                unlink($file_path);
            }
            
            CommonHelper::createLog('Design Document Deleted. Design ID: '.$design_id,'DESIGN_DOCUMENT_DELETED','PRODUCTION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Document deleted successfully','status' => 'success'),200);
        }catch(Exception $e) {
            CommonHelper::saveException($e,'PRODUCTION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function requestReview(Request $request,$id){
        try{
            $request_data = $request->all();
            $user = Auth::user();
            $design_id = $id;
            
            $production_file = Design_support_files::where('design_id',$design_id)->where('is_deleted',0)->first();
            if(empty($production_file)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Please upload one document file to request review', 'errors' =>'Please upload one document file to request review' ));
            }
            
            $production_user_data = User::where('id',$user->id)->first();
                    
            if(!isset($production_user_data['parent_user']) || empty($production_user_data['parent_user'])){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Production Head does not exists', 'errors' =>'Production Head does not exists' ));
            }
            
            $design_data = Design::where('id',$design_id)->first();
            if(empty($design_data->production_count)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Production count is 0', 'errors' =>'Production count is 0' ));
            }
            
            $update_data = array('production_status'=>'waiting','production_id'=>$user->id,'production_rev_req_date'=>Date('Y/m/d H:i:s'),'production_head_id'=>$production_user_data['parent_user']);
            Design::where('id',$design_id)->update($update_data);
            
            $design_data->increment('production_version');
            
            CommonHelper::createLog('Production Review Added. Design ID: '.$design_id,'PRODUCTION_REVIEW_ADDED','PRODUCTION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Review request submitted successfully','status' => 'success'),200);
        }catch(Exception $e) {
            CommonHelper::saveException($e,'PRODUCTION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
   
}
