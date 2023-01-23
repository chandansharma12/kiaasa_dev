@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'store/dashboard'),array('name'=>'Assets Orders List','link'=>'store/asset/order/list'),array('name'=>'Create Assets Order')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Create Assets Order'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <div id="createOrderErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="ordersList">
                <form class="" name="createOrderFrm" id="createOrderFrm" method="POST" enctype="multipart/form-data">
                   <button type="button" id="add_order_row" name="add_order_row" onclick="addAssetOrderRow('add');" class="btn btn-dialog">Add+</button>
                   <div class="separator-10"></div>
                    <div class="order-items-container" id="">
                        <div class="form-row order-items-row" id="order_items_row_first">
                            <div class="form-group col-md-3">
                                <label class="label-text">Store Asset</label>
                                <div class="typeahead__container">
                                    <div class="typeahead__field">
                                        <div class="typeahead__query">
                                            <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" >
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="storeItem_add[]" id="storeItem_add" class="form-control order-item_add" >
                            </div>
                            <div class="form-group col-md-1">
                                <label class="label-text">Quantity</label>
                                <input type="number" name="storeItemQuantity_add[]" id="storeItemQuantity_add" class="form-control order-item-quantity_add" onkeyup="updateAssetOrderPrice('add');" onchange="updateAssetOrderPrice('add');">
                            </div>
                            <div class="form-group col-md-1">
                                <label class="label-text">Unit Price</label>
                                <span class="order-item-unit-price"></span>
                            </div>
                            <div class="form-group col-md-1">
                                <label class="label-text">Total Price</label>
                                <span class="order-item-price"></span>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="label-text">Picture</label>
                                <input type="file" name="storeItemPicture_add[]" id="storeItemPicture_add" class="form-control order-item-picture_add">
                            </div>
                        </div>  
                    </div>
                    <div class="form-row" >
                        <div class="form-group col-md-2">
                            <span id="order_grand_total" name="order_grand_total"></span>
                        </div> 
                    </div>
                    <br/>
                    <div class="form-row" >
                        <button type="button" id ="order_add_submit" name="order_add_submit" class="btn btn-dialog" value="Submit" onclick="createAssetsOrder('add');">Create Order</button>&nbsp;&nbsp;
                        <button type="button" id="order_add_cancel" name="order_add_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('store/asset/order/list')}}'">Cancel</button>
                        <input type="hidden" name="create_order" id="create_order" value="1">
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
        var action_type = 'add',page_type = 'asset';
    </script>
@endif

<script src="{{ asset('js/store.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/jquery.typeahead.min.css') }}" />
<script src="{{ asset('js/jquery.typeahead.min.js') }}" ></script>
@endsection
