@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Assets')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Assets'); ?>
    
    <?php $permission_create_order =  CommonHelper::hasRoutePermission('storeassetcreateorder',$user->user_type); ?>
    <?php $permission_edit_order =  CommonHelper::hasRoutePermission('storeassetediteorder',$user->user_type); ?>
    <?php $permission_order_detail =  CommonHelper::hasRoutePermission('storeassetdetailorder',$user->user_type); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    @if($permission_edit_order)
                        <div class="col-md-2" >
                            <select name="order_action" id="order_action" class="form-control">
                                <option value="">-- Select Action --</option>
                                <option value="waiting">Request for Approval</option>
                                <option value="enable">Enable</option>
                                <option value="disable">Disable</option>
                                <option value="delete">Delete</option>
                            </select>
                        </div>
                        <div class="col-md-1" ><input type="button" name="editOrder" id="editOrder" value="Update" class="btn btn-dialog" onclick="updateAssetOrderStatus();"></div>
                    @endif
                    
                    @if($permission_create_order)
                        <div class="col-md-2" ><a href="{{url('store/asset/order/create')}}" class="btn btn-dialog ">Create Order</a></div>
                    @endif    
                    
                    @if($user->user_type == 9)
                        <div class="col-md-2">
                            @if(!empty($store_initial_order))
                                <input type="button" name="editInitialOrder" id="editInitialOrder" value="Previous Assets" class="btn btn-dialog" onclick="window.location.href='{{url('store/asset/order/edit/'.$store_initial_order->id)}}'">
                            @else
                                <input type="button" name="createInitialOrderBtn" id="createInitialOrderBtn" value="Previous Assets" class="btn btn-dialog" onclick="createInitialOrder();">
                            @endif
                        </div>
                    @endif
                    
                </div>
            </form>
            <form method="post" name="initialOrderForm" id="initialOrderForm"><input type="hidden" name="create_initial_order" id="create_initial_order" value="1">@csrf</form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div id="orderListOverlay" class="table-list-overlay"><div id="order-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="ordersList">

                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                    <th><input type="checkbox" name="chk_order_id_all" id="chk_order_id_all" value="1" class="order_id-chk" onclick="checkAllCheckboxes(this,'order_id');" > 
                                    <?php echo CommonHelper::getSortLink('ID','id','store/asset/order/list',true,'DESC'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Total Amount','total_amount','store/asset/order/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Total Bill Amount','total_bill_amount','store/asset/order/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Store','store','store/asset/order/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Order Status','order_status','store/asset/order/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created By','created_by','store/asset/order/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Reviewer','reviewer','store/asset/order/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Order Type','order_type','store/asset/order/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created On','created_on','store/asset/order/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Status','status','store/asset/order/list'); ?></th>
                                    <th>Action</th>
                                </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($orders_list);$i++)
                                    <tr>
                                        <td><input type="checkbox" name="chk_order_id_{{$orders_list[$i]->id}}" id="chk_order_id_{{$orders_list[$i]->id}}" value="{{$orders_list[$i]->id}}" class="order_id-chk"> {{$orders_list[$i]->id}}</td>
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
                                            @if($permission_order_detail)
                                                <a href="{{url('store/asset/order/detail/'.$orders_list[$i]->id)}}" ><i title="Order Details" class="fas fa-eye"></i></a> &nbsp;
                                            @endif
                                            @if($permission_edit_order)
                                                <a href="{{url('store/asset/order/edit/'.$orders_list[$i]->id)}}" ><i title="Edit Order" class="far fa-edit"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $orders_list->links() }} <p>Displaying {{$orders_list->count()}} of {{ $orders_list->total() }} assets orders.</p>

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
