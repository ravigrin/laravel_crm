<?php

namespace App\Models\OAuth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $table = 'oauth_services';

    protected $fillable = [
        'temp_id', 'service', 'client_id', 'client_secret', 'redirect_url'
    ];
}



