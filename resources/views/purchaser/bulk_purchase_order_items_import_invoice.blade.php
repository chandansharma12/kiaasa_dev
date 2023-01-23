@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>

<table class="table-1" style="text-align: center">
    <tr><td class="heading-4">{{$company_data['company_name']}}</td></tr>
    <tr><td class="text-3">HEAD OFFICE</td></tr>
    <tr><td class="text-1">{{$company_data['company_address']}}, {{$company_data['company_state']}} - {{$company_data['company_postal_code']}}, 
    PH NO. : {{$company_data['company_phone_no']}}, GST IN : {{$company_data['company_gst_no']}}</td></tr>
    <tr><td class="heading-3" style="padding-top: 8px;padding-bottom: 2px;">PURCHASE NOTE</td></tr>
</table>

<table class="table-1" style="border: 1px solid #000;">
    <tr>
        <td colspan="2">
            <table>
                <tr><td class="text-2"><b>GRN No:</b>  </td><td class="text-1" valign="top">{{$vendor_details->grn_no}}</td></tr>
                <tr><td class="text-2"><b>Bill No:</b>  </td><td class="text-1" valign="top">{{$vendor_details->invoice_no}}</td></tr>
           </table> 
        </td>
        <td colspan="2">
            <table>
                <tr><td class="text-2"><b>Date:</b>  </td><td class="text-1" valign="top">&nbsp;&nbsp;&nbsp;&nbsp;{{date('d-m-Y',strtotime($vendor_details->grn_created_date))}}</td></tr>
                <tr><td class="text-2"><b>Date:</b>  </td><td class="text-1" valign="top">&nbsp;&nbsp;&nbsp;&nbsp;{{date('d-m-Y',strtotime($vendor_details->invoice_date))}}</td></tr>
           </table> 
        </td>
        <td colspan="3">
            <table>
                <tr><td class="text-2"><b>Supplier Name:</b> </td><td class="text-1" valign="top">&nbsp;&nbsp;&nbsp;&nbsp;{{$vendor_details->name}}</td></tr>
                <tr><td class="text-2"><b>Address:</b> </td><td class="text-1" valign="top">&nbsp;&nbsp;&nbsp;&nbsp;{{$vendor_details->address}}</td></tr>
           </table>  
        </td>
    </tr>
    <tr class="border-top-tr border-bottom-tr heading-tr-1 content-tr-1 text-1">
        <td class="s-no">SNo.</td><td>Item Code</td><td>Fabric</td>
        <td>Rate</td><td>Qty</td><td>Tax Amount</td><td>Job Amount</td>
    </tr>
    <?php $total_qty_rec = $total_amount = $total_gst = 0;?>
    <?php $invoice_items = json_decode($vendor_details->invoice_items,true); ?>
    @for($i=0;$i<count($purchase_orders_items);$i++)
        <?php $id = $purchase_orders_items[$i]->id;  ?>
        @if(isset($invoice_items[$id]) && !empty($invoice_items[$id]))
            <tr class="content-tr-2 text-1">
                <td class="s-no">{{$i+1}}</td>
                <td>{{$purchase_orders_items[$i]->sku}}</td>
                <td>{{$purchase_orders_items[$i]->fabric_name}}</td>
                <td>{{$rate = $purchase_orders_items[$i]->rate}}</td>
                <td>{{$qty = $invoice_items[$id]}} {{$purchase_orders_items[$i]->unit_code}}</td>
                <?php $gst_amt = ($qty*$rate)*($purchase_orders_items[$i]->gst_percent/100); ?>
                <td>{{round($gst_amt,2) }}</td>
                <td>{{$amount = ($qty*$rate)+$gst_amt}}</td>
            </tr>
            <?php $total_qty_rec+=$qty; ?>
            <?php $total_amount+=$amount; ?>
            <?php $total_gst+=$gst_amt; ?>
        @endif
    @endfor    
    <tr class="border-top-tr border-bottom-tr heading-tr-1 content-tr-1 text-1">
        <td colspan="4" class="s-no">Total</td>
        <td>{{$total_qty_rec}} {{$purchase_orders_items[0]->unit_code}}</td>
        <td>{{round($total_gst,2)}}</td>
        <td>{{$currency}} {{round($total_amount,2)}}</td>
    </tr>    
</table>   

@endsection

