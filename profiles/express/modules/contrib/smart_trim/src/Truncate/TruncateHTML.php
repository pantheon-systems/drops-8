<?php

/**
 * @file
 * Contains trim functionality.
 *
 * As noted on
 *    http://www.pjgalbraith.com/2011/11/truncating-text-html-with-php/
 * with some modifications to adhere to the Drupal Coding Standards.
 */

/*
 * Copyright 2011  Patrick Galbraith  (email : patrick.j.galbraith@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, version 2, as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Drupal\smart_trim\Truncate;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

/**
 * Class TruncateHTML.
 */
class TruncateHTML {

  /**
   * Total characters.
   *
   * @type int
   */
  protected $charCount = 0;

  /**
   * Total words.
   *
   * @type int
   */
  protected $wordCount = 0;

  /**
   * Character / Word limit.
   *
   * @type int
   */
  protected $limit;

  /**
   * Element to start on.
   *
   * @type \DOMElement
   */
  protected $startNode;

  /**
   * Ellipsis character.
   *
   * @type string
   */
  protected $ellipsis;

  /**
   * Did we find the breakpoint?
   *
   * @type bool
   */
  protected $foundBreakpoint = FALSE;


  /**
   * Sets up object for use.
   *
   * @param string $html
   *   Text to be prepared.
   * @param int $limit
   *   Amount of text to return.
   * @param string $ellipsis
   *   Characters to use at the end of the text.
   *
   * @return \DOMDocument
   *   Prepared DOMDocument to work with.
   */
  protected function init($html, $limit, $ellipsis) {

    $dom = Html::load($html);

    // The body tag node, our html fragment is automatically wrapped in
    // a <html><body> etc.
    $this->startNode = $dom->getElementsByTagName("body")->item(0);
    $this->limit = $limit;
    $this->ellipsis = $ellipsis;
    $this->charCount = 0;
    $this->wordCount = 0;
    $this->foundBreakpoint = FALSE;

    return $dom;
  }

  /**
   * Truncates HTML text by characters.
   *
   * @param string $html
   *   Text to be updated.
   * @param int $limit
   *   Amount of text to allow.
   * @param string $ellipsis
   *   Characters to use at the end of the text.
   *
   * @return mixed
   *   Resulting text.
   */
  public function truncateChars($html, $limit, $ellipsis = '...') {

    if ($limit <= 0 || $limit >= strlen(strip_tags($html))) {
      return $html;
    }
    $dom = $this->init($html, $limit, $ellipsis);
    // Pass the body node on to be processed.
    $this->domNodeTruncateChars($this->startNode);
    return Html::serialize($dom);
  }

  /**
   * Truncates HTML text by words.
   *
   * @param string $html
   *   Text to be updated.
   * @param int $limit
   *   Amount of text to allow.
   * @param string $ellipsis
   *   Characters to use at the end of the text.
   *
   * @return mixed
   *   Resulting text.
   */
  public function truncateWords($html, $limit, $ellipsis = '...') {

    if ($limit <= 0 || $limit >= $this->countWords(strip_tags($html))) {
      return $html;
    }

    $dom = $this->init($html, $limit, $ellipsis);
    // Pass the body node on to be processed.
    $this->domNodeTruncateWords($this->startNode);
    return Html::serialize($dom);
  }

  /**
   * Truncates a DOMNode by character count.
   *
   * @param \DOMNode $domnode
   *   Object to be truncated.
   */
  protected function domNodeTruncateChars(\DOMNode $domnode) {

    foreach ($domnode->childNodes as $node) {

      if ($this->foundBreakpoint == TRUE) {
        return;
      }

      if ($node->hasChildNodes()) {
        $this->domNodeTruncateChars($node);
      }
      else {
        $text = html_entity_decode($node->nodeValue, ENT_QUOTES, 'UTF-8');
        $length = Unicode::strlen($text);
        if (($this->charCount + $length) >= $this->limit) {
          // We have found our end point.
          $node->nodeValue = Unicode::substr($text, 0, $this->limit - $this->charCount);
          $this->removeProceedingNodes($node);
          $this->insertEllipsis($node);
          $this->foundBreakpoint = TRUE;
          return;
        }
        else {
          $this->charCount += $length;
        }
      }
    }
  }

