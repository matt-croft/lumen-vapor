# Lumen Vapor

We have to keep the `config` directory because of the `Harmonizing Configuration Files` command needs it, we can use the build to bypass the requirement for the `database` directory.

The only blocker at the moment is the `/vendor/laravel/vapor-core/src/Runtime/HttpKernel.php` within Vapor Core, this has a type `Application` in the constructor that cannot be changed from outside that im aware off, removing this type will allow the code to execute locally and on Vapor.

There is a brute force fix within the composer post install scripts that removes the type.

### I have no intention on maintaining this .... yet