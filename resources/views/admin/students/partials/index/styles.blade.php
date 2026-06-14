<!-- Print Styles -->
<style>
    @media print {
        #default-sidebar, 
        .admin-sidebar, 
        .admin-topbar, 
        topbar, 
        aside, 
        form, 
        nav, 
        .breadcrumbs, 
        .focus-messages,
        .flash-messages, 
        footer, 
        .print\:hidden,
        .module-dashboard-link,
        .sidebar-section-container,
        .sidebar-profile-card,
        .admin-shell > a,
        .mb-5.grid,
        .mb-5,
        [data-lucide="arrow-left"] {
            display: none !important;
        }

        .admin-content, 
        .admin-shell, 
        body, 
        main, 
        .mx-auto,
        section,
        .bg-white {
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            min-width: auto !important;
            width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            font-family: Arial, sans-serif !important;
        }

        .admin-content {
            margin-left: 0 !important;
        }

        .print\:block {
            display: block !important;
        }

        table {
            border-collapse: collapse !important;
            width: 100% !important;
            font-family: Arial, sans-serif !important;
            border: none !important;
            margin-bottom: 2rem !important;
        }

        table th {
            position: static !important;
            background: #f8fafc !important;
            border-bottom: 1.5px solid #cbd5e1 !important;
            color: #1e293b !important;
            font-family: Arial, sans-serif !important;
            font-weight: bold !important;
            font-size: 10px !important;
            padding: 8px 10px !important;
            text-transform: uppercase !important;
            text-align: left !important;
        }

        table td {
            border: none !important;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 8px 10px !important;
            font-family: Arial, sans-serif !important;
            font-size: 9px !important;
            color: #334155 !important;
            background: transparent !important;
        }

        table td svg,
        table td i,
        table td [data-lucide],
        table td .inline-flex,
        table td span {
            display: inline !important;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
            color: #334155 !important;
            font-size: 9px !important;
            font-weight: normal !important;
        }

        table th svg,
        table th i,
        table th [data-lucide] {
            display: none !important;
        }

        .print\:hidden,
        table td .print\:hidden,
        table td span.print\:hidden,
        table td div.print\:hidden,
        table td x-smart-image.print\:hidden {
            display: none !important;
        }

        table td img,
        table td .flex-shrink-0,
        table td x-smart-image {
            display: none !important;
        }

        tr {
            page-break-inside: avoid !important;
        }

        .page-break-after {
            page-break-after: always !important;
            break-after: page !important;
        }
    }
</style>
