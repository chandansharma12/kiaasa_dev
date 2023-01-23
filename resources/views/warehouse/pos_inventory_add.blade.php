@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard'),array('name'=>'Add Product Inventory')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Add Product Inventory'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        
                    </div>
                </div>
            </form> 
            
            <div id="demandsList">
                <div id="addPosInventoryErrorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="addPosInventorySuccessMessage" class="alert alert-success" style="display:none;"></div>
                <form class="" name="addPosInventoryFrm" id="addPosInventoryFrm" method="POST" enctype="multipart/form-data">
                   <button type="button" id="add_demand_row" name="add_demand_row" onclick="addDemandRow('add');" class="btn btn-dialog">Add+</button>
                   <div class="separator-10"></div>
                    <div class="demand-items-container" id="">
                        <div class="form-row demand-items-row" id="demand_items_row_first">
                            <div class="form-group col-md-2">
                                <label>Product</label>
                                <div class="typeahead__container">
                                    <div class="typeahead__field">
                                        <div class="typeahead__query">
                                            <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" >
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="product_add[]" id="product_add" class="form-control demand-item_add" >
                            </div>
                            <div class="form-group col-md-1">
                                <label>Available</label>
                                <span class="demand-item-inventory-count_add demand-item-data"></span>
                                <input type="hidden" name="demand_item_inventory_count_add" id="demand_item_inventory_count_add" class="demand_item_inventory_count_add" value="">
                            </div>
                            <div class="form-group col-md-1">
                                <label>Unit Price</label>
                                <span class="demand-item-unit-price demand-item-data"></span>
                            </div>
                            
                            <div class="form-group col-md-1">
                                <label>Quantity</label>
                                <input type="number" name="quantity_add[]" id="quantity_add" class="form-control demand-item-quantity_add" >
                            </div>
                            
                        </div>  
                    </div>
                    <!--<div class="form-row" >
                        <div class="form-group col-md-2">
                            <span id="demand_grand_total" name="demand_grand_total"></span>
                        </div> 
                    </div> -->
                    <br/>
                    <div class="form-row" >
                        <div id="add_pos_inventory_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id ="pos_add_inventory_submit" name="pos_add_inventory_submit" class="btn btn-dialog" value="Submit" onclick="addPosInventory();">Add Inventory</button>&nbsp;&nbsp;
                        <button type="button" id="pos_add_inventory_cancel" name="pos_add_inventory_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                        
                    </div>
                    
                    @csrf
                </form>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script type="text/javascript" >
        var action_type = 'add',page_type = 'push_demand';
    </script>
@endif
<link rel="stylesheet" href="{{ asset('css/jquery.typeahead.min.css') }}" />
<script src="{{ asset('js/jquery.typeahead.min.js') }}" ></script>
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
