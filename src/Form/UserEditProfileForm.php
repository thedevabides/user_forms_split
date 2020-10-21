<?php

namespace Drupal\user_forms_split\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\ProfileForm;
use Drupal\user\Entity\User;

/**
 * Form handler for the profile forms.
 *
 * @internal
 */
class UserEditProfileForm extends ProfileForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Get the account.
    $account = $this->entity;
    if ($account instanceof User) {
      // Get the current email address for the account.
      $current_email = $account->getEmail();
    }

    // Use #access to hide password, current password, and email form fields.
    $form['account']['pass']['#access'] = FALSE;
    $form['account']['current_pass']['#access'] = FALSE;
    $form['account']['mail']['#access'] = FALSE;

    // Create a new email form section. Will contain a display of the user's
    // current email, and a link to the UserUpdateEmailForm.
    $cf_email = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'cf-email-wrapper',
        ],
      ],
      '#weight' => -20,
      'pseudo_label' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'cf-form-label',
            'pseudo-label-above',
          ],
        ],
        '#value' => $this->t('Email'),
      ],
      'current_email' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'cf-static-value',
          ],
        ],
        '#value' => !empty($current_email) ? $current_email : $this->t('N/A'),
      ],
      'update_button' => [
        '#type' => 'link',
        '#title' => $this->t('Change Email'),
        '#url' => Url::fromRoute('user_forms_split.user.email', ['user' => $this->entity->id()]),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ],
    ];
    // Prepend the new email section to the form.
    $form = ['cf_email' => $cf_email] + $form;

    // New password form section containing UserUpdatePasswordForm link.
    $cf_password = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'cf-password-wrapper',
        ],
      ],
      '#weight' => -19,
      'pseudo_label' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'cf-form-label',
            'pseudo-label-above',
          ],
        ],
        '#value' => $this->t('Password'),
      ],
      'update_button' => [
        '#type' => 'link',
        '#title' => $this->t('Change Password'),
        '#url' => Url::fromRoute('user_forms_split.user.password', ['user' => $this->entity->id()]),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ],
    ];
    // Prepend the new password section to the form.
    $form = ['cf_password' => $cf_password] + $form;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // Add cf button classes to save button.
    if (is_array($element['submit'])) {
      $element['submit'] = array_merge_recursive($element['submit'], [
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ]);
    }

    // Add cf button classes to account deletion button.
    if (is_array($element['delete'])) {
      $element['delete'] = array_merge_recursive($element['delete'], [
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ]);
    }

    return $element;
  }

}
