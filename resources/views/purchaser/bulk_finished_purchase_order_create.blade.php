@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Purchase Orders','link'=>'purchase-order/product/list'),array('name'=>'Create Stitching Purchase Order')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Create Stitching Purchase Order'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="OrderRowStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="OrderRowStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            
            <div id="purchaseOrderCreateErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="purchaseOrderCreateSuccessMessage" class="alert alert-success" style="display:none;"></div>
            
            <div id="productsContainer">
                
                <form class="" name="addPOFrm" id="addPOFrm" type="POST">
                    <div class="table-responsive">
                        <div id="style_row_add_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <table class="table table-striped clearfix">
                            <thead>
                                <tr>
                                    <th>Style</th><th>Color</th>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <th>{{$size_list[$i]['size']}}</th>
                                    @endfor
                                    <th>Rate</th><th>Total Qty</th><th>Amount</th><th></th>
                                </tr>
                                <tr>
                                    <td style="width:10%;">
                                        <!--<input id="style_name" type="text" class="form-control" name="style_name" value="" autofocus oninput="getBulkFinishedStyleData(this.value);" >-->
                                        <select name="style_name"  id="style_name" class="form-control" onchange="getBulkFinishedStyleData(this.value);" autofocus >
                                            <option value="">Design</option>
                                            @for($i=0;$i<count($design_list);$i++)
                                                <option value="{{$design_list[$i]['sku']}}">{{$design_list[$i]['sku']}}</option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td>
                                        <input id="color_name" type="text" class="form-control" name="color_name" value="" readonly="true" >
                                        <input id="color_id" type="hidden" name="color_id" value=""  />
                                        <input id="hsn_code" type="hidden" name="hsn_code" value=""  />
                                    </td>
                                    @for($i=0;$i<count($size_list);$i++)
                                    <td>
                                        <input id="size_{{$size_list[$i]['id']}}" type="text" class="form-control size-item" name="size_{{$size_list[$i]['id']}}" value="" onkeyup="updatePOTotalQtyCost();">
                                    </td>
                                    @endfor
                                    <td>
                                        <input id="style_rate" type="text" class="form-control" name="style_rate" value="" readonly="true">
                                    </td>
                                    <td>
                                        <input id="size_total" type="text" class="form-control" name="size_total" value="" readonly="true">
                                    </td>
                                    <td>
                                        <input id="amount_total" type="text" class="form-control" name="amount_total" value="" readonly="true">
                                    </td>
                                    <td>
                                        <button type="button" id="purchase_order_row_add_submit" name="purchase_order_row_add_submit" class="btn btn-dialog" onclick="addBulkFinishedPurchaseOrderRow();">Add </button>
                                    </td>
                                </tr>
                            </thead>
                        </table>            
                    </div>    
                   
                </form>
                
                <div class="separator-10"></div>
                <div id="purchase_order_rows_list"></div>
                
                <!--<div class="form-group row  elem-hidden submit-data-row">
                    <label for="other cost" class="col-sm-2 col-form-label">Fabric Quantity:</label>
                    <div class="col-sm-1">
                        <input type="text" class="form-control" id="quantity" name="quantity" placeholder="Fabric Quantity" >
                    </div>
                    <div class="col-sm-1">
                        <select class="form-control" name="unit_id" id="unit_id">
                            <option value="">Unit</option>
                            @for($i=0;$i<count($unit_list);$i++)
                                <option value="{{$unit_list[$i]['id']}}">{{$unit_list[$i]['code']}}</option>
                            @endfor  
                        </select>  
                    </div>
                </div>-->
                
                <div class="form-group row elem-hidden submit-data-row">
                    <label for="other cost" class="col-sm-2 col-form-label">Vendor:</label>
                    <div class="col-sm-2">
                        <select class="form-control" name="vendor_id" id="vendor_id">
                            <option value="">Select Vendor</option>
                            @for($i=0;$i<count($vendor_list);$i++)
                                <option value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}} ({{$vendor_list[$i]['email']}}) </option>
                            @endfor  
                        </select>    
                    </div>
                </div>
                <div class="form-group row elem-hidden submit-data-row">
                    <label for="other cost" class="col-sm-2 col-form-label">Category:</label>
                    <div class="col-sm-2">
                        <select class="form-control" name="category_id" id="category_id">
                            <option value="">Select Category</option>
                            @for($i=0;$i<count($po_category_list);$i++)
                                @if(strtolower($po_category_list[$i]['name']) == 'stitching')
                                    <option value="{{$po_category_list[$i]['id']}}">{{$po_category_list[$i]['name']}}</option>
                                @endif
                            @endfor  
                        </select>  
                    </div>
                </div>
                <div class="form-group row elem-hidden submit-data-row">
                    <label for="delivery date" class="col-sm-2 col-form-label">Delivery Date:</label>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="delivery_date" name="delivery_date" placeholder="Delivery Date" >
                    </div>
                </div>
                <div class="form-group row elem-hidden submit-data-row">
                    <label for="other cost" class="col-sm-2 col-form-label">Other Cost:</label>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="other_cost" name="other_cost" placeholder="Other Cost" onkeyup="validateNumericField(this);displayPurchaseOrderRows();">
                    </div>
                </div>
                <div class="form-group row elem-hidden submit-data-row">
                    <label for="other cost" class="col-sm-2 col-form-label">Other Comments:</label>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="other_comments" name="other_comments" placeholder="Other Comments">
                    </div>
                </div>
                
                <div class="form-group row elem-hidden submit-data-row">
                    <label for="other cost" class="col-sm-2 col-form-label"></label>
                    <div class="col-sm-2">
                        <button type="button" id="create_purchase_order_submit" name="create_purchase_order_submit" class="btn btn-dialog" onclick="createBulkFinishedPurchaseOrder();">Submit</button>
                        <div id="create_purchase_order_spinner_1" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="confirm_delete_row_item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteRowErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="deleteRowSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Style<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-row-item-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_style_row_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="delete_style_row_btn" name="delete_style_row_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js?v=1.45') }}" ></script>
<script>
    var size_list = [];
    @for($i=0;$i<count($size_list);$i++)
        size_list.push([{{$size_list[$i]['id']}},"{{$size_list[$i]['size']}}"]);
    @endfor
    
</script>    
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('#delivery_date').datepicker({format: 'dd/mm/yyyy',startDate: '-0d'});</script>
@endsection
