@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
<?php $count = 1; ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Report Types')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Report Types '); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="s_id" id="s_id" class="form-control" onchange="this.form.submit();">
                            <option value="">-- Store --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = (isset($store_data->id) && $store_list[$i]['id'] == $store_data->id)?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </form>
            
            <div class="separator-10">&nbsp;</div>
            <div class="alert alert-success alert-dismissible elem-hidden" id="updateReportTypeSuccessMessage"></div>
            <div class="alert alert-danger alert-dismissible elem-hidden"  id="updateReportTypeErrorMessage"></div>
    
            @if(isset($store_data) && !empty($store_data))
                <form method="post" name="reportTypeForm" id="reportTypeForm">
                    <div id="orderContainer" class="table-container">
                        <div class="table-responsive table-filter">
                            <table class="table table-striped admin-table" cellspacing="0" style="font-size:13px; ">
                                <thead><tr class="header-tr">
                                    <th>SNo</th>
                                    <th>Report</th>
                                    <th>Report Type</th>
                                </tr></thead>
                                <tbody>
                                    @foreach($reports as $report=>$report_name)
                                        <tr>
                                            <td>{{$count++}}</td>
                                            <td>{{$report_name}}</td>
                                            <td>
                                                <select name="{{$report}}" id="{{$report}}" class="form-control">
                                                    <option value="">Report Type</option>
                                                    @foreach($report_types as $report_type=>$report_type_name)
                                                        <?php $sel = (isset($store_reports[$report]) && strtolower($store_reports[$report]) == strtolower($report_type))?'selected':''; ?>
                                                        <option {{$sel}} value="{{$report_type}}">{{$report_type_name}}</option>
                                                    @endforeach
                                                </select>    
                                            </td>
                                        </tr>
                                    @endforeach 
                                </tbody>
                            </table>
                            <input type="hidden" name="store_id" id="store_id" value="{{isset($store_data->id)?$store_data->id:''}}">
                            <center><input type="button" name="reports_types_btn" id="reports_types_btn" value="Update" class="btn btn-dialog" onclick="updateReportTypes();"></center>
                        </div>
                    </div>
                </form>
            @endif
            
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js') }}" ></script>
@endsection
