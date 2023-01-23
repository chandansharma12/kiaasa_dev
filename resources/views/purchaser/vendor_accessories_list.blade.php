@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendor Accessories List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendor Accessories List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateAccessoryStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateAccessoryStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1">
                        <input type="text" name="acc_id" id="acc_id" class="form-control" value="{{request('acc_id')}}" placeholder="ID">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="acc_name" id="acc_name" class="form-control" value="{{request('acc_name')}}" placeholder="Accessory Name">
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2" ><input type="button" name="addAccessoryBtn" id="addAccessoryBtn" value="Add Vendor Accessory" class="btn btn-dialog" onclick="addVendorAccessory();"></div>
                </div>
            </form>
            
            <div class="separator-10">&nbsp;</div>

            <div id="accessoryContainer">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" >
                        <thead>
                            <tr class="header-tr">
                                <th>ID</th><th>Vendor Name</th><th>Accessory Name</th>
                                <th>PO</th><th>Quantity</th><th>Date Provided</th><th>Date Added</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($vendor_acc_list);$i++)
                            <tr>
                                <td>{{$vendor_acc_list[$i]->id}}</td>
                                <td>{{$vendor_acc_list[$i]->vendor_name}}</td>
                                <td>{{$vendor_acc_list[$i]->accessory_name}}</td>
                                <td>{{$vendor_acc_list[$i]->po_no}}</td>
                                <td>{{$vendor_acc_list[$i]->quantity}}</td>
                                <td>{{date('d M Y',strtotime($vendor_acc_list[$i]->date_provided))}}</td>
                                <td>@if(!empty($vendor_acc_list[$i]->created_at)) {{date('d M Y',strtotime($vendor_acc_list[$i]->created_at))}} @endif</td>
                                <td><a href="javascript:;" class="user-list-edit" onclick="editVendorAccessory({{$vendor_acc_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a></td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $vendor_acc_list->links() }}
                    <p>Displaying {{$vendor_acc_list->count()}} of {{ $vendor_acc_list->total() }} accessories.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="edit_vendor_accessory_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Vendor Accessory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editVendorAccessorySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editVendorAccessoryErrorMessage"></div>

                <form class="" name="editVendorAccessoryFrm" id="editVendorAccessoryFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Vendor</label>
                                <select name="vendor_id_edit" id="vendor_id_edit"  class="form-control" onchange="getVendorPOList(this.value,'edit');">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($vendor_list);$i++)
                                        <option value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_vendor_id_edit"></div>
                            </div>
                            <div class="row" >
                                <div class="form-group col-md-6" >
                                    <label>PO</label>
                                    <select name="po_id_edit" id="po_id_edit"  class="form-control" >
                                        <option value="">Select</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_po_id_edit"></div>
                                </div>
                                <div class="form-group col-md-6" >
                                    <label>Accessory</label>
                                    <select name="acc_id_edit" id="acc_id_edit"  class="form-control" >
                                        <option value="">Select</option>
                                        @for($i=0;$i<count($accessory_list);$i++)
                                            <option value="{{$accessory_list[$i]['id']}}">{{$accessory_list[$i]['accessory_name']}}</option>
                                        @endfor    
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_acc_id_edit"></div>
                                </div>
                            </div>
                            <div class="row" >
                                <div class="form-group col-md-6" >
                                    <label>Quantity</label>
                                    <input id="quantity_edit" type="text" class="form-control" name="quantity_edit" value="" >
                                    <div class="invalid-feedback" id="error_validation_quantity_edit"></div>
                                </div>
                                <div class="form-group col-md-6" >
                                    <label>Date Provided</label>
                                    <input id="date_provided_edit" type="text" class="form-control" name="date_provided_edit" value="" >
                                    <div class="invalid-feedback" id="error_validation_date_provided_edit"></div>
                                </div>
                            </div>    
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="vendor_accessory_edit_cancel" name="vendor_accessory_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="vendor_accessory_edit_submit" name="vendor_accessory_edit_submit" class="btn btn-dialog" onclick="updateVendorAccessory();">Submit</button>
                        <input type="hidden" name="vendor_acc_edit_id" id="vendor_acc_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_vendor_accessory_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Vendor Accessory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addVendorAccessorySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addVendorAccessoryErrorMessage"></div>

                <form class="" name="addVendorAccessoryFrm" id="addVendorAccessoryFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Vendor</label>
                                <select name="vendor_id_add" id="vendor_id_add"  class="form-control" onchange="getVendorPOList(this.value,'add','');">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($vendor_list);$i++)
                                        <option value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_vendor_id_add"></div>
                            </div>
                            <div class="row" >
                                <div class="form-group col-md-6" >
                                    <label>PO</label>
                                    <select name="po_id_add" id="po_id_add"  class="form-control" >
                                        <option value="">Select</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_po_id_add"></div>
                                </div>
                                <div class="form-group  col-md-6" >
                                    <label>Accessory</label>
                                    <select name="acc_id_add" id="acc_id_add"  class="form-control" >
                                        <option value="">Select</option>
                                        @for($i=0;$i<count($accessory_list);$i++)
                                            <option value="{{$accessory_list[$i]['id']}}">{{$accessory_list[$i]['accessory_name']}}</option>
                                        @endfor    
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_acc_id_add"></div>
                                </div>
                            </div>    
                            <div class="row" >
                                <div class="form-group col-md-6" >
                                    <label>Quantity</label>
                                    <input id="quantity_add" type="text" class="form-control" name="quantity_add" value="" >
                                    <div class="invalid-feedback" id="error_validation_quantity_add"></div>
                                </div>
                                <div class="form-group col-md-6" >
                                    <label>Date Provided</label>
                                    <input id="date_provided_add" type="text" class="form-control" name="date_provided_add" value="" >
                                    <div class="invalid-feedback" id="error_validation_date_provided_add"></div>
                                </div>
                            </div>    
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="vendor_accessory_add_cancel" name="vendor_accessory_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="vendor_accessory_add_submit" name="vendor_accessory_add_submit" class="btn btn-dialog" onclick="submitAddVendorAccessory();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('#date_provided_add,#date_provided_edit').datepicker({format: 'dd-mm-yyyy'});</script>
@endsection
