@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard'),array('name'=>'Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'Purchase Order Invoice List','link'=>'purchase-order/product/invoice/list/'.$po_detail_data->po_id),array('name'=>'PO Import Inventory')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'PO Import Inventory ('.$po_data->order_no.')'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
               
           <div id="products_import_div" >
                
                <form class="" name="importPosInventoryFrm" id="importPosInventoryFrm" method="POST" >
                    Invoice No: {{$po_detail_data->invoice_no}} | Invoice Date: {{date('d-m-Y',strtotime($po_detail_data->invoice_date))}}
                    
                    <div class="alert alert-danger alert-dismissible elem-hidden" id="inventoryErrorMessage"></div>
                    <div class="separator-10"></div>
                    @if(empty($grn_data) )
                        @if($is_fake_inventory_user  == false)
                            <div class="form-row " id="products_add_div">
                                <div class="form-group col-md-2">
                                    <label>Piece Barcode</label>
                                    <input type="text" name="piece_barcode_inv_import" id="piece_barcode_inv_import" class="form-control " autofocus="true">
                                    <input type="hidden" name="piece_id" id="piece_id" value="">
                                    <input type="hidden" name="added_barcode" id="added_barcode" value="">
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
                                    <label>Intake Date</label>
                                    <input type="text" name="intake_date" id="intake_date" class="form-control import-data" readonly="true" >
                                </div>
                                <div class="form-group col-md-1">
                                    <label>&nbsp;</label>
                                    <!--<button type="button" id="pos_add_inventory_submit" name="pos_add_inventory_submit" class="btn btn-dialog" value="Submit" onclick="addInventoryProductData();">Add</button>-->
                                    <button type="button" id="upload_po_invoice_inv_link" name="upload_po_invoice_inv_link" onclick="importPOInvoiceInventory();" class="btn btn-dialog" title="Import Inventory"><i title="Import Inventory" class="fas fa-upload fas-icon"></i></button>
                                 </div>
                            </div>  
                        
                        @endif
                        <div style="height:25px;">
                            <span id="product_added_span" class="alert-success product-added-span elem-hidden" ></span>
                            <div id="importPosInventoryErrorMessage" class="alert alert-danger elem-hidden" ></div>
                            <div id="importPosInventorySuccessMessage" class="alert alert-success elem-hidden"></div>
                        </div>
                    
                        <hr/><h6>GRN Inventory</h6>
                        <div id="products_imported_list"></div>
                        <div id="products_paging_links"></div>
                   
                        <br/>
                        <div class="form-row" >
                            <button type="button" id="delete_inventory_import_submit" name="delete_inventory_import_submit" class="btn btn-dialog" value="Submit" onclick="deleteInventoryImportItems();">Delete</button>&nbsp;&nbsp;
                        </div>
                        <br/>
                        <div class="form-row" >
                            <div id="add_inventory_grn_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id ="pos_add_inventory_grn_submit" name="pos_add_inventory_grn_submit" class="btn btn-dialog" value="Submit" onclick="displayAddInventoryGRN();">Add Inventory GRN</button>&nbsp;&nbsp;
                            <button type="button" id="pos_add_inventory_grn_cancel" name="pos_add_inventory_grn_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                        </div>
                    @endif
                    
                    @if(!empty($grn_data))
                        <hr><h6>GRN </h6><div class="table-responsive">
                            <table class="table table-striped clearfix admin-table" cellspacing="0">
                                <thead><tr class="header-tr"><th>GRN ID</th><th>GRN No</th><th>Inventory Count</th><th>Comments</th><th>Added On</th>
                                    @if($grn_data->grn_edited == 0)    
                                        <th>Edit GRN</th>
                                    @endif
                                    <th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{$grn_data->id}}</td>
                                        <td>{{$grn_data->grn_no}}</td>
                                        <td><?php $count_data = json_decode($grn_data->other_data,true) ?> {{$count_data['total']}}</td>
                                        <td>{{$grn_data->comments}}</td>
                                        <td>{{date('d-m-Y H:i:s',strtotime($grn_data->created_at))}}</td>
                                        @if($grn_data->grn_edited == 0)  
                                            <td>&nbsp;<a href="javascript:;" onclick="editInvoiceGRNInventory({{$grn_data->id}});" title="Edit Invoice GRN Data"><i title="Edit Invoice GRN Data" class="fas fa-edit"></i></a></td>
                                        @endif
                                        <td>
                                            <a href="{{url('warehouse/sor/inventory/import/invoice/'.$grn_data->id)}}" ><i title="Download GRN PDF Invoice" class="fas fa-download"></i></a>
                                            
                                            @if(!empty($less_inv_debit_note))
                                                &nbsp;|&nbsp;
                                                &nbsp;&nbsp;<a href="{{url('warehouse/sor/inventory/pending/invoice/'.$less_inv_debit_note->id)}}" ><i title="Download Pending Inventory Debit Note Invoice" class="fas fa-download"></i></a>
                                                @if(in_array($user->user_type,[1,6]))
                                                    <?php /* ?>&nbsp;&nbsp;<a href="{{url('warehouse/sor/inventory/debit-note/add/'.$po_detail_data->id)}}" ><i title="Edit Pending Inventory Debit Note" class="fas fa-edit"></i></a> <?php */ ?>
                                                    &nbsp;&nbsp;<a href="javascript:;" onclick="cancelPendingInventoryDebitNote({{$less_inv_debit_note->id}});" ><i title="Cancel Pending Inventory Debit Note" class="fas fa-crosshairs"></i></a>
                                                @endif
                                            @else
                                                @if(in_array($user->user_type,[1,6]))
                                                    &nbsp;|&nbsp;
                                                    &nbsp;&nbsp;<a href="{{url('warehouse/sor/inventory/debit-note/add/'.$po_detail_data->id)}}" ><i title="Add Pending Inventory Debit Note" class="fas fa-edit"></i></a>
                                                @endif
                                            @endif
                                            
                                            @if(in_array($user->user_type,[1,6]))
                                                &nbsp;|&nbsp;
                                                @if(empty($excess_amount_debit_note))
                                                    <!--&nbsp;&nbsp;<a href="{{url('warehouse/sor/inventory/debit-note/excess-amount/add/'.$po_detail_data->id)}}" ><i title="Add Excess Amount Debit Note" class="fas fa-edit"></i></a>-->
                                                @endif
                                                
                                                @if(!empty($excess_amount_debit_note))
                                                    &nbsp;&nbsp;<a href="{{url('warehouse/sor/inventory/debit-note/excess-amount/download/'.$excess_amount_debit_note->id)}}" ><i title="Download Excess Amount Debit Note" class="fas fa-download"></i></a>
                                                    &nbsp;&nbsp;<a href="javascript:;" onclick="cancelPOInvoiceExcessAmountDebitNote({{$excess_amount_debit_note->id}});" ><i title="Cancel Excess Amount Debit Note" class="fas fa-crosshairs"></i></a>
                                                @endif
                                            @endif
                                            
                                            @if(in_array($user->user_type,[1,6]) && empty($qc_data))
                                                &nbsp;<a href="javascript:;" onclick="deleteInvoiceGRN({{$grn_data->id}});"><i title="Delete Invoice GRN Data" class="fas fa-trash"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                       <div class="table-responsive table-filter" style="font-size:12px; ">
                            <table class="table table-striped admin-table" cellspacing="0">
                                <thead>
                                    <tr class="header-tr">
                                        <th>SNo.</th><th>Style</th><th>Color</th>
                                        @for($i=0;$i<count($size_list);$i++)
                                            <th>{{$size_list[$i]['size']}}</th>
                                        @endfor
                                        <th>Rate</th><th >Total Qty</th><th>Amount</th>
                                        @if(!empty($qc_data))
                                            <!--<th>Action</th>-->
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sizes_count = array(); $sku_size_count_total = 0; ?>
                                    @for($i=0;$i<count($sku_products_list);$i++)
                                        <?php $sku_size_count = 0; ?>
                                        <tr>
                                            <td>{{($i+1)}}</td>
                                            <td>{{($sku_products_list[$i]->vendor_sku)}}</td>
                                            <td>{{($sku_products_list[$i]->color_name)}}</td>
                                            @for($q=0;$q<count($size_list);$q++)
                                                <?php $size_id = $size_list[$q]['id']; ?>
                                                @if(isset($sku_products_list[$i]->size_data[$size_id]))
                                                    <?php $size_count = $sku_products_list[$i]->size_data[$size_id]; ?>
                                                    <td>{{$size_count}}</td>
                                                    <?php $sku_size_count+=$size_count;  ?>
                                                    <?php if(isset($sizes_count[$size_id])) $sizes_count[$size_id]+=$size_count;else $sizes_count[$size_id] = $size_count; ?>
                                                @else    
                                                <td></td>
                                                @endif
                                            @endfor    
                                            <?php $sku_size_count_total+=$sku_size_count; ?>
                                            <td>{{--$sku_products_list[$i]->base_price --}} {{($sku_products_list[$i]->poi_rate)}}</td>
                                            <td>{{$sku_size_count}}</td>
                                            <td>{{round($sku_products_list[$i]->base_price*$sku_size_count,2)}}</td>
                                            @if(!empty($qc_data))
                                                <!--<td>
                                                    @if($user->user_type == 6)
                                                        <a href="javascript:;" onclick="inventoryTransferInInvoices('{{($sku_products_list[$i]->product_sku)}}');"><i title="Transfer Inventory" class="far fa-edit"></i></a>
                                                    @endif
                                                </td>-->
                                            @endif 
                                        </tr>
                                    @endfor
                                    <tr>
                                        <td colspan="3"></td>
                                        @for($i=0;$i<count($size_list);$i++)
                                            <td>@if(isset($sizes_count[$size_list[$i]['id']])) {{$sizes_count[$size_list[$i]['id']]}} @endif </td>
                                        @endfor
                                        <td></td>
                                        <td>{{$sku_size_count_total}}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>   
                        
                        <hr/><h6>GRN Inventory</h6>
                        <div id="products_imported_list"></div>
                        <div id="products_paging_links"></div>
                        
                        <input type="hidden" name="grn_id" id="grn_id" value="{{$grn_data->id}}">
                    @endif
                    <br/>
                    <input type="hidden" name="po_id" id="po_id" value="{{$po_detail_data->po_id}}">
                    <input type="hidden" name="po_detail_id" id="po_detail_id" value="{{$po_detail_data->id}}">
                    @csrf
                </form>
            </div>
        </div>
    </section>
    
    <div class="modal fade data-modal" id="add_inventory_grn_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Inventory GRN</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addInventoryGRNSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addInventoryGRNErrorMessage"></div>

                <form class="" name="addInventoryGRNForm" id="addInventoryGRNForm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Purchase Order</label>
                                <input id="grn_preview_po_name" type="text" class="form-control" name="grn_preview_po_name" value="{{$po_data->order_no}}" readonly="true">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Invoice No</label>
                                <input id="grn_preview_invoice_no" type="text" class="form-control" name="grn_preview_invoice_no" value="{{$po_detail_data->invoice_no}}" readonly="true">
                            </div>
                        </div>    
                        <div id="grn_preview_data"></div>
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
                        <button type="button" id ="add_inventory_grn_submit" name="add_inventory_grn_submit" class="btn btn-dialog" onclick="submitAddInventoryGRN();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="inventory_transfer_invoice_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Transfer Inventory to other Invoice</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="inventoryTransferInvoiceSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="inventoryTransferInvoiceErrorMessage"></div>

                <form class="" name="inventoryTransferInvoiceForm" id="inventoryTransferInvoiceForm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Transfer Inventory</label>
                                <div id="size_list"></div>
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Transfer to Invoice</label>
                                <select name="invoice_id" id="invoice_id" class="form-control">
                                    <option value="">Invoice</option>
                                    @for($i=0;$i<count($po_invoice_list);$i++)
                                        <?php $sel = ($po_invoice_list[$i]['id'] == request('invoice_id'))?'selected':''; ?>
                                        <option {{$sel}} value="{{$po_invoice_list[$i]['id']}}">{{$po_invoice_list[$i]['invoice_no']}} ({{$po_invoice_list[$i]['products_count']}})</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_invoice_id"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="transfer_sku" id="transfer_sku" value=""/>
                        <input type="hidden" name="transfer_id_str" id="transfer_id_str" value=""/>
                        <div id="inventory_transfer_invoice_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="inventory_transfer_invoice_cancel" name="inventory_transfer_invoice_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="inventory_transfer_invoice_submit" name="inventory_transfer_invoice_submit" class="btn btn-dialog" onclick="submitInventoryTransferInInvoices();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="inventory_import_delete_items_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="inventoryImportItemsDeleteErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="inventoryImportItemsSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Inventory Import Items ?<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_inventory_import_items_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_inventory_import_items_btn" name="delete_inventory_import_items_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="inventory_import_delete_error_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="modal-body">
                    <h6>Please select Inventory Import Items<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_inventory_import_items_cancel">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoice_grn_delete_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="invoiceGRNDeleteErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="invoiceGRNDeleteSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Invoice GRN Data ?<br/></h6>
                    <br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="invoice_grn_delete_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="invoice_grn_delete_btn" name="invoice_grn_delete_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="edit_grn_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit GRN Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editGRNSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editGRNErrorMessage"></div>

                <form name="editGRNForm" id="editGRNForm" method="POST" >
                    <div class="modal-body">
                        <a id="add_grn_inv_btn" name="add_grn_inv_btn" class="btn btn-dialog" href="javascript:;" onclick="toggleEditGRN('add');"><i title="Add" class="fa fas-icon fa-plus" ></i> Add Inventory</a>
                        <a id="delete_grn_inv_btn" name="delete_grn_inv_btn" class="btn btn-dialog" href="javascript:;" onclick="toggleEditGRN('delete');" style="opacity:0.6;"><i title="Add" class="fa fas-icon fa-trash"></i> Delete Inventory</a>
                        
                        <div class="separator-10">&nbsp;</div>
                        
                        <div class="form-row" id="edit_grn_qr_code_add_div">
                            @for($i=1;$i<=20;$i++)
                                <div class="form-group col-md-3">
                                    <label></label>
                                    <input id="qr_code_add_{{$i}}" type="text" class="form-control edit-grn-qr-code" name="qr_code_add_{{$i}}" value="" placeholder="Add QR Code {{$i}}" maxlength="25">
                                    <div class="invalid-feedback" id="error_validation_qr_code_add_{{$i}}"></div>
                                </div>
                            @endfor
                        </div>
                        
                        <div class="form-row elem-hidden" id="edit_grn_qr_code_delete_div">
                            @for($i=1;$i<=20;$i++)
                                <div class="form-group col-md-3">
                                    <label></label>
                                    <input id="qr_code_delete_{{$i}}" type="text" class="form-control edit-grn-qr-code" name="qr_code_delete_{{$i}}" value="" placeholder="Delete QR Code {{$i}}" maxlength="25">
                                    <div class="invalid-feedback" id="error_validation_qr_code_delete_{{$i}}"></div>
                                </div>
                            @endfor
                        </div>                    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="edit_grn_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editGRNCancel" name="editGRNCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editGRNSubmit" name="editGRNSubmit" class="btn btn-dialog" onclick="submitEditInvoiceGRNInventory();">Submit</button>
                        
                        <input type="hidden" name="edit_demand_add_total" id="edit_demand_add_total" value="20"> 
                        <input type="hidden" name="edit_demand_delete_total" id="edit_demand_delete_total" value="20"> 
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pending_inv_debit_note_cancel_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Cancel Pending Inventory Debit Note</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="cancelPendingInventoryDebitNoteSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="cancelPendingInventoryDebitNoteErrorMessage"></div>
                
                <form name="cancelPendingInventoryDebitNoteForm" id="cancelPendingInventoryDebitNoteForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Comments</label>
                                <input id="pending_inv_debit_note_cancel_comments" type="text" class="form-control" name="pending_inv_debit_note_cancel_comments" value="" placeholder="Comments" maxlength="250">
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="pending_inv_debit_note_id" id="pending_inv_debit_note_id" value=""> 
                        <button type="button" id="cancelPendingInventoryDebitNoteCancel" name="cancelPendingInventoryDebitNoteCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="cancelPendingInventoryDebitNoteSubmit" name="cancelPendingInventoryDebitNoteSubmit" class="btn btn-dialog" onclick="submitCancelPendingInventoryDebitNote();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="excess_amount_debit_note_cancel_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Cancel Excess Amount Debit Note</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="cancelExcessAmountDebitNoteSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="cancelExcessAmountDebitNoteErrorMessage"></div>
                
                <form name="cancelExcessAmountDebitNoteForm" id="cancelExcessAmountDebitNoteForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Comments</label>
                                <input id="excess_amount_debit_note_cancel_comments" type="text" class="form-control" name="excess_amount_debit_note_cancel_comments" value="" placeholder="Comments" maxlength="250">
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="excess_amount_debit_note_id" id="excess_amount_debit_note_id" value=""> 
                        <button type="button" id="cancelExcessAmountDebitNoteCancel" name="cancelExcessAmountDebitNoteCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="cancelExcessAmountDebitNoteSubmit" name="cancelExcessAmountDebitNoteSubmit" class="btn btn-dialog" onclick="submitCancelPOInvoiceExcessAmountDebitNote();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="importPOInvoiceInventoryDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" >
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Import PO Invoice Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="importPOInvoiceInventoryErrorMessage" style="max-height:300px;overflow-y: scroll; "></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="importPOInvoiceInventorySuccessMessage"></div>
                
                <form method="post" name="importPOInvoiceInventoryForm" id="importPOInvoiceInventoryForm">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>QR Code Text File</label>
                            <input type="file" name="barcodeTxtFile" id="barcodeTxtFile" class="form-control"  />
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="importPOInvoiceInventorySpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="importPOInvoiceInventoryCancel" id="importPOInvoiceInventoryCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="importPOInvoiceInventorySubmit" id="importPOInvoiceInventorySubmit" value="Submit" class="btn btn-dialog" onclick="submitImportPOInvoiceInventory();">Submit</button>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=2.52') }}" ></script>
<script type="text/javascript">
    @if(empty($grn_data)) var page_type = 'edit'; @else var page_type = 'detail'; @endif 
    loadPOInventory(1);
</script>
@endsection
