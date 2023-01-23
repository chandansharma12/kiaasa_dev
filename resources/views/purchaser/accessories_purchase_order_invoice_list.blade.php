@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Accessories Purchase Orders','link'=>'purchase-order/accessories/list'),array('name'=>'Purchase Order Invoice list')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Purchase Order Invoice List ('.$po_data->order_no.')'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                @if($user->user_type == 1 || $user->user_type == 6)
                    <div class="row justify-content-end" >
                        <div class="col-md-2" ><button type="button" id="pos_add_invoice_submit" name="pos_add_invoice_submit" class="btn btn-dialog" value="Submit" onclick="displayAddAccessoriesPOInvoice();">Add Invoice</button>&nbsp;&nbsp;</div>
                    </div>
                @endif
            </form>
            <div class="separator-10"></div>
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="productsContainer">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th>SNo</th><th>ID</th><th>Invoice No</th><th>Invoice Date</th><th>Vehicle No</th><th>Containers Count</th><th>GRN Created</th><th>Created By</th><th>Created On</th><th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($po_invoices);$i++)
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$po_invoices[$i]->id}}</td>
                                    <td>{{$po_invoices[$i]->invoice_no}}</td>
                                    <td>{{date('d M Y',strtotime($po_invoices[$i]->invoice_date))}}</td>
                                    
                                    <td>{{$po_invoices[$i]->vehicle_no}}</td>
                                    <td>{{$po_invoices[$i]->containers_count}}</td>
                                    <td>{{(!empty($po_invoices[$i]->grn_id))?'Yes':'No'}}</td>
                                    <td>{{$po_invoices[$i]->user_name}}</td>
                                    <td>{{date('d M Y, H:i',strtotime($po_invoices[$i]->created_at))}}</td>
                                    <td>
                                        <a href="javascript:;" onclick="displayAccessoriesPOInvoiceDetails({{$po_invoices[$i]->id}});"><i title="Invoice Details" class="fas fa-eye"></i> &nbsp; 
                                        @if($user->user_type == 1 || $user->user_type == 6)    
                                            <a href="{{url('purchase-order/accessories/items/import/'.$po_invoices[$i]->id)}}"><i title="Import Inventory" class="fas fa-eye"></i> &nbsp; 
                                        @else        
                                            @if(!empty($po_invoices[$i]->grn_id))    
                                                <a href="{{url('purchase-order/accessories/items/import/'.$po_invoices[$i]->id)}}"><i title="Import Inventory" class="fas fa-eye"></i> &nbsp; 
                                            @endif    
                                        @endif    
                                        
                                        <?php /* ?>  
                                        @if($user->user_type == 6 && empty($po_invoices[$i]->grn_id) && empty($po_invoices[$i]->qc_id) )   
                                            &nbsp;
                                            <a href="javascript:;" onclick="deletePOInvoice({{$po_invoices[$i]->po_id}},{{$po_invoices[$i]->id}});"><i title="Delete Invoice" class="fas fa-trash"></i> &nbsp; 
                                        @endif   <?php */ ?>  
                                    </td>
                                </tr>
                            @endfor
                            
                            @if(!$po_invoices->count())
                                <tr><td colspan="15" align="center">No Records</td></tr>
                            @endif
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

                <form class="" name="addAccessoriesVehicleDetailsForm" id="addAccessoriesVehicleDetailsForm" type="POST" enctype="multipart/form-data">
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
                            <!--<div class="form-group col-md-2">
                                <label>Inventory Count</label>
                                
                                <select id="products_count" class="form-control" name="products_count" ></select>
                                <div class="invalid-feedback" id="error_validation_products_count"></div>
                            </div> -->
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
                        <button type="button" id ="add_vehicle_details_confirm_submit" name="add_vehicle_details_confirm_submit" class="btn btn-dialog" onclick="submitAddAccessoriesPOInvoice();">Submit</button>
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

                <form class="" name="accessoriesVehicleDetailsForm" id="accessoriesVehicleDetailsForm" type="POST" >
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
                            <!--<div class="form-group col-md-2">
                                <label>Inventory Count</label>
                                <input id="products_count_detail" type="text" class="form-control" name="products_count_detail" value="" readonly="true">
                            </div> -->
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
                            <button type="button" id ="update_po_invoice_detail_submit" name="update_po_invoice_detail_submit" class="btn btn-dialog" onclick="submitUpdateAccessoriesPOInvoiceDetails();">Submit</button>
                            <input type="hidden" name="po_detail_id" id="po_detail_id" value="">
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoice_delete_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="invoiceDeleteErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="invoiceDeleteSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Invoice ?<br/></h6>
                    <br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="invoice_delete_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="invoice_delete_btn" name="invoice_delete_btn">Delete</button>
                </div>
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
