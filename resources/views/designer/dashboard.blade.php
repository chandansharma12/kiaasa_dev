@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
<form method = "POST" action="{{url('/design/add')}}" name="design_add_frm" id="design_add_frm">
    <button type="submit" class="btn_add pull-right" style="border:none;margin-right: 30px;">Add New Design</button> 
    <a type="button" class="btn_add pull-right" href="{{ route('get-sor-product-add') }}" style="border:none;margin-right: 30px;">Add SOR Design</a> 
    <a class="btn_add pull-right" href="{{ url('pos/product/list') }}" style="border:none;margin-right: 30px;">SOR Designs</a> 
    @csrf
</form> 

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="productionDashboardErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="productionDashboard">
                <div class="pro_row clearfix ">
                @for($i=0;$i<count($designs);$i++)
                    <?php 
                    if(!empty($designs[$i]->image_name) && file_exists(public_path('images/design_images/'.$designs[$i]->id.'/thumbs/'.$designs[$i]->image_name))){
                        $designs[$i]->image_path = asset('images/design_images/'.$designs[$i]->id.'/thumbs/'.$designs[$i]->image_name);
                    }else{
                        $designs[$i]->image_path = asset('images/pro2.jpg');
                    }
                    ?>

                    <div class="col"><div class="pro_blk"><a href="{{url('design/edit/'.$designs[$i]->id)}}" >
                    <img src="{{$designs[$i]->image_path}}"  alt="" class="img-thumbnail" />
                    <p>@if(!empty($designs[$i]->sku)) {{$designs[$i]->sku}} @else &mdash; @endif<span>{{$designs[$i]->category_name}}</span></p></a>
                    @if($designs[$i]->designer_submitted == 1)       
                        {{ucfirst($designs[$i]->design_review)}}
                    @else
                        Draft
                    @endif

                    </div></div>
                    <?php if($i > 0 && ($i+1)%5 == 0){ ?><div class="clearfix"></div> <?php } ?>
                @endfor    

            </div>
            {{ $designs->links() }}<p>Displaying {{$designs->count()}} of {{ $designs->total() }} designs.</p>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/design.js') }}" ></script>
@endsection
