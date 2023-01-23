@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>

    <table class="table-1" style="text-align: center">
        
        <tr><td class="heading-1" style="font-size:16px;"><b>Gate Pass</b></td></tr>
        
        <tr><td><div class="separator-10"></div></td></tr>
        <tr>
            <td width="100%" valign="top" class="text-3">
                <b>{{$company_data['company_name']}}</b> <br/>
                {{$company_data['company_address']}}<br/>
                GSTIN : {{$company_data['company_gst_no']}}
            </td>
           
        </tr>
    </table>
    
<br/>

<table cellpadding="0" id="table_gate_pass" cellspacing="0" >
    
    <tr class="border-bottom-tr">
        <td>Company Invoice No: </td><td>{{$demand_data->invoice_no}}</td>
    </tr>    
    <tr class="border-bottom-tr"> 
        <td>Invoice Date: </td><td>{{date('d-m-Y',strtotime($demand_data->created_at))}}</td>
    </tr>
    <tr class="border-bottom-tr"> 
        <td>No of Boxes: </td><td>{{$gate_pass_data->boxes_count}}</td>
    </tr>
    <tr class="border-bottom-tr"> 
        <td>No of Pcs: </td><td>{{$total_qty}}</td>
    </tr>
    <tr class="border-bottom-tr">
        <td>Transporter Name: </td><td>{{$gate_pass_data->transporter_name}}</td>
    </tr>    
    <tr class="border-bottom-tr">
        <td>Transporter GSTIN No: </td><td>{{$gate_pass_data->transporter_gst}}</td>
    </tr>    
    <tr class="border-bottom-tr">
        <td>Docket No: </td><td>{{$gate_pass_data->docket_no}}</td>
    </tr>    
    <tr class="border-bottom-tr">
        <td>EWay Bill No: </td><td>{{$gate_pass_data->eway_bill_no}}</td>
    </tr>
    <tr class="border-bottom-tr">
         <td>Destination: </td><td  style="padding:5px;font-size:12px;">{{$demand_data->store_name}}, {{$demand_data->city_name}}, {{$demand_data->postal_code}}</td>
    </tr>    
        
</table>

        
@endsection

