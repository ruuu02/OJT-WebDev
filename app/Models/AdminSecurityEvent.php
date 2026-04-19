<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSecurityEvent extends Model
{
    protected $table = 'admin_security_events';

    protected $fillable = [
        'admin_user_id',
        'event',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
