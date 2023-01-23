@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Expenses Monthly Values','link'=>'store/expense/monthly/list/'.$store_data->id),array('name'=>'Edit Store Expenses Monthly Values')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Expenses Monthly Values: '.$store_data->store_name.' ('.$store_data->store_id_code.') ('.date('F Y',strtotime($expense_date)).')'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                </div>
            </form>
            
            <div class="alert alert-success alert-dismissible elem-hidden" id="updateStoreMonthlyExpenseSuccessMessage"></div>
            <div class="alert alert-danger alert-dismissible elem-hidden"  id="updateStoreMonthlyExpenseErrorMessage"></div>
            
            <div class="separator-10">&nbsp;</div>
            <form method="post" name="updateStoreMonthlyExpenseFrm" id="updateStoreMonthlyExpenseFrm">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead><tr class="header-tr">
                            <th>SNo</th>
                            <th>Expense Name</th>
                            <th>Expense Value</th>
                        </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($store_expense_monthly_values);$i++)
                            <?php $id = $store_expense_monthly_values[$i]->expense_id; ?>
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$store_expense_monthly_values[$i]->expense_name}}</td>
                                    <td>
                                        <input type="text" name="expense_{{$id}}" id="expense_{{$id}}" value="{{$store_expense_monthly_values[$i]->expense_value}}">
                                        <div class="invalid-feedback" id="error_validation_expense_{{$id}}"></div>
                                    </td>
                                    
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <input type="hidden" name="store_id" id="store_id" value="{{$store_data->id}}">
                    <div class="col-md-3" >
                        <input type="button" name="updateStoreMonthlyExpenseCancel" id="updateStoreMonthlyExpenseCancel" value="Cancel" class="btn btn-secondary" onclick="cancelUpdateStoreMonthlyExpense();">        
                        <input type="button" name="updateStoreMonthlyExpenseBtn" id="updateStoreMonthlyExpenseBtn" value="Update" class="btn btn-dialog" onclick="updateStoreMonthlyExpense();">
                    </div>
                    <div class="separator-10">&nbsp;</div>
                </div>
                <input type="hidden" name="expense_date" id="expense_date" value="{{$expense_date}}">
            </form>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js') }}" ></script>
@endsection
