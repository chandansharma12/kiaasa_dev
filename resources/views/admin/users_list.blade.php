@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Users List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Users List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateUserStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateUserStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="u_id" id="u_id" class="form-control" placeholder="User ID" value="{{request('u_id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="u_name" id="u_name" class="form-control" placeholder="Name or Email" value="{{request('u_name')}}" />
                    </div>
                    
                    <div class="col-md-2">
                        <select name="role_id" id="role_id" class="form-control">
                            <option value="">User Type</option>
                            @for($i=0;$i<count($roles_list);$i++)
                                @if($user->user_type != 17)
                                    @if($roles_list[$i]['id'] != 1)
                                        <?php if(request('role_id') == $roles_list[$i]['id']) $sel = 'selected'; else $sel = ''; ?>
                                        <option <?php echo $sel; ?> value="{{$roles_list[$i]['id']}}">{{$roles_list[$i]['role_name']}}</option>
                                    @endif
                                @endif
                                
                                @if($user->user_type == 17)
                                    @if(!($roles_list[$i]['id'] == 9 || $roles_list[$i]['id'] == 15))
                                        <?php if(request('role_id') == $roles_list[$i]['id']) $sel = 'selected'; else $sel = ''; ?>
                                        <option <?php echo $sel; ?> value="{{$roles_list[$i]['id']}}">{{$roles_list[$i]['role_name']}}</option>
                                    @endif
                                @endif
                            @endfor   
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2" >
                        <select name="user_action" id="user_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editUser" id="editUser" value="Update" class="btn btn-dialog" onclick="updateUserStatus();"></div>
                    <div class="col-md-1" ><input type="button" name="addUserBtn" id="addUserBtn" value="Add User" class="btn btn-dialog" onclick="addUser();"></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('User List'); ?></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="usersContainer">
                <div id="usersListOverlay"><div id="users-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" >
                        <thead><tr class="header-tr"><th><input type="checkbox" name="user_list_all" id="user_list_all"  value="1" onclick="checkAllCheckboxes(this,'user-list');"> 
                                &nbsp;<?php echo CommonHelper::getSortLink('ID','id','user/list',true,'ASC'); ?> </th>    
                                <th><?php echo CommonHelper::getSortLink('Name','name','user/list'); ?> </th>    
                                <th><?php echo CommonHelper::getSortLink('Email','email','user/list'); ?> </th>    
                                <th><?php echo CommonHelper::getSortLink('Type','type','user/list'); ?> </th>    
                                <th><?php echo CommonHelper::getSortLink('Status','status','user/list'); ?> </th>
                                <th><?php echo CommonHelper::getSortLink('Created On','created','user/list'); ?> </th>    
                                <th><?php echo CommonHelper::getSortLink('Updated On','updated','user/list'); ?> </th>    
                        <th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($users_list);$i++)
                            <tr>
                                <td><input type="checkbox" name="user_list" id="user_list_{{$users_list[$i]->id}}" class="user-list-chk" value="{{$users_list[$i]->id}}"> &nbsp;{{$users_list[$i]->id}}</td>
                                <td>{{$users_list[$i]->name}}</td>
                                <td>{{$users_list[$i]->email}}</td>
                                <td>{{$users_list[$i]->role_name}}</td>
                                <td>@if($users_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                <td>@if(!empty($users_list[$i]->created_at)) {{date('d M Y',strtotime($users_list[$i]->created_at))}} @endif</td>
                                <td>@if(!empty($users_list[$i]->updated_at)) {{date('d M Y',strtotime($users_list[$i]->updated_at))}} @endif</td>
                                <td>
                                    <a href="javascript:;" class="user-list-edit" onclick="editUser({{$users_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a>
                                    
                                    @if($users_list[$i]->user_type == 9)
                                        &nbsp;<a href="{{url('user/stores/list?user_id='.$users_list[$i]->id)}}" class="user-list-edit" ><i title="Manage Stores" class="far fa-edit"></i></a>&nbsp;
                                    @endif

                                    @if($user->user_type == 17)
                                        &nbsp;
                                        <a href="{{url('user/profile/view/'.$users_list[$i]->id)}}" class="user-list-edit" ><i title="View Profile" class="far fa-eye"></i></a>&nbsp;
                                        <a href="{{url('user/salary/list/'.$users_list[$i]->id)}}" class="user-list-edit" ><i title="View Salary" class="far fa-eye"></i></a>&nbsp;
                                    @endif
                                    &nbsp;<a href="{{url('user/permissions/'.$users_list[$i]->id)}}" class="user-list-edit" ><i title="View User Permissions" class="far fa-eye"></i></a>&nbsp;
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $users_list->withQueryString()->links() }}
                    <p>Displaying {{$users_list->count()}} of {{ $users_list->total() }} users.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="edit_user_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editUserSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editUserErrorMessage"></div>

                <form class="" name="editUserFrm" id="editUserFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6" >
                                <label>Name</label>
                                <input id="userName" type="text" class="form-control" name="userName" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_userName"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Email</label>
                                <input id="userEmail" type="text" class="form-control" name="userEmail" value=""  >
                                <div class="invalid-feedback" id="error_validation_userEmail"></div>
                            </div>
                        </div>    

                        <div class="form-group" >
                            <label>Password</label>
                            <input id="userPassword" type="password" class="form-control" name="userPassword" value=""  >
                            <div class="invalid-feedback" id="error_validation_userPassword"></div>
                            <div class="separator-10"></div>
                            <input type="checkbox" name="updatePassword" id="updatePassword"> Click to change Password
                        </div>

                        <div class="form-group">
                            <label>User Type</label>
                            <select name="userType" id="userType" class="form-control" onchange="checkParentUser(this.value,'edit')">
                                <option value="">Select</option>
                                @for($i=0;$i<count($roles_list);$i++)
                                    @if($user->user_type != 17)
                                        
                                            <?php if(request('role_id') == $roles_list[$i]['id']) $sel = 'selected'; else $sel = ''; ?>
                                            <option <?php echo $sel; ?> value="{{$roles_list[$i]['id']}}">{{$roles_list[$i]['role_name']}}</option>
                                        
                                    @endif

                                    @if($user->user_type == 17)
                                        @if(!($roles_list[$i]['id'] == 1 || $roles_list[$i]['id'] == 9 || $roles_list[$i]['id'] == 15))
                                            <?php if(request('role_id') == $roles_list[$i]['id']) $sel = 'selected'; else $sel = ''; ?>
                                            <option <?php echo $sel; ?> value="{{$roles_list[$i]['id']}}">{{$roles_list[$i]['role_name']}}</option>
                                        @endif
                                    @endif
                                @endfor      
                            </select>
                            <div class="invalid-feedback" id="error_validation_userType"></div>
                        </div>

                        <div class="form-group" id="userParent_div_edit" style="display:none;">
                            <label>Head Designer</label>
                            <select name="userParent" id="userParent"  class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($reviewers_list);$i++)
                                    <option value="{{$reviewers_list[$i]['id']}}">{{$reviewers_list[$i]['name']}} ({{$reviewers_list[$i]['email']}}) </option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_userParent"></div>	
                        </div>

                        <div class="form-group" id="userParentPH_div_edit" style="display:none;">
                            <label>Production Head</label>
                            <select name="userParentPH_edit" id="userParentPH_edit"  class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($production_head_list);$i++)
                                    <option value="{{$production_head_list[$i]['id']}}">{{$production_head_list[$i]['name']}} ({{$production_head_list[$i]['email']}})</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_userParentPH_edit"></div>	
                        </div>

                        <div class="form-group" id="userParentWH_div_edit" style="display:none;">
                            <label>Warehouse Head</label>
                            <select name="userParentWH_edit" id="userParentWH_edit"  class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($warehouse_head_list);$i++)
                                    <option value="{{$warehouse_head_list[$i]['id']}}">{{$warehouse_head_list[$i]['name']}} ({{$warehouse_head_list[$i]['email']}})</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_userParentWH_edit"></div>	
                        </div>

                        <div class="form-group" id="userStore_div_edit" style="display:none;">
                            <label>Store</label>
                            <select name="userStore_edit" id="userStore_edit"  class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($stores_list);$i++)
                                    <option value="{{$stores_list[$i]['id']}}">{{$stores_list[$i]['store_name']}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_userStore_edit"></div>	
                        </div>
                        
                        <div class="form-group" id="userStoreUserType_div_edit" style="display:none;">
                            <label>Store User Type</label>
                            <select name="userStoreUserType_edit" id="userStoreUserType_edit"  class="form-control">
                                <option value="">Select</option>
                                <option value="1">Store User</option>
                                <option value="2">Store Owner</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_userStoreUserType_edit"></div>	
                        </div>
                        
                        <div class="form-group" >
                            <label></label>
                            <input type="checkbox" name="viewModifiedReport" id="viewModifiedReport" value="1"> View Modified Report
                        </div>

                    </div>
                    <div class="modal-footer center-footer">
                        <div id="user_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="user_edit_cancel" name="user_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="user_edit_submit" name="user_edit_submit" class="btn btn-dialog" onclick="updateUser();">Submit</button>
                        <input type="hidden" name="user_edit_id" id="user_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_user_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addUserSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addUserErrorMessage"></div>

                <form class="" name="addUserFrm" id="addUserFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6" >
                                <label>Name</label>
                                <input id="userName_add" type="text" class="form-control" name="userName_add" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_userName_add"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Email</label>
                                <input id="userEmail_add" type="text" class="form-control" name="userEmail_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_userEmail_add"></div>
                            </div>
                        </div>    
                        
                        <div class="form-row">
                            <div class="form-group col-md-6" >
                                <label>Password</label>
                                <input id="userPassword_add" type="password" class="form-control" name="userPassword_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_userPassword_add"></div>
                            </div>

                            <div class="form-group col-md-6" >
                                <label>Confirm Password</label>
                                <input id="userPassword_add_confirmation" type="password" class="form-control" name="userPassword_add_confirmation" value=""  >
                                <div class="invalid-feedback" id="error_validation_userPassword_add_confirmation"></div>
                            </div>
                        </div>    

                        <div class="form-group">
                            <label>User Type</label>
                            <select name="userType_add" id="userType_add" onchange="checkParentUser(this.value,'add')" class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($roles_list);$i++)
                                    @if($user->user_type != 17)
                                        @if($roles_list[$i]['id'] != 1)
                                            <?php if(request('role_id') == $roles_list[$i]['id']) $sel = 'selected'; else $sel = ''; ?>
                                            <option <?php echo $sel; ?> value="{{$roles_list[$i]['id']}}">{{$roles_list[$i]['role_name']}}</option>
                                        @endif
                                    @endif

                                    @if($user->user_type == 17)
                                        @if(!($roles_list[$i]['id'] == 1 || $roles_list[$i]['id'] == 9 || $roles_list[$i]['id'] == 15))
                                            <?php if(request('role_id') == $roles_list[$i]['id']) $sel = 'selected'; else $sel = ''; ?>
                                            <option <?php echo $sel; ?> value="{{$roles_list[$i]['id']}}">{{$roles_list[$i]['role_name']}}</option>
                                        @endif
                                    @endif
                                @endfor   
                            </select>
                            <div class="invalid-feedback" id="error_validation_userType_add"></div>	
                        </div>

                        <div class="form-group" id="userParent_div_add" style="display:none;">
                            <label>Head Designer</label>
                            <select name="userParent_add" id="userParent_add"  class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($reviewers_list);$i++)
                                    <option value="{{$reviewers_list[$i]['id']}}">{{$reviewers_list[$i]['name']}} ({{$reviewers_list[$i]['email']}})</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_userParent_add"></div>	
                        </div>

                        <div class="form-group" id="userParentPH_div_add" style="display:none;">
                            <label>Production Head</label>
                            <select name="userParentPH_add" id="userParentPH_add"  class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($production_head_list);$i++)
                                    <option value="{{$production_head_list[$i]['id']}}">{{$production_head_list[$i]['name']}} ({{$production_head_list[$i]['email']}})</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_userParentPH_add"></div>	
                        </div>

                        <div class="form-group" id="userParentWH_div_add" style="display:none;">
                            <label>Warehouse Head</label>
                            <select name="userParentWH_add" id="userParentWH_add"  class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($warehouse_head_list);$i++)
                                    <option value="{{$warehouse_head_list[$i]['id']}}">{{$warehouse_head_list[$i]['name']}} ({{$warehouse_head_list[$i]['email']}})</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_userParentWH_add"></div>	
                        </div>

                        <div class="form-group" id="userStore_div_add" style="display:none;">
                            <label>Store</label>
                            <select name="userStore_add" id="userStore_add"  class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($stores_list);$i++)
                                    <option value="{{$stores_list[$i]['id']}}">{{$stores_list[$i]['store_name']}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_userStore_add"></div>	
                        </div>
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="user_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="user_add_cancel" name="user_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="user_add_submit" name="user_add_submit" class="btn btn-dialog" onclick="submitAddUser();">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($users_list->total(),1000,'/user/list','Download Users List','Users'); ?>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/users.js?v=1.1') }}" ></script>

@endsection
