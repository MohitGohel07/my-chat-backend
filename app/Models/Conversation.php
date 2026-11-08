<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'is_group', 'disappearing_duration'];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'disappearing_duration' => 'string',
        ];
    }

    public function participants()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
