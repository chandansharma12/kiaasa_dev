<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Design_lookup_items_master;
use App\Models\Product_category_master; 
use App\Models\Pos_product_master;
use App\Models\Pos_product_images;
use App\Models\Production_size_counts;
use App\Models\Story_master;
use App\Models\Purchase_order;
use App\Models\Purchase_order_items;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;

class SorProductController extends Controller
{
    public function __construct(){
    }
    
    function sorProductsAddView(Request $request){
        try{
            //$this->submitImportPosProduct(1);
            //$this->importInventory();
            //$this->importPOItems();
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $category_list = $color_list = $season_list = $size_list = array();
            
            $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','COLOR','SEASON'))->where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            
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
            
            return view('designer/sor_product_add',array('category_list'=>$category_list,'error_message'=>$error_message,'size_list'=>$size_list,
            'color_list'=>$color_list,'user'=>$user,'story_list'=>$story_list,'season_list'=>$season_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('designer/sor_product_add',array('error_message'=>$e->getMessage(),'demands_list'=>array()));
        }
    }
    
    public function addSorProduct(Request $request){
        try{
            
            $data = $request->all();
            $uploaded_images = $required_images = array();
            
            $user = Auth::user();

            $validateionRules = array('product_name_add'=>'required','product_image_add.*'=>'image|mimes:jpeg,png,jpg,gif|max:5120',
            'product_category_add'=>'required','product_subcategory_add'=>'required','size_list_add'=>'required','color_id_add'=>'required',
            'product_base_price_add'=>'required|numeric','product_hsn_code_add'=>'required','gst_inclusive_add'=>'required',
            'product_image_front_add'=>'required|image|mimes:jpeg,png,jpg,gif|max:5120','product_image_back_add'=>'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'product_image_close_add'=>'required|image|mimes:jpeg,png,jpg,gif|max:5120');
            
            $attributes = array('product_name_add'=>'Product Name','product_barcode_add'=>'Barcode','product_sku_add'=>'Item Code','story_id_add'=>'Story','product_base_price_add'=>'Base Price',
            'season_id_add'=>'Season','product_category_add'=>'Category','product_subcategory_add'=>'Subcategory','size_list_add'=>'Size','color_id_add'=>'Color','gst_inclusive_add'=>'required',
            'product_sale_price_add'=>'Sale Price','product_hsn_code_add'=>'HSN Code','product_image_front_add'=>'Front Image','product_image_back_add'=>'Back Image','product_image_close_add'=>'Close Image');
            
            if(!empty($request->file('product_image_add'))){
                for($i=0;$i<count($request->file('product_image_add'));$i++){
                    $attributes['product_image_add.'.$i] = 'Product Image '.($i+1);
                }
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            /*$productExists = Pos_product_master::where('product_sku',$data['product_sku_add'])->where('is_deleted',0)->first();
            if(!empty($productExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product with sku already exists', 'errors' => 'Product with sku already exists'));
            }*/
            \DB::beginTransaction();
            
            /** Create SKU Start **/
            
            $season_abv_1 = $season_abv_2 = $category_abv = $subcategory_abv = $sku_abv = '';
            $season_data = Design_lookup_items_master::where('id',$data['season_id_add'])->where('is_deleted',0)->first(); 
            $season_abv_1 = substr($season_data->name,0,1);
            $season_abv_2 = substr($season_data->name,3,2);
            $category_data = Design_lookup_items_master::where('id',$data['product_category_add'])->where('is_deleted',0)->first(); 
            $category_abv = $category_data->description;
            $subcategory_list = Design_lookup_items_master::where('pid',$data['product_category_add'])->where('is_deleted',0)->orderBy('name','ASC')->get()->toArray();
            
            for($i=0;$i<count($subcategory_list);$i++){
                if($subcategory_list[$i]['id'] == $data['product_subcategory_add']){
                    $subcategory_abv = $i+1;
                    break;
                }
            }
            
            if(!empty($subcategory_abv)){
                $subcategory_abv = (strlen($subcategory_abv) == 1)?'0'.$subcategory_abv:$subcategory_abv;
            }
            
            //check existing products with same categrory, subcategory and season
            /*
            $product_existing = Pos_product_master::where('category_id',$data['product_category_add'])
            ->where('subcategory_id',$data['product_subcategory_add'])->where('season_id',$data['season_id_add'])->where('custom_product',1)
            ->orderBy('product_sku','DESC')->select('product_sku')->first(); 
            */
            
            $product_sku = 'K'.'-'.$season_abv_1.'-'.$season_abv_2.'-'.$category_abv.'-'.$subcategory_abv.'-';
            
            $product_existing = Pos_product_master::where('product_type','sor')
            ->where('product_sku','LIKE',$product_sku.'%')        
            //->where('category_id',$data['product_category_add'])
            //->where('subcategory_id',$data['product_subcategory_add'])
            //->where('season_id',$data['season_id_add'])
            ->where('custom_product',1)
            ->orderBy('product_sku','DESC')
            ->select('product_sku')->first(); 
            
            $sku_abv = (!empty($product_existing))?substr(str_replace('-','',$product_existing->product_sku),8):0;//print_r($sku_abv);exit;
            $sku_abv = str_pad($sku_abv+1,3,'0',STR_PAD_LEFT);
            
            if($season_abv_1 == '' || $season_abv_2 == '' || $category_abv == '' || $subcategory_abv == '' || $sku_abv == ''){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Product SKU', 'errors' => 'Error in creating Product SKU'));
            }
            
            $product_sku = 'K'.'-'.$season_abv_1.'-'.$season_abv_2.'-'.$category_abv.'-'.$subcategory_abv.'-'.$sku_abv;
            
            /** Create SKU End **/
            
            $product_sku_id = Pos_product_master::select('product_sku_id')->orderBy('product_sku_id','DESC')->first(); 
            $product_sku_id = $product_sku_id->product_sku_id+1;
            
            for($z=0;$z<count($data['size_list_add']);$z++){
                $size_id = $data['size_list_add'][$z];
                
                $insertArray = array('product_name'=>$data['product_name_add'],
                'story_id'=>$data['story_id_add'],'season_id'=>$data['season_id_add'],'product_description'=>$data['product_description_add'],'product_type'=>'SOR',
                'category_id'=>$data['product_category_add'],'subcategory_id'=>$data['product_subcategory_add'],'size_id'=>$size_id,'color_id'=>$data['color_id_add'],'custom_product'=>1,
                'base_price'=>$data['product_base_price_add'],'hsn_code'=>$data['product_hsn_code_add'], 'user_id'=>$user->id,'product_sku'=>$product_sku,'gst_inclusive'=>$data['gst_inclusive_add'],
                'vendor_product_sku'=>$data['vendor_product_sku_add'],'product_sku_id'=>$product_sku_id);

                $product = Pos_product_master::create($insertArray);
                
                // Upload images for first product
                if($z == 0){
                    $images = array('product_image_front_add','product_image_back_add','product_image_close_add');
                    for($i=0;$i<count($images);$i++){
                        $image_name = CommonHelper::uploadImage($request,$request->file($images[$i]),'images/pos_product_images/'.$product->id);
                        $image_type = str_replace(array('product_image_','_add'), array('',''), $images[$i]);
                        $insertArray = array('product_id'=>$product->id,'image_name'=>$image_name,'image_title'=>$image_name,'image_type'=>$image_type);
                        Pos_product_images::create($insertArray);
                        $images_product_id = $product->id;
                        $uploaded_images[] = array('name'=>$image_name,'type'=>$image_type);
                    }
                    
                    if(!empty($request->file('product_image_add'))){
                        for($i=0;$i<count($request->file('product_image_add'));$i++){
                            $image_name = CommonHelper::uploadImage($request,$request->file('product_image_add')[$i],'images/pos_product_images/'.$product->id);
                            $insertArray = array('product_id'=>$product->id,'image_name'=>$image_name,'image_title'=>$image_name,'image_type'=>'other');
                            Pos_product_images::create($insertArray);
                            $images_product_id = $product->id;
                            $uploaded_images[] = array('name'=>$image_name,'type'=>'other');
                        }
                    }
                }
                
                // Copy images after first product
                if($z > 0 && !empty($uploaded_images)){
                    for($i=0;$i<count($uploaded_images);$i++){
                        if($i == 0){
                            CommonHelper::createDirectory('images/pos_product_images/'.$product->id);
                            CommonHelper::createDirectory('images/pos_product_images/'.$product->id.'/thumbs');
                        }
                        
                        $img_src = public_path('images/pos_product_images/'.$images_product_id);
                        $img_dest = public_path('images/pos_product_images/'.$product->id);
                        
                        copy($img_src.'/'.$uploaded_images[$i]['name'],$img_dest.'/'.$uploaded_images[$i]['name']);
                        copy($img_src.'/thumbs/'.$uploaded_images[$i]['name'],$img_dest.'/thumbs/'.$uploaded_images[$i]['name']);
                        
                        $insertArray = array('product_id'=>$product->id,'image_name'=>$uploaded_images[$i]['name'],'image_title'=>$uploaded_images[$i]['name'],'image_type'=>$uploaded_images[$i]['type']);
                        Pos_product_images::create($insertArray);
                    }
                }
            }
            
            \DB::commit();
            CommonHelper::createLog('POS Product Created. ID: '.$product->id,'POS_PRODUCT_CREATED','POS_PRODUCT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', Line: '.$e->getLine()),500);
        }  
    }

    public function updatePosProduct(Request $request){
        try{
            $data = $request->all();//print_r($data);exit;
            $product_id = $data['product_edit_id'];
            
            $validateionRules = array('product_name_edit'=>'required','product_image_edit.*'=>'image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_category_edit'=>'required','product_subcategory_edit'=>'required','color_id_edit'=>'required',
            'product_base_price_edit'=>'required|numeric','product_hsn_code_edit'=>'required',
            'product_image_front_edit'=>'image|mimes:jpeg,png,jpg,gif|max:5120','product_image_back_edit'=>'image|mimes:jpeg,png,jpg,gif|max:5120',
            'product_image_close_edit'=>'image|mimes:jpeg,png,jpg,gif|max:5120');
            
            $attributes = array('product_name_edit'=>'Product Name','product_barcode_edit'=>'Barcode','product_sku_edit'=>'SKU','story_id_edit'=>'Story','product_base_price_edit'=>'Base Price',
            'season_id_edit'=>'Season','product_category_edit'=>'Category','product_subcategory_edit'=>'Subcategory','size_id_edit'=>'Size','color_id_edit'=>'Color',
            'product_sale_price_edit'=>'Sale Price','product_hsn_code_edit'=>'HSN Code','product_image_front_edit'=>'Front Image','product_image_back_edit'=>'Back Image','product_image_close_edit'=>'Close Image');
            
            if(!empty($request->file('product_image_edit'))){
                for($i=0;$i<count($request->file('product_image_edit'));$i++){
                    $attributes['product_image_edit.'.$i] = 'Product Image '.($i+1);
                }
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            /*$productExists = Pos_product_master::where('product_sku',$data['product_sku_edit'])->where('id','!=',$product_id)->where('is_deleted',0)->first();
            if(!empty($productExists)){
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product with sku already exists', 'errors' => 'Product with sku already exists'));
            }*/
            
            \DB::beginTransaction();
            
            $product_data = Pos_product_master::where('id',$product_id)->select('*')->first();
            
            $updateArray = array('product_name'=>$data['product_name_edit'],
            'story_id'=>$data['story_id_edit'],'season_id'=>$data['season_id_edit'],'product_description'=>$data['product_description_edit'],'base_price'=>$data['product_base_price_edit'],
            'category_id'=>$data['product_category_edit'],'subcategory_id'=>$data['product_subcategory_edit'],'color_id'=>$data['color_id_edit'],'hsn_code'=>$data['product_hsn_code_edit']);
            
            Pos_product_master::where('id', '=', $product_id)->update($updateArray);
            
            $product_images = Pos_product_images::where('product_id',$product_id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            $images = array('product_image_front_edit','product_image_back_edit','product_image_close_edit');
            for($i=0;$i<count($images);$i++){
                if(!empty($request->file($images[$i]))){
                    $image_name = CommonHelper::uploadImage($request,$request->file($images[$i]),'images/pos_product_images/'.$product_id);
                    $image_type = str_replace(array('product_image_','_edit'), array('',''), $images[$i]);
                    $updateArray = array('image_name'=>$image_name,'image_title'=>$image_name);
                    Pos_product_images::where('product_id',$product_id)->where('image_type',$image_type)->update($updateArray);
                }
            }
             
            // Add new images
            if(!empty($request->file('product_image_edit'))){
                for($i=0;$i<count($request->file('product_image_edit'));$i++){
                    $q = $i+1;
                    if(!empty($request->file('product_image_edit')[$q])){//echo $i;exit;
                        $image_name = CommonHelper::uploadImage($request,$request->file('product_image_edit')[$q],'images/pos_product_images/'.$product_id);
                        $insertArray = array('product_id'=>$product_id,'image_name'=>$image_name,'image_title'=>$image_name,'image_type'=>'other');
                        $q = Pos_product_images::create($insertArray);
                    }
                }
            }
            
            // Iterate through exisiting images to update them
            for($i=0;$i<count($product_images);$i++){
                $image = 'product_image_'.$product_images[$i]['id'].'_edit';
                if(!empty($request->file($image))){
                    $image_name = CommonHelper::uploadImage($request,$request->file($image),'images/pos_product_images/'.$product_id);
                    $updateArray = array('image_name'=>$image_name);
                    Pos_product_images::where('id',$product_images[$i]['id'])->update($updateArray);
                }
            }
            
            // update color in po item if product color is updated.
            if($product_data['color_id'] != $data['color_id_edit']){
                $updateArray = array('quotation_detail_id'=>$data['color_id_edit']);
                Purchase_order_items::where('product_sku',$product_data['product_sku'])->update($updateArray);
                
                $updateArray = array('color_id'=>$data['color_id_edit']);
                Pos_product_master::where('product_sku',$product_data['product_sku'])->update($updateArray);
            }
            
            \DB::commit();
            
            $product_images = Pos_product_images::where('product_id',$product_id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            CommonHelper::createLog('POS Product Updated. ID: '.$product_id,'POS_PRODUCT_UPDATED','POS_PRODUCT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product updated successfully','product_images'=>$product_images,'status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function posProductData(Request $request,$id){
        try{
            $data = $request->all();
            $product_data = Pos_product_master::where('id',$id)->select('*')->first();
            $product_images = Pos_product_images::where('product_id',$id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            $color_data = (!empty($product_data->color_id))?Design_lookup_items_master::where('id',$product_data->color_id)->first():array();;
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product data','product_data' => $product_data,'product_images'=>$product_images,'color_data'=>$color_data,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function deleteProductImage(Request $request,$id){
        try{
            $data = $request->all();exit;
            $image_data = Pos_product_images::where('id',$id)->first();
            Pos_product_images::where('id',$id)->update(array('is_deleted'=>1));
            $product_images = Pos_product_images::where('product_id',$image_data->product_id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product image deleted successfully','product_images'=>$product_images,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    public function posProductUpdateStatus(Request $request){
        try{
            
            $data = $request->all();
            $product_ids = explode(',',$data['ids']);
            $action = strtolower($data['action']);
            
            $validateionRules = array('action'=>'required','ids'=>'required');
            $attributes = array('ids'=>'Products');
            
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
                
            Pos_product_master::whereIn('id',$product_ids)->update($updateArray);
            
            CommonHelper::createLog('POS Product Updated. IDs: '.$data['ids'],'POS_PRODUCT_UPDATED','POS_PRODUCT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Pos Product updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    function editSorProduct(Request $request,$product_id){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $category_list = $color_list = $season_list = $size_list = [];
            $prod_images = array('other'=>[]);
            
            $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','COLOR','SEASON'))->where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            
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
            $product_data = Pos_product_master::where('id',$product_id)->first();
            $product_images = Pos_product_images::where('product_id',$product_id)->where('is_deleted',0)->orderBy('id')->get()->toArray();
            
            for($i=0;$i<count($product_images);$i++){
                if($product_images[$i]['image_type'] == 'front'){
                    $prod_images['front'] = $product_images[$i];
                }elseif($product_images[$i]['image_type'] == 'back'){
                    $prod_images['back'] = $product_images[$i];
                }elseif($product_images[$i]['image_type'] == 'close'){
                    $prod_images['close'] = $product_images[$i];
                }else{
                    $prod_images['other'][] = $product_images[$i];
                }
            }
            
            return view('designer/sor_product_edit',array('category_list'=>$category_list,'error_message'=>$error_message,'size_list'=>$size_list,
            'color_list'=>$color_list,'user'=>$user,'story_list'=>$story_list,'season_list'=>$season_list,'product_data'=>$product_data,'prod_images'=>$prod_images));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('designer/sor_product_edit',array('error_message'=>$e->getMessage(),'demands_list'=>array()));
        }
    }
    
    public function updateSorProduct(Request $request){
        try{
            $data = $request->all();
            $main_images_updated = $other_images_updated = false;
            $product_id = $data['product_id'];
            
            $validateionRules = array('product_name_edit'=>'required','product_image_edit.*'=>'image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_category_edit'=>'required','product_subcategory_edit'=>'required',
            'product_hsn_code_edit'=>'required','product_image_front_edit'=>'image|mimes:jpeg,png,jpg,gif|max:3072',
            'product_image_back_edit'=>'image|mimes:jpeg,png,jpg,gif|max:3072','product_image_close_edit'=>'image|mimes:jpeg,png,jpg,gif|max:3072');
            
            $attributes = array('product_name_edit'=>'Product Name','product_sku_edit'=>'SKU','story_id_edit'=>'Story',
            'season_id_edit'=>'Season','product_category_edit'=>'Category','product_subcategory_edit'=>'Subcategory',
            'product_hsn_code_edit'=>'HSN Code','product_image_front_edit'=>'Front Image','product_image_back_edit'=>'Back Image','product_image_close_edit'=>'Close Image');
            
            // Validate existing edit images
            if(!empty($request->file('product_image_edit'))){
                for($i=0;$i<count($request->file('product_image_edit'));$i++){
                    $validateionRules['product_image_edit.'.$i] = 'image|mimes:jpeg,png,jpg,gif|max:3072';
                    $attributes['product_image_edit.'.$i] = 'Product Image '.($i+1);
                }
            }
            
            // Validate new images
            $product_images = Pos_product_images::where('product_id',$product_id)->where('is_deleted',0)->where('status',1)->orderBy('id')->get()->toArray();
            
            for($i=0;$i<count($product_images);$i++){
                $image = 'product_image_'.$product_images[$i]['id'].'_edit';
                $validateionRules[$image] = 'image|mimes:jpeg,png,jpg,gif|max:3072';
                $attributes[$image] = 'Product Image ';
            }
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>400, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            \DB::beginTransaction();
            
            $product_data = Pos_product_master::where('id',$product_id)->select('*')->first();
            
            $story_id_edit = (isset($data['story_id_edit']))?$data['story_id_edit']:0;
            
            $updateArray = array('product_name'=>$data['product_name_edit'],
            'story_id'=>$story_id_edit,'season_id'=>$data['season_id_edit'],'product_description'=>$data['product_description_edit'],
            'category_id'=>$data['product_category_edit'],'subcategory_id'=>$data['product_subcategory_edit'],'hsn_code'=>$data['product_hsn_code_edit']);
            
            Pos_product_master::where('id',$product_id)->update($updateArray);
            
            $images = array('product_image_front_edit','product_image_back_edit','product_image_close_edit');
            for($i=0;$i<count($images);$i++){
                if(!empty($request->file($images[$i]))){
                    $image_name = CommonHelper::uploadImage($request,$request->file($images[$i]),'images/pos_product_images/'.$product_id);
                    $image_type = str_replace(array('product_image_','_edit'), array('',''), $images[$i]);
                    $updateArray = array('image_name'=>$image_name,'image_title'=>$image_name);
                    Pos_product_images::where('product_id',$product_id)->where('image_type',$image_type)->update($updateArray);
                    $main_images_updated = true;
                }
            }
             
            // Add new images
            if(!empty($request->file('product_image_edit'))){
                for($i=0;$i<count($request->file('product_image_edit'));$i++){
                    if(!empty($request->file('product_image_edit')[$i])){//echo $i;exit;
                        $image_name = CommonHelper::uploadImage($request,$request->file('product_image_edit')[$i],'images/pos_product_images/'.$product_id);
                        $insertArray = array('product_id'=>$product_id,'image_name'=>$image_name,'image_title'=>$image_name,'image_type'=>'other');
                        $q = Pos_product_images::create($insertArray);
                        $other_images_updated = true;
                    }
                }
            }
            
            // Iterate through exisiting images to update them
            for($i=0;$i<count($product_images);$i++){
                $image = 'product_image_'.$product_images[$i]['id'].'_edit';
                if(!empty($request->file($image))){
                    $image_name = CommonHelper::uploadImage($request,$request->file($image),'images/pos_product_images/'.$product_id);
                    $updateArray = array('image_name'=>$image_name);
                    Pos_product_images::where('id',$product_images[$i]['id'])->update($updateArray);
                    $other_images_updated = true;
                }
            }
            
            // Update front, back and close images if uploaded
            if($main_images_updated){
                // Other products of this SKU, but exclude this id
                $other_products = Pos_product_master::where('product_sku',$product_data->product_sku)->where('id','!=',$product_data->id)->where('is_deleted',0)->select('*')->orderBy('id')->get()->toArray();
                $images_list = Pos_product_images::where('product_id',$product_id)->wherein('image_type',['front','back','close'])->where('is_deleted',0)->orderBy('id')->get()->toArray();
                for($i=0;$i<count($other_products);$i++){
                    $other_product_id = $other_products[$i]['id'];
                    // Create directory if does not exists
                    CommonHelper::createDirectory('images/pos_product_images/'.$other_product_id);
                    CommonHelper::createDirectory('images/pos_product_images/'.$other_product_id.'/thumbs');
                    
                    for($q=0;$q<count($images_list);$q++){
                        
                        $image_exists = Pos_product_images::where('product_id',$other_product_id)->where('image_type',$images_list[$q]['image_type'])->where('is_deleted',0)->first();
                        
                        if(!empty($image_exists)){
                            // Delete image if exists as image is not replaced
                            if(file_exists(public_path('/images/pos_product_images/'.$other_product_id.'/'.$images_list[$q]['image_name']))){
                                unlink(public_path('/images/pos_product_images/'.$other_product_id.'/'.$images_list[$q]['image_name']));
                                unlink(public_path('/images/pos_product_images/'.$other_product_id.'/thumbs/'.$images_list[$q]['image_name']));
                            }
                            
                            $updateArray = ['image_name'=>$images_list[$q]['image_name'],'image_title'=>$images_list[$q]['image_name']];
                            Pos_product_images::where('product_id',$other_product_id)->where('image_type',$images_list[$q]['image_type'])->where('is_deleted',0)->update($updateArray);
                        }else{
                            $insertArray = ['image_name'=>$images_list[$q]['image_name'],'image_title'=>$images_list[$q]['image_name'],'product_id'=>$other_product_id,'image_type'=>$images_list[$q]['image_type']];
                            Pos_product_images::create($insertArray);
                        }
                        
                        // Copy images 
                        $img_src = public_path('images/pos_product_images/'.$images_list[$q]['product_id']);
                        $img_dest = public_path('images/pos_product_images/'.$other_product_id);
                        
                        if(file_exists($img_src.'/'.$images_list[$q]['image_name'])){
                            copy($img_src.'/'.$images_list[$q]['image_name'],$img_dest.'/'.$images_list[$q]['image_name']);
                            copy($img_src.'/thumbs/'.$images_list[$q]['image_name'],$img_dest.'/thumbs/'.$images_list[$q]['image_name']);
                        }
                    }
                }
            }
            
            // Update if other type images are uploaded
            if($other_images_updated){
                // Other products of this SKU, but exclude this id
                $other_products = Pos_product_master::where('product_sku',$product_data->product_sku)->where('id','!=',$product_data->id)->where('is_deleted',0)->select('*')->orderBy('id')->get()->toArray();
                $images_list = Pos_product_images::where('product_id',$product_id)->where('image_type','other')->where('is_deleted',0)->orderBy('id')->get()->toArray();
                for($i=0;$i<count($other_products);$i++){
                    $other_product_id = $other_products[$i]['id'];
                    
                    // Create directory if does not exists
                    CommonHelper::createDirectory('images/pos_product_images/'.$other_product_id);
                    CommonHelper::createDirectory('images/pos_product_images/'.$other_product_id.'/thumbs');
                    
                    // Delete existing other images of products
                    $other_product_images = Pos_product_images::where('product_id',$other_product_id)->where('image_type','other')->where('is_deleted',0)->get()->toArray();
                    for($q=0;$q<count($other_product_images);$q++){
                        if(file_exists(public_path('/images/pos_product_images/'.$other_product_id.'/'.$other_product_images[$q]['image_name']))){
                            unlink(public_path('/images/pos_product_images/'.$other_product_id.'/'.$other_product_images[$q]['image_name']));
                            unlink(public_path('/images/pos_product_images/'.$other_product_id.'/thumbs/'.$other_product_images[$q]['image_name']));
                        }
                        
                        $updateArray = ['is_deleted'=>1];
                        Pos_product_images::where('id',$other_product_images[$q]['id'])->update($updateArray);
                    }
                    
                    // Insert new images
                    for($q=0;$q<count($images_list);$q++){
                        $insertArray = ['image_name'=>$images_list[$q]['image_name'],'image_title'=>$images_list[$q]['image_name'],'product_id'=>$other_product_id,'image_type'=>$images_list[$q]['image_type']];
                        Pos_product_images::create($insertArray);
                        
                        $img_src = public_path('images/pos_product_images/'.$images_list[$q]['product_id']);
                        $img_dest = public_path('images/pos_product_images/'.$other_product_id);
                        
                        copy($img_src.'/'.$images_list[$q]['image_name'],$img_dest.'/'.$images_list[$q]['image_name']);
                        copy($img_src.'/thumbs/'.$images_list[$q]['image_name'],$img_dest.'/thumbs/'.$images_list[$q]['image_name']);
                    }
                }
            }
            
            \DB::commit();
            
            CommonHelper::createLog('POS Product Updated. ID: '.$product_id,'POS_PRODUCT_UPDATED','POS_PRODUCT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
}
