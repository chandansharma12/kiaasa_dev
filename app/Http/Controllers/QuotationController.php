<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Design;
use App\Models\Design_item_master;
use App\Models\Design_items_instance;
use App\Models\Unit;
use App\Models\Category;
use App\Models\Product; 
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
use App\Models\Story_master;
use App\Models\Quotation; 
use App\Models\Quotation_vendors; 
use App\Models\Quotation_details; 
use Validator;
use App\Helpers\CommonHelper;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class QuotationController extends Controller
{
    
    public function __construct(){
    }
    
    function listing(Request $request){
        try{
            $quotation_ids = $quotation_submit_list = array();
                    
            $quotation_list = \DB::table('quotation as q')
            ->Join('quotation_vendors as qv','q.id','=','qv.quotation_id')
            ->Join('users as u','q.created_by','=','u.id')
            ->where('q.status',1)
            ->where('q.is_deleted',0)
            ->where('qv.is_deleted',0)        
            ->selectRaw('q.*,u.name as created_by_name,COUNT(qv.id) as vendors_count')
            ->groupByRaw('q.id')
            ->orderBy('q.id','DESC')
            ->paginate(50);
            
            for($i=0;$i<count($quotation_list);$i++){
                $quotation_ids[] = $quotation_list[$i]->id;
            }
            
            if(!empty($quotation_ids)){
                $quotation_submissions = \DB::table('quotation as q')
                ->Join('quotation_vendors as qv','q.id','=','qv.quotation_id')
                ->where("q.id",'>=',min($quotation_ids))        
                ->where("q.id",'<=',max($quotation_ids))                
                ->where('qv.quotation_submitted',1)
                ->where('q.status',1)
                ->where('q.is_deleted',0)
                ->where('qv.is_deleted',0)        
                ->selectRaw('q.id,COUNT(qv.id) as submissions_count')
                ->groupByRaw('q.id')
                ->get()->toArray();       

                for($i=0;$i<count($quotation_submissions);$i++){
                    $quotation_submit_list[$quotation_submissions[$i]->id] = $quotation_submissions[$i]->submissions_count;
                }

                for($i=0;$i<count($quotation_list);$i++){
                    $quotation_list[$i]->submissions_count = (isset($quotation_submit_list[$quotation_list[$i]->id]))?$quotation_submit_list[$quotation_list[$i]->id]:0;
                }
            }
            
            return view('quotation/quotation_list',array('quotation_list'=>$quotation_list,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'QUOTATION',__FUNCTION__,__FILE__);
            return view('quotation/quotation_list',array('error_message'=>$e->getMessage(),'quotation_list'=>array()));
        }
    }
    
    function quotationVendorsList(Request $request,$id){
        try{
            $vendors_list = \DB::table('quotation_vendors as qv')
            ->Join('vendor_detail as vd','qv.vendor_id','=','vd.id')
            ->where('qv.status',1)
            ->where('qv.is_deleted',0)
            ->where('qv.quotation_id',$id)
            ->selectRaw('qv.*,vd.name as vendor_name,vd.email as vendor_email')
            ->orderBy('qv.id','ASC')
            ->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(),'status'=>'success','message' => 'Quotation vendors','vendors_list'=>$vendors_list,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'QUOTATION',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function submitQuotation(Request $request,$quotationId,$vendorId){
        try{
            
            $quotation_detail = Quotation::where('id',$quotationId)->first();
            $quotation_vendor_detail = Quotation_vendors::where('quotation_id',$quotationId)->where('vendor_id',$vendorId)->first();
            
            if($quotation_detail->type_id == 1){
                $quotation_data = \DB::table('quotation_details as qd')
                ->join('design_item_master as dim','dim.id', '=', 'qd.item_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')
                ->leftJoin('design_lookup_items_master as dlim_2','dim.quality_id', '=', 'dlim_2.id')        
                ->leftJoin('design_lookup_items_master as dlim_3','dim.color_id', '=', 'dlim_3.id')
                ->leftJoin('design_lookup_items_master as dlim_4','dim.content_id', '=', 'dlim_4.id')
                ->leftJoin('design_lookup_items_master as dlim_5','dim.gsm_id', '=', 'dlim_5.id')
                ->leftJoin('design_lookup_items_master as dlim_6','dim.width_id', '=', 'dlim_6.id')
                ->leftJoin('units as u1','dim.unit_id', '=', 'u1.id')        
                ->where('qd.quotation_id',$quotationId)->where('qd.vendor_id',$vendorId)->whereIn('dim.type_id',array(1,2,3))->where('qd.is_deleted',0)->where('qd.status',1)
                ->select('qd.*','dlim_1.name as name_name','dlim_2.name as quality_name','dlim_3.name as color_name','dlim_4.name as content_name',
                'dlim_5.name as gsm_name','dlim_6.name as width_name','u1.code as unit_code','dim.type_id')->get()->toArray();
                
            }else{
                $quotation_data = \DB::table('quotation_details as qd')
                ->join('design_item_master as dim','dim.id', '=', 'qd.item_master_id')
                ->join('designs as d','d.id', '=', 'qd.design_id')         
                ->leftJoin('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')
                ->where('qd.quotation_id',$quotationId)->where('qd.vendor_id',$vendorId)->whereIn('dim.type_id',array(4,5))->where('qd.is_deleted',0)->where('qd.status',1)
                ->select('qd.*','dlim_1.name as name_name','dim.type_id','d.sku','d.production_count')->get()->toArray();
                 
                $quotation_info = array();
                for($i=0;$i<count($quotation_data);$i++){
                    $quotation_info[$quotation_data[$i]->design_id]['design_data'] = array('sku'=>$quotation_data[$i]->sku,'production_count'=>$quotation_data[$i]->production_count);
                    $quotation_info[$quotation_data[$i]->design_id]['rows'][] = $quotation_data[$i];
                }
                
                $quotation_data = $quotation_info;
            }
            
            return view('quotation/submit_quotation',array('quotation_data'=>$quotation_data,'quotation_detail'=>$quotation_detail,'quotation_vendor_detail'=>$quotation_vendor_detail,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'QUOTATION',__FUNCTION__,__FILE__);
            return view('quotation/submit_quotation',array('error_message'=>$e->getMessage(),'quotation_data'=>array(),'quotation_detail'=>array(),'quotation_vendor_detail'=>array()));
        }
    }
    
    function saveQuotation(Request $request,$quotationId,$vendorId){
        try{
            $data = $request->all();
            $quotation_detail = Quotation::where('id',$quotationId)->first();
            if($quotation_detail->type_id == 1){
                $fabric_ids = (!empty($data['fabric_ids']))?explode(',',$data['fabric_ids']):array();
                for($i=0;$i<count($fabric_ids);$i++){
                    $row_id = $fabric_ids[$i];
                    $validateionRules['fabric_price_'.$row_id] = 'required|numeric';
                    $attributes['fabric_price_'.$row_id] = 'Fabric '.($i+1).' Quote Price';
                }
                
                $acc_ids = (!empty($data['acc_ids']))?explode(',',$data['acc_ids']):array();
                for($i=0;$i<count($acc_ids);$i++){
                    $row_id = $acc_ids[$i];
                    $validateionRules['acc_price_'.$row_id] = 'required|numeric';
                    $attributes['acc_price_'.$row_id] = 'Accessories '.($i+1).' Quote Price';
                }
                
                $process_ids = (!empty($data['process_ids']))?explode(',',$data['process_ids']):array();
                for($i=0;$i<count($process_ids);$i++){
                    $row_id = $process_ids[$i];
                    $validateionRules['process_price_'.$row_id] = 'required|numeric';
                    $attributes['process_price_'.$row_id] = 'Process '.($i+1).' Quote Price';
                }
                
                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return redirect('quotation/submit/'.$quotationId.'/'.$vendorId)->withErrors($validator)->withInput();
                }
                
                \DB::beginTransaction();
            
                for($i=0;$i<count($fabric_ids);$i++){
                    $id = $fabric_ids[$i];
                    $updateArray = array();
                    $updateArray['price'] = (isset($data['fabric_price_'.$id]) && $data['fabric_price_'.$id] != '')?$data['fabric_price_'.$id]:null;
                    $updateArray['comments'] = (isset($data['fabric_comment_'.$id]) && $data['fabric_comment_'.$id] != '')?$data['fabric_comment_'.$id]:null;
                    Quotation_details::where('id', '=', $id)->update($updateArray);
                }

                for($i=0;$i<count($acc_ids);$i++){
                    $id = $acc_ids[$i];
                    $updateArray = array();
                    $updateArray['price'] = (isset($data['acc_price_'.$id]) && $data['acc_price_'.$id] != '')?$data['acc_price_'.$id]:null;
                    $updateArray['comments'] = (isset($data['acc_comment_'.$id]) && $data['acc_comment_'.$id] != '')?$data['acc_comment_'.$id]:null;
                    Quotation_details::where('id', '=', $id)->update($updateArray);
                }
                
                for($i=0;$i<count($process_ids);$i++){
                    $id = $process_ids[$i];
                    $updateArray = array();
                    $updateArray['price'] = (isset($data['process_price_'.$id]) && $data['process_price_'.$id] != '')?$data['process_price_'.$id]:null;
                    $updateArray['comments'] = (isset($data['process_comment_'.$id]) && $data['process_comment_'.$id] != '')?$data['process_comment_'.$id]:null;
                    Quotation_details::where('id', '=', $id)->update($updateArray);
                }
                
                \DB::commit();
                
                $updateArray = array('quotation_submitted'=>1,'submitted_on'=>date('Y/m/d H:i:s'));
                Quotation_vendors::where('quotation_id',$quotationId)->where('vendor_id',$vendorId)->update($updateArray);
                
                CommonHelper::createLog('Quotation submitted successfully. Quotation ID: '.$quotationId.', Vendor ID:  '.$vendorId,'QUOTATION_SUBMITTED','QUOTATION');
                return redirect('quotation/submit/'.$quotationId.'/'.$vendorId)->with('success_message', 'Quotation submitted successfully');
            
            }else{
                $pack_sheet_ids = (!empty($data['pack_sheet_ids']))?explode(',',$data['pack_sheet_ids']):array();
                for($i=0;$i<count($pack_sheet_ids);$i++){
                    $row_id = $pack_sheet_ids[$i];
                    $validateionRules['pack_sheet_price_'.$row_id] = 'required|numeric';
                    $attributes['pack_sheet_price_'.$row_id] = 'Packaging sheet '.($i+1).' Quote Price';
                }
                
                $prod_process_ids = (!empty($data['prod_process_ids']))?explode(',',$data['prod_process_ids']):array();
                for($i=0;$i<count($prod_process_ids);$i++){
                    $row_id = $prod_process_ids[$i];
                    $validateionRules['prod_process_price_'.$row_id] = 'required|numeric';
                    $attributes['prod_process_price_'.$row_id] = 'Product Process '.($i+1).' Quote Price';
                }
                
                $validator = Validator::make($data,$validateionRules,array(),$attributes);
                if ($validator->fails()){ 
                    return redirect('quotation/submit/'.$quotationId.'/'.$vendorId)->withErrors($validator)->withInput();
                }
                
                \DB::beginTransaction();
            
                for($i=0;$i<count($pack_sheet_ids);$i++){
                    $id = $pack_sheet_ids[$i];
                    $updateArray = array();
                    $updateArray['price'] = (isset($data['pack_sheet_price_'.$id]) && $data['pack_sheet_price_'.$id] != '')?$data['pack_sheet_price_'.$id]:null;
                    $updateArray['comments'] = (isset($data['pack_sheet_comment_'.$id]) && $data['pack_sheet_comment_'.$id] != '')?$data['pack_sheet_comment_'.$id]:null;
                    Quotation_details::where('id', '=', $id)->update($updateArray);
                }
                
                for($i=0;$i<count($prod_process_ids);$i++){
                    $id = $prod_process_ids[$i];
                    $updateArray = array();
                    $updateArray['price'] = (isset($data['prod_process_price_'.$id]) && $data['prod_process_price_'.$id] != '')?$data['prod_process_price_'.$id]:null;
                    $updateArray['comments'] = (isset($data['prod_process_comment_'.$id]) && $data['prod_process_comment_'.$id] != '')?$data['prod_process_comment_'.$id]:null;
                    Quotation_details::where('id', '=', $id)->update($updateArray);
                }
                
                \DB::commit();
                
                $updateArray = array('quotation_submitted'=>1,'submitted_on'=>date('Y/m/d H:i:s'));
                Quotation_vendors::where('quotation_id',$quotationId)->where('vendor_id',$vendorId)->update($updateArray);
                
                CommonHelper::createLog('Quotation submitted successfully. Quotation ID: '.$quotationId.', Vendor ID:  '.$vendorId,'QUOTATION_SUBMITTED','QUOTATION');
                return redirect('quotation/submit/'.$quotationId.'/'.$vendorId)->with('success_message', 'Quotation submitted successfully');
            }
            
            return view('quotation/submit_quotation',array('quotation_data'=>array(),'quotation_detail'=>array(),'quotation_vendor_detail'=>array()));
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'QUOTATION',__FUNCTION__,__FILE__);
            return view('quotation/submit_quotation',array('error_message'=>$e->getMessage(),'quotation_data'=>array(),'quotation_detail'=>array(),'quotation_vendor_detail'=>array()));
        }
    }
    
    function quotationSubmissionsList(Request $request,$id){
        try{
            $data = $request->all();
            $quotationId = $id;
            $vendors_ids = $quote_submitted_vendors_ids = $vendors_data = $quotation_list = $quotation_info = array();
            $quotation_detail = Quotation::where('id',$quotationId)->first();
            $quotation_vendors_detail = Quotation_vendors::where('quotation_id',$quotationId)->get()->toArray();
            
            for($i=0;$i<count($quotation_vendors_detail);$i++){
                if($quotation_vendors_detail[$i]['quotation_submitted'] == 1){
                    $quote_submitted_vendors_ids[] = $quotation_vendors_detail[$i]['vendor_id'];
                }
                
                $vendors_ids[] = $quotation_vendors_detail[$i]['vendor_id'];
            }
            
            if(!empty($quote_submitted_vendors_ids)){
                if($quotation_detail->type_id == 1){
                    $quotation_data = \DB::table('quotation_details as qd')
                    ->join('design_item_master as dim','dim.id', '=', 'qd.item_master_id')
                    ->leftJoin('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')
                    ->leftJoin('design_lookup_items_master as dlim_2','dim.quality_id', '=', 'dlim_2.id')        
                    ->leftJoin('design_lookup_items_master as dlim_3','dim.color_id', '=', 'dlim_3.id')
                    ->leftJoin('design_lookup_items_master as dlim_4','dim.content_id', '=', 'dlim_4.id')
                    ->leftJoin('design_lookup_items_master as dlim_5','dim.gsm_id', '=', 'dlim_5.id')
                    ->leftJoin('design_lookup_items_master as dlim_6','dim.width_id', '=', 'dlim_6.id')
                    ->leftJoin('units as u1','dim.unit_id', '=', 'u1.id')        
                    ->where('qd.quotation_id',$quotationId)->whereIn('dim.type_id',array(1,2,3))->where('qd.is_deleted',0)->where('qd.status',1)
                    ->select('qd.*','dlim_1.name as name_name','dlim_2.name as quality_name','dlim_3.name as color_name','dlim_4.name as content_name',
                    'dlim_5.name as gsm_name','dlim_6.name as width_name','u1.code as unit_code','dim.type_id')->get()->toArray();
                
                    for($i=0;$i<count($quotation_data);$i++){
                        if($quotation_data[$i]->vendor_id == $quote_submitted_vendors_ids[0]){
                            $quotation_list[] = $quotation_data[$i];
                        }

                        $vendors_data[$quotation_data[$i]->vendor_id][$quotation_data[$i]->item_master_id] = $quotation_data[$i];
                    }

                    for($i=0;$i<count($quotation_list);$i++){
                        $max_value = 0;
                        $min_value = 100000000;
                        for($q=0;$q<count($quote_submitted_vendors_ids);$q++){
                            $price = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->price;
                            if($price > $max_value) $max_value = $price;
                            if($price < $min_value) $min_value = $price;
                        }

                        $quotation_list[$i]->max_price = $max_value;
                        $quotation_list[$i]->min_price = $min_value;
                    }
                
                }else{
                    $quotation_data = \DB::table('quotation_details as qd')
                    ->join('design_item_master as dim','dim.id', '=', 'qd.item_master_id')
                    ->leftJoin('design_lookup_items_master as dlim_1','dim.name_id', '=', 'dlim_1.id')
                    ->where('qd.quotation_id',$quotationId)->whereIn('dim.type_id',array(4,5))->where('qd.is_deleted',0)->where('qd.status',1)
                    ->select('qd.*','dlim_1.name as name_name','dim.type_id as item_type_id')->get()->toArray();
                    
                    $designs_ids = array();
                    
                    for($i=0;$i<count($quotation_data);$i++){
                        if($quotation_data[$i]->vendor_id == $quote_submitted_vendors_ids[0]){
                            $quotation_list[] = $quotation_data[$i];
                        }
                        
                        $key_id = $quotation_data[$i]->item_master_id.'_'.$quotation_data[$i]->design_id;
                        $vendors_data[$quotation_data[$i]->vendor_id][$key_id] = $quotation_data[$i];
                        if(!in_array($quotation_data[$i]->design_id,$designs_ids)) $designs_ids[] = $quotation_data[$i]->design_id;
                    }
                    
                    for($i=0;$i<count($quotation_list);$i++){
                        $max_value = 0;
                        $min_value = 100000000;
                        $key_id = $quotation_data[$i]->item_master_id.'_'.$quotation_data[$i]->design_id;
                        for($q=0;$q<count($quote_submitted_vendors_ids);$q++){
                            $price = $vendors_data[$quote_submitted_vendors_ids[$q]][$key_id]->price;
                            if($price > $max_value) $max_value = $price;
                            if($price < $min_value) $min_value = $price;
                        }

                        $quotation_list[$i]->max_price = $max_value;
                        $quotation_list[$i]->min_price = $min_value;
                    }
                    
                    $designs_list = Design::whereIn('id', $designs_ids)->get()->toArray();
                
                    for($i=0;$i<count($designs_list);$i++){
                        $design_id = $designs_list[$i]['id'];
                        $design_data = $designs_list[$i];
                        $data = CommonHelper::getDBArray($quotation_list, 'design_id', $design_id);

                        $quotation_info[] = array('design_data'=>$design_data,'data'=>$data);
                    }
                    
                }
            }
            
            return view('quotation/quotation_submissions',array('quotation_list'=>$quotation_list,'vendors_data'=>$vendors_data,'quote_submitted_vendors_ids'=>$quote_submitted_vendors_ids,'quotation_detail'=>$quotation_detail,'quotation_info'=>$quotation_info,'quotation_id'=>$quotationId,'error_message'=>''));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'QUOTATION',__FUNCTION__,__FILE__);
            return view('quotation/quotation_submissions',array('error_message'=>$e->getMessage(),'quotation_list'=>array(),'vendors_data'=>array(),'quotation_vendor_detail'=>array(),'quotation_detail'=>$quotation_detail,'quotation_info'=>array()));
        }    
    }
   
}
