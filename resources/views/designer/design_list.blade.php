@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Design List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Design List'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="id" id="id" class="form-control" placeholder="Design ID" value="{{request('id')}}">
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Lookup Items'); ?></div>
                </div>    
            </form>    
            <div id="designListErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="designList">
                <div class="separator-10"></div>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead>
                            <tr class="header-tr">
                                <th>ID</th>
                                <th>Style</th>
                                <th>Category</th>
                                <th>Story</th>
                                <th>Product</th>
                                <th>Designer</th>
                                <th>Purchaser Review</th>
                                <th>Mgmt Review</th>
                                <th>Date Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($designs_list);$i++)
                                <tr>
                                    
                                    <td>{{$designs_list[$i]->id}}</td>
                                    <td>{{$designs_list[$i]->sku}}</td>
                                    <td>{{$designs_list[$i]->category_name}}</td>
                                    
                                    <td>{{$designs_list[$i]->story_name}}</td>
                                    <td>{{$designs_list[$i]->design_type_name}}</td>
                                    <td>{{$designs_list[$i]->designer_name}}</td>
                                    <td>{{ucfirst($designs_list[$i]->purchaser_review)}}</td>
                                    <td>{{ucfirst($designs_list[$i]->management_review)}}</td>
                                    
                                    <td>{{date('d M Y',strtotime($designs_list[$i]->created_at))}}</td>
                                    <td>
                                        <a href="{{url('design/detail/'.$designs_list[$i]->id)}}" ><i title="Design Details" class="fas fa-eye"></i></a>&nbsp;&nbsp;
                                        <a href="{{url('purchaser/design/po/list/'.$designs_list[$i]->id)}}" ><i title="Purchase Orders List" class="fas fa-eye"></i></a>&nbsp;&nbsp;
                                    </td>
                                </tr>
                            @endfor

                            @if(is_object($designs_list) && $designs_list->count() == 0)
                                <tr><td colspan="10" align="center">No Records</td></tr>
                            @endif
                        </tbody>
                    </table>
                    @if(is_object($designs_list))
                        {{ $designs_list->withQueryString()->links() }} <p>Displaying {{$designs_list->count()}} of {{ $designs_list->total() }} designs.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="edit_design_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editDesignProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editDesignProductErrorMessage"></div>

                <form class="" name="editDesignProductFrm" id="editDesignProductFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        
                        <div class="form-row">
                            <div class="form-group col-md-3" >
                                <label>Product Name</label>
                                <input id="product_name_edit" type="text" class="form-control" name="product_name_edit" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_product_name_edit"></div>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label>Color</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger"><span id="color_label_edit">Select One</span>
                                            <i class="fa fa-angle-down select_opt custom-select-arrow" ></i> 
                                        </div>
                                        <div class="custom-options">
                                            @for($i=0;$i<count($color_list);$i++)
                                                <span class="span-custom-option span-color" data-value="{{$color_list[$i]['id']}}" style="background-color: {{$color_list[$i]['description']}};">{{$color_list[$i]['name']}}</span><span class="span-custom-option span-text span-text-{{$color_list[$i]['id']}}" data-value="{{$color_list[$i]['id']}}" >{{$color_list[$i]['name']}}</span>
                                            @endfor
                                        </div>
                                    </div>
                                    <input type="hidden" name="color_id" id="color_id" value="" class="color-hdn">
                                </div>
                                <div class="invalid-feedback" id="error_validation_color_id_edit"></div>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label>Category</label>
                                <select name="product_category_edit" id="product_category_edit"  class="form-control" onchange="getDesignProductSubcategories(this.value,'product_subcategory_edit');getDesignProductHsnCode(this.value,'product_hsn_code_edit');">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($category_list);$i++)
                                        <option value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_category_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>SubCategory</label>
                                <select name="product_subcategory_edit" id="product_subcategory_edit"  class="form-control">
                                    <option value="">Select</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_subcategory_edit"></div>
                            </div>
                            <!--
                            <div class="form-group col-md-3">
                                <label>Barcode</label>
                                <input id="product_barcode_edit" type="text" class="form-control" name="product_barcode_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_product_barcode_edit"></div>
                            </div>
                            
                            
                            
                            <div class="form-group col-md-3">
                                <label>GST Inclusive </label>
                                <select name="gst_inclusive_edit" id="gst_inclusive_edit"  class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_gst_inclusive_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Vendor Product SKU</label>
                                <input id="vendor_product_sku_edit" type="text" class="form-control" name="vendor_product_sku_edit" value="" >
                                <div class="invalid-feedback" id="error_validation_vendor_product_sku_edit"></div>
                            </div>-->
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Story</label>
                                <select name="story_id_edit" id="story_id_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($story_list);$i++)
                                        <option value="{{$story_list[$i]['id']}}">{{$story_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_story_id_edit"></div>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label>Season</label>
                                <select name="season_id_edit" id="season_id_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($season_list);$i++)
                                        <option value="{{$season_list[$i]['id']}}">{{$season_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_season_id_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Base Price</label>
                                <input id="product_base_price_edit" type="text" class="form-control" name="product_base_price_edit" value=""  readonly="true">
                                <div class="invalid-feedback" id="error_validation_product_base_price_edit"></div>
                            </div> 
                            <div class="form-group col-md-3">
                                <label>Sale Price</label>
                                <input id="product_sale_price_edit" type="text" class="form-control" name="product_sale_price_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_product_sale_price_edit"></div>
                            </div>
                            
                        </div>
                        <div class="form-row">
                            
                           
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-4" >
                                <label>Description</label>
                                <textarea name="product_description_edit" id="product_description_edit" style="height:40px;" class="form-control"></textarea>
                                <div class="invalid-feedback" id="error_validation_product_description_edit"></div>
                            </div>
                            <div class="form-group col-md-2">
                                <label>HSN Code</label>
                                <input id="product_hsn_code_edit" type="text" class="form-control" name="product_hsn_code_edit" value="" readonly="true">
                                <div class="invalid-feedback" id="error_validation_product_hsn_code_edit"></div>
                            </div>
                        </div>    
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="design_product_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="design_product_edit_cancel" name="design_product_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="design_product_edit_submit" name="design_product_edit_submit" class="btn btn-dialog" onclick="updateDesignProduct();">Submit</button>
                        <input id="design_edit_id" type="hidden" class="form-control" name="design_edit_id" value=""  >
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($designs_list->total(),1000,'/design/list','Download Designs List','Designs Items'); ?>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/design.js') }}" ></script>
<script src="{{ asset('js/design_common.js') }}" ></script>
@endsection
