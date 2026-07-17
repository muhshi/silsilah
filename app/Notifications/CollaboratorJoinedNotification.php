<?php

namespace App\Notifications;

use App\Models\FamilyTree;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaboratorJoinedNotification extends Notification
{
    use Queueable;

    public $collaborator;
    public $tree;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $collaborator, FamilyTree $tree)
    {
        $this->collaborator = $collaborator;
        $this->tree = $tree;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'collaborator_name' => $this->collaborator->name,
            'collaborator_email' => $this->collaborator->email,
            'collaborator_avatar' => $this->collaborator->avatar,
            'tree_name' => $this->tree->name,
            'tree_id' => $this->tree->id,
            'message' => "{$this->collaborator->name} telah menerima undangan dan bergabung sebagai editor di pohon {$this->tree->name}.",
        ];
    }
}
