@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store SKU Inventory Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store SKU Inventory Report');$page_name = 'store_sku_inventory_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <input type="text" name="sku" id="sku" class="form-control" placeholder="SKU" value="{{request('sku')}}" />
                    </div>
                    <?php /* ?>
                    <div class="col-md-2" >
                        <select name="inv_type" id="inv_type"  class="form-control " >
                            <option value="">-- All Inventory --</option>
                            <option <?php if(request('inv_type') == 1) echo 'selected'; ?> value="1">{{CommonHelper::getInventoryType(1)}}</option>
                            <option <?php if(request('inv_type') == 2) echo 'selected'; ?> value="2">{{CommonHelper::getInventoryType(2)}}</option>
                        </select>
                    </div><?php */ ?>
                    @if($user->user_type != 15)
                        <div class="col-md-2" >
                            <select name="v_id" id="v_id" class="form-control">
                                <option value="">-- All Vendors --</option>
                                @for($i=0;$i<count($vendor_list);$i++)
                                    <?php $sel = ($vendor_list[$i]['id'] == request('v_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2" >
                        <select name="s_id" id="s_id" class="form-control">
                            <option value="">-- All Stores --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div>
                    
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    <div class="col-md-1">
                        <a href="javascript:;" onclick="downloadReportData();" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a>
                    </div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                
                <div class="table-responsive table-filter" >
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0"  id="storeReportTbl" style="font-size:12px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>SNo</th>
                                <th>Image</th>
                                <th>Vendor</th>
                                <th>SKU</th>
                                <th>Vendor SKU</th>
                                <th>Store Name</th>
                                <th>Code</th>
                                <th>Store Rec WH</th>
                                <th>Store Rec Store</th>
                                <th>Sold Qty</th>
                                <th>Store to WH</th>
                                <th>Store to Store</th>
                                <th>Balance</th>
                                <th>In Stores</th>
                                <th>Sold Net Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 0; ?>
                            
                            <?php $total_array = array('wh_to_store'=>0,'store_to_store_rec'=>0,'sold_qty'=>0,'store_to_wh'=>0,'store_to_store'=>0,'bal_qty'=>0); ?>
                            
                            @for($i=0;$i<count($inv_list);$i++)
                                <tr>
                                    <td>@if($count == 0 || (isset($inv_list[$i-1]['product_sku']) && $inv_list[$i]['product_sku'] != $inv_list[$i-1]['product_sku'])) {{$sno++}} @endif</td>
                                    <td>
                                        @if(isset($images_list[$inv_list[$i]['product_id']]) && file_exists(public_path('images/pos_product_images/'.$inv_list[$i]['product_id'].'/'.$images_list[$inv_list[$i]['product_id']]->image_name)))
                                            <a href="javascript:;" onclick="displayDialogImage('{{url('images/pos_product_images/'.$inv_list[$i]['product_id'].'/'.$images_list[$inv_list[$i]['product_id']]->image_name)}}')">
                                                <img src="{{url('images/pos_product_images/'.$inv_list[$i]['product_id'].'/thumbs/'.$images_list[$inv_list[$i]['product_id']]->image_name)}}" class="img-thumbnail" style="max-width: 50px;">
                                            </a>
                                        @endif 
                                    </td>
                                    <td>{{isset($vendor_id_list[$inv_list[$i]['vendor_id']])?$vendor_id_list[$inv_list[$i]['vendor_id']]['name']:''}}</td>
                                    <td>{{$inv_list[$i]['product_sku']}}</td>
                                    <td>{{$inv_list[$i]['vendor_product_sku']}}</td>
                                    @if($inv_list[$i]['wh_to_store_qty'] > 0 || $inv_list[$i]['store_to_store_rec_qty'] > 0)
                                        <td>{{$store_id_list[$inv_list[$i]['store_id']]['store_name']}}</td>
                                        <td>{{$store_id_list[$inv_list[$i]['store_id']]['store_id_code']}}</td>
                                        <td>{{$inv_list[$i]['wh_to_store_qty']}}</td>
                                        <td>{{$inv_list[$i]['store_to_store_rec_qty']}}</td>
                                        <td>{{$inv_list[$i]['inv_sold_count']}}</td>
                                        <td>{{$inv_list[$i]['store_to_wh_qty']}}</td>
                                        <td>{{$inv_list[$i]['store_to_store_qty']}}</td>
                                        <td>{{$bal_qty = ($inv_list[$i]['wh_to_store_qty']+$inv_list[$i]['store_to_store_rec_qty'])-($inv_list[$i]['inv_sold_count']+$inv_list[$i]['store_to_wh_qty']+$inv_list[$i]['store_to_store_qty'])}}</td>
                                        <td>{{$inv_list[$i]['inv_in_store_qty']}}</td>
                                        <td>{{round($inv_list[$i]['inv_sold_net_price'],2)}}</td>
                                        <?php $total_array['wh_to_store']+=$inv_list[$i]['wh_to_store_qty']; ?>
                                        <?php $total_array['store_to_store_rec']+=$inv_list[$i]['store_to_store_rec_qty']; ?>
                                        <?php $total_array['sold_qty']+=$inv_list[$i]['inv_sold_count']; ?>
                                        <?php $total_array['store_to_wh']+=$inv_list[$i]['store_to_wh_qty']; ?>
                                        <?php $total_array['store_to_store']+=$inv_list[$i]['store_to_store_qty']; ?>
                                        <?php $total_array['bal_qty']+=$bal_qty; ?>
                                    @endif
                                </tr>
                                <?php $count++; ?>
                            @endfor
                        </tbody>    
                        <tfoot>
                            <tr>
                                <th colspan="7">Page Total</th>
                                <th>{{$total_array['wh_to_store']}}</th>
                                <th>{{$total_array['store_to_store_rec']}}</th>
                                <th>{{$total_array['sold_qty']}}</th> 
                                <th>{{$total_array['store_to_wh']}}</th> 
                                <th>{{$total_array['store_to_store']}}</th>
                                <th>{{$total_array['bal_qty']}}</th>
                                <th></th> 
                            </tr>
                            @if(!empty($search_array))
                                <tr>
                                    <th colspan="7">Total</th>
                                    <th>{{$total_data['wh_to_store']}}</th>
                                    <th>{{$total_data['store_to_store_rec']}}</th>
                                    <th>{{$total_data['inv_sold_count']}}</th>
                                    <th>{{$total_data['store_to_wh']+$total_data['store_to_wh_comp']}}</th>
                                    <th>{{$total_data['store_to_store']}}</th>
                                    <th>{{($total_data['wh_to_store']+$total_data['store_to_store_rec'])-($total_data['inv_sold_count']+$total_data['store_to_wh']+$total_data['store_to_wh_comp']+$total_data['store_to_store'])}}</th>
                                    <th>{{isset($total_data['inv_in_store_count'])?$total_data['inv_in_store_count']:0}}</th>
                                </tr>
                            @endif
                        </tfoot>
                    </table>

                    {{ $sku_list->withQueryString()->links() }}
                    <p>Displaying {{$sku_list->count()}} of {{ $sku_list->total() }} SKUs.</p>
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
                    <button name="downloadReportBtn" id="downloadReportBtn" value="Download Inventory" class="btn btn-dialog" onclick="submitDownloadReportData('/store/sku/inventory/report');">Download</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
