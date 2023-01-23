@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendor Sales Report')); ?>
    <?php $page_heading = (isset($vendor_data->name))?' - '.$vendor_data->name:''; ?>
    <?php $report_name = '';if($report_type == 'sku_list') $report_name = ' - SKU Sales List';if($report_type == 'sku_detail') $report_name = ' - SKU Sales Detail'; ?>
    <?php if($report_type == 'store_sku_list') $report_name = ' - Store SKU: '.request('sku'); if(!empty($product_info->vendor_product_sku)) $report_name.=' ('.$product_info->vendor_product_sku.')'; ?>
    <?php if($report_type == 'vendor_list') { $page_name = 'vendor_sales_report'; } ?>
    <?php if($report_type == 'sku_list') { $page_name = 'vendor_sales_report_sku_list'; } ?>
    <?php if($report_type == 'sku_detail') { $page_name = 'vendor_sales_report_sku_detail'; } ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendor Sales Report'.$page_heading.$report_name); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="ErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="SuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    @if($report_type != 'store_sku_list')
                        <div class="col-md-2" >
                            <select name="po_cat_id" id="po_cat_id" class="form-control">
                                <option value=""> -- PO Category -- </option>
                                @for($i=0;$i<count($po_category_list);$i++)
                                    <?php if(request('po_cat_id') == $po_category_list[$i]['id']) $sel = 'selected';else $sel = ''; ?>
                                    <option {{$sel}} value="{{$po_category_list[$i]['id']}}">{{$po_category_list[$i]['name']}}</option>
                                @endfor    
                            </select>
                        </div>
                    @endif
                    @if($report_type == 'sku_detail')
                        <div class="col-md-2" >
                            <select name="rec_type" id="rec_type" class="form-control">
                                <option value=""> All Records ({{$total_data['sale']+$total_data['return']}})</option>
                                <option value="sale" @if(request('rec_type') == 'sale' ) selected @endif > Sale Records ({{$total_data['sale']}})</option>
                                <option value="return" @if(request('rec_type') == 'return' ) selected @endif> Return Records ({{$total_data['return']}})</option>
                            </select>
                        </div>
                    @endif
                    
                    @if($report_type == 'vendor_list' && !empty($sub_vendor_list))
                        <div class="col-md-2" >
                            <select name="sub_v_id" id="sub_v_id" class="form-control">
                                <option value=""> -- All Vendors -- </option>
                                <option value="{{$vendor_info->id}}" <?php if(request('sub_v_id') == $vendor_info->id) echo 'selected'; ?>>{{$vendor_info->name}}</option>
                                @for($i=0;$i<count($sub_vendor_list);$i++)
                                    <?php if(request('sub_v_id') == $sub_vendor_list[$i]['id']) $sel = 'selected';else $sel = ''; ?>
                                    <option {{$sel}} value="{{$sub_vendor_list[$i]['id']}}">{{$sub_vendor_list[$i]['name']}}</option>
                                @endfor    
                            </select>
                        </div>
                    @endif
                    
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{$start_date = request('startDate')}}@else{{$start_date = date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{$end_date = request('endDate')}}@else{{$end_date = date('d-m-Y')}}@endif">
                        </div>
                    </div>
                                    
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    <?php $startDate = (!empty(request('startDate')))?request('startDate'):'';  ?>
                    <?php $endDate = (!empty(request('endDate')))?request('endDate'):'';  ?>
                    
                    <?php $startDate1 = (!empty(request('startDate')))?request('startDate'):date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()));  ?>
                    <?php $endDate1 = (!empty(request('endDate')))?request('endDate'):date('d-m-Y');  ?>
                    
                    @if($report_type == 'sku_detail')
                        <div class="col-md-1"><a href="{{url('vendor/report/sales/'.$vendor_data->id.'?report_type=sku_detail&action=download_csv_sku_detail&'.$query_str)}}" class="btn btn-dialog" ><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                        @if($user->user_type == 1)
                            <div class="col-md-2"><a href="{{url('vendor/inventory/payment/add'.'?vendor_id='.$vendor_data->id.'&startDate='.$startDate1.'&endDate='.$endDate1)}}" class="btn btn-dialog" >Make Payment </a></div>
                        @endif
                        
                        @if($user->user_type == 15)
                            <div class="col-md-2"><a href="{{url('vendor/inventory/payment/add'.'?vendor_id='.$vendor_data->id.'&startDate='.$startDate1.'&endDate='.$endDate1)}}" class="btn btn-dialog" >Pending Payment </a></div>
                        @endif
                    @endif
                    
                    @if($report_type == 'store_sku_list')
                        <input type="hidden" name="sku" id="sku" value="{{request('sku')}}">
                    @endif
                         
                    <input type="hidden" name="report_type" id="report_type" value="{{request('report_type')}}">
                    
                    @if($report_type == 'vendor_list')
                        <div class="col-md-1"><a href="{{url('vendor/report/sales?action=download_csv_vendor_list&'.$query_str)}}" class="btn btn-dialog" ><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                    @endif
                    
                    @if($report_type == 'sku_list')
                        <div class="col-md-1"><a href="{{url('vendor/report/sales/'.$vendor_data->id.'?action=download_csv_sku_list&'.$query_str)}}" class="btn btn-dialog" ><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                    @endif
                </div>
            </form>
            <div class="separator-10"></div>

            <div id="usersContainer">
                
                @if($report_type == 'vendor_list')
                    <div style="width:2800px">&nbsp;</div>
                    <div class="table-responsive" style="width:2780px;">
                        <table class="table table-striped clearfix admin-table static-header-tbl" cellspacing="0" id="vendorListTbl" style="font-size: 13px;">
                            <thead>
                                <tr class="header-tr">
                                    <th>Vendor</th>
                                    <th colspan="9" align="center" style="text-align: center;border-left: 1px solid #fff;">Total </th>    
                                    <th colspan="5" align="center" style="text-align: center;border-left: 1px solid #fff;border-right: 1px solid #fff;">From {{$start_date}} - {{$end_date}}</th>    
                                    <th>In Stores</th>   
                                    <th>In WH</th> 
                                    <th>WH to Vendor</th>
                                    <th>In Stores Diff</th>   
                                    <th>In WH Diff</th>   
                                    <th>Action</th>
                                </tr>
                                <tr class="header-tr">
                                    <th style="border-top: none;"></th>
                                    <th style="border-left: 1px solid #fff;border-top: none;">Vendor to WH Rec</th>
                                    <th style="border-top: none;">Transit WH to Store</th> 
                                    <th style="border-top: none;">WH to Store Rec</th>
                                    <th style="border-top: none;">Transit Store to WH</th>
                                    <th style="border-top: none;">Store to WH Rec</th>
                                    <th style="border-top: none;">Transit Store to Store</th>
                                    <th style="border-top: none;">Sold Units</th>
                                    <th style="border-top: none;">Net Price</th>
                                    <th style="border-right: 1px solid #fff;border-top: none;">Sell Through</th>
                                    <th style="border-top: none;">WH to Store Rec</th>
                                    <th style="border-top: none;">Store to WH Rec</th>
                                    <th style="border-top: none;">Sold Units</th>
                                    <th style="border-top: none;">Net Price</th>
                                    <th style="border-right: 1px solid #fff;border-top: none;">Sell Through</th>
                                    <th colspan="6" style="border-top: none;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_data = array('inv_pushed_total'=>0,'inv_pushed'=>0,'inv_sold'=>0,'inv_sold_total'=>0,'inv_balance'=>0,'inv_sold_price'=>0,'inv_sold_price_total'=>0,'inv_transit_wh_to_store'=>0,'inv_warehouse'=>0); ?>
                                @for($i=0;$i<count($vendors_list);$i++)
                                    <tr>
                                        <td>{{$vendors_list[$i]['name']}}</td>
                                        <td>{{$vendors_list[$i]['inv_vendor_to_wh']}}</td>
                                        <td>{{$vendors_list[$i]['inv_transit_wh_to_store']}}</td>
                                        <td>{{$vendors_list[$i]['inv_pushed_total']}}</td>
                                        <td>{{$vendors_list[$i]['inv_returned_transit_total']}}</td>
                                        <td>{{$vendors_list[$i]['inv_returned_total']}}</td>
                                        <td>{{$vendors_list[$i]['inv_transit_store_to_store']}}</td>
                                        <td>{{$vendors_list[$i]['inv_sold_total']}}</td>
                                        <td>{{CommonHelper::currencyFormat($vendors_list[$i]['inv_sold_price_total'])}}</td>
                                        <td>@if(($vendors_list[$i]['inv_balance']+$vendors_list[$i]['inv_sold_total']) > 0) {{round(($vendors_list[$i]['inv_sold_total']/($vendors_list[$i]['inv_balance']+$vendors_list[$i]['inv_sold_total']))*100,3)}} % @endif</td>
                                        <td>{{$vendors_list[$i]['inv_pushed']}}</td>
                                        <td>{{$vendors_list[$i]['inv_returned']}}</td>
                                        <td>{{$vendors_list[$i]['inv_sold']}}</td>
                                        <td>{{CommonHelper::currencyFormat($vendors_list[$i]['inv_sold_price'])}}</td>
                                        <td>@if($vendors_list[$i]['inv_balance']+$vendors_list[$i]['inv_sold'] > 0) {{round(($vendors_list[$i]['inv_sold']/($vendors_list[$i]['inv_balance']+$vendors_list[$i]['inv_sold']))*100,3)}} % @endif</td>
                                        <td>{{$vendors_list[$i]['inv_balance']}}</td>
                                        <td>{{$vendors_list[$i]['inv_warehouse']}}</td>
                                        <td>{{$vendors_list[$i]['inv_wh_to_vendor']}}</td>
                                        <td>{{$vendors_list[$i]['inv_pushed_total']-($vendors_list[$i]['inv_returned_transit_total']+$vendors_list[$i]['inv_returned_total']+$vendors_list[$i]['inv_transit_store_to_store']+$vendors_list[$i]['inv_sold_total']+$vendors_list[$i]['inv_balance'])}}</td>
                                        <td>{{(($vendors_list[$i]['inv_vendor_to_wh']+$vendors_list[$i]['inv_returned_total'])-($vendors_list[$i]['inv_transit_wh_to_store']+$vendors_list[$i]['inv_pushed_total']))-($vendors_list[$i]['inv_warehouse']+$vendors_list[$i]['inv_wh_to_vendor'])}}</td>
                                        <td>
                                            <a class="table-link" href="{{url('vendor/report/sales/'.$vendors_list[$i]['id'].'?report_type=sku_list&startDate='.$startDate.'&endDate='.$endDate)}}">Sales Report</a> | 
                                            <a class="table-link" href="{{url('vendor/report/sales/'.$vendors_list[$i]['id'].'?report_type=sku_detail&startDate='.$startDate.'&endDate='.$endDate)}}">Sales Detail Report</a>
                                        </td>
                                    </tr>
                                    <?php $total_data['inv_pushed_total']+=$vendors_list[$i]['inv_pushed_total']; ?>
                                    <?php $total_data['inv_pushed']+=$vendors_list[$i]['inv_pushed']; ?>
                                    <?php $total_data['inv_sold']+=$vendors_list[$i]['inv_sold']; ?>
                                    <?php $total_data['inv_sold_total']+=$vendors_list[$i]['inv_sold_total']; ?>
                                    <?php $total_data['inv_balance']+=$vendors_list[$i]['inv_balance']; ?>
                                    <?php $total_data['inv_sold_price']+=$vendors_list[$i]['inv_sold_price']; ?>
                                    <?php $total_data['inv_sold_price_total']+=$vendors_list[$i]['inv_sold_price_total']; ?>
                                    <?php $total_data['inv_transit_wh_to_store']+=$vendors_list[$i]['inv_transit_wh_to_store']; ?>
                                    <?php $total_data['inv_warehouse']+=$vendors_list[$i]['inv_warehouse']; ?>
                                @endfor
                                
                                @if($user->user_type != 15)
                                    <tr>
                                        <td>Kiaasa Retail LLP (Arnon)</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>{{$vendors_list_orders_without_po->inv_count_sold_total}}</td>
                                        <td>{{CommonHelper::currencyFormat($vendors_list_orders_without_po->total_net_price_total)}}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>{{$vendors_list_orders_without_po->inv_count_sold}}</td>
                                        <td>{{CommonHelper::currencyFormat($vendors_list_orders_without_po->total_net_price)}}</td>
                                        <td colspan="7"></td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>{{$total_data['inv_sold_total']+$vendors_list_orders_without_po->inv_count_sold_total}}</th>
                                    <th>{{CommonHelper::currencyFormat($total_data['inv_sold_price_total']+$vendors_list_orders_without_po->total_net_price_total)}}</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>{{$total_data['inv_sold']+$vendors_list_orders_without_po->inv_count_sold}}</th>
                                    <th>{{CommonHelper::currencyFormat($total_data['inv_sold_price']+$vendors_list_orders_without_po->total_net_price)}}</th>
                                    <th></th>
                                    <th>{{$total_data['inv_balance']}}</th>
                                    
                                    <th>{{$total_data['inv_warehouse']}}</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                        <br/>    
                        
                    </div>
                @endif
                
                @if($report_type == 'sku_list')
                    <div style="width:1900px">&nbsp;</div>
                    <div class="table-responsive" style="width:1850px">
                        <table class="table table-striped clearfix admin-table static-header-tbl" cellspacing="0" id="skuListTbl" style="font-size: 13px;">
                            <thead>
                                <tr class="header-tr" >
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Vendor SKU</th>
                                    <th>Cost Rate</th>
                                    <th>GST Amount</th>
                                    <th>Cost Price</th>
                                    <th>MRP</th>
                                    <th>Sold Units</th>    
                                    <th>Total Taxable Amt</th>
                                    <th>Total GST Amt</th>
                                    <th>Total Net Price</th>
                                    <th>Action</th>
                                </tr>    
                            </thead>    
                            <tbody>
                                <?php $total_data = array('inv_count'=>0,'total_taxable_amount'=>0,'total_gst_amount'=>0,'total_net_price'=>0); ?>
                                @for($i=0;$i<count($products_list);$i++)
                                    <tr>
                                        <td>
                                            @if(file_exists(public_path('images/pos_product_images/'.$products_list[$i]->product_id.'/'.$products_list[$i]->image_name)))
                                                <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$products_list[$i]->product_id.'/'.$products_list[$i]->image_name)}}')">
                                                    <img src="{{url('images/pos_product_images/'.$products_list[$i]->product_id.'/thumbs/'.$products_list[$i]->image_name)}}" class="img-thumbnail" style="max-width: 75px;">
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{$products_list[$i]->product_name}} {{$products_list[$i]->color_name}}</td>
                                        <td>{{$products_list[$i]->product_sku}}</td>
                                        <td>{{$products_list[$i]->vendor_product_sku}}</td>
                                        <td>{{$products_list[$i]->vendor_base_price}}</td>
                                        <td>{{$products_list[$i]->vendor_gst_amount}}</td>
                                        <td>{{$products_list[$i]->base_price}}</td>
                                        <td>{{$products_list[$i]->sale_price}}</td>
                                        <td>{{$products_list[$i]->inv_count}}</td>
                                        <td>{{round($products_list[$i]->total_taxable_amount,2)}}</td>
                                        <td>{{round($products_list[$i]->total_gst_amount,2)}}</td>
                                        <td>{{round($products_list[$i]->total_net_price,2)}}</td>
                                        <td>
                                            <a class="table-link" href="{{url('vendor/report/sales/'.$vendor_data['id'].'?report_type=store_sku_list&sku='.$products_list[$i]->product_sku.'&startDate='.$startDate.'&endDate='.$endDate)}}">Store Report</a>
                                        </td>
                                    </tr>
                                    <?php $total_data['inv_count']+=$products_list[$i]->inv_count; ?>
                                    <?php $total_data['total_taxable_amount']+=$products_list[$i]->total_taxable_amount; ?>
                                    <?php $total_data['total_gst_amount']+=$products_list[$i]->total_gst_amount; ?>
                                    <?php $total_data['total_net_price']+=$products_list[$i]->total_net_price; ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="8">Total</th>
                                    <th>{{$total_data['inv_count']}}</th>
                                    <th>{{round($total_data['total_taxable_amount'],2)}}</th>
                                    <th>{{round($total_data['total_gst_amount'],2)}}</th>
                                    <th>{{round($total_data['total_net_price'],2)}}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                        @if(!empty($products_list))
                            {{ $products_list->withQueryString()->links() }} <p>Displaying {{$products_list->count()}} of {{ $products_list->total() }} records.</p>
                        @endif
                        <br/>
                    </div>    
                @endif
                
                @if($report_type == 'sku_detail')
                    @if(!empty($products_list))
                        <p><b>Note:</b> This report contains multiple pos orders of single inventory product, therefore payment should not be done on basis of sales records in this report. 
                        Product could be sold and returned multiple times, therefore multiple pos orders of single inventory product are created.
                        <br>For Example: if search is executed from 01/05/2022 to 15/05/2022, then single inventory product could be sold and returned multiple times in this search period. 
                        Payment will be made only once for latest order of sold product, if its current status is sold from store and if it is not paid previously in payments.</p>
                    @endif
                    <div style="width:2450px">&nbsp;</div>
                    <div class="table-responsive" style="width:2400px;">
                        <table class="table table-striped clearfix admin-table static-header-tbl" cellspacing="0" style="font-size: 12px;">
                            <thead>
                                <tr class="header-tr">
                                    <th>Image</th>
                                    <th>Type</th>
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
                                    <th>Size</th>
                                    <th>Color</th>
                                    <th>Inventory ID</th>
                                    <th>QR Code</th>
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
                                        <td>{{($products_list[$i]->product_quantity == 1)?'Sale':'Return'}}</td>
                                        <td>{{$products_list[$i]->product_name}}</td>
                                        <td>{{$products_list[$i]->product_sku}}</td>
                                        <td>{{$products_list[$i]->vendor_product_sku}}</td>
                                        <td>{{$products_list[$i]->sale_price}}</td>
                                        <td>{{round($products_list[$i]->discount_percent,2)}} %</td>
                                        <td>{{round($products_list[$i]->discount_amount,2)}}</td>
                                        <td>{{$products_list[$i]->gst_percent}}</td>
                                        <td>{{round($products_list[$i]->gst_amount,2)}}</td>
                                        <td>{{($products_list[$i]->gst_inclusive == 1)?'Yes':'No'}}</td>
                                        <td>{{round($products_list[$i]->net_price,2)}}</td>
                                        <td>{{($products_list[$i]->product_quantity == -1)?'-':''}}{{$products_list[$i]->vendor_base_price}}</td>
                                        <td>{{($products_list[$i]->product_quantity == -1)?'-':''}}{{$products_list[$i]->vendor_gst_percent}} %</td>
                                        <td>{{($products_list[$i]->product_quantity == -1)?'-':''}}{{$products_list[$i]->vendor_gst_amount}}</td>
                                        <td>{{($products_list[$i]->product_quantity == -1)?'-':''}}{{$products_list[$i]->base_price}}</td>
                                        <td>{{$products_list[$i]->store_name}}</td>
                                        <td>{{$products_list[$i]->store_id_code}}</td>
                                        <td>{{date('d-m-Y H:i',strtotime($products_list[$i]->order_date))}}</td>
                                        <td>{{$products_list[$i]->order_no}}</td>
                                        <td>{{$products_list[$i]->size_name}}</td>
                                        <td>{{$products_list[$i]->color_name}}</td>
                                        <td>{{$products_list[$i]->inventory_id}}</td>
                                        <td>{{$products_list[$i]->peice_barcode}}</td>
                                        <td>{{$products_list[$i]->payment_status == '1'?'Paid':'Not Paid'}}</td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                        <p><!--Total Records: {{$total_data['sale']+$total_data['return']}} &nbsp;|&nbsp;--> Sale Records: {{$total_data['sale']}} &nbsp;|&nbsp; Return Records: {{$total_data['return']}} <!--&nbsp;|&nbsp; Total Units Sold: {{$total_data['sale']-$total_data['return']}}--></p>
                        @if(!empty($products_list))
                            {{ $products_list->withQueryString()->links() }}
                            <p>Displaying {{$products_list->count()}} of {{ $products_list->total() }} records.</p>
                            
                        @endif
                    </div>    
                @endif
                
                @if($report_type == 'store_sku_list')
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table static-header-tbl" cellspacing="0" id="storeSkuListTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th>Store</th>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <th>{{$size_list[$i]->size}}</th>    
                                    @endfor
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total = array('total'=>0); ?>
                                @for($i=0;$i<count($products_list);$i++)
                                    <tr>
                                        <td>{{$products_list[$i]['store_name']}} ({{$products_list[$i]['store_id_code']}})</td>
                                        @for($q=0;$q<count($size_list);$q++)
                                            <?php $size_id = $size_list[$q]->id; ?>
                                            <?php if(isset($products_list[$i][$size_id])){
                                                $count = $products_list[$i][$size_id];
                                                if(isset($total[$size_id])) $total[$size_id]+=$count; else $total[$size_id]=$count;
                                            }else{
                                                $count = 0;
                                            }
                                            ?>
                                            <td>{{$count}}</td>    
                                        @endfor
                                        <td>{{$products_list[$i]['total']}}</td>
                                    </tr>
                                    
                                    <?php $total['total']+=$products_list[$i]['total']; ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    @for($q=0;$q<count($size_list);$q++)
                                        <th>
                                            <?php if(isset($total[$size_list[$q]->id])) echo $total[$size_list[$q]->id]; ?>
                                        </th>
                                    @endfor    
                                    <th>{{$total['total']}}</th>
                                </tr>
                            </tfoot>
                        </table>
                        
                        @if(file_exists(public_path('images/pos_product_images/'.$product_info->product_id.'/'.$product_info->image_name)))
                            <br/>
                            <center>
                                <!--<a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$product_info->product_id.'/'.$product_info->image_name)}}')">-->
                                    <img src="{{url('images/pos_product_images/'.$product_info->product_id.'/'.$product_info->image_name)}}" class="img-fluid" style="max-width: 800px;border:1px solid #ccc;padding:2px;">
                                <!--</a>-->
                            </center>
                            <br/><br/>
                        @endif

                    </div>
                @endif
                
             </div>
        </div>
    </section>
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/vendors.js') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>

<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@if(empty($error_message))
    <script type="text/javascript">
        $(document).ready(function(){
            
        });
    </script>
@endif
@endsection
