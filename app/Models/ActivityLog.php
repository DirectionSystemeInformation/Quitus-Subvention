<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'causer_id',
        'causer_name',
        'subject_id',
        'subject_name',
        'action',
        'description',
    ];

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public static function record(string $action, string $description, ?int $subjectId = null, ?string $subjectName = null): self
    {
        $causer = Auth::user();

        return self::create([
            'causer_id' => $causer?->id,
            'causer_name' => $causer?->name,
            'subject_id' => $subjectId,
            'subject_name' => $subjectName,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
