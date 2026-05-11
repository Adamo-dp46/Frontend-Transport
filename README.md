### FT-Transport

- **Command**
    > php -S localhost:5000 -t public | symfony serve
    > php bin/console cache:clear
    > php bin/console cache:warmup
    > php bin/console debug:router
    > php bin/console make:controller
    > php bin/console make:entity
    > php bin/console make:form
    > php bin/console make:voter
    > php bin/console make:listener
    > php bin/console make:subscriber
    > php bin/console translation:extract --force fr --format=yaml
    > php bin/console make:state-processor
    > php bin/console make:state-provider

- **Git**
    > git remote add origin git@github.com:Damo-dp45/Frontend-Transport.git
    > git branch -M main
    > git push -u origin main

- **Production**
    > La 1ère
        > git clone https://github.com/Damo-dp45/Frontend-Transport.git .
        > Pour le `.env..` on peut `cp .env .env.local` ou `composer dump-env prod` qui génère un fichier `.env.local.php` qui est plus optimisé
        > composer install --no-dev --optimize-autoloader
        > composer require symfony/apache-pack
        > php bin/console cache:clear --env=prod
        > php bin/console cache:warmup --env=prod
    > Les prochaines
        > git pull origin main
        > composer install --no-dev --optimize-autoloader
        > php bin/console cache:clear --env=prod
        > php bin/console cache:warmup --env=prod