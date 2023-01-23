@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Purchase Orders')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Purchase Orders'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="productsContainer">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th >Order ID</th><th>Type</th><th>Name</th><th>Vendor</th><th>Quotation Type</th><th>Design SKU</th><th>Purchaser</th><th>Date Created</th><th>Details</th></tr></thead>
                        <tbody>
                             <?php $item_types = array('Fabric','Accessories','Fabric Process','Packaging Sheet','Production Process'); ?>
                            @for($i=0;$i<count($purchase_orders);$i++)
                                <tr>
                                    <td>{{$purchase_orders[$i]->id}}</td>
                                    <td>{{$item_types[$purchase_orders[$i]->item_type_id-1]}}</td>
                                    <td>{{$purchase_orders[$i]->name_id_name}}</td>
                                    <td>{{$purchase_orders[$i]->vendor_name}}</td>
                                    <td>@if($purchase_orders[$i]->quotation_type_id == 1) Bulk @else Design @endif</td>
                                    <td>{{$purchase_orders[$i]->sku}}</td>
                                    <td>{{$purchase_orders[$i]->user_name}}</td>
                                    <td>{{date('d M Y, H:i',strtotime($purchase_orders[$i]->created_at))}}</td>
                                    <td><a href="{{url('purchase-orders/detail/'.$purchase_orders[$i]->id)}}"><i title="Order Details" class="fas fa-eye"></i></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $purchase_orders->withQueryString()->links() }}
                        <p>Displaying {{$purchase_orders->count()}} of {{ $purchase_orders->total() }} orders.</p>
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')

@endsection
