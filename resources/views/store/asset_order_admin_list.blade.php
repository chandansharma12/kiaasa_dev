@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard'),array('name'=>'Store Assets Orders List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Assets Orders List'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div id="orderListOverlay" class="table-list-overlay"><div id="order-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="ordersList">

                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead>
                                <tr class="header-tr">
                                    <th><?php echo CommonHelper::getSortLink('ID','id','store/asset/order/admin-list',true,'DESC'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Total Amount','total_amount','store/asset/order/admin-list'); ?> </th>
                                    <th><?php echo CommonHelper::getSortLink('Total Bill Amount','total_bill_amount','store/asset/order/admin-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Store','store','store/asset/order/admin-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Order Status','order_status','store/asset/order/admin-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created By','created_by','store/asset/order/admin-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Reviewer','reviewer','store/asset/order/admin-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Order Type','order_type','store/asset/order/admin-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created On','created_on','store/asset/order/admin-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Status','status','store/asset/order/admin-list'); ?></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($orders_list);$i++)
                                    <tr>
                                        <td>{{$orders_list[$i]->id}}</td>
                                        <td>{{$currency}} {{$orders_list[$i]->total_amount}}</td>
                                        <td>@if(!empty($orders_list[$i]->total_bill_amount)) {{$currency}} {{$orders_list[$i]->total_bill_amount}} @endif</td>
                                        <td>{{$orders_list[$i]->store_name}}</td>
                                        <td>{{str_replace('_',' ',$orders_list[$i]->order_status)}}</td>
                                        <td>{{$orders_list[$i]->order_user_name}}</td>
                                        <td>{{$orders_list[$i]->approver_name}}</td>
                                        <td>{{$orders_list[$i]->order_type}}</td>
                                        <td>{{date('d M Y',strtotime($orders_list[$i]->created_at))}}</td>
                                        <td>@if($orders_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                        <td>
                                            <a href="{{url('store/asset/order/detail/'.$orders_list[$i]->id)}}" ><i title="Order Details" class="fas fa-eye"></i></a> &nbsp;
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $orders_list->withQueryString()->links() }} <p>Displaying {{$orders_list->count()}} of {{ $orders_list->total() }} orders.</p>

                    </div>
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
