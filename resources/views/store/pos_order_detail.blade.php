@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Orders List','link'=>'pos/order/list'),array('name'=>'Order Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Order Detail'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <form id="pos_order_detail_form" name="pos_order_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Order No</label>						
                        {{$pos_order_data->order_no}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Total Price</label>						
                        {{$currency}} {{round($pos_order_data->total_price,2)}}    
                    </div>
                     <div class="form-group col-md-2">
                        <label for="Product">Total Items</label>						
                        {{$pos_order_data->total_items}}    
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="Product">Store</label>						
                        {{$pos_order_data->store_name}} ({{$pos_order_data->store_id_code}})   
                    </div>
                    @if(in_array($pos_order_data->order_status,[1,3])  )
                        <div class="form-group col-md-2">
                            <a href="{{url('pos/order/invoice/'.$pos_order_data->id)}}" class="btn  btn-pdf">Print Invoice</a>
                        </div>
                        <div class="form-group col-md-2">
                            <a href="{{url('pos/order/invoice/'.$pos_order_data->id)}}?action=get_pdf" class="btn  btn-pdf">Invoice PDF</a>
                        </div>
                    @endif
                
                    <div class="form-group col-md-2">
                        <label for="Product">Customer Name</label>						
                        @if(strtolower($pos_order_data->salutation) != 'other') {{$pos_order_data->salutation}} @endif {{$pos_order_data->customer_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Customer Phone</label>						
                        {{$pos_order_data->customer_phone}}    
                    </div>
                    @if(!empty($pos_order_data->customer_gst_no))
                        <div class="form-group col-md-2">
                            <label for="Category">Customer GST No</label>						
                            {{$pos_order_data->customer_gst_no}}    
                        </div> 
                    @endif
                    @if(!empty($pos_order_data->coupon_no))
                        <div class="form-group col-md-2">
                            <label for="Category">Coupon No</label>						
                            {{$pos_order_data->coupon_no}}    
                        </div> 
                        <div class="form-group col-md-2">
                            <label for="Category">Coupon Discount</label>						
                            {{$pos_order_data->coupon_discount}} %    
                        </div> 
                    @endif
                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$pos_order_data->order_type=='customer'?$pos_order_data->store_user_name:'Auditor'}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($pos_order_data->created_at))}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Color">Order Status </label>						
                        {{CommonHelper::getPosOrderStatusText($pos_order_data->order_status)}}    
                    </div>
                    
                    @if($pos_order_data->order_status == 2)
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Order </label>						
                            <button type="button" id="cancel_order_btn" name="cancel_order_btn" class="btn btn-dialog" value="Submit" onclick="cancelPosOrder();">Cancel Order</button>&nbsp;
                        </div>
                    @endif
                    
                    @if($pos_order_data->order_status == 3)
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Comments </label>						
                            {{$pos_order_data->cancel_comments}}    
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Date </label>						
                            {{date('d M Y H:i',strtotime($pos_order_data->cancel_date))}}    
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Cancelled By </label>						
                            {{$pos_order_data->cancel_user_name}}    
                        </div>
                    @endif
                    
                    <div class="form-group col-md-2">
                        <label for="Product">FOC</label>						
                        {{$pos_order_data->foc == 1?'Yes':'No'}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Bags</label>						
                        {{$pos_order_data->bags_count}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Order Source</label>						
                        @if($pos_order_data->order_source == 'web') POS @endif    
                        @if($pos_order_data->order_source == 'website_api') Website @endif    
                        @if($pos_order_data->order_source == 'api') App @endif    
                    </div>
                </div>
                <input type="hidden" name="order_id_hdn" id="order_id_hdn" value="{{$pos_order_data->id}} ">
            </form> 
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div id="orderListOverlay" class="table-list-overlay"><div id="order-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="ordersList">
                    <h5>Products List</h5>
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" style="font-size: 13px;">
                            <thead><tr class="header-tr">
                                    <th>Product Name</th><th>Barcode</th><th>SKU</th><th>Category</th><!--<th>Sub Category</th>--><th>Type</th><th>Staff</th><th>Scheme</th><th>Price</th><th>Discount</th><th>Amount</th><th>GST</th><th>NET Price</th></tr></thead>
                            <tbody>
                                <?php $total_sale_price = $total_net_price = $total_discount = $total_discounted_amount = $total_gst_amount = 0; ?>
                                @for($i=0;$i<count($pos_order_products);$i++)
                                    <tr>
                                        <td>{{$pos_order_products[$i]->product_name}} {{$pos_order_products[$i]->size_name}} {{$pos_order_products[$i]->color_name}}</td>
                                        <td>{{$pos_order_products[$i]->peice_barcode}}</td>
                                        <td>{{(!empty($pos_order_products[$i]->vendor_sku))?$pos_order_products[$i]->vendor_sku:$pos_order_products[$i]->product_sku}}</td>
                                        <td>{{$pos_order_products[$i]->category_name}}</td>
                                        <!--<td>{{$pos_order_products[$i]->subcategory_name}}</td>-->
                                        <td>{{$pos_order_products[$i]->arnon_prod_inv == 0?'Kiaasa':'Arnon'}}</td>
                                        <td>{{$pos_order_products[$i]->staff_name}}</td>
                                        <td>{{!empty($pos_order_products[$i]->bill_product_group_name)?'Buy '.str_replace(',',' Get ',$pos_order_products[$i]->bill_product_group_name):''}}</td>
                                        <td>{{$currency}} {{$pos_order_products[$i]->sale_price}}</td>
                                        <td>{{round($pos_order_products[$i]->discount_amount,2)}} ({{abs($pos_order_products[$i]->discount_percent)}}%)</td>
                                        <?php $discounted_amount = round($pos_order_products[$i]->sale_price-$pos_order_products[$i]->discount_amount,2); ?>
                                        <td>{{$discounted_amount = ($pos_order_products[$i]->gst_inclusive == 1)?round($discounted_amount-$pos_order_products[$i]->gst_amount,2):$discounted_amount }}</td>
                                        <td>{{round($pos_order_products[$i]->gst_amount,2)}} ({{abs($pos_order_products[$i]->gst_percent)}}%) ({{$gst_type = ($pos_order_products[$i]->gst_inclusive == 1)?'Inc.':'Exc.'}})</td>
                                        <td>{{$currency}} {{round($pos_order_products[$i]->net_price,2)}}</td>
                                        
                                        <?php $total_sale_price+=$pos_order_products[$i]->sale_price;
                                        $total_net_price+=$pos_order_products[$i]->net_price;
                                        $total_discounted_amount+=$discounted_amount;
                                        $total_gst_amount+=$pos_order_products[$i]->gst_amount;
                                        $total_discount+=$pos_order_products[$i]->discount_amount;?>
                                    </tr>
                                @endfor
                                <tr class="total-tr"><th colspan="7">Total</th><th>{{$currency}} {{$total_sale_price}}</th><th>{{$currency}} {{round($total_discount,2)}}</th>
                                <th>{{$currency}} {{round($total_discounted_amount,2)}}</th><th>{{$currency}} {{round($total_gst_amount,2)}}</th><th>{{$currency}} {{round($total_net_price,2)}}</th></tr>
                            </tbody>
                        </table>
                        
                        <h6>Payment Types</h6>
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>Payment Type</th><th>Payment Amount</th><th>Received Amount</th><th>Reference No</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($payment_types);$i++)
                                    <tr>
                                        <td>{{$payment_types[$i]['payment_method']}}</td>
                                        <td>{{$payment_types[$i]['payment_amount']}}</td>
                                        <td>{{$payment_types[$i]['payment_received']}}</td>
                                        <td>{{$payment_types[$i]['reference_number']}}</td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>    
                        
                        @if($pos_order_data->voucher_amount > 0)
                            <h6>Voucher Details</h6>
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead><tr class="header-tr">
                                    <th>Voucher Payment</th><th>Comments</th><th>Approver</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td>{{$pos_order_data->voucher_amount}}</td>
                                        <td>{{$pos_order_data->voucher_comment}}</td>
                                        <td>{{$pos_order_data->voucher_approver_id}}</td>
                                    </tr>
                                </tbody>
                            </table>    
                        @endif
                        
                        @if($pos_order_data->order_source == 'website_api')
                            <h6>Shipping Address</h6>
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead><tr class="header-tr">
                                    <th>Customer Name</th><th>Address</th><th>Locality</th><th>City</th><th>Postal Code</th><th>State</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td>{{$pos_order_data->full_name}}</td>
                                        <td>{{$pos_order_data->address}}</td>
                                        <td>{{$pos_order_data->locality}}</td>
                                        <td>{{$pos_order_data->city_name}}</td>
                                        <td>{{$pos_order_data->postal_code}}</td>
                                        <td>{{$pos_order_data->state_name}}</td>
                                    </tr>
                                </tbody>
                            </table>    
                        @endif
                        
                        @if($pos_order_data->order_source == 'website_api' && $pos_order_data->bill_data_same == 0)
                            <h6>Billing Address</h6>
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead><tr class="header-tr">
                                    <th>Customer Name</th><th>Address</th><th>Locality</th><th>City</th><th>Postal Code</th><th>State</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td>{{$pos_order_data->bill_cust_name}}</td>
                                        <td>{{$pos_order_data->bill_address}}</td>
                                        <td>{{$pos_order_data->bill_locality}}</td>
                                        <td>{{$pos_order_data->bill_city_name}}</td>
                                        <td>{{$pos_order_data->bill_postal_code}}</td>
                                        <td>{{$pos_order_data->bill_state_name}}</td>
                                    </tr>
                                </tbody>
                            </table>    
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="cancelPosOrderDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Cancel Order</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="cancelPosOrderSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="cancelPosOrderErrorMessage"></div>

                <form class="" name="cancelPosOrderForm" id="cancelPosOrderForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-10">
                                <label>Comments</label>
                                <input id="comments_cancel_order" type="text" class="form-control" name="comments_cancel_order" value=""  maxlength="250">
                                <div class="invalid-feedback" id="error_validation_comments_cancel_order"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="cancelPosOrderSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="cancelPosOrderCancel" name="cancelPosOrderCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="cancelPosOrderSubmit" name="cancelPosOrderSubmit" class="btn btn-dialog" onclick="submitCancelPosOrder();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos.js?v=3.20') }}" ></script>
@endsection
