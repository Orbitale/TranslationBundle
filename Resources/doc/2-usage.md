2. Usage
--------

You can use your translator like before, in Twig, controllers or anything.

If you already have translation files, Orbitale's translator will find them and rely on the native 
translator to cache them and retrieve them.

But if the translator does not find any translation, then it will search in the database, and if there is none, it 
will persist a new "empty" translation, for you to know that something has to be translated !

You can manage translations in three different ways :

A. **Using the Orbitale's `TranslationController`**

    You'll just load the internal controller by injecting our routes into your app, and get access to our translation 
    manager. See "Admin Panel" first section for more informations.
    
B. **Connecting to your `SonataAdminBundle` configuration**

    You can add one single configuration value to inject the `TranslationAdmin` service to add a simple manager 
    into your backoffice
    
C. **Raw database management**

    Well, this is the brutal method, but you can simply search in your database with any db-administration system, 
    and modify translations manually. For sanity reasons, I let you write your SQL statements, or load PhpMyAdmin to 
    modify the translations yourself ;) 
    
    
## A: Internal controller

For this you can inject our routing files into your own routing config :

```yml
# app/config/routing.yml
orbitale_translation_front:
    resource: "@OrbitaleTranslationBundle/Resources/config/routing_front.yml"
    prefix:   /

orbitale_translation_admin:
    resource: "@OrbitaleTranslationBundle/Resources/config/routing_admin.yml"
    prefix:   /

```

**Note :** I deliberately wrote the prefix : you should specify them, as your routes are totally different than any 
other routes. They are unique, yours, and they are dedicated to your app. These are your prefixes, enjoy them <3.

Basically, the template is a classical Twitter's Bootstrap v3 and jQuery v2, all bundled in the bundle.

There are four routes usable by the **OrbitaleTranslationBundle** controller :

#### Front
+ **Locale change** :
    `/%prefix%/lang/{locale}` , A simple route that allows the user to change the locale he's using. Obviously, if 
    you have all routes beginning with the locale, for example `http://www.mysite.com/{locale}/action/param/...`, 
    this route is unnecessary, but it's still here to help you getting rid of the many checks, as this bundle uses 
    a special listener to check the locale for any request.

#### Admin
+ **Admin panel index** :
    `/%prefix%/translations/` , Shows a list of all elements found in the database, sorted by ***locale*** and 
    ***translation domains***. A tiny count system allows you to directly have a view on how many elements are 
    translated or not. To view directly the health state of the translations, the counts will use badges and have 
    three different colors : red if no element is translated, orange if some elements are translated but not all, 
    and green if all elements are translated.

+ **Admin panel edition** :
    `/%prefix%/translations/{locale}/{domain}` , With a little Javascript, it can save your translations in the 
    database. You can save the datas with an AJAX request and a little tooltip will appear showing you if the 
    translation has succeded.

+ **Admin panel extraction** :
    `/%prefix%/translations/export/{locale}` , *(Locale is optional)* This route allows the user to extract 
    translations into files, in YML format, in a configurable output directory.
     **Tip :** This feature is also available in command line.
     **Warning!** This commands overwrite your actual translations if they're located in the 
     `app/Resources/translations` directory ! This bundle cannot (yet ?) merge the existing files with the 
     currently extracted translations.

## B: SonataAdminBundle

If you are using SonataAdminBundle in your Symfony app, then you can add the `TranslationAdmin` class by simply 
adding a configuration parameter :

```yml
# app/config/config.yml
orbitale_translation:
    use_sonata: true
```

The `OrbitaleTranslationExtension` class will then load a `SonataAdmin` service which adds a translation list, and 
allows you to edit your translations directly in your Sonata backoffice.

## C: using the Symfony's WebToolbar and Profiler

Just add this routing for your **dev** environment:

```yml
# app/config/routing_dev.yml
orbitale_translation_profiler:
    resource: "@OrbitaleTranslationBundle/Resources/config/routing_profiler.yml"
```

Make sure the web debug toolbar is enabled in `config_dev.yml`

```yml
# app/config/config_dev.yml
web_profiler:
    toolbar: true
```

And you'll see a little Orbitale icon in your web debug toolar as long as the Orbitale Translator has to translate 
a message from the database!

If you click on this icon and go to the SymfonyProfiler panel, a new "Translations" tab will appear, allowing you 
to use the same behavior as the internal controller to save your translations.

But the real advantage is that all translations you see in the profiler are the ones triggered only by the current 
request! This way, you REALLY know the translation context! 

***

[Next: 3. Reference](/Resources/doc/3-reference.md) →
