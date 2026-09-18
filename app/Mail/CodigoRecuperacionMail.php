<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodigoRecuperacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $codigo;
    public string $nombres;

    /**
     * Crear una nueva instancia del mailable.
     */
    public function __construct(string $codigo, string $nombres = 'Usuario')
    {
        $this->codigo = $codigo;
        $this->nombres = $nombres;
    }

    /**
     * Configurar el sobre del correo con el remitente exacto del servicio SMTP.
     */
    public function envelope(): Envelope
    {
        $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@ugb.edu.sv');
        $fromName = env('MAIL_FROM_NAME', 'Génesis Profesional UGB');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'Código de verificación: ' . $this->codigo . ' - Génesis Profesional',
        );
    }

    /**
     * Definir la vista del contenido.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo_recuperacion',
        );
    }

    /**
     * Configurar cabeceras de alta prioridad para evitar filtro de cuarentena / spam en Microsoft 365 y Exchange.
     */
    public function callbacks(): array
    {
        return [
            function ($message) {
                $headers = $message->getHeaders();
                $headers->addTextHeader('Auto-Submitted', 'auto-generated');
                $headers->addTextHeader('X-Auto-Response-Suppress', 'All');
                $headers->addTextHeader('X-Priority', '1 (Highest)');
                $headers->addTextHeader('X-MSMail-Priority', 'High');
                $headers->addTextHeader('Importance', 'High');
                $headers->addTextHeader('X-Mailer', 'GenesisProfesional-SMTP/1.0');
            }
        ];
    }

    /**
     * Adjuntos.
     */
    public function attachments(): array
    {
        return [];
    }
}
