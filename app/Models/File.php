<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ["user_id", "name", "file"];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
