<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'notes', 'day_of_week', 'days', 'time', 'color', 'melody', 'is_active', 'snoozed_until'];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'is_active' => 'boolean',
            'snoozed_until' => 'datetime',
        ];
    }
}
