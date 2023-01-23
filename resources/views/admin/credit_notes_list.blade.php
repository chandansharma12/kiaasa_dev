@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Credit Notes List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Credit Notes List'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <h6>Type: {{$credit_note_types[$type_id]}}</h6>
            
            <div id="searchCreditNoteErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="searchCreditNoteSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <form method="get" name="searchCreditNoteForm" id="searchCreditNoteForm">
                <div class="row justify-content-end" >
                    <div class="col-md-1">
                        <input type="text" name="id" id="id" class="form-control" placeholder="ID" value="{{request('id')}}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="credit_note_no" id="credit_note_no" class="form-control" placeholder="Credit Note No" value="{{request('credit_note_no')}}">
                    </div>
                    @if(in_array($type_id,array(1,2,3,4,5,6,7)))
                        <div class="col-md-2">
                            <div class="input-group input-daterange">
                                <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif" autocomplete="off">
                                <div class="input-group-addon" style="margin-top:10px;">to</div> 
                                <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif" autocomplete="off">
                            </div>
                        </div>
                    @endif
                    
                    @if($user->user_type != 15)
                        <div class="col-md-3" >
                            <select name="type_id" id="type_id" class="form-control">
                                <option value="">-- Credit Note Type --</option>
                                @foreach($credit_note_types as $id=>$type)
                                    <?php if($type_id == $id) $sel = 'selected';else $sel = ''; ?>
                                    <option {{$sel}} value="{{$id}}">{{$type}}</option>
                                @endforeach    
                            </select>
                        </div>
                        <div class="col-md-1" ><input type="button" name="search" id="search" value="Search" class="btn btn-dialog" onclick="searchCreditNotes();"></div>
                        
                        @if(in_array($type_id,array(1,2,3,4,5,6)))
                            <?php $query_str = CommonHelper::getQueryString();?>
                            <div class="col-md-1"><a href="{{url('credit/notes/list?action=download_csv&'.$query_str)}}" class="btn btn-dialog" ><i title="Download CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                        @endif
                    @endif
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="purchaseOrdersErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="productsContainer">
                @if($type_id == 1)
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>ID</th><th>Credit Note No</th><th>PO No</th><th>Invoice No</th><th>Defective Inventory</th><th>Comments</th><th>Created On</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($credit_notes_list);$i++)
                                    <?php $defective_inv = json_decode($credit_notes_list[$i]->other_data,true); ?>
                                    <tr>
                                        <td>{{$credit_notes_list[$i]->id}}</td>
                                        <td>{{$credit_notes_list[$i]->credit_note_no}}</td>
                                        <td>{{$credit_notes_list[$i]->order_no}}</td>
                                        <td>{{$credit_notes_list[$i]->invoice_no}}</td>
                                        <td>{{$defective_inv['total']}}</td>
                                        <td>{{$credit_notes_list[$i]->comments}}</td>
                                        <td>{{date('d M Y, H:i',strtotime($credit_notes_list[$i]->created_at))}}</td>
                                        <td><a href="{{url('warehouse/sor/inventory/qc/'.$credit_notes_list[$i]->po_detail_id)}}"><i title="Details" class="fas fa-eye"></i></a> &nbsp; </td>
                                    </tr>
                                @endfor
                                @if(empty($credit_notes_list))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>
                        </table>
                        @if(!empty($credit_notes_list))
                            {{ $credit_notes_list->withQueryString()->links() }}
                            <p>Displaying {{$credit_notes_list->count()}} of {{ $credit_notes_list->total() }} credit notes.</p>
                        @endif    
                    </div>
                @endif
                
                @if($type_id == 2)
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>ID</th><th>Credit Note No</th><th>Invoice No</th><th>Store</th><th>Comments</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($credit_notes_list);$i++)
                                    
                                    <tr>
                                        <td>{{$credit_notes_list[$i]->id}}</td>
                                        <td>{{$credit_notes_list[$i]->credit_invoice_no}}</td>
                                        <td>{{$credit_notes_list[$i]->base_demand_invoice_no}}</td>
                                        <td>{{$credit_notes_list[$i]->store_name}} ({{$credit_notes_list[$i]->store_id_code}})</td>
                                        <td>{{$credit_notes_list[$i]->comments}}</td>
                                        <td>{{date('d M Y',strtotime($credit_notes_list[$i]->created_at))}}</td>
                                        <td>{{CommonHelper::getDemandStatusText('inventory_return_to_warehouse',$credit_notes_list[$i]->demand_status)}}</td>
                                        <td><a href="{{url('store/demand/inventory-return/detail/'.$credit_notes_list[$i]->id)}}?type=crt"><i title="Details" class="fas fa-eye"></i></a> &nbsp; </td>
                                    </tr>
                                @endfor
                                @if(empty($credit_notes_list))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>
                        </table>
                        @if(!empty($credit_notes_list))
                            {{ $credit_notes_list->withQueryString()->links() }}
                            <p>Displaying {{$credit_notes_list->count()}} of {{ $credit_notes_list->total() }} credit notes.</p>
                        @endif    
                    </div>
                @endif
                
                @if($type_id == 3)
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>ID</th><th>Credit Note No</th><th>Store</th><th>Comments</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($credit_notes_list);$i++)
                                    <tr>
                                        <td>{{$credit_notes_list[$i]->id}}</td>
                                        <td>{{$credit_notes_list[$i]->credit_invoice_no}}</td>
                                        <td>{{$credit_notes_list[$i]->store_name}}  ({{$credit_notes_list[$i]->store_id_code}})</td>
                                        <td>{{$credit_notes_list[$i]->comments}}</td>
                                        <td>{{date('d M Y, H:i',strtotime($credit_notes_list[$i]->created_at))}}</td>
                                        <td>{{CommonHelper::getDemandStatusText('inventory_return_complete',$credit_notes_list[$i]->demand_status)}}</td>
                                        <td><a href="{{url('store/demand/inventory-return-complete/detail/'.$credit_notes_list[$i]->id)}}?type=crt"><i title="Details" class="fas fa-eye"></i></a> &nbsp; </td>
                                    </tr>
                                @endfor
                                @if(empty($credit_notes_list))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>
                        </table>
                        @if(!empty($credit_notes_list))
                            {{ $credit_notes_list->withQueryString()->links() }}
                            <p>Displaying {{$credit_notes_list->count()}} of {{ $credit_notes_list->total() }} credit notes.</p>
                        @endif    
                    </div>
                @endif
                
                @if($type_id == 4)
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>ID</th><th>Credit Note No</th><th>Vendor</th><!--<th>PO No</th><th>PO Invoice No</th>--><th>Comments</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($credit_notes_list);$i++)
                                    <tr>
                                        <td>{{$credit_notes_list[$i]->id}}</td>
                                        <td>{{$credit_notes_list[$i]->credit_invoice_no}}</td>
                                        <td>{{$credit_notes_list[$i]->vendor_name}}</td>
                                        <?php /* ?><td>{{$credit_notes_list[$i]->po_no}}</td>
                                        <td>{{$credit_notes_list[$i]->pod_invoice_no}}</td><?php */ ?>
                                        <td>{{$credit_notes_list[$i]->comments}}</td>
                                        <td>{{date('d M Y, H:i',strtotime($credit_notes_list[$i]->created_at))}}</td>
                                        <td>{{CommonHelper::getDemandStatusText('inventory_return_to_vendor',$credit_notes_list[$i]->demand_status)}}</td>
                                        <td><a href="{{url('warehouse/demand/inventory-return-vendor/detail/'.$credit_notes_list[$i]->id)}}?type=crt"><i title="Details" class="fas fa-eye"></i></a> &nbsp; </td>
                                    </tr>
                                @endfor
                                @if(empty($credit_notes_list))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>
                        </table>
                        @if(!empty($credit_notes_list))
                            {{ $credit_notes_list->withQueryString()->links() }}
                            <p>Displaying {{$credit_notes_list->count()}} of {{ $credit_notes_list->total() }} credit notes.</p>
                        @endif    
                    </div>
                @endif
                
                @if($type_id == 5)
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>ID</th><th>Credit Note No</th><th>PO No</th><th>PO Invoice No</th><th>Vendor</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($credit_notes_list);$i++)
                                    <tr>
                                        <td>{{$credit_notes_list[$i]->id}}</td>
                                        <td>{{$credit_notes_list[$i]->credit_note_no}}</td>
                                        <td>{{$credit_notes_list[$i]->order_no}}</td>
                                        <td>{{$credit_notes_list[$i]->invoice_no}}</td>
                                        <td>{{$credit_notes_list[$i]->vendor_name}}</td>
                                        <td>{{date('d M Y, H:i',strtotime($credit_notes_list[$i]->created_at))}}</td>
                                        <td>{{ucfirst($credit_notes_list[$i]->debit_note_status)}}</td>
                                        <td><a href="{{url('warehouse/sor/inventory/pending/invoice/'.$credit_notes_list[$i]->id)}}/2"><i title="Download Pending Inventory Credit Note Invoice" class="fas fa-download"></i></a> &nbsp; </td>
                                    </tr>
                                @endfor
                                @if(empty($credit_notes_list))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>
                        </table>
                        @if(!empty($credit_notes_list))
                            {{ $credit_notes_list->withQueryString()->links() }}
                            <p>Displaying {{$credit_notes_list->count()}} of {{ $credit_notes_list->total() }} credit notes.</p>
                        @endif    
                    </div>
                @endif
                
                @if($type_id == 6)
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>ID</th><th>Credit Note No</th><th>Push Demand No</th><th>Store</th><th>Inventory Count</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($credit_notes_list);$i++)
                                    <tr>
                                        <td>{{$credit_notes_list[$i]->id}}</td>
                                        <td>{{$credit_notes_list[$i]->credit_note_no}}</td>
                                        <td>{{$credit_notes_list[$i]->demand_invoice_no}}</td>
                                        <td>{{$credit_notes_list[$i]->store_name}}  ({{$credit_notes_list[$i]->store_id_code}})</td>
                                        <td>{{$credit_notes_list[$i]->items_count}}</td>
                                        <td>{{date('d M Y',strtotime($credit_notes_list[$i]->created_at))}}</td>
                                        <td>{{ucfirst($credit_notes_list[$i]->debit_note_status)}}</td>
                                        <td><a href="{{url('store/demand/inventory-push/debit-note/invoice/'.$credit_notes_list[$i]->id)}}/2"><i title="Download Less Inventory Debit Note Invoice" class="fas fa-download"></i></a> &nbsp; </td>
                                    </tr>
                                @endfor
                                @if(empty($credit_notes_list))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>
                        </table>
                        @if(!empty($credit_notes_list))
                            {{ $credit_notes_list->withQueryString()->links() }}
                            <p>Displaying {{$credit_notes_list->count()}} of {{ $credit_notes_list->total() }} debit notes.</p>
                        @endif    
                    </div>
                @endif
                
                 @if($type_id == 7)
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th>ID</th><th>Credit Note No</th><th>PO No</th><th>Invoice No</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($credit_notes_list);$i++)
                                    <tr>
                                        <td>{{$credit_notes_list[$i]->id}}</td>
                                        <td>{{$credit_notes_list[$i]->credit_note_no}}</td>
                                        <td>{{$credit_notes_list[$i]->order_no}}</td>
                                        <td>{{$credit_notes_list[$i]->invoice_no}}</td>
                                        <td>{{date('d M Y, H:i',strtotime($credit_notes_list[$i]->created_at))}}</td>
                                        <td>{{ucfirst($credit_notes_list[$i]->debit_note_status)}}</td>
                                        <td><a href="{{url('warehouse/sor/inventory/debit-note/excess-amount/download/'.$credit_notes_list[$i]->id)}}/2"><i title="Download Credit Note" class="fas fa-download"></i></a> &nbsp; </td>
                                    </tr>
                                @endfor
                                @if(empty($credit_notes_list))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>
                        </table>
                        @if(!empty($credit_notes_list))
                            {{ $credit_notes_list->withQueryString()->links() }}
                            <p>Displaying {{$credit_notes_list->count()}} of {{ $credit_notes_list->total() }} credit notes.</p>
                        @endif    
                    </div>
                @endif
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{ asset('js/pos_product.js?v=2.1') }}" ></script>
@endsection
