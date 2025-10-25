<div
    x-data="{
        show: false,
        message: '',
        type: 'success',
        timeout: null,
        showNotification(detail) {
            this.message = detail.message;
            this.type = detail.type || 'success';
            this.show = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => { this.show = false }, 5000);
        }
    }"
    x-on:notify.window="showNotification($event.detail)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    style="display: none;"
    class="fixed bottom-4 right-4 z-50 rounded-lg shadow-lg p-4"
    :class="{
        'bg-green-100 border border-green-400 text-green-800 dark:bg-green-900 dark:border-green-700 dark:text-green-200': type === 'success',
        'bg-red-100 border border-red-400 text-red-800 dark:bg-red-900 dark:border-red-700 dark:text-red-200': type === 'error'
    }"
>
    <div class="flex items-center">
        <span x-text="message"></span>
        <button @click="show = false" class="ml-4 text-current">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
</div>