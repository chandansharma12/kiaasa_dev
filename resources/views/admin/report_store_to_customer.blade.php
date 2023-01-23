@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
@if(empty($error_message))
  
<?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store to Customer Sales Report')); ?>
<?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store to Customer Sales Report'.' ('.str_replace('_',' ',$report_type).')'); ?>

<?php $currency = CommonHelper::getCurrency(); ?>
<section class="product_area">
<div class="container-fluid" >

<div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
<div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
<form method="get">
<div class="row justify-content-end" >
<div class="col-md-2" id="state_list_div">
<select name="state_id" id="state_id" class="form-control" onchange="getStateStores(this.value,'');">
<option value="">State</option>
@for($i=0;$i<count($state_list);$i++)
<?php $sel = ($state_list[$i]->id == request('state_id'))?'selected':''; ?>
<option {{$sel}} value="{{$state_list[$i]->id}}">{{$state_list[$i]->state_name}}</option>
@endfor
</select>
</div>    
<div class="col-md-3" id="store_list_div">
<select name="store_id" id="store_id" class="form-control">
<option value="">All Stores</option>
@for($i=0;$i<count($store_list);$i++)
<?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>
<option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
@endfor
</select>
</div>
<div class="col-md-2" id="store_type_div">
<select name="store_type" id="store_type" class="form-control">
<option value="">Store Type</option>
<option  <?php echo $sel = (1 == request('store_type'))?'selected':''; ?> value="1">Kiaasa</option>
<option  <?php echo $sel = (2 == request('store_type'))?'selected':''; ?> value="2">Franchise</option>
</select>
</div> 
   
<div class="col-md-2" id="date_range_div">
<div class="input-group input-daterange">
<input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime('-7 days'))}}@endif">
<div class="input-group-addon" style="margin-top:10px;">to</div>
<input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
</div>
</div>
<input type="hidden" name="report_type" id="report_type" value="{{$report_type}}">
<div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>

<div class="col-md-1"><a href="javascript:;" onclick="downloadStoreToCustomerReport();" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>

</div>
</form>
<div class="separator-10">&nbsp;</div>
<div id="orderContainer" class="table-container">

<div id="ordersList">
<div style="width:8050px">&nbsp;</div>
<div class="table-responsive table-filter" style="width:8000px;">

<table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size: 12px;">
<thead><tr class="header-tr">
<th>Store Name</th>  
<th>Code</th>  
<th>Store GST No</th>  
@if($report_type == 'bill')
<th>Bill No</th>
<th>Bill Date</th>
<th>Cust Name</th>
<th>Cust GST No</th>
<th>Description</th>
@endif
@if($report_type == 'date') 
<th>Bill Date</th>
<th>Bill No</th>
@endif
@if($report_type == 'month') 
<th>Bill Date</th>
<th>Bill No</th>
@endif
@if($report_type == 'hsn_code') 
<th>HSN Code</th>
@endif
<th>Narration</th>
<th>Bill Qty</th>
@if($report_type != 'hsn_code')
<th>Cash Ledger</th>
<th>Cash</th>
<th>Card Ledger</th>
<th>Card</th>
<th>E-Wallet Ledger</th>
<th>E-Wallet</th>
<th>Voucher</th>
<th>Round off</th>
<th>Round off</th>
@endif
<th>Gross Value</th>
<th>Discount</th>
<th>Net Amt</th>
<th>Sales 3%</th>
@if($report_type == 'hsn_code') 
<th>Qty 3%</th>
@endif
<th>GST 3% Taxable Value</th>
<th>CGST Ledger 1.5%</th>
<th>CGST 1.5%</th>
<th>SGST Ledger 1.5%</th>
<th>SGST 1.5%</th>
<th>Total GST 3%</th>
<th>Sales 5%</th>
@if($report_type == 'hsn_code') 
<th>Qty 5%</th>
@endif
<th>GST 5% Taxable Value</th>
<th>CGST Ledger 2.5%</th>
<th>CGST 2.5%</th>
<th>SGST Ledger 2.5%</th>
<th>SGST 2.5%</th>
<th>Total GST 5%</th>
<th>Sales 12%</th>
@if($report_type == 'hsn_code') 
<th>Qty 12%</th>
@endif
<th>GST 12% Taxable Value</th>
<th>CGST Ledger 6%</th>
<th>CGST 6%</th>
<th>SGST Ledger 6%</th>
<th>SGST 6%</th>
<th>Total GST 12%</th>
<th>Sales 18%</th>
@if($report_type == 'hsn_code') 
<th>Qty 18%</th>
@endif
<th>GST 18% Taxable Value</th>
<th>CGST Ledger 9%</th>
<th>CGST 9%</th>
<th>SGST Ledger 9%</th>
<th>SGST 9%</th>
<th>Total GST 18%</th>

