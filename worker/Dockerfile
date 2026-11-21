FROM python:3.11-slim

# Install system dependencies including FFmpeg and curl
RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy requirements (matches whether context is repo root or worker/ directory)
COPY requirements.txt* worker/requirements.txt* ./
RUN if [ -f requirements.txt ]; then pip install --no-cache-dir -r requirements.txt; \
    elif [ -f worker/requirements.txt ]; then pip install --no-cache-dir -r worker/requirements.txt; fi

# Copy application files
COPY . .

ENV PYTHONUNBUFFERED=1

# Execute main.py from either root or worker/ subdirectory
CMD ["sh", "-c", "if [ -f worker/main.py ]; then python worker/main.py; else python main.py; fi"]
