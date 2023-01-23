@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>
<?php $inv_total = array('inv_count_system'=>0,'inv_count_wh'=>0,'inv_price_system'=>0,'inv_price_wh'=>0); ?>

<table class="table-1" style="text-align: center">
    <tr><td class="heading-4">Audit Report</td></tr>
    <tr><td class="text-1">{{$audit_data->audit_no}}</td></tr>
    <tr><td class="text-1">{{$audit_data->store_name}}</td></tr>
    <tr><td class="text-1">Audit Date: {{date('d-M-Y H:i',strtotime($audit_data->created_at))}}</td></tr>
</table>

<table class="table-1" cellspacing="0" style="border: 1px solid #000;">
    <tr class="border-bottom-tr content-tr-1 text-1"><td colspan="2" align="center"><b>Audit Details</b></td></tr>
    <tr class="content-tr-2 text-1">
        <td style="width:20%">Members Present: </td>
        <td>{{$audit_data->members_present}}</td>
    </tr> 
    <tr class="content-tr-2 text-1">
        <td style="width:20%">Manual Bills: </td>
        <td>{{$audit_data->manual_bills}}</td>
    </tr>    
   <tr class="content-tr-2 text-1">
        <td style="width:20%">Cash Verified: </td>
        <td>{{$audit_data->cash_verified}}</td>
    </tr>  
    <tr class="content-tr-2 text-1">
        <td style="width:20%">Cash Verification Comments: </td>
        <td>{{$audit_data->cash_verified_comment}}</td>
    </tr>  
    <tr class="content-tr-2 text-1">
        <td style="width:20%">Auditor: </td>
        <td>{{$audit_data->auditor_name}}</td>
    </tr>  
</table>

<br/>
<table class="table-1" cellspacing="0" style="border: 1px solid #000;">
    <tr class="border-bottom-tr content-tr-1 text-1"><td colspan="2"></td><td colspan="2" style="padding-left:30%;"><b>System</b></td><td colspan="2" style="padding-left:30%;"><b>Store</b></td></tr>
    <tr class="border-bottom-tr content-tr-1 text-1"><td class="s-no">SNo</td><td>Category</td><td>Quantity</td><td>Value</td><td>Quantity</td><td>Value</td></tr>

    @for($i=0;$i<count($category_list);$i++)
        <tr class="content-tr-2 text-1">
            <td class="s-no">{{$i+1}}</td>
            <td>{{$category_list[$i]['name']}}</td>
            <td>{{$inv_count_system = (isset($audit_inventory_system[$category_list[$i]['id']]->inv_count))?$audit_inventory_system[$category_list[$i]['id']]->inv_count:0}}</td>
            <td>{{$inv_price_system = (isset($audit_inventory_system[$category_list[$i]['id']]->inv_price))?$audit_inventory_system[$category_list[$i]['id']]->inv_price:0}}</td>
            <td>{{$inv_count_wh = (isset($audit_inventory_store[$category_list[$i]['id']]->inv_count))?$audit_inventory_store[$category_list[$i]['id']]->inv_count:0}}</td>
            <td>{{$inv_price_wh = (isset($audit_inventory_store[$category_list[$i]['id']]->inv_price))?$audit_inventory_store[$category_list[$i]['id']]->inv_price:0}}</td>
        </tr>
        <?php $inv_total['inv_count_system']+=$inv_count_system; ?>
        <?php $inv_total['inv_price_system']+=$inv_price_system; ?>
        <?php $inv_total['inv_count_wh']+=$inv_count_wh; ?>
        <?php $inv_total['inv_price_wh']+=$inv_price_wh; ?>
    @endfor
    <tr class="content-tr-2 text-6 total-tr border-top-tr">
        <td colspan="2">Total</td>
        <td>{{$inv_total['inv_count_system']}}</td>
        <td>{{$inv_total['inv_price_system']}}</td>
        <td>{{$inv_total['inv_count_wh']}}</td>
        <td>{{$inv_total['inv_price_wh']}}</td>
    </tr>

</table>


@endsection