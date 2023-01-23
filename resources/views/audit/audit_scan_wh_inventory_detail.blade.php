@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Audits List','link'=>'audit/list'),array('name'=>'Audit Scan Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Audit Scan Detail'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form id="order_detail_form" name="order_detail_form">
                <input type="hidden" name="audit_id" id="audit_id" value="{{$audit_data->id}}">
                <input type="hidden" name="store_id" id="store_id" value="">
                <input type="hidden" name="scan_status" id="scan_status" value="0,1">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Audit ID</label>						
                        {{$audit_data->id}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Product">Audit Type</label>						
                        Warehouse   
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Status</label>						
                        {{ucwords(str_replace('_',' ',$audit_data->audit_status))}}    
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($audit_data->created_at))}}    
                    </div> 
                    <div class="form-group col-md-3">
                        <label for="Category">Created by</label>						
                        {{$audit_data->auditor_name}}    
                    </div> 
                </div>    
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Category">Total Inventory in System</label>						
                        {{$inv_status['inv_in_system']}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Category">Total Inventory in Warehouse</label>						
                        {{$inv_status['inv_in_wh']}}          
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Category">Inventory in both System and Warehouse</label>						
                        {{$inv_status['inv_in_both_system_wh']}}          
                    </div> 
                    <div class="form-group col-md-3">
                        <label for="Category">Inventory in System, but not in Warehouse</label>						
                        {{$inv_status['inv_only_in_system']}}          
                    </div> 
                    <div class="form-group col-md-3">
                        <label for="Category">Inventory in Warehouse, but not in System</label>						
                        {{$inv_status['inv_only_in_wh']}}          
                    </div> 
                    <div class="form-group col-md-3">
                        <label for="Category">Inventory in both System and Warehouse, but Status not in Warehouse</label>						
                        {{$inv_status['inv_both_system_wh_diff_status']}}          
                    </div> 
                </div>
                
            </form> 
            
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <hr/><h6>Audit Inventory</h6>
                    <div class="form-row">
                         <div class="form-group col-md-2">
                             <?php $i=1; ?>
                            <select name="inv_status_search" id="inv_status_search" class="form-control">
                                <option value="">Inventory</option>
                                @foreach($inv_status as $status=>$count)
                                       <?php //if($i > 4) continue; ?> 
                                       <option value="{{$i}}">{{str_replace(array('_','inv','wh'),array(' ','Inventory','warehouse'),$status)}} ({{$count}})</option>
                                       <?php $i++; ?>
                                @endforeach    
                            </select>
                        </div>
                        
                        <div class="form-group col-md-2">
                            <select name="product_status_search" id="product_status_search" class="form-control">
                                <option value="">Product Status</option>
                                @for($i=0;$i<count($status_list);$i++)
                                    <option value="{{$status_list[$i]->product_status}}">{{$status_list[$i]->status_name}} ({{$status_list[$i]->inv_count}})</option>
                                @endfor    
                            </select>
                         </div>
                    
                         <div class="form-group col-md-2">
                             <input type="button" name="search_btn" id="search_btn" class="btn btn-dialog" value="Search" onclick="searchWHAuditInventory();">
                         </div>
                    </div>    
                    <div id="products_imported_list"></div>
                    <div id="products_paging_links"></div>
                    
                    
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/audit.js?v=1.45') }}" ></script>
<script type="text/javascript">
    var page_type = 'detail';
    $(document).ready(function(){
        loadWHAuditInventory(1);
    });
</script>
@endsection
