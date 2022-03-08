<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequisitionRequest extends FormRequest
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
        $rules = [
            'school_class_id' => 'required|numeric',
            'requested_number' => 'required|numeric|gt:0',
            'priority' => 'required|numeric|in:1,2,3',
            'recommendations' => 'sometimes|array',
            'recommendations.*.codpes' => 'required|numeric',
            'activities' => 'required|array',
            'activities.*' => 'required|in:Atendimento a alunos,Correção de listas de exercícios,Fiscalização de provas',
        ];

        return $rules;
    }
}
