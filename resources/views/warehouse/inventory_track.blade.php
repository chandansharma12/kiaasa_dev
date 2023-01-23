@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Track Inventory')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Track Inventory'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="products_import_div" >

                <form name="importPosInventoryFrm" id="importPosInventoryFrm" method="POST" >

                   <div class="separator-10"></div>

                        <div class="form-row ">
                            <div class="form-group col-md-2">
                                <label>Piece Barcode</label>
                                <input type="text" name="inv_track_piece_barcode" id="inv_track_piece_barcode" class="form-control " autofocus="true" maxlength="20">
                            </div>
                            <div class="form-group col-md-2">
                                <label>&nbsp;</label>
                                <button type="button" id="inventory_track_submit" name="inventory_track_submit" class="btn btn-dialog" value="Submit" onclick="trackInventoryProduct();">Track Product</button>
                            </div>
                        </div>  
                   
                        <div style="height:45px;">
                            <span id="trackInventoryErrorMessage" class="alert alert-danger product-added-span elem-hidden"></span>
                            <span id="trackInventorySuccessMessage" class="alert alert-success product-added-span elem-hidden"></span>
                        </div>
                   
                        <div class="form-row elem-hidden" id="product_data_row">
                            <div class="form-group col-md-2">
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
                            <div class="form-group col-md-1">
                                <label>Cost</label>
                                <input type="text" name="piece_cost" id="piece_cost" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-1">
                                <label>Store</label>
                                <input type="text" name="piece_store" id="piece_store" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-2">
                                <label>Status</label>
                                <input type="text" name="piece_status" id="piece_status" class="form-control import-data" readonly="true">
                            </div>
                        </div>

                        <div id="track_product_data"></div>
                        
                    @csrf
                </form>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=1.25') }}" ></script>
@endsection