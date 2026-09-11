<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FederationActivityDocument extends Model
{
    protected $fillable = [
        'federation_activity_id',
        'file_path',
        'original_filename',
    ];

    public function activity()
    {
        return $this->belongsTo(FederationActivity::class, 'federation_activity_id');
    }

    public function extensionLabel(): string
    {
        return strtoupper(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }

    public function nameWithoutExtension(): string
    {
        return pathinfo($this->original_filename, PATHINFO_FILENAME);
    }

    public function sizeLabel(): ?string
    {
        if (! Storage::disk('local')->exists($this->file_path)) {
            return null;
        }

        $bytes = Storage::disk('local')->size($this->file_path);

        if ($bytes < 1024) {
            return $bytes.' o';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', ' ').' Ko';
        }

        return number_format($bytes / (1024 * 1024), 1, ',', ' ').' Mo';
    }
}
