@extends('layouts.pdf')
@section('content')
<?php $currency = CommonHelper::getCurrency(); ?>
<section class="product_area">
    <div class="container-fluid">
        <div id="demandContainer" class="table-container">
            <div id="demandsList">
                <div class="table-responsive table-filter">
                    <div class="separator-10"><br><br></div>
                    <h5>Demand Detail</h5>
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th>Demand ID</th><th>Demand Status</th><th>Comments</th><th>Created On</th><th>Created by</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>{{$demand_data->id}}</td>
                                <td>{{str_replace('_',' ',$demand_data->demand_status)}}</td>
                                <td>{{$demand_data->comments}}</td>
                                <td>{{date('d M Y',strtotime($demand_data->created_at))}}</td>
                                <td>{{$demand_data->user_name}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
        
                <div class="table-responsive table-filter">
                    <div class="separator-10"><br><br></div>
                    <h5>Demands Products</h5>
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th>Product Name</th><th>Quantity</th><!--<th>Product SKU</th><th>Product Barcode</th>--><th>Unit Price</th><th>Total Price</th></tr></thead>
                        <tbody>
                            <?php $total_price = $inventory_count = 0; ?>
                            @for($i=0;$i<count($products_list);$i++)
                                <tr>
                                    <td>{{$products_list[$i]->product_name}}</td>
                                    <td>{{$products_list[$i]->inventory_count}}</td>
                                    <!--<td>{{$products_list[$i]->product_sku}}</td>
                                    <td>{{$products_list[$i]->product_barcode}}</td> -->
                                    <td>{{$currency}} {{$products_list[$i]->sale_price}}</td>
                                    <td>{{$currency}} {{round($products_list[$i]->sale_price*$products_list[$i]->inventory_count,2)}}</td>
                                </tr>
                                <?php $total_price+=($products_list[$i]->sale_price*$products_list[$i]->inventory_count); ?>
                                <?php $inventory_count+=($products_list[$i]->inventory_count); ?>
                            @endfor
                            <tr class="total-tr"><td><strong>Total</strong><td><strong> {{$inventory_count}}</strong></td><td></td><td><strong>{{$currency}} {{round($total_price,2)}}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection