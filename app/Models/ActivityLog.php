<?php

namespace App\Models;

use App\Models\Concerns\IsImmutableContextualLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['school_id', 'user_id', 'module', 'action', 'description', 'subject_type', 'subject_id', 'ip_address', 'user_agent', 'created_at'])]
class ActivityLog extends Model
{
    use IsImmutableContextualLog;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
