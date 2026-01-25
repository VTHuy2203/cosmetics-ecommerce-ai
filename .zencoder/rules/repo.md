---
description: Repository Information Overview
alwaysApply: true
---

# Repository Information Overview

## Repository Summary
This repository contains "WebBanHangMyPham", a comprehensive E-commerce system for cosmetics. It is a multi-component system comprising a PHP customer frontend, a PHP admin panel with a Bootstrap theme, a real-time WebSocket server, and a Python-based Machine Learning/RAG service for recommendations and chat.

## Repository Structure
The main project files are located in the `TTTT` directory:
- **WebBanHangMyPham**: Customer-facing website (PHP).
- **AdminManage**: Admin management interface (PHP + Bootstrap theme).
- **WebSocketServer**: Real-time communication server (PHP Ratchet).
- **Image_upload** & **imgs**: Shared asset storage.

## Projects

### WebBanHangMyPham (Customer Web)
**Location**: `TTTT/WebBanHangMyPham`

#### Language & Runtime
**Language**: PHP
**Version**: Not explicitly locked (implied >= 7.4)
**Package Manager**: Composer

#### Dependencies
**Configuration File**: `src/composer.json`
**Main Dependencies**:
- `phpmailer/phpmailer`: Email services (^6.6).

#### Structure
- **src/View**: View files for the frontend.
- **src/Processing**: Backend logic handlers.
- **src/Layouts**: Common layout components.
- **src/ML**: Machine Learning service integration.

### AdminManage (Admin Panel)
**Location**: `TTTT/AdminManage`

#### Language & Runtime
**Language**: PHP (Backend), HTML/SCSS/JS (Frontend Theme)
**Frontend Build System**: Gulp (Node.js)

#### Dependencies
**Theme Configuration**: `system/package.json`
**Main Frontend Dependencies**:
- `bootstrap`: 4.6.0
- `jquery`: 3.6.0
- `@fortawesome/fontawesome-free`: 5.15.3

#### Build & Installation
The `system` directory contains the build tools for the admin theme:
```bash
# In TTTT/AdminManage/system
npm install
npm start  # Runs gulp watch
```

#### Structure
- **src/**: PHP backend logic (`Brands`, `Products`, `Users`, etc.).
- **system/**: Theme development and build tools.
- **css/**, **js/**: Compiled assets (SB Admin 2).

### WebSocketServer
**Location**: `TTTT/WebSocketServer`
**Description**: Real-time WebSocket server for notifications and chat.

#### Language & Runtime
**Language**: PHP
**Version**: >=7.4
**Package Manager**: Composer

#### Dependencies
**Configuration File**: `composer.json`
**Main Dependencies**:
- `cboden/ratchet`: ^0.4
- `react/socket`: ^1.0

#### Usage
**Entry Point**: `server.php`
```bash
php server.php
```
Runs on `0.0.0.0:8080`.

### ML Service (RAG & Recommendations)
**Location**: `TTTT/WebBanHangMyPham/src/ML`
**Description**: Python-based Retrieval-Augmented Generation (RAG) system using FastAPI and ChromaDB.

#### Language & Runtime
**Language**: Python
**Version**: 3.x

#### Dependencies
**Configuration File**: `requirements.txt`
**Main Dependencies**:
- `fastapi`, `uvicorn`: Web server.
- `chromadb`: Vector database.
- `sentence-transformers`: Embeddings.
- `mysql-connector-python`: Database connection.
- `langdetect`: Language detection.

#### Usage
**Entry Point**: `api_rag_ollama.py`
```bash
# Install dependencies
pip install -r requirements.txt

# Run the API
# Defaults to port 8010 if run directly
python api_rag_ollama.py
```

## Global Configuration
**Root Directory**: `TTTT/`
**Shared Dependencies**: `TTTT/composer.json` declares `phpmailer/phpmailer` and `vlucas/phpdotenv`.
**Database**: `webmipham1.sql` (MySQL dump).
