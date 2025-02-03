<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiFetchLog extends Model
{
    use HasFactory;

    protected $fillable = ['source', 'type', 'success', 'error_message'];
}
