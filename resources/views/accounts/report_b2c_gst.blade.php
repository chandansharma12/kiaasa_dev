@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'B2C GST Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'B2C GST Report');$page_name = 'b2c_gst_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <div class="alert alert-danger alert-dismissible elem-hidden"  id="reportErrorMessage"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <select name="state_id" id="state_id" class="form-control" onchange="getStateStores(this.value,'');">
                            <option value="">-- All States --</option>
                            @foreach($states as $id=>$name)
                                <?php $sel = ($id == request('state_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div> 
                    <div class="col-md-2">
                        <select name="s_id" id="s_id" class="form-control">
                            <option value="">-- All Stores --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div> 
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" autocomplete="off" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" autocomplete="off" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <div class="col-md-1">
                        <?php echo CommonHelper::displayDownloadDialogButton(); ?>
                    </div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:12px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>Type</th>
                                <th>Place Of Supply</th>
                                <th>Store</th>
                                <th>Rate</th>
                                <th>Qty</th>
                                <th>Taxable Value</th>
                                <th>GST Amount</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = array('qty'=>0,'taxable_value'=>0,'gst_amount'=>0,'total_value'=>0); ?>
                            @for($i=0;$i<count($orders_list);$i++)
                                <tr>
                                    <td>OE</td>
                                    <td>{{$states[$orders_list[$i]->state_id]}}</td>
                                    <td>{{$orders_list[$i]->store_name}} ({{$orders_list[$i]->store_id_code}})</td>
                                    <td>{{$orders_list[$i]->gst_percent}}</td>
                                    <td>{{$orders_list[$i]->inv_count_sold}}</td>
                                    <td>{{round($orders_list[$i]->total_discounted_price,2)}}</td>
                                    <td>{{round($orders_list[$i]->total_gst_amount,2)}}</td>
                                    <td>{{round($orders_list[$i]->total_discounted_price+$orders_list[$i]->total_gst_amount,2)}}</td>
                                </tr>
                                <?php $total['qty']+=$orders_list[$i]->inv_count_sold;
                                $total['taxable_value']+=$orders_list[$i]->total_discounted_price;
                                $total['gst_amount']+=$orders_list[$i]->total_gst_amount;
                                $total['total_value']+=($orders_list[$i]->total_discounted_price+$orders_list[$i]->total_gst_amount); ?>
                            @endfor
                        </tbody>    
                        <tfoot>
                            <tr>
                                <th colspan="4">Page Total</th>
                                <th>{{$total['qty']}}</th>
                                <th>{{round($total['taxable_value'],2)}}</th>
                                <th>{{round($total['gst_amount'],2)}}</th>
                                <th>{{round($total['total_value'],2)}}</th>
                            </tr>
                        </tfoot>
                    </table>
                    {{ $orders_list->withQueryString()->links() }}
                        <p>Displaying {{$orders_list->count()}} of {{ $orders_list->total() }} Records.</p>
                    <br/>
                </div>
            </div>
        </div>
    </section>

    <?php echo CommonHelper::displayDownloadDialogHtml($orders_list->total(),1000,'/report/gst/b2c','Download B2C GST Report','B2C Records'); ?>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
<script src="{{asset('js/setting.js')}}"></script>
<script type="text/javascript">@if(request('state_id') != '') getStateStores({{request('state_id')}},"{{request('s_id')}}"); @endif</script>
@endsection
