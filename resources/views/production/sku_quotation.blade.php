@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'production/dashboard'),array('name'=>'Design List','link'=>'production/design-list'),array('name'=>'Quotation by SKU ')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Quotation by SKU'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="designListErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="designList">
                <div class="alert alert-danger alert-dismissible" style="display:none" id="skuQuotationErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="skuQuotationSuccessMessage"></div>
                <select name="vendor_ids" id="vendor_ids" class="form-control" multiple style="border:1px solid #ccc;">
                            @for($i=0;$i<count($vendors_list);$i++)
                                <option value="{{$vendors_list[$i]['id']}}">{{$vendors_list[$i]['name']}} ({{$vendors_list[$i]['email']}})</option>
                            @endfor
                </select>    
                 <div class="separator-10">&nbsp;</div>
                
                <button onclick="requestSKUQuotation();" id="sku_quotation_btn" class="btn btn-dialog pull-right">Request Quotation</button>
                <div id="sku_quotation_spinner" style="display:none;margin-right: 15px;margin-top: 10px;" class="spinner-border spinner-border-sm text-secondary pull-right" role="status"><span class="sr-only">Loading...</span></div>
                <div class="separator-10">&nbsp;</div>
                <div class="table-responsive table-filter">
                    @for($q=0;$q<count($quotation_data);$q++) 
                        <?php $design_data = $quotation_data[$q]['design_data']; $quotation_prod_process = $quotation_data[$q]['prod_process_data']; $quotation_pack_sheet = $quotation_data[$q]['pack_sheet_data'];  ?>    
                        <table class="table table-striped admin-table" cellspacing="0">
                            <tr class="header-tr"><th colspan="10">Design SKU: {{$design_data['sku']}}, Production Count: {{$design_data['production_count']}}</th></tr>
                            <tr class="header-tr"><th colspan="10">Production Process</th></tr>
                            <tr><th><!--<input type="checkbox" name="chk_prod_process_id_all" id="chk_prod_process_id_all" value="1" class="prod_process_id_all-chk" onclick="checkAllCheckboxes(this,'prod_process_id');" > --></th>
                            <th>Name</th><th>Quantity</th><th>Purchase Quantity</th></tr>

                            @for($i=0;$i<count($quotation_prod_process);$i++) 
                                <tr>
                                    <td><input type="checkbox" name="chk_prod_process_id_{{$quotation_prod_process[$i]['id']}}" id="chk_prod_process_id_{{$quotation_prod_process[$i]['id']}}" value="{{$quotation_prod_process[$i]['design_item_id']}}_{{$quotation_prod_process[$i]['design_id']}}" class="prod_process_id-chk"> </td>
                                    <td>{{$quotation_prod_process[$i]['name_id_name']}}</td>
                                    <td>{{$quotation_prod_process[$i]['production_count']}}</td>
                                    <td><input value="{{$quotation_prod_process[$i]['production_count']}}" type="text" name="prod_process_qty_{{$quotation_prod_process[$i]['design_item_id']}}_{{$quotation_prod_process[$i]['design_id']}}" id="prod_process_qty_{{$quotation_prod_process[$i]['design_item_id']}}_{{$quotation_prod_process[$i]['design_id']}}" class="form-control" style="width:80px;border:1px solid #ccc;"></td>
                                </tr>
                            @endfor

                            <tr class="header-tr"><th colspan="10">Packaging Sheet</th></tr>
                            <tr><th><!--<input type="checkbox" name="chk_pack_sheet_id_all" id="chk_pack_sheet_id_all" value="1" class="pack_sheet_id-chk" onclick="checkAllCheckboxes(this,'pack_sheet_id');" > --></th>
                            <th>Name</th><th>Quantity</th><th>Purchase Quantity</th></tr>
                            @for($i=0;$i<count($quotation_pack_sheet);$i++) 
                                <?php if($quotation_pack_sheet[$i]['avg'] > 0): ?>
                                    <tr>
                                        <td><input type="checkbox" name="chk_pack_sheet_id_{{$quotation_pack_sheet[$i]['id']}}" id="chk_pack_sheet_id_{{$quotation_pack_sheet[$i]['id']}}" value="{{$quotation_pack_sheet[$i]['design_item_id']}}_{{$quotation_pack_sheet[$i]['design_id']}}" class="pack_sheet_id-chk"> </td>
                                        <td>{{$quotation_pack_sheet[$i]['name_id_name']}}</td>

                                        <td>{{$quotation_pack_sheet[$i]['avg']}}</td>
                                        <td><input value="{{$quotation_pack_sheet[$i]['avg']}}" type="text" name="pack_sheet_qty_{{$quotation_pack_sheet[$i]['design_item_id']}}_{{$quotation_pack_sheet[$i]['design_id']}}" id="pack_sheet_qty_{{$quotation_pack_sheet[$i]['design_item_id']}}_{{$quotation_pack_sheet[$i]['design_id']}}" class="form-control"  style="width:80px;border:1px solid #ccc;"></td>
                                    </tr>
                                <?php endif ?>
                            @endfor
                        </table>     
                    @endfor
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="error_request_quotation_sku" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteErrorMessage"></div>
                <div class="modal-body">
                    <h6>Select the records for quotation<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-dialog" id="error_request_quotation_sku_btn" name="error_request_quotation_sku_btn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="request_quotation_sku_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">SKU Quotation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="skuQuotationErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="skuQuotationSuccessMessage"></div>
                <div class="modal-body" >
                    <div class="form-group">
                        <label style="text-align: left;float: left;margin-bottom: 5px;color:#000;"><b>Vendor:</b> </label>
                        <select name="vendor_ids" id="vendor_ids" class="form-control" multiple style="border:1px solid #ccc;">
                            @for($i=0;$i<count($vendors_list);$i++)
                                <option value="{{$vendors_list[$i]['id']}}">{{$vendors_list[$i]['name']}} ({{$vendors_list[$i]['email']}})</option>
                            @endfor
                        </select>    
                        <div class="invalid-feedback" id="error_editFabric_unit_id"></div>	
                        <input type="hidden" name="fabric_id_edit_hdn" id="fabric_id_edit_hdn" value="">
                    </div>
                    <div class="separator-10"></div>
                    <div class="form-group" id="quotation_sku_data">

                    </div>
                </div>
                <div class="modal-footer center-footer">
                    <div id="sku_quotation_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" id="request_quotation_sku_cancel" name="request_quotation_sku_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dialog" id="request_quotation_sku_btn" name="request_quotation_sku_btn" onclick="confirmSKUQuotation();">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/production.js') }}" ></script>
@endsection
