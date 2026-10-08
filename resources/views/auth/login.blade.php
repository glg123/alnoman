<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — نظام إدارة بيانات المخيم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=El+Messiri:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { cairo: ['Cairo', 'sans-serif'], display: ['El Messiri', 'Cairo', 'sans-serif'] },
                colors: { primary: { DEFAULT: '#1F5C4F', dark: '#164439' }, canvas: '#F6F5F1', ink: '#1C1C1A', muted: '#6B6A63', line: '#E4E1D8', rejected: '#B3432D' } } }
        };
    </script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .pattern-bg {
            background-color: #F6F5F1;
            background-image:
                radial-gradient(circle at 1px 1px, rgba(31, 92, 79, 0.10) 1.5px, transparent 0);
            background-size: 28px 28px;
        }
        .brand-mark {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #1F5C4F, #2C7A69);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
        }
    </style>
</head>
<body class="pattern-bg text-ink antialiased min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-sm">
        <div class="flex flex-col items-center text-center mb-8">
            <div class="brand-mark mb-4 shadow-lg shadow-primary/20">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M3 21L12 3L21 21H3Z" stroke="white" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M8 21V13H16V21" stroke="white" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
            </div>
            <p class="font-display text-3xl font-bold text-primary-dark">مخيم</p>
            <p class="text-sm text-muted mt-1">نظام إدارة بيانات الأسر</p>
        </div>

        <div class="bg-white border border-line rounded-2xl p-6 sm:p-8 shadow-xl shadow-ink/5">
            <h1 class="font-display text-xl font-bold mb-6">تسجيل الدخول</h1>

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-rejected/30 bg-rejected/10 text-rejected px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="username" class="block text-sm font-semibold mb-1.5">اسم المستخدم</label>
                    <input id="username" name="username" type="text" required autofocus value="{{ old('username') }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold mb-1.5">كلمة المرور</label>
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <button type="submit"
                        class="w-full bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg py-2.5 text-sm shadow-md shadow-primary/20">
                    دخول
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-muted mt-6">لا تملك بيانات دخول؟ راجع إدارة المخيم.</p>
    </div>

</body>
</html>
