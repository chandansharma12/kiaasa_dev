@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Subcategory Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Subcategory Sales Report'); ?>

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
                    <div class="col-md-2"><a href="{{url('subcategory/report/sales/'.$category_data->id.'?action=download_csv_subcat&'.$query_str)}}" class="btn btn-dialog" >Download </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <h6>Category: {{$category_data->name}}</h6>
                        <table class="table table-striped admin-table report-sort-1" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th>Subcategory</th>
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
                                @for($i=0;$i<count($subcategory_type_list);$i++)
                                    <tr>
                                        <td>{{$subcategory_type_list[$i]['name']}}</td>
                                        <td>{{$subcategory_type_list[$i]['inv_pushed_total']}}</td>
                                        <td>{{$subcategory_type_list[$i]['inv_pushed']}}</td>
                                        <td>{{$subcategory_type_list[$i]['inv_sold']}}</td>
                                        <td>{{$subcategory_type_list[$i]['inv_balance']}}</td>
                                        <td>@if($subcategory_type_list[$i]['inv_balance']+$subcategory_type_list[$i]['inv_sold'] > 0) {{round(($subcategory_type_list[$i]['inv_sold']/($subcategory_type_list[$i]['inv_balance']+$subcategory_type_list[$i]['inv_sold']))*100,3)}} % @endif</td>
                                        <td>{{CommonHelper::currencyFormat($subcategory_type_list[$i]['inv_sold_price'])}}</td>
                                    </tr>
                                    <?php $total_data['inv_pushed_total']+=$subcategory_type_list[$i]['inv_pushed_total']; ?>
                                    <?php $total_data['inv_pushed']+=$subcategory_type_list[$i]['inv_pushed']; ?>
                                    <?php $total_data['inv_sold']+=$subcategory_type_list[$i]['inv_sold']; ?>
                                    <?php $total_data['inv_balance']+=$subcategory_type_list[$i]['inv_balance']; ?>
                                    <?php $total_data['inv_sold_price']+=$subcategory_type_list[$i]['inv_sold_price']; ?>
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

                    <h6>Store Subcategory Report | Category: {{$category_data->name}}</h6>

                    <form method="get">
                        <div class="row justify-content-end" >
                            <div class="col-md-2"><a href="{{url('subcategory/report/sales/'.$category_data->id.'?action=download_csv_subcat_store&'.$query_str)}}" class="btn btn-dialog" >Download </a></div>
                        </div>
                        <div class="separator-10"></div>
                    </form>
                        
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort-2" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th style="width:25%;">Store</th>
                                    <th style="width:25%;">Subcategory</th>
                                    <th>Sold Units</th>
                                    <th>Total NET Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_units = $total_price = $store_units_total = $store_total_price = 0; ?>
                                @for($i=0;$i<count($subcategory_list);$i++)
                                    <tr>
                                        <td>{{$subcategory_list[$i]->store_name}} ({{$subcategory_list[$i]->store_id_code}})</td>
                                        <td>{{$subcategory_list[$i]->subcategory_name}}</td>
                                        <td>{{$subcategory_list[$i]->subcat_count}}</td>
                                        <td>{{CommonHelper::currencyFormat($subcategory_list[$i]->subcat_net_price)}}</td>
                                    </tr>
                                    
                                    <?php $total_units+=$subcategory_list[$i]->subcat_count; ?>
                                    <?php $total_price+=$subcategory_list[$i]->subcat_net_price; ?>
                                    <?php $store_units_total+=$subcategory_list[$i]->subcat_count; ?>
                                    <?php $store_total_price+=$subcategory_list[$i]->subcat_net_price; ?>
                                    
                                    @if((isset($subcategory_list[$i+1]->store_name) && $subcategory_list[$i]->store_name != $subcategory_list[$i+1]->store_name) || (!isset($subcategory_list[$i+1]->store_name)) )
                                        <tr>
                                            <th>Total</th>
                                            <th></th>
                                            <th>{{$store_units_total}}</th>
                                            <th>{{CommonHelper::currencyFormat($store_total_price)}}</th>
                                        </tr>
                                        <?php $store_units_total = $store_total_price = 0; ?>
                                    @endif
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th></th>
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
        $('.report-sort-1').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [[ 2, "desc" ]] });
        $('.report-sort-2').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [] });
    });
</script>
@endsection
