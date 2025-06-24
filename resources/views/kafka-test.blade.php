<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafka Test</title>
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
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
        }
        .card-header {
            background-color: #6c757d;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        #response {
            min-height: 100px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Laravel Kafka Integration</h1>

        <div class="row">
            <!-- Producer Card -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Send Message to Kafka</h5>
                    </div>
                    <div class="card-body">
                        <form id="produceForm">
                            <div class="mb-3">
                                <label for="topic" class="form-label">Topic</label>
                                <input type="text" class="form-control" id="topic" value="test-topic" required>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="3" required>Hello Kafka from Laravel!</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="key" class="form-label">Key (optional)</label>
                                <input type="text" class="form-control" id="key" value="test-key">
                            </div>

                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Response Card -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Response</h5>
                    </div>
                    <div class="card-body">
                        <pre id="response">No response yet...</pre>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Instructions</h5>
                    </div>
                    <div class="card-body">
                        <p>To consume messages, run the following command in your terminal:</p>
                        <pre>docker-compose exec app php artisan kafka:consume</pre>

                        <p class="mt-3">To view Kafka topics and messages:</p>
                        <a href="http://localhost:8080" target="_blank" class="btn btn-secondary">Open Kafka UI</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('produceForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const topic = document.getElementById('topic').value;
            const message = document.getElementById('message').value;
            const key = document.getElementById('key').value;

            const responseElem = document.getElementById('response');
            responseElem.innerText = 'Sending message...';

            fetch('/kafka/produce', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    topic: topic,
                    message: message,
                    key: key || null
                })
            })
            .then(response => response.json())
            .then(data => {
                responseElem.innerText = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                responseElem.innerText = 'Error: ' + error;
            });
        });
    </script>
</body>
</html>
