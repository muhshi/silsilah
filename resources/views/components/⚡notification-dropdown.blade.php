<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component
{
    public function markAsRead($notificationId)
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function with()
    {
        return [
            'notifications' => auth()->user()->notifications()->take(5)->get(),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ];
    }
};
?>

<div>
    <flux:dropdown position="bottom-end">
        <flux:button variant="ghost" class="relative !h-10 !w-10 !px-0 rounded-full text-on-surface-variant hover:text-primary hover:bg-primary/10">
            <span class="material-symbols-outlined text-xl">notifications</span>
            @if($unreadCount > 0)
                <span class="absolute top-2 right-2 flex h-2.5 w-2.5 items-center justify-center rounded-full bg-red-500 text-[8px] font-bold text-white ring-2 ring-surface"></span>
            @endif
        </flux:button>

        <flux:menu class="w-80 p-0 overflow-hidden border border-outline-variant/30 shadow-xl rounded-xl">
            <div class="px-4 py-3 bg-surface-container-low border-b border-outline-variant/30 flex justify-between items-center">
                <span class="font-bold text-on-surface text-sm">Notifikasi</span>
                @if($unreadCount > 0)
                    <button wire:click="markAllAsRead" class="text-xs text-primary hover:underline font-medium">Tandai Semua Dibaca</button>
                @endif
            </div>

            <div class="max-h-[300px] overflow-y-auto">
                @if($notifications->count() > 0)
                    @foreach($notifications as $notification)
                        <div class="px-4 py-3 border-b border-outline-variant/10 hover:bg-surface-container-lowest transition-colors flex gap-3 relative group {{ $notification->read_at ? 'opacity-70' : 'bg-primary/5' }}">
                            <div class="shrink-0 mt-0.5">
                                @if(isset($notification->data['collaborator_avatar']))
                                    <img src="{{ $notification->data['collaborator_avatar'] }}" class="w-8 h-8 rounded-full border border-outline-variant/30">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined text-sm">info</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-on-surface leading-tight mb-1">
                                    {{ $notification->data['message'] ?? 'Ada notifikasi baru.' }}
                                </p>
                                <p class="text-[10px] text-on-surface-variant">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @if(!$notification->read_at)
                                <button wire:click="markAsRead('{{ $notification->id }}')" class="absolute top-3 right-3 w-2 h-2 rounded-full bg-primary" title="Tandai Dibaca"></button>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="px-4 py-8 text-center text-on-surface-variant flex flex-col items-center">
                        <span class="material-symbols-outlined text-4xl mb-2 opacity-30">notifications_off</span>
                        <p class="text-sm">Belum ada notifikasi.</p>
                    </div>
                @endif
            </div>
        </flux:menu>
    </flux:dropdown>
</div>