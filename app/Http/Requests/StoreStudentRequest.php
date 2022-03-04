<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
            'codema' => 'required',
            'sexo' => 'required|in:Masculino,Feminino',
            'cpf' => 'required|numeric',
            'endereco' => 'required',
            'complemento' => 'nullable',
            'cep' => 'required|numeric',
            'bairro' => 'required',
            'cidade' => 'required',
            'estado' => 'required',
            'tel_celular' => 'nullable|numeric',
            'tel_residencial' => 'nullable|numeric',
            'possui_conta_bb' => 'sometimes|bool',
        ];

        return $rules;
    }
}
