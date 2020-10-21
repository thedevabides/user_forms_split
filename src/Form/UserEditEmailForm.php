<?php

namespace Drupal\user_forms_split\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Form handler for the profile forms.
 *
 * @internal
 */
class UserEditEmailForm extends CurrentPasswordFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_edit_email';
  }

  /**
   * {@inheritdoc}
   */
  public static function formPageTitle() {
    return new TranslatableMarkup('Change Email');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames() {
    return ['mail'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $user = $this->currentUser();
    $account = $this->getEntity();

    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#weight' => -10,
      '#attributes' => [
        'class' => ['heading'],
      ],
      '#value' => $this->formPageTitle(),
    ];

    // The mail field is NOT required if account originally had no mail set
    // and the user performing the edit has 'administer users' permission.
    // This allows users without email address to be edited and deleted.
    // Also see \Drupal\user\Plugin\Validation\Constraint\UserMailRequired.
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.'),
      '#required' => !(!$account->getEmail() && $user->hasPermission('administer users')),
      '#default_value' => ($account->getEmail() ? $account->getEmail() : ''),
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

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $account->toUrl('edit-form'),
      '#attributes' => [
        'class' => ['button', 'button--secondary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Display success message to user.
    $message = $this->t('Email updated successfully.');
    $this->messenger()->addMessage($message);
  }

}
