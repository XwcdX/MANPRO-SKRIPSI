@props(['wireModel', 'label', 'required' => false, 'disabled' => false, 'min' => null, 'max' => null])

<div x-data="{
    displayValue: '',
    internalValue: @entangle($wireModel).live,
    init() {
        this.updateDisplay();
        this.$watch('internalValue', () => this.updateDisplay());
    },
    updateDisplay() {
        if (this.internalValue) {
            const [year, month, day] = this.internalValue.split('-');
            this.displayValue = day + '/' + month + '/' + year;
        } else {
            this.displayValue = '';
        }
    },
    openPicker() {
        this.$refs.datePicker.showPicker();
    }
}">
    @if($label)
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
            {{ $label }}@if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    <div class="relative">
        <input 
            type="text" 
            x-model="displayValue"
            @click="openPicker"
            placeholder="DD/MM/YYYY"
            readonly
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge(['class' => 'block w-full h-10 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-black dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 cursor-pointer']) }}
        >
        <input 
            type="date" 
            x-ref="datePicker"
            x-model="internalValue"
            {{ $disabled ? 'disabled' : '' }}
            @if($min) min="{{ $min }}" @endif
            @if($max) max="{{ $max }}" @endif
            class="absolute inset-0 w-full h-full opacity-0 pointer-events-none"
        >
    </div>
</div>
