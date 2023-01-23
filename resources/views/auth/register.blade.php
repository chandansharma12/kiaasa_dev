@extends('layouts.login')

@section('content')

<section class="login_blk">
    <div class="container-fluid">
        <div class="login_section">
            <div class="row no-gutters bg-white">
                <div class="col-md-6">
                    <div class="login_form">
                        <a href="#" class="m-0"><img src="{{asset('images/logo.png')}}" alt="Kiaasa Logo" /></a>
                        <h3>Procurement Portal</h3>	
                        <h2>Register</h2>
                        <form method="POST" action="{{ route('register') }}" name="register_form" id="register_form">
                            @csrf
                            <div class="form-group">
                                <label for="Email">{{ __('Name') }}</label>						
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus >
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div> 
                            <div class="form-group">
                                <label for="Email">{{ __('E-Mail Address') }}</label>						
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" >
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div> 
                            <div class="form-group mb-3">
                                <label for="Password">{{ __('Password') }}</label>						
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label for="Password">{{ __('Confirm Password') }}</label>						
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div> 
                            <div class="text-right">
                                <a href="{{url('/login')}}">Login</a>
                            </div> 
                            <a href="javascript:;" onclick="$('#register_form').submit();" class="btn_login">Register</a>
                        </form>
                    </div> 
                </div>
                <div class="col-md-6">
                    <div class="login_img">
                        <img src="{{asset('images/login_img.jpg')}}" alt="Login" /> 
                    </div> 
                </div> 
            </div>
        </div>
    </div>
</section>

@endsection
