<?php

namespace App\Mail;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class NuevaCotizacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cotizacion;
    public $negocio;

    /**
     * Create a new message instance.
     */
    public function __construct(Cotizacion $cotizacion)
    {
        $this->cotizacion = $cotizacion;
        $this->negocio = $cotizacion->trabajo->negocio;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva Cotización Disponibles - ' . ($this->negocio->nombre ?? 'Mantenere'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.nueva_cotizacion',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        
        if ($this->cotizacion->archivo) {
            $path = storage_path('app/public/' . $this->cotizacion->archivo);
            if (file_exists($path)) {
                $attachments[] = Attachment::fromPath($path);
            }
        }

        return $attachments;
    }
}
