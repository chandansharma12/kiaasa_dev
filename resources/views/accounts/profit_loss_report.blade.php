@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Profit Loss Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Profit Loss Report'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{$start_date = request('startDate')}}@else{{$start_date = date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{$end_date = request('endDate')}}@else{{$end_date = date('d-m-Y')}}@endif">
                        </div>
                    </div>
                                    
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    <div class="col-md-2"><a href="{{url('report/profit-loss?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            
            <div id="usersContainer">
                
                <div style="width:2720px">&nbsp;</div>
                <div class="table-responsive" style="width:2700px;">
                    <table class="table table-striped clearfix admin-table static-header-tbl" cellspacing="0" id="vendorListTbl" style="font-size: 12px;">
                        <thead>
                            <tr class="header-tr">
                                <th colspan="4" align="center" style="text-align: center;">Store Info</th>
                                <th colspan="6" align="center" style="text-align: center;border-left: 1px solid #fff;">Top Line</th>    
                                <th colspan="7" align="center" style="text-align: center;border-left: 1px solid #fff;">Occupancy Cost</th>
                                <th colspan="6" align="center" style="text-align: center;border-left: 1px solid #fff;">Manpower Cost</th>    
                                <th colspan="4" align="center" style="text-align: center;border-left: 1px solid #fff;">Selling Handling</th>    
                                <th colspan="2" align="center" style="text-align: center;border-left: 1px solid #fff;">Total Expenditure</th>    
                                <th colspan="2" align="center" style="text-align: center;border-left: 1px solid #fff;border-right: 1px solid #fff;">EBITDA</th>    
                            </tr>
                            <tr class="header-tr">
                                <th style="border-top: none;">SNo.</th>
                                <th style="border-top: none;">Store Code</th> 
                                <th style="border-top: none;">Store Name</th>
                                <th style="border-right: 1px solid #fff;border-top: none;">Date</th>
                                <th style="border-top: none;">No. of PCS</th>
                                <th style="border-top: none;">Turnover</th>
                                <th style="border-top: none;">COGS</th>
                                <th style="border-top: none;">GM</th>
                                <th style="border-top: none;">%</th>
                                <th style="border-right: 1px solid #fff;border-top: none;">ASP</th>
                                <th style="border-top: none;">Rent</th>
                                <th style="border-top: none;">Cam</th>
                                <th style="border-top: none;">Total</th>
                                <th style="border-top: none;">GST</th>
                                <th style="border-top: none;">TDS</th>
                                <th style="border-top: none;">Total</th>
                                <th style="border-right: 1px solid #fff;border-top: none;">%</th>
                                <th style="border-top: none;">Comm</th>
                                <th style="border-top: none;">GST</th>
                                <th style="border-top: none;">TDS</th>
                                <th style="border-top: none;">Total</th>
                                <th style="border-top: none;">Salary</th>
                                <th style="border-right: 1px solid #fff;border-top: none;">%</th>
                                <th style="border-top: none;">Imprest</th>
                                <th style="border-top: none;">Bank Charges</th>
                                <th style="border-top: none;">Electricity</th>
                                <th style="border-right: 1px solid #fff;border-top: none;">Others</th>
                                <th style="border-top: none;">Total</th>
                                <th style="border-right: 1px solid #fff;border-top: none;">%</th>
                                <th style="border-top: none;">EBITDA</th>
                                <th style="border-right: 1px solid #fff;border-top: none;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $start_date = date('d-m-Y',strtotime($start_date));$end_date = date('d-m-Y',strtotime($end_date)); ?>
                            @for($i=0;$i<count($store_list);$i++)
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$store_list[$i]['store_id_code']}}</td>
                                    <td>{{$store_list[$i]['store_name']}}</td>
                                    <td>{{$start_date}} - {{$end_date}}</td>
                                    <td>{{$store_list[$i]['top_line_pcs']}}</td>
                                    <td>{{round($store_list[$i]['top_line_turn_over'],3)}}</td>
                                    <td>{{round($store_list[$i]['top_line_cogs'],3)}}</td>
                                    <td>{{round($store_list[$i]['top_line_gm'],3)}}</td>
                                    <td>{{$store_list[$i]['top_line_percent']}} %</td>
                                    <td>{{$store_list[$i]['top_line_asp']}}</td>
                                    <td>{{round($store_list[$i]['oc_rent'],3)}}</td>
                                    <td>{{round($store_list[$i]['oc_cam'],3)}}</td>
                                    <td>{{round($store_list[$i]['oc_rent']+$store_list[$i]['oc_cam'],3)}}</td>
                                    <td>{{round($store_list[$i]['oc_gst_val'],3)}}</td>
                                    <td>{{round($store_list[$i]['oc_tds_val'],3)}}</td>
                                    <td>{{round($store_list[$i]['oc_total_val'],3)}}</td>
                                    <td>{{$store_list[$i]['oc_percent']}} %</td>
                                    <td>{{round($store_list[$i]['mc_comm_val'],3)}}</td>
                                    <td>{{round($store_list[$i]['mc_gst_val'],3)}}</td>
                                    <td>{{round($store_list[$i]['mc_tds_val'],3)}}</td>
                                    <td>{{round($store_list[$i]['mc_total_val'],3)}}</td>
                                    <td>{{round($store_list[$i]['salary'],3)}}</td>
                                    <td>{{$store_list[$i]['mc_percent']}}</td>
                                    <td>{{round($store_list[$i]['sh_imprest'],3)}}</td>
                                    <td>{{round($store_list[$i]['sh_bank_charges_val'],3)}}</td>
                                    <td>{{round($store_list[$i]['sh_electricity'],3)}}</td>
                                    <td>{{round($store_list[$i]['sh_others'],3)}}</td>
                                    <?php $total_expenditure = $store_list[$i]['total_expenditure']; ?>
                                    <td>{{round($total_expenditure,3)}}</td>
                                    <td>{{($store_list[$i]['top_line_turn_over'] > 0)?round(($total_expenditure/$store_list[$i]['top_line_turn_over'])*100,3):0}} %</td>
                                    <?php $ebitda = $store_list[$i]['ebitda']; ?>
                                    <td>{{round($ebitda,3)}}</td>    
                                    <td>{{($store_list[$i]['top_line_turn_over'] > 0)?round(($ebitda/$store_list[$i]['top_line_turn_over'])*100,3):0}} %</td>        
                                </tr>
                            @endfor
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Total</th>
                                <th>{{$total_data['top_line_pcs']}}</th>
                                <th>{{round($total_data['top_line_turn_over'],3)}}</th>
                                <th>{{round($total_data['top_line_cogs'],3)}}</th>
                                <th>{{round($total_data['top_line_turn_over']-$total_data['top_line_cogs'],3)}}</th>
                                <th>{{($total_data['top_line_turn_over'] > 0)?round((($total_data['top_line_turn_over']-$total_data['top_line_cogs'])/$total_data['top_line_turn_over'])*100,3):0}} %</th>
                                <th>{{($total_data['top_line_pcs'] > 0)?round($total_data['top_line_turn_over']/$total_data['top_line_pcs'],3):0}}</th>
                                <th>{{round($total_data['oc_rent'],3)}}</th>
                                <th>{{round($total_data['oc_cam'],3)}}</th>
                                <th>{{round($total_data['oc_rent']+$total_data['oc_cam'],3)}}</th>
                                <th>{{round($total_data['oc_gst_val'],3)}}</th>
                                <th>{{round($total_data['oc_tds_val'],3)}}</th>
                                <th>{{round($total_data['oc_total_val'],3)}}</th>
                                <th>{{($total_data['top_line_turn_over'] > 0)?round(($total_data['oc_total_val']/$total_data['top_line_turn_over'])*100,3):0}} %</th>
                                <th>{{round($total_data['mc_comm_val'],3)}}</th>
                                <th>{{round($total_data['mc_gst_val'],3)}}</th>
                                <th>{{round($total_data['mc_tds_val'],3)}}</th>
                                <th>{{round($total_data['mc_total_val'],3)}}</th>
                                <th>{{round($total_data['salary'],3)}}</th>
                                <th>{{($total_data['top_line_turn_over'] > 0)?round(($total_data['salary']/$total_data['top_line_turn_over'])*100,3):0}} %</th>
                                <th>{{round($total_data['sh_imprest'],3)}}</th>
                                <th>{{round($total_data['sh_bank_charges_val'],3)}}</th>
                                <th>{{round($total_data['sh_electricity'],3)}}</th>
                                <th>{{round($total_data['sh_others'],3)}}</th>
                                <?php $total_expenditure = $total_data['total_expenditure']; ?>
                                <th>{{round($total_expenditure,3)}}</th>
                                <th>{{($total_data['top_line_turn_over'] > 0)?round(($total_expenditure/$total_data['top_line_turn_over'])*100,3):0}} %</th>
                                <?php $ebitda = $total_data['ebitda']; ?>
                                <th>{{round($ebitda,3)}}</th>    
                                <th>{{($total_data['top_line_turn_over'] > 0)?round(($ebitda/$total_data['top_line_turn_over'])*100,3):0}} %</th>        
                            </tr>
                        </tfoot>
                    </table>
                    <br/>    
                </div>
            </div>
        </div>
    </section>
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
<script type="text/javascript">
    $(document).ready(function(){ autoInsertMonthlyExpenseData(); });
</script>
@endsection