<th>Sales 0%</th>
@if($report_type == 'hsn_code') 
<th>Qty 0%</th>
@endif
<th>GST 0% Taxable Value</th>
<th>CGST Ledger 0%</th>
<th>CGST 0%</th>
<th>SGST Ledger 0%</th>
<th>SGST 0%</th>
<th>Total GST 0%</th>
</tr></thead>
<tbody>
<?php $store_total = array('total_items'=>0,'card_total'=>0,'cash_total'=>0,'ewallet_total'=>0,'voucher_total'=>0,'roundoff_total'=>0,
'sale_price_total'=>0,'discount_amount_total'=>0,'net_price_total'=>0,'taxable_value_gst_3_total'=>0,'gst_amount_gst_3_total'=>0,'taxable_value_gst_5_total'=>0,
'gst_amount_gst_5_total'=>0,'taxable_value_gst_12_total'=>0,'gst_amount_gst_12_total'=>0,'taxable_value_gst_18_total'=>0,'gst_amount_gst_18_total'=>0,
'taxable_value_gst_0_total'=>0,'gst_amount_gst_0_total'=>0,'qty_gst_3_total'=>0,'qty_gst_5_total'=>0,'qty_gst_12_total'=>0,'qty_gst_18_total'=>0,'qty_gst_0_total'=>0); ?>
<?php $grand_total = $store_total; ?>
@for($i=0;$i<count($bill_list);$i++)
<?php $total_items =  $bill_list[$i]->product_quantity_total;// ?>
<?php $voucher_total = (isset($bill_list[$i]->voucher_total) && $bill_list[$i]->voucher_total > 0)?$bill_list[$i]->voucher_total:0; ?>
<?php $total_payment_received = $bill_list[$i]->cash_total+$bill_list[$i]->card_total+$bill_list[$i]->ewallet_total+$voucher_total; ?>
<?php $round_off = round($total_payment_received-round($bill_list[$i]->net_price_total,2),2); ?>
<tr>
<td>{{$bill_list[$i]->store_name}}</td>
<td>{{$bill_list[$i]->store_id_code}}</td>
<td>{{$bill_list[$i]->store_gst_no}}</td>
@if($report_type == 'bill')   
<td>{{$bill_list[$i]->order_no}}</td>
<td>{{date('d-m-Y',strtotime($bill_list[$i]->created_at))}}</td>
<td>{{$bill_list[$i]->customer_name}}</td>
<td>{{$bill_list[$i]->customer_gst_no}}</td>
<td>@if($total_items > 0) BILLING @else EXCHANGE @endif</td>
@endif
@if($report_type == 'date') 
<td>{{date('d-m-Y',strtotime($bill_list[$i]->created_at))}}</td>
<td>{{$bill_list[$i]->order_no}} - {{$bill_list[$i]->max_id}}</td>
@endif
@if($report_type == 'month') 
<td>{{date('M Y',strtotime($bill_list[$i]->created_at))}}</td>
<td>{{$bill_list[$i]->order_no}} - {{$bill_list[$i]->max_id}}</td>
@endif
@if($report_type == 'hsn_code') 
<td>{{$bill_list[$i]->hsn_code}}</td>
@endif
<td>Qty Pcs - @if($total_items > 0) {{$total_items}} @endif</td>
<td>@if($total_items > 0) {{$total_items}} @endif</td>
@if($report_type != 'hsn_code')
<td>@if($bill_list[$i]->cash_total != 0) Cash Sale - {{$bill_list[$i]->store_name}} @endif</td>
<td>@if($bill_list[$i]->cash_total != 0) {{$bill_list[$i]->cash_total}} @endif</td>
<td>@if($bill_list[$i]->card_total != 0) Card Sale - {{$bill_list[$i]->store_name}} @endif</td>
<td>@if($bill_list[$i]->card_total != 0)  {{$bill_list[$i]->card_total}} @endif</td>
<td>@if($bill_list[$i]->ewallet_total != 0) E-Wallet Sale - {{$bill_list[$i]->store_name}} @endif</td>
<td>@if($bill_list[$i]->ewallet_total != 0) {{$bill_list[$i]->ewallet_total}}@endif</td>
<td>{{isset($bill_list[$i]->voucher_total)?$bill_list[$i]->voucher_total:''}}</td>
<td>Round off</td>
<td>@if($report_type != 'hsn_code') {{$round_off = ($round_off != -0)?$round_off:0}} @endif</td>
@endif
<td>{{$bill_list[$i]->sale_price_total}}</td>
<td>{{round($bill_list[$i]->discount_amount_total,2)}}</td>
<td>{{round($bill_list[$i]->net_price_total,2)}}</td>
<td>{{$bill_list[$i]->store_name}} Sale 3%</td>
@if($report_type == 'hsn_code')
<td>{{$bill_list[$i]->gst_3_gst_qty_total}}</td>
@endif
<td>{{round($bill_list[$i]->discounted_price_gst_3_total,3)}}</td>
<td>CGST Output 1.50%</td>
<td>{{$gst_1_5 = round($bill_list[$i]->gst_amount_gst_3_total/2,3)}} </td>
<td>SGST Output 1.50%</td>
<td>{{$gst_1_5 = round($bill_list[$i]->gst_amount_gst_3_total/2,3)}} </td>
<td>{{$gst_1_5+$gst_1_5}}</td>
<td>{{$bill_list[$i]->store_name}} Sale 5%</td>
@if($report_type == 'hsn_code')
<td>{{$bill_list[$i]->gst_5_gst_qty_total}}</td>
@endif
<td>{{round($bill_list[$i]->discounted_price_gst_5_total,3)}}</td>
<td>CGST Output 2.50%</td>
<td>{{$gst_2_5 = round($bill_list[$i]->gst_amount_gst_5_total/2,3)}} </td>
<td>SGST Output 2.50%</td>
<td>{{$gst_2_5 = round($bill_list[$i]->gst_amount_gst_5_total/2,3)}} </td>
<td>{{$gst_2_5+$gst_2_5}}</td>
<td>{{$bill_list[$i]->store_name}} Sale 12%</td>
@if($report_type == 'hsn_code')
<td>{{$bill_list[$i]->gst_12_gst_qty_total}}</td>
@endif
<td>{{round($bill_list[$i]->discounted_price_gst_12_total,3)}} </td>
<td>CGST Output 6%</td>
<td>{{$gst_6 = round($bill_list[$i]->gst_amount_gst_12_total/2,3)}} </td>
<td>SGST Output 6%</td>
<td>{{$gst_6 = round($bill_list[$i]->gst_amount_gst_12_total/2,3)}} </td>
<td>{{$gst_6+$gst_6}}</td>
<td>{{$bill_list[$i]->store_name}} Sale 18%</td>
@if($report_type == 'hsn_code')
<td>{{$bill_list[$i]->gst_18_gst_qty_total}}</td>
@endif
<td>{{round($bill_list[$i]->discounted_price_gst_18_total,3)}} </td>
<td>CGST Output 9%</td>
<td>{{$gst_9 = round($bill_list[$i]->gst_amount_gst_18_total/2,3)}} </td>
<td>SGST Output 9%</td>
<td>{{$gst_9 = round($bill_list[$i]->gst_amount_gst_18_total/2,3)}} </td>
<td>{{$gst_9+$gst_9}}</td>
<td>{{$bill_list[$i]->store_name}} Sale 0%</td>
@if($report_type == 'hsn_code')
<td>{{$bill_list[$i]->gst_0_gst_qty_total}}</td>
@endif
<td>{{round($bill_list[$i]->discounted_price_gst_0_total,3)}} </td>
<td>CGST Output 0%</td>
<td>{{$gst_0 = round($bill_list[$i]->gst_amount_gst_0_total,3)}} </td>
<td>SGST Output 0%</td>
<td>{{$gst_0 = round($bill_list[$i]->gst_amount_gst_0_total,3)}} </td>
<td>{{$gst_0+$gst_0}}</td>
</tr>
<?php 
$store_total['total_items']+=$total_items; 
if($report_type != 'hsn_code'){
$store_total['card_total']+=$bill_list[$i]->card_total;
$store_total['cash_total']+=$bill_list[$i]->cash_total;
$store_total['ewallet_total']+=$bill_list[$i]->ewallet_total;
$store_total['voucher_total']+=$bill_list[$i]->voucher_total;
}

