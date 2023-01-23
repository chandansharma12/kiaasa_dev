@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'store/dashboard'),array('name'=>'Demands List','link'=>'store/demand/inventory-transfer-store/list'),array('name'=>'Load Store to Store Inventory Transfer Demand')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Load Store to Store Inventory Transfer Demand'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    @if(strtolower($demand_data->demand_status) == 'loaded' || strtolower($demand_data->demand_status) == 'store_loading')
        <section class="product_area">
            <div class="container-fluid" >
                
                <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
                <input type="hidden" name="store_id" id="store_id" value="{{$demand_data->from_store_id}}">

                <div id="products_import_div" >

                    <form class="" name="inventoryTransferStoreRecFrm" id="inventoryTransferStoreRecFrm" method="POST">

                        <div class="separator-10"></div>

                        <div class="form-row ">
                            <div class="form-group col-md-2">
                                <label>Piece Barcode</label>
                                <input type="text" name="piece_barcode_inv_transfer_store_rec_demand" id="piece_barcode_inv_transfer_store_rec_demand" class="form-control" autofocus="true">
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
                                <button type="button" id="upload_transfer_demand_inv_btn" name="upload_transfer_demand_inv_btn" onclick="importStoreToStoreDemandInventoryLoad();" class="btn btn-dialog" title="Import Demand Inventory"><i title="Import Demand Inventory" class="fas fa-upload fas-icon"></i></button>
                            </div>
                        </div>  
                        
                        <div style="height:48px;">
                            <span id="inventoryTransferStoreRecDemandErrorMessage" class="alert alert-danger product-added-span elem-hidden"></span>
                            <span id="inventoryTransferStoreRecDemandSuccessMessage" class="alert alert-success product-added-span elem-hidden"></span>
                        </div>
                        
                        <hr/>
                        <h6>Products Inventory</h6>
                        <div id="products_imported_list"></div>
                        <div id="products_paging_links"></div>
                        <br/>
                        
                        <div class="form-row" >
                            <div id="add_pos_inventory_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id ="close_inventory_transfer_store_demand_submit" name="close_inventory_transfer_store_demand_submit" class="btn btn-dialog" value="Submit" onclick="closeInventoryTransferStoreRecDemand();">Close Demand</button>&nbsp;&nbsp;
                            <button type="button" id="close_inventory_transfer_store_demand_cancel" name="close_inventory_transfer_store_demand_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                        </div>
                        <br/>
                        @csrf
                    </form>
                </div>
            </div>
            
            <div class="modal fade data-modal" id="closeInventoryTransferStoreDemandDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLongTitle">Close Demand</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                        </div>

                        <div class="alert alert-success alert-dismissible elem-hidden" id="closeInventoryTransferStoreDemandSuccessMessage"></div>
                        <div class="alert alert-danger alert-dismissible elem-hidden"  id="closeInventoryTransferStoreDemandErrorMessage"></div>

                        <form class="" name="closeDemandForm" id="closeDemandForm" type="POST" >
                            <div class="modal-body">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Comments</label>
                                        <input id="comments_close_demand" type="text" class="form-control" name="comments_close_demand" value="" >
                                        <div class="invalid-feedback" id="error_validation_comments_close_demand"></div>
                                    </div>
                                </div>    
                            </div>
                            <div class="modal-footer center-footer">
                                <div id="closeInventoryTransferStoreDemandSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                                <button type="button" id="closeInventoryTransferStoreDemandCancel" name="closeInventoryTransferStoreDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" id ="closeInventoryTransferStoreDemandSubmit" name="closeInventoryTransferStoreDemandSubmit" class="btn btn-dialog" onclick="submitCloseInventoryTransferStoreRecDemand();">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    
        <div class="modal fade data-modal" id="importStoreToStoreDemandInventoryLoadDialog" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content" >
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLongTitle">Import Store to Store Demand Inventory</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                    </div>
                    <div class="alert alert-danger alert-dismissible elem-hidden"  id="importStoreToStoreDemandInventoryLoadErrorMessage" style="max-height:300px;overflow-y: scroll; "></div>
                    <div class="alert alert-success alert-dismissible elem-hidden" id="importStoreToStoreDemandInventoryLoadSuccessMessage"></div>

                    <form method="post" name="importStoreToStoreDemandInventoryLoadForm" id="importStoreToStoreDemandInventoryLoadForm">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>QR Code Text File</label>
                                <input type="file" name="barcodeTxtFile" id="barcodeTxtFile" class="form-control"  />
                            </div>
                        </div>
                    </form>    
                    <div class="modal-footer center-footer">
                        <div id="importStoreToStoreDemandInventoryLoadSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                        <button name="importStoreToStoreDemandInventoryLoadCancel" id="importStoreToStoreDemandInventoryLoadCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button name="importStoreToStoreDemandInventoryLoadSubmit" id="importStoreToStoreDemandInventoryLoadSubmit" value="Submit" class="btn btn-dialog" onclick="submitImportStoreToStoreDemandInventoryLoad();">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/store.js?v=1.68') }}" ></script>
<script type="text/javascript">
    var page_type = 'edit';
    $(document).ready(function(){
        loadInventoryTransferStoreRecDemandInventory(1);
    });
    
</script>
@endsection
