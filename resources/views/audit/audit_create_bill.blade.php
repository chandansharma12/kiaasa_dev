@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Audits List','link'=>'audit/list'),array('name'=>'Audit Create Bill')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Audit Create Bill'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            
            <form id="order_detail_form" name="order_detail_form">
                <input type="hidden" name="audit_id" id="audit_id" value="{{$audit_data->id}}">
                <input type="hidden" name="store_id" id="store_id" value="{{$audit_data->store_id}}">
                
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Audit ID</label>						
                        {{$audit_data->id}}    
                    </div> 

                    <div class="form-group col-md-2">
                        <label for="Product">Status</label>						
                        {{ucwords(str_replace('_',' ',$audit_data->audit_status))}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Store</label>						
                        {{$audit_data->store_name}}    
                    </div>
                    <div class="form-group col-md-3">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($audit_data->created_at))}}    
                    </div> 
                    <div class="form-group col-md-3">
                        <label for="Category">Created by</label>						
                        {{$audit_data->auditor_name}}    
                    </div> 
                </div>    
               
            </form> 
            <hr/>
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                   
                    <div class="table-responsive table-filter">
                        <h6>Audit Inventory in System, not in Store:</h6>
                        <?php $total_system = array('cost_price'=>0,'mrp'=>0,'net_price'=>0); ?>
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                            <thead><tr class="header-tr">
                                <th>SNo</th><th>ID</th><th>Product</th><th>Size</th><th>Color</th><th>SKU</th><th>QR Code</th><th>Store</th><th>Cost Price</th><th>MRP</th><th>Net Price</th><th>Status</th></tr></thead>
                            <tbody>
                                
                                @for($i=0;$i<count($audit_inv_system);$i++)
                                    <tr>
                                        <td>{{$i+1}}</td>
                                        <td>{{$audit_inv_system[$i]->id}}</td>
                                        <td>{{$audit_inv_system[$i]->product_name}}</td>
                                        <td>{{$audit_inv_system[$i]->size_name}}</td>
                                        <td>{{$audit_inv_system[$i]->color_name}}</td>
                                        <td>{{$audit_inv_system[$i]->product_sku}}</td>
                                        <td>{{$audit_inv_system[$i]->peice_barcode}}</td>
                                        <td>{{$audit_inv_system[$i]->store_name}}</td>
                                        <td>{{$audit_inv_system[$i]->store_base_price}}</td>
                                        <td>{{$audit_inv_system[$i]->sale_price}}</td>
                                        <td>{{isset($audit_inv_system[$i]->net_price)?$audit_inv_system[$i]->net_price:''}}</td>
                                        <td>{{CommonHelper::getposProductStatusName($audit_inv_system[$i]->product_status)}}</td>
                                    </tr>
                                    <?php $total_system['cost_price']+=$audit_inv_system[$i]->store_base_price; ?>
                                    <?php $total_system['mrp']+=$audit_inv_system[$i]->sale_price; ?>
                                    <?php $total_system['net_price']+=isset($audit_inv_system[$i]->net_price)?$audit_inv_system[$i]->net_price:0; ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="8">Total</th>
                                    <th>{{$total_system['cost_price']}}</th>
                                    <th>{{$total_system['mrp']}}</th>
                                    <th>{{round($total_system['net_price'],2)}}</th>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <h6>Audit Inventory in Store, not in System:</h6>
                        <?php $total_store = array('cost_price'=>0,'mrp'=>0,'net_price'=>0); ?>
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px; ">
                            <thead><tr class="header-tr">
                                <th>SNo</th><th>ID</th><th>Product</th><th>Size</th><th>Color</th><th>SKU</th><th>QR Code</th><th>Store</th><th>Cost Price</th><th>MRP</th><th>Net Price</th><th>Status</th></tr></thead>
                            <tbody>
                                
                                @for($i=0;$i<count($audit_inv_store);$i++)
                                    <tr>
                                        <td>{{$i+1}}</td>
                                        <td>{{$audit_inv_store[$i]->id}}</td>
                                        <td>{{$audit_inv_store[$i]->product_name}}</td>
                                        <td>{{$audit_inv_store[$i]->size_name}}</td>
                                        <td>{{$audit_inv_store[$i]->color_name}}</td>
                                        <td>{{$audit_inv_store[$i]->product_sku}}</td>
                                        <td>{{$audit_inv_store[$i]->peice_barcode}}</td>
                                        <td>{{$audit_inv_store[$i]->store_name}}</td>
                                        <td>{{$audit_inv_store[$i]->store_base_price}}</td>
                                        <td>{{$audit_inv_store[$i]->sale_price}}</td>
                                        <td>{{round($audit_inv_store[$i]->net_price,2)}}</td>
                                        <td>{{CommonHelper::getposProductStatusName($audit_inv_store[$i]->product_status)}}</td>
                                    </tr>
                                    <?php $total_store['cost_price']+=$audit_inv_store[$i]->store_base_price; ?>
                                    <?php $total_store['mrp']+=$audit_inv_store[$i]->sale_price; ?>
                                    <?php $total_store['net_price']+=$audit_inv_store[$i]->net_price; ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="8">Total</th>
                                    <th>{{$total_store['cost_price']}}</th>
                                    <th>{{$total_store['mrp']}}</th>
                                    <th>{{round($total_store['net_price'],2)}}</th>
                                </tr>
                            </tfoot>
                        </table>
                        
                    </div> 
                    
                    @if(empty($discount) && empty($gst) && count($audit_inv_system) > 0)
                        
                        <form id="bill_calculate_form" name="bill_calculate_form">
                            <div id="calculateBillErrorMessage" class="alert alert-danger elem-hidden"></div>
                            <div id="calculateBillSuccessMessage" class="alert alert-success elem-hidden"></div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="Season">Discount</label>						
                                    <input type="text" class="form-control" name="discount" id="discount">    
                                </div> 
                                <div class="form-group col-md-2">
                                    <label for="Season">GST Type</label>						
                                    <select name="gst" id="gst" class="form-control">
                                        <option value="">Select</option>
                                        <option value="exc">Exclusive</option>
                                        <option value="inc">Inclusive</option>
                                    </select>    
                                </div> 
                                <div class="form-group col-md-3">
                                    <label for="Season">&nbsp;</label>						
                                    <button type="button" class="btn btn-dialog" name="bill_calculate_btn" id="bill_calculate_btn" value="1" onclick="calculateAuditBill();">Calculate Bill</button>    
                                    <input type="hidden" name="calculate_bill" id="calculate_bill" value="1">
                                </div> 
                                
                            </div>       
                        </form>    
                    
                        <form id="discount_calculate_form" name="discount_calculate_form" >
                            <div id="calculateDiscountErrorMessage" class="alert alert-danger elem-hidden"></div>
                            <div id="calculateDiscountSuccessMessage" class="alert alert-success elem-hidden"></div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="Season">Bill Amount</label>						
                                    <input type="text" class="form-control" name="required_bill_amount" id="required_bill_amount">    
                                    <div class="invalid-feedback" id="error_validation_required_bill_amount"></div>
                                </div> 
                                <div class="form-group col-md-2">
                                    <label for="Season">GST Type</label>						
                                    <select name="discount_gst_type" id="discount_gst_type" class="form-control">
                                        <option value="">Select</option>
                                        <!--<option value="exc">Exclusive</option>-->
                                        <option value="inc">Inclusive</option>
                                    </select>    
                                    <div class="invalid-feedback" id="error_validation_discount_gst_type"></div>
                                </div> 
                                <div class="form-group col-md-2">
                                    <label for="Season">&nbsp;</label>						
                                    <button type="button" class="btn btn-dialog" name="discount_calculate_btn" id="discount_calculate_btn" value="1" onclick="calculateAuditDiscount();">Calculate Discount</button>    
                                    <input type="hidden" name="calculate_discount" id="calculate_discount" value="1">
                                </div> 
                            </div>       
                        </form>    
                    @endif
                    
                    @if($create_bill == 1)
                        <form id="bill_create_form" name="bill_create_form">
                            <div id="createBillErrorMessage" class="alert alert-danger elem-hidden"></div>
                            <div id="createBillSuccessMessage" class="alert alert-success elem-hidden"></div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="Season">Discount</label>						
                                    <input type="text" class="form-control" name="bill_discount" id="bill_discount" readonly="true" value="{{$discount}} %">    
                                </div> 
                                <div class="form-group col-md-2">
                                    <label for="Season">GST Type</label>						
                                    <input type="text" class="form-control" name="bill_gst" id="bill_gst" readonly="true" value="{{$gst == 'inc'?'Inclusive':'Exclusive'}}">    
                                </div> 
                            </div>    
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="Season">Excess Amount</label>						
                                    <input type="text" class="form-control" name="excess_amount" id="excess_amount" readonly="true" value="{{round($total_system['net_price'],2)}}">    
                                </div> 
                                <div class="form-group col-md-2">
                                    <label for="Season">Exchange Amount</label>						
                                    <input type="text" class="form-control" name="exchange_amount" id="exchange_amount" readonly="true" value="{{round($total_store['net_price'],2)}}">    
                                </div> 
                                <div class="form-group col-md-2">
                                    <label for="Season">Net Bill Amount</label>						
                                    <input type="text" class="form-control" name="net_bill_amount" id="net_bill_amount" readonly="true" value="{{round($total_system['net_price']-$total_store['net_price'],2)}}">    
                                </div> 
                                
                            </div>     
                           
                            <div class="form-row">
                                <?php $salutation_arr = array('Mr','Mrs','Ms','Dr','Other'); ?>
                                <div class="form-group col-md-2">
                                    <label>Salutation</label>
                                    <select name="customer_salutation" id="customer_salutation" class="form-control">
                                        <option value="">Select</option>
                                        @for($i=0;$i<count($salutation_arr);$i++)
                                            <option value="{{$salutation_arr[$i]}}">{{$salutation_arr[$i]}}</option>
                                        @endfor    
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_billing_customer_salutation"></div>
                                </div>       
                                <div class="form-group col-md-2">
                                    <label>Customer Name</label>
                                    <input id="customer_name" type="text" class="form-control" name="customer_name" value=""  >
                                    <div class="invalid-feedback" id="error_validation_billing_customer_name"></div>
                                </div>
                                 <div class="form-group col-md-2" >
                                    <label>Customer Phone</label>
                                    <input id="customer_phone_new" type="text" class="form-control" name="customer_phone_new" value=""  >
                                    <div class="invalid-feedback" id="error_validation_billing_customer_phone_new"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="Season">&nbsp;</label>						
                                    <button type="button" class="btn btn-dialog" name="bill_create_btn" id="bill_create_btn" onclick="createAuditBill();">Create Bill</button>    
                                </div> 
                            </div>
                        </form>    
                    @endif
                    
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/audit.js?v=1.25') }}" ></script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
<script type="text/javascript">
    
</script>
@endsection
