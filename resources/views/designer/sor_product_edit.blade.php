@extends('layouts.default')
@section('content')
<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))    
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area" id="add_pos_product_dialog">
        <div class="container-fluid" >

            <div id="updateSorProductErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateSorProductSuccessMessage" class="alert alert-success elem-hidden" ></div>
          
            <div class="separator-10"></div>
       
            <div id="posProductContainer" class="table-container">
                
                <div class="table-responsive">
                       
                <div class="modal-content">
                
                    <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit SOR Product</h5>
                </div>

                <form class="" name="editSorProductFrm" id="editSorProductFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body product-add-div">
                        
                        <div class="form-row">
                            <div class="form-group  col-md-3" >
                                <label>Product Name</label>
                                <input id="product_name_edit" type="text" class="form-control" name="product_name_edit" value="{{$product_data->product_name}}" autofocus>
                                <div class="invalid-feedback" id="error_validation_product_name_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Category</label>
                                <select name="product_category_edit" id="product_category_edit"  class="form-control" onchange="getPosProductSubcategories(this.value,'edit');getPosProductHsnCode(this.value,'edit');">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($category_list);$i++)
                                        <?php $sel = ($product_data->category_id == $category_list[$i]['id'])?'selected':''; ?>
                                        <option value="{{$category_list[$i]['id']}}" {{$sel}}>{{$category_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_category_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Sub Category</label>
                                <select name="product_subcategory_edit" id="product_subcategory_edit"  class="form-control">
                                    <option value="">Select</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_product_subcategory_edit"></div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Story</label>
                                <select name="story_id_edit" id="story_id_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($story_list);$i++)
                                        <?php $sel = ($product_data->story_id == $story_list[$i]['id'])?'selected':''; ?>
                                        <option value="{{$story_list[$i]['id']}}" {{$sel}}>{{$story_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_story_id_edit"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Season</label>
                                <select name="season_id_edit" id="season_id_edit"  class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($season_list);$i++)
                                        <?php $sel = ($product_data->season_id == $season_list[$i]['id'])?'selected':''; ?>
                                        <option value="{{$season_list[$i]['id']}}" {{$sel}}>{{$season_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_season_id_edit"></div>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label>HSN Code</label>
                                <input id="product_hsn_code_edit" type="text" class="form-control" name="product_hsn_code_edit" value="" readonly="true">
                                <div class="invalid-feedback" id="error_validation_product_hsn_code_edit"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-10" >
                                <label>Description</label>
                                <textarea name="product_description_edit" id="product_description_edit" class="form-control" style="height:40px;">{{$product_data->product_description}}</textarea>
                                <div class="invalid-feedback" id="error_validation_product_description_edit"></div>
                            </div>
                       </div>    
                        
                        <div class="form-row product-image-container-add">
                            <div id="picture_error_edit" class="invalid-feedback"></div>
                            
                            <div class="form-group col-md-4" >
                                <label>Front View</label>
                                @if(file_exists(public_path('/images/pos_product_images/'.$product_data->id.'/'.$prod_images['front']['image_name'])))
                                    <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$product_data->id.'/'.$prod_images['front']['image_name'])}}')">
                                        <img src="{{url('images/pos_product_images/'.$product_data->id.'/'.$prod_images['front']['image_name'])}}" class="img-thumbnail" style="max-width: 75px;">
                                    </a>
                                @endif
                                <input type="file" name="product_image_front_edit" id="product_image_front_edit" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_front_edit"></div>
                            </div>
                            
                            <div class="form-group col-md-4" >
                                <label>Back View</label>
                                @if(file_exists(public_path('/images/pos_product_images/'.$product_data->id.'/'.$prod_images['back']['image_name'])))
                                    <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$product_data->id.'/'.$prod_images['back']['image_name'])}}')">
                                        <img src="{{url('images/pos_product_images/'.$product_data->id.'/'.$prod_images['back']['image_name'])}}" class="img-thumbnail" style="max-width: 75px;">
                                    </a>
                                @endif
                                <input type="file" name="product_image_back_edit" id="product_image_back_edit" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_back_edit"></div>
                            </div>
                            
                            <div class="form-group col-md-4" >
                                <label>Close View</label>
                                @if(file_exists(public_path('/images/pos_product_images/'.$product_data->id.'/'.$prod_images['back']['image_name'])))
                                    <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$product_data->id.'/'.$prod_images['close']['image_name'])}}')">
                                        <img src="{{url('images/pos_product_images/'.$product_data->id.'/'.$prod_images['close']['image_name'])}}" class="img-thumbnail" style="max-width: 75px;">
                                    </a>
                                @endif
                                <input type="file" name="product_image_close_edit" id="product_image_close_edit" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_close_edit"></div>
                            </div>
                            
                            @for($i=0;$i<count($prod_images['other']);$i++)
                                <div class="form-group col-md-4" >
                                    <label>Picture</label>
                                    @if(file_exists(public_path('/images/pos_product_images/'.$product_data->id.'/'.$prod_images['other'][$i]['image_name'])))
                                        <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$product_data->id.'/'.$prod_images['other'][$i]['image_name'])}}')">
                                            <img src="{{url('images/pos_product_images/'.$product_data->id.'/'.$prod_images['other'][$i]['image_name'])}}" class="img-thumbnail" style="max-width: 75px;">
                                            &nbsp;<a onclick="deleteSorProductImage('{{$prod_images['other'][$i]['id']}}');" href="javascript:;" title="Delete Picture"><i class="fas fa-trash"></i></a>
                                        </a>
                                    @endif
                                    <input type="file" name="product_image_{{$prod_images['other'][$i]['id']}}_edit" id="product_image_{{$prod_images['other'][$i]['id']}}_edit" class="form-control">
                                    
                                    <div class="invalid-feedback" id="error_validation_product_image_{{$prod_images['other'][$i]['id']}}_edit"></div>
                                </div>
                            @endfor    
                            
                            <div class="form-group product-image-div-add col-md-4" >
                                <label>Picture</label>
                                <input type="file" name="product_image_edit[]" id="product_image_edit" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_edit"></div>
                            </div>
                            
                            <div class="form-group product-image-div-add col-md-4" >
                                <label>Picture</label>
                                <input type="file" name="product_image_edit[]" id="product_image_edit" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_edit"></div>
                            </div>
                            
                            <div class="form-group product-image-div-add col-md-4" >
                                <label>Picture</label>
                                <input type="file" name="product_image_edit[]" id="product_image_edit" class="form-control">
                                <div class="invalid-feedback" id="error_validation_product_image_edit"></div>
                            </div>
                        </div>
                        <div class="form-group" style="float:left;">
                            <label></label>
                            <input type="button" name="image_edit_btn" id="image_edit_btn" class="btn btn-dialog" value="Add More Picture" onclick="addProductPicture('add');">
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="product_edit_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <a type="button" id="product_edit_cancel" href="{{ route('posproductlist') }}" class="btn btn-secondary" >Cancel</a>
                        <button type="button" id="product_edit_submit" name="product_edit_submit" class="btn btn-dialog" onclick="submitEditSorProduct();">Submit</button>
                        <input type="hidden" name="product_id" id="product_id" value="{{$product_data->id}}"/>
                    </div>
                </form>
            </div>
            </div>
                
            </div>
        </div>
    </section>

    <div class="modal fade" id="confirm_delete_product_image" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="deleteProductImageErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="deleteProductImageSuccessMessage"></div>
                
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

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/pos_product.js') }}" ></script>
<script type="text/javascript">
    getPosProductSubcategories({{$product_data->category_id}},'edit',"{{$product_data->subcategory_id}}");
    getPosProductHsnCode({{$product_data->category_id}},'edit');
</script>        


@endsection
