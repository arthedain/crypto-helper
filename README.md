# Crypto test

## Installation

Steps to install the project:

1. Clone the repository:
    ```sh
    git clone https://github.com/arthedain/crypto-helper.git
    ```
2. Navigate to the project directory:
    ```sh
    cd crypto-helper
    ```
3. Install the necessary dependencies:
    ```sh
    composer install
    ```

## Docker Setup

Steps to run the project using Docker:

1. Make sure you have Docker installed on your machine. If not, follow
   the [installation guide](https://docs.docker.com/get-docker/).
2. Build the Docker image:
    ```sh
    docker-compose build
    ```
3. Run the Docker container:
    ```sh
    docker-compose up -d
    ```

# CalcProfitCommand

## Overview
The `CalcProfitCommand` is a console command for calculating potential profit opportunities across various cryptocurrency exchanges. It allows users to input two currencies and displays any profitable trading opportunities between the specified exchanges.

## Command Signature
```bash
php artisan app:calc-profit
```

# GetCurrencyPairsCommand

## Overview
The `GetCurrencyPairsCommand` is a console command that retrieves the maximum and minimum prices for a specified currency pair from various cryptocurrency exchanges. It allows users to input two currencies and displays the highest and lowest prices along with the corresponding exchanges.

## Command Signature
```bash
php artisan app:get-currency-pairs
```
