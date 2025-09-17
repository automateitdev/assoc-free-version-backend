<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DateSetupResource extends JsonResource
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
            'fee_head_id' => new FeeHeadResource($this->feeHead),
            'fee_subhead_id' => new FeeSubheadResource($this->feeSubhead),
            'fee_payable_date' => $this->fee_payable_date,
            'fine_active_date' => $this->fine_active_date,
            'fee_expire_date' => $this->fee_expire_date
        ];
    }
}
