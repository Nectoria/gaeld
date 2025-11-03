<?php

namespace App\Mail;

use App\Models\CompanyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CompanyInvitation $invitation
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join ' . $this->invitation->company->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Ensure expires_at is a Carbon instance
        $expiresAt = $this->invitation->expires_at;
        if (is_string($expiresAt)) {
            $expiresAt = \Carbon\Carbon::parse($expiresAt);
        }

        return new Content(
            markdown: 'emails.company-invitation',
            with: [
                'companyName' => $this->invitation->company->name,
                'inviterName' => $this->invitation->inviter->name,
                'role' => ucfirst($this->invitation->role),
                'acceptUrl' => route('invitations.accept', $this->invitation->token),
                'expiresAt' => $expiresAt->format('F j, Y'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
