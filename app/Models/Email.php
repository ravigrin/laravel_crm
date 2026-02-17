<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use SoftDeletes, Cacheable;

    protected $table = 'email_templates';

    protected $fillable = [
        'template_id',
        'locale_code',
        'template_code',
    ];

    protected $primaryKey = 'template_id';

    public $incrementing = false;
}



