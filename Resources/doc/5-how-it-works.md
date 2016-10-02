5. How it works, behind the scene
---------------------------------

The new translator simply extends Symfony's native translator, to keep using native's powerful translation system, 
just adding it a new abstraction layer : the database.

When you use the native **Twig** filters ( `trans`, `transchoice`, `trans_default_domain` ), when you get the 
translator from the **Services Container** ( `$this->container->get('translator');/*From a controller*/` ), 
whenever you *translate* an expression, Orbitale TranslationBundle's translator service will do several things 
the native one does not do.

1. First, it will **search if the element exists in the Symfony's native translator**.
If it does, then, it just returns it.

2. Else, it will get the **translation domain** asked, if none, use **messages** (exactly like the native translator), 
and will load an internal catalogue, and **check if the source** *(also named "id" in the native translator)* 
**exists in the database** (it will create a specific token based on source, locale and domain, and check token's 
existence).

3. If the token does not exist, then it will **persist a new element in the database, with an empty translation**. 
At this moment, it will be visible in the **translation UI (admin panel)**, and the count number will indicate a 
"missing" translation : x/y , where **x** equals the number of translated elements and **y** equals the total 
number of elements.

4. If the token exists, and if the element is already translated in the database, the translation is returned. If not, 
then the original expression is returned, after parsing the eventual translation parameters.

5. As you may have noticed, Symfony's native translator is called ***at first***. It's simply to use Symfony's powerful 
**cache system**, which saves all translations inside a cached catalogue, to strongly enhance time execution and 
memory saving.
