<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AMIS Admin' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/AMIS_Logo.svg') }}">
    <style>
        *,::before,::after{box-sizing:border-box;border:0 solid #e5e7eb}
        html{-webkit-text-size-adjust:100%;tab-size:4;font-family:Inter,Arial,sans-serif}
        body{margin:0;line-height:1.5;color:#1f2937;background:#f8fafc;-webkit-font-smoothing:antialiased}
        h1,h2,h3,h4{font-weight:700;color:#111827}
        a{color:#059669;text-decoration:none}
        table{width:100%;border-collapse:collapse;text-indent:0}
        th{text-align:left;font-weight:700;text-transform:uppercase;font-size:.75rem;letter-spacing:.05em;color:#6b7280;padding:.75rem 1rem;background:#f9fafb;border-bottom:1px solid #e5e7eb}
        td{padding:.75rem 1rem;border-bottom:1px solid #f3f4f6;font-size:.875rem}
        button,input,select,textarea{font-family:inherit;font-size:inherit;line-height:inherit}
        img,svg,video{display:block;max-width:100%;height:auto}
        .admin-shell{display:flex;min-height:100vh}
        .admin-sidebar{width:16rem;position:fixed;top:4rem;height:calc(100vh - 4rem);background:#fff;border-right:1px solid #e5e7eb;z-index:40}
        .admin-content{margin-left:16rem;padding-top:4rem;min-height:calc(100vh - 4rem)}
        .admin-content main{padding:1.5rem;max-width:1280px;margin:0 auto}
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
    {{ $slot }}
</body>
</html>
