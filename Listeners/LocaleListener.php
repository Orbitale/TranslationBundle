<?php

namespace Pierstoval\Bundle\TranslationBundle\Listeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class LocaleListener, created from the "sticky locale in session" documentation page
 *
 * @link http://symfony.com/doc/current/cookbook/session/locale_sticky_session.html
 */
class LocaleListener implements EventSubscriberInterface
{
    private $defaultLocale;

    public function __construct($defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Request has maximum priority in setting locale
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // If no locale is in the route, locale is retrieved from session, or default locale if not already in session.
            $locale = $request->getSession()->get('_locale', $this->defaultLocale);

            // And then we set locale in all environments
            $request->setLocale($locale);
            $request->getSession()->set('_locale', $locale);
            $request->attributes->set('_locale', $locale);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => array(
                array('onKernelRequest', 17)
            ),
        );
    }
}
