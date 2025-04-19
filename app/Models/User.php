<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, AuditableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

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
            'uuid' => 'string',
        ];
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the role that owns the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has a specific role.
     * 
     * @param string|array $roleNames
     * @return bool
     */
    public function hasRole($roleNames)
    {
        if (!$this->role) {
            return false;
        }
        
        // If role names is a string, convert to array
        if (!is_array($roleNames)) {
            $roleNames = [$roleNames];
        }
        
        // Case-insensitive role checking
        foreach ($roleNames as $roleName) {
            if (strtolower($this->role->name) === strtolower($roleName)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has a specific permission.
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (!$this->role) {
            return false;
        }
        
        // Admin users have all permissions
        if ($this->hasRole(['admin', 'administrator'])) {
            return true;
        }
        
        // Check role-based permission
        return $this->role->hasPermission($permission);
    }
    
    /**
     * Check if user has permission to access a specific feature.
     * This is a convenience method for frontend permissions.
     * 
     * @param string $feature
     * @param string $action
     * @return bool
     */
    public function canAccess($feature, $action = 'view')
    {
        return $this->hasPermission("$feature.$action");
    }
}
