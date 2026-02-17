<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class EntityCredentials extends Model
{
    protected $table = 'entity_credentials';

    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'external_entity_id',
        'integration_credentials_id'
    ];

    public function credentials()
    {
        return $this->hasMany(Credentials::class, 'id', 'integration_credentials_id');
    }
}



