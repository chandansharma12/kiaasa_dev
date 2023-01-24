<?php
namespace App\Helpers;

use Illuminate\Http\Request;
use App\Models\Design;
use App\Models\Design_sizes;
use App\Models\User;
use App\Models\Notification;
use App\Models\App_logs;
use App\Models\Pos_product_master_inventory;
use App\Models\Pos_product_master;
use App\Models\Purchase_order_details;
use App\Models\Purchase_order_items;
use App\Models\Purchase_order_grn_qc;
use App\Models\Discount;
use App\Models\Pos_customer;
use App\Models\Pos_customer_orders;
use App\Models\Pos_customer_orders_detail;
use App\Models\Pos_customer_orders_payments;
use App\Models\Pos_customer_orders_drafts;
use App\Models\Store;
use App\Models\Store_inventory;
use App\Models\Store_inventory_balance;
use App\Models\Category_hsn_code;
use App\Models\Store_products_demand;
use App\Models\Store_products_demand_detail;
use App\Models\Store_products_demand_inventory;
use App\Models\Store_products_demand_sku;
use App\Models\Vendor_detail;
use App\Models\Coupon_items;
use App\Models\Debit_notes;
use App\Models\Discount_list;
use App\Models\PosProductWishlist;
use App\Models\Pos_customer_orders_errors;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use DateTime;
use Carbon\Carbon;
use PDF;
        
class CommonHelper
{
    public static function getCurrency($params = array())
    {
        return 'INR';
    }
    
    public static function getDBArray($array,$key,$value){
        $dbArray = array();
        
        if(isset($array[0]) && !is_array($array[0])){
            $str = json_encode($array);
            $array = json_decode($str,true);
        }
        
        for($i=0;$i<count($array);$i++){
            if($array[$i][$key] == $value){
                $dbArray[] = $array[$i];
            }
        }
        
        return $dbArray;
    }
    
    public static function getArrayRecord($array,$key,$value){
        $dbArray = array();
        if(isset($array[0]) && !is_array($array[0])){
            $str = json_encode($array);
            $array = json_decode($str,true);
        }
        
        for($i=0;$i<count($array);$i++){
            if(strtolower($array[$i][$key]) == strtolower($value)){
                $dbArray = $array[$i];
                break;
            }
        }
        
        return $dbArray;
    }
    
