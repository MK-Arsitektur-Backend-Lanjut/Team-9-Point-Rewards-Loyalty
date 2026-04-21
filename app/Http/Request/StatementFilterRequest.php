<?php

namespace App\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StatementFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            'activity_code' => 'nullable|string|max:50',
            'point_status' => 'nullable|in:active,expired,redeemed',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal awal',
            'point_status.in' => 'Status poin harus: active, expired, atau redeemed',
            'per_page.min' => 'Jumlah data per halaman minimal 1',
            'per_page.max' => 'Jumlah data per halaman maksimal 100',
        ];
    }
}