<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemPointsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'points_to_redeem' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'points_to_redeem.required' => 'Points to redeem is required',
            'points_to_redeem.min' => 'Points must be at least 1',
        ];
    }
}
