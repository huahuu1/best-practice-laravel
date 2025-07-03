<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 0;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
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
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        .order-body {
            padding: 10px 15px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-footer {
            padding: 10px 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .timer {
            font-weight: bold;
        }
        .status-pill {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        .status-received {
            background-color: #ffebee;
            color: #c62828;
        }
        .status-processing {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        .status-ready {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .status-completed {
            background-color: #eceff1;
            color: #546e7a;
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
        .quantity-badge {
            background-color: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="m-0">Kitchen Display System</h2>
                <div>
                    <button id="refreshBtn" class="btn btn-light me-2">Refresh</button>
                    <a href="{{ route('tables.index') }}" class="btn btn-outline-light">Tables</a>
                    <a href="{{ route('react.kitchen.display') }}" class="btn btn-primary ms-2">Switch to React UI</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <div class="orders-container" id="ordersContainer">
            <!-- Orders will be loaded here via JavaScript -->
            <div class="status-column">
                <div class="status-header received-header">New Orders</div>
                <div id="receivedOrders"></div>
            </div>

            <div class="status-column">
                <div class="status-header processing-header">In Progress</div>
                <div id="processingOrders"></div>
            </div>

            <div class="status-column">
                <div class="status-header ready-header">Ready to Serve</div>
                <div id="readyOrders"></div>
            </div>

            <div class="status-column">
                <div class="status-header completed-header">Completed</div>
                <div id="completedOrders"></div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & JS Dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const receivedOrdersContainer = document.getElementById('receivedOrders');
            const processingOrdersContainer = document.getElementById('processingOrders');
            const readyOrdersContainer = document.getElementById('readyOrders');
            const completedOrdersContainer = document.getElementById('completedOrders');
            const refreshBtn = document.getElementById('refreshBtn');

            // Sample orders (in a real app, these would come from the Kafka consumer)
            let orders = [
                {
                    order_id: 'ORD-ABCD1234',
                    table_id: 1,
                    items: [
                        { id: 1, name: 'Pizza Margherita', quantity: 2, price: 10.99 },
                        { id: 5, name: 'Soft Drink', quantity: 2, price: 2.99 }
                    ],
                    status: 'received',
                    timestamp: new Date().toISOString()
                },
                {
                    order_id: 'ORD-EFGH5678',
                    table_id: 3,
                    items: [
                        { id: 2, name: 'Burger', quantity: 1, price: 12.99 },
                        { id: 3, name: 'Caesar Salad', quantity: 1, price: 8.99 }
                    ],
                    status: 'processing',
                    timestamp: new Date(Date.now() - 5 * 60000).toISOString() // 5 minutes ago
                },
                {
                    order_id: 'ORD-IJKL9012',
                    table_id: 5,
                    items: [
                        { id: 4, name: 'Tiramisu', quantity: 3, price: 6.99 },
                        { id: 6, name: 'Coffee', quantity: 3, price: 3.99 }
                    ],
                    status: 'ready',
                    timestamp: new Date(Date.now() - 10 * 60000).toISOString() // 10 minutes ago
                }
            ];

            // Function to load initial orders
            function loadOrders() {
                // Clear existing orders
                receivedOrdersContainer.innerHTML = '';
                processingOrdersContainer.innerHTML = '';
                readyOrdersContainer.innerHTML = '';
                completedOrdersContainer.innerHTML = '';

                // In a real app, fetch orders from the server
                fetch('/api/kitchen/orders')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            orders = data.orders;
                        }
                    })
                    .catch(() => {
                        console.log('Using sample orders (in development)');
                    })
                    .finally(() => {
                        // Display orders
                        displayOrders();
                    });
            }

            // Function to display orders
            function displayOrders() {
                orders.forEach(order => {
                    const orderCard = createOrderCard(order);

                    // Add to appropriate container based on status
                    switch (order.status) {
                        case 'received':
                            receivedOrdersContainer.appendChild(orderCard);
                            break;
                        case 'processing':
                            processingOrdersContainer.appendChild(orderCard);
                            break;
                        case 'ready':
                            readyOrdersContainer.appendChild(orderCard);
                            break;
                        case 'completed':
                            completedOrdersContainer.appendChild(orderCard);
                            break;
                    }
                });

                // Start timers for all orders
                startTimers();
            }

            // Function to create order card element
            function createOrderCard(order) {
                const orderTime = new Date(order.timestamp);

                // Create the order card element
                const orderCard = document.createElement('div');
                orderCard.className = `order-card status-${order.status}`;
                orderCard.id = `order-${order.order_id}`;
                orderCard.setAttribute('data-order-id', order.order_id);

                // Create the order header
                const orderHeader = document.createElement('div');
                orderHeader.className = 'order-header';
                orderHeader.innerHTML = `
                    <div>
                        <h5 class="m-0">Table ${order.table_id}</h5>
                        <small>${orderTime.toLocaleTimeString()}</small>
                    </div>
                    <span class="status-pill status-${order.status}">
                        ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                    </span>
                `;

                // Create the order body with items
                const orderBody = document.createElement('div');
                orderBody.className = 'order-body';

                // Add items
                order.items.forEach(item => {
                    const orderItem = document.createElement('div');
                    orderItem.className = 'order-item';
                    orderItem.innerHTML = `
                        <div>
                            <span class="quantity-badge">${item.quantity}x</span>
                            ${item.name}
                        </div>
                        <div>$${(item.price * item.quantity).toFixed(2)}</div>
                    `;
                    orderBody.appendChild(orderItem);
                });

                // Create the order footer
                const orderFooter = document.createElement('div');
                orderFooter.className = 'order-footer';

                // Add the timer element
                const timer = document.createElement('div');
                timer.className = 'timer';
                timer.id = `timer-${order.order_id}`;
                timer.textContent = 'Calculating...';

                // Add action buttons based on status
                const actions = document.createElement('div');
                actions.className = 'actions';

                switch (order.status) {
                    case 'received':
                        actions.innerHTML = `
                            <button class="btn btn-warning btn-sm status-update-btn" data-order-id="${order.order_id}" data-status="processing">
                                Start Preparing
                            </button>
                        `;
                        break;
                    case 'processing':
                        actions.innerHTML = `
                            <button class="btn btn-success btn-sm status-update-btn" data-order-id="${order.order_id}" data-status="ready">
                                Mark Ready
                            </button>
                        `;
                        break;
                    case 'ready':
                        actions.innerHTML = `
                            <button class="btn btn-secondary btn-sm status-update-btn" data-order-id="${order.order_id}" data-status="completed">
                                Complete
                            </button>
                        `;
                        break;
                    case 'completed':
                        actions.innerHTML = `
                            <button class="btn btn-outline-danger btn-sm delete-order-btn" data-order-id="${order.order_id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                        break;
                }

                // Add timer and actions to footer
                orderFooter.appendChild(timer);
                orderFooter.appendChild(actions);

                // Add all elements to the card
                orderCard.appendChild(orderHeader);
                orderCard.appendChild(orderBody);
                orderCard.appendChild(orderFooter);

                return orderCard;
            }

            // Function to start timers for all orders
            function startTimers() {
                orders.forEach(order => {
                    const timerElement = document.getElementById(`timer-${order.order_id}`);
                    if (timerElement) {
                        updateTimer(timerElement, new Date(order.timestamp));

                        // Update the timer every minute
                        setInterval(() => {
                            updateTimer(timerElement, new Date(order.timestamp));
                        }, 60000);
                    }
                });
            }

            // Function to update a single timer
            function updateTimer(element, startTime) {
                const now = new Date();
                const diffMs = now - startTime;
                const diffMins = Math.floor(diffMs / 60000);

                if (diffMins < 1) {
                    element.textContent = 'Just now';
                    element.className = 'timer text-success';
                } else if (diffMins === 1) {
                    element.textContent = '1 minute ago';
                    element.className = 'timer text-success';
                } else if (diffMins < 5) {
                    element.textContent = `${diffMins} minutes ago`;
                    element.className = 'timer text-success';
                } else if (diffMins < 10) {
                    element.textContent = `${diffMins} minutes ago`;
                    element.className = 'timer text-warning';
                } else {
                    element.textContent = `${diffMins} minutes ago`;
                    element.className = 'timer text-danger';
                }
            }

            // Function to update order status
            function updateOrderStatus(orderId, newStatus) {
                // In a real app, send request to update status on the server
                fetch(`/api/kitchen/orders/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update was successful
                        loadOrders(); // Reload all orders
                    } else {
                        console.error('Failed to update order status');
                    }
                })
                .catch(error => {
                    console.error('Error updating order status:', error);

                    // For development, update the UI anyway
                    const orderIndex = orders.findIndex(o => o.order_id === orderId);
                    if (orderIndex !== -1) {
                        orders[orderIndex].status = newStatus;
                        loadOrders(); // Reload all orders
                    }
                });
            }

            // Event delegation for status update buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('status-update-btn')) {
                    const orderId = e.target.getAttribute('data-order-id');
                    const newStatus = e.target.getAttribute('data-status');
                    updateOrderStatus(orderId, newStatus);
                }

                if (e.target.classList.contains('delete-order-btn')) {
                    const orderId = e.target.getAttribute('data-order-id');
                    // In a real app, send request to delete order
                    const orderIndex = orders.findIndex(o => o.order_id === orderId);
                    if (orderIndex !== -1) {
                        orders.splice(orderIndex, 1);
                        loadOrders(); // Reload all orders
                    }
                }
            });

            // Refresh button handler
            refreshBtn.addEventListener('click', loadOrders);

            // Initial load
            loadOrders();

            // Set up refresh interval (every minute)
            setInterval(loadOrders, 60000);

            // Set up Kafka consumer listener for real-time updates
            // In a real app, this would be done with WebSockets
            function setupKafkaListener() {
                const eventSource = new EventSource('/api/kitchen/events');

                eventSource.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    if (data.type === 'new_order') {
                        // Add new order
                        orders.push(data.order);
                        loadOrders();
                    } else if (data.type === 'update_order') {
                        // Update existing order
                        const orderIndex = orders.findIndex(o => o.order_id === data.order_id);
                        if (orderIndex !== -1) {
                            orders[orderIndex] = data.order;
                            loadOrders();
                        }
                    }
                };

                eventSource.onerror = function() {
                    console.error('EventSource failed, reconnecting...');
                    setTimeout(setupKafkaListener, 5000);
                    eventSource.close();
                };
            }

            // Uncomment this in a real application
            // setupKafkaListener();
        });
    </script>

    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</body>
</html>
