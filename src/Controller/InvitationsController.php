<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Service\HubClient;
use App\Service\NgageClient;
use App\Controller\AuthController;
use App\Utils\Settings;

class InvitationsController extends AbstractController {

  private $hubClient;
  private $logger;

  public function __construct(HubClient $hubClient, LoggerInterface $logger, AuthController $authCtrl){
    $this->hubClient = $hubClient;
    $this->logger    = $logger;
    $this->authCtrl  = $authCtrl;
  }

  //
  // Contller Methods
  //

  public function acceptInvitation(Request $request , $tokenId){
    
    $cliRes = $this->hubClient->getInvitationData($tokenId);
    if (!$cliRes['resolved']){
      $this->logger->error("Invitation | acceptInvitation | Error requesting user data!!");
      return $this->render('invitations/acceptInvitation.html.twig', [
        'error_msg' => 'Ocurrió un error cargando la invitación (1)',
        'email' => '',
        'token' => ''
      ]);
    }else{
      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
        $this->logger->error("Invitation | acceptInvitation | Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
        return $this->render('invitations/acceptInvitation.html.twig', [
          'error_msg' => 'Ocurrió un error cargando la invitación (2)',
          'email' => '',
          'token' => ''
        ]);
      }else{
        // print_r($cliRes);
        // die();
        /* 
        Array ( 
          [id] => 17 
          [token] => qebWdak0ir0OWRecQU3U9 
          [createdDate] => 2019-11-14T01:02:26.000Z 
          [ttl] => 48 
          [invitationType] => FAMILY_MEMBER_ADD 
          [status] => PENDING 
          [senderUserId] => 540 
          [recipientName] => Pac 04 
          [recipientEmail] => arondella+paccl04111901@tingelmar.com 
          [invitationData] => {"onwerId":540,"deviceSerialNum":"CL0005"} 
          [lastUsed] => [isExpired] => 1 
          [ownerFirstname] => Pac CL 05111901 
          [ownerLastname] => Rondella 
          [ownerEmail] => arondella+paccl05111901@tingelmar.com ) 
        */
        
        // Load invitation data
        $invitation = $cliRes['data'];
        $invitation['invitationData'] = json_decode($invitation['invitationData']);
        // Check if invited email already exists
        $mailExistsRes = $this->hubClient->checkEmail($invitation['recipientEmail']);
        // print_r($mailExists);
        // die();
        if (!$mailExistsRes['resolved']){
          $this->logger->error("Invitation | acceptInvitation | Error checking email!!");
          return $this->render('invitations/acceptInvitation.html.twig', [
            'error_msg' => 'Ocurrió un error cargando la invitación (3)',
            'email' => '',
            'token' => ''
          ]);
        }else{
          if ($mailExistsRes['status_code'] != 200 && $mailExistsRes['status_code'] != 0){
            $this->logger->error("Invitation | acceptInvitation | Server returned status code ".$mailExistsRes['status_code']." checking email!!");
            return $this->render('invitations/acceptInvitation.html.twig', [
              'error_msg' => 'Ocurrió un error cargando la invitación (4)',
              'email' => '',
              'token' => ''
            ]);
          }else{
            return $this->render('invitations/acceptInvitation.html.twig', [
              'invitation' => $invitation,
              'mailExists' => $mailExistsRes['data']['exists']
            ]);
          }
        }
      }
    }
  }
  
  public function doAcceptInvitation(Request $request , $tokenId) {
    $this->logger->debug("Invitation | doAcceptInvitation | Accepting invitation with token " . $tokenId);
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    $this->logger->debug("Invitation | doAcceptInvitation | Sending accept invitation for user " . $user['email']);
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->acceptInvitation($tokenId);
    if (!$cliRes['resolved']){
      $this->logger->error("Invitation | doAcceptInvitation | Error!!");
      return $this->render('invitations/invitationAccepted.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }else{
      $cliRes = $this->hubClient->getInvitationData($tokenId);
      if (!$cliRes['resolved']){
        $this->logger->error("Invitation | doAcceptInvitation | Error getting invitation data!!");
        return $this->render('invitations/invitationAccepted.html.twig', [
          'error_msg'  => 'Ocurrió un error cargando la invitación (1)',
          'invitation' => NULL
        ]);
      }else{
        if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
          $this->logger->error("Invitation | doAcceptInvitation | Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
          return $this->render('invitations/invitationAccepted.html.twig', [
            'error_msg'  => 'Ocurrió un error cargando la invitación (2)',
            'invitation' => NULL
          ]);
        }else{
          // Load invitation data
          $invitation = $cliRes['data'];
          $invitation['invitationData'] = json_decode($invitation['invitationData']);
          return $this->render('invitations/invitationAccepted.html.twig', [
            'invitation' => $invitation
          ]);
        }
      }
    }
  }
}