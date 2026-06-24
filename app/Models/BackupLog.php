<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'backup_type',
    'backup_scope',
    'file_path',
    'file_size_bytes',
    'status',
    'started_at',
    'completed_at',
    'generated_by',
    'error_message',
])]
class BackupLog extends Model
{
    public const TYPE_MANUAL = 'manual';

    public const TYPE_SCHEDULED = 'scheduled';

    public const SCOPE_PLATFORM = 'platform';

    public const SCOPE_SCHOOL = 'school';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DELETED = 'deleted';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'file_size_bytes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isDownloadable(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && filled($this->file_path);
    }

    public function isDeletable(): bool
    {
        return $this->status !== self::STATUS_DELETED;
    }
}
