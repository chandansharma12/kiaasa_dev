@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>

<table class="table-1" style="text-align: center">
    <tr><td class="heading-4">Mismatch Report</td></tr>
    <tr><td class="text-1">{{$audit_data->store_name}}</td></tr>
    <tr><td class="text-1">{{$audit_data->audit_no}}</td></tr>
    <tr><td class="text-1">Audit Date: {{date('d-M-Y H:i',strtotime($audit_data->created_at))}}</td></tr>
</table>

<table class="table-1" cellspacing="0" style="border: 1px solid #000;">
    
        <tr class="border-bottom-tr content-tr-1 text-1"><td colspan="2" align="center"><b>Mismatch in Store</b></td></tr>
        <tr class="content-tr-2 text-1">
            <td style="width:20%">Total Quantity Mismatched: </td>
            <td>{{$qrcode_inventory['inv_count_mismatch_store']}}</td>
        </tr>    
        <tr class="content-tr-2 text-1">
            <td>Total Amount Mismatched: </td>
            <td>{{$qrcode_inventory['inv_price_mismatch_store']}}</td>
        </tr>     
        <tr class="content-tr-2 text-1">
            <td>Mismatched QR Codes: </td>
            <td>{{implode(', ',$qrcode_inventory['inv_qrcodes_mismatch_store'])}}</td>
        </tr>  
    
</table>
<br/>
<table class="table-1" cellspacing="0" style="border: 1px solid #000;">
    
        <tr class="border-bottom-tr content-tr-1 text-1"><td colspan="2" align="center"><b>Mismatch in System</b></td></tr>
        <tr class="content-tr-2 text-1">
            <td style="width:20%">Total Quantity Mismatched: </td>
            <td>{{$qrcode_inventory['inv_count_mismatch_system']}}</td>
        </tr>    
        <tr class="content-tr-2 text-1">
            <td>Total Amount Mismatched: </td>
            <td>{{$qrcode_inventory['inv_price_mismatch_system']}}</td>
        </tr>     
        <tr class="content-tr-2 text-1">
            <td>Mismatched QR Codes: </td>
            <td>{{implode(', ',$qrcode_inventory['inv_qrcodes_mismatch_system'])}}</td>
        </tr>  
    
</table>


@endsection