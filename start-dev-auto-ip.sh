#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting chat service with auto IP detection...${NC}"

# First, run the update-base-url script
./update-base-url.sh

# Check if the update script was successful
if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to update IP address. Exiting.${NC}"
    exit 1
fi

# Get the current IP address (works on macOS and most Linux distributions)
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    IP_ADDRESS=$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null)
    if [ -z "$IP_ADDRESS" ]; then
        # Try alternative method for macOS
        IP_ADDRESS=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -n 1)
    fi
else
    # Linux
    IP_ADDRESS=$(hostname -I | awk '{print $1}')
fi

# Check if IP was found
if [ -z "$IP_ADDRESS" ]; then
    echo -e "${RED}Error: Could not determine IP address.${NC}"
    exit 1
fi

# Set the port
PORT=3001
# Set Kafka broker with detected IP
KAFKA_BROKER="${IP_ADDRESS}:9092"

echo -e "${GREEN}Detected IP address: ${IP_ADDRESS}${NC}"
echo -e "${GREEN}Service will be available at: http://${IP_ADDRESS}:${PORT}${NC}"
echo -e "${GREEN}Using Kafka broker: ${KAFKA_BROKER}${NC}"

# Create a .env file for the chat service
cat > .env << EOL
HOST=${IP_ADDRESS}
PORT=${PORT}
KAFKA_BROKER=${KAFKA_BROKER}
EOL
echo -e "${GREEN}Created .env file with environment variables${NC}"

# Create an env.js file for the frontend to access
mkdir -p public
cat > public/env.js << EOL
window.env = {
  WS_URL: 'http://${IP_ADDRESS}:${PORT}',
  IP_ADDRESS: '${IP_ADDRESS}',
  PORT: ${PORT}
};
EOL
echo -e "${GREEN}Created public/env.js with environment variables${NC}"

# Copy the env.js file to the Laravel project's public directory
if [ -d "../best-practice-laravel/public" ]; then
    cp public/env.js ../best-practice-laravel/public/
    echo -e "${GREEN}Copied env.js to Laravel project's public directory${NC}"
fi

# Kill any existing npm run start:dev processes
echo -e "${YELLOW}Stopping any running development servers...${NC}"
pkill -f "npm run start:dev" 2>/dev/null
pkill -f "nest start" 2>/dev/null

# Start the development server with the detected IP
echo -e "${GREEN}Starting development server...${NC}"
HOST=${IP_ADDRESS} PORT=${PORT} KAFKA_BROKER=${KAFKA_BROKER} npm run start:dev
