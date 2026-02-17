<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Credentials extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'integration_credentials';

    protected $fillable = ['credentials', 'enabled', 'code', 'hash', 'temp_id'];

    /**
     * Cast credentials. Need to convert json to assoc_array on model call
     * @var string[]
     */
    protected $casts = [
        'credentials' => 'array'
    ];

    /**
     * Relation to projects
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function projectCredentials()
    {
        return $this->belongsTo(ProjectCredentials::class, 'integration_credentials_id');
    }

    /**
     * Relation to entities
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entityCredentials()
    {
        return $this->belongsTo(EntityCredentials::class, 'integration_credentials_id');
    }
}



