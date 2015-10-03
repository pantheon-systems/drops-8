<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\filter\StringFilter.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;

/**
 * Basic textfield filter to handle string filtering commands
 * including equality, like, not like, etc.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("string")
 */
class StringFilter extends FilterPluginBase {

  // exposed filter options
  protected $alwaysMultiple = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['expose']['contains']['required'] = array('default' => FALSE);

    return $options;
  }

  /**
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   */
  function operators() {
    $operators = array(
      '=' => array(
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opEqual',
        'values' => 1,
      ),
      '!=' => array(
        'title' => $this->t('Is not equal to'),
        'short' => $this->t('!='),
        'method' => 'opEqual',
        'values' => 1,
      ),
      'contains' => array(
        'title' => $this->t('Contains'),
        'short' => $this->t('contains'),
        'method' => 'opContains',
        'values' => 1,
      ),
      'word' => array(
        'title' => $this->t('Contains any word'),
        'short' => $this->t('has word'),
        'method' => 'opContainsWord',
        'values' => 1,
      ),
      'allwords' => array(
        'title' => $this->t('Contains all words'),
        'short' => $this->t('has all'),
        'method' => 'opContainsWord',
        'values' => 1,
      ),
      'starts' => array(
        'title' => $this->t('Starts with'),
        'short' => $this->t('begins'),
        'method' => 'opStartsWith',
        'values' => 1,
      ),
      'not_starts' => array(
        'title' => $this->t('Does not start with'),
        'short' => $this->t('not_begins'),
        'method' => 'opNotStartsWith',
        'values' => 1,
      ),
      'ends' => array(
        'title' => $this->t('Ends with'),
        'short' => $this->t('ends'),
        'method' => 'opEndsWith',
        'values' => 1,
      ),
      'not_ends' => array(
        'title' => $this->t('Does not end with'),
        'short' => $this->t('not_ends'),
        'method' => 'opNotEndsWith',
        'values' => 1,
      ),
      'not' => array(
        'title' => $this->t('Does not contain'),
        'short' => $this->t('!has'),
        'method' => 'opNotLike',
        'values' => 1,
      ),
      'shorterthan' => array(
        'title' => $this->t('Length is shorter than'),
        'short' => $this->t('shorter than'),
        'method' => 'opShorterThan',
        'values' => 1,
      ),
      'longerthan' => array(
        'title' => $this->t('Length is longer than'),
        'short' => $this->t('longer than'),
        'method' => 'opLongerThan',
        'values' => 1,
      ),
      'regular_expression' => array(
        'title' => $this->t('Regular expression'),
        'short' => $this->t('regex'),
        'method' => 'opRegex',
        'values' => 1,
      ),
    );
    // if the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += array(
        'empty' => array(
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ),
        'not empty' => array(
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ),
      );
    }

    return $operators;
  }

  /**
   * Build strings from the operators() for 'select' options
   */
  public function operatorOptions($which = 'title') {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    $options = $this->operatorOptions('short');
    $output = '';
    if (!empty($options[$this->operator])) {
      $output = $options[$this->operator];
    }
    if (in_array($this->operator, $this->operatorValues(1))) {
      $output .= ' ' . $this->value;
    }
    return $output;
  }

  protected function operatorValues($values = 1) {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      if (isset($info['values']) && $info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  /**
   * Provide a simple textfield for equality
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // We have to make some choices when creating this as an exposed
    // filter form. For example, if the operator is locked and thus
    // not rendered, we can't render dependencies; instead we only
    // render the form items we need.
    $which = 'all';
    if (!empty($form['operator'])) {
      $source = ':input[name="options[operator]"]';
    }
    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(1)) ? 'value' : 'none';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }
    }

    if ($which == 'all' || $which == 'value') {
      $form['value'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
        '#size' => 30,
        '#default_value' => $this->value,
      );
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }

      if ($which == 'all') {
        // Setup #states for all operators with one value.
        foreach ($this->operatorValues(1) as $operator) {
          $form['value']['#states']['visible'][] = array(
            $source => array('value' => $operator),
          );
        }
      }
    }

    if (!isset($form['value'])) {
      // Ensure there is something in the 'value'.
      $form['value'] = array(
        '#type' => 'value',
        '#value' => NULL
      );
    }
  }

  function operator() {
    return $this->operator == '=' ? 'LIKE' : 'NOT LIKE';
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  public function opEqual($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value, $this->operator());
  }

  protected function opContains($field) {
    $this->query->addWhere($this->options['group'], $field, '%' . db_like($this->value) . '%', 'LIKE');
  }

  protected function opContainsWord($field) {
    $where = $this->operator == 'word' ? db_or() : db_and();

    // Don't filter on empty strings.
    if (empty($this->value)) {
      return;
    }

    preg_match_all('/ (-?)("[^"]+"|[^" ]+)/i', ' ' . $this->value, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      $phrase = FALSE;
      // Strip off phrase quotes
      if ($match[2]{0} == '"') {
        $match[2] = substr($match[2], 1, -1);
        $phrase = TRUE;
      }
      $words = trim($match[2], ',?!();:-');
      $words = $phrase ? array($words) : preg_split('/ /', $words, -1, PREG_SPLIT_NO_EMPTY);
      foreach ($words as $word) {
        $where->condition($field, '%' . db_like(trim($word, " ,!?")) . '%', 'LIKE');
      }
    }

    if (!$where) {
      return;
    }

    // previously this was a call_user_func_array but that's unnecessary
    // as views will unpack an array that is a single arg.
    $this->query->addWhere($this->options['group'], $where);
  }

  protected function opStartsWith($field) {
    $this->query->addWhere($this->options['group'], $field, db_like($this->value) . '%', 'LIKE');
  }

  protected function opNotStartsWith($field) {
    $this->query->addWhere($this->options['group'], $field, db_like($this->value) . '%', 'NOT LIKE');
  }

  protected function opEndsWith($field) {
    $this->query->addWhere($this->options['group'], $field, '%' . db_like($this->value), 'LIKE');
  }

  protected function opNotEndsWith($field) {
    $this->query->addWhere($this->options['group'], $field, '%' . db_like($this->value), 'NOT LIKE');
  }

  protected function opNotLike($field) {
    $this->query->addWhere($this->options['group'], $field, '%' . db_like($this->value) . '%', 'NOT LIKE');
  }

  protected function opShorterThan($field) {
    $placeholder = $this->placeholder();
    // Type cast the argument to an integer because the SQLite database driver
    // has to do some specific alterations to the query base on that data type.
    $this->query->addWhereExpression($this->options['group'], "LENGTH($field) < $placeholder", array($placeholder => (int) $this->value));
  }

  protected function opLongerThan($field) {
    $placeholder = $this->placeholder();
    // Type cast the argument to an integer because the SQLite database driver
    // has to do some specific alterations to the query base on that data type.
    $this->query->addWhereExpression($this->options['group'], "LENGTH($field) > $placeholder", array($placeholder => (int) $this->value));
  }

  /**
   * Filters by a regular expression.
   *
   * @param string $field
   *   The expression pointing to the queries field, for example "foo.bar".
   */
  protected function opRegex($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value, 'REGEXP');
  }

  protected function opEmpty($field) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addWhere($this->options['group'], $field, NULL, $operator);
  }

}
