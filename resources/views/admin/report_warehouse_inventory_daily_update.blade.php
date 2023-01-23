@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Warehouse Inventory Daily In/Out Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Warehouse Inventory Daily In/Out Report');$page_name = 'warehouse_inventory_daily_in_out_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div> 
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    <div class="col-md-2"><a href="{{url('warehouse/report/inventory/daily/update?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File" ><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    
                    <?php  $total_data = array('inv_in_vendor'=>0,'inv_in_store'=>0,'total_in'=>0,'inv_out_store'=>0,'inv_out_vendor'=>0,'inv_out_vendor_defective'=>0,'total_out'=>0); ?>
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort static-header-tbl" cellspacing="0"  id="storeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th style="width:10%;">Date</th>
                                    <th colspan="3" aligm="center" style="text-align: center;border-left:1px solid #fff;">Warehouse In</th>
                                    <th colspan="4" align="center" style="text-align: center;border-left:1px solid #fff;">Warehouse Out</th>
                                </tr>
                                <tr class="header-tr">
                                    <th></th>
                                    <th style="border-left:1px solid #fff;">PO Invoices from Vendor</th>
                                    <th>SOR Return from Store</th>
                                    <th>Total In</th>
                                    <th style="border-left:1px solid #fff;">Inventory Push to Store</th>
                                    <th>SOR Return to Vendor</th>
                                    <th>QC Defective Return to Vendor</th>
                                    <th>Total Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($dates_list);$i++)
                                    <tr>
                                        <td>{{date('d-m-Y',strtotime($dates_list[$i]['date']))}}</td>
                                        <td>{{$inv_in_vendor = (isset($dates_list[$i]['inventory_in_from_vendor']))?$dates_list[$i]['inventory_in_from_vendor']:0}}</td>
                                        <td>{{$inv_in_store = (isset($dates_list[$i]['inventory_in_from_store']))?$dates_list[$i]['inventory_in_from_store']:0}}</td>
                                        <td>{{$total_in = $inv_in_vendor+$inv_in_store}}</td>
                                        <td>{{$inv_out_store = (isset($dates_list[$i]['inventory_out_to_store']))?$dates_list[$i]['inventory_out_to_store']:0}}</td>
                                        <td>{{$inv_out_vendor = (isset($dates_list[$i]['inventory_out_to_vendor']))?$dates_list[$i]['inventory_out_to_vendor']:0}}</td>
                                        <td>{{$inv_out_vendor_defective = (isset($dates_list[$i]['inventory_out_to_vendor_defective']))?$dates_list[$i]['inventory_out_to_vendor_defective']:0}}</td>
                                        <td>{{$total_out = $inv_out_store+$inv_out_vendor+$inv_out_vendor_defective}}</td>
                                    </tr>
                                    <?php $total_data['inv_in_vendor']+=$inv_in_vendor; ?>    
                                    <?php $total_data['inv_in_store']+=$inv_in_store; ?>      
                                    <?php $total_data['total_in']+=$total_in; ?>      
                                    <?php $total_data['inv_out_store']+=$inv_out_store; ?>      
                                    <?php $total_data['inv_out_vendor']+=$inv_out_vendor; ?>      
                                    <?php $total_data['inv_out_vendor_defective']+=$inv_out_vendor_defective; ?>
                                    <?php $total_data['total_out']+=$total_out; ?>      
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th>{{$total_data['inv_in_vendor']}}</th>
                                    <th>{{$total_data['inv_in_store']}}</th>
                                    <th>{{$total_data['total_in']}}</th>
                                    <th>{{$total_data['inv_out_store']}}</th>
                                    <th>{{$total_data['inv_out_vendor']}}</th>
                                    <th>{{$total_data['inv_out_vendor_defective']}}</th>
                                    <th>{{$total_data['total_out']}}</th>
                                </tr>
                            </tfoot>
                        </table>
                        <br/>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>

<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>

@endsection