$store_total['roundoff_total']+=$round_off;
$store_total['sale_price_total']+=$bill_list[$i]->sale_price_total;
$store_total['discount_amount_total']+=$bill_list[$i]->discount_amount_total;
$store_total['net_price_total']+=($bill_list[$i]->net_price_total);
$store_total['taxable_value_gst_3_total']+=$bill_list[$i]->discounted_price_gst_3_total;
$store_total['gst_amount_gst_3_total']+=$bill_list[$i]->gst_amount_gst_3_total;
$store_total['taxable_value_gst_5_total']+=$bill_list[$i]->discounted_price_gst_5_total;
$store_total['gst_amount_gst_5_total']+=$bill_list[$i]->gst_amount_gst_5_total;
$store_total['taxable_value_gst_12_total']+=$bill_list[$i]->discounted_price_gst_12_total;
$store_total['gst_amount_gst_12_total']+=$bill_list[$i]->gst_amount_gst_12_total;
$store_total['taxable_value_gst_18_total']+=$bill_list[$i]->discounted_price_gst_18_total;
$store_total['gst_amount_gst_18_total']+=$bill_list[$i]->gst_amount_gst_18_total;
$store_total['taxable_value_gst_0_total']+=$bill_list[$i]->discounted_price_gst_0_total;
$store_total['gst_amount_gst_0_total']+=$bill_list[$i]->gst_amount_gst_0_total;

