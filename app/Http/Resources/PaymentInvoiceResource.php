<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentInvoiceResource extends JsonResource
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
            'academic_year' => $this->academicyear->coresubcategories->core_subcategory_name,
            'academic_year_id' => $this->academic_year_id,
            'payment_invoice' => $this->payment_invoice,
            'transaction_id' => $this->transaction_id,
            'transaction_date' => $this->transaction_date,
            'amount' => $this->amount,
            'status' => $this->status
        ];
    }
}
