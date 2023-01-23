@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Stores Inventory Status Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Stores Inventory Status Report');$page_name = 'stores_inventory_status_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    @if($user->user_type != 9)
                        <div class="col-md-2" >
                            <select name="s_id" id="s_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2" >
                            <select name="v_id" id="v_id" class="form-control">
                                <option value="">All Vendors</option>
                                @for($i=0;$i<count($vendors_list);$i++)
                                    <?php $sel = ($vendors_list[$i]['id'] == request('v_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$vendors_list[$i]['id']}}">{{$vendors_list[$i]['name']}}</option>
                                @endfor
                            </select>
                        </div>
                        @if(request('v_id') == '15')
                            <div class="col-md-2" >
                                <select name="inv_type" id="inv_type" class="form-control">
                                    <option value="">Inventory Type</option>
                                    <option <?php if(request('inv_type') == 'arnon'): ?> selected <?php endif; ?> value="arnon">Arnon</option>
                                    <option <?php if(request('inv_type') == 'north'): ?> selected <?php endif; ?> value="north">Northcorp</option>
                                </select>
                            </div>
                        @endif
                    @endif
                    <?php /* ?>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div> <?php */ ?>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    <div class="col-md-2"><a href="{{url('store/report/inventory/status?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                     <?php $status_list = array('2'=>'Reserved for Store','3'=>'Transit to Store','4'=>'Ready for Sale by Store','5'=>'Sold from Store') ?>
                    <?php $statuses_total = ['2'=>0,'3'=>0,'4'=>0,'5'=>0]; ?>
                    <?php $grand_total = $store_total = $return_total_stores = 0; ?>
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl">
                            <thead>
                                <tr class="header-tr">
                                    <th>Store Name</th>
                                    <th>Code</th>
                                    <th>Reserved for Store</th>
                                    <th>Transit to Store</th>
                                    <th>Ready for Sale by Store</th>
                                    <th>Sold from Store</th>
                                    <?php /* ?><th>Returned to Warehouse</th><?php */ ?>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($store_inventory);$i++)
                                    <?php $store_id = $store_inventory[$i]['store_id']; ?>
                                    <tr>
                                        <td>{{$store_inventory[$i]['store_name']}}</td>
                                        <td>{{$store_inventory[$i]['store_id_code']}}</td>
                                        @foreach($status_list as $status_id=>$status_name)
                                            <td>{{$status_total = (isset($store_inventory[$i]['status_'.$status_id]))?$store_inventory[$i]['status_'.$status_id]:0}}</td>
                                            <?php $store_total+=$status_total; ?>
                                            <?php $statuses_total[$status_id]+=$status_total; ?>
                                        @endforeach
                                        <?php /* ?>
                                        <td>{{$return_total = isset($store_demand_return_data[$store_id])?$store_demand_return_data[$store_id]->inv_count_return:0}}</td>
                                        <?php $store_total+=$return_total; ?> <?php */ ?>
                                        <td>{{$store_total}}</td>
                                    </tr>
                                    <?php $store_total = 0; ?>
                                    <?php //$return_total_stores+=$return_total; ?>
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr>
                                    <th colspan="2">Total</th>
                                    @foreach($status_list as $status_id=>$status_name)
                                        <th>{{$statuses_total[$status_id]}}</th>
                                        <?php $grand_total+=$statuses_total[$status_id]; ?>
                                    @endforeach
                                    <?php /* ?><th>{{$return_total_stores}}</th><?php */ ?>
                                    <th>{{$grand_total}}</th>
                                </tr>
                            </tfoot>
                        </table>
                        <br/>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{ asset('js/datatables.min.js') }}" ></script>
<link rel="stylesheet" type="text/css" href="{{asset('css/datatables.min.css')}}"/>
<script type="text/javascript">
    $(document).ready(function(){
        $('.report-sort').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [] });
    });
</script>
@endsection