if($report_type == 'hsn_code'){
$store_total['qty_gst_3_total']+=$bill_list[$i]->gst_3_gst_qty_total;    
$store_total['qty_gst_5_total']+=$bill_list[$i]->gst_5_gst_qty_total;
$store_total['qty_gst_12_total']+=$bill_list[$i]->gst_12_gst_qty_total;
$store_total['qty_gst_18_total']+=$bill_list[$i]->gst_18_gst_qty_total;
$store_total['qty_gst_0_total']+=$bill_list[$i]->gst_0_gst_qty_total;
}
?>

<?php 
$grand_total['total_items']+=$total_items; 
if($report_type != 'hsn_code'){
$grand_total['card_total']+=$bill_list[$i]->card_total;
$grand_total['cash_total']+=$bill_list[$i]->cash_total;
$grand_total['ewallet_total']+=$bill_list[$i]->ewallet_total;
$grand_total['voucher_total']+=$bill_list[$i]->voucher_total;
}

$grand_total['roundoff_total']+=$round_off;
$grand_total['sale_price_total']+=$bill_list[$i]->sale_price_total;
$grand_total['discount_amount_total']+=$bill_list[$i]->discount_amount_total;
$grand_total['net_price_total']+=($bill_list[$i]->net_price_total);
$grand_total['taxable_value_gst_3_total']+=$bill_list[$i]->discounted_price_gst_3_total;
$grand_total['gst_amount_gst_3_total']+=$bill_list[$i]->gst_amount_gst_3_total;
$grand_total['taxable_value_gst_5_total']+=$bill_list[$i]->discounted_price_gst_5_total;
$grand_total['gst_amount_gst_5_total']+=$bill_list[$i]->gst_amount_gst_5_total;
$grand_total['taxable_value_gst_12_total']+=$bill_list[$i]->discounted_price_gst_12_total;
$grand_total['gst_amount_gst_12_total']+=$bill_list[$i]->gst_amount_gst_12_total;
$grand_total['taxable_value_gst_18_total']+=$bill_list[$i]->discounted_price_gst_18_total;
$grand_total['gst_amount_gst_18_total']+=$bill_list[$i]->gst_amount_gst_18_total;
$grand_total['taxable_value_gst_0_total']+=$bill_list[$i]->discounted_price_gst_0_total;
$grand_total['gst_amount_gst_0_total']+=$bill_list[$i]->gst_amount_gst_0_total;

