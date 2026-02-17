<?php

namespace App\Models\OAuth;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Client\Token\AccessToken;

class Token extends Model
{
    protected $table = 'oauth_tokens';

    protected $fillable = [
        'service', 'domain', 'access_token', 'refresh_token', 'expires',
    ];

    public $timestamps = false;

    public function setExpiresAtAttribute($value)
    {
        $this->attributes['expires'] = Carbon::parse($value)->timestamp;
    }

    public function getAsAccessTokenInstance(): AccessToken
    {
        $this->setExpiresAtAttribute($this->attributes['expires']);
        return new AccessToken($this->attributes);
    }
}



