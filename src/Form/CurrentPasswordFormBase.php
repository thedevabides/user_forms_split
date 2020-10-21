<?php

namespace Drupal\user_forms_split\Form;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraint;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Base form for user account changes that requires the current password.
 */
abstract class CurrentPasswordFormBase extends FormBase {

  use MessengerTrait;

  /**
   * A loaded entity object to be worked on by this form.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Current route for the form.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Create a new instance of a form which requires the current password.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   Current route to use in determining the account being altered.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged in user.
   */
  public function __construct(RouteMatchInterface $current_route_match, AccountProxyInterface $current_user) {
    $this->routeMatch = $current_route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * A string that can be used as the page or form title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The page title string.
   */
  abstract public static function formPageTitle();

  /**
   * An array of field names that should get updated and validated.
   *
   * @return string[]
   *   The field name.
   */
  abstract protected function getEditedFieldNames();

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $account = $this->getEntity();

    // To skip the current password field, the user must have logged in via a
    // one-time link and have the token in the URL. Store this in $form_state
    // so it persists even on subsequent Ajax requests.
    if (!$form_state->get('user_pass_reset') && ($token = $this->getRequest()->get('pass-reset-token'))) {
      $session_key = 'pass_reset_' . $account->id();
      $user_pass_reset = isset($_SESSION[$session_key]) && Crypt::hashEquals($_SESSION[$session_key], $token);
      $form_state->set('user_pass_reset', $user_pass_reset);
    }

    if ($user->id() == $account->id()) {
      $form['account']['current_pass'] = [
        '#type' => 'password',
        '#title' => $this->t('Current password'),
        '#size' => 25,
        '#access' => !$form_state->get('user_pass_reset'),
        '#required' => !$form_state->get('user_pass_reset'),
        '#weight' => -5,
        '#attributes' => ['autocomplete' => 'off'],
      ];
      $form_state->set('user', $account);

      // The user may only change their own password without their current
      // password if they logged in via a one-time login link.
      if (!$form_state->get('user_pass_reset')) {
        $form['account']['current_pass']['#description'] = $this->t('Required if you want to change the  %pass below.', [
          '%pass' => $this->t('Password'),
        ]);
      }
    }

    return $form;
  }

  /**
   * Get the target user entity for this this form is editing.
   *
   * @return \Drupal\user\Entity\User
   *   Gets a loaded user entity, for which this form is editing.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If no valid user is available, throw a Not Found exception.
   */
  public function getEntity() {
    if (!isset($this->entity)) {
      $this->entity = $this->routeMatch->getParameter('user');

      if (!$this->entity) {
        throw new NotFoundHttpException();
      }
    }

    return $this->entity;
  }

  /**
   * Update the user entity based on the submitted form values.
   *
   * @param array $form
   *   The current form structure and renderable elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state, build and input values.
   *
   * @return \Drupal\user\Entity\User
   *   The update user entity.
   */
  protected function buildEntity(array $form, FormStateInterface $form_state) {
    $account = $this->getEntity();

    // Set values for the form, which provided by the form input.
    foreach ($this->getEditedFieldNames() as $key) {
      $account->set($key, $form_state->getValue($key));
    }

    // Set existing password if set in the form state.
    $current_pass = trim($form_state->getValue('current_pass'));
    if (strlen($current_pass) > 0) {
      $account->setExistingPassword($current_pass);
    }

    // Skip the protected user field constraint if the user came from the
    // password recovery page.
    $account->_skipProtectedUserFieldConstraint = $form_state->get('user_pass_reset');

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $violations = $entity->validate();

    $violations->filterByFieldAccess($this->currentUser());
    $errors = $violations->getByFields($this->getEditedFieldNames());
    $formErrors = [];

    foreach ($errors as $error) {
      if ($error->getConstraint() instanceof ProtectedUserFieldConstraint) {
        $form_state->setError($form['account']['current_pass'], $error->getMessage());
      }
      else {
        $path = str_replace('.', '][', $error->getPropertyPath());
        $formErrors[$path][] = $error->getMessage();
      }
    }

    // Combine error messages if multiple errors are found on a single field.
    foreach ($formErrors as $elementName => $errors) {
      if (count($errors) === 1) {
        $form_state->setErrorByName($elementName, reset($errors));
      }
      else {
        $pwStr = '';
        $pwError = [];

        foreach ($errors as $key => $message) {
          $pwStr .= "@{$key}<br \>";
          $pwError['@' . $key] = $message;
        }

        $form_state->setErrorByName($elementName, t($pwStr, $pwError));
      }
    }

    $entity->setValidationRequired(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->save();

    // If there's a session set to the users id, remove the password reset tag
    // since a new password was saved.
    if (isset($_SESSION['pass_reset_' . $entity->id()])) {
      unset($_SESSION['pass_reset_' . $entity->id()]);
    }

    $url = $this->getEntity()->toUrl('edit-form');
    $form_state->setRedirectUrl($url);
  }

  /**
   * Form submit callback for redirecting to back to the user edit form.
   *
   * @param array $form
   *   The current form structure and renderable elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state, build and input values.
   */
  public function cancelForm(array $form, FormStateInterface $form_state) {
    $url = $this->getEntity()->toUrl('edit-form');
    $form_state->setRedirectUrl($url);
  }

}
