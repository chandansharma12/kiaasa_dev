@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Price Slot Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Price Slot Sales Report'); $page_name = 'price_slot_sales_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get" id="searchForm" name="searchForm">
                <div class="row justify-content-end" >
                    @if($user->user_type != 9)
                        <div class="col-md-3" >
                            <select name="s_id" id="s_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2"><a href="{{url('price/report/sales?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th>MRP Slot</th>
                                    <th>Total Units</th>
                                    <th>Avg Unit Net Price</th>
                                    <th>Total NET Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_units = $total_price = 0; ?>
                                @for($i=0;$i<count($price_slots);$i++)
                                    <tr>
                                        <td>{{$price_slots[$i]['min']}} - {{$price_slots[$i]['max']}}</td>
                                        <td>{{$price_slots[$i]['prod_count']}}</td>
                                        <td>@if($price_slots[$i]['prod_count'] > 0) {{CommonHelper::currencyFormat(round($price_slots[$i]['prod_net_price']/$price_slots[$i]['prod_count'],2))}} @else 0.00 @endif </td>
                                        <td>{{CommonHelper::currencyFormat($price_slots[$i]['prod_net_price'])}}</td>
                                        <td><a href="javascript:;" onclick="getPriceSlotCategoryData({{$price_slots[$i]['min']}},{{$price_slots[$i]['max']}});" class="table-link">Categories &raquo;</a></td>
                                    </tr>
                                    <?php $total_units+=$price_slots[$i]['prod_count']; ?>
                                    <?php $total_price+=$price_slots[$i]['prod_net_price']; ?>
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th>{{$total_units}}</th>
                                    <th></th>
                                    <th>{{CommonHelper::currencyFormat($total_price)}}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                        <br/>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="category_report_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="category_report_title">Category Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="categoryReportSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="categoryReportErrorMessage"></div>

                <form class="" name="categoryReportFrm" id="categoryReportFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body" id="category_report_content">
                            
                        </div>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/category.js') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{ asset('js/datatables.min.js') }}" ></script>
<link rel="stylesheet" type="text/css" href="{{asset('css/datatables.min.css')}}"/>
<script type="text/javascript">
    $(document).ready(function(){
        $('.report-sort').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [] });
    });
</script>
@endsection
