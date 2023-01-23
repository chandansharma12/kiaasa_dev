@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'purchaser/dashboard'),array('name'=>'Design List','link'=>'purchaser/design-list'),array('name'=>'Quotation by SKU')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Quotation by SKU'); ?>


    <section class="product_area">
        <div class="container-fluid" >
            <div id="designListErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="designList">
                <a href="javascript:;" onclick="requestSKUQuotation();" class="btn btn-dialog pull-right">Request Quotation</a>
                <div class="separator-10">&nbsp;</div>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th colspan="10">Fabric</th></tr></thead>
                        <thead><tr class="header-tr"><th><input type="checkbox" name="chk_fabric_id_all" id="chk_fabric_id_all" value="1" class="fabric_id_all-chk" onclick="checkAllCheckboxes(this,'fabric_id');" > </th>
                            <th>Name</th><th>Color</th><th>Width</th><th></th><th>Quantity</th><th>Purchase Quantity</th></thead></tr>
                            <?php $i=0 ?>
                            @foreach($quotation_fabric as $id=>$data) 
                                <tr>
                                    <td><input type="checkbox" name="chk_fabric_id_{{$id}}" id="chk_fabric_id_{{$id}}" value="{{$data['name']}}__{{$data['color']}}__{{$data['width']}}__{{$data['unit']}}__{{$id}}" class="fabric_id-chk"> </td>
                                    <td>{{$data['name']}}</td>
                                    <td>{{$data['color']}}</td>
                                    <td>{{$data['width']}} {{$data['unit']}}</td>
                                    <td></td>
                                    <td>{{$data['peice_data']}}</td>
                                    <td><input type="text" name="fabric_qty_{{$id}}" id="fabric_qty_{{$id}}" class="form-control" value="{{$data['peice_data']}}" style="width:80px;border:1px solid #ccc;"></td>
                                </tr>
                                <?php $i++ ?>
                            @endforeach

                            <thead><tr class="header-tr"><th colspan="10">Accessories</th></tr></thead>
                            <thead><tr class="header-tr"><th><input type="checkbox" name="chk_acc_id_all" id="chk_acc_id_all" value="1" class="acc_id_all-chk" onclick="checkAllCheckboxes(this,'acc_id');" > </th>
                            <th>Category</th><th>Sub Category</th><th>Color</th><th>Size</th><th>Quantity</th><th>Purchase Quantity</th></tr></thead>
                            <?php $i=0 ?>
                            @foreach($quotation_accessories as $id=>$data)
                                <tr>
                                    <td><input type="checkbox" name="chk_acc_id_{{$id}}" id="chk_acc_id_{{$id}}" value="{{$data['category']}}__{{$data['subcategory']}}__{{$data['color']}}__{{$data['size']}}__{{$id}}" class="acc_id-chk"> </td>
                                    <td>{{$data['category']}}</td>
                                    <td>{{$data['subcategory']}}</td>
                                    <td>{{$data['color']}}</td>
                                    <td>{{$data['size']}}</td>
                                    <td>{{$data['peice_data']}}</td>
                                    <td><input type="text" name="acc_qty_{{$id}}" id="acc_qty_{{$id}}" class="form-control" value="{{$data['peice_data']}}" style="width:80px;border:1px solid #ccc;"></td>
                                </tr>
                                <?php $i++ ?>
                            @endforeach

                            <thead><tr class="header-tr"><th colspan="10">Process</th></tr></thead>
                            <thead><tr class="header-tr"><th><input type="checkbox" name="chk_process_id_all" id="chk_process_id_all" value="1" class="process_id_all-chk" onclick="checkAllCheckboxes(this,'process_id');" > </th>
                                    <th>Category</th><th>Type</th><th></th><th>Quantity</th><th>Purchase Quantity</th><th></th></tr></thead>
                            <?php $i=0 ?>
                            @foreach($quotation_process as $id=>$data)
                                <tr>
                                    <td><input type="checkbox" name="chk_process_id_{{$id}}" id="chk_process_id_{{$id}}" value="{{$data['category']}}__{{$data['type']}}__{{$id}}" class="process_id-chk"> </td>
                                    <td>{{$data['category']}}</td>
                                    <td>{{$data['type']}}</td>
                                    <td></td>
                                    <td>{{$data['peice_data']}}</td>
                                    <td><input type="text" name="process_qty_{{$id}}" id="process_qty_{{$id}}" class="form-control" value="{{$data['peice_data']}}" style="width:80px;border:1px solid #ccc;"></td>
                                    <td></td>
                                </tr>
                                <?php $i++ ?>
                            @endforeach
                    </table>        
                    <?php /* ?><table class="table table-striped ">
                        <tr><th colspan="10">Fabric</th></tr>
                        <tr><th><input type="checkbox" name="chk_design_id_all" id="chk_design_id_all" value="1" class="design_id-chk" onclick="checkAllCheckboxes(this,'design_id');" > </th>
                        <th>Name</th><th>Quality</th><th>Color</th><th>Quantity</th><th>Purchase Quantity</th></tr>
                            @for($i=0;$i<count($design_items_fabric);$i++)
                                <tr>
                                    <td><input type="checkbox" name="chk_design_id_{{$i}}" id="chk_design_id_{{$i}}" value="{{$i}}" class="design_id-chk"> </td>
                                    <td>{{$design_items_fabric[$i]->name_id_name}}</td>
                                    <td>{{$design_items_fabric[$i]->quality_id_name}}</td>
                                    <td>{{$design_items_fabric[$i]->color_id_name}}</td>
                                    <td>{{$design_items_fabric[$i]->cnt}}</td>
                                    <td><input type="text" class="form-control" style="width:80px;border:1px solid #ccc;"></td>

                                </tr>
                            @endfor

                            <tr><th colspan="10">Accessories</th></tr>
                            <tr><th><input type="checkbox" name="chk_design_id_all" id="chk_design_id_all" value="1" class="design_id-chk" onclick="checkAllCheckboxes(this,'design_id');" > </th>
                            <th>Category</th><th></th><th>Color</th><th>Quantity</th><th>Purchase Quantity</th></tr>
                            @for($i=0;$i<count($design_items_accessories);$i++)
                                <tr>
                                    <td><input type="checkbox" name="chk_design_id_{{$i}}" id="chk_design_id_{{$i}}" value="{{$i}}" class="design_id-chk"> </td>
                                    <td>{{$design_items_accessories[$i]->category_id_name}}</td>
                                    <td></td>
                                    <td>{{$design_items_accessories[$i]->color_id_name}}</td>
                                    <td>{{$design_items_accessories[$i]->cnt}}</td>
                                   <td><input type="text" class="form-control" style="width:80px;border:1px solid #ccc;"></td>
                                </tr>
                            @endfor

                            <tr><th colspan="10">Process</th></tr>
                            <tr><th><input type="checkbox" name="chk_design_id_all" id="chk_design_id_all" value="1" class="design_id-chk" onclick="checkAllCheckboxes(this,'design_id');" > </th>
                            <th>Category</th><th></th><th>Type</th><th>Quantity</th><th>Purchase Quantity</th></tr>
                            @for($i=0;$i<count($design_items_process);$i++)
                                <tr>
                                    <td><input type="checkbox" name="chk_design_id_{{$i}}" id="chk_design_id_{{$i}}" value="{{$i}}" class="design_id-chk"> </td>
                                    <td>{{$design_items_process[$i]->category_id_name}}</td>
                                    <td></td>
                                    <td>{{$design_items_process[$i]->type_id_name}}</td>
                                    <td>{{$design_items_process[$i]->items_count}}</td>
                                    <td><input type="text" class="form-control" style="width:80px;border:1px solid #ccc;"></td>
                                </tr>
                            @endfor

                            <tr><th colspan="10">Packaging sheet</th></tr>
                            <tr><th><input type="checkbox" name="chk_design_id_all" id="chk_design_id_all" value="1" class="design_id-chk" onclick="checkAllCheckboxes(this,'design_id');" > </th>
                            <th>Name</th><th></th><th></th><th>Quantity</th><th>Purchase Quantity</th></tr>
                            @for($i=0;$i<count($design_items_packaging_sheet);$i++)
                                <tr>
                                    <td><input type="checkbox" name="chk_design_id_{{$i}}" id="chk_design_id_{{$i}}" value="{{$i}}" class="design_id-chk"> </td>
                                    <td>{{$design_items_packaging_sheet[$i]->name_id_name}}</td>
                                    <td></td>
                                    <td></td>
                                    <td>{{$design_items_packaging_sheet[$i]->items_count}}</td>
                                    <td><input type="text" class="form-control" style="width:80px;border:1px solid #ccc;"></td>
                                </tr>
                            @endfor
                    </table> <?php */ ?>
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
<script src="{{ asset('js/purchaser.js') }}" ></script>
@endsection
