@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'SOR Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'SOR Purchase Order Invoice Debit Note')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'SOR Purchase Order Invoice Debit Note'); ?>
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <hr/>
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger elem-hidden"></div>
            <input type="hidden" name="po_id" id="po_id" value="{{$purchase_order_data->id}}">
            <input type="hidden" name="po_detail_id" id="po_detail_id" value="{{$purchase_order_data->po_detail_id}}">
            
            <h6>Products List</h6>
            <div id="productsContainer">
                <form method="post" name="addDebitNoteform" id="addDebitNoteform">
                <div class="table-responsive table-filter" style="font-size:12px; ">
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0">
                        <thead>
                            <tr class="header-tr">
                                <th>SNo.</th><th>Style</th>
                                @for($i=0;$i<count($size_list);$i++)
                                    <th colspan="2" class="pull-center">{{$size_list[$i]['size']}}</th>
                                @endfor
                                <th>Rate</th><th colspan="2" class="pull-center">Total Qty</th><th>Amount</th>
                            </tr>
                            <tr>
                                <th colspan="2"></th>
                                @for($i=0;$i<count($size_list);$i++)
                                    <th>Ord.</th>
                                    <th>Rec.</th>
                                @endfor
                                <th></th>
                                <th>Ord.</th>
                                <th>Rec.</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total_amount = $total_qty = $total_qty_rec = $total_gst = 0; $total_size = $total_size_rec =  array(); ?>
                            @for($i=0;$i<count($purchase_orders_items);$i++)
                                <?php $size_data = json_decode($purchase_orders_items[$i]->size_data,true); ?>
                                <?php $size_data_rec = json_decode($purchase_orders_items[$i]->size_data_received,true); ?>
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$purchase_orders_items[$i]->vendor_sku}}</td>
                                    
                                    @for($q=0;$q<count($size_list);$q++)
                                        <?php $size_id = $size_list[$q]['id']; ?>
                                        <td><?php if(isset($size_data[$size_id])) echo $size_data[$size_id];  ?></td>
                                        <td><?php if(isset($size_data_rec[$size_id])) echo $size_data_rec[$size_id];  ?></td>
                                        <?php $size_qty = (isset($size_data[$size_id]))?$size_data[$size_id]:0; ?>
                                        <?php if(isset($total_size[$size_id])) $total_size[$size_id]+=$size_qty; else $total_size[$size_id] = $size_qty; ?>
                                        <?php $size_qty_rec = (isset($size_data_rec[$size_id]))?$size_data_rec[$size_id]:0; ?>
                                        <?php if(isset($total_size_rec[$size_id])) $total_size_rec[$size_id]+=$size_qty_rec; else $total_size_rec[$size_id] = $size_qty_rec; ?>
                                        <?php $size_list[$q]['size_qty'] = $size_qty; ?>
                                        <?php $size_list[$q]['size_qty_rec'] = $size_qty_rec; ?>
                                    @endfor    
                                    <td>{{$purchase_orders_items[$i]->rate}}</td>
                                    <td>{{$purchase_orders_items[$i]->qty_ordered}}</td>
                                    <td>{{$purchase_orders_items[$i]->qty_received}}</td>
                                    <td>{{$purchase_orders_items[$i]->cost}}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">Debit Note Quantity</td>
                                    @for($q=0;$q<count($size_list);$q++)
                                        <?php $size_id = $size_list[$q]['id']; ?>
                                        <?php $readonly = ($size_list[$q]['size_qty'] == $size_list[$q]['size_qty_rec'])?'readonly':''; ?>
                                        <?php $name = str_replace(' ','_',$purchase_orders_items[$i]->product_sku).'__'.$size_list[$q]['id']; ?>
                                        <?php $size_qty = (isset($size_data[$size_id]))?$size_data[$size_id]:0; ?>
                                         <?php $size_qty_rec = (isset($size_data_rec[$size_id]))?$size_data_rec[$size_id]:0; ?>
                                        <td colspan="2"> <input type="text" class="form-control debit-note-val" style="width:50px;margin-left: 10%;height:20px;" <?php echo $readonly; ?> name="{{$name}}" value="" onkeyup="updateDebitNoteTotalValue(this,'{{$size_qty}}','{{$size_qty_rec}}');"></td>
                                    @endfor     
                                    <td></td>
                                    <td colspan="2" align="center"><input type="text" class="form-control debit-note-val-row-total" style="width:50px;height:20px;" readonly="true" name="debit_note_val_row_total" value=""></td>
                                    <td></td>
                                </tr>
                                <?php $total_amount+=($purchase_orders_items[$i]->cost); ?>
                                <?php $total_gst+=($purchase_orders_items[$i]->gst_amount); ?>
                                <?php $total_qty+=($purchase_orders_items[$i]->qty_ordered); ?>
                                <?php $total_qty_rec+=($purchase_orders_items[$i]->qty_received); ?>
                                
                            @endfor
                            <tr><td colspan="20"></td></tr>
                            <tr>
                                <th colspan="2">Total</th>
                                @for($q=0;$q<count($size_list);$q++)
                                    <th></th>
                                    <th></th>
                                @endfor        
                                <th></th>
                                <th colspan="2" align="center" style="text-align: center;"><input type="text" class="form-control debit-note-val-total" style="width:50px;height:20px;display:inline;" readonly="true" name="debit_note_val_total" value=""> </th>
                                <th></th>
                            </tr>
                            
                            <?php /* ?>
                            <tr>
                                <th colspan="2">Total</th>
                                @for($q=0;$q<count($size_list);$q++)
                                    <th>{{$total_size[$size_list[$q]['id']]}}</th>
                                    <th>{{$total_size_rec[$size_list[$q]['id']]}}</th>
                                @endfor        
                                <th></th>
                                <th>{{$total_qty}}</th>
                                <th>{{$total_qty_rec}}</th>
                                <th>{{$currency}} {{round($total_amount,2)}}</th>
                            </tr>
                            @for($i=0;$i<count($gst_type_percent);$i++)
                                <tr>
                                    <td colspan="{{(count($size_list)*2)+5}}">{{$gst_type_percent[$i]['gst_name']}}</td>
                                    <td>{{$currency}} {{round($total_gst*($gst_type_percent[$i]['gst_percent']/100),2)}}</td>
                                </tr>
                            @endfor
                            <tr>
                                <td colspan="{{(count($size_list)*2)+5}}">Other Cost</td>
                                <td>{{$currency}} {{(!empty($purchase_order_data->other_cost))?$purchase_order_data->other_cost:0}}</td>
                            </tr>
                            <tr>
                                <th colspan="{{(count($size_list)*2)+5}}">Total Cost</th>
                                <th>{{$currency}} {{round($total_amount+$purchase_order_data->other_cost+$total_gst,2)}}</th>
                            </tr>
                            <?php */ ?>
                        </tbody>
                    </table>
                    
                    <div id="addDebitNoteSuccessMessage" class="alert alert-success elem-hidden"></div>
                    <div id="addDebitNoteErrorMessage" class="alert alert-danger elem-hidden"></div>
                    
                    <div class="row" style="margin-left:2px;margin-right: 2px;">
                        <div class="col-md-4">
                            <input type="text" name="comments" id="comments" class="form-control" placeholder="Remarks" maxlength="250">
                        </div>
                    </div>
                    
                    <div class="separator-10" ><br/></div>
                    <button type="button" id="add_debit_note_submit" class="btn btn-dialog" onclick="addPOInvoiceDebitNote();">Submit</button> &nbsp;
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="add_debit_note_cancel" onclick="window.location.href='{{url('warehouse/sor/inventory/import/'.$purchase_order_data->po_detail_id)}}'">Cancel</button>
                    <br/><br/>       
                </div>
                </form>    
            </div>
        </div>
    </section>

    <div class="modal fade" id="inventory_error_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="modal-body">
                    <h6 id="inv_error_text"></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="inventory_error_cancel">Close</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=1.98') }}" ></script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
