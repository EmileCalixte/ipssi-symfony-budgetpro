## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

What things you need to install the software and how to install them?

- [Composer](https://getcomposer.org/)
- [Docker CE](https://www.docker.com/community-edition)
- [Docker Compose](https://docs.docker.com/compose/install)

### Install

#### (optional) Create your `docker-compose.override.yml` file

```bash
cp docker-compose.override.yml.dist docker-compose.override.yml
```
> Notice : Check the file content. If other containers use the same ports, change yours.

#### Install composer dependencies

```bash
composer install
```

### Init

```bash
cp .env.dist .env
docker-compose up -d
docker-compose exec --user=application web bash
php bin/console d:s:u --force
```

Load fake data with:
```bash
php bin/console app:fixtures
```

### API endpoints

#### Anonymous

##### Subscriptions
- `GET /subscriptions` Returns list of subscriptions
- `GET /subscriptions/{id}` Returns details of one subscription

##### Users
- `GET /users` Returns list of users
- `GET /users/{id}` Returns details of one user, with his subscription and his cards
- `POST /users` Creates a new user

#### Admin

##### Subscriptions
- `GET /admin/subscriptions` Returns list of subscriptions with list of users for each subscription
- `GET /admin/subscriptions/{id}` Returns details of one subscription with list of users for this subscription
- `POST /admin/subscriptions` Creates a new subscription
- `PATCH /admin/subscriptions/{id}` Edits a subscription
- `DELETE /admin/subscriptions/{id}` Deletes a subscription (only if this subscription has no user)

