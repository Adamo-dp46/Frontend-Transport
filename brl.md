### Brl

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
- 

- 
- **Cookie `rt` : flag `secure` conditionnel** — `withSecure($request->isSecure())` (`ApiAuthenticator` + `RefreshTokenCookieSubscriber`). Derrière un reverse proxy en prod, si les *trusted proxies* ne sont pas configurés, `isSecure()` peut renvoyer `false` → cookie de refresh envoyé en clair sur HTTP. → Configurer `framework.trusted_proxies`/`trusted_headers`, ou forcer `secure: true` en prod.


- On vas ajouter la possibilités d'exporter les données de statistique

- Guide utilisateur via Driver.js ou Intro.js avec persisantce de l'état sur lequel l'utilisateur est et peut recommencer

**Intro.js** `introjs.com` - Permet d'indiquer l'utilisation d'une application à un utilisateur
- npm install intro.js --save

## Comparaison Driver.js vs Intro.js

**Driver.js**
- Plus moderne, léger (~5kb gzip)
- Highlight visuel élégant — met en surbrillance l'élément ciblé avec un overlay
- API simple et flexible
- Pas de dépendances
- Meilleur pour des guides contextuels par page/module
- Gratuit et open source

**Intro.js**
- Plus ancien, plus lourd
- Style "tooltip numéroté" classique
- Nécessite une licence pour usage commercial
- Plus de configuration nécessaire

**Recommandation : Driver.js** — plus adapté à ton cas car tu as des guides par module (contextuels par page), il est plus léger, gratuit et son rendu est plus moderne.