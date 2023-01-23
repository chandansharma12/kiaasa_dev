@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Audit List','link'=>'audit/list'),array('name'=>'Audit Scan')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Audit Scan'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    @if(strtolower($audit_data->audit_status) == 'scan_progress')
        <section class="product_area">
            <div class="container-fluid" >
                
                <input type="hidden" name="audit_id" id="audit_id" value="{{$audit_data->id}}">
                <input type="hidden" name="store_id" id="store_id" value="{{$audit_data->store_id}}">
                <input type="hidden" name="scan_status" id="scan_status" value="1">

                <div id="products_import_div" >

                    <form class="" name="auditInventoryFrm" id="auditInventoryFrm" method="POST" enctype="multipart/form-data">

                       <div class="separator-10"></div>

                            <div class="form-row ">
                                <div class="form-group col-md-2">
                                    <label>Piece Barcode</label>
                                    <input type="text" name="piece_barcode_audit_inv" id="piece_barcode_audit_inv" class="form-control" autofocus="true">
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
                                <!--
                                <div class="form-group col-md-1">
                                    <label>&nbsp;</label>
                                    <button type="button" id="audit_inventory_submit" name="audit_inventory_submit" class="btn btn-dialog" value="Submit" onclick="addAuditInventoryProductData();">Add</button>
                                </div>-->
                            </div>  
                       
                            <div style="height:40px;">
                                <div id="auditInventoryErrorMessage" class="alert alert-danger elem-hidden"></div>
                                <div id="auditInventorySuccessMessage" class="alert alert-success elem-hidden"></div>
                                <span id="product_added_span" class="alert-success product-added-span elem-hidden"  ></span>
                            </div>

                        <hr/><h6>Audit Inventory</h6>
                        <div id="products_imported_list"></div>
                        <div id="products_paging_links"></div>
                        
                        <br/>
                        
                        <div class="form-row" >
                            <button type="button" id="delete_inventory_audit_submit" name="delete_inventory_audit_submit" class="btn btn-dialog" value="Submit" onclick="deleteAuditInventoryItems();">Delete</button>&nbsp;&nbsp;
                        </div>
                        <br/>
                        
                        <div class="form-row" >
                            <div id="audit_complete_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id ="audit_scan_complete_submit" name="audit_scan_complete_submit" class="btn btn-dialog" value="Submit" onclick="completeAuditScan();">Complete Scan</button>&nbsp;&nbsp;
                            <button type="button" id="audit_scan_complete_cancel" name="audit_scan_complete_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                        </div>

                        @csrf
                    </form>
                </div>
            </div>
        </section>
    @endif
    
    <div class="modal fade data-modal" id="completeAuditScanInventoryDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Complete Audit Scan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="completeAuditScanSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="completeAuditScanErrorMessage"></div>
                
                <form class="" name="completeAuditScanInventoryForm" id="completeAuditScanInventoryForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Comments</label>
                                <input id="complete_audit_scan_comment" type="text" class="form-control" name="complete_audit_scan_comment" value="" maxlength="250">
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="audit_complete_confirm_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="audit_scan_complete_confirm_cancel" name="audit_scan_complete_confirm_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="audit_scan_complete_confirm_submit" name="audit_scan_complete_confirm_submit" class="btn btn-dialog" onclick="submitcompleteAuditScan();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="audit_delete_items_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="deleteAuditItemsErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="deleteAuditItemsSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Audit Items ?<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_audit_items_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_audit_items_btn" name="delete_audit_items_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="audit_delete_error_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="modal-body">
                    <h6>Please select Audit Items<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_audit_items_cancel">Close</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')

<script src="{{ asset('js/audit.js?v=1.46') }}" ></script>
<script type="text/javascript">
    var page_type = 'edit';
    $(document).ready(function(){
        loadAuditInventory(1);
    });
    
</script>
@endsection
