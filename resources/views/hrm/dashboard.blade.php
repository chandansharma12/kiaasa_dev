@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'hrm/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="dashboardErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <section class="product_area">
                <div class="container-fluid" >
                    
                    <div id="productionDashboard">
                        <div class="row">
                            <div class="col-md-2"><a href="{{url('user/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-user fas-icon"></i> Employees</a>&nbsp</div>
                            <div class="col-md-2"><a href="{{url('user/attendance/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-user fas-icon"></i> Attendance</a>&nbsp</div>
                            <div class="col-md-2"><a href="{{url('user/leaves/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-user fas-icon"></i> Leaves</a>&nbsp</div>
                            <div class="col-md-2"><a href="{{url('user/overtime/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-user fas-icon"></i> Overtime</a>&nbsp</div>
                            <div class="col-md-2"><a href="{{url('store/expense/monthly/list/1')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-user fas-icon"></i> Store Monthly Salary</a>&nbsp</div>
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
