<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
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
            'disponibilidade_diurno' => 'sometimes|bool',
            'disponibilidade_noturno' => 'sometimes|bool',
            'voluntario' => 'sometimes|bool',
            'observacoes' => 'nullable|max:65500',
            'preferencia_horario' => 'required',
            'scholarships' => 'sometimes|array',
            'scholarships.*' => 'required|numeric|exists:App\Models\Scholarship,id',
        ];

        return $rules;
    }
}
