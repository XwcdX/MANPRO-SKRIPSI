<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 lg:overflow-hidden">
    <flux:sidebar sticky stashable
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 min-h-screen">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('lecturer.dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse"
            wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('lecturer.dashboard')"
                    :current="request()->routeIs('lecturer.dashboard')" wire:navigate>{{ __('Dashboard') }}
                </flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Management')" class="grid">
                <flux:navlist.item icon="users" :href="route('lecturer.students.index')"
                    :current="request()->routeIs('lecturer.students.*')" wire:navigate>{{ __('View Students') }}
                </flux:navlist.item>
                <flux:navlist.item icon="academic-cap" :href="route('lecturer.supervisions.index')"
                    :current="request()->routeIs('lecturer.supervisions.*')" wire:navigate>{{ __('Supervisions') }}
                </flux:navlist.item>
                <flux:navlist.item icon="document-check" :href="route('lecturer.thesis.titles')"
                    :current="request()->routeIs('lecturer.thesis.*')" wire:navigate>{{ __('Thesis Titles') }}
                </flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Scheduling')" class="grid">
                <flux:navlist.item icon="calendar-days" :href="route('lecturer.schedules.availability')"
                    :current="request()->routeIs('lecturer.schedules.*')" wire:navigate>{{ __('Schedules') }}
                </flux:navlist.item>
            </flux:navlist.group>

            @can('manage-roles')
                <flux:navlist.group :heading="__('Administration')" class="grid">
                    <flux:navlist.item icon="shield-check" :href="route('lecturer.roles.index')"
                        :current="request()->routeIs('lecturer.roles.*')" wire:navigate>{{ __('Roles & Permissions') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="user-circle" :href="route('lecturer.assignments.index')"
                        :current="request()->routeIs('lecturer.assignments.*')" wire:navigate>{{ __('Assign Roles') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="user-group" :href="route('lecturer.lecturers.index')"
                        :current="request()->routeIs('lecturer.lecturers.*')" wire:navigate>{{ __('Manage Lecturers') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @endcan

        </flux:navlist>

        <flux:spacer />

        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevron-up-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>
                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('lecturer.profile')" icon="cog" wire:navigate>
                        {{ __('Profile') }}
                    </flux:menu.item>
                    <flux:menu.item :href="route('lecturer.settings')" icon="adjustments-horizontal" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu.radio.group>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <main class="lg:ps-72 lg:h-screen">
        <flux:header class="lg:hidden border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('lecturer.profile')" icon="cog" wire:navigate>
                            {{ __('Profile') }}
                        </flux:menu.item>
                        <flux:menu.item :href="route('lecturer.settings')" icon="adjustments-horizontal" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.radio.group>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Content Container --}}
        <div class="p-4 sm:p-6 lg:p-8 lg:translate-y-[-100vh] lg:h-screen lg:overflow-y-auto">
            {{ $slot }}
        </div>
    </main>

    @fluxScripts
</body>

</html>
