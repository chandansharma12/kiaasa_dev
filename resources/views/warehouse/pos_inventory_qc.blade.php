@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard'),array('name'=>'SOR Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'SOR Purchase Order QC')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'SOR Purchase Order QC ('.$po_data->order_no.')'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <span id="po_no_span" style="font-size: 16px;" class="elem-hidden"></span>
            <input type="hidden" name="po_id" id="po_id" value="{{$po_data->id}}">
            <div id="qcPosInventoryErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="qcPosInventorySuccessMessage" class="alert alert-success" style="display:none;"></div>
            
            <div id="products_qc_div" >
                
                <form  name="qcPosInventoryFrm" id="qcPosInventoryFrm" method="POST" enctype="multipart/form-data">
                    Total Inventory: {{$inventory_status_data['inventory_import_pending']+$inventory_status_data['inventory_imported']}} 
                    | Imported: {{$inventory_status_data['inventory_imported']}} | Not Imported: {{$inventory_status_data['inventory_import_pending']}}
                     
                    <div class="separator-10"></div>
                    
                    {{--@if($inventory_status_data['inventory_import_pending'] == 0)
                        @if($inventory_qc_data['inventory_qc_pending'] > 0)--}}
                        @if($inventory_qc_data['inventory_qc_pending'] > 0)
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
                                    <button type="button" id="pos_add_inventory_submit" name="pos_add_inventory_submit" class="btn btn-dialog" value="Submit" onclick="addInventoryProductQC();">Add</button>
                                </div>
                            </div>  
                        @endif
                        {{--@endif
                    @endif--}}
                    
                    @if($inventory_status_data['inventory_imported'] > 0)
                        QC Status: {{ucwords($po_data->qc_status)}} &nbsp; | &nbsp; 
                        Total Inventory: {{$inventory_qc_data['inventory_total']}} &nbsp; | &nbsp; 
                        Accepted Inventory: {{(isset($inventory_qc_data['inventory_accepted']))?$inventory_qc_data['inventory_accepted']:0}} &nbsp; | &nbsp; 
                        Defective Inventory: {{(isset($inventory_qc_data['inventory_defective']))?$inventory_qc_data['inventory_defective']:0}} &nbsp; | &nbsp; 
                        QC Pending Inventory: {{(isset($inventory_qc_data['inventory_qc_pending']))?$inventory_qc_data['inventory_qc_pending']:0}}
                        <div class="separator-10"></div>
                    @endif

                    <div id="products_qc_list"></div>
                    <div id="products_paging_links"></div>
                    <div class="separator-10"></div>

                    {{--@if($inventory_status_data['inventory_import_pending'] == 0)
			@if($inventory_qc_data['inventory_qc_pending'] > 0 )--}}
                        @if($inventory_qc_data['inventory_qc_pending'] > 0)
                            <div class="form-row" >
                                <div id="add_pos_inventory_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                                <button type="button" id ="pos_add_inventory_submit" name="pos_add_inventory_submit" class="btn btn-dialog" value="Submit" onclick="displayConfirmQCInventory();">Complete QC</button>&nbsp;&nbsp;
                                <button type="button" id="pos_add_inventory_cancel" name="pos_add_inventory_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                            </div>
                        @endif
                        {{--@endif
                    @endif--}}
                    
                    
                    @csrf
                    
                    @if(!empty($qc_list))
                        <div class="separator-10"></div>
                        <h6>QC List</h6>
                        <div class="table-responsive table-filter">
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead><tr class="header-tr"><th>Total Inventory</th><th>Accepted</th><th>Defective</th><th>Returned</th><th>Comments</th><th>Created On</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php  ?>
                                    @for($i=0;$i<count($qc_list);$i++)
                                        <?php $qc_data = json_decode($qc_list[$i]['other_data'],true); ?>
                                        <tr>
                                            <td>{{$qc_data['total']}}</td>
                                            <td>{{$qc_data['accepted']}}</td>
                                            <td>{{$qc_data['defective']}}</td>
                                            <td>{{($qc_list[$i]['po_detail_id'] == 1)?'Yes':'No'}}</td>
                                            <td>{{$qc_list[$i]['comments']}}</td>
                                            <td>{{date('d M Y H:i',strtotime($qc_list[$i]['created_at']))}}</td>
                                            <td>
                                                @if($qc_data['defective'] > 0 && $qc_list[$i]['po_detail_id'] == 0)
                                                    <input type="button" name="return_inv_btn" id="return_inv_btn" value="Return" class="btn btn-dialog" onclick="returnInventoryToVendor({{$qc_list[$i]['id']}});">
                                                @endif
                                            </td>
                                        </tr>
                                        <?php ?>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    @endif                                   
                    
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

                <div class="alert alert-success alert-dismissible" style="display:none" id="qcInventoryConfirmSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="qcInventoryConfirmErrorMessage"></div>

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

    <div class="modal fade data-modal" id="return_inventory_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Return Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="inventoryReturnSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="inventoryReturnErrorMessage"></div>

                <form class="" name="inventoryReturnForm" id="inventoryReturnForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                Are you sure to Return defective Inventory to Vendor?
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="qc_return_id" id="qc_return_id" value="" />
                        <div id="inventory_return_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="inventory_return_confirm_cancel" name="inventory_return_confirm_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="inventory_return_confirm_submit" name="inventory_return_confirm_submit" class="btn btn-dialog" onclick="submitReturnInventoryToVendor();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js') }}" ></script>
<script type="text/javascript">
    $(document).ready(function(){
       loadPOQCInventory(1); 
    });
</script>
@endsection
