<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandStatus extends Model
{
    use HasFactory;
    protected $fillable=['topic','command','published_at','message'];
}
