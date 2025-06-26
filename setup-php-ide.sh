#!/bin/bash

# Get the container ID of the PHP (app) container
CONTAINER_ID=$(docker-compose ps -q app)

if [ -z "$CONTAINER_ID" ]; then
  echo "Error: PHP container is not running. Start it with 'docker-compose up -d' first."
  exit 1
fi

# Create a wrapper script that routes PHP validation to Docker
mkdir -p ~/.cursor/php-docker

cat > ~/.cursor/php-docker/php-docker.sh << 'EOL'
#!/bin/bash
docker exec -i $(docker-compose ps -q app) php "$@"
EOL

# Make the wrapper script executable
chmod +x ~/.cursor/php-docker/php-docker.sh

echo "PHP Docker wrapper created at: ~/.cursor/php-docker/php-docker.sh"
echo ""
echo "Please update your Cursor settings.json with this path:"
echo ""
echo "\"php.validate.executablePath\": \"$HOME/.cursor/php-docker/php-docker.sh\""
echo ""
echo "Restart Cursor after making this change."
