@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard'),array('name'=>'Store Assets Orders Items')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Assets Orders Items'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="store_id" id="store_id" class="form-control">
                            <option value="">-- All Stores --</option>
                            <?php $sel = ''; ?>
                            @for($i=0;$i<count($stores_list);$i++)
                                <?php if(isset($_GET['store_id']) && $stores_list[$i]['id'] == $_GET['store_id']) $sel = 'selected'; else $sel = ''; ?>
                                <option <?php echo $sel ?> value="{{$stores_list[$i]['id']}}">{{$stores_list[$i]['store_name']}}</option>
                            @endfor    
                        </select>
                    </div>    
                    <div class="col-md-2" >    
                        <select name="order_status" id="order_status" class="form-control">
                            <option value="">-- All  Orders --</option>
                            <?php $statuses = array('approved','rejected','waiting','accounts_submitted'); $sel = ''; ?>
                            @for($i=0;$i<count($statuses);$i++)
                                <?php if(isset($_GET['order_status']) && $statuses[$i] == $_GET['order_status']) $sel = 'selected'; else $sel = ''; ?>
                                <option <?php echo $sel ?> value="{{$statuses[$i]}}">{{ucwords(str_replace('_',' ',$statuses[$i]))}}</option>
                            @endfor   
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div id="orderListOverlay" class="table-list-overlay"><div id="order-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="ordersList">

                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th><?php echo CommonHelper::getSortLink('Order ID','id','store/asset/order/items-list',true,'DESC'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Item','item','store/asset/order/items-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Price','price','store/asset/order/items-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Quantity','quantity','store/asset/order/items-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Order Total','total_amount','store/asset/order/items-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Store','store','store/asset/order/items-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Order Status','order_status','store/asset/order/items-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created By','created_by','store/asset/order/items-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created On','created_on','store/asset/order/items-list'); ?></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($asset_orders_items_list);$i++)
                                    <tr>
                                        <td>{{$asset_orders_items_list[$i]->order_id}}</td>
                                        <td>{{$asset_orders_items_list[$i]->item_name}}</td>
                                        <td>{{$currency}} {{$asset_orders_items_list[$i]->item_price}}</td>
                                        <td>{{$asset_orders_items_list[$i]->item_quantity}}</td>
                                        <td>{{$currency}} {{round($asset_orders_items_list[$i]->total_amount,2)}}</td>
                                        <td>{{$asset_orders_items_list[$i]->store_name}}</td>
                                        <td>{{str_replace('_',' ',$asset_orders_items_list[$i]->order_status)}}</td>
                                        <td>{{$asset_orders_items_list[$i]->order_user_name}}</td>
                                        <td>{{date('d M Y',strtotime($asset_orders_items_list[$i]->created_at))}}</td>
                                        <td>
                                            <a href="{{url('store/asset/order/detail/'.$asset_orders_items_list[$i]->order_id)}}" ><i title="Order Details" class="fas fa-eye"></i></a> &nbsp;
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $asset_orders_items_list->withQueryString()->links() }} <p>Displaying {{$asset_orders_items_list->count()}} of {{ $asset_orders_items_list->total() }} orders items.</p>

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
