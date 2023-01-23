@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
<?php $image_types = '(Jpg, Jpeg, Png, Gif)'; ?>
@if(empty($error_message))
    <script>var design_id = {{ $design_data['id'] }} </script>
    
    <nav aria-label="breadcrumb" class="page_bbreadcrumb">
        <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Add New Design')); ?>
        <?php echo CommonHelper::createBreadCrumb($breadCrumbArray); ?>

        <h2 class="page_heading">Add New Design <span style="font-size:70%;"> (Version: <span id="design_version_title">{{$design_data['version']}}</span>, 
        Requisition Created: <span id="requisition_created_title">@if($design_data['is_requisition_created'] == 1) Yes @else No @endif</span><span id="reviewer_status_title">@if(!empty($design_data['reviewer_status'])), Status: {{$design_data['reviewer_status']}} @endif</span>)</span>
        <a href="javascript:;" class="btn_box reviews-list" onclick="getDesignReviews('design');">Reviews List</a>
        </h2>                                                                                                                                                                                        
    </nav> 
    
    <section class="form_area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-success alert-dismissible elem-hidden" id="savestatusMessage"></div>
                    <div class="alert alert-danger alert-dismissible elem-hidden"  id="errortatusMessage"></div>
                    <form id="update_design_data" name="update_design_data">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="Season" class="design-label">Season *</label>						
                                <select name="season_id" id="season_id" class="form-control design-sel">
                                    <option value="" >Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'season')
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor
                                </select>  
                                 <i class="fa fa-angle-down select_opt"></i>
                                <div class="invalid-feedback" id="error_Design_season_id" >This field is required.</div>
                            </div> 

                            <div class="form-group col-md-2"></div>

                            <div class="form-group col-md-4">
                                <label for="Product" class="design-label">Product *</label>						
                                <select name="product_id" id="product_id" class="form-control design-sel" onchange="/*getProductData(this.value,3,'category_id');*/">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($products);$i++)
                                        <option value="{{$products[$i]['id']}}">{{$products[$i]['name']}}</option>
                                    @endfor
                                </select>   
                                <i class="fa fa-angle-down select_opt"></i>
                                <div class="invalid-feedback" id="error_Design_product_id" >This field is required.</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="Product" class="design-label">Colour *</label>						
                                <div class="custom-select-wrapper">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger"><span>Select One</span>
                                            <i class="fa fa-angle-down select_opt custom-select-arrow" ></i> 
                                        </div>
                                        <div class="custom-options">
                                            @for($i=0;$i<count($design_items);$i++)
                                                @if(strtolower($design_items[$i]['type']) == 'color')
                                                    <span class="span-custom-option span-color" data-value="{{$design_items[$i]['id']}}" style="background-color: {{$design_items[$i]['description']}};">&nbsp;</span><span class="span-custom-option span-text span-text-{{$design_items[$i]['id']}}" data-value="{{$design_items[$i]['id']}}" >{{ucwords(strtolower($design_items[$i]['name']))}}</span>
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                    <input type="hidden" name="color_id" id="color_id" value="" class="color-hdn">
                                </div>
                                
                                <div class="invalid-feedback" id="error_Design_color_id" >This field is required.</div>
                            </div>

                            <div class="form-group col-md-2"></div>

                            <div class="form-group col-md-4">
                                <label for="Season" class="design-label">Category *</label>						
                                <select name="category_id" id="category_id" class="form-control design-sel" onchange="getDesignProductSubcategories(this.value,'sub_category_id');/*getProductData(this.value,4,'sub_category_id');*/">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'pos_product_category')
                                            <option value="{{$design_items[$i]['id']}}" >{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor
                                </select>    
                                <i class="fa fa-angle-down select_opt"></i>
                                <div class="invalid-feedback" id="error_Design_category_id" >This field is required.</div>
                            </div> 
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="Story" class="design-label">Sub Category *</label>						
                                <select name="sub_category_id" id="sub_category_id" class="form-control">
                                    <option value="">Select One</option>
                                </select>   
                                <i class="fa fa-angle-down select_opt"></i>
                                <div class="invalid-feedback" id="error_Design_sub_category_id" >This field is required.</div>
                            </div> 

                            <div class="form-group col-md-2"></div>

                            <div class="form-group col-md-4">
                                <label for="Story" class="design-label">Story *</label>						
                                <select name="story_id" id="story_id" class="form-control" >
                                    <option value="">Select One</option>
                                    <option value="0">N/A</option>
                                    @for($i=0;$i<count($stories);$i++)
                                        <option value="{{$stories[$i]['id']}}">@if(!empty($stories[$i]['story_year'])) {{$stories[$i]['story_year']}}_ @endif {{$stories[$i]['name']}}</option>
                                    @endfor
                                </select>   
                                <i class="fa fa-angle-down select_opt"></i>
                                <div class="invalid-feedback" id="error_Design_story_id" >This field is required.</div>
                            </div> 
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="Product Name" class="design-label">Product Name</label>						
                                <input type="text" class="form-control" name="product_name" id="product_name" value="" >
                                <div class="invalid-feedback" id="error_Design_product_name" >This field is required.</div>
                            </div> 

                            <div class="form-group col-md-2"></div>
                            <div class="form-group col-md-4">
                                <label for="Sale Price" class="design-label">Sale Price</label>						
                                <input type="text" class="form-control" name="sale_price" id="sale_price" value="" >
                                <div class="invalid-feedback" id="error_Design_sale_price" >This field is required.</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="Article" class="design-label">Article Number</label>						
                                <input type="text" class="form-control readonly-field" name="sku" id="sku" value="" readonly >
                            </div> 

                            <div class="form-group col-md-2"></div>
                            <?php $design_sizes_ids = array(); ?>
                            <?php for($i=0;$i<count($design_sizes);$i++) $design_sizes_ids[] = $design_sizes[$i]['size_id']; ?>
                            <div class="form-group col-md-4">
                                <label for="Size" class="design-label">Size</label>						
                                    @for($i=0;$i<count($size_list);$i++) 
                                        <input type="checkbox" name="size_id[]" <?php if(in_array($size_list[$i]['id'], $design_sizes_ids)) echo 'checked'; ?> id="size_{{$size_list[$i]['id']}}" style="height:auto;" value="{{$size_list[$i]['id']}}"> <span style="margin-right:10px;margin-left: 2px;"> {{$size_list[$i]['size']}}</span>
                                    @endfor
                                <div class="invalid-feedback" id="error_Design_size_id" >This field is required.</div>
                            </div> 
                        </div>
                        
                        

                        <input type="hidden" name="create_requisition" id="create_requisition" value="0" />

                    </form> 
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="Article">Images {{$image_types}}
                                <div id="design_image_upload_spinner" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                            </label>
                            <form method="post" id="upload_image_form" name="upload_image_form" enctype="multipart/form-data">
                            <div class="alert" id="design_upload_message" style="display: none"></div>    
                            <div class="img_upload">

                                <div class="img_group img_width" @if(isset($images_types['front'])) style="display:block;" @else style="display:none;" @endif id="design_image_div_view_front">
                                    <a href="javascript:;" onclick="deleteDesignImage('front');"><img src="{{asset('images/close.png')}}" alt="Delete" ></a>
                                    <img src="@if(isset($images_types['front'])) {{asset('images/design_images/'.$design_data['id'])}}/thumbs/{{$images_types['front']['image_name']}} @endif " alt="" id="design_image_view_front"> 
                                    <span>Front View</span>
                                </div> 

                                <div class="img_input img_width" id="design_image_div_upload_front" @if(!isset($images_types['front'])) style="display:block;" @else style="display:none;" @endif>
                                    <a href="javascript:;">Upload</a>
                                    <img src="{{asset('images/upload_img.jpg')}}" alt=""> 
                                    <input type="file" class="form-control-file" id="design_image_front" name="design_image_front" onchange="uploadDesignImage('front');"> 
                                    <input type="hidden" name="image_name_hdn_front" id="image_name_hdn_front" value="">
                                    <input type="hidden" name="image_updated_front" id="image_updated_front" value="0">
                                    <span>Front View</span>
                                </div>

                                <div class="img_group img_width" @if(isset($images_types['back'])) style="display:block;" @else style="display:none;" @endif id="design_image_div_view_back">
                                    <a href="javascript:;" onclick="deleteDesignImage('back');"><img src="{{asset('images/close.png')}}" alt="Delete" ></a>
                                    <img src="@if(isset($images_types['back'])) {{asset('images/design_images/'.$design_data['id'])}}/thumbs/{{$images_types['back']['image_name']}} @endif " alt="" id="design_image_view_back"> 
                                    <span>Back View</span>
                                </div> 

                                <div class="img_input img_width" id="design_image_div_upload_back" @if(!isset($images_types['back'])) style="display:block;" @else style="display:none;" @endif>
                                    <a href="javascript:;">Upload</a>
                                    <img src="{{asset('images/upload_img.jpg')}}" alt=""> 
                                    <input type="file" class="form-control-file" id="design_image_back" name="design_image_back" onchange="uploadDesignImage('back');"> 
                                    <input type="hidden" name="image_name_hdn_back" id="image_name_hdn_back" value="">
                                    <input type="hidden" name="image_updated_back" id="image_updated_back" value="0">
                                    <span>Back View</span>
                                </div>

                                <div class="img_group img_width" @if(isset($images_types['side'])) style="display:block;" @else style="display:none;" @endif id="design_image_div_view_side">
                                    <a href="javascript:;" onclick="deleteDesignImage('side');"><img src="{{asset('images/close.png')}}" alt="Delete" ></a>
                                    <img src="@if(isset($images_types['side'])) {{asset('images/design_images/'.$design_data['id'])}}/thumbs/{{$images_types['side']['image_name']}} @endif " alt="" id="design_image_view_side"> 
                                    <span>Side View</span>
                                </div> 

                                <div class="img_input img_width" id="design_image_div_upload_side" @if(!isset($images_types['side'])) style="display:block;" @else style="display:none;" @endif>
                                    <a href="javascript:;">Upload</a>
                                    <img src="{{asset('images/upload_img.jpg')}}" alt=""> 
                                    <input type="file" class="form-control-file" id="design_image_side" name="design_image_side" onchange="uploadDesignImage('side');"> 
                                    <input type="hidden" name="image_name_hdn_side" id="image_name_hdn_side" value="">
                                    <input type="hidden" name="image_updated_side" id="image_updated_side" value="0">
                                    <span>Side View</span>
                                </div>

                                <div class="img_group img_width" @if(isset($images_types['top'])) style="display:block;" @else style="display:none;" @endif id="design_image_div_view_top">
                                    <a href="javascript:;" onclick="deleteDesignImage('top');"><img src="{{asset('images/close.png')}}" alt="Delete" ></a>
                                    <img src="@if(isset($images_types['top'])) {{asset('images/design_images/'.$design_data['id'])}}/thumbs/{{$images_types['top']['image_name']}} @endif " alt="" id="design_image_view_top"> 
                                    <span>Close View</span>
                                </div> 

                                <div class="img_input img_width" id="design_image_div_upload_top" @if(!isset($images_types['top'])) style="display:block;" @else style="display:none;" @endif>
                                    <a href="javascript:;">Upload</a>
                                    <img src="{{asset('images/upload_img.jpg')}}" alt=""> 
                                    <input type="file" class="form-control-file" id="design_image_top" name="design_image_top" onchange="uploadDesignImage('top');"> 
                                    <input type="hidden" name="image_name_hdn_top" id="image_name_hdn_top" value="">
                                    <input type="hidden" name="image_updated_top" id="image_updated_top" value="0">
                                    <span>Close View</span>
                                </div>

                               <input type="hidden" name="image_type" id="image_type" value="">

                            </div>
                            </form>
                        </div> 
                        
                        <div class="form-group col-md-6">
                            <div class="table-responsive">
                                <table class="table clearfix design-cost-tbl">
                                    <!--<tr><th>Fabric Cost</th><td id="fabric_cost_td"></td></tr>
                                    <tr><th>Accessories Cost</th><td id="accessories_cost_td"></td></tr>
                                    <tr><th>Fabric Process Cost</th><td id="fabric_process_cost_td"></td></tr>
                                    <tr><th>Production Process Cost</th><td id="prod_process_cost_td"></td></tr>
                                    <tr><th>Total Cost</th><td id="total_cost_td"></td></tr>
                                    <tr><th>GST</th><td id="gst_td"></td></tr>
                                    <tr ><th>Net Total Cost</th><td id="net_cost_td"></td></tr>-->
                                    @if($design_data['designer_submitted'] == 1)
                                        <tr><th>Review Status:</th><td>{{ucfirst($design_data['design_review'])}}</td></tr>
                                        @if(!empty($design_data['purchaser_review_comment']))
                                            <tr><th>Review Comments:</th><td>{{$design_data['purchaser_review_comment']}}  ({{date('d-m-Y',strtotime($design_data['purchaser_review_date']))}})</td></tr>
                                        @endif
                                        
                                    @endif
                                </table>
                            </div>
                        </div>  -
                    </div>	
                    <div class="form-row">
                        <div class="col-md-12"> 
                            <div class="requirements_blk"> 
                                <h2>Requirements</h2>
                                <div style="display:none;" id="requirementsMessage"></div>
                                <div style="display:none;" id="updateSpecificationSheetErrorMessage" class="alert alert-danger alert-dismissible"></div>

                                <div class="tabs_blk"> 
                                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                        <a class="nav-link active" id="v-pills-Fabrics-tab" data-toggle="pill" href="#v-pills-Fabrics" role="tab" aria-controls="v-pills-Fabrics" aria-selected="true" onclick="displayTabData('fabric');">Fabrics</a>
                                        <a class="nav-link" id="v-pills-Accessories-tab" data-toggle="pill" href="#v-pills-Accessories" role="tab" aria-controls="v-pills-Accessories" aria-selected="false" onclick="displayTabData('accessories');">Accessories</a>
                                        <!--<a class="nav-link" id="v-pills-Process-tab" data-toggle="pill" href="#v-pills-Process" role="tab" aria-controls="v-pills-Process" aria-selected="false" onclick="displayTabData('process');">Fabric Process</a>
                                        <a class="nav-link" id="v-pills-Product_process-tab" data-toggle="pill" href="#v-pills-Product_process" role="tab" aria-controls="v-pills-Product_process" aria-selected="true" onclick="displayTabData('product_process');">Production Process</a>-->
                                        <a class="nav-link" id="v-pills-Packaging-tab" data-toggle="pill" href="#v-pills-Packaging" role="tab" aria-controls="v-pills-Packaging" aria-selected="false" onclick="displayTabData('packaging_sheet');">Packaging Sheet</a>
                                        <a class="nav-link" id="v-pills-Embroidery-tab" data-toggle="pill" href="#v-pills-Embroidery" role="tab" aria-controls="v-pills-Embroidery" aria-selected="false" onclick="displayTabData('embroidery');">Embroidery</a>
                                        <a class="nav-link" id="v-pills-Printing-tab" data-toggle="pill" href="#v-pills-Printing" role="tab" aria-controls="v-pills-Printing" aria-selected="false" onclick="displayTabData('printing');">Printing</a>
                                        <a class="nav-link" id="v-pills-Garment_cmt-tab" data-toggle="pill" href="#v-pills-Garment_cmt" role="tab" aria-controls="v-pills-Garment_cmt" aria-selected="false" onclick="displayTabData('garment_cmt');">Garment CMT</a>
                                        <a class="nav-link" id="v-pills-Specification_sheet-tab" data-toggle="pill" href="#v-pills-Specification_sheet" role="tab" aria-controls="v-pills-Specification_sheet" aria-selected="true" onclick="displayTabData('specification_sheet');">Specification Sheet</a>
                                    </div>
                                    <div class="tab-content" id="v-pills-tabContent">
                                        <div class="tab-pane fade show active" id="v-pills-Fabrics" role="tabpanel" aria-labelledby="v-pills-Fabrics-tab">
                                            <div id="req_tab_data"></div>
                                            <div class="table-responsive">
                                                <div class="table_actions">

                                                    <?php /* ?> @if(empty($design_data['reviewer_status']) || strtolower($design_data['reviewer_status']) == 'rejected') <?php */ ?>
                                                    @if($design_data['is_requisition_created'] == 0)
                                                        Comments: 
                                                        <textarea class="form-control design-comment" id="comments"  name="comments"></textarea>
                                                        <a href="javascript:;" onclick="window.location.href='{{url('designer/dashboard')}}'" class="btn_plane">Cancel</a>
                                                        <a href="javascript:;" class="btn_plane" id="submitDesignBtnDraft" name="submitDesignBtnDraft">Save</a>
                                                        <button class="btn_box" id="submitDesignBtn" name="submitDesignBtn">Send for approval</button>
                                                    @endif

                                                    <?php /* ?>
                                                    @if(isset($display_ds_head_notif_btn) && $display_ds_head_notif_btn == true)
                                                       <button class="btn_box" id="submitNotificationBtn" name="submitNotificationBtn" onclick="sendNotification(1,design_id);">Send Review Notification</button>
                                                    @endif <?php */ ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>   
                            </div>   
                        </div>   
                    </div>
                    
                    <hr/>
                    @if(!empty($design_data['sku']))
                        <div class="form-row">
                            <div class="col-md-12"> 
                                <div style="float:left">
                                    <h5>Costing Details</h5>
                                </div>
                                <div style="float:right;margin-bottom: 5px;">
                                    <a class="btn btn-dialog" href="{{url('design/detail/'.$design_data['id'].'?action=download_costing_csv')}}" >Download CSV</a>    
                                </div>
                                <div class="table-responsive table-filter">
                                    <table class="table table-striped admin-table" cellspacing="0" >
                                        <thead><tr class="header-tr">
                                            <th>SNo.</th><th>Particular</th><th>Avg.</th><th>Rate</th><th>PCS/MTR</th><th>Amount</th>
                                        </tr></thead>
                                        <tbody>
                                            <?php $total_data = array('fabric'=>0,'acc'=>0,'pack'=>0,'emb'=>0,'cmt'=>0,'print'=>0); ?>
                                            
                                            <tr style="background-color: #F9C9C9;"><th>1</th><th colspan="5">Fabric</th></tr>
                                            @for($i=0;$i<count($design_fabric_data);$i++)
                                                <tr>
                                                    <td></td>
                                                    <td>{{$design_fabric_data[$i]->fabric_name}}</td>
                                                    <td>{{$design_fabric_data[$i]->avg}}</td>
                                                    <td>{{$currency}} {{$design_fabric_data[$i]->rate}}</td>
                                                    <td>{{$design_fabric_data[$i]->unit_code}}</td>
                                                    <td>{{$currency}} {{$design_fabric_data[$i]->cost}}</td>
                                                </tr>
                                                <?php $total_data['fabric']+=$design_fabric_data[$i]->cost; ?>
                                            @endfor
                                            <tr><th></th><th colspan="4">Total</th><th>{{$currency}} {{$total_data['fabric']}}</th></tr>
                                            <tr><th></th><th colspan="5"></th></tr>
                                            
                                            <tr style="background-color: #F9C9C9;"><th>2</th><th colspan="5">Accessories</th></tr>
                                            @for($i=0;$i<count($design_accessories_data);$i++)
                                                <tr>
                                                    <td></td>
                                                    <td>{{$design_accessories_data[$i]->category_name}}</td>
                                                    <td>{{$design_accessories_data[$i]->avg}}</td>
                                                    <td>{{$currency}} {{$design_accessories_data[$i]->rate}}</td>
                                                    <td>{{$design_accessories_data[$i]->unit_code}}</td>
                                                    <td>{{$currency}} {{$design_accessories_data[$i]->cost}}</td>
                                                </tr>
                                                <?php $total_data['acc']+=$design_accessories_data[$i]->cost; ?>
                                            @endfor
                                            <tr><th></th><th colspan="4">Total</th><th>{{$currency}} {{$total_data['acc']}}</th></tr>
                                            <tr><th></th><th colspan="5"></th></tr>
                                            
                                            <tr style="background-color: #F9C9C9;"><th>3</th><th colspan="5">Packaging Sheet</th></tr>
                                            @for($i=0;$i<count($design_packaging_sheet_data);$i++)
                                                @if($design_packaging_sheet_data[$i]->avg > 0)
                                                    <tr>
                                                        <td></td>
                                                        <td>{{$design_packaging_sheet_data[$i]->packaging_sheet_name}}</td>
                                                        <td>{{$design_packaging_sheet_data[$i]->avg}}</td>
                                                        <td>{{$currency}} {{$design_packaging_sheet_data[$i]->rate}}</td>
                                                        <td>PCS</td>
                                                        <td>{{$currency}} {{$design_packaging_sheet_data[$i]->cost}}</td>
                                                    </tr>
                                                    <?php $total_data['pack']+=$design_packaging_sheet_data[$i]->cost; ?>
                                                @endif
                                            @endfor
                                            <tr><th></th><th colspan="4">Total</th><th>{{$currency}} {{$total_data['pack']}}</th></tr>
                                            <tr><th></th><th colspan="5"></th></tr>
                                            
                                            <tr style="background-color: #F9C9C9;"><th>4</th><th colspan="5">Embroidery</th></tr>
                                            @for($i=0;$i<count($design_embroidery_data);$i++)
                                                <tr>
                                                    <td></td>
                                                    <td>{{$design_embroidery_data[$i]->embroidery_type}}</td>
                                                    <td></td>
                                                    <td>{{$currency}} {{$design_embroidery_data[$i]->rate}}</td>
                                                    <td>{{$design_embroidery_data[$i]->unit_code}}</td>
                                                    <td>{{$currency}} {{$design_embroidery_data[$i]->cost}}</td>
                                                </tr>
                                                <?php $total_data['emb']+=$design_embroidery_data[$i]->cost; ?>
                                            @endfor
                                            <tr><th></th><th colspan="4">Total</th><th>{{$currency}} {{$total_data['emb']}}</th></tr>
                                            <tr><th></th><th colspan="5"></th></tr>
                                            
                                            <tr style="background-color: #F9C9C9;"><th>5</th><th colspan="5">Printing</th></tr>
                                            @for($i=0;$i<count($design_printing_data);$i++)
                                                <tr>
                                                    <td></td>
                                                    <td>{{$design_printing_data[$i]->printing_type}}</td>
                                                    <td></td>
                                                    <td>{{$currency}} {{$design_printing_data[$i]->rate}}</td>
                                                    <td>{{$design_printing_data[$i]->unit_code}}</td>
                                                    <td>{{$currency}} {{$design_printing_data[$i]->cost}}</td>
                                                </tr>
                                                <?php $total_data['print']+=$design_printing_data[$i]->cost; ?>
                                            @endfor
                                            <tr><th></th><th colspan="4">Total</th><th>{{$currency}} {{$total_data['emb']}}</th></tr>
                                            <tr><th></th><th colspan="5"></th></tr>
                                            
                                            <tr style="background-color: #F9C9C9;"><th>6</th><th colspan="5">Garment CMT</th></tr>
                                            @for($i=0;$i<count($design_garment_cmt_data);$i++)
                                                <?php if(strtolower($design_garment_cmt_data[$i]->garment_cmt_name) == 'margin') $margin = $design_garment_cmt_data[$i]->rate; ?>
                                                @if($design_garment_cmt_data[$i]->rate > 0 && strtolower($design_garment_cmt_data[$i]->garment_cmt_name) != 'margin')
                                                    <tr>
                                                        <td></td>
                                                        <td>{{$design_garment_cmt_data[$i]->garment_cmt_name}}</td>
                                                        <td></td>
                                                        <td>{{$currency}} {{$design_garment_cmt_data[$i]->rate}}</td>
                                                        <td>{{$design_garment_cmt_data[$i]->unit_code}}</td>
                                                        <td>{{$currency}} {{$design_garment_cmt_data[$i]->cost}}</td>
                                                    </tr>
                                                    <?php $total_data['cmt']+=$design_garment_cmt_data[$i]->cost; ?>
                                                @endif
                                            @endfor
                                            <tr><th></th><th colspan="4">Total</th><th>{{$currency}} {{$total_data['cmt']}}</th></tr>
                                            <tr><th></th><th colspan="5"></th></tr>
                                            
                                            <tfoot>
                                                <tr style="background-color: #F7B5B5;"><th></th><th colspan="4">Total Price</th><th>{{$currency}} {{$total_price =  round($total_data['fabric']+$total_data['acc']+$total_data['pack']+$total_data['emb']+$total_data['cmt']+$total_data['print'],2)}}</th></tr>
                                                
                                                <tr style="background-color: #F7B5B5;"><th></th><th>Margin</th><th></th><th>{{$margin}}%</th><th></th><th>{{$currency}} {{$margin_amt =  round($total_price*($margin/100),2)}}</th></tr>
                                                <tr><th></th><th colspan="5"></th></tr>
                                                <tr style="background-color: #F7B5B5;"><th></th><th>Net Price</th><th></th><th></th><th></th><th>{{$currency}} {{$total_price+$margin_amt}}</th></tr>
                                            </tfoot>
                                        </tbody>
                                    </table>
                                </div>
                            </div>    
                        </div>        
                    @endif
                </div> 
            </div>
        </div>
    </section>

    <div class="modal fade" id="add-design-fabric" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"  aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Fabric</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addFabricErrorMessage"></div>

                <form class="" name="addFabricFrm" id="addFabricFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Body Part *</label>
                                <select name="addFabricBodyPart" id="addFabricBodyPart" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'body_part')
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor   
                                </select> 
                                <div class="invalid-feedback" id="error_addFabric_body_part"></div>			
                            </div>

                            <div class="form-group col-md-4">
                                <label>Fabric Type *</label>
                                <select name="addFabricName" id="addFabricName" class="form-control" onchange="updateFabricNameData(this.value,'add',0,'');">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'fabric_name')
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor
                                </select> 
                                <div class="invalid-feedback" id="error_addFabric_name"></div>	
                            </div>

                            <div class="form-group col-md-4">
                                <label>GSM *</label>
                                <select name="addFabricGSM" id="addFabricGSM" class="form-control">
                                    <option value="">Select One</option>
                                </select>   
                                <div class="invalid-feedback" id="error_addFabric_gsm_id"></div>	
                            </div>
                        </div>    

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Color *</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger"><span id="add_fabric_color_span">Select One</span>
                                            <i class="fa fa-angle-down select_opt custom-select-arrow" ></i> 
                                        </div>
                                        <div class="custom-options">
                                            @for($i=0;$i<count($design_items);$i++)
                                                @if(strtolower($design_items[$i]['type']) == 'color')
                                                    <span class="span-custom-option span-color" data-value="{{$design_items[$i]['id']}}" style="background-color: {{$design_items[$i]['description']}};">&nbsp;</span><span class="span-custom-option span-text span-text-{{$design_items[$i]['id']}}" data-value="{{$design_items[$i]['id']}}" >{{ucwords(strtolower($design_items[$i]['name']))}}</span>
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                    <input type="hidden" name="addFabricColor" id="addFabricColor" value="" class="color-hdn">
                                </div>
                                
                                <div class="invalid-feedback" id="error_addFabric_color" style="margin-top: 20px;"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Width *</label>
                                <select name="addFabricWidth" id="addFabricWidth" class="form-control">
                                    <option value="">Select One</option>
                                </select> 
                                <div class="invalid-feedback" id="error_addFabric_width"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Unit *</label>
                                <select name="addFabricMasterUnit" id="addFabricMasterUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_addFabric_master_unit_id"></div>	
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label>Content *</label>
                                <select name="addFabricContent" id="addFabricContent" class="form-control">
                                    <option value="">Select One</option>
                                </select> 
                                <div class="invalid-feedback" id="error_addFabric_content_id"></div>	
                            </div>

                        </div>    

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Average *</label>
                                <input type="text" name="addFabricAvg" id="addFabricAvg" class="form-control" onkeyup="updateFabricCost('add');">
                                <div class="invalid-feedback" id="error_addFabric_avg"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Unit *</label>
                                <select name="addFabricUnit" id="addFabricUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_addFabric_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Rate *</label>
                                <input type="text" name="addFabricRate" id="addFabricRate" class="form-control" onkeyup="updateFabricCost('add');">
                                <div class="invalid-feedback" id="error_addFabric_rate"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Cost *</label>
                                <input type="text" name="addFabricCost" id="addFabricCost" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_addFabric_cost"></div>	
                            </div>
                        </div>    

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Comments</label>
                                <textarea name="addFabricComments" id="addFabricComments" class="form-control" style="height:40px;"></textarea>
                                <div class="invalid-feedback" id="error_addFabric_comments"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Image <span class="image-types">{{$image_types}}</span></label>
                                <input type="file" name="addFabricImage" id="addFabricImage" class="form-control">
                                <div class="invalid-feedback" id="error_addFabric_addFabricImage"></div>	
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-fabric-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addFabricBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addFabricBtn_submit" class="btn btn-dialog" onclick="addDesignFabric({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-design-fabric" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Fabric</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="editFabricErrorMessage"></div>

                <form class="" name="editFabricFrm" id="editFabricFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Body Part *</label>
                                <select name="editFabricBodyPart" id="editFabricBodyPart" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++):
                                        @if(strtolower($design_items[$i]['type']) == 'body_part')
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor;     
                                </select> 

                                <div class="invalid-feedback" id="error_editFabric_body_part"></div>			
                            </div>

                            <div class="form-group col-md-4">
                                <label>Fabric Type *</label>
                                <select name="editFabricName" id="editFabricName" class="form-control" onchange="updateFabricNameData(this.value,'edit',0,'');">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++):
                                        @if(strtolower($design_items[$i]['type']) == 'fabric_name')
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor;       
                                </select> 
                                <div class="invalid-feedback" id="error_editFabric_name"></div>	
                            </div>

                            <div class="form-group col-md-4">
                                <label>GSM *</label>
                                <select name="editFabricGSM" id="editFabricGSM" class="form-control">
                                    <option value="">Select One</option>
                                </select>   
                                <div class="invalid-feedback" id="error_editFabric_gsm_id"></div>	
                            </div>
                        </div>    

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Color *</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger" ><span id="edit_fabric_color_span">Select One</span>
                                            <i class="fa fa-angle-down select_opt custom-select-arrow" ></i> 
                                        </div>
                                        <div class="custom-options">
                                            @for($i=0;$i<count($design_items);$i++)
                                                @if(strtolower($design_items[$i]['type']) == 'color')
                                                    <span class="span-custom-option span-color" data-value="{{$design_items[$i]['id']}}" style="background-color: {{$design_items[$i]['description']}};">&nbsp;</span><span class="span-custom-option span-text span-text-{{$design_items[$i]['id']}}" data-value="{{$design_items[$i]['id']}}" >{{ucwords(strtolower($design_items[$i]['name']))}}</span>
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                    <input type="hidden" name="editFabricColor" id="editFabricColor" value="" class="color-hdn">
                                </div>
                                
                                <div class="invalid-feedback" id="error_editFabric_color"></div>	
                            </div>

                            <div class="form-group col-md-3">
                                <label>Width *</label>
                                <select name="editFabricWidth" id="editFabricWidth" class="form-control">
                                    <option value="">Select One</option>
                                </select> 
                                <div class="invalid-feedback" id="error_editFabric_width"></div>	
                            </div>

                            <div class="form-group col-md-3">
                                <label>Unit *</label>
                                <select name="editFabricMasterUnit" id="editFabricMasterUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editFabric_master_unit_id"></div>	
                                <input type="hidden" name="fabric_id_edit_hdn" id="fabric_id_edit_hdn" value="">
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label>Content *</label>
                                <select name="editFabricContent" id="editFabricContent" class="form-control">
                                    <option value="">Select One</option>
                                </select> 
                                <div class="invalid-feedback" id="error_editFabric_content_id"></div>	
                            </div>
                        </div>   

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Average *</label>
                                <input type="text" name="editFabricAvg" id="editFabricAvg" class="form-control" onkeyup="updateFabricCost('edit');">
                                <div class="invalid-feedback" id="error_editFabric_avg"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Unit *</label>
                                <select name="editFabricUnit" id="editFabricUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editFabric_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Rate *</label>
                                <input type="text" name="editFabricRate" id="editFabricRate" class="form-control" onkeyup="updateFabricCost('edit');">
                                <div class="invalid-feedback" id="error_editFabric_rate"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Cost *</label>
                                <input type="text" name="editFabricCost" id="editFabricCost" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_editFabric_cost"></div>	
                            </div>
                        </div>    
                        <div class="form-row ">

                            <div class="form-group col-md-12" >
                                <label>Comments</label>
                                <textarea name="editFabricComments" id="editFabricComments" class="form-control" style="height:40px;"></textarea>
                                <div class="invalid-feedback" id="error_editFabric_comments"></div>	
                            </div>
                        </div>        

                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Image <span class="image-types">{{$image_types}}</span></label>
                                <div class="separator-10"></div>
                                <img src="" id="editFabricImg" style="display:none;float:left;" class="edit-design-image"/>
                                <a href="javascript:;" onclick="deleteDesignItemImage('editFabricImg',this);" id="editFabricImg_delete_link" style="float:left;margin-left: 5px;"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                                <input type="hidden" id="editFabricImg_delete_hdn" name="editFabricImg_delete_hdn" value="0">
                                <div class="separator-10">&nbsp;</div>
                                <input type="file" name="editFabricImage" id="editFabricImage" class="form-control" onchange="updateDesignItemImage('editFabricImg');">
                                <div class="invalid-feedback" id="error_editFabric_editFabricImage"></div>	
                            </div>
                            <div class="form-group col-md-6 mandatory-fields-info" >
                                Fields marked with * are mandatory
                            </div>
                        </div>        
                    </div>
                    
                    <div class="modal-footer center-footer">
                        <div id="edit-fabric-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editFabricBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="editFabricBtn_submit"  class="btn btn-dialog" onclick="updateDesignFabric();">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="add-design-accessories" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Accessories</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addAccessoriesErrorMessage"></div>

                <form class="" name="addAccessoriesFrm" id="addAccessoriesFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Category *</label>
                                <select name="addAccessoriesCategory" id="addAccessoriesCategory" class="form-control" onchange="getAccessorySubcategories(this.value,'addAccessoriesSubCategory',0);">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'accessory_category' && $design_items[$i]['pid'] == 0)
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor 
                                </select>    
                                <div class="invalid-feedback" id="error_addAccessories_category_id"></div>			
                            </div>
                            <div class="form-group col-md-4">
                                <label>Sub Category *</label>
                                <select name="addAccessoriesSubCategory" id="addAccessoriesSubCategory" class="form-control" onchange="getAccessorySize(this.value,'addAccessoriesSize',0);">
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_addAccessories_subcategory_id"></div>			
                            </div>
                            <div class="form-group col-md-4">
                                <label>Size *</label>
                                <select name="addAccessoriesSize" id="addAccessoriesSize" class="form-control">
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_addAccessories_size"></div>			
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Color *</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger"><span id="add_acc_color_span">Select One</span>
                                            <i class="fa fa-angle-down select_opt custom-select-arrow" ></i> 
                                        </div>
                                        <div class="custom-options">
                                            @for($i=0;$i<count($design_items);$i++)
                                                @if(strtolower($design_items[$i]['type']) == 'color')
                                                    <span class="span-custom-option span-color" data-value="{{$design_items[$i]['id']}}" style="background-color: {{$design_items[$i]['description']}};">&nbsp;</span><span class="span-custom-option span-text span-text-{{$design_items[$i]['id']}}" data-value="{{$design_items[$i]['id']}}" >{{ucwords(strtolower($design_items[$i]['name']))}}</span>
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                    <input type="hidden" name="addAccessoriesColor" id="addAccessoriesColor" value="" class="color-hdn">
                                </div>

                                <div class="invalid-feedback" id="error_addAccessories_color_id"></div>	
                            </div>
                            <div class="form-group col-md-4">
                                <label>Rate *</label>
                                <input type="text" name="addAccessoriesRate" id="addAccessoriesRate" class="form-control" onkeyup="updateAccessoriesCost('add');">
                                <div class="invalid-feedback" id="error_addAccessories_rate"></div>	
                            </div>
                            <div class="form-group col-md-4">
                                <label>Quantity/Average *</label>
                                <input type="text" name="addAccessoriesQuantity" id="addAccessoriesQuantity" class="form-control" onkeyup="updateAccessoriesCost('add');">
                                <div class="invalid-feedback" id="error_addAccessories_qty"></div>	
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Unit *</label>
                                <select name="addAccessoriesUnit" id="addAccessoriesUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_addAccessories_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Cost *</label>
                                <input type="text" name="addAccessoriesCost" id="addAccessoriesCost" class="form-control readonly-field" readonly="">
                                <div class="invalid-feedback" id="error_addAccessories_cost"></div>	
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <input type="file" name="addAccessoriesImage" id="addAccessoriesImage" class="form-control">
                            <div class="invalid-feedback" id="error_addAccessories_addAccessoriesImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="addAccessoriesComments" id="addAccessoriesComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_addAccessories_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer center-footer">
                        <div id="add-Accessories-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addAccessoriesBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addAccessoriesBtn_submit"  class="btn btn-dialog" onclick="addDesignAccessories({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="edit-design-accessories" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Accessories</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="editAccessoriesErrorMessage"></div>

                <form class="" name="editAccessoriesFrm" id="editAccessoriesFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Category *</label>
                               <select name="editAccessoriesCategory" id="editAccessoriesCategory" class="form-control" onchange="getAccessorySubcategories(this.value,'editAccessoriesSubCategory',0);">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'accessory_category' && $design_items[$i]['pid'] == 0)
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editAccessories_category_id"></div>			
                            </div>
                            <div class="form-group col-md-4">
                                <label>Sub Category *</label>
                                <select name="editAccessoriesSubCategory" id="editAccessoriesSubCategory" class="form-control" onchange="getAccessorySize(this.value,'editAccessoriesSize',0);">
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_editAccessories_subcategory_id"></div>			
                            </div>
                            <div class="form-group col-md-4">
                                <label>Size *</label>
                                <select name="editAccessoriesSize" id="editAccessoriesSize" class="form-control">
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_editAccessories_size"></div>			
                            </div>
                        </div>
                        
                        <div class="form-row ">
                            <div class="form-group col-md-4">
                                <label>Color *</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger"><span id="edit_acc_color_span">Select One</span>
                                            <i class="fa fa-angle-down select_opt custom-select-arrow" ></i> 
                                        </div>
                                        <div class="custom-options">
                                            @for($i=0;$i<count($design_items);$i++)
                                                @if(strtolower($design_items[$i]['type']) == 'color')
                                                    <span class="span-custom-option span-color" data-value="{{$design_items[$i]['id']}}" style="background-color: {{$design_items[$i]['description']}};">&nbsp;</span><span class="span-custom-option span-text span-text-{{$design_items[$i]['id']}}" data-value="{{$design_items[$i]['id']}}" >{{ucwords(strtolower($design_items[$i]['name']))}}</span>
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                    <input type="hidden" name="editAccessoriesColor" id="editAccessoriesColor" value="" class="color-hdn">
                                </div>

                                <div class="invalid-feedback" id="error_editAccessories_color_id"></div>	
                            </div>

                            <div class="form-group col-md-4">
                                <label>Rate *</label>
                                <input type="text" name="editAccessoriesRate" id="editAccessoriesRate" class="form-control" onkeyup="updateAccessoriesCost('edit');">
                                <div class="invalid-feedback" id="error_editAccessories_rate"></div>	
                            </div>
                            <div class="form-group col-md-4">
                                <label>Quantity/Average *</label>
                                <input type="text" name="editAccessoriesQuantity" id="editAccessoriesQuantity" class="form-control" onkeyup="updateAccessoriesCost('edit');">
                                <div class="invalid-feedback" id="error_editAccessories_qty"></div>	
                            </div>
                        </div>     

                        <div class="form-row ">
                            <div class="form-group col-md-6">
                                <label>Unit *</label>
                                <select name="editAccessoriesUnit" id="editAccessoriesUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++):
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor;    
                                </select>    
                                <div class="invalid-feedback" id="error_editAccessories_unit_id"></div>	
                                <input type="hidden" name="accessories_id_edit_hdn" id="accessories_id_edit_hdn" value="">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Cost *</label>
                                <input type="text" name="editAccessoriesCost" id="editAccessoriesCost" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_editAccessories_cost"></div>	
                            </div>
                        </div>   
                        
                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <img src="" id="editAccessoriesImg" style="display:none;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editAccessoriesImg',this);" id="editAccessoriesImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editAccessoriesImg_delete_hdn" name="editAccessoriesImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            <input type="file" name="editAccessoriesImage" id="editAccessoriesImage" class="form-control" onchange="updateDesignItemImage('editAccessoriesImg');">
                            <div class="invalid-feedback" id="error_editAccessories_editAccessoriesImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editAccessoriesComments" id="editAccessoriesComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_editAccessories_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="edit-Accessories-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editAccessoriesBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editAccessoriesBtn_submit"  class="btn btn-dialog" onclick="updateDesignAccessories({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-design-process" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Fabric Process</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addProcessErrorMessage"></div>

                <form class="" name="addProcessFrm" id="addProcessFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Fabric *</label>
                            <select name="addProcessFabric" id="addProcessFabric" class="form-control" onchange="updateProcessCost('add');">
                                <option value="">Select One</option>
                            </select>    
                            <div class="invalid-feedback" id="error_addProcess_fabric_id"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Category *</label>
                                <select name="addProcessCategory" id="addProcessCategory" class="form-control" onchange="getProcessTypes(this.value,'addProcessType',0);">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'process_category')
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_addProcess_category_id"></div>			
                            </div>
                            <div class="form-group col-md-6">
                                <label>Type *</label>
                                <select name="addProcessType" id="addProcessType" class="form-control" >
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_addProcess_type_id"></div>	
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Rate *</label>
                                <input type="text" name="addProcessRate" id="addProcessRate" class="form-control" onkeyup="updateProcessCost('add');">
                                <div class="invalid-feedback" id="error_addProcess_rate"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Unit *</label>
                                <select name="addProcessUnit" id="addProcessUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor   
                                </select>    
                                <div class="invalid-feedback" id="error_addProcess_unit_id"></div>	
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Average *</label>
                                <input type="text" name="addProcessAvg" id="addProcessAvg" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_addProcess_avg"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Cost *</label>
                                <input type="text" name="addProcessCost" id="addProcessCost" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_addProcess_cost"></div>	
                            </div>
                        </div>    
                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <input type="file" name="addProcessImage" id="addProcessImage" class="form-control">
                            <div class="invalid-feedback" id="error_addProcess_addProcessImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="addProcessComments" id="addProcessComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_addProcess_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-Process-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addProcessBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addProcessBtn_submit" class="btn btn-dialog" onclick="addDesignProcess({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-design-process" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Fabric Process</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="editProcessErrorMessage"></div>

                <form class="" name="editProcessFrm" id="editProcessFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Fabric</label>
                            <select name="editProcessFabric" id="editProcessFabric" class="form-control" onchange="updateProcessCost('edit');">
                                <option value="">Select One</option>
                            </select>    
                            <div class="invalid-feedback" id="error_editProcess_fabric_id"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Category *</label>
                                <select name="editProcessCategory" id="editProcessCategory" class="form-control" onchange="getProcessTypes(this.value,'editProcessType',0);">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'process_category')
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editProcess_category_id"></div>			
                            </div>
                            <div class="form-group col-md-6">
                                <label>Type *</label>
                                <select name="editProcessType" id="editProcessType" class="form-control">
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_editProcess_type_id"></div>	
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Rate *</label>
                                <input type="text" name="editProcessRate" id="editProcessRate" class="form-control" onkeyup="updateProcessCost('edit');">
                                <div class="invalid-feedback" id="error_editProcess_rate"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Unit *</label>
                                <select name="editProcessUnit" id="editProcessUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editProcess_unit_id"></div>	
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Average *</label>
                                <input type="text" name="editProcessAvg" id="editProcessAvg" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_editProcess_avg"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Cost *</label>
                                <input type="text" name="editProcessCost" id="editProcessCost" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_editProcess_cost"></div>
                                <input type="hidden" name="process_id_edit_hdn" id="process_id_edit_hdn" value="">
                            </div>
                        </div>    
                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <img src="" id="editProcessImg" style="display:none;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editProcessImg',this);" id="editProcessImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editProcessImg_delete_hdn" name="editProcessImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            <input type="file" name="editProcessImage" id="editProcessImage" class="form-control" onchange="updateDesignItemImage('editProcessImg');">
                            <div class="invalid-feedback" id="error_editProcess_editProcessImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editProcessComments" id="editProcessComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_editProcess_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="edit-Process-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editProcessBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editProcessBtn_submit" class="btn btn-dialog" onclick="updateDesignProcess({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-design-packaging_sheet" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Packaging Sheet</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="editPackagingSheetErrorMessage"></div>

                <form class="" name="editPackagingSheetFrm" id="editPackagingSheetFrm" type="POST">
                    <div class="modal-body" >

                         <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="editPackagingSheetName" id="editPackagingSheetName" class="form-control readonly-field" readonly>
                            <div class="invalid-feedback" id="error_editPackagingSheet_name"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Rate *</label>
                                <input type="text" name="editPackagingSheetRate" id="editPackagingSheetRate" class="form-control" onkeyup="updatePackagingSheetCost('edit');">
                                <div class="invalid-feedback" id="error_editPackagingSheet_rate"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Quantity *</label>
                                <input type="text" name="editPackagingSheetQty" id="editPackagingSheetQty" class="form-control" onkeyup="updatePackagingSheetCost('edit');">
                                <div class="invalid-feedback" id="error_editPackagingSheet_qty"></div>	
                            </div>
                        </div>    
                        <div class="form-group">
                            <label>Cost *</label>
                            <input type="text" name="editPackagingSheetCost" id="editPackagingSheetCost" class="form-control readonly-field" readonly>
                            <div class="invalid-feedback" id="error_editPackagingSheet_cost"></div>	
                            <input type="hidden" name="packaging_sheet_id_edit_hdn" id="packaging_sheet_id_edit_hdn" value="">
                        </div>

                         <div class="form-group">
                            <label>Image  <span class="image-types">{{$image_types}}</span></label>
                            <img src="" id="editPackagingSheetImg" style="display:none;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editPackagingSheetImg',this);" id="editPackagingSheetImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editPackagingSheetImg_delete_hdn" name="editPackagingSheetImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            <input type="file" name="editPackagingSheetImage" id="editPackagingSheetImage" class="form-control" onchange="updateDesignItemImage('editPackagingSheetImg');">
                            <div class="invalid-feedback" id="error_editPackagingSheet_editPackagingSheetImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editPackagingSheetComments" id="editPackagingSheetComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_editPackagingSheet_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="edit-PackagingSheet-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editPackagingSheetBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editPackagingSheetBtn_submit" class="btn btn-dialog" onclick="updateDesignPackagingSheet({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-design-product_process" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Production Process</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addProductProcessErrorMessage"></div>

                <form class="" name="addProductProcessFrm" id="addProductProcessFrm" type="POST">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Body Part *</label>
                            <select name="addProductProcessBodyPart" id="addProductProcessBodyPart" class="form-control">
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'body_part')
                                        <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor   
                            </select> 
                            <div class="invalid-feedback" id="error_addProductProcess_body_part_id"></div>			
                        </div>
                        <div class="form-group">
                            <label>Name *</label>
                            <select name="addProductProcessName" id="addProductProcessName" class="form-control" onchange="updateProductProcessForm();">
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'production_process')
                                        <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor
                            </select>    
                            <div class="invalid-feedback" id="error_addProductProcess_name_id"></div>	
                        </div>
                        <div class="form-group" id="product_process_cost_div" >
                            <label>Designer Cost *</label>
                            <input type="text" name="addProductProcessDesignerCost" id="addProductProcessDesignerCost" class="form-control" >
                            <div class="invalid-feedback" id="error_addProductProcess_cost"></div>	
                        </div>

                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <input type="file" name="addProductProcessImage" id="addProductProcessImage" class="form-control" onchange="updateDesignItemImage('addProductProcessImg');">
                            <div class="invalid-feedback" id="error_addProductProcess_addProductProcessImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="addProductProcessComments" id="addProductProcessComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_addProductProcess_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-ProductProcess-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addProductProcessBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addProductProcessBtn_submit" class="btn btn-dialog" onclick="addDesignProductProcess({{ $design_data['id'] }});">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-design-product_process" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Production Process</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="editProductProcessErrorMessage"></div>

                <form class="" name="editProductProcessFrm" id="editProductProcessFrm" type="POST">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Body Part *</label>
                            <select name="editProductProcessBodyPart" id="editProductProcessBodyPart" class="form-control">
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'body_part')
                                        <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor   
                            </select> 
                            <div class="invalid-feedback" id="error_editProductProcess_body_part_id"></div>			
                        </div>
                        <div class="form-group">
                            <label>Name *</label>
                            <select name="editProductProcessName" id="editProductProcessName" class="form-control" >
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'production_process')
                                        <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor
                            </select>    
                            <div class="invalid-feedback" id="error_editProductProcess_name_id"></div>	
                        </div>
                        <div class="form-group" id="product_process_cost_edit_div" >
                            <label>Designer Cost *</label>
                            <input type="text" name="editProductProcessDesignerCost" id="editProductProcessDesignerCost" class="form-control" >
                            <div class="invalid-feedback" id="error_editProductProcess_cost"></div>	
                        </div>

                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <img src="" id="editProductProcessImg" style="display:none;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editProductProcessImg',this);" id="editProductProcessImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editProductProcessImg_delete_hdn" name="editProductProcessImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            <input type="file" name="editProductProcessImage" id="editProductProcessImage" class="form-control" onchange="updateDesignItemImage('editProductProcessImg');">
                            <div class="invalid-feedback" id="error_editProductProcess_editProductProcessImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editProductProcessComments" id="editProductProcessComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_editProductProcess_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="edit-ProductProcess-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editProductProcessBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editProductProcessBtn_submit" class="btn btn-dialog" onclick="updateDesignProductProcess({{ $design_data['id'] }});">Submit</button>
                        <input type="hidden" name="edit_product_process_id_edit_hdn" id="edit_product_process_id_edit_hdn" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-design-specification_sheet" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <?php /* ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Specification sheet</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div><?php */ ?>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addSpecificationSheetErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="addSpecificationSheetSuccessMessage"></div>

                <form class="" name="addSpecificationSheetFrm" id="addSpecificationSheetFrm" type="POST">
                    <div class="modal-body" >
                        <div id="specification_sheet_add_content"></div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-SpecificationSheet-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addSpecificationSheetBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addSpecificationSheetBtn_submit" class="btn btn-dialog" onclick="submitAddDesignSpecificationSheet();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="add-design-embroidery" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Embroidery</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible elem-hidden" id="addEmbroideryErrorMessage"></div>

                <form class="" name="addEmbroideryFrm" id="addEmbroideryFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Type *</label>
                                <select name="addEmbroideryType" id="addEmbroideryType" class="form-control" >
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'embroidery_type' && $design_items[$i]['pid'] == 0)
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor 
                                </select>    
                                <div class="invalid-feedback" id="error_addEmbroidery_type"></div>			
                            </div>
                            <div class="form-group col-md-3">
                                <label>Rate *</label>
                                <input type="text" name="addEmbroideryRate" id="addEmbroideryRate" class="form-control" onkeyup="updateEmbroideryCost('add');">
                                <div class="invalid-feedback" id="error_addEmbroidery_rate"></div>	
                            </div>
                            <!--<div class="form-group col-md-4">
                                <label>Quantity/Average *</label>
                                <input type="text" name="addAccessoriesQuantity" id="addAccessoriesQuantity" class="form-control" onkeyup="updateAccessoriesCost('add');">
                                <div class="invalid-feedback" id="error_addAccessories_qty"></div>	
                            </div>-->
                        
                            <div class="form-group col-md-3">
                                <label>Unit *</label>
                                <select name="addEmbroideryUnit" id="addEmbroideryUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <?php if(strtolower($units[$i]['name']) != 'pcs') continue; ?>
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_addEmbroidery_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Cost *</label>
                                <input type="text" name="addEmbroideryCost" id="addEmbroideryCost" class="form-control readonly-field" readonly="">
                                <div class="invalid-feedback" id="error_addEmbroidery_cost"></div>	
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <input type="file" name="addEmbroideryImage" id="addEmbroideryImage" class="form-control">
                            <div class="invalid-feedback" id="error_addEmbroidery_addEmbroideryImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="addEmbroideryComments" id="addEmbroideryComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_addEmbroidery_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer center-footer">
                        <div id="add-Embroidery-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addEmbroideryBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addEmbroideryBtn_submit"  class="btn btn-dialog" onclick="addDesignEmbroidery({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="edit-design-embroidery" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Embroidery</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible elem-hidden" id="editEmbroideryErrorMessage"></div>

                <form class="" name="editEmbroideryFrm" id="editEmbroideryFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Type *</label>
                                <select name="editEmbroideryType" id="editEmbroideryType" class="form-control" >
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'embroidery_type' && $design_items[$i]['pid'] == 0)
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor 
                                </select>    
                                <div class="invalid-feedback" id="error_editEmbroidery_type"></div>			
                            </div>
                            <div class="form-group col-md-3">
                                <label>Rate *</label>
                                <input type="text" name="editEmbroideryRate" id="editEmbroideryRate" class="form-control" onkeyup="updateEmbroideryCost('edit');">
                                <div class="invalid-feedback" id="error_editEmbroidery_rate"></div>	
                            </div>
                            <!--<div class="form-group col-md-4">
                                <label>Quantity/Average *</label>
                                <input type="text" name="editAccessoriesQuantity" id="editAccessoriesQuantity" class="form-control" onkeyup="updateAccessoriesCost('edit');">
                                <div class="invalid-feedback" id="error_editAccessories_qty"></div>	
                            </div>-->
                        
                            <div class="form-group col-md-3">
                                <label>Unit *</label>
                                <select name="editEmbroideryUnit" id="editEmbroideryUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <?php if(strtolower($units[$i]['name']) != 'pcs') continue; ?>
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editEmbroidery_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Cost *</label>
                                <input type="text" name="editEmbroideryCost" id="editEmbroideryCost" class="form-control readonly-field" readonly="">
                                <div class="invalid-feedback" id="error_editEmbroidery_cost"></div>	
                            </div>
                        </div>

                        <div class="form-group">
                            
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <div class="separator-10"></div>
                            <img src="" id="editEmbroideryImg" style="display:none;float:left;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editEmbroideryImg',this);" id="editEmbroideryImg_delete_link" style="float:left;margin-left: 5px;"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editEmbroideryImg_delete_hdn" name="editEmbroideryImg_delete_hdn" value="0">
                            <div class="separator-10">&nbsp;</div>
                            <input type="file" name="editEmbroideryImage" id="editEmbroideryImage" class="form-control" onchange="updateDesignItemImage('editEmbroideryImage');">
                            <div class="invalid-feedback" id="error_editEmbroidery_editEmbroideryImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editEmbroideryComments" id="editEmbroideryComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_editEmbroidery_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer center-footer">
                        <div id="edit-Embroidery-spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editEmbroideryBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editEmbroideryBtn_submit"  class="btn btn-dialog" onclick="updateDesignEmbroidery({{ $design_data['id'] }});">Submit</button>
                        <input type="hidden" name="edit_embroidery_id_edit_hdn" id="edit_embroidery_id_edit_hdn" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="add-design-printing" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Printing</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible elem-hidden" id="addPrintingErrorMessage"></div>

                <form class="" name="addPrintingFrm" id="addPrintingFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Type *</label>
                                <select name="addPrintingType" id="addPrintingType" class="form-control" >
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'printing_type' && $design_items[$i]['pid'] == 0)
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor 
                                </select>    
                                <div class="invalid-feedback" id="error_addPrinting_type"></div>			
                            </div>
                            <div class="form-group col-md-3">
                                <label>Rate *</label>
                                <input type="text" name="addPrintingRate" id="addPrintingRate" class="form-control" onkeyup="updatePrintingCost('add');">
                                <div class="invalid-feedback" id="error_addPrinting_rate"></div>	
                            </div>
                            
                        
                            <div class="form-group col-md-3">
                                <label>Unit *</label>
                                <select name="addPrintingUnit" id="addPrintingUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <?php if(strtolower($units[$i]['name']) != 'pcs') continue; ?>
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_addPrinting_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Cost *</label>
                                <input type="text" name="addPrintingCost" id="addPrintingCost" class="form-control readonly-field" readonly="">
                                <div class="invalid-feedback" id="error_addPrinting_cost"></div>	
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <input type="file" name="addPrintingImage" id="addPrintingImage" class="form-control">
                            <div class="invalid-feedback" id="error_addPrinting_addPrintingImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="addPrintingComments" id="addPrintingComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_addPrinting_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer center-footer">
                        <div id="add-Printing-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addPrintingBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addPrintingBtn_submit"  class="btn btn-dialog" onclick="addDesignPrinting({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="edit-design-printing" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Printing</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible elem-hidden" id="editPrintingErrorMessage"></div>

                <form class="" name="editPrintingFrm" id="editPrintingFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Type *</label>
                                <select name="editPrintingType" id="editPrintingType" class="form-control" >
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'printing_type' && $design_items[$i]['pid'] == 0)
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor 
                                </select>    
                                <div class="invalid-feedback" id="error_editPrinting_type"></div>			
                            </div>
                            <div class="form-group col-md-3">
                                <label>Rate *</label>
                                <input type="text" name="editPrintingRate" id="editPrintingRate" class="form-control" onkeyup="updatePrintingCost('edit');">
                                <div class="invalid-feedback" id="error_editPrinting_rate"></div>	
                            </div>
                            
                        
                            <div class="form-group col-md-3">
                                <label>Unit *</label>
                                <select name="editPrintingUnit" id="editPrintingUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <?php if(strtolower($units[$i]['name']) != 'pcs') continue; ?>
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editPrinting_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Cost *</label>
                                <input type="text" name="editPrintingCost" id="editPrintingCost" class="form-control readonly-field" readonly="">
                                <div class="invalid-feedback" id="error_editPrinting_cost"></div>	
                            </div>
                        </div>

                        <div class="form-group">
                            
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <div class="separator-10"></div>
                            <img src="" id="editPrintingImg" style="display:none;float:left;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editPrintingImg',this);" id="editPrintingImg_delete_link" style="float:left;margin-left: 5px;"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editPrintingImg_delete_hdn" name="editPrintingImg_delete_hdn" value="0">
                            <div class="separator-10">&nbsp;</div>
                            <input type="file" name="editPrintingImage" id="editPrintingImage" class="form-control" onchange="updateDesignItemImage('editPrintingImage');">
                            <div class="invalid-feedback" id="error_editPrinting_editPrintingImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editPrintingComments" id="editPrintingComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_editPrinting_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer center-footer">
                        <div id="edit-Printing-spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editPrintingBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editPrintingBtn_submit"  class="btn btn-dialog" onclick="updateDesignPrinting({{ $design_data['id'] }});">Submit</button>
                        <input type="hidden" name="edit_printing_id_edit_hdn" id="edit_printing_id_edit_hdn" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="edit-design-garment_cmt" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Garment CMT</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible elem-hidden" id="editGarmentCmtErrorMessage"></div>

                <form class="" name="editGarmentCmtFrm" id="editGarmentCmtFrm" type="POST">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="editGarmentCmtName" id="editGarmentCmtName" class="form-control readonly-field" readonly>
                            <div class="invalid-feedback" id="error_editGarmentCmt_name"></div>	
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Rate *</label>
                                <input type="text" name="editGarmentCmtRate" id="editGarmentCmtRate" class="form-control" onkeyup="updateGarmentCmtCost('edit');">
                                <div class="invalid-feedback" id="error_editGarmentCmt_rate"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Unit *</label>
                                <input type="text" name="editGarmentCmtUnit" id="editGarmentCmtUnit" class="form-control" value="PCS" disabled="true">
                            </div>
                        </div>    
                        <div class="form-group" id="garment_cmt_cost_div">
                            <label>Cost *</label>
                            <input type="text" name="editGarmentCmtCost" id="editGarmentCmtCost" class="form-control readonly-field" readonly>
                            <div class="invalid-feedback" id="error_editGarmentCmt_cost"></div>	
                            <input type="hidden" name="garment_cmt_id_edit_hdn" id="garment_cmt_id_edit_hdn" value="">
                        </div>
                        <div class="form-group">
                            <label>Image <span class="image-types">{{$image_types}}</span></label>
                            <img src="" id="editGarmentCmtImg" style="display:none;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editGarmentCmtImg',this);" id="editGarmentCmtImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editGarmentCmtImg_delete_hdn" name="editGarmentCmtImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            <input type="file" name="editGarmentCmtImage" id="editGarmentCmtImage" class="form-control" onchange="updateDesignItemImage('editGarmentCmtImg');">
                            <div class="invalid-feedback" id="error_editGarmentCmt_editGarmentCmtImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editGarmentCmtComments" id="editGarmentCmtComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_editGarmentCmt_comments"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12 mandatory-fields-info">Fields marked with * are mandatory</div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="edit-GarmentCmt-spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editGarmentCmtBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editGarmentCmtBtn_submit" class="btn btn-dialog" onclick="updateDesignGarmentCmt({{ $design_data['id'] }});">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm_delete_rows" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteErrorMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Rows<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-rows-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_rows_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="delete_rows_btn" name="delete_rows_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="error_delete_rows" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteErrorMessage"></div>
                <div class="modal-body">
                    <h6>Select the records to delete<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-dialog" id="error_delete_rows_btn" name="error_delete_rows_btn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="status_updated_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="saveStatusDialogTitle">Success</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="modal-body" id="saveStatusDialogContent"></div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-dialog" id="status_updated_dialog_close_btn" name="status_updated_dialog_close_btn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="design_reviews_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Design Reviews</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="designReviewsErrorMessage"></div>
                <div class="modal-body" id="design_reviews_content"></div>
                <div class="modal-footer center-footer">
                    <button type="button" id="design_reviews_close" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="design_item_image_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document" >
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Design Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="modal-body" id="design_item_image_content"></div>
                <div class="modal-footer center-footer">
                    <button type="button" id="design_item_image_close" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="design_size_variation_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document" >
            <div class="modal-content">
                <form name="SizeVariationForm" id="SizeVariationForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Size Variation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="sizeVariationErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="sizeVariationSuccessMessage"></div>

                <div class="modal-body" id="design_size_variation_content"></div>
                <div class="modal-footer center-footer">
                    <div id="edit-SizeVariation-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" id="editSizeVariationBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id ="editSizeVariationBtn_submit" class="btn btn-dialog" onclick="updateSizeVariation();">Submit</button>
                    <input type="hidden" name="size_var_inst_id" id="size_var_inst_id" value="">
                    <input type="hidden" name="size_var_size_ids" id="size_var_size_ids" value="">
                </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script type="text/javascript">var user_type = "{{strtolower($user->user_type)}}";var currency = "{{$currency}}";</script>
@endif
<script src="{{ asset('js/design.js?v=1.21') }}" ></script>
<script src="{{ asset('js/design_common.js?v=1.21') }}" ></script>
@endsection