@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>
<?php $total_cols = count($size_list)+5; ?>

<table class="table-1" style="text-align: center">
    <tr><td class="heading-4">{{$company_data['company_name']}}</td></tr>
    <tr><td class="text-1">{{$company_data['company_address']}}, {{$company_data['company_state']}} - {{$company_data['company_postal_code']}}, 
    PH NO. : {{$company_data['company_phone_no']}}, GST IN : {{$company_data['company_gst_no']}}</td></tr>
</table>
<table class="table-1" style="margin-top: 5px;">
    <tr>
        <td class="heading-5" >Purchase Order</td>
        <td class="heading-5" style="text-align: right;">Outright Purchase</td>
    </tr>
</table>
       
<table class="table-1" style="border: 1px solid #000;">
    <tr>
        <td colspan="{{ceil($total_cols/2)}}" align="left" style="padding-left: 5px;">
            <table>
                <tr><td class="text-2">PO No: </td><td class="text-3" valign="top">{{$po_data->order_no}}</td></tr>
                <tr><td class="text-2">PO Date: </td><td class="text-3" valign="top">{{date('d/m/Y',strtotime($po_data->created_at))}}</td></tr>
                <tr><td class="text-2">Delivery Date: </td><td class="text-3" valign="top">{{date('d/m/Y',strtotime($po_data->delivery_date))}}</td></tr>
            </table> 
        </td>
        <td colspan="{{floor($total_cols/2)}}"  align="right">
            <table >
                <tr><td class="text-2" >Supplier: </td>
                    <td class="text-3" valign="top">{{$vendor_data->name}}<br>{{$vendor_data->address}}
                        <br>{{$vendor_data->city}}, {{$vendor_data->state_name}} - {{$vendor_data->postal_code}}
                    </td>
                </tr>
                <tr><td class="text-2">GSTIN NO : </td><td class="text-3" valign="top">{{$vendor_data->gst_no}}</td></tr>
            </table> 
        </td>
    </tr>
    <tr class="border-top-tr border-bottom-tr heading-tr-1 content-tr-1 text-1">
        <td class="s-no">SNo.</td><td width="15%">Item</td>
        @for($i=0;$i<count($size_list);$i++)
            
            <td>{{$size_list[$i]['size']}}</td>
        @endfor
        <td>Rate</td><td>Qty</td><td>Amount</td>
    </tr>
    
    <?php $total_amount = $total_qty = $total_gst = 0; $total_size = array(); ?>
    @for($i=0;$i<count($po_items);$i++)
        <?php $size_data = json_decode($po_items[$i]->size_data,true); ?>
        
        <tr class="content-tr-2 text-1">
            <td class="s-no">{{$i+1}}</td>
            <td>{{$po_items[$i]->accessory_name}}</td>
            
            @for($q=0;$q<count($size_list);$q++)
                <?php $size_id = $size_list[$q]['id']; ?>
                <td><?php if(isset($size_data[$size_id])) echo $size_data[$size_id];  ?></td>
                <?php $size_qty = (isset($size_data[$size_id]))?$size_data[$size_id]:0; ?>
                <?php if(isset($total_size[$size_id])) $total_size[$size_id]+=$size_qty; else $total_size[$size_id] = $size_qty; ?>
            @endfor      
            <td>{{$po_items[$i]->rate}}</td>
            <td>{{$po_items[$i]->qty_ordered}}</td>
            <td>{{$po_items[$i]->cost}}</td>
        </tr>
        <?php $total_amount+=($po_items[$i]->cost); ?>
        <?php $total_gst+=($po_items[$i]->gst_amount); ?>
        <?php $total_qty+=($po_items[$i]->qty_ordered); ?>
    @endfor

    <tr><td colspan="{{$total_cols}}">&nbsp;</td></tr>
    <tr class="content-tr-2 text-6 border-bottom-tr border-top-tr">
        <td colspan="2" class="total-td">Total</td>
        @for($q=0;$q<count($size_list);$q++)
            <td>{{$total_size[$size_list[$q]['id']]}}</td>
        @endfor        
        <td></td>
        <td>{{$total_qty}}</td>
        <td>{{round($total_amount,2)}}</td>
    </tr>
    @for($i=0;$i<count($gst_types);$i++)
        <tr class="content-tr-2 text-6 border-bottom-tr total-tr">
            <td colspan="{{count($size_list)+4}}" class="total-td">{{$gst_types[$i]['gst_name']}}</td>
            <td> {{round($total_gst*($gst_types[$i]['gst_percent']/100),2)}}</td>
        </tr>
    @endfor
    <tr class="content-tr-2 text-6 border-bottom-tr total-tr">
        <td colspan="{{count($size_list)+4}}" class="total-td">Other Cost</td>
        <td> {{(!empty($po_data->other_cost))?$po_data->other_cost:0}}</td>
    </tr>
    <tr class="content-tr-2 text-6 border-bottom-tr total-tr">
        <td colspan="{{count($size_list)+4}}" class="total-td">Total Cost</td>
        <td> {{round($total_cost = $total_amount+$po_data->other_cost+$total_gst,2)}}</td>
    </tr>
    <tr class="content-tr-2 text-6 border-bottom-tr total-tr">
        <td colspan="{{count($size_list)+4}}" class="total-td">Round Off (+ -)</td>
        <td> {{round(ceil($total_cost) - $total_cost,2)}}</td>
    </tr>
     <tr class="content-tr-2 text-6 border-bottom-tr total-tr">
        <td colspan="{{count($size_list)+4}}" class="total-td">Net Cost</td>
        <td> {{$net_cost = ceil($total_cost)}}</td>
    </tr>
    <tr class="content-tr-2 text-6 border-bottom-tr total-tr">
        <td colspan="{{$total_cols}}" class="total-td">Rupees {{CommonHelper::numberToWords($net_cost)}} Only</td>
    </tr>
    <tr class="content-tr-2 text-6 border-bottom-tr total-tr">
        <td colspan="{{$total_cols}}" class="total-td">Remarks: {{$po_data->other_comments}}</td>
    </tr>
    <tr class="content-tr-2 text-3 border-bottom-tr total-tr">
        <td colspan="{{$total_cols}}" class="total-td">Declaration:<br>
            We declare that this Invoice shows the actual price of goods<br>
            described and that all particulars are true correct.<br>
            All/Any disputes are Subject to Noida Jurisdiction Only.<br>
        </td>
    </tr>
</table>   
<table class="table-1" >
    <tr class="content-tr-2 text-6 total-tr" >
        <td colspan="3" style="text-align: right;">For {{$company_data['company_name']}}</td>
    </tr>
    <tr>
        <td><br/><br/></td>
    </tr>
    <tr class="content-tr-2 text-6 total-tr" >
        <td colspan="1" >Suppler Acceptance </td>
        <td colspan="1" >PRODUCTION HEAD</td>
        <td colspan="1" style="text-align: right;">Authorized Signatory</td>
    </tr>
    <tr>
        <td><br/></td>
    </tr>
    <tr class="content-tr-2 text-6 total-tr" >
        <td colspan="3" style="text-align: right;">CEO</td>
    </tr>
</table>
                
        
@endsection

