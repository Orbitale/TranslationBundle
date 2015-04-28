3. Configuration reference
--------------------------

```yaml
orbitale_translation:
    # View default_locales.yml to view all 135 supported locales !
    # By default, it will only use the %locale% parameter
    locales: ~ 
    
    # Whether you want to use the Admin class with SonataAdminBundle
    use_sonata: false 
    
    # This is the layout that will be extended by all internal administration views (list and edition)
    admin_layout: OrbitaleTranslationBundle:Translation:_layout.html.twig 
    
    # This is the directory where the extracted translations will be saved, when using the translation UI or the command-line-interface.
    output_directory: %kernel.root_dir%/Resources/translations/
```


## Basic configuration:
No minimum configuration is needed.
```yml
# app/config/config.yml
orbitale_translations:
    locales: ~
```

By default, about 135 locales are supported. The ideal configuration for this bundle is to use the shortest 
syntax for locale name, for example `fr` instead of `fr_FR`, and `en` instead of `en_US` or `en_UK`.

The default value for this parameter is automatically set to `%locale%` if you have set it in your application 
parameters. To view supported locales, see the [default_locales.yml](Resources/config/default_locales.yml) file.

**Specifying the `locales` parameter in your config file totally _overrides_ the default locales.**

## Adding more locales:
If you want to use more locales, you have three ways of doing it :

1. `locales: {"fr": "Français", "en": "English"}`
This is the basic way, and _it is the only way to add languages that are not supported by this bundle_. But if a 
locale is not supported, you'd better make a pull-request or open an issue for it !

2. `locales: ["fr", "en"]`
This is basically a way to use only 2 locales in your website. With the 3rd way, it allows you to use the 
powerful `$translator->getLangs()` method, which returns all used locales. If you were not using this method, 
this method would then return all 135 locales. It's useful to make a dynamic "Change language" menu : you put your 
languages in the configuration, then you get them from the translator, and you have both the locale and the public 
language name ! (in english). This is great for a dropdown menu, for example !

3. `locales: "fr,en,de,es"`
With the 2nd method, it's the shortest way. The languages will only be splitted into an array and the extension 
will find them 

***

[Next: 4. Dump translations](/Resources/doc/4-dump.md) →
