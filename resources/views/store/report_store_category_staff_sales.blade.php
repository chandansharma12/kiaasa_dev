@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Category Staff Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Category Staff Sales Report');$page_name = 'store_category_staff_sales_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="GET">
                <div class="row justify-content-end" >
                    @if($user->user_type != 9)
                        <div class="col-md-2" >
                            <select name="s_id" id="s_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @foreach($store_list as $id=>$store_data)
                                    <?php $sel = ($id == request('s_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$id}}">{{$store_data['store_name']}} ({{$store_data['store_id_code']}})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2"><a href="{{url('report/store/category/staff/sales?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0"  id="storeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th>Date</th>
                                    <th>Store Name</th>
                                    <th>Code</th>
                                    <th>Category</th>
                                    <th>Staff</th>
                                    <th>Sale Qty</th>
                                    <th>Sale NET Price</th>
                                    <th>Bal Qty</th>
                                    <th>Bal MRP Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total = array('qty'=>0,'net_price'=>0); ?>
                                @for($i=0;$i<count($orders_list);$i++)
                                    <?php $key = strtotime($orders_list[$i]->order_date).'_'.$orders_list[$i]->store_id.'_'.$orders_list[$i]->category_id; ?>
                                    <tr>
                                        <td>{{date('d-m-Y',strtotime($orders_list[$i]->order_date))}}</td>
                                        <td>{{$store_list[$orders_list[$i]->store_id]['store_name']}}</td>
                                        <td>{{$store_list[$orders_list[$i]->store_id]['store_id_code']}}</td>
                                        <td>{{$category_list[$orders_list[$i]->category_id]['name']}}</td>
                                        <td>{{isset($staff_list[$orders_list[$i]->staff_id]['name'])?$staff_list[$orders_list[$i]->staff_id]['name']:''}}</td>
                                        <td>{{$orders_list[$i]->prod_count}}</td>
                                        <td>{{CommonHelper::currencyFormat($orders_list[$i]->prod_net_price)}}</td>
                                        <td>{{isset($inv_bal[$key]['bal_qty'])?$inv_bal[$key]['bal_qty']:''}}</td>
                                        <td>{{isset($inv_bal[$key]['bal_value'])?CommonHelper::currencyFormat($inv_bal[$key]['bal_value']):''}}</td>
                                    </tr>
                                    <?php $total['qty']+=$orders_list[$i]->prod_count; ?>
                                    <?php $total['net_price']+=$orders_list[$i]->prod_net_price; ?>
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr>
                                    <th colspan="5">Page Total</th>
                                    <th>{{$total['qty']}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['net_price'])}}</th>
                                </tr>
                                <tr>
                                    <th colspan="5">Total</th>
                                    <th>{{$orders_list_total->prod_count}}</th>
                                    <th>{{CommonHelper::currencyFormat($orders_list_total->prod_net_price)}}</th>
                                </tr>
                            </tfoot>
                        </table>
                        {{ $orders_list->withQueryString()->links() }}
                        <p>Displaying {{$orders_list->count()}} of {{ $orders_list->total() }} records.</p>
                        <br/>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>

@endsection
