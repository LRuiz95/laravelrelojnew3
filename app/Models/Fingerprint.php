<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fingerprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'device_id',
        'finger',
        'template',
        'template_hash',
    ];

    protected $casts = [
        'finger' => 'integer',
    ];

    protected $hidden = ['template'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
