@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
<?php $count = 0; ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Closing Stock Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Closing Stock Report'); ?>
    <?php $total_data = ['inv_bal_start'=>0,'inv_val_start'=>0,'inv_bal_end'=>0,'inv_val_end'=>0,'pur_units'=>0,'pur_value'=>0,'sale_units'=>0,'sale_value'=>0]; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    @if($user->user_type != 9)
                        <div class="col-md-2" >
                            <select name="s_id" id="s_id" class="form-control">
                                <!--<option value="">-- All Stores --</option>-->
                                <option value="-1" @if(request('s_id') == -1) selected  @endif >Warehouse</option>
                                @for($i=0;$i<count($store_list_total);$i++)
                                    <?php $sel = ($store_list_total[$i]['id'] == request('s_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_list_total[$i]['id']}}">{{$store_list_total[$i]['store_name']}} ({{$store_list_total[$i]['store_id_code']}})</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2" id="date_range_div">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <!--<div class="col-md-1"><a href="{{url('report/closing-stock/detail?action=download_csv&'.$query_str)}}" class="btn btn-dialog" >Download </a></div>-->
                    <div class="col-md-2"><a href="javascript:;" onclick="downloadClosingStockReport();" class="btn btn-dialog" ><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i></a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
<table class="table table-striped admin-table static-header-tbl" cellspacing="0"  id="storeReportTbl" style="font-size:12px;">
<thead>
<tr class="header-tr">
<th>SNo</th>
<th>Particulars</th>
<th colspan="3">Opening Stock</th>
<th colspan="3">Purchase</th>
<th colspan="3">Sale</th>
<th colspan="3">Closing Stock</th>
</tr>
<tr style="background-color: #fff;">
<th colspan="2"></th>
<th>Unit</th>
<th>Rate</th>
<th>Value</th>
<th>Unit</th>
<th>Rate</th>
<th>Value</th>
<th>Unit</th>
<th>Rate</th>
<th>Value</th>
<th>Unit</th>
<th>Rate</th>
<th>Value</th>
</tr>
</thead>
<tbody>
    
@foreach($inv_total as $sku_id=>$record)
    
<tr>
<td>{{++$count}}</td>
<td>{{$record->product_name}} &nbsp;&nbsp;&nbsp;{{$record->product_sku}}</td>

<td>{{$inv_bal_start = isset($inv_start[$sku_id])?$inv_start[$sku_id]:0}}</td>
<td>{{isset($inv_start[$sku_id])?$record->base_price:0}}</td>
<td>{{$inv_val_start = isset($inv_start[$sku_id])?round($inv_start[$sku_id]*$record->base_price,2):0}}</td>

<td>{{$purchase_units = isset($store_purchase[$sku_id])?$store_purchase[$sku_id]->inv_in_count:0}}</td>
<td>{{($purchase_units > 0)?round($store_purchase[$sku_id]->store_base_price,2):0}}</td>
<td>{{$purchase_units_val = isset($store_purchase[$sku_id])?round($store_purchase[$sku_id]->store_base_price*$store_purchase[$sku_id]->inv_in_count,2):0}}</td>

<td>{{$sale_units = isset($store_sale[$sku_id])?$store_sale[$sku_id]->inv_sale_count:0}}</td>
<td>{{($sale_units > 0)?round($store_sale[$sku_id]->net_price,2):0}}</td>
<td>{{$sale_units_val = isset($store_sale[$sku_id])?round($store_sale[$sku_id]->net_price*$store_sale[$sku_id]->inv_sale_count,2):0}}</td>

<td>{{$inv_bal_end = isset($inv_end[$sku_id])?$inv_end[$sku_id]:0}}</td>
<td>{{isset($inv_end[$sku_id])?$record->base_price:0}}</td>
<td>{{$inv_val_end = isset($inv_end[$sku_id])?round($inv_end[$sku_id]*$record->base_price,2):0}}</td>
</tr>

<?php $total_data['inv_bal_start']+=$inv_bal_start; ?>
<?php $total_data['inv_val_start']+=$inv_val_start; ?>
<?php $total_data['inv_bal_end']+=$inv_bal_end; ?>
<?php $total_data['inv_val_end']+=$inv_val_end; ?>
<?php $total_data['sale_units']+=$sale_units; ?>
<?php $total_data['sale_value']+=$sale_units_val; ?>
<?php $total_data['pur_units']+=$purchase_units; ?>
<?php $total_data['pur_value']+=$purchase_units_val; ?>
@endforeach

</tbody>    

<tfoot>
<tr>
<th colspan="2">{{$store_data->store_name}} ({{$store_data->store_code}}) Total</th>
<th>{{$total_data['inv_bal_start']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['inv_val_start'])}}</th>
<th>{{$total_data['pur_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['pur_value'])}}</th>
<th>{{$total_data['sale_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['sale_value'])}}</th>
<th>{{$total_data['inv_bal_end']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['inv_val_end'])}}</th>
</tr>
</tfoot>


</table>
                        <br/>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="report_download_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Download Closing Stock Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="reportDownloadSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="reportDownloadErrorMessage"></div>

                <form class="" name="reportDownloadFrm" id="reportDownloadFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>Store</label>
                                <select name="store_id" id="store_id" class="form-control">
                                <?php $store_list_total_count = count($store_list_total); ?>    
                                @for($i=0;$i<=$store_list_total_count;$i=$i+10) 
                                    <?php $start = $i+1; $end = $i+10; ?>
                                    <?php $end = ($end < $store_list_total_count)?$end:$store_list_total_count; ?>
                                    <option value="{{$start}}_{{$end}}">{{$start}} - {{$end}} Stores</option>
                                @endfor
                                <option value="-1">Warehouse</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_list"></div>
                            </div>
                                            
                            <div class="form-group col-md-4" >
                                <label>Date</label>
                                <div id="date_range_div_download"></div>
                                <div class="invalid-feedback" id="error_validation_date_range"></div>
                            </div>                
                        </div>    
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="report_download_cancel" name="report_download_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="report_download_submit" name="report_download_submit" class="btn btn-dialog" onclick="submitDownloadClosingStockReport();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
<script src="{{ asset('js/users.js?v=1.2') }}" ></script>
@endsection
