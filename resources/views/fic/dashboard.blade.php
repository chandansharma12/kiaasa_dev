@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'fic/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="dashboardErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <section class="product_area">
                <div class="container-fluid" >
                    
                    <div class="row">
                        <div class="col-md-2"><a href="{{url('purchase-order/product/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Purchase Orders</a></div>
                        <div class="col-md-2"><a href="{{url('warehouse/demand/inventory-push/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck fas-icon" ></i> Inventory Push Demands</a></div>
                        <div class="col-md-2"><a href="{{url('pos/order/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-cart-plus fas-icon" ></i> POS Orders</a></div>
                        <div class="col-md-2"><a href="{{url('pos/order/series/update/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon" ></i> POS Order Updates</a></div>
                        <div class="col-md-2"><a href="{{url('pos/product/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Products List</a></div>
                    </div>
                    <div class="row">&nbsp;</div>
                    <div class="row">
                        <div class="col-md-2"><a href="{{url('pos/product/inventory/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon" ></i> Inventory</a></div>
                        <div class="col-md-2"><a href="{{url('store/pos/inventory/list?status=4&store_id=1')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon" ></i> Store Inventory</a></div>
                        <div class="col-md-2"><a href="{{url('store/report/sales/dates')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i> Daily Sales Report</a></div>
                        <div class="col-md-2"><a href="{{url('report/store/to/customer')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i> Store to Customer Sales Report (Bill) </a></div>
                        <div class="col-md-2"><a href="{{url('report/store/to/customer?report_type=date')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i> Store to Customer Sales Report (Date)</a></div>
                    </div>
                    <div class="row">&nbsp;</div>
                    <div class="row">
                        <div class="col-md-2"><a href="{{url('report/store/to/customer?report_type=month')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i> Store to Customer Sales Report (Month)</a></div>
                        <div class="col-md-2"><a href="{{url('report/store/to/customer?report_type=hsn_code')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i> Store to Customer Sales Report (HSN Code)</a></div>
                        <div class="col-md-2"><a href="{{url('report/warehouse/to/store')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck fas-icon"></i> Warehouse to Store Sales Report (Bill)</a></div>
                        <div class="col-md-2"><a href="{{url('report/warehouse/to/store?report_type=hsn_code')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck fas-icon"></i> Warehouse to Store Sales Report (HSN Code)</a></div>
                    </div>
                </div>
            </section>
        </div>
    </section>

@endif

@endsection

@section('scripts')

@endsection
