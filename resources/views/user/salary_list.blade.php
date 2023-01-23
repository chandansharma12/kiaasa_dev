@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'User Salary List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'User Salary List: '.$user_data->name); ?>
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                   
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2">
                        <a href="javascript:;" onclick="addUserSalary();" class="btn btn-dialog" >Add Salary </a>&nbsp;
                    </div>
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <input type="hidden" name="user_id" id="user_id" value="{{$user_data->id}}">
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>Month</th><th>Year</th><th>Net Salary</th><th>Status</th><th>Created on </th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($salary_list);$i++)
                                    <tr>
                                        <td>{{date('F',strtotime($salary_list[$i]->salary_year.'/'.$salary_list[$i]->salary_month.'/1'))}}</td>
                                        <td>{{$salary_list[$i]->salary_year}}</td>
                                        <td>{{$salary_list[$i]->net_salary}}</td>
                                        <td>{{str_replace('_',' ',$salary_list[$i]->status)}}</td>
                                        <td>{{date('d M Y',strtotime($salary_list[$i]->created_at))}}</td>
                                        <td>
                                            <a href="{{url('user/salary/edit/'.$salary_list[$i]->id)}}" ><i title="Edit Salary" class="far fa-edit"></i></a> &nbsp;
                                            <a href="{{url('user/salary/view/'.$salary_list[$i]->id)}}" ><i title="View Salary" class="far fa-eye"></i></a> &nbsp;
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $salary_list->links() }} <p>Displaying {{$salary_list->count()}} of {{ $salary_list->total() }} User Salary.</p>
                        
                    </div> 
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="salary_add_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Salary</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="salaryAddSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="salaryAddErrorMessage"></div>

                <form class="" name="salaryAddFrm" id="salaryAddFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Month</label>
                                <select name="salaryMonth_add" id="salaryMonth_add" class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<=8;$i++)
                                        <option value="{{date('m_Y',strtotime("-$i month"))}}">{{date('F Y',strtotime("-$i month"))}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_salaryMonth_add"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="salary_add_cancel" name="salary_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="salary_add_submit" name="salary_add_submit" class="btn btn-dialog" onclick="submitAddUserSalary();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/users.js?v=1.2') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
