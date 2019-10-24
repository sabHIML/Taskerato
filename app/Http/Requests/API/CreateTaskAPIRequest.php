<?php

namespace App\Http\Requests\API;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class CreateTaskAPIRequest extends FormRequest{
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
        return Task::$rules;
    }
}
