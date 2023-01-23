@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Expenses List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Expenses List'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                </div>
            </form>
            
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead><tr class="header-tr">
                                <th>SNo</th>
                                <th>Expense Name</th>
                                <th>Expense Category</th>
                        </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($store_expenses_list);$i++)
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$store_expenses_list[$i]->expense_name}}</td>
                                    <td>{{$store_expenses_list[$i]->expense_category}}</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="desc_add_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Page Description</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addPageDescSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="addPageDescErrorMessage"></div>

                <form class="" name="addPageDescFrm" id="addPageDescFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6" >
                                <label>Page Name</label>
                                <input id="page_name_add" type="text" class="form-control" name="page_name_add" value="" >
                                <div class="invalid-feedback" id="error_validation_page_name_add"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Type</label>
                                <select id="desc_type_add" class="form-control" name="desc_type_add"  >
                                    <option value="">-- Type --</option>
                                    <option value="column">Table Column</option>
                                    <option value="filter">Filter</option>
                                    <option value="page_description">Page Description</option>
                                </select>    
                                <div class="invalid-feedback" id="error_validation_desc_type_add"></div>
                            </div>
                        </div>    
                        
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="desc_name_add" type="text" class="form-control" name="desc_name_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_desc_name_add"></div>
                        </div>    

                         <div class="form-group" >
                            <label>Description</label>
                            <textarea id="desc_detail_add" type="password"  name="desc_detail_add"  class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_validation_desc_detail_add"></div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="page_desc_add_cancel" name="page_desc_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="page_desc_add_submit" name="page_desc_add_submit" class="btn btn-dialog" onclick="submitAddPageDescription();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="desc_edit_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Page Description</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" id="editPageDescSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible"  id="editPageDescErrorMessage"></div>

                <form class="" name="editPageDescFrm" id="editPageDescFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6" >
                                <label>Page Name</label>
                                <input id="page_name_edit" type="text" class="form-control" name="page_name_edit" value="" >
                                <div class="invalid-feedback" id="error_validation_page_name_edit"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Type</label>
                                <select id="desc_type_edit" class="form-control" name="desc_type_edit"  >
                                    <option value="">-- Type --</option>
                                    <option value="column">Table Column</option>
                                    <option value="filter">Filter</option>
                                    <option value="page_description">Page Description</option>
                                </select>    
                                <div class="invalid-feedback" id="error_validation_desc_type_edit"></div>
                            </div>
                        </div>    
                        
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="desc_name_edit" type="text" class="form-control" name="desc_name_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_desc_name_edit"></div>
                        </div>    

                         <div class="form-group" >
                            <label>Description</label>
                            <textarea id="desc_detail_edit" type="password"  name="desc_detail_edit" class="form-control"  ></textarea>
                            <div class="invalid-feedback" id="error_validation_desc_detail_edit"></div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="desc_edit_id" id="desc_edit_id" value="">
                        <button type="button" id="page_desc_edit_cancel" name="page_desc_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="page_desc_edit_submit" name="page_desc_edit_submit" class="btn btn-dialog" onclick="submitEditPageDescription();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos_product.js') }}" ></script>
@endsection
