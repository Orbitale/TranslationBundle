Pierstoval TranslationBundle
==================

Adds new features to native Symfony translator, without replacing it.

Creates translation files, format filenames with the translation domains names, but with more powerful support of all translations.

Installation
-------------------------

**With Composer**

Just write this command line instruction if Composer is installed in your Symfony root directory :

```bash
php composer.phar require pierstoval/translation-bundle dev-master
```

I recommend to use Composer, as it is the best way to keep this repository update on your application.
You can easily download Composer by checking [the official Composer website](http://getcomposer.org/)

Initialization
-------------------------

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
                new Pierstoval\Bundle\TranslationBundle\PierstovalTranslationBundle(),// Registers Pierstoval TranslationBundle
    
    ```

2. Update your database to add the new entity :

    ```bash
    php app/console doctrine:schema:update --force
    ```
    
    Or if you are using Symfony3 directory structure :

    ```bash
    php bin/console doctrine:schema:update --force
    ```

3. Done !

Usage
-------------------------

You can use your translator like before, in Twig, controllers or anything.

If you already have translation files, Pierstoval's translator will find them and rely on the native translator to cache them and retrieve them.

But if the translator does not find any translation, then it will search in the database, and if there is none, it will persist a new "empty" translation, for you to know that something has to be translated !

Managing translations in the database
-------------------------

You can manage translations in three different ways :

1. **Using the Pierstoval's `TranslationController`**

    You'll just load the internal controller by injecting our routes into your app, and get access to our translation manager.
    
2. **Connecting to your `SonataAdminBundle` configuration**

    You can add one single configuration value to inject the `TranslationAdmin` service to add a simple manager into your backoffice
    
3. **Raw database management**

    Well, this is the brutal method, but you can simply search in your database with any db-administration system, and modify translations manually. For sanity reasons, I let you write your SQL statements, or load PhpMyAdmin to modify the translations yourself ;) 


Admin Panel
-------------------------

## First solution : internal controller

For this you can inject our routing files into your own routing config :

```yml
# app/config/routing.yml
pierstoval_translation_front:
    resource: "@PierstovalTranslationBundle/Resources/config/routing_front.yml"
    prefix:   /

pierstoval_translation_admin:
    resource: "@PierstovalTranslationBundle/Resources/config/routing_admin.yml"
    prefix:   /
    
```

**Note :** I deliberately wrote the prefix : you should specify them, as your routes are totally different than any other routes. They are unique, yours, and they are dedicated to your app. These are your prefixes, enjoy them <3.

Basically, the template is a classical Twitter's Bootstrap v3 and jQuery v2, all bundled in the bundle.

There are four routes usable by the **PierstovalTranslationBundle** controller :

#### Front
+ **Locale change** :
    `/%prefix%/lang/{locale}` , A simple route that allows the user to change the locale he's using. Obviously, if you have all routes beginning with the locale, for example `http://www.mysite.com/{locale}/action/param/...`, this route is unnecessary, but it's still here to help you getting rid of the many checks, as this bundle uses a special listener to check the locale for any request.

#### Admin
+ **Admin panel index** :
    `/%prefix%/translations/` , Shows a list of all elements found in the database, sorted by ***locale*** and ***translation domains***. A tiny count system allows you to directly have a view on how many elements are translated or not. If **Twitter's Bootstrap** is enabled, the counts will use badges and have three different colors : red if no element is translated, orange if some elements are translated but not all, and green if all elements are translated.

+ **Admin panel edition** :
    `/%prefix%/translations/{locale}/{domain}` , With a little Javascript, it can save your translations in the database. If **jQuery** is enabled, you can save the datas with an AJAX request ; and if **Twitter's Bootstrap** is enabled, a little javascript tooltip will appear showing you if the translation has succeded.

+ **Admin panel extraction** :
    `/%prefix%/translations/export/{locale}` , *(Locale is optional)* This route allows the user to extract translations into files, in YML format, in a configurable output directory.
     **Tip :** This feature is also available in command line.

## Second solution : SonataAdminBundle

If you are using SonataAdminBundle in your Symfony app, then you can add the `TranslationAdmin` class by simply adding a configuration parameter :

```yml
# app/config/config.yml
pierstoval_translation:
    use_sonata: true
```

The `PierstovalTranslationExtension` class will then load a `SonataAdmin` service which adds a translation list, and allows you to edit your translations directly in your Sonata backoffice.


Configuration reference
-------------------------

```yml
pierstoval_translation:
    # View default_locales.yml to view all 135 supported locales !
    locales: ~ 
    
    # Whether you want to use the Admin class with SonataAdminBundle
    use_sonata: false 
    
    # This is the layout that will be extended by all internal administration views (list and edition)
    admin_layout: PierstovalTranslationBundle:Translation:_layout.html.twig 
    
    # This is the directory where the extracted translations will be saved, when using the translation UI or the command-line-interface.
    output_directory: %kernel.root_dir%/Resources/translations/
```

##### Locales :
By default, about 135 locales are supported. The ideal configuration for this bundle is to use the shortest syntax for locale name, for example `fr` instead of `fr_FR`, and `en` instead of `en_US` or `en_UK`.

**To view supported locales, see the [default_locales.yml](src/Pierstoval/Bundle/TranslationBundle/Resources/config/default_locales.yml) file.**

**Specifying the `locales` parameter in your config file totally _overrides_ the default locales.**

If you want to use more locales, you have three ways of doing it :

1. `locales: {"fr": "Français", "en": "English"}`
This is the basic way, and _it is the only way to add languages that are not supported by this bundle_. But if a locale is not supported, you'd better make a pull-request or open an issue for it !

2. `locales: ["fr", "en"]`
This is basically a way to use only 2 locales in your website. This use is probably one of the best, with the 3rd way, because you just "truncate" the 135 locales into the ones you only want to use, and it allows you to use the powerful `Translator->getLangs()` method, which returns all used locales. If you were not using this method, this method would then return all 135 locales. It's useful to make a dynamic "Change language" menu : you put your languages in the configuration, then you get them from the translator, and you have both the locale and the public language name ! (in english). This is great for a dropdown menu, for example !

3. `locales: "fr,en,de,es"`
With the 2nd method, it's the shortest way. The languages will only be splitted into an array and the extension will find them 


##### Dump the translations, extract them into files

If you want to **extract the translations from the database to files**, you can do it in two ways :

1. Use the internal controller. If you configured the internal routing parameters, you can use the translation UI and click the "extract" link to call a specific service. A locale is not necessary, as it can save all translation from all locales. **It will only save translated messages**.
2. Use the command line tool, by running `pierstoval:translation:extract` command. With this tool, you can specify the locale, but also some more options, like the `--output-directory` (default `app/Resources/translations/`), the `--output-format` (default "yml", but you can use any format provided by Symfony's native translator, as I use the native service to export translations), and two other notions : *dirty* and *keeping files*.

A *dirty* translation is a **blank translation**, where the source, locale and domain are specified, and not the translation. If you specify the `--dirty` option to the command line tool, every blank translation will be saved in the files. Normally, they are not, as it's really not useful, and if you translate an element in the database but do not extract database into files, the expression will still be untranslated until you extract files.

When you specify the `--keep-files` option, it will only write non-existing files. It means you can save only "new translation domains" if you have new ones.


How it works, behind
-------------------------

The new translator simply extends Symfony's native translator, to keep using native's powerful translation system, just adding it a new abstraction layer : the database.

When you use the native **Twig** filters ( `trans`, `transchoice`, `trans_default_domain` ), when you get the translator from the **Services Container** ( `$this->container->get('translator');/*From a controller*/` ), whenever you *translate* an expression, Pierstoval TranslationBundle's translator service will do several things the native one does not do.

1. First, it will **search if the element exists in the Symfony's native translator**.
If it does, then, it just returns it.

2. Else, it will get the **translation domain** asked, if none, use **messages** (exactly like the native translator), and will load an internal catalogue, and **check if the source** *(also named "id" in the native translator)* **exists in the database** (it will create a specific token based on source, locale and domain, and check token's existence).

3. If the token does not exist, then it will **persist a new element in the database, with an empty translation**. At this moment, it will be visible in the **translation UI (admin panel)**, and the count number will indicate a "missing" translation : x/y , where **x** equals the number of translated elements and **y** equals the total number of elements.

4. If the token exists, and if the element is already translated in the database, the translation is returned. If not, then the original expression is returned, after parsing the eventual translation parameters.

5. As you may have noticed, Symfony's native translator is called ***at first***. It's simply to use Symfony's powerful **cache system**, which saves all translations inside a cached catalogue, to strongly enhance time execution and memory saving.

Translations UI template
-------------------------

The UI template is really not beautiful. In fact, it's completely UGLY. But it is logical : the way I will design it will be different than the way YOU will design it, so in my opinion, there is no need to make a perfect design for this UI, especially if you want it to be directly integrated to your own backoffice system. That's why I only put a basic Twitter's Bootstrap + jQuery template.

So I made "template override" possible and easy for anyone, by adding a simple twig inheritance facility.

To do this, simply change this parameter in your config file :

```yml
# app/config/config.yml
pierstoval_translations:
    admin_layout: AcmeDemoBundle:Demo:translation_layout.html.twig
```

You can choose ANY layout, even your base layout, but you must add at least three blocks in your Twig template :
```twig
{# AcmeDemoBundle:Demo:translation_layout.html.twig #}
{% block translation_stylesheets %}{% endblock %}

{% block translation_admin_wrapper %}
    {% block translation_admin_body %}{% endblock %}
{% endblock %}
{% block translation_javascripts %}{% endblock %}
```

You can put these blocks anywhere, and when the controller will render the template for the `adminList` or the `edit` route, the template will extend your own layout (that's why these blocks are important). 

You can put ANYTHING outside this inclusion, so you can render your own menus, extend your own layout, etc., this is important when you developed your own backoffice, for example.

**Bonus :**

* If you have included **jQuery** to your layout, a "Save" button will appear, allowing you to save only one translation at a time with an AJAX request. This is useful when you have to translate only one or two missing elements.

* If you have included **Twitter's Bootstrap** (v3) CSS framework to your layout, the buttons will use the bootstrap's *btn btn-default* class, and the admin index will show colored and *bedged* counts for translated/total elements.

* If you have included **Twitter's Bootstrap** (v3) JS *Tooltip* feature, as jQuery is mandatory to bootstrap, the "Save" button will activate a tooltip next to the textarea containing the translation, with an information about the saving process (successful or not) after the AJAX request.

Conclusion
-------------------------

You can also view this repository [on its Packagist.org page](https://packagist.org/packages/pierstoval/translation-bundle), even though it's not really useful to see.

Feel free to send me a mail at pierstoval@gmail.com if you have any question !! (I LOVE questions, really, feel free to ask !)
 
If you find this bundle to be cool, feel free to propose improvements and send pull-requests !

Thanks for reading and using !

Pierstoval.
