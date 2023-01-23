@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'POS Orders Drafts List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'POS Orders Drafts List'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead><tr class="header-tr">
                            <th>
                                <input type="checkbox" name="pos_product_list_all" id="pos_product_list_all"  value="1" onclick="checkAllCheckboxes(this,'pos_product-list');">
                                Created On
                            </th>    
                            <th>Products Count</th>
                            <th>Action</th>
                            </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($drafts_list);$i++)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="pos_product_list" id="pos_product_list_{{$drafts_list[$i]->id}}" class="pos_product-list-chk" value="{{$drafts_list[$i]->id}}"> 
                                        {{date('d M Y H:i',strtotime($drafts_list[$i]->created_at))}}
                                    </td>
                                    <td>{{$drafts_list[$i]->products_count}}</td>
                                    <td>
                                        <a href="{{url('store/posbilling?draft_id='.$drafts_list[$i]->id)}}" ><i title="Process Order" class="fas fa-edit"></i></a> &nbsp;
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    @if($drafts_list->count())
                        <input type="submit" name="delete_drafts" id="delete_drafts" value="Delete" class="btn btn-dialog" onclick="deletePosOrderDrafts();">
                    @endif
                    {{ $drafts_list->withQueryString()->links() }} <p>Displaying {{$drafts_list->count()}} of {{ $drafts_list->total() }} records.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="delete_pos_order_draft_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete_pos_order_draft_title">Delete POS Order Draft</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"   id="deletePosOrderDraftErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="deletePosOrderDraftSuccessMessage"></div>
                <div class="modal-body">
                    
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos.js?v=2.3') }}" ></script>

@endsection
