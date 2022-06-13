<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolTermRequest extends FormRequest
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
            'year' => 'required|numeric',
            'period' => 'required|in:1° Semestre,2° Semestre',
            'status' => 'required|in:Aberto,Aberto para inscrições,Fechado',
            'evaluation_period' => 'required|in:Aberto,Fechado',
            'max_enrollments' => 'required|numeric|gt:0',
            'started_at' => 'required|date_format:d/m/Y|before:finished_at',
            'finished_at' => 'required|date_format:d/m/Y',
            'start_date_requisitions' => 'required|date_format:d/m/Y|before:end_date_requisitions',
            'end_date_requisitions' => 'required|date_format:d/m/Y',
            'start_date_enrollments' => 'required|date_format:d/m/Y|before:end_date_enrollments',
            'end_date_enrollments' => 'required|date_format:d/m/Y',
        ];

        return $rules;
    }
}
