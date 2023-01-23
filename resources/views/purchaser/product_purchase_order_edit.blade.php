@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'Edit Purchase Order')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Purchase Order'); ?>
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                    
                    <div class="form-group col-md-2">
                        <label for="Season">Order No</label>						
                        {{$purchase_order_data->order_no}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Product">Vendor</label>						
                        {{$purchase_order_data->vendor_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Delivery Date</label>						
                        @if(!empty($purchase_order_data->delivery_date)) {{date('d-m-Y',strtotime($purchase_order_data->delivery_date))}}  @endif  
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d-m-Y',strtotime($purchase_order_data->created_at))}}
                        
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$purchase_order_data->user_name}}    
                    </div>
                    
                </div>
            </form> 
            <hr/>
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger" style="display:none;"></div>
            
            <div class="clear">&nbsp;</div>
            <h6>Products List</h6>
            <div id="productsContainer">
                <div class="table-responsive table-filter" style="font-size:12px; ">
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0">
                        <thead>
                            <tr class="header-tr">
                                <th>SNo.</th><th>Style</th><th>Color</th>
                                @for($i=0;$i<count($size_list);$i++)
                                    <th colspan="2" class="pull-center">{{$size_list[$i]['size']}}</th>
                                @endfor
                                <th>Rate</th><th colspan="2" class="pull-center">Total Qty</th><th>Amount</th>
                                <th>Action</th>
                            </tr>
                            <tr style="background-color: #fff;">
                                <th colspan="3"></th>
                                @for($i=0;$i<count($size_list);$i++)
                                    <th>Ord.</th>
                                    <th>Rec.</th>
                                @endfor
                                <th></th>
                                <th>Ord.</th>
                                <th>Rec.</th>
                                <th></th>
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
                                    <td>{{$purchase_orders_items[$i]->color_name}}</td>
                                    @for($q=0;$q<count($size_list);$q++)
                                        <?php $size_id = $size_list[$q]['id']; ?>
                                        <td><?php if(isset($size_data[$size_id])) echo $size_data[$size_id];  ?></td>
                                        <td><?php if(isset($size_data_rec[$size_id])) echo $size_data_rec[$size_id];  ?></td>
                                        <?php $size_qty = (isset($size_data[$size_id]))?$size_data[$size_id]:0; ?>
                                        <?php if(isset($total_size[$size_id])) $total_size[$size_id]+=$size_qty; else $total_size[$size_id] = $size_qty; ?>
                                        <?php $size_qty_rec = (isset($size_data_rec[$size_id]))?$size_data_rec[$size_id]:0; ?>
                                        <?php if(isset($total_size_rec[$size_id])) $total_size_rec[$size_id]+=$size_qty_rec; else $total_size_rec[$size_id] = $size_qty_rec; ?>
                                    @endfor    
                                    <td>{{$purchase_orders_items[$i]->rate}}</td>
                                    <td>{{$purchase_orders_items[$i]->qty_ordered}}</td>
                                    <td>{{$purchase_orders_items[$i]->qty_received}}</td>
                                    <td>{{$purchase_orders_items[$i]->cost}}</td>
                                    <td>
                                        <a href="javascript:;" class="edit-link" onclick="editPurchaseOrder({{$purchase_orders_items[$i]->order_id}},{{$purchase_orders_items[$i]->id}});"><i title="Edit PO Items Quantity" class="fas fa-edit"></i> </a> &nbsp;
                                        <a href="javascript:;" class="edit-link" onclick="editPurchaseOrderItemRate({{$purchase_orders_items[$i]->order_id}},{{$purchase_orders_items[$i]->id}});"><i title="Edit PO Item Rate" class="fas fa-edit"></i> </a>
                                    </td>
                                </tr>
                                <?php $total_amount+=($purchase_orders_items[$i]->cost); ?>
                                <?php $total_gst+=($purchase_orders_items[$i]->gst_amount); ?>
                                <?php $total_qty+=($purchase_orders_items[$i]->qty_ordered); ?>
                                <?php $total_qty_rec+=($purchase_orders_items[$i]->qty_received); ?>
                                
                            @endfor
                            <tr>
                                <th colspan="3">Total</th>
                                @for($q=0;$q<count($size_list);$q++)
                                    <th>{{$total_size[$size_list[$q]['id']]}}</th>
                                    <th>{{$total_size_rec[$size_list[$q]['id']]}}</th>
                                @endfor        
                                <th></th>
                                <th>{{$total_qty}}</th>
                                <th>{{$total_qty_rec}}</th>
                                <th>{{$currency}} {{round($total_amount,2)}}</th>
                                <th></th>
                            </tr>
                            @for($i=0;$i<count($gst_type_percent);$i++)
                                <tr>
                                    <td colspan="{{(count($size_list)*2)+6}}">{{$gst_type_percent[$i]['gst_name']}}</td>
                                    <td>{{$currency}} {{round($total_gst*($gst_type_percent[$i]['gst_percent']/100),2)}}</td>
                                    <td></td>
                                </tr>
                            @endfor
                            <tr>
                                <td colspan="{{(count($size_list)*2)+6}}">Other Cost</td>
                                <td>{{$currency}} {{(!empty($purchase_order_data->other_cost))?$purchase_order_data->other_cost:0}}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <th colspan="{{(count($size_list)*2)+6}}">Total Cost</th>
                                <th>{{$currency}} {{round($total_amount+$purchase_order_data->other_cost+$total_gst,2)}}</th>
                                <th></th>
                            </tr>
                        </tbody>
                    </table>
                    @if(!empty($purchase_order_data->other_comments))
                        <div class="col-md-12" style="text-align: right;">Other Comments:  {{$purchase_order_data->other_comments}}</div>
                    @endif
                </div>
                
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="edit_po_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Purchase Order Items Quantity</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editPoErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="editPoSuccessMessage"></div>
                <div class="modal-body">
                    <form method="post" name="editPoForm" id="editPoForm">
                        <div id="edit_po_data"></div>
                        <input type="hidden" name="po_item_id" id="po_item_id" value="">
                    </form>
                </div>
                <div class="modal-footer center-footer">
                    <div id="edit-po-spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="edit_po_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="edit_po_btn" name="edit_po_btn" onclick="submitEditPurchaseOrder({{$purchase_order_data->id}});">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_po_rate_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Purchase Order Item Rate</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editPoItemRateErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="editPoItemRateoSuccessMessage"></div>
                <div class="modal-body">
                    <form method="post" name="editPoItemRateForm" id="editPoItemRateForm">
                        <div class="form-group" >
                            <label>Item SKU</label>
                            <input id="item_sku_edit" type="text" class="form-control" name="item_sku_edit" value="" readonly="true" >
                        </div>
                        <div class="form-group" >
                            <label>Rate</label>
                            <input id="item_rate_edit" type="text" class="form-control" name="item_rate_edit" value="" >
                            <div class="invalid-feedback" id="error_validation_item_rate_edit"></div>	
                        </div>
                        <input type="hidden" name="po_item_id_1" id="po_item_id_1" value="">
                    </form>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="edit_po_item_rate_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="edit_po_item_rate_btn" name="edit_po_item_rate_btn" onclick="submitEditPurchaseOrderItemRate({{$purchase_order_data->id}});">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.1') }}" ></script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
