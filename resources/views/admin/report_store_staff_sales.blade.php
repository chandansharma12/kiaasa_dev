@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Staff Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Staff Sales Report');$page_name = 'store_staff_sales_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    @if($user->user_type != 9)
                        <div class="col-md-3" >
                            <select name="s_id" id="s_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor
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
                    <div class="col-md-2"><a href="{{url('store/staff/report/sales?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th style="width:25%;">Staff Member</th>
                                    <th>Store Name</th>
                                    <th>Store Code</th>
                                    <th>Total Units</th>
                                    <th>Total NET Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_units = $total_price = 0; ?>
                                @for($i=0;$i<count($staff_list);$i++)
                                    <tr>
                                        <td>{{$staff_list[$i]->staff_name}}</td>
                                        <td>{{$staff_list[$i]->store_name}}</td>
                                        <td>{{$staff_list[$i]->store_id_code}}</td>
                                        <td>{{$staff_list[$i]->prod_count}}</td>
                                        <td>{{CommonHelper::currencyFormat($staff_list[$i]->prod_net_price)}}</td>
                                    </tr>
                                    <?php $total_units+=$staff_list[$i]->prod_count; ?>
                                    <?php $total_price+=$staff_list[$i]->prod_net_price; ?>
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total Sales</th>
                                    <th>{{$total_units}}</th>
                                    <th>{{CommonHelper::currencyFormat($total_price)}}</th>
                                </tr>
                                <tr>
                                    <th colspan="3">Total Returns</th>
                                    <th>{{abs($return_products_list->prod_count)}}</th>
                                    <th>{{CommonHelper::currencyFormat(abs($return_products_list->prod_net_price))}}</th>
                                </tr>
                                <tr>
                                    <th colspan="3">Total</th>
                                    <th>{{$total_units+$return_products_list->prod_count}}</th>
                                    <th>{{CommonHelper::currencyFormat($total_price+$return_products_list->prod_net_price)}}</th>
                                </tr>
                            </tfoot>
                        </table>
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
<script src="{{ asset('js/datatables.min.js') }}" ></script>
<link rel="stylesheet" type="text/css" href="{{asset('css/datatables.min.css')}}"/>
<script type="text/javascript">
    $(document).ready(function(){
        $('.report-sort').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader": true,"order": [[ 3, "desc" ]] });
    });
</script>
@endsection
