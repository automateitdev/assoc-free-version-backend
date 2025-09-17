<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionOpsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unique_number' => $this->unique_number,
            'applicant_name' => $this->applicant_name,
            'payment_date' => $this->transaction_date,
            'transaction_id' => $this->transaction_id,
            'invoice' => $this->invoice_no,
            'total_amount' => $this->total_amount,
        ];
    }
}
