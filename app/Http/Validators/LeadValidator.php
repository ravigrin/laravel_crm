<?php

namespace App\Http\Validators;

use App\Enums\DefaultStatuses;
use Illuminate\Http\Request;

trait LeadValidator
{
    protected function validateSaveLead(Request $request)
    {
        $this->validate($request, [
            'external_id' => 'string|max:150',
            'name' => 'string|max:150',
            'email' => 'email|max:150',
            'phone' => 'string|max:150',
            'messengers' => 'array',
            'data' => 'array',
            'contacts' => 'array',
            'ip_address' => 'string|max:150',
            'status' => 'integer',
            'city' => 'string|max:100',
            'country' => 'string|max:100',
            'integration_status' => 'string|max:150',
            'integration_data' => 'array',
            'is_test' => 'boolean',
            'viewed' => 'boolean',
            'paid' => 'boolean',
            'blocked' => 'boolean',
            'fingerprint' => 'string|max:255',
            'equal_answer_id' => 'integer',
            'utm_source' => 'string',
            'utm_medium' => 'string',
            'utm_campaign' => 'string',
            'utm_content' => 'string',
            'utm_term' => 'string',
            'external_project_id' => 'required|string|max:250',
            'external_system' => 'required|string|max:250',
            'external_entity' => 'required|string|max:250',
            'external_entity_id' => 'required|string|max:250',
            'delay_sec' => 'integer'
        ]);
    }

    protected function validateUpdateLead(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'external_id' => 'string|max:150',
            'name' => 'string|max:150',
            'email' => 'email|max:150',
            'phone' => 'string|max:150',
            'messengers' => 'array',
            'data' => 'array',
            'contacts' => 'array',
            'ip_address' => 'string|max:150',
            'status' => 'integer|in:' . implode(',', array_column(DefaultStatuses::cases(), 'value')),
            'city' => 'string|max:100',
            'country' => 'string|max:100',
            'integration_status' => 'string|max:150',
            'integration_data' => 'array',
            'is_test' => 'boolean',
            'viewed' => 'boolean',
            'paid' => 'boolean',
            'blocked' => 'boolean',
            'fingerprint' => 'string|max:255',
            'equal_answer_id' => 'integer',
            'utm_source' => 'string',
            'utm_medium' => 'string',
            'utm_campaign' => 'string',
            'utm_content' => 'string',
            'utm_term' => 'string',
            'external_project_id' => 'string|max:250',
            'external_system' => 'string|max:250',
            'external_entity' => 'string|max:250',
            'external_entity_id' => 'string|max:250',
            'delay_sec' => 'integer'
        ]);
    }

    protected function validateDeleteLead(Request $request)
    {
        $this->validate($request, [
            'ids' => 'required|array',
            'external_entity_id' => 'required|string|max:250'
        ]);
    }

    protected function validateGetLead(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|string|max:150',
            'external_entity_id' => 'required|string|max:250'
        ]);
    }

    protected function validateGetLeadCollection(Request $request)
    {
        $this->validate($request, [
            'external_entity_id' => 'required|string|max:250',
            'params' => 'array',
        ]);
    }
}