if($report_type == 'hsn_code'){
$grand_total['qty_gst_3_total']+=$bill_list[$i]->gst_3_gst_qty_total;    
$grand_total['qty_gst_5_total']+=$bill_list[$i]->gst_5_gst_qty_total;
$grand_total['qty_gst_12_total']+=$bill_list[$i]->gst_12_gst_qty_total;
$grand_total['qty_gst_18_total']+=$bill_list[$i]->gst_18_gst_qty_total;
$grand_total['qty_gst_0_total']+=$bill_list[$i]->gst_0_gst_qty_total;
}
?>

@if((isset($bill_list[$i+1]->store_name) && $bill_list[$i]->store_name != $bill_list[$i+1]->store_name) || (!isset($bill_list[$i+1]->store_name)) )
<tr>
@if($report_type == 'bill') 
<th colspan="8">Total</th>
@elseif($report_type == 'hsn_code') 
<th colspan="4">Total</th>
@else
<th colspan="5">Total</th>
@endif
<th>Qty Pcs - {{$store_total['total_items']}}</th>
<th>{{$store_total['total_items']}}</th>
@if($report_type != 'hsn_code')
<th>Cash Sale - {{$bill_list[$i]->store_name}}</th>
<th>{{$store_total['cash_total']}}</th>
<th>Card Sale - {{$bill_list[$i]->store_name}}</th>
<th>{{$store_total['card_total']}}</th>
<th>E-Wallet Sale - {{$bill_list[$i]->store_name}}</th>
<th>{{$store_total['ewallet_total']}}</th>
<th>{{$store_total['voucher_total']}}</th>
<th>Round off</th>
<th>@if($report_type != 'hsn_code') {{$store_total['roundoff_total']}} @endif</th>
@endif
<th>{{round($store_total['sale_price_total'],2)}}</th>
<th>{{round($store_total['discount_amount_total'],2)}}</th>
<th>{{round($store_total['net_price_total'],2)}}</th>
<th>{{$bill_list[$i]->store_name}} Sale 3%</th>
@if($report_type == 'hsn_code')
<th>{{$store_total['qty_gst_3_total']}}</th>
@endif
<th>{{round($store_total['taxable_value_gst_3_total'],3)}}</th>
<td>CGST Output 1.50%</td>
<th>{{$gst_1_5 = round($store_total['gst_amount_gst_3_total']/2,3)}}</th>
<td>SGST Output 1.50%</td>
<th>{{$gst_1_5 = round($store_total['gst_amount_gst_3_total']/2,3)}}</th>
<th>{{$gst_1_5+$gst_1_5}}</th>
<th>{{$bill_list[$i]->store_name}} Sale 5%</th>
@if($report_type == 'hsn_code')
<th>{{$store_total['qty_gst_5_total']}}</th>
@endif
<th>{{round($store_total['taxable_value_gst_5_total'],3)}}</th>
<td>CGST Output 2.50%</td>
<th>{{$gst_2_5 = round($store_total['gst_amount_gst_5_total']/2,3)}}</th>
<td>SGST Output 2.50%</td>
<th>{{$gst_2_5 = round($store_total['gst_amount_gst_5_total']/2,3)}}</th>
<th>{{$gst_2_5+$gst_2_5}}</th>
<th>{{$bill_list[$i]->store_name}} Sale 12%</th>
@if($report_type == 'hsn_code')
<th>{{$store_total['qty_gst_12_total']}}</th>
@endif
<th>{{round($store_total['taxable_value_gst_12_total'],3)}}</th>
<td>CGST Output 6%</td>
<th>{{$gst_6 = round($store_total['gst_amount_gst_12_total']/2,3)}}</th>
<td>SGST Output 6%</td>
<th>{{$gst_6 = round($store_total['gst_amount_gst_12_total']/2,3)}}</th>
<th>{{$gst_6+$gst_6}}</th>
<th>{{$bill_list[$i]->store_name}} Sale 18%</th>
@if($report_type == 'hsn_code')
<th>{{$store_total['qty_gst_18_total']}}</th>
@endif
<th>{{round($store_total['taxable_value_gst_18_total'],3)}}</th>
<td>CGST Output 9%</td>
<th>{{$gst_9 = round($store_total['gst_amount_gst_18_total']/2,3)}}</th>
<td>SGST Output 9%</td>
<th>{{$gst_9 = round($store_total['gst_amount_gst_18_total']/2,3)}}</th>
<th>{{$gst_9+$gst_9}}</th>
<th>{{$bill_list[$i]->store_name}} Sale 0%</th>
@if($report_type == 'hsn_code')
<th>{{$store_total['qty_gst_0_total']}}</th>
@endif
<th>{{round($store_total['taxable_value_gst_0_total'],3)}}</th>
<td>CGST Output 0%</td>
<th>{{$gst_0 = round($store_total['gst_amount_gst_0_total'],3)}}</th>
<td>SGST Output 0%</td>
<th>{{$gst_0 = round($store_total['gst_amount_gst_0_total'],3)}}</th>
<th>{{$gst_0+$gst_0}}</th>
</tr>
<?php $store_total = array('total_items'=>0,'card_total'=>0,'cash_total'=>0,'ewallet_total'=>0,'voucher_total'=>0,'roundoff_total'=>0,
'sale_price_total'=>0,'discount_amount_total'=>0,'net_price_total'=>0,'taxable_value_gst_3_total'=>0,'gst_amount_gst_3_total'=>0,'taxable_value_gst_5_total'=>0,
'gst_amount_gst_5_total'=>0,'taxable_value_gst_12_total'=>0,'gst_amount_gst_12_total'=>0,'taxable_value_gst_18_total'=>0,'gst_amount_gst_18_total'=>0,
'taxable_value_gst_0_total'=>0,'gst_amount_gst_0_total'=>0,'qty_gst_5_total'=>0,'qty_gst_12_total'=>0,'qty_gst_18_total'=>0,'qty_gst_0_total'=>0,'qty_gst_3_total'=>0); ?>
@endif
@endfor

