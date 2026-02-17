<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory, Cacheable;
    protected $table = 'statuses';

    protected $fillable = [
        'user_id', 'project_id', 'code', 'name', 'order', 'color', 'is_default'
    ];

    protected $casts = [
        'order' => 'int',
        'is_default' => 'boolean',
    ];
}



