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
        'titulo',
        'link_portafolio',
    ];

    /**
     * Accesor para permitir $portafolio->link
     */
    public function getLinkAttribute()
    {
        return $this->link_portafolio;
    }

    /**
     * Mutador para permitir $portafolio->link = '...'
     */
    public function setLinkAttribute($value)
    {
        $this->attributes['link_portafolio'] = $value;
    }

    /**
     * Relación con el usuario propietario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
