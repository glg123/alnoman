<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'نظام إدارة بيانات المخيم')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=El+Messiri:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { cairo: ['Cairo', 'sans-serif'], display: ['El Messiri', 'Cairo', 'sans-serif'] },
                    colors: {
                        primary: { DEFAULT: '#1F5C4F', dark: '#164439', light: '#2C7A69' },
                        canvas: '#F6F5F1',
                        ink: '#1C1C1A',
                        muted: '#6B6A63',
                        line: '#E4E1D8',
                        pending: '#C97A2B',
                        approved: '#2F6B4F',
                        rejected: '#B3432D',
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
        h1, h2, .font-display { font-family: 'El Messiri', 'Cairo', sans-serif; }
        #sidebar { transform: translateX(100%); }
        #sidebar.open { transform: translateX(0); }
        #sidebar-overlay { display: none; }
        #sidebar-overlay.open { display: block; }
        @media (min-width: 1024px) {
            #sidebar { transform: translateX(0) !important; }
        }
    </style>
</head>
<body class="bg-canvas text-ink antialiased">
<div class="min-h-screen lg:flex">

    {{-- شريط علوي للموبايل --}}
    <header class="lg:hidden flex items-center justify-between bg-primary text-white px-4 h-14 sticky top-0 z-30">
        <button type="button" onclick="document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebar-overlay').classList.add('open');"
                class="p-2 -mr-2" aria-label="فتح القائمة">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="font-bold">مخيم — إدارة البيانات</span>
        <span class="w-6"></span>
    </header>

    {{-- خلفية معتمة عند فتح القائمة بالموبايل --}}
    <div id="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open'); this.classList.remove('open');"
         class="fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

    {{-- الشريط الجانبي --}}
    <aside id="sidebar"
        class="fixed lg:sticky top-0 right-0 z-50 h-screen w-72 bg-primary-dark text-white flex flex-col transition-transform duration-200 ease-out">
        <div class="flex items-center justify-between px-5 h-16 border-b border-white/10">
            <div>
                <p class="font-display font-extrabold text-lg leading-none">مخيم</p>
                <p class="text-xs text-white/60 mt-1">نظام إدارة بيانات الأسر</p>
            </div>
            <button type="button" onclick="document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebar-overlay').classList.remove('open');"
                    class="lg:hidden p-1" aria-label="إغلاق القائمة">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 text-sm">
            @auth
                @if(auth()->user()->isAdmin())
                    <p class="px-3 pt-2 pb-1 text-xs text-white/40">إدارة</p>
                    <a href="{{ route('admin.families.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 {{ request()->routeIs('admin.families.*') ? 'bg-white/10 font-semibold' : '' }}">
                        <span>طلبات الأسر</span>
                    </a>
                    <a href="{{ route('admin.edit-requests.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 {{ request()->routeIs('admin.edit-requests.*') ? 'bg-white/10 font-semibold' : '' }}">
                        <span>طلبات التعديل</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 {{ request()->routeIs('admin.users.*') ? 'bg-white/10 font-semibold' : '' }}">
                        <span>المستخدمون</span>
                    </a>
                    <p class="px-3 pt-4 pb-1 text-xs text-white/40">الاستفادات</p>
                    <a href="{{ route('admin.organizations.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 {{ request()->routeIs('admin.organizations.*') ? 'bg-white/10 font-semibold' : '' }}">
                        <span>المؤسسات والجمعيات</span>
                    </a>
                    <a href="{{ route('admin.benefits.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 {{ request()->routeIs('admin.benefits.*') ? 'bg-white/10 font-semibold' : '' }}">
                        <span>سجل الاستفادات</span>
                    </a>
                @else
                    <p class="px-3 pt-2 pb-1 text-xs text-white/40">حسابي</p>
                    <a href="{{ route('family.show') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 {{ request()->routeIs('family.show') ? 'bg-white/10 font-semibold' : '' }}">
                        <span>بيانات أسرتي</span>
                    </a>
                @endif
            @endauth
        </nav>

        @auth
        <div class="border-t border-white/10 p-4">
            <p class="text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-white/50 truncate mb-3">{{ auth()->user()->username }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-right text-sm text-white/70 hover:text-white transition">تسجيل الخروج</button>
            </form>
        </div>
        @endauth
    </aside>

    {{-- المحتوى --}}
    <main class="flex-1 min-w-0">
        <div class="max-w-5xl mx-auto px-4 py-6 lg:px-8 lg:py-10">

            @if(session('success'))
                <div class="mb-6 rounded-lg border border-approved/30 bg-approved/10 text-approved px-4 py-3 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 rounded-lg border border-rejected/30 bg-rejected/10 text-rejected px-4 py-3 text-sm font-medium">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
