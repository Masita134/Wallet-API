<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SimulateFixedTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'duration' => 'required|integer|min:30|max:365',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser numérico.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'duration.required' => 'El plazo en días es obligatorio.',
            'duration.integer' => 'El plazo debe ser un número entero de días.',
            'duration.min' => 'El plazo mínimo permitido es de 30 días.',
            'duration.max' => 'El plazo máximo permitido es de 365 días.',
        ];
    }
}
