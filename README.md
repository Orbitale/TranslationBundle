Pierstoval TranslationBundle
==================

Adds new features to native Symfony2 translator, without replacing it.

Creates translation files, format filenames with the translation domains names, but with more powerful support of all translations.

Installation
-------------------------

**With Composer**

Just write this command line instruction if Composer is installed in your Symfony2 root directory :

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

2. Then, import the resources in `config.yml`

    ```yaml
    # app/config/config.yml
    imports:
        #...
        - { resource: "@PierstovalTranslationBundle/Resources/config/services.xml" }
    ```

3. *(optional, but recommended !)* Initiate the translation UI.
    For this part, you can choose to add routing params either in `routing.yml` or `routing_dev.yml`, it's up to you. I personally recommand to add it in `routing.yml`, as the translations may change from time to time, for example in the database, files, or anything.
    **Tip :** The GUI routes begin with /admin/, so you can set up a specific required role or authentication system in your `security.yml` file to restrict /admin/ area. If you don't like these routes, you can add routes yourself in your routing.yml file, the old routes shall not be erased, but you can either have your own routes to manage your translations.
    
```yaml
    # app/config/routing.yml
    pierstoval_translation:
        # The name does not matter, only the annotations are required.
        resource: "@PierstovalTranslationBundle/Controller/"
        type:     annotation
        prefix:   /
```

Usage
-------------------------

There are four routes usable by the **TranslationBundle** controller :

+ **Admin panel index** :
    `/admin/translations/` , Shows a list of all elements found in the database, sorted by ***locale*** and ***translation domains***. A tiny count system allows you to directly have a view on how many elements are translated or not. If you override the template and use **Twitter's Bootstrap**, the counts will be labeled and have three different colors : red if no element is translated, orange if some elements are translated but not all, and green if all elements are translated.

+ **Admin panel edition** :
    `/admin/translations/{locale}/{domain}` , With a little Javascript, it can save your translations in the database. If you override the template and use **jQuery**, you can save the datas with an AJAX request ; and if you override the template and use **Twitter's Bootstrap**, a little tooltip will appear showing you if the tran- slation has succeded.

