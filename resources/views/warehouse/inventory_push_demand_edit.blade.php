@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard'),array('name'=>'Demands List','link'=>'warehouse/demand/inventory-push/list'),array('name'=>'Edit Inventory Push Demand')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Inventory Push Demand'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    @if(strtolower($demand_data->demand_status) == 'warehouse_loading')
        <section class="product_area">
            <div class="container-fluid" >
                
                <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
                <input type="hidden" name="store_id" id="store_id" value="{{$demand_data->store_id}}">

                <div id="products_import_div" >

                        <div class="separator-10"></div>
                        
                        @if(!$is_fake_inventory_user)
                            <form class="" name="importPosInventoryFrm" id="importPosInventoryFrm" method="POST" enctype="multipart/form-data">
                            <div class="form-row ">
                                <div class="form-group col-md-2">
                                    <label>Piece Barcode</label>
                                    <input type="text" name="piece_barcode_inv_push_demand" id="piece_barcode_inv_push_demand" class="form-control " autofocus="true">
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

                                <div class="form-group col-md-2">
                                    <label>&nbsp;</label>
                                    <!--<button type="button" id="pos_push_inventory_submit" name="pos_push_inventory_submit" class="btn btn-dialog" value="Submit" onclick="addInventoryPushDemandProductData();">Add</button>
                                    <div id="productAddedSuccessMessage" class="alert alert-success elem-hidden" style="padding: .45rem 1.25rem;margin-bottom: 0;"></div>-->
                                    <button type="button" id="upload_push_demand_inv_btn" name="upload_push_demand_inv_btn" onclick="importPushDemandInventory();" class="btn btn-dialog" title="Import Demand Inventory"><i title="Import Demand Inventory" class="fas fa-upload fas-icon"></i></button>
                                </div>
                            </div>  
                            @csrf
                            </form>
                        @endif
                        
                        
                        @if($is_fake_inventory_user)
                            <h6>Warehouse Inventory:</h6>
                            <form method="get">
                            <div class="row justify-content-end" >
                                <div class="col-md-2" >
                                    <input type="text" name="prod_name_search" id="prod_name_search" class="form-control" placeholder="Prod Name/Barcode/SKU" value="{{request('prod_name_search')}}">
                                </div>
                                <div class="col-md-2" >
                                    <input name="po_search" id="po_search" class="form-control" placeholder="PO ID/No" value="{{request('po_search')}}" />
                                </div>
                                <div class="col-md-2" >
                                    <select name="category_search" id="category_search"  class="form-control search-select-1" onchange="getPosProductSubcategories(this.value,'search');">
                                        <option value="">Category</option>
                                        @for($i=0;$i<count($category_list);$i++)
                                            <?php if($category_list[$i]['id'] == request('category_search')) $sel = 'selected';else $sel = ''; ?>    
                                            <option {{$sel}} value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                                        @endfor    
                                    </select>
                                </div>
                                <div class="col-md-1" >
                                    <select name="size_search" id="size_search"  class="form-control" >
                                        <option value="">Size</option>
                                        @for($i=0;$i<count($size_list);$i++)
                                            <?php if($size_list[$i]['id'] == request('size_search')) $sel = 'selected';else $sel = ''; ?>    
                                            <option {{$sel}} value="{{$size_list[$i]['id']}}">{{$size_list[$i]['size']}}</option>
                                        @endfor    
                                    </select>
                                </div>
                                <div class="col-md-2" >
                                    <select name="color_search" id="color_search"  class="form-control search-select-1">
                                        <option value="">Color</option>
                                        @for($i=0;$i<count($color_list);$i++)
                                            <?php if($color_list[$i]['id'] == request('color_search')) $sel = 'selected';else $sel = ''; ?>    
                                            <option {{$sel}} value="{{$color_list[$i]['id']}}">{{$color_list[$i]['name']}}</option>
                                        @endfor    
                                    </select>
                                </div>
                                
                                <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>
                            </div>
                            </form>
                            <div class="separator-10"></div>
                            <div class="table-responsive table-filter">
                            <table class="table table-striped admin-table" cellspacing="0" style="font-size: 12px;">
                                <thead>
                                    <tr class="header-tr">
                                    <th>
                                        <input type="checkbox" name="pos_product_list_all" id="pos_product_list_all"  value="1" onclick="checkAllCheckboxes(this,'pos_product-list');">ID
                                    </th>    
                                    <th>Prod Name</th>
                                    <th>Piece Barcode</th>
                                    <th>Prod Barcode</th>
                                    <th>SKU</th>
                                    <th>PO</th>
                                    <th>Category</th>
                                    <th>SubCategory</th>
                                    <th>Base Price</th>
                                    <th>MRP</th>
                                   <th>Details</th>
                                </tr>
                            </thead>
                                <tbody>
                                    <?php  ?>
                                    @for($i=0;$i<count($inventory_list);$i++)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="pos_product_list" id="pos_product_list_{{$inventory_list[$i]->id}}" class="pos_product-list-chk" value="{{$inventory_list[$i]->id}}"> 
                                                {{$inventory_list[$i]->id}}
                                            </td>
                                            <td>{{$inventory_list[$i]->product_name}} {{$inventory_list[$i]->size_name}} {{$inventory_list[$i]->color_name}}</td>
                                            <td>{{$inventory_list[$i]->peice_barcode}}</td>
                                            <td>{{$inventory_list[$i]->product_barcode}}</td>
                                            <td>{{$inventory_list[$i]->product_sku}}</td>
                                            <td>{{$inventory_list[$i]->po_order_no}}</td>
                                            <td>{{$inventory_list[$i]->category_name}}</td>
                                            <td>{{$inventory_list[$i]->subcategory_name}}</td>
                                            <td>{{$inventory_list[$i]->base_price}}</td>
                                            <td>{{$inventory_list[$i]->sale_price}}</td>
                                            <td><a href="{{url('pos/product/inventory/detail/'.$inventory_list[$i]->id)}}" ><i title="Inventory Details" class="far fa-eye"></i></a></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                            {{ $inventory_list->withQueryString()->links() }} <p>Displaying {{$inventory_list->count()}} of {{ $inventory_list->total() }} products.</p>
                            
                            <button type="button" id="add_fake_push_demand_inv_btn" name="add_fake_push_demand_inv_btn" class="btn btn-dialog" value="Submit" onclick="addFakePushDemandInventory();">Add to Demand</button>
                            </div>
                        @endif
                        
                        <div style="height:48px;">
                            <span id="importPosInventoryErrorMessage" class="alert alert-danger product-added-span elem-hidden"></span>
                            <span id="importPosInventorySuccessMessage" class="alert alert-success product-added-span elem-hidden"></span>
                        </div>
                        
                        <hr/>
                        <h6>Push Demand Inventory</h6>
                        <div id="products_imported_list"></div>
                        <div id="products_paging_links"></div>
                        <br/>
                        
                        <div class="form-row" >
                            <button type="button" id="delete_inventory_push_demand_submit" name="delete_inventory_push_demand_submit" class="btn btn-dialog" value="Submit" onclick="deleteInventoryPushDemandItems();">Delete</button>&nbsp;&nbsp;
                        </div>
                        <br/>
                        <div class="form-row" >
                            <div id="add_pos_inventory_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id ="close_inventory_push_demand_submit" name="close_inventory_push_demand_submit" class="btn btn-dialog" value="Submit" onclick="closeInventoryPushDemand();">Close Demand</button>&nbsp;&nbsp;
                            <button type="button" id="close_inventory_push_demand_cancel" name="close_inventory_push_demand_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                        </div>
                        <br/>
                        
                </div>
            </div>
        </section>
    @endif
    
    <div class="modal fade data-modal" id="closeInventoryPushDemandDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Close Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"   id="closeInventoryPushDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="closeInventoryPushDemandErrorMessage"></div>

                <form class="" name="closeInventoryPushDemandForm" id="closeInventoryPushDemandFormForm" type="POST" >
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
                                <label>Store</label>
                                <input id="demand_prev_store_name" type="text" class="form-control" name="demand_prev_store_name" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Store GST No</label>
                                <input id="demand_prev_store_gst_no" type="text" class="form-control" name="demand_prev_store_gst_no" value="" readonly="true">
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
                            <div class="form-group col-md-3" >
                                <label>LR No</label>
                                <input id="lr_no" type="text" class="form-control" name="lr_no" value="" >
                                <div class="invalid-feedback" id="error_validation_lr_no"></div>
                            </div>
                        </div>    
                        
                        <?php /* ?>
                        <div class="form-row">
                            <div class="form-group col-md-4" >
                                <label>Demand Specific Discount</label>
                                <select id="discount_applicable" class="form-control" name="discount_applicable" >
                                    <option value="">Select</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>    
                                <div class="invalid-feedback" id="error_validation_discount_applicable"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Discount Percent</label>
                                <input id="discount_percent" type="text" class="form-control" name="discount_percent" value="" >
                                <div class="invalid-feedback" id="error_validation_discount_percent"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>GST Inclusive</label>
                                <select id="gst_inclusive" class="form-control" name="gst_inclusive" >
                                    <option value="">Select</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>    
                                <div class="invalid-feedback" id="error_validation_gst_inclusive"></div>
                            </div>
                        </div>   <?php */ ?> 
                        
                        
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="closeInventoryPushDemandSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="closeInventoryPushDemandCancel" name="closeInventoryPushDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="closeInventoryPushDemandSubmit" name="closeInventoryPushDemandCancelSubmit" class="btn btn-dialog" onclick="submitCloseInventoryPushDemand();">Submit</button>
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
                        </div>    
                        <div class="form-row">
                            <div class="form-group col-md-2"><label>Container Images</label></div>
                        </div>
                        <div  id="container_images_detail">
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="push_demand_delete_items_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="deletePushDemandItemsErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="deletePushDemandItemsSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Push Demand Items ?<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_push_demand_items_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_push_demand_items_btn" name="delete_push_demand_items_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="push_demand_delete_error_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="modal-body">
                    <h6>Please select Push Demand Items<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_push_demand_items_cancel">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="push_demand_add_items_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="addPushDemandItemsErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="addPushDemandItemsSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to add Push Demand Items ?<br/></h6>
                    <span id="name_add_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="add_push_demand_items_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="add_push_demand_items_btn" name="add_push_demand_items_btn">Add</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal" id="importPushDemandInventoryDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" >
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Import Push Demand Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="importPushDemandInventoryErrorMessage" style="max-height:300px;overflow-y: scroll; "></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="importPushDemandInventorySuccessMessage"></div>
                
                <form method="post" name="importPushDemandInventoryForm" id="importPushDemandInventoryForm">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>QR Code Text File</label>
                            <input type="file" name="barcodeTxtFile" id="barcodeTxtFile" class="form-control"  />
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="importPushDemandInventorySpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="importPushDemandInventoryCancel" id="importPushDemandInventoryCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="importPushDemandInventorySubmit" id="importPushDemandInventorySubmit" value="Submit" class="btn btn-dialog" onclick="submitImportPushDemandInventory();">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')

<script src="{{ asset('js/warehouse_po.js?v=2.20') }}" ></script>
<script type="text/javascript">
    var page_type = 'edit';
    $(document).ready(function(){
        loadInventoryPushDemandInventory(1);
    });
    
</script>
@endsection
