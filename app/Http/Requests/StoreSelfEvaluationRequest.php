<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSelfEvaluationRequest extends FormRequest
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
            'student_amount'=>'required|integer',
            'homework_amount'=>'required|integer',
            'secondary_activity'=>'sometimes',
            'workload'=>'required|integer',
            'workload_reason'=>'sometimes',
            'comments'=>'sometimes',
        ];
    }
}
