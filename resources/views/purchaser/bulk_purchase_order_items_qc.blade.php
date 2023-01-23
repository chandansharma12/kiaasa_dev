@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Purchase Orders','link'=>'purchase-order/bulk/list'),array('name'=>'Purchase Order Invoice List','link'=>'purchase-order/bulk/invoice/list/'.$po_detail_data->po_id),array('name'=>'PO Products QC')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'PO Products QC('.$po_data->order_no.')'); ?>
    
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
                <h6 class="alert alert-danger">GRN not added for invoice</h6>
            @endif

            @if(!empty($grn_data) && empty($qc_data))
                <form class="" name="qcBulkPOItemsFrm" id="qcBulkPOItemsFrm" method="POST" >
                    <div class="table-responsive table-filter" style="font-size:12px; ">
                        <table class="table table-striped admin-table " cellspacing="0">
                            <thead>
                                <tr class="header-tr">
                                    <th>SNo.</th><th>Style</th><th>Fabric</th><th>Width</th><th>Content</th><th>GSM</th>
                                    <th>Color</th><th>Rate</th><th>Invoice Qty</th><th>Accepted</th><th>Defective</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_qty_rec = 0; ?>
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
                                            <td><input type="text" name="qc_acc_{{$purchase_orders_items[$i]->id}}" id="qc_acc_{{$purchase_orders_items[$i]->id}}" value="" class="form-control" style="width:75px;border:1px solid #ccc;" onkeyup="updateBulkQCDefectiveItem({{$qty}},{{$purchase_orders_items[$i]->id}});">  </td>
                                            <td><input type="text" name="qc_def_{{$purchase_orders_items[$i]->id}}" id="qc_def_{{$purchase_orders_items[$i]->id}}" value="" class="form-control" style="width:75px;" readonly="true"></td>
                                        </tr>
                                        <?php $total_qty_rec+=$qty; ?>
                                    @endif
                                @endfor
                                <tr>
                                    <th colspan="8">Total</th>
                                    <th>{{$total_qty_rec}} {{$purchase_orders_items[0]->unit_code}}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tbody>
                        </table>
                       <button type="button" id="pos_add_inventory_qc_submit" name="pos_add_inventory_qc_submit" class="btn btn-dialog" value="Submit" onclick="displayAddBulkPOQC();">Complete QC</button>&nbsp;&nbsp;
                       <button type="button" id="pos_add_inventory_qc_cancel" name="pos_add_inventory_qc_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                    </div>
                </form>    

                <input type="hidden" name="grn_id" id="grn_id" value="{{$grn_data->id}}">
            @endif
            <br/>
            
            @if(!empty($qc_data))
                <h6>QC Products:</h6>
                <div class="table-responsive table-filter" style="font-size:12px; ">
                    <table class="table table-striped admin-table " cellspacing="0">
                        <thead>
                            <tr class="header-tr">
                                <th>SNo.</th><th>Style</th><th>Fabric</th><th>Width</th><th>Content</th><th>GSM</th>
                                <th>Color</th><th>Rate</th><th>Invoice Qty</th><th>Accepted</th><th>Defective</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total_qty_rec = $total_qty_acc = $total_qty_def = 0;  ?>
                            <?php $qc_items = json_decode($qc_data->other_data,true) ?>
                            <?php $invoice_items = json_decode($po_detail_data->invoice_items,true) ?>
                            @for($i=0;$i<count($purchase_orders_items);$i++)
                                <?php $id = $purchase_orders_items[$i]->id;  ?>
                                @if(isset($qc_items[$id]) && !empty($qc_items[$id]))
                                    <tr>
                                        <td>{{$i+1}}</td>
                                        <td>{{$purchase_orders_items[$i]->sku}}</td>
                                        <td>{{$purchase_orders_items[$i]->fabric_name}}</td>
                                        <td>{{$purchase_orders_items[$i]->width_name}}</td>
                                        <td>{{$purchase_orders_items[$i]->content_name}}</td>
                                        <td>{{$purchase_orders_items[$i]->gsm_name}}</td>
                                        <td>{{$purchase_orders_items[$i]->color_name}}</td>
                                        <td>{{$rate = $purchase_orders_items[$i]->rate}}</td>
                                        <td>{{$invoice_qty = $invoice_items[$id]}}  {{$purchase_orders_items[$i]->unit_code}}</td>
                                        <td>{{$acc_qty = $qc_items[$id]}}  {{$purchase_orders_items[$i]->unit_code}}</td>
                                        <td>{{$def_qty = $invoice_qty-$acc_qty}} {{$purchase_orders_items[$i]->unit_code}}</td>
                                    </tr>
                                    <?php $total_qty_rec+=$invoice_qty; ?>
                                    <?php $total_qty_acc+=$acc_qty; ?>
                                    <?php $total_qty_def+=$def_qty; ?>
                                @endif
                            @endfor
                            <tr>
                                <th colspan="8">Total</th>
                                <th>{{$total_qty_rec}} {{$purchase_orders_items[0]->unit_code}}</th>
                                <th>{{$total_qty_acc}} {{$purchase_orders_items[0]->unit_code}}</th>
                                <th>{{$def_qty}} {{$purchase_orders_items[0]->unit_code}}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <input type="hidden" name="grn_id" id="grn_id" value="{{$grn_data->id}}">
                
                <hr>
                <div class="separator-10"></div>
                <h6>QC Data:</h6>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr"><th>Total</th><th>Accepted</th><th>Defective</th><th>Returned</th><th>Comments</th><th>Created On</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php $grn_items = json_decode($grn_data->other_data,true);
                            $qc_items = json_decode($qc_data->other_data,true); ?>
                            <tr>
                                <td>{{$total = array_sum($grn_items)}}</td>
                                <td>{{$acc = array_sum($qc_items)}}</td>
                                <td>{{$total-$acc}}</td>
                                <td>{{$qc_data['defective_returned']}}</td>
                                <td>{{$qc_data['comments']}}</td>
                                <td>{{date('d M Y H:i',strtotime($qc_data['created_at']))}}</td>
                                <td>
                                    @if($qc_data['return_to_vendor'] == 1)
                                        <input type="button" name="return_inv_btn" id="return_inv_btn" value="Return" class="btn btn-dialog" onclick="returnBulkInventoryToVendor();">
                                    @endif

                                    @if(!empty($qc_return_data))
                                        <a href="{{url('purchase-order/bulk/items/qc-return/invoice/'.$qc_return_data->id)}}/1" ><i title="Debit Note Invoice" class="fas fa-download"></i></a>&nbsp;
                                        <a href="{{url('purchase-order/bulk/items/qc-return/invoice/'.$qc_return_data->id)}}/2" ><i title="Credit Note Invoice" class="fas fa-download"></i></a>&nbsp;
                                        @if($user->user_type == 6)
                                            <a href="{{url('purchase-order/bulk/items/qc-return/gatepass/'.$qc_return_data->id)}}" ><i title="Gate Pass" class="fas fa-download"></i></a>&nbsp;
                                            <a href="javascript:;" onclick="editReturnBulkPOItemsGatePassData();" ><i title="Edit Gate Pass Data" class="fas fa-edit"></i></a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
                    
        </div>
    </section>
    
    <div class="modal fade data-modal" id="add_inventory_qc_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Complete QC</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addInventoryQCSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addInventoryQCErrorMessage"></div>

                <form class="" name="addInventoryQCForm" id="addInventoryQCForm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Comments</label>
                                <input id="add_inventory_qc_comments" type="text" class="form-control" name="add_inventory_qc_comments" value="" maxlength="250">
                                <div class="invalid-feedback" id="error_validation_add_inventory_qc_comments"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add_inventory_qc_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="add_inventory_qc_cancel" name="add_inventory_qc_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="add_inventory_qc_submit" name="add_inventory_qc_submit" class="btn btn-dialog" onclick="submitAddBulkPOQC();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="returnInventoryToVendorDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Return Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="returnInventoryToVendorSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="returnInventoryToVendorErrorMessage"></div>

                <form class="" name="returnInventoryToVendorForm" id="returnInventoryToVendorFormForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>No of Boxes</label>
                                <input id="boxes_count" type="text" class="form-control" name="boxes_count" value="" >
                                <div class="invalid-feedback" id="error_validation_boxes_count"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Transporter Name</label>
                                <input id="transporter_name" type="text" class="form-control" name="transporter_name" value="" >
                                <div class="invalid-feedback" id="error_validation_transporter_name"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Transporter GST</label>
                                <input id="transporter_gst" type="text" class="form-control" name="transporter_gst" value="" >
                                <div class="invalid-feedback" id="error_validation_transporter_gst"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Docket No</label>
                                <input id="docket_no" type="text" class="form-control" name="docket_no" value="" >
                                <div class="invalid-feedback" id="error_validation_docket_no"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Eway Bill No</label>
                                <input id="eway_bill_no" type="text" class="form-control" name="eway_bill_no" value="" >
                                <div class="invalid-feedback" id="error_validation_eway_bill_no"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="returnInventoryToVendorSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="returnInventoryToVendorCancel" name="returnInventoryToVendorCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="returnInventoryToVendorCancelSubmit" name="returnInventoryToVendorCancelSubmit" class="btn btn-dialog" onclick="submitReturnBulkInventoryToVendor();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(!empty($qc_return_gate_pass_data))
        <div class="modal fade data-modal" id="editGatePassDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLongTitle">Close Demand</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                    </div>

                    <div class="alert alert-success alert-dismissible" style="display:none" id="editGatePassSuccessMessage"></div>
                    <div class="alert alert-danger alert-dismissible" style="display:none" id="editGatePassErrorMessage"></div>

                    <form class="" name="editGatePassForm" id="editGatePassForm" type="POST" >
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>No of Boxes</label>
                                    <input id="boxes_count" type="text" class="form-control" name="boxes_count" value="{{$qc_return_gate_pass_data->boxes_count}}" >
                                    <div class="invalid-feedback" id="error_validation_boxes_count"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Transporter Name</label>
                                    <input id="transporter_name" type="text" class="form-control" name="transporter_name" value="{{$qc_return_gate_pass_data->transporter_name}}">
                                    <div class="invalid-feedback" id="error_validation_transporter_name"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Transporter GST</label>
                                    <input id="transporter_gst" type="text" class="form-control" name="transporter_gst" value="{{$qc_return_gate_pass_data->transporter_gst}}">
                                    <div class="invalid-feedback" id="error_validation_transporter_gst"></div>
                                </div>
                                 <div class="form-group col-md-3" >
                                    <label>Docket No</label>
                                    <input id="docket_no" type="text" class="form-control" name="docket_no" value="{{$qc_return_gate_pass_data->docket_no}}" >
                                    <input type="hidden" name="qc_return_gate_pass_id" id="qc_return_gate_pass_id" value="{{$qc_return_gate_pass_data->id}}">
                                    <div class="invalid-feedback" id="error_validation_docket_no"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Eway Bill No</label>
                                    <input id="eway_bill_no" type="text" class="form-control" name="eway_bill_no" value="{{$qc_return_gate_pass_data->eway_bill_no}}" >
                                    <div class="invalid-feedback" id="error_validation_eway_bill_no"></div>
                                </div>
                            </div>    
                        </div>
                        <div class="modal-footer center-footer">
                            <div id="editGatePassFormSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id="editGatePassCancel" name="editGatePassCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" id ="editGatePassSubmit" name="editGatePassSubmit" class="btn btn-dialog" onclick="submitEditReturnBulkPOItemsGatePassData();">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.58') }}" ></script>
<script type="text/javascript">
    @if(empty($grn_data)) var page_type = 'edit'; @else var page_type = 'detail'; @endif 
    
</script>
@endsection
