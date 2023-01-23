@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Audits List','link'=>'audit/list'),array('name'=>'Audit Mismatch Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Audit Mismatch Report'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" ><a class="btn btn-dialog" href="{{url('audit/inventory/report/mismatch/'.$audit_data->id)}}?action=download_pdf">Download PDF</a></div>
                </div>
            </form>
            
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                   
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <tbody>
                                <tr class="header-tr"><th colspan="2">Mismatch in Store</th></tr>
                                <tr>
                                    <td style="width:20%">Total Quantity Mismatched: </td>
                                    <td>{{$qrcode_inventory['inv_count_mismatch_store']}}</td>
                                </tr>    
                                <tr>
                                    <td>Total Amount Mismatched: </td>
                                    <td>{{$qrcode_inventory['inv_price_mismatch_store']}}</td>
                                </tr>     
                                <tr>
                                    <td>Mismatched QR Codes: </td>
                                    <td>{{implode(', ',$qrcode_inventory['inv_qrcodes_mismatch_store'])}}</td>
                                </tr>  
                            </tbody>
                        </table>
                        
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <tbody>
                                <tr class="header-tr"><th colspan="2">Mismatch in System</th></tr>
                                <tr>
                                    <td style="width:20%">Total Quantity Mismatched: </td>
                                    <td>{{$qrcode_inventory['inv_count_mismatch_system']}}</td>
                                </tr>    
                                <tr>
                                    <td>Total Amount Mismatched: </td>
                                    <td>{{$qrcode_inventory['inv_price_mismatch_system']}}</td>
                                </tr>     
                                <tr>
                                    <td>Mismatched QR Codes: </td>
                                    <td>{{implode(', ',$qrcode_inventory['inv_qrcodes_mismatch_system'])}}</td>
                                </tr>  
                            </tbody>
                        </table>
                       
                    </div>
                    
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/audit.js') }}" ></script>
@endsection
