@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Audits List','link'=>'audit/list'),array('name'=>'Audit Variance Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Audit Variance Report'); ?>
    <?php $inv_total = array('inv_count_system'=>0,'inv_count_store'=>0,'inv_price_system'=>0,'inv_price_store'=>0); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="report_type" id="report_type" class="form-control" onchange="updateVarianceReportType({{$audit_data->id}},this.value);">
                            <option value="">-- Report Type --</option>
                            <option value="1" @if($report_type_id == 1) selected @endif>Category</option>
                            <option value="2" @if($report_type_id == 2) selected @endif>SKU</option>
                            <option value="3" @if($report_type_id == 3) selected @endif>QR Code</option>
                        </select>
                    </div>
                    <div class="col-md-2" ><a class="btn btn-dialog" href="{{url('audit/inventory/report/variance/'.$audit_data->id.'/'.$report_type_id)}}?action=download_pdf">Download PDF</a></div>
                </div>
            </form>
            
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <!--<h5>Products List</h5>-->
                    <div class="table-responsive table-filter">
                        @if($report_type_id == 1)
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead>
                                    <tr class="header-tr"><th colspan="2"></th><th style="padding-left:10%;" colspan="2">System</th><th colspan="2" style="padding-left:10%;">Store</th></tr>
                                    <tr class="header-tr"><th>SNo</th><th>Category</th><th>Quantity</th><th>Value</th><th>Quantity</th><th>Value</th></tr>
                                </thead>
                                <tbody>
                                    
                                    @for($i=0;$i<count($category_list);$i++)
                                        <tr>
                                            <td>{{$i+1}}</td>
                                            <td>{{$category_list[$i]['name']}}</td>
                                            <td>{{$inv_count_system = (isset($audit_inventory_system[$category_list[$i]['id']]->inv_count))?$audit_inventory_system[$category_list[$i]['id']]->inv_count:0}}</td>
                                            <td>{{$inv_price_system = (isset($audit_inventory_system[$category_list[$i]['id']]->inv_price))?$audit_inventory_system[$category_list[$i]['id']]->inv_price:0}}</td>
                                            <td>{{$inv_count_store = (isset($audit_inventory_store[$category_list[$i]['id']]->inv_count))?$audit_inventory_store[$category_list[$i]['id']]->inv_count:0}}</td>
                                            <td>{{$inv_price_store = (isset($audit_inventory_store[$category_list[$i]['id']]->inv_price))?$audit_inventory_store[$category_list[$i]['id']]->inv_price:0}}</td>
                                        </tr>
                                        <?php $inv_total['inv_count_system']+=$inv_count_system; ?>
                                        <?php $inv_total['inv_price_system']+=$inv_price_system; ?>
                                        <?php $inv_total['inv_count_store']+=$inv_count_store; ?>
                                        <?php $inv_total['inv_price_store']+=$inv_price_store; ?>
                                    @endfor
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th>{{$inv_total['inv_count_system']}}</th>
                                        <th>{{$inv_total['inv_price_system']}}</th>
                                        <th>{{$inv_total['inv_count_store']}}</th>
                                        <th>{{$inv_total['inv_price_store']}}</th>
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                        
                        @if($report_type_id == 2)
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead>
                                    <tr class="header-tr"><th colspan="2"></th><th style="padding-left:10%;" colspan="2">System</th><th colspan="2" style="padding-left:10%;">Store</th></tr>
                                    <tr class="header-tr"><th>SNo</th><th>SKU</th><th>Quantity</th><th>Value</th><th>Quantity</th><th>Value</th></tr>
                                </thead>
                                <tbody>

                                    @for($i=0;$i<count($sku_list);$i++)
                                        <tr>
                                            <td>{{$i+1}}</td>
                                            <td>{{$sku_list[$i]['name']}}</td>
                                            <td>{{$inv_count_system = (isset($audit_inventory_system[$sku_list[$i]['id']]->inv_count))?$audit_inventory_system[$sku_list[$i]['id']]->inv_count:0}}</td>
                                            <td>{{$inv_price_system = (isset($audit_inventory_system[$sku_list[$i]['id']]->inv_price))?$audit_inventory_system[$sku_list[$i]['id']]->inv_price:0}}</td>
                                            <td>{{$inv_count_store = (isset($audit_inventory_store[$sku_list[$i]['id']]->inv_count))?$audit_inventory_store[$sku_list[$i]['id']]->inv_count:0}}</td>
                                            <td>{{$inv_price_store = (isset($audit_inventory_store[$sku_list[$i]['id']]->inv_price))?$audit_inventory_store[$sku_list[$i]['id']]->inv_price:0}}</td>
                                        </tr>
                                        <?php $inv_total['inv_count_system']+=$inv_count_system; ?>
                                        <?php $inv_total['inv_price_system']+=$inv_price_system; ?>
                                        <?php $inv_total['inv_count_store']+=$inv_count_store; ?>
                                        <?php $inv_total['inv_price_store']+=$inv_price_store; ?>
                                    @endfor
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th>{{$inv_total['inv_count_system']}}</th>
                                        <th>{{$inv_total['inv_price_system']}}</th>
                                        <th>{{$inv_total['inv_count_store']}}</th>
                                        <th>{{$inv_total['inv_price_store']}}</th>
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                        
                        @if($report_type_id == 3)
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead>
                                    <tr class="header-tr"><th colspan="2"></th><th style="padding-left:10%;" colspan="2">System</th><th colspan="2" style="padding-left:10%;">Store</th></tr>
                                    <tr class="header-tr"><th>SNo</th><th>QR Code</th><th>Quantity</th><th>Value</th><th>Quantity</th><th>Value</th></tr>
                                </thead>
                                <tbody>

                                    @for($i=0;$i<count($barcode_list);$i++)
                                        <tr>
                                            <td>{{$i+1}}</td>
                                            <td>{{$barcode_list[$i]['id']}}</td>
                                            <td>{{$inv_count_system = (isset($audit_inventory_system[$barcode_list[$i]['id']]->inv_count))?$audit_inventory_system[$barcode_list[$i]['id']]->inv_count:0}}</td>
                                            <td>{{$inv_price_system = (isset($audit_inventory_system[$barcode_list[$i]['id']]->inv_price))?$audit_inventory_system[$barcode_list[$i]['id']]->inv_price:0}}</td>
                                            <td>{{$inv_count_store = (isset($audit_inventory_store[$barcode_list[$i]['id']]->inv_count))?$audit_inventory_store[$barcode_list[$i]['id']]->inv_count:0}}</td>
                                            <td>{{$inv_price_store = (isset($audit_inventory_store[$barcode_list[$i]['id']]->inv_price))?$audit_inventory_store[$barcode_list[$i]['id']]->inv_price:0}}</td>
                                        </tr>
                                        <?php $inv_total['inv_count_system']+=$inv_count_system; ?>
                                        <?php $inv_total['inv_price_system']+=$inv_price_system; ?>
                                        <?php $inv_total['inv_count_store']+=$inv_count_store; ?>
                                        <?php $inv_total['inv_price_store']+=$inv_price_store; ?>
                                    @endfor
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th>{{$inv_total['inv_count_system']}}</th>
                                        <th>{{$inv_total['inv_price_system']}}</th>
                                        <th>{{$inv_total['inv_count_store']}}</th>
                                        <th>{{$inv_total['inv_price_store']}}</th>
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                        
                    </div>
                    
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/audit.js?v=1.15') }}" ></script>
@endsection