    public static function DBArrayExists($array,$key,$value){
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
    
    public static function searchArrayByKeys($source_array,$search_array){
        if(isset($source_array[0]) && !is_array($source_array[0])){
            $str = json_encode($source_array);
            $source_array = json_decode($str,true);
        }
        
        $val_exists = $index = false;
        for($i=0;$i<count($source_array);$i++){
            foreach($search_array as $key=>$value){
                if(strtolower($source_array[$i][$key]) == strtolower($value)){
                    $val_exists = true;
                }else{
                    $val_exists = false;
                    break;
                }
            }
            
            if($val_exists == true){
                $index = $i;
                break;
            }
        }
        
        return $index;
    }
    
    public static function getSortOrder($sort_by,$default_sort_column = false,$default_sort_order = 'ASC'){
        if($default_sort_column){            
            if(!isset($_GET['sort_by'])){
                if(strtolower($default_sort_order) == 'asc') $sort_order =  'DESC';else $sort_order =  'ASC';
            }else{
                $sort_order = 'ASC';
            }
        }else{
            $sort_order = 'ASC';
        } 
        
        if(isset($_GET['sort_by']) && strtolower($_GET['sort_by']) == strtolower($sort_by) && isset($_GET['sort_order'])){
           $sort_order = (strtolower($_GET['sort_order']) == 'asc')?'DESC':'ASC';
        }
        
        return $sort_order;
    }
    
    public static function getSortIcon($sort_by){
        $icon = '';
        
        if(isset($_GET['sort_by']) && strtolower($_GET['sort_by']) == strtolower($sort_by) && isset($_GET['sort_order'])){
            $icon = (strtolower($_GET['sort_order']) == 'asc')?'<i class="fas fa-angle-up"></i>':'<i class="fas fa-angle-down"></i>';
        }
        
        return $icon;
    }
    
    public static function getDefaultSortIcon($default_sort_order = 'ASC'){
        if(strtolower($default_sort_order) == 'asc')
            return '<i class="fas fa-angle-up"></i>';
        else
            return '<i class="fas fa-angle-down"></i>';
    }
    
    public static function getSortingOrder(){
        return (!empty(request('sort_order')) && in_array(strtolower(request('sort_order')),array('asc','desc')))?strtoupper(request('sort_order')):'ASC';
    }
    
    public static function getSortLink($name,$sort_by,$url,$default_sort_column = false,$default_sort_order = 'ASC'){
        $link = '<a href="'.url($url).'?sort_by='.$sort_by.'&sort_order='.CommonHelper::getSortOrder($sort_by,$default_sort_column,$default_sort_order).'">'.$name.' ';
        if($default_sort_column)
            if(empty(request('sort_by'))) $link.=CommonHelper::getDefaultSortIcon($default_sort_order);else $link.=CommonHelper::getSortIcon($sort_by);
        else
            $link.=CommonHelper::getSortIcon($sort_by);
        return $link.'</a>';
    }
    
    public static function getEditableFields($type,$role_id){
        //$user = \Auth::user();
        $fields = array();
        //$role_id = $user->user_type;
        if($type == 1){
            if($role_id == 5){  //Fabric
                $fields = array('design_id','design_item_id','design_type_id','body_part_id','width','avg','rate','cost','size','qty','fabric_instance_id','unit_id','image_name','comments','body_part','content_id','gsm_id','name','color');
            }elseif($role_id == 2){
                $fields = array('avg','cost');
            }elseif($role_id == 3){
                $fields = array('rate','unit_id','cost');
            }elseif($role_id == 7){
                $fields = array('rate','cost');
            }
        }elseif($type == 2){ // Accessories
            if($role_id == 5){
                $fields = array('design_id','design_item_id','design_type_id','body_part_id','width','avg','rate','cost','size','qty','fabric_instance_id','unit_id','image_name','comments','category_id','subcategory_id');
            }elseif($role_id == 2){
                $fields = array('avg','rate','unit_id','cost');
            }
        }elseif($type == 3){ // Fabric Process
            if($role_id == 5){
                $fields = array('design_id','design_item_id','design_type_id','body_part_id','width','avg','rate','cost','size','qty','fabric_instance_id','unit_id','image_name','comments','fabric_id','category_id','type_id');
            }elseif($role_id == 2){
                $fields = array('avg','cost');
            }elseif($role_id == 3){
                $fields = array('rate','unit_id','cost');
            }elseif($role_id == 7){
                $fields = array('rate','cost');
            }
        }elseif($type == 4){    // Packaging sheet
            if($role_id == 5){
                $fields = array('design_id','design_item_id','design_type_id','body_part_id','width','avg','rate','cost','size','qty','fabric_instance_id','unit_id','image_name','comments');
            }elseif($role_id == 2){
                $fields = array('avg','rate','unit_id','cost');
            }
        }elseif($type == 5){    // Production Process
            if($role_id == 5){
                $fields = array('design_id','design_item_id','design_type_id','body_part_id','width','avg','rate','cost','size','qty','fabric_instance_id','unit_id','image_name','comments','name_id');
            }elseif($role_id == 2){
                $fields = array('avg','rate','unit_id','cost');
            }
        }
        
        return $fields;
    }
    
    public static function getEditSizeVariationTypes($role_id){
        $types = array();
        if($role_id == 2){ //production user
            $types = array(1,2,3,4);
        }
        
        return $types;
    }
    
    public static function hasPermission($permission_key,$role_id){
        //if($role_id == 1) return true;
        
        $permission_status = false;
        
        if(strtolower($permission_key) == 'review_designer_design'){
            if($role_id == 4){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'display_design_history_links'){
            if($role_id == 4 || $role_id == 1){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'display_production_history_links'){
            if($role_id == 2 || $role_id == 7 || $role_id == 1){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'upload_production_document'){
            if($role_id == 2){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'request_production_review'){
            if($role_id == 2){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'review_production_design'){
            if($role_id == 7){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'send_production_notification'){
            if($role_id == 2){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'update_store_order'){
            if($role_id == 10 || $role_id == 1){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'update_store_demand'){
            if($role_id == 10 || $role_id == 1){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'update_push_demand'){
            if($role_id == 1){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'update_push_demand'){
            if($role_id == 1){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'search_filter_inventory_store'){
            if($role_id == 1 || $role_id == 6){
                $permission_status = true;
            }
        }elseif(strtolower($permission_key) == 'update_product_barcode_by_csv'){
            if($role_id == 1){
                $permission_status = true;
            }
        }
        
        return $permission_status;
    }
    
    public static function hasRoutePermission($route_key,$role_id){
        $permission = \DB::table('permissions as p')
        ->join('roles_permissions as rp','p.id', '=', 'rp.permission_id')
        ->where('p.route_key',$route_key)->where('rp.role_id',$role_id)->select('p.id')->first();        
        
        return (!empty($permission))?true:false;
    }
    
    public static function getDesignDetail($design_id,$history = false,$version = '',$history_type = ''){
        
        $design_table = ($history == false)?'designs':'designs_history';
        $design_data = \DB::table($design_table.' as d')
        ->leftJoin('users as u1','u1.id','=','d.user_id')        
        ->leftJoin('users as u2','u2.id','=','d.reviewer_id')            
        ->leftJoin('users as u3','u3.id','=','d.production_id')         
        ->leftJoin('users as u4','u4.id','=','d.production_head_id')           
        ->leftJoin('design_lookup_items_master as s','d.season_id','=','s.id')
        ->leftJoin('story_master as sm','d.story_id','=','sm.id')
        ->leftJoin('design_lookup_items_master as c','c.id','=','d.color_id')
        ->leftJoin('product_category_master as dt','d.design_type_id','=','dt.id')
        //->leftJoin('product_category_master as dc1','dc1.id','=','d.category_id')
        //->leftJoin('product_category_master as dc2','dc2.id','=','d.sub_cat_id')
        ->leftJoin('design_lookup_items_master as d1','d1.id','=','d.category_id')
        ->leftJoin('design_lookup_items_master as d2','d2.id','=','d.sub_cat_id')        
        //->leftJoin('production_size_counts as psc','psc.id','=','d.size_id')
        ->where('d.is_deleted',0);
        
        if($history == true && $version != '') {
            $design_data = $design_data->where('d.design_id',$design_id)->where('d.history_type',$history_type);
            if(strtolower($history_type) == 'design'){
                $design_data = $design_data->where('d.version',$version);
            }else{
                $design_data = $design_data->where('d.production_version',$version);
            }
        }else{
            $design_data = $design_data->where('d.id',$design_id);
        }
        
        $design_data = $design_data->select('d.*','s.name as season_name','sm.name as story_name','dt.name as design_type_name','d1.name as category_name','d2.name as subcat_name','c.name as color_name',
        'u1.name as designer_name','u2.name as reviewer_name','u2.email as reviewer_email','u3.name as production_name','u4.name as production_head_name',
        'u4.email as production_head_email')->first();
        
        $design_sizes = \DB::table('design_sizes as ds')
        ->Join('production_size_counts as psc','ds.size_id','=','psc.id')        
        ->where('ds.design_id',$design_id)  
        ->where('ds.is_deleted',0)        
        ->where('psc.is_deleted',0)                
        ->select('ds.*','psc.size')
        ->orderBy('psc.id')        
        ->get()->toArray();
        
        if(!empty($design_data)){ 
            $design_data->design_id = $design_id;
            $design_data->design_sizes = $design_sizes;
        }
        
        return $design_data;
    }
    
    public static function getDesignsList($whereArray = array(),$orderArray = array(),$otherParamsArray = array()){
        //try{ 
            $data = request()->all();
            $order_by = (isset($orderArray['order_by']))?$orderArray['order_by']:'d.id DESC';
            $paginate = (isset($orderArray['paginate']))?$orderArray['paginate']:30;
            
            $designs = \DB::table('designs as d')
            ->leftJoin('design_lookup_items_master as s','d.season_id','=','s.id')
            ->leftJoin('design_lookup_items_master as c','c.id','=','d.color_id')
            ->leftJoin('product_category_master as dt','d.design_type_id','=','dt.id')
            ->leftJoin('product_category_master as dc1','dc1.id','=','d.category_id')
            ->leftJoin('product_category_master as dc2','dc2.id','=','d.sub_cat_id')
            ->leftJoin('production_size_counts as psc','psc.id','=','d.size_id')
            ->leftJoin('users as u1','u1.id','=','d.user_id')        
            ->leftJoin('users as u2','u2.id','=','d.reviewer_id')         
            ->leftJoin('users as u3','u3.id','=','d.production_id') 
            ->leftJoin('users as u4','u4.id','=','d.production_head_id')         
            ->leftJoin('story_master as sm','sm.id','=','d.story_id')
            ->leftJoin('design_images as di',function($join){
            $join->on('d.id','=','di.design_id')
                ->where('di.image_type','=','front')
                ->where('di.is_deleted','=','0')     
                ->where('di.status','=','1');
            });                
                    
            $designs = $designs->where($whereArray);
            
            if(isset($otherParamsArray['where_raw']) && !empty($otherParamsArray['where_raw'])){
                $whereRawArray = $otherParamsArray['where_raw'];
                for($i=0;$i<count($whereRawArray);$i++){
                    $designs = $designs->whereRaw($whereRawArray[$i]);
                }
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'d.id','article'=>'d.sku','story'=>'sm.name','prod_count'=>'d.production_count','product'=>'dt.name','designer'=>'u1.name',
                'version'=>'d.version','dh_status'=>'d.reviewer_status','ph_status'=>'d.production_status','created'=>'d.created_at','updated'=>'d.updated_at');                
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'d.id';
                $sort_order = CommonHelper::getSortingOrder();
                $designs = $designs->orderBy($sort_by,$sort_order);
            }else{
                $designs = $designs->orderByRaw($order_by);
            }
            
            $designs = $designs->select('d.*','s.name as season_name','sm.name as story_name','dt.name as design_type_name','dc1.name as category_name','dc2.name as subcat_name',
            'c.name as color_name','psc.size as size_name','u1.name as designer_name','sm.name as story_name','u2.name as reviewer_name','u3.name as production_name',
            'u4.name as production_head_name','di.image_name')
            ->paginate($paginate);

            return $designs;
        /*}catch (\Exception $e){
            return $e->getMessage();
        }*/
    }
    
    public static function sendNotificationEmail($notification_id,$params = array()){
        
        try{

            if($notification_id == 1){
                $design_id = $params['ref_id'];
                $design_data = CommonHelper::getDesignDetail($design_id);

                $template_data = \DB::table('notification_templates')->where('id',$notification_id)->first();

                $email_subject = $template_data->email_subject;
                $search_array = array('{DESIGN_SKU}');
                $replace_array = array($design_data->sku);
                $email_subject = str_replace($search_array,$replace_array,$email_subject);

                $email_body = $template_data->email_body;
                $search_array = array('{DESIGN_SKU}','{DESIGNER_HEAD_NAME}','{STORY}','{PRODUCT}','{DESIGNER_NAME}','{DESIGN_CREATION_DATE}');
                $replace_array = array($design_data->sku,$design_data->reviewer_name,$design_data->story_name,$design_data->design_type_name,$design_data->designer_name,$design_data->created_at);
                $email_body = str_replace($search_array,$replace_array,$email_body);

                $designer_data = User::where('id',$design_data->user_id)->where('status',1)->where('is_deleted',0)->first();

                if(!isset($designer_data['parent_user']) || empty($designer_data['parent_user'])){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Designer Head does not exists', 'errors' =>'Designer Head does not exists' ));
                }

                $from_user_id = $design_data->user_id;
                $to_user_id = $designer_data['parent_user'];
                $to_role_id = 4;
                $template_id = 1;
                $reference_id = $design_id;
                $to_email = $design_data->reviewer_email;
                
                /**  Notification to Reviewer(Designer head)    **/
                //mail($to_email,$email_subject,$email_body);
                $insertArray = array('from_user_id'=>$from_user_id,'to_user_id'=>$to_user_id,'to_role_id'=>$to_role_id,'template_id'=>$template_id,'reference_id'=>$reference_id,
                'notification_text'=>$email_subject);
                Notification::create($insertArray);
                
                /**  Notification to Administrator    **/
                $requsition_created_hours_diff = ((strtotime(date('Y/m/d H:i:s')) - strtotime($design_data->requisition_created_date))/3600);
                if($requsition_created_hours_diff >= 48){
                    $admin_user_data = User::where('user_type',1)->where('status',1)->where('is_deleted',0)->first();
                    if(!empty($admin_user_data)){
                        //mail($admin_user_data['email'],$email_subject,$email_body);
                        $insertArray = array('from_user_id'=>$from_user_id,'to_user_id'=>$admin_user_data->id,'to_role_id'=>$admin_user_data->user_type,'template_id'=>$template_id,'reference_id'=>$reference_id,
                        'notification_text'=>$email_subject);
                        Notification::insert($insertArray);
                    }
                }
                                        
            }elseif($notification_id == 2){
                $design_id = $params['ref_id'];
                $design_data = CommonHelper::getDesignDetail($design_id);

                $template_data = \DB::table('notification_templates')->where('id',$notification_id)->first();

                $email_subject = $template_data->email_subject;
                $search_array = array('{DESIGN_SKU}');
                $replace_array = array($design_data->sku);
                $email_subject = str_replace($search_array,$replace_array,$email_subject);

                $email_body = $template_data->email_body;
                $search_array = array('{DESIGN_SKU}','{PRODUCTION_HEAD_NAME}','{STORY}','{PRODUCT}','{DESIGNER_NAME}','{PRODUCTION_NAME}','{DESIGN_CREATION_DATE}');
                $replace_array = array($design_data->sku,$design_data->production_head_name,$design_data->story_name,$design_data->design_type_name,$design_data->designer_name,
                $design_data->production_name,$design_data->created_at);
                $email_body = str_replace($search_array,$replace_array,$email_body);

                $production_user_data = User::where('id',$design_data->production_id)->where('status',1)->where('is_deleted',0)->first();

                if(!isset($production_user_data['parent_user']) || empty($production_user_data['parent_user'])){
                    return response(array('httpStatus'=>400, 'dateTime'=>time(), 'status'=>'fail', 'message'=>'Production Head does not exists', 'errors' =>'Production Head does not exists' ));
                }

                $from_user_id = $design_data->production_id;
                $to_user_id = $production_user_data['parent_user'];
                $to_role_id = 7;
                $template_id = 2;
                $reference_id = $design_id;
                $to_email = $design_data->production_head_email;
                
                /**  Notification to Production head    **/
                //mail($to_email,$email_subject,$email_body);
                $insertArray = array('from_user_id'=>$from_user_id,'to_user_id'=>$to_user_id,'to_role_id'=>$to_role_id,'template_id'=>$template_id,'reference_id'=>$reference_id,
                'notification_text'=>$email_subject);
                Notification::insert($insertArray);
                
                /**  Notification to Administrator    **/
                $requsition_created_hours_diff = ((strtotime(date('Y/m/d H:i:s')) - strtotime($design_data->production_rev_req_date))/3600);
                if($requsition_created_hours_diff >= 48){
                    $admin_user_data = User::where('user_type',1)->where('status',1)->where('is_deleted',0)->first();
                    if(!empty($admin_user_data)){
                        //mail($admin_user_data['email'],$email_subject,$email_body);
                        $insertArray = array('from_user_id'=>$from_user_id,'to_user_id'=>$admin_user_data->id,'to_role_id'=>$admin_user_data->user_type,'template_id'=>$template_id,'reference_id'=>$reference_id,
                        'notification_text'=>$email_subject);
                        Notification::insert($insertArray);
                    }
                }
            }

            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Notification Sent','status' => 'success'),201);
        }catch (\Exception $e){		
            return response(array('httpStatus'=>500,'dateTime'=>time(),'status' => 'fail','message' =>$e->getMessage()),500);
        }  
        
    }
    
    public static function getHistoryType($historyTypeId){
        if($historyTypeId == 1){
             return 'design';
        }elseif($historyTypeId == 2){
             return 'production';
        }
    }
    
    public static function getDataRole($role_id){
        if($role_id == 2 || $role_id == 3 || $role_id == 7){
            $display_role_id = 2;   //production user records
        }else{
            $display_role_id = 5;   //designer user records
        }
        
        return $display_role_id;
    }
    
    public static function displayHistoryData($design_data,$version,$history_type){
        
        if(!is_array($design_data)) $design_data = json_decode(json_encode($design_data),true);
        
        if($version == '' || ($history_type == 1 && $design_data['version'] == $version) || ($history_type == 2 && $design_data['production_version'] == $version)){
            return false;
        }else{
            return true;
        }
    }
    
    public static function createLog($title,$key,$type,$params=array()){
        $user = \Auth::user();
        $insertArray = array('log_title'=>$title,'log_key'=>$key,'log_type'=>$type);
        if(isset($user->id) && !empty($user->id)){
            $insertArray['user_id'] = $user->id;
            $insertArray['role_id'] = $user->user_type;
        }
        App_logs::create($insertArray);
    }
    
    public static function createResizedImage($imagePath,$newPath,$newWidth,$newHeight =0,$outExt = 'DEFAULT')
    {
        if (!$newPath or !file_exists ($imagePath)) {
            return null;
        }

        $types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP, IMAGETYPE_WEBP];
        $type = exif_imagetype ($imagePath);

        if (!in_array ($type, $types)) {
            return null;
        }

        list ($width, $height) = getimagesize ($imagePath);

        $outBool = in_array ($outExt, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg ($imagePath);
                if (!$outBool) $outExt = 'jpg';
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng ($imagePath);
                if (!$outBool) $outExt = 'png';
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif ($imagePath);
                if (!$outBool) $outExt = 'gif';
                break;
            case IMAGETYPE_BMP:
                $image = imagecreatefrombmp ($imagePath);
                if (!$outBool) $outExt = 'bmp';
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp ($imagePath);
                if (!$outBool) $outExt = 'webp';
        }

        $newImage = imagecreatetruecolor ($newWidth, $newHeight);

        //TRANSPARENT BACKGROUND
        $color = imagecolorallocatealpha ($newImage, 0, 0, 0, 127); //fill transparent back
        imagefill ($newImage, 0, 0, $color);
        imagesavealpha ($newImage, true);

        //ROUTINE
        imagecopyresampled ($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        switch (true) {
            case in_array ($outExt, ['jpg', 'jpeg']): $success = imagejpeg ($newImage, $newPath);
                break;
            case $outExt === 'png': $success = imagepng ($newImage, $newPath);
                break;
            case $outExt === 'gif': $success = imagegif ($newImage, $newPath);
                break;
            case  $outExt === 'bmp': $success = imagebmp ($newImage, $newPath);
                break;
            case  $outExt === 'webp': $success = imagewebp ($newImage, $newPath);
        }

        if (!$success) {
            return null;
        }

        return $newPath;
    }
    
    public static function createBreadCrumb($array){
        $str = '<ol class="breadcrumb">';        
        for($i=0;$i<count($array);$i++){
            if(isset($array[$i]['link'])){
                $str.='<li class="breadcrumb-item"><a href="'.url($array[$i]['link']).'">'.$array[$i]['name'].'  </a></li>';
            }else{
                $str.='<li  class="breadcrumb-item active" aria-current="page">'.$array[$i]['name'].' </li>';
            }
        }
        
        $str.='</ol>';
        return $str;
    }
    
    public static function headerMessages(){
        $str = '<div style="clear:both;height:15px;"></div>';
    
        if (session('error_message')){
            $str.= '<br/>
            <div class="alert alert-danger">
                '.session('error_message').'
            </div>';
        }

        if(session('success_message')){
            $str.='<br/>
            <div class="alert alert-success">
                '.session('success_message').'
            </div>';
        }

       return $str;
    }
    
    public static function pageSubHeader($breadCrumbArray,$pageHeading){
        $str = '<nav class="page_bbreadcrumb" aria-label="breadcrumb">'.CommonHelper::createBreadCrumb($breadCrumbArray).'</nav>';
        $str.='<h2 class="page_heading page-heading">'.$pageHeading.' </h2>';
        $str.=CommonHelper::headerMessages();
        return $str;
    }
    
    public static function displayPageErrorMsg($error_message){
        $str = '';
        if(isset($error_message) && !empty($error_message)){
            $str.='<div class="alert alert-danger">'.$error_message.'</div>';
        }
        return $str;
    }
    
    public static function getStoresAssetsOrdersList($whereArray = array(),$orderArray = array(),$otherParamsArray = array()){
        try{ 
            $data = request()->all();
            $order_by = (isset($orderArray['order_by']))?$orderArray['order_by']:'so.id DESC';
            $paginate = (isset($orderArray['paginate']))?$orderArray['paginate']:30;
            
            $orders_list = \DB::table('store_asset_order as so')
            ->join('users as u1','u1.id', '=', 'so.user_id')
            ->join('store as s','s.id', '=', 'so.store_id')        
            ->leftJoin('users as u2','u2.id', '=', 'so.approver_id');
            
            $orders_list = $orders_list->where($whereArray);
            if(isset($otherParamsArray['where_raw']) && !empty($otherParamsArray['where_raw'])){
                $whereRawArray = $otherParamsArray['where_raw'];
                for($i=0;$i<count($whereRawArray);$i++){
                    $orders_list = $orders_list->whereRaw($whereRawArray[$i]);
                }
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'so.id','total_amount'=>'so.total_amount','total_bill_amount'=>'so.total_bill_amount','store'=>'s.store_name','order_status'=>'so.order_status',
                'created_by'=>'order_user_name','reviewer'=>'approver_name','created_on'=>'so.created_at','status'=>'so.status','order_type'=>'so.order_type');                
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'so.id';
                $orders_list = $orders_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }else{
                $orders_list = $orders_list->orderByRaw($order_by);
            }
            
            $orders_list = $orders_list->select('so.*','u1.name as order_user_name','s.store_name','u2.name as approver_name')->paginate($paginate);        
           
            return array('orders_list'=>$orders_list,'error_message'=>'');
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return array('orders_list'=>array(),'error_message'=>$e->getMessage());
        }
    }
    
    public static function getStoresDemandsList($whereArray = array(),$orderArray = array(),$otherParamsArray = array()){
        try{ 
            $data = request()->all();
            $order_by = (isset($orderArray['order_by']))?$orderArray['order_by']:'spd.id DESC';
            $paginate = (isset($orderArray['paginate']))?$orderArray['paginate']:30;
            
            $demands_list = \DB::table('store_products_demand as spd')
            ->join('users as u1','u1.id', '=', 'spd.user_id')
            ->join('store as s','s.id', '=', 'spd.store_id')        
            ->leftJoin('users as u2','u2.id', '=', 'spd.approver_id')
            ->leftJoin('store_products_demand_detail as spdd',function($join){
            $join->on('spd.id','=','spdd.demand_id')
                ->where('spdd.is_deleted','=','0')     
                ->where('spdd.status','=','1');
            });        
            
            $demands_list = $demands_list->where($whereArray);
            if(isset($otherParamsArray['where_raw']) && !empty($otherParamsArray['where_raw'])){
                $whereRawArray = $otherParamsArray['where_raw'];
                for($i=0;$i<count($whereRawArray);$i++){
                    $demands_list = $demands_list->whereRaw($whereRawArray[$i]);
                }
            }
            
            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'spd.id','store'=>'s.store_name','demand_status'=>'spd.demand_status','created_by'=>'demand_user_name','reviewer'=>'approver_name',
                'created_on'=>'spd.created_at','status'=>'spd.status','products_count'=>'products_count');                
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'spd.id';
                $demands_list = $demands_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }else{
                $demands_list = $demands_list->orderByRaw($order_by);
            }
            
            $demands_list = $demands_list->groupBy('spd.id');
            $demands_list = $demands_list->select('spd.*','u1.name as demand_user_name','s.store_name','u2.name as approver_name',\DB::raw('sum(spdd.product_quantity) as products_count'))
            ->paginate($paginate);        
           
            return array('demands_list'=>$demands_list,'error_message'=>'');
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return array('demands_list'=>array(),'error_message'=>$e->getMessage());
        }
    }
    
    public static function getAssetsOrderItemsList(){
        try{
            $data = request()->all();
            $order_by = (isset($orderArray['order_by']))?$orderArray['order_by']:'spd.id DESC';
            $paginate = (isset($orderArray['paginate']))?$orderArray['paginate']:30;
            
            $asset_orders_items_list = \DB::table('store_asset_order as so')
            ->join('store as s','s.id', '=', 'so.store_id')        
            ->join('store_asset_order_detail as sod','so.id', '=', 'sod.order_id')                
            ->join('store_assets as si','si.id', '=', 'sod.item_id')      
            ->join('design_lookup_items_master as dlim_1','dlim_1.id', '=', 'si.category_id')              
            ->join('design_lookup_items_master as dlim_2','dlim_2.id', '=', 'si.subcategory_id')              
            ->join('users as u1','u1.id', '=', 'so.user_id')        
            ->where('so.is_deleted',0);

            if(isset($data['store_id']) && is_numeric($data['store_id'])){
                $asset_orders_items_list = $asset_orders_items_list->where('so.store_id',$data['store_id']);
            }

            if(isset($data['asset_type']) && !empty($data['asset_type'])){
                $asset_orders_items_list = $asset_orders_items_list->where('si.item_type',$data['asset_type']);
            }

            if(isset($data['cat_id']) && !empty($data['cat_id'])){
                $asset_orders_items_list = $asset_orders_items_list->where('si.category_id',$data['cat_id']);
            }

            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $start_date = date('Y/m/d',strtotime(trim($data['startDate']))).' 00:00';
                $end_date = date('Y/m/d',strtotime(trim($data['endDate']))).' 23:59';
                $asset_orders_items_list = $asset_orders_items_list->whereRaw("so.order_approve_date BETWEEN '$start_date' AND '$end_date'");
            }

            if(isset($data['order_status']) && !empty($data['order_status'])){
                $asset_orders_items_list = $asset_orders_items_list->where('so.order_status',$data['order_status']);
            }

            if(isset($data['sort_by']) && !empty($data['sort_by'])){
                $sort_array = array('id'=>'so.id','item'=>'si.item_name','price'=>'sod.item_price','quantity'=>'sod.item_quantity','total_amount'=>'so.total_amount',
                'store'=>'s.store_name','order_status'=>'so.order_status','created_by'=>'order_user_name','created_on'=>'so.created_at','type'=>'si.item_type',
                'category'=>'category_name','subcategory'=>'subcategory_name','approved_date'=>'subcategory_name');
                $sort_by = (isset($sort_array[$data['sort_by']]))?$sort_array[$data['sort_by']]:'so.id';
                $asset_orders_items_list = $asset_orders_items_list->orderBy($sort_by,CommonHelper::getSortingOrder());
            }else{
                $asset_orders_items_list = $asset_orders_items_list->orderByRaw('so.id DESC, sod.id ASC');
            }

            $asset_orders_items_list = $asset_orders_items_list->select('so.order_status','so.total_amount','sod.*','si.item_name','si.item_desc','si.item_type','s.store_name',
            'u1.name as order_user_name','dlim_1.name as category_name','dlim_2.name as subcategory_name','so.order_approve_date')->paginate($paginate);
            
            return array('asset_orders_items_list'=>$asset_orders_items_list,'error_message'=>'');
            
        }catch (\Exception $e){
            CommonHelper::saveException($e,'STORE',__FUNCTION__,__FILE__);
            return array('asset_orders_items_list'=>array(),'error_message'=>$e->getMessage());
        }
    }
    
    public static function saveException($e,$type,$function_name,$file_name){
        CommonHelper::createLog('Exception: '.$e->getMessage().'. Method: '.$function_name. '. File: '.basename($file_name).'. Line No: '.$e->getLine(),'EXCEPTION',$type);
    }
    
    public static function uploadImage($request,$file_name,$dest_folder,$create_thumb=true,$thumb_folder='thumbs'){
        
        CommonHelper::createDirectory($dest_folder);
        if($create_thumb){
            CommonHelper::createDirectory($dest_folder.'/'.$thumb_folder);
        }
        
        $image = (is_object($file_name))?$file_name:$request->file($file_name);
        $image_name_text = substr($image->getClientOriginalName(),0,strpos($image->getClientOriginalName(),'.'));
        $image_name_text = substr(str_replace(' ','_',strtolower($image_name_text)),0,150);
        $image_ext = $image->getClientOriginalExtension();
        
        for($i=0;$i<1000;$i++){
            $image_name = ($i == 0)?$image_name_text.'.'.$image_ext:$image_name_text.'_'.$i.'.'.$image_ext;
            if(!file_exists(public_path($dest_folder.'/'.$image_name))){
                break;
            }
        }
        
        if(!isset($image_name)){
            $image_name = $image_name_text.'_'.rand(1000,100000).'.'.$image_ext;
        }
        
        $image->move(public_path($dest_folder), $image_name);

        if($create_thumb){
            $src = public_path($dest_folder).'/'.$image_name;
            $dest = public_path($dest_folder).'/'.$thumb_folder.'/'.$image_name;
            CommonHelper::createResizedImage($src,$dest,350,450);
        }
        
        return $image_name;
    }
    
    public static function getposProductStatusList(){
        $status_array = array('0'=>'Warehouse In Pending','1'=>'Available in Warehouse','2'=>'Reserved for Store','3'=>'Transit to Store','4'=>'Ready for Sale by Store','5'=>'Sold from Store','6'=>'Transit to Warehouse from Store','7'=>'Returned to Vendor');
        return $status_array;
    }
    
    public static function getposProductStatusName($status_id){
        $status_array = CommonHelper::getposProductStatusList();
        return isset($status_array[$status_id])?$status_array[$status_id]:'Invalid Status code';
    }
    
    public static function getProductInventoryQCStatusList(){
        $status_arr = array('Pending','Accepted','Defective');
        return $status_arr;
    }
    
    public static function getProductInventoryQCStatusName($status_id){
        $status_array = CommonHelper::getProductInventoryQCStatusList();
        return isset($status_array[$status_id])?$status_array[$status_id]:'Invalid Status code';
    }
    
    public static function createDirectory($path){
        if(!file_exists(public_path($path))){
            mkdir(public_path($path));
            chmod(public_path($path), 0777);
        }
    }
    
    public static function getUserStoreData($user_id){
        $store_user = \DB::table('store as s')->join('store_users as su','s.id', '=', 'su.store_id')->where('su.user_id',$user_id)->select('s.*')->first();
        return $store_user;
    }
    
    public static function getGSTPercent($amount){
        $gst_percent = ($amount<=1000)?5:12;
        return $gst_percent;
    }
    
    public static function getGSTData($hsn_code,$amount){
        $gst_data = \DB::table('gst_rates')->where('hsn_code',$hsn_code)->where('min_amount','<',$amount)->where('max_amount','>=',$amount)->where('is_deleted',0)->select('*')->first();
        return $gst_data;
    }
    
    public static function numberToWords($number){
        $no = floor($number);
        $point = round($number - $no, 2) * 100;
        $hundred = null;
        $digits_1 = strlen($no);
        $i = 0;
        $str = array();
        $words = array('0' => '', '1' => 'One', '2' => 'Two',
         '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
         '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
         '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
         '13' => 'Thirteen', '14' => 'Fourteen',
         '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
         '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty',
         '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
         '60' => 'Sixty', '70' => 'Seventy',
         '80' => 'Eighty', '90' => 'Ninety');
        $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
        while ($i < $digits_1) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 21) ? $words[$number] ." " . $digits[$counter] . $plural . " " . $hundred:
                $words[floor($number / 10) * 10]. " " . $words[$number % 10] . " ". $digits[$counter] . $plural . " " . $hundred;
            } else $str[] = null;
        }
        $str = array_reverse($str);
        $result = implode('', $str);
        $points = ($point) ?"." . $words[$point / 10] . " " . $words[$point = $point % 10] : '';
        
        return $result  . $points;
    }
    
    public static function sendEmail($email_subject,$email_body,$search_data,$replace_data,$to_email,$to_name){
        $email_subject = str_replace($search_data, $replace_data,$email_subject);
        $email_body = str_replace($search_data, $replace_data,$email_body);
        
        $headers = array();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        $headers[] = 'To: '.$to_name.' <'.$to_email.'>';
        //$headers[] = 'From: Kiasa <birthday@example.com>';
        
        //mail($to_email, $email_subject, $email_body,implode("\r\n", $headers));
        CommonHelper::smtpEmail($email_subject, $email_body,$to_email,$to_name);
    }
    
    public static function getQuotationEmailData($quotation){
        $email_subject = 'Kiasa Quotation Submision #'.$quotation->id;
        $email_str = file_get_contents(public_path('documents/email_template_1.html'));
        
        return array('email_subject'=>$email_subject,'email_body'=>$email_str);
    }
    
    public static function getCompanyData(){
        $company_data = array();
        
        $company_info = \DB::table('app_settings')
        ->where('setting_key','LIKE','company_%')
        ->select('*')->get()->toArray();

        for($i=0;$i<count($company_info);$i++){
            $company_data[$company_info[$i]->setting_key] = $company_info[$i]->setting_value;
        }
        
        return $company_data;
    }
    
    public static function smtpEmail($email_subject,$email_body,$to_email,$to_name){
        
        $mail = new PHPMailer(true);

        //try {
            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'northcorpdeveloper@gmail.com';         // SMTP username
            $mail->Password   = 'Ncdev!23';                               // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
            $mail->SMTPOptions = array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
            );

            //Recipients
            $mail->setFrom('northcorpdeveloper@gmail.com', 'Kiasa');
            $mail->addAddress($to_email, $to_name);     // Add a recipient
            
            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $email_subject;
            $mail->Body    = $email_body;
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            //echo 'Message has been sent';
        /*} catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }*/
    }
    
    public static function getGSTType($gst_no){
        $kiasa_state_code = '09';
        $vendor_state_code = substr($gst_no,0,2);
        return ($kiasa_state_code == $vendor_state_code)?1:2;
    }
    
    public static function getGSTTypePercent($gst_type){
        $percent_array = array();
        
        if($gst_type == 1){
            $percent_array[] = array('gst_name'=>'CGST','gst_percent'=>'50');
            $percent_array[] = array('gst_name'=>'SGST','gst_percent'=>'50');
        }elseif($gst_type == 2){
            $percent_array[] = array('gst_name'=>'IGST','gst_percent'=>'100');
        }
        
        return $percent_array;
    }
    
    public static function getPosDiscountPercent(){
        return 70;
    }
    
    public static function moneyFormat($num) {
        return $num;
    }
    
    public static function getSearchStartEndDate($data,$default_date = true,$default_interval = ''){
        $start_date = $end_date = '';
        if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
            $data_arr = explode('-',str_replace('/','-',$data['startDate']));
            $data['startDate'] = $data_arr[2].'-'.$data_arr[1].'-'.$data_arr[0];
            $data_arr = explode('-',str_replace('/','-',$data['endDate']));
            $data['endDate'] = $data_arr[2].'-'.$data_arr[1].'-'.$data_arr[0];
            $start_date = date('Y/m/d',strtotime(trim($data['startDate']))).' 00:00';
            $end_date = date('Y/m/d',strtotime(trim($data['endDate']))).' 23:59';
        }else{
            if($default_date){
                $interval = (!empty($default_interval))?$default_interval:CommonHelper::getDefaultDaysInterval();
                $start_date = date('Y/m/d',strtotime($interval)).' 00:00';
                $end_date = date('Y/m/d').' 23:59';
            }
        }
        
        return array('start_date'=>$start_date,'end_date'=>$end_date);
    }
    
    public static function getDefaultDaysInterval(){
        return '-1 month';
    }
    
    public static function dateDiff($date1,$date2){
        $dt1 = new DateTime($date1);
        $dt2 = new DateTime($date2);

        return $diff = $dt2->diff($dt1)->format("%a");
    }
    
    public static function getdiscount($barcode,$prod_data = array()){
        if($barcode ==''){
            return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => 'Please check product barcode');
        }
        
        if(empty($prod_data)){
            $productData = Pos_product_master_inventory::where('peice_barcode',$barcode)->where('product_status',4)->where('fake_inventory',0)->where('is_deleted',0)->select('product_master_id','store_id','store_base_price','sale_price','arnon_inventory')->get()->first();

            if($productData){
                $discount_inv_type = ($productData->arnon_inventory == 0)?1:2;
                $sale_price = $productData->sale_price;
                $productData = $productData->toArray();
                $productMasterData = Pos_product_master::where('id',$productData['product_master_id'])->select('product_sku as sku','category_id','season_id as season')->get()->first();
                if($productMasterData){
                    $productMasterData = $productMasterData->toArray();
                    foreach($productMasterData as $productMasterKey=>$productMasterValue){
                        $productData[$productMasterKey]=$productMasterValue;
                    }
                }                
            }else{
                return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Validation error', 'errors' => 'Please check product barcode');
            }
        }else{
            $productData = $prod_data;
            $discount_inv_type = ($prod_data['arnon_inventory'] == 0)?1:2;
            $sale_price = $prod_data['sale_price'];
        }
        
        $matchAttributeWeightArr = array("store_id" => 100,"sku" => 1000,"category_id" => 100,"season" => 100); 
        $currentDate = Carbon::now()->format('Y-m-d');
        $store_id =$productData['store_id'];
        $sku =$productData['sku'];
        $category_id =$productData['category_id'];
        $season =$productData['season'];
        
        $discount_list = Discount::where('is_deleted',0)
        ->where('inv_type',$discount_inv_type)        
        ->where('from_date','<=',$currentDate)
        ->where('to_date','>=',$currentDate)
        ->whereRaw("( (from_price IS NULL AND to_price IS NULL) OR (from_price = 0 AND to_price = 0) OR (from_price <= $sale_price AND to_price >= $sale_price) )")        
        ->where(function ($discount_list) use ($store_id){ $discount_list->where('store_id', '=',$store_id)
            ->orWhereNull('store_id');
        })
        ->where(function ($discount_list) use ($sku){ $discount_list->where('sku', '=',$sku)
            ->orWhereNull('sku');
        })                                
        ->where(function ($discount_list) use ($category_id){ $discount_list->where('category_id', '=',$category_id)
            ->orWhereNull('category_id');
        })                               
        ->where(function ($discount_list) use ($season){ $discount_list->where('season', '=',$season)
            ->orWhereNull('season');
        });
        
        $discount_list= $discount_list->select('id','category_id','store_id','sku','discount_type','flat_price','discount_percent','gst_including','season')->get()->toArray();  
        $discountRowWeightArr= $discountRowsData =array();     
        
        if(empty($discount_list)){
           return array('httpStatus'=>200, 'dateTime'=>time(), 'data'=>array(), 'status'=>'success','message' => 'Discounts match not exist','status' => 'success');
        }
        
        foreach($discount_list as $discount_list_arr){
            $matchWeight =0;
            $commonResult=array_intersect_assoc($productData,$discount_list_arr);
            foreach($commonResult as $commonResultKey=>$value){
                 $matchWeight = $matchWeight+ $matchAttributeWeightArr[$commonResultKey];
            }
            $discountRowWeightArr[$discount_list_arr['id']] =  $matchWeight;                        
            $discountRowsData[$discount_list_arr['id']] = $discount_list_arr;                 
        }
        
        $maxIndex='';
        $maxValue=0;
        
        foreach($discountRowWeightArr as $key=>$value){
            if($value>=$maxValue){
                $maxValue = $value;                    
                $maxIndex = $key;
            }
        } 
        
        if( $discountRowsData[$maxIndex]['discount_type'] ==1){ unset($discountRowsData[$maxIndex]['flat_price']); }else{ unset($discountRowsData[$maxIndex]['discount_percent']); }
        
        return array('httpStatus'=>200, 'dateTime'=>time(), 'data'=>$discountRowsData[$maxIndex], 'status'=>'success','message' => 'Discounts matched successfully','status' => 'success');

    }
    
    public static function getAuditProductScanStatusList(){
        $status_array = array('0'=>'Not Scanned','1'=>'Scanned','2'=>'Sold during Scan','3'=>'Other Store Product','4'=>'Sold in System, present in Store');
        return $status_array;
    }
    
    public static function getAuditProductScanStatus($status_id){
        $status_array = CommonHelper::getAuditProductScanStatusList();
        return isset($status_array[$status_id])?$status_array[$status_id]:'Invalid Status code';
    }
    
    public static function getPosProductData($barcode,$id_str,$user_id,$foc = 0){
        //$user = Auth::user();
        $user = User::where('id',$user_id)->first();
        
        $barcode = trim($barcode);
        $ids = trim($id_str);
        
        $store_data = CommonHelper::getUserStoreData($user_id);
        
        $product_data = \DB::table('pos_product_master_inventory as ppmi')
        ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')                 
        ->leftJoin('store_products_demand as spd','ppmi.demand_id', '=', 'spd.id')        
        ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
        ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')   
        ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
        ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
        ->where('ppmi.peice_barcode',$barcode)
        ->where('ppmi.is_deleted',0);
        
        if($user->user_type != 18){
            $product_data = $product_data->where('ppmi.fake_inventory',0);          
        }
        
        $product_data = $product_data->where('ppm.is_deleted',0)
        ->where('ppm.status',1)
        ->where('ppmi.status',1);
        
        if(!empty($store_data)){
            $product_data = $product_data->where('ppmi.store_id',$store_data->id)
            ->where('ppmi.product_status',4);
        }else{
            $product_data = $product_data->where('ppmi.product_status',1);
        }
        
        if(!empty($ids)){
            $product_data = $product_data->whereRaw("ppmi.id NOT IN($ids)");
        }

        $product_data = $product_data->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','ppm.category_id','ppm.subcategory_id','ppm.hsn_code',
        'ppm.gst_inclusive','dlim_1.name as category_name','dlim_2.name as subcategory_name','dlim_3.name as color_name','psc.size as size_name','ppm.size_id','ppm.color_id',
        'spd.discount_applicable as demand_discount_applicable','spd.discount_percent as demand_discount','spd.gst_inclusive as demand_gst_inclusive','ppm.product_description')->first();
        
        /* Check Return product start. Return products not allowed in foc order   */
        if(empty($product_data) && $foc == 0){

            $product_data = \DB::table('pos_product_master_inventory as ppmi')
            ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')             
            ->join('store as s','ppmi.store_id', '=', 's.id')         
            ->join('pos_customer_orders_detail as pcod',function($join){$join->on('ppmi.id','=','pcod.inventory_id')->on('ppmi.customer_order_id','=','pcod.order_id');})      
            ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
            ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')   
            ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
            ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
            ->where('ppmi.peice_barcode',$barcode)
            //->where('ppmi.store_id',$store_data->id)
            ->where('ppmi.product_status',5)
            ->where('ppmi.is_deleted',0);
            
            if($user->user_type != 18){
                $product_data = $product_data->where('ppmi.fake_inventory',0);
            }
            
            $product_data = $product_data->where('ppm.is_deleted',0)
            ->where('ppm.status',1)->where('ppmi.status',1);

            if(!empty($ids)){
                $product_data = $product_data->whereRaw("ppmi.id NOT IN($ids)");
            }

            $product_data = $product_data->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','ppm.category_id','ppm.subcategory_id','ppm.hsn_code','ppm.gst_inclusive',
            'dlim_1.name as category_name','dlim_2.name as subcategory_name','dlim_3.name as color_name','psc.size as size_name','s.store_name','pcod.base_price as base_price_return',
            'pcod.sale_price as sale_price_return','pcod.net_price as net_price_return','pcod.discount_percent as discount_percent_return','pcod.discount_amount as discount_amount_return',
            'pcod.gst_percent as gst_percent_return','pcod.gst_amount as gst_amount_return','pcod.gst_inclusive as gst_inclusive_return')->first();
            
        }
        /* Check Return product end   */

