@extends('layouts.default')
@section('content')
<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Point of Sale')); ?>
    <?php $order_str = (!empty($order_data))?' (Order No: '.$order_data->order_no.')':''; ?>
    <?php $foc_str = ($foc == 1)?' (Free of Cost Order)':''; ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Point of Sale'.$order_str.$foc_str); ?>
    <?php $currency = CommonHelper::getCurrency(); ?>

<!-- page content--->
<div class="custom-cart-sections">
    <div class="custom-container">
        <div class="wrap-area">
            <div class="left-section">
                
                <div class="custom-searchbar">
                    <input class="email-field" style="width:80%;border-right:1px solid #ccc;" type="text" placeholder="Enter Barcode" name="productBarcode" id="productBarcode" autofocus="true">
                    <!--<input class="email-field" style="width:20%;border-left: 1px solid #ccc;" type="text" placeholder="Discount (%)" id="discount" name="discount" oninput="validatePosDiscount(this.value);"> -->
                    <select name="staff_id" id="staff_id" class="email-field" style="width:20%;border:none; ">
                        <option value="0">Select Staff</option>
                        @for($i=0;$i<count($store_staff);$i++)
                            <option value="{{$store_staff[$i]['id']}}">{{$store_staff[$i]['name']}}</option>
                        @endfor    
                    <select>    
                    <button class="custom-add-item" onclick="addProductToBilling();" id="addBtn">Add Item</button>
                </div>  
                <span id="prodError" class="alert alert-danger elem-hidden" style="font-weight: bold;width:100%;"></span>
                <div class="custom-table-section">
                    <div class="custom-table-heading">
                        <div class="custom-title">Product Name</div>
                        <div class="custom-title">MRP</div>
                        <div class="custom-title">Disc.(%)</div>
                        <div class="custom-title">Disc. Price</div>
                        <div class="custom-title">GST</div>
                        <div class="custom-title">Qty.</div>
                        <div class="custom-title">Net Price</div>
                        <div class="custom-title">Action</div>
                    </div>  
                    <div class="inner-content billing-prod-list">
                        
                    </div>  
                </div>  
                <div>
                    <div class="row">
                        <div class="col-md-2"><input type="checkbox" id="cashvoucher"  name="cashvoucher" onclick="toggleCashVoucherDiv();"> &nbsp; Cash Voucher  </div>
                        <div class="col-md-3"><input type="checkbox" id="customer_gst_no_chk"  name="customer_gst_no_chk" onclick="toggleCustomerGSTNoDiv();"> &nbsp; Customer GST No  </div>
                        <div class="col-md-3"> 
                            @if(empty($order_data))
                                <a href="javascript:;" style="color:#EB5D70;" onclick="savePosOrderDraft();"><i title="POS Order Details" class="fas fa-save"></i>&nbsp; Save Draft</a> &nbsp;&nbsp;&nbsp;&nbsp;
                            @endif
                            <a href="javascript:;" style="color:#EB5D70;" onclick="displayCalculatePosOrderAmount();"><i title="Calculate Return Amount" class="fas fa-calculator"></i>&nbsp; Calculate</a>  
                        </div>
                    </div>
                    <div class="row elem-hidden" id="cashVoucherDiv">
                        <div  class="col-md-8" >
                            Voucher Amount: &nbsp;<input type="text"  id="voucherAmount" placeholder="Amount" name="voucherAmount" class="form-control" style="width:100px;display:inline;">
                            Comment: &nbsp;<input type="text"  id="voucherComment" placeholder="Comment" name="voucherComment" class="form-control" style="width:200px;display:inline;" maxlength="200">
                            <button type="button" name="voucherBtn" id="voucherBtn" value="apply" class="btn payment-btn-sel" onclick="applyVoucher();"><i title="POS Order Details" class="fas fa-check"></i></button>
                            <!--Voucher Approver: <input type="text" id="voucherApprover" placeholder="Voucher Approver" name="voucherApprover">-->
                        </div>
                    </div>    
                    <div class="row elem-hidden" id="customerGstNoDiv">
                        <div  class="col-md-6" >
                            GST No: &nbsp;<input type="text"  id="customerGSTNo" placeholder="GST No" name="customerGSTNo" class="form-control" style="width:200px;display:inline;" maxlength="25">
                        </div>
                    </div>
                </div>
                
                <div class="bottom-btn-area"><button class="custom-print-bill" onclick="createPosOrder();">Print Bill</button></div>
            </div>  
            <div class="right-section">
                <h3 class="cart-sec" id="cartitems_total">Cart Details (0 Items)</h3>
                <div class="custom-cart-wrap">
                    <div class="cart-detail">
                        <div class="custom-sec">
                            <h3 class="custom-subtotal">Subtotal</h3>
                            <h4 class="custom-subtotal-val" id="subtotal_total">0.00</h4>
                        </div>
                        <div class="custom-sec">
                            <h3 class="custom-subtotal">Discount</h3>
                            <h4 class="custom-subtotal-val" id="discount_total">0.00</h4>
                        </div>
                        <div class="custom-sec">
                            <h3 class="custom-subtotal">CGST</h3>
                            <h4 class="custom-subtotal-val" id="cgst_total">0.00</h4>
                        </div>  
                        <div class="custom-sec">
                            <h3 class="custom-subtotal">SGST</h3>
                            <h4 class="custom-subtotal-val" id="sgst_total">0.00</h4>
                        </div>
                        <div class="custom-sec coupon-sec" style="display:none;">
                            <h3 class="custom-subtotal">Total</h3>
                            <h4 class="custom-subtotal-val" id="sub_total">0.00</h4>
                        </div>
                        <div class="custom-sec coupon-sec" style="display:none;">
                            <h3 class="custom-subtotal">Coupon Discount</h3>
                            <h4 class="custom-subtotal-val" id="coupon_discount">0.00</h4>
                        </div>
                        <div class="custom-section-order">
                            <h3 class="custom-subtotal-sec">Order Total</h3>
                            <h4 class="custom-subtotal-value" id="order_total">0.00</h4>
                        </div>
                        <div class="custom-sec voucher-sec" style="display:none;">
                            <h3 class="custom-subtotal">Voucher</h3>
                            <h4 class="custom-subtotal-val" id="voucher_payment">0.00</h4>
                        </div>
                        <div class="custom-section-order voucher-sec" style="display:none;">
                            <h3 class="custom-subtotal-sec">Order Total</h3>
                            <h4 class="custom-subtotal-value" id="order_total_final">0.00</h4>
                        </div>
                    </div>      
                </div>  
                
                <div class="separator-10"></div>
                <span id="couponAddError" class="alert alert-danger elem-hidden" style="font-weight: bold;width:100%;"></span>
                <span id="couponAddSuccess" class="alert alert-success elem-hidden" style="font-weight: bold;width:100%;"></span>
                <div class="col-md-12 row">  
                    <div class="col-md-7" style="float: right;">
                        <input type="text" class="form-control " style="margin-top: 5px;" id="couponNo" placeholder="Coupon" name="couponNo" @if($foc == 1) readonly = "true" @endif> 
                    </div>
                    <div class="col-md-3" style="float: left;">
                        <button class="custom-card payment-btn-sel" onclick="applyPosBillCoupon();" name="applyCouponBtn" id="applyCouponBtn" style=" border: 1px solid #ccc; background: #FFFFFF 0% 0% no-repeat padding-box;padding: 18px;width: 150px;">Apply Coupon</button>
                        <button class="custom-card payment-btn-sel elem-hidden" onclick="removePosBillCoupon();" name="removeCouponBtn" id="removeCouponBtn" style=" border: 1px solid #ccc; background: #FFFFFF 0% 0% no-repeat padding-box;padding: 18px;width: 150px;">Remove Coupon</button>
                        <input type="hidden" name="coupon_item_id" id="coupon_item_id" value="">
                        <input type="hidden" name="coupon_discount" id="coupon_discount" value="">
                        <input type="hidden" name="draft_id" id="draft_id" value="<?php if(isset($_GET['draft_id'])) echo $_GET['draft_id']; ?>">
                        <input type="hidden" name="order_id" id="order_id" value="<?php if(isset($_GET['order_id'])) echo $_GET['order_id']; ?>">
                        <input type="hidden" name="foc" id="foc" value="{{$foc}}">
                        <input type="hidden" name="store_user_id" id="store_user_id" value="{{$store_user_id}}">
                    </div>  
                     
                </div>  
                
                <h3 class="custom-pay">Payment Method</h3>
                
              
                <div class="col-md-12 row">  
                    <div class="col-md-6" style="float: left;">
                        <button class="custom-card payment-btn-sel" style=" border: 1px solid #ccc; background: #FFFFFF 0% 0% no-repeat padding-box;padding: 18px;width: 150px;">CASH</button>
                    </div>  
                    <div class="col-md-6" style="float: right;">
                        <input type="text" class="form-control " style="margin-top: 5px;" id="CashAmt" placeholder="Cash Amount" name="CashAmt" @if($foc == 1) readonly = "true" @endif > 
                    </div>
                </div>  
                <div class="col-md-12 row" style="padding-top: 10px;">  
                    <div class="col-md-6" style="float: left;">
                        <button class="custom-card payment-btn-sel" style="  border: 1px solid #ccc; background: #FFFFFF 0% 0% no-repeat padding-box;padding: 18px;width: 150px;">CARD</button>
                    </div>  
                    <div class="col-md-6" style="float: right;">
                        <input type="text" class="form-control " style="margin-top: 5px;" id="CardAmt" placeholder="Card Amount" name="CardAmt" @if($foc == 1) readonly = "true" @endif> 
                    </div>
                </div> 
                <div class="col-md-12 row" style="padding-top: 10px;">  
                    <div class="col-md-6" style="float: left;">
                        <button class="custom-card payment-btn-sel" style=" border: 1px solid #ccc;  background: #FFFFFF 0% 0% no-repeat padding-box;padding: 18px;width: 150px;">E-WALLET</button>
                    </div>  
                    <div class="col-md-6" style="float: right;">
                        <input type="text" class="form-control" style="margin-top: 5px;" id="E-WalletAmt" placeholder="E-Wallet Amount" name="E-WalletAmt" @if($foc == 1) readonly = "true" @endif> 
                    </div>
                    <input type="text" class="form-control billing-text-input" style="margin-top: 5px;" id="eWalletRefNo" placeholder="E-Wallet Ref No" name="eWalletRefNo" @if($foc == 1) readonly = "true" @endif>
                </div>         
            </div>  
        </div>
    </div>
