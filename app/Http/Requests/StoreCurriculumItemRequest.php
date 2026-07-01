<?php

namespace App\Http\Requests;

use App\Models\CurriculumItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumItemRequest extends FormRequest
{
    /**
     * Role/permission checks for this resource are handled by the
     * controller's route middleware (Admin|Registrar), same as every
     * other Curriculum-related controller — this just validates shape.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isSubject = $this->input('item_type') === CurriculumItem::TYPE_SUBJECT;
        $isOjt = $this->input('item_type') === CurriculumItem::TYPE_OJT;

        return [

            'curriculum_id' => [
                'required',
                'exists:curricula,id',
            ],

            'item_type' => [
                'required',
                Rule::in(CurriculumItem::ITEM_TYPES),
            ],

            /*
            |--------------------------------------------------------------------------
            | Subject fields (item_type = Subject only)
            |--------------------------------------------------------------------------
            |
            | An array, not a single subject_id — the Create form lets the
            | user check off several subjects at once to place them all into
            | the same year_level/semester in one submit. Duplicate-vs-
            | already-assigned checking happens in the controller (so it can
            | silently skip already-assigned subjects instead of failing the
            | whole batch), not here.
            |
            */

            'subject_ids' => [
                Rule::requiredIf($isSubject),
                'array',
                'min:' . ($isSubject ? 1 : 0),
            ],

            'subject_ids.*' => [
                'integer',
                'exists:subjects,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | OJT fields (item_type = OJT only)
            |--------------------------------------------------------------------------
            */

            'title' => [
                Rule::requiredIf($isOjt),
                'nullable',
                'string',
                'max:255',
            ],

            'ojt_hours' => [
                Rule::requiredIf($isOjt),
                'nullable',
                'integer',
                'min:1',
                'max:2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Shared placement fields
            |--------------------------------------------------------------------------
            */

            'year_level' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'semester' => [
                'required',
                'integer',
                Rule::in([
                    CurriculumItem::SEMESTER_FIRST,
                    CurriculumItem::SEMESTER_SECOND,
                    CurriculumItem::SEMESTER_SUMMER,
                ]),
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'subject_ids.required' => 'Select at least one subject.',
            'subject_ids.min' => 'Select at least one subject.',
            'title.required' => 'Enter a title for this OJT item.',
            'ojt_hours.required' => 'Enter the number of OJT hours.',
        ];
    }
}