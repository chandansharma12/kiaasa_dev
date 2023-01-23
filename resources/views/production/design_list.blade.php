@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'production/dashboard'),array('name'=>'Design List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Design List'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="designListErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="designList">
                <a href="javascript:;" onclick="requestQuotationBySKU();" class="btn btn-dialog ">Create SKU Quotation</a>
                <div class="separator-10"></div>
                <div class="table-responsive table-filter" cellspacing="0">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th><input type="checkbox" name="chk_design_id_all" id="chk_design_id_all" value="1" class="design_id-chk" onclick="checkAllCheckboxes(this,'design_id');" > 
                            <?php echo CommonHelper::getSortLink('ID','id','production/design-list',true,'DESC'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Article Number','article','production/design-list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Product','product','production/design-list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Designer','designer','production/design-list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Version','version','production/design-list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Design Status','dh_status','production/design-list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Production Status','ph_status','production/design-list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Production Count','prod_count','production/design-list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Date Added','created','production/design-list'); ?></th>
                            <th>Action</th>
                            </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($approved_designs);$i++)
                                <tr>
                                    <?php if(empty($approved_designs[$i]->production_count) ) $production_count = 0;else $production_count = $approved_designs[$i]->production_count; ?>
                                    <td>
                                        <input type="checkbox" name="chk_design_id_{{$approved_designs[$i]->id}}" id="chk_design_id_{{$approved_designs[$i]->id}}" value="{{$approved_designs[$i]->id}}" class="design_id-chk" @if(strtolower($approved_designs[$i]->production_status) != 'approved') disabled @endif> {{$approved_designs[$i]->id}}
                                    </td>
                                    <td>{{$approved_designs[$i]->sku}}</td>
                                    <td>{{$approved_designs[$i]->design_type_name}}</td>
                                    <td>{{$approved_designs[$i]->designer_name}}</td>
                                    <td>{{$approved_designs[$i]->version}}</td>
                                    <td>{{$approved_designs[$i]->reviewer_status}}</td>
                                    <td>{{$approved_designs[$i]->production_status}}</td>
                                    <td>{{$production_count}}</td>
                                    <td>{{date('d M Y',strtotime($approved_designs[$i]->created_at))}}</td>
                                    <td>
                                        <a href="{{url('design/detail/'.$approved_designs[$i]->id)}}" ><i title="Details" class="fas fa-eye"></i></a> &nbsp;
                                        @if(strtolower($approved_designs[$i]->production_status) != 'approved')
                                            <a href="javascript:;" onclick="editProductionCount({{$approved_designs[$i]->id}},{{$production_count}});"><i title="Edit Production Count" class="far fa-edit"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $approved_designs->withQueryString()->links() }} <p>Displaying {{$approved_designs->count()}} of {{ $approved_designs->total() }} designs.</p>
                </div>
            </div>
        </div>
    </section>

    <form method="post" id="sku_quotation_form" name="sku_quotation_form" action="{{url('production/sku-quotation')}}">
        <input type="hidden" name="design_ids" id="design_ids" value=""/>
        @csrf
    </form> 

    <div class="modal fade" id="edit-design-production_count" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Design Production Count</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="editDesignProductionCountErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="editDesignProductionCountSuccessMessage"></div>

                <form class="" name="editDesignProductionCountFrm" id="editDesignProductionCountFrm" type="POST">
                    <div class="modal-body" >

                        <div class="form-group" id="product_process_cost_edit_div" >
                            <label>Production Count</label>
                            <input type="text" name="productionCount" id="productionCount" class="form-control" >
                            <div class="invalid-feedback" id="error_editDesignProductionCount_production_count"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="edit-productionCount-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="editproductionCountBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="editproductionCountBtn_submit" class="btn btn-dialog" onclick="updateDesignproductionCount();">Submit</button>
                        <input type="hidden" name="edit_production_count_design_id_edit_hdn" id="edit_production_count_design_id_edit_hdn" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="error_request_quotation_sku_dialog" tabindex="-1" role="dialog" aria-hidden="true">
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
<script src="{{ asset('js/production.js') }}" ></script>
@endsection
