<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Controllers\UserController;
use App\Models\User;

class UserRequest extends FormRequest
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
        $id = $this->route('usuario');
        $rules = [
            'name' => 'required',
            'email' => ['required', 'unique:App\Models\User,email,' . $id],
            'roles' => 'required|array|min:1',
        ];

        return $rules;
    }
}
