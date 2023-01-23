<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','user_type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public $roles = array('1'=>'Administrator','2'=>'Production','3'=>'Purchaser','4'=>'Reviewer (D.H.)','5'=>'Designer','6'=>'Warehouse','7'=>'Production Head','8'=>'Warehouse Head',
    '9'=>'Store','10'=>'Store Head','11'=>'Accounts','12'=>'Operation','13'=>'IT','14'=>'Auditor','15'=>'Vendor','16'=>'ASM','17'=>'HRM','18'=>'Fake Inventory Creator');
    
    public function getRoleName() {
       return $this->roles[$this->user_type];
    }
    
    public function getOtherRoles(){
        $user = \Auth::user();
        $other_roles = array();
        if(!empty($user->other_roles)){
            $user_roles = explode(',',$user->other_roles);
            $other_roles = \DB::table('user_roles as ur')->whereIn('id',$user_roles)->where('ur.role_status',1)->get()->toArray();
        }
        
        return $other_roles;
    }
}
