@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Closing Stock Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Closing Stock Report'); ?>

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
                    
                    <div class="col-md-2">
                        <a href="javascript:;" onclick="downloadClosingStockReport();" class="btn btn-dialog" ><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i></a> &nbsp; &nbsp; 
                        <a href="javascript:;" onclick="downloadStoreClosingStockReport();" class="btn btn-dialog" ><i title="Download Individual Store Report CSV File" class="fa fa-download fas-icon" ></i></a>
                    </div>
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
@if(request('s_id') == -1)

<?php  $count = 1; ?>
<?php $total_data = array('op_stock_units'=>0,'op_stock_value'=>0,'closing_stock_units'=>0,'closing_stock_value'=>0,'pur_units'=>0,'pur_value'=>0,'sale_units'=>0,'sale_value'=>0); ?>
<tr><th colspan="15">Warehouse HO</th></tr>
@for($q=0;$q<count($sku_list_ho);$q++)
<?php $key = $sku_list_ho[$q]; ?>
@if(isset($ho_inv_opening_stock[$key]) || isset($ho_inv_purchase[$key]) || isset($ho_inv_sale[$key]) || isset($ho_inv_closing_stock[$key]))
<?php $op_stock_unit = (isset($ho_inv_opening_stock[$key]))?$ho_inv_opening_stock[$key]['inv_count']:0; ?>
<?php $purchase_unit = (isset($ho_inv_purchase[$key]))?$ho_inv_purchase[$key]['inv_in_count']:0; ?>
<?php $sale_unit = (isset($ho_inv_sale[$key]))?$ho_inv_sale[$key]['inv_out_count']:0 ?>
<?php $closing_stock_unit = (isset($ho_inv_closing_stock[$key]))?$ho_inv_closing_stock[$key]['inv_count']:0 ?>
@if($op_stock_unit > 0 || $purchase_unit > 0 || $sale_unit > 0 || $closing_stock_unit > 0 )
<tr>
<td>{{$count++}}</td>
<td>{{$sku_category[$sku_list_ho[$q]]}} &nbsp;&nbsp;&nbsp;{{$sku_list_ho[$q]}}</td>
<td>{{$op_stock_unit}}</td>
<td>{{(isset($ho_inv_opening_stock[$key]))?$ho_inv_opening_stock[$key]['base_price']:0}}</td>
<td>{{(isset($ho_inv_opening_stock[$key]))?round($ho_inv_opening_stock[$key]['inv_count']*$ho_inv_opening_stock[$key]['base_price'],2):0}}</td>
<td>{{$purchase_unit}}</td>
<td>{{(isset($ho_inv_purchase[$key]))?$ho_inv_purchase[$key]['base_price']:0}}</td>
<td>{{(isset($ho_inv_purchase[$key]))?round($ho_inv_purchase[$key]['inv_in_count']*$ho_inv_purchase[$key]['base_price'],2):0}}</td>
<td>{{$sale_unit}}</td>
<td>{{(isset($ho_inv_sale[$key]))?$ho_inv_sale[$key]['store_base_price']:0}}</td>
<td>{{(isset($ho_inv_sale[$key]))?round($ho_inv_sale[$key]['inv_out_count']*$ho_inv_sale[$key]['store_base_price'],2):0}}</td>
<td>{{$closing_stock_unit}}</td>
<td>{{(isset($ho_inv_closing_stock[$key]))?$ho_inv_closing_stock[$key]['base_price']:0}}</td>
<td>{{(isset($ho_inv_closing_stock[$key]))?round($ho_inv_closing_stock[$key]['inv_count']*$ho_inv_closing_stock[$key]['base_price'],2):0}}</td>
</tr>
<?php $total_data['op_stock_units']+=$op_stock_unit; ?>
<?php $total_data['op_stock_value']+=(isset($ho_inv_opening_stock[$key]))?($op_stock_unit*$ho_inv_opening_stock[$key]['base_price']):0; ?>
<?php $total_data['pur_units']+=$purchase_unit; ?>
<?php $total_data['pur_value']+=(isset($ho_inv_purchase[$key]))?($purchase_unit*$ho_inv_purchase[$key]['base_price']):0; ?>
<?php $total_data['sale_units']+=$sale_unit; ?>
<?php $total_data['sale_value']+=(isset($ho_inv_sale[$key]))?($sale_unit*$ho_inv_sale[$key]['store_base_price']):0; ?>
<?php $total_data['closing_stock_units']+=$closing_stock_unit; ?>
<?php $total_data['closing_stock_value']+=(isset($ho_inv_closing_stock[$key]))?($closing_stock_unit*$ho_inv_closing_stock[$key]['base_price']):0; ?>
@endif    
@endif
@endfor	

