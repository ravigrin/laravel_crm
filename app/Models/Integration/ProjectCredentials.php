<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class ProjectCredentials extends Model
{
    public $timestamps = false;

    protected $table = 'project_credentials';
    public $incrementing = false;

    protected $fillable = [
        'external_project_id',
        'integration_credentials_id'
    ];

    public function credentials()
    {
        return $this->hasMany(Credentials::class, 'id', 'integration_credentials_id');
    }
}



