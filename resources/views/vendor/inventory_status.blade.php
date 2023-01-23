@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendor Inventory Status')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendor Inventory Status'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    
                    <?php $inv_total = 0; ?>
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th>In Warehouse</th>
                                    <th>In Transit</th>
                                    <th>In Stores</th>
                                    <th>Sold</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{$status_data['inv_in_warehouse']}}</td>
                                    <td>{{$status_data['inv_in_transit']}}</td>
                                    <td>{{$status_data['inv_in_store']}}</td>
                                    <td>{{$status_data['inv_sold']}}</td>
                                    <td>{{$status_data['inv_total']}}</td>
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
