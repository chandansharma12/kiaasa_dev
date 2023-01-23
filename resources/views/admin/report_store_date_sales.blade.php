@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <meta http-equiv="refresh" content="60">
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Daily Sales Report')); ?>
    <?php $store_name = (isset($store_data) && !empty($store_data))?$store_data->store_name.' ('.$store_data->store_id_code.')':'All Stores'; ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Daily Sales Report: '.$store_name); $page_name = 'daily_sales_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    
                    @if($user->user_type != 9)
                        <div class="col-md-2" >
                            <select name="s_zone_id" id="s_zone_id" class="form-control">
                                <option value="">-- All Zones --</option>
                                @for($i=0;$i<count($store_zones);$i++)
                                    <?php $sel = ($store_zones[$i]['id'] == request('s_zone_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_zones[$i]['id']}}">{{$store_zones[$i]['name']}}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2" >
                            <select name="s_id" id="s_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2" >
                            <select name="order_source" id="order_source" class="form-control">
                                <option value="">-- Order Source --</option>
                                <option @if(request('order_source') == 'pos_orders') selected @endif value="pos_orders">POS Orders</option>
                                <option @if(request('order_source') == 'website') selected @endif value="website">Website</option>
                            </select>
                        </div>
                    @endif
                    
                    @if($user->user_type == 9 && count($user_stores_list) > 1)
                        <div class="col-md-2" >
                            <select name="s_id" id="s_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @for($i=0;$i<count($user_stores_list);$i++)
                                    <?php $sel = ($user_stores_list[$i]->id == request('s_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$user_stores_list[$i]->id}}">{{$user_stores_list[$i]->store_name}}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif" autocomplete="off">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php /* ?>@if(count($sales_data)) <?php */ ?>
                        <?php $query_str = CommonHelper::getQueryString();?>
                        <div class="col-md-3">
                            <a href="{{url('store/report/sales/dates?action=download&'.$query_str)}}" class="btn btn-dialog" title="Download Report PDF File"><i title="Download Report PDF File" class="fa fa-download fas-icon" ></i> </a> &nbsp; &nbsp;&nbsp;
                            <a href="{{url('store/report/sales/dates?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a>
                        </div>
                    <?php /* ?>@endif<?php */ ?>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size: 13px;">
                        <thead><tr class="header-tr">
                            <th><?php echo CommonHelper::getSortLink('Date','od','store/report/sales/dates',true,'DESC'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Total Orders','oc','store/report/sales/dates'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Sold Units','un','store/report/sales/dates'); ?></th>
                            <th>Return</th>
                            <th>Total Units</th>
                            <th>Arnon</th>
                            @if($user->user_type == 1)
                                <th><?php echo CommonHelper::getSortLink('Total Base Price','tbp','store/report/sales/dates'); ?></th>
                            @endif
                            <th><?php echo CommonHelper::getSortLink('Total Sale Price','tsp','store/report/sales/dates'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Total Discount','tda','store/report/sales/dates'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Total GST','tgst','store/report/sales/dates'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Total NET Price','tnp','store/report/sales/dates'); ?></th>
                            <th>Cash</th>
                            <th>Card</th>
                            <th>E-Wallet</th>
                        </tr></thead>
                        <tbody>
                            <?php $total_order = $total_base_price = $total_sale_price = $total_discount_amount = $total_gst_amount = $total_net_price = 0; ?>
                            <?php $total_cash = $total_card = $total_ewallet = $units_total = $units_return = $units_arnon = 0; ?>
                            @for($i=0;$i<count($sales_data);$i++)
                                <?php $date = date('Y-n-d',strtotime($sales_data[$i]->sales_date)); ?>
                                <tr>
                                    <td>{{date('d M Y',strtotime($sales_data[$i]->sales_date))}}</td>
                                    <td>{{$sales_data[$i]->orders_count}}</td>
                                    <td>{{$sales_data[$i]->items_count_1}}</td>
                                    <td>{{$sales_data[$i]->items_count_2}}</td>
                                    <td>{{$sales_data[$i]->items_count_1-$sales_data[$i]->items_count_2}}</td>
                                    <td>{{$sales_data[$i]->arnon_inv}}</td>
                                    @if($user->user_type == 1)
                                        <td>{{$sales_data[$i]->total_base_price}}</td>
                                    @endif
                                    <td>{{$sales_data[$i]->total_sale_price}}</td>
                                    <td>{{round($sales_data[$i]->total_discount_amout,2)}}</td>
                                    <td>{{round($sales_data[$i]->total_gst_amout,2)}}</td>
                                    <td>{{round($sales_data[$i]->total_net_price,2)}}</td>
                                    <td>@if(isset($payment_data[$date]['cash'])) {{$payment_data[$date]['cash']}} @else 0.00 @endif</td>
                                    <td>@if(isset($payment_data[$date]['card'])) {{$payment_data[$date]['card']}} @else 0.00 @endif</td>
                                    <td>@if(isset($payment_data[$date]['e-wallet'])) {{$payment_data[$date]['e-wallet']}} @else 0.00 @endif</td>
                                </tr>
                                <?php $total_order+=$sales_data[$i]->orders_count; $total_base_price+=$sales_data[$i]->total_base_price;  ?>
                                <?php $total_sale_price+=$sales_data[$i]->total_sale_price; $total_discount_amount+=$sales_data[$i]->total_discount_amout;  ?>
                                <?php $total_net_price+=$sales_data[$i]->total_net_price; ?>
                                <?php $total_gst_amount+=$sales_data[$i]->total_gst_amout; ?>
                                <?php $units_total+=$sales_data[$i]->items_count_1; ?>
                                <?php $units_return+=$sales_data[$i]->items_count_2; ?>
                                <?php $units_arnon+=$sales_data[$i]->arnon_inv; ?>
                                <?php $total_cash+=(isset($payment_data[$date]['cash']))?$payment_data[$date]['cash']:0; ?>
                                <?php $total_card+=(isset($payment_data[$date]['card']))?$payment_data[$date]['card']:0; ?>
                                <?php $total_ewallet+=(isset($payment_data[$date]['e-wallet']))?$payment_data[$date]['e-wallet']:0; ?>
                            @endfor
                            <tr><th>Total</th>
                                <th>{{$total_order}}</th>
                                <th>{{$units_total}}</th>
                                <th>{{$units_return}}</th>
                                <th>{{$units_total-$units_return}}</th>
                                <th>{{$units_arnon}}</th>
                                @if($user->user_type == 1)
                                    <th> {{CommonHelper::currencyFormat($total_base_price)}}</th>
                                @endif
                                <th> {{CommonHelper::currencyFormat($total_sale_price)}}</th>
                                <th>{{CommonHelper::currencyFormat($total_discount_amount)}}</th>
                                <th> {{CommonHelper::currencyFormat($total_gst_amount)}}</th>
                                <th>{{CommonHelper::currencyFormat($total_net_price)}}</th>
                                <th>{{CommonHelper::currencyFormat($total_cash)}}</th> 
                                <th> {{CommonHelper::currencyFormat($total_card)}}</th>
                                <th>{{CommonHelper::currencyFormat($total_ewallet)}}</th>
                            </tr>
                        </tbody>
                    </table>
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
