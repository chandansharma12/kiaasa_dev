@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Return Vendor Demands List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Return Vendor Demands List'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="invoice_id" id="invoice_id" value="{{request('invoice_id')}}" placeholder="Demand ID" class="form-control">
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="invoice_no" id="invoice_no" class="form-control" placeholder="Invoice No" value="{{request('invoice_no')}}">
                    </div>
                    
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    @if(in_array($user->user_type,array(1,6)))
                        <div class="col-md-2" ><a href="javascript:;" onclick="createInventoryReturnVendorDemand();" class="btn btn-dialog ">Create Demand</a></div>
                    @endif
                    
                    @if(in_array($user->user_type,array(1)))
                        <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Inventory Return to Vendor Demands'); ?></div>
                    @endif
                    
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
               <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th> ID</th>
                            <th>Invoice No</th><th>Vendor</th><!--<th>PO</th><th>PO Invoice No</th>--><th>Demand Status</th><th>Created by</th><th>Created On</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($demands_list);$i++)
                                <tr>
                                    <td> {{$demands_list[$i]->id}}</td>
                                    <td>{{$demands_list[$i]->invoice_no}}</td>
                                    <td>{{$demands_list[$i]->vendor_name}}</td>
                                    <?php /* ?><td>{{$demands_list[$i]->order_no}}</td>
                                    <td>{{$demands_list[$i]->pod_invoice_no}}</td> <?php */ ?>
                                    <td>{{ucwords(str_replace('_',' ',$demands_list[$i]->demand_status))}}</td>
                                    <td>{{$demands_list[$i]->user_name}}</td>
                                    <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                    <td>
                                        @if(in_array($user->user_type,array(1,6)) && strtolower($demands_list[$i]->demand_status) == 'warehouse_loading')
                                            <a href="{{url('warehouse/demand/inventory-return-vendor/edit/'.$demands_list[$i]->id)}}" ><i title="Edit Demand" class="far fa-edit"></i></a> &nbsp;
                                        @endif
                                        @if($user->user_type == 6 || $user->user_type == 1)
                                            <a href="{{url('warehouse/demand/inventory-return-vendor/detail/'.$demands_list[$i]->id)}}?type=dbt" ><i title="Demand Detail" class="fas fa-eye"></i></a> 
                                        @endif
                                        @if($user->user_type == 15)
                                            <a href="{{url('warehouse/demand/inventory-return-vendor/detail/'.$demands_list[$i]->id)}}?type=crt" ><i title="Demand Detail" class="fas fa-eye"></i></a> 
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>

                    {{ $demands_list->links() }} <p>Displaying {{$demands_list->count()}} of {{ $demands_list->total() }} Demands.</p>
                        
                </div>
                
            </div>
        </div>
    </section>

    <div class="modal fade" id="add_inventory_return_vendor_demand_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Create Inventory Return to Vendor Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="createInventoryReturnVendorDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="createInventoryReturnVendorDemandErrorMessage"></div>

                <form class="" name="inventoryReturnVendorFrm" id="inventoryReturnVendorFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group col-md-12" >
                                    <label>Vendor</label>
                                    <?php /* ?><select id="po_id" class="form-control" name="po_id" onchange="getPoInvoiceList(this.value);"> <?php */ ?>
                                    <select id="vendor_id" class="form-control" name="vendor_id" >    
                                        <option value="">Select</option>
                                        @for($i=0;$i<count($vendors_list);$i++):
                                            <option value="{{$vendors_list[$i]['id']}}">{{$vendors_list[$i]['name']}}</option>
                                        @endfor
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_vendor_id"></div>
                                </div>
                            </div>
                            <?php /* ?>
                            <div class="form-row">
                                <div class="form-group col-md-12" >
                                    <label>Purchase Order Invoice</label>
                                    <select id="po_invoice_id" class="form-control" name="po_invoice_id" >
                                        <option value="">Select</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_po_invoice_id"></div>
                                </div>
                            </div><?php */ ?>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add_inventory_return_vendor_demand_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="inventory_return_vendor_add_cancel" name="inventory_return_vendor_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="inventory_return_vendor_add_submit" name="inventory_return_vendor_add_submit" class="btn btn-dialog" onclick="submitCreateInventoryReturnVendorDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($demands_list->total(),1000,'/warehouse/demand/inventory-return-vendor/list','Download Inventory Return to Vendor Demands','Demands List'); ?>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=1.35') }}" ></script>
@endsection
