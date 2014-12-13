<?php

/*
 * TwitterText
 *
 * A class for linkifying tweets by entities.
 * Requires PHP 5.3.0 or later.
 * 
 * @Version 2.1.0
 * @Author  CertaiN
 * @License BSD 2-Clause
 * @GitHub  http://github.com/mpyw/TwitterText
 */

abstract class TwitterText {
    
    protected $text;
    
   /**
    * You have to implement the following methods on your extended classes.
    * 
    * @param  stdClass  See here: https://dev.twitter.com/docs/tweet-entities
    * @return mixed     You have to return linkified string.
    *                   When NULL returned, its linkification is aborted.
    */
    abstract protected function linkifyUserMention(stdClass $user_mention);
    abstract protected function linkifyUrl(stdClass $url);
    abstract protected function linkifyHashtag(stdClass $hashtag);
    abstract protected function linkifySymbol(stdClass $symbol);
    abstract protected function linkifyMediaList(array $media_list);
    
   /**
    * Constructor Wapper. Useful for method chaining.
    * 
    * @param  string $text Text returned from Twitter API.
    *                      Incompletely escaped chars are automatically fixed.
    */
    final public static function factory($text) {
        return new static($text);
    }
    
   /**
    * Used for linkifying texts.
    * Your methods based on abstract methods will be used here.
    * 
    * @param  stdClass $entities            See here: https://dev.twitter.com/docs/tweet-entities
    * @param  stdClass [$extended_entities] 
    * @return string                        Unescaped raw text.
    */
    final public function linkify(stdClass $entities, stdClass $extended_entities = null) {
        $enc = mb_internal_encoding();
        mb_internal_encoding('UTF-8');
        $info = array();
        foreach (array('user_mention', 'url', 'hashtag', 'symbol') as $type) {
            foreach ($entities->{$type . 's'} as $value) {
                $info[$value->indices[0]] = (object)array(
                    'type'    => $type,
                    'indices' => $value->indices,
                    'value'   => $value,
                );
            }
        }
        if (isset($extended_entities->media[0])) {
            $info[$extended_entities->media[0]->indices[0]] = (object)array(
                'type'    => 'media_list',
                'indices' => $extended_entities->media[0]->indices,
                'value'   => $extended_entities->media,
            );
        } elseif (isset($entities->media[0])) {
            $info[$entities->media[0]->indices[0]] = (object)array(
                'type'    => 'media_list',
                'indices' => $entities->media[0]->indices,
                'value'   => $entities->media,
            );
        }
        ksort($info);
        $p = 0;
        $text = $this->text;
        foreach ($info as $item) {
            list($a, $b) = $item->indices;
            $new = call_user_func(array($this, lcfirst(implode(array_map('ucfirst', explode('_', 'linkify_' . $item->type))))), $item->value);
            if ($new === null) {
                continue;
            }
            $text = mb_substr($text, 0, $a + $p) . $new . mb_substr($text, $b + $p);
            $p += mb_strlen($new) - $b + $a;
        }
        mb_internal_encoding($enc);
        return preg_replace('/&(?!amp;|lt;|gt;)/', '&amp;', $text);
    }
    
   /**
    * Your inherited linkifyUrl() should call this method for getting image urls.
    * 
    * @final
    * @static
    * @access protected 
    * @param  string    $expanded_url See here: https://dev.twitter.com/docs/tweet-entities
    * @return mixed                   If $expanded_url is matched with supported format,
    *                                 a stdClass object that contains 5 properties,
    *                                 "full", "large", "medium", "small", "mini".
    *                                 If not, this returns FALSE.
    */
    final protected static function getImageUrls($expanded_url) {
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
    
    final protected function __construct($text) {
        $this->text = $text;
    }
    
}