</div>  
    
    <div class="modal fade" id="pos_add_order_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add POS Order</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible elem-hidden" id="posCreateOrderErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="posCreateOrderSuccessMessage"></div>

                <form method="post" name="createPosOrderForm" id="createPosOrderForm">
                    <div class="modal-body">                    
                        <div class="form-group" >
                            <input checked id="billing_customer_type_existing" type="radio" name="billing_customer_type" value="existing" onclick="toggleBillingCustomerForm('exist');" /> Existing Customer &nbsp;&nbsp;
                            <input id="billing_customer_type_new" type="radio" name="billing_customer_type" value="new" onclick="toggleBillingCustomerForm('new');" /> New Customer
                            <div class="invalid-feedback" id="error_validation_billing_customer_type"></div>
                        </div>

                        <div class="form-group billing-exist-cust-div billing-cust-div" style="display:block;">
                            <label>Customer Phone</label>
                            <input id="customer_phone_existing" type="text" class="form-control" name="customer_phone_existing" value=""  >
                            <div class="invalid-feedback" id="error_validation_billing_phone_no"></div>
                            <input type="hidden" name="customer_id" id="customer_id" value="">
                        </div>
                        
                        <div class="form-group billing-exist-cust-div billing-cust-div" style="display:block;">
                            <label></label>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" id="exist_cust_cancel">Cancel</button>
                            <button type="button" id="exist_cust_btn" name="exist_cust_btn" class="btn btn-dialog" onclick="checkExistingPosCustomer();">Submit</button>
                        </div>
                        
                        <div class="form-group billing-new-cust-div billing-cust-div" >
                            <label>Customer Phone</label>
                            <input id="customer_phone_new" type="text" class="form-control" name="customer_phone_new" value=""  >
                            <div class="invalid-feedback" id="error_validation_billing_customer_phone_new"></div>
                        </div>
                        <div class="form-group billing-new-cust-div billing-cust-div" >
                            <label>Customer Name</label>
                            <?php $salutation_arr = array('Mr','Mrs','Ms','Dr','Other'); ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <select name="customer_salutation" id="customer_salutation" class="form-control">
                                        <option value="">Select</option>
                                        @for($i=0;$i<count($salutation_arr);$i++)
                                            <option value="{{$salutation_arr[$i]}}">{{$salutation_arr[$i]}}</option>
                                        @endfor    
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_billing_customer_salutation"></div>
                                </div>       
                                <div class="col-md-8">
                                    <input id="customer_name" type="text" class="form-control" name="customer_name" value=""  >
                                    <div class="invalid-feedback" id="error_validation_billing_customer_name"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            @if(isset($store_data->bags_inventory) && $store_data->bags_inventory > 0)
                                <?php $bags_count = ($store_data->bags_inventory < 50 )?$store_data->bags_inventory:25;  ?>
                                <div class="form-group billing-new-cust-div billing-cust-div col-md-5" style="padding-top:10px;">
                                    <label>No of Bags</label>
                                    <select name="bags_count" id="bags_count" class="form-control">
                                        <option value="">Select</option>
                                        @for($i=1;$i<=$bags_count;$i++)
                                            <option value="{{$i}}">{{$i}}</option>
                                        @endfor    
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_billing_bags_count"></div>
                                </div>
                            @else
                                <input type="hidden" name="bags_count" id="bags_count" value="0">
                            @endif
                            <div class="form-group billing-new-cust-div billing-cust-div col-md-7" style="padding-top:10px;">
                                <label>Email Address</label>
                                <input id="customer_email" type="text" class="form-control" name="customer_email" value=""  >
                                <div class="invalid-feedback" id="error_validation_billing_customer_email"></div>
                            </div>
                        </div>    
                        <div class="form-row">
                            <div class="form-group billing-new-cust-div billing-cust-div col-md-6" >
                                <label>DOB</label>
                                <input id="customer_dob" type="text" class="form-control datepicker" name="customer_dob" value=""  data-date-format="yyyy/mm/dd">
                                <div class="invalid-feedback" id="error_validation_billing_customer_dob"></div>
                            </div>

                            <div class="form-group billing-new-cust-div billing-cust-div col-md-6" >
                                <label>Wedding Date</label>
                                <input id="customer_wedding_date" type="text" class="form-control datepicker" name="customer_wedding_date" value=""  data-date-format="yyyy/mm/dd">
                                <div class="invalid-feedback" id="error_validation_billing_customer_wedding_date"></div>
                            </div>
                        </div>    
                        <div class="form-group billing-new-cust-div billing-cust-div" >
                            <label>Postal Code</label>
                            <input id="customer_postal_code" type="text" class="form-control" name="customer_postal_code" value=""  >
                            <div class="invalid-feedback" id="error_validation_billing_customer_postal_code"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer billing-new-cust-div billing-cust-div">
                        <div id="pos_add_order_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="pos_order_cancel" name="pos_order_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="pos_order_submit" name="pos_order_submit" class="btn btn-dialog" onclick="submitCreatePosOrder();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm_delete_billing_product" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteBillingProductErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="deleteBillingProductSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Product<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-demand_item-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_billing_product_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="delete_billing_product_btn" name="delete_billing_product_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="create_pos_order_error_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="createPosOrderErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="createPosOrderSuccessMessage"></div>
                <div class="modal-body">
                    <div id="create_pos_order_error"></div>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="save_pos_order_draft_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="save_pos_order_draft_title">Save Draft</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"   id="savePosOrderDraftErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="savePosOrderDraftSuccessMessage"></div>
                <div class="modal-body">
                    
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pos_order_calculate_amount_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Calculate</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible elem-hidden" id="posOrderCalculateAmountErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="posOrderCalculateAmountSuccessMessage"></div>

                <form method="post" name="posOrderCalculateAmountForm" id="posOrderCalculateAmountForm">
                    <div class="modal-body">                    
                        <div class="form-group" >
                            <label>Bill Total</label>
                            <input id="bill_total_calculate" type="text" class="form-control" name="bill_total_calculate" value=""  readonly="true">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4" >
                                <label>Cash Amount</label>
                                <input id="cash_amount_calculate" type="text" class="form-control" name="cash_amount_calculate" value=""  >
                                <div class="invalid-feedback" id="error_validation_cash_amount_calculate"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Card Amount</label>
                                <input id="card_amount_calculate" type="text" class="form-control" name="card_amount_calculate" value=""  >
                                <div class="invalid-feedback" id="error_validation_card_amount_calculate"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>E-Wallet Amount</label>
                                <input id="ewallet_amount_calculate" type="text" class="form-control" name="ewallet_amount_calculate" value=""  >
                                <div class="invalid-feedback" id="error_validation_ewallet_amount_calculate"></div>
                            </div>
                        </div>
                        <div class="form-group" >
                            <label>Return Amount</label>
                            <input id="return_amount_calculate" type="text" class="form-control" name="return_amount_calculate" value=""  readonly="true">
                        </div>
                    </div>
                    <div class="modal-footer center-footer ">
                        <button type="button" id="pos_order_calculate_amount_cancel" name="pos_order_calculate_amount_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="pos_order_calculate_amount_submit" name="pos_order_calculate_amount_submit" class="btn btn-dialog" onclick="calculatePosOrderAmount();">Calculate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/pos.js?v=3.50') }}" type="text/javascript"></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" type="text/javascript"></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">
$(document).ready(function(){
    $("#coupon_discount,#coupon_item_id").val('');
    $("#cashvoucher").attr('checked',false).prop("checked", false);
    $("#voucherAmount,#voucherComment").val('');
    <?php if(isset($_GET['draft_id']) && !empty($_GET['draft_id'])) { ?> loadPosDraft(<?php echo $_GET['draft_id']; ?>); <?php } ?>
})
</script>    
@endsection
