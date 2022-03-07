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
            'max_enrollments' => 'required|numeric',
            'started_at' => 'required',
            'finished_at' => 'required',
            'start_date_requisitions' => 'required',
            'end_date_requisitions' => 'required',
            'start_date_enrollments' => 'required',
            'end_date_enrollments' => 'required',
        ];

        return $rules;
    }
}
