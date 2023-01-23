@extends('layouts.default')

@section('content')

<section class="login_blk">
    <div class="container-fluid">
        <div class="login_section">
            <div class="row no-gutters bg-white">
                <div class="col-md-6">
                    <div class="login_form">
                        <h2>Edit Profile</h2>
                        @if (session('success_message'))
                            <br/>
                            <div class="alert alert-success">
                                {{ session('success_message') }}
                            </div>
                        @endif
                        @if (session('error_message'))
                            <br/>
                            <div class="alert alert-danger">
                                {{ session('error_message') }}
                            </div>
                        @endif
                        @if (!empty($error_msg))
                            <br/>
                            <div class="alert alert-danger">
                                {{ $error_msg }}
                            </div>
                        @endif
                        
                        @if(empty($error_msg))
                        <form method="POST" action="{{ route('updateProfile') }}" name="profile_edit_form" id="profile_edit_form">
                            @csrf
                            <div class="form-group">
                                <label for="Email">{{ __('Name') }}</label>						
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $user->name }}" required autocomplete="name" autofocus >
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div> 
                            <div class="form-group">
                                <label for="Email">{{ __('E-Mail Address') }}</label>						
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $user->email }}" required autocomplete="email" >
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
                            <div class="form-group mb-3">
                                 <label for="Password"><input type="checkbox" name="update_password" id="update_password" value="1"> Click to update password</label>						
                            </div> 
                            <div class="form-group mb-3">
                                <label for="Role">Current Role: {{$user->role_name}}</label>						
                            </div> 
                            @if(!empty($other_roles))
                                
                                <div class="form-group">
                                <label for="Role">Switch Role</label>						
                                <select name="switch_role_id" id="switch_role_id" class="form-control @error('role_id') is-invalid @enderror">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($other_roles);$i++)
                                        <option value="{{$other_roles[$i]->id}}">{{$other_roles[$i]->role_name}}</option>
                                    @endfor    
                                </select>
                                
                                @error('role_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                </div> 
                            @endif
                            <a href="javascript:;" onclick="$('#profile_edit_form').submit();" class="btn_login">Update</a>
                        </form>
                        @endif
                    </div> 
                </div>
                 
            </div>
        </div>
    </div>
</section>

@endsection
