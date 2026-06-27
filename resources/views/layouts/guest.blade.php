<x-app-layout :title="$title ?? 'AMIS Support Portal'">
    <main class="min-h-screen bg-gray-50 dark:bg-gray-900">
        {{ $slot }}
    </main>
    @include('partials.chatbot')
</x-app-layout>
