<?php

namespace App\Models;

use App\Enums\FormFieldType;
use Database\Factories\FormFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormField extends Model
{
    /** @use HasFactory<FormFieldFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'form_id',
        'type',
        'label',
        'description',
        'is_required',
        'sort_order',
        'options',
        'validation_rules',
    ];

    protected function casts(): array
    {
        return [
            'type' => FormFieldType::class,
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'options' => 'array',
            'validation_rules' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function formResponseAnswers(): HasMany
    {
        return $this->hasMany(FormResponseAnswer::class);
    }
}
