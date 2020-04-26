# Lumen Vapor

## What is this project?

This is a fun experiment to see if Lumen can be fun on Laravel Vapor, the aim is just to get it working at a bare minimum with further intent to improve it if it proves worthwhile.

I would suggest you do not run this code in any form of production environment or for production application's.

We have to keep the `config` directory because of Vapor's `Harmonizing Configuration Files` command needs it, as well as the `database` directory as Vapor does not check for its existence before trying to pull files from it.

## Handlers

The `lumen-vapor.yml` file is where you would define your handlers that should exists, these are essentialy routes but wrapped in an easy way to manage, the structure is as follows, only basic route styles are supported at this time. They get parsed via the `Router` so parameter styled routes will work as normal within the `path`.

```
handlers:
    get-version: # Route Name
        handler: GetVersion.handle # handlerClassName.methodToCall
        path: / # Uri
        method: get # Method
```

## Disclaimer

The only blocker at the moment is the `/vendor/laravel/vapor-core/src/Runtime/HttpKernel.php` within Vapor Core, this has a type `Application` in the constructor that cannot be changed from outside that im aware off, removing this type will allow the code to execute locally and on Vapor.

There is a brute force fix within the composer post install scripts that removes the type.

### I have no intention on maintaining this .... yet