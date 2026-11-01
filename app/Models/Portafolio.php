<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portafolio extends Model
{
    use HasFactory;

    protected $table = 'portafolios';

    protected $fillable = [
        'usuario_id',
        'link_portafolio',
    ];

    /**
     * Relación con el usuario propietario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}

