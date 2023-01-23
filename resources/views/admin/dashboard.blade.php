@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="administratorDashboardErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="curve_chart" style="width: 900px; height: 500px"></div>
        </div>
    </section>

@endif

@endsection

@if(empty($error_message))
@section('scripts')
  
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {

      var data = new google.visualization.DataTable();
      data.addColumn('date', 'Date');
      data.addColumn('number', 'Orders');

      
        data.addRows([
          <?php for($i=0;$i<count($pos_orders);$i++){ ?>
            <?php $timestamp = strtotime($pos_orders[$i]['date_created']); ?>          
            [new Date(<?php echo date('Y',$timestamp) ?>,<?php echo date('m',$timestamp)-1 ?>,<?php echo date('d',$timestamp) ?>),<?php echo $pos_orders[$i]['orders_count'] ?>],
          <?php } ?>      
        ]);


        var options = {
            title: 'POS Orders',
            background:'transparent',
            width: 1000,
            height:400,
            chartArea: {width: '90%'},
            backgroundColor:'transparent'
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
    </script>
  
    
@endsection
@endif