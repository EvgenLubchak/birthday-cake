FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    zip \
    curl \
    && docker-php-ext-install \
    pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create application directory
WORKDIR /var/www/app

# Copy composer files first for better caching
COPY composer.json ./

# Create a temporary composer.lock if it doesn't exist and install only production dependencies
RUN if [ ! -f composer.lock ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs; \
    else \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Copy application files
COPY src/ ./src/
COPY bin/ ./bin/

# Make the binary executable
RUN chmod +x bin/cake-calculator

# Create non-root user
RUN addgroup -S appgroup && adduser -S appuser -G appgroup
RUN chown -R appuser:appgroup /var/www/app
USER appuser

# Set default command
ENTRYPOINT ["php", "bin/cake-calculator"]
CMD ["--help"]
