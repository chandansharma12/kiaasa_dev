@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'purchaser/dashboard'),array('name'=>'Design List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Design List'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="designListErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="designList">
                <!--<a href="{{url('quotation/list')}}" class="btn btn-dialog">Quotations List</a>&nbsp;&nbsp;&nbsp;-->
                <!--<a href="javascript:;" onclick="requestQuotationBySKU();" class="btn btn-dialog">Create Quotation</a>&nbsp;&nbsp;&nbsp;-->
                <!--<a href="{{url('purchase-orders/list')}}" class="btn btn-dialog">Purchase Orders</a>-->
                <div class="separator-10"></div>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table headings-info-tbl" cellspacing="0">
                        <thead>
                            <tr class="header-tr">
                                <th><!--<input type="checkbox" name="chk_design_id_all" id="chk_design_id_all" value="1" class="design_id-chk" onclick="checkAllCheckboxes(this,'design_id');" >--> ID</th>
                                <th>Style</th>
                                <th>Category</th>
                                <!--<th>Subcategory</th>-->
                                <th>Story</th>
                                <th>Product</th>
                                <th>Designer</th>
                                <th>Purchaser Review</th>
                                <th>Mgmt Review</th>
                                <!--<th>Version</th>
                                <th>DH Status</th>
                                <th>PH Status</th>
                                <th>Prod Count</th>-->
                                <th>Date Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($approved_designs);$i++)
                                <tr>
                                    <?php if(empty($approved_designs[$i]->production_count) ) $production_count = 0;else $production_count = $approved_designs[$i]->production_count; ?>
                                    <td><!--<input type="checkbox" name="chk_design_id_{{$approved_designs[$i]->id}}" id="chk_design_id_{{$approved_designs[$i]->id}}" value="{{$approved_designs[$i]->id}}" class="design_id-chk">-->  {{$approved_designs[$i]->id}}</td>
                                    <td>{{$approved_designs[$i]->sku}}</td>
                                    <td>{{$approved_designs[$i]->category_name}}</td>
                                    <!--<td>{{$approved_designs[$i]->subcategory_name}}</td>-->
                                    <td>{{$approved_designs[$i]->story_name}}</td>
                                    <td>{{$approved_designs[$i]->design_type_name}}</td>
                                    <td>{{$approved_designs[$i]->designer_name}}</td>
                                    <td>{{ucfirst($approved_designs[$i]->purchaser_review)}}</td>
                                    <td>{{ucfirst($approved_designs[$i]->management_review)}}</td>
                                    <!--<td>{{$approved_designs[$i]->version}}</td>
                                    <td>{{$approved_designs[$i]->reviewer_status}}</td>
                                    <td>{{$approved_designs[$i]->production_status}}</td>
                                    <td>{{$production_count}}</td>-->
                                    <td>{{date('d M Y',strtotime($approved_designs[$i]->created_at))}}</td>
                                    <td>
                                        <a href="{{url('design/detail/'.$approved_designs[$i]->id)}}" ><i title="Design Details" class="fas fa-eye"></i></a>&nbsp;&nbsp;
                                        <a href="{{url('purchaser/design/po/list/'.$approved_designs[$i]->id)}}" ><i title="Purchase Orders List" class="fas fa-eye"></i></a>&nbsp;&nbsp;
                                        <?php /* ?><a href="javascript:;" title="Request Quotation" onclick="displayQuotationForm({{$approved_designs[$i]->id}},'{{$approved_designs[$i]->sku}}','{{$approved_designs[$i]->version}}','{{$approved_designs[$i]->version}}',{{$production_count}});">
                                        <i title="Request Quotation" class="fas fa-angle-double-right"></i></a>
                                        &nbsp;&nbsp;<a href="{{url('quotation/requests/'.$approved_designs[$i]->id)}}" title="Quotation Requests List"><i title="Quotation Requests List" class="fas fa-angle-double-right"></i></a>
                                        &nbsp;&nbsp;<a href="{{url('quotation/submissions/'.$approved_designs[$i]->id)}}" title="Quotation Submissions List"><i title="Quotation Submissions List" class="fas fa-angle-double-right"></i></a> <?php */ ?>
                                    </td>
                                </tr>
                            @endfor

                            @if(is_object($approved_designs) && $approved_designs->count() == 0)
                                <tr><td colspan="10" align="center">No Records</td></tr>
                            @endif
                        </tbody>
                    </table>
                    <!--<div class="pull-right table-heading-info clear">DH Status: Designer Head Status &nbsp;| &nbsp;PH Status: Production Head Status&nbsp; | &nbsp;Prod Count: Production Count</div>-->
                    <div class="clear"></div>
                    @if(is_object($approved_designs))
                        {{ $approved_designs->links() }} <p>Displaying {{$approved_designs->count()}} of {{ $approved_designs->total() }} designs.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <form method="post" id="sku_quotation_form" name="sku_quotation_form" action="{{url('purchaser/sku-quotation/')}}">
        <input type="hidden" name="design_ids" id="design_ids" value=""/>
        @csrf
    </form>    

    <div class="modal fade" id="request_quotation_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Request Quotation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="requestQuotationSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="requestQuotationErrorMessage"></div>

                <form class="" name="requestQuotationFrm" id="requestQuotationFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" id="review_status_div">
                            <label>Design SKU</label>
                            <span id="request_quotation_sku"></span>
                        </div>
                        <div class="form-group" id="review_status_div">
                            <label>Reviewer</label>
                            <span id="request_quotation_reviewer"></span>
                        </div>

                        <div class="form-group" id="review_status_div">
                            <label>Email Addresses</label>
                            <input id="quotation_emails" type="text" class="form-control" name="quotation_emails" value="" autofocus >
                            <div class="invalid-feedback" id="error_request_quotation_quotation_emails"></div>	
                        </div>

                        <div class="form-group">
                            <label>Message</label>
                            <textarea class="form-control" name="quotation_message" id="quotation_message"></textarea>
                            <div class="invalid-feedback" id="error_request_quotation_quotation_message"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="request_quotation_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="request_quotation_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="request_quotation_submit" class="btn btn-dialog" onclick="requestQuotation();">Submit</button>
                        <input type="hidden" name="quotation_design_id" id="quotation_design_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="error_request_quotation_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="modal-body">
                    <h6>Production Count is empty<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-dialog" id="error_request_quotation_btn" name="error_request_quotation_btn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="error_request_quotation_sku" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteErrorMessage"></div>
                <div class="modal-body">
                    <h6>Select the records for quotation<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-dialog" id="error_request_quotation_sku_btn" name="error_request_quotation_sku_btn">Close</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js') }}" ></script>
@endsection
