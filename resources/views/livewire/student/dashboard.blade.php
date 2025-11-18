<?php

use App\Traits\WithAuthUser;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On; 

new class extends Component {
    use WithAuthUser;

    public int $studentStatus;
    public array $steps = [];
    public int $currentStatus;

    public function mount()
    {
        $this->studentStatus = $this->user->status;
        $this->currentStatus = $this->studentStatus;
    }

    #[On('student-status-updated')]
    public function updateStudentStatus($status)
    {
        $this->studentStatus = $status;
        \Log::info('🔔 Event diterima di komponen dashboard', [
            'ssstatus' => $this->studentStatus,
        ]);

        $this->dispatch('$refresh');
    }

    #[On('notify')]
    public function showSweetAlert($type, $message)
    {
        $this->js("
            Swal.fire({
                toast: true,
                icon: '{$type}',
                title: '{$message}',
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false
            });
        ");
    }

    public function goToStep($index)
    {
        if($index > $this->studentStatus){
            $this->dispatch('notify', type: 'error', message: 'Anda belum sampai tahap tersebut!');
            return;
        }
        $this->currentStatus = $index;
    }
}; ?>

    

    <div class="bg-white bg-opacity-90 backdrop-blur-sm p-4 md:p-8 rounded-xl shadow-lg text-black">
        @php
            // Variabel PHP untuk logic:
            $steps = ['Judul', 'Pilih Dosbing', 'Upload Proposal', 'Sidang Proposal', 'Final Proposal', 'Upload Skripsi', 'Sidang Skripsi', 'Final Skripsi'];
        @endphp

        <div id="desktop-content-container" class="hidden md:block">
            <div class="mb-8 sm:mb-10 overflow-x-auto">
                <h3 class="font-medium text-gray-600 mb-4 text-sm sm:text-base">Your Progress</h3>
                <div class="flex items-start min-w-max">

                    @foreach($steps as $index => $step)
                    <button wire:click="goToStep({{ $index }})" class="focus:outline-none cursor-pointer">
                        <div class="flex flex-col items-center w-16 flex-shrink-0" wire:key="step-{{ $index }}-{{ $studentStatus }}">
                                @if($index < $currentStatus)
                                    <div class="w-7 h-7 bg-green-500 border-2 border-green-500 rounded-full flex items-center justify-center z-10">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs sm:text-sm font-medium text-gray-700 text-center">{{ $step }}</p>

                                @elseif($index == $currentStatus)
                                    <div class="w-7 h-7 bg-black border-2 border-black rounded-full z-10"></div>
                                    <p class="mt-2 text-xs sm:text-sm font-medium text-gray-700 text-center">{{ $step }}</p>
                                
                                @elseif($index > $currentStatus && $index < $studentStatus)
                                    <div class="w-7 h-7 bg-green-500 border-2 border-green-500 rounded-full flex items-center justify-center z-10">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs sm:text-sm font-medium text-gray-700 text-center">{{ $step }}</p>

                                @elseif($index == $studentStatus)
                                    <div class="w-7 h-7 bg-yellow-500 border-2 border-black rounded-full z-10"></div>
                                    <p class="mt-2 text-xs sm:text-sm font-medium text-gray-700 text-center">{{ $step }}</p>

                                @else
                                    <div class="w-7 h-7 bg-white border-2 border-gray-300 rounded-full z-10"></div>
                                    <p class="mt-2 text-xs sm:text-sm text-gray-500 text-center">{{ $step }}</p>
                                @endif
                            </div>
                        </button>

                        @if(!$loop->last)
                            <div class="flex-auto border-t-2 {{ $index < $studentStatus ? 'border-green-500' : 'border-gray-300' }} mx-2 mt-3">
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            @if($currentStatus == 0)
                @livewire('student.submit-title')
            @elseif($currentStatus == 1)
                @livewire('student.submit-dosbing')
            @elseif($currentStatus == 2)
                @livewire('student.upload-proposal')
            @elseif($currentStatus == 4)
                @livewire('student.final-proposal')
            @endif
        </div> {{-- End Desktop Container --}}


        <div class="mb-8 sm:mb-10 md:hidden" 
             id="mobile-accordion-container">
            <h3 class="font-medium text-gray-600 mb-4 text-sm sm:text-base">Your Progress</h3>
            <div id="progress-accordion">

                @foreach($steps as $index => $step)
                    <div class="border-b border-gray-200 last:border-b-0">
                        <button type="button"
                            class="w-full py-3 px-1 text-left font-medium text-sm focus:outline-none flex items-center justify-between
                            {{ $index < $studentStatus ? 'text-green-600 bg-green-50 hover:bg-green-100' : ($index == $studentStatus ? 'text-black bg-gray-100 hover:bg-gray-200' : 'text-gray-500 hover:bg-gray-50') }}"
                            data-accordion-target="#accordion-body-{{ $index }}"
                            aria-expanded="{{ $index == $currentStatus ? 'true' : 'false' }}"
                            aria-controls="accordion-body-{{ $index }}">
                            
                            <div class="flex items-center">
                                @if($index < $studentStatus)
                                    <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                @elseif($index == $studentStatus)
                                    <div class="w-4 h-4 mr-3 border-2 border-black rounded-full flex items-center justify-center flex-shrink-0">
                                        <div class="w-2 h-2 bg-black rounded-full"></div> 
                                    </div>
                                @else
                                    <div class="w-4 h-4 mr-3 border-2 border-gray-300 rounded-full flex items-center justify-center flex-shrink-0"></div>
                                @endif
                                <span>{{ $step }}</span>
                            </div>

                            <svg class="w-4 h-4 transform transition-transform duration-300 accordion-icon {{ $index == $studentStatus ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        {{-- Body Accordion (Konten & Form Submit) --}}
                        <div id="accordion-body-{{ $index }}" class="overflow-hidden transition-all duration-300 accordion-content" 
                            style="{{ $index == $studentStatus ? 'max-height: 900px; padding: 10px;' : 'max-height: 0; padding: 0 10px;' }}">
                            
                            <div class="pb-3 pt-1 text-gray-600 text-sm">
                                @if($index <= $studentStatus)
                                    @if($currentStatus == 0)
                                        @livewire('student.submit-title')
                                    @elseif($currentStatus == 1)
                                        @livewire('student.submit-dosbing')
                                    @elseif($currentStatus == 2)
                                        @livewire('student.upload-proposal')
                                    @elseif($currentStatus == 4)
                                        @livewire('student.final-proposal')
                                    @endif
                                @else
                                    <p>Anda belum sampai tahap ini!</p>
                                @endif
                                
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const accordionButtons = document.querySelectorAll('[data-accordion-target]');
        
        accordionButtons.forEach(button => {
            button.addEventListener('click', handleAccordionClick);
        });
        
        function handleAccordionClick() {
            const button = this; 
            const targetId = button.getAttribute('data-accordion-target');
            const targetElement = document.querySelector(targetId);
            const isExpanded = button.getAttribute('aria-expanded') === 'true' || false;
            const icon = button.querySelector('.accordion-icon');

            document.querySelectorAll('.accordion-content').forEach(content => {
                if (content !== targetElement) {
                    content.style.maxHeight = '0';
                    content.style.padding = '0 10px';
                    document.querySelector(`[data-accordion-target="#${content.id}"]`).setAttribute('aria-expanded', 'false');
                    document.querySelector(`[data-accordion-target="#${content.id}"] .accordion-icon`).classList.remove('rotate-180');
                }
            });

            if (isExpanded) {
                targetElement.style.maxHeight = '0';
                targetElement.style.padding = '0 10px';
                button.setAttribute('aria-expanded', 'false');
                icon.classList.remove('rotate-180');
            } else {
                targetElement.style.maxHeight = targetElement.scrollHeight + 1000 + 'px'; 
                targetElement.style.padding = '10px';
                button.setAttribute('aria-expanded', 'true');
                icon.classList.add('rotate-180');
            }
        }
    });
</script>