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
        <td class="s-no">SNo.</td><td>Item Code</td><td>Item Name</td>
        <td>Qty</td><td>Rate</td><td>Tax Amount</td><td>Job Amount</td>
    </tr>
    <?php $grand_total_qty = $grand_total_tax = $grand_total_amount = 0;?>
    @for($i=0;$i<count($products_list);$i++)
        <?php //$base_price = (!empty($products_list[$i]->vendor_base_price))?$products_list[$i]->vendor_base_price:$products_list[$i]->rate; ?>
        <?php $base_price = $products_list[$i]->poi_rate; ?>
        <?php $total_rate = ($base_price*$products_list[$i]->products_count);
        //$gst_amount = (!empty($products_list[$i]->vendor_gst_amount))?$products_list[$i]->vendor_gst_amount:($products_list[$i]->rate*($products_list[$i]->gst_percent/100));
        $gst_amount = $products_list[$i]->poi_rate*($products_list[$i]->poi_gst_percent/100);
        $tax_amount = round($gst_amount*$products_list[$i]->products_count,2);
        $total_amount = ($total_rate+$tax_amount);
                           
        $grand_total_qty+=$products_list[$i]->products_count;
        $grand_total_tax+=$tax_amount;
        $grand_total_amount+=$total_amount; ?>
        <tr class="content-tr-2 text-1">
            <td class="s-no">{{$i+1}}</td>
            <td>{{$products_list[$i]->vendor_sku}}</td>
            <td>{{$products_list[$i]->product_name}}</td>
            <td>{{$products_list[$i]->products_count}}</td>
            <td>{{$base_price}}</td>
            <td>{{$tax_amount}}</td>
            <td>{{$total_amount}}</td>
        </tr>
    @endfor    
    <tr class="border-top-tr border-bottom-tr heading-tr-1 content-tr-1 text-1">
        <td colspan="3" class="s-no">Total</td>
        <td>{{$grand_total_qty}}</td>
        <td></td>
        <td>{{$grand_total_tax}}</td>
        <td>{{$grand_total_amount}}</td>
    </tr>    
</table>   

@endsection

