@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Expenses Master Values','link'=>'store/expense/master/list/'.$store_data->id),array('name'=>'Edit Store Expenses Master Values')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Expenses Master Values: '.$store_data->store_name.' ('.$store_data->store_id_code.')'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                </div>
            </form>
            
            <div class="alert alert-success alert-dismissible elem-hidden" id="updateStoreMasterExpenseSuccessMessage"></div>
            <div class="alert alert-danger alert-dismissible elem-hidden"  id="updateStoreMasterExpenseErrorMessage"></div>
            
            <div class="separator-10">&nbsp;</div>
            <form method="post" name="updateStoreMasterExpenseFrm" id="updateStoreMasterExpenseFrm">
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
                            <?php $id = $store_expense_master_values[$i]->expense_id; ?>
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$store_expense_master_values[$i]->expense_name}}</td>
                                    <td>
                                        <input type="text" name="expense_{{$id}}" id="expense_{{$id}}" value="{{$store_expense_master_values[$i]->expense_value}}">
                                        <div class="invalid-feedback" id="error_validation_expense_{{$id}}"></div>
                                    </td>
                                    <td>{{$store_expense_master_values[$i]->expense_category}}</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <input type="hidden" name="store_id" id="store_id" value="{{$store_data->id}}">
                    <div class="col-md-3" >
                        <input type="button" name="updateStoreMasterExpenseCancel" id="updateStoreMasterExpenseCancel" value="Cancel" class="btn btn-secondary" onclick="cancelUpdateStoreMasterExpense();">        
                        <input type="button" name="updateStoreMasterExpenseBtn" id="updateStoreMasterExpenseBtn" value="Update" class="btn btn-dialog" onclick="updateStoreMasterExpense();">
                        <br/><br/>
                    </div>
                </div>
            </form>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js') }}" ></script>
@endsection
