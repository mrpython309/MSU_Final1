<?php

namespace App\Http\Requests\SupportTicket;

use App\AssetStatus;
use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

            'asset_id'       => [
                'nullable',
                Rule::exists('assets', 'id')->where(function ($query) {
                    $currentAssetId = $this->currentTicketAssetId();

                    $query->where(function ($inner) use ($currentAssetId) {
                        $inner->where(function ($statusQuery) {
                            $statusQuery->whereNull('status')
                                ->orWhere('status', '!=', AssetStatus::DISPOSED->value);
                        });

                        if ($currentAssetId) {
                            $inner->orWhere('id', $currentAssetId);
                        }
                    });
                }),
            ],

            'description'    => ['nullable', 'string', 'max:5000'],

            'filename' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.exists' => 'Disposed assets cannot be selected for a support ticket.',
        ];
    }

    private function currentTicketAssetId(): ?int
    {
        $ticket = $this->route('supportTicket')
            ?? $this->route('support_ticket');

        if ($ticket instanceof SupportTicket) {
            return $ticket->asset_id ? (int) $ticket->asset_id : null;
        }

        if (is_numeric($ticket)) {
            $assetId = SupportTicket::query()->whereKey($ticket)->value('asset_id');

            return $assetId ? (int) $assetId : null;
        }

        return null;
    }
}
