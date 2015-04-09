[![SensioLabsInsight](https://insight.sensiolabs.com/projects/79130396-6820-46ba-9412-5e3c29429845/mini.png)](https://insight.sensiolabs.com/projects/79130396-6820-46ba-9412-5e3c29429845)
[![Build Status](https://travis-ci.org/Orbitale/TranslationBundle.svg?branch=master)](https://travis-ci.org/Orbitale/TranslationBundle)
[![Coverage Status](https://coveralls.io/repos/Orbitale/TranslationBundle/badge.svg?branch=master)](https://coveralls.io/r/Orbitale/TranslationBundle?branch=master)

Orbitale TranslationBundle
==================

Adds new features to native Symfony translator, without replacing it.

Creates translation files, format filenames with the translation domains names, but with more powerful support of all translations.


Index
-------------------------

* [Installation](#installation)
* [Initialization](#initialization)
* [Usage](#usage)
* [Managing translations in the database](#managing-translations-in-the-database)
  * [Native admin backend](#native)
  * [SonataAdminBundle](#sonata)
  * [EasyAdmin](#easyadmin)
  * [Raw database use](#raw)
* [Default configuration reference](#default-configuration-reference)
  * [Locales](#locales)
* [Dump the translations into files](#dump)

Installation
-------------------------

**With Composer**

Just write this command line instruction if Composer is installed in your Symfony root directory :

```sh
composer require orbitale/translation-bundle
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
                new Orbitale\Bundle\TranslationBundle\OrbitaleTranslationBundle(),
    
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

Usage
-------------------------

You can use your translator like before, in Twig, controllers or anything.

If you already have translation files, Orbitale's translator will find them and rely on the native translator to cache them and retrieve them.

But if the translator does not find any translation, then it will search in the database, and if there is none, it will persist a new "empty" translation, for you to know that something has to be translated !

Managing translations in the database
-------------------------

You can manage translations in five different ways :

1. <a name="native"></a> **Using the native Orbitale TranslationController**

    You can, of course, choose another solution, but this one is the **best** because it offers a properly dedicated translation interface.

    You'll just load the internal controller by injecting our routes into your app, and get access to our translation manager. See "Admin Panel" first section for more informations.
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
    **Note :** I deliberately wrote the prefix : you should specify them, as your routes are totally different than any other routes. They are unique, yours, and they are dedicated to your app. These are your prefixes, enjoy them <3.
    
    Basically, the template is a classical Twitter's Bootstrap v3 and jQuery v2, all bundled in the bundle.
    
    There are four routes usable by the **OrbitaleTranslationBundle** controller :
    
    ##### Front
    + **Locale change** :
        `/%prefix%/lang/{locale}` , A simple route that allows the user to change the locale he's using. Obviously, if you have all routes beginning with the locale, for example `http://www.mysite.com/{locale}/action/param/...`, this route is unnecessary, but it's still here to help you getting rid of the many checks, as this bundle uses a special listener to check the locale for any request.
    
    ##### Admin
    + **Admin panel index** :
        `/%prefix%/translations/` , Shows a list of all elements found in the database, sorted by ***locale*** and ***translation domains***. A tiny count system allows you to directly have a view on how many elements are translated or not. To view directly the health state of the translations, the counts will use badges and have three different colors : red if no element is translated, orange if some elements are translated but not all, and green if all elements are translated.
    
    + **Admin panel edition** :
        `/%prefix%/translations/{locale}/{domain}` , With a little Javascript, it can save your translations in the database. You can save the datas with an AJAX request and a little tooltip will appear showing you if the translation has succeded.
    
    + **Admin panel extraction** :
        `/%prefix%/translations/export/{locale}` , *(Locale is optional)* This route allows the user to extract translations into files, in YML format, in a configurable output directory.
         **Tip :** This feature is also available in command line.
         **Warning!** This commands overwrite your actual translations if they're located in the `app/Resources/translations` directory ! This bundle cannot (yet ?) merge the existing files with the currently extracted translations.
    
2. <a name="sonata"></a> **Connecting to your [SonataAdminBundle](https://sonata-project.org/bundles/admin/master/doc/index.html) configuration**

    If you are using SonataAdminBundle in your Symfony app, then you can add the `TranslationAdmin` class by simply adding a configuration parameter:

    ```yaml
    # app/config/config.yml
    orbitale_translation:
        admin_backend: sonata
    ```

3. <a name="easyadmin"></a> **Connecting to your [EasyAdmin](https://github.com/javiereguiluz/EasyAdminBundle) configuration**

    1. First method: by using the native **TranslationController** AND adding this configuration:
    
        ```yaml
        # app/config/config.yml
        orbitale_translation:
            admin_backend: easyadmin
        ```

        By going to the `/%prefix%/translations` page (like in the [native admin panel](#admin) ), you'll see that the controller will be wrapped inside EasyAdmin's layout,
        avoiding you to override any controller!

        For now, you cannot add a menu item in EasyAdmin for this interface (it will then be "invisible" unless you type the url in your browser).
        But be sure that once EasyAdmin implements a solution for adding a menu item in the interface, you'll be warned ;) .
    
    2. Second method: by adding this configuration directly under the `easy_admin` parameter:
    
        ```yaml
        # app/config/config.yml
        easy_admin:
            Translation:
                group: "Blog"
                class: Orbitale\Bundle\TranslationBundle\Entity\Translation
                list:
                    fields: [ id, source, translation, locale, domain, notes ]
                form:
                    fields: [ id, { property: source, type: text, help: "Do not modify this value or this translation becomes corrupted!" }, translation, locale, domain, notes ]
        ```
        
        This will create a new "Translation" interface directly in EasyAdmin, where you'll be able to list/edit/create translations.
    
4. <a name="raw"></a> **Raw database management**

    Well, this is the brutal method, but you can simply search in your database with any db-administration system, and modify translations manually.
    For sanity reasons, I let you write your SQL statements, or load PhpMyAdmin to modify the translations yourself ;) 


Default configuration reference
-------------------------

```yaml
orbitale_translation:
    # View default_locales.yml to view all 135 supported locales !
    # By default, it will only use the %locale% parameter
    locales: ~ 
    
    # Which back-end you are using to manage your translations
    # Available values are the following: native (or null), easyadmin, sonata
    admin_backend: ~
    
    # This is the layout that will be extended by all internal administration views (list and edition)
    # Note that this layout is overriden when using a back-end configuration different to "native"
    admin_layout: OrbitaleTranslationBundle:Translation:_layout.html.twig 
    
    # This is the directory where the extracted translations will be saved, when using the translation UI or the command-line-interface.
    extractor_output_directory: %kernel.root_dir%/Resources/translations/
```

##### Locales

Example :

```yml
# app/config/config.yml
orbitale_translations:
    locales: ~
```

By default, about 135 locales are supported. The ideal configuration for this bundle is to use the shortest syntax for locale name, for example `fr` instead of `fr_FR`, and `en` instead of `en_US` or `en_UK`.

**The default value for this parameter is automatically set to `%locale%` if you have set it in your application parameters.**

**To view supported locales, see the [default_locales.yml](Resources/config/default_locales.yml) file.**

**Specifying the `locales` parameter in your config file totally _overrides_ the default locales.**

If you want to use more locales, you have three ways of doing it :

1. `locales: {"fr": "Français", "en": "English"}`
This is the basic way, and _it is the only way to add languages that are not supported by this bundle_. But if a locale is not supported, you'd better make a pull-request or open an issue for it !

2. `locales: ["fr", "en"]`
This is basically a way to use only 2 locales in your website. With the 3rd way, it allows you to use the powerful `$translator->getLangs()` method, which returns all used locales. If you were not using this method, this method would then return all 135 locales. It's useful to make a dynamic "Change language" menu : you put your languages in the configuration, then you get them from the translator, and you have both the locale and the public language name ! (in english). This is great for a dropdown menu, for example !

3. `locales: "fr,en,de,es"`
With the 2nd method, it's the shortest way. The languages will only be splitted into an array and the extension will find them 


##### <a name="dump"></a> Dump the translations, extract them into files

If you want to **extract the translations from the database to files**, you can do it in two ways :

1. Use the internal controller. If you configured the internal routing parameters, you can use the translation UI and click the "extract" link to call a specific service. A locale is not necessary, as it can save all translation from all locales. **It will only save translated messages**.
2. Use the command line tool, by running `orbitale:translation:extract` command. With this tool, you can specify the locale, but also some more options, like the `--output-directory` (default `app/Resources/translations/`), the `--output-format` (default "yml", but you can use any format provided by Symfony's native translator, as I use the native service to export translations), and two other notions : *dirty* and *keeping files*.

A *dirty* translation is a **blank translation**, where the source, locale and domain are specified, and not the translation. If you specify the `--dirty` option to the command line tool, every blank translation will be saved in the files. Normally, they are not, as it's really not useful, and if you translate an element in the database but do not extract database into files, the expression will still be untranslated until you extract files.

When you specify the `--keep-files` option, it will only write non-existing files. It means you can save only "new translation domains" if you have new ones.


How it works, behind
-------------------------

The new translator simply extends Symfony's native translator, to keep using native's powerful translation system, just adding it a new abstraction layer : the database.

When you use the native **Twig** filters ( `trans`, `transchoice`, `trans_default_domain` ), when you get the translator from the **Services Container** ( `$this->container->get('translator');/*From a controller*/` ), whenever you *translate* an expression, Orbitale TranslationBundle's translator service will do several things the native one does not do.

1. First, it will **search if the element exists in the Symfony's native translator**.
If it does, then, it just returns it.

2. Else, it will get the **translation domain** asked, if none, use `messages` (exactly like the native translator), and will load an internal catalogue, and **check if the source** *(also named "id" in the native translator)* **exists in the database** (it will create a specific token based on source, locale and domain, and check token's existence).

3. If the token does not exist, then it will **persist a new element in the database, with an empty translation**. At this moment, it will be visible in the **translation UI (admin panel)**, and the count number will indicate a "missing" translation : x/y , where **x** equals the number of translated elements and **y** equals the total number of elements.

4. If the token exists, and if the element is already translated in the database, the translation is returned. If not, then the original expression is returned, after parsing the eventual translation parameters.

5. As you may have noticed, Symfony's native translator is called ***at first***. It's simply to use Symfony's powerful **cache system**, which saves all translations inside a cached catalogue, to strongly enhance time execution and memory saving.
