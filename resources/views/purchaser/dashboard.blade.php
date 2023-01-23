@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'purchaser/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="productionDashboardErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="productionDashboard">
                <?php /* ?>
                <div class="dashboard-item-container" onclick="window.location.href='{{url('purchaser/design-list')}}'">
                    <i class="fas fa-drafting-compass dashboard-item-icon" ></i>
                    <div class="clear"></div>
                    <a href="{{url('purchaser/design-list')}}" class="btn btn-dialog">Design List</a>
                </div>
                
                <div class="dashboard-item-container" onclick="window.location.href='{{url('quotation/list')}}'">
                    <i class="fa fa-envelope dashboard-item-icon" ></i>
                    <div class="clear"></div>
                    <a href="{{url('quotation/list')}}" class="btn btn-dialog">Quotations List</a>
                </div>
                
                <div class="dashboard-item-container" onclick="window.location.href='{{url('purchase-orders/list')}}'">
                    <i class="fa fa-shopping-bag dashboard-item-icon" ></i>
                    <div class="clear"></div>
                    <a href="{{url('purchase-orders/list')}}" class="btn btn-dialog">Purchase Orders</a>
                </div> <?php */ ?>
                
                  
                <!--<a href="{{url('purchaser/design-list')}}" class="btn btn-dialog">Design List</a>&nbsp;&nbsp;
                <a href="{{url('quotation/list')}}" class="btn btn-dialog">Quotations List</a>&nbsp;&nbsp;
                <a href="{{url('purchase-orders/list')}}" class="btn btn-dialog">Purchase Orders</a> -->
                
                <div class="row">
                    <div class="col-md-2"><a href="{{url('purchase-order/product/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Purchase Orders</a></div>
                    <div class="col-md-2"><a href="{{url('purchase-order/bulk/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Fabric Purchase Orders</a></div>
                    <div class="col-md-2"><a href="{{url('vendor/report/sales')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck fas-icon" ></i> Vendor Sales Report</a></div>
                    <div class="col-md-2"><a href="{{url('product-sku/details')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon" ></i> SKU Details</a></div>
                    <div class="col-md-2"><a href="{{url('purchase-order/product/grn/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon" ></i> SOR GRN List</a></div>
                </div>
                <div class="row">&nbsp;</div>
                
                 <div class="row">
                    <div class="col-md-2"><a href="{{url('purchaser/design-list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-desktop fas-icon" ></i> Design List</a></div>
                    <div class="col-md-2"><a href="{{url('report/shelf/life')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-desktop fas-icon" ></i> Shelf Life Report</a></div>
                    <!--<div class="col-md-2"><a href="{{url('purchase-order/bulk/finished/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Stitching Purchase Orders</a></div>
                    <div class="col-md-2"><a href="{{url('quotation/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-save fas-icon" ></i> Quotations List</a></div>
                    <div class="col-md-2"><a href="{{url('purchase-orders/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Purchase Orders</a></div>
                    <div class="col-md-2"><a href="javascript:;" class="btn btn-dialog dashboard-btn"><i class="fa fa-cart-plus fas-icon" ></i> GRN List</a></div>-->
                    <div class="col-md-2"><a href="{{url('purchase-order/accessories/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Accessories Purchase Orders</a></div>
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.1') }}" ></script>
@endsection
