<x-academic.workspace-shell
    title="Academic Rooms & Venues"
    description="Configure lecture rooms, laboratories, event spaces, and room capacities used by the schedule conflict guard."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    <x-slot:actions><button type="button" onclick="window.dispatchEvent(new CustomEvent('open-room-modal'))" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white"><i data-lucide="plus" class="h-4 w-4"></i>Configure Room</button></x-slot:actions>

    <div x-data="roomDirectory()" @open-room-modal.window="openCreate()">
        <x-academic.workspace-filters placeholder="Search room name or type..." :status="true">
            <select name="type" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700"><option value="">All room types</option>@foreach($roomTypes as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach</select>
        </x-academic.workspace-filters>

        @if ($rooms->isEmpty())
            <div class="mt-6 rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center"><i data-lucide="school" class="mx-auto h-9 w-9 text-slate-300"></i><h2 class="mt-4 text-lg font-black text-slate-800">No Rooms Configured</h2><p class="mt-1 text-sm text-slate-500">Configure rooms to activate room collision checks in the builder.</p></div>
        @else
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($rooms as $room)
                    <article class="flex min-h-52 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200 hover:shadow-md">
                        <div>
                            <div class="flex items-start justify-between gap-3"><span class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600"><i data-lucide="door-open" class="h-5 w-5"></i></span><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $room->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $room->status }}</span></div>
                            <h2 class="mt-4 text-lg font-black text-slate-900">{{ $room->name }}</h2><p class="mt-1 text-xs font-bold text-slate-500">{{ $room->room_type ?: 'General classroom' }}</p>
                            <div class="mt-4 flex gap-2"><span class="rounded-lg bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-600">{{ $room->capacity ?: '—' }} seats</span><span class="rounded-lg bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700">{{ $room->schedules_count }} schedule blocks</span></div>
                        </div>
                        <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" @click="openEdit(@js($room->only(['id','name','room_type','capacity','status'])))" class="rounded-lg border border-slate-200 p-2 text-slate-500 hover:bg-indigo-50 hover:text-indigo-700"><i data-lucide="pencil" class="h-4 w-4"></i></button>@if($room->status === 'active')<form method="POST" action="{{ route('admin.academic.rooms.destroy', $room) }}" onsubmit="return confirm('Archive this room? Existing schedules will be preserved.')">@csrf @method('DELETE')<button class="rounded-lg border border-slate-200 p-2 text-amber-600 hover:bg-amber-50"><i data-lucide="archive" class="h-4 w-4"></i></button></form>@endif</div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $rooms->links() }}</div>
        @endif

        <div x-cloak x-show="modal" class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/55 p-4" @keydown.escape.window="modal=false" @click.self="modal=false">
            <form method="POST" :action="editing ? `/academic/rooms/${form.id}` : '{{ route('admin.academic.rooms.store') }}'" class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
                @csrf <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><p class="text-[10px] font-black uppercase tracking-[.14em] text-indigo-600">Room registry</p><h2 class="text-lg font-black" x-text="editing ? 'Edit Room Configuration' : 'Configure New Room'"></h2></div><button type="button" @click="modal=false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <label class="md:col-span-2"><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Room name</span><input name="name" x-model="form.name" required placeholder="e.g. Science Laboratory" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Room type</span><input name="room_type" x-model="form.room_type" placeholder="Lecture, Lab, Hall..." class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Capacity</span><input type="number" min="1" max="5000" name="capacity" x-model="form.capacity" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label class="md:col-span-2"><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Status</span><select name="status" x-model="form.status" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
                </div>
                <div class="flex justify-end gap-2 border-t bg-slate-50 px-6 py-4"><button type="button" @click="modal=false" class="rounded-xl border bg-white px-4 py-2 text-sm font-bold text-slate-600">Cancel</button><button class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white">Save Room</button></div>
            </form>
        </div>
    </div>
    <script>
        function roomDirectory(){ const blank=()=>({id:null,name:'',room_type:'',capacity:'',status:'active'}); return {modal:false,editing:false,form:blank(),openCreate(){this.form=blank();this.editing=false;this.modal=true},openEdit(room){this.form={...blank(),...room};this.editing=true;this.modal=true}} }
    </script>
</x-academic.workspace-shell>
