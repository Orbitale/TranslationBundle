<?php

namespace Pierstoval\Bundle\TranslationBundle\Controller;

use Pierstoval\Bundle\TranslationBundle\Entity\Languages as Languages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class TranslateController extends Controller {

    /**
     * @Route("/lang/{locale}")
     */
    public function changeLangAction(Languages $lang) {
        //Récupération de la liste des langues disponibles
        $this->get('session')->set('_locale', $lang->getLocale());
        $translator = $this->get('translator');
        $msg = $translator->trans('La langue a été modifiée pour : %lang%', array('%lang%' => $translator->trans($lang->getName())));
        $this->get('session')->getFlashBag()->add('info', $msg);
        return $this->redirect($this->getRequest()->getBaseUrl());
    }

    /**
     * @Route("/admin/translations/")
     * @Template()
     */
    public function adminListAction() {
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
            if ($translation->getTranslation()){
                $translations[$locale][$domain]['count']++;
            }

            if ($translations[$locale][$domain]['count'] && $translations[$locale][$domain]['count'] < $translations[$locale][$domain]['total']) {
            } elseif ($translations[$locale][$domain]['count'] && $translations[$locale][$domain]['count'] === $translations[$locale][$domain]['total']) {
            }

            $translations[$locale][$domain]['total']++;
            ksort($translations[$locale]);
        }
        ksort($translations);

        return array(
            'layoutToExtend' => null,
            'translations'=>$translations,
        );
    }

    /**
     * @Route("/admin/translations/export/{locale}")
     * @Route("/admin/translations/export/")
     */
    public function exportAction($locale = null) {
        $extractor = $this->container->get('pierstoval.translation.extractor');

        $done = 'true';

        $languages = null;
        if (!$locale || $locale = 'all') {
            $languages = $this->getDoctrine()->getManager()->getRepository('PierstovalTranslationBundle:Languages')->findAll();
        }

        if ($languages) {
            foreach ($languages as $language) {
                if (!$extractor->extract($language->getLocale())) {
                    $done = 'false';
                }
            }
        } else {
            $done = $extractor->extract($locale) ? 'true' : 'false';
        }

        if ($done) {
            $this->container->get('session')->getFlashBag()->add('success', 'Extraction des traductions effectuée !');
            return $this->redirect($this->generateUrl('pierstoval_translation_translate_adminlist'));
        } else {
            $msg = $this->container->get('translator')->trans('Une erreur inconnue est survenue dans l\'extractions des traductions...', array(), 'admin.translation');
            throw new \Exception($msg);
        }
    }

    /**
     * @Route("/admin/translations/{locale}/{domain}")
     * @Template()
     */
    public function editAction(Request $request, $locale, $domain) {

        $em = $this->getDoctrine()->getManager();
        $transRepo = $em->getRepository('PierstovalTranslationBundle:Translation');

        $nb = null;
        if ($request->isMethod('POST')) {
            $post = $request->request;
            if ($request->isXmlHttpRequest()) {
                $token = $post->get('token');
                $translation = $post->get('translation');
                $check_translations = $post->get('check_translation');
                if ($token && $translation && !$check_translations) {
                    return $this->_saveTranslation($token, $translation);
                }
            } else {
                $tokens = $post->get('translation');
                $keys = array_keys($tokens);

                $found = $transRepo->findByTokens($keys);

                $nb = 0;
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
        }
        $translations = $transRepo->findBy(array('locale'=>$locale,'domain'=>$domain));

        $lang = $em->getRepository('PierstovalTranslationBundle:Languages')->findOneByLocale($locale);

        if (!$lang) {
            throw $this->createNotFoundException('Language for locale "'.$locale.'" not found. Did you forget to use "pierstoval:translation:language-add" command ?');
        }

        return array(
            'nb_translated' => $nb,
            'translations' => $translations,
            'lang' => $lang,
            'domain' => $domain,
        );
    }

    private function _saveTranslation($token, $translation) {
        $message = 'Une erreur est survenue : ';
        $translated = false;

        if (!trim($translation)) {
            $message .= 'Aucune traduction donnée.';
        } else {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('PierstovalTranslationBundle:Translation')
                ->findOneByToken($token);
            if (!$entity) {
                $message .= 'Aucune traduction trouvée avec ce jeton.';
            } else if (trim($entity->getTranslation()) == trim($translation)) {
                $translated = true;
                $message = 'La traduction est identique, aucun changement n\'a été fait.';
            } else {
                $entity->setTranslation($translation);
                $em->persist($entity);
                $em->flush();
                $message = 'Traduction effectuée !';
                $translated = true;
            }
        }

        $message = $this->get('translator')->trans($message, array(), 'admin.translations');

        return new Response(json_encode(array(
            'token'=>$token,
            'message'=>$message,
            'translated'=>$translated,
            'translation'=>$translation
        )));
    }

}
