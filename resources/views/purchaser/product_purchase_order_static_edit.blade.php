@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'SOR Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'Edit Static Purchase Order')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Static Purchase Order'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="purchaseOrderEditErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="purchaseOrderEditSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <div id="productsContainer">
                
                <div class="separator-10"></div>
                <form method="post" name="editStaticPurchaseOrderForm" id="editStaticPurchaseOrderForm">
                    <div class="form-group col-md-3" >
                        <label>CSV File</label>
                        <input type="file" name="purchaseOrderCsvFile" id="purchaseOrderCsvFile" class="form-control"  />
                    </div>

                    <div class="form-group col-md-3 ">
                        <label for="other cost" class="col-sm-2 col-form-label"></label>
                        <div id="edit_purchase_order_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="edit_purchase_order_submit" name="edit_purchase_order_submit" class="btn btn-dialog" onclick="uploadStaticPurchaseOrderCsvFile();">Submit</button>
                    </div>
                    <input type="hidden" name="po_id" id="po_id" value="{{$po_data->id}}">
                </form>    
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.1') }}" ></script>
@endsection
