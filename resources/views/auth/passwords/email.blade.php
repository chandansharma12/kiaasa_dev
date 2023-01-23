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
                        <h2>{{ __('Reset Password') }}</h2>
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif
                        <form method="POST" action="{{ route('password.email') }}" name="forgot_password_form" id="forgot_password_form">
                            @csrf
                            <div class="form-group">
                                <label for="Email">{{ __('E-Mail Address') }}</label>						
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus >
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div> 
                            <a href="javascript:;" onclick="$('#forgot_password_form').submit();" class="btn_login">{{ __('Send Password Reset Link') }}</a>
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