<tr>
@if($report_type == 'bill') 
<th colspan="8">Grand Total</th>
@elseif($report_type == 'hsn_code') 
<th colspan="4">Total</th>
@else
<th colspan="5">Grand Total</th>
@endif
<th>Qty Pcs - {{$grand_total['total_items']}}</th>
<th>{{$grand_total['total_items']}}</th>
@if($report_type != 'hsn_code')
<th>Cash Sale</th>
<th>{{round($grand_total['cash_total'],3)}}</th>
<th>Card Sale</th>
<th>{{$grand_total['card_total']}}</th>
<th>E-Wallet Sale</th>
<th>{{$grand_total['ewallet_total']}}</th>
<th>{{$grand_total['voucher_total']}}</th>
<th>Round off</th>
<th>@if($report_type != 'hsn_code') {{$grand_total['roundoff_total']}} @endif</th>
@endif
<th>{{round($grand_total['sale_price_total'],2)}}</th>
<th>{{round($grand_total['discount_amount_total'],2)}}</th>
<th>{{round($grand_total['net_price_total'],2)}}</th>
<th>Sales 3%</th>
@if($report_type == 'hsn_code') 
<th>{{$grand_total['qty_gst_3_total']}}</th>
@endif
<th>{{round($grand_total['taxable_value_gst_3_total'],3)}}</th>
<th>CGST Ledger 1.50%</th>
<th>{{$gst_1_5 = round($grand_total['gst_amount_gst_3_total']/2,3)}}</th>
<th>SGST Ledger 1.50%</th>
<th>{{$gst_1_5 = round($grand_total['gst_amount_gst_3_total']/2,3)}}</th>
<th>{{$gst_1_5+$gst_1_5}}</th>
<th>Sales 5%</th>
@if($report_type == 'hsn_code') 
<th>{{$grand_total['qty_gst_5_total']}}</th>
@endif
<th>{{round($grand_total['taxable_value_gst_5_total'],3)}}</th>
<th>CGST Ledger 2.50%</th>
<th>{{$gst_2_5 = round($grand_total['gst_amount_gst_5_total']/2,3)}}</th>
<th>SGST Ledger 2.50%</th>
<th>{{$gst_2_5 = round($grand_total['gst_amount_gst_5_total']/2,3)}}</th>
<th>{{$gst_2_5+$gst_2_5}}</th>
<th>Sales 12%</th>
@if($report_type == 'hsn_code') 
<th>{{$grand_total['qty_gst_12_total']}}</th>
@endif
<th>{{round($grand_total['taxable_value_gst_12_total'],3)}}</th>
<th>CGST Ledger 6%</th>
<th>{{$gst_6 = round($grand_total['gst_amount_gst_12_total']/2,3)}}</th>
<th>SGST Ledger 6%</th>
<th>{{$gst_6 = round($grand_total['gst_amount_gst_12_total']/2,3)}}</th>
<th>{{$gst_6+$gst_6}}</th>
<th>Sales 18%</th>
@if($report_type == 'hsn_code') 
<th>{{$grand_total['qty_gst_18_total']}}</th>
@endif
<th>{{round($grand_total['taxable_value_gst_18_total'],3)}}</th>
<th>CGST Ledger 9%</th>
<th>{{$gst_9 = round($grand_total['gst_amount_gst_18_total']/2,3)}}</th>
<th>SGST Ledger 9%</th>
<th>{{$gst_9 = round($grand_total['gst_amount_gst_18_total']/2,3)}}</th>
<th>{{$gst_9+$gst_9}}</th>
<th>Sales 0%</th>
@if($report_type == 'hsn_code') 
<th>{{$grand_total['qty_gst_0_total']}}</th>
@endif
<th>{{round($grand_total['taxable_value_gst_0_total'],3)}}</th>
<th>CGST Ledger 0%</th>
<th>{{$gst_0 = round($grand_total['gst_amount_gst_0_total']/2,3)}}</th>
<th>SGST Ledger 0%</th>
<th>{{$gst_0 = round($grand_total['gst_amount_gst_0_total']/2,3)}}</th>
<th>{{$gst_0+$gst_0}}</th>
</tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
</section>

