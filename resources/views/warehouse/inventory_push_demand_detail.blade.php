@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Demands List','link'=>'warehouse/demand/inventory-push/list'),array('name'=>'Inventory Push Demand Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Push Demand Detail'); ?>
    <?php $size_data = array(); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                    
                    <div class="form-group col-md-2">
                        <label for="Color">Invoice  No</label>						
                        {{$demand_data->invoice_no}}     
                    </div>

                    <div class="form-group col-md-2">
                        <label for="Product">Status</label>						
                        {{ucwords(str_replace('_',' ',$demand_data->demand_status))}}    
                    </div>

                    <div class="form-group col-md-2">
                        <label for="Product">Store</label>						
                        {{$demand_data->store_name}} ({{$demand_data->store_id_code}})    
                    </div>
                
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($demand_data->created_at))}}    
                    </div> 

                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$demand_data->user_name}}    
                    </div> 
                    
                    <div class="form-group col-md-2">
                        <label for="Color">Comments</label>						
                        {{$demand_data->comments}}     
                    </div>
                    
                    @if(!empty($demand_data->crn_user_name))
                        <div class="form-group col-md-2">
                            <label for="Category">Closed by</label>						
                            {{$demand_data->crn_user_name}}    
                        </div> 
                    @endif
                    
                    @if(in_array(strtolower($demand_data->demand_status),array('warehouse_dispatched','store_loading','store_loaded')))
                        @if($user->user_type == 6)
                            <div class="form-group col-md-2">
                                <label for="Color">Gate Pass Info</label>						
                                <button type="button" id ="gate_pass_info_btn" name="gate_pass_info_btn" class="btn btn-dialog" onclick="editGatePassData();"><i title="Edit Gate Pass Info" class="far fas-icon fa-edit"></i> Gate Pass Info</button>
                            </div> 
                        @endif
                        <div class="form-group col-md-2">
                            <label for="Color">Gate Pass PDF</label>						
                            <a type="button" id ="gate_pass_pdf_btn" name="gate_pass_pdf_btn" class="btn btn-dialog" href="{{url('warehouse/demand/inventory-push/gatepass/'.$demand_data->id)}}"><i title="Download Gate Pass PDF" class="fa fas-icon fa-download"></i> Gate Pass PDF</a>
                        </div>
                    @endif
                
                    @if(in_array(strtolower($demand_data->demand_status),array('warehouse_dispatched','store_loading','store_loaded','cancelled')))
                        <div class="form-group col-md-2">
                            <label for="Color">Invoice  </label>						
                            <a href="{{url('warehouse/demand/inventory-push/invoice/'.$demand_data->id)}}" class="btn  btn-pdf"><i title="Download Invoice PDF" class="fa fas-icon fa-download"></i> Invoice PDF</a>&nbsp;
                        </div>
                    @endif
                    
                    @if(!empty($demand_data->receive_docket_no))
                        <div class="form-group col-md-2">
                            <label for="Category">Receive Docket No</label>						
                            {{$demand_data->receive_docket_no}}    
                        </div> 
                        <div class="form-group col-md-2">
                            <label for="Category">Receive Date</label>						
                            {{date('d M Y',strtotime($demand_data->receive_date))}}    
                        </div>
                    @endif
                    
                    @if(!empty($gate_pass_data))
                        <div class="form-group col-md-2">
                            <label for="Color">Boxes Count</label>						
                            {{$gate_pass_data->boxes_count}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Transporter Name</label>						
                            {{$gate_pass_data->transporter_name}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Transporter GST</label>						
                            {{$gate_pass_data->transporter_gst}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Docket No</label>						
                            {{$gate_pass_data->docket_no}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">EWay Bill No</label>						
                            {{$gate_pass_data->eway_bill_no}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">LR No</label>						
                            {{$gate_pass_data->lr_no}}     
                        </div>
                    @endif
                    
                    @if($inventory_received_count == 0 && in_array(strtolower($demand_data->demand_status),array('warehouse_loading','warehouse_dispatched','store_loading')))
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Push Demand</label>		
                            <button type="button" id="cancel_push_demand_btn" name="cancel_push_demand_btn" class="btn btn-dialog" onclick="cancelInventoryPushDemand();"><i title="Cancel Push Demand" class="far fas-icon fa-close"></i> Cancel Push Demand</button>
                        </div>
                    @endif
                    
                    @if(strtolower($demand_data->demand_status) == 'cancelled')
                        <div class="form-group col-md-2">
                            <label for="Color">Cancelled by</label>						
                            {{$demand_data->cancel_user_name}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Comments</label>						
                            {{$demand_data->cancel_comments}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Date</label>						
                            {{date('d M Y H:i',strtotime($demand_data->cancel_date))}}     
                        </div>
                    @endif
                    
                    <div class="form-group col-md-2">
                        <label for="Color">Discounts List</label>		
                        <a class="btn btn-dialog" href="{{url('inventory-push/demand/discount/list/'.$demand_data->id)}}"><i title="Discounts List" class="far fas-icon fa-close"></i> Discounts List</a>
                    </div>
                    
                    @if(strtolower($demand_data->demand_status) == 'store_loaded' && $inventory_total_count > $inventory_received_count)
                        <div class="form-group col-md-2">
                            <label for="Color">Credit Note</label>						
                            @if(!empty($debit_note))
                                <a href="{{url('store/demand/inventory-push/debit-note/invoice/'.$debit_note->id)}}/2" class="btn btn-pdf"><i title="Download Credit Note" class="fa fas-icon fa-download"></i> Credit Note</a> 
                            @endif
                        </div>
                    @endif
                    
                    @if($is_fake_inventory_user && (strtolower($demand_data->demand_status) == 'warehouse_dispatched' || strtolower($demand_data->demand_status) == 'store_loading'))
                        <div class="form-group col-md-2">
                            <label for="Color">Load in Store</label>		
                            <a href="javascript:;" onclick="loadFakeInventoryDemandInStore();" class="btn  btn-pdf"><i title="Load in Store" class="fa fas-icon fa-download"></i> Load in Store</a> 
                        </div>
                    @endif
                    
                    @if($user->user_type == 6 && strtolower($demand_data->demand_status) == 'warehouse_dispatched' && $demand_data->demand_edited == 0)
                        <div class="form-group col-md-2">
                            <label for="Color">Edit Push Demand</label>		
                            <button type="button" id="edit_push_demand_btn" name="edit_push_demand_btn" class="btn btn-dialog" onclick="editInventoryPushDemand();"><i title="Edit Push Demand" class="far fas-icon fa-edit"></i> Edit Push Demand</button>
                        </div>
                    @endif
                    
                    @if($user->user_type == 1 && (strtolower($demand_data->demand_status) == 'store_loaded'))
                        <div class="form-group col-md-2">
                            <label for="Color">Open Demand</label>		
                            <a href="javascript:;" onclick="openInventoryPushDemand();" class="btn  btn-dialog" title="Reopen Demand for Store">Open Demand</a> 
                        </div>
                    @endif
                </div>
                
                <input type="hidden" name="demand_id_hdn" id="demand_id_hdn" value="{{$demand_data->id}}">
                <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
                <input type="hidden" name="store_id" id="store_id" value="{{$demand_data->store_id}}">
            </form> 
            <hr/>

            <div class="separator-10"></div>
            
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <h5>Products List</h5>
                    <div class="table-responsive table-filter">
                        
                        <?php 
                            $total_size = $count = 0; 
                        ?>

                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th>S No</th><th>Product</th><th>SKU</th><th>Color</th><th>HSN Code</th>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <th>{{$size_list[$i]['size']}}</th>
                                    @endfor    
                                </tr>
                            </thead>
                            <tbody>
                                
                                @foreach($products_sku as $sku=>$product_data)
                                    <tr>
                                        <td>{{++$count}}</td>
                                        <td>{{$product_data->product_name}}</td>
                                        <td>{{(!empty($product_data->vendor_sku))?$product_data->vendor_sku:$product_data->product_sku}}</td>
                                        <td>{{$product_data->color_name}}</td>
                                        <td>{{$product_data->hsn_code}}</td>
                                        @for($q=0;$q<count($size_list);$q++)
                                            <?php $key = strtolower($product_data->product_sku).'_'.$size_list[$q]['id']; ?>
                                            <td>{{$cnt = (isset($products[$key]))?$products[$key]:0 }}</td>
                                            <?php if(isset($size_data[$size_list[$q]['id']]['count'])) $size_data[$size_list[$q]['id']]['count']+=$cnt;else $size_data[$size_list[$q]['id']]['count'] = $cnt; ?>
                                        @endfor    
                                    </tr>
                                @endforeach
                               
                                <tr>
                                    <th colspan="5">Total</th>
                                    @for($q=0;$q<count($size_list);$q++)
                                        <th>{{$size_data[$size_list[$q]['id']]['count']}}</th>
                                        <?php $total_size+=$size_data[$size_list[$q]['id']]['count']; ?>
                                    @endfor    
                                </tr>
                                <tr>
                                    <th colspan="5">Total</th>
                                    <th colspan="{{count($size_list)}}" align="center" style="text-align: center;">{{$total_size}}</th>
                                </tr>
                                
                            </tbody>
                        </table>
                        
                    </div>
                    
                    
                    <hr/>
                    <h6>Products Inventory</h6>
                    <div id="products_imported_list">
                        
                    </div>
                    <div id="products_paging_links"></div>
                    <br/>

                    <!--<div class="form-row" >
                        <button type="button" id="delete_inventory_push_demand_submit" name="delete_inventory_push_demand_submit" class="btn btn-dialog" value="Submit" onclick="deleteInventoryPushDemandItems();">Delete</button>&nbsp;&nbsp;
                    </div>-->
                    
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

            </div>
        </div>
    </section>
    
    @if(!empty($gate_pass_data))
        <div class="modal fade data-modal" id="editGatePassDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLongTitle">Gate Pass Information</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                    </div>

                    <div class="alert alert-success alert-dismissible elem-hidden"  id="editGatePassSuccessMessage"></div>
                    <div class="alert alert-danger alert-dismissible elem-hidden"  id="editGatePassErrorMessage"></div>

                    <form class="" name="editGatePassForm" id="editGatePassForm" type="POST" >
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>No of Boxes</label>
                                    <input id="boxes_count" type="text" class="form-control" name="boxes_count" value="{{$gate_pass_data->boxes_count}}" >
                                    <div class="invalid-feedback" id="error_validation_boxes_count"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Transporter Name</label>
                                    <input id="transporter_name" type="text" class="form-control" name="transporter_name" value="{{$gate_pass_data->transporter_name}}">
                                    <div class="invalid-feedback" id="error_validation_transporter_name"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Transporter GST</label>
                                    <input id="transporter_gst" type="text" class="form-control" name="transporter_gst" value="{{$gate_pass_data->transporter_gst}}">
                                    <div class="invalid-feedback" id="error_validation_transporter_gst"></div>
                                </div>
                                 <div class="form-group col-md-3" >
                                    <label>Docket No</label>
                                    <input id="docket_no" type="text" class="form-control" name="docket_no" value="{{$gate_pass_data->docket_no}}" >
                                    <?php /* ?> <input type="hidden" name="demand_id" id="demand_id" value="{{$gate_pass_data->demand_id}}"> <?php */ ?>
                                    <div class="invalid-feedback" id="error_validation_docket_no"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Eway Bill No</label>
                                    <input id="eway_bill_no" type="text" class="form-control" name="eway_bill_no" value="{{$gate_pass_data->eway_bill_no}}" >
                                    <div class="invalid-feedback" id="error_validation_eway_bill_no"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>LR No</label>
                                    <input id="lr_no" type="text" class="form-control" name="lr_no" value="{{$gate_pass_data->lr_no}}" >
                                    <div class="invalid-feedback" id="error_validation_lr_no"></div>
                                </div>
                            </div>    
                            
                            <div class="form-row">
                                <div class="form-group col-md-4" >
                                    <label>Demand Specific Discount</label>
                                    <select id="discount_applicable" class="form-control" name="discount_applicable" >
                                        <option value="">Select</option>
                                        <option value="1" @if($demand_data->discount_applicable == 1) selected @endif>Yes</option>
                                        <option value="0" @if($demand_data->discount_applicable == 0) selected @endif>No</option>
                                    </select>    
                                    <div class="invalid-feedback" id="error_validation_discount_applicable"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Discount Percent</label>
                                    <input id="discount_percent" type="text" class="form-control" name="discount_percent" value="{{$demand_data->discount_percent}}" >
                                    <div class="invalid-feedback" id="error_validation_discount_percent"></div>
                                </div>
                                <div class="form-group col-md-4" >
                                    <label>GST Inclusive</label>
                                    <select id="gst_inclusive" class="form-control" name="gst_inclusive" >
                                        <option value="">Select</option>
                                        <option value="1" @if($demand_data->gst_inclusive === 1) selected @endif>Yes</option>
                                        <option value="0" @if($demand_data->gst_inclusive === 0) selected @endif>No</option>
                                    </select>    
                                    <div class="invalid-feedback" id="error_validation_gst_applicable"></div>
                                </div>
                            </div>    
                            
                             <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Ship To</label>
                                    <input id="ship_to" type="text" class="form-control" name="ship_to" value="{{$gate_pass_data->ship_to}}" >
                                    <div class="invalid-feedback" id="error_validation_ship_to"></div>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Dispatch From</label>
                                    <input id="dispatch_by" type="text" class="form-control" name="dispatch_by" value="{{$gate_pass_data->dispatch_by}}" >
                                    <div class="invalid-feedback" id="error_validation_dispatch_by"></div>
                                </div>
                            </div>     
                        </div>
                        <div class="modal-footer center-footer">
                            <div id="editGatePassFormSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id="editGatePassCancel" name="editGatePassCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" id ="editGatePassSubmit" name="editGatePassSubmit" class="btn btn-dialog" onclick="submitEditGatePassData();">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    <div class="modal fade" id="push_demand_cancel_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Cancel Push Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="cancelPushDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="cancelPushDemandErrorMessage"></div>
                
                <form class="" name="cancelPushDemandForm" id="cancelPushDemandForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Comments</label>
                                <input id="cancel_comments" type="text" class="form-control" name="cancel_comments" value="" placeholder="Comments" maxlength="250">
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="cancelPushDemandSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="cancelPushDemandCancel" name="cancelPushDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="cancelPushDemandSubmit" name="cancelPushDemandSubmit" class="btn btn-dialog" onclick="submitCancelInventoryPushDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="edit_push_demand_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Push Demand Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editPushDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editPushDemandErrorMessage"></div>

                <form name="editPushDemandForm" id="editPushDemandForm" method="POST" >
                    <div class="modal-body">
                        <a id="add_push_demand_inv_btn" name="add_push_demand_inv_btn" class="btn btn-dialog" href="javascript:;" onclick="toggleEditPushDemand('add');"><i title="Add" class="fa fas-icon fa-plus" ></i> Add Inventory</a>
                        <a id="delete_push_demand_inv_btn" name="delete_push_demand_inv_btn" class="btn btn-dialog" href="javascript:;" onclick="toggleEditPushDemand('delete');" style="opacity:0.6;"><i title="Add" class="fa fas-icon fa-trash"></i> Delete Inventory</a>
                        <div class="separator-10">&nbsp;</div>
                        <div class="form-row" id="edit_push_demand_qr_code_add_div">
                            @for($i=1;$i<=20;$i++)
                                <div class="form-group col-md-3">
                                    <label></label>
                                    <input id="qr_code_add_{{$i}}" type="text" class="form-control edit-demand-qr-code" name="qr_code_add_{{$i}}" value="" placeholder="Add QR Code {{$i}}" maxlength="25">
                                    <div class="invalid-feedback" id="error_validation_qr_code_add_{{$i}}"></div>
                                </div>
                            @endfor
                        </div>
                        
                        <div class="form-row elem-hidden" id="edit_push_demand_qr_code_delete_div">
                            @for($i=1;$i<=20;$i++)
                                <div class="form-group col-md-3">
                                    <label></label>
                                    <input id="qr_code_delete_{{$i}}" type="text" class="form-control edit-demand-qr-code" name="qr_code_delete_{{$i}}" value="" placeholder="Delete QR Code {{$i}}" maxlength="25">
                                    <div class="invalid-feedback" id="error_validation_qr_code_delete_{{$i}}"></div>
                                </div>
                            @endfor
                        </div>                    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="edit_push_demand_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editPushDemandCancel" name="editPushDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editPushDemandSubmit" name="editPushDemandSubmit" class="btn btn-dialog" onclick="submitEditInventoryPushDemand();">Submit</button>
                        <input type="hidden" name="edit_demand_add_total" id="edit_demand_add_total" value="20"> 
                        <input type="hidden" name="edit_demand_delete_total" id="edit_demand_delete_total" value="20"> 
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="push_demand_load_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Load Push Demand in Store</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="loadPushDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="loadPushDemandErrorMessage"></div>
                
                <form class="" name="loadPushDemandForm" id="loadPushDemandForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Comments</label>
                                <input id="load_demand_comments" type="text" class="form-control" name="load_demand_comments" value="" placeholder="Comments" maxlength="250">
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="loadPushDemandSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="loadPushDemandCancel" name="loadPushDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="loadPushDemandSubmit" name="loadPushDemandSubmit" class="btn btn-dialog" onclick="submitLoadFakeInventoryDemandInStore();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="push_demand_open_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Open Push Demand for Store</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="openPushDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="openPushDemandErrorMessage"></div>
                
                <form class="" name="openPushDemandForm" id="openPushDemandForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Comments</label>
                                <input id="open_comments" type="text" class="form-control" name="open_comments" value="" placeholder="Comments" maxlength="250">
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="openPushDemandSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="openPushDemandCancel" name="openPushDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="openPushDemandSubmit" name="openPushDemandSubmit" class="btn btn-dialog" onclick="submitOpenInventoryPushDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=1.751') }}" ></script>
<script type="text/javascript">
    var page_type = 'detail';
    $(document).ready(function(){
        loadInventoryPushDemandInventory(1);
    });
    
</script>
@endsection
