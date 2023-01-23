@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'User Attendance List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'User Attendance List'); ?>
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
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
                    <div class="col-md-2">
                        <a href="{{url('user/attendance/edit')}}" class="btn btn-dialog" >Edit Attendance </a>&nbsp;
                        <!--<a href="{{url('user/leaves/list')}}" class="btn btn-dialog" >Manage Leaves </a>
                        <a href="{{url('user/overtime/list')}}" class="btn btn-dialog" >Manage Overtime </a>-->
                    </div>
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>Name</th><th>Type</th>
                                    @for($i=0;$i<=$days;$i++)
                                        <th>{{date('d-m-y',strtotime("+$i days",strtotime($start_date)))}}</th>
                                    @endfor
                                    <th>Action</th>
                                </tr>
                                <tr class="sub-tr">
                                    <th colspan="2"></th>
                                    @for($i=0;$i<=$days;$i++)
                                        <th>{{date('D',strtotime("+$i days",strtotime($start_date)))}}</th>
                                    @endfor
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($user_list);$i++)
                                    <?php $user_id = $user_list[$i]->id; ?>        
                                    <?php $attendance = (isset($attendance_list[$user_id]))?$attendance_list[$user_id]:array(); ?>                                      
                                    <tr>
                                        <td>{{$user_list[$i]->name}}</td>
                                        <td>{{$user_list[$i]->role_name}}</td>
                                        @for($q=0;$q<=$days;$q++)
                                            <?php $date = date('Y_m_d',strtotime("+$q days",strtotime($start_date))); ?>
                                            @if(isset($users_leaves[$user_id][$date]))
                                                <td>{{($users_leaves[$user_id][$date] == 'half_day')?'HD Leave':'FD Leave'}}</td>
                                            @else
                                                <?php $code = (isset($attendance[$date]))?$attendance[$date]['attendance_status']:-1; ?>
                                                <td>{{CommonHelper::getUserAttendanceText($code)}}</td>
                                            @endif
                                        @endfor
                                        <td>
                                            <a href="{{url('user/attendance/edit/'.$user_list[$i]->id)}}" ><i title="Edit Attendance" class="far fa-edit"></i></a> &nbsp;
                                            <a href="{{url('user/attendance/view/'.$user_list[$i]->id)}}" ><i title="View Attendance" class="far fa-eye"></i></a>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $user_list->links() }} <p>Displaying {{$user_list->count()}} of {{ $user_list->total() }} Users.</p>
                        
                    </div> 
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
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
