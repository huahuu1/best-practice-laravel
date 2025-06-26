#!/bin/bash

# Script to regenerate and view Swagger documentation

echo "Regenerating API documentation..."
docker-compose exec -e L5_SWAGGER_CONST_HOST=http://localhost:9999/api app php artisan l5-swagger:generate

echo "API documentation is available at: http://localhost:9999/api/documentation"
echo "Run the app with: docker-compose up -d"

# Optional: Open the documentation in the default browser
if command -v open >/dev/null 2>&1; then
  echo "Opening documentation in browser..."
  open http://localhost:9999/api/documentation
elif command -v xdg-open >/dev/null 2>&1; then
  echo "Opening documentation in browser..."
  xdg-open http://localhost:9999/api/documentation
elif command -v start >/dev/null 2>&1; then
  echo "Opening documentation in browser..."
  start http://localhost:9999/api/documentation
else
  echo "Please open the documentation in your browser manually."
fi
