<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
                'nullable',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10MB
            ],
            'analysis_file' => [
                'nullable',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10MB
            ],
            'target_shift' => [
                'nullable',
                'string',
                'in:SHIFT 1,SHIFT 2',
            ],
            'category_mode' => [
                'nullable',
                'string',
                'in:AUTO,DRY,FROZEN',
            ],
            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],
        ];
    }

    /**
     * Ensure at least one Excel file is provided.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->hasFile('report_file') && !$this->hasFile('analysis_file')) {
                $validator->errors()->add(
                    'report_file',
                    'Minimal unggah salah satu berkas: Replenishment Report atau Analisa Replenishment.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'report_file.file' => 'Berkas Replenishment Report tidak valid.',
            'report_file.mimes' => 'File Replenishment Report harus berformat .xlsx atau .xls.',
            'report_file.max' => 'Ukuran file Replenishment Report maksimal 10MB.',
            'analysis_file.file' => 'Berkas Analisa Replenishment tidak valid.',
            'analysis_file.mimes' => 'File Analisa Replenishment harus berformat .xlsx atau .xls.',
            'analysis_file.max' => 'Ukuran file Analisa Replenishment maksimal 10MB.',
            'target_shift.in' => 'Target shift harus SHIFT 1 atau SHIFT 2.',
            'category_mode.in' => 'Kategori data harus AUTO, DRY, atau FROZEN.',
        ];
    }
}
