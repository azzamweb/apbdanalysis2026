@extends('layouts.app')

@section('title','Jadwal Penganggaran')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('tahapan.create') }}" class="btn btn-primary">Create New Tahapan</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @foreach ($tahapans as $tahapan)
                                <tr>
                                    <td class="text-center">{{ $no++ }}</td>
                                    <td><a href="{{ route('tahapan.show', $tahapan->id) }}">{{ $tahapan->name }}</a></td>
                                    <td>{{ $tahapan->description }}</td>
                                    <td>{{ $tahapan->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('tahapan.edit', $tahapan->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete({{ $tahapan->id }})">Delete</button>
                                        <form id="delete-form-{{ $tahapan->id }}" action="{{ route('tahapan.destroy', $tahapan->id) }}" method="POST" style="display:none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url('tahapan') }}/' + id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: response.error,
                                });
                            } else {
                                Swal.fire(
                                    'Deleted!',
                                    'Tahapan has been deleted.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            }
                        }
                    });
                }
            })
        }
    </script>
@endsection