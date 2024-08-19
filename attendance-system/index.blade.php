@extends('layouts.app')
@section('title', $moduleName)

@section('content')

{{-- HTML CONTEN --}}
<div class="card">
    <div class="card-header text-center">
        <h3> Attendance of {{ $date }} </h3>
    </div>
    <div class="card-body text-center">

        <div class="form-group">
            @if($showCheckInBtn)
                <a href="{{ route('check-in') }}" class="btn btn-success"> CHECK IN </a>
            @endif
            @if($showCheckOutBtn)
                <a href="{{ route('check-out') }}" class="btn btn-danger"> CHECK OUT </a>
            @endif
        </div>

        <div class="form-group">
            @if($showCheckOutForBreakBtn)
                <a href="{{ route('break-out') }}" class="btn btn-success"> CHECK OUT FOR BREAK </a>
            @endif

            @if($showCheckInFromBreakBtn)
            <a href="{{ route('break-in') }}" class="btn btn-danger"> CHECK IN FROM BREAK </a>
            @endif
        </div>

        <div class="form-group">
            <a class="btn btn-primary"> TIME : {{ $time }} </a>
        </div>

    </div>
    <div class="card-footer">
        <div class="row">
            <div class="col-12">
                <table class="table table-bordered" id="attendance-info">
                    <thead>
                        <th width="10%"> SRNO </th>
                        <th width="70%"> TIME </th>
                        <th width="20%"> TYPE </th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
{{-- HTML CONTENT --}}


@endsection

@push('js')
<script>
    $(document).ready(function() {
        let dataTable = $('#attendance-info').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('attendance') }}",
                type: 'POST',
                data: {

                }                
            },
            columns: [
                {data: 'DT_RowIndex'},
                {data: 'time'},
                {data: 'type'}
            ],
            'columnDefs': [{
                'targets': [0],
                'orderable': false,
                'searchable': false
            }]
        })
    });
</script>
@endpush
