<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    const USER_TYPE_SUPERADMIN = 1;
    const USER_TYPE_ADMIN = 2;
    const USER_TYPE_USER = 3;

    public function permissions() {
        return $this->belongsToMany(Permission::class);
    }

    public function uploadedFiles() {
        return $this->hasMany(UploadedFiles::class);
    }

    public function hasPermission($permission) {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'status',
        'password',
        'user_type',
        'last_name',
        'first_name',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];
}
