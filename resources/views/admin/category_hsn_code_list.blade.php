@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Category HSN Codes')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Category HSN Codes'); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="categoryHsnErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="categoryHsntSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="id" id="id" class="form-control" placeholder="Category ID" value="{{request('id')}}" />
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr"><th>Category ID</th><th>Category</th><th>HSN Code</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php  ?>
                                @for($i=0;$i<count($category_list);$i++)
                                    <tr>
                                        <td>{{$category_list[$i]->id}}</td>
                                        <td>{{$category_list[$i]->name}}</td>
                                        <td>{{$category_list[$i]->hsn_code}}</td>
                                        <td>
                                            <a href="javascript:;" class="setting-list-edit" onclick="editCategoryHsnCode({{$category_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <div class="modal fade" id="edit_category_hsn_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Category HSN Code</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editCategoryHsnSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editCategoryHsnErrorMessage"></div>

                <form class="" name="editCategoryHsnFrm" id="editCategoryHsnFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Category</label>
                            <input id="category_name" type="text" class="form-control" name="category_name" value=""  readonly="true">
                        </div>
                        <div class="form-group" >
                            <label>HSN Code</label>
                            <input id="hsn_code_edit" type="text" class="form-control" name="hsn_code_edit" value="" >
                            <div class="invalid-feedback" id="error_validation_hsn_code_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="category_hsn_edit_cancel" name="category_hsn_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="category_hsn_edit_submit" name="category_hsn_edit_submit" class="btn btn-dialog" onclick="updateCategoryHsnCode();">Submit</button>
                        <input type="hidden" name="category_id" id="category_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js?v=1.2') }}" ></script>
@endsection
