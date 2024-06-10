@extends('layout.app', ['pageTitleCheck' => $pageTitle])

@section('content')
<div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <div id="layoutSidenav_content">
        <div class="container">
        <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                <a href="{{ route('queries.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Saved Queries</a>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Query</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($queries as $query)
                        <tr>
                            <td>{{ $query->title }}</td>
                            <td>{{ $query->query }}</td>
                            <td><a href="{{ route('queries.edit', ['query' => $query->id]) }}" class="btn btn-primary">
                            <i class="fa fa-pencil-square" aria-hidden="true"></i> Edit</a> <a class="btn btn-danger" href="#" onclick="deleteQuery({{ $query->id }})"><i class="fa fa-trash" aria-hidden="true"></i> Delete</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    function deleteQuery(queryId) {
    console.log(queryId);
    if (confirm('Are you sure you want to delete this query?')) {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var url = 'queries/delete/' + queryId;

        $.ajax({
            url: url,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
            success: function(response) {
                // Handle success, e.g., remove the element from the DOM
                location.reload(); // or any other action you want to take after successful deletion
            },
            error: function(xhr, status, error) {
                // Handle errors
                console.error('Error:', error);
                alert('An error occurred while deleting the query.');
            }
        });
    }
}

</script>
@endsection