@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::guard('student')->user() ?? Auth::guard('lecturer')->user();
@endphp

<div class="flex h-screen">
    <aside 
        id="sidebar" 
        class="fixed inset-y-0 left-0 w-64 bg-gray-800 bg-opacity-80 text-white flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50"
    >
        <div class="p-6 text-2xl font-semibold border-b border-gray-700">PETRA</div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="/" class="flex items-center px-4 py-2.5 hover:bg-gray-700 rounded-lg font-medium">Dashboard</a>
            <a href="/" class="flex items-center px-4 py-2.5 hover:bg-gray-700 rounded-lg">Topik Dosen</a>
            <a href="/" class="flex items-center px-4 py-2.5 hover:bg-gray-700 rounded-lg">Sidang Proposal</a>
            <a href="/" class="flex items-center px-4 py-2.5 hover:bg-gray-700 rounded-lg">Sidang Skripsi</a>
        </nav>
        <div class="px-4 py-6 border-t border-gray-700">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex items-center w-full px-4 py-2.5 text-gray-300 hover:bg-gray-700 rounded-lg text-left">
                    Log out
                </button>
            </form>
        </div>
    </aside>

    <div id="blurOverlay" class="fixed inset-0 bg-transparent backdrop-blur-sm hidden z-40 lg:hidden"></div>

    <main id="mainContent" class="flex-1 flex flex-col ml-0 lg:ml-64 transition-all duration-300 min-w-0">
        <header class="flex items-center justify-between p-5 bg-white bg-opacity-70 backdrop-blur-sm">
            <div class="flex items-center space-x-4">
                <button id="hamburger" class="text-gray-600 lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
                <h1 class="text-xl font-semibold text-gray-800">Pendaftaran & Penjadwalan Proposal Skripsi</h1>
            </div>
            <div class="text-md font-medium text-gray-700">Hello, {{ $user->name ?? 'Unknown User' }}</div>
        </header>

        <div class="flex-1 p-8 overflow-y-auto min-w-0">
            {{ $slot }}
        </div>
    </main>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburger');
    const blurOverlay = document.getElementById('blurOverlay');

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        blurOverlay.classList.remove('hidden');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        blurOverlay.classList.add('hidden');
    }

    hamburger.addEventListener('click', () => {
        if (sidebar.classList.contains('-translate-x-full')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    });

    blurOverlay.addEventListener('click', closeSidebar);
</script>