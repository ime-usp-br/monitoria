<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TriggerAttendanceRecordsRequest extends FormRequest
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
            'frequencies_id' => 'required|array',
            'frequencies_id.*' => 'required|numeric',
        ];

        return $rules;
    }
}
