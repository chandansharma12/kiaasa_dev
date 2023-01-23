@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Product Inventory List','link'=>'pos/product/inventory/list'),array('name'=>'Product Inventory Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Product Inventory Detail'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >

            <div id="updatePosProductStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updatePosProductStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <div id="posProductContainer" class="table-container">
                
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size: 13px;">
                        <tr class="header-tr">
                            <th>Inventory ID</th><th>QR Code</th><th>Status</th><th>Inventory Type</th><th>Date Added</th>
                        </tr>
                        <tr>
                            <td>{{$inv_data->id}} @if($inv_data->fake_inventory) (Fake Inventory) @endif</td><td>{{$inv_data->peice_barcode}}</td><td>{{CommonHelper::getposProductStatusName($inv_data->product_status)}}</td><td>{{($inv_data->arnon_inventory == 1)?'Arnon':'Northcorp'}}</td><td>{{date('d M Y H:i',strtotime($inv_data->created_at))}}</td>
                        </tr>
                        <tr class="header-tr">
                            <th>Current Store</th><th>Current Demand</th><th>Demand Type</th><th>Demand Route</th><th>Vendor</th>
                        </tr>
                        <tr>
                            <td>@if(!empty($inv_data->store_name)) {{$inv_data->store_name}} ({{$inv_data->store_id_code}}) @endif</td>
                            <td>
                                @if(!empty($inv_data->demand_id)) 
                                    <a href="{{url($inv_data->demand_url.$inv_data->demand_id)}}" class="table-link" >{{$inv_data->demand_invoice_no}}</a> 
                                @endif
                            </td>
                            <td>{{CommonHelper::getDemandTypeText($inv_data->demand_type)}}</td>
                            <td>{{$inv_data->demand_route}}</td>
                            <td>{{$inv_data->vendor_name}}</td>
                        </tr>
                        <tr class="header-tr">
                            <th>Purchase Order</th><th>PO Invoice No</th><th>GRN No</th><th>GRN Date</th><th>QC Status</th>
                        </tr>
                        <tr>
                            <td><a href="{{url('purchase-order/product/detail/'.$inv_data->po_id)}}" class="table-link" >{{$inv_data->po_order_no}}</a></td>
                            <td><a href="{{url('purchase-order/product/invoice/list/'.$inv_data->po_id)}}" class="table-link" >{{$inv_data->wh_invoice_no}}</a></td>
                            <td><a href="{{url('warehouse/sor/inventory/import/'.$inv_data->po_detail_id)}}" class="table-link" >{{$inv_data->grn_no}}</a></td>
                            <td>@if(!empty($inv_data->grn_date)) {{date('d M Y H:i',strtotime($inv_data->grn_date))}} @endif</td>
                            <td>@if(!empty($inv_data->po_detail_id)) <a href="{{url('warehouse/sor/inventory/qc/'.$inv_data->po_detail_id)}}" class="table-link" >@endif {{CommonHelper::getProductInventoryQCStatusName($inv_data->qc_status)}} @if(!empty($inv_data->qc_date))({{date('d M Y',strtotime($inv_data->qc_date))}}) @endif  @if(!empty($inv_data->po_detail_id))</a>@endif</td>
                        </tr>
                        <tr class="header-tr">
                            <th>Product SKU</th><th>Vendor SKU</th><th>Product Name</th><th>Category</th><th>Subcategory</th>
                        </tr>
                        <tr>
                            <td><a href="{{url('pos/product/detail/'.$inv_data->product_master_id)}}" class="table-link" >{{!empty($inv_data->vendor_sku)?$inv_data->vendor_sku:$inv_data->product_sku}}</a></td>
                            <td>{{$inv_data->vendor_product_sku}}</td><td>{{$inv_data->product_name}}</td><td>{{$inv_data->category_name}}</td><td>{{$inv_data->subcategory_name}}</td>
                        </tr>
                        <tr class="header-tr">
                            <th>Color</th><th>Size</th><th>HSN Code</th><th>Product Type</th><th>Product Barcode</th>
                        </tr>
                        <tr>
                            <td>{{$inv_data->color_name}}</td><td>{{$inv_data->size_name}}</td><td>{{$inv_data->hsn_code}}</td><td>{{($inv_data->arnon_product==1)?'Arnon':'Northcorp'}}</td><td>{{$inv_data->product_barcode}}</td>
                        </tr>
                        <tr class="header-tr">
                            <th>Vendor Rate</th><th>Vendor GST %</th><th>Vendor GST Amount</th><th>Cost Price</th><th>Sale Price (MRP)</th>
                        </tr>
                        <tr>
                            <td>{{$inv_data->vendor_base_price}}</td><td>{{$inv_data->vendor_gst_percent}} %</td><td>{{$inv_data->vendor_gst_amount}}</td><td>{{$inv_data->base_price}}</td><td>{{$inv_data->sale_price}}</td>
                        </tr>
                        <tr class="header-tr">
                            <th>Store Base Rate</th><th>Store GST %</th><th>Store GST Amount</th><th>Store Cost Price</th><th>Store POS Order No</th>
                        </tr>
                        <tr>
                            <td>{{$inv_data->store_base_rate}}</td><td>{{(!empty($inv_data->store_gst_percent))?$inv_data->store_gst_percent.'%':''}}</td><td>{{$inv_data->store_gst_amount}}</td><td>{{$inv_data->store_base_price}}</td><td>{{$inv_data->pos_order_no}}</td>
                        </tr>
                        <tr class="header-tr">
                            <th>Warehouse Intake Date</th><th>Store Assign Date</th><th>Store Intake Date</th><th>Store Sale Date</th><th>Record Last Updated</th>
                        </tr>
                        <tr>
                            <td>{{(!empty($inv_data->intake_date))?date('d M Y H:i',strtotime($inv_data->intake_date)):''}}</td><td>{{(!empty($inv_data->store_assign_date))?date('d M Y H:i',strtotime($inv_data->store_assign_date)):''}}</td><td>{{(!empty($inv_data->store_intake_date))?date('d M Y H:i',strtotime($inv_data->store_intake_date)):''}}</td>
                            <td>{{(!empty($inv_data->store_sale_date))?date('d M Y H:i',strtotime($inv_data->store_sale_date)):''}}</td><td>{{(!empty($inv_data->updated_at))?date('d M Y H:i',strtotime($inv_data->updated_at)):''}}</td>
                        </tr>
                        <tr class="header-tr">
                            <th>Vendor Payment Status</th><th>Vendor Payment ID</th><th></th><th></th><th></th>
                        </tr>
                        <tr>
                            <td>{{CommonHelper::getInventoryPaymentStatusText($inv_data->payment_status)}}</td><td>{{$inv_data->payment_id}}</td>
                        </tr>
                    </table>   
                    
                    <h6>GRN and QC List:</h6>
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size: 13px;">
                        <tr class="header-tr">
                            <th>SNo</th><th>Type</th><th>GRN No</th><th>QC Status</th><th>Invoice No</th><th>Date Added</th>
                        </tr>
                        <?php $count = 1; ?>
                        @for($i=0;$i<count($grn_qc_list);$i++)
                            <tr>
                                <td>{{$count++}}</td>
                                <td>{{strtoupper(str_replace('_',' ',$grn_qc_list[$i]->type))}}</td>
                                <td>
                                    <a href="{{url('warehouse/sor/inventory/import/'.$grn_qc_list[$i]->po_detail_id)}}" class="table-link" >{{$grn_qc_list[$i]->grn_no}}</a>
                                </td>
                                <td>
                                    <a href="{{url('warehouse/sor/inventory/qc/'.$grn_qc_list[$i]->po_detail_id)}}" class="table-link" >{{($grn_qc_list[$i]->type == 'qc')?CommonHelper::getProductInventoryQCStatusName($grn_qc_list[$i]->qc_status):''}}</a>
                                </td>
                                <td><a href="{{url('purchase-order/product/invoice/list/'.$grn_qc_list[$i]->po_id)}}" class="table-link" >{{$grn_qc_list[$i]->invoice_no}}</a></td>
                                <td>{{date('d M Y H:i',strtotime($grn_qc_list[$i]->created_at))}}</td>
                            </tr>
                        @endfor   
                    </table>    
                    
                    <h6>Demands List:</h6>
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size: 13px;">
                        <tr class="header-tr">
                            <th>SNo</th><th>Invoice No</th><th>Demand Type</th><th>Demand Route</th><th>Store Rate</th><th>GST</th><th>Store Cost Price</th><th>Date Added</th>
                        </tr>
                        <?php $count = 1; ?>
                        @foreach($inventory_demands as $timestamp=>$demand_data)
                            <?php $demand_route_data = CommonHelper::getInventoryDemandRoute($demand_data); ?>
                            <tr>
                                <td>{{$count++}}</td>
                                <td>
                                    @if(!empty($demand_route_data['demand_url']))
                                        <a href="{{url($demand_route_data['demand_url'].$demand_data->id)}}" class="table-link" >{{$demand_data->invoice_no}}</a>
                                    @else
                                        {{$demand_data->invoice_no}}
                                    @endif
                                </td>
                                <td>
                                    {{CommonHelper::getDemandTypeText($demand_data->demand_type)}}
                                </td>
                                <td>{{$demand_route_data['demand_route']}}</td>
                                <td>{{$demand_data->store_base_rate}}</td>
                                <td>{{$demand_data->store_gst_amount}} ({{$demand_data->store_gst_percent}} %)</td>
                                <td>{{$demand_data->store_base_price}}</td>
                                <td>{{date('d M Y H:i',$timestamp)}}</td>
                            </tr>
                        @endforeach    
                        
                        @if(empty($inventory_demands))
                            <tr><td colspan="10" align="center">No Records</td></tr> 
                        @endif
                    </table>    
                    
                    <h6>POS Orders List:</h6>
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size: 13px;">
                        <tr class="header-tr">
                            <th>SNo</th><th>Order No</th><th>Sale Type</th><th>Store</th><th>MRP</th><th>Discount</th><th>GST</th><th>Net Price</th><th>Order Date</th>
                        </tr>
                        <?php $count = 1; ?>
                        @foreach($pos_orders as $timestamp=>$pos_order_data)
                            <tr>
                                <td>{{$count++}}</td>
                                <td><a href="{{url('pos/order/detail/'.$pos_order_data->order_id)}}" class="table-link" >{{$pos_order_data->order_no}}</a></td>
                                <td>{{$pos_order_data->product_quantity>=1?'Sale':'Return'}}</td>
                                <td>@if($pos_order_data->foc == 0) {{$pos_order_data->store_name}} ({{$pos_order_data->store_id_code}}) @else Warehouse @endif</td>
                                <td>{{$pos_order_data->sale_price}}</td>
                                <td>{{round($pos_order_data->discount_amount,2)}} ({{$pos_order_data->discount_percent}} %)</td>
                                <td>{{round($pos_order_data->gst_amount,2)}} ({{$pos_order_data->gst_percent}} %) ({{$pos_order_data->gst_inclusive == 1?'Inc':'Exc'}})</td>
                                <td>{{round($pos_order_data->net_price,2)}}</td>
                                <td>{{date('d M Y H:i',$timestamp)}}</td>
                            </tr>
                        @endforeach    
                        
                        @if(empty($pos_orders))
                            <tr><td colspan="8" align="center">No Records</td></tr>
                        @endif
                    </table>    
                    
                </div>    
                
            </div>
        </div>
    </section>

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/pos_product.js?v=1.1') }}" ></script>
@endsection
