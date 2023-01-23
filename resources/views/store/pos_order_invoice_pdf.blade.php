<!DOCTYPE html>
<html lang="en">
<head>
    <title>receipt</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/pdf.css') }}">
    
    <?php 
        if(empty($pos_order_data->store_id) && $pos_order_data->foc == 1){
            $page_height = round(200+(count($pos_order_products)*10)); 
        }elseif(isset($store_data->id) && $store_data->id != 40){
            $page_height = round(210+(count($pos_order_products)*10)); 
        }else{
            $page_height = round(220+(count($pos_order_products)*10)); 
        }
    ?>
    <style>@page { size: 80mm {{$page_height}}mm;padding: 0;margin:0;margin-top:0;padding-top:0;}
    body.receipt .sheet { width: 80mm; height:{{$page_height}}mm;margin-top:0;padding-top:0; } /* change height as you like */
    @media print { body.receipt { width: 80mm;height:{{$page_height}}mm;margin-top:0;padding-top:0; } /* this line is needed for fixing Chrome's bug */
    .center {margin-left: auto;margin-right: auto;}
    html { margin: 0px}
    </style>
</head>

<body class="receipt" style="margin-top:0;padding-top: 0;">
<table style="width: 72mm;margin: auto;margin-top:0;padding-top: 0;">
    <tr>
        <td align="center" >
            <table class="table-2" style="text-align: center;" align="center">
                <tr><td class="heading-1"><b>Tax Invoice</b></td></tr>
                <?php /* $company_name =  (isset($store_data->id) && ($store_data->id == 40 || $store_data->store_info_type == 2))?str_replace('RETAIL LLP', '', $company_data['company_name']):$company_data['company_name']; ?>
                <tr><td class="heading-2" style="padding-bottom:2px;font-weight: 600;font-size:18px;">{{$company_name}}</td></tr> <?php */ ?>
                <tr><td class="heading-2" style="padding-bottom:2px;font-weight: 600;font-size:18px;">{{$pos_order_data->bill_top_text}}</td></tr>
                <tr>
                    <td valign="top" class="text-pos-bill-1" align="center">
                        <table align="center" style="text-align:center;">
                            @if(isset($store_data->id) && $store_data->id != 40)
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>{{$store_data->address_line1}}</b></td></tr>
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>{{$store_data->address_line2}}, {{$store_data->city_name}}-{{$store_data->postal_code}}</b></td></tr>
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>Ph. No. :  {{$store_data->phone_no}}</b></td></tr>
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>GSTIN : {{$pos_order_data->store_gst_no}} @if(isset($pos_order_data->store_info_type_1) && $pos_order_data->store_info_type_1 == 2) ({{$pos_order_data->store_gst_name}})  @endif</b></td></tr>
                            @endif
                            
                            @if(isset($store_data->id) && $store_data->id == 40)
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>Krish Clothing Company - JANAKPURI</b></td></tr>
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>Franch Precious Hospitality & Leisure Pvt Ltd</b></td></tr>
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>G 7 A  Janakpuri District Centre </b></td></tr>
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>Janakpuri New Delhi 110058</b></td></tr>
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>Ph. No. :  {{$store_data->phone_no}}</b></td></tr>
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>GSTIN : {{$store_data->gst_no}}</b></td></tr>
                            @endif
                            
                            @if(empty($pos_order_data->store_id) && $pos_order_data->foc == 1)
                                <tr><td style="padding-top:1px;padding-bottom: 1px;"><b>Warehouse</b></td></tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="border-top">
            <table class="table-2 " >
                <tr>
                    <td class="text-pos-bill-1" style="padding:2px;">Customer Name: </td>
                    <td class="text-pos-bill-1">&nbsp; @if(strtolower($pos_order_data->salutation) != 'other') {{$pos_order_data->salutation}} @endif {{$pos_order_data->customer_name}}</td>
                    @if(!empty($pos_order_data->customer_gst_no))
                        <td class="text-pos-bill-1" style="padding:2px;">&nbsp;&nbsp;&nbsp;GST No: </td>
                        <td class="text-pos-bill-1">&nbsp; {{$pos_order_data->customer_gst_no}}</td>
                    @endif
                </tr>
                <tr><td class="text-pos-bill-1" style="padding:2px;">Mobile No: </td><td class="text-pos-bill-1">&nbsp;{{$pos_order_data->customer_phone}}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="text-pos-bill-1" style="padding:2px;">
            @if(!empty($pos_order_data->order_no)) Invoice No: &nbsp;{{$pos_order_data->order_no}}  &nbsp;&nbsp;&nbsp; @endif Date Time: &nbsp;{{date('d-m-Y  H:i',strtotime($pos_order_data->created_at))}}
        </td>
    </tr>
    <tr>
        <td class="border-top" width="100%">
            <table class="pos-items-table" width="100%">
                
                <tr>
                    <td>S.No</td>
                    <td colspan="3">Description</td>
                    <td class="pull-right" style="padding-right:5mm;">HSN Code</td>
                </tr>
                <tr class="dashed-tr-1">
                    <td colspan="5" style="padding-top:5px;padding-bottom:3px;"></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="pull-center">Qty</td>
                    <td class="pull-center">Rate</td>
                    <td class="pull-center">Discount</td>
                    <td class="pull-right" style="padding-right:5mm;">Amount</td>
                </tr>
                <tr >
                    <td colspan="5" style="padding-top:2px;padding-bottom:1px;"></td>
                </tr>
                <?php $total_discounted_amount  = 0;$gst_detail = array(); ?>
                @for($i=0;$i<count($pos_order_products);$i++)
                    <tr class="content-tr-1">
                        <td valign="top">{{$i+1}}</td>
                        <td colspan="3" valign="top"><b>{{$pos_order_products[$i]->peice_barcode}} [{{$pos_order_products[$i]->product_name}}] [{{$pos_order_products[$i]->size_name}}]</b></td>
                        <td colspan="1" class="pull-right" valign="top" style="padding-right:5mm;">{{$pos_order_products[$i]->hsn_code}}</td>
                    </tr>    
                    <tr class="content-tr-1">
                        <td></td>
                        <td class="pull-center">{{$pos_order_products[$i]->product_quantity}}</td>
                        <td class="pull-center"><b>{{$pos_order_products[$i]->sale_price}}</b></td>
                        <td class="pull-center"><b>{{round($pos_order_products[$i]->discount_percent,3)}}%</b></td>
                        <?php $discounted_amount = $pos_order_products[$i]->sale_price-$pos_order_products[$i]->discount_amount; ?>
                        <td class="pull-right" style="padding-right:5mm;"><b>{{$discounted_amount = ($pos_order_products[$i]->gst_inclusive == 1)?round($discounted_amount-$pos_order_products[$i]->gst_amount,2):round($discounted_amount,2)}}</b></td>
                    </tr>
                    <?php $total_discounted_amount+=$discounted_amount; ?>
                    <?php $gst_percent = ($pos_order_products[$i]->gst_percent); ?>
                    <?php   if(isset($gst_detail[$gst_percent])){ 
                                $gst_detail[$gst_percent]['gst_amt']+=$pos_order_products[$i]->gst_amount;
                                $gst_detail[$gst_percent]['taxable_amt']+=$discounted_amount;
                            }else{ 
                                $gst_detail[$gst_percent] = array('gst_amt'=>$pos_order_products[$i]->gst_amount,'taxable_amt'=>$discounted_amount);
                            }
                    ?>
                @endfor
                <tr class="dashed-tr-2">
                    <td colspan="5" >
                    </td>
                </tr>
                <tr class="content-tr-1">
                    <td colspan="4">TOTAL</td>
                    <td class="pull-right" style="padding-right:5mm;">{{round($total_discounted_amount,2)}}</td>
                </tr>
                @foreach($gst_detail as $percent=>$amount_arr)
                <tr class="content-tr-1 content-tr-3">
                    <td colspan="4">GST {{$percent}} %</td>
                    <td class="pull-right" style="padding-right:5mm;">{{round($amount_arr['gst_amt'],2)}}</td>
                </tr>
                @endforeach
                <tr class="content-tr-1">
                    <td colspan="4">Round Off</td>
                    <td class="pull-right" style="padding-right:5mm;">{{round(ceil($pos_order_data->total_price)-($pos_order_data->total_price),2)}}</td>
                </tr> 
                 <tr class="dashed-tr-2">
                    <td colspan="5" >
                    </td>
                </tr>
                <tr class="content-tr-1">
                    <td ><b>Total</b></td>
                    <td class="pull-center"><b>{{$pos_order_data->total_items}}</b></td>
                    <td colspan="2"></td>
                    <td class="pull-right" style="padding-right:5mm;"><b>{{ceil($pos_order_data->total_price)}}.00</b></td>
                </tr>
                
                <tr class="content-tr-1 content-tr-3">
                    <td align="center" colspan="6" class="border-top border-bottom">
                        GST Detail
                    </td>
                </tr>
                <tr><td colspan="5">
                <table class="pos-items-table" width="100%">       
                <tr class="content-tr-1 content-tr-3">
                    <td colspan="1" valign="top" >
                        GST Name
                    </td>
                    <td valign="top" align="center">
                        Taxable<br> Amt.
                    </td>
                    <td valign="top" align="center">
                        CGST<br> %
                    </td>
                    <td valign="top" align="center">
                        CGST<br> Amt.
                    </td>
                    <td valign="top" align="center">
                        SGST<br> %
                    </td>
                    <td valign="top" align="center" style="padding-right:4mm;">
                        SGST<br> Amt.
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="border-bottom"></td>
                </tr>
                @foreach($gst_detail as $percent=>$amount_arr)
                <tr class="content-tr-1 content-tr-3">
                    <td colspan="1" >GST {{$percent}}%</td>
                    <td align="center">{{round($amount_arr['taxable_amt'],2)}}</td>
                    <td align="center">{{round($percent/2,2)}}</td>
                    <td align="center">{{round($amount_arr['gst_amt']/2,3)}}</td>
                    <td align="center">{{round($percent/2,2)}}</td>
                    <td align="center" style="padding-right:4mm;">{{round($amount_arr['gst_amt']/2,3)}}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="6" class="border-bottom"></td>
                </tr>
                <tr class="content-tr-1">
                    <td colspan="6" class="border-bottom">
                        Rupees {{CommonHelper::numberToWords(ceil($pos_order_data->total_price))}} Only
                    </td>
                </tr> 
                </td></tr></table>
            </table>
        </td>
    </tr>
    
    <tr>
        <td class="text-pos-bill-1" style="padding-top:2px;">
            <?php $payment_received_total = 0; ?>
            @for($i=0;$i<count($payment_types);$i++)
                {{$payment_types[$i]['payment_method']}}: {{ceil($payment_types[$i]['payment_received'])}}  &nbsp;
                <?php $payment_received_total+=$payment_types[$i]['payment_received']; ?>
            @endfor
            
            @if($pos_order_data->voucher_amount > 0)
                <?php $payment_received_total+=$pos_order_data->voucher_amount; ?>
                Cash Voucher: {{$pos_order_data->voucher_amount}} 
            @endif
        </td>
    </tr>
    
    <tr>
        <td class="border-bottom" align="center">
            <table align="center" class="pos-amount-table">
                <tr>
                    <td>
                        <b>RECEIVED AMT :</b>
                    </td>
                    
                    <td><b>{{ceil($payment_received_total)}}.00</b></td>
                </tr>
                <tr><td colspan="2" style="height:3px;"></td></tr>
                <tr>
                    <td >
                        <b>BILL AMT :</b>
                    </td>
                    <td ><b>{{ceil($pos_order_data->total_price)}}.00</b></td>
                </tr>
                <tr><td colspan="2" style="height:3px;"></td></tr>
                <tr>
                    <td >
                        <b>CHANGE AMT :</b>
                    </td>
                    <td ><b>{{ceil($payment_received_total)-ceil($pos_order_data->total_price)}}.00</b></td>
                </tr>
                <tr><td colspan="2" style="height:2px;"></td></tr>
            </table>
        </td>
    </tr>
    
    <tr>
        <td style="font-size:9px;font-weight:bold; padding-right: 5mm;">
            1. We carefully inspect each of our merchandise for damage
            and determine to give quality garments only.<br>
            2. We accept exchange on all fresh merchandise excluding
            jewellery in week days only (Monday to Friday)<br>
            3. Discounted merchandise will not be exchanged or returned.<br>
            4. Altered or torn merchandise would not be considered for
            exchange.<br>
            5. Fading of garment due to sweat in underarm area would not
            be considered for exchange.<br>
            <p align="center" style="font-size:10px;">EXCHANGE TERMS & CONDITIONS</p>
            6. Merchandise for exchange must be unworn with original tags
            attached.<br>
            7. If you purchase a merchandise you are not satisfied with
            please email us at kd@kiaasaretail.com &<br> we can approve the
            exchange/return within 15 days of purchase only after
            inspection.<br>
            8. Kindly preserve cash memo for all future references.<br>
            9. All disputes are subject to Ghaziabad Jurisdiction.<br>
            10. Any merchandise with genuine quality defect will be exchanged within 30 days of purchase. <br>
        </td>
    </tr>
    
    <tr>
        <td class="border-top text-5" align="right" style="padding-right: 5mm;">
            <b>For {{$pos_order_data->bill_bottom_text}}</b>
        </td>
    </tr>
    <tr>
        <td align="center" class="text-5" style="padding-right: 5mm;">
            <b>THANKS ! VISIT AGAIN.........</b>
        </td>
    </tr>
</table>
    <script>window.print();var ROOT_PATH = "{{url('/')}}";
        setTimeout(function(){  window.location.href = ROOT_PATH+'/pos/order/detail/{{$pos_order_data->id}}'; }, 20000);
    </script>
</body>
</html>

