@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    @if($page_action == 'edit')
        <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Salary List','link'=>'user/salary/list/'.$salary_data->user_id),array('name'=>'Edit User Salary')); ?>
        <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit User Salary: '.$salary_data->user_name.' ('.date('F Y',strtotime($start_date)).')' ); ?>
    @else
        <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Salary List','link'=>'user/salary/list/'.$salary_data->user_id),array('name'=>'View User Salary')); ?>
        <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'View User Salary: '.$salary_data->user_name.' ('.date('F Y',strtotime($start_date)).')' ); ?>
    @endif
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <input type="hidden" name="salary_id" id="salary_id" value="{{$salary_data->id}}">
            
            <div id="demandContainer" class="table-container">
                
                <?php $total = array('approved_leaves'=>0,'unapproved_leaves'=>0,'overtime_hours'=>0); ?>
                <div class="table-responsive table-filter">
                    <h6>Approved Leaves:</h6>
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>From Date</th><th>To Date</th><th>Days</th><th>Type</th><th>Status</th><th>Created on </th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($user_leaves);$i++)
                                <tr>
                                    <td>{{date('d M Y',strtotime($user_leaves[$i]['from_date'])) }}</td>
                                    <td>{{date('d M Y',strtotime($user_leaves[$i]['to_date'])) }}</td>
                                    <td>{{$days = $user_leaves[$i]['salary_month_days']}} days</td>
                                    <td>{{ucwords(str_replace('_',' ',$user_leaves[$i]['leave_type']))}}</td>
                                    <td>{{ucwords($user_leaves[$i]['leave_status'])}}</td>
                                    <td>{{date('d M Y',strtotime($user_leaves[$i]['created_at'])) }}</td>
                                </tr>
                                <?php $leave_add = ($user_leaves[$i]['leave_type'] == 'full_day')?1:0.5; ?>
                                <?php $total['approved_leaves']+=($days*$leave_add); ?>
                            @endfor
                        </tbody>
                        <tfoot>
                            <th colspan="2">Total</th>
                            <th>{{$total['approved_leaves']}} days</th>
                        </tfoot>
                    </table>
                </div> 
                
               <div class="table-responsive table-filter">
                    <h6>Absents:</h6>
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>Date</th><th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($full_day_absent_timestamps);$i++)
                                <tr>
                                    <td>{{date('d M Y',$full_day_absent_timestamps[$i]) }}</td>
                                    <td>Full Day</td>
                                </tr>
                                <?php $total['unapproved_leaves']+=1; ?>
                            @endfor
                            
                            @for($i=0;$i<count($half_day_absent_timestamps);$i++)
                                <tr>
                                    <td>{{date('d M Y',$half_day_absent_timestamps[$i]) }}</td>
                                    <td>Half Day</td>
                                </tr>
                                <?php $total['unapproved_leaves']+=0.5; ?>
                            @endfor
                        </tbody>
                        <tfoot>
                            <th>Total</th>
                            <th>{{$total['unapproved_leaves']}} days</th>
                        </tfoot>
                    </table>
                </div> 

                <div class="table-responsive table-filter">
                    <h6>Approved Overtimes:</h6>
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>Date</th><th>Hours</th><th>Hourly Rate</th><th>Status</th><th>Created on </th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($user_overtime);$i++)
                                <tr>
                                    <td>{{date('d M Y',strtotime($user_overtime[$i]['overtime_date'])) }}</td>
                                    <td>{{$user_overtime[$i]['overtime_hours']}}</td>
                                    <td>{{$profile_data->overtime_hourly_rate}}</td>
                                    <td>{{ucwords($user_overtime[$i]['overtime_status'])}}</td>
                                    <td>{{date('d M Y',strtotime($user_overtime[$i]['created_at'])) }}</td>
                                </tr>
                                <?php $total['overtime_hours']+=$user_overtime[$i]['overtime_hours']; ?>
                            @endfor
                        </tbody>
                        <tfoot>
                            <th>Total</th>
                            <th>{{$total['overtime_hours']}} hours</th>
                        </tfoot>
                    </table>
                </div> 
                
                <form method="POST" name="salaryEditForm" id="salaryEditForm">
                    <div id="updateSalaryErrorMessage" class="alert alert-danger elem-hidden"></div>
                    <div id="updateSalarySuccessMessage" class="alert alert-success elem-hidden"></div>
                    <div class="table-responsive table-filter">
                       
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                            <thead>
                                <tr class="header-tr"><th colspan="2">Create Salary</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>Employee</th>
                                    <td>{{$salary_data->user_name}} ({{$salary_data->role_name}})</td>
                                </tr>
                                <tr>
                                    <th>Salary Month</th>
                                    <td>{{date('F Y',strtotime($start_date))}}</td>
                                </tr>
                                <tr>
                                    <th>Annual CTC</th>
                                    <td>INR {{CommonHelper::currencyFormat($salary_data->annual_ctc)}}</td>
                                </tr>
                                <tr>
                                    <th>Monthly Salary</th>
                                    <td>INR {{CommonHelper::currencyFormat($salary_data->monthly_salary)}}</td>
                                </tr>
                                <tr>
                                    <th>Basic</th>
                                    <td>
                                        <input type="text" name="basic" id="basic" class="form-control numeric-text" value="{{$salary_data->basic}}">
                                        <div class="invalid-feedback" id="error_validation_basic"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>DA</th>
                                    <td>
                                        <input type="text" name="da" id="da" class="form-control numeric-text" value="{{$salary_data->da}}">
                                        <div class="invalid-feedback" id="error_validation_da"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>HRA</th>
                                    <td>
                                        <input type="text" name="hra" id="hra" class="form-control numeric-text" value="{{$salary_data->hra}}">
                                        <div class="invalid-feedback" id="error_validation_hra"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Conveyance</th>
                                    <td>
                                        <input type="text" name="conveyance" id="conveyance" class="form-control numeric-text" value="{{$salary_data->conveyance}}">
                                        <div class="invalid-feedback" id="error_validation_conveyance"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Medical</th>
                                    <td>
                                        <input type="text" name="medical" id="medical" class="form-control numeric-text" value="{{$salary_data->medical}}">
                                        <div class="invalid-feedback" id="error_validation_medical"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>LTA</th>
                                    <td>
                                        <input type="text" name="lta" id="lta" class="form-control numeric-text" value="{{$salary_data->lta}}">
                                        <div class="invalid-feedback" id="error_validation_lta"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Overtime Wages</th>
                                    <td>
                                        <input type="text" name="overtime_wages" id="overtime_wages" class="form-control numeric-text" value="{{$salary_data->overtime_wages}}">
                                        <div class="invalid-feedback" id="error_validation_overtime_wages"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Leave Deduction</th>
                                    <td>
                                        <input type="text" name="leaves_deduction" id="leaves_deduction" class="form-control numeric-text" value="{{$salary_data->leaves_deduction}}">
                                        <div class="invalid-feedback" id="error_validation_leaves_deduction"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>PF Deduction</th>
                                    <td>
                                        <input type="text" name="pf_deduction" id="pf_deduction" class="form-control numeric-text" value="{{$salary_data->pf_deduction}}">
                                        <div class="invalid-feedback" id="error_validation_pf_deduction"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Net Salary</th>
                                    <td>
                                        <input type="text" name="net_salary" id="net_salary" class="form-control numeric-text" value="{{!empty($salary_data->net_salary)?$salary_data->net_salary:$salary_data->monthly_salary}}">
                                        <div class="invalid-feedback" id="error_validation_net_salary"></div>
                                        <input type="hidden" name="overtime_hours" id="overtime_hours" value="{{$total['overtime_hours']}}">
                                        <input type="hidden" name="overtime_hourly_rate" id="overtime_hourly_rate" value="{{$profile_data->overtime_hourly_rate}}">
                                        <input type="hidden" name="approved_leaves" id="approved_leaves" value="{{$total['approved_leaves']}}">
                                        <input type="hidden" name="unapproved_leaves" id="unapproved_leaves" value="{{$total['unapproved_leaves']}}">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Comments</th>
                                    <td>
                                        <textarea name="comments" id="comments" class="form-control" maxlength="250">{{$salary_data->comments}}</textarea>
                                    </td>
                                </tr>
                                @if($page_action == 'edit')
                                    <tr>
                                        <th></th>
                                        <td>
                                            <button type="button" id="update_salary_cancel" name="update_salary_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('user/salary/list/'.$salary_data->user_id)}}'">Cancel</button>
                                            <input type="button" name="update_salary_btn" id="update_salary_btn" class="btn btn-dialog" value="Update Salary" onclick="updateUserSalary();">
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
<script>@if($page_action == 'view') $(".form-control").attr('readonly',true).css('border','none'); @endif</script>
@endsection
