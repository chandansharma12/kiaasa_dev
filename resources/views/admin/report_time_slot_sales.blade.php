@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Time Slot Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Time Slot Sales Report');$page_name = 'time_slot_sales_report'; ?>

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
                    
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    <div class="col-md-2"><a href="{{url('time-slot/report/sales?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th>Time Slot</th>
                                    <th>Total Units</th>
                                    <th>Total NET Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_units = $total_price = 0; ?>
                                @for($i=0;$i<count($orders_list);$i++)
                                <?php $start_hour = $orders_list[$i]->order_hour;$end_hour = $orders_list[$i]->order_hour+1; ?>
                                <?php if($start_hour >= 12 ) {if($start_hour != 12) $start_hour = $start_hour-12; $start_hour.=' PM'; } else{ $start_hour.=' AM';} ?>
                                <?php if($end_hour >= 12 ) {if($end_hour != 12) $end_hour = $end_hour-12; $end_hour.=' PM'; } else{ $end_hour.=' AM';} ?>
                                    <tr>
                                        <td>{{$start_hour}} - {{$end_hour}}</td>
                                        <td>{{$orders_list[$i]->prod_count}}</td>
                                        <td>{{CommonHelper::currencyFormat($orders_list[$i]->prod_net_price)}}</td>
                                    </tr>
                                    <?php $total_units+=$orders_list[$i]->prod_count; ?>
                                    <?php $total_price+=$orders_list[$i]->prod_net_price; ?>
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th>{{$total_units}}</th>
                                    <th>{{CommonHelper::currencyFormat($total_price)}}</th>
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
        $('.report-sort').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader": true,"order": [] });
    });
</script>
@endsection
