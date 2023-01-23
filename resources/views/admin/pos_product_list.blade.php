@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Kiaasa Products')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Kiaasa Products'); ?>
    <?php $product_add_permission = CommonHelper::hasRoutePermission('posproductadd',$user->user_type); ?>
    <?php $product_edit_permission = CommonHelper::hasRoutePermission('posproductupdate',$user->user_type); ?>
    <?php $product_csv_permission = CommonHelper::hasRoutePermission('posproductdownloadcsv',$user->user_type); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >

            <div id="updatePosProductStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updatePosProductStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="id" id="id" class="form-control" placeholder="Prod ID" value="{{request('id')}}">
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="prod_name_search" id="prod_name_search" class="form-control" placeholder="Prod Name / Barcode / SKU" value="{{request('prod_name_search')}}">
                    </div>
                    <div class="col-md-1" >
                        <select name="size_search" id="size_search"  class="form-control" >
                            <option value="">Size</option>
                            @for($i=0;$i<count($size_list);$i++)
                                <?php if($size_list[$i]['id'] == request('size_search')) $sel = 'selected';else $sel = ''; ?>    
                                <option {{$sel}} value="{{$size_list[$i]['id']}}">{{$size_list[$i]['size']}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-2" >
                        <select name="color_search" id="color_search"  class="form-control search-select-1">
                            <option value="">Color</option>
                            @for($i=0;$i<count($color_list);$i++)
                                <?php if($color_list[$i]['id'] == request('color_search')) $sel = 'selected';else $sel = ''; ?>    
                                <option {{$sel}} value="{{$color_list[$i]['id']}}">{{$color_list[$i]['name']}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-2" >
                        <select name="category_search" id="category_search"  class="form-control search-select-1" onchange="getPosProductSubcategories(this.value,'search');">
                            <option value="">Category</option>
                            @for($i=0;$i<count($category_list);$i++)
                                <?php if($category_list[$i]['id'] == request('category_search')) $sel = 'selected';else $sel = ''; ?>    
                                <option {{$sel}} value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-2" >
                        <select name="product_subcategory_search" id="product_subcategory_search"  class="form-control search-select-1" >
                            <option value="">Sub Category</option>
                        </select>
                    </div>
                    <div class="col-md-1" >
                        <select name="prod_type" id="prod_type"  class="form-control " >
                            <option value="">-- All Products --</option>
                            <option <?php if(request('prod_type') == 1) echo 'selected'; ?> value="1">{{CommonHelper::getInventoryType(1)}}</option>
                            <option <?php if(request('prod_type') == 2) echo 'selected'; ?> value="2">{{CommonHelper::getInventoryType(2)}}</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                </div>
            </form>
            
            <div class="separator-10"></div>
            
            <form method="post" name="actioForm" id="actionForm">
                <div class="row justify-content-end" >
                    <div class="col-md-12" >
                        @if($product_add_permission)
                            <input type="button" name="addPosProductBtn" id="addPosProductBtn" value="Add Product" class="btn btn-dialog search-element" onclick="addPosProduct();"> &nbsp;&nbsp;
                            <input type="button" name="addStaticPosProductBtn" id="addStaticPosProductBtn" value="Add Static Product" class="btn btn-dialog search-element" onclick="addStaticPosProduct();">&nbsp;&nbsp;
                            <a class="btn btn-dialog search-element" href="{{url('pos/product/static/import')}}">Import Static Products</a>&nbsp;&nbsp;
                        @endif
                        
                        @if(in_array($user->user_type,array(1,6,9)))
                            <a href="javascript:;" onclick="downloadPosProducts();" class="btn btn-dialog" title="Download Pos Products CSV"><i title="Download Pos Products CSV" class="fa fa-download fas-icon" ></i> </a>&nbsp; &nbsp;
                        @endif    
                        
                        @if(in_array($user->user_type,array(1)))
                            <?php $query_str = CommonHelper::getQueryString();?>
                            <a href="{{url('pos/product/download/csv/2?'.$query_str)}}" class="btn btn-dialog" title="Download SKU CSV"><i title="Download SKU CSV" class="fa fa-download fas-icon" ></i> </a>&nbsp;&nbsp;
                        @endif
                        
                        @if(CommonHelper::hasPermission('update_product_barcode_by_csv',$user->user_type) || $is_fake_inventory_user)
                            <!--<input type="button" name="updateBarcodesBtn" id="updateBarcodesBtn" value="Update Barcodes" class="btn btn-dialog search-element" onclick="displayBarcodeCSVForm();"> -->
                            <input type="button" name="updateBarcodesBySchedulerBtn" id="updateBarcodesBySchedulerBtn" value="Update Barcodes" class="btn btn-dialog search-element" onclick="updateBarcodesByScheduler();"> 
                        @endif    
                    </div>
                </div>    
            </form>
            
            <div class="separator-10"></div>
            
            <div id="posProductContainer" class="table-container">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" >
                        <thead>
                            <tr class="header-tr"><th>
                                @if($product_edit_permission)    
                                    <input type="checkbox" name="pos_product_list_all" id="pos_product_list_all"  value="1" onclick="checkAllCheckboxes(this,'pos_product-list');">
                                @endif    
                                &nbsp;<?php echo CommonHelper::getSortLink('ID','id','pos/product/list',true,'ASC'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Name','name','pos/product/list'); ?></th>    
                                <th><?php echo CommonHelper::getSortLink('Size','size','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Color','color','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Category','category','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('SubCategory','subcategory','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Story','story','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Season','season','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('SKU','sku','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Base Price','base_price','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Sale Price','sale_price','pos/product/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Created On','created','pos/product/list'); ?></th>
                                <th>Action</th>
                            </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($pos_product_list);$i++)
                            <tr>
                                <td>@if($product_edit_permission)    
                                        <input type="checkbox" name="pos_product_list" id="pos_product_list_{{$pos_product_list[$i]->id}}" class="pos_product-list-chk" value="{{$pos_product_list[$i]->id}}"> 
                                    @endif
                                    &nbsp;{{$pos_product_list[$i]->id}}
                                </td>
                                <td>{{$pos_product_list[$i]->product_name}}</td>
                                <td>{{$pos_product_list[$i]->size_name}}</td>
                                <td>{{$pos_product_list[$i]->color_name}}</td>
                                <td>{{$pos_product_list[$i]->category_name}}</td>
                                <td>{{$pos_product_list[$i]->subcategory_name}}</td>
                                <td>{{$pos_product_list[$i]->story_name}}</td>
                                <td>{{$pos_product_list[$i]->season_name}}</td>
                                <td>{{$pos_product_list[$i]->product_sku}}</td>
                                <td>{{$currency}} {{$pos_product_list[$i]->base_price}}</td>
                                <td>{{$currency}} {{$pos_product_list[$i]->sale_price}}</td>
                                <td>@if(!empty($pos_product_list[$i]->created_at)) {{date('d M Y',strtotime($pos_product_list[$i]->created_at))}} @endif</td>
                                <td>
                                    @if($product_edit_permission)
                                        @if($pos_product_list[$i]->static_product == 0)
                                            <a href="javascript:;" class="story-list-edit" onclick="editPosProduct({{$pos_product_list[$i]->id}});"><i title="Edit Product" class="far fa-edit"></i></a> &nbsp;
                                        @else
                                            <a href="javascript:;" class="story-list-edit" onclick="editStaticPosProduct({{$pos_product_list[$i]->id}});"><i title="Edit Product" class="far fa-edit"></i></a> &nbsp;
                                        @endif
                                    @endif
                                    @if($user->user_type == 5)
                                        <a href="{{url('designer/sor/product/edit/'.$pos_product_list[$i]->id)}}" class="story-list-edit" ><i title="Edit Product" class="far fa-edit"></i></a> &nbsp;
                                    @endif
                                    <a href="{{url('pos/product/detail/'.$pos_product_list[$i]->id)}}" class="story-list-edit" ><i title="Product Detail" class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $pos_product_list->withQueryString()->links() }}
                    <p>Displaying {{$pos_product_list->count()}} of {{ $pos_product_list->total() }} products.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="edit_pos_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editPosProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editPosProductErrorMessage"></div>

                <form class="" name="editPosProductFrm" id="editPosProductFrm" type="POST" enctype="multipart/form-data">
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
                                <select name="product_category_edit" id="product_category_edit"  class="form-control" onchange="getPosProductSubcategories(this.value,'edit');getPosProductHsnCode(this.value,'edit');">
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
                            
                            <div class="form-group col-md-3">
                                <label>Barcode</label>
                                <input id="product_barcode_edit" type="text" class="form-control" name="product_barcode_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_product_barcode_edit"></div>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label>Sale Price</label>
                                <input id="product_sale_price_edit" type="text" class="form-control" name="product_sale_price_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_product_sale_price_edit"></div>
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
                            </div>
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
                                <input id="product_base_price_edit" type="text" class="form-control" name="product_base_price_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_product_base_price_edit"></div>
                            </div> 
                            <div class="form-group col-md-3">
                                <label>Size</label>
                                <select name="size_id_edit" id="size_id_edit" disabled="true" class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <option value="{{$size_list[$i]['id']}}">{{$size_list[$i]['size']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_size_id_edit"></div>
                            </div>
                            
                        </div>
                        <div class="form-row">
                            
                            <!--<div class="form-group col-md-3">
                                <label>Sale Category</label>
                                <select name="product_sale_category_edit" id="product_sale_category_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_sale_category_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>SKU</label>
                                <input id="product_sku_edit" type="text" class="form-control" name="product_sku_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_product_sku_edit"></div>
                            </div>-->
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
                            <div class="form-group col-md-2 prod-size-add-div">
                                <label>Add Size</label>
                                <select name="product_other_size_add_edit" id="product_other_size_add_edit"  class="form-control" >
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_other_size_add_edit"></div>
                            </div>
                            <div class="form-group col-md-2 prod-size-add-div">
                                <label>&nbsp;</label>
                                <button type="button" id="product_other_size_add_submit" name="product_other_size_add_submit" class="btn btn-dialog" onclick="addOtherSizePosProduct();" style="display:block;margin-top:20px;">Add</button>
                            </div>
                        </div>    
                        
                        <div class="form-row product-image-container-edit">
                            
                            <div id="picture_error_edit" class="invalid-feedback"></div>
                            <div class="alert alert-success alert-dismissible" style="display:none" id="deleteProductImageSuccessMessage"></div>
                            <div id="picture_images_list" style="width:100%;"></div>
                            
                            <div class="separator-10"></div>
                            
                            <div class="form-group product-image-div-edit-copy col-md-4" style="display:none;">
                                <label>Picture</label>
                                <input type="file" name="product_image_edit[]" id="product_image_edit" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_edit"></div>
                            </div>
                            
                            
                            <!--<div class="form-group product-image-div-edit col-md-4" >
                                <label>Picture</label>
                                <input type="file" name="product_image_edit[]" id="product_image_edit" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_edit"></div>
                            </div>-->
                           
                        </div>
                        <div class="form-group" style="float: left;">
                            <label></label>
                            <input type="button" name="image_add_btn" id="image_add_btn" class="btn btn-dialog" value="Add Picture" onclick="addProductPicture('edit');">
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="pos_product_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="pos_product_edit_cancel" name="pos_product_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="pos_product_edit_submit" name="pos_product_edit_submit" class="btn btn-dialog" onclick="updatePosProduct();">Submit</button>
                        <input id="product_edit_id" type="hidden" class="form-control" name="product_edit_id" value=""  >
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="add_pos_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addPosProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addPosProductErrorMessage"></div>

                <form class="" name="addPosProductFrm" id="addPosProductFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body product-add-div">
                        
                        <div class="form-row">
                            <div class="form-group  col-md-3" >
                                <label>Product Name</label>
                                <input id="product_name_add" type="text" class="form-control" name="product_name_add" value="" autofocus>
                                <div class="invalid-feedback" id="error_validation_product_name_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Color</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger"><span id="color_label_add">Select One</span>
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
                                <div class="invalid-feedback" id="error_validation_color_id_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Category</label>
                                <select name="product_category_add" id="product_category_add"  class="form-control" onchange="getPosProductSubcategories(this.value,'add');getPosProductHsnCode(this.value,'add');">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($category_list);$i++)
                                        <option value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_category_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Sub Category</label>
                                <select name="product_subcategory_add" id="product_subcategory_add"  class="form-control">
                                    <option value="">Select</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_subcategory_add"></div>
                            </div>
                            
                        </div>
                        <div class="form-row">
                            
                            <div class="form-group col-md-3">
                                <label>Story</label>
                                <select name="story_id_add" id="story_id_add"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($story_list);$i++)
                                        <option value="{{$story_list[$i]['id']}}">{{$story_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_story_id_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Season</label>
                                <select name="season_id_add" id="season_id_add"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($season_list);$i++)
                                        <option value="{{$season_list[$i]['id']}}">{{$season_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_season_id_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Base Price</label>
                                <input id="product_base_price_add" type="text" class="form-control" name="product_base_price_add" value="">
                                <div class="invalid-feedback" id="error_validation_product_base_price_add"></div>
                            </div> 
                            <div class="form-group col-md-3">
                                <label>HSN Code</label>
                                <input id="product_hsn_code_add" type="text" class="form-control" name="product_hsn_code_add" value="" readonly="true">
                                <div class="invalid-feedback" id="error_validation_product_hsn_code_add"></div>
                            </div>
                            <!--<div class="form-group col-md-3">
                                <label>Sale Price</label>
                                <input id="product_sale_price_add" type="text" class="form-control" name="product_sale_price_add" value="">
                                <div class="invalid-feedback" id="error_validation_product_sale_price_add"></div>
                            </div> -->
                        </div>
                        
                        <div class="form-row">
                            
                            <div class="form-group col-md-8">
                                <label>Size</label>
                                <div class="clearfix separator-10">&nbsp;</div>
                                <div style="float:left;">
                                @for($i=0;$i<count($size_list);$i++)
                                    <input type="checkbox" name="size_list_add[]" id="size_list_add" value="{{$size_list[$i]['id']}}" /> {{$size_list[$i]['size']}} &nbsp;&nbsp;
                                @endfor  
                                </div>
                                
                                <div class="invalid-feedback" id="error_validation_size_list_add"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Description</label>
                                <textarea name="product_description_add" id="product_description_add" class="form-control" style="height:40px;"></textarea>
                                <div class="invalid-feedback" id="error_validation_product_description_add"></div>
                            </div>
                             
                        </div>    
                        
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label>GST Inclusive </label>
                                <select name="gst_inclusive_add" id="gst_inclusive_add"  class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_gst_inclusive_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Vendor Product SKU</label>
                                <input id="vendor_product_sku_add" type="text" class="form-control" name="vendor_product_sku_add" value="" >
                                <div class="invalid-feedback" id="error_validation_vendor_product_sku_add"></div>
                            </div>
                        </div>
                        
                        <div class="form-row product-image-container-add">
                            <div id="picture_error_add" class="invalid-feedback"></div>
                            
                            <div class="form-group col-md-4" >
                                <label>Front View</label>
                                <input type="file" name="product_image_front_add" id="product_image_front_add" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_front_add"></div>
                            </div>
                            
                            <div class="form-group col-md-4" >
                                <label>Back View</label>
                                <input type="file" name="product_image_back_add" id="product_image_back_add" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_back_add"></div>
                            </div>
                            
                            <div class="form-group col-md-4" >
                                <label>Close View</label>
                                <input type="file" name="product_image_close_add" id="product_image_close_add" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_close_add"></div>
                            </div>
                            
                            <div class="form-group product-image-div-add col-md-4" >
                                <label>Picture</label>
                                <input type="file" name="product_image_add[]" id="product_image_add" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_add"></div>
                            </div>
                            
                            <div class="form-group product-image-div-add col-md-4" >
                                <label>Picture</label>
                                <input type="file" name="product_image_add[]" id="product_image_add" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_add"></div>
                            </div>
                            
                            <div class="form-group product-image-div-add col-md-4" >
                                <label>Picture</label>
                                <input type="file" name="product_image_add[]" id="product_image_add" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_add"></div>
                            </div>
                        </div>
                        <div class="form-group" style="float:left;">
                            <label></label>
                            <input type="button" name="image_add_btn" id="image_add_btn" class="btn btn-dialog" value="Add More Picture" onclick="addProductPicture('add');">
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="pos_product_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="pos_product_add_cancel" name="pos_product_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="pos_product_add_submit" name="pos_product_add_submit" class="btn btn-dialog" onclick="submitAddPosProduct();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm_delete_product_image" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteProductImageErrorMessage"></div>
                
                <div class="modal-body">
                    <h6>Are you sure to delete Product Image<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-product-image-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="product_image_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="product_image_btn" name="product_image_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadBarcodeCsvDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Upload Barcode CSV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="uploadBarcodeCSVErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="uploadBarcodeCSVSuccessMessage"></div>
                <form method="post" name="barcodeCsvForm" id="barcodeCsvForm">
                <div class="modal-body">
                    <div class="form-group" >
                        <label>CSV File</label>
                        <input type="file" name="barcodeCsvFile" id="barcodeCsvFile" class="form-control"  />
                    </div>
                    <div class="form-group">
                        <label>PO</label>
                        <select name="po_id" id="po_id"  class="form-control" >
                            <option value="">Select</option>
                            @for($i=0;$i<count($po_list);$i++)
                                <option value="{{$po_list[$i]['id']}}">{{$po_list[$i]['order_no']}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="form-group pull-right" >
                        <label></label>
                        <a href="{{url('documents/product_barcode_csv/product_barcode_sample_csv.csv')}}" target="_blank">Sample CSV File</a>
                    </div>
                </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="product_barcode_csv_spinner" class="spinner-border spinner-border-sm text-secondary" style="display:none;" role="status"><span class="sr-only">Loading...</span></div>
                    <button name="updateBarcodesCancel" id="updateBarcodesCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="uploadCsvBtn" id="uploadCsvBtn" value="Upload CSV" class="btn btn-dialog" onclick="submitUpdateBarcodesByScheduler();">Upload CSV</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="edit_static_pos_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Static Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" id="editStaticPosProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" id="editStaticPosProductErrorMessage"></div>

                <form class="" name="editStaticPosProductFrm" id="editStaticPosProductFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body product-edit-div">
                        
                        <div class="form-row">
                            <div class="form-group  col-md-3" >
                                <label>Product Name</label>
                                <input id="static_product_name_edit" type="text" class="form-control" name="static_product_name_edit" value="" >
                                <div class="invalid-feedback" id="error_validation_static_product_name_edit"></div>
                            </div>
                            <div class="form-group  col-md-3" >
                                <label>Product SKU</label>
                                <input id="static_product_sku_edit" type="text" class="form-control" name="static_product_sku_edit" value="" readonly="true">
                                <div class="invalid-feedback" id="error_validation_static_product_sku_edit"></div>
                            </div>
                            <div class="form-group  col-md-3" >
                                <label>Barcode</label>
                                <input id="static_product_barcode_edit" type="text" class="form-control" name="static_product_barcode_edit" value="" >
                                <div class="invalid-feedback" id="error_validation_static_product_barcode_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Color</label>
                                <select name="static_product_color_edit" id="static_product_color_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($color_list);$i++)
                                        <option value="{{$color_list[$i]['id']}}">{{$color_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_product_color_edit"></div>
                            </div>
                            
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Size</label>
                                 <select name="static_size_id_edit" id="static_size_id_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <option value="{{$size_list[$i]['id']}}">{{$size_list[$i]['size']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_size_id_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Category</label>
                                <select name="static_product_category_edit" id="static_product_category_edit"  class="form-control" onchange="getStaticPosProductSubcategories(this.value,'edit');getStaticPosProductHsnCode(this.value,'edit');">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($category_list);$i++)
                                        <option value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_product_category_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Sub Category</label>
                                <select name="static_product_subcategory_edit" id="static_product_subcategory_edit"  class="form-control">
                                    <option value="">Select</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_product_subcategory_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Story</label>
                                <select name="static_story_id_edit" id="static_story_id_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($story_list);$i++)
                                        <option value="{{$story_list[$i]['id']}}">{{$story_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_story_id_edit"></div>
                            </div>
                            
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Season</label>
                                <select name="static_season_id_edit" id="static_season_id_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($season_list);$i++)
                                        <option value="{{$season_list[$i]['id']}}">{{$season_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_season_id_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Base Price</label>
                                <input id="static_product_base_price_edit" type="text" class="form-control" name="static_product_base_price_edit" value="">
                                <div class="invalid-feedback" id="error_validation_static_product_base_price_edit"></div>
                            </div> 
                            <div class="form-group col-md-3">
                                <label>Sale Price</label>
                                <input id="static_product_sale_price_edit" type="text" class="form-control" name="static_product_sale_price_edit" value="">
                                <div class="invalid-feedback" id="error_validation_static_product_sale_price_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>HSN Code</label>
                                <input id="static_product_hsn_code_edit" type="text" class="form-control" name="static_product_hsn_code_edit" value="" readonly="true">
                                <div class="invalid-feedback" id="error_validation_static_product_hsn_code_edit"></div>
                            </div>
                            
                        </div>    
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <input id="static_product_id_edit" type="hidden"  name="static_product_id_edit" value="" >
                        <button type="button" id="static_pos_product_edit_cancel" name="static_pos_product_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="static_pos_product_edit_submit" name="static_pos_product_edit_submit" class="btn btn-dialog" onclick="submitEditStaticPosProduct();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="add_static_pos_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Static Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" id="addStaticPosProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" id="addStaticPosProductErrorMessage"></div>

                <form class="" name="addStaticPosProductFrm" id="addStaticPosProductFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body product-add-div">
                        
                        <div class="form-row">
                            <div class="form-group  col-md-3" >
                                <label>Product Name</label>
                                <input id="static_product_name_add" type="text" class="form-control" name="static_product_name_add" value="" autofocus>
                                <div class="invalid-feedback" id="error_validation_static_product_name_add"></div>
                            </div>
                            <div class="form-group  col-md-3" >
                                <label>Product SKU</label>
                                <input id="static_product_sku_add" type="text" class="form-control" name="static_product_sku_add" value="" autofocus>
                                <div class="invalid-feedback" id="error_validation_static_product_sku_add"></div>
                            </div>
                            <div class="form-group  col-md-3" >
                                <label>Barcode</label>
                                <input id="static_product_barcode_add" type="text" class="form-control" name="static_product_barcode_add" value="" autofocus>
                                <div class="invalid-feedback" id="error_validation_static_product_barcode_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Color</label>
                                <select name="static_product_color_add" id="static_product_color_add"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($color_list);$i++)
                                        <option value="{{$color_list[$i]['id']}}">{{$color_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_product_color_add"></div>
                            </div>
                            
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Size</label>
                                 <select name="static_size_id_add" id="static_size_id_add"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <option value="{{$size_list[$i]['id']}}">{{$size_list[$i]['size']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_size_id_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Category</label>
                                <select name="static_product_category_add" id="static_product_category_add"  class="form-control" onchange="getStaticPosProductSubcategories(this.value,'add');getStaticPosProductHsnCode(this.value,'add');">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($category_list);$i++)
                                        <option value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_product_category_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Sub Category</label>
                                <select name="static_product_subcategory_add" id="static_product_subcategory_add"  class="form-control">
                                    <option value="">Select</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_product_subcategory_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Story</label>
                                <select name="static_story_id_add" id="static_story_id_add"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($story_list);$i++)
                                        <option value="{{$story_list[$i]['id']}}">{{$story_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_story_id_add"></div>
                            </div>
                            
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Season</label>
                                <select name="static_season_id_add" id="static_season_id_add"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($season_list);$i++)
                                        <option value="{{$season_list[$i]['id']}}">{{$season_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_static_season_id_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Base Price</label>
                                <input id="static_product_base_price_add" type="text" class="form-control" name="static_product_base_price_add" value="">
                                <div class="invalid-feedback" id="error_validation_static_product_base_price_add"></div>
                            </div> 
                            <div class="form-group col-md-3">
                                <label>Sale Price</label>
                                <input id="static_product_sale_price_add" type="text" class="form-control" name="static_product_sale_price_add" value="">
                                <div class="invalid-feedback" id="error_validation_static_product_sale_price_add"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>HSN Code</label>
                                <input id="static_product_hsn_code_add" type="text" class="form-control" name="static_product_hsn_code_add" value="" readonly="true">
                                <div class="invalid-feedback" id="error_validation_static_product_hsn_code_add"></div>
                            </div>
                            
                        </div>    
                        
                    </div>
                    <div class="modal-footer center-footer">
                        
                        <button type="button" id="static_pos_product_add_cancel" name="static_pos_product_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="static_pos_product_add_submit" name="static_pos_product_add_submit" class="btn btn-dialog" onclick="submitAddStaticPosProduct();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="downloadPosProductDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Download Pos Products</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadPosProductsErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadPosProductsSuccessMessage"></div>
                <form method="post" name="downloadPosProductsForm" id="downloadPosProductsForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Pos Products Records</label>
                                <select name="pos_product_count" id="pos_product_count" class="form-control" >
                                    <option value="">-- Pos Products Records --</option>
                                        @for($i=0;$i<=$pos_product_count;$i=$i+50000) 
                                            <?php $start = $i+1; $end = $i+50000; ?>
                                            <?php $end = ($end < $pos_product_count)?$end:$pos_product_count; ?>
                                            <option value="{{$start}}_{{$end}}">{{$start}} - {{$end}}</option>
                                        @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_pos_product_count"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                
                <div class="modal-footer center-footer">
                    <button name="downloadPosProductsCancel" id="downloadPosProductsCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="downloadPosProductsBtn" id="downloadPosProductsBtn" value="Download Pos Products" class="btn btn-dialog" onclick="submitDownloadPosProducts();">Download</button>
                </div>
            </div>
        </div>
    </div>

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/pos_product.js?v=3.15') }}" ></script>
@if(!empty(request('category_search')))
    <script type="text/javascript">
        getPosProductSubcategories({{request('category_search')}},'search',"{{request('product_subcategory_search')}}");
    </script>        
@endif
@endsection
