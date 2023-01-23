@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Staff List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Staff List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateStoreStaffStatusErrorMessage" class="alert alert-danger elem-hidden"  ></div>
            <div id="updateStoreStaffStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <input type="text" name="u_name" id="u_name" class="form-control" placeholder="Name" value="{{request('u_name')}}" />
                    </div>

                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <?php /* ?>
                    <div class="col-md-2" >
                        <select name="store_staff_action" id="store_staff_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                            
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editStoreStaff" id="editStoreStaff" value="Update" class="btn btn-dialog" onclick="editStoreStaffStatus();"></div>
                    <?php */ ?>
                    <div class="col-md-2" ><input type="button" name="addStoreStaffBtn" id="addStoreStaffBtn" value="Add Store Staff" class="btn btn-dialog"  onclick="addStoreStaff();"></div>
                </div>
            </form>

            <div class="separator-10">&nbsp;</div>

            <div id="usersContainer">
                
                <div id="usersList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                    <th><!--<input type="checkbox" name="store_staff_list_all" id="store_staff_list_all"  value="1" onclick="selectAllStoreStaff(this);">-->
                                    &nbsp;ID </th>      
                                    <th>Name </th> 
                                    <th>Phone </th> 
                                    <th>Address </th> 
                                    <th>Status</th> 
                                    <th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($staff_list);$i++)
                                <tr>
                                    <td><!--<input type="checkbox" name="store_staff_list" id="store_staff_list_{{$staff_list[$i]->id}}" class="store-staff-list" value="{{$staff_list[$i]->id}}"> &nbsp;-->{{$staff_list[$i]->id}}</td>
                                    <td>{{$staff_list[$i]->name}}</td>
                                    <td>{{$staff_list[$i]->phone_no}}</td>
                                    <td>{{$staff_list[$i]->address}}</td>
                                    <td>@if($staff_list[$i]->status == 1) <i title="Enabled" class="fas fa-check"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                    <td>
                                        <a href="javascript:;" class="user-list-edit" onclick="editStoreStaff({{$staff_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a>
                                    </td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $staff_list->withQueryString()->links() }}
                        <p>Displaying {{$staff_list->count()}} of {{ $staff_list->total() }} staff members.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <div class="modal fade data-modal" id="edit_store_staff_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Store Staff</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="editStoreStaffSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editStoreStaffErrorMessage"></div>

                <form class="" name="editStoreStaffFrm" id="editStoreStaffFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Name</label>
                                <input id="name_edit" type="text" class="form-control" name="name_edit" value="" >
                                <div class="invalid-feedback" id="error_validation_name_edit"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Phone</label>
                                <input id="phone_no_edit" type="text" class="form-control" name="phone_no_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_phone_no_edit"></div>
                            </div>
                        </div> 
                            
                        <div class="form-group" >
                            <label>Address</label>
                            <input id="address_edit" type="text" class="form-control" name="address_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_address_edit"></div>
                        </div>
                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Status</label>
                                <select name="status_edit" id="status_edit" class="form-control">
                                    <option value="">-- Status --</option>
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_status_edit"></div>
                            </div>
                        </div>     
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="store_staff_edit_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="store_staff_edit_cancel" name="store_staff_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="store_staff_edit_submit" name="store_staff_edit_submit" class="btn btn-dialog" onclick="updateStoreStaff();">Submit</button>
                        <input type="hidden" name="store_staff_id" id="store_staff_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="add_store_staff_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Store Staff</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="addStoreStaffSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addStoreStaffErrorMessage"></div>

                <form class="" name="addStoreStaffFrm" id="addStoreStaffFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Name</label>
                                <input id="name_add" type="text" class="form-control" name="name_add" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_name_add"></div>
                            </div>
                            
                            <div class="form-group col-md-6" >
                                <label>Phone</label>
                                <input id="phone_no_add" type="text" class="form-control" name="phone_no_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_phone_no_add"></div>
                            </div>
                        </div>    
                            
                        <div class="form-group" >
                            <label>Address</label>
                            <input id="address_add" type="text" class="form-control" name="address_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_address_add"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="store_staff_add_spinner"  class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="store_staff_add_cancel" name="store_staff_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="store_staff_add_submit" name="store_staff_add_submit" class="btn btn-dialog" onclick="submitAddStoreStaff();">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/store_staff.js?v=1.2') }}" ></script>
@endsection
