@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Raw Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Raw Report'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="v_id" id="v_id" class="form-control">
                            <option value="">Vendor</option>
                            @foreach($vendors as $id=>$vendor_data)
                                <?php $sel = ($id == request('v_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$vendor_data['name']}}</option>
                            @endforeach
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
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input autocomplete="off" type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}} {{-- @else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}} --}} @endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input autocomplete="off" type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}} {{-- @else{{date('d-m-Y')}} --}} @endif">
                        </div>
                    </div>
                    
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Raw Inventory'); ?></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div style="width:9900px">&nbsp;</div>
                <div class="table-responsive table-filter" style="width:9880px;">
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size: 12px;">
                        <thead><tr class="header-tr">
                            <th>Inventory ID</th>  
                            <th>QR Code</th>  
                            <th>Status</th>
                            <th>Inventory Type</th>
                            <th>Date Added</th>
                            <th>Current Store</th>
                            <th>Current Demand</th>
                            <th>Demand Type</th>
                            <th>From Store</th>
                            <th>To Store</th>
                            <th>Dem Status</th>
                            <th>Dem Created On</th>
                            <th>Dem Created by</th>
                            <th>Dem Store Base Rate</th>
                            <th>Dem Store GST%</th>
                            <th>Dem Store GST Amt</th>
                            <th>Dem Store Cost Price</th>
                            <th>Dem Comments</th>
                            <th>Dem Boxes Count</th>
                            <th>Dem Transporter Name</th>
                            <th>Dem Transporter GST</th>
                            <th>Dem Docket No</th>
                            <th>Dem Eway Bill No</th>
                            <th>Dem LR No</th>
                            <th>Vendor</th>
                            <th>Purchase Order</th>
                            <th>Created On</th>
                            <th>Created by</th>
                            <th>PO Invoice No</th>
                            <th>GRN No</th>
                            <th>QC Status</th>
                            <th>Product SKU</th>
                            <th>Vendor SKU</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Color</th>
                            <th>Size</th>
                            <th>HSN Code</th>
                            <th>Product Type</th>
                            <th>Product Barcode</th>
                            <th>Description</th>
                            <th>Created On</th>
                            <th>Updated On</th>
                            <th>Vendor Rate</th>
                            <th>Vendor GST %</th>
                            <th>Vendor GST Amount</th>
                            <th>Cost Price</th>
                            <th>Sale Price (MRP)</th>
                            <th>Store Base Rate</th>
                            <th>Store GST %</th>
                            <th>Store GST Amount</th>
                            <th>Store Cost Price</th>
                            <th>Warehouse Intake Date</th>
                            <th>Store Assign Date</th>
                            <th>Store Intake Date</th>
                            <th>Store Sale Date</th>
                            <th>Record Last Updated</th>
                            <th>Store POS Order No</th>
                            <th>Order Total Price</th>
                            <th>Order Total Items</th>
                            <th>Order Store</th>
                            <th>Order Customer Name</th>
                            <th>Order Customer Phone</th>
                            <th>Order Created By</th>
                            <th>Order Bags</th>
                            <th>Order Product MRP</th>
                            <th>Order Product Discount %</th>
                            <th>Order Product Discount Amount</th>
                            <th>Order Product GST %</th>
                            <th>Order Product GST Amount</th>
                            <th>Order Product Net Price</th>
                            <th>Order Product Staff Name</th>
                            <th>Order Date</th>
                        </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($inventory_list);$i++)
                                <tr>
                                    <td>{{$inventory_list[$i]->id}}</td>
                                    <td>{{$inventory_list[$i]->peice_barcode}}</td>
                                    <td>{{CommonHelper::getposProductStatusName($inventory_list[$i]->product_status)}}</td>
                                    <td>{{($inventory_list[$i]->arnon_inventory == 0)?CommonHelper::getInventoryType(1):CommonHelper::getInventoryType(2)}}</td>
                                    <td>{{date('d-m-Y',strtotime($inventory_list[$i]->created_at))}}</td>
                                    <td>{{isset($stores[$inventory_list[$i]->store_id])?$stores[$inventory_list[$i]->store_id]['store_name']:''}}</td>
                                    <td>{{$inventory_list[$i]->demand_invoice_no}}</td>
                                    <td>{{CommonHelper::getDemandTypeText($inventory_list[$i]->demand_type)}}</td>
                                    <td>{{isset($stores[$inventory_list[$i]->from_store_id])?$stores[$inventory_list[$i]->from_store_id]['store_name']:''}}</td>
                                    <td>{{isset($stores[$inventory_list[$i]->to_store_id])?$stores[$inventory_list[$i]->to_store_id]['store_name']:''}}</td>
                                    <td>{{CommonHelper::getDemandStatusText($inventory_list[$i]->demand_type,$inventory_list[$i]->demand_status)}}</td>
                                    <td>{{(!empty($inventory_list[$i]->demand_created_at))?date('d-m-Y',strtotime($inventory_list[$i]->demand_created_at)):''}}</td>
                                    <td>{{(!empty($inventory_list[$i]->demand_created_by))?$users[$inventory_list[$i]->demand_created_by]->name:''}}</td>
                                    <td>{{$inventory_list[$i]->spdi_store_base_rate}}</td>
                                    <td>{{$inventory_list[$i]->spdi_store_gst_percent}}</td>
                                    <td>{{$inventory_list[$i]->spdi_store_gst_amount}}</td>
                                    <td>{{$inventory_list[$i]->spdi_store_base_price}}</td>
                                    <td>{{substr($inventory_list[$i]->demand_comments,0,25)}}</td>
                                    <td>{{$inventory_list[$i]->boxes_count}}</td>
                                    <td>{{$inventory_list[$i]->transporter_name}}</td>
                                    <td>{{$inventory_list[$i]->transporter_gst}}</td>
                                    <td>{{$inventory_list[$i]->docket_no}}</td>
                                    <td>{{$inventory_list[$i]->eway_bill_no}}</td>
                                    <td>{{$inventory_list[$i]->lr_no}}</td>
                                    <td>{{isset($vendors[$inventory_list[$i]->vendor_id])?$vendors[$inventory_list[$i]->vendor_id]['name']:''}}</td>
                                    <td>{{isset($po_list[$inventory_list[$i]->po_id])?$po_list[$inventory_list[$i]->po_id]->order_no:''}}</td>
                                    <td>{{isset($po_list[$inventory_list[$i]->po_id])?date('d-m-Y',strtotime($po_list[$inventory_list[$i]->po_id]->created_at)):''}}</td>
                                    <td>{{(isset($po_list[$inventory_list[$i]->po_id]) && isset($users[$po_list[$inventory_list[$i]->po_id]->user_id]))?$users[$po_list[$inventory_list[$i]->po_id]->user_id]->name:''}}</td>
                                    <td>{{$inventory_list[$i]->pod_invoice_no}}</td>
                                    <td>{{$inventory_list[$i]->grn_no}}</td>
                                    <td>{{CommonHelper::getProductInventoryQCStatusName($inventory_list[$i]->qc_status)}}</td>
                                    <td>{{!empty($inventory_list[$i]->vendor_sku)?$inventory_list[$i]->vendor_sku:$inventory_list[$i]->product_sku}}</td>
                                    <td>{{$inventory_list[$i]->vendor_product_sku}}</td>
                                    <td>{{$inventory_list[$i]->product_name}}</td>
                                    <td>{{isset($design_items[$inventory_list[$i]->category_id])?$design_items[$inventory_list[$i]->category_id]['name']:''}}</td>
                                    <td>{{isset($design_items[$inventory_list[$i]->subcategory_id])?$design_items[$inventory_list[$i]->subcategory_id]['name']:''}}</td>
                                    <td>{{isset($design_items[$inventory_list[$i]->color_id])?$design_items[$inventory_list[$i]->color_id]['name']:''}}</td>
                                    <td>{{isset($size_list[$inventory_list[$i]->size_id])?$size_list[$inventory_list[$i]->size_id]['size']:''}}</td>
                                    <td>{{$inventory_list[$i]->hsn_code}}</td>
                                    <td>{{($inventory_list[$i]->arnon_product == 0)?CommonHelper::getInventoryType(1):CommonHelper::getInventoryType(2)}}</td>
                                    <td>{{$inventory_list[$i]->product_barcode}}</td>
                                    <td>{{substr($inventory_list[$i]->product_description,0,25)}}</td>
                                    <td>{{(!empty($inventory_list[$i]->product_created_at))?date('d-m-Y',strtotime($inventory_list[$i]->product_created_at)):''}}</td>
                                    <td>{{(!empty($inventory_list[$i]->product_updated_at))?date('d-m-Y',strtotime($inventory_list[$i]->product_updated_at)):''}}</td>
                                    <td>{{$inventory_list[$i]->vendor_base_price}}</td>
                                    <td>{{$inventory_list[$i]->vendor_gst_percent}}</td>
                                    <td>{{$inventory_list[$i]->vendor_gst_amount}}</td>
                                    <td>{{$inventory_list[$i]->base_price}}</td>
                                    <td>{{$inventory_list[$i]->sale_price}}</td>
                                    <td>{{$inventory_list[$i]->store_base_rate}}</td>
                                    <td>{{$inventory_list[$i]->store_gst_percent}}</td>
                                    <td>{{$inventory_list[$i]->store_gst_amount}}</td>
                                    <td>{{$inventory_list[$i]->store_base_price}}</td>
                                    <td>{{(!empty($inventory_list[$i]->intake_date))?date('d-m-Y',strtotime($inventory_list[$i]->intake_date)):''}}</td>
                                    <td>{{(!empty($inventory_list[$i]->store_assign_date))?date('d-m-Y',strtotime($inventory_list[$i]->store_assign_date)):''}}</td>
                                    <td>{{(!empty($inventory_list[$i]->store_intake_date))?date('d-m-Y',strtotime($inventory_list[$i]->store_intake_date)):''}}</td>
                                    <td>{{(!empty($inventory_list[$i]->store_sale_date))?date('d-m-Y',strtotime($inventory_list[$i]->store_sale_date)):''}}</td>
                                    <td>{{(!empty($inventory_list[$i]->updated_at))?date('d-m-Y',strtotime($inventory_list[$i]->updated_at)):''}}</td>
                                    <td>{{$inventory_list[$i]->pos_order_no}}</td>
                                    <td>{{!empty($inventory_list[$i]->pos_total_price)?round($inventory_list[$i]->pos_total_price,2):''}}</td>
                                    <td>{{$inventory_list[$i]->pos_total_items}}</td>
                                    <td>{{isset($stores[$inventory_list[$i]->pco_store_id])?$stores[$inventory_list[$i]->pco_store_id]['store_name']:''}}</td>
                                    <td>{{$inventory_list[$i]->salutation}} {{$inventory_list[$i]->customer_name}}</td>
                                    <td>{{$inventory_list[$i]->customer_phone}}</td>
                                    <td>{{!empty($inventory_list[$i]->store_user_id)?$users[$inventory_list[$i]->store_user_id]->name:''}}</td>
                                    <td>{{$inventory_list[$i]->bags_count}}</td>
                                    <td>{{!empty($inventory_list[$i]->pcod_sale_price)?round($inventory_list[$i]->pcod_sale_price,2):''}}</td>
                                    <td>{{!empty($inventory_list[$i]->pcod_discount_percent)?round($inventory_list[$i]->pcod_discount_percent,2).'%':''}}</td>
                                    <td>{{!empty($inventory_list[$i]->pcod_discount_amount)?round($inventory_list[$i]->pcod_discount_amount,2):''}}</td>
                                    <td>{{!empty($inventory_list[$i]->pcod_gst_percent)?round($inventory_list[$i]->pcod_gst_percent,2).'%':''}}</td>
                                    <td>{{!empty($inventory_list[$i]->pcod_gst_amount)?round($inventory_list[$i]->pcod_gst_amount,2):''}}</td>
                                    <td>{{!empty($inventory_list[$i]->pcod_net_price)?round($inventory_list[$i]->pcod_net_price,2):''}}</td>
                                    <td>{{$inventory_list[$i]->store_staff_name}}</td>
                                    <td>{{(!empty($inventory_list[$i]->pco_created_at))?date('d-m-Y',strtotime($inventory_list[$i]->pco_created_at)):''}}</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <p>{{ $inventory_list->withQueryString()->links() }} <p>Displaying {{$inventory_list->count()}} of {{ $inventory_list->total() }} inventory.</p>
                </div>
            </div>
        </div>
    </section>

    <?php echo CommonHelper::displayDownloadDialogHtml($inventory_list->total(),50000,'/inventory/report/raw','Download Raw Inventory','Raw Inventory'); ?>
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
