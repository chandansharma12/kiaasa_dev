@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>
<?php $cancel_str = ($debit_note_data->debit_note_status == 'cancelled')?'(Cancelled)':'';  ?>
    
    @if(strtolower($invoice_type) == 'debit_note')
        <table class="table-1">
            @if($gst_name == '')
                <tr><td></td><td class="heading-1">Delivery Challan {{$cancel_str}}</td></tr>
            @else
                <tr><td></td><td class="heading-1">Debit Note {{$cancel_str}}</td></tr>
            @endif
            
            <tr><td colspan="2"><div class="separator-10"></div></td></tr>
            <tr>
                <td width="50%" valign="top" class="text-1">
                    <b>Debit Note To</b><br/>
                    <b>{{$company_data['company_name']}}</b> <br/>
                    Plot no 1/35 <br/> S.S GT ROAD INDUSTRIAL AREA<br/> LAL KUAN GHAZIABAD - 201002<br/>
                    GSTIN : {{$company_data['company_gst_no']}}
                </td>
                <td  width="50%" valign="top" align="right" class="text-1">
                    <b class="text-1">Debit Note From</b><br/>
                    <b>{{strtoupper($store_data->gst_name)}}</b><br/>
                    <span class="text-2">Address : {{$store_data->address_line1}}<br/>{{$store_data->address_line2}}<br/>{{$store_data->city_name}} - {{$store_data->postal_code}}<br/>
                    Ph.No. : {{$store_data->phone_no}}<br/>
                    GSTIN NO.: {{$store_data->gst_no}}</span>
                </td>
            </tr>
        </table>
    @endif
    
    @if(strtolower($invoice_type) == 'credit_note')
        <table class="table-1">
            @if($gst_name == '')
                <tr><td></td><td class="heading-1">Delivery Challan {{$cancel_str}}</td></tr>
            @else
                <tr><td></td><td class="heading-1">Credit Note {{$cancel_str}}</td></tr>
            @endif

            <tr><td colspan="2"><div class="separator-10"></div></td></tr>
            <tr>
                <td  width="50%" valign="top" >
                    <b class="text-1">Credit Note To</b><br/>
                    <b>{{strtoupper($store_data->gst_name)}}</b><br/>
                    <span class="text-2">Address : {{$store_data->address_line1}}<br/>{{$store_data->address_line2}}<br/>{{$store_data->city_name}} - {{$store_data->postal_code}}<br/>
                    Ph.No. : {{$store_data->phone_no}}<br/>
                    GSTIN NO.: {{$store_data->gst_no}}</span>
                </td>
                <td width="50%" valign="top" align="right" class="text-1">
                    <b>Credit Note From</b><br/>
                    <b>{{$company_data['company_name']}}</b> <br/>
                    {{$company_data['company_address']}} - {{$company_data['company_postal_code']}}<br/>
                    GSTIN : {{$company_data['company_gst_no']}}
                </td>
            </tr>
        </table>
    @endif
    
    @if(strtolower($invoice_type) == 'debit_note')
        <table class="table-1 border-top border-bottom" >
            <tr><td width="50%">Debit Note No:&nbsp;{{$debit_note_data->debit_note_no}}<span class="text-3"> </span></td><td width="50%">Debit Note Date:&nbsp;<span class="text-3">{{date('d/m/Y H:i',strtotime($debit_note_data->created_at))}} </span></td></tr>
        </table>    
    @endif
    
    @if(strtolower($invoice_type) == 'credit_note')
        <table class="table-1 border-top border-bottom" >
            <tr><td width="50%">Credit Note No:&nbsp;{{$debit_note_data->credit_note_no}}<span class="text-3"> </span></td><td width="50%">Credit Note Date:&nbsp;<span class="text-3">{{date('d/m/Y H:i',strtotime($debit_note_data->created_at))}} </span></td></tr>
        </table>    
    @endif
    
    
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
        <?php $total_qty = $total_taxable_val = $total_gst_amt = $total_value = $i = 0;$gst_data = array(); ?>    
        @foreach($demand_sku_list as $sku=>$product_data)    
            <tr>
                <td>{{$i+1}}</td>
                <td>{{$product_data['prod']->product_name}}</td>
                <td>{{$product_data['prod']->hsn_code}}</td>
                <td>{{$product_data['qty']}}</td>
                <td>{{(!empty($product_data['prod']->vendor_sku))?$product_data['prod']->vendor_sku:$product_data['prod']->product_sku}}</td>
                <td>{{$product_data['prod']->store_base_rate}}</td>
                <td>{{$taxable_val = round($product_data['prod']->store_base_rate*$product_data['qty'],2)}}</td>
                
                @if($gst_name == 's_gst')
                    <td>{{$product_data['prod']->store_gst_percent/2 }}%</td>
                    <td style="text-align: left;">{{round($taxable_val*($product_data['prod']->store_gst_percent*0.5/100),2)}}</td>
                    <td>{{$product_data['prod']->store_gst_percent/2 }}%</td>
                    <td style="text-align: left;">{{round($taxable_val*($product_data['prod']->store_gst_percent*0.5/100),2)}} </td>
                    <td colspan="2"></td>
                @endif
                
                @if($gst_name == 'i_gst')
                    <td colspan="4"></td>
                    <td>{{$product_data['prod']->store_gst_percent }}%</td>
                    <td>{{round($taxable_val*($product_data['prod']->store_gst_percent/100),2)}}</td>
                @endif
                
                @if($gst_name == '')
                    <td colspan="6"></td>
                @endif
                
                <?php $gst_percent = ($gst_name != '')?$product_data['prod']->store_gst_percent:0; ?>
                <?php $gst_amount = ($gst_name != '')?round($taxable_val*($gst_percent/100),2):0; ?>
                
                <td>{{$value = $taxable_val+$gst_amount}}</td>
            </tr>
            <?php $total_qty+=$product_data['qty']; ?>
            <?php $total_taxable_val+=$taxable_val; ?>
            <?php $total_gst_amt+=$gst_amount; ?>
            <?php $total_value+=$value; ?>
            <?php $gst_data[$gst_percent]['taxable_value'] = (!isset($gst_data[$gst_percent]['taxable_value']))?$taxable_val:$gst_data[$gst_percent]['taxable_value']+$taxable_val;  ?>
            <?php $gst_data[$gst_percent]['gst_amount'] = (!isset($gst_data[$gst_percent]['gst_amount']))?$gst_amount:$gst_data[$gst_percent]['gst_amount']+$gst_amount;  ?>
            <?php $gst_data[$gst_percent]['qty'] = (!isset($gst_data[$gst_percent]['qty']))?$product_data['qty']:$gst_data[$gst_percent]['qty']+$product_data['qty'];  ?>
            <?php $i++; ?>
        @endforeach   
        <tr><td colspan="10"><br/><br/></td></tr>
        <tr>
            <td colspan="3" class="border-top border-bottom">Total</td>
            <td class="border-top border-bottom">{{$total_qty}}</td>
            <td colspan="2" class="border-top border-bottom"></td>
            <td class="border-top border-bottom">{{$total_taxable_val}}</td>
            
            @if($gst_name == 's_gst')
                <td class="border-top border-bottom"></td>
                <td class="border-top border-bottom">{{round($total_gst_amt/2,2)}}</td>
                <td class="border-top border-bottom"></td>
                <td class="border-top border-bottom">{{round($total_gst_amt/2,2)}}</td>
                <td class="border-top border-bottom"></td>
                <td class="border-top border-bottom"></td>
            @endif
            
            @if($gst_name == 'i_gst')
                <td class="border-top border-bottom" colspan="5"></td>
                <td class="border-top border-bottom">{{$total_gst_amt}}</td>
            @endif 
            
            @if($gst_name == '')
                <td class="border-top border-bottom" colspan="6"></td>
            @endif 
            
            <td class="border-top border-bottom">{{$total_value}}</td>
        </tr>
        <tr>
            <td colspan="7" valign="top">
                @if($gst_name != '')
                    <?php ksort($gst_data); ?><br/>
                    @foreach($gst_data as $gst_percent=>$data)
                        GST {{$gst_percent}} % {{$currency}} {{$data['gst_amount']}} ON VALUE {{$currency}} {{$data['taxable_value']}} ({{$data['qty']}} PCS) <br/><br/>
                    @endforeach
                @endif
            </td>
            <td colspan="7" valign="top" align="right">
                <table class="table-1">
                    <tr>
                        <td class="border-bottom text-4">Total Amount Before Tax:</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{$total_taxable_val}}</td>
                    </tr>
                    @if($gst_name == 's_gst')
                        <tr>
                            <td class="border-bottom text-4">Add CGST:</td>
                            <td class="border-bottom text-4 pull-right">{{$currency}} {{round($total_gst_amt/2,2)}}</td>
                        </tr>
                        <tr>
                            <td class="border-bottom text-4">Add SGST:</td>
                            <td class="border-bottom text-4 pull-right">{{$currency}} {{round($total_gst_amt/2,2)}}</td>
                        </tr>
                        <tr>
                            <td  class="border-bottom text-4">Add IGST:</td>
                            <td class="border-bottom text-4 pull-right"></td>
                        </tr>
                    @endif
                    @if($gst_name == 'i_gst')
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
                    @endif
                    
                    @if($gst_name == '')
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
                            <td class="border-bottom text-4 pull-right"></td>
                        </tr>
                    @endif
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
                Remarks: 
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
                Shortage / complaint if any to be reported within 5 days from the bill in Generated.
            </td>
        </tr>
        </tbody>
    </table>
                
@endsection

