@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'audit/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="auditorDashboard">
                <div class="row">
                    <div class="col-md-2"><a href="{{url('audit/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-bars fas-icon"></i> Audit List</a>&nbsp</div>
                
                    <div class="col-md-2"><a href="{{url('pos/product/inventory/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i> Inventory List</a>&nbsp</div>
                </div>
            </div>
               
        </div>
    </section>

@endif

@endsection

@section('scripts')
@endsection
