@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Products Inventory List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Products Inventory List'); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            @if($user->user_type != 9)
                <form method="get">
                    <div class="row justify-content-end" >
                        <div class="col-md-1" >
                            <input type="text" name="inv_id" id="inv_id" class="form-control" placeholder="ID" value="{{request('inv_id')}}" />
                        </div>
                        <div class="col-md-2" >
                            <select name="store_id" id="store_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor    
                            </select>
                        </div>
                        <div class="col-md-2" >
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                @foreach($status_list as $id=>$status_name)
                                    <?php $sel = ($id == request('status') && request('status') != '')?'selected':''; ?>
                                    <option {{$sel}} value="{{$id}}">{{$status_name}}</option>
                                @endforeach    
                            </select>
                        </div>
                        <div class="col-md-1" >
                            <select name="qc_status" id="qc_status" class="form-control">
                                <option value="">QC Status</option>
                                <?php $qc_status_list = array('0'=>'Pending','1'=>'Accepted','2'=>'Defective') ?>
                                @foreach($qc_status_list as $id=>$status_name)
                                    <?php $sel = (request('qc_status') != '' && $id == request('qc_status'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$id}}">{{$status_name}}</option>
                                @endforeach    
                            </select>
                        </div>
                        <div class="col-md-2" >
                            <input type="text" name="prod_name_search" id="prod_name_search" class="form-control" placeholder="Prod Name/Barcode/SKU" value="{{request('prod_name_search')}}">
                        </div>
                        <div class="col-md-1" >
                            <select name="v_id" id="v_id"  class="form-control search-select-1">
                                <option value="">Vendor</option>
                                @for($i=0;$i<count($vendor_list);$i++)
                                    <?php if($vendor_list[$i]['id'] == request('v_id')) $sel = 'selected';else $sel = ''; ?>    
                                    <option {{$sel}} value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                                @endfor    
                            </select>
                        </div>
                        <div class="col-md-1" >
                            <select name="size_search" id="size_search"  class="form-control" >
                                <option value="">Size</option>
                                @for($i=0;$i<count($size_list);$i++)
                                    <?php if($size_list[$i]['id'] == request('size_search')) $sel = 'selected';else $sel = ''; ?>    
                                    <option {{$sel}} value="{{$size_list[$i]['id']}}">{{$size_list[$i]['size']}}</option>
                                @endfor    
                            </select>
                        </div>
                        <div class="col-md-1" >
                            <select name="color_search" id="color_search"  class="form-control search-select-1">
                                <option value="">Color</option>
                                @for($i=0;$i<count($color_list);$i++)
                                    <?php if($color_list[$i]['id'] == request('color_search')) $sel = 'selected';else $sel = ''; ?>    
                                    <option {{$sel}} value="{{$color_list[$i]['id']}}">{{$color_list[$i]['name']}}</option>
                                @endfor    
                            </select>
                        </div>

                        <div class="col-md-1" >
                            <select name="category_search" id="category_search"  class="form-control search-select-1" onchange="getPosProductSubcategories(this.value,'search');">
                                <option value="">Category</option>
                                @for($i=0;$i<count($category_list);$i++)
                                    <?php if($category_list[$i]['id'] == request('category_search')) $sel = 'selected';else $sel = ''; ?>    
                                    <option {{$sel}} value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                                @endfor    
                            </select>
                        </div>
                    </div>

                    <div class="clear">&nbsp;</div>

                    <div class="row " >
                        <div class="col-md-2" >
                            <select name="product_subcategory_search" id="product_subcategory_search"  class="form-control search-select-1" >
                                <option value="">Sub Category</option>
                            </select>
                        </div>
                        <div class="col-md-2" >
                            <input name="po_search" id="po_search" class="form-control" placeholder="PO ID/No" value="{{request('po_search')}}" />
                        </div>
                        <div class="col-md-1" >
                            <select name="inv_type" id="inv_type"  class="form-control " >
                                <option value="">-- All Inventory --</option>
                                <option <?php if(request('inv_type') == 1) echo 'selected'; ?> value="1">{{CommonHelper::getInventoryType(1)}}</option>
                                <option <?php if(request('inv_type') == 2) echo 'selected'; ?> value="2">{{CommonHelper::getInventoryType(2)}}</option>
                            </select>
                        </div>
                        <div class="col-md-1" >
                            <select name="payment_status" id="payment_status"  class="form-control " >
                                <option value="">-- Payment Status --</option>
                                <option <?php if(request('payment_status') != '' && request('payment_status') == 0) echo 'selected'; ?> value="0">{{CommonHelper::getInventoryPaymentStatusText(0)}}</option>
                                <option <?php if(request('payment_status') == 1) echo 'selected'; ?> value="1">{{CommonHelper::getInventoryPaymentStatusText(1)}}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group input-daterange">
                                <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Added Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}} @endif" autocomplete="off">
                                <div class="input-group-addon" style="margin-top:10px;">to</div>
                                <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="Added End Date" value="@if(!empty(request('endDate'))){{request('endDate')}} @endif" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-1" >
                            <input name="mrp_search" id="mrp_search" class="form-control" placeholder="MRP" value="{{request('mrp_search')}}" />
                        </div>
                        <div class="col-md-1" >
                            <input name="demand_search" id="demand_search" class="form-control" placeholder="Demand Invoice" value="{{request('demand_search')}}" />
                        </div>
                        <div class="col-md-1" style="margin-top:12px; ">
                            <input name="sku_size_sort" id="sku_size_sort"  type="checkbox" value="1" <?php if(request('sku_size_sort') == 1) echo 'checked'; ?> /> SKU Size Sort
                        </div>
                        <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>
                    </div>

                    @if(in_array($user->user_type,array(1,6,15)))
                        <div class="clear">&nbsp;</div>
                        <div class="row " >
                            <div class="col-md-6" >
                                <input type="checkbox" name="no_discount_chk" id="no_discount_chk" value="1"> No Discount
                                <input type="button" name="get_barcodes_btn" id="get_barcodes_btn" value="Barcodes" class="btn btn-dialog" onclick="getInventoryBarcodes('barcodes');">&nbsp;&nbsp;
                                <input type="button" name="get_barcodes_btn" id="get_barcodes_btn" value="QR Codes" class="btn btn-dialog" onclick="getInventoryBarcodes('qrcodes');">&nbsp;&nbsp;
                                <input type="button" name="get_barcodes_btn" id="get_barcodes_btn" value="BarQR Codes" class="btn btn-dialog" onclick="getInventoryBarcodes('barqrcodes');">
                                <input type="button" name="get_barcodes_btn" id="get_barcodes_btn" value="Jewellery QR Codes" class="btn btn-dialog" onclick="getInventoryBarcodes('jewelleryqrcodes');">
                            </div>
                            
                            @if($user->user_type != 15)
                                <div class="col-md-1">
                                    <a href="javascript:;" onclick="downloadInventory();" class="btn btn-dialog" title="Download Inventory CSV"><i title="Download Inventory CSV" class="fa fa-download fas-icon" ></i> </a>
                                </div>
                            @endif
                        </div>    
                    @endif

                </form> 
            @endif
            
            @if($user->user_type == 9)
                <form method="get">
                    <div class="row justify-content-end" >
                        <div class="col-md-2" >
                            <select name="store_id" id="store_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                <option <?php if(request('store_id') == $store_data->id) echo 'selected'; ?> value="{{$store_data->id}}">{{$store_data->store_name}} ({{$store_data->store_id_code}})</option>
                            </select>
                        </div>
                        <div class="col-md-1" >
                            <input type="text" name="inv_id" id="inv_id" class="form-control" placeholder="ID" value="{{request('inv_id')}}" />
                        </div>
                        <div class="col-md-2" >
                            <input type="text" name="prod_name_search" id="prod_name_search" class="form-control" placeholder="Prod Name/Barcode/QRCode/SKU" value="{{request('prod_name_search')}}">
                        </div>
                        <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>
                        <div class="col-md-1">
                            <a href="javascript:;" onclick="downloadInventory();" class="btn btn-dialog" title="Download Inventory CSV"><i title="Download Inventory CSV" class="fa fa-download fas-icon" ></i> </a>
                        </div>
                    </div>
                </form> 
            @endif
            
            <div class="clear">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size: 12px;">
                        <thead>
                            <tr class="header-tr">
                            <th>
                                @if(in_array($user->user_type,array(1,6,15)))
                                    <input type="checkbox" name="pos_product_list_all" id="pos_product_list_all"  value="1" onclick="checkAllCheckboxes(this,'pos_product-list');">
                                @endif
                                <?php echo CommonHelper::getSortLink('ID','id','pos/product/inventory/list',true,'ASC'); ?>
                            </th>    
                            <th><?php echo CommonHelper::getSortLink('Prod','product_name','pos/product/inventory/list'); ?></th>
                            <th>Store</th>
                            <th><?php echo CommonHelper::getSortLink('QR Code','piece_barcode','pos/product/inventory/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Barcode','product_barcode','pos/product/inventory/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('SKU','sku','pos/product/inventory/list'); ?></th>
                            @if($user->user_type != 9)
                                <th><?php echo CommonHelper::getSortLink('PO','po','pos/product/inventory/list'); ?></th>
                            @endif
                            <th>Cat</th>
                            <th>Sub Cat</th>
                            @if($user->user_type != 9)
                                <th><?php echo CommonHelper::getSortLink('Base Price','base_price','pos/product/inventory/list'); ?></th>
                            @endif
                            <th><?php echo CommonHelper::getSortLink('MRP','sale_price','pos/product/inventory/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Status','status','pos/product/inventory/list'); ?></th>
                            @if($user->user_type != 9)
                                <th><?php echo CommonHelper::getSortLink('QC','qc_status','pos/product/inventory/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Payment','payment_status','pos/product/inventory/list'); ?></th>
                                @if($user->user_type != 15)
                                    <th><?php echo CommonHelper::getSortLink('Det','details','pos/product/inventory/list'); ?></th>
                                @endif
                            @endif
                        </tr>
                    </thead>
                        <tbody>
                            <?php  ?>
                            @for($i=0;$i<count($products_list);$i++)
                                <tr>
                                    <td>
                                        @if(in_array($user->user_type,array(1,6,15)))
                                            <input type="checkbox" name="pos_product_list" id="pos_product_list_{{$products_list[$i]->id}}" class="pos_product-list-chk" value="{{$products_list[$i]->id}}"> 
                                        @endif
                                        {{$products_list[$i]->id}}
                                    </td>
                                    <td>{{$products_list[$i]->product_name}} {{$size_id_list[$products_list[$i]->size_id]}} {{$color_id_list[$products_list[$i]->color_id]}}</td>
                                    <td>@if(!empty($products_list[$i]->store_id)) {{$store_id_list[$products_list[$i]->store_id]['store_name']}} ({{$store_id_list[$products_list[$i]->store_id]['store_id_code']}}) @endif</td>
                                    <td>{{$products_list[$i]->peice_barcode}}</td>
                                    <td>{{$products_list[$i]->product_barcode}}</td>
                                    <td>{{$products_list[$i]->product_sku}}</td>
                                    @if($user->user_type != 9)<td>@if(isset($po_id_list[$products_list[$i]->po_id])) {{$po_id_list[$products_list[$i]->po_id]}} @endif</td>@endif
                                    <td>@if(isset($category_id_list[$products_list[$i]->category_id])) {{$category_id_list[$products_list[$i]->category_id]}} @endif</td>
                                    <td>@if(isset($subcategory_id_list[$products_list[$i]->subcategory_id])) {{$subcategory_id_list[$products_list[$i]->subcategory_id]}} @endif</td>
                                    @if($user->user_type != 9)<td>{{$products_list[$i]->base_price}}</td>@endif
                                    <td>{{$products_list[$i]->sale_price}}</td>
                                    <td>@if($products_list[$i]->product_status == 0) WAREHOUSE IN PENDING @else {{strtoupper(CommonHelper::getposProductStatusName($products_list[$i]->product_status))}} @endif </td>
                                    @if($user->user_type != 9)
                                        <td>@if($products_list[$i]->product_status != 0) {{strtoupper(CommonHelper::getProductInventoryQCStatusName($products_list[$i]->qc_status))}} @endif</td>
                                        <td>{{CommonHelper::getInventoryPaymentStatusText($products_list[$i]->payment_status)}}</td>
                                        @if($user->user_type != 15)
                                            <td><a href="{{url('pos/product/inventory/detail/'.$products_list[$i]->id)}}" ><i title="Inventory Details" class="far fa-eye"></i></a></td>
                                        @endif
                                    @endif
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $products_list->withQueryString()->links() }} <p>Displaying {{$products_list->count()}} of {{ $products_list->total() }} products.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="editInventoryDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
               
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editInventoryErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="editInventorySuccessMessage"></div>
                
                <form method="post" name="editInventoryForm" id="editInventoryForm">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Purchase Order</label>
                            <select name="po_id_edit_inv" id="po_id_edit_inv"  class="form-control" >
                                <option value="">Select</option>
                                @for($i=0;$i<count($po_list);$i++)
                                    <option value="{{$po_list[$i]['id']}}">{{$po_list[$i]['order_no']}}  ({{date('d M Y',strtotime($po_list[$i]['created_at']))}})</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_po_id_edit_inv"></div>
                        </div>
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" name="sku_edit_inv" id="sku_edit_inv" class="form-control" placeholder="SKU" value="">
                            <div class="invalid-feedback" id="error_validation_sku_edit_inv"></div>
                        </div>
                        <div class="form-group" >
                            <label>MRP</label>
                            <input type="text" name="mrp_edit_inv" id="mrp_edit_inv" class="form-control" placeholder="MRP" value="">
                            <div class="invalid-feedback" id="error_validation_mrp_edit_inv"></div>
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="edit_inventory_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button name="editInventoryCancel" id="editInventoryCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="editInventoryBtn" id="editInventoryBtn" value="Update" class="btn btn-dialog" onclick="submitEditInventory();">Update</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="downloadInventoryDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Download Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadInventoryErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadInventorySuccessMessage"></div>
                <form method="post" name="downloadInventoryForm" id="downloadInventoryForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Inventory Records</label>
                                <select name="inv_count" id="inv_count" class="form-control" >
                                    <option value="">--Inventory Records--</option>
                                        @for($i=0;$i<=$products_list_count;$i=$i+50000) 
                                            <?php $start = $i+1; $end = $i+50000; ?>
                                            <?php $end = ($end < $products_list_count)?$end:$products_list_count; ?>
                                            <option value="{{$start}}_{{$end}}">{{$start}} - {{$end}}</option>
                                        @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_inv_count"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                
                <div class="modal-footer center-footer">
                    <div id="downloadInventory_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button name="downloadInventoryCancel" id="downloadInventoryCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="downloadInventoryBtn" id="downloadInventoryBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadInventory();">Download</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.1') }}" ></script>
<script src="{{ asset('js/pos_product.js?v=2.2') }}" ></script>
@if(!empty(request('category_search')))
    <script type="text/javascript">
        getPosProductSubcategories({{request('category_search')}},'search',"{{request('product_subcategory_search')}}");
    </script>        
@endif
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
@endsection