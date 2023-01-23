@extends('layouts.default')

@section('content')


<!-- Strat breadcrumb -->
<nav aria-label="breadcrumb" class="page_bbreadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard  </a></li>
    <li class="breadcrumb-item active" aria-current="page">Quotation Requests Items list</li>
  </ol>
    <h2 class="page_heading">Design Quotation Requests</h2>
</nav> 
<!-- End breadcrumb -->
<!-- Strat Form Area -->  
<section class="form_area">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                @if(isset($error_msg) && !empty($error_msg))
                    <div class="alert alert-danger">{{$error_msg}}</div>
                @endif
                
              
                	
                <div class="form-row">
                    <div class="col-md-12"> 
                        <div class="table-responsive table-filter">
                            <table class="table table-striped">
                            <thead><tr><th>Item Detail</th><th>Available Quantity</th> <th>Required Quantity</th><th>Request Date</th><th>Quotation Link</th></tr></thead>
                                <tbody>
                                    @for($i=0;$i<count($design_data);$i++)
                                    <tr><td>{{$design_data[$i]->fabric_name}}, {{$design_data[$i]->quality_name}}, {{$design_data[$i]->color_name}}</td></tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>   
                </div>	
                
            </div> 
        </div>
    </div>
</section>

@endsection

@section('scripts')
@endsection