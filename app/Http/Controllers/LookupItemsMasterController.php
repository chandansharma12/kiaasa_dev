<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Design_lookup_items_master;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class LookupItemsMasterController extends Controller
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
            $rec_per_page = 100;
            $items_list = \DB::table('design_lookup_items_master as c1')->leftJoin('design_lookup_items_master as c2','c1.pid','=','c2.id')->where('c1.is_deleted',0);
            
            if(isset($data['type']) && !empty($data['type'])){
                $type = trim($data['type']);
                $items_list = $items_list->where('c1.type','=',$type);
            }
            
            if(isset($data['name']) && !empty($data['name'])){
                $name = trim($data['name']);
                $items_list = $items_list->whereRaw("(c1.name like '%{$name}%')");
            }
            if(isset($data['id']) && !empty($data['id'])){
                $items_list = $items_list->where('c1.id',trim($data['id']));
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'c1.id','name'=>'c1.name','description'=>'c1.description','type'=>'c1.type','parent'=>'c2.name','status'=>'c1.status','created'=>'c1.created_at','updated'=>'c1.updated_at');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'c1.id';
                $items_list = $items_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }
            
            $items_list = $items_list->select('c1.*','c2.name as parent_item_name');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $paging_data = CommonHelper::getDownloadPagingData($data['report_rec_count']);
                $start = $paging_data['start'];
                $limit = $paging_data['limit'];
                $items_list = $items_list->offset($start)->limit($limit)->get()->toArray();
            }else{
                $items_list = $items_list->paginate($rec_per_page);
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=lookup_items_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Item ID','Name','Description ','Parent','Type','Created On','Updated On');

                $callback = function() use ($items_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    for($i=0;$i<count($items_list);$i++){
                        $created_at = (!empty($items_list[$i]->created_at))?date('d-m-Y',strtotime($items_list[$i]->created_at)):'';
                        $updated_at = (!empty($items_list[$i]->updated_at))?date('d-m-Y',strtotime($items_list[$i]->updated_at)):'';
                        $array = array($items_list[$i]->id,CommonHelper::filterCsvData($items_list[$i]->name),CommonHelper::filterCsvData($items_list[$i]->description),trim($items_list[$i]->parent_item_name),trim($items_list[$i]->type),$created_at,$updated_at);
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            //$types_list = Design_lookup_items_master::where('is_deleted',0)->select('type')->distinct()->get()->toArray();
            $types_list = array('COLOR','QUALITY','BODY_PART','ACCESSORY_CATEGORY','ACCESSORY_SUBCATEGORY','ACCESSORY_SIZE','PROCESS_CATEGORY','PROCESS_TYPE','FABRIC_NAME','PACKAGING_SHEET','FABRIC_QUALITY','FABRIC_WIDTH','FABRIC_CONTENT',
            'FABRIC_COUNT','FABRIC_GSM','PRODUCTION_PROCESS','SPECIFICATION_SHEET_ITEM','STORE_ITEM_REGION','STORE_ASSET_CATEGORY','STORE_ASSET_SUBCATEGORY','POS_PRODUCT_CATEGORY','POS_PRODUCT_SUBCATEGORY','SEASON','SOR_PURCHASE_ORDER_CATEGORY',
            'PURCHASE_ORDER_CATEGORY','STORE_ZONE');
            sort($types_list);
            
            
            return view('admin/lookup_items_master_list',array('items_list'=>$items_list,'types_list'=>$types_list,'error_message'=>''));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'LOOKUP_ITEM',__FUNCTION__,__FILE__);
            return view('admin/lookup_items_master_list',array('error_message'=>$e->getMessage(),'items_list'=>array(),'types_list'=>array()));
        }
    }
    
    function data(Request $request,$id){
        try{
            $data = $request->all();
            $item_data = Design_lookup_items_master::where('id',$id)->select('*')->first();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'items data','item_data' => $item_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'LOOKUP_ITEM',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function update(Request $request){
        try{
            
            $data = $request->all();
            $item_id = $data['lookup_item_edit_id'];
            
            $validateionRules = array('itemName_edit'=>'required','itemType_edit'=>'required');
            $attributes = array('itemName_edit'=>'Name','itemType_edit'=>'Type');
            $item_data = Design_lookup_items_master::where('type',$data['itemType_edit'])->first();
            if($item_data['pid'] > 0){
                $validateionRules['itemTypeParent_edit'] = 'required';
                $attributes['itemTypeParent_edit'] = 'Parent';
            }
            
            if(strtoupper($data['itemType_edit']) == 'POS_PRODUCT_CATEGORY'){
                $validateionRules['description_edit'] = 'required|max:2|alpha';
                $attributes['description_edit'] = 'Description';
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $pid = (isset($data['itemTypeParent_edit']) && !empty($data['itemTypeParent_edit']))?$data['itemTypeParent_edit']:0;
            
            if(!in_array(strtolower($data['itemType_edit']),['color','pos_product_category','pos_product_subcategory']) ){
                $itemsExists = Design_lookup_items_master::where('name',$data['itemName_edit'])->where('type',$data['itemType_edit'])->where('pid',$pid)->where('id','!=',$item_id)->where('is_deleted',0)->first();
                if(!empty($itemsExists)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>ucwords(str_replace('_',' ',$data['itemType_edit'])).' already exists', 'errors' => ucwords(str_replace('_',' ',$data['itemType_edit'])).' already exists'));
                }
            }
            
            if(strtoupper($data['itemType_edit']) == 'POS_PRODUCT_CATEGORY'){
                $itemsExists = Design_lookup_items_master::where('description',$data['description_edit'])->where('id','!=',$item_id)->where('is_deleted',0)->first();
                if(!empty($itemsExists)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Category with Description code already exists', 'errors' => 'Category with Description code already exists'));
                }
            }
            
            $api_data = (isset($data['api_data_edit']) && !empty($data['api_data_edit']))?1:0;
            $slug = CommonHelper::getSlug($data['itemName_edit']); 
            $updateArray = array('name'=>$data['itemName_edit'],'type'=>strtoupper($data['itemType_edit']),'pid'=>$pid,'description'=>$data['description_edit'],'api_data'=>$api_data,'slug'=>$slug);
           
            Design_lookup_items_master::where('id', '=', $item_id)->update($updateArray);
            
            CommonHelper::createLog('Lookup Item Updated. ID: '.$item_id,'LOOKUP_ITEM_UPDATED','LOOKUP_ITEM');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design lookup item updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'LOOKUP_ITEM',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateStatus(Request $request){
        try{
            
            $data = $request->all();
            $user_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Design lookup items');
            
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
                
            Design_lookup_items_master::whereIn('id',$user_ids)->update($updateArray);
            
            CommonHelper::createLog('Lookup Item Updated. IDS: '.$data['ids'],'LOOKUP_ITEM_UPDATED','LOOKUP_ITEM');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design lookup items updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'LOOKUP_ITEM',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function add(Request $request){ 
        try{
            $data = $request->all();
            
            $validateionRules = array('itemName_add'=>'required','itemType_add'=>'required');
            $attributes = array('itemName_add'=>'Name','itemType_add'=>'Type');
            
            $item_data = Design_lookup_items_master::where('type',$data['itemType_add'])->first();
            if(isset($item_data['pid']) && $item_data['pid'] > 0){
                $validateionRules['itemTypeParent_add'] = 'required';
                $attributes['itemTypeParent_add'] = 'Parent';
            }
            
            if(strtoupper($data['itemType_add']) == 'POS_PRODUCT_CATEGORY'){
                $validateionRules['description_add'] = 'required|max:2|alpha';
                $attributes['description_add'] = 'Description';
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $pid = (isset($data['itemTypeParent_add']) && !empty($data['itemTypeParent_add']))?$data['itemTypeParent_add']:0;
            
            $itemsExists = Design_lookup_items_master::where('name',$data['itemName_add'])->where('type',$data['itemType_add'])->where('pid',$pid)->where('is_deleted',0)->first();
            if(!empty($itemsExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>ucwords(str_replace('_',' ',$data['itemType_add'])).' already exists', 'errors' => ucwords(str_replace('_',' ',$data['itemType_add'])).' already exists'));
            }
            
            if(strtoupper($data['itemType_add']) == 'POS_PRODUCT_CATEGORY'){
                $itemsExists = Design_lookup_items_master::where('description',$data['description_add'])->where('is_deleted',0)->first();
                if(!empty($itemsExists)){
                    return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Category with Description code already exists', 'errors' => 'Category with Description code already exists'));
                }
            }
            
            $slug = CommonHelper::getSlug($data['itemName_add']); 
            
            $api_data = (isset($data['api_data_add']) && !empty($data['api_data_add']))?1:0;
            $insertArray = array('name'=>$data['itemName_add'],'type'=>strtoupper($data['itemType_add']),'pid'=>$pid,'description'=>$data['description_add'],'api_data'=>$api_data,'slug'=>$slug);
           
            $lookup_item = Design_lookup_items_master::create($insertArray);
            
            CommonHelper::createLog('Lookup Item Created. ID: '.$lookup_item->id,'LOOKUP_ITEM_CREATED','LOOKUP_ITEM');
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Design lookup item added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'LOOKUP_ITEM',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }  
    }
    
    function parentItemsList(Request $request){
        try{
            $data = $request->all();
            $parent_items_list = array();
            $parent_type = '';
            $item_type = strtolower($data['item_type']);
            
            $parents_types = array('color','quality','body_part','accessory_category','process_category','fabric_name','packaging_sheet','pos_product_category');
            
            if(!in_array($item_type,$parents_types)){
                if($item_type == 'accessory_subcategory'){
                    $parent_type = 'accessory_category';
                }elseif($item_type == 'accessory_size'){
                    $parent_type = 'accessory_subcategory';
                }elseif(in_array($item_type,array('fabric_content','fabric_gsm','fabric_quality','fabric_width'))){
                    $parent_type = 'fabric_name';
                }elseif($item_type == 'process_type'){
                    $parent_type = 'process_category';
                }elseif($item_type == 'store_asset_subcategory'){
                    $parent_type = 'store_asset_category';
                }elseif($item_type == 'pos_product_subcategory'){
                    $parent_type = 'pos_product_category';
                }
            }
            
            if(!empty($parent_type)){
                $parent_items_list = Design_lookup_items_master::where('type',$parent_type)->where('is_deleted',0)->where('status',1)->get()->toArray();
            }
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'items list','items_list' => $parent_items_list,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'LOOKUP_ITEM',__FUNCTION__,__FILE__);
            return response(array("httpStatus"=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function getLookupItemsData(Request $request){
        try{
            $data = $request->all();
            
            $design_lookup_items = Design_lookup_items_master::where('is_deleted',0)->where('status',1);
            if(isset($data['pid'])){
                $design_lookup_items = $design_lookup_items->where('pid',$data['pid']);
            }
            if(isset($data['type']) && !empty($data['type'])){
                $design_lookup_items = $design_lookup_items->where('type',$data['type']);
            }
            
            $design_lookup_items = $design_lookup_items->orderBy('name')->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'design lookup items','design_lookup_items'=>$design_lookup_items,'status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'LOOKUP_ITEM',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
}
