#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Updating IP addresses for all services...${NC}"

# Get the local IP address
IP_ADDRESS=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -n 1)

if [ -z "$IP_ADDRESS" ]; then
  echo "Could not determine local IP address, using localhost"
  IP_ADDRESS="localhost"
fi

echo "Using IP address: $IP_ADDRESS"

# Update Laravel .env file
echo "Updating Laravel .env file..."
sed -i '' "s/HOST=.*/HOST=$IP_ADDRESS/g" .env
sed -i '' "s/KAFKA_BROKER=.*/KAFKA_BROKER=$IP_ADDRESS:9092/g" .env
sed -i '' "s|BASE_URL=.*|BASE_URL=http://$IP_ADDRESS:9999|g" .env
sed -i '' "s|APP_URL=.*|APP_URL=http://$IP_ADDRESS:9999|g" .env

# Update public/env.js file
echo "Updating public/env.js file..."
cat > public/env.js << EOL
window.env = {
  BASE_URL: 'http://$IP_ADDRESS:9999',
  API_URL: 'http://$IP_ADDRESS:9999/api',
  IP_ADDRESS: '$IP_ADDRESS',
  PORT: 9999,
  WS_URL: 'http://$IP_ADDRESS:3001'
};
EOL

# Update chat service .env file if it exists
CHAT_SERVICE_DIR="/Users/huuht/Documents/practice-code/restaurant-chat-service"
if [ -d "$CHAT_SERVICE_DIR" ]; then
  echo "Updating chat service .env file..."
  sed -i '' "s/HOST=.*/HOST=$IP_ADDRESS/g" "$CHAT_SERVICE_DIR/.env"
  sed -i '' "s/KAFKA_BROKER=.*/KAFKA_BROKER=$IP_ADDRESS:9092/g" "$CHAT_SERVICE_DIR/.env"

  # Create env.js for chat service
  mkdir -p "$CHAT_SERVICE_DIR/public"
  cat > "$CHAT_SERVICE_DIR/public/env.js" << EOL
window.env = {
  BASE_URL: 'http://$IP_ADDRESS:9999',
  API_URL: 'http://$IP_ADDRESS:9999/api',
  IP_ADDRESS: '$IP_ADDRESS',
  PORT: 3001,
  WS_URL: 'http://$IP_ADDRESS:3001'
};
EOL
fi

echo "All IP addresses updated successfully!"

# Rebuild frontend assets
echo "Rebuilding frontend assets..."
npm run build

# Restart Laravel containers
echo "Restarting Laravel containers..."
docker-compose restart app nginx

echo "Done!"
