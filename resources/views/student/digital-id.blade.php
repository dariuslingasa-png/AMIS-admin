<x-student-layout title="Digital ID">
    <div class="space-y-6">
        <section class="portal-card overflow-hidden">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-6">
                <div>
                    <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs uppercase tracking-wider">
                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                        <span>Official Student Credential</span>
                    </div>
                    <h2 class="mt-1 font-heading text-2xl font-black text-slate-900">My Digital ID</h2>
                    <p class="text-xs font-medium text-slate-500">Verified securely from the AMIS student database.</p>
                </div>
                <span class="portal-badge portal-badge-emerald">ID # {{ $student->student_number }}</span>
            </header>
            <div class="relative min-h-[720px] bg-slate-50">
                <iframe src="{{ $idUrl }}" title="AMIS Digital Student ID" loading="eager" class="absolute inset-0 h-full w-full border-0" referrerpolicy="same-origin"></iframe>
            </div>
        </section>
    </div>
</x-student-layout>
