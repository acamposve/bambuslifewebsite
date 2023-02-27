<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Psr\Log\LoggerInterface;
use App\Service\HubClient;
use App\Service\NgageClient;
use App\Utils\Settings;


class DefaultController extends AbstractController
{
    private $hubClient;

    public function __construct(HubClient $hubClient, NgageClient $ngageClient, LoggerInterface $logger){
      $this->hubClient = $hubClient;
      $this->ngageClient = $ngageClient;
      $this->logger = $logger;
    }

    public function indexGlobal(Request $request){
        // Check user last preference
        if ($request->cookies->has('bl_loc')) {
            return $this->redirectToRoute('index', array('_locale' => $request->cookies->get('bl_loc')));
        } else {
            // Get country using the IP
            $res = $this->hubClient->getUserCountry();
            if ($res['status_code'] != 200) {
                $this->logger->error("DefaultController | indexGlobal | Error en hubClient.getUserCountry() | (" . $res['status_code'] . " | " . json_encode($res['data']) . ")");
            } else {
                $country = strtolower($res['data']['country_code']);
                $this->logger->info("DefaultController | indexGlobal | Resolved. | (" . $res['status_code'] . " | " . json_encode($res['data']) . ")");
                if (!array_key_exists($country, Settings::countries)) {
                    $this->logger->info("DefaultController | indexGlobal | Country not found in the list of countries. Will default to UY.");
                    $country = 'uy';
                };

                return $this->changeCountry($request, $country);
            }
        }
        // Otherwise show default
        return $this->render('index.html.twig');
    }

    public function index(Request $request, $_locale){

        // SESSION TESTS FROM:
        //  - https://stackoverflow.com/questions/16753631/getting-session-id-with-session-getid-returns-an-empty-result-in-symfony2
        //  - https://stackoverflow.com/questions/8366780/session-variable-missing-when-redirecting-from-other-website

        // $session = $this->get('session');
        // $session->setId('9dmf7315r45a5ojketirptpub8');
        // $session->start();

        // $sessionId = $session->getId();

        // $test_counter = $session->get('test_counter');
        // if ($test_counter == NULL) $test_counter=0;
        // $test_counter++;
        // $session->set('test_counter', $test_counter);

        //return $this->render('index.html.twig', array('test_counter'=>$test_counter, 'ajo'=>$sessionId));
        return $this->render('index.html.twig');
    }

    public function chooseCountry(Request $request){
        $lastUrl = $request->query->get('r');
        if ($lastUrl == NULL) {
            $lastUrl = '';
        } else {
            $lastUrl = '?r=' . $lastUrl;
        }
        return $this->render('chooseCountry.html.twig', [
            'lastUrl' => $lastUrl
        ]);
    }

    public function changeCountry(Request $request, $_selLocal){
        // Check Redirection
        $lastUrl = $request->query->get('r');
        if ($lastUrl == NULL) {
            $lastUrl = '/';
        }

        // Check selection
        if ($_selLocal == NULL) {
            return $this->redirectToRoute('listLocal', array('r' => $lastUrl));
        }
        // Check Redirect
        $router = $this->get('router');
        $routeName = $router->match($lastUrl)['_route'];
        //$route = $this->get('router')->getRouteCollection()->get($routeName);

        $response = $this->redirectToRoute($routeName, array('_locale' => $_selLocal));

        // Save preference
        $response->headers->setCookie(Cookie::create('bl_loc', $_selLocal, time() + (10 * 365 * 24 * 60 * 60)));

        $request->setLocale($_selLocal);

        return $response;
    }

    public function resellers(Request $request){
        return $this->render('resellers.html.twig');
    }

    public function pressreleases(Request $request)
    {
        return $this->render('pressreleases/_press.html.twig');
    }

    public function landing_covid(Request $request) {
        return $this->render('landing/landing_covid.html.twig');
    }

    public function landing_miecg7(Request $request) {
        return $this->render('landing/landing_miecg7.html.twig');


    }

    public function landing_opciones(Request $request) {
        return $this->render('landing/opciones.html.twig');
    }

    public function portal_medico(Request $request) {
        return $this->render('landing/landing_portal_medico.html.twig');
    }

    public function landing_casmu(Request $request) {
      return $this->render('landing/landing_casmu.html.twig', ['error'=> NULL, 'flagForm' => 0]);
    }

    public function landing_casmu_post(Request $request) {
      // Build POST request:
      $recaptcha_url      = 'https://www.google.com/recaptcha/api/siteverify';
      $recaptcha_secret   = $_ENV["RECAPTCHA_KEY"];
      $recaptcha_response = $_POST['recaptcha_response'];

      // Make and decode POST request:
      $this->logger->debug("ContactForm | contactForm | Verifying recaptcha...");
      $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
      $this->logger->debug("ContactForm | contactForm | Recaptcha response is" . $recaptcha);
      $recaptcha = json_decode($recaptcha);

      // Take action based on the score returned:
      if ($recaptcha->success && $recaptcha->score >= 0.5) {
        // Verified - send email
        $data    = $request->request->get('form');
        $name    = $request->request->get('name');
        $email   = $request->request->get('email');
        $phone   = $request->request->get('phone');
        $socio   = $request->request->get('nrosocio');
        $this->logger->debug("Casmu | sorteoForm | Start...");
        $cliRes = $this->ngageClient->sendCasmuForm($name, $email, $phone, $socio);
        if (!$cliRes['resolved']){
          return $this->render('landing/landing_casmu.html.twig', ['error'=> NULL, 'flagForm' => 2]);
        }else{
          return $this->render('landing/landing_casmu.html.twig', ['error'=> NULL, 'flagForm' => 1]);
        }
      } else {
        // Not verified - show form error
        return $this->render('landing/landing_casmu.html.twig', ['error'=> NULL, 'flagForm' => 3]);
      }

    }
}