<x-filament-panels::page>
    <form wire:submit="saveAll">
        {{ $this->form }}
        
        {{-- Data Entry Buttons --}}
        <div class="mt-6 flex flex-wrap gap-3">
            <x-filament::button 
                type="submit" 
                icon="heroicon-o-check-circle"
                size="lg"
            >
                Save All Data
            </x-filament::button>

            <x-filament::button 
                type="button" 
                wire:click="generateReport"
                color="success"
                icon="heroicon-o-document-text"
                size="lg"
            >
                Generate & Share Report
            </x-filament::button>
        </div>
    </form>

    {{-- Saved Status --}}
    @if($this->eggsSaved || $this->feedSaved || $this->waterSaved || $this->mortalitySaved)
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-check-circle class="h-5 w-5 text-green-500" />
                        Saved Records
                    </div>
                </x-slot>
                
                <div class="flex flex-wrap gap-4">
                    @if($this->eggsSaved)
                        <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 px-3 py-2 rounded-lg">
                            <x-heroicon-o-check class="h-5 w-5" />
                            <span>Eggs recorded</span>
                        </div>
                    @endif
                    
                    @if($this->feedSaved)
                        <div class="flex items-center gap-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 px-3 py-2 rounded-lg">
                            <x-heroicon-o-check class="h-5 w-5" />
                            <span>Feed recorded</span>
                        </div>
                    @endif
                    
                    @if($this->waterSaved)
                        <div class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 px-3 py-2 rounded-lg">
                            <x-heroicon-o-check class="h-5 w-5" />
                            <span>Water recorded</span>
                        </div>
                    @endif
                    
                    @if($this->mortalitySaved)
                        <div class="flex items-center gap-2 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 px-3 py-2 rounded-lg">
                            <x-heroicon-o-check class="h-5 w-5" />
                            <span>Mortality recorded</span>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- Generated Report Section --}}
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
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 font-mono text-sm whitespace-pre-wrap border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto">
                        {{ $this->generatedMessage }}
                    </div>
                    
                    <div class="flex flex-wrap gap-3">
                        <x-filament::button
                            tag="a"
                            href="{{ $this->whatsappUrl }}"
                            target="_blank"
                            color="success"
                            icon="heroicon-o-paper-airplane"
                            size="lg"
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

    {{-- Quick Tips --}}
    <div class="mt-6">
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-gray-500" />
                    Quick Tips
                </div>
            </x-slot>
            
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                <li class="flex items-start gap-2">
                    <span class="text-green-500">•</span>
                    <span>Fill in eggs, feed, water, and mortality data, then click <strong>"Save All Data"</strong> to save everything at once.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-500">•</span>
                    <span>Click <strong>"Generate & Share Report"</strong> to create a WhatsApp message with all daily data.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-amber-500">•</span>
                    <span>Expand the <strong>"Daily Report Information"</strong> section to add climate, health conditions, and treatments.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-red-500">•</span>
                    <span>The <strong>Mortality</strong> section is collapsed by default - expand it if you need to record deaths.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-gray-500">•</span>
                    <span>Eggs and water records are updated if they exist for the same batch/date. Feed and mortality create new entries.</span>
                </li>
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
