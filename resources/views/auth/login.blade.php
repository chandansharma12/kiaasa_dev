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
                        <h2>Login</h2>
                        <form method="POST" action="{{ route('login') }}" name="login_form" id="login_form">
                            @csrf
                            <div class="form-group">
                                <label for="Email">Email ID</label>						
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus >
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div> 
                            <div class="form-group mb-3">
                                <label for="Password">Password</label>						
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                             <div class="form-group mb-3">
                                <label for="remember me"></label>&nbsp;	&nbsp;&nbsp;					
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                Remember Me
                            </div>
                            <div class="text-right">
                                <a href="{{url('/register')}}">Register</a> &nbsp;&nbsp;&nbsp; <a href="{{url('password/reset')}}">Forgot Password?</a> 
                            </div> 
                            <!--<a href="javascript:;" onclick="$('#login_form').submit();" class="btn_login">Login</a>-->
                            <button type="submit" class="btn_login" name="login_submit" id="login_submit">Login</button>
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
