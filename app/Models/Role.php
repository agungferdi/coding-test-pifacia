<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class Role extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uuid' => 'string',
        'permissions' => 'array',
    ];

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
            
            if (empty($model->guard_name)) {
                $model->guard_name = 'web';
            }
            
            if (empty($model->permissions)) {
                $model->permissions = json_encode([]);
            }
        });
    }

    /**
     * Get the users for the role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    /**
     * Check if the role has a specific permission
     */
    public function hasPermission($permission)
    {
        $permissions = $this->permissions ?? [];
        
        // Special case: Administrator role has all permissions
        if (strtolower($this->name) === 'administrator' || strtolower($this->name) === 'admin') {
            return true;
        }
        
        return isset($permissions[$permission]) && $permissions[$permission];
    }
    
    /**
     * Get all permissions for this role
     */
    public function getAllPermissions()
    {
        return $this->permissions ?? [];
    }
}
