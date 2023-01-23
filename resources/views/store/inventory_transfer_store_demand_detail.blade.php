@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Demands List','link'=>'store/demand/inventory-transfer-store/list'),array('name'=>'Store to Store Inventory Transfer Demand Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store to Store Inventory Transfer Demand Detail'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                    @if($pdf_type == 'invoice_challan')
                        <div class="form-group col-md-2">
                            <label for="Color">Invoice  No</label>						
                            {{$demand_data->invoice_no}}     
                        </div>
                    @endif
                    
                    @if($pdf_type == 'debit_credit_note')
                        @if($user->user_type == 1 || ($user->user_type == 9 && $store_user->store_info_type == 2))
                            <div class="form-group col-md-2">
                                <label for="Color">Debit Note No</label>						
                                {{$demand_data->invoice_no}}     
                            </div>
                        @endif

                        @if($user->user_type == 1 || ($user->user_type == 9 && $store_user->store_info_type == 3))
                            <div class="form-group col-md-2">
                                <label for="Color">Credit Note No</label>						
                                {{$demand_data->credit_invoice_no}}     
                            </div>
                        @endif
                    @endif
                    <div class="form-group col-md-2">
                        <label for="Product">Status</label>						
                        {{CommonHelper::getDemandStatusText('inventory_transfer_to_store',$demand_data->demand_status)}}
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">From Store</label>						
                        {{$demand_data->from_store_name}} ({{$demand_data->from_store_id_code}})      
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="Product">To Store</label>						
                        {{$demand_data->to_store_name}} ({{$demand_data->to_store_id_code}})      
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
                        <label for="Category">Comments</label>						
                        {{$demand_data->comments}}    
                    </div> 
                    
                    @if(strtolower($demand_data->demand_status) != 'loading' && strtolower($demand_data->demand_status) != 'cancelled')
                        @if($user->user_type == 9)
                            @if($store_user->id == $demand_data->from_store_id)
                                <div class="form-group col-md-2">
                                    <label for="Color">Gate Pass Info</label>						
                                    <button type="button" id ="gate_pass_info_btn" name="gate_pass_info_btn" class="btn btn-dialog" onclick="editDemandTransportationData();"><i title="Edit Gate Pass Info" class="far fas-icon fa-edit"></i> Transport Details</button>
                                </div> 
                            @endif
                            
                            <div class="form-group col-md-2">
                                <label for="Color">Gate Pass PDF</label>						
                                <a type="button" id ="gate_pass_pdf_btn" name="gate_pass_pdf_btn" class="btn btn-dialog" href="{{url('store/demand/inventory-transfer-store/list?action=get_gate_pass_pdf&id='.$demand_data->id)}}"><i title="Download Transportation PDF" class="fa fas-icon fa-download"></i> Transportation PDF</a>
                            </div>
                        @endif
                    @endif
                    
                    @if(strtolower($demand_data->demand_status) != 'loading' )
                        @if($pdf_type == 'invoice_challan')
                            <div class="form-group col-md-2">
                                <label for="Color">Invoice PDF</label>						
                                <a href="{{url('store/demand/inventory-transfer-store/invoice/'.$demand_data->id)}}" class="btn btn-pdf" title="Download Invoice PDF"><i title="Download Invoice PDF" class="fas fa-download" style="color: #fff;"></i> Invoice PDF</a>&nbsp;
                            </div>
                        @endif
                        
                        @if($pdf_type == 'debit_credit_note')
                            @if($user->user_type == 1 || ($user->user_type == 9 && $store_user->store_info_type == 2))
                                <div class="form-group col-md-2">
                                    <label for="Color">Debit Note PDF</label>						
                                    <a href="{{url('store/demand/inventory-transfer-store/invoice/'.$demand_data->id)}}" class="btn btn-pdf" title="Download Debit Note PDF"><i title="Download Debit Note PDF" class="fa fas-icon fa-download"></i> Debit Note PDF</a>&nbsp;
                                </div>
                            @endif
                            
                            @if($user->user_type == 1 || ($user->user_type == 9 && $store_user->store_info_type == 3))
                                <div class="form-group col-md-2">
                                    <label for="Color">Credit Note PDF</label>						
                                    <a href="{{url('store/demand/inventory-transfer-store/invoice/'.$demand_data->id.'/2')}}" class="btn btn-pdf" title="Download Credit Note PDF"><i title="Download Credit Note PDF" class="fa fas-icon fa-download"></i> Credit Note PDF</a>&nbsp;
                                </div>
                            @endif
                        @endif
                    @endif 
                    
                    @if( in_array(strtolower($demand_data->demand_status),array('loading','loaded')))
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Push Demand</label>		
                            <button type="button" id="cancel_push_demand_btn" name="cancel_push_demand_btn" class="btn btn-dialog" onclick="cancelStoreToStoreTransferInventoryPushDemand({{$demand_data->id}});"><i title="Cancel Demand" class="fa fas-icon fa-crosshairs"></i> Cancel Demand</button>
                        </div>
                    @endif
                    
                    @if(!empty($gate_pass_data) && strtolower($demand_data->demand_status) != 'cancelled')
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
                    @endif
                    
                    @if(strtolower($demand_data->demand_status) == 'cancelled')
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Comments</label>						
                            {{$demand_data->cancel_comments}}     
                        </div>
                    @endif
                    
                    @if($user->user_type == 1 && strtolower($demand_data->demand_status) == 'store_loaded')
                        <div class="form-group col-md-2">
                            <label for="Color">Open Demand</label>						
                            <a href="javascript:;" onclick="openStoreToStoreDemand();" class="btn  btn-dialog" title="Reopen Demand for Store">Open Demand</a> 
                        </div>
                    @endif
                </div>
                
                <input type="hidden" name="demand_id_hdn" id="demand_id_hdn" value="{{$demand_data->id}}">
                <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
            </form> 
            <hr/>
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <h5>Products List</h5>
                    <div class="table-responsive table-filter">
                        <?php $total_size = $count = 0; ?>
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>S No</th><th>Product</th><th>SKU</th><th>Color</th><th>HSN Code</th><th>Barcode</th>
                                @for($i=0;$i<count($size_list);$i++)
                                    <th>{{$size_list[$i]['size']}}</th>
                                @endfor    
                            </tr></thead>
                            <tbody>
                                
                                @foreach($products_sku as $sku=>$product_data)
                                    <tr>
                                        <td>{{++$count}}</td>
                                        <td>{{$product_data->product_name}}</td>
                                        <td>{{(!empty($product_data->vendor_sku))?$product_data->vendor_sku:$product_data->product_sku}}</td>
                                        <td>{{$product_data->color_name}}</td>
                                        <td>{{$product_data->hsn_code}}</td>
                                        <td>{{$product_data->product_barcode}}</td>
                                        @for($q=0;$q<count($size_list);$q++)
                                            <?php $key = strtolower($product_data->product_sku).'_'.$size_list[$q]['id']; ?>
                                            <td>{{$cnt = (isset($products[$key]))?$products[$key]:0 }}</td>
                                            <?php if(isset($size_data[$size_list[$q]['id']]['count'])) $size_data[$size_list[$q]['id']]['count']+=$cnt;else $size_data[$size_list[$q]['id']]['count'] = $cnt; ?>
                                        @endfor    
                                    </tr>
                                @endforeach
                               
                                <tr>
                                    <th colspan="6">Total</th>
                                    @for($q=0;$q<count($size_list);$q++)
                                        <th>{{$size_data[$size_list[$q]['id']]['count']}}</th>
                                        <?php $total_size+=$size_data[$size_list[$q]['id']]['count']; ?>
                                    @endfor    
                                </tr>
                                <tr>
                                    <th colspan="6">Total</th>
                                    <th colspan="{{count($size_list)}}" align="center" style="text-align: center;">{{$total_size}}</th>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>
                    
                    <hr/>
                    <h6>Products Inventory</h6>
                    <!--<div id="products_imported_list"></div>
                    <div id="products_paging_links"></div> -->
                    <br/>
                    
                    <div class="alert alert-danger alert-dismissible elem-hidden"  id="searchInvErrorMessage"></div>
                    <!--<div class="row"><div class="col-md-2"><input type="text" value="" class="form-control" name="barcode_search_inv_push_demand_detail" id="barcode_search_inv_push_demand_detail" placeholder="Peice Barcode"></div><div class="col-md-1"><button type="button" id="barcode_inv_push_demand_detail_search" name="barcode_inv_push_demand_detail_search" class="btn btn-dialog" value="Submit" onclick="searchPushDemandDetailInv();">Search</button></div></div><div class="separator-10"></div>-->
                    <div class="table-responsive table-filter" id="inventory_list_div">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Piece Barcode</th>
                                <th>Product Barcode</th>
                                <th>SKU</th>
                                <th>Base Price</th>
                                <th>Sale Price</th>
                                <th>Status</th>
                            </tr></thead>
                            <tbody id="inventory_table_body">
                                <?php  ?>
                                @for($i=0;$i<count($product_inventory);$i++)
                                    <tr>
                                        <td>{{$product_inventory[$i]->id}}</td>
                                        <td>{{$product_inventory[$i]->product_name}} {{$product_inventory[$i]->size_name}} {{$product_inventory[$i]->color_name}}</td>
                                        <td>{{$product_inventory[$i]->peice_barcode}}</td>
                                        <td>{{$product_inventory[$i]->product_barcode}}</td>
                                        <td>{{(!empty($product_inventory[$i]->vendor_sku))?$product_inventory[$i]->vendor_sku:$product_inventory[$i]->product_sku}}</td>
                                        <td>{{$currency}} {{$product_inventory[$i]->store_base_price}}</td>
                                        <td>{{$currency}} {{$product_inventory[$i]->sale_price}}</td>
                                        <td>@if($product_inventory[$i]->product_status > 0) {{strtoupper(CommonHelper::getposProductStatusName($product_inventory[$i]->product_status))}} @endif</td>
                                    </tr>
                                    <?php ?>
                                @endfor
                            </tbody>
                        </table>
                        {{ $product_inventory->withQueryString()->links() }} <p>Displaying {{$product_inventory->count()}} of {{ $product_inventory->total() }} inventory products.</p>
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
                        <h5 class="modal-title" id="exampleModalLongTitle">Transportation Details</h5>
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
                            </div>    
                        </div>
                        <div class="modal-footer center-footer">
                            <div id="editGatePassFormSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                            <button type="button" id="editGatePassCancel" name="editGatePassCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" id ="editGatePassSubmit" name="editGatePassSubmit" class="btn btn-dialog" onclick="submitEditDemandTransportationData();">Submit</button>
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
                        <button type="button" id ="cancelPushDemandSubmit" name="cancelPushDemandSubmit" class="btn btn-dialog" onclick="submitCancelStoreToStoreTransferInventoryPushDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="store_demand_open_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Open Store to Store Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="openStoreDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="openStoreDemandErrorMessage"></div>
                
                <form class="" name="openStoreDemandForm" id="openStoreDemandForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Comments</label>
                                <input id="open_comments" type="text" class="form-control" name="open_comments" value="" placeholder="Comments" maxlength="250">
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="openStoreDemandSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="openStoreDemandCancel" name="openStoreDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="openStoreDemandSubmit" name="openStoreDemandSubmit" class="btn btn-dialog" onclick="submitOpenStoreToStoreDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=2.38') }}" ></script>
<script type="text/javascript">
    var page_type = 'detail';
    $(document).ready(function(){
        //loadInventoryPushDemandInventory(1);
    });
    
</script>
@endsection
