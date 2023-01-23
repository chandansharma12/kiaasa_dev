@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $title = ($user->user_type == 15)?'Pending Vendor Inventory Payment':'Add Vendor Inventory Payment'; ?>
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>$title)); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,$title); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <?php $query_str = CommonHelper::getQueryString();  ?>
            Vendor: {{$vendor_data->name}} &nbsp;| &nbsp;
            Start Date: {{date('d-m-Y',strtotime($start_date))}} &nbsp;| &nbsp;
            End Date: {{date('d-m-Y',strtotime($end_date))}} &nbsp;| &nbsp;
            
            <a href="{{url('vendor/inventory/payment/add?action=download_csv&'.$query_str)}}" class="btn btn-dialog" ><i title="Download CSV File" class="fa fa-download fas-icon" ></i> </a>
            
            <div id="demandContainer" class="table-container">
                <div style="width:2450px">&nbsp;</div>
                <div class="table-responsive" style="width:2400px;">
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
                                </tr>
                            @endfor
                        </tbody>
                        <tr><th colspan="6">Total Sold Units: {{$products_list_total->inv_count}}</th><th>{{round($products_list_total->inv_sale_price,2)}}</th><th></th>
                        <th>{{round($products_list_total->inv_discount_amount,2)}}</th><th></th>
                        <th>{{round($products_list_total->inv_gst_amount,2)}}</th><th></th>
                        <th>{{round($products_list_total->inv_net_price,2)}}</th><th>{{round($products_list_total->inv_vendor_base_price,2)}}</th><th></th>
                        <th>{{round($products_list_total->inv_vendor_gst_amount,2)}}</th>
                        <th>{{round($products_list_total->inv_cost_price,2)}}</th>
                        <th colspan="4"></th></tr>
                    </table>
                    
                    @if(!empty($products_list))
                        {{ $products_list->withQueryString()->links() }}
                        <p>Displaying {{$products_list->count()}} of {{ $products_list->total() }} records.</p>
                    @endif
                </div>
                
                @if($user->user_type == 1)
                    <div class="alert alert-success alert-dismissible elem-hidden" id="addVendorPaymentSuccessMessage"></div>
                    <div class="alert alert-danger alert-dismissible elem-hidden" id="addVendorPaymentErrorMessage"></div>

                    <form method="post" name="addVendorPaymentFrm" id="addVendorPaymentFrm">
                        <div class="row " >
                            <div class="col-md-6 form-group" >
                                <textarea name="comment" id="comment" class="form-control" placeholder="Comment" maxlength="250"></textarea>
                            </div>
                        </div>
                        <div class="row " >
                            <div class="col-md-6 form-group" >
                                <div id="vendor_payment_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                                <button type="button" id="vendor_payment_add_cancel" name="vendor_payment_add_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('vendor/report/sales')}}'">Cancel</button>&nbsp;
                                <button type="button" id ="vendor_payment_add_submit" name="vendor_payment_add_submit" class="btn btn-dialog" onclick="submitAddVendorPayment();">Submit Payment</button>

                                <input type="hidden" name="vendor_id" id="vendor_id" value="{{$vendor_data->id}}"/>
                                <input type="hidden" name="startDate" id="startDate" value="{{$startDate}}"/>
                                <input type="hidden" name="endDate" id="endDate" value="{{$endDate}}"/>
                            </div>
                        </div>
                    </form>
                
                @endif
                    
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/vendors.js?v=1.1') }}" ></script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
