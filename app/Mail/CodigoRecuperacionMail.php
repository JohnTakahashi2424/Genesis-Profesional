<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
     * Configurar el sobre del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de verificación para restablecer tu contraseña - Génesis Profesional',
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
     * Adjuntos.
     */
    public function attachments(): array
    {
        return [];
    }
}
