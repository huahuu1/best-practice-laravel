<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Manager - React App</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Environment Variables -->
    <script src="{{ asset('env.js') }}"></script>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <!-- Custom Styles -->
    <style>
        body {
            padding: 0;
            background-color: #f8f9fa;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .menu-section {
            margin-bottom: 30px;
        }
        .menu-item {
            border-left: 4px solid #007bff;
            background-color: white;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .menu-item:hover {
            transform: translateY(-3px);
        }
        .cart-container {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            padding: 15px;
        }
        .cart-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            font-size: 24px;
            cursor: pointer;
            z-index: 1000;
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
        }
        .category-nav {
            padding: 10px;
            background-color: white;
            position: sticky;
            top: 56px;
            z-index: 99;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-card {
            border-left: 5px solid #007bff;
            background-color: white;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-card.status-received {
            border-left-color: #dc3545;
        }
        .order-card.status-processing {
            border-left-color: #ffc107;
        }
        .order-card.status-ready {
            border-left-color: #28a745;
        }
        .order-card.status-completed {
            border-left-color: #6c757d;
            opacity: 0.7;
        }
        .orders-container {
            display: flex;
            flex-wrap: wrap;
        }
        .status-column {
            flex: 1;
            min-width: 300px;
            max-width: 100%;
            padding: 0 10px;
        }
        .status-header {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            color: white;
            text-align: center;
            font-weight: bold;
        }
        .received-header {
            background-color: #dc3545;
        }
        .processing-header {
            background-color: #ffc107;
        }
        .ready-header {
            background-color: #28a745;
        }
        .completed-header {
            background-color: #6c757d;
        }
        .qr-code-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
    </style>

    <!-- Kafka Configuration -->
    <script>
        window.kafkaConfig = {
            topics: {
                qr_scan_events: '{{ config('kafka.topics.qr_scan_events', 'qr-scan-events') }}',
                table_orders: '{{ config('kafka.topics.table_orders', 'table-orders') }}',
                order_status_updates: '{{ config('kafka.topics.order_status_updates', 'order-status-updates') }}',
            }
        };
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
    <div id="react-app"></div>
</body>
</html>