  /**
   * Truncates a DOMNode by words.
   *
   * @param \DOMNode $domnode
   *   Object to be truncated.
   */
  protected function domNodeTruncateWords(\DOMNode $domnode) {

    foreach ($domnode->childNodes as $node) {

      if ($this->foundBreakpoint == TRUE) {
        return;
      }

      if ($node->hasChildNodes()) {
        $this->domNodeTruncateWords($node);
      }
      else {
        $cur_count = $this->countWords($node->nodeValue);

        if (($this->wordCount + $cur_count) >= $this->limit) {
          // We have found our end point.
          if ($cur_count > 1 && ($this->limit - $this->wordCount) < $cur_count) {
            // Note that PREG_SPLIT_OFFSET_CAPTURE and UTF-8 is interesting.
            // preg_split() works on the string as an array of bytes therefore
            // in order to use it's results we need to use non unicode aware
            // functions.
            // @see https://bugs.php.net/bug.php?id=67487
            $words = preg_split("/[\n\r\t ]+/", $node->nodeValue, ($this->limit - $this->wordCount) + 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
            end($words);
            $last_word = prev($words);
            $node->nodeValue = substr($node->nodeValue, 0, $last_word[1] + strlen($last_word[0]));
          }

          $this->removeProceedingNodes($node);
          $this->insertEllipsis($node);
          $this->foundBreakpoint = TRUE;
          return;
        }
        else {
          $this->wordCount += $cur_count;
        }
      }
    }
  }

  /**
   * Removes preceding sibling node.
   *
   * @param \DOMNode $domnode
   *   Node to be altered.
   */
  protected function removeProceedingNodes(\DOMNode $domnode) {
    $nextnode = $domnode->nextSibling;

    if ($nextnode !== NULL) {
      $this->removeProceedingNodes($nextnode);
      $domnode->parentNode->removeChild($nextnode);
    }
    else {
      // Scan upwards till we find a sibling.
      $curnode = $domnode->parentNode;
      while ($curnode !== $this->startNode) {
        if ($curnode->nextSibling !== NULL) {
          $curnode = $curnode->nextSibling;
          $this->removeProceedingNodes($curnode);
          $curnode->parentNode->removeChild($curnode);
          break;
        }
        $curnode = $curnode->parentNode;
      }
    }
  }

  /**
   * Inserts the Elipsis character to the node.
   *
   * @param \DOMNode $domnode
   *   Node to be altered.
   */
  protected function insertEllipsis(\DOMNode $domnode) {
    // HTML tags to avoid appending the ellipsis to.
    $avoid = array('a', 'strong', 'em', 'h1', 'h2', 'h3', 'h4', 'h5');

    if (in_array($domnode->parentNode->nodeName, $avoid) && ($domnode->parentNode->parentNode !== NULL || $domnode->parentNode->parentNode !== $this->startNode)) {
      // Append as text node to parent instead.
      $textnode = new \DOMText($this->ellipsis);

      if ($domnode->parentNode->parentNode->nextSibling) {
        $domnode->parentNode->parentNode->insertBefore($textnode, $domnode->parentNode->parentNode->nextSibling);
      }
      else {
        $domnode->parentNode->parentNode->appendChild($textnode);
      }
    }
    else {
      // Append to current node.
      $domnode->nodeValue = rtrim($domnode->nodeValue) . $this->ellipsis;
    }
  }

  /**
   * Gets number of words in text.
   *
   * @param string $text
   *   Text to be counted.
   *
   * @return int
   *   Results
   */
  protected function countWords($text) {
    $words = preg_split("/[\n\r\t ]+/", $text, -1, PREG_SPLIT_NO_EMPTY);
    return count($words);
  }

}
