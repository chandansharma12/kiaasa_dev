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
                                    <thead><tr class="header-tr"><th>ID</th><th>Type</th><th>Created On</th><th>Submitted</th><th>Submission Date</th><th>Action</th></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($quotation_list);$i++)
                                            <tr>
                                                <td>{{$quotation_list[$i]->id}}</td>
                                                <td>@if($quotation_list[$i]->type_id == 1) Bulk @else Design @endif</td>
                                                <td>{{date('d M Y',strtotime($quotation_list[$i]->created_at))}}</td>
                                                <td>{{($quotation_list[$i]->quotation_submitted == 1)?'Yes':'No'}}</td>
                                                <td>{{($quotation_list[$i]->quotation_submitted == 1)?date('d M Y',strtotime($quotation_list[$i]->submitted_on)):''}}</td>
                                                <td>
                                                    @if($quotation_list[$i]->quotation_submitted == 0)
                                                        <a href="{{url('quotation/submit/'.$quotation_list[$i]->id.'/'.$quotation_list[$i]->vendor_id)}}" ><i title="Submit Quotation" class="fas fa-edit"></i></a>
                                                    @endif
                                                </td>       
                                            </tr>
                                        @endfor
                                        @if(count($quotation_list) == 0)    
                                            <tr><td colspan="5" align="center">No Records</td></tr>
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

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/quotation.js') }}" ></script>
@endsection