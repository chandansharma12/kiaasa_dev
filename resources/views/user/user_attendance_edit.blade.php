@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    @if($page_action == 'edit')
        <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'User Attendance','link'=>'user/attendance/list'),array('name'=>'Edit User Attendance')); ?>
        <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit User Attendance: '.$user_data->name.' ('.$user_data->role_name.')'); ?>
    @else
        <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'User Attendance','link'=>'user/attendance/list'),array('name'=>'View User Attendance')); ?>
        <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'View User Attendance: '.$user_data->name.' ('.$user_data->role_name.')'); ?>
    @endif
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateAttendanceErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="updateAttendanceSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <?php $days = CommonHelper::dateDiff($start_date,$end_date); ?>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime("-$days days"))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <form method="post" name="userAttendanceform" id="userAttendanceform">
                    <input type="hidden" name="start_date" id="start_date" value="{{$start_date}}">
                    <input type="hidden" name="end_date" id="end_date" value="{{$end_date}}">
                    <input type="hidden" name="user_id" id="user_id" value="{{$user_data->id}}">
                
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px;width:50%; ">
                            <thead>
                                <tr class="header-tr"><th>Day </th><th>Attendance</th></tr>
                            </thead>   
                            <tbody>
                                <?php $attendance_codes = CommonHelper::getUserAttendanceList(); ?>
                                @for($i=0;$i<=$days;$i++)
                                    <tr>
                                        <th class="report-sel-td" style="width:50%;border-bottom:1px solid #fff; ">{{date('d-m-y',strtotime("+$i days",strtotime($start_date)))}} 
                                        ({{date('D',strtotime("+$i days",strtotime($start_date)))}})</th>
                                        
                                        <?php $date = date('Y_m_d',strtotime("+$i days",strtotime($start_date))); ?>
                                        @if(isset($users_leaves[$date]))
                                            <td>{{($users_leaves[$date] == 'half_day')?'HD Leave':'FD Leave'}}
                                                <input name="attendance_{{$date}}" id="attendance_{{$date}}" type="hidden" value="{{($users_leaves[$date] == 'full_day')?0:2}}">
                                            </td>
                                        @else
                                            <?php $code = (isset($attendance_list[$date]))?$attendance_list[$date]['attendance_status']:-1; ?>
                                            <td>
                                                @if($page_action == 'edit')
                                                    <select name="attendance_{{$date}}" id="attendance_{{$date}}" class="form-control attendance-select">
                                                        <option value="-1">{{CommonHelper::getUserAttendanceText(-1)}}</option>
                                                        @foreach($attendance_codes as $attend_code=>$text)
                                                            <?php $sel = ($attend_code == $code)?'selected':''; ?>
                                                            <option {{$sel}} value="{{$attend_code}}">{{$text}}</option>
                                                        @endforeach
                                                    </select>    
                                                @else
                                                    {{CommonHelper::getUserAttendanceText($code)}}
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endfor
                                
                                @if($page_action == 'edit')
                                    <tr>
                                        <td colspan="2" align="center">
                                            <button type="button" id="update_attendance_cancel" name="update_attendance_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('user/attendance/list')}}'">Cancel</button>
                                            <input type="button" name="update_attendance_btn" id="update_attendance_btn" class="btn btn-dialog" value="Update" onclick="updateUserAttendance();">
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div> 
                </form>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/users.js?v=1.2') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
