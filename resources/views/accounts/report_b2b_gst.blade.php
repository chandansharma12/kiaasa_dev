@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'B2B GST Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'B2B GST Report');$page_name = 'b2b_gst_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                     <div class="col-md-2">
                        <select name="s_id" id="s_id" class="form-control">
                            <option value="">-- All Stores --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div> 
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" autocomplete="off" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" autocomplete="off" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <div class="col-md-1">
                        <?php echo CommonHelper::displayDownloadDialogButton(); ?>
                    </div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:12px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>Recipient GST No</th>
                                <th>Invoice Number</th>
                                <th>Invoice Date</th>
                                <th>Invoice Value</th>
                                <th>Place of Supply</th>
                                <th>Rate</th>
                                <th>Qty</th>
                                <th>Taxable Value</th>
                                <th>GST Amount</th>
                                <th>IGST</th>
                                <th>CGST</th>
                                <th>SGST</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = array('qty'=>0,'taxable_value'=>0,'gst_amount'=>0,'total_value'=>0,'igst'=>0,'cgst'=>0,'sgst'=>0); ?>
                            @for($i=0;$i<count($invoice_listing);$i++)
                                <tr>
                                    <td>{{$invoice_listing[$i]['gst_no']}}</td>
                                    <td>{{$invoice_listing[$i]['invoice_no']}}</td>
                                    <td>{{date('d-m-Y',strtotime($invoice_listing[$i]['created_at']))}}</td>
                                    <td>{{$invoice_listing[$i]['invoice_value']}}</td>
                                    <td>09-Uttar Pradesh</td>
                                    <td>{{$invoice_listing[$i]['rate']}}</td>
                                    <td>{{$invoice_listing[$i]['qty']}}</td>
                                    <td>{{$invoice_listing[$i]['taxable_value']}}</td>
                                    <td>{{$invoice_listing[$i]['gst_amount']}}</td>
                                    <td>{{$invoice_listing[$i]['igst']}}</td>
                                    <td>{{$invoice_listing[$i]['cgst']}}</td>
                                    <td>{{$invoice_listing[$i]['sgst']}}</td>
                                    <td>{{$invoice_listing[$i]['taxable_value']+$invoice_listing[$i]['gst_amount']}}</td>
                                </tr>
                                <?php $total['qty']+=$invoice_listing[$i]['qty'];
                                $total['taxable_value']+=$invoice_listing[$i]['taxable_value'];
                                $total['gst_amount']+=$invoice_listing[$i]['gst_amount'];
                                $total['total_value']+=($invoice_listing[$i]['taxable_value']+$invoice_listing[$i]['gst_amount']); 
                                $total['igst'] = $total['igst']+($invoice_listing[$i]['igst'] > 0?$invoice_listing[$i]['igst']:0);
                                $total['cgst'] = $total['cgst']+($invoice_listing[$i]['cgst'] > 0?$invoice_listing[$i]['cgst']:0);
                                $total['sgst'] = $total['sgst']+($invoice_listing[$i]['sgst'] > 0?$invoice_listing[$i]['sgst']:0);
                                ?>
                            @endfor
                        </tbody>    
                        <tfoot>
                            <tr>
                                <th colspan="6">Page Total</th>
                                <th>{{$total['qty']}}</th>
                                <th>{{round($total['taxable_value'],2)}}</th>
                                <th>{{round($total['gst_amount'],2)}}</th>
                                <th>{{round($total['igst'],2)}}</th>
                                <th>{{round($total['cgst'],2)}}</th>
                                <th>{{round($total['sgst'],2)}}</th>
                                <th>{{round($total['total_value'],2)}}</th>
                            </tr>
                        </tfoot>
                    </table>
                    {{ $invoice_list->withQueryString()->links() }}
                        <p>Displaying {{$invoice_list->count()}} of {{ $invoice_list->total() }} Invoices.</p>
                    <br/>
                </div>
            </div>
        </div>
    </section>
    
    <?php echo CommonHelper::displayDownloadDialogHtml($invoice_list->total(),10000,'/report/gst/b2b','Download B2B GST Report','Invoice Records'); ?>
    <?php /* ?><div class="modal fade" id="downloadReportDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Download B2B GST Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadReportErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadReportSuccessMessage"></div>
                
                <form method="post" name="downloadReportForm" id="downloadReportForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Invoice Records</label>
                                <?php $invoice_count = $invoice_list->total(); ?>
                                <select name="report_rec_count" id="report_rec_count" class="form-control" >
                                    <option value="">-- Invoice Records --</option>
                                        @for($i=0;$i<=$invoice_count;$i=$i+10000) 
                                            <?php $start = $i+1; $end = $i+10000; ?>
                                            <?php $end = ($end < $invoice_count)?$end:$invoice_count; ?>
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
                    <button name="downloadReportBtn" id="downloadReportBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadReportData('/report/gst/b2b');">Download</button>
                </div>
            </div>
        </div>
    </div><?php */ ?>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
