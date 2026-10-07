<?php

namespace App\Mail;

use App\Models\BiometricDevice;
use App\Models\School;
use App\Models\SchoolNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeviceStatusSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    /**
     * @param  Collection<int, BiometricDevice>  $devices
     */
    public function __construct(
        public School $school,
        public Collection $devices,
        public SchoolNotificationSetting $setting,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->setting->from_address, $this->setting->from_name),
            subject: "Resumen de lectores: {$this->school->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.device.status-summary',
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
}
