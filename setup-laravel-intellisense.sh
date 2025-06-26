#!/bin/bash

# Create a wrapper script for Laravel Extra Intellisense to use Docker's PHP
mkdir -p ~/.cursor/php-docker

# Create a wrapper script that will be used by Laravel Extra Intellisense
cat > ~/.cursor/php-docker/php-intellisense.sh << 'EOL'
#!/bin/bash

# Get the container ID of the running app container
CONTAINER_ID=$(docker ps --filter "name=best-practice-laravel-app" --format "{{.ID}}")

if [ -z "$CONTAINER_ID" ]; then
  echo "Error: PHP container is not running" >&2
  exit 1
fi

# Execute PHP command in the Docker container
docker exec -i $CONTAINER_ID php "$@"
EOL

# Make the wrapper script executable
chmod +x ~/.cursor/php-docker/php-intellisense.sh

echo "Laravel Extra Intellisense PHP wrapper created"
echo ""
echo "Add the following to your Cursor settings.json:"
echo ""
echo "\"amiralizadeh9480.laravel-extra-intellisense.phpCommand\": \"$HOME/.cursor/php-docker/php-intellisense.sh\""
echo ""
echo "Restart Cursor after making this change."
