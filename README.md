# نظام إدارة مخيم لاجئين — الملفات الأساسية

## خطوات التركيب في مشروعك

1. انسخ مجلدات `app/Models`, `app/Http/Controllers`, `app/Http/Middleware` إلى مشروعك (دمج، وليس استبدال إن وُجد تعارض).
2. انسخ ملفات `database/migrations` و `database/seeders/DatabaseSeeder.php`.
3. سجّل الـ middleware في `bootstrap/app.php` (Laravel 11+):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'is-admin' => \App\Http\Middleware\IsAdmin::class,
    ]);
})
```

أو في Laravel 10 داخل `app/Http/Kernel.php` ضمن `$middlewareAliases`.

4. ادمج محتوى `routes_web_snippet.php` داخل `routes/web.php` الخاص بك.
5. اربط مجلد التخزين العام (**ضروري لعرض صور الهوية والمرفقات**):

```bash
php artisan storage:link
```

6. شغّل:

```bash
php artisan migrate
php artisan db:seed
```

> **ملاحظة مهمة:** migration تعديل عمود `email` إلى nullable يتطلب حزمة
> `doctrine/dbal` (فقط إذا كنت تستخدم Laravel < 11 مع migration من نوع change()):
> `composer require doctrine/dbal --dev`
> إن لم ترد هذا التعديل، احذف السطر `$table->string('email')->nullable()->change();`
> من migration رقم 1 واترك بريد وهمي إلزامي كما في الـ Controller (username@camp.local).

## حسابات تجريبية بعد التشغيل (Seeder)

| الدور | Username | Password |
|---|---|---|
| أدمن | admin | admin1234 |
| مستخدم (بيانات معتمدة) | user1 | user12345 |
| مستخدم (بيانات بانتظار الموافقة) | user2 | user12345 |

## آلية العمل

- عند إدخال المستخدم لبياناته أول مرة → تُحفظ بحالة `pending` في جدول `families`.
- الأدمن يستعرض `/admin/families` ويقبل أو يرفض (مع سبب الرفض).
- إن أراد مستخدم لديه بيانات **معتمدة مسبقاً** تعديلها → لا يُعدَّل الجدول الأساسي مباشرة،
  بل يُنشأ سجل في `family_edit_requests` (JSON) بانتظار موافقة الأدمن، وعند القبول
  يتم دمج البيانات الجديدة في `families/children/special_cases` ضمن transaction واحدة.
- الأدمن يستطيع من `/admin/users/create` إنشاء مستخدم جديد، والنظام يولّد
  username وpassword تلقائياً ويعرضهما في صفحة واحدة (`admin.users.created`)
  لنسخهما وإرسالهما للمستخدم.
- **صورة الهوية**: حقل اختياري برأس الفورم، تُخزَّن في `storage/app/public/id-photos`.
- **مرفقات الحالات الخاصة**: كل حالة خاصة (إعاقة/مرض مزمن...) فيها حقل رفع اختياري
  (صورة أو PDF حتى 5 ميجابايت) — تُخزَّن في `storage/app/public/special-case-documents`.
  عند إنشاء طلب تعديل لأسرة معتمدة، يُخزَّن الملف الجديد فوراً على القرص ويُحفظ مساره
  داخل الـ JSON، وعند اعتماد الأدمن للطلب يُدمج المسار كما هو (لا حاجة لإعادة رفع).
  **ملاحظة:** لو الأدمن رفض طلب تعديل يحوي ملفاً مرفوعاً حديثاً، الملف يبقى على القرص
  (لا يُحذف تلقائياً) — تنظيف دوري بسيط ممكن إضافته لاحقاً لو أردت.

## الواجهات (Views)

أُضيفت الآن ضمن `resources/views`:
- `layouts/app.blade.php` — القالب العام (Sidebar متجاوب، RTL، Tailwind + Cairo)
- `auth/login.blade.php` — تسجيل الدخول بـ username/password
- `family/create.blade.php`, `family/edit.blade.php`, `family/_form.blade.php` — فورم الإدخال مع حقول متكررة (أطفال/حالات خاصة) + رفع صورة هوية ومرفقات، بـ JavaScript عادي (بدون أي مكتبة خارجية)
- `family/show.blade.php` — لوحة المستخدم لعرض حالة بياناته وروابط الملفات المرفوعة
- `admin/users/*` — إنشاء مستخدم + عرض بيانات الدخول المولَّدة
- `admin/families/*` — مراجعة/اعتماد/رفض طلبات الأسر مع عرض صورة الهوية والمرفقات
- `admin/edit-requests/*` — مراجعة طلبات التعديل بمقارنة "الحالي مقابل المقترح"، وعرض أي ملف جديد مرفوع بالطلب
- `components/status-badge.blade.php` — بادج الحالة (pending/approved/rejected)

**تنبيه مهم:** الواجهات تستخدم Tailwind عبر CDN (`cdn.tailwindcss.com`) فقط —
لا يوجد أي اعتماد على Alpine.js أو أي مكتبة JS خارجية بعد الآن (كل التفاعل
بالأطفال/الحالات الخاصة/الأزرار مكتوب بـ JavaScript عادي داخل الملفات نفسها)،
عشان ما ينكسر الموقع لو انحجب أي CDN خارجي بجهاز المستخدم. Tailwind عبر CDN
نفسه غير مناسب للإنتاج (أداء أبطأ، لا purge للـ CSS) — قبل النشر النهائي ثبّته
عبر Vite حسب توثيق Laravel الرسمي، وانقل نفس الكلاسات كما هي — التصميم لن يتغير.

كذلك اعتمدت Laravel's `auth` middleware الافتراضي — تأكد إن عندك جلسات (sessions)
مفعّلة بشكل طبيعي (`SESSION_DRIVER` في `.env`)، ولا حاجة لأي حزمة auth إضافية
(لم أستخدم Breeze/Jetstream لأن تسجيل الدخول بـ username لا email).

## لا يزال ينقصك (اختياري لاحقاً)

- Form Requests منفصلة بدل inline validation (لتنظيف الكود أكثر لو حبيت).
- Policies بدل middleware بسيط لو احتجت صلاحيات أدق مستقبلاً.
- صفحة 403/404 مخصصة بنفس هوية التصميم.
- Toast/Alert بدل session flash البسيط الحالي لو حبيت تفاعلية أكثر.


## المؤسسات والاستفادات

- **المؤسسات والجمعيات** (`/admin/organizations`): الأدمن يضيف مؤسسة بشعارها وهاتفها وعنوانها ووصفها.
  لا يمكن حذف مؤسسة لها سجل استفادات (للحفاظ على السجل).
- **سجل الاستفادات** (`/admin/benefits`): تسجيل توزيع واحد = مؤسسة + نوع الاستفادة (مثل كوبون صحي وغذائي)
  + تاريخ + اختيار **عدة مستفيدين دفعة وحدة** (بحث فوري + تحديد الكل). المستفيدون من الأسر المعتمدة فقط.
- الاستفادات المسجَّلة تظهر في لوحة الأسرة نفسها وفي صفحة مراجعتها عند الأدمن.
- بعد النسخ شغّل: `php artisan migrate` (3 migrations جديدة: 000007 إلى 000009)،
  وادمج الـ routes الجديدة من `routes_web_snippet.php`.
# alnoman
