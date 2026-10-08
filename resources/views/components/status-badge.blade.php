@props(['status'])

@php
$map = [
    'pending'  => ['bg-pending/10 text-pending border-pending/30', 'بانتظار المراجعة'],
    'approved' => ['bg-approved/10 text-approved border-approved/30', 'معتمدة'],
    'rejected' => ['bg-rejected/10 text-rejected border-rejected/30', 'مرفوضة'],
];
[$classes, $label] = $map[$status] ?? ['bg-muted/10 text-muted border-muted/30', $status];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border $classes"]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    {{ $label }}
</span>
