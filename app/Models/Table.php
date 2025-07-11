<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'capacity',
        'status',
        'qr_code_path',
    ];

    /**
     * Get the orders for the table.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
