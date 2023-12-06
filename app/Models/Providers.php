<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Providers extends Model
{
    use HasFactory;

    public function endpoints(): HasOne
    {
        return $this->hasOne(Endpoints::class,'provider_id');
    }
}