        if(!empty($product_data)){
            $product_data->return_product = ($product_data->product_status == 5)?1:0;

            // Add discount if it is not return product
            if($product_data->return_product == 0){
                
                if($foc == 0){
                    $discount_data = CommonHelper::getdiscount($barcode);
                }else{
                    $discount_data = array('status'=>'success','data' => ['discount_type'=>1,'discount_percent'=>100,'gst_including'=>1,'id'=>0]);
                }
                
                if($discount_data['status'] == 'success' && isset($discount_data['data']['discount_type']) && $discount_data['data']['discount_type'] == 1){
                    $discount_percent = $discount_data['data']['discount_percent'];
                    $gst_inclusive = $discount_data['data']['gst_including'];
                    $discount_id = $discount_data['data']['id'];
                }elseif($discount_data['status'] == 'success' && isset($discount_data['data']['discount_type']) && $discount_data['data']['discount_type'] == 2){
                    $flat_price = $discount_data['data']['flat_price'];
                    $discount_percent = round(100-($flat_price/$product_data->sale_price)*100,2);
                    $gst_inclusive = $discount_data['data']['gst_including'];
                    $discount_id = $discount_data['data']['id'];
                }else{
                    $discount_percent = CommonHelper::getPosDiscountPercent();
                    $discount_id = 0;
                    if(isset($store_data->gst_type) && !empty($store_data->gst_type)){
                        $store_gst_inclusive = ($store_data->gst_type == 'inclusive')?1:0;
                    }else{
                        $store_gst_inclusive = null;
                    }
                    $gst_inclusive = ($store_gst_inclusive !== null)?$store_gst_inclusive:$product_data->gst_inclusive;
                }
                
                // Override gst inclusive is defined at store level. default, inclusive or not_inclusive
                if(isset($store_data->gst_type) && !empty($store_data->gst_type)){
                    if(strtolower($store_data->gst_type) == 'inclusive')  $gst_inclusive = 1;
                    if(strtolower($store_data->gst_type) == 'not_inclusive')  $gst_inclusive = 0;
                }

                $product_data->discount_percent = $discount_percent;
                $product_data->discount_id = $discount_id;
                $product_data->gst_inclusive = $gst_inclusive;

                $discount_price = ($discount_percent > 0)?($product_data->sale_price*($discount_percent/100)):0;
                $discounted_price = round($product_data->sale_price-$discount_price,2);
                
                $product_data->discount_amount = round($discount_price,2);
                $product_data->discounted_price = $discounted_price;

                if((isset($store_data->gst_applicable) && $store_data->gst_applicable == 1) || $foc == 1){
                    $gst_data = CommonHelper::getGSTData($product_data->hsn_code,$discounted_price);
                    $product_data->gst_percent = (!empty($gst_data))?$gst_data->rate_percent:0;
                }else{
                    $product_data->gst_percent = 0;
                }
                
                $gst_percent = $product_data->gst_percent;
                
                if($discount_percent == 0 || ($discount_percent > 0 && $gst_inclusive == 1)){
                    $discounted_price_orig = $discounted_price;
                    $gst_percent_1 = 100+$gst_percent;
                    $gst_percent_1 = $gst_percent_1/100;
                    $discounted_price = round($discounted_price/$gst_percent_1,6);

                    $gst_amount = $discounted_price_orig-$discounted_price;
                    $net_price = $discounted_price_orig; 
                }else{
                    $gst_amount = (!empty($gst_percent))?round($discounted_price*($gst_percent/100),6):0;
                    $net_price = $discounted_price+$gst_amount;
                }
                
                $product_data->gst_amount = round($gst_amount,6);
                $product_data->net_price = round($net_price,6);
                    
            }else{
                $product_data->discount_percent = 0;
                $product_data->discount_id = 0;
                $product_data->gst_inclusive = 0;
                $product_data->gst_percent = 0;
                if($store_data->id != $product_data->store_id){
                    $product_data->other_store_prod = 1;
                    $product_data->other_store_name = $product_data->store_name;
                }
            }
        }
        
        if(isset($product_data->net_price_return)){
            $product_data->net_price_return = round($product_data->net_price_return,2);
            $product_data->discount_amount_return = round($product_data->discount_amount_return,2);
            $product_data->gst_amount_return = round($product_data->gst_amount_return,2);
        }
        
