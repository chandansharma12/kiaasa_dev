@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Discount Types Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Discount Types Sales Report'); $page_name = 'discount_types_sales_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" autocomplete="off" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" autocomplete="off" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-1"><a href="{{url('store/report/discount/types?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort static-header-tbl" cellspacing="0" style="font-size: 13px;">
                            <thead>
                                <tr class="header-tr">
                                    <th>Store Name</th>
                                    <th>Code</th>
                                    <th>Discount %</th>
                                    <th>Inventory Count</th>
                                    <th>Sale Price</th>
                                    <th>Discount Amount</th>
                                    <th>GST Amount</th>
                                    <th>Net Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total = array('units'=>0,'sale_price'=>0,'discount_amount'=>0,'gst_amount'=>0,'net_price'=>0); ?>
                                <?php $total_store = array('units'=>0,'sale_price'=>0,'discount_amount'=>0,'gst_amount'=>0,'net_price'=>0); ?>
                                @for($i=0;$i<count($products_list_all);$i++)
                                    <tr>
                                        <td>All Stores</td>
                                        <td></td>
                                        <td>{{$products_list_all[$i]['discount_percent']}} %</td>
                                        <td>{{$products_list_all[$i]['prod_count']}}</td>
                                        <td>{{CommonHelper::currencyFormat($products_list_all[$i]['prod_sale_price'])}}</td>
                                        <td>{{CommonHelper::currencyFormat($products_list_all[$i]['prod_discount_amount'])}}</td>
                                        <td>{{CommonHelper::currencyFormat($products_list_all[$i]['prod_gst_amount'])}}</td>
                                        <td>{{CommonHelper::currencyFormat($products_list_all[$i]['prod_net_price'])}}</td>
                                    </tr>
                                    <?php $total['units']+=$products_list_all[$i]['prod_count']; ?>
                                    <?php $total['sale_price']+=$products_list_all[$i]['prod_sale_price']; ?>
                                    <?php $total['discount_amount']+=$products_list_all[$i]['prod_discount_amount']; ?>
                                    <?php $total['gst_amount']+=$products_list_all[$i]['prod_gst_amount']; ?>
                                    <?php $total['net_price']+=$products_list_all[$i]['prod_net_price']; ?>
                                @endfor
                                
                                <tr>
                                    <th colspan="3">All Stores Total</th>
                                    <th>{{$total['units']}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['sale_price'])}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['discount_amount'])}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['gst_amount'])}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['net_price'])}}</th>
                                </tr>
                                
                                <?php $total = array('units'=>0,'sale_price'=>0,'discount_amount'=>0,'gst_amount'=>0,'net_price'=>0); ?>
                                @for($i=0;$i<count($products_list);$i++)
                                    <tr>
                                        <td>{{$products_list[$i]['store_name']}}</td>
                                        <td>{{$products_list[$i]['store_id_code']}}</td>
                                        <td>{{$products_list[$i]['discount_percent']}} %</td>
                                        <td>{{$products_list[$i]['prod_count']}}</td>
                                        <td>{{CommonHelper::currencyFormat($products_list[$i]['prod_sale_price'])}}</td>
                                        <td>{{CommonHelper::currencyFormat($products_list[$i]['prod_discount_amount'])}}</td>
                                        <td>{{CommonHelper::currencyFormat($products_list[$i]['prod_gst_amount'])}}</td>
                                        <td>{{CommonHelper::currencyFormat($products_list[$i]['prod_net_price'])}}</td>
                                    </tr>
                                    <?php $total['units']+=$products_list[$i]['prod_count']; ?>
                                    <?php $total['sale_price']+=$products_list[$i]['prod_sale_price']; ?>
                                    <?php $total['discount_amount']+=$products_list[$i]['prod_discount_amount']; ?>
                                    <?php $total['gst_amount']+=$products_list[$i]['prod_gst_amount']; ?>
                                    <?php $total['net_price']+=$products_list[$i]['prod_net_price']; ?>
                                    
                                    <?php $total_store['units']+=$products_list[$i]['prod_count']; ?>
                                    <?php $total_store['sale_price']+=$products_list[$i]['prod_sale_price']; ?>
                                    <?php $total_store['discount_amount']+=$products_list[$i]['prod_discount_amount']; ?>
                                    <?php $total_store['gst_amount']+=$products_list[$i]['prod_gst_amount']; ?>
                                    <?php $total_store['net_price']+=$products_list[$i]['prod_net_price']; ?>
                                    
                                    @if((isset($products_list[$i+1]['store_id']) && $products_list[$i]['store_id'] != $products_list[$i+1]['store_id']) || ($i+1 == count($products_list)) )
                                        <tr>
                                            <th colspan="3">{{$products_list[$i]['store_name']}} Total</th>
                                            <th>{{$total_store['units']}}</th>
                                            <th>{{CommonHelper::currencyFormat($total_store['sale_price'])}}</th>
                                            <th>{{CommonHelper::currencyFormat($total_store['discount_amount'])}}</th>
                                            <th>{{CommonHelper::currencyFormat($total_store['gst_amount'])}}</th>
                                            <th>{{CommonHelper::currencyFormat($total_store['net_price'])}}</th>
                                        </tr>
                                        <?php $total_store = array('units'=>0,'sale_price'=>0,'discount_amount'=>0,'gst_amount'=>0,'net_price'=>0); ?>
                                    @endif
                                    
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total</th>
                                    <th>{{$total['units']}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['sale_price'])}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['discount_amount'])}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['gst_amount'])}}</th>
                                    <th>{{CommonHelper::currencyFormat($total['net_price'])}}</th>
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
<script src="{{ asset('js/store.js') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
