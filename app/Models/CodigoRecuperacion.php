<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CodigoRecuperacion extends Model
{
    /**
     * Tabla asociada en la base de datos PostgreSQL.
     */
    protected $table = 'codigos_recuperacion';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'correo',
        'codigo',
        'intentos',
        'utilizado',
        'expira_en',
    ];

    /**
     * Casts de tipos de datos.
     */
    protected function casts(): array
    {
        return [
            'utilizado' => 'boolean',
            'intentos' => 'integer',
            'expira_en' => 'datetime',
        ];
    }

    /**
     * Comprobar si el código está vigente y no ha sido consumido ni bloqueado por intentos.
     */
    public function esValido(): bool
    {
        return !$this->utilizado 
            && $this->expira_en->isFuture() 
            && $this->intentos < 5;
    }

    /**
     * Generar un código único de 6 dígitos para un correo, invalidando los anteriores.
     */
    public static function generarParaCorreo(string $correo): self
    {
        // 1. Invalidar códigos activos anteriores para evitar colisiones
        self::where('correo', $correo)
            ->where('utilizado', false)
            ->update(['utilizado' => true]);

        // 2. Generar código numérico criptográficamente seguro de 6 dígitos
        $codigo = sprintf('%06d', random_int(100000, 999999));

        // 3. Crear registro con 1 minuto y 30 segundos de vigencia (90 segundos según diseño)
        return self::create([
            'correo' => $correo,
            'codigo' => $codigo,
            'intentos' => 0,
            'utilizado' => false,
            'expira_en' => Carbon::now()->addSeconds(90),
        ]);
    }
}
