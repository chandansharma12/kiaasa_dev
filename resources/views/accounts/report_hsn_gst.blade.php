@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'HSN GST Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'HSN GST Report');$page_name = 'hsn_gst_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <style type="text/css">.select2 .selection {display:block;}</style>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <select name="state_id" id="state_id" class="form-control" onchange="getStateStoresHSNReport(this.value,'');">
                            <option value="">-- All States --</option>
                            @foreach($states as $id=>$name)
                                <?php $sel = ($id == request('state_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div> 
                     <div class="col-md-6">
                        <select name="s_id[]" id="s_id" class="form-control js-example-responsive" multiple="multiple" style="width:100%;">
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = (is_array(request('s_id')) && in_array($store_list[$i]['id'],request('s_id')))?'selected':''; ?>
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
                                <th>Store</th>
                                <th>State</th>
                                <th>HSN</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Total Value</th>
                                <th>Taxable Value</th>
                                <th>Tax Rate</th>
                                <th>GST Amount</th>
                                <th>IGST</th>
                                <th>CGST</th>
                                <th>SGST</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = array('qty'=>0,'taxable_value'=>0,'gst_amount'=>0,'total_value'=>0,'i_gst_amount'=>0,'s_gst_amount'=>0); ?>
                            @for($i=0;$i<count($orders_data);$i++)
                                <tr>
                                    <td>{{$orders_data[$i]['store_name']}} ({{$orders_data[$i]['store_id_code']}})</td>
                                    <td>{{$states[$orders_data[$i]['state_id']]}}</td>
                                    <td>{{$orders_data[$i]['hsn_code']}}</td>
                                    <td>GARMENTS</td>
                                    <td>{{$orders_data[$i]['qty']}}</td>
                                    <td>{{round($orders_data[$i]['taxable_value']+$orders_data[$i]['gst_amount'],2)}}</td>
                                    <td>{{round($orders_data[$i]['taxable_value'],2)}}</td>
                                    <td>{{$orders_data[$i]['gst_percent']}}</td>
                                    <td>{{round($orders_data[$i]['gst_amount'],2)}}</td>
                                    <td>{{round($orders_data[$i]['i_gst_amount'],2)}}</td>
                                    <td>{{round($orders_data[$i]['s_gst_amount']/2,2)}}</td>
                                    <td>{{round($orders_data[$i]['s_gst_amount']/2,2)}}</td>
                                </tr>
                                <?php $total['qty']+=$orders_data[$i]['qty'];
                                $total['taxable_value']+=$orders_data[$i]['taxable_value'];
                                $total['gst_amount']+=$orders_data[$i]['gst_amount'];
                                $total['total_value']+=($orders_data[$i]['taxable_value']+$orders_data[$i]['gst_amount']);
                                $total['i_gst_amount']+=$orders_data[$i]['i_gst_amount'];
                                $total['s_gst_amount']+=$orders_data[$i]['s_gst_amount']; ?>
                            @endfor
                        </tbody>    
                        <tfoot>
                            <tr>
                                <th colspan="4">Page Total</th>
                                <th>{{$total['qty']}}</th>
                                <th>{{round($total['total_value'],2)}}</th>
                                <th>{{round($total['taxable_value'],2)}}</th>
                                <th></th>
                                <th>{{round($total['gst_amount'],2)}}</th>
                                <th>{{round($total['i_gst_amount'],2)}}</th> 
                                <th>{{round($total['s_gst_amount']/2,2)}}</th> 
                                <th>{{round($total['s_gst_amount']/2,2)}}</th> 
                            </tr>
                        </tfoot>
                    </table>
                    @if(!empty($orders_list))
                    {{ $orders_list->withQueryString()->links() }}
                        <p>Displaying {{$orders_list->count()}} of {{ $orders_list->total() }} records.</p>
                    @endif    
                    <br/>    
                </div>
            </div>
        </div>
    </section>

    <?php $records_count = !empty($orders_list)?$orders_list->total():1; ?>
    <?php echo CommonHelper::displayDownloadDialogHtml($records_count,1000,'/report/gst/hsn','Download HSN GST Report','Records'); ?>


@endif

@endsection

@section('scripts')
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}"  ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
<script src="{{asset('js/setting.js')}}"></script>
<?php $store_ids = request('s_id')!= ''?implode(',',request('s_id')):'' ?>
<script type="text/javascript">@if(request('state_id') != '') getStateStoresHSNReport({{request('state_id')}},"{{$store_ids}}"); @endif</script>
<script src="{{ asset('js/select2.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/select2.min.css') }}" />
<script>
    $(document).ready(function() {$('#s_id').select2();});
</script>    
@endsection
