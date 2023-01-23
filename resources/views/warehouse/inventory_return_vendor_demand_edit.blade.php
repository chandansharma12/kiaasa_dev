@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Return Vendor Demands List','link'=>'warehouse/demand/inventory-return-vendor/list'),array('name'=>'Load Vendor Inventory Return')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Load Vendor Return Inventory'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    @if(in_array(strtolower($demand_data->demand_status),array('warehouse_loading')))
        <section class="product_area">
            <div class="container-fluid" >
                
                <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
                <input type="hidden" name="push_demand_id" id="push_demand_id" value="{{$demand_data->push_demand_id}}">

                <div id="products_import_div" >

                    <form class="" name="inventoryReturnVendorFrm" id="inventoryReturnVendorFrm" method="POST" >

                       <div class="separator-10"></div>

                            <div class="form-row ">
                                <div class="form-group col-md-2">
                                    <label>Piece Barcode</label>
                                    <input type="text" name="inv_return_vendor_piece_barcode" id="inv_return_vendor_piece_barcode" class="form-control " autofocus="true">
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
                                    <!--<button type="button" id="pos_add_inventory_submit" name="pos_add_inventory_submit" class="btn btn-dialog" value="Submit" onclick="addInventoryReturnVendorDemandProductData();">Add</button>-->
                                    <button type="button" id="upload_return_demand_inv_btn" name="upload_return_demand_inv_btn" onclick="importWHToVendorDemandInventory();" class="btn btn-dialog" title="Import Demand Inventory"><i title="Import Demand Inventory" class="fas fa-upload fas-icon"></i></button>
                                </div>
                            </div>  
                       
                       <div style="height:50px;">
                            <span id="inventoryReturnVendorErrorMessage" class="alert alert-danger product-added-span elem-hidden"></span>
                            <span id="inventoryReturnVendorSuccessMessage" class="alert alert-success product-added-span elem-hidden"></span>
                        </div>

                        <div id="products_imported_list"></div>
                        <div id="products_paging_links"></div>
                        
                        <br/>
                        
                        <div class="form-row" >
                            <button type="button" id="delete_inventory_return_demand_submit" name="delete_inventory_return_demand_submit" class="btn btn-dialog" value="Submit" onclick="deleteInventoryReturnVendorDemandItems();">Delete</button>&nbsp;&nbsp;
                        </div>
                        <br/>
                        
                        <div class="form-row" >
                            <div id="inventory_return_vendor_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id ="close_inventory_return_vendor_submit" name="close_inventory_return_vendor_submit" class="btn btn-dialog" value="Submit" onclick="closeInventoryReturnVendorDemand();">Close Demand</button>&nbsp;&nbsp;
                            <button type="button" id="close_inventory_return_vendor_cancel" name="close_inventory_return_vendor_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                        </div>
                        <br/>
                        @csrf
                    </form>
                </div>
            </div>
        </section>
    @endif
    
    <div class="modal fade data-modal" id="closeInventoryReturnVendorDemandDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Close Return Inventory Vendor Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="closeInventoryReturnDemandVendorSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="closeInventoryReturnDemandVendorErrorMessage"></div>

                <form class="" name="closeInventoryReturnDemandVendorForm" id="closeInventoryReturnDemandVendorForm" type="POST" >
                    <div class="modal-body">
                        <?php /* ?>
                        <div class="form-row">
                            
                            <div class="form-group col-md-2">
                                <label>Inventory Loaded</label>
                                <input id="close_demand_inv_loaded" type="text" readonly="true" class="form-control" name="close_demand_inv_loaded" value="" >
                            </div>
                            <div class="form-group col-md-2">
                                <label>Total Base Price</label>
                                <input id="close_demand_base_price" type="text" readonly="true" class="form-control" name="close_demand_base_price" value="" >
                            </div>
                            <div class="form-group col-md-2">
                                <label>Total Sale Price</label>
                                <input id="close_demand_sale_price" type="text" readonly="true" class="form-control" name="close_demand_sale_price" value="" >
                            </div>
                        </div>    <?php */ ?>
                        
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
                                <label>Vendor</label>
                                <input id="demand_prev_vendor_name" type="text" class="form-control" name="demand_prev_vendor_name" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Vendor GST No</label>
                                <input id="demand_prev_vendor_gst_no" type="text" class="form-control" name="demand_prev_vendor_gst_no" value="" readonly="true">
                            </div>
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
                                <label>Total MRP</label>
                                <input id="demand_prev_total_sale_price" type="text" class="form-control" name="demand_prev_total_sale_price" value="" readonly="true">
                            </div>
                        </div>    
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Comments</label>
                                <input id="comments_close_demand" type="text" class="form-control" name="comments_close_demand" value="" >
                                <div class="invalid-feedback" id="error_validation_comments_close_demand"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="closeInventoryReturnDemandVendorSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="closeInventoryReturnDemandVendorCancel" name="closeInventoryReturnDemandVendorCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="closeInventoryReturnDemandVendorSubmit" name="closeInventoryReturnDemandVendorSubmit" class="btn btn-dialog" onclick="submitCloseInventoryReturnVendorDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="inventory_return_demand_delete_items_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="deleteInventoryDemandItemsErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="deleteInventoryDemandItemsSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Demand Items ?<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_inventory_return_demand_items_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_inventory_return_demand_items_btn" name="delete_inventory_return_demand_items_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="inventory_return_demand_delete_error_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="modal-body">
                    <h6>Please select Demand Items<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_inventory_return_demand_items_cancel">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="importWHToVendorDemandInventoryDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" >
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Import Warehouse to Vendor Demand Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="importWHToVendorDemandInventoryErrorMessage" style="max-height:300px;overflow-y: scroll; "></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="importWHToVendorDemandInventorySuccessMessage"></div>
                
                <form method="post" name="importWHToVendorDemandInventoryForm" id="importWHToVendorDemandInventoryForm">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>QR Code Text File</label>
                            <input type="file" name="barcodeTxtFile" id="barcodeTxtFile" class="form-control"  />
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="importWHToVendorDemandInventorySpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="importWHToVendorDemandInventoryCancel" id="importWHToVendorDemandInventoryCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="importWHToVendorDemandInventorySubmit" id="importWHToVendorDemandInventorySubmit" value="Submit" class="btn btn-dialog" onclick="submitImportWHToVendorDemandInventory();">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')

<script src="{{ asset('js/warehouse_po.js?v=2.48') }}" ></script>
<script type="text/javascript">
    var page_type = 'edit';
    $(document).ready(function(){
        loadInventoryReturnVendorDemandInventory(1);
    });
    
</script>
@endsection
