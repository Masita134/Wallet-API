<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class SaveAdminMovementRequest extends FormRequest
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
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:deposit,transfer_in,transfer_out',
            'amount' => 'required|numeric|min:0.01',
            'counterpart_cbu' => 'nullable|string|max:22', //<-- esto seria opcional ya que depende el tipo de moviento que el admin realice.
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'account_id.required' => 'La cuenta es obligatoria.',
            'account_id.exists' => 'La cuenta seleccionada no existe en el sistema.',
            'type.required' => 'El tipo de movimiento es obligatorio.',
            'type.in' => 'El tipo de movimiento no es válido. Debe de ser: depósito (deposit), transferencia recibida (transfer_in) o transferencia enviada (transfer_out).',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe de ser valor númerico.',
            'amount.min' => 'El monto debe ser mayor a 0.',
        ];
    }
}
