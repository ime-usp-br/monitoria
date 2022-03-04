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
            'started_at' => 'required',
            'finished_at' => 'required',
            'start_date_teacher_requests' => 'required',
            'end_date_teacher_requests' => 'required',
            'start_date_student_registration' => 'required',
            'end_date_student_registration' => 'required',
        ];

        return $rules;
    }
}
