@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendor SKU Inventory Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendor SKU Inventory Report');$page_name = 'vendor_sku_inventory_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    @if($user->user_type != 15)
                        <div class="col-md-2" >
                            <select name="v_id" id="v_id" class="form-control">
                                <option value="">All Vendors</option>
                                @for($i=0;$i<count($vendor_list);$i++)
                                    <?php $sel = ($vendor_list[$i]['id'] == request('v_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    @endif
                   
                    <div class="col-md-1">
                        <a href="javascript:;" onclick="downloadReportData();" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a>
                    </div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                    <div style="width:1900px">&nbsp;</div>
                    <div class="table-responsive table-filter" style="width:1880px;">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0"  id="storeReportTbl" style="font-size:12px;">
                            <thead>
                                <tr class="header-tr">
                                    <th>SNo</th>
                                    <th>Image</th>
                                    <th>Vendor</th>
                                    <th>SKU</th>
                                    <th>Vendor SKU</th>
                                    <th>Cost Rate</th>
                                    <th>GST Amount</th>
                                    <th>Cost Price</th>
                                    <th>GRN QTY</th>
                                    <th>QC Defective</th>
                                    <th>QC Returned</th>
                                    <th>WH Qty</th>
                                    <th>Store Qty</th>
                                    <th>Sold Qty</th>
                                    <th>Transit Transfer</th>
                                    <th>Transit Return</th>
                                    <th>Returned to Vendor</th>
                                    <th>Total</th>
                                    <th>Bal Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_array = array('grn_qty'=>0,'w_qty'=>0,'store_qty'=>0,'sold_qty'=>0,'total_qty'=>0,'bal_qty'=>0,'transit_transfer_qty'=>0,'transit_return_qty'=>0,'return_vendor_qty'=>0,'qc_defective_qty'=>0,'qc_returned_qty'=>0); ?>
                                @for($i=0;$i<count($sku_list);$i++)
                                    <tr>
                                        <td>{{$sno+$i}}</td>
                                        <td>
                                           @if(isset($images_list[$sku_list[$i]->product_id]) && file_exists(public_path('images/pos_product_images/'.$sku_list[$i]->product_id.'/'.$images_list[$sku_list[$i]->product_id]->image_name)))
                                                <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$sku_list[$i]->product_id.'/'.$images_list[$sku_list[$i]->product_id]->image_name)}}')">
                                                    <img src="{{url('images/pos_product_images/'.$sku_list[$i]->product_id.'/thumbs/'.$images_list[$sku_list[$i]->product_id]->image_name)}}" class="img-thumbnail" style="max-width: 50px;">
                                                </a>
                                            @endif 
                                    
                                        </td>
                                        
                                        <td>{{$sku_list[$i]->vendor_name}}</td>
                                        <td>{{$sku_list[$i]->vendor_sku}}</td>
                                        <td>{{$sku_list[$i]->vendor_product_sku}}</td>
                                        <td>{{$sku_list[$i]->rate}}</td>
                                        <td>{{$gst_amt = round($sku_list[$i]->rate*($sku_list[$i]->gst_percent/100),2)}}</td>
                                        <td>{{$sku_list[$i]->rate+$gst_amt}}</td>
                                        
                                        <td>{{$sku_list[$i]->inv_grn}}</td>
                                        <td>{{$sku_list[$i]->qc_defective}}</td>
                                        <td>{{$sku_list[$i]->qc_returned}}</td>
                                        <td>{{$wh_qty = $sku_list[$i]->inv_status_1+$sku_list[$i]->inv_status_2}}</td>
                                        <td>{{$sku_list[$i]->inv_status_4}}</td>
                                        <td>{{$sku_list[$i]->inv_status_5}}</td>
                                        <td>{{$sku_list[$i]->inv_status_3}}</td>
                                        <td>{{$sku_list[$i]->inv_status_6}}</td>
                                        <td>{{$sku_list[$i]->inv_status_7}}</td>
                                        <?php $qty_received = $sku_list[$i]->inv_grn-$sku_list[$i]->qc_defective ?>
                                        <td>{{$total_qty = $sku_list[$i]->inv_status_1+$sku_list[$i]->inv_status_2+$sku_list[$i]->inv_status_3+$sku_list[$i]->inv_status_4+$sku_list[$i]->inv_status_5+$sku_list[$i]->inv_status_6+$sku_list[$i]->inv_status_7}}</td>
                                        <td>{{$bal_qty = $qty_received-$total_qty}}</td>
                                    </tr>
                                    <?php $total_array['grn_qty']+= $sku_list[$i]->inv_grn; ?>
                                    <?php $total_array['w_qty']+= $wh_qty; ?>
                                    <?php $total_array['store_qty']+= $sku_list[$i]->inv_status_4; ?>
                                    <?php $total_array['sold_qty']+= $sku_list[$i]->inv_status_5; ?>
                                    <?php $total_array['transit_transfer_qty']+= $sku_list[$i]->inv_status_3; ?>
                                    <?php $total_array['transit_return_qty']+= $sku_list[$i]->inv_status_6; ?>
                                    <?php $total_array['return_vendor_qty']+= $sku_list[$i]->inv_status_7; ?>
                                    <?php $total_array['qc_defective_qty']+= $sku_list[$i]->qc_defective; ?>
                                    <?php $total_array['qc_returned_qty']+= $sku_list[$i]->qc_returned; ?>
                                    <?php $total_array['total_qty']+= $total_qty; ?>
                                    <?php $total_array['bal_qty']+= $bal_qty; ?>
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr>
                                    <th colspan="8">Page Total</th>
                                    <th>{{$total_array['grn_qty']}}</th>
                                    <th>{{$total_array['qc_defective_qty']}}</th>
                                    <th>{{$total_array['qc_returned_qty']}}</th>
                                    <th>{{$total_array['w_qty']}}</th>
                                    <th>{{$total_array['store_qty']}}</th>
                                    <th>{{$total_array['sold_qty']}}</th>
                                    <th>{{$total_array['transit_transfer_qty']}}</th>
                                    <th>{{$total_array['transit_return_qty']}}</th>
                                    <th>{{$total_array['return_vendor_qty']}}</th>
                                    <th>{{$total_array['total_qty']}}</th>
                                    <th>{{$total_array['bal_qty']}}</th>
                                </tr>
                                @if(!empty($vendor_id))
                                <tr>
                                    <th colspan="8">Total</th>
                                    <th>{{$vendor_total_data['grn_qty']}}</th>
                                    <th>{{$vendor_total_data['qc_defective']}}</th>
                                    <th>{{$vendor_total_data['qc_returned']}}</th>
                                    <th>{{$inventory_total_data['inv_status_1']+$inventory_total_data['inv_status_2']}}</th>
                                    <th>{{$inventory_total_data['inv_status_4']}}</th>
                                    <th>{{$inventory_total_data['inv_status_5']}}</th>
                                    <th>{{$inventory_total_data['inv_status_3']}}</th>
                                    <th>{{$inventory_total_data['inv_status_6']}}</th>
                                    <th>{{$inventory_total_data['inv_status_7']}}</th>
                                    <th>{{$total_qty = $inventory_total_data['inv_status_1']+$inventory_total_data['inv_status_2']+$inventory_total_data['inv_status_3']+$inventory_total_data['inv_status_4']+$inventory_total_data['inv_status_5']+$inventory_total_data['inv_status_6']+$inventory_total_data['inv_status_7']}}</th>
                                    <th>{{$bal_qty = ($vendor_total_data['grn_qty']-$vendor_total_data['qc_defective'])-$total_qty}}</th>
                                @endif    
                                </tr>
                            </tfoot>
                        </table>
                        {{ $sku_list->withQueryString()->links() }}
                        <p>Displaying {{$sku_list->count()}} of {{ $sku_list->total() }} SKU.</p>
                    </div>
                
            </div>
        </div>
    </section>

    <div class="modal fade" id="downloadReportDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" >Download Store SKU Inventory Report</h5>
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
                    <button name="downloadReportBtn" id="downloadReportBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadReportData('/vendor/sku/inventory/report');">Download</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
