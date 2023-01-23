@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'asm/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="dashboardErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <section class="product_area">
                <div class="container-fluid" >
                    <div id="productionDashboard">
                        <div class="row">
                            <div class="col-md-2"><a href="{{url('store/report/sales/dates')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-chart-line fas-icon"></i> Stores Sales Report</a>&nbsp</div>
                            <div class="col-md-2"><a href="{{url('pos/product/inventory/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i> Inventory</a>&nbsp</div>
                            <div class="col-md-2"><a href="{{url('audit/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-bars fas-icon"></i> Audits</a>&nbsp</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>

@endif

@endsection

@section('scripts')

@endsection
