@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Purchase Orders','link'=>'purchase-orders/list'),array('name'=>'Purchase Order Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Purchase Order Detail'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger" style="display:none;"></div>

            <div id="productsContainer">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th>Order ID</th><th>Purchaser</th><th>Type</th><th>Status</th><th>Date Created</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>{{$purchase_order_detail->id}}</td>
                                <td>{{$purchase_order_detail->user_name}}</td>
                                <td>{{($purchase_order_detail->type_id == 1)?'Bulk':'Design'}}</td>
                                <td></td>
                                <td>{{date('d M Y, H:i',strtotime($purchase_order_detail->created_at))}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <hr>

                <h5>Order Items</h5>

                <?php $count = 0; $item_types = array('Fabric','Accessories','Fabric Process','Packaging Sheet','Production Process'); ?>
                 <div class="table-responsive table-filter">
                    @if($purchase_order_detail->type_id == 1)
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>Type</th><th>Name</th><th>Quality</th><th>Color</th><th>Vendor</th><th>Quantity</th><th>Cost</th><th>Status</th></tr></thead>
                            @for($i=0;$i<count($purchase_order_items);$i++)
                                <tr>
                                    <td>{{$item_types[$purchase_order_items[$i]->item_type_id-1]}}</td>
                                    <td>{{$purchase_order_items[$i]->name_id_name}}</td>
                                    <td>{{$purchase_order_items[$i]->quality_id_name}}</td>
                                    <td>{{$purchase_order_items[$i]->color_id_name}}</td>
                                    <td>{{$purchase_order_items[$i]->vendor_name}}</td>
                                    <td>{{$purchase_order_items[$i]->qty_ordered}}</td>
                                    <td>{{$purchase_order_items[$i]->cost}}</td>
                                    <td></td>
                                    <?php $count++; ?>
                                </tr>

                            @endfor

                            @if($count == 0)
                                <tr><td colspan="10" align="center">No Records</td></tr>
                            @endif
                        </table>
                    @endif
                    
                    @if($purchase_order_detail->type_id == 2)
                        <table class="table table-striped admin-table" cellspacing="0">
                        @foreach($purchase_order_items as $design_id=>$data)    
                        <tr><th class="header-tr" colspan="6" style="border-bottom: 1px solid #fff;">Design SKU: {{$data['design_data']['sku']}}</th></tr>
                            <tr class="header-tr"><th>Type</th><th>Name</th><th>Vendor</th><th>Quantity</th><th>Cost</th><th>Status</th></tr>
                            @for($i=0;$i<count($data['order_items']);$i++)
                                <tr>
                                    <td>{{$item_types[$data['order_items'][$i]->item_type_id-1]}}</td>
                                    <td>{{$data['order_items'][$i]->name_id_name}}</td>
                                    <td>{{$data['order_items'][$i]->vendor_name}}</td>
                                    <td>{{$data['order_items'][$i]->qty_ordered}}</td>
                                    <td>{{$data['order_items'][$i]->cost}}</td>
                                    <td></td>
                                </tr>
                            @endfor 
                        @endforeach    
                        </table>    
                    @endif
                </div>
                
                <div class="separator-10"></div>
                
                @if($purchase_order_detail->type_id == 2)
                    <h5>Receive GRN Products</h5>
                    <table class="table admin-table" cellspacing="0">
                    <tr class="header-tr"><th >Design SKU</th><th>Quantity</th><th>Comments</th></tr>
                    @foreach($purchase_order_items as $design_id=>$data)    
                        <tr>
                            <th>{{$data['design_data']['sku']}}</th>
                            <th width="150"><input type="text" class="form-control" style="width:100px;border:1px solid #ccc;"></th>
                            <th><textarea class="form-control" style="border:1px solid #ccc;"></textarea></th>
                        </tr>
                    @endforeach    
                    <tr><td colspan="3"><button type="button" class="btn_add"  style="border:none;margin-left: 30%;">Receive Products</button> </td></tr>
                    </table>    
                @endif
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')

@endsection