+ **Admin panel extraction** :
    `/admin/translations/export/{locale}` , *(Locale is optional)* This route allows the user to extract translations into files, in YML format, in `app/Resources/translations` folder (or another, if you need it, it's configurable). **Tip :** This feature is also available in command line.

+ **Locale change** :
    `/lang/{locale}` , A simple route that allows the user to change the locale he's using. Obviously, if you have all routes beginning with the locale, for example `http://www.mysite.com/{locale}/action/param/...`, this route is unnecessary, but it's still here to help you getting rid of the many checks (session, request...)

Usage
-------------------------

To use all the features provided by this bundle, after you've set up the above parameters, you just have to do nothing ! It's implemented, and if you use the translation UI, you can manage translations and save them in the database, and once you're ready, extract translations in files, and then, Symfony2 will simply load them into its cached catalogues !
If all database translation are specified, Symfony2 will *never* use the database to search for translations, except if it finds a new word which is not translated !
 
To conclude about the usage, you just have to use your translation system as you are already using it ! Just remove any other translation-overrider you have, it's just for security issues.


How it works, behind
-------------------------

The new translator simply extends Symfony2's native translator, to keep using native's powerful translation system, just adding it a new abstraction layer : the database.

When you use the native **Twig** filters ( `trans`, `transchoice`, `trans_default_domain` ), when you get the translator from the **Services Container** ( `$this->container->get('translator');/*From a controller*/` ), whenever you *translate* an expression, Pierstoval TranslationBundle's translator service will do several things the native one does not do.

First, it will **search if the element exists in the Symfony2's native translator**.
If it does, then, it just returns it.

Else, it will get the **translation domain** asked, if none, use **messages** (exactly like the native translator), and will load an internal catalogue, and **check if the source** *(also named "id" in the native translator)* **exists in the database** (it will create a specific token based on source, locale and domain, and check token's existence).

If the token does not exist, then it will **persist a new element in the database, with an empty translation**. At this moment, it will be visible in the **translation UI (admin panel)**, and the count number will indicate a "missing" translation : x/y , where **x** equals the number of translated elements and **y** equals the total number of elements.

If the token exists, and if the element is already translated in the database, the translation is returned. If not, then the original expression is returned, after parsing the eventual translation parameters.

As you may have noticed, Symfony2's native translator is called ***at first***. It's simply to use Symfony2's powerful **cache system**, which saves all translations inside a cached catalogue, to strongly enhance time execution and memory saving.
 
If you want to **extract the translations from the database to files**, you can do it in two ways :

1. Use the controller. If you configured the routing parameters, you can use the translation UI and click the "extract" link to call a specific service. A locale is not necessary, as it can save all translation from all locales.
2. Use the command line tool, by running `pierstoval:translation:extract` command. With this tool, you can specify the locale, but also some more options, like the `--output-directory` (default `app/Resources/translations/`), the `--output-format` (default "yml", but you can use any format provided by Symfony2's native translator, as I use the native service to export translations), and two other notions : *dirty* and *keeping files*.

    A *dirty* translation is a **blank translation**, where the source, locale and domain are specified, and not the translation. If you specify the `--dirty` option to the command line tool, every blank translation will be saved in the files. Normally, they are not, as it's really not useful, and if you translate an element in the database but do not extract database into files, the expression will still be untranslated until you extract files.

    When you specify the `--keep-files` option, it will only write non-existing files. It means you can save only "new translation domains" if you have new ones.

Translations UI template
-------------------------

The UI template is really not beautiful. In fact, it's completely UGLY. But it is logical : the way I will design it will be different than the way YOU will design it, so in my opinion, there is no need to make a perfect design for this UI, especially if you want it to be directly integrated to your own backoffice system.

So I made "template override" possible and easy for anyone, by adding two little files to your own templates.

**Note :** At the moment where I write this, the template inheritance has been tested on a basic Symfony2 raw install, and on my own apps, but there may be some issues in the way you separate your own blocks, for example, javascript blocks.
 
***How to use my own templates for the translation UI ?***

It is really simple, and we'll use the native [Symfony2's Bundle inheritance](http://symfony.com/doc/master/cookbook/bundles/inheritance.html).

Create this directory into your application : `%YourSF2Directory%/app/Resources/PierstovalTranslationBundle/views/Translate/`
 
Then, create two files : `adminList.html.twig` and `edit.html.twig`. 

Once you've done, all you have to do is **include the template which is finally rendered by the controller**.

1. For `adminList.html.twig`, the element you have to include is the following :
    `{% include 'PierstovalTranslationBundle:Translate:override.adminlist.html.twig' %}`
2. And for `edit.html.twig`, the inclusion is this one :
    `{% include 'PierstovalTranslationBundle:Translate:override.edit.html.twig' %}`

You can put ANYTHING outside this inclusion, so you can render your own menus, extend your own layout, etc.

**Bonus :**

* If you have included **jQuery** to your layout, a "Save" button will appear, allowing you to save only one translation at a time with an AJAX request. This is useful when you have to translate only one or two missing elements.

* If you have included **Twitter's Bootstrap** (v3) CSS framework to your layout, the buttons will use the bootstrap's *btn btn-default* class, and the admin index will show colored and labelled counts for translated/total elements.

* If you have included **Twitter's Bootstrap** (v3) JS *Tooltip* feature, as jQuery is mandatory to bootstrap, the "Save" button will activate a tooltip next to the textarea containing the translation, with an information about the saving process (successful or not).

Conclusion
-------------------------

You can also view this repository [on its Packagist.org page](https://packagist.org/packages/pierstoval/translation-bundle), even though it's not really useful to see.

Feel free to send me a mail at pierstoval@gmail.com if you have any question !! (I LOVE questions, really, feel free to ask !)
 
If you find this bundle to be cool, feel free to propose improvements and send pull-requests !

Thanks for reading and using !

Pierstoval.