<tr>
<th colspan="2">Warehouse HO Total</th>
<th>{{$total_data['op_stock_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['op_stock_value'])}}</th>
<th>{{$total_data['pur_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['pur_value'])}}</th>
<th>{{$total_data['sale_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['sale_value'])}}</th>
<th>{{$total_data['closing_stock_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['closing_stock_value'])}}</th>
</tr>

@endif

<?php $total_data = array('op_stock_units'=>0,'op_stock_value'=>0,'closing_stock_units'=>0,'closing_stock_value'=>0,'pur_units'=>0,'pur_value'=>0,'sale_units'=>0,'sale_value'=>0); ?>
<?php $store_data = $total_data; $count = 1; ?>
@for($i=0;$i<count($store_list);$i++)
<tr><th colspan="15">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</th></tr>

@for($q=0;$q<count($sku_list);$q++)
<?php $key = $store_list[$i]['id'].'_'.$sku_list[$q]; ?>
@if(isset($inv_opening_stock[$key]) || isset($inv_purchase[$key]) || isset($inv_sale[$key]) || isset($inv_closing_stock[$key]))
<tr>
<td>{{$count++}}</td>
<td>{{$sku_category[$sku_list[$q]]}} &nbsp;&nbsp;&nbsp;{{$sku_list[$q]}}</td>
<td>{{$op_stock_unit = (isset($inv_opening_stock[$key]))?$inv_opening_stock[$key]['inv_count']:0}}</td>
<td>{{(isset($inv_opening_stock[$key]))?$inv_opening_stock[$key]['store_base_price']:0}}</td>
<td>{{(isset($inv_opening_stock[$key]))?round($inv_opening_stock[$key]['inv_count']*$inv_opening_stock[$key]['store_base_price'],2):0}}</td>
<td>{{$purchase_unit = (isset($inv_purchase[$key]))?$inv_purchase[$key]['inv_in_count']:0}}</td>
<td>{{(isset($inv_purchase[$key]))?$inv_purchase[$key]['store_base_price']:0}}</td>
<td>{{(isset($inv_purchase[$key]))?round($inv_purchase[$key]['inv_in_count']*$inv_purchase[$key]['store_base_price'],2):0}}</td>
<td>{{$sale_unit = (isset($inv_sale[$key]))?$inv_sale[$key]['inv_out_count']:0}}</td>
<td>{{(isset($inv_sale[$key]))?round($inv_sale[$key]['net_price'],2):0}}</td>
<td>{{(isset($inv_sale[$key]))?round($inv_sale[$key]['inv_out_count']*$inv_sale[$key]['net_price'],2):0}}</td>
<td>{{$closing_stock_unit = (isset($inv_closing_stock[$key]))?$inv_closing_stock[$key]['inv_count']:0}}</td>
<td>{{(isset($inv_closing_stock[$key]))?$inv_closing_stock[$key]['store_base_price']:0}}</td>
<td>{{(isset($inv_closing_stock[$key]))?round($inv_closing_stock[$key]['inv_count']*$inv_closing_stock[$key]['store_base_price'],2):0}}</td>
</tr>
<?php $total_data['op_stock_units']+=$op_stock_unit; ?>
<?php $total_data['op_stock_value']+=(isset($inv_opening_stock[$key]))?($op_stock_unit*$inv_opening_stock[$key]['store_base_price']):0; ?>
<?php $total_data['pur_units']+=$purchase_unit; ?>
<?php $total_data['pur_value']+=(isset($inv_purchase[$key]))?($purchase_unit*$inv_purchase[$key]['store_base_price']):0; ?>
<?php $total_data['sale_units']+=$sale_unit; ?>
<?php $total_data['sale_value']+=(isset($inv_sale[$key]))?($sale_unit*$inv_sale[$key]['net_price']):0; ?>
<?php $total_data['closing_stock_units']+=$closing_stock_unit; ?>
<?php $total_data['closing_stock_value']+=(isset($inv_closing_stock[$key]))?($closing_stock_unit*$inv_closing_stock[$key]['store_base_price']):0; ?>
<?php $store_data['op_stock_units']+=$op_stock_unit; ?>
<?php $store_data['op_stock_value']+=(isset($inv_opening_stock[$key]))?($op_stock_unit*$inv_opening_stock[$key]['store_base_price']):0; ?>
<?php $store_data['pur_units']+=$purchase_unit; ?>
<?php $store_data['pur_value']+=(isset($inv_purchase[$key]))?($purchase_unit*$inv_purchase[$key]['store_base_price']):0; ?>
<?php $store_data['sale_units']+=$sale_unit; ?>
<?php $store_data['sale_value']+=(isset($inv_sale[$key]))?($sale_unit*$inv_sale[$key]['net_price']):0; ?>
<?php $store_data['closing_stock_units']+=$closing_stock_unit; ?>
<?php $store_data['closing_stock_value']+=(isset($inv_closing_stock[$key]))?($closing_stock_unit*$inv_closing_stock[$key]['store_base_price']):0; ?>
@endif
@endfor
<tr>
<th colspan="2">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}}) Total:</th>
<th>{{$store_data['op_stock_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($store_data['op_stock_value'])}}</th>
<th>{{$store_data['pur_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($store_data['pur_value'])}}</th>
<th>{{$store_data['sale_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($store_data['sale_value'])}}</th>
<th>{{$store_data['closing_stock_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($store_data['closing_stock_value'])}}</th>
</tr>
<?php $store_data = array('op_stock_units'=>0,'op_stock_value'=>0,'closing_stock_units'=>0,'closing_stock_value'=>0,'pur_units'=>0,'pur_value'=>0,'sale_units'=>0,'sale_value'=>0); ?>
@endfor 
</tbody>    
<tfoot>
<tr>
<th colspan="2">Stores Total</th>
<th>{{$total_data['op_stock_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['op_stock_value'])}}</th>
<th>{{$total_data['pur_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['pur_value'])}}</th>
<th>{{$total_data['sale_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['sale_value'])}}</th>
<th>{{$total_data['closing_stock_units']}}</th>
<th></th>
<th>{{CommonHelper::currencyFormat($total_data['closing_stock_value'])}}</th>
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

    <div class="modal fade data-modal" id="report_store_download_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Download Closing Stock Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <form class="" name="reportStoreDownloadFrm" id="reportStoreDownloadFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>Store</label>
                                <select name="store_id" id="store_id" class="form-control">
                                    <option value="" >Store</option>
                                    @for($i=0;$i<count($store_list_total);$i++)
                                        <?php $sel = ($store_list_total[$i]['id'] == request('store_id'))?'selected':''; ?>
                                        <option {{$sel}} value="{{$store_list_total[$i]['id']}}">{{$store_list_total[$i]['store_name']}} ({{$store_list_total[$i]['store_id_code']}})</option>
                                    @endfor
                                    <option value="-1" @if(request('s_id') == -1) selected  @endif >Warehouse</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_id"></div>
                            </div>
                                            
                            <div class="form-group col-md-4" >
                                <label>Date</label>
                                <div id="date_range_store_div_download"></div>
                                <div class="invalid-feedback" id="error_validation_date_range_store"></div>
                            </div>                
                        </div>    
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="report_store_download_cancel" name="report_store_download_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="report_store_download_submit" name="report_store_download_submit" class="btn btn-dialog" onclick="submitDownloadStoreClosingStockReport();">Submit</button>
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
<script src="{{ asset('js/users.js?v=2.1') }}" ></script>
@endsection
