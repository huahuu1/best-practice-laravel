#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting all services with auto IP detection...${NC}"

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

echo -e "${GREEN}Detected IP address: ${IP_ADDRESS}${NC}"

# First, run the update-base-url script
./update-base-url.sh

# Check if the update script was successful
if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to update IP address. Exiting.${NC}"
    exit 1
fi

# Start the chat service in the background
echo -e "${YELLOW}Starting chat service...${NC}"
cd ../restaurant-chat-service
./start-dev-auto-ip.sh &
CHAT_PID=$!
cd ../best-practice-laravel

# Wait a moment for the chat service to start
sleep 2

# Copy the chat service env.js to the Laravel public directory
echo -e "${YELLOW}Syncing environment files...${NC}"
npm run sync-chat-env

# Kill any existing npm run dev processes
echo -e "${YELLOW}Stopping any running Laravel development servers...${NC}"
pkill -f "npm run dev" 2>/dev/null
pkill -f "vite" 2>/dev/null

# Start the Laravel development server
echo -e "${GREEN}Starting Laravel development server...${NC}"
VITE_HOST=${IP_ADDRESS} npm run dev

# Function to handle script termination
cleanup() {
    echo -e "${YELLOW}Stopping all services...${NC}"
    kill $CHAT_PID 2>/dev/null
    exit 0
}

# Set up trap to catch termination signals
trap cleanup SIGINT SIGTERM

# Keep the script running to maintain the background processes
wait
