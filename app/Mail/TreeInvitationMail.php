<?php

namespace App\Mail;

use App\Models\FamilyTree;
use App\Models\TreeInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TreeInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;
    public $tree;

    /**
     * Create a new message instance.
     */
    public function __construct(TreeInvitation $invitation, FamilyTree $tree)
    {
        $this->invitation = $invitation;
        $this->tree = $tree;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Undangan Kolaborasi Pohon Silsilah: ' . $this->tree->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tree-invitation',
            with: [
                'acceptUrl' => url('/invitations/accept/' . $this->invitation->token),
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
