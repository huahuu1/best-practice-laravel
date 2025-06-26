<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #6c757d;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .qr-code {
            text-align: center;
            padding: 10px;
        }
        .qr-code img {
            max-width: 100%;
        }
        .top-header {
            background-color: #343a40;
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-header text-center">
            <h1>Restaurant Table Management</h1>
            <p class="lead">Scan table QR codes for digital menu ordering</p>
        </div>

        <div class="row">
            @foreach ($tables as $table)
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ $table['name'] }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="qr-code">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($table['qr_code_url']) }}"
                                 alt="QR Code for {{ $table['name'] }}">
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ $table['qr_code_url'] }}" target="_blank" class="btn btn-primary">View Menu</a>
                            <button class="btn btn-secondary print-qr" data-table="{{ $table['id'] }}">Print QR</button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Kitchen Display</h5>
                    </div>
                    <div class="card-body">
                        <p>View and manage all incoming orders from tables:</p>
                        <a href="{{ route('kitchen.display') }}" class="btn btn-lg btn-primary">Open Kitchen Display</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Print QR code functionality
        document.querySelectorAll('.print-qr').forEach(button => {
            button.addEventListener('click', function() {
                const tableId = this.getAttribute('data-table');
                const qrImage = this.closest('.card').querySelector('.qr-code img').src;

                const printWindow = window.open('', '', 'width=600,height=600');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>QR Code for Table ${tableId}</title>
                            <style>
                                body { font-family: Arial, sans-serif; text-align: center; }
                                .qr-container { margin: 20px; }
                                h2 { margin-bottom: 20px; }
                            </style>
                        </head>
                        <body>
                            <div class="qr-container">
                                <h2>Table ${tableId} - Scan to Order</h2>
                                <img src="${qrImage}" style="width: 300px;">
                            </div>
                            <script>
                                window.onload = function() { window.print(); }
                            </script>
                        </body>
                    </html>
                `);
                printWindow.document.close();
            });
        });
    </script>
</body>
</html>
