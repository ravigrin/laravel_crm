<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;

trait EmailTemplateValidator
{
    protected function validateSaveTemplate(Request $request)
    {
        $this->validate($request, [
            'template_id'=>'required',
            'locale_code'=>'required',
            'template_code'=>'required',
        ]);
    }

    protected function validateUpdateTemplate(Request $request)
    {
        $this->validate($request, [
            'template_id'=>'required',
            'locale_code'=>'required',
            'template_code'=>'required',
        ]);
    }

    protected function validateGetTemplate(Request $request)
    {
        $this->validate($request, [
            'template_id'=>'required'
        ]);
    }

    protected function validateDeleteTemplate(Request $request)
    {
        $this->validate($request, [
            'template_id'=>'required'
        ]);
    }
}



