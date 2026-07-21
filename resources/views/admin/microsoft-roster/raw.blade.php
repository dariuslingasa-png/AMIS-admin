<x-admin-layout title="Microsoft Graph Raw Data">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><a href="{{ route('admin.microsoft-roster.show',$team) }}" class="mb-2 inline-block text-xs font-bold text-blue-700">← {{ $team->display_name }}</a><h1 class="text-2xl font-extrabold text-slate-950">Microsoft Graph Raw Data</h1><p class="mt-1 text-sm text-slate-500">Sanitized Team response, member responses, and local normalized result.</p></div><div class="flex gap-2"><button type="button" onclick="copyRosterJson()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold">Copy JSON</button><a href="{{ route('admin.microsoft-roster.raw.download',$team) }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white">Download JSON</a></div></div>
    <div class="mb-3"><input id="json-search" oninput="searchRosterJson(this.value)" placeholder="Search within JSON" class="w-full rounded-xl border-slate-200 text-sm"></div>
    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950"><pre id="json-viewer" class="max-h-[70vh] overflow-auto whitespace-pre-wrap p-5 text-xs leading-6 text-emerald-300">{{ json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre></div>
    <p id="json-search-result" class="mt-2 text-xs font-semibold text-slate-500"></p>
    <script>
        const rosterJson = @json(json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        async function copyRosterJson() { await navigator.clipboard.writeText(rosterJson); }
        function searchRosterJson(term) { const count = term ? rosterJson.toLowerCase().split(term.toLowerCase()).length - 1 : 0; document.getElementById('json-search-result').textContent = term ? `${count} match${count === 1 ? '' : 'es'} found` : ''; }
    </script>
</x-admin-layout>
