@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard'),array('name'=>'Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'Purchase Order Invoice List','link'=>'purchase-order/product/invoice/list/'.$po_data->id),array('name'=>'Create SOR Purchase Order Excess Amount Invoice Debit Note ')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Create SOR Purchase Order Excess Amount Invoice Debit Note' ); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
               
            <form class="" name="addDebitNoteFrm" id="addDebitNoteFrm" method="POST" >

               <div class="separator-10"></div>

                    <hr><h6>GRN </h6>
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>GRN ID</th><th>GRN No</th><th>Invoice No</th><th>PO No</th><th>Inventory Count</th><th>Comments</th><th>Added On</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>{{$grn_data->id}}</td>
                                    <td>{{$grn_data->grn_no}}</td>
                                    <td>{{$po_data->invoice_no}}</td>
                                    <td>{{$po_data->order_no}}</td>
                                    <td><?php $count_data = json_decode($grn_data->other_data,true) ?> {{$count_data['total']}}</td>
                                    <td>{{$grn_data->comments}}</td>
                                    <td>{{date('d-m-Y H:i:s',strtotime($grn_data->created_at))}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-danger alert-dismissible elem-hidden" id="debitNoteItemAddErrorMessage"></div>
                    <div class="alert alert-success alert-dismissible elem-hidden" id="debitNoteItemAddSuccessMessage"></div>
                                        
                    <div class="table-responsive table-filter" style="font-size:12px; ">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead>
                                <tr class="header-tr">
                                    <th>SNo.</th><th>Style</th><th>Color</th>
                                    <th>Rate</th><th>GST</th><th>Cost</th><th >Total Qty</th><th>Amount</th>
                                    <th>Invoice Rate</th><th>Invoice GST</th><th>Invoice cost</th><th>Action</th><!--<th>Invoice Amount</th><th>Debit Note Amount</th>-->
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total = array('inv_count'=>0,'po_amount'=>0,'invoice_amount'=>0,'debit_note_amount'=>0);$ids = []; ?>
                                @for($i=0;$i<count($sku_list);$i++)
                                    <?php $sku_size_count = 0; ?>
                                    <?php $sku_data = isset($debit_note_sku_list[$sku_list[$i]->id])?$debit_note_sku_list[$sku_list[$i]->id]:array(); ?>
                                    <tr>
                                        <td>{{($i+1)}}</td>
                                        <td>{{($sku_list[$i]->vendor_sku)}}</td>
                                        <td>{{($sku_list[$i]->color_name)}}</td>
                                        <td>{{($sku_list[$i]->rate)}}</td>
                                        <td>{{$gst_amount = round($sku_list[$i]->rate*($sku_list[$i]->gst_percent/100),2)}}</td>
                                        <td>{{$cost = ($sku_list[$i]->rate+$gst_amount)}}</td>
                                        <td>{{$qty = $sku_list[$i]->inv_count}}</td>
                                        <td>{{$po_amount = round($cost*$sku_list[$i]->inv_count,2)}}</td>
                                        <td>
                                            <input type="text" class="numeric-text" name="invoice_rate_{{($sku_list[$i]->id)}}" id="invoice_rate_{{($sku_list[$i]->id)}}" value="">
                                            <input type="hidden" name="item_qty_{{($sku_list[$i]->id)}}" id="item_qty_{{($sku_list[$i]->id)}}" value="{{$sku_list[$i]->inv_count}}"> 
                                        </td>
                                        <td>
                                            <input type="text" class="numeric-text" name="invoice_gst_{{($sku_list[$i]->id)}}" id="invoice_gst_{{($sku_list[$i]->id)}}" value="">
                                        </td>
                                         <td>
                                            <input type="text" class="numeric-text" name="invoice_cost_{{($sku_list[$i]->id)}}" id="invoice_cost_{{($sku_list[$i]->id)}}" value="">
                                        </td>
                                        <td>
                                            <input type="button" class="btn btn-dialog" value="Add" onclick="addExcessAmountDebitNoteItem({{($sku_list[$i]->id)}},'{{($qty)}}');" id="item_add_btn" name="item_add_btn">
                                        </td>
                                        <!--<td><span id="invoice_amount_{{($sku_list[$i]->id)}}" class="invoice-amount">{{$invoice_amount = isset($sku_data['item_invoice_cost'])?round($sku_data['item_invoice_cost']*$sku_list[$i]->inv_count,2):''}}</span></td>
                                        <td><span id="debit_note_amount_{{($sku_list[$i]->id)}}" class="debit-note-amount">{{$debit_note_amount = !empty($invoice_amount)?round($invoice_amount-$po_amount,2):''}}</span></td>-->
                                    </tr>
                                    <?php $total['inv_count']+=$sku_list[$i]->inv_count; ?>
                                    <?php $total['po_amount']+=($cost*$sku_list[$i]->inv_count); ?>
                                    <?php $total['invoice_amount']+=(!empty($invoice_amount))?$invoice_amount:0; ?>
                                    <?php $total['debit_note_amount']+=(!empty($debit_note_amount))?$debit_note_amount:0; ?>
                                    <?php $ids[] = $sku_list[$i]->id; ?>
                                @endfor
                                <?php /* ?>
                                <tr>
                                    <td colspan="6"></td>
                                    <td>{{$total['inv_count']}}</td>
                                    <td>{{$total['po_amount']}}</td>
                                    <td></td>
                                    <td></td>
                                    <!--<td id="invoice_total_amount">{{$total['invoice_amount']}}</td>
                                    <td id="debit_note_total_amount">{{round($total['debit_note_amount'],2)}}</td>-->
                                    
                                </tr>
                                <?php */ ?>
                            </tbody>
                            <?php /* ?><tfoot>
                                <tr>
                                    <td colspan="12" align="center">
                                        <div class="alert alert-danger alert-dismissible elem-hidden" id="debitNoteErrorMessage"></div>
                                        <div class="alert alert-success alert-dismissible elem-hidden" id="debitNoteSuccessMessage"></div>
                                        <button type="button" id="add_excess_amount_debit_note_submit" class="btn btn-dialog" onclick="addPOInvoiceExcessAmountDebitNote();">Submit</button> &nbsp;
                                        <button type="button" data-dismiss="modal" class="btn btn-secondary" id="add_excess_amount_debit_note_cancel" onclick="window.location.href='{{url('warehouse/sor/inventory/import/'.$grn_data->po_detail_id)}}'">Cancel</button>
                                    </td>
                                </tr>
                            </tfoot> <?php */ ?>
                        </table>
                       <input type="hidden" name="ids" id="ids" value="{{implode(',',$ids)}}"> 
                       <input type="hidden" name="invoice_id" id="invoice_id" value="{{$po_data->po_detail_id}}">
                       
                       
                    </div>
                    
                    <div id="debit_note_items_list"></div>

                @csrf
            </form>
           
        </div>
    </section>
    
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=1.48') }}" ></script>
<script type="text/javascript">
    
</script>
@endsection
