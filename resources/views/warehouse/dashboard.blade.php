@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="storeHeadDashboardErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="storeHeadDashboard">
                <div class="row">
                    <div class="col-md-2"><a href="{{url('purchase-order/product/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Purchase Orders</a></div>
                    <!--<a href="{{url('warehouse/pos/inventory/import')}}" class="btn btn-dialog">Import Inventory</a>-->
                    <div class="col-md-2"><a href="{{url('warehouse/demand/inventory-push/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-truck fas-icon"></i> Inventory Push Demands</a></div>
                    <div class="col-md-2"><a href="{{url('store/demand/inventory-return/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-dolly-flatbed fas-icon"></i> Receive Stock Return</a></div>
                    <div class="col-md-2"><a href="{{url('store/demand/inventory-return-complete/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i> Complete Stock Return</a></div>
                    <div class="col-md-2"><a href="{{url('warehouse/demand/inventory-return-vendor/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i> Stock Return Vendor</a></div>
                    <!--<a href="{{url('store/demand/list')}}" class="btn btn-dialog">Demands List</a>&nbsp;-->
                    
                </div>
                <div class="row">&nbsp;</div>
                <div class="row">
                    <div class="col-md-2"><a href="{{url('warehouse/demand/inventory-assign/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-dolly-flatbed fas-icon"></i> Assign Inventory</a></div>
                    <div class="col-md-2"><a href="{{url('warehouse/inventory/track')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-qrcode fas-icon"></i> Track Inventory</a></div>
                    <div class="col-md-2"><a href="{{url('pos/product/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i> Products</a></div>
                    <div class="col-md-2"><a href="{{url('pos/product/inventory/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i> Products Inventory</a></div>
                    <div class="col-md-2"><a href="{{url('store/report/inventory/status')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-dolly-flatbed fas-icon"></i> Stores Inventory Report</a></div>
                    <!--<a href="{{url('warehouse/pos/inventory/add')}}" class="btn btn-dialog">Add Products Inventory</a>
                    <a href="{{url('purchase-orders/list')}}" class="btn btn-dialog">Purchase Orders</a>-->
                </div>
                <div class="row">&nbsp;</div>
                <div class="row">
                    <div class="col-md-2"><a href="{{url('warehouse/report/inventory/status')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i> Warehouse Inventory Status</a></div>
                    <div class="col-md-2"><a href="{{url('warehouse/report/inventory/daily/update')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-qrcode fas-icon"></i> Inventory In/Out Report</a></div>
                    <div class="col-md-2"><a href="{{url('warehouse/report/inventory/balance')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i> Inventory Balance Report</a></div>
                    <div class="col-md-2"><a href="{{url('purchase-order/purchased/products')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon"></i> Purchase Details</a></div>
                    <div class="col-md-2"><a href="{{url('store/stock/details')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-warehouse fas-icon"></i> Store Stock Details</a></div>
                </div>
                <div class="row">&nbsp;</div>
                <div class="row">
                    <div class="col-md-2"><a href="{{url('purchase-order/stock/details')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i> Stock Details</a></div>
                    <div class="col-md-2"><a href="{{url('vendor/sku/inventory/report')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-qrcode fas-icon"></i> Vendor SKU Inventory Report</a> </div>
                    <div class="col-md-2"><a href="{{url('store/sku/inventory/report')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-boxes fas-icon"></i> Store SKU Inventory Report</a></div>
                    <div class="col-md-2"><a href="{{url('purchase-order/bulk/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Fabric Purchase Orders</a></div>
                    <div class="col-md-2"><a href="{{url('purchase-order/accessories/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> Accessories Purchase Orders</a></div>
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
