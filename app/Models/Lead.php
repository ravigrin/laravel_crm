<?php

namespace App\Models;

use App\Enums\DefaultStatuses;
use App\Services\Encryption\LeadContactEncryptionService;
use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes, Cacheable;

    protected $table = 'leads';

    protected $fillable = [
        #showable fields
        'external_id', 'name', 'email', 'phone', 'messengers', 'data', 'ip_address', 'status',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        #system fields:
        'external_project_id', 'external_system', 'external_entity', 'external_entity_id',
        # ownership
        'user_id', 'project_id', 'quiz_id',
        # new fields
        'contacts', 'city', 'country', 'integration_status', 'integration_data',
        'is_test', 'viewed', 'paid', 'blocked', 'fingerprint', 'equal_answer_id'
    ];

    protected $guarded = ['version', 'payload'];

    protected $casts = [
        'messengers' => 'array',
        'data' => 'array',
        'integration_data' => 'array',
        // 'contacts' => 'array', // Handled manually to prevent array expansion
        'status' => 'int',
        'is_test' => 'boolean',
        'viewed' => 'boolean',
        'paid' => 'boolean',
        'blocked' => 'boolean',
        'equal_answer_id' => 'int',
    ];

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForQuiz($query, int $quizId)
    {
        return $query->where('quiz_id', $quizId);
    }

    /**
     * Get the user that owns the lead.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        // Set default values for required NOT NULL fields before creating
        static::creating(function (Lead $lead) {
            if (!$lead->external_system) {
                $lead->external_system = 'example_system';
            }
            if (!$lead->external_entity) {
                $lead->external_entity = 'lead';
            }
            if (!$lead->external_entity_id) {
                $lead->external_entity_id = \Illuminate\Support\Str::uuid()->toString();
            }
            if (!$lead->data) {
                $lead->data = [];
            }
        });
    }

    public function getUtmTags()
    {
        return [
            'UTM_SOURCE' => $this->attributes['utm_source'],
            'UTM_MEDIUM' => $this->attributes['utm_medium'],
            'UTM_CAMPAIGN' => $this->attributes['utm_campaign'],
            'UTM_CONTENT' => $this->attributes['utm_content'],
            'UTM_TERM' => $this->attributes['utm_term'],
        ];
    }

    protected function contacts(): Attribute
    {
        // No set method - we handle it in setAttribute() to prevent array expansion
        return Attribute::make(
            get: function ($value) {
                if ($value === null) {
                    return null;
                }

                // Decode JSON string if needed (since we store it as JSON)
                $decoded = is_string($value) ? json_decode($value, true) : $value;
                
                return app(LeadContactEncryptionService::class)->decrypt($decoded);
            }
        );
    }

    public function fill(array $attributes)
    {
        // Remove version and payload if they somehow got in
        unset($attributes['version'], $attributes['payload']);

        return parent::fill($attributes);
    }

    public function setAttribute($key, $value)
    {
        // Prevent version and payload from being set as separate attributes
        // These are internal keys of the encrypted contacts structure
        if ($key === 'version' || $key === 'payload') {
            return $this;
        }

        // Handle contacts specially to prevent array expansion
        if ($key === 'contacts') {
            $encrypted = app(LeadContactEncryptionService::class)->encrypt($value);
            // JSON-encode the encrypted array since we're bypassing the cast system
            // The cast will handle JSON decoding when reading
            $this->attributes['contacts'] = json_encode($encrypted);
            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}



