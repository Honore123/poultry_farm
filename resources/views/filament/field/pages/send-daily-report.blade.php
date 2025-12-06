<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}
        
        <div class="mt-6 flex gap-3">
            <x-filament::button type="submit" icon="heroicon-o-document-text">
                Generate Report
            </x-filament::button>
        </div>
    </form>

    @if($this->generatedMessage)
        <div class="mt-8">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chat-bubble-left-ellipsis class="h-5 w-5 text-green-500" />
                        Generated WhatsApp Message
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 font-mono text-sm whitespace-pre-wrap border border-gray-200 dark:border-gray-700">
                        {{ $this->generatedMessage }}
                    </div>
                    
                    <div class="flex flex-wrap gap-3">
                        <x-filament::button
                            tag="a"
                            href="{{ $this->whatsappUrl }}"
                            target="_blank"
                            color="success"
                            icon="heroicon-o-paper-airplane"
                        >
                            Open in WhatsApp
                        </x-filament::button>
                        
                        <x-filament::button
                            color="gray"
                            icon="heroicon-o-clipboard-document"
                            x-data="{}"
                            x-on:click="
                                navigator.clipboard.writeText(@js($this->generatedMessage));
                                $wire.copyMessage();
                            "
                        >
                            Copy Message
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>

