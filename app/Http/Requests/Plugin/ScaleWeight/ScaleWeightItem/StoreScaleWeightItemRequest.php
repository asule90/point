<?php

namespace App\Http\Requests\Plugin\ScaleWeight\ScaleWeightItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreScaleWeightItemRequest extends FormRequest
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
        return [
            'machine_code' => 'required',
            'form_number' => 'required',
            'vendor' => 'required',
            'item' => 'required',
            'gross_weight' => 'required|numeric|min:1',
            'tare_weight' => 'required|numeric|min:0',
            'net_weight' => 'required|numeric|min:1',
            'time' => 'required',
            'user' => 'required',
        ];
    }
}
