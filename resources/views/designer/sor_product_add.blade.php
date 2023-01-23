@extends('layouts.default')
@section('content')
<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))    
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area" id="add_pos_product_dialog">
        <div class="container-fluid" >

            <div id="updatePosProductStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updatePosProductStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
          
            <div class="separator-10"></div>
       
            <div id="posProductContainer" class="table-container">
                <div id="posProductListOverlay" class="table-list-overlay"><div id="posProduct-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="posProductList">
                    <div class="table-responsive">
                       
                       <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Product</h5>
                    
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
                            
                            <div class="form-group col-md-5">
                                <label>Size</label>
                                <div class="clearfix separator-10">&nbsp;</div>
                                <div style="float:left;">
                                @for($i=0;$i<count($size_list);$i++)
                                    <input type="checkbox" name="size_list_add[]" id="size_list_add" value="{{$size_list[$i]['id']}}" /> {{$size_list[$i]['size']}} &nbsp;&nbsp;
                                @endfor  
                                </div>
                                
                                <div class="invalid-feedback" id="error_validation_size_list_add"></div>
                            </div>
                            
                            <div class="form-group col-md-2">
                                <label>GST Inclusive </label>
                                <select name="gst_inclusive_add" id="gst_inclusive_add"  class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_gst_inclusive_add"></div>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Vendor Product SKU</label>
                                <input id="vendor_product_sku_add" type="text" class="form-control" name="vendor_product_sku_add" value="" >
                                <div class="invalid-feedback" id="error_validation_vendor_product_sku_add"></div>
                            </div>
                            
                            <div class="form-group col-md-3" >
                                <label>Description</label>
                                <textarea name="product_description_add" id="product_description_add" class="form-control" style="height:40px;"></textarea>
                                <div class="invalid-feedback" id="error_validation_product_description_add"></div>
                            </div>
                               
                            
                            <!--<div class="form-group col-md-2">
                                <label>Sale Category</label>
                                <select name="product_sale_category_add" id="product_sale_category_add"  class="form-control" >
                                    <option value="">Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_sale_category_add"></div>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Item Code</label>
                                <input id="product_sku_add" type="text" class="form-control" name="product_sku_add" value="" >
                                <div class="invalid-feedback" id="error_validation_product_sku_add"></div>
                            </div>-->
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
                        <a type="button" id="pos_product_add_cancel" href="{{ route('designerdashboard') }}" class="btn btn-secondary" >Cancel</a>
                        <button type="button" id ="pos_product_add_submit" name="pos_product_add_submit" class="btn btn-dialog" onclick="submitAddPosProduct();">Submit</button>
                    </div>
                </form>
            </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
@endsection

@section('scripts')
<link href="{{asset('css/select2.min.css')}}" rel="stylesheet"  />
<script src="{{asset('js/select2.min.js')}}"></script>
<script src="{{ asset('js/sor_pos_product.js') }}" ></script>
@if(!empty(request('category_search')))
    <script type="text/javascript">
        getPosProductSubcategories({{request('category_search')}},'search',"{{request('product_subcategory_search')}}");
    </script>        
@endif

<script>
    /*$('#size_id_add').select2();*/
</script>    
@endsection
