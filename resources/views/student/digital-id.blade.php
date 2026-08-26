<x-student-layout title="Digital ID">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-emerald-600">Official student credential</p>
                <h2 class="mt-1 text-2xl font-black text-slate-900">My Digital ID</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Verified securely from the AMIS student database.</p>
            </div>
            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700">{{ $student->student_number }}</span>
        </header>
        <div class="relative min-h-[720px] bg-slate-50">
            <iframe src="{{ $idUrl }}" title="AMIS Digital Student ID" loading="eager" class="absolute inset-0 h-full w-full border-0" referrerpolicy="same-origin"></iframe>
        </div>
    </section>
</x-student-layout>
