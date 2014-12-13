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

abstract class Linkifier {
    
    protected $text;
    
   /**
    * You have to implement the following methods on your extended classes.
    * 
    * @param  stdClass  See here: https://dev.twitter.com/docs/tweet-entities
    * @return mixed     You have to return linkified string.
    *                   When NULL returned, its linkification is aborted.
    */
    abstract protected function linkifyUserMention(\stdClass $user_mention);
    abstract protected function linkifyUrl(\stdClass $url);
    abstract protected function linkifyHashtag(\stdClass $hashtag);
    abstract protected function linkifySymbol(\stdClass $symbol);
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
    final public function linkify(\stdClass $entities, \stdClass $extended_entities = null) {
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
        $text = (string)$this->text;
        foreach ($info as $item) {
            $new = call_user_func(
                array($this, lcfirst(implode(array_map(
                    'ucfirst',
                    explode('_', 'linkify_' . $item->type)
                )))),
                $item->value
            );
            if (!is_string($new)) {
                continue;
            }
            list($a, $b) = $item->indices;
            $text = mb_substr($text, 0, $a + $p) . $new . mb_substr($text, $b + $p);
            $p += mb_strlen($new) - $b + $a;
        }
        mb_internal_encoding($enc);
        return preg_replace('/&(?!amp;|lt;|gt;)/', '&amp;', $text);
    }
    
    final protected function __construct($text) {
        $this->text = $text;
    }
    
}