<?php

namespace App\Models;

use App\Traits\HasDateFiltersTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasDateFiltersTrait;

    protected $guarded = ['id', 'created_at', 'name', 'email', 'phone', 'identity_id', 'document_number', 'roles'];

    protected $with = ['user:id,name,email,status,phone,identity_id,document_number', 'user.roles'];

    protected $hidden = ['user'];

    protected $appends = ['name', 'email', 'phone', 'identity_id', 'document_number', 'roles'];

    protected $casts = [
        'salary' => 'decimal:2',
        'status' => 'boolean',
        'allow_extra_hours' => 'boolean',
        'tolerance_minutes' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function getIdentityIdAttribute(): ?int
    {
        return $this->user?->identity_id;
    }

    public function getDocumentNumberAttribute(): ?string
    {
        return $this->user?->document_number;
    }

    public function getRolesAttribute(): array
    {
        return $this->user?->getRoleNames()->all() ?? [];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function identity()
    {
        return $this->belongsTo(Identity::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class)->orderByDesc('date');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable')->orderByDesc('date');
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class)->orderBy('day_of_week');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query->where('biometric_code', 'like', '%'.trim($search).'%');
            $query->orWhereHas('user', function (Builder $query) use ($search) {
                $query->where('name', 'like', '%'.trim($search).'%')
                    ->orWhere('email', 'like', '%'.trim($search).'%')
                    ->orWhere('phone', 'like', '%'.trim($search).'%')
                    ->orWhere('document_number', 'like', '%'.trim($search).'%');
            });
        });
    }
}
