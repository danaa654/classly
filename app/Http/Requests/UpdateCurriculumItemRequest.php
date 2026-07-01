<?php

namespace App\Http\Requests;

use App\Models\CurriculumItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurriculumItemRequest extends FormRequest
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

        // Route parameter is named {curriculumItem} — see routes/web.php.
        $currentItem = $this->route('curriculumItem');

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
            */

            'subject_id' => [
                Rule::requiredIf($isSubject),
                'nullable',
                'exists:subjects,id',
                Rule::unique('curriculum_items', 'subject_id')
                    ->where(fn ($query) => $query
                        ->where('curriculum_id', $this->input('curriculum_id'))
                        ->where('item_type', CurriculumItem::TYPE_SUBJECT))
                    ->ignore($currentItem),
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
            'subject_id.required' => 'Select a subject.',
            'subject_id.unique' => 'That subject is already assigned to this curriculum.',
            'title.required' => 'Enter a title for this OJT item.',
            'ojt_hours.required' => 'Enter the number of OJT hours.',
        ];
    }
}