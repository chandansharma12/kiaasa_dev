@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'operation/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="dashboardErrorMessage" class="alert alert-danger elem-hidden"></div>
            <section class="product_area">
                <div class="container-fluid" >
                    <div id="productionDashboard">
                        <div class="row">
                            <div class="col-md-2"><a href="{{url('purchase-order/product/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Purchase Orders</a></div>
                            <div class="col-md-2"><a href="{{url('store/report/sales/dates')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Stores Sales Report</a>&nbsp</div>
                            <div class="col-md-2"><a href="{{url('purchaser/design-list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-desktop fas-icon" ></i> Design List</a></div>
                            <div class="col-md-2"><a href="{{url('warehouse/demand/inventory-assign/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-desktop fas-icon" ></i> Assign Inventory</a></div>
                            <?php /* ?>
                            <div class="col-md-2"><a href="{{url('vendor/report/sales')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Vendor Sales Report</a></div>
                            <div class="col-md-2"><a href="{{url('report/store/to/customer')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Store to Customer Bill Report</a></div>
                            <div class="col-md-2"><a href="{{url('report/store/to/customer?report_type=date')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Store to Customer Date Report</a></div>
                            <?php */ ?>
                        </div>
                        
                        <?php /* ?>
                        <div class="row">
                            <div class="col-md-2"><a href="{{url('report/store/to/customer?report_type=month')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Store to Customer Month Report</a></div>
                            <div class="col-md-2"><a href="{{url('report/store/to/customer?report_type=hsn_code')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Store to Customer HSN Code Report</a></div>
                            <div class="col-md-2"><a href="{{url('report/warehouse/to/store')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Warehouse to Store Report</a></div>
                            <div class="col-md-2"><a href="{{url('report/warehouse/to/store?report_type=hsn_code')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Warehouse to Store HSN Code Report</a></div>
                            <div class="col-md-2"><a href="{{url('warehouse/demand/inventory-assign/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file-signature fas-icon"></i> Assign Inventory</a></div>
                        </div><?php */ ?>
                    </div>
                </div>
            </section>
        </div>
    </section>

@endif

@endsection

@section('scripts')

@endsection
