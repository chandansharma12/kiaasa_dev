@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>

<table class="table-1" style="text-align: center">
    <tr><td class="heading-4">Daily Sales Report</td></tr>
    <tr><td class="text-3">@if(!empty($store_data)) {{$store_data->store_name}} ({{$store_data->store_id_code}}) @else All Stores @endif</td></tr>
    @if(!empty($data['startDate']) && !empty($data['endDate']))
        <tr><td class="text-3">{{date('d-m-Y',strtotime($data['startDate']))}} - {{date('d-m-Y',strtotime($data['endDate']))}}</td></tr>
    @endif
</table>

<table class="table-1" style="border: 1px solid #000;" cellspacing="0" >
    <thead><tr class="border-top-tr border-bottom-tr heading-tr-1 content-tr-1 text-1">
        <td class="s-no">Date</td>
        <td align="center">Total Orders</td>
        <td align="center">Sold Units</td>
        <td align="center">Return</td>
        <td align="center">Total Units</td>
        <td align="center">Arnon</td>
        @if($user->user_type == 1)
            <td>Base Price</td>
        @endif
        <td>Sale Price</td>
        <td>Discount</td>
        <td>GST</td>
        <td>NET Price</td>
        <td>Cash</td>
        <td>Card</td>
        <td>E-Wallet</td>
    </tr></thead>
    <tbody>
        <?php $total_order = $total_base_price = $total_sale_price = $total_discount_amount = $total_gst_amount = $total_net_price = 0; ?>
        <?php $total_cash = $total_card = $total_ewallet = $units_total = $units_return =  $units_arnon = 0; ?>
        @for($i=0;$i<count($sales_data);$i++)
            <?php $date = date('Y-n-d',strtotime($sales_data[$i]->sales_date)); ?>
            <tr class="content-tr-2 text-1 border-bottom-tr">
                <td class="s-no">{{date('d M Y',strtotime($sales_data[$i]->sales_date))}}</td>
                <td align="center">{{$sales_data[$i]->orders_count}}</td>
                <td align="center">{{$sales_data[$i]->items_count_1}}</td>
                <td align="center">{{$sales_data[$i]->items_count_2}}</td>
                <td align="center">{{$sales_data[$i]->items_count_1-$sales_data[$i]->items_count_2}}</td>
                <td align="center">{{$sales_data[$i]->arnon_inv}}</td>
                @if($user->user_type == 1)
                    <td>{{$sales_data[$i]->total_base_price}}</td>
                @endif
                <td>{{$sales_data[$i]->total_sale_price}}</td>
                <td>{{round($sales_data[$i]->total_discount_amout,2)}}</td>
                <td>{{round($sales_data[$i]->total_gst_amout,2)}}</td>
                <td>{{round($sales_data[$i]->total_net_price,2)}}</td>
                <td>@if(isset($payment_data[$date]['cash'])) {{$payment_data[$date]['cash']}} @else 0.00 @endif</td>
                <td>@if(isset($payment_data[$date]['card'])) {{$payment_data[$date]['card']}} @else 0.00 @endif</td>
                <td>@if(isset($payment_data[$date]['e-wallet'])) {{$payment_data[$date]['e-wallet']}} @else 0.00 @endif</td>
            </tr>
            <?php $total_order+=$sales_data[$i]->orders_count; $total_base_price+=$sales_data[$i]->total_base_price;  ?>
            <?php $total_sale_price+=$sales_data[$i]->total_sale_price; $total_discount_amount+=$sales_data[$i]->total_discount_amout;  ?>
            <?php $total_net_price+=$sales_data[$i]->total_net_price; ?>
            <?php $total_gst_amount+=$sales_data[$i]->total_gst_amout; ?>
            <?php $units_total+=$sales_data[$i]->items_count_1; ?>
            <?php $units_return+=$sales_data[$i]->items_count_2; ?>
            <?php $units_arnon+=$sales_data[$i]->arnon_inv; ?>
            <?php $total_cash+=(isset($payment_data[$date]['cash']))?$payment_data[$date]['cash']:0; ?>
            <?php $total_card+=(isset($payment_data[$date]['card']))?$payment_data[$date]['card']:0; ?>
            <?php $total_ewallet+=(isset($payment_data[$date]['e-wallet']))?$payment_data[$date]['e-wallet']:0; ?>
        @endfor
        <tr class="border-top-tr border-bottom-tr heading-tr-1 content-tr-1 text-1">
            <td class="s-no">Total</td>
            <td align="center">{{$total_order}}</td>
            <td align="center">{{$units_total}}</td>
            <td align="center">{{$units_return}}</td>
            <td align="center">{{$units_total-$units_return}}</td>
            <td align="center">{{$units_arnon}}</td>
            @if($user->user_type == 1)
                <td>{{$total_base_price}}</td>
            @endif
            <td>{{$total_sale_price}}</td>
            <td>{{round($total_discount_amount,2)}}</td><td>{{round($total_gst_amount,2)}}</td>
            <td>{{round($total_net_price,2)}}</td>
            <td>{{$total_cash}}</td> <td>{{$total_card}}</td> <td>{{$total_ewallet}}</td>
        </tr>
    </tbody>
    
</table>

@endsection

