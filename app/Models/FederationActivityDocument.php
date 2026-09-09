<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
