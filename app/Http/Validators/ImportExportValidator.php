<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ImportExportValidator
{
    protected function validateExport(Request $request)
    {
        $this->validate($request, [
            'from' => 'date',
            'to' => 'date',
            'external_entity_id' => 'required_without:external_project_id',
            'external_project_id' => 'required_without:external_entity_id'
        ]);
    }

    protected function validateDownload(Request $request)
    {
        Validator::make($request->query(), [
            'type'=>'required|in:project,entity',
            'filename'=>'required|string',
        ])->validate();
    }
}



