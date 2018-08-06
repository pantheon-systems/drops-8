<?php

namespace Drupal\user_external_invite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user_external_invite\InviteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class UserInviteController extends ControllerBase {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * @var \Drupal\user_external_invite\InviteManager
   */
  private $inviteManager;

  /**
   * UserInviteController constructor.
   * @param \Drupal\user_external_invite\InviteManager $inviteManager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   */
  public function __construct(InviteManager $inviteManager, LoggerChannelFactoryInterface $loggerChannelFactory) {

    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->inviteManager = $inviteManager;
  }


  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {

    $invite_manager = $container->get('user_external_invite.invite_manager');
    $logger_factory = $container->get('logger.factory');

    return new static($invite_manager, $logger_factory);
  }


  /**
   * @return array
   */
  public function inviteUsers() {

    $this->inviteManager->sendInvite('bull');

    $store = $this->keyValue('ding')->get('joop');

    $this->loggerChannelFactory->get('default')->debug($store);

    //return new Response($store);

    return [
      '#markup' => 'Page Content...',
    ];
  }


  public function manageInvites() {

    return [
      '#title' => 'Manage Invites',
      '#markup' => '<h2>stuff...</h2>',
    ];
  }


}
