@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>

    <table class="table-1">
        <tr><td></td><td class="heading-1">Tax Invoice</td></tr>
        <tr><td colspan="2"><div class="separator-10"></div></td></tr>
        <tr>
            <td width="50%" valign="top" class="text-1">
                <b>{{$company_data['company_name']}}</b> <br/>
                {{$company_data['company_address']}}<br/>
                GSTIN : {{$company_data['company_gst_no']}}
            </td>
            <td width="50%" valign="top">
                <b class="text-1">Buyer: {{$demand_data->store_name}}</b><br/>
                <span class="text-2">Address : {{$demand_data->address_line1}}<br/>{{$demand_data->address_line2}}<br/>{{$demand_data->city_name}} - {{$demand_data->postal_code}}<br/>
                Ph.No. : {{$demand_data->phone_no}}<br/>
                GSTIN NO.: {{$demand_data->gst_no}}</span>
            </td>
        </tr>
    </table>
    
    <table class="table-1 border-top border-bottom" >
        <tr><td width="50%">Invoice No:&nbsp;<span class="text-3">IN00101 </span></td><td width="50%">Invoice Date:&nbsp;<span class="text-3">{{date('d/m/Y')}} </span></td></tr>
    </table>    
    
    <table class="table-1" >
        <tr>
            <td>SNo.</td><td>Item Name</td><td>HSN <br>Code</td><td>Qty/<br>PCS</td>
            <td width="15%">Style</td><td>Rate</td><td>Taxable<br>Value</td>
            <td colspan="2" style="text-align: left;padding-left: 15px;">CGST</td>
            <td colspan="2" style="text-align: left;padding-left: 15px;">SGST</td>
            <td colspan="2" style="text-align:left;padding-left: 15px;">IGST</td>
            <td>Value</td>
        </tr>
        <tr>
            <td class="border-bottom" colspan="7"></td>
            <td class="border-bottom">Rate</td>
            <td class="border-bottom" style="text-align: left;">Amt</td>
            <td class="border-bottom">Rate</td>
            <td class="border-bottom" style="text-align: left;">Amt</td>
            <td class="border-bottom">Rate</td>
            <td class="border-bottom" style="text-align: left;">Amt</td>
            <td class="border-bottom"></td>
        </tr>    
        <tbody class="content-data-1">
        <?php $total_qty = $total_taxable_val = $total_gst_amt = $total_value = 0;$gst_data = array(); ?>    
        @for($i=0;$i<count($demand_products_list);$i++)    
            <tr>
                <td>{{$i+1}}</td>
                <td>{{$demand_products_list[$i]->product_name}}</td>
                <td>{{$demand_products_list[$i]->hsn_code}}</td>
                <td>{{$demand_products_list[$i]->inventory_count}}</td>
                <td>{{$demand_products_list[$i]->product_sku}}</td>
                <td>{{$demand_products_list[$i]->base_price}}</td>
                <td>{{$taxable_val = round($demand_products_list[$i]->base_price*$demand_products_list[$i]->inventory_count,2)}}</td>
                <td colspan="4"></td>
                <td>{{$gst_percent = CommonHelper::getGSTPercent($taxable_val)}}%</td>
                <td style="text-align: left;">{{$gst_amount = round($taxable_val*($gst_percent/100),2)}}</td>
                <td>{{$value = $taxable_val+$gst_amount}}</td>
            </tr>
            <?php $total_qty+=$demand_products_list[$i]->inventory_count; ?>
            <?php $total_taxable_val+=$taxable_val; ?>
            <?php $total_gst_amt+=$gst_amount; ?>
            <?php $total_value+=$value; ?>
            <?php $gst_data[$gst_percent]['taxable_value'] = (!isset($gst_data[$gst_percent]['taxable_value']))?$taxable_val:$gst_data[$gst_percent]['taxable_value']+$taxable_val;  ?>
            <?php $gst_data[$gst_percent]['gst_amount'] = (!isset($gst_data[$gst_percent]['gst_amount']))?$gst_amount:$gst_data[$gst_percent]['gst_amount']+$gst_amount;  ?>
        @endfor   
        <tr><td colspan="10"><br/><br/></td></tr>
        <tr>
            <td colspan="3" class="border-top border-bottom">Total</td>
            <td class="border-top border-bottom">{{$total_qty}}</td>
            <td colspan="2" class="border-top border-bottom"></td>
            <td class="border-top border-bottom">{{$total_taxable_val}}</td>
            <td colspan="5" class="border-top border-bottom"></td>
            <td class="border-top border-bottom">{{$total_gst_amt}}</td>
            <td class="border-top border-bottom">{{$total_value}}</td>
        </tr>
        <tr>
            <td colspan="7" valign="top">
                <?php ksort($gst_data); ?><br/>
                @foreach($gst_data as $gst_percent=>$data)
                    GST {{$gst_percent}} % {{$currency}} {{$data['gst_amount']}} ON VALUE {{$currency}} {{$data['taxable_value']}} <br/><br/>
                @endforeach
            </td>
            <td colspan="7" valign="top" align="right">
                <table class="table-1">
                    <tr>
                        <td class="border-bottom text-4">Total Amount Before Tax:</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{$total_taxable_val}}</td>
                    </tr>
                    <tr>
                        <td class="border-bottom text-4">Add CGST:</td>
                        <td class="border-bottom text-4 pull-right"></td>
                    </tr>
                    <tr>
                        <td class="border-bottom text-4">Add SGST:</td>
                        <td class="border-bottom text-4 pull-right"></td>
                    </tr>
                    <tr>
                        <td  class="border-bottom text-4">Add IGST:</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{$total_gst_amt}}</td>
                    </tr>
                    <tr>
                        <td class="border-bottom text-4">Total Amount Tax:</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{$total_gst_amt}}</td>
                    </tr>
                    <tr>
                        <td class="border-bottom text-4">Grand Total:</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{$total_value}}</td>
                    </tr>
                    <tr>
                        <td class="border-bottom text-4">Round Off (+-):</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{round(ceil($total_value)-$total_value,2)}}</td>
                    </tr>
                    <tr>
                        <td class="text-4">NET AMOUNT:</td>
                        <td class="text-4 pull-right">{{$currency}} {{ceil($total_value)}}</td>
                    </tr>
                </table>
            </td>
        </tr> 
        <tr>
            <td colspan="14" class="border-top border-bottom text-5">
                Rupees {{CommonHelper::numberToWords(ceil($total_value))}} Only
            </td>
        </tr>
        <tr><td colspan="14"><br/></td></tr>
        <tr>
            <td colspan="7" align="left">
                Remarks: REQPCS10PCS
            </td>
            <td colspan="7" class="pull-right">
                For {{$company_data['company_name']}}
            </td>
        </tr>
        <tr><td colspan="14"><br/></td></tr>
        <tr>
            <td colspan="14" class="text-2" align="left">
                Goods once sold will not be taken back.<br>
                Price in including GST.<br/>
                All / Any disputes are Subject to NOIDA(U.P) Jurisdiction Only.<br/>
                24% Interest will be charged if the Bill is not paid within 30 Days.<br/>
                Shortage / complaint if any to be reported within 5 days from the bill in Genarated.
            </td>
        </tr>
        </tbody>
    </table>
                
        
@endsection

