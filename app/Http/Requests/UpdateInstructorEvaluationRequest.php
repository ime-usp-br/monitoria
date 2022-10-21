<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstructorEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'selection_id'=>'required|integer',
            'ease_of_contact'=>'required|in:0,1,2',
            'efficiency'=>'required|in:0,1,2',
            'reliability'=>'required|in:0,1,2',
            'overall'=>'required|in:0,1,2',
            'comments'=>'sometimes|max:512',
        ];
    }
}
