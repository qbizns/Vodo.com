<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityFieldValue extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'entity_field_values';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'record_id',
        'field_slug',
        'value',
    ];

    /**
     * Get the record this value belongs to.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(EntityRecord::class, 'record_id');
    }

    /**
     * Get the field definition.
     */
    public function getFieldDefinition(): ?EntityField
    {
        $record = $this->record;
        if (!$record) return null;

        return EntityField::where('entity_name', $record->entity_name)
            ->where('slug', $this->field_slug)
            ->first();
    }

    /**
     * Get casted value.
     */
    public function getCastedValue()
    {
        $field = $this->getFieldDefinition();
        if (!$field) return $this->value;

        return $field->castFromStorage($this->value);
    }
}
