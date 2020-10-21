<?php

namespace Drupal\user_forms_split\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change the route associated with the user one time login page
    // (/user/reset/{uid}/{timestamp}/{hash}/login).
    if ($route = $collection->get('user.reset.login')) {
      $route->setDefault('_controller', '\Drupal\user_forms_split\Controller\UserFormsController::resetPassLogin');
    }
  }

}
