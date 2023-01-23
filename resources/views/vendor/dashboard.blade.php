@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'vendor/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="productionDashboardErrorMessage" class="alert alert-danger elem-hidden"></div>
            <section class="product_area">
                <div class="container-fluid" >
                    <div id="productionDashboard">
                        <div class="row">
                            <div class="col-md-2"><a href="{{url('vendor/report/sales')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon" ></i> Sales Report</a></div>
                            <div class="col-md-2"><a href="{{url('debit/notes/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-barcode fas-icon" ></i> Debit Notes</a></div>
                            <div class="col-md-2"><a href="{{url('vendor/inventory/status')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon" ></i> Inventory Status</a></div>
                            <div class="col-md-2"><a href="{{url('warehouse/demand/inventory-return-vendor/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck fas-icon" ></i> Stock Return</a></div>
                            <div class="col-md-2"><a href="{{url('vendor/sku/inventory/report')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-qrcode fas-icon"></i> Vendor SKU Inventory Report</a> </div>
                        </div>
                        <div class="row">&nbsp;</div>
                        <div class="row">
                            <div class="col-md-2"><a href="{{url('vendor/inventory/payment/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-qrcode fas-icon" ></i> Payments</a></div>
                            <div class="col-md-2"><a href="{{url('store/sku/inventory/report')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i> Store SKU Inventory Report</a></div>
                            <div class="col-md-2"><a href="{{url('pos/product/inventory/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i> Inventory List</a></div>
                            <div class="col-md-2"><a href="{{url('grn/sku/report')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i> GRN SKU Report</a></div>
                        </div>
                        <br>
                    </div>
                </div>
            </section>
        </div>
    </section>

@endif

@endsection

@section('scripts')

@endsection
