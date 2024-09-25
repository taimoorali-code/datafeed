# Laravel DataFeed (Json to XML Formatting)
=====================================

## Project Overview
-------------------

This Laravel project provides a comprehensive xml formatted files, enabling users to get well xml-formatted shopify store product details. The system parses and stores the JSON data in a database, displaying xml formatted product details according to given sample xml files. Additionally, the project features provide details with support for multiple languages, including English, and Arabic.

## Getting Started
-------------------

### Prerequisites

Before proceeding, ensure you have the following installed:

* **PHP** (version 7.4 or higher)
* **Composer**
* **Laravel** (version 8.x or higher)
* **MySQL** or another supported database

### Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/taimoorali-code/datafeed.git
    cd datafeed
    ```

2. Install Dependencies:
    ```bash
    composer install
    ```
3. Set Up Environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure Database
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Run Migrations
```bash

php artisan migrate
```
6.  Start the Development Server
```bash

php artisan serve
```
## Sync Product from Shopify Api to local Database

```bash

{app_url}/syncproduct

```
This command allows users to:
- Upload Json Products details to local / server database. 

## Export Xml Formatted File

```bash

{app_url}/feed/{language}

```
This command allows users to:
- Generate XML formatted products details according to given language 
- Example For English: http://127.0.0.1:8000/feed/en
- Example For Arabic: http://127.0.0.1:8000/feed/ar
