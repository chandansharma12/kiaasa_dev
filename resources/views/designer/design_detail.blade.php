@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))

    @if($history == 0)
        <script>var design_id = {{ $design_data['id'] }}</script>
    @else
        <script>var design_id = {{ $design_data['design_id'] }}</script>
    @endif
    <!-- Strat breadcrumb -->
    <nav aria-label="breadcrumb" class="page_bbreadcrumb">
        
        <?php if(in_array($user->user_type, array(1,4,7)) ) $list_link = 'design/list';elseif($user->user_type == 2) $list_link = 'production/design-list';
        elseif($user->user_type == 3) $list_link = 'purchaser/design-list';else $list_link = ''; ?>
        <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Design List','link'=>$list_link),array('name'=>'Design Detail')); ?>
        <?php echo CommonHelper::createBreadCrumb($breadCrumbArray); ?>
        <?php /* ?>
        @if(CommonHelper::getDataRole($user->user_type) == 2)
            @if(CommonHelper::hasPermission('display_production_history_links',$user->user_type)) 
                @if(isset($design_info->production_version) && $design_info->production_version > 1)
                    <ul style="display:flex;padding-left: 0px;">
                        @for($i=1;$i<=$design_info->production_version;$i++)
                            <?php if($version != '') { if($version == $i) $css = 'font-weight:bold;';else $css = ''; }else { if($i == $design_info->production_version) $css = 'font-weight:bold;';else $css = '';  } ?>
                            <li style="list-style: none;<?php echo $css; ?>"><a href="{{url('design/detail/'.$design_info->id.'/'.$i.'/2')}}">Version {{$i}}  </a></li>
                            @if($i < $design_info->production_version) &nbsp;|&nbsp; @endif
                        @endfor     
                    </ul>    
                @endif
            @endif
        @else
            @if(CommonHelper::hasPermission('display_design_history_links',$user->user_type)) 
                @if(isset($design_info->version) && $design_info->version > 1)
                    <ul style="display:flex;padding-left: 0px;">
                        @for($i=1;$i<=$design_info->version;$i++)
                            <?php if($version != '') { if($version == $i) $css = 'font-weight:bold;';else $css = ''; }else { if($i == $design_info->version) $css = 'font-weight:bold;';else $css = '';  } ?>
                            <li style="list-style: none;<?php echo $css; ?>"><a href="{{url('design/detail/'.$design_info->id.'/'.$i.'/1')}}">Version {{$i}}  </a></li>
                            @if($i < $design_info->version) &nbsp;|&nbsp; @endif
                        @endfor     
                    </ul>    
                @endif
            @endif
        @endif

        <h2 class="page_heading">Design Detail <span style="font-size:70%;">(Design Version: {{$design_data['version']}}, Status: <span id="reviewer_status_title">{{$design_data['reviewer_status']}}</span>, <a href="javascript:;"  onclick="getDesignReviews('design');">Reviews List</a>)
                (Production Version: {{$design_data['production_version']}}, Status: <span id="reviewer_status_title">{{$design_data['production_status']}}</span>, <a href="javascript:;"  onclick="getDesignReviews('production');">Reviews List</a>)
            </span>
        </h2>
        <?php */ ?>
        
        <h2 class="page_heading">Design Detail</h2>
    </nav> 
    <!-- End breadcrumb -->
    <!-- Strat Form Area -->  
    <section class="form_area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-success alert-dismissible" style="display:none" id="savestatusMessage"></div>
                    <div class="alert alert-danger alert-dismissible" style="display:none" id="errortatusMessage"></div>
                    <form id="update_design_data" name="update_design_data">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="Season">Season</label>						
                                {{$design_data['season_name']}}    
                            </div> 

                            <div class="form-group col-md-2"></div>

                            <div class="form-group col-md-3">
                                <label for="Product">Product</label>						
                                {{$design_data['design_type_name']}}    
                            </div> 
                        </div>    

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="Color">Color</label>						
                                {{$design_data['color_name']}}    
                            </div> 

                            <div class="form-group col-md-2"></div>

                            <div class="form-group col-md-3">
                                <label for="Category">Category</label>						
                                {{$design_data['category_name']}}    
                            </div> 
                        </div>    

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="Sub Category">Sub Category</label>						
                                {{$design_data['subcat_name']}}    
                            </div> 

                            <div class="form-group col-md-2"></div>

                            <div class="form-group col-md-3">
                                <label for="Story">Story</label>						
                                {{$design_data['story_name']}}    
                            </div> 
                        </div>  

                        <div class="form-row">
                             <div class="form-group col-md-3">
                                <label for="Sub Category">Article Number</label>						
                                {{$design_data['sku']}}    
                            </div> 

                            <div class="form-group col-md-2"></div>

                            <div class="form-group col-md-3">
                                <label for="Size">Sizes</label>						
                                @for($i=0;$i<count($design_data['design_sizes']);$i++)
                                    <span>{{$design_data['design_sizes'][$i]['size']}}</span> &nbsp;
                                @endfor    
                            </div> 
                        </div>      
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="Sub Category">Product Name</label>						
                                {{$design_data['product_name']}}    
                            </div> 

                            <div class="form-group col-md-2"></div>

                            <div class="form-group col-md-3">
                                <label for="Story">Sale Price</label>						
                                {{$currency}} {{$design_data['mrp']}}    
                            </div> 
                        </div>

                        <input type="hidden" name="is_requisition_created" id="is_requisition_created" value="{{$design_data['is_requisition_created']}}">
                        <input type="hidden" name="design_status" id="design_status" value="$design_data['design_status']">
                    </form> 
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="Article">Images</label>
                            <form method="post" id="upload_image_form" name="upload_image_form" enctype="multipart/form-data">
                                <div class="img_upload">
                                    <div class="img_group img_width" >
                                        @if(!empty($images['front']['image_name']) && file_exists(public_path('images/design_images/'.$design_data['design_id'].'/thumbs/'.$images['front']['image_name'])))
                                            <img onclick="displayDialogImage('{{asset('images/design_images/'.$design_data['design_id'])}}/{{$images['front']['image_name']}}');" src="{{asset('images/design_images/'.$design_data['design_id'])}}/thumbs/{{$images['front']['image_name']}}" alt="Front View" class="img-thumbnail design-img"> 
                                        @endif
                                        <span>Front View</span>
                                    </div> 

                                    <div class="img_group img_width" >
                                        @if(!empty($images['back']['image_name']) && file_exists(public_path('images/design_images/'.$design_data['design_id'].'/thumbs/'.$images['back']['image_name'])))
                                            <img onclick="displayDialogImage('{{asset('images/design_images/'.$design_data['design_id'])}}/{{$images['back']['image_name']}}');" src="{{asset('images/design_images/'.$design_data['design_id'])}}/thumbs/{{$images['back']['image_name']}}" alt="Back View" class="img-thumbnail design-img"> 
                                        @endif
                                        <span>Back View</span>
                                    </div> 

                                    <div class="img_group img_width" >
                                        @if(!empty($images['side']['image_name']) && file_exists(public_path('images/design_images/'.$design_data['design_id'].'/thumbs/'.$images['side']['image_name'])))
                                            <img onclick="displayDialogImage('{{asset('images/design_images/'.$design_data['design_id'])}}/{{$images['side']['image_name']}}');" src="{{asset('images/design_images/'.$design_data['design_id'])}}/thumbs/{{$images['side']['image_name']}}" alt="Side View" class="img-thumbnail design-img"> 
                                        @endif
                                        <span>Side View</span>
                                    </div> 

                                    <div class="img_group img_width" >
                                        @if(!empty($images['top']['image_name']) && file_exists(public_path('images/design_images/'.$design_data['design_id'].'/thumbs/'.$images['top']['image_name'])))
                                            <img onclick="displayDialogImage('{{asset('images/design_images/'.$design_data['design_id'])}}/{{$images['top']['image_name']}}');" src="{{asset('images/design_images/'.$design_data['design_id'])}}/thumbs/{{$images['top']['image_name']}}" alt="Top View" class="img-thumbnail design-img"> 
                                        @endif
                                        <span>Top View</span>
                                    </div> 
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
                                        <tr><th>Purchaser Review:</th><td>{{ucfirst($design_data['purchaser_review'])}}</td></tr>
                                        @if(!empty($design_data['purchaser_review_comment']))
                                            <tr><th>Purchaser Comments:</th><td>{{$design_data['purchaser_review_comment']}}  ({{date('d-m-Y',strtotime($design_data['purchaser_review_date']))}})</td></tr>
                                        @endif
                                        <tr><th>Mgmt Review:</th><td>{{ucfirst($design_data['management_review'])}}</td></tr>
                                        @if(!empty($design_data['management_review_comment']))
                                            <tr><th>Mgmt Comments:</th><td>{{$design_data['management_review_comment']}} ({{date('d-m-Y',strtotime($design_data['management_review_date']))}})</td></tr>
                                        @endif
                                    @endif
                                </table>
                            </div>				
                        </div>  

                    </div>	
                    <div class="form-row">
                        <div class="col-md-12"> 
                            <div class="requirements_blk"> 
                                <h2>Requirements</h2>
                                <div style="display:none;" id="requirementsMessage"></div>

                                <div class="tabs_blk"> 
                                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                        <a class="nav-link active" id="v-pills-Fabrics-tab" data-toggle="pill" href="#v-pills-Fabrics" role="tab" aria-controls="v-pills-Fabrics" aria-selected="true" onclick="displayTabData('fabric');">Fabrics</a>
                                        <a class="nav-link" id="v-pills-Accessories-tab" data-toggle="pill" href="#v-pills-Accessories" role="tab" aria-controls="v-pills-Accessories" aria-selected="false" onclick="displayTabData('accessories');">Accessories</a>
                                        <!--<a class="nav-link" id="v-pills-Process-tab" data-toggle="pill" href="#v-pills-Process" role="tab" aria-controls="v-pills-Process" aria-selected="false" onclick="displayTabData('process');">Fabric Process</a>
                                        <a class="nav-link" id="v-pills-Product_process-tab" data-toggle="pill" href="#v-pills-Product_process" role="tab" aria-controls="v-pills-Product_process" aria-selected="true" onclick="displayTabData('product_process');">Production Process</a>-->
                                        <a class="nav-link" id="v-pills-Embroidery-tab" data-toggle="pill" href="#v-pills-Embroidery" role="tab" aria-controls="v-pills-Embroidery" aria-selected="false" onclick="displayTabData('embroidery');">Embroidery</a>
                                        <a class="nav-link" id="v-pills-Printing-tab" data-toggle="pill" href="#v-pills-Printing" role="tab" aria-controls="v-pills-Printing" aria-selected="false" onclick="displayTabData('printing');">Printing</a>
                                        <a class="nav-link" id="v-pills-Garment_cmt-tab" data-toggle="pill" href="#v-pills-Garment_cmt" role="tab" aria-controls="v-pills-Garment_cmt" aria-selected="false" onclick="displayTabData('garment_cmt');">Garment CMT</a>
                                        <a class="nav-link" id="v-pills-Packaging-tab" data-toggle="pill" href="#v-pills-Packaging" role="tab" aria-controls="v-pills-Packaging" aria-selected="false" onclick="displayTabData('packaging_sheet');">Packaging Sheet</a>
                                        <a class="nav-link" id="v-pills-Specification_sheet-tab" data-toggle="pill" href="#v-pills-Specification_sheet" role="tab" aria-controls="v-pills-Specification_sheet" aria-selected="true" onclick="displayTabData('specification_sheet');">Specification Sheet</a>
                                    </div>
                                    <div class="tab-content" id="v-pills-tabContent">
                                        <div class="tab-pane fade show active" id="v-pills-Fabrics" role="tabpanel" aria-labelledby="v-pills-Fabrics-tab">

                                            @if(CommonHelper::hasPermission('upload_production_document',$user->user_type) && strtolower($design_data['reviewer_status']) == 'approved' && (empty($design_data['production_status']) || strtolower($design_data['production_status']) == 'rejected'))
                                                <form method="post" id="upload_design_document_form" name="upload_design_document_form" enctype="multipart/form-data">
                                                    <div class="img_upload prod-documents" >
                                                        <div style="@if(!isset($design_support_files[1])) display:none; @endif" id="documentLinkDiv_1">
                                                            <a class="documentLink_1" href="{{url('production/downnloaddesigndocument/'.$design_data['id'].'/1')}}" title="Download">Document 1</a> &nbsp;
                                                            <a class="documentLink_2" href="{{url('production/downnloaddesigndocument/'.$design_data['id'].'/1')}}" title="Download"><i class="fas fa-download"></i></a>
                                                            <a class="documentLink_3" href="javascript:;" onclick="deleteDesignDocument(1);" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                                        </div>    
                                                         <div style="@if(!isset($design_support_files[2])) display:none; @endif" id="documentLinkDiv_2">
                                                            <a class="documentLink_1" href="{{url('production/downnloaddesigndocument/'.$design_data['id'].'/2')}}" title="Download">Document 2</a>
                                                            <a class="documentLink_2" href="{{url('production/downnloaddesigndocument/'.$design_data['id'].'/2')}}" title="Download"><i class="fas fa-download"></i></a>
                                                            <a class="documentLink_2" href="javascript:;" onclick="deleteDesignDocument(2);" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                                        </div>
                                                        <div class="img_input img_width" id="documentUploadDiv_1" style="@if(isset($design_support_files[1])) display:none; @endif">
                                                            <a href="javascript:;" style="top:40%;">Upload Document 1</a>
                                                            <img src="{{asset('images/upload_img.jpg')}}" alt="" style="width:100px;"> 
                                                            <input type="file" class="form-control-file" id="design_document_1" name="design_document_1" onchange="uploadDesignDocument(1);"> 
                                                            <span></span>
                                                        </div>
                                                        <div class="img_input img_width" id="documentUploadDiv_2" style="width:auto;@if(isset($design_support_files[2])) display:none; @endif">
                                                            <a href="javascript:;" style="top:40%;">Upload Document 2</a>
                                                            <img src="{{asset('images/upload_img.jpg')}}" alt="" style="width:100px;"> 
                                                            <input type="file" class="form-control-file" id="design_document_2" name="design_document_2" onchange="uploadDesignDocument(2);"> 
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                    <div class="clear"></div><div class="pull-right image-types" >(Pdf, Jpeg, Jpg, Png, Gif)</div>
                                                    <input type="hidden" name="document_type" id="document_type" value=""/>
                                                </form>
                                            @endif

                                            <div id="req_tab_data"></div>

                                        </div>
                                    </div>
                                </div>   
                            </div>   
                        </div>   
                    </div>
                    
                    @if($user->user_type == 3 && strtolower($design_data['purchaser_review']) == 'pending')
                        <div class="table-responsive">
                            <div class="table_actions">
                                <button class="btn_box" id="purchaserReviewDesign" name="purchaserReviewDesign" onclick="designReviewByPurchaser();">Review Design</button>
                            </div>
                        </div>
                    @endif
                    
                    @if($user->user_type == 12 && strtolower($design_data['purchaser_review']) == 'approved' && strtolower($design_data['management_review']) == 'pending')
                        <div class="table-responsive">
                            <div class="table_actions">
                                <button class="btn_box" id="managementReviewDesign" name="managementReviewDesign" onclick="designReviewByManagement();">Review Design</button>
                            </div>
                        </div>
                    @endif
                    
                    <br/>
                    <?php /* ?>
                    @if(CommonHelper::hasPermission('request_production_review',$user->user_type) && strtolower($design_data['reviewer_status']) == 'approved' && (empty($design_data['production_status']) || strtolower($design_data['production_status']) == 'rejected') )
                        <div class="table-responsive">
                            <div class="table_actions">
                                <button class="btn_box" id="submitProductionReviewBtn" name="submitProductionReviewBtn" onclick="submitProductionReview();">Send for approval</button>
                            </div>
                        </div>
                    @endif

                    @if(CommonHelper::hasPermission('send_production_notification',$user->user_type) && isset($display_ph_head_notif_btn) && $display_ph_head_notif_btn == true)
                        <div class="table-responsive">
                            <div class="table_actions">
                                <button class="btn_box" id="submitNotificationBtn" name="submitNotificationBtn" onclick="sendNotification(2,design_id);">Send Review Notification</button>
                            </div>
                        </div>
                    @endif

                    @if(CommonHelper::hasPermission('review_designer_design',$user->user_type) && strtolower($design_data['reviewer_status']) == 'waiting')
                        <div class="form-row">
                            <div class="col-md-12">
                                <button class="btn_box" id="reviewDesignBtn" name="reviewDesignBtn" style="border:none;float:right;" onclick="displayReviewDesign();" >Review Design</button>    
                            </div> 
                        </div>
                    @endif

                    @if(CommonHelper::hasPermission('review_production_design',$user->user_type) && strtolower($design_data['reviewer_status']) == 'approved' && strtolower($design_data['production_status']) == 'waiting')
                        <div class="form-row">
                            <div class="col-md-12">
                                <button class="btn_box" id="productionReviewDesignBtn" name="productionReviewDesignBtn" style="border:none;float:right;" onclick="displayProductionReviewDesign();" >Review Design</button>    
                            </div> 
                        </div>
                    @endif
                    <?php */ ?>
                    
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
                                            <tr><th></th><th colspan="4">Total</th><th>{{$currency}} {{$total_data['print']}}</th></tr>
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

    <div class="modal fade" id="purchaser_design_review_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Review Design</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="purchaserReviewDesignSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="purchaserReviewDesignErrorMessage"></div>

                <form class="" name="purchaserReviewDesignFrm" id="purchaserReviewDesignFrm" type="POST">
                    <div class="modal-body">

                        <div class="form-group" id="review_status_div">
                            <label>Review Status</label>
                            
                            <select name="purchaser_review_sel" id="purchaser_review_sel" class="form-control">
                                <option value="">Select One</option>
                                <option value="approved">Approved</option>
                                <option value="disapproved">Disapproved</option>
                            </select>   
                            <div class="invalid-feedback" id="error_design_purchaser_review_sel"></div>	
                        </div>

                        <div class="form-group">
                            <label>Comments</label>
                            <textarea class="form-control" name="purchaser_review_comment" id="purchaser_review_comment" maxlength="250"></textarea>
                            <div class="invalid-feedback" id="error_design_purchaser_review_comment"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="review_design_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="purchaser_review_design_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="purchaser_review_design_submit" class="btn btn-dialog" onclick="submitDesignReviewByPurchaser();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
        <div class="modal fade" id="management_design_review_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Review Design</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="managementReviewDesignSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="managementReviewDesignErrorMessage"></div>

                <form class="" name="managementReviewDesignFrm" id="managementReviewDesignFrm" type="POST">
                    <div class="modal-body">

                        <div class="form-group" id="review_status_div">
                            <label>Review Status</label>
                            <select name="management_review_sel" id="management_review_sel" class="form-control">
                                <option value="">Select One</option>
                                <option value="approved">Approved</option>
                                <option value="disapproved">Disapproved</option>
                            </select>   
                            <div class="invalid-feedback" id="error_design_management_review_sel"></div>	
                        </div>

                        <div class="form-group">
                            <label>Comments</label>
                            <textarea class="form-control" name="management_review_comment" id="management_review_comment" maxlength="250"></textarea>
                            <div class="invalid-feedback" id="error_design_management_review_comment"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="review_design_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="management_review_design_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="management_review_design_submit" class="btn btn-dialog" onclick="submitDesignReviewByManagement();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="review_design_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Review Design</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="reviewDesignSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="reviewDesignErrorMessage"></div>

                <form class="" name="reviewDesignFrm" id="reviewDesignFrm" type="POST">
                    <div class="modal-body">

                        <div class="form-group" id="review_status_div">
                            <label>Review Status</label>
                            <span id="design_status_text"></span>
                            <select name="design_status_sel" id="design_status_sel" class="form-control">
                                <option value="">Select One</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>   
                            <div class="invalid-feedback" id="error_design_design_status_sel"></div>	
                        </div>

                        <div class="form-group">
                            <label>Comments</label>
                            <textarea class="form-control" name="comment" id="comment"></textarea>
                            <div class="invalid-feedback" id="error_design_comment"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="review_design_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="review_design_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="review_design_submit" class="btn btn-dialog" onclick="reviewDesign();">Submit</button>
                        <input type="hidden" name="update_status" id="update_status" value="">
                        <input type="hidden" name="review_id" id="review_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="design_reviews_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="designReviewsDialogTitle">Design Reviews</h5>
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

    <div class="modal fade" id="add-design-product_process" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Product Process</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addProductProcessErrorMessage"></div>

                <form class="" name="addProductProcessFrm" id="addProductProcessFrm" type="POST">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Body Part</label>
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
                            <label>Name</label>
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
                            <label>Designer Cost</label>
                            <input type="text" name="addProductProcessDesignerCost" id="addProductProcessDesignerCost" class="form-control" >
                            <div class="invalid-feedback" id="error_addProductProcess_cost"></div>	
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="addProductProcessImage" id="addProductProcessImage" class="form-control" onchange="updateDesignItemImage('addProductProcessImg');">
                            <div class="invalid-feedback" id="error_addProductProcess_addProductProcessImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="addProductProcessComments" id="addProductProcessComments" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_addProductProcess_comments"></div>	
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

    <?php $pp_editable_fields = CommonHelper::getEditableFields(5,$user->user_type); ?>
    <div class="modal fade" id="edit-design-product_process" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Product Process</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="editProductProcessErrorMessage"></div>

                <form class="" name="editProductProcessFrm" id="editProductProcessFrm" type="POST">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Body Part</label>
                            <select name="editProductProcessBodyPart" id="editProductProcessBodyPart" class="form-control" <?php if(!in_array('body_part_id',$pp_editable_fields)) echo 'disabled'; ?>>
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
                            <label>Name</label>
                            <select name="editProductProcessName" id="editProductProcessName" class="form-control" <?php if(!in_array('design_item_id',$pp_editable_fields)) echo 'disabled'; ?>>
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
                            <label>Designer Cost</label>
                            <input type="text" name="editProductProcessDesignerCost" id="editProductProcessDesignerCost" class="form-control" >
                            <div class="invalid-feedback" id="error_editProductProcess_cost"></div>	
                        </div>

                       <div class="form-group">
                            <label>Image</label>
                            <img src="" id="editProductProcessImg" style="display:none;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editProductProcessImg',this);" id="editProductProcessImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editProductProcessImg_delete_hdn" name="editProductProcessImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            <input type="file" name="editProductProcessImage" id="editProductProcessImage" class="form-control" onchange="updateDesignItemImage('editProductProcessImg');">
                            <div class="invalid-feedback" id="error_editProductProcess_editProductProcessImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editProductProcessComments" id="editProductProcessComments" class="form-control" <?php if(!in_array('comments',$pp_editable_fields)) echo 'disabled'; ?>></textarea>
                            <div class="invalid-feedback" id="error_editProductProcess_comments"></div>	
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
                <?php /* ?><div class="modal-header">
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

    <?php $fabric_editable_fields = CommonHelper::getEditableFields(1,$user->user_type); ?>
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
                                <label>Body Part</label>
                                <select name="editFabricBodyPart" id="editFabricBodyPart" class="form-control" <?php if(!in_array('body_part_id',$fabric_editable_fields)) echo 'disabled'; ?>>
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
                                <label>Fabric Type</label>
                                <select name="editFabricName" id="editFabricName" class="form-control" onchange="updateFabricNameData(this.value,'edit',0,'');" <?php if(!in_array('design_item_id',$fabric_editable_fields)) echo 'disabled'; ?>>
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
                                <label>GSM</label>
                                <select name="editFabricGSM" id="editFabricGSM" class="form-control" <?php if(!in_array('design_item_id',$fabric_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                </select>   
                                <div class="invalid-feedback" id="error_editFabric_gsm_id"></div>	
                            </div>
                        </div>    

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Color</label>
                                <select name="editFabricColor" id="editFabricColor" class="form-control" <?php if(!in_array('design_item_id',$fabric_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++):
                                        @if(strtolower($design_items[$i]['type']) == 'color')
                                            <option style="background-color: {{$design_items[$i]['description']}}" value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor;     
                                </select> 
                                <div class="invalid-feedback" id="error_editFabric_color"></div>	
                            </div>

                            <div class="form-group col-md-3">
                                <label>Width</label>
                                <select name="editFabricWidth" id="editFabricWidth" class="form-control" <?php if(!in_array('design_item_id',$fabric_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                </select> 
                                <div class="invalid-feedback" id="error_editFabric_width"></div>	
                            </div>

                            <div class="form-group col-md-3">
                                <label>Unit</label>
                                <select name="editFabricMasterUnit" id="editFabricMasterUnit" class="form-control" <?php if(!in_array('design_item_id',$fabric_editable_fields)) echo 'disabled'; ?>> 
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editFabric_master_unit_id"></div>	
                                <input type="hidden" name="fabric_id_edit_hdn" id="fabric_id_edit_hdn" value="">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Content</label>
                                <select name="editFabricContent" id="editFabricContent" class="form-control" <?php if(!in_array('design_item_id',$fabric_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                </select> 
                                <div class="invalid-feedback" id="error_editFabric_content_id"></div>	
                            </div>
                        </div>   

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Average</label>
                                <input type="text" name="editFabricAvg" id="editFabricAvg" class="form-control" onkeyup="updateFabricCost('edit');" <?php if(!in_array('avg',$fabric_editable_fields)) echo 'disabled'; ?>>
                                <div class="invalid-feedback" id="error_editFabric_avg"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Unit</label>
                                <select name="editFabricUnit" id="editFabricUnit" class="form-control" <?php if(!in_array('unit_id',$fabric_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_editFabric_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Rate</label>
                                <input type="text" name="editFabricRate" id="editFabricRate" class="form-control" onkeyup="updateFabricCost('edit');" <?php if(!in_array('rate',$fabric_editable_fields)) echo 'disabled'; ?>>
                                <div class="invalid-feedback" id="error_editFabric_rate"></div>	
                            </div>
                            <div class="form-group col-md-3">
                                <label>Cost</label>
                                <input type="text" name="editFabricCost" id="editFabricCost" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_editFabric_cost"></div>	
                            </div>
                        </div>    
                        <div class="form-row ">
                            <div class="form-group col-md-12" >
                                <label>Comments</label>
                                <textarea name="editFabricComments" id="editFabricComments" class="form-control" style="height:40px;" <?php if(!in_array('comments',$fabric_editable_fields)) echo 'disabled'; ?>></textarea>
                                <div class="invalid-feedback" id="error_editFabric_comments"></div>	
                            </div>
                        </div>        

                        <div class="form-row" >
                            <div class="form-group" >
                                <label>Image</label>
                                <div class="separator-10"></div>
                                <img src="" id="editFabricImg" style="display:none;float:left;" class="edit-design-image"/>
                                <a href="javascript:;" onclick="deleteDesignItemImage('editFabricImg',this);" id="editFabricImg_delete_link" style="float:left;margin-left: 5px;"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                                <input type="hidden" id="editFabricImg_delete_hdn" name="editFabricImg_delete_hdn" value="0">
                                <div class="separator-10">&nbsp;</div>
                                <input type="file" name="editFabricImage" id="editFabricImage" class="form-control" onchange="updateDesignItemImage('editFabricImg');" <?php if(!in_array('image_name',$fabric_editable_fields)) echo 'disabled'; ?>>
                                <div class="invalid-feedback" id="error_editFabric_editFabricImage"></div>	
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

    <?php $acc_editable_fields = CommonHelper::getEditableFields(2,$user->user_type); ?>
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
                                <label>Category</label>
                               <select name="editAccessoriesCategory" id="editAccessoriesCategory" class="form-control" onchange="getAccessorySubcategories(this.value,'editAccessoriesSubCategory',0);" <?php if(!in_array('design_item_id',$acc_editable_fields)) echo 'disabled'; ?>>
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
                                <label>Sub Category</label>
                                <select name="editAccessoriesSubCategory" id="editAccessoriesSubCategory" class="form-control" onchange="getAccessorySize(this.value,'editAccessoriesSize',0);" <?php if(!in_array('design_item_id',$acc_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_editAccessories_subcategory_id"></div>			
                            </div>
                            <div class="form-group col-md-4">
                                <label>Size</label>
                                <select name="editAccessoriesSize" id="editAccessoriesSize" class="form-control" <?php if(!in_array('design_item_id',$acc_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_editAccessories_size"></div>			
                            </div>
                        </div>
                        <div class="form-row ">
                            <div class="form-group col-md-4">
                                <label>Color</label>
                                <select name="editAccessoriesColor" id="editAccessoriesColor" class="form-control" <?php if(!in_array('design_item_id',$acc_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++):
                                        @if(strtolower($design_items[$i]['type']) == 'color')
                                            <option style="background-color: {{$design_items[$i]['description']}}" value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor;     
                                </select>    
                                <div class="invalid-feedback" id="error_editAccessories_color_id"></div>	
                            </div>
                            <div class="form-group col-md-4">
                                <label>Rate</label>
                                <input type="text" name="editAccessoriesRate" id="editAccessoriesRate" class="form-control" onkeyup="updateAccessoriesCost('edit');" <?php if(!in_array('rate',$acc_editable_fields)) echo 'disabled'; ?>>
                                <div class="invalid-feedback" id="error_editAccessories_rate"></div>	
                            </div>
                            <div class="form-group col-md-4">
                                <label>Quantity/Average</label>
                                <input type="text" name="editAccessoriesQuantity" id="editAccessoriesQuantity" class="form-control" onkeyup="updateAccessoriesCost('edit');" <?php if(!in_array('avg',$acc_editable_fields)) echo 'disabled'; ?>>
                                <div class="invalid-feedback" id="error_editAccessories_qty"></div>	
                            </div>
                        </div>    
                        <div class="form-row ">
                            <div class="form-group col-md-6">
                                <label>Unit</label>
                                <select name="editAccessoriesUnit" id="editAccessoriesUnit" class="form-control" <?php if(!in_array('unit_id',$acc_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++):
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor;    
                                </select>    
                                <div class="invalid-feedback" id="error_editAccessories_unit_id"></div>	
                                <input type="hidden" name="accessories_id_edit_hdn" id="accessories_id_edit_hdn" value="">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Cost</label>
                                <input type="text" name="editAccessoriesCost" id="editAccessoriesCost" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_editAccessories_cost"></div>	
                            </div>
                        </div>   
                        <div class="form-group">
                            <label>Image</label>
                            <img src="" id="editAccessoriesImg" style="display:none;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editAccessoriesImg',this);" id="editAccessoriesImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editAccessoriesImg_delete_hdn" name="editAccessoriesImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            <input type="file" name="editAccessoriesImage" id="editAccessoriesImage" class="form-control" onchange="updateDesignItemImage('editAccessoriesImg');" <?php if(!in_array('image_name',$acc_editable_fields)) echo 'disabled'; ?>>
                            <div class="invalid-feedback" id="error_editAccessories_editAccessoriesImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editAccessoriesComments" id="editAccessoriesComments" class="form-control" <?php if(!in_array('comments',$acc_editable_fields)) echo 'disabled'; ?>></textarea>
                            <div class="invalid-feedback" id="error_editAccessories_comments"></div>	
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

    <?php $fp_editable_fields = CommonHelper::getEditableFields(3,$user->user_type); ?>
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
                            <select name="editProcessFabric" id="editProcessFabric" class="form-control" onchange="updateProcessCost('edit');" <?php if(!in_array('design_item_id',$fp_editable_fields)) echo 'disabled'; ?>>
                                <option value="">Select One</option>
                            </select>    
                            <div class="invalid-feedback" id="error_editProcess_fabric_id"></div>	
                        </div>
                        <div class="form-row ">
                            <div class="form-group col-md-6">
                                <label>Category</label>
                                <select name="editProcessCategory" id="editProcessCategory" class="form-control" onchange="getProcessTypes(this.value,'editProcessType',0);" <?php if(!in_array('design_item_id',$fp_editable_fields)) echo 'disabled'; ?>>
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
                                <label>Type</label>
                                <select name="editProcessType" id="editProcessType" class="form-control" <?php if(!in_array('design_item_id',$fp_editable_fields)) echo 'disabled'; ?>>
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_editProcess_type_id"></div>	
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Rate</label>
                                <input type="text" name="editProcessRate" id="editProcessRate" class="form-control" onkeyup="updateProcessCostWithAvg('edit');" <?php if(!in_array('rate',$fp_editable_fields)) echo 'disabled'; ?>>
                                <div class="invalid-feedback" id="error_editProcess_rate"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Unit</label>
                                <select name="editProcessUnit" id="editProcessUnit" class="form-control" <?php if(!in_array('unit_id',$fp_editable_fields)) echo 'disabled'; ?>>
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
                                <label>Average</label>
                                <input type="text" name="editProcessAvg" id="editProcessAvg" class="form-control" <?php if(in_array('avg',$fp_editable_fields)): ?> onkeyup="updateProcessCostWithAvg('edit');"  <?php else:?> readonly <?php endif; ?> >
                                <div class="invalid-feedback" id="error_editProcess_avg"></div>	
                            </div>
                            <div class="form-group col-md-6">
                                <label>Cost</label>
                                <input type="text" name="editProcessCost" id="editProcessCost" class="form-control readonly-field" readonly>
                                <div class="invalid-feedback" id="error_editProcess_cost"></div>
                                <input type="hidden" name="process_id_edit_hdn" id="process_id_edit_hdn" value="">
                            </div>
                        </div>    
                        <div class="form-group">
                            <label>Image</label>
                            <img src="" id="editProcessImg" style="display:none;" class="edit-design-image"/>
                            <a href="javascript:;" onclick="deleteDesignItemImage('editProcessImg',this);" id="editProcessImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image"></a>
                            <input type="hidden" id="editProcessImg_delete_hdn" name="editProcessImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            <input type="file" name="editProcessImage" id="editProcessImage" class="form-control" onchange="updateDesignItemImage('editProcessImg');" <?php if(!in_array('image_name',$fp_editable_fields)) echo 'disabled'; ?>>
                            <div class="invalid-feedback" id="error_editProcess_editProcessImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editProcessComments" id="editProcessComments" class="form-control" <?php if(!in_array('comments',$fp_editable_fields)) echo 'disabled'; ?>></textarea>
                            <div class="invalid-feedback" id="error_editProcess_comments"></div>	
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

    <?php $ps_editable_fields = CommonHelper::getEditableFields(4,$user->user_type); ?>
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
                            <label>Name</label>
                            <input type="text" name="editPackagingSheetName" id="editPackagingSheetName" class="form-control readonly-field" readonly>
                            <div class="invalid-feedback" id="error_editPackagingSheet_name"></div>	
                        </div>
                        <div class="form-row">
                            <div class="form-group  col-md-6">
                                <label>Rate</label>
                                <input type="text" name="editPackagingSheetRate" id="editPackagingSheetRate" class="form-control" onkeyup="updatePackagingSheetCost('edit');" <?php if(!in_array('rate',$ps_editable_fields)) echo 'disabled'; ?>>
                                <div class="invalid-feedback" id="error_editPackagingSheet_rate"></div>	
                            </div>
                            <div class="form-group  col-md-6">
                                <label>Quantity</label>
                                <input type="text" name="editPackagingSheetQty" id="editPackagingSheetQty" class="form-control" onkeyup="updatePackagingSheetCost('edit');" <?php if(!in_array('avg',$ps_editable_fields)) echo 'disabled'; ?>>
                                <div class="invalid-feedback" id="error_editPackagingSheet_qty"></div>	
                            </div>
                        </div>    
                        <div class="form-group">
                            <label>Cost</label>
                            <input type="text" name="editPackagingSheetCost" id="editPackagingSheetCost" class="form-control readonly-field" readonly>
                            <div class="invalid-feedback" id="error_editPackagingSheet_cost"></div>	
                            <input type="hidden" name="packaging_sheet_id_edit_hdn" id="packaging_sheet_id_edit_hdn" value="">
                        </div>

                         <div class="form-group">
                            <label>Image</label>
                            <img src="" id="editPackagingSheetImg" style="display:none;" class="edit-design-image"/>
                            @if(in_array('image_name',$ps_editable_fields))
                                <a href="javascript:;" onclick="deleteDesignItemImage('editPackagingSheetImg',this);" id="editPackagingSheetImg_delete_link"><img src="{{asset('images/close.png')}}" alt="Delete Image" title="Delete Image" ></a>
                            @endif
                            <input type="hidden" id="editPackagingSheetImg_delete_hdn" name="editPackagingSheetImg_delete_hdn" value="0">
                            <div class="separator-10"></div>
                            
                            <input type="file" name="editPackagingSheetImage" id="editPackagingSheetImage" class="form-control" onchange="updateDesignItemImage('editPackagingSheetImg');" <?php if(!in_array('image_name',$ps_editable_fields)) echo 'disabled'; ?>>
                            <div class="invalid-feedback" id="error_editPackagingSheet_editPackagingSheetImage"></div>	
                        </div>
                        <div class="form-group">
                            <label>Comments</label>
                            <textarea name="editPackagingSheetComments" id="editPackagingSheetComments" class="form-control" <?php if(!in_array('comments',$ps_editable_fields)) echo 'disabled'; ?>></textarea>
                            <div class="invalid-feedback" id="error_editPackagingSheet_comments"></div>	
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

    <div class="modal fade" id="confirm_delete_design_document" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteDocumentErrorMessage"></div>

                <div class="modal-body">
                    <h6>Are you sure to delete Document<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-document-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_document_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_document_btn" name="delete_document_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="review_production_design_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Review Design</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="reviewProductionDesignSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="reviewProductionDesignErrorMessage"></div>

                <form class="" name="reviewProductionDesignFrm" id="reviewProductionDesignFrm" type="POST">
                    <div class="modal-body">

                        <div class="form-group" id="review_production_status_div">
                            <label>Review Status</label>
                            <span id="production_status_text"></span>
                            <select name="production_status_sel" id="production_status_sel" class="form-control">
                                <option value="">Select One</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>   
                            <div class="invalid-feedback" id="error_review_production_status_sel"></div>	
                        </div>

                        <div class="form-group">
                            <label>Comments</label>
                            <textarea class="form-control" name="production_comment" id="production_comment"></textarea>
                            <div class="invalid-feedback" id="error_review_production_comment"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="review_production_design_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="review_production_design_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="review_production_design_submit" class="btn btn-dialog" onclick="reviewProductionDesign();">Submit</button>
                        <input type="hidden" name="update_production_status" id="update_production_status" value="">
                        <input type="hidden" name="review_production_id" id="review_production_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script type="text/javascript" >var user_type = "{{strtolower($user->user_type)}}";var version = "{{$version}}";var history_data = "{{$history}}";var currency = "{{$currency}}";var history_type = "{{$history_type}}";</script>   
@endif
<script src="{{ asset('js/design_detail.js?v=1.1') }}" ></script>
<script src="{{ asset('js/design_common.js?v=1.1') }}" ></script>
@endsection