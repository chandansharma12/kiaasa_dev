@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Kiaasa Products List','link'=>'pos/product/list'),array('name'=>'Kiaasa Product Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Kiaasa Product Detail'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >

            <div id="updatePosProductStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updatePosProductStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            
            <div class="separator-10">&nbsp;</div>

            <div id="posProductContainer" class="table-container">
                <div id="posProductListOverlay" class="table-list-overlay"><div id="posProduct-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="posProductList">
                    <form id="product_detail_form" name="product_detail_form">
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="Season">Product ID</label>						
                                {{$product_data->id}}    
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Product">Product Name</label>						
                                {{$product_data->product_name}}
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Product">Product Barcode</label>						
                                {{$product_data->product_barcode}}
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Product">Product SKU</label>						
                                {{$product_data->product_sku}}
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Product">Category</label>						
                                {{$product_data->category_name}}
                            </div>
                            <div class="form-group col-md-2">
                                <label for="Product">SubCategory</label>						
                                {{$product_data->subcategory_name}}
                            </div>
                        </div>    
                        <hr/>
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="Season">Description</label>						
                                {!! nl2br($product_data->product_description) !!}  
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Product">Base Price</label>						
                                {{$product_data->base_price}}
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Product">Sale Price</label>						
                                {{$product_data->sale_price}}
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Product">Size</label>						
                                {{$product_data->size_name}}
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Product">Color</label>						
                                {{$product_data->color_name}}
                            </div>
                            <div class="form-group col-md-2">
                                <label for="Product">Inventory</label>	
                                {{$product_data->inventory_count}}    
                            </div>
                        </div>    
                        <hr/>
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="Product">HSN Code</label>	
                                {{$product_data->hsn_code}}    
                            </div>
                            <div class="form-group col-md-2">
                                <label for="Product">Vendor Product SKU</label>	
                                {{$product_data->vendor_product_sku}}    
                            </div>
                            <div class="form-group col-md-2">
                                <label for="Product">Status</label>	
                                @if($product_data->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif
                            </div>
                            <div class="form-group col-md-2">
                                <label for="Season">Created On</label>						
                                @if(!empty($product_data->created_at)) {{date('d M Y',strtotime($product_data->created_at))}} @endif   
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Season">Updated On</label>						
                                @if(!empty($product_data->updated_at)) {{date('d M Y',strtotime($product_data->updated_at))}} @endif    
                            </div> 
                            <div class="form-group col-md-2">
                                <label for="Season">Created by</label>						
                                {{$product_data->user_name}}   
                            </div> 
                        </div>  
                        
                        <hr><h6>Product Images</h6>
                        <div class="form-row">
                            @for($i=0;$i<count($product_images);$i++)
                                <div class="form-group col-md-2">
                                    <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$product_images[$i]['product_id'].'/'.$product_images[$i]['image_name'])}}')">
                                        <img src="{{url('images/pos_product_images/'.$product_images[$i]['product_id'].'/'.$product_images[$i]['image_name'])}}" class="img-thumbnail">
                                    </a>    
                                    <div style="margin-left: 40%">{{$product_images[$i]['image_type']}}</div>
                                </div>
                            @endfor  
                        </div>
                    </form> 
                    
                </div>
            </div>
        </div>
    </section>

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/pos_product.js?v=1.1') }}" ></script>
@endsection
