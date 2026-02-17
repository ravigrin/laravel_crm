<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;

trait StatusValidator
{
    protected function validateSaveStatus(Request $request)
    {
        $this->validate($request, [
            'external_entity_id' => 'required|string|max:250',
            'label' => 'required|string|max:250',
            'status_id' => 'required|integer'
        ]);
    }

    protected function validateUpdateStatus(Request $request)
    {
        $this->validate($request, [
            'status_id' => 'required|integer',
            'external_entity_id' => 'required|string|max:250',
            'label' => 'string|max:250',
            'color' => 'string|max:250'
        ]);
    }

    protected function validateDeleteStatus(Request $request)
    {
        $this->validate($request, [
            'status_id' => 'required|integer',
            'external_entity_id' => 'required|string|max:250',
        ]);
    }

    protected function validateShowStatus(Request $request)
    {
        $this->validate($request, [
            'status_id' => 'required|integer',
            'external_entity_id' => 'required|string|max:250',
        ]);
    }

    protected function validateIndexStatuses(Request $request)
    {
        $this->validate($request, [
            'external_entity_id' => 'string|max:250',
        ]);
    }
}



