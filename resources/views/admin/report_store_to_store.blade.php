@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store to Store Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store to Store Sales Report - '. strtoupper(str_replace('_',' ',$report_type))); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                   <div class="col-md-3" id="store_from_div">
                        <select name="from_store_id" id="from_store_id" class="form-control">
                            <option value="">-- From Store --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('from_store_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div> 
                    <div class="col-md-3" id="store_to_div">
                        <select name="to_store_id" id="to_store_id" class="form-control">
                            <option value="">-- To Store --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('to_store_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div> 
                   
                    <div class="col-md-2" id="date_range_div">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif" autocomplete="off">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif" autocomplete="off">
                        </div>
                    </div>
                    <input type="hidden" name="report_type" id="report_type" value="{{$report_type}}">
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <div class="col-md-1"><a href="javascript:;" onclick="downloadStoreToStoreReport();" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                
                <div id="ordersList">
                    <div style="width:5550px">&nbsp;</div>
                    <div class="table-responsive table-filter" style="width:5500px;">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size: 12px;">
                            <thead><tr class="header-tr">
                                <th>Supplier Name</th>
                                <th>Supplier Location</th>
                                <th>Supplier Code</th>
                                <th>Supplier State</th>
                                <th>Supplier GST No</th>
                                <th>Recipient Name</th>
                                <th>Recipient Location</th>
                                 <th>Recipient Code</th>
                                <th>Recipient State</th>
                                <th>Recipient GST No</th>
                                <th>Bill No</th>
                                
                                @if($report_type == 'hsn_code')
                                    <th>HSN Code</th>
                                @endif
                                <th>Bill Date</th>
                                <th>Sale Qty</th>
                                
                                <th>Qty 3%</th>
                                <th>Taxable Value 3%</th>
                                <th>IGST 3%</th>
                                <th>CGST 1.5%</th>
                                <th>SGST 1.5%</th>
                                <th>Total Tax 3%</th>
                                <th>Total Value 3%</th>
                                
                                <th>Qty 5%</th>
                                <th>Taxable Value 5%</th>
                                <th>IGST 5%</th>
                                <th>CGST 2.5%</th>
                                <th>SGST 2.5%</th>
                                <th>Total Tax 5%</th>
                                <th>Total Value 5%</th>
                                
                                <th>Qty 12%</th>
                                <th>Taxable Value 12%</th>
                                <th>IGST 12%</th>
                                <th>CGST 6%</th>
                                <th>SGST 6%</th>
                                <th>Total Tax 12%</th>
                                <th>Total Value 12%</th>
                                
                                <th>Qty 18%</th>
                                <th>Taxable Value 18%</th>
                                <th>IGST 18%</th>
                                <th>CGST 9%</th>
                                <th>SGST 9%</th>
                                <th>Total Tax 18%</th>
                                <th>Total Value 18%</th>
                                
                                <th>Qty 0%</th>
                                <th>Taxable Value 0%</th>
                                <th>IGST 0%</th>
                                <th>CGST 0%</th>
                                <th>SGST 0%</th>
                                <th>Total Tax 0%</th>
                                <th>Total Value 0%</th>
                                <th>Total Net Amount</th>
                            </tr></thead>
                            <tbody>
                                <?php $total_data = array('units_total'=>0,
                                'taxable_value_3'=>0,'igst_3'=>0,'cgst_1_5'=>0,'sgst_1_5'=>0,'total_tax_3'=>0,'total_value_3'=>0,    
                                'taxable_value_5'=>0,'igst_5'=>0,'cgst_2_5'=>0,'sgst_2_5'=>0,'total_tax_5'=>0,'total_value_5'=>0,
                                'taxable_value_12'=>0,'igst_12'=>0,'cgst_6'=>0,'sgst_6'=>0,'total_tax_12'=>0,'total_value_12'=>0,
                                'taxable_value_18'=>0,'igst_18'=>0,'cgst_9'=>0,'sgst_9'=>0,'total_tax_18'=>0,'total_value_18'=>0,'taxable_value_0'=>0,'igst_0'=>0,'cgst_0'=>0,    
                                'sgst_0'=>0,'total_tax_0'=>0,'total_value_0'=>0,'total_net_amount'=>0,'qty_3'=>0,'qty_5'=>0,'qty_12'=>0,'qty_18'=>0,'qty_0'=>0); ?>
                                
                                @for($i=0;$i<count($invoice_list);$i++)
                                    <?php $from_store_data = json_decode($invoice_list[$i]['from_store_data'],true); ?>
                                    <?php $to_store_data = json_decode($invoice_list[$i]['store_data'],true); ?>
                                    <?php $total_info = ($report_type == 'hsn_code')?json_decode($invoice_list[$i]['total_data_hsn'],true):json_decode($invoice_list[$i]['total_data'],true); ?>
                                    <?php $gst_type = CommonHelper::getGSTType($from_store_data['gst_no']); ?>
                                    <?php $taxable_value_3 = $igst_3 = $cgst_1_5 = $sgst_1_5 = $total_tax_3 = $total_value_3 = 0; ?>
                                    <?php $units_total = $taxable_value_5 = $igst_5 = $cgst_2_5 = $sgst_2_5 = $total_tax_5 = $total_value_5 = 0; ?>
                                    <?php $taxable_value_12 = $igst_12 = $cgst_6 = $sgst_6 = $total_tax_12 = $total_value_12 = 0; ?>
                                    <?php $taxable_value_18 = $igst_18 = $cgst_9 = $sgst_9 = $total_tax_18 = $total_value_18 = 0; ?>
                                    <?php $taxable_value_0 = $igst_0 = $cgst_0 = $sgst_0 = $total_tax_0 = $total_value_0 = 0; ?>
                                    <?php $qty_3 = $qty_5 = $qty_12 = $qty_18 = $qty_0 = 0; ?>
                                    <?php $hsn_code = isset($invoice_list[$i]['hsn_code'])?$invoice_list[$i]['hsn_code']:''; ?>
                                    
                                    <tr>
                                        <td>{{$from_store_data['gst_name']}}</td>
                                        <td>{{$from_store_data['store_name']}}</td>
                                        <td>{{$store_id_list[$invoice_list[$i]['from_store_id']]['store_id_code']}}</td>
                                        <td>{{$state_list[$from_store_data['state_id']]}}</td>
                                        <td>{{$from_store_data['gst_no']}}</td>
                                        <td>{{$to_store_data['gst_name']}}</td>
                                        <td>{{$to_store_data['store_name']}}</td>
                                         <td>{{$store_id_list[$invoice_list[$i]['store_id']]['store_id_code']}}</td>
                                        <td>{{$state_list[$to_store_data['state_id']]}}</td>
                                        <td>{{$to_store_data['gst_no']}}</td>
                                        <td>{{$invoice_list[$i]['invoice_no']}}</td>
                                        
                                        @if($report_type == 'hsn_code')
                                            <td>{{$invoice_list[$i]['hsn_code']}}</td>
                                        @endif
                                        <td>{{date('d-m-Y',strtotime($invoice_list[$i]['created_at']))}}</td>
                                        <td>{{$units_total = ($report_type == 'bill')?$total_info['total_qty']:$total_info['total_data'][$hsn_code]['total_qty']}} </td>
                                        
                                        <?php $key = ($report_type == 'bill')?'3':$hsn_code.'_3'; ?>
                                        @if(isset($total_info['taxable_value_'.$key]))
                                            <td>{{$qty_3 = $total_info['qty_'.$key]}}</td>
                                            <td>{{$taxable_value_3 = $total_info['taxable_value_'.$key]}}</td>
                                            <td>@if($gst_type == 2) {{$igst_3 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$cgst_1_5 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$sgst_1_5 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$total_tax_3 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>{{$total_value_3 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key]}}</td>
                                        @else
                                            <td colspan="7"></td>
                                        @endif 
                                        
                                        <?php $key = ($report_type == 'bill')?'5':$hsn_code.'_5'; ?>
                                        @if(isset($total_info['taxable_value_'.$key]))
                                            <td>{{$qty_5 = $total_info['qty_'.$key]}}</td>
                                            <td>{{$taxable_value_5 = $total_info['taxable_value_'.$key]}}</td>
                                            <td>@if($gst_type == 2) {{$igst_5 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$cgst_2_5 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$sgst_2_5 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$total_tax_5 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>{{$total_value_5 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key]}}</td>
                                        @else
                                            <td colspan="7"></td>
                                        @endif 
                                        
                                        <?php $key = ($report_type == 'bill')?'12':$hsn_code.'_12'; ?>
                                        @if(isset($total_info['taxable_value_'.$key]))
                                            <td>{{$qty_12 = $total_info['qty_'.$key]}}</td>
                                            <td>{{$taxable_value_12 = $total_info['taxable_value_'.$key]}}</td>
                                            <td>@if($gst_type == 2) {{$igst_12 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$cgst_6 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$sgst_6 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$total_tax_12 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>{{$total_value_12 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key]}}</td>
                                        @else
                                            <td colspan="7"></td>
                                        @endif 
                                        
                                        <?php $key = ($report_type == 'bill')?'18':$hsn_code.'_18'; ?>
                                        @if(isset($total_info['taxable_value_'.$key]))
                                            <td>{{$qty_18 = $total_info['qty_'.$key]}}</td>
                                            <td>{{$taxable_value_18 = $total_info['taxable_value_'.$key]}}</td>
                                            <td>@if($gst_type == 2) {{$igst_18 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$cgst_9 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$sgst_9 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$total_tax_18 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>{{$total_value_18 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key]}}</td>
                                        @else
                                            <td colspan="7"></td>
                                        @endif 
                                        
                                        <?php $key = ($report_type == 'bill')?'0':$hsn_code.'_0'; ?>
                                        @if(isset($total_info['taxable_value_'.$key]))
                                            <td>{{$qty_0 = $total_info['qty_'.$key]}}</td>
                                            <td>{{$taxable_value_0 = $total_info['taxable_value_'.$key]}}</td>
                                            <td>@if($gst_type == 2) {{$igst_0 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$cgst_0 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$sgst_0 = round($total_info['gst_amount_'.$key]/2,2)}}  @endif</td>
                                            <td>@if($gst_type == 1) {{$total_tax_0 = $total_info['gst_amount_'.$key]}}  @endif</td>
                                            <td>{{$total_value_0 = $total_info['taxable_value_'.$key]+$total_info['gst_amount_'.$key]}}</td>
                                        @else
                                            <td colspan="7"></td>
                                        @endif 
                                        
                                        <td>{{$total_net_amount = ($report_type == 'bill')?$total_info['total_value']:$total_info['total_data'][$hsn_code]['total_value']}}</td>
                                        
                                    </tr>
                                    <?php $total_data['units_total']+=$units_total;
                                    
                                        $total_data['qty_3']+=$qty_3; 
                                        $total_data['taxable_value_3']+=$taxable_value_3; 
                                        $total_data['igst_3']+=$igst_3; 
                                        $total_data['cgst_1_5']+=$cgst_1_5; 
                                        $total_data['sgst_1_5']+=$sgst_1_5; 
                                        $total_data['total_tax_3']+=$total_tax_3; 
                                        $total_data['total_value_3']+=$total_value_3; 
                                        
                                        $total_data['qty_5']+=$qty_5; 
                                        $total_data['taxable_value_5']+=$taxable_value_5; 
                                        $total_data['igst_5']+=$igst_5; 
                                        $total_data['cgst_2_5']+=$cgst_2_5; 
                                        $total_data['sgst_2_5']+=$sgst_2_5; 
                                        $total_data['total_tax_5']+=$total_tax_5; 
                                        $total_data['total_value_5']+=$total_value_5; 
                                        
                                        $total_data['qty_12']+=$qty_12; 
                                        $total_data['taxable_value_12']+=$taxable_value_12; 
                                        $total_data['igst_12']+=$igst_12; 
                                        $total_data['cgst_6']+=$cgst_6; 
                                        $total_data['sgst_6']+=$sgst_6; 
                                        $total_data['total_tax_12']+=$total_tax_12; 
                                        $total_data['total_value_12']+=$total_value_12; 
                                        
                                        $total_data['qty_18']+=$qty_18; 
                                        $total_data['taxable_value_18']+=$taxable_value_18; 
                                        $total_data['igst_18']+=$igst_18; 
                                        $total_data['cgst_9']+=$cgst_9; 
                                        $total_data['sgst_9']+=$sgst_9; 
                                        $total_data['total_tax_18']+=$total_tax_18; 
                                        $total_data['total_value_18']+=$total_value_18; 
                                        
                                        $total_data['qty_0']+=$qty_0; 
                                        $total_data['taxable_value_0']+=$taxable_value_0; 
                                        $total_data['igst_0']+=$igst_0; 
                                        $total_data['cgst_0']+=$cgst_0; 
                                        $total_data['sgst_0']+=$sgst_0; 
                                        $total_data['total_tax_0']+=$total_tax_0; 
                                        $total_data['total_value_0']+=$total_value_0; 
                                        $total_data['total_net_amount']+=$total_net_amount; 
                                    ?>
                                @endfor
                                <tr>
                                    @if($report_type == 'hsn_code')
                                        <th colspan="13">Total</th>
                                    @else
                                        <th colspan="12">Total</th>
                                    @endif
                                    <th>{{$total_data['units_total']}}</th>
                                    <th>{{$total_data['qty_3']}}</th>
                                    <th>{{$total_data['taxable_value_3']}}</th>
                                    <th>{{$total_data['igst_3']}}</th>
                                    <th>{{$total_data['cgst_1_5']}}</th>
                                    <th>{{$total_data['sgst_1_5']}}</th>
                                    <th>{{$total_data['total_tax_3']}}</th>
                                    <th>{{$total_data['total_value_3']}}</th>
                                    
                                    <th>{{$total_data['qty_5']}}</th>
                                    <th>{{$total_data['taxable_value_5']}}</th>
                                    <th>{{$total_data['igst_5']}}</th>
                                    <th>{{$total_data['cgst_2_5']}}</th>
                                    <th>{{$total_data['sgst_2_5']}}</th>
                                    <th>{{$total_data['total_tax_5']}}</th>
                                    <th>{{$total_data['total_value_5']}}</th>
                                    
                                    <th>{{$total_data['qty_12']}}</th>
                                    <th>{{$total_data['taxable_value_12']}}</th>
                                    <th>{{$total_data['igst_12']}}</th>
                                    <th>{{$total_data['cgst_6']}}</th>
                                    <th>{{$total_data['sgst_6']}}</th>
                                    <th>{{$total_data['total_tax_12']}}</th>
                                    <th>{{$total_data['total_value_12']}}</th>
                                    
                                    <th>{{$total_data['qty_18']}}</th>
                                    <th>{{$total_data['taxable_value_18']}}</th>
                                    <th>{{$total_data['igst_18']}}</th>
                                    <th>{{$total_data['cgst_9']}}</th>
                                    <th>{{$total_data['sgst_9']}}</th>
                                    <th>{{$total_data['total_tax_18']}}</th>
                                    <th>{{$total_data['total_value_18']}}</th>
                                    
                                    <th>{{$total_data['qty_0']}}</th>
                                    <th>{{$total_data['taxable_value_0']}}</th>
                                    <th>{{$total_data['igst_0']}}</th>
                                    <th>{{$total_data['cgst_0']}}</th>
                                    <th>{{$total_data['sgst_0']}}</th>
                                    <th>{{$total_data['total_tax_0']}}</th>
                                    <th>{{$total_data['total_value_0']}}</th>
                                    <th>{{$total_data['total_net_amount']}}</th>
                                </tr>
                            </tbody>
                        </table>
                        
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="report_download_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download <?php echo 'Store to Store Sales Report'.' ('.str_replace('_',' ',$report_type).')' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
            </div>

            <div class="alert alert-success alert-dismissible elem-hidden" id="reportDownloadSuccessMessage"></div>
            <div class="alert alert-danger alert-dismissible elem-hidden" id="reportDownloadErrorMessage"></div>

            <form class="" name="reportDownloadFrm" id="reportDownloadFrm" type="POST">
                <div class="modal-body">
                    <div class="form-row" >
                        <div class="form-group col-md-4" >
                            <label>From Store</label>
                            <div id="store_from_div_download"></div>
                            <div class="invalid-feedback" id="error_validation_store_from"></div>
                        </div>

                        <div class="form-group col-md-4" >
                            <label>To Store</label>
                            <div id="store_to_div_download"></div>
                            <div class="invalid-feedback" id="error_validation_store_to"></div>
                        </div>
                        <div class="form-group col-md-4" >
                            <label>Date</label>
                            <div id="date_range_div_download"></div>
                            <div class="invalid-feedback" id="error_validation_date_range"></div>
                        </div>
                    </div>    
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" id="report_download_cancel" name="report_download_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id ="report_download_submit" name="report_download_submit" class="btn btn-dialog" onclick="submitDownloadStoreToStoreReport();">Submit</button>
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
<script src="{{ asset('js/users.js') }}" ></script>
@endsection