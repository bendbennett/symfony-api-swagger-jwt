This directory (swagger-ui) just contains the entire contents of the /dist directory from 
https://github.com/swagger-api/swagger-ui/releases

Currently using 2.1.4

    rm -rf app/cache/*
    app/console assets:install web
    app/console assetic:dump