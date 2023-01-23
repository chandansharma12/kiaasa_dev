@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'SOR Purchase Orders GRN List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'SOR Purchase Orders GRN List');$page_name = 'grn_report'; ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                
                <div class="row justify-content-end">
                    
                    <div class="col-md-2">
                        <input type="text" name="invoice_no" id="invoice_no" class="form-control" value="{{request('invoice_no')}}" placeholder="PO Invoice No">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="po_no" id="po_no" class="form-control" value="{{request('po_no')}}" placeholder="PO Order No">
                    </div>
					<!--
                    <div class="col-md-2">
                        <select name="po_cat_id" id="po_cat_id" class="form-control">
                            <option value="">-- PO Category --</option>
                            @for($i=0;$i<count($po_category_list);$i++)
                                <?php if($po_category_list[$i]['id'] == request('po_cat_id')) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$po_category_list[$i]['id']}}">{{$po_category_list[$i]['name']}}</option>
                            @endfor   
                        </select>
                    </div>-->
                    <div class="col-md-2">
                        <select name="v_id" id="v_id" class="form-control">
                            <option value="">-- Vendor --</option>
                            @for($i=0;$i<count($vendors_list);$i++)
                                <?php if($vendors_list[$i]['id'] == request('v_id')) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$vendors_list[$i]['id']}}">{{$vendors_list[$i]['name']}}</option>
                            @endfor   
                        </select>
                    </div>
					
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input autocomplete="off" type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}} @endif">
                            <div class="input-group-addon" style="margin-top:10px;">to </div> &nbsp;
                            <input autocomplete="off" type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}} @endif">
                        </div>
                    </div>
                    
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2"><a href="{{url('purchase-order/product/grn/list?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="productsContainer">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead><tr class="header-tr"><th>GRN No</th><th>Invoice No</th>
                                <th>PO No</th><th>Vendor</th>
                                <th>Inventory Count</th><th>Vendor Price</th><th>GST Amt</th><th>Base Price</th>
                                <th>Balance Qty</th><th>Vendor Price</th><th>GST Amt</th><th>Base Price</th>
                                <th>Category</th><th>Created On</th>
                                <th>QC Completed</th>
                                @if($user->user_type == 3 || $user->user_type == 6)
                                    <th>Action</th>
                                @endif
                        </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($grn_list);$i++)
                            
                                <tr>
                                    <td>{{$grn_list[$i]->grn_no}}</td>
                                    <td>{{$grn_list[$i]->invoice_no}}</td>
                                    <td>{{$grn_list[$i]->po_no}}</td>
                                    <td>{{$grn_list[$i]->vendor_name}}</td>
                                    <?php /* $other_data = json_decode($grn_list[$i]->other_data,true) ?>
                                    <td>{{$other_data['total']}}</td><?php */ ?>
                                    
                                    <td>{{$grn_list[$i]->grn_cnt}}</td>
                                    <td>{{$grn_list[$i]->grn_vendor_base_price_total}}</td>
                                    <td>{{$grn_list[$i]->grn_vendor_gst_amount_total}}</td>
                                    <td>{{$grn_list[$i]->grn_base_price_total}}</td>
                                    
                                    <td>{{$grn_list[$i]->balance_cnt}}</td>
                                    <td>{{$grn_list[$i]->balance_vendor_base_price_total}}</td>
                                    <td>{{$grn_list[$i]->balance_vendor_gst_amount_total}}</td>
                                    <td>{{$grn_list[$i]->balance_base_price_total}}</td>
                                    <td>{{$grn_list[$i]->po_category_name}}</td>
                                    <td>{{date('d-m-Y',strtotime($grn_list[$i]->created_at))}}</td>
                                    <td>{{(!empty($grn_list[$i]->qc_id))?'Yes':'No'}}</td>
                                    @if($user->user_type == 3 || $user->user_type == 6)
                                        <td>
                                            <a href="{{url('warehouse/sor/inventory/import/'.$grn_list[$i]->po_detail_id)}}"><i title="GRN Details" class="fas fa-eye"></i> &nbsp; 
                                            @if(!empty($grn_list[$i]->qc_id))    
                                                <a href="{{url('warehouse/sor/inventory/qc/'.$grn_list[$i]->po_detail_id)}}"><i title="QC Details" class="fas fa-eye"></i>    
                                            @endif        
                                        </td>
                                    @endif
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $grn_list->withQueryString()->links() }}
                        <p>Displaying {{$grn_list->count()}} of {{ $grn_list->total() }} GRNs.</p>
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
@endsection
