@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Product SKU Details')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Product SKU Details');$page_name = 'sku_details'; ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end">
                    <div class="col-md-2">
                        <select name="v_id" id="v_id" class="form-control">
                            <option value="">-- Vendor --</option>
                            @for($i=0;$i<count($vendor_list);$i++)
                                <?php if($vendor_list[$i]['id'] == request('v_id')) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                            @endfor   
                        </select>
                    </div>
                    
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2"><a href="javascript:;" onclick="downloadReportData();" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                    
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                
                <div class="table-responsive table-filter" >

                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size: 13px;">
                        <thead><tr class="header-tr"><th>S.No</th><th>Product SKU</th><th>Store Name</th><th>Code</th>
                        @for($i=0;$i<count($size_list);$i++)
                            <th>{{$size_list[$i]['size']}}</th>
                        @endfor    
                        <th>Total Inventory</th>
                        </tr></thead>
                        <tbody>
                            <?php $total_data = array('total'=>0);$count = 1;$store_sku = 0;$sku_sizes = array(); ?>
                            @for($i=0;$i<count($sku_list);$i++)
                                <?php $sku_sizes = array(); ?>
                                @for($q=0;$q<count($store_list);$q++)
                                    <?php $key = $store_list[$q]['id'].'_'.$sku_list[$i]; ?>

                                    @if(isset($sku_details_store[$key]))
                                        <tr>
                                            <td>{{$sno}}</td>
                                            <td>{{$sku_data[$sku_list[$i]]->product_sku}}</td>
                                            <td>{{$store_list[$q]['store_name']}}</td>
                                            <td>{{$store_list[$q]['store_id_code']}}</td>
                                            <?php $inv_sku = 0; ?>
                                            @for($z=0;$z<count($size_list);$z++)
                                                <?php $size_id = $size_list[$z]['id']; ?>
                                                <td>{{$inv = (isset($sku_details_store[$key][$size_id]))?$sku_details_store[$key][$size_id]:0 }}</td>
                                                <?php $store_sku+=$inv; ?>
                                                <?php $inv_sku+=$inv; ?>
                                                <?php $total_data['total']+=$inv; ?>
                                                <?php if(isset($sku_sizes[$size_id])) $sku_sizes[$size_id]+=$inv; else $sku_sizes[$size_id] = $inv; ?>
                                            @endfor    
                                            <td>{{$inv_sku}}</td>
                                        </tr>
                                    @endif

                                @endfor
                                <?php $sno++; ?>
                                <tr><th colspan="4">{{$sku_data[$sku_list[$i]]->product_sku}} Total</th>
                                    @for($z=0;$z<count($size_list);$z++)
                                        <?php $size_id = $size_list[$z]['id']; ?>
                                        <th>{{isset($sku_sizes[$size_id])?$sku_sizes[$size_id]:0}}</th>
                                    @endfor  
                                    <th>{{$store_sku}}</th>
                                </tr>
                                <?php $store_sku = 0; ?>
                            @endfor
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="{{count($size_list)+4}}">Page Total</th>
                                <th>{{$total_data['total']}}</th>
                            </tr>
                            @if(!empty($vendor_id))
                                <tr>
                                    <th colspan="{{count($size_list)+4}}">Total</th>
                                    <th>{{$inventory_list_total}}</th>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                    {{ $skus->withQueryString()->links() }}
                    <p>Displaying {{$skus->count()}} of {{ $skus->total() }} SKUs.</p>
                </div> 
                 
            </div>
        </div>
    </section>

    <div class="modal fade" id="downloadReportDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" >Download Product SKU Details Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadReportErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadReportSuccessMessage"></div>
                
                <form method="post" name="downloadReportForm" id="downloadReportForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>SKU Records</label>
                                <?php $sku_count = $skus->total(); ?>
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
                    <button name="downloadReportBtn" id="downloadReportBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadReportData('/product-sku/details');">Download</button>
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
