<?php
/*
* This file is part of the OrbitaleTranslationBundle package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Bundle\TranslationBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Orbitale\Bundle\TranslationBundle\Entity\Translation;
use Orbitale\Bundle\TranslationBundle\Repository\TranslationRepository;
use Orbitale\Bundle\TranslationBundle\Translation\Extractor;
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

        // See class OrbitaleTranslationExtension:processLocale to view locale array format
        if (!isset($locales[$locale])) {
            $msg = $translator->trans('Locale not found : "%locale%"', array('%locale%' => $locale));
            throw $this->createNotFoundException($msg);
        }

        // Changes the locale in session
        $request->getSession()->set('_locale', $locale);

        $request->getSession()->getFlashBag()->add('info', $translator->trans('La langue a été modifiée pour : %lang%', array('%lang%' => $translator->trans($locales[$locale]))));

        // Allows redirection with ?redirect= param
        return $this->redirect($request->query->has('redirect') ? $request->query->get('redirect') : $request->getBaseUrl());
    }

    public function adminListAction()
    {
        $translations = $this->getDoctrine()->getManager()->getRepository('OrbitaleTranslationBundle:Translation')->getForAdmin();

        return $this->render('OrbitaleTranslationBundle:Translation:adminList.html.twig', array(
            'layoutToExtend' => $this->container->getParameter('orbitale_translation.admin_layout') ?: $this->container->getParameter('orbitale_translation.default_layout'),
            'translations' => $translations,
        ));
    }

    public function exportAction($locale = null, Request $request)
    {
        /** @var Extractor $extractor */
        $extractor = $this->container->get('orbitale.translation.extractor');

        $done = true;

        $languages = (!$locale || $locale = 'all') ? array_keys($this->container->getParameter('locales')) : null;

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
            $request->getSession()->getFlashBag()->add('success', $this->container->get('translator')->trans('extraction_done', array(), 'orbitale_translation'));
            return $this->redirect($this->generateUrl('orbitale_translation_adminlist'));
        } else {
            throw new \Exception($this->container->get('translator')->trans('error.translation_extract', array(), 'orbitale_translation'));
        }
    }

    public function editAction(Request $request, $locale, $domain)
    {
        /** @var TranslationRepository $transRepo */
        $transRepo = $this->getDoctrine()->getRepository('OrbitaleTranslationBundle:Translation');

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

        $layoutToExtend = $this->container->getParameter('orbitale_translation.admin_layout') ?: $this->container->getParameter('orbitale_translation.default_layout');

        return $this->render('OrbitaleTranslationBundle:Translation:edit.html.twig', array(
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
                return $this->saveTranslation($token, $translation, $em);
            }
        } else {
            $tokens = $post->get('translation');

            /** @var Translation[] $found */
            $found = $transRepo->findByTokens(array_keys($tokens));

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
    private function saveTranslation($token, $translation, $em)
    {
        $message = 'error.';
        $translated = false;

        if (!trim($translation)) {
            $message .= 'no_translation';
        } else {
            /** @var Translation $entity */
            $entity = $em->getRepository('OrbitaleTranslationBundle:Translation')->findOneByToken($token);
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

        $message = $this->get('translator')->trans($message, array(), 'orbitale_translation');

        return new Response(json_encode(array(
            'token' => $token,
            'message' => $message,
            'translated' => $translated,
            'translation' => $translation
        )));
    }
}
