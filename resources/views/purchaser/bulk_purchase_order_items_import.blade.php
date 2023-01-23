@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Purchase Orders','link'=>'purchase-order/bulk/list'),array('name'=>'Purchase Order Invoice List','link'=>'purchase-order/bulk/invoice/list/'.$po_detail_data->po_id),array('name'=>'PO Import Products')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'PO Import Products ('.$po_data->order_no.')'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
               
           <form class="" name="importPosInventoryFrm" id="importPosInventoryFrm" method="POST" >
                <input type="hidden" name="po_id" id="po_id" value="{{$po_detail_data->po_id}}">
                <input type="hidden" name="po_detail_id" id="po_detail_id" value="{{$po_detail_data->id}}">
                @csrf
            </form>
            Invoice No: {{$po_detail_data->invoice_no}} | Invoice Date: {{date('d-m-Y',strtotime($po_detail_data->invoice_date))}}

            <div class="alert alert-danger alert-dismissible elem-hidden" id="inventoryErrorMessage"></div>
            <div class="separator-10"></div>

            @if(empty($grn_data))
                <h6>Products List</h6>
                <form class="" name="importBulkPOItemsFrm" id="importBulkPOItemsFrm" method="POST" >
                    <div id="productsContainer">
                        <div class="table-responsive table-filter" style="font-size:12px; ">
                            <table class="table table-striped admin-table " cellspacing="0">
                                <thead>
                                    <tr class="header-tr">
                                        <th>SNo.</th><th>Style</th><th>Fabric</th><th>Width</th><th>Content</th><th>GSM</th>
                                        <th>Color</th><th>Rate</th><th>Qty Ordered</th><!--<th>Amount</th>--><th>Qty Received</th><th>Qty Pending</th><th>Invoice Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $total_amount = $total_qty = $total_qty_rec = $total_gst = 0; $total_size = $total_size_rec =  array(); ?>
                                    @for($i=0;$i<count($purchase_orders_items);$i++)

                                        <tr>
                                            <td>{{$i+1}}</td>
                                            <td>{{$purchase_orders_items[$i]->sku}}</td>
                                            <td>{{$purchase_orders_items[$i]->fabric_name}}</td>
                                            <td>{{$purchase_orders_items[$i]->width_name}}</td>
                                            <td>{{$purchase_orders_items[$i]->content_name}}</td>
                                            <td>{{$purchase_orders_items[$i]->gsm_name}}</td>
                                            <td>{{$purchase_orders_items[$i]->color_name}}</td>
                                            <td>{{$purchase_orders_items[$i]->rate}}</td>
                                            <td>{{$purchase_orders_items[$i]->qty_ordered}} {{$purchase_orders_items[$i]->unit_code}}</td>
                                            <!--<td>{{$purchase_orders_items[$i]->cost}}</td>-->
                                            <td>{{$purchase_orders_items[$i]->qty_received}}</td>
                                            <td>{{$purchase_orders_items[$i]->qty_ordered-$purchase_orders_items[$i]->qty_received}}</td>
                                            <td><input type="text" name="qty_{{$purchase_orders_items[$i]->id}}" id="qty_{{$purchase_orders_items[$i]->id}}" value="" class="form-control" style="width:75px;border:1px solid #ccc;"></td>
                                        </tr>
                                        <?php $total_amount+=($purchase_orders_items[$i]->cost); ?>
                                        <?php $total_gst+=($purchase_orders_items[$i]->gst_amount); ?>
                                        <?php $total_qty+=($purchase_orders_items[$i]->qty_ordered); ?>
                                        <?php $total_qty_rec+=($purchase_orders_items[$i]->qty_received); ?>
                                    @endfor
                                    <tr>
                                        <th colspan="8">Total</th>
                                        <th>{{$total_qty}} {{$purchase_orders_items[0]->unit_code}}</th>
                                        <th>{{$total_qty_rec}}</th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                    
                                </tbody>
                            </table>
                            @if(!empty($purchase_order_data->other_comments))
                                <div class="col-md-12" style="text-align: right;">Other Comments:  {{$po_data->other_comments}}</div>
                            @endif

                            <button type="button" id ="pos_add_inventory_grn_submit" name="pos_add_inventory_grn_submit" class="btn btn-dialog" value="Submit" onclick="displayAddBulkPOGRN();">Add GRN</button>&nbsp;&nbsp;
                            <button type="button" id="pos_add_inventory_grn_cancel" name="pos_add_inventory_grn_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>

                        </div>

                        <hr/>
                    </div>
                </form>    
            @endif

            @if(!empty($grn_data))
                <hr><h6>GRN </h6>
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th>GRN ID</th><th>GRN No</th><th>Inventory Count</th><th>Comments</th><th>Added On</th><th>Action</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>{{$grn_data->id}}</td>
                                <td>{{$grn_data->grn_no}}</td>
                                <td><?php $count_data = json_decode($grn_data->other_data,true) ?> </td>
                                <td>{{$grn_data->comments}}</td>
                                <td>{{date('d-m-Y H:i:s',strtotime($grn_data->created_at))}}</td>
                                <td>
                                    <a href="{{url('purchase-order/bulk/items/import/invoice/'.$grn_data->id)}}" ><i title="Download GRN PDF Invoice" class="fas fa-download"></i></a>
                                    <?php /* ?>
                                    @if($po_detail_data->debit_note_added == 1)
                                        &nbsp;&nbsp;<a href="{{url('warehouse/sor/inventory/pending/invoice/'.$po_detail_data->id)}}" ><i title="Download Pending Inventory Debit Note Invoice" class="fas fa-download"></i></a>
                                        @if($user->user_type == 6)
                                            &nbsp;&nbsp;<a href="{{url('warehouse/sor/inventory/debit-note/add/'.$po_detail_data->id)}}" ><i title="Edit Pending Inventory Debit Note Invoice" class="fas fa-edit"></i></a>
                                        @endif
                                    @else
                                        @if($user->user_type == 6)
                                            &nbsp;&nbsp;<a href="{{url('warehouse/sor/inventory/debit-note/add/'.$po_detail_data->id)}}" ><i title="Add Pending Inventory Debit Note Invoice" class="fas fa-edit"></i></a>
                                        @endif
                                    @endif <?php */ ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

               <div class="table-responsive table-filter" style="font-size:12px; ">
                    <table class="table table-striped admin-table " cellspacing="0">
                        <thead>
                            <tr class="header-tr">
                                <th>SNo.</th><th>Style</th><th>Fabric</th><th>Width</th><th>Content</th><th>GSM</th>
                                <th>Color</th><th>Rate</th><!--<th>Qty Ordered</th><th>Amount</th><th>Qty Received</th><th>Qty Pending</th>--><th>Quantity</th>
                                <th>Tax Amount</th><th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total_amount = $total_qty = $total_qty_rec = $total_gst = 0; $total_size = $total_size_rec =  array(); ?>
                            <?php $invoice_items = json_decode($po_detail_data->invoice_items,true) ?>
                            @for($i=0;$i<count($purchase_orders_items);$i++)
                                <?php $id = $purchase_orders_items[$i]->id;  ?>
                                @if(isset($invoice_items[$id]) && !empty($invoice_items[$id]))
                                    <tr>
                                        <td>{{$i+1}}</td>
                                        <td>{{$purchase_orders_items[$i]->sku}}</td>
                                        <td>{{$purchase_orders_items[$i]->fabric_name}}</td>
                                        <td>{{$purchase_orders_items[$i]->width_name}}</td>
                                        <td>{{$purchase_orders_items[$i]->content_name}}</td>
                                        <td>{{$purchase_orders_items[$i]->gsm_name}}</td>
                                        <td>{{$purchase_orders_items[$i]->color_name}}</td>
                                        <td>{{$rate = $purchase_orders_items[$i]->rate}}</td>
                                        <td>{{$qty = $invoice_items[$id]}}  {{$purchase_orders_items[$i]->unit_code}}</td>
                                        <?php $gst_amt = ($qty*$rate)*($purchase_orders_items[$i]->gst_percent/100); ?>
                                        <td>{{round($gst_amt,2) }}</td>
                                        <td>{{$amount = ($qty*$rate)+$gst_amt}}</td>
                                    </tr>
                                    <?php $total_qty_rec+=$qty; ?>
                                    <?php $total_amount+=$amount; ?>
                                    <?php $total_gst+=$gst_amt; ?>
                                @endif
                            @endfor
                            <tr>
                                <th colspan="8">Total</th>
                                <th>{{$total_qty_rec}} {{$purchase_orders_items[0]->unit_code}}</th>
                                <th>{{round($total_gst,2)}}</th>
                                <th>{{$currency}} {{round($total_amount,2)}}</th>
                            </tr>
                            
                        </tbody>
                    </table>
                    @if(!empty($purchase_order_data->other_comments))
                        <div class="col-md-12" style="text-align: right;">Other Comments:  {{$po_data->other_comments}}</div>
                    @endif
                </div>

                <input type="hidden" name="grn_id" id="grn_id" value="{{$grn_data->id}}">
            @endif
            <br/>
                    
        </div>
    </section>
    
    <div class="modal fade data-modal" id="add_inventory_grn_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add GRN</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addInventoryGRNSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addInventoryGRNErrorMessage"></div>

                <form class="" name="addInventoryGRNForm" id="addInventoryGRNForm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Comments</label>
                                <input id="add_inventory_grn_comments" type="text" class="form-control" name="add_inventory_grn_comments" value="" maxlength="250">
                                <div class="invalid-feedback" id="error_validation_add_inventory_grn_comments"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add_inventory_grn_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="add_inventory_grn_cancel" name="add_inventory_grn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="add_inventory_grn_submit" name="add_inventory_grn_submit" class="btn btn-dialog" onclick="submitAddBulkPOGRN();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.48') }}" ></script>
<script type="text/javascript">
    @if(empty($grn_data)) var page_type = 'edit'; @else var page_type = 'detail'; @endif 
    //loadPOInventory(1);
</script>
@endsection
