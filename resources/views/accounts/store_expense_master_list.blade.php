@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store List','link'=>'store/list'),array('name'=>'Store Expenses Master Values')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Expenses Master Values: '.$store_data->store_name.' ('.$store_data->store_id_code.')'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <a type="button"  class="btn btn-dialog" href="{{url('store/expense/master/edit/'.$store_data->id)}}"><i title="Edit Values" class="far fa-edit fas-icon"></i> Edit Values</a>
                    </div>
                </div>
            </form>
            
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead><tr class="header-tr">
                                <th>SNo</th>
                                <th>Expense Name</th>
                                <th>Expense Value</th>
                                <th>Expense Category</th>
                        </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($store_expense_master_values);$i++)
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$store_expense_master_values[$i]->expense_name}}</td>
                                    <td>{{$store_expense_master_values[$i]->expense_value}}</td>
                                    <td>{{$store_expense_master_values[$i]->expense_category}}</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <input type="hidden" name="store_id" id="store_id" value="{{$store_data->id}}">
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js') }}" ></script>
@endsection
