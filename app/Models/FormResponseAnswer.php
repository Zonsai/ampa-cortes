<?php

namespace App\Models;

use Database\Factories\FormResponseAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormResponseAnswer extends Model
{
    /** @use HasFactory<FormResponseAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'form_response_id',
        'form_field_id',
        'value',
    ];

    public function formResponse(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class);
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }

    /** Returns the decoded value for multi-value fields (checkboxes). */
    public function decodedValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        $decoded = json_decode($this->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->value;
    }
}
