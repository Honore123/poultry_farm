<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="verify">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            Verify Code
        </x-filament::button>
    </x-filament-panels::form>

    <div class="mt-4 text-center space-y-2">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Didn't receive the code?
        </p>
        
        <div class="flex justify-center gap-4">
            <x-filament::button
                wire:click="resend"
                color="gray"
                size="sm"
            >
                Resend Code
            </x-filament::button>
            
            <x-filament::button
                wire:click="cancel"
                color="danger"
                size="sm"
                outlined
            >
                Cancel
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page.simple>

