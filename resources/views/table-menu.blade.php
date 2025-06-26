<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Menu - {{ $tableName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        .item-body {
            padding: 10px 15px;
        }
        .item-description {
            color: #6c757d;
        }
        .item-price {
            font-weight: bold;
            color: #28a745;
        }
        .cart-container {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            padding: 15px;
            display: none;
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
        .category-nav .nav-link {
            color: #343a40;
            font-weight: 500;
        }
        .category-nav .nav-link.active {
            color: #007bff;
            border-bottom: 2px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="m-0">{{ $tableName }}</h2>
                <p class="m-0">Digital Menu</p>
            </div>
        </div>
    </div>

    <div class="category-nav">
        <div class="container">
            <div class="nav nav-pills nav-fill overflow-auto">
                <a class="nav-link active" href="#starters">Starters</a>
                <a class="nav-link" href="#mains">Main Dishes</a>
                <a class="nav-link" href="#desserts">Desserts</a>
                <a class="nav-link" href="#drinks">Drinks</a>
            </div>
        </div>
    </div>

    <div class="container mt-4 mb-5 pb-5">
        <!-- Group menu items by category -->
        @php
            $categories = collect($menuItems)->groupBy('category');
        @endphp

        @foreach($categories as $category => $items)
            <div class="menu-section" id="{{ strtolower($category) }}s">
                <h3>{{ $category }}</h3>

                @foreach($items as $item)
                <div class="menu-item">
                    <div class="item-header">
                        <h5 class="item-title mb-0">{{ $item['name'] }}</h5>
                        <span class="item-price">${{ number_format($item['price'], 2) }}</span>
                    </div>
                    <div class="item-body">
                        <p class="item-description">{{ $item['description'] }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="quantity-controls">
                                <button class="btn btn-sm btn-outline-secondary decrease-qty" data-id="{{ $item['id'] }}">-</button>
                                <span class="quantity-display mx-2" id="qty-{{ $item['id'] }}">0</span>
                                <button class="btn btn-sm btn-outline-secondary increase-qty" data-id="{{ $item['id'] }}">+</button>
                            </div>
                            <button class="btn btn-primary add-to-cart"
                                    data-id="{{ $item['id'] }}"
                                    data-name="{{ $item['name'] }}"
                                    data-price="{{ $item['price'] }}">
                                Add to Order
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="cart-toggle">
        <i class="bi bi-cart"></i>
        <span class="cart-badge" id="cart-count">0</span>
    </div>

    <div class="cart-container">
        <div class="container">
            <h4>Your Order</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cart-items">
                        <!-- Cart items will be inserted here -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total:</td>
                            <td id="cart-total" class="fw-bold">$0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn btn-outline-secondary" id="close-cart">Continue Ordering</button>
                <button class="btn btn-success" id="place-order">Place Order</button>
            </div>
        </div>
    </div>

    <!-- Order Success Modal -->
    <div class="modal fade" id="orderSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Order Placed!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Your order has been sent to the kitchen.</p>
                    <p>Order ID: <span id="order-id"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & JS Dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize cart
            let cart = [];
            const tableId = {{ $tableId }};

            // DOM elements
            const cartToggle = document.querySelector('.cart-toggle');
            const cartContainer = document.querySelector('.cart-container');
            const closeCart = document.getElementById('close-cart');
            const cartCount = document.getElementById('cart-count');
            const cartItems = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');
            const placeOrderBtn = document.getElementById('place-order');

            // Cart toggle functionality
            cartToggle.addEventListener('click', function() {
                cartContainer.style.display = cartContainer.style.display === 'block' ? 'none' : 'block';
            });

            closeCart.addEventListener('click', function() {
                cartContainer.style.display = 'none';
            });

            // Add to cart functionality
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = parseInt(this.getAttribute('data-id'));
                    const name = this.getAttribute('data-name');
                    const price = parseFloat(this.getAttribute('data-price'));
                    const quantity = parseInt(document.getElementById(`qty-${id}`).textContent);

                    if (quantity > 0) {
                        // Check if item is already in cart
                        const existingItem = cart.find(item => item.id === id);

                        if (existingItem) {
                            existingItem.quantity += quantity;
                        } else {
                            cart.push({
                                id: id,
                                name: name,
                                price: price,
                                quantity: quantity
                            });
                        }

                        // Reset quantity display
                        document.getElementById(`qty-${id}`).textContent = "0";

                        // Update cart display
                        updateCartDisplay();
                    }
                });
            });

            // Quantity control functionality
            const decreaseButtons = document.querySelectorAll('.decrease-qty');
            const increaseButtons = document.querySelectorAll('.increase-qty');

            decreaseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const qtyDisplay = document.getElementById(`qty-${id}`);
                    let qty = parseInt(qtyDisplay.textContent);
                    if (qty > 0) {
                        qtyDisplay.textContent = qty - 1;
                    }
                });
            });

            increaseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const qtyDisplay = document.getElementById(`qty-${id}`);
                    let qty = parseInt(qtyDisplay.textContent);
                    qtyDisplay.textContent = qty + 1;
                });
            });

            // Update cart display
            function updateCartDisplay() {
                // Update cart count
                const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
                cartCount.textContent = totalItems;

                // Clear existing cart items
                cartItems.innerHTML = '';

                // Add each item to the cart display
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.name}</td>
                        <td>${item.quantity}</td>
                        <td>$${item.price.toFixed(2)}</td>
                        <td>$${itemTotal.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger remove-item" data-id="${item.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                    cartItems.appendChild(row);
                });

                // Add remove item functionality
                document.querySelectorAll('.remove-item').forEach(button => {
                    button.addEventListener('click', function() {
                        const id = parseInt(this.getAttribute('data-id'));
                        cart = cart.filter(item => item.id !== id);
                        updateCartDisplay();
                    });
                });

                // Update total
                const total = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
                cartTotal.textContent = `$${total.toFixed(2)}`;
            }

            // Place order functionality
            placeOrderBtn.addEventListener('click', function() {
                if (cart.length === 0) {
                    alert('Your order is empty!');
                    return;
                }

                // Prepare order data
                const orderItems = cart.map(item => ({
                    id: item.id,
                    name: item.name,
                    quantity: item.quantity,
                    price: item.price
                }));

                // Send order to server
                fetch(`/tables/${tableId}/order`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        items: orderItems
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success modal
                        document.getElementById('order-id').textContent = data.order_id;
                        const modal = new bootstrap.Modal(document.getElementById('orderSuccessModal'));
                        modal.show();

                        // Clear cart
                        cart = [];
                        updateCartDisplay();
                        cartContainer.style.display = 'none';
                    } else {
                        alert('Failed to place order: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            });

            // Smooth scrolling for category navigation
            document.querySelectorAll('.category-nav .nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Remove active class from all links
                    document.querySelectorAll('.category-nav .nav-link').forEach(l => {
                        l.classList.remove('active');
                    });

                    // Add active class to clicked link
                    this.classList.add('active');

                    // Scroll to section
                    const id = this.getAttribute('href').substring(1);
                    const section = document.getElementById(id);
                    if (section) {
                        const offset = section.offsetTop - 140; // Account for header and nav
                        window.scrollTo({
                            top: offset,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>

    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</body>
</html>
