<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;

trait IntegrationValidator
{
    protected function validateSaveCredentials(Request $request)
    {
        $this->validate($request, [
            'external_entity_id' => 'required_without:external_project_id',
            'external_project_id' => 'required_without:external_entity_id',
            'credentials' => 'required|array',
        ]);
    }

    protected function validateUpdateCredentials(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'credentials' => 'required|array',
        ]);
    }

    protected function validateDeleteCredentials(Request $request)
    {
        $this->validate($request, [
            'ids' => 'required|array'
        ]);
    }

    protected function validateGetCredentials(Request $request)
    {
        $this->validate($request, [
            'external_entity_id' => 'required_without:external_project_id',
            'external_project_id' => 'required_without:external_entity_id',
        ]);
    }

    protected function validateTestConnection(Request $request)
    {
        $this->validate($request, [
            'credentials' => 'required|array',
        ]);
    }
}



