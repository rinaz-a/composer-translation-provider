# composer-translation-service-provider
Translation service provider

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/etna-alternance/composer-translation-provider/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/etna-alternance/composer-translation-provider/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/etna-alternance/composer-translation-provider/badge.svg?branch=master)](https://coveralls.io/github/etna-alternance/composer-translation-provider?branch=master)
[![Build Status](http://drone.etna-alternance.net/api/badge/github.com/etna-alternance/composer-translation-provider/status.svg?branch=master)](http://drone.etna-alternance.net/github.com/etna-alternance/composer-translation-provider)

Ce provider va nous permettre de renvoyer les messages `abort` dans la langue de l'utilisateur à partir de nos API vers nos clients.

Il faut ajouter un (ou plusieurs) fichier de traduction et les ajouter au EtnaConfig.php de la façon suivante :

```
    $app['translator'] = $app->extend('translator', function ($translator) {
        $translator->addResource('xliff', __DIR__.'/Utils/Silex/Translator/validators.fr.xlf', 'fr', 'validators');
        $translator->addResource('xliff', __DIR__.'/Utils/Silex/Translator/abort.fr.xlf', 'fr', 'abort');

        return $translator;
    });
```

Ceux ci seront remplis de la façon suivante :

```
<?xml version="1.0"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" datatype="plaintext" original="file.ext">
        <body>
            <trans-unit id="1">
                <source>Hello %name%</source>
                <target>Bonjour %name%</target>
            </trans-unit>
        </body>
    </file>
</xliff>
```

`%name%` étant ici un token (optionnel) que l'on peut ajouter à la traduction, il faudra le placer dans le header du `abort` comme ceci :
/!\ Le nom du token ne doit pas contenir de caractères spéciaux, on préfèrera ici le camelCase.

```
    return $app->abort(400, "Bad request : operation forbidden on a deleted %entityName%", [
        'Translation' => json_encode(['%entityName%' => $entity_name])
    ]);
```

Lorsque le message ne contient pas de token on n'as pas besoin d'envoyer de 3ème paramètre au `$app->abort()`

Pour que les `headers` que l'on surcharge au `app->abort()` soient bien envoyés dans la response que recevra notre provider il faut que la fonction `onKernelExpection` de votre `ExceptionListener` les récupère et les set correctement comme ici :

```
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $headers   = [];

        switch (true) {
            case method_exists($exception, 'getStatusCode'):
                $code = $exception->getStatusCode();
                break;

            case method_exists($exception, 'getCode'):
                $code = $exception->getCode();
                break;

            default:
                $code = 500;
                break;
        }

        if (is_a($exception, "InvalidArgumentException")) {
            $code = 400;
        }
        if (is_a($exception, "Symfony\Component\HttpKernel\Exception\HttpException")) {
            $headers                = $exception->getHeaders();
            $headers["Translation"] = json_encode($headers);
        }
        $event->setResponse(
            new JsonResponse(
                (true === $this->app["debug"] || 500 !== $code) ? $exception->getMessage() : null,
                $code,
                $headers
            )
        );
    }
```
