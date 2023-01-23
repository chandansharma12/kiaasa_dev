@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Users Attendance List','link'=>'user/attendance/list'),array('name'=>'Edit Users Attendance')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Users Attendance'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section >
        <div class="container-fluid" >
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="date" id="date" placeholder="Date" value="@if(!empty(request('date'))){{request('date')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
                <div id="updateAttendancErrorMessage" class="alert alert-danger elem-hidden"></div>
                <div id="updateAttendancSuccessMessage" class="alert alert-success elem-hidden"></div>
                
                <div id="demandList">
                    <form method="post" name="DailyAttendanceFrm" id="DailyAttendanceFrm">
                        <div class="table-responsive table-filter">
                            <button type="button" class="btn btn-dialog" id="editAttendanceBtn" name="editAttendanceBtn" onclick="editDailyAttendance('edit'); "><i title="Edit" class="fas fa-edit"></i> Edit</button>
                            <button type="button" id="updateAttendanceBtn_cancel" class="btn btn-secondary elem-hidden" onclick="editDailyAttendance('view'); "><i title="Cancel" class="fas fa-caret-left"></i> Cancel</button>
                            <button type="button" class="btn btn-dialog elem-hidden" id="updateAttendanceBtn_submit" name="updateAttendanceBtn_submit" onclick="updateDailyAttendance();"> <i title="Update" class="fas fa-save"></i> Save</button>

                            <div class="clear"><br/></div>

                            <table class="table table-striped admin-table" cellspacing="0" style="font-size:13px; ">
                                <thead><tr class="header-tr">
                                    <th>Name</th><th>Type</th><th>{{date('d M Y',strtotime($date))}}</th>
                                </tr></thead>
                                <tbody>
                                    <?php $attendance_codes = CommonHelper::getUserAttendanceList(); ?>
                                    @for($i=0;$i<count($user_list);$i++)
                                        <?php $user_id = $user_list[$i]->id; ?>        
                                        <?php $attendance = (isset($attendance_list[$user_id]))?$attendance_list[$user_id]:array();?>                                      
                                        <tr>
                                            <td>{{$user_list[$i]->name}}</td>
                                            <td>{{$user_list[$i]->role_name}}</td>
                                            
                                            <?php $date_key = date('Y_m_d',strtotime($date)); ?>
                                            <?php $code = (isset($attendance[$date_key]))?$attendance[$date_key]['attendance_status']:-1; ?>
                                            
                                            @if(isset($users_leaves[$user_id]))
                                                <td class="attendance-view ">{{CommonHelper::getUserLeaveText($users_leaves[$user_id])}}</td>
                                                <td class="attendance-edit elem-hidden">{{CommonHelper::getUserLeaveText($users_leaves[$user_id])}}
                                                    <input type="hidden" name="attendance_{{$user_id}}" id="attendance_{{$user_id}}" value="{{($users_leaves[$user_id] == 'full_day')?0:2}}">
                                                </td>
                                            @else
                                                <td class="attendance-view ">{{CommonHelper::getUserAttendanceText($code)}}</td>
                                                <td class="attendance-edit elem-hidden">
                                                    <select name="attendance_{{$user_id}}" id="attendance_{{$user_id}}" class="form-control attendance-select">
                                                        <option value="-1">{{CommonHelper::getUserAttendanceText(-1)}}</option>
                                                        @foreach($attendance_codes as $attend_code=>$text)
                                                            <?php $sel = ($attend_code == $code)?'selected':''; ?>
                                                            <option {{$sel}} value="{{$attend_code}}">{{$text}}</option>
                                                        @endforeach
                                                    </select>    
                                                </td>
                                            @endif
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>

                            {{ $user_list->links() }} <p>Displaying {{$user_list->count()}} of {{ $user_list->total() }} Users.</p>

                            <br/>
                        </div> 
                        <input type="hidden" name="user_ids" id="user_ids" value="{{implode(',',$users_ids)}}"/>
                        <input type="hidden" name="date" id="date" value="{{$date}}"/>
                    </form>    
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/users.js?v=1.2') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy',endDate: '-0d'});</script>
@endsection
