@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Size List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Size List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <form method="GET">
                <div class="row justify-content-end" >
                    <div class="col-md-1" ><input type="button" name="addSizeBtn" id="addSizeBtn" value="Add Size" class="btn btn-dialog" onclick="addSize();"></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="categoryContainer">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" >
                        <thead><tr class="header-tr"><th>ID</th><th>Size</th><th>Slug</th><th>Created On</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($size_list);$i++)
                                <tr>
                                    <td>{{$size_list[$i]->id}}</td>
                                    <td>{{$size_list[$i]->size}}</td>
                                    <td>{{$size_list[$i]->slug}}</td>
                                    <td>@if(!empty($size_list[$i]->created_at)) {{date('d M Y',strtotime($size_list[$i]->created_at))}} @endif</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="add_size_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Size</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addSizeSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addSizeErrorMessage"></div>

                <form class="" name="addSizeFrm" id="addSizeFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Size</label>
                                <input id="sizeName_add" type="text" class="form-control" name="sizeName_add" value="" >
                                <div class="invalid-feedback" id="error_validation_sizeName_add"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="size_add_cancel" name="size_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="size_add_submit" name="size_add_submit" class="btn btn-dialog" onclick="submitAddSize();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/category.js') }}" ></script>
@endsection
