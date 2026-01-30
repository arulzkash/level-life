<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'title', // +++
        'mood_emoji',
        'is_favorite',
        'body',
        'sections',
        'xp_awarded',
        'coin_awarded',
        'rewarded_at',
    ];


    protected $casts = [
        'date' => 'date',
        'sections' => 'array',
        'rewarded_at' => 'datetime',
        'id' => 'string',
    ];

    protected function isFavorite(): Attribute
    {
        return Attribute::make(
            // GET (Dari DB ke Frontend): 
            // Pastikan apapun yang keluar dari DB ('t', 'TRUE', 1) jadi boolean true/false
            get: function ($value) {
                if (is_bool($value)) return $value;
                return in_array($value, [1, '1', 't', 'true', 'TRUE'], true);
            },

            // SET (Dari Controller ke DB):
            // Terima boolean true/false, ubah jadi string 't'/'f' buat Postgres
            set: function ($value) {
                // Handle false/0/'f' dengan tegas
                if ($value === false || $value === 0 || $value === '0' || $value === 'f' || $value === 'false') {
                    return 'f';
                }
                return 't';
            }
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
