<?php

namespace App\Http\Requests\SupportTicket;

use Illuminate\Foundation\Http\FormRequest;

class SaveSupportTicketRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       return [
            'subject'        => ['required', 'string', 'max:255'],

            'company_id'     => ['required', 'exists:companies,id'],

            'branch_id'      => ['required', 'exists:branches,id'],

            'division_id' => ['required', 'exists:divisions,id'],

            'priority'       => ['required', 'in:low,normal,high,urgent'],

            'due_date'       => ['nullable', 'date'],

            'asset_id'       => ['nullable', 'exists:assets,id'],

            'description'    => ['nullable', 'string', 'max:5000'],

            'filename' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ];
    }
}
