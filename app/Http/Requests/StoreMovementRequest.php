<?php

namespace App\Http\Requests;

use App\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => [
                'required',
                Rule::enum(MovementType::class),
            ],

            'amount' => [
                'required_if:type,deposit,withdrawal',
                'prohibited_if:type,buy',
                'prohibited_if:type,sell',
                'nullable',
                'numeric',
                'gt:0',
            ],

            'instrument' => [
                'required_if:type,buy,sell',
                'prohibited_if:type,deposit',
                'prohibited_if:type,withdrawal',
                'nullable',
                'string',
                'max:20',
            ],

            'quantity' => [
                'required_if:type,buy,sell',
                'prohibited_if:type,deposit',
                'prohibited_if:type,withdrawal',
                'nullable',
                'integer',
                'min:1',
            ],

            'price' => [
                'required_if:type,buy,sell',
                'prohibited_if:type,deposit',
                'prohibited_if:type,withdrawal',
                'nullable',
                'numeric',
                'gt:0',
            ],
        ];
    }
}
