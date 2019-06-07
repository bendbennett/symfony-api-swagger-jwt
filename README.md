# Symfony API with OpenAPI (Swagger) Documentation and JWT Authentication and Authorization

## Requirements
* [Docker](https://github.com/bendbennett/docker-compose-php7-mongo3) development environment for use with this repo, includes:
  * nginx
  * php-fpm
  * mongodb
  * composer
* Clone this repo.
* [Create the JWT keys](#jwtkeys).
* Bring up the stack following the instructions in the `README` for the [Docker](https://github.com/bendbennett/docker-compose-php7-mongo3) development environment.

## OpenAPI (Swagger) Docs
* The OpenAPI (Swagger) docs for the API are secured with Basic Http Authentication (`swagger - swagger`) and should be visible at [http://localhost/docs](http://localhost/docs).

## <a name="jwt_keys"></a>JWT Set-Up
Generate the keys as described in the [documentation](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/master/Resources/doc/index.md#generate-the-ssh-keys-) for the JWT Authentication Bundle. Essentially, you'll need to:

* Create a directory for the keys:

      mkdir -p config/jwt

* Generate the keys without a passphrase:
    
      openssl genrsa -out config/jwt/private.pem 4096

      openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

* If you want to use keys with a passphrase use the following:

      openssl genrsa -out config/jwt/private.pem -aes256 4096

      openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

  * Note that if you use a passphrase you'll need to add `JWT_PASSPHRASE` to `.env`

## Log-in
* The seeded mongodb already contains an admin user, but if you need to create one you can run the following within the php-fpm container:
    
      docker exec -it <docker-php-fpm-container-name> bash
      ./bin/console doctrine:mongodb:fixtures:load
    
* You can then login by calling `POST /login` with the following request body:
    
      {
        "email": "administrator@demo.com",
        "password": "admin"
      }

* For example,

      curl -X "POST" "http://localhost/login" \
           -H 'Content-Type: application/json' \
           -d $'{
                  "email": "administrator@demo.com",
                  "password": "admin"
                }'

## Running Tests
To run the test suite:

    docker exec -it <docker-php-fpm-container-name> bash
    ./bin/phpunit

## Tags
### 4.3.0
* Symfony Framework 4.3
* Symfony Flex 1.2

