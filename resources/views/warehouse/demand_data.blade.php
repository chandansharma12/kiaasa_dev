@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Demand Detail'); ?>
    <?php $size_data = array(); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            Demand Ref No: {{$demand_data->invoice_no}}
            <hr/>

            <div class="separator-10"></div>
            
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <h5>Products List</h5>
                    <div class="table-responsive table-filter">
                        
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th>S No</th><th>QR Code</th><th>Product</th><th>SKU</th><th>Status</th><th>Store</th><th>Demand No</th><th>Demand ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 0; ?>
                                @for($i=0;$i<count($inv_list);$i++)
                                    <?php $product_data = $inv_list[$i];  ?>
                                    <tr>
                                        <td>{{++$count}}</td>
                                        <td>{{$product_data->peice_barcode}}</td>
                                        <td>{{$product_data->product_name}} {{$product_data->size_name}} {{$product_data->color_name}}</td>
                                        <td>{{$product_data->product_sku}}</td>
                                        <td>{{CommonHelper::getposProductStatusName($product_data->product_status)}}</td>
                                        <td>{{$product_data->store_name}}</td>
                                        <td>{{$product_data->invoice_no}}</td>
                                        <td>{{$product_data->demand_id}}</td>
                                    </tr>
                                @endfor
                               
                            </tbody>
                        </table>
                        
                    </div>
                    
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')

<script type="text/javascript">
    var page_type = 'detail';
    $(document).ready(function(){
        
    });
    
</script>
@endsection
