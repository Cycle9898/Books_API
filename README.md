# Books_API

REST API created with PHP Symfony to access resources from a library.

You can fetch the books list, create, get details, edit or delete a book.

You can fetch the authors list, create, get details, edit or delete an author.

Books and Authors endpoints handle users authentication via JWT tokens. You can log in with '/login_check' endpoint.

All data are stored in a PostgreSQL database.

The documentation was created with Nelmio and is accessible on the endpoint '/api/doc'.

The main goal is to learn how the Symfony framework works.

## Getting Started

### Prerequisites

This project uses the following tech stack:

-   [PHP](https://www.php.net/downloads)

-   [Composer](https://getcomposer.org/)

-   [Symfony](https://symfony.com/) with its CLI

-   [PostgreSQL](https://www.postgresql.org/) for local PostgreSQL database but you can use a cloud hosted one

-   [Postman](https://www.postman.com/) to test endpoints [optional]

### Instructions

1. Create the database locally or online
2. Clone the repo onto your computer
3. Open a terminal in the cloned project folder
4. Install dependencies with composer: `composer install`
5. Create .env file and set database url in the "DATABASE_URL" env variable.
6. Generate Public and Private keys for JWT tokens with `symfony console lexik:jwt:generate-keypair`
7. Start the project with this command:

```bash
# Start local dev server
symfony server:start
```

Open Postman and fetch on a endpoint starting with 'http://localhost:8000/api' (default).
