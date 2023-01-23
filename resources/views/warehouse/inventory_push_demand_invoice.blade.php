@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>
<?php $cancel_str = ($demand_data->demand_status == 'cancelled')?'(Cancelled)':'';  ?>
<?php $new_format_demand_id = 1236; ?>
<?php $new_gst_round_demand_id = 2441; ?>

    <table class="table-1">
        @if($gst_name == '')
            <tr><td></td><td class="heading-1">Delivery Challan {{$cancel_str}}</td></tr>
        @else
            <tr><td></td><td class="heading-1">Tax Invoice {{$cancel_str}}</td></tr>
        @endif
        
        <tr><td colspan="2"><div class="separator-10"></div></td></tr>
        <?php $store_data = json_decode($demand_data->store_data,false); ?>
        <tr>
            <td width="50%" valign="top" >
                @if($gst_name == '')
                    <b class="text-1">Challan To</b><br/>
                @else
                    <b class="text-1">Invoice To</b><br/>
                @endif
                <b>{{strtoupper($store_data->gst_name)}}</b><br/>
                
                <span class="text-2">Address : {{$store_data->address_line1}}<br/>{{$store_data->address_line2}}<br/>{{$store_data->city_name}} - {{$store_data->postal_code}}<br/>{{$demand_data->state_name}}<br/>
                Ph.No. : {{$store_data->phone_no}}<br/>
                GSTIN NO.: {{$store_data->gst_no}}</span>
                
                @if(isset(($gate_pass_data->ship_to)) && !empty($gate_pass_data->ship_to))
                    <br/>
                    <b class="text-1">Ship To</b><br/>
                    <span class="text-2">{{$gate_pass_data->ship_to}}</span>
                @endif
            </td>
            <td width="50%" valign="top" align="right" class="text-1">
                @if($gst_name == '')
                    <b class="text-1">Challan From</b><br/>
                @else
                    <b class="text-1">Invoice From</b><br/>
                @endif
                <b>{{$demand_data->company_gst_name}}</b><br/>
                
                Plot no 1/37,<br/>
                S.S GT ROAD INDUSTRIAL AREA <br>
                LAL KUAN GHAZIABAD - 201002
                <br/>GSTIN : {{$demand_data->company_gst_no}}
                
                @if(isset(($gate_pass_data->dispatch_by)) && !empty($gate_pass_data->dispatch_by))
                    <br/>
                    <b class="text-1">Dispatch From</b><br/>
                    <span class="text-2">{{$gate_pass_data->dispatch_by}}</span>
                @endif
                
            </td>
        </tr>
    </table>
    
    <table class="table-1 border-top border-bottom" >
        <tr><td width="50%">Invoice No:&nbsp;<span class="text-3">{{$demand_data->invoice_no}} </span></td><td width="50%">Invoice Date:&nbsp;<span class="text-3">{{date('d-m-Y',strtotime($demand_data->created_at))}} </span></td></tr>
    </table>    
    
    <table class="table-1" >
        <tr>
            <td>SNo.</td><td>Item Name</td><td width="15%">Style</td>
            @if($demand_data->invoice_type  == 'product_id')
                <td>Size</td>
                <td>Color</td>
            @endif
            <td>HSN <br>Code</td><td>Qty/<br>PCS</td>
            <td>Rate</td><td>Taxable<br>Value</td>
            <td colspan="2" style="text-align: left;padding-left: 15px;">CGST</td>
            <td colspan="2" style="text-align: left;padding-left: 15px;">SGST</td>
            <td colspan="2" style="text-align:left;padding-left: 15px;">IGST</td>
            <td>Value</td>
        </tr>
        <tr>
            @if($demand_data->invoice_type  == 'product_id')
                <td class="border-bottom" colspan="9"></td>
            @else
                <td class="border-bottom" colspan="7"></td>
            @endif
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
                <td>{{$product_data['prod']->vendor_sku}}</td>
                @if($demand_data->invoice_type  == 'product_id')
                    <td>{{$sizes[$product_data['prod']->size_id]}}</td>
                    <td>{{$colors[$product_data['prod']->color_id]}}</td>
                @endif
                <td>{{$product_data['prod']->hsn_code}}</td>
                <td>{{$product_data['qty']}}</td>
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
                <?php if($demand_data->id >= $new_gst_round_demand_id ){ $gst_amount = ($gst_name != '')?round($taxable_val*($gst_percent/100),6):0; } else { $gst_amount = ($gst_name != '')?round($taxable_val*($gst_percent/100),2):0; } ?>
               
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
        
        
        @foreach($demand_sku_list_arnon as $sku=>$product_data)    
            <tr>
                <td>{{$i+1}}</td>
                <td>{{$product_data['prod']->product_name}}</td>
                <td>{{$product_data['prod']->product_sku}}</td>
                @if($demand_data->invoice_type  == 'product_id')
                    <td>{{$sizes[$product_data['prod']->size_id]}}</td>
                    <td>{{$colors[$product_data['prod']->color_id]}}</td>
                @endif
                <td>{{$product_data['prod']->hsn_code}}</td>
                <td>{{$product_data['qty']}}</td>
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
                <?php if($demand_data->id >= $new_gst_round_demand_id ){ $gst_amount = ($gst_name != '')?round($taxable_val*($gst_percent/100),6):0; } else { $gst_amount = ($gst_name != '')?round($taxable_val*($gst_percent/100),2):0; } ?>
               
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
        
        @if($demand_data->invoice_type  == 'product_id')
            <tr><td colspan="12"><br/><br/></td></tr>
        @else
            <tr><td colspan="10"><br/><br/></td></tr>
        @endif
        <tr>
            @if($demand_data->invoice_type  == 'product_id')
                <td colspan="6" class="border-top border-bottom">Total</td>
            @else
                <td colspan="4" class="border-top border-bottom">Total</td>
            @endif
            <td class="border-top border-bottom">{{$total_qty}}</td>
            <td colspan="1" class="border-top border-bottom"></td>
            <td class="border-top border-bottom">{{round($total_taxable_val,2)}}</td>
            
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
                <td class="border-top border-bottom">{{round($total_gst_amt,2)}}</td>
            @endif 
            
            @if($gst_name == '')
                <td class="border-top border-bottom" colspan="6"></td>
            @endif 
            
            <td class="border-top border-bottom">{{round($total_value,2)}}</td>
        </tr>
        <tr>
            <td @if($demand_data->invoice_type  == 'product_id') colspan="9" @else colspan="7" @endif valign="top">
                @if($gst_name != '')
                    <?php ksort($gst_data); ?><br/>
                    @foreach($gst_data as $gst_percent=>$data)
                        GST {{$gst_percent}} % {{$currency}} {{round($data['gst_amount'],2)}} ON VALUE {{$currency}} {{$data['taxable_value']}} ({{$data['qty']}} PCS) <br/><br/>
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
                            <td class="border-bottom text-4 pull-right">{{$currency}} {{round($total_gst_amt,2)}}</td>
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
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{round($total_gst_amt,2)}}</td>
                    </tr>
                    <tr>
                        <td class="border-bottom text-4">Grand Total:</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{round($total_value,2)}}</td>
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
            <td @if($demand_data->invoice_type  == 'product_id') colspan="16" @else colspan="14" @endif class="border-top border-bottom text-5">
                Rupees {{CommonHelper::numberToWords(ceil($total_value))}} Only
            </td>
        </tr>
        <tr><td @if($demand_data->invoice_type  == 'product_id') colspan="16" @else colspan="14" @endif><br/></td></tr>
        <tr>
            <td @if($demand_data->invoice_type  == 'product_id') colspan="8" @else colspan="7" @endif align="left">
                Remarks: 
            </td>
            <td @if($demand_data->invoice_type  == 'product_id') colspan="8" @else colspan="7" @endif class="pull-right">
                For {{$demand_data->company_gst_name}}
            </td>
        </tr>
        <tr><td colspan="14"><br/></td></tr>
        
        <?php echo CommonHelper::getDemandGSNData($demand_data,$gst_name); ?>
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

