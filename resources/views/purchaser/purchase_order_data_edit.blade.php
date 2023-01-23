@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'SOR Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'Edit Purchase Order Data')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Purchase Order Data'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="editPurchaseOrderErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="editPurchaseOrderSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <div id="productsContainer">
                
                <form class="" name="editPOFrm" id="editPOFrm" type="POST">
                    @if($po_data->category_id == 322 || $po_data->category_id == 324)
                        <div class="form-group row submit-data-row">
                            <label for="Category" class="col-sm-2 col-form-label">Category:</label>
                            <div class="col-sm-2">
                                <select class="form-control" name="category_id" id="category_id">
                                    <option value="">Select Category</option>
                                    @for($i=0;$i<count($po_category_list);$i++)
                                        <?php $sel = ($po_data->category_id == $po_category_list[$i]['id'])?"selected":''; ?>
                                        <option value="{{$po_category_list[$i]['id']}}" {{$sel}}>{{$po_category_list[$i]['name']}}</option>
                                    @endfor  
                                </select>  
                                <div class="invalid-feedback" id="error_validation_category_id"></div>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="category_id" id="category_id" value="{{$po_data->category_id}}"/>
                    @endif
                    
                    <div class="form-group row submit-data-row">
                        <label for="delivery date" class="col-sm-2 col-form-label">Delivery Date:</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" id="delivery_date" name="delivery_date" placeholder="Delivery Date" value="{{date('d/m/Y',strtotime($po_data->delivery_date))}}">
                            <div class="invalid-feedback" id="error_validation_delivery_date"></div>
                        </div>
                    </div>

                    <div class="form-group row submit-data-row">
                        <label for="other cost" class="col-sm-2 col-form-label">Other Comments:</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" id="other_comments" name="other_comments" placeholder="Other Comments" value="{{$po_data->other_comments}}" maxlength="200"/>
                        </div>
                    </div>

                    <div class="form-group row submit-data-row">
                        <label for="other cost" class="col-sm-2 col-form-label"></label>
                        <div class="col-sm-2">
                            <button type="button" onclick="window.location.href='{{url('purchase-order/product/list')}}'" class="btn btn-secondary" id="edit_po_cancel">Cancel</button> &nbsp;
                            <button type="button" id="create_purchase_order_submit" name="create_purchase_order_submit" class="btn btn-dialog" onclick="updatePurchaseOrderData();">Submit</button>
                        </div>
                    </div>
                    <input type="hidden" name="po_id" id="po_id" value="{{$po_data->id}}">
                </form>        
                    
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.1') }}" ></script>
<script>
</script>    
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('#delivery_date').datepicker({format: 'dd/mm/yyyy',startDate: '-0d'});</script>
@endsection
