<?php

/*
 * TwitterText
 *
 * A class for linkifying tweets by entities.
 * Requires PHP 5.3.0 or later.
 * 
 * @Version 3.0.0
 * @Author  CertaiN
 * @License BSD 2-Clause
 * @GitHub  http://github.com/mpyw/TwitterText
 */

namespace mpyw\TwitterText;

class ImageUtil {
    
   /**
    * Generate image urls from $expanded_url.
    * 
    * @param  string    $expanded_url See here: https://dev.twitter.com/docs/tweet-entities
    * @return mixed                   If $expanded_url is matched with supported format,
    *                                 a stdClass object that contains 5 properties,
    *                                 "full", "large", "medium", "small", "mini".
    *                                 If not, this returns FALSE.
    */
    public static function getUrls($expanded_url) {
        $elements = parse_url($expanded_url);
        if (!isset($elements['host'])) {
            return false;
        }
        $elements += array(
            'path' => '',
            'query' => '',
        );
        parse_str($elements['query'], $q);
        switch ($elements['host']) {
            case 'youtu.be':
                if (preg_match('@^/(\\w++)/*+$@', $elements['path'], $matches)) {
                    $q = array('v' => $matches[1]);
                } else {
                    return false;
                }
            case 'www.youtube.com':
            case 'youtube.com':
            case 'jp.youtube.com':
                if (!isset($q['v'])) {
                    return false;
                }
                $header = 'http://i.ytimg.com/vi/' . $q['v'] . '/';
                return (object)array(
                    'full'   => $header . 'hqdefault.jpg',
                    'large'  => $header . 'hqdefault.jpg',
                    'medium' => $header . 'mqdefault.jpg',
                    'small'  => $header . 'default.jpg',
                    'mini'   => $header . 'default.jpg',
                );
            case 'twitpic.com':
                if (preg_match('@^/(\\w++)/*+$@', $elements['path'], $matches)) {
                    $header = 'http://twitpic.com/show/';
                    return (object)array(
                        'full'   => $header . 'full/'  . $matches[1],
                        'large'  => $header . 'large/' . $matches[1],
                        'medium' => $header . 'large/' . $matches[1],
                        'small'  => $header . 'thumb/' . $matches[1],
                        'mini'   => $header . 'mini/'  . $matches[1],
                    );
                } else {
                    return false;
                }
            case 'moby.to':
                if (preg_match('@^/(\\w++)/*+$@', $elements['path'], $matches)) {
                    $header = 'http://moby.to/' . $matches[1];
                    return (object)array(
                        'full'   => $header . ':full',
                        'large'  => $header . ':view',
                        'medium' => $header . ':medium',
                        'small'  => $header . ':thumbnail',
                        'mini'   => $header . ':thumbnail',
                    );
                } else {
                    return false;
                }
            case 'yfrog.com':
            case 'twitter.yfrog.com':
                if (preg_match('@^/(\\w++)/*+$@', $elements['path'], $matches)) {
                    $header = 'http://yfrog.com/' . $matches[1];
                    return (object)array(
                        'full'   => $header . ':medium',
                        'large'  => $header . ':medium',
                        'medium' => $header . ':iphone',
                        'small'  => $header . ':small',
                        'mini'   => $header . ':small',
                    );
                } else {
                    return false;
                }
            case 'movapic.com':
                if (preg_match('@^/pic/(\\w++)/*+$@', $elements['path'], $matches)) {
                    $header = 'http://image.movapic.com/pic/';
                    $param  = '_' . $matches[1] . 'jpeg';
                    return (object)array(
                        'full'   => $header . 'm_' . $matches[1] . '.jpeg',
                        'large'  => $header . 'm_' . $matches[1] . '.jpeg',
                        'medium' => $header . 's_' . $matches[1] . '.jpeg',
                        'small'  => $header . 's_' . $matches[1] . '.jpeg',
                        'mini'   => $header . 't_' . $matches[1] . '.jpeg',
                    );
                } else {
                    return false;
                }
            case 'instagram.com':
            case 'instagr.am':
                if (preg_match('@^/p/([\\w-]++)/*+$@', $elements['path'], $matches)) {
                    $header = 'http://instagr.am/p/' . $matches[1] . '/media?size=';
                    return (object)array(
                        'full'   => $header . 'l',
                        'large'  => $header . 'l',
                        'medium' => $header . 'm',
                        'small'  => $header . 't',
                        'mini'   => $header . 't',
                    );
                } else {
                    return false;
                }
            case 'f.hatena.ne.jp':
                if (preg_match('@^/(([\\w-])[\\w-]*+)/((\\d{8})\\d++)/*+$@', $elements['path'], $matches)) {
                    $header = 
                        'http://img.f.hatena.ne.jp/images/fotolife/' .
                        $matches[2] . '/' . $matches[1] . '/' . $matches[4] . '/' . $matches[3]
                    ;
                    return (object)array(
                        'full'   => $header . '.jpg',
                        'large'  => $header . '.jpg',
                        'medium' => $header . '_120.jpg',
                        'small'  => $header . '_120.jpg',
                        'mini'   => $header . '_m.jpg',
                    );
                } else {
                    return false;
                }
            case 'img.ly':
                if (preg_match('@^/(\\w++)/*+$@', $elements['path'], $matches)) {
                    $header = 'http://img.ly/show/';
                    return (object)array(
                        'full'   => $header . 'full/'   . $matches[1],
                        'large'  => $header . 'large/'  . $matches[1],
                        'medium' => $header . 'medium/' . $matches[1],
                        'small'  => $header . 'thumb/'  . $matches[1],
                        'mini'   => $header . 'mini/'   . $matches[1],
                    );
                } else {
                    return false;
                }
            case 'i.imgur.com':
            case 'imgur.com':
                if (preg_match('@^/(\\w++)[.\\w]*+/*+$@', $elements['path'], $matches)) {
                    $header = 'http://i.imgur.com/' . $matches[1];
                    return (object)array(
                        'full'   => $header . '.jpg',
                        'large'  => $header . 'l.jpg',
                        'medium' => $header . 'l.jpg',
                        'small'  => $header . 's.jpg',
                        'mini'   => $header . 's.jpg',
                    );
                } else {
                    return false;
                }
            case 'photozou.jp':
                if (preg_match('@^/photo/show/\\d++/(\\d++)/*+$@', $elements['path'], $matches)) {
                    $header = 'http://photozou.jp/p/';
                    return (object)array(
                        'full'   => $header . 'img/'   . $matches[1],
                        'large'  => $header . 'img/'   . $matches[1],
                        'medium' => $header . 'img/'   . $matches[1],
                        'small'  => $header . 'thumb/' . $matches[1],
                        'mini'   => $header . 'thumb/' . $matches[1],
                    );
                } else {
                    return false;
                }
            case 'p.twipple.jp':
                if (preg_match('@^/(\\w++)/*+$@', $elements['path'], $matches)) {
                    $header = 'http://p.twpl.jp/show/';
                    return (object)array(
                        'full'   => $header . 'orig/'  . $matches[1],
                        'large'  => $header . 'large/' . $matches[1],
                        'medium' => $header . 'large/' . $matches[1],
                        'small'  => $header . 'thumb/' . $matches[1],
                        'mini'   => $header . 'thumb/' . $matches[1],
                    );
                } else {
                    return false;
                }
            default:
                return false;
        }
    }
    
    final protected function __construct() { }
    
}