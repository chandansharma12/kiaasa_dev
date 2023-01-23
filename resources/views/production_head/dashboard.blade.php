@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'production-head/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="productionDashboardErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="productionDashboard">
                <a href="{{url('design/list')}}" class="btn btn-dialog">Design List</a>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/production.js') }}" ></script>
@endsection
