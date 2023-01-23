@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $store_name = (isset($store_data) && !empty($store_data))?ucwords(strtolower($store_data->store_name)).' ('.$store_data->store_id_code.')' :' All Stores'; ?>
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Category Sales Report'));$page_name = 'category_sales_report_graph'; ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Category Sales Report - '.$store_name); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    @if($user->user_type != 9)
                        <div class="col-md-2" >
                            <select name="store_id" id="store_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>
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
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div class="separator-10">&nbsp;</div>
                <div id="salesQuantityChart" ></div>
                
                <div class="separator-10">&nbsp;</div>
                <div id="salesNetPriceChart" ></div>
                
                <div class="separator-10">&nbsp;</div>
                <div id="salesPaymentMethodChart" ></div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>

@if(empty($error_message))

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load("current", {packages:['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
        // Payment quantity graph
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Category');
        data.addColumn('number', 'Sales Quantity');

        data.addRows([
        <?php for($i=0;$i<count($category_sales);$i++){ ?>
            ["<?php echo $category_sales[$i]->category_name ?>",<?php echo $category_sales[$i]->cat_qty ?>],
          <?php } ?>      
        ]);
        
        var options = {
            title: "Category Sales | Type: Sales Quantity | Store: <?php echo $store_name; ?> | Date: <?php echo date('d-m-Y',strtotime($start_date)) ?> - <?php echo date('d-m-Y',strtotime($end_date)) ?>",
            height: 500,
            bar: {groupWidth: "85%"},
            legend: { position: "none" },
        };
        
        var chart = new google.visualization.ColumnChart(document.getElementById("salesQuantityChart"));
        chart.draw(data, options);
        
        // Payment net price graph
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Category');
        data.addColumn('number', 'Sales Net Price');

        data.addRows([
        <?php for($i=0;$i<count($category_sales_price);$i++){ ?>
            ["<?php echo $category_sales_price[$i]->category_name ?>",<?php echo round($category_sales_price[$i]->cat_net_price,2) ?>],
          <?php } ?>      
        ]);
        
        var options = {
            title: "Category Sales | Type: Sales Net Price | Store: <?php echo $store_name; ?> | Date: <?php echo date('d-m-Y',strtotime($start_date)) ?> - <?php echo date('d-m-Y',strtotime($end_date)) ?>",
            height: 500,
            bar: {groupWidth: "85%"},
            legend: { position: "none" },
        };
        
        var chart = new google.visualization.ColumnChart(document.getElementById("salesNetPriceChart"));
        chart.draw(data, options);
        
        // Payment method graph
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Payment Method');
        data.addColumn('number', 'Sales Net Price');

        data.addRows([
        <?php for($i=0;$i<count($payment_sales);$i++){ ?>
            ["<?php echo $payment_sales[$i]->payment_method ?>",<?php echo round($payment_sales[$i]->total_payment_amount,2) ?>],
          <?php } ?>      
        ]);
        
        var options = {
            title: "Payment Method | Store: <?php echo $store_name; ?> | Date: <?php echo date('d-m-Y',strtotime($start_date)) ?> - <?php echo date('d-m-Y',strtotime($end_date)) ?>",is3D: true,
        };
        
        var chart = new google.visualization.PieChart(document.getElementById("salesPaymentMethodChart"));
        chart.draw(data, options);
  }
</script>

@endif

@endsection
