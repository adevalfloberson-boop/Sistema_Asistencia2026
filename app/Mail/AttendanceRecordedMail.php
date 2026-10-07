<?php

namespace App\Mail;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceRecordedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Attendance $attendance, public bool $isTest = false) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isTest
                ? 'Correo de prueba — '.$this->attendance->school->name
                : 'Registro de '.mb_strtolower($this->eventLabel()).' — '.$this->attendance->school->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.attendance.recorded',
            with: [
                'eventLabel' => $this->eventLabel(),
                'notificationMessage' => $this->notificationMessage(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function eventLabel(): string
    {
        if ($this->attendance->is_early_departure) {
            return 'Salida anticipada';
        }

        return $this->attendance->tipo;
    }

    public function notificationMessage(): string
    {
        $studentName = trim(($this->attendance->student?->nombre ?? '').' '.($this->attendance->student?->apellido ?? ''));
        $message = $this->attendance->school->notificationSetting?->notification_message
            ?: 'Se ha registrado la {evento} de {estudiante}.';

        return strtr($message, [
            '{evento}' => mb_strtolower($this->eventLabel()),
            '{estudiante}' => $studentName,
            '{escuela}' => $this->attendance->school->name,
            '{fecha}' => $this->attendance->fecha_hora->format('d/m/Y'),
            '{hora}' => $this->attendance->fecha_hora->format('H:i'),
        ]);
    }
}
