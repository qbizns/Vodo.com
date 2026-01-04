<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'content' => $this->content,
            'author_type' => $this->author_type,
            'author_name' => $this->getAuthorName(),
            'is_customer_visible' => $this->is_customer_visible,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
