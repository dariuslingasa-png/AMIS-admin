@if ($isPrint)
    @if (request()->filled('print_credentials'))
        @include('admin.students.partials.index.print_credentials')
    @elseif (request()->filled('print_info'))
        @include('admin.students.partials.index.print_info')
    @elseif (request()->filled('print_id'))
        @include('admin.students.partials.index.print_id')
    @else
        @include('admin.students.partials.index.print_list')
    @endif
@endif
