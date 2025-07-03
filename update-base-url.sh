#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Updating BASE_URL with current IP address...${NC}"

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
PORT=9999

# Create the base URL
BASE_URL="http://${IP_ADDRESS}:${PORT}"

echo -e "${GREEN}Current IP address: ${IP_ADDRESS}${NC}"
echo -e "${GREEN}New BASE_URL: ${BASE_URL}${NC}"

# Update the .env file
if [ -f .env ]; then
    # Check if BASE_URL already exists in .env
    if grep -q "^BASE_URL=" .env; then
        # Update existing BASE_URL
        sed -i.bak "s|^BASE_URL=.*|BASE_URL=${BASE_URL}|" .env && rm -f .env.bak
    else
        # Add BASE_URL if it doesn't exist
        echo "BASE_URL=${BASE_URL}" >> .env
    fi
    echo -e "${GREEN}Updated BASE_URL in .env file${NC}"
else
    echo -e "${RED}Warning: .env file not found${NC}"
fi

# Update APP_URL in .env file
if [ -f .env ]; then
    # Check if APP_URL already exists in .env
    if grep -q "^APP_URL=" .env; then
        # Update existing APP_URL
        sed -i.bak "s|^APP_URL=.*|APP_URL=${BASE_URL}|" .env && rm -f .env.bak
    else
        # Add APP_URL if it doesn't exist
        echo "APP_URL=${BASE_URL}" >> .env
    fi
    echo -e "${GREEN}Updated APP_URL in .env file${NC}"
fi

# Update React components that use BASE_URL
# Find all JavaScript/JSX files in the resources directory
JS_FILES=$(find resources/js -type f -name "*.js" -o -name "*.jsx")

# Look for files containing BASE_URL or API_URL or hardcoded IPs
for file in $JS_FILES; do
    if grep -q "BASE_URL\|API_URL\|http://[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}" "$file"; then
        echo -e "${YELLOW}Updating $file...${NC}"

        # Use different sed syntax for macOS vs Linux
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS requires an empty string after -i

            # Handle ternary operator pattern for API_URL
            if grep -q "const API_URL = window.location.hostname === 'localhost'" "$file"; then
                # Update only the fallback URL in the ternary operator
                sed -i '' "s|(window.env?.API_URL || 'http://[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}:9999/api')|(window.env?.API_URL || '${BASE_URL}/api')|g" "$file" 2>/dev/null
            else
                # Update standard constant declarations
                sed -i '' "s|const BASE_URL = .*|const BASE_URL = '${BASE_URL}';|g" "$file" 2>/dev/null
                sed -i '' "s|const API_URL = .*|const API_URL = window.location.hostname === 'localhost' ? 'http://localhost:${PORT}/api' : (window.env?.API_URL || '${BASE_URL}/api');|g" "$file" 2>/dev/null
            fi

            # Replace any hardcoded IPs with port 9999 (common pattern in this codebase)
            sed -i '' "s|http://[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}:9999|${BASE_URL}|g" "$file" 2>/dev/null
        else
            # Linux
            # Handle ternary operator pattern for API_URL
            if grep -q "const API_URL = window.location.hostname === 'localhost'" "$file"; then
                # Update only the fallback URL in the ternary operator
                sed -i "s|(window.env?.API_URL || 'http://[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}:9999/api')|(window.env?.API_URL || '${BASE_URL}/api')|g" "$file" 2>/dev/null
            else
                # Update standard constant declarations
                sed -i "s|const BASE_URL = .*|const BASE_URL = '${BASE_URL}';|g" "$file" 2>/dev/null
                sed -i "s|const API_URL = .*|const API_URL = window.location.hostname === 'localhost' ? 'http://localhost:${PORT}/api' : (window.env?.API_URL || '${BASE_URL}/api');|g" "$file" 2>/dev/null
            fi

            # Replace any hardcoded IPs with port 9999 (common pattern in this codebase)
            sed -i "s|http://[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}:9999|${BASE_URL}|g" "$file" 2>/dev/null
        fi

        echo -e "${GREEN}Updated $file${NC}"
    fi
done

# Clear Laravel config cache
php artisan config:clear
echo -e "${GREEN}Cleared Laravel configuration cache${NC}"

# Generate a .env.js file with environment variables for the frontend
cat > public/env.js << EOL
window.env = {
  BASE_URL: '${BASE_URL}',
  API_URL: '${BASE_URL}/api',
  IP_ADDRESS: '${IP_ADDRESS}',
  PORT: ${PORT}
};
EOL
echo -e "${GREEN}Created public/env.js with environment variables${NC}"

# Restart development server if running
echo -e "${YELLOW}Note: You may need to restart your development server for changes to take effect${NC}"
echo -e "${GREEN}Done! Your application is now accessible at: ${BASE_URL}${NC}"
