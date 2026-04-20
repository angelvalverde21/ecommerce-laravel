<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\HasStatusScopesTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasStatusScopesTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    const ACTIVE = 1;
    const BLOCKED = 0;

    protected $guarded = ['id', 'created_at'];

    protected $guard_name = 'api';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function courier()
    {
        return $this->hasOne(Courier::class);
    }

    public function identity()
    {
        return $this->belongsTo(Identity::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchaseable');
    }
}
