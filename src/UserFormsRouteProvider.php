<?php

namespace Drupal\user_forms_split;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;
use Drupal\user\Entity\UserRouteProvider;

/**
 * Extends default user route provider to add password and email form routes.
 */
class UserFormsRouteProvider extends UserRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);

    // $route_collection->add('user.reset.login',$this->getOneTimeLoginRoute());
    // Override /user/{user}/edit.
    $route = (new Route('/user/{user}/edit'))
      ->setDefaults([
        '_entity_form' => 'user.edit',
        '_title_callback' => 'Drupal\user\Controller\UserController::userTitle',
      ])
      ->setOption('_admin_route', TRUE)
      ->setRequirement('user', '\d+')
      ->setRequirement('_entity_access', 'user.update');
    $route_collection->add('entity.user.edit_form', $route);

    return $route_collection;
  }

  /**
   * Helper function to create route object for `user.reset.login`.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route object.
   */
  protected function getOneTimeLoginRoute() {
    // Override user on-time login function.
    return (new Route('/user/reset/{uid}/{timestamp}/{hash}/login'))
      ->setDefaults([
        '_controller' => 'Drupal\user_forms_split\Controller\UserFormsController::resetPassLogin',
        '_title' => 'Reset password',
      ])
      ->setRequirement('_user_is_logged_in', 'FALSE')
      ->setOption('_maintenance_access', TRUE)
      ->setOption('no_cache', TRUE);
  }

}
