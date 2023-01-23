@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Stock Details')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Stock Details');$page_name = 'store_stock_details'; ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end">
                    <div class="col-md-1">
                        <input type="text" name="sku" id="sku" class="form-control" value="{{request('sku')}}" placeholder="Style / SKU">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="invoice_no" id="invoice_no" class="form-control" value="{{request('invoice_no')}}" placeholder="Doc / Invoice No">
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
                        <select name="type" id="type" class="form-control">
                            @foreach($report_types as $type=>$type_name)
                                <option <?php echo $sel = ($type == $report_type)?'selected':''; ?> value="{{$type}}">{{$type_name}}</option>
                            @endforeach
                        </select>
                    </div> 
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif" autocomplete="off">
                            <div class="input-group-addon" style="margin-top:10px;">&nbsp;to&nbsp;</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2">
                        
                        <a href="javascript:;" onclick="downloadReportData();" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a>
                    </div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                
                <div id="ordersList">
                     <div style="width:1860px">&nbsp;</div>
                        <div class="table-responsive table-filter" style="width:1850px;">
                        <?php $receive_count = $transfer_count = 0; ?>    
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size: 13px;">
                            <thead><tr class="header-tr"><th>S.No</th><th>Image</th><th>Doc No</th><th>Store Name</th><th>Code</th><th>Item Name</th>
                                <th>Doc Date</th><th>Rec. DocNo</th><th>Rec. Date</th><th>Qty</th><th>Rec Qty</th>
                                <th>Value</th><th>Type</th><th>Style</th><th>Sale Price</th><th>Inventory Group</th>
                                <th>Size</th><th>Color</th>
                            </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($store_products);$i++)
                                    <tr>
                                        <td>{{$sno+$i}}</td>
                                        <td>
                                            @if(isset($images_list[$store_products[$i]->product_id]->image_name) && file_exists(public_path('images/pos_product_images/'.$store_products[$i]->product_id.'/'.$images_list[$store_products[$i]->product_id]->image_name)))
                                                <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$store_products[$i]->product_id.'/'.$images_list[$store_products[$i]->product_id]->image_name)}}')">
                                                    <img src="{{url('images/pos_product_images/'.$store_products[$i]->product_id.'/thumbs/'.$images_list[$store_products[$i]->product_id]->image_name)}}" class="img-thumbnail" style="max-width: 75px;">
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{$store_products[$i]->invoice_no}}</td>
                                        <td>{{$store_id_list[$store_products[$i]->store_id]['store_name'] }}</td>
                                        <td>{{$store_id_list[$store_products[$i]->store_id]['store_id_code']}}</td>
                                        <td>{{$store_products[$i]->product_name}}</td>
                                        <td>{{date('d-m-Y',strtotime($store_products[$i]->invoice_date))}}</td>
                                        <td>{{$store_products[$i]->receive_docket_no}}</td>
                                        <td>@if(!empty($store_products[$i]->receive_date)) {{date('d-m-Y',strtotime($store_products[$i]->receive_date))}} @endif</td>
                                        <td>{{$store_products[$i]->transfer_count}}</td>
                                        <td>{{(!empty($store_products[$i]->receive_count))?$store_products[$i]->receive_count:0}}</td>
                                        <td>{{$store_products[$i]->store_base_price}}</td>
                                        <td></td>
                                        <td>{{(!empty($store_products[$i]->vendor_sku))?$store_products[$i]->vendor_sku:$store_products[$i]->product_sku}}</td>
                                        <td>{{$store_products[$i]->sale_price}}</td>
                                        <td>{{$category_id_list[$store_products[$i]->category_id] }}</td>
                                        <td>{{$size_id_list[$store_products[$i]->size_id] }}</td>
                                        <td>{{$color_id_list[$store_products[$i]->color_id] }}</td>
                                    </tr>
                                    <?php $transfer_count+=$store_products[$i]->transfer_count; ?>
                                    <?php $receive_count+=$store_products[$i]->receive_count; ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="9">Page Total</th>
                                    <th>{{$transfer_count}}</th>
                                    <th>{{$receive_count}}</th>
                                </tr>
                                @if(!empty($search_array))
                                    <tr>
                                        <th colspan="9">Total</th>
                                        <th>{{$inv_transfer_total}}</th>
                                        <th>{{$inv_receive_total}}</th>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                        {{ $store_products->withQueryString()->links() }}
                        <p>Displaying {{$store_products->count()}} of {{ $store_products->total() }} records.</p>
                    </div>       
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="downloadReportDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" >Download Store Stock Details Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadReportErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadReportSuccessMessage"></div>
                
                <form method="post" name="downloadReportForm" id="downloadReportForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Records</label>
                                <?php $rec_count = $store_products->total(); ?>
                                <select name="report_rec_count" id="report_rec_count" class="form-control" >
                                    <option value="">--Records--</option>
                                        @for($i=0;$i<=$rec_count;$i=$i+50000) 
                                            <?php $start = $i+1; $end = $i+50000; ?>
                                            <?php $end = ($end < $rec_count)?$end:$rec_count; ?>
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
                    <button name="downloadReportBtn" id="downloadReportBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadReportData('/store/stock/details');">Download</button>
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
