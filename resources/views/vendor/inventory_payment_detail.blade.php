@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendor Inventory Payment List','link'=>'vendor/inventory/payment/list'),array('name'=>'Vendor Inventory Payment Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendor Inventory Payment Detail'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <p>
                Vendor: {{$payment_detail->vendor_name}} &nbsp;| &nbsp;
                Start Date: {{date('d-m-Y',strtotime($payment_detail->start_date))}} &nbsp;| &nbsp;
                End Date: {{date('d-m-Y',strtotime($payment_detail->end_date))}}
            </p>
            <p>
                Created On: {{date('d-m-Y',strtotime($payment_detail->created_at))}} &nbsp;| &nbsp;
                Created By: {{$payment_detail->user_name}} &nbsp;| &nbsp;
                Comments: {{$payment_detail->comment}}
            </p>
            <a href="{{url('vendor/inventory/payment/detail/'.$payment_detail->id.'?action=download_csv')}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a>
            <div id="demandContainer" class="table-container">
               
                <div style="width:2520px">&nbsp;</div>
                <div class="table-responsive" style="width:2500px;">
                    <table class="table table-striped clearfix admin-table static-header-tbl" cellspacing="0" style="font-size: 12px;">
                        <thead>
                            <tr class="header-tr">
                                <th>Image</th>
                                <th>Inventory ID</th>
                                <th>QR Code</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Vendor SKU</th>
                                <th>MRP</th>
                                <th>Disc. %</th>
                                <th>Disc. Amt</th>
                                <th>GST %</th>
                                <th>GST Amt</th>
                                <th>GST Inc</th>
                                <th>Net Price</th>
                                <th>Cost Rate</th>
                                <th>Cost GST %</th>
                                <th>Cost GST Amt</th>
                                <th>Cost Price</th>
                                <th>Store Name</th>
                                <th>Code</th>
                                <th>Order Date</th>
                                <th>Order No</th>
                                <th>Payment Status</th>
                            </tr>    
                        </thead>    
                        <tbody>
                            @for($i=0;$i<count($products_list);$i++)
                                <tr>
                                    <td>
                                        @if(file_exists(public_path('images/pos_product_images/'.$products_list[$i]->product_id.'/'.$products_list[$i]->image_name)))
                                            <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$products_list[$i]->product_id.'/'.$products_list[$i]->image_name)}}')">
                                                <img src="{{url('images/pos_product_images/'.$products_list[$i]->product_id.'/thumbs/'.$products_list[$i]->image_name)}}" class="img-thumbnail" style="max-width: 75px;">
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{$products_list[$i]->inventory_id}}</td>
                                    <td>{{$products_list[$i]->peice_barcode}}</td>
                                    <td>{{$products_list[$i]->product_name}} {{$products_list[$i]->size_name}} {{$products_list[$i]->color_name}}</td>
                                    <td>{{$products_list[$i]->product_sku}}</td>
                                    <td>{{$products_list[$i]->vendor_product_sku}}</td>
                                    <td>{{$products_list[$i]->sale_price}}</td>
                                    <td>{{round($products_list[$i]->discount_percent,2)}} %</td>
                                    <td>{{round($products_list[$i]->discount_amount,2)}}</td>
                                    <td>{{$products_list[$i]->gst_percent}} %</td>
                                    <td>{{round($products_list[$i]->gst_amount,2)}}</td>
                                    <td>{{($products_list[$i]->gst_inclusive == 1)?'Yes':'No'}}</td>
                                    <td>{{round($products_list[$i]->net_price,2)}}</td>
                                    <td>{{$products_list[$i]->vendor_base_price}}</td>
                                    <td>{{$products_list[$i]->vendor_gst_percent}}</td>
                                    <td>{{$products_list[$i]->vendor_gst_amount}}</td>
                                    <td>{{$products_list[$i]->base_price}}</td>
                                    <td>{{$products_list[$i]->store_name}}</td>
                                    <td>{{$products_list[$i]->store_id_code}}</td>
                                    <td>{{date('d-m-Y H:i',strtotime($products_list[$i]->order_date))}}</td>
                                    <td>{{$products_list[$i]->order_no}}</td>
                                    <td>{{CommonHelper::getInventoryPaymentStatusText($products_list[$i]->payment_status)}}</td>
                                </tr>
                            @endfor
                        </tbody>
                        <tr><th colspan="3">Total Sold Units: {{$payment_detail->inventory_count}}</th><th colspan="9"></th>
                        <th>{{round($payment_detail->inventory_net_price,2)}}</th><th colspan="3"></th><th>{{round($payment_detail->inventory_cost_price,2)}}</th>
                        <th colspan="5"></th></tr>
                    </table>
                    
                    @if(!empty($products_list))
                        {{ $products_list->withQueryString()->links() }}
                        <p>Displaying {{$products_list->count()}} of {{ $products_list->total() }} records.</p>
                    @endif
                </div>
                 
                <p><b>Note:</b> Vendor sales SKU Detail report contains multiple pos orders of single inventory product, therefore payment should not be done on basis of sales records in this report. Product could be sold and returned multiple times, therefore multiple pos orders of single inventory product are created.
                For Example: if search is executed from 01/05/2022 to 15/05/2022, then single inventory product could be sold and returned multiple times in this search period. Payment will be made only once for latest order of sold product, if its current status is sold from store and if it is not paid previously in payments.</p>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/vendors.js?v=1.1') }}" ></script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
