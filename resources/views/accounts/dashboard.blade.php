@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'accounts/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="productionDashboard">
               <div class="row">
                    <div class="col-md-2"><a href="{{url('vendor/report/sales')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i> Vendor Sales Report</a></div>
                    <div class="col-md-2"><a href="{{url('debit/notes/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-book fas-icon"></i> Debit Notes</a></div>
                    <div class="col-md-2"><a href="{{url('credit/notes/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-book fas-icon"></i> Credit Notes</a></div>
                    <div class="col-md-2"><a href="{{url('store/asset/order/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Asset Orders List</a></div>
                    <div class="col-md-2"><a href="{{url('accounts/asset/order/items-list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-cart fas-icon"></i> Asset Orders Items List</a></div>
                </div>
                
                <div class="row">&nbsp;</div>
                
                <div class="row">
                    <div class="col-md-2"><a href="{{url('store/demand/inventory-transfer-store/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck fas-icon"></i>&nbsp; Store to Store Transfers</a></div>
                    <div class="col-md-2"><a href="{{url('vendor/inventory/payment/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-qrcode fas-icon" ></i> Vendor Payments</a></div>
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')

@endsection
