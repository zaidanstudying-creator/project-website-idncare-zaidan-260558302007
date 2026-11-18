<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuggestReportTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::MAHASISWA;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([ReportType::FACILITAS, ReportType::ASPIRASI, ReportType::PELANGGARAN])],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
        ];
    }
}
