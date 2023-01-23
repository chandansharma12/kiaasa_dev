@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'store/dashboard'),array('name'=>'Assets Orders List','link'=>'store/asset/order/list'),array('name'=>'Edit Assets Order')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Assets Order'); ?>
    
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
                        {{date('d M Y',strtotime($order_data->created_at))}}    
                    </div> 

                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$order_data->user_name}}    
                    </div> 
                </div>
            </form> 
            <hr/>
            
            @if(!in_array(strtolower($order_data->order_status),array('approved','accounts_submitted','accounts_rejected')))
                <button type="button" id="add_order_row" name="add_order_row" onclick="addAssetOrderRow('edit');" class="btn btn-dialog">Add+</button>
            @endif
            <div class="separator-10"></div>
            <div id="ordersList">
                <div id="createOrderErrorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="updateOrderStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="updateOrderStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
                <form class="" name="createOrderFrm" id="createOrderFrm" method="POST" enctype="multipart/form-data">
                   
                    <div class="order-items-container" id="">
                        @for($i=0;$i<count($order_items_list);$i++)
                            <div class="form-row order-items-row" @if($i==0) id="order_items_row_first" @endif >
                                <div class="form-group col-md-3">
                                    @if($i==0) <label class="label-text">Store Asset</label> @endif
                                    <div class="typeahead__container">
                                        <div class="typeahead__field">
                                            <div class="typeahead__query">
                                                <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" value="{{$order_items_list[$i]->item_name}}">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="storeItem_add[]" id="storeItem_add" class="form-control order-item_edit" value="{{$order_items_list[$i]->item_id}}">
                                </div>
                                <div class="form-group col-md-1">
                                    @if($i==0) <label class="label-text">Quantity</label> @endif
                                    <input type="number" name="storeItemQuantity_add[]" id="storeItemQuantity_add" class="form-control order-item-quantity_edit" value="{{$order_items_list[$i]->item_quantity}}"  onkeyup="updateAssetOrderPrice('edit');" @if(in_array(strtolower($order_data->order_status),array('approved','accounts_submitted','accounts_rejected'))) disabled @endif>
                                </div>
                                <div class="form-group col-md-1">
                                    @if($i==0) <label class="label-text">Unit Price</label> @endif
                                    <span class="order-item-unit-price"></span>
                                </div>
                                <div class="form-group col-md-1">
                                    @if($i==0) <label class="label-text">Total Price</label> @endif
                                    <span class="order-item-price"></span>
                                </div>    
                                <div class="form-group col-md-2">
                                    @if($i==0) <label class="label-text">Initial Picture</label> @endif
                                    <input type="file" name="storeItemPicture_add[]" id="storeItemPicture_add" class="form-control order-item-picture_edit" @if(in_array(strtolower($order_data->order_status),array('approved','accounts_submitted','accounts_rejected'))) disabled @endif>
                                </div>
                                <div class="form-group col-md-1 order-image-div">
                                    @if(!empty($order_items_list[$i]->initial_picture))
                                        <a href="javascript:;" onclick="displayDialogImage('{{asset('images/order_images/'.$order_data->id)}}/{{$order_items_list[$i]->initial_picture}}');">
                                            <img src="{{asset('images/order_images/'.$order_data->id)}}/thumbs/{{$order_items_list[$i]->initial_picture}}" class="order-thumb-image">
                                        </a>
                                    @endif 
                                </div> 
                                @if(in_array(strtolower($order_data->order_status),array('approved','accounts_submitted','accounts_rejected')))
                                    <div class="form-group col-md-2">
                                        @if($i==0) <label class="label-text">New Picture</label> @endif
                                        <input type="file" name="storeItemNewPicture_{{$order_items_list[$i]->id}}" id="storeItemNewPicture_{{$order_items_list[$i]->id}}" class="form-control order-item-picture_edit" @if(in_array(strtolower($order_data->order_status),array('accounts_submitted'))) disabled @endif>
                                    </div>
                                    <div class="form-group col-md-1 order-image-div">
                                        @if(!empty($order_items_list[$i]->new_picture))
                                            <a href="javascript:;" onclick="displayDialogImage('{{asset('images/order_images/'.$order_data->id)}}/{{$order_items_list[$i]->new_picture}}');">
                                                <img src="{{asset('images/order_images/'.$order_data->id)}}/thumbs/{{$order_items_list[$i]->new_picture}}" class="order-thumb-image">
                                            </a>
                                        @endif 
                                    </div> 
                                @endif
                                
                                @if(!in_array(strtolower($order_data->order_status),array('approved','accounts_submitted','accounts_rejected')))
                                    <div class="form-group col-md-1 ">
                                        @if($i==0) <label class="label-text">Delete</label> @endif
                                        <button type="button" id="order_add_submit" name="order_add_submit" class="btn btn-dialog" value="Submit" onclick="deleteAssetOrderItem({{$order_items_list[$i]->id}});">X</button>
                                    </div>
                                @endif
                            </div>
                        @endfor   
                        
                        @if(count($order_items_list) == 0)
                            <div class="form-row order-items-row" id="order_items_row_first">
                                <div class="form-group col-md-3">
                                    <label class="label-text">Store Asset</label>
                                    <div class="typeahead__container">
                                        <div class="typeahead__field">
                                            <div class="typeahead__query">
                                                <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" value="">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="storeItem_add[]" id="storeItem_add" class="form-control order-item_edit" value="">
                                </div>
                                <div class="form-group col-md-1">
                                    <label class="label-text">Quantity</label>
                                    <input type="number" name="storeItemQuantity_add[]" id="storeItemQuantity_add" class="form-control order-item-quantity_edit" onkeyup="updateAssetOrderPrice('edit');">
                                </div>
                                <div class="form-group col-md-1">
                                    <label class="label-text">Unit Price</label>
                                    <span class="order-item-unit-price"></span>
                                </div>
                                <div class="form-group col-md-1">
                                    <label class="label-text">Total Price</label>
                                    <span class="order-item-price"></span>
                                </div>   
                                <div class="form-group col-md-2">
                                    <label class="label-text">Picture</label>
                                    <input type="file" name="storeItemPicture_add[]" id="storeItemPicture_add" class="form-control order-item-picture_edit">
                                </div>
                            </div>  
                        @endif    
                    </div>
                    <div class="form-row" >
                        <div class="form-group col-md-2">
                            <span  id="order_grand_total" name="order_grand_total"></span>
                        </div> 
                    </div> 
                    <br/>
                    @if(in_array(strtolower($order_data->order_status),array('approved','accounts_submitted','accounts_rejected')))
                        <div class="form-row" >
                            @if(in_array(strtolower($order_data->order_status),array('approved','accounts_rejected')))
                                <div class="form-group col-md-1">
                                    <label></label>
                                    <button type="button" id="add_store_asset_bill" name="add_store_asset_bill" onclick="addAssetOrderBill();" class="btn btn-dialog">Add Bill</button>
                                </div>
                            @endif    
                            
                        </div>
                        <div class="separator-10"></div>
                        @if(!empty($asset_bills))
                            <div class="table-responsive table-filter" style="width:50%;">
                                <h5>Bills</h5>
                                <table class="table table-striped admin-table" cellspacing="0" >
                                    <thead><tr class="header-tr"><th>Bill Amount</th><th>Bill Picture</th><th>Payment Method</th><th>Date Added</th>
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
                    <div class="separator-10"></div>
                    <div class="form-row" >
                        @if(!in_array(strtolower($order_data->order_status),array('accounts_submitted')))
                            <button type="button" id ="order_add_submit" name="order_add_submit" class="btn btn-dialog" value="Submit" onclick="createAssetsOrder('edit');">Update Order</button>&nbsp;&nbsp;
                        @endif
                        
                        <?php /* ?>@if(in_array(strtolower($order_data->order_status),array('approved','accounts_rejected')) && !empty($order_data->bill_amount) && !empty($order_data->bill_picture))<?php */ ?>
                        @if(in_array(strtolower($order_data->order_status),array('approved','accounts_rejected')) && !empty($asset_bills) )
                            <button type="button" id ="order_accounts_submit" name="order_accounts_submit" class="btn btn-dialog" value="Submit" onclick="submitAssetOrderToAccounts({{$order_data->id}});">Submit Order</button>&nbsp;&nbsp;
                        @endif
                        @if(!in_array(strtolower($order_data->order_status),array('accounts_submitted')))
                            <button type="button" id="order_add_cancel" name="order_add_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('store/asset/order/list')}}'">Cancel</button>
                            <input type="hidden" name="edit_order" id="edit_order" value="1">
                        @endif
                    </div>
                    @csrf
                </form>
            </div>
        </div>
    </section>
    
    <div class="modal fade" id="confirm_delete_order_item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteOrderItemErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="deleteOrderItemSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Order Asset<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-order_item-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_rows_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="delete_rows_btn" name="delete_rows_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="asset_order_bill_add_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Assets Bill</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addAssetsBillSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addAssetsBillErrorMessage"></div>

                <form class="" name="addAssetsBillFrm" id="addAssetsBillFrm" type="POST"  enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Bill Amount</label>
                            <input id="bill_amount_add" type="text" class="form-control" name="bill_amount_add" value="" autofocus > 
                            <div class="invalid-feedback" id="error_validation_bill_amount_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Bill Picture</label>
                            <input type="file" name="bill_picture_add" id="bill_picture_add" class="form-control" >
                            <div class="invalid-feedback" id="error_validation_bill_picture_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Payment Method</label>
                            <input type="radio" name="bill_payment_method_add" id="bill_payment_method_add_online" value="online" onclick="updateAssetBillFields(this.value,'add');" /> Online &nbsp;&nbsp;
                            <input type="radio" name="bill_payment_method_add" id="bill_payment_method_add_cash" value="cash" onclick="updateAssetBillFields(this.value,'add');" /> Cash
                            <div class="invalid-feedback" id="error_validation_bill_payment_method_add"></div>	
                        </div>
                        <div class="form-group asset-bank-payment-div-add" >
                            <label>Bank Name</label>
                            <input id="bill_bank_name_add" type="text" class="form-control" name="bill_bank_name_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_name_add"></div>
                        </div>
                        <div class="form-group asset-bank-payment-div-add">
                            <label>Customer Name</label>
                            <input id="bill_bank_customer_name_add" type="text" class="form-control" name="bill_bank_customer_name_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_customer_name_add"></div>
                        </div>
                        <div class="form-group asset-bank-payment-div-add" >
                            <label>Account Number</label>
                            <input id="bill_bank_account_no_add" type="text" class="form-control" name="bill_bank_account_no_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_account_no_add"></div>
                        </div>
                        <div class="form-group asset-bank-payment-div-add" >
                            <label>Account Type</label>
                            <input id="bill_bank_account_type_add" type="text" class="form-control" name="bill_bank_account_type_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_account_type_add"></div>
                        </div>
                        <div class="form-group asset-bank-payment-div-add" >
                            <label>IFSC Code</label>
                            <input id="bill_bank_ifsc_code_add" type="text" class="form-control" name="bill_bank_ifsc_code_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_ifsc_code_add"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="asset_order_bill_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="asset_order_bill_add_cancel" name="asset_order_bill_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="asset_order_bill_add_submit" name="asset_order_bill_add_submit" class="btn btn-dialog" onclick="submitAddAssetOrderBill();">Submit</button>
                        <input type="hidden" name="store_asset_order_id" id="store_asset_order_id" value="{{$order_data->id}}">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="asset_order_bill_edit_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Assets Bill</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editAssetsBillSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editAssetsBillErrorMessage"></div>

                <form class="" name="editAssetsBillFrm" id="editAssetsBillFrm" type="POST"  enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Bill Amount</label>
                            <input id="bill_amount_edit" type="text" class="form-control" name="bill_amount_edit" value="" autofocus > 
                            <div class="invalid-feedback" id="error_validation_bill_amount_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Bill Picture</label>
                            <img id="bill_image_edit" src="" style="display:none;" class="order-thumb-image">
                            <input type="file" name="bill_picture_edit" id="bill_picture_edit" class="form-control" >
                            <div class="invalid-feedback" id="error_validation_bill_picture_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Payment Method</label>
                            <input type="radio" name="bill_payment_method_edit" id="bill_payment_method_edit_online" value="online" onclick="updateAssetBillFields(this.value,'edit');" /> Online &nbsp;&nbsp;
                            <input type="radio" name="bill_payment_method_edit" id="bill_payment_method_edit_cash" value="cash" onclick="updateAssetBillFields(this.value,'edit');" /> Cash
                            <div class="invalid-feedback" id="error_validation_bill_payment_method_edit"></div>	
                        </div>
                        <div class="form-group asset-bank-payment-div-edit" >
                            <label>Bank Name</label>
                            <input id="bill_bank_name_edit" type="text" class="form-control" name="bill_bank_name_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_name_edit"></div>
                        </div>
                        <div class="form-group asset-bank-payment-div-edit">
                            <label>Customer Name</label>
                            <input id="bill_bank_customer_name_edit" type="text" class="form-control" name="bill_bank_customer_name_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_customer_name_edit"></div>
                        </div>
                        <div class="form-group asset-bank-payment-div-edit" >
                            <label>Account Number</label>
                            <input id="bill_bank_account_no_edit" type="text" class="form-control" name="bill_bank_account_no_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_account_no_edit"></div>
                        </div>
                        <div class="form-group asset-bank-payment-div-edit" >
                            <label>Account Type</label>
                            <input id="bill_bank_account_type_edit" type="text" class="form-control" name="bill_bank_account_type_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_account_type_edit"></div>
                        </div>
                        <div class="form-group asset-bank-payment-div-edit" >
                            <label>IFSC Code</label>
                            <input id="bill_bank_ifsc_code_edit" type="text" class="form-control" name="bill_bank_ifsc_code_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_bill_bank_ifsc_code_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="asset_order_bill_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="asset_order_bill_edit_cancel" name="asset_order_bill_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="asset_order_bill_edit_submit" name="asset_order_bill_edit_submit" class="btn btn-dialog" onclick="updateAssetOrderBill();">Submit</button>
                        <input type="hidden" name="store_asset_bill_id" id="store_asset_bill_id" value="">
                        <input type="hidden" name="store_asset_edit_order_id" id="store_asset_edit_order_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script type="text/javascript" >
        var action_type = 'edit',page_type = 'asset',asset_order_id={{$order_data->id}};
    </script>
@endif

<script src="{{ asset('js/store.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/jquery.typeahead.min.css') }}" />
<script src="{{ asset('js/jquery.typeahead.min.js') }}" ></script>
@endsection
