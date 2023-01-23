@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Stitching Purchase Orders','link'=>'purchase-order/bulk/finished/list'),array('name'=>'Stitching Purchase Order Invoice list')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Stitching Purchase Order Invoice List ('.$po_data->order_no.')'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                @if($user->user_type == 6)
                    <div class="row justify-content-end" >
                        <div class="col-md-2" ><button type="button" id="pos_add_invoice_submit" name="pos_add_invoice_submit" class="btn btn-dialog" value="Submit" onclick="displayAddBulkFinishedPOInvoice();">Add Invoice</button>&nbsp;&nbsp;</div>
                    </div>
                @endif
            </form>
            <div class="separator-10"></div>
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="productsContainer">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th>SNo</th><th>ID</th><th>Invoice No</th><th>Invoice Date</th><th>Inventory Count</th><th>Vehicle No</th><th>Containers Count</th><th>GRN Created</th><th>QC Completed</th><th>Created By</th><th>Created On</th><th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($po_invoices);$i++)
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$po_invoices[$i]->id}}</td>
                                    <td>{{$po_invoices[$i]->invoice_no}}</td>
                                    
                                    <td>{{date('d M Y',strtotime($po_invoices[$i]->invoice_date))}}</td>
                                    <td>{{$po_invoices[$i]->products_count}}</td>
                                    <td>{{$po_invoices[$i]->vehicle_no}}</td>
                                    <td>{{$po_invoices[$i]->containers_count}}</td>
                                    <td>{{(!empty($po_invoices[$i]->grn_id))?'Yes':'No'}}</td>
                                    <td>{{(!empty($po_invoices[$i]->qc_id))?'Yes':'No'}}</td>
                                    <td>{{$po_invoices[$i]->user_name}}</td>
                                    <td>{{date('d M Y, H:i',strtotime($po_invoices[$i]->created_at))}}</td>
                                    <td>
                                        <a href="javascript:;" onclick="displayBulkFinishedPOInvoiceDetails({{$po_invoices[$i]->id}});"><i title="Invoice Details" class="fas fa-eye"></i> &nbsp; 
                                        @if($user->user_type == 6)    
                                            <a href="{{url('warehouse/bulk/finished/inventory/import/'.$po_invoices[$i]->id)}}"><i title="Import Inventory" class="fas fa-eye"></i> &nbsp; 
                                        @else        
                                            @if(!empty($po_invoices[$i]->grn_id))    
                                                <a href="{{url('warehouse/bulk/finished/inventory/import/'.$po_invoices[$i]->id)}}"><i title="Import Inventory" class="fas fa-eye"></i> &nbsp; 
                                            @endif    
                                        @endif    
                                        
                                        @if($user->user_type == 6)    
                                            <a href="{{url('warehouse/bulk/finished/inventory/qc/'.$po_invoices[$i]->id)}}"><i title="Inventory QC" class="fas fa-eye"></i>    
                                        @else
                                            @if(!empty($po_invoices[$i]->qc_id))    
                                                <a href="{{url('warehouse/bulk/finished/inventory/qc/'.$po_invoices[$i]->id)}}"><i title="Inventory QC" class="fas fa-eye"></i>    
                                            @endif    
                                        @endif    
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $po_invoices->withQueryString()->links() }}<p>Displaying {{$po_invoices->count()}} of {{ $po_invoices->total() }} invoices.</p>
                    <input type="hidden" name="po_id" id="po_id" value="{{$po_data->id}}">
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="add_po_invoice_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Invoice</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addVehicleDetailsSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addVehicleDetailsErrorMessage"></div>

                <form class="" name="addBulkFinishedPOVehicleDetailsForm" id="addBulkFinishedPOVehicleDetailsForm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label>Invoice No</label>
                                <input id="invoice_no" type="text" class="form-control" name="invoice_no" value="" >
                                <div class="invalid-feedback" id="error_validation_invoice_no"></div>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Invoice Date</label>
                                <input id="invoice_date" type="text" class="form-control" name="invoice_date" value="" >
                                <div class="invalid-feedback" id="error_validation_invoice_date"></div>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Inventory Count</label>
                                <?php /* ?><input id="products_count" type="text" class="form-control" name="products_count" value="" > <?php */ ?>
                                <select id="products_count" class="form-control" name="products_count" ></select>
                                <div class="invalid-feedback" id="error_validation_products_count"></div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Vehicle/Airways Number</label>
                                <input id="vehicle_no" type="text" class="form-control" name="vehicle_no" value="" >
                                <div class="invalid-feedback" id="error_validation_vehicle_no"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>No of Containers</label>
                                <select id="containers_count" class="form-control" name="containers_count" onchange="displayBulkFinishedVehicleDetailsContainerImages(this.value);">
                                    <option value="">Select</option>
                                    @for($i=1;$i<=100;$i++):
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_containers_count"></div>
                            </div>
                        </div>    
                        
                        <div class="form-row">
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
                        <button type="button" id ="add_vehicle_details_confirm_submit" name="add_vehicle_details_confirm_submit" class="btn btn-dialog" onclick="submitAddBulkFinishedPOInvoice();">Submit</button>
                        <input type="hidden" name="po_id" id="po_id" value="{{$po_data->id}}">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="po_invoice_detail_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Invoice Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden" id="updateInvoiceDetailsSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="updateInvoiceDetailsErrorMessage"></div>

                <form class="" name="vehicleDetailForm" id="vehicleDetailForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label>Invoice No</label>
                                <input id="invoice_no_detail" type="text" class="form-control" name="invoice_no_detail" value="">
                            </div>
                            <div class="form-group col-md-2">
                                <label>Invoice Date</label>
                                <input id="invoice_date_detail" type="text" class="form-control" name="invoice_date_detail" value="" >
                            </div>
                            <div class="form-group col-md-2">
                                <label>Inventory Count</label>
                                <input id="products_count_detail" type="text" class="form-control" name="products_count_detail" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Vehicle/Airways Number</label>
                                <input id="vehicle_no_detail" type="text" class="form-control" name="vehicle_no_detail" value="" >
                            </div>
                            <div class="form-group col-md-3" >
                                <label>No of Containers</label>
                                <input id="containers_count_detail" type="text" class="form-control" name="containers_count_detail" value="" readonly="true">
                            </div>
                        </div>    
                        
                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label>Comments</label>
                                <input id="comments_detail" type="text" class="form-control" name="comments_detail"  >
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
                    @if($user->user_type == 6)
                        <div class="modal-footer center-footer">
                            <button type="button" id="update_po_invoice_detail_cancel" name="update_po_invoice_detail_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" id ="update_po_invoice_detail_submit" name="update_po_invoice_detail_submit" class="btn btn-dialog" onclick="submitUpdateBulkFinishedPOInvoiceDetails();">Submit</button>
                            <input type="hidden" name="po_detail_id" id="po_detail_id" value="">
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.135') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('#invoice_date,#invoice_date_detail').datepicker({format: 'dd/mm/yyyy'});</script>
@endsection
