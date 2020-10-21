<?php

namespace Drupal\user_forms_split\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Form handler for the profile forms.
 */
class UserEditPasswordForm extends CurrentPasswordFormBase {

  /**
   * The password policy entity storage handler.
   *
   * @var \Drupal\user_policies\Entity\Storage\PasswordPolicyStorageInterface
   */
  protected $policyStorage;

  /**
   * Create a new instance of a form which requires the current password.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   Current route to use in determining the account being altered.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged in user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $current_route_match, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($current_route_match, $current_user);
    $this->policyStorage = $entity_type_manager->getStorage('password_policy');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_edit_password';
  }

  /**
   * {@inheritdoc}
   */
  public static function formPageTitle() {
    return new TranslatableMarkup('Change Password');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames() {
    return ['pass'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $user = $this->currentUser();
    $account = $this->getEntity();
    $policy = $this->policyStorage->getPasswordPolicy($account);
    $expiration = $policy->getPasswordExpires($account) ?: NULL;

    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#weight' => -10,
      '#attributes' => [
        'class' => ['heading'],
      ],
      '#value' => $this->formPageTitle(),
    ];

    $form['pass'] = [
      '#type' => 'password_confirm',
      '#size' => 25,
      '#description' => $this->t('To change the current user password, enter the new password in both fields.'),
      '#attached' => [],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    if ($user->id() == $account->id() && !empty($expiration) && $expiration < REQUEST_TIME) {
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Logout'),
        '#url' => Url::fromRoute('user.logout'),
        '#attributes' => [
          'class' => ['button', 'button--secondary'],
        ],
      ];
    }
    else {
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => $account->toUrl('edit-form'),
        '#attributes' => [
          'class' => ['button', 'button--secondary'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Display success message to user.
    $message = $this->t('Password updated successfully.');
    $this->messenger()->addMessage($message);
  }

}
