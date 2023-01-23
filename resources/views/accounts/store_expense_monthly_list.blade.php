@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Expenses Monthly Values')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Expenses Monthly Values '); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    
                    <div class="col-md-2" >
                        <select name="s_id" id="s_id" class="form-control">
                            <option value="">-- Store --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == $store_data->id)?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div>
                    
                    <div class="col-md-2" >
                        <select name="expense_date" id="expense_date" class="form-control">
                            <option value="">-- Month --</option>
                            @for($i=0;$i<count($expense_dates);$i++)
                                <?php $sel = (strtotime($expense_dates[$i]) == strtotime($expense_date))?'selected':''; ?>
                                <option {{$sel}} value="{{$expense_dates[$i]}}">{{date('F Y',strtotime($expense_dates[$i]))}} </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-1"><input type="button" name="search" id="search" value="Search" class="btn btn-dialog" onclick="searchStoreMonthlyExpense();"></div>
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    @if(($user->user_type == 1) || (in_array($user->user_type,[9,17]) && $expense_date == $date))
                        <div class="col-md-1"><a title="Edit Monthly Data" href="{{url('store/expense/monthly/edit/'.$store_id.'?'.$query_str)}}" class="btn btn-dialog" ><i title="Edit Monthly Data" class="far fa-edit fas-icon"></i> Edit</a></div>
                    @endif
                    
                    @if(in_array($user->user_type,array(1,9)))
                        <div class="col-md-1"><a href="{{url('store/expense/monthly/list/'.$store_id.'?action=download_csv&'.$query_str)}}" class="btn btn-dialog" ><i title="Download CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                    @endif
                </div>
            </form>
            
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead><tr class="header-tr">
                                <th>SNo</th>
                                <th>Store</th>
                                <th>Month</th>
                                <th>Expense Name</th>
                                <th>Expense Value</th>
                                <th>Expense Category</th>
                        </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($store_expense_monthly_values);$i++)
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$store_expense_monthly_values[$i]->store_name}} ({{$store_expense_monthly_values[$i]->store_id_code}})</td>
                                    <td>{{date('F Y',strtotime($store_expense_monthly_values[$i]->expense_date))}}</td>
                                    <td>{{$store_expense_monthly_values[$i]->expense_name}}</td>
                                    <td>{{$store_expense_monthly_values[$i]->expense_value}}</td>
                                    <td>{{$store_expense_monthly_values[$i]->expense_category}}</td>
                                </tr>
                            @endfor
                            @if(empty($store_expense_monthly_values))
                                <tr><td colspan="6" align="center">No Records</td></tr>
                            @endif
                        </tbody>
                    </table>
                    
                    <input type="hidden" name="store_id" id="store_id" value="{{$store_data->id}}">
                    <input type="hidden" name="expense_date" id="expense_date" value="{{$expense_date}}">
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js') }}" ></script>
@endsection
