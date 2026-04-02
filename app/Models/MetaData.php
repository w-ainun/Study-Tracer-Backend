<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaData extends Model
{
    protected $table = 'meta_data';
    protected $fillable = [
        'title',
        'description',
        'icon',
    ];
}
