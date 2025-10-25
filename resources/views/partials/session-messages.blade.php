@if (session()->has('success'))
    <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="flex justify-between items-center mb-6 p-4 bg-green-100 border border-green-400 text-green-800 dark:bg-green-900 dark:border-green-700 dark:text-green-200 rounded-lg">
        <span>{{ session('success') }}</span>
        <button @click="show = false" class="text-green-800 dark:text-green-200 hover:text-green-600 dark:hover:text-green-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
@endif

@if (session()->has('error'))
    <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="flex justify-between items-center mb-6 p-4 bg-red-100 border border-red-400 text-red-800 dark:bg-red-900 dark:border-red-700 dark:text-red-200 rounded-lg">
        <span>{{ session('error') }}</span>
        <button @click="show = false" class="text-red-800 dark:text-red-200 hover:text-red-600 dark:hover:text-red-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
@endif

@if (session()->has('warning'))
    <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="flex justify-between items-center mb-6 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-200 rounded-lg">
        <span>{{ session('warning') }}</span>
        <button @click="show = false" class="text-yellow-800 dark:text-yellow-200 hover:text-yellow-600 dark:hover:text-yellow-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
@endif

@if (session()->has('info'))
    <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="flex justify-between items-center mb-6 p-4 bg-blue-100 border border-blue-400 text-blue-800 dark:bg-blue-900 dark:border-blue-700 dark:text-blue-200 rounded-lg">
        <span>{{ session('info') }}</span>
        <button @click="show = false" class="text-blue-800 dark:text-blue-200 hover:text-blue-600 dark:hover:text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
@endif