@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'purchaser/dashboard'),array('name'=>'Design List','link'=>'purchaser/design-list'),array('name'=>'Design Quotation Requests')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Design Quotation Requests'); ?>

    @if(isset($design_data) && !empty($design_data))<script>var design_id = {{ $design_data['id'] }}</script>@endif
  
    <section class="form_area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    @if(isset($error_msg) && !empty($error_msg))
                        <div class="alert alert-danger">{{$error_msg}}</div>
                    @endif

                    @if(isset($design_data) && !empty($design_data))
                    <form id="" name="">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="Article">SKU</label>						
                                {{$design_data['sku']}}
                            </div> 
                            <div class="form-group col-md-3">
                                <label for="Season">Version</label>						
                                {{$design_data['version']}}    
                            </div> 
                            <div class="form-group col-md-3">
                                <label for="Story">Designer</label>						
                                {{$design_data['designer_name']}}    
                            </div> 
                            <div class="form-group col-md-3">
                                <label for="Product">Reviewer</label>			
                                {{$design_data['reviewer_name']}}   
                            </div> 
                        </div>
                    </form> 
                    @endif

                    <div class="form-row">
                        <div class="col-md-12"> 
                            <div class="table-responsive table-filter">
                                <table class="table table-striped">
                                <thead><tr><th>Email</th><th>Message</th><th>Request Date</th><th>Quotation Link</th></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($quotation_list);$i++)
                                            <tr><td>{{$quotation_list[$i]['vendor_email']}}</td><td>{{$quotation_list[$i]['message']}}</td>
                                            <td>{{date('d F Y',strtotime($quotation_list[$i]['created_at']))}}</td>
                                            <td><a href="{{url('vendor/quotation/'.$quotation_list[$i]['id'])}}">Quotation Link</a></td>    
                                            </tr>
                                        @endfor
                                        @if(count($quotation_list) == 0)    
                                            <tr><td colspan="5" align="center">No Records</td></tr>
                                        @endif
                                    </tbody>
                                </table>
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
@endsection