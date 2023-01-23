@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
@if(empty($error_message))
    <?php $name = 'User Permissions '; if(!empty($user_data)) $name.=': '.$user_data->name;  ?>
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard'),array('name'=>'Permissions','link'=>'permission/list'),array('name'=>$name)); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,$name); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateUserPermissionStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateUserPermissionStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get" id="user_form" name="user_form" onchange="submitUserForm();">
                <div class="row justify-content-end" >
                    <div class="col-md-4" >
                        <select name="user_id" id="user_id" class="form-control">
                            <option value="">-- Select User --</option>
                            @for($i=0;$i<count($user_list);$i++)
                                <?php if($user_id == $user_list[$i]->id) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$user_list[$i]->id}}">{{$user_list[$i]->name}} ({{$user_list[$i]->email}})</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editUserPermissions" id="editUserPermissions" value="Update" class="btn btn-dialog" onclick="updateUserPermissions();"></div>
                    @if(isset($user_data->name) && !empty($user_data->name))
                        <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton($user_data->name.' User Permissions List'); ?></div>
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
                                    <?php echo CommonHelper::getSortLink('ID','id','permission/role-permissions/'.$user_id,true,'ASC'); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Route Path','route_path','permission/role-permissions/'.$user_id); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Route Key','route_key','permission/role-permissions/'.$user_id); ?> </th>
                                    <!--<th><?php echo CommonHelper::getSortLink('Description','desc','permission/role-permissions/'.$user_id); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Status','status','permission/role-permissions/'.$user_id); ?> </th>-->
                                    <th><?php echo CommonHelper::getSortLink('Created On','created_on','permission/role-permissions/'.$user_id); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Updated On','updated_on','permission/role-permissions/'.$user_id); ?> </th>
                                    <!--<th>Action</th>-->
                                </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($user_permissions_list);$i++)
                                    <?php if(!empty($user_permissions_list[$i]->user_permission_status)) $chk = 'checked';else $chk = '';  ?>
                                    <tr>
                                        <td><input {{$chk}} type="checkbox" name="permission_list" id="permission_list_{{$user_permissions_list[$i]->id}}" class="permission-list-chk" value="{{$user_permissions_list[$i]->id}}"> &nbsp;{{$user_permissions_list[$i]->id}}</td>
                                        <td>{{$user_permissions_list[$i]->route_path}}</td>
                                        <td>{{$user_permissions_list[$i]->route_key}}</td>
                                        <!--<td>{{str_replace('_',' ',$user_permissions_list[$i]->description)}}</td>
                                        <td>@if($user_permissions_list[$i]->user_permission_status != null && $user_permissions_list[$i]->user_permission_status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>-->
                                        <td>@if(!empty($user_permissions_list[$i]->rp_created_at)) {{date('d M Y',strtotime($user_permissions_list[$i]->rp_created_at))}} @endif</td>
                                        <td>@if(!empty($user_permissions_list[$i]->rp_updated_at)) {{date('d M Y',strtotime($user_permissions_list[$i]->rp_updated_at))}} @endif</td>
                                        <!--<td></td>-->
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                        @if(empty($error_message) )
                            {{$user_permissions_list->withQueryString()->links() }}
                            <p>Displaying {{$user_permissions_list->count()}} of {{ $user_permissions_list->total() }} user permissions.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    @if(isset($user_data->name) && !empty($user_data->name))
        <?php echo CommonHelper::displayDownloadDialogHtml($user_permissions_list->total(),1000,'/permission/user-permissions/'.$user_data->id,'Download '.$user_data->name.' User Permissions List','User Permissions'); ?>    
    @endif 

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/permission.js?v=1.1') }}" ></script>
@endsection
