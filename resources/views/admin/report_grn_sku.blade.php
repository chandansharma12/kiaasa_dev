@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'GRN SKU Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'GRN SKU Report');$page_name = 'grn_sku_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    @if($user->user_type == 1 )
                        <div class="col-md-2" >
                            <select name="v_id" id="v_id" class="form-control">
                                <option value="">All Vendors</option>
                                @for($i=0;$i<count($vendor_list);$i++)
                                    <?php $sel = ($vendor_list[$i]['id'] == request('v_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    
                    @if($user->user_type == 15 && !empty($sub_vendor_list) )
                        <div class="col-md-2" >
                            <select name="sub_v_id" id="sub_v_id" class="form-control">
                                <option value="">All Vendors</option>
                                <option value="{{$vendor_data->id}}" <?php if(request('sub_v_id') == $vendor_data->id) echo 'selected'; ?>>{{$vendor_data->name}}</option>
                                @for($i=0;$i<count($sub_vendor_list);$i++)
                                    <?php $sel = ($sub_vendor_list[$i]['id'] == request('sub_v_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$sub_vendor_list[$i]['id']}}">{{$sub_vendor_list[$i]['name']}}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    
                    <div class="col-md-2" >
                        <input type="text" name="po_no" id="po_no" class="form-control" placeholder="PO No" value="{{request('po_no')}}">
                    </div>
                        <div class="col-md-1" >
                        <input type="text" name="invoice_no" id="invoice_no" class="form-control" placeholder="Invoice No" value="{{request('invoice_no')}}">
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="sku" id="sku" class="form-control" placeholder="SKU" value="{{request('sku')}}">
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input autocomplete="off" type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}} @endif">
                            <div class="input-group-addon" style="margin-top:10px;">to </div> &nbsp;
                            <input autocomplete="off" type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}} @endif">
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
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>GRN No</th>
                                    <th>Invoice No</th>
                                    <th>PO No</th>
                                    <th>Vendor</th>
                                    <th>Style</th>
                                    <th>Total Qty</th>
                                    <th>WH Qty</th>
                                    <th>Store Qty</th>
                                    <th>Category</th>
                                    <th>Rate</th>
                                    <th>GST%</th>
                                    <th>GST Amt</th>
                                    <th>Cost</th>
                                    <th>Total Cost</th>
                                    <th>Created On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_inv = $wh_inv = $store_inv = 0; ?>
                                @for($i=0;$i<count($sku_list);$i++)
                                    <tr>
                                        <td>{{$sku_list[$i]->grn_no}}</td>
                                        <td>{{$sku_list[$i]->invoice_no}}</td>
                                        <td>{{$sku_list[$i]->order_no}}</td>
                                        <td>{{$vendor_id_list[$sku_list[$i]->vendor_id]}}</td>
                                        <td>{{$sku_list[$i]->product_sku}}</td>
                                        <td>{{$sku_list[$i]->inv_count}}</td>
                                        <td>{{$sku_list[$i]->inv_wh}}</td>
                                        <td>{{$sku_list[$i]->inv_store}}</td>
                                        <td>{{$category_id_list[$sku_list[$i]->po_category_id]}}</td>
                                        <td>{{$sku_list[$i]->poi_rate}}</td>
                                        <td>{{$sku_list[$i]->poi_gst_percent}}</td>
                                        <td>{{$gst_amount = round($sku_list[$i]->poi_rate*($sku_list[$i]->poi_gst_percent)/100,2)}}</td>
                                        <td>{{$cost = $sku_list[$i]->poi_rate+$gst_amount}}</td>
                                        <td>{{round($sku_list[$i]->inv_count*$cost,2)}}</td>
                                        <td>{{date('d-m-Y',strtotime($sku_list[$i]->grn_date))}}</td>
                                    </tr>
                                    <?php $total_inv+=$sku_list[$i]->inv_count; ?>
                                    <?php $wh_inv+=$sku_list[$i]->inv_wh; ?>
                                    <?php $store_inv+=$sku_list[$i]->inv_store; ?>
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr>
                                    <th colspan="5">Page Total</th>
                                    <th>{{$total_inv}}</th>
                                    <th>{{$wh_inv}}</th>
                                    <th>{{$store_inv}}</th>
                                </tr>
                                @if(!empty($search_array))
                                    <tr>
                                        <th colspan="5">Total</th>
                                        <th>{{$total_array['grn']}}</th>
                                        <th>{{$total_array['wh']}}</th>
                                        <th>{{$total_array['store']}}</th>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                        {{ $sku_list->withQueryString()->links() }}
                        <p>Displaying {{$sku_list->count()}} of {{ $sku_list->total() }} records.</p>
                        <br/>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="downloadReportDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" >Download GRN SKU Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadReportErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadReportSuccessMessage"></div>
                
                <form method="post" name="downloadReportForm" id="downloadReportForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>SKU Records</label>
                                <?php $sku_count = $sku_list->total(); ?>
                                <select name="report_rec_count" id="report_rec_count" class="form-control" >
                                    <option value="">--SKU Records--</option>
                                        @for($i=0;$i<=$sku_count;$i=$i+10000) 
                                            <?php $start = $i+1; $end = $i+10000; ?>
                                            <?php $end = ($end < $sku_count)?$end:$sku_count; ?>
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
                    <button name="downloadReportBtn" id="downloadReportBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadReportData('/grn/sku/report');">Download</button>
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


@endsection
