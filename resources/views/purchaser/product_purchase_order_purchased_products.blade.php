@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'SOR Purchase Products')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'SOR Purchase Products'); $page_name = 'purchase_order_purchased_products'; ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end">
                    <div class="col-md-2">
                        <select name="vendor_id" id="vendor_id" class="form-control">
                            <option value="">-- Vendor --</option>
                            @for($i=0;$i<count($vendor_list);$i++)
                                <?php if($vendor_list[$i]['id'] == request('vendor_id')) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                            @endfor   
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="po_no" id="po_no" class="form-control" value="{{request('po_no')}}" placeholder="PO Order No">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="sku" id="sku" class="form-control" value="{{request('sku')}}" placeholder="Style / SKU">
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" autocomplete="off" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif" autocomplete="false">
                            <div class="input-group-addon" style="margin-top:10px;">&nbsp;to&nbsp;</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-1">
                        <a href="javascript:;" onclick="downloadReportData();" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a>
                    </div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                
                <div id="ordersList">
                    <div style="width:2450px">&nbsp;</div>
                        <div class="table-responsive table-filter" style="width:2400px;">

                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size: 13px;">
                            <thead><tr class="header-tr"><th>S.No</th><th>Supplier</th><th>Style</th><th>Item Name</th>
                                    <th>Size</th><th>Color</th><th>HSN Code</th><th>Season</th><th>PO No</th>
                                    <th>Bill No</th><th>Bill Date</th><th>GRN No</th><th>GRN Date</th><th>Qty</th><th>Return</th>
                                    <th>Rate</th><th>Cost</th><th>GST %</th><th>GST Amt</th><th>Total Cost</th><th>Sale Price</th> 
                            </tr></thead>
                            <tbody>
                                <?php $total_data = array('qty'=>0,'gst_amt'=>0,'cost'=>0,'total_cost'=>0,'return'=>0); ?>
                                @for($i=0;$i<count($products_list);$i++)
                                    <tr>
                                        <td>{{$sno+$i}}</td>
                                        <td>{{$vendor_id_list[$products_list[$i]->vendor_id]}}</td>
                                        <td>{{$products_list[$i]->vendor_sku}}</td>
                                        <td>{{$products_list[$i]->product_name}}</td>
                                        <td>{{$size_id_list[$products_list[$i]->size_id]}}</td>
                                        <td>{{$color_id_list[$products_list[$i]->color_id]}}</td>
                                        <td>{{$products_list[$i]->hsn_code}}</td>
                                        <td>{{isset($season_id_list[$products_list[$i]->season_id])?$season_id_list[$products_list[$i]->season_id]:0}}</td>
                                        <td>{{$products_list[$i]->order_no}}</td>
                                        <td>{{$products_list[$i]->invoice_no}}</td>
                                        <td>{{date('d-m-Y',strtotime($products_list[$i]->invoice_date))}}</td>
                                        <td>{{$products_list[$i]->grn_no}}</td>
                                        <td>{{date('d-m-Y',strtotime($products_list[$i]->grn_date))}}</td>
                                        <td>{{$products_list[$i]->inv_count}}</td>
                                        <td>{{$products_list[$i]->inv_return}}</td>
                                        <td>{{$products_list[$i]->rate}}</td>
                                        <td>{{$cost = round($products_list[$i]->rate*$products_list[$i]->inv_count,2)}}</td>
                                        <td>{{$products_list[$i]->gst_percent}}%</td>
                                        <td>{{$gst_amt = round(($products_list[$i]->rate*$products_list[$i]->inv_count)*($products_list[$i]->gst_percent/100),2)}}</td>
                                        <td>{{$cost+$gst_amt}}</td>
                                        <td>{{$products_list[$i]->sale_price}}</td>
                                    </tr>
                                    <?php
                                    $cost = $products_list[$i]->rate*$products_list[$i]->inv_count;
                                    $gst_amt = ($products_list[$i]->rate*$products_list[$i]->inv_count)*($products_list[$i]->gst_percent/100);
                                    
                                    $total_data['qty']+=$products_list[$i]->inv_count;
                                    $total_data['gst_amt']+=$gst_amt;
                                    $total_data['cost']+=$cost;
                                    $total_data['total_cost']+=($cost+$gst_amt);
                                    $total_data['return']+=$products_list[$i]->inv_return;
                                    ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="13">Page Total</th>
                                    <th>{{$total_data['qty']}}</th>
                                    <th>{{$total_data['return']}}</th>
                                    <th></th>
                                    <th>{{round($total_data['cost'],2)}}</th>
                                    <th></th>
                                    <th>{{round($total_data['gst_amt'],2)}}</th>
                                    <th>{{round($total_data['total_cost'],2)}}</th>
                                    <th></th>
                                </tr>
                                @if(!empty($search_array))
                                    <tr>
                                        <th colspan="13">Total</th>
                                        <th>{{$total_array['grn']}}</th>
                                        <th>{{(isset($total_array['return']))?$total_array['return']:0}}</th>
                                        <th colspan="6"></th>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                        {{ $products_list->withQueryString()->links() }}
                        <p>Displaying {{$products_list->count()}} of {{ $products_list->total() }} records.</p> 
                    </div>       
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="downloadReportDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" >Download SOR Purchase Products</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadReportErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadReportSuccessMessage"></div>
                
                <form method="post" name="downloadReportForm" id="downloadReportForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Records</label>
                                <?php $records_count = $products_list->total(); ?>
                                <select name="report_rec_count" id="report_rec_count" class="form-control" >
                                    <option value="">--Records--</option>
                                        @for($i=0;$i<=$records_count;$i=$i+20000) 
                                            <?php $start = $i+1; $end = $i+20000; ?>
                                            <?php $end = ($end < $records_count)?$end:$records_count; ?>
                                            <option value="{{$start}}_{{$end}}">{{$start}} - {{$end}}</option>
                                        @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_report_rec_count"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                
                <div class="modal-footer center-footer">
                    <div id="downloadReport_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button name="downloadReportCancel" id="downloadReportCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="downloadReportBtn" id="downloadReportBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadReportData('/purchase-order/purchased/products');">Download</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
