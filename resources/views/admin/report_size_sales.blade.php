@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Size Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Size Sales Report'); $page_name = 'size_sales_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2"><a href="{{url('size/report/sales?action=download_csv_size&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            
            <div class="separator-10"></div>
            
            <div id="orderContainer" class="table-container">
                <div id="ordersList">

                    <div class="table-responsive table-filter">
                        <h6>Size Report</h6>
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th>Size</th>
                                    <th>Total Pushed to Store Units</th>
                                    <th>Pushed to Store Units</th>
                                    <th>Sold Units</th>
                                    <th>Balance Units</th>
                                    <th>Sell Through</th>
                                    <th>Total NET Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_balance'=>0,'inv_sold_price'=>0); ?>
                                @for($i=0;$i<count($size_type_list);$i++)
                                    <tr>
                                        <td>{{$size_type_list[$i]['size']}}</td>
                                        <td>{{$size_type_list[$i]['inv_pushed_total']}}</td>
                                        <td>{{$size_type_list[$i]['inv_pushed']}}</td>
                                        <td>{{$size_type_list[$i]['inv_sold']}}</td>
                                        <td>{{$size_type_list[$i]['inv_balance']}}</td>
                                        <td>@if($size_type_list[$i]['inv_balance']+$size_type_list[$i]['inv_sold'] > 0) {{round(($size_type_list[$i]['inv_sold']/($size_type_list[$i]['inv_balance']+$size_type_list[$i]['inv_sold']))*100,3)}} % @endif</td>
                                        <td>{{CommonHelper::currencyFormat($size_type_list[$i]['inv_sold_price'])}}</td>
                                        
                                    </tr>
                                    <?php $total_data['inv_pushed_total']+=$size_type_list[$i]['inv_pushed_total']; ?>
                                    <?php $total_data['inv_pushed']+=$size_type_list[$i]['inv_pushed']; ?>
                                    <?php $total_data['inv_sold']+=$size_type_list[$i]['inv_sold']; ?>
                                    <?php $total_data['inv_balance']+=$size_type_list[$i]['inv_balance']; ?>
                                    <?php $total_data['inv_sold_price']+=$size_type_list[$i]['inv_sold_price']; ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th>{{$total_data['inv_pushed_total']}}</th>
                                    <th>{{$total_data['inv_pushed']}}</th>
                                    <th>{{$total_data['inv_sold']}}</th>
                                    <th>{{$total_data['inv_balance']}}</th>
                                    <th></th>
                                    <th>{{CommonHelper::currencyFormat($total_data['inv_sold_price'])}}</th>
                                    
                                </tr>
                            </tfoot>
                        </table>
                    </div>    
                    
                    <hr/>

                    <h6>Store Size Report</h6>
                    <form method="get">
                        <div class="row justify-content-end" >
                            <div class="col-md-2"><a href="{{url('size/report/sales?action=download_csv_size_store&'.$query_str)}}" class="btn btn-dialog" >Download </a></div>
                        </div>
                        <div class="separator-10"></div>
                    </form>
                    
                    <div class="table-responsive table-filter">    
                        <table class="table table-striped admin-table " cellspacing="0" id="sizeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th>Store</th>
                                    <th >Size</th>
                                    <th>Sold Units</th>
                                    <th>Balance Units</th>
                                    <th>Total NET Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_units = $total_price = $store_units_total = $store_total_price = $total_bal_units = $store_bal_units = 0; ?>
                                @for($i=0;$i<count($size_list);$i++)
                                    <tr>
                                        <td>{{$size_list[$i]->store_name}} ({{$size_list[$i]->store_id_code}})</td>
                                        <td>{{$size_list[$i]->size_name}}</td>
                                        <td>{{$size_list[$i]->size_count}}</td>
                                        <td>{{$size_list[$i]->balance_inv}}</td>
                                        <td>{{CommonHelper::currencyFormat($size_list[$i]->size_net_price)}}</td>
                                    </tr>
                                    
                                    <?php $total_units+=$size_list[$i]->size_count; ?>
                                    <?php $total_price+=$size_list[$i]->size_net_price; ?>
                                    <?php $total_bal_units+=$size_list[$i]->balance_inv; ?>
                                    <?php $store_units_total+=$size_list[$i]->size_count; ?>
                                    <?php $store_total_price+=$size_list[$i]->size_net_price; ?>
                                    <?php $store_bal_units+=$size_list[$i]->balance_inv; ?>
                                    
                                    @if((isset($size_list[$i+1]->store_name) && $size_list[$i]->store_name != $size_list[$i+1]->store_name) || (!isset($size_list[$i+1]->store_name)) )
                                        <tr>
                                            <th >Total</th>
                                            <th></th>
                                            <th>{{$store_units_total}}</th>
                                            <th>{{$store_bal_units}}</th>
                                            <th>{{CommonHelper::currencyFormat($store_total_price)}}</th>
                                        </tr>
                                        <?php $store_units_total = $store_total_price = $store_bal_units = 0; ?>
                                    @endif
                                   
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr class="total-tr">
                                    <th>Total</th>
                                    <th></th>
                                    <th>{{$total_units}}</th>
                                    <th>{{$total_bal_units}}</th>
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
        $('.report-sort-1').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [[ 2, "desc" ]] });
        $('.report-sort-2').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [] });
    });
</script>
@endsection
