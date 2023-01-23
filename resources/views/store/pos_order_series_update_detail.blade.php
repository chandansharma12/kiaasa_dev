@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Orders Updates List','link'=>'pos/order/series/update/list'),array('name'=>'Orders Updates Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Orders Updates Detail'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form id="pos_order_detail_form" name="pos_order_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Update Date</label>						
                        {{date('d M Y',strtotime($pos_orders_update_data->order_date)) }}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Store</label>						
                        {{$pos_orders_update_data->store_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Orders Count</label>						
                        {{$pos_orders_update_data->orders_count}}        
                    </div>
                </div>    
            </form>    
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                
               <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th>Order ID</th>
                            <th>New Order No</th>
                            <th>Previous Order No</th>
                            <th>Type</th>

                            </tr></thead>
                        <tbody>
                            <?php $order_no_new_list = array(); ?>
                            @for($i=0;$i<count($pos_orders_updates_detail);$i++)
                                <?php if(in_array($pos_orders_updates_detail[$i]->order_no_new,$order_no_new_list)) $css_class = 'background-color:#F7C1BD';else $css_class = ''; ?>
                                <tr style="{{$css_class}}">
                                    <td>{{$pos_orders_updates_detail[$i]->order_id}}</td>
                                    <td>{{$pos_orders_updates_detail[$i]->order_no_new}}</td>
                                    <td>{{$pos_orders_updates_detail[$i]->order_no_prev}}</td>
                                    <td>{{($pos_orders_updates_detail[$i]->update_type==1)?'Add':'Update'}}</td>
                                </tr>
                                <?php $order_no_new_list[] = $pos_orders_updates_detail[$i]->order_no_new; ?>
                            @endfor
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos.js?v=1.1') }}" ></script>

@endsection
