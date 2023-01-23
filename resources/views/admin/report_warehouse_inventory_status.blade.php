@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Warehouse Inventory Status Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Warehouse Inventory Status Report');$page_name = 'warehouse_inventory_status_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    <div class="col-md-2"><a href="{{url('warehouse/report/inventory/status?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <?php $status_list = CommonHelper::getposProductStatusList(); ?>
                    <?php unset($status_list[6]); $statuses_total = array(); ?>
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    @foreach($status_list as $status_id=>$status_name)
                                        <th>{{str_replace('Store','Stores',$status_name)}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $grand_total = 0; ?>
                                <tr>
                                    @foreach($status_list as $status_id=>$status_name)
                                        <td>{{$status_total = (isset($warehouse_inventory['status_'.$status_id]))?$warehouse_inventory['status_'.$status_id]:0}}</td>
                                        <?php $grand_total+=$status_total; ?>
                                    @endforeach
                                    <td>{{$grand_total}}</td>
                                </tr>
                            </tbody>    
                            <tfoot>
                                <tr>
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
<script type="text/javascript">//$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{ asset('js/datatables.min.js') }}" ></script>
<link rel="stylesheet" type="text/css" href="{{asset('css/datatables.min.css')}}"/>
<script type="text/javascript">
    $(document).ready(function(){
        //$('.report-sort').DataTable({ "autoWidth": false,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"order": [] });
    });
</script>
@endsection