<div class="modal fade data-modal" id="report_download_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download <?php echo 'Store to Customer Sales Report'.' ('.str_replace('_',' ',$report_type).')' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
            </div>

            <div class="alert alert-success alert-dismissible elem-hidden" id="reportDownloadSuccessMessage"></div>
            <div class="alert alert-danger alert-dismissible elem-hidden" id="reportDownloadErrorMessage"></div>

            <form class="" name="reportDownloadFrm" id="reportDownloadFrm" type="POST">
                <div class="modal-body">
                    <div class="form-row" >
                        <div class="form-group col-md-4" >
                            <label>State</label>
                            <div id="state_list_div_download"></div>
                            <div class="invalid-feedback" id="error_validation_state_list"></div>
                        </div>
                        <div class="form-group col-md-4" >
                            <label>Store</label>
                            <div id="store_list_div_download"></div>
                            <div class="invalid-feedback" id="error_validation_store_list"></div>
                        </div>

                        <div class="form-group col-md-4" >
                            <label>Store Type</label>
                            <div id="store_type_div_download"></div>
                            <div class="invalid-feedback" id="error_validation_store_type"></div>
                        </div>
                    </div>    
                    <div class="form-row" >
                        <div class="form-group col-md-4" >
                            <label>Date</label>
                            <div id="date_range_div_download"></div>
                            <div class="invalid-feedback" id="error_validation_date_range"></div>
                        </div>
                    </div>    
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" id="report_download_cancel" name="report_download_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id ="report_download_submit" name="report_download_submit" class="btn btn-dialog" onclick="submitDownloadStoreToCustomerReport();">Submit</button>
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
<script src="{{ asset('js/users.js') }}" ></script>
<script type="text/javascript">@if(request('state_id') != '') getStateStores({{request('state_id')}},"{{request('store_id')}}"); @endif</script>
@endsection
