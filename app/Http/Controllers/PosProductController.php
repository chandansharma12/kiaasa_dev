<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Design;
use App\Models\Store;
use App\Models\Pages_description;
use App\Models\Store_asset;
use App\Models\Store_asset_detail;
use App\Models\Store_asset_order;
use App\Models\Store_asset_order_detail;
use App\Models\Store_products_demand;
use App\Models\Store_products_demand_detail;
use App\Models\Store_products_demand_inventory;
use App\Models\Design_lookup_items_master;
use App\Models\Product_category_master; 
use App\Models\Pos_product_master;
use App\Models\Pos_product_images;
use App\Models\Pos_product_master_inventory;
use App\Models\Production_size_counts;
use App\Models\Story_master;
use App\Models\Purchase_order;
use App\Models\Purchase_order_items;
use App\Models\Pos_customer_orders;
use App\Models\Pos_customer_orders_detail;
use App\Models\Pos_customer_orders_payments;
use App\Models\Purchase_order_details;
use App\Models\Purchase_order_grn_qc_items;
use App\Models\Purchase_order_grn_qc;
use App\Models\User;
use App\Models\Discount;
use App\Models\Vendor_detail;
use App\Models\Debit_notes;
use App\Models\Debit_note_items;
use App\Models\Category_hsn_code;
use App\Models\Scheduled_tasks;
use App\Models\Scheduled_tasks_details;
use App\Helpers\CommonHelper;
use Validator;
use Illuminate\Validation\Rule;
use Exception;
use DateTime;

class PosProductController extends Controller
{
    public function __construct(){
    }
    
    function posProductsList(Request $request){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $category_list = $color_list = $season_list = $size_list = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if(isset($data['action']) && $data['action'] == 'get_prod_hsn_code'){
                $hsn_code = CommonHelper::getFabricPosProductHsnCode($data['category_id']);
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'hsn code','hsn_code'=>$hsn_code,'status' => 'success'),200);
            }
            
            if(isset($data['action']) && $data['action'] == 'add_other_size_product'){
                \DB::beginTransaction();
                
                $validationRules = array('product_id'=>'required','size_id'=>'required');
                $attributes = array('product_id'=>'Product','size_id'=>'Size');
                $validator = Validator::make($data,$validationRules,array(),$attributes);
                
                if ($validator->fails()){ 
                    return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }
                
                $product_data = Pos_product_master::where('id',$data['product_id'])->first()->toArray();
                unset($product_data['product_barcode']);
                unset($product_data['created_at']);
                unset($product_data['updated_at']);
                
                $product_data['size_id'] = $data['size_id'];
                $product_data['user_id'] = $user->id;
                
                $product = Pos_product_master::create($product_data);
                
                $product_images = Pos_product_images::where('product_id',$data['product_id'])->where('is_deleted',0)->where('status',1)->get()->toArray();
                
                // Copy images
                if(!empty($product_images)){
                    for($i=0;$i<count($product_images);$i++){
                        if($i == 0){
                            CommonHelper::createDirectory('images/pos_product_images/'.$product->id);
                            CommonHelper::createDirectory('images/pos_product_images/'.$product->id.'/thumbs');
                        }
                        
                        $img_src = public_path('images/pos_product_images/'.$data['product_id']);
                        $img_dest = public_path('images/pos_product_images/'.$product->id);
                        
                        if(file_exists($img_src)){
                            copy($img_src.'/'.$product_images[$i]['image_name'],$img_dest.'/'.$product_images[$i]['image_name']);
                            copy($img_src.'/thumbs/'.$product_images[$i]['image_name'],$img_dest.'/thumbs/'.$product_images[$i]['image_name']);

                            $insertArray = array('product_id'=>$product->id,'image_name'=>$product_images[$i]['image_name'],'image_title'=>$product_images[$i]['image_title'],'image_type'=>$product_images[$i]['image_type']);
                            Pos_product_images::create($insertArray);
                        }
                    }
                }
                
                \DB::commit();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Size added successfully'),200);
            }
            
            $pos_product_list = \DB::table('pos_product_master as ppm')
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')
            ->leftJoin('story_master as sm','ppm.story_id', '=', 'sm.id')        
            ->leftJoin('design_lookup_items_master as dlim_4','ppm.season_id', '=', 'dlim_4.id')            
            ->where('ppm.is_deleted',0)
            ->selectRaw('ppm.*,dlim_1.name as category_name,dlim_2.name as subcategory_name,dlim_3.name as color_name,psc.size as size_name,sm.name as story_name, dlim_4.name as season_name');
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'ppm.id','name'=>'ppm.product_name','barcode'=>'ppm.product_barcode','sku'=>'ppm.product_sku','category'=>'category_name',
                'subcategory'=>'subcategory_name','description'=>'ppm.product_description','base_price'=>'ppm.base_price','sale_price'=>'ppm.sale_price','status'=>'ppm.status',
                'created'=>'ppm.created_at','updated'=>'ppm.updated_at','size'=>'size_name','color'=>'color_name','inventory'=>'inventory_count','story'=>'story_name','season'=>'season_name');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'ppm.id';
                $pos_product_list = $pos_product_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }else{
                $pos_product_list = $pos_product_list->orderBy('ppm.id','ASC');
            }
            
            if(isset($data['prod_type']) && !empty($data['prod_type'])){
                if($data['prod_type'] == 1)
                    $pos_product_list = $pos_product_list->where('ppm.arnon_product',0);
                if($data['prod_type'] == 2)
                    $pos_product_list = $pos_product_list->where('ppm.arnon_product',1);
            }
            
            if(isset($data['prod_name_search']) && !empty($data['prod_name_search'])){
                $product_name = trim($data['prod_name_search']);
                $vendor_sku = (strpos($product_name,'-') !== false)?substr($product_name,0, strrpos($product_name, '-')):$product_name;
                $pos_product_list = $pos_product_list->whereRaw("(ppm.product_name LIKE '%{$product_name}%' OR ppm.product_barcode = '$product_name' OR ppm.product_sku = '$product_name' OR ppm.product_sku = '$vendor_sku')");
            }
            
            if(isset($data['size_search']) && !empty($data['size_search'])){
                $pos_product_list = $pos_product_list->where('ppm.size_id',$data['size_search']);
            }
            
            if(isset($data['color_search']) && !empty($data['color_search'])){
                $pos_product_list = $pos_product_list->where('ppm.color_id',$data['color_search']);
            }
            
            if(isset($data['category_search']) && !empty($data['category_search'])){
                $pos_product_list = $pos_product_list->where('ppm.category_id',$data['category_search']);
            }
            
            if(isset($data['product_subcategory_search']) && !empty($data['product_subcategory_search'])){
                $pos_product_list = $pos_product_list->where('ppm.subcategory_id',$data['product_subcategory_search']);
            }
            
            if(isset($data['id']) && !empty($data['id'])){
                $pos_product_list = $pos_product_list->where('ppm.id',trim($data['id']));
            }
            
            // If user is logged as designer, then display only user products
            if($user->user_type == 5){
                $pos_product_list = $pos_product_list->where('ppm.user_id',$user->id);
            }
            
            if($is_fake_inventory_user){
                $pos_product_list = $pos_product_list->where('ppm.fake_inventory',1);
            }else{
                $pos_product_list = $pos_product_list->where('ppm.fake_inventory',0);
            }
            
            //$pos_product_list = $pos_product_list->paginate(100);
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $pos_product_count = trim($data['pos_product_count']);
                $pos_product_count_arr = explode('_',$pos_product_count);
                $start = $pos_product_count_arr[0];
                $start = $start-1;
                $end = $pos_product_count_arr[1];
                $limit = $end-$start;
                $pos_product_list = $pos_product_list->offset($start)->limit($limit)->get();
            }else{
                $pos_product_list = $pos_product_list->paginate(100);
                $pos_product_count = $pos_product_list->total();
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=pos_products.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                
                $columns = array('Brand Name', 'Product Name', 'Product Description', 'GTIN', 'Product Packaging Level', 'Primary GTIN', 'SKU Number', 'Category', 'Sub Category', 'Product Webpage URL',
                'Video URL','Marketing Information','Measurement Unit','Net Content/Count','Mass Measurement Unit','Gross Weight','Net Weight','Target Market','MRP Location','MRP (inRupees)',
                'MRP Activation Date','HS Code','IGST (Without %)','Date of Activation','Date of Deactivation','Country of Origin','Ingredients','Allergen Information','Storage Condition');

                $callback = function() use ($pos_product_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    for($i=0;$i<count($pos_product_list);$i++){
                        $product_name = $pos_product_list[$i]->product_name.' '.$pos_product_list[$i]->size_name.' '.$pos_product_list[$i]->color_name;
                        $array = array('KIASA',$product_name,$pos_product_list[$i]->product_description,'','','',$pos_product_list[$i]->product_sku,$pos_product_list[$i]->category_name,
                        $pos_product_list[$i]->subcategory_name,'','','','','','','','','Retail','PAN India',$pos_product_list[$i]->sale_price,'','','','','','','','','');

                        fputcsv($file, $array);
                    }
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
            
            $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','COLOR','SEASON'))->where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            
            $po_list = Purchase_order::wherein('type_id',array(3,5));
            if($is_fake_inventory_user){
                $po_list = $po_list->where('fake_inventory',1);
            }else{
                $po_list = $po_list->where('fake_inventory',0);
            }
            $po_list = $po_list->get()->toArray();
            
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
            
            return view('admin/pos_product_list',array('category_list'=>$category_list,'error_message'=>$error_message,'pos_product_list'=>$pos_product_list,'size_list'=>$size_list,
            'color_list'=>$color_list,'user'=>$user,'story_list'=>$story_list,'season_list'=>$season_list,'po_list'=>$po_list,'is_fake_inventory_user'=>$is_fake_inventory_user,'pos_product_count'=>$pos_product_count));
            
        }catch (\Exception $e){
            
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
            }else{
                return view('admin/pos_product_list',array('error_message'=>$e->getMessage()));
            }
        }
    }
    
    public function addPosProduct(Request $request){
        try{
            
            $data = $request->all();
            $uploaded_images = $required_images = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            $validateionRules = array('product_name_add'=>'required','product_image_add.*'=>'image|mimes:jpeg,png,jpg,gif|max:3072',
            'product_category_add'=>'required','product_subcategory_add'=>'required','size_list_add'=>'required','color_id_add'=>'required',
            'product_base_price_add'=>'required|numeric','product_hsn_code_add'=>'required','gst_inclusive_add'=>'required',
            'product_image_front_add'=>'required|image|mimes:jpeg,png,jpg,gif|max:3072','product_image_back_add'=>'required|image|mimes:jpeg,png,jpg,gif|max:3072',
            'product_image_close_add'=>'required|image|mimes:jpeg,png,jpg,gif|max:3072');
            
            $attributes = array('product_name_add'=>'Product Name','product_barcode_add'=>'Barcode','product_sku_add'=>'Item Code','story_id_add'=>'Story','product_base_price_add'=>'Base Price',
            'season_id_add'=>'Season','product_category_add'=>'Category','product_subcategory_add'=>'Subcategory','size_list_add'=>'Size','color_id_add'=>'Color','gst_inclusive_add'=>'GST Inclusive',
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
            
            $product_sku = 'K'.'-'.$season_abv_1.'-'.$season_abv_2.'-'.$category_abv.'-'.$subcategory_abv.'-';
            
            //check existing products with same category, subcategory and season
            $product_existing = Pos_product_master::where('product_type','sor')
            ->where('product_sku','LIKE',$product_sku.'%')        
            //->where('category_id',$data['product_category_add'])
            //->where('subcategory_id',$data['product_subcategory_add'])
            //->where('season_id',$data['season_id_add'])
            ->where('custom_product',1)
            ->orderBy('product_sku','DESC')
            ->select('product_sku')->first(); 
            
            $sku_abv = (!empty($product_existing))?substr(str_replace('-','',$product_existing->product_sku),8):0;
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
                
                $insertArray = array('product_name'=>$data['product_name_add'],'custom_product'=>1,
                'story_id'=>$data['story_id_add'],'season_id'=>$data['season_id_add'],'product_description'=>$data['product_description_add'],'product_type'=>'sor',
                'category_id'=>$data['product_category_add'],'subcategory_id'=>$data['product_subcategory_add'],'size_id'=>$size_id,'color_id'=>$data['color_id_add'],
                'base_price'=>$data['product_base_price_add'],'hsn_code'=>$data['product_hsn_code_add'],'product_sku'=>$product_sku,'gst_inclusive'=>$data['gst_inclusive_add'],
                'vendor_product_sku'=>$data['vendor_product_sku_add'],'product_sku_id'=>$product_sku_id);
                
                if($is_fake_inventory_user){
                    $insertArray['fake_inventory'] = 1;
                }

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
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }

    public function updatePosProduct(Request $request){
        try{
            $data = $request->all();//print_r($data);exit;
            $main_images_updated = $other_images_updated = false;
            $product_id = $data['product_edit_id'];
            
            $validateionRules = array('product_name_edit'=>'required','product_image_edit.*'=>'image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_category_edit'=>'required','product_subcategory_edit'=>'required','color_id_edit'=>'required',
            'product_base_price_edit'=>'required|numeric','product_hsn_code_edit'=>'required','gst_inclusive_edit'=>'required',
            'product_image_front_edit'=>'image|mimes:jpeg,png,jpg,gif|max:3072','product_image_back_edit'=>'image|mimes:jpeg,png,jpg,gif|max:3072',
            'product_image_close_edit'=>'image|mimes:jpeg,png,jpg,gif|max:3072');
            
            $attributes = array('product_name_edit'=>'Product Name','product_barcode_edit'=>'Barcode','product_sku_edit'=>'SKU','story_id_edit'=>'Story','product_base_price_edit'=>'Base Price',
            'season_id_edit'=>'Season','product_category_edit'=>'Category','product_subcategory_edit'=>'Subcategory','size_id_edit'=>'Size','color_id_edit'=>'Color','gst_inclusive_edit'=>'GST Inclusive',
            'product_sale_price_edit'=>'Sale Price','product_hsn_code_edit'=>'HSN Code','product_image_front_edit'=>'Front Image','product_image_back_edit'=>'Back Image','product_image_close_edit'=>'Close Image');
            
            if(!empty($request->file('product_image_edit'))){
                for($i=0;$i<count($request->file('product_image_edit'));$i++){
                    $attributes['product_image_edit.'.$i] = 'Product Image '.($i+1);
                }
            }
            
            $images = ['front','back','close'];
            
            for($i=0;$i<count($images);$i++){
                $image_type = $images[$i];
                $imageExists = Pos_product_images::where('product_id',$product_id)->where('image_type',$image_type)->where('is_deleted',0)->first();
                if(empty($imageExists) && empty($request->file('product_image_'.$image_type.'_edit'))){
                    $validateionRules['product_image_'.$image_type.'_edit'] = 'required|image|mimes:jpeg,png,jpg,gif|max:3072';
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
            
            $story_id_edit = (isset($data['story_id_edit']))?$data['story_id_edit']:0;
            
            $updateArray = array('product_name'=>$data['product_name_edit'],'gst_inclusive'=>$data['gst_inclusive_edit'],'product_barcode'=>$data['product_barcode_edit'],'sale_price'=>$data['product_sale_price_edit'],
            'story_id'=>$story_id_edit,'season_id'=>$data['season_id_edit'],'product_description'=>$data['product_description_edit'],'base_price'=>$data['product_base_price_edit'],
            'category_id'=>$data['product_category_edit'],'subcategory_id'=>$data['product_subcategory_edit'],'color_id'=>$data['color_id_edit'],'hsn_code'=>$data['product_hsn_code_edit'],'vendor_product_sku'=>$data['vendor_product_sku_edit']);
            
            
            Pos_product_master::where('id', '=', $product_id)->update($updateArray);
            
            $product_images = Pos_product_images::where('product_id',$product_id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            $images = array('product_image_front_edit','product_image_back_edit','product_image_close_edit');
            for($i=0;$i<count($images);$i++){
                if(!empty($request->file($images[$i]))){
                    $image_name = CommonHelper::uploadImage($request,$request->file($images[$i]),'images/pos_product_images/'.$product_id);
                    $image_type = str_replace(array('product_image_','_edit'), array('',''), $images[$i]);
                    $imageExists = Pos_product_images::where('product_id',$product_id)->where('image_type',$image_type)->where('is_deleted',0)->first();
                    if(!empty($imageExists)){
                        $updateArray = array('image_name'=>$image_name,'image_title'=>$image_name);
                        Pos_product_images::where('product_id',$product_id)->where('image_type',$image_type)->update($updateArray);
                    }else{
                        $insertArray = array('product_id'=>$product_id,'image_name'=>$image_name,'image_title'=>$image_name,'image_type'=>$image_type);
                        Pos_product_images::create($insertArray);
                    }
                    
                    $main_images_updated = true;
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
                        
                        copy($img_src.'/'.$images_list[$q]['image_name'],$img_dest.'/'.$images_list[$q]['image_name']);
                        copy($img_src.'/thumbs/'.$images_list[$q]['image_name'],$img_dest.'/thumbs/'.$images_list[$q]['image_name']);
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
            
            // update color in po item if product color is updated.
            if($product_data['color_id'] != $data['color_id_edit']){
                $updateArray = array('quotation_detail_id'=>$data['color_id_edit']);
                Purchase_order_items::where('product_sku',$product_data['product_sku'])->update($updateArray);
                
                $updateArray = array('color_id'=>$data['color_id_edit']);
                Pos_product_master::where('product_sku',$product_data['product_sku'])->update($updateArray);
            }
            
            // Update sale price of other sizes if product sale price is updated.
            if($product_data['sale_price'] != trim($data['product_sale_price_edit'])){
                $updateArray = array('sale_price'=>trim($data['product_sale_price_edit']));
                Pos_product_master::where('product_sku',$product_data['product_sku'])->update($updateArray);
                
                if(strtolower($product_data['product_type']) == 'design'){
                    $updateArray = array('mrp'=>trim($data['product_sale_price_edit']));
                    Design::where('sku',$product_data['product_sku'])->update($updateArray);
                }
            }
            
            // Update base price of other sizes if product base price is updated.
            if($product_data['base_price'] != trim($data['product_base_price_edit'])){
                $updateArray = array('base_price'=>trim($data['product_base_price_edit']));
                Pos_product_master::where('product_sku',$product_data['product_sku'])->update($updateArray);
            }
            
            // Update description of other sizes if product description is updated.
            if($product_data['product_description'] != trim($data['product_description_edit'])){
                $updateArray = array('product_description'=>trim($data['product_description_edit']));
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
            $size_list = array();
            $product_data = Pos_product_master::where('id',$id)->select('*')->first();
            $product_images = Pos_product_images::where('product_id',$id)->where('is_deleted',0)->where('status',1)->orderBy('id')->get()->toArray();
            
            $color_data = (!empty($product_data->color_id))?Design_lookup_items_master::where('id',$product_data->color_id)->first():array();;
            
            // Fetch size list which are not added for this sku
            $size_data = \DB::table('production_size_counts as psc')
            ->leftJoin('pos_product_master as ppm',function($join) use ($product_data){$join->on('ppm.size_id', '=', 'psc.id')->where('ppm.product_sku','=',$product_data->product_sku)->where('ppm.is_deleted','=',0);})        
            ->where('psc.status',1)
            ->where('psc.is_deleted',0)        
            ->select('psc.*','ppm.size_id')        
            ->get()->toArray();        
            
            for($i=0;$i<count($size_data);$i++){
                if(empty($size_data[$i]->size_id)){
                    $size_list[] = $size_data[$i];
                }
            }
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product data','product_data' => $product_data,'product_images'=>$product_images,'color_data'=>$color_data,'size_list' => $size_list),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function deleteProductImage(Request $request,$id){
        try{
            $data = $request->all();
            $image_data = Pos_product_images::where('id',$id)->first();
            $product_data = Pos_product_master::where('id',$image_data->product_id)->select('*')->first();
            
            // Delete Other type image
            if($image_data->image_type == 'other'){
                Pos_product_images::where('id',$id)->update(array('is_deleted'=>1));
                
                if(file_exists(public_path('/images/pos_product_images/'.$image_data->product_id.'/'.$image_data->image_name))){
                    unlink(public_path('/images/pos_product_images/'.$image_data->product_id.'/'.$image_data->image_name));
                    unlink(public_path('/images/pos_product_images/'.$image_data->product_id.'/thumbs/'.$image_data->image_name));
                }

                /* Update images of other products with this SKU Start */
                
                $other_products = Pos_product_master::where('product_sku',$product_data->product_sku)->where('id','!=',$product_data->id)->where('is_deleted',0)->select('*')->orderBy('id')->get()->toArray();
                $images_list = Pos_product_images::where('product_id',$product_data->id)->where('image_type','other')->where('is_deleted',0)->orderBy('id')->get()->toArray();

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
            
            /* Update images of other products with this SKU End */
            
            // Delete front, back, close images
            if(in_array($image_data->image_type,['front','back','close'])){
                $products = Pos_product_master::where('product_sku',$product_data->product_sku)->where('is_deleted',0)->select('*')->orderBy('id')->get()->toArray();
                
                for($i=0;$i<count($products);$i++){
                    $product_id = $products[$i]['id'];
                    $product_image = Pos_product_images::where('product_id',$product_id)->where('image_type',$image_data->image_type)->where('is_deleted',0)->first();
                    
                    if(!empty($product_image)){
                        if(file_exists(public_path('/images/pos_product_images/'.$product_id.'/'.$product_image->image_name))){
                            unlink(public_path('/images/pos_product_images/'.$product_id.'/'.$product_image->image_name));
                            unlink(public_path('/images/pos_product_images/'.$product_id.'/thumbs/'.$product_image->image_name));
                        }

                        $updateArray = ['is_deleted'=>1];
                        Pos_product_images::where('id',$product_image->id)->update($updateArray);
                    }
                }
            }
            
            $product_images = Pos_product_images::where('product_id',$image_data->product_id)->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product image deleted successfully','product_images'=>$product_images,'status' => 'success'),200);
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
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
    
    function downloadPosProductCsv(Request $request,$type_id){
        try{
            $data = $request->all();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=products.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
            
            /*if($type_id == 1){
                $pos_product_list = \DB::table('pos_product_master as ppm')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
                ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
                ->join('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
                ->join('production_size_counts as psc','ppm.size_id', '=', 'psc.id')            
                ->where('ppm.is_deleted',0)
                ->select('ppm.*','dlim_1.name as category_name','dlim_2.name as subcategory_name','dlim_3.name as color_name','psc.size as size_name');
                
                if($is_fake_inventory_user){
                    $pos_product_list = $pos_product_list->where('ppm.fake_inventory',1);
                }else{
                    $pos_product_list = $pos_product_list->where('ppm.fake_inventory',0);
                }
                
                $pos_product_list = $pos_product_list->get()->toArray();;

                $columns = array('Brand Name', 'Product Name', 'Product Description', 'GTIN', 'Product Packaging Level', 'Primary GTIN', 'SKU Number', 'Category', 'Sub Category', 'Product Webpage URL',
                'Video URL','Marketing Information','Measurement Unit','Net Content/Count','Mass Measurement Unit','Gross Weight','Net Weight','Target Market','MRP Location','MRP (inRupees)',
                'MRP Activation Date','HS Code','IGST (Without %)','Date of Activation','Date of Deactivation','Country of Origin','Ingredients','Allergen Information','Storage Condition');

                $callback = function() use ($pos_product_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    for($i=0;$i<count($pos_product_list);$i++){
                        $product_name = $pos_product_list[$i]->product_name.' '.$pos_product_list[$i]->size_name.' '.$pos_product_list[$i]->color_name;
                        $array = array('KIASA',$product_name,$pos_product_list[$i]->product_description,'','','',$pos_product_list[$i]->product_sku,$pos_product_list[$i]->category_name,
                        $pos_product_list[$i]->subcategory_name,'','','','','','','','','Retail','PAN India',$pos_product_list[$i]->sale_price,'','','','','','','','','');

                        fputcsv($file, $array);
                    }
                    fclose($file);
                };
            }*/
            
            if($type_id == 2){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=products_sku.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                
                $pos_product_list = \DB::table('pos_product_master as ppm')
                ->leftJoin('pos_product_master_inventory as ppmi','ppmi.product_master_id', '=', 'ppm.id') 
                ->join('purchase_order as po','ppmi.po_id', '=', 'po.id')        
                ->where('ppm.is_deleted',0);
                
                $products_list = \DB::table('pos_product_master as ppm')->where('is_deleted',0);
                
                if($is_fake_inventory_user){
                    $pos_product_list = $pos_product_list->where('ppm.fake_inventory',1);
                    $products_list = $products_list->where('ppm.fake_inventory',1);
                }else{
                    $pos_product_list = $pos_product_list->where('ppm.fake_inventory',0);
                    $products_list = $products_list->where('ppm.fake_inventory',1);
                }
                
                if(isset($data['prod_name_search']) && !empty($data['prod_name_search'])){
                    $product_name = trim($data['prod_name_search']);
                    $vendor_sku = (strpos($product_name,'-') !== false)?substr($product_name,0, strrpos($product_name, '-')):$product_name;
                    $pos_product_list = $pos_product_list->whereRaw("(ppm.product_name LIKE '%{$product_name}%' OR ppm.product_barcode = '$product_name' OR ppm.product_sku = '$product_name' OR ppm.product_sku = '$vendor_sku')");
                    
                    $products_list = $products_list->whereRaw("(ppm.product_name LIKE '%{$product_name}%' OR ppm.product_barcode = '$product_name' OR ppm.product_sku = '$product_name' OR ppm.product_sku = '$vendor_sku')");
                }

                if(isset($data['size_search']) && !empty($data['size_search'])){
                    $pos_product_list = $pos_product_list->where('ppm.size_id',$data['size_search']);
                    $products_list = $products_list->where('ppm.size_id',$data['size_search']);
                }

                if(isset($data['color_search']) && !empty($data['color_search'])){
                    $pos_product_list = $pos_product_list->where('ppm.color_id',$data['color_search']);
                    $products_list = $products_list->where('ppm.color_id',$data['color_search']);
                }

                if(isset($data['category_search']) && !empty($data['category_search'])){
                    $pos_product_list = $pos_product_list->where('ppm.category_id',$data['category_search']);
                    $products_list = $products_list->where('ppm.category_id',$data['category_search']);
                }

                if(isset($data['product_subcategory_search']) && !empty($data['product_subcategory_search'])){
                    $pos_product_list = $pos_product_list->where('ppm.subcategory_id',$data['product_subcategory_search']);
                    $products_list = $products_list->where('ppm.subcategory_id',$data['product_subcategory_search']);
                }

                $pos_product_list = $pos_product_list->select('ppm.product_sku','ppmi.base_price','ppmi.sale_price','po.order_no as po_order_no')
                ->groupBy('ppm.product_sku')
                ->orderBy('ppm.id')->get()->toArray();
                
                $pos_product_list = json_decode(json_encode($pos_product_list),true);
                
                $products_list = $products_list->select('product_sku','base_price','sale_price')
                ->get()->toArray();
                
                $products_list = json_decode(json_encode($products_list),true);
                
                for($i=0;$i<count($products_list);$i++){
                    $index = array_search($products_list[$i]['product_sku'], array_column($pos_product_list, 'product_sku'));
                    if($index === false){
                        $array = $products_list[$i];
                        $array['po_order_no'] = '';
                        $pos_product_list[] = $array;
                    }
                }
                
                $columns = array('Product SKU','Base Price','Sale Price','PO No');

                $callback = function() use ($pos_product_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    for($i=0;$i<count($pos_product_list);$i++){
                        $array = array($pos_product_list[$i]['product_sku'],$pos_product_list[$i]['base_price'],$pos_product_list[$i]['sale_price'],trim($pos_product_list[$i]['po_order_no']));
                        fputcsv($file, $array);
                    }
                    fclose($file);
                };
            }
            
            return response()->stream($callback, 200, $headers);
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    function posProductDetail(Request $request,$id){
        try{
            $data = $request->all();
            $product_id = $id;
            $error_message = '';
            
            $product_data = \DB::table('pos_product_master as ppm')
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')
            ->leftJoin('users as u','ppm.user_id', '=', 'u.id')              
            ->leftJoin('pos_product_master_inventory as ppmi',function($join) use ($data){
            $join->on('ppmi.product_master_id','=','ppm.id')->where('ppmi.product_status',1)->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)->where('ppmi.status',1);})             
            ->where('ppm.id',$product_id)
            ->where('ppm.is_deleted',0)
            //->where('ppm.arnon_product',0)
            ->groupBy('ppm.id')        
            ->selectRaw('ppm.*,dlim_1.name as category_name,dlim_2.name as subcategory_name,dlim_3.name as color_name,psc.size as size_name,count(ppmi.id) as inventory_count,u.name as user_name')
            ->first();
            
            $product_images = Pos_product_images::where('product_id',$product_id)->where('is_deleted',0)->orderBy('id')->get()->toArray();
            
            return view('admin/pos_product_detail',array('error_message'=>$error_message,'product_data'=>$product_data,'product_images'=>$product_images));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/pos_product_detail',array('error_message'=>$e->getMessage(),'product_data'=>array()));
        }
    }
    
    public function addStaticPosProduct(Request $request){
        try{
            
            $data = $request->all();
            $uploaded_images = $required_images = array();
            
            $validateionRules = array('static_product_name_add'=>'required','static_product_sku_add'=>'Required','static_product_barcode_add'=>'Required',
            'static_product_category_add'=>'required','static_product_subcategory_add'=>'required','static_size_id_add'=>'required','static_product_color_add'=>'required',
            'static_product_base_price_add'=>'required|numeric','static_product_hsn_code_add'=>'required','static_product_sale_price_add'=>'required|numeric',);
            
            $attributes = array('static_product_name_add'=>'Product Name','static_product_barcode_add'=>'Barcode','static_story_id_add'=>'Story','static_product_base_price_add'=>'Base Price',
            'static_season_id_add'=>'Season','static_product_category_add'=>'Category','static_product_subcategory_add'=>'Subcategory','static_size_id_add'=>'Size','static_product_color_add'=>'Color',
            'static_product_sale_price_add'=>'Sale Price','static_product_hsn_code_add'=>'HSN Code','static_product_sku_add'=>'Product SKU','static_product_barcode_add'=>'Barcode');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $productExists = Pos_product_master::where('product_sku',$data['static_product_sku_add'])->where('color_id',$data['static_product_color_add'])->where('size_id',$data['static_size_id_add'])->where('is_deleted',0)->where('fake_inventory',0)->first();
            if(!empty($productExists)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product with SKU, Color, Size already exists', 'errors' => 'Product with SKU, Color, Size already exists'));
            }
            
            $productExists = Pos_product_master::where('product_barcode',$data['static_product_barcode_add'])->where('is_deleted',0)->where('fake_inventory',0)->first();
            if(!empty($productExists)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product with Barcode already exists', 'errors' => 'Product with Barcode already exists'));
            }
            
            if(stripos(trim($data['static_product_sku_add']), 'k-s-') !== false){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product SKU should not contain K-S-', 'errors' => 'Product SKU should not contain K-S-'));
            }
            
            \DB::beginTransaction();
            
            $product_sku_id = Pos_product_master::where('product_sku',trim($data['static_product_sku_add']))->select('product_sku_id')->first(); 
            if(!empty($product_sku_id)){
                $product_sku_id = $product_sku_id->product_sku_id;
            }else{
                $product_sku_id = Pos_product_master::select('product_sku_id')->orderBy('product_sku_id','DESC')->first(); 
                $product_sku_id = $product_sku_id->product_sku_id+1;
            }
            
            $insertArray = array('product_name'=>trim($data['static_product_name_add']),'custom_product'=>0,'product_sku'=>trim($data['static_product_sku_add']),'product_barcode'=>trim($data['static_product_barcode_add']),
            'story_id'=>$data['static_story_id_add'],'season_id'=>$data['static_season_id_add'],'product_description'=>null,'product_type'=>'sor','sale_price'=>$data['static_product_sale_price_add'],
            'category_id'=>$data['static_product_category_add'],'subcategory_id'=>$data['static_product_subcategory_add'],'size_id'=>$data['static_size_id_add'],'color_id'=>$data['static_product_color_add'],
            'base_price'=>$data['static_product_base_price_add'],'hsn_code'=>$data['static_product_hsn_code_add'],'gst_inclusive'=>1,'vendor_product_sku'=>null,'static_product'=>1,'product_sku_id'=>$product_sku_id);

            $product = Pos_product_master::create($insertArray);
                
            \DB::commit();
            CommonHelper::createLog('POS Product Created. ID: '.$product->id,'POS_PRODUCT_CREATED','POS_PRODUCT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product added successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function updateStaticPosProduct(Request $request,$id){
        try{
            
            $data = $request->all();
            $product_id = $id;
            $uploaded_images = $required_images = array();
            
            $validateionRules = array('static_product_name_edit'=>'required','static_product_sku_edit'=>'Required','static_product_barcode_edit'=>'Required',
            'static_product_category_edit'=>'required','static_product_subcategory_edit'=>'required','static_size_id_edit'=>'required','static_product_color_edit'=>'required',
            'static_product_base_price_edit'=>'required|numeric','static_product_hsn_code_edit'=>'required','static_product_sale_price_edit'=>'required|numeric',);
            
            $attributes = array('static_product_name_edit'=>'Product Name','static_product_barcode_edit'=>'Barcode','static_story_id_edit'=>'Story','static_product_base_price_edit'=>'Base Price',
            'static_season_id_edit'=>'Season','static_product_category_edit'=>'Category','static_product_subcategory_edit'=>'Subcategory','static_size_id_edit'=>'Size','static_product_color_edit'=>'Color',
            'static_product_sale_price_edit'=>'Sale Price','static_product_hsn_code_edit'=>'HSN Code','static_product_sku_edit'=>'Product SKU','static_product_barcode_edit'=>'Barcode');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, "dateTime"=>time(),'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $productExists = Pos_product_master::where('id','!=',$product_id)->where('product_sku',$data['static_product_sku_edit'])->where('color_id',$data['static_product_color_edit'])->where('size_id',$data['static_size_id_edit'])->where('is_deleted',0)->where('fake_inventory',0)->first();
            if(!empty($productExists)){
                return response(array('httpStatus'=>200, "dateTime"=>time(),'status'=>'fail', 'message'=>'Product with SKU, Color, Size already exists', 'errors' => 'Product with SKU, Color, Size already exists'));
            }
            
            $productExists = Pos_product_master::where('id','!=',$product_id)->where('product_barcode',$data['static_product_barcode_edit'])->where('is_deleted',0)->where('fake_inventory',0)->first();
            if(!empty($productExists)){
                return response(array('httpStatus'=>200, "dateTime"=>time(),'status'=>'fail', 'message'=>'Product with Barcode already exists', 'errors' => 'Product with Barcode already exists'));
            }
            
            if(stripos(trim($data['static_product_sku_edit']), 'k-s-') !== false){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product SKU should not contain K-S-', 'errors' => 'Product SKU should not contain K-S-'));
            }
            
            \DB::beginTransaction();
            
            $updateArray = array('product_name'=>trim($data['static_product_name_edit']),'custom_product'=>0,'product_sku'=>trim($data['static_product_sku_edit']),'product_barcode'=>trim($data['static_product_barcode_edit']),
            'story_id'=>$data['static_story_id_edit'],'season_id'=>$data['static_season_id_edit'],'product_description'=>null,'product_type'=>'sor','sale_price'=>$data['static_product_sale_price_edit'],
            'category_id'=>$data['static_product_category_edit'],'subcategory_id'=>$data['static_product_subcategory_edit'],'size_id'=>$data['static_size_id_edit'],'color_id'=>$data['static_product_color_edit'],
            'base_price'=>$data['static_product_base_price_edit'],'hsn_code'=>$data['static_product_hsn_code_edit'],'gst_inclusive'=>1,'vendor_product_sku'=>null,'static_product'=>1);
            
            unset($updateArray['product_sku']);
            $product = Pos_product_master::where('id',$product_id)->update($updateArray);
                
            \DB::commit();
            CommonHelper::createLog('POS Product Updated. ID: '.$product_id,'POS_PRODUCT_CREATED','POS_PRODUCT');
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Product updated successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
    }
    
    public function importStaticPosProduct(Request $request){
        try{
            $data = $request->all();
            $error_message = '';
            
            return view('admin/import_static_pos_product',array('error_message'=>$error_message));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STATIC_POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/import_static_pos_product',array('error_message'=>$e->getMessage().' '.$e->getLine()));
        }  
    }
    
    
    public function submitImportStaticPosProduct(Request $request){
        try{
            set_time_limit(900);
            $data = $request->all();
            $user = Auth::user();
            
            $products_list  = $errors_list = $sku_size_color_array = $barcode_array = $category_list = $color_list = $season_list = $subcategory_list = [];
            $size_id_list = $story_id_list = $category_id_list = $color_id_list = $season_id_list = $subcategory_id_list = $category_hsn_codes = [];
            
            // CSV File Validation
            $validateionRules = array('importStaticProductsCsvFile'=>'required|mimes:csv,txt|max:3072');
            $attributes = array('importStaticProductsCsvFile'=>'CSV File');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            // Upload and Move CSV File
            $file = $request->file('importStaticProductsCsvFile');
            $file_name_text = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(),'.'));
            $file_ext = $file->getClientOriginalExtension();
            $dest_folder = 'documents/static_product_import_csv';

            for($i=0;$i<1000;$i++){
                $file_name = ($i == 0)?$file_name_text.'.'.$file_ext:$file_name_text.'_'.$i.'.'.$file_ext;
                if(!file_exists(public_path($dest_folder.'/'.$file_name))){
                    break;
                }
            }

            $file->move(public_path($dest_folder), $file_name);
            
            $file = public_path($dest_folder.'/'.$file_name);
            if(($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $name = isset($data[0])?trim($data[0]):'';
                    $sku = isset($data[1])?trim($data[1]):'';
                    $barcode = isset($data[2])?trim($data[2]):'';
                    $color_id = isset($data[3])?trim($data[3]):'';
                    $size_id = isset($data[4])?trim($data[4]):'';
                    $category_id = isset($data[5])?trim($data[5]):'';
                    $subcategory_id = isset($data[6])?trim($data[6]):'';
                    $story_id = isset($data[7])?trim($data[7]):'';
                    $season_id = isset($data[8])?trim($data[8]):'';
                    $base_price = isset($data[9])?trim($data[9]):'';
                    $sale_price = isset($data[10])?trim($data[10]):'';
                    
                    $products_list[] = ['name'=>$name,'sku'=>$sku,'barcode'=>$barcode,'color_id'=>$color_id,'size_id'=>$size_id,'category_id'=>$category_id,'subcategory_id'=>$subcategory_id,
                    'story_id'=>$story_id,'season_id'=>$season_id,'base_price'=>$base_price,'sale_price'=>$sale_price];
                }
            }
            
            if(count($products_list) > 100){
                fclose($handle);
                unlink($file);
                return response(array('httpStatus'=>200, "dateTime"=>time(),'status'=>'fail', 'message'=>'Maximum 100 Products can be Imported', 'errors' => 'Maximum 100 Products can be Imported'));
            }
            
            // Array key names
            $product_keys = !empty($products_list)?array_keys($products_list[0]):[];
            $product_numeric_keys = ['color_id','size_id','category_id','subcategory_id','story_id','season_id','base_price','sale_price'];
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $story_list = Story_master::where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','POS_PRODUCT_SUBCATEGORY','COLOR','SEASON'))->where('is_deleted',0)->where('status',1)->orderBy('name','ASC')->get()->toArray();
            $category_hsn_codes_list = Category_hsn_code::where('is_deleted',0)->get()->toArray();
            
            for($i=0;$i<count($category_hsn_codes_list);$i++){
                $category_hsn_codes[$category_hsn_codes_list[$i]['category_id']] = $category_hsn_codes_list[$i];
            }
            
            for($i=0;$i<count($design_lookup_items);$i++){
                if(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_CATEGORY'){
                    $category_list[] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_SUBCATEGORY'){
                    $pid = $design_lookup_items[$i]['pid'];
                    $subcategory_list[$pid][] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'COLOR'){
                    $color_list[] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'SEASON'){
                    $season_list[] = $design_lookup_items[$i];
                }
            }
            
            // IDs Array
            $size_id_list = array_column($size_list, 'id');
            $story_id_list = array_column($story_list, 'id');
            $category_id_list = array_column($category_list, 'id');
            $color_id_list = array_column($color_list, 'id');
            $season_id_list = array_column($season_list, 'id');
            
            foreach($subcategory_list as $cat_id=>$subcat_list){
                $subcategory_id_list[$cat_id] = array_column($subcat_list,'id');
            }
            
            for($i=0;$i<count($products_list);$i++){
                $errors = [];
                $sku_size_color = '';
                $product = $products_list[$i];
                
                // Required Fields Validation 
                for($q=0;$q<count($product_keys);$q++){
                    $key = $product_keys[$q];
                    if(empty($product[$key])){
                        $errors[] = $key.' is Required Field';
                    }
                }
                
                // Numeric Fields Validation
                for($q=0;$q<count($product_numeric_keys);$q++){
                    $key = $product_numeric_keys[$q];
                    if(!empty($product[$key]) && !is_numeric($product[$key])){
                        $errors[] = $key.' should have numeric value';
                    }
                    
                    if(!empty($product[$key]) && is_numeric($product[$key]) && $product[$key] <= 0){
                        $errors[] = $key.' should be greater than zero';
                    }
                    
                    if(!empty($product[$key]) && is_numeric($product[$key]) && strlen($product[$key]) > 6){
                        $errors[] = 'Invalid '.$key;
                    }
                }
                
                if(!empty($product['name']) && strlen($product['name']) > 200){
                    $errors[] = 'Product Name should not have more than 200 characters';
                }
                
                // SKU Length and Format Validation
                if(!empty($product['sku'])){
                    if(strlen($product['sku']) > 50){
                        $errors[] = 'Product SKU should not have more than 50 characters';
                    }
                    
                    if(!preg_match('/^[a-zA-Z0-9_\-]+$/',$product['sku'])){
                        $errors[] = 'Invalid Product SKU Format';
                    }
                }
                
                // Barcode Length and Format Validation
                if(!empty($product['barcode'])){
                    if(strlen($product['barcode']) > 15){
                        $errors[] = 'Product Barcode should not have more than 15 characters';
                    }
                    
                    if(!preg_match('/^[a-zA-Z0-9]+$/',$product['barcode'])){
                        $errors[] = 'Invalid Product Barcode Format';
                    }
                }
                
                // Check SKU, Size and Color in Database
                if(!empty($product['sku']) && !empty($product['size_id']) && !empty($product['color_id'])){
                    $productExists = Pos_product_master::where('product_sku',$product['sku'])->where('color_id',$product['color_id'])->where('size_id',$product['size_id'])->where('is_deleted',0)->where('fake_inventory',0)->first();
                    if(!empty($productExists)){
                        $errors[] = 'Product with SKU, Size, Color already exists in database';
                    }
                    
                    $sku_size_color = trim(strtolower($product['sku'])).'-'.$product['size_id'].'-'.$product['color_id'];
                }
                
                // Check Barcode in Database
                if(!empty($product['barcode'])){
                    $productExists = Pos_product_master::where('product_barcode',$product['barcode'])->where('is_deleted',0)->where('fake_inventory',0)->first();
                    if(!empty($productExists)){
                        $errors[] = 'Product with Barcode already exists in database';
                    }
                }

                if(stripos(trim($product['sku']), 'k-s-') !== false){
                    $errors[] = 'Product SKU should not contain K-S-';
                }
                
                // Duplicate Data within CSV File
                if(in_array($sku_size_color,$sku_size_color_array)){
                    $errors[] = 'Product with Duplicate SKU, Color Id, Size Id in CSV File'; 
                }
                
                // Duplicate Barcode within CSV File
                if(!empty($product['barcode']) && in_array($product['barcode'],$barcode_array)){
                    $errors[] = 'Product with Duplicate Barcode in CSV File';
                }
                
                if(!empty($product['color_id']) && !in_array($product['color_id'],$color_id_list)){
                    $errors[] = 'Color ID does not exists in database';
                }
                
                if(!empty($product['size_id']) && !in_array($product['size_id'],$size_id_list)){
                    $errors[] = 'Size ID does not exists in database';
                }
                
                if(!empty($product['category_id']) && !in_array($product['category_id'],$category_id_list)){
                    $errors[] = 'Category ID does not exists in database';
                }
                
                if(!empty($product['category_id']) && !isset($category_hsn_codes[$product['category_id']]) ){
                    $errors[] = 'HSN Code does not exists for Category ID';
                }
                
                if(!empty($product['category_id']) && !empty($product['subcategory_id']) && isset($subcategory_id_list[$product['category_id']]) && !in_array($product['subcategory_id'],$subcategory_id_list[$product['category_id']]) ){
                    $errors[] = 'SubCategory ID does not exists in Category';
                }
                
                if(!empty($product['story_id']) && !in_array($product['story_id'],$story_id_list)){
                    $errors[] = 'Story ID does not exists in database';
                }
                
                if(!empty($product['season_id']) && !in_array($product['season_id'],$season_id_list)){
                    $errors[] = 'Season ID does not exists in database';
                }
                
                if(!empty($errors)){
                    $errors_list[] = ['product'=>$product,'errors'=>$errors,'row'=>$i+1];
                }
                
                if(!empty($sku_size_color)){
                    $sku_size_color_array[] = $sku_size_color;
                }
                
                if(!empty($product['barcode'])){
                    $barcode_array[] = $product['barcode'];
                }
            }
            
            if(!empty($errors_list)){
                fclose($handle);
                unlink($file);
                return response(array('httpStatus'=>200, "dateTime"=>time(),'status'=>'fail', 'message'=>'Please Correct Following Errors', 'errors' => 'Please Correct Following Errors','errors_list'=>$errors_list));
            }
            
            // Insert Code
            \DB::beginTransaction();
            
            for($i=0;$i<count($products_list);$i++){
                
                $product = $products_list[$i];
                
                $product_sku_id = Pos_product_master::where('product_sku',trim($product['sku']))->select('product_sku_id')->first(); 
                if(!empty($product_sku_id)){
                    $product_sku_id = $product_sku_id->product_sku_id;
                }else{
                    $product_sku_id = Pos_product_master::select('product_sku_id')->orderBy('product_sku_id','DESC')->first(); 
                    $product_sku_id = $product_sku_id->product_sku_id+1;
                }
                
                $hsn_code = $category_hsn_codes[$product['category_id']]['hsn_code'];

                $insertArray = array('product_name'=>trim($product['name']),'custom_product'=>0,'product_sku'=>trim($product['sku']),'product_barcode'=>trim($product['barcode']),
                'story_id'=>$product['story_id'],'season_id'=>$product['season_id'],'product_description'=>null,'product_type'=>'sor','sale_price'=>$product['sale_price'],
                'category_id'=>$product['category_id'],'subcategory_id'=>$product['subcategory_id'],'size_id'=>$product['size_id'],'color_id'=>$product['color_id'],
                'base_price'=>$product['base_price'],'hsn_code'=>$hsn_code,'gst_inclusive'=>1,'vendor_product_sku'=>null,'static_product'=>1,'product_sku_id'=>$product_sku_id,'user_id'=>$user->id);
                
                $product_added = Pos_product_master::create($insertArray);
            }
                
            \DB::commit();
            CommonHelper::createLog(count($products_list).' POS Product Imported by CSV ','POS_PRODUCT_CREATED','POS_PRODUCT');
            
            fclose($handle);
            unlink($file);
                
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Products imported successfully','status' => 'success'),200);
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }  
    }
    
    public function listPosProductInventory(Request $request){
        try{
            ini_set('memory_limit', '-1');
            //set_time_limit(60);
            
            $data = $request->all();            
            $user = Auth::user();
            $error_message = $start_date = $end_date = '';
            $category_list = $color_list = $season_list = $size_list = $store_data = array();
            $category_id_list = $color_id_list = $subcategory_id_list = $store_id_list = $size_id_list = $po_id_list = array();
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            
            if(isset($data['action']) && $data['action'] == 'edit_inventory'){
                $validationRules = array('po_id_edit_inv'=>'required','sku_edit_inv'=>'required','mrp_edit_inv'=>'required|numeric');
                $attributes = array('po_id_edit_inv'=>'Purchase Order','sku_edit_inv'=>'SKU','mrp_edit_inv'=>'MRP');

                $validator = Validator::make($data,$validationRules,array(),$attributes);
                if($validator->fails()){ 
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
                }	
                
                $po_id = trim($data['po_id_edit_inv']);
                $sku = trim($data['sku_edit_inv']);
                $mrp = trim($data['mrp_edit_inv']);
                
                $po_sku = Purchase_order_items::where('order_id',$po_id)->where('product_sku',$sku)->where('is_deleted',0)->where('fake_inventory',0)->first();
                
                if(empty($po_sku)){ 
                    return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'SKU does not exists in Purchase order', 'errors' =>'SKU does not exists in Purchase order' ));
                }	
                
                $sql = "SELECT id from pos_product_master WHERE product_sku = '$sku' AND is_deleted = 0 AND fake_inventory = 0";
                $updateArray = array('sale_price'=>$mrp);
                Pos_product_master_inventory::where('po_id',$po_id)->whereRaw("product_master_id IN($sql)")->wherein('product_status',[1,4])->where('is_deleted',0)->where('fake_inventory',0)->update($updateArray);;
                
                return response(array('httpStatus'=>200,'dateTime'=>time(),'status'=>'success','message' => 'Inventory updated Successfully'),200);
            }
            
            $products_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('purchase_order as po','ppmi.po_id', '=', 'po.id')                 
            //->leftJoin('store as s','s.id', '=', 'ppmi.store_id')        
            //->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            //->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
            //->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            //->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            //->leftJoin('purchase_order as po','ppmi.po_id', '=', 'po.id')     
            ->leftJoin('store_products_demand as spd','ppmi.demand_id', '=', 'spd.id')                 
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1);
            
            if($user->user_type == 9){
                $store_data = CommonHelper::getUserStoreData($user->id);
                /*$products_list = $products_list->where('ppmi.store_id',$store_data->id)->where('ppmi.product_status',4);*/
                $products_list = $products_list->where('ppmi.product_status',4);
            }
            
            if($user->user_type == 15){
                $vendor_data = Vendor_detail::where('user_id',$user->id)->where('is_deleted',0)->first();
                $products_list = $products_list->where('ppmi.vendor_id',$vendor_data->id);
            }
            
            if($is_fake_inventory_user){
                $products_list = $products_list->where('ppmi.fake_inventory',1);
                $products_list = $products_list->where('ppm.fake_inventory',1);
            }else{
                $products_list = $products_list->where('ppmi.fake_inventory',0);
                $products_list = $products_list->where('ppm.fake_inventory',0);
            }
            
            if(isset($data['inv_type']) && !empty($data['inv_type'])){
                if($data['inv_type'] == 1)
                    $products_list = $products_list->where('ppmi.arnon_inventory',0);
                if($data['inv_type'] == 2)
                    $products_list = $products_list->where('ppmi.arnon_inventory',1);
            }
           
            if(isset($data['store_id']) && !empty($data['store_id'])){
                $products_list = $products_list->where('ppmi.store_id',trim($data['store_id']));
            }
            
            if(isset($data['status']) && ($data['status']) != ''){
                $products_list = $products_list->where('ppmi.product_status',trim($data['status']));
            }
            
            if(isset($data['qc_status']) && ($data['qc_status']) != ''){
                $products_list = $products_list->where('ppmi.qc_status',trim($data['qc_status']));
            }
            if(isset($data['prod_name_search']) && !empty($data['prod_name_search'])){
                $product_name = trim($data['prod_name_search']);
                $vendor_sku = (strpos($product_name,'-') !== false)?substr($product_name,0, strrpos($product_name, '-')):$product_name;
                $products_list = $products_list->whereRaw("(ppm.product_name LIKE '%{$product_name}%' OR ppm.product_barcode = '$product_name' OR ppmi.peice_barcode = '$product_name' OR ppm.product_sku = '$product_name' OR ppm.product_sku = '$vendor_sku')");
            }
            
            if(isset($data['size_search']) && !empty($data['size_search'])){
                $products_list = $products_list->where('ppm.size_id',$data['size_search']);
            }
            
            if(isset($data['color_search']) && !empty($data['color_search'])){
                $products_list = $products_list->where('ppm.color_id',$data['color_search']);
            }
            
            if(isset($data['category_search']) && !empty($data['category_search'])){
                $products_list = $products_list->where('ppm.category_id',$data['category_search']);
            }
            
            if(isset($data['product_subcategory_search']) && !empty($data['product_subcategory_search'])){
                $products_list = $products_list->where('ppm.subcategory_id',$data['product_subcategory_search']);
            }
            
            if(isset($data['po_search']) && !empty($data['po_search'])){
                $po = trim($data['po_search']);
                $po_data = Purchase_order::where('order_no',$po)->first();
                if(!empty($po_data)) $po = $po_data->id;
                $products_list = $products_list->whereRaw("(ppmi.po_id = '$po')");
            }
            
            if(isset($data['mrp_search']) && !empty($data['mrp_search'])){
                $products_list = $products_list->where('ppmi.sale_price',$data['mrp_search']);
            }
            
            if(isset($data['demand_search']) && !empty($data['demand_search'])){
                $products_list = $products_list->where('spd.invoice_no',trim($data['demand_search']));
            }
            
            if(isset($data['v_id']) && !empty($data['v_id'])){
                $products_list = $products_list->where('po.vendor_id',trim($data['v_id']));
            }
            
            if(isset($data['inv_id']) && !empty($data['inv_id'])){
                $products_list = $products_list->where('ppmi.id',trim($data['inv_id']));
            }
            
            if(isset($data['payment_status']) && $data['payment_status'] != ''){
                $products_list = $products_list->where('ppmi.payment_status',trim($data['payment_status']));
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            if(!empty($search_date['start_date']) && !empty($search_date['start_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            if(!empty($start_date) && !empty($end_date)){
                $products_list = $products_list->whereRaw("DATE(ppmi.created_at) >= '$start_date' AND DATE(ppmi.created_at) <= '$end_date'");     
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'ppmi.id','product_id'=>'ppm.id','product_name'=>'ppm.product_name','store'=>'s.store_name','piece_barcode'=>'ppmi.peice_barcode',
                'product_barcode'=>'ppm.product_barcode','sku'=>'ppm.product_sku','category'=>'category_name','subcategory'=>'subcategory_name','base_price'=>'ppmi.base_price',
                'sale_price'=>'ppmi.sale_price','status'=>'ppmi.product_status','qc_status'=>'ppmi.qc_status','po'=>'po.order_no','payment_status'=>'ppmi.payment_status');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'ppmi.id';
                $products_list = $products_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }elseif(isset($data['sku_size_sort']) && $data['sku_size_sort'] == 1){
                $products_list = $products_list->orderByRaw('ppm.product_sku, ppm.size_id');
            }else{
                $products_list = $products_list->orderBy('id','ASC');
            }

            $products_list = $products_list->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku',
            'ppm.color_id','ppm.size_id','ppm.category_id','ppm.subcategory_id');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $inv_count = trim($data['inv_count']);
                $inv_count_arr = explode('_',$inv_count);
                $start = $inv_count_arr[0];
                $start = $start-1;
                $end = $inv_count_arr[1];
                $limit = $end-$start;
                $products_list = $products_list->offset($start)->limit($limit)->get();
            }else{
                $products_list_count = clone ($products_list);
                $products_list_count = $products_list_count->count();
                $products_list = $products_list->paginate(100);
            }
            
            $store_list = CommonHelper::getStoresList();
            $status_list = CommonHelper::getposProductStatusList();
            $size_list = Production_size_counts::get()->toArray();
            
            $vendor_list = Vendor_detail::where('is_deleted',0);
            if($user->user_type == 15){
                $vendor_list = $vendor_list->where('id',$vendor_data->id); 
            }
            
            $vendor_list = $vendor_list->get()->toArray(); 
            
            if($is_fake_inventory_user){
                $po_list = Purchase_order::where('fake_inventory',1)->orderBy('id')->get()->toArray();
            }else{
                $po_list = Purchase_order::where('fake_inventory',0)->orderBy('id')->get()->toArray();
            }
            
            $design_lookup_items = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY','COLOR','SEASON','POS_PRODUCT_SUBCATEGORY'))->orderBy('name')->get()->toArray();
            
            for($i=0;$i<count($design_lookup_items);$i++){
                if(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_CATEGORY'){
                    $category_list[] = $design_lookup_items[$i];
                    $category_id_list[$design_lookup_items[$i]['id']] = $design_lookup_items[$i]['name'];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'COLOR'){
                    $color_list[] = $design_lookup_items[$i];
                    $color_id_list[$design_lookup_items[$i]['id']] = $design_lookup_items[$i]['name'];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'SEASON'){
                    $season_list[] = $design_lookup_items[$i];
                }elseif(strtoupper($design_lookup_items[$i]['type']) == 'POS_PRODUCT_SUBCATEGORY'){
                    $subcategory_id_list[$design_lookup_items[$i]['id']] = $design_lookup_items[$i]['name'];
                }
            }
            
            for($i=0;$i<count($store_list);$i++){
                $store_id_list[$store_list[$i]['id']] = $store_list[$i];
            }
            
            for($i=0;$i<count($size_list);$i++){
                $size_id_list[$size_list[$i]['id']] = $size_list[$i]['size'];
            }
            
            for($i=0;$i<count($po_list);$i++){
                $po_id_list[$po_list[$i]['id']] = $po_list[$i]['order_no'];
            }
            
            // Download CSV Start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=inventory_list.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Inv ID','Product Name','Store Name','Store Code','Peice Barcode','Product Barcode','SKU','PO No','Category','Subcategory','Base Price','MRP','Status','QC','Payment Status');

                $callback = function() use ($products_list,$category_id_list, $color_id_list, $subcategory_id_list, $store_id_list, $size_id_list, $po_id_list, $columns){
                    $file = fopen('php://output', 'w');
                    
                    fputcsv($file, $columns);
                    $total_data = array('units'=>0);
                    
                    for($i=0;$i<count($products_list);$i++){
                        $store_name = isset($store_id_list[$products_list[$i]->store_id]['store_name'])?$store_id_list[$products_list[$i]->store_id]['store_name']:'';
                        $store_code = isset($store_id_list[$products_list[$i]->store_id]['store_id_code'])?$store_id_list[$products_list[$i]->store_id]['store_id_code']:'';
                        $po_no = isset($po_id_list[$products_list[$i]->po_id])?$po_id_list[$products_list[$i]->po_id]:'';
                        $category_name = isset($category_id_list[$products_list[$i]->category_id])?$category_id_list[$products_list[$i]->category_id]:'';
                        $subcategory_name = isset($subcategory_id_list[$products_list[$i]->subcategory_id])?$subcategory_id_list[$products_list[$i]->subcategory_id]:'';
                        
                        $array = array();
                        $array[] = $products_list[$i]->id;
                        $array[] = $products_list[$i]->product_name.' '.$size_id_list[$products_list[$i]->size_id].' '.$color_id_list[$products_list[$i]->color_id];
                        $array[] = $store_name;
                        $array[] = $store_code;
                        $array[] = CommonHelper::filterCsvInteger($products_list[$i]->peice_barcode);
                        $array[] = CommonHelper::filterCsvInteger($products_list[$i]->product_barcode);
                        $array[] = $products_list[$i]->product_sku;
                        $array[] = $po_no;
                        $array[] = $category_name;
                        $array[] = $subcategory_name;
                        $array[] = $products_list[$i]->base_price;
                        $array[] = $products_list[$i]->sale_price;
                        $array[] = ($products_list[$i]->product_status == 0)?'WAREHOUSE IN PENDING':strtoupper(CommonHelper::getposProductStatusName($products_list[$i]->product_status));
                        $array[] = ($products_list[$i]->product_status != 0)?strtoupper(CommonHelper::getProductInventoryQCStatusName($products_list[$i]->qc_status)):'';
                        $array[] = CommonHelper::getInventoryPaymentStatusText($products_list[$i]->payment_status);
                        fputcsv($file, $array);
                        
                        $total_data['units']+=$i;
                    }

                    $array = array('Total Inventory',count($products_list));
                    
                    fputcsv($file, $array);

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV End
            
            return view('admin/pos_products_inventory_list',array('error_message'=>$error_message,'products_list'=>$products_list,'store_list'=>$store_list,'status_list'=>$status_list,
            'user'=>$user,'size_list'=>$size_list,'color_list'=>$color_list,'user'=>$user,'season_list'=>$season_list,'category_list'=>$category_list,'po_list'=>$po_list,
            'products_list_count'=>$products_list_count,'category_id_list'=>$category_id_list,'subcategory_id_list'=>$subcategory_id_list,'color_id_list'=>$color_id_list,
            'store_id_list'=>$store_id_list,'size_id_list'=>$size_id_list,'po_id_list'=>$po_id_list,'vendor_list'=>$vendor_list,'store_data'=>$store_data));
            
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'POS',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().' '.$e->getLine()),500);
            }else{
                return view('admin/pos_products_inventory_list',array('error_message'=>$e->getMessage().' '.$e->getLine()));
            }
        }  
    }
    
    public function listPosProductDuplicateInventory(Request $request){
        try{
           $data = $request->all();          
           $error_message = '';
           $qr_codes = $inventory_list = array();
           
            /*$inv_list = \DB::table('pos_product_master_inventory as ppmi')
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->selectRaw('peice_barcode,COUNT(id) as cnt')     
            ->groupBy('peice_barcode')    
            ->having('cnt','>','1')        
            ->orderBy('cnt','DESC')        
            ->get()->toArray();
            
            for($i=0;$i<count($inv_list);$i++){
                $qr_codes[] = $inv_list[$i]->peice_barcode;
            }
            
            $inventory_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('store as s','s.id', '=', 'ppmi.store_id')        
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->leftJoin('purchase_order as po','ppmi.po_id', '=', 'po.id')     
            ->leftJoin('store_products_demand as spd','ppmi.demand_id', '=', 'spd.id')     
            ->wherein('ppmi.peice_barcode',$qr_codes)        
            ->where('ppmi.is_deleted',0)
            ->where('ppmi.status',1)
            ->where('ppmi.fake_inventory',0)        
            ->select('ppmi.*','ppm.product_name','ppm.product_sku','ppm.product_barcode','s.store_name','psc.size as size_name','po.order_no as po_no','dlim_1.name as cat_name','dlim_2.name as subcat_name','dlim_3.name as color_name')        
            ->orderBy('ppmi.peice_barcode')        
            ->get()->toArray();        */
            
            return view('admin/pos_products_duplicate_inventory_list',array('error_message'=>$error_message,'inventory_list'=>$inventory_list));
            
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'POS',__FUNCTION__,__FILE__);
            
            if(isset($data['action']) && !empty($data['action'])){
                return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().' '.$e->getLine()),500);
            }else{
                return view('admin/pos_products_duplicate_inventory_list',array('error_message'=>$e->getMessage().' '.$e->getLine()));
            }
        }  
    }
    
    public function posProductInventoryDetail(Request $request,$id){
        try{
            $data = $request->all();            
            $user = Auth::user();
            $error_message = '';
            $inventory_demands = $pos_orders = array();
            $inventory_id = $id;
            
            $inv_data = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
            ->leftJoin('store_products_demand as spd','spd.id', '=', 'ppmi.demand_id')    
            ->leftJoin('store as s1','s1.id', '=', 'ppmi.store_id')         
            ->leftJoin('store as s2','s2.id', '=', 'spd.store_id')   
            ->leftJoin('store as s3','s3.id', '=', 'spd.from_store_id')         
            ->leftJoin('pos_customer_orders as pco',function($join){$join->on('pco.id', '=', 'ppmi.customer_order_id')->where('pco.order_status','=','1')->where('pco.is_deleted','=','0');})          
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')                 
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
            ->leftJoin('purchase_order as po','ppmi.po_id', '=', 'po.id')   
            ->leftJoin('vendor_detail as vd','vd.id', '=', 'po.vendor_id')        
            ->leftJoin('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
            ->leftJoin('purchase_order_grn_qc as grn','ppmi.grn_id', '=', 'grn.id')
            ->leftJoin('purchase_order_details as pod','ppmi.po_detail_id', '=', 'pod.id')        
            ->where('ppmi.id',$inventory_id)
            ->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','dlim_1.name as category_name','ppm.product_barcode',
            'dlim_2.name as subcategory_name','s1.store_name','s1.store_id_code','dlim_3.name as color_name','psc.size as size_name','po.order_no as po_order_no',
            'poi.vendor_sku','grn.grn_no','pod.invoice_no as wh_invoice_no','ppm.vendor_product_sku','grn.created_at as grn_date','ppm.hsn_code','ppm.arnon_product',
            'spd.invoice_no as demand_invoice_no','spd.demand_type','s2.store_name as demand_store_1_name','pco.order_no as pos_order_no','vd.name as vendor_name',
            's3.store_name as demand_store_2_name')        
            ->first();        
            
            $demand_route_data = CommonHelper::getInventoryDemandRoute($inv_data);
            $inv_data->demand_route = $demand_route_data['demand_route'];
            $inv_data->demand_url = $demand_route_data['demand_url'];
            
            // Inventory Push Demand data. Product is pushed to store
            $demands_list = \DB::table('store_products_demand as spd')
            ->join('store_products_demand_inventory as spdi','spd.id', '=', 'spdi.demand_id')
            ->join('store as s1','s1.id', '=', 'spd.store_id')      
            ->leftJoin('store as s2','s2.id', '=', 'spd.from_store_id')      
            ->where('spdi.inventory_id',$inventory_id)         
            ->where('spd.is_deleted',0)
            ->where('spdi.is_deleted',0)       
            ->where('spdi.demand_status',1)        
            ->orderByRaw('spd.id ASC')
            ->select('spd.*','s1.store_name as demand_store_1_name','spdi.transfer_status','spdi.receive_status','spdi.receive_status','spdi.vendor_base_price',
            'spdi.vendor_gst_percent','spdi.vendor_gst_amount','spdi.base_price','spdi.sale_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','spdi.store_base_price',
            'spdi.transfer_date','spdi.receive_date','s2.store_name as demand_store_2_name');        
                    
            $wh_to_store_push_demand_list = clone $demands_list;
            $store_to_store_demand_list = clone $demands_list;
            $inv_return_complete_demand_list = clone $demands_list;
            $wh_to_vendor_demand_list = clone $demands_list;
            $store_to_wh_demand_list = clone $demands_list;
            
            $wh_to_store_push_demand_list = $wh_to_store_push_demand_list->where('spd.demand_type','inventory_push')
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))        
            ->get()->toArray();        
            
            $store_to_store_demand_list = $store_to_store_demand_list->where('spd.demand_type','inventory_transfer_to_store')
            ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))        
            ->get()->toArray();    
            
            $inv_return_complete_demand_list = $inv_return_complete_demand_list->where('spd.demand_type','inventory_return_complete')
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loaded'))        
            ->get()->toArray();        
            
            $wh_to_vendor_demand_list = $wh_to_vendor_demand_list->where('spd.demand_type','inventory_return_to_vendor')
            ->wherein('spd.demand_status',array('warehouse_dispatched'))        
            ->get()->toArray();   
            
            $store_to_wh_demand_list = $store_to_wh_demand_list->where('spd.demand_type','inventory_return_to_warehouse')
            ->wherein('spd.demand_status',array('warehouse_dispatched','warehouse_loading','warehouse_loaded'))        
            ->get()->toArray();   
            
            for($i=0;$i<count($wh_to_store_push_demand_list);$i++){
                $timestamp = strtotime($wh_to_store_push_demand_list[$i]->created_at);
                $inventory_demands[$timestamp] = $wh_to_store_push_demand_list[$i];
            }
            
            for($i=0;$i<count($store_to_store_demand_list);$i++){
                $timestamp = strtotime($store_to_store_demand_list[$i]->created_at);
                $inventory_demands[$timestamp] = $store_to_store_demand_list[$i];
            }
            
            for($i=0;$i<count($inv_return_complete_demand_list);$i++){
                $timestamp = strtotime($inv_return_complete_demand_list[$i]->created_at);
                $inventory_demands[$timestamp] = $inv_return_complete_demand_list[$i];
            }
            
            for($i=0;$i<count($wh_to_vendor_demand_list);$i++){
                $timestamp = strtotime($wh_to_vendor_demand_list[$i]->created_at);
                $inventory_demands[$timestamp] = $wh_to_vendor_demand_list[$i];
            }
            
            for($i=0;$i<count($store_to_wh_demand_list);$i++){
                $timestamp = strtotime($store_to_wh_demand_list[$i]->created_at);
                $inventory_demands[$timestamp] = $store_to_wh_demand_list[$i];
            }
            
            ksort($inventory_demands);//print_r($inventory_demands);
            
            $pos_orders_list = \DB::table('pos_customer_orders as pco')
            ->join('pos_customer_orders_detail as pcod','pco.id', '=', 'pcod.order_id')
            ->leftJoin('store as s','s.id', '=', 'pco.store_id')                        
            ->where('pcod.inventory_id',$inventory_id)    
            ->where('pco.is_deleted',0)
            ->where('pcod.is_deleted',0)     
            ->where('pco.order_status',1)
            ->where('pcod.order_status',1)        
            ->orderByRaw('pco.id ASC')
            ->select('pco.order_no','pcod.*','s.store_name','s.store_id_code','pco.foc')        
            ->get()->toArray();
            
            for($i=0;$i<count($pos_orders_list);$i++){
                $timestamp = strtotime($pos_orders_list[$i]->created_at);
                $pos_orders[$timestamp] = $pos_orders_list[$i];
            }
            
            ksort($pos_orders);
            
            $grn_qc_list = \DB::table('purchase_order_grn_qc as po_grn_qc')
            ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc.id', '=', 'po_grn_qc_items.grn_qc_id')
            ->join('purchase_order_details as pod','pod.id', '=', 'po_grn_qc.po_detail_id')        
            ->where('po_grn_qc_items.inventory_id',$inventory_id)                
            ->where('po_grn_qc.is_deleted',0)
            ->where('po_grn_qc_items.is_deleted',0)        
            ->orderBy('po_grn_qc.id')
            ->select('po_grn_qc.*','pod.invoice_no','po_grn_qc_items.qc_status')        
            ->get()->toArray();
            
            return view('admin/pos_products_inventory_detail',array('error_message'=>$error_message,'inv_data'=>$inv_data,'inventory_demands'=>$inventory_demands,'pos_orders'=>$pos_orders,'grn_qc_list'=>$grn_qc_list));
            
        }catch (\Exception $e){		
            CommonHelper::saveException($e,'POS',__FUNCTION__,__FILE__);
            return view('admin/pos_products_inventory_detail',array('error_message'=>$e->getMessage()));
        }  
    }
    
    function listPosProductInventoryBarcodes(Request $request){
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            $products_list = array();
            $company_data = CommonHelper::getCompanyData();
            $id_array = (isset($data['id_str']) && !empty($data['id_str']))?explode(',', $data['id_str']):array();
            
            $type = (isset($data['type']))?$data['type']:'barcodes';
            
            if(!empty($id_array)){
                $products_list = \DB::table('pos_product_master_inventory as ppmi')
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
                ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')   
                //->leftJoin('purchase_order_items as poi',function($join){$join->on('ppm.product_sku', '=', 'poi.product_sku')->on('poi.order_id','=','ppmi.po_id');})         
                ->leftJoin('purchase_order_items as poi','ppmi.po_item_id', '=', 'poi.id')        
                ->whereIn('ppmi.id',$id_array)        
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.status',1)
                ->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','poi.vendor_sku')
                ->limit(100)->get()->toArray();
            }
            
            if($type == 'barcodes')
                return view('admin/pos_products_inventory_barcodes_list',array('error_message'=>$error_message,'products_list'=>$products_list,'company_data'=>$company_data));
            if($type == 'qrcodes')
                return view('admin/pos_products_inventory_qrcodes_list',array('error_message'=>$error_message,'products_list'=>$products_list,'company_data'=>$company_data));
            if($type == 'barqrcodes')
                return view('admin/pos_products_inventory_barcodes_qr_list',array('error_message'=>$error_message,'products_list'=>$products_list,'company_data'=>$company_data));
            if($type == 'jewelleryqrcodes')
                return view('admin/pos_products_inventory_jewellery_barcodes_qr_list',array('error_message'=>$error_message,'products_list'=>$products_list));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/pos_products_inventory_barcodes_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function storeToCustomerSalesReport(Request $request){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $error_message = '';
            $payment_data = $gst_data = $quantity_data = $voucher_data = array();
            
            $report_type = (isset($data['report_type']) && !empty($data['report_type']))?$data['report_type']:'bill';
            $search_date = CommonHelper::getSearchStartEndDate($data,true,'-7 days');
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                if(CommonHelper::dateDiff($search_date['start_date'],$search_date['end_date']) > 366){
                    throw new Exception('Date difference should not be more than 366 days');
                }
            }else{
                if(CommonHelper::dateDiff($search_date['start_date'],$search_date['end_date']) > 100){
                    throw new Exception('Date difference should not be more than 100 days');
                }
            }
            
            if(isset($data['action']) && $data['action']  == 'get_state_stores'){
                $state_id = trim($data['state_id']);
                $state_where = !empty($state_id)?'state_id = '.$state_id:'state_id > 0';
                $stores_list = Store::where('is_deleted',0)->whereRaw($state_where)->select('id','store_name','store_id_code')->orderBy('store_name')->get()->toArray();
                
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Stores list','stores_list'=>$stores_list),200);
            }
            
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            $fake_inventory = $is_fake_inventory_user?[0,1]:[0];
            
            //\DB::enableQueryLog();
            
            $store_type = (!empty($data['store_type']))?array(trim($data['store_type'])):array(1,2);
            $store_where = (!empty($data['store_id']))?'s.id = '.$data['store_id']:'s.id > 0';
            $state_where = (!empty($data['state_id']))?'s.state_id = '.$data['state_id']:'s.state_id > 0';
            
            if($report_type == 'bill'){
                // bill list
                $bill_list = \DB::table('pos_customer_orders as pco')
                ->join('pos_customer as pc','pc.id', '=', 'pco.customer_id')        
                ->join('store as s','s.id', '=', 'pco.store_id')         
                ->join('pos_customer_orders_detail as pcod','pcod.order_id', '=', 'pco.id')                 
                ->selectRaw("pco.*,s.store_name,s.store_id_code,s.gst_no,pc.customer_name,SUM(pcod.sale_price) as sale_price_total,SUM(pcod.net_price) as net_price_total,SUM(pcod.discount_amount_actual) as discount_amount_total")
                ->groupBy('pco.id')        
                ->wherein('s.store_type',$store_type)    
                ->whereRaw($store_where)        
                ->whereRaw($state_where)                
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->wherein('pco.fake_inventory',$fake_inventory)
                ->wherein('pcod.fake_inventory',$fake_inventory)        
                //->where('pc.fake_inventory',0)      
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)       
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")
                ->orderByRaw('s.store_name,pco.id')        
                ->orderBy('pcod.created_at','ASC')->get()->toArray();
                
                // payment type list
                $payment_list = \DB::table('pos_customer_orders_payments as pcop')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcop.order_id')      
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)               
                ->whereRaw($store_where)        
                ->whereRaw($state_where)                        
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcop.is_deleted',0) 
                ->wherein('pcop.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)       
                ->where('pco.order_status',1)
                ->where('pcop.order_status',1)               
                ->groupByRaw('pcop.order_id,pcop.payment_method')        
                ->selectRaw('pcop.order_id,pcop.payment_method,SUM(pcop.payment_received) as payment_amount_total')        
                ->get()->toArray();
                
                // gst type list
                $gst_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')     
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)     
                ->whereRaw($store_where)                
                ->whereRaw($state_where)                        
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)     
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)   
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->groupByRaw('pcod.order_id,pcod.gst_percent')        
                ->selectRaw('pcod.order_id,pcod.gst_percent,SUM(pcod.net_price) as net_price_total,SUM(pcod.gst_amount) as gst_amount_total,SUM(pcod.discounted_price_actual) as discounted_price')        
                ->get()->toArray();
                
                // quantity list
                $quantity_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')               
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)             
                ->whereRaw($store_where)                
                ->whereRaw($state_where)                        
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)     
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)   
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->groupByRaw('pcod.order_id')        
                ->selectRaw('pcod.order_id,SUM(pcod.product_quantity) as product_quantity_total')        
                ->get()->toArray();
                
                // create payment type array
                for($i=0;$i<count($payment_list);$i++){
                    if(strtolower($payment_list[$i]->payment_method) == 'cash')
                        $payment_data['cash_'.$payment_list[$i]->order_id] = $payment_list[$i]->payment_amount_total;
                    elseif(strtolower($payment_list[$i]->payment_method) == 'card')
                        $payment_data['card_'.$payment_list[$i]->order_id] = $payment_list[$i]->payment_amount_total;
                    elseif(strtolower($payment_list[$i]->payment_method) == 'e-wallet')
                        $payment_data['ewallet_'.$payment_list[$i]->order_id] = $payment_list[$i]->payment_amount_total;
                }
                
                // create gst type array
                for($i=0;$i<count($gst_list);$i++){
                    if($gst_list[$i]->gst_percent == 3 || $gst_list[$i]->gst_percent == -3){
                        if(isset($gst_data['gst_3_net_price_'.$gst_list[$i]->order_id])){
                            $gst_data['gst_3_net_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_3_gst_amount_'.$gst_list[$i]->order_id]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_3_discounted_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_3_net_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_3_gst_amount_'.$gst_list[$i]->order_id] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_3_discounted_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 5 || $gst_list[$i]->gst_percent == -5){
                        if(isset($gst_data['gst_5_net_price_'.$gst_list[$i]->order_id])){
                            $gst_data['gst_5_net_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_5_gst_amount_'.$gst_list[$i]->order_id]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_5_discounted_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_5_net_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_5_gst_amount_'.$gst_list[$i]->order_id] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_5_discounted_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 12 || $gst_list[$i]->gst_percent == -12){
                        if(isset($gst_data['gst_12_net_price_'.$gst_list[$i]->order_id])){
                            $gst_data['gst_12_net_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_12_gst_amount_'.$gst_list[$i]->order_id]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_12_discounted_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_12_net_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_12_gst_amount_'.$gst_list[$i]->order_id] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_12_discounted_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 18 || $gst_list[$i]->gst_percent == -18){
                        if(isset($gst_data['gst_18_net_price_'.$gst_list[$i]->order_id])){
                            $gst_data['gst_18_net_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_18_gst_amount_'.$gst_list[$i]->order_id]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_18_discounted_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_18_net_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_18_gst_amount_'.$gst_list[$i]->order_id] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_18_discounted_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 0 || $gst_list[$i]->gst_percent == -0){
                        if(isset($gst_data['gst_0_net_price_'.$gst_list[$i]->order_id])){
                            $gst_data['gst_0_net_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_0_gst_amount_'.$gst_list[$i]->order_id]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_0_discounted_price_'.$gst_list[$i]->order_id]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_0_net_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_0_gst_amount_'.$gst_list[$i]->order_id] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_0_discounted_price_'.$gst_list[$i]->order_id] = $gst_list[$i]->discounted_price;
                        }
                    }
                }
                
                // create quantity array
                for($i=0;$i<count($quantity_list);$i++){
                    $quantity_data['quantity_'.$quantity_list[$i]->order_id] = $quantity_list[$i]->product_quantity_total;
                }
                
                // merge into bill array
                for($i=0;$i<count($bill_list);$i++){
                    $order_id = $bill_list[$i]->id;
                    
                    $bill_list[$i]->cash_total = (isset($payment_data['cash_'.$order_id]))?$payment_data['cash_'.$order_id]:0;
                    $bill_list[$i]->card_total = (isset($payment_data['card_'.$order_id]))?$payment_data['card_'.$order_id]:0;
                    $bill_list[$i]->ewallet_total = (isset($payment_data['ewallet_'.$order_id]))?$payment_data['ewallet_'.$order_id]:0;
                    
                    $bill_list[$i]->voucher_total = $bill_list[$i]->voucher_amount;
                            
                    $bill_list[$i]->net_price_gst_3_total = (isset($gst_data['gst_3_net_price_'.$order_id]))?$gst_data['gst_3_net_price_'.$order_id]:0;
                    $bill_list[$i]->gst_amount_gst_3_total = (isset($gst_data['gst_3_gst_amount_'.$order_id]))?$gst_data['gst_3_gst_amount_'.$order_id]:0;
                    $bill_list[$i]->discounted_price_gst_3_total = (isset($gst_data['gst_3_discounted_price_'.$order_id]))?$gst_data['gst_3_discounted_price_'.$order_id]:0;
                    
                    $bill_list[$i]->net_price_gst_5_total = (isset($gst_data['gst_5_net_price_'.$order_id]))?$gst_data['gst_5_net_price_'.$order_id]:0;
                    $bill_list[$i]->gst_amount_gst_5_total = (isset($gst_data['gst_5_gst_amount_'.$order_id]))?$gst_data['gst_5_gst_amount_'.$order_id]:0;
                    $bill_list[$i]->discounted_price_gst_5_total = (isset($gst_data['gst_5_discounted_price_'.$order_id]))?$gst_data['gst_5_discounted_price_'.$order_id]:0;
                    
                    $bill_list[$i]->net_price_gst_12_total = (isset($gst_data['gst_12_net_price_'.$order_id]))?$gst_data['gst_12_net_price_'.$order_id]:0;
                    $bill_list[$i]->gst_amount_gst_12_total = (isset($gst_data['gst_12_gst_amount_'.$order_id]))?$gst_data['gst_12_gst_amount_'.$order_id]:0;
                    $bill_list[$i]->discounted_price_gst_12_total = (isset($gst_data['gst_12_discounted_price_'.$order_id]))?$gst_data['gst_12_discounted_price_'.$order_id]:0;
                    
                    $bill_list[$i]->net_price_gst_18_total = (isset($gst_data['gst_18_net_price_'.$order_id]))?$gst_data['gst_18_net_price_'.$order_id]:0;
                    $bill_list[$i]->gst_amount_gst_18_total = (isset($gst_data['gst_18_gst_amount_'.$order_id]))?$gst_data['gst_18_gst_amount_'.$order_id]:0;
                    $bill_list[$i]->discounted_price_gst_18_total = (isset($gst_data['gst_18_discounted_price_'.$order_id]))?$gst_data['gst_18_discounted_price_'.$order_id]:0;
                    
                    $bill_list[$i]->net_price_gst_0_total = (isset($gst_data['gst_0_net_price_'.$order_id]))?$gst_data['gst_0_net_price_'.$order_id]:0;
                    $bill_list[$i]->gst_amount_gst_0_total = (isset($gst_data['gst_0_gst_amount_'.$order_id]))?$gst_data['gst_0_gst_amount_'.$order_id]:0;
                    $bill_list[$i]->discounted_price_gst_0_total = (isset($gst_data['gst_0_discounted_price_'.$order_id]))?$gst_data['gst_0_discounted_price_'.$order_id]:0;
                    
                    $bill_list[$i]->product_quantity_total = (isset($quantity_data['quantity_'.$order_id]))?$quantity_data['quantity_'.$order_id]:0;
                }
            }
            
            if($report_type == 'date'){
                
                $bill_list = \DB::table('pos_customer_orders as pco')
                ->join('store as s','s.id', '=', 'pco.store_id')         
                ->join('pos_customer_orders_detail as pcod','pcod.order_id', '=', 'pco.id')     
                ->wherein('s.store_type',$store_type)         
                ->whereRaw($store_where)       
                ->whereRaw($state_where)             
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)     
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)              
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->groupByRaw('pco.store_id,DATE(pco.created_at)')              
                ->selectRaw("pco.*,DATE(pco.created_at) as created_at,s.store_name,s.store_id_code,s.gst_no,MAX(pco.order_no) as max_id,SUM(pcod.sale_price) as sale_price_total,SUM(pcod.net_price) as net_price_total,SUM(pcod.discount_amount_actual) as discount_amount_total")
                ->orderByRaw('s.store_name,pco.id')        
                ->get()->toArray();
                
                // Voucher list
                $bill_voucher_list = \DB::table('pos_customer_orders as pco')
                ->join('store as s','s.id', '=', 'pco.store_id')         
                ->wherein('s.store_type',$store_type)  
                ->whereRaw($store_where)            
                ->whereRaw($state_where)             
                ->where('pco.is_deleted',0)
                ->wherein('pco.fake_inventory',$fake_inventory)    
                ->where('pco.order_status',1)
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->groupByRaw('pco.store_id,DATE(pco.created_at)')              
                ->selectRaw("pco.*,DATE(pco.created_at) as created_at_date,s.store_name,s.store_id_code,SUM(pco.voucher_amount) as voucher_total")
                ->orderByRaw('s.store_name,pco.id')        
                ->get()->toArray();
                
                // payment type list
                //\DB::enableQueryLog();
                $payment_list = \DB::table('pos_customer_orders_payments as pcop')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcop.order_id')     
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)        
                ->whereRaw($store_where)     
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcop.is_deleted',0)      
                ->wherein('pcop.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)     
                ->where('pco.order_status',1)
                ->where('pcop.order_status',1)               
                ->groupByRaw('pcop.store_id,DATE(pco.created_at),pcop.payment_method')        
                ->selectRaw('pcop.store_id,DATE(pco.created_at) as created_at_date,pcop.payment_method,SUM(pcop.payment_received) as payment_amount_total')        
                ->get()->toArray();
                
                //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
                
                // gst type list
                //\DB::enableQueryLog();
                $gst_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')     
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)        
                ->whereRaw($store_where)     
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)     
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)              
                ->groupByRaw('pcod.store_id,DATE(pcod.created_at),pcod.gst_percent')        
                ->selectRaw('pcod.store_id,DATE(pco.created_at) as created_at_date,pcod.gst_percent,SUM(pcod.net_price) as net_price_total,SUM(pcod.gst_amount) as gst_amount_total,SUM(pcod.discounted_price_actual) as discounted_price')        
                ->get()->toArray();
                //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
                
                // quantity list
                $quantity_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')    
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)      
                ->whereRaw($store_where)          
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)      
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->groupByRaw('pcod.store_id,DATE(pcod.created_at)')        
                ->selectRaw('pcod.store_id,DATE(pco.created_at) as created_at_date,SUM(pcod.product_quantity) as product_quantity_total')        
                ->get()->toArray();
                
                // create voucher type array
                for($i=0;$i<count($bill_voucher_list);$i++){
                    $date = $bill_voucher_list[$i]->store_id.'_'.str_replace('-','_',$bill_voucher_list[$i]->created_at_date);
                    $voucher_data['voucher_'.$date] = $bill_voucher_list[$i]->voucher_total;
                }
                
                // create payment type array
                for($i=0;$i<count($payment_list);$i++){
                    $date = $payment_list[$i]->store_id.'_'.str_replace('-','_',$payment_list[$i]->created_at_date);
                    
                    if(strtolower($payment_list[$i]->payment_method) == 'cash')
                        $payment_data['cash_'.$date] = $payment_list[$i]->payment_amount_total;
                    elseif(strtolower($payment_list[$i]->payment_method) == 'card')
                        $payment_data['card_'.$date] = $payment_list[$i]->payment_amount_total;
                    elseif(strtolower($payment_list[$i]->payment_method) == 'e-wallet')
                        $payment_data['ewallet_'.$date] = $payment_list[$i]->payment_amount_total;
                }
                
                // create gst type array
                for($i=0;$i<count($gst_list);$i++){
                    $date = $gst_list[$i]->store_id.'_'.str_replace('-','_',$gst_list[$i]->created_at_date);
                    
                    if($gst_list[$i]->gst_percent == 3 || $gst_list[$i]->gst_percent == -3){
                        if(isset($gst_data['gst_3_net_price_'.$date])){
                            $gst_data['gst_3_net_price_'.$date]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_3_gst_amount_'.$date]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_3_discounted_price_'.$date]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_3_net_price_'.$date] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_3_gst_amount_'.$date] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_3_discounted_price_'.$date] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 5 || $gst_list[$i]->gst_percent == -5){
                        if(isset($gst_data['gst_5_net_price_'.$date])){
                            $gst_data['gst_5_net_price_'.$date]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_5_gst_amount_'.$date]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_5_discounted_price_'.$date]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_5_net_price_'.$date] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_5_gst_amount_'.$date] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_5_discounted_price_'.$date] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 12 || $gst_list[$i]->gst_percent == -12){
                        if(isset($gst_data['gst_12_net_price_'.$date])){
                            $gst_data['gst_12_net_price_'.$date]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_12_gst_amount_'.$date]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_12_discounted_price_'.$date]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_12_net_price_'.$date] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_12_gst_amount_'.$date] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_12_discounted_price_'.$date] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 18 || $gst_list[$i]->gst_percent == -18){
                        if(isset($gst_data['gst_18_net_price_'.$date])){
                            $gst_data['gst_18_net_price_'.$date]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_18_gst_amount_'.$date]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_18_discounted_price_'.$date]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_18_net_price_'.$date] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_18_gst_amount_'.$date] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_18_discounted_price_'.$date] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 0 || $gst_list[$i]->gst_percent == -0){
                        if(isset($gst_data['gst_0_net_price_'.$date])){
                            $gst_data['gst_0_net_price_'.$date]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_0_gst_amount_'.$date]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_0_discounted_price_'.$date]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_0_net_price_'.$date] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_0_gst_amount_'.$date] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_0_discounted_price_'.$date] = $gst_list[$i]->discounted_price;
                        }
                    }
                }
                
                // create quantity array
                for($i=0;$i<count($quantity_list);$i++){
                    $date = $quantity_list[$i]->store_id.'_'.str_replace('-','_',$quantity_list[$i]->created_at_date);
                    $quantity_data['quantity_'.$date] = $quantity_list[$i]->product_quantity_total;
                }
                
                // merge into bill array
                for($i=0;$i<count($bill_list);$i++){
                    $date = $bill_list[$i]->store_id.'_'.str_replace('-','_',$bill_list[$i]->created_at);
                    
                    $bill_list[$i]->cash_total = (isset($payment_data['cash_'.$date]))?$payment_data['cash_'.$date]:0;
                    $bill_list[$i]->card_total = (isset($payment_data['card_'.$date]))?$payment_data['card_'.$date]:0;
                    $bill_list[$i]->ewallet_total = (isset($payment_data['ewallet_'.$date]))?$payment_data['ewallet_'.$date]:0;
                    
                    $bill_list[$i]->voucher_total = (isset($voucher_data['voucher_'.$date]))?$voucher_data['voucher_'.$date]:0;
                    
                    $bill_list[$i]->net_price_gst_3_total = (isset($gst_data['gst_3_net_price_'.$date]))?$gst_data['gst_3_net_price_'.$date]:0;
                    $bill_list[$i]->gst_amount_gst_3_total = (isset($gst_data['gst_3_gst_amount_'.$date]))?$gst_data['gst_3_gst_amount_'.$date]:0;
                    $bill_list[$i]->discounted_price_gst_3_total = (isset($gst_data['gst_3_discounted_price_'.$date]))?$gst_data['gst_3_discounted_price_'.$date]:0;
                    
                    $bill_list[$i]->net_price_gst_5_total = (isset($gst_data['gst_5_net_price_'.$date]))?$gst_data['gst_5_net_price_'.$date]:0;
                    $bill_list[$i]->gst_amount_gst_5_total = (isset($gst_data['gst_5_gst_amount_'.$date]))?$gst_data['gst_5_gst_amount_'.$date]:0;
                    $bill_list[$i]->discounted_price_gst_5_total = (isset($gst_data['gst_5_discounted_price_'.$date]))?$gst_data['gst_5_discounted_price_'.$date]:0;
                    
                    $bill_list[$i]->net_price_gst_12_total = (isset($gst_data['gst_12_net_price_'.$date]))?$gst_data['gst_12_net_price_'.$date]:0;
                    $bill_list[$i]->gst_amount_gst_12_total = (isset($gst_data['gst_12_gst_amount_'.$date]))?$gst_data['gst_12_gst_amount_'.$date]:0;
                    $bill_list[$i]->discounted_price_gst_12_total = (isset($gst_data['gst_12_discounted_price_'.$date]))?$gst_data['gst_12_discounted_price_'.$date]:0;
                    
                    $bill_list[$i]->net_price_gst_18_total = (isset($gst_data['gst_18_net_price_'.$date]))?$gst_data['gst_18_net_price_'.$date]:0;
                    $bill_list[$i]->gst_amount_gst_18_total = (isset($gst_data['gst_18_gst_amount_'.$date]))?$gst_data['gst_18_gst_amount_'.$date]:0;
                    $bill_list[$i]->discounted_price_gst_18_total = (isset($gst_data['gst_18_discounted_price_'.$date]))?$gst_data['gst_18_discounted_price_'.$date]:0;
                    
                    $bill_list[$i]->net_price_gst_0_total = (isset($gst_data['gst_0_net_price_'.$date]))?$gst_data['gst_0_net_price_'.$date]:0;
                    $bill_list[$i]->gst_amount_gst_0_total = (isset($gst_data['gst_0_gst_amount_'.$date]))?$gst_data['gst_0_gst_amount_'.$date]:0;
                    $bill_list[$i]->discounted_price_gst_0_total = (isset($gst_data['gst_0_discounted_price_'.$date]))?$gst_data['gst_0_discounted_price_'.$date]:0;
                    
                    $bill_list[$i]->product_quantity_total = (isset($quantity_data['quantity_'.$date]))?$quantity_data['quantity_'.$date]:0;
                }
            }
            
            if($report_type == 'month'){
                /*$start  = new DateTime($search_date['start_date']);
                $start->modify('first day of this month');
                $start_date =  $start->format("Y-m-d").' 00:00';;
                
                $end  = new DateTime($search_date['end_date']);
                $end->modify('last day of this month');
                $end_date =  $end->format("Y-m-d").' 23:59';;*/
                
                $start_date = $search_date['start_date'];
                $end_date = $search_date['end_date'];
                
                $bill_list = \DB::table('pos_customer_orders as pco')
                ->join('store as s','s.id', '=', 'pco.store_id')         
                ->join('pos_customer_orders_detail as pcod','pcod.order_id', '=', 'pco.id') 
                ->wherein('s.store_type',$store_type)         
                ->whereRaw($store_where)     
                ->whereRaw($state_where)             
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->wherein('pco.fake_inventory',$fake_inventory)    
                ->wherein('pcod.fake_inventory',$fake_inventory)        
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->whereRaw("pco.created_at BETWEEN '".$start_date."' AND '".$end_date."'")        
                ->groupByRaw('pco.store_id,MONTH(pco.created_at)')        
                ->orderByRaw('s.store_name,pco.id')
                ->selectRaw("pco.*,MONTH(pco.created_at) as created_at_month,s.store_name,s.store_id_code,s.gst_no,MAX(pco.order_no) as max_id,SUM(pcod.sale_price) as sale_price_total,SUM(pcod.net_price) as net_price_total,
                SUM(pcod.discount_amount_actual) as discount_amount_total")
                ->get()->toArray();        
                
                // Voucher list
                $bill_voucher_list = \DB::table('pos_customer_orders as pco')
                ->join('store as s','s.id', '=', 'pco.store_id')         
                ->wherein('s.store_type',$store_type)    
                ->whereRaw($store_where) 
                ->whereRaw($state_where)             
                ->where('pco.is_deleted',0)
                ->wherein('pco.fake_inventory',$fake_inventory)   
                ->where('pco.order_status',1)
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->groupByRaw('pco.store_id,MONTH(pco.created_at)')              
                ->selectRaw("pco.*,MONTH(pco.created_at) as created_at_month,s.store_name,s.store_id_code,SUM(pco.voucher_amount) as voucher_total")
                ->orderByRaw('s.store_name,pco.id')        
                ->get()->toArray();
                
                // payment type list
                $payment_list = \DB::table('pos_customer_orders_payments as pcop')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcop.order_id')       
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)     
                ->whereRaw($store_where)  
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$start_date."' AND '".$end_date."'")        
                ->where('pco.is_deleted',0)
                ->where('pcop.is_deleted',0)     
                ->wherein('pcop.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)    
                ->where('pco.order_status',1)
                ->where('pcop.order_status',1)               
                ->groupByRaw('pcop.store_id,MONTH(pco.created_at),pcop.payment_method')        
                ->selectRaw('pcop.store_id,MONTH(pco.created_at) as created_at_month,pcop.payment_method,SUM(pcop.payment_received) as payment_amount_total')        
                ->get()->toArray();
                
                // gst type list
                $gst_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')    
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)    
                ->whereRaw($store_where)    
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$start_date."' AND '".$end_date."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)      
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)        
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->groupByRaw('pcod.store_id,MONTH(pcod.created_at),pcod.gst_percent')        
                ->selectRaw('pcod.store_id,MONTH(pco.created_at) as created_at_month,pcod.gst_percent,SUM(pcod.net_price) as net_price_total,SUM(pcod.gst_amount) as gst_amount_total,SUM(pcod.discounted_price_actual) as discounted_price')        
                ->get()->toArray();
                
                //\DB::enableQueryLog();
                // quantity list
                $quantity_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')  
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)       
                ->whereRaw($store_where)      
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$start_date."' AND '".$end_date."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)   
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)     
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->groupByRaw('pcod.store_id,MONTH(pcod.created_at)')        
                ->selectRaw('pcod.store_id,MONTH(pco.created_at) as created_at_month,SUM(pcod.product_quantity) as product_quantity_total')        
                ->get()->toArray();
                
                //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
                
                //create voucher array
                for($i=0;$i<count($bill_voucher_list);$i++){
                    $month = $bill_voucher_list[$i]->store_id.'_'.$bill_voucher_list[$i]->created_at_month;
                    $voucher_data['voucher_'.$month] = $bill_voucher_list[$i]->voucher_total;
                }
                
                // create payment type array
                for($i=0;$i<count($payment_list);$i++){
                    $month = $payment_list[$i]->store_id.'_'.$payment_list[$i]->created_at_month;
                    
                    if(strtolower($payment_list[$i]->payment_method) == 'cash')
                        $payment_data['cash_'.$month] = $payment_list[$i]->payment_amount_total;
                    elseif(strtolower($payment_list[$i]->payment_method) == 'card')
                        $payment_data['card_'.$month] = $payment_list[$i]->payment_amount_total;
                    elseif(strtolower($payment_list[$i]->payment_method) == 'e-wallet')
                        $payment_data['ewallet_'.$month] = $payment_list[$i]->payment_amount_total;
                }
                
                // create gst type array
                for($i=0;$i<count($gst_list);$i++){
                    $month = $gst_list[$i]->store_id.'_'.$gst_list[$i]->created_at_month;
                    
                    if($gst_list[$i]->gst_percent == 3 || $gst_list[$i]->gst_percent == -3){
                        if(isset($gst_data['gst_3_net_price_'.$month])){
                            $gst_data['gst_3_net_price_'.$month]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_3_gst_amount_'.$month]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_3_discounted_price_'.$month]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_3_net_price_'.$month] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_3_gst_amount_'.$month] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_3_discounted_price_'.$month] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 5 || $gst_list[$i]->gst_percent == -5){
                        if(isset($gst_data['gst_5_net_price_'.$month])){
                            $gst_data['gst_5_net_price_'.$month]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_5_gst_amount_'.$month]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_5_discounted_price_'.$month]+=$gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_5_net_price_'.$month] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_5_gst_amount_'.$month] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_5_discounted_price_'.$month] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 12 || $gst_list[$i]->gst_percent == -12){
                        if(isset($gst_data['gst_12_net_price_'.$month])){
                            $gst_data['gst_12_net_price_'.$month]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_12_gst_amount_'.$month]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_12_discounted_price_'.$month]+= $gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_12_net_price_'.$month] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_12_gst_amount_'.$month] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_12_discounted_price_'.$month] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 18 || $gst_list[$i]->gst_percent == -18){
                        if(isset($gst_data['gst_18_net_price_'.$month])){
                            $gst_data['gst_18_net_price_'.$month]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_18_gst_amount_'.$month]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_18_discounted_price_'.$month]+= $gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_18_net_price_'.$month] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_18_gst_amount_'.$month] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_18_discounted_price_'.$month] = $gst_list[$i]->discounted_price;
                        }
                    }elseif($gst_list[$i]->gst_percent == 0 || $gst_list[$i]->gst_percent == -0){
                        if(isset($gst_data['gst_0_net_price_'.$month])){
                            $gst_data['gst_0_net_price_'.$month]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_0_gst_amount_'.$month]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_0_discounted_price_'.$month]+= $gst_list[$i]->discounted_price;
                        }else{
                            $gst_data['gst_0_net_price_'.$month] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_0_gst_amount_'.$month] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_0_discounted_price_'.$month] = $gst_list[$i]->discounted_price;
                        }
                    }
                }
                
                // create quantity array
                for($i=0;$i<count($quantity_list);$i++){
                    $month = $quantity_list[$i]->store_id.'_'.$quantity_list[$i]->created_at_month;
                    $quantity_data['quantity_'.$month] = $quantity_list[$i]->product_quantity_total;
                }
                
                // merge into bill array
                for($i=0;$i<count($bill_list);$i++){
                    $month = $bill_list[$i]->store_id.'_'.$bill_list[$i]->created_at_month;
                    
                    $bill_list[$i]->cash_total = (isset($payment_data['cash_'.$month]))?$payment_data['cash_'.$month]:0;
                    $bill_list[$i]->card_total = (isset($payment_data['card_'.$month]))?$payment_data['card_'.$month]:0;
                    $bill_list[$i]->ewallet_total = (isset($payment_data['ewallet_'.$month]))?$payment_data['ewallet_'.$month]:0;
                    $bill_list[$i]->voucher_total = (isset($voucher_data['voucher_'.$month]))?$voucher_data['voucher_'.$month]:0;
                    
                    $bill_list[$i]->net_price_gst_3_total = (isset($gst_data['gst_3_net_price_'.$month]))?$gst_data['gst_3_net_price_'.$month]:0;
                    $bill_list[$i]->gst_amount_gst_3_total = (isset($gst_data['gst_3_gst_amount_'.$month]))?$gst_data['gst_3_gst_amount_'.$month]:0;
                    $bill_list[$i]->discounted_price_gst_3_total = (isset($gst_data['gst_3_discounted_price_'.$month]))?$gst_data['gst_3_discounted_price_'.$month]:0;
                    
                    $bill_list[$i]->net_price_gst_5_total = (isset($gst_data['gst_5_net_price_'.$month]))?$gst_data['gst_5_net_price_'.$month]:0;
                    $bill_list[$i]->gst_amount_gst_5_total = (isset($gst_data['gst_5_gst_amount_'.$month]))?$gst_data['gst_5_gst_amount_'.$month]:0;
                    $bill_list[$i]->discounted_price_gst_5_total = (isset($gst_data['gst_5_discounted_price_'.$month]))?$gst_data['gst_5_discounted_price_'.$month]:0;
                    
                    $bill_list[$i]->net_price_gst_12_total = (isset($gst_data['gst_12_net_price_'.$month]))?$gst_data['gst_12_net_price_'.$month]:0;
                    $bill_list[$i]->gst_amount_gst_12_total = (isset($gst_data['gst_12_gst_amount_'.$month]))?$gst_data['gst_12_gst_amount_'.$month]:0;
                    $bill_list[$i]->discounted_price_gst_12_total = (isset($gst_data['gst_12_discounted_price_'.$month]))?$gst_data['gst_12_discounted_price_'.$month]:0;
                    
                    $bill_list[$i]->net_price_gst_18_total = (isset($gst_data['gst_18_net_price_'.$month]))?$gst_data['gst_18_net_price_'.$month]:0;
                    $bill_list[$i]->gst_amount_gst_18_total = (isset($gst_data['gst_18_gst_amount_'.$month]))?$gst_data['gst_18_gst_amount_'.$month]:0;
                    $bill_list[$i]->discounted_price_gst_18_total = (isset($gst_data['gst_18_discounted_price_'.$month]))?$gst_data['gst_18_discounted_price_'.$month]:0;
                    
                    $bill_list[$i]->net_price_gst_0_total = (isset($gst_data['gst_0_net_price_'.$month]))?$gst_data['gst_0_net_price_'.$month]:0;
                    $bill_list[$i]->gst_amount_gst_0_total = (isset($gst_data['gst_0_gst_amount_'.$month]))?$gst_data['gst_0_gst_amount_'.$month]:0;
                    $bill_list[$i]->discounted_price_gst_0_total = (isset($gst_data['gst_0_discounted_price_'.$month]))?$gst_data['gst_0_discounted_price_'.$month]:0;
                    
                    $bill_list[$i]->product_quantity_total = (isset($quantity_data['quantity_'.$month]))?$quantity_data['quantity_'.$month]:0;
                }
            }
            
            if($report_type == 'hsn_code'){
                
                $bill_list = \DB::table('pos_customer_orders as pco')
                ->join('store as s','s.id', '=', 'pco.store_id')         
                ->join('pos_customer_orders_detail as pcod','pcod.order_id', '=', 'pco.id')                 
                ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')   
                ->wherein('s.store_type',$store_type)         
                ->whereRaw($store_where)             
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->wherein('pco.fake_inventory',$fake_inventory)    
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('ppm.fake_inventory',$fake_inventory)   
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->groupByRaw('pco.store_id,ppm.hsn_code')        
                ->selectRaw("pco.*,s.store_name,s.store_id_code,s.gst_no,MAX(pco.id) as max_id,SUM(pcod.sale_price) as sale_price_total,SUM(pcod.net_price) as net_price_total,
                SUM(pcod.discount_amount_actual) as discount_amount_total,ppm.hsn_code")
                ->orderByRaw('s.store_name,pco.id')
                ->get()->toArray();        
                
                // payment type list
                /*$payment_list = \DB::table('pos_customer_orders_payments as pcop')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcop.order_id')
                ->join('pos_customer_orders_detail as pcod','pcod.order_id', '=', 'pco.id')                 
                ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')        
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcop.is_deleted',0)      
                ->groupByRaw('pcop.store_id,ppm.hsn_code,pcop.payment_method')        
                ->selectRaw('pcop.store_id,ppm.hsn_code,pcop.payment_method,SUM(pcod.net_price) as payment_amount_total')     
                ->get()->toArray();//print_r($payment_list);exit;*/
                
                // gst type list
                $gst_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')    
                ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')      
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)      
                ->whereRaw($store_where)       
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0) 
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)  
                ->wherein('ppm.fake_inventory',$fake_inventory)    
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->groupByRaw('pcod.store_id,ppm.hsn_code,pcod.gst_percent')        
                ->selectRaw('pcod.store_id,ppm.hsn_code,pcod.gst_percent,SUM(pcod.net_price) as net_price_total,SUM(pcod.gst_amount) as gst_amount_total,SUM(pcod.discounted_price_actual) as discounted_price,SUM(pcod.product_quantity) as gst_qty_total')        
                ->get()->toArray();
                
                // quantity list
                $quantity_list = \DB::table('pos_customer_orders_detail as pcod')
                ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')      
                ->join('pos_product_master as ppm','pcod.product_id', '=', 'ppm.id')        
                ->join('store as s','s.id', '=', 'pco.store_id')        
                ->wherein('s.store_type',$store_type)     
                ->whereRaw($store_where)         
                ->whereRaw($state_where)             
                ->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)   
                ->wherein('pcod.fake_inventory',$fake_inventory)    
                ->wherein('pco.fake_inventory',$fake_inventory)   
                ->wherein('ppm.fake_inventory',$fake_inventory)    
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->groupByRaw('pcod.store_id,ppm.hsn_code')        
                ->selectRaw('pcod.store_id,ppm.hsn_code,SUM(pcod.product_quantity) as product_quantity_total')        
                ->get()->toArray();
                
                // create payment type array
                /*for($i=0;$i<count($payment_list);$i++){
                    $hsn_code = $payment_list[$i]->store_id.'_'.$payment_list[$i]->hsn_code;
                    
                    if(strtolower($payment_list[$i]->payment_method) == 'cash')
                        $payment_data['cash_'.$hsn_code] = $payment_list[$i]->payment_amount_total;
                    elseif(strtolower($payment_list[$i]->payment_method) == 'card')
                        $payment_data['card_'.$hsn_code] = $payment_list[$i]->payment_amount_total;
                    elseif(strtolower($payment_list[$i]->payment_method) == 'e-wallet')
                        $payment_data['ewallet_'.$hsn_code] = $payment_list[$i]->payment_amount_total;
                }*/
                
                 // create gst type array
                for($i=0;$i<count($gst_list);$i++){
                    $hsn_code = $gst_list[$i]->store_id.'_'.$gst_list[$i]->hsn_code;
                    
                    if($gst_list[$i]->gst_percent == 3 || $gst_list[$i]->gst_percent == -3){
                        if(isset($gst_data['gst_3_net_price_'.$hsn_code])){
                            $gst_data['gst_3_net_price_'.$hsn_code]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_3_gst_amount_'.$hsn_code]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_3_discounted_price_'.$hsn_code]+=$gst_list[$i]->discounted_price;
                            $gst_data['gst_3_gst_qty_total_'.$hsn_code]+=$gst_list[$i]->gst_qty_total;
                        }else{
                            $gst_data['gst_3_net_price_'.$hsn_code] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_3_gst_amount_'.$hsn_code] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_3_discounted_price_'.$hsn_code] = $gst_list[$i]->discounted_price;
                            $gst_data['gst_3_gst_qty_total_'.$hsn_code] = $gst_list[$i]->gst_qty_total;
                        }
                    }elseif($gst_list[$i]->gst_percent == 5 || $gst_list[$i]->gst_percent == -5){
                        if(isset($gst_data['gst_5_net_price_'.$hsn_code])){
                            $gst_data['gst_5_net_price_'.$hsn_code]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_5_gst_amount_'.$hsn_code]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_5_discounted_price_'.$hsn_code]+=$gst_list[$i]->discounted_price;
                            $gst_data['gst_5_gst_qty_total_'.$hsn_code]+=$gst_list[$i]->gst_qty_total;
                        }else{
                            $gst_data['gst_5_net_price_'.$hsn_code] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_5_gst_amount_'.$hsn_code] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_5_discounted_price_'.$hsn_code] = $gst_list[$i]->discounted_price;
                            $gst_data['gst_5_gst_qty_total_'.$hsn_code] = $gst_list[$i]->gst_qty_total;
                        }
                    }elseif($gst_list[$i]->gst_percent == 12 || $gst_list[$i]->gst_percent == -12){
                        if(isset($gst_data['gst_12_net_price_'.$hsn_code])){
                            $gst_data['gst_12_net_price_'.$hsn_code]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_12_gst_amount_'.$hsn_code]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_12_discounted_price_'.$hsn_code]+= $gst_list[$i]->discounted_price;
                            $gst_data['gst_12_gst_qty_total_'.$hsn_code]+=$gst_list[$i]->gst_qty_total;
                        }else{
                            $gst_data['gst_12_net_price_'.$hsn_code] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_12_gst_amount_'.$hsn_code] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_12_discounted_price_'.$hsn_code] = $gst_list[$i]->discounted_price;
                            $gst_data['gst_12_gst_qty_total_'.$hsn_code] = $gst_list[$i]->gst_qty_total;
                        }
                    }elseif($gst_list[$i]->gst_percent == 18 || $gst_list[$i]->gst_percent == -18){
                        if(isset($gst_data['gst_18_net_price_'.$hsn_code])){
                            $gst_data['gst_18_net_price_'.$hsn_code]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_18_gst_amount_'.$hsn_code]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_18_discounted_price_'.$hsn_code]+=$gst_list[$i]->discounted_price;
                            $gst_data['gst_18_gst_qty_total_'.$hsn_code]+=$gst_list[$i]->gst_qty_total;
                        }else{
                            $gst_data['gst_18_net_price_'.$hsn_code] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_18_gst_amount_'.$hsn_code] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_18_discounted_price_'.$hsn_code] = $gst_list[$i]->discounted_price;
                            $gst_data['gst_18_gst_qty_total_'.$hsn_code] = $gst_list[$i]->gst_qty_total;
                        }
                    }elseif($gst_list[$i]->gst_percent == 0 || $gst_list[$i]->gst_percent == -0){
                        if(isset($gst_data['gst_0_net_price_'.$hsn_code])){
                            $gst_data['gst_0_net_price_'.$hsn_code]+=$gst_list[$i]->net_price_total;
                            $gst_data['gst_0_gst_amount_'.$hsn_code]+=$gst_list[$i]->gst_amount_total;
                            $gst_data['gst_0_discounted_price_'.$hsn_code]+=$gst_list[$i]->discounted_price;
                            $gst_data['gst_0_gst_qty_total_'.$hsn_code]+=$gst_list[$i]->gst_qty_total;
                        }else{
                            $gst_data['gst_0_net_price_'.$hsn_code] = $gst_list[$i]->net_price_total;
                            $gst_data['gst_0_gst_amount_'.$hsn_code] = $gst_list[$i]->gst_amount_total;
                            $gst_data['gst_0_discounted_price_'.$hsn_code] = $gst_list[$i]->discounted_price;
                            $gst_data['gst_0_gst_qty_total_'.$hsn_code] = $gst_list[$i]->gst_qty_total;
                        }
                    }
                }
                
                // create quantity array
                for($i=0;$i<count($quantity_list);$i++){
                    $hsn_code = $quantity_list[$i]->store_id.'_'.$quantity_list[$i]->hsn_code;
                    $quantity_data['quantity_'.$hsn_code] = $quantity_list[$i]->product_quantity_total;
                }
                
                // merge into bill array
                for($i=0;$i<count($bill_list);$i++){
                    $hsn_code = $bill_list[$i]->store_id.'_'.$bill_list[$i]->hsn_code;
                    
                    $bill_list[$i]->cash_total = '0'; //(isset($payment_data['cash_'.$hsn_code]))?$payment_data['cash_'.$hsn_code]:0;
                    $bill_list[$i]->card_total = '0'; //(isset($payment_data['card_'.$hsn_code]))?$payment_data['card_'.$hsn_code]:0;
                    $bill_list[$i]->ewallet_total = '0'; //(isset($payment_data['ewallet_'.$hsn_code]))?$payment_data['ewallet_'.$hsn_code]:0;
                    
                    $bill_list[$i]->net_price_gst_3_total = (isset($gst_data['gst_3_net_price_'.$hsn_code]))?$gst_data['gst_3_net_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_amount_gst_3_total = (isset($gst_data['gst_3_gst_amount_'.$hsn_code]))?$gst_data['gst_3_gst_amount_'.$hsn_code]:0;
                    $bill_list[$i]->discounted_price_gst_3_total = (isset($gst_data['gst_3_discounted_price_'.$hsn_code]))?$gst_data['gst_3_discounted_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_3_gst_qty_total = (isset($gst_data['gst_3_gst_qty_total_'.$hsn_code]))?$gst_data['gst_3_gst_qty_total_'.$hsn_code]:0;
                    
                    $bill_list[$i]->net_price_gst_5_total = (isset($gst_data['gst_5_net_price_'.$hsn_code]))?$gst_data['gst_5_net_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_amount_gst_5_total = (isset($gst_data['gst_5_gst_amount_'.$hsn_code]))?$gst_data['gst_5_gst_amount_'.$hsn_code]:0;
                    $bill_list[$i]->discounted_price_gst_5_total = (isset($gst_data['gst_5_discounted_price_'.$hsn_code]))?$gst_data['gst_5_discounted_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_5_gst_qty_total = (isset($gst_data['gst_5_gst_qty_total_'.$hsn_code]))?$gst_data['gst_5_gst_qty_total_'.$hsn_code]:0;
                    
                    $bill_list[$i]->net_price_gst_12_total = (isset($gst_data['gst_12_net_price_'.$hsn_code]))?$gst_data['gst_12_net_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_amount_gst_12_total = (isset($gst_data['gst_12_gst_amount_'.$hsn_code]))?$gst_data['gst_12_gst_amount_'.$hsn_code]:0;
                    $bill_list[$i]->discounted_price_gst_12_total = (isset($gst_data['gst_12_discounted_price_'.$hsn_code]))?$gst_data['gst_12_discounted_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_12_gst_qty_total = (isset($gst_data['gst_12_gst_qty_total_'.$hsn_code]))?$gst_data['gst_12_gst_qty_total_'.$hsn_code]:0;
                    
                    $bill_list[$i]->net_price_gst_18_total = (isset($gst_data['gst_18_net_price_'.$hsn_code]))?$gst_data['gst_18_net_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_amount_gst_18_total = (isset($gst_data['gst_18_gst_amount_'.$hsn_code]))?$gst_data['gst_18_gst_amount_'.$hsn_code]:0;
                    $bill_list[$i]->discounted_price_gst_18_total = (isset($gst_data['gst_18_discounted_price_'.$hsn_code]))?$gst_data['gst_18_discounted_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_18_gst_qty_total = (isset($gst_data['gst_18_gst_qty_total_'.$hsn_code]))?$gst_data['gst_18_gst_qty_total_'.$hsn_code]:0;
                    
                    $bill_list[$i]->net_price_gst_0_total = (isset($gst_data['gst_0_net_price_'.$hsn_code]))?$gst_data['gst_0_net_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_amount_gst_0_total = (isset($gst_data['gst_0_gst_amount_'.$hsn_code]))?$gst_data['gst_0_gst_amount_'.$hsn_code]:0;
                    $bill_list[$i]->discounted_price_gst_0_total = (isset($gst_data['gst_0_discounted_price_'.$hsn_code]))?$gst_data['gst_0_discounted_price_'.$hsn_code]:0;
                    $bill_list[$i]->gst_0_gst_qty_total = (isset($gst_data['gst_0_gst_qty_total_'.$hsn_code]))?$gst_data['gst_0_gst_qty_total_'.$hsn_code]:0;
                    
                    $bill_list[$i]->product_quantity_total = (isset($quantity_data['quantity_'.$hsn_code]))?$quantity_data['quantity_'.$hsn_code]:0;
                }
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array(
                    'Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=report_store_to_customer_'.$report_type.'.csv',
                    'Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0'
                );

                if($report_type == 'bill'){
                    $columns_1 = array('Store Name','Store Code','Store GST No','Bill No','Bill Date','Customer Name','Customer GST No','Description');
                    $columns_2 = array('Narration','Bill Qty','Cash Ledger','Cash','Card Ledger','Card','E-Wallet Ledger','E-Wallet','Voucher','Round off','Round off','Gross Value','Discount','Net Amount','Sales 3%','GST 3% Taxable Value','CGST Ledger 1.5%','CGST 1.5%','SGST Ledger 1.5%','SGST 1.5%','Total GST 3%','Sales 5%','GST 5% Taxable Value','CGST Ledger 2.5%','CGST 2.5%','SGST Ledger 2.5%','SGST 2.5%','Total GST 5%','Sales 12%','GST 12% Taxable Value','CGST Ledger 6%','CGST 6%','SGST Ledger 6%','SGST 6%','Total GST 12%','Sales 18%','GST 18% Taxable Value','CGST Ledger 9%','CGST 9%','SGST Ledger 9%','SGST 9%','Total GST 18%','Sales 0%','GST 0% Taxable Value','CGST Ledger 0%','CGST 0%','SGST Ledger 0%','SGST 0%','Total GST 0%');
                }elseif($report_type == 'date'){
                    $columns_1 = array('Store Name','Store Code','Store GST No','Bill Date','Bill No');
                    $columns_2 = array('Narration','Bill Qty','Cash Ledger','Cash','Card Ledger','Card','E-Wallet Ledger','E-Wallet','Voucher','Round off','Round off','Gross Value','Discount','Net Amount','Sales 3%','GST 3% Taxable Value','CGST Ledger 1.5%','CGST 1.5%','SGST Ledger 1.5%','SGST 1.5%','Total GST 3%','Sales 5%','GST 5% Taxable Value','CGST Ledger 2.5%','CGST 2.5%','SGST Ledger 2.5%','SGST 2.5%','Total GST 5%','Sales 12%','GST 12% Taxable Value','CGST Ledger 6%','CGST 6%','SGST Ledger 6%','SGST 6%','Total GST 12%','Sales 18%','GST 18% Taxable Value','CGST Ledger 9%','CGST 9%','SGST Ledger 9%','SGST 9%','Total GST 18%','Sales 0%','GST 0% Taxable Value','CGST Ledger 0%','CGST 0%','SGST Ledger 0%','SGST 0%','Total GST 0%');
                }elseif($report_type == 'month'){
                    $columns_1 = array('Store Name','Store Code','Store GST No','Bill Month','Bill No');
                    $columns_2 = array('Narration','Bill Qty','Cash Ledger','Cash','Card Ledger','Card','E-Wallet Ledger','E-Wallet','Voucher','Round off','Round off','Gross Value','Discount','Net Amount','Sales 3%','GST 3% Taxable Value','CGST Ledger 1.5%','CGST 1.5%','SGST Ledger 1.5%','SGST 1.5%','Total GST 3%','Sales 5%','GST 5% Taxable Value','CGST Ledger 2.5%','CGST 2.5%','SGST Ledger 2.5%','SGST 2.5%','Total GST 5%','Sales 12%','GST 12% Taxable Value','CGST Ledger 6%','CGST 6%','SGST Ledger 6%','SGST 6%','Total GST 12%','Sales 18%','GST 18% Taxable Value','CGST Ledger 9%','CGST 9%','SGST Ledger 9%','SGST 9%','Total GST 18%','Sales 0%','GST 0% Taxable Value','CGST Ledger 0%','CGST 0%','SGST Ledger 0%','SGST 0%','Total GST 0%');
                }elseif($report_type == 'hsn_code'){
                    $columns_1 = array('Store Name','Store Code','Store GST No','HSN Code');
                    $columns_2 = array('Narration','Bill Qty','Gross Value','Discount','Net Amount','Sales 3%','Qty 3%','GST 3% Taxable Value','CGST Ledger 1.5%','CGST 1.5%','SGST Ledger 1.5%','SGST 1.5%','Total GST 3%','Sales 5%','Qty 5%','GST 5% Taxable Value','CGST Ledger 2.5%','CGST 2.5%','SGST Ledger 2.5%','SGST 2.5%','Total GST 5%','Sales 12%','Qty 12%','GST 12% Taxable Value','CGST Ledger 6%','CGST 6%','SGST Ledger 6%','SGST 6%','Total GST 12%','Sales 18%','Qty 18%','GST 18% Taxable Value','CGST Ledger 9%','CGST 9%','SGST Ledger 9%','SGST 9%','Total GST 18%','Sales 0%','Qty 0%','GST 0% Taxable Value','CGST Ledger 0%','CGST 0%','SGST Ledger 0%','SGST 0%','Total GST 0%');
                }
                
                $columns = array_merge($columns_1,$columns_2);
                
                /*if($report_type == 'hsn_code'){
                    unset($columns[5]);unset($columns[6]);unset($columns[7]);unset($columns[8]);unset($columns[9]);
                    unset($columns[10]);unset($columns[11]);unset($columns[12]);unset($columns[13]);
                }*/
                
                $callback = function() use ($bill_list, $columns,$report_type){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $store_total = array('total_items'=>0,'card_total'=>0,'cash_total'=>0,'ewallet_total'=>0,'voucher_total'=>0,'roundoff_total'=>0,
                    'sale_price_total'=>0,'discount_amount_total'=>0,'net_price_total'=>0,'taxable_value_gst_3_total'=>0,'gst_amount_gst_3_total'=>0,'taxable_value_gst_5_total'=>0,
                    'gst_amount_gst_5_total'=>0,'taxable_value_gst_12_total'=>0,'gst_amount_gst_12_total'=>0,'taxable_value_gst_18_total'=>0,'gst_amount_gst_18_total'=>0,
                    'taxable_value_gst_0_total'=>0,'gst_amount_gst_0_total'=>0,'qty_gst_5_total'=>0,'qty_gst_12_total'=>0,'qty_gst_18_total'=>0,'qty_gst_0_total'=>0,'qty_gst_3_total'=>0);
                    
                    $grand_total = $store_total;
                    
                    for($i=0;$i<count($bill_list);$i++){
                        $total_items = $bill_list[$i]->product_quantity_total;
                        $voucher_total = (isset($bill_list[$i]->voucher_total) && $bill_list[$i]->voucher_total > 0)?$bill_list[$i]->voucher_total:0; 
                        $total_payment_received = $bill_list[$i]->cash_total+$bill_list[$i]->card_total+$bill_list[$i]->ewallet_total+$voucher_total;
                        
                        if($report_type == 'bill'){
                            $array = array($bill_list[$i]->store_name,$bill_list[$i]->store_id_code,$bill_list[$i]->store_gst_no,$bill_list[$i]->order_no,date('j/n/Y',strtotime($bill_list[$i]->created_at)),$bill_list[$i]->customer_name,$bill_list[$i]->customer_gst_no);
                            if($total_items > 0) $array[] = 'BILLING';else $array[] = 'EXCHANGE';
                        }
                        if($report_type == 'date'){
                            $array = array($bill_list[$i]->store_name,$bill_list[$i]->store_id_code,$bill_list[$i]->store_gst_no,date('j/n/Y',strtotime($bill_list[$i]->created_at)),$bill_list[$i]->order_no.' - '.$bill_list[$i]->max_id);
                        }
                        if($report_type == 'month'){
                            $array = array($bill_list[$i]->store_name,$bill_list[$i]->store_id_code,$bill_list[$i]->store_gst_no,date('F-Y',strtotime($bill_list[$i]->created_at)),$bill_list[$i]->order_no.' - '.$bill_list[$i]->max_id);
                        }
                        if($report_type == 'hsn_code'){
                            $array = array($bill_list[$i]->store_name,$bill_list[$i]->store_id_code,$bill_list[$i]->store_gst_no,$bill_list[$i]->hsn_code);
                        }
                        
                        $array[] = 'Qty Pcs - '.$total_items;
                        if($total_items > 0) $array[] = $total_items;else $array[] = '';
                        $array[] = ($bill_list[$i]->cash_total != 0)?'Cash Sale - '.$bill_list[$i]->store_name:'';
                        $array[] = ($bill_list[$i]->cash_total != 0)?$bill_list[$i]->cash_total:'';
                        $array[] = ($bill_list[$i]->card_total != 0)?'Card Sale - '.$bill_list[$i]->store_name:'';
                        $array[] = ($bill_list[$i]->card_total != 0)?$bill_list[$i]->card_total:'';
                        $array[] = ($bill_list[$i]->ewallet_total != 0)?'E-Wallet Sale - '.$bill_list[$i]->store_name:'';
                        $array[] = ($bill_list[$i]->ewallet_total != 0)?$bill_list[$i]->ewallet_total:'';
                        $array[] = $voucher_total;
                        //$round_off = round(($total_payment_received-$bill_list[$i]->net_price_total),2);
                        $round_off = round($total_payment_received-round($bill_list[$i]->net_price_total,2),2);
                        $round_off = ($round_off != -0)?$round_off:0;
                        $array[] = 'Round Off';
                        $array[] = $round_off;
                        $array[] = $bill_list[$i]->sale_price_total;
                        $array[] = round($bill_list[$i]->discount_amount_total,2);
                        $array[] = round($bill_list[$i]->net_price_total,2);
                        
                        $array[] = $bill_list[$i]->store_name.' Sale 3%';
                        if($report_type == 'hsn_code'){
                            $array[] = $bill_list[$i]->gst_3_gst_qty_total;
                        }
                        $array[] = round($bill_list[$i]->discounted_price_gst_3_total,3);
                        $array[] = 'CGST Output 1.50%';
                        $array[] = $gst_1_5 = round($bill_list[$i]->gst_amount_gst_3_total/2,3);
                        $array[] = 'SGST Output 1.50%';
                        $array[] = $gst_1_5 = round($bill_list[$i]->gst_amount_gst_3_total/2,3);        
                        $array[] = $gst_1_5+$gst_1_5;  
                        
                        $array[] = $bill_list[$i]->store_name.' Sale 5%';
                        if($report_type == 'hsn_code'){
                            $array[] = $bill_list[$i]->gst_5_gst_qty_total;
                        }
                        $array[] = round($bill_list[$i]->discounted_price_gst_5_total,3);
                        $array[] = 'CGST Output 2.50%';
                        $array[] = $gst_2_5 = round($bill_list[$i]->gst_amount_gst_5_total/2,3);
                        $array[] = 'SGST Output 2.50%';
                        $array[] = $gst_2_5 = round($bill_list[$i]->gst_amount_gst_5_total/2,3);        
                        $array[] = $gst_2_5+$gst_2_5;  
                        
                        $array[] = $bill_list[$i]->store_name.' Sale 12%';
                        if($report_type == 'hsn_code'){
                            $array[] = $bill_list[$i]->gst_12_gst_qty_total;
                        }
                        $array[] = round($bill_list[$i]->discounted_price_gst_12_total,3);
                        $array[] = 'CGST Output 6%';
                        $array[] = $gst_6 = round($bill_list[$i]->gst_amount_gst_12_total/2,3);
                        $array[] = 'SGST Output 6%';
                        $array[] = $gst_6 = round($bill_list[$i]->gst_amount_gst_12_total/2,3);        
                        $array[] = $gst_6+$gst_6;  
                        
                        $array[] = $bill_list[$i]->store_name.' Sale 18%';
                        if($report_type == 'hsn_code'){
                            $array[] = $bill_list[$i]->gst_18_gst_qty_total;
                        }
                        $array[] = round($bill_list[$i]->discounted_price_gst_18_total,3);
                        $array[] = 'CGST Output 9%';
                        $array[] = $gst_9 = round($bill_list[$i]->gst_amount_gst_18_total/2,3);
                        $array[] = 'SGST Output 9%';
                        $array[] = $gst_9 = round($bill_list[$i]->gst_amount_gst_18_total/2,3);        
                        $array[] = $gst_9+$gst_9;  
                        
                        $array[] = $bill_list[$i]->store_name.' Sale 0%';
                        if($report_type == 'hsn_code'){
                            $array[] = $bill_list[$i]->gst_0_gst_qty_total;
                        }
                        $array[] = round($bill_list[$i]->discounted_price_gst_0_total,3);
                        $array[] = 'CGST Output 0%';
                        $array[] = $gst_0 = round($bill_list[$i]->gst_amount_gst_0_total/2,3);
                        $array[] = 'SGST Output 0%';
                        $array[] = $gst_0 = round($bill_list[$i]->gst_amount_gst_0_total/2,3);        
                        $array[] = $gst_0+$gst_0;  
                        
                        if($report_type == 'hsn_code'){
                            unset($array[5]);unset($array[6]);unset($array[7]);unset($array[8]);unset($array[9]);
                            unset($array[10]);unset($array[11]);unset($array[12]);unset($array[13]);
                        }
                        
                        fputcsv($file, $array);
                        
                        $store_total['total_items']+=$total_items; 
                        $store_total['card_total']+=$bill_list[$i]->card_total;
                        $store_total['cash_total']+=$bill_list[$i]->cash_total;
                        $store_total['ewallet_total']+=$bill_list[$i]->ewallet_total;
                        $store_total['voucher_total']+=$voucher_total;
                        $store_total['roundoff_total']+=$round_off;
                        $store_total['sale_price_total']+=$bill_list[$i]->sale_price_total;
                        $store_total['discount_amount_total']+=$bill_list[$i]->discount_amount_total;
                        $store_total['net_price_total']+=$bill_list[$i]->net_price_total;
                        $store_total['taxable_value_gst_3_total']+=$bill_list[$i]->discounted_price_gst_3_total;
                        $store_total['gst_amount_gst_3_total']+=$bill_list[$i]->gst_amount_gst_3_total;
                        $store_total['taxable_value_gst_5_total']+=$bill_list[$i]->discounted_price_gst_5_total;
                        $store_total['gst_amount_gst_5_total']+=$bill_list[$i]->gst_amount_gst_5_total;
                        $store_total['taxable_value_gst_12_total']+=$bill_list[$i]->discounted_price_gst_12_total;
                        $store_total['gst_amount_gst_12_total']+=$bill_list[$i]->gst_amount_gst_12_total;
                        $store_total['taxable_value_gst_18_total']+=$bill_list[$i]->discounted_price_gst_18_total;
                        $store_total['gst_amount_gst_18_total']+=$bill_list[$i]->gst_amount_gst_18_total;
                        $store_total['taxable_value_gst_0_total']+=$bill_list[$i]->discounted_price_gst_0_total;
                        $store_total['gst_amount_gst_0_total']+=$bill_list[$i]->gst_amount_gst_0_total;
                        if($report_type == 'hsn_code'){
                            $store_total['qty_gst_3_total']+=$bill_list[$i]->gst_3_gst_qty_total;
                            $store_total['qty_gst_5_total']+=$bill_list[$i]->gst_5_gst_qty_total;
                            $store_total['qty_gst_12_total']+=$bill_list[$i]->gst_12_gst_qty_total;
                            $store_total['qty_gst_18_total']+=$bill_list[$i]->gst_18_gst_qty_total;
                            $store_total['qty_gst_0_total']+=$bill_list[$i]->gst_0_gst_qty_total;
                        }
                        
                        $grand_total['total_items']+=$total_items; 
                        $grand_total['card_total']+=$bill_list[$i]->card_total;
                        $grand_total['cash_total']+=$bill_list[$i]->cash_total;
                        $grand_total['ewallet_total']+=$bill_list[$i]->ewallet_total;
                        $grand_total['voucher_total']+=$voucher_total;
                        $grand_total['roundoff_total']+=$round_off;
                        $grand_total['sale_price_total']+=$bill_list[$i]->sale_price_total;
                        $grand_total['discount_amount_total']+=$bill_list[$i]->discount_amount_total;
                        $grand_total['net_price_total']+=$bill_list[$i]->net_price_total;
                        $grand_total['taxable_value_gst_3_total']+=$bill_list[$i]->discounted_price_gst_3_total;
                        $grand_total['gst_amount_gst_3_total']+=$bill_list[$i]->gst_amount_gst_3_total;
                        $grand_total['taxable_value_gst_5_total']+=$bill_list[$i]->discounted_price_gst_5_total;
                        $grand_total['gst_amount_gst_5_total']+=$bill_list[$i]->gst_amount_gst_5_total;
                        $grand_total['taxable_value_gst_12_total']+=$bill_list[$i]->discounted_price_gst_12_total;
                        $grand_total['gst_amount_gst_12_total']+=$bill_list[$i]->gst_amount_gst_12_total;
                        $grand_total['taxable_value_gst_18_total']+=$bill_list[$i]->discounted_price_gst_18_total;
                        $grand_total['gst_amount_gst_18_total']+=$bill_list[$i]->gst_amount_gst_18_total;
                        $grand_total['taxable_value_gst_0_total']+=$bill_list[$i]->discounted_price_gst_0_total;
                        $grand_total['gst_amount_gst_0_total']+=$bill_list[$i]->gst_amount_gst_0_total;
                        if($report_type == 'hsn_code'){
                            $grand_total['qty_gst_3_total']+=$bill_list[$i]->gst_3_gst_qty_total;
                            $grand_total['qty_gst_5_total']+=$bill_list[$i]->gst_5_gst_qty_total;
                            $grand_total['qty_gst_12_total']+=$bill_list[$i]->gst_12_gst_qty_total;
                            $grand_total['qty_gst_18_total']+=$bill_list[$i]->gst_18_gst_qty_total;
                            $grand_total['qty_gst_0_total']+=$bill_list[$i]->gst_0_gst_qty_total;
                        }
                        
                        if((isset($bill_list[$i+1]->store_name) && $bill_list[$i]->store_name != $bill_list[$i+1]->store_name) || (!isset($bill_list[$i+1]->store_name)) ){
                            
                            if($report_type == 'bill') $array1 = array('Total','','','','','','','');elseif($report_type == 'hsn_code') $array1 = array('Total','','','');else $array1 = array('Total','','','','');
                            
                            $gst_1_5 = round($store_total['gst_amount_gst_3_total']/2,3);
                            $gst_2_5 = round($store_total['gst_amount_gst_5_total']/2,3);
                            $gst_6 = round($store_total['gst_amount_gst_12_total']/2,3);
                            $gst_9 = round($store_total['gst_amount_gst_18_total']/2,3);
                            $gst_0 = round($store_total['gst_amount_gst_0_total']/2,3);
                            
                            if($report_type == 'hsn_code'){
                                $array2 = array('Qty Pcs - '.$store_total['total_items'],$store_total['total_items'],
                                $store_total['sale_price_total'],round($store_total['discount_amount_total'],2),round($store_total['net_price_total'],2),
                                $bill_list[$i]->store_name.' Sale 3%',$store_total['qty_gst_3_total'],round($store_total['taxable_value_gst_3_total'],3),'CGST Output 1.50%',$gst_1_5,'SGST Output 1.50%',$gst_1_5,($gst_1_5+$gst_1_5),    
                                $bill_list[$i]->store_name.' Sale 5%',$store_total['qty_gst_5_total'],round($store_total['taxable_value_gst_5_total'],3),'CGST Output 2.50%',$gst_2_5,'SGST Output 2.50%',
                                $gst_2_5,($gst_2_5+$gst_2_5),$bill_list[$i]->store_name.' Sale 12%',$store_total['qty_gst_12_total'],round($store_total['taxable_value_gst_12_total'],3),'CGST Output 6%',$gst_6,'SGST Output 6%',$gst_6,($gst_6+$gst_6),
                                $bill_list[$i]->store_name.' Sale 18%',$store_total['qty_gst_18_total'],round($store_total['taxable_value_gst_18_total'],3),'CGST Output 9%',$gst_9,'SGST Output 9%',$gst_9,($gst_9+$gst_9),
                                $bill_list[$i]->store_name.' Sale 0%',$store_total['qty_gst_0_total'],round($store_total['taxable_value_gst_0_total'],3),'CGST Output 0%',$gst_0,'SGST Output 0%',$gst_0,($gst_0+$gst_0));
                            }else{
                                $array2 = array('Qty Pcs - '.$store_total['total_items'],$store_total['total_items'],'Cash Sale - '.$bill_list[$i]->store_name,$store_total['cash_total'],'Card Sale - '.$bill_list[$i]->store_name,$store_total['card_total'],
                                'E-Wallet Sale - '.$bill_list[$i]->store_name,$store_total['ewallet_total'],$store_total['voucher_total'],'Round Off',$store_total['roundoff_total'],
                                $store_total['sale_price_total'],round($store_total['discount_amount_total'],2),round($store_total['net_price_total'],2),
                                $bill_list[$i]->store_name.' Sale 3%',round($store_total['taxable_value_gst_3_total'],3),'CGST Output 1.50%',$gst_1_5,'SGST Output 1.50%',$gst_1_5,($gst_1_5+$gst_1_5),    
                                $bill_list[$i]->store_name.' Sale 5%',round($store_total['taxable_value_gst_5_total'],3),'CGST Output 2.50%',$gst_2_5,'SGST Output 2.50%',$gst_2_5,($gst_2_5+$gst_2_5),
                                $bill_list[$i]->store_name.' Sale 12%',round($store_total['taxable_value_gst_12_total'],3),'CGST Output 6%',$gst_6,'SGST Output 6%',$gst_6,($gst_6+$gst_6),
                                $bill_list[$i]->store_name.' Sale 18%',round($store_total['taxable_value_gst_18_total'],3),'CGST Output 9%',$gst_9,'SGST Output 9%',$gst_9,($gst_9+$gst_9),
                                $bill_list[$i]->store_name.' Sale 0%',round($store_total['taxable_value_gst_0_total'],3),'CGST Output 0%',$gst_0,'SGST Output 0%',$gst_0,($gst_0+$gst_0));
                            }
                            
                            $array = array_merge($array1,$array2);
                            
                            /*if($report_type == 'hsn_code'){
                                unset($array[5]);unset($array[6]);unset($array[7]);unset($array[8]);unset($array[9]);
                                unset($array[10]);unset($array[11]);unset($array[12]);unset($array[13]);
                            }*/
                            
                            fputcsv($file, $array);
                            
                            //reset store total array
                            $store_total = array('total_items'=>0,'card_total'=>0,'cash_total'=>0,'ewallet_total'=>0,'voucher_total'=>0,'roundoff_total'=>0,
                            'sale_price_total'=>0,'discount_amount_total'=>0,'net_price_total'=>0,'taxable_value_gst_3_total'=>0,'gst_amount_gst_3_total'=>0,'taxable_value_gst_5_total'=>0,
                            'gst_amount_gst_5_total'=>0,'taxable_value_gst_12_total'=>0,'gst_amount_gst_12_total'=>0,'taxable_value_gst_18_total'=>0,'gst_amount_gst_18_total'=>0,
                            'taxable_value_gst_0_total'=>0,'gst_amount_gst_0_total'=>0,'qty_gst_5_total'=>0,'qty_gst_12_total'=>0,'qty_gst_18_total'=>0,'qty_gst_0_total'=>0,'qty_gst_3_total'=>0);
                        }
                    }
                    
                    if($report_type == 'bill') $array1 = array('Grand Total','','','','','','','');elseif($report_type == 'hsn_code') $array1 = array('Total','','','');else $array1 = array('Total','','','','');
                    
                    $gst_1_5 = round($grand_total['gst_amount_gst_3_total']/2,3);
                    $gst_2_5 = round($grand_total['gst_amount_gst_5_total']/2,3);
                    $gst_6 = round($grand_total['gst_amount_gst_12_total']/2,3);
                    $gst_9 = round($grand_total['gst_amount_gst_18_total']/2,3);
                    $gst_0 = round($grand_total['gst_amount_gst_0_total']/2,3);
                    
                    if($report_type == 'hsn_code'){
                        $array2 = array('Qty Pcs - '.$grand_total['total_items'],$grand_total['total_items'],
                        $grand_total['sale_price_total'],round($grand_total['discount_amount_total'],2),round($grand_total['net_price_total'],2),
                        'Sale 3%',$grand_total['qty_gst_3_total'],round($grand_total['taxable_value_gst_3_total'],3),'CGST Output 1.50%',$gst_1_5,'SGST Output 1.50%',$gst_1_5,($gst_1_5+$gst_1_5),    
                        'Sale 5%',$grand_total['qty_gst_5_total'],round($grand_total['taxable_value_gst_5_total'],3),'CGST Output 2.50%',$gst_2_5,'SGST Output 2.50%',$gst_2_5,($gst_2_5+$gst_2_5),
                        'Sale 12%',$grand_total['qty_gst_12_total'],round($grand_total['taxable_value_gst_12_total'],3),'CGST Output 6%',$gst_6,'SGST Output 6%',$gst_6,($gst_6+$gst_6),
                        'Sale 18%',$grand_total['qty_gst_18_total'],round($grand_total['taxable_value_gst_18_total'],3),'CGST Output 9%',$gst_9,'SGST Output 9%',$gst_9,($gst_9+$gst_9),
                        'Sale 0%',$grand_total['qty_gst_0_total'],round($grand_total['taxable_value_gst_0_total'],3),'CGST Output 0%',$gst_9,'SGST Output 0%',$gst_0,($gst_0+$gst_0));
                    }else{
                        $array2 = array('Qty Pcs - '.$grand_total['total_items'],$grand_total['total_items'],'Cash Sale',$grand_total['cash_total'],'Card Sale',
                        $grand_total['card_total'],'E-Wallet Sale',$grand_total['ewallet_total'],$grand_total['voucher_total'],'Round Off',$grand_total['roundoff_total'],
                        $grand_total['sale_price_total'],round($grand_total['discount_amount_total'],2),round($grand_total['net_price_total'],2),
                        'Sale 3%',round($grand_total['taxable_value_gst_3_total'],3),'CGST Output 1.50%',$gst_1_5,'SGST Output 1.50%',$gst_1_5,($gst_1_5+$gst_1_5),    
                        'Sale 5%',round($grand_total['taxable_value_gst_5_total'],3),'CGST Output 2.50%',$gst_2_5,'SGST Output 2.50%',$gst_2_5,($gst_2_5+$gst_2_5),
                        'Sale 12%',round($grand_total['taxable_value_gst_12_total'],3),'CGST Output 6%',$gst_6,'SGST Output 6%',$gst_6,($gst_6+$gst_6),
                        'Sale 18%',round($grand_total['taxable_value_gst_18_total'],3),'CGST Output 9%',$gst_9,'SGST Output 9%',$gst_9,($gst_9+$gst_9),
                        'Sale 0%',round($grand_total['taxable_value_gst_0_total'],3),'CGST Output 0%',$gst_9,'SGST Output 0%',$gst_0,($gst_0+$gst_0));
                    }
                    
                    $array = array_merge($array1,$array2);
                    
                    /*if($report_type == 'hsn_code'){
                        unset($array[5]);unset($array[5]);unset($array[5]);unset($array[8]);unset($array[9]);
                        unset($array[10]);unset($array[11]);unset($array[12]);unset($array[13]);
                    }*/
                            
                    fputcsv($file, $array);
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            //$bill_list = $bill_list->paginate(50);
            //$laQuery = \DB::getQueryLog();print_r($laQuery);//exit;
            
            $whereArray = !empty($data['state_id'])?array('state_id'=>$data['state_id']):array();
            $store_list = CommonHelper::getStoresList($whereArray);
            
            $state_list = \DB::table('state_list as s')
            ->where('s.is_deleted',0)
            ->whereRaw("s.id IN(SELECT DISTINCT store_state_id FROM store_products_demand)")
            ->orderBy('state_name')        
            ->get()->toArray();
            
            return view('admin/report_store_to_customer',array('error_message'=>$error_message,'bill_list'=>$bill_list,'report_type'=>$report_type,'store_list'=>$store_list,'state_list'=>$state_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/report_store_to_customer',array('error_message'=>$e->getMessage()));
        }
    }
    
    function warehouseToStoreSalesReport_Previous(Request $request){
        try{
            $data = $request->all();
            $error_message = '';
            $invoice_list_sum = $gst_type_total = '';
            $report_type = (isset($data['report_type']) && !empty($data['report_type']))?$data['report_type']:'bill';
            
            $store_list = CommonHelper::getStoresList();
            $store_type = (!empty($data['store_type']))?array(trim($data['store_type'])):array(1,2);
            
            //\DB::enableQueryLog();
            $invoice_list = \DB::table('store_products_demand as spd')
            ->join('store_products_demand_inventory as spdi','spdi.demand_id', '=', 'spd.id')        
            ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')         
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')        
            ->join('store as s','s.id', '=', 'spd.store_id')         
            ->leftJoin('state_list as sl','sl.id', '=', 'spd.store_state_id')        
            ->wherein('s.store_type',$store_type)         
            ->where('spd.demand_type','inventory_push')        
            ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))        
            ->where('spd.is_deleted',0)
            ->where('ppmi.is_deleted',0)
            //->where('ppmi.arnon_inventory',0)
            ->where('spdi.is_deleted',0);
            
            if(strtolower($report_type) == 'bill'){
                $invoice_list = $invoice_list->groupByRaw('spdi.demand_id,spdi.store_gst_percent');
            }else{
                $invoice_list = $invoice_list->groupByRaw('spdi.demand_id,spdi.store_gst_percent,ppm.hsn_code');        
            }
            
            $invoice_list = $invoice_list->selectRaw("spd.id,spd.store_data,spd.invoice_no,spd.demand_status,spd.created_at as bill_date,s.store_name,s.gst_no,s.gst_name,spdi.store_gst_percent,sl.state_name,ppm.hsn_code,SUM(spdi.store_base_rate) as base_rate_total,SUM(spdi.store_gst_amount) as gst_amount_total,COUNT(spdi.id) as units_total")
            ->orderByRaw('s.store_name,spd.id');
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            if(CommonHelper::dateDiff($search_date['start_date'],$search_date['end_date']) > 180){
                throw new Exception('Date difference should not be more than 180 days');
            }
            
            $invoice_list = $invoice_list->whereRaw("spd.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")->orderBy('spd.created_at','ASC');
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $invoice_list = $invoice_list->where('s.id',$data['s_id']);
            }
            
            $invoice_list = json_decode(json_encode($invoice_list->get()->toArray()),true);//print_r($invoice_list);
            
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            
            /* Invoice list is group by demand_id and gst_percent or demand_id gst_percent and hsn_code
            These different gst value rows are merged in to single row in the following code */
            
            $invoices = array();
            $index = false;
            for($i=0;$i<count($invoice_list);$i++){
                $invoice_data = $invoice_list[$i];
                $store_gst_percent = str_replace('.00','',$invoice_data['store_gst_percent']);
                $invoice_data['base_rate_total_'.$store_gst_percent] = $invoice_data['base_rate_total'];
                $invoice_data['gst_amount_total_'.$store_gst_percent] = $invoice_data['gst_amount_total'];
                $invoice_data['units_total_'.$store_gst_percent] = $invoice_data['units_total'];
                
                if(strtolower($report_type) == 'bill'){
                    $index = array_search($invoice_data['id'],array_column($invoices,'id'));
                }else{
                    for($q=0;$q<count($invoices);$q++){
                        if($invoices[$q]['id'] == $invoice_data['id'] && $invoices[$q]['hsn_code'] == $invoice_data['hsn_code']){
                            $index = $q;
                            break;
                        }else{
                            $index = false;
                        }
                    }
                }
                
                if($index !== false){
                    $invoice_data_1 = $invoices[$index];
                    $invoice_data = array_merge($invoice_data,$invoice_data_1);
                    $invoices[$index] = $invoice_data;
                }else{
                    $invoices[] = $invoice_data;
                }
            }
            
            $invoice_list = $invoices;
            
            //  print_r($invoice_list);
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array(
                    'Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=report_warehouse_to_store.csv',
                    'Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0'
                );

                $columns = array('Supplier','Supplier State','Recipient Name','Recipient Location','Recipient State',
                'Recipient GST No','Bill No','HSN Code','Bill Date','Sale Qty','Taxable Value 5%','IGST 5%','CGST 5%','SGST 5%',
                'Total Tax 5%','Total Value 5%','Taxable Value 12%','IGST 12%','CGST 6%','SGST 6%','Total Tax 12%','Total Value 12%',
                'Taxable Value 18%','IGST 18%','CGST 9%','SGST 9%','Total Tax 18%','Total Value 18%',    
                'Taxable Value 0%','IGST 0%','CGST 0%','SGST 0%','Total Tax 0%','Total Value 0%','Total Net Amount');

                $callback = function() use ($invoice_list, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    $total_data = array('units_total'=>0,'taxable_value_5'=>0,'igst_5'=>0,'cgst_2_5'=>0,'sgst_2_5'=>0,'total_tax_5'=>0,'total_value_5'=>0,
                    'taxable_value_12'=>0,'igst_12'=>0,'cgst_6'=>0,'sgst_6'=>0,'total_tax_12'=>0,'total_value_12'=>0,
                    'taxable_value_18'=>0,'igst_18'=>0,'cgst_9'=>0,'sgst_9'=>0,'total_tax_18'=>0,'total_value_18'=>0,    
                    'taxable_value_0'=>0,'igst_0'=>0,'cgst_0'=>0,'sgst_0'=>0,'total_tax_0'=>0,'total_value_0'=>0,'total_net_amount'=>0);
                    
                    for($i=0;$i<count($invoice_list);$i++){
                        
                        $units_total = $taxable_value_5 = $igst_5 = $cgst_2_5 = $sgst_2_5 = $total_tax_5 = $total_value_5 = 0;
                        $taxable_value_12 = $igst_12 = $cgst_6 = $sgst_6 = $total_tax_12 = $total_value_12 = 0;
                        $taxable_value_18 = $igst_18 = $cgst_9 = $sgst_9 = $total_tax_18 = $total_value_18 = 0;
                        $taxable_value_0 = $igst_0 = $cgst_0 = $sgst_0 = $total_tax_0 = $total_value_0 = 0;

                        $store_data = json_decode($invoice_list[$i]['store_data'],true);
                        $gst_type = CommonHelper::getGSTType($store_data['gst_no']);
                        
                        $units_total = (isset($invoice_list[$i]['units_total_0'])?$invoice_list[$i]['units_total_0']:0)+(isset($invoice_list[$i]['units_total_5'])?$invoice_list[$i]['units_total_5']:0)+(isset($invoice_list[$i]['units_total_12'])?$invoice_list[$i]['units_total_12']:0);
                        $array = array('Kiaasa-HO (Noida)','UP',$store_data['gst_name'],$store_data['store_name'],$invoice_list[$i]['state_name'],$store_data['gst_no'],$invoice_list[$i]['invoice_no'],$invoice_list[$i]['hsn_code'],date('d-m-Y',strtotime($invoice_list[$i]['bill_date'])),$units_total);
                        
                        if(isset($invoice_list[$i]['base_rate_total_5'])){
                            $array[] = $taxable_value_5 = $invoice_list[$i]['base_rate_total_5'];
                            if($gst_type == 2) $array[] = $igst_5 = $invoice_list[$i]['gst_amount_total_5'];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_2_5 = round($invoice_list[$i]['gst_amount_total_5']/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_2_5 = round($invoice_list[$i]['gst_amount_total_5']/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_5 = $invoice_list[$i]['gst_amount_total_5'];else $array[] = '';
                            $array[] = $total_value_5 = $invoice_list[$i]['base_rate_total_5']+$invoice_list[$i]['gst_amount_total_5'];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        if(isset($invoice_list[$i]['base_rate_total_12'])){
                            $array[] = $taxable_value_12 = $invoice_list[$i]['base_rate_total_12'];
                            if($gst_type == 2) $array[] = $igst_12 = $invoice_list[$i]['gst_amount_total_12'];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_6 = round($invoice_list[$i]['gst_amount_total_12']/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_6 = round($invoice_list[$i]['gst_amount_total_12']/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_12 = $invoice_list[$i]['gst_amount_total_12'];else $array[] = '';
                            $array[] = $total_value_12 = $invoice_list[$i]['base_rate_total_12']+$invoice_list[$i]['gst_amount_total_12'];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        if(isset($invoice_list[$i]['base_rate_total_18'])){
                            $array[] = $taxable_value_18 = $invoice_list[$i]['base_rate_total_18'];
                            if($gst_type == 2) $array[] = $igst_18 = $invoice_list[$i]['gst_amount_total_18'];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_9 = round($invoice_list[$i]['gst_amount_total_18']/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_9 = round($invoice_list[$i]['gst_amount_total_18']/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_18 = $invoice_list[$i]['gst_amount_total_18'];else $array[] = '';
                            $array[] = $total_value_18 = $invoice_list[$i]['base_rate_total_18']+$invoice_list[$i]['gst_amount_total_18'];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        if(isset($invoice_list[$i]['base_rate_total_0'])){
                            $array[] = $taxable_value_0 = $invoice_list[$i]['base_rate_total_0'];
                            if($gst_type == 2) $array[] = $igst_0 = $invoice_list[$i]['gst_amount_total_0'];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_0 = round($invoice_list[$i]['gst_amount_total_0']/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_0 = round($invoice_list[$i]['gst_amount_total_0']/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_0 = $invoice_list[$i]['gst_amount_total_0'];else $array[] = '';
                            $array[] = $total_value_0 = $invoice_list[$i]['base_rate_total_0']+$invoice_list[$i]['gst_amount_total_0'];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $total_net_amount = $total_value_5+$total_value_12+$total_value_18+$total_value_0;
                        $array[] = $total_net_amount;
                        
                        fputcsv($file, $array);
                        
                        $total_data['units_total']+=$units_total;
                        $total_data['taxable_value_5']+=$taxable_value_5; 
                        $total_data['igst_5']+=$igst_5; 
                        $total_data['cgst_2_5']+=$cgst_2_5; 
                        $total_data['sgst_2_5']+=$sgst_2_5; 
                        $total_data['total_tax_5']+=$total_tax_5; 
                        $total_data['total_value_5']+=$total_value_5; 

                        $total_data['taxable_value_12']+=$taxable_value_12; 
                        $total_data['igst_12']+=$igst_12; 
                        $total_data['cgst_6']+=$cgst_6; 
                        $total_data['sgst_6']+=$sgst_6; 
                        $total_data['total_tax_12']+=$total_tax_12; 
                        $total_data['total_value_12']+=$total_value_12; 
                        
                        $total_data['taxable_value_18']+=$taxable_value_18; 
                        $total_data['igst_18']+=$igst_18; 
                        $total_data['cgst_9']+=$cgst_9; 
                        $total_data['sgst_9']+=$sgst_9; 
                        $total_data['total_tax_18']+=$total_tax_18; 
                        $total_data['total_value_18']+=$total_value_18; 

                        $total_data['taxable_value_0']+=$taxable_value_0; 
                        $total_data['igst_0']+=$igst_0; 
                        $total_data['cgst_0']+=$cgst_0; 
                        $total_data['sgst_0']+=$sgst_0; 
                        $total_data['total_tax_0']+=$total_tax_0; 
                        $total_data['total_value_0']+=$total_value_0; 
                        $total_data['total_net_amount']+=$total_net_amount; 
                    }
                    
                    $array = array('Total','','','','','','','','',$total_data['units_total'],$total_data['taxable_value_5'],
                    $total_data['igst_5'],$total_data['cgst_2_5'],$total_data['sgst_2_5'],$total_data['total_tax_5'],$total_data['total_value_5'],
                    $total_data['taxable_value_12'],$total_data['igst_12'],$total_data['cgst_6'],$total_data['sgst_6'],$total_data['total_tax_12'],$total_data['total_value_12'],
                    $total_data['taxable_value_18'],$total_data['igst_18'],$total_data['cgst_9'],$total_data['sgst_9'],$total_data['total_tax_18'],$total_data['total_value_18'],    
                    $total_data['taxable_value_0'],$total_data['igst_0'],$total_data['cgst_0'],$total_data['sgst_0'],$total_data['total_tax_0'],$total_data['total_value_0'],$total_data['total_net_amount']);
                    
                    fputcsv($file, $array);
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
            
            return view('admin/report_warehouse_to_store',array('error_message'=>$error_message,'invoice_list'=>$invoice_list,'invoice_list_sum'=>$invoice_list_sum,'gst_type_total'=>$gst_type_total,'store_list'=>$store_list,'report_type'=>$report_type));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/report_warehouse_to_store',array('error_message'=>$e->getMessage()));
        }
    }
    
    function warehouseToStoreSalesReport(Request $request){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $error_message = '';
            $invoice_list_sum = $gst_type_total = '';
            $report_type = (isset($data['report_type']) && !empty($data['report_type']))?$data['report_type']:'bill';
            $store_type = (!empty($data['store_type']))?array(trim($data['store_type'])):array(1,2);
            $is_fake_inventory_user = CommonHelper::isFakeInventoryUser();
            $fake_inventory = $is_fake_inventory_user?[0,1]:[0];
            
            //\DB::enableQueryLog();
            if(strtolower($report_type) == 'bill'){
                $invoice_list = \DB::table('store_products_demand as spd')
                ->join('store as s','s.id', '=', 'spd.store_id')         
                ->leftJoin('state_list as sl','sl.id', '=', 'spd.store_state_id')        
                ->wherein('s.store_type',$store_type)         
                ->where('spd.demand_type','inventory_push')        
                ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                ->wherein('spd.fake_inventory',$fake_inventory)         
                ->where('spd.is_deleted',0);
                
                $invoice_list = $invoice_list->selectRaw("spd.id,spd.store_data,spd.invoice_no,spd.demand_status,spd.created_at as bill_date,spd.total_data,s.store_name,s.store_id_code,s.gst_no,s.gst_name,sl.state_name");
            }
            
            if(strtolower($report_type) == 'hsn_code'){
                $invoice_list = \DB::table('store_products_demand as spd')
                ->join('store_products_demand_inventory as spdi','spdi.demand_id', '=', 'spd.id')        
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')         
                ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')        
                ->join('store as s','s.id', '=', 'spd.store_id')         
                ->leftJoin('state_list as sl','sl.id', '=', 'spd.store_state_id')        
                ->wherein('s.store_type',$store_type)         
                ->where('spd.demand_type','inventory_push')        
                ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))        
                ->where('spd.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->wherein('spd.fake_inventory',$fake_inventory)
                ->wherein('spdi.fake_inventory',$fake_inventory)
                ->wherein('ppm.fake_inventory',$fake_inventory)
                ->wherein('ppmi.fake_inventory',$fake_inventory)    
                ->where('spdi.demand_status',1)        
                ->where('spdi.is_deleted',0);

                $invoice_list = $invoice_list->groupByRaw('spdi.demand_id,ppm.hsn_code');        
                $invoice_list = $invoice_list->selectRaw("spd.id,spd.store_data,spd.invoice_no,spd.demand_status,spd.created_at as bill_date,spd.total_data_hsn,s.store_name,s.store_id_code,s.gst_no,s.gst_name,sl.state_name,ppm.hsn_code,COUNT(spdi.id) as units_total");
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                if(CommonHelper::dateDiff($search_date['start_date'],$search_date['end_date']) > 365){
                    throw new Exception('Date difference should not be more than 365 days');
                }
            }else{
                if(CommonHelper::dateDiff($search_date['start_date'],$search_date['end_date']) > 180){
                    throw new Exception('Date difference should not be more than 180 days');
                }
            }
            
            $invoice_list = $invoice_list->whereRaw("spd.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")->orderBy('spd.created_at','ASC');
            
            if(isset($data['s_id']) && !empty($data['s_id'])){
                $invoice_list = $invoice_list->where('s.id',$data['s_id']);
            }
            
            if(isset($data['state_id']) && !empty($data['state_id'])){
                $invoice_list = $invoice_list->where('s.state_id',$data['state_id']);
            }
            
            $invoice_list = json_decode(json_encode($invoice_list->get()->toArray()),true);
            
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array(
                    'Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=report_warehouse_to_store.csv',
                    'Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0'
                );

                $columns = array('Supplier','Supplier State','Recipient Name','Recipient Location','Recipient Code','Recipient State',
                'Recipient GST No','Bill No','HSN Code','Bill Date','Sale Qty',
                'Qty 3%','Taxable Value 3%','IGST 3%','CGST 3%','SGST 3%','Total Tax 3%','Total Value 3%',    
                'Qty 5%','Taxable Value 5%','IGST 5%','CGST 5%','SGST 5%','Total Tax 5%','Total Value 5%',
                'Qty 12%','Taxable Value 12%','IGST 12%','CGST 6%','SGST 6%','Total Tax 12%','Total Value 12%',
                'Qty 18%','Taxable Value 18%','IGST 18%','CGST 9%','SGST 9%','Total Tax 18%','Total Value 18%',    
                'Qty 0%','Taxable Value 0%','IGST 0%','CGST 0%','SGST 0%','Total Tax 0%','Total Value 0%','Total Net Amount');

                $callback = function() use ($invoice_list,$report_type, $columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    $total_data = array('units_total'=>0,
                    'taxable_value_3'=>0,'igst_3'=>0,'cgst_1_5'=>0,'sgst_1_5'=>0,'total_tax_3'=>0,'total_value_3'=>0,    
                    'taxable_value_5'=>0,'igst_5'=>0,'cgst_2_5'=>0,'sgst_2_5'=>0,'total_tax_5'=>0,'total_value_5'=>0,
                    'taxable_value_12'=>0,'igst_12'=>0,'cgst_6'=>0,'sgst_6'=>0,'total_tax_12'=>0,'total_value_12'=>0,
                    'taxable_value_18'=>0,'igst_18'=>0,'cgst_9'=>0,'sgst_9'=>0,'total_tax_18'=>0,'total_value_18'=>0,    
                    'taxable_value_0'=>0,'igst_0'=>0,'cgst_0'=>0,'sgst_0'=>0,'total_tax_0'=>0,'total_value_0'=>0,'total_net_amount'=>0,
                    'qty_3'=>0,'qty_5'=>0,'qty_12'=>0,'qty_18'=>0,'qty_0'=>0);
                    
                    for($i=0;$i<count($invoice_list);$i++){
                        
                        $taxable_value_3 = $igst_3 = $cgst_1_5 = $sgst_1_5 = $total_tax_3 = $total_value_3 = 0;
                        $units_total = $taxable_value_5 = $igst_5 = $cgst_2_5 = $sgst_2_5 = $total_tax_5 = $total_value_5 = 0;
                        $taxable_value_12 = $igst_12 = $cgst_6 = $sgst_6 = $total_tax_12 = $total_value_12 = 0;
                        $taxable_value_18 = $igst_18 = $cgst_9 = $sgst_9 = $total_tax_18 = $total_value_18 = 0;
                        $taxable_value_0 = $igst_0 = $cgst_0 = $sgst_0 = $total_tax_0 = $total_value_0 = 0;
                        $qty_3 = $qty_5 = $qty_12 = $qty_18 = $qty_0 = 0; 

                        $store_data = json_decode($invoice_list[$i]['store_data'],true);
                        //$total_info = json_decode($invoice_list[$i]['total_data'],true);
                        $total_info = ($report_type == 'hsn_code')?json_decode($invoice_list[$i]['total_data_hsn'],true):json_decode($invoice_list[$i]['total_data'],true);
                        $gst_type = CommonHelper::getGSTType($store_data['gst_no']);
                        
                        //$units_total = $total_info['total_qty'];
                        $hsn_code = isset($invoice_list[$i]['hsn_code'])?$invoice_list[$i]['hsn_code']:'';
                        //$units_total = ($report_type == 'bill')?$total_info['total_qty']:$total_info['total_data'][$hsn_code]['total_qty'];
                        
                        if($report_type == 'bill'){
                            $units_total = $total_info['total_qty'];
                        }else{
                            $units_total = isset($total_info['total_data'][$hsn_code]['total_qty'])?$total_info['total_data'][$hsn_code]['total_qty']:0;
                        }
                        
                        $array = array('Kiaasa-HO (Noida)','UP',$store_data['gst_name'],$store_data['store_name'],$invoice_list[$i]['store_id_code'],$invoice_list[$i]['state_name'],$store_data['gst_no'],$invoice_list[$i]['invoice_no'],$hsn_code,date('d-m-Y',strtotime($invoice_list[$i]['bill_date'])),$units_total);
                        
                        $key = ($report_type == 'bill')?'3':$hsn_code.'_3';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_3 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_3 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_3 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_1_5 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_1_5 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_3 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_3 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $key = ($report_type == 'bill')?'5':$hsn_code.'_5';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_5 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_5 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_5 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_2_5 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_2_5 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_5 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_5 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $key = ($report_type == 'bill')?'12':$hsn_code.'_12';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_12 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_12 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_12 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_6 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_6 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_12 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_12 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $key = ($report_type == 'bill')?'18':$hsn_code.'_18';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_18 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_18 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_18 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_9 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_9 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_18 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_18 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $key = ($report_type == 'bill')?'0':$hsn_code.'_0';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_0 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_0 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_0 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_0 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_0 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_0 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_0 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        //$total_net_amount = ($report_type == 'bill')?$total_info['total_value']:$total_info['total_data'][$hsn_code]['total_value'];
                        if($report_type == 'bill'){
                            $total_net_amount = $total_info['total_value'];
                        }else{
                            $total_net_amount = isset($total_info['total_data'][$hsn_code]['total_value'])?$total_info['total_data'][$hsn_code]['total_value']:0;
                        }
                        
                        $array[] = $total_net_amount;
                        
                        fputcsv($file, $array);
                        
                        $total_data['units_total']+=$units_total;
                        
                        $total_data['qty_3']+=$qty_3;
                        $total_data['taxable_value_3']+=$taxable_value_3; 
                        $total_data['igst_3']+=$igst_3; 
                        $total_data['cgst_1_5']+=$cgst_1_5; 
                        $total_data['sgst_1_5']+=$sgst_1_5; 
                        $total_data['total_tax_3']+=$total_tax_3; 
                        $total_data['total_value_3']+=$total_value_3; 
                        
                        $total_data['qty_5']+=$qty_5;
                        $total_data['taxable_value_5']+=$taxable_value_5; 
                        $total_data['igst_5']+=$igst_5; 
                        $total_data['cgst_2_5']+=$cgst_2_5; 
                        $total_data['sgst_2_5']+=$sgst_2_5; 
                        $total_data['total_tax_5']+=$total_tax_5; 
                        $total_data['total_value_5']+=$total_value_5; 

                        $total_data['qty_12']+=$qty_12;
                        $total_data['taxable_value_12']+=$taxable_value_12; 
                        $total_data['igst_12']+=$igst_12; 
                        $total_data['cgst_6']+=$cgst_6; 
                        $total_data['sgst_6']+=$sgst_6; 
                        $total_data['total_tax_12']+=$total_tax_12; 
                        $total_data['total_value_12']+=$total_value_12; 
                        
                        $total_data['qty_18']+=$qty_18;
                        $total_data['taxable_value_18']+=$taxable_value_18; 
                        $total_data['igst_18']+=$igst_18; 
                        $total_data['cgst_9']+=$cgst_9; 
                        $total_data['sgst_9']+=$sgst_9; 
                        $total_data['total_tax_18']+=$total_tax_18; 
                        $total_data['total_value_18']+=$total_value_18; 

                        $total_data['qty_0']+=$qty_0;
                        $total_data['taxable_value_0']+=$taxable_value_0; 
                        $total_data['igst_0']+=$igst_0; 
                        $total_data['cgst_0']+=$cgst_0; 
                        $total_data['sgst_0']+=$sgst_0; 
                        $total_data['total_tax_0']+=$total_tax_0; 
                        $total_data['total_value_0']+=$total_value_0; 
                        $total_data['total_net_amount']+=$total_net_amount; 
                    }
                    
                    $array = array('Total','','','','','','','','','',$total_data['units_total'],
                    $total_data['qty_3'],$total_data['taxable_value_3'],$total_data['igst_3'],$total_data['cgst_1_5'],$total_data['sgst_1_5'],$total_data['total_tax_3'],$total_data['total_value_3'],    
                    $total_data['qty_5'],$total_data['taxable_value_5'],$total_data['igst_5'],$total_data['cgst_2_5'],$total_data['sgst_2_5'],$total_data['total_tax_5'],$total_data['total_value_5'],
                    $total_data['qty_12'],$total_data['taxable_value_12'],$total_data['igst_12'],$total_data['cgst_6'],$total_data['sgst_6'],$total_data['total_tax_12'],$total_data['total_value_12'],
                    $total_data['qty_18'],$total_data['taxable_value_18'],$total_data['igst_18'],$total_data['cgst_9'],$total_data['sgst_9'],$total_data['total_tax_18'],$total_data['total_value_18'],    
                    $total_data['qty_0'],$total_data['taxable_value_0'],$total_data['igst_0'],$total_data['cgst_0'],$total_data['sgst_0'],$total_data['total_tax_0'],$total_data['total_value_0'],$total_data['total_net_amount']);
                    
                    fputcsv($file, $array);
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
            
            $whereArray = !empty($data['state_id'])?array('state_id'=>$data['state_id']):array();
            $store_list = CommonHelper::getStoresList($whereArray);
            
            $state_list = \DB::table('state_list as s')
            ->where('s.is_deleted',0)
            ->whereRaw("s.id IN(SELECT DISTINCT store_state_id FROM store_products_demand)")
            ->orderBy('state_name')        
            ->get()->toArray();
            
            return view('admin/report_warehouse_to_store',array('error_message'=>$error_message,'invoice_list'=>$invoice_list,'invoice_list_sum'=>$invoice_list_sum,'gst_type_total'=>$gst_type_total,'store_list'=>$store_list,'report_type'=>$report_type,'state_list'=>$state_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/report_warehouse_to_store',array('error_message'=>$e->getMessage()));
        }
    }
    
    function storeToStoreSalesReport(Request $request){
        try{
            $data = $request->all();
            $error_message = $invoice_list_sum = $gst_type_total = '';
            $store_id_list = array();
            $report_type = (isset($data['report_type']) && !empty($data['report_type']))?$data['report_type']:'bill';
            $store_type = (!empty($data['store_type']))?array(trim($data['store_type'])):array(1,2);
            $from_store = (isset($data['from_store_id']) && !empty($data['from_store_id']))?$data['from_store_id']:'';
            $to_store   = (isset($data['to_store_id']) && !empty($data['to_store_id']))?$data['to_store_id']:'';
            
            //\DB::enableQueryLog();
            if(strtolower($report_type) == 'bill'){
                $invoice_list = \DB::table('store_products_demand as spd')
                ->where('spd.from_store_id',$from_store)         
                ->where('spd.demand_type','inventory_transfer_to_store')        
                ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
                ->where('spd.fake_inventory',0)         
                ->where('spd.is_deleted',0);
                
                $invoice_list = $invoice_list->selectRaw('spd.*');
            }
            
            if(strtolower($report_type) == 'hsn_code'){
                $invoice_list = \DB::table('store_products_demand as spd')
                ->join('store_products_demand_inventory as spdi','spdi.demand_id', '=', 'spd.id')        
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')         
                ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')        
                ->where('spd.from_store_id',$from_store)               
                ->where('spd.demand_type','inventory_transfer_to_store')        
                ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))        
                ->where('spd.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('spd.fake_inventory',0)
                ->where('spdi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)   
                ->where('spdi.demand_status',1)        
                ->where('spdi.is_deleted',0);

                $invoice_list = $invoice_list->groupByRaw('spdi.demand_id,ppm.hsn_code');        
                $invoice_list = $invoice_list->selectRaw("spd.*,ppm.hsn_code,COUNT(spdi.id) as units_total");
            }
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                if(CommonHelper::dateDiff($search_date['start_date'],$search_date['end_date']) > 365){
                    throw new Exception('Date difference should not be more than 365 days');
                }
            }else{
                if(CommonHelper::dateDiff($search_date['start_date'],$search_date['end_date']) > 180){
                    throw new Exception('Date difference should not be more than 180 days');
                }
            }
            
            $invoice_list = $invoice_list->whereRaw("spd.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")->orderBy('spd.created_at','ASC');
            
            if(isset($data['to_store_id']) && !empty($data['to_store_id'])){
                $invoice_list = $invoice_list->where('spd.store_id',$data['to_store_id']);
            }
            
            $invoice_list = json_decode(json_encode($invoice_list->get()->toArray()),true);
            
            //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
            
            $states = \DB::table('state_list as s')
            ->where('s.is_deleted',0)
            ->whereRaw("s.id IN(SELECT DISTINCT store_state_id FROM store_products_demand)")
            ->orderBy('state_name')->get()->toArray();
            
            for($i=0;$i<count($states);$i++){    
                $state_list[$states[$i]->id] = $states[$i]->state_name;
            }
            
            $whereArray = !empty($data['state_id'])?array('state_id'=>$data['state_id']):array();
            $store_list = CommonHelper::getStoresList($whereArray);
            
            for($i=0;$i<count($store_list);$i++){
                $store_id_list[$store_list[$i]['id']] = $store_list[$i];
            }
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array(
                    'Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=report_store_to_store.csv',
                    'Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0'
                );

                $columns = array('Supplier Name','Supplier Location','Supplier Code','Supplier State','Supplier GST No','Recipient Name','Recipient Location','Recipient Code','Recipient State',
                'Recipient GST No','Bill No','HSN Code','Bill Date','Sale Qty',
                'Qty 3%','Taxable Value 3%','IGST 3%','CGST 1.5%','SGST 1.5%','Total Tax 3%','Total Value 3%',    
                'Qty 5%','Taxable Value 5%','IGST 5%','CGST 2.5%','SGST 2.5%','Total Tax 5%','Total Value 5%',
                'Qty 12%','Taxable Value 12%','IGST 12%','CGST 6%','SGST 6%','Total Tax 12%','Total Value 12%',
                'Qty 18%','Taxable Value 18%','IGST 18%','CGST 9%','SGST 9%','Total Tax 18%','Total Value 18%',    
                'Qty 0%','Taxable Value 0%','IGST 0%','CGST 0%','SGST 0%','Total Tax 0%','Total Value 0%','Total Net Amount');

                $callback = function() use ($invoice_list,$report_type, $columns,$state_list,$store_id_list){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    $total_data = array('units_total'=>0,
                    'taxable_value_3'=>0,'igst_3'=>0,'cgst_1_5'=>0,'sgst_1_5'=>0,'total_tax_3'=>0,'total_value_3'=>0,    
                    'taxable_value_5'=>0,'igst_5'=>0,'cgst_2_5'=>0,'sgst_2_5'=>0,'total_tax_5'=>0,'total_value_5'=>0,
                    'taxable_value_12'=>0,'igst_12'=>0,'cgst_6'=>0,'sgst_6'=>0,'total_tax_12'=>0,'total_value_12'=>0,
                    'taxable_value_18'=>0,'igst_18'=>0,'cgst_9'=>0,'sgst_9'=>0,'total_tax_18'=>0,'total_value_18'=>0,    
                    'taxable_value_0'=>0,'igst_0'=>0,'cgst_0'=>0,'sgst_0'=>0,'total_tax_0'=>0,'total_value_0'=>0,'total_net_amount'=>0,
                    'qty_3'=>0,'qty_5'=>0,'qty_12'=>0,'qty_18'=>0,'qty_0'=>0);
                    
                    for($i=0;$i<count($invoice_list);$i++){
                        
                        $taxable_value_3 = $igst_3 = $cgst_1_5 = $sgst_1_5 = $total_tax_3 = $total_value_3 = 0;
                        $units_total = $taxable_value_5 = $igst_5 = $cgst_2_5 = $sgst_2_5 = $total_tax_5 = $total_value_5 = 0;
                        $taxable_value_12 = $igst_12 = $cgst_6 = $sgst_6 = $total_tax_12 = $total_value_12 = 0;
                        $taxable_value_18 = $igst_18 = $cgst_9 = $sgst_9 = $total_tax_18 = $total_value_18 = 0;
                        $taxable_value_0 = $igst_0 = $cgst_0 = $sgst_0 = $total_tax_0 = $total_value_0 = 0;
                        $qty_3 = $qty_5 = $qty_12 = $qty_18 = $qty_0 = 0; 

                        $from_store_data = json_decode($invoice_list[$i]['from_store_data'],true); 
                        $to_store_data = json_decode($invoice_list[$i]['store_data'],true);
                        
                        $total_info = ($report_type == 'hsn_code')?json_decode($invoice_list[$i]['total_data_hsn'],true):json_decode($invoice_list[$i]['total_data'],true);
                        $gst_type = CommonHelper::getGSTType($to_store_data['gst_no']);
                        
                        //$units_total = $total_info['total_qty'];
                        $hsn_code = isset($invoice_list[$i]['hsn_code'])?$invoice_list[$i]['hsn_code']:'';
                        $units_total = ($report_type == 'bill')?$total_info['total_qty']:$total_info['total_data'][$hsn_code]['total_qty'];
                        $from_store_code = $store_id_list[$invoice_list[$i]['from_store_id']]['store_id_code'];
                        $to_store_code = $store_id_list[$invoice_list[$i]['store_id']]['store_id_code'];
                        $array = array($from_store_data['gst_name'],$from_store_data['store_name'],$from_store_code,$state_list[$from_store_data['state_id']],$from_store_data['gst_no'],$to_store_data['gst_name'],$to_store_data['store_name'],$to_store_code,$state_list[$to_store_data['state_id']],$to_store_data['gst_no'],$invoice_list[$i]['invoice_no'],$hsn_code,date('d-m-Y',strtotime($invoice_list[$i]['created_at'])),$units_total);
                        
                        $key = ($report_type == 'bill')?'3':$hsn_code.'_3';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_3 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_3 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_3 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_1_5 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_1_5 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_3 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_3 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $key = ($report_type == 'bill')?'5':$hsn_code.'_5';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_5 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_5 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_5 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_2_5 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_2_5 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_5 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_5 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $key = ($report_type == 'bill')?'12':$hsn_code.'_12';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_12 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_12 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_12 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_6 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_6 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_12 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_12 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $key = ($report_type == 'bill')?'18':$hsn_code.'_18';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_18 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_18 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_18 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_9 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_9 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_18 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_18 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $key = ($report_type == 'bill')?'0':$hsn_code.'_0';
                        if(isset($total_info['taxable_value_'.$key])){
                            $array[] = $qty_0 = $total_info['qty_'.$key];
                            $array[] = $taxable_value_0 = $total_info['taxable_value_'.$key];
                            
                            if($gst_type == 2) $array[] = $igst_0 = $total_info['gst_amount_'.$key];else $array[] = '';
                            if($gst_type == 1) $array[] = $cgst_0 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $sgst_0 = round($total_info['gst_amount_'.$key]/2,2);else $array[] = '';
                            if($gst_type == 1) $array[] = $total_tax_0 = $total_info['gst_amount_'.$key];else $array[] = '';
                            $array[] = $total_value_0 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key];
                        }else{
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                            $array[] = '';
                        }
                        
                        $total_net_amount = ($report_type == 'bill')?$total_info['total_value']:$total_info['total_data'][$hsn_code]['total_value'];
                        $array[] = $total_net_amount;
                        
                        fputcsv($file, $array);
                        
                        $total_data['units_total']+=$units_total;
                        
                        $total_data['qty_3']+=$qty_3;
                        $total_data['taxable_value_3']+=$taxable_value_3; 
                        $total_data['igst_3']+=$igst_3; 
                        $total_data['cgst_1_5']+=$cgst_1_5; 
                        $total_data['sgst_1_5']+=$sgst_1_5; 
                        $total_data['total_tax_3']+=$total_tax_3; 
                        $total_data['total_value_3']+=$total_value_3; 
                        
                        $total_data['qty_5']+=$qty_5;
                        $total_data['taxable_value_5']+=$taxable_value_5; 
                        $total_data['igst_5']+=$igst_5; 
                        $total_data['cgst_2_5']+=$cgst_2_5; 
                        $total_data['sgst_2_5']+=$sgst_2_5; 
                        $total_data['total_tax_5']+=$total_tax_5; 
                        $total_data['total_value_5']+=$total_value_5; 

                        $total_data['qty_12']+=$qty_12;
                        $total_data['taxable_value_12']+=$taxable_value_12; 
                        $total_data['igst_12']+=$igst_12; 
                        $total_data['cgst_6']+=$cgst_6; 
                        $total_data['sgst_6']+=$sgst_6; 
                        $total_data['total_tax_12']+=$total_tax_12; 
                        $total_data['total_value_12']+=$total_value_12; 
                        
                        $total_data['qty_18']+=$qty_18;
                        $total_data['taxable_value_18']+=$taxable_value_18; 
                        $total_data['igst_18']+=$igst_18; 
                        $total_data['cgst_9']+=$cgst_9; 
                        $total_data['sgst_9']+=$sgst_9; 
                        $total_data['total_tax_18']+=$total_tax_18; 
                        $total_data['total_value_18']+=$total_value_18; 

                        $total_data['qty_0']+=$qty_0;
                        $total_data['taxable_value_0']+=$taxable_value_0; 
                        $total_data['igst_0']+=$igst_0; 
                        $total_data['cgst_0']+=$cgst_0; 
                        $total_data['sgst_0']+=$sgst_0; 
                        $total_data['total_tax_0']+=$total_tax_0; 
                        $total_data['total_value_0']+=$total_value_0; 
                        $total_data['total_net_amount']+=$total_net_amount; 
                    }
                    
                    $array = array('Total','','','','','','','','','','','','',$total_data['units_total'],
                    $total_data['qty_3'],$total_data['taxable_value_3'],$total_data['igst_3'],$total_data['cgst_1_5'],$total_data['sgst_1_5'],$total_data['total_tax_3'],$total_data['total_value_3'],    
                    $total_data['qty_5'],$total_data['taxable_value_5'],$total_data['igst_5'],$total_data['cgst_2_5'],$total_data['sgst_2_5'],$total_data['total_tax_5'],$total_data['total_value_5'],
                    $total_data['qty_12'],$total_data['taxable_value_12'],$total_data['igst_12'],$total_data['cgst_6'],$total_data['sgst_6'],$total_data['total_tax_12'],$total_data['total_value_12'],
                    $total_data['qty_18'],$total_data['taxable_value_18'],$total_data['igst_18'],$total_data['cgst_9'],$total_data['sgst_9'],$total_data['total_tax_18'],$total_data['total_value_18'],    
                    $total_data['qty_0'],$total_data['taxable_value_0'],$total_data['igst_0'],$total_data['cgst_0'],$total_data['sgst_0'],$total_data['total_tax_0'],$total_data['total_value_0'],$total_data['total_net_amount']);
                    
                    fputcsv($file, $array);
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
            
            return view('admin/report_store_to_store',array('error_message'=>$error_message,'invoice_list'=>$invoice_list,'invoice_list_sum'=>$invoice_list_sum,'gst_type_total'=>$gst_type_total,'store_list'=>$store_list,'report_type'=>$report_type,'state_list'=>$state_list,'store_id_list'=>$store_id_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/report_store_to_store',array('error_message'=>$e->getMessage()));
        }
    }
    
    function closingStockDetailReport(Request $request){
        try{
            ini_set('memory_limit', '-1');
            set_time_limit(300);
            $data = $request->all();
            $error_message = $store_id = $start_date = $end_date = '';
            $user = Auth::user();
            $inv_opening_stock = $inv_opening_stock1 = $inv_closing_stock = $base_price_list = $inv_purchase = $inv_sale = $sku_list = $sku_category = $sku_list1 = array();
            $store_in_prod = $store_out_prod = $ho_in_prod = $ho_out_prod = $ho_inv_opening_stock = $ho_inv_closing_stock = $ho_inv_sale = $ho_inv_purchase = array();
            $sku_list_ho = $store_info = [];
            
            $search_date = CommonHelper::getSearchStartEndDate($data);
            
            if(!empty($search_date['start_date']) && !empty($search_date['start_date'])){
                $start_date = date('Y/m/d',strtotime($search_date['start_date']));
                $end_date = date('Y/m/d',strtotime($search_date['end_date']));
            }
            
            if(isset($data['s_id']) && $data['s_id'] > 0){
                $store_list = Store::where('id',$data['s_id'])->get()->toArray();
            }elseif(isset($data['store_id']) && !empty($data['store_id'])){
                $store_ids = explode('_',trim($data['store_id']));
                $store_list = Store::where('id','>=',$store_ids[0])->where('id','<=',$store_ids[1])->where('is_deleted',0)->get()->toArray();
            }else{
                $store_list = [];
            }
            
            for($q=0;$q<count($store_list);$q++){ 
                
                $store_id = $store_list[$q]['id'];
                $store_info = Store::where('id',$store_id)->first();
            
                if(!empty($store_info) &&  in_array($store_info->store_info_type, [1,2])){

                    /* Opening Stock Start */
                    if($store_info->store_info_type == 1){
                        $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                        ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                        ->where('spdi.transfer_status',1)        
                        ->where('ppmi.is_deleted',0)
                        ->where('ppmi.arnon_inventory',0)
                        ->where('ppmi.status',1)
                        ->where('spdi.is_deleted',0)
                        ->where('spdi.demand_status',1)        
                        ->where('spd.fake_inventory',0)
                        ->where('spdi.fake_inventory',0)       
                        ->where('ppm.fake_inventory',0)
                        ->where('ppmi.fake_inventory',0)       
                        ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                        ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                        ->where('spd.demand_type','inventory_push');
                    }

                    if($store_info->store_info_type == 2){
                        $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                        ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                        ->where('spd.from_store_id',50)                
                        ->where('spdi.transfer_status',1)        
                        ->where('ppmi.is_deleted',0)
                        ->where('ppmi.arnon_inventory',0)
                        ->where('ppmi.status',1)
                        ->where('spdi.is_deleted',0)
                        ->where('spdi.demand_status',1)        
                        ->where('spd.fake_inventory',0)
                        ->where('spdi.fake_inventory',0)       
                        ->where('ppm.fake_inventory',0)
                        ->where('ppmi.fake_inventory',0)       
                        ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
                        ->where('spd.demand_type','inventory_transfer_to_store');
                    }

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_in_products = $store_in_products->whereRaw("DATE(spd.created_at) < '".$search_date['start_date']."'");
                    }

                    $store_in_products = $store_in_products->where('spd.store_id',trim($store_id));

                    $store_in_products = $store_in_products->groupByRaw('spd.store_id,ppm.product_sku')
                    ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_in_count')
                    ->orderByRaw('spd.store_id')->get()->toArray();

                    //\DB::enableQueryLog();
                    $store_out_products = \DB::table('pos_customer_orders_detail as pcod')
                    ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')         
                    ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')         
                    ->where('ppm.is_deleted',0)
                    ->where('pco.is_deleted',0)      
                    ->where('pcod.arnon_prod_inv',0) 
                    ->where('pcod.fake_inventory',0)
                    ->where('pco.fake_inventory',0)        
                    ->where('ppm.fake_inventory',0)        
                    ->where('pco.order_status',1)
                    ->where('pcod.order_status',1)               
                    ->where('pcod.is_deleted',0);

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_out_products = $store_out_products->whereRaw("DATE(pco.created_at) < '".$search_date['start_date']."'");
                    }        

                    $store_out_products = $store_out_products->where('pco.store_id',trim($store_id));

                    $store_out_products = $store_out_products->groupByRaw('pcod.store_id,ppm.product_sku')
                    ->selectRaw('pcod.store_id,abs(pcod.net_price) as net_price,ppm.product_sku,SUM(pcod.product_quantity) as inv_out_count')     
                    ->orderBy('pcod.store_id')        
                    ->get()->toArray();

                    //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;

                    $store_in_products  = json_decode(json_encode($store_in_products),true);
                    $store_out_products  = json_decode(json_encode($store_out_products),true);

                    for($i=0;$i<count($store_in_products);$i++){    
                        $store_in_prod[$store_in_products[$i]['store_id'].'_'.$store_in_products[$i]['product_sku']] = $store_in_products[$i];
                        $sku_list[] = $store_in_products[$i]['product_sku'];
                    }

                    for($i=0;$i<count($store_out_products);$i++){    
                        $store_out_prod[$store_out_products[$i]['store_id'].'_'.$store_out_products[$i]['product_sku']] = $store_out_products[$i];
                        $sku_list[] = $store_out_products[$i]['product_sku'];
                    }

                    foreach($store_in_prod as $key=>$inv_in_data){
                        $inv_count = (isset($store_out_prod[$key]))?$inv_in_data['inv_in_count']-$store_out_prod[$key]['inv_out_count']:$inv_in_data['inv_in_count'];
                        $net_price = (isset($store_out_prod[$key]['net_price']))?$store_out_prod[$key]['net_price']:0;
                        $inv_opening_stock[$key] = array('inv_count'=>$inv_count,'store_base_price'=>$inv_in_data['store_base_price'],'net_price'=>$net_price);
                    }

                    /* Opening Stock End */

                    /* Closing Stock Start */

                    if($store_info->store_info_type == 1){
                        $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                        ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id') 
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                        ->where('spdi.transfer_status',1)        
                        ->where('ppmi.is_deleted',0)
                        ->where('ppmi.arnon_inventory',0)
                        ->where('ppmi.status',1)
                        ->where('spdi.is_deleted',0)
                        ->where('spdi.demand_status',1)        
                        ->where('spd.fake_inventory',0)
                        ->where('spdi.fake_inventory',0)        
                        ->where('ppm.fake_inventory',0)
                        ->where('ppmi.fake_inventory',0)        
                        ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                        ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                        ->where('spd.demand_type','inventory_push');
                    }

                    if($store_info->store_info_type == 2){
                        $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                        ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id') 
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                        ->where('spd.from_store_id',50)              
                        ->where('spdi.transfer_status',1)        
                        ->where('ppmi.is_deleted',0)
                        ->where('ppmi.arnon_inventory',0)
                        ->where('ppmi.status',1)
                        ->where('spdi.is_deleted',0)
                        ->where('spdi.demand_status',1)        
                        ->where('spd.fake_inventory',0)
                        ->where('spdi.fake_inventory',0)        
                        ->where('ppm.fake_inventory',0)
                        ->where('ppmi.fake_inventory',0)        
                        ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
                        ->where('spd.demand_type','inventory_transfer_to_store');
                    }

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_in_products = $store_in_products->whereRaw("DATE(spd.created_at) <= '".$search_date['end_date']."'");
                    }

                    $store_in_products = $store_in_products->where('spd.store_id',trim($store_id));

                    $store_in_products = $store_in_products->groupByRaw('spd.store_id,ppm.product_sku')
                    ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_in_count')
                    ->orderBy('spd.store_id')->get()->toArray();

                    $store_out_products = \DB::table('pos_customer_orders_detail as pcod')
                    ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')         
                    ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
                    ->where('ppm.is_deleted',0)
                    ->where('pco.is_deleted',0)                
                    ->where('pcod.arnon_prod_inv',0) 
                    ->where('pcod.fake_inventory',0)
                    ->where('pco.fake_inventory',0)        
                    ->where('ppm.fake_inventory',0)  
                    ->where('pco.order_status',1)
                    ->where('pcod.order_status',1)               
                    ->where('pcod.is_deleted',0);

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_out_products = $store_out_products->whereRaw("DATE(pco.created_at) <= '".$search_date['end_date']."'");
                    }        

                    $store_out_products = $store_out_products->where('pco.store_id',trim($store_id));

                    $store_out_products = $store_out_products->groupByRaw('pcod.store_id,ppm.product_sku')
                    ->selectRaw('pcod.store_id,abs(pcod.net_price) as net_price,ppm.product_sku,SUM(pcod.product_quantity) as inv_out_count')     
                    ->orderBy('pcod.store_id')        
                    ->get()->toArray();

                    $store_in_products  = json_decode(json_encode($store_in_products),true);
                    $store_out_products  = json_decode(json_encode($store_out_products),true);

                    for($i=0;$i<count($store_in_products);$i++){    
                        $store_in_prod[$store_in_products[$i]['store_id'].'_'.$store_in_products[$i]['product_sku']] = $store_in_products[$i];
                        $sku_list[] = $store_in_products[$i]['product_sku'];
                    }

                    for($i=0;$i<count($store_out_products);$i++){    
                        $store_out_prod[$store_out_products[$i]['store_id'].'_'.$store_out_products[$i]['product_sku']] = $store_out_products[$i];
                        $sku_list[] = $store_out_products[$i]['product_sku'];
                    }

                    foreach($store_in_prod as $key=>$inv_in_data){
                        $inv_count = (isset($store_out_prod[$key]))?$inv_in_data['inv_in_count']-$store_out_prod[$key]['inv_out_count']:$inv_in_data['inv_in_count'];
                        $net_price = (isset($store_out_prod[$key]['net_price']))?$store_out_prod[$key]['net_price']:0;
                        $inv_closing_stock[$key] = array('inv_count'=>$inv_count,'store_base_price'=>$inv_in_data['store_base_price'],'net_price'=>$net_price);
                    }

                    /* Closing Stock End */

                    /* Purchase Start */

                    if($store_info->store_info_type == 1){
                        $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                        ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                        ->where('spdi.transfer_status',1)        
                        ->where('ppmi.is_deleted',0)
                        ->where('ppmi.arnon_inventory',0)
                        ->where('ppmi.status',1)
                        ->where('spdi.is_deleted',0)
                        ->where('spdi.demand_status',1)        
                        ->where('spd.fake_inventory',0)
                        ->where('spdi.fake_inventory',0)        
                        ->where('ppm.fake_inventory',0)
                        ->where('ppmi.fake_inventory',0)                
                        ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                        ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                        ->where('spd.demand_type','inventory_push');
                    }

                    if($store_info->store_info_type == 2){
                        $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                        ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')     
                        ->where('spd.from_store_id',50)              
                        ->where('spdi.transfer_status',1)        
                        ->where('ppmi.is_deleted',0)
                        ->where('ppmi.arnon_inventory',0)
                        ->where('ppmi.status',1)
                        ->where('spdi.is_deleted',0)
                        ->where('spdi.demand_status',1)        
                        ->where('spd.fake_inventory',0)
                        ->where('spdi.fake_inventory',0)        
                        ->where('ppm.fake_inventory',0)
                        ->where('ppmi.fake_inventory',0)                
                        ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
                        ->where('spd.demand_type','inventory_transfer_to_store');
                    }

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_in_products = $store_in_products->whereRaw("spd.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'");
                    }

                    $store_in_products = $store_in_products->where('spd.store_id',trim($store_id));

                    $store_in_products = $store_in_products->groupByRaw('spd.store_id,ppm.product_sku')
                    ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_in_count')
                    ->orderBy('spd.store_id')->get()->toArray();

                    $store_in_products  = json_decode(json_encode($store_in_products),true);

                    for($i=0;$i<count($store_in_products);$i++){    
                        $inv_purchase[$store_in_products[$i]['store_id'].'_'.$store_in_products[$i]['product_sku']] = $store_in_products[$i];
                        $sku_list[] = $store_in_products[$i]['product_sku'];
                    }

                    /* Purchase End */

                    /* Sale Start */

                    $store_out_products = \DB::table('pos_customer_orders_detail as pcod')
                    ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')         
                    ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id') 
                    ->where('ppm.is_deleted',0)
                    ->where('pco.is_deleted',0)            
                    ->where('pcod.arnon_prod_inv',0)   
                    ->where('pcod.fake_inventory',0)
                    ->where('pco.fake_inventory',0)        
                    ->where('ppm.fake_inventory',0)     
                    ->where('pco.order_status',1)
                    ->where('pcod.order_status',1)               
                    ->where('pcod.is_deleted',0);

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_out_products = $store_out_products->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'");
                    }        

                    $store_out_products = $store_out_products->where('pco.store_id',trim($store_id));

                    $store_out_products = $store_out_products->groupByRaw('pcod.store_id,ppm.product_sku')
                    ->selectRaw('pcod.store_id,abs(pcod.net_price) as net_price,ppm.product_sku,SUM(pcod.product_quantity) as inv_out_count')     
                    ->orderBy('pcod.store_id')        
                    ->get()->toArray();

                    $store_out_products  = json_decode(json_encode($store_out_products),true);

                    for($i=0;$i<count($store_out_products);$i++){    
                        $inv_sale[$store_out_products[$i]['store_id'].'_'.$store_out_products[$i]['product_sku']] = $store_out_products[$i];
                        $sku_list[] = $store_out_products[$i]['product_sku'];
                    }

                    /* Sale End */

                    $sku_list = array_values(array_unique($sku_list));

                    $product_list = \DB::table('pos_product_master as ppm')
                    ->join('design_lookup_items_master as dlim','ppm.category_id', '=', 'dlim.id') 
                    ->where('ppm.is_deleted',0)
                    ->where('ppm.fake_inventory',0)        
                    ->groupBy('ppm.product_sku')   
                    ->select('ppm.product_sku','dlim.name as category_name')        
                    ->orderBy('dlim.name')        
                    ->get()->toArray();        

                    // create array to display category of sku
                    for($i=0;$i<count($product_list);$i++){ 
                        $sku_category[$product_list[$i]->product_sku] = $product_list[$i]->category_name;

                        // To sort sku list by category
                        if(in_array($product_list[$i]->product_sku, $sku_list)){
                            $sku_list1[] = $product_list[$i]->product_sku;
                        }
                    }

                    $sku_list = $sku_list1;
                }

                // Store End

                // Tiki Global Store Warehouse, Store ID: 50
                if(!empty($store_info) &&  $store_info->store_info_type == 3){

                    /* Opening Stock Start */

                    // Stock received by Tiki global from warehouse
                    $store_in_prod = $store_out_prod = $store_out_prod_wh = [];

                    $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                    ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                    ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')      
                    //->where('ppm.product_sku','LG-285P-B')        
                    ->where('spd.store_id',trim($store_id))        
                    ->where('spdi.transfer_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.arnon_inventory',0)
                    ->where('ppmi.status',1)
                    ->where('spdi.is_deleted',0)
                    ->where('spdi.demand_status',1)        
                    ->where('spd.fake_inventory',0)
                    ->where('spdi.fake_inventory',0)       
                    ->where('ppm.fake_inventory',0)
                    ->where('ppmi.fake_inventory',0)       
                    ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded','loaded'))    
                    ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                    ->wherein('spd.demand_type',['inventory_push','inventory_transfer_to_store']);

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_in_products = $store_in_products->whereRaw("DATE(spd.created_at) < '".$start_date."'");
                    }

                    $store_in_products = $store_in_products->groupByRaw('ppm.product_sku')
                    ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_in_count')
                    ->orderByRaw('spd.store_id')->get()->toArray();

                    // Tiki global to other stores
                    $store_out_products = \DB::table('store_products_demand_inventory as spdi')
                    ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                    ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                    //->where('ppm.product_sku','LG-285P-B')        
                    ->where('spd.from_store_id',trim($store_id))                
                    ->where('spdi.transfer_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.arnon_inventory',0)
                    ->where('ppmi.status',1)
                    ->where('spdi.is_deleted',0)
                    ->where('spdi.demand_status',1)        
                    ->where('spd.fake_inventory',0)
                    ->where('spdi.fake_inventory',0)       
                    ->where('ppm.fake_inventory',0)
                    ->where('ppmi.fake_inventory',0)       
                    ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
                    ->where('spd.demand_type','inventory_transfer_to_store');

                    // Stock retured to warehouse
                    $store_out_products_to_wh = \DB::table('store_products_demand_inventory as spdi')
                    ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                    ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                    //->where('ppm.product_sku','LG-285P-B')        
                    ->where('spd.store_id',trim($store_id))                
                    ->where('spdi.transfer_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.arnon_inventory',0)
                    ->where('ppmi.status',1)
                    ->where('spdi.is_deleted',0)
                    ->where('spdi.demand_status',1)        
                    ->where('spd.fake_inventory',0)
                    ->where('spdi.fake_inventory',0)       
                    ->where('ppm.fake_inventory',0)
                    ->where('ppmi.fake_inventory',0)       
                    ->wherein('spd.demand_status',array('warehouse_dispatched','warehouse_loading','warehouse_loaded'))    
                    ->where('spd.demand_type','inventory_return_to_warehouse');

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_out_products = $store_out_products->whereRaw("DATE(spd.created_at) < '".$start_date."'");
                        $store_out_products_to_wh = $store_out_products_to_wh->whereRaw("DATE(spd.created_at) < '".$start_date."'");
                    }

                    $store_out_products = $store_out_products->groupByRaw('ppm.product_sku')
                    ->selectRaw('spd.from_store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_out_count')
                    ->orderByRaw('spd.store_id')->get()->toArray();

                    $store_out_products_to_wh = $store_out_products_to_wh->groupByRaw('ppm.product_sku')
                    ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_out_count')
                    ->orderByRaw('spd.store_id')->get()->toArray();

                    $store_in_products  = json_decode(json_encode($store_in_products),true);
                    $store_out_products  = json_decode(json_encode($store_out_products),true);
                    $store_out_products_to_wh  = json_decode(json_encode($store_out_products_to_wh),true);

                    for($i=0;$i<count($store_in_products);$i++){    
                        $store_in_prod[$store_in_products[$i]['store_id'].'_'.$store_in_products[$i]['product_sku']] = $store_in_products[$i];
                        $sku_list[] = $store_in_products[$i]['product_sku'];
                    }

                    for($i=0;$i<count($store_out_products);$i++){    
                        $store_out_prod[$store_out_products[$i]['from_store_id'].'_'.$store_out_products[$i]['product_sku']] = $store_out_products[$i];
                        $sku_list[] = $store_out_products[$i]['product_sku'];
                    }

                    for($i=0;$i<count($store_out_products_to_wh);$i++){    
                        $store_out_prod_wh[$store_out_products_to_wh[$i]['store_id'].'_'.$store_out_products_to_wh[$i]['product_sku']] = $store_out_products_to_wh[$i];
                        $sku_list[] = $store_out_products_to_wh[$i]['product_sku'];
                    }

                    //echo '<br>opening: ';print_r($store_in_prod);echo '<br/>';print_r($store_out_prod);//exit;

                    foreach($store_in_prod as $key=>$inv_in_data){
                        $inv_count = (isset($store_out_prod[$key]))?$inv_in_data['inv_in_count']-$store_out_prod[$key]['inv_out_count']:$inv_in_data['inv_in_count'];
                        $inv_count = (isset($store_out_prod_wh[$key]))?$inv_count-$store_out_prod_wh[$key]['inv_out_count']:$inv_count;
                        $net_price = 0; //(isset($store_out_prod[$key]['net_price']))?$store_out_prod[$key]['net_price']:0;
                        $inv_opening_stock[$key] = array('inv_count'=>$inv_count,'store_base_price'=>$inv_in_data['store_base_price'],'net_price'=>$net_price);
                    }

                    /* Opening Stock End */

                    /* Closing Stock Start */

                    // Stock received by Tiki global from warehouse

                    $store_in_prod = $store_out_prod = $store_out_prod_wh = [];

                    $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                    ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                    ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')  
                    //->where('ppm.product_sku','LG-285P-B')        
                    ->where('spd.store_id',trim($store_id))        
                    ->where('spdi.transfer_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.arnon_inventory',0)
                    ->where('ppmi.status',1)
                    ->where('spdi.is_deleted',0)
                    ->where('spdi.demand_status',1)        
                    ->where('spd.fake_inventory',0)
                    ->where('spdi.fake_inventory',0)       
                    ->where('ppm.fake_inventory',0)
                    ->where('ppmi.fake_inventory',0)       
                    ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded','loaded'))    
                    ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                    ->wherein('spd.demand_type',['inventory_push','inventory_transfer_to_store']);

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_in_products = $store_in_products->whereRaw("DATE(spd.created_at) <= '".$end_date."'");
                    }

                    $store_in_products = $store_in_products->groupByRaw('ppm.product_sku')
                    ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_in_count')
                    ->orderByRaw('spd.store_id')->get()->toArray();
                    //print_r($store_in_products);exit;

                    // Tiki global to other stores
                    $store_out_products = \DB::table('store_products_demand_inventory as spdi')
                    ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                    ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                    //->where('ppm.product_sku','LG-285P-B')        
                    ->where('spd.from_store_id',trim($store_id))                
                    ->where('spdi.transfer_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.arnon_inventory',0)
                    ->where('ppmi.status',1)
                    ->where('spdi.is_deleted',0)
                    ->where('spdi.demand_status',1)        
                    ->where('spd.fake_inventory',0)
                    ->where('spdi.fake_inventory',0)       
                    ->where('ppm.fake_inventory',0)
                    ->where('ppmi.fake_inventory',0)       
                    ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
                    ->where('spd.demand_type','inventory_transfer_to_store');

                    // Stock returned from Tiki Global to warehouse
                    $store_out_products_to_wh = \DB::table('store_products_demand_inventory as spdi')
                    ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                    ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                    //->where('ppm.product_sku','LG-285P-B')        
                    ->where('spd.store_id',trim($store_id))                
                    ->where('spdi.transfer_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.arnon_inventory',0)
                    ->where('ppmi.status',1)
                    ->where('spdi.is_deleted',0)
                    ->where('spdi.demand_status',1)        
                    ->where('spd.fake_inventory',0)
                    ->where('spdi.fake_inventory',0)       
                    ->where('ppm.fake_inventory',0)
                    ->where('ppmi.fake_inventory',0)       
                    ->wherein('spd.demand_status',array('warehouse_dispatched','warehouse_loading','warehouse_loaded'))    
                    ->where('spd.demand_type','inventory_return_to_warehouse');

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_out_products = $store_out_products->whereRaw("DATE(spd.created_at) <= '".$end_date."'");
                        $store_out_products_to_wh = $store_out_products_to_wh->whereRaw("DATE(spd.created_at) <= '".$end_date."'");
                    }

                    $store_out_products = $store_out_products->groupByRaw('ppm.product_sku')
                    ->selectRaw('spd.from_store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_out_count')
                    ->orderByRaw('spd.store_id')->get()->toArray();//print_r($store_out_products);exit;

                    $store_out_products_to_wh = $store_out_products_to_wh->groupByRaw('ppm.product_sku')
                    ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_out_count')
                    ->orderByRaw('spd.store_id')->get()->toArray();

                    $store_in_products  = json_decode(json_encode($store_in_products),true);
                    $store_out_products  = json_decode(json_encode($store_out_products),true);
                    $store_out_products_to_wh  = json_decode(json_encode($store_out_products_to_wh),true);

                    for($i=0;$i<count($store_in_products);$i++){    
                        $store_in_prod[$store_in_products[$i]['store_id'].'_'.$store_in_products[$i]['product_sku']] = $store_in_products[$i];
                        $sku_list[] = $store_in_products[$i]['product_sku'];
                    }

                    for($i=0;$i<count($store_out_products);$i++){    
                        $store_out_prod[$store_out_products[$i]['from_store_id'].'_'.$store_out_products[$i]['product_sku']] = $store_out_products[$i];
                        $sku_list[] = $store_out_products[$i]['product_sku'];
                    }

                    for($i=0;$i<count($store_out_products_to_wh);$i++){    
                        $store_out_prod_wh[$store_out_products_to_wh[$i]['store_id'].'_'.$store_out_products_to_wh[$i]['product_sku']] = $store_out_products_to_wh[$i];
                        $sku_list[] = $store_out_products_to_wh[$i]['product_sku'];
                    }

                    //echo '<br>Closing: ';print_r($store_in_prod);echo '<br/>';print_r($store_out_prod);exit;
                    foreach($store_in_prod as $key=>$inv_in_data){
                        $inv_count = (isset($store_out_prod[$key]))?$inv_in_data['inv_in_count']-$store_out_prod[$key]['inv_out_count']:$inv_in_data['inv_in_count'];
                        $inv_count = (isset($store_out_prod_wh[$key]))?$inv_count-$store_out_prod_wh[$key]['inv_out_count']:$inv_count;
                        $net_price = 0; //(isset($store_out_prod[$key]['net_price']))?$store_out_prod[$key]['net_price']:0;
                        $inv_closing_stock[$key] = array('inv_count'=>$inv_count,'store_base_price'=>$inv_in_data['store_base_price'],'net_price'=>$net_price);
                    }

                    /* Closing Stock End */

                    /* Purchase Start */

                    $store_in_products = \DB::table('store_products_demand_inventory as spdi')
                    ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                    ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')      
                    //->where('ppm.product_sku','LG-285P-B')         
                    ->where('spd.store_id',trim($store_id))        
                    ->where('spdi.transfer_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.arnon_inventory',0)
                    ->where('ppmi.status',1)
                    ->where('spdi.is_deleted',0)
                    ->where('spdi.demand_status',1)        
                    ->where('spd.fake_inventory',0)
                    ->where('spdi.fake_inventory',0)       
                    ->where('ppm.fake_inventory',0)
                    ->where('ppmi.fake_inventory',0)       
                    ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                    ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                    ->where('spd.demand_type','inventory_push');

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_in_products = $store_in_products->whereRaw("(DATE(spd.created_at) >= '".$start_date."' AND DATE(spd.created_at) <= '".$end_date."')");
                    }

                    $store_in_products = $store_in_products->groupByRaw('ppm.product_sku')
                    ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_in_count')
                    ->orderByRaw('spd.store_id')->get()->toArray();

                    $store_in_products  = json_decode(json_encode($store_in_products),true);

                    for($i=0;$i<count($store_in_products);$i++){    
                        $inv_purchase[$store_in_products[$i]['store_id'].'_'.$store_in_products[$i]['product_sku']] = $store_in_products[$i];
                        $sku_list[] = $store_in_products[$i]['product_sku'];
                    }
                    //echo '<br>';print_r($inv_purchase);echo '<br>';//exit;
                    /* Purchase End */

                    /* Sale Start */

                    // Tiki global to other stores sale
                    $store_out_products = \DB::table('store_products_demand_inventory as spdi')
                    ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                    ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                    ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
                    //->where('ppm.product_sku','LG-285P-B')         
                    ->where('spd.from_store_id',trim($store_id))                
                    ->where('spdi.transfer_status',1)        
                    ->where('ppmi.is_deleted',0)
                    ->where('ppmi.arnon_inventory',0)
                    ->where('ppmi.status',1)
                    ->where('spdi.is_deleted',0)
                    ->where('spdi.demand_status',1)        
                    ->where('spd.fake_inventory',0)
                    ->where('spdi.fake_inventory',0)       
                    ->where('ppm.fake_inventory',0)
                    ->where('ppmi.fake_inventory',0)       
                    ->wherein('spd.demand_status',array('loaded','store_loading','store_loaded'))    
                    ->where('spd.demand_type','inventory_transfer_to_store');

                    if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                        $store_out_products = $store_out_products->whereRaw("(DATE(spd.created_at) >= '".$start_date."' AND DATE(spd.created_at) <= '".$end_date."')");
                    }

                    $store_out_products = $store_out_products->groupByRaw('ppm.product_sku')
                    ->selectRaw('spd.from_store_id,spdi.store_base_price as net_price,ppm.product_sku,COUNT(spdi.id) as inv_out_count,spd.created_at')
                    ->orderByRaw('spd.store_id')->get()->toArray();//print_r($store_out_products);exit;

                    $store_out_products  = json_decode(json_encode($store_out_products),true);

                    for($i=0;$i<count($store_out_products);$i++){    
                        $inv_sale[$store_out_products[$i]['from_store_id'].'_'.$store_out_products[$i]['product_sku']] = $store_out_products[$i];
                        $sku_list[] = $store_out_products[$i]['product_sku'];
                    }
                    //print_r($search_date['end_date']);
                    //print_r($inv_sale);exit;

                    /* Sale End */

                    $sku_list = array_values(array_unique($sku_list));

                    $product_list = \DB::table('pos_product_master as ppm')
                    ->join('design_lookup_items_master as dlim','ppm.category_id', '=', 'dlim.id') 
                    ->where('ppm.is_deleted',0)
                    ->where('ppm.fake_inventory',0)        
                    ->groupBy('ppm.product_sku')   
                    ->select('ppm.product_sku','dlim.name as category_name')        
                    ->orderBy('dlim.name')        
                    ->get()->toArray();        

                    // create array to display category of sku
                    for($i=0;$i<count($product_list);$i++){ 
                        $sku_category[$product_list[$i]->product_sku] = $product_list[$i]->category_name;

                        // To sort sku list by category
                        if(in_array($product_list[$i]->product_sku, $sku_list)){
                            $sku_list1[] = $product_list[$i]->product_sku;
                        }
                    }

                    $sku_list = $sku_list1;

                }
            
            }
            
            // Add warehouse data if selected from store filter dropdown or download all csv 
            if(isset($data['s_id']) && $data['s_id']  == -1){
            
                /* HO Start */

                /* Opening stock start */

                $ho_in_products = \DB::table('purchase_order_details as pod')
                ->join('purchase_order_grn_qc as po_grn_qc','pod.id', '=', 'po_grn_qc.po_detail_id')  
                ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc_items.grn_qc_id', '=', 'po_grn_qc.id')          
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'po_grn_qc_items.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->where('po_grn_qc.type','qc')        
                ->where('po_grn_qc_items.qc_status','<',2)
                ->where('pod.is_deleted',0)
                ->where('po_grn_qc.is_deleted',0)
                ->where('po_grn_qc_items.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.arnon_inventory',0)
                ->where('pod.fake_inventory',0)
                ->where('po_grn_qc.fake_inventory',0)
                ->where('po_grn_qc_items.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)        
                ->where('ppm.fake_inventory',0)                
                ->where('ppm.is_deleted',0);

                if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                    $ho_in_products = $ho_in_products->whereRaw("DATE(pod.created_at) < '".$search_date['start_date']."'");
                }

                $ho_in_products = $ho_in_products->groupByRaw('ppm.product_sku')
                ->selectRaw('ppmi.base_price,ppm.product_sku,COUNT(po_grn_qc_items.id) as inv_in_count')
                ->orderByRaw('ppm.product_sku')->get()->toArray();

                $ho_out_products = \DB::table('store_products_demand_inventory as spdi')
                ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->where('spdi.transfer_status',1)        
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)
                ->where('spdi.demand_status',1)        
                ->where('ppm.is_deleted',0)
                ->where('spdi.fake_inventory',0)
                ->where('spd.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                ->where('spd.demand_type','inventory_push');

                if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                    $ho_out_products = $ho_out_products->whereRaw("DATE(spd.created_at) < '".$search_date['start_date']."'");
                }

                $ho_out_products = $ho_out_products->groupByRaw('ppm.product_sku')
                ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_out_count')
                ->orderByRaw('spd.store_id')->get()->toArray();

                $ho_in_products  = json_decode(json_encode($ho_in_products),true);
                $ho_out_products  = json_decode(json_encode($ho_out_products),true);

                for($i=0;$i<count($ho_in_products);$i++){    
                    $ho_in_prod[$ho_in_products[$i]['product_sku']] = $ho_in_products[$i];
                    $sku_list_ho[] = $ho_in_products[$i]['product_sku'];
                }

                for($i=0;$i<count($ho_out_products);$i++){    
                    $ho_out_prod[$ho_out_products[$i]['product_sku']] = $ho_out_products[$i];
                    $sku_list_ho[] = $ho_out_products[$i]['product_sku'];
                }

                foreach($ho_in_prod as $key=>$inv_in_data){
                    $inv_count = (isset($ho_out_prod[$key]))?$inv_in_data['inv_in_count']-$ho_out_prod[$key]['inv_out_count']:$inv_in_data['inv_in_count'];
                    $store_base_price = (isset($ho_out_prod[$key]['store_base_price']))?$ho_out_prod[$key]['store_base_price']:0;
                    $ho_inv_opening_stock[$key] = array('inv_count'=>$inv_count,'base_price'=>$inv_in_data['base_price'],'store_base_price'=>$store_base_price);
                }


                /* Opening stock end */

                /* Closing stock start */

                $ho_in_products = \DB::table('purchase_order_details as pod')
                ->join('purchase_order_grn_qc as po_grn_qc','pod.id', '=', 'po_grn_qc.po_detail_id')  
                ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc_items.grn_qc_id', '=', 'po_grn_qc.id')          
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'po_grn_qc_items.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->where('po_grn_qc.type','qc')        
                ->where('po_grn_qc_items.qc_status','<',2)
                ->where('pod.is_deleted',0)
                ->where('po_grn_qc.is_deleted',0)
                ->where('po_grn_qc_items.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.arnon_inventory',0)
                ->where('pod.fake_inventory',0)
                ->where('po_grn_qc.fake_inventory',0)
                ->where('po_grn_qc_items.fake_inventory',0)        
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->where('ppm.is_deleted',0);

                if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                    $ho_in_products = $ho_in_products->whereRaw("DATE(pod.created_at) <= '".$search_date['end_date']."'");
                }

                $ho_in_products = $ho_in_products->groupByRaw('ppm.product_sku')
                ->selectRaw('ppmi.base_price,ppm.product_sku,COUNT(po_grn_qc_items.id) as inv_in_count')
                ->orderByRaw('ppm.product_sku')->get()->toArray();

                //print_r($store_in_products);

                $ho_out_products = \DB::table('store_products_demand_inventory as spdi')
                ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->where('spdi.transfer_status',1)        
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)
                ->where('spdi.demand_status',1)        
                ->where('ppm.is_deleted',0)
                ->where('spdi.fake_inventory',0)
                ->where('spd.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                ->where('spd.demand_type','inventory_push');

                if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                    $ho_out_products = $ho_out_products->whereRaw("DATE(spd.created_at) <= '".$search_date['end_date']."'");
                }

                $ho_out_products = $ho_out_products->groupByRaw('ppm.product_sku')
                ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_out_count')
                ->orderByRaw('spd.store_id')->get()->toArray();

                $ho_in_products  = json_decode(json_encode($ho_in_products),true);
                $ho_out_products  = json_decode(json_encode($ho_out_products),true);

                for($i=0;$i<count($ho_in_products);$i++){    
                    $ho_in_prod[$ho_in_products[$i]['product_sku']] = $ho_in_products[$i];
                    $sku_list_ho[] = $ho_in_products[$i]['product_sku'];
                }

                for($i=0;$i<count($ho_out_products);$i++){    
                    $ho_out_prod[$ho_out_products[$i]['product_sku']] = $ho_out_products[$i];
                    $sku_list_ho[] = $ho_out_products[$i]['product_sku'];
                }

                foreach($ho_in_prod as $key=>$inv_in_data){
                    $inv_count = (isset($ho_out_prod[$key]))?$inv_in_data['inv_in_count']-$ho_out_prod[$key]['inv_out_count']:$inv_in_data['inv_in_count'];
                    $store_base_price = (isset($ho_out_prod[$key]['store_base_price']))?$ho_out_prod[$key]['store_base_price']:0;
                    $ho_inv_closing_stock[$key] = array('inv_count'=>$inv_count,'base_price'=>$inv_in_data['base_price'],'store_base_price'=>$store_base_price);
                }

                /* Closing stock end */

                /* Purchase stock start */

                $ho_in_products = \DB::table('purchase_order_details as pod')
                ->join('purchase_order_grn_qc as po_grn_qc','pod.id', '=', 'po_grn_qc.po_detail_id')  
                ->join('purchase_order_grn_qc_items as po_grn_qc_items','po_grn_qc_items.grn_qc_id', '=', 'po_grn_qc.id')          
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'po_grn_qc_items.inventory_id')        
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->where('po_grn_qc.type','qc')        
                ->where('po_grn_qc_items.qc_status','<',2)
                ->where('pod.is_deleted',0)
                ->where('po_grn_qc.is_deleted',0)
                ->where('po_grn_qc_items.is_deleted',0)
                ->where('ppmi.is_deleted',0)
                ->where('ppmi.arnon_inventory',0)
                ->where('pod.fake_inventory',0)
                ->where('po_grn_qc.fake_inventory',0)
                ->where('po_grn_qc_items.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->where('ppm.is_deleted',0);

                if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                    $ho_in_products = $ho_in_products->whereRaw("pod.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'");
                }

                $ho_in_products = $ho_in_products->groupByRaw('ppm.product_sku')
                ->selectRaw('ppmi.base_price,ppm.product_sku,COUNT(po_grn_qc_items.id) as inv_in_count')
                ->orderByRaw('ppm.product_sku')->get()->toArray();

                $ho_in_products  = json_decode(json_encode($ho_in_products),true);

                for($i=0;$i<count($ho_in_products);$i++){    
                    $ho_inv_purchase[$ho_in_products[$i]['product_sku']] = $ho_in_products[$i];
                    $sku_list_ho[] = $ho_in_products[$i]['product_sku'];
                }

                /* Purchase stock end */

                /* Sale stock start */

                $ho_out_products = \DB::table('store_products_demand_inventory as spdi')
                ->join('store_products_demand as spd','spdi.demand_id', '=', 'spd.id')  
                ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')  
                ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
                ->where('spdi.transfer_status',1)        
                ->where('ppmi.is_deleted',0)->where('ppmi.arnon_inventory',0)
                ->where('ppmi.status',1)
                ->where('spdi.is_deleted',0)
                ->where('spdi.demand_status',1)        
                ->where('ppm.is_deleted',0)
                ->where('spdi.fake_inventory',0)
                ->where('spd.fake_inventory',0)
                ->where('ppmi.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->wherein('spd.demand_status',array('warehouse_dispatched','store_loading','store_loaded'))    
                ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")
                ->where('spd.demand_type','inventory_push');

                if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                    $ho_out_products = $ho_out_products->whereRaw("spd.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'");
                }

                $ho_out_products = $ho_out_products->groupByRaw('ppm.product_sku')
                ->selectRaw('spd.store_id,spdi.store_base_price,ppm.product_sku,COUNT(spdi.id) as inv_out_count')
                ->orderByRaw('spd.store_id')->get()->toArray();

                $ho_out_products  = json_decode(json_encode($ho_out_products),true);

                for($i=0;$i<count($ho_out_products);$i++){    
                    $ho_inv_sale[$ho_out_products[$i]['product_sku']] = $ho_out_products[$i];
                    $sku_list_ho[] = $ho_out_products[$i]['product_sku'];
                }

                /* Sale stock end */

                $product_list = \DB::table('pos_product_master as ppm')
                ->join('design_lookup_items_master as dlim','ppm.category_id', '=', 'dlim.id') 
                ->where('ppm.is_deleted',0)
                ->where('ppm.fake_inventory',0)        
                ->groupBy('ppm.product_sku')   
                ->select('ppm.product_sku','dlim.name as category_name')        
                ->orderBy('dlim.name')        
                ->get()->toArray();        

                $sku_list_ho = array_values(array_unique($sku_list_ho));
                $sku_list1 = array();

                for($i=0;$i<count($product_list);$i++){ 
                    $sku_category[$product_list[$i]->product_sku] = $product_list[$i]->category_name;
                    // To sort sku list by category
                    if(in_array($product_list[$i]->product_sku, $sku_list_ho)){
                        $sku_list1[] = $product_list[$i]->product_sku;
                    }
                }

                $sku_list_ho = $sku_list1;
			
            }			
            /* HO End */
            
        
            $store_list_total = Store::where('is_deleted',0)->get()->toArray();
            
            $data_array = array('error_message'=>$error_message,'user'=>$user,'store_list'=>$store_list,'inv_opening_stock'=>$inv_opening_stock,'sku_list'=>$sku_list,'inv_closing_stock'=>$inv_closing_stock,'inv_purchase'=>$inv_purchase,'inv_sale'=>$inv_sale,'sku_category'=>$sku_category,'sku_list_ho'=>$sku_list_ho,'ho_inv_opening_stock'=>$ho_inv_opening_stock,'ho_inv_closing_stock'=>$ho_inv_closing_stock,'ho_inv_purchase'=>$ho_inv_purchase,'ho_inv_sale'=>$ho_inv_sale,'store_list_total'=>$store_list_total);
            
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=report_closing_stock.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');

                $columns1 = array('SNo','Particulars','Opening Stock','','','Purchase','','','Sale','','','Closing Stock');
                $columns2 = array('','','Unit','Rate','Value','Unit','Rate','Value','Unit','Rate','Value','Unit','Rate','Value');
                
                $callback = function() use ($data_array, $columns1,$columns2,$data){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns1);
                    fputcsv($file, $columns2);
                    
                    extract($data_array);
                    
                    /* HO Start */
                    
                    if(empty($store_list)){
                        $count = 1;
                        $total_data = array('op_stock_units'=>0,'op_stock_value'=>0,'closing_stock_units'=>0,'closing_stock_value'=>0,'pur_units'=>0,'pur_value'=>0,'sale_units'=>0,'sale_value'=>0);

                        $array[] = 'Warehouse HO';
                        fputcsv($file, $array);

                        for($q=0;$q<count($sku_list_ho);$q++){
                            $key = $sku_list_ho[$q];
                            if(isset($ho_inv_opening_stock[$key]) || isset($ho_inv_purchase[$key]) || isset($ho_inv_sale[$key]) || isset($ho_inv_closing_stock[$key])){

                                $op_stock_unit = (isset($ho_inv_opening_stock[$key]))?$ho_inv_opening_stock[$key]['inv_count']:0;
                                $purchase_unit = (isset($ho_inv_purchase[$key]))?$ho_inv_purchase[$key]['inv_in_count']:0; 
                                $sale_unit = (isset($ho_inv_sale[$key]))?$ho_inv_sale[$key]['inv_out_count']:0;
                                $closing_stock_unit = (isset($ho_inv_closing_stock[$key]))?$ho_inv_closing_stock[$key]['inv_count']:0;

                                if($op_stock_unit > 0 || $purchase_unit > 0 || $sale_unit > 0 || $closing_stock_unit > 0 ){

                                    $array = array($count++,$sku_category[$sku_list_ho[$q]].' '.$sku_list_ho[$q],$op_stock_unit);
                                    $array[] = (isset($ho_inv_opening_stock[$key]))?$ho_inv_opening_stock[$key]['base_price']:0;
                                    $array[] = (isset($ho_inv_opening_stock[$key]))?round($ho_inv_opening_stock[$key]['inv_count']*$ho_inv_opening_stock[$key]['base_price'],2):0;
                                    $array[] = $purchase_unit;
                                    $array[] = (isset($ho_inv_purchase[$key]))?$ho_inv_purchase[$key]['base_price']:0;
                                    $array[] = (isset($ho_inv_purchase[$key]))?round($ho_inv_purchase[$key]['inv_in_count']*$ho_inv_purchase[$key]['base_price'],2):0;
                                    $array[] = $sale_unit;        
                                    $array[] = (isset($ho_inv_sale[$key]))?$ho_inv_sale[$key]['store_base_price']:0;
                                    $array[] = (isset($ho_inv_sale[$key]))?round($ho_inv_sale[$key]['inv_out_count']*$ho_inv_sale[$key]['store_base_price'],2):0;
                                    $array[] = $closing_stock_unit;
                                    $array[] = (isset($ho_inv_closing_stock[$key]))?$ho_inv_closing_stock[$key]['base_price']:0;
                                    $array[] = (isset($ho_inv_closing_stock[$key]))?round($ho_inv_closing_stock[$key]['inv_count']*$ho_inv_closing_stock[$key]['base_price'],2):0;   

                                    fputcsv($file, $array);

                                    $total_data['op_stock_units']+=$op_stock_unit; 
                                    $total_data['op_stock_value']+=(isset($ho_inv_opening_stock[$key]))?($op_stock_unit*$ho_inv_opening_stock[$key]['base_price']):0; 
                                    $total_data['pur_units']+=$purchase_unit; 
                                    $total_data['pur_value']+=(isset($ho_inv_purchase[$key]))?($purchase_unit*$ho_inv_purchase[$key]['base_price']):0; 
                                    $total_data['sale_units']+=$sale_unit; 
                                    $total_data['sale_value']+=(isset($ho_inv_sale[$key]))?($sale_unit*$ho_inv_sale[$key]['store_base_price']):0; 
                                    $total_data['closing_stock_units']+=$closing_stock_unit; 
                                    $total_data['closing_stock_value']+=(isset($ho_inv_closing_stock[$key]))?($closing_stock_unit*$ho_inv_closing_stock[$key]['base_price']):0; 

                                }
                            }
                        }

                        $array = array('Warehouse HO Total','',$total_data['op_stock_units'],'',CommonHelper::currencyFormat($total_data['op_stock_value']),$total_data['pur_units'],'',CommonHelper::currencyFormat($total_data['pur_value']),$total_data['sale_units'],'',CommonHelper::currencyFormat($total_data['sale_value']));
                        $array[] = $total_data['closing_stock_units'];
                        $array[] = '';
                        $array[] = CommonHelper::currencyFormat($total_data['closing_stock_value']);       

                        fputcsv($file, $array);
                    
                    }
                    /* HO End */
                    
                    
                    /* Store Start */
                    
                    if(isset($data['s_id']) && !empty($data['s_id'])){ 
                        $store_list = Store::where('is_deleted',0)->where('status',1)->where('id',$data['s_id'])->get()->toArray();
                    }
                    
                    $total_data = array('op_stock_units'=>0,'op_stock_value'=>0,'closing_stock_units'=>0,'closing_stock_value'=>0,'pur_units'=>0,'pur_value'=>0,'sale_units'=>0,'sale_value'=>0);
                    $store_data = $total_data; $count = 1;
                    
                    for($i=0;$i<count($store_list);$i++){
                        
                        $array = array($store_list[$i]['store_name'].' ('.$store_list[$i]['store_id_code'].')'); 
                        fputcsv($file, $array);
                        
                        for($q=0;$q<count($sku_list);$q++){
                            $key = $store_list[$i]['id'].'_'.$sku_list[$q];
                            if(isset($inv_opening_stock[$key]) || isset($inv_purchase[$key]) || isset($inv_sale[$key]) || isset($inv_closing_stock[$key])){
                                
                                $op_stock_unit = (isset($inv_opening_stock[$key]))?$inv_opening_stock[$key]['inv_count']:0;
                                $purchase_unit = (isset($inv_purchase[$key]))?$inv_purchase[$key]['inv_in_count']:0; 
                                $sale_unit = (isset($inv_sale[$key]))?$inv_sale[$key]['inv_out_count']:0;
                                $closing_stock_unit = (isset($inv_closing_stock[$key]))?$inv_closing_stock[$key]['inv_count']:0;
                            
                                $array = array($count++,$sku_category[$sku_list[$q]].' '.$sku_list[$q]);
                                $array[] = $op_stock_unit;
                                $array[] = (isset($inv_opening_stock[$key]))?$inv_opening_stock[$key]['store_base_price']:0;
                                $array[] = (isset($inv_opening_stock[$key]))?round($inv_opening_stock[$key]['inv_count']*$inv_opening_stock[$key]['store_base_price'],2):0;
                                $array[] = $purchase_unit;
                                $array[] = (isset($inv_purchase[$key]))?$inv_purchase[$key]['store_base_price']:0;        
                                $array[] = (isset($inv_purchase[$key]))?round($inv_purchase[$key]['inv_in_count']*$inv_purchase[$key]['store_base_price'],2):0;
                                $array[] = $sale_unit;
                                $array[] = (isset($inv_sale[$key]))?$inv_sale[$key]['net_price']:0;
                                $array[] = (isset($inv_sale[$key]))?round($inv_sale[$key]['inv_out_count']*$inv_sale[$key]['net_price'],2):0;
                                $array[] = $closing_stock_unit;
                                $array[] = (isset($inv_closing_stock[$key]))?$inv_closing_stock[$key]['store_base_price']:0;        
                                $array[] = (isset($inv_closing_stock[$key]))?round($inv_closing_stock[$key]['inv_count']*$inv_closing_stock[$key]['store_base_price'],2):0;
                                
                                fputcsv($file, $array);
                                
                                $total_data['op_stock_units']+=$op_stock_unit; 
                                $total_data['op_stock_value']+=(isset($inv_opening_stock[$key]))?($op_stock_unit*$inv_opening_stock[$key]['store_base_price']):0; 
                                $total_data['pur_units']+=$purchase_unit; 
                                $total_data['pur_value']+=(isset($inv_purchase[$key]))?($purchase_unit*$inv_purchase[$key]['store_base_price']):0; 
                                $total_data['sale_units']+=$sale_unit; 
                                $total_data['sale_value']+=(isset($inv_sale[$key]))?($sale_unit*$inv_sale[$key]['net_price']):0; 
                                $total_data['closing_stock_units']+=$closing_stock_unit; 
                                $total_data['closing_stock_value']+=(isset($inv_closing_stock[$key]))?($closing_stock_unit*$inv_closing_stock[$key]['store_base_price']):0; 

                                $store_data['op_stock_units']+=$op_stock_unit; 
                                $store_data['op_stock_value']+=(isset($inv_opening_stock[$key]))?($op_stock_unit*$inv_opening_stock[$key]['store_base_price']):0; 
                                $store_data['pur_units']+=$purchase_unit; 
                                $store_data['pur_value']+=(isset($inv_purchase[$key]))?($purchase_unit*$inv_purchase[$key]['store_base_price']):0; 
                                $store_data['sale_units']+=$sale_unit; 
                                $store_data['sale_value']+=(isset($inv_sale[$key]))?($sale_unit*$inv_sale[$key]['net_price']):0; 
                                $store_data['closing_stock_units']+=$closing_stock_unit; 
                                $store_data['closing_stock_value']+=(isset($inv_closing_stock[$key]))?($closing_stock_unit*$inv_closing_stock[$key]['store_base_price']):0; 
                                
                            }
                        }
                        
                        $array = array($store_list[$i]['store_name'].' ('.$store_list[$i]['store_id_code'].')'.' Total','',$store_data['op_stock_units'],'',CommonHelper::currencyFormat($store_data['op_stock_value']),$store_data['pur_units'],'',CommonHelper::currencyFormat($store_data['pur_value']),$store_data['sale_units'],'',CommonHelper::currencyFormat($store_data['sale_value']));
                        $array[] = $store_data['closing_stock_units'];
                        $array[] = '';
                        $array[] = CommonHelper::currencyFormat($store_data['closing_stock_value']);    

                        fputcsv($file, $array);

                        $store_data = array('op_stock_units'=>0,'op_stock_value'=>0,'closing_stock_units'=>0,'closing_stock_value'=>0,'pur_units'=>0,'pur_value'=>0,'sale_units'=>0,'sale_value'=>0); 

                    }
                    
                    $array = array('Stores Total','',$total_data['op_stock_units'],'',CommonHelper::currencyFormat($total_data['op_stock_value']),$total_data['pur_units'],'',CommonHelper::currencyFormat($total_data['pur_value']),$total_data['sale_units'],'',CommonHelper::currencyFormat($total_data['sale_value']));
                    $array[] = $total_data['closing_stock_units'];
                    $array[] = '';
                    $array[] = CommonHelper::currencyFormat($total_data['closing_stock_value']);
                    fputcsv($file, $array);
                    
                    /* Store End */
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers); 
            }
            
            //$data_array['store_list'] = $store_list_total;
            return view('admin/report_closing_stock_detail',$data_array);
        }catch (\Exception $e) {
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/report_closing_stock_detail',array('error_message'=>$e->getMessage().', '.$e->getLine()));
        }
    }
    
    function updatePosProductList(Request $request){
        try{
            set_time_limit(900);
            $data = $request->all();//print_r($data);exit;
            $validateionRules = array('barcodeCsvFile'=>'required|mimes:csv,txt|max:3072','po_id'=>'required');
            $attributes = array('barcodeCsvFile'=>'CSV File','po_id'=>'PO');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $po_id = $data['po_id'];
            
            $file = $request->file('barcodeCsvFile');
            $file_name_text = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(),'.'));
            $file_ext = $file->getClientOriginalExtension();
            $dest_folder = 'documents/product_barcode_csv';

            for($i=0;$i<1000;$i++){
                $file_name = ($i == 0)?$file_name_text.'.'.$file_ext:$file_name_text.'_'.$i.'.'.$file_ext;
                if(!file_exists(public_path($dest_folder.'/'.$file_name))){
                    break;
                }
            }

            if(!isset($file_name)){
                $file_name = $file_name_text.'_'.rand(1000,100000).'.'.$file_ext;
            }

            $file->move(public_path($dest_folder), $file_name);
            
            \DB::beginTransaction();
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $file = public_path($dest_folder.'/'.$file_name);
            if(($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $style = trim($data[0]);
                    $size = trim($data[1]);
                    $size_data = CommonHelper::getArrayRecord($size_list, 'size', $size);
                    if(!empty($size_data)){
                        //$barcode = str_replace(',','',number_format($data[2]));//$data[2];
                        $barcode = trim($data[2]);
                        
                        if(strpos($barcode,'E') !== false || strpos($barcode,'.') !== false || strpos($barcode,'+') !== false){
                            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Invalid barcode: '.$barcode.' for '.$style.', '.$size, 'errors' =>'Invalid barcode: '.$barcode.' for '.$style.', '.$size ));
                        }
                        
                        $product_data = Pos_product_master::where('product_sku',$style)->where('size_id',$size_data['id'])->where('is_deleted',0)->where('status',1)->first();
                        
                        if(empty($product_data)){
                            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Product with SKU: '.$style.', '.$size.' does not exists', 'errors' =>'Product with SKU: '.$style.', '.$size.' does not exists' ));
                        }
                        
                        if(!empty($product_data->product_barcode)){
                            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Product barcode is already added for '.$style.', '.$size, 'errors' =>'Product barcode is already added for '.$style.', '.$size ));
                        }
                        
                        Pos_product_master::where('product_sku',$style)->where('size_id',$size_data['id'])->update(array('product_barcode'=>$barcode));
                        
                        $product_data = Pos_product_master::where('product_sku',$style)->where('size_id',$size_data['id'])->where('is_deleted',0)->where('status',1)->first();
                        
                        $inventory_product = Pos_product_master_inventory::where('peice_barcode','LIKE',$product_data->product_barcode.'%')->where('is_deleted',0)->orderBy('id','ASC')->first();
                        
                        if(!empty($inventory_product)){
                            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Inventory product already exists with '.$inventory_product->peice_barcode, 'errors' =>'Inventory product already exists with '.$inventory_product->peice_barcode ));
                        }
                        
                        $inventory_products = Pos_product_master_inventory::where('po_id',$po_id)->where('product_master_id',$product_data->id)->where('is_deleted',0)->orderBy('id','ASC')->get()->toArray();
                        
                        $inventory_product_barcode = 0;
                        for($i=0;$i<count($inventory_products);$i++){
                            $barcode = $inventory_product_barcode+($i+1);
                            $barcode = str_pad($barcode,3,'0',STR_PAD_LEFT);
                            $barcode = trim($product_data->product_barcode.$barcode);
                            
                            /*$qr_code_exists = Pos_product_master_inventory::where('peice_barcode',$barcode)->select('id')->first();
                            if(!empty($qr_code_exists)){
                                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>$barcode.': QR Code already exists', 'errors' =>$barcode.': QR Code already exists'));
                            }*/
                            
                            $updateArray = array('peice_barcode'=>$barcode);
                            Pos_product_master_inventory::where('id',$inventory_products[$i]['id'])->update($updateArray);
                        }
                    }
                }
            }
            
            \DB::commit();
            
            fclose($handle);
            unlink($file);
           
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Products updated successfully'),200);
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    
    function addPosProductBarcodesToScheduler(Request $request){
        try{
            
            $data = $request->all();
            $sku_id_list = $items_list = [];
            $validateionRules = array('barcodeCsvFile'=>'required|mimes:csv,txt|max:3072','po_id'=>'required');
            $attributes = array('barcodeCsvFile'=>'CSV File','po_id'=>'PO');
            
            $validator = Validator::make($data,$validateionRules,array(),$attributes);
            if ($validator->fails()){ 
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => $validator->errors()));
            }	
            
            $po_id = $data['po_id'];
            
            $file = $request->file('barcodeCsvFile');
            $file_name_text = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(),'.'));
            $file_ext = $file->getClientOriginalExtension();
            $dest_folder = 'documents/product_barcode_csv';

            for($i=0;$i<1000;$i++){
                $file_name = ($i == 0)?$file_name_text.'.'.$file_ext:$file_name_text.'_'.$i.'.'.$file_ext;
                if(!file_exists(public_path($dest_folder.'/'.$file_name))){
                    break;
                }
            }

            if(!isset($file_name)){
                $file_name = $file_name_text.'_'.rand(1000,100000).'.'.$file_ext;
            }

            $file->move(public_path($dest_folder), $file_name);
            
            $sku_list = \DB::table('purchase_order_items as poi')
            ->join('pos_product_master as ppm','ppm.product_sku', '=', 'poi.product_sku')                 
            ->where('poi.order_id',$po_id)
            ->where('poi.is_deleted',0)        
            ->where('ppm.is_deleted',0)                
            ->select('ppm.*')        
            ->get();        
            
            for($i=0;$i<count($sku_list);$i++){
                $key = strtolower($sku_list[$i]->product_sku).'_'.$sku_list[$i]->size_id;
                $sku_id_list[$key] = $sku_list[$i];
            }
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $file = public_path($dest_folder.'/'.$file_name);
            if(($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $style = trim($data[0]);
                    $size = trim($data[1]);
                    $barcode = trim($data[2]);
                    
                    $size_data = CommonHelper::getArrayRecord($size_list, 'size', $size);
                    
                    if(empty($size_data)){
                        fclose($handle);
                        unlink($file);
                        return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Size: '.$size.' does not exists', 'errors' =>'Size: '.$size.' does not exists' ));
                    } 
                    
                    if(strpos($barcode,'E') !== false || strpos($barcode,'.') !== false || strpos($barcode,'+') !== false){
                        fclose($handle);
                        unlink($file);
                        return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Invalid barcode: '.$barcode.' for '.$style.', '.$size, 'errors' =>'Invalid barcode: '.$barcode.' for '.$style.', '.$size ));
                    }

                    $key = strtolower($style).'_'.$size_data['id'];
                    if(!(isset($sku_id_list[$key]) && !empty($sku_id_list[$key]))){
                        fclose($handle);
                        unlink($file);
                        return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Product with SKU: '.$style.', '.$size.' does not exists', 'errors' =>'Product with SKU: '.$style.', '.$size.' does not exists' ));
                    }

                    if(!empty($sku_id_list[$key]->product_barcode)){
                        fclose($handle);
                        unlink($file);
                        return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Product barcode is already added for '.$style.', '.$size, 'errors' =>'Product barcode is already added for '.$style.', '.$size ));
                    }
                    
                    $items_list[] = $data;
                }
            }
            
            fclose($handle);
            unlink($file);
            
            if(!empty($items_list)){
                \DB::beginTransaction();
                
                $po_data = Purchase_order::where('id',$po_id)->first();
                $insertArray = ['task_type'=>'po','task_ref_id'=>$po_data->id,'task_ref_no'=>$po_data->order_no,'task_items_count'=>count($items_list),'items_limit'=>15];
                $task = Scheduled_tasks::create($insertArray);
                
                for($i=0;$i<count($items_list);$i++){
                    $insertArray = ['task_id'=>$task->id,'task_item_data'=>json_encode($items_list[$i])];
                    Scheduled_tasks_details::create($insertArray);
                }
                
                \DB::commit();
            }
           
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Products added to Scheduler Successfully'),200);
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }
    }
    
    
    function executeScheduler(Request $request){
        try{
            if($file_handle = fopen('cron_file.txt', 'a')){fwrite($file_handle, date('Y/m/d H:i:s'));fclose($file_handle);}
            
            $task_data = Scheduled_tasks::wherein('task_status',['pending','in_progress'])->where('is_deleted',0)->orderBy('id')->first();
            $task_in_progress = Scheduled_tasks::where('cron_status',1)->where('is_deleted',0)->first();
            
            if(!empty($task_in_progress)){
                /*if($task_in_progress->attempts_count >= 5){
                    Scheduled_tasks::where('id',$task_in_progress->id)->update(['task_status'=>'error','cron_status'=>2]);
                }else{
                    Scheduled_tasks::where('id',$task_in_progress->id)->increment('attempts_count');
                }*/
                
                return false;
            }
            
            $items_status = ['success'=>0,'fail'=>0];
            
            if(!empty($task_data)){
                if(strtolower($task_data->task_type) == 'po'){
                    $sku_id_list = $size_id_list = $items_id_success =  [];
                    
                    $updateArray = ['task_status'=>'in_progress','cron_status'=>1,'attempts_count'=>0];
                    Scheduled_tasks::where('id',$task_data->id)->update($updateArray);
                    
                    $items_list = Scheduled_tasks_details::where('task_id',$task_data->id)->where('task_item_status',0)->where('is_deleted',0)->limit($task_data->items_limit)->orderBy('id')->get()->toArray();
                    
                    $sku_list = \DB::table('purchase_order_items as poi')
                    ->join('pos_product_master as ppm','ppm.product_sku', '=', 'poi.product_sku')                 
                    ->where('poi.order_id',$task_data->task_ref_id)
                    ->where('poi.is_deleted',0)        
                    ->where('ppm.is_deleted',0)                
                    ->select('ppm.*')        
                    ->get();        
                    
                    $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
                    for($i=0;$i<count($size_list);$i++){
                        $size_id_list[strtolower($size_list[$i]['size'])] = $size_list[$i];
                    }
                    
                    for($i=0;$i<count($sku_list);$i++){
                        $key = strtolower($sku_list[$i]->product_sku).'_'.$sku_list[$i]->size_id;
                        $sku_id_list[$key] = $sku_list[$i];
                    }
                    
                    \DB::beginTransaction();
                    
                    for($i=0;$i<count($items_list);$i++){
                        $item_data = json_decode($items_list[$i]['task_item_data'],true);
                        
                        if(!(is_array($item_data) && count($item_data) == 3)){
                            $updateArray = ['task_item_status'=>3,'error_text'=>'Invalid Data'];
                            Scheduled_tasks_details::where('id',$items_list[$i]['id'])->update($updateArray);
                            $items_status['fail']++;
                            continue;
                        }
                        
                        $style = trim($item_data[0]);
                        $size = trim($item_data[1]);
                        $barcode = trim($item_data[2]);
                        $size_data = $size_id_list[strtolower($size)];
                        
                        $key = strtolower($style).'_'.$size_data['id'];
                        
                        if(!(isset($sku_id_list[$key]) && !empty($sku_id_list[$key]))){
                            $updateArray = ['task_item_status'=>3,'error_text'=>'Product with SKU: '.$style.', '.$size.' does not exists'];
                            Scheduled_tasks_details::where('id',$items_list[$i]['id'])->update($updateArray);
                            $items_status['fail']++;
                            continue;
                        }
                        
                        $product_data = $sku_id_list[$key];
                        $product_data->product_barcode = $barcode;
                                
                        $inventory_product = Pos_product_master_inventory::where('peice_barcode','LIKE',$product_data->product_barcode.'%')->where('is_deleted',0)->orderBy('id','ASC')->first();
                        
                        if(!empty($inventory_product)){
                            $updateArray = ['task_item_status'=>3,'error_text'=>'Inventory product already exists with '.$inventory_product->peice_barcode];
                            Scheduled_tasks_details::where('id',$items_list[$i]['id'])->update($updateArray);
                            $items_status['fail']++;
                            continue;
                        }
                        
                        $inventory_products = Pos_product_master_inventory::where('po_id',$task_data->task_ref_id)->where('product_master_id',$product_data->id)->where('is_deleted',0)->orderBy('id','ASC')->get()->toArray();
                        
                        Pos_product_master::where('product_sku',$style)->where('size_id',$size_data['id'])->update(array('product_barcode'=>$barcode));
                        
                        $inventory_product_barcode = 0;
                        for($q=0;$q<count($inventory_products);$q++){
                            $barcode = $inventory_product_barcode+($q+1);
                            $barcode = str_pad($barcode,3,'0',STR_PAD_LEFT);
                            $barcode = trim($product_data->product_barcode.$barcode);
                            
                            $updateArray = array('peice_barcode'=>$barcode);
                            Pos_product_master_inventory::where('id',$inventory_products[$q]['id'])->update($updateArray);
                        }
                        
                        $items_status['success']++;
                        $items_id_success[] = $items_list[$i]['id'];
                        
                    }
                    
                    if(!empty($items_id_success)){
                        $updateArray = ['task_item_status'=>2,'error_text'=>null];
                        Scheduled_tasks_details::wherein('id',$items_id_success)->update($updateArray);
                    }
                    
                    $updateArray = ['cron_status'=>0];
                    Scheduled_tasks::where('id',$task_data->id)->increment('task_items_comp_count',count($items_list));
                    
                    $task_data = Scheduled_tasks::where('id',$task_data->id)->first();
                    if($task_data->task_items_count == $task_data->task_items_comp_count){
                        $updateArray['task_status'] = 'completed';
                    }
                    
                    Scheduled_tasks::where('id',$task_data->id)->update($updateArray);
                    
                    \DB::commit();
                    
                    echo 'Scheduler completed successfully. Success Items: '.$items_status['success'].', Fail Items: '.$items_status['fail'];
                }
            }
            
        }catch(Exception $e) {
            \DB::rollBack();
            CommonHelper::saveException($e,'SCHEDULER',__FUNCTION__,__FILE__);
            return response(array('httpStatus'=>500,"dateTime"=>time(),'status' => 'fail','message' =>$e->getMessage().', '.$e->getLine()),500);
        }
    }
    
    function schedulerTaskList(Request $request){
        try{
            $data = $request->all();
            $error_message = '';
            
            $task_list = \DB::table('scheduled_tasks as st')
            ->where('st.is_deleted',0)
            ->select('st.*')
            ->orderBy('st.id','DESC')        
            ->paginate(100);
            
            return view('admin/scheduler_task_list',array('error_message'=>$error_message,'task_list'=>$task_list));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/scheduler_task_list',array('error_message'=>$e->getMessage()));
        }
    }
    
    function schedulerTaskDetail(Request $request,$task_id){
        try{
            $data = $request->all();
            $error_message = '';
            
            $task_data = Scheduled_tasks::where('id',$task_id)->first();
            $task_items = Scheduled_tasks_details::where('task_id',$task_data->id)->where('is_deleted',0)->orderBy('id')->paginate(100);
            
            return view('admin/scheduler_task_detail',array('error_message'=>$error_message,'task_data'=>$task_data,'task_items'=>$task_items));
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/scheduler_task_detail',array('error_message'=>$e->getMessage()));
        }
    }
    
    function hsnCodeBillSalesReport(Request $request){
        try{
            ini_set('memory_limit', '-1');
            $data = $request->all();
            $error_message = '';
            $user = Auth::user();
            $bill_list = array();
            $search_date = CommonHelper::getSearchStartEndDate($data,false);
            
            if($user->user_type == 9){
                $store_data = CommonHelper::getUserStoreData($user->id);
                $store_id = $store_data->id;
            }else{
                $store_id = isset($data['s_id'])?trim($data['s_id']):'';
            }
            
            $max_days = (isset($data['action']) && $data['action'] == 'download_csv')?365:61;
            
            if(!empty($search_date['start_date']) && !empty($search_date['end_date']) && CommonHelper::dateDiff($search_date['start_date'],$search_date['end_date']) > $max_days){
                throw new Exception('Date difference should not be more than '.$max_days.' days');
            }
            
            if(!empty($search_date['start_date']) && !empty($search_date['end_date'])){
                $bill_list = \DB::table('pos_customer_orders as pco')
                ->join('pos_customer_orders_detail as pcod','pcod.order_id', '=', 'pco.id')                 
                ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
                ->join('pos_product_master_inventory as ppmi','pcod.inventory_id', '=', 'ppmi.id')        
                ->join('store as s','s.id', '=', 'pco.store_id');        

                if(!empty($store_id)){
                    $bill_list = $bill_list->where('pco.store_id',$store_id);        
                }

                $bill_list = $bill_list->whereRaw("pco.created_at BETWEEN '".$search_date['start_date']."' AND '".$search_date['end_date']."'")        
                ->where('pco.is_deleted',0)
                ->where('pcod.is_deleted',0)
                ->where('ppmi.is_deleted',0)        
                ->where('pco.fake_inventory',0)
                ->where('pcod.fake_inventory',0)
                ->where('ppm.fake_inventory',0)        
                ->where('ppmi.fake_inventory',0)                
                ->where('pco.order_status',1)
                ->where('pcod.order_status',1)               
                ->selectRaw('pco.order_no,pco.created_at as order_date,pcod.*,ppm.product_name,ppm.hsn_code,ppm.product_sku,s.store_name,s.store_id_code,ppmi.peice_barcode')
                ->orderBy('pco.id','ASC')
                ->get()->toArray();
            }
            
            // Download CSV start
            if(isset($data['action']) && $data['action'] == 'download_csv'){
                $headers = array('Content-type' => 'text/csv','Content-Disposition' => 'attachment; filename=hsn_bill_sales_report.csv','Pragma' => 'no-cache','Cache-Control' => 'must-revalidate, post-check=0, pre-check=0','Expires' => '0');
                $columns = array('Bill Date','Bill No','Store Name','Store Code','Product Name','SKU','QR Code','GST%','HSN Code','MRP','Quantity','MRP Value','Discount','Bill Value','CGST Amount','SGST Amount','Taxable Value');
                
                $callback = function() use ($bill_list,$columns){
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    
                    $total = array('qty'=>0,'mrp_val'=>0,'disc'=>0,'bill_val'=>0,'gst'=>0,'taxable_val'=>0);
                    $total_bill = $total;
                    
                    for($i=0;$i<count($bill_list);$i++){
                        $array = array(date('d-m-Y',strtotime($bill_list[$i]->order_date)),$bill_list[$i]->order_no,$bill_list[$i]->store_name,$bill_list[$i]->store_id_code,$bill_list[$i]->product_name,$bill_list[$i]->product_sku,CommonHelper::filterCsvInteger($bill_list[$i]->peice_barcode),$bill_list[$i]->gst_percent,$bill_list[$i]->hsn_code,$bill_list[$i]->sale_price,
                        $bill_list[$i]->product_quantity,$bill_list[$i]->sale_price,$bill_list[$i]->discount_amount_actual,$bill_list[$i]->net_price,round($bill_list[$i]->gst_amount/2,6),round($bill_list[$i]->gst_amount/2,6),$bill_list[$i]->discounted_price_actual);
                         
                        fputcsv($file, $array);
                        
                        $total_bill['qty']+=$bill_list[$i]->product_quantity;
                        $total_bill['mrp_val']+=$bill_list[$i]->sale_price;
                        $total_bill['disc']+=$bill_list[$i]->discount_amount_actual;
                        $total_bill['bill_val']+=$bill_list[$i]->net_price;
                        $total_bill['gst']+=$bill_list[$i]->gst_amount;
                        $total_bill['taxable_val']+=$bill_list[$i]->discounted_price_actual;
                        
                        $total['qty']+=$bill_list[$i]->product_quantity;
                        $total['mrp_val']+=$bill_list[$i]->sale_price;
                        $total['disc']+=$bill_list[$i]->discount_amount_actual;
                        $total['bill_val']+=$bill_list[$i]->net_price;
                        $total['gst']+=$bill_list[$i]->gst_amount;
                        $total['taxable_val']+=$bill_list[$i]->discounted_price_actual;
                        
                        if((isset($bill_list[$i+1]->order_id) && $bill_list[$i]->order_id != $bill_list[$i+1]->order_id) || !isset($bill_list[$i+1]->order_id) ){
                            $array = array('Bill Total','','','','','','','','','',$total_bill['qty'],round($total_bill['mrp_val'],2),round($total_bill['disc'],2),round($total_bill['bill_val'],2),round($total_bill['gst']/2,2),round($total_bill['gst']/2,2),round($total_bill['taxable_val'],2));
                            fputcsv($file, $array);
                            $total_bill = array('qty'=>0,'mrp_val'=>0,'disc'=>0,'bill_val'=>0,'gst'=>0,'taxable_val'=>0);
                        }
                    }
                    
                    if(!empty($bill_list)){
                        $array = array('Total','','','','','','','','','',$total['qty'],round($total['mrp_val'],2),round($total['disc'],2),round($total['bill_val'],2),round($total['gst']/2,2),round($total['gst']/2,2),round($total['taxable_val'],2));
                        fputcsv($file, $array);
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
            
            // Download CSV end
            
            $store_list = CommonHelper::getStoresList();
             
            return view('admin/report_hsn_code_bill_sales',array('error_message'=>$error_message,'bill_list'=>$bill_list,'user'=>$user,'store_list'=>$store_list,'store_id'=>$store_id,'search_date'=>$search_date));
        }catch (\Exception $e) {
            CommonHelper::saveException($e,'HSN_CODE_BILL_REPORT',__FUNCTION__,__FILE__);
            return view('admin/report_hsn_code_bill_sales',array('error_message'=>$e->getMessage()));
        }
    }
    
    //   pos/product/import/csv 
    function importPosProduct(Request $request){
        
        //$this->submitImportPosProduct(1);
        //$this->importPOItems();
        $this->addStores();
        
        try{
            $data = $request->all();
            $user = Auth::user();
            $error_message = '';
            
            return view('admin/pos_product_import',array('error_message'=>$error_message,'user'=>$user));
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            return view('admin/pos_product_import',array('error_message'=>$e->getMessage()));
        }
    }
    
    function submitImportPosProduct($request){
        try{
            set_time_limit(1000);
            
            $sheet_file = 'documents/po/po-156-csv.csv';
            $skuIndex = 4;
            $colorIndex = 5;
            $categoryIndex = 7; 
            $subcategoryIndex = 8; 
            $storyIndex = 9;
            $salePriceIndex = 11;
            $seasonIndex = 12;
            $sizeIndex = 13;
            $productNameIndex = 14; 
            $barcodeIndex = 18;
            $basePriceIndex = 20;
            
            $user = Auth::user();
            $indexList = array($skuIndex,$colorIndex,$categoryIndex,$subcategoryIndex,$seasonIndex,$storyIndex,$sizeIndex,$barcodeIndex,$productNameIndex,$basePriceIndex,$salePriceIndex);
            $error_message = '';
            $seasons = $story = $size = $color = $category = $subcategory = $barcodes_added = $data_list = $data_not_added = array();
            
            $size_list = Production_size_counts::where('is_deleted',0)->where('status',1)->get()->toArray();
            $story_list = Story_master::where('is_deleted',0)->where('status',1)->get()->toArray();//print_r($story_list);exit;
            $season_list = Design_lookup_items_master::where('type','season')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $color_list = Design_lookup_items_master::where('type','color')->where('is_deleted',0)->where('status',1)->get()->toArray();
            $category_list = Design_lookup_items_master::where('type','POS_PRODUCT_CATEGORY')->where('is_deleted',0)->where('status',1)->get()->toArray();
            
            \DB::beginTransaction();
            
            if (($handle = fopen($sheet_file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $data_list[] = $data;
                }
            }
            
            fclose($handle);
            
            for($i=0;$i<count($data_list);$i++){    
                $data = $data_list[$i];
                
                for($q=0;$q<count($indexList);$q++){        
                    $index = $indexList[$q];
                    if(empty(trim($data[$index])) ||  empty(trim($data[$index]))){
                        echo 'Empty data in Row: '.($i+1);
                        exit();
                    }
                }
                
                if(strpos($data[$barcodeIndex],'E') !== false || strpos($data[$barcodeIndex],'.') !== false || strpos($data[$barcodeIndex],'+') !== false){
                    echo 'Invalid Barcode: '.$data[$barcodeIndex].' in Row: '.($i+1);
                    exit();
                }
                
                $product_data = Pos_product_master::where('product_barcode',trim($data[$barcodeIndex]))->where('is_deleted',0)->select('*')->first();
                if(!empty($product_data)){
                    echo 'Barcode Exists: '.$data[$barcodeIndex].' in Row: '.($i+1);
                    exit();
                }
                
                $color_data = CommonHelper::getArrayRecord($color_list,'name',trim($data[$colorIndex]));
                if(empty($color_data)){
                    $data_not_added['color'][] = trim($data[$colorIndex]);
                }
                
                $size_name = ((strpos($data[$sizeIndex],'('))!=false)?trim(substr($data[$sizeIndex],0,strpos($data[$sizeIndex],'('))):$data[$sizeIndex];
                $size_data = CommonHelper::getArrayRecord($size_list,'size',$size_name);
                if(empty($size_data)){
                    $data_not_added['size'][] = trim($data[$sizeIndex]);
                }
                
                $season_data = CommonHelper::getArrayRecord($season_list,'name',trim($data[$seasonIndex]));
                if(empty($season_data)){
                    $data_not_added['season'][] = trim($data[$seasonIndex]);
                }
                
                $story_data = CommonHelper::getArrayRecord($story_list,'name',trim($data[$storyIndex]));
                if(empty($story_data)){
                    $data_not_added['story'][] = trim($data[$storyIndex]);
                }
                
                $category_data = CommonHelper::getArrayRecord($category_list,'name',trim($data[$categoryIndex]));
                if(empty($category_data)){
                    $data_not_added['category'][] = trim($data[$categoryIndex]);
                }
                
                if(!empty($category_data)){
                    $subcategory_data = Design_lookup_items_master::where('type','POS_PRODUCT_SUBCATEGORY')->where('name',$data[$subcategoryIndex])->where('pid',$category_data['id'])->first();
                    if(empty($subcategory_data)){
                        $data_not_added['subcategory'][] = $category_data['id'].': '.trim($data[$subcategoryIndex]);
                    }
                }
            }
            
            foreach($data_not_added as $key=>$elem_data){
                $elem_data = array_unique($elem_data);
                $error_message.=$key.': '.implode(', ',$elem_data).'<br/>';
            }
            
            if(!empty($error_message)){
                echo 'Following data is not added: '.$error_message;
                exit();
            }
                    
            for($i=0;$i<count($data_list);$i++){        
                $data = $data_list[$i];
                
                $size_name = $season_name = $story_name = $color_name = $category_name = $subcategory_name = '';

                $color_data = CommonHelper::getArrayRecord($color_list,'name',trim($data[$colorIndex]));
                $color_id = $color_data['id'];
                
                $size_name = ((strpos($data[$sizeIndex],'('))!=false)?trim(substr($data[$sizeIndex],0,strpos($data[$sizeIndex],'('))):$data[$sizeIndex];
                $size_data = CommonHelper::getArrayRecord($size_list,'size',$size_name);//print_r($size_data);exit;
                $size_id = $size_data['id'];
                
                $season_data = CommonHelper::getArrayRecord($season_list,'name',trim($data[$seasonIndex]));
                $season_id = $season_data['id'];
                
                $story_data = CommonHelper::getArrayRecord($story_list,'name',trim($data[$storyIndex]));
                $story_id = $story_data['id'];
                
                $category_data = CommonHelper::getArrayRecord($category_list,'name',trim($data[$categoryIndex]));
                $category_id = $category_data['id'];
                
                $subcategory_data = Design_lookup_items_master::where('type','POS_PRODUCT_SUBCATEGORY')->where('name',trim($data[$subcategoryIndex]))->where('pid',$category_id)->first();
                $subcategory_id = $subcategory_data['id'];
                    
                $barcode = trim($data[$barcodeIndex]);//str_replace(',','',number_format($data[18]));

                if(in_array($barcode,$barcodes_added)) continue;

                $insertArray = array('product_name'=>trim($data[$productNameIndex]),'product_sku'=>trim($data[$skuIndex]),'category_id'=>$category_id,'subcategory_id'=>$subcategory_id,'base_price'=>trim($data[$basePriceIndex]),'sale_price'=>trim($data[$salePriceIndex]),
                'size_id'=>$size_id,'story_id'=>$story_id,'season_id'=>$season_id,'product_barcode'=>$barcode,'product_description'=>null,'color_id'=>$color_id);

                Pos_product_master::create($insertArray);
                $barcodes_added[] = $barcode;
                
            }
                
            \DB::commit();
            
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            echo 'Exception: '.$e->getMessage();
            exit();
        }
    }
    
    public function importPOItems(){
        try{
            set_time_limit(10000);
            
            $orderId = $poId =  8;
            $quantityIndex = 21;
            $barcodeIndex = 18;
            $rateIndex = 20;
            $file = 'documents/po/po-156-csv.csv';
            
            $barcodes_added = $skuArray = array();
            $product_list = Pos_product_master::where('is_deleted',0)->get()->toArray();
            \DB::beginTransaction();
            $row = 1;
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $quantity = trim($data[$quantityIndex]);
                    $barcode = $data[$barcodeIndex]; //str_replace(',','',number_format($data[15]));
                    
                    if(empty($quantity) || empty($barcode)){
                        echo 'Invalid Quantity or Barcode at row: '.$row;
                        exit();
                    } 
                    
                    if(in_array($barcode,$barcodes_added)) continue;
                    
                    $barcodes_added[] = $barcode;
                    
                    $product_data = CommonHelper::getArrayRecord($product_list,'product_barcode',$barcode);
                    
                    $product_data['csv_data'] = $data;
                    $skuArray[$product_data['product_sku']][] = $product_data;
                    $row++;
                }
                
                fclose($handle);
                
                foreach($skuArray as $sku=>$data_array){
                    $poItem = Purchase_order_items::where('product_sku',$sku)->where('order_id',$poId)->first();
                    if(!empty($poItem)){
                        echo 'SKU: '.$sku.' already exists for PO';
                        exit();
                    }
                    $total_qty = 0;$size_data = array();
                    for($i=0;$i<count($data_array);$i++){
                        $size_id = $data_array[$i]['size_id'];
                        $size_data[$size_id] = $data_array[$i]['csv_data'][$quantityIndex];
                        $total_qty+=$data_array[$i]['csv_data'][$quantityIndex];
                    }
                    
                    $insertArray = array('order_id'=>$orderId,'product_sku'=>$sku,'item_master_id'=>$data_array[0]['id'],'quotation_detail_id'=>$data_array[0]['color_id'],'qty_ordered'=>$total_qty);
                    $insertArray['rate'] = $data_array[0]['csv_data'][$rateIndex];
                    $insertArray['cost'] = round($data_array[0]['csv_data'][$rateIndex]*$total_qty,2);
                    //$gst_per_item = $data_array[0]['csv_data'][18]-$data_array[0]['csv_data'][$rateIndex];     // base price - rate
                    $gst_percent = ($insertArray['rate'] >1000)?12:5;
                    $gst_per_item = round($insertArray['rate']*($gst_percent/100),2);
                    $insertArray['gst_amount'] = round($gst_per_item*$total_qty,2);
                    $insertArray['gst_percent'] = $gst_percent; //round(($gst_per_item/$insertArray['rate'])*100);
                    $insertArray['total_cost'] = $insertArray['cost']+$insertArray['gst_amount'];    
                    $insertArray['size_data'] = json_encode($size_data);
                    $insertArray['vendor_sku'] = $sku;
                    
                    $po_item = Purchase_order_items::create($insertArray);
                    
                    for($i=0;$i<count($data_array);$i++){
                        $product_data = $data_array[$i];
                        $size_id = $product_data['size_id'];
                        $quantity = $product_data['csv_data'][$quantityIndex];
                        
                        $vendor_rate = $product_data['csv_data'][$rateIndex];
                        $vendor_gst_percent = ($vendor_rate >1000)?12:5;
                        $vendor_gst_amount = round($vendor_rate*($vendor_gst_percent/100),2);
                        $base_price = $vendor_rate+$vendor_gst_amount;

                        $inventory_product = Pos_product_master_inventory::where('product_master_id',$product_data['id'])->where('is_deleted',0)->orderBy('id','DESC')->first();
                        $inventory_product_barcode = (!empty($inventory_product))?ltrim(str_replace($product_data['product_barcode'],'',$inventory_product['peice_barcode']),'0'):0;

                        for($q=1;$q<=$quantity;$q++){
                            $barcode = $inventory_product_barcode+$q;
                            $barcode = str_pad($barcode,6,'0',STR_PAD_LEFT);
                            $barcode = $product_data['product_barcode'].$barcode;

                            $insertArray = array('product_master_id'=>$product_data['id'],'peice_barcode'=>$barcode,'po_id'=>$poId,'po_item_id'=>$po_item->id,
                            'product_status'=>0,'base_price'=>$base_price,'sale_price'=>$product_data['sale_price'],
                            'vendor_base_price'=>$vendor_rate,'vendor_gst_percent'=>$vendor_gst_percent,'vendor_gst_amount'=>$vendor_gst_amount);
                            
                            Pos_product_master_inventory::create($insertArray);   
                        }
                    }
                }
                
                \DB::commit();
            }
        }catch (\Exception $e){
            \DB::rollBack();
            CommonHelper::saveException($e,'POS_PRODUCT',__FUNCTION__,__FILE__);
            echo 'Exception: '.$e->getMessage();
            exit(); 
        }
    }
    

    function addStores(){
        
        function z1(){
        
        try{
            set_time_limit(3200);
            $sku_list = $size_list = $category_list = $subcategory_list = $color_list = $season_list =  $skus = $products = $total_data = array();
            $colors_list_not_exists = $colors_list = $size1 = $color1 = $cat1 = [];
            $file = 'documents/po2.csv'; //'documents/qr_code_1.csv';
            $count = $total = 0;
            $date = date('Y/m/d H:i:s');
            $po_id = 264; //198;//197;
            $po_detail_id = 871; //670; //668;
            $grn_id = 1679; //1341; //1340;
            $qc_id = 1680; //1342; //1341;
            
            $sizes = Production_size_counts::where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($sizes);$i++){
                $key = str_replace(' ','_',strtolower($sizes[$i]['size']));
                $size_list[$key] = $sizes[$i]['id'];
            }
            
            $size_list['fs'] = 10;
            
            $categories = Design_lookup_items_master::where('type','POS_PRODUCT_CATEGORY')->where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($categories);$i++){
                $key = strtolower($categories[$i]['name']);
                $category_list[$key] = $categories[$i]['id'];
            }
            
            $subcategories = Design_lookup_items_master::where('type','POS_PRODUCT_SUBCATEGORY')->where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($subcategories);$i++){
                $key = strtolower($subcategories[$i]['name']);
                $subcategory_list[$subcategories[$i]['pid']][$key] = $subcategories[$i]['id'];
            }
            
            $categories = array('KURTI'=>646,'JEANS'=>25,'JEGGINGS'=>25,'LEGGING'=>25,'DUPATTA'=>26,'WESTERN_WEAR'=>24,'PLAZO'=>25,
            'PANTS'=>310,'INNERWEAR'=>18,'NIGHT_WEAR'=>18,'PATIYALA'=>18,'MASK'=>18,'NIGHT_SUIT'=>18,'T_SHIRT'=>27,'PARTY_WEAR'=>24,
            'DRESS'=>24,'C_PANTS'=>310,'LEHENGA'=>18,'DENIM'=>18,'SKIRTS'=>25,'G_SERVICE'=>18,'SHIRT'=>27,'JACKET'=>18,'SCARF'=>18,
            'UNIFORM'=>18,'CHINOS'=>18,'TRACK'=>18,'KIDS'=>18,'ACCESSORIES'=>18,'BAG'=>31,'TRACK_P'=>18,'T___SHIRT'=>27,'TOPS'=>27,'SHRUG'=>18);
            
            $seasons = Design_lookup_items_master::where('type','SEASON')->where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($seasons);$i++){
                $key = strtolower($seasons[$i]['name']);
                $season_list[$seasons[$i]['pid']][$key] = $seasons[$i]['id'];
            }
            
            $colors = Design_lookup_items_master::where('type','COLOR')->where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($colors);$i++){
                $key = strtolower($colors[$i]['name']);
                $color_list[$key] = $colors[$i]['id'];
                //$colors_list[] = strtolower($colors[$i]['name']);
            }
            
            $total_price = 0;
            \DB::beginTransaction();
            
            //$inv_id = 244319;
            
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    /*if($count == 0){
                        $count++;
                        continue;
                    }*/
                    
                    $bal_qty = trim($data[8]);
                    
                    if($bal_qty > 0){
                        $prod_name = trim($data[3]);
                        $sku = trim($data[4]);
                        $color_name = strtolower(trim($data[5]));
                        $size_name = strtolower(trim($data[6]));
                        $category_name = strtolower(trim($data[3]));
                        $category_list['wear-o'] = 18;
                        
                        $base_price = trim($data[9]);
                        
                        $total_price+= $bal_qty*$base_price;
                        continue;
                        /*if(strpos($size_name,'/') !== false){
                            $size_name = explode('/',$size_name);
                            $size_name = $size_name[0];
                        }*/
                        
                        /*if(!isset($category_list[strtolower($category_name)])){
                            $cat1[] = $category_name;
                        }
                        
                        if(!isset($size_list[strtolower($size_name)])){
                            $size1[] = $size_name;
                        }
                        
                        if(!isset($color_list[strtolower($color_name)])){
                            $color1[] = $color_name;
                        }*/
                        
                        
                        
                        //Purchase_order_items::where('product_sku',$sku)->where('quotation_detail_id',$color_id)->selectRaw('SUM()')->first();
                        /*$base_price = trim($data[9]);
                        $cost_price = ($base_price*$bal_qty);
                        
                        $key = strtolower($sku).'_'.$color_id;
                        
                        if(!isset($total_data_1[$key])){
                            $total_data_1[$key] = $cost_price;
                        }else{
                            $total_data_1[$key]+= $cost_price;
                        }*/
                        
                        /*if(!isset($total_data[$key])){
                            $total_data[$key] = $base_price;
                        }else{
                            //$total_data[$key]+= $base_price;
                        }*/
                        
                        //continue;
                        /*if(strpos($size_name,'(') !== false){
                            $size_name  = str_replace(' ','_',substr($size_name,0,strpos($size_name,'(')));
                        }else{
                            $size_name  = str_replace(' ','_',$size_name);
                        }*/
                        
                        /*  if(!isset($color_list[$color_name])){
                            $skus[] =  $sku.' - '.$color_name;//exit;
                        }
                        */
                        
                        /*if(!in_array($prod_name, $products)){
                            $products[] = $prod_name;
                        }
                        continue;*/
                        
                        /*if(!in_array($color_name, $colors_list)){
                            $colors_list_not_exists[] = $color_name;
                        }
                        
                        continue;*/
                        
                        /*if(strtolower($prod_name) == 'innerwear'){
                            //continue;
                        }*/
                        
                        /*
                        if(isset($color_list[$color_name])){
                            $color_id = $color_list[$color_name];
                        }elseif($color_name == '' || $color_name == '-' || $color_name == '1' || $color_name == '-0' || $color_name == '0'){
                            $color_id = 327; // mix
                        }else{
                            //echo 'color not exists: '.$color_name;
                            //exit;
                            $insertArray = array('name'=> strtoupper($color_name),'type'=>'COLOR');
                            $color = Design_lookup_items_master::create($insertArray);
                            $color_id = $color->id;
                            $color_list[$color_name] = $color_id;
                        }*/
                        
                        //continue;
                        /*if(in_array($size_name,['','-','xs s m','l xl 2xl','mask','free size','na','fsl','fsxl','socks','h chief','-0','85']) ){
                            $size_name = 'free';
                        }elseif($size_name == 28 || $size_name == 14 || $size_name == 80){
                            $size_name = 'xs';
                        }elseif($size_name == 30 || $size_name == 16 || $size_name == 85){
                            $size_name = 's';
                        }elseif($size_name == 32 || $size_name == 18 || $size_name == 90){
                            $size_name = 'm';
                        }elseif($size_name == 34 || $size_name == 20 || $size_name == 95){
                            $size_name = 'l';
                        }elseif($size_name == 36 || $size_name == 22){
                            $size_name = 'xl';
                        }elseif($size_name == 38 || $size_name == 24){
                            $size_name = 'xxl';
                        }elseif($size_name == 40 || $size_name == 26){
                            $size_name = '3xl';
                        }elseif($size_name == 42){
                            $size_name = '4xl';
                        }elseif($size_name == 44){
                            $size_name = '5xl';
                        }elseif($size_name == '2xl'){
                            $size_name = 'XXL';
                        }*/
                        
                        $color_id = $color_list[$color_name];
                        $size_id = $size_list[strtolower($size_name)];
                        $category_id = $category_list[strtolower($category_name)];
                        $product = Pos_product_master::where('product_sku',$sku)->where('size_id',$size_id)->where('color_id',$color_id)->where('is_deleted',0)->select('id','product_barcode','base_price')->first();
                        
                        $sale_price = trim($data[7]);
                        /*$base_price = trim($data[1]);
                        $sale_price = trim($data[7]);
                        $base_price = round($base_price*0.25,2);
                        $hsn_code = trim($data[5]);
                        $product_barcode = str_ireplace('u','',trim($data[0]));
                        $product_barcode = str_pad($product_barcode, 6, "0", STR_PAD_LEFT);*/
                        
                        if(empty($product)){
                            
                            //$skus[] = $sku.' - '.$color_name;
                            
                            /*   
                            $category_name = strtolower(trim($data[25]));
                            $subcategory_name = strtolower(trim($data[21]));
                            
                            $season_name = strtolower(trim($data[18]));
                            if($season_name == 'ss20') $season_name = 'ss-20';
                            if($season_name == 'aw20') $season_name = 'aw-20';

                            $category_id = (!empty($category_name))?((isset($category_list[$category_name]))?$category_list[$category_name]:16):16;
                            $subcategory_id = (!empty($category_id) && !empty($subcategory_name))?((isset($subcategory_list[$category_id][$subcategory_name]))?$subcategory_list[$category_id][$subcategory_name]:0):0;
                            if(isset($color_list[$color_name])){
                                $color_id = $color_list[$color_name];
                            }else{
                                $insertArray = array('name'=> strtoupper($color_name),'type'=>'COLOR');
                                $color = Design_lookup_items_master::create($insertArray);
                                $color_id = $color->id;
                            }
                            
                            $season_id = (!empty($season_name))?(isset($season_list[$season_name])?$season_list[$season_name]:0):0;
                            */
                            
                            //$category_id = $categories[str_replace([' ','-'],['_','_'],strtoupper($prod_name))];
                            
                            $product_sku_id = Pos_product_master::where('product_sku',$sku)->select('id','product_sku_id')->orderBy('product_sku_id','DESC')->first();
                            if(empty($product_sku_id)){
                                $product_sku_id = Pos_product_master::select('id','product_sku_id')->orderBy('product_sku_id','DESC')->first();
                                $product_sku_id = $product_sku_id->product_sku_id+1;
                            }else{
                                $product_sku_id = $product_sku_id->product_sku_id;
                            }
                            
                            $base_price = trim($data[9]);
                            $hsn_code  = trim($data[2]);
                            $product_barcode = trim($data[0]);
                            $sale_price = trim($data[7]);
                            $gst_percent = ($base_price > 1049)?12:5;
                        
                            $base_price_orig = $base_price;
                            $gst_percent_1 = 100+$gst_percent;
                            $gst_percent_1 = $gst_percent_1/100;
                            $base_price_1 = round($base_price/$gst_percent_1,2);
                            $gst_amount = $base_price_orig-$base_price_1;

                            $vendor_base_price = $base_price-$gst_amount;
                            $vendor_gst_percent = $gst_percent;
                            $vendor_gst_amount =  $gst_amount;     
                            
                            //echo $base_price = round($vendor_base_price,2);exit;
                        
                            $subcategory_id = $season_id = 0;
                            $insertArray = array('product_name'=>$prod_name,'product_barcode'=>$product_barcode,'product_sku'=>$sku,'category_id'=>$category_id,'subcategory_id'=>$subcategory_id,'base_price'=>$base_price,
                            'sale_price'=>$sale_price,'size_id'=>$size_id,'color_id'=>$color_id,'season_id'=>$season_id,'product_type'=>'sor','hsn_code'=>$hsn_code,'custom_product'=>0,'arnon_product'=>0,'product_sku_id'=>$product_sku_id);
                            
                            //print_r($insertArray);exit;
                            $product = Pos_product_master::create($insertArray);
                        }else{
                            $product_barcode = str_ireplace('u','',$product->product_barcode);//$product->product_barcode;
                        }
                        
                        //continue;
                        $product_id = $product->id;
                        $barcode_length = strlen($product_barcode)+3;
                        
                        $inventory_product = Pos_product_master_inventory::where('peice_barcode','LIKE',$product_barcode.'%')->whereRaw("LENGTH(peice_barcode) = $barcode_length")->orderBy('peice_barcode','DESC')->select('peice_barcode')->first();
                        $inv_barcode = (!empty($inventory_product) && !empty($inventory_product->peice_barcode))?substr($inventory_product->peice_barcode,strlen($inventory_product->peice_barcode)-3):0;
                        
                        //print_r($inv_barcode);;exit;
                        /*$gst_data = CommonHelper::getGSTData($hsn_code,$base_price);
                        if(!empty($gst_data)){
                            $gst_percent = $gst_data->rate_percent;
                        }else{
                            $gst_percent = ($base_price >= 1049)?12:5;
                        }*/
                        
                        /*$gst_percent = ($base_price >= 1049)?12:5;
                        
                        $base_price_orig = $base_price;
                        $gst_percent_1 = 100+$gst_percent;
                        $gst_percent_1 = $gst_percent_1/100;
                        $base_price_1 = round($base_price/$gst_percent_1,2);
                        $gst_amount = $base_price_orig-$base_price_1;
                        
                        $vendor_base_price = $base_price-$gst_amount;
                        $vendor_gst_percent = $gst_percent;
                        $vendor_gst_amount =  $gst_amount;*/       
                        
                        $product_sku_id = $product->product_sku_id;
                        $vendor_base_price = $product->base_price;
                        $vendor_gst_percent = ($vendor_base_price > 1000)?12:5;
                        $vendor_gst_amount = round($vendor_base_price*($vendor_gst_percent/100),2);
                        $base_price = $vendor_base_price+$vendor_gst_amount;
                        
                        for($i=1;$i<=$bal_qty;$i++){
                            $inv_barcode_add = $inv_barcode+($i);
                            $barcode = str_pad($inv_barcode_add,3,'0',STR_PAD_LEFT);
                            $peice_barcode = $product_barcode.$barcode;
                            
                            $insertArray = array('product_master_id'=>$product_id,'peice_barcode'=>$peice_barcode,'product_status'=>1,'vendor_base_price'=>$vendor_base_price,'vendor_gst_percent'=>$vendor_gst_percent,
                            'vendor_gst_amount'=>$vendor_gst_amount,'base_price'=>$base_price,'sale_price'=>$sale_price,'intake_date'=>$date,'arnon_inventory'=>0,'po_id'=>$po_id,'po_detail_id'=>$po_detail_id,
                            'grn_id'=>$grn_id,'qc_id'=>$qc_id,'qc_status'=>1,'qc_date'=>$date,'product_sku_id'=>$product_sku_id);//print_r($insertArray);exit;
                            
                            Pos_product_master_inventory::create($insertArray);
                            //exit;
                        }
                        
                        
                        /*for($i=1;$i<=$bal_qty;$i++){
                            $updateArray = array('product_master_id'=>$product_id);
                            Pos_product_master_inventory::where('id',$inv_id)->update($updateArray);
                            $inv_id++;
                        }*/
                        
                    }
                    
                    //$count++;
                    
                    /*if($count > 1000){
                        break;
                    }*/
                }
            }
            
            $cat1 = array_values(array_unique($cat1));
            $size1 = array_values(array_unique($size1));
            $color1 = array_values(array_unique($color1));
            
            echo $total_price;
            //print_r($total_data);exit;
            /*foreach($total_data as $key=>$value){
                $arr = explode('_',$key);
                //$poi = Purchase_order_items::where('product_sku',$arr[0])->where('quotation_detail_id',$arr[1])->select('total_cost')->first();
                //$total_cost = $poi->total_cost;
                //if(round($total_cost) != round($value)){
                    
                    $base_price = $value;
                    
                    $gst_percent = 5;

                    $base_price_orig = $base_price;
                    $gst_percent_1 = 100+$gst_percent;
                    $gst_percent_1 = $gst_percent_1/100;
                    $base_price_1 = round($base_price/$gst_percent_1,2);
                    $gst_amount = $base_price_orig-$base_price_1;

                    $vendor_base_price = $base_price-$gst_amount;
                    
                    $updateArray = ['rate'=>$vendor_base_price];
                    Purchase_order_items::where('product_sku',$arr[0])->where('quotation_detail_id',$arr[1])->update($updateArray);
                    
                //}
            }*/
            
            /*foreach($total_data_1 as $key=>$value){
                $arr = explode('_',$key);
                $poi = Purchase_order_items::where('product_sku',$arr[0])->where('quotation_detail_id',$arr[1])->select('total_cost')->first();
                if(isset($poi->total_cost)){
                $total_cost = $poi->total_cost;
                if(round($total_cost) != round($value)){
                    echo $key.' , '.$total_cost.' , '.$value.'<br>';//exit;
                }
                }
            }*/
            
            //print_r(implode(', ',$color1));print_r($size1);print_r($cat1);
           // $skus = array_unique($skus);
            /*$colors_list_not_exists = array_unique($colors_list_not_exists);
            sort($colors_list_not_exists);
            for($i=0;$i<count($colors_list_not_exists);$i++){
                if(strlen($colors_list_not_exists[$i]) >1){
                    $insertArray = array('name'=> strtoupper($colors_list_not_exists[$i]),'type'=>'COLOR');
                    $color = Design_lookup_items_master::create($insertArray);
                }
            }
            
            var_dump($colors_list_not_exists);*/
        
            \DB::commit();
        }catch(Exception $e){
            \DB::rollBack();
            echo $e->getMessage().', Line: '.$e->getLine();
        }
        
        }
        
        //exit;
        
        /*
        try{
            \DB::beginTransaction();
            
            $po_id = 264; //197;
            $grn_id = 1679; //1340;
            $qc_id = 1680; //1341;
            $date = date('Y/m/d H:i:s');
            $inv_list = Pos_product_master_inventory::where('po_id',$po_id)->select('id')->get()->toArray();
            
            
            for($i=0;$i<count($inv_list);$i++){
                $insertArray = array('grn_qc_id'=>$grn_id,'inventory_id'=>$inv_list[$i]['id'],'grn_qc_date'=>$date);
                Purchase_order_grn_qc_items::create($insertArray);
            }
            
            for($i=0;$i<count($inv_list);$i++){
                $insertArray = array('grn_qc_id'=>$qc_id,'inventory_id'=>$inv_list[$i]['id'],'grn_qc_date'=>$date,'qc_status'=>1);
                Purchase_order_grn_qc_items::create($insertArray);
            }
            
            \DB::commit();
        }catch(Exception $e){
            \DB::rollBack();
            echo $e->getMessage().', Line: '.$e->getLine();
        }
        
        */
        
        
        /*
        
        try{
            set_time_limit(4260);
            \DB::beginTransaction();
            
            $vendor_id = 41;
            $po_id = 264; //197;
            $grn_id = 1679; //1340;
            $qc_id = 1680; //1341;
            $date = date('Y/m/d H:i:s');
            
            $inv_list = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')  
            ->where('ppmi.po_id',$po_id)
            //->where('ppmi.id','>=',733299)        
            //->where('ppmi.id','<=',733298)        
            ->where('ppmi.is_deleted',0)                
            ->select('ppmi.*','ppm.product_sku','ppm.size_id','ppm.color_id')
            ->get()->toArray();        
            
            for($i=0;$i<count($inv_list);$i++){
                $product = Pos_product_master::where('product_sku',$inv_list[$i]->product_sku)->where('color_id',$inv_list[$i]->color_id)->select('id','product_sku','color_id')->orderBy('id')->first();
                $poi = Purchase_order_items::where('order_id',$po_id)->where('product_sku',$inv_list[$i]->product_sku)->where('quotation_detail_id',$inv_list[$i]->color_id)->select('id','size_data')->first();
                //var_dump($product);exit;
                
                if(empty($poi)){
                    $size_data = json_encode(array($inv_list[$i]->size_id=>1));
                    
                    $insertArray = array('order_id'=>$po_id,'product_sku'=>$product->product_sku,'vendor_sku'=>$product->product_sku,'item_master_id'=>$product->id,'quotation_detail_id'=>$product->color_id,
                    'vendor_id'=>$vendor_id,'qty_ordered'=>1,'qty_received'=>1,'rate'=>$inv_list[$i]->vendor_base_price,'cost'=>0,'gst_amount'=>0,'gst_percent'=>$inv_list[$i]->vendor_gst_percent,'total_cost'=>0,'size_data'=>$size_data);
                    
                    $poi = Purchase_order_items::create($insertArray);
                }else{
                    $size_data = json_decode($poi->size_data,true);
                    $size_data[$inv_list[$i]->size_id] = (isset($size_data[$inv_list[$i]->size_id]))?$size_data[$inv_list[$i]->size_id]+1:1;
                    $size_data = json_encode($size_data);
                    
                    \DB::update("UPDATE purchase_order_items set qty_ordered = qty_ordered+1, qty_received = qty_received+1,size_data = '$size_data' where id = ".$poi->id);
                }
                
                $updateArray = array('po_item_id'=>$poi->id);
                Pos_product_master_inventory::where('id',$inv_list[$i]->id)->update($updateArray);
            }
            
            \DB::commit();
        }catch(Exception $e){
            \DB::rollBack();
            echo $e->getMessage().', Line: '.$e->getLine();
        }
        
        */
        
       
        //}
        
        function z2(){
                
        try{//exit;
            set_time_limit(900);
            
            $po_ids = [203,206,208,209];
            $demand_id = 1188;

            $product_inv = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
            ->wherein('ppmi.po_id',$po_ids)                
            ->where('ppmi.is_deleted',0)                
            ->where('ppmi.process_inv',0)        
            ->select('ppmi.*','ppm.hsn_code','ppm.product_name')
            ->orderBy('ppmi.id')        
            ->limit(10000)        
            ->get()->toArray(); 

            \DB::beginTransaction();

            //$company_data = CommonHelper::getCompanyData();
            $demand_data = Store_products_demand::where('id',$demand_id)->first();
            $store_data = Store::where('id',$demand_data->store_id)->first();
            $date = date('Y/m/d H:i:s');

            for($i=0;$i<count($product_inv);$i++){
                $product_data = $product_inv[$i];
                // Add 10% to vendor base price if store is franchise
                //$store_base_rate = ($store_data->store_type == 1)?$product_data->vendor_base_price:round($product_data->vendor_base_price+($product_data->vendor_base_price*.10),2);
                
                //$store_base_rate = round($product_data->base_price+($product_data->base_price*0.30),2);
                /*$gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                
                if(!empty($gst_data)){
                    $gst_percent = $gst_data->rate_percent;
                }else{
                    $gst_percent = ($store_base_rate >= 1000)?12:5;
                }*/
                
                if(strtolower($product_data->product_name) != 'innerwear'){
                    $store_base_rate = round($product_data->sale_price*0.14,2);
                }else{
                    $store_base_rate = round($product_data->base_price*0.55,2);
                }
                
                $gst_percent = ($store_base_rate >= 1000)?12:5;
                
                $gst_amount = round($store_base_rate*($gst_percent/100),2);
                
                $store_base_price = $store_base_rate+$gst_amount;

                $updateArray = array('product_status'=>4,'store_id'=>$demand_data->store_id,'demand_id'=>$demand_data->id,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'store_assign_date'=>$date,'process_inv'=>1);
                Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 
                
                $updateArray = array('store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price);
                Store_products_demand_inventory::where('demand_id',$demand_data->id)->where('inventory_id',$product_data->id)->update($updateArray);

                /*
                $demand_product = Store_products_demand_detail::where('demand_id',$demand_data->id)->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->first();
                if(empty($demand_product)){
                    $insertArray = array('demand_id'=>$demand_data->id,'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                    Store_products_demand_detail::create($insertArray);
                }else{
                    $demand_product->increment('product_quantity');
                }

                $insertArray = array('demand_id'=>$demand_data->id,'inventory_id'=>$product_data->id,'transfer_status'=>1,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'transfer_date'=>$date);
                Store_products_demand_inventory::create($insertArray);*/
            }
                
            \DB::commit();
        }catch(Exception $e){
            \DB::rollBack();
            echo $e->getMessage().', Line: '.$e->getLine();
        }
        
        }
        
        function updateCsvInv(){
        try{
            set_time_limit(900);
            \DB::beginTransaction();
            $count = 0;
            $color_list = $size_list = array();
            
            $colors = Design_lookup_items_master::where('type','COLOR')->where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($colors);$i++){
                $key = strtolower($colors[$i]['name']);
                $color_list[$key] = $colors[$i]['id'];
            }
            
            $sizes = Production_size_counts::where('is_deleted',0)->get()->toArray();
            for($i=0;$i<count($sizes);$i++){
                $key = str_replace(' ','_',strtolower($sizes[$i]['size']));
                $size_list[$key] = $sizes[$i]['id'];
            }
            $size_list['fs'] = 10;
            
            $inv_list = \DB::table('inv_qty')->get()->toArray();
            for($i=0;$i<count($inv_list);$i++){
                $size_name = trim(strtolower($inv_list[$i]->size));
                $color_name = trim(strtolower($inv_list[$i]->color));
                $size_id = $size_list[$size_name];
                $color_id = $color_list[$color_name];
                
                $updateArray = ['size_id'=>$size_id,'color_id'=>$color_id];
                
                \DB::table('inv_qty')->where('id',$inv_list[$i]->id)->update($updateArray);
            }
            
            /*$file = 'documents/warehouse_list.csv';
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $qty = trim($data[8]);
                    $barcode = trim($data[0]);
                    if($qty > 0){
                        $insert_array = array('barcode'=>$barcode,'sku'=>trim($data[2]),'size'=>trim($data[3]),'color'=>trim($data[4]),
                        'hsn_code'=>trim($data[5]),'item_name'=>trim($data[6]),'cost_price'=>trim($data[1]),'mrp'=>trim($data[7]),'qty'=>$qty);

                        \DB::table('inv_qty')->insert($insert_array);
                    }
                    
                }
            }*/
           
            \DB::commit();
        }catch(Exception $e){
            \DB::rollBack();
            echo $e->getMessage().', Line: '.$e->getLine().', '.$inv_list[$i]->id;
        }
        }
        
        function z3(){
        // For innerwear, cost_price = cost_price * 0.25, mrp = mrp
        // For other, mrp = cost_price * 6, cost_price = cost_price * 0.25.
        //SELECT item_name,sku,size, color, COUNT(id) AS cnt  FROM inv_qty GROUP BY item_name,sku,size, color  ORDER BY cnt DESC LIMIT 1000
        //SELECT * FROM pos_product_master WHERE product_barcode LIKE 'U%'
        //SELECT * FROM  `inv_qty` WHERE sku = '1391' AND item_name = 'DUPATTA' AND size_id = 10 AND color_id = 327
        
        try{
            set_time_limit(900);
            \DB::beginTransaction();
            $count = 0;
            $color_list = $size_list = array();
            
            $products = Pos_product_master::where('product_barcode','LIKE','U%')
            ->where('product_rate_updated',0)        
            //->where('id',34068)        
            ->limit(1000)
            ->get()->toArray();
            
            for($i=0;$i<count($products);$i++){
                $product_name = trim($products[$i]['product_name']);
                
                $inv = \DB::table('inv_qty')->where('item_name',$product_name)->where('sku',$products[$i]['product_sku'])
                ->where('size_id',$products[$i]['size_id'])->where('color_id',$products[$i]['color_id'])    
                ->select('cost_price','mrp')->orderBy('cost_price','DESC')->first();        //print_r($inv);echo round($products[$i]['base_price']*4,2);exit;
                
                //if(round($products[$i]['base_price']*4,2) != $inv->cost_price){
                    //echo $products[$i]['product_name'];echo $products[$i]['id'];
                    //print_r($inv);exit;
                    if(strtolower($product_name) != 'innerwear'){
                        $base_price = $inv->cost_price;
                        $sale_price = round($base_price*6,2);
                        $base_price = round($base_price*0.25,2);
                    }else{
                        $base_price = $inv->cost_price;
                        $sale_price = $inv->mrp;
                        $base_price = round($base_price*0.25,2);
                    }
                    
                    $gst_percent = ($base_price >= 1049)?12:5;
                        
                    $base_price_orig = $base_price;
                    $gst_percent_1 = 100+$gst_percent;
                    $gst_percent_1 = $gst_percent_1/100;
                    $base_price_1 = round($base_price/$gst_percent_1,2);
                    $gst_amount = $base_price_orig-$base_price_1;

                    $vendor_base_price = $base_price-$gst_amount;
                    $vendor_gst_percent = $gst_percent;
                    $vendor_gst_amount =  $gst_amount;       
                    
                    $updateArray = array('base_price'=>$base_price,'sale_price'=>$sale_price,'product_rate_updated'=>1);
                    Pos_product_master::where('id',$products[$i]['id'])->update($updateArray);
                    
                    $updateArray = array('vendor_base_price'=>$vendor_base_price,'vendor_gst_percent'=>$vendor_gst_percent,'vendor_gst_amount'=>$vendor_gst_amount,'base_price'=>$base_price,'sale_price'=>$sale_price);
                    Pos_product_master_inventory::where('product_master_id',$products[$i]['id'])->update($updateArray);
                    //break;
                //}
            }
           
            \DB::commit();
        }catch(Exception $e){
            \DB::rollBack();
            echo $e->getMessage().', Line: '.$e->getLine().', '.$inv_list[$i]->id;
        }
        
        }
        
        
        
        
        
        // Tikki global to other stores. 
        // Make -1 and 1 as zero. Search product with same name, sku and mrp. Size and color not required to be same.
        // If mrp is equal to cost price, then update mrp to 3 x mrp, but not for innerwear
        function z6(){
            
            try{
                $count = 1;
                set_time_limit(1800);
                $demand_id = 1374;
                $push_demand_id = 1188;
                
                $sizes = Production_size_counts::where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($sizes);$i++){
                    $key = strtolower($sizes[$i]['size']);
                    $size_list[$key] = $sizes[$i]['id'];
                }

                $size_list['fs'] = 10;

                $colors = Design_lookup_items_master::where('type','COLOR')->where('is_deleted',0)->get()->toArray();
                for($i=0;$i<count($colors);$i++){
                    $key = strtolower($colors[$i]['name']);
                    $color_list[$key] = $colors[$i]['id'];
                }
                
                $demand_data = \DB::table('store_products_demand as spd')
                ->where('spd.id',$demand_id)->where('spd.status',1)->where('spd.is_deleted',0)
                ->select('spd.*')->first();
                
                /*if($demand_data->demand_status != 'loading'){
                    echo 'Demand status is not loading';
                    exit;
                }*/

                \DB::beginTransaction();
                
                /*$file = 'documents/csv/magalgaon.csv';
                if(($handle = fopen($file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                        $sku = trim($data[3]);
                        $name = trim($data[4]);
                        $color = trim($data[5]);
                        $size = trim($data[7]);
                        $qty = trim($data[8]);
                        
                        $insertArray = ['sku'=>$sku,'name'=>$name,'color'=>$color,'size'=>$size,'qty'=>$qty];
                        \DB::table('csv_data')->insert($insertArray);
                    }
                }*/
                
                
                /*$csv_data = \DB::table('csv_data')->where('process_inv',0)->where('qty','<',0)->orderBy('id')->get()->toArray();
                for($z=0;$z<count($csv_data);$z++){
                    //print_r($csv_data[$z]);echo '<br>';
                    $negative_count = abs($csv_data[$z]->qty);
                    $name  = trim($csv_data[$z]->name);
                    $sku = trim($csv_data[$z]->sku);
                    $inv_plus = \DB::table('csv_data')->where('name',$name)->where('sku',$sku)->where('process_inv',0)->where('qty','>',0)->orderBy('id')->get()->toArray();
                    $negative_added = 0;
                    //print_r($inv_plus);echo '<br>';//exit;
                    for($i=0;$i<count($inv_plus);$i++){
                        $plus_count = $inv_plus[$i]->qty;
                        if($negative_count == $plus_count){
                            \DB::update("UPDATE csv_data set qty = -".$negative_count.", minus_added = 1 where id = ".$inv_plus[$i]->id);
                            $negative_added = $negative_count;
                        }elseif($negative_count < $plus_count){
                            \DB::update("UPDATE csv_data set qty = qty-".$negative_count.", minus_added = 1 where id = ".$inv_plus[$i]->id);
                            $negative_added = $negative_count;
                        }elseif($negative_count > $plus_count){
                            \DB::update("UPDATE csv_data set qty = qty-".$plus_count.", minus_added = 1 where id = ".$inv_plus[$i]->id);
                            $negative_added+=$plus_count;
                        }
                        //exit;
                        if($negative_added == $negative_count){
                            break;
                        }
                    }
                }*/
                
                
                //\DB::commit();
                 
                //exit;
                
                $csv_data = \DB::table('csv_data')->where('process_inv',0)->where('qty','>',0)->orderBy('id')->limit(500)->get()->toArray();

                for($z=0;$z<count($csv_data);$z++){

                    $product_name = trim($csv_data[$z]->name);
                    $sku = trim($csv_data[$z]->sku);
                    $size_name = strtolower(trim($csv_data[$z]->size));
                    $color_name = strtolower(trim($csv_data[$z]->color));
                    $qty = trim($csv_data[$z]->qty);
                    $date = date('Y/m/d H:i:s');

                    if($qty <= 0){
                        continue;
                    }

                    if(isset($color_list[$color_name])){
                        $color_id = $color_list[$color_name];
                    }elseif($color_name == '' || $color_name == '-' || $color_name == '1' || $color_name == '-0' || $color_name == '0'){
                        $color_id = 327; // mix
                    }else{
                        echo 'color not exists: '.$color_name;
                        exit;
                    }

                    if(!isset($size_list[$size_name])){
                        if(in_array($size_name,['','-','xs s m','l xl 2xl','mask','free size','na','fsl','fsxl','socks','h chief','-0','85']) ){
                            $size_name = 'free';
                        }elseif($size_name == 28 || $size_name == 14 || $size_name == 80){
                            $size_name = 'xs';
                        }elseif($size_name == 30 || $size_name == 16 || $size_name == 85){
                            $size_name = 's';
                        }elseif($size_name == 32 || $size_name == 18 || $size_name == 90){
                            $size_name = 'm';
                        }elseif($size_name == 34 || $size_name == 20 || $size_name == 95){
                            $size_name = 'l';
                        }elseif($size_name == 36 || $size_name == 22){
                            $size_name = 'xl';
                        }elseif($size_name == 38 || $size_name == 24){
                            $size_name = 'xxl';
                        }elseif($size_name == 40 || $size_name == 26){
                            $size_name = '3xl';
                        }elseif($size_name == 42){
                            $size_name = '4xl';
                        }elseif($size_name == 44){
                            $size_name = '5xl';
                        }elseif($size_name == '2xl'){
                            $size_name = 'XXL';
                        }
                    }

                    $size_id = $size_list[strtolower($size_name)];

                    $product = Pos_product_master::where('product_name',$product_name)->where('product_sku',$sku)->where('size_id',$size_id)->where('color_id',$color_id)->where('is_deleted',0)->first();

                    if(!empty($product)){
                        $inv_list = Pos_product_master_inventory::where('product_master_id',$product->id)->where('demand_id',$push_demand_id)->limit($qty)->orderBy('id')->get();

                        if(count($inv_list) < $qty){
                            echo 'product inv does not exists '.$product_name.'  '.(count($inv_list)-$qty).'<br>';
                            //exit;
                        }

                        for($i=0;$i<count($inv_list);$i++){
                            $inv = $inv_list[$i];

                            $store_base_rate = round($inv->store_base_price*0.35,2);
                            $gst_percent = ($store_base_rate >= 1000)?12:5;
                            $gst_amount = round($store_base_rate*($gst_percent/100),2);
                            $store_base_price = $store_base_rate+$gst_amount;

                            $insertArray = array('demand_id'=>$demand_id,'inventory_id'=>$inv->id,'transfer_status'=>1,'transfer_date'=>$date,'push_demand_id'=>$inv->demand_id,'base_price'=>$inv->base_price,'sale_price'=>$inv->sale_price,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'receive_status'=>1,'receive_date'=>$date);

                            Store_products_demand_inventory::create($insertArray);

                            // Add/update store demand detail row
                            $demand_product = Store_products_demand_detail::where('demand_id',$demand_id)->where('product_id',$inv->product_master_id)->where('is_deleted',0)->first();
                            if(empty($demand_product)){
                               $insertArray = array('demand_id'=>$demand_id,'store_id'=>null,'product_id'=>$inv->product_master_id,'product_quantity'=>1,'po_item_id'=>$inv->po_item_id); 
                               Store_products_demand_detail::create($insertArray);
                            }else{
                                $demand_product->increment('product_quantity');
                            }

                            $updateArray = array('store_id'=>$demand_data->store_id,'demand_id'=>$demand_id,'product_status'=>4,'store_intake_date'=>$date,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price);
                            Pos_product_master_inventory::where('id',$inv->id)->update($updateArray); 

                        }
                    }else{
                        echo 'product does not exists '.$product_name.'  '.$count.'<br>';
                        //exit;
                    }

                    \DB::table('csv_data')->where('id',$csv_data[$z]->id)->update(['process_inv'=>1]);

                    $count++;
                }
               
                \DB::commit();
            }catch(Exception $e){
                \DB::rollBack();
                echo $e->getMessage().', Line: '.$e->getLine();
            }
            
        }
        
        
        
        function z8(){
            try{
                set_time_limit(1800);
                
                \DB::beginTransaction();
                $po_id = 227;
                $demand_id = 1513;
                $demand_data = Store_products_demand::where('id',$demand_id)->first();//print_r($demand_data);
                
                $inv_list = Pos_product_master_inventory::where('po_id',$po_id)->where('is_deleted',0)->get();
                $company_data = CommonHelper::getCompanyData();
                $store_data = Store::where('id',$demand_data->store_id)->first();
                
                for($i=0;$i<count($inv_list);$i++){
                   $product_data = $inv_list[$i];

                    // Warehouse to Tikki global else other store
                    if(!empty($demand_data->transfer_field) && !empty($demand_data->transfer_percent)){
                        $store_base_rate = ($demand_data->transfer_field == 'base_price')?$product_data->base_price:$product_data->sale_price;
                        $store_base_rate = round($store_base_rate*($demand_data->transfer_percent/100),2);
                        $gst_data = []; //CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                        if(!empty($gst_data)){
                            $gst_percent = $gst_data->rate_percent;
                        }else{
                            $gst_percent = ($store_base_rate >= 1000)?12:5;
                        }

                        $gst_amount = round($store_base_rate*($gst_percent/100),2);

                    }else{
                        // Add 10% to vendor base price if store is franchise
                        $store_base_rate = ($store_data->store_type == 1)?$product_data->vendor_base_price:round($product_data->vendor_base_price+($product_data->vendor_base_price*.10),2);

                        if($store_data->gst_no != $company_data['company_gst_no']){
                            $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$store_base_rate);
                            $gst_percent = $gst_data->rate_percent;
                            $gst_amount = round($store_base_rate*($gst_percent/100),2);
                        }else{
                            $gst_percent = $gst_amount = 0;
                        }
                    }

                    $store_base_price = $store_base_rate+$gst_amount;
                    $product_data->store_base_price = $store_base_price;

                    $updateArray = array('product_status'=>2,'store_id'=>$demand_data->store_id,'demand_id'=>$demand_data->id,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'store_assign_date'=>date('Y/m/d H:i:s'));
                    Pos_product_master_inventory::where('id',$product_data->id)->update($updateArray); 

                    $demand_product = Store_products_demand_detail::where('demand_id',$demand_data->id)->where('product_id',$product_data->product_master_id)->where('is_deleted',0)->first();
                    if(empty($demand_product)){
                        $insertArray = array('demand_id'=>$demand_data->id,'product_id'=>$product_data->product_master_id,'product_quantity'=>1,'po_item_id'=>$product_data->po_item_id);
                        Store_products_demand_detail::create($insertArray);
                    }else{
                        $demand_product->increment('product_quantity');
                    }

                    $insertArray = array('demand_id'=>$demand_data->id,'inventory_id'=>$product_data->id,'transfer_status'=>1,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,'transfer_date'=>date('Y/m/d H:i:s'));
                    Store_products_demand_inventory::create($insertArray);

                }
                
                \DB::commit();
            }catch(Exception $e){
                \DB::rollBack();
                echo $e->getMessage().', Line: '.$e->getLine();
            }
        }
        
        function z9(){
            try{
                set_time_limit(1800);
                \DB::beginTransaction();
                
                $stores = [1,2,3,4,6,7,16,18,21,22,25,27,30,31,34,12,13,26,36,5,29,32,38,49,28,37,46,39,11,23,33,14,10,41,19,40,42,45,43,69,44,47,48,72,67];
                $stores = [70,71,76,79,74,60,75,77,78,59,80,68,51,55,73,81,52,56,54,57,53,65,61,64,63,62];
                /*$stores_names = Store::wherein('id',$stores)->get();
                for($i=0;$i<count($stores_names);$i++){
                     echo $stores_names[$i]->store_name.'<br>';
                }
                
                exit;*/
                exit;
                $stores = [57,53,65,61,64,63,62];
                $exc_skus = ['K-A-21-MN-17-202','K-A-21-MN-17-203','K-A-21-MN-17-204','K-A-21-MN-17-205','K-A-21-MN-17-206','K-A-21-MN-17-207',
                'K-A-21-SK-09-088','K-A-21-SK-09-089','K-A-21-SK-09-090','K-A-21-SK-09-091','K-A-21-SK-09-092'];
                
                $skus = Pos_product_master::where('is_deleted',0)->selectRaw('DISTINCT product_sku,arnon_product,sale_price')->get()->toArray();
                for($i=0;$i<count($stores);$i++){
                    for($q=0;$q<count($skus);$q++){
                        $mrp = $skus[$q]['sale_price'];
                        $sku = $skus[$q]['product_sku'];
                        if(in_array($sku, $exc_skus)){
                            continue;
                        }
                        $discount = ($mrp < 1000)?25:70;
                        $inv_type = ($skus[$q]['arnon_product'] == 1)?2:1;
                        $insertArray = array('store_id'=>$stores[$i],'sku'=>$sku,'from_date'=>'2021/12/24','to_date'=>'2022/12/24','discount_type'=>1,'discount_percent'=>$discount,'gst_including'=>1,'inv_type'=>$inv_type);
                        //print_r($insertArray);exit;
                        Discount::create($insertArray);
                    }
                }
                
                \DB::commit();
                exit;
                
                $category_list = Design_lookup_items_master::wherein('type',array('POS_PRODUCT_CATEGORY'))->orderBy('name','ASC')->get()->toArray();
                for($i=0;$i<count($category_list);$i++){
                    $name = str_replace(' ', '_', trim(strtolower($category_list[$i]['name'])));
                    $categories[$name] = $category_list[$i]['id'];
                }
                
                //$file = 'documents/discounts.csv';
                $file = 'documents/sku.csv';
                if (($handle = fopen($file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                        for($i=0;$i<count($stores);$i++){
                            $sku = trim($data[0]);
                            $mrp = trim($data[1]);
                            //$discount = trim($data[1]);
                            
                            if(!empty($sku)){
                                //$category_name = str_replace(' ', '_', trim(strtolower($data[2])));
                                //$category_id = $categories[trim($category_name)];
                                $discount = ($mrp < 1000)?25:80;
                                $insertArray = array('store_id'=>$stores[$i],'sku'=>$sku,'from_date'=>'2021/12/24','to_date'=>'2022/12/24','discount_type'=>1,'discount_percent'=>$discount,'gst_including'=>1,'inv_type'=>2);
                                //print_r($insertArray);exit;
                                Discount::create($insertArray);
                            }
                        }
                    }
                }
                
                \DB::commit();
            }catch(Exception $e){
                \DB::rollBack();
                echo $e->getMessage().', Line: '.$e->getLine().$category_name;
            }
        }
        
        function z10(){
            try{
                set_time_limit(1000);
                ///\DB::beginTransaction();
                $qr_codes_all = $qr_codes = $qr_codes_data = [];
                $count = 0;
                
                $date = '2022/08/01';
                $purchase_orders = \DB::table('purchase_order as po')
                ->where('id','>=',100)        
                ->where('process_start',0)        
                //->where('id','<=',540)                
                //->whereRaw("DATE(created_at) >= '$date'")        
                ->where('po.is_deleted',0)        
                ->wherein('type_id',array(3,5))
                ->limit(1)        
                ->get()->toArray();     //print_r($purchase_orders);   exit;
                
                $updateArray = ['process_start'=>1];
                Purchase_order::where('id',$purchase_orders[0]->id)->update($updateArray);
                
                for($i=0;$i<count($purchase_orders);$i++){
                    $order_id = $purchase_orders[$i]->id;
                    
                    $po_items = \DB::table('purchase_order_items as poi')
                    ->where('poi.order_id',$order_id)       
                    ->where('poi.is_deleted',0)        
                    ->select('poi.id','poi.product_sku','poi.order_id')
                    ->orderBy('poi.id','ASC')
                    ->get()->toArray();//print_r($po_items);exit;
                    
                    for($q=0;$q<count($po_items);$q++){
                        $sku = $po_items[$q]->product_sku;
                        $po_id = $po_items[$q]->order_id;
                        
                        $po_inventory = \DB::table('pos_product_master_inventory as ppmi')
                        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
                        ->where('ppm.product_sku',$sku)
                        ->where('ppmi.po_id',$po_id)        
                        ->where('ppmi.product_status','>',0)        
                        ->where('ppmi.is_deleted',0)
                        ->where('ppmi.status',1)
                        ->where('ppmi.fake_inventory',0)        
                        ->where('ppm.is_deleted',0)
                        ->groupBy('ppm.size_id')
                        ->selectRaw('ppm.size_id,COUNT(ppmi.id) as cnt')
                        ->get()->toArray();       
                        
                        $size_items  = [];
                        $rec_total = 0;
                        for($z=0;$z<count($po_inventory);$z++){
                            $size_items[$po_inventory[$z]->size_id] = $po_inventory[$z]->cnt;
                            $rec_total+=$po_inventory[$z]->cnt;
                        }
                        
                        $size_rec_data = json_encode($size_items);
                        $updateArray = ['qty_received'=>$rec_total,'qty_received_actual'=>$rec_total,'size_data_received'=>$size_rec_data];
                        //print_r($updateArray);echo '<br>';///exit;
                        Purchase_order_items::where('id',$po_items[$q]->id)->update($updateArray);
                    }
                    
                    $updateArray = ['process_end'=>1];
                    Purchase_order::where('id',$order_id)->update($updateArray);
                }
                

                
                //\DB::commit();
                exit;
                
                $file = 'store_gst_update.csv';
                if(($handle = fopen($file, "r")) !== FALSE) {
                   while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                        if($count == 0){
                            $count++;
                            continue;
                        }
                        
                        $store_id = trim($data[0]);
                        $store_name = trim($data[1]);
                        $old_gst_no = trim($data[2]);
                        $old_gst_name = trim($data[3]);
                        $new_gst_no = trim($data[4]);
                        $new_gst_name = trim($data[5]);
                        $old_date = '2022/08/31';
                        $new_date = '2022/09/01';
                        
                        /** Store GST and pos orders data update start **/
                        
                        //\DB::enableQueryLog();
                        /*$updateArray = ['store_gst_no'=>$old_gst_no,'store_gst_name'=>$old_gst_name];
                        Pos_customer_orders::where('store_id',$store_id)->whereRaw("DATE(created_at) <= '$old_date'")->update($updateArray);
                        //$laQuery = \DB::getQueryLog();print_r($laQuery);exit;
                        
                        $updateArray = ['store_gst_no'=>$new_gst_no,'store_gst_name'=>$new_gst_name];
                        Pos_customer_orders::where('store_id',$store_id)->whereRaw("DATE(created_at) >= '$new_date'")->update($updateArray);
                        
                        $updateArray = ['gst_no'=>$new_gst_no,'gst_name'=>$new_gst_name];
                        Store::where('id',$store_id)->update($updateArray);*/
                        
                        /** Store GST and pos orders data update end **/
                        
                        /** Code to update demand data start **/
                        
                        /*$demands_list = Store_products_demand::where('store_id',$store_id)->whereRaw("DATE(created_at) >= '$new_date'")->get()->toArray();
                        for($i=0;$i<count($demands_list);$i++){
                            if(!empty($demands_list[$i]['store_data'])){
                                $store_data = json_decode($demands_list[$i]['store_data'],true);
                                $store_data['gst_no'] = $new_gst_no;
                                $store_data['gst_name'] = $new_gst_name;
                                $updateArray = ['store_data'=>json_encode($store_data)];
                                
                                Store_products_demand::where('id',$demands_list[$i]['id'])->update($updateArray);
                            }
                        }
                        
                        $demands_list = Store_products_demand::where('from_store_id',$store_id)->whereRaw("DATE(created_at) >= '$new_date'")->get()->toArray();
                        for($i=0;$i<count($demands_list);$i++){
                            if(!empty($demands_list[$i]['from_store_data'])){
                                $from_store_data = json_decode($demands_list[$i]['from_store_data'],true);
                                $from_store_data['gst_no'] = $new_gst_no;
                                $from_store_data['gst_name'] = $new_gst_name;
                                $updateArray = ['from_store_data'=>json_encode($from_store_data)];
                                
                                Store_products_demand::where('id',$demands_list[$i]['id'])->update($updateArray);
                            }
                        }*/
                        
                        // Sql to update company gst in demand table. Add two columns in demand table
                        //UPDATE `store_products_demand` SET company_gst_no = '09AATFK3764C1Z0',company_gst_name = 'KIAASA RETAIL LLP' WHERE DATE(created_at) <= '2022/08/31'
                        //UPDATE `store_products_demand` SET company_gst_no = '09AAJCK5771A1ZH',company_gst_name = 'KIAASA RETAIL PRIVATE LIMITED' WHERE DATE(created_at) >= '2022/09/01'
                        
                        /** Code to update demand data end **/
                        
                        $count++;
                    }
                    
                    fclose($handle);
                }
                
                /*$company_data = CommonHelper::getCompanyData();
                $company_data['company_name'] = 'KIAASA RETAIL LLP';
                $company_data['company_gst_no'] = '09AATFK3764C1Z0';
                $updateArray = ['company_data'=>json_encode($company_data)];
                
                $po_list = Purchase_order::whereRaw("DATE(created_at) <= '$old_date'")->get()->toArray();
                for($i=0;$i<count($po_list);$i++){
                    Purchase_order::where('id',$po_list[$i]['id'])->update($updateArray);
                }
                
                
                $company_data = CommonHelper::getCompanyData();
                $updateArray = ['company_data'=>json_encode($company_data)];
                
                $po_list = Purchase_order::whereRaw("DATE(created_at) >= '$new_date'")->get()->toArray();
                for($i=0;$i<count($po_list);$i++){
                    Purchase_order::where('id',$po_list[$i]['id'])->update($updateArray);
                }*/
                
                
                \DB::commit();
            }catch(Exception $e){
                \DB::rollBack();
                echo $e->getMessage().', Line: '.$e->getLine();
            }
        }
        
    }
    
}
