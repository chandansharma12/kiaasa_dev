@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'store/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="storeDashboardErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="storeDashboard">
                @if($user->store_owner == 0)
                    <div class="row">
                        <div class="col-md-2"><a href="{{url('store/posbilling')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-cart fas-icon"></i> &nbsp;Point of Sale</a></div>
                        <div class="col-md-2"><a href="{{url('pos/order/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-cart-plus fas-icon"></i> &nbsp;Orders</a></div>
                        <div class="col-md-2"><a href="{{url('store/report/sales/dates')}}" class="btn btn-dialog dashboard-btn"> <i class="fa fa-chart-line fas-icon"></i> &nbsp;Daily Sales Report</a></div>
                        <div class="col-md-2"><a href="{{url('store/demand/inventory-push/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-dolly-flatbed fas-icon"></i>&nbsp; Stock In</a></div>
                        <div class="col-md-2"><a href="{{url('store/demand/inventory-return/list')}}" class="btn btn-dialog dashboard-btn"> <i class="fa fa-dolly-flatbed fas-icon"></i>&nbsp; Stock Return</a></div>
                    </div>
                    <div class="row">&nbsp;</div>
                    <div class="row">
                        <div class="col-md-2"><a href="{{url('store/pos/inventory/list?status=4')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i>&nbsp; Inventory</a></div>
                        <div class="col-md-2"><a href="{{url('pos/product/inventory/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i>&nbsp; Inventory List</a></div>
                        <div class="col-md-2"><a href="{{url('pos/product/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i>&nbsp; Kiaasa Products</a></div>
                        <div class="col-md-2"><a href="{{url('store/staff/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-user fas-icon"></i>&nbsp; Staff</a></div>
                        <div class="col-md-2"><a href="{{url('store/staff/report/sales')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i>&nbsp; Staff Sales Report</a></div>
                    </div>
                    <div class="row">&nbsp;</div>
                    <div class="row">
                        <!--<div class="col-md-2"><a href="{{url('store/asset/order/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i>&nbsp; Assets</a></div>-->
                        <div class="col-md-2"><a href="{{url('store/inventory/review')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-dolly-flatbed fas-icon"></i>&nbsp; Inventory Review</a></div>
                        <div class="col-md-2"><a href="{{url('store/demand/inventory-transfer-store/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck fas-icon"></i>&nbsp; Store to Store Transfers</a></div>
                        <div class="col-md-2"><a href="{{url('report/hsn/bill/sales')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i>&nbsp; HSN Code Bill Sales Report</a></div>
                        <div class="col-md-2"><a href="{{url('category/report/sales')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i>&nbsp; Category Sales Report</a></div>
                        <div class="col-md-2"><a href="{{url('pos/orders/draft/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-save fas-icon"></i>&nbsp; POS Order Drafts</a></div>
                    </div>
                    <div class="row">&nbsp;</div>
                    <div class="row">
                        <div class="col-md-2"><a href="{{url('store/expense/monthly/list/'.$user_store->store_id)}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-save fas-icon"></i>&nbsp; Monthly Data</a></div>
                        <div class="col-md-2"><a href="{{url('store/demand/inventory-return-complete/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck-loading fas-icon"></i>&nbsp; Complete Stock Return</a></div>
                    </div>
                @endif
                
                @if($user->store_owner == 1)
                    <div class="col-md-2"><a href="{{url('store/report/sales/dates')}}" class="btn btn-dialog dashboard-btn"> <i class="fa fa-chart-line fas-icon"></i> &nbsp;Daily Sales Report</a></div>
                @endif
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.1') }}" ></script>
@endsection
