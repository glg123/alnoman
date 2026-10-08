<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Import\PeopleImporter;
use App\Services\Import\SimpleXlsxWriter;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PeopleImportController extends Controller
{
    /** عدد الصفوف التي تُنفَّذ بكل طلب (التشفير بطيء نسبياً فنقسّم العمل). */
    private const CHUNK = 20;

    /** بعد كم يوم تُحذف مجلدات الاستيراد القديمة (فيها كلمات مرور). */
    private const KEEP_DAYS = 3;

    private const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    // --------------------------------------------------------------- الرفع

    public function create()
    {
        $this->cleanup();

        return view('admin.import.create');
    }

    public function template()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        SimpleXlsxWriter::write($tmp, [
            'الأسر' => [
                ['الاسم الأول', 'اسم الأب', 'اسم الجد', 'العائلة', 'رقم الهوية', 'اسم الزوجة', 'رقم هوية الزوجة', 'عدد الأفراد'],
                ['مثال: أحمد', 'محمود', 'خليل', 'النمر', '900000000', 'سلمى يوسف', '900000001', '5'],
            ],
        ]);

        return response()->download($tmp, 'import-template.xlsx', ['Content-Type' => self::XLSX])->deleteFileAfterSend(true);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ], [
            'file.required' => 'اختر ملفاً أولاً.',
            'file.max' => 'حجم الملف أكبر من 5 ميغابايت.',
            'file.uploaded' => 'فشل رفع الملف. غالباً حجمه أكبر من المسموح بالسيرفر.',
        ]);

        $file = $request->file('file');

        try {
            $rows = SpreadsheetReader::read($file->getRealPath(), $file->getClientOriginalName());
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $hasHeader = ! $request->boolean('no_header');

        if (count($rows) < ($hasHeader ? 2 : 1)) {
            return back()->withErrors(['file' => 'الملف لا يحتوي بيانات.']);
        }

        $colCount = count($rows[0]['cells']);

        if ($hasHeader) {
            $headerRow = array_shift($rows);
            $headers = [];
            foreach ($headerRow['cells'] as $i => $h) {
                $headers[] = $h !== '' ? $h : 'عمود ' . ($i + 1);
            }
        } else {
            $headers = [];
            for ($i = 0; $i < $colCount; $i++) {
                $headers[] = 'عمود ' . ($i + 1);
            }
        }

        $token = Str::random(40);
        $this->put($token, 'data.json', [
            'filename' => $file->getClientOriginalName(),
            'headers' => $headers,
            'rows' => $rows,
        ]);
        $this->put($token, 'meta.json', [
            'admin_id' => Auth::id(),
            'created_at' => now()->toIso8601String(),
            'cursor' => 0,
            'total' => 0,
            'mapping' => null,
        ]);

        return redirect()->route('admin.import.mapping', $token);
    }

    // ---------------------------------------------------------- ربط الأعمدة

    public function mapping(string $token)
    {
        $meta = $this->meta($token);
        $data = $this->get($token, 'data.json');

        $mapping = $meta['mapping'] ?: PeopleImporter::guessMapping($data['headers']);

        return view('admin.import.mapping', [
            'token' => $token,
            'filename' => $data['filename'],
            'headers' => $data['headers'],
            'sample' => array_map(fn ($r) => $r['cells'], array_slice($data['rows'], 0, 6)),
            'rowsCount' => count($data['rows']),
            'fields' => PeopleImporter::FIELDS,
            'mapping' => $mapping,
        ]);
    }

    public function saveMapping(Request $request, string $token)
    {
        $meta = $this->meta($token);
        $data = $this->get($token, 'data.json');
        $colCount = count($data['headers']);

        $mapping = [];
        foreach (PeopleImporter::FIELDS as $key => $def) {
            $idxs = [];
            foreach ((array) $request->input("map.$key", []) as $v) {
                if ($v === null || $v === '' || ! ctype_digit((string) $v) || (int) $v >= $colCount) {
                    continue;
                }
                $idxs[] = (int) $v;
            }
            $mapping[$key] = $def['multi'] ? $idxs : array_slice($idxs, 0, 1);
        }

        if (! $mapping['full_name']) {
            return back()->withInput()->withErrors(['map' => 'لازم تحدد عمود (أو أعمدة) الاسم.']);
        }

        $built = PeopleImporter::preview($data['rows'], $mapping);

        $this->put($token, 'records.json', $built);
        $this->forget($token, 'results.json');

        $meta['mapping'] = $mapping;
        $meta['cursor'] = 0;
        $meta['total'] = count($built['records']);
        $this->put($token, 'meta.json', $meta);

        return redirect()->route('admin.import.preview', $token);
    }

    // --------------------------------------------------------------- معاينة

    public function preview(string $token)
    {
        $meta = $this->meta($token);
        $built = $this->get($token, 'records.json', null);

        if (! $built) {
            return redirect()->route('admin.import.mapping', $token);
        }

        $records = $built['records'];
        $families = count(array_filter($records, fn ($r) => $r['type'] === 'family'));

        return view('admin.import.preview', [
            'token' => $token,
            'records' => array_slice($records, 0, 40),
            'skipped' => array_slice($built['skipped'], 0, 200),
            'skippedCount' => count($built['skipped']),
            'total' => count($records),
            'families' => $families,
            'usersOnly' => count($records) - $families,
            'cursor' => $meta['cursor'],
        ]);
    }

    // ---------------------------------------------------------------- تنفيذ

    public function run(string $token)
    {
        $meta = $this->meta($token);
        $built = $this->get($token, 'records.json', null);

        if (! $built) {
            return response()->json(['error' => 'لا توجد بيانات للاستيراد.'], 422);
        }

        @set_time_limit(120);

        Storage::disk('local')->makeDirectory($this->dir($token));
        $lock = fopen(Storage::disk('local')->path($this->dir($token) . '/lock'), 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return response()->json(['error' => 'عملية استيراد أخرى جارية لنفس الملف.'], 409);
        }

        try {
            $meta = $this->meta($token);
            $records = $built['records'];
            $total = count($records);
            $results = $this->get($token, 'results.json', ['created' => [], 'failed' => []]);

            $from = (int) $meta['cursor'];
            $to = min($from + self::CHUNK, $total);

            for ($i = $from; $i < $to; $i++) {
                $rec = $records[$i];
                $res = PeopleImporter::createOne($rec, (int) Auth::id());

                if ($res['ok']) {
                    $results['created'][] = [
                        'row' => $rec['row'],
                        'name' => $rec['name'],
                        'national_id' => $rec['national_id'],
                        'type' => $rec['type'],
                        'username' => $res['username'],
                        'password' => $res['password'],
                        'notes' => $rec['notes'],
                    ];
                } else {
                    $results['failed'][] = [
                        'row' => $rec['row'],
                        'name' => $rec['name'],
                        'reason' => $res['reason'],
                    ];
                }
            }

            $meta['cursor'] = $to;
            $this->put($token, 'results.json', $results);
            $this->put($token, 'meta.json', $meta);

            return response()->json([
                'cursor' => $to,
                'total' => $total,
                'created' => count($results['created']),
                'failed' => count($results['failed']),
                'done' => $to >= $total,
            ]);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    // --------------------------------------------------------------- النتيجة

    public function result(string $token)
    {
        $meta = $this->meta($token);
        $built = $this->get($token, 'records.json', null);

        if (! $built || (int) $meta['cursor'] < (int) $meta['total'] || (int) $meta['total'] === 0) {
            return redirect()->route('admin.import.preview', $token);
        }

        $results = $this->get($token, 'results.json', ['created' => [], 'failed' => []]);

        return view('admin.import.result', [
            'token' => $token,
            'created' => $results['created'],
            'failed' => $results['failed'],
            'skipped' => $built['skipped'],
        ]);
    }

    public function download(string $token)
    {
        $this->meta($token);
        $built = $this->get($token, 'records.json', null);
        $results = $this->get($token, 'results.json', null);

        if (! $built || ! $results) {
            abort(404);
        }

        $login = [['الاسم', 'اسم المستخدم', 'كلمة المرور', 'رقم الهوية', 'النوع']];
        foreach ($results['created'] as $c) {
            $login[] = [
                $c['name'], $c['username'], $c['password'], $c['national_id'] ?? '',
                $c['type'] === 'family' ? 'حساب + أسرة' : 'حساب فقط',
            ];
        }

        $notImported = [['الصف بالملف', 'الاسم', 'السبب']];
        foreach ($results['failed'] as $f) {
            $notImported[] = [$f['row'], $f['name'], $f['reason']];
        }
        foreach ($built['skipped'] as $s) {
            $notImported[] = [$s['row'], $s['name'], $s['reason']];
        }

        $sheets = ['بيانات الدخول' => $login];
        if (count($notImported) > 1) {
            $sheets['لم يُستورد'] = $notImported;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'cred');
        SimpleXlsxWriter::write($tmp, $sheets);

        return response()->download($tmp, 'credentials-' . now()->format('Ymd-His') . '.xlsx', ['Content-Type' => self::XLSX])->deleteFileAfterSend(true);
    }

    public function destroy(string $token)
    {
        $this->meta($token);
        Storage::disk('local')->deleteDirectory($this->dir($token));

        return redirect()->route('admin.users.index')->with('success', 'تم حذف بيانات الاستيراد وكلمات المرور المؤقتة من السيرفر.');
    }

    // --------------------------------------------------------------- مساعدات

    private function dir(string $token): string
    {
        return 'imports/' . $token;
    }

    private function meta(string $token): array
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40}$/', $token), 404);

        $meta = $this->get($token, 'meta.json', null);
        abort_unless($meta && (int) $meta['admin_id'] === (int) Auth::id(), 404);

        return $meta;
    }

    private function get(string $token, string $file, $default = 'abort')
    {
        $path = $this->dir($token) . '/' . $file;
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            if ($default === 'abort') {
                abort(404);
            }

            return $default;
        }

        return json_decode($disk->get($path), true);
    }

    private function put(string $token, string $file, array $data): void
    {
        Storage::disk('local')->put(
            $this->dir($token) . '/' . $file,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function forget(string $token, string $file): void
    {
        Storage::disk('local')->delete($this->dir($token) . '/' . $file);
    }

    private function cleanup(): void
    {
        $disk = Storage::disk('local');
        $limit = now()->subDays(self::KEEP_DAYS)->getTimestamp();

        if (! $disk->exists('imports')) {
            return;
        }

        foreach ($disk->directories('imports') as $dir) {
            $meta = $dir . '/meta.json';
            if (! $disk->exists($meta) || $disk->lastModified($meta) < $limit) {
                $disk->deleteDirectory($dir);
            }
        }
    }
}
