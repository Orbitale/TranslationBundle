1. Installation
---------------

**With Composer**

Just write this command line instruction if Composer is installed in your Symfony root directory :

```sh
composer require orbitale/translation-bundle
```

I recommend to use Composer, as it is the best way to keep this repository update on your application.
You can easily download Composer by checking [the official Composer website](http://getcomposer.org/)

Initialization
-----------------

You need to initiate some settings to make sure the bundle is configured properly. 

1. First, register the bundle in the kernel :

    ```php
    <?php
    # app/AppKernel.php
    class AppKernel extends Kernel
    {
        public function registerBundles() {
            $bundles = array(
                // ...
                new Orbitale\Bundle\TranslationBundle\OrbitaleTranslationBundle(),// Registers Orbitale TranslationBundle
    
    ```

2. Update your database to add the new entity :

    ```bash
    php app/console doctrine:schema:update --force
    ```

4. Enable the translator in the framework:

	```yml
	# app/config/config.yml
	framework:
	    translator:      { fallback: "%locale%" } # Un-comment this line to enable the translator
	```

5. (optional) If it's not done already by composer, install the assets with `php app/console assets:install --symlink`

6. Done !

***

[Next: 2. Usage](/Resources/doc/2-usage.md) →