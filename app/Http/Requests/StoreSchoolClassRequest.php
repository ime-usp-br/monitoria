<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolClassRequest extends FormRequest
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
            'periodoId' => 'required|numeric',
            'department_id' => 'required|numeric',
            'codtur' => 'required|numeric',
            'coddis' => 'required',
            'nomdis' => 'required',
            'tiptur' => 'required',
            'dtainitur' => 'required|date_format:d/m/Y|before:dtafimtur',
            'dtafimtur' => 'required|date_format:d/m/Y',
            'horarios' => 'sometimes|array',
            'horarios.*.diasmnocp' => 'required|in:seg,ter,qua,qui,sex,sab,dom',
            'horarios.*.horent' => 'required|date_format:H:i|before:horarios.*.horsai',
            'horarios.*.horsai' => 'required|date_format:H:i',
            'instrutores' => 'required|array',
            'instrutores.*.codpes' => 'required|numeric',
        ];

        return $rules;
    }
}
