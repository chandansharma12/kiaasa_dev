@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'store/dashboard'),array('name'=>'Demands List','link'=>'store/demand/inventory-transfer-store/list'),array('name'=>'Edit Store to Store Inventory Transfer Demand')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Store to Store Inventory Transfer Demand'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    @if(strtolower($demand_data->demand_status) == 'loading')
        <section class="product_area">
            <div class="container-fluid" >
                
                <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
                <input type="hidden" name="store_id" id="store_id" value="{{$demand_data->from_store_id}}">

                <div id="products_import_div" >

                    <form class="" name="inventoryTransferStoreFrm" id="inventoryTransferStoreFrm" method="POST" >

                        <div class="separator-10"></div>

                        <div class="form-row ">
                            <div class="form-group col-md-2">
                                <label>Piece Barcode</label>
                                <input type="text" name="piece_barcode_inv_transfer_store_demand" id="piece_barcode_inv_transfer_store_demand" class="form-control" autofocus="true">
                                <input type="hidden" name="piece_id" id="piece_id" value="">
                                <input type="hidden" name="product_id" id="product_id" value="">
                            </div>
                            <div class="form-group col-md-1">
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
                                <label>SKU</label>
                                <input type="text" name="product_sku" id="product_sku" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-1">
                                <label>Cost</label>
                                <input type="text" name="piece_cost" id="piece_cost" class="form-control import-data" readonly="true">
                            </div>
                            <div class="form-group col-md-1">
                                <label>&nbsp;</label>
                                <button type="button" id="upload_transfer_demand_inv_btn" name="upload_transfer_demand_inv_btn" onclick="importStoreToStoreDemandInventory();" class="btn btn-dialog" title="Import Demand Inventory"><i title="Import Demand Inventory" class="fas fa-upload fas-icon"></i></button>
                            </div>
                            
                        </div>  
                        
                        <div style="height:48px;">
                            <span id="inventoryTransferStoreDemandErrorMessage" class="alert alert-danger product-added-span elem-hidden"></span>
                            <span id="inventoryTransferStoreDemandSuccessMessage" class="alert alert-success product-added-span elem-hidden"></span>
                        </div>
                        
                        <hr/>
                        <h6>Products Inventory</h6>
                        <div id="products_imported_list"></div>
                        <div id="products_paging_links"></div>
                        <br/>
                        
                        <div class="form-row" >
                            <button type="button" id="delete_inventory_transfer_store_demand_submit" name="delete_inventory_transfer_store_demand_submit" class="btn btn-dialog" value="Submit" onclick="deleteInventoryTransferStoreDemandItems();">Delete</button>&nbsp;&nbsp;
                        </div>
                        <br/>
                        
                        <div class="form-row" >
                            <div id="add_pos_inventory_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id ="close_inventory_transfer_store_demand_submit" name="close_inventory_transfer_store_demand_submit" class="btn btn-dialog" value="Submit" onclick="closeInventoryTransferStoreDemand();">Close Demand</button>&nbsp;&nbsp;
                            <button type="button" id="close_inventory_transfer_store_demand_cancel" name="close_inventory_transfer_store_demand_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                        </div>
                        <br/>
                        @csrf
                    </form>
                </div>
            </div>
        </section>
    @endif
    
    <div class="modal fade data-modal" id="closeInventoryTransferStoreDemandDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Close Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="closeInventoryTransferStoreDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="closeInventoryTransferStoreDemandErrorMessage"></div>

                <form class="" name="closeInventoryTransferStoreDemandForm" id="closeInventoryTransferStoreDemandForm" type="POST" >
                    <div class="modal-body">
                         <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Demand Type</label>
                                <input id="demand_prev_type" type="text" class="form-control" name="demand_prev_type" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Date Created</label>
                                <input id="demand_prev_date_created" type="text" class="form-control" name="demand_prev_date_created" value="" readonly="true">
                            </div>
                             <div class="form-group col-md-3" >
                                <label>From Store</label>
                                <input id="demand_prev_from_store_name" type="text" class="form-control" name="demand_prev_from_store_name" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>From Store GST No</label>
                                <input id="demand_prev_from_store_gst_no" type="text" class="form-control" name="demand_prev_from_store_gst_no" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>To Store</label>
                                <input id="demand_prev_store_name" type="text" class="form-control" name="demand_prev_store_name" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>To Store GST No</label>
                                <input id="demand_prev_store_gst_no" type="text" class="form-control" name="demand_prev_store_gst_no" value="" readonly="true">
                            </div>
                        </div>     
                        <div class="form-row">
                            <div class="form-group col-md-2" >
                                <label>Total Inventory</label>
                                <input id="demand_prev_total_inv" type="text" class="form-control" name="demand_prev_total_inv" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-2" >
                                <label>Taxable Value</label>
                                <input id="demand_prev_taxable_value" type="text" class="form-control" name="demand_prev_taxable_value" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-2" >
                                <label>GST Amount</label>
                                <input id="demand_prev_gst_amount" type="text" class="form-control" name="demand_prev_gst_amount" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Total Cost Price</label>
                                <input id="demand_prev_total_amt" type="text" class="form-control" name="demand_prev_total_amt" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Total Sale Price</label>
                                <input id="demand_prev_total_sale_price" type="text" class="form-control" name="demand_prev_total_sale_price" value="" readonly="true">
                            </div>
                        </div>
                        
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
                        <div id="closeInventoryTransferStoreDemandSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="closeInventoryTransferStoreDemandCancel" name="closeInventoryTransferStoreDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="closeInventoryTransferStoreDemandSubmit" name="closeInventoryTransferStoreDemandSubmit" class="btn btn-dialog" onclick="submitCloseInventoryTransferStoreDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="inv_transfer_store_demand_delete_items_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="deleteInventoryTransferDemandItemsErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="deleteInventoryTransferDemandItemsSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Demand Items ?<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_inv_transfer_store_demand_items_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_inv_transfer_store_demand_items_btn" name="delete_inv_transfer_store_demand_items_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="inv_transfer_store_demand_delete_error_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="modal-body">
                    <h6>Please select the Demand Items<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_inv_transfer_store_demand_items_cancel">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="importStoreToStoreDemandInventoryDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" >
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Import Store to Store Demand Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="importStoreToStoreDemandInventoryErrorMessage" style="max-height:300px;overflow-y: scroll; "></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="importStoreToStoreDemandInventorySuccessMessage"></div>
                
                <form method="post" name="importStoreToStoreDemandInventoryForm" id="importStoreToStoreDemandInventoryForm">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>QR Code Text File</label>
                            <input type="file" name="barcodeTxtFile" id="barcodeTxtFile" class="form-control"  />
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="importStoreToStoreDemandInventorySpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="importStoreToStoreDemandInventoryCancel" id="importStoreToStoreDemandInventoryCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="importStoreToStoreDemandInventorySubmit" id="importStoreToStoreDemandInventorySubmit" value="Submit" class="btn btn-dialog" onclick="submitImportStoreToStoreDemandInventory();">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')

<script src="{{ asset('js/store.js?v=2.68') }}" ></script>
<script type="text/javascript">
    var page_type = 'edit';
    $(document).ready(function(){
        loadInventoryTransferStoreDemandInventory(1);
    });
    
</script>
@endsection
