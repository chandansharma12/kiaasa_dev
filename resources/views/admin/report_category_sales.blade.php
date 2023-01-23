@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $store_name = !empty($store_data)?': '.$store_data->store_name:''; ?>
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Category Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Category Sales Report'.$store_name); $page_name = 'category_sales_report'; ?>

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
                    <div class="col-md-2"><a href="{{url('category/report/sales?action=download_csv_cat&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                
                <div class="table-responsive table-filter">
                    <h6>Category Report</h6>
                    <table class="table table-striped admin-table report-sort-1" cellspacing="0" style="font-size:12px;">
                        <thead>
                            <tr class="header-tr">
                                <th></th>
                                <th colspan="6" align="center" style="text-align: center;border-left: 1px solid #fff;">Total</th>
                                <th colspan="4" align="center" style="text-align: center;border-left: 1px solid #fff;border-right: 1px solid #fff;">From {{date('d-m-Y',strtotime($start_date))}} - {{date('d-m-Y',strtotime($end_date))}}</th>    
                                @if($user->user_type != 9)
                                    <th></th>
                                @endif
                            </tr>
                            <tr class="header-tr">
                                <th style="width:12%;" >Category</th>
                                <th style="border-left: 1px solid #fff;border-top: none;">Received Units</th>
                                <th>Sold Units</th>
                                <th>Returned Units</th>
                                <th>Transferred Units</th>
                                <th>Balance Units</th>
                                <th>Balance Value</th>
                                <th style="border-left: 1px solid #fff;border-top: none;">Received Units</th>
                                <th>Sold Units</th>
                                <th>Sell Through</th>
                                <th>Total NET Price</th>
                                @if($user->user_type != 9)
                                    <th style="width:15%;text-align: center;border-left: 1px solid #fff;">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_balance'=>0,'inv_sold_price'=>0,'inv_sold_total'=>0,'inv_returned_total'=>0,'inv_transferred_total'=>0,'bal_inv_mrp'=>0); ?>
                            @for($i=0;$i<count($category_type_list);$i++)
                                <tr>
                                    <td>{{$category_type_list[$i]['name']}}</td>
                                    <td>{{$category_type_list[$i]['inv_pushed_total']}}</td>
                                    <td>{{$category_type_list[$i]['inv_sold_total']}}</td>
                                    <td>{{$category_type_list[$i]['inv_returned']+$category_type_list[$i]['inv_returned_complete']}}</td>
                                    <td>{{$category_type_list[$i]['inv_transfer_from_store']}}</td> 
                                    <td>{{$category_type_list[$i]['inv_balance']}}</td>
                                    <td>{{$category_type_list[$i]['bal_inv_mrp']}}</td>
                                    <td>{{$category_type_list[$i]['inv_pushed']}}</td>
                                    <td>{{$category_type_list[$i]['inv_sold']}}</td>
                                    <td>@if($category_type_list[$i]['inv_balance']+$category_type_list[$i]['inv_sold'] > 0) {{round(($category_type_list[$i]['inv_sold']/($category_type_list[$i]['inv_balance']+$category_type_list[$i]['inv_sold']))*100,3)}} % @endif</td>
                                    <td>{{CommonHelper::currencyFormat($category_type_list[$i]['inv_sold_price'])}}</td>
                                    @if($user->user_type != 9)
                                        <td><a class="table-link" href="{{url('subcategory/report/sales/'.$category_type_list[$i]['id'])}}?{{$query_str}}">Subcategory Report <i class="fa fa-arrow-circle-right"></i></a></td>
                                    @endif
                                </tr>
                                <?php $total_data['inv_pushed_total']+=$category_type_list[$i]['inv_pushed_total']; ?>
                                <?php $total_data['inv_pushed']+=$category_type_list[$i]['inv_pushed']; ?>
                                <?php $total_data['inv_sold']+=$category_type_list[$i]['inv_sold']; ?>
                                <?php $total_data['inv_balance']+=$category_type_list[$i]['inv_balance']; ?>
                                <?php $total_data['inv_sold_price']+=$category_type_list[$i]['inv_sold_price']; ?>
                                <?php $total_data['inv_sold_total']+=$category_type_list[$i]['inv_sold_total']; ?>
                                <?php $total_data['inv_returned_total']+=($category_type_list[$i]['inv_returned']+$category_type_list[$i]['inv_returned_complete']); ?>
                                <?php $total_data['inv_transferred_total']+=$category_type_list[$i]['inv_transfer_from_store']; ?>
                                <?php $total_data['bal_inv_mrp']+=$category_type_list[$i]['bal_inv_mrp']; ?>
                            @endfor
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>{{$total_data['inv_pushed_total']}}</th>
                                <th>{{$total_data['inv_sold_total']}}</th>
                                <th>{{$total_data['inv_returned_total']}}</th>
                                <th>{{$total_data['inv_transferred_total']}}</th>
                                <th>{{$total_data['inv_balance']}} <?php /* ?>- {{$total_data['inv_pushed_total']-($total_data['inv_sold_total']+$total_data['inv_returned_total']+$total_data['inv_transferred_total'])}}<?php */ ?> </th>
                                <th>{{$total_data['bal_inv_mrp']}}</th>
                                <th>{{$total_data['inv_pushed']}}</th>
                                <th>{{$total_data['inv_sold']}}</th>
                                <th></th>
                                <th>{{CommonHelper::currencyFormat($total_data['inv_sold_price'])}}</th>
                                @if($user->user_type != 9)
                                    <th></th>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                    <hr/>
                </div>

                @if($user->user_type != 9)

                    <h6>Store Category Report</h6>

                    <form method="get">
                        <div class="row justify-content-end" >
                            <div class="col-md-2"><a href="{{url('category/report/sales?action=download_csv_cat_store&'.$query_str)}}" class="btn btn-dialog" >Download </a></div>
                        </div>
                        <div class="separator-10"></div>
                    </form>
                    <div class="table-responsive table-filter">    
                        <table class="table table-striped admin-table report-sort-2" cellspacing="0" style="font-size:12px;">
                            <thead>
                                <tr class="header-tr">
                                    <th width="25%">Store</th>
                                    <th>Category</th>
                                    <th>Sold Units</th>
                                    <th>Balance Units</th>
                                    <th>Total NET Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_sold_units = $total_balance_units = $total_price = $store_sold_units_total = $store_balance_units_total = $store_total_price = 0; ?>
                                <?php $keys = array_keys($category_listing); ?>
                                @foreach($category_listing as $key=>$category_data)
                                    <tr>
                                        <td>{{$category_data['store_name']}} ({{$category_data['store_id_code']}})</td>
                                        <td>{{$category_data['category_name']}}</td>
                                        <td>{{$sale_qty = isset($category_data['sale_qty'])?$category_data['sale_qty']:0}}</td>
                                        <td>{{$bal_qty = isset($category_data['bal_qty'])?$category_data['bal_qty']:0}}</td>
                                        <td>{{$sale_price = isset($category_data['sale_price'])?round($category_data['sale_price'],2):0}}</td>
                                    </tr>

                                    <?php $total_sold_units+=$sale_qty; ?>
                                    <?php $total_balance_units+=$bal_qty; ?>
                                    <?php $total_price+=$sale_price; ?>
                                    <?php $store_sold_units_total+=$sale_qty; ?>
                                    <?php $store_balance_units_total+=$bal_qty; ?>
                                    <?php $store_total_price+=$sale_price; ?>
                                    <?php $next_key = next($keys); ?>
                                    @if((isset($category_listing[$key]['store_name']) && isset($category_listing[$next_key]['store_name']) && $category_listing[$key]['store_name'] != $category_listing[$next_key]['store_name']) || (!isset($category_listing[$next_key]['store_name'])) ) 
                                        <tr>
                                            <th>Total</th>
                                            <th></th>
                                            <th>{{$store_sold_units_total}}</th>
                                            <th>{{$store_balance_units_total}}</th>
                                            <th>{{CommonHelper::currencyFormat($store_total_price)}}</th>
                                        </tr>
                                        <?php $store_sold_units_total = $store_balance_units_total = $store_total_price = 0; ?>
                                    @endif
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th></th>
                                    <th>{{$total_sold_units}}</th>
                                     <th>{{$total_balance_units}}</th>
                                    <th>{{CommonHelper::currencyFormat($total_price)}}</th>
                                </tr>
                            </tfoot>
                        </table>
                        <br/>
                    </div>
                @endif
                    
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
        //$('.report-sort-1').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [[ 2, "desc" ]] });
        //$('.report-sort-2').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [] });
    });
</script>
@endsection
