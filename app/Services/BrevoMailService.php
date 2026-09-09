<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodigoRecuperacionMail;

class BrevoMailService
{
    /**
     * Enviar código de recuperación utilizando el transporte SMTP configurado en .env
     * (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION)
     */
    public static function enviarCodigo(string $correoDestino, string $nombreDestino, string $codigo): array
    {
        try {
            Mail::to($correoDestino, $nombreDestino)
                ->send(new CodigoRecuperacionMail($codigo, $nombreDestino));

            Log::info('Código de recuperación enviado por SMTP', [
                'destinatario' => $correoDestino,
            ]);

            return [
                'exito'  => true,
                'metodo' => 'smtp',
            ];
        } catch (\Throwable $e) {
            Log::error('Error al enviar código de recuperación por SMTP: ' . $e->getMessage(), [
                'destinatario' => $correoDestino,
            ]);

            return [
                'exito'   => false,
                'metodo'  => 'smtp',
                'mensaje' => $e->getMessage(),
            ];
        }
    }
}
