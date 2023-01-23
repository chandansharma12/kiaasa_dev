@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Users Activity List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Users Activity List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateUserStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateUserStatusSuccessMessage" class="alert alert-success elem-hidden"  ></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                   <div class="col-md-2">
                        <select name="role_id" id="role_id" class="form-control" onchange="getRoleUsers(this.value);">
                            <option value="">-- User Type --</option>
                            @for($i=0;$i<count($roles_list);$i++)
                                <?php if(request('role_id') == $roles_list[$i]['id']) $sel = 'selected'; else $sel = ''; ?>
                                <option <?php echo $sel; ?> value="{{$roles_list[$i]['id']}}">{{$roles_list[$i]['role_name']}}</option>
                            @endfor   
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="user_id" id="user_id" class="form-control">
                            <option value="">-- User --</option>
                            @for($i=0;$i<count($users_list);$i++)
                                <?php if(request('user_id') == $users_list[$i]['id']) $sel = 'selected'; else $sel = ''; ?>
                                <option <?php echo $sel; ?> value="{{$users_list[$i]['id']}}">{{$users_list[$i]['name']}}</option>
                            @endfor   
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif" autocomplete="off">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('User Activity List'); ?></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="usersContainer">
                
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; " >
                        <thead>
                            <tr class="header-tr"><th>ID</th>    
                                <th style="width:33%;">Title </th>    
                                <th>Type </th>    
                                <th>User </th>    
                                <th>User Type</th>    
                                <th>Date</th>    
                            </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($logs_list);$i++)
                                <tr>
                                    <td>{{$logs_list[$i]->id}}</td>
                                    <td>{{$logs_list[$i]->log_title}}</td>
                                    <td>{{$logs_list[$i]->log_type}}</td>
                                    <td>{{$logs_list[$i]->user_name}}</td>
                                    <td>{{$logs_list[$i]->role_name}}</td>
                                    <td> {{date('d-m-Y H:i',strtotime($logs_list[$i]->created_at))}} </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $logs_list->withQueryString()->links() }}
                    <p>Displaying {{$logs_list->count()}} of {{ $logs_list->total() }} records.</p>
                </div>
               
            </div>
        </div>
    </section>

    <?php echo CommonHelper::displayDownloadDialogHtml($logs_list->total(),50000,'/user/activity/list','Download Users Activity List','Users Activity'); ?>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/users.js?v=1.1') }}" ></script>
<script type="text/javascript">@if(request('role_id') != '') getRoleUsers({{request('role_id')}},"{{request('user_id')}}"); @endif</script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
