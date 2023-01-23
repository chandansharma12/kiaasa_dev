<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<!-- Required meta tags -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/developer.css?v=1.50') }}">
<script>var ROOT_PATH = "{{url('/')}}"; </script>
<title>{{ config('app.name', 'Kiaasa') }}</title>
</head>
<body>
<!-- Strat Header -->  
<header class="kiaasa_header">
    <div class="container-fluid">
        <div class="row align-items-md-center">
            <div class="col-md-4 logo">
                <a href="{{ url('/') }}"><img src="{{asset('images/logo.png')}}" alt="{{ config('app.name', 'Kiaasa') }}" /></a>
            </div> 
            <div class="col-md-4 text-center">
                <h2>Procurement Portal</h2>
            </div>
            <div class="col-md-4 kiaasa_header_right navbar">
                <ul class="navbar-nav ml-auto">
                    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                    @if(isset(Auth::user()->name))
                        <li class="dropdown">
                            <a class="nav-link" href="#" id="notificationDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-bell" ></i>
                                <span class="badge badge-danger badge-counter" id="notificationBadge"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="notificationDropdown" id="notificationContent">

                            </div>
                        </li> 
                        <!-- Nav Item - User Information -->

                        <li class="dropdown">
                            <a class="nav-link dropdown-toggle pr-0" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span>{{ Auth::user()->name }}</span>
                                <i class="fa fa-angle-down"></i>
                                <img class="img-profile rounded-circle" src="{{asset('images/proimg.jpg')}}" alt="" />
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="{{url('dashboard')}}"> 
                                    Dashboard
                                </a>
                                <a class="dropdown-item" href="{{url('user/editprofile')}}"> 
                                    My Profile ({{Auth::user()->getRoleName()}})
                                </a>
                                <?php $other_roles = Auth::user()->getOtherRoles(); ?>
                                @for($i=0;$i<count($other_roles);$i++)
                                    <a class="dropdown-item" href="javascript:;" onclick="switchRoleHeader({{$other_roles[$i]->id}});" >  Switch as {{$other_roles[$i]->role_name}} </a>
                                @endfor    

                                <a class="dropdown-item" href="{{url('/logout')}}"> 
                                    Logout
                                </a>

                            </div>
                        </li>
                    @endif
                </ul>
            </div> 
        </div>
    </div>
    <form method="post" action="{{url('user/updaterole')}}" name="switchRoleFrmHeader" id="switchRoleFrmHeader">
        <input type="hidden" name="switch_role_id_header" id="switch_role_id_header" value="">
        @csrf
    </form>
</header>

