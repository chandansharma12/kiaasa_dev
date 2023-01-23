@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Sales Report');$page_name = 'store_sales_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="inv_type" id="inv_type" class="form-control">
                            <option value="">Both Inventory Types</option>
                            <option value="north" @if(request('inv_type') == 'north') selected @endif>Northcorp</option>
                            <option value="arnon" @if(request('inv_type') == 'arnon') selected @endif>Arnon</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2"><a href="{{url('store/report/sales?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort-1 report-sort" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th width="15%">Store</th>
                                    <!--<th>Total Pushed to Store Units</th>-->
                                    <th>Pushed to Store Units</th>
                                    <th>Return to WH Units</th>
                                    <th colspan="3" align="center" style="text-align: center;border-left:1px solid #fff;border-right:1px solid #fff;">Sold Units</th>
                                    <th>Balance Units</th>
                                    <th>Sell Through</th>
                                    <th>Total NET Price</th>
                                </tr>
                                <tr class="header-tr">
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th style="border-left:1px solid #fff;">SOR</th>
                                    <th >Purchase</th>
                                    <th style="border-right:1px solid #fff;">Total</th>
                                    <th colspan="4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_balance'=>0,'inv_sold_price'=>0,'sor_total'=>0,'purchase_total'=>0,'inv_return'=>0); ?>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sor_key = $store_list[$i]['id'].'_322';  ?>
                                    <?php $purchase_key = $store_list[$i]['id'].'_324';  ?>
                                    <tr>
                                        <td width="15%">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</td>
                                        <!--<td>{{$store_list[$i]['inv_pushed_total']}}</td>-->
                                        <td>{{$store_list[$i]['inv_pushed']}}</td>
                                        <td>{{$store_list[$i]['inv_return']}}</td>
                                        <td>{{$sor_units = isset($po_category_sale_data[$sor_key])?$po_category_sale_data[$sor_key]->prod_count:0}}</td>
                                        <td>{{$purchase_units = isset($po_category_sale_data[$purchase_key])?$po_category_sale_data[$purchase_key]->prod_count:0}}</td>
                                        <td>{{$store_list[$i]['inv_sold']}}</td>
                                        <td>{{$store_list[$i]['inv_balance']}}</td>
                                        <td>@if($store_list[$i]['inv_balance']+$store_list[$i]['inv_sold'] > 0) {{round(($store_list[$i]['inv_sold']/($store_list[$i]['inv_balance']+$store_list[$i]['inv_sold']))*100,3)}} % @endif</td>
                                        <td>{{CommonHelper::currencyFormat($store_list[$i]['inv_sold_price'])}}</td>
                                        
                                    </tr>
                                    <?php $total_data['inv_pushed_total']+=$store_list[$i]['inv_pushed_total']; ?>
                                    <?php $total_data['inv_pushed']+=$store_list[$i]['inv_pushed']; ?>
                                    <?php $total_data['inv_sold']+=$store_list[$i]['inv_sold']; ?>
                                    <?php $total_data['inv_balance']+=$store_list[$i]['inv_balance']; ?>
                                    <?php $total_data['inv_sold_price']+=$store_list[$i]['inv_sold_price']; ?>
                                    <?php $total_data['sor_total']+=$sor_units; ?>
                                    <?php $total_data['purchase_total']+=$purchase_units; ?>
                                    <?php $total_data['inv_return']+=$store_list[$i]['inv_return']; ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th width="15%">Total</th>
                                    <!--<th>{{$total_data['inv_pushed_total']}}</th>-->
                                    <th>{{$total_data['inv_pushed']}}</th>
                                    <th>{{$total_data['inv_return']}}</th>
                                    <th>{{$total_data['sor_total']}}</th>
                                    <th>{{$total_data['purchase_total']}}</th>
                                    <th>{{$total_data['inv_sold']}}</th>
                                    <th>{{$total_data['inv_balance']}}</th>
                                    <th></th>
                                    <th>{{CommonHelper::currencyFormat($total_data['inv_sold_price'])}}</th>
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
        $('.report-sort').DataTable({ "autoWidth": false,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [[ 0, "asc" ]] });
    });
</script>
@endsection