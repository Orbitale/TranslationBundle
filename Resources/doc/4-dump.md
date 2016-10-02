4. Dump the translations
------------------------

If you want to **extract the translations from the database to files**, you can do it in two ways :

+   Use the internal controller. If you configured the internal routing parameters, you can use the translation 
    UI and click the "extract" link to call a specific service. A locale is not necessary, as it can save all 
    translation from all locales. **It will only save translated messages**.

+   Use the command line tool, by running `orbitale:translation:extract` command. With this tool, you can specify 
    the locale, but also some more options, like the `--output-directory` (default `app/Resources/translations/`), 
    the `--output-format` (default "yml", but you can use any format provided by Symfony's native translator, as I 
    use the native service to export translations), and two other notions : *dirty* and *keeping files*.

A *dirty* translation is a **blank translation**, where the source, locale and domain are specified, and not the 
translation. If you specify the `--dirty` option to the command line tool, every blank translation will be saved 
in the files. Normally, they are not, as it's really not useful, and if you translate an element in the database 
but do not extract database into files, the expression will still be untranslated until you extract files.

When you specify the `--keep-files` option, it will only write non-existing files. It means you can save only 
"new translation domains" if you have new ones.

***

[Next: 5. How it works](/Resources/doc/5-how-it-works.md) →
