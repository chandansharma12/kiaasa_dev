@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendors List','link'=>'vendor/list'),array('name'=>'Vendor SubVendors List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendor SubVendors List'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <input type="text" name="v_name" id="v_name" class="form-control" placeholder="Subvendor Name / Email / Phone" value="{{request('v_name')}}" />
                    </div>
                    
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2" ><input type="button" name="addSubVendorBtn" id="addSubVendorBtn" value="Add Sub Vendor" class="btn btn-dialog" onclick="addSubVendor();"></div>
                </div>
            </form>

            <div class="separator-10">&nbsp;</div>
            Main Vendor: {{$vendor_data->name}}
            <div class="separator-10"></div>
            
            <div id="usersContainer">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th>SubVendor ID</th>     
                            <th>SubVendor Name</th> 
                            <th>Email</th> 
                            <th>Phone</th> 
                            <th>Added on</th> 
                            <th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($vendors_list);$i++)
                            <tr>
                                <td>{{$vendors_list[$i]->id}}</td>
                                <td>{{$vendors_list[$i]->name}}</td>
                                <td>{{$vendors_list[$i]->email}}</td>
                                <td>{{$vendors_list[$i]->phone}}</td>
                                <td>
                                    @if(!empty($vendors_list[$i]->pid_added_date)) 
                                        {{date('d M Y',strtotime($vendors_list[$i]->pid_added_date))}}
                                    @endif
                                </td>
                                <td><a href="javascript:;" class="user-list-edit" onclick="deleteSubvendorFromVendor({{$vendors_list[$i]->id}});"><i title="Delete Subvendor from Vendor" class="fa fa-trash"></i></a></td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $vendors_list->withQueryString()->links() }}
                    <p>Displaying {{$vendors_list->count()}} of {{ $vendors_list->total() }} records.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="add_subvendor_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New SubVendor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addSubVendorSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addSubVendorErrorMessage"></div>

                <form class="" name="addSubVendorFrm" id="addSubVendorFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-8" >
                                <label>Vendor</label>
                                <select name="subvendor_add" id="subvendor_add" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($vendors_add_list);$i++)
                                        <option value="{{$vendors_add_list[$i]['id']}}">{{$vendors_add_list[$i]['name']}} ({{$vendors_add_list[$i]['email']}})</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_subvendor_add"></div>
                            </div>
                        </div>    
                    </div>
                    
                    <div class="modal-footer center-footer">
                        <div id="subvendor_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="subvendor_add_cancel" name="subvendor_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="subvendor_add_submit" name="subvendor_add_submit" class="btn btn-dialog" onclick="submitAddSubVendor();">Submit</button>
                        <input type="hidden" name="main_vendor_id" id="main_vendor_id" value="{{$vendor_data->id}}">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm_delete_subvendor" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="subVendorDeleteErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="subVendorDeleteSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Sub Vendor from Vendor<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <div id="subvendor-delete-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="subvendor_delete_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="subvendor_delete_btn" name="subvendor_delete_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/vendors.js?v=2.01') }}" ></script>
@endsection