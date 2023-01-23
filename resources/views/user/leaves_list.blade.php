@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Users Attendance List','link'=>'user/attendance/list'),array('name'=>'Employee Leaves List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Employee Leaves List'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateLeaveStatusErrorMessage" class="alert alert-danger elem-hidden"  ></div>
            <div id="updateLeaveStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <div class="input-group input-daterange" id="daterange_search">
                            <input type="text" autocomplete="off" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" autocomplete="off" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2"><a href="javascript:;" onclick="addUserLeave();" class="btn btn-dialog" >Add Leave </a></div>
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>Name</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Date Added</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($leaves_list);$i++)
                                    <tr>
                                        <td>{{$leaves_list[$i]->user_name}} ({{$leaves_list[$i]->role_name}})</td>
                                        <td>{{date('d M Y',strtotime($leaves_list[$i]->from_date)) }}</td>
                                        <td>{{date('d M Y',strtotime($leaves_list[$i]->to_date)) }}</td>
                                        <td>{{CommonHelper::dateDiff($leaves_list[$i]->from_date,$leaves_list[$i]->to_date)+1}} days</td>
                                        <td>{{$leaves_list[$i]->leave_status}}</td>
                                        <td>{{date('d M Y',strtotime($leaves_list[$i]->created_at)) }}</td>
                                        <td>
                                            <a href="javascript:;" onclick="editUserLeave({{$leaves_list[$i]->id}});" ><i title="Edit Leave" class="far fa-edit"></i></a> &nbsp;
                                            <a href="javascript:;" onclick="deleteUserLeave({{$leaves_list[$i]->id}});" ><i title="Delete Leave" class="fas fa-trash"></i></a> &nbsp;
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $leaves_list->links() }} <p>Displaying {{$leaves_list->count()}} of {{ $leaves_list->total() }} Leaves.</p>
                        
                    </div> 
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="leave_add_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Leave</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="leaveAddSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="leaveAddErrorMessage"></div>

                <form class="" name="leaveAddFrm" id="leaveAddFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Employee</label>
                                <select name="userId_add" id="userId_add" class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($user_list);$i++)
                                        <option value="{{$user_list[$i]->id}}">{{$user_list[$i]->name}} ({{$user_list[$i]->role_name}})</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_userId_add"></div>
                            </div>
                            <div class="form-row input-group input-daterange" id="daterange_add">
                                <div class="form-group col-md-6">
                                    <label>Leave From</label>
                                    <input id="leaveFrom_add" type="text" class="form-control datepicker" name="leaveFrom_add" value="" autocomplete="off">
                                    <div class="invalid-feedback" id="error_validation_leaveFrom_add"></div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Leave To</label>
                                    <input id="leaveTo_add" type="text" class="form-control datepicker" name="leaveTo_add" value="" autocomplete="off">
                                    <div class="invalid-feedback" id="error_validation_leaveTo_add"></div>
                                </div>
                            </div>
                            <div class="form-row" >
                                <div class="form-group col-md-6" >
                                    <label>Leave Type</label>
                                    <select id="leaveType_add" type="text" class="form-control" name="leaveType_add">
                                        <option value="">Select</option>
                                        <option value="full_day">Full Day</option>
                                        <option value="half_day">Half Day</option>
                                    </select>        
                                    <div class="invalid-feedback" id="error_validation_leaveType_add"></div>
                                </div>
                                <div class="form-group col-md-6" >
                                    <label>Status</label>
                                    <select id="leaveStatus_add" type="text" class="form-control" name="leaveStatus_add">
                                        <option value="">Select</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="disapproved">Disapproved</option>
                                    </select>        
                                    <div class="invalid-feedback" id="error_validation_leaveStatus_add"></div>
                                </div>
                            </div>    
                            <div class="form-group">
                                <label>Reason/Comments</label>
                                <textarea name="leaveComments_add" id="leaveComments_add" class="form-control"></textarea>
                                <div class="invalid-feedback" id="error_validation_leaveComments_add"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                       <button type="button" id="leave_add_cancel" name="leave_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="leave_add_submit" name="leave_add_submit" class="btn btn-dialog" onclick="submitAddUserLeave();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="leave_edit_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Leave</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="leaveEditSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="leaveEditErrorMessage"></div>

                <form class="" name="leaveEditFrm" id="leaveEditFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Employee</label>
                                <select name="userId_edit" id="userId_edit" class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($user_list);$i++)
                                        <option value="{{$user_list[$i]->id}}">{{$user_list[$i]->name}} ({{$user_list[$i]->role_name}})</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_userId_edit"></div>
                            </div>
                            <div class="form-row input-group input-daterange" id="daterange_edit">
                                <div class="form-group col-md-6">
                                    <label>Leave From</label>
                                    <input id="leaveFrom_edit" type="text" class="form-control datepicker" name="leaveFrom_edit" value="" autocomplete="off">
                                    <div class="invalid-feedback" id="error_validation_leaveFrom_edit"></div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Leave To</label>
                                    <input id="leaveTo_edit" type="text" class="form-control datepicker" name="leaveTo_edit" value="" autocomplete="off">
                                    <div class="invalid-feedback" id="error_validation_leaveTo_edit"></div>
                                </div>
                            </div>
                            <div class="form-row" >
                                <div class="form-group col-md-6" >
                                    <label>Leave Type</label>
                                    <select id="leaveType_edit" type="text" class="form-control" name="leaveType_edit">
                                        <option value="">Select</option>
                                        <option value="full_day">Full Day</option>
                                        <option value="half_day">Half Day</option>
                                    </select>        
                                    <div class="invalid-feedback" id="error_validation_leaveType_edit"></div>
                                </div>
                                <div class="form-group col-md-6" >
                                    <label>Status</label>
                                    <select id="leaveStatus_edit" type="text" class="form-control" name="leaveStatus_edit">
                                        <option value="">Select</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="disapproved">Disapproved</option>
                                    </select>        
                                    <div class="invalid-feedback" id="error_validation_leaveStatus_edit"></div>
                                </div>
                            </div>    
                            <div class="form-group">
                                <label>Reason/Comments</label>
                                <textarea name="leaveComments_edit" id="leaveComments_edit" class="form-control"></textarea>
                                <div class="invalid-feedback" id="error_validation_leaveComments_edit"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="leave_edit_cancel" name="leave_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="leave_edit_submit" name="leave_edit_submit" class="btn btn-dialog" onclick="updateUserLeave();">Submit</button>
                        <input type="hidden" name="leave_id_edit" id="leave_id_edit" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="leave_delete_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden" id="leaveDeleteSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="leaveDeleteErrorMessage"></div>
                
                <div class="modal-body">
                    <h6>Are you sure to delete leave ?<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="leave_delete_cancel">Cancel</button>
                    <button type="button" id ="leave_delete_submit" name="leave_delete_submit" class="btn btn-dialog">Submit</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/users.js?v=1.2') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('#daterange_add,#daterange_edit').datepicker({format: 'dd-mm-yyyy',startDate: '-3m'});$('#daterange_search').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
