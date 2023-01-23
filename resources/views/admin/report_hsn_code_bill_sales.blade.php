@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'HSN Code Bill Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'HSN Code Bill Sales Report'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    @if($user->user_type != 9)
                        <div class="col-md-3" id="store_list_div">
                            <select name="s_id" id="s_id" class="form-control">
                                <option value="">-- Store --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2" id="date_range_div">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif" autocomplete="off">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-1"><a href="javascript:;" onclick="downloadHSNCodeBillSalesReport();" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                @if(!empty($search_date['start_date']) && !empty($search_date['end_date']))
                    <div style="width:1720px">&nbsp;</div>
                    <div class="table-responsive table-filter" style="width:1700px;">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0"  style="font-size: 12px;">
                            <thead>
                                <tr class="header-tr">
                                    <th>Bill Date</th>
                                    <th>Bill No</th>
                                    <th>Store Name</th>
                                    <th>Code</th>
                                    <th>Prod Name</th>
                                    <th>SKU</th>
                                    <th>QR Code</th>
                                    <th>GST%</th>
                                    <th>HSN</th>
                                    <th>MRP</th>
                                    <th>Qty</th>
                                    <th>MRP Val</th>
                                    <th>Discount</th>
                                    <th>Bill Val</th>
                                    <th>CGST Amt</th>
                                    <th>SGST Amt</th>
                                    <th>Taxable Val</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total = array('qty'=>0,'mrp_val'=>0,'disc'=>0,'bill_val'=>0,'gst'=>0,'taxable_val'=>0); ?>
                                <?php $total_bill = $total; ?>
                                @for($i=0;$i<count($bill_list);$i++)
                                    <tr>
                                        <td>{{date('d-m-Y',strtotime($bill_list[$i]->order_date))}}</td>
                                        <td>{{$bill_list[$i]->order_no}}</td>
                                        <td>{{$bill_list[$i]->store_name}}</td>
                                        <td>{{$bill_list[$i]->store_id_code}}</td>
                                        <td>{{$bill_list[$i]->product_name}}</td>
                                        <td>{{$bill_list[$i]->product_sku}}</td>
                                        <td>{{$bill_list[$i]->peice_barcode}}</td>
                                        <td>{{$bill_list[$i]->gst_percent}}</td>
                                        <td>{{$bill_list[$i]->hsn_code}}</td>
                                        <td>{{$bill_list[$i]->sale_price}}</td>
                                        <td>{{$qty = $bill_list[$i]->product_quantity}}</td>
                                        <td>{{$mrp_val = $bill_list[$i]->sale_price}}</td>
                                        <td>{{$disc = $bill_list[$i]->discount_amount_actual}}</td>
                                        <td>{{$net_price = $bill_list[$i]->net_price}}</td>
                                        <td>{{round($bill_list[$i]->gst_amount/2,6)}}</td>
                                        <td>{{round($bill_list[$i]->gst_amount/2,6)}}</td>
                                        <td>{{$taxable_val = $bill_list[$i]->discounted_price_actual}}</td>
                                    </tr>
                                    <?php $total_bill['qty']+=$qty; ?>
                                    <?php $total_bill['mrp_val']+=$mrp_val; ?>
                                    <?php $total_bill['disc']+=$disc; ?>
                                    <?php $total_bill['bill_val']+=$net_price; ?>
                                    <?php $total_bill['gst']+=$bill_list[$i]->gst_amount; ?>
                                    <?php $total_bill['taxable_val']+=$taxable_val; ?>

                                    <?php $total['qty']+=$qty; ?>
                                    <?php $total['mrp_val']+=$mrp_val; ?>
                                    <?php $total['disc']+=$disc; ?>
                                    <?php $total['bill_val']+=$net_price; ?>
                                    <?php $total['gst']+=$bill_list[$i]->gst_amount; ?>
                                    <?php $total['taxable_val']+=$taxable_val; ?>

                                    @if((isset($bill_list[$i+1]->order_id) && $bill_list[$i]->order_id != $bill_list[$i+1]->order_id) || !isset($bill_list[$i+1]->order_id) )
                                        <tr class="sub-header-tr">
                                            <td colspan="10">Bill Total</td>
                                            <td>{{$total_bill['qty']}}</td>
                                            <td>{{round($total_bill['mrp_val'],2)}}</td>
                                            <td>{{round($total_bill['disc'],2)}}</td>
                                            <td>{{round($total_bill['bill_val'],2)}}</td>
                                            <td>{{round($total_bill['gst']/2,2)}}</td>
                                            <td>{{round($total_bill['gst']/2,2)}}</td>
                                            <td>{{round($total_bill['taxable_val'],2)}}</td>
                                        </tr>
                                         <?php $total_bill = array('qty'=>0,'mrp_val'=>0,'disc'=>0,'bill_val'=>0,'gst'=>0,'taxable_val'=>0); ?>
                                    @endif
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr class="header-tr">
                                    <th colspan="10">Total</th>
                                    <th>{{$total['qty']}}</th>
                                    <th>{{round($total['mrp_val'],2)}}</th>
                                    <th>{{round($total['disc'],2)}}</th>
                                    <th>{{round($total['bill_val'],2)}}</th>
                                    <th>{{round($total['gst']/2,2)}}</th>
                                    <th>{{round($total['gst']/2,2)}}</th>
                                    <th>{{round($total['taxable_val'],2)}}</th>
                                </tr>
                            </tfoot>
                        </table>
                        <br/>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="modal fade " id="report_download_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download HSN Code Bill Sales Report</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
            </div>

            <div class="alert alert-success alert-dismissible elem-hidden" id="reportDownloadSuccessMessage"></div>
            <div class="alert alert-danger alert-dismissible elem-hidden" id="reportDownloadErrorMessage"></div>

            <form class="" name="reportDownloadFrm" id="reportDownloadFrm" type="POST">
                <div class="modal-body">
                    <div class="form-row" >
                        @if($user->user_type != 9)
                            <div class="form-group col-md-6" >
                                <label>Store</label>
                                <div id="store_list_div_download"></div>
                                <div class="invalid-feedback" id="error_validation_store_list"></div>
                            </div>
                        @endif
                        <div class="form-group col-md-6" >
                            <label>Date</label>
                            <div id="date_range_div_download"></div>
                            <div class="invalid-feedback" id="error_validation_date_range"></div>
                        </div>
                    </div>    
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" id="report_download_cancel" name="report_download_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id ="report_download_submit" name="report_download_submit" class="btn btn-dialog" onclick="submitDownloadHSNCodeBillSalesReport();">Submit</button>
                </div>
            </form>
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
<script src="{{ asset('js/pos_product.js?v=2.1') }}" ></script>
@endsection
