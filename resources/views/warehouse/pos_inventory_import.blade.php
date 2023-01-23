@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard'),array('name'=>'Import Product Inventory')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Import Product Inventory'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <span id="po_no_span" style="font-size: 16px;" class="elem-hidden"></span>
            <span id="product_added_span" class="alert-success" style="font-size: 14px;margin-left: 15px;padding:3px;" ></span>
            <div id="importPosInventoryErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="importPosInventorySuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get" id="poCheckForm" name="poCheckForm">
                <div class="form-row " id="po_check_div">
                    <div class="form-group col-md-2">
                        <label>PO Number</label>
                        <input type="text" name="po_no" id="po_no" class="form-control " autofocus="true">
                        <input type="hidden" name="po_id" id="po_id" value="">
                        <input type="hidden" name="po_detail_id" id="po_detail_id" value="">
                    </div>
                   
                    <div class="form-group col-md-1">
                        <label>&nbsp;</label>
                        <button type="button" id="po_submit" name="po_submit" class="btn btn-dialog" onclick="checkImportInventoryPO();">Submit</button>
                    </div>
                </div>    
            </form> 
            
            <div id="vehicle_details_div" class="elem-hidden">
                <button type="button" id="pos_add_vehicle_details_submit" name="pos_add_vehicle_details_submit" class="btn btn-dialog" value="Submit" onclick="displayAddVehicleDetails();">Add Vehicle Details</button>&nbsp;&nbsp;
                <button type="button" id="pos_add_vehicle_details_cancel" name="pos_add_vehicle_details_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
            </div>            
            
            <div id="products_import_div" class="elem-hidden">
                
                <form class="" name="importPosInventoryFrm" id="importPosInventoryFrm" method="POST" enctype="multipart/form-data">
                   
                   <div class="separator-10"></div>
                    
                        <div class="form-row " id="products_add_div">
                            <div class="form-group col-md-2">
                                <label>Piece Barcode</label>
                                <input type="text" name="piece_barcode_inv_import" id="piece_barcode_inv_import" class="form-control " autofocus="true">
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
                                <label>Intake Date</label>
                                <input type="text" name="intake_date" id="intake_date" class="form-control import-data" readonly="true" >
                            </div>
                             <div class="form-group col-md-1">
                                <label>&nbsp;</label>
                                <button type="button" id="pos_add_inventory_submit" name="pos_add_inventory_submit" class="btn btn-dialog" value="Submit" onclick="addInventoryProductData();">Add</button>
                            </div>
                        </div>  
                    
                    <div id="inventoryVehicleDetailsErrorMessage" class="alert alert-danger" style="display:none;"></div>
                    <div id="inventoryVehicleDetailsSuccessMessage" class="alert alert-success" style="display:none;"></div>
            
                    <div id="current_vehicle_details"></div>    
                    <div id="products_imported_list"></div>
                    <div id="products_paging_links"></div>
                    <div id="vehicle_details_list"></div>
                    
                    <br/>
                    <div class="form-row" >
                        <div id="add_inventory_grn_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id ="pos_add_inventory_grn_submit" name="pos_add_inventory_grn_submit" class="btn btn-dialog" value="Submit" onclick="displayAddInventoryGRN();">Add Inventory GRN</button>&nbsp;&nbsp;
                        <button type="button" id="pos_add_inventory_grn_cancel" name="pos_add_inventory_grn_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                    </div>
                    <br/>
                    @csrf
                </form>
            </div>
        </div>
    </section>
    
    <div class="modal fade data-modal" id="add_vehicle_details_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Vehicle Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addVehicleDetailsSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addVehicleDetailsErrorMessage"></div>

                <form class="" name="addVehicleDetailsForm" id="addVehicleDetailsForm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Vehicle/Airways Number</label>
                                <input id="vehicle_no" type="text" class="form-control" name="vehicle_no" value="" >
                                <div class="invalid-feedback" id="error_validation_vehicle_no"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>No of Containers</label>
                                <select id="containers_count" class="form-control" name="containers_count" onchange="displayVehicleDetailsContainerImages(this.value);">
                                    <option value="">Select</option>
                                    @for($i=1;$i<=1000;$i++):
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_containers_count"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Comments</label>
                                <input id="comments" type="text" class="form-control" name="comments" value="" maxlength="250">
                                <div class="invalid-feedback" id="error_validation_comments"></div>
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-2"><label>Container Images</label></div>
                        </div>
                        <div class="form-row" id="vehicle_details_container_images"></div>
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="vehicle_details_add_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="add_vehicle_details_confirm_cancel" name="add_vehicle_details_confirm_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="add_vehicle_details_confirm_submit" name="add_vehicle_details_confirm_submit" class="btn btn-dialog" onclick="submitAddVehicleDetails();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="inventory_vehicle_detail_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Vehicle Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <form class="" name="vehicleDetailForm" id="vehicleDetailForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Vehicle/Airways Number</label>
                                <input id="vehicle_no_detail" type="text" class="form-control" name="vehicle_no_detail" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>No of Containers</label>
                                <input id="containers_count_detail" type="text" class="form-control" name="containers_count_detail" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Comments</label>
                                <input id="comments_detail" type="text" class="form-control" name="comments_detail"  readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>GRN Comments</label>
                                <input id="grn_comments_detail" type="text" class="form-control" name="grn_comments_detail"  readonly="true">
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-2"><label>Container Images</label></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12" id="container_images_detail">
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-12" id="po_detail_products_list">
                            </div>
                        </div>   
                    </div>
                    <div class="modal-footer center-footer">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm_delete_vehicle_detail" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteVehicleDetailsErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="deleteVehicleDetailsSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Vehicle Details<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete_vehicle_detail_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_vehicle_detail_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_vehicle_detail_btn" name="delete_vehicle_detail_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

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

    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/warehouse_po.js') }}" ></script>
@endsection
