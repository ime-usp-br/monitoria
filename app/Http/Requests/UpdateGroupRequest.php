<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
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
            'dtainitur' => 'required|date_format:Y-m-d|before:dtafimtur',
            'dtafimtur' => 'required|date_format:Y-m-d',
            'horarios' => 'sometimes|array',
            'horarios.*.diasmnocp' => 'required|in:seg,ter,qua,qui,sex,sab,dom',
            'horarios.*.horent' => 'required|date_format:H:i|before:horarios.*.horsai',
            'horarios.*.horsai' => 'required|date_format:H:i',
            'instrutores' => 'sometimes|array',
            'instrutores.*.codpes' => 'required|numeric',
        ];

        return $rules;
    }
}
