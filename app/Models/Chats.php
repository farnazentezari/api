<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chats extends Model
{
    use HasFactory;
    protected $table = "chats";
    protected $fillable = [
        'name',
        'sub_name',
        'logo',
        'prompt',
        'status',
        'description'
    ];
}
