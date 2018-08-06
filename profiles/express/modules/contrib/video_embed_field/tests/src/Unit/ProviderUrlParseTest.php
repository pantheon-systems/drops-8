<?php

namespace Drupal\Tests\video_embed_field\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\video_embed_field\Kernel\MockHttpClient;
use Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo;
use Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube;

/**
 * Test that URL parsing for various providers is functioning.
 *
 * @group video_embed_field
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * Test URL parsing works as expected.
   *
   * @dataProvider urlsWithExpectedIds
   */
  public function testUrlParsing($provider, $url, $expected) {
    $this->assertEquals($expected, $provider::getIdFromInput($url));
  }

  /**
   * A data provider for URL parsing test cases.
   *
   * @return array
   *   An array of test cases.
   */
  public function urlsWithExpectedIds() {
    return [
      // Youtube passing cases.
      'YouTube: Standard URL' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://www.youtube.com/watch?v=fdbFVWupSsw',
        'fdbFVWupSsw',
      ],
      'YouTube: Non HTTPS' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'http://www.youtube.com/watch?v=fdbFVWupSsw',
        'fdbFVWupSsw',
      ],
      'YouTube: Non WWW' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://youtube.com/watch?v=fdbFVWupSsw',
        'fdbFVWupSsw',
      ],
      'YouTube: Special Characters' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://youtube.com/watch?v=fdbFV_Wup-Ssw',
        'fdbFV_Wup-Ssw',
      ],
      'YouTube: Short URL' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://youtu.be/fdbFVWupSsw',
        'fdbFVWupSsw',
      ],
      'YouTube: With Language Preference' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://youtube.com/watch?v=fdbFV_Wup-Ssw&hl=fr-ca',
        'fdbFV_Wup-Ssw',
      ],
      'YouTube: Added Query String' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://youtube.com/watch?v=fdbFVWupSsw&some_param=value&t=150',
        'fdbFVWupSsw',
      ],
      'YouTube: Added Query String in first position' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://youtube.com/watch?feature=player_detailpage&v=fdbFV_Wup-Ssw',
        'fdbFV_Wup-Ssw',
      ],
      'YouTube: Short URL Added Query String' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://youtu.be/fdbFVWupSsw?some_param=other&another=something&t=55',
        'fdbFVWupSsw',
      ],
      // Youtube failing cases.
      'YouTube: Non-youtube domain with ?v param' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://www.otherdomain.com/watch?v=fdbFVWupSsw',
        FALSE,
      ],
      'YouTube: Malformed String' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        $this->randomMachineName(),
        FALSE,
      ],
      'YouTube: Playlist URL' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://www.youtube.com/watch?v=-A2Nc3TRpi0&list=PLs4n2zZ8S1eszdZZwDSQ1G8iP95DmJHSh',
        FALSE,
      ],
      'YouTube: Playlist URL (reversed params)' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTube',
        'https://www.youtube.com/watch?list=PLs4n2zZ8S1eszdZZwDSQ1G8iP95DmJHSh&v=-A2Nc3TRpi0',
        FALSE,
      ],
      // Youtube Playlists passing cases.
      'YouTube Playlist' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'https://www.youtube.com/watch?v=xoJH3qZwsHc&list=PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
        'PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
      ],
      'YouTube Playlist: Reversed param order' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'https://www.youtube.com/watch?list=PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB&v=xoJH3qZwsHc',
        'PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
      ],
      'YouTube Playlist: Underscore in ID' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'https://www.youtube.com/watch?list=PLpeDXSh4nHjQCIZmkxg3VSdpR5e8_7X5eB&v=xoJH3qZwsHc',
        'PLpeDXSh4nHjQCIZmkxg3VSdpR5e8_7X5eB',
      ],
      'YouTube Playlist: No HTTPs' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'http://www.youtube.com/watch?v=xoJH3qZwsHc&list=PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
        'PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
      ],
      'YouTube Playlist: No www' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'https://youtube.com/watch?v=xoJH3qZwsHc&list=PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
        'PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
      ],
      'Youtube Playlist: Hyphens' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'https://www.youtube.com/watch?list=PLg7vT2Yor-Q72v4NPNlWXWmT6iJ4t___k&v=5gdSMPaJOf4',
        'PLg7vT2Yor-Q72v4NPNlWXWmT6iJ4t___k',
      ],
      // Youtube Playlists failing cases.
      'YouTube Playlist: Invalid ID' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'https://www.youtube.com/watch?v=xoJH3qZwsHc&list=!@#123',
        FALSE,
      ],
      'YouTube Playlist: No ID' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'https://www.youtube.com/watch?v=xoJH3qZwsHc&list=',
        FALSE,
      ],
      'YouTube Playlist: No List' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\YouTubePlaylist',
        'https://www.youtube.com/watch?v=xoJH3qZwsHc',
        FALSE,
      ],
      // Vimeo passing cases.
      'Vimeo: Normal URL' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo',
        'https://vimeo.com/138627894',
        '138627894',
      ],
      'Vimeo: WWW URL' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo',
        'https://www.vimeo.com/138627894',
        '138627894',
      ],
      'Vimeo: Non HTTPS' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo',
        'http://www.vimeo.com/138627894',
        '138627894',
      ],
      'Vimeo: Channel URL' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo',
        'https://vimeo.com/channels/staffpicks/138627894',
        '138627894',
      ],
      'Vimeo: Private Video' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo',
        'https://vimeo.com/173101914/aab5894fec',
        '173101914',
      ],
      'Vimeo: with timeindex' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo',
        'https://vimeo.com/193517656#t=160s',
        '193517656',
      ],
      // Vimeo failing cases.
      'Vimeo: Malformed String' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo',
        $this->randomMachineName(),
        FALSE,
      ],
      'Vimeo: Non numeric channel page' => [
        'Drupal\video_embed_field\Plugin\video_embed_field\Provider\Vimeo',
        'https://vimeo.com/channels/staffpicks/some-page',
        FALSE,
      ],
    ];
  }

  /**
   * Test the langauge parsing feature.
   *
   * @dataProvider languageParseTestCases
   */
  public function testYouTubeLanguageParsing($url, $expected) {
    $provider = new YouTube([
      'input' => $url,
    ], '', [], new MockHttpClient());
    $embed = $provider->renderEmbedCode(100, 100, TRUE);
    $language = isset($embed['#query']['cc_lang_pref']) ? $embed['#query']['cc_lang_pref'] : FALSE;
    $this->assertEquals($expected, $language);
  }

  /**
   * A data provider for testYouTubeLanguageParsing.
   *
   * @return array
   *   An array of test cases.
   */
  public function languageParseTestCases() {
    return [
      'Simple Preference' => [
        'https://youtube.com/watch?v=fdbFV_Wup-Ssw&hl=fr',
        'fr',
      ],
      'Preference with Hyphen' => [
        'https://youtube.com/watch?v=fdbFV_Wup-Ssw&hl=fr-ca',
        'fr-ca',
      ],
      'Invalid Language' => [
        'https://youtube.com/watch?v=fdbFV_Wup-Ssw&hl=<test>',
        FALSE,
      ],
      'Multiple Parameters' => [
        'https://youtube.com/watch?v=fdbFV_Wup-Ssw&hl=au&anotherparam=1',
        'au',
      ],
    ];
  }

  /**
   * Test the YouTube time index parsing.
   *
   * @dataProvider youTubeTimeIndexTestCases
   */
  public function testYouTubeTimeIndex($url, $expected) {
    $provider = new YouTube([
      'input' => $url,
    ], '', [], new MockHttpClient());
    $embed = $provider->renderEmbedCode(100, 100, TRUE);
    $this->assertEquals($expected, $embed['#query']['start']);
  }

  /**
   * A data provider for testYouTubeTimeIndex.
   *
   * @return array
   *   An array of test cases.
   */
  public function youTubeTimeIndexTestCases() {
    return [
      'Simple Timeindex' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw&t=15',
        '15',
      ],
      'No Timeindex' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw',
        '0',
      ],
      'Invalid Timeindex' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw&t=time',
        '0',
      ],
    ];
  }

  /**
   * Test the Vimeo time index integration.
   *
   * @dataProvider vimeoTimeIndexTestCases
   */
  public function testVimeoTimeIndex($url, $expected, $exception_expected = FALSE) {
    $exception_triggered = FALSE;
    try {
      $provider = new Vimeo([
        'input' => $url,
      ], '', [], new MockHttpClient());
    }
    catch (\Exception $e) {
      $exception_triggered = TRUE;
    }

    $this->assertEquals($exception_expected, $exception_triggered);

    if (!$exception_triggered) {
      $embed = $provider->renderEmbedCode(100, 100, TRUE);
      $this->assertEquals($expected, isset($embed['#fragment']) ? $embed['#fragment'] : FALSE);
    }
  }

  /**
   * A data provider for testVimeoTimeIndex.
   *
   * @return array
   *   An array of test cases.
   */
  public function vimeoTimeIndexTestCases() {
    return [
      'Standard time index' => [
        'https://vimeo.com/193517656#t=150s',
        't=150s',
        FALSE,
      ],
      'Empty start time' => [
        'https://vimeo.com/193517656#t=',
        NULL,
        TRUE,
      ],
      'Empty start time (with seconds)' => [
        'https://vimeo.com/193517656#t=s',
        NULL,
        TRUE,
      ],
      'Non numeric start time' => [
        'https://vimeo.com/193517656#t=STARTs',
        NULL,
        TRUE,
      ],
      'Non t fragment' => [
        'https://vimeo.com/193517656#o=150s',
        NULL,
        TRUE,
      ],
      'No seconds' => [
        'https://vimeo.com/193517656#t=15',
        NULL,
        TRUE,
      ],
    ];
  }

}
