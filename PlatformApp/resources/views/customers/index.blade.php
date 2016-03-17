<!DOCTYPE html>
<html>
<head>
    <title>Customers Summary</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

    <style type="text/css">
        #customers-summary-table {
            min-width: 400px;
        }
        #customers-summary-table td {
            padding: 3px;
        }
        #customers-summary-table .example-row .content-placeholder {
            width: 125px;
            height: 20px;
            background-color: #CCC;
        }
    </style>

    <script type="text/javascript">
        $(function() {
            //We delay 1.5secs just for demo purposes
            setTimeout(function() {
                $.ajax({
                    url: '{{ app('config')->get('services.our-apis.reportingapp.domain') }}/customers',
                    success: function (returnData) {
                        var tableCode = '';
                        var numCustomers = returnData.length;
                        for (var i = 0; i < numCustomers; i++) {
                            var customer = returnData[i];
                            tableCode += '<tr>';
                            tableCode += '<td>' + customer.id + '</td>';
                            tableCode += '<td>' + customer.name + '</td>';
                            tableCode += '<td>' + customer.state + '</td>';
                            tableCode += '<td>' + customer.zipCode + '</td>';
                            tableCode += '<td>' + customer.numProducts.toLocaleString() + '</td>'
                            tableCode += '</tr>';
                        }
                        $('#customers-summary-table tbody').html(tableCode);
                    }
                });
            }, 1500);
        });
    </script>
</head>
<body>
<div class="container">
    <div class="content">
        <div class="title">Customers Summary</div>

        <table id="customers-summary-table">
            <thead>
                <th>ID</th>
                <th>Name</th>
                <th>State</th>
                <th>Zip Code</th>
                <th># of Products</th>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr class="example-row">
                    <td>{{ $customer->id }}</td>
                    <td>{{ $customer->name }}</td>
                    <td><i class="fa fa-spinner fa-spin"></i></td>
                    <td><div class="content-placeholder"></div></td>
                    <td><div class="content-placeholder"></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
