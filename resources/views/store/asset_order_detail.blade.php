@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php //if($user->user_type == 1) $list_link = 'store/asset/order/admin-list';elseif($user->user_type == 9) $list_link = 'store/asset/order/list';elseif($user->user_type == 10) $list_link = 'store-head/asset/order/list';elseif($user->user_type == 11) $list_link = 'accounts/asset/order/list'; ?>
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Asset Orders List','link'=>'store/asset/order/list'),array('name'=>'Asset Order Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Asset Order Detail'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Order ID</label>						
                        {{$order_data->id}}    
                    </div> 

                    <div class="form-group col-md-2">
                        <label for="Product">Order Status</label>						
                        {{str_replace('_',' ',$order_data->order_status)}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Product">Comments</label>						
                        {{$order_data->comments}}    
                    </div>
                
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{$order_data->created_at}}    
                    </div> 

                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$order_data->user_name}}    
                    </div> 
                </div>
            </form> 
            <hr/>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th> Order Asset</th><th>Price</th><th>Quantity</th><th>Total Price</th><th>Initial Picture</th><th>New Picture</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($order_items_list);$i++)
                                    <tr>
                                        <td>{{$order_items_list[$i]->item_name}}</td>
                                        <td>{{$currency}} {{$order_items_list[$i]->item_price}}</td>
                                        <td>{{$order_items_list[$i]->item_quantity}}</td>
                                        <td>{{$currency}} {{$order_items_list[$i]->item_price*$order_items_list[$i]->item_quantity}}</td>
                                        <td>
                                            @if(!empty($order_items_list[$i]->initial_picture))
                                            <a href="javascript:;" onclick="displayDialogImage('{{asset('images/order_images/'.$order_data->id)}}/{{$order_items_list[$i]->initial_picture}}');">
                                                <img src="{{asset('images/order_images/'.$order_data->id)}}/thumbs/{{$order_items_list[$i]->initial_picture}}" class="order-thumb-image">
                                            </a>    
                                            @endif 
                                        </td>
                                        <td>
                                            @if(!empty($order_items_list[$i]->new_picture))
                                            <a href="javascript:;" onclick="displayDialogImage('{{asset('images/order_images/'.$order_data->id)}}/{{$order_items_list[$i]->new_picture}}');">
                                                <img src="{{asset('images/order_images/'.$order_data->id)}}/thumbs/{{$order_items_list[$i]->new_picture}}" class="order-thumb-image">
                                            </a>    
                                            @endif 
                                        </td>
                                    </tr>
                                @endfor
                                <tr style="border-top:1px solid #ccc;"><td colspan="3"><strong>Total</strong></td><td><strong>{{$currency}} {{$order_data->total_amount}}</strong></td><td colspan="2"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                     <div class="form-row " >
                        @if(in_array(strtolower($order_data->order_status),array('accounts_submitted','accounts_rejected')))
                            @if(!empty($asset_bills))
                                <div class="table-responsive table-filter" style="width:80%;">
                                    <h5>Order Bills</h5>
                                    <table class="table table-striped admin-table" cellspacing="0" >
                                        <thead><tr class="header-tr"><th>Bill Amount</th><th>Bill Picture</th><th>Payment Method</th><th>Bank Name</th><th>Account No</th>
                                                <th>IFSC Code</th><th>Customer Name</th><th>Account Type</th><th>Date Added</th>
                                            @if(strtolower($order_data->order_status) != 'accounts_submitted')    
                                                <th>Action</th>
                                            @endif    
                                        </tr></thead>
                                        @for($i=0;$i<count($asset_bills);$i++)
                                            <tr>
                                                <td>{{$asset_bills[$i]['bill_amount']}}</td>
                                                <td>    
                                                    @if(!empty($asset_bills[$i]['bill_picture']))
                                                        <a href="javascript:;" onclick="displayDialogImage('{{asset('images/asset_order_images/'.$asset_bills[$i]['order_id'])}}/{{$asset_bills[$i]['bill_picture']}}');">
                                                            <img src="{{asset('images/asset_order_images/'.$asset_bills[$i]['order_id'])}}/thumbs/{{$asset_bills[$i]['bill_picture']}}" class="order-thumb-image">
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>{{$asset_bills[$i]['payment_method']}}</td>
                                                <td>{{$asset_bills[$i]['vendor_bank_name']}}</td>
                                                <td>{{$asset_bills[$i]['vendor_bank_acc_no']}}</td>
                                                <td>{{$asset_bills[$i]['vendor_bank_ifsc_code']}}</td>
                                                <td>{{$asset_bills[$i]['vendor_bank_cust_name']}}</td>
                                                <td>{{$asset_bills[$i]['vendor_bank_acc_type']}}</td>
                                                <td>{{date('d M Y',strtotime($asset_bills[$i]['created_at']))}}</td>
                                                @if(strtolower($order_data->order_status) != 'accounts_submitted')
                                                    <td><a href="javascript:;" class="user-list-edit" onclick="editAssetOrderBill({{$asset_bills[$i]['id']}});"><i title="Edit Bill" class="far fa-edit"></i></a></td>
                                                @endif    
                                            </tr>
                                        @endfor    
                                    </table>
                                </div>
                            @endif                        
                        @endif
                    </div>
                    
                    @if(CommonHelper::hasPermission('update_store_order',$user->user_type) && in_array(strtolower($order_data->order_status),array('waiting','rejected'))  )
                        <div class="table-responsive">
                            <div class="table_actions">
                                <div id="updateOrderStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
                                <div id="updateOrderStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
                                Comments: 
                                <textarea class="form-control design-comment" id="order_comments"  name="order_comments"></textarea>
                                <button class="btn_box" id="approveOrderBtn" name="approveOrderBtn" onclick="updateAssetOrder({{$order_data->id}},'approved');">Approve Order</button>
                                <button class="btn_box" id="rejectOrderBtn" name="rejectOrderBtn" onclick="updateAssetOrder({{$order_data->id}},'rejected');">Reject Order</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
