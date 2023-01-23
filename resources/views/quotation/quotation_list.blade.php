@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Design Quotation Requests')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Design Quotation Requests'); ?>

    <section class="form_area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    @if(isset($error_msg) && !empty($error_msg))
                        <div class="alert alert-danger">{{$error_msg}}</div>
                    @endif

                    <div class="alert alert-danger" id="statusErrorMsg" style="display:none;"></div>

                    <div class="form-row">
                        <div class="col-md-12"> 
                            <div class="table-responsive table-filter">
                                <table class="table table-striped admin-table" cellspacing="0">
                                    <thead><tr class="header-tr"><th>ID</th><th>Type</th><th>Quotation Vendors</th><th>Submissions</th><th>Created By </th><th>Created On</th><th>PO Created</th><th>PO Details</th></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($quotation_list);$i++)
                                            <tr id="tr_quote_{{$quotation_list[$i]->id}}">
                                                <td>{{$quotation_list[$i]->id}}</td>
                                                <td>@if($quotation_list[$i]->type_id == 1) Bulk @else Design @endif</td>
                                                <td>
                                                    <a class="table-link" href="javascript:;" onclick="quotationVendorsList({{$quotation_list[$i]->id}});">Vendors List ({{$quotation_list[$i]->vendors_count}})</a>
                                                </td>
                                                <td>{{$quotation_list[$i]->submissions_count}}</td>
                                                <td>{{$quotation_list[$i]->created_by_name}}</td>
                                                <td>{{date('d M Y',strtotime($quotation_list[$i]->created_at))}}</td>
                                                <td>{{!empty($quotation_list[$i]->po_id)?'Yes':'No'}}</td>
                                                <td>
                                                    @if(empty($quotation_list[$i]->po_id))
                                                        <a class="btn btn-dialog" href="{{url('quotation/submissions/list/'.$quotation_list[$i]->id)}}" style="font-size: 14px;"><i title="Create PO" class="fas fa-edit" style="color:#fff;"></i> Create PO</a>
                                                    @else
                                                        <a class="btn btn-dialog" href="{{url('purchase-orders/list?po_id='.$quotation_list[$i]->po_id)}}" style="font-size: 14px;"><i title="PO Details" class="fas fa-eye" style="color:#fff;"></i> PO Details</a>
                                                    @endif
                                                </td>       
                                            </tr>
                                        @endfor
                                        @if(count($quotation_list) == 0)    
                                            <tr><td colspan="10" align="center">No Records</td></tr>
                                        @endif
                                    </tbody>
                                </table>
                                {{ $quotation_list->withQueryString()->links() }}
                                <p>Displaying {{$quotation_list->count()}} of {{ $quotation_list->total() }} Quotations.</p>
                            </div>
                        </div>   
                    </div>	

                </div> 
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="quatation_list_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Quotation Vendors List</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="modal-body" id="quatation_list">
                    
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="quatation_list_cancel">Close</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/quotation.js') }}" ></script>
@endsection