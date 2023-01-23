<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Design;
use App\Models\Design_sizes;
use App\Models\Design_item_master;
use App\Models\Design_items_instance;
use App\Models\Design_review;
use App\Models\Unit;
use App\Models\Product_category_master; 
use App\Models\Design_image; 
use App\Models\Reviewer_comment; 
use App\Models\Design_quotation; 
use App\Models\Vendor_quotation; 
use App\Models\Design_lookup_items_master;
use App\Models\Vendor_detail;
use App\Models\Material_vendor;
use App\Models\Design_size_variations;
use App\Models\User;
use App\Models\Purchase_order;
use App\Models\Purchase_order_items;
use App\Models\Pos_product_master;
use App\Models\Story_master;
use App\Models\Design_specification_sheet;
use App\Models\Production_size_counts;
use App\Models\Design_support_files;
use App\Models\Notification;
use App\Models\Design_items_instance_history;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class DesignController extends Controller
{
    
    public function __construct(){
    }

    public function dashboard(){
        try{
            $user = Auth::user();
            $rec_per_page = 30;
            $whereArray = array('d.status'=>1,'d.is_deleted'=>0,'d.user_id'=>$user->id);
            //$designs = CommonHelper::getDesignsList($whereArray);
            
            $designs = \DB::table('designs as d')
            ->leftJoin('design_lookup_items_master as s','d.season_id','=','s.id')
            ->leftJoin('design_lookup_items_master as c','c.id','=','d.color_id')
            ->leftJoin('design_lookup_items_master as d1','d1.id','=','d.category_id')        
            ->leftJoin('design_lookup_items_master as d2','d2.id','=','d.sub_cat_id')                
            ->leftJoin('product_category_master as dt','d.design_type_id','=','dt.id')
            ->leftJoin('users as u1','u1.id','=','d.user_id')        
            ->leftJoin('story_master as sm','sm.id','=','d.story_id')
            ->leftJoin('design_images as di',function($join){$join->on('d.id','=','di.design_id')->where('di.image_type','=','front')->where('di.is_deleted','=','0')->where('di.status','=','1');});                
            
            $designs = $designs->where($whereArray);
            
            $designs = $designs->select('d.*','s.name as season_name','sm.name as story_name','dt.name as design_type_name','d1.name as category_name','d2.name as subcategory_name',
            'c.name as color_name','u1.name as designer_name','sm.name as story_name','di.image_name')
            ->orderBy('d.id','DESC')        
            ->paginate($rec_per_page);
            
            return view('designer/dashboard',array('designs'=>$designs,'error_message'=>''));
        }catch (\Exception $e){	
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return view('designer/dashboard',array('designs'=>array(),'error_message'=>$e->getMessage()));
        }  
    }
    
    function getProductsList(Request $request){
        try{
            //\DB::enableQueryLog();
            $request_data = $request->all();
            $designs = array();
            $error_msg = '';
            $user = Auth::user();
            $user_id = Auth::id();
            
            $designs = \DB::table('designs as d')->leftJoin('design_images as di',function($join){
            $join->on('d.id','=','di.design_id')
                        ->where('di.image_type','=','front')
                        ->where('di.is_deleted','=','0')     
                        ->where('di.status','=','1');
            });
            
            $designs = $designs->leftJoin('reviewer_comments as rc',function($join){
            $join->on('d.id','=','rc.design_id')
                ->on('d.version','=','rc.version');
            });
            
            $designs = $designs->leftJoin('users as u1','u1.id','=','rc.reviewer_id');
            
            $designs = $designs->Join('users as u','u.id','=','d.user_id');
            
            $designs = $designs->leftJoin('product_category_master as p','p.id','=','d.design_type_id');
            
            $designs = $designs->leftJoin('story_master as sm','sm.id','=','d.story_id');
            
            if(isset($request_data['searchText']) && !empty($request_data['searchText'])){
                $designs = $designs->where('d.sku','LIKE','%'.$request_data['searchText'].'%');
            }
            
            if(isset($request_data['statusArray']) && !empty($request_data['statusArray'])){
                $designs = $designs->whereIn('d.design_status',$request_data['statusArray']);
            }
            
            if(isset($request_data['categoryArray']) && !empty($request_data['categoryArray'])){
                $designs = $designs->whereIn('d.category_id',$request_data['categoryArray']);
            }
            
            if(isset($request_data['productArray']) && !empty($request_data['productArray'])){
                $designs = $designs->whereIn('d.design_type_id',$request_data['productArray']);
            }
            
            if($user->user_type == 'user'){
                $designs = $designs->where('d.user_id',$user_id);
            }
            
            if($user->user_type == 'reviewer'){
                $designs = $designs->where('d.is_requisition_created',1);
            }
            
            if($user->user_type == 'purchaser'){
                $designs = $designs->where('d.reviewer_status','approved');
            }
            
            $designs = $designs->where('d.is_deleted',0);//->where('d.status',1);
            
            $designs = $designs->select('d.*','di.image_name','u.name as designer_name','u1.name as reviewer_name','rc.created_at as date_reviewed','p.name as product_name','sm.name as story_name')->orderByRaw('d.id DESC')->paginate(20);
            $paging_links =  (string) $designs->links();
            
            for($i=0;$i<count($designs);$i++){
                
                if(!empty($designs[$i]->image_name) && file_exists(public_path('images/design_images/'.$designs[$i]->id.'/thumbs/'.$designs[$i]->image_name))){
                    $designs[$i]->image_path = asset('images/design_images/'.$designs[$i]->id.'/thumbs/'.$designs[$i]->image_name);
                }else{
                    $designs[$i]->image_path = asset('images/pro2.jpg');
                }
                
                if(!empty($designs[$i]->reviewer_status)) $designs[$i]->reviewer_status = ucfirst($designs[$i]->reviewer_status);
                if(!empty($designs[$i]->created_at)) $designs[$i]->date_created = date('d M Y',strtotime($designs[$i]->created_at));
            }
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'products list','designs' => $designs,'user_type'=>$user->user_type,'paging_links'=>$paging_links,'status' => 'success'),200);
        }catch (\Exception $e){	
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function addDesign(Request $request){
        try{
            $request_data = $request->all();
            //$user_id = Auth::id();
            $user = Auth::user();    
            $design = new Design();
            $design->user_id = $user->id;
            $design->save();

            /* Code to insert packaging sheet data start */
            $design_items_master_data = Design_item_master::where('type_id',4)->get()->toArray();

            // Add records in design items master table
            $design_lookup_items = Design_lookup_items_master::whereraw("lower(type) = 'packaging_sheet'")->get()->toArray();
            for($i=0;$i<count($design_lookup_items);$i++){
                if(!$this->searchDBArray($design_items_master_data,'name_id',$design_lookup_items[$i]['id'])){
                    $insertArray = array('type_id'=>4,'name_id'=>$design_lookup_items[$i]['id']);
                    Design_item_master::create($insertArray);
                }
            }

            $design_items_data = Design_item_master::where('type_id',4)->get()->toArray();

            // Add records into design items instance table
            for($i=0;$i<count($design_items_data);$i++){
                $packagingSheetDBArray = array('design_id'=>$design->id,'design_item_id'=>$design_items_data[$i]['id'],'design_type_id'=>4,'body_part_id'=>0,'width'=>0,'avg'=>0,'rate'=>0,'cost'=>0,'unit_id'=>1,'size'=>0,'qty'=>0,'role_id'=>$user->user_type);
                $packagingSheet = Design_items_instance::create($packagingSheetDBArray);
            }

            /* Code to insert packaging sheet data end */
            
            CommonHelper::createLog('New Design Added. ID: '.$design->id,'DESIGN_ADDED','DESIGN');
            
            return redirect('design/edit/'.$design->id);
        
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return redirect('dashboard')->with('error_message', $e->getMessage());
        }  
    }
    
    public function editDesign(Request $request,$id){
        try{
            $error_msg = '';
            $user = Auth::user();
            $data = $request->all();
            $display_ds_head_notif_btn = false;
            $design_data = Design::where('id',$id)->where('is_deleted',0)->where('status',1)->first();
            
            if(!empty($design_data)){
                $design_data = $design_data->toArray();
                $user_id = Auth::id();
                if($design_data['user_id'] == $user_id){
                    
                    /* Code to insert packaging sheet data start */
                    
                    // Fetch design items data for type id 4 of packaging sheet in design items instance table for design id
                    $design_items_instance_data = Design_items_instance::where('design_id',$design_data['id'])->where('design_type_id',4)->get()->toArray();
                    // Fetch records from design lookup items master table of type packaging sheet
                    $design_lookup_items = Design_lookup_items_master::whereraw("lower(type) = 'packaging_sheet'")->get()->toArray();
                    
                    // Check if count is not equal
                    if(count($design_items_instance_data) != count($design_lookup_items)){
                        
                        $design_items_master_data = Design_item_master::where('type_id',4)->get()->toArray();
                        
                        // Add records to design items master table
                        for($i=0;$i<count($design_lookup_items);$i++){
                           if(!$this->searchDBArray($design_items_master_data,'name_id',$design_lookup_items[$i]['id'])){
                                $insertArray = array('type_id'=>4,'name_id'=>$design_lookup_items[$i]['id']);
                                Design_item_master::create($insertArray);
                            }
                        }

                        $design_items_master_data = Design_item_master::where('type_id',4)->get()->toArray();

                        // Add records into design items instance table
                        for($i=0;$i<count($design_items_master_data);$i++){
                            if(!$this->searchDBArray($design_items_instance_data,'design_item_id',$design_items_master_data[$i]['id'])){
                                $packagingSheetDBArray = array('design_id'=>$design_data['id'],'design_item_id'=>$design_items_master_data[$i]['id'],'design_type_id'=>4,'body_part_id'=>0,'width'=>0,'avg'=>0,'rate'=>0,'cost'=>0,'unit_id'=>1,'size'=>0,'qty'=>0,'role_id'=>$user->user_type);
                                $packagingSheet = Design_items_instance::create($packagingSheetDBArray);
                            }
                        }
                    }
                    
                    /* Code to insert packaging sheet data end */
                    
                    /* Code to insert garment cmt data start */
                    
                    $design_items_instance_data = Design_items_instance::where('design_id',$design_data['id'])->where('design_type_id',7)->get()->toArray();
                    $design_lookup_items = Design_lookup_items_master::whereraw("lower(type) = 'garment_cmt'")->get()->toArray();
                    
                    // Check if count is not equal
                    if(count($design_items_instance_data) != count($design_lookup_items)){
                        
                        $design_items_master_data = Design_item_master::where('type_id',7)->get()->toArray();
                        
                        // Add records to design items master table
                        for($i=0;$i<count($design_lookup_items);$i++){
                           if(!$this->searchDBArray($design_items_master_data,'name_id',$design_lookup_items[$i]['id'])){
                                $insertArray = array('type_id'=>7,'name_id'=>$design_lookup_items[$i]['id']);
                                Design_item_master::create($insertArray);
                            }
                        }

                        $design_items_master_data = Design_item_master::where('type_id',7)->get()->toArray();

                        // Add records into design items instance table
                        for($i=0;$i<count($design_items_master_data);$i++){
                            if(!$this->searchDBArray($design_items_instance_data,'design_item_id',$design_items_master_data[$i]['id'])){
                                $packagingSheetDBArray = array('design_id'=>$design_data['id'],'design_item_id'=>$design_items_master_data[$i]['id'],'design_type_id'=>7,'body_part_id'=>0,'width'=>0,'avg'=>0,'rate'=>0,'cost'=>0,'unit_id'=>7,'size'=>0,'qty'=>0,'role_id'=>$user->user_type);
                                $packagingSheet = Design_items_instance::create($packagingSheetDBArray);
                            }
                        }
                    }
                    
                    /* Code to insert garment cmt data end */
                    
                    //$seasons = Design_lookup_master::whereraw("lower(type) = 'season'")->where('is_deleted',0)->where('status',1)->get()->toArray();
                    $products = Product_category_master::where('is_deleted',0)->where('type_id',1)->where('parent_id',0)->where('status',1)->get()->toArray();
                    $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
                    
                    $units = Unit::where('is_deleted',0)->where('status',1)->get()->toArray();
                    $stories = Story_master::where('is_deleted',0)->where('status',1)->get()->toArray();
                    $design_items = Design_lookup_items_master::where('is_deleted',0)->where('status',1)->orderBy('name')->get()->toArray();
                    
                    $design_images = Design_image::where('design_id',$id)->where('is_deleted',0)->where('status',1)->get()->toArray();
                    $design_images_types = array();
                    for($i=0;$i<count($design_images);$i++){
                        $design_images_types[$design_images[$i]['image_type']] = $design_images[$i];
                    }
                    
                    if($design_data['reviewer_status'] == 'waiting' && $design_data['is_requisition_created'] == 1 ){
                        $requsition_created_hours_diff = ((strtotime(date('Y/m/d H:i:s')) - strtotime($design_data['requisition_created_date']))/3600);
                        $notification_data = Notification::where('reference_id',$design_data['id'])->where('template_id',1)->where('is_deleted',0)->get()->toArray();
                        if($requsition_created_hours_diff >= 24 &&  empty($notification_data)){
                            $display_ds_head_notif_btn = true;
                        }
                        
                        if($requsition_created_hours_diff >= 48 &&  count($notification_data) == 1){
                            $display_ds_head_notif_btn = true;
                        }
                    }
                    
                    $design_accessories_data = $this->getDesignItemData('accessories',$design_data['id'],false,'','');
                    $design_fabric_data =  $this->getDesignItemData('fabric',$design_data['id'],false,'','');
                    $design_packaging_sheet_data = $this->getDesignItemData('packaging_sheet',$design_data['id'],false,'','');
                    $design_embroidery_data = $this->getDesignItemData('embroidery',$design_data['id'],false,'','');
                    $design_printing_data = $this->getDesignItemData('printing',$design_data['id'],false,'','');
                    $design_garment_cmt_data = $this->getDesignItemData('garment_cmt',$design_data['id'],false,'','');
                    $design_sizes = Design_sizes::where('design_id',$design_data['id'])->where('is_deleted',0)->get()->toArray();
                    
                    $view_data = array('products'=>$products,'units'=>$units,'stories'=>$stories,'size_list'=>$size_list);
                    $view_data['design_data'] = $design_data;
                    $view_data['images'] = $design_images;
                    $view_data['images_types'] = $design_images_types;
                    $view_data['design_items'] = $design_items;
                    $view_data['error_message'] = $error_msg;
                    $view_data['currency'] = CommonHelper::getCurrency();
                    $view_data['user'] = $user;
                    $view_data['display_ds_head_notif_btn'] = $display_ds_head_notif_btn;
                    $view_data['design_fabric_data'] = $design_fabric_data;
                    $view_data['design_accessories_data'] = $design_accessories_data;
                    $view_data['design_packaging_sheet_data'] = $design_packaging_sheet_data;
                    $view_data['design_embroidery_data'] = $design_embroidery_data;
                    $view_data['design_printing_data'] = $design_printing_data;
                    $view_data['design_garment_cmt_data'] = $design_garment_cmt_data;
                    $view_data['design_sizes'] = $design_sizes;
                    
                   return view('designer/add_design',$view_data);
                }else{
                    return redirect('dashboard')->with('error_message', 'You do not have permission to access this design');
                }
            }else{
                return redirect('dashboard')->with('error_message', 'Design does not exists');
            }
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return view('designer/add_design',array('error_message'=>$e->getMessage()));
        }  
    }
    
    public function designDetail(Request $request,$id,$version = '',$history_type = ''){
        $user = Auth::user();
        try{
            $error_msg = $design_id =  $history_type_name ='';
            $data = $request->all();
            $display_ph_head_notif_btn = false;
            $design_info = Design::where('id',$id)->first();
            if(!empty($design_info)){
                $display_history_data = CommonHelper::displayHistoryData($design_info,$version,$history_type);
                
                if(!$display_history_data){
                    $design_data = CommonHelper::getDesignDetail($id);
                    $design_data = json_decode(json_encode($design_data),true);
                
                    $design_images = Design_image::where('design_id',$id)->where('is_deleted',0)->where('status',1)->get()->toArray();
                    $design_id = $design_data['id'];
                    $history = 0;
                }else{
                    $historyTypeId = ($history_type == '' )?1:$history_type;
                    $history_type_name = CommonHelper::getHistoryType($historyTypeId);
                    $design_data = CommonHelper::getDesignDetail($id,true,$version,$history_type_name);
                    $design_data = json_decode(json_encode($design_data),true);
                    
                    $design_id = $design_data['design_id'];
                    if($historyTypeId == 1){
                        $design_images = \DB::table('design_images_history')->where('design_id',$id)->where('history_type','design')->where('version',$version)->where('is_deleted',0)->where('status',1)->get()->toArray();
                    }else{
                        $design_images = \DB::table('design_images_history')->where('design_id',$id)->where('history_type','production')->where('version',$version)->where('is_deleted',0)->where('status',1)->get()->toArray();
                    }
                    $design_images = json_decode(json_encode($design_images),true);
                    $history = 1;
                }
            
                $design_images_types = array();
                for($i=0;$i<count($design_images);$i++){
                    $design_images_types[$design_images[$i]['image_type']] = $design_images[$i];
                }
                
                $design_items = Design_lookup_items_master::where('is_deleted',0)->where('status',1)->get()->toArray();
                $currency = CommonHelper::getCurrency();
                
                // Insert rows of designer for production user. 
                if($user->user_type == 2){
                    $design_instances_production = Design_items_instance::where('design_id',$id)->where('role_id',$user->user_type)->where('is_deleted',0)->get()->toArray();
                    $design_instances_designer = Design_items_instance::where('design_id',$id)->where('role_id',5)->where('is_deleted',0)->get()->toArray();
                    for($i=0;$i<count($design_instances_designer);$i++){
                        if(!CommonHelper::DBArrayExists($design_instances_production,'pid',$design_instances_designer[$i]['id'])){
                            $insertArray = $design_instances_designer[$i];
                            $insertArray['role_id'] = $user->user_type;
                            $insertArray['pid'] = $design_instances_designer[$i]['id'];
                            
                            if($insertArray['design_type_id'] == 1){    // Fabric
                                $insertArray['avg'] = $insertArray['rate'] = $insertArray['cost'] =  0;
                            }elseif($insertArray['design_type_id'] == 2){   // Acc
                                $insertArray['avg'] = $insertArray['rate'] = $insertArray['cost'] =  0;
                            }elseif($insertArray['design_type_id'] == 3){   // Fabric Process
                                $insertArray['avg'] = $insertArray['rate'] = $insertArray['cost'] =  0;
                            }elseif($insertArray['design_type_id'] == 4){   // Packaging Sheet
                                $insertArray['avg'] = $insertArray['rate'] = $insertArray['cost'] =  0;
                            }elseif($insertArray['design_type_id'] == 5){   // Product Process
                                $insertArray['rate'] = $insertArray['cost'] =  0;
                            }
                            Design_items_instance::create($insertArray);
                        }
                    }
                }
                
                $units = Unit::where('is_deleted',0)->where('status',1)->get()->toArray();
                
                $design_support_files_data = array();
                $design_support_files = Design_support_files::where('design_id',$id)->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($design_support_files);$i++){
                    $design_support_files_data[$design_support_files[$i]['file_number']] = $design_support_files[$i];
                }
                
                if($design_data['production_status'] == 'waiting' ){
                    $production_review_hours_diff = ((strtotime(date('Y/m/d H:i:s')) - strtotime($design_data['production_rev_req_date']))/3600);
                    $notification_data = Notification::where('reference_id',$design_data['id'])->where('template_id',2)->where('is_deleted',0)->get()->toArray();
                    if($production_review_hours_diff >= 24 &&  empty($notification_data)){
                        $display_ph_head_notif_btn = true;
                    }

                    if($production_review_hours_diff >= 48 &&  count($notification_data) == 1){
                        $display_ph_head_notif_btn = true;
                    }
                }
                
                $design_accessories_data = $this->getDesignItemData('accessories',$design_data['id'],false,'','');
                $design_fabric_data =  $this->getDesignItemData('fabric',$design_data['id'],false,'','');
                $design_packaging_sheet_data = $this->getDesignItemData('packaging_sheet',$design_data['id'],false,'','');
                $design_embroidery_data = $this->getDesignItemData('embroidery',$design_data['id'],false,'','');
                $design_printing_data = $this->getDesignItemData('printing',$design_data['id'],false,'','');
                $design_garment_cmt_data = $this->getDesignItemData('garment_cmt',$design_data['id'],false,'','');
                
                $view_data = array('design_data'=>$design_data,'images'=>$design_images_types,'user'=>$user,'design_id'=>$design_id,'version'=>$version,'history_type'=>$history_type,
                'design_items'=>$design_items,'history'=>$history,'design_info'=>$design_info,'currency'=>$currency,'units'=>$units,'design_support_files'=>$design_support_files_data,
                'display_ph_head_notif_btn'=>$display_ph_head_notif_btn,'error_message'=>'');
                
                $view_data['design_fabric_data'] = $design_fabric_data;
                $view_data['design_accessories_data'] = $design_accessories_data;
                $view_data['design_packaging_sheet_data'] = $design_packaging_sheet_data;
                $view_data['design_embroidery_data'] = $design_embroidery_data;
                $view_data['design_printing_data'] = $design_printing_data;
                $view_data['design_garment_cmt_data'] = $design_garment_cmt_data;
                
                // Download CSV start
                if(isset($data['action']) && $data['action'] == 'download_costing_csv'){
                    $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=design_costing.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                    $columns = array('SNo','Particular','Avg','Rate','PCS/MTR','Amount','');

                    $callback = function() use ($view_data, $columns){
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $columns);
                        $total_data = array('fabric'=>0,'acc'=>0,'pack'=>0,'emb'=>0,'cmt'=>0,'print'=>0);

                        $design_fabric_data = $view_data['design_fabric_data'];
                        $design_accessories_data = $view_data['design_accessories_data'];
                        $design_packaging_sheet_data = $view_data['design_packaging_sheet_data'];
                        $design_embroidery_data = $view_data['design_embroidery_data'];
                        $design_printing_data = $view_data['design_printing_data'];
                        $design_garment_cmt_data = $view_data['design_garment_cmt_data'];

                        // Fabric data
                        $array = array('1','Fabric','','','','');
                        fputcsv($file, $array);

                        for($i=0;$i<count($design_fabric_data);$i++){
                            $array = array('',$design_fabric_data[$i]->fabric_name,$design_fabric_data[$i]->avg,$design_fabric_data[$i]->rate,$design_fabric_data[$i]->unit_code,$design_fabric_data[$i]->cost);
                            fputcsv($file, $array);
                            $total_data['fabric']+=$design_fabric_data[$i]->cost;
                        }

                        $array = array('','Total','','','',$total_data['fabric']);
                        fputcsv($file, $array);

                        fputcsv($file, array('','','','','',''));

                        // Accessories data
                        $array = array('2','Accessories','','','','');
                        fputcsv($file, $array);

                        for($i=0;$i<count($design_accessories_data);$i++){
                            $array = array('',$design_accessories_data[$i]->category_name,$design_accessories_data[$i]->avg,$design_accessories_data[$i]->rate,$design_accessories_data[$i]->unit_code,$design_accessories_data[$i]->cost);
                            fputcsv($file, $array);
                            $total_data['acc']+=$design_accessories_data[$i]->cost;
                        }

                        $array = array('','Total','','','',$total_data['acc']);
                        fputcsv($file, $array);

                        fputcsv($file, array('','','','','',''));

                        // Packaging Sheet data
                        $array = array('3','Packaging Sheet','','','','');
                        fputcsv($file, $array);

                        for($i=0;$i<count($design_packaging_sheet_data);$i++){
                            if($design_packaging_sheet_data[$i]->avg > 0){
                                $array = array('',$design_packaging_sheet_data[$i]->packaging_sheet_name,$design_packaging_sheet_data[$i]->avg,$design_packaging_sheet_data[$i]->rate,'PCS',$design_packaging_sheet_data[$i]->cost);
                                fputcsv($file, $array);
                                $total_data['pack']+=$design_packaging_sheet_data[$i]->cost;
                            }
                        }

                        $array = array('','Total','','','',$total_data['pack']);
                        fputcsv($file, $array);

                        fputcsv($file, array('','','','','',''));

                        // Embroidery data
                        $array = array('4','Embroidery','','','','');
                        fputcsv($file, $array);

                        for($i=0;$i<count($design_embroidery_data);$i++){
                            $array = array('',$design_embroidery_data[$i]->embroidery_type,'',$design_embroidery_data[$i]->rate,$design_embroidery_data[$i]->unit_code,$design_embroidery_data[$i]->cost);
                            fputcsv($file, $array);
                            $total_data['emb']+=$design_embroidery_data[$i]->cost;
                        }

                        $array = array('','Total','','','',$total_data['emb']);
                        fputcsv($file, $array);

                        fputcsv($file, array('','','','','',''));
                        
                        // Printing data
                        $array = array('5','Printing','','','','');
                        fputcsv($file, $array);

                        for($i=0;$i<count($design_printing_data);$i++){
                            $array = array('',$design_printing_data[$i]->printing_type,'',$design_printing_data[$i]->rate,$design_printing_data[$i]->unit_code,$design_printing_data[$i]->cost);
                            fputcsv($file, $array);
                            $total_data['print']+=$design_printing_data[$i]->cost;
                        }

                        $array = array('','Total','','','',$total_data['print']);
                        fputcsv($file, $array);

                        fputcsv($file, array('','','','','',''));

                        // Garment CMT data
                        $array = array('6','Garment CMT','','','','');
                        fputcsv($file, $array);

                        for($i=0;$i<count($design_garment_cmt_data);$i++){
                            if(strtolower($design_garment_cmt_data[$i]->garment_cmt_name) == 'margin') $margin = $design_garment_cmt_data[$i]->rate;
                            if($design_garment_cmt_data[$i]->rate > 0 && strtolower($design_garment_cmt_data[$i]->garment_cmt_name) != 'margin'){
                                $array = array('',$design_garment_cmt_data[$i]->garment_cmt_name,'',$design_garment_cmt_data[$i]->rate,$design_garment_cmt_data[$i]->unit_code,$design_garment_cmt_data[$i]->cost);
                                fputcsv($file, $array);
                                $total_data['cmt']+=$design_garment_cmt_data[$i]->cost;
                            }
                        }

                        $array = array('','Total','','','',$total_data['cmt']);
                        fputcsv($file, $array);

                        fputcsv($file, array('','','','','',''));

                        $total_price  = round($total_data['fabric']+$total_data['acc']+$total_data['pack']+$total_data['emb']+$total_data['print']+$total_data['cmt'],2);
                        $array = array('','Total Price','','','',$total_price);
                        fputcsv($file, $array);

                        $margin_amt =  round($total_price*($margin/100),2);
                        $array = array('','Margin','',$margin,'%',$margin_amt);
                        fputcsv($file, $array);

                        $array = array('','Net Price','','','',$total_price+$margin_amt);
                        fputcsv($file, $array);

                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }

                // Download CSV end
                
                return view('designer/design_detail',$view_data);
            }else{
                throw new \Exception('Design does not exists');
            }
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return view('designer/design_detail',array('design_data'=>array(),'error_message'=>$e->getMessage()));
        }  
    }
    
    public function updateDesignDetail(Request $request,$design_id){
        try{
            $user = Auth::user();
            $data = $request->all();
            
            // designer_submitted column is to check of design is submiited by designer. It ia updated to 1 when requisition is created and is not updated again.
            // is_requisition_created column is updated from 0 to 1 when requisition is created. It is updated again to 0 only when purchaser disapprove design. 
            // is_requisition_created is used to allow designer to edit design design.
            
            if(isset($data['action']) && $data['action'] == 'submit_purchaser_review'){
                $validateionRules = array('purchaser_review_sel'=>'required','purchaser_review_comment'=>'required|max:250');

                $attributes = array('purchaser_review_sel'=>'Review','purchaser_review_comment'=>'Comment');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                \DB::beginTransaction();
                
                $updateArray = array('purchaser_review'=>$data['purchaser_review_sel'],'purchaser_review_date'=>date('Y/m/d H:i:s'),'purchaser_review_comment'=>trim($data['purchaser_review_comment']));
                
                // design review is final review for designer
                if(strtolower($data['purchaser_review_sel']) == 'disapproved'){
                    $updateArray['design_review'] = 'disapproved';
                    $updateArray['is_requisition_created'] = 0;
                    $updateArray['requisition_created_date'] = null;
                }
                
                Design::where('id',$design_id)->update($updateArray);
                
                $insertArray = array('design_id'=>$design_id,'user_id'=>$user->id,'role_id'=>$user->user_type,'review_status'=>$data['purchaser_review_sel'],'comment'=>trim($data['purchaser_review_comment']));
                Design_review::create($insertArray);
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Review submitted successfully'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'submit_management_review'){
                $validateionRules = array('management_review_sel'=>'required','management_review_comment'=>'required|max:250');

                $attributes = array('management_review_sel'=>'Review','management_review_comment'=>'Comment');

                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                 
                \DB::beginTransaction();
                
                $updateArray = array('management_review'=>$data['management_review_sel'],'management_review_date'=>date('Y/m/d H:i:s'),'management_review_comment'=>trim($data['management_review_comment']));
                
                if(strtolower($data['management_review_sel']) == 'approved'){
                    $updateArray['design_review'] = 'approved';
                }
                
                // if management has disapproved, purchaser review is marked as pending and purchaser have to review it again to inform designer.
                if(strtolower($data['management_review_sel']) == 'disapproved'){
                    $updateArray['purchaser_review'] = 'pending';
                }
                Design::where('id',$design_id)->update($updateArray);
                
                $insertArray = array('design_id'=>$design_id,'user_id'=>$user->id,'role_id'=>$user->user_type,'review_status'=>$data['management_review_sel'],'comment'=>trim($data['management_review_comment']));
                Design_review::create($insertArray);
                
                if(strtolower($data['management_review_sel']) == 'approved'){
                    $design_sizes = \DB::table('design_sizes as ds')
                    ->Join('designs as d','d.id','=','ds.design_id')        
                    ->where('d.id',$design_id)
                    ->where('d.is_deleted',0)
                    ->where('d.status',1)   
                    ->where('ds.is_deleted',0)        
                    ->select('d.*','ds.size_id')
                    ->get()->toArray();
                    
                    for($i=0;$i<count($design_sizes);$i++){
                        $design_info = $design_sizes[$i];
                        
                        $insertArray = array('product_name'=>$design_info->product_name,'custom_product'=>1,
                        'story_id'=>$design_info->story_id,'season_id'=>$design_info->season_id,'product_description'=>$design_info->description,'product_type'=>'design',
                        'category_id'=>$design_info->category_id,'subcategory_id'=>$design_info->sub_cat_id,'size_id'=>$design_info->size_id,'color_id'=>$design_info->color_id,
                        'base_price'=>$design_info->net_cost,'hsn_code'=>$design_info->hsn_code,'product_sku'=>$design_info->sku,'gst_inclusive'=>0,
                        'vendor_product_sku'=>null,'sale_price'=>$design_info->mrp,'user_id'=>$design_info->user_id);
                        
                        $product = Pos_product_master::create($insertArray);
                    }
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Review submitted successfully'),200);
            }
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }      
    }
    
    public function uploadImage(Request $request,$design_id){
        $request_data = $request->all();
        $image_type = $request_data['image_type'];
        $validation = Validator::make($request->all(), ['design_image_'.$image_type => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);
        
        if($validation->passes()){
            $image_name = CommonHelper::uploadImage($request,'design_image_'.$image_type,'images/design_images/'.$design_id);
            
            return response()->json([
                'status'=> 'success','message' => 'Image Upload Successfully','image_name' => $image_name,
                'image_url'=> url('images/design_images/'.$design_id.'/thumbs/'.$image_name),
                'image_type' => $image_type,
            ]);
        }else{
            return response()->json([
                'status'=> 'fail','message' => $validation->errors()->all(),'uploaded_image' => ''
            ]);
        }
    }
    
    function getDesignItemData($type,$design_id,$history_data = false,$history_type = '',$version = ''){
        $user = Auth::user();
        
        //$user_type = CommonHelper::getDataRole($user->user_type);
        $user_type = 5;// designer
            
        if($history_data == true){
            $historyTypeId = ($history_type == '')?1:$history_type;
            $history_type = CommonHelper::getHistoryType($historyTypeId);
            
            if($type == 'fabric'){
                $design_data = \DB::table('design_items_instance_history as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->join('design_lookup_items_master as dlim_2','dim.color_id', '=', 'dlim_2.id')->join('design_lookup_items_master as dlim_3','dim.content_id', '=', 'dlim_3.id')->join('design_lookup_items_master as dlim_4','dim.gsm_id', '=', 'dlim_4.id')->join('design_lookup_items_master as dlim_5','dim.width_id', '=', 'dlim_5.id')->join('design_lookup_items_master as dlim_6','dii.body_part_id', '=', 'dlim_6.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',1)->where('dii.is_deleted',0)->where('dii.status',1)->where('dii.version',$version)->where('dii.history_type',$history_type)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as fabric_name','dlim_2.name as color_name','dlim_3.name as content_name','dlim_4.name as gsm_name','dlim_5.name as width_name','dlim_6.name as body_part_name','dim.name_id','dim.color_id','dim.content_id','dim.gsm_id','dim.width_id','dim.unit_id as master_unit_id')->get()->toArray();
            }elseif($type == 'accessories'){
                $design_data = \DB::table('design_items_instance_history as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->join('design_lookup_items_master as dlim_2','dim.quality_id', '=', 'dlim_2.id')->join('design_lookup_items_master as dlim_3','dim.color_id', '=', 'dlim_3.id')->join('design_lookup_items_master as dlim_4','dim.content_id', '=', 'dlim_4.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',2)->where('dii.is_deleted',0)->where('dii.status',1)->where('dii.version',$version)->where('dii.history_type',$history_type)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as category_name','dlim_2.name as quality_name','dlim_3.name as color_name','dlim_4.name as size_name','dim.name_id as category_id','dim.quality_id','dim.color_id','dim.content_id as size_id')->get()->toArray();
            }elseif($type == 'process'){
                $design_data = \DB::table('design_items_instance_history as dii')->join('units','units.id', '=', 'dii.unit_id')
                ->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')
                ->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')
                ->leftJoin('design_lookup_items_master as dlim_2','dim.quality_id', '=', 'dlim_2.id')
                ->join('design_items_instance as dii_fabric','dii.fabric_instance_id', '=', 'dii_fabric.id')
                ->join('design_item_master as dim_fabric','dim_fabric.id', '=', 'dii_fabric.design_item_id')        
                ->join('design_lookup_items_master as dlim_fabric_1','dlim_fabric_1.id', '=', 'dim_fabric.name_id')              
                ->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',3)->where('dii.is_deleted',0)->where('dii.status',1)->where('dii.version',$version)->where('dii.history_type',$history_type)
                ->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as category_name','dlim_2.name as type_name',
                'dim.name_id as category_id','dim.quality_id as type_id','dlim_fabric_1.name as fabric_name')
                ->get()->toArray();
            }elseif($type == 'packaging_sheet'){
                $design_data = \DB::table('design_items_instance_history as dii')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',4)->where('dii.is_deleted',0)->where('dii.status',1)->where('dii.version',$version)->where('dii.history_type',$history_type)->select('dii.*','dlim_1.name as packaging_sheet_name')->get()->toArray();
            }elseif($type == 'product_process'){
                $design_data = \DB::table('design_items_instance_history as dii')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->join('design_lookup_items_master as dlim_2','dii.body_part_id', '=', 'dlim_2.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',5)->where('dii.is_deleted',0)->where('dii.status',1)->where('dii.version',$version)->where('dii.history_type',$history_type)->select('dii.*','dlim_1.name as product_process_name','dlim_2.name as body_part_name','dim.name_id as name_id')->get()->toArray();
            }elseif($type == 'specification_sheet'){
                $design_data = \DB::table('design_specification_sheet_history as dss')->join('product_category_master as pm','dss.specification_id', '=', 'pm.id')->where('dss.design_id',$design_id)->where('dss.is_deleted',0)->where('dss.status',1)->where('dss.role_id',$user_type)->where('dss.version',$version)->where('dss.history_type',$history_type)->select('dss.*','pm.name as specification_sheet_name')->get()->toArray();
            }elseif($type == 'embroidery'){
                $design_data = \DB::table('design_items_instance_history as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',6)->where('dii.is_deleted',0)->where('dii.status',1)->where('dii.history_type',$history_type)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as embroidery_type','dim.name_id','dim.unit_id as master_unit_id')->get()->toArray();
            }elseif($type == 'printing'){
                $design_data = \DB::table('design_items_instance_history as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',8)->where('dii.is_deleted',0)->where('dii.status',1)->where('dii.history_type',$history_type)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as printing_type','dim.name_id','dim.unit_id as master_unit_id')->get()->toArray();
            }elseif($type == 'garment_cmt'){
                $design_data = \DB::table('design_items_instance_history as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',7)->where('dii.is_deleted',0)->where('dii.status',1)->where('dii.history_type',$history_type)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as garment_cmt_name','dim.name_id')->get()->toArray();
            }
        }else{
            if($type == 'fabric'){
                $design_data = \DB::table('design_items_instance as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->join('design_lookup_items_master as dlim_2','dim.color_id', '=', 'dlim_2.id')->join('design_lookup_items_master as dlim_3','dim.content_id', '=', 'dlim_3.id')->join('design_lookup_items_master as dlim_4','dim.gsm_id', '=', 'dlim_4.id')->join('design_lookup_items_master as dlim_5','dim.width_id', '=', 'dlim_5.id')->join('design_lookup_items_master as dlim_6','dii.body_part_id', '=', 'dlim_6.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',1)->where('dii.is_deleted',0)->where('dii.status',1)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as fabric_name','dlim_2.name as color_name','dlim_3.name as content_name','dlim_4.name as gsm_name','dlim_5.name as width_name','dlim_6.name as body_part_name','dim.name_id','dim.color_id','dim.content_id','dim.gsm_id','dim.width_id','dim.unit_id as master_unit_id')->get()->toArray();
            }elseif($type == 'accessories'){
                $design_data = \DB::table('design_items_instance as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->join('design_lookup_items_master as dlim_2','dim.quality_id', '=', 'dlim_2.id')->join('design_lookup_items_master as dlim_3','dim.color_id', '=', 'dlim_3.id')->join('design_lookup_items_master as dlim_4','dim.content_id', '=', 'dlim_4.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',2)->where('dii.is_deleted',0)->where('dii.status',1)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as category_name','dlim_2.name as quality_name','dlim_3.name as color_name','dlim_4.name as size_name','dim.name_id as category_id','dim.quality_id','dim.color_id','dim.content_id as size_id')->get()->toArray();
            }elseif($type == 'process'){
                $design_data = \DB::table('design_items_instance as dii')->join('units','units.id', '=', 'dii.unit_id')
                ->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')
                ->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')
                ->leftJoin('design_lookup_items_master as dlim_2','dim.quality_id', '=', 'dlim_2.id')
                ->join('design_items_instance as dii_fabric','dii.fabric_instance_id', '=', 'dii_fabric.id')
                ->join('design_item_master as dim_fabric','dim_fabric.id', '=', 'dii_fabric.design_item_id')        
                ->join('design_lookup_items_master as dlim_fabric_1','dlim_fabric_1.id', '=', 'dim_fabric.name_id')              
                ->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',3)->where('dii.is_deleted',0)->where('dii.status',1)
                ->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as category_name','dlim_2.name as type_name',
                'dim.name_id as category_id','dim.quality_id as type_id','dlim_fabric_1.name as fabric_name')
                ->get()->toArray();
            }elseif($type == 'packaging_sheet'){
                $design_data = \DB::table('design_items_instance as dii')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',4)->where('dii.is_deleted',0)->where('dii.status',1)->select('dii.*','dlim_1.name as packaging_sheet_name')->get()->toArray();
            }elseif($type == 'product_process'){
                $design_data = \DB::table('design_items_instance as dii')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->join('design_lookup_items_master as dlim_2','dii.body_part_id', '=', 'dlim_2.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',5)->where('dii.is_deleted',0)->where('dii.status',1)->select('dii.*','dlim_1.name as product_process_name','dlim_2.name as body_part_name','dim.name_id as name_id')->get()->toArray();
            }elseif($type == 'specification_sheet'){
                $design_data = \DB::table('design_specification_sheet as dss')->join('product_category_master as pm','dss.specification_id', '=', 'pm.id')->where('dss.design_id',$design_id)->where('dss.is_deleted',0)->where('dss.status',1)->where('dss.role_id',$user_type)->select('dss.*','pm.name as specification_sheet_name')->get()->toArray();
            }elseif($type == 'embroidery'){
                $design_data = \DB::table('design_items_instance as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',6)->where('dii.is_deleted',0)->where('dii.status',1)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as embroidery_type','dim.name_id','dim.unit_id as master_unit_id')->get()->toArray();
            }elseif($type == 'printing'){
                $design_data = \DB::table('design_items_instance as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',8)->where('dii.is_deleted',0)->where('dii.status',1)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as printing_type','dim.name_id','dim.unit_id as master_unit_id')->get()->toArray();
            }elseif($type == 'garment_cmt'){
                $design_data = \DB::table('design_items_instance as dii')->join('units','units.id', '=', 'dii.unit_id')->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')->join('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->where('dii.design_id',$design_id)->where('dii.role_id',$user_type)->where('dim.type_id',7)->where('dii.is_deleted',0)->where('dii.status',1)->select('dii.*','units.name as unit_name','units.code as unit_code','dlim_1.name as garment_cmt_name','dim.name_id')->get()->toArray();
            }
        }
        
        return $design_data;
    }
    
    public function designData(Request $request,$id,$version = '',$history_type = ''){
        $user = Auth::user();
        $design_data = Design::where('id',$id)->where('is_deleted',0)->first()->toArray();
        $history_data =  CommonHelper::displayHistoryData($design_data,$version,$history_type); //($version == '' || $design_data['version'] == $version)?false:true;
        
        if(!empty($design_data)){
            $design_images_data = array(); 
            
            $design_accessories_data = $this->getDesignItemData('accessories',$id,$history_data,$history_type,$version);
            $design_fabric_data =  $this->getDesignItemData('fabric',$id,$history_data,$history_type,$version);
            $design_process_data = $this->getDesignItemData('process',$id,$history_data,$history_type,$version);//print_r($design_process_data);exit;
            $design_packaging_sheet_data = $this->getDesignItemData('packaging_sheet',$id,$history_data,$history_type,$version);
            $design_product_process_data = $this->getDesignItemData('product_process',$id,$history_data,$history_type,$version);
            $design_specification_sheet_data = $this->getDesignItemData('specification_sheet',$id,$history_data,$history_type,$version);
            $embroidery_data = $this->getDesignItemData('embroidery',$id,$history_data,$history_type,$version);
            $printing_data = $this->getDesignItemData('printing',$id,$history_data,$history_type,$version);
            $garment_cmt_data = $this->getDesignItemData('garment_cmt',$id,$history_data,$history_type,$version);
            
            $design_review_data = Reviewer_comment::where('design_id',$id)->where('review_type','design')->where('version',$design_data['version'])->first();
            $design_review_data = (!empty($design_review_data))?$design_review_data->toArray():array();
            
            $production_review_data = Reviewer_comment::where('design_id',$id)->where('review_type','production')->where('version',$design_data['production_version'])->first();
            $production_review_data = (!empty($production_review_data))?$production_review_data->toArray():array();
            
            if(isset($design_data['color_id']) && !empty($design_data['color_id'])){
                $color_data = Design_lookup_items_master::where('id',$design_data['color_id'])->where('is_deleted',0)->where('status',1)->first();
                $design_data['color_data'] = $color_data;
            }
            
            $design_array = array('design_data'=>$design_data,'images_data'=>$design_images_data,'accessories_data'=>$design_accessories_data,'fabric_data'=>$design_fabric_data);
            $design_array['process_data'] = $design_process_data;
            $design_array['packaging_sheet_data'] = $design_packaging_sheet_data;
            $design_array['product_process_data'] = $design_product_process_data;
            $design_array['specification_sheet_data'] = $design_specification_sheet_data;
            $design_array['printing_data'] = $printing_data;
            $design_array['embroidery_data'] = $embroidery_data;
            $design_array['garment_cmt_data'] = $garment_cmt_data;
            $design_array['review_data'] = $design_review_data;
            $design_array['production_review_data'] = $production_review_data;
            $design_array['history_data'] = $history_data;
            $design_array['fabric_editable_fields'] = CommonHelper::getEditableFields(1,$user->user_type);
            $design_array['acc_editable_fields'] = CommonHelper::getEditableFields(2,$user->user_type);
            $design_array['fp_editable_fields'] = CommonHelper::getEditableFields(3,$user->user_type);
            $design_array['pp_editable_fields'] = CommonHelper::getEditableFields(5,$user->user_type);
            $design_array['ps_editable_fields'] = CommonHelper::getEditableFields(4,$user->user_type);
            
            $design_array['size_variation_types'] = CommonHelper::getEditSizeVariationTypes($user->user_type);
        }else{
            $design_array = array();
        }
        
        return response()->json(array('designdata'=>$design_array));
    }
    
    public function updateDesignData(Request $request,$id){
        try{
            
            $design_id = $id;
            $design_data = Design::where('id',$design_id)->where('is_deleted',0)->first();

            if(!empty($design_data)){
                if($design_data->is_requisition_created == 1 && $design_data->reviewer_status == 'waiting'){
                    return response()->json(array('status'=>'fail','message'=>'Requisition is in waiting status.'),200);
                }else if($design_data->is_requisition_created == 1 && $design_data->reviewer_status == 'approved'){
                    return response()->json(array('status'=>'fail','message'=>'Requisition is approved.'),200);
                }else{
                    
                    $postData = $request->all();
                    
                    $validateionRules = array('season_id'=>'required','product_id'=>'required','color_id'=>'required','category_id'=>'required','sub_category_id'=>'required','story_id'=>'required','size_id'=>'required','product_name'=>'required|max:250','sale_price'=>'required|numeric');

                    $attributes = array('season_id'=>'Season','product_id'=>'Product','color_id'=>'Color','category_id'=>'Category','sub_category_id'=>'Subcategory','story_id'=>'Story','size_id'=>'Size');

                    $validator = Validator::make($postData,$validateionRules,array(),$attributes);
                    if ($validator->fails()){ 
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                    }
                    
                    $design_data->sku = $postData['sku'];
                    $design_data->season_id = $postData['season_id'];
                    $design_data->story_id = $postData['story_id'];
                    $design_data->design_type_id = $postData['product_id'];
                    
                    $design_data->category_id = $postData['category_id'];
                    $design_data->sub_cat_id = $postData['sub_category_id'];
                    $design_data->color_id = $postData['color_id'];
                    $design_data->size_id = 2;//$postData['size_id'];
                    $design_data->comments = trim($postData['comments']);
                    $design_data->mrp = trim($postData['sale_price']);
                    $design_data->product_name = trim($postData['product_name']);
                    
                    $design_data->is_requisition_created = $postData['create_requisition'];
                    if(isset($postData['create_requisition']) && $postData['create_requisition'] == 1){
                        //$design_data->reviewer_status = 'waiting';
                        $design_data->requisition_created_date = date('Y/m/d H:i:s');
                        $design_data->purchaser_review = 'pending';
                        $design_data->purchaser_review_date = null;
                        $design_data->purchaser_review_comment = null;
                        $design_data->management_review = 'pending';
                        $design_data->management_review_date  = null;
                        $design_data->management_review_comment = null;
                        $design_data->design_review = 'pending';
                        $design_data->designer_submitted = 1;
                    }

                    $design_sizes_ids = $insert_sizes = $delete_sizes = array();
                    
                    $design_sizes = Design_sizes::where('design_id',$design_id)->where('is_deleted',0)->get()->toArray();
                    for($i=0;$i<count($design_sizes);$i++){
                        $design_sizes_ids[] = $design_sizes[$i]['size_id'];
                    }
                    
                    $size_ids = $postData['size_id'];
                    
                    // Add to existing sizes
                    for($i=0;$i<count($size_ids);$i++){
                        if(!in_array($size_ids[$i], $design_sizes_ids)){
                            $size_id = $size_ids[$i];
                            $design_exists = Design_sizes::where('design_id',$design_id)->where('size_id',$size_id)->where('is_deleted',1)->count();
                            
                            if($design_exists == 0){
                                $insertArray = array('design_id'=>$design_id,'size_id'=>$size_id);
                                Design_sizes::create($insertArray);
                            }else{
                                $updateArray = array('is_deleted'=>0);
                                Design_sizes::where('design_id',$design_id)->where('size_id',$size_id)->update($updateArray);
                            }
                        }
                    }
                    
                    // Delete from existing sizes
                    for($i=0;$i<count($design_sizes_ids);$i++){
                        if(!in_array($design_sizes_ids[$i], $size_ids)){
                            $updateArray = array('is_deleted'=>1);
                            Design_sizes::where('design_id',$design_id)->where('size_id',$design_sizes_ids[$i])->where('is_deleted',0)->update($updateArray);
                        }
                    }

                    $design_images = Design_image::where('design_id',$id)->where('is_deleted',0)->where('status',1)->get()->toArray();
                    $design_images_types = array();

                    for($i=0;$i<count($design_images);$i++){
                        $design_images_types[$design_images[$i]['image_type']] = $design_images[$i];
                    }

                    $image_types = array('front','back','side','top');
                    for($i=0;$i<count($image_types);$i++){
                        $image_type = $image_types[$i];           
                        if(array_key_exists('image_name_'.$image_type,$postData) ){    
                            $image_name = $postData['image_name_'.$image_type];
                            if(isset($design_images_types[$image_type])){   // update
                                $row_id = $design_images_types[$image_type]['id'];
                                if(!empty($image_name)){
                                    Design_image::where('id', $row_id)->update(array('image_path'=>$image_name,'image_title'=>$image_name,'image_name'=>$image_name));
                                }else{
                                    Design_image::where('id', $row_id)->update(array('is_deleted'=>1));
                                }
                            }else{  //insert
                                if(!empty($image_name)){
                                    $image_data = array('design_id'=>$id,'image_path'=>$image_name,'image_title'=>$image_name,'image_name'=>$image_name,'image_type'=>$image_type);
                                    $image = Design_image::create($image_data);
                                }
                            }
                        }
                    }
                    
                    $hsn_code = CommonHelper::getFabricPosProductHsnCode($postData['category_id']);
                    $design_data->hsn_code = $hsn_code;

                    $design_data->save();	
                    
                    $design_id = $design_data->id;
                    $cost_price = $margin = 0;
                    $cost_data = array('accessories'=>0,'fabric'=>0,'packaging_sheet'=>0,'embroidery'=>0,'printing'=>0,'garment_cmt'=>0,);
                    
                    $design_accessories_data = $this->getDesignItemData('accessories',$design_id,false,'','');
                    $design_fabric_data =  $this->getDesignItemData('fabric',$design_id,false,'','');
                    $design_packaging_sheet_data = $this->getDesignItemData('packaging_sheet',$design_id,false,'','');
                    $design_embroidery_data = $this->getDesignItemData('embroidery',$design_id,false,'','');
                    $design_printing_data = $this->getDesignItemData('printing',$design_id,false,'','');
                    $design_garment_cmt_data = $this->getDesignItemData('garment_cmt',$design_id,false,'','');
                    
                    for($i=0;$i<count($design_accessories_data);$i++){
                        $cost_price+=$design_accessories_data[$i]->cost;
                        $cost_data['accessories']+=$design_accessories_data[$i]->cost;
                    }
                    
                    for($i=0;$i<count($design_fabric_data);$i++){
                        $cost_price+=$design_fabric_data[$i]->cost;
                        $cost_data['fabric']+=$design_fabric_data[$i]->cost;
                    }
                    
                    for($i=0;$i<count($design_packaging_sheet_data);$i++){
                        if($design_packaging_sheet_data[$i]->avg > 0){
                            $cost_price+=$design_packaging_sheet_data[$i]->cost;
                            $cost_data['packaging_sheet']+=$design_packaging_sheet_data[$i]->cost;
                        }
                    }
                    
                    for($i=0;$i<count($design_embroidery_data);$i++){
                        $cost_price+=$design_embroidery_data[$i]->cost;
                        $cost_data['embroidery']+=$design_embroidery_data[$i]->cost;
                    }
                    
                    for($i=0;$i<count($design_printing_data);$i++){
                        $cost_price+=$design_printing_data[$i]->cost;
                        $cost_data['printing']+=$design_printing_data[$i]->cost;
                    }
                    
                    for($i=0;$i<count($design_garment_cmt_data);$i++){
                        $margin = (strtolower($design_garment_cmt_data[$i]->garment_cmt_name) == 'margin')?$design_garment_cmt_data[$i]->rate:0; 
                        
                        if($design_garment_cmt_data[$i]->rate > 0 && strtolower($design_garment_cmt_data[$i]->garment_cmt_name) != 'margin'){
                            $cost_price+=$design_garment_cmt_data[$i]->cost;
                            $cost_data['garment_cmt']+=$design_garment_cmt_data[$i]->cost;
                        }
                    }
                    
                    $margin_amt = ($margin>0)?round($cost_price*($margin/100),2):0;
                    $cost_price+=$margin_amt;
                    
                    $updateArray = array('net_cost'=>$cost_price,'fabric_cost'=>$cost_data['fabric'],'accessories_cost'=>$cost_data['accessories'],'embroidery_cost'=>$cost_data['embroidery'],
                    'printing_cost'=>$cost_data['printing'],'garment_cmt_cost'=>$cost_data['garment_cmt'],'packaging_sheet_cost'=>$cost_data['packaging_sheet']);
                    
                    Design::where('id',$design_id)->update($updateArray);

                    if(isset($postData['create_requisition']) && $postData['create_requisition'] == 1){
                        $design_data->increment('version');
                        $message = 'Requisition created successfully';
                    }else{
                        $message = 'Design updated successfully';
                    }

                    $design_data = Design::where('id',$id)->where('is_deleted',0)->first();
                    
                    CommonHelper::createLog('Design Updated. ID: '.$id,'DESIGN_UPDATED','DESIGN');

                    return response()->json(array('status'=>'success','message'=>$message,'design_data'=>$design_data),200);
                }
            }else{
                return response()->json(array('status'=>'fail','message'=>'design not found'),200);
            }
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function addDesignFabric(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $user = Auth::user();
            $image_name = null;
            
            $validateionRules = array('body_part'=>'required','width'=>'required|numeric','avg'=>'required|numeric','rate'=>'required|numeric','content_id'=>'required','gsm_id'=>'required',
            'cost'=>'required|numeric','unit_id'=>'required|integer','addFabricImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048','color'=>'required','name'=>'required','master_unit_id'=>'required|integer');
            
            $attributes = array('design_id'=>'design','unit_id'=>'unit','avg'=>'average','addFabricImage'=>'Image','master_unit_id'=>'Width Unit','gsm_id'=>'GSM');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Check and add data in item master table
            $design_item_master_data = Design_item_master::where('type_id',1)->where('name_id',$data['name'])->where('color_id',$data['color'])->where('content_id',$data['content_id'])->where('gsm_id',$data['gsm_id'])->where('width_id',$data['width'])->where('unit_id',$data['master_unit_id'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataFabricMasterArray = array('type_id'=>1,'name_id'=>$data['name'],'color_id'=>$data['color'],'content_id'=>$data['content_id'],'gsm_id'=>$data['gsm_id'],'width_id'=>$data['width'],'unit_id'=>$data['master_unit_id']);
                $dataFabricMasterArray['unique_code'] = $data['name'].'-'.$data['color'].'-'.$data['content_id'].'-'.$data['gsm_id'].'-'.$data['width'].'-'.$data['master_unit_id'];
                $design_item_master_data = Design_item_master::create($dataFabricMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_type_id',1)->where('design_item_id',$design_item_id)->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Fabric already added for this Name, Color, GSM, Content, Width and Unit' ));
            }
            
            if(!empty($request->file('addFabricImage'))){
                $image_name = CommonHelper::uploadImage($request,'addFabricImage','images/design_images/'.$design_id);
            }
            
            $fabricDBArray = array('design_id'=>$design_id,'design_item_id'=>$design_item_id,'design_type_id'=>1,'body_part_id'=>$data['body_part'],'avg'=>$data['avg'],'rate'=>$data['rate'],'cost'=>$data['cost'],'unit_id'=>$data['unit_id'],'image_name'=>$image_name,'comments'=>$data['comments'],'role_id'=>$user->user_type);
            
            $fabric = Design_items_instance::create($fabricDBArray);
            $designFabrics = $this->getDesignItemData('fabric',$design_id); 
            
            CommonHelper::createLog('Design Fabric Item Added. ID: '.$fabric->id,'DESIGN_ITEM_ADDED','DESIGN_ITEM');

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Fabric added successfully','fabric_data' => $designFabrics,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function deleteDesignItem(Request $request,$id){
        try{
            
            $data = $request->all();
            if(!isset($data['deleteChk']) || empty($data['deleteChk'])){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => 'Select rows to delete'));
            }
            
            $ids = $data['deleteChk'];
            
            // Delete images of item instance
            $design_items = Design_items_instance::whereIn('id', $ids)->get()->toArray();
            for($i=0;$i<count($design_items);$i++){
                $image_path = 'images/design_images/'.$design_items[$i]['design_id'].'/'.$design_items[$i]['image_name'];
                if(!empty($design_items[$i]['image_name']) && file_exists(public_path($image_path))){
                    unlink($image_path);
                }
                
                $image_thumb_path = 'images/design_images/'.$design_items[$i]['design_id'].'/thumbs/'.$design_items[$i]['image_name'];
                if(!empty($design_items[$i]['image_name']) && file_exists(public_path($image_thumb_path))){
                    unlink($image_thumb_path);
                }
            }
            
            // Update record to deleted in database
            Design_items_instance::whereIn('id', $ids)->update(array('is_deleted' => 1));
            
            $type = strtolower($data['type']);
            if($type == 'fabric'){
                $designFabrics = $this->getDesignItemData('fabric',$id); 
                Design_items_instance::whereIn('fabric_instance_id', $ids)->where('design_type_id', 3)->update(array('is_deleted' => 1));
                $designProcess = $this->getDesignItemData('process',$id);
                CommonHelper::createLog('Design Item Deleted. IDs: '.implode(',',$ids),'DESIGN_ITEM_DELETED','DESIGN_ITEM');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Fabric deleted successfully','fabric_data' => $designFabrics,'process_data' => $designProcess,'status' => 'success'),200);
            }elseif($type == 'accessories'){
                $designAccessories = $this->getDesignItemData('accessories',$id);
                CommonHelper::createLog('Design Item Deleted. IDs: '.implode(',',$ids),'DESIGN_ITEM_DELETED','DESIGN_ITEM');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Accessories deleted successfully','accessories_data' => $designAccessories,'status' => 'success'),200);
            }elseif($type == 'process'){
                $designProcess = $this->getDesignItemData('process',$id);
                CommonHelper::createLog('Design Item Deleted. IDs: '.implode(',',$ids),'DESIGN_ITEM_DELETED','DESIGN_ITEM');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Process deleted successfully','process_data' => $designProcess,'status' => 'success'),200);
            }elseif($type == 'packaging_sheet'){
                $designPackagingSheet = $this->getDesignItemData('packaging_sheet',$id);
                CommonHelper::createLog('Design Item Deleted. IDs: '.implode(',',$ids),'DESIGN_ITEM_DELETED','DESIGN_ITEM');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Packaging Sheet deleted successfully','packaging_sheet_data' => $designPackagingSheet,'status' => 'success'),200);
            }elseif($type == 'product_process'){
                $designProductionProcess = $this->getDesignItemData('product_process',$id);
                CommonHelper::createLog('Design Item Deleted. IDs: '.implode(',',$ids),'DESIGN_ITEM_DELETED','DESIGN_ITEM');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Production Process deleted successfully','product_process_data' => $designProductionProcess,'status' => 'success'),200);
            }elseif($type == 'embroidery'){
                $designEmbroidery = $this->getDesignItemData('embroidery',$id);
                CommonHelper::createLog('Design Item Deleted. IDs: '.implode(',',$ids),'DESIGN_ITEM_DELETED','DESIGN_ITEM');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Embroidery deleted successfully','embroidery_data' => $designEmbroidery,'status' => 'success'),200);
            }elseif($type == 'printing'){
                $designPrinting = $this->getDesignItemData('printing',$id);
                CommonHelper::createLog('Design Item Deleted. IDs: '.implode(',',$ids),'DESIGN_ITEM_DELETED','DESIGN_ITEM');
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Printing deleted successfully','printing_data' => $designPrinting,'status' => 'success'),200);
            }
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function updateDesignFabric(Request $request,$id){
        try{
           
            $data = $request->all();
            $design_id = $id;
            $image_name = null;
            $user = Auth::user();
            $editableFields = CommonHelper::getEditableFields(1,$user->user_type);

            $validateionRules = array('id'=>'required|integer','body_part'=>'required','width'=>'required|numeric','avg'=>'required|numeric','rate'=>'required|numeric','content_id'=>'required','gsm_id'=>'required',
            'cost'=>'required|numeric','unit_id'=>'required|integer','editFabricImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048','name'=>'required','color'=>'required','master_unit_id'=>'required|integer');
            
            $attributes = array('design_id'=>'design','unit_id'=>'unit','avg'=>'average','editFabricImage'=>'Image','master_unit_id'=>'Width Unit','gsm_id'=>'GSM');
            
            foreach($validateionRules as $field=>$rule){
                if(!in_array($field,$editableFields)){
                    unset($validateionRules[$field]);
                }
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Check and add data in item master table
            $design_item_master_data = Design_item_master::where('type_id',1)->where('name_id',$data['name'])->where('color_id',$data['color'])->where('content_id',$data['content_id'])->where('gsm_id',$data['gsm_id'])->where('width_id',$data['width'])->where('unit_id',$data['master_unit_id'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataFabricMasterArray = array('type_id'=>1,'name_id'=>$data['name'],'color_id'=>$data['color'],'content_id'=>$data['content_id'],'gsm_id'=>$data['gsm_id'],'width_id'=>$data['width'],'unit_id'=>$data['master_unit_id']);
                $dataFabricMasterArray['unique_code'] = $data['name'].'-'.$data['color'].'-'.$data['content_id'].'-'.$data['gsm_id'].'-'.$data['width'].'-'.$data['master_unit_id'];
                $design_item_master_data = Design_item_master::create($dataFabricMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_item_id',$design_item_id)->where('design_type_id',1)->where('role_id',$user->user_type)->where('id','!=',$data['id'])->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Fabric already added for this Name, Color, GSM, Content, Width and Unit' ));
            }
            
            if(!empty($request->file('editFabricImage'))){
                $image_name = CommonHelper::uploadImage($request,'editFabricImage','images/design_images/'.$design_id);
            }
            
            $fabricDBArray = array('body_part_id'=>$data['body_part'],'design_item_id'=>$design_item_id,'avg'=>$data['avg'],'rate'=>$data['rate'],'cost'=>$data['cost'],'unit_id'=>$data['unit_id'],'comments'=>$data['comments']);
            
            if(!empty($image_name)){
                $fabricDBArray['image_name'] = $image_name;
            }
            
            if(isset($data['delete_image']) && $data['delete_image'] == 1){
                $fabricDBArray['image_name'] = null; 
            }
            
            foreach($fabricDBArray as $field=>$value){
                if(!in_array($field,$editableFields)){
                    unset($fabricDBArray[$field]);
                }
            }
            
            Design_items_instance::where('id', '=', $data['id'])->update($fabricDBArray);
            CommonHelper::createLog('Design Item Updated. ID: '.$data['id'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');
            
            $designFabrics = $this->getDesignItemData('fabric',$id); 
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Fabric updated successfully','fabric_data' => $designFabrics,'status' => 'success'),200);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function addDesignAccessories(Request $request,$id){
        try{
            $data = $request->all();
            $design_id = $id;
            $image_name = null;    
            $user = Auth::user();
            $validateionRules = array('category_id'=>'required|integer','color_id'=>'required|integer','rate'=>'required|numeric','qty'=>'required|numeric',
            'cost'=>'required|numeric','unit_id'=>'required|integer','addAccessoriesImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048','subcategory_id'=>'required','size'=>'required');
            $attributes = array('category_id'=>'category','color_id'=>'color','qty'=>'quantity','unit_id'=>'unit','addAccessoriesImage'=>'Image','subcategory_id'=>'sub category');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $design_item_master_data = Design_item_master::where('type_id',2)->where('name_id',$data['category_id'])->where('quality_id',$data['subcategory_id'])->where('color_id',$data['color_id'])->where('content_id',$data['size'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataAccessoryMasterArray = array('type_id'=>2,'quality_id'=>$data['subcategory_id'],'color_id'=>$data['color_id'],'name_id'=>$data['category_id'],'content_id'=>$data['size']);
                $dataAccessoryMasterArray['unique_code'] = $data['category_id'].'-'.$data['subcategory_id'].'-'.$data['color_id'].'-'.$data['size'];
                $design_item_master_data = Design_item_master::create($dataAccessoryMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_type_id',2)->where('design_item_id',$design_item_id)->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Accessories already added for this Category and Color' ));
            }
            
            if(!empty($request->file('addAccessoriesImage'))){
                $image_name = CommonHelper::uploadImage($request,'addAccessoriesImage','images/design_images/'.$design_id);
            }
            
            $accessoriesDBArray = array('design_id'=>$id,'design_item_id'=>$design_item_id,'design_type_id'=>2,'rate'=>$data['rate'],'avg'=>$data['qty'],'cost'=>$data['cost'],'unit_id'=>$data['unit_id'],'image_name'=>$image_name,'comments'=>$data['comments'],'size'=>null,'role_id'=>$user->user_type);
            $accessories = Design_items_instance::create($accessoriesDBArray);
            
            CommonHelper::createLog('Design Item Created. ID: '.$accessories->id,'DESIGN_ITEM_CREATED','DESIGN_ITEM');
            $designAccessories = $this->getDesignItemData('accessories',$id);

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Accessories added successfully','accessories_data' => $designAccessories,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateDesignAccessories(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $image_name = null;
            $user = Auth::user();
            $editableFields = CommonHelper::getEditableFields(2,$user->user_type);
            
            $validateionRules = array('id'=>'required|integer','category_id'=>'required|integer','color_id'=>'required|integer','rate'=>'required|numeric','qty'=>'required|numeric',
            'cost'=>'required|numeric','unit_id'=>'required|integer','editAccessoriesImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048','subcategory_id'=>'required','size'=>'required');
            $attributes = array('design_id'=>'design','category_id'=>'category','color_id'=>'color','qty'=>'quantity','unit_id'=>'unit','editAccessoriesImage'=>'Image','subcategory_id'=>'sub category');
            
            foreach($validateionRules as $field=>$rule){
                if(!in_array($field,$editableFields)){
                    unset($validateionRules[$field]);
                }
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $design_item_master_data = Design_item_master::where('type_id',2)->where('name_id',$data['category_id'])->where('quality_id',$data['subcategory_id'])->where('color_id',$data['color_id'])->where('content_id',$data['size'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataAccessoryMasterArray = array('type_id'=>2,'quality_id'=>$data['subcategory_id'],'color_id'=>$data['color_id'],'name_id'=>$data['category_id'],'content_id'=>$data['size']);
                $dataAccessoryMasterArray['unique_code'] = $data['category_id'].'-'.$data['subcategory_id'].'-'.$data['color_id'].'-'.$data['size'];
                $design_item_master_data = Design_item_master::create($dataAccessoryMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_item_id',$design_item_id)->where('role_id',$user->user_type)->where('design_type_id',2)->where('id','!=',$data['id'])->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Accessories already added for this Category and Color' ));
            }
            
            if(!empty($request->file('editAccessoriesImage'))){
                $image_name = CommonHelper::uploadImage($request,'editAccessoriesImage','images/design_images/'.$design_id);
            }
            
            $accessoriesDBArray = array('design_item_id'=>$design_item_id,'rate'=>$data['rate'],'avg'=>$data['qty'],'cost'=>$data['cost'],'unit_id'=>$data['unit_id'],'comments'=>$data['comments'],'size'=>null);
            
            if(!empty($image_name)){
                $accessoriesDBArray['image_name'] = $image_name;
            }
            
            if(isset($data['delete_image']) && $data['delete_image'] == 1){
                $accessoriesDBArray['image_name'] = null; 
            }
            
            foreach($accessoriesDBArray as $field=>$value){
                if(!in_array($field,$editableFields)){
                    unset($accessoriesDBArray[$field]);
                }
            }
            
            $accessories = Design_items_instance::where('id', '=', $data['id'])->update($accessoriesDBArray);
            
            CommonHelper::createLog('Design Item Updated. ID: '.$data['id'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');
            $designAccessories = $this->getDesignItemData('accessories',$id);

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Accessories updated successfully','accessories_data' => $designAccessories,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function addDesignProcess(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $image_name = null;    
            $user = Auth::user();
            
            $validateionRules = array('category_id'=>'required|integer','cost'=>'required|numeric','addProcessImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048','unit_id'=>'required|integer','fabric_id'=>'required|integer','rate'=>'required|numeric','avg'=>'required|numeric','cost'=>'required|numeric','type_id'=>'required');
            $attributes = array('category_id'=>'category','type_id'=>'type','addProcessImage'=>'Image','unit_id'=>'unit','fabric_id'=>'Fabric','avg'=>'Average');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $design_item_master_data = Design_item_master::where('type_id',3)->where('name_id',$data['category_id'])->where('quality_id',$data['type_id'])->where('color_id',null)->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataProcessMasterArray = array('type_id'=>3,'name_id'=>$data['category_id'],'quality_id'=>$data['type_id'],'color_id'=>null);
                $dataProcessMasterArray['unique_code'] = (!empty($data['type_id']))?$data['category_id'].'-'.$data['type_id']:$data['category_id'];
                $design_item_master_data = Design_item_master::create($dataProcessMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_type_id',3)->where('design_item_id',$design_item_id)->where('fabric_instance_id',$data['fabric_id'])->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Process already added for this Fabric, Category and Type' ));
            }
            
            if(!empty($request->file('addProcessImage'))){
                $image_name = CommonHelper::uploadImage($request,'addProcessImage','images/design_images/'.$design_id);
            }
            
            $processDBArray = array('design_id'=>$id,'design_item_id'=>$design_item_id,'design_type_id'=>3,'rate'=>$data['rate'],'avg'=>$data['avg'],'qty'=>0,'cost'=>$data['cost'],'unit_id'=>$data['unit_id'],'image_name'=>$image_name,'comments'=>$data['comments'],'size'=>0,'fabric_instance_id'=>$data['fabric_id'],'role_id'=>$user->user_type);
            $process = Design_items_instance::create($processDBArray);
            
            CommonHelper::createLog('Design Item Created. ID: '.$process->id,'DESIGN_ITEM_CREATED','DESIGN_ITEM');
            $designProcess = $this->getDesignItemData('process',$id);

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Process added successfully','process_data' => $designProcess,'status' => 'success'),201);
        }catch (\Exception $e){	
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateDesignProcess(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $image_name = null;
            $user = Auth::user();
            $editableFields = CommonHelper::getEditableFields(3,$user->user_type);

            $validateionRules = array('id'=>'required|integer','category_id'=>'required|integer','cost'=>'required|numeric','editProcessImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048',
            'unit_id'=>'required|integer','fabric_id'=>'required|integer','rate'=>'required|numeric','avg'=>'required|numeric','cost'=>'required|numeric','type_id'=>'required');
            $attributes = array('design_id'=>'design','category_id'=>'category','type_id'=>'type','editProcessImage'=>'Image','unit_id'=>'unit','fabric_id'=>'Fabric','avg'=>'Average');
           
            foreach($validateionRules as $field=>$rule){
                if(!in_array($field,$editableFields)){
                    unset($validateionRules[$field]);
                }
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $design_item_master_data = Design_item_master::where('type_id',3)->where('name_id',$data['category_id'])->where('quality_id',$data['type_id'])->where('color_id',null)->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataProcessMasterArray = array('type_id'=>3,'name_id'=>$data['category_id'],'quality_id'=>$data['type_id'],'color_id'=>null);
                $dataProcessMasterArray['unique_code'] = (!empty($data['type_id']))?$data['category_id'].'-'.$data['type_id']:$data['category_id'];
                $design_item_master_data = Design_item_master::create($dataProcessMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_item_id',$design_item_id)->where('role_id',$user->user_type)->where('design_type_id',3)->where('fabric_instance_id',$data['fabric_id'])->where('id','!=',$data['id'])->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Process already added for this Fabric, Category and Type' ));
            }
            
            if(!empty($request->file('editProcessImage'))){
                $image_name = CommonHelper::uploadImage($request,'editProcessImage','images/design_images/'.$design_id);
            }
            
            $processDBArray = array('design_item_id'=>$design_item_id,'rate'=>$data['rate'],'avg'=>$data['avg'],'cost'=>$data['cost'],'unit_id'=>$data['unit_id'],'comments'=>$data['comments'],'fabric_instance_id'=>$data['fabric_id']);
            if(!empty($image_name)){
                $processDBArray['image_name'] = $image_name;
            }
            
            if(isset($data['delete_image']) && $data['delete_image'] == 1){
                $processDBArray['image_name'] = null; 
            }
            
            foreach($processDBArray as $field=>$value){
                if(!in_array($field,$editableFields)){
                    unset($processDBArray[$field]);
                }
            }
            
            $process = Design_items_instance::where('id', '=', $data['id'])->update($processDBArray);
            
            CommonHelper::createLog('Design Item Updated. ID: '.$data['id'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');
            $designProcess = $this->getDesignItemData('process',$id); 

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Process updated successfully','process_data' => $designProcess,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateDesignPackagingSheet(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $image_name = null;
            $user = Auth::user();
            $validateionRules = array('id'=>'required|integer','rate'=>'required|numeric','cost'=>'required|numeric','qty'=>'required|numeric','editPackagingSheetImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048');
            $attributes = array('editPackagingSheetImage'=>'Image','qty'=>'Quantity');
            
            /*$editableFields = CommonHelper::getEditableFields(4,$user->user_type);
            if(in_array('image_name',$editableFields)) $editableFields[] = 'editPackagingSheetImage';
            
            $packaging_sheet_data = Design_items_instance::where('id',$data['id'])->first();
            if(isset($packaging_sheet_data->image_name) && !empty($packaging_sheet_data->image_name)){
                $validateionRules['editPackagingSheetImage'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
            }else{
                $validateionRules['editPackagingSheetImage'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
            }
            
            foreach($validateionRules as $field=>$rule){
                if(!in_array($field,$editableFields)){
                    unset($validateionRules[$field]);
                }
            }*/
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if(!empty($request->file('editPackagingSheetImage'))){
                $image_name = CommonHelper::uploadImage($request,'editPackagingSheetImage','images/design_images/'.$design_id);
            }
            
            $packagingSheetDBArray = array('rate'=>$data['rate'],'cost'=>$data['cost'],'avg'=>$data['qty'],'comments'=>$data['comments']);
            if(!empty($image_name)){
                $packagingSheetDBArray['image_name'] = $image_name;
            }
            
            if(isset($data['delete_image']) && $data['delete_image'] == 1){
                $packagingSheetDBArray['image_name'] = null; 
            }
            
            /*foreach($packagingSheetDBArray as $field=>$value){
                if(!in_array($field,$editableFields)){
                    unset($packagingSheetDBArray[$field]);
                }
            }*/
            
            $packagingSheet = Design_items_instance::where('id', '=', $data['id'])->update($packagingSheetDBArray);
            
            CommonHelper::createLog('Design Item Updated. ID: '.$data['id'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');
            $designPackagingSheet = $this->getDesignItemData('packaging_sheet',$id);

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Packaging Sheet updated successfully','packaging_sheet_data' => $designPackagingSheet,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function addDesignProductProcess(Request $request,$id){
        try{
            $data = $request->all();
            $design_id = $id;
            $image_name = null;
            $user = Auth::user();
            $validateionRules = array('name_id'=>'required','addProductProcessImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048','cost'=>'nullable|numeric','body_part_id'=>'required','cost'=>'required|numeric');
            $attributes = array('addProductProcessImage'=>'Image','name_id'=>'Name','body_part_id'=>'Body Part');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
           if(!empty($request->file('addProductProcessImage'))){
                $image_name = CommonHelper::uploadImage($request,'addProductProcessImage','images/design_images/'.$design_id);
            }
            
            $design_item_master_data = Design_item_master::where('type_id',5)->where('name_id',$data['name_id'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataProcessMasterArray = array('type_id'=>5,'name_id'=>$data['name_id'],'quality_id'=>null,'color_id'=>null,'unique_code'=>$data['name_id']);
                $design_item_master_data = Design_item_master::create($dataProcessMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_type_id',5)->where('design_item_id',$design_item_id)->where('body_part_id',$data['body_part_id'])->where('role_id',$user->user_type)->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Production Process already added for this Body part and Name' ));
            }
            
            $productProcessDBArray = array('design_id'=>$design_id,'role_id'=>$user->user_type,'body_part_id'=>$data['body_part_id'],'design_item_id'=>$design_item_id,'design_type_id'=>5,'cost'=>$data['cost'],'comments'=>$data['comments']);
            
            if(!empty($image_name)){
                $productProcessDBArray['image_name'] = $image_name;
            }
            
            $productProcess = Design_items_instance::create($productProcessDBArray);
            
            CommonHelper::createLog('Design Item Created. ID: '.$productProcess->id,'DESIGN_ITEM_CREATED','DESIGN_ITEM');
            $designProductProcess = $this->getDesignItemData('product_process',$id);

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Product Process added successfully','product_process_data' => $designProductProcess,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateDesignProductProcess(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $user = Auth::user();
            $image_name = null;
            $editableFields = CommonHelper::getEditableFields(5,$user->user_type);
            $validateionRules = array('id'=>'required|integer','name_id'=>'required','editProductProcessImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048','body_part_id'=>'required','cost'=>'required|numeric','name_id'=>'required');
            $attributes = array('editProductProcessImage'=>'Image','name_id'=>'Name','body_part_id'=>'Body Part');
            
            foreach($validateionRules as $field=>$rule){
                if(!in_array($field,$editableFields)){
                    unset($validateionRules[$field]);
                }
            }
            
            $validator = Validator::make($data,$validateionRules);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if(!empty($request->file('editProductProcessImage'))){
                $image_name = CommonHelper::uploadImage($request,'editProductProcessImage','images/design_images/'.$design_id);
            }
            
            $design_item_master_data = Design_item_master::where('type_id',5)->where('name_id',$data['name_id'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataProcessMasterArray = array('type_id'=>5,'name_id'=>$data['name_id'],'quality_id'=>null,'color_id'=>null,'unique_code'=>$data['name_id']);
                $design_item_master_data = Design_item_master::create($dataProcessMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_type_id',5)->where('design_item_id',$design_item_id)->where('body_part_id',$data['body_part_id'])->where('role_id',$user->user_type)->where('id','!=',$data['id'])->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Production Process already added for this Body part and Name' ));
            }
            
            $productProcessDBArray = array('design_item_id'=>$design_item_id,'cost'=>$data['cost'],'comments'=>$data['comments'],'body_part_id'=>$data['body_part_id']);
            
            if(!empty($image_name)){
                $productProcessDBArray['image_name'] = $image_name;
            }
            
            if(isset($data['delete_image']) && $data['delete_image'] == 1){
                $productProcessDBArray['image_name'] = null; 
            }
            
            foreach($productProcessDBArray as $field=>$value){
                if(!in_array($field,$editableFields)){
                    unset($productProcessDBArray[$field]);
                }
            }
            
            $productProcess = Design_items_instance::where('id', '=', $data['id'])->update($productProcessDBArray);
            
            CommonHelper::createLog('Design Item Updated. ID: '.$data['id'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');
            $designProductProcess = $this->getDesignItemData('product_process',$id);

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Product Process updated successfully','product_process_data' => $designProductProcess,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function getDesignSpecificationSheet(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $user = Auth::user();
            $user_type = $user->user_type;
            
            $design_data = Design::where('id',$design_id)->first();
            if(empty($design_data->design_type_id)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Product ID is empty', 'errors' => 'Product ID is empty'));
            }
            
            $sql = 'SELECT specification_id FROM design_specification_sheet as dss WHERE dss.design_id = '.$design_id.' AND dss.role_id = '.$user_type.' AND dss.is_deleted = 0 AND dss.status = 1';
            
            $specification_sheet_items = Product_category_master::where('parent_id','=',$design_data->design_type_id)->whereRaw("id NOT IN(".$sql.")")->where('type_id',2)->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'specification sheet data','specification_sheet' => $specification_sheet_items,'status' => 'success'),200);
            
        }catch (\Exception $e){	
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function addDesignSpecificationSheet(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $user = Auth::user();
            $id_array = explode(',',$data['id_str']);
            
            if(empty($data['id_str'])){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => 'Specification sheet is required field'));
            }
            
            \DB::beginTransaction();
            
            for($i=0;$i<count($id_array);$i++){
                $insertArray = array('specification_id'=>$id_array[$i],'design_id'=>$design_id,'role_id'=>$user->user_type);
                Design_specification_sheet::create($insertArray);
            }
            
            \DB::commit();
            
            $designSpecificationSheet = $this->getDesignItemData('specification_sheet',$id);
            
            CommonHelper::createLog('Design Specification Sheet Created. IDs: '.$data['id_str'],'DESIGN_ITEM_CREATED','DESIGN_ITEM');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Specification sheet added successfully','specification_sheet_data'=>$designSpecificationSheet,'status' => 'success'),200);
            
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function updateDesignSpecificationSheet(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $id_array = explode(',',$data['id_str']);
            
            $size_list = array('s','m','l','xl','xxl','xxxl','allowance');
            for($i=0;$i<count($id_array);$i++){
                $id = $id_array[$i];
                for($q=0;$q<count($size_list);$q++){
                    if(isset($data['spec_sheet_size_'.$size_list[$q].'_'.$id]) && !empty($data['spec_sheet_size_'.$size_list[$q].'_'.$id]) && !is_numeric($data['spec_sheet_size_'.$size_list[$q].'_'.$id])){
                        return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => 'Specification sheet value should be numeric'));
                    }
                }
            }
            
            \DB::beginTransaction();
            
            for($i=0;$i<count($id_array);$i++){
                $id = $id_array[$i];
                $size_s = (isset($data['spec_sheet_size_s_'.$id]) && !empty($data['spec_sheet_size_s_'.$id]))?$data['spec_sheet_size_s_'.$id]:null;
                $size_m = (isset($data['spec_sheet_size_m_'.$id]) && !empty($data['spec_sheet_size_m_'.$id]))?$data['spec_sheet_size_m_'.$id]:null;
                $size_l = (isset($data['spec_sheet_size_l_'.$id]) && !empty($data['spec_sheet_size_l_'.$id]))?$data['spec_sheet_size_l_'.$id]:null;
                $size_xl = (isset($data['spec_sheet_size_xl_'.$id]) && !empty($data['spec_sheet_size_xl_'.$id]))?$data['spec_sheet_size_xl_'.$id]:null;
                $size_xxl = (isset($data['spec_sheet_size_xxl_'.$id]) && !empty($data['spec_sheet_size_xxl_'.$id]))?$data['spec_sheet_size_xxl_'.$id]:null;
                $size_xxxl = (isset($data['spec_sheet_size_xxxl_'.$id]) && !empty($data['spec_sheet_size_xxxl_'.$id]))?$data['spec_sheet_size_xxxl_'.$id]:null;
                $allowance = (isset($data['spec_sheet_allowance_'.$id]) && !empty($data['spec_sheet_allowance_'.$id]))?$data['spec_sheet_allowance_'.$id]:null;
                
                $updateArray = array('size_s'=>$size_s,'size_m'=>$size_m,'size_l'=>$size_l,'size_xl'=>$size_xl,'size_xxl'=>$size_xxl,'size_xxxl'=>$size_xxxl,'allowance'=>$allowance);
                $review = Design_specification_sheet::where('id', '=', $id)->update($updateArray);
            }
            
            \DB::commit();
            
            CommonHelper::createLog('Design Specification Sheet Updated. IDs: '.$data['id_str'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');    
            $designSpecificationSheet = $this->getDesignItemData('specification_sheet',$design_id);
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Specification sheet updated successfully','specification_sheet_data'=>$designSpecificationSheet,'status' => 'success'),200);
            
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function deleteDesignSpecificationSheet(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            if(!isset($data['deleteChk']) || empty($data['deleteChk'])){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => 'Select rows to delete'));
            }
            
            $ids = $data['deleteChk'];
            // Update record to deleted in database
            Design_specification_sheet::whereIn('id', $ids)->update(array('is_deleted' => 1));
            
            $designSpecificationSheet = $this->getDesignItemData('specification_sheet',$design_id);
            
            CommonHelper::createLog('Design Specification Sheet Deleted. IDs: '.implode(',',$ids),'DESIGN_ITEM_DELETED','DESIGN_ITEM');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Specification sheet deleted successfully','specification_sheet_data' => $designSpecificationSheet,'status' => 'success'),200);
            
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function addDesignEmbroidery(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $user = Auth::user();
            $image_name = null;
            
            $validateionRules = array('type'=>'required','rate'=>'required|numeric','cost'=>'required|numeric','unit_id'=>'required|integer','addEmbroideryImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048');
            
            $attributes = array('design_id'=>'design','unit_id'=>'unit','type'=>'Type','addEmbroideryImage'=>'Image');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Check and add data in item master table
            $design_item_master_data = Design_item_master::where('type_id',6)->where('name_id',$data['type'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataEmbroideryMasterArray = array('type_id'=>6,'name_id'=>$data['type']);
                $dataEmbroideryMasterArray['unique_code'] = $data['type'];
                $design_item_master_data = Design_item_master::create($dataEmbroideryMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_type_id',6)->where('design_item_id',$design_item_id)->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Embroidery already added for this Type' ));
            }
            
            if(!empty($request->file('addEmbroideryImage'))){
                $image_name = CommonHelper::uploadImage($request,'addEmbroideryImage','images/design_images/'.$design_id);
            }
            
            $embroideryDBArray = array('design_id'=>$design_id,'design_item_id'=>$design_item_id,'rate'=>$data['rate'],'cost'=>$data['cost'],'design_type_id'=>6,'unit_id'=>$data['unit_id'],'image_name'=>$image_name,'comments'=>$data['comments'],'role_id'=>$user->user_type);
            
            $embroidery = Design_items_instance::create($embroideryDBArray);
            $designEmbroidery = $this->getDesignItemData('embroidery',$design_id); 
            
            CommonHelper::createLog('Design Embroidery Item Added. ID: '.$embroidery->id,'DESIGN_ITEM_ADDED','DESIGN_ITEM');

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Embroidery added successfully','embroidery_data' => $designEmbroidery,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function updateDesignEmbroidery(Request $request,$id){
        try{
           
            $data = $request->all();
            $design_id = $id;
            $image_name = null;
            $user = Auth::user();
            //$editableFields = CommonHelper::getEditableFields(1,$user->user_type);

            $validateionRules = array('type'=>'required','rate'=>'required|numeric','cost'=>'required|numeric','unit_id'=>'required|integer','editEmbroideryImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048');
            
            $attributes = array('design_id'=>'design','unit_id'=>'unit','type'=>'Type','editEmbroideryImage'=>'Image');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Check and add data in item master table
            $design_item_master_data = Design_item_master::where('type_id',6)->where('name_id',$data['type'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataEmbroideryMasterArray = array('type_id'=>6,'name_id'=>$data['type']);
                $dataEmbroideryMasterArray['unique_code'] = $data['type'];
                $design_item_master_data = Design_item_master::create($dataEmbroideryMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_item_id',$design_item_id)->where('design_type_id',6)->where('role_id',$user->user_type)->where('id','!=',$data['id'])->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Embroidery already added for this Type' ));
            }
            
            if(!empty($request->file('editEmbroideryImage'))){
                $image_name = CommonHelper::uploadImage($request,'editEmbroideryImage','images/design_images/'.$design_id);
            }
            
            $embroideryDBArray = array('design_item_id'=>$design_item_id,'rate'=>$data['rate'],'cost'=>$data['cost'],'unit_id'=>$data['unit_id'],'comments'=>$data['comments']);
            
            if(!empty($image_name)){
                $embroideryDBArray['image_name'] = $image_name;
            }
            
            if(isset($data['delete_image']) && $data['delete_image'] == 1){
                $embroideryDBArray['image_name'] = null; 
            }
            
            Design_items_instance::where('id', '=', $data['id'])->update($embroideryDBArray);
            CommonHelper::createLog('Embroidery Item Updated. ID: '.$data['id'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');
            
           $designEmbroidery = $this->getDesignItemData('embroidery',$design_id); 
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Embroidery updated successfully','embroidery_data' => $designEmbroidery,'status' => 'success'),200);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    
    
    public function addDesignPrinting(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $user = Auth::user();
            $image_name = null;
            
            $validateionRules = array('type'=>'required','rate'=>'required|numeric','cost'=>'required|numeric','unit_id'=>'required|integer','addPrintingImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048');
            
            $attributes = array('design_id'=>'design','unit_id'=>'unit','type'=>'Type','addPrintingImage'=>'Image');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Check and add data in item master table
            $design_item_master_data = Design_item_master::where('type_id',8)->where('name_id',$data['type'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataPrintingMasterArray = array('type_id'=>8,'name_id'=>$data['type']);
                $dataPrintingMasterArray['unique_code'] = $data['type'];
                $design_item_master_data = Design_item_master::create($dataPrintingMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_type_id',8)->where('design_item_id',$design_item_id)->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Printing already added for this Type' ));
            }
            
            if(!empty($request->file('addPrintingImage'))){
                $image_name = CommonHelper::uploadImage($request,'addPrintingImage','images/design_images/'.$design_id);
            }
            
            $printingDBArray = array('design_id'=>$design_id,'design_item_id'=>$design_item_id,'rate'=>$data['rate'],'cost'=>$data['cost'],'design_type_id'=>8,'unit_id'=>$data['unit_id'],'image_name'=>$image_name,'comments'=>$data['comments'],'role_id'=>$user->user_type);
            
            $printing = Design_items_instance::create($printingDBArray);
            $designPrinting = $this->getDesignItemData('printing',$design_id); 
            
            CommonHelper::createLog('Design Printing Item Added. ID: '.$printing->id,'DESIGN_ITEM_ADDED','DESIGN_ITEM');

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Printing added successfully','printing_data' => $designPrinting,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function updateDesignPrinting(Request $request,$id){
        try{
           
            $data = $request->all();
            $design_id = $id;
            $image_name = null;
            $user = Auth::user();
            //$editableFields = CommonHelper::getEditableFields(1,$user->user_type);

            $validateionRules = array('type'=>'required','rate'=>'required|numeric','cost'=>'required|numeric','unit_id'=>'required|integer','editPrintingImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048');
            
            $attributes = array('design_id'=>'design','unit_id'=>'unit','type'=>'Type','editPrintingImage'=>'Image');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Check and add data in item master table
            $design_item_master_data = Design_item_master::where('type_id',8)->where('name_id',$data['type'])->first();
            if(!empty($design_item_master_data)){
                $design_item_id = $design_item_master_data->id;
            }else{
                $dataPrintingMasterArray = array('type_id'=>8,'name_id'=>$data['type']);
                $dataPrintingMasterArray['unique_code'] = $data['type'];
                $design_item_master_data = Design_item_master::create($dataPrintingMasterArray);
                $design_item_id = $design_item_master_data->id;
            }
            
            $design_items_exists = Design_items_instance::where('design_id',$design_id)->where('design_item_id',$design_item_id)->where('design_type_id',8)->where('role_id',$user->user_type)->where('id','!=',$data['id'])->where('is_deleted',0)->first();
            if(!empty($design_items_exists)){
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' =>'Printing already added for this Type' ));
            }
            
            if(!empty($request->file('editPrintingImage'))){
                $image_name = CommonHelper::uploadImage($request,'editPrintingImage','images/design_images/'.$design_id);
            }
            
            $printingDBArray = array('design_item_id'=>$design_item_id,'rate'=>$data['rate'],'cost'=>$data['cost'],'unit_id'=>$data['unit_id'],'comments'=>$data['comments']);
            
            if(!empty($image_name)){
                $printingDBArray['image_name'] = $image_name;
            }
            
            if(isset($data['delete_image']) && $data['delete_image'] == 1){
                $printingDBArray['image_name'] = null; 
            }
            
            Design_items_instance::where('id', '=', $data['id'])->update($printingDBArray);
            CommonHelper::createLog('Printing Item Updated. ID: '.$data['id'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');
            
           $designPrinting = $this->getDesignItemData('printing',$design_id); 
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design Printing updated successfully','printing_data' => $designPrinting,'status' => 'success'),200);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateDesignGarmentCmt(Request $request,$id){
        try{
            
            $data = $request->all();
            $design_id = $id;
            $image_name = null;
            $user = Auth::user();
            $validateionRules = array('id'=>'required|integer','rate'=>'required|numeric','cost'=>'required|numeric','editGarmentCmtImage'=>'image|mimes:jpeg,png,jpg,gif|max:2048');
            $attributes = array('editGarmentCmtImage'=>'Image','qty'=>'Quantity');
            /*$editableFields = CommonHelper::getEditableFields(4,$user->user_type);
            if(in_array('image_name',$editableFields)) $editableFields[] = 'editPackagingSheetImage';
            
            $packaging_sheet_data = Design_items_instance::where('id',$data['id'])->first();
            if(isset($packaging_sheet_data->image_name) && !empty($packaging_sheet_data->image_name)){
                $validateionRules['editPackagingSheetImage'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
            }else{
                $validateionRules['editPackagingSheetImage'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
            }
            
            foreach($validateionRules as $field=>$rule){
                if(!in_array($field,$editableFields)){
                    unset($validateionRules[$field]);
                }
            }*/
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            if(!empty($request->file('editGarmentCmtImage'))){
                $image_name = CommonHelper::uploadImage($request,'editGarmentCmtImage','images/design_images/'.$design_id);
            }
            
            $garmentCmtDBArray = array('rate'=>$data['rate'],'cost'=>$data['cost'],'comments'=>$data['comments']);
            if(!empty($image_name)){
                $garmentCmtDBArray['image_name'] = $image_name;
            }
            
            if(isset($data['delete_image']) && $data['delete_image'] == 1){
                $garmentCmtDBArray['image_name'] = null; 
            }
            
            /*foreach($packagingSheetDBArray as $field=>$value){
                if(!in_array($field,$editableFields)){
                    unset($packagingSheetDBArray[$field]);
                }
            }*/
            
            $garmentCmt = Design_items_instance::where('id', '=', $data['id'])->update($garmentCmtDBArray);
            
            CommonHelper::createLog('Design Item Updated. ID: '.$data['id'],'DESIGN_ITEM_UPDATED','DESIGN_ITEM');
            $designGarmentCmt = $this->getDesignItemData('garment_cmt',$id);

            return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Garment Cmt updated successfully','garment_cmt_data' => $designGarmentCmt,'status' => 'success'),201);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function getProductData(Request $request,$id){
        try{
            $data = $request->all();
            $elements_list = Product_category_master::where('parent_id',$id)->where('type_id',$data['type_id'])->where('is_deleted',0)->where('status',1)->get()->toArray();

            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Elements list','elements_list' => $elements_list,'status' => 'success'),200);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function getProcessTypes(Request $request,$id){
        try{
            $process_types = Design_lookup_items_master::whereraw("lower(type) = 'process_type'")->where('pid','=',$id)->where('is_deleted',0)->where('status',1)->get()->toArray();

            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Process Types','process_types' => $process_types,'status' => 'success'),200);
        }catch (\Exception $e){	
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function getAccessoriesSubcategories(Request $request,$id){
        try{
            $subcategory_list = Design_lookup_items_master::whereraw("lower(type) = 'accessory_subcategory'")->where('pid','=',$id)->where('is_deleted',0)->where('status',1)->get()->toArray();

            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Accessories Subcategories','sub_category_list' => $subcategory_list,'status' => 'success'),200);
        }catch (\Exception $e){	
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function getAccessoriesSize(Request $request,$id){
        try{
            $size_list = Design_lookup_items_master::whereraw("lower(type) = 'accessory_size'")->where('pid','=',$id)->where('is_deleted',0)->where('status',1)->get()->toArray();

            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Accessories Size','size_list' => $size_list,'status' => 'success'),200);
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function reviewDesign(Request $request,$id){
        try{
            
            $data = $request->all();
            $user_id = Auth::id();    
            
            $design_data = Design::where('id',$id)->where('is_deleted',0)->first();
            $attributes = $validateionRules = array();
            
            if(strtolower($design_data->reviewer_status) == 'waiting'){
                $validateionRules['design_status_sel'] = 'required';
                $attributes = array('design_status_sel'=>'Review Status');
                //if(strtolower($data['design_status_sel']) == 'rejected'){
                    $validateionRules['comment'] = 'required|min:20';
                //}
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            \DB::beginTransaction();
            
            if(strtolower($design_data->reviewer_status) == 'waiting'){
                $reviewDBArray = array('design_id'=>$id,'reviewer_id'=>$user_id,'comment'=>$data['comment'],'design_status'=>$data['design_status_sel'],'version'=>$data['version'],'review_type'=>'design');
                $review = Reviewer_comment::create($reviewDBArray);
                
                $designDBArray = array('reviewer_status'=>$data['design_status_sel'],'reviewer_id'=>$user_id);
                $design = Design::where('id', '=', $id)->update($designDBArray);
                
                // Save design history
                if(strtolower($data['design_status_sel']) == 'rejected'){
                    $this->saveDesignHistory($id);
                }
                
                /*if(strtolower($data['design_status_sel']) == 'approved'){
                    $design_sizes = \DB::table('design_sizes as ds')
                    ->Join('designs as d','d.id','=','ds.design_id')        
                    ->where('d.id',$id)->where('d.is_deleted',0)->where('d.status',1)   
                    ->where('ds.is_deleted',0)        
                    ->select('d.*','ds.size_id')
                    ->get()->toArray();
                    
                    for($i=0;$i<count($design_sizes);$i++){
                        $design_info = $design_sizes[$i];
                        
                        $insertArray = array('product_name'=>$design_info->product_name,'custom_product'=>1,
                        'story_id'=>$design_info->story_id,'season_id'=>$design_info->season_id,'product_description'=>$design_info->description,'product_type'=>'Finished',
                        'category_id'=>$design_info->category_id,'subcategory_id'=>$design_info->sub_cat_id,'size_id'=>$design_info->size_id,'color_id'=>$design_info->color_id,
                        'base_price'=>$design_info->net_cost,'hsn_code'=>$design_info->hsn_code,'product_sku'=>$design_info->sku,'gst_inclusive'=>null,
                        'vendor_product_sku'=>null,'sale_price'=>$design_info->mrp);
                        
                        $product = Pos_product_master::create($insertArray);
                    }
                }*/
                
                $msg = 'Review added successfully';
            }else{
                $reviewDBArray = array('comment'=>$data['comment']);
                $review = Reviewer_comment::where('id', '=', $data['review_id'])->update($reviewDBArray);
                $msg = 'Review updated successfully';
            }
            
            Notification::where('reference_id',$design_data->id)->where('to_user_id',$user_id)->update(array('is_deleted'=>1));
            
            $design_data = Design::where('id',$id)->where('is_deleted',0)->first()->toArray();
            $review_data = Reviewer_comment::where('design_id',$id)->where('review_type','design')->where('version',$data['version'])->first();
            $review_data = (!empty($review_data))?$review_data->toArray():array();
                    
            \DB::commit();
            
            CommonHelper::createLog('Design Review Added/Updated. Design ID: '.$design_data['id'],'DESIGN_REVIEW_ADDED_UPDATED','DESIGN_REVIEW');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => $msg,'status' => 'success','design_data'=>$design_data,'review_data'=>$review_data),201);
        }catch (\Exception $e){		
            \DB::rollBack();
            CommonHelper::saveException($e,'DESIGN_REVIEW',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function saveDesignHistory($design_id){
        
        $design_data = Design::where('id',$design_id)->first()->toArray();
        $version = $design_data['version'];
        unset($design_data['id']);
        $design_data['design_id'] = $design_id;
        $design_data['history_type'] = 'design';
        $design_data['created_at'] = date('Y/m/d H:i:s',strtotime($design_data['created_at']));
        $design_data['updated_at'] = date('Y/m/d H:i:s',strtotime($design_data['updated_at']));

        \DB::table('designs_history')->insert($design_data);

        $design_images = Design_image::where('design_id',$design_id)->get()->toArray();
        for($i=0;$i<count($design_images);$i++){
            unset($design_images[$i]['id']);
            $design_images[$i]['version'] = $version;
            $design_images[$i]['history_type'] = 'design';
            $design_images[$i]['created_at'] = date('Y/m/d H:i:s',strtotime($design_images[$i]['created_at']));
            $design_images[$i]['updated_at'] = date('Y/m/d H:i:s',strtotime($design_images[$i]['updated_at']));
            \DB::table('design_images_history')->insert($design_images[$i]);
        }

        $design_items_instance = Design_items_instance::where('design_id',$design_id)->get()->toArray();
        for($i=0;$i<count($design_items_instance);$i++){
            unset($design_items_instance[$i]['id']);
            $design_items_instance[$i]['version'] = $version;
            $design_items_instance[$i]['history_type'] = 'design';
            $design_items_instance[$i]['created_at'] = date('Y/m/d H:i:s',strtotime($design_items_instance[$i]['created_at']));
            $design_items_instance[$i]['updated_at'] = date('Y/m/d H:i:s',strtotime($design_items_instance[$i]['updated_at']));
            \DB::table('design_items_instance_history')->insert($design_items_instance[$i]);
        }

        $design_specification_sheet = Design_specification_sheet::where('design_id',$design_id)->get()->toArray();
        for($i=0;$i<count($design_specification_sheet);$i++){
            unset($design_specification_sheet[$i]['id']);
            $design_specification_sheet[$i]['version'] = $version;
            $design_specification_sheet[$i]['history_type'] = 'design';
            $design_specification_sheet[$i]['created_at'] = date('Y/m/d H:i:s',strtotime($design_specification_sheet[$i]['created_at']));
            $design_specification_sheet[$i]['updated_at'] = date('Y/m/d H:i:s',strtotime($design_specification_sheet[$i]['updated_at']));
            \DB::table('design_specification_sheet_history')->insert($design_specification_sheet[$i]);
        }
        
        CommonHelper::createLog('Design History Saved. Design ID: '.$design_id,'DESIGN_HISTORY_SAVED','DESIGN_HISTORY');
    }
    
    function getDesignReviewsList(Request $request, $id,$type){
        try{
            $design_reviews = Reviewer_comment::where('design_id',$id)->where('review_type',$type)->orderBy('id','DESC')->get();
            $design_reviews = (!empty($design_reviews))?$design_reviews->toArray():array();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => ucfirst($type).' Reviews','status' => 'success','design_reviews'=>$design_reviews),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function getSizeVariations(Request $request,$id){
        try{
            $data = $request->all();
            $design_data = \DB::table('design_items_instance as dii')->Join('designs as d','dii.design_id','=','d.id')->where('dii.id',$id) ->select('d.size_id')->first();
            $design_size_id = $design_data->size_id;

            $size_list = \DB::table('production_size_counts as psc')
            ->leftJoin('design_size_variations as dsv',function($join) use($id){$join->on('psc.id','=','dsv.size_id')->where('dsv.design_items_instance_id','=',$id)->where('dsv.is_deleted','=','0')->where('dsv.status','=','1');})        
            ->where('psc.status',1)->where('psc.is_deleted',0)->where('psc.id','!=',$design_size_id)
            ->select('dsv.*','psc.id as dlm_id','psc.size as size_id_name')->orderBy('psc.id','ASC')->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Size list','size_list' => $size_list,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function updateSizeVariationData(Request $request,$id){
        try{
            $data = $request->all();
            $size_ids_arr = explode(',',trim($data['size_var_size_ids']));
            $instance_id = trim($data['size_var_inst_id']);
            
            $size_list = $validateionRules = $attributes = array();
            $size_data = \DB::table('production_size_counts')->whereIn('id',$size_ids_arr)->get()->toArray();;
            for($i=0;$i<count($size_data);$i++){
                $size_list[$size_data[$i]->id] = $size_data[$i]->size;
            }
            
            for($i=0;$i<count($size_ids_arr);$i++){
                $size_id = $size_ids_arr[$i];
                $variation_type = trim($data['variation_type_'.$size_id]);
                $variation_value = trim($data['variation_value_'.$size_id]);
                
                $validateionRules['variation_value_'.$size_id] = Rule::requiredIf(!empty($variation_type));
                $attributes['variation_value_'.$size_id] = $size_list[$size_id].' Variation value';
                
                $validateionRules['variation_type_'.$size_id] = Rule::requiredIf(!empty($variation_value));
                $attributes['variation_type_'.$size_id] = $size_list[$size_id].' Variation type';
            }
           
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            
            for($i=0;$i<count($size_ids_arr);$i++){
                $size_id = $size_ids_arr[$i];
                $validator->sometimes('variation_value_'.$size_id, 'max:100|integer', function ($data) use($size_id) {
                    return $data['variation_type_'.$size_id] == 'percent';
                });
            }
            
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            \DB::beginTransaction();
            
            for($i=0;$i<count($size_ids_arr);$i++){
                $size_id = $size_ids_arr[$i];
                $variation_type = trim($data['variation_type_'.$size_id]);
                $variation_value = trim($data['variation_value_'.$size_id]);
                $instance_data = Design_size_variations::where('design_items_instance_id',$instance_id)->where('size_id',$size_id)->first();
                
                if(empty($instance_data)){
                    if(!empty($variation_type) && !empty($variation_value)){
                        $insertArray = array('design_items_instance_id'=>$instance_id,'size_id'=>$size_id,'variation_type'=>$variation_type,'variation_value'=>$variation_value);
                        Design_size_variations::create($insertArray);
                    }
                }else{
                    if(!empty($variation_type) && !empty($variation_value)){
                        $updateArray = array('variation_type'=>$variation_type,'variation_value'=>$variation_value);
                    }else{
                        $updateArray = array('variation_type'=>null,'variation_value'=>null);
                    }
                    Design_size_variations::where('design_items_instance_id',$instance_id)->where('size_id',$size_id)->update($updateArray);
                }
            }
            
            \DB::commit();
            
            CommonHelper::createLog('Design Variation Data Updated. Instance ID: '.$instance_id,'DESIGN_SIZE_VARIATION_UPDATED','DESIGN_SIZE_VARIATION');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Size variation updated','size_list' => '','status' => 'success'),200);
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function getDesignLookupItems(Request $request,$id){
        try{
            $data = $request->all();
            $design_id = $id;
            $type_id = (isset($data['type_id']) && !empty($data['type_id']))?$data['type_id']:'';
            
            $design_lookup_items = \DB::table('design_items_instance as dii')
            ->join('design_item_master as dim','dim.id', '=', 'dii.design_item_id')
            ->leftJoin('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')->leftJoin('design_lookup_items_master as dlim_2','dim.color_id', '=', 'dlim_2.id')
            ->leftJoin('design_lookup_items_master as dlim_3','dim.content_id', '=', 'dlim_3.id')->leftJoin('design_lookup_items_master as dlim_4','dim.gsm_id', '=', 'dlim_4.id')
            ->leftJoin('design_lookup_items_master as dlim_5','dim.width_id', '=', 'dlim_5.id')->leftJoin('design_lookup_items_master as dlim_6','dim.quality_id', '=', 'dlim_6.id')
            ->where('dii.design_id',$design_id)->where('dii.is_deleted',0)->where('dii.status',1);
            
            if(!empty($type_id)){
                $design_lookup_items = $design_lookup_items->where('dim.type_id',$type_id);
            }
                    
            $design_lookup_items = $design_lookup_items->select('dii.*','dlim_1.name as item_name','dlim_2.name as color_name','dlim_3.name as content_name',
            'dlim_4.name as gsm_name','dlim_5.name as width_name','dlim_6.name as quality_name','dim.name_id','dim.color_id','dim.content_id','dim.gsm_id','dim.width_id','dim.quality_id')
            ->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'design lookup items','lookup_items'=>$design_lookup_items,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    private function searchDBArray($array,$key,$value){
        $value_exists = false;
        
        if(isset($array[0]) && !is_array($array[0])){
            $str = json_encode($array);
            $array = json_decode($str,true);
        }
        
        for($i=0;$i<count($array);$i++){
            if($array[$i][$key] == $value){
                $value_exists = true;
                break;
            }
        }
        
        return $value_exists;
    }
    
    function getDesignTotalCost(Request $request,$id,$version = '',$history_type = ''){
        try{
            $user = Auth::user();
            $data = $request->all();
            $design_id = $id;
            
            $design_info = Design::where('id',$id)->first();
            $user_type = CommonHelper::getDataRole($user->user_type);
            $display_history_data = CommonHelper::displayHistoryData($design_info,$version,$history_type);
            
            $fabric_cost = $accessories_cost = $process_cost = $prod_process_cost = 0;
            if(!$display_history_data){
                $instance_data = Design_items_instance::where('design_id',$design_id)->where('role_id',$user_type)->where('design_type_id','!=',4)->where('is_deleted',0)->where('status',1)->groupBy('design_type_id')->selectRaw('design_type_id,sum(cost) as total_cost')->get()->toArray();
            }else{
                $historyTypeId = ($history_type == '' )?1:$history_type;
                $history_type_name = CommonHelper::getHistoryType($historyTypeId);
                $instance_data = Design_items_instance_history::where('design_id',$design_id)->where('role_id',$user_type)->where('history_type',$history_type_name)->where('design_type_id','!=',4)->where('is_deleted',0)->where('status',1)->groupBy('design_type_id')->selectRaw('design_type_id,sum(cost) as total_cost')->get()->toArray();
            }
            
            for($i=0;$i<count($instance_data);$i++){
                if($instance_data[$i]['design_type_id'] == 1)
                    $fabric_cost = round($instance_data[$i]['total_cost'],2);
                elseif($instance_data[$i]['design_type_id'] == 2)
                    $accessories_cost = round($instance_data[$i]['total_cost'],2);
                elseif($instance_data[$i]['design_type_id'] == 3)
                    $process_cost = round($instance_data[$i]['total_cost'],2);
                elseif($instance_data[$i]['design_type_id'] == 5)
                    $prod_process_cost = round($instance_data[$i]['total_cost'],2);
            }
            
            //$gst_data = \DB::table('app_settings')->where('setting_key','gst')->first();
            //$gst_percent = (isset($gst_data->setting_value))?str_replace('%','',$gst_data->setting_value):0;
            
            $total_cost = $fabric_cost+$accessories_cost+$process_cost+$prod_process_cost;
            $gst_percent = CommonHelper::getGSTPercent($total_cost);
            $gst_amount = round($total_cost*($gst_percent/100),2);
            $net_cost = $total_cost+$gst_amount;
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Total Cost','fabric_cost'=>$fabric_cost,'accessories_cost'=>$accessories_cost,
            'process_cost'=>$process_cost,'prod_process_cost'=>$prod_process_cost,'total_cost'=>$total_cost,'net_cost'=>$net_cost,'gst_percent'=>$gst_percent,'gst_amount'=>$gst_amount,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function designList(Request $request){
        try{ 
            $data = $request->all();
            $user = Auth::user();
            $whereRawArray = $category_list = $color_list = $season_list = array();
            
            if(isset($data['action']) && $data['action'] == 'get_prod_hsn_code'){
                $hsn_code = CommonHelper::getFabricPosProductHsnCode($data['category_id']);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'hsn code','hsn_code'=>$hsn_code,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'get_design_data'){
                $design_data = Design::where('id',$data['id'])->where('is_deleted',0)->first();
                $color_data = Design_lookup_items_master::where('id',$design_data->color_id)->where('is_deleted',0)->first();
                $design_data->color_data = $color_data;
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'design data','design_data'=>$design_data,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'update_design_data'){
                $validateionRules = array('product_name_edit'=>'required','product_sale_price_edit'=>'required|numeric',
                'product_category_edit'=>'required','product_subcategory_edit'=>'required','color_id'=>'required','story_id_edit'=>'required',
                'product_base_price_edit'=>'required|numeric');

                $attributes = array('product_name_edit'=>'Product Name','product_barcode_edit'=>'Barcode','product_sku_edit'=>'SKU','story_id_edit'=>'Story','product_base_price_edit'=>'Base Price',
                'season_id_edit'=>'Season','product_category_edit'=>'Category','product_subcategory_edit'=>'Subcategory','size_id_edit'=>'Size','color_id'=>'Color',
                'product_sale_price_edit'=>'Sale Price','product_hsn_code_edit'=>'HSN Code');
                
                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $updateArray = array('product_name'=>$data['product_name_edit'],'mrp'=>$data['product_sale_price_edit'],
                'story_id'=>$data['story_id_edit'],'season_id'=>$data['season_id_edit'],'description'=>$data['product_description_edit'],
                'category_id'=>$data['product_category_edit'],'sub_cat_id'=>$data['product_subcategory_edit'],'color_id'=>$data['color_id']);

                $design_id = $data['design_edit_id'];
                Design::where('id', '=', $design_id)->update($updateArray);
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design updated successfully','status' => 'success'),200);
            }
            
            $whereArray = array('design_review'=>'approved','d.status'=>1,'d.is_deleted'=>0);
            
            $designs = \DB::table('designs as d')
            ->leftJoin('design_lookup_items_master as s','d.season_id','=','s.id')
            ->leftJoin('design_lookup_items_master as c','c.id','=','d.color_id')
            ->leftJoin('design_lookup_items_master as d1','d1.id','=','d.category_id')        
            ->leftJoin('design_lookup_items_master as d2','d2.id','=','d.sub_cat_id')                
            ->leftJoin('product_category_master as dt','d.design_type_id','=','dt.id')
            ->leftJoin('users as u1','u1.id','=','d.user_id')        
            ->leftJoin('story_master as sm','sm.id','=','d.story_id');
            
            $designs = $designs->where($whereArray);
            if(isset($data['id']) && !empty($data['id'])){
                $designs = $designs->where('d.id',trim($data['id']));
            }
            
            $designs = $designs->select('d.*','s.name as season_name','sm.name as story_name','dt.name as design_type_name','d1.name as category_name','d2.name as subcategory_name',
            'c.name as color_name','u1.name as designer_name','sm.name as story_name');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $designs = $designs->offset($start)->limit($limit)->orderBy('d.id','ASC')->get()->toArray();
            }else{
                $designs = $designs->orderBy('d.id','DESC')->paginate(100);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=design_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Design ID','Style','Category','Story','Product','Designer','Designer Review','Management Review','Date Added');

                $callback = function() use ($designs,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($designs);$i++){
                        $array = array($designs[$i]->id,$designs[$i]->sku,$designs[$i]->category_name,$designs[$i]->story_name,$designs[$i]->design_type_name,$designs[$i]->designer_name,ucfirst($designs[$i]->purchaser_review),ucfirst($designs[$i]->management_review),date('d-m-Y',strtotime($designs[$i]->created_at)));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','COLOR','SEASON'))->where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            $po_list = Purchase_order::where('type_id',5)->get()->toArray();
            
            for($i=0;$i<count($design_lookup_items);$i++){
                if(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_CATEGORY'){
                    $category_list[] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'COLOR'){
                    $color_list[] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'SEASON'){
                    $season_list[] = $design_lookup_items[$i];
                }
            }
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $story_list = Story_master::where('is_deleted',0)->where('status',1)->get()->toArray();

            return view('designer/design_list',array('designs_list'=>$designs,'error_message'=>'','user'=>$user,'category_list'=>$category_list,'color_list'=>$color_list,'season_list'=>$season_list,
            'story_list'=>$story_list,'po_list'=>$po_list,'size_list'=>$size_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'DESIGN',__FUNCTION__,__FILE__);
            if(isset($data['action']) && !empty($data['action'])){
                \DB::rollBack();
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('designer/design_list',array('error_message'=>$e->getMessage(),'designs_list'=>array()));
            }
        }
    }
    
}
