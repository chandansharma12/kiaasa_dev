@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>

    @if(strtolower($invoice_type) == 'debit_note')
        <table class="table-1">
           <tr><td></td><td class="heading-1"><b>Debit Note</b></td></tr>
            <tr><td colspan="2"><div class="separator-10"></div></td></tr>
            <tr>
                <td width="50%" valign="top" class="text-1">
                    <b>{{$company_data['company_name']}}</b> <br/>
                    {{$company_data['company_address']}}<br/>
                    GSTIN : {{$company_data['company_gst_no']}}
                </td>
                <td width="50%" valign="top" align="right">
                    <b class="text-1">Supplier: {{$po_data->name}}</b><br/>
                    <span class="text-2">Address : {{$po_data->address}}<br/>{{$po_data->city}} - {{$po_data->postal_code}}<br/>{{$po_data->address}}
                    Ph.No. : {{$po_data->phone}}<br/>
                    GSTIN NO.: {{$po_data->gst_no}}</span>
                </td>
            </tr>
        </table>
    @endif
    
    @if(strtolower($invoice_type) == 'credit_note')
        <table class="table-1">
           <tr><td></td><td class="heading-1"><b>Credit Note</b></td></tr>
            <tr><td colspan="2"><div class="separator-10"></div></td></tr>
            <tr>
                <td width="50%" valign="top" class="text-1">
                    <b class="text-1">Supplier: {{$po_data->name}}</b><br/>
                    <span class="text-2">Address : {{$po_data->address}}<br/>{{$po_data->city}} - {{$po_data->postal_code}}<br/>{{$po_data->address}}
                    Ph.No. : {{$po_data->phone}}<br/>
                    GSTIN NO.: {{$po_data->gst_no}}</span>
                </td>
                <td width="50%" valign="top" align="right">
                    <b>{{$company_data['company_name']}}</b> <br/>
                    {{$company_data['company_address']}}<br/>
                    GSTIN : {{$company_data['company_gst_no']}}
                </td>
            </tr>
        </table>
    @endif
    
    <table class="table-1 border-top border-bottom" >
        <tr>
            @if(strtolower($invoice_type) == 'debit_note')
                <td width="20%">Invoice No:&nbsp;<span class="text-2">{{$debit_note_data->debit_note_no}} </span></td>
            @endif
            @if(strtolower($invoice_type) == 'credit_note')
                <td width="20%">Invoice No:&nbsp;<span class="text-2">{{$debit_note_data->credit_note_no}} </span></td>
            @endif
            <td width="25%">Purchase Order Invoice No:&nbsp;<span class="text-2">{{$po_data->invoice_no}} </span></td>
            <td width="20%">Invoice Date:&nbsp;<span class="text-2">{{date('d-m-Y',strtotime($po_data->invoice_date))}} </span></td>
            <td width="35%">GRN No:&nbsp;<span class="text-2">{{$po_data->grn_no}} </span></td>
        </tr>
    </table>    
    
    <table class="table-1" >
        <tr>
            <td>SNo.</td><td>Item Name</td><td>HSN <br>Code</td><td>Qty/<br>PCS</td>
            <td width="15%">Style</td><td>PO Cost</td><td>Invoice Cost</td>
            <td>Debit Note Amount</td>
        </tr>
       
        <tbody class="content-data-1">
        <?php $total_qty = $total_debit_note_amount = 0;?>    
        @for($i=0;$i<count($sku_list);$i++)    
            <tr>
                <td>{{$i+1}}</td>
                <td>{{$sku_list[$i]->product_name}}</td>
                <td>{{$sku_list[$i]->hsn_code}}</td>
                <td>{{$sku_list[$i]->item_qty}}</td>
                <td>{{$sku_list[$i]->vendor_sku}}</td>
                <td>{{$sku_list[$i]->item_cost}}</td>
                <td>{{$sku_list[$i]->item_invoice_cost}}</td>
                <?php $debit_note_amount = ($sku_list[$i]->item_invoice_cost-$sku_list[$i]->item_cost)*$sku_list[$i]->item_qty; ?>
                <td>{{round($debit_note_amount,2)}}</td>
            </tr>
            <?php $total_qty+=$sku_list[$i]->item_qty; ?>
            <?php $total_debit_note_amount+=$debit_note_amount; ?>
           
           
        @endfor
        <tr><td colspan="8"><br/><br/></td></tr>
        
        <tr>
            <td colspan="8" valign="top" align="right">
                <table class="table-1">
                    
                    <tr>
                        <td class="border-bottom text-4">Grand Total:</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{$total_debit_note_amount}}</td>
                    </tr>
                    <tr>
                        <td class="border-bottom text-4">Round Off (+-):</td>
                        <td class="border-bottom text-4 pull-right">{{$currency}} {{round(ceil($total_debit_note_amount)-$total_debit_note_amount,2)}}</td>
                    </tr>
                    <tr>
                        <td class="text-4">NET AMOUNT:</td>
                        <td class="text-4 pull-right">{{$currency}} {{ceil($total_debit_note_amount)}}</td>
                    </tr>
                </table>
            </td>
        </tr> 
        <tr>
            <td colspan="8" class="border-top border-bottom text-5">
                Rupees {{CommonHelper::numberToWords(ceil($total_debit_note_amount))}} Only
            </td>
        </tr>
        <tr><td colspan="8"><br/></td></tr>
        <tr>
            <td colspan="4" align="left">
                Remarks: 
            </td>
            <td colspan="4" class="pull-right">
                For {{$company_data['company_name']}}
            </td>
        </tr>
        <tr><td colspan="8"><br/></td></tr>
        <tr>
            <td colspan="8" class="text-2" align="left">
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

