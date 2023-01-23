@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Accessories Purchase Orders','link'=>'purchase-order/accessories/list'),array('name'=>'Accessories Purchase Order Invoice List','link'=>'purchase-order/accessories/invoice/list/'.$po_detail_data->po_id),array('name'=>'PO Import Items')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'PO Import Items ('.$po_data->order_no.')'); ?>
    
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
                <h6>Items List</h6>
                <form class="" name="importAccessoriesPOItemsFrm" id="importAccessoriesPOItemsFrm" method="POST" >
                    <div id="productsContainer">
                        <div class="table-responsive table-filter" style="font-size:12px; ">
                            <table class="table table-striped admin-table " cellspacing="0">
                                <thead>
                                    <tr class="header-tr">
                                        <th>SNo.</th><th>Item</th>
                                        @for($i=0;$i<count($size_list);$i++)
                                            <th>{{$size_list[$i]['size']}}</th>
                                        @endfor
                                        <th>Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $total =  array(); ?>
                                    @for($i=0;$i<count($purchase_orders_items);$i++)
                                        <?php $size_ordered = json_decode($purchase_orders_items[$i]->size_data,true); ?>
                                        <?php $size_received = json_decode($purchase_orders_items[$i]->size_data_received,true); ?>
                                        <tr>
                                            <td>{{$i+1}}</td>
                                            <td>{{$purchase_orders_items[$i]->accessory_name}}</td>
                                            @for($q=0;$q<count($size_list);$q++)
                                                <?php $size_id = $size_list[$q]['id']; ?>
                                                <?php $key = $purchase_orders_items[$i]->id.'_'.$size_list[$q]['id'];  ?>
                                                <td> 
                                                    <input type="text" name="qty_{{$key}}" id="qty_{{$key}}" value="" class="form-control" style="width:75px;border:1px solid #ccc;">
                                                    Ord: {{$size_ordered[$size_id]}} Rec: {{isset($size_received[$size_id])?$size_received[$size_id]:0}}
                                                </td>
                                            @endfor
                                            <td>{{$purchase_orders_items[$i]->rate}}</td>
                                        </tr>
                                       
                                    @endfor
                                    <tr>
                                        <th colspan="{{count($size_list)+2}}">Total</th>
                                    </tr>
                                    
                                </tbody>
                            </table>
                            @if(!empty($purchase_order_data->other_comments))
                                <div class="col-md-12" style="text-align: right;">Other Comments:  {{$po_data->other_comments}}</div>
                            @endif

                            <button type="button" id ="pos_add_inventory_grn_submit" name="pos_add_inventory_grn_submit" class="btn btn-dialog" value="Submit" onclick="displayAddAccessoriesPOGRN();">Add GRN</button>&nbsp;&nbsp;
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
                                <td><?php $count_data = json_decode($grn_data->other_data,true); echo $count_data['total']; ?> </td>
                                <td>{{$grn_data->comments}}</td>
                                <td>{{date('d-m-Y H:i:s',strtotime($grn_data->created_at))}}</td>
                                <td>
                                    <a href="{{url('purchase-order/accessories/items/import/invoice/'.$grn_data->id)}}" ><i title="Download GRN PDF Invoice" class="fas fa-download"></i></a>
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

                <?php $total =  array('qty'=>0,'amt'=>0); ?>
               <div class="table-responsive table-filter" style="font-size:12px; ">
                    <table class="table table-striped admin-table " cellspacing="0">
                        <thead>
                            <tr class="header-tr">
                                <th>SNo.</th><th>Item</th>
                                @for($i=0;$i<count($size_list);$i++)
                                    <th>{{$size_list[$i]['size']}}</th>
                                    <?php  $total[$size_list[$i]['id']] = 0; ?>
                                @endfor
                                <th>Rate</th>
                                <th>Total Qty</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            <?php $grn_items = json_decode($grn_data->grn_items,true) ?>
                            @for($i=0;$i<count($purchase_orders_items);$i++)
                                <?php $item_id = $purchase_orders_items[$i]->id; ?>
                                @if(isset($grn_items[$item_id]))
                                    <?php $size_received = $grn_items[$item_id]; ?>
                                    <?php $total_rec = 0; ?>
                                    <tr>
                                        <td>{{$i+1}}</td>
                                        <td>{{$purchase_orders_items[$i]->accessory_name}}</td>
                                        @for($q=0;$q<count($size_list);$q++)
                                            <?php $size_id = $size_list[$q]['id']; ?>
                                            <td> {{$rec_qty = isset($size_received[$size_id])?$size_received[$size_id]:0}}</td>
                                            <?php $total_rec+=$rec_qty; ?>
                                            <?php $total[$size_id]+=$rec_qty; ?>
                                        @endfor
                                        <td>{{$purchase_orders_items[$i]->rate}}</td>
                                        <td>{{$total_rec}}</td>
                                        <?php $amount = $total_rec*$purchase_orders_items[$i]->rate; ?>
                                        <td>{{round($amount,2)}}</td>
                                    </tr>
                                    <?php $total['qty']+=$total_rec; ?>
                                    <?php $total['amt']+=$amount; ?>
                                @endif
                            @endfor
                            
                        <tr>
                            <th colspan="2">Total</th>
                            @for($q=0;$q<count($size_list);$q++)
                                <th>{{$total[$size_list[$q]['id']]}}</th>
                            @endfor    
                            <th></th>
                            <th>{{$total['qty']}}</th>
                            <th>{{$total['amt']}}</th>
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
                        <button type="button" id ="add_inventory_grn_submit" name="add_inventory_grn_submit" class="btn btn-dialog" onclick="submitAddAccessoriesPOGRN();">Submit</button>
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
