@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
@if(empty($error_message))

    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Permission List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Permission List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updatePermissionStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updatePermissionStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    <!--
                    <div class="col-md-2" >
                        <select name="permission_action" id="permission_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editPermission" id="editPermission" value="Update" class="btn btn-dialog" onclick="updatePermissionStatus();"></div>-->
                    <!--<div class="col-md-1" ><input type="button" name="addPermissionBtn" id="addPermissionBtn" value="Add Permission" class="btn btn-dialog" onclick="addPermission();"></div>-->
                    <div class="col-md-2" ><a  href="{{url('permission/role-permissions/2')}}" class="btn btn-dialog" >Role Permissions</a></div>
                    <div class="col-md-2" ><a  href="{{url('permission/user-permissions/2')}}" class="btn btn-dialog" >User Permissions</a></div>
                </div>
            </form>
            
            <div class="separator-10">&nbsp;</div>

            <div id="permissionContainer">
                <div id="permissionListOverlay"><div id="permission-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="permissionList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr"><th><input type="checkbox" name="permission_list_all" id="permission_list_all"  value="1" onclick="checkAllCheckboxes(this,'permission-list');"> 
                                    &nbsp;<?php echo CommonHelper::getSortLink('ID','id','permission/list',true,'ASC'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Route Path','route_path','permission/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Route Key','route_key','permission/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Type','type','permission/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Description','desc','permission/list'); ?></th>
                                    <!--<th><?php echo CommonHelper::getSortLink('Status','status','permission/list'); ?></th>-->
                                    <!--<th><?php echo CommonHelper::getSortLink('Created On','created','permission/list'); ?></th>-->
                                    <!--<th><?php echo CommonHelper::getSortLink('Updated On','updated','permission/list'); ?></th>-->
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($permission_list);$i++)
                                <tr>
                                    <td><input type="checkbox" name="permission_list" id="permission_list_{{$permission_list[$i]->id}}" class="permission-list-chk" value="{{$permission_list[$i]->id}}"> &nbsp;{{$permission_list[$i]->id}}</td>
                                    <td>{{$permission_list[$i]->route_path}}</td>
                                    <td>{{$permission_list[$i]->route_key}}</td>
                                    <td>{{$permission_list[$i]->permission_type}}</td>
                                    <td>{{str_replace('_',' ',$permission_list[$i]->description)}}</td>
                                    <!--<td>@if($permission_list[$i]->permission_status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>-->
                                    <!--<td>@if(!empty($permission_list[$i]->created_at)) {{date('d M Y',strtotime($permission_list[$i]->created_at))}} @endif</td>-->
                                    <!--<td>@if(!empty($permission_list[$i]->updated_at)) {{date('d M Y',strtotime($permission_list[$i]->updated_at))}} @endif</td>-->
                                    <td><a href="javascript:;" class="user-list-edit" onclick="editPermission({{$permission_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        @if(empty($error_message))
                            {{ $permission_list->links() }}
                            <p>Displaying {{$permission_list->count()}} of {{ $permission_list->total() }} permissions.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="edit_permission_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Permission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editPermissionSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editPermissionErrorMessage"></div>

                <form class="" name="editPermissionFrm" id="editPermissionFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Route Path</label>
                            <input id="routePath_edit" type="text" class="form-control" name="routePath_edit" value="" readonly="true"> 
                            <div class="invalid-feedback" id="error_validation_routePath_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Route Key</label>
                            <input id="routeKey_edit" type="text" class="form-control" name="routeKey_edit" value="" readonly="true" >
                            <div class="invalid-feedback" id="error_validation_routeKey_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Description</label>
                            <input id="description_edit" type="text" class="form-control" name="description_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_description_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Type</label>
                            <select id="permission_type_edit"   class="form-control" name="permission_type_edit"   >
                                <option value="">-- Type --</option>
                                <option value="page">Page</option>
                                <option value="system">System</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_permission_type_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="permission_edit_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="permission_edit_cancel" name="permission_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="permission_edit_submit" name="permission_edit_submit" class="btn btn-dialog" onclick="updatePermission();">Submit</button>
                        <input type="hidden" name="permission_edit_id" id="permission_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_permission_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Permission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addPermissionSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addPermissionErrorMessage"></div>

                <form class="" name="addPermissionFrm" id="addPermissionFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Route Path</label>
                            <input id="routePath_add" type="text" class="form-control" name="routePath_add" value="" autofocus> 
                            <div class="invalid-feedback" id="error_validation_routePath_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Route Key</label>
                            <input id="routeKey_add" type="text" class="form-control" name="routeKey_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_routeKey_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Description</label>
                            <input id="description_add" type="text" class="form-control" name="description_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_description_add"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="permission_add_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="permission_add_cancel" name="permission_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="permission_add_submit" name="permission_add_submit" class="btn btn-dialog" onclick="submitAddPermission();">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/permission.js') }}" ></script>
@endsection
