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

Full documentation is available at `/api/doc`

#### Anonymous

##### - Subscriptions
- `GET /subscriptions` Returns list of subscriptions
- `GET /subscriptions/{id}` Returns details of one subscription

##### - Users
- `GET /users` Returns list of users
- `GET /users/{id}` Returns details of one user, with his subscription and his cards
- `POST /users` Creates a new user

#### User

##### - Profile
- `GET /profile` Returns details of user's profile
- `PATCH /profile` Edits user's profile

##### - Cards
- `GET /profile/cards` Returns list of user's cards
- `GET /profile/cards/{id}` Returns details of one user's card
- `POST /profile/cards` Create a new card for user
- `PATCH /profile/card/{id}` Edits a user's card
- `DELETE /profile/card/{id}` Deletes a user's card

#### Admin

##### - Subscriptions
- `GET /admin/subscriptions` Returns list of subscriptions with list of users for each subscription
- `GET /admin/subscriptions/{id}` Returns details of one subscription with list of users for this subscription
- `POST /admin/subscriptions` Creates a new subscription
- `PATCH /admin/subscriptions/{id}` Edits a subscription
- `DELETE /admin/subscriptions/{id}` Deletes a subscription (only if this subscription has no user)

##### - Users
- `GET /admin/users` Return detailed list of all users
- `GET /admin/users/{id}` Return details of one user
- `PATCH /admin/users/{id}` Edits a user
- `DELETE /admin/users/{id}` Deletes a user and his cards

##### - Cards
- `GET /admin/cards` Returns list of cards
- `GET /admin/cards/{id}` Returns details of one card
- `POST /admin/cards` Creates a new card
- `PATCH /admin/cards/{id}` Edits a card
- `DELETE /admin/cards/{id}` Deletes a card

### Commands

- Use `php bin/console app:add-admin` to grant admin rights to an existing user or to create a new admin user
- Use `php bin/console app:countcards` to display the number of cards of a user