<?php /* ?> @if(isset(Auth::user()->name) && in_array(Auth::user()->user_type,array(1,11,12,13,14,16))) <?php */ ?>
    <div class="bs-example" style="text-align: center;">
        <div class="btn-group">
            @if(in_array(Auth::user()->user_type,array(1)))
                <div class="btn-group">
                    <button type="button" class="btn btn-dialog dropdown-toggle" data-toggle="dropdown">Designs</button>
                    <div class="dropdown-menu">
                        <a href="{{url('design/list')}}" class="dropdown-item">Designs List</a>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-dialog dropdown-toggle" data-toggle="dropdown">Purchase Orders</button>
                    <div class="dropdown-menu">
                        <a href="{{url('purchase-order/product/list')}}" class="dropdown-item">Product Purchase Orders</a>
                        <a href="{{url('purchase-order/accessories/list')}}" class="dropdown-item">Accessories Purchase Orders</a>
                        <a href="{{url('purchase-order/bulk/list')}}" class="dropdown-item">Bulk Purchase Orders</a>
                        <a href="{{url('accessories/list')}}" class="dropdown-item">Non Trading Items</a>
                        <a href="{{url('vendor/accessories/list')}}" class="dropdown-item">Vendor Non Trading Items</a>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-dialog dropdown-toggle" data-toggle="dropdown">Demands</button>
                    <div class="dropdown-menu">
                        <a href="{{url('warehouse/demand/inventory-push/list')}}" class="dropdown-item">Warehouse to Store</a>
                        <a href="{{url('store/demand/inventory-return/list')}}" class="dropdown-item">Store to Warehouse</a>
                        <a href="{{url('store/demand/inventory-transfer-store/list')}}" class="dropdown-item">Store to Store</a>
                        <a href="{{url('warehouse/demand/inventory-return-vendor/list')}}" class="dropdown-item">Warehouse to Vendor</a>
                        <a href="{{url('store/demand/inventory-return-complete/list')}}" class="dropdown-item">Complete Inventory Return</a>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-dialog dropdown-toggle" data-toggle="dropdown">User Management</button>
                    <div class="dropdown-menu">
                        <a href="{{url('user/list')}}" class="dropdown-item">Users</a>
                        <a href="{{url('vendor/list')}}" class="dropdown-item">Vendors</a>
                        <a href="{{url('user/activity/list')}}" class="dropdown-item">User Activity List</a>
                    </div>
                </div>     
            
                <div class="btn-group">
                    <button type="button" class="btn btn-dialog dropdown-toggle" data-toggle="dropdown">Content Management</button>
                    <div class="dropdown-menu">
                        <a href="{{url('story/list')}}"  class="dropdown-item">Story List</a>
                        <!--<a href="{{url('product/list')}}" class="dropdown-item">Products List</a>-->
                        <a href="{{url('lookup-item/list')}}" class="dropdown-item">Lookup Items List</a>
                        <a href="{{url('store/list')}}" class="dropdown-item">Stores List</a>       
                        <!--<a href="{{url('store/asset/list')}}" class="dropdown-item">Store Assets</a>           
                        <a href="{{url('store/asset/order/list')}}" class="dropdown-item">Store Assets Orders</a>      
                        <a href="{{url('store/asset/order/items-list')}}" class="dropdown-item">Store Assets Orders Items</a>
                        <a href="{{url('store/demand/list')}}" class="dropdown-item">Store Demands</a>-->
                        <a href="{{url('pos/order/list')}}" class="dropdown-item">Pos Orders</a>
                        <a href="{{url('pos/customer/list')}}" class="dropdown-item">Pos Customers</a>
                        <a href="{{url('pos/product/list')}}" class="dropdown-item">Pos Products</a>
                        <a href="{{url('pos/product/inventory/list')}}" class="dropdown-item">Pos Products Inventory</a>
                        <a href="{{url('warehouse/inventory/track')}}" class="dropdown-item">Track Inventory</a>
                        <!--<a href="{{url('warehouse/demand/inventory-assign/list')}}" class="dropdown-item">Assign Inventory Demands</a>-->
                        <a href="{{url('discount/list')}}" class="dropdown-item">Discounts</a>
                        <!--<a href="{{url('discounts/list')}}" class="dropdown-item">Discounts List</a>-->
                        <a href="{{url('store/bags/inventory/list')}}" class="dropdown-item">Store Bags Inventory</a>
                        <a href="{{url('coupon/list')}}" class="dropdown-item">Coupons</a>
                        <a href="{{url('hsn/gst/list')}}" class="dropdown-item">HSN Codes GST</a>
                        <a href="{{url('category/hsn/list')}}" class="dropdown-item">Category HSN Code</a>
                        <a href="{{url('credit/notes/list')}}" class="dropdown-item">Credit Notes</a>
                        <a href="{{url('debit/notes/list')}}" class="dropdown-item">Debit Notes</a>
                        <a href="{{url('audit/list')}}" class="dropdown-item">Audits</a>
                        <a href="{{url('size/list')}}" class="dropdown-item">Size List</a>
                        <a href="{{url('scheduler/task/list')}}" class="dropdown-item">Scheduler Tasks</a>
                        <a href="{{url('store/expense/monthly/list/1')}}" class="dropdown-item">Store Monthly Expenses</a>
                        <a href="{{url('vendor/inventory/payment/list')}}" class="dropdown-item">Vendor Inventory Payments</a>
                        <a href="{{url('store/report-types')}}" class="dropdown-item">Report Types</a>
                    </div>
                </div> 
            @endif
            
            @if(in_array(Auth::user()->user_type,array(1,11,12,13,14,16)))
                <div class="btn-group">
                    <button type="button" class="btn btn-dialog dropdown-toggle" data-toggle="dropdown">Sales Reports</button>
                    <div class="dropdown-menu">
                        <a href="{{url('store/report/sales/dates')}}" class="dropdown-item">Daily Sales Report</a>
                        <a href="{{url('vendor/report/sales')}}" class="dropdown-item">Vendor Sales Report</a>
                        <a href="{{url('report/store/to/customer')}}" class="dropdown-item">Store wise Bill wise Report</a>
                        <a href="{{url('report/store/to/customer?report_type=date')}}" class="dropdown-item">Store wise Date wise Report</a>
                        <a href="{{url('report/store/to/customer?report_type=month')}}" class="dropdown-item">Store wise Month wise Report</a>
                        <a href="{{url('report/store/to/customer?report_type=hsn_code')}}" class="dropdown-item">Store wise HSN Code wise Report</a>
                        <a href="{{url('report/warehouse/to/store')}}" class="dropdown-item">HO Sales Report</a>
                        <a href="{{url('report/warehouse/to/store?report_type=hsn_code')}}" class="dropdown-item">HO HSN Code wise Sales Report</a>
                        <a href="{{url('report/store/to/store?from_store_id=50')}}" class="dropdown-item">Store Sales Report</a>
                        <a href="{{url('report/store/to/store?report_type=hsn_code&from_store_id=50')}}" class="dropdown-item">Store HSN Code wise Sales Report</a>
                        <a href="{{url('report/hsn/bill/sales')}}" class="dropdown-item">HSN Code Bill Sales Report</a>
                        <a href="{{url('report/profit-loss')}}" class="dropdown-item">Profit Loss Report</a>
                        <a href="{{url('store/report/discount/types')}}" class="dropdown-item">Discount Type Report</a>
                        <a href="{{url('category/report/sales')}}" class="dropdown-item">Category Sales Report</a>
                        <a href="{{url('category/report/sales/graph')}}" class="dropdown-item">Category Sales Report Graph</a>
                        <a href="{{url('category/detail/report/sales')}}" class="dropdown-item">Category Detail Sales Report</a>
                        <a href="{{url('size/report/sales')}}" class="dropdown-item">Size Sales Report</a>
                        <a href="{{url('store/report/sales')}}" class="dropdown-item">Store Sales Report</a>
                        <a href="{{url('store/staff/report/sales')}}" class="dropdown-item">Store Staff Sales Report</a>
                        <a href="{{url('report/store/category/sales')}}" class="dropdown-item">Store Category Sales Report</a>
                        <a href="{{url('report/store/category/staff/sales')}}" class="dropdown-item">Store Category Staff Sales Report</a>
                        <a href="{{url('price/report/sales')}}" class="dropdown-item">Price Slot Sales Report</a>
                        <a href="{{url('time-slot/report/sales')}}" class="dropdown-item">Time Slot Sales Report</a>
                         
                    </div>
                </div>     
            
                <div class="btn-group">
                    <button type="button" class="btn btn-dialog dropdown-toggle" data-toggle="dropdown">Inventory Reports</button>
                    <div class="dropdown-menu">
                        <a href="{{url('store/report/inventory/status')}}" class="dropdown-item">Stores Inventory Report</a>
                        <a href="{{url('warehouse/report/inventory/status')}}" class="dropdown-item">Warehouse Inventory Status</a>
                        <a href="{{url('warehouse/report/inventory/daily/update')}}" class="dropdown-item">Inventory In/Out Report</a>
                        <a href="{{url('warehouse/report/inventory/balance')}}" class="dropdown-item">Inventory Balance Report</a>
                        <a href="{{url('report/warehouse/size/inventory/balance')}}" class="dropdown-item">Size Wise Inventory Balance Report</a>
                        <a href="{{url('report/warehouse/product/inventory/balance')}}" class="dropdown-item">Product Wise Inventory Balance Report</a>
                        <a href="{{url('warehouse/demand/inventory-push/list')}}" class="dropdown-item">Stock Out Details</a>       
                        <a href="{{url('purchase-order/purchased/products')}}" class="dropdown-item">Purchase Details</a>  
                        <a href="{{url('store/stock/details')}}" class="dropdown-item">Store Stock Details</a> 
                        <a href="{{url('purchase-order/stock/details')}}" class="dropdown-item">Stock Details</a> 
                        <a href="{{url('product-sku/details')}}" class="dropdown-item">SKU Details</a>
                        <a href="{{url('report/closing-stock/detail?s_id=1')}}" class="dropdown-item">Closing Stock Report</a> 
                        <a href="{{url('vendor/sku/inventory/report')}}" class="dropdown-item">Vendor SKU Inventory Report</a> 
                        <a href="{{url('store/sku/inventory/report')}}" class="dropdown-item">Store SKU Inventory Report</a>
                        <a href="{{url('grn/sku/report')}}" class="dropdown-item">GRN SKU Report</a>
			<a href="{{url('purchase-order/product/grn/list')}}" class="dropdown-item">GRN Report</a>
                        <a href="{{url('report/shelf/life')}}" class="dropdown-item">Shelf Life Report</a>
                        <a href="{{url('report/gst/b2b')}}" class="dropdown-item">B2B GST Report</a>
                        <a href="{{url('report/gst/b2c')}}" class="dropdown-item">B2C GST Report</a>
                        <a href="{{url('report/gst/hsn')}}" class="dropdown-item">HSN GST Report</a>
                        <a href="{{url('inventory/report/raw')}}" class="dropdown-item">Inventory Raw Report</a>
                    </div>
                </div>    
            @endif
            
            @if(in_array(Auth::user()->user_type,array(1)))
                <div class="btn-group">
                    <button type="button" class="btn btn-dialog dropdown-toggle" data-toggle="dropdown">Settings</button>
                    <div class="dropdown-menu">
                        <a href="{{url('setting/list')}}" class="dropdown-item">Settings List</a>
                        <a href="{{url('permission/list')}}" class="dropdown-item">Permissions</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
