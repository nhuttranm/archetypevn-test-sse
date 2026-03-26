FROM node:20-alpine

WORKDIR /app

# Install dependencies first (for better caching)
COPY package*.json ./
RUN npm install

# Copy application source
COPY . .

# Expose Vite's default dev server port
EXPOSE 5173

# Run the dev server directly, exposing it to the container network
CMD ["npm", "run", "dev", "--", "--host"]
