@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Return Demands List','link'=>'warehouse/demand/inventory-return-vendor/list'),array('name'=>'Inventory Return Vendor Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Return Vendor Detail'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Demand ID</label>						
                        {{$demand_data->id}}    
                    </div> 
                   
                    <div class="form-group col-md-2">
                        <label for="Season">Invoice No</label>						
                        {{$demand_data->invoice_no}}    
                    </div> 
                    
                     <?php /* ?>
                    @if($user->user_type == 15)
                        <div class="form-group col-md-2">
                            <label for="Season">Credit Note No</label>						
                            {{$demand_data->credit_invoice_no}}    
                        </div> 
                    @endif <?php */ ?>
                    <div class="form-group col-md-2">
                        <label for="Product">Status</label>						
                        {{ucwords(str_replace('_',' ',$demand_data->demand_status))}}    
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y H:i',strtotime($demand_data->created_at))}}    
                    </div> 

                    <div class="form-group col-md-2">
                        <label for="Category">Comments</label>						
                        {{$demand_data->comments}}    
                    </div> 
                    
                    @if($user->user_type == 6 && strtolower($demand_data->demand_status) == 'warehouse_dispatched')
                        <div class="form-group col-md-2">
                            <label for="Color">Gate Pass Info</label>						
                            <button type="button" id ="gate_pass_info_btn" name="gate_pass_info_btn" class="btn btn-dialog" onclick="editReturnVendorDemandGatePassData();"><i title="Edit" class="fas fa-edit fas-icon"></i> Gate Pass Info </button>
                        </div> 
                        @if(!empty($gate_pass_data))
                            <div class="form-group col-md-2">
                                <label for="Color">Gate Pass PDF</label>						
                                <a type="button" id ="gate_pass_pdf_btn" name="gate_pass_pdf_btn" class="btn btn-dialog" href="{{url('warehouse/demand/inventory-return-vendor/gatepass/'.$demand_data->id)}}">Gate Pass PDF <i title="Download" class="fas fa-download fas-icon"></i></a>
                            </div>
                        @endif
                    @endif

                    <?php //$has_permission = CommonHelper::hasRoutePermission('warehousedemandinventoryreturnvendorinvoice',$user->user_type); ?>
                    <?php /* ?>
                    {{-- @if($has_permission && request('type') == 'dbt' && strtolower($demand_data->demand_status) == 'warehouse_dispatched') --}}
                        <div class="form-group col-md-2">
                            <label for="Category">Debit Note PDF</label>						
                            <a type="button" class="btn btn-dialog" href="{{url('warehouse/demand/inventory-return-vendor/invoice/'.$demand_data->id.'/1')}}">Debit Note PDF <i title="Download Debit Note Invoice" class="fas fa-download fas-icon"></i></a>
                        </div> 
                    {{-- @endif--}}
                    
                    {{-- @if($has_permission && request('type') == 'crt' && strtolower($demand_data->demand_status) == 'warehouse_dispatched') --}}
                        <div class="form-group col-md-2">
                            <label for="Category">Credit Note PDF</label>						
                            <a type="button" class="btn btn-dialog" href="{{url('warehouse/demand/inventory-return-vendor/invoice/'.$demand_data->id.'/2')}}">Credit Note PDF <i title="Download Credit Note Invoice" class="fas fa-download fas-icon"></i></a>
                        </div> 
                    {{-- @endif --}}
                    <?php */ ?>
                    
                    <div class="form-group col-md-2">
                        <label for="Category">Invoice PDF</label>						
                        <a type="button" class="btn btn-dialog" href="{{url('warehouse/demand/inventory-return-vendor/invoice/'.$demand_data->id.'/1')}}">Invoice PDF <i title="Download Invoice Invoice" class="fas fa-download fas-icon"></i></a>
                    </div> 
                    
                    @if($user->user_type == 6 && in_array(strtolower($demand_data->demand_status),array('warehouse_loading','warehouse_dispatched')))
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Demand</label>		
                            <button type="button" id="cancel_push_demand_btn" name="cancel_push_demand_btn" class="btn btn-dialog" onclick="cancelInventoryReturnVendorDemand({{$demand_data->id}});"><i title="Cancel Demand" class="fa fas-icon fa-crosshairs"></i> Cancel Demand</button>
                        </div>
                    @endif
                </div>    
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
                                <th>S No</th><th>Product</th><th>SKU</th><th>Color</th><th>HSN Code</th>
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
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Piece Barcode</th>
                                <th>Product Barcode</th>
                                <th>SKU</th>
                                <th>Cost Price</th>
                                <th>Sale Price</th>
                                <th>Status</th>
                            </tr></thead>
                            <tbody>
                                <?php  ?>
                                @for($i=0;$i<count($product_inventory);$i++)
                                    <tr>
                                        <td>{{$product_inventory[$i]->id}}</td>
                                        <td>{{$product_inventory[$i]->product_name}} {{$product_inventory[$i]->size_name}} {{$product_inventory[$i]->color_name}}</td>
                                        <td>{{$product_inventory[$i]->peice_barcode}}</td>
                                        <td>{{$product_inventory[$i]->product_barcode}}</td>
                                        <td>{{(!empty($product_inventory[$i]->vendor_sku))?$product_inventory[$i]->vendor_sku:$product_inventory[$i]->product_sku}}</td>
                                        <td>{{$currency}} {{$product_inventory[$i]->base_price}}</td>
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

    <div class="modal fade data-modal" id="editGatePassDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Gate Pass Information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editGatePassSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editGatePassErrorMessage"></div>

                <form class="" name="editGatePassForm" id="editGatePassForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>No of Boxes</label>
                                <input id="boxes_count" type="text" class="form-control" name="boxes_count" value="{{(isset($gate_pass_data->boxes_count))?$gate_pass_data->boxes_count:''}}" >
                                <div class="invalid-feedback" id="error_validation_boxes_count"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Transporter Name</label>
                                <input id="transporter_name" type="text" class="form-control" name="transporter_name" value="{{(isset($gate_pass_data->transporter_name))?$gate_pass_data->transporter_name:''}}">
                                <div class="invalid-feedback" id="error_validation_transporter_name"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Transporter GST</label>
                                <input id="transporter_gst" type="text" class="form-control" name="transporter_gst" value="{{(isset($gate_pass_data->transporter_gst))?$gate_pass_data->transporter_gst:''}}">
                                <div class="invalid-feedback" id="error_validation_transporter_gst"></div>
                            </div>
                             <div class="form-group col-md-3" >
                                <label>Docket No</label>
                                <input id="docket_no" type="text" class="form-control" name="docket_no" value="{{(isset($gate_pass_data->docket_no))?$gate_pass_data->docket_no:''}}" >
                                <input type="hidden" name="demand_id_gate_pass" id="demand_id_gate_pass" value="{{(isset($gate_pass_data->demand_id))?$gate_pass_data->demand_id:''}}">
                                <div class="invalid-feedback" id="error_validation_docket_no"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Eway Bill No</label>
                                <input id="eway_bill_no" type="text" class="form-control" name="eway_bill_no" value="{{(isset($gate_pass_data->eway_bill_no))?$gate_pass_data->eway_bill_no:''}}" >
                                <div class="invalid-feedback" id="error_validation_eway_bill_no"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="editGatePassFormSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editGatePassCancel" name="editGatePassCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editGatePassSubmit" name="editGatePassSubmit" class="btn btn-dialog" onclick="submitEditReturnVendorDemandGatePassData();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="push_demand_cancel_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Cancel Demand</h5>
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
                        <button type="button" id ="cancelPushDemandSubmit" name="cancelPushDemandSubmit" class="btn btn-dialog" onclick="submitCancelInventoryReturnVendorDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=2.25') }}" ></script>
@endsection
