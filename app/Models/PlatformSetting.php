<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function resolvedValue(): ?string
    {
        if ($this->value === null || $this->value === '') {
            return null;
        }

        if (! $this->is_encrypted) {
            return $this->value;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Throwable) {
            return null;
        }
    }
}
