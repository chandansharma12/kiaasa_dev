@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard'),array('name'=>'Users','link'=>'user/list'),array('name'=>'User Permissions')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'User Permissions'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateRolePermissionStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateRolePermissionStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            User ID: {{$user_data->id}}  &nbsp;|&nbsp;  Name: {{$user_data->name}} &nbsp;|&nbsp;  User Type: {{$user_data->role_name}}
            <form method="get" id="role_form" name="role_form" >
                <div class="row justify-content-end" >
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <?php $count = 0; ?>
            <div id="permissionContainer">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>SNo</th>
                                <th>ID</th>
                                <th>Route Path </th>
                                <th>Description</th>
                                <th>Type</th>
                            </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($role_permissions);$i++)
                                <tr>
                                    <td>{{++$count}}</td>
                                    <td>{{$role_permissions[$i]->id}}</td>
                                    <td>{{$role_permissions[$i]->route_path}}</td>
                                    <td>{{$role_permissions[$i]->description}}</td>
                                    <td>Role</td>
                                </tr>
                            @endfor
                            
                            @for($i=0;$i<count($user_permissions);$i++)
                                <tr>
                                    <td>{{++$count}}</td>
                                    <td>{{$user_permissions[$i]->id}}</td>
                                    <td>{{$user_permissions[$i]->route_path}}</td>
                                    <td>{{$user_permissions[$i]->description}}</td>
                                    <td>User</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

@endsection