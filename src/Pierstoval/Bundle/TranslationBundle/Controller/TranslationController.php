<?php

namespace Pierstoval\Bundle\TranslationBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Pierstoval\Bundle\TranslationBundle\Entity\Translation;
use Pierstoval\Bundle\TranslationBundle\Repository\TranslationRepository;
use Pierstoval\Bundle\TranslationBundle\Translation\Extractor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslationController extends Controller
{

    /**
     * @param $locale
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changeLangAction($locale, Request $request)
    {
        $translator = $this->get('translator');

        // Retrieves locales
        $locales = $this->container->getParameter('locales');

        // See class PierstovalTranslationExtension:processLocale to view locale array format
        if (!isset($locales[$locale])) {
            $msg = $translator->trans('Locale not found : "%locale%"', array('%locale%' => $locale));
            throw $this->createNotFoundException($msg);
        }

        // Gets public name
        $localeName = $translator->trans($locales[$locale]);

        // Changes the locale in session
        $request->getSession()->set('_locale', $locale);

        $msg = $translator->trans('La langue a été modifiée pour : %lang%', array('%lang%' => $localeName));
        $request->getSession()->getFlashBag()->add('info', $msg);

        // Allows redirection with ?redirect= param
        $redirect = $request->query->has('redirect') ? $request->query->get('redirect') : $request->getBaseUrl();

        return $this->redirect($redirect);
    }

    public function adminListAction()
    {
        /** @var Translation[] $list */
        $list = $this->getDoctrine()->getManager()->getRepository('PierstovalTranslationBundle:Translation')->findAll();

        $translations = array();
        foreach ($list as $translation) {
            $locale = $translation->getLocale();
            $domain = $translation->getDomain();

            if (!isset($translations[$locale][$domain])) {
                $translations[$locale][$domain] = array(
                    'count' => 0,
                    'total' => 0,
                );
            }
            if ($translation->getTranslation()) {
                $translations[$locale][$domain]['count']++;
            }

            if ($translations[$locale][$domain]['count'] && $translations[$locale][$domain]['count'] < $translations[$locale][$domain]['total']) {
            } elseif ($translations[$locale][$domain]['count'] && $translations[$locale][$domain]['count'] === $translations[$locale][$domain]['total']) {
            }

            $translations[$locale][$domain]['total']++;
            ksort($translations[$locale]);
        }
        ksort($translations);

        $layoutToExtend = $this->container->getParameter('pierstoval_translation.admin_layout') ?: $this->container->getParameter('pierstoval_translation.default_layout');

        return $this->render('PierstovalTranslationBundle:Translation:adminList.html.twig', array(
            'layoutToExtend' => $layoutToExtend,
            'translations' => $translations,
        ));
    }

    public function exportAction($locale = null, Request $request)
    {
        /** @var Extractor $extractor */
        $extractor = $this->container->get('pierstoval.translation.extractor');

        $done = true;

        $languages = null;
        if (!$locale || $locale = 'all') {
            $languages = array_keys($this->container->getParameter('locales'));
        }

        if ($languages) {
            foreach ($languages as $locale) {
                if (!$extractor->extract($locale)) {
                    $done = false;
                }
            }
        } else {
            $done = $extractor->extract($locale) ? true : false;
        }

        if ($done) {
            $msg = $this->container->get('translator')->trans('extraction_done', array(), 'pierstoval_translation');
            $request->getSession()->getFlashBag()->add('success', $msg);
            return $this->redirect($this->generateUrl('pierstoval_translation_adminlist'));
        } else {
            //Une erreur inconnue est survenue dans l\'extractions des traductions...
            $msg = $this->container->get('translator')->trans('error.translation_extract', array(), 'pierstoval_translation');
            throw new \Exception($msg);
        }
    }

    public function editAction(Request $request, $locale, $domain)
    {
        /** @var TranslationRepository $transRepo */
        $transRepo = $this->getDoctrine()->getRepository('PierstovalTranslationBundle:Translation');

        $nb = null;
        if ($request->isMethod('POST')) {
            $return = $this->doEdit($request, $transRepo);
            if (is_object($return)) {
                return $return;
            } elseif (is_int($return)) {
                $nb = $return;
            }
        }

        $translations = $transRepo->findLikes($locale, $domain);

        $locales = $this->container->getParameter('locales');
        $lang = isset($locales[$locale]) ? $locales[$locale] : null;

        if (!$lang) {
            throw $this->createNotFoundException('Unsupported locale "'.$locale.'".');
        }

        $layoutToExtend = $this->container->getParameter('pierstoval_translation.admin_layout') ?: $this->container->getParameter('pierstoval_translation.default_layout');

        return $this->render('PierstovalTranslationBundle:Translation:edit.html.twig', array(
            'layoutToExtend' => $layoutToExtend,
            'nb_translated' => $nb,
            'translations' => $translations,
            'locale' => $locale,
            'lang' => $lang,
            'domain' => $domain,
        ));
    }

    /**
     * Edits the translation.
     * If request is AJAX, returns a response
     *
     * @param Request $request
     * @param TranslationRepository $transRepo
     * @return bool
     */
    private function doEdit(Request $request, TranslationRepository $transRepo)
    {
        $nb = 0;

        $em = $this->getDoctrine()->getManager();
        $post = $request->request;
        if ($request->isXmlHttpRequest()) {
            $token = $post->get('token');
            $translation = $post->get('translation');
            $check_translations = $post->get('check_translation');
            if ($token && $translation && !$check_translations) {
                return $this->_saveTranslation($token, $translation, $em);
            }
        } else {
            $tokens = $post->get('translation');
            $keys = array_keys($tokens);

            /** @var Translation[] $found */
            $found = $transRepo->findByTokens($keys);

            foreach ($found as $translation) {
                if (
                    isset($tokens[$translation->getToken()])
                    && $tokens[$translation->getToken()] !== $translation->getTranslation()
                    && $tokens[$translation->getToken()]
                ) {
                    $translation->setTranslation($tokens[$translation->getToken()]);
                    $em->persist($translation);
                    $nb++;
                }
            }

            if ($nb) {
                $em->flush();
            }

        }

        return $nb;
    }


    /**
     * Saves a translation if it exists. Mostly used in AJAX.
     *
     * @param $token
     * @param $translation
     * @param ObjectManager $em
     * @return Response
     */
    private function _saveTranslation($token, $translation, $em)
    {
        $message = 'error.';
        $translated = false;

        if (!trim($translation)) {
            $message .= 'no_translation';
        } else {
            /** @var Translation $entity */
            $entity = $em->getRepository('PierstovalTranslationBundle:Translation')->findOneByToken($token);
            if (!$entity) {
                $message .= 'no_translation_token';
            } else {
                if (trim($entity->getTranslation()) == trim($translation)) {
                    $translated = true;
                    $message = 'translation_identical';
                } else {
                    $entity->setTranslation($translation);
                    $em->persist($entity);
                    $em->flush();
                    $message = 'translation_done';
                    $translated = true;
                }
            }
        }

        $message = $this->get('translator')->trans($message, array(), 'pierstoval_translation');

        return new Response(json_encode(array(
            'token' => $token,
            'message' => $message,
            'translated' => $translated,
            'translation' => $translation
        )));
    }
}
