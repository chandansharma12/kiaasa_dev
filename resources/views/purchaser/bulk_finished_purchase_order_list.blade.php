@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Stitching Purchase Orders')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Stitching Purchase Orders'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                
                <div class="row justify-content-end">
                    <div class="col-md-2">
                        <input type="text" name="po_no" id="po_no" class="form-control" value="{{request('po_no')}}" placeholder="PO Order No">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="invoice_no" id="invoice_no" class="form-control" value="{{request('invoice_no')}}" placeholder="PO Invoice No">
                    </div>
                    <!--
                    <div class="col-md-2">
                        <select name="po_cat_id" id="po_cat_id" class="form-control">
                            <option value="">-- PO Category --</option>
                            @for($i=0;$i<count($po_category_list);$i++)
                                <?php if($po_category_list[$i]['id'] == request('po_cat_id')) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$po_category_list[$i]['id']}}">{{$po_category_list[$i]['name']}}</option>
                            @endfor   
                        </select>
                    </div>-->
                    <div class="col-md-2">
                        <select name="v_id" id="v_id" class="form-control">
                            <option value="">-- Vendor --</option>
                            @for($i=0;$i<count($vendors_list);$i++)
                                <?php if($vendors_list[$i]['id'] == request('v_id')) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$vendors_list[$i]['id']}}">{{$vendors_list[$i]['name']}}</option>
                            @endfor   
                        </select>
                    </div>
                    
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    @if($user->user_type == 3)
                        <div class="col-md-2" ><a  class="btn btn-dialog" href="{{url('purchase-order/bulk/finished/create')}}">Create Order</a></div>
                    @endif
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="productsContainer">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th>Order ID</th><th>Order No</th><th>Vendor</th><th>Category</th><th>Delivery Date</th><th>Invoice Count</th><th>Created By</th><th>Created On</th><th>Details</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($purchase_orders);$i++)
                                <tr>
                                    <td>{{$purchase_orders[$i]->id}}</td>
                                    <td>{{$purchase_orders[$i]->order_no}}</td>
                                    <td>{{$purchase_orders[$i]->vendor_name}}</td>
                                    <td>{{$purchase_orders[$i]->po_category_name}}</td>
                                    <td>@if(!empty($purchase_orders[$i]->delivery_date)) {{date('d M Y',strtotime($purchase_orders[$i]->delivery_date))}} @endif</td>
                                    <td>{{$purchase_orders[$i]->invoice_count}}</td>
                                    <td>{{$purchase_orders[$i]->user_name}}</td>
                                    <td>{{date('d M Y, H:i',strtotime($purchase_orders[$i]->created_at))}}</td>
                                    <td>
                                        <a href="{{url('purchase-order/bulk/finished/detail/'.$purchase_orders[$i]->id)}}"><i title="Order Details" class="fas fa-eye"></i></a> &nbsp; 
                                        
                                        @if($user->user_type == 6 || $user->user_type == 3)
                                            <a href="{{url('purchase-order/bulk/finished/invoice/list/'.$purchase_orders[$i]->id)}}"><i title="PO Invoices" class="fas fa-eye"></i></a> &nbsp;   
                                        @endif        
                                        
                                        @if($user->user_type == 3)
                                            <!--<a href="{{url('purchase-order/bulk/finished/edit/'.$purchase_orders[$i]->id)}}"><i title="Edit Order" class="fas fa-edit"></i> </a> -->
                                        @endif    
                                    </td>
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
