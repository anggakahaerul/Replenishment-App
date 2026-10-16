<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportReplenishmentRequest extends FormRequest
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
            'report_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10MB
            ],
            'analysis_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10MB
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'report_file.required' => 'File Replenishment Report wajib diunggah.',
            'report_file.mimes' => 'File Replenishment Report harus berformat .xlsx atau .xls.',
            'report_file.max' => 'Ukuran file Replenishment Report maksimal 10MB.',
            'analysis_file.required' => 'File Analisa Replenishment wajib diunggah.',
            'analysis_file.mimes' => 'File Analisa Replenishment harus berformat .xlsx atau .xls.',
            'analysis_file.max' => 'Ukuran file Analisa Replenishment maksimal 10MB.',
        ];
    }
}
