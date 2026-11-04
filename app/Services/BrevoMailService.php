<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodigoRecuperacionMail;

class BrevoMailService
{
    /**
     * Enviar código de recuperación utilizando la API REST de Brevo (HTTPS - Puerto 443)
     * o fallback a SMTP.
     * 
     * En servidores en la nube como Render (plan gratuito), los puertos SMTP (25, 465, 587)
     * están bloqueados a nivel de firewall para prevenir spam, provocando timeouts de 60s
     * y el consecuente error 504 Gateway Timeout en Netlify.
     * La API REST funciona por HTTPS estándar (puerto 443) y envía en menos de 500 ms.
     */
    public static function enviarCodigo(string $correoDestino, string $nombreDestino, string $codigo): array
    {
        $apiKey = env('BREVO_API_KEY');

        // 1. MÉTODO RECOMENDADO: API REST de Brevo vía HTTPS (Puerto 443)
        if (!empty($apiKey)) {
            try {
                $remitenteEmail = env('MAIL_FROM_ADDRESS', 'eme9769@gmail.com');
                $remitenteNombre = env('MAIL_FROM_NAME', 'Génesis Profesional');

                $htmlContent = view('emails.codigo_recuperacion', [
                    'codigo' => $codigo,
                    'nombres' => $nombreDestino
                ])->render();

                $response = Http::timeout(6)
                    ->withHeaders([
                        'accept' => 'application/json',
                        'api-key' => $apiKey,
                        'content-type' => 'application/json',
                    ])
                    ->post('https://api.brevo.com/v3/smtp/email', [
                        'sender' => [
                            'name' => $remitenteNombre,
                            'email' => $remitenteEmail,
                        ],
                        'to' => [
                            [
                                'email' => $correoDestino,
                                'name' => $nombreDestino,
                            ]
                        ],
                        'subject' => 'Código de verificación: ' . $codigo . ' - Génesis Profesional',
                        'htmlContent' => $htmlContent,
                    ]);

                if ($response->successful()) {
                    Log::info('Código de recuperación enviado vía API REST de Brevo (HTTPS)', [
                        'destinatario' => $correoDestino,
                        'messageId' => $response->json('messageId')
                    ]);

                    return [
                        'exito'  => true,
                        'metodo' => 'api_rest',
                    ];
                }

                Log::error('Fallo al enviar correo vía API REST de Brevo', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            } catch (\Throwable $e) {
                Log::error('Excepción al conectar con API REST de Brevo: ' . $e->getMessage());
            }
        }

        // 2. MÉTODO SMTP (con timeout corto de 5s para nunca provocar 504 en Netlify)
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
