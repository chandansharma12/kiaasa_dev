@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Users Attendance List','link'=>'user/attendance/list'),array('name'=>'Employee Overtime List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Employee Overtime List'); ?>
    
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
                    <div class="col-md-2"><a href="javascript:;" onclick="addUserOverTime();" class="btn btn-dialog" >Add Overtime </a></div>
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
                                    <th>Date</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                    <th>Date Added</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($overtime_list);$i++)
                                    <tr>
                                        <td>{{$overtime_list[$i]->user_name}} ({{$overtime_list[$i]->role_name}})</td>
                                        <td>{{date('d M Y',strtotime($overtime_list[$i]->overtime_date)) }}</td>
                                        <td>{{$overtime_list[$i]->overtime_hours}}</td>
                                        <td>{{$overtime_list[$i]->overtime_status}}</td>
                                        <td>{{date('d M Y',strtotime($overtime_list[$i]->created_at)) }}</td>
                                        <td>
                                            <a href="javascript:;" onclick="editUserOverTime({{$overtime_list[$i]->id}});" ><i title="Edit Overtime" class="far fa-edit"></i></a> &nbsp;
                                            <a href="javascript:;" onclick="deleteUserOverTime({{$overtime_list[$i]->id}});" ><i title="Delete Overtime" class="fas fa-trash"></i></a> &nbsp;
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $overtime_list->links() }} <p>Displaying {{$overtime_list->count()}} of {{ $overtime_list->total() }} Overtime.</p>
                        
                    </div> 
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="overtime_add_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Overtime</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="overtimeAddSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="overtimeAddErrorMessage"></div>

                <form class="" name="overtimeAddFrm" id="overtimeAddFrm" type="POST">
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
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Overtime Date</label>
                                    <input id="overTimeDate_add" type="text" class="form-control datepicker" name="overTimeDate_add" value="">
                                    <div class="invalid-feedback" id="error_validation_overTimeDate_add"></div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Overtime Hours</label>
                                    <select id="overTimeHours_add" class="form-control" name="overTimeHours_add" >
                                        <option value="">Select</option>
                                        @for($i=1;$i<=24;$i++)
                                            <option value="{{$i}}">{{$i}}</option>
                                        @endfor
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_overTimeHours_add"></div>
                                </div>
                            </div>
                            <div class="form-row" >
                                <div class="form-group col-md-6" >
                                    <label>Status</label>
                                    <select id="overtimeStatus_add" type="text" class="form-control" name="overtimeStatus_add">
                                        <option value="">Select</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="disapproved">Disapproved</option>
                                    </select>        
                                    <div class="invalid-feedback" id="error_validation_overtimeStatus_add"></div>
                                </div>
                            </div>    
                            <div class="form-group">
                                <label>Reason/Comments</label>
                                <textarea name="overtimeComments_add" id="overtimeComments_add" class="form-control"></textarea>
                                <div class="invalid-feedback" id="error_validation_overtimeComments_add"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                       <button type="button" id="overtime_add_cancel" name="overtime_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="overtime_add_submit" name="overtime_add_submit" class="btn btn-dialog" onclick="submitAddUserOverTime();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="overtime_edit_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Overtime</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="overtimeEditSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="overtimeEditErrorMessage"></div>

                <form class="" name="overtimeEditFrm" id="overtimeEditFrm" type="POST">
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
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Overtime Date</label>
                                    <input id="overTimeDate_edit" type="text" class="form-control datepicker" name="overTimeDate_edit" value="">
                                    <div class="invalid-feedback" id="error_validation_overTimeDate_edit"></div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Overtime Hours</label>
                                    <select id="overTimeHours_edit" class="form-control" name="overTimeHours_edit" >
                                        <option value="">Select</option>
                                        @for($i=1;$i<=24;$i++)
                                            <option value="{{$i}}">{{$i}}</option>
                                        @endfor
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_overTimeHours_edit"></div>
                                </div>
                            </div>
                            <div class="form-row" >
                                
                                <div class="form-group col-md-6" >
                                    <label>Status</label>
                                    <select id="overtimeStatus_edit" type="text" class="form-control" name="overtimeStatus_edit">
                                        <option value="">Select</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="disapproved">Disapproved</option>
                                    </select>        
                                    <div class="invalid-feedback" id="error_validation_overtimeStatus_edit"></div>
                                </div>
                            </div>    
                            <div class="form-group">
                                <label>Reason/Comments</label>
                                <textarea name="overtimeComments_edit" id="overtimeComments_edit" class="form-control"></textarea>
                                <div class="invalid-feedback" id="error_validation_overtimeComments_edit"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="overtime_edit_cancel" name="overtime_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="overtime_edit_submit" name="overtime_edit_submit" class="btn btn-dialog" onclick="updateUserOverTime();">Submit</button>
                        <input type="hidden" name="overtime_id_edit" id="overtime_id_edit" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="overtime_delete_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden" id="overtimeDeleteSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="overtimeDeleteErrorMessage"></div>
                
                <div class="modal-body">
                    <h6>Are you sure to delete overtime ?<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="overtime_delete_cancel">Cancel</button>
                    <button type="button" id ="overtime_delete_submit" name="overtime_delete_submit" class="btn btn-dialog">Submit</button>
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
<script type="text/javascript">$('#overTimeDate_add,#overTimeDate_edit').datepicker({format: 'dd-mm-yyyy'});;$('#daterange_search').datepicker({format: 'dd-mm-yyyy'})</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
