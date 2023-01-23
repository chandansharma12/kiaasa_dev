@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendors List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendors List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateVendorStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateVendorStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="v_id" id="v_id" class="form-control" placeholder="Vendor ID" value="{{request('v_id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="u_name" id="u_name" class="form-control" placeholder="Name or Email" value="{{request('u_name')}}" />
                    </div>

                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Vendor List'); ?></div>
                </div>
            </form>

            <div class="separator-10">&nbsp;</div>

            <div id="usersContainer">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:13px; ">
                        <thead><tr class="header-tr">
                            <th><input type="checkbox" name="vendor_list_all" id="vendor_list_all"  value="1" onclick="selectAllVendors(this);">
                            &nbsp;<?php echo CommonHelper::getSortLink('ID','id','vendor/list',true,'ASC'); ?> </th>      
                            <th><?php echo CommonHelper::getSortLink('Name','name','vendor/list'); ?> </th> 
                            <th><?php echo CommonHelper::getSortLink('Email','email','vendor/list'); ?> </th> 
                            <th><?php echo CommonHelper::getSortLink('Phone','phone','vendor/list'); ?> </th> 
                            <th><?php echo CommonHelper::getSortLink('Address','address','vendor/list'); ?> </th> 
                            <th>Main Vendor</th>
                            <th><?php echo CommonHelper::getSortLink('Ecomm Status','ecomm_status','vendor/list'); ?> </th> 
                            <th><?php echo CommonHelper::getSortLink('Created On','created','vendor/list'); ?> </th> 
                            <th>Action</th></tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($users_list);$i++)
                            <tr>
                                <td><input type="checkbox" name="vendor_list" id="vendor_list_{{$users_list[$i]->id}}" class="vendor-list" value="{{$users_list[$i]->id}}"> &nbsp;{{$users_list[$i]->id}}</td>
                                <td>{{$users_list[$i]->name}}</td>
                                <td>{{$users_list[$i]->email}}</td>
                                <td>{{$users_list[$i]->phone}}</td>
                                <td>{{substr($users_list[$i]->address,0,50)}}</td>
                                <td>{{$users_list[$i]->main_vendor_name}}</td>
                                <td>@if($users_list[$i]->ecommerce_status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                <td>@if(!empty($users_list[$i]->created_at)) {{date('d M Y',strtotime($users_list[$i]->created_at))}} @endif</td>
                                <td>
                                    <a href="javascript:;" class="user-list-edit" onclick="editVendor({{$users_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a> &nbsp;
                                    @if(empty($users_list[$i]->pid))
                                        <a href="{{url('vendor/subvendors/list/'.$users_list[$i]->id)}}" class="user-list-edit" ><i title="Sub Vendors List" class="far fa-eye"></i></a>
                                    @endif
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $users_list->withQueryString()->links() }}
                    <p>Displaying {{$users_list->count()}} of {{ $users_list->total() }} vendors.</p>
                </div>
            </div>

        </div>
    </section>

    <div class="modal fade data-modal" id="edit_user_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Vendor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editUserSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editUserErrorMessage"></div>

                <form class="" name="editUserFrm" id="editUserFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>Name</label>
                                <input id="userName_edit" type="text" class="form-control" name="userName_edit" value="" autofocus readonly="true">
                                <div class="invalid-feedback" id="error_validation_userName_edit"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Email</label>
                                <input id="userEmail_edit" type="text" class="form-control" name="userEmail_edit" value="" readonly="true" >
                                <div class="invalid-feedback" id="error_validation_userEmail_edit"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Phone</label>
                                <input id="userPhone_edit" type="text" class="form-control" name="userPhone_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_userPhone_edit"></div>
                            </div>
                        </div> 
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>City</label>
                                <input id="userCity_edit" type="text" class="form-control" name="userCity_edit" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_userCity_edit"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>State</label>
                                <select name="userState_edit" id="userState_edit" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($states_list);$i++)
                                        <option value="{{$states_list[$i]->id}}">{{$states_list[$i]->state_name}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_userState_edit"></div>
                            </div>

                            <div class="form-group col-md-4" >
                                <label>Postal Code</label>
                                <input id="userPotalCode_edit" type="text" class="form-control" name="userPotalCode_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_userPotalCode_edit"></div>
                            </div>
                        </div>    
                        <div class="form-group" >
                            <label>Address</label>
                            <input id="userAddress_edit" type="text" class="form-control" name="userAddress_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_userAddress_edit"></div>
                        </div>
                        
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>GST No</label>
                                <input id="userGstNo_edit" type="text" class="form-control" name="userGstNo_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_userGstNo_edit"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Vendor Code</label>
                                <input id="vendorCode_edit" type="text" class="form-control" name="vendorCode_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_vendorCode_edit"></div>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Ecommerce Status</label>
                                <select name="ecommerceStatus_edit" id="ecommerceStatus_edit" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_ecommerceStatus_edit"></div>	
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="user_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="user_edit_cancel" name="user_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="user_edit_submit" name="user_edit_submit" class="btn btn-dialog" onclick="updateVendor();">Submit</button>
                        <input type="hidden" name="user_edit_id" id="user_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="add_user_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Vendor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addUserSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addUserErrorMessage"></div>

                <form class="" name="addUserFrm" id="addUserFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>Name</label>
                                <input id="userName_add" type="text" class="form-control" name="userName_add" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_userName_add"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Email</label>
                                <input id="userEmail_add" type="text" class="form-control" name="userEmail_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_userEmail_add"></div>
                            </div>

                            <div class="form-group col-md-4" >
                                <label>Phone</label>
                                <input id="userPhone_add" type="text" class="form-control" name="userPhone_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_userPhone_add"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>City</label>
                                <input id="userCity_add" type="text" class="form-control" name="userCity_add" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_userCity_add"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>State</label>
                                <select name="userState_add" id="userState_add" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($states_list);$i++)
                                        <option value="{{$states_list[$i]->id}}">{{$states_list[$i]->state_name}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_userState_add"></div>
                            </div>

                            <div class="form-group col-md-4" >
                                <label>Postal Code</label>
                                <input id="userPotalCode_add" type="text" class="form-control" name="userPotalCode_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_userPotalCode_add"></div>
                            </div>
                        </div>    
                        <div class="form-group" >
                            <label>Address</label>
                            <input id="userAddress_add" type="text" class="form-control" name="userAddress_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_userAddress_add"></div>
                        </div>
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>GST No</label>
                                <input id="userGstNo_add" type="text" class="form-control" name="userGstNo_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_userGstNo_add"></div>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Status</label>
                                <select name="userStatus_add" id="userStatus_add" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_userStatus_add"></div>	
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="user_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="user_add_cancel" name="user_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="user_add_submit" name="user_add_submit" class="btn btn-dialog" onclick="submitAddVendor();">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($users_list->total(),1000,'/vendor/list','Download Vendors List','Vendors'); ?>

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/vendors.js?v=1.1') }}" ></script>
@endsection