<?php /* ?> @endif <?php */ ?>

@yield('content')
<footer class="container-fluid"><div id="page_desc_div"></div></footer>
<?php $currency = CommonHelper::getCurrency(); ?>
<script type="text/javascript" >var currency = "{{$currency}}";</script>
<script src="{{ asset('js/jquery-3.4.1.slim.min.js') }}" ></script>
<script	src="{{ asset('js/jquery-2.2.4.min.js') }}" ></script>
<script src="{{ asset('js/popper.min.js') }}" ></script>
<script src="{{ asset('js/bootstrap.min.js') }}" ></script> 
<script src="{{ asset('js/common.js?v=1.65') }}" ></script> 
@yield('scripts')
@if(isset($page_name) && !empty($page_name)) <script type="text/javascript" >$(document).ready(function(){ displayPageDescription("{{$page_name}}"); });</script> @endif

<div class="modal fade content-dialog" id="image_common_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
    <div class="modal-dialog modal-dialog-centered" role="document" >
        <div class="modal-content">
            <div class="modal-header" >
                <h5 class="modal-title" id="image_common_dialog_title" >Image</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
            </div>
            <div class="modal-body" id="image_common_dialog_content"></div>
            <div class="modal-footer center-footer">
                <button type="button" id="image_common_dialog_close" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>