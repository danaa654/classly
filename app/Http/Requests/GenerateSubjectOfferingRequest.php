<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateSubjectOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role gating already happens in SubjectOfferingController's
        // middleware — this stays true and lets Laravel's normal
        // validation flow run. (SubjectOfferingPolicy::generate() is
        // additionally checked explicitly in the controller.)
        return true;
    }

    public function rules(): array
    {
        return [

            'academic_term_id' => [
                'required',
                'integer',
                'exists:academic_terms,id',
            ],

        ];
    }
}