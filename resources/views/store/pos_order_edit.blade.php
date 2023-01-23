@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Orders List','link'=>'pos/order/list'),array('name'=>'Edit Order')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Order'); ?>

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
                        {{$pos_order_data->store_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$pos_order_data->order_type=='customer'?$pos_order_data->store_user_name:'Auditor'}}       
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($pos_order_data->created_at))}}    
                        <input type="hidden" name="order_id_hdn" id="order_id_hdn" value="{{$pos_order_data->id}} ">
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Color">Order Status </label>						
                        {{CommonHelper::getPosOrderStatusText($pos_order_data->order_status)}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Customer Name</label>						
                        @if(strtolower($pos_order_data->salutation) != 'other') {{$pos_order_data->salutation}} @endif {{$pos_order_data->customer_name}}    
                        &nbsp;<a href="javascript:;" onclick="editPosOrderCustomerData({{$pos_order_data->id}});"><i title="Edit Customer Name" class="fas fa-edit"></i></a> &nbsp; 
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Customer Phone</label>						
                        {{$pos_order_data->customer_phone}}    
                    </div>
                    
                    @if($pos_order_data->order_status == 1 || $pos_order_data->order_status == 2)
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Order </label>						
                            <button type="button" id="cancel_order_btn" name="cancel_order_btn" class="btn btn-dialog" value="Submit" onclick="cancelPosOrder();">Cancel Order</button>&nbsp;
                        </div>
                    @endif
                </div>    
                
            </form> 
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            @if(in_array($pos_order_data->order_status,[1,2]) && $pos_order_data->foc == 0)
                <div id="orderContainer" class="table-container">
                    <div id="ordersList">

                        <a href="javascript:;" onclick="addPosOrderProduct();" class="btn btn-dialog" style="float:right;margin-right:10px;margin-bottom: 5px; " title="Add Product"> <i title="Add Product" class="fas fa-plus fas-icon"></i> Add</a>
                        <h5>Products List</h5>
                        <div class="table-responsive table-filter">
                            <table class="table table-striped admin-table" cellspacing="0" style="font-size: 13px;">
                                <thead><tr class="header-tr">
                                        <th>Product Name</th><th>Barcode</th><th>SKU</th><th>Category</th><th>Staff</th><th>Price</th><th>Discount</th><th>Amount</th><th>GST</th><th>NET Price</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php $total_sale_price = $total_net_price = $total_discount = $total_discounted_amount = $total_gst_amount = 0; ?>
                                    @for($i=0;$i<count($pos_order_products);$i++)
                                        <tr>
                                            <td>{{$pos_order_products[$i]->product_name}} {{$pos_order_products[$i]->size_name}} {{$pos_order_products[$i]->color_name}}</td>
                                            <td>{{$pos_order_products[$i]->peice_barcode}}</td>
                                            <td>{{$pos_order_products[$i]->product_sku}}</td>
                                            <td>{{$pos_order_products[$i]->category_name}}</td>
                                            <td>{{$pos_order_products[$i]->staff_name}}</td>
                                            <td>{{$currency}} {{$pos_order_products[$i]->sale_price}}</td>
                                            <td>{{round($pos_order_products[$i]->discount_amount,2)}} ({{abs($pos_order_products[$i]->discount_percent)}}%)</td>
                                            <?php $discounted_amount = round($pos_order_products[$i]->sale_price-$pos_order_products[$i]->discount_amount,2); ?>
                                            <td>{{$discounted_amount = ($pos_order_products[$i]->gst_inclusive == 1)?$discounted_amount-$pos_order_products[$i]->gst_amount:$discounted_amount }}</td>
                                            <td>{{round($pos_order_products[$i]->gst_amount,2)}} ({{abs($pos_order_products[$i]->gst_percent)}}%) ({{$gst_type = ($pos_order_products[$i]->gst_inclusive == 1)?'Inc.':'Exc.'}})</td>
                                            <td>{{$currency}} {{round($pos_order_products[$i]->net_price,2)}}</td>
                                            <td>
                                                <a href="javascript:;" onclick="editPosOrderProduct({{$pos_order_data->id}},{{$pos_order_products[$i]->id}});"><i title="Edit Order Product" class="fas fa-edit"></i></a> &nbsp; 
                                                <a href="javascript:;" onclick="editPosOrderProductStaff({{$pos_order_data->id}},{{$pos_order_products[$i]->id}});"><i title="Edit Order Product Staff" class="fas fa-edit"></i></a> &nbsp; 
                                                <a href="javascript:;" onclick="deletePosOrderProduct({{$pos_order_products[$i]->id}});"><i title="Delete Product" class="fas fa-trash"></i></a>
                                            </td>

                                            <?php $total_sale_price+=$pos_order_products[$i]->sale_price;
                                            $total_net_price+=$pos_order_products[$i]->net_price;
                                            $total_discounted_amount+=$discounted_amount;
                                            $total_gst_amount+=$pos_order_products[$i]->gst_amount;
                                            $total_discount+=$pos_order_products[$i]->discount_amount;?>
                                        </tr>
                                    @endfor
                                    <tr class="total-tr"><th colspan="5">Total</th><th>{{$currency}} {{$total_sale_price}}</th><th>{{$currency}} {{round($total_discount,2)}}</th>
                                        <th>{{$currency}} {{round($total_discounted_amount,2)}}</th><th>{{$currency}} {{round($total_gst_amount,2)}}</th><th>{{$currency}} {{round($total_net_price,2)}}</th><th></th></tr>
                                </tbody>
                            </table>

                            <h6>Payment Types</h6>
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead><tr class="header-tr">
                                        <th>Payment Type</th><th>Payment Amount</th><th>Received Amount</th><th>Reference No</th><th>Action</th></tr></thead>
                                <tbody>
                                    @for($i=0;$i<count($payment_types);$i++)
                                        <tr>
                                            <td>{{$payment_types[$i]['payment_method']}}</td>
                                            <td>{{$payment_types[$i]['payment_amount']}}</td>
                                            <td>{{$payment_types[$i]['payment_received']}}</td>
                                            <td>{{$payment_types[$i]['reference_number']}}</td>
                                            <td><a href="javascript:;" onclick="editPosOrderPaymentMethod({{$pos_order_data->id}},{{$payment_types[$i]['id']}});"><i title="Edit Payment Method" class="fas fa-edit"></i></a> &nbsp;</td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>    

                            @if($pos_order_data->voucher_amount > 0)
                                <h6>Voucher Details</h6>
                                <table class="table table-striped admin-table" cellspacing="0" >
                                    <thead><tr class="header-tr">
                                        <th>voucher Payment</th><th>Comments</th><th>Approver</th></tr></thead>
                                    <tbody>
                                    <tr>
                                        <td>{{$pos_order_data->voucher_amount}}</td>
                                        <td>{{$pos_order_data->voucher_comment}}</td>
                                        <td>{{$pos_order_data->voucher_approver_id}}</td>
                                    </tr>
                                    </tbody>
                                </table>    
                            @endif
                        </div>
                    </div>
                </div>
            
            @endif
        </div>
    </section>

    <div class="modal fade data-modal" id="edit_payment_method_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Payment Method</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="editOrderPaymentMethodSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editOrderPaymentMethodErrorMessage"></div>

                <form class="" name="editOrderPaymentMethodFrm" id="editOrderPaymentMethodFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group col-md-3" >
                                    <label>Payment Method</label>
                                    <input type="text" name="payment_method_cash" id="payment_method_cash" class="form-control" readonly="true" value="Cash">
                                    <div class="invalid-feedback" id="error_validation_payment_method_edit"></div>
                                </div>
                                
                                <div class="form-group col-md-3" >
                                    <label>Payment Amount</label>
                                    <input type="text" name="payment_amount_cash_edit" id="payment_amount_cash_edit" class="form-control" >
                                    <div class="invalid-feedback" id="error_validation_payment_amount_cash_edit"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Payment Received</label>
                                    <input type="text" name="payment_received_cash_edit" id="payment_received_cash_edit" class="form-control">
                                    <div class="invalid-feedback" id="error_validation_payment_received_cash_edit"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-3" >
                                    <label>Payment Method</label>
                                    <input type="text" name="payment_method_card_edit" id="payment_method_card_edit" class="form-control" readonly="true" value="Card">
                                    <div class="invalid-feedback" id="error_validation_payment_method_edit"></div>
                                </div>
                                
                                <div class="form-group col-md-3" >
                                    <label>Payment Amount</label>
                                    <input type="text" name="payment_amount_card_edit" id="payment_amount_card_edit" class="form-control" >
                                    <div class="invalid-feedback" id="error_validation_payment_amount_cash_edit"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Payment Received</label>
                                    <input type="text" name="payment_received_card_edit" id="payment_received_card_edit" class="form-control">
                                    <div class="invalid-feedback" id="error_validation_payment_received_cash_edit"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-3" >
                                    <label>Payment Method</label>
                                    <input type="text" name="payment_method_ewallet_edit" id="payment_method_ewallet_edit" class="form-control" readonly="true" value="E-Wallet">
                                    <div class="invalid-feedback" id="error_validation_payment_method_ewallet_edit"></div>
                                </div>
                                
                                <div class="form-group col-md-3" >
                                    <label>Payment Amount</label>
                                    <input type="text" name="payment_amount_ewallet_edit" id="payment_amount_ewallet_edit" class="form-control" >
                                    <div class="invalid-feedback" id="error_validation_payment_amount_ewallet_edit"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Payment Received</label>
                                    <input type="text" name="payment_received_ewallet_edit" id="payment_received_ewallet_edit" class="form-control">
                                    <div class="invalid-feedback" id="error_validation_payment_received_ewallet_edit"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Reference No</label>
                                    <input type="text" name="reference_no_ewallet_edit" id="reference_no_ewallet_edit" class="form-control">
                                    <div class="invalid-feedback" id="error_validation_reference_no_ewallet_edit"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="payment_method_id" id="payment_method_id" value="">
                        <div id="edit_payment_method_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="edit_payment_method_cancel" name="edit_payment_method_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="edit_payment_method_submit" name="edit_payment_method_submit" class="btn btn-dialog" onclick="submitEditPosOrderPaymentMethod({{$pos_order_data->id}})">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal " id="edit_order_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit POS Order Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editOrderProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editOrderProductErrorMessage"></div>

                <form class="" name="editOrderProductForm" id="editOrderProductForm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group col-md-3" >
                                    <label>Product</label>
                                    <input type="text" name="product_name_edit" id="product_name_edit" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>MRP</label>
                                    <input type="text" name="order_product_mrp_edit" id="order_product_mrp_edit" class="form-control" value="" readonly="true">
                                </div>
                            </div>    
                            <div class="form-row">    
                                <div class="form-group col-md-2" >
                                    <label>Discount %</label>
                                    <input type="text" name="order_product_discount_percent_edit" id="order_product_discount_percent_edit" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Discount Amt</label>
                                    <input type="text" name="order_product_discount_amount_edit" id="order_product_discount_amount_edit" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Discounted Amt</label>
                                    <input type="text" name="order_product_discounted_amt_edit" id="order_product_discounted_amt_edit" class="form-control" value="" readonly="true"> 
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>GST %</label>
                                    <input type="text" name="order_product_gst_percent_edit" id="order_product_gst_percent_edit" class="form-control" value="" readonly="true">
                                </div>
                                 <div class="form-group col-md-2" >
                                    <label>GST Amt</label>
                                    <input type="text" name="order_product_gst_amount_edit" id="order_product_gst_amount_edit" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Net Price</label>
                                    <input type="text" name="order_product_net_price_edit" id="order_product_net_price_edit" class="form-control" value="" readonly="true">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3" >
                                    <label>Edit Discount</label>
                                    <select name="order_discount_modify_type_edit" id="order_discount_modify_type_edit" class="form-control" onchange="toggleEditOrderDiscountType(this.value);">
                                        <option value="">-- Discount Type --</option>
                                        <option value="percent">Percent %</option>
                                        <option value="amount">Amount</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_discount_edit_type"></div>
                                </div>
                                <div class="form-group col-md-2" id="discount_type_edit_percent">
                                    <label>Discount %</label>
                                    <input type="text" name="order_discount_modify_percent_edit" id="order_discount_modify_percent_edit" class="form-control" value="" >
                                    <div class="invalid-feedback" id="error_validation_discount_percent"></div>
                                </div>
                                <div class="form-group col-md-2 elem-hidden" id="discount_type_edit_amount">
                                    <label>Discount Amount</label>
                                    <input type="text" name="order_discount_modify_amount_edit" id="order_discount_modify_amount_edit" class="form-control" value="" >
                                    <div class="invalid-feedback" id="error_validation_discount_amount"></div>
                                </div>
                                <div class="form-group col-md-3" >
                                    <label>Edit GST Type</label>
                                    <select name="order_gst_type_modify_edit" id="order_gst_type_modify_edit" class="form-control">
                                        <option value="">-- GST Type --</option>
                                        <option value="exclusive">Exclusive</option>
                                        <option value="inclusive">Inclusive</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_gst_type"></div>
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>&nbsp;</label>
                                    <button style="margin-top: 10px;" type="button" id ="edit_order_product_calculate_btn" name="edit_order_product_calculate_btn" class="btn btn-dialog" onclick="calculateEditOrderProduct();">Calculate</button>
                                </div>
                            </div>
                            
                            <div class="form-row">    
                                <div class="form-group col-md-2" >
                                    <label>Discount %</label>
                                    <input type="text" name="order_product_discount_percent_calculate" id="order_product_discount_percent_calculate" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Discount Amt</label>
                                    <input type="text" name="order_product_discount_amount_calculate" id="order_product_discount_amount_calculate" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Discounted Amt</label>
                                    <input type="text" name="order_product_discounted_amt_calculate" id="order_product_discounted_amt_calculate" class="form-control" value="" readonly="true"> 
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>GST %</label>
                                    <input type="text" name="order_product_gst_percent_calculate" id="order_product_gst_percent_calculate" class="form-control" value="" readonly="true">
                                </div>
                                 <div class="form-group col-md-2" >
                                    <label>GST Amt</label>
                                    <input type="text" name="order_product_gst_amount_calculate" id="order_product_gst_amount_calculate" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Net Price</label>
                                    <input type="text" name="order_product_net_price_calculate" id="order_product_net_price_calculate" class="form-control" value="" readonly="true">
                                </div>
                            </div>
                             
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="order_product_edit_id_hdn" id="order_product_edit_id_hdn" value="">
                        <button type="button" id="edit_order_product_cancel" name="edit_order_product_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="edit_order_product_submit" name="edit_order_product_submit" class="btn btn-dialog" onclick="submitEditPosOrderProduct();">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade data-modal " id="add_order_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add POS Order Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addOrderProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addOrderProductErrorMessage"></div>

                <form class="" name="addOrderProductForm" id="addOrderProductForm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group col-md-3" >
                                    <label>QR Code</label>
                                    <input type="text" name="product_qr_code_add" id="product_qr_code_add" class="form-control" value="" >
                                    <div class="invalid-feedback" id="error_validation_qr_code"></div>
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>&nbsp;</label>
                                    <button type="button" style="margin-top: 12px;" id ="product_qr_code_submit" name="product_qr_code_submit" class="btn btn-dialog" onclick="editOrderGetProductData();">Submit</button>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2" >
                                    <label>Product</label>
                                    <input type="text" name="product_name_add" id="product_name_add" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>SKU</label>
                                    <input type="text" name="product_sku_add" id="product_sku_add" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Size</label>
                                    <input type="text" name="product_size_add" id="product_size_add" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Color</label>
                                    <input type="text" name="product_color_add" id="product_color_add" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Category</label>
                                    <input type="text" name="product_category_add" id="product_category_add" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>MRP</label>
                                    <input type="text" name="order_product_mrp_add" id="order_product_mrp_add" class="form-control" value="" readonly="true">
                                </div>
                            </div>    
                            <div class="form-row">    
                                <div class="form-group col-md-2" >
                                    <label>Discount %</label>
                                    <input type="text" name="order_product_discount_percent_add" id="order_product_discount_percent_add" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Discount Amt</label>
                                    <input type="text" name="order_product_discount_amount_add" id="order_product_discount_amount_add" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Discounted Amt</label>
                                    <input type="text" name="order_product_discounted_amt_add" id="order_product_discounted_amt_add" class="form-control" value="" readonly="true"> 
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>GST %</label>
                                    <input type="text" name="order_product_gst_percent_add" id="order_product_gst_percent_add" class="form-control" value="" readonly="true">
                                </div>
                                 <div class="form-group col-md-2" >
                                    <label>GST Amt</label>
                                    <input type="text" name="order_product_gst_amount_add" id="order_product_gst_amount_add" class="form-control" value="" readonly="true">
                                </div>
                                <div class="form-group col-md-2" >
                                    <label>Net Price</label>
                                    <input type="text" name="order_product_net_price_add" id="order_product_net_price_add" class="form-control" value="" readonly="true">
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="order_product_add_id_hdn" id="order_product_add_id_hdn" value="">
                        <button type="button" id="add_order_product_cancel" name="add_order_product_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="add_order_product_submit" name="add_order_product_submit" class="btn btn-dialog" onclick="submitAddPosOrderProduct();">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_order_product_staff_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Order Product Staff</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="editOrderProductStaffSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editOrderProductStaffErrorMessage"></div>
                <form class="" name="editOrderProductStaffForm" id="editOrderProductStaffForm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">    
                            <div class="form-group col-md-10" >
                                <label>Staff Name</label>
                                <select name="order_product_staff_edit" id="order_product_staff_edit" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($store_staff);$i++)
                                        <option value="{{$store_staff[$i]['id']}}">{{$store_staff[$i]['name']}}</option>
                                    @endfor    
                                </select>
                            </div>
                        </div>    
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <input type="hidden" name="order_ataff_product_id_hdn" id="order_ataff_product_id_hdn" value="">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" name="edit_order_product_ataff_cancel" id="edit_order_product_ataff_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="edit_order_product_ataff_submit" name="edit_order_product_ataff_submit" onclick="submitEditPosOrderProductStaff();">Update</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="confirm_delete_order_product" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="deleteOrderProductErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="deleteOrderProductSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Product<br/></h6>
                    <span id="name_delete_product"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-demand_item-spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_order_product_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="delete_order_product_btn" name="delete_order_product_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

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

    <div class="modal fade" id="editPosOrderCustomerDataDialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="editPosOrderCustomerDataSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editPosOrderCustomerDataErrorMessage"></div>

                <form class="" name="editPosOrderCustomerDataForm" id="editPosOrderCustomerDataForm" type="POST" >
                    <div class="modal-body">
                        <?php $salutation_arr = array('Mr','Mrs','Ms','Dr','Other'); ?>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Salutation</label>
                                <select name="customer_salutation" id="customer_salutation" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($salutation_arr);$i++)
                                        <?php $sel = (strtolower($pos_order_data->salutation) == strtolower($salutation_arr[$i]))?'selected':''; ?>
                                        <option {{$sel}} value="{{$salutation_arr[$i]}}">{{$salutation_arr[$i]}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_customer_salutation"></div>
                            </div>
                            <div class="form-group col-md-8">
                                <label>Customer Name</label>
                                <input id="customer_name" type="text" class="form-control" name="customer_name" value="{{$pos_order_data->customer_name}}"  maxlength="250">
                                <div class="invalid-feedback" id="error_validation_customer_name"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="cancelPosOrderSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editPosOrderCustomerDataCancel" name="editPosOrderCustomerDataCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="editPosOrderCustomerDataSubmit" name="editPosOrderCustomerDataSubmit" class="btn btn-dialog" onclick="submitEditPosOrderCustomerData();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=2.3') }}" ></script>
<script src="{{ asset('js/pos.js?v=2.3') }}" ></script>
@endsection
