@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'Purchase Order Invoice List','link'=>'purchase-order/product/invoice/list/'.$po_detail_data->po_id),array('name'=>'Purchase Order QC')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Purchase Order QC ('.$po_data->order_no.')'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <input type="hidden" name="po_id" id="po_id" value="{{$po_data->id}}">
            <div id="products_qc_div" >
                
                <form  name="qcPosInventoryFrm" id="qcPosInventoryFrm" method="POST">
                    Invoice No: {{$po_detail_data->invoice_no}}  | Invoice Date: {{date('d-m-Y',strtotime($po_detail_data->invoice_date))}} 
                    
                    @if(!empty($grn_data) && empty($qc_data))
                        | Total Inventory: {{$inventory_status_data['inventory_total']}} 
                        | Imported: {{$inventory_status_data['inventory_imported']}} | Not Imported: {{$inventory_status_data['inventory_import_pending']}}
                    @endif 
                    
                    <div class="separator-10"></div>
                    
                    @if(empty($grn_data))
                        <div class="alert alert-danger">GRN not created for Invoice</div>
                    @endif
                    
                    @if(!empty($grn_data) && empty($qc_data))
                        <div class="form-row ">
                            <div class="form-group col-md-2">
                                <label>Defective Piece Barcode</label>
                                <input type="text" name="piece_barcode_inv_qc" id="piece_barcode_inv_qc" class="form-control " autofocus="true">
                                <input type="hidden" name="piece_id" id="piece_id" value="">
                            </div>
                            <div class="form-group col-md-2">
                                <label>Product</label>
                                <input type="text" name="product_name" id="product_name" class="form-control import-data" readonly="true" >
                            </div>
                            <div class="form-group col-md-1">
                                <label>Size</label>
                                <input type="text" name="piece_size" id="piece_size" class="form-control import-data" readonly="true" >
                            </div>
                            <div class="form-group col-md-1">
                                <label>Color</label>
                                <input type="text" name="piece_color" id="piece_color" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-1">
                                <label>Vendor</label>
                                <input type="text" name="piece_vendor" id="piece_vendor" class="form-control import-data" readonly="true">
                            </div>
                             <div class="form-group col-md-1">
                                <label>PO Number</label>
                                <input type="text" name="piece_po_number" id="piece_po_number" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-1">
                                <label>SKU</label>
                                <input type="text" name="product_sku" id="product_sku" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-1">
                                <label>Cost</label>
                                <input type="text" name="piece_cost" id="piece_cost" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-1">
                                <label>QC Date</label>
                                <input type="text" name="qc_date" id="qc_date" class="form-control import-data" readonly="true" >
                            </div>
                            
                             <div class="form-group col-md-1">
                                <label>&nbsp;</label>
                                <!--<button type="button" id="pos_add_inventory_qc_submit" name="pos_add_inventory_qc_submit" class="btn btn-dialog" value="Submit" onclick="addInventoryProductQC();">Add</button>-->
                                <button type="button" id="upload_po_invoice_inv_link" name="upload_po_invoice_inv_link" onclick="importPOQCInventory();" class="btn btn-dialog" title="Import Defective Inventory"><i title="Import Defective Inventory" class="fas fa-upload fas-icon"></i></button>
                            </div>
                        </div>  
                        <div style="height:45px;">
                            <span id="qcPosInventoryErrorMessage" class="alert alert-danger elem-hidden product-added-span" ></span>
                            <span id="qcPosInventorySuccessMessage" class="alert alert-success elem-hidden product-added-span" ></span>
                        </div>  
                    
                        <div id="products_qc_list"></div>
                        
                        <div class="separator-10"></div>
                        <div class="form-row" >
                            <button type="button" id="delete_inventory_import_submit" name="delete_inventory_import_submit" class="btn btn-dialog" value="Submit" onclick="deleteInventoryQcItems();">Delete</button>&nbsp;&nbsp;
                        </div>
                        <div class="separator-10"></div>
                        
                        <div id="products_paging_links"></div>
                        <br/>
                    
                        <div class="form-row" >
                            <div id="add_pos_inventory_spinner " class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id="pos_qc_inventory_complete_submit" name="pos_qc_inventory_complete_submit" class="btn btn-dialog" value="Submit" onclick="displayConfirmQCInventory();">Complete QC</button>&nbsp;&nbsp;
                            <button type="button" id="pos_qc_inventory_complete_cancel" name="pos_qc_inventory_complete_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                        </div>
                        <br/>
                    @endif
                        
                    @csrf
                    
                    @if(!empty($qc_data))
                        <hr>
                        <div class="separator-10"></div>
                        <h6>QC Data:</h6>
                        <div class="table-responsive table-filter">
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead><tr class="header-tr"><th>Total Inventory</th><th>Accepted</th><th>Defective</th><th>Returned</th><th>Comments</th><th>Created On</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php $qc_data_1 = json_decode($qc_data['other_data'],true); ?>
                                    <tr>
                                        <td>{{$qc_data_1['total']}}</td>
                                        <td>{{$qc_data_1['accepted']}}</td>
                                        <td>{{$qc_data_1['defective']}}</td>
                                        <td>{{$qc_data['defective_returned']}}</td>
                                        <td>{{$qc_data['comments']}}</td>
                                        <td>{{date('d M Y H:i',strtotime($qc_data['created_at']))}}</td>
                                        <td>
                                            @if($qc_data['return_to_vendor'] == 1)
                                                <input type="button" name="return_inv_btn" id="return_inv_btn" value="Return" class="btn btn-dialog" onclick="returnInventoryToVendor();">
                                            @endif
                                        
                                            @if(!empty($qc_return_data))
                                                <a href="{{url('warehouse/sor/inventory/qc-return/invoice/'.$qc_return_data->id)}}/1" ><i title="Debit Note Invoice" class="fas fa-download"></i></a>&nbsp;
                                                <a href="{{url('warehouse/sor/inventory/qc-return/invoice/'.$qc_return_data->id)}}/2" ><i title="Credit Note Invoice" class="fas fa-download"></i></a>&nbsp;
                                                @if($user->user_type == 6)
                                                    <a href="{{url('warehouse/sor/inventory/qc-return/gatepass/'.$qc_return_data->id)}}" ><i title="Gate Pass" class="fas fa-download"></i></a>&nbsp;
                                                    <a href="javascript:;" onclick="editReturnInvGatePassData();" ><i title="Edit Gate Pass Data" class="fas fa-edit"></i></a>
                                                @endif
                                            @endif
                                            
                                            @if($qc_data_1['total'] == $qc_data_1['accepted'])
                                                <a href="javascript:;" onclick="$('#edit_qc_tr').show('slow');" ><i title="Edit QC Data" class="fas fa-edit"></i></a>
                                            @endif
                                            
                                            @if(empty($qc_return_data) && isset($inventory_status_list[1]) && $inventory_status_list['total_count'] == $inventory_status_list[1])
                                                &nbsp;&nbsp;
                                                <a href="javascript:;" onclick="deleteInvoiceQC({{$qc_data->id}});" ><i title="Delete QC Data" class="fas fa-trash"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($qc_data_1['total'] == $qc_data_1['accepted'])
                                    <tr id="edit_qc_tr" class="elem-hidden">
                                        <td colspan="10">
                                            <div class="alert alert-success alert-dismissible elem-hidden"  id="editQCDataSuccessMessage"></div>
                                            <div class="alert alert-danger alert-dismissible elem-hidden" id="editQCDataErrorMessage"></div>
                                            <p>Comma Separated Defective Inventory QR Codes</p>
                                            <textarea style="width:100%;height:200px;" name="editQcQRCodes" id="editQcQRCodes" ></textarea>
                                            <div class="clearfix clear">&nbsp;</div>
                                            <input type="editQcComments" id="editQcComments" value="" placeholder="Comments" class="form-control">
                                            <div class="clearfix clear">&nbsp;</div>
                                            <button type="button" id="qc_data_edit_cancel_btn" name="qc_data_edit_cancel_btn" class="btn btn-secondary" onclick="$('#edit_qc_tr').hide('slow');">Cancel</button>
                                            <button type="button" id="qc_data_edit_submit_btn" name="qc_data_edit_submit_btn" class="btn btn-dialog" value="Submit" onclick="submitEditQCData();">Update QC</button>
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        
                        <input type="hidden" name="qc_id" id="qc_id" value="{{$qc_data->id}}">
                        <hr/>
                        <div id="products_qc_list"></div>
                        <div id="products_paging_links"></div>
                        <br/>
                        <?php /* ?>
                        <h6>QC Completed Inventory:</h6>
                        <div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>ID</th><th>Piece Barcode</th><th>Product</th>
                        <th>Size</th><th>Color</th><th>SKU</th><th>Cost</th><th>QC Date</th><th>Status</th></tr></thead><tbody>
                        @for($i=0;$i<count($product_list);$i++)    
                            <tr>
                                <td>{{$product_list[$i]->id}}</td>
                                <td>{{$product_list[$i]->peice_barcode}}</td>
                                <td>{{$product_list[$i]->product_name}}</td>
                                <td>{{$product_list[$i]->size_name}}</td>
                                <td>{{$product_list[$i]->color_name}}</td>
                                <td>{{$product_list[$i]->vendor_sku}}</td>
                                <td>{{$product_list[$i]->base_price}}</td>
                                <td>@if(!empty($product_list[$i]->qc_date_1)) {{date('d-m-Y H:i:s',strtotime($product_list[$i]->qc_date_1))}} @endif</td>
                                <td>{{CommonHelper::getProductInventoryQCStatusName($product_list[$i]->qc_status_1)}}</td>
                            </tr>
                        @endfor    
                        </table>
                            {{ $product_list->links() }} <p>Displaying {{$product_list->count()}} of {{ $product_list->total() }} products.</p>
                        </div>
                        <?php */ ?>
                        
                        @if(!empty($product_list_returned))
                            <hr/><h6>Returned Inventory:</h6>
                            <div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>ID</th><th>Piece Barcode</th><th>Product</th>
                            <th>Size</th><th>Color</th><th>SKU</th><th>Cost</th><th>Return Date</th><th>Status</th></tr></thead><tbody>
                            @for($i=0;$i<count($product_list_returned);$i++)    
                                <tr>
                                    <td>{{$product_list_returned[$i]->id}}</td>
                                    <td>{{$product_list_returned[$i]->peice_barcode}}</td>
                                    <td>{{$product_list_returned[$i]->product_name}}</td>
                                    <td>{{$product_list_returned[$i]->size_name}}</td>
                                    <td>{{$product_list_returned[$i]->color_name}}</td>
                                    <td>{{$product_list_returned[$i]->vendor_sku}}</td>
                                    <td>{{$product_list_returned[$i]->base_price}}</td>
                                    <td>@if(!empty($product_list_returned[$i]->qc_date_1)) {{date('d-m-Y H:i:s',strtotime($product_list_returned[$i]->qc_date_1))}} @endif</td>
                                    <td>{{CommonHelper::getProductInventoryQCStatusName($product_list_returned[$i]->qc_status_1)}}</td>
                                </tr>
                            @endfor    
                            </table>
                                {{ $product_list_returned->links() }} <p>Displaying {{$product_list_returned->count()}} of {{ $product_list_returned->total() }} products.</p>
                            </div>
                        @endif
                        
                    @endif                                   
                    
                    <input type="hidden" name="po_id" id="po_id" value="{{$po_detail_data->po_id}}">
                    <input type="hidden" name="po_detail_id" id="po_detail_id" value="{{$po_detail_data->id}}">
                </form>
            </div>
        </div>
    </section>
    
    <div class="modal fade data-modal" id="inventory_qc_complete_confirm_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirm Complete QC</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="qcInventoryConfirmSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="qcInventoryConfirmErrorMessage"></div>

                <form class="" name="qcInventoryConfirmForm" id="qcInventoryConfirmForm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Total Inventory</label>
                                <input id="inventory_total" type="text" class="form-control" disabled="true" name="inventory_total" value="" >
                                
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Accepted Inventory</label>
                                <input id="inventory_accepted" type="text" class="form-control" disabled="true" name="inventory_accepted" value="" >
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Defective Inventory</label>
                                <input id="inventory_defective" type="text" class="form-control" disabled="true" name="inventory_defective" value="" >
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-9">
                                <label>Comments</label>
                                <input id="comments_complete_qc" type="text" class="form-control" name="comments_complete_qc" value="" maxlength="250">
                                <div class="invalid-feedback" id="error_validation_comments_complete_qc"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="qc_inventory_confirm_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="qc_inventory_confirm_cancel" name="qc_inventory_confirm_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="qc_inventory_confirm_submit" name="qc_inventory_confirm_submit" class="btn btn-dialog" onclick="submitConfirmQCInventory();">Submit</button>
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
                        <button type="button" id ="returnInventoryToVendorCancelSubmit" name="returnInventoryToVendorCancelSubmit" class="btn btn-dialog" onclick="submitReturnInventoryToVendor();">Submit</button>
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
                            <button type="button" id ="editGatePassSubmit" name="editGatePassSubmit" class="btn btn-dialog" onclick="submitEditReturnInvGatePassData();">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    <div class="modal fade" id="inventory_qc_delete_items_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="inventoryQcItemsDeleteErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="inventoryQcItemsSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Inventory QC Items ?<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_inventory_qc_items_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_inventory_qc_items_btn" name="delete_inventory_qc_items_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="inventory_qc_delete_error_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="modal-body">
                    <h6>Please select Inventory QC Items<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_inventory_qc_items_cancel">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="inventory_qc_update_item_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="inventoryQcItemUpdateErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="inventoryQcItemUpdateSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to update Inventory Item as <span id="name_update_qc"></span>?<br/></h6>
                    <br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="update_inventory_qc_item_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="update_inventory_qc_item_btn" name="update_inventory_qc_item_btn">Update</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="invoice_qc_delete_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="invoiceQCDeleteErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="invoiceQCDeleteSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Invoice QC Data ?<br/></h6>
                    <br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="invoice_qc_delete_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="invoice_qc_delete_btn" name="invoice_qc_delete_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="importPOQCInventoryDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" >
                <div class="modal-header">
                    <h5 class="modal-title" >Import PO Invoice QC Defective Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="importPOQCInventoryErrorMessage" style="max-height:300px;overflow-y: scroll; "></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="importPOQCInventorySuccessMessage"></div>
                
                <form method="post" name="importPOQCInventoryForm" id="importPOQCInventoryForm">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>QR Code Text File</label>
                            <input type="file" name="barcodeTxtFile" id="barcodeTxtFile" class="form-control"  />
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="importPOQCInventorySpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="importPOQCInventoryCancel" id="importPOQCInventoryCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="importPOQCInventorySubmit" id="importPOQCInventorySubmit" value="Submit" class="btn btn-dialog" onclick="submitImportPOQCInventory();">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=2.1') }}" ></script>
<script type="text/javascript">
    @if(empty($qc_data)) var page_type = 'edit'; @else var page_type = 'detail'; @endif
    $(document).ready(function(){
        loadPOQCInventory(1); 
    });
</script>
@endsection
