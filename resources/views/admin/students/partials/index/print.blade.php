@if ($isPrint)
    @if (request()->filled('print_credentials'))
        @include('admin.students.partials.index.print_credentials')
    @else
        @include('admin.students.partials.index.print_list')
    @endif
@endif
