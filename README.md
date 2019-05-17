Symfony API with Swagger Documentation and JWT Authentication and Authorization
============================

Requirements
----
* Requires PHP 7 and Mongo 3.2+
    * Vagrant - https://github.com/bendbennett/vagrant-php7-mysql5-mongo3
        * you'll need to update `parameters.yml.dist` and change `mongodb_server: mongodb://mongo:27017` to `mongodb_server: mongodb://localhost:27017` if you're the vagrant box.
    * Docker - https://github.com/bendbennett/docker-compose-php7-mongo3
        
Set-up
----
* Clone this repo.
* Run `composer install`.
    * if you're using the Vagrant env (linked above) then composer install will run during the ansible provisioning
    * if you're using the Docker env (linked above) then composer install will run during docker-compose up
        * you can tail the logs using `docker logs -f {name_of_docker_container_running_composer}`

Swagger Docs
----
* The Swagger docs for the API are secured with Basic Http Authentication (`swagger - swagger`) and should be visible at `http://{your_local_host}/app_dev.php/api#`.
* To change the password for the docs run the following and then add the `Encoded password` to `parameters.yml.dist` and run `composer install`:
&nbsp;

        php app/console security:encode-password {your_swagger_password}

JWT Set-Up
----
* JWT set-up uses the approach described in this post -  http://kolabdigital.com/lab-time/symfony-json-web-tokens-authentication-guard.
* You'll need to generate the keys as described in the link.
&nbsp;

        mkdir -p app/var/jwt
        openssl genrsa -out app/var/jwt/private.pem -aes256 4096
        openssl rsa -pubout -in app/var/jwt/private.pem -out app/var/jwt/public.pem

* Omit -aes256 to generate keys without using a passphrase.  
&nbsp;
    
        openssl genrsa -out app/var/jwt/private.pem 4096
        openssl rsa -pubout -in app/var/jwt/private.pem -out app/var/jwt/public.pem

Log-in
----
* You can set up an admin user by running the following:  
&nbsp;
    
        php app/console doctrine:mongodb:fixtures:load 
    
* You can then login by calling `POST /login` with the following request body:
&nbsp;
    
        {
            "email": "administrator@demo.com",
            "password": "admin"
        }
    
Running Tests
----
To run the test suite execute `bin/phpunit -c app/`.

