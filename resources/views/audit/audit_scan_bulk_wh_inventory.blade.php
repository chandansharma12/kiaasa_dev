@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Audit List','link'=>'audit/list'),array('name'=>'Audit Scan Bulk Inventory')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Audit Scan Bulk Inventory'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    @if(strtolower($audit_data->audit_status) == 'scan_progress')
        <section class="product_area">
            <div class="container-fluid" >
                <div id="products_import_div" >
                    <form class="" name="auditWHInventoryScanBulkFrm" id="auditWHInventoryScanBulkFrm" method="POST" enctype="multipart/form-data">
                        
                        @if(isset($errors) && $errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="flash-message">
                            @foreach (['danger', 'warning', 'success', 'info'] as $msg)
                                @if(Session::has('alert-' . $msg))
                                    <p class="alert alert-{{ $msg }}">{{ Session::get('alert-' . $msg) }}</p>
                                @endif
                            @endforeach
                        </div>
                        
                        @if(request('upload_submit') != '')
                            @if(!empty($inventory_not_in_system))
                                <b>Inventory not in system</b>
                                <table class="table table-striped admin-table" cellspacing="0" >
                                    <thead><tr class="header-tr"><th>Barcode</th><!--<th>SKU</th>--></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($inventory_not_in_system);$i++)
                                            <tr><td>{{$inventory_not_in_system[$i]}}</td><!--<td></td>--></tr>
                                        @endfor   
                                    </tbody>
                                </table>
                            @endif
                            
                            @if(!empty($inventory_already_scanned))
                                <b>Inventory already scanned</b>
                                <table class="table table-striped admin-table" cellspacing="0" >
                                    <thead><tr class="header-tr"><th>Barcode</th><th>SKU</th></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($inventory_already_scanned);$i++)
                                            <tr><td>{{$inventory_already_scanned[$i]->peice_barcode}}</td><td>{{$inventory_already_scanned[$i]->product_sku}}</td></tr>
                                        @endfor   
                                    </tbody>
                                </table>
                            @endif      
                            
                            <b>Inventory Scanned</b>
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead><tr class="header-tr"><th>Barcode</th><th>SKU</th></tr></thead>
                                <tbody>
                                    @for($i=0;$i<count($inventory_scanned);$i++)
                                        <tr><td>{{$inventory_scanned[$i]->peice_barcode}}</td><td>{{$inventory_scanned[$i]->product_sku}}</td></tr>
                                    @endfor   
                                    @if(empty($inventory_scanned))
                                        <tr><td colspan="2">No Records</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        @endif
                        <div class="form-row" >
                            <div class="form-group col-md-3">
                                <label>Piece Barcode File</label>
                                <input type="file" name="piece_barcode_file" id="piece_barcode_file" class="form-control">
                            </div>
                            
                            <div class="form-group col-md-2">
                                <button type="button" id ="audit_scan_upload_submit" name="audit_scan_upload_submit" class="btn btn-dialog" value="Submit" onclick="submitScanBulkWHInventory();">Upload</button>&nbsp;&nbsp;
                                <button type="button" id="audit_scan_upload_cancel" name="audit_scan_upload_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('dashboard')}}'">Cancel</button>
                            </div>
                        </div>
                        
                        <input type="hidden" name="audit_id" id="audit_id" value="{{$audit_data->id}}">
                        <input type="hidden" name="store_id" id="store_id" value="">
                        <input type="hidden" name="upload_submit" id="upload_submit" value="1">
                        @csrf
                    </form>
                </div>
            </div>
        </section>
    @endif
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/audit.js?v=1.35') }}" ></script>
<script type="text/javascript">
</script>
@endsection
