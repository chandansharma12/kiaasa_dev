@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

    <?php $name = 'Role Permissions'; if(!empty($role_data)) $name.=': '.$role_data->role_name;  ?>
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard'),array('name'=>'Permissions','link'=>'permission/list'),array('name'=>$name)); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,$name); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateRolePermissionStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateRolePermissionStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get" id="role_form" name="role_form" onchange="submitRoleForm();">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <input type="text" name="p_name" id="p_name" class="form-control" placeholder="Permission" value="{{request('p_name')}}" />
                    </div>
                    <div class="col-md-2" >
                        <select name="role_id" id="role_id" class="form-control">
                            <option value="">-- Select Role --</option>
                            @for($i=0;$i<count($roles_list);$i++)
                                <?php if($role_id == $roles_list[$i]->id) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$roles_list[$i]->id}}">{{$roles_list[$i]->role_name}}</option>
                            @endfor    
                        </select>
                    </div>
                    
                    <div class="col-md-1" ><input type="submit" name="editRolePermissions" id="editRolePermissions" value="Search" class="btn btn-dialog" ></div>
                    
                    <div class="col-md-1" ><input type="button" name="editRolePermissions" id="editRolePermissions" value="Update" class="btn btn-dialog" onclick="updateRolePermissions();"></div>
                    @if(isset($role_data->role_name) && !empty($role_data->role_name))
                        <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton($role_data->role_name.' Role Permissions List'); ?></div>
                    @endif
                    
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="permissionContainer">
                <div id="permissionListOverlay"><div id="permission-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="permissionList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th><input type="checkbox" name="permission_list_all" id="permission_list_all"  value="1" onclick="checkAllCheckboxes(this,'permission-list');"> &nbsp;
                                    <?php echo CommonHelper::getSortLink('ID','id','permission/role-permissions/'.$role_id,true,'ASC'); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Route Path','route_path','permission/role-permissions/'.$role_id); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Route Key','route_key','permission/role-permissions/'.$role_id); ?> </th>
                                    <!--<th><?php echo CommonHelper::getSortLink('Description','desc','permission/role-permissions/'.$role_id); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Status','status','permission/role-permissions/'.$role_id); ?> </th>-->
                                    <th><?php echo CommonHelper::getSortLink('Created On','created_on','permission/role-permissions/'.$role_id); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Updated On','updated_on','permission/role-permissions/'.$role_id); ?> </th>
                                    <!--<th>Action</th>-->
                                </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($role_permissions_list);$i++)
                                    <?php if(!empty($role_permissions_list[$i]->role_permission_status)) $chk = 'checked';else $chk = '';  ?>
                                    <tr>
                                        <td><input {{$chk}} type="checkbox" name="permission_list" id="permission_list_{{$role_permissions_list[$i]->id}}" class="permission-list-chk" value="{{$role_permissions_list[$i]->id}}"> &nbsp;{{$role_permissions_list[$i]->id}}</td>
                                        <td>{{$role_permissions_list[$i]->route_path}}</td>
                                        <td>{{$role_permissions_list[$i]->route_key}}</td>
                                        <!--<td>{{str_replace('_',' ',$role_permissions_list[$i]->description)}}</td>
                                        <td>@if($role_permissions_list[$i]->role_permission_status != null && $role_permissions_list[$i]->role_permission_status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>-->
                                        <td>@if(!empty($role_permissions_list[$i]->rp_created_at)) {{date('d M Y',strtotime($role_permissions_list[$i]->rp_created_at))}} @endif</td>
                                        <td>@if(!empty($role_permissions_list[$i]->rp_updated_at)) {{date('d M Y',strtotime($role_permissions_list[$i]->rp_updated_at))}} @endif</td>
                                        <!--<td></td>-->
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                        @if(empty($error_message) )
                            {{$role_permissions_list->withQueryString()->links() }}
                            <p>Displaying {{$role_permissions_list->count()}} of {{ $role_permissions_list->total() }} role permissions.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(isset($role_data->role_name) && !empty($role_data->role_name))
        <?php echo CommonHelper::displayDownloadDialogHtml($role_permissions_list->total(),1000,'/permission/role-permissions/'.$role_id,'Download '.$role_data->role_name.' Role Permissions List','Role Permissions'); ?>    
    @endif    

@endsection

@section('scripts')
<script src="{{ asset('js/permission.js') }}" ></script>
@endsection