        return $product_data;
    }
    
    public static function getPosCustomerData($data){
        $validateionRules = array('phone_no'=>'Required');
        $attributes = array();
        $validator = Validator::make($data,$validateionRules,array(),$attributes);
        if ($validator->fails()){ 
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Phone no is Required Field', 'errors' => $validator->errors()));
        } 

        $pos_customer = Pos_customer::where('phone',$data['phone_no'])->where('is_deleted',0)->first();
        if(empty($pos_customer)){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Customer with phone no does not exists', 'errors' => 'Customer with phone no does not exists'));
        }

        if(!empty($pos_customer->dob)) $pos_customer->dob = date('d-m-Y',strtotime($pos_customer->dob));
        if(!empty($pos_customer->wedding_date)) $pos_customer->wedding_date = date('d-m-Y',strtotime($pos_customer->wedding_date));

        return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'success','message' => 'Pos customer data','customer_data'=>$pos_customer),200);
    }
    
    public static function addPosCustomer($data){
        // $validateionRules = array('customer_phone_new'=>'required','customer_email'=>'nullable|email','customer_salutation'=>['required',Rule::in(['Mr', 'Mrs','Ms','Dr','Other'])],'customer_name'=>'required');
        $validateionRules = array('customer_phone_new'=>'required|digits:10|numeric','customer_email'=>'nullable|email','customer_name'=>'required');
        $attributes = array('customer_phone_new'=>'Customer Phone','customer_name'=>'Name');
        // $attributes = array('customer_phone_new'=>'Customer Phone','customer_salutation'=>'Salutation','customer_name'=>'Name');

        $validator = Validator::make($data,$validateionRules,array(),$attributes);
        if ($validator->fails()){ 
            $errors_str = CommonHelper::parseValidationErrors($validator->errors());
            return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $validator->errors());
        } 
        
        $pos_customer = Pos_customer::where('phone',$data['customer_phone_new'])->where('is_deleted',0)->first();
        if(!empty($pos_customer)){
            return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Customer with phone no already exists', 'errors' => 'Customer with phone already exists');
        }
        if(!empty($data['customer_email'])){
            $pos_customer_info = Pos_customer::where('email',$data['customer_email'])->where('is_deleted',0)->first();
            if(!empty($pos_customer_info)){
                return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Customer with email already exists', 'errors' => 'Customer with email already exists');
            }
        }

        $insertArray = array('salutation'=>$data['customer_salutation'],'customer_name'=>$data['customer_name'],'email'=>$data['customer_email'],'phone'=>$data['customer_phone_new'],'postal_code'=>$data['customer_postal_code']);
        if(!empty($data['customer_wedding_date'])){ 
            $date_wedding_arr = explode('-',$data['customer_wedding_date']);
            $insertArray['wedding_date'] = $date_wedding_arr[2].'-'.$date_wedding_arr[1].'-'.$date_wedding_arr[0];
        }
        if(!empty($data['customer_dob'])){ 
            $date_dob_arr = explode('-',$data['customer_dob']);
            $insertArray['dob'] = $date_dob_arr[2].'-'.$date_dob_arr[1].'-'.$date_dob_arr[0];
        }
        if(isset($data['fake_inventory']) && $data['fake_inventory'] == 1){ 
            $insertArray['fake_inventory'] = 1;
        }
        if(isset($data['password']) && !empty($data['password']) ){ 
            $insertArray['password'] = $data['password'];
        }
        
        $pos_customer = Pos_customer::create($insertArray);
        unset($pos_customer['password']);
        
        return array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Pos customer Created','customer_data'=>$pos_customer);
    }
    
    public static function updatePosCustomer($data){
        
        $updateArray = array();
        if(isset($data['customer_salutation']) && !empty($data['customer_salutation'])){
            $updateArray['salutation'] = $data['customer_salutation'];
        }
        
        if(isset($data['customer_name']) && !empty($data['customer_name'])){
            $updateArray['customer_name'] = $data['customer_name'];
        }
        
        if(array_key_exists('customer_postal_code', $data) ){
            $updateArray['postal_code'] = $data['customer_postal_code'];
        }
        
        if(array_key_exists('customer_email', $data) ){
            $updateArray['email'] = $data['customer_email'];
        }
        
        if(array_key_exists('customer_wedding_date', $data) ){
            if(!empty($data['customer_wedding_date'])){ 
                $updateArray['wedding_date'] = $data['customer_wedding_date'];
            }else{
                $updateArray['wedding_date'] = null;
            }
        }
        
        if(array_key_exists('customer_dob', $data) ){
            if(!empty($data['customer_dob'])){ 
                $updateArray['dob'] = $data['customer_dob'];
            }else{
                $updateArray['dob'] = null;
            }
        }

        if(!empty($updateArray)){
            $pos_customer = Pos_customer::where('id',$data['customer_id'])->update($updateArray);
        }
    }
    
    public static function createPosOrder($data){
        
        $total_price = $total_payment_received = 0;
        $id_array = explode(',',$data['ids']);
        $staff_id_array = (isset($data['staff_ids']))?explode(',',$data['staff_ids']):array();
        $gst_percent_array = (isset($data['gst_percent']))?explode(',',$data['gst_percent']):array();
        $gst_inclusive_array = (isset($data['gst_inclusive']))?explode(',',$data['gst_inclusive']):array();
        $discount_percent_array = (isset($data['discounts']))?explode(',',$data['discounts']):array();
        $fake_inventory = (isset($data['fake_inventory']) && $data['fake_inventory'] == 1)?1:0;
        $order_id = (isset($data['order_id']) && !empty($data['order_id']))?trim($data['order_id']):'';
        $foc = (isset($data['foc']) && $data['foc'] == 1)?1:0;
        $bags_count = (isset($data['bags_count']) && !empty($data['bags_count']))?trim($data['bags_count']):0;
        $address_id = (isset($data['address_id']) && !empty($data['address_id']))?trim($data['address_id']):null;
        $store_id = (isset($data['store_id']) && !empty($data['store_id']))?trim($data['store_id']):0;
        
        $store_staff = $other_store_products = $other_store_prods = $coupon_data = $gst_percent_arr = $gst_inclusive_arr = $discount_percent_arr = array();
        $payment_method = $reference_no = null; 
        $net_price_str = '';
        
        for($i=0;$i<count($id_array);$i++){
            $store_staff[$id_array[$i]] = (isset($staff_id_array[$i]))?$staff_id_array[$i]:null;
            $gst_percent_arr[$id_array[$i]] = (isset($gst_percent_array[$i]))?$gst_percent_array[$i]:null;
            $gst_inclusive_arr[$id_array[$i]] = (isset($gst_inclusive_array[$i]))?$gst_inclusive_array[$i]:null;
            $discount_percent_arr[$id_array[$i]] = (isset($discount_percent_array[$i]))?$discount_percent_array[$i]:null;
        }
            
        //$store_user = CommonHelper::getUserStoreData($data['user_id']); 
        $store_user = Store::where('id',$store_id)->first();
        
        if(empty($store_user) && $foc == 0){
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Users other than store can only create FOC order',),200);
        }
        
        if(count($id_array) != count(array_values(array_unique($id_array)))){
            return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Duplicate Products in Order'),200);
        }
        
        $products_list = \DB::table('pos_product_master_inventory as ppmi')
        ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id') 
        ->leftJoin('store_products_demand as spd','ppmi.demand_id', '=', 'spd.id')         
        ->leftJoin('pos_customer_orders_detail as pcod',function($join){$join->on('ppmi.id','=','pcod.inventory_id')->on('ppmi.customer_order_id','=','pcod.order_id')->where('pcod.is_deleted','=','0')->where('pcod.order_status',1);})             
        ->wherein('ppmi.id',$id_array)
        ->select('ppmi.*','ppm.product_name','ppm.hsn_code','pcod.base_price as base_price_return','ppm.gst_inclusive',
        'pcod.sale_price as sale_price_return','pcod.net_price as net_price_return','pcod.discount_percent as discount_percent_return',
        'pcod.discount_amount as discount_amount_return','pcod.gst_percent as gst_percent_return','pcod.gst_amount as gst_amount_return',
        'pcod.gst_inclusive as gst_inclusive_return','spd.gst_inclusive as demand_gst_inclusive')
        ->get()->toArray(); 

        $products_list = json_decode(json_encode($products_list),true);
        
        if(!empty($order_id)){
            $pos_customer_order =  Pos_customer_orders::where('id',$order_id)->first();
            
            if($pos_customer_order->order_status != 2){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Order Status is not Pending',),200);
            }
            
            if($pos_customer_order->store_id != $store_user->id){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Order does not exists in '.$store_user->store_name,),200);
            }
        }
        
        /** Coupon code start **/
        $coupon_item_id = isset($data['coupon_item_id'])?trim($data['coupon_item_id']):'';
        
        if(!empty($coupon_item_id)){
            $coupon_data = \DB::table('coupon as c')
            ->join('coupon_items as ci','c.id', '=', 'ci.coupon_id')        
            ->where('ci.id',$coupon_item_id)        
            ->where('c.is_deleted',0)        
            ->where('ci.is_deleted',0)
            ->select('c.*','ci.coupon_used','ci.coupon_no','ci.id as coupon_item_id')        
            ->first();

            if(empty($coupon_data)){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon does not exists',),200);
            }

            if($coupon_data->status != 1){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon is not enabled',),200);
            }

            if($coupon_data->coupon_used == 1){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon is already used',),200);
            }

            if(!(strtotime(date('Y/m/d')) >= strtotime($coupon_data->valid_from) && strtotime(date('Y/m/d')) <= strtotime($coupon_data->valid_to))){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon date is expired '),200);
            }

            if($coupon_data->store_id != $store_user->id){
                return response(array('httpStatus'=>200, 'dateTime'=>time(), 'status'=>'fail','message' => 'Coupon is not applicable for your store',),200);
            }
        }
        
        $coupon_discount_percent = (!empty($coupon_data))?$coupon_data->discount:null;
        $coupon_item_id = (!empty($coupon_data))?$coupon_data->coupon_item_id:null;
        
        /** Coupon code end **/
        
        for($i=0;$i<count($products_list);$i++){
            if($products_list[$i]['product_status'] == 4 || $products_list[$i]['product_status'] == 1){
                
                $staff_id = $store_staff[$products_list[$i]['id']];
                
                // Auditor condition of discount
                if(isset($data['discount_percent']) ){
                    $discount_percent = $data['discount_percent'];
                    $discount_id = 0;
                }else{    
                    $product_data = CommonHelper::getPosProductData($products_list[$i]['peice_barcode'],'',$data['user_id'],$foc);
                    if(empty($coupon_data)){
                        $discount_percent = $product_data->discount_percent;
                    }else{
                        $discount_percent = $discount_percent_arr[$products_list[$i]['id']];
                    }
                    $discount_id = $product_data->discount_id;
                }
                
                $discount_amount = (empty($discount_percent))?0:round(($products_list[$i]['sale_price']*($discount_percent/100)),6);
                $discounted_price = $products_list[$i]['sale_price']-$discount_amount;
                
                if(empty($coupon_data)){
                    if((isset($store_user->gst_applicable) && $store_user->gst_applicable == 1) || $foc == 1 ){
                        $gst_data = CommonHelper::getGSTData($products_list[$i]['hsn_code'],$discounted_price);
                        $gst_percent = (!empty($gst_data))?$gst_data->rate_percent:0;
                    }else{
                        $gst_percent = 0;
                    }

                    $products_list[$i]['gst_inclusive'] = (isset($data['gst_inclusive']))?$data['gst_inclusive']:$product_data->gst_inclusive;
                }else{
                    $gst_percent = $gst_percent_arr[$products_list[$i]['id']];
                    $products_list[$i]['gst_inclusive'] = $gst_inclusive_arr[$products_list[$i]['id']];        
                }

                // Auditor condition of gst inclusive
                if(isset($data['gst_inclusive'])){
                    if($products_list[$i]['gst_inclusive'] == 1){
                        $discounted_price_orig = $discounted_price;
                        $gst_percent_1 = 100+$gst_percent;
                        $gst_percent_1 = $gst_percent_1/100;
                        $discounted_price = round($discounted_price/$gst_percent_1,6);

                        $gst_amount = $discounted_price_orig-$discounted_price;
                        $net_price = $discounted_price_orig; 
                    }else{
                        $gst_amount = (!empty($gst_percent))?round($discounted_price*($gst_percent/100),6):0;
                        $net_price = $discounted_price+$gst_amount;
                    }
                }else{
                    if($discount_percent == 0 || ($discount_percent > 0 && $products_list[$i]['gst_inclusive'] == 1)){
                        $discounted_price_orig = $discounted_price;
                        $gst_percent_1 = 100+$gst_percent;
                        $gst_percent_1 = $gst_percent_1/100;
                        $discounted_price = round($discounted_price/$gst_percent_1,6);

                        $gst_amount = $discounted_price_orig-$discounted_price;
                        $net_price = $discounted_price_orig; 
                    }else{
                        $gst_amount = (!empty($gst_percent))?round($discounted_price*($gst_percent/100),6):0;
                        $net_price = $discounted_price+$gst_amount;
                    }
                }

                $total_price+=round($net_price,6);

                $products_list[$i]['discount_percent'] = $discount_percent;
                $products_list[$i]['discount_amount'] = $discount_amount;
                $products_list[$i]['gst_percent'] = $gst_percent;
                $products_list[$i]['gst_amount'] = $gst_amount;
                $products_list[$i]['net_price'] = $net_price;
                $products_list[$i]['discount_id'] = $discount_id;
                $products_list[$i]['staff_id'] = $staff_id;
                $net_price_str.=$products_list[$i]['id'].':'.$net_price.',';
            }else{
                $total_price-=$products_list[$i]['net_price_return'];
                $net_price_str.=$products_list[$i]['id'].':-'.$products_list[$i]['net_price_return'].',';
                
                $products_list[$i]['staff_id'] = isset($store_staff[$products_list[$i]['id']])?$store_staff[$products_list[$i]['id']]:0;
            }
        }

        if(empty($store_user) && $foc == 0){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store not assigned to user', 'errors' => 'Store not assigned to user'));
        }
        
        if(round($total_price) < 0){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Order Total price is less than zero', 'errors' => 'Order Total price is less than zero'));
        }
        
        if($fake_inventory == 1){
            $data['cashAmtValue'] = $total_price;
        }
        
        $data['cashAmtValue'] = (!empty(trim($data['cashAmtValue'])))?trim($data['cashAmtValue']):0;
        $data['cardAmtValue'] = (!empty(trim($data['cardAmtValue'])))?trim($data['cardAmtValue']):0;
        $data['WalletAmtValue'] = (!empty(trim($data['WalletAmtValue'])))?trim($data['WalletAmtValue']):0;
        $data['voucherAmount'] = (!empty(trim($data['voucherAmount'])))?trim($data['voucherAmount']):0;
        
        if(!empty($data['cashAmtValue']) && !is_numeric($data['cashAmtValue'])){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Cash value should be numeric', 'errors' => 'Cash value should be numeric'));
        }
        if(!empty($data['cardAmtValue']) && !is_numeric($data['cardAmtValue'])){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Card value should be numeric', 'errors' => 'Card value should be numeric'));
        }
        if(!empty($data['WalletAmtValue']) && !is_numeric($data['WalletAmtValue'])){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Wallet value should be numeric', 'errors' => 'Wallet value should be numeric'));
        }
        if(!empty($data['voucherAmount']) && !is_numeric($data['voucherAmount'])){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Voucher value should be numeric', 'errors' => 'Voucher value should be numeric'));
        }
        
        $total_payment_received = $data['cashAmtValue']+$data['cardAmtValue']+$data['WalletAmtValue']+$data['voucherAmount'];
        
        /*if(($data['cashAmtValue']+$data['cardAmtValue']+$data['WalletAmtValue']+$data['voucherAmount']) < $total_price){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Cash + Card + Wallet + Voucher amount is less than Order Total price ('.$total_price.')', 'errors' => 'Cash + Card + Wallet + Voucher amount is less than Order Total price ('.$total_price.')'));
        }*/
        
        if(ceil($total_payment_received) != ceil($total_price) && floor($total_payment_received) != floor($total_price) && round($total_payment_received) != round($total_price)){
            /*$insertArray = ['store_id'=>$store_id,'inv_ids'=>trim($data['ids']),'client_price_list'=>trim($data['price_data']),'server_price_list'=>rtrim($net_price_str,','),'client_total_price'=>trim($data['total_net_price']),
            'server_total_price'=>$total_price,'cash_amount'=>$data['cashAmtValue'],'card_amount'=>$data['cardAmtValue'],'ewallet_amount'=>$data['WalletAmtValue'],'voucher_amount'=>$data['voucherAmount'],
            'total_items'=>count($id_array),'error_text'=>'Cash + Card + Wallet + Voucher amount is less/more than Order Total price ('.$total_price.')'];
            
            Pos_customer_orders_errors::create($insertArray);*/
            
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Cash + Card + Wallet + Voucher amount is less/more than Order Total price ('.$total_price.')', 'errors' => 'Cash + Card + Wallet + Voucher amount is less/more than Order Total price ('.$total_price.')'));
        }

        $store_id = (!empty($store_user))?$store_user->id:0;

        if($fake_inventory == 1){
            $order_no = $data['order_no'];
        }else{
            $order_no = (!empty($store_user))?CommonHelper::getPosOrderNo($store_user):CommonHelper::getWarehouseFocPosOrderNo();
            $order_exists =  Pos_customer_orders::where('order_no',$order_no)->where('invoice_series_type',2)->select('order_no')->first();
            if(!empty($order_exists)){
                return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Error in creating Order No', 'errors' => 'Error in creating Order No'));
            }
        }

        $insertArray = array('customer_id'=>$data['customer_id'],'store_id'=>$store_id,'payment_method'=>$payment_method,'store_user_id'=>$data['user_id'],'total_price'=>$total_price,
        'reference_no'=>$reference_no,'total_items'=>count($products_list),'order_no'=>$order_no,'order_source'=>$data['order_source'],'foc'=>$foc,'bags_count'=>$bags_count,'address_id'=>$address_id);

        // code by sandeep
        if(!empty($data['voucherAmount'])){
            $insertArray['voucher_amount']=$data['voucherAmount'];
        } 
        if(!empty($data['voucherComment'])){
            $insertArray['voucher_comment']=$data['voucherComment'];
        } 
        if(!empty($data['voucherApprover'])){
            $insertArray['voucher_approver_id']=$data['voucherApprover'];
        }
        
        $insertArray['customer_gst_no'] = (isset($data['customer_gst_no']) && !empty($data['customer_gst_no']))?trim($data['customer_gst_no']):null;
        
        $insertArray['coupon_item_id'] = $coupon_item_id;
        
        $insertArray['rp_order_id'] = (isset($data['rp_order_id']))?trim($data['rp_order_id']):null;
        $insertArray['rp_payment_id'] = (isset($data['rp_payment_id']))?trim($data['rp_payment_id']):null;
        $insertArray['rp_signature'] = (isset($data['rp_signature']))?trim($data['rp_signature']):null;
        
        $insertArray['bill_top_text'] = 'KIAASA';
        $insertArray['bill_bottom_text'] = (isset($store_user->store_info_type) && $store_user->store_info_type == 2)?'TIKI GLOBAL PRIVATE LIMITED':'KIAASA RETAIL PRIVATE LIMITED';
        $insertArray['store_gst_no'] = isset($store_user->gst_no)?$store_user->gst_no:null;
        $insertArray['store_gst_name'] = isset($store_user->gst_name)?$store_user->gst_name:null;
        $insertArray['store_info_type_1'] = isset($store_user->store_info_type)?$store_user->store_info_type:null;        
        
        $insertArray['bill_data_same'] = (isset($data['bill_data_same']))?trim($data['bill_data_same']):1;
        $insertArray['bill_cust_name'] = (isset($data['bill_cust_name']))?trim($data['bill_cust_name']):null;
        $insertArray['bill_address'] = (isset($data['bill_address']))?trim($data['bill_address']):null;
        $insertArray['bill_locality'] = (isset($data['bill_locality']))?trim($data['bill_locality']):null;
        $insertArray['bill_city_name'] = (isset($data['bill_city_name']))?trim($data['bill_city_name']):null;
        $insertArray['bill_postal_code'] = (isset($data['bill_postal_code']))?trim($data['bill_postal_code']):null;
        $insertArray['bill_state_id'] = (isset($data['bill_state_id']))?trim($data['bill_state_id']):null;

        if(isset($data['order_id']) && !empty($data['order_id'])){
            $updateArray = $insertArray;
            unset($updateArray['order_no']);
            $updateArray['order_status'] = 1;
            Pos_customer_orders::where('id',$data['order_id'])->update($updateArray);
            
        }else{
            $pos_customer_order = Pos_customer_orders::create($insertArray);            
        }
        
        for($i=0;$i<count($products_list);$i++){
            if($products_list[$i]['product_status'] == 4 || $products_list[$i]['product_status'] == 1){
                $discounted_price = $products_list[$i]['sale_price']-$products_list[$i]['discount_amount'];
                $discounted_price_actual = ($products_list[$i]['gst_inclusive'] == 1)?$discounted_price-$products_list[$i]['gst_amount']:$discounted_price;        
                $discount_amount_actual =  ($products_list[$i]['gst_inclusive'] == 1)?$products_list[$i]['discount_amount']+$products_list[$i]['gst_amount']:$products_list[$i]['discount_amount'];               
                
                $updateArray = array('product_status'=>5,'customer_order_id'=>$pos_customer_order->id,'store_sale_date'=>Carbon::now());

                $insertArray = array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'product_id'=>$products_list[$i]['product_master_id'],'inventory_id'=>$products_list[$i]['id'],'base_price'=>$products_list[$i]['base_price'],
                'sale_price'=>$products_list[$i]['sale_price'],'net_price'=>$products_list[$i]['net_price'],'discount_percent'=>$products_list[$i]['discount_percent'],'discount_id'=>$products_list[$i]['discount_id'],
                'discount_amount'=>$products_list[$i]['discount_amount'],'gst_percent'=>$products_list[$i]['gst_percent'],'gst_amount'=>$products_list[$i]['gst_amount'],'gst_inclusive'=>$products_list[$i]['gst_inclusive'],
                'staff_id'=>$products_list[$i]['staff_id'],'discounted_price'=>$discounted_price,'discounted_price_actual'=>$discounted_price_actual,'discount_amount_actual'=>$discount_amount_actual,
                'arnon_prod_inv'=>$products_list[$i]['arnon_inventory'],'coupon_discount_percent'=>$coupon_discount_percent,'coupon_item_id'=>$coupon_item_id,'foc'=>$foc,'product_sku_id'=>$products_list[$i]['product_sku_id'],
                'product_sku_id'=>$products_list[$i]['product_sku_id'],'po_item_id'=>$products_list[$i]['po_item_id'],'vendor_id'=>$products_list[$i]['vendor_id']);
            }else{
                $discounted_price = $products_list[$i]['sale_price_return']-$products_list[$i]['discount_amount_return'];
                $discounted_price_actual = ($products_list[$i]['gst_inclusive_return'] == 1)?$discounted_price-$products_list[$i]['gst_amount_return']:$discounted_price;        
                $discount_amount_actual =  ($products_list[$i]['gst_inclusive_return'] == 1)?$products_list[$i]['discount_amount_return']+$products_list[$i]['gst_amount_return']:$products_list[$i]['discount_amount_return'];               

                $updateArray = array('product_status'=>4,'customer_order_id'=>null,'store_sale_date'=>null);

                $other_store_product = ($store_id == $products_list[$i]['store_id'])?0:1;
                
                $insertArray = array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'product_id'=>$products_list[$i]['product_master_id'],'inventory_id'=>$products_list[$i]['id'],'base_price'=>-$products_list[$i]['base_price_return'],
                'sale_price'=>-$products_list[$i]['sale_price_return'],'net_price'=>-$products_list[$i]['net_price_return'],'discount_percent'=>-$products_list[$i]['discount_percent_return'],'product_sku_id'=>$products_list[$i]['product_sku_id'],
                'discount_amount'=>-$products_list[$i]['discount_amount_return'],'gst_percent'=>-$products_list[$i]['gst_percent_return'],'gst_amount'=>-$products_list[$i]['gst_amount_return'],'gst_inclusive'=>$products_list[$i]['gst_inclusive_return'],'staff_id'=>$products_list[$i]['staff_id'],
                'product_quantity'=>-1,'discounted_price'=>-$discounted_price,'discounted_price_actual'=>-$discounted_price_actual,'discount_amount_actual'=>-$discount_amount_actual,'other_store_product'=>$other_store_product,'arnon_prod_inv'=>$products_list[$i]['arnon_inventory'],'foc'=>$foc);
                
                if($store_id != $products_list[$i]['store_id']){
                    $other_store_products[] = $products_list[$i];
                }
            }

            Pos_product_master_inventory::where('id',$products_list[$i]['id'])->update($updateArray);

            Pos_customer_orders_detail::create($insertArray);
        }

        // code by sandeep
        if($data['cashAmtValue'] > 0 || $foc == 1){
            //$cash_payment = $total_price-($data['cardAmtValue']+$data['WalletAmtValue']);
            $insertCashOrderPayment=array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'payment_method'=>'Cash','payment_amount'=>$data['cashAmtValue'],'payment_received'=>$data['cashAmtValue'],'foc'=>$foc );
            Pos_customer_orders_payments::create($insertCashOrderPayment);
        }
        if($data['cardAmtValue'] > 0 ){
            $insertCashOrderPayment=array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'payment_method'=>'Card','payment_amount'=>$data['cardAmtValue'],'payment_received'=>$data['cardAmtValue'],'foc'=>$foc );
            Pos_customer_orders_payments::create($insertCashOrderPayment);
        }
        if($data['WalletAmtValue'] > 0 ){
            $insertCashOrderPayment=array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'payment_method'=>'E-Wallet','payment_amount'=>$data['WalletAmtValue'],'reference_number'=>$data['ref_no'],'payment_received'=>$data['WalletAmtValue'],'foc'=>$foc );
            Pos_customer_orders_payments::create($insertCashOrderPayment);
        }
        
        /* Update payment amount start  */
        
        if($total_payment_received != $total_price){
            $diff = round($total_price,2)-$total_payment_received;
            if($data['cashAmtValue'] > 0){
               $updateArray = array('payment_amount'=>($data['cashAmtValue']+$diff));
               Pos_customer_orders_payments::where('order_id',$pos_customer_order->id)->where('payment_method','Cash')->update($updateArray);
            }elseif($data['cardAmtValue'] > 0){
               $updateArray = array('payment_amount'=>($data['cardAmtValue']+$diff));
               Pos_customer_orders_payments::where('order_id',$pos_customer_order->id)->where('payment_method','Card')->update($updateArray);
            }elseif($data['WalletAmtValue'] > 0){
               $updateArray = array('payment_amount'=>($data['WalletAmtValue']+$diff));
               Pos_customer_orders_payments::where('order_id',$pos_customer_order->id)->where('payment_method','E-Wallet')->update($updateArray);
            }  
        }
        
        /* Update payment amount end */
        
        // Add fake inventory field and order date by updating order data
        if($fake_inventory == 1){
            $updateArray = array('fake_inventory'=>1,'created_at'=>$data['order_date'],'updated_at'=>$data['order_date']);
            Pos_customer_orders::where('id',$pos_customer_order->id)->update($updateArray);
            Pos_customer_orders_detail::where('order_id',$pos_customer_order->id)->update($updateArray);
            Pos_customer_orders_payments::where('order_id',$pos_customer_order->id)->update($updateArray);
        }
        
        // Update Pending orders date.  Update order detail and payment rows date 
        if(isset($data['order_id']) && !empty($data['order_id']) && $fake_inventory == 0){
            $updateArray = array('created_at'=>$pos_customer_order->created_at,'updated_at'=>$pos_customer_order->created_at);
            Pos_customer_orders_detail::where('order_id',$pos_customer_order->id)->update($updateArray);
            Pos_customer_orders_payments::where('order_id',$pos_customer_order->id)->update($updateArray);
        }
        
        if(!empty($coupon_data)){
            $updateArray = array('coupon_used'=>1,'order_id'=>$pos_customer_order->id);
            Coupon_items::where('id',$coupon_data->coupon_item_id)->update($updateArray);
        }
        
        if($bags_count > 0 && !empty($store_user)){
            Store::where('id',$store_user->id)->decrement('bags_inventory',$bags_count);
        }

        CommonHelper::createLog('POS Order Created. ID: '.$pos_customer_order->id,'POS_ORDER_CREATED','POS_ORDER');
        
        unset($pos_customer_order->payment_method);
        unset($pos_customer_order->reference_no);
        
        if(!empty($store_user)){
            $pdf_file_name = CommonHelper::posOrderInvoice(array('action'=>'save_pdf'),$pos_customer_order->id);
            $pos_customer_order['pdf_file_link'] = url('documents/pos_order_pdf/'.$pdf_file_name);
            $order_info = Pos_customer_orders::where('id',$pos_customer_order->id)->first();
            $pos_customer_order['pdf_file_link_1'] = url('orders/'.$order_info->pdf_file);
        }
        
        // Other store product code start
        if(!empty($other_store_products)){
            $company_data = CommonHelper::getCompanyData();
            
            for($i=0;$i<count($other_store_products);$i++){
                $other_store_prods[$other_store_products[$i]['store_id']][] = $other_store_products[$i];
            }
            
            foreach($other_store_prods as $from_store_id=>$prod_list){
                $from_store_data = Store::where('id',$from_store_id)->first();
                if(empty($from_store_id) || empty($store_id)){
                    continue;
                }
                // Create demand
                $invoice_no = CommonHelper::getStoreToStoreTransferInvoiceNo($from_store_id,$store_id);
                $insertArray = array('invoice_no'=>$invoice_no,'credit_invoice_no'=>null,'user_id'=>$data['user_id'],'demand_type'=>'inventory_transfer_to_store','demand_status'=>'store_loaded','push_demand_id'=>null,'store_id'=>$store_id,'store_data'=>json_encode($store_user),'store_state_id'=>$store_user->state_id,'from_store_id'=>$from_store_id,'from_store_data'=>json_encode($from_store_data));
                $demand = Store_products_demand::create($insertArray);
                
                for($i=0;$i<count($prod_list);$i++){
                    // Add store product demand inventory row
                    
                    if($from_store_data->store_type == 1 && $store_user->store_type == 2){
                        // Kiaasa to Franchisee.  Increase by 10%  // Add 10% to vendor base price if store is franchise
                        $store_base_rate = round($prod_list[$i]['vendor_base_price']+($prod_list[$i]['vendor_base_price']*.10),6);

                        if($store_user->gst_no != $company_data['company_gst_no']){
                            $gst_data = CommonHelper::getGSTData($prod_list[$i]['hsn_code'],$store_base_rate);
                            $gst_percent = $gst_data->rate_percent;
                            $gst_amount = round($store_base_rate*($gst_percent/100),6);
                        }else{
                            $gst_percent = $gst_amount = 0;
                        }

                        $store_base_price = $store_base_rate+$gst_amount;
                    }else{
                        // Do not increase/decrease rates in other cases
                        $store_base_rate = $prod_list[$i]['store_base_rate'];
                        $store_base_price = $prod_list[$i]['store_base_price'];
                        $gst_percent = $prod_list[$i]['store_gst_percent'];
                        $gst_amount = $prod_list[$i]['store_gst_amount'];
                    }
                    
                    $insertArray = array('demand_id'=>$demand->id,'inventory_id'=>$prod_list[$i]['id'],'transfer_status'=>1,'transfer_date'=>date('Y/m/d H:i:s'),
                    'push_demand_id'=>$prod_list[$i]['demand_id'],'base_price'=>$prod_list[$i]['base_price'],'sale_price'=>$prod_list[$i]['sale_price'],
                    'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price,
                    'store_id'=>$store_id,'product_id'=>$prod_list[$i]['product_master_id'],'product_sku_id'=>$prod_list[$i]['product_sku_id'],
                    'from_store_id'=>$from_store_id,'po_item_id'=>$prod_list[$i]['po_item_id'],'vendor_id'=>$prod_list[$i]['vendor_id'],'receive_status'=>1);
                    Store_products_demand_inventory::create($insertArray);
                
                    // Add/update store demand detail row
                    /*$demand_product = Store_products_demand_detail::where('demand_id',$demand->id)->where('product_id',$prod_list[$i]['product_master_id'])->where('is_deleted',0)->first();
                    if(empty($demand_product)){
                       $insertArray = array('demand_id'=>$demand->id,'store_id'=>null,'product_id'=>$prod_list[$i]['product_master_id'],'product_quantity'=>1,'po_item_id'=>$prod_list[$i]['po_item_id']); 
                       Store_products_demand_detail::create($insertArray);
                    }else{
                        $demand_product->increment('product_quantity');
                    }*/
                    
                    // Update pos inventory data
                    $updateArray = array('store_id'=>$store_id,'demand_id'=>$demand->id,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price);
                    Pos_product_master_inventory::where('id',$prod_list[$i]['id'])->update($updateArray);
                }
                
                CommonHelper::updateDemandTotalData($demand->id,3);
            }
        }
        
        // Other store product code end
        
        if(isset($data['draft_id']) && !empty($data['draft_id'])){
            $updateArray = array('is_deleted'=>1);
            Pos_customer_orders_drafts::where('id',$data['draft_id'])->update($updateArray);
        }
        
        return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Pos Order added successfully','order_data'=>$pos_customer_order),201);

    }
    
    public static function posOrderInvoice($data,$order_id){
        $error_message = '';
        $store_data = array();
        $request_data = $data;
        
        $pos_order_data = \DB::table('pos_customer_orders as pco')
        ->join('pos_customer as pc','pc.id', '=', 'pco.customer_id')
        ->join('users as u1','u1.id', '=', 'pco.store_user_id')        
        ->leftJoin('store as s','s.id', '=', 'pco.store_id')               
        ->where('pco.id',$order_id)->where('pco.is_deleted',0)
        ->select('pco.*','pc.customer_name','u1.name as store_user_name','s.store_name','pc.phone as customer_phone','pc.salutation')->first();

        $pos_order_products = \DB::table('pos_customer_orders_detail as pcod')
        ->join('pos_product_master as ppm','ppm.id', '=', 'pcod.product_id')
        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'pcod.inventory_id')
        ->leftJoin('design_lookup_items_master as dlim_1','ppm.color_id', '=', 'dlim_1.id')                         
        ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
        ->where('pcod.order_id',$order_id)->where('pcod.is_deleted',0)
        ->select('pcod.*','ppm.product_name','ppm.hsn_code','ppm.product_sku','dlim_1.name as color_name','psc.size as size_name','ppmi.peice_barcode')->get()->toArray();

        $company_data = CommonHelper::getCompanyData();
        
        if(!empty($pos_order_data->store_id)){
            $store_data = Store::where('id',$pos_order_data->store_id)->first();
        }

        $payment_types = Pos_customer_orders_payments::where('order_id',$order_id)->where('is_deleted',0)->get()->toArray();

        $data = array('message' => 'products list','pos_order_data' => $pos_order_data,'pos_order_products' => $pos_order_products,'company_data'=>$company_data,'store_data'=>$store_data,'payment_types'=>$payment_types);

        if(isset($request_data['action']) && $request_data['action'] == 'get_pdf'){
            //return view('store/pos_order_invoice_pdf',$data);
            $pdf = PDF::loadView('store/pos_order_invoice_pdf', $data)->setPaper('a4', 'landscape');;
            return $pdf->download('pos_order_invoice_pdf_'.$order_id);
        }
        
        if(isset($request_data['action']) && $request_data['action'] == 'save_pdf'){
            $pdf = PDF::loadView('store/pos_order_invoice_pdf', $data)->setPaper('a4', 'landscape');
            $str = $order_id.'_'.md5($pos_order_data->order_no).'.pdf';
            $pdf->save('documents/pos_order_pdf/'.$str);
            $pdf_file = $pos_order_data->id.rand(1001,9999).'.pdf';
            copy('documents/pos_order_pdf/'.$str,'orders/'.$pdf_file);
            Pos_customer_orders::where('id',$pos_order_data->id)->update(['pdf_file'=>$pdf_file]);
            return $str;
        }

        return view('store/pos_order_invoice_pdf',$data);
    }
    
    public static function getFabricPosProductHsnCode($category_id){
        //$array = array('20'=>'62046200','18'=>'62046300','31'=>'42022190');
        //return (isset($array[$category_id]))?$array[$category_id]:62046290;
        $category_hsn_data = Category_hsn_code::where('category_id',$category_id)->where('is_deleted',0)->first();
        return (!empty($category_hsn_data))?$category_hsn_data['hsn_code']:62046290;
    }
    
    public static function getFinancialYear($date){
        $fin_year = '';
        $month = date('m',strtotime($date));
        $year = date('Y',strtotime($date));
        if($month > 3){
            $next_year = $year+1;
            $fin_year = substr($year,2).substr($next_year,2);
        }else{
            $prev_year = $year-1;
            $fin_year = substr($prev_year,2).substr($year,2);
        }
        
        return $fin_year;
    }
    
    public static function parseValidationErrors($errors){
        $str = '';
        $errors_arr = json_decode(json_encode($errors),true);
        foreach($errors_arr as $key=>$value){
            $str.=$value[0];
        }
        return $str;
    }
    
    public static function inventoryPushDemandInvoiceNo($store_data){
        $invoice_no = '';
        $company_data = CommonHelper::getCompanyData();
        $financial_year = CommonHelper::getFinancialYear(date('Y/m/d'));

        if($store_data->gst_no != $company_data['company_gst_no']){ // Different GST
            $invoice_no = 'H-'.$financial_year.'-'.'NCT'.'-';
            $store_demand = Store_products_demand::wherein('demand_type',array('inventory_push','inventory_return_to_vendor'))->where('invoice_no','LIKE',"{$invoice_no}%")->where('invoice_series_type',2)->select('invoice_no')->orderBy('invoice_no','DESC')->first();
            $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,strrpos($store_demand->invoice_no,'-')+1):0;
            $invoice_no = 'H-'.$financial_year.'-'.'NCT'.'-'.str_pad($invoice_no+1, 5, "0", STR_PAD_LEFT);
        }elseif($store_data->gst_no == $company_data['company_gst_no'] && $store_data->state_id != $company_data['company_state_id']){ // Same GST and diff state
            $invoice_no = 'H-'.$financial_year.'-'.'NCT'.'-';
            $store_demand = Store_products_demand::wherein('demand_type',array('inventory_push','inventory_return_to_vendor'))->where('invoice_no','LIKE',"{$invoice_no}%")->where('invoice_series_type',2)->select('invoice_no')->orderBy('invoice_no','DESC')->first();
            $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,strrpos($store_demand->invoice_no,'-')+1):0;
            $invoice_no = 'H-'.$financial_year.'-'.'NCT'.'-'.str_pad($invoice_no+1, 5, "0", STR_PAD_LEFT);
        }else{
            $invoice_no = 'H-'.$financial_year.'-'.'NCU'.'-'; // Same GST and same state
            $store_demand = Store_products_demand::wherein('demand_type',array('inventory_push','inventory_return_to_vendor'))->where('invoice_no','LIKE',"{$invoice_no}%")->where('invoice_series_type',2)->select('invoice_no')->orderBy('invoice_no','DESC')->first();
            $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,strrpos($store_demand->invoice_no,'-')+1):0;
            $invoice_no = 'H-'.$financial_year.'-'.'NCU'.'-'.str_pad($invoice_no+1, 5, "0", STR_PAD_LEFT);
        }
        
        return $invoice_no;
    }
    
    public static function getWarehouseFocPosOrderNo(){
        $financial_year = CommonHelper::getFinancialYear(date('Y/m/d'));
        $order_no = 'WHFOC/'.substr($financial_year,0,2).'-'.substr($financial_year,2,2).'/';    
        
        $last_order =  Pos_customer_orders::where('foc',1)->whereRaw('(store_id = 0 OR store_id IS NULL)')->where('order_no','LIKE',"{$order_no}%")->select('order_no')->orderBy('order_no','DESC')->first();
        $order_no = (!empty($last_order) && !empty($last_order->order_no))?substr($last_order->order_no,strrpos($last_order->order_no,'/')+1):0;
        $order_no = 'WHFOC/'.substr($financial_year,0,2).'-'.substr($financial_year,2,2).'/'.str_pad($order_no+1,4, "0", STR_PAD_LEFT);
        
        return $order_no;
    }
    
    public static function currencyFormat($num){
        $explrestunits = "" ;
        $num = preg_replace('/,+/', '', $num);
        $words = explode(".", $num);
        $des = "00";
        if(count($words)<=2){
            $num=$words[0];
            if(count($words)>=2){$des=$words[1];}
            if(strlen($des)<2){$des="$des";}else{$des=substr($des,0,2);}
        }
        if(strlen($num)>3){
            $lastthree = substr($num, strlen($num)-3, strlen($num));
            $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
            $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
            $expunit = str_split($restunits, 2);
            for($i=0; $i<sizeof($expunit); $i++){
                // creates each of the 2's group and adds a comma to the end
                if($i==0)
                {
                    $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
                }else{
                    $explrestunits .= $expunit[$i].",";
                }
            }
            $thecash = $explrestunits.$lastthree;
        } else {
            $thecash = $num;
        }
        return "$thecash.$des"; // writes the final format where $currency is the currency symbol.
    }
    
    public static function getQueryString(){
        return $_SERVER['QUERY_STRING'];
    }
    
    public static function getStatusDeleteArray($type_id,$prefix){
        $array = array();
        if($type_id == 1){
            $array = array(array($prefix.'.is_deleted','=','0'));
        }
        if($type_id == 2){
            $array = array(array($prefix.'.status','=','1'),array($prefix.'.is_deleted','=','0'));
        }
        
        return $array;
    }
    
    public static function getInventoryDBObject($ppmi_tbl = [],$ppm_tbl = [],$po_tbl = [],$poi_tbl = [],$vendor_tbl = [],$size_tbl=[],$color_tbl=[],$category_tbl=[],$subcategory_tbl=[],$store_tbl=[],$po_grn_qc_items_tbl=[],$demand_tbl=[],$demand_inv_tbl=[]){
        $join_type_array = array('1'=>'join','2'=>'leftJoin');
        
        $inventory_data = \DB::table('pos_product_master_inventory as ppmi');
        
        if(!empty($ppm_tbl)){
            $join_type = $join_type_array[$ppm_tbl[0]];
            $whereArray = (isset($ppm_tbl[1]) && $ppm_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($ppm_tbl[1],'ppm'):array();
            $inventory_data = $inventory_data->$join_type('pos_product_master as ppm',function($join) use ($whereArray) {$join->on('ppm.id','=','ppmi.product_master_id')->where($whereArray)->where('ppm.fake_inventory',0);});        
        }
        
        if(!empty($po_tbl)){
            $join_type = $join_type_array[$po_tbl[0]];
            $whereArray = (isset($po_tbl[1]) && $po_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($po_tbl[1],'po'):array();
            $inventory_data = $inventory_data->$join_type('purchase_order as po',function($join) use ($whereArray) {$join->on('po.id','=','ppmi.po_id')->where($whereArray)->where('po.fake_inventory',0);});        
        }
        
        if(!empty($poi_tbl)){
            $join_type = $join_type_array[$poi_tbl[0]];
            $whereArray = (isset($poi_tbl[1]) && $poi_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($poi_tbl[1],'poi'):array();
            $inventory_data = $inventory_data->$join_type('purchase_order_items as poi',function($join) use ($whereArray) {$join->on('poi.id','=','ppmi.po_item_id')->where($whereArray)->where('poi.fake_inventory',0);});        
        }
        
        if(!empty($vendor_tbl)){
            $join_type = $join_type_array[$vendor_tbl[0]];
            $whereArray = (isset($vendor_tbl[1]) && $vendor_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($vendor_tbl[1],'vd'):array();
            $inventory_data = $inventory_data->$join_type('vendor_detail as vd',function($join) use ($whereArray) {$join->on('vd.id','=','po.vendor_id')->where($whereArray);});        
        }
        
        if(!empty($size_tbl)){
            $join_type = $join_type_array[$size_tbl[0]];
            $whereArray = (isset($size_tbl[1]) && $size_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($size_tbl[1],'psc'):array();
            $inventory_data = $inventory_data->$join_type('production_size_counts as psc',function($join) use ($whereArray) {$join->on('ppm.size_id','=','psc.id')->where($whereArray);});        
        }
        
        if(!empty($color_tbl)){
            $join_type = $join_type_array[$color_tbl[0]];
            $whereArray = (isset($color_tbl[1]) && $color_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($color_tbl[1],'dlim_color'):array();
            $inventory_data = $inventory_data->$join_type('design_lookup_items_master as dlim_color',function($join) use ($whereArray) {$join->on('ppm.color_id','=','dlim_color.id')->where($whereArray);});        
        }
        
        if(!empty($category_tbl)){
            $join_type = $join_type_array[$category_tbl[0]];
            $whereArray = (isset($category_tbl[1]) && $category_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($category_tbl[1],'dlim_category'):array();
            $inventory_data = $inventory_data->$join_type('design_lookup_items_master as dlim_category',function($join) use ($whereArray) {$join->on('ppm.category_id','=','dlim_category.id')/*->where($whereArray)*/;});        
        }
        
        if(!empty($subcategory_tbl)){
            $join_type = $join_type_array[$subcategory_tbl[0]];
            $whereArray = (isset($subcategory_tbl[1]) && $subcategory_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($subcategory_tbl[1],'dlim_subcategory'):array();
            $inventory_data = $inventory_data->$join_type('design_lookup_items_master as dlim_subcategory',function($join) use ($whereArray) {$join->on('ppm.subcategory_id','=','dlim_subcategory.id')/*->where($whereArray)*/;});        
        }
        
        /*if(!empty($po_grn_qc_items_tbl)){
            $join_type = $join_type_array[$po_grn_qc_items_tbl[0]];
            $whereArray = (isset($po_grn_qc_items_tbl[1]) && $po_grn_qc_items_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($po_grn_qc_items_tbl[1],'po_qc_items'):array();
            $inventory_data = $inventory_data->$join_type('purchase_order_grn_qc_items as po_qc_items',function($join) use ($whereArray) {$join->on('po_qc_items.inventory_id','=','ppmi.id')->where($whereArray);});        
        }
        
        if(!empty($demand_tbl)){
            $join_type = $join_type_array[$demand_tbl[0]];
            $whereArray = (isset($demand_tbl[1]) && $demand_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($demand_tbl[1],'spd'):array();
            $inventory_data = $inventory_data->$join_type('store_products_demand as spd',function($join) use ($whereArray) {$join->on('spd.id','=','ppmi.demand_id')->where($whereArray);});        
        }*/
        
        
        if(!empty($ppmi_tbl)){
            $whereArray = (isset($ppmi_tbl[1]) && $ppmi_tbl[1] > 0)?CommonHelper::getStatusDeleteArray($ppmi_tbl[1],'ppmi'):array();
            $inventory_data = $inventory_data->where($whereArray)->where('ppmi.fake_inventory',0);
        }
        
        return $inventory_data;
    }
    
    public static function getDispatchedPushDemandStatusList(){
        return array('warehouse_dispatched','store_loading','store_loaded');
    }
    
    public static function getPOInvoiceDebitNoteNo(){
        
        $debit_note_data = Debit_notes::where('debit_note_no','!=','')->where('debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice')->where('invoice_series_type',2)->orderBy('debit_note_no','DESC')->first();
        $debit_note_no_1 = (!empty($debit_note_data->debit_note_no))?substr($debit_note_data->debit_note_no,strpos($debit_note_data->debit_note_no,'-')+1):0;
        
        $qc_return_data = Purchase_order_grn_qc::where('type','qc_return')->where('grn_no','!=','')->where('invoice_series_type',2)->orderBy('id','DESC')->first();
        $debit_note_no_2 = (!empty($qc_return_data->grn_no))?substr($qc_return_data->grn_no,strpos($qc_return_data->grn_no,'-')+1):0;
        
        $debit_note_data = Debit_notes::where('debit_note_no','!=','')->where('debit_note_type','excess_amount')->where('invoice_series_type',2)->orderBy('debit_note_no','DESC')->first();
        $debit_note_no_3 = (!empty($debit_note_data->debit_note_no))?substr($debit_note_data->debit_note_no,strpos($debit_note_data->debit_note_no,'-')+1):0;
        
        $debit_note_no = max(array($debit_note_no_1,$debit_note_no_2,$debit_note_no_3));
        
        $debit_note_no = 'HNCD-'.str_pad($debit_note_no+1,4,'0',STR_PAD_LEFT);
        
        return $debit_note_no;
    }
   
    public static function getPOInvoiceCreditNoteNo(){
        
        $credit_note_data = Debit_notes::where('credit_note_no','!=','')->where('debit_note_type','less_inventory_from_vendor_to_warehouse_in_po_invoice')->where('invoice_series_type',2)->orderBy('credit_note_no','DESC')->first();
        $credit_note_no_1 = (!empty($credit_note_data->credit_note_no))?substr($credit_note_data->credit_note_no,strpos($credit_note_data->credit_note_no,'-')+1):0;

        $qc_return_data = Purchase_order_grn_qc::where('type','qc_return')->where('credit_note_no','!=','')->where('invoice_series_type',2)->orderBy('id','DESC')->first();
        $credit_note_no_2 = (!empty($qc_return_data->credit_note_no))?substr($qc_return_data->credit_note_no,strpos($qc_return_data->credit_note_no,'-')+1):0;

        $credit_note_data = Debit_notes::where('credit_note_no','!=','')->where('debit_note_type','excess_amount')->where('invoice_series_type',2)->orderBy('credit_note_no','DESC')->first();
        $credit_note_no_3 = (!empty($credit_note_data->credit_note_no))?substr($credit_note_data->credit_note_no,strpos($credit_note_data->credit_note_no,'-')+1):0;
        
        $credit_note_no = max(array($credit_note_no_1,$credit_note_no_2,$credit_note_no_3));
        
        $credit_note_no = 'HNCC'.substr(CommonHelper::getFinancialYear(date('Y/m/d')),0,2).'-'.str_pad($credit_note_no+1,4,'0',STR_PAD_LEFT);
        
        return $credit_note_no;
    }
    
    public static function getInventoryType($type_id){
        $inv_types = array('1'=>'NorthCorp','2'=>'Arnon');
        return isset($inv_types[$type_id])?$inv_types[$type_id]:'';
    }
    
    public static function getStoresList($where = array(),$sort_by = 'store_name'){
        $store_list = Store::where('is_deleted',0);
        if(!empty($where)){
            $store_list = $store_list->where($where);
        }
        $store_list = $store_list->orderBy($sort_by)->get()->toArray();
        return $store_list;
    }
    
    public static function getVendorsList(){
        $vendor_list = Vendor_detail::where('is_deleted',0)->orderBy('name')->get()->toArray();
        return $vendor_list;
    }
    
    public static function saveCacheFile($file_name,$data){
	if($file_handle = fopen($file_name, 'w')){
            if(!fwrite($file_handle, $data)){
                CommonHelper::createLog('Error in Saving Cache File '.$file_name,'File_Cache','File_Cache');
            }
            fclose($file_handle);	
	}else{
            CommonHelper::createLog('Error in Saving Cache File '.$file_name,'File_Cache','File_Cache');
	}
    }
    
    public static function getStoreToStoreTransferInvoiceNo($from_store_id,$to_store_id){
        $invoice_no = '';
        $from_store_data = \DB::table('store as s')->where('id',$from_store_id)->select('s.*')->first();
        $to_store_data = \DB::table('store as s')->where('id',$to_store_id)->select('s.*')->first();
        $from_store_state_id = $from_store_data->state_id;
        $to_store_state_id = $to_store_data->state_id;
        
        /*$store_state_id_name = array('2'=>'AP','5'=>'BR','9'=>'DL','12'=>'GJ','14'=>'HR','15'=>'JK','23'=>'MP','27'=>'PB','29'=>'RJ','33'=>'UK','34'=>'UP');  */  
        $state_list =  \DB::table('state_list as sl')->where('is_deleted',0)->select('id','state_name','state_prefix')->get();
        for($i=0;$i<count($state_list);$i++){
            $store_state_id_name[$state_list[$i]->id] = $state_list[$i]->state_prefix;
        }
                
        $financial_year = CommonHelper::getFinancialYear(date('Y/m/d'));
        $financial_year = substr($financial_year,0,2).'-'.substr($financial_year,2);
        $prefix = ($from_store_data->store_info_type == 1)?'K':'';
            
        if($from_store_state_id != $to_store_state_id){
            if(isset($store_state_id_name[$from_store_state_id])){
                $invoice_no = $prefix.$store_state_id_name[$from_store_state_id].'NC'.'/'.$financial_year.'/';
                $store_demand = Store_products_demand::where('demand_type','inventory_transfer_to_store')->where('invoice_no','LIKE',"{$invoice_no}%")->where('invoice_series_type',2)->select('invoice_no')->orderBy('invoice_no','DESC')->first();
                $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,strrpos($store_demand->invoice_no,'/')+1):0;
                $invoice_no = $prefix.$store_state_id_name[$from_store_state_id].'NC'.'/'.$financial_year.'/'.str_pad($invoice_no+1, 5, "0", STR_PAD_LEFT);
            }
        }else{
            if(isset($store_state_id_name[$from_store_state_id])){
                $invoice_no = $prefix.$store_state_id_name[$from_store_state_id].'CH'.'/'.$financial_year.'/';
                $store_demand = Store_products_demand::where('demand_type','inventory_transfer_to_store')->where('invoice_no','LIKE',"{$invoice_no}%")->where('invoice_series_type',2)->select('invoice_no')->orderBy('invoice_no','DESC')->first();
                $invoice_no = (!empty($store_demand) && !empty($store_demand->invoice_no))?substr($store_demand->invoice_no,strrpos($store_demand->invoice_no,'/')+1):0;
                $invoice_no = $prefix.$store_state_id_name[$from_store_state_id].'CH'.'/'.$financial_year.'/'.str_pad($invoice_no+1, 4, "0", STR_PAD_LEFT);
            }
        }
        
        return $invoice_no;
    }
    
    public static function formatDate($date){
        //convert dd/mm/yyyy  to yyyy/mm/dd
        if(!empty($date)){
            $dob_arr = explode('/',str_replace('-','/',trim($date)));
            $dob = (is_array($dob_arr) && count($dob_arr) == 3)?$dob_arr[2].'/'.$dob_arr[1].'/'.$dob_arr[0]:null;
        }else{
            $dob = null;
        }
        
        return $dob;
    }
    
    public static function getUserAttendanceText($code){
        $attendance = array('1'=>'Present','0'=>'Absent','2'=>'Half Day');
        return (isset($attendance[$code]))?$attendance[$code]:'Not Added';
    }
    
    public static function getUserAttendanceList(){
        return array('1'=>'Present','0'=>'Absent','2'=>'Half Day');
    }
    
    public static function getUserLeaveText($code){
        $leave_types = array('full_day'=>'FD Leave','half_day'=>'HD Leave');
        return (isset($leave_types[$code]))?$leave_types[$code]:'';
    }
    
    public static function getInventoryPaymentStatusText($status){
        $payment_status_list = array('Not Paid','Paid');
        return (isset($payment_status_list[$status]))?$payment_status_list[$status]:'';
    }
    
    public static function getInventoryDemandRoute($inv_data){
        
        $demand_route = $demand_url = '';
        
        if($inv_data->demand_type == 'inventory_push'){
            $demand_route = 'Warehouse to '.$inv_data->demand_store_1_name.' Store';
            $demand_url = 'warehouse/demand/inventory-push/detail/';
        }elseif($inv_data->demand_type == 'inventory_transfer_to_store'){
            $demand_route = $inv_data->demand_store_2_name.' to '.$inv_data->demand_store_1_name.' Store';
            $demand_url = 'store/demand/inventory-transfer-store/detail/';
        }elseif($inv_data->demand_type == 'inventory_return_complete'){
            $demand_route = $inv_data->demand_store_1_name.' to Warehouse';
            $demand_url = 'store/demand/inventory-return-complete/detail/';
        }elseif($inv_data->demand_type == 'inventory_return_to_vendor'){
            $demand_route = 'Warehouse to Vendor';
            $demand_url = 'warehouse/demand/inventory-return-vendor/detail/';
        }elseif($inv_data->demand_type == 'inventory_return_to_warehouse'){
            $demand_route = $inv_data->demand_store_1_name.' to Warehouse';
            $demand_url = 'store/demand/inventory-return/detail/';
        }else{
            $demand_route = $demand_url = '';
        }
        
        return array('demand_route'=>$demand_route,'demand_url'=>$demand_url);
    }
    
    public static function getDemandTypeText($demand_type){
        $demand_types = array('inventory_assign'=>'Assigned to Store','inventory_push'=>'Warehouse to Store Transfer','inventory_return_to_warehouse'=>'Return from Store to Warehouse',
        'inventory_return_complete'=>'Complete Return from Store to Warehouse','inventory_return_to_vendor'=>'Return from Warehouse to Vendor','inventory_transfer_to_store'=>'Store to Store Transfer');
        
        return isset($demand_types[$demand_type])?$demand_types[$demand_type]:'';
    }
    
    public static function getDemandStatusText($demand_type,$status){
        if($demand_type == 'inventory_push'){
            $demand_status_list = array('warehouse_loading'=>'Loading in Warehouse','warehouse_dispatched'=>'Dispatched from Warehouse to Store','store_loading'=>'Loading in Store','store_loaded'=>'Loading completed in Store','cancelled'=>'Demand Cancelled');
        }elseif($demand_type == 'inventory_return_to_warehouse'){
            $demand_status_list = array('store_loading'=>'Loading in Store','warehouse_dispatched'=>'Dispatched from Store to Warehouse','warehouse_loading'=>'Loading in Warehouse','warehouse_loaded'=>'Loading completed in Warehouse','cancelled'=>'Demand Cancelled');
        }elseif($demand_type == 'inventory_return_complete'){
            $demand_status_list = array('warehouse_dispatched'=>'Dispatched from Warehouse to Store','store_loaded'=>'Loading completed in Store','cancelled'=>'Demand Cancelled');
        }elseif($demand_type == 'inventory_return_to_vendor'){
            $demand_status_list = array('warehouse_loading'=>'Loading in Warehouse','warehouse_dispatched'=>'Dispatched from Warehouse to Vendor','cancelled'=>'Demand Cancelled');
        }elseif($demand_type == 'inventory_transfer_to_store'){
            $demand_status_list = array('loading'=>'Loading in Store 1','loaded'=>'Dispatched from Store 1 to Store 2','store_loading'=>'Loading in Store 2','store_loaded'=>'Loading completed by Store 2','cancelled'=>'Demand Cancelled');
        }
        
        return isset($demand_status_list[$status])?$demand_status_list[$status]:'';
    }
    
    public static function getPosProductDataUpdated($barcode,$id_str,$user_id){
        $barcode = trim($barcode);
        $ids = trim($id_str);
        $store_data = CommonHelper::getUserStoreData($user_id);

        $product_data = \DB::table('pos_product_master_inventory as ppmi')
        ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')                 
        ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
        ->leftJoin('design_lookup_items_master as dlim_2','ppm.subcategory_id', '=', 'dlim_2.id')   
        ->leftJoin('design_lookup_items_master as dlim_3','ppm.color_id', '=', 'dlim_3.id')                         
        ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
        ->where('ppmi.peice_barcode',$barcode)        
        //->where('ppmi.store_id',$store_data->id)
        ->where('ppmi.is_deleted',0)->where('ppm.is_deleted',0)
        ->where('ppm.status',1)->where('ppmi.status',1);

        if(!empty($ids)){
            $product_data = $product_data->whereRaw("ppmi.id NOT IN($ids)");
        }

        $product_data = $product_data->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','ppm.category_id','ppm.subcategory_id','ppm.hsn_code',
        'ppm.gst_inclusive','dlim_1.name as category_name','dlim_2.name as subcategory_name','dlim_3.name as color_name','psc.size as size_name')->first();
        
        if(!empty($product_data)){
            if($product_data->product_status == 4 && $product_data->store_id != $store_data->id){
                $product_data = null;
            }elseif(!in_array($product_data->product_status,array(4,5))){
                $product_data = null;
            }
        }
        
        return $product_data;
    }
    
    
    public static function createPosOrderUpdated($data){
        
        $total_price = $total_payment_received = 0;
        $id_array = explode(',',$data['ids']);
        $staff_id_array = (isset($data['staff_ids']))?explode(',',$data['staff_ids']):array();
        
        $store_staff = $other_store_products = $other_store_prods = $coupon_data = $gst_percent_arr = $gst_inclusive_arr = $discount_percent_arr = array();
        $payment_method = $reference_no = null; 
        
        for($i=0;$i<count($id_array);$i++){
            $store_staff[$id_array[$i]] = (isset($staff_id_array[$i]))?$staff_id_array[$i]:null;
        }
            
        $store_user = CommonHelper::getUserStoreData($data['user_id']); 
        
        if(empty($store_user)){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Store not assigned to user', 'errors' => 'Store not assigned to user'));
        }
        
        $products_data = CommonHelper::posBillingProductsData($id_array,$store_user);
        $group_products = $products_data['group_products'];
        $individual_products = $products_data['individual_products'];
        $return_products = $products_data['return_products'];
        $discount_group = $products_data['discount_group'];
        
        for($i=0;$i<count($group_products);$i++){
            $total_price+=$group_products[$i]['net_price'];
            
            $group_products[$i]['bill_product_type'] = 'group';
            $group_products[$i]['bill_product_group_id'] = $discount_group['id'];
            $group_products[$i]['bill_product_group_name'] = $discount_group['buy_items'].','.$discount_group['get_items'];
            
            $products_list[] = $group_products[$i];
        }
        
        for($i=0;$i<count($individual_products);$i++){
            $total_price+=$individual_products[$i]['net_price'];
            
            $individual_products[$i]['bill_product_type'] = 'individual';
            $individual_products[$i]['bill_product_group_id'] = null;
            $individual_products[$i]['bill_product_group_name'] = null;
            
            $products_list[] = $individual_products[$i];
        }
        
        for($i=0;$i<count($return_products);$i++){
            $total_price-=$return_products[$i]['order_data']['net_price'];
            
            $return_products[$i]['bill_product_type'] = 'return';
            $return_products[$i]['bill_product_group_id'] = null;
            $return_products[$i]['bill_product_group_name'] = null;
            
            $products_list[] = $return_products[$i];
            
        }
        
        if(round($total_price) < 0){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Order Total price is less than zero', 'errors' => 'Order Total price is less than zero'));
        }
        
        $data['cashAmtValue'] = (!empty(trim($data['cashAmtValue'])))?trim($data['cashAmtValue']):0;
        $data['cardAmtValue'] = (!empty(trim($data['cardAmtValue'])))?trim($data['cardAmtValue']):0;
        $data['WalletAmtValue'] = (!empty(trim($data['WalletAmtValue'])))?trim($data['WalletAmtValue']):0;
        $data['voucherAmount'] = (!empty(trim($data['voucherAmount'])))?trim($data['voucherAmount']):0;
        
        if(!empty($data['cashAmtValue']) && !is_numeric($data['cashAmtValue'])){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Cash value should be numeric', 'errors' => 'Cash value should be numeric'));
        }
        if(!empty($data['cardAmtValue']) && !is_numeric($data['cardAmtValue'])){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Card value should be numeric', 'errors' => 'Card value should be numeric'));
        }
        if(!empty($data['WalletAmtValue']) && !is_numeric($data['WalletAmtValue'])){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Wallet value should be numeric', 'errors' => 'Wallet value should be numeric'));
        }
        if(!empty($data['voucherAmount']) && !is_numeric($data['voucherAmount'])){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Voucher value should be numeric', 'errors' => 'Voucher value should be numeric'));
        }
        
        $total_payment_received = $data['cashAmtValue']+$data['cardAmtValue']+$data['WalletAmtValue']+$data['voucherAmount'];
        
        if(ceil($total_payment_received) != ceil($total_price) && floor($total_payment_received) != floor($total_price) ){
            return response(array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Cash + Card + Wallet + Voucher amount is less/more than Order Total price ('.$total_price.')', 'errors' => 'Cash + Card + Wallet + Voucher amount is less/more than Order Total price ('.$total_price.')'));
        }

        $store_id = $store_user->id;

        $coupon_item_id = $coupon_discount_percent = 0;
        $last_order =  Pos_customer_orders::where('store_id',$store_id)->whereRaw('YEAR(created_at) = YEAR(CURDATE())')->orderBy('id','DESC')->first();

        $order_no = (!empty($last_order) && strlen($last_order->order_no) == 12)?ltrim(substr($last_order->order_no,6),0):0;
        $order_no = 'K'.$store_user->store_code.date('y').str_pad(($order_no+1),6,'0',STR_PAD_LEFT);

        $insertArray = array('customer_id'=>$data['customer_id'],'store_id'=>$store_id,'payment_method'=>$payment_method,'store_user_id'=>$data['user_id'],'total_price'=>$total_price,
        'reference_no'=>$reference_no,'total_items'=>count($products_list),'order_no'=>$order_no,'order_source'=>$data['order_source']);

        // code by sandeep
        if(!empty($data['voucherAmount'])){
            $insertArray['voucher_amount']=$data['voucherAmount'];
        } 
        if(!empty($data['voucherComment'])){
            $insertArray['voucher_comment']=$data['voucherComment'];
        } 
        if(!empty($data['voucherApprover'])){
            $insertArray['voucher_approver_id']=$data['voucherApprover'];
        }
        
        $insertArray['customer_gst_no'] = (isset($data['customer_gst_no']) && !empty($data['customer_gst_no']))?trim($data['customer_gst_no']):null;
        
        $insertArray['coupon_item_id'] = $coupon_item_id;

        $pos_customer_order = Pos_customer_orders::create($insertArray);            
        
        for($i=0;$i<count($products_list);$i++){
            if($products_list[$i]['product_status'] == 4){
                $discounted_price = $products_list[$i]['sale_price']-$products_list[$i]['discount_amount'];
                $discounted_price_actual = ($products_list[$i]['gst_inclusive'] == 1)?$discounted_price-$products_list[$i]['gst_amount']:$discounted_price;        
                $discount_amount_actual =  ($products_list[$i]['gst_inclusive'] == 1)?$products_list[$i]['discount_amount']+$products_list[$i]['gst_amount']:$products_list[$i]['discount_amount'];               
                $products_list[$i]['discount_id'] = 0;
                $staff_id = $store_staff[$products_list[$i]['id']];
                        
                $updateArray = array('product_status'=>5,'customer_order_id'=>$pos_customer_order->id,'store_sale_date'=>Carbon::now());

                $insertArray = array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'product_id'=>$products_list[$i]['product_master_id'],'inventory_id'=>$products_list[$i]['id'],'base_price'=>$products_list[$i]['base_price'],
                'sale_price'=>$products_list[$i]['sale_price'],'net_price'=>$products_list[$i]['net_price'],'discount_percent'=>$products_list[$i]['discount_percent'],'discount_id'=>$products_list[$i]['discount_id'],
                'discount_amount'=>$products_list[$i]['discount_amount'],'gst_percent'=>$products_list[$i]['gst_percent'],'gst_amount'=>$products_list[$i]['gst_amount'],'gst_inclusive'=>$products_list[$i]['gst_inclusive'],
                'staff_id'=>$staff_id,'discounted_price'=>$discounted_price,'discounted_price_actual'=>$discounted_price_actual,'discount_amount_actual'=>$discount_amount_actual,'arnon_prod_inv'=>$products_list[$i]['arnon_inventory'],
                'coupon_discount_percent'=>$coupon_discount_percent,'coupon_item_id'=>$coupon_item_id,'bill_product_type'=>$products_list[$i]['bill_product_type'],'bill_product_group_id'=>$products_list[$i]['bill_product_group_id'],'bill_product_group_name'=>$products_list[$i]['bill_product_group_name']);
            }else{
                $discounted_price = $products_list[$i]['order_data']['sale_price']-$products_list[$i]['order_data']['discount_amount'];
                $discounted_price_actual = ($products_list[$i]['order_data']['gst_inclusive'] == 1)?$discounted_price-$products_list[$i]['order_data']['gst_amount']:$discounted_price;        
                $discount_amount_actual =  ($products_list[$i]['order_data']['gst_inclusive'] == 1)?$products_list[$i]['order_data']['discount_amount']+$products_list[$i]['order_data']['gst_amount']:$products_list[$i]['order_data']['discount_amount'];               

                $updateArray = array('product_status'=>4,'customer_order_id'=>null,'store_sale_date'=>null);

                $other_store_product = ($store_id == $products_list[$i]['order_data']['store_id'])?0:1;
                
                $insertArray = array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'product_id'=>$products_list[$i]['product_master_id'],'inventory_id'=>$products_list[$i]['id'],'base_price'=>-$products_list[$i]['order_data']['base_price'],
                'sale_price'=>-$products_list[$i]['order_data']['sale_price'],'net_price'=>-$products_list[$i]['order_data']['net_price'],'discount_percent'=>-$products_list[$i]['order_data']['discount_percent'],
                'discount_amount'=>-$products_list[$i]['order_data']['discount_amount'],'gst_percent'=>-$products_list[$i]['order_data']['gst_percent'],'gst_amount'=>-$products_list[$i]['order_data']['gst_amount'],'gst_inclusive'=>$products_list[$i]['order_data']['gst_inclusive'],
                'product_quantity'=>-1,'discounted_price'=>-$discounted_price,'discounted_price_actual'=>-$discounted_price_actual,'discount_amount_actual'=>-$discount_amount_actual,'other_store_product'=>$other_store_product,'arnon_prod_inv'=>$products_list[$i]['arnon_inventory'],
                'bill_product_type'=>$products_list[$i]['bill_product_type'],'bill_product_group_id'=>$products_list[$i]['bill_product_group_id'],'bill_product_group_name'=>$products_list[$i]['bill_product_group_name']);
                
                if($store_id != $products_list[$i]['store_id']){
                    $other_store_products[] = $products_list[$i];
                }
            }

            Pos_product_master_inventory::where('id',$products_list[$i]['id'])->update($updateArray);

            Pos_customer_orders_detail::create($insertArray);
        }

        // code by sandeep
        if($data['cashAmtValue'] > 0 ){
            //$cash_payment = $total_price-($data['cardAmtValue']+$data['WalletAmtValue']);
            $insertCashOrderPayment=array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'payment_method'=>'Cash','payment_amount'=>$data['cashAmtValue'],'payment_received'=>$data['cashAmtValue'] );
            Pos_customer_orders_payments::create($insertCashOrderPayment);
        }
        if($data['cardAmtValue'] > 0 ){
            $insertCashOrderPayment=array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'payment_method'=>'Card','payment_amount'=>$data['cardAmtValue'],'payment_received'=>$data['cardAmtValue'] );
            Pos_customer_orders_payments::create($insertCashOrderPayment);
        }
        if($data['WalletAmtValue'] > 0 ){
            $insertCashOrderPayment=array('order_id'=>$pos_customer_order->id,'store_id'=>$store_id,'payment_method'=>'E-Wallet','payment_amount'=>$data['WalletAmtValue'],'reference_number'=>$data['ref_no'],'payment_received'=>$data['WalletAmtValue'] );
            Pos_customer_orders_payments::create($insertCashOrderPayment);
        }
        
        /* Update payment amount start  */
        
        if($total_payment_received != $total_price){
            $diff = round($total_price,2)-$total_payment_received;
            if($data['cashAmtValue'] > 0){
               $updateArray = array('payment_received'=>($data['cashAmtValue']+$diff));
               Pos_customer_orders_payments::where('order_id',$pos_customer_order->id)->where('payment_method','Cash')->update($updateArray);
            }elseif($data['cardAmtValue'] > 0){
               $updateArray = array('payment_received'=>($data['cardAmtValue']+$diff));
               Pos_customer_orders_payments::where('order_id',$pos_customer_order->id)->where('payment_method','Card')->update($updateArray);
            }elseif($data['WalletAmtValue'] > 0){
               $updateArray = array('payment_received'=>($data['WalletAmtValue']+$diff));
               Pos_customer_orders_payments::where('order_id',$pos_customer_order->id)->where('payment_method','E-Wallet')->update($updateArray);
            }  
        }
        
        /* Update payment amount end */
        
        if(!empty($coupon_data)){
            $updateArray = array('coupon_used'=>1,'order_id'=>$pos_customer_order->id);
            Coupon_items::where('id',$coupon_data->coupon_item_id)->update($updateArray);
        }

        CommonHelper::createLog('POS Order Created. ID: '.$pos_customer_order->id,'POS_ORDER_CREATED','POS_ORDER');
        
        unset($pos_customer_order->payment_method);
        unset($pos_customer_order->reference_no);
        
        $pdf_file_name = CommonHelper::posOrderInvoice(array('action'=>'save_pdf'),$pos_customer_order->id);
        $pos_customer_order['pdf_file_link'] = url('documents/pos_order_pdf/'.$pdf_file_name);
        
        // Other store product code start
        if(!empty($other_store_products)){
            $company_data = CommonHelper::getCompanyData();
            
            for($i=0;$i<count($other_store_products);$i++){
                $other_store_prods[$other_store_products[$i]['store_id']][] = $other_store_products[$i];
            }
            
            foreach($other_store_prods as $from_store_id=>$prod_list){
                $from_store_data = Store::where('id',$from_store_id)->first();
                // Create demand
                $invoice_no = CommonHelper::getStoreToStoreTransferInvoiceNo($from_store_id,$store_id);
                $insertArray = array('invoice_no'=>$invoice_no,'credit_invoice_no'=>null,'user_id'=>$data['user_id'],'demand_type'=>'inventory_transfer_to_store','demand_status'=>'store_loaded','push_demand_id'=>null,'store_id'=>$store_id,'store_data'=>json_encode($store_user),'store_state_id'=>$store_user->state_id,'from_store_id'=>$from_store_id,'from_store_data'=>json_encode($from_store_data));
                $demand = Store_products_demand::create($insertArray);
                
                for($i=0;$i<count($prod_list);$i++){
                    // Add store product demand inventory row
                    
                    if($from_store_data->store_type == 1 && $store_user->store_type == 2){
                        // Kiaasa to Franchisee.  Increase by 10%  // Add 10% to vendor base price if store is franchise
                        $store_base_rate = round($prod_list[$i]['vendor_base_price']+($prod_list[$i]['vendor_base_price']*.10),6);

                        if($store_user->gst_no != $company_data['company_gst_no']){
                            $gst_data = CommonHelper::getGSTData($prod_list[$i]['hsn_code'],$store_base_rate);
                            $gst_percent = $gst_data->rate_percent;
                            $gst_amount = round($store_base_rate*($gst_percent/100),6);
                        }else{
                            $gst_percent = $gst_amount = 0;
                        }

                        $store_base_price = $store_base_rate+$gst_amount;
                    }else{
                        // Do not increase/decrease rates in other cases
                        $store_base_rate = $prod_list[$i]['store_base_rate'];
                        $store_base_price = $prod_list[$i]['store_base_price'];
                        $gst_percent = $prod_list[$i]['store_gst_percent'];
                        $gst_amount = $prod_list[$i]['store_gst_amount'];
                    }
                    
                    $insertArray = array('demand_id'=>$demand->id,'inventory_id'=>$prod_list[$i]['id'],'transfer_status'=>1,'transfer_date'=>date('Y/m/d H:i:s'),'push_demand_id'=>$prod_list[$i]['demand_id'],'base_price'=>$prod_list[$i]['base_price'],'sale_price'=>$prod_list[$i]['sale_price'],'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price);
                    Store_products_demand_inventory::create($insertArray);
                
                    // Add/update store demand detail row
                    /*$demand_product = Store_products_demand_detail::where('demand_id',$demand->id)->where('product_id',$prod_list[$i]['product_master_id'])->where('is_deleted',0)->first();
                    if(empty($demand_product)){
                       $insertArray = array('demand_id'=>$demand->id,'store_id'=>null,'product_id'=>$prod_list[$i]['product_master_id'],'product_quantity'=>1,'po_item_id'=>$prod_list[$i]['po_item_id']); 
                       Store_products_demand_detail::create($insertArray);
                    }else{
                        $demand_product->increment('product_quantity');
                    }*/
                    
                    // Update pos inventory data
                    $updateArray = array('store_id'=>$store_id,'demand_id'=>$demand->id,'store_base_rate'=>$store_base_rate,'store_gst_percent'=>$gst_percent,'store_gst_amount'=>$gst_amount,'store_base_price'=>$store_base_price);
                    Pos_product_master_inventory::where('id',$prod_list[$i]['id'])->update($updateArray);
                }
            }
        }
        
        // Other store product code end
        
        return response(array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Pos Order added successfully','order_data'=>$pos_customer_order),201);

    }
    
    public static function posBillingProductsData($ids,$store_data){
         
        $buy_products = $return_products = $exchanged_products = $buy_products_updated = $discount_groups = $discount_group = $group_products = array();
        $individual_products = $single_product_data = $return_products_types = $previous_return_products = array();
        $return_products_count = array('group'=>0,'individual'=>0,'previous'=>0);
        $discount_percent_products = array('zero'=>0,'other'=>0);
        $count = $sale_price_total = $discount_total = $gst_total = $net_price_total = 0;
        $error_msg = '';

        $products_list = \DB::table('pos_product_master_inventory as ppmi')
        ->join('pos_product_master as ppm','ppmi.product_master_id', '=', 'ppm.id')                 
        ->join('store as s','ppmi.store_id', '=', 's.id')                   
        ->leftJoin('design_lookup_items_master as dlim_1','ppm.category_id', '=', 'dlim_1.id')         
        ->leftJoin('design_lookup_items_master as dlim_2','ppm.color_id', '=', 'dlim_2.id')                         
        ->leftJoin('production_size_counts as psc','ppm.size_id', '=', 'psc.id')        
        ->wherein('ppmi.id',$ids)        
        //->where('ppmi.store_id',$store_data->id)
        ->where('ppmi.is_deleted',0)
        ->where('ppm.is_deleted',0)
        ->where('ppm.status',1)
        ->where('ppmi.status',1)
        ->select('ppmi.*','ppm.product_name','ppm.product_barcode','ppm.product_sku','ppm.category_id','ppm.hsn_code',
        'dlim_1.name as category_name','dlim_2.name as color_name','psc.size as size_name','s.store_name')
        ->orderBy('ppmi.sale_price','DESC')        
        ->get()->toArray();

        $products_list = json_decode(json_encode($products_list),true);

        for($i=0;$i<count($products_list);$i++){
            if($products_list[$i]['product_status'] == 4){
                $buy_products[] = $products_list[$i];
            }else{
                $return_products[] = $products_list[$i];
            }
        }
        
        if(count($return_products) > 0){
            for($i=0;$i<count($return_products);$i++){
                $order_data = Pos_customer_orders_detail::where('order_id',$return_products[$i]['customer_order_id'])->where('inventory_id',$return_products[$i]['id'])->first();
                $return_products[$i]['order_data'] = $order_data;
                if(!empty($order_data['bill_product_type'])){
                    if($order_data['bill_product_type'] == 'group'){
                        $return_products_types['group'][] = $return_products[$i];
                        $return_products_count['group']+=1;
                    }else{
                        $return_products_types['individual'][] = $return_products[$i];
                        $return_products_count['individual']+=1;
                    }
                }else{
                    $return_products_types['previous'][] = $return_products[$i];
                    $return_products_count['previous']+=1;
                }
            }
           
            for($i=0;$i<count($return_products);$i++){
                if($return_products[$i]['order_data']['discount_percent'] == 0 || $return_products[$i]['order_data']['discount_percent'] == 100){
                    $discount_percent_products['zero']+=1;
                }else{
                    $discount_percent_products['other']+=1;
                }
            }
            
            if($return_products_count['group'] > 0 && $return_products_count['individual'] > 0){
                $error_msg = 'Scheme products cannot be returned with other products';
            }elseif($return_products_count['group'] > 0 && $return_products_count['previous'] > 0){
                $error_msg = 'Scheme products cannot be returned with other products';
            }elseif($return_products_count['individual'] > 0 && $return_products_count['previous'] > 0){
                $error_msg = 'New Scheme products cannot be returned with previous products';
            }elseif($discount_percent_products['zero'] > 0 && $discount_percent_products['other'] > 0 ){
                $error_msg = '0% discount products cannot be returned with other discount products';
            }
        }
        
        if(empty($error_msg)){
            for($i=0;$i<count($return_products);$i++){
                

                if($return_products[$i]['store_id'] != $store_data->id){
                    $return_products[$i]['other_store_prod'] = 1;
                    $return_products[$i]['other_store_name'] = $return_products[$i]['store_name'];
                }else{
                    $return_products[$i]['other_store_prod'] = 0;
                }
                
                if($return_products[$i]['order_data']['discount_percent'] == 100){
                    $return_products[$i]['order_data']['discount_percent'] = 0;
                    $return_products[$i]['order_data']['discount_amount'] = 0;
                    $return_products[$i]['order_data']['discounted_price'] = $return_products[$i]['order_data']['sale_price'];

                    $gst_data = CommonHelper::getGSTData($return_products[$i]['hsn_code'],$return_products[$i]['order_data']['discounted_price']);
                    $return_products[$i]['gst_percent'] = $gst_data->rate_percent;
                    $gst_percent = $gst_data->rate_percent;

                    if($return_products[$i]['order_data']['gst_inclusive'] == 1){
                        $discounted_price_orig = $discounted_price = $return_products[$i]['order_data']['discounted_price'];
                        $gst_percent_1 = 100+$gst_percent;
                        $gst_percent_1 = $gst_percent_1/100;
                        $discounted_price = round($discounted_price/$gst_percent_1,6);

                        $gst_amount = $discounted_price_orig-$discounted_price;
                        $net_price = $discounted_price_orig; 
                        $gst_inclusive = 1;
                    }else{
                        $gst_amount = $return_products[$i]['order_data']['discounted_price']*($gst_percent/100);
                        $net_price = $return_products[$i]['order_data']['discounted_price']+$gst_amount;
                        $gst_inclusive = 0;
                    }

                    $return_products[$i]['order_data']['gst_amount'] = round($gst_amount,6);
                    $return_products[$i]['order_data']['net_price'] = round($net_price,6);
                    $return_products[$i]['order_data']['gst_inclusive'] = $gst_inclusive;        
                }
            }

           

            // Create groups and get single product data
            $discounts = Discount_list::where('is_deleted',0)->orderBy('get_items')->get()->toArray();
            for($i=0;$i<count($discounts);$i++){
                if($discounts[$i]['item_type'] == 'multiple'){
                    $discount_groups[] = $discounts[$i];
                }else{
                    $single_product_data = $discounts[$i];
                }
            }
            
            // check if return prodcuts have scheme group products in it. 
            if($return_products_count['group'] == 0){

                // if number of get items in group are exactly same as buy items
                for($i=0;$i<count($discount_groups);$i++){
                    if($discount_groups[$i]['get_items'] == count($buy_products)){
                        $discount_group = $discount_groups[$i];
                    }
                }

                // if number of get items in group are exactly not same as buy items
                if(empty($discount_group)){
                    for($i=0;$i<count($discount_groups);$i++){
                        if(isset($discount_groups[$i+1]['get_items']) && $discount_groups[$i]['get_items'] < count($buy_products) && $discount_groups[$i+1]['get_items'] > count($buy_products)){
                            $discount_group = $discount_groups[$i];
                        }
                    }
                }

                for($i=0;$i<count($buy_products);$i++){
                    if(isset($discount_group['get_items']) && $discount_group['get_items'] > $count ){
                        $group_products[] = $buy_products[$i];
                        $count++;
                        continue;
                    }

                    $individual_products[] = $buy_products[$i];
                }
            }else{
                for($i=0;$i<count($buy_products);$i++){
                     $individual_products[] = $buy_products[$i];
                }
            }
            
            if($return_products_count['previous'] > 0){
                for($i=0;$i<count($return_products);$i++){
                   $previous_return_products[] = $return_products[$i];
                }
            }
            
            usort($previous_return_products, array("CommonHelper","sortResults"));
            
            for($i=0;$i<count($group_products);$i++){
                if($discount_group['buy_items'] > $i ){
                    $gst_data = CommonHelper::getGSTData($group_products[$i]['hsn_code'],$group_products[$i]['sale_price']);
                    $group_products[$i]['gst_percent'] = $gst_data->rate_percent;
                    $gst_percent = $gst_data->rate_percent;

                    if($discount_group['gst_type'] == 'inclusive'){
                        $discounted_price_orig = $discounted_price = $group_products[$i]['sale_price'];
                        $gst_percent_1 = 100+$gst_percent;
                        $gst_percent_1 = $gst_percent_1/100;
                        $discounted_price = round($discounted_price/$gst_percent_1,6);

                        $gst_amount = $discounted_price_orig-$discounted_price;
                        $net_price = $discounted_price_orig; 
                        $gst_inclusive = 1;
                    }else{
                        $gst_amount = $group_products[$i]['sale_price']*($group_products[$i]['gst_percent']/100);
                        $net_price = $group_products[$i]['sale_price']+$gst_amount;
                        $gst_inclusive = 0;
                    }

                    $group_products[$i]['gst_amount'] = round($gst_amount,6);
                    $group_products[$i]['net_price'] = round($net_price,6);
                    $group_products[$i]['discount_percent'] = 0;
                    $group_products[$i]['discount_amount'] = 0;
                    $group_products[$i]['discounted_price'] = $group_products[$i]['sale_price'];
                    $group_products[$i]['gst_inclusive'] = $gst_inclusive;

                    continue;
                }

                $group_products[$i]['gst_percent'] = 0;
                $group_products[$i]['gst_amount'] = 0;
                $group_products[$i]['net_price'] = 0;
                $group_products[$i]['discount_percent'] = 100;
                $group_products[$i]['discount_amount'] = $group_products[$i]['sale_price'];
                $group_products[$i]['discounted_price'] = 0;
                $group_products[$i]['gst_inclusive'] = $gst_inclusive;
            }

            for($i=0;$i<count($individual_products);$i++){

                // If scheme group products are returned, then new products are purchased at 0% discount
                if($return_products_count['group'] > 0){
                    $individual_products[$i]['discount_percent'] = 0;
                    $gst_inclusive = ($return_products_types['group'][0]['order_data']['gst_inclusive'] == 1)?1:0;
                }else{
                    // check if previous discount products are returned. If number of new products is equal to return products, then discount is previous, else single product discount
                    if($return_products_count['previous'] > 0){
                        if(count($group_products) == 0 && count($individual_products) == count($return_products)){
                            $individual_products[$i]['discount_percent'] = $previous_return_products[$i]['order_data']['discount_percent']; 
                            $gst_inclusive = $previous_return_products[$i]['order_data']['gst_inclusive'];
                        }else{
                            $individual_products[$i]['discount_percent'] = $single_product_data['discount'];
                            $gst_inclusive = ($single_product_data['gst_type'] == 'inclusive')?1:0;
                        }
                    }else{
                        // Check if individual returned products have discount of 0%, then apply 0% discount to new products, else apply single product discount.
                        if($return_products_count['individual'] > 0 && $return_products_types['individual'][0]['order_data']['discount_percent'] == 0){
                            $individual_products[$i]['discount_percent'] = 0;
                            $gst_inclusive = $return_products_types['individual'][0]['order_data']['gst_inclusive'];
                        }else{
                            // check if product have 0% discount in previous system, then add 0% discount else add single product discount
                            $individual_product_discount = Discount::where('sku',$individual_products[$i]['product_sku'])->where('is_deleted',0)->orderBy('id','DESC')->first();
                            if(!empty($individual_product_discount) && $individual_product_discount->discount_percent == 0){
                                $individual_products[$i]['discount_percent'] = 0;
                                $gst_inclusive = $individual_product_discount->gst_including;
                            }else{
                                $individual_products[$i]['discount_percent'] = $single_product_data['discount'];
                                $gst_inclusive = ($single_product_data['gst_type'] == 'inclusive')?1:0;
                            }
                        }
                    }
                }
                
                $individual_products[$i]['discount_amount'] = round($individual_products[$i]['sale_price']*($individual_products[$i]['discount_percent']/100),6);
                $individual_products[$i]['discounted_price'] = round($individual_products[$i]['sale_price']*((100-$individual_products[$i]['discount_percent'])/100),6);

                $gst_data = CommonHelper::getGSTData($individual_products[$i]['hsn_code'],$individual_products[$i]['discounted_price']);
                $individual_products[$i]['gst_percent'] = $gst_data->rate_percent;
                $gst_percent = $gst_data->rate_percent;;

                if($gst_inclusive == 1){
                    $discounted_price_orig = $discounted_price = $individual_products[$i]['discounted_price'];
                    $gst_percent_1 = 100+$gst_percent;
                    $gst_percent_1 = $gst_percent_1/100;
                    $discounted_price = round($discounted_price/$gst_percent_1,6);

                    $gst_amount = $discounted_price_orig-$discounted_price;
                    $net_price = $discounted_price_orig; 
                    $gst_inclusive = 1;
                }else{
                    $gst_amount = $individual_products[$i]['discounted_price']*($individual_products[$i]['gst_percent']/100);
                    $net_price = $individual_products[$i]['discounted_price']+$gst_amount;
                    $gst_inclusive = 0;
                }

                $individual_products[$i]['gst_amount'] = round($gst_amount,6);
                $individual_products[$i]['net_price'] = round($net_price,6);
                $individual_products[$i]['gst_inclusive'] = $gst_inclusive;
            }

            

            for($i=0;$i<count($group_products);$i++){
                $sale_price_total+=$group_products[$i]['sale_price'];
                $discount_total+=$group_products[$i]['discount_amount'];
                $gst_total+=$group_products[$i]['gst_amount'];
                $net_price_total+=$group_products[$i]['net_price'];
            }

            for($i=0;$i<count($individual_products);$i++){
                $sale_price_total+=$individual_products[$i]['sale_price'];
                $discount_total+=$individual_products[$i]['discount_amount'];
                $gst_total+=$individual_products[$i]['gst_amount'];
                $net_price_total+=$individual_products[$i]['net_price'];
            }

            for($i=0;$i<count($return_products);$i++){
                $sale_price_total-=$return_products[$i]['order_data']['sale_price'];
                $discount_total-=$return_products[$i]['order_data']['discount_amount'];
                $gst_total-=$return_products[$i]['order_data']['gst_amount'];
                $net_price_total-=$return_products[$i]['order_data']['net_price'];

                
            }
        
        }else{
            
        }

        $response_data = array('return_products'=>$return_products,
        'group_products'=>$group_products,'individual_products'=>$individual_products,'sale_price_total'=>$sale_price_total,'discount_total'=>$discount_total,
        'gst_total'=>$gst_total,'net_price_total'=>$net_price_total,'discount_group'=>$discount_group,'error_msg'=>$error_msg);

        return $response_data;
        
    }
    
    public static function sortResults($array1, $array2){
        if ($array1['order_data']['discount_percent'] == $array2['order_data']['discount_percent']) {
            return 0;
        }
        return ($array1['order_data']['discount_percent'] > $array2['order_data']['discount_percent']) ? -1 : 1;
    }
    
    //$demand_type = 1 - store,  $demand_type = 2 - vendor,  $demand_type = 3 - store to store
    public static function updateDemandTotalData($demand_id,$demand_type = 1,$return_data = 0){
        $demand_sku_list_arnon = $demand_sku_list = $sku_north = $sku_arnon = array();
       
        // store demand or vendor demand
        if($demand_type == 1 || $demand_type == 3){
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('store as s','s.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)
            ->select('spd.*','s.gst_no')->first();
        }else{
            $demand_data = \DB::table('store_products_demand as spd')
            ->join('vendor_detail as vd','vd.id', '=', 'spd.store_id')
            ->where('spd.id',$demand_id)
            ->select('spd.*','vd.gst_no')->first();
        }
        
        $company_data = CommonHelper::getCompanyData();

        // store or vendor demands
        if($demand_type == 1 || $demand_type == 2){
            if($demand_data->gst_no != $company_data['company_gst_no']){
                $gst_type = CommonHelper::getGSTType($demand_data->gst_no);
                $gst_name = ($gst_type == 1)?'s_gst':'i_gst';
            }else{
                $gst_name = '';
            }
        }
        
        // In store to store demand, all gst data is saved. $gst_name = 's_gst' is only to save all gst data
        if($demand_type == 3){
            $gst_name = 's_gst';
        }
        
        //  Northcorp inventory
        $demand_products_list = \DB::table('store_products_demand_inventory as spdi')
        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
        ->leftJoin('purchase_order_items as poi','poi.id','=','ppmi.po_item_id')        
        ->where('spdi.demand_id',$demand_id)        
        ->where('ppmi.is_deleted',0)
        ->where('ppmi.arnon_inventory',0)
        ->where('spdi.is_deleted',0)  
        ->where('ppm.is_deleted',0);
        
        if($return_data == 0){
            $demand_products_list = $demand_products_list->where('spdi.transfer_status',1);        
        }
        
        $demand_products_list = $demand_products_list->select('spdi.id as spdi_id','ppmi.id as inventory_id','ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.id as product_id','ppmi.sale_price',
        'ppm.product_name','ppm.product_sku','ppm.hsn_code','ppm.size_id','ppm.color_id','poi.vendor_sku','spdi.vendor_base_price','spdi.vendor_gst_percent','spdi.vendor_gst_amount','spdi.base_price as spdi_base_price')
        ->get()->toArray();
        
        // Update demand data which have store base rate as null. It displays error in db update query
        if($demand_type == 1 || $demand_type == 3){
            for($i=0;$i<count($demand_products_list);$i++){
               // Empty function does not returns 0.00 as empty.  check if one of price is 0
                if( !($demand_products_list[$i]->store_base_rate > 0 && $demand_products_list[$i]->store_base_price > 0) ) {
                    $prod_data = CommonHelper::getUpdatedDemandProductData($demand_products_list[$i],$gst_name);
                    $updateArray = ['store_base_rate'=>$prod_data['base_rate'],'store_gst_percent'=>$prod_data['gst_percent'],'store_gst_amount'=>$prod_data['gst_amount'],'store_base_price'=>$prod_data['base_price']];
                    Store_products_demand_inventory::where('id',$demand_products_list[$i]->spdi_id)->update($updateArray);
 
                    $demand_products_list[$i]->store_base_rate = $prod_data['base_rate'];
                    $demand_products_list[$i]->store_gst_percent = $prod_data['gst_percent'];
                }
            }
        }

        for($i=0;$i<count($demand_products_list);$i++){
            if($demand_data->invoice_type  == 'product_id'){
                $key = $demand_products_list[$i]->product_id;
            }else{
                $key = $demand_products_list[$i]->product_sku;
            }
            
            if(!isset($demand_sku_list[$key])){
                $demand_sku_list[$key] = array('prod'=>$demand_products_list[$i],'qty'=>1);
            }else{
                $demand_sku_list[$key]['qty']+= 1;
            }
        }

        // Arnon Inventory
        $demand_products_list_arnon = \DB::table('store_products_demand_inventory as spdi')
        ->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')        
        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
        ->where('spdi.demand_id',$demand_id)        
        ->where('ppmi.is_deleted',0)
        ->where('ppmi.arnon_inventory',1)
        ->where('spdi.is_deleted',0)  
        ->where('ppm.is_deleted',0);
        
        if($return_data == 0){
            $demand_products_list_arnon = $demand_products_list_arnon->where('spdi.transfer_status',1);        
        }     
        
        $demand_products_list_arnon = $demand_products_list_arnon->select('spdi.id as spdi_id','ppmi.id as inventory_id','ppmi.base_price','spdi.store_base_price','spdi.store_base_rate','spdi.store_gst_percent','spdi.store_gst_amount','ppm.id as product_id','ppmi.sale_price',
        'ppm.product_name','ppm.product_sku','ppm.hsn_code','spdi.vendor_base_price','spdi.vendor_gst_percent','spdi.vendor_gst_amount','spdi.base_price as spdi_base_price')
        ->get()->toArray();
        
        // Update demand data which have store base rate as null. It displays error in db update query
        if($demand_type == 1 || $demand_type == 3){
            for($i=0;$i<count($demand_products_list_arnon);$i++){
                // Check if one of price is 0
                if(!($demand_products_list_arnon[$i]->store_base_rate > 0 && $demand_products_list_arnon[$i]->store_base_price > 0) ) {
                    $prod_data = CommonHelper::getUpdatedDemandProductData($demand_products_list_arnon[$i],$gst_name);
                    $updateArray = ['store_base_rate'=>$prod_data['base_rate'],'store_gst_percent'=>$prod_data['gst_percent'],'store_gst_amount'=>$prod_data['gst_amount'],'store_base_price'=>$prod_data['base_price']];
                    Store_products_demand_inventory::where('id',$demand_products_list_arnon[$i]->spdi_id)->update($updateArray);
                    
                    $demand_products_list_arnon[$i]->store_base_rate = $prod_data['base_rate'];
                    $demand_products_list_arnon[$i]->store_gst_percent = $prod_data['gst_percent'];
                }
            }
        }

        for($i=0;$i<count($demand_products_list_arnon);$i++){
            if($demand_data->invoice_type  == 'product_id' ){    
                $key = $demand_products_list_arnon[$i]->product_id;
            }else{
                $key = $demand_products_list_arnon[$i]->product_sku;
            }
            
            if(!isset($demand_sku_list_arnon[$key])){
                $demand_sku_list_arnon[$key] = array('prod'=>$demand_products_list_arnon[$i],'qty'=>1);
            }else{
                $demand_sku_list_arnon[$key]['qty']+= 1;
            }
        }
        
        $sku_north = json_decode(json_encode($demand_sku_list),true);
        $sku_arnon = json_decode(json_encode($demand_sku_list_arnon),true);

        $total_qty = $total_taxable_val = $total_gst_amt = $total_value = $total_sale_price =  0;
        $gst_data = $hsn_data = $gst_data_hsn = $gst_data_hsn_1 = $hsn_data_1 = array();

        foreach($demand_sku_list as $key=>$product_data){
            $hsn_code  = $product_data['prod']->hsn_code;
            if($demand_type == 1 || $demand_type == 3){
                $taxable_val = round($product_data['prod']->store_base_rate*$product_data['qty'],6);
                $gst_percent = ($gst_name != '')?$product_data['prod']->store_gst_percent:0;
            }else{
                $taxable_val = round($product_data['prod']->vendor_base_price*$product_data['qty'],6);
                $gst_percent = ($gst_name != '')?$product_data['prod']->vendor_gst_percent:0;
            }
            
            $gst_amount = ($gst_name != '')?round($taxable_val*($gst_percent/100),6):0;
            
            $total_qty+=$product_data['qty']; 
            $total_taxable_val+=$taxable_val; 
            $total_gst_amt+=$gst_amount; 
            $value = $taxable_val+$gst_amount;
            $total_value+=$value; 
            $total_sale_price+=$product_data['prod']->sale_price; 

            $gst_data[$gst_percent]['taxable_value'] = (!isset($gst_data[$gst_percent]['taxable_value']))?$taxable_val:$gst_data[$gst_percent]['taxable_value']+$taxable_val; 
            $gst_data[$gst_percent]['gst_amount'] = (!isset($gst_data[$gst_percent]['gst_amount']))?$gst_amount:$gst_data[$gst_percent]['gst_amount']+$gst_amount; 
            $gst_data[$gst_percent]['qty'] = (!isset($gst_data[$gst_percent]['qty']))?$product_data['qty']:$gst_data[$gst_percent]['qty']+$product_data['qty'];

            $key = $hsn_code.'_'.str_replace('.00','',$gst_percent);
            $gst_data_hsn[$key]['taxable_value'] = (!isset($gst_data_hsn[$key]['taxable_value']))?$taxable_val:$gst_data_hsn[$key]['taxable_value']+$taxable_val; 
            $gst_data_hsn[$key]['gst_amount'] = (!isset($gst_data_hsn[$key]['gst_amount']))?$gst_amount:$gst_data_hsn[$key]['gst_amount']+$gst_amount; 
            $gst_data_hsn[$key]['qty'] = (!isset($gst_data_hsn[$key]['qty']))?$product_data['qty']:$gst_data_hsn[$key]['qty']+$product_data['qty'];

            if(isset($hsn_data[$hsn_code])){
                $hsn_data[$hsn_code]['total_qty']+=$product_data['qty'];
                $hsn_data[$hsn_code]['total_taxable_val']+=$taxable_val;
                $hsn_data[$hsn_code]['total_gst_amt']+=$gst_amount;
                $hsn_data[$hsn_code]['total_value']+=$value;
            }else{
                $hsn_data[$hsn_code]['total_qty'] = $product_data['qty'];
                $hsn_data[$hsn_code]['total_taxable_val'] = $taxable_val;
                $hsn_data[$hsn_code]['total_gst_amt'] = $gst_amount;
                $hsn_data[$hsn_code]['total_value'] = $value;
            }
            
            // Data to display at bottom of pdf invoice
            if($gst_percent == 0){
                $gst_percent = CommonHelper::getGSTData($product_data['prod']->hsn_code,$product_data['prod']->store_base_rate);
                $gst_percent = $gst_percent->rate_percent;
            }
               
            $gst_amount = round($taxable_val*($gst_percent/100),6);

            $key = $hsn_code.'_'.str_replace('.00','',$gst_percent);
            $gst_data_hsn_1[$key]['taxable_value'] = (!isset($gst_data_hsn_1[$key]['taxable_value']))?$taxable_val:$gst_data_hsn_1[$key]['taxable_value']+$taxable_val; 
            $gst_data_hsn_1[$key]['gst_amount'] = (!isset($gst_data_hsn_1[$key]['gst_amount']))?$gst_amount:$gst_data_hsn_1[$key]['gst_amount']+$gst_amount; 
            $gst_data_hsn_1[$key]['qty'] = (!isset($gst_data_hsn_1[$key]['qty']))?$product_data['qty']:$gst_data_hsn_1[$key]['qty']+$product_data['qty'];

            if(isset($hsn_data_1[$hsn_code])){
                $hsn_data_1[$hsn_code]['total_qty']+=$product_data['qty'];
                $hsn_data_1[$hsn_code]['total_taxable_val']+=$taxable_val;
                $hsn_data_1[$hsn_code]['total_gst_amt']+=$gst_amount;
                $hsn_data_1[$hsn_code]['total_value']+=$value;
            }else{
                $hsn_data_1[$hsn_code]['total_qty'] = $product_data['qty'];
                $hsn_data_1[$hsn_code]['total_taxable_val'] = $taxable_val;
                $hsn_data_1[$hsn_code]['total_gst_amt'] = $gst_amount;
                $hsn_data_1[$hsn_code]['total_value'] = $value;
            }
        }    

        foreach($demand_sku_list_arnon as $key=>$product_data){
            $hsn_code  = $product_data['prod']->hsn_code;
            if($demand_type == 1 || $demand_type == 3){
                $taxable_val = round($product_data['prod']->store_base_rate*$product_data['qty'],6);
                $gst_percent = ($gst_name != '')?$product_data['prod']->store_gst_percent:0;
            }else{
                $taxable_val = round($product_data['prod']->vendor_base_price*$product_data['qty'],6);
                $gst_percent = ($gst_name != '')?$product_data['prod']->vendor_gst_percent:0;
            }
            
            $gst_amount = ($gst_name != '')?round($taxable_val*($gst_percent/100),6):0;
            
            $total_qty+=$product_data['qty']; 
            $total_taxable_val+=$taxable_val; 
            $total_gst_amt+=$gst_amount; 
            $value = $taxable_val+$gst_amount;
            $total_value+=$value; 
            $total_sale_price+=$product_data['prod']->sale_price;; 

            $gst_data[$gst_percent]['taxable_value'] = (!isset($gst_data[$gst_percent]['taxable_value']))?$taxable_val:$gst_data[$gst_percent]['taxable_value']+$taxable_val; 
            $gst_data[$gst_percent]['gst_amount'] = (!isset($gst_data[$gst_percent]['gst_amount']))?$gst_amount:$gst_data[$gst_percent]['gst_amount']+$gst_amount; 
            $gst_data[$gst_percent]['qty'] = (!isset($gst_data[$gst_percent]['qty']))?$product_data['qty']:$gst_data[$gst_percent]['qty']+$product_data['qty'];

            $key = $hsn_code.'_'.str_replace('.00','',$gst_percent);
            $gst_data_hsn[$key]['taxable_value'] = (!isset($gst_data_hsn[$key]['taxable_value']))?$taxable_val:$gst_data_hsn[$key]['taxable_value']+$taxable_val; 
            $gst_data_hsn[$key]['gst_amount'] = (!isset($gst_data_hsn[$key]['gst_amount']))?$gst_amount:$gst_data_hsn[$key]['gst_amount']+$gst_amount; 
            $gst_data_hsn[$key]['qty'] = (!isset($gst_data_hsn[$key]['qty']))?$product_data['qty']:$gst_data_hsn[$key]['qty']+$product_data['qty'];

            if(isset($hsn_data[$hsn_code])){
                $hsn_data[$hsn_code]['total_qty']+=$product_data['qty'];
                $hsn_data[$hsn_code]['total_taxable_val']+=$taxable_val;
                $hsn_data[$hsn_code]['total_gst_amt']+=$gst_amount;
                $hsn_data[$hsn_code]['total_value']+=$value;
            }else{
                $hsn_data[$hsn_code]['total_qty'] = $product_data['qty'];
                $hsn_data[$hsn_code]['total_taxable_val'] = $taxable_val;
                $hsn_data[$hsn_code]['total_gst_amt'] = $gst_amount;
                $hsn_data[$hsn_code]['total_value'] = $value;
            }
            
            // Data to display at bottom of invoice
            if($gst_percent == 0){
                $gst_percent = CommonHelper::getGSTData($product_data['prod']->hsn_code,$product_data['prod']->store_base_rate);
                $gst_percent = $gst_percent->rate_percent;
            }    
            
            $gst_amount = round($taxable_val*($gst_percent/100),6);

            $key = $hsn_code.'_'.str_replace('.00','',$gst_percent);
            $gst_data_hsn_1[$key]['taxable_value'] = (!isset($gst_data_hsn_1[$key]['taxable_value']))?$taxable_val:$gst_data_hsn_1[$key]['taxable_value']+$taxable_val; 
            $gst_data_hsn_1[$key]['gst_amount'] = (!isset($gst_data_hsn_1[$key]['gst_amount']))?$gst_amount:$gst_data_hsn_1[$key]['gst_amount']+$gst_amount; 
            $gst_data_hsn_1[$key]['qty'] = (!isset($gst_data_hsn_1[$key]['qty']))?$product_data['qty']:$gst_data_hsn_1[$key]['qty']+$product_data['qty'];

            if(isset($hsn_data_1[$hsn_code])){
                $hsn_data_1[$hsn_code]['total_qty']+=$product_data['qty'];
                $hsn_data_1[$hsn_code]['total_taxable_val']+=$taxable_val;
                $hsn_data_1[$hsn_code]['total_gst_amt']+=$gst_amount;
                $hsn_data_1[$hsn_code]['total_value']+=$value;
            }else{
                $hsn_data_1[$hsn_code]['total_qty'] = $product_data['qty'];
                $hsn_data_1[$hsn_code]['total_taxable_val'] = $taxable_val;
                $hsn_data_1[$hsn_code]['total_gst_amt'] = $gst_amount;
                $hsn_data_1[$hsn_code]['total_value'] = $value;
            }
        }    

        $array = $array_gsn = $array_gsn_1 = array();
        
        ksort($gst_data);
        foreach($gst_data as $gst_percent=>$data){
            $gst_percent = str_replace('.00','',$gst_percent);

            $array['taxable_value_'.$gst_percent] = $data['taxable_value'];
            $array['gst_amount_'.$gst_percent] = $data['gst_amount'];
            $array['qty_'.$gst_percent] = $data['qty'];
        }

        $array['total_qty'] = $total_qty;
        $array['total_taxable_val'] = $total_taxable_val;
        $array['total_gst_amt'] = $total_gst_amt;
        $array['total_value'] = $total_value;
        $array['total_sale_price'] = $total_sale_price;

        ksort($gst_data_hsn);
        foreach($gst_data_hsn as $key=>$data){
            $array_gsn['taxable_value_'.$key] = $data['taxable_value'];
            $array_gsn['gst_amount_'.$key] = $data['gst_amount'];
            $array_gsn['qty_'.$key] = $data['qty'];
        }
        
        $array_gsn['total_data'] = $hsn_data;
        
        ksort($gst_data_hsn_1);
        foreach($gst_data_hsn_1 as $key=>$data){
            $array_gsn_1['taxable_value_'.$key] = $data['taxable_value'];
            $array_gsn_1['gst_amount_'.$key] = $data['gst_amount'];
            $array_gsn_1['qty_'.$key] = $data['qty'];
        }
        
        $array_gsn_1['total_data'] = $hsn_data_1;
        
        if($return_data == 1){
            return array('total_data'=> $array,'total_data_hsn'=>$array_gsn,'total_data_hsn_1'=>$array_gsn_1);
        }

        $updateArray = array('total_data'=> json_encode($array),'total_data_hsn'=>json_encode($array_gsn),'total_data_hsn_1'=>json_encode($array_gsn_1));
        Store_products_demand::where('id',$demand_id)->update($updateArray);
    }
    
    public static function getUpdatedDemandProductData($prod_data,$gst_name){
        
        if( !($prod_data->store_base_rate > 0) && ($prod_data->store_base_price > 0)){
            $base_price = $prod_data->store_base_price;
            
            if($gst_name == ''){
                $base_rate = $base_price;
                $gst_percent = $gst_amount = 0;
            }else{
                $gst_percent = ($base_price > 1049)?12:5;

                $base_price_orig = $base_price;
                $gst_percent_1 = 100+$gst_percent;
                $gst_percent_1 = $gst_percent_1/100;
                $base_price_1 = round($base_price/$gst_percent_1,2);
                $gst_amount = $base_price_orig-$base_price_1;

                $base_rate = $base_price-$gst_amount;
                $gst_percent = $gst_percent;
            }
        }elseif(($prod_data->store_base_rate > 0) && !($prod_data->store_base_price > 0)){
            $base_rate = $prod_data->store_base_rate;
            
            if($gst_name == ''){
                $base_price = $base_rate;
                $gst_percent = $gst_amount = 0;
            }else{
                $gst_percent = CommonHelper::getGSTPercent($base_rate);
                $gst_amount = round($base_rate*($gst_percent/100),2);
                $base_price = $base_rate+$gst_amount;
            }
        }elseif(!($prod_data->store_base_rate > 0 && $prod_data->store_base_price > 0)){
            $base_rate = $prod_data->base_price;
            if($gst_name == ''){
                $base_price = $base_rate;
                $gst_percent = $gst_amount = 0;
            }else{
                $gst_percent = CommonHelper::getGSTPercent($base_rate);
                $gst_amount = round($base_rate*($gst_percent/100),2);
                $base_price = $base_rate+$gst_amount;
            }
        }
        
        return ['base_rate'=>$base_rate,'gst_percent'=>$gst_percent,'gst_amount'=>$gst_amount,'base_price'=>$base_price];
    }
    
    public static function updateStoreInventoryBalance(){
        $date = date('Y/m/d');
        if(date('H') < 22){
            echo 'Invalid date time';
            exit();
        }
        
        $record_exists = Store_inventory_balance::where('inv_date',$date)->where('is_deleted',0)->first();
        if(!empty($record_exists)){
            echo 'Record exists';
            exit();
        }
        
        $store_inv = Pos_product_master_inventory::where('product_status',4)
        ->where('is_deleted',0)
        ->groupBy('store_id')                        
        ->selectRaw('store_id,COUNT(id) as inv_count,SUM(sale_price) as inv_value')
        ->orderBy('store_id')        
        ->get()->toArray();
        
        for($i=0;$i<count($store_inv);$i++){
            $insertArray = array('inv_date'=>$date,'record_type'=>1,'store_id'=>$store_inv[$i]['store_id'],'category_id'=>null,'bal_qty'=>$store_inv[$i]['inv_count'],'bal_value'=>$store_inv[$i]['inv_value']);
            Store_inventory_balance::create($insertArray);
        }
        
        $store_cat_inv = \DB::table('pos_product_master_inventory as ppmi')
        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')
        ->where('ppmi.product_status',4)
        ->where('ppmi.is_deleted',0)
        ->groupByRaw('ppmi.store_id,ppm.category_id')                        
        ->selectRaw('ppmi.store_id,ppm.category_id,COUNT(ppmi.id) as inv_count,SUM(ppmi.sale_price) as inv_value')
        ->orderByRaw('ppmi.store_id,ppm.category_id')               
        ->get()->toArray();
        
        for($i=0;$i<count($store_cat_inv);$i++){
            $insertArray = array('inv_date'=>$date,'record_type'=>2,'store_id'=>$store_cat_inv[$i]->store_id,'category_id'=>$store_cat_inv[$i]->category_id,'bal_qty'=>$store_cat_inv[$i]->inv_count,'bal_value'=>$store_cat_inv[$i]->inv_value);
            Store_inventory_balance::create($insertArray);
        }
    }
    
    public static function isFakeInventoryUser(){
        $user = Auth::user();
        if($user->user_type == 18){
            return true;
        }else{
            return false;
        }
    }
    
    public static function viewModifiedInventoryUser(){
        $user = Auth::user();
        if($user->is_view_modified_inv == 1){
            return true;
        }else{
            return false;
        }
    }
    
    public static function getPosOrderStatusText($order_status){
        $order_status_array = array('1'=>'Completed','2'=>'Pending','3'=>'Cancelled');
        return isset($order_status_array[$order_status])?$order_status_array[$order_status]:'Invalid Order Status';
    }
    
    public static function getPosOrderNo($store_data){
        $last_order =  Pos_customer_orders::where('store_id',$store_data->id)->whereRaw('YEAR(created_at) = YEAR(CURDATE())')->where('invoice_series_type',2)->orderBy('order_no','DESC')->first();

        //$order_no = (!empty($last_order) && strlen($last_order->order_no) == 12)?ltrim(substr($last_order->order_no,6),0):0;
        
        $order_no = (!empty($last_order) && strlen($last_order->order_no) >= 12)?substr($last_order->order_no,-6):0;        
        $order_no = 'K'.$store_data->store_code.date('y').str_pad(($order_no+1),6,'0',STR_PAD_LEFT);
        
        return $order_no;
    }
    
    public static function getDemandGSNData($demand_data,$gst_name){
        $str = '';
        
        //if($gst_name != ''){
            if($demand_data->id >= 2503){
                $total_data_hsn = (!empty($demand_data->total_data_hsn_1))?json_decode($demand_data->total_data_hsn_1,true):array();
            }else{
                $total_data_hsn = (!empty($demand_data->total_data_hsn))?json_decode($demand_data->total_data_hsn,true):array();
            }
            
            if(isset($total_data_hsn['total_data']) && !empty($total_data_hsn['total_data'])){
                $hsn_codes = array_keys($total_data_hsn['total_data']);
                $gst_types = [0,3,5,12,18]; $total_data = array('qty'=>0,'taxable_amt'=>0,'gst_amount'=>0);
                $str = '<tr><td class="border-top border-bottom text-4" colspan="2">HSN Code</td><td class="border-top border-bottom text-4">GST</td><td class="border-top border-bottom text-4">Qty</td><td class="border-top border-bottom text-4"  colspan="2">Taxable Amount</td><td class="border-top border-bottom text-4" >Unit Price</td><td class="border-top border-bottom text-4">CGST %</td><td class="border-top border-bottom text-4">Amount</td><td class="border-top border-bottom text-4">SGST %</td><td class="border-top border-bottom text-4">Amount</td><td class="border-top border-bottom text-4">IGST %</td><td class="border-top border-bottom text-4">Amount</td><td class="border-top border-bottom text-4" colspan="2">Tax Amount</td></tr>';

                for($i=0;$i<count($hsn_codes);$i++){
                    for($q=0;$q<count($gst_types);$q++){
                        $hsn_code = $hsn_codes[$i];
                        $gst = $gst_types[$q];
                        $key = $hsn_code.'_'.$gst;
                        if(isset($total_data_hsn['taxable_value_'.$key])){
                            $str.='<tr>';
                            $str.='<td class="border-bottom text-4" colspan="2">'.$hsn_code.'</td><td class="border-bottom text-4">'.$gst.'%</td><td class="border-bottom text-4">'.$total_data_hsn['qty_'.$key].'</td>';
                            $str.='<td class="border-bottom text-4" colspan="2">'.$total_data_hsn['taxable_value_'.$key].'</td><td class="border-bottom text-4" >'.round($total_data_hsn['taxable_value_'.$key]/$total_data_hsn['qty_'.$key],2).'</td>';
                            if($gst_name == 's_gst'){
                                $str.='<td class="border-bottom text-4">'.round($gst/2,2).'</td><td class="border-bottom text-4">'.round($total_data_hsn['gst_amount_'.$key]/2,2).'</td><td class="border-bottom text-4">'.round($gst/2,2).'</td><td class="border-bottom text-4">'.round($total_data_hsn['gst_amount_'.$key]/2,2).'</td>';
                                $str.='<td class="border-bottom text-4"></td><td class="border-bottom text-4"></td>';
                            }elseif($gst_name == 'i_gst' || $gst_name == ''){
                                $str.='<td class="border-bottom text-4"></td><td class="border-bottom text-4"></td><td class="border-bottom text-4"></td><td class="border-bottom text-4"></td>';
                                $str.='<td class="border-bottom text-4">'.$gst.'</td><td class="border-bottom text-4">'.round($total_data_hsn['gst_amount_'.$key],2).'</td>';
                            }

                            $str.='<td class="border-bottom text-4" colspan="2">'.round($total_data_hsn['gst_amount_'.$key],2).'</td></tr>';

                            $total_data['qty']+=$total_data_hsn['qty_'.$key];
                            $total_data['taxable_amt']+=$total_data_hsn['taxable_value_'.$key];
                            $total_data['gst_amount']+=$total_data_hsn['gst_amount_'.$key];
                        }
                    }
                } 

                $str.='<tr><td colspan="3" class="border-bottom text-4">Total</td><td class="border-bottom text-4">'.$total_data['qty'].'</td><td colspan="3" class="border-bottom text-4">'.$total_data['taxable_amt'].'</td>';
                if($gst_name == 's_gst'){
                    $str.='<td class="border-bottom text-4"></td><td class="border-bottom text-4">'.round($total_data['gst_amount']/2,2).'</td><td class="border-bottom text-4"></td><td class="border-bottom text-4">'.round($total_data['gst_amount']/2,2).'</td>';
                    $str.='<td class="border-bottom text-4"></td><td class="border-bottom text-4"></td>';
                }elseif($gst_name == 'i_gst' || $gst_name == ''){
                    $str.='<td class="border-bottom text-4"></td><td class="border-bottom text-4"></td><td class="border-bottom text-4"></td><td class="border-bottom text-4"></td>';
                    $str.='<td class="border-bottom text-4"></td><td class="border-bottom text-4">'.round($total_data['gst_amount'],2).'</td>';
                }
                $str.='<td class="border-bottom text-4" colspan="2">'.round($total_data['gst_amount'],2).'</td></tr>';
            }
        //}
        
        return $str;
    }
    
    public static function convertUserDateToDBDate($date){
        $date = str_replace('-','/',trim($date));
        $date_arr = explode('/',$date);
        $date = $date_arr[2].'/'.$date_arr[1].'/'.$date_arr[0];
        
        return $date;
    }
    
    public static function importInventoryBarcodes($request,$data){
        $error_msg = $dest_folder = $file_name =  '';
        $barcodes = $barcodes_updated = array();
        
        // validations
        $validationRules = array('barcodeTxtFile'=>'required|mimes:txt|max:3072');
        $attributes = array('barcodeTxtFile'=>'Barcode File');

        $validator = Validator::make($data,$validationRules,array(),$attributes);
        if ($validator->fails()){ 
            $error_msg = $validator->errors();
        }	

        if(empty($error_msg)){
            // Get file data
            $file = $request->file('barcodeTxtFile');
            $file_name_text = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(),'.'));
            $file_ext = $file->getClientOriginalExtension();
            $dest_folder = 'documents/product_barcode_txt';

            $file_name = $file_name_text.'_'.rand(1000,1000000).'.'.$file_ext;
            $file->move(public_path($dest_folder), $file_name);
            $barcodes = file(public_path($dest_folder).'/'.$file_name);

            // maximum allowed barcodes is 300 validation
            if(count($barcodes) > 300){
                $error_msg = 'Maximum 300 products can be imported';
            }
            
            if(empty($error_msg)){
                for($i=0;$i<count($barcodes);$i++){
                    if(!empty(trim($barcodes[$i]))){
                        $barcodes_updated[$i] = substr(trim($barcodes[$i]),0,25);
                    }
                }

                $barcodes = array_values(array_unique($barcodes_updated));
            }
        }
        
        return ['barcodes'=>$barcodes,'error_msg'=>$error_msg,'dest_folder'=>$dest_folder,'file_name'=>$file_name];
    }
    
    public static function getProductNetPriceData($product_data,$store_data){
        
        $discount_data = CommonHelper::getdiscount($product_data['peice_barcode'],$product_data);
        
        if($discount_data['status'] == 'success' && isset($discount_data['data']['discount_type']) && $discount_data['data']['discount_type'] == 1){
            $discount_percent = $discount_data['data']['discount_percent'];
            $gst_inclusive = $discount_data['data']['gst_including'];
            $discount_id = $discount_data['data']['id'];
        }elseif($discount_data['status'] == 'success' && isset($discount_data['data']['discount_type']) && $discount_data['data']['discount_type'] == 2){
            $flat_price = $discount_data['data']['flat_price'];
            $discount_percent = round(100-($flat_price/$product_data['sale_price'])*100,2);
            $gst_inclusive = $discount_data['data']['gst_including'];
            $discount_id = $discount_data['data']['id'];
        }else{
            $discount_percent = CommonHelper::getPosDiscountPercent();
            $discount_id = 0;
            if(isset($store_data->gst_type) && !empty($store_data->gst_type)){
                $store_gst_inclusive = ($store_data->gst_type == 'inclusive')?1:0;
            }else{
                $store_gst_inclusive = null;
            }
            $gst_inclusive = ($store_gst_inclusive !== null)?$store_gst_inclusive:$product_data['gst_inclusive'];
        }

        // Override gst inclusive is defined at store level. default, inclusive or not_inclusive
        if(isset($store_data->gst_type) && !empty($store_data->gst_type)){
            if(strtolower($store_data->gst_type) == 'inclusive')  $gst_inclusive = 1;
            if(strtolower($store_data->gst_type) == 'not_inclusive')  $gst_inclusive = 0;
        }

        $discount_price = ($discount_percent > 0)?($product_data['sale_price']*($discount_percent/100)):0;
        $discounted_price = round($product_data['sale_price']-$discount_price,2);

        if((isset($store_data->gst_applicable) && $store_data->gst_applicable == 1) ){
            $gst_data = CommonHelper::getGSTData($product_data['hsn_code'],$discounted_price);
            $gst_percent = (!empty($gst_data))?$gst_data->rate_percent:0;
        }else{
            $gst_percent = 0;
        }
        
        $discount_amount = (empty($discount_percent))?0:round(($product_data['sale_price']*($discount_percent/100)),6);
        $discounted_price = $discounted_price_1 = $product_data['sale_price']-$discount_amount;

        if($gst_inclusive == 1){
            $discounted_price_orig = $discounted_price;
            $gst_percent_1 = 100+$gst_percent;
            $gst_percent_1 = $gst_percent_1/100;
            $discounted_price = round($discounted_price/$gst_percent_1,6);

            $gst_amount = $discounted_price_orig-$discounted_price;
            $net_price = $discounted_price_orig; 
        }else{
            $gst_amount = (!empty($gst_percent))?round($discounted_price*($gst_percent/100),6):0;
            $net_price = $discounted_price+$gst_amount;
        }
        
        $discount_amount_actual = ($gst_inclusive == 1)?round($discount_amount+$gst_amount,3):round($discount_amount,6);
        $discount_percent_actual = ($gst_inclusive == 1)?round(($discount_amount_actual/$product_data['sale_price'])*100,6):$discount_percent;
        $discounted_price_actual = ($gst_inclusive == 1)?round($discounted_price_1-$gst_amount,3):round($discounted_price_1,6);
        
        return ['discount_percent_actual'=>$discount_percent_actual,'net_price'=>$net_price,'discount_percent'=>$discount_percent,'gst_inclusive'=>$gst_inclusive];
    }
    
    public static function getSKUList(){
        $sku_list = \DB::table('pos_product_master_inventory as ppmi')
        ->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
        ->where('ppmi.is_deleted',0)
        ->where('ppm.is_deleted',0)        
        ->where('ppmi.fake_inventory',0)
        ->where('ppm.fake_inventory',0);
        
        return $sku_list;
    }
    
    public static function getWHToStoreList($demand_statuses = ['warehouse_dispatched','store_loading','store_loaded']){
        $wh_to_store_list = \DB::table('store_products_demand_inventory as spdi')
        //->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')           
        //->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
        ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')       
        ->where('spd.demand_type','inventory_push')    
        ->wherein('spd.demand_status',$demand_statuses)   
        ->whereRaw("(spd.push_demand_id IS NULL OR spd.push_demand_id = 0 OR spd.push_demand_id = '')")        
        ->where('spd.is_deleted',0)               
        ->where('spdi.is_deleted',0)     
        ->where('spdi.demand_status',1)        
        //->where('ppmi.is_deleted',0)
        //->where('ppm.is_deleted',0)        
        ->where('spdi.fake_inventory',0)
        //->where('ppmi.fake_inventory',0)
        //->where('ppm.fake_inventory',0)        
        ->where('spd.fake_inventory',0);           
        
        return $wh_to_store_list;
    }
    
    public static function getStoreToWHList($demand_statuses = ['warehouse_dispatched','warehouse_loading','warehouse_loaded']){
        $store_to_wh_list = \DB::table('store_products_demand_inventory as spdi')
        //->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')           
        //->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')   
        ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')       
        ->where('spd.demand_type','inventory_return_to_warehouse')    
        ->wherein('spd.demand_status',$demand_statuses)        
        ->where('spdi.is_deleted',0)    
        ->where('spdi.demand_status',1)        
        //->where('ppmi.is_deleted',0)
        //->where('ppm.is_deleted',0)        
        ->where('spdi.fake_inventory',0)
        //->where('ppmi.fake_inventory',0)
        //->where('ppm.fake_inventory',0)        
        ->where('spd.fake_inventory',0);                   
        
        return $store_to_wh_list;
    }
    
    public static function getStoreToWhCompleteList($demand_statuses = ['warehouse_dispatched','store_loaded']){
        $store_to_wh_comp_list = \DB::table('store_products_demand_inventory as spdi')
        //->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')           
        ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id') 
        //->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')         
        ->where('spd.demand_type','inventory_return_complete')        
        ->wherein('spd.demand_status',$demand_statuses)    
        //->where('ppmi.is_deleted',0)
        ->where('spdi.is_deleted',0)    
        ->where('spdi.demand_status',1)               
        ->where('spd.is_deleted',0)
        //->where('ppm.is_deleted',0)           
        ->where('spdi.fake_inventory',0)
        //->where('ppmi.fake_inventory',0)
        //->where('ppm.fake_inventory',0)        
        ->where('spd.fake_inventory',0);                   
        
        return $store_to_wh_comp_list;
    }
    
    public static function getStoreToStoreList($demand_statuses = ['loaded','store_loading','store_loaded']){
        $store_to_store_list = \DB::table('store_products_demand_inventory as spdi')
        //->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')                
        ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
        //->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')        
        ->where('spd.demand_type','inventory_transfer_to_store')      
        ->wherein('spd.demand_status',$demand_statuses)            
        //->where('ppmi.is_deleted',0)
        ->where('spdi.is_deleted',0)    
        ->where('spdi.demand_status',1)               
        ->where('spd.is_deleted',0)
        //->where('ppm.is_deleted',0)           
        ->where('spdi.fake_inventory',0)
        //->where('ppmi.fake_inventory',0)
        //->where('ppm.fake_inventory',0)        
        ->where('spd.fake_inventory',0);    
        
        return $store_to_store_list;
    }
    
    public static function getWHToVendorList($demand_statuses = ['warehouse_dispatched']){
        $wh_to_vendor_list = \DB::table('store_products_demand_inventory as spdi')
        //->join('pos_product_master_inventory as ppmi','ppmi.id', '=', 'spdi.inventory_id')                  
        ->join('store_products_demand as spd','spd.id', '=', 'spdi.demand_id')   
        //->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')            
        ->where('spd.demand_type','inventory_return_to_vendor')        
        ->wherein('spd.demand_status',$demand_statuses)        
        //->where('ppmi.is_deleted',0)
        ->where('spdi.is_deleted',0)    
        ->where('spdi.demand_status',1)               
        ->where('spd.is_deleted',0)
        //->where('ppm.is_deleted',0)           
        ->where('spdi.fake_inventory',0)
        //->where('ppmi.fake_inventory',0)
        //->where('ppm.fake_inventory',0)        
        ->where('spd.fake_inventory',0);    
        
        return $wh_to_vendor_list;
    }
    
    public static function getPosOrdersList(){
        $pos_orders_list = \DB::table('pos_customer_orders_detail as pcod')
        //->join('pos_product_master_inventory as ppmi','pcod.inventory_id', '=', 'ppmi.id')           
        ->join('pos_customer_orders as pco','pco.id', '=', 'pcod.order_id')    
        //->join('pos_product_master as ppm','ppm.id', '=', 'ppmi.product_master_id')           
        //->where('ppmi.is_deleted',0)
        //->where('ppm.is_deleted',0)           
        ->where('pco.is_deleted',0)        
        ->where('pcod.is_deleted',0)
        ->where('pco.order_status',1)
        ->where('pcod.order_status',1)
        ->where('pco.fake_inventory',0)
        //->where('ppmi.fake_inventory',0)
        //->where('ppm.fake_inventory',0)        
        ->where('pcod.fake_inventory',0);  
        
        return $pos_orders_list;
    }
    
    public static function getReturnDemandCreditNoteNo(){
        $invoice_no = '';  
        $financial_year = CommonHelper::getFinancialYear(date('Y/m/d'));

        $store_demand = Store_products_demand::wherein('demand_type',array('inventory_return_complete','inventory_return_to_warehouse'))->whereRaw("(credit_invoice_no != '' && credit_invoice_no IS NOT NULL)")->where('invoice_series_type',2)->select('credit_invoice_no')->orderBy('credit_invoice_no','DESC')->first();
        
        if(!empty($store_demand) && !empty($store_demand->credit_invoice_no)){
            $invoice_financial_year = substr($store_demand->credit_invoice_no,3,4);
            $invoice_no = ($invoice_financial_year == $financial_year)?substr($store_demand->credit_invoice_no,7):0;
        }else{
            $invoice_no = 0;
        }
        
        $store_demand_debit_note = Debit_notes::whereRaw("(credit_note_no != '' && credit_note_no IS NOT NULL)")->where('debit_note_type','less_inventory_from_warehouse_to_store_in_push_demand')->where('invoice_series_type',2)->orderBy('credit_note_no','DESC')->first();
        if(!empty($store_demand_debit_note) && !empty($store_demand_debit_note->credit_note_no)){
            $invoice_financial_year = substr($store_demand_debit_note->credit_note_no,3,4);
            $invoice_no_credit_note = ($invoice_financial_year == $financial_year)?substr($store_demand_debit_note->credit_note_no,7):0;
        }else{
            $invoice_no_credit_note = 0;
        }
        
        $invoice_no = max(array($invoice_no,$invoice_no_credit_note));
        
        $invoice_no = 'K'.'CN'.$financial_year.str_pad($invoice_no+1, 4, "0", STR_PAD_LEFT);  
        
        return $invoice_no;
    }
    
    public static function displayDownloadDialogHtml($total,$limit,$url,$dialog_title,$select_title = ''){
        $option_str = '';
        
        for($i=0;$i<=$total;$i=$i+$limit){ 
            $start = $i+1; $end = $i+$limit;
            $end = ($end < $total)?$end:$total;
            $option_str.='<option value="'.$start.'_'.$end.'">'.$start.' - '.$end.'</option>';
        }
                                        
        $str = '<div class="modal fade" id="downloadReportDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">'.$dialog_title.'</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="'.asset('images/close.png').'" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadReportErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadReportSuccessMessage"></div>
                
                <form method="post" name="downloadReportForm" id="downloadReportForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>'.$select_title.' </label>
                                <select name="report_rec_count" id="report_rec_count" class="form-control" >
                                    <option value="">-- '.$select_title.'  --</option>'.$option_str.'
                                </select>
                                <div class="invalid-feedback" id="error_validation_report_rec_count"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                
                <div class="modal-footer center-footer">
                    <div id="downloadReport_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button name="downloadReportCancel" id="downloadReportCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="downloadReportBtn" id="downloadReportBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadReportData(\''.$url.'\');">Download</button>
                </div>
            </div>
        </div>
        </div>';
        
        return $str;
    }
    
    public static function displayDownloadDialogButton($title = 'Report'){
        return '<a href="javascript:;" onclick="downloadReportData();" class="btn btn-dialog" title="Download '.$title.' CSV File"><i title="Download '.$title.' CSV File" class="fa fa-download fas-icon" ></i> </a>';
    }
    
    public static function getDownloadPagingData($rec_count){
        $rec_count_arr = explode('_',$rec_count);
        $start = $rec_count_arr[0];
        $start = $start-1;
        $end = $rec_count_arr[1];
        $limit = $end-$start;
        
        return ['start'=>$start,'limit'=>$limit];
    }
    
    public static function filterCsvData($str){
        return str_replace(['"',"'"],['',''],trim($str));
    }
    
    public static function filterCsvInteger($value){
        return $value."\t";
    }
    
    public static function processCURLRequest($url,$post_data='',$username='',$password='',$headers = array(),$return_output = false,$delete = false,$put = false){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(!empty($post_data)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
        }
        if(!empty($username) && !empty($password)){
            curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        }

        if($delete){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if($put){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,10); 
        curl_setopt($ch, CURLOPT_TIMEOUT ,20); 

        if(!empty($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $server_output = curl_exec ($ch);

        if(!$return_output){
            curl_close ($ch);
            return $server_output;
        }else{
            $info = curl_getinfo($ch);
            $info['curl_error'] = curl_error($ch);
            curl_close ($ch);
            return array('output'=>$server_output,'curl_info'=>$info);
        }
    }
    
    public static function validateMobileNumber($mobile) {
        if(!empty($mobile)) {
            $isMobileNmberValid = TRUE;
            $mobileDigitsLength = strlen($mobile);
            if ($mobileDigitsLength < 10 || $mobileDigitsLength > 15) {
                $isMobileNmberValid = FALSE;
            } else {
                if (!preg_match("/^[+]?[1-9][0-9]{9,14}$/", $mobile)) {
                $isMobileNmberValid = FALSE;
                }
            }
          return $isMobileNmberValid;
        }else{
          return false;
        }
    }
    
    public static function getDemandDuplicateInventory($demand_id){
        $inv_qrcodes = [];
        $duplicate_inventory = Store_products_demand_inventory::where('demand_id',$demand_id)->where('is_deleted',0)->selectRaw('inventory_id,COUNT(id) as inv_cnt')->groupByRaw('inventory_id')->havingRaw('inv_cnt > 1')->orderBy('inv_cnt','DESC')->get()->toArray();
        if(!empty($duplicate_inventory)){
            $duplicate_inv_ids = array_column($duplicate_inventory,'inventory_id');
            $inv_qrcodes = Pos_product_master_inventory::wherein('id',$duplicate_inv_ids)->where('is_deleted',0)->select('peice_barcode')->get()->toArray();
            $inv_qrcodes = array_column($inv_qrcodes,'peice_barcode');
        }
        
        return $inv_qrcodes;
    }
    
    public static function getSlug($text){
        $slug =  strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        $slug = rtrim($slug,'-');
        $slug = ltrim($slug,'-');
        return $slug;
    }

    // add loggedin user wishlist product 
    public static function add_pos_product_wish_list($requestData)
    {
        $validateionRules = array('customer_id'=>'required','product_id'=>'required');
        $attributes = array('customer_id'=>'customer_id','product_id'=>'product_id');

        $validator = Validator::make($requestData,$validateionRules,array(),$attributes);
        if ($validator->fails())
        { 
            $errors_str = CommonHelper::parseValidationErrors($validator->errors());
            return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>$errors_str, 'errors' => $validator->errors());
        } 
        
        $checkWishList = PosProductWishlist::where('product_id',$requestData['product_id'])->where('user_id',$requestData['customer_id'])->first();
        if(!empty($checkWishList))
        {
            return array('httpStatus'=>200, "dateTime"=>time(), 'status'=>'fail', 'message'=>'Product already exists in your wishlist', 'errors' => 'Product already exists in your wishlist');
        }

        // create wishlist 
        $insertArray = [
            'user_id' => $requestData['customer_id'],
            'product_id' => $requestData['product_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $posWishlist = PosProductWishlist::create($insertArray);
        
        return array('httpStatus'=>201, 'dateTime'=>time(), 'status'=>'success','message' => 'Wishlist added successfully!');
    }

    // dynamic send opt function 
    public static function dynamic_otp_send($number,$text)
    {
       $url = 'http://smsfortius.com/api/mt/SendSMS?user=kiaasa&password=kiaasa951&senderid=KIAASA&channel=Trans&DCS=0&flashsms=0&number='.$number.'&text='.urlencode($text).'&route=02';    
       $curl = curl_init();

       curl_setopt_array($curl, array(
       CURLOPT_URL => $url,
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => 'GET',
       ));

       $response = curl_exec($curl);

       curl_close($curl);
       return  $response;
    }
    
}