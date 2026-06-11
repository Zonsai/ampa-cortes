<?php

namespace App\Models;

use Database\Factories\FormTargetItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormTargetItem extends Model
{
    /** @use HasFactory<FormTargetItemFactory> */
    use HasFactory;

    protected $fillable = [
        'form_id',
        'targetable_type',
        'targetable_id',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }
}
