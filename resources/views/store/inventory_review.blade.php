@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Review')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Review'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    
    <section class="product_area">
        <div class="container-fluid" >
            
            <input type="hidden" name="store_id" id="store_id" value="{{$store_data->id}}">

            <div id="products_import_div" >

                <form class="" name="importPosInventoryFrm" id="importPosInventoryFrm" method="POST" >

                   <div class="separator-10"></div>

                        <div class="form-row ">
                            <div class="form-group col-md-2">
                                <label>Piece Barcode</label>
                                <input type="text" name="inv_review_piece_barcode" id="inv_review_piece_barcode" class="form-control " autofocus="true" maxlength="20">
                            </div>
                            <div class="form-group col-md-2">
                                <label>&nbsp;</label>
                                <button type="button" id="pos_review_inventory_submit" name="pos_review_inventory_submit" class="btn btn-dialog" value="Submit" onclick="checkInventoryReviewProduct();">Check Product</button>
                            </div>
                        </div>  
                   
                        <div style="height:45px;">
                            <span id="reviewPosInventoryErrorMessage" class="alert alert-danger product-added-span elem-hidden"></span>
                            <span id="reviewPosInventoryErrorMessage" class="alert alert-success product-added-span elem-hidden"></span>
                        </div>
                   
                        <div class="form-row elem-hidden" id="product_data_row">
                            <div class="form-group col-md-1">
                                <label>Barcode</label>
                                <input type="text" name="piece_barcode" id="piece_barcode" class="form-control import-data" readonly="true" >
                            </div> 
                           <div class="form-group col-md-1">
                                <label>Product</label>
                                <input type="text" name="piece_product_name" id="piece_product_name" class="form-control import-data" readonly="true" >
                            </div>
                            <div class="form-group col-md-1">
                                <label>Size</label>
                                <input type="text" name="piece_size" id="piece_size" class="form-control import-data" readonly="true" >
                            </div>
                            <div class="form-group col-md-1">
                                <label>Color</label>
                                <input type="text" name="piece_color" id="piece_color" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-1">
                                <label>SKU</label>
                                <input type="text" name="product_sku" id="product_sku" class="form-control import-data" readonly="true">
                            </div>
                            <!--<div class="form-group col-md-1">
                                <label>Cost</label>
                                <input type="text" name="piece_cost" id="piece_cost" class="form-control import-data" readonly="true">
                            </div>-->
                            <div class="form-group col-md-1">
                                <label>Store</label>
                                <input type="text" name="piece_store" id="piece_store" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-2">
                                <label>Status</label>
                                <input type="text" name="piece_status" id="piece_status" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Reason</label>
                                <span name="piece_reason" id="piece_reason" class="alert alert-danger elem-hidden" ></span>
                            </div>
                        </div>

                        <div id="products_list"></div>
                        <div id="products_paging_links"></div>
                    
                    @csrf
                </form>
            </div>
        </div>
    </section>
    
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/store.js?v=1.31') }}" ></script>
<script type="text/javascript">
    $(document).ready(function(){
        loadInventoryReviewData(1);
    });
    
</script>
@endsection
