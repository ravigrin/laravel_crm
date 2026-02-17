<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blocklist extends Model
{
    use HasFactory;

    protected $table = 'blocklist';

    protected $fillable = [
        'user_id',
        'quiz_id',
        'lead_id',
        'fingerprint',
        'ip_address',
        'email',
        'phone',
        'type',
        'reason',
    ];
}

