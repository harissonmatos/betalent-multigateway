<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para atribuição em massa (fillable)
     */
    protected $fillable = [
        'name',
        'amount',
    ];
}
