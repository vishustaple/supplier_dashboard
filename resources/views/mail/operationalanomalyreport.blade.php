<!DOCTYPE html>
    <html lang="en">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Operational Anomaly Report</title>
    </head>
    <body>
        <div class="card bg-light mb-3" style="width: 18rem;">
        <div class="card-body">{!! $supplierDate !!}</div>
        </div>
        <table class="table">
            <thead>
                <tr>
                <th scope="col">Account Name</th>
                <th scope="col">Supplier Name</th>
                <th scope="col">52wk AVG</th>
                <th scope="col">10wk AVG</th>
                <th scope="col">2wk AVG</th>
                <th scope="col">Percentage Drop</th>
                <th scope="col">52wk Median</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($data)) 
                    @foreach($data as $key => $value)
                        <tr>
                            <td>{{ $value->account_name }}</td>
                            <td>{{ $value->supplier_name }}</td>
                            <td>${{ $value->fifty_two_wk_avg }}</td>
                            <td>${{ $value->ten_week_avg }}</td>
                            <td>${{ $value->two_wk_avg_percentage }}</td>
                            <td>{{ $value->drop }}%</td>
                            <td>${{ $value->median }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </body>
</html>