@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'SOR Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'Create Static Purchase Order')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Create Static Purchase Order'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="purchaseOrderCreateErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="purchaseOrderCreateSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <div id="productsContainer">
                
                <div class="separator-10"></div>
                
                <div class="form-group row ">
                    <label for="other cost" class="col-sm-2 col-form-label">Vendor:</label>
                    <div class="col-sm-2">
                        <select class="form-control" name="vendor_id" id="vendor_id">
                            <option value="">Select Vendor</option>
                            @for($i=0;$i<count($vendor_list);$i++)
                                <option value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}} ({{$vendor_list[$i]['email']}}) </option>
                            @endfor  
                        </select>    
                        <div class="invalid-feedback" id="error_validation_vendor_id"></div>
                    </div>
                </div>
                <div class="form-group row ">
                    <label for="other cost" class="col-sm-2 col-form-label">Category:</label>
                    <div class="col-sm-2">
                        <select class="form-control" name="category_id" id="category_id">
                            <option value="">Select Category</option>
                            @for($i=0;$i<count($po_category_list);$i++)
                                <option value="{{$po_category_list[$i]['id']}}">{{$po_category_list[$i]['name']}}</option>
                            @endfor  
                        </select>  
                        <div class="invalid-feedback" id="error_validation_category_id"></div>
                    </div>
                </div>
                <div class="form-group row ">
                    <label for="delivery date" class="col-sm-2 col-form-label">Delivery Date:</label>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="delivery_date" name="delivery_date" placeholder="Delivery Date" >
                        <div class="invalid-feedback" id="error_validation_delivery_date"></div>
                    </div>
                </div>
                <div class="form-group row ">
                    <label for="other cost" class="col-sm-2 col-form-label">Other Cost:</label>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="other_cost" name="other_cost" placeholder="Other Cost" onkeyup="validateNumericField(this);displayPurchaseOrderRows();">
                    </div>
                </div>
                <div class="form-group row ">
                    <label for="other cost" class="col-sm-2 col-form-label">Other Comments:</label>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="other_comments" name="other_comments" placeholder="Other Comments">
                    </div>
                </div>
                
                <div class="form-group row ">
                    <label for="other cost" class="col-sm-2 col-form-label"></label>
                    <div class="col-sm-2">
                        <button type="button" id="create_purchase_order_submit" name="create_purchase_order_submit" class="btn btn-dialog" onclick="createStaticPurchaseOrder();">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.1') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('#delivery_date').datepicker({format: 'dd/mm/yyyy',startDate: '-0d'});</script>
@endsection